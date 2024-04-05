<?php
try {
  require_once DIR_SEI_WEB.'/SEI.php';

  session_start();

  $objSessaoSEI = SessaoSEI::getInstance();
  $objPaginaSEI = PaginaSEI::getInstance();

  $objSessaoSEI->validarLink();
  $objSessaoSEI->validarPermissao($_GET['acao']);

  //////////////////////////////////////////////////////////////////////////////
  // InfraDebug::getInstance()->setBolLigado(false);
  // InfraDebug::getInstance()->setBolDebugInfra(true);
  // InfraDebug::getInstance()->limpar();
  //////////////////////////////////////////////////////////////////////////////

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

  $arrComandos = array();
  switch($_GET['acao']) {
    case 'pen_incluir_processo_em_bloco_tramite':

      $strTitulo = 'Incluir Processo no Bloco de Tr�mite';
      $arrComandos[] = '<button type="submit" accesskey="S" name="sbmCadastrarProcessoEmBloco" value="Salvar" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
      $arrComandos[] = '<button type="button" accesskey="C" name="btnCancelar" id="btnCancelar" value="Cancelar" onclick="location.href=\'' . $objSessaoSEI->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao'] . $strParametros) . '\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';

      $objProcedimentoDTO = new ProcedimentoDTO();
      $objProcedimentoDTO->retStrProtocoloProcedimentoFormatado();
      $objProcedimentoDTO->setDblIdProcedimento($_GET['id_procedimento']);
    
      $objProcedimentoRN = new ProcedimentoRN();
      $procedimento = $objProcedimentoRN->consultarRN0201($objProcedimentoDTO);
    
      $objTramiteEmBlocoProtocoloDTO = new TramitaEmBlocoProtocoloDTO();

      $objTramiteEmBlocoProtocoloDTO->setNumId(null);
      $objTramiteEmBlocoProtocoloDTO->setDblIdProtocolo($_GET['id_procedimento']);
      $objTramiteEmBlocoProtocoloDTO->setNumIdTramitaEmBloco($_POST['id_tramita_bloco']);
      $objTramiteEmBlocoProtocoloDTO->setStrIdxRelBlocoProtocolo($procedimento->getStrProtocoloProcedimentoFormatado());

      if (isset($_POST['sbmCadastrarProcessoEmBloco'])) {
        try{
          $objTramiteEmBlocoProtocoloRN = new TramitaEmBlocoProtocoloRN();
          $validar = $objTramiteEmBlocoProtocoloRN->validarBlocoDeTramite($objTramiteEmBlocoProtocoloDTO);

          if ($validar) {
            $objPaginaSEI->adicionarMensagem($validar, InfraPagina::$TIPO_MSG_AVISO);
            header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao']));
            exit(0);
          } 

          $objTramiteEmBlocoProtocoloDTO = $objTramiteEmBlocoProtocoloRN->cadastrar($objTramiteEmBlocoProtocoloDTO);
          $objPaginaSEI->adicionarMensagem('Processo "' . $procedimento->getStrProtocoloProcedimentoFormatado() . '" adicionado ao bloco', InfraPagina::$TIPO_MSG_AVISO);
          
        }catch(Exception $e){
          PaginaSEI::getInstance()->processarExcecao($e);
        }
        header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao']));
        exit(0);
      }
      break;

    default:
      throw new InfraException("A��o '".$_GET['acao']."' n�o reconhecida.");
  }

  //Monta o select dos blocos
  $arrMapIdBloco = array();

  $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
  $objTramiteEmBlocoDTO->retNumId();
  $objTramiteEmBlocoDTO->retNumIdUnidade();
  $objTramiteEmBlocoDTO->retStrDescricao();

  $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
  foreach ($objTramiteEmBlocoRN->listar($objTramiteEmBlocoDTO) as $dados) {
    $arrMapIdBloco[$dados->getNumId()] = "{$dados->getNumId()} - {$dados->getStrDescricao()}";
  }

} catch(Exception $e) {
  PaginaSEI::getInstance()->processarExcecao($e);
}
  // View
  ob_clean();

  $objPaginaSEI->montarDocType();
  $objPaginaSEI->abrirHtml();
  $objPaginaSEI->abrirHead();
  $objPaginaSEI->montarMeta();
  $objPaginaSEI->montarTitle($objPaginaSEI->getStrNomeSistema() . ' - ' . $strTitulo);
  $objPaginaSEI->montarStyle();
  $objPaginaSEI->abrirStyle();

?>
  #divIdentificacao {display:none;}
  #lblBlocos{position:absolute;left:0%;top:0%;width:60%;min-width:250px;}
  #selBlocos{position:absolute;left:0%;top:13%;width:60%;min-width:250px;}

<?
$objPaginaSEI->fecharStyle();
$objPaginaSEI->montarJavaScript();
$objPaginaSEI->abrirJavaScript();
?>

function inicializar(){

if ('<?=$_GET['acao']?>'=='pen_incluir_processo_em_bloco_tramite') {
  document.getElementById('divIdentificacao').style.display = 'none';
  document.getElementById('id_tramita_bloco').focus();
} else {
  document.getElementById('divIdentificacao').style.display = 'block';
  document.getElementById('btnCancelar').focus();
}
}

<?php
$objPaginaSEI->fecharJavaScript();
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody($strTitulo, 'onload="inicializar();"');
?>

<form id="frmProcessoEmBlocoCadastro" method="post" action="<?=SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao'].'&acao_origem='.$_GET['acao'].$strParametros)?>">

  <?php
  $objPaginaSEI->montarBarraComandosSuperior($arrComandos);
  $objPaginaSEI->montarAreaValidacao();
  $objPaginaSEI->abrirAreaDados('15em');
  ?>

  <label id="lblBlocos" for="lblIdBloco" class="infraLabelObrigatorio">Blocos :</label>
  <select id="selBlocos" name="id_tramita_bloco" class="infraSelect" >
    <?php print InfraINT::montarSelectArray('', 'Selecione', $objTramiteEmBlocoProtocoloDTO->getNumId(), array_filter($arrMapIdBloco)); ?>
  </select>

  <input type="hidden" id="hdnIdBloco" name="hdnIdBloco" value="<?=$objTramiteEmBlocoProtocoloDTO->getNumId();?>" />
  <input type="hidden" id="hdnIdProtocolo" name="hdnIdProtocolo" value="<?=$objTramiteEmBlocoProtocoloDTO->getNumIdProtocolo();?>" />

  <?php
  $objPaginaSEI->fecharAreaDados();
  $objPaginaSEI->montarBarraComandosInferior($arrComandos);
  ?>

</form>

<?php
$objPaginaSEI->fecharBody();
$objPaginaSEI->fecharHtml();
?>