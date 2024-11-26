<?php

require_once DIR_SEI_WEB.'/SEI.php';

class PENAgendamentoRN extends InfraRN
{
  protected function inicializarObjInfraIBanco() {
      return BancoSEI::getInstance();
  }

    /**
     * Atualização das hipóteses legais vindas do barramento
     * @throws InfraException
     */

  protected function atualizarHipotesesLegaisControlado()
    {
    try {
        $objBD = new PenHipoteseLegalBD($this->inicializarObjInfraIBanco());
        $processoEletronicoRN = new ProcessoEletronicoRN();
        $hipotesesPen = $processoEletronicoRN->consultarHipotesesLegais();
        $hipotesesPenDesativadas = $processoEletronicoRN->consultarHipotesesLegais(false);

      $hipoteses = array();
      if (!empty($hipotesesPen) && !empty($hipotesesPen['hipotesesLegais'])) {
        $hipoteses = $hipotesesPen['hipotesesLegais'];
      }

      if (!empty($hipotesesPenDesativadas) && !empty($hipotesesPenDesativadas['hipotesesLegais'])) {
        $hipoteses = array_merge($hipoteses, $hipotesesPenDesativadas['hipotesesLegais']);
      }

      if(empty($hipoteses)){
        throw new InfraException('Não foi possível obter as hipóteses legais dos serviços de integração');
      }

        //Para cada hipótese vinda do PEN será verificado a existencia.
      foreach ($hipoteses as $hipotese) {

          $objDTO = new PenHipoteseLegalDTO();
          $objDTO->setNumIdentificacao($hipotese['identificacao']);
          $objDTO->setNumMaxRegistrosRetorno(1);
          $objDTO->retStrNome();
          $objDTO->retNumIdHipoteseLegal();
          $objConsulta = $objBD->consultar($objDTO);

          //Caso não haja um nome para a hipótese legal, ele pula para a próxima.
        if (empty($hipotese['nome'])) {
          continue;
        }

          $objDTO->setStrNome(mb_convert_encoding($hipotese['nome'], 'ISO-8859-1', 'UTF-8'));

        if ($hipotese['status']) {
            $objDTO->setStrAtivo('S');
        } else {
            $objDTO->setStrAtivo('N');
        }

          //Caso não exista a hipótese irá cadastra-la no sei.
        if (empty($objConsulta)) {
            $objBD->cadastrar($objDTO);
        } else {
            //Caso contrário apenas irá atualizar os dados.
            $objDTO->setNumIdHipoteseLegal($objConsulta->getNumIdHipoteseLegal());
            $objBD->alterar($objDTO);
        }
      }

        LogSEI::getInstance()->gravar("Hipóteses Legais do Tramita GOV.BR atualizadas com sucesso.", LogSEI::$INFORMACAO);
    } catch (Exception $e) {
        throw new InfraException('Erro no agendamento das Hipóteses Legais', $e);
    }
  }


    /**
     * Rotina de atualização das espécies documentais do Barramento PEN na base de dados do SEI
     *
     * Durante a sincronização, as espécies documentais podem ser cadastradas, removidas ou atualizadas
     *
     * @throws InfraException
     * @return void
     */
  protected function atualizarEspeciesDocumentaisControlado()
    {
    try{
        // Obtém lista de espécies documentais do Barramento de Serviços do PEN
        $processoEletronicoRN = new ProcessoEletronicoRN();
        $arrEspeciesDocumentaisPEN = $processoEletronicoRN->consultarEspeciesDocumentais();

        // Obtém lista de espécies documentais registradas na base de dados do SEI
        $objGenericoBD = new GenericoBD($this->inicializarObjInfraIBanco());
        $objEspecieDocumentalDTO = new EspecieDocumentalDTO();
        $objEspecieDocumentalDTO->retDblIdEspecie();
        $objEspecieDocumentalDTO->retStrNomeEspecie();
        $arrIdsEspeciesDocumentaisSEI = InfraArray::converterArrInfraDTO($objGenericoBD->listar($objEspecieDocumentalDTO), "NomeEspecie", "IdEspecie");

        // Combina e percorre as duas listas para avaliar o que precisar ser feito: inserir, desativar ou alterar a descrição
        $arrEspeciesDocumentaisCombinadas = array_replace($arrIdsEspeciesDocumentaisSEI, $arrEspeciesDocumentaisPEN);
      foreach ($arrEspeciesDocumentaisCombinadas as $numIdEspecie => $strNomeEspecie) {
        $numIdEspecie = intval($numIdEspecie);
        $bolExisteBaseDados = array_key_exists($numIdEspecie, $arrIdsEspeciesDocumentaisSEI);
        $bolExisteBarramento = array_key_exists($numIdEspecie, $arrEspeciesDocumentaisPEN);
        $bolNomesDiferentes = ($bolExisteBaseDados && $bolExisteBaseDados && $arrEspeciesDocumentaisPEN[$numIdEspecie] != $arrIdsEspeciesDocumentaisSEI[$numIdEspecie]);

        // Prepara consulta e atualização da espécie documental
        $objEspecieDocumentalDTO = new EspecieDocumentalDTO();
        $objEspecieDocumentalDTO->setDblIdEspecie($numIdEspecie);

        if($bolExisteBarramento && !$bolExisteBaseDados){
            // Caso a espécie documental EXISTA no Barramento do PEN mas não exista no SEI, necessário fazer o seu cadastramento
          if ($objGenericoBD->contar($objEspecieDocumentalDTO) == 0) {
            $objEspecieDocumentalDTO->setDblIdEspecie($numIdEspecie);
            $objEspecieDocumentalDTO->setStrNomeEspecie($strNomeEspecie);
            $objGenericoBD->cadastrar($objEspecieDocumentalDTO);
          }
        } elseif(!$bolExisteBarramento && $bolExisteBaseDados){
            // Caso a espécie documental NÂO exista no Barramento do PEN mas exista no SEI, necessário fazer a sua desativação
          if ($objGenericoBD->contar($objEspecieDocumentalDTO) > 0) {
                // Remove mapeamentos de Tipos de Documentos para Envio vinculados ao código de espécie
                $objPenRelTipoDocMapEnviadoRN = new PenRelTipoDocMapEnviadoRN();
                $objPenRelTipoDocMapEnviadoRN->excluirPorEspecieDocumental($numIdEspecie);

                // Remove mapeamentos de Tipos de Documentos para Envio vinculados ao código de espécie
                $objPenRelTipoDocMapRecebidoRN = new PenRelTipoDocMapRecebidoRN();
                $objPenRelTipoDocMapRecebidoRN->excluirPorEspecieDocumental($numIdEspecie);

                // Remove a espécie documental do PEN que não mais existe no Barramento do PEN
                $objEspecieDocumentalDTO->setDblIdEspecie($numIdEspecie);
                $objEspecieDocumentalDTO->setStrNomeEspecie($strNomeEspecie);
                $objGenericoBD->excluir($objEspecieDocumentalDTO);
          }
        } elseif($bolExisteBarramento && $bolExisteBaseDados && $bolNomesDiferentes) {
            // Caso a espécie documental exista no Barramento do PEN e no SEI mas com nomes diferentes, necessário atualizar descrição
          if ($objGenericoBD->contar($objEspecieDocumentalDTO) > 0) {
                $objEspecieDocumentalDTO->setDblIdEspecie($numIdEspecie);
                $objEspecieDocumentalDTO->setStrNomeEspecie($strNomeEspecie);
                $objGenericoBD->alterar($objEspecieDocumentalDTO);
          }
        }
      }

        LogSEI::getInstance()->gravar("Espécies Documentais do Tramita GOV.BR atualizadas com sucesso.", LogSEI::$INFORMACAO);
    } catch (Exception $e) {
        throw new InfraException('Erro no agendamento de atualização de Hipóteses Legais', $e);
    }
  }

    /**
     * Atualização de dados do Barramento de Serviços do PEN para utilização pelo SEI nas configurações
     * @throws InfraException
     */
  protected function atualizarInformacoesPENControlado()
    {
      InfraDebug::getInstance()->setBolLigado(true);
      InfraDebug::getInstance()->setBolDebugInfra(false);
      InfraDebug::getInstance()->setBolEcho(false);
      InfraDebug::getInstance()->limpar();

    try {
      if(!PENIntegracao::verificarCompatibilidadeConfiguracoes()){
        return false;
      }

        $this->atualizarHipotesesLegais();
        $this->atualizarEspeciesDocumentais();

        LogSEI::getInstance()->gravar("Espécies Documentais para envio mapeadas com sucesso", LogSEI::$INFORMACAO);
        $objPenRelTipoDocMapEnviadoRN = new PenRelTipoDocMapEnviadoRN();
        $objPenRelTipoDocMapEnviadoRN->mapearEspeciesDocumentaisEnvio();

        LogSEI::getInstance()->gravar("Espécies Documentais para recebimento mapeadas com sucesso", LogSEI::$INFORMACAO);
        $objPenRelTipoDocMapRecebidoRN = new PenRelTipoDocMapRecebidoRN();
        $objPenRelTipoDocMapRecebidoRN->mapearEspeciesDocumentaisRecebimento();
    }catch(Exception $e){
        InfraDebug::getInstance()->setBolLigado(false);
        InfraDebug::getInstance()->setBolDebugInfra(false);
        InfraDebug::getInstance()->setBolEcho(false);

        throw new InfraException('Erro processamento atualização de informações do Barramento de Serviços do PEN.', $e);
    }
  }

    /**
     * Processa tarefas recebidas pelo Barramento de Serviços do PEN para receber novos processos/documentos,
     * notificações de conclusão de trâmites ou notificação de recusa de processos
     *
     * @return void
     */
  public function processarTarefasRecebimentoPEN($arrParametros)
    {
      InfraDebug::getInstance()->setBolLigado(true);
      InfraDebug::getInstance()->setBolDebugInfra(false);
      InfraDebug::getInstance()->setBolEcho(false);
      InfraDebug::getInstance()->limpar();

    try {
      if(!PENIntegracao::verificarCompatibilidadeConfiguracoes()){
        return false;
      }

        $bolDebugAtivo = array_key_exists('debug', $arrParametros) && $arrParametros['debug'][0] != false;
        $bolMonitoramentoAtivado = array_key_exists('monitorar', $arrParametros) && $arrParametros['monitorar'][0] != false;
        $strValorWorkers = array_key_exists('workers', $arrParametros) ? $arrParametros['workers'][0] : null;
        $strValorWorkers = (is_null($strValorWorkers) && array_key_exists('worker', $arrParametros)) ? $arrParametros['worker'][0] : $strValorWorkers;
        $numValorWorkers = is_numeric($strValorWorkers) ? intval($strValorWorkers) : null;
        $bolForcarInicializacaoWorkers = array_key_exists('forcarInicializacaoWorkers', $arrParametros) && $arrParametros['forcarInicializacaoWorkers'][0] == true;
        $bolAtivaWorker = (is_null($numValorWorkers) || $numValorWorkers > 0) && ($this->foiIniciadoPeloTerminal() || $bolForcarInicializacaoWorkers);

        $objConfiguracaoModPEN = ConfiguracaoModPEN::getInstance();
        $arrObjGearman = $objConfiguracaoModPEN->getValor("PEN", "Gearman", false);
        $bolExecutarEmSegundoPlano = !empty(trim(@$arrObjGearman["Servidor"] ?: null));

        // Inicializa workers do Gearman caso este componente esteja configurado e não desativado no agendamento do sistema
      if($bolAtivaWorker && $bolExecutarEmSegundoPlano){
        ProcessarPendenciasRN::inicializarWorkers($numValorWorkers);
      }

        // Faz uma requisição para o controlador do sistema
        PendenciasTramiteRN::inicializarMonitoramentoRecebimentoPendencias($numValorWorkers, $bolMonitoramentoAtivado, $bolExecutarEmSegundoPlano, $bolDebugAtivo);

    }catch(Exception $e){
        InfraDebug::getInstance()->setBolLigado(false);
        InfraDebug::getInstance()->setBolDebugInfra(false);
        InfraDebug::getInstance()->setBolEcho(false);

        throw new InfraException('Erro processando pendências de trâmites do Barramento de Serviços do PEN.', $e);
    }
  }

  /**
   * Processa tarefas recebidas pelo Barramento de Serviços do PEN para receber novos processos/documentos,
   * notificações de conclusão de trâmites ou notificação de recusa de processos
   *
   * @return void
   */
  public function processarTarefasEnvioPEN($arrParametros)
  {
    InfraDebug::getInstance()->setBolLigado(true);
    InfraDebug::getInstance()->setBolDebugInfra(false);
    InfraDebug::getInstance()->setBolEcho(false);
    InfraDebug::getInstance()->limpar();

    try {
      if (!PENIntegracao::verificarCompatibilidadeConfiguracoes()) {
        return false;
      }

      $bolDebugAtivo = array_key_exists('debug', $arrParametros) && $arrParametros['debug'][0] != false;
      $bolMonitoramentoAtivado = array_key_exists('monitorar', $arrParametros) && $arrParametros['monitorar'][0] != false;
      $strValorWorkers = array_key_exists('workers', $arrParametros) ? $arrParametros['workers'][0] : null;
      $strValorWorkers = (is_null($strValorWorkers) && array_key_exists('worker', $arrParametros)) ? $arrParametros['worker'][0] : $strValorWorkers;
      $numValorWorkers = is_numeric($strValorWorkers) ? intval($strValorWorkers) : null;
      $bolForcarInicializacaoWorkers = array_key_exists('forcarInicializacaoWorkers', $arrParametros) && $arrParametros['forcarInicializacaoWorkers'][0] == true;
      $bolAtivaWorker = (is_null($numValorWorkers) || $numValorWorkers > 0) && ($this->foiIniciadoPeloTerminal() || $bolForcarInicializacaoWorkers);

      $objConfiguracaoModPEN = ConfiguracaoModPEN::getInstance();
      $arrObjGearman = $objConfiguracaoModPEN->getValor("PEN", "Gearman", false);
      $bolExecutarEmSegundoPlano = !empty(trim(@$arrObjGearman["Servidor"] ?: null));

      // Inicializa workers do Gearman caso este componente esteja configurado e não desativado no agendamento do sistema
      if ($bolAtivaWorker && $bolExecutarEmSegundoPlano) {
        ProcessarPendenciasRN::inicializarWorkers($numValorWorkers);
      }

      // Faz uma requisição para o controlador do sistema
      PendenciasEnvioTramiteRN::inicializarMonitoramentoEnvioPendencias($numValorWorkers, $bolMonitoramentoAtivado, $bolExecutarEmSegundoPlano, $bolDebugAtivo);
    } catch (Exception $e) {
      InfraDebug::getInstance()->setBolLigado(false);
      InfraDebug::getInstance()->setBolDebugInfra(false);
      InfraDebug::getInstance()->setBolEcho(false);

      throw new InfraException('Erro processando pendências de trâmites do Barramento de Serviços do PEN.', $e);
    }
  }

  private function foiIniciadoPeloTerminal()
    {
      return (!isset($_SERVER['SERVER_SOFTWARE']) && (php_sapi_name() == 'cli' || (is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0)));
  }
}
