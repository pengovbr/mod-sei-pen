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

        // Par�metro opcional. N�o ativar o servi�o de monitoramento de pend�ncias, deixando o agendamento do SEI executar tal opera��o
        $this->strEnderecoServicoPendencias = trim($objConfiguracaoModPEN->getValor("PEN", "WebServicePendencias", false));

        // Par�metro opcional. N�o ativar o processamento por fila de tarefas, deixando o agendamento do SEI executar tal opera��o
        $arrObjGearman = $objConfiguracaoModPEN->getValor("PEN", "Gearman", false);
        $this->strGearmanServidor = trim(@$arrObjGearman["Servidor"] ?: null);
        $this->strGearmanPorta = trim(@$arrObjGearman["Porta"] ?: null);
    }

    /**
     * Busca pend�ncias de recebimento de tr�mites de processos e encaminha para processamento
     *
     * Os c�digos de tr�mites podem ser obtidos de duas formas:
     * 1 - Atrav�s da API Webservice SOAP, fazendo uma requisi��o direta para o servi�o de consulta de pend�ncias de tr�mite
     * 2 - Atrav�s da API Rest de Stream, onde o m�dulo ir� conectar ao Barramento e ficar na esculta por qualquer novo evento
     *
     * @param boolean $parBolMonitorarPendencias Indicador para ativar a esculta de eventos do Barramento
     * @return int  C�digo de resultado do processamento, sendo 0 para sucesso e 1 em caso de erros
     */
    public function encaminharPendencias($parBolMonitorarPendencias=false, $parBolSegundoPlano=false, $parBolDebug=false)
    {
        try{
            ini_set('max_execution_time','0');
            ini_set('memory_limit','-1');

            $this->validarCertificado();

            PENIntegracao::validarCompatibilidadeModulo();

            if(empty($this->strEnderecoServico) && empty($this->strEnderecoServicoPendencias)){
                throw new InfraException("Servi�o de monitoramento de pend�ncias n�o pode ser iniciado devido falta de configura��o de endere�os de WebServices");
            }

            $objPenParametroRN = new PenParametroRN();
            //SessaoSEI::getInstance(false)->simularLogin('SEI', null, null, $objPenParametroRN->getParametro('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO'));
            ModPenUtilsRN::simularLoginUnidadeRecebimento();

            $mensagemInicioMonitoramento = 'Iniciando servi�o de monitoramento de pend�ncias de tr�mites de processos';
            $this->gravarLogDebug($mensagemInicioMonitoramento, 0);

            do{
                try {
                    PENIntegracao::validarCompatibilidadeBanco();
                    $this->gravarLogDebug('Recuperando lista de pend�ncias do PEN', 1);
                    $arrObjPendenciasDTO = $this->obterPendenciasTramite($parBolMonitorarPendencias);

                    $objInfraException = new InfraException();
                    foreach ($arrObjPendenciasDTO as $objPendenciaDTO) {
                        $numIdTramite = $objPendenciaDTO->getNumIdentificacaoTramite();
                        $strStatusTramite = $objPendenciaDTO->getStrStatus();
                        $mensagemLog = ">>> Enviando pend�ncia $numIdTramite (status $strStatusTramite) para fila de processamento";
                        $this->gravarLogDebug($mensagemLog, 3);

                        try {
                            $this->enviarPendenciaProcessamento($objPendenciaDTO, $parBolSegundoPlano);
                        } catch (\Exception $e) {
                            $this->gravarAmostraErroLogSEI($e);
                            $this->gravarLogDebug(InfraException::inspecionar($e));
                        }
                    }

                } catch(ModuloIncompativelException $e) {
                    // Sai loop de eventos para finalizar o script e subir uma nova vers�o atualizada
                    throw $e;
                } catch (Exception $e) {
                    //Apenas registra a falha no log do sistema e reinicia o ciclo de requisi��o
                    $this->gravarAmostraErroLogSEI($e);
                    $this->gravarLogDebug(InfraException::inspecionar($e));
                }

                if($parBolMonitorarPendencias){
                    $this->gravarLogDebug(sprintf("Reiniciando monitoramento de pend�ncias em %s segundos", self::TEMPO_ESPERA_REINICIALIZACAO_MONITORAMENTO), 1);
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

        // Caso n�o esteja sendo realizado o monitoramente de pend�ncias, lan�a exce��o diretamente na p�gina para apresenta��o ao usu�rio
        if(!$parBolMonitorarPendencias){
            $this->salvarLogDebug($parBolDebug);
        }

        return self::CODIGO_EXECUCAO_SUCESSO;
    }


    /**
     * Valida a correta parametriza��o do certificado digital
     *
     * @return void
     */
    private function validarCertificado()
    {
        if (InfraString::isBolVazia($this->strLocalizacaoCertificadoDigital)) {
            throw new InfraException('Certificado digital de autentica��o do servi�o de integra��o do Processo Eletr�nico Nacional(PEN) n�o informado.');
        }

        if (!@file_get_contents($this->strLocalizacaoCertificadoDigital)) {
            throw new InfraException("Certificado digital de autentica��o do servi�o de integra��o do Processo Eletr�nico Nacional(PEN) n�o encontrado.");
        }

        if (InfraString::isBolVazia($this->strSenhaCertificadoDigital)) {
            throw new InfraException('Dados de autentica��o do servi�o de integra��o do Processo Eletr�nico Nacional(PEN) n�o informados.');
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
     * Fun��o para recuperar as pend�ncias de tr�mite que j� foram recebidas pelo servi�o de long pulling e n�o foram processadas com sucesso
     * @param  num $parNumIdTramiteRecebido
     * @return [type]                          [description]
     */
    private function obterPendenciasTramite($parBolMonitorarPendencias)
    {
        //Obter todos os tr�mites pendentes antes de iniciar o monitoramento
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

        $this->gravarLogDebug(count($arrObjPendenciasDTO) . " pend�ncias de tr�mites identificadas", 2);

        foreach ($arrObjPendenciasDTO as $objPendenciaDTO) {
            //Captura todas as pend�ncias e status retornadas para impedir duplicidade
            $arrPendenciasRetornadas[] = sprintf("%d-%s", $objPendenciaDTO->getNumIdentificacaoTramite(), $objPendenciaDTO->getStrStatus());
            yield $objPendenciaDTO;
        }

        if($parBolMonitorarPendencias && $this->servicoMonitoramentoPendenciasAtivo()){
            //Obt�m demais pend�ncias do servi�o de long polling
            $bolEncontrouPendencia = false;
            $numUltimoIdTramiteRecebido = 0;

            $arrObjPendenciasDTONovas = array();
            $this->gravarLogDebug("Iniciando monitoramento no servi�o de pend�ncias (long polling)", 2);

            do {
                $curl = $this->configurarRequisicao();
                try{
                    $arrObjPendenciasDTONovas = array_unique($arrObjPendenciasDTONovas);
                    curl_setopt($curl, CURLOPT_URL, $this->strEnderecoServicoPendencias . "?idTramiteDaPendenciaRecebida=" . $numUltimoIdTramiteRecebido);

                    // A seguinte requisi��o ir� aguardar a notifi��o do PEN sobre uma nova pend�ncia no tr�mite
                    // ou at� o lan�amento da exce��o de timeout definido pela constante TIMEOUT_SERVICO_PENDENCIAS
                    $this->gravarLogDebug(sprintf("Executando requisi��o de pend�ncia com IDT %d como offset", $numUltimoIdTramiteRecebido), 2);
                    $strResultadoJSON = curl_exec($curl);
                    if(curl_errno($curl)) {
                        if (curl_errno($curl) != 28){
                            throw new InfraException("Erro na requisi��o do servi�o de monitoramento de pend�ncias. Curl: " . curl_errno($curl));
                        }

                        $bolEncontrouPendencia = false;
                        $this->gravarLogDebug(sprintf("Timeout de monitoramento de %d segundos do servi�o de pend�ncias alcan�ado", self::TIMEOUT_SERVICO_PENDENCIAS), 2);
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

                            //N�o processo novamente as pend�ncias j� capturadas na consulta anterior ($objProcessoEletronicoRN->listarPendencias)
                            //Considera somente as novas identificadas pelo servi�o de monitoramento
                            if(!in_array($strChavePendencia, $arrPendenciasRetornadas)){
                                $arrObjPendenciasDTONovas[] = $strChavePendencia;
                                yield $objPendenciaDTO;

                            } elseif(in_array($strChavePendencia, $arrObjPendenciasDTONovas)) {
                                // Sleep adicionado para minimizar problema do servi�o de pend�ncia que retorna o mesmo c�digo e status
                                // in�meras vezes por causa de erro ainda n�o tratado
                                $mensagemErro = sprintf("Pend�ncia de tr�mite (IDT: %d / status: %s) enviado em duplicidade pelo servi�o de monitoramento de pend�ncias do PEN",
                                    $numUltimoIdTramiteRecebido, $strUltimoStatusRecebido);
                                $this->gravarLogDebug($mensagemErro, 2);
                                throw new InfraException($mensagemErro);
                            } else {
                                $arrObjPendenciasDTONovas[] = $strChavePendencia;
                                $this->gravarLogDebug(sprintf("IDT %d desconsiderado por j� ter sido retornado na consulta inicial", $numUltimoIdTramiteRecebido), 2);
                            }
                        }
                    }
                } catch (Exception $e) {
                    $bolEncontrouPendencia = false;
                    throw new InfraException("Erro processando monitoramento de pend�ncias de tr�mite de processos", $e);
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
        $strMensagemErro = "N�o foi poss�vel conectar ao servidor Gearman (%s, %s). Erro: %s";
        try {
            if(!empty($this->strGearmanServidor)) {
                if(!class_exists("GearmanClient")){
                    throw new InfraException("N�o foi poss�vel localizar as bibliotecas do PHP para conex�o ao GEARMAN. " .
                        "Verifique os procedimentos de instala��o do mod-sei-pen para maiores detalhes");
                }

                try{
                    $objGearmanClient = new GearmanClient();
                    $objGearmanClient->addServer($this->strGearmanServidor, $this->strGearmanPorta);
                    $bolAtivo = $objGearmanClient->ping("health");
                } catch (\Exception $e) {
                    $strMensagem = "N�o foi poss�vel conectar ao servidor Gearman ($this->strGearmanServidor, $this->strGearmanPorta). Erro:" . $objGearmanClient->error();
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
     * Verifica se o servi�o de monitoramento de pend�ncias foi configurado e encontra-se ativo
     *
     * @return bool
     */
    private function servicoMonitoramentoPendenciasAtivo()
    {
        $bolMonitoramentoAtivo = !empty($this->strEnderecoServicoPendencias);
        return $bolMonitoramentoAtivo;
    }


    /**
     * Envia a pend�ncia de tr�mite para a fila de processamento do tarefas de acordo com a estrat�gia definida
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
     * Processa pend�ncia de recebimento diretamente atrav�s da chamada das fun��es de processamento
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
                    $this->gravarLogDebug("Situa��o do tr�mite ($strStatus) n�o pode ser tratada.");
                    break;
            }
        }
    }

    /**
     * Envia pend�ncia de recebimento para fila de tarefas do Gearman para processamento futuro
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

                    $this->gravarLogDebug("Situa��o do tr�mite ($numStatus ) n�o pode ser tratada.");
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
     * Registra log de erro no SEI caso o mesmo j� n�o tenha sido registrado anteriormente em per�odo determinado de tempo
     *
     * @param string $parObjException Exce��o lan�ada pelo sistema
     * @param int $numTempoRegistroErro Tempo m�nimo para novo registro de erro nos logs do sistema
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

            // Remove registros de logs mais antigos para n�o sobrecarregar
            if(count($this->arrStrUltimasMensagensErro) > self::NUMERO_MAXIMO_LOG_ERROS){
                array_shift($this->arrStrUltimasMensagensErro);
            }

            $this->arrStrUltimasMensagensErro[$strHashMensagem] = new DateTime("now");
            LogSEI::getInstance()->gravar($strMensagemErro);
        }
    }

    /**
     * Inicia o recebimento de tarefas de Barramento do PEN em novo processo separado,
     * evitando o bloqueio da thread da aplica��o
     *
     * @param int $parNumQtdeWorkers Quantidade de processos paralelos que ser�o iniciados
     * @param boolean $parBolMonitorar Indica��o se o novo processo ficar� monitorando o Barramento do PEN
     * @param boolean $parBolSegundoPlano Indica��o se ser� utilizado o processamento das tarefas em segundo plano com o Gearman
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
                        $strPhpExec,               // Bin�rio do PHP utilizado no contexto de execu��o do script atual (ex: /usr/bin/php)
                        $strPhpIni,                // Arquivo de configuca��o o PHP utilizado no contexto de execu��o do script atual (ex: /etc/php.ini)
                        $strLocalizacaoScript,     // Path absoluto do script de monitoramento de tarefas do Barramento
                        $strIdWorker,              // Identificador sequencial do processo paralelo a ser iniciado
                        $strParametroMonitorar,    // Par�metro para executar processo em modo de monitoramente ativo
                        $strParametroSegundoPlano, // Par�metro para executar processo em segundo plano com Gearman
                        $strParametroDebugAtivo,   // Par�metro para executar processo em modo de debug
                        $strParametroWsdlCache,    // Diret�rio de cache de wsdl utilizado no contexto de execu��o do script atual (ex: /tmp/)
                        "/dev/null" // Localiza��o de log adicinal para registros de falhas n�o salvas pelo SEI no BD
                    );

                    shell_exec($strComandoMonitoramentoTarefas);

                    // Verifica se monitoramento de tarefas foi iniciado corretamente, finalizando o la�o para n�o
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
            $strMensagem = "Falha: N�o foi poss�vel iniciar o monitoramento de tarefas Barramento PEN";
            $objInfraException = new InfraException($strMensagem, $e);
            throw $objInfraException;
        }

        return $bolInicializado;
    }
}
