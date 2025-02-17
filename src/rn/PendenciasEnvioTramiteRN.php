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
          throw new InfraException("Módulo do Tramita: Serviço de monitoramento de pendências não pode ser iniciado devido falta de configuração de endereços de WebServices");
      }

        ModPenUtilsRN::simularLoginUnidadeRecebimento();
        $mensagemInicioMonitoramento = 'Iniciando serviço de monitoramento de envio de pendências de trâmites de processos';
        $this->gravarLogDebug($mensagemInicioMonitoramento, 0);

      do {
        try {
            $this->gravarLogDebug('Recuperando lista de pendências de envio do Tramita GOV.BR', 1);
            $arrObjPendenciasDTO = $this->obterPendenciasEnvioTramite();


          foreach ($arrObjPendenciasDTO as $objPendenciaDTO) {
            $numIdTramite = $objPendenciaDTO->getNumIdentificacaoTramite();
            $strStatusTramite = $objPendenciaDTO->getStrStatus();
            $mensagemLog = ">>> Enviando pendência $numIdTramite (status $strStatusTramite) para fila de processamento";
            $this->gravarLogDebug($mensagemLog, 3);

            try {
                  $this->expedirPendenciaProcessamento($objPendenciaDTO, $parBolSegundoPlano);
            } catch (\Exception $e) {
                    $this->gravarAmostraErroLogSEI($e);
                    $this->gravarLogDebug(InfraException::inspecionar($e));
            }
          }
        } catch (ModuloIncompativelException $e) {
            // Sai loop de eventos para finalizar o script e subir uma nova versão atualizada
            throw $e;
        } catch (Exception $e) {
            //Apenas registra a falha no log do sistema e reinicia o ciclo de requisição
            $this->gravarAmostraErroLogSEI($e);
            $this->gravarLogDebug(InfraException::inspecionar($e));
        }

        if ($parBolMonitorarPendencias) {
            $this->gravarLogDebug(sprintf("Reiniciando monitoramento de pendências em %s segundos", self::TEMPO_ESPERA_REINICIALIZACAO_MONITORAMENTO), 1);
            sleep(self::TEMPO_ESPERA_REINICIALIZACAO_MONITORAMENTO);
            $this->carregarParametrosIntegracao();
        }
      } while ($parBolMonitorarPendencias);
    } catch (Exception $e) {
        $this->gravarLogDebug(InfraException::inspecionar($e));
        $this->gravarAmostraErroLogSEI($e);
        return self::CODIGO_EXECUCAO_ERRO;
    }

      // Caso não esteja sendo realizado o monitoramente de pendências, lança exceção diretamente na página para apresentação ao usuário
      $this->salvarLogDebug($parBolDebug);

      return self::CODIGO_EXECUCAO_SUCESSO;
  }

    /**
     * Função para recuperar as pendências de trâmite que já foram recebidas pelo serviço de long pulling e não foram processadas com sucesso
     *
     * @param  num $parNumIdTramiteRecebido
     * @return [type]                          [description]
     */
  private function obterPendenciasEnvioTramite()
    {
      //Obter todos os trâmites pendentes antes de iniciar o monitoramento
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
      $this->gravarLogDebug(count($arrObjPendenciasDTO) . " pendências de trâmites identificadas", 2);
    foreach ($arrObjPendenciasDTO as $objPendenciaDTO) {
        //Captura todas as pendências e status retornadas para impedir duplicidade
        $arrPendenciasRetornadas[] = sprintf("%d-%s", $objPendenciaDTO->getNumIdentificacaoTramite(), $objPendenciaDTO->getStrStatus());
        yield $objPendenciaDTO;
    }
  }

    /**
     * Envia a pendência de trâmite para a fila de processamento do tarefas de acordo com a estratégia definida
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
     * Processa pendência de recebimento diretamente através da chamada das funções de processamento
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
        $this->gravarLogDebug("Situação do trâmite ($strStatus) não pode ser tratada para expedir pendências.");
      }

        $objProcessarPendenciaRN->expedirBloco($numIDT);
    }
  }

    /**
     * Envia pendência de recebimento para fila de tarefas do Gearman para processamento futuro
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
        $this->gravarLogDebug("Situação do trâmite ($strStatus) não pode ser tratada para expedir pendências.");
      }

        $client->addTaskBackground('expedirBloco', $numIDT, null, $numIDT);
        $client->runTasks();
    }
  }

    /**
     * Inicia o envio de tarefas de Barramento do PEN em novo processo separado,
     * evitando o bloqueio da thread da aplicação
     *
     * @param  int     $parNumQtdeWorkers  Quantidade de processos paralelos que serão iniciados
     * @param  boolean $parBolMonitorar    Indicação se o novo processo ficará monitorando o Barramento do PEN
     * @param  boolean $parBolSegundoPlano Indicação se será utilizado o processamento das tarefas em segundo plano com o Gearman
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
        exec(self::COMANDO_IDENTIFICACAO_WORKER_ENVIO, $strSaida, $numCodigoRespostaAtivacao);
        $bolInicializado = $numCodigoRespostaAtivacao == 0;
    } catch (\Exception $e) {
        $strMensagem = "Falha: Não foi possível iniciar o monitoramento de tarefas Barramento Tramita GOV.BR";
        $objInfraException = new InfraException($strMensagem, $e);
        throw $objInfraException;
    }

      return $bolInicializado;
  }
}
