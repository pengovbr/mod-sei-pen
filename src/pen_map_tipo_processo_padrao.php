<?
try {

  require_once DIR_SEI_WEB . '/SEI.php';

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

  $arrComandos = array();
  $objPenParametroDTO = new PenParametroDTO();
  $objPenParametroRN = new PenParametroRN();

  switch ($_GET['acao']) {
    case 'pen_map_tipo_processo_padrao_salvar':
      try {
        if (!empty($_POST['PEN_TIPO_PROCESSO_EXTERNO'])) {
          $objPenParametroDTO = new PenParametroDTO();
          $objPenParametroDTO->setStrNome('PEN_TIPO_PROCESSO_EXTERNO');
          $objPenParametroDTO->retStrNome();

          if($objPenParametroRN->contar($objPenParametroDTO) > 0) {
              $objPenParametroDTO->setStrValor($_POST['PEN_TIPO_PROCESSO_EXTERNO']);
              $objPenParametroRN->alterar($objPenParametroDTO);
          }
        }

        PaginaSEI::getInstance()->adicionarMensagem('Atribuição de Tipo de Processo Padrão realizada com sucesso.', 5);
      } catch (Exception $e) {
        PaginaSEI::getInstance()->processarExcecao($e);
      }
      header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_tipo_processo_padrao&acao_origem=' . $_GET['acao']));
      die;
      break;
    case 'pen_map_tipo_processo_padrao':
      $strTitulo = 'Atribuir Tipo de Processo Padrão';
      $arrComandos[] = '<button type="submit" accesskey="S" name="sbmSalvar" value="Salvar" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
      $arrComandos[] = '<button type="button" accesskey="C" name="btnCancelar" value="Cancelar" onclick="location.href=\'' 
        . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_orgaos_externos_listar&acao_origem=' . $_GET['acao_origem']) 
        . '\';" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';

      $objPenParametroDTO->retTodos();
      $objPenParametroDTO->setStrNome('PEN_TIPO_PROCESSO_EXTERNO');

      $parametro = $objPenParametroRN->consultar($objPenParametroDTO);

      $objRelTipoProcedimentoDTO = new TipoProcedimentoDTO();
      $objRelTipoProcedimentoDTO->retNumIdTipoProcedimento();
      $objRelTipoProcedimentoDTO->retStrNome();
      $objRelTipoProcedimentoDTO->setOrdStrNome(InfraDTO::$TIPO_ORDENACAO_ASC);

      $objRelTipoProcedimentoRN = new TipoProcedimentoRN();
      $arrTipoProcedimento = InfraArray::converterArrInfraDTO($objRelTipoProcedimentoRN->listarRN0244($objRelTipoProcedimentoDTO), "IdTipoProcedimento");

      $objRelTipoProcedimentoDTO->setNumIdTipoProcedimento($arrTipoProcedimento, InfraDTO::$OPER_IN);
      $arrObjTipoProcedimentoDTO = $objRelTipoProcedimentoRN->listarRN0244($objRelTipoProcedimentoDTO);

      $objPenRelTipoDocMapRecebidoRN = new PenRelTipoDocMapRecebidoRN();
      $numTipoDocumentoPadrao = $objPenRelTipoDocMapRecebidoRN->consultarTipoDocumentoPadrao();

      break;

    default:
      throw new InfraException("Módulo do Tramita: Ação '" . $_GET['acao'] . "' não reconhecida.");
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
.infraFieldset{with:80%; height: auto; margin-bottom: 11px;}
.infraFieldset p{font-size: 1.2em;}
#lblTipoDocumentoPadrao {width:40%;}
#selTipoDocumentoPadrao {width:40%;}
.infraFieldset {padding: 20px 20px 0px 20px;}
<?
PaginaSEI::getInstance()->fecharStyle();
PaginaSEI::getInstance()->montarJavaScript();
?>
<script>
  function inicializar() {
    infraEfeitoTabelas();
  }

  function OnSubmitForm() {
    return validarCadastro();
  }

  function validarCadastro() {
    if (!infraSelectSelecionado('PEN_TIPO_PROCESSO_EXTERNO')) {
      alert('Selecione um Tipo de Processao padrão para salvar.');
      document.getElementById('PEN_TIPO_PROCESSO_EXTERNO').focus();
      return false;
    }

    return true;
  }
</script>
<?
PaginaSEI::getInstance()->fecharHead();
PaginaSEI::getInstance()->abrirBody($strTitulo, 'onload="inicializar();"');
$acao_origem = $_GET['acao_origem'];
?>
<form id="frmEspeciePadraoAtribuir" method="post" onsubmit="return OnSubmitForm();" 
  action="<?php echo SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_tipo_processo_padrao_salvar&acao_origem=' . $acao_origem) ?>"
>
  <?
  PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandos);
  PaginaSEI::getInstance()->abrirAreaDados('30em');
  ?>

    <fieldset class="infraFieldset sizeFieldset">
      <legend class="infraLegend">&nbsp; Orientações Gerais &nbsp;</legend>
      <p class="infraLabel">
        A configuração de <strong>Tipo de Processo Padrão</strong> define qual será o comportamento do sistema ao receber processos que contenham Tipos de Processo
        não mapeadas previamente pelo Administrador. Na hipótese desta situação, o Tipo de Processo configurado abaixo será aplicado automaticamente, evitando que o trâmite
        seja cancelado pela falta de configuração.
      </p>
    </fieldset>

    <?php if ($parametro != null) { ?>
      <label id="lblTipoDocumentoPadrao" for="selTipoDocumentoPadrao" accesskey="P" class="infraLabelObrigatorio"><span class="infraTeclaAtalho">Tipo de Processo Padrão:</span></label>
      <select id="PEN_TIPO_PROCESSO_EXTERNO" name="PEN_TIPO_PROCESSO_EXTERNO" class="infraSelect" tabindex="<?php echo PaginaSEI::getInstance()->getProxTabDados() ?>">
        <?php $strItensSelTiposDocumentos = InfraINT::montarSelectArrInfraDTO('null', '&nbsp;', $parametro->getStrValor(), $arrObjTipoProcedimentoDTO, 'IdTipoProcedimento', 'Nome') ?>
        <?php echo $strItensSelTiposDocumentos ?>
      </select>
    <?php } ?>

    <?
    PaginaSEI::getInstance()->fecharAreaDados();
    ?>
</form>
<?
PaginaSEI::getInstance()->montarAreaDebug();
PaginaSEI::getInstance()->fecharBody();
PaginaSEI::getInstance()->fecharHtml();
?>
