<?php

require_once DIR_SEI_WEB . '/SEI.php';

class SincronizacaoExpedirProcedimentoRN extends ExpedirProcedimentoRN
{
  public function __construct()
  {
    parent::__construct();
  }

  protected function sincronizarControlado(ExpedirProcedimentoDTO $objExpedirProcedimentoDTO)
  {
    $numIdTramite = 0;
    try {
      //Valida Permissão
      SessaoSEI::getInstance()->validarAuditarPermissao('pen_procedimento_expedir', __METHOD__, $objExpedirProcedimentoDTO);
      $dblIdProcedimento = $objExpedirProcedimentoDTO->getDblIdProcedimento();

      $this->barraProgresso->exibir();
      $this->barraProgresso->setStrRotulo(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_VALIDACAO);

      $objInfraException = new InfraException();
      //Carregamento dos dados de processo e documento para validação e envio externo
      $objProcedimentoDTO = $this->consultarProcedimento($dblIdProcedimento);
      $objProcedimentoDTO->setArrObjDocumentoDTO($this->listarDocumentos($dblIdProcedimento));
      $objProcedimentoDTO->setArrObjParticipanteDTO($this->listarInteressados($dblIdProcedimento));
      $this->validarPreCondicoesExpedirProcedimento($objInfraException, $objProcedimentoDTO, null, false);
      $this->validarParametrosExpedicao($objInfraException, $objExpedirProcedimentoDTO);

      //Apresentao da mensagens de validao na janela da barra de progresso
      if ($objInfraException->contemValidacoes()) {
        $this->barraProgresso->mover(0);
        $this->barraProgresso->setStrRotulo('Erro durante validação dos dados do processo.');
        $objInfraException->lancarValidacoes();
      }

      //Busca metadados do processo registrado em trâmite anterior
      $objMetadadosProcessoTramiteAnterior = $this->consultarMetadadosPEN($dblIdProcedimento);

      $objProcessoEletronicoRN = new ProcessoEletronicoRN();
      $objProcessoEletronicoRN->gravarAtividadeMuiltiplosOrgaos($objProcedimentoDTO, $objMetadadosProcessoTramiteAnterior->IDT, ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MULTIPLOS_ORGAOS);

      // Solicitar sincronização do documentos pendentes
      $numIdTramite = $this->objProcessoEletronicoRN->solicitarSincronizarTramite($objMetadadosProcessoTramiteAnterior->IDT);

      $this->bloquearProcedimentoExpedicao($objExpedirProcedimentoDTO, $dblIdProcedimento);

      $this->gravarLogDebug("Solicitação de sincronização de trâmite para o processo {$objMetadadosProcessoTramiteAnterior->IDT} foi realizada.", 0, true);
      $this->barraProgresso->mover($this->barraProgresso->getNumMax());
      $this->barraProgresso->setStrRotulo(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_CONCLUSAO);

      return $numIdTramite;
    } catch (\Exception $e) {
      $this->gravarLogDebug("Erro processando envio de processo: $e", 0, true);
      throw new InfraException('Módulo do Tramita: Falha de comunicação com o serviços de integração. Por favor, tente novamente mais tarde.', $e);
    }
  }

  protected function expedirAutoControlado(ExpedirProcedimentoDTO $objExpedirProcedimentoDTO)
  {
    $numIdTramite = 0;
    try {
      //Valida Permissão
      SessaoSEI::getInstance()->validarAuditarPermissao('pen_procedimento_expedir', __METHOD__, $objExpedirProcedimentoDTO);
      $dblIdProcedimento = $objExpedirProcedimentoDTO->getDblIdProcedimento();

      $objPenBlocoProcessoRN = new PenBlocoProcessoRN();
      $bolSinProcessamentoEmBloco = $objExpedirProcedimentoDTO->getBolSinProcessamentoEmBloco();
      $numIdBloco = $objExpedirProcedimentoDTO->getNumIdBloco();
      $numIdAtividade = $objExpedirProcedimentoDTO->getNumIdAtividade();
      $numIdUnidade = $objExpedirProcedimentoDTO->getNumIdUnidade();

      $objInfraException = new InfraException();
      //Carregamento dos dados de processo e documento para validação e envio externo
      $objProcedimentoDTO = $this->consultarProcedimento($dblIdProcedimento);
      $objProcedimentoDTO->setArrObjDocumentoDTO($this->listarDocumentos($dblIdProcedimento));
      $objProcedimentoDTO->setArrObjParticipanteDTO($this->listarInteressados($dblIdProcedimento));
      $this->validarPreCondicoesExpedirProcedimento($objInfraException, $objProcedimentoDTO, null, $bolSinProcessamentoEmBloco);
      $this->validarParametrosExpedicao($objInfraException, $objExpedirProcedimentoDTO);

      //Apresentao da mensagens de validao na janela da barra de progresso
      if ($objInfraException->contemValidacoes() && $objExpedirProcedimentoDTO->getBolSinEnvioAutoMultiplosOrgaos() === false) {
        if (!$bolSinProcessamentoEmBloco) {
          $objInfraException->lancarValidacoes();
        }
      }

      ModPenUtilsRN::simularLoginUnidadeRecebimento();

      //Busca metadados do processo registrado em trâmite anterior
      $objMetadadosProcessoTramiteAnterior = $this->consultarMetadadosPEN($dblIdProcedimento);

      //Construção do cabeçalho para envio do processo
      $objProcessoEletronicoPesquisaDTO = new ProcessoEletronicoDTO();
      $objProcessoEletronicoPesquisaDTO->setDblIdProcedimento($dblIdProcedimento);
      $objUltimoTramiteRecebidoDTO = $this->objProcessoEletronicoRN->consultarUltimoTramiteRecebido($objProcessoEletronicoPesquisaDTO);

      if (isset($objMetadadosProcessoTramiteAnterior->documento)) {
        $strNumeroRegistro = null;
      } else {
        $strNumeroRegistro = isset($objUltimoTramiteRecebidoDTO) ? $objUltimoTramiteRecebidoDTO->getStrNumeroRegistro() : $objMetadadosProcessoTramiteAnterior?->NRE;
      }

      $objCabecalho = $this->construirCabecalho($objExpedirProcedimentoDTO, $strNumeroRegistro, $dblIdProcedimento);

      //Construção do processo para envio
      $arrProcesso = $this->construirProcessoREST($dblIdProcedimento, $objExpedirProcedimentoDTO->getArrIdProcessoApensado(), $objMetadadosProcessoTramiteAnterior);

      //Cancela trâmite anterior caso este esteja travado em status inconsistente 1 - STA_SITUACAO_TRAMITE_INICIADO
      $objTramitesAnteriores = $this->consultarTramitesAnteriores($strNumeroRegistro);
      if ($objTramiteInconsistente = $this->necessitaCancelamentoTramiteAnterior($objTramitesAnteriores)) {
        $this->objProcessoEletronicoRN->cancelarTramite($objTramiteInconsistente->IDT);
      }

      $param = [
        'novoTramiteDeProcesso' => [
          'cabecalho' => $objCabecalho,
          'processo' => $arrProcesso
        ],
        'dblIdProcedimento' => $dblIdProcedimento
      ];

      $novoTramite = $this->objProcessoEletronicoRN->enviarProcessoREST($param);

      $numIdTramite = $novoTramite->IDT;
      $this->lancarEventoEnvioMetadados($numIdTramite);

      $this->atualizarPenProtocolo($dblIdProcedimento);

      if (isset($novoTramite)) {
        $objTramite = $novoTramite;
        $this->objProcedimentoAndamentoRN->setOpts($objTramite->NRE, $objTramite->IDT, ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO), $dblIdProcedimento);

        try {
          $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento('Envio do metadados do processo', 'S'));

          // $idAtividadeExpedicao = $this->bloquearProcedimentoExpedicao($objExpedirProcedimentoDTO, $arrProcesso['idProcedimentoSEI']);

          $this->objProcessoEletronicoRN->cadastrarTramiteDeProcesso(
            $arrProcesso['idProcedimentoSEI'],
            $objTramite->NRE,
            $objTramite->IDT,
            ProcessoEletronicoRN::$STA_TIPO_TRAMITE_ENVIO,
            $objTramite->dataHoraDeRegistroDoTramite,
            $objExpedirProcedimentoDTO->getNumIdRepositorioOrigem(),
            $objExpedirProcedimentoDTO->getNumIdUnidadeOrigem(),
            $objExpedirProcedimentoDTO->getNumIdRepositorioDestino(),
            $objExpedirProcedimentoDTO->getNumIdUnidadeDestino(),
            $arrProcesso,
            $objTramite->ticketParaEnvioDeComponentesDigitais,
            $objTramite->processosComComponentesDigitaisSolicitados,
            $bolSinProcessamentoEmBloco,
            $numIdUnidade
          );

          $this->objProcessoEletronicoRN->cadastrarTramitePendente($objTramite->IDT, $idAtividadeExpedicao);

          $this->enviarComponentesDigitais($objTramite->NRE, $objTramite->IDT, $arrProcesso['protocolo'], $bolSinProcessamentoEmBloco);

          $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento('Concluído envio dos componentes do processo', 'S'));

          $this->receberReciboDeEnvio($objTramite->IDT);

          try {
            $objProcessoEletronicoRN = new ProcessoEletronicoRN();
            if ($objProcessoEletronicoRN->validarProcessoMultiplosOrgaos($objProcedimentoDTO->getDblIdProcedimento())) {
              $idTarefa = ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_ENVIO_MULTIPLOS_ORGAOS_REMETENTE);

              $objAtividadeDTO = new AtividadeDTO();
              $objAtividadeDTO->setDblIdProtocolo($dblIdProcedimento);
              $objAtividadeDTO->setDthConclusao(null);
              $objAtividadeDTO->setDistinct(true);
              $objAtividadeDTO->setNumIdTarefa($idTarefa);
              $objAtividadeDTO->setNumMaxRegistrosRetorno(1);
              $objAtividadeDTO->retTodos();

              $objAtividadeRN = new AtividadeRN();
              $objAtividadeDTO = $objAtividadeRN->consultarRN0033($objAtividadeDTO);

              if($objAtividadeDTO) {
                $objAtividadeRN = new AtividadeRN();
                $objAtividadeRN->concluirRN0726([$objAtividadeDTO]);
              }

              $objProcessoEletronicoRN->gravarAtividadeMuiltiplosOrgaos($objProcedimentoDTO, $objTramite->IDT, ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_ENVIO_MULTIPLOS_ORGAOS_REMETENTE);
            }
          } catch (\Exception $e) {
            $this->gravarLogDebug("Erro ao gravar atividade múltiplos órgãos: $e", 0, true);
          }

          $this->gravarLogDebug(sprintf('Trâmite do processo %s foi concluído', $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado()), 2);

          $this->gravarLogDebug("Finalizado o envio de protocolo número " . $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado(), 0, true);
        } catch (\Exception $e) {
          //Realiza o cancelamento do tramite
          try {
            if ($numIdTramite != 0) {
              $this->objProcessoEletronicoRN->cancelarTramite($numIdTramite);
            }
          } catch (InfraException) {
          }

          $this->registrarAndamentoExpedicaoAbortada($arrProcesso['idProcedimentoSEI']);

          $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento('Concluído envio dos componentes do processo', 'N'));
          throw $e;
        }
      }
    } catch (\Exception $e) {
      $this->gravarLogDebug("Erro processando envio de processo: $e", 0, true);
      throw new InfraException('Módulo do Tramita: Falha de comunicação com o serviços de integração. Por favor, tente novamente mais tarde.', $e);
    }
  }

  protected function expedirSincronizarControlado(ExpedirProcedimentoDTO $objExpedirProcedimentoDTO)
  {
    $numIdTramite = 0;
    $numTempoInicialRecebimento = microtime(true);
    try {
      //Valida Permissão
      SessaoSEI::getInstance()->validarAuditarPermissao('pen_procedimento_expedir', __METHOD__, $objExpedirProcedimentoDTO);
      $dblIdProcedimento = $objExpedirProcedimentoDTO->getDblIdProcedimento();

      $objPenBlocoProcessoRN = new PenBlocoProcessoRN();
      $numIdAtividade = $objExpedirProcedimentoDTO->getNumIdAtividade();
      $numIdUnidade = $objExpedirProcedimentoDTO->getNumIdUnidade();

      $objInfraException = new InfraException();
      //Carregamento dos dados de processo e documento para validação e envio externo
      $objProcedimentoDTO = $this->consultarProcedimento($dblIdProcedimento);
      $objProcedimentoDTO->setArrObjDocumentoDTO($this->listarDocumentos($dblIdProcedimento));
      $objProcedimentoDTO->setArrObjParticipanteDTO($this->listarInteressados($dblIdProcedimento));
      
      $this->objProcessoEletronicoRN->cadastrarAtividadePedidoSincronizacao($objProcedimentoDTO, ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MULTIPLOS_ORGAOS_RECEBIDO);

      //Busca metadados do processo registrado em trâmite anterior
      $objMetadadosProcessoTramiteAnterior = $objExpedirProcedimentoDTO->getObjMetadadosProcedimento();

      //Construção do cabeçalho para envio do processo
      $objProcessoEletronicoPesquisaDTO = new ProcessoEletronicoDTO();
      $objProcessoEletronicoPesquisaDTO->setDblIdProcedimento($dblIdProcedimento);
      $objUltimoTramiteRecebidoDTO = $this->objProcessoEletronicoRN->consultarUltimoTramiteRecebido($objProcessoEletronicoPesquisaDTO);

      if (isset($objMetadadosProcessoTramiteAnterior->documento)) {
        $strNumeroRegistro = null;
      } else {
        $strNumeroRegistro = isset($objUltimoTramiteRecebidoDTO) ? $objUltimoTramiteRecebidoDTO->getStrNumeroRegistro() : $objMetadadosProcessoTramiteAnterior?->NRE;
      }

      $objCabecalho = $this->construirCabecalho($objExpedirProcedimentoDTO, $strNumeroRegistro, $dblIdProcedimento);
      //Construção do processo para envio
      $arrProcesso = $this->construirProcessoREST($dblIdProcedimento, $objExpedirProcedimentoDTO->getArrIdProcessoApensado(), $objMetadadosProcessoTramiteAnterior);

      //Cancela trâmite anterior caso este esteja travado em status inconsistente 1 - STA_SITUACAO_TRAMITE_INICIADO
      $objTramitesAnteriores = $this->consultarTramitesAnteriores($strNumeroRegistro);
      if ($objTramiteInconsistente = $this->necessitaCancelamentoTramiteAnterior($objTramitesAnteriores)) {
        $this->objProcessoEletronicoRN->cancelarTramite($objTramiteInconsistente->IDT);
      }

      $param = [
        'novoTramiteDeProcesso' => [
          'cabecalho' => $objCabecalho,
          'processo' => $arrProcesso
        ],
        'dblIdProcedimento' => $dblIdProcedimento
      ];

      $novoTramite = $this->objProcessoEletronicoRN->enviarProcessoREST($param);

      $numIdTramite = $novoTramite->IDT;
      $this->lancarEventoEnvioMetadados($numIdTramite);

      $this->atualizarPenProtocolo($dblIdProcedimento);

      if (isset($novoTramite)) {
        $objTramite = $novoTramite;
        $this->objProcedimentoAndamentoRN->setOpts($objTramite->NRE, $objTramite->IDT, ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO), $dblIdProcedimento);

        try {
          $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento('Envio do metadados do processo', 'S'));

          $this->objProcessoEletronicoRN->cadastrarTramiteDeProcesso(
            $arrProcesso['idProcedimentoSEI'],
            $objTramite->NRE,
            $objTramite->IDT,
            ProcessoEletronicoRN::$STA_TIPO_TRAMITE_ENVIO,
            $objTramite->dataHoraDeRegistroDoTramite,
            $objExpedirProcedimentoDTO->getNumIdRepositorioOrigem(),
            $objExpedirProcedimentoDTO->getNumIdUnidadeOrigem(),
            $objExpedirProcedimentoDTO->getNumIdRepositorioDestino(),
            $objExpedirProcedimentoDTO->getNumIdUnidadeDestino(),
            $arrProcesso,
            $objTramite->ticketParaEnvioDeComponentesDigitais,
            $objTramite->processosComComponentesDigitaisSolicitados,
            false,
            $numIdUnidade
          );


          $this->objProcessoEletronicoRN->cadastrarTramitePendente($objTramite->IDT, $idAtividadeExpedicao);

          $this->enviarComponentesDigitais($objTramite->NRE, $objTramite->IDT, $arrProcesso['protocolo'], false);

          $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento('Concluído envio dos componentes do processo', 'S'));

          $this->receberReciboDeEnvio($objTramite->IDT);

          $this->gravarLogDebug(sprintf('Trâmite do processo %s foi concluído', $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado()), 2);

          $numTempoTotalRecebimento = round(microtime(true) - $numTempoInicialRecebimento, 2);
          $this->gravarLogDebug("Finalizado o envio de protocolo número " . $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado() . " (Tempo total: {$numTempoTotalRecebimento}s)", 0, true);
        } catch (\Exception $e) {
          //Realiza o cancelamento do tramite
          try {
            if ($numIdTramite != 0) {
              $this->objProcessoEletronicoRN->cancelarTramite($numIdTramite);
            }
          } catch (InfraException) {
          }

          $this->registrarAndamentoExpedicaoAbortada($arrProcesso['idProcedimentoSEI']);

          $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento('Concluído envio dos componentes do processo', 'N'));
          throw $e;
        }
      }
    } catch (\Exception $e) {
      $this->gravarLogDebug("Erro processando envio de processo: $e", 0, true);
      throw new InfraException('Módulo do Tramita: Falha de comunicação com o serviços de integração. Por favor, tente novamente mais tarde.', $e);
    }
  }

  /**
   * Processa a mensagem de pendência de Envio de Componentes Digitais
   *
   * @param  object $idTramite Contexto com informações para processamento da tarefa
   * @return void
   */
  public function enviarSincronizacaoTramite($idTramite)
  {
    $this->gravarLogDebug("Processando envio de sincronização de tramite [enviarSincronizacaoTramite] com IDT $idTramite", 0, true);
    $numTempoInicialEnvio = microtime(true);

    $objProcessoEletronicoRN =  new ProcessoEletronicoRN();
    $objMetadadosProcedimento = $objProcessoEletronicoRN->solicitarMetadados($idTramite);

    $protocolo = $objMetadadosProcedimento->metadados->processo->protocolo;
    $remetente = $objMetadadosProcedimento->remetente;
    $destinatario = $objMetadadosProcedimento->destinatario;

    try {
      $objProtocoloDTO = new ProtocoloDTO();
      $objProtocoloDTO->setStrProtocoloFormatado($protocolo);
      $objProtocoloDTO->retTodos();

      $objProtocoloRN = new ProtocoloRN();
      $objProtocoloDTO = $objProtocoloRN->consultarRN0186($objProtocoloDTO);

      $objExpedirProcedimentoDTO = new ExpedirProcedimentoDTO();
      $objExpedirProcedimentoDTO->setNumIdRepositorioOrigem($remetente->identificacaoDoRepositorioDeEstruturas);
      $objExpedirProcedimentoDTO->setNumIdUnidadeOrigem($remetente->numeroDeIdentificacaoDaEstrutura);

      $objExpedirProcedimentoDTO->setNumIdRepositorioDestino($destinatario->identificacaoDoRepositorioDeEstruturas);
      $objExpedirProcedimentoDTO->setNumIdUnidadeDestino($destinatario->numeroDeIdentificacaoDaEstrutura);
      $objExpedirProcedimentoDTO->setArrIdProcessoApensado(null);
      $objExpedirProcedimentoDTO->setBolSinUrgente(false);
      $objExpedirProcedimentoDTO->setDblIdProcedimento($objProtocoloDTO->getDblIdProtocolo());
      $objExpedirProcedimentoDTO->setNumIdMotivoUrgencia(null);
      $objExpedirProcedimentoDTO->setNumIdBloco(null);
      $objExpedirProcedimentoDTO->setNumIdAtividade(null);
      $objExpedirProcedimentoDTO->setBolSinProcessamentoEmBloco(false);
      $objExpedirProcedimentoDTO->setNumIdUnidade($objProtocoloDTO->getNumIdUnidadeGeradora());
      $objExpedirProcedimentoDTO->setBolSinMultiplosOrgaos(true);
      $objExpedirProcedimentoDTO->setObjMetadadosProcedimento($objMetadadosProcedimento);
      $objExpedirProcedimentoDTO->setBolSinEnvioAutoMultiplosOrgaos(false);

      $this->expedirSincronizar($objExpedirProcedimentoDTO);

      $numIDT = $objProtocoloDTO->getDblIdProtocolo();
      $numTempoTotalEnvio = round(microtime(true) - $numTempoInicialEnvio, 2);
      $this->gravarLogDebug("Finalizado o envio de protocolo com IDProcedimento $numIDT(Tempo total: {$numTempoTotalEnvio}s)", 0, true);
    } catch (\Exception $e) {
      $this->gravarLogDebug("Erro processando envio de sincronização de tramite: $e", 0, true);
      throw new InfraException('Módulo do Tramita: Falha de comunicação com o serviços de integração. Por favor, tente novamente mais tarde.', $e);
    }
  }
}
