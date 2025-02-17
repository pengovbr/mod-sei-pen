<?
try {

  require_once DIR_SEI_WEB.'/SEI.php';

  session_start();

  //////////////////////////////////////////////////////////////////////////////
  //InfraDebug::getInstance()->setBolLigado(false);
  //InfraDebug::getInstance()->setBolDebugInfra(true);
  //InfraDebug::getInstance()->limpar();
  //////////////////////////////////////////////////////////////////////////////

  SessaoSEI::getInstance()->validarLink();
  SessaoSEI::getInstance()->validarPermissao($_GET['acao']);
  PaginaSEI::getInstance()->salvarCamposPost(array('selTipoDocumentoPadrao'));

  $strParametros = '';
  if(isset($_GET['arvore'])){
    PaginaSEI::getInstance()->setBolArvore($_GET['arvore']);
    $strParametros .= '&arvore='.$_GET['arvore'];
  }

  if (isset($_GET['id_procedimento'])){
    $strParametros .= '&id_procedimento='.$_GET['id_procedimento'];
  }

  $arrComandos = array();

  $objPenRelTipoDocMapRecebidoRN = new PenRelTipoDocMapRecebidoRN();
  $numTipoDocumentoPadrao = $objPenRelTipoDocMapRecebidoRN->consultarTipoDocumentoPadrao();

  switch($_GET['acao']){

    case 'pen_map_tipo_doc_recebimento_padrao_atribuir':
      $strTitulo = 'Atribuir Tipo de Documento Padrão para Recebimento';
      $arrComandos[] = '<button type="submit" accesskey="S" name="sbmSalvar" value="Salvar" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
      $arrComandos[] = '<button type="button" accesskey="C" name="btnCancelar" value="Cancelar" onclick="location.href=\''.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.PaginaSEI::getInstance()->getAcaoRetorno().'&acao_origem='.$_GET['acao_origem']).'\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';

      if (isset($_POST['sbmSalvar'])){
        try{
          $numTipoDocumentoPadrao = PaginaSEI::getInstance()->recuperarCampo('selTipoDocumentoPadrao');
          $objPenRelTipoDocMapRecebidoRN->atribuirTipoDocumentoPadrao($numTipoDocumentoPadrao);
          header('Location: '.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.PaginaSEI::getInstance()->getAcaoRetorno().'&acao_origem='.$_GET['acao']));
          die;
        }catch(Exception $e){
          PaginaSEI::getInstance()->processarExcecao($e);
        }
      }
        break;

    case 'pen_map_tipo_doc_recebimento_padrao_consultar':
      $strTitulo = 'Consultar Tipo de Documento Padrão para Recebimento';
      $arrComandos[] = '<button type="button" accesskey="F" name="btnFechar" value="Fechar" onclick="location.href=\''.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.PaginaSEI::getInstance()->getAcaoRetorno().'&acao_origem='.$_GET['acao']).'#ID-'.$_GET['id_cidade'].'\';" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';
        break;

    default:
        throw new InfraException("Módulo do Tramita: Ação '".$_GET['acao']."' não reconhecida.");
  }

  $objTipoDocMapRN = new TipoDocMapRN();
  $strItensSelTiposDocumentos = InfraINT::montarSelectArray('null', '', $numTipoDocumentoPadrao, $objTipoDocMapRN->listarParesSerie());

}catch(Exception $e){
  PaginaSEI::getInstance()->processarExcecao($e);
}

PaginaSEI::getInstance()->montarDocType();
PaginaSEI::getInstance()->abrirHtml();
PaginaSEI::getInstance()->abrirHead();
PaginaSEI::getInstance()->montarMeta();
PaginaSEI::getInstance()->montarTitle(PaginaSEI::getInstance()->getStrNomeSistema().' - '.$strTitulo);
PaginaSEI::getInstance()->montarStyle();
PaginaSEI::getInstance()->abrirStyle();
?>
.infraFieldset{with:80%; height: auto; margin-bottom: 11px;}
.infraFieldset p{font-size: 1.2em;}
#lblTipoDocumentoPadrao {width:40%;}
#selTipoDocumentoPadrao {width:40%;}
<?
PaginaSEI::getInstance()->fecharStyle();
PaginaSEI::getInstance()->montarJavaScript();
PaginaSEI::getInstance()->abrirJavaScript();
?>
function inicializar(){

  if ('<?php echo htmlspecialchars($_GET['acao'])?>'=='pen_map_tipo_doc_recebimento_padrao_atribuir'){
    document.getElementById('selTipoDocumentoPadrao').focus();
  } else if ('<?php echo htmlspecialchars($_GET['acao'])?>'=='pen_map_tipo_doc_recebimento_padrao_consultar'){
    infraDesabilitarCamposAreaDados();
  }

  infraEfeitoTabelas();
}

function OnSubmitForm() {
  return validarCadastro();
}

function validarCadastro() {
  if (!infraSelectSelecionado('selTipoDocumentoPadrao')) {
    alert('Selecione um Tipo de Documento padrão para recebimento de processos.');
    document.getElementById('selTipoDocumentoPadrao').focus();
    return false;
  }

  return true;
}

<?
PaginaSEI::getInstance()->fecharJavaScript();
PaginaSEI::getInstance()->fecharHead();
PaginaSEI::getInstance()->abrirBody($strTitulo, 'onload="inicializar();"');
?>
<form id="frmEspeciePadraoAtribuir" method="post" onsubmit="return OnSubmitForm();" action="<?php echo SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.htmlspecialchars($_GET['acao']).'&acao_origem='.htmlspecialchars($_GET['acao_origem']).$strParametros)?>">
<?
PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandos);
PaginaSEI::getInstance()->abrirAreaDados('30em');
?>

<fieldset class="infraFieldset sizeFieldset">
  <legend class="infraLegend">&nbsp; Orientações Gerais &nbsp;</legend>
  <p class="infraLabel">
  A configuração de <strong>Tipo de Documento Padrão para Recebimento</strong> define qual será o comportamento do sistema ao receber processos que contenham Espécies Documentais
  não mapeadas previamente pelo Administrador. Na hipótese desta situação, o tipo de documento configurado abaixo será aplicado automaticamente, evitando que o trâmite
  seja cancelado pela falta de mapeamento.</p>
  <p>
  Somente Tipos de Documento com aplicabilidade 'Externa' ou 'Interna e Externa' podem ser selecionados para esta configuração.
  </p>
</fieldset>

<label id="lblTipoDocumentoPadrao" for="selTipoDocumentoPadrao" accesskey="P" class="infraLabelObrigatorio"><span class="infraTeclaAtalho">T</span>ipo de Documento SEI padrão para recebimento:</label>
<select id="selTipoDocumentoPadrao" name="selTipoDocumentoPadrao" class="infraSelect" tabindex="<?php echo PaginaSEI::getInstance()->getProxTabDados()?>">
<?php echo $strItensSelTiposDocumentos?>
</select>
<?
PaginaSEI::getInstance()->fecharAreaDados();
?>
</form>
<?
PaginaSEI::getInstance()->montarAreaDebug();
PaginaSEI::getInstance()->fecharBody();
PaginaSEI::getInstance()->fecharHtml();
?>
