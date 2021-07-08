<?php

class PENIntegracao extends SeiIntegracao
{
    const VERSAO_MODULO = "3.0.1";
    const PARAMETRO_VERSAO_MODULO_ANTIGO = 'PEN_VERSAO_MODULO_SEI';
    const PARAMETRO_VERSAO_MODULO = 'VERSAO_MODULO_PEN';

    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new PENIntegracao();
        }

        return self::$instance;
    }

    public function getNome() {
        return 'Integração Processo Eletrônico Nacional - PEN';
    }


    public function getVersao() {
        return self::VERSAO_MODULO;
    }


    public function getInstituicao() {
        return 'Ministério da Economia - ME (Projeto Colaborativo no Portal do SPB)';
    }


    public function inicializar($strVersaoSEI)
    {
        define('DIR_SEI_WEB', realpath(DIR_SEI_CONFIG.'/../web'));

        // Aplicação de validações pertinentes à instalação e inicialização do módulo
        // Regras de verificação da disponibilidade do PEN não devem ser aplicadas neste ponto pelo risco de erro geral no sistema em
        // caso de indisponibilidade momentânea do Barramento de Serviços.
        PENIntegracao::validarArquivoConfiguracao();
        PENIntegracao::validarCompatibilidadeModulo($strVersaoSEI);
    }


    public function montarBotaoProcesso(ProcedimentoAPI $objSeiIntegracaoDTO)
    {
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
                $strAcoesProcedimento .= '<a id="validar_expedir_processo" href="' . $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_expedir&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_procedimento=' . $dblIdProcedimento . '&arvore=1')) . '" tabindex="' . $numTabBotao . '" class="botaoSEI"><img class="infraCorBarraSistema" src=' . ProcessoEletronicoINT::getCaminhoIcone("/pen_expedir_procedimento.gif",$this->getDiretorioImagens()) . ' alt="Envio Externo de Processo" title="Envio Externo de Processo" /></a>';
            }
        }

        //Apresenta o botão da página de recibos
        if($bolAcaoExpedirProcesso){
            $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
            $objProcessoEletronicoDTO->retDblIdProcedimento();
            $objProcessoEletronicoDTO->setDblIdProcedimento($dblIdProcedimento);
            $objProcessoEletronicoRN = new ProcessoEletronicoRN();
            if($objProcessoEletronicoRN->contar($objProcessoEletronicoDTO) != 0){
                $numTabBotao = $objPaginaSEI->getProxTabBarraComandosSuperior();
                $strAcoesProcedimento .= '<a href="' . $objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_estado&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_procedimento=' . $dblIdProcedimento . '&arvore=1') . '" tabindex="' . $numTabBotao . '" class="botaoSEI">';
                $strAcoesProcedimento .= '<img class="infraCorBarraSistema" src=' . ProcessoEletronicoINT::getCaminhoIcone("/pen_consultar_recibos.png",$this->getDiretorioImagens()) . ' alt="Consultar Recibos" title="Consultar Recibos"/>';
                $strAcoesProcedimento .= '</a>';
            }
        }

        //Apresenta o botão de cancelar trâmite
        $objAtividadeDTO = $objExpedirProcedimentoRN->verificarProcessoEmExpedicao($objSeiIntegracaoDTO->getIdProcedimento());
        if ($objAtividadeDTO && $objAtividadeDTO->getNumIdTarefa() == ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO)) {
            $numTabBotao = $objPaginaSEI->getProxTabBarraComandosSuperior();
            $strAcoesProcedimento .= '<a href="' . $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_cancelar_expedir&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_procedimento=' . $dblIdProcedimento . '&arvore=1')) . '" tabindex="' . $numTabBotao . '" class="botaoSEI">';
            $strAcoesProcedimento .= '<img class="infraCorBarraSistema" src=' . ProcessoEletronicoINT::getCaminhoIcone("/pen_cancelar_tramite.gif",$this->getDiretorioImagens()) . '  alt="Cancelar Tramitação Externa" title="Cancelar Tramitação Externa" />';
            $strAcoesProcedimento .= '</a>';
        }

        return array($strAcoesProcedimento);
    }


    public function montarIconeControleProcessos($arrObjProcedimentoAPI = array())
    {
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

    public function montarIconeProcesso(ProcedimentoAPI $objProcedimentoAP)
    {
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

    public function montarIconeAcompanhamentoEspecial($arrObjProcedimentoDTO)
    {

    }

    public function getDiretorioImagens()
    {
        return static::getDiretorio() . '/imagens';
    }


    public function montarMensagemProcesso(ProcedimentoAPI $objProcedimentoAPI)
    {
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



    public function montarIconeDocumento(ProcedimentoAPI $objProcedimentoAPI, $arrObjDocumentoAPI)
    {
        $arrIcones = array();

        if ($objProcedimentoAPI->getCodigoAcesso() > 0) {
            $objProcessoEletronicoRN = new ProcessoEletronicoRN();
            $objPenRelTipoDocMapRecebidoRN = new PenRelTipoDocMapRecebidoRN();

            $objProcessoEletronicoPesquisaDTO = new ProcessoEletronicoDTO();
            $objProcessoEletronicoPesquisaDTO->setDblIdProcedimento($objProcedimentoAPI->getIdProcedimento());
            $objUltimoTramiteRecebidoDTO = $objProcessoEletronicoRN->consultarUltimoTramiteRecebido($objProcessoEletronicoPesquisaDTO);

            if (!is_null($objUltimoTramiteRecebidoDTO)) {
                if ($objProcessoEletronicoRN->possuiComponentesComDocumentoReferenciado($objUltimoTramiteRecebidoDTO)) {
                    $arrObjComponentesDigitaisDTO = $objProcessoEletronicoRN->listarComponentesDigitais($objUltimoTramiteRecebidoDTO);
                    $arrObjCompIndexadoPorOrdemDTO = InfraArray::indexarArrInfraDTO($arrObjComponentesDigitaisDTO, 'OrdemDocumento');
                    $arrObjCompIndexadoPorIdDocumentoDTO = InfraArray::indexarArrInfraDTO($arrObjComponentesDigitaisDTO, 'IdDocumento');

                    $arrObjDocumentoAPIIndexado = array();
                    foreach ($arrObjDocumentoAPI as $objDocumentoAPI) {
                        $arrObjDocumentoAPIIndexado[$objDocumentoAPI->getIdDocumento()] = $objDocumentoAPI;

                        if ($objDocumentoAPI->getCodigoAcesso() > 0) {
                            $dblIdDocumento = $objDocumentoAPI->getIdDocumento();
                            if (array_key_exists($dblIdDocumento, $arrObjCompIndexadoPorIdDocumentoDTO)) {
                                $objComponenteDTO = $arrObjCompIndexadoPorIdDocumentoDTO[$dblIdDocumento];
                                if (!is_null($objComponenteDTO->getNumOrdemDocumentoReferenciado())) {
                                    $arrIcones[$dblIdDocumento] = array();

                                    $objComponenteReferenciadoDTO = $arrObjCompIndexadoPorOrdemDTO[$objComponenteDTO->getNumOrdemDocumentoReferenciado()];
                                    $objDocumentoReferenciadoAPI = $arrObjDocumentoAPIIndexado[$objComponenteReferenciadoDTO->getDblIdDocumento()];

                                    $strTextoInformativo = sprintf("Anexo do %s \(%s\)",
                                        $objDocumentoReferenciadoAPI->getNomeSerie(),
                                        $objDocumentoReferenciadoAPI->getNumeroProtocolo()
                                    );

                                    $objSerieDTO = $objPenRelTipoDocMapRecebidoRN->obterSerieMapeada($objComponenteDTO->getNumCodigoEspecie());
                                    if(!is_null($objSerieDTO)){
                                        $strTextoInformativo .= " - " . $objSerieDTO->getStrNome();
                                    }

                                    $objArvoreAcaoItemAPI = new ArvoreAcaoItemAPI();
                                    $objArvoreAcaoItemAPI->setTipo('MD_PEN_DOCUMENTO_REFERENCIADO');
                                    $objArvoreAcaoItemAPI->setId('MD_PEN_DOC_REF' . $dblIdDocumento);
                                    $objArvoreAcaoItemAPI->setIdPai($dblIdDocumento);
                                    $objArvoreAcaoItemAPI->setTitle($strTextoInformativo);
                                    $objArvoreAcaoItemAPI->setIcone(ProcessoEletronicoINT::getCaminhoIcone("imagens/anexos.gif"));
                                    $objArvoreAcaoItemAPI->setTarget(null);
                                    $objArvoreAcaoItemAPI->setHref("javascript:alert('$strTextoInformativo');");
                                    $objArvoreAcaoItemAPI->setSinHabilitado('S');

                                    $arrIcones[$dblIdDocumento][] = $objArvoreAcaoItemAPI;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $arrIcones;
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

            case 'pen_map_tipo_documento_envio_listar';
            case 'pen_map_tipo_documento_envio_excluir';
            case 'pen_map_tipo_documento_envio_desativar';
            case 'pen_map_tipo_documento_envio_ativar':
                require_once dirname(__FILE__) . '/pen_map_tipo_documento_envio_listar.php';
            break;

            case 'pen_map_tipo_documento_envio_cadastrar';
            case 'pen_map_tipo_documento_envio_visualizar':
                require_once dirname(__FILE__) . '/pen_map_tipo_documento_envio_cadastrar.php';
            break;

            case 'pen_map_tipo_documento_recebimento_listar';
            case 'pen_map_tipo_documento_recebimento_excluir':
                require_once dirname(__FILE__) . '/pen_map_tipo_documento_recebimento_listar.php';
            break;

            case 'pen_map_tipo_documento_recebimento_cadastrar';
            case 'pen_map_tipo_documento_recebimento_visualizar':
                require_once dirname(__FILE__) . '/pen_map_tipo_documento_recebimento_cadastrar.php';
            break;

            case 'pen_apensados_selecionar_expedir_procedimento':
                require_once dirname(__FILE__) . '/apensados_selecionar_expedir_procedimento.php';
            break;

            case 'pen_unidades_administrativas_externas_selecionar_expedir_procedimento':
                //verifica qual o tipo de seleção passado para carregar o arquivo especifico.
                if($_GET['tipo_pesquisa'] == 1){
                    require_once dirname(__FILE__) . '/pen_unidades_administrativas_selecionar_expedir_procedimento.php';
                }else {
                    require_once dirname(__FILE__) . '/pen_unidades_administrativas_pesquisa_textual_expedir_procedimento.php';
                }
            break;

            case 'pen_procedimento_estado':
                require_once dirname(__FILE__) . '/pen_procedimento_estado.php';
            break;

            // Mapeamento de Hipóteses Legais de Envio
            case 'pen_map_hipotese_legal_envio_cadastrar';
            case 'pen_map_hipotese_legal_envio_visualizar':
                require_once dirname(__FILE__) . '/pen_map_hipotese_legal_envio_cadastrar.php';
            break;

            case 'pen_map_hipotese_legal_envio_listar';
            case 'pen_map_hipotese_legal_envio_excluir':
                require_once dirname(__FILE__) . '/pen_map_hipotese_legal_envio_listar.php';
            break;

            // Mapeamento de Hipóteses Legais de Recebimento
            case 'pen_map_hipotese_legal_recebimento_cadastrar';
            case 'pen_map_hipotese_legal_recebimento_visualizar':
                require_once dirname(__FILE__) . '/pen_map_hipotese_legal_recebimento_cadastrar.php';
            break;

            case 'pen_map_hipotese_legal_recebimento_listar';
            case 'pen_map_hipotese_legal_recebimento_excluir':
                require_once dirname(__FILE__) . '/pen_map_hipotese_legal_recebimento_listar.php';
            break;

            case 'pen_map_hipotese_legal_padrao_cadastrar';
            case 'pen_map_hipotese_legal_padrao_visualizar':
                require_once dirname(__FILE__) . '/pen_map_hipotese_legal_padrao_cadastrar.php';
            break;

            case 'pen_map_unidade_cadastrar';
            case 'pen_map_unidade_visualizar':
                require_once dirname(__FILE__) . '/pen_map_unidade_cadastrar.php';
            break;

            case 'pen_map_unidade_listar';
            case 'pen_map_unidade_excluir':
                require_once dirname(__FILE__) . '/pen_map_unidade_listar.php';
            break;

            case 'pen_parametros_configuracao';
            case 'pen_parametros_configuracao_salvar':
                require_once dirname(__FILE__) . '/pen_parametros_configuracao.php';
            break;

            case 'pen_map_tipo_documento_envio_padrao_atribuir';
            case 'pen_map_tipo_documento_envio_padrao_consultar':
                require_once dirname(__FILE__) . '/pen_map_tipo_documento_envio_padrao.php';
            break;

            case 'pen_map_tipo_doc_recebimento_padrao_atribuir';
            case 'pen_map_tipo_doc_recebimento_padrao_consultar':
                require_once dirname(__FILE__) . '/pen_map_tipo_doc_recebimento_padrao.php';
            break;

            default:
                return false;

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
                $dblIdProcedimentoAtual = $_POST['id_procedimento_atual'];
                $numIdUnidadeAtual = SessaoSEI::getInstance()->getNumIdUnidadeAtual();
                $arrObjProcedimentoDTO = ProcessoEletronicoINT::autoCompletarProcessosApensados($dblIdProcedimentoAtual, $numIdUnidadeAtual, $_POST['palavras_pesquisa']);
                $xml = InfraAjax::gerarXMLItensArrInfraDTO($arrObjProcedimentoDTO, 'IdProtocolo', 'ProtocoloFormatadoProtocolo');
            break;


            case 'pen_procedimento_expedir_validar':
                require_once dirname(__FILE__) . '/pen_procedimento_expedir_validar.php';
            break;

            case 'pen_procedimento_expedir_cancelar':
                $numIdTramite = $_POST['id_tramite'];
                $objProcessoEletronicoRN = new ProcessoEletronicoRN();
                $result = json_encode($objProcessoEletronicoRN->cancelarTramite($numIdTramite));
                InfraAjax::enviarJSON($result);
                exit(0);
            break;

            case 'pen_pesquisar_unidades_administrativas_estrutura_pai':
                $idRepositorioEstruturaOrganizacional = $_POST['idRepositorioEstruturaOrganizacional'];
                $numeroDeIdentificacaoDaEstrutura = $_POST['numeroDeIdentificacaoDaEstrutura'];

                $objProcessoEletronicoRN = new ProcessoEletronicoRN();
                $arrEstruturas = $objProcessoEletronicoRN->consultarEstruturasPorEstruturaPai($idRepositorioEstruturaOrganizacional, $numeroDeIdentificacaoDaEstrutura == "" ? null : $numeroDeIdentificacaoDaEstrutura);

                print json_encode($arrEstruturas);
                exit(0);
            break;


            case 'pen_pesquisar_unidades_administrativas_estrutura_pai_textual':
                $registrosPorPagina = 50;
                $idRepositorioEstruturaOrganizacional = $_POST['idRepositorioEstruturaOrganizacional'];
                $numeroDeIdentificacaoDaEstrutura     = $_POST['numeroDeIdentificacaoDaEstrutura'];
                $siglaUnidade = ($_POST['siglaUnidade'] == '') ? null : utf8_encode($_POST['siglaUnidade']);
                $nomeUnidade  = ($_POST['nomeUnidade']  == '') ? null : utf8_encode($_POST['nomeUnidade']);
                $offset       = $_POST['offset'] * $registrosPorPagina;

                $objProcessoEletronicoRN = new ProcessoEletronicoRN();
                $arrObjEstruturaDTO = $objProcessoEletronicoRN->listarEstruturas($idRepositorioEstruturaOrganizacional, null, $numeroDeIdentificacaoDaEstrutura, $nomeUnidade, $siglaUnidade, $offset, $registrosPorPagina);

                $interface = new ProcessoEletronicoINT();
                //Gera a hierarquia de SIGLAS das estruturas
                $arrHierarquiaEstruturaDTO = $interface->gerarHierarquiaEstruturas($arrObjEstruturaDTO);

                $arrEstruturas['estrutura'] = [];
                if(!is_null($arrHierarquiaEstruturaDTO[0])){
                    foreach ($arrHierarquiaEstruturaDTO as $key => $estrutura) {
                        //Monta um array com as estruturas para retornar o JSON
                        $arrEstruturas['estrutura'][$key]['nome'] = utf8_encode($estrutura->get('Nome'));
                        $arrEstruturas['estrutura'][$key]['numeroDeIdentificacaoDaEstrutura'] = $estrutura->get('NumeroDeIdentificacaoDaEstrutura');
                        $arrEstruturas['estrutura'][$key]['sigla'] = utf8_encode($estrutura->get('Sigla'));
                        $arrEstruturas['estrutura'][$key]['ativo'] = $estrutura->get('Ativo');
                        $arrEstruturas['estrutura'][$key]['aptoParaReceberTramites'] = $estrutura->get('AptoParaReceberTramites');
                        $arrEstruturas['estrutura'][$key]['codigoNoOrgaoEntidade'] = $estrutura->get('CodigoNoOrgaoEntidade');

                    }
                    $arrEstruturas['totalDeRegistros']   = $estrutura->get('TotalDeRegistros');
                    $arrEstruturas['registrosPorPagina'] = $registrosPorPagina;
                }

                print json_encode($arrEstruturas);
                exit(0);
            break;
        }

        return $xml;
    }


    public function processarControladorWebServices($servico)
    {
        $strArq = null;
        switch ($_GET['servico']) {
          case 'modpen':
            $strArq =  dirname(__FILE__) . '/ws/modpen.wsdl';
            break;
        }

        return $strArq;
    }


    /**
    * Método responsável por recuperar a hierarquia da unidade e montar o seu nome com as SIGLAS da hierarquia
    * @param $idRepositorioEstruturaOrganizacional
    * @param $arrEstruturas
    * @return mixed
    * @throws InfraException
    */
    private function obterHierarquiaEstruturaDeUnidadeExterna($idRepositorioEstruturaOrganizacional, $arrEstruturas)
    {
        //Monta o nome da unidade com a hierarquia de SIGLAS
        $objProcessoEletronicoRN = new ProcessoEletronicoRN();
        foreach ($arrEstruturas as $key => $estrutura) {
            if(!is_null($estrutura)) {
                $arrObjEstruturaDTO = $objProcessoEletronicoRN->listarEstruturas($idRepositorioEstruturaOrganizacional, $estrutura->numeroDeIdentificacaoDaEstrutura);
                if (!is_null($arrObjEstruturaDTO[0])) {
                    $interface = new ProcessoEletronicoINT();
                    $arrHierarquiaEstruturaDTO = $interface->gerarHierarquiaEstruturas($arrObjEstruturaDTO);
                    $arrEstruturas[$key]->nome = utf8_encode($arrHierarquiaEstruturaDTO[0]->get('Nome'));
                }
            }
        }

        return $arrEstruturas;
    }

    public static function getDiretorio()
    {
        $arrConfig = ConfiguracaoSEI::getInstance()->getValor('SEI', 'Modulos');
        $strModulo = $arrConfig['PENIntegracao'];
        return "modulos/".$strModulo;
    }

    /**
    * Método responsável pela validação da compatibilidade do banco de dados do módulo em relação ao versão instalada
    *
    * @param  boolean $bolGerarExcecao Flag para geração de exceção do tipo InfraException caso base de dados incompatível
    * @return boolean Indicardor se base de dados é compatível
    */
    public static function validarCompatibilidadeBanco($bolGerarExcecao=true)
    {
        $objVerificadorInstalacaoRN = new VerificadorInstalacaoRN();
        return $objVerificadorInstalacaoRN->verificarCompatibilidadeModulo($bolGerarExcecao);
    }


    /**
    * Método responsável pela validação da compatibilidade do módulo em relação à versão oficial do SEI
    *
    * @param string $parStrVersaoSEI
    * @return void
    */
    public static function validarCompatibilidadeModulo($parStrVersaoSEI=null)
    {
        $objVerificadorInstalacaoRN = new VerificadorInstalacaoRN();
        $objVerificadorInstalacaoRN->verificarCompatibilidadeModulo();
    }


    /**
    * Método responsável pela validação da compatibilidade do módulo em relação à versão oficial do SEI
    *
    * @param string $parStrVersaoSEI
    * @return void
    */
    public static function validarArquivoConfiguracao()
    {
        $objVerificadorInstalacaoRN = new VerificadorInstalacaoRN();
        $objVerificadorInstalacaoRN->verificarArquivoConfiguracao();
    }

    public function processarPendencias()
    {
        SessaoSEI::getInstance(false);
        ProcessarPendenciasRN::getInstance()->processarPendencias();
    }
}

class ModuloIncompativelException extends InfraException { }
