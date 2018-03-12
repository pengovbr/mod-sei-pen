<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

error_reporting(E_ALL);

//TODO: Modificar nome da classe e mtodo para outro mais apropriado
class PendenciasTramiteRN extends InfraRN {

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
            InfraDebug::getInstance()->setBolEcho(false);
            InfraDebug::getInstance()->limpar();

            $objPenParametroRN = new PenParametroRN();
            SessaoSEI::getInstance(false)->simularLogin('SEI', null, null, $objPenParametroRN->getParametro('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO'));

            $numSeg = InfraUtil::verificarTempoProcessamento();
            InfraDebug::getInstance()->gravar('MONITORANDO OS TRÂMITES PENDENTES ENVIADOS PARA O RGO (PEN)');
            echo "[".date("d/m/Y H:i:s")."] Iniciando serviço de monitoramento de pendências de trâmites de processos...\n";

            try{
                $numIdTramiteRecebido = 0;
                $strStatusTramiteRecebido = '';
                $numQuantidadeErroTramite = 0;
                $arrQuantidadeErrosTramite = array();

                //TODO: Tratar quantidade de erros o sistema consecutivos para um tramite de processo
                //Alcanado est quantidade, uma pendncia posterior dever ser obtida do barramento
                while (true) {
                    $objPendenciaDTO = $this->obterPendenciasTramite($numIdTramiteRecebido);          
                    if(isset($objPendenciaDTO)) {
                        if($numIdTramiteRecebido != $objPendenciaDTO->getNumIdentificacaoTramite() || 
                            $strStatusTramiteRecebido != $objPendenciaDTO->getStrStatus()) {
                            $numIdTramiteRecebido = $objPendenciaDTO->getNumIdentificacaoTramite();
                            $strStatusTramiteRecebido = $objPendenciaDTO->getStrStatus();
                            $this->enviarPendenciaFilaProcessamento($objPendenciaDTO);
                        }
                    }
                sleep(5);
                }
            }
            //TODO: Urgente: Tratar erro especfico de timeout e refazer a requisio      
            catch(Exception $e) {
                $strAssunto = 'Erro monitorando pendências.';
                $strErro = InfraException::inspecionar($e);      
                LogSEI::getInstance()->gravar($strAssunto."\n\n".$strErro); 
            }

            $numSeg = InfraUtil::verificarTempoProcessamento($numSeg);
            InfraDebug::getInstance()->gravar('TEMPO TOTAL DE EXECUCAO: '.$numSeg.' s');
            InfraDebug::getInstance()->gravar('FIM');
            LogSEI::getInstance()->gravar(InfraDebug::getInstance()->getStrDebug());

        } 
        catch(Exception $e) {
            InfraDebug::getInstance()->setBolLigado(false);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(false);
            throw new InfraException('Erro processando pendências de integração com o PEN - Processo Eletrônico Nacional.',$e);
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
        curl_setopt($curl, CURLOPT_TIMEOUT, 60); //timeout in seconds
        return $curl;
    }

    private function obterPendenciasTramite($parNumIdTramiteRecebido) 
    {
        $resultado = null;
        $curl = $this->configurarRequisicao();
      
        try{
            if(isset($parNumIdTramiteRecebido)) {
                curl_setopt($curl, CURLOPT_URL, $this->strEnderecoServicoPendencias . "?idTramiteDaPendenciaRecebida=" . $parNumIdTramiteRecebido);     
            }

            //A seguinte requisio ir aguardar a notificao do PEN sobre uma nova pendncia
            //ou at o lanamento da exceo de timeout definido pela infraestrutura da soluo
            //Ambos os comportamentos so esperados para a requisio abaixo.      
            $strResultadoJSON = curl_exec($curl);

            if(curl_errno($curl)) {
                if (curl_errno($curl) != 28)
                    throw new InfraException("Erro na requisição do serviço de monitoramento de pendências. Curl: " . curl_errno($curl));        
            }

            if(!InfraString::isBolVazia($strResultadoJSON)) {
                $strResultadoJSON = json_decode($strResultadoJSON);

                if(isset($strResultadoJSON) && $strResultadoJSON->encontrou) {
                    $objPendenciaDTO = new PendenciaDTO();
                    $objPendenciaDTO->setNumIdentificacaoTramite($strResultadoJSON->IDT);
                    $objPendenciaDTO->setStrStatus($strResultadoJSON->status);            
                    $resultado = $objPendenciaDTO;        
                }
            }
        }
        catch(Exception $e){
            curl_close($curl);
            throw $e;      
        }    

        curl_close($curl);
        return $resultado;
    }

    private function enviarPendenciaFilaProcessamento($objPendencia)
    {
        if(isset($objPendencia)) {

        $client = new GearmanClient();
        $client->addServer('localhost', 4730);
        //$client->setCreatedCallback("create_change");
        //$client->setDataCallback("data_change");
        //$client->setStatusCallback("status_change");
        //$client->setCompleteCallback("complete_change");
        //$client->setFailCallback("fail_change");

        $strWorkload = strval($objPendencia->getNumIdentificacaoTramite());

        switch ($objPendencia->getStrStatus()) {

            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO:
                $client->addTaskBackground('enviarComponenteDigital', $strWorkload, null);
                break;

            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_ENVIADOS_REMETENTE:
            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_METADADOS_RECEBIDO_DESTINATARIO:
            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO:
                $objPenParametroRN = new PenParametroRN();
                $numTentativas = $objPenParametroRN->getParametro(PenTramiteProcessadoRN::PARAM_NUMERO_TENTATIVAS, false);
                $numCont = 0;
                // Executa sempre + 1 alm do configurado no parâmetro para executar a recusa
                while($numCont <= $numTentativas) {
                    $client->addTaskBackground('receberProcedimento', $strWorkload, null);
                    $numCont++;
                }
                break;

            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_ENVIADO_DESTINATARIO:
                $objPenParametroRN = new PenParametroRN();
                $numTentativas = $objPenParametroRN->getParametro(PenTramiteProcessadoRN::PARAM_NUMERO_TENTATIVAS, false);
                $numCont = 0;

                while($numCont < $numTentativas) {                    
                    $client->addTaskBackground('receberReciboTramite', $strWorkload, null);
                    $numCont++;
                }
                break;

            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE:
            break;

            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO:
            break;

            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO:
                $client->addTaskBackground("receberTramitesRecusados", $strWorkload, null);;
            break;

            default:
                //TODO: Alterar lgica para no deixar de processar demais pendncias retornadas pelo PEN
                throw new Exception('Situação do trâmite não pode ser identificada.');
                break;
            }

            $client->runTasks();
        }
    }
}

SessaoSEI::getInstance(false);
PendenciasTramiteRN::getInstance()->monitorarPendencias();
