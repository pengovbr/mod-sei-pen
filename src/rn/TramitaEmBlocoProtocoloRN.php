<?php

require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Regra de negócio para o parâmetros do módulo PEN
 */
class TramitaEmBlocoProtocoloRN extends InfraRN
{
  /**
   * Inicializa o obj do banco da Infra
   * @return obj
   */
  protected function inicializarObjInfraIBanco()
  {
    return BancoSEI::getInstance();
  }

  protected function listarProtocolosBlocoConectado(TramitaEmBlocoProtocoloDTO $parObjTramitaEmBlocoProtocoloDTO)
  {
    try {

      $ret = array();

      //Valida Permissao
      SessaoSEI::getInstance()->validarAuditarPermissao('pen_tramita_em_bloco_protocolo_listar', __METHOD__, $parObjTramitaEmBlocoProtocoloDTO);

      $parObjRelBlocoProtocoloDTO = InfraString::prepararPesquisaDTO($parObjTramitaEmBlocoProtocoloDTO, "PalavrasPesquisa", "IdxRelBlocoProtocolo");
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
        $objTramiteEmBlocoDTO->setNumId($parObjTramitaEmBlocoProtocoloDTO->getNumIdTramitaEmBloco());
        $objTramiteEmBlocoDTO->retNumId();
        $objTramiteEmBlocoDTO->retStrStaEstado();

        $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
        $blocoResultado = $objTramiteEmBlocoRN->consultar($objTramiteEmBlocoDTO);

        if (!empty($blocoResultado)) {
          $dto->setStrStaEstadoBloco($blocoResultado->getStrStaEstado());
        } else {
          $dto->setStrStaEstadoBloco('A');
        }

        $objPenLoteProcedimentoDTO = new PenLoteProcedimentoDTO(true);
        $objPenLoteProcedimentoDTO->setDblIdProcedimento($dto->getDblIdProtocolo());
        $objPenLoteProcedimentoDTO->setNumMaxRegistrosRetorno(1);

        $objPenLoteProcedimentoDTO->retNumIdLote();
        $objPenLoteProcedimentoDTO->retDblIdProcedimento();
        $objPenLoteProcedimentoDTO->retStrProcedimentoFormatado();
        $objPenLoteProcedimentoDTO->retNumIdAndamento();
        $objPenLoteProcedimentoDTO->retStrUnidadeDestino();
        $objPenLoteProcedimentoDTO->retStrNomeUsuario();
        $objPenLoteProcedimentoDTO->retDthRegistro();
        $objPenLoteProcedimentoDTO->setOrdNumIdLote(InfraDTO::$TIPO_ORDENACAO_DESC);

        $objPenLoteProcedimentoRN = new PenLoteProcedimentoRN();
        // $objPenLoteProcedimentoRN = $objPenLoteProcedimentoRN->consultarLoteProcedimento($objPenLoteProcedimentoDTO);
        $objPenLoteProcedimentoRN = $objPenLoteProcedimentoRN->consultarLoteProcedimento($objPenLoteProcedimentoDTO);

        $dto->setObjPenLoteProcedimentoDTO($objPenLoteProcedimentoRN);

        // $objTramiteDTO = new TramiteDTO();
        // $objTramiteDTO->setNumIdProcedimento($dto->getDblIdProtocolo());
        // $objTramiteDTO->setOrd('IdTramite', InfraDTO::$TIPO_ORDENACAO_DESC);
        // $objTramiteDTO->setNumMaxRegistrosRetorno(1);
        // $objTramiteDTO->retNumIdTramite();
        // $objTramiteDTO->retDthRegistro();
        // $objTramiteDTO->retNumIdEstruturaDestino();
        // $objTramiteDTO->retStrNomeUsuario();

        // $objTramiteBD = new TramiteBD($this->getObjInfraIBanco());
        // $objTramiteDTO = $objTramiteBD->consultar($objTramiteDTO);

        // $dto->setObjTramiteDTO($arrObjPenLoteProcedimentoRN);


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



  protected function consultarConectado(TramitaEmBlocoProtocoloDTO $objDTO)
  {
    try {
      $objTramitaEmBlocoProtocoloBD = new TramitaEmBlocoProtocoloBD($this->getObjInfraIBanco());
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
  protected function listarControlado(TramitaEmBlocoProtocoloDTO $objDTO)
  {
    try {
      //Valida Permissão
      SessaoSEI::getInstance()->validarAuditarPermissao('pen_tramita_em_bloco_protocolo_listar', __METHOD__, $objDTO);

      $objTramitaEmBlocoProtocoloBD = new TramitaEmBlocoProtocoloBD($this->getObjInfraIBanco());
      $arrTramitaEmBlocoProtocoloDTO = $objTramitaEmBlocoProtocoloBD->listar($objDTO);

      return $arrTramitaEmBlocoProtocoloDTO;
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
        $objBD = new TramitaEmBlocoProtocoloBD(BancoSEI::getInstance());

        $objPenProtocoloDTO = new PenProtocoloDTO();
        $objPenProtocoloDTO->setDblIdProtocolo($objDTO->getDblIdProtocolo());
        $objPenProtocoloDTO->retStrSinObteveRecusa();
        // $objPenProtocoloDTO->setNumMaxRegistrosRetorno(1);

        $objProtocoloBD = new ProtocoloBD(BancoSEI::getInstance());
        $objPenProtocoloDTO = $objProtocoloBD->consultar($objPenProtocoloDTO);

        if (!empty($objPenProtocoloDTO) && $objPenProtocoloDTO->getStrSinObteveRecusa() != 'S') {
          $tramiteEmBlocoDTO = new TramiteEmBlocoDTO();
          $tramiteEmBlocoDTO->setNumId($objDTO->getNumIdTramitaEmBloco());
          $tramiteEmBlocoDTO->setStrStaEstado(TramiteEmBlocoRN::$TE_ABERTO);
          $tramiteEmBlocoDTO->retStrStaEstado();
          $tramiteEmBlocoDTO->retNumId();

          $tramiteEmBlocoRN = new TramiteEmBlocoRN();
          $tramiteEmBloco = $tramiteEmBlocoRN->consultar($tramiteEmBlocoDTO);

          if ($tramiteEmBloco != null) {
            $arrayExcluido[] = $objBD->excluir($objDTO);
          }
        } else {
          $arrayExcluido[] = $objBD->excluir($objDTO);
        }
      }
      return $arrayExcluido;
    } catch (Exception $e) {
      throw new InfraException('Erro excluindo Bloco.', $e);
    }
  }

  protected function montarIndexacaoControlado(TramitaEmBlocoProtocoloDTO $objTramitaEmBlocoProtocoloDTO)
  {
    try {

      $dto = new TramitaEmBlocoProtocoloDTO();
      $dto->retNumId();

      if (is_array($objTramitaEmBlocoProtocoloDTO->getNumId())) {
        $dto->setNumId($objTramitaEmBlocoProtocoloDTO->getNumId(), InfraDTO::$OPER_IN);
      } else {
        $dto->setNumId($objTramitaEmBlocoProtocoloDTO->getNumId());
      }

      $objTramitaEmBlocoProtocoloDTOIdx = new TramitaEmBlocoProtocoloDTO();

      $arrObjTramitaEmBlocoProtocoloDTO = $this->listar($dto);

      foreach ($arrObjTramitaEmBlocoProtocoloDTO as $dto) {

        $objTramitaEmBlocoProtocoloDTOIdx->setNumId($dto->getNumId());
        $objTramitaEmBlocoProtocoloDTOIdx->setStrIdxRelBlocoProtocolo(InfraString::prepararIndexacao($dto->getNumId()));
      }
    } catch (Exception $e) {
      throw new InfraException('Erro montando indexação de processos em bloco.', $e);
    }
  }

  protected function cadastrarControlado(TramitaEmBlocoProtocoloDTO $objTramitaEmBlocoProtocoloDTO)
  {
    try {

      //Valida Permissao
      SessaoSEI::getInstance()->validarAuditarPermissao('pen_tramite_em_bloco_cadastrar', __METHOD__, $objTramitaEmBlocoProtocoloDTO);

      //Regras de Negocio
      $objInfraException = new InfraException();

      $objTramiteEmBlocoBD = new TramitaEmBlocoProtocoloBD($this->getObjInfraIBanco());
      $ret = $objTramiteEmBlocoBD->cadastrar($objTramitaEmBlocoProtocoloDTO);

      $this->montarIndexacao($ret);

      return $ret;
    } catch (Exception $e) {
      throw new InfraException('Erro cadastrando Processo em Bloco.', $e);
    }
  }

  protected function validarBlocoDeTramiteControlado($IdProtocolo)
  {
    $tramitaEmBlocoProtocoloDTO = new TramitaEmBlocoProtocoloDTO();
    $tramitaEmBlocoProtocoloDTO->retNumId();
    $tramitaEmBlocoProtocoloDTO->setDblIdProtocolo($IdProtocolo);
    $tramitaEmBlocoProtocoloDTO->retNumIdTramitaEmBloco();
    $tramitaEmBlocoProtocoloDTO->retStrIdxRelBlocoProtocolo();

    $arrTramitaEmBloco = $this->listar($tramitaEmBlocoProtocoloDTO);

    foreach ($arrTramitaEmBloco as $tramitaEmBloco) {
      $tramiteEmBlocoDTO = new TramiteEmBlocoDTO();
      $tramiteEmBlocoDTO->setNumId($tramitaEmBloco->getNumIdTramitaEmBloco());
      $tramiteEmBlocoDTO->setStrStaEstado([
        TramiteEmBlocoRN::$TE_ABERTO,
        TramiteEmBlocoRN::$TE_DISPONIBILIZADO,
        TramiteEmBlocoRN::$TE_CONCLUIDO_PARCIALMENTE,
      ], InfraDTO::$OPER_IN);
      $tramiteEmBlocoDTO->retStrDescricao();
      $tramiteEmBlocoDTO->retStrStaEstado();
      $tramiteEmBlocoDTO->retNumId();

      $tramiteEmBlocoRN = new TramiteEmBlocoRN();
      $tramiteEmBloco = $tramiteEmBlocoRN->consultar($tramiteEmBlocoDTO);

      if (!empty($tramiteEmBloco)) {
        return "Prezado(a) usuário(a), o processo {$tramitaEmBloco->getStrIdxRelBlocoProtocolo()} encontra-se inserido no bloco {$tramiteEmBloco->getNumId()} - {$tramiteEmBloco->getStrDescricao()}. Para continuar com essa ação é necessário que o processo seja removido do bloco em questão.";
      }
    }

    return false;
  }

  public function validarQuantidadeDeItensNoBloco($dblIdbloco, $arrProtocolosOrigem)
  {
    $tramitaEmBlocoProtocoloDTO = new TramitaEmBlocoProtocoloDTO();
    $tramitaEmBlocoProtocoloDTO->setNumIdTramitaEmBloco($dblIdbloco);
    $tramitaEmBlocoProtocoloDTO->retNumIdTramitaEmBloco();
    $tramitaEmBlocoProtocoloDTO->retStrIdxRelBlocoProtocolo();

    $tramitaEmBlocoProtocoloRN = new TramitaEmBlocoProtocoloRN();
    $arrTramitaEmBlocoProtocolo = $tramitaEmBlocoProtocoloRN->listar($tramitaEmBlocoProtocoloDTO);
    $numRegistroBloco = count($arrTramitaEmBlocoProtocolo);
    $numRegistroItens = count($arrProtocolosOrigem);

    $numMaximoDeProcessos = 100;
    if (!empty($numRegistroBloco) && $numRegistroBloco >= $numMaximoDeProcessos) {
      return "Não é possível incluir mais que {$numMaximoDeProcessos} processos em um único bloco. O bloco selecionado já atingiu sua capacidade máxima.";
    }

    if ($numRegistroBloco+$numRegistroItens > $numMaximoDeProcessos) {
      return "Não é possível incluir mais que {$numMaximoDeProcessos} processos em um único bloco. Por favor, selecione outro bloco ou selecione uma quantidade menor de processos.";
    }

    return false;
  }

  /**
   * Atualizar Bloco  de tramite externo para concluído
   */
  public function atualizarEstadoDoBloco(TramitaEmBlocoProtocoloDTO $tramiteEmBlocoProtocoloDTO, $novoEstadoDoBloco)
  {
    // Verificar se tem existe processo recusado dentro de um bloco
    $objTramiteEmBlocoProtocoloDTO2 = new TramitaEmBlocoProtocoloDTO();
    $objTramiteEmBlocoProtocoloDTO2->setNumIdTramitaEmBloco($tramiteEmBlocoProtocoloDTO->getNumIdTramitaEmBloco());
    $objTramiteEmBlocoProtocoloDTO2->retNumIdTramitaEmBloco();
    $objTramiteEmBlocoProtocoloDTO2->retDblIdProtocolo();

    $objTramiteEmBlocoProtocoloDTORN = new TramitaEmBlocoProtocoloRN($objTramiteEmBlocoProtocoloDTO2);

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

    $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
    $objTramiteEmBlocoDTO->setNumId($tramiteEmBlocoProtocoloDTO->getNumIdTramitaEmBloco());
    $objTramiteEmBlocoDTO->setStrStaEstado($novoEstadoDoBloco);

    $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
    $objTramiteEmBlocoRN->alterar($objTramiteEmBlocoDTO);
  }

  /**
   * Atualizar Bloco  de tramite externo para concluído parcialmente
   */
  public function atualizarEstadoDoBlocoConcluidoParcialmente($arrTramiteEmBlocoProtocoloDTO)
  {
    $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
    $objTramiteEmBlocoDTO->setNumId($arrTramiteEmBlocoProtocoloDTO[0]->getNumIdTramitaEmBloco());
    $objTramiteEmBlocoDTO->setStrStaEstado(TramiteEmBlocoRN::$TE_CONCLUIDO_PARCIALMENTE);

    $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
    $objTramiteEmBlocoRN->alterar($objTramiteEmBlocoDTO);
  }
}
