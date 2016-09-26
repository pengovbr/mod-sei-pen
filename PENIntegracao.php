<?php

class PENIntegracao extends SeiIntegracao {

    private static $strDiretorio;
    
    public function __construct() {
        
    }

    public function montarBotaoProcedimento(SeiIntegracaoDTO $objSeiIntegracaoDTO) {

        $objSessaoSEI = SessaoSEI::getInstance();
        $objPaginaSEI = PaginaSEI::getInstance();
        $strAcoesProcedimento = "";

        $objProcedimentoDTO = $objSeiIntegracaoDTO->getObjProcedimentoDTO();
        $dblIdProcedimento = $objProcedimentoDTO->getDblIdProcedimento();
        $numIdUsuario = SessaoSEI::getInstance()->getNumIdUsuario();
        $numIdUnidadeAtual = SessaoSEI::getInstance()->getNumIdUnidadeAtual();

        //Verifica se o processo encontra-se aberto na unidade atual
        $objAtividadeRN = new AtividadeRN();
        $objPesquisaPendenciaDTO = new PesquisaPendenciaDTO();
        $objPesquisaPendenciaDTO->setDblIdProtocolo($dblIdProcedimento);
        $objPesquisaPendenciaDTO->setNumIdUsuario($numIdUsuario);
        $objPesquisaPendenciaDTO->setNumIdUnidade($numIdUnidadeAtual);
        $objPesquisaPendenciaDTO->setStrSinMontandoArvore('N');
        $arrObjProcedimentoDTO = $objAtividadeRN->listarPendenciasRN0754($objPesquisaPendenciaDTO);
        $bolFlagAberto = count($arrObjProcedimentoDTO) == 1;


        //Verificação da Restrição de Acesso à Funcionalidade
        $bolAcaoExpedirProcesso = $objSessaoSEI->verificarPermissao('pen_procedimento_expedir');

        // ExpedirProcedimentoRN::__construct() criar a instância do ProcessoEletronicoRN
        // e este pode lançar exceções caso alguma configuração dele não estaja correta
        // invalidando demais ações na tela do Controle de Processo, então ecapsulamos
        // no try/catch para prevenir o erro em tela adicionamos no log
        try {

            $objExpedirProcedimentoRN = new ExpedirProcedimentoRN();
            $objProcedimentoDTO = $objExpedirProcedimentoRN->consultarProcedimento($dblIdProcedimento);

            $bolProcessoEstadoNormal = !in_array($objProcedimentoDTO->getStrStaEstadoProtocolo(), array(
                        ProtocoloRN::$TE_PROCEDIMENTO_SOBRESTADO,
                        ProtocoloRN::$TE_EM_PROCESSAMENTO,
                        ProtocoloRn::$TE_BLOQUEADO
            ));

            //TODO: Não apresentar
            //$bolFlagAberto && $bolAcaoProcedimentoEnviar && $objProcedimentoDTO->getStrStaNivelAcessoGlobalProtocolo()!=ProtocoloRN::$NA_SIGILOSO
            if ($bolFlagAberto && $bolAcaoExpedirProcesso && $bolProcessoEstadoNormal && $objProcedimentoDTO->getStrStaNivelAcessoGlobalProtocolo() != ProtocoloRN::$NA_SIGILOSO) {
                $numTabBotao = $objPaginaSEI->getProxTabBarraComandosSuperior();
                $strAcoesProcedimento .= '<a id="validar_expedir_processo" href="' . $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_expedir&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_procedimento=' . $dblIdProcedimento . '&arvore=1')) . '" tabindex="' . $numTabBotao . '" class="botaoSEI"><img class="infraCorBarraSistema" src="' . $this->getDiretorioImagens() . '/pen_expedir_procedimento.gif" alt="Expedir Processo" title="Expedir Processo" /></a>';
            }
            
            if($objProcedimentoDTO->getStrStaEstadoProtocolo() == ProtocoloRN::$TE_EM_PROCESSAMENTO) {
                
                $objProcessoEletronicoRN = new ProcessoEletronicoRN();
                
                if ($objProcessoEletronicoRN->isDisponivelCancelarTramite($objProcedimentoDTO->getStrProtocoloProcedimentoFormatado())) {
                    $strAcoesProcedimento .= '<a href="' . $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_cancelar_expedir&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_procedimento=' . $dblIdProcedimento . '&arvore=1')) . '" tabindex="' . $numTabBotao . '" class="botaoSEI">';
                    $strAcoesProcedimento .= '<img class="infraCorBarraSistema" src="' . $this->getDiretorioImagens() . '/sei_desanexar_processo.gif" alt="Cancelar Expedição" title="Cancelar Expedição" />';
                    $strAcoesProcedimento .= '</a>';
                }
            }
            $objProcedimentoAndamentoDTO = new ProcedimentoAndamentoDTO();
            $objProcedimentoAndamentoDTO->setDblIdProcedimento($dblIdProcedimento);

            $objGenericoBD = new GenericoBD(BancoSEI::getInstance());

            if ($objGenericoBD->contar($objProcedimentoAndamentoDTO) > 0) {

                $strAcoesProcedimento .= '<a href="' . $objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_estado&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_procedimento=' . $dblIdProcedimento . '&arvore=1') . '" tabindex="' . $numTabBotao . '" class="botaoSEI">';
                $strAcoesProcedimento .= '<img class="infraCorBarraSistema" src="' . $this->getDiretorioImagens() . '/pen_consultar_recibos.png" alt="Consultar Recibos" title="Consultar Recibos"/>';
                $strAcoesProcedimento .= '</a>';
            }
            /**
             * Rodina para validar doc processo 
             */
            //TODO: Revisar implementação feita pela Softimais nesse arquivo
            //$resultProcessoStatus = $objExpedirProcedimentoRn->consultaProcessoStatus($objProcedimentoDTO->getDblIdProcedimento());
            // $objProtocoloDTO  = new ProtocoloDTO();
            // $objProtocoloRN    = new ProtocoloRN();
            // $objProtocoloDTO->setDblIdProtocolo($objProcedimentoDTO->getDblIdProcedimento());
            // $objProtocoloDTO->retTodos();
            // $objProtocoloDTO =  $objProtocoloRN->consultarRN0186($objProtocoloDTO);
            //TODO: Verificar a diferença entre Nivel de Acesso Local e Global
            //$bolProcessoPublico = $objProcedimentoDTO->getStrStaNivelAcessoGlobalProtocolo() == ProtocoloRN::$NA_PUBLICO;
            //$bolProcessoEstado = $objProtocoloDTO->getStrStaEstado() != ProtocoloRN::$TE_PROCEDIMENTO_SOBRESTADO;
            // if ($bolAcaoExpedirProcesso &&  $bolProcessoEstado && $resultProcessoStatus['retorno'] ){
            // if ($bolAcaoExpedirProcesso &&  $bolProcessoEstado && $resultProcessoStatus['retorno'] ){    
            //   $strAcoesProcedimento .= '<a  id="validar_expedir_processo" href="'.$objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_expedir&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_procedimento='.$dblIdProcedimento.'&arvore=1')).'" tabindex="'.$numTabBotao.'" class="botaoSEI"><img class="infraCorBarraSistema" src="'.$this->getDiretorioImagens().'/pen_expedir_procedimento.gif" alt="Expedir Processo" title="Expedir Processo" /></a>';
            // }
            // if ($bolAcaoExpedirProcesso && $bolProcessoPublico && ! $bolProcessoEstado ){
            //   $strAcoesProcedimento .= '<a  id="validar_expedir_processo" href="'.$objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_cancelar_expedir&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_procedimento='.$dblIdProcedimento.'&arvore=1')).'" tabindex="'.$numTabBotao.'" class="botaoSEI">'
            //                          . '<img class="infraCorBarraSistema" src="'.$this->getDiretorioImagens().'/cancelar_tramitir.gif" alt="Cancelar Tramiter" title="Cancelar Tramiter" />'
            //                          . '</a>';
            // }
        } 
        catch(InfraException $e){
            LogSEI::getInstance()->gravar($e->getStrDescricao());
        } 
        catch (Exception $e) {
            LogSEI::getInstance()->gravar($e->getMessage());
        }

        return array($strAcoesProcedimento);
    }

    public function montarIconeProcedimento(SeiIntegracaoDTO $objSeiIntegracaoDTO) {
        return array();
    }

    public function montarBotaoDocumento(SeiIntegracaoDTO $objSeiIntegracaoDTO) {
        return array();
    }

    public function montarIconeDocumento(SeiIntegracaoDTO $objSeiIntegracaoDTO) {
        return array();
    }

    public function excluirProcedimento(ProcedimentoDTO $objProcedimentoDTO) {
        
    }

    public function atualizarConteudoDocumento(DocumentoDTO $objDocumentoDTO) {
        
    }

    public function excluirDocumento(DocumentoDTO $objDocumentoDTO) {
        
    }

    public function montarBotaoControleProcessos() {
        
    }

    public function montarIconeControleProcessos($arrObjProcedimentoDTO = array()) {

        $arrStrIcone = array();
        $arrDblIdProcedimento = array();

        foreach ($arrObjProcedimentoDTO as $objProcedimentoDTO) {

            $arrDblIdProcedimento[] = $objProcedimentoDTO->getDblIdProcedimento();
        }

        $objProcedimentoDTO = new ProcedimentoDTO();
        $objProcedimentoDTO->setDblIdProcedimento($arrDblIdProcedimento, InfraDTO::$OPER_IN);
        $objProcedimentoDTO->retDblIdProcedimento();
        $objProcedimentoDTO->retStrStaEstadoProtocolo();
        //$objProcedimentoDTO->retStrSinObteveRecusa();

        $objProcedimentoBD = new ProcedimentoBD(BancoSEI::getInstance());
        $arrObjProcedimentoDTO = $objProcedimentoBD->listar($objProcedimentoDTO);

        if (!empty($arrObjProcedimentoDTO)) {

            foreach ($arrObjProcedimentoDTO as $objProcedimentoDTO) {

                $dblIdProcedimento = $objProcedimentoDTO->getDblIdProcedimento();

                switch ($objProcedimentoDTO->getStrStaEstadoProtocolo()) {
                    case ProtocoloRN::$TE_EM_PROCESSAMENTO:
                        $arrStrIcone[$dblIdProcedimento] = array('<img src="' . $this->getDiretorioImagens() . '/pen_em_processamento.png" title="Em processamento" />');
                        break;

                    case ProtocoloRN::$TE_BLOQUEADO:
                        break;

                    default:
                        $objPenProtocoloDTO = new PenProtocoloDTO();
                        $objPenProtocoloDTO->setDblIdProtocolo($dblIdProcedimento);
                        $objPenProtocoloDTO->retStrSinObteveRecusa();
                        $objPenProtocoloDTO->setNumMaxRegistrosRetorno(1);

                        $objProtocoloBD = new ProtocoloBD(BancoSEI::getInstance());
                        $objPenProtocoloDTO = $objProtocoloBD->consultar($objPenProtocoloDTO);

                        if (!empty($objPenProtocoloDTO) && $objPenProtocoloDTO->getStrSinObteveRecusa() == 'S') {

                            $arrStrIcone[$dblIdProcedimento] = array('<img src="' . $this->getDiretorioImagens() . '/pen_tramite_recusado.png" title="Um trâmite para esse processo foi recusado" />');
                        }
                }
            }
        }

        return $arrStrIcone;
    }

    public function montarIconeAcompanhamentoEspecial($arrObjProcedimentoDTO) {
        
    }

    public function getDiretorioImagens() {
        return static::getDiretorio().'/imagens';
    }

    public function montarMensagemSituacaoProcedimento(ProcedimentoDTO $objProcedimentoDTO) {
        if($objProcedimentoDTO->getStrStaEstadoProtocolo() ==  ProtocoloRN::$TE_EM_PROCESSAMENTO ||  $objProcedimentoDTO->getStrStaEstadoProtocolo() ==  ProtocoloRN::$TE_BLOQUEADO ){
            $objAtividadeDTO = new AtividadeDTO();
            $objAtividadeDTO->setDblIdProtocolo($objProcedimentoDTO->getDblIdProcedimento());
            $objAtividadeDTO->retNumIdAtividade();

            $objAtividadeRN = new AtividadeRN();
            $arrAtividadeDTO = (array) $objAtividadeRN->listarRN0036($objAtividadeDTO);

            if (empty($arrAtividadeDTO)) {

                throw new InfraException('Não foi possivel localizar as atividades executadas nesse procedimento');
            }

            $objFiltroAtributoAndamentoDTO = new AtributoAndamentoDTO();
            $objFiltroAtributoAndamentoDTO->setStrNome('UNIDADE_DESTINO');
            $objFiltroAtributoAndamentoDTO->retStrValor();
            $objFiltroAtributoAndamentoDTO->setOrdNumIdAtributoAndamento(InfraDTO::$TIPO_ORDENACAO_DESC);

            $objAtributoAndamentoRN = new AtributoAndamentoRN();
            $objAtributoAndamentoFinal = null;

            foreach ($arrAtividadeDTO as $objAtividadeDTO) {

                $objFiltroAtributoAndamentoDTO->setNumIdAtividade($objAtividadeDTO->getNumIdAtividade());
                $objAtributoAndamentoDTO = $objAtributoAndamentoRN->consultarRN1366($objFiltroAtributoAndamentoDTO);

                if (!empty($objAtributoAndamentoDTO)) {
                    $objAtributoAndamentoFinal = $objAtributoAndamentoDTO;
                }
            }
            $objAtributoAndamentoDTO = $objAtributoAndamentoFinal;

            $strUnidadeDestino = array_pop(array_pop(PaginaSEI::getInstance()->getArrOptionsSelect($objAtributoAndamentoDTO->getStrValor())));

            return "<br/>".sprintf('Processo em trâmite externo para "%s".', $strUnidadeDestino);
       
        }
    }
    
    
    public static function getDiretorio(){
        
        if(empty(static::$strDiretorio)) {
            
            $arrModulos = ConfiguracaoSEI::getInstance()->getValor('SEI','Modulos');

            $strModuloPath = realpath($arrModulos['PEN']);
            static::$strDiretorio = str_replace(realpath(__DIR__.'/../..'), '', $strModuloPath);
            static::$strDiretorio = preg_replace('/^\//', '', static::$strDiretorio);            
        }
        
        return static::$strDiretorio;
    }
}
