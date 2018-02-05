<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

class ProcessarPendenciasRN extends InfraAgendamentoTarefa {

  private static $instance = null;
  private $objGearmanWorker = null;

  protected function inicializarObjInfraIBanco(){
    return BancoSEI::getInstance();
  }

  public static function getInstance() {
    if (self::$instance == null) {
      self::$instance = new ProcessarPendenciasRN(ConfiguracaoSEI::getInstance(), SessaoSEI::getInstance(), BancoSEI::getInstance(), LogSEI::getInstance());
    }
    return self::$instance;
  }  

  public function __construct() {
    //Configuraзгo do worker do Gearman para realizar o processamento de tarefas
    $this->objGearmanWorker = new GearmanWorker();
    $this->objGearmanWorker->addServer('localhost', 4730);
    $this->configurarCallbacks();
  }

  public function processarPendencias()
  {
    try{
      ini_set('max_execution_time','0');
      ini_set('memory_limit','-1');

      InfraDebug::getInstance()->setBolLigado(true);
      InfraDebug::getInstance()->setBolDebugInfra(true);
      InfraDebug::getInstance()->setBolEcho(false);
      InfraDebug::getInstance()->limpar();
      
      $objPenParametroRN = new PenParametroRN();
      SessaoSEI::getInstance(false)->simularLogin('SEI', null, null, $objPenParametroRN->getParametro('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO'));

      $numSeg = InfraUtil::verificarTempoProcessamento();
      
      InfraDebug::getInstance()->gravar('ANALISANDO OS TRВMITES PENDENTES ENVIADOS PARA O УRGГO (PEN)');
      echo "[".date("d/m/Y H:i:s")."] Iniciando serviзo de processamento de pendкncias de trвmites de processos...\n";

      while($this->objGearmanWorker->work())
      {
        if ($this->objGearmanWorker->returnCode() != GEARMAN_SUCCESS)
        {
          $strAssunto = 'Erro executando agendamentos';
          $strErro = InfraException::inspecionar($e);      
          echo $strAssunto."\n\n".$strErro;
          LogSEI::getInstance()->gravar($strAssunto."\n\n".$strErro); 
          break;
        }
      }

      $numSeg = InfraUtil::verificarTempoProcessamento($numSeg);
      InfraDebug::getInstance()->gravar('TEMPO TOTAL DE EXECUCAO: '.$numSeg.' s');
      InfraDebug::getInstance()->gravar('FIM');
      LogSEI::getInstance()->gravar(InfraDebug::getInstance()->getStrDebug());
    } 
    catch(Exception $e){
      $strAssunto = 'Agendamento FALHOU';
      $strErro = '';
      $strErro .= 'Servidor: '.gethostname()."\n\n";
      $strErro .= 'Data/Hora: '.InfraData::getStrDataHoraAtual()."\n\n";
      $strErro .= 'Erro: '.InfraException::inspecionar($e);
      LogSEI::getInstance()->gravar($strAssunto."\n\n".$strErro); 
    }
  }

  private function configurarCallbacks() 
  {
    //PROCESSAMENTO DE TAREFAS RELACIONADAS AO ENVIO DE UM PROCESSO ELETRФNICO
    //////////////////////////////////////////////////////////////////////////

    //Etapa 01 - Processamento de pendкncias envio dos metadados do processo
    $this->objGearmanWorker->addFunction("enviarProcesso", function ($job) {

      InfraDebug::getInstance()->gravar("[".date("d/m/Y H:i:s")."] Processando tarefa [enviarProcesso] " . $job->workload());
      //TODO: Implementar tarefa relacionada
      //...

      //Agendamento de nova tarefa para envio dos componentes digitais do processo
      //$this->objGearmanClient->addTask("enviarComponenteDigital", $numIdentificacaoTramite, null);

    });

    //Etapa 02 - Processamento de pendкncias envio dos componentes digitais do processo
    $this->objGearmanWorker->addFunction("enviarComponenteDigital", function ($job) {

      InfraDebug::getInstance()->gravar("[".date("d/m/Y H:i:s")."] Processando tarefa [enviarComponenteDigital] " . $job->workload());
      //TODO: Implementar tarefa relacionada
      //...

      //Agendamento de nova tarefa para recebimento do recibo de envio do processo
      //$this->objGearmanClient->addTask("receberReciboTramite", $numIdentificacaoTramite, null);

    });

    //Etapa 03 - Processamento de pendкncias de recebimento do recibo de envio do processo
    $this->objGearmanWorker->addFunction("receberReciboTramite", function ($job) {
        
        $numIdentificacaoTramite = intval($job->workload());
        
        InfraDebug::getInstance()->gravar("[".date("d/m/Y H:i:s")."] Processando tarefa [receberReciboTramite] " . $job->workload());
        //TODO: Implementar tarefa relacionada
        
        $objPenTramiteProcessadoRN = new PenTramiteProcessadoRN(PenTramiteProcessadoRN::STR_TIPO_RECIBO);
        
        if(!$objPenTramiteProcessadoRN->isProcedimentoRecebido($numIdentificacaoTramite)){
            
            $objReceberReciboTramiteRN = new ReceberReciboTramiteRN();
            $objReceberReciboTramiteRN->receberReciboDeTramite($numIdentificacaoTramite);
        }
    });


    //PROCESSAMENTO DE TAREFAS RELACIONADAS AO RECEBIMENTO DE UM PROCESSO ELETRФNICO
    //////////////////////////////////////////////////////////////////////////

    //Processamento de pendкncias de recebimento dos metadados do processo
    $this->objGearmanWorker->addFunction("receberProcedimento", function ($job) {
      
        $numIdentificacaoTramite = intval($job->workload());

        InfraDebug::getInstance()->gravar("[".date("d/m/Y H:i:s")."] Processando tarefa [receberProcedimento] " . $job->workload());
               
        $objPenTramiteProcessadoRN = new PenTramiteProcessadoRN(PenTramiteProcessadoRN::STR_TIPO_PROCESSO);
        
        if(!$objPenTramiteProcessadoRN->isProcedimentoRecebido($numIdentificacaoTramite)){
            
            $objReceberProcedimentoRN = new ReceberProcedimentoRN(); 
            $objReceberProcedimentoRN->receberProcedimento($numIdentificacaoTramite);
            
            //TODO: A prуxima etapa deveria ser o recebimento dos componentes digitais, rotina tradada na funзгo receberProcedimento(...)
            //Agendamento de nova tarefa para envio do recibo de conclusгo do trгmite
            //ProcessarPendenciasRN::processarTarefa("enviarReciboTramiteProcesso", $job->workload());
            
          /*  $objEnviarReciboTramiteRN = new EnviarReciboTramiteRN();
            $objEnviarReciboTramiteRN->enviarReciboTramiteProcesso($numIdentificacaoTramite, $arrayHash);*/
        }
    });
    
    // Verifica no barramento os procedimentos que foram enviados por esta unidade
    // e foram recusados pelas mesmas
    $this->objGearmanWorker->addFunction("receberTramitesRecusados", function ($job) {
      
        InfraDebug::getInstance()->gravar("[".date("d/m/Y H:i:s")."] Processando tarefa [receberRecusaTramite] " . $job->workload());
        $numIdentificacaoTramite = intval($job->workload());
        
        $objReceberProcedimentoRN = new ReceberProcedimentoRN();  
        $objReceberProcedimentoRN->receberTramitesRecusados($numIdentificacaoTramite);
    });
    
    //Processamento de pendкncias de recebimento dos componentes digitais do processo
    $this->objGearmanWorker->addFunction("receberComponenteDigital", function ($job) {
      
      InfraDebug::getInstance()->gravar("[".date("d/m/Y H:i:s")."] Processando tarefa [receberComponenteDigital] " . $job->workload());
      //TODO: A prуxima etapa deveria ser o recebimento dos componentes digitais, rotina tradada na funзгo receberProcedimento(...)
      //...    

      //Agendamento de nova tarefa para envio do recibo de conclusгo do trвmite
      ProcessarPendenciasRN::processarTarefa("enviarReciboTramiteProcesso", $job->workload());
      //$this->objGearmanClient->addTaskBackground("enviarReciboTramiteProcesso", $numIdentificacaoTramite, null);
    });

    //Processamento de pendкncias de envio do recibo de conclusгo do trгmite do processo
    $this->objGearmanWorker->addFunction("enviarReciboTramiteProcesso", function ($job) {

      InfraDebug::getInstance()->gravar("[".date("d/m/Y H:i:s")."] Processando tarefa [enviarReciboTramiteProcesso] " . $job->workload());

      $numIdentificacaoTramite = intval($job->workload());
      $objEnviarReciboTramiteRN = new EnviarReciboTramiteRN();
      $objEnviarReciboTramiteRN->enviarReciboTramiteProcesso($numIdentificacaoTramite);
    });
  }

  static function processarTarefa($strNomeTarefa, $strWorkload)
  {
    $objClient = new GearmanClient();    
    $objClient->addServer('localhost', 4730);
    //$objClient->addTaskBackground($strNomeTarefa, $strWorkload);
    //$objClient->runTasks();
    $objClient->doBackground($strNomeTarefa, $strWorkload);
  }
}

//TODO: Tratar envio de e-mail em caso de falhas de execuзгo
SessaoSEI::getInstance(false);
ProcessarPendenciasRN::getInstance()->processarPendencias();

?>