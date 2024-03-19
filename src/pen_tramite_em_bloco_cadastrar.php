<?php

try {
  require_once DIR_SEI_WEB . '/SEI.php';

  session_start();

  $objSessaoSEI = SessaoSEI::getInstance();
  $objPaginaSEI = PaginaSEI::getInstance();
  $objDebug = InfraDebug::getInstance();
  $objInfraException = new InfraException();

  $strParametros = '';
  if (isset($_GET['arvore'])) {
    PaginaSEI::getInstance()->setBolArvore($_GET['arvore']);
    $strParametros .= '&arvore=' . $_GET['arvore'];
  }

  if (isset($_GET['id_procedimento'])) {
    $strParametros .= "&id_procedimento=" . $_GET['id_procedimento'];
  }

  if (isset($_GET['id_documento'])) {
    $strParametros .= "&id_documento=" . $_GET['id_documento'];
  }

  $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();

  $strDesabilitar = '';

  $arrComandos = array();
  $bolCadastroOk = false;

  switch ($_GET['acao']) {
    case 'pen_tramite_em_bloco_cadastrar':
      $strTitulo = 'Novo Tr�mite em Bloco';
      $arrComandos[] = '<button type="submit" accesskey="S" name="sbmCadastrarTramiteEmBloco" value="Salvar" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
      $arrComandos[] = '<button type="button" accesskey="C" name="btnCancelar" id="btnCancelar" value="Cancelar" onclick="location.href=\'' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=md_pen_tramita_em_bloco&acao_origem=' . $_GET['acao'] . $strParametros) . '\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';

      $objTramiteEmBlocoDTO->setNumId(null);
      $objTramiteEmBlocoDTO->setStrStaTipo(TramiteEmBlocoRN::$TB_INTERNO);
      $objTramiteEmBlocoDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
      $objTramiteEmBlocoDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
      $objTramiteEmBlocoDTO->setStrDescricao($_POST['txtDescricao']);
      $objTramiteEmBlocoDTO->setStrIdxBloco(null);
      $objTramiteEmBlocoDTO->setStrStaEstado(TramiteEmBlocoRN::$TE_ABERTO);
      if (isset($_POST['sbmCadastrarTramiteEmBloco'])) {
        try {
          $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
          $objTramiteEmBlocoDTO = $objTramiteEmBlocoRN->cadastrar($objTramiteEmBlocoDTO);
          $objPaginaSEI->adicionarMensagem('Bloco de Tr�mite externo criado com sucesso!', 5);
        } catch (Exception $e) {
          PaginaSEI::getInstance()->processarExcecao($e);
        }
        header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=md_pen_tramita_em_bloco&acao_origem=' . $_GET['acao'] . '&id_bloco=' . $objTramiteEmBlocoDTO->getNumId() . $strParametros . PaginaSEI::getInstance()->montarAncora($objTramiteEmBlocoDTO->getNumId())));
        exit(0);
      }
        break;

    case 'pen_tramite_em_bloco_alterar':
      $strTitulo = 'Alterar Tr�mite em Bloco';
      $arrComandos[] = '<button type="submit" accesskey="S" name="sbmAlterarBloco" value="Salvar" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
      $strDesabilitar = 'disabled="disabled"';

      if (isset($_GET['id_bloco'])) {
        $objTramiteEmBlocoDTO->setNumId($_GET['id_bloco']);
        $objTramiteEmBlocoDTO->retTodos();
        $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
        $objTramiteEmBlocoDTO = $objTramiteEmBlocoRN->consultar($objTramiteEmBlocoDTO);
        if ($objTramiteEmBlocoDTO == null) {
          throw new InfraException("Registro n�o encontrado.");
        }
      } else {
        $objTramiteEmBlocoDTO->setNumId($_POST['hdnIdBloco']);
        $objTramiteEmBlocoDTO->setStrDescricao($_POST['txtDescricao']);
      }

      $arrComandos[] = '<button type="button" accesskey="C" name="btnCancelar" id="btnCancelar" value="Cancelar" onclick="location.href=\'' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao'] . PaginaSEI::getInstance()->montarAncora($objTramiteEmBlocoDTO->getNumId())) . '\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';

      if (isset($_POST['sbmAlterarBloco'])) {
        try {
          $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
          $objTramiteEmBlocoRN->alterar($objTramiteEmBlocoDTO);
          PaginaSEI::getInstance()->setStrMensagem('Tr�mite em Bloco "' . $objTramiteEmBlocoDTO->getNumId() . '" alterado com sucesso.');
          $objPaginaSEI->adicionarMensagem('Bloco de tr�mite externo alterado com sucesso!', 5);
        } catch (Exception $e) {
          PaginaSEI::getInstance()->processarExcecao($e);
        }
        header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao'] . PaginaSEI::getInstance()->montarAncora($objTramiteEmBlocoDTO->getNumId())));
        exit(0);
      }
        break;
    default:
        throw new InfraException("A��o '" . $_GET['acao'] . "' n�o reconhecida.");
  }
} catch (Exception $e) {
  PaginaSEI::getInstance()->processarExcecao($e);
}

PaginaSEI::getInstance()->montarDocType();
PaginaSEI::getInstance()->abrirHtml();
PaginaSEI::getInstance()->abrirHead();
PaginaSEI::getInstance()->montarMeta();
PaginaSEI::getInstance()->montarTitle(PaginaSEI::getInstance()->getStrNomeSistema() . ' - ' . $strTitulo);
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
$acao = $_GET['acao'];
?>

function inicializar(){

if ('<?= $acao ?>'=='pen_tramite_em_bloco_consultar') {
document.getElementById('divIdentificacao').style.display = 'block';
infraDesabilitarCamposAreaDados();
document.getElementById('btnFechar').focus();
return;
} else if ('<?= $acao ?>'=='pen_tramite_em_bloco_cadastrar') {
document.getElementById('divIdentificacao').style.display = 'none';
document.getElementById('txtDescricao').focus();
} else {
document.getElementById('divIdentificacao').style.display = 'block';
document.getElementById('btnCancelar').focus();
}

infraEfeitoTabelas();
}

<?
PaginaSEI::getInstance()->fecharJavaScript();
PaginaSEI::getInstance()->fecharHead();
PaginaSEI::getInstance()->abrirBody($strTitulo, 'onload="inicializar();"');
?>
<form id="frmBlocoCadastro" method="post" action="<?= SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . $acao . '&acao_origem=' . $acao . $strParametros) ?>">
  <?
  PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandos);
  ?>
  <p style="font-size: 1.0rem;">Aten��o!! Cada bloco permite incluir e tramitar no m�ximo 100 processos.
  <p>
  <div id="divIdentificacao" class="infraAreaDados" style="height:5em;">
    <label id="lblIdBloco" for="txtIdBloco" accesskey="" class="infraLabelObrigatorio">N�mero:</label>
    <input type="text" id="txtIdBloco" name="txtIdBloco" class="infraText" disabled="true" value="<?= $objTramiteEmBlocoDTO->getNumId(); ?>" tabindex="<?= PaginaSEI::getInstance()->getProxTabDados() ?>" />
  </div>
  <div id="divDescricao" class="infraAreaDados" style="height:10em;">
    <label id="lblDescricao" for="txtDescricao" accesskey="" class="infraLabelOpcional">Descri��o:</label>
    <textarea id="txtDescricao" name="txtDescricao" rows="<?= PaginaSEI::getInstance()->isBolNavegadorFirefox() ? '3' : '4' ?>" class="infraTextarea" onkeypress="return infraLimitarTexto(this,event,250);" tabindex="<?= PaginaSEI::getInstance()->getProxTabDados() ?>"><?= PaginaSEI::tratarHTML($objTramiteEmBlocoDTO->getStrDescricao()) ?></textarea>
  </div>

  <input type="hidden" id="hdnIdBloco" name="hdnIdBloco" value="<?= $objTramiteEmBlocoDTO->getNumId(); ?>" />
  <?

  PaginaSEI::getInstance()->montarAreaDebug();
  ?>
</form>
<?
PaginaSEI::getInstance()->fecharBody();
PaginaSEI::getInstance()->fecharHtml();
?>