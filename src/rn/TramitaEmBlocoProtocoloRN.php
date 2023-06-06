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

    protected function listarProtocolosBlocoConectado(RelBlocoProtocoloDTO $parObjRelBlocoProtocoloDTO)
    {
        try {

            $ret = array();

            //Valida Permissao
            // SessaoSEI::getInstance()->validarAuditarPermissao('rel_bloco_protocolo_listar', __METHOD__, $parObjRelBlocoProtocoloDTO);

            $parObjRelBlocoProtocoloDTO->retDblIdProtocolo();

            $parObjRelBlocoProtocoloDTO = InfraString::prepararPesquisaDTO($parObjRelBlocoProtocoloDTO, "PalavrasPesquisa", "IdxRelBlocoProtocolo");

            $parObjRelBlocoProtocoloDTO->setStrStaNivelAcessoGlobalProtocolo(ProtocoloRN::$NA_SIGILOSO, InfraDTO::$OPER_DIFERENTE);

            $arrObjRelProtocoloBlocoDTO = $this->listar($parObjRelBlocoProtocoloDTO);

            if (count($arrObjRelProtocoloBlocoDTO)) {

                foreach ($arrObjRelProtocoloBlocoDTO as $objRelBlocoProtocoloDTO) {
                    if ($parObjRelBlocoProtocoloDTO->isRetObjProtocoloDTO()) {
                        $objRelBlocoProtocoloDTO->setObjProtocoloDTO(null);
                    }
                    if ($parObjRelBlocoProtocoloDTO->isRetArrObjAssinaturaDTO()) {
                        $objRelBlocoProtocoloDTO->setArrObjAssinaturaDTO(array());
                    }
                }

                $arrObjRelProtocoloBlocoDTO = InfraArray::indexarArrInfraDTO($arrObjRelProtocoloBlocoDTO, 'IdProtocolo', true);

                $arrIdProtocolos = array_chunk(array_keys($arrObjRelProtocoloBlocoDTO), 1000);

                $objProtocoloRN = new ProtocoloRN();
                $objAssinaturaRN = new AssinaturaRN();

                foreach ($arrIdProtocolos as $arrIdProtocolosPartes) {

                    if ($parObjRelBlocoProtocoloDTO->isRetObjProtocoloDTO() || $parObjRelBlocoProtocoloDTO->isRetStrSinAberto()) {

                        $objProtocoloDTO = new ProtocoloDTO();
                        $objProtocoloDTO->setDistinct(true);
                        $objProtocoloDTO->retDblIdProtocolo();
                        $objProtocoloDTO->retStrProtocoloFormatado();
                        $objProtocoloDTO->retDtaGeracao();
                        $objProtocoloDTO->retStrStaProtocolo();
                        $objProtocoloDTO->retStrStaNivelAcessoGlobal();
                        $objProtocoloDTO->retNumIdTipoProcedimentoProcedimento();
                        $objProtocoloDTO->retStrNomeTipoProcedimentoProcedimento();
                        $objProtocoloDTO->retNumIdSerieDocumento();
                        $objProtocoloDTO->retStrNomeSerieDocumento();
                        $objProtocoloDTO->retStrNumeroDocumento();
                        $objProtocoloDTO->retDblIdProcedimentoDocumentoProcedimento();
                        $objProtocoloDTO->retNumIdTipoProcedimentoDocumento();
                        $objProtocoloDTO->retStrNomeTipoProcedimentoDocumento();
                        $objProtocoloDTO->retStrProtocoloFormatadoProcedimentoDocumento();
                        $objProtocoloDTO->retDblIdProcedimentoDocumento();

                        $objProtocoloDTO->setNumTipoFkProcedimento(InfraDTO::$TIPO_FK_OPCIONAL);
                        $objProtocoloDTO->setNumTipoFkDocumento(InfraDTO::$TIPO_FK_OPCIONAL);

                        $objProtocoloDTO->setDblIdProtocolo($arrIdProtocolosPartes, InfraDTO::$OPER_IN);

                        $objProtocoloDTO->setOrdDblIdProtocolo(InfraDTO::$TIPO_ORDENACAO_DESC);

                        $arrObjProtocoloDTO = InfraArray::indexarArrInfraDTO($objProtocoloRN->listarRN0668($objProtocoloDTO), 'IdProtocolo');

                        if ($parObjRelBlocoProtocoloDTO->isRetStrSinAberto()) {

                            $arrIdProcedimentos = array();

                            foreach ($arrObjProtocoloDTO as $objProtocoloDTO) {
                                if ($objProtocoloDTO->getStrStaProtocolo() == ProtocoloRN::$TP_PROCEDIMENTO) {
                                    $arrIdProcedimentos[$objProtocoloDTO->getDblIdProtocolo()] = true;
                                } else {
                                    $arrIdProcedimentos[$objProtocoloDTO->getDblIdProcedimentoDocumento()] = true;
                                }
                            }

                            $objAtividadeDTO = new AtividadeDTO();
                            $objAtividadeDTO->setDistinct(true);
                            $objAtividadeDTO->retDblIdProtocolo();
                            $objAtividadeDTO->setDthConclusao(null);
                            $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
                            $objAtividadeDTO->setDblIdProtocolo(array_keys($arrIdProcedimentos), InfraDTO::$OPER_IN);

                            $objAtividadeRN = new AtividadeRN();
                            $arrAtividades = InfraArray::indexarArrInfraDTO($objAtividadeRN->listarRN0036($objAtividadeDTO), 'IdProtocolo');

                            foreach ($arrObjProtocoloDTO as $objProtocoloDTO) {

                                if ($objProtocoloDTO->getStrStaProtocolo() == ProtocoloRN::$TP_PROCEDIMENTO) {
                                    $dblIdProcesso = $objProtocoloDTO->getDblIdProtocolo();
                                } else {
                                    $dblIdProcesso = $objProtocoloDTO->getDblIdProcedimentoDocumento();
                                }

                                if (isset($arrAtividades[$dblIdProcesso])) {
                                    $objProtocoloDTO->setStrSinAberto('S');
                                } else {
                                    $objProtocoloDTO->setStrSinAberto('N');
                                }
                            }
                        }

                        foreach ($arrObjProtocoloDTO as $dblIdProtocolo => $objProtocoloDTO) {
                            foreach ($arrObjRelProtocoloBlocoDTO[$dblIdProtocolo] as $objRelProtocoloBlocoDTO) {
                                $objRelProtocoloBlocoDTO->setObjProtocoloDTO($objProtocoloDTO);
                            }
                        }
                    }

                    if ($parObjRelBlocoProtocoloDTO->isRetArrObjAssinaturaDTO()) {

                        $objAssinaturaDTO = new AssinaturaDTO();
                        $objAssinaturaDTO->retDblIdDocumento();
                        $objAssinaturaDTO->retStrNome();
                        $objAssinaturaDTO->retStrTratamento();
                        $objAssinaturaDTO->retDthAberturaAtividade();
                        $objAssinaturaDTO->retNumIdUsuario();
                        $objAssinaturaDTO->retStrIdOrigemUsuario();
                        $objAssinaturaDTO->retNumIdOrgaoUsuario();
                        $objAssinaturaDTO->retStrSiglaUsuario();
                        $objAssinaturaDTO->setDblIdDocumento($arrIdProtocolosPartes, InfraDTO::$OPER_IN);
                        $arrObjAssinaturaDTO = InfraArray::indexarArrInfraDTO($objAssinaturaRN->listarRN1323($objAssinaturaDTO), 'IdDocumento', true);

                        foreach ($arrObjAssinaturaDTO as $dblIdDocumento => $arrObjAssinaturaDTODocumento) {
                            foreach ($arrObjRelProtocoloBlocoDTO[$dblIdDocumento] as $objRelProtocoloBlocoDTO) {
                                $objRelProtocoloBlocoDTO->setArrObjAssinaturaDTO($arrObjAssinaturaDTODocumento);
                            }
                        }
                    }
                }
            }

            foreach ($arrObjRelProtocoloBlocoDTO as $dblIdProtocolo => $arr) {
                foreach ($arr as $dto) {
                    $ret[] = $dto;
                }
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
            //SessaoSEI::getInstance()->validarAuditarPermissao('pen_expedir_lote', __METHOD__, $objDTO);

            $objTramitaEmBlocoProtocoloBD = new TramitaEmBlocoProtocoloBD($this->getObjInfraIBanco());
            $arrTramitaEmBlocoProtocoloDTO = $objTramitaEmBlocoProtocoloBD->listar($objDTO);

            return $arrTramitaEmBlocoProtocoloDTO;
        } catch (\Exception $e) {
            throw new InfraException('Falha na listagem de pendências de trâmite de processos em lote.', $e);
        }
    }

    /**
     * Método utilizado para exclusão de dados.
     * @param TramitaEmBlocoProtocoloDTO $objDTO
     * @return array
     * @throws InfraException
     */
    protected function excluirControlado(TramitaEmBlocoProtocoloDTO $objDTO)
    {
        try {
            $objBD = new TramitaEmBlocoProtocoloBD(BancoSEI::getInstance());
            return $objBD->excluir($objDTO);
        } catch (Exception $e) {
            throw new InfraException('Erro excluindo mapeamento de unidades.', $e);
        }
    }
}
