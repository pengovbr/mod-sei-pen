<?
/**
 *
 *
 */
try {
  require_once DIR_SEI_WEB.'/SEI.php';

  session_start();

  $objSessaoSEI = SessaoSEI::getInstance();
  $objPaginaSEI = PaginaSEI::getInstance();

  $objSessaoSEI->validarLink();
  //$objSessaoSEI->validarPermissao($_GET['acao']);

  //////////////////////////////////////////////////////////////////////////////
  InfraDebug::getInstance()->setBolLigado(false);
  InfraDebug::getInstance()->setBolDebugInfra(true);
  InfraDebug::getInstance()->limpar();
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

  SessaoSEI::getInstance()->setArrParametrosRepasseLink(array('arvore', 'pagina_simples','id_protocolo'));

  if(isset($_GET['arvore'])){
    PaginaSEI::getInstance()->setBolArvore($_GET['arvore']);
  }

  $numIdProtocolo = '';
  if(isset($_GET['id_procedimento'])){
    $numIdProtocolo = $_GET['id_procedimento'];
  }

//Carregar dados do cabeçalho
  $objProcedimentoDTO = new ProcedimentoDTO();
  $objProcedimentoDTO->retStrProtocoloProcedimentoFormatado();
  $objProcedimentoDTO->setDblIdProcedimento($_GET['id_procedimento']);

  $objProcedimentoRN = new ProcedimentoRN();
  $arr = $objProcedimentoRN->listarCompleto($objProcedimentoDTO);

  foreach($arr as $item){
    $protocolo =  $item->getStrProtocoloProcedimentoFormatado();
  }

  $objTramiteEmBlocoProtocoloDTO = new TramitaEmBlocoProtocoloDTO();

  $arrComandos = array();
  $bolCadastroOk = false;

  switch($_GET['acao']) {
    case 'pen_incluir_processo_em_bloco_tramite':

      $strTitulo = 'Incluir Processo no Bloco de Trâmite';
      $arrComandos[] = '<button type="submit" accesskey="S" name="sbmCadastrarProcessoEmBloco" value="Salvar" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
      $arrComandos[] = '<button type="button" accesskey="C" name="btnCancelar" id="btnCancelar" value="Cancelar" onclick="location.href=\'' . $objSessaoSEI->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao'] . $strParametros) . '\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';

      $objTramiteEmBlocoProtocoloDTO->setNumId(null);
      $objTramiteEmBlocoProtocoloDTO->setDblIdProtocolo($_GET['id_procedimento']);
      $objTramiteEmBlocoProtocoloDTO->setNumIdTramitaEmBloco($_POST['id_tramita_bloco']);
      $objTramiteEmBlocoProtocoloDTO->setStrAnotacao(null);
      $objTramiteEmBlocoProtocoloDTO->setNumSequencia(null);
      $objTramiteEmBlocoProtocoloDTO->setStrIdxRelBlocoProtocolo($protocolo);

      if (isset($_POST['sbmCadastrarProcessoEmBloco'])) {
        try{

          $objTramiteEmBlocoProtocoloRN = new TramitaEmBlocoProtocoloRN();

          $objTramiteEmBlocoProtocoloDTO = $objTramiteEmBlocoProtocoloRN->cadastrar($objTramiteEmBlocoProtocoloDTO);
          PaginaSEI::getInstance()->setStrMensagem('Incluir Processo em Bloco "' . $objTramiteEmBlocoProtocoloDTO->getNumId() . '" cadastrado com sucesso.');
          header('Location: ' . $objSessaoSEI->assinarLink('controlador.php?acao=pen_incluir_processo_em_bloco_tramite&acao_origem=' . $_GET['acao'] . '&id_bloco=' . $objTramiteEmBlocoProtocoloDTO->getNumId() . $strParametros . PaginaSEI::getInstance()->montarAncora($objTramiteEmBlocoProtocoloDTO->getNumId())));

        }catch(Exception $e){
          PaginaSEI::getInstance()->processarExcecao($e);
        }
      }
      break;

    default:
      throw new InfraException("Ação '".$_GET['acao']."' não reconhecida.");
  }

  //Monta o select dos blocos
  $arrMapIdBloco = array();
  $objTramiteEmBlocoRN = new TramiteEmBlocoRN();

  $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
  $arrNumIdBlocosUsados = $objTramiteEmBlocoRN->getNumMaxTamanhoDescricao($objTramiteEmBlocoDTO);

  $objTramiteEmBlocoDTO->retNumId();
  $objTramiteEmBlocoDTO->retNumIdUnidade();
  $objTramiteEmBlocoDTO->retStrDescricao();

  foreach ($objTramiteEmBlocoRN->listar($objTramiteEmBlocoDTO) as $dados) {
    $arrMapIdBloco[$dados->getNumId()] = $dados->getStrDescricao();
  }

} catch(Exception $e) {
  PaginaSEI::getInstance()->processarExcecao($e);
}

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

<?if ($bolCadastroOk){?>
  <? if ($_GET['arvore']=='1') { ?>
    parent.document.getElementById('ifrVisualizacao').contentWindow.incluirProtocoloNoBloco(<?=$objTramiteEmBlocoProtocoloDTO->getNumId()?>);
  <? } else { ?>
    window.parent.incluirProtocoloNoBloco(<?=$objTramiteEmBlocoProtocoloDTO->getNumId()?>);
  <? } ?>
  self.setTimeout('infraFecharJanelaModal()',500);
  return;
<?}?>

if ('<?=$_GET['acao']?>'=='pen_incluir_processo_em_bloco_tramite') {
  document.getElementById('divIdentificacao').style.display = 'none';
  document.getElementById('id_tramita_bloco').focus();
} else {
  document.getElementById('divIdentificacao').style.display = 'block';
  document.getElementById('btnCancelar').focus();
}

infraEfeitoTabelas();
}

function validarCadastroProcessoEmBloco() {
  return true;
}

function OnSubmitForm() {
  return validarCadastroProcessoEmBloco();
}

<?
$objPaginaSEI->fecharJavaScript();
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody($strTitulo, 'onload="inicializar();"');
?>

<form id="frmProcessoEmBlocoCadastro" method="post" onsubmit="return OnSubmitForm();" action="<?=SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao'].'&acao_origem='.$_GET['acao'].$strParametros)?>">

  <?
  $objPaginaSEI->montarBarraComandosSuperior($arrComandos);
  $objPaginaSEI->montarAreaValidacao();
  $objPaginaSEI->abrirAreaDados('15em');
  ?>

  <label id="lblBlocos" for="lblIdBloco" class="infraLabelObrigatorio">Blocos :</label>
  <select id="selBlocos" name="id_tramita_bloco" class="infraSelect" >
    <?php print InfraINT::montarSelectArray('', 'Selecione', $objTramiteEmBlocoProtocoloDTO->getNumId(), $arrMapIdBloco); ?>
  </select>

  <input type="hidden" id="hdnIdBloco" name="hdnIdBloco" value="<?=$objTramiteEmBlocoProtocoloDTO->getNumId();?>" />
  <input type="hidden" id="hdnIdProtocolo" name="hdnIdProtocolo" value="<?=$objTramiteEmBlocoProtocoloDTO->getNumIdProtocolo();?>" />

  <?
  PaginaSEI::getInstance()->montarAreaDebug();
  PaginaSEI::getInstance()->fecharAreaDados();
  ?>

  <?
  $objPaginaSEI->fecharAreaDados();
  $objPaginaSEI->montarAreaDebug();
  $objPaginaSEI->montarBarraComandosInferior($arrComandos);
  ?>

</form>

<?
$objPaginaSEI->fecharBody();
$objPaginaSEI->fecharHtml();
?>
