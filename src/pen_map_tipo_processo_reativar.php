<?php

require_once DIR_SEI_WEB . '/SEI.php';

session_start();

define('PEN_RECURSO_ATUAL', 'pen_map_tipo_processo');
define('PEN_RECURSO_BASE', 'pen_map_tipo_processo_reativar');
define('PEN_PAGINA_TITULO', 'Reativar Mapeamento de Tipos de Processo');
define('PEN_PAGINA_GET_ID', 'id');

$objPagina = PaginaSEI::getInstance();
$objBanco = BancoSEI::getInstance();
$objSessao = SessaoSEI::getInstance();
$objDebug = InfraDebug::getInstance();

$acaoOrigem = $_GET['acao_origem'];

PaginaSEI::getInstance()->salvarCamposPost(['txtTipoOrigem']);
$txtTipoOrigem = PaginaSEI::getInstance()->recuperarCampo('txtTipoOrigem');

try {

    $objDebug->setBolLigado(false);
    $objDebug->setBolDebugInfra(true);
    $objDebug->limpar();

    $objSessao->validarLink();

    $idOrgaoExterno = $_GET['id'];

    $arrParam = array_merge($_GET, $_POST);
    $strParametros .= '&id=' . $idOrgaoExterno;

  switch ($_GET['acao']) {
    case 'pen_map_tipo_processo_reativar':
        $strTitulo = 'Reativar Mapeamento de Tipos de Processo';
      if (isset($_POST['hdnInfraItensSelecionados']) && !empty($_POST['hdnInfraItensSelecionados'])) {
          $arrHdnInInfraItensSelecionados = explode(",", $_POST['hdnInfraItensSelecionados']);
        foreach ($arrHdnInInfraItensSelecionados as $arr) {
            $id = explode(";", $arr)[0];
            $objPenMapTipoProcedimentoDTO = new PenMapTipoProcedimentoDTO();
            $objPenMapTipoProcedimentoDTO->setDblId($id);
            $objPenMapTipoProcedimentoDTO->setStrAtivo('S');

            $objPenMapTipoProcedimentoRN = new PenMapTipoProcedimentoRN();
            $objPenMapTipoProcedimentoRN->alterar($objPenMapTipoProcedimentoDTO);
        }
          $objPagina->adicionarMensagem(sprintf('%s foi reativado com sucesso.', 'Mapeamento de Tipo de Processo'), 5);
          header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_tipo_processo_reativar&acao_origem=' . $_GET['acao'] . $strParametros));
          exit(0);
      }

      if (isset($_POST['hdnInfraItemId']) && is_numeric($_POST['hdnInfraItemId'])) {
          $objPenMapTipoProcedimentoDTO = new PenMapTipoProcedimentoDTO();
          $objPenMapTipoProcedimentoDTO->setDblId($_POST['hdnInfraItemId']);
          $objPenMapTipoProcedimentoDTO->setStrAtivo('S');

          $objPenMapTipoProcedimentoRN = new PenMapTipoProcedimentoRN();
          $objPenMapTipoProcedimentoRN->alterar($objPenMapTipoProcedimentoDTO);
          $objPagina->adicionarMensagem(sprintf('%s foi reativado com sucesso.', 'Mapeamento de Tipo de Processo'), 5);
          header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_tipo_processo_reativar&acao_origem=' . $_GET['acao'] . $strParametros));
          exit(0);
      }
        break;
    default:
        throw new InfraException("Módulo do Tramita: Ação '" . $_GET['acao'] . "' não reconhecida.");
  }

    $arrComandos = [];
    $arrComandosFinal = [];

    $arrComandos[] = '<button type="submit" accesskey="P" id="btnPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';

    $objMapeamentoTipoProcedimentoDTO = new PenMapTipoProcedimentoDTO();
    $objMapeamentoTipoProcedimentoDTO->setStrAtivo('N');
    $objMapeamentoTipoProcedimentoDTO->retDblId();
    $objMapeamentoTipoProcedimentoDTO->retNumIdMapOrgao();
    $objMapeamentoTipoProcedimentoDTO->retNumIdTipoProcessoOrigem();
    $objMapeamentoTipoProcedimentoDTO->retNumIdTipoProcessoDestino();
    $objMapeamentoTipoProcedimentoDTO->retStrNomeTipoProcesso();
    $objMapeamentoTipoProcedimentoDTO->retStrOrgaoDestino();
    $objMapeamentoTipoProcedimentoDTO->retStrOrgaoOrigem();
    $objMapeamentoTipoProcedimentoDTO->retStrNomeTipoProcedimento();
    $objMapeamentoTipoProcedimentoDTO->retStrAtivo();


  if (isset($_POST['chkSinAssuntosNaoMapeados'])) {
      $objMapeamentoTipoProcedimentoDTO->setNumIdTipoProcessoDestino(null);
  }

    $objMapeamentoTipoProcedimentoDTO->setStrNomeTipoProcesso('%' . trim($txtTipoOrigem . '%'), InfraDTO::$OPER_LIKE);

    $objPagina->prepararOrdenacao($objMapeamentoTipoProcedimentoDTO, 'OrgaoOrigem', InfraDTO::$TIPO_ORDENACAO_ASC);
    $objPagina->prepararPaginacao($objMapeamentoTipoProcedimentoDTO);

    $objMapeamentoTipoProcedimentoRN = new PenMapTipoProcedimentoRN();
    $arrObjMapeamentoAssuntoDTO = $objMapeamentoTipoProcedimentoRN->listar($objMapeamentoTipoProcedimentoDTO);

    $objPagina->processarPaginacao($objMapeamentoTipoProcedimentoDTO);
    $numRegistros = InfraArray::contar($arrObjMapeamentoAssuntoDTO);

    $strAjaxVariaveis = '';
  if ($numRegistros > 0) {

      $arrComandos[] = '<button type="button" id="btnReativar" value="Reativar" onclick="onClickBtnReativar()" class="infraButton btnReativar"><span class="infraTeclaAtalho">R</span>eativar</button>';

      $arrComandosFinal[] = '<button type="button" id="btnReativar" value="Reativar" onclick="onClickBtnReativar()" class="infraButton btnReativar"><span class="infraTeclaAtalho">R</span>eativar</button>';

      $strLinkGerenciar = SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_orgaos_externos_mapeamento_gerenciar&acao_origem=' . $_GET['acao'] . '&acao_retorno=' . $_GET['acao'] . $strParametros);

      $strResultado = '';
      $strAjaxInicializar = '';

      $strSumarioTabela = 'Tabela de mapeamento de tipo de processo.';
      $strCaptionTabela = 'tipos de processos para mapeamento';

      $strResultado .= '<table width="99%" class="infraTable" summary="' . $strSumarioTabela . '">' . "\n";
      $strResultado .= '<caption class="infraCaption">' . $objPagina->gerarCaptionTabela($strCaptionTabela, $numRegistros) . '</caption>';
      $strResultado .= '<tr>';


      $strResultado .= '<th class="infraTh" width="1%">' . $objPagina->getThCheck() . '</th>' . "\n";

      $strResultado .= '<th class="infraTh">' . $objPagina->getThOrdenacao($objMapeamentoTipoProcedimentoDTO, 'Unidade Origem', 'OrgaoOrigem', $arrObjMapeamentoAssuntoDTO) . '</th>' . "\n";
      $strResultado .= '<th class="infraTh">' . $objPagina->getThOrdenacao($objMapeamentoTipoProcedimentoDTO, 'Unidade Destino', 'OrgaoDestino', $arrObjMapeamentoAssuntoDTO) . '</th>' . "\n";
      $strResultado .= '<th class="infraTh">' . $objPagina->getThOrdenacao($objMapeamentoTipoProcedimentoDTO, 'Tipo de Processo Origem', 'NomeTipoProcesso', $arrObjMapeamentoAssuntoDTO) . '</th>' . "\n";
      $strResultado .= '<th class="infraTh">' . $objPagina->getThOrdenacao($objMapeamentoTipoProcedimentoDTO, 'Tipo de Processo Destino', 'NomeTipoProcedimento', $arrObjMapeamentoAssuntoDTO) . '</th>' . "\n";
      $strResultado .= '<th class="infraTh" width="10%">Ações</th>' . "\n";

      $strResultado .= '</tr>' . "\n";
      $strCssTr = '';
    for ($i = 0; $i < $numRegistros; $i++) {

        $numIdAssuntoOrigem = $arrObjMapeamentoAssuntoDTO[$i]->getNumIdTipoProcessoOrigem();
        $numIdAssuntoDestino = $arrObjMapeamentoAssuntoDTO[$i]->getNumIdTipoProcessoDestino();

        $strResultado .= ($strCssTr == '<tr class="infraTrClara">') ? '<tr class="infraTrEscura">' : '<tr class="infraTrClara">';

        $strResultado .= '<td align="center">' . $objPagina->getTrCheck($i, $arrObjMapeamentoAssuntoDTO[$i]->getDblId() . ';' . $arrObjMapeamentoAssuntoDTO[$i]->getStrAtivo(), '') . '</td>';
        $strResultado .= '<td align="center">'. $arrObjMapeamentoAssuntoDTO[$i]->getStrOrgaoOrigem() . '';
        $strResultado .= '<td align="center">'. $arrObjMapeamentoAssuntoDTO[$i]->getStrOrgaoDestino() . '</td>';
        $strResultado .= '<td>' . $arrObjMapeamentoAssuntoDTO[$i]->getStrNomeTipoProcesso() . '</td>';
        $strResultado .= '<td>' . $arrObjMapeamentoAssuntoDTO[$i]->getStrNomeTipoProcedimento() . '</td>';
        $strResultado .= '<td align="center">';

        $strLinkReativar = $objSessao->assinarLink('controlador.php?acao=pen_map_tipo_processo_reativar&acao_origem=' . $acaoOrigem . '&id=' . $arrObjMapeamentoAssuntoDTO[$i]->getDblId());
        $strId = $arrObjMapeamentoAssuntoDTO[$i]->getDblId();
        $strResultado .= '<a class="reativar"  onclick="acaoReativar(\'' . $strId . '\')"><img src="' . PaginaSEI::getInstance()->getIconeReativar() . '" title="Reativar Mapeamento de Tipo de Processo" alt="Reativar Mapeamento de Tipo de Processo" class="infraImg"></a>';

        $strResultado .= '</td>';
        $strResultado .= '</tr>' . "\n";
    }

      $strResultado .= '</table>';
  }

    $arrComandos[] = '<button type="button" accesskey="F" id="btnFechar" value="Fechar" onclick="location.href=\'' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_orgaos_externos_listar&acao_origem=' . $_GET['acao'] . $strParametros . PaginaSEI::getInstance()->montarAncora($idOrgaoExterno)) . '\'" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';
    $arrComandosFinal[] = '<button type="button" accesskey="F" id="btnFechar" value="Fechar" onclick="location.href=\'' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_orgaos_externos_listar&acao_origem=' . $_GET['acao'] . $strParametros . PaginaSEI::getInstance()->montarAncora($idOrgaoExterno)) . '\'" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';
} catch (Exception $e) {
    PaginaSEI::getInstance()->processarExcecao($e);
}

PaginaSEI::getInstance()->montarDocType();
PaginaSEI::getInstance()->abrirHtml();
PaginaSEI::getInstance()->abrirHead();
PaginaSEI::getInstance()->montarMeta();
PaginaSEI::getInstance()->montarTitle(PaginaSEI::getInstance()->getStrNomeSistema() . ' - ' . $strTitulo);
PaginaSEI::getInstance()->montarStyle();
?>
<style type="text/css">
  .no-close .ui-dialog-titlebar-close {
    display: none;
  }

  .ui-dialog .ui-dialog-title {
    margin: 0.4em 0;
  }

  .ui-widget-header {
    background: #1351b4;
    border: 0;
    color: #fff;
    font-weight: normal;
    padding: 2px;
  }

  .lblTipoOrigem {
    position: absolute;
    left: 0%;
    top: 0%;
    width: 25%;
  }

  #txtTipoOrigem {
    position: absolute;
    left: 0%;
    top: 50%;
    width: 25%
  }

  .ui-dialog {
    z-index: 1001 !important;
  }

  .infraDivRotuloOrdenacao {
    text-align: center !important;
  }
</style>
<?php PaginaSEI::getInstance()->montarJavaScript(); ?>
<script type="text/javascript">
  <?php echo $strAjaxVariaveis ?>

  var bolAlteracao = false;

  function inicializar() {

    var linkAutoCompletar = '<?php echo SessaoSEI::getInstance()->assinarLink('controlador_ajax.php?acao_ajax=tipo_procedimento_auto_completar') ?>';

    <?php echo $strAjaxInicializar ?>

    bolAlteracao = false;
  }

  function acaoReativar(id) {
    if (confirm("Confirma a reativação do Mapeamento de Tipo de Processo?")) {
      document.getElementById('hdnInfraItemId').value = id;
      document.getElementById('frmMapeamentoOrgaosLista').action = '<?php echo $strLinkReativar ?>';
      document.getElementById('frmMapeamentoOrgaosLista').submit();
    }
  }

  function onClickBtnReativar() {
    try {
      var len = jQuery('input[name*=chkInfraItem]:checked').length;
      if (len > 0) {
        if (confirm('Confirma a reativação de ' + len + ' mapeamento(s) de tipos de processo ?')) {
          document.getElementById('frmMapeamentoOrgaosLista').action = '<?php echo $strLinkReativar ?>';
          document.getElementById('frmMapeamentoOrgaosLista').submit();
        }
      } else {
        alert('Selecione pelo menos um mapeamento para reativar');
      }
    } catch (e) {
      alert('Erro : ' + e.message);
    }
  }
  $(function () {
    $('#chkSinAssuntosNaoMapeados').click(function () {
      $('#btnPesquisar').click();
    });
  });
</script>
<?php
PaginaSEI::getInstance()->fecharHead();
PaginaSEI::getInstance()->abrirBody($strTitulo, 'onload="inicializar();"');
$acao = $_GET['acao'];
?>
<form id="frmMapeamentoOrgaosLista" method="post" action="<?php echo SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . $acao . '&acao_origem=' . $acao . '&id=' . $idOrgaoExterno) ?>">
  <?php
    PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandos);
    PaginaSEI::getInstance()->abrirAreaDados('5em');
  ?>
  <label for="txtTipoOrigem" id="lblTipoOrigem" class="lblTipoOrigem infraLabelOpcional">Tipo de Processo Origem:</label>
  <input type="text" id="txtTipoOrigem" name="txtTipoOrigem" class="infraText" value="<?php echo PaginaSEI::tratarHTML($txtTipoOrigem); ?>">
  <?
  PaginaSEI::getInstance()->fecharAreaDados();
  PaginaSEI::getInstance()->montarAreaTabela($strResultado, $numRegistros);
  //PaginaSEI::getInstance()->montarAreaDebug();
  PaginaSEI::getInstance()->montarBarraComandosInferior($arrComandosFinal, true);
  ?>
</form>
<?php
PaginaSEI::getInstance()->fecharBody();
PaginaSEI::getInstance()->fecharHtml();
?>
