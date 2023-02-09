<?php

require_once DIR_SEI_WEB.'/SEI.php';

class ProcessarPendenciasRN extends InfraRN
{
    private $objGearmanWorker = null;
    private $objPenDebug = null;
    private $strGearmanServidor = null;
    private $strGearmanPorta = null;

    const TIMEOUT_PROCESSAMENTO_JOB = 5400; // valores em segundos, 5400 = 90 minutos
    const TIMEOUT_PROCESSAMENTO_EVENTOS = 300000; // valores em milisegundos, 300000 = 5 minutos

    const NUMERO_WORKERS_GEARMAN = 4;
    const MAXIMO_WORKERS_GEARMAN = 10;
    const COMANDO_IDENTIFICACAO_WORKER = "ps -c ax | grep 'ProcessamentoTarefasPEN\.php' | grep -o '^[ ]*[0-9]*'";
    const COMANDO_IDENTIFICACAO_WORKER_ID = "ps -c ax | grep 'ProcessamentoTarefasPEN\.php.*--worker=%02d' | grep -o '^[ ]*[0-9]*'";
    const COMANDO_EXECUCAO_WORKER = '%s %s %s --worker=%02d > %s 2>&1 &';
    const LOCALIZACAO_SCRIPT_WORKER = DIR_SEI_WEB . "/../scripts/mod-pen/ProcessamentoTarefasPEN.php";

  protected function inicializarObjInfraIBanco()
    {
      return BancoSEI::getInstance();
  }

  public function __construct($parStrLogTag = null)
    {
      $this->carregarParametrosIntegracao();
      $this->objPenDebug = DebugPen::getInstance($parStrLogTag);
      $this->objPenParametroRN = new PenParametroRN();
  }

  private function carregarParametrosIntegracao()
    {
      $objConfiguracaoModPEN = ConfiguracaoModPEN::getInstance();
      $this->strLocalizacaoCertificadoDigital = $objConfiguracaoModPEN->getValor("PEN", "LocalizacaoCertificado");
      $this->strSenhaCertificadoDigital = $objConfiguracaoModPEN->getValor("PEN", "SenhaCertificado");

      // Par�metro opcional. N�o ativar o processamento por fila de tarefas, deixando o agendamento do SEI executar tal opera��o
      $arrObjGearman = $objConfiguracaoModPEN->getValor("PEN", "Gearman", false);
      $this->strGearmanServidor = trim(@$arrObjGearman["Servidor"] ?: null);
      $this->strGearmanPorta = trim(@$arrObjGearman["Porta"] ?: null);

    if (!@file_get_contents($this->strLocalizacaoCertificadoDigital)) {
        throw new InfraException("Certificado digital de autentica��o do servi�o de integra��o do Processo Eletr�nico Nacional(PEN) n�o encontrado.");
    }

    if (InfraString::isBolVazia($this->strSenhaCertificadoDigital)) {
        throw new InfraException('Dados de autentica��o do servi�o de integra��o do Processo Eletr�nico Nacional(PEN) n�o informados.');
    }
  }

    /**
     * Inicializa GearmanWorker caso bibliotecas e servi�o sejam localizados
     *
     * @return void
     */
  private function inicializarGearman()
    {
    if(!class_exists("GearmanWorker")){
        throw new InfraException("N�o foi poss�vel localizar as bibliotecas do PHP para conex�o ao GEARMAN./n" .
                                 "Verifique os procedimentos de instala��o do mod-sei-pen para maiores detalhes");
    }

      $this->objGearmanWorker = new GearmanWorker();
      $this->objGearmanWorker->setTimeout(self::TIMEOUT_PROCESSAMENTO_EVENTOS);
      $this->objGearmanWorker->addServer($this->strGearmanServidor, $this->strGearmanPorta);
      $this->configurarCallbacks();
  }

  public function processarPendencias()
    {
    try{
        $this->inicializarGearman();

        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '-1');

        PENIntegracao::verificarCompatibilidadeConfiguracoes();
        ModPenUtilsRN::simularLoginUnidadeRecebimento();

        $numProcID = getmygid();
        $mensagemInicioProcessamento = "Iniciando servi�o de processamento de pend�ncias de tr�mites de processos ($numProcID)";
        $this->gravarLogDebug($mensagemInicioProcessamento, 0, true);

      while($this->objGearmanWorker->work())
        {
        try {
            $numReturnCode = $this->objGearmanWorker->returnCode();

          switch ($numReturnCode) {
            case GEARMAN_SUCCESS:
                break;

            case GEARMAN_TIMEOUT:
                    //Nenhuma a��o necess�rio, sendo que timeout � utilizado apenas para avalia��o de sinal pcntl_signal de interrup��o
                break;

            case GEARMAN_ERRNO:
              $strErro = "Erro no processamento de pend�ncias do PEN. ErrorCode: $numReturnCode";
              LogSEI::getInstance()->gravar($strErro);
              $this->gravarLogDebug($strErro, 0);
                break;

            default:
              $this->gravarLogDebug("C�digo de retorno $numReturnCode de processamento de tarefas n�o tradado", 0);
                break;
          }
        } catch (\Exception $e) {
            $this->gravarLogDebug(InfraException::inspecionar($e), 0, true);
            LogSEI::getInstance()->gravar(InfraException::inspecionar($e));
        }
      }

        $numProcID = getmygid();
        $this->gravarLogDebug("Finaliza��o do processamento de tarefas do Barramento do PEN (pid=$numProcID)", 0);
    }
    catch(Exception $e) {
        $strAssunto = 'Falha no processamento de pend�ncias de tr�mite do PEN';
        $strErro = 'Erro: '. InfraException::inspecionar($e);
        LogSEI::getInstance()->gravar($strAssunto."\n\n".$strErro);
        throw new InfraException($strAssunto."\n\n".$strErro, $e);
    }
  }

    /**
     * Processa a mensagem de pend�ncia de Envio de Processos
     *
     * @param object $idTramite Contexto com informa��es para processamento da tarefa
     * @return void
     */
  public function enviarProcesso($idTramite)
    {
      $this->gravarLogDebug("Processando envio de processo [enviarProcesso] com IDT $idTramite", 0, true);
  }


    /**
     * Processa a mensagem de pend�ncia de Envio de Componentes Digitais
     *
     * @param object $idTramite Contexto com informa��es para processamento da tarefa
     * @return void
     */
  public function enviarComponenteDigital($idTramite)
    {
      $this->gravarLogDebug("Processando envio de componentes digitais [enviarComponenteDigital] com IDT $idTramite", 0, true);
  }


    /**
     * Processa a mensagem de pend�ncia de Recebimento de Recibo de Conclus�o de Tr�mite
     *
     * @param object $idTramite Contexto com informa��es para processamento da tarefa
     * @return void
     */
  public function receberReciboTramite($idTramite)
    {
      $this->gravarLogDebug("Processando recebimento de recibo de tr�mite [receberReciboTramite] com IDT $idTramite", 0, true);
      $numIdentificacaoTramite = intval($idTramite);
      $objReceberReciboTramiteRN = new ReceberReciboTramiteRN();
      $objReceberReciboTramiteRN->receberReciboDeTramite($numIdentificacaoTramite);
  }

    /**
     * Processa a mensagem de pend�ncia de Recebimento de Processo ou Documento Avulso
     *
     * @param object $idTramite Contexto com informa��es para processamento da tarefa
     * @return void
     */
  public function receberProcedimento($idTramite)
    {
    try{
        $this->gravarLogDebug("Processando recebimento de protocolo [receberProcedimento] com IDT " . $idTramite, 0, true);
        $numTempoInicialRecebimento = microtime(true);

        $numIdentificacaoTramite = intval($idTramite);
        $objReceberProcedimentoRN = new ReceberProcedimentoRN();
        $objReceberProcedimentoRN->receberProcedimento($numIdentificacaoTramite);

        $numTempoTotalRecebimento = round(microtime(true) - $numTempoInicialRecebimento, 2);
        $this->gravarLogDebug("Finalizado o recebimento de protocolo com IDT $idTramite(Tempo total: {$numTempoTotalRecebimento}s)", 0, true);
    }
    catch(Exception $e){
        //N�o recusa tr�mite caso o processo atual n�o possa ser desbloqueado, evitando que o processo fique aberto em dois sistemas ao mesmo tempo
        $bolDeveRecusarTramite = !($e instanceof InfraException && $e->getObjException() != null && $e->getObjException() instanceof ProcessoNaoPodeSerDesbloqueadoException);
      if($bolDeveRecusarTramite) {
          $objProcessoEletronicoRN = new ProcessoEletronicoRN();
          $strMensagem = ($e instanceof InfraException) ? $e->__toString() : $e->getMessage();
          $objProcessoEletronicoRN->recusarTramite($idTramite, $strMensagem, ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_OUTROU);
      }

        throw $e;
    }
  }


    /**
     * Processa a mensagem de pend�ncia de Recebimento de Tr�mites Recusados
     *
     * @param object $idTramite Contexto com informa��es para processamento da tarefa
     * @return void
     */
  public function receberTramitesRecusados($idTramite)
    {
      $this->gravarLogDebug("Processando tr�mite recusado [receberTramitesRecusados] com IDT $idTramite", 0, true);
      $numIdentificacaoTramite = intval($idTramite);
      $objReceberProcedimentoRN = new ReceberProcedimentoRN();
      $objReceberProcedimentoRN->receberTramitesRecusados($numIdentificacaoTramite);
  }


    /**
     * Processa a mensagem de pend�ncia de Recebimento de Componentes Digitais
     *
     * @param object $idTramite Contexto com informa��es para processamento da tarefa
     * @return void
     */
  public function receberComponenteDigital($idTramite) {
      $this->gravarLogDebug("Processando recebimento de componentes digitais [receberComponenteDigital] com IDT " . $idTramite, 0, true);
      // Caso receba mensagem indicando que foi realizado o recebimento dos componentes digitais, ent�o o recibo de conclu�o dever� ser enviado
      $this->enviarReciboTramiteProcesso($idTramite);
  }


    /**
     * Processa a mensagem de pend�ncia de Envio de Recibo de Tr�mite
     *
     * @param object $idTramite Contexto com informa��es para processamento da tarefa
     * @return void
     */
  public function enviarReciboTramiteProcesso($idTramite)
    {
      $this->gravarLogDebug("Processando envio do recibo de tr�mite [enviarReciboTramiteProcesso] com IDT $idTramite", 0, true);
      $numIdentificacaoTramite = intval($idTramite);
      $objEnviarReciboTramiteRN = new EnviarReciboTramiteRN();
      $objEnviarReciboTramiteRN->enviarReciboTramiteProcesso($numIdentificacaoTramite);
  }

    /**
     * Processa a mensagem de pend�ncia de Envio de Processo
     *
     * @param object $idProcedimento Contexto com informa��es para processamento da tarefa
     * @return void
     */
  public function expedirLote($idProcedimento)
    {
    try {

        $this->gravarLogDebug("Processando envio de protocolo [expedirProcedimento] com IDProcedimento " . $idProcedimento, 0, true);
        $numTempoInicialEnvio = microtime(true);

        $objPenLoteProcedimentoDTO = new PenLoteProcedimentoDTO();
        $objPenLoteProcedimentoDTO->retNumIdRepositorioOrigem();
        $objPenLoteProcedimentoDTO->retNumIdUnidadeOrigem();
        $objPenLoteProcedimentoDTO->retNumIdRepositorioDestino();
        $objPenLoteProcedimentoDTO->retStrRepositorioDestino();
        $objPenLoteProcedimentoDTO->retNumIdUnidadeDestino();
        $objPenLoteProcedimentoDTO->retStrUnidadeDestino();
        $objPenLoteProcedimentoDTO->retDblIdProcedimento();
        $objPenLoteProcedimentoDTO->retNumIdLote();
        $objPenLoteProcedimentoDTO->retNumIdAtividade();
        $objPenLoteProcedimentoDTO->retNumIdUnidade();
        $objPenLoteProcedimentoDTO->setDblIdProcedimento(intval($idProcedimento));
        $objPenLoteProcedimentoDTO->setNumIdAndamento(ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_NAO_INICIADO);

        $objPenLoteProcedimentoRN = new PenLoteProcedimentoRN();
        $objLoteProcedimento = $objPenLoteProcedimentoRN->consultarLoteProcedimento($objPenLoteProcedimentoDTO);

        $objExpedirProcedimentoDTO = new ExpedirProcedimentoDTO();
        $objExpedirProcedimentoDTO->setNumIdRepositorioOrigem($objLoteProcedimento->getNumIdRepositorioOrigem());
        $objExpedirProcedimentoDTO->setNumIdUnidadeOrigem($objLoteProcedimento->getNumIdUnidadeOrigem());

        $objExpedirProcedimentoDTO->setNumIdRepositorioDestino($objLoteProcedimento->getNumIdRepositorioDestino());
        $objExpedirProcedimentoDTO->setStrRepositorioDestino($objLoteProcedimento->getStrRepositorioDestino());
        $objExpedirProcedimentoDTO->setNumIdUnidadeDestino($objLoteProcedimento->getNumIdUnidadeDestino());
        $objExpedirProcedimentoDTO->setStrUnidadeDestino($objLoteProcedimento->getStrUnidadeDestino());
        $objExpedirProcedimentoDTO->setArrIdProcessoApensado(null);
        $objExpedirProcedimentoDTO->setBolSinUrgente(false);
        $objExpedirProcedimentoDTO->setDblIdProcedimento($objLoteProcedimento->getDblIdProcedimento());
        $objExpedirProcedimentoDTO->setNumIdMotivoUrgencia(null);
        $objExpedirProcedimentoDTO->setBolSinProcessamentoEmLote(true);
        $objExpedirProcedimentoDTO->setNumIdLote($objLoteProcedimento->getNumIdLote());
        $objExpedirProcedimentoDTO->setNumIdAtividade($objLoteProcedimento->getNumIdAtividade());
        $objExpedirProcedimentoDTO->setNumIdUnidade($objLoteProcedimento->getNumIdUnidade());

        $objExpedirProcedimentoRN = new ExpedirProcedimentoRN();
        $objExpedirProcedimentoRN->expedirProcedimento($objExpedirProcedimentoDTO);

        $numTempoTotalEnvio = round(microtime(true) - $numTempoInicialEnvio, 2);
        $this->gravarLogDebug("Finalizado o envio de protocolo com IDProcedimento $numIDT(Tempo total: {$numTempoTotalEnvio}s)", 0, true);            

    } catch (\Exception $e) {
        throw new InfraException('Falha ao expedir processso em lote.', $e);
    }
  }

  private function configurarCallbacks()
    {
      $this->objGearmanWorker->addFunction("enviarProcesso", function($job) {
          $this->enviarProcesso($job->workload());
      }, null, self::TIMEOUT_PROCESSAMENTO_JOB);

      $this->objGearmanWorker->addFunction("enviarComponenteDigital", function($job) {
          $this->enviarComponenteDigital($job->workload());
      }, null, self::TIMEOUT_PROCESSAMENTO_JOB);

      $this->objGearmanWorker->addFunction("receberReciboTramite", function($job) {
          $this->receberReciboTramite($job->workload());
      }, null, self::TIMEOUT_PROCESSAMENTO_JOB);

      $this->objGearmanWorker->addFunction("receberProcedimento", function($job) {
          $this->receberProcedimento($job->workload());
      }, null, self::TIMEOUT_PROCESSAMENTO_JOB);

      $this->objGearmanWorker->addFunction("receberTramitesRecusados", function($job) {
          $this->receberTramitesRecusados($job->workload());
      }, null, self::TIMEOUT_PROCESSAMENTO_JOB);

      $this->objGearmanWorker->addFunction("receberComponenteDigital", function($job) {
          $this->receberComponenteDigital($job->workload());
      }, null, self::TIMEOUT_PROCESSAMENTO_JOB);

      $this->objGearmanWorker->addFunction("enviarReciboTramiteProcesso", function($job) {
          $this->enviarReciboTramiteProcesso($job->workload());
      }, null, self::TIMEOUT_PROCESSAMENTO_JOB);

      $this->objGearmanWorker->addFunction("expedirLote", function($job) {
          $this->expedirLote($job->workload());
      }, null, self::TIMEOUT_PROCESSAMENTO_JOB);

  }

  private function gravarLogDebug($parStrMensagem, $parNumIdentacao = 0, $parBolLogTempoProcessamento = false)
    {
      $this->objPenDebug->gravar($parStrMensagem, $parNumIdentacao, $parBolLogTempoProcessamento);
  }

    /**
     * Inicializa processos workers de processamento de tarefas do PEN
     *
     * @return void
     */
  private static function verificarGearmanAtivo($parStrServidor, $parStrPorta)
    {
      // Verifica se existe um servidor do Gearman ativo para conex�o
      $bolAtivo = false;

    try {
      if(!class_exists("GearmanClient")){
        throw new InfraException("N�o foi poss�vel localizar as bibliotecas do PHP para conex�o ao GEARMAN (GearmanClient). " .
            "Verifique os procedimentos de instala��o do mod-sei-pen para maiores detalhes");
      }

      if(!class_exists("GearmanWorker")){
          throw new InfraException("N�o foi poss�vel localizar as bibliotecas do PHP para conex�o ao GEARMAN (GearmanWorker). " .
              "Verifique os procedimentos de instala��o do mod-sei-pen para maiores detalhes");
      }

        $objGearmanClient = new GearmanClient();
        $objGearmanClient->addServer($parStrServidor, $parStrPorta);
        return $objGearmanClient->ping("health");

    } catch (\Exception $e) {
        $strMensagem = "Alerta: N�o foi poss�vel ativar processamento ass�ncrono de tarefas do Barramento PEN via Gearman";
        $strDetalhes = "Devido ao impedimento, o processamento das tarefas ser� realizado diretamente pelo agendamento de tarefas";
        $objInfraException = new InfraException($strMensagem, $e, $strDetalhes);
        LogSEI::getInstance()->gravar(InfraException::inspecionar($objInfraException), LogSEI::$AVISO);
    }
  }

    /**
     * Inicializa processos workers de processamento de tarefas do PEN
     *
     * @return void
     */
  public static function inicializarWorkers($parNumQtdeWorkers = null)
    {
      $bolInicializado = false;
      $parNumQtdeWorkers = min($parNumQtdeWorkers ?: self::NUMERO_WORKERS_GEARMAN, self::MAXIMO_WORKERS_GEARMAN);

      $objConfiguracaoModPEN = ConfiguracaoModPEN::getInstance();
      $arrObjGearman = $objConfiguracaoModPEN->getValor("PEN", "Gearman", false);
      $strGearmanServidor = trim(@$arrObjGearman["Servidor"] ?: null);
      $strGearmanPorta = trim(@$arrObjGearman["Porta"] ?: null);

    if(!empty($strGearmanServidor)){
      try {
        if(self::verificarGearmanAtivo($strGearmanServidor, $strGearmanPorta)) {
          for ($worker=0; $worker < $parNumQtdeWorkers; $worker++) {
            $strComandoIdentificacaoWorker = sprintf(self::COMANDO_IDENTIFICACAO_WORKER_ID, $worker);
            exec($strComandoIdentificacaoWorker, $strSaida, $numCodigoResposta);

            if($numCodigoResposta != 0){
                 $strLocalizacaoScript = realpath(self::LOCALIZACAO_SCRIPT_WORKER);
                 $strPhpExec = empty(PHP_BINARY) ? "php" : PHP_BINARY;
                 $strPhpIni = php_ini_loaded_file();
                 $strPhpIni = $strPhpIni ? "-c $strPhpIni" : "";

                 $strComandoProcessamentoTarefas = sprintf(
                     self::COMANDO_EXECUCAO_WORKER,
                     $strPhpExec,             // Bin�rio do PHP utilizado no contexto de execu��o do script atual (ex: /usr/bin/php)
                     $strPhpIni,              // Arquivo de configuca��o o PHP utilizado no contexto de execu��o do script atual (ex: /etc/php.ini)
                     $strLocalizacaoScript,   // Path absoluto do script de processamento de tarefas do Barramento
                     $worker,                 // Identificador sequencial do processo paralelo a ser iniciado
                     "/dev/null"              // Localiza��o de log adicinal para registros de falhas n�o salvas pelo SEI no BDsss
                  );

                  shell_exec($strComandoProcessamentoTarefas);
            }
          }
        }

        // Confirma se existe algum worker ativo
        exec(self::COMANDO_IDENTIFICACAO_WORKER, $strSaida, $numCodigoRespostaAtivacao);
        $bolInicializado = $numCodigoRespostaAtivacao == 0;

      } catch (\Exception $e) {
          $strMensagem = "Alerta: N�o foi poss�vel ativar processamento ass�ncrono de tarefas do Barramento PEN via Gearman";
          $strDetalhes = "Devido ao impedimento, o processamento das tarefas ser� realizado diretamente pelo agendamento de tarefas";
          $objInfraException = new InfraException($strMensagem, $e, $strDetalhes);
          LogSEI::getInstance()->gravar(InfraException::inspecionar($objInfraException), LogSEI::$ERRO);
      }
    }

      return $bolInicializado;
  }

}

?>
