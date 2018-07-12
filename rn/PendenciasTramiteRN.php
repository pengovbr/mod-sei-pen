<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

error_reporting(E_ALL);

class PendenciasTramiteRN extends InfraRN {

    const TIMEOUT_SERVICO_PENDENCIAS = 900;

    private static $instance = null;
    private $strEnderecoServicoPendencias = null;
    private $strLocalizacaoCertificadoDigital = null;
    private $strSenhaCertificadoDigital = null;

    protected function inicializarObjInfraIBanco(){
        return BancoSEI::getInstance();
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new PendenciasTramiteRN(ConfiguracaoSEI::getInstance(), SessaoSEI::getInstance(), BancoSEI::getInstance(), LogSEI::getInstance());
        }

        return self::$instance;
    }

    public function __construct() {
        $objPenParametroRN = new PenParametroRN();

        $this->strLocalizacaoCertificadoDigital = $objPenParametroRN->getParametro('PEN_LOCALIZACAO_CERTIFICADO_DIGITAL');
        $this->strEnderecoServicoPendencias = $objPenParametroRN->getParametro('PEN_ENDERECO_WEBSERVICE_PENDENCIAS');
        //TODO: Urgente - Remover senha do certificado de autenticao dos servios do PEN da tabela de parâmetros
        $this->strSenhaCertificadoDigital = $objPenParametroRN->getParametro('PEN_SENHA_CERTIFICADO_DIGITAL');

        if (InfraString::isBolVazia($this->strEnderecoServicoPendencias)) {
            throw new InfraException('Endereço do serviço de pendências de trâmite do Processo Eletrônico Nacional (PEN) não informado.');
        }

        if (!@file_get_contents($this->strLocalizacaoCertificadoDigital)) {
            throw new InfraException("Certificado digital de autenticação do serviço de integração do Processo Eletrônico Nacional(PEN) não encontrado.");
        }

        if (InfraString::isBolVazia($this->strSenhaCertificadoDigital)) {
            throw new InfraException('Dados de autenticação do serviço de integração do Processo Eletrónico Nacional(PEN) não informados.');
        }
    }



    public function monitorarPendencias() {
        try{
            ini_set('max_execution_time','0');
            ini_set('memory_limit','-1');

            InfraDebug::getInstance()->setBolLigado(true);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(true);
            InfraDebug::getInstance()->limpar();

            PENIntegracao::validarCompatibilidadeModulo();

            $objPenParametroRN = new PenParametroRN();
            SessaoSEI::getInstance(false)->simularLogin('SEI', null, null, $objPenParametroRN->getParametro('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO'));

            $mensagemInicioMonitoramento = 'Iniciando serviço de monitoramento de pendências de trâmites de processos';
            LogSEI::getInstance()->gravar($mensagemInicioMonitoramento, LogSEI::$INFORMACAO);
            $this->gravarLogDebug($mensagemInicioMonitoramento);

            $numIdTramiteRecebido = 0;
            $strStatusTramiteRecebido = '';
            $numQuantidadeErroTramite = 0;
            $arrQuantidadeErrosTramite = array();

            while (true) {
                $this->gravarLogDebug('Recuperando lista de pendências do PEN', 1);
                $arrObjPendenciasDTO = $this->obterPendenciasTramite();
                foreach ($arrObjPendenciasDTO as $objPendenciaDTO) {
                    $this->gravarLogDebug(sprintf(">>> Enviando pendência %d (status %s) para fila de processamento", $objPendenciaDTO->getNumIdentificacaoTramite(), $objPendenciaDTO->getStrStatus()), 3);
                    $this->enviarPendenciaFilaProcessamento($objPendenciaDTO);
                }

                $this->gravarLogDebug("Reiniciando monitoramento de pendências", 1);
                sleep(5);
            }
        }
        catch(Exception $e) {
            InfraDebug::getInstance()->setBolLigado(false);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(false);
            throw $e;
        }
    }

    private function configurarRequisicao()
    {
        $curl = curl_init($this->strEnderecoServicoPendencias);
        curl_setopt($curl, CURLOPT_URL, $this->strEnderecoServicoPendencias);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSLCERT, $this->strLocalizacaoCertificadoDigital);
        curl_setopt($curl, CURLOPT_SSLCERTPASSWD, $this->strSenhaCertificadoDigital);
        curl_setopt($curl, CURLOPT_TIMEOUT, self::TIMEOUT_SERVICO_PENDENCIAS);
        return $curl;
    }


    /**
     * Função para recuperar as pendências de trâmite que já foram recebidas pelo serviço de long pulling e não foram processadas com sucesso
     * @param  num $parNumIdTramiteRecebido
     * @return [type]                          [description]
     */
    private function obterPendenciasTramite()
    {
        //Obter todos os trâmites pendentes antes de iniciar o monitoramento
        $arrPendenciasRetornadas = array();
        $objProcessoEletronicoRN = new ProcessoEletronicoRN();
        $arrObjPendenciasDTO = $objProcessoEletronicoRN->listarPendencias(false) or array();

        $this->gravarLogDebug("Recuperado todas pendências de trâmite do PEN: " . count($arrObjPendenciasDTO), 2);

        foreach ($arrObjPendenciasDTO as $objPendenciaDTO) {
            //Captura todas as pendências e status retornadas para impedir duplicidade
            $arrPendenciasRetornadas[] = sprintf("%d-%s", $objPendenciaDTO->getNumIdentificacaoTramite(), $objPendenciaDTO->getStrStatus());
            yield $objPendenciaDTO;
        }

        //Obter demais pendências do serviço de long pulling
        $bolEncontrouPendencia = false;
        $numUltimoIdTramiteRecebido = 0;

        $arrObjPendenciasDTONovas = array();
        $this->gravarLogDebug("Iniciando monitoramento no serviço de pendências (long polling)", 2);

        do {
            $curl = $this->configurarRequisicao();
            try{
                $arrObjPendenciasDTONovas = array_unique($arrObjPendenciasDTONovas);
                curl_setopt($curl, CURLOPT_URL, $this->strEnderecoServicoPendencias . "?idTramiteDaPendenciaRecebida=" . $numUltimoIdTramiteRecebido);

                //A seguinte requisio ir aguardar a notificao do PEN sobre uma nova pendncia
                //ou at o lanamento da exceo de timeout definido pela infraestrutura da soluo
                //Ambos os comportamentos so esperados para a requisio abaixo.
                $this->gravarLogDebug(sprintf("Executando requisição de pendência com IDT %d como offset", $numUltimoIdTramiteRecebido), 2);
                $strResultadoJSON = curl_exec($curl);

                if(curl_errno($curl)) {
                    if (curl_errno($curl) != 28)
                        throw new InfraException("Erro na requisição do serviço de monitoramento de pendências. Curl: " . curl_errno($curl));

                    $bolEncontrouPendencia = false;
                    $this->gravarLogDebug(sprintf("Timeout de monitoramento de %d segundos do serviço de pendências alcançado", self::TIMEOUT_SERVICO_PENDENCIAS), 2);
                }

                if(!InfraString::isBolVazia($strResultadoJSON)) {
                    $strResultadoJSON = json_decode($strResultadoJSON);

                    if(isset($strResultadoJSON->encontrou) && $strResultadoJSON->encontrou) {
                        $bolEncontrouPendencia = true;
                        $numUltimoIdTramiteRecebido = $strResultadoJSON->IDT;
                        $strUltimoStatusRecebido = $strResultadoJSON->status;
                        $strChavePendencia = sprintf("%d-%s", $strResultadoJSON->IDT, $strResultadoJSON->status);
                        $objPendenciaDTO = new PendenciaDTO();
                        $objPendenciaDTO->setNumIdentificacaoTramite($strResultadoJSON->IDT);
                        $objPendenciaDTO->setStrStatus($strResultadoJSON->status);

                        //Não processo novamente as pendências já capturadas na consulta anterior ($objProcessoEletronicoRN->listarPendencias)
                        //Considera somente as novas identificadas pelo serviço de monitoramento
                        if(!in_array($strChavePendencia, $arrPendenciasRetornadas)){
                            $arrObjPendenciasDTONovas[] = $strChavePendencia;
                            yield $objPendenciaDTO;

                        } elseif(in_array($strChavePendencia, $arrObjPendenciasDTONovas)) {
                            // Sleep adicionado para minimizar problema do serviço de pendência que retorna o mesmo código e status
                            // inúmeras vezes por causa de erro ainda não tratado
                            $mensagemErro = sprintf("Pendência de trâmite (IDT: %d / status: %s) enviado em duplicidade pelo serviço de monitoramento de pendências do PEN",
                                $numUltimoIdTramiteRecebido, $strUltimoStatusRecebido);
                            $this->gravarLogDebug($mensagemErro, 2);
                            throw new InfraException($mensagemErro);
                        } else {
                            $arrObjPendenciasDTONovas[] = $strChavePendencia;
                            $this->gravarLogDebug(sprintf("IDT %d desconsiderado por já ter sido retornado na consulta inicial", $numUltimoIdTramiteRecebido), 2);
                        }
                    }
                }
            } catch (Exception $e) {
                $bolEncontrouPendencia = false;
                throw new InfraException("Erro processando monitoramento de pendências de trâmite de processos", $e);
            }finally{
                curl_close($curl);
            }

        } while($bolEncontrouPendencia);
    }

    private function enviarPendenciaFilaProcessamento($objPendencia)
    {
        if(isset($objPendencia)) {

        $client = new GearmanClient();
        $client->addServer();

        $strWorkload = strval($objPendencia->getNumIdentificacaoTramite());

        switch ($objPendencia->getStrStatus()) {

            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO:
                $client->addTaskBackground('enviarComponenteDigital', $strWorkload, null);
                break;

            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_ENVIADOS_REMETENTE:
            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_METADADOS_RECEBIDO_DESTINATARIO:
            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO:
                $client->addTaskBackground('receberProcedimento', $strWorkload, null);
                $numCont++;
                break;

            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_ENVIADO_DESTINATARIO:
                $client->addTaskBackground('receberReciboTramite', $strWorkload, null);
                $numCont++;
                break;

            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE:
            break;

            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO:
            break;

            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO:
                $client->addTaskBackground("receberTramitesRecusados", $strWorkload, null);;
            break;

            default:
                throw new Exception('Situação do trâmite não pode ser identificada.');
                break;
            }

            $client->runTasks();
        }
    }

    private function gravarLogDebug($mensagem, $identacao=0)
    {
        $mensagem = sprintf("[%s] %s %s", date("d/m/Y H:i:s"), str_repeat("\t", $identacao), $mensagem);
        InfraDebug::getInstance()->gravar($mensagem);
    }
}

SessaoSEI::getInstance(false);
PendenciasTramiteRN::getInstance()->monitorarPendencias();
