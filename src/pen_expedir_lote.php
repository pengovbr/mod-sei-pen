<?php
try {
    require_once DIR_SEI_WEB.'/SEI.php';

    session_start();

    $objSessaoSEI = SessaoSEI::getInstance();
    $objPaginaSEI = PaginaSEI::getInstance();

    $objSessaoSEI->validarLink();
    $objSessaoSEI->validarPermissao($_GET['acao']);

    $strParametros = '';
    $bolErrosValidacao = false;
    $executarExpedicao = false;
    $arrComandos = array();

    $strDiretorioModulo = PENIntegracao::getDiretorio();

    $arrProtocolosOrigem = array();
    $arrProtocolosOrigem = array_merge($objPaginaSEI->getArrStrItensSelecionados('Gerados'),$objPaginaSEI->getArrStrItensSelecionados('Recebidos'),$objPaginaSEI->getArrStrItensSelecionados('Detalhado'));

    if (count($arrProtocolosOrigem)==0){
        $arrProtocolosOrigem = explode(',',$_POST['hdnIdProcedimento']);
    }

    if (count($arrProtocolosOrigem)==0){
        throw new InfraException('Processo não informado.');
    }

    $strItensSelProcedimentos = ProcedimentoINT::conjuntoCompletoFormatadoRI0903($arrProtocolosOrigem);

    if (isset($_GET['arvore'])) {
        $objPaginaSEI->setBolArvore($_GET['arvore']);
        $strParametros .= '&arvore=' . $_GET['arvore'];
    }

    if (isset($_GET['executar'])) {
        $executarExpedicao = filter_var($_GET['executar'], FILTER_VALIDATE_BOOLEAN);
    }

    $strLinkValidacao = $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=' . $_GET['acao'] . '&acao_origem=' . $_GET['acao'] . $strParametros));
    $strLinkProcedimento = $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=' . $objPaginaSEI->getAcaoRetorno() . '&acao_origem=' . $_GET['acao'] . '&acao_destino=' . $_GET['acao'] . $strParametros));

    switch ($_GET['acao']) {

        case 'pen_expedir_lote':

            $strTitulo = 'Envio Externo de Processo em Lote';
            $arrComandos[] = '<button type="button" accesskey="E" onclick="enviarForm(event)" value="Enviar" class="infraButton" style="width:8%;"><span class="infraTeclaAtalho">E</span>nviar</button>';
            $arrComandos[] = '<button type="button" accesskey="C" name="btnCancelar" value="Cancelar" onclick="location.href=\'' . $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=' . $objPaginaSEI->getAcaoRetorno() . '&acao_origem=' . $_GET['acao'] . '&acao_destino=' . $_GET['acao'] . $strParametros)) . '\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';

            //Obter dados do repositório em que o SEI está registrado (Repositório de Origem)
            $objPenParametroRN = new PenParametroRN();
            $numIdRepositorioOrigem = $objPenParametroRN->getParametro('PEN_ID_REPOSITORIO_ORIGEM');

            //Preparação dos dados para montagem da tela de expedição de processos
            $objExpedirProcedimentoRN = new ExpedirProcedimentoRN();
            $repositorios = $objExpedirProcedimentoRN->listarRepositoriosDeEstruturas();

            $idRepositorioSelecionado = (isset($numIdRepositorio)) ? $numIdRepositorio : '';
            $strItensSelRepositorioEstruturas = InfraINT::montarSelectArray('', 'Selecione', $idRepositorioSelecionado, $repositorios);

            $strLinkAjaxUnidade = $objSessaoSEI->assinarLink('controlador_ajax.php?acao_ajax=pen_unidade_auto_completar_expedir_procedimento');
            $strLinkUnidadesAdministrativasSelecao = $objSessaoSEI->assinarLink('controlador.php?acao=pen_unidades_administrativas_externas_selecionar_expedir_procedimento&tipo_pesquisa=1&id_object=objLupaUnidadesAdministrativas&idRepositorioEstrutura=1');

            $objUnidadeDTO = new PenUnidadeDTO();
            $objUnidadeDTO->retNumIdUnidadeRH();
            $objUnidadeDTO->setNumIdUnidade($objSessaoSEI->getNumIdUnidadeAtual());

            $objUnidadeRN = new UnidadeRN();
            $objUnidadeDTO = $objUnidadeRN->consultarRN0125($objUnidadeDTO);

            if (!$objUnidadeDTO) {
                throw new InfraException("A unidade atual não foi mapeada.");
            }

            $numIdUnidadeOrigem = $objUnidadeDTO->getNumIdUnidadeRH();
            $numIdRepositorio = $_POST['selRepositorioEstruturas'];
            $strRepositorio = (array_key_exists($numIdRepositorio, $repositorios) ? $repositorios[$numIdRepositorio] : '');
            $numIdUnidadeDestino = $_POST['hdnIdUnidade'];
            $strNomeUnidadeDestino = $_POST['txtUnidade'];
            $numIdUsuario = $objSessaoSEI->getNumIdUsuario();
            $dthRegistro = date('d/m/Y H:i:s');

            if(isset($_POST['sbmExpedir'])) {
                $numVersao = $objPaginaSEI->getNumVersao();
                echo "<link href='$strDiretorioModulo/css/" . ProcessoEletronicoINT::getCssCompatibilidadeSEI4("pen_procedimento_expedir.css") . "' rel='stylesheet' type='text/css' media='all' />\n";
                echo "<script type='text/javascript' charset='iso-8859-1' src='$strDiretorioModulo/js/expedir_processo/pen_procedimento_expedir.js?$numVersao'></script>";

                $strTituloPagina = "Cadastro de processos em Lote";
                $objPaginaSEI->prepararBarraProgresso($strTitulo, $strTituloPagina);

                try {

                    $objPenExpedirLoteDTO = new PenExpedirLoteDTO();
                    $objPenExpedirLoteDTO->setNumIdLote(null);
                    $objPenExpedirLoteDTO->setNumIdRepositorioOrigem($numIdRepositorioOrigem);
                    $objPenExpedirLoteDTO->setNumIdUnidadeOrigem($numIdUnidadeOrigem);
                    $objPenExpedirLoteDTO->setNumIdRepositorioDestino($numIdRepositorio);
                    $objPenExpedirLoteDTO->setStrRepositorioDestino($strRepositorio);
                    $objPenExpedirLoteDTO->setNumIdUnidadeDestino($numIdUnidadeDestino);
                    $objPenExpedirLoteDTO->setStrUnidadeDestino($strNomeUnidadeDestino);
                    $objPenExpedirLoteDTO->setNumIdUsuario($numIdUsuario);
                    $objPenExpedirLoteDTO->setNumIdUnidade($objSessaoSEI->getNumIdUnidadeAtual());
                    $objPenExpedirLoteDTO->setDthRegistro($dthRegistro);
                    $objPenExpedirLoteDTO->setArrIdProcedimento($arrProtocolosOrigem);

                    $objPenExpedirLoteRN = new PenExpedirLoteRN();
                    $ret = $objPenExpedirLoteRN->cadastrarLote($objPenExpedirLoteDTO);
                    $bolBotaoFecharCss = InfraUtil::compararVersoes(SEI_VERSAO, ">", "4.0.1");

                    // Muda situação da barra de progresso para Concluído
                    echo "<script type='text/javascript'>sinalizarStatusConclusao('$strLinkProcedimento','$bolBotaoFecharCss');</script> ";
                } catch(\Exception $e) {
                    $objPaginaSEI->processarExcecao($e);
                    echo "<script type='text/javascript'>adicionarBotaoFecharErro('$strLinkProcedimento');</script> ";
                }

                $objPaginaSEI->finalizarBarraProgresso(null, false);
            }

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

#lblProtocoloExibir {position:absolute;left:0%;top:0%;}
#txtProtocoloExibir {position:absolute;left:0%;top:38%;width:50%;}
#selProcedimentos {position:absolute;left:0%;top:21%;width:81%;}

#lblRepositorioEstruturas {position:absolute;left:0%;top:10%;width:50%;}
#selRepositorioEstruturas {position:absolute;left:0%;top:48%;width:51%;}

#lblUnidades {position:absolute;left:0%;top:10%;}
#txtUnidade {left:0%;top:48%;width:50%;border:.1em solid #666;}
#imgLupaUnidades {position:absolute;left:52%;top:48%;}
.alinhamentoBotaoImput{position:absolute;left:0%;top:48%;width:85%;};
#btnIdUnidade {float: right;}
#imgPesquisaAvancada {
    vertical-align: middle;
    margin-left: 10px;
}

<?php
$objPaginaSEI->fecharStyle();
$objPaginaSEI->montarJavaScript();
?>
<script type="text/javascript">

var idRepositorioEstrutura = null;
var objAutoCompletarUnidade = null;
var objAutoCompletarEstrutura = null;

var objLupaUnidades = null;
var objLupaUnidadesAdministrativas = null;
var objJanelaExpedir = null;
var evnJanelaExpedir = null;

function inicializar() {

    objLupaUnidadesAdministrativas = new infraLupaSelect('selRepositorioEstruturas','hdnUnidadesAdministrativas','<?=$strLinkUnidadesAdministrativasSelecao ?>');

    objAutoCompletarEstrutura = new infraAjaxAutoCompletar('hdnIdUnidade','txtUnidade','<?=$strLinkAjaxUnidade?>', "Nenhuma unidade foi encontrada");
    objAutoCompletarEstrutura.bolExecucaoAutomatica = false;
    objAutoCompletarEstrutura.mostrarAviso = true;
    objAutoCompletarEstrutura.limparCampo = false;
    objAutoCompletarEstrutura.tempoAviso = 10000000;

    objAutoCompletarEstrutura.prepararExecucao = function(){
        var selRepositorioEstruturas = document.getElementById('selRepositorioEstruturas');
        var parametros = 'palavras_pesquisa=' + document.getElementById('txtUnidade').value;
        parametros += '&id_repositorio=' + selRepositorioEstruturas.options[selRepositorioEstruturas.selectedIndex].value
        return parametros;
    };

    objAutoCompletarEstrutura.processarResultado = function(id,descricao,complemento){
        window.infraAvisoCancelar();
    };

    $('#btnIdUnidade').click(function() {
        objAutoCompletarEstrutura.executar();
        objAutoCompletarEstrutura.procurar();
    });

    //Botão de pesquisa avançada
    $('#imgPesquisaAvancada').click(function() {
        var idRepositorioEstrutura = $('#selRepositorioEstruturas :selected').val();
        if((idRepositorioEstrutura != '') && (idRepositorioEstrutura != 'null')){
            $("#hdnUnidadesAdministrativas").val(idRepositorioEstrutura);
            objLupaUnidadesAdministrativas.selecionar(700,500);
        }else{
            alert('Selecione um repositório de Estruturas Organizacionais');
        }
    });

    document.getElementById('selRepositorioEstruturas').focus();
}

//Caso não tenha unidade encontrada
$(document).ready(function() {
    $(document).on('click', '#txtUnidade', function() {
        if ($(this).val() == "Unidade não Encontrada.") {
            $(this).val('');
        }
    });
});

function selecionarRepositorio()
{
    var txtUnidade = $('#txtUnidade');
    var selRepositorioEstruturas = $('#selRepositorioEstruturas');

    var txtUnidadeEnabled = selRepositorioEstruturas.val() > 0;
    txtUnidade.prop('disabled', !txtUnidadeEnabled);
    $('#hdnIdUnidade').val('');
    txtUnidade.val('');

    if(!txtUnidadeEnabled){
        txtUnidade.addClass('infraReadOnly');
    } else {
        txtUnidade.removeClass('infraReadOnly');
    }
}

function avaliarPreCondicoes() {
    var houveErros = document.getElementById('hdnErrosValidacao').value;
    if(houveErros) {
        infraDesabilitarCamposDiv(document.getElementById('divProcedimentos'));
        infraDesabilitarCamposDiv(document.getElementById('divRepositorioEstruturas'));
        infraDesabilitarCamposDiv(document.getElementById('divUnidades'));

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
    iframe.setAttribute('scrolling', 'no');

    return iframe;
}

function exibirBarraProgresso(elemBarraProgresso){
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


function abrirBarraProgresso(form, action, largura, altura){

    if (typeof(form.onsubmit) == 'function' && !form.onsubmit()){
        return null;
    }

    iframe = criarIFrameBarraProgresso();
    exibirBarraProgresso(iframe);

    form.target = iframe.id;
    form.action = action;
    form.submit();
}


function redimencionarBarraProgresso(){
    var divFundo = document.getElementById('divFundoBarraProgresso');
    if (divFundo!=null){
        divFundo.style.width = infraClientWidth() + 'px';
        divFundo.style.height = infraClientHeight() + 'px';
    }
}

function tratarResultadoValidacao(resp, textStatus, jqXHR){
    if(!resp.sucesso) {
        var strRespMensagem = "Verifique alguns erros no processo antes de tramitar:\n\n";
        jQuery.each(resp.erros, function(strProtocoloFormatado, arrStrMensagem){
            strRespMensagem += "Nr. Processo: " + strProtocoloFormatado + ".\n";
            jQuery.each(arrStrMensagem, function(index, strMensagem){
            strRespMensagem += " - " + strMensagem + "\n";
            });

            strRespMensagem += "\n";
        });
        alert(strRespMensagem);
        return false;
    }
    var strAction = '<?=$strLinkValidacao ?>';
    abrirBarraProgresso(document.forms['frmLote'], strAction, 600, 200);
}


function enviarForm(event){
    var button = jQuery(event.target);
    var labelPadrao = button.html();

    button.attr('disabled', 'disabled').html('Validando...');

    var urlValidacao = '<?php print $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador_ajax.php?acao_ajax=pen_validar_expedir_lote'.$strParametros)); ?>';
    var objData = {};

    jQuery.each(['selProcedimentos', 'selRepositorioEstruturas', 'hdnIdUnidade'], function(index, name){
        var objInput = jQuery('#' + name);
        objData[name] = objInput.val();
    });

    jQuery('option', 'select#selProcedimentos').each(function(index, element){
        objData['selProcedimentos['+ index +']'] = jQuery(element).attr('value');
    });

    jQuery.ajax({
        url:urlValidacao,
        method:'POST',
        dataType:'json',
        data:objData,
        success: tratarResultadoValidacao
    }).done(function(){
        button.removeAttr('disabled').html(labelPadrao);
    });
}

</script>
<?php
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody($strTitulo, 'onload="inicializar();"');
?>
<form id="frmLote" name="frmLote" method="post" action="<?=$strLinkValidacao ?>">
    <input type="hidden" id="sbmExpedir" name="sbmExpedir" value="1" />
    <input type="hidden" id="sbmPesquisa" name="sbmPesquisa" value="1" />
<?php
$objPaginaSEI->montarBarraComandosSuperior($arrComandos);
?>

    <div id="divProcedimentos" class="infraAreaDados" style="height:8em;">
        <label id="lblProcedimentos" for="selProcedimentos" class="infraLabelObrigatorio">Protocolo:</label>
        <select id="selProcedimentos" name="selProcedimentos" size="3" class="infraSelect infraReadOnly" readonly="readonly" tabindex="<?=$objPaginaSEI->getProxTabDados()?>">
            <?=$strItensSelProcedimentos?>
        </select>
    </div>

	<div id="divRepositorioEstruturas" class="infraAreaDados" style="height: 4.5em;">
		<label id="lblRepositorioEstruturas" for="selRepositorioEstruturas" accesskey="" class="infraLabelObrigatorio">Repositório de Estruturas Organizacionais:</label>
        <select id="selRepositorioEstruturas" name="selRepositorioEstruturas" class="infraSelect" onchange="selecionarRepositorio();" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" >
        <?= $strItensSelRepositorioEstruturas ?>
        </select>
	</div>

	<div id="divUnidades" class="infraAreaDados" style="height: 4.5em;">
            <label id="lblUnidades" for="selUnidades" class="infraLabelObrigatorio">Unidade:</label>
            <div class="alinhamentoBotaoImput">
                <input type="text" id="txtUnidade" name="txtUnidade" class="infraText infraReadOnly" disabled="disabled"
                    placeholder="Digite o nome/sigla da unidade e pressione ENTER para iniciar a pesquisa rápida"
                    value="<?=$strNomeUnidadeDestino ?>" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" value="" />
                <button id="btnIdUnidade" type="button" class="infraText">Consultar</button>
                <img id="imgPesquisaAvancada" src="imagens/organograma.gif" alt="Consultar organograma" title="Consultar organograma" class="infraImg" />
            </div>

        <input type="hidden" id="hdnIdUnidade" name="hdnIdUnidade" class="infraText" value="<?=$numIdUnidadeDestino; ?>" />
	</div>

	<input type="hidden" id="hdnIdProcedimento" name="hdnIdProcedimento" value="<?=implode(',',$arrProtocolosOrigem) ?>" />
    <input type="hidden" id="hdnErrosValidacao" name="hdnErrosValidacao" value="<?=$bolErrosValidacao ?>" />
    <input type="hidden" id="hdnUnidadesAdministrativas" name="hdnUnidadesAdministrativas" value="" />

</form>
<?php
$objPaginaSEI->montarAreaDebug();
$objPaginaSEI->fecharBody();
$objPaginaSEI->fecharHtml();
?>
