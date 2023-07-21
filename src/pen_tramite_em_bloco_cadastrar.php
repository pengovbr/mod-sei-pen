<?
/**
*
*
*/
try {
  require_once DIR_SEI_WEB.'/SEI.php';

  session_start();

  //////////////////////////////////////////////////////////////////////////////
  InfraDebug::getInstance()->setBolLigado(false);
  InfraDebug::getInstance()->setBolDebugInfra(true);
  InfraDebug::getInstance()->limpar();
  //////////////////////////////////////////////////////////////////////////////

  SessaoSEI::getInstance()->validarLink();

  // SessaoSEI::getInstance()->validarPermissao($_GET['acao']);

  $strParametros = '';
  if(isset($_GET['arvore'])) {
    PaginaSEI::getInstance()->setBolArvore($_GET['arvore']);
    $strParametros .= '&arvore='.$_GET['arvore'];
  }

  if (isset($_GET['id_procedimento'])) {
    $strParametros .= "&id_procedimento=".$_GET['id_procedimento'];
  }

  if (isset($_GET['id_documento'])) {
    $strParametros .= "&id_documento=".$_GET['id_documento'];
  }

  $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();

  $strDesabilitar = '';

  $arrComandos = array();
  $bolCadastroOk = false;

  switch($_GET['acao']) {
    case 'pen_tramite_em_bloco_cadastrar':

      $strTitulo = 'Novo Trâmite em Bloco';
      $arrComandos[] = '<button type="submit" accesskey="S" name="sbmCadastrarTramiteEmBloco" value="Salvar" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
<<<<<<< HEAD
      $arrComandos[] = '<button type="button" accesskey="C" name="btnCancelar" id="btnCancelar" value="Cancelar" onclick="location.href=\'' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=md_pen_tramita_em_bloco&acao_origem=' . $_GET['acao'] . $strParametros) . '\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';
=======
      $arrComandos[] = '<button type="button" accesskey="C" name="btnCancelar" id="btnCancelar" value="Cancelar" onclick="location.href=\'' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao'] . $strParametros) . '\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';
>>>>>>> 4be085dd884b03050a0840fc35dfbcff27659250

      $objTramiteEmBlocoDTO->setNumId(null);
      $objTramiteEmBlocoDTO->setStrStaTipo(TramiteEmBlocoRN::$TB_INTERNO);
      $objTramiteEmBlocoDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
      $objTramiteEmBlocoDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
      $objTramiteEmBlocoDTO->setStrDescricao($_POST['txtDescricao']);
      $objTramiteEmBlocoDTO->setStrIdxBloco(null);
      $objTramiteEmBlocoDTO->setStrStaEstado(TramiteEmBlocoRN::$TE_ABERTO);
      if (isset($_POST['sbmCadastrarTramiteEmBloco'])) {
        try{
          //
          $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
          $objTramiteEmBlocoDTO = $objTramiteEmBlocoRN->cadastrar($objTramiteEmBlocoDTO);
          PaginaSEI::getInstance()->setStrMensagem('Trâmite em Bloco "' . $objTramiteEmBlocoDTO->getNumId() . '" cadastrado com sucesso.');
          header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=md_pen_tramita_em_bloco&acao_origem=' . $_GET['acao'] . '&id_bloco=' . $objTramiteEmBlocoDTO->getNumId() . $strParametros . PaginaSEI::getInstance()->montarAncora($objTramiteEmBlocoDTO->getNumId())));
          die;
        }catch(Exception $e){
          PaginaSEI::getInstance()->processarExcecao($e);
        }
      }
      break;

    case 'pen_tramite_em_bloco_alterar':

      $strTitulo = 'Alterar Trâmite em Bloco';
      $arrComandos[] = '<button type="submit" accesskey="S" name="sbmAlterarBloco" value="Salvar" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
      $strDesabilitar = 'disabled="disabled"';

      if (isset($_GET['id_bloco'])){
        $objTramiteEmBlocoDTO->setNumId($_GET['id_bloco']);
        $objTramiteEmBlocoDTO->retTodos();
        $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
        $objTramiteEmBlocoDTO = $objTramiteEmBlocoRN->consultar($objTramiteEmBlocoDTO);
        if ($objTramiteEmBlocoDTO==null){
          throw new InfraException("Registro não encontrado.");
        }
      } else {
        $objTramiteEmBlocoDTO->setNumId($_POST['hdnIdBloco']);
        $objTramiteEmBlocoDTO->setStrDescricao($_POST['txtDescricao']);
      }

      $arrComandos[] = '<button type="button" accesskey="C" name="btnCancelar" id="btnCancelar" value="Cancelar" onclick="location.href=\''.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.PaginaSEI::getInstance()->getAcaoRetorno().'&acao_origem='.$_GET['acao'].PaginaSEI::getInstance()->montarAncora($objTramiteEmBlocoDTO->getNumId())).'\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';

      if (isset($_POST['sbmAlterarBloco'])) {
        try {
          $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
          $objTramiteEmBlocoRN->alterar($objTramiteEmBlocoDTO);
          PaginaSEI::getInstance()->setStrMensagem('Trâmite em Bloco "'.$objTramiteEmBlocoDTO->getNumId().'" alterado com sucesso.');
          header('Location: '.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.PaginaSEI::getInstance()->getAcaoRetorno().'&acao_origem='.$_GET['acao'].PaginaSEI::getInstance()->montarAncora($objTramiteEmBlocoDTO->getNumId())));
          die;
        } catch (Exception $e) {
          PaginaSEI::getInstance()->processarExcecao($e);
        }
      }
      break;

    default:
      throw new InfraException("Ação '".$_GET['acao']."' não reconhecida.");
  }

} catch(Exception $e) {
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
#divIdentificacao {display:none;}
#lblIdBloco {position:absolute;left:0%;top:0%;width:25%;}
#txtIdBloco {position:absolute;left:0%;top:40%;width:25%;}

#lblDescricao {position:absolute;left:0%;top:0%;width:95%;}
#txtDescricao {position:absolute;left:0%;top:18%;width:95%;}

<?
PaginaSEI::getInstance()->fecharStyle();
PaginaSEI::getInstance()->montarJavaScript();
PaginaSEI::getInstance()->abrirJavaScript();
?>

function inicializar(){

  <?if ($bolCadastroOk){?>
    <? if ($_GET['arvore']=='1') { ?>
      parent.document.getElementById('ifrVisualizacao').contentWindow.atualizarBlocos(<?=$objTramiteEmBlocoDTO->getNumId()?>);
    <? } else { ?>
      window.parent.atualizarBlocos(<?=$objTramiteEmBlocoDTO->getNumId()?>);
    <? } ?>
      self.setTimeout('infraFecharJanelaModal()',500);
      return;
  <?}?>

  if ('<?=$_GET['acao']?>'=='pen_tramite_em_bloco_consultar') {
    document.getElementById('divIdentificacao').style.display = 'block';
    infraDesabilitarCamposAreaDados();
    document.getElementById('btnFechar').focus();
    return;
  } else if ('<?=$_GET['acao']?>'=='pen_tramite_em_bloco_cadastrar') {
    document.getElementById('divIdentificacao').style.display = 'none';
    document.getElementById('txtDescricao').focus();
  } else {
    document.getElementById('divIdentificacao').style.display = 'block';
    document.getElementById('btnCancelar').focus();
  }

  infraEfeitoTabelas();
}

function validarCadastroRI1284() {
  return true;
}

function OnSubmitForm() {
  return validarCadastroRI1284();
}


<?
PaginaSEI::getInstance()->fecharJavaScript();
PaginaSEI::getInstance()->fecharHead();
PaginaSEI::getInstance()->abrirBody($strTitulo,'onload="inicializar();"');
?>
<form id="frmBlocoCadastro" method="post" onsubmit="return OnSubmitForm();" action="<?=SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao'].'&acao_origem='.$_GET['acao'].$strParametros)?>">
<?
PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandos);
?>
  <div id="divIdentificacao" class="infraAreaDados" style="height:5em;">
    <label id="lblIdBloco" for="txtIdBloco" accesskey="" class="infraLabelObrigatorio">Número:</label>
    <input type="text" id="txtIdBloco" name="txtIdBloco" class="infraText" disabled="true" value="<?=$objTramiteEmBlocoDTO->getNumId();?>" tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" />
  </div>
  <div id="divDescricao" class="infraAreaDados" style="height:10em;">
    <label id="lblDescricao" for="txtDescricao" accesskey="" class="infraLabelOpcional">Descrição:</label>
    <textarea id="txtDescricao" name="txtDescricao" rows="<?=PaginaSEI::getInstance()->isBolNavegadorFirefox()?'3':'4'?>" class="infraTextarea" onkeypress="return infraLimitarTexto(this,event,250);" tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>"><?=PaginaSEI::tratarHTML($objTramiteEmBlocoDTO->getStrDescricao())?></textarea>
  </div>

  <input type="hidden" id="hdnIdBloco" name="hdnIdBloco" value="<?=$objTramiteEmBlocoDTO->getNumId();?>" />
<?

  PaginaSEI::getInstance()->montarAreaDebug();
?>
</form>
<?
PaginaSEI::getInstance()->fecharBody();
PaginaSEI::getInstance()->fecharHtml();
?>
