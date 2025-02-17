<?php
try {
    include_once DIR_SEI_WEB.'/SEI.php';

    session_start();

    //////////////////////////////////////////////////////////////////////////////
    //InfraDebug::getInstance()->setBolLigado(true);
    //InfraDebug::getInstance()->setBolDebugInfra(true);
    //InfraDebug::getInstance()->limpar();
    //////////////////////////////////////////////////////////////////////////////

    $objSessaoSEI = SessaoSEI::getInstance();
    $objPaginaSEI = PaginaSEI::getInstance();

    $objSessaoSEI->validarLink();
    $objSessaoSEI->validarPermissao($_GET['acao']);

    $strParametros = '';
    $bolErrosValidacao = false;
    $executarExpedicao = false;
    $arrComandos = [];
    $objExpedirProcedimentosRN = new ExpedirProcedimentoRN();

    $idProcedimento = filter_var($_GET['id_procedimento'], FILTER_SANITIZE_NUMBER_INT);
    $strDiretorioModulo = PENIntegracao::getDiretorio();

  if(!$idProcedimento) {
      throw new InfraException('Módulo do Tramita: Processo não informado.');
  }

  if ($idProcedimento) {
      $strParametros .= '&id_procedimento=' . $idProcedimento;
  }

  if (isset($_GET['arvore'])) {
      $objPaginaSEI->setBolArvore($_GET['arvore']);
      $strParametros .= '&arvore=' . $_GET['arvore'];
  }

  if (isset($_GET['executar'])) {
      $executarExpedicao = filter_var($_GET['executar'], FILTER_VALIDATE_BOOLEAN);
  }

    $strLinkValidacao = $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=' . $_GET['acao'] . '&acao_origem=' . $_GET['acao'] . $strParametros));
    $strLinkProcedimento = $objSessaoSEI->assinarLink('controlador.php?acao=procedimento_trabalhar&acao_origem=procedimento_controlar&acao_retorno=procedimento_controlar&id_procedimento='.$idProcedimento);

  switch ($_GET['acao']) {

    case 'pen_procedimento_expedir':
        $strTitulo = 'Envio Externo de Processo';
        $arrComandos[] = '<button type="button" accesskey="E" onclick="enviarForm(event)" value="Enviar" class="infraButton" style="width:8%;"><span class="infraTeclaAtalho">E</span>nviar</button>';
        $arrComandos[] = '<button type="button" accesskey="C" name="btnCancelar" value="Cancelar" onclick="location.href=\'' . $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=' . $objPaginaSEI->getAcaoRetorno() . '&acao_origem=' . $_GET['acao'] . '&acao_destino=' . $_GET['acao'] . $strParametros)) . '\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';

        //Obter dados do repositório em que o SEI está registrado (Repositório de Origem)
        $objPenParametroRN = new PenParametroRN();
        $numIdRepositorioOrigem = $objPenParametroRN->getParametro('PEN_ID_REPOSITORIO_ORIGEM');

      try {
          $objUnidadeDTO = new PenUnidadeDTO();
          $objUnidadeDTO->retNumIdUnidadeRH();
          $objUnidadeDTO->setNumIdUnidade($objSessaoSEI->getNumIdUnidadeAtual());

          $objUnidadeRN = new UnidadeRN();
          $objUnidadeDTO = $objUnidadeRN->consultarRN0125($objUnidadeDTO);

          $objPenUnidadeRestricaoDTO = new PenUnidadeRestricaoDTO();
          $objPenUnidadeRestricaoDTO->setNumIdUnidade($objSessaoSEI->getNumIdUnidadeAtual());
          $objPenUnidadeRestricaoDTO->setNumIdUnidadeRH($objUnidadeDTO->getNumIdUnidadeRH());
          $objPenUnidadeRestricaoDTO->retNumIdUnidadeRestricao();
          $objPenUnidadeRestricaoDTO->retStrNomeUnidadeRestricao();

          $objPenUnidadeRestricaoRN = new PenUnidadeRestricaoRN();
          $arrIdUnidadeRestricao = $objPenUnidadeRestricaoRN->listar($objPenUnidadeRestricaoDTO);

          //Preparação dos dados para montagem da tela de expedição de processos
        if ($arrIdUnidadeRestricao != null) {
            $repositorios = [];
          foreach ($arrIdUnidadeRestricao as $value) {
            $repositorios[$value->getNumIdUnidadeRestricao()] = $value->getStrNomeUnidadeRestricao();
          }
        } else {
            $repositorios = $objExpedirProcedimentosRN->listarRepositoriosDeEstruturas();
        }
      } catch (Exception $e) {
          $repositorios = $objExpedirProcedimentosRN->listarRepositoriosDeEstruturas();
      }

        $motivosDeUrgencia = $objExpedirProcedimentosRN->consultarMotivosUrgencia();

        $idRepositorioSelecionado = $numIdRepositorio ?? '';
        $strItensSelRepositorioEstruturas = InfraINT::montarSelectArray('', 'Selecione', $idRepositorioSelecionado, $repositorios);

        $idMotivosUrgenciaSelecionado = $idMotivosUrgenciaSelecionado ?? '';
        $strItensSelMotivosUrgencia = InfraINT::montarSelectArray('', 'Selecione', $idMotivosUrgenciaSelecionado, $motivosDeUrgencia);

        $strLinkAjaxUnidade = $objSessaoSEI->assinarLink('controlador_ajax.php?acao_ajax=pen_unidade_auto_completar_expedir_procedimento&acao=' . $_GET['acao']);
        $strLinkAjaxProcedimentoApensado = $objSessaoSEI->assinarLink('controlador_ajax.php?acao_ajax=pen_apensados_auto_completar_expedir_procedimento');

        $strLinkProcedimentosApensadosSelecao = $objSessaoSEI->assinarLink('controlador.php?acao=pen_apensados_selecionar_expedir_procedimento&tipo_selecao=2&id_object=objLupaProcedimentosApensados&id_procedimento='.$idProcedimento.'');
        $strLinkUnidadesAdministrativasSelecao = $objSessaoSEI->assinarLink('controlador.php?acao=pen_unidades_administrativas_externas_selecionar_expedir_procedimento&tipo_pesquisa=1&id_object=objLupaUnidadesAdministrativas');

      if (!$objUnidadeDTO) {
        throw new InfraException("A unidade atual não foi mapeada.");
      }

        $numIdUnidadeOrigem = $objUnidadeDTO->getNumIdUnidadeRH();
        $numIdProcedimento = $_POST['hdnIdProcedimento'];
        $strProtocoloProcedimentoFormatado = $_POST['txtProtocoloExibir'];
        $numIdRepositorio = $_POST['selRepositorioEstruturas'];
        $strRepositorio = (array_key_exists($numIdRepositorio, $repositorios) ? $repositorios[$numIdRepositorio] : '');
        $numIdUnidadeDestino = $_POST['hdnIdUnidade'];
        $strNomeUnidadeDestino = $_POST['txtUnidade'];
        $numIdMotivoUrgente = $_POST['selMotivosUrgencia'];
        $boolSinUrgente = $objPaginaSEI->getCheckbox($_POST['chkSinUrgente'], true, false);
        $arrIdProcedimentosApensados = $objPaginaSEI->getArrValuesSelect($_POST['hdnProcedimentosApensados']);

        //Carregar dados do procedimento na primeiro acesso à página
      if (!isset($_POST['hdnIdProcedimento'])) {
            $objProcedimentoRN = new ProcedimentoRN();
            $objProcedimentoDTO = $objExpedirProcedimentosRN->consultarProcedimento($idProcedimento);
            $numIdProcedimento = $objProcedimentoDTO->getDblIdProcedimento();
            $strProtocoloProcedimentoFormatado = $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado();
      }

      if(isset($_POST['sbmExpedir'])) {
          $numVersao = $objPaginaSEI->getNumVersao();
          echo "<link href='$strDiretorioModulo/css/" . ProcessoEletronicoINT::getCssCompatibilidadeSEI4("pen_procedimento_expedir.css") . "' rel='stylesheet' type='text/css' media='all' />\n";
          echo "<script type='text/javascript' charset='iso-8859-1' src='$strDiretorioModulo/js/expedir_processo/pen_procedimento_expedir.js?$numVersao'></script>";

          $strTituloPagina = "Envio externo do processo $strProtocoloProcedimentoFormatado";
          $objPaginaSEI->prepararBarraProgresso($strTitulo, $strTituloPagina);

          $objExpedirProcedimentoDTO = new ExpedirProcedimentoDTO();
          $objExpedirProcedimentoDTO->setNumIdRepositorioOrigem($numIdRepositorioOrigem);
          $objExpedirProcedimentoDTO->setNumIdUnidadeOrigem($numIdUnidadeOrigem);

          $objExpedirProcedimentoDTO->setNumIdRepositorioDestino($numIdRepositorio);
          $objExpedirProcedimentoDTO->setStrRepositorioDestino($strRepositorio);
          $objExpedirProcedimentoDTO->setNumIdUnidadeDestino($numIdUnidadeDestino);
          $objExpedirProcedimentoDTO->setStrUnidadeDestino($strNomeUnidadeDestino);
          $objExpedirProcedimentoDTO->setArrIdProcessoApensado($arrIdProcedimentosApensados);
          $objExpedirProcedimentoDTO->setBolSinUrgente($boolSinUrgente);
          $objExpedirProcedimentoDTO->setDblIdProcedimento($numIdProcedimento);
          $objExpedirProcedimentoDTO->setNumIdMotivoUrgencia($numIdMotivoUrgente);
          $objExpedirProcedimentoDTO->setBolSinProcessamentoEmBloco(false);
          $objExpedirProcedimentoDTO->setNumIdBloco(null);
          $objExpedirProcedimentoDTO->setNumIdAtividade(null);
          $objExpedirProcedimentoDTO->setNumIdUnidade(null);

        try {
            $objExpedirProcedimentosRN->setEventoEnvioMetadados(
                function ($parNumIdTramite) use ($strLinkProcedimento): void {
                    $strLinkCancelarAjax = SessaoSEI::getInstance()->assinarLink('controlador_ajax.php?acao_ajax=pen_procedimento_expedir_cancelar&id_tramite='.$parNumIdTramite);
                    echo "<script type='text/javascript'>adicionarBotaoCancelarEnvio('$parNumIdTramite', '$strLinkCancelarAjax', '$strLinkProcedimento');</script> ";
                }
            );

            $respostaExpedir = $objExpedirProcedimentosRN->expedirProcedimento($objExpedirProcedimentoDTO);
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
        throw new InfraException("Módulo do Tramita: Ação '" . $_GET['acao'] . "' não reconhecida.");
  }

} catch (Exception $e) {
    throw new InfraException("Módulo do Tramita: Erro processando requisição de envio externo de processo", $e);
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

#selProcedimentosApensados {position:absolute;left:0%;top:43%;width:86%;}
#imgLupaProcedimentosApensados {position:absolute;left:87%;top:43%;}
#imgExcluirProcedimentosApensados {position:absolute;left:87%;top:60%;}

#lblMotivosUrgencia {position:absolute;left:0%;top:0%;width:50%;}
#selMotivosUrgencia {position:absolute;left:0%;top:38%;width:51%;}


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

    objLupaProcedimentosApensados = new infraLupaSelect('selProcedimentosApensados','hdnProcedimentosApensados','<?php echo $strLinkProcedimentosApensadosSelecao ?>');
    objLupaUnidadesAdministrativas = new infraLupaSelect('selRepositorioEstruturas','hdnUnidadesAdministrativas','<?php echo $strLinkUnidadesAdministrativasSelecao ?>');

    objAutoCompletarEstrutura = new infraAjaxAutoCompletar('hdnIdUnidade','txtUnidade','<?php echo $strLinkAjaxUnidade?>', "Nenhuma unidade foi encontrada");
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

    objLupaUnidadesAdministrativas.prepararExecucao = function(){
        var parametros = '&idRepositorioEstrutura='+$('#selRepositorioEstruturas').val();
        return parametros;
    };

    objAutoCompletarApensados = new infraAjaxAutoCompletar('hdnIdProcedimentoApensado','txtProcedimentoApensado','<?php echo $strLinkAjaxProcedimentoApensado?>');
    objAutoCompletarApensados.mostrarAviso = true;
    objAutoCompletarApensados.tamanhoMinimo = 3;
    objAutoCompletarApensados.limparCampo = true;

    objAutoCompletarApensados.prepararExecucao = function(){
        var parametros = 'palavras_pesquisa='+document.getElementById('txtProcedimentoApensado').value;
        parametros += '&id_procedimento_atual='+document.getElementById('hdnIdProcedimento').value;
        return parametros;
    };

    objAutoCompletarApensados.processarResultado = function(id,descricao,complemento){

        if (id!=''){
            var options = document.getElementById('selProcedimentosApensados').options;

            for(var i=0;i < options.length;i++){
                if (options[i].value == id){
                    self.setTimeout('alert(\'O processo já consta na lista.\')',100);
                    break;
                }
            }

            if (i==options.length){
                for(i=0;i < options.length;i++){
                    options[i].selected = false;
                }

                opt = infraSelectAdicionarOption(document.getElementById('selProcedimentosApensados'),descricao,id);
                objLupaProcedimentosApensados.atualizar();
                opt.selected = true;
            }

            document.getElementById('txtProcedimentoApensado').value = '';
            document.getElementById('txtProcedimentoApensado').focus();
        }

    };

    document.getElementById('selRepositorioEstruturas').focus();
}

function validarCadastroAbrirRI0825()
{
    if (!infraSelectSelecionado('selUnidades')) {
        alert('Informe as Unidades de Destino.');
        document.getElementById('selUnidades').focus();
        return false;
    }

    return true;
}

function selecionarUrgencia()
{
    var chkSinUrgenteEnabled = $('#chkSinUrgente').is(':checked');
    $('#selMotivosUrgencia').prop('disabled', !chkSinUrgenteEnabled);

    if(!chkSinUrgenteEnabled){
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
    var strAction = '<?php echo $strLinkValidacao ?>';
    abrirBarraProgresso(document.forms['frmExpedirProcedimento'], strAction, 600, 200);
}


function enviarForm(event){
    var button = jQuery(event.target);
    var labelPadrao = button.html();

    button.attr('disabled', 'disabled').html('Validando...');

    var urlValidacao = '<?php print $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador_ajax.php?acao_ajax=pen_procedimento_expedir_validar'.$strParametros)); ?>';
    var objData = {};

    jQuery.each(['txtProtocoloExibir', 'selRepositorioEstruturas', 'hdnIdUnidade'], function(index, name){
        var objInput = jQuery('#' + name);
        objData[name] = objInput.val();
    });

    jQuery('option', 'select#selProcedimentosApensados').each(function(index, element){
        objData['selProcedimentosApensados['+ index +']'] = jQuery(element).attr('value');
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
<form id="frmExpedirProcedimento" name="frmExpedirProcedimento" method="post" action="<?php echo $strLinkValidacao ?>">
    <input type="hidden" id="sbmExpedir" name="sbmExpedir" value="1" />
    <input type="hidden" id="sbmPesquisa" name="sbmPesquisa" value="1" />
<?php
$objPaginaSEI->montarBarraComandosSuperior($arrComandos);
?>
    <div id="divProtocoloExibir" class="infraAreaDados" style="height: 4.5em;">
        <label id="lblProtocoloExibir" for="txtProtocoloExibir" accesskey="" class="infraLabelObrigatorio">Protocolo:</label>
        <input type="text" id="txtProtocoloExibir" name="txtProtocoloExibir" class="infraText infraReadOnly" readonly="readonly" value="<?php echo $strProtocoloProcedimentoFormatado; ?>" tabindex="<?php echo $objPaginaSEI->getProxTabDados() ?>" />
    </div>

    <div id="divRepositorioEstruturas" class="infraAreaDados" style="height: 4.5em;">
        <label id="lblRepositorioEstruturas" for="selRepositorioEstruturas" accesskey="" class="infraLabelObrigatorio">Repositório de Estruturas Organizacionais:</label>
        <select id="selRepositorioEstruturas" name="selRepositorioEstruturas" class="infraSelect" onchange="selecionarRepositorio();" tabindex="<?php echo $objPaginaSEI->getProxTabDados() ?>" >
        <?php echo $strItensSelRepositorioEstruturas ?>
        </select>
    </div>

    <div id="divUnidades" class="infraAreaDados" style="height: 4.5em;">
            <label id="lblUnidades" for="selUnidades" class="infraLabelObrigatorio">Unidade:</label>
            <div class="alinhamentoBotaoImput">
                <input type="text" id="txtUnidade" name="txtUnidade" class="infraText infraReadOnly" disabled="disabled"
                    placeholder="Digite o nome/sigla da unidade e pressione ENTER para iniciar a pesquisa rápida"
                    value="<?php echo $strNomeUnidadeDestino ?>" tabindex="<?php echo $objPaginaSEI->getProxTabDados() ?>" value="" />
                <button id="btnIdUnidade" type="button" class="infraButton">Consultar</button>
                <!-- <img id="imgPesquisaAvancada" src="imagens/organograma.gif" alt="Consultar organograma" title="Consultar organograma" class="infraImg" /> -->
            </div>

        <input type="hidden" id="hdnIdUnidade" name="hdnIdUnidade" class="infraText" value="<?php echo $numIdUnidadeDestino; ?>" />
    </div>

    <div id="divProcedimentosApensados" class="infraAreaDados" style="height: 12em; display: none; ">
        <label id="lblProcedimentosApensados" for="selProcedimentosApensados" class="infraLabelObrigatorio">Processos Apensados:</label>
        <input type="text" id="txtProcedimentoApensado" name="txtProcedimentoApensado" class="infraText" tabindex="<?php echo $objPaginaSEI->getProxTabDados() ?>" />
        <input type="hidden" id="hdnIdProcedimentoApensado" name="hdnIdProcedimentoApensado" value="" />
        <select id="selProcedimentosApensados" name="selProcedimentosApensados[ ]" size="4" multiple="multiple" class="infraSelect" tabindex="<?php echo $objPaginaSEI->getProxTabDados() ?>"></select>
        <img id="imgLupaProcedimentosApensados" onclick="objLupaProcedimentosApensados.selecionar(700,500);" src="/infra_css/imagens/lupa.gif" alt="Selecionar Processos Apensados" title="Selecionar Processos Apensados" class="infraImg" tabindex="<?php echo $objPaginaSEI->getProxTabDados() ?>" />
        <img id="imgExcluirProcedimentosApensados" onclick="objLupaProcedimentosApensados.remover();" src="/infra_css/imagens/remover.gif" alt="Remover Processo Apensado" title="Remover Processo Apensado" class="infraImg" tabindex="<?php echo $objPaginaSEI->getProxTabDados() ?>" />
    </div>


    <div id="divMotivosUrgencia" class="infraAreaDados" style="height: 4.5em; display:none">
        <label id="lblMotivosUrgencia" for="selMotivosUrgencia" accesskey="" class="infraLabel">Motivo da Urgência:</label>
        <select id="selMotivosUrgencia" name="selMotivosUrgencia" class="infraSelect infraReadOnly" disabled="disabled" tabindex="<?php echo $objPaginaSEI->getProxTabDados() ?>">
        <?php echo $strItensSelMotivosUrgencia ?>
        </select>
    </div>

    <input type="hidden" id="hdnIdProcedimento" name="hdnIdProcedimento" value="<?php echo $numIdProcedimento ?>" />
    <input type="hidden" id="hdnErrosValidacao" name="hdnErrosValidacao" value="<?php echo $bolErrosValidacao ?>" />
    <input type="hidden" id="hdnProcedimentosApensados" name="hdnProcedimentosApensados" value="<?php echo htmlspecialchars($_POST['hdnProcedimentosApensados'])?>" />
    <input type="hidden" id="hdnUnidadesAdministrativas" name="hdnUnidadesAdministrativas" value="" />

</form>
<?php
$objPaginaSEI->montarAreaDebug();
$objPaginaSEI->fecharBody();
$objPaginaSEI->fecharHtml();
?>
