<?php

require_once DIR_SEI_WEB.'/SEI.php';

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

                $objTramiteDTO = new TramiteDTO();
                $objTramiteDTO->setNumIdProcedimento($dto->getDblIdProtocolo());
                $objTramiteDTO->setOrd('IdTramite', InfraDTO::$TIPO_ORDENACAO_DESC);
                $objTramiteDTO->setNumMaxRegistrosRetorno(1);
                $objTramiteDTO->retNumIdTramite();
                $objTramiteDTO->retDthRegistro();
                $objTramiteDTO->retNumIdEstruturaDestino();
                $objTramiteDTO->retStrNomeUsuario();

                $objTramiteBD = new TramiteBD($this->getObjInfraIBanco());
                $objTramiteDTO = $objTramiteBD->consultar($objTramiteDTO);

                $dto->setObjTramiteDTO($objTramiteDTO);

                $objAtividadeDTO = new AtividadeDTO();
                $objAtividadeDTO->setDblIdProtocolo($dto->getDblIdProtocolo());
                $objAtividadeDTO->setNumIdTarefa([
                    ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO),
                    ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_CANCELADO)
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
          //  SessaoSEI::getInstance()->validarAuditarPermissao('pen_tramita_em_bloco_protocolo_listar', __METHOD__, $objDTO);

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
                $arrayExcluido[] = $objBD->excluir($objDTO);
            }
            return $arrayExcluido;
        } catch (Exception $e) {
            throw new InfraException('Erro excluindo Bloco.', $e);
        }
    }

  protected function montarIndexacaoControlado(TramitaEmBlocoProtocoloDTO $objTramitaEmBlocoProtocoloDTO){
    try{

      $dto = new TramitaEmBlocoProtocoloDTO();
      $dto->retNumId();

      if (is_array($objTramitaEmBlocoProtocoloDTO->getNumId())) {
        $dto->setNumId($objTramitaEmBlocoProtocoloDTO->getNumId(), InfraDTO::$OPER_IN);
      } else {
        $dto->setNumId($objTramitaEmBlocoProtocoloDTO->getNumId());
      }

      $objTramitaEmBlocoProtocoloDTOIdx = new TramitaEmBlocoProtocoloDTO();
      $objInfraException = new InfraException();
      $objTramitaEmBlocoProtocoloBD = new TramiteEmBlocoBD($this->getObjInfraIBanco());

      $arrObjTramitaEmBlocoProtocoloDTO = $this->listar($dto);

      foreach($arrObjTramitaEmBlocoProtocoloDTO as $dto) {

        $objTramitaEmBlocoProtocoloDTOIdx->setNumId($dto->getNumId());
        $objTramitaEmBlocoProtocoloDTOIdx->setStrIdxRelBlocoProtocolo(InfraString::prepararIndexacao($dto->getNumId()));
      }

    } catch(Exception $e) {
      throw new InfraException('Erro montando indexação de processos em bloco.',$e);
    }
  }

  protected function cadastrarControlado(TramitaEmBlocoProtocoloDTO $objTramitaEmBlocoProtocoloDTO) {
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
      throw new InfraException('Erro cadastrando Processo em Bloco.',$e);
    }
  }

  protected function validarBlocoDeTramiteControlado(TramitaEmBlocoProtocoloDTO $objTramitaEmBlocoProtocoloDTO) {

    if (empty($objTramitaEmBlocoProtocoloDTO->getNumIdTramitaEmBloco())) {
      return 'Nenhum bloco foi selecionado! Por favor, selecione um bloco.';
    }

    $tramitaEmBlocoProtocoloDTO = new TramitaEmBlocoProtocoloDTO();
    $tramitaEmBlocoProtocoloDTO->retNumId();
    $tramitaEmBlocoProtocoloDTO->setDblIdProtocolo($objTramitaEmBlocoProtocoloDTO->getDblIdProtocolo());
    $tramitaEmBlocoProtocoloDTO->retNumIdTramitaEmBloco();
    
    $arrTramitaEmBloco = $this->listar($tramitaEmBlocoProtocoloDTO);

    if ($arrTramitaEmBloco!= null) {
      return "O processo atual já consta no bloco {$arrTramitaEmBloco[0]->getNumIdTramitaEmBloco()}";
    }

    $tramitaEmBlocoProtocoloDTO = new TramitaEmBlocoProtocoloDTO();
    $tramitaEmBlocoProtocoloDTO->retNumId();
    $tramitaEmBlocoProtocoloDTO->retNumIdTramitaEmBloco();
    $tramitaEmBlocoProtocoloDTO->setNumIdTramitaEmBloco($objTramitaEmBlocoProtocoloDTO->getNumIdTramitaEmBloco());

    $arrTramitaEmBloco = $this->listar($tramitaEmBlocoProtocoloDTO);

    $numMaximoDeProcessos = 100;
    $numRegistro = count($arrTramitaEmBloco);
    if (!empty($numRegistro) && $numRegistro >= $numMaximoDeProcessos) {
      return "O Bloco já contém o número máximo de {$numMaximoDeProcessos} processos.";
    }

    return false;
  }
}