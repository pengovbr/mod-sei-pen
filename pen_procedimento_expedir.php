<?php
try {
    require_once dirname(__FILE__) . '/../../SEI.php';

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
    $arrComandos = array();
    $objExpedirProcedimentosRN = new ExpedirProcedimentoRN();

    $idProcedimento = filter_var($_GET['id_procedimento'], FILTER_SANITIZE_NUMBER_INT);

    if(!$idProcedimento){
        throw new InfraException('Processo não informado.');
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
    
    
    
    
    //$objPaginaSEI->setBolExibirMensagens(false);

    //$resultProcessosAnexados = $objExpedirProcedimentosRN->consultarProcessosApensados($idProcedimento);
      
    //$strLinkAssuntosSelecao = $objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_processo_anexado&tipo_selecao=2&id_object=objLupaAssuntos');

         
    switch ($_GET['acao']) {

        case 'pen_procedimento_expedir':
            
            $strTitulo = 'Envio Externo de Processo';
            $arrComandos[] = '<button type="button" accesskey="E" onclick="enviarForm(this)" value="Enviar" class="infraButton" style="width:8%;"><span class="infraTeclaAtalho">E</span>nviar</button>';
            $arrComandos[] = '<button type="button" accesskey="C" name="btnCancelar" value="Cancelar" onclick="location.href=\'' . $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=' . $objPaginaSEI->getAcaoRetorno() . '&acao_origem=' . $_GET['acao'] . '&acao_destino=' . $_GET['acao'] . $strParametros)) . '\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';

            //TODO: Avaliar a necessidade de validar cada um dos parâmetros do PEN exigidos por essa funcionalidade
            //Obter dados do repositório em que o SEI está registrado (Repositório de Origem)
            $objPenParametroRN = new PenParametroRN();
            $numIdRepositorioOrigem = $objPenParametroRN->getParametro('PEN_ID_REPOSITORIO_ORIGEM');
            
            //Preparação dos dados para montagem da tela de expedição de processos
            $repositorios = $objExpedirProcedimentosRN->listarRepositoriosDeEstruturas();            
            $motivosDeUrgencia = $objExpedirProcedimentosRN->consultarMotivosUrgencia();

            $idRepositorioSelecionado = (isset($numIdRepositorio)) ? $numIdRepositorio : '';
            $strItensSelRepositorioEstruturas = InfraINT::montarSelectArray('', 'Selecione', $idRepositorioSelecionado, $repositorios);
            
            $idMotivosUrgenciaSelecionado = (isset($idMotivosUrgenciaSelecionado)) ? $idMotivosUrgenciaSelecionado : '';
            $strItensSelMotivosUrgencia = InfraINT::montarSelectArray('', 'Selecione', $idMotivosUrgenciaSelecionado, $motivosDeUrgencia);            

            $strLinkAjaxUnidade = $objSessaoSEI->assinarLink('controlador_ajax.php?acao_ajax=pen_unidade_auto_completar_expedir_procedimento');         
            $strLinkAjaxProcedimentoApensado = $objSessaoSEI->assinarLink('controlador_ajax.php?acao_ajax=pen_apensados_auto_completar_expedir_procedimento');
            
            //$strLinkUnidadeSelecao = $objSessaoSEI->assinarLink('controlador.php?acao=pen_unidade_sel_expedir_procedimento&tipo_selecao=2&id_object=objLupaUnidades');
            //$strLinkRepositorioSelecao = $objSessaoSEI->assinarLink('controlador.php?acao=pen_repositorio_selecionar_expedir_procedimento&tipo_selecao=2&id_object=objLupaProcedimentosApensados');
            $strLinkProcedimentosApensadosSelecao = $objSessaoSEI->assinarLink('controlador.php?acao=pen_apensados_selecionar_expedir_procedimento&tipo_selecao=2&id_object=objLupaProcedimentosApensados&id_procedimento='.$idProcedimento.'');

            //TODO: Obter dados do repositório e unidade de orígem através de serviço do PEN
            //Obtenção dos parâmetros selecionados pelo usuário
                        
            //TODO: Obter repositório de origem a partir dos parâmetros do sistema
            //$numIdRepositorioOrigem = 1;
            //$numIdUnidadeOrigem = 161313;
            
            //TODO: Atualmente, o campo ID Unidade RH irá conter o código da unidade registrado no barramento. 
            //A ideia é que no futura, o campo contenha o código do SIORG e busque no barramento qual o código da estrutura

            //$objSessaoSEI->getNumIdUnidadeAtual()
            $objUnidadeDTO = new PenUnidadeDTO();            
            $objUnidadeDTO->retNumIdUnidadeRH();
            $objUnidadeDTO->setNumIdUnidade($objSessaoSEI->getNumIdUnidadeAtual());
      
            $objUnidadeRN = new UnidadeRN();
            $objUnidadeDTO = $objUnidadeRN->consultarRN0125($objUnidadeDTO);
            
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
//                $objProcedimentoDTO->setArrObjDocumentoDTO($objExpedirProcedimentosRN->listarDocumentos($idProcedimento));
//                $objProcedimentoDTO->setArrObjParticipanteDTO($objExpedirProcedimentosRN->listarInteressados($idProcedimento));
//
//                try {
//                    //Validação das pré-condições para que o processo possa ser expedido
//                    $objInfraException = new InfraException();                                        
//                    $objExpedirProcedimentosRN->validarPreCondicoesExpedirProcedimento($objInfraException, $objProcedimentoDTO);
//                    $objInfraException->lancarValidacoes();
//                } catch(Exception $e){
//                    $bolErrosValidacao = true;
//                    $objPaginaSEI->processarExcecao($e);
//                } 

                $numIdProcedimento = $objProcedimentoDTO->getDblIdProcedimento();
                $strProtocoloProcedimentoFormatado = $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado();
            }

            //------------------------------------------------------------------
            // Executado dentro da window
            //------------------------------------------------------------------
            //Tratamento da ação de expedir o procedimento
            if(isset($_POST['sbmExpedir'])) {            

                $strTituloPagina = "Envio externo do processo $strProtocoloProcedimentoFormatado";
                $objPaginaSEI->prepararBarraProgresso($strTitulo, $strTituloPagina);                
                
                $objExpedirProcedimentoDTO = new ExpedirProcedimentoDTO();                     
                
                //TODO: Remover atribuição de tais parâmetros de 
                $objExpedirProcedimentoDTO->setNumIdRepositorioOrigem($numIdRepositorioOrigem);
                $objExpedirProcedimentoDTO->setNumIdUnidadeOrigem($numIdUnidadeOrigem);

                //$objExpedirProcedimentoDTO->setNumIdUnidadeAtual($objSessaoSEI->getNumIdUnidadeAtual());

                $objExpedirProcedimentoDTO->setNumIdRepositorioDestino($numIdRepositorio);
                $objExpedirProcedimentoDTO->setStrRepositorioDestino($strRepositorio);
                $objExpedirProcedimentoDTO->setNumIdUnidadeDestino($numIdUnidadeDestino);
                $objExpedirProcedimentoDTO->setStrUnidadeDestino($strNomeUnidadeDestino);
                $objExpedirProcedimentoDTO->setArrIdProcessoApensado($arrIdProcedimentosApensados);
                $objExpedirProcedimentoDTO->setBolSinUrgente($boolSinUrgente);
                $objExpedirProcedimentoDTO->setDblIdProcedimento($numIdProcedimento);
                $objExpedirProcedimentoDTO->setNumIdMotivoUrgencia($numIdMotivoUrgente);

                try {
                    $respostaExpedir = $objExpedirProcedimentosRN->expedirProcedimento($objExpedirProcedimentoDTO);
                    
                    echo '<input type="button" onclick="javascript:window.close()" class="botao_fechar" value="Fechar" '
                         . 'style="margin-left: 84%; margin-top: 4%;"/>'; //Botão para fechar a janela
                } catch(\Exception $e) {                    
                    $objPaginaSEI->processarExcecao($e);
                }

                // Faz o die();
                $objPaginaSEI->finalizarBarraProgresso(null, false);
            }
            //------------------------------------------------------------------
            break;
        default:
            throw new InfraException("Ação '" . $_GET['acao'] . "' não reconhecida.");
    }

} catch (Exception $e) {    
    //$objPaginaSEI->finalizarBarraProgresso($objSessaoSEI->assinarLink('controlador.php?acao='.$objPaginaSEI->getAcaoRetorno().'&acao_origem='.$_GET['acao'].'#ID-'.$IdProcedimento));
    //$objPaginaSEI->processarExcecao($e);
    throw new InfraException("Error Processing Request 11", $e);
    
}

$objPaginaSEI->montarDocType();
$objPaginaSEI->abrirHtml();
$objPaginaSEI->abrirHead();
$objPaginaSEI->montarMeta();
$objPaginaSEI->montarTitle(':: ' . $objPaginaSEI->getStrNomeSistema() . ' - ' . $strTitulo . ' ::');
$objPaginaSEI->montarStyle();
?>
<style type="text/css">
#lblProtocoloExibir {position:absolute;left:0%;top:0%;}
#txtProtocoloExibir {position:absolute;left:0%;top:38%;width:50%;}

#lblRepositorioEstruturas {position:absolute;left:0%;top:10%;width:50%;}
#selRepositorioEstruturas {position:absolute;left:0%;top:48%;width:51%;}

#lblUnidades {position:absolute;left:0%;top:10%;}
#txtUnidade {left:0%;top:48%;width:50%;border:.1em solid #666;}
#imgLupaUnidades {position:absolute;left:52%;top:48%;}
.alinhamentoBotaoImput{position:absolute;left:0%;top:48%;width:85%;};
#hdnIdUnidade2 {float: right;}

#lblProcedimentosApensados {position:absolute;left:0%;top:10%;}
#txtProcedimentoApensado {position:absolute;left:0%;top:25%;width:50%;border:.1em solid #666;}
#selProcedimentosApensados {position:absolute;left:0%;top:43%;width:86%;}
#imgLupaProcedimentosApensados {position:absolute;left:87%;top:43%;}
#imgExcluirProcedimentosApensados {position:absolute;left:87%;top:60%;}

#lblMotivosUrgencia {position:absolute;left:0%;top:10%;width:50%;}
#selMotivosUrgencia {position:absolute;left:0%;top:48%;width:51%;}

</style>
<?php $objPaginaSEI->montarJavaScript(); ?>
<script type="text/javascript">

var objAutoCompletarUnidade = null;
var objAutoCompletarEstrutura = null;
var objAutoCompletarProcedimentosApensados = null;

var objLupaUnidades = null;
var objLupaProcedimentosApensados = null;
var objJanelaExpedir = null;
var evnJanelaExpedir = null;

function inicializar() {
    
    objLupaProcedimentosApensados = new infraLupaSelect('selProcedimentosApensados','hdnProcedimentosApensados','<?=$strLinkProcedimentosApensadosSelecao ?>');
    
    objAutoCompletarEstrutura = new infraAjaxAutoCompletar('hdnIdUnidade','txtUnidade','<?=$strLinkAjaxUnidade?>', "Nenhuma unidade foi encontrada");
    objAutoCompletarEstrutura.bolExecucaoAutomatica = false;
    objAutoCompletarEstrutura.mostrarAviso = true;
    //objAutoCompletarEstrutura.tamanhoMinimo = 3;
    objAutoCompletarEstrutura.limparCampo = false;    
    //objAutoCompletarEstrutura.mostrarImagemVerificado = true;
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
    
    $('#hdnIdUnidade2').click(function() {
        objAutoCompletarEstrutura.executar();
        objAutoCompletarEstrutura.procurar();
    });

    objAutoCompletarApensados = new infraAjaxAutoCompletar('hdnIdProcedimentoApensado','txtProcedimentoApensado','<?=$strLinkAjaxProcedimentoApensado?>');
    objAutoCompletarApensados.mostrarAviso = true;
    objAutoCompletarApensados.tamanhoMinimo = 3;
    objAutoCompletarApensados.limparCampo = true;

    objAutoCompletarApensados.prepararExecucao = function(){
        //return 'palavras_pesquisa='+document.getElementById('txtProcedimentoApensado').value;
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

    //objLupaUnidades = new infraLupaSelect('txtUnidade','hdnIdUnidade','<?= $strLinkUnidadeSelecao ?>');

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

/**
 * Simula o evento onclose do pop-up
 * 
 * @return {null}
 */
function monitorarJanela(){
    
  if(objJanelaExpedir.closed) {
    
        window.clearInterval(evnJanelaExpedir);
        
        jQuery('#divInfraModalFundo', window.parent.document).css('visibility', 'hidden');

        var strDestino = '<?php print $objSessaoSEI->assinarLink('controlador.php?acao=procedimento_trabalhar&acao_origem=procedimento_controlar&acao_retorno=procedimento_controlar&id_procedimento='.$idProcedimento); ?>';
        
        if(strDestino) {
            window.top.location =  strDestino;
        }
    }
}

/**
 * Gera a pop-up de expedir procedimento e cria os gatilho para o próprio fechamento
 * 
 * @return {null}
 */
function abrirJanela(nome,largura,altura){
    
    var opcoes = 'location=0,status=0,resizable=0,scrollbars=1,width=' + largura + ',height=' + altura;
    var largura = largura || 100;
    var altura = altura || 100;
      
    var janela = window.open('', nome, opcoes);
  
    try{
        if (INFRA_CHROME>17) {
            setTimeout(function() {
                janela.moveTo(((screen.availWidth/2) - (largura/2)),((screen.availHeight/2) - (altura/2)));
            },100); 
        }
        else {
            janela.moveTo(((screen.availWidth/2) - (largura/2)),((screen.availHeight/2) - (altura/2)));
        }
        janela.focus();
    }
    catch(e){}
  
    
    infraJanelaModal = janela;

    var div = parent.document.getElementById('divInfraModalFundo');

    if (div==null){
      div = parent.document.createElement('div');
      div.id = 'divInfraModalFundo';
      div.className = 'infraFundoTransparente';
      
      if (INFRA_IE > 0 && INFRA_IE < 7){
        ifr = parent.document.createElement('iframe');
        ifr.className =  'infraFundoIE';
        div.appendChild(ifr);  
      }else{
        div.onclick = function(){
          try{
            infraJanelaModal.focus();
          }catch(exc){ }
        }
      }
      parent.document.body.appendChild(div);  
    }
    
    if (INFRA_IE==0 || INFRA_IE>=7){
      div.style.position = 'fixed';
    }
    
    div.style.width = parent.infraClientWidth() + 'px';
    div.style.height = parent.infraClientHeight() + 'px';
    div.style.visibility = 'visible';
    
    evnJanelaExpedir = window.setInterval('monitorarJanela()', 100);
  
    return janela;
}

function abrirBarraProgresso(form, action, largura, altura){

    if (typeof(form.onsubmit) == 'function' && !form.onsubmit()){
        return null;
    }

    var nomeJanela = 'janelaProgresso' + (new Date()).getTime();
    objJanelaExpedir = abrirJanela(nomeJanela, largura, altura);
    form.target = nomeJanela;
    form.action = action;
    form.submit();
  }


function enviarForm(el){
    
    var button = jQuery(el);
    var label = button.html();
    
    button.attr('disabled', 'disabled').html('Validando...');

    var strUrl = '<?php print $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador_ajax.php?acao_ajax=pen_procedimento_expedir_validar'.$strParametros)); ?>';
    var objData = {};   

    jQuery.each(['txtProtocoloExibir', 'selRepositorioEstruturas', 'hdnIdUnidade'], function(index, name){
        
        var objInput = jQuery('#' + name);
        
        objData[name] = objInput.val();
    });
    
    jQuery('option', 'select#selProcedimentosApensados').each(function(index, element){
                
        objData['selProcedimentosApensados['+ index +']'] = jQuery(element).attr('value');
    });
    
    jQuery.ajax({
        
        url:strUrl,
        method:'POST',
        dataType:'json',
        data:objData,
        success:function(resp, textStatus, jqXHR) {
                        
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
            var strAction = '<?php print $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao='.$_GET['acao'] . '&acao_origem=' . $_GET['acao'] . '&acao_destino=' . $_GET['acao'] .'&'.$strParametros.'&executar=1')); ?>';    
            abrirBarraProgresso(document.forms['frmExpedirProcedimento'], strAction, 600, 200);
        }
    }).done(function(){
    
        button.removeAttr('disabled').html(label);
    });
}

</script>
<?php
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody($strTitulo, 'onload="inicializar();"');
?>
<form id="frmExpedirProcedimento" name="frmExpedirProcedimento"
	method="post"
	action="<?= $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=' . $_GET['acao'] . '&acao_origem=' . $_GET['acao'] . $strParametros)) ?>">
    <input type="hidden" id="sbmExpedir" name="sbmExpedir" value="1" />
<?php
//$objPaginaSEI->montarBarraLocalizacao($strTitulo);
$objPaginaSEI->montarBarraComandosSuperior($arrComandos);
//$objPaginaSEI->montarAreaValidacao();
?>        
    <div id="divProtocoloExibir" class="infraAreaDados" style="height: 4.5em;">
		<label id="lblProtocoloExibir" for="txtProtocoloExibir" accesskey="" class="infraLabelObrigatorio">Protocolo:</label> 
        <input type="text" id="txtProtocoloExibir" name="txtProtocoloExibir" class="infraText infraReadOnly" readonly="readonly" value="<?=$strProtocoloProcedimentoFormatado; ?>" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />
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
                <input type="text" id="txtUnidade" name="txtUnidade" class="infraText infraReadOnly" disabled="disabled" value="<?=$strNomeUnidadeDestino ?>" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" value="" />
                <button id="hdnIdUnidade2" type="button" class="infraText">Pesquisar</button>    
            </div>
        
        <input type="hidden" id="hdnIdUnidade" name="hdnIdUnidade" class="infraText" value="<?=$numIdUnidadeDestino; ?>" /> 
        <?php /* ?><img id="imgLupaUnidades" src="/infra_css/imagens/lupa.gif" alt="Selecionar Unidades" title="Selecionar Unidades" class="infraImg" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" /><?php */ ?>
        
	</div>

	<div id="divProcedimentosApensados" class="infraAreaDados" style="height: 12em; display: none; ">
		<label id="lblProcedimentosApensados" for="selProcedimentosApensados" class="infraLabelObrigatorio">Processos Apensados:</label> 
        <input type="text" id="txtProcedimentoApensado" name="txtProcedimentoApensado" class="infraText" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" /> 
        <input type="hidden" id="hdnIdProcedimentoApensado" name="hdnIdProcedimentoApensado" class="infraText" value="" /> 
        <select id="selProcedimentosApensados" name="selProcedimentosApensados[ ]" size="4" multiple="multiple" class="infraSelect" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>"></select> 
        <img id="imgLupaProcedimentosApensados" onclick="objLupaProcedimentosApensados.selecionar(700,500);" src="/infra_css/imagens/lupa.gif" alt="Selecionar Processos Apensados" title="Selecionar Processos Apensados" class="infraImg" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" /> 
        <img id="imgExcluirProcedimentosApensados" onclick="objLupaProcedimentosApensados.remover();" src="/infra_css/imagens/remover.gif" alt="Remover Processo Apensado" title="Remover Processo Apensado" class="infraImg" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />
	</div>

	<div id="divSinUrgente" class="infraDivCheckbox">
		<input type="checkbox" id="chkSinUrgente" name="chkSinUrgente" class="infraCheckbox" onclick="selecionarUrgencia();" <?= $objPaginaSEI->setCheckbox($boolSinUrgente, true, false) ?> tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" /> 
        <label id="lblSinUrgente" for="chkSinUrgente" accesskey="" class="infraLabelCheckbox">Urgente</label>
	</div>

	<div id="divMotivosUrgencia" class="infraAreaDados" style="height: 4.5em; display:none">
		<label id="lblMotivosUrgencia" for="selMotivosUrgencia" accesskey="" class="infraLabel">Motivo da Urgência:</label> 
        <select id="selMotivosUrgencia" name="selMotivosUrgencia" class="infraSelect infraReadOnly" disabled="disabled" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>">
        <?= $strItensSelMotivosUrgencia ?>
        </select>
	</div>

	<input type="hidden" id="hdnIdProcedimento" name="hdnIdProcedimento" value="<?=$numIdProcedimento ?>" /> 
    <input type="hidden" id="hdnErrosValidacao" name="hdnErrosValidacao" value="<?=$bolErrosValidacao ?>" /> 
    <input type="hidden" id="hdnProcedimentosApensados" name="hdnProcedimentosApensados" value="<?=$_POST['hdnProcedimentosApensados']?>" />    
<?
//$objPaginaSEI->montarBarraComandosInferior($arrComandos);
?>
</form>
<?php
$objPaginaSEI->montarAreaDebug();
$objPaginaSEI->fecharBody();
$objPaginaSEI->fecharHtml();
?>