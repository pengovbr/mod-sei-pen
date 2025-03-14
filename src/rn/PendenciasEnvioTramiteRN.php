<?php

require_once DIR_SEI_WEB . '/SEI.php';

class PendenciasEnvioTramiteRN extends PendenciasTramiteRN
{
  public function expedirPendencias($parBolMonitorarPendencias = false, $parBolSegundoPlano = false, $parBolDebug = false)
    {
    try {
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '-1');

      if (!PENIntegracao::verificarCompatibilidadeConfiguracoes()) {
        return false;
      }

      if (empty($this->strEnderecoServico) && empty($this->strEnderecoServicoPendencias)) {
          throw new InfraException("M�dulo do Tramita: Servi�o de monitoramento de pend�ncias n�o pode ser iniciado devido falta de configura��o de endere�os de WebServices");
      }

        ModPenUtilsRN::simularLoginUnidadeRecebimento();
        $mensagemInicioMonitoramento = 'Iniciando servi�o de monitoramento de envio de pend�ncias de tr�mites de processos';
        $this->gravarLogDebug($mensagemInicioMonitoramento, 0);

      do {
        try {
            $this->gravarLogDebug('Recuperando lista de pend�ncias de envio do Tramita GOV.BR', 1);
            $arrObjPendenciasDTO = $this->obterPendenciasEnvioTramite();


          foreach ($arrObjPendenciasDTO as $objPendenciaDTO) {
            $numIdTramite = $objPendenciaDTO->getNumIdentificacaoTramite();
            $strStatusTramite = $objPendenciaDTO->getStrStatus();
            $mensagemLog = ">>> Enviando pend�ncia $numIdTramite (status $strStatusTramite) para fila de processamento";
            $this->gravarLogDebug($mensagemLog, 3);

            try {
                  $this->expedirPendenciaProcessamento($objPendenciaDTO, $parBolSegundoPlano);
            } catch (\Exception $e) {
                    $this->gravarAmostraErroLogSEI($e);
                    $this->gravarLogDebug(InfraException::inspecionar($e));
            }
          }
        } catch (ModuloIncompativelException $e) {
            // Sai loop de eventos para finalizar o script e subir uma nova vers�o atualizada
            throw $e;
        } catch (Exception $e) {
            //Apenas registra a falha no log do sistema e reinicia o ciclo de requisi��o
            $this->gravarAmostraErroLogSEI($e);
            $this->gravarLogDebug(InfraException::inspecionar($e));
        }

        if ($parBolMonitorarPendencias) {
            $this->gravarLogDebug(sprintf("Reiniciando monitoramento de pend�ncias em %s segundos", self::TEMPO_ESPERA_REINICIALIZACAO_MONITORAMENTO), 1);
            sleep(self::TEMPO_ESPERA_REINICIALIZACAO_MONITORAMENTO);
            $this->carregarParametrosIntegracao();
        }
      } while ($parBolMonitorarPendencias);
    } catch (Exception $e) {
        $this->gravarLogDebug(InfraException::inspecionar($e));
        $this->gravarAmostraErroLogSEI($e);
        return self::CODIGO_EXECUCAO_ERRO;
    }

      // Caso n�o esteja sendo realizado o monitoramente de pend�ncias, lan�a exce��o diretamente na p�gina para apresenta��o ao usu�rio
      $this->salvarLogDebug($parBolDebug);

      return self::CODIGO_EXECUCAO_SUCESSO;
  }

    /**
     * Fun��o para recuperar as pend�ncias de tr�mite que j� foram recebidas pelo servi�o de long pulling e n�o foram processadas com sucesso
     *
     * @param  num $parNumIdTramiteRecebido
     * @return [type]                          [description]
     */
  private function obterPendenciasEnvioTramite()
    {
      //Obter todos os tr�mites pendentes antes de iniciar o monitoramento
      $arrPendenciasRetornadas = [];
      $arrObjPendenciasDTO = [];
      $objPenBlocoProcessoDTO = new PenBlocoProcessoDTO();
      $objPenBlocoProcessoDTO->retNumIdBlocoProcesso();
      $objPenBlocoProcessoDTO->retDblIdProtocolo();
      $objPenBlocoProcessoDTO->retNumIdAndamento();
      $objPenBlocoProcessoDTO->retNumIdAtividade();
      $objPenBlocoProcessoDTO->retNumIdRepositorioDestino();
      $objPenBlocoProcessoDTO->retStrRepositorioDestino();
      $objPenBlocoProcessoDTO->retNumIdRepositorioOrigem();
      $objPenBlocoProcessoDTO->retNumIdUnidadeDestino();
      $objPenBlocoProcessoDTO->retStrUnidadeDestino();
      $objPenBlocoProcessoDTO->retNumIdUnidadeOrigem();
      $objPenBlocoProcessoDTO->retNumIdBloco();
      $objPenBlocoProcessoDTO->retNumIdUsuario();
      $objPenBlocoProcessoDTO->retStrProtocoloFormatadoProtocolo();
      $objPenBlocoProcessoDTO->setNumIdAndamento(ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_NAO_INICIADO);
      $objPenBlocoProcessoRN = new PenBlocoProcessoRN();
      $arrObjPenBlocoProcessoDTO = $objPenBlocoProcessoRN->obterPendenciasBloco($objPenBlocoProcessoDTO);
    foreach ($arrObjPenBlocoProcessoDTO as $objPenBlocoProcessoDTO) {
        $objPendenciaDTO = new PendenciaDTO();
        $objPendenciaDTO->setNumIdentificacaoTramite($objPenBlocoProcessoDTO->getDblIdProtocolo());
        $objPendenciaDTO->setStrStatus($objPenBlocoProcessoDTO->getNumIdAndamento());
        $arrObjPendenciasDTO[] = $objPendenciaDTO;
    }
      $this->gravarLogDebug(count($arrObjPendenciasDTO) . " pend�ncias de tr�mites identificadas", 2);
    foreach ($arrObjPendenciasDTO as $objPendenciaDTO) {
        //Captura todas as pend�ncias e status retornadas para impedir duplicidade
        $arrPendenciasRetornadas[] = sprintf("%d-%s", $objPendenciaDTO->getNumIdentificacaoTramite(), $objPendenciaDTO->getStrStatus());
        yield $objPendenciaDTO;
    }
  }

    /**
     * Envia a pend�ncia de tr�mite para a fila de processamento do tarefas de acordo com a estrat�gia definida
     *
     * @param  stdClass $objPendencia
     * @return void
     */
  private function expedirPendenciaProcessamento($objPendencia, $parBolSegundoPlano)
    {
    if ($parBolSegundoPlano && $this->servicoGearmanAtivo()) {
        $this->expedirPendenciaFilaProcessamento($objPendencia);
    } else {
        $this->expedirPendenciaProcessamentoDireto($objPendencia);
    }
  }

    /**
     * Processa pend�ncia de recebimento diretamente atrav�s da chamada das fun��es de processamento
     *
     * @param  stclass $objPendencia
     * @return void
     */
  private function expedirPendenciaProcessamentoDireto($objPendencia)
    {
    if (isset($objPendencia)) {
        $numIDT = strval($objPendencia->getNumIdentificacaoTramite());
        $numStatus = strval($objPendencia->getStrStatus());
        $objProcessarPendenciaRN = new ProcessarPendenciasRN();

      if (!in_array(
            $numStatus,
            [ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_ENVIADOS_REMETENTE]
        )
        ) {
        $strStatus = $objPendencia->getStrStatus();
        $this->gravarLogDebug("Situa��o do tr�mite ($strStatus) n�o pode ser tratada para expedir pend�ncias.");
      }

        $objProcessarPendenciaRN->expedirBloco($numIDT);
    }
  }

    /**
     * Envia pend�ncia de recebimento para fila de tarefas do Gearman para processamento futuro
     *
     * @param  stdclass $objPendencia
     * @return void
     */
  private function expedirPendenciaFilaProcessamento($objPendencia)
    {
    if (isset($objPendencia)) {
        $client = new GearmanClient();
        $client->addServer($this->strGearmanServidor, $this->strGearmanPorta);

        $numIDT = strval($objPendencia->getNumIdentificacaoTramite());
        $numStatus = strval($objPendencia->getStrStatus());

      if ($numStatus != ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO) {
        $strStatus = $objPendencia->getStrStatus();
        $this->gravarLogDebug("Situa��o do tr�mite ($strStatus) n�o pode ser tratada para expedir pend�ncias.");
      }

        $client->addTaskBackground('expedirBloco', $numIDT, null, $numIDT);
        $client->runTasks();
    }
  }

    /**
     * Inicia o envio de tarefas de Barramento do PEN em novo processo separado,
     * evitando o bloqueio da thread da aplica��o
     *
     * @param  int     $parNumQtdeWorkers  Quantidade de processos paralelos que ser�o iniciados
     * @param  boolean $parBolMonitorar    Indica��o se o novo processo ficar� monitorando o Barramento do PEN
     * @param  boolean $parBolSegundoPlano Indica��o se ser� utilizado o processamento das tarefas em segundo plano com o Gearman
     * @return bool Monitoramento iniciado com sucesso
     */
  public static function inicializarMonitoramentoEnvioPendencias($parNumQtdeWorkers = null, $parBolMonitorar = false, $parBolSegundoPlano = false, $parBolDebugAtivo = false, $parStrUsuarioProcesso = null)
    {
      $bolInicializado = false;
      $parNumQtdeWorkers = min($parNumQtdeWorkers ?: self::NUMERO_PROCESSOS_MONITORAMENTO, self::MAXIMO_PROCESSOS_MONITORAMENTO);

    try {
      for ($worker = 0; $worker < $parNumQtdeWorkers; $worker++) {
        $strComandoIdentificacaoWorker = sprintf(self::COMANDO_IDENTIFICACAO_WORKER_ID_ENVIO, $worker);
        exec($strComandoIdentificacaoWorker, $strSaida, $numCodigoResposta);

        if ($numCodigoResposta != 0) {
            $strLocalizacaoScript = realpath(self::LOCALIZACAO_SCRIPT_WORKER_ENVIO);
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
        exec(self::COMANDO_IDENTIFICACAO_WORKER_ENVIO, $strSaida, $numCodigoRespostaAtivacao);
        $bolInicializado = $numCodigoRespostaAtivacao == 0;
    } catch (\Exception $e) {
        $strMensagem = "Falha: N�o foi poss�vel iniciar o monitoramento de tarefas Barramento Tramita GOV.BR";
        $objInfraException = new InfraException($strMensagem, $e);
        throw $objInfraException;
    }

      return $bolInicializado;
  }
}
