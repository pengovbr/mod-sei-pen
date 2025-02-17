<?php

require_once DIR_SEI_WEB . '/SEI.php';

session_start();

define('PEN_RECURSO_ATUAL', 'pen_map_orgaos_externos_mapeamento_tipo_listar');
define('PEN_RECURSO_BASE', 'pen_map_orgaos_externos_mapeamento');
define('PEN_PAGINA_TITULO', 'Mapeamento de Tipo de Processo');
define('PEN_PAGINA_GET_ID', 'id');

$objPagina = PaginaSEI::getInstance();
$objBanco = BancoSEI::getInstance();
$objSessao = SessaoSEI::getInstance();
$objDebug = InfraDebug::getInstance();

$acaoOrigem = $_GET['acao_origem'];

PaginaSEI::getInstance()->salvarCamposPost(['txtPalavrasPesquisaMapeamento']);
$palavrasPesquisa = PaginaSEI::getInstance()->recuperarCampo('txtPalavrasPesquisaMapeamento');

try {

    $objDebug->setBolLigado(false);
    $objDebug->setBolDebugInfra(true);
    $objDebug->limpar();

    $objSessao->validarLink();

    $idOrgaoExterno = $_GET['id'];

    $arrParam = array_merge($_GET, $_POST);
    $strParametros .= '&id=' . $idOrgaoExterno;

  switch ($_GET['acao']) {
    case 'pen_map_orgaos_externos_mapeamento_desativar':
      if ((isset($_POST['hdnInfraItensSelecionados']) && !empty($_POST['hdnInfraItensSelecionados'])) && isset($_POST['hdnAcaoDesativar'])) {
          $btnDesativar = null;
          $arrHdnInInfraItensSelecionados = explode(",", $_POST['hdnInfraItensSelecionados']);
        foreach ($arrHdnInInfraItensSelecionados as $arr) {
            $id = explode(";", $arr)[0];
            $objPenMapTipoProcedimentoDTO = new PenMapTipoProcedimentoDTO();
            $objPenMapTipoProcedimentoDTO->setDblId($id);
            $objPenMapTipoProcedimentoDTO->setStrAtivo('N');

            $objPenMapTipoProcedimentoRN = new PenMapTipoProcedimentoRN();
            $objPenMapTipoProcedimentoRN->alterar($objPenMapTipoProcedimentoDTO);
        }
          $objPagina->adicionarMensagem(sprintf('%s foi desativado com sucesso.', PEN_PAGINA_TITULO), 5);
          header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_orgaos_externos_mapeamento&acao_origem=' . $_GET['acao'] . $strParametros));
          exit(0);
      }

      if (isset($_POST['hdnInfraItemId']) && is_numeric($_POST['hdnInfraItemId'])) {
          $objPenMapTipoProcedimentoDTO = new PenMapTipoProcedimentoDTO();
          $objPenMapTipoProcedimentoDTO->setDblId($_POST['hdnInfraItemId']);
          $objPenMapTipoProcedimentoDTO->setStrAtivo('N');

          $objPenMapTipoProcedimentoRN = new PenMapTipoProcedimentoRN();
          $objPenMapTipoProcedimentoRN->alterar($objPenMapTipoProcedimentoDTO);
          $objPagina->adicionarMensagem(sprintf('%s foi desativado com sucesso.', PEN_PAGINA_TITULO), 5);
          header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_orgaos_externos_mapeamento&acao_origem=' . $_GET['acao'] . $strParametros));
          exit(0);
      }
        break;
    case 'pen_map_orgaos_externos_mapeamento_gerenciar':
      try {

          $arrTiposProcessos = $_POST;
        foreach (array_keys($arrTiposProcessos) as $strKeyPost) {
          if (substr($strKeyPost, 0, 10) == 'txtAssunto') {
            $objConsultaTipoProcedimentoDTO = new PenMapTipoProcedimentoDTO();
            $objConsultaTipoProcedimentoDTO->setNumIdMapOrgao($_POST['idOrgaoExterno']);
            $objConsultaTipoProcedimentoDTO->setNumIdTipoProcessoOrigem(substr($strKeyPost, 10));
            $objConsultaTipoProcedimentoDTO->retDblId();
            $objConsultaTipoProcedimentoDTO->retNumIdTipoProcessoOrigem();

            $objConsultaTipoProcedimentoRN = new PenMapTipoProcedimentoRN();
            $objConsultaTipoProcedimentoDTO = $objConsultaTipoProcedimentoRN->consultar($objConsultaTipoProcedimentoDTO);

            $objMapeamentoAssuntoDTO = new PenMapTipoProcedimentoDTO();
            $objMapeamentoAssuntoDTO->setDblId($objConsultaTipoProcedimentoDTO->getDblId());
            $objMapeamentoAssuntoDTO->setNumIdTipoProcessoOrigem(substr($strKeyPost, 10));
            $objMapeamentoAssuntoDTO->setNumIdTipoProcessoDestino($_POST['hdnIdAssunto' . substr($strKeyPost, 10)]);

            $objAlterTipoProcedimentoRN = new PenMapTipoProcedimentoRN();
            $objAlterTipoProcedimentoRN->alterar($objMapeamentoAssuntoDTO);
          }
        }

          $objPagina->adicionarMensagem(sprintf('Inclusão de %s realizada com sucesso.', PEN_PAGINA_TITULO), 5);
      } catch (Exception $e) {
          PaginaSEI::getInstance()->processarExcecao($e);
      }

        header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . $_GET['acao_origem'] . '&acao_origem=' . $_GET['acao'] . '&id=' . $_POST['idOrgaoExterno']));
        die;
    case 'pen_map_orgaos_externos_mapeamento':
        $strTitulo = 'Mapeamento de Tipo de Processo';
      if (!is_null($_POST['mapId']) && !empty($_POST['mapId'])) {
        try {
            $penMapTipoProcedimentoRN = new PenMapTipoProcedimentoRN();
            $arrProcedimentoDTO = [];
            $tipoDeProcedimentos = [];
            $procedimentos = explode(',', $_POST['dados']);
          for ($i = 0; $i < count($procedimentos); $i += 2) {
            $key = trim($procedimentos[$i]);
            $value = trim($procedimentos[$i + 1], '"');
            $tipoDeProcedimentos[$key] = $value;
          }
            $contador = 0;
          foreach ($tipoDeProcedimentos as $idProcedimento => $nomeProcedimento) {
              $procedimentoDTO = new PenMapTipoProcedimentoDTO();
              $procedimentoDTO->setNumIdMapOrgao($_POST['mapId']);
              $procedimentoDTO->setNumIdTipoProcessoOrigem($idProcedimento);
              $procedimentoDTO->setStrNomeTipoProcesso($nomeProcedimento);
              $procedimentoDTO->setNumIdUnidade($_GET['infra_unidade_atual']);
            if ($penMapTipoProcedimentoRN->contar($procedimentoDTO)) {
              continue;
            }
              $procedimentoDTO->setDthRegistro(date('d/m/Y H:i:s'));
              $penMapTipoProcedimentoRN->cadastrar($procedimentoDTO);
              $contador += 1;
          }
            $mensagem = "Importação realizada com sucesso. Importado(s) %s tipo(s) de processo(s).\n"
            . "Obs.: Se algum tipo de processo não foi importado, verifique se ele já está presente na tabela e/ou se foi desativado.";
            $objPagina->adicionarMensagem(sprintf($mensagem, $contador), 5);
            header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_orgaos_externos_mapeamento&acao_origem=' . $_GET['acao'] . '&id=' . $_POST['mapId']));
            exit(0);
        } catch (Exception $e) {
            throw new InfraException($e->getMessage());
        }
      }
        break;
    case 'pen_map_orgaos_externos_mapeamento_excluir':
      if (array_key_exists('hdnInfraItensSelecionados', $arrParam) && !empty($arrParam['hdnInfraItensSelecionados'])) {

          $objExclusaoTipoProcedimentoDTO = new PenMapTipoProcedimentoDTO();
          $objExclusaoTipoProcedimentoRN = new PenMapTipoProcedimentoRN();

          $arrParam['hdnInfraItensSelecionados'] = explode(',', $arrParam['hdnInfraItensSelecionados']);

        if (is_array($arrParam['hdnInfraItensSelecionados'])) {
          foreach ($arrParam['hdnInfraItensSelecionados'] as $arr) {
            $dblId = explode(";", $arr)[0];
            $objExclusaoTipoProcedimentoDTO->setDblId($dblId);
            $objExclusaoTipoProcedimentoRN->excluir($objExclusaoTipoProcedimentoDTO);
          }
        } else {
            $objExclusaoTipoProcedimentoDTO->setDblId($arrParam['hdnInfraItensSelecionados']);
            $objExclusaoTipoProcedimentoRN->excluir($objExclusaoTipoProcedimentoDTO);
        }

          $objPagina->adicionarMensagem('Mapeamento de tipos de processo foi excluído com sucesso.', 5);
          header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_orgaos_externos_mapeamento&acao_origem=' . $_GET['acao'] . '&id=' . $idOrgaoExterno));
          exit(0);
      } else {
          $objPagina->adicionarMensagem('Não existe nenhum registro de mapeamento para tipos de processo.', InfraPagina::$TIPO_MSG_AVISO);
          header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_orgaos_externos_mapeamento&acao_origem=' . $_GET['acao'] . '&id=' . $idOrgaoExterno));
          exit(0);
      }
    default:
        throw new InfraException("Módulo do Tramita: Ação '" . $_GET['acao'] . "' não reconhecida.");
  }

    $arrComandos = [];
    $arrComandosFinal = [];

    $arrComandos[] = '<button type="submit" accesskey="P" id="btnPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';

    $objMapeamentoTipoProcedimentoDTO = new PenMapTipoProcedimentoDTO();
    $objMapeamentoTipoProcedimentoDTO->setNumIdMapOrgao($idOrgaoExterno);
    $objMapeamentoTipoProcedimentoDTO->setStrAtivo('S');
    $objMapeamentoTipoProcedimentoDTO->retDblId();
    $objMapeamentoTipoProcedimentoDTO->retNumIdMapOrgao();
    $objMapeamentoTipoProcedimentoDTO->retNumIdTipoProcessoOrigem();
    $objMapeamentoTipoProcedimentoDTO->retNumIdTipoProcessoDestino();
    $objMapeamentoTipoProcedimentoDTO->retStrNomeTipoProcesso();
    $objMapeamentoTipoProcedimentoDTO->retStrAtivo();
    $objMapeamentoTipoProcedimentoDTO->setOrdStrNomeTipoProcesso(InfraDTO::$TIPO_ORDENACAO_ASC);

  if (isset($_POST['chkSinAssuntosNaoMapeados'])) {
      $objMapeamentoTipoProcedimentoDTO->setNumIdTipoProcessoDestino(null);
  }

    $filtro = (int) $palavrasPesquisa;
  if (!empty($filtro) && $filtro != null || $filtro != 0) {
      $objMapeamentoTipoProcedimentoDTO->setNumIdTipoProcessoOrigem($palavrasPesquisa, InfraDTO::$OPER_IGUAL);
  } else {
      $objMapeamentoTipoProcedimentoDTO->setStrNomeTipoProcesso('%' . trim($palavrasPesquisa . '%'), InfraDTO::$OPER_LIKE);
  }

    $objPenOrgaoExternoDTO = new PenOrgaoExternoDTO();
    $objPenOrgaoExternoDTO->setDblId($idOrgaoExterno);
    $objPenOrgaoExternoDTO->retStrOrgaoDestino();
    $objPenOrgaoExternoDTO->retStrOrgaoOrigem();


    $objPenOrgaoExternoRN = new PenOrgaoExternoRN();
    $objPenOrgaoExternoDTO = $objPenOrgaoExternoRN->consultar($objPenOrgaoExternoDTO);

    // PaginaSEI::getInstance()->prepararOrdenacao($objMapeamentoTipoProcedimentoDTO, 'OrgaoOrigem', InfraDTO::$TIPO_ORDENACAO_ASC);
    PaginaSEI::getInstance()->prepararPaginacao($objMapeamentoTipoProcedimentoDTO, 100);

    $objMapeamentoTipoProcedimentoRN = new PenMapTipoProcedimentoRN();
    $arrObjMapeamentoAssuntoDTO = $objMapeamentoTipoProcedimentoRN->listar($objMapeamentoTipoProcedimentoDTO);

    PaginaSEI::getInstance()->processarPaginacao($objMapeamentoTipoProcedimentoDTO);


    $numRegistros = InfraArray::contar($arrObjMapeamentoAssuntoDTO);

    $arrComandos[] = '<button type="button" accesskey="S" id="importarCsvButton" value="Salvar" onclick="infraImportarCsv('
    . "'" . $objSessao->assinarLink('controlador.php?acao=pen_map_orgaos_externos_mapeamento&tipo_pesquisa=1&id_object=objInfraTableToTable&idMapOrgao=' . $idOrgaoExterno)
    . "'," . $idOrgaoExterno . ')" '
    . 'class="infraButton"><span class="infraTeclaAtalho">I</span>mportar</button>';

    $arrComandosFinal[] = '<button type="button" accesskey="S" id="importarCsvButton" value="Salvar" onclick="infraImportarCsv('
    . "'" . $objSessao->assinarLink('controlador.php?acao=pen_map_orgaos_externos_mapeamento&tipo_pesquisa=1&id_object=objInfraTableToTable&idMapOrgao=' . $idOrgaoExterno)
    . "'," . $idOrgaoExterno . ')" '
    . 'class="infraButton"><span class="infraTeclaAtalho">I</span>mportar</button>';

  if ($numRegistros > 0) {

      $arrComandos[] = '<button type="button" accesskey="S" id="btnSalvar" value="Salvar" onclick="gerenciar();" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
      $arrComandos[] = '<button type="button" value="Desativar" id="btnDesativar" onclick="onClickBtnDesativar()" class="infraButton"><span class="infraTeclaAtalho">D</span>esativar</button>';
      $arrComandos[] = '<button type="button" value="Excluir" id="btnExcluir" onclick="onClickBtnExcluir()" class="infraButton"><span class="infraTeclaAtalho">E</span>xcluir</button>';

      $arrComandosFinal[] = '<button type="button" accesskey="S" id="btnSalvar" value="Salvar" onclick="gerenciar();" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
      $arrComandosFinal[] = '<button type="button" value="Desativar" id="btnDesativar" onclick="onClickBtnDesativar()" class="infraButton"><span class="infraTeclaAtalho">D</span>esativar</button>';
      $arrComandosFinal[] = '<button type="button" value="Excluir" id="btnExcluir" onclick="onClickBtnExcluir()" class="infraButton"><span class="infraTeclaAtalho">E</span>xcluir</button>';

      $strLinkGerenciar = SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_orgaos_externos_mapeamento_gerenciar&acao_origem=' . $_GET['acao'] . '&acao_retorno=' . $_GET['acao'] . $strParametros);

      $bolAcaoExcluir = SessaoSEI::getInstance()->verificarPermissao('mapeamento_assunto_excluir');

      $strResultado = '';
      $strAjaxVariaveis = '';
      $strAjaxInicializar = '';

      $strSumarioTabela = 'Tabela de mapeamento de tipo de processo.';
      $strCaptionTabela = 'tipos de processos para mapeamento';

      $strResultado .= '<table width="99%" class="infraTable" summary="' . $strSumarioTabela . '">' . "\n";
      $strResultado .= '<caption class="infraCaption">' . PaginaSEI::getInstance()->gerarCaptionTabela($strCaptionTabela, $numRegistros) . '</caption>';
      $strResultado .= '<tr>';


      $strResultado .= '<th class="infraTh" width="1%">' . $objPagina->getThCheck() . '</th>' . "\n";

      $strResultado .= '<th width="45%" class="infraTh">' . PaginaSEI::getInstance()->getThOrdenacao($objMapeamentoTipoProcedimentoDTO, 'Tipo de Processo Origem', 'id', $arrObjMapeamentoAssuntoDTO) . '</th>' . "\n";
      $strResultado .= '<th width="45%" class="infraTh">Tipo de Processo Destino</th>' . "\n";
      $strResultado .= '<th class="infraTh" width="15%">Ações</th>' . "\n";

      $strResultado .= '</tr>' . "\n";
      $strCssTr = '';
    for ($i = 0; $i < $numRegistros; $i++) {

        $numIdAssuntoOrigem = $arrObjMapeamentoAssuntoDTO[$i]->getNumIdTipoProcessoOrigem();
        $numIdAssuntoDestino = $arrObjMapeamentoAssuntoDTO[$i]->getNumIdTipoProcessoDestino();

      if ($arrObjMapeamentoAssuntoDTO[$i]->getStrAtivo() == 'S') {
        $strCssTr = ($strCssTr == '<tr class="infraTrClara">') ? '<tr class="infraTrEscura">' : '<tr class="infraTrClara">';
      } else {
          $strCssTr = '<tr class="trVermelha">';
      }

        $strResultado .= $strCssTr;

        $strResultado .= '<td align="center">' . $objPagina->getTrCheck($i, $arrObjMapeamentoAssuntoDTO[$i]->getDblId() . ';' . $arrObjMapeamentoAssuntoDTO[$i]->getStrAtivo(), '') . '</td>';

        $strResultado .= '<td>' . PaginaSEI::tratarHTML(AssuntoINT::formatarCodigoDescricaoRI0568($numIdAssuntoOrigem, $arrObjMapeamentoAssuntoDTO[$i]->getStrNomeTipoProcesso())) . '</td>';

        $descricaoTipoProcedimento = '';
      if ($numIdAssuntoDestino != null) {
          $tipoProcedimentoDTO = new TipoProcedimentoDTO();
          $tipoProcedimentoDTO->retNumIdTipoProcedimento();
          $tipoProcedimentoDTO->retStrNome();
          $tipoProcedimentoDTO->setNumIdTipoProcedimento($numIdAssuntoDestino);

          $tipoProcedimentoRN = new TipoProcedimentoRN();
          $objTipoProcedimentoDTO = $tipoProcedimentoRN->consultarRN0267($tipoProcedimentoDTO);
          $descricaoTipoProcedimento = $numIdAssuntoDestino . ' - ' . $objTipoProcedimentoDTO->getStrNome();
      }

        $strResultado .= '<td> <input type="text" value="' . $descricaoTipoProcedimento . '" id="txtAssunto' . $numIdAssuntoOrigem . '" name="txtAssunto' . $numIdAssuntoOrigem . '" class="infraText" tabindex="' . PaginaSEI::getInstance()->getProxTabTabela() . '" style="width:99.5%" />
        <input type="hidden" id="hdnIdAssunto' . $numIdAssuntoOrigem . '" name="hdnIdAssunto' . $numIdAssuntoOrigem . '" class="infraText" value="' . $numIdAssuntoDestino . '" /></td>';

        $strResultado .= '<td align="center">';

      if ($arrObjMapeamentoAssuntoDTO[$i]->getStrAtivo() == 'S') {
          $strId = $arrObjMapeamentoAssuntoDTO[$i]->getDblId();
          $strLinkDesativar = $objSessao->assinarLink(
              'controlador.php?acao=pen_map_orgaos_externos_mapeamento_desativar&acao_origem='
              . $_GET['acao_origem'] . '&acao_retorno=' . $_GET['acao'] . $strParametros
          );
          $strResultado .= '<a class="desativar" href="' . PaginaSEI::getInstance()->montarAncora($strId) . '" onclick="acaoDesativar(\'' . $strId . '\')"><img src="'
          . PaginaSEI::getInstance()->getIconeDesativar() . '" title="Desativar Mapeamento de Tipos de Processo" alt="Desativar Mapeamento de Tipos de Processo" class="infraImg"></a>';
      }

        $strResultado .= '<a href="#" onclick="onCLickLinkDelete(\''
        . SessaoSEI::getInstance()->assinarLink(
            'controlador.php?acao=pen_map_orgaos_externos_mapeamento_excluir&acao_origem=' . $_GET['acao']
            . '&hdnInfraItensSelecionados=' . $arrObjMapeamentoAssuntoDTO[$i]->getDblId()
            . '&id=' . $idOrgaoExterno
        )
        . '\', this)">'
        . '<img src='
        . ProcessoEletronicoINT::getCaminhoIcone("imagens/excluir.gif")
        . ' title="Excluir Mapeamento" alt="Excluir Mapeamento" class="infraImg">'
        . '</a>';

        $strResultado .= '</td>';

        $strResultado .= '</tr>' . "\n";

        $strAjaxVariaveis .= 'var objAutoCompletarAssunto' . $numIdAssuntoOrigem . ';' . "\n";

        $strAjaxInicializar .= '  objAutoCompletarAssunto' . $numIdAssuntoOrigem . ' = new infraAjaxAutoCompletar(\'hdnIdAssunto' . $numIdAssuntoOrigem . '\',\'txtAssunto' . $numIdAssuntoOrigem . '\', linkAutoCompletar);' . "\n" .
        '  objAutoCompletarAssunto' . $numIdAssuntoOrigem . '.prepararExecucao = function(){' . "\n" .
        '    return \'id_tabela_assuntos=' . '1' . '&palavras_pesquisa=\'+document.getElementById(\'txtAssunto' . $numIdAssuntoOrigem . '\').value;' . "\n" .
        '  }' . "\n" .
        '  objAutoCompletarAssunto' . $numIdAssuntoOrigem . '.processarResultado = function(){' . "\n" .
        '    bolAlteracao = true;' . "\n" .
        '  }' . "\n\n";
    }

      $strResultado .= '</table>';
  }

    $strResultadoImportar = '';

    $strResultadoImportar .= '<table width="99%" class="infraTable" id="tableImportar">' . "\n";
    $strResultadoImportar .= '<caption class="infraCaption" id="tableTotal"></caption>';

    $strResultadoImportar .= '<tr>';
    $strResultadoImportar .= '<th class="infraTh" width="30%">ID</th>' . "\n";
    $strResultadoImportar .= '<th class="infraTh">Tipo de Processo</th>' . "\n";
    $strResultadoImportar .= '</tr>' . "\n";
    $strResultadoImportar .= '</table>';

    $btnImportar = '<button type="button" accesskey="F" id="btnImportar" value="Fechar" onclick="" class="infraButton"><span class="infraTeclaAtalho">I</span>mportar</button>';
    $btnFecharModal = '<button type="button" accesskey="F" id="btnFecharSelecao" value="Fechar" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';
    $btnImportarFinal = '<button type="button" accesskey="F" id="btnImportarFinal" value="Fechar" onclick="" class="infraButton"><span class="infraTeclaAtalho">I</span>mportar</button>';
    $btnFecharModalFinal = '<button type="button" accesskey="F" id="btnFecharSelecaoFinal" value="Fechar" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';
    $arrComandosModal = [$btnImportar, $btnFecharModal];
    $arrComandosModalFinal = [$btnImportarFinal, $btnFecharModalFinal];

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

  .ui-dialog {
    z-index: 1001 !important;
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

  function gerenciar() {

    document.getElementById('frmMapeamentoOrgaosLista').target = '_self';
    document.getElementById('frmMapeamentoOrgaosLista').action = '<?php echo $strLinkGerenciar ?>';
    document.getElementById('frmMapeamentoOrgaosLista').submit();

    infraExibirAviso(false);
  }

  function onClickBtnExcluir() {
    try {
      var len = jQuery('input[name*=chkInfraItem]:checked').length;
      if (len > 0) {
        if (confirm('Confirma a exclusão do(s) mapeamento(s) de tipos de processo?')) {
          var form = jQuery('#frmMapeamentoOrgaosLista');
          form.attr('action', '<?php echo $objSessao->assinarLink('controlador.php?acao=pen_map_orgaos_externos_mapeamento_excluir&acao_origem=' . $acaoOrigem . '&acao_retorno=pen_map_orgaos_externos_mapeamento&id=' . $idOrgaoExterno); ?>');
          form.submit();
        }
      } else {
        alert('Selecione pelo menos um mapeamento para excluir');
      }
    } catch (e) {
      alert('Erro : ' + e.message);
    }
  }

  function onCLickLinkDelete(url, link) {

    var row = jQuery(link).parents('tr:first');

    var strEspecieDocumental = row.find('td:eq(1)').text();
    var strTipoDocumento = row.find('td:eq(2)').text();

    if (confirm('Confirma a exclusão do mapeamento de tipos de processo?')) {

      window.location = url;
    }

  }

  function infraImportarCsv(linkOrgaoId, orgaoId) {
    document.getElementById('mapId').value = orgaoId;
    $('#importArquivoCsv').click();
  }

  function processarDados(csv) {
    const lines = csv.split(/\r\n|\n/);
    const data = [];

    for (let i = 1; i < lines.length; i++) {
      const formatLine = lines[i]
      const lineData = formatLine.toString().split(';');

      if (isNaN(parseInt(lineData[0]))) {
        continue;
      }
      const tipoProcessoId = parseInt(lineData[0]);
      const tipoProcessoNome = lineData[1].replace(/["]/g, '');

      data.push(tipoProcessoId);
      data.push(tipoProcessoNome);

      const td1 = $('<td>', {
          'align': "center"
        })
        .text(tipoProcessoId);
      const td2 = $('<td>', {
          'align': "center"
        })
        .text(tipoProcessoNome);
      $('<tr>')
        //.addClass(strCssTr)
        .append(td1)
        .append(td2)
        .appendTo($('#tableImportar'));
    }

    return data.join(',').replace(/["]/g, '');
  }

  function importarCsv(event, orgaoId) {
    const file = event.target.files[0];

    if (!file) {
      console.error("Nenhum arquivo selecionado.");
      return;
    }

    const reader = new FileReader();

    reader.onload = function(event) {
      const csvContent = event.target.result;
      console.log(event.target.result);
      enviarFormulario(processarDados(csvContent));
    };
    reader.readAsText(file, 'ISO-8859-1');
  }

  function enviarFormulario(data) {
    const dataInput = document.getElementById('dadosInput');
    const orgaoId = document.getElementById('mapId');
    dataInput.value = data;

    $('#btnImportar, #btnImportarFinal').click(function(e) {
      e.preventDefault();
      const form = jQuery('#formImportarDados');
      form.submit();
    })
    $('#btnFecharSelecao, #btnFecharSelecaoFinal').click(function(e) {
      e.preventDefault();
      window.location.reload();
      $('#formImportarDados').dialog('close');
    })

    $('#formImportarDados').dialog({
      'height': 400,
      'width': 600,
      'modal': true,
      'resizable': true,
      'dialogClass': 'no-close success-dialog'
    });
  }

  function acaoDesativar(id) {
    if (confirm("Confirma a desativação do Mapeamento de Tipo de Processo?")) {
      document.getElementById('hdnInfraItemId').value = id;
      document.getElementById('frmMapeamentoOrgaosLista').action = '<?php echo $strLinkDesativar ?>';
      document.getElementById('frmMapeamentoOrgaosLista').submit();
    }
  }

  function onClickBtnDesativar() {
    try {
      var len = jQuery('input[name*=chkInfraItem]:checked').length;
      if (len > 0) {
        if (confirm('Confirma a desativação de ' + len + ' mapeamento(s) de tipos de processo ?')) {
          var form = jQuery('#frmMapeamentoOrgaosLista');
          var acaoReativar = $("<input>").attr({
            type: "hidden",
            name: "hdnAcaoDesativar",
            value: "1"
          });
          form.append(acaoReativar);
          form.attr('action', '<?php print $objSessao->assinarLink('controlador.php?acao=' . PEN_RECURSO_BASE . '_desativar&acao_origem=' . $acaoOrigem . '&acao_retorno=' . PEN_RECURSO_BASE . '_listar' . $strParametros); ?>');
          form.submit();
        }
      } else {
        alert('Selecione pelo menos um mapeamento para desativar');
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
<input style="display: none" type="file" id="importArquivoCsv" encoding="ISO-8859-1" accept=".csv" onchange="importarCsv(event)">
<form id="frmMapeamentoOrgaosLista" method="post" action="<?php echo SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . $acao . '&acao_origem=' . $acao . '&id=' . $idOrgaoExterno) ?>">
  <?php
    PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandos);
    PaginaSEI::getInstance()->abrirAreaDados('17em');
  ?>

  <div style="display:grid; width: 40%;float: left;">
    <label class="infraLabelObrigatorio">Unidade Origem:</label>
    <input type="text" disabled="disabled" name="txtTabelaAssuntosOrigem" readonly="readonly" class="infraText infraReadOnly inputCenter" value=" <?php echo PaginaSEI::tratarHTML($objPenOrgaoExternoDTO->getStrOrgaoOrigem()) ?>" tabindex="<?php echo PaginaSEI::getInstance()->getProxTabDados() ?>" />

    <label class="infraLabelObrigatorio">Unidade Destino:</label>
    <input type="text" disabled="disabled" name="" class="infraText infraReadOnly inputCenter" value=" <?php echo PaginaSEI::tratarHTML($objPenOrgaoExternoDTO->getStrOrgaoDestino()) ?>" tabindex="<?php echo PaginaSEI::getInstance()->getProxTabDados() ?>" />

    <label for="txtPalavrasPesquisaMapeamento" class="infraLabelOpcional">Palavras para Pesquisa:</label>
    <input type="text" id="txtPalavrasPesquisaMapeamentoA" name="txtPalavrasPesquisaMapeamento" value="<?php echo $palavrasPesquisa != null ? $palavrasPesquisa : ''; ?>" class="infraText inputCenter" tabindex="<?php echo PaginaSEI::getInstance()->getProxTabDados() ?>" />

    <div id="divSinAssuntosNaoMapeados" class="infraDivCheckbox" style="padding-top: 10px;">
      <?php $chkSinAssuntosNaoMapeados = $_POST['chkSinAssuntosNaoMapeados']; ?>
      <input type="checkbox" id="chkSinAssuntosNaoMapeados" <?php echo isset($chkSinAssuntosNaoMapeados) ? 'checked' : ''; ?> name="chkSinAssuntosNaoMapeados" class="infraCheckbox" tabindex="<?php echo PaginaSEI::getInstance()->getProxTabDados() ?>" />
      <label id="lblSinAssuntosNaoMapeados" for="chkSinAssuntosNaoMapeados" class="infraLabelCheckbox">Exibir apenas tipos de processo sem mapeamento definido</label>
    </div>
  </div>

  <input type="hidden" name="idOrgaoExterno" value="<?php echo $idOrgaoExterno; ?>" />
  <?
  PaginaSEI::getInstance()->fecharAreaDados();
  PaginaSEI::getInstance()->montarAreaTabela($strResultado, $numRegistros);
  //PaginaSEI::getInstance()->montarAreaDebug();
  PaginaSEI::getInstance()->montarBarraComandosInferior($arrComandosFinal, true);
  ?>
</form>
<form id="formImportarDados" method="post" action="<?php print $objSessao->assinarLink('controlador.php?acao=pen_map_orgaos_externos_mapeamento&acao_origem=' . $acaoOrigem . '&acao_retorno=' . PEN_RECURSO_BASE . '_listar'); ?>" style="display: none;">
  <div id="divInfraBarraLocalizacao2" class="infraBarraLocalizacao" tabindex="450">Pré-visualização da Importação</div>
  <input type="hidden" name="mapId" id="mapId">
  <input type="hidden" name="dados" id="dadosInput">
  <?php $objPagina->abrirAreaDados('5em'); ?>
  <?php PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandosModal); ?>
  <?php $objPagina->fecharAreaDados(); ?>
  <div id="divImportarDados">
    <?php $objPagina->montarAreaTabela($strResultadoImportar, 1); ?>
  </div>
  <?php PaginaSEI::getInstance()->montarBarraComandosInferior($arrComandosModalFinal, true); ?>
</form>
<?php
PaginaSEI::getInstance()->fecharBody();
PaginaSEI::getInstance()->fecharHtml();
?>
