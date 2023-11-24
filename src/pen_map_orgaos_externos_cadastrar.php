<?php
require_once DIR_SEI_WEB . '/SEI.php';

session_start();

//////////////////////////////////////////////////////////////////////////////
//InfraDebug::getInstance()->setBolLigado(true);
//InfraDebug::getInstance()->setBolDebugInfra(true);
//InfraDebug::getInstance()->limpar();
//////////////////////////////////////////////////////////////////////////////

$objSessaoSEI = SessaoSEI::getInstance();
$objPaginaSEI = PaginaSEI::getInstance();
$objDebug = InfraDebug::getInstance();
$objInfraException = new InfraException();

try {
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

    //Prepara��o dos dados para montagem da tela de expedi��o de processos
    $objExpedirProcedimentosRN = new ExpedirProcedimentoRN();
    $repositorios = $objExpedirProcedimentosRN->listarRepositoriosDeEstruturas();

    $objUnidadeDTO = new PenUnidadeDTO();
    $objUnidadeDTO->retNumIdUnidadeRH();
    $objUnidadeDTO->setNumIdUnidade($objSessaoSEI->getNumIdUnidadeAtual());

    $objUnidadeRN = new UnidadeRN();
    $objUnidadeDTO = $objUnidadeRN->consultarRN0125($objUnidadeDTO);

    if (!$objUnidadeDTO) {
        throw new InfraException("A unidade atual n�o foi mapeada.");
    }

    $numIdUnidadeOrigem = $objUnidadeDTO->getNumIdUnidadeRH();

    // �rg�o de origem
    $numIdOrgaoOrigem = $_POST['hdnIdUnidadeOrigem'];
    $strNomeOrgaoOrigem = $_POST['txtUnidadeOrigem'];
    $numIdRepositorioOrigem = $_POST['selRepositorioEstruturasOrigem'];
    $txtRepositorioEstruturasOrigem = $_POST['txtRepositorioEstruturasOrigem'];
    // �rg�o de destino
    $numIdOrgaoDestino = $_POST['hdnIdUnidadeDestino'];
    $strNomeOrgaoDestino = $_POST['txtUnidadeDestino'];

    $strLinkAjaxUnidade = $objSessaoSEI->assinarLink('controlador_ajax.php?acao_ajax=pen_unidade_auto_completar_expedir_procedimento&acao=' . $_GET['acao']);
    $strLinkAjaxUnidadeDestino = $objSessaoSEI->assinarLink('controlador_ajax.php?acao_ajax=pen_unidade_auto_completar_mapeados&acao=' . $_GET['acao']);

    switch ($_GET['acao']) {
        case 'pen_map_orgaos_externos_salvar':
            if (is_null($numIdRepositorioOrigem)) {
                $objPaginaSEI->adicionarMensagem('selecione um reposit�rio de origem.', InfraPagina::$TIPO_MSG_AVISO);
                header('Location: ' . $objSessaoSEI->assinarLink('controlador.php?acao=pen_map_orgaos_externos_cadastrar&acao_origem=' . $_GET['acao_origem']));
                exit(0);
            } elseif (is_null($numIdOrgaoOrigem)) {
                $objPaginaSEI->adicionarMensagem('o org�o origem n�o foi selecionado.', InfraPagina::$TIPO_MSG_AVISO);
                header('Location: ' . $objSessaoSEI->assinarLink('controlador.php?acao=pen_map_orgaos_externos_cadastrar&acao_origem=' . $_GET['acao_origem']));
                exit(0);
            } elseif (is_null($numIdOrgaoDestino)) {
                $objPaginaSEI->adicionarMensagem('o org�o destino n�o foi selecionado.', InfraPagina::$TIPO_MSG_AVISO);
                header('Location: ' . $objSessaoSEI->assinarLink('controlador.php?acao=pen_map_orgaos_externos_cadastrar&acao_origem=' . $_GET['acao_origem']));
                exit(0);
            } 
            
            $objPenOrgaoExternoDTO = new PenOrgaoExternoDTO();
            $objPenOrgaoExternoDTO->setNumIdUnidade($objSessaoSEI->getNumIdUnidadeAtual());
            $objPenOrgaoExternoDTO->setNumIdOrgaoOrigem($numIdOrgaoOrigem);
            $objPenOrgaoExternoDTO->setNumIdEstrutaOrganizacionalOrigem($numIdRepositorioOrigem);
            $objPenOrgaoExternoDTO->setNumIdOrgaoDestino($numIdOrgaoDestino);
            $objPenOrgaoExternoDTO->setNumMaxRegistrosRetorno(1);

            $objPenOrgaoExternoRN = new PenOrgaoExternoRN();
            $respObjPenOrgaoExternoDTO = $objPenOrgaoExternoRN->contar($objPenOrgaoExternoDTO);
            if ($respObjPenOrgaoExternoDTO > 0) {
                $objPaginaSEI->adicionarMensagem('Cadastro de relacionamento entre �rg�os ja existe.', InfraPagina::$TIPO_MSG_ERRO);
                header('Location: ' . $objSessaoSEI->assinarLink('controlador.php?acao=pen_map_orgaos_externos_cadastrar&acao_origem=' . $_GET['acao_origem']));
                exit(0);
            }

            $objPenOrgaoExternoDTO = new PenOrgaoExternoDTO();
            $objPenOrgaoExternoDTO->setNumIdUnidade($objSessaoSEI->getNumIdUnidadeAtual());
            $objPenOrgaoExternoDTO->setDthRegistro(date('d/m/Y H:i:s'));
            // �rg�o de origem
            $objPenOrgaoExternoDTO->setNumIdOrgaoOrigem($numIdOrgaoOrigem);
            $objPenOrgaoExternoDTO->setStrOrgaoOrigem($strNomeOrgaoOrigem);
            $objPenOrgaoExternoDTO->setNumIdEstrutaOrganizacionalOrigem($numIdRepositorioOrigem);
            $objPenOrgaoExternoDTO->setStrEstrutaOrganizacionalOrigem($txtRepositorioEstruturasOrigem);
            // �rg�o de destino
            $objPenOrgaoExternoDTO->setNumIdOrgaoDestino($numIdOrgaoDestino);
            $objPenOrgaoExternoDTO->setStrOrgaoDestino($strNomeOrgaoDestino);

            $objPenOrgaoExternoRN = new PenOrgaoExternoRN();
            $objPenOrgaoExternoRN->cadastrar($objPenOrgaoExternoDTO);
            $objPaginaSEI->adicionarMensagem('Relacionamento cadastrado com sucesso.', InfraPagina::$TIPO_MSG_INFORMACAO);
            header('Location: ' . $objSessaoSEI->assinarLink('controlador.php?acao=pen_map_orgaos_externos_listar&acao_origem=' . $_GET['acao_origem']));
            exit(0);
            break;
        case 'pen_map_orgaos_externos_cadastrar':
            $strTitulo = 'Cadastro de Relacionamento entre �rg�os';

            //Monta os bot�es do topo
            if ($objSessaoSEI->verificarPermissao('pen_map_orgaos_externos_cadastrar')) {
                $arrComandos[] = '<button type="submit" id="btnSalvar" value="Salvar" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
                $arrComandos[] = '<button type="button" id="btnCancelar" value="Cancelar" onclick="location.href=\'' . $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=pen_map_orgaos_externos_listar&acao_origem=' . $_GET['acao'])) . '\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';
            } else {
                $arrComandos[] = '<button type="button" id="btnCancelar" value="Voltar" onclick="location.href=\'' . $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=pen_map_orgaos_externos_listar&acao_origem=' . $_GET['acao'])) . '\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';
            }

            //Obter dados do reposit�rio em que o SEI est� registrado (Reposit�rio de Origem)
            $objPenParametroRN = new PenParametroRN();
            $numIdRepositorioOrigem = $objPenParametroRN->getParametro('PEN_ID_REPOSITORIO_ORIGEM');
            $idRepositorioSelecionado = (isset($numIdRepositorioOrigem)) ? $numIdRepositorioOrigem : '';
            $strItensSelRepositorioEstruturasOrigem = InfraINT::montarSelectArray('', 'Selecione', $idRepositorioSelecionado, $repositorios);

            $strLinkAjaxProcedimentoApensado = $objSessaoSEI->assinarLink('controlador_ajax.php?acao_ajax=pen_apensados_auto_completar_expedir_procedimento');
            $strLinkUnidadesAdministrativasSelecao = $objSessaoSEI->assinarLink('controlador.php?acao=pen_unidades_administrativas_externas_selecionar_expedir_procedimento&tipo_pesquisa=1&id_object=objLupaUnidadesAdministrativas&idRepositorioEstruturaOrigem=1');
            break;
        default:
            throw new InfraException("A��o '" . $_GET['acao'] . "' n�o reconhecida.");
    }
} catch (Exception $e) {
    $objPaginaSEI->adicionarMensagem('Falha no cadastro do relacionamento. Consulte o log do SEI para mais informa��es.', InfraPagina::$TIPO_MSG_ERRO);
    throw new InfraException("Erro processando requisi��o de envio externo de processo", $e);
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

div.conteiner{
    width: 100%;
    padding: 15px;
}

div.infraAreaDados {
margin-bottom: 10px;
}

#lblProtocoloExibir {position:absolute;left:0%;top:0%;}
#txtProtocoloExibir {position:absolute;left:0%;top:38%;width:50%;}

#lblRepositorioEstruturasOrigem {position:absolute;left:0%;top:0%;}
#selRepositorioEstruturasOrigem {position:absolute;left:0%;top:38%;}

#lblUnidadesOrigem {position:absolute;left:0%;top:0%;}
#txtUnidadeOrigem {left:0%;top:38%;width:100%;border:.1em solid #666;}
#imgLupaUnidadesOrigem {position:absolute;left:52%;top:48%;}

#lblUnidadesDestino {position:absolute;left:0%;top:0%;}
#txtUnidadeDestino {left:0%;top:38%;width:100%;border:.1em solid #666;}
#imgLupaUnidadesDestino {position:absolute;left:52%;top:48%;}

.alinhamentoBotaoImput{position:absolute;left:0%;top:48%;width:85%;};

#btnIdUnidadeOrigem {float: left;}
#btnIdUnidadeDestino {float: left;}
#imgPesquisaAvancada {
    vertical-align: middle;
    margin-left: 10px;
    width: 20px;
    height: 20px;
}

.panelOrgao {
    color: #fff;
    width: 48%;
    height: 22em;
    float: left;
    padding: 1em 0em 5em 2em;
    border: 2px solid #999;
    margin: 10px;
    border-radius: 12px;
}

.panelOrgao > h4 {
    position: relative;
    background: #155f9b;
    width: 42%;
    border-radius: 12px;
    text-align: center;
    padding: 6px;
    top: -33px;
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

    function inicializarOrigem() {
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
        

        //Bot�o de pesquisa avan�ada
        $('#imgPesquisaAvancada').click(function() {
            var idRepositorioEstrutura = $('#selRepositorioEstruturasOrigem :selected').val();
            if ((idRepositorioEstruturaOrigem != '') && (idRepositorioEstruturaOrigem != 'null')) {
                $("#hdnUnidadesAdministrativas").val(idRepositorioEstruturaOrigem);
                objLupaUnidadesAdministrativas.selecionar(700, 500);
            } else {
                alert('Selecione um reposit�rio de Estruturas Organizacionais');
            }
        });
        document.getElementById('selRepositorioEstruturasOrigem').focus();
        selecionarRepositorioOrigem();
    }

    function inicializarDestino() {
        objAutoCompletarEstruturaDestino = new infraAjaxAutoCompletar('hdnIdUnidadeDestino', 'txtUnidadeDestino', '<?= $strLinkAjaxUnidadeDestino ?>', "Nenhuma unidade foi encontrada");
        objAutoCompletarEstruturaDestino.bolExecucaoAutomatica = false;
        objAutoCompletarEstruturaDestino.mostrarAviso = true;
        objAutoCompletarEstruturaDestino.limparCampo = false;
        objAutoCompletarEstruturaDestino.tempoAviso = 10000000;

        objAutoCompletarEstruturaDestino.prepararExecucao = function() {
            var parametros = 'palavras_pesquisa=' + document.getElementById('txtUnidadeDestino').value;
            return parametros;
        };

        objAutoCompletarEstruturaDestino.processarResultado = function(id, descricao, complemento) {
            window.infraAvisoCancelar();
        };

        $('#btnIdUnidadeDestino').click(function() {
            objAutoCompletarEstruturaDestino.executar();
            objAutoCompletarEstruturaDestino.procurar();
        });
    }

    //Caso n�o tenha unidade encontrada
    $(document).ready(function() {
        $(document).on('click', '#txtUnidadeOrigem', function() {
            if ($(this).val() == "�rg�o origem n�o Encontrado.") {
                $(this).val('');
            }
        });
        $(document).on('click', '#txtUnidadeDestino', function() {
            if ($(this).val() == "�rg�o destino n�o Encontrado.") {
                $(this).val('');
            }
        });
    });

    function selecionarRepositorioOrigem() {
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
</script>
<?php
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody($strTitulo, 'onload="infraEfeitoTabelas(); inicializarOrigem(); inicializarDestino();"');
?>
<form id="frmGravarOrgaoExterno" name="frmGravarOrgaoExterno" method="post" action="<?= $strLinkValidacao ?>">
    <div class='conteiner'>
    <?php
    $objPaginaSEI->montarBarraComandosSuperior($arrComandos);
    ?>
    </div>

    <div class="panelOrgao divOrgaoOrigem">
        <h4>�rg�o Origem</h5>

        <div id="divRepositorioEstruturasOrigem" class="infraAreaDados" style="height: 4.5em;">
            <label id="lblRepositorioEstruturasOrigem" for="selRepositorioEstruturasOrigem" accesskey="" class="infraLabelObrigatorio">Reposit�rio de Estruturas Organizacionais:</label>
            <select id="selRepositorioEstruturasOrigem" name="selRepositorioEstruturasOrigem" class="infraSelect" onchange="selecionarRepositorioOrigem();" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>">
                <?= $strItensSelRepositorioEstruturasOrigem ?>
            </select>

            <input type="hidden" id="txtRepositorioEstruturasOrigem" name="txtRepositorioEstruturasOrigem" class="infraText" value="<?= $txtRepositorioEstruturasOrigem; ?>" />
        </div>

        <div id="divUnidadesUnidades" class="infraAreaDados" style="height: 4.5em;">
            <label id="lblUnidadesOrigem" for="selUnidadesOrigem" class="infraLabelObrigatorio">Org�o Origem:</label>
            <div class="alinhamentoBotaoImput">
                <input type="text" id="txtUnidadeOrigem" name="txtUnidadeOrigem" class="infraText infraReadOnly" disabled="disabled" placeholder="Digite o nome/sigla da unidade e pressione ENTER para iniciar a pesquisa r�pida" value="<?= $strNomeOrgaoDestino ?>" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" value="" />
                <br />
                <br />
                <button id="btnIdUnidadeOrigem" type="button" class="infraButton">Consultar</button>
                <img id="imgPesquisaAvancada" src="imagens/organograma.gif" alt="Consultar organograma" title="Consultar organograma" class="infraImg" />
            </div>

            <input type="hidden" id="hdnIdUnidadeOrigem" name="hdnIdUnidadeOrigem" class="infraText" value="<?= $numIdOrgaoOrigem; ?>" />
        </div>
    </div>

    <div class="panelOrgao divOrgaoDestino">
        <h4>�rg�o Destino</h4>

        <div id="divUnidadesUnidades" class="infraAreaDados" style="height: 4.5em;">
            <label id="lblUnidadesDestino" for="selUnidadesDestino" class="infraLabelObrigatorio">Org�o Destino:</label>
            <div class="alinhamentoBotaoImput">
                <input type="text" id="txtUnidadeDestino" name="txtUnidadeDestino" class="infraText infraReadOnly"
                    placeholder="Digite o nome/sigla da unidade e pressione ENTER para iniciar a pesquisa r�pida" 
                    value="<?= PaginaSEI::tratarHTML($strNomeOrgaoDestino); ?>" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" value="" />
                <br /><br />
                <button id="btnIdUnidadeDestino" type="button" class="infraButton">Consultar</button>
            </div>

            <input type="hidden" id="hdnIdUnidadeDestino" name="hdnIdUnidadeDestino" class="infraText" value="<?= $numIdOrgaoDestino; ?>" />
        </div>
    </div>

    <input type="hidden" id="hdnErrosValidacao" name="hdnErrosValidacao" value="<?= $bolErrosValidacao ?>" />
    <input type="hidden" id="hdnUnidadesAdministrativas" name="hdnUnidadesAdministrativas" value="" />
</form>
<?php
$objPaginaSEI->montarAreaDebug();
$objPaginaSEI->fecharBody();
$objPaginaSEI->fecharHtml();
?>