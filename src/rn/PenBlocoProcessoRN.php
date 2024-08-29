<?php

require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Regra de neg�cio para o par�metros do m�dulo PEN
 */
class PenBlocoProcessoRN extends InfraRN
{
  /**
   * Inicializa o obj do banco da Infra
   * @return obj
   */
  protected function inicializarObjInfraIBanco()
  {
    return BancoSEI::getInstance();
  }

  /**
   * Verifica se o bloco pode ser exclu�do
   *
   * @param array $arrObjDTO
   * @return string|null
   */
  public function verificarExclusaoBloco(array $arrObjDTO)
  {
    $podeExcluir = true;
    $messagem = "Existem protocolos em andamento que n�o pode ser exclu�dos.";

    foreach ($arrObjDTO as $objPenLoteProcedimentoDTO) {
      $objPenLoteProcedimentoDTO->retNumIdBlocoProcesso();
      $objPenLoteProcedimentoDTO->retNumIdAndamento();
      $objPenLoteProcedimentoDTO->retStrProtocoloFormatadoProtocolo();

      $objPenLoteProcedimentoBD = new PenLoteProcedimentoBD($this->getObjInfraIBanco());
      $objPenLoteProcedimentoDTO = $objPenLoteProcedimentoBD->consultar($objPenLoteProcedimentoDTO);

      $situacaoPodeExcluir = array(
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CIENCIA_RECUSA,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO_AUTOMATICAMENTE ,
      );
      if (
        $objPenLoteProcedimentoDTO != null
        && !is_null($objPenLoteProcedimentoDTO->getNumIdAndamento())
        && !in_array($objPenLoteProcedimentoDTO->getNumIdAndamento(), $situacaoPodeExcluir)
      ) {
        $messagem .= "\n - {$objPenLoteProcedimentoDTO->getStrProtocoloFormatadoProtocolo()}";
        $podeExcluir = false;
      }
    }

    if (!$podeExcluir) {
      return $messagem;
    }

    return null;
  }

  protected function obterPendenciasLoteControlado(PenBlocoProcessoDTO $objPenLoteProcedimentoDTO)
  {
    try {

      //Valida Permiss�oTipo
      SessaoSEI::getInstance()->validarAuditarPermissao('pen_expedir_bloco', __METHOD__, $objPenLoteProcedimentoDTO);

      //Obter todos os processos pendentes antes de iniciar o monitoramento
      $arrObjPendenciasLoteDTO = $this->listar($objPenLoteProcedimentoDTO) ?: array();
      shuffle($arrObjPendenciasLoteDTO);

      $objPenLoteProcedimentoBD = new PenLoteProcedimentoBD($this->getObjInfraIBanco());
      foreach ($arrObjPendenciasLoteDTO as $objPendenciasLoteDTO) {
        //Captura todas as pend�ncias e status retornadas para impedir duplicidade
        $arrPendenciasLoteRetornadas[] = sprintf("%d-%s", $objPendenciasLoteDTO->getDblIdProtocolo(), $objPendenciasLoteDTO->getNumIdAndamento());

        $objPendenciasLoteDTO->setNumIdAndamento(ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO);
        $objPenLoteProcedimentoBD->alterar($objPendenciasLoteDTO);

        yield $objPendenciasLoteDTO;
      }
    } catch (\Exception $e) {
      throw new InfraException('Falha em obter pend�ncias de tr�mite de processos em lote.', $e);
    }
  }

  protected function desbloquearProcessoLoteControlado($dblIdProcedimento)
  {
    try {

      $objPenLoteProcedimentoDTO = new PenBlocoProcessoDTO();
      $objPenLoteProcedimentoDTO->retTodos();
      $objPenLoteProcedimentoDTO->setDblIdProtocolo($dblIdProcedimento);
      $objPenLoteProcedimentoDTO->setNumIdAndamento(array(ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_NAO_INICIADO, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO), InfraDTO::$OPER_IN);

      $objPenLoteProcedimentoDTO = $this->consultar($objPenLoteProcedimentoDTO);

      if (!is_null($objPenLoteProcedimentoDTO)) {
        $objPenLoteProcedimentoDTO->setNumIdAndamento(ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO);

        $this->alterar($objPenLoteProcedimentoDTO);

        // Atualizar Bloco para concluido parcialmente
        $objTramiteEmBlocoProtocoloDTO = new PenBlocoProcessoDTO();
        $objTramiteEmBlocoProtocoloDTO->setDblIdProtocolo($dblIdProcedimento);
        $objTramiteEmBlocoProtocoloDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
        $objTramiteEmBlocoProtocoloDTO->setOrdNumIdBlocoProcesso(InfraDTO::$TIPO_ORDENACAO_DESC);
        $objTramiteEmBlocoProtocoloDTO->retDblIdProtocolo();
        $objTramiteEmBlocoProtocoloDTO->retNumIdBloco();

        $objTramitaEmBlocoProtocoloRN = new PenBlocoProcessoRN();
        $tramiteEmBlocoProtocolo = $objTramitaEmBlocoProtocoloRN->listar($objTramiteEmBlocoProtocoloDTO);

        if ($tramiteEmBlocoProtocolo != null) {
          $idBloco = $tramiteEmBlocoProtocolo[0]->getNumIdBloco();
          $this->atualizarEstadoDoBloco($idBloco);
        }
      }

      //Desbloqueia o processo
      $objProtocoloRN = new ProtocoloRN();
      $objProtocoloDTO = new ProtocoloDTO();
      $objProtocoloDTO->setStrStaEstado(ProtocoloRN::$TE_NORMAL);
      $objProtocoloDTO->setDblIdProtocolo($dblIdProcedimento);
      $objProtocoloRN->alterarRN0203($objProtocoloDTO);

      //Cria o Objeto que registrar a Atividade de cancelamento
      $objAtividadeDTO = new AtividadeDTO();
      $objAtividadeDTO->setDblIdProtocolo($dblIdProcedimento);
      $objAtividadeDTO->setNumIdUnidade($objPenLoteProcedimentoDTO->getNumIdUnidade());
      $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_CANCELADO));

      //Seta os atributos do tamplate de descrio dessa atividade
      $objAtributoAndamentoDTOHora = new AtributoAndamentoDTO();
      $objAtributoAndamentoDTOHora->setStrNome('DATA_HORA');
      $objAtributoAndamentoDTOHora->setStrIdOrigem(null);
      $objAtributoAndamentoDTOHora->setStrValor(date('d/m/Y H:i'));

      $objUsuarioDTO = new UsuarioDTO();
      $objUsuarioDTO->setNumIdUsuario($objPenLoteProcedimentoDTO->getNumIdUsuario());
      $objUsuarioDTO->setBolExclusaoLogica(false);
      $objUsuarioDTO->retStrNome();

      $objUsuarioRN = new UsuarioRN();
      $objUsuario = $objUsuarioRN->consultarRN0489($objUsuarioDTO);

      $objAtributoAndamentoDTOUser = new AtributoAndamentoDTO();
      $objAtributoAndamentoDTOUser->setStrNome('USUARIO');
      $objAtributoAndamentoDTOUser->setStrIdOrigem(null);
      $objAtributoAndamentoDTOUser->setStrValor($objUsuario->getStrNome());

      $objAtividadeDTO->setArrObjAtributoAndamentoDTO(array($objAtributoAndamentoDTOHora, $objAtributoAndamentoDTOUser));

      $objAtividadeRN = new AtividadeRN();
      $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);
    } catch (\Exception $e) {
      throw new InfraException('Falha em obter pend�ncias de tr�mite de processos em lote.', $e);
    }
  }

  /**
   * Registra a tentativa de tr�mite do processo em lote para posterior verifica��o de estouro do limite de envios
   *
   * @param PenBlocoProcessoDTO $objPenLoteProcedimentoDTO
   * @return void
   */
  protected function registrarTentativaEnvioControlado(PenBlocoProcessoDTO $objPenLoteProcedimentoDTO)
  {
    $numTentativas = $objPenLoteProcedimentoDTO->getNumTentativas() ?: 0;
    $numTentativas += 1;

    $objPenLoteProcedimentoDTO->setNumTentativas($numTentativas);
    $objPenLoteProcedimentoBD = new PenBlocoProcessoBD($this->getObjInfraIBanco());
    $objPenLoteProcedimentoBD->alterar($objPenLoteProcedimentoDTO);
  }

  protected function listarProtocolosBlocoConectado(PenBlocoProcessoDTO $parObjTramitaEmBlocoProtocoloDTO)
  {
    try {

      $ret = array();

      //Valida Permissao
      SessaoSEI::getInstance()->validarAuditarPermissao('pen_tramita_em_bloco_protocolo_listar', __METHOD__, $parObjTramitaEmBlocoProtocoloDTO);

      $parObjRelBlocoProtocoloDTO = InfraString::prepararPesquisaDTO($parObjTramitaEmBlocoProtocoloDTO, "PalavrasPesquisa", "ProtocoloFormatadoProtocolo");
      $parObjRelBlocoProtocoloDTO->setStrStaNivelAcessoGlobalProtocolo(ProtocoloRN::$NA_SIGILOSO, InfraDTO::$OPER_DIFERENTE);
      $arrObjRelProtocoloBlocoDTO = $this->listar($parObjRelBlocoProtocoloDTO);

      foreach ($arrObjRelProtocoloBlocoDTO as $dto) {

        $objPenProtocoloDTO = new PenProtocoloDTO();
        $objPenProtocoloDTO->setDblIdProtocolo($dto->getDblIdProtocolo());
        $objPenProtocoloDTO->retStrSinObteveRecusa();
        $objPenProtocoloDTO->setNumMaxRegistrosRetorno(1);

        $objProtocoloBD = new ProtocoloBD(BancoSEI::getInstance());
        $objPenProtocoloDTO = $objProtocoloBD->consultar($objPenProtocoloDTO);

        if (!empty($objPenProtocoloDTO)) {
          $dto->setStrSinObteveRecusa($objPenProtocoloDTO->getStrSinObteveRecusa());
        } else {
          $dto->setStrSinObteveRecusa('N');
        }

        $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
        $objTramiteEmBlocoDTO->setNumId($parObjTramitaEmBlocoProtocoloDTO->getNumIdBloco());
        $objTramiteEmBlocoDTO->retNumId();
        $objTramiteEmBlocoDTO->retStrStaEstado();

        $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
        $blocoResultado = $objTramiteEmBlocoRN->consultar($objTramiteEmBlocoDTO);

        if (!empty($blocoResultado)) {
          $dto->setStrStaEstadoBloco($blocoResultado->getStrStaEstado());
        } else {
          $dto->setStrStaEstadoBloco('A');
        }

        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDblIdProtocolo($dto->getDblIdProtocolo());
        $objAtividadeDTO->setNumIdTarefa([
          ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO),
          ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO),
          ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_CANCELADO),
          ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_RECUSADO),
          ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_EXTERNO),
          ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_ABORTADO)
        ], InfraDTO::$OPER_IN);
        $objAtividadeDTO->setOrdDthAbertura(InfraDTO::$TIPO_ORDENACAO_DESC);
        $objAtividadeDTO->setNumMaxRegistrosRetorno(1);
        $objAtividadeDTO->retNumIdAtividade();
        $objAtividadeDTO->retNumIdTarefa();
        $objAtividadeDTO->retDblIdProcedimentoProtocolo();
        $objAtividadeRN = new AtividadeRN();
        $arrObjAtividadeDTO = $objAtividadeRN->listarRN0036($objAtividadeDTO);

        if (!empty($arrObjAtividadeDTO) && $arrObjAtividadeDTO[0]->getNumIdTarefa() != null) {
          $dto->setNumStaIdTarefa($arrObjAtividadeDTO[0]->getNumIdTarefa());
        } else {
          $dto->setNumStaIdTarefa(0);
        }

        $ret[] = $dto;
      }

      return $ret;
    } catch (Exception $e) {
      throw new InfraException('Erro listando protocolos do bloco.', $e);
    }
  }

  protected function consultarProtocolosBlocoConectado(TramitaEmBlocoProtocoloDTO $objTramitaEmBlocoProtocoloDTO)
  {
    $ret = array();

    $parObjTramitaEmBlocoProtocoloDTO = $this->listar($objTramitaEmBlocoProtocoloDTO);

    foreach ($parObjTramitaEmBlocoProtocoloDTO as $objTramitaEmBlocoProtocoloDTO) {

      $objAtividadeDTO = new AtividadeDTO();
      $objAtividadeDTO->setDblIdProtocolo($objTramitaEmBlocoProtocoloDTO->getDblIdProtocolo());
      $objAtividadeDTO->setNumIdTarefa([
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO),
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO),
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_CANCELADO),
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_RECUSADO),
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_EXTERNO),
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_ABORTADO)
      ], InfraDTO::$OPER_IN);
      $objAtividadeDTO->setOrdDthAbertura(InfraDTO::$TIPO_ORDENACAO_DESC);
      $objAtividadeDTO->setNumMaxRegistrosRetorno(1);
      $objAtividadeDTO->retNumIdAtividade();
      $objAtividadeDTO->retNumIdTarefa();
      $objAtividadeDTO->retDblIdProcedimentoProtocolo();
      $objAtividadeRN = new AtividadeRN();
      $arrObjAtividadeDTO = $objAtividadeRN->listarRN0036($objAtividadeDTO);

      if (!empty($arrObjAtividadeDTO) && $arrObjAtividadeDTO[0]->getNumIdTarefa() != null) {
        $objTramitaEmBlocoProtocoloDTO->setNumStaIdTarefa($arrObjAtividadeDTO[0]->getNumIdTarefa());
      } else {
        $objTramitaEmBlocoProtocoloDTO->setNumStaIdTarefa(0);
      }

      return $objTramitaEmBlocoProtocoloDTO;
    }
  }

  protected function consultarConectado(PenBlocoProcessoDTO $objDTO)
  {
    try {
      $objTramitaEmBlocoProtocoloBD = new PenBlocoProcessoBD($this->getObjInfraIBanco());
      return $objTramitaEmBlocoProtocoloBD->consultar($objDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro consutando blocos.', $e);
    }
  }

  /**
   * M�todo utilizado para exclus�o de dados.
   * @param TramitaEmBlocoProtocoloDTO $objDTO
   * @return array
   * @throws InfraException
   */
  protected function listarControlado(PenBlocoProcessoDTO $objDTO)
  {
    try {
      //Valida Permiss�o
      SessaoSEI::getInstance()->validarAuditarPermissao('pen_tramita_em_bloco_protocolo_listar', __METHOD__, $objDTO);

      $objPenBlocoProcessoBD = new PenBlocoProcessoBD($this->getObjInfraIBanco());
      $arrObjPenBlocoProcessoDTO = $objPenBlocoProcessoBD->listar($objDTO);

      return $arrObjPenBlocoProcessoDTO;
    } catch (\Exception $e) {
      throw new InfraException('Falha na listagem de pend�ncias de tr�mite de processos em lote.', $e);
    }
  }

  protected function excluirControlado(array $arrayObjDTO)
  {
    try {
      //Valida Permiss�o
      SessaoSEI::getInstance()->validarAuditarPermissao('pen_tramita_em_bloco_protocolo_excluir', __METHOD__, $arrayObjDTO);

      $arrExcluido = array();

      $arrPodeExcluir = array(
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CIENCIA_RECUSA,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO_AUTOMATICAMENTE,
      );

      foreach ($arrayObjDTO as $objDTO) {

        $objBD = new PenBlocoProcessoBD(BancoSEI::getInstance());

        $tramiteEmBlocoDTO = new TramiteEmBlocoDTO();
        $tramiteEmBlocoDTO->setNumId($objDTO->getNumIdBloco());
        $tramiteEmBlocoDTO->setStrStaEstado(TramiteEmBlocoRN::$TE_ABERTO);
        $tramiteEmBlocoDTO->retStrStaEstado();
        $tramiteEmBlocoDTO->retNumId();

        $tramiteEmBlocoRN = new TramiteEmBlocoRN();
        $tramiteEmBloco = $tramiteEmBlocoRN->consultar($tramiteEmBlocoDTO);

        if ($tramiteEmBloco != null) {
          $arrayExcluido[] = $objBD->excluir($objDTO);
          continue;
        }

        $objPenProtocoloDTO = new PenBlocoProcessoDTO();
        $objPenProtocoloDTO->setDblIdProtocolo($objDTO->getDblIdProtocolo());
        $objPenProtocoloDTO->retNumIdAndamento();
        $objPenProtocoloDTO->retDblIdProtocolo();
        $objPenProtocoloDTO->setNumIdAndamento($arrPodeExcluir, InfraDTO::$OPER_IN);

        $objPenProtocoloDTO = $this->consultar($objPenProtocoloDTO);

        if ($objPenProtocoloDTO != null) {
          $arrExcluido[] = $objBD->excluir($objDTO);
          continue;
        }  
      }

      return $arrExcluido;
    } catch (Exception $e) {
      throw new InfraException('Erro excluindo Bloco.', $e);
    }
  }

  protected function montarIndexacaoControlado(PenBlocoProcessoDTO $objPenBlocoProcessoDTO)
  {
    try {

      $dto = new PenBlocoProcessoDTO();
      $dto->retNumIdBlocoProcesso();

      if (is_array($objPenBlocoProcessoDTO->getNumIdBlocoProcesso())) {
        $dto->setNumIdBlocoProcesso($objPenBlocoProcessoDTO->getNumIdBlocoProcesso(), InfraDTO::$OPER_IN);
      } else {
        $dto->setNumIdBlocoProcesso($objPenBlocoProcessoDTO->getNumIdBlocoProcesso());
      }

      $objPenBlocoProcessoDTOIdx = new PenBlocoProcessoDTO();

      $arrObjPenBlocoProcessoDTO = $this->listar($dto);

      foreach ($arrObjPenBlocoProcessoDTO as $dto) {

        $objPenBlocoProcessoDTOIdx->setNumBlocoProcesso($dto->getNumIdBlocoProcesso());
      }
    } catch (Exception $e) {
      throw new InfraException('Erro montando indexa��o de processos em bloco.', $e);
    }
  }

  protected function cadastrarControlado(PenBlocoProcessoDTO $objPenBlocoProcessoDTO)
  {
    try {

      //Valida Permissao
      SessaoSEI::getInstance()->validarAuditarPermissao('pen_tramite_em_bloco_cadastrar', __METHOD__, $objPenBlocoProcessoDTO);

      //Regras de Negocio
      $objInfraException = new InfraException();

      $objPenBlocoProcessoBD = new PenBlocoProcessoBD($this->getObjInfraIBanco());
      $ret = $objPenBlocoProcessoBD->cadastrar($objPenBlocoProcessoDTO);

      return $ret;
    } catch (Exception $e) {
      throw new InfraException('Erro cadastrando Processo em Bloco.', $e);
    }
  }

  protected function alterarControlado(PenBlocoProcessoDTO $objPenBlocoProcessoDTO)
  {
    try {
      $objPenBlocoProcessoBD = new PenBlocoProcessoBD($this->getObjInfraIBanco());
      $ret = $objPenBlocoProcessoBD->alterar($objPenBlocoProcessoDTO);

      return $ret;
    } catch (Exception $e) {
      throw new InfraException('Erro cadastrando Processo em Bloco.', $e);
    }
  }

  protected function validarBlocoDeTramiteControlado($idProtocolo)
  {
    $objPenBlocoProcessoDTO = new PenBlocoProcessoDTO();
    $objPenBlocoProcessoDTO->retNumIdBlocoProcesso();
    $objPenBlocoProcessoDTO->setDblIdProtocolo($idProtocolo);
    $objPenBlocoProcessoDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
    $objPenBlocoProcessoDTO->retNumIdBloco();
    $objPenBlocoProcessoDTO->retDblIdProtocolo();
    $objPenBlocoProcessoDTO->retStrProtocoloFormatadoProtocolo();

    $arrTramitaEmBloco = $this->listar($objPenBlocoProcessoDTO);

    foreach ($arrTramitaEmBloco as $tramitaEmBloco) {
      $tramiteEmBlocoDTO = new TramiteEmBlocoDTO();
      $tramiteEmBlocoDTO->setNumId($tramitaEmBloco->getNumIdBloco());
      $tramiteEmBlocoDTO->setStrStaEstado([
        TramiteEmBlocoRN::$TE_ABERTO,
        TramiteEmBlocoRN::$TE_DISPONIBILIZADO,
      ], InfraDTO::$OPER_IN);
      $tramiteEmBlocoDTO->retStrDescricao();
      $tramiteEmBlocoDTO->retStrStaEstado();
      $tramiteEmBlocoDTO->retNumId();
      $tramiteEmBlocoDTO->retNumOrdem();

      $tramiteEmBlocoRN = new TramiteEmBlocoRN();
      $tramiteEmBloco = $tramiteEmBlocoRN->consultar($tramiteEmBlocoDTO);

      if (!empty($tramiteEmBloco)) {
        return "Prezado(a) usu�rio(a), o processo {$tramitaEmBloco->getStrProtocoloFormatadoProtocolo()} encontra-se inserido no bloco {$tramiteEmBloco->getNumOrdem()} - {$tramiteEmBloco->getStrDescricao()}. Para continuar com essa a��o � necess�rio que o processo seja removido do bloco em quest�o.";
      }

      $processoRecusadoNoBlocoParcial = $this->validarBlocoEstadoConcluidoParcial($tramitaEmBloco->getNumIdBloco(), $idProtocolo);
      if ($processoRecusadoNoBlocoParcial !== false) {
        return "Prezado(a) usu�rio(a), o processo {$tramitaEmBloco->getStrProtocoloFormatadoProtocolo()} encontra-se inserido no bloco {$processoRecusadoNoBlocoParcial->getNumOrdem()} - {$processoRecusadoNoBlocoParcial->getStrDescricao()}. Para continuar com essa a��o � necess�rio que o processo seja removido do bloco em quest�o.";
      }
    }

    $objExpedirProcedimentoRN = new ExpedirProcedimentoRN();
    $objProcedimentoDTO = $objExpedirProcedimentoRN->consultarProcedimento($idProtocolo);

    $bolProcessoEstadoNormal = !in_array($objProcedimentoDTO->getStrStaEstadoProtocolo(), array(
      ProtocoloRN::$TE_PROCEDIMENTO_SOBRESTADO,
      ProtocoloRN::$TE_PROCEDIMENTO_BLOQUEADO
    ));
    if (!$bolProcessoEstadoNormal) {
      return "Prezado(a) usu�rio(a), o processo {$objProcedimentoDTO->getStrProtocoloProcedimentoFormatado()} encontra-se bloqueado."
        . " Dessa forma, n�o foi poss�vel realizar a sua inser��o no bloco selecionado.";
    }

    return false;
  }

  public function validarBlocoEstadoConcluidoParcial($dblIdbloco, $idProtocolo)
  {
    $tramiteEmBlocoDTO = new TramiteEmBlocoDTO();
    $tramiteEmBlocoDTO->setNumId($dblIdbloco);
    $tramiteEmBlocoDTO->setStrStaEstado([
      TramiteEmBlocoRN::$TE_CONCLUIDO_PARCIALMENTE,
    ], InfraDTO::$OPER_IN);
    $tramiteEmBlocoDTO->retStrDescricao();
    $tramiteEmBlocoDTO->retStrStaEstado();
    $tramiteEmBlocoDTO->retNumId();
    $tramiteEmBlocoDTO->retNumOrdem(); 

    $tramiteEmBlocoRN = new TramiteEmBlocoRN();
    $tramiteEmBloco = $tramiteEmBlocoRN->consultar($tramiteEmBlocoDTO);

    if (!empty($tramiteEmBloco)) {

      $parcialmenteConcluido = array(
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CIENCIA_RECUSA,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO_AUTOMATICAMENTE,
      );

      $objPenBlocoProcessoDTO = new PenBlocoProcessoDTO();
      $objPenBlocoProcessoDTO->setDblIdProtocolo($idProtocolo);
      $objPenBlocoProcessoDTO->setNumIdBloco($dblIdbloco);
      $objPenBlocoProcessoDTO->setNumIdAndamento($parcialmenteConcluido, InfraDTO::$OPER_IN);
      $objPenBlocoProcessoDTO->retDblIdProtocolo();
      $objPenBlocoProcessoDTO->retNumIdBloco();
      $objPenBlocoProcessoDTO->retNumIdAndamento();

      $objPenProtocoloBD = new PenBlocoProcessoBD(BancoSEI::getInstance());
      $ObjPenProtocoloDTO = $objPenProtocoloBD->consultar($objPenBlocoProcessoDTO);

      if ($ObjPenProtocoloDTO != null) {
        return $tramiteEmBloco;
      }
    }

    return false;
  }

  public function validarQuantidadeDeItensNoBloco($dblIdbloco, $arrProtocolosOrigem)
  {
    $tramitaEmBlocoProtocoloDTO = new PenBlocoProcessoDTO();
    $tramitaEmBlocoProtocoloDTO->setNumIdBloco($dblIdbloco);
    $tramitaEmBlocoProtocoloDTO->retNumIdBloco();

    $tramitaEmBlocoProtocoloRN = new PenBlocoProcessoRN();
    $arrTramitaEmBlocoProtocolo = $tramitaEmBlocoProtocoloRN->listar($tramitaEmBlocoProtocoloDTO);
    $numRegistroBloco = count($arrTramitaEmBlocoProtocolo);
    $numRegistroItens = count($arrProtocolosOrigem);

    $numMaximoDeProcessos = 100;
    if (!empty($numRegistroBloco) && $numRegistroBloco >= $numMaximoDeProcessos) {
      return "N�o � poss�vel incluir mais que {$numMaximoDeProcessos} processos em um �nico bloco. O bloco selecionado j� atingiu sua capacidade m�xima.";
    }

    if ($numRegistroBloco + $numRegistroItens > $numMaximoDeProcessos) {
      return "N�o � poss�vel incluir mais que {$numMaximoDeProcessos} processos em um �nico bloco. Por favor, selecione outro bloco ou selecione uma quantidade menor de processos.";
    }

    return false;
  }

  /**
   * Atualizar Bloco  de tramite externo para conclu�do
   *
   * @param int $idBloco
   * @throws InfraException
   */
  public function atualizarEstadoDoBloco($idBloco)
  {
    $blocoResultado = $this->buscarBloco($idBloco);

    if ($blocoResultado != null) {
      $arrObjTramiteEmBlocoProtocoloDTO = $this->buscarBlocoProcessos($idBloco);
      
      $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
      $idAndamentoBloco = TramiteEmBlocoRN::$TE_ABERTO;
      if (count($arrObjTramiteEmBlocoProtocoloDTO) > 0) {
        $idAndamentoBloco = $this->validarStatusProcessoParaBloco($arrObjTramiteEmBlocoProtocoloDTO, $idAndamentoBloco);
        $objTramiteEmBlocoDTO->setStrStaEstado($idAndamentoBloco);
      } else {
        $objTramiteEmBlocoDTO->setStrStaEstado($idAndamentoBloco);
      }

      $objTramiteEmBlocoDTO->setNumId($idBloco);

      $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
      $objTramiteEmBlocoRN->alterar($objTramiteEmBlocoDTO);
    }
  }

  /**
   * Busca um bloco pelo ID
   *
   * @param int $dblIdBloco
   */
  private function buscarBloco($idBloco)
  {
    $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
    $objTramiteEmBlocoDTO->setNumId($idBloco);
    $objTramiteEmBlocoDTO->retNumId();
    $objTramiteEmBlocoDTO->retStrStaEstado();

    $objTramiteEmBlocoRN = new TramiteEmBlocoRN();

    return $objTramiteEmBlocoRN->consultar($objTramiteEmBlocoDTO);
  }

  /**
   * Busca todos os processos de um bloco
   *
   * @param int $dblIdBloco
   */
  private function buscarBlocoProcessos($idBloco)
  {
    $objTramiteEmBlocoProtocoloDTO = new PenBlocoProcessoDTO();
    $objTramiteEmBlocoProtocoloDTO->setNumIdBloco($idBloco);
    $objTramiteEmBlocoProtocoloDTO->retNumIdAndamento();
    $objTramiteEmBlocoProtocoloDTO->retNumIdBloco();

    $tramitaEmBlocoProtocoloRN = new PenBlocoProcessoRN();

    return $tramitaEmBlocoProtocoloRN->listar($objTramiteEmBlocoProtocoloDTO);
  }

  /**
   * Valida o status do processo para o bloco
   *
   * @param array $arrObjTramiteEmBlocoProtocoloDTO
   * @param int $idAndamentoBloco
   */
  private function validarStatusProcessoParaBloco($arrObjTramiteEmBlocoProtocoloDTO, $idAndamentoBloco)
  {
    $concluido = ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE;
    $parcialmenteConcluido = array(
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CIENCIA_RECUSA,
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO,
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO,
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO_AUTOMATICAMENTE,
    );
    $emAndamento = array(
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO,
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_ENVIADOS_REMETENTE,
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_METADADOS_RECEBIDO_DESTINATARIO,
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO,
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_ENVIADO_DESTINATARIO
    );
    foreach ($arrObjTramiteEmBlocoProtocoloDTO as $objDTO) {
      if (
        in_array($objDTO->getNumIdAndamento(), $emAndamento)
        && $idAndamentoBloco != TramiteEmBlocoRN::$TE_CONCLUIDO_PARCIALMENTE
      ) {
        $idAndamentoBloco = TramiteEmBlocoRN::$TE_DISPONIBILIZADO;
      }
      if (in_array($objDTO->getNumIdAndamento(), $parcialmenteConcluido)) {
        $idAndamentoBloco = TramiteEmBlocoRN::$TE_CONCLUIDO_PARCIALMENTE;
      }
      if (
        $objDTO->getNumIdAndamento() == $concluido
        && (
          $idAndamentoBloco == TramiteEmBlocoRN::$TE_CONCLUIDO
          || $idAndamentoBloco == TramiteEmBlocoRN::$TE_ABERTO
        )
      ) {
        $idAndamentoBloco = TramiteEmBlocoRN::$TE_CONCLUIDO;
      }
    }

    return $idAndamentoBloco;
  }
}
