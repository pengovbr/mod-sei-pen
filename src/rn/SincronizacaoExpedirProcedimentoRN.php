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

      if ($this->objProcessoEletronicoRN->possuiDocumentoInternoNaoAssinado($dblIdProcedimento)) {
        throw new InfraException('Módulo do Tramita: Sincronização não permitida para processos com documentos internos não assinados.');
      }

      // Solicitar sincronização do documentos pendentes
      $numIdTramite = $this->objProcessoEletronicoRN->solicitarSincronizarTramite($objMetadadosProcessoTramiteAnterior->IDT);
      
      $objProcessoEletronicoRN = new ProcessoEletronicoRN();
      $objProcessoEletronicoRN->bloquearProcesso($dblIdProcedimento);
      $objProcessoEletronicoRN->gravarAtividadeMultiplosOrgaos($objProcedimentoDTO, $objMetadadosProcessoTramiteAnterior->IDT, ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MULTIPLOS_ORGAOS);

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
    $dblIdProcedimento = null;
    try {
      //Valida Permissão
      SessaoSEI::getInstance()->validarAuditarPermissao('pen_procedimento_expedir', __METHOD__, $objExpedirProcedimentoDTO);
      $dblIdProcedimento = $objExpedirProcedimentoDTO->getDblIdProcedimento();

      $numIdUnidade = $objExpedirProcedimentoDTO->getNumIdUnidade();

      $objInfraException = new InfraException();
      //Carregamento dos dados de processo e documento para validação e envio externo
      $objProcedimentoDTO = $this->consultarProcedimento($dblIdProcedimento);
      $objProcedimentoDTO->setArrObjDocumentoDTO($this->listarDocumentos($dblIdProcedimento));
      $objProcedimentoDTO->setArrObjParticipanteDTO($this->listarInteressados($dblIdProcedimento));
      $this->validarPreCondicoesExpedirProcedimento($objInfraException, $objProcedimentoDTO, null, false);
      $this->validarParametrosExpedicao($objInfraException, $objExpedirProcedimentoDTO);

      //Apresentao da mensagens de validao na janela da barra de progresso
      if ($objInfraException->contemValidacoes() && $objExpedirProcedimentoDTO->getBolSinEnvioAutoMultiplosOrgaos() === false) {
        $objInfraException->lancarValidacoes();
      }

      if ($this->objProcessoEletronicoRN->possuiDocumentoInternoNaoAssinado($dblIdProcedimento)) {
        throw new InfraException('Módulo do Tramita: Envio não permitido para processos com documentos internos não assinados.');
      }

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
            false,
            $numIdUnidade
          );

          $this->objProcessoEletronicoRN->cadastrarTramitePendente($objTramite->IDT, $idAtividadeExpedicao);

          $this->enviarComponentesDigitais($objTramite->NRE, $objTramite->IDT, $arrProcesso['protocolo'], false, false, true);

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

              $objProcessoEletronicoRN->gravarAtividadeMultiplosOrgaos($objProcedimentoDTO, $objTramite->IDT, ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_ENVIO_MULTIPLOS_ORGAOS_REMETENTE);
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
    } finally {
      if (!is_null($dblIdProcedimento)) {
        $numIdUnidadeSessaoOriginal = SessaoSEI::getInstance()->getNumIdUnidadeAtual();
        $numIdUnidadeDesbloqueio = null;

        try {
          $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
          $objProcessoEletronicoDTO->setDblIdProcedimento($dblIdProcedimento);
          $objTramiteBD = new TramiteBD($this->getObjInfraIBanco());
          $objTramiteDTO = $objTramiteBD->consultarPrimeiroTramite($objProcessoEletronicoDTO, ProcessoEletronicoRN::$STA_TIPO_TRAMITE_RECEBIMENTO);

          if (!is_null($objTramiteDTO)) {
            $objUnidadeDTO = new PenUnidadeDTO();
            $objUnidadeDTO->setNumIdUnidadeRH($objTramiteDTO->getNumIdEstruturaDestino());
            $objUnidadeDTO->setStrSinAtivo('S');
            $objUnidadeDTO->retNumIdUnidade();

            $objUnidadeRN = new UnidadeRN();
            $objUnidadeDTO = $objUnidadeRN->consultarRN0125($objUnidadeDTO);

            if (!is_null($objUnidadeDTO)) {
              $numIdUnidadeDesbloqueio = $objUnidadeDTO->getNumIdUnidade();
            }
          }
        } catch (\Exception $e) {
          $this->gravarLogDebug("Erro ao identificar unidade para desbloqueio automatico do processo $dblIdProcedimento: $e", 2, true);
        }

        if (is_null($numIdUnidadeDesbloqueio)) {
          try {
            $numIdUnidadeDesbloqueio = ProcessoEletronicoRN::obterUnidadeParaRegistroDocumento($dblIdProcedimento);
          } catch (\Exception $e) {
            $this->gravarLogDebug("Erro ao obter unidade fallback para desbloqueio automatico do processo $dblIdProcedimento: $e", 2, true);
          }
        }

        try {
          if (!is_null($numIdUnidadeDesbloqueio)) {
            SessaoSEI::getInstance()->setNumIdUnidadeAtual($numIdUnidadeDesbloqueio);
          }

          ProcessoEletronicoRN::desbloquearProcesso($dblIdProcedimento);
        } catch (\Exception $e) {
          $this->gravarLogDebug("Processo $dblIdProcedimento nao pode ser desbloqueado automaticamente", 2);
        } finally {
          try {
            SessaoSEI::getInstance()->setNumIdUnidadeAtual($numIdUnidadeSessaoOriginal);
          } catch (\Exception $e) {
            $this->gravarLogDebug("Nao foi possivel restaurar a unidade original apos tentativa de desbloqueio do processo $dblIdProcedimento: $e", 2, true);
          }
        }
      }
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

      $numIdUnidade = $objExpedirProcedimentoDTO->getNumIdUnidade();

      //Carregamento dos dados de processo e documento para validação e envio externo
      $objProcedimentoDTO = $this->consultarProcedimento($dblIdProcedimento);
      $objProcedimentoDTO->setArrObjDocumentoDTO($this->listarDocumentos($dblIdProcedimento));
      $objProcedimentoDTO->setArrObjParticipanteDTO($this->listarInteressados($dblIdProcedimento));
      
      $this->validarSincronizacaoProcessoSigiloso($dblIdProcedimento);
      
      if ($this->objProcessoEletronicoRN->possuiDocumentoInternoNaoAssinado($dblIdProcedimento)) {
        throw new InfraException('Módulo do Tramita: Sincronização não permitida para processos com documentos internos não assinados.');
      }

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
      $arrProcesso = $this->construirProcessoREST($dblIdProcedimento, $objExpedirProcedimentoDTO->getArrIdProcessoApensado(), $objMetadadosProcessoTramiteAnterior, true);

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

          $this->enviarComponentesDigitaisSincronizar($objTramite->NRE, $objTramite->IDT, $arrProcesso['protocolo']);

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
    $nre = $objMetadadosProcedimento->NRE;
    $this->gravarLogDebug("NRE do tramite realizado: $nre", 0, true);

    $protocolo = $objMetadadosProcedimento->metadados->processo->protocolo;
    $remetente = $objMetadadosProcedimento->remetente;
    $destinatario = $objMetadadosProcedimento->destinatario;

    try {
      $objProtocoloDTO = new ProtocoloDTO();
      $objProtocoloDTO->setStrProtocoloFormatado($protocolo);
      $objProtocoloDTO->retDblIdProtocolo();
      $objProtocoloDTO->retNumIdUnidadeGeradora();

      $objProtocoloRN = new ProtocoloRN();
      $objProtocoloDTO = $objProtocoloRN->consultarRN0186($objProtocoloDTO);
      if (!empty($objProtocoloDTO)){
        $objProcedimentoDTO = $this->consultarProcedimento($objProtocoloDTO->getDblIdProtocolo());
        $numIdUnidadeProcesso = ProcessoEletronicoRN::obterUnidadeParaRegistroDocumento($objProtocoloDTO->getDblIdProtocolo());

        $objProcessoEletronicoRN->cadastrarAtividadePedidoSincronizacao($objProcedimentoDTO, ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MULTIPLOS_ORGAOS_RECEBIDO);

        $dblIdProcedimento = $objProtocoloDTO->getDblIdProtocolo();
        $objInfraException = new InfraException();
        $objExpedirProcedimentosRN = new ExpedirProcedimentoRN();
        $objProcedimentoDTO->setArrObjDocumentoDTO($objExpedirProcedimentosRN->listarDocumentos($dblIdProcedimento));
        $objProcedimentoDTO->setArrObjParticipanteDTO($objExpedirProcedimentosRN->listarInteressados($dblIdProcedimento));
        $objExpedirProcedimentosRN->validarPreCondicoesSincronizarProcedimento($objInfraException, $objProcedimentoDTO, $protocolo);

        if ($objInfraException->contemValidacoes()) {
          $strValidacoes = rtrim($this->trazerTextoSeContemValidacoes($objInfraException));
          $strMensagemErro = "";

          if ($strValidacoes !== '') {
            $strMensagemErro .= mb_convert_encoding($strValidacoes, 'ISO-8859-1', 'UTF-8');
          }

          $cabecalhoMsg = "Tramitação externa do processo $protocolo cancelada. ";
          LogSEI::getInstance()->gravar($cabecalhoMsg . $strMensagemErro, InfraLog::$ERRO);
          $this->gravarLogDebug($cabecalhoMsg . $strMensagemErro, 0, true);

          $this->concluirAtividadePendenteSincronizacao($objProcedimentoDTO->getDblIdProcedimento());

          $objProcessoEletronicoRN->gravarAtividadeMultiplosOrgaos(
            $objProcedimentoDTO,
            $idTramite,
            ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_SINC_MULTIPLOS_ORGAOS_CANCELADO_ORIGEM,
            $numIdUnidadeProcesso,
            $strMensagemErro
          );

          $objProcessoEletronicoRN->cancelarTramite($idTramite);
          return;
        }

        if ($objProcessoEletronicoRN->possuiDocumentoInternoNaoAssinado($objProtocoloDTO->getDblIdProtocolo())) {
          $this->concluirAtividadePendenteSincronizacao($objProcedimentoDTO->getDblIdProcedimento());

          $objProcessoEletronicoRN->cancelarTramite($idTramite);
          $objProcessoEletronicoRN->gravarAtividadeMultiplosOrgaos(
            $objProcedimentoDTO,
            $idTramite,
            ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_SINC_MULTIPLOS_ORGAOS_CANCELADO_ORIGEM,
            $numIdUnidadeProcesso
          );

          $strMensagemErro = "Tramitação externa do processo $protocolo cancelada. Não é possível sincronizar processos com documentos internos gerados e não assinados.";
          LogSEI::getInstance()->gravar($strMensagemErro, InfraLog::$ERRO);
          $this->gravarLogDebug($strMensagemErro, 0, true);
          return;
        }

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
        $objExpedirProcedimentoDTO->setNumIdUnidade($numIdUnidadeProcesso);
        $objExpedirProcedimentoDTO->setBolSinMultiplosOrgaos(true);
        $objExpedirProcedimentoDTO->setObjMetadadosProcedimento($objMetadadosProcedimento);
        $objExpedirProcedimentoDTO->setBolSinEnvioAutoMultiplosOrgaos(false);

        $numIdUnidadeSessaoAtual = SessaoSEI::getInstance()->getNumIdUnidadeAtual();
        SessaoSEI::getInstance()->setNumIdUnidadeAtual($numIdUnidadeProcesso);
        try {
          $this->expedirSincronizar($objExpedirProcedimentoDTO);
        } finally {
          SessaoSEI::getInstance()->setNumIdUnidadeAtual($numIdUnidadeSessaoAtual);
        }

        $numIDT = $objProtocoloDTO->getDblIdProtocolo();
        $numTempoTotalEnvio = round(microtime(true) - $numTempoInicialEnvio, 2);
        $this->gravarLogDebug("Finalizado o envio de protocolo com IDProcedimento $numIDT(Tempo total: {$numTempoTotalEnvio}s)", 0, true);
        $objProcessoEletronicoRN->cadastrarAtividadePedidoSincronizacao($objProcedimentoDTO, ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MULTIPLOS_ORGAOS_SUCESSO);
        $this->concluirAtividadePendenteSincronizacao($objProcedimentoDTO->getDblIdProcedimento());
      } else {
        $objProcessoEletronicoRN->cancelarTramite($idTramite);
        $this->gravarLogDebug("Sincronismo do $protocolo: Protocolo não encontrado. Tramite $idTramite cancelado", 0, true);
      }
    } catch (\Exception $e) {
      if (!empty($objProtocoloDTO) && $objProtocoloDTO->getDblIdProtocolo()) {
        $this->reabrirProcessoSeBloqueado($objProtocoloDTO->getDblIdProtocolo());

        try {
          $objProcedimentoDTO = $this->consultarProcedimento($objProtocoloDTO->getDblIdProtocolo());
          $numIdUnidadeProcesso = ProcessoEletronicoRN::obterUnidadeParaRegistroDocumento($objProtocoloDTO->getDblIdProtocolo());

          $this->concluirAtividadePendenteSincronizacao($objProcedimentoDTO->getDblIdProcedimento());

          $objProcessoEletronicoRN->gravarAtividadeMultiplosOrgaos(
            $objProcedimentoDTO,
            $idTramite,
            ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_SINC_MULTIPLOS_ORGAOS_RECUSA,
            $numIdUnidadeProcesso,
            'Erro no processamento da sincronização: ' . $e->getMessage()
          );
        } catch (\Exception $ex) {
          $this->gravarLogDebug("Erro ao gravar atividade de falha na sincronizacao de tramite: $ex", 2, true);
        }
      }

      //Nao recusa tramite caso o processo atual nao possa ser desbloqueado, evitando que o processo fique aberto em dois sistemas ao mesmo tempo
      $bolDeveRecusarTramite = !($e instanceof InfraException && $e->getObjException() != null && $e->getObjException() instanceof ProcessoNaoPodeSerDesbloqueadoException);
      // ou caso reproducao de ultimo tramite
      $bolDeveCancelar = $bolDeveRecusarTramite && !$objMetadadosProcedimento->metadados->reproducaoDeTramite;
      
      if($bolDeveCancelar) {
        $objProcessoEletronicoRN->cancelarTramite($idTramite);
      }

      $this->gravarLogDebug("Erro processando envio de sincronizacao de tramite: $e", 0, true);
      throw new InfraException('M\363dulo do Tramita: Falha de comunica\347\343o com o servi\347os de integra\347\343o. Por favor, tente novamente mais tarde.', $e);
    }
  }

  /**
   * Conclui a atividade pendente de sincronização para evitar que ela permaneça
   * como status mais recente quando o trâmite é cancelado por erro.
   *
   * @param mixed $dblIdProcedimento
   * @return void
   */
  private function concluirAtividadePendenteSincronizacao($dblIdProcedimento)
  {
    if (empty($dblIdProcedimento)) {
      return;
    }

    try {
      $idTarefa = ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MULTIPLOS_ORGAOS_RECEBIDO);

      $objAtividadeDTO = new AtividadeDTO();
      $objAtividadeDTO->setDblIdProtocolo($dblIdProcedimento);
      $objAtividadeDTO->setDthConclusao(null);
      $objAtividadeDTO->setDistinct(true);
      $objAtividadeDTO->setNumIdTarefa($idTarefa);
      $objAtividadeDTO->setNumMaxRegistrosRetorno(1);
      $objAtividadeDTO->retTodos();

      $objAtividadeRN = new AtividadeRN();
      $objAtividadeDTO = $objAtividadeRN->consultarRN0033($objAtividadeDTO);

      if ($objAtividadeDTO) {
        $objAtividadeRN->concluirRN0726([$objAtividadeDTO]);
      }
    } catch (\Exception $e) {
      $this->gravarLogDebug("Erro ao concluir atividade pendente de sincronizacao do processo $dblIdProcedimento: $e", 2, true);
    }
  }

  /**
   * Reabre de forma defensiva um processo que tenha permanecido bloqueado apos
   * falha no envio de sincronizacao disparado por uma pendencia de tramite.
   *
   * O metodo valida o estado atual do processo antes de tentar o desbloqueio
   * e restaura a unidade original da sessao ao final da operacao.
   *
   * @param mixed $dblIdProcedimento
   * @return void
   */
  private function reabrirProcessoSeBloqueado($dblIdProcedimento)
  {
    if (empty($dblIdProcedimento)) {
      return;
    }

    try {
      $objProcedimentoDTO = $this->consultarProcedimento($dblIdProcedimento);

      if ($objProcedimentoDTO->getStrStaEstadoProtocolo() != ProtocoloRN::$TE_PROCEDIMENTO_BLOQUEADO) {
        return;
      }

      $numIdUnidadeSessaoOriginal = SessaoSEI::getInstance()->getNumIdUnidadeAtual();
      $numIdUnidadeDesbloqueio = null;

      try {
        $numIdUnidadeDesbloqueio = ProcessoEletronicoRN::obterUnidadeParaRegistroDocumento($dblIdProcedimento);

        if (!empty($numIdUnidadeDesbloqueio)) {
          SessaoSEI::getInstance()->setNumIdUnidadeAtual($numIdUnidadeDesbloqueio);
        }

        ProcessoEletronicoRN::desbloquearProcesso($dblIdProcedimento);
        $this->gravarLogDebug("Processo $dblIdProcedimento reaberto automaticamente apos falha no envio de sincronizacao", 2, true);
      } finally {
        SessaoSEI::getInstance()->setNumIdUnidadeAtual($numIdUnidadeSessaoOriginal);
      }
    } catch (\Exception $e) {
      $this->gravarLogDebug("Falha ao reabrir automaticamente o processo $dblIdProcedimento apos erro no envio de sincronizacao: $e", 2, true);
    }
  }

  /**
   * Valida se o processo a ser sincronizado é sigiloso
   *
   * @param  mixed $dblIdProcedimento
   * @return void
   */
  public function validarSincronizacaoProcessoSigiloso($dblIdProcedimento)
  {
    $objProcedimentoDTO = $this->consultarProcedimento($dblIdProcedimento);
    if ($objProcedimentoDTO->getStrStaNivelAcessoLocalProtocolo() == ProtocoloRN::$NA_SIGILOSO) {
        throw new InfraException('Não é possível sincronizar um processo com informações sigilosas.');
    }
  }
}
