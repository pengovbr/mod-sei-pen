<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

class ProcessarPendenciasRN extends InfraAgendamentoTarefa
{
    private static $instance = null;
    private $objGearmanWorker = null;

    protected function inicializarObjInfraIBanco()
    {
        return BancoSEI::getInstance();
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
          self::$instance = new ProcessarPendenciasRN(ConfiguracaoSEI::getInstance(), SessaoSEI::getInstance(), BancoSEI::getInstance(), LogSEI::getInstance());
      }
      return self::$instance;
    }

    public function __construct()
    {
        $this->objGearmanWorker = new GearmanWorker();
        $this->objGearmanWorker->addServer("127.0.0.1", 4730);
        $this->configurarCallbacks();
    }

    public function processarPendencias()
    {
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

            $mensagemInicioProcessamento = 'Iniciando serviço de processamento de pendências de trâmites de processos';
            LogSEI::getInstance()->gravar($mensagemInicioProcessamento, LogSEI::$INFORMACAO);
            $this->gravarLogDebug($mensagemInicioProcessamento, 0, true);

            while($this->objGearmanWorker->work())
            {
                if ($this->objGearmanWorker->returnCode() != GEARMAN_SUCCESS) {
                    $strErro = 'Erro no processamento de pendências do PEN. ErrorCode: ' . $this->objGearmanWorker->returnCode();
                    LogSEI::getInstance()->gravar($strErro);
                    break;
                }
            }
        }
        catch(Exception $e) {
            $strAssunto = 'Falha no processamento de pendências de trâmite do PEN';
            $strErro = 'Erro: '. InfraException::inspecionar($e);
            LogSEI::getInstance()->gravar($strAssunto."\n\n".$strErro);
            throw new InfraException($strAssunto."\n\n".$strErro, $e);
        }
    }

    private function configurarCallbacks()
    {
        // Processamento de pendências envio dos metadados do processo
        $this->objGearmanWorker->addFunction("enviarProcesso", function ($job) {
            $this->gravarLogDebug("Processando envio de processo [enviarComponenteDigital] com IDT " . $job->workload(), 0, true);
        });

        // Processamento de pendências envio dos componentes digitais do processo
        $this->objGearmanWorker->addFunction("enviarComponenteDigital", function ($job) {
            $this->gravarLogDebug("Processando envio de componentes digitais [enviarComponenteDigital] com IDT " . $job->workload(), 0, true);
        });

        // Processamento de pendências de recebimento do recibo de envio do processo
        $this->objGearmanWorker->addFunction("receberReciboTramite", function ($job) {
            try{
                $this->gravarLogDebug("Processando recebimento de recibo de trâmite [receberReciboTramite] com IDT " . $job->workload(), 0, true);
                $numIdentificacaoTramite = intval($job->workload());
                $objPenTramiteProcessadoRN = new PenTramiteProcessadoRN(PenTramiteProcessadoRN::STR_TIPO_RECIBO);
                if(!$objPenTramiteProcessadoRN->isProcedimentoRecebido($numIdentificacaoTramite)){
                    $objReceberReciboTramiteRN = new ReceberReciboTramiteRN();
                    $objReceberReciboTramiteRN->receberReciboDeTramite($numIdentificacaoTramite);
                }

            }
            catch(Exception $e){
                $this->gravarLogDebug(InfraException::inspecionar($e), 0, true);
                LogSEI::getInstance()->gravar(InfraException::inspecionar($e));
            }
        });

        //Processamento de pendências de recebimento dos metadados do processo
        $this->objGearmanWorker->addFunction("receberProcedimento", function ($job) {
            try{
                $this->gravarLogDebug("Processando recebimento de processo [receberProcedimento] com IDT " . $job->workload(), 0, true);
                $numIdentificacaoTramite = intval($job->workload());
                $objPenTramiteProcessadoRN = new PenTramiteProcessadoRN(PenTramiteProcessadoRN::STR_TIPO_PROCESSO);

                if(!$objPenTramiteProcessadoRN->isProcedimentoRecebido($numIdentificacaoTramite)){
                    $objReceberProcedimentoRN = new ReceberProcedimentoRN();
                    $objReceberProcedimentoRN->receberProcedimento($numIdentificacaoTramite);
                }
            }
            catch(Exception $e){
                $this->gravarLogDebug(InfraException::inspecionar($e), 0, true);
                LogSEI::getInstance()->gravar(InfraException::inspecionar($e));
                $objProcessoEletronicoRN = new ProcessoEletronicoRN();
                $strMensagem = ($e instanceof InfraException) ? $e->__toString() : $e->getMessage();
                $objProcessoEletronicoRN->recusarTramite($numIdentificacaoTramite, $strMensagem, ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_OUTROU);
            }
        });

        // Verifica no barramento os procedimentos que foram enviados por esta unidade e foram recusados pelas mesmas
        $this->objGearmanWorker->addFunction("receberTramitesRecusados", function ($job) {
            try {
                $this->gravarLogDebug("Processando trâmite recusado [receberTramitesRecusados] com IDT " . $job->workload(), 0, true);
                $numIdentificacaoTramite = intval($job->workload());
                $objReceberProcedimentoRN = new ReceberProcedimentoRN();
                $objReceberProcedimentoRN->receberTramitesRecusados($numIdentificacaoTramite);
            } catch (Exception $e) {
                $this->gravarLogDebug(InfraException::inspecionar($e), 0, true);
                LogSEI::getInstance()->gravar(InfraException::inspecionar($e));
            }
        });

        //Processamento de pendências de recebimento dos componentes digitais do processo
        $this->objGearmanWorker->addFunction("receberComponenteDigital", function ($job) {
            $this->gravarLogDebug("Processando recebimento de componentes digitais [receberComponenteDigital] com IDT " . $job->workload(), 0, true);
            ProcessarPendenciasRN::processarTarefa("enviarReciboTramiteProcesso", $job->workload());
        });

        //Processamento de pendências de envio do recibo de conclusão do trãmite do processo
        $this->objGearmanWorker->addFunction("enviarReciboTramiteProcesso", function ($job) {
            try {
                $this->gravarLogDebug("Processando envio do recibo de trâmite [enviarReciboTramiteProcesso] com IDT " . $job->workload(), 0, true);
                $numIdentificacaoTramite = intval($job->workload());
                $objEnviarReciboTramiteRN = new EnviarReciboTramiteRN();
                $objEnviarReciboTramiteRN->enviarReciboTramiteProcesso($numIdentificacaoTramite);
            } catch (Exception $e) {
                $this->gravarLogDebug(InfraException::inspecionar($e), 0, true);
                LogSEI::getInstance()->gravar(InfraException::inspecionar($e));
            }
      });
    }

    private function gravarLogDebug($strMensagem, $numIdentacao=0, $bolEcho=false)
    {
        $strDataLog = date("d/m/Y H:i:s");
        $strLog = sprintf("[%s] [PROCESSAMENTO] %s %s", $strDataLog, str_repeat(" ", $numIdentacao * 4), $strMensagem);
        InfraDebug::getInstance()->gravar($strLog);
        if(!InfraDebug::getInstance()->isBolEcho() && $bolEcho) echo sprintf("\n[%s] [PROCESSAMENTO] %s", $strDataLog, $strMensagem);
    }

    static function processarTarefa($strNomeTarefa, $strWorkload)
    {
        $objClient = new GearmanClient();
        $objClient->addServer("127.0.0.1", 4730);
        $objClient->doBackground($strNomeTarefa, $strWorkload);
    }
}

SessaoSEI::getInstance(false);
ProcessarPendenciasRN::getInstance()->processarPendencias();

?>
