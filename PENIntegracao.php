<?php

class PENIntegracao extends SeiIntegracao {

    const COMPATIBILIDADE_MODULO_SEI = array('3.0.5', '3.0.6', '3.0.7', '3.0.8', '3.0.9', '3.0.11', '3.0.12', '3.0.13', '3.0.14', '3.0.15', '3.1.0');

    private static $strDiretorio;

    public function getNome() {
        return 'Integração Processo Eletrônico Nacional - PEN';
    }

    public function getVersao() {
        return '1.2.1';
    }

    public function getInstituicao() {
        return 'Ministério do Planejamento - MPDG (Projeto Colaborativo no Portal do SPB)';
    }

    public function montarBotaoProcesso(ProcedimentoAPI $objSeiIntegracaoDTO) {

        $objProcedimentoDTO = new ProcedimentoDTO();
        $objProcedimentoDTO->setDblIdProcedimento($objSeiIntegracaoDTO->getIdProcedimento());
        $objProcedimentoDTO->retTodos();

        $objProcedimentoRN = new ProcedimentoRN();
        $objProcedimentoDTO = $objProcedimentoRN->consultarRN0201($objProcedimentoDTO);

        $objSessaoSEI = SessaoSEI::getInstance();
        $objPaginaSEI = PaginaSEI::getInstance();
        $strAcoesProcedimento = "";

        $dblIdProcedimento = $objProcedimentoDTO->getDblIdProcedimento();
        $numIdUsuario = SessaoSEI::getInstance()->getNumIdUsuario();
        $numIdUnidadeAtual = SessaoSEI::getInstance()->getNumIdUnidadeAtual();
        $objInfraParametro = new InfraParametro(BancoSEI::getInstance());

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
        $objExpedirProcedimentoRN = new ExpedirProcedimentoRN();
        $objProcedimentoDTO = $objExpedirProcedimentoRN->consultarProcedimento($dblIdProcedimento);

        $bolProcessoEstadoNormal = !in_array($objProcedimentoDTO->getStrStaEstadoProtocolo(), array(
                    ProtocoloRN::$TE_PROCEDIMENTO_SOBRESTADO,
                    ProtocoloRN::$TE_PROCEDIMENTO_BLOQUEADO
        ));

        //Apresenta o botão de expedir processo
        if ($bolFlagAberto && $bolAcaoExpedirProcesso && $bolProcessoEstadoNormal && $objProcedimentoDTO->getStrStaNivelAcessoGlobalProtocolo() != ProtocoloRN::$NA_SIGILOSO) {

            $objPenUnidadeDTO = new PenUnidadeDTO();
            $objPenUnidadeDTO->retNumIdUnidade();
            $objPenUnidadeDTO->setNumIdUnidade($numIdUnidadeAtual);
            $objPenUnidadeRN = new PenUnidadeRN();

            if($objPenUnidadeRN->contar($objPenUnidadeDTO) != 0) {
                $numTabBotao = $objPaginaSEI->getProxTabBarraComandosSuperior();
                $strAcoesProcedimento .= '<a id="validar_expedir_processo" href="' . $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_expedir&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_procedimento=' . $dblIdProcedimento . '&arvore=1')) . '" tabindex="' . $numTabBotao . '" class="botaoSEI"><img class="infraCorBarraSistema" src="' . $this->getDiretorioImagens() . '/pen_expedir_procedimento.gif" alt="Envio Externo de Processo" title="Envio Externo de Processo" /></a>';
            }
        }

        //Apresenta o botão da página de recibos
        if($bolAcaoExpedirProcesso){
            $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
            $objProcessoEletronicoDTO->retDblIdProcedimento();
            $objProcessoEletronicoDTO->setDblIdProcedimento($dblIdProcedimento);
            $objProcessoEletronicoRN = new ProcessoEletronicoRN();
            if($objProcessoEletronicoRN->contar($objProcessoEletronicoDTO) != 0){
                $strAcoesProcedimento .= '<a href="' . $objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_estado&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_procedimento=' . $dblIdProcedimento . '&arvore=1') . '" tabindex="' . $numTabBotao . '" class="botaoSEI">';
                $strAcoesProcedimento .= '<img class="infraCorBarraSistema" src="' . $this->getDiretorioImagens() . '/pen_consultar_recibos.png" alt="Consultar Recibos" title="Consultar Recibos"/>';
                $strAcoesProcedimento .= '</a>';
            }
        }

        //Apresenta o botão de cancelar trâmite
        $objAtividadeDTO = $objExpedirProcedimentoRN->verificarProcessoEmExpedicao($objSeiIntegracaoDTO->getIdProcedimento());
        if ($objAtividadeDTO && $objAtividadeDTO->getNumIdTarefa() == ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO)) {
            $strAcoesProcedimento .= '<a href="' . $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_cancelar_expedir&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_procedimento=' . $dblIdProcedimento . '&arvore=1')) . '" tabindex="' . $numTabBotao . '" class="botaoSEI">';
            $strAcoesProcedimento .= '<img class="infraCorBarraSistema" src="' . $this->getDiretorioImagens() . '/pen_cancelar_tramite.gif" alt="Cancelar Tramitação Externa" title="Cancelar Tramitação Externa" />';
            $strAcoesProcedimento .= '</a>';
         }

        return array($strAcoesProcedimento);
    }

    public function montarIconeControleProcessos($arrObjProcedimentoAPI = array()) {

        $arrStrIcone = array();
        $arrDblIdProcedimento = array();

        foreach ($arrObjProcedimentoAPI as $ObjProcedimentoAPI) {
            $arrDblIdProcedimento[] = $ObjProcedimentoAPI->getIdProcedimento();
        }

        $objProcedimentoDTO = new ProcedimentoDTO();
        $objProcedimentoDTO->setDblIdProcedimento($arrDblIdProcedimento, InfraDTO::$OPER_IN);
        $objProcedimentoDTO->retDblIdProcedimento();
        $objProcedimentoDTO->retStrStaEstadoProtocolo();

        $objProcedimentoBD = new ProcedimentoBD(BancoSEI::getInstance());
        $arrObjProcedimentoDTO = $objProcedimentoBD->listar($objProcedimentoDTO);

        if (!empty($arrObjProcedimentoDTO)) {

            foreach ($arrObjProcedimentoDTO as $objProcedimentoDTO) {

                $dblIdProcedimento = $objProcedimentoDTO->getDblIdProcedimento();
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

        return $arrStrIcone;
    }

    public function montarIconeProcesso(ProcedimentoAPI $objProcedimentoAP) {
        $dblIdProcedimento = $objProcedimentoAP->getIdProcedimento();

        $objArvoreAcaoItemAPI = new ArvoreAcaoItemAPI();
        $objArvoreAcaoItemAPI->setTipo('MD_TRAMITE_PROCESSO');
        $objArvoreAcaoItemAPI->setId('MD_TRAMITE_PROC_' . $dblIdProcedimento);
        $objArvoreAcaoItemAPI->setIdPai($dblIdProcedimento);
        $objArvoreAcaoItemAPI->setTitle('Um trâmite para esse processo foi recusado');
        $objArvoreAcaoItemAPI->setIcone($this->getDiretorioImagens() . '/pen_tramite_recusado.png');

        $objArvoreAcaoItemAPI->setTarget(null);
        $objArvoreAcaoItemAPI->setHref('javascript:alert(\'Um trâmite para esse processo foi recusado\');');

        $objArvoreAcaoItemAPI->setSinHabilitado('S');

        $objProcedimentoDTO = new ProcedimentoDTO();
        $objProcedimentoDTO->setDblIdProcedimento($dblIdProcedimento);
        $objProcedimentoDTO->retDblIdProcedimento();
        $objProcedimentoDTO->retStrStaEstadoProtocolo();

        $objProcedimentoBD = new ProcedimentoBD(BancoSEI::getInstance());
        $arrObjProcedimentoDTO = $objProcedimentoBD->consultar($objProcedimentoDTO);

        if (!empty($arrObjProcedimentoDTO)) {
            $dblIdProcedimento = $objProcedimentoDTO->getDblIdProcedimento();
            $objPenProtocoloDTO = new PenProtocoloDTO();
            $objPenProtocoloDTO->setDblIdProtocolo($dblIdProcedimento);
            $objPenProtocoloDTO->retStrSinObteveRecusa();
            $objPenProtocoloDTO->setNumMaxRegistrosRetorno(1);

            $objProtocoloBD = new ProtocoloBD(BancoSEI::getInstance());
            $objPenProtocoloDTO = $objProtocoloBD->consultar($objPenProtocoloDTO);

            if (!empty($objPenProtocoloDTO) && $objPenProtocoloDTO->getStrSinObteveRecusa() == 'S') {
                $arrObjArvoreAcaoItemAPI[] = $objArvoreAcaoItemAPI;
            }
        } else {
            return array();
        }

        return $arrObjArvoreAcaoItemAPI;
    }

    public function montarIconeAcompanhamentoEspecial($arrObjProcedimentoDTO) {

    }

    public function getDiretorioImagens() {
        return static::getDiretorio() . '/imagens';
    }

    public function montarMensagemProcesso(ProcedimentoAPI $objProcedimentoAPI) {

        $objExpedirProcedimentoRN = new ExpedirProcedimentoRN();
        $objAtividadeDTO = $objExpedirProcedimentoRN->verificarProcessoEmExpedicao($objProcedimentoAPI->getIdProcedimento());

        if ($objAtividadeDTO && $objAtividadeDTO->getNumIdTarefa() == ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO)) {

            $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
            $objAtributoAndamentoDTO->setStrNome('UNIDADE_DESTINO');
            $objAtributoAndamentoDTO->setNumIdAtividade($objAtividadeDTO->getNumIdAtividade());
            $objAtributoAndamentoDTO->retStrValor();

            $objAtributoAndamentoRN = new AtributoAndamentoRN();
            $objAtributoAndamentoDTO = $objAtributoAndamentoRN->consultarRN1366($objAtributoAndamentoDTO);

            return sprintf('Processo em trâmite externo para "%s".', $objAtributoAndamentoDTO->getStrValor());
        }
    }

    public static function getDiretorio() {
        $arrConfig = ConfiguracaoSEI::getInstance()->getValor('SEI', 'Modulos');
        $strModulo = $arrConfig['PENIntegracao'];
        return "modulos/".$strModulo;
    }

    public function processarControlador($strAcao)
    {
        //Configuração de páginas do contexto da árvore do processo para apresentação de erro de forma correta
        $bolArvore = in_array($strAcao, array('pen_procedimento_expedir', 'pen_procedimento_estado'));
        PaginaSEI::getInstance()->setBolArvore($bolArvore);

        if (strpos($strAcao, 'pen_') === false) {
            return false;
        }

        PENIntegracao::validarCompatibilidadeModulo();

        switch ($strAcao) {
            case 'pen_procedimento_expedir':
                require_once dirname(__FILE__) . '/pen_procedimento_expedir.php';
                break;

            case 'pen_unidade_sel_expedir_procedimento':
                require_once dirname(__FILE__) . '/pen_unidade_sel_expedir_procedimento.php';
                break;

            case 'pen_procedimento_processo_anexado':
                require_once dirname(__FILE__) . '/pen_procedimento_processo_anexado.php';
                break;

            case 'pen_procedimento_cancelar_expedir':
                require_once dirname(__FILE__) . '/pen_procedimento_cancelar_expedir.php';
                break;

            case 'pen_procedimento_expedido_listar':
                require_once dirname(__FILE__) . '/pen_procedimento_expedido_listar.php';
                break;

            case 'pen_map_tipo_documento_envio_listar':
            case 'pen_map_tipo_documento_envio_excluir':
            case 'pen_map_tipo_documento_envio_desativar':
            case 'pen_map_tipo_documento_envio_ativar':
                require_once dirname(__FILE__) . '/pen_map_tipo_documento_envio_listar.php';
                break;

            case 'pen_map_tipo_documento_envio_cadastrar':
            case 'pen_map_tipo_documento_envio_visualizar':
                require_once dirname(__FILE__) . '/pen_map_tipo_documento_envio_cadastrar.php';
                break;

            case 'pen_map_tipo_documento_recebimento_listar':
            case 'pen_map_tipo_documento_recebimento_excluir':
                require_once dirname(__FILE__) . '/pen_map_tipo_documento_recebimento_listar.php';
                break;

            case 'pen_map_tipo_documento_recebimento_cadastrar':
            case 'pen_map_tipo_documento_recebimento_visualizar':
                require_once dirname(__FILE__) . '/pen_map_tipo_documento_recebimento_cadastrar.php';
                break;

            case 'pen_apensados_selecionar_expedir_procedimento':
                require_once dirname(__FILE__) . '/apensados_selecionar_expedir_procedimento.php';
                break;

            case 'pen_procedimento_estado':
                require_once dirname(__FILE__) . '/pen_procedimento_estado.php';
                break;

            // Mapeamento de Hipóteses Legais de Envio
            case 'pen_map_hipotese_legal_envio_cadastrar':
            case 'pen_map_hipotese_legal_envio_visualizar':
                require_once dirname(__FILE__) . '/pen_map_hipotese_legal_envio_cadastrar.php';
                break;

            case 'pen_map_hipotese_legal_envio_listar':
            case 'pen_map_hipotese_legal_envio_excluir':
                require_once dirname(__FILE__) . '/pen_map_hipotese_legal_envio_listar.php';
                break;

            // Mapeamento de Hipóteses Legais de Recebimento
            case 'pen_map_hipotese_legal_recebimento_cadastrar':
            case 'pen_map_hipotese_legal_recebimento_visualizar':
                require_once dirname(__FILE__) . '/pen_map_hipotese_legal_recebimento_cadastrar.php';
                break;

            case 'pen_map_hipotese_legal_recebimento_listar':
            case 'pen_map_hipotese_legal_recebimento_excluir':
                require_once dirname(__FILE__) . '/pen_map_hipotese_legal_recebimento_listar.php';
                break;

            case 'pen_map_hipotese_legal_padrao_cadastrar':
            case 'pen_map_hipotese_legal_padrao_visualizar':
                require_once dirname(__FILE__) . '/pen_map_hipotese_legal_padrao_cadastrar.php';
                break;

            case 'pen_map_unidade_cadastrar':
            case 'pen_map_unidade_visualizar':
                require_once dirname(__FILE__) . '/pen_map_unidade_cadastrar.php';
                break;

            case 'pen_map_unidade_listar':
            case 'pen_map_unidade_excluir':
                require_once dirname(__FILE__) . '/pen_map_unidade_listar.php';
                break;

            case 'pen_parametros_configuracao':
            case 'pen_parametros_configuracao_salvar':
                require_once dirname(__FILE__) . '/pen_parametros_configuracao.php';
                break;
            default:
                return false;
                break;
        }

        return true;
    }

    public function processarControladorAjax($strAcao) {
        $xml = null;

        switch ($_GET['acao_ajax']) {

            case 'pen_unidade_auto_completar_expedir_procedimento':
                $arrObjEstruturaDTO = (array) ProcessoEletronicoINT::autoCompletarEstruturas($_POST['id_repositorio'], $_POST['palavras_pesquisa']);

                if (count($arrObjEstruturaDTO) > 0) {
                    $xml = InfraAjax::gerarXMLItensArrInfraDTO($arrObjEstruturaDTO, 'NumeroDeIdentificacaoDaEstrutura', 'Nome');
                } else {
                    return '<itens><item id="0" descricao="Unidade não Encontrada."></item></itens>';
                }
                break;

            case 'pen_apensados_auto_completar_expedir_procedimento':
                //TODO: Validar parâmetros passados via ajax
                $dblIdProcedimentoAtual = $_POST['id_procedimento_atual'];
                $numIdUnidadeAtual = SessaoSEI::getInstance()->getNumIdUnidadeAtual();
                $arrObjProcedimentoDTO = ProcessoEletronicoINT::autoCompletarProcessosApensados($dblIdProcedimentoAtual, $numIdUnidadeAtual, $_POST['palavras_pesquisa']);
                $xml = InfraAjax::gerarXMLItensArrInfraDTO($arrObjProcedimentoDTO, 'IdProtocolo', 'ProtocoloFormatadoProtocolo');
                break;

            case 'pen_procedimento_expedir_validar':
                require_once dirname(__FILE__) . '/pen_procedimento_expedir_validar.php';
                break;
        }

        return $xml;
    }

    public static function validarCompatibilidadeModulo($parStrVersaoSEI=null)
    {
        $strVersaoSEI =  $parStrVersaoSEI ?: SEI_VERSAO;
        $objPENIntegracao = new PENIntegracao();
        if(!in_array($strVersaoSEI, self::COMPATIBILIDADE_MODULO_SEI)) {
            throw new InfraException(sprintf("Módulo %s (versão %s) não é compatível com a versão %s do SEI.", $objPENIntegracao->getNome(), $objPENIntegracao->getVersao(), $strVersaoSEI));
        }
    }

    /**
     * Método responsável pela validação da compatibilidade do banco de dados do módulo em relação ao versão instalada.
     *
     * @param  boolean $bolGerarExcecao Flag para geração de exceção do tipo InfraException caso base de dados incompatível
     * @return boolean                  Indicardor se base de dados é compatível
     */
    public static function validarCompatibilidadeBanco($bolGerarExcecao=true)
    {
        $objInfraParametro = new InfraParametro(BancoSEI::getInstance());
        $strVersaoBancoModulo = $objInfraParametro->getValor(PenAtualizarSeiRN::PARAMETRO_VERSAO_MODULO, false) ?: $objInfraParametro->getValor(PenAtualizarSeiRN::PARAMETRO_VERSAO_MODULO_ANTIGO, false);

        $objPENIntegracao = new PENIntegracao();
        $strVersaoModulo = $objPENIntegracao->getVersao();

        $bolBaseCompativel = ($strVersaoModulo === $strVersaoBancoModulo);

        if(!$bolBaseCompativel && $bolGerarExcecao){
            throw new ModuloIncompativelException(sprintf("Base de dados do módulo '%s' (versão %s) encontra-se incompatível. A versão da base de dados atualmente instalada é a %s. \n ".
                "Favor entrar em contato com o administrador do sistema.", $objPENIntegracao->getNome(), $strVersaoModulo, $strVersaoBancoModulo));
        }

        return $bolBaseCompativel;
    }
}

class ModuloIncompativelException extends InfraException { }
