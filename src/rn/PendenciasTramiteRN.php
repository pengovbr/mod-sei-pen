<?php

require_once DIR_SEI_WEB.'/SEI.php';

class PendenciasTramiteRN extends InfraRN
{
    const TIMEOUT_SERVICO_PENDENCIAS = 300; // 5 minutos
    const TEMPO_ESPERA_REINICIALIZACAO_MONITORAMENTO = 30; // 30 segundos
    const RECUPERAR_TODAS_PENDENCIAS = true;
    const TEMPO_MINIMO_REGISTRO_ERRO = 600; // 10 minutos
    const NUMERO_MAXIMO_LOG_ERROS = 500;
    const CODIGO_EXECUCAO_SUCESSO = 0;
    const CODIGO_EXECUCAO_ERRO = 1;
    const NUMERO_PROCESSOS_MONITORAMENTO = 10;
    const MAXIMO_PROCESSOS_MONITORAMENTO = 20;
    const COMANDO_EXECUCAO_WORKER = '%s %s %s %s %s %s %s %s > %s 2>&1 &';
    const LOCALIZACAO_SCRIPT_WORKER = DIR_SEI_WEB . "/../scripts/mod-pen/MonitoramentoTarefasPEN.php";
    const COMANDO_IDENTIFICACAO_WORKER = "ps -c ax | grep 'MonitoramentoTarefasPEN\.php' | grep -o '^[ ]*[0-9]*'";
    const COMANDO_IDENTIFICACAO_WORKER_ID = "ps -c ax | grep 'MonitoramentoTarefasPEN\.php.*--worker=%02d' | grep -o '^[ ]*[0-9]*'";

    private $objPenDebug = null;
    private $strEnderecoServico = null;
    private $strEnderecoServicoPendencias = null;
    private $strLocalizacaoCertificadoDigital = null;
    private $strSenhaCertificadoDigital = null;
    private $arrStrUltimasMensagensErro = array();

    public function __construct($parStrLogTag=null)
    {
        $this->carregarParametrosIntegracao();
        $this->objPenDebug = DebugPen::getInstance($parStrLogTag);
    }


    protected function inicializarObjInfraIBanco()
    {
        return BancoSEI::getInstance();
    }

    private function carregarParametrosIntegracao()
    {
        $objConfiguracaoModPEN = ConfiguracaoModPEN::getInstance();
        $this->strLocalizacaoCertificadoDigital = $objConfiguracaoModPEN->getValor("PEN", "LocalizacaoCertificado");
        $this->strSenhaCertificadoDigital = $objConfiguracaoModPEN->getValor("PEN", "SenhaCertificado");
        $this->strEnderecoServico = trim($objConfiguracaoModPEN->getValor("PEN", "WebService", false));

        // Parâmetro opcional. Não ativar o serviço de monitoramento de pendências, deixando o agendamento do SEI executar tal operação
        $this->strEnderecoServicoPendencias = trim($objConfiguracaoModPEN->getValor("PEN", "WebServicePendencias", false));

        // Parâmetro opcional. Não ativar o processamento por fila de tarefas, deixando o agendamento do SEI executar tal operação
        $arrObjGearman = $objConfiguracaoModPEN->getValor("PEN", "Gearman", false);
        $this->strGearmanServidor = trim(@$arrObjGearman["Servidor"] ?: null);
        $this->strGearmanPorta = trim(@$arrObjGearman["Porta"] ?: null);
    }

    /**
     * Busca pendências de recebimento de trâmites de processos e encaminha para processamento
     *
     * Os códigos de trâmites podem ser obtidos de duas formas:
     * 1 - Através da API Webservice SOAP, fazendo uma requisição direta para o serviço de consulta de pendências de trâmite
     * 2 - Através da API Rest de Stream, onde o módulo irá conectar ao Barramento e ficar na esculta por qualquer novo evento
     *
     * @param boolean $parBolMonitorarPendencias Indicador para ativar a esculta de eventos do Barramento
     * @return int  Código de resultado do processamento, sendo 0 para sucesso e 1 em caso de erros
     */
    public function encaminharPendencias($parBolMonitorarPendencias=false, $parBolSegundoPlano=false, $parBolDebug=false)
    {
        try{
            ini_set('max_execution_time','0');
            ini_set('memory_limit','-1');

            $this->validarCertificado();

            PENIntegracao::validarCompatibilidadeModulo();

            if(empty($this->strEnderecoServico) && empty($this->strEnderecoServicoPendencias)){
                throw new InfraException("Serviço de monitoramento de pendências não pode ser iniciado devido falta de configuração de endereços de WebServices");
            }

            $objPenParametroRN = new PenParametroRN();
            //SessaoSEI::getInstance(false)->simularLogin('SEI', null, null, $objPenParametroRN->getParametro('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO'));
            ModPenUtilsRN::simularLoginUnidadeRecebimento();

            $mensagemInicioMonitoramento = 'Iniciando serviço de monitoramento de pendências de trâmites de processos';
            $this->gravarLogDebug($mensagemInicioMonitoramento, 0);

            do{
                try {
                    PENIntegracao::validarCompatibilidadeBanco();
                    $this->gravarLogDebug('Recuperando lista de pendências do PEN', 1);
                    $arrObjPendenciasDTO = $this->obterPendenciasTramite($parBolMonitorarPendencias);

                    $objInfraException = new InfraException();
                    foreach ($arrObjPendenciasDTO as $objPendenciaDTO) {
                        $numIdTramite = $objPendenciaDTO->getNumIdentificacaoTramite();
                        $strStatusTramite = $objPendenciaDTO->getStrStatus();
                        $mensagemLog = ">>> Enviando pendência $numIdTramite (status $strStatusTramite) para fila de processamento";
                        $this->gravarLogDebug($mensagemLog, 3);

                        try {
                            $this->enviarPendenciaProcessamento($objPendenciaDTO, $parBolSegundoPlano);
                        } catch (\Exception $e) {
                            $this->gravarAmostraErroLogSEI($e);
                            $this->gravarLogDebug(InfraException::inspecionar($e));
                        }
                    }

                } catch(ModuloIncompativelException $e) {
                    // Sai loop de eventos para finalizar o script e subir uma nova versão atualizada
                    throw $e;
                } catch (Exception $e) {
                    //Apenas registra a falha no log do sistema e reinicia o ciclo de requisição
                    $this->gravarAmostraErroLogSEI($e);
                    $this->gravarLogDebug(InfraException::inspecionar($e));
                }

                if($parBolMonitorarPendencias){
                    $this->gravarLogDebug(sprintf("Reiniciando monitoramento de pendências em %s segundos", self::TEMPO_ESPERA_REINICIALIZACAO_MONITORAMENTO), 1);
                    sleep(self::TEMPO_ESPERA_REINICIALIZACAO_MONITORAMENTO);
                    $this->carregarParametrosIntegracao();
                }

            } while($parBolMonitorarPendencias);
        }
        catch(Exception $e) {
            $this->gravarLogDebug(InfraException::inspecionar($e));
            $this->gravarAmostraErroLogSEI($e);
            return self::CODIGO_EXECUCAO_ERRO;
        }

        // Caso não esteja sendo realizado o monitoramente de pendências, lança exceção diretamente na página para apresentação ao usuário
        if(!$parBolMonitorarPendencias){
            $this->salvarLogDebug($parBolDebug);
        }

        return self::CODIGO_EXECUCAO_SUCESSO;
    }


    /**
     * Valida a correta parametrização do certificado digital
     *
     * @return void
     */
    private function validarCertificado()
    {
        if (InfraString::isBolVazia($this->strLocalizacaoCertificadoDigital)) {
            throw new InfraException('Certificado digital de autenticação do serviço de integração do Processo Eletrônico Nacional(PEN) não informado.');
        }

        if (!@file_get_contents($this->strLocalizacaoCertificadoDigital)) {
            throw new InfraException("Certificado digital de autenticação do serviço de integração do Processo Eletrônico Nacional(PEN) não encontrado.");
        }

        if (InfraString::isBolVazia($this->strSenhaCertificadoDigital)) {
            throw new InfraException('Dados de autenticação do serviço de integração do Processo Eletrónico Nacional(PEN) não informados.');
        }
    }

    /**
     * Grava log de debug nas tabelas de log do SEI, caso o debug esteja habilitado
     *
     * @return void
     */
    private function salvarLogDebug($parBolDebugAtivado)
    {
        if($parBolDebugAtivado){
            $strTextoDebug = InfraDebug::getInstance()->getStrDebug();
            if(!InfraString::isBolVazia($strTextoDebug)){
                LogSEI::getInstance()->gravar(utf8_decode($strTextoDebug), LogSEI::$DEBUG);
            }
        }
    }

    private function configurarRequisicao()
    {
        $curl = curl_init($this->strEnderecoServicoPendencias);
        curl_setopt($curl, CURLOPT_URL, $this->strEnderecoServicoPendencias);
        curl_setopt($curl, CURLOPT_TIMEOUT, self::TIMEOUT_SERVICO_PENDENCIAS);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
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
    private function obterPendenciasTramite($parBolMonitorarPendencias)
    {
        //Obter todos os trâmites pendentes antes de iniciar o monitoramento
        $arrPendenciasRetornadas = array();
        $objProcessoEletronicoRN = new ProcessoEletronicoRN();
        $arrObjPendenciasDTO = $objProcessoEletronicoRN->listarPendencias(self::RECUPERAR_TODAS_PENDENCIAS) ?: array();
        shuffle($arrObjPendenciasDTO);

        $tableExists = BancoSEI::getInstance()->consultarSql("SELECT table_name FROM information_schema.tables WHERE table_schema = 'sei' AND table_name = 'md_pen_expedir_lote'");

        if(count($tableExists) >0){

            $objPenLoteProcedimentoDTO = new PenLoteProcedimentoDTO();
            $objPenLoteProcedimentoDTO->retNumIdLote();
            $objPenLoteProcedimentoDTO->retDblIdProcedimento();
            $objPenLoteProcedimentoDTO->retNumIdAndamento();
            $objPenLoteProcedimentoDTO->retNumIdAtividade();
            $objPenLoteProcedimentoDTO->retNumIdRepositorioDestino();
            $objPenLoteProcedimentoDTO->retStrRepositorioDestino();
            $objPenLoteProcedimentoDTO->retNumIdRepositorioOrigem();
            $objPenLoteProcedimentoDTO->retNumIdUnidadeDestino();
            $objPenLoteProcedimentoDTO->retStrUnidadeDestino();
            $objPenLoteProcedimentoDTO->retNumIdUnidadeOrigem();
            $objPenLoteProcedimentoDTO->retNumIdUsuario();
            $objPenLoteProcedimentoDTO->retStrProcedimentoFormatado();

            $objPenLoteProcedimentoDTO->setNumIdAndamento(ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_NAO_INICIADO);

            $objPenLoteProcedimentoRN = new PenLoteProcedimentoRN();
            $arrObjPenLoteProcedimentoDTO = $objPenLoteProcedimentoRN->obterPendenciasLote($objPenLoteProcedimentoDTO);

            if (isset($arrObjPendenciasDTO)){
                if (!is_array($arrObjPendenciasDTO)){
                    $arrObjPendenciasDTO = array();
                }
            }                 

            foreach ($arrObjPenLoteProcedimentoDTO as $objPenLoteProcedimentoDTO) {
                $objPendenciaDTO = new PendenciaDTO();
                $objPendenciaDTO->setNumIdentificacaoTramite($objPenLoteProcedimentoDTO->getDblIdProcedimento());
                $objPendenciaDTO->setStrStatus($objPenLoteProcedimentoDTO->getNumIdAndamento());
                $arrObjPendenciasDTO[] = $objPendenciaDTO;
            }

        }        

        $this->gravarLogDebug(count($arrObjPendenciasDTO) . " pendências de trâmites identificadas", 2);

        foreach ($arrObjPendenciasDTO as $objPendenciaDTO) {
            //Captura todas as pendências e status retornadas para impedir duplicidade
            $arrPendenciasRetornadas[] = sprintf("%d-%s", $objPendenciaDTO->getNumIdentificacaoTramite(), $objPendenciaDTO->getStrStatus());
            yield $objPendenciaDTO;
        }

        if($parBolMonitorarPendencias && $this->servicoMonitoramentoPendenciasAtivo()){
            //Obtém demais pendências do serviço de long polling
            $bolEncontrouPendencia = false;
            $numUltimoIdTramiteRecebido = 0;

            $arrObjPendenciasDTONovas = array();
            $this->gravarLogDebug("Iniciando monitoramento no serviço de pendências (long polling)", 2);

            do {
                $curl = $this->configurarRequisicao();
                try{
                    $arrObjPendenciasDTONovas = array_unique($arrObjPendenciasDTONovas);
                    curl_setopt($curl, CURLOPT_URL, $this->strEnderecoServicoPendencias . "?idTramiteDaPendenciaRecebida=" . $numUltimoIdTramiteRecebido);

                    // A seguinte requisição irá aguardar a notifição do PEN sobre uma nova pendência no trâmite
                    // ou até o lançamento da exceção de timeout definido pela constante TIMEOUT_SERVICO_PENDENCIAS
                    $this->gravarLogDebug(sprintf("Executando requisição de pendência com IDT %d como offset", $numUltimoIdTramiteRecebido), 2);
                    $strResultadoJSON = curl_exec($curl);
                    if(curl_errno($curl)) {
                        if (curl_errno($curl) != 28){
                            throw new InfraException("Erro na requisição do serviço de monitoramento de pendências. Curl: " . curl_errno($curl));
                        }

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
    }


    /**
     * Verifica se gearman se encontra configurado e ativo para receber tarefas na fila
     *
     * @return bool
     */
    private function servicoGearmanAtivo()
    {
        $bolAtivo = false;
        $strMensagemErro = "Não foi possível conectar ao servidor Gearman (%s, %s). Erro: %s";
        try {
            if(!empty($this->strGearmanServidor)) {
                if(!class_exists("GearmanClient")){
                    throw new InfraException("Não foi possível localizar as bibliotecas do PHP para conexão ao GEARMAN. " .
                        "Verifique os procedimentos de instalação do mod-sei-pen para maiores detalhes");
                }

                try{
                    $objGearmanClient = new GearmanClient();
                    $objGearmanClient->addServer($this->strGearmanServidor, $this->strGearmanPorta);
                    $bolAtivo = $objGearmanClient->ping("health");
                } catch (\Exception $e) {
                    $strMensagem = "Não foi possível conectar ao servidor Gearman ($this->strGearmanServidor, $this->strGearmanPorta). Erro:" . $objGearmanClient->error();
                    $strMensagem = sprintf($strMensagemErro, $this->strGearmanServidor, $this->strGearmanPorta, $objGearmanClient->error());
                    LogSEI::getInstance()->gravar($strMensagem, LogSEI::$AVISO);
                }
            }
        } catch (\InfraException $e) {
            $strMensagem = sprintf($strMensagemErro, $this->strGearmanServidor, $this->strGearmanPorta, InfraException::inspecionar($e));
            LogSEI::getInstance()->gravar($strMensagem, LogSEI::$AVISO);
        }

        return $bolAtivo;
    }


    /**
     * Verifica se o serviço de monitoramento de pendências foi configurado e encontra-se ativo
     *
     * @return bool
     */
    private function servicoMonitoramentoPendenciasAtivo()
    {
        $bolMonitoramentoAtivo = !empty($this->strEnderecoServicoPendencias);
        return $bolMonitoramentoAtivo;
    }


    /**
     * Envia a pendência de trâmite para a fila de processamento do tarefas de acordo com a estratégia definida
     *
     * @param stdClass $objPendencia
     * @return void
     */
    private function enviarPendenciaProcessamento($objPendencia, $parBolSegundoPlano)
    {
        if($parBolSegundoPlano && $this->servicoGearmanAtivo()){
            $this->enviarPendenciaFilaProcessamento($objPendencia);
        } else {
            $this->enviarPendenciaProcessamentoDireto($objPendencia);
        }
    }


    /**
     * Processa pendência de recebimento diretamente através da chamada das funções de processamento
     *
     * @param stclass $objPendencia
     * @return void
     */
    private function enviarPendenciaProcessamentoDireto($objPendencia)
    {
        if(isset($objPendencia)) {
            $numIDT = strval($objPendencia->getNumIdentificacaoTramite());
            $numStatus = strval($objPendencia->getStrStatus());
            $objProcessarPendenciaRN = new ProcessarPendenciasRN();

            switch ($numStatus) {
                case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_NAO_INICIADO:
                    $objProcessarPendenciaRN->expedirLote($numIDT);
                    break;

                case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO:
                    $objProcessarPendenciaRN->enviarComponenteDigital($numIDT);
                    break;

                case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_ENVIADOS_REMETENTE:
                case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_METADADOS_RECEBIDO_DESTINATARIO:
                case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO:
                    $objProcessarPendenciaRN->receberProcedimento($numIDT);
                    break;

                case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_ENVIADO_DESTINATARIO:
                    $objProcessarPendenciaRN->receberReciboTramite($numIDT);
                    break;

                case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO:
                    $objProcessarPendenciaRN->receberTramitesRecusados($numIDT);
                    break;

                default:
                    $strStatus = $objPendencia->getStrStatus();
                    $this->gravarLogDebug("Situação do trâmite ($strStatus) não pode ser tratada.");
                    break;
            }
        }
    }

    /**
     * Envia pendência de recebimento para fila de tarefas do Gearman para processamento futuro
     *
     * @param stdclass $objPendencia
     * @return void
     */
    private function enviarPendenciaFilaProcessamento($objPendencia)
    {
        if(isset($objPendencia)) {
            $client = new GearmanClient();
            $client->addServer($this->strGearmanServidor, $this->strGearmanPorta);

            $numIDT = strval($objPendencia->getNumIdentificacaoTramite());
            $numStatus = strval($objPendencia->getStrStatus());

            switch ($numStatus) {

                case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_NAO_INICIADO:
                    $client->addTaskBackground('expedirLote', $numIDT, null, $numIDT);
                    break;

                case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO:
                    $client->addTaskBackground('enviarComponenteDigital', $numIDT, null, $numIDT);
                    break;

                case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_ENVIADOS_REMETENTE:
                case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_METADADOS_RECEBIDO_DESTINATARIO:
                case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO:
                    $client->addTaskBackground('receberProcedimento', $numIDT, null, $numIDT);
                    break;

                case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_ENVIADO_DESTINATARIO:
                    $client->addTaskBackground('receberReciboTramite', $numIDT, null, $numIDT);
                    break;

                case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO:
                    $client->addTaskBackground("receberTramitesRecusados", $numIDT, null, $numIDT);
                break;

                default:

                    $this->gravarLogDebug("Situação do trâmite ($numStatus ) não pode ser tratada.");
                    break;
            }

            $client->runTasks();
        }
    }

    private function gravarLogDebug($parStrMensagem, $parNumIdentacao=0, $parBolLogTempoProcessamento=false)
    {
        $this->objPenDebug->gravar($parStrMensagem, $parNumIdentacao, $parBolLogTempoProcessamento);
    }

    /**
     * Registra log de erro no SEI caso o mesmo já não tenha sido registrado anteriormente em período determinado de tempo
     *
     * @param string $parObjException Exceção lançada pelo sistema
     * @param int $numTempoRegistroErro Tempo mínimo para novo registro de erro nos logs do sistema
     * @return void
     */
    private function gravarAmostraErroLogSEI($parObjException, $strTipoLog="E")
    {
        if(!is_null($parObjException)){
            $strMensagemErro = InfraException::inspecionar($parObjException);
            $strHashMensagem = md5($strMensagemErro);
            if(array_key_exists($strHashMensagem, $this->arrStrUltimasMensagensErro)){
                $dthUltimoRegistro = $this->arrStrUltimasMensagensErro[$strHashMensagem];
                $dthDataMinimaParaRegistro = new DateTime(sprintf("-%d seconds", self::TEMPO_MINIMO_REGISTRO_ERRO));
                if($dthUltimoRegistro > $dthDataMinimaParaRegistro){
                    return false;
                }
            }

            // Remove registros de logs mais antigos para não sobrecarregar
            if(count($this->arrStrUltimasMensagensErro) > self::NUMERO_MAXIMO_LOG_ERROS){
                array_shift($this->arrStrUltimasMensagensErro);
            }

            $this->arrStrUltimasMensagensErro[$strHashMensagem] = new DateTime("now");
            LogSEI::getInstance()->gravar($strMensagemErro);
        }
    }

    /**
     * Inicia o recebimento de tarefas de Barramento do PEN em novo processo separado,
     * evitando o bloqueio da thread da aplicação
     *
     * @param int $parNumQtdeWorkers Quantidade de processos paralelos que serão iniciados
     * @param boolean $parBolMonitorar Indicação se o novo processo ficará monitorando o Barramento do PEN
     * @param boolean $parBolSegundoPlano Indicação se será utilizado o processamento das tarefas em segundo plano com o Gearman
     * @return bool Monitoramento iniciado com sucesso
     */
    public static function inicializarMonitoramentoPendencias($parNumQtdeWorkers=null, $parBolMonitorar=false, $parBolSegundoPlano=false, $parBolDebugAtivo=false, $parStrUsuarioProcesso=null)
    {
        $bolInicializado = false;
        $parNumQtdeWorkers = min($parNumQtdeWorkers ?: self::NUMERO_PROCESSOS_MONITORAMENTO, self::MAXIMO_PROCESSOS_MONITORAMENTO);

        try {
            for ($worker=0; $worker < $parNumQtdeWorkers; $worker++) {
                $strComandoIdentificacaoWorker = sprintf(self::COMANDO_IDENTIFICACAO_WORKER_ID, $worker);
                exec($strComandoIdentificacaoWorker, $strSaida, $numCodigoResposta);

                if ($numCodigoResposta != 0) {
                    $strLocalizacaoScript = realpath(self::LOCALIZACAO_SCRIPT_WORKER);
                    $strPhpExec = empty(PHP_BINARY) ? "php" : PHP_BINARY;
                    $strPhpIni = php_ini_loaded_file();
                    $strPhpIni = $strPhpIni ? "-c $strPhpIni" : "";
                    $strWsdlCacheDir = ini_get('soap.wsdl_cache_dir');
                    $strParametroWsdlCache = "--wsdl-cache='$strWsdlCacheDir'";
                    $strIdWorker = sprintf("--worker=%02d", $worker);
                    $strParametroMonitorar = $parBolMonitorar ? "--monitorar" : '';
                    $strParametroSegundoPlano = $parBolSegundoPlano ? "--segundo-plano" : "";
                    $strParametroDebugAtivo = $parBolDebugAtivo ? "--debug" : "";

                    $strComandoMonitoramentoTarefas = sprintf(
                        self::COMANDO_EXECUCAO_WORKER,
                        $strPhpExec,               // Binário do PHP utilizado no contexto de execução do script atual (ex: /usr/bin/php)
                        $strPhpIni,                // Arquivo de configucação o PHP utilizado no contexto de execução do script atual (ex: /etc/php.ini)
                        $strLocalizacaoScript,     // Path absoluto do script de monitoramento de tarefas do Barramento
                        $strIdWorker,              // Identificador sequencial do processo paralelo a ser iniciado
                        $strParametroMonitorar,    // Parâmetro para executar processo em modo de monitoramente ativo
                        $strParametroSegundoPlano, // Parâmetro para executar processo em segundo plano com Gearman
                        $strParametroDebugAtivo,   // Parâmetro para executar processo em modo de debug
                        $strParametroWsdlCache,    // Diretório de cache de wsdl utilizado no contexto de execução do script atual (ex: /tmp/)
                        "/dev/null" // Localização de log adicinal para registros de falhas não salvas pelo SEI no BD
                    );

                    shell_exec($strComandoMonitoramentoTarefas);

                    // Verifica se monitoramento de tarefas foi iniciado corretamente, finalizando o laço para não
                    // permitir que mais de um monitoramento esteja iniciado
                    exec($strComandoIdentificacaoWorker, $strSaida, $numCodigoResposta);

                    if ($numCodigoResposta == 0) {
                        break;
                    }
                }
            }

            // Confirma se existe algum worker ativo
            exec(self::COMANDO_IDENTIFICACAO_WORKER, $strSaida, $numCodigoRespostaAtivacao);
            $bolInicializado = $numCodigoRespostaAtivacao == 0;

        } catch (\Exception $e) {
            $strMensagem = "Falha: Não foi possível iniciar o monitoramento de tarefas Barramento PEN";
            $objInfraException = new InfraException($strMensagem, $e);
            throw $objInfraException;
        }

        return $bolInicializado;
    }
}
