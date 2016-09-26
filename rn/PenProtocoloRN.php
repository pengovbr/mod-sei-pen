<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

/**
 * @author Join Tecnologia
 */
class PenProtocoloRN extends ProtocoloRN {

    public function cancelar(ProtocoloDTO $objProtocoloDTO) {

        $bolAcumulacaoPrevia = FeedSEIProtocolos::getInstance()->isBolAcumularFeeds();

        FeedSEIProtocolos::getInstance()->setBolAcumularFeeds(true);

        $objIndexacaoDTO = new IndexacaoDTO();
        $objIndexacaoDTO->setArrObjProtocoloDTO(array($objProtocoloDTO));

        $objIndexacaoRN = new IndexacaoRN();
        $objIndexacaoRN->prepararRemocaoProtocolo($objIndexacaoDTO);

        $this->cancelarInterno($objProtocoloDTO);

        if (!$bolAcumulacaoPrevia) {
            FeedSEIProtocolos::getInstance()->setBolAcumularFeeds(false);
            FeedSEIProtocolos::getInstance()->indexarFeeds();
        }
    }

    protected function cancelarInternoControlado(ProtocoloDTO $objProtocoloDTO) {
        try {

            //Valida Permissao
            SessaoSEI::getInstance()->validarAuditarPermissao('protocolo_cancelar', __METHOD__, $objProtocoloDTO);

            //Regras de Negocio
            $objInfraException = new InfraException();

            $this->validarStrMotivoCancelamento($objProtocoloDTO, $objInfraException);

            $objDocumentoDTO = new DocumentoDTO();
            $objDocumentoDTO->retDblIdDocumento();
            $objDocumentoDTO->retNumVersaoLock();
            $objDocumentoDTO->setDblIdDocumento($objProtocoloDTO->getDblIdProtocolo());

            $objDocumentoRN = new DocumentoRN();
            $objDocumentoDTO = $objDocumentoRN->consultarRN0005($objDocumentoDTO);

            if ($objDocumentoDTO == null) {
                $objInfraException->lancarValidacao('Documento não encontrado.');
            }

            $objDocumentoRN->bloquear($objDocumentoDTO);

            $objDocumentoDTO = new DocumentoDTO();
            $objDocumentoDTO->retDblIdDocumento();
            $objDocumentoDTO->retDblIdProcedimento();
            $objDocumentoDTO->retNumIdUnidadeGeradoraProtocolo();
            $objDocumentoDTO->retStrProtocoloDocumentoFormatado();
            $objDocumentoDTO->retDblIdProtocoloProtocolo();
            $objDocumentoDTO->retStrStaProtocoloProtocolo();
            $objDocumentoDTO->retStrStaNivelAcessoLocalProtocolo();
            $objDocumentoDTO->retStrStaNivelAcessoGlobalProtocolo();
            $objDocumentoDTO->retStrStaArquivamentoProtocolo();
            $objDocumentoDTO->retStrStaEstadoProtocolo();
            $objDocumentoDTO->retStrSinFormulario();
            $objDocumentoDTO->retObjPublicacaoDTO();
            $objDocumentoDTO->setDblIdDocumento($objProtocoloDTO->getDblIdProtocolo());

            $objDocumentoRN = new DocumentoRN();
            $objDocumentoDTO = $objDocumentoRN->consultarRN0005($objDocumentoDTO);

            if ($objDocumentoDTO == null) {
                $objInfraException->lancarValidacao('Documento não encontrado.');
            }

            if ($objDocumentoDTO->getStrStaEstadoProtocolo() == ProtocoloRN::$TE_CANCELADO) {
                $objInfraException->lancarValidacao('Documento já foi cancelado.');
            }

            if ($objDocumentoDTO->getStrSinFormulario() == 'S') {
                $objInfraException->lancarValidacao('Formulários não podem ser cancelados.');
            }


            if ($objDocumentoDTO->getStrStaProtocoloProtocolo() == ProtocoloRN::$TP_DOCUMENTO_GERADO) {
                if ($objDocumentoDTO->getObjPublicacaoDTO() != null) {

                    if ($objDocumentoDTO->getObjPublicacaoDTO()->getStrStaEstado() == PublicacaoRN::$TE_PUBLICADO) {
                        $objInfraException->lancarValidacao('Não é possível cancelar um documento publicado.');
                    }

                    if ($objDocumentoDTO->getObjPublicacaoDTO()->getStrStaEstado() == PublicacaoRN::$TE_AGENDADO) {
                        $objInfraException->lancarValidacao('Não é possível cancelar um documento agendado para publicação.');
                    }
                }
            }

            if ($objDocumentoDTO->getStrStaNivelAcessoGlobalProtocolo() == ProtocoloRN::$NA_SIGILOSO) {
                $objAtividadeRN = new AtividadeRN();
                $arrObjAtividadeDTO = $objAtividadeRN->listarCredenciaisAssinatura($objDocumentoDTO);
                foreach ($arrObjAtividadeDTO as $objAtividadeDTO) {
                    if ($objAtividadeDTO->getNumIdTarefa() == TarefaRN::$TI_CONCESSAO_CREDENCIAL_ASSINATURA) {
                        $objInfraException->lancarValidacao('Documento possui credencial para assinatura ativa.');
                        break;
                    }
                }
            }

            if ($objDocumentoDTO->getStrStaArquivamentoProtocolo() == ProtocoloRN::$TA_ARQUIVADO) {
                $objInfraException->lancarValidacao('Não é possível cancelar um documento arquivado.');
            } else if ($objDocumentoDTO->getStrStaArquivamentoProtocolo() == ProtocoloRN::$TA_SOLICITADO_DESARQUIVAMENTO) {
                $objInfraException->lancarValidacao('Não é possível cancelar um documento com solicitação de arquivamento.');
                /*
                  }else{

                  $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
                  $objAtributoAndamentoDTO->retNumIdAtividade();
                  $objAtributoAndamentoDTO->retNumIdUnidadeOrigemAtividade();
                  $objAtributoAndamentoDTO->retStrSiglaUnidadeOrigemAtividade();
                  $objAtributoAndamentoDTO->retNumIdTarefaAtividade();
                  $objAtributoAndamentoDTO->setStrNome('DOCUMENTO');
                  $objAtributoAndamentoDTO->setStrIdOrigem($objDocumentoDTO->getDblIdDocumento());
                  $objAtributoAndamentoDTO->setNumIdTarefaAtividade(TarefaRN::$TI_ARQUIVAMENTO);
                  $objAtributoAndamentoDTO->setOrdNumIdAtividade(InfraDTO::$TIPO_ORDENACAO_DESC);
                  $objAtributoAndamentoDTO->setNumMaxRegistrosRetorno(1);

                  $objAtributoAndamentoRN = new AtributoAndamentoRN();
                  $objAtributoAndamentoDTO = $objAtributoAndamentoRN->consultarRN1366($objAtributoAndamentoDTO);

                  if ($objAtributoAndamentoDTO!=null){
                  $objInfraException->lancarValidacao('Houve arquivamento do documento pela unidade '.$objAtributoAndamentoDTO->getStrSiglaUnidadeOrigemAtividade().'.');
                  }
                 */
            }

            $objInfraException->lancarValidacoes();

            $this->validarProtocoloArquivadoRN1210($objProtocoloDTO);

            $objRelBlocoProtocoloDTO = new RelBlocoProtocoloDTO();
            $objRelBlocoProtocoloDTO->retNumIdBloco();
            $objRelBlocoProtocoloDTO->retDblIdProtocolo();
            $objRelBlocoProtocoloDTO->setDblIdProtocolo($objProtocoloDTO->getDblIdProtocolo());

            $objRelBlocoProtocoloRN = new RelBlocoProtocoloRN();
            $objRelBlocoProtocoloRN->excluirRN1289($objRelBlocoProtocoloRN->listarRN1291($objRelBlocoProtocoloDTO));

            $arrObjAtributoAndamentoDTO = array();
            $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
            $objAtributoAndamentoDTO->setStrNome('DOCUMENTO');
            $objAtributoAndamentoDTO->setStrValor($objDocumentoDTO->getStrProtocoloDocumentoFormatado());
            $objAtributoAndamentoDTO->setStrIdOrigem($objProtocoloDTO->getDblIdProtocolo());
            $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

            $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
            $objAtributoAndamentoDTO->setStrNome('MOTIVO');
            $objAtributoAndamentoDTO->setStrValor($objProtocoloDTO->getStrMotivoCancelamento());
            $objAtributoAndamentoDTO->setStrIdOrigem($objProtocoloDTO->getDblIdProtocolo());
            $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

            $objAtividadeDTO = new AtividadeDTO();
            $objAtividadeDTO->setDblIdProtocolo($objDocumentoDTO->getDblIdProcedimento());
            $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
            $objAtividadeDTO->setNumIdTarefa(TarefaRN::$TI_CANCELAMENTO_DOCUMENTO);
            $objAtividadeDTO->setArrObjAtributoAndamentoDTO($arrObjAtributoAndamentoDTO);

            $objAtividadeRN = new AtividadeRN();
            $objAtividadeDTO = $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);

            if ($objDocumentoDTO->getStrStaNivelAcessoLocalProtocolo() != ProtocoloRN::$NA_PUBLICO) {
                $dto = new ProtocoloDTO();
                $dto->setStrStaNivelAcessoLocal(ProtocoloRN::$NA_PUBLICO);
                $dto->setDblIdProtocolo($objProtocoloDTO->getDblIdProtocolo());
                $this->alterarRN0203($dto);
            }

            //cancelar
            $objProtocoloDTO->setStrStaEstado(ProtocoloRN::$TE_CANCELADO);

            $objProtocoloBD = new ProtocoloBD($this->getObjInfraIBanco());
            $objProtocoloBD->alterar($objProtocoloDTO);

            //Auditoria
        } catch (Exception $e) {
            throw new InfraException('Erro cancelando protocolo.', $e);
        }
    }

    private function validarStrMotivoCancelamento(ProtocoloDTO $objProtocoloDTO, InfraException $objInfraException) {
        if (InfraString::isBolVazia($objProtocoloDTO->getStrMotivoCancelamento())) {
            $objInfraException->adicionarValidacao('Motivo não informado.');
        }
    }
}
