<?php
try {
    require_once DIR_SEI_WEB . '/SEI.php';

    session_start();

    //////////////////////////////////////////////////////////////////////////////
    //InfraDebug::getInstance()->setBolLigado(true);
    //InfraDebug::getInstance()->setBolDebugInfra(true);
    //InfraDebug::getInstance()->limpar();
    //////////////////////////////////////////////////////////////////////////////

    $objSessaoSEI = SessaoSEI::getInstance();
    $objPaginaSEI = PaginaSEI::getInstance();
    $objInfraException = new InfraException();

    $objSessaoSEI->validarLink();
    $objSessaoSEI->validarPermissao($_GET['acao']);

    $strParametros = '';
    $bolErrosValidacao = false;
    $strDiretorioModulo = PENIntegracao::getDiretorio();

    if (isset($_GET['arvore'])) {
        $objPaginaSEI->setBolArvore($_GET['arvore']);
        $strParametros .= '&arvore=' . $_GET['arvore'];
    }

    $strLinkValidacao = $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=pen_map_orgaos_externos_salvar&acao_origem=' . $_GET['acao'] . $strParametros));
    
    //Preparação dos dados para montagem da tela de expedição de processos
    $objExpedirProcedimentosRN = new ExpedirProcedimentoRN();
    $repositorios = $objExpedirProcedimentosRN->listarRepositoriosDeEstruturas();

    $objUnidadeDTO = new PenUnidadeDTO();
    $objUnidadeDTO->retNumIdUnidadeRH();
    $objUnidadeDTO->setNumIdUnidade($objSessaoSEI->getNumIdUnidadeAtual());

    $objUnidadeRN = new UnidadeRN();
    $objUnidadeDTO = $objUnidadeRN->consultarRN0125($objUnidadeDTO);

    if (!$objUnidadeDTO) {
        throw new InfraException("A unidade atual não foi mapeada.");
    }

    $strNomeUnidadeDestino = $_POST['txtUnidade'];
    $numIdRepositorio = $_POST['selRepositorioEstruturas'];
    $txtRepositorioEstruturas = $_POST['txtRepositorioEstruturas'];
    $numIdUnidadeOrigem = $objUnidadeDTO->getNumIdUnidadeRH();
    $boolSinExtenderSubUnidades = $objPaginaSEI->getCheckbox($_POST['chkSinExtenderSubUnidades'], true, false);

    switch ($_GET['acao']) {
        case 'pen_map_orgaos_externos_salvar':
            if (is_null($numIdRepositorio)) {
                $objPaginaSEI->adicionarMensagem('selecione um repositório de destino.');
            } else {
                $objPenOrgaoExternoDTO = new PenOrgaoExternoDTO();
                $objPenOrgaoExternoDTO->setNumIdUnidade($objSessaoSEI->getNumIdUnidadeAtual());
                $objPenOrgaoExternoDTO->setNumIdOrgao($numIdRepositorio);
                $objPenOrgaoExternoDTO->setNumMaxRegistrosRetorno(1);

                $objPenOrgaoExternoRN = new PenOrgaoExternoRN();
                $respObjPenOrgaoExternoDTO = $objPenOrgaoExternoRN->contar($objPenOrgaoExternoDTO);
                if ($respObjPenOrgaoExternoDTO > 0) {
                    $objPaginaSEI->adicionarMensagem('Orgão externo ja cadastrado.');
                    header('Location: '.$objSessaoSEI->assinarLink('controlador.php?acao=pen_map_orgaos_externos_cadastrar&acao_origem='.$_GET['acao'] . $strParametros));
                    exit(0);
                }

                $boolSinExtenderSubUnidades = !empty($boolSinExtenderSubUnidades) && $boolSinExtenderSubUnidades ? 'S' : 'N';
                $objPenOrgaoExternoDTO = new PenOrgaoExternoDTO();
                $objPenOrgaoExternoDTO->setNumIdUnidade($objSessaoSEI->getNumIdUnidadeAtual());
                $objPenOrgaoExternoDTO->setNumIdOrgao($numIdRepositorio);
                $objPenOrgaoExternoDTO->setStrOrgao($txtRepositorioEstruturas);
                $objPenOrgaoExternoDTO->setDthRegistro(date('d/m/Y H:i:s'));
                $objPenOrgaoExternoDTO->setStrExtenderSubUnidades($boolSinExtenderSubUnidades);

                $objPenOrgaoExternoRN = new PenOrgaoExternoRN();
                $respObjPenOrgaoExternoDTO = $objPenOrgaoExternoRN->contar($objPenOrgaoExternoDTO);
                if ($respObjPenOrgaoExternoDTO > 0) {
                    var_dump($respObjPenOrgaoExternoDTO);
                    exit;
                }

                $objPenOrgaoExternoRN = new PenOrgaoExternoRN();
                $objPenOrgaoExternoRN->cadastrar($objPenOrgaoExternoDTO);

                $objPaginaSEI->adicionarMensagem('Orgão externo cadastrado com sucesso.');
            }

            header('Location: '.$objSessaoSEI->assinarLink('controlador.php?acao=pen_map_orgaos_externos_cadastrar&acao_origem='.$_GET['acao'] . $strParametros));
            exit(0);
            break;
        case 'pen_map_orgaos_externos_cadastrar':
            $strTitulo = 'Cadastro Orgão Externo';

            //Monta os botões do topo
            if ($objSessaoSEI->verificarPermissao('pen_map_orgaos_externos_cadastrar')) {
                $arrComandos[] = '<button type="submit" id="btnSalvar" value="Salvar" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
            }
            $arrComandos[] = '<button type="button" id="btnCancelar" value="Cancelar" onclick="location.href=\'' . $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=pen_parametros_configuracao&acao_origem=' . $_GET['acao'])) . '\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';

            //Obter dados do repositório em que o SEI está registrado (Repositório de Origem)
            $objPenParametroRN = new PenParametroRN();
            $numIdRepositorioOrigem = $objPenParametroRN->getParametro('PEN_ID_REPOSITORIO_ORIGEM');

            $motivosDeUrgencia = $objExpedirProcedimentosRN->consultarMotivosUrgencia();

            $idRepositorioSelecionado = (isset($numIdRepositorio)) ? $numIdRepositorio : '';
            $strItensSelRepositorioEstruturas = InfraINT::montarSelectArray('', 'Selecione', $idRepositorioSelecionado, $repositorios);

            $strLinkAjaxUnidade = $objSessaoSEI->assinarLink('controlador_ajax.php?acao_ajax=pen_unidade_auto_completar_expedir_procedimento&acao=' . $_GET['acao']);
            $strLinkAjaxProcedimentoApensado = $objSessaoSEI->assinarLink('controlador_ajax.php?acao_ajax=pen_apensados_auto_completar_expedir_procedimento');
            $strLinkUnidadesAdministrativasSelecao = $objSessaoSEI->assinarLink('controlador.php?acao=pen_unidades_administrativas_externas_selecionar_expedir_procedimento&tipo_pesquisa=1&id_object=objLupaUnidadesAdministrativas&idRepositorioEstrutura=1');
            break;
        default:
            throw new InfraException("Ação '" . $_GET['acao'] . "' não reconhecida.");
    }
} catch (Exception $e) {
    throw new InfraException("Erro processando requisição de envio externo de processo", $e);
}

$objPaginaSEI->montarDocType();
$objPaginaSEI->abrirHtml();
$objPaginaSEI->abrirHead();
$objPaginaSEI->montarMeta();
$objPaginaSEI->montarTitle(':: ' . $objPaginaSEI->getStrNomeSistema() . ' - ' . $strTitulo . ' ::');
$objPaginaSEI->montarStyle();
echo "<link href='$strDiretorioModulo/css/" . ProcessoEletronicoINT::getCssCompatibilidadeSEI4("pen_procedimento_expedir.css") . "' rel='stylesheet' type='text/css' media='all' />\n";

$objPaginaSEI->abrirStyle();
?>

div.infraAreaDados {
margin-bottom: 10px;
}

#lblProtocoloExibir {position:absolute;left:0%;top:0%;}
#txtProtocoloExibir {position:absolute;left:0%;top:38%;width:50%;}

#lblRepositorioEstruturas {position:absolute;left:0%;top:0%;width:50%;}
#selRepositorioEstruturas {position:absolute;left:0%;top:38%;width:51%;}

.alinhamentoBotaoImput{position:absolute;left:0%;top:48%;width:85%;};

#imgPesquisaAvancada {
vertical-align: middle;
margin-left: 10px;
width: 20px;
height: 20px;
}

#lblProcedimentosApensados {position:absolute;left:0%;top:0%;}
#txtProcedimentoApensado {position:absolute;left:0%;top:25%;width:50%;border:.1em solid #666;}

#imgLupaProcedimentosApensados {position:absolute;left:87%;top:43%;}
#imgExcluirProcedimentosApensados {position:absolute;left:87%;top:60%;}

<?php
$objPaginaSEI->fecharStyle();
$objPaginaSEI->montarJavaScript();
?>
<script type="text/javascript">
    var idRepositorioEstrutura = null;
    var objAutoCompletarUnidade = null;
    var objAutoCompletarEstrutura = null;
    var objAutoCompletarProcedimentosApensados = null;


    function inicializar() {
        infraEfeitoTabelas();
        var strMensagens = '<?php print str_replace("\n", '\n', $objPaginaSEI->getStrMensagens()); ?>';
        if(strMensagens) {
            alert(strMensagens);
        }

        //Botão de pesquisa avançada
        $('#imgPesquisaAvancada').click(function() {
            var idRepositorioEstrutura = $('#selRepositorioEstruturas :selected').val();
            if ((idRepositorioEstrutura != '') && (idRepositorioEstrutura != 'null')) {
                $("#hdnUnidadesAdministrativas").val(idRepositorioEstrutura);
                objLupaUnidadesAdministrativas.selecionar(700, 500);
            } else {
                alert('Selecione um repositório de Estruturas Organizacionais');
            }
        });

        document.getElementById('selRepositorioEstruturas').focus();
    }


    function selecionarRepositorio() {
        $('#txtRepositorioEstruturas').val($("#selRepositorioEstruturas option:selected").text());
    }
</script>
<?php
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody($strTitulo, 'onload="inicializar();"');
?>
<form id="frmGravarOrgaoExterno" name="frmGravarOrgaoExterno" method="post" action="<?= $strLinkValidacao ?>">
    <?php
    $objPaginaSEI->montarBarraComandosSuperior($arrComandos);
    ?>

    <div id="divRepositorioEstruturas" class="infraAreaDados" style="height: 4.5em;">
        <label id="lblRepositorioEstruturas" for="selRepositorioEstruturas" accesskey="" class="infraLabelObrigatorio">Repositório de Estruturas Organizacionais:</label>
        <select id="selRepositorioEstruturas" name="selRepositorioEstruturas" class="infraSelect" onchange="selecionarRepositorio();" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>">
            <?= $strItensSelRepositorioEstruturas ?>
        </select>
        
        <input type="hidden" id="txtRepositorioEstruturas" name="txtRepositorioEstruturas" class="infraText" value="<?= $txtRepositorioEstruturas; ?>" />
    </div>

    <div id="divSinExtenderSubUnidades" class="infraAreaDados" style="height: 4.5em;">
        <input type="checkbox" id="chkSinExtenderSubUnidades" name="chkSinExtenderSubUnidades" class="infraCheckbox" <?= $objPaginaSEI->setCheckbox($boolSinExtenderSubUnidades) ?> tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />
        <label id="lblSinExtenderSubUnidades" for="chkSinExtenderSubUnidades" class="infraLabelCheckbox">Extender para subunidades?</label>
    </div>

    <input type="hidden" id="hdnErrosValidacao" name="hdnErrosValidacao" value="<?= $bolErrosValidacao ?>" />
    <input type="hidden" id="hdnUnidadesAdministrativas" name="hdnUnidadesAdministrativas" value="" />
</form>
<?php
$objPaginaSEI->montarAreaDebug();
$objPaginaSEI->fecharBody();
$objPaginaSEI->fecharHtml();
?>