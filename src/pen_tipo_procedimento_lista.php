<?php

try {
    include_once DIR_SEI_WEB . '/SEI.php';

    session_start();

    //////////////////////////////////////////////////////////////////////////////
    //InfraDebug::getInstance()->setBolLigado(false);
    //InfraDebug::getInstance()->setBolDebugInfra(true);
    //InfraDebug::getInstance()->limpar();
    //////////////////////////////////////////////////////////////////////////////

    SessaoSEI::getInstance()->validarLink();

    SessaoSEI::getInstance()->validarPermissao($_GET['acao']);

  switch ($_GET['acao']) {
    case 'pen_map_orgaos_exportar_tipos_processos':
        $strTitulo = 'Exportação de Tipos de Processo';
      if ($_POST['dadosInput']) {
        try {
            $arrStrIds = explode(',', $_POST['dadosInput']);
            $qtdSelecao = count($arrStrIds);

            $objTipoProcedimentoDTO = new TipoProcedimentoDTO();
            $objTipoProcedimentoDTO->retNumIdTipoProcedimento();
            $objTipoProcedimentoDTO->retStrNome();
            $objTipoProcedimentoDTO->setNumIdTipoProcedimento($arrStrIds, InfraDTO::$OPER_IN);
            PaginaSEI::getInstance()->prepararOrdenacao($objTipoProcedimentoDTO, 'Nome', InfraDTO::$TIPO_ORDENACAO_ASC);

            $objTipoProcedimentoRN = new TipoProcedimentoRN();
          if (InfraUtil::compararVersoes(SEI_VERSAO, ">=", "4.0.0")) {
            $arrObjTipoProcedimentoDTO = $objTipoProcedimentoRN->pesquisar($objTipoProcedimentoDTO);
          } else {
              $arrObjTipoProcedimentoDTO = $objTipoProcedimentoRN->listarRN0244($objTipoProcedimentoDTO);
          }

            $dados = [];
            $dados[] = ['ID', 'Nome'];

          foreach ($arrObjTipoProcedimentoDTO as $value) {
              $dados[] = [$value->getNumIdTipoProcedimento(), $value->getStrNome()];
          }

            $nomeArquivo = 'tipos_processos.csv';
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $nomeArquivo . '";');
            $fp = fopen('php://output', 'w');

          foreach ($dados as $linha) {
              fputcsv($fp, $linha, ';');
          }
            fclose($fp);
            exit(0);
        } catch (Exception $e) {
            PaginaSEI::getInstance()->processarExcecao($e);
        }
      }
        break;
    default:
        throw new InfraException("Módulo do Tramita: Ação '" . $_GET['acao'] . "' não reconhecida.");
  }

    $arrComandosModal = [];
    $arrComandosModal[] = '<button type="button" accesskey="E" id="btnExportarModal" value="Exportar" class="infraButton"><span class="infraTeclaAtalho">E</span>xportar</button>';
    $arrComandosModal[] = '<button type="button" accesskey="Fechar" id="btnFecharModal" value="Fechar" onclick="window.close();" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';
    $arrComandosModalFinal = [];
    $arrComandosModalFinal[] = '<button type="button" accesskey="E" id="btnExportarModalFinal" onclick="$(\'#btnExportarModal\').click();" value="Exportar" class="infraButton"><span class="infraTeclaAtalho">E</span>xportar</button>';
    $arrComandosModalFinal[] = '<button type="button" accesskey="Fechar" id="btnFecharModalFinal" value="Fechar" onclick="$(\'#btnFecharModal\').click();" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';

    $strResultadoExportar = '';

    $strResultadoExportar .= '<table width="99%" class="infraTable" id="tableExportar">' . "\n";
    $strResultadoExportar .= '<caption class="infraCaption" id="tableTotal"></caption>';

    $strResultadoExportar .= '<tr>';
    $strResultadoExportar .= '<th class="infraTh" width="30%">ID</th>' . "\n";
    $strResultadoExportar .= '<th class="infraTh">Tipo de Processo</th>' . "\n";
    $strResultadoExportar .= '</tr>' . "\n";
    $strResultadoExportar .= '</table>';

    $arrComandos = [];

    $arrComandos[] = '<button type="submit" accesskey="P" id="sbmPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';
    $arrComandos[] = '<button type="button" accesskey="E" onclick="exportarTiposProcessos();" id="btnExportar" value="Exportar" class="infraButton"><span class="infraTeclaAtalho">E</span>xportar</button>';

    $objTipoProcedimentoDTO = new TipoProcedimentoDTO();
    $objTipoProcedimentoDTO->retNumIdTipoProcedimento();
    $objTipoProcedimentoDTO->retStrNome();

  if ($_GET['acao'] == 'tipo_procedimento_reativar') {
      //Lista somente inativos
      $objTipoProcedimentoDTO->setBolExclusaoLogica(false);
      $objTipoProcedimentoDTO->setStrSinAtivo('N');
  }

    $strNomeTipoProcessoPesquisa = !empty($_POST['txtNomeTipoProcessoPesquisa']) && !is_null($_POST['txtNomeTipoProcessoPesquisa']) 
    ? $_POST['txtNomeTipoProcessoPesquisa']
    : "";
  if (trim($strNomeTipoProcessoPesquisa) != '') {
      $objTipoProcedimentoDTO->setStrNome('%' . trim($strNomeTipoProcessoPesquisa) . '%', InfraDTO::$OPER_LIKE);
  }

    $strIdAssunto = !empty($_POST['hdnIdAssuntoTipoProcesso']) && !is_null($_POST['hdnIdAssuntoTipoProcesso']) 
    ? $_POST['hdnIdAssuntoTipoProcesso']
    : "";
  if (!InfraString::isBolVazia($strIdAssunto)) {
      $objTipoProcedimentoDTO->setNumIdAssunto($strIdAssunto);
  }

    PaginaSEI::getInstance()->prepararOrdenacao($objTipoProcedimentoDTO, 'Nome', InfraDTO::$TIPO_ORDENACAO_ASC);

    $objTipoProcedimentoRN = new TipoProcedimentoRN();
  if (InfraUtil::compararVersoes(SEI_VERSAO, ">=", "4.0.0")) {
      $arrObjTipoProcedimentoDTO = $objTipoProcedimentoRN->pesquisar($objTipoProcedimentoDTO);
  } else {
      $arrObjTipoProcedimentoDTO = $objTipoProcedimentoRN->listarRN0244($objTipoProcedimentoDTO);
  }
    $numRegistros = count($arrObjTipoProcedimentoDTO);

  if ($numRegistros > 0) {

      $strResultado = '';

    if ($_GET['acao'] != 'tipo_procedimento_reativar') {
        $strSumarioTabela = 'Tabela de Tipos de Processo.';
        $strCaptionTabela = 'Tipos de Processo';
    } else {
        $strSumarioTabela = 'Tabela de Tipos de Processo Inativos.';
        $strCaptionTabela = 'Tipos de Processo Inativos';
    }

      $strResultado .= '<table width="99%" class="infraTable" summary="' . $strSumarioTabela . '">' . "\n"; //70
      $strResultado .= '<caption class="infraCaption">' . PaginaSEI::getInstance()->gerarCaptionTabela($strCaptionTabela, $numRegistros) . '</caption>';
      $strResultado .= '<tr>';
      $strResultado .= '<th class="infraTh" width="1%">' . PaginaSEI::getInstance()->getThCheck() . '</th>' . "\n";

      $strResultado .= '<th class="infraTh" width="30%">' . PaginaSEI::getInstance()->getThOrdenacao($objTipoProcedimentoDTO, 'ID', 'IdTipoProcedimento', $arrObjTipoProcedimentoDTO) . '</th>' . "\n";
      $strResultado .= '<th class="infraTh">' . PaginaSEI::getInstance()->getThOrdenacao($objTipoProcedimentoDTO, 'Nome', 'Nome', $arrObjTipoProcedimentoDTO) . '</th>' . "\n";
      $strResultado .= '</tr>' . "\n";
      $strCssTr = '';
    for ($i = 0; $i < $numRegistros; $i++) {
        $idTipoProcedimento = $arrObjTipoProcedimentoDTO[$i]->getNumIdTipoProcedimento();

        $strCssTr = ($strCssTr == '<tr class="infraTrClara">') ? '<tr class="infraTrEscura" id="trExportar-'.$idTipoProcedimento.'">' : '<tr class="infraTrClara" id="trExportar-'.$idTipoProcedimento.'">';

        $strResultado .= $strCssTr;

        $strResultado .= '<td valign="top">' . PaginaSEI::getInstance()->getTrCheck($i, $arrObjTipoProcedimentoDTO[$i]->getNumIdTipoProcedimento(), $arrObjTipoProcedimentoDTO[$i]->getStrNome()) . '</td>';

        $strResultado .= '<td align="center">' . $arrObjTipoProcedimentoDTO[$i]->getNumIdTipoProcedimento() . '</td>';
        $strResultado .= '<td>' . PaginaSEI::tratarHTML($arrObjTipoProcedimentoDTO[$i]->getStrNome()) . '</td>';
        $strResultado .= '</tr>' . "\n";
    }
      $strResultado .= '</table>';
  }
    $arrComandos[] = '<button type="button" accesskey="Fechar" id="btnFechar" value="Fechar" onclick="location.href=\'' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_orgaos_externos_listar&acao_origem=' . $_GET['acao']) . '\'" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';

    $strLinkAjaxAssuntoRI1223 = SessaoSEI::getInstance()->assinarLink('controlador_ajax.php?acao_ajax=assunto_auto_completar_RI1223');
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

.no-close .ui-dialog-titlebar-close {display: none;}
.ui-dialog .ui-dialog-title {margin: 0.4em 0;}
.ui-widget-header {background: #1351b4;border: 0;color: #fff;font-weight: normal;padding: 2px;}

#lblNomeTipoProcessoPesquisa {position:absolute;left:0%;top:0%;}
#txtNomeTipoProcessoPesquisa {position:absolute;left:0%;top:40%;width:25%;}

#lblAssuntoTipoProcesso {position:absolute;left:26%;top:0%;}
#txtAssuntoTipoProcesso {position:absolute;left:26%;top:40%;width:25%;}

.ui-dialog {z-index: 1001 !important;}

<?
PaginaSEI::getInstance()->fecharStyle();
PaginaSEI::getInstance()->montarJavaScript();
?>
<script type="text/javascript">
  var objAutoCompletarAssuntoRI1223 = null;

  function inicializar() {
    setTimeout("document.getElementById('btnFechar').focus()", 50);

    <?php if (InfraUtil::compararVersoes(SEI_VERSAO, ">=", "4.0.0")) { ?>
    objAutoCompletarAssuntoRI1223 = new infraAjaxAutoCompletar('hdnIdAssuntoTipoProcesso', 'txtAssuntoTipoProcesso', '<?php echo $strLinkAjaxAssuntoRI1223 ?>');
    objAutoCompletarAssuntoRI1223.limparCampo = true;
    objAutoCompletarAssuntoRI1223.prepararExecucao = function() {
      return 'palavras_pesquisa=' + document.getElementById('txtAssuntoTipoProcesso').value;
    };
    objAutoCompletarAssuntoRI1223.selecionar('<?php echo $strIdAssunto; ?>', '<?php echo PaginaSEI::getInstance()->formatarParametrosJavaScript($strDescricaoAssunto, false) ?>');
    <?php } ?>
    infraEfeitoTabelas();
  }

  function exportarTiposProcessos() {
    const hdnInfraItensSelecionados = $('#hdnInfraItensSelecionados').val();
    if (hdnInfraItensSelecionados == '' || hdnInfraItensSelecionados == undefined || hdnInfraItensSelecionados == null) {
      alert('selecione ao menos um tipo de processo para exportar.');
      return;
    }

    clonarSelecionados(hdnInfraItensSelecionados);

    $('#btnExportarModal').click(function(e) {
      e.preventDefault();
      selecionarTodosCheckbox();
      $('#tableExportar tr.infraTrClara').remove();
      $('#tableExportar tr.infraTrEscura').remove();
      $('#formExportarDados').dialog('close');
      const form = jQuery('#formExportarDados');
      form.submit();
    })
    $('#btnFecharModal').click(function(e) {
      e.preventDefault();
      $('#tableExportar tr.infraTrClara').remove();
      $('#tableExportar tr.infraTrEscura').remove();
      $('#formExportarDados').dialog('close');
    })

    $('#formExportarDados').dialog({
      height: 500,
      width: 700,
      modal: true,
      resizable: true,
      dialogClass: 'no-close success-dialog'
    });
  }

  function selecionarTodosCheckbox() {
    var lista = document.getElementsByClassName("infraCheckboxInput");
    for ( var i = 0 ; i < lista.length ; i++) {
        if (lista[i].checked == true) {
          $(lista[i]).click();
        }
    }
  }
  function clonarSelecionados(hdnInfraItensSelecionados) {
    const arraySelecionados = hdnInfraItensSelecionados.split(',');
    document.getElementById('dadosInput').value = hdnInfraItensSelecionados;

    for (let index = 0; index < arraySelecionados.length; index++) {
      const element = arraySelecionados[index];
      const selecionado = document.getElementById('trExportar-' + element);
      const clone = $(selecionado).clone();
      $('#tableExportar').append(clone);
    }
    $('#tableExportar tr td:first-child').remove();
  }
</script>
<?
PaginaSEI::getInstance()->fecharHead();
PaginaSEI::getInstance()->abrirBody($strTitulo, 'onload="inicializar();"');
?>
<?php $acao = $_GET['acao'] ?>
<form id="frmTipoProcedimentoLista" method="post" action="<?php echo SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . $acao . '&acao_origem=' . $acao) ?>">
  <?
  PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandos);
  PaginaSEI::getInstance()->abrirAreaDados('10em');
  ?>

  <label id="lblNomeTipoProcessoPesquisa" accesskey="o" for="txtNomeTipoProcessoPesquisa" class="infraLabelOpcional">N<span class="infraTeclaAtalho">o</span>me:</label>
  <input type="text" id="txtNomeTipoProcessoPesquisa" name="txtNomeTipoProcessoPesquisa" value="<?php echo PaginaSEI::tratarHTML($strNomeTipoProcessoPesquisa) ?>" class="infraText" tabindex="<?php echo PaginaSEI::getInstance()->getProxTabDados() ?>" />

  <?php if (InfraUtil::compararVersoes(SEI_VERSAO, ">=", "4.0.0")) { ?>
    <label id="lblAssuntoTipoProcesso" for="txtAssuntoTipoProcesso" class="infraLabelOpcional">Assunto:</label>
    <input type="text" id="txtAssuntoTipoProcesso" name="txtAssuntoTipoProcesso" class="infraText" tabindex="<?php echo PaginaSEI::getInstance()->getProxTabDados() ?>" value="<?php echo PaginaSEI::tratarHTML($strDescricaoAssunto) ?>" />
  <?php } ?>

  <input type="hidden" id="hdnIdAssuntoTipoProcesso" name="hdnIdAssuntoTipoProcesso" class="infraText" value="<?php echo $strIdAssunto ?>" />
  <?
  PaginaSEI::getInstance()->fecharAreaDados();
  PaginaSEI::getInstance()->montarAreaTabela($strResultado, $numRegistros);
  //PaginaSEI::getInstance()->montarAreaDebug();
  PaginaSEI::getInstance()->montarBarraComandosInferior($arrComandos);
  ?>
</form>
<form id="formExportarDados" method="post" style="display: none;" action="<?php echo SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . $acao . '&acao_origem=' . $acao) ?>">
  <div id="divInfraBarraLocalizacao2" class="infraBarraLocalizacao" tabindex="450">Pré-visualização da Exportação</div>
  <input type="hidden" name="dadosInput" id="dadosInput">
  <?php PaginaSEI::getInstance()->abrirAreaDados('5em'); ?>
  <?php PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandosModal); ?>
  <?php PaginaSEI::getInstance()->fecharAreaDados(); ?>
  <?php echo $strResultadoExportar ?>
  <?php PaginaSEI::getInstance()->montarBarraComandosInferior($arrComandosModalFinal, true); ?>
</form>
<?
PaginaSEI::getInstance()->fecharBody();
PaginaSEI::getInstance()->fecharHtml();
?>
