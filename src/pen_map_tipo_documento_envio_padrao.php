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
  PaginaSEI::getInstance()->salvarCamposPost(array('selEspeciePadraoEnvio'));
  
  $strParametros = '';
  if(isset($_GET['arvore'])){
    PaginaSEI::getInstance()->setBolArvore($_GET['arvore']);
    $strParametros .= '&arvore='.$_GET['arvore'];
  }
  
  if (isset($_GET['id_procedimento'])){
    $strParametros .= '&id_procedimento='.$_GET['id_procedimento'];
  }
  
  $arrComandos = array();

  $objPenRelTipoDocMapEnviadoRN = new PenRelTipoDocMapEnviadoRN();
  $numEspeciePadraoEnvio = $objPenRelTipoDocMapEnviadoRN->consultarEspeciePadrao();

  switch($_GET['acao']){
    
    case 'pen_map_tipo_documento_envio_padrao_atribuir':
      $strTitulo = 'Atribuir Esp�cie Documental Padr�o para Envio';
      $arrComandos[] = '<button type="submit" accesskey="S" name="sbmSalvar" value="Salvar" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
      $arrComandos[] = '<button type="button" accesskey="C" name="btnCancelar" value="Cancelar" onclick="location.href=\''.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.PaginaSEI::getInstance()->getAcaoRetorno().'&acao_origem='.$_GET['acao_origem']).'\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';

      if (isset($_POST['sbmSalvar'])){
        try{
          $numEspeciePadraoEnvio = PaginaSEI::getInstance()->recuperarCampo('selEspeciePadraoEnvio');
          $objPenRelTipoDocMapEnviadoRN->atribuirEspeciePadrao($numEspeciePadraoEnvio);
          header('Location: '.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.PaginaSEI::getInstance()->getAcaoRetorno().'&acao_origem='.$_GET['acao']));
          die;
        }catch(Exception $e){
          PaginaSEI::getInstance()->processarExcecao($e);
        }
      }            
    break;
    
    case 'pen_map_tipo_documento_envio_padrao_consultar':       
      $strTitulo = 'Consultar Esp�cie Documental Padr�o para Envio';
      $arrComandos[] = '<button type="button" accesskey="F" name="btnFechar" value="Fechar" onclick="location.href=\''.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.PaginaSEI::getInstance()->getAcaoRetorno().'&acao_origem='.$_GET['acao']).'#ID-'.$_GET['id_cidade'].'\';" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';
    break;
    
    default:
    throw new InfraException("A��o '".$_GET['acao']."' n�o reconhecida.");
  }
  
  $objTipoDocMapRN = new TipoDocMapRN();
  $strItensSelEspeciesDocumentais = InfraINT::montarSelectArray('null', '', $numEspeciePadraoEnvio, $objTipoDocMapRN->listarParesEspecie());
  
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
#lblEspeciePadraoEnvio {width:40%;}
#selEspeciePadraoEnvio {width:40%;}


<?
PaginaSEI::getInstance()->fecharStyle();
PaginaSEI::getInstance()->montarJavaScript();
PaginaSEI::getInstance()->abrirJavaScript();
?>
function inicializar(){
  
  if ('<?=$_GET['acao']?>'=='pen_map_tipo_documento_envio_padrao_atribuir'){
    document.getElementById('selEspeciePadraoEnvio').focus();
  } else if ('<?=$_GET['acao']?>'=='pen_map_tipo_documento_envio_padrao_consultar'){
    infraDesabilitarCamposAreaDados();
  }
  
  infraEfeitoTabelas();
}

function OnSubmitForm() {
  return validarCadastro();
}

function validarCadastro() {
  if (!infraSelectSelecionado('selEspeciePadraoEnvio')) {
    alert('Selecione uma Esp�cie Documental padr�o para envio de processos.');
    document.getElementById('selEspeciePadraoEnvio').focus();
    return false;
  }
  
  return true;
}

<?
PaginaSEI::getInstance()->fecharJavaScript();
PaginaSEI::getInstance()->fecharHead();
PaginaSEI::getInstance()->abrirBody($strTitulo,'onload="inicializar();"');
?>
<form id="frmEspeciePadraoAtribuir" method="post" onsubmit="return OnSubmitForm();" action="<?=SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao'].'&acao_origem='.$_GET['acao_origem'].$strParametros)?>">
<?
PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandos);
PaginaSEI::getInstance()->abrirAreaDados('30em');
?>

<fieldset class="infraFieldset sizeFieldset">
  <legend class="infraLegend">&nbsp; Orienta��es Gerais &nbsp;</legend>
  <p class="infraLabel">    
  A configura��o de <strong>Esp�cie Documental Padr�o para Envio</strong> define qual ser� o comportamento do sistema ao enviar processos que contenham Tipos de Documentos 
  n�o mapeados previamente pelo Administrador. Na hip�tese desta situa��o, a esp�cie documental configurada abaixo ser� aplicada automaticamente, evitando que o tr�mite 
  seja cancelado pela falta desta configura��o.
  </p>  
</fieldset>

<label id="lblEspeciePadraoEnvio" for="selEspeciePadraoEnvio" accesskey="P" class="infraLabelObrigatorio"><span class="infraTeclaAtalho">E</span>sp�cie Documental PEN padr�o para envio:</label>
<select id="selEspeciePadraoEnvio" name="selEspeciePadraoEnvio" class="infraSelect" tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>">
<?=$strItensSelEspeciesDocumentais?>
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
