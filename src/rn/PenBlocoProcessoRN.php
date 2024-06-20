<?php

require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Regra de negócio para o parâmetros do módulo PEN
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

  protected function obterPendenciasLoteControlado(PenBlocoProcessoDTO $objPenLoteProcedimentoDTO)
  {
    try {

      //Valida PermissãoTipo
      SessaoSEI::getInstance()->validarAuditarPermissao('pen_expedir_bloco', __METHOD__, $objPenLoteProcedimentoDTO);

      //Obter todos os processos pendentes antes de iniciar o monitoramento
      $arrObjPendenciasLoteDTO = $this->listar($objPenLoteProcedimentoDTO) ?: array();
      shuffle($arrObjPendenciasLoteDTO);

      $objPenLoteProcedimentoBD = new PenLoteProcedimentoBD($this->getObjInfraIBanco());
      foreach ($arrObjPendenciasLoteDTO as $objPendenciasLoteDTO) {
        //Captura todas as pendências e status retornadas para impedir duplicidade
        $arrPendenciasLoteRetornadas[] = sprintf("%d-%s", $objPendenciasLoteDTO->getDblIdProtocolo(), $objPendenciasLoteDTO->getNumIdAndamento());

        $objPendenciasLoteDTO->setNumIdAndamento(ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO);
        $objPenLoteProcedimentoBD->alterar($objPendenciasLoteDTO);

        yield $objPendenciasLoteDTO;
      }
    } catch (\Exception $e) {
      throw new InfraException('Falha em obter pendências de trâmite de processos em lote.', $e);
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
        $objTramiteEmBlocoProtocoloDTO->setOrdNumIdBlocoProcesso(InfraDTO::$TIPO_ORDENACAO_DESC);
        $objTramiteEmBlocoProtocoloDTO->retDblIdProtocolo();
        $objTramiteEmBlocoProtocoloDTO->retNumIdBloco();

        $objTramitaEmBlocoProtocoloRN = new PenBlocoProcessoRN();
        $tramiteEmBlocoProtocolo = $objTramitaEmBlocoProtocoloRN->listar($objTramiteEmBlocoProtocoloDTO);

        if ($tramiteEmBlocoProtocolo != null) {
          $this->atualizarEstadoDoBlocoConcluidoParcialmente($tramiteEmBlocoProtocolo);
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
      throw new InfraException('Falha em obter pendências de trâmite de processos em lote.', $e);
    }
  }

  /**
   * Registra a tentativa de trâmite do processo em lote para posterior verificação de estouro do limite de envios
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
   * Método utilizado para exclusão de dados.
   * @param TramitaEmBlocoProtocoloDTO $objDTO
   * @return array
   * @throws InfraException
   */
  protected function listarControlado(PenBlocoProcessoDTO $objDTO)
  {
    try {
      //Valida Permissão
      SessaoSEI::getInstance()->validarAuditarPermissao('pen_tramita_em_bloco_protocolo_listar', __METHOD__, $objDTO);

      $objPenBlocoProcessoBD = new PenBlocoProcessoBD($this->getObjInfraIBanco());
      $arrObjPenBlocoProcessoDTO = $objPenBlocoProcessoBD->listar($objDTO);

      return $arrObjPenBlocoProcessoDTO;
    } catch (\Exception $e) {
      throw new InfraException('Falha na listagem de pendências de trâmite de processos em lote.', $e);
    }
  }

  /**
   * Método utilizado para exclusão de dados.
   * @param array $arrayObjDTO
   * @return array
   * @throws InfraException
   */
  protected function excluirControlado(array $arrayObjDTO)
  {
    try {
      //Valida Permissão
      SessaoSEI::getInstance()->validarAuditarPermissao('pen_tramita_em_bloco_protocolo_excluir', __METHOD__, $arrayObjDTO);

      $arrayExcluido = array();

      foreach ($arrayObjDTO as $objDTO) {
        $objBD = new PenBlocoProcessoBD(BancoSEI::getInstance());

        $objPenProtocoloDTO = new PenProtocoloDTO();
        $objPenProtocoloDTO->setDblIdProtocolo($objDTO->getDblIdProtocolo());
        $objPenProtocoloDTO->retStrSinObteveRecusa();
        // $objPenProtocoloDTO->setNumMaxRegistrosRetorno(1);

        $objProtocoloBD = new ProtocoloBD(BancoSEI::getInstance());
        $objPenProtocoloDTO = $objProtocoloBD->consultar($objPenProtocoloDTO);

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

        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDblIdProtocolo($objDTO->getDblIdProtocolo());
        $objAtividadeDTO->setNumIdTarefa([
          ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_CANCELADO),
          ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_RECUSADO),
          ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_ABORTADO)
        ], InfraDTO::$OPER_IN);
        $objAtividadeDTO->setOrdDthAbertura(InfraDTO::$TIPO_ORDENACAO_DESC);
        $objAtividadeDTO->setNumMaxRegistrosRetorno(1);
        $objAtividadeDTO->retNumIdAtividade();
        $objAtividadeDTO->retNumIdTarefa();
        $objAtividadeDTO->retDblIdProcedimentoProtocolo();
        $objAtividadeRN = new AtividadeRN();
        $objAtividadeDTO = $objAtividadeRN->consultarRN0033($objAtividadeDTO);

        if ($objAtividadeDTO != null) {
          $arrayExcluido[] = $objBD->excluir($objDTO);
        }
      }
      return $arrayExcluido;
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
      throw new InfraException('Erro montando indexação de processos em bloco.', $e);
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
    $objPenBlocoProcessoDTO->retNumIdBloco();
    $objPenBlocoProcessoDTO->retDblIdProtocolo();
    // $objPenBlocoProcessoDTO->retStrIdxRelBlocoProtocolo();

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

      $tramiteEmBlocoRN = new TramiteEmBlocoRN();
      $tramiteEmBloco = $tramiteEmBlocoRN->consultar($tramiteEmBlocoDTO);

      if (!empty($tramiteEmBloco)) {
        return "Prezado(a) usuário(a), o processo {$tramitaEmBloco->getDblIdProtocolo()} encontra-se inserido no bloco {$tramiteEmBloco->getNumId()} - {$tramiteEmBloco->getStrDescricao()}. Para continuar com essa ação é necessário que o processo seja removido do bloco em questão.";
      }

      $processoRecusadoNoBlocoParcial = $this->validarBlocoEstadoConcluidoParcial($tramitaEmBloco->getNumIdBloco(), $idProtocolo);
      if ($processoRecusadoNoBlocoParcial !== false) {
        return "Prezado(a) usuário(a), o processo {$tramitaEmBloco->getDblIdProtocolo()} encontra-se inserido no bloco {$processoRecusadoNoBlocoParcial->getNumId()} - {$processoRecusadoNoBlocoParcial->getStrDescricao()}. Para continuar com essa ação é necessário que o processo seja removido do bloco em questão.";
      }
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

    $tramiteEmBlocoRN = new TramiteEmBlocoRN();
    $tramiteEmBloco = $tramiteEmBlocoRN->consultar($tramiteEmBlocoDTO);

    if (!empty($tramiteEmBloco)) {
      $objPenProtocolo = new PenProtocoloDTO();
      $objPenProtocolo->setDblIdProtocolo($idProtocolo);
      $objPenProtocolo->setStrSinObteveRecusa('S');
      $objPenProtocolo->setNumMaxRegistrosRetorno(1);
      $objPenProtocolo->retDblIdProtocolo();

      $objPenProtocoloBD = new ProtocoloBD(BancoSEI::getInstance());
      $ObjPenProtocoloDTO = $objPenProtocoloBD->consultar($objPenProtocolo);

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
      return "Não é possível incluir mais que {$numMaximoDeProcessos} processos em um único bloco. O bloco selecionado já atingiu sua capacidade máxima.";
    }

    if ($numRegistroBloco + $numRegistroItens > $numMaximoDeProcessos) {
      return "Não é possível incluir mais que {$numMaximoDeProcessos} processos em um único bloco. Por favor, selecione outro bloco ou selecione uma quantidade menor de processos.";
    }

    return false;
  }

  /**
   * Atualizar Bloco  de tramite externo para concluído
   */
  public function atualizarEstadoDoBloco(PenBlocoProcessoDTO $tramiteEmBlocoProtocoloDTO, $novoEstadoDoBloco)
  {
    // Verificar se tem existe processo recusado dentro de um bloco
    $objTramiteEmBlocoProtocoloDTO2 = new PenBlocoProcessoDTO();
    $objTramiteEmBlocoProtocoloDTO2->setNumIdBloco($tramiteEmBlocoProtocoloDTO->getNumIdBloco());
    $objTramiteEmBlocoProtocoloDTO2->retNumIdBloco();
    $objTramiteEmBlocoProtocoloDTO2->retDblIdProtocolo();

    $objTramiteEmBlocoProtocoloDTORN = new PenBlocoProcessoRN($objTramiteEmBlocoProtocoloDTO2);
    $arrTramiteEmBlocoProtocolo = $objTramiteEmBlocoProtocoloDTORN->listar($objTramiteEmBlocoProtocoloDTO2);

    $objPenProtocolo = new PenProtocoloDTO();
    $objPenProtocolo->setDblIdProtocolo(InfraArray::converterArrInfraDTO($arrTramiteEmBlocoProtocolo, 'IdProtocolo'), InfraDTO::$OPER_IN);
    $objPenProtocolo->setStrSinObteveRecusa('S');
    $objPenProtocolo->setNumMaxRegistrosRetorno(1);
    $objPenProtocolo->retDblIdProtocolo();

    $objPenProtocoloBD = new ProtocoloBD(BancoSEI::getInstance());
    $ObjPenProtocoloDTO = $objPenProtocoloBD->consultar($objPenProtocolo);

    if ($ObjPenProtocoloDTO != null) {
      return null;
    }
    // não atualizar para concluido quando o bloco estiver em concluido parcialmente
    $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
    $objTramiteEmBlocoDTO->setNumId($tramiteEmBlocoProtocoloDTO->getNumIdBloco());
    $objTramiteEmBlocoDTO->setStrStaEstado([
      TramiteEmBlocoRN::$TE_ABERTO,
      TramiteEmBlocoRN::$TE_DISPONIBILIZADO,
    ], InfraDTO::$OPER_IN);
    $objTramiteEmBlocoDTO->retNumId();
    $objTramiteEmBlocoDTO->retStrStaEstado();

    $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
    $objTramiteEmBlocoDTO = $objTramiteEmBlocoRN->consultar($objTramiteEmBlocoDTO);

    if ($objTramiteEmBlocoDTO != null) {
      $objTramiteEmBlocoDTO->setStrStaEstado($novoEstadoDoBloco);
      $objTramiteEmBlocoRN->alterar($objTramiteEmBlocoDTO);
    }
  }

  /**
   * Atualizar Bloco  de tramite externo para concluído parcialmente
   */
  public function atualizarEstadoDoBlocoConcluidoParcialmente($arrTramiteEmBlocoProtocoloDTO)
  {
    $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
    $objTramiteEmBlocoDTO->setNumId($arrTramiteEmBlocoProtocoloDTO[0]->getNumIdBloco());
    $objTramiteEmBlocoDTO->setStrStaEstado(TramiteEmBlocoRN::$TE_CONCLUIDO_PARCIALMENTE);

    $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
    $objTramiteEmBlocoRN->alterar($objTramiteEmBlocoDTO);
  }
}
