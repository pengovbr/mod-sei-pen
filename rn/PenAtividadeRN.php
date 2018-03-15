<?php
/**
 * @author Join Tecnologia
 */
require_once dirname(__FILE__) . '/../../../SEI.php';

class PenAtividadeRN extends AtividadeRN {

    private $statusPesquisa = true;
    
    public function setStatusPesquisa($statusPesquisa) {
        
        $this->statusPesquisa = $statusPesquisa;
    }
   /* 
    protected function listarPendenciasRN0754Conectado(PesquisaPendenciaDTO $objPesquisaPendenciaDTO) {

        if ($this->statusPesquisa) {
            if (!$objPesquisaPendenciaDTO->isSetStrStaEstadoProcedimento()) {
                $objPesquisaPendenciaDTO->setStrStaEstadoProcedimento(ProtocoloRN::$TE_NORMAL);
            }
        }

        if (!$objPesquisaPendenciaDTO->isSetStrStaTipoAtribuicao()) {
            $objPesquisaPendenciaDTO->setStrStaTipoAtribuicao(self::$TA_TODAS);
        }

        if (!$objPesquisaPendenciaDTO->isSetNumIdUsuarioAtribuicao()) {
            $objPesquisaPendenciaDTO->setNumIdUsuarioAtribuicao(null);
        }

        if (!$objPesquisaPendenciaDTO->isSetStrSinMontandoArvore()) {
            $objPesquisaPendenciaDTO->setStrSinMontandoArvore('N');
        }

        if (!$objPesquisaPendenciaDTO->isSetStrSinDocTodos()) {
            $objPesquisaPendenciaDTO->setStrSinDocTodos('N');
        }

        if (!$objPesquisaPendenciaDTO->isSetStrSinDocAnexos()) {
            $objPesquisaPendenciaDTO->setStrSinDocAnexos('N');
        }

        if (!$objPesquisaPendenciaDTO->isSetStrSinDocConteudo()) {
            $objPesquisaPendenciaDTO->setStrSinDocConteudo('N');
        }

        if (!$objPesquisaPendenciaDTO->isSetStrSinAnotacoes()) {
            $objPesquisaPendenciaDTO->setStrSinAnotacoes('N');
        }

        if (!$objPesquisaPendenciaDTO->isSetStrSinInteressados()) {
            $objPesquisaPendenciaDTO->setStrSinInteressados('N');
        }

        if (!$objPesquisaPendenciaDTO->isSetStrSinRetornoProgramado()) {
            $objPesquisaPendenciaDTO->setStrSinRetornoProgramado('N');
        }

        if (!$objPesquisaPendenciaDTO->isSetStrSinCredenciais()) {
            $objPesquisaPendenciaDTO->setStrSinCredenciais('N');
        }

        if (!$objPesquisaPendenciaDTO->isSetStrSinProcAnexados()) {
            $objPesquisaPendenciaDTO->setStrSinProcAnexados('N');
        }

        if (!$objPesquisaPendenciaDTO->isSetStrSinHoje()) {
            $objPesquisaPendenciaDTO->setStrSinHoje('N');
        }


        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->retNumIdAtividade();
        $objAtividadeDTO->retNumIdTarefa();
        $objAtividadeDTO->retNumIdUsuarioAtribuicao();
        $objAtividadeDTO->retNumIdUsuarioVisualizacao();
        $objAtividadeDTO->retNumTipoVisualizacao();
        $objAtividadeDTO->retNumIdUnidade();
        $objAtividadeDTO->retDthConclusao();
        $objAtividadeDTO->retDblIdProtocolo();
        $objAtividadeDTO->retStrSiglaUnidade();
        $objAtividadeDTO->retStrSinInicial();
        $objAtividadeDTO->retNumIdUsuarioAtribuicao();
        $objAtividadeDTO->retStrSiglaUsuarioAtribuicao();
        $objAtividadeDTO->retStrNomeUsuarioAtribuicao();

        $objAtividadeDTO->setNumIdUnidade($objPesquisaPendenciaDTO->getNumIdUnidade());

        if ($objPesquisaPendenciaDTO->getStrSinHoje() == 'N') {
            $objAtividadeDTO->setDthConclusao(null);
        } else {
            $objAtividadeDTO->adicionarCriterio(array('Conclusao', 'Conclusao'), array(InfraDTO::$OPER_IGUAL, InfraDTO::$OPER_MAIOR_IGUAL), array(null, InfraData::getStrDataAtual() . ' 00:00:00'), array(InfraDTO::$OPER_LOGICO_OR));
        }

        $objAtividadeDTO->adicionarCriterio(array('StaNivelAcessoGlobalProtocolo'), array(InfraDTO::$OPER_DIFERENTE), array(ProtocoloRN::$NA_SIGILOSO), array(), 'criterioRestritosPublicos');

        $objAtividadeDTO->adicionarCriterio(array('StaNivelAcessoGlobalProtocolo', 'IdUsuario'), array(InfraDTO::$OPER_IGUAL, InfraDTO::$OPER_IGUAL), array(ProtocoloRN::$NA_SIGILOSO, $objPesquisaPendenciaDTO->getNumIdUsuario()), array(InfraDTO::$OPER_LOGICO_AND), 'criterioSigilosos');

        $objAtividadeDTO->agruparCriterios(array('criterioRestritosPublicos', 'criterioSigilosos'), array(InfraDTO::$OPER_LOGICO_OR));

        if ($objPesquisaPendenciaDTO->getStrStaTipoAtribuicao() == self::$TA_MINHAS) {
            $objAtividadeDTO->setNumIdUsuarioAtribuicao($objPesquisaPendenciaDTO->getNumIdUsuario());
        } else if ($objPesquisaPendenciaDTO->getStrStaTipoAtribuicao() == self::$TA_DEFINIDAS) {
            $objAtividadeDTO->setNumIdUsuarioAtribuicao(null, InfraDTO::$OPER_DIFERENTE);
        } else if ($objPesquisaPendenciaDTO->getStrStaTipoAtribuicao() == self::$TA_ESPECIFICAS) {
            $objAtividadeDTO->setNumIdUsuarioAtribuicao($objPesquisaPendenciaDTO->getNumIdUsuarioAtribuicao());
        }

        if ($objPesquisaPendenciaDTO->isSetDblIdProtocolo()) {
            if (!is_array($objPesquisaPendenciaDTO->getDblIdProtocolo())) {
                $objAtividadeDTO->setDblIdProtocolo($objPesquisaPendenciaDTO->getDblIdProtocolo());
            } else {
                $objAtividadeDTO->setDblIdProtocolo($objPesquisaPendenciaDTO->getDblIdProtocolo(), InfraDTO::$OPER_IN);
            }
        }

        if ($objPesquisaPendenciaDTO->isSetStrStaEstadoProcedimento()) {
            if (is_array($objPesquisaPendenciaDTO->getStrStaEstadoProcedimento())) {
                $objAtividadeDTO->setStrStaEstadoProtocolo($objPesquisaPendenciaDTO->getStrStaEstadoProcedimento(), InfraDTO::$OPER_IN);
            } else {
                $objAtividadeDTO->setStrStaEstadoProtocolo($objPesquisaPendenciaDTO->getStrStaEstadoProcedimento());
            }
        }

        //ordenar pela data de abertura descendente
        $objAtividadeDTO->setOrdDthAbertura(InfraDTO::$TIPO_ORDENACAO_DESC);


        //paginação 
        $objAtividadeDTO->setNumMaxRegistrosRetorno($objPesquisaPendenciaDTO->getNumMaxRegistrosRetorno());
        $objAtividadeDTO->setNumPaginaAtual($objPesquisaPendenciaDTO->getNumPaginaAtual());

        $arrAtividadeDTO = $this->listarRN0036($objAtividadeDTO);

        //paginação
        $objPesquisaPendenciaDTO->setNumTotalRegistros($objAtividadeDTO->getNumTotalRegistros());
        $objPesquisaPendenciaDTO->setNumRegistrosPaginaAtual($objAtividadeDTO->getNumRegistrosPaginaAtual());

        $arrProcedimentos = array();

        //Se encontrou pelo menos um registro
        if (count($arrAtividadeDTO) > 0) {

            $objProcedimentoDTO = new ProcedimentoDTO();

            $objProcedimentoDTO->retDblIdProcedimento();
            $objProcedimentoDTO->retStrProtocoloProcedimentoFormatado();
            $objProcedimentoDTO->retStrNomeTipoProcedimento();
            $objProcedimentoDTO->retNumIdUnidadeGeradoraProtocolo();
            $objProcedimentoDTO->retStrStaEstadoProtocolo();
            $objProcedimentoDTO->retStrDescricaoProtocolo();
            $objProcedimentoDTO->retArrObjDocumentoDTO();


            $arrProtocolosAtividades = array_unique(InfraArray::converterArrInfraDTO($arrAtividadeDTO, 'IdProtocolo'));
            $objProcedimentoDTO->setDblIdProcedimento($arrProtocolosAtividades, InfraDTO::$OPER_IN);

            if ($objPesquisaPendenciaDTO->getStrSinMontandoArvore() == 'S') {
                $objProcedimentoDTO->setStrSinMontandoArvore('S');
            }

            if ($objPesquisaPendenciaDTO->getStrSinDocTodos() == 'S') {
                $objProcedimentoDTO->setStrSinDocTodos('S');
            }

            if ($objPesquisaPendenciaDTO->getStrSinDocAnexos() == 'S') {
                $objProcedimentoDTO->setStrSinDocAnexos('S');
            }

            if ($objPesquisaPendenciaDTO->getStrSinDocConteudo() == 'S') {
                $objProcedimentoDTO->setStrSinDocConteudo('S');
            }

            if ($objPesquisaPendenciaDTO->getStrSinProcAnexados() == 'S') {
                $objProcedimentoDTO->setStrSinProcAnexados('S');
            }

            if ($objPesquisaPendenciaDTO->isSetDblIdDocumento()) {
                $objProcedimentoDTO->setArrDblIdProtocoloAssociado(array($objPesquisaPendenciaDTO->getDblIdDocumento()));
            }

            $objProcedimentoRN = new ProcedimentoRN();

            $arr = InfraArray::indexarArrInfraDTO($objProcedimentoRN->listarCompleto($objProcedimentoDTO), 'IdProcedimento');

            $arrObjAnotacaoDTO = null;
            if ($objPesquisaPendenciaDTO->getStrSinAnotacoes() == 'S') {
                $objAnotacaoDTO = new AnotacaoDTO();
                $objAnotacaoDTO->retDblIdProtocolo();
                $objAnotacaoDTO->retStrDescricao();
                $objAnotacaoDTO->retStrSiglaUsuario();
                $objAnotacaoDTO->retStrNomeUsuario();
                $objAnotacaoDTO->retStrSinPrioridade();
                $objAnotacaoDTO->retNumIdUsuario();
                $objAnotacaoDTO->retStrStaAnotacao();
                $objAnotacaoDTO->setNumIdUnidade($objPesquisaPendenciaDTO->getNumIdUnidade());
                $objAnotacaoDTO->setDblIdProtocolo($arrProtocolosAtividades, InfraDTO::$OPER_IN);

                $objAnotacaoRN = new AnotacaoRN();
                $arrObjAnotacaoDTO = InfraArray::indexarArrInfraDTO($objAnotacaoRN->listar($objAnotacaoDTO), 'IdProtocolo', true);
            }


            $arrObjParticipanteDTO = null;
            if ($objPesquisaPendenciaDTO->getStrSinInteressados() == 'S') {

                $arrObjParticipanteDTO = array();

                $objParticipanteDTO = new ParticipanteDTO();
                $objParticipanteDTO->retDblIdProtocolo();
                $objParticipanteDTO->retStrSiglaContato();
                $objParticipanteDTO->retStrNomeContato();
                $objParticipanteDTO->setStrStaParticipacao(ParticipanteRN::$TP_INTERESSADO);
                $objParticipanteDTO->setDblIdProtocolo($arrProtocolosAtividades, InfraDTO::$OPER_IN);

                $objParticipanteRN = new ParticipanteRN();
                $arrTemp = $objParticipanteRN->listarRN0189($objParticipanteDTO);

                foreach ($arrTemp as $objParticipanteDTO) {
                    if (!isset($arrObjParticipanteDTO[$objParticipanteDTO->getDblIdProtocolo()])) {
                        $arrObjParticipanteDTO[$objParticipanteDTO->getDblIdProtocolo()] = array($objParticipanteDTO);
                    } else {
                        $arrObjParticipanteDTO[$objParticipanteDTO->getDblIdProtocolo()][] = $objParticipanteDTO;
                    }
                }
            }

            $arrObjRetornoProgramadoDTO = null;
            if ($objPesquisaPendenciaDTO->getStrSinRetornoProgramado() == 'S') {
                $objRetornoProgramadoDTO = new RetornoProgramadoDTO();
                $objRetornoProgramadoDTO->retDblIdProtocoloAtividadeEnvio();
                $objRetornoProgramadoDTO->retStrSiglaUnidadeOrigemAtividadeEnvio();
                $objRetornoProgramadoDTO->retDtaProgramada();
                $objRetornoProgramadoDTO->setNumIdUnidadeAtividadeEnvio($objPesquisaPendenciaDTO->getNumIdUnidade());
                $objRetornoProgramadoDTO->setDblIdProtocoloAtividadeEnvio($arrProtocolosAtividades, InfraDTO::$OPER_IN);
                $objRetornoProgramadoDTO->setNumIdAtividadeRetorno(null);

                $objRetornoProgramadoRN = new RetornoProgramadoRN();
                $arrObjRetornoProgramadoDTO = InfraArray::indexarArrInfraDTO($objRetornoProgramadoRN->listar($objRetornoProgramadoDTO), 'IdProtocoloAtividadeEnvio', true);
            }


            //Manter ordem obtida na listagem das atividades
            $arrAdicionados = array();
            $arrIdProcedimentoSigiloso = array();

            foreach ($arrAtividadeDTO as $objAtividadeDTO) {

                $objProcedimentoDTO = $arr[$objAtividadeDTO->getDblIdProtocolo()];

                //pode não existir se o procedimento foi excluído
                if ($objProcedimentoDTO != null) {

                    $dblIdProcedimento = $objProcedimentoDTO->getDblIdProcedimento();

                    if ($objProcedimentoDTO->getStrStaNivelAcessoGlobalProtocolo() == ProtocoloRN::$NA_SIGILOSO) {

                        $objProcedimentoDTO->setStrSinCredencialProcesso('N');
                        $objProcedimentoDTO->setStrSinCredencialAssinatura('N');

                        $arrIdProcedimentoSigiloso[] = $dblIdProcedimento;
                    }

                    if (!isset($arrAdicionados[$dblIdProcedimento])) {
                        $objProcedimentoDTO->setArrObjAtividadeDTO(array($objAtividadeDTO));

                        if (is_array($arrObjAnotacaoDTO)) {

                            $objProcedimentoDTO->setObjAnotacaoDTO(null);

                            if (isset($arrObjAnotacaoDTO[$dblIdProcedimento])) {

                                foreach ($arrObjAnotacaoDTO[$dblIdProcedimento] as $objAnotacaoDTO) {
                                    if ($objProcedimentoDTO->getStrStaNivelAcessoGlobalProtocolo() == ProtocoloRN::$NA_SIGILOSO) {
                                        if ($objAnotacaoDTO->getNumIdUsuario() == $objPesquisaPendenciaDTO->getNumIdUsuario() && $objAnotacaoDTO->getStrStaAnotacao() == AnotacaoRN::$TA_INDIVIDUAL) {
                                            $objProcedimentoDTO->setObjAnotacaoDTO($objAnotacaoDTO);
                                            break;
                                        }
                                    } else {
                                        if ($objAnotacaoDTO->getStrStaAnotacao() == AnotacaoRN::$TA_UNIDADE) {
                                            $objProcedimentoDTO->setObjAnotacaoDTO($objAnotacaoDTO);
                                            break;
                                        }
                                    }
                                }
                            }
                        }

                        if (is_array($arrObjParticipanteDTO)) {
                            if (isset($arrObjParticipanteDTO[$dblIdProcedimento])) {
                                $objProcedimentoDTO->setArrObjParticipanteDTO($arrObjParticipanteDTO[$dblIdProcedimento]);
                            } else {
                                $objProcedimentoDTO->setArrObjParticipanteDTO(null);
                            }
                        }

                        if (is_array($arrObjRetornoProgramadoDTO)) {
                            if (isset($arrObjRetornoProgramadoDTO[$dblIdProcedimento])) {
                                $objProcedimentoDTO->setArrObjRetornoProgramadoDTO($arrObjRetornoProgramadoDTO[$dblIdProcedimento]);
                            } else {
                                $objProcedimentoDTO->setArrObjRetornoProgramadoDTO(null);
                            }
                        }

                        $arrProcedimentos[] = $objProcedimentoDTO;
                        $arrAdicionados[$dblIdProcedimento] = 0;
                    } else {
                        $arrAtividadeDTOProcedimento = $objProcedimentoDTO->getArrObjAtividadeDTO();
                        $arrAtividadeDTOProcedimento[] = $objAtividadeDTO;
                        $objProcedimentoDTO->setArrObjAtividadeDTO($arrAtividadeDTOProcedimento);
                    }
                }
            }

            if ($objPesquisaPendenciaDTO->getStrSinCredenciais() == 'S' && count($arrIdProcedimentoSigiloso)) {

                $objAcessoDTO = new AcessoDTO();
                $objAcessoDTO->retDblIdProtocolo();
                $objAcessoDTO->retStrStaTipo();
                $objAcessoDTO->setNumIdUsuario($objPesquisaPendenciaDTO->getNumIdUsuario());
                $objAcessoDTO->setNumIdUnidade($objPesquisaPendenciaDTO->getNumIdUnidade());
                $objAcessoDTO->setStrStaTipo(array(AcessoRN::$TA_CREDENCIAL_PROCESSO, AcessoRN::$TA_CREDENCIAL_ASSINATURA_PROCESSO), InfraDTO::$OPER_IN);
                $objAcessoDTO->setDblIdProtocolo($arrIdProcedimentoSigiloso, InfraDTO::$OPER_IN);

                $objAcessoRN = new AcessoRN();
                $arrObjAcessoDTO = $objAcessoRN->listar($objAcessoDTO);

                /*
                  foreach($arr as $objProcedimentoDTO){
                  $objProcedimentoDTO->setStrSinCredencialProcesso('N');
                  $objProcedimentoDTO->setStrSinCredencialAssinatura('N');
                  }
                 */

          /*      foreach ($arrObjAcessoDTO as $objAcessoDTO) {
                    if ($objAcessoDTO->getStrStaTipo() == AcessoRN::$TA_CREDENCIAL_PROCESSO) {
                        $arr[$objAcessoDTO->getDblIdProtocolo()]->setStrSinCredencialProcesso('S');
                    } else if ($objAcessoDTO->getStrStaTipo() == AcessoRN::$TA_CREDENCIAL_ASSINATURA_PROCESSO) {
                        $arr[$objAcessoDTO->getDblIdProtocolo()]->setStrSinCredencialAssinatura('S');
                    }
                }
            }
        }

        return $arrProcedimentos;
    }*/

    /**
     * Retorna a atividade da ação do tramite, ou seja, se estava enviando
     * ou recebendo um tramite
     * 
     * @param int $numIdTramite
     * @return object (bool bolReciboExiste, string mensagem)
     */
    public static function retornaAtividadeDoTramiteFormatado($numIdTramite = 0, $numIdTarefa = 501){
        
        $objReturn = (object)array(
            'strMensagem' => '',
            'bolReciboExiste' => false
        );
        
        $objBancoSEI = BancoSEI::getInstance();
        
        $objTramiteDTO = new TramiteDTO();
        $objTramiteDTO->setNumIdTramite($numIdTramite);
        $objTramiteDTO->retStrNumeroRegistro();
        
        $objTramiteBD = new TramiteBD($objBancoSEI);
        $objTramiteDTO = $objTramiteBD->consultar($objTramiteDTO);
        
        if(!empty($objTramiteDTO)) {
            
            $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
            $objProcessoEletronicoDTO->setStrNumeroRegistro($objTramiteDTO->getStrNumeroRegistro());
            $objProcessoEletronicoDTO->retDblIdProcedimento();

            $objProcessoEletronicoDB = new ProcessoEletronicoBD($objBancoSEI);
            $objProcessoEletronicoDTO = $objProcessoEletronicoDB->consultar($objProcessoEletronicoDTO);

            $objAtividadeDTO = new AtividadeDTO();
            $objAtividadeDTO->setDblIdProtocolo($objProcessoEletronicoDTO->getDblIdProcedimento());
            $objAtividadeDTO->setDblIdProtocolo($objProcessoEletronicoDTO->getDblIdProcedimento());
            $objAtividadeDTO->setNumIdTarefa($numIdTarefa);            
            $objAtividadeDTO->retNumIdAtividade();
            
            $objAtividadeBD = new AtividadeBD($objBancoSEI);
            $arrObjAtividadeDTO = $objAtividadeBD->listar($objAtividadeDTO);
                       
            if(!empty($arrObjAtividadeDTO)) {

                $arrNumAtividade = array();
                                
                foreach($arrObjAtividadeDTO as $objAtividadeDTO) {
                    
                    $arrNumAtividade[] = $objAtividadeDTO->getNumIdAtividade();
                }
                
                switch($numIdTarefa){
                    case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO):
                        $strMensagem = 'Trâmite externo do Processo %s para %s';
                        $strNome = 'UNIDADE_DESTINO';
                        
                        $objReciboTramiteDTO = new ReciboTramiteDTO();
                        $objReciboTramiteDTO->setNumIdTramite($numIdTramite);
                        $objReciboTramiteDTO->retNumIdTramite();

                        $objReciboTramiteBD = new ReciboTramiteBD($objBancoSEI);
                        $objReturn->bolReciboExiste = ($objReciboTramiteBD->contar($objReciboTramiteDTO) > 0) ? true : false; 
                        break;
                            
                    case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO):
                        $strMensagem = 'Recebimento do Processo %s remetido por %s';
                        $strNome = 'ENTIDADE_ORIGEM';
                        
                        $objReciboTramiteDTO = new ReciboTramiteRecebidoDTO();
                        $objReciboTramiteDTO->setNumIdTramite($numIdTramite);
                        $objReciboTramiteDTO->retNumIdTramite();

                        $objReciboTramiteBD = new ReciboTramiteRecebidoBD($objBancoSEI);
                        $objReturn->bolReciboExiste = ($objReciboTramiteBD->contar($objReciboTramiteDTO) > 0) ? true : false;
                        break;
                } 
                                                
                $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
                $objAtributoAndamentoDTO->setNumIdAtividade($arrNumAtividade, InfraDTO::$OPER_IN);
                $objAtributoAndamentoDTO->setStrNome($strNome);
                $objAtributoAndamentoDTO->retStrValor();
                
                $objAtributoAndamentoBD = new AtributoAndamentoBD($objBancoSEI);
                $arrAtributoAndamentoDTO = $objAtributoAndamentoBD->listar($objAtributoAndamentoDTO);

                $objAtributoAndamentoDTO = current($arrAtributoAndamentoDTO);

                $obProtocoloDTO = new ProtocoloDTO();
                $obProtocoloDTO->setDblIdProtocolo($objProcessoEletronicoDTO->getDblIdProcedimento());
                $obProtocoloDTO->retStrProtocoloFormatado();
                
                
                $objProtocoloBD = new ProtocoloBD($objBancoSEI);
                $obProtocoloDTO = $objProtocoloBD->consultar($obProtocoloDTO);

                $objReturn->strMensagem = sprintf($strMensagem, $obProtocoloDTO->getStrProtocoloFormatado(), $objAtributoAndamentoDTO->getStrValor());                
            }
        }
        
        return $objReturn;
    }    
}