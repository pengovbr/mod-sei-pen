<?php

require_once DIR_SEI_WEB.'/SEI.php';

class PENAgendamentoRN extends InfraRN
{
  protected function inicializarObjInfraIBanco() {
      return BancoSEI::getInstance();
  }

    /**
     * Atualiza��o das hip�teses legais vindas do barramento
     * @throws InfraException
     */

  protected function atualizarHipotesesLegaisControlado()
    {
    try {
        $objBD = new PenHipoteseLegalBD($this->inicializarObjInfraIBanco());
        $processoEletronicoRN = new ProcessoEletronicoRN();
        $hipotesesPen = $processoEletronicoRN->consultarHipotesesLegais();

      if(empty($hipotesesPen)){
        throw new InfraException('N�o foi poss�vel obter as hip�teses legais dos servi�os de integra��o');
      }

        //Para cada hip�tese vinda do PEN ser� verificado a existencia.
      foreach ($hipotesesPen->hipotesesLegais->hipotese as $hipotese) {

          $objDTO = new PenHipoteseLegalDTO();
          $objDTO->setNumIdentificacao($hipotese->identificacao);
          $objDTO->setNumMaxRegistrosRetorno(1);
          $objDTO->retStrNome();
          $objDTO->retNumIdHipoteseLegal();
          $objConsulta = $objBD->consultar($objDTO);

          //Caso n�o haja um nome para a hip�tese legal, ele pula para a pr�xima.
        if (empty($hipotese->nome)) {
          continue;
        }

          $objDTO->setStrNome(utf8_decode($hipotese->nome));

        if ($hipotese->status) {
            $objDTO->setStrAtivo('S');
        } else {
            $objDTO->setStrAtivo('N');
        }

          //Caso n�o exista a hip�tese ir� cadastra-la no sei.
        if (empty($objConsulta)) {

            $objBD->cadastrar($objDTO);
        } else {
            //Caso contr�rio apenas ir� atualizar os dados.
            $objDTO->setNumIdHipoteseLegal($objConsulta->getNumIdHipoteseLegal());
            $objBD->alterar($objDTO);
        }
      }

        LogSEI::getInstance()->gravar("Hip�teses Legais do PEN atualizadas com sucesso.", LogSEI::$INFORMACAO);
    } catch (Exception $e) {
        throw new InfraException('Erro no agendamento das Hip�teses Legais', $e);
    }
  }


    /**
     * Rotina de atualiza��o das esp�cies documentais do Barramento PEN na base de dados do SEI
     *
     * Durante a sincroniza��o, as esp�cies documentais podem ser cadastradas, removidas ou atualizadas
     *
     * @throws InfraException
     * @return void
     */
  protected function atualizarEspeciesDocumentaisControlado()
    {
    try{
        // Obt�m lista de esp�cies documentais do Barramento de Servi�os do PEN
        $processoEletronicoRN = new ProcessoEletronicoRN();
        $arrEspeciesDocumentaisPEN = $processoEletronicoRN->consultarEspeciesDocumentais();

        // Obt�m lista de esp�cies documentais registradas na base de dados do SEI
        $objGenericoBD = new GenericoBD(BancoSEI::getInstance());
        $objEspecieDocumentalDTO = new EspecieDocumentalDTO();
        $objEspecieDocumentalDTO->retDblIdEspecie();
        $objEspecieDocumentalDTO->retStrNomeEspecie();
        $arrIdsEspeciesDocumentaisSEI = InfraArray::converterArrInfraDTO($objGenericoBD->listar($objEspecieDocumentalDTO), "NomeEspecie", "IdEspecie");

        // Combina e percorre as duas listas para avaliar o que precisar ser feito: inserir, desativar ou alterar a descri��o
        $arrEspeciesDocumentaisCombinadas = array_replace($arrIdsEspeciesDocumentaisSEI, $arrEspeciesDocumentaisPEN);
      foreach ($arrEspeciesDocumentaisCombinadas as $numIdEspecie => $strNomeEspecie) {
        $numIdEspecie = intval($numIdEspecie);
        $bolExisteBaseDados = array_key_exists($numIdEspecie, $arrIdsEspeciesDocumentaisSEI);
        $bolExisteBarramento = array_key_exists($numIdEspecie, $arrEspeciesDocumentaisPEN);
        $bolNomesDiferentes = ($bolExisteBaseDados && $bolExisteBaseDados && $arrEspeciesDocumentaisPEN[$numIdEspecie] != $arrIdsEspeciesDocumentaisSEI[$numIdEspecie]);

        // Prepara consulta e atualiza��o da esp�cie documental
        $objEspecieDocumentalDTO = new EspecieDocumentalDTO();
        $objEspecieDocumentalDTO->setDblIdEspecie($numIdEspecie);

        if($bolExisteBarramento && !$bolExisteBaseDados){
            // Caso a esp�cie documental EXISTA no Barramento do PEN mas n�o exista no SEI, necess�rio fazer o seu cadastramento
          if ($objGenericoBD->contar($objEspecieDocumentalDTO) == 0) {
            $objEspecieDocumentalDTO->setDblIdEspecie($numIdEspecie);
            $objEspecieDocumentalDTO->setStrNomeEspecie($strNomeEspecie);
            $objGenericoBD->cadastrar($objEspecieDocumentalDTO);
          }
        } elseif(!$bolExisteBarramento && $bolExisteBaseDados){
            // Caso a esp�cie documental N�O exista no Barramento do PEN mas exista no SEI, necess�rio fazer a sua desativa��o
          if ($objGenericoBD->contar($objEspecieDocumentalDTO) > 0) {
                // Remove mapeamentos de Tipos de Documentos para Envio vinculados ao c�digo de esp�cie
                $objPenRelTipoDocMapEnviadoRN = new PenRelTipoDocMapEnviadoRN();
                $objPenRelTipoDocMapEnviadoRN->excluirPorEspecieDocumental($numIdEspecie);

                // Remove mapeamentos de Tipos de Documentos para Envio vinculados ao c�digo de esp�cie
                $objPenRelTipoDocMapRecebidoRN = new PenRelTipoDocMapRecebidoRN();
                $objPenRelTipoDocMapRecebidoRN->excluirPorEspecieDocumental($numIdEspecie);

                // Remove a esp�cie documental do PEN que n�o mais existe no Barramento do PEN
                $objEspecieDocumentalDTO->setDblIdEspecie($numIdEspecie);
                $objEspecieDocumentalDTO->setStrNomeEspecie($strNomeEspecie);
                $objGenericoBD->excluir($objEspecieDocumentalDTO);
          }
        } elseif($bolExisteBarramento && $bolExisteBaseDados && $bolNomesDiferentes) {
            // Caso a esp�cie documental exista no Barramento do PEN e no SEI mas com nomes diferentes, necess�rio atualizar descri��o
          if ($objGenericoBD->contar($objEspecieDocumentalDTO) > 0) {
                $objEspecieDocumentalDTO->setDblIdEspecie($numIdEspecie);
                $objEspecieDocumentalDTO->setStrNomeEspecie($strNomeEspecie);
                $objGenericoBD->alterar($objEspecieDocumentalDTO);
          }
        }
      }

        LogSEI::getInstance()->gravar("Esp�cies Documentais do PEN atualizadas com sucesso.", LogSEI::$INFORMACAO);
    } catch (Exception $e) {
        throw new InfraException('Erro no agendamento de atualiza��o de Hip�teses Legais', $e);
    }
  }

    /**
     * Atualiza��o de dados do Barramento de Servi�os do PEN para utiliza��o pelo SEI nas configura��es
     * @throws InfraException
     */
  protected function atualizarInformacoesPENControlado()
    {
      InfraDebug::getInstance()->setBolLigado(true);
      InfraDebug::getInstance()->setBolDebugInfra(false);
      InfraDebug::getInstance()->setBolEcho(false);
      InfraDebug::getInstance()->limpar();

    try {
        PENIntegracao::verificarCompatibilidadeConfiguracoes();

        $this->atualizarHipotesesLegais();
        $this->atualizarEspeciesDocumentais();

        LogSEI::getInstance()->gravar("Esp�cies Documentais para envio mapeadas com sucesso", LogSEI::$INFORMACAO);
        $objPenRelTipoDocMapEnviadoRN = new PenRelTipoDocMapEnviadoRN();
        $objPenRelTipoDocMapEnviadoRN->mapearEspeciesDocumentaisEnvio();

        LogSEI::getInstance()->gravar("Esp�cies Documentais para recebimento mapeadas com sucesso", LogSEI::$INFORMACAO);
        $objPenRelTipoDocMapRecebidoRN = new PenRelTipoDocMapRecebidoRN();
        $objPenRelTipoDocMapRecebidoRN->mapearEspeciesDocumentaisRecebimento();
    }catch(Exception $e){
        InfraDebug::getInstance()->setBolLigado(false);
        InfraDebug::getInstance()->setBolDebugInfra(false);
        InfraDebug::getInstance()->setBolEcho(false);

        throw new InfraException('Erro processamento atualiza��o de informa��es do Barramento de Servi�os do PEN.', $e);
    }
  }

    /**
     * Processa tarefas recebidas pelo Barramento de Servi�os do PEN para receber novos processos/documentos,
     * notifica��es de conclus�o de tr�mites ou notifica��o de recusa de processos
     *
     * @return void
     */
  public function processarTarefasPEN($arrParametros)
    {
      InfraDebug::getInstance()->setBolLigado(true);
      InfraDebug::getInstance()->setBolDebugInfra(false);
      InfraDebug::getInstance()->setBolEcho(false);
      InfraDebug::getInstance()->limpar();
                
    try {
        PENIntegracao::verificarCompatibilidadeConfiguracoes();

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

        // Inicializa workers do Gearman caso este componente esteja configurado e n�o desativado no agendamento do sistema
      if($bolAtivaWorker && $bolExecutarEmSegundoPlano){
        ProcessarPendenciasRN::inicializarWorkers($numValorWorkers);
      }

        // Faz uma requisi��o para o controlador do sistema
        PendenciasTramiteRN::inicializarMonitoramentoPendencias($numValorWorkers, $bolMonitoramentoAtivado, $bolExecutarEmSegundoPlano, $bolDebugAtivo);

    }catch(Exception $e){
        InfraDebug::getInstance()->setBolLigado(false);
        InfraDebug::getInstance()->setBolDebugInfra(false);
        InfraDebug::getInstance()->setBolEcho(false);

        throw new InfraException('Erro processando pend�ncias de tr�mites do Barramento de Servi�os do PEN.', $e);
    }
  }


  private function foiIniciadoPeloTerminal()
    {
      return (!isset($_SERVER['SERVER_SOFTWARE']) && (php_sapi_name() == 'cli' || (is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0)));
  }
}
