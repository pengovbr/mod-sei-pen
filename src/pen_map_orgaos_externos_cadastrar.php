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

    $numIdOrgao = $_POST['hdnIdUnidade'];
    $strNomeOrgaoDestino = $_POST['txtUnidade'];
    $numIdRepositorio = $_POST['selRepositorioEstruturas'];
    $txtRepositorioEstruturas = $_POST['txtRepositorioEstruturas'];
    $numIdUnidadeOrigem = $objUnidadeDTO->getNumIdUnidadeRH();
    $boolSinExtenderSubUnidades = $objPaginaSEI->getCheckbox($_POST['chkSinExtenderSubUnidades'], true, false);

    switch ($_GET['acao']) {
        case 'pen_map_orgaos_externos_salvar':
            if (is_null($numIdRepositorio)) {
                $objPaginaSEI->adicionarMensagem('selecione um repositório de destino.');
            } elseif (is_null($numIdOrgao)) {
                $objPaginaSEI->adicionarMensagem('o orgão não foi selecionado.');
            } else {
                $objPenOrgaoExternoDTO = new PenOrgaoExternoDTO();
                $objPenOrgaoExternoDTO->setNumIdUnidade($objSessaoSEI->getNumIdUnidadeAtual());
                $objPenOrgaoExternoDTO->setNumIdOrgao($numIdOrgao);
                $objPenOrgaoExternoDTO->setNumIdEstrutaOrganizacional($numIdRepositorio);
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
                $objPenOrgaoExternoDTO->setNumIdOrgao($numIdOrgao);
                $objPenOrgaoExternoDTO->setStrOrgao($strNomeOrgaoDestino);
                $objPenOrgaoExternoDTO->setStrExtenderSubUnidades($boolSinExtenderSubUnidades);
                $objPenOrgaoExternoDTO->setNumIdEstrutaOrganizacional($numIdRepositorio);
                $objPenOrgaoExternoDTO->setStrEstrutaOrganizacional($txtRepositorioEstruturas);
                $objPenOrgaoExternoDTO->setDthRegistro(date('d/m/Y H:i:s'));

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

#lblUnidades {position:absolute;left:0%;top:0%;}
#txtUnidade {left:0%;top:38%;width:50%;border:.1em solid #666;}
#imgLupaUnidades {position:absolute;left:52%;top:48%;}

.alinhamentoBotaoImput{position:absolute;left:0%;top:48%;width:85%;};

#btnIdUnidade {float: right;}
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

    var objLupaUnidades = null;
    var objLupaUnidadesAdministrativas = null;
    var objLupaProcedimentosApensados = null;
    var objJanelaExpedir = null;
    var evnJanelaExpedir = null;

    function inicializar() {
        infraEfeitoTabelas();
        var strMensagens = '<?php print str_replace("\n", '\n', $objPaginaSEI->getStrMensagens()); ?>';
        if(strMensagens) {
            alert(strMensagens);
        }
        objLupaUnidadesAdministrativas = new infraLupaSelect('selRepositorioEstruturas', 'hdnUnidadesAdministrativas', '<?= $strLinkUnidadesAdministrativasSelecao ?>');

        objAutoCompletarEstrutura = new infraAjaxAutoCompletar('hdnIdUnidade', 'txtUnidade', '<?= $strLinkAjaxUnidade ?>', "Nenhuma unidade foi encontrada");
        objAutoCompletarEstrutura.bolExecucaoAutomatica = false;
        objAutoCompletarEstrutura.mostrarAviso = true;
        objAutoCompletarEstrutura.limparCampo = false;
        objAutoCompletarEstrutura.tempoAviso = 10000000;

        objAutoCompletarEstrutura.prepararExecucao = function() {
            var selRepositorioEstruturas = document.getElementById('selRepositorioEstruturas');
            var parametros = 'palavras_pesquisa=' + document.getElementById('txtUnidade').value;
            parametros += '&id_repositorio=' + selRepositorioEstruturas.options[selRepositorioEstruturas.selectedIndex].value
            return parametros;
        };

        objAutoCompletarEstrutura.processarResultado = function(id, descricao, complemento) {
            window.infraAvisoCancelar();
        };

        $('#btnIdUnidade').click(function() {
            objAutoCompletarEstrutura.executar();
            objAutoCompletarEstrutura.procurar();
        });

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

    function validarCadastroAbrirRI0825() {
        if (!infraSelectSelecionado('selUnidades')) {
            alert('Informe as Unidades de Destino.');
            document.getElementById('selUnidades').focus();
            return false;
        }

        return true;
    }

    function selecionarUrgencia() {
        var chkSinUrgenteEnabled = $('#chkSinUrgente').is(':checked');
        $('#selMotivosUrgencia').prop('disabled', !chkSinUrgenteEnabled);

        if (!chkSinUrgenteEnabled) {
            infraSelectSelecionarItem('selMotivosUrgencia', '0');
            $('#selMotivosUrgencia').addClass('infraReadOnly');
            $('#divMotivosUrgencia').css('display', 'none');
        } else {
            $('#selMotivosUrgencia').removeClass('infraReadOnly');
            $('#divMotivosUrgencia').css('display', 'block');
        }
    }

    //Caso não tenha unidade encontrada
    $(document).ready(function() {
        $(document).on('click', '#txtUnidade', function() {
            if ($(this).val() == "Unidade não Encontrada.") {
                $(this).val('');
            }
        });
    });

    function selecionarRepositorio() {
        var txtUnidade = $('#txtUnidade');
        var selRepositorioEstruturas = $('#selRepositorioEstruturas');

        var txtUnidadeEnabled = selRepositorioEstruturas.val() > 0;
        txtUnidade.prop('disabled', !txtUnidadeEnabled);
        $('#hdnIdUnidade').val('');
        txtUnidade.val('');

        if (!txtUnidadeEnabled) {
            txtUnidade.addClass('infraReadOnly');
        } else {
            txtUnidade.removeClass('infraReadOnly');
            $('#txtRepositorioEstruturas').val($("#selRepositorioEstruturas option:selected").text());
        }
    }

    function avaliarPreCondicoes() {
        var houveErros = document.getElementById('hdnErrosValidacao').value;
        if (houveErros) {
            infraDesabilitarCamposDiv(document.getElementById('divProtocoloExibir'));
            infraDesabilitarCamposDiv(document.getElementById('divRepositorioEstruturas'));
            infraDesabilitarCamposDiv(document.getElementById('divUnidades'));
            infraDesabilitarCamposDiv(document.getElementById('divProcedimentosApensados'));
            infraDesabilitarCamposDiv(document.getElementById('divSinUrgente'));
            infraDesabilitarCamposDiv(document.getElementById('divMotivosUrgencia'));

            var smbExpedir = document.getElementById('sbmExpedir');
            smbExpedir.disabled = true;
            smbExpedir.className += ' infraReadOnly';
        }
    }


    function criarIFrameBarraProgresso() {

        nomeIFrameEnvioProcesso = 'ifrEnvioProcesso';
        var iframe = document.getElementById(nomeIFrameEnvioProcesso);
        if (iframe != null) {
            iframe.parentElement.removeChild(iframe);
        }

        var iframe = document.createElement('iframe');
        iframe.id = nomeIFrameEnvioProcesso;
        iframe.name = nomeIFrameEnvioProcesso;
        iframe.setAttribute('frameBorder', '0');
        iframe.setAttribute('scrolling', 'yes');

        return iframe;
    }

    function exibirBarraProgresso(elemBarraProgresso) {
        // Exibe camada de fundo da barra de progresso
        var divFundo = document.createElement('div');
        divFundo.id = 'divFundoBarraProgresso';
        divFundo.className = 'infraFundoTransparente';
        divFundo.style.visibility = 'visible';

        var divAviso = document.createElement('div');
        divAviso.id = 'divBarraProgresso';
        divAviso.appendChild(elemBarraProgresso);
        divFundo.appendChild(divAviso);

        document.body.appendChild(divFundo);

        redimencionarBarraProgresso();
        infraAdicionarEvento(window, 'resize', redimencionarBarraProgresso);
    }


    function abrirBarraProgresso(form, action, largura, altura) {

        if (typeof(form.onsubmit) == 'function' && !form.onsubmit()) {
            return null;
        }

        iframe = criarIFrameBarraProgresso();
        exibirBarraProgresso(iframe);

        form.target = iframe.id;
        form.action = action;
        form.submit();
    }


    function redimencionarBarraProgresso() {
        var divFundo = document.getElementById('divFundoBarraProgresso');
        if (divFundo != null) {
            divFundo.style.width = infraClientWidth() + 'px';
            divFundo.style.height = infraClientHeight() + 'px';
        }
    }

    function enviarForm(event) {
        var button = jQuery(event.target);
        var labelPadrao = button.html();

        button.attr('disabled', 'disabled').html('Validando...');

        var urlValidacao = '<?php print $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador_ajax.php?acao_ajax=pen_procedimento_expedir_validar' . $strParametros)); ?>';
        var objData = {};

        jQuery.each(['txtProtocoloExibir', 'selRepositorioEstruturas', 'hdnIdUnidade'], function(index, name) {
            var objInput = jQuery('#' + name);
            objData[name] = objInput.val();
        });

        jQuery.ajax({
            url: urlValidacao,
            method: 'POST',
            dataType: 'json',
            data: objData
        }).done(function() {
            button.removeAttr('disabled').html(labelPadrao);
        });
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

    <div id="divUnidades" class="infraAreaDados" style="height: 4.5em;">
        <label id="lblUnidades" for="selUnidades" class="infraLabelObrigatorio">Orgão Destino:</label>
        <div class="alinhamentoBotaoImput">
            <input type="text" id="txtUnidade" name="txtUnidade" class="infraText infraReadOnly" disabled="disabled" placeholder="Digite o nome/sigla da unidade e pressione ENTER para iniciar a pesquisa rápida" value="<?= $strNomeOrgaoDestino ?>" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" value="" />
            <button id="btnIdUnidade" type="button" class="infraButton">Consultar</button>
            <img id="imgPesquisaAvancada" src="imagens/organograma.gif" alt="Consultar organograma" title="Consultar organograma" class="infraImg" />
        </div>

        <input type="hidden" id="hdnIdUnidade" name="hdnIdUnidade" class="infraText" value="<?= $numIdOrgao; ?>" />
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