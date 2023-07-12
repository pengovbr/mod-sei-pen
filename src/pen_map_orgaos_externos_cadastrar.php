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

    $numIdOrgaoOrigem = $_POST['hdnIdUnidadeOrigem'];
    $strNomeOrgaoDestinoOrigem = $_POST['txtUnidadeOrigem'];
    $numIdRepositorioOrigem = $_POST['selRepositorioEstruturasOrigem'];
    $txtRepositorioEstruturasOrigem = $_POST['txtRepositorioEstruturasOrigem'];
    $numIdUnidadeOrigem = $objUnidadeDTO->getNumIdUnidadeRH();
    $boolSinExtenderSubUnidades = $objPaginaSEI->getCheckbox($_POST['chkSinExtenderSubUnidades'], true, false);

    switch ($_GET['acao']) {
        case 'pen_map_orgaos_externos_salvar':
            if (is_null($numIdRepositorioOrigem)) {
                $objPaginaSEI->adicionarMensagem('selecione um repositório de destino.');
            } elseif (is_null($numIdOrgao)) {
                $objPaginaSEI->adicionarMensagem('o orgão não foi selecionado.');
            } else {
                $objPenOrgaoExternoDTO = new PenOrgaoExternoDTO();
                $objPenOrgaoExternoDTO->setNumIdUnidade($objSessaoSEI->getNumIdUnidadeAtual());
                $objPenOrgaoExternoDTO->setNumIdOrgao($numIdOrgao);
                $objPenOrgaoExternoDTO->setNumIdEstrutaOrganizacional($numIdRepositorioOrigem);
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
                $objPenOrgaoExternoDTO->setNumIdEstrutaOrganizacional($numIdRepositorioOrigem);
                $objPenOrgaoExternoDTO->setStrEstrutaOrganizacional($txtRepositorioEstruturasOrigem);
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
            $idRepositorioSelecionado = (isset($numIdRepositorioOrigem)) ? $numIdRepositorioOrigem : '';
            $strItensSelRepositorioEstruturasOrigem = InfraINT::montarSelectArray('', 'Selecione', $idRepositorioSelecionado, $repositorios);

            $strLinkAjaxUnidade = $objSessaoSEI->assinarLink('controlador_ajax.php?acao_ajax=pen_unidade_auto_completar_expedir_procedimento&acao=' . $_GET['acao']);
            $strLinkAjaxProcedimentoApensado = $objSessaoSEI->assinarLink('controlador_ajax.php?acao_ajax=pen_apensados_auto_completar_expedir_procedimento');
            $strLinkUnidadesAdministrativasSelecao = $objSessaoSEI->assinarLink('controlador.php?acao=pen_unidades_administrativas_externas_selecionar_expedir_procedimento&tipo_pesquisa=1&id_object=objLupaUnidadesAdministrativas&idRepositorioEstruturaOrigem=1');
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

#lblRepositorioEstruturasOrigem {position:absolute;left:0%;top:0%;width:50%;}
#selRepositorioEstruturasOrigem {position:absolute;left:0%;top:38%;width:51%;}

#lblUnidadesOrigem {position:absolute;left:0%;top:0%;}
#txtUnidadeOrigem {left:0%;top:38%;width:50%;border:.1em solid #666;}
#imgLupaUnidadesOrigem {position:absolute;left:52%;top:48%;}

.alinhamentoBotaoImput{position:absolute;left:0%;top:48%;width:85%;};

#btnIdUnidadeOrigem {float: right;}
#imgPesquisaAvancada {
    vertical-align: middle;
    margin-left: 10px;
    width: 20px;
    height: 20px;
}

<?php
$objPaginaSEI->fecharStyle();
$objPaginaSEI->montarJavaScript();
?>
<script type="text/javascript">
    var idRepositorioEstruturaOrigem = null;
    var objAutoCompletarEstruturaOrigem = null;

    var objLupaUnidadesOrigem = null;
    var objLupaUnidadesAdministrativasOrigem = null;

    function inicializar() {
        infraEfeitoTabelas();
        var strMensagens = '<?php print str_replace("\n", '\n', $objPaginaSEI->getStrMensagens()); ?>';
        if(strMensagens) {
            alert(strMensagens);
        }
        objLupaUnidadesAdministrativas = new infraLupaSelect('selRepositorioEstruturasOrigem', 'hdnUnidadesAdministrativas', '<?= $strLinkUnidadesAdministrativasSelecao ?>');

        objAutoCompletarEstruturaOrigem = new infraAjaxAutoCompletar('hdnIdUnidadeOrigem', 'txtUnidadeOrigem', '<?= $strLinkAjaxUnidade ?>', "Nenhuma unidade foi encontrada");
        objAutoCompletarEstruturaOrigem.bolExecucaoAutomatica = false;
        objAutoCompletarEstruturaOrigem.mostrarAviso = true;
        objAutoCompletarEstruturaOrigem.limparCampo = false;
        objAutoCompletarEstruturaOrigem.tempoAviso = 10000000;

        objAutoCompletarEstruturaOrigem.prepararExecucao = function() {
            var selRepositorioEstruturasOrigem = document.getElementById('selRepositorioEstruturasOrigem');
            var parametros = 'palavras_pesquisa=' + document.getElementById('txtUnidadeOrigem').value;
            parametros += '&id_repositorio=' + selRepositorioEstruturasOrigem.options[selRepositorioEstruturasOrigem.selectedIndex].value
            return parametros;
        };

        objAutoCompletarEstruturaOrigem.processarResultado = function(id, descricao, complemento) {
            window.infraAvisoCancelar();
        };

        $('#btnIdUnidadeOrigem').click(function() {
            objAutoCompletarEstruturaOrigem.executar();
            objAutoCompletarEstruturaOrigem.procurar();
        });

        //Botão de pesquisa avançada
        $('#imgPesquisaAvancada').click(function() {
            var idRepositorioEstrutura = $('#selRepositorioEstruturasOrigem :selected').val();
            if ((idRepositorioEstruturaOrigem != '') && (idRepositorioEstruturaOrigem != 'null')) {
                $("#hdnUnidadesAdministrativas").val(idRepositorioEstruturaOrigem);
                objLupaUnidadesAdministrativas.selecionar(700, 500);
            } else {
                alert('Selecione um repositório de Estruturas Organizacionais');
            }
        });
        document.getElementById('selRepositorioEstruturasOrigem').focus();
    }

    function validarCadastroAbrirRI0825() {
        if (!infraSelectSelecionado('selUnidadesOrigem')) {
            alert('Informe as Unidades de Destino.');
            document.getElementById('selUnidadesOrigem').focus();
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
        $(document).on('click', '#txtUnidadeOrigem', function() {
            if ($(this).val() == "Unidade não Encontrada.") {
                $(this).val('');
            }
        });
    });

    function selecionarRepositorio() {
        var txtUnidadeOrigem = $('#txtUnidadeOrigem');
        var selRepositorioEstruturasOrigem = $('#selRepositorioEstruturasOrigem');

        var txtUnidadeOrigemEnabled = selRepositorioEstruturasOrigem.val() > 0;
        txtUnidadeOrigem.prop('disabled', !txtUnidadeOrigemEnabled);
        $('#hdnIdUnidadeOrigem').val('');
        txtUnidadeOrigem.val('');

        if (!txtUnidadeOrigemEnabled) {
            txtUnidadeOrigem.addClass('infraReadOnly');
        } else {
            txtUnidadeOrigem.removeClass('infraReadOnly');
            $('#txtRepositorioEstruturasOrigem').val($("#selRepositorioEstruturasOrigem option:selected").text());
        }
    }

    function avaliarPreCondicoes() {
        var houveErros = document.getElementById('hdnErrosValidacao').value;
        if (houveErros) {
            infraDesabilitarCamposDiv(document.getElementById('divProtocoloExibir'));
            infraDesabilitarCamposDiv(document.getElementById('divRepositorioEstruturasOrigin'));
            infraDesabilitarCamposDiv(document.getElementById('divUnidadesUnidades'));
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

        jQuery.each(['txtProtocoloExibir', 'selRepositorioEstruturasOrigem', 'hdnIdUnidadeOrigem'], function(index, name) {
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
        $('#txtRepositorioEstruturasOrigem').val($("#selRepositorioEstruturasOrigem option:selected").text());
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

    <div id="divRepositorioEstruturasOrigem" class="infraAreaDados" style="height: 4.5em;">
        <label id="lblRepositorioEstruturasOrigem" for="selRepositorioEstruturasOrigem" accesskey="" class="infraLabelObrigatorio">Repositório de Estruturas Organizacionais:</label>
        <select id="selRepositorioEstruturasOrigem" name="selRepositorioEstruturasOrigem" class="infraSelect" onchange="selecionarRepositorio();" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>">
            <?= $strItensSelRepositorioEstruturasOrigem ?>
        </select>
        
        <input type="hidden" id="txtRepositorioEstruturasOrigem" name="txtRepositorioEstruturasOrigem" class="infraText" value="<?= $txtRepositorioEstruturasOrigem; ?>" />
    </div>

    <div id="divUnidadesUnidades" class="infraAreaDados" style="height: 4.5em;">
        <label id="lblUnidadesOrigem" for="selUnidadesOrigem" class="infraLabelObrigatorio">Orgão Origem:</label>
        <div class="alinhamentoBotaoImput">
            <input type="text" id="txtUnidadeOrigem" name="txtUnidadeOrigem" class="infraText infraReadOnly" disabled="disabled" placeholder="Digite o nome/sigla da unidade e pressione ENTER para iniciar a pesquisa rápida" value="<?= $strNomeOrgaoDestino ?>" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" value="" />
            <button id="btnIdUnidadeOrigem" type="button" class="infraButton">Consultar</button>
            <img id="imgPesquisaAvancada" src="imagens/organograma.gif" alt="Consultar organograma" title="Consultar organograma" class="infraImg" />
        </div>

        <input type="hidden" id="hdnIdUnidadeOrigem" name="hdnIdUnidadeOrigem" class="infraText" value="<?= $numIdOrgao; ?>" />
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