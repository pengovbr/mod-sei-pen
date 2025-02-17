<?php

require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Consulta os logs do estado do procedimento ao ser expedido
 */

session_start();

define('PEN_RECURSO_ATUAL', 'pen_map_orgaos_externos_listar');
define('PEN_RECURSO_BASE', 'pen_map_orgaos_externos');
define('PEN_PAGINA_TITULO', 'Relacionamento entre Unidades');
define('PEN_PAGINA_GET_ID', 'id');


$objPagina = PaginaSEI::getInstance();
$objBanco = BancoSEI::getInstance();
$objSessao = SessaoSEI::getInstance();
$objDebug = InfraDebug::getInstance();

try {

    $objDebug->setBolLigado(false);
    $objDebug->setBolDebugInfra(true);
    $objDebug->limpar();

    $objSessao->validarLink();
    $objSessao->validarPermissao(PEN_RECURSO_ATUAL);


    //--------------------------------------------------------------------------
    // Ações
  if (array_key_exists('acao', $_GET)) {

      $arrParam = array_merge($_GET, $_POST);

    switch ($_GET['acao']) {

      case 'pen_map_orgaos_externos_excluir':
        if (array_key_exists('hdnInfraItensSelecionados', $arrParam) && !empty($arrParam['hdnInfraItensSelecionados'])) {

            $objPenOrgaoExternoDTO = new PenOrgaoExternoDTO();
            $objPenOrgaoExternoRN = new PenOrgaoExternoRN();
            $objMapeamentoTipoProcedimentoDTO = new PenMapTipoProcedimentoDTO();
            $objMapeamentoTipoProcedimentoDTO->retNumIdMapOrgao();
            $objMapeamentoTipoProcedimentoRN = new PenMapTipoProcedimentoRN();

            $arrParam['hdnInfraItensSelecionados'] = explode(',', $arrParam['hdnInfraItensSelecionados']);

          if (is_array($arrParam['hdnInfraItensSelecionados'])) {
            foreach ($arrParam['hdnInfraItensSelecionados'] as $arr) {
                $dblId = explode(";", $arr)[0];

                $objMapeamentoTipoProcedimentoDTO->setNumIdMapOrgao($dblId);
              if ($objMapeamentoTipoProcedimentoRN->contar($objMapeamentoTipoProcedimentoDTO)) {
                $mensagem = "Relacionamento entre unidades possuí tipos de processo mapeados. Remova os tipos de processo para realizar a exclusão do relacionamento.";
                $objPagina->adicionarMensagem($mensagem, InfraPagina::$TIPO_MSG_ERRO);
                header(
                  'Location: ' . SessaoSEI::getInstance()->assinarLink(
                      'controlador.php?acao='
                      . $_GET['acao_retorno'] . '&acao_origem=' . $_GET['acao_origem']
                  )
                );
                exit(0);
              }

                $objPenOrgaoExternoDTO->setDblId($dblId);
                $objPenOrgaoExternoRN->excluir($objPenOrgaoExternoDTO);
            }
          } else {
              $objMapeamentoTipoProcedimentoDTO->setNumIdMapOrgao($arrParam['hdnInfraItensSelecionados']);
            if ($objMapeamentoTipoProcedimentoRN->contar($objMapeamentoTipoProcedimentoDTO)) {
                  $mensagem = "Relacionamento entre unidades possuí tipos de processo mapeados. Remova os tipos de processo para realizar a exclusão do relacionamento.";
                  $objPagina->adicionarMensagem($mensagem, InfraPagina::$TIPO_MSG_ERRO);
                  header(
                      'Location: ' . SessaoSEI::getInstance()->assinarLink(
                          'controlador.php?acao='
                          . $_GET['acao_retorno'] . '&acao_origem=' . $_GET['acao_origem']
                      )
                  );
                  exit(0);
            }

              $objPenOrgaoExternoDTO->setDblId($arrParam['hdnInfraItensSelecionados']);
              $objPenOrgaoExternoRN->excluir($objPenOrgaoExternoDTO);
          }

            $objPagina->adicionarMensagem('Relacionamento entre unidades foi excluído com sucesso.', 5);

            header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . $_GET['acao_retorno'] . '&acao_origem=' . $_GET['acao_origem']));
            exit(0);
        } else {
            throw new InfraException('Módulo do Tramita: Nenhum Registro foi selecionado para executar esta ação');
        }

      case 'pen_map_orgaos_externos_listar':
          // Ação padrão desta tela
          break;
      case 'pen_map_orgaos_importar_tipos_processos':
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
          }
            $objPagina->adicionarMensagem('Importação realizada com sucesso.', 5);
            header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . $_GET['acao_retorno'] . '&acao_origem=' . $_GET['acao_origem']));
            exit(0);
        } catch (Exception $e) {
            throw new InfraException($e->getMessage());
        }

      case 'pen_map_orgaos_externos_reativar':
        if ((isset($_POST['hdnInfraItensSelecionados']) && !empty($_POST['hdnInfraItensSelecionados'])) && isset($_POST['hdnAcaoReativar'])) {
            $arrHdnInInfraItensSelecionados = explode(",", $_POST['hdnInfraItensSelecionados']);
          foreach ($arrHdnInInfraItensSelecionados as $arr) {
              $id = explode(";", $arr)[0];
              $objPenOrgaoExternoDTO = new PenOrgaoExternoDTO();
              $objPenOrgaoExternoDTO->setDblId($id);
              $objPenOrgaoExternoDTO->setStrAtivo('S');

              $objPenOrgaoExternoRN = new PenOrgaoExternoRN();
              $objPenOrgaoExternoRN->alterar($objPenOrgaoExternoDTO);
          }
            $objPagina->adicionarMensagem(sprintf('%s foi reativado com sucesso.', PEN_PAGINA_TITULO), 5);
        }

        if (isset($_POST['hdnInfraItemId']) && is_numeric($_POST['hdnInfraItemId'])) {
            $objPenOrgaoExternoDTO = new PenOrgaoExternoDTO();
            $objPenOrgaoExternoDTO->setDblId($_POST['hdnInfraItemId']);
            $objPenOrgaoExternoDTO->setStrAtivo('S');

            $objPenOrgaoExternoRN = new PenOrgaoExternoRN();
            $objPenOrgaoExternoRN->alterar($objPenOrgaoExternoDTO);
            $objPagina->adicionarMensagem(sprintf('%s foi reativado com sucesso.', PEN_PAGINA_TITULO), 5);
        }
          break;

      case 'pen_map_orgaos_externos_desativar':
        if ((isset($_POST['hdnInfraItensSelecionados']) && !empty($_POST['hdnInfraItensSelecionados'])) && isset($_POST['hdnAcaoDesativar'])) {
            $btnDesativar = null;
            $arrHdnInInfraItensSelecionados = explode(",", $_POST['hdnInfraItensSelecionados']);
          foreach ($arrHdnInInfraItensSelecionados as $arr) {
            $id = explode(";", $arr)[0];
            $objPenOrgaoExternoDTO = new PenOrgaoExternoDTO();
            $objPenOrgaoExternoDTO->setDblId($id);
            $objPenOrgaoExternoDTO->setStrAtivo('N');

            $objPenOrgaoExternoRN = new PenOrgaoExternoRN();
            $objPenOrgaoExternoRN->alterar($objPenOrgaoExternoDTO);
          }
            $objPagina->adicionarMensagem(sprintf('%s foi desativado com sucesso.', PEN_PAGINA_TITULO), InfraPagina::$TIPO_MSG_AVISO);
        }

        if (isset($_POST['hdnInfraItemId']) && is_numeric($_POST['hdnInfraItemId'])) {
            $objPenOrgaoExternoDTO = new PenOrgaoExternoDTO();
            $objPenOrgaoExternoDTO->setDblId($_POST['hdnInfraItemId']);
            $objPenOrgaoExternoDTO->setStrAtivo('N');

            $objPenOrgaoExternoRN = new PenOrgaoExternoRN();
            $objPenOrgaoExternoRN->alterar($objPenOrgaoExternoDTO);
            $objPagina->adicionarMensagem(sprintf('%s foi desativado com sucesso.', PEN_PAGINA_TITULO), 5);
        }
          break;

      default:
          throw new InfraException('Módulo do Tramita: Ação não permitida nesta tela');
    }
  }
    //--------------------------------------------------------------------------

    $acao = $_GET['acao'];
    $acaoOrigem = $_GET['acao_origem'];
    $acaoRetorno = $_GET['acao_retorno'];

    // DTO de paginao
    $objPenOrgaoExternoDTO = new PenOrgaoExternoDTO();
    $objPenOrgaoExternoDTO->setStrAtivo('S');
    $objPenOrgaoExternoDTO->retDblId();
    $objPenOrgaoExternoDTO->retNumIdOrgaoOrigem();
    $objPenOrgaoExternoDTO->retStrOrgaoOrigem();
    $objPenOrgaoExternoDTO->retNumIdOrgaoDestino();
    $objPenOrgaoExternoDTO->retStrOrgaoDestino();
    $objPenOrgaoExternoDTO->retStrAtivo();

    //--------------------------------------------------------------------------
    // Filtragem
  if (array_key_exists('txtSiglaOrigem', $_POST) && ((!empty($_POST['txtSiglaOrigem']) && $_POST['txtSiglaOrigem'] !== 'null') || $_POST['txtSiglaOrigem'] == "0")) {
      $objPenOrgaoExternoDTO->setStrOrgaoOrigem('%' . $_POST['txtSiglaOrigem'] . '%', InfraDTO::$OPER_LIKE);
  }

  if (array_key_exists('txtSiglaDestino', $_POST) && ((!empty($_POST['txtSiglaDestino']) && $_POST['txtSiglaDestino'] !== 'null') || $_POST['txtSiglaDestino'] == "0")) {
      $objPenOrgaoExternoDTO->setStrOrgaoDestino('%' . $_POST['txtSiglaDestino'] . '%', InfraDTO::$OPER_LIKE);
  }

  if (array_key_exists('txtEstado', $_POST) && (!empty($_POST['txtEstado']) && $_POST['txtEstado'] !== 'null')) {
      $objPenOrgaoExternoDTO->setStrAtivo($_POST['txtEstado']);
  }

    //--------------------------------------------------------------------------

    $btnReativarAdicionado = 'N';
    $btnDesativarAdicionado = 'N';
    $btnReativar = '';
    $btnDesativar = null;
    $btnPesquisar = '<button type="button" accesskey="P" onclick="onClickBtnPesquisar();" id="btnPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';
    $btnTipoProcessoPadrao = '<button type="button" value="Atribuir Tipo de Processo Padrão" id="btnTipoProcessoPadrao"  onclick="location.href=\''
    . PaginaSEI::getInstance()->formatarXHTML(
        SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_tipo_processo_padrao&acao_origem=' . $_GET['acao'])
    )
    . '\';" class="infraButton"><span class="infraTeclaAtalho">A</span>tribuir Tipo de Processo Padrão</button>';
    $btnNovo = '<button type="button" value="Novo" id="btnNovo" onclick="onClickBtnNovo()" class="infraButton"><span class="infraTeclaAtalho">N</span>ovo</button>';
  if (empty($_POST['txtEstado']) || is_null($_POST['txtEstado']) || $_POST['txtEstado'] == 'S') {
      $btnDesativar = '<button type="button" value="Desativar" id="btnDesativar" onclick="onClickBtnDesativar()" class="infraButton"><span class="infraTeclaAtalho">D</span>esativar</button>';
      $btnDesativarAdicionado = 'S';
  }

  if (!empty($_POST['txtEstado']) && $_POST['txtEstado'] == 'N') {
      $btnReativar = '<button type="button" id="btnReativar" value="Reativar" onclick="onClickBtnReativar()" class="infraButton btnReativar"><span class="infraTeclaAtalho">R</span>eativar</button>';
      $btnReativarAdicionado = 'S';
  }

    $btnExcluir = '<button type="button" value="Excluir" id="btnExcluir" onclick="onClickBtnExcluir()" class="infraButton"><span class="infraTeclaAtalho">E</span>xcluir</button>';
    $btnImprimir = '<button type="button" accesskey="I" id="btnImprimir" value="Imprimir" onclick="infraImprimirTabela();" class="infraButton"><span class="infraTeclaAtalho">I</span>mprimir</button>';
    $btnFechar = '<button type="button" id="btnCancelar" value="Fechar" onclick="location.href=\''
    . PaginaSEI::getInstance()->formatarXHTML(SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_parametros_configuracao&acao_origem=' . $_GET['acao']))
    . '\';" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';
    $btnDesativarReativar = null;

  if ($btnDesativarAdicionado == 'S') {
      $btnDesativarReativar = $btnDesativar;
  } elseif ($btnReativarAdicionado == 'S') {
      $btnDesativarReativar = $btnReativar;
  }
    $arrComandos = [$btnPesquisar, $btnTipoProcessoPadrao, $btnNovo, $btnDesativarReativar, $btnExcluir, $btnImprimir, $btnFechar];
    $arrComandosFinal = [$btnNovo, $btnDesativarReativar, $btnExcluir, $btnImprimir, $btnFechar];

    //--------------------------------------------------------------------------

    $objPagina->prepararOrdenacao($objPenOrgaoExternoDTO, 'Id', InfraDTO::$TIPO_ORDENACAO_ASC);
    $objPagina->prepararPaginacao($objPenOrgaoExternoDTO);

    $objPenOrgaoExternoRN = new PenOrgaoExternoRN();
    $respObjPenOrgaoExternoDTO = $objPenOrgaoExternoRN->listar($objPenOrgaoExternoDTO);

    $objPagina->processarPaginacao($objPenOrgaoExternoDTO);

    $numRegistros = count($respObjPenOrgaoExternoDTO);
  if (!empty($respObjPenOrgaoExternoDTO)) {

      $strResultado = '';

      $strResultado .= '<table width="99%" class="infraTable">' . "\n";
      $strResultado .= '<caption class="infraCaption">' . $objPagina->gerarCaptionTabela(PEN_PAGINA_TITULO, $numRegistros) . '</caption>';

      $strResultado .= '<tr>';
      $strResultado .= '<th class="infraTh" width="1%">' . $objPagina->getThCheck() . '</th>' . "\n";
      $strResultado .= '<th class="infraTh" width="12%">' . $objPagina->getThOrdenacao($objPenOrgaoExternoDTO, 'ID <br><small>Origem</small>', 'IdOrgaoOrigem', $respObjPenOrgaoExternoDTO) . '</th>' . "\n";
      $strResultado .= '<th class="infraTh" width="25%">' . $objPagina->getThOrdenacao($objPenOrgaoExternoDTO, 'Unidade Origem', 'OrgaoOrigem', $respObjPenOrgaoExternoDTO) . '</th>' . "\n";
      $strResultado .= '<th class="infraTh" width="12%" style="text-align: center !important;">' . $objPagina->getThOrdenacao($objPenOrgaoExternoDTO, 'ID <br><small>Destino</small>', 'IdOrgaoDestino', $respObjPenOrgaoExternoDTO) . '</th>' . "\n";
      $strResultado .= '<th class="infraTh" width="25%">' . $objPagina->getThOrdenacao($objPenOrgaoExternoDTO, 'Unidade Destino', 'OrgaoDestino', $respObjPenOrgaoExternoDTO) . '</th>' . "\n";
      $strResultado .= '<th class="infraTh" width="15%">Ações</th>' . "\n";
      $strResultado .= '</tr>' . "\n";
      $strCssTr = '';

      $index = 0;
    foreach ($respObjPenOrgaoExternoDTO as $objPenOrgaoExternoDTO) {
        $strCssTr = ($strCssTr == 'infraTrClara') ? 'infraTrEscura' : 'infraTrClara';

        $strResultado .= '<tr class="' . $strCssTr . '">';
        $strResultado .= '<td align="center">' . $objPagina->getTrCheck($index, $objPenOrgaoExternoDTO->getDblId() . ';' . $objPenOrgaoExternoDTO->getStrAtivo(), '') . '</td>';
        $strResultado .= '<td align="center">' . $objPenOrgaoExternoDTO->getNumIdOrgaoOrigem() . '</td>';
        $strResultado .= '<td align="center">' . $objPenOrgaoExternoDTO->getStrOrgaoOrigem() . '</td>';
        $strResultado .= '<td align="center">' . $objPenOrgaoExternoDTO->getNumIdOrgaoDestino() . '</td>';
        $strResultado .= '<td align="center">' . $objPenOrgaoExternoDTO->getStrOrgaoDestino() . '</td>';
        $strResultado .= '<td align="center">';

        $strResultado .= '<a href="'
        . $objSessao->assinarLink(
            'controlador.php?acao=' . PEN_RECURSO_BASE
            . '_visualizar&acao_origem=' . $_GET['acao_origem']
            . '&acao_retorno=' . $_GET['acao'] . '&id=' . $objPenOrgaoExternoDTO->getDblId()
        ) . '"><img src='
        . ProcessoEletronicoINT::getCaminhoIcone("imagens/consultar.gif")
        . ' title="Consultar Mapeamento Entre Unidades" alt="Consultar Mapeamento Entre Unidades" class="infraImg"></a>';

      if ($objSessao->verificarPermissao('pen_map_orgaos_externos_atualizar')) {
        $strResultado .= '<a href="'
        . $objSessao->assinarLink(
            'controlador.php?acao=' . PEN_RECURSO_BASE
            . '_atualizar&acao_origem=' . $_GET['acao_origem'] . '&acao_retorno=' . $_GET['acao']
            . '&' . PEN_PAGINA_GET_ID . '=' . $objPenOrgaoExternoDTO->getDblId()
        ) . '"><img src='
        . ProcessoEletronicoINT::getCaminhoIcone("imagens/alterar.gif")
        . ' title="Alterar Relacionamento" alt="Alterar Relacionamento Entre Unidades" class="infraImg"></a>';
      }

        $objMapeamentoTipoProcedimentoDTO = new PenMapTipoProcedimentoDTO();
        $objMapeamentoTipoProcedimentoDTO->setNumIdMapOrgao($objPenOrgaoExternoDTO->getDblId());
        $objMapeamentoTipoProcedimentoDTO->retNumIdMapOrgao();
        $objMapeamentoTipoProcedimentoDTO->retStrAtivo();

        $objMapeamentoTipoProcedimentoRN = new PenMapTipoProcedimentoRN();
        $arrPenOrgaoExternoDTO = $objMapeamentoTipoProcedimentoRN->listar($objMapeamentoTipoProcedimentoDTO);

        $strResultado .= ' <input type="hidden" id="dblId_' . $objPenOrgaoExternoDTO->getDblId() . '" name="dblId_' . $objPenOrgaoExternoDTO->getDblId() . '" value="' . $objPenOrgaoExternoDTO->getDblId() . '" />';

      if ($arrPenOrgaoExternoDTO == null) {
        $strResultado .= '<a href="#" id="importarCsvButton" onclick="infraImportarCsv('
        . "'" . $objSessao->assinarLink('controlador.php?acao=pen_map_orgaos_externos_tipo_processo_listar&tipo_pesquisa=1&id_object=objInfraTableToTable&idMapOrgao=' . $objPenOrgaoExternoDTO->getDblId())
        . "'," . $objPenOrgaoExternoDTO->getDblId() . ')">'
        . '<img src='
        . ProcessoEletronicoINT::getCaminhoIcone("/importar.svg", $this->getDiretorioImagens())
        . ' title="Importar CSV" alt="Importar CSV" style="margin-bottom: 2.5px; width: 20px;">'
        . '</a>';
      } else {
          $strResultado .= '<a href="'
          . $objSessao->assinarLink(
              'controlador.php?acao=' . PEN_RECURSO_BASE
              . '_mapeamento&acao_origem=' . $_GET['acao_origem']
              . '&acao_retorno=' . $_GET['acao'] . '&id=' . $objPenOrgaoExternoDTO->getDblId()
          ) . '"><img src='
          . ProcessoEletronicoINT::getCaminhoIcone("svg/arquivo_mapeamento_assunto.svg")
          . ' title="Mapear tipos de processos" alt="Mapear tipos de processos" class="infraImg"></a>';
      }

      if ($objSessao->verificarPermissao('pen_map_orgaos_externos_reativar') && $objPenOrgaoExternoDTO->getStrAtivo() == 'N') {
          $strLinkReativar = $objSessao->assinarLink('controlador.php?acao=pen_map_orgaos_externos_reativar&acao_origem=' . $_GET['acao_origem'] . '&acao_retorno=' . $_GET['acao'] . '&' . PEN_PAGINA_GET_ID . '=' . $objPenOrgaoExternoDTO->getDblId());
          $strId = $objPenOrgaoExternoDTO->getDblId();
          $strResultado .= '<a class="reativar" href="' . PaginaSEI::getInstance()->montarAncora($strId) . '" onclick="acaoReativar(\'' . $strId . '\')"><img src="' . PaginaSEI::getInstance()->getIconeReativar() . '" title="Reativar Relacionamento entre Unidades" alt="Reativar Relacionamento entre Unidades" class="infraImg"></a>';
      }

      if ($objSessao->verificarPermissao('pen_map_orgaos_externos_desativar') && $objPenOrgaoExternoDTO->getStrAtivo() == 'S') {
          $strLinkDesativar = $objSessao->assinarLink('controlador.php?acao=pen_map_orgaos_externos_desativar&acao_origem=' . $_GET['acao_origem'] . '&acao_retorno=' . $_GET['acao'] . '&' . PEN_PAGINA_GET_ID . '=' . $objPenOrgaoExternoDTO->getDblId());
          $strId = $objPenOrgaoExternoDTO->getDblId();
          $strResultado .= '<a class="desativar" href="' . PaginaSEI::getInstance()->montarAncora($strId) . '" onclick="acaoDesativar(\'' . $strId . '\')"><img src="'
          . PaginaSEI::getInstance()->getIconeDesativar() . '" title="Desativar Relacionamento entre Unidades" alt="Desativar Relacionamento entre Unidades" class="infraImg"></a>';
      }

      if ($objSessao->verificarPermissao('pen_map_orgaos_externos_excluir') && $arrPenOrgaoExternoDTO == null) {
          $strResultado .= '<a href="#" onclick="onCLickLinkDelete(\''
          . $objSessao->assinarLink(
              'controlador.php?acao=pen_map_orgaos_externos_excluir&acao_origem='
              . $_GET['acao_origem']
              . '&acao_retorno=' . $_GET['acao']
              . '&hdnInfraItensSelecionados=' . $objPenOrgaoExternoDTO->getDblId()
          )
          . '\', this)">'
          . '<img src='
          . ProcessoEletronicoINT::getCaminhoIcone("imagens/excluir.gif")
          . ' title="Excluir Mapeamento" alt="Excluir Mapeamento" class="infraImg">'
          . '</a>';
      }
        $strResultado .= '</td>';
        $strResultado .= '</tr>';

        $index++;
    }
      $strResultado .= '</table>';

      $strResultadoImportar = '';

      $strResultadoImportar .= '<table width="99%" class="infraTable" id="tableImportar">' . "\n";
      $strResultadoImportar .= '<caption class="infraCaption" id="tableTotal"></caption>';

      $strResultadoImportar .= '<tr>';
      $strResultadoImportar .= '<th class="infraTh" width="30%">ID</th>' . "\n";
      $strResultadoImportar .= '<th class="infraTh">Tipo de Processo</th>' . "\n";
      $strResultadoImportar .= '</tr>' . "\n";
      $strResultadoImportar .= '</table>';
  }

    $btnImportar = '<button type="button" accesskey="F" id="btnImportar" value="Fechar" onclick="" class="infraButton"><span class="infraTeclaAtalho">I</span>mportar</button>';
    $btnFecharModal = '<button type="button" accesskey="F" id="btnFecharSelecao" value="Fechar" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';
    $arrComandosModal = [$btnImportar, $btnFecharModal];
    $arrComandosModalFinal = [$btnImportar, $btnFecharModal];
} catch (InfraException $e) {
    $objPagina->processarExcecao($e);
}


$objPagina->montarDocType();
$objPagina->abrirHtml();
$objPagina->abrirHead();
$objPagina->montarMeta();
$objPagina->montarTitle(':: ' . $objPagina->getStrNomeSistema() . ' - ' . PEN_PAGINA_TITULO . ' ::');
$objPagina->montarStyle();
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

  /* fim */

  .lblSiglaOrigem {
    position: absolute;
    left: 0%;
    top: 0%;
    width: 25%;
  }

  #txtSiglaOrigem {
    position: absolute;
    left: 0%;
    top: 50%;
    width: 25%
  }

  #lblSiglaDestino {
    position: absolute;
    left: 26%;
    top: 0%;
    width: 25%;
  }

  #txtSiglaDestino {
    position: absolute;
    left: 26%;
    top: 50%;
    width: 25%;
  }

  #lblEstado {
    position: absolute;
    left: 52%;
    top: 0%;
    width: 25%;
  }

  #txtEstado {
    position: absolute;
    left: 52%;
    top: 50%;
    width: 25%;
  }

  #txtEstadoSelect {
    position: absolute;
    left: 52%;
    top: 50%;
    width: 25%;
  }

  .ui-dialog {
    z-index: 1001 !important;
  }

  .infraDivRotuloOrdenacao {
    text-align: center !important;
  }
</style>
<?php $objPagina->montarJavaScript(); ?>
<script type="text/javascript">
  var objInfraTableToTable = null;

  function inicializar() {
    infraEfeitoTabelas();
  }

  function onClickBtnPesquisar() {
    document.getElementById('frmAcompanharEstadoProcesso').action = '<?php print $objSessao->assinarLink('controlador.php?acao=' . $acao . '&acao_origem=' . $acaoOrigem . '&acao_retorno=' . $acaoRetorno); ?>';
    document.getElementById('frmAcompanharEstadoProcesso').submit();
  }

  function tratarEnter(ev) {
    var key = infraGetCodigoTecla(ev);
    if (key == 13) {
      onClickBtnPesquisar();
    }
    return true;
  }

  function onCLickLinkDelete(url, link) {

    var row = jQuery(link).parents('tr:first');

    var strEspecieDocumental = row.find('td:eq(1)').text();
    var strTipoDocumento = row.find('td:eq(2)').text();

    if (confirm('Confirma a exclusão do relacionamento entre unidades?')) {

      window.location = url;
    }

  }

  function onClickBtnNovo() {

    window.location = '<?php print $objSessao->assinarLink('controlador.php?acao=' . PEN_RECURSO_BASE . '_cadastrar&acao_origem=' . $acaoOrigem . '&acao_retorno=' . $_acaoOrigem); ?>';
  }

  function onClickBtnDesativar() {

    try {
      var len = jQuery('input[name*=chkInfraItem]:checked').length;
      if (len > 0) {
        if (confirm('Confirma a desativação de ' + len + ' relacionamento(s) entre unidades ?')) {
          var form = jQuery('#frmAcompanharEstadoProcesso');
          var acaoReativar = $("<input>").attr({
            type: "hidden",
            name: "hdnAcaoDesativar",
            value: "1"
          });
          form.append(acaoReativar);
          form.attr('action', '<?php print $objSessao->assinarLink('controlador.php?acao=' . PEN_RECURSO_BASE . '_desativar&acao_origem=' . $acaoOrigem . '&acao_retorno=' . PEN_RECURSO_BASE . '_listar'); ?>');
          form.submit();
        }
      } else {
        alert('Selecione pelo menos um relacionamento para desativar');
      }
    } catch (e) {
      alert('Erro : ' + e.message);
    }
  }

  function acaoDesativar(id) {
    if (confirm("Confirma a desativação do relacionamento entre unidades?")) {
      document.getElementById('hdnInfraItemId').value = id;
      document.getElementById('frmAcompanharEstadoProcesso').action = '<?php echo $strLinkDesativar ?>';
      document.getElementById('frmAcompanharEstadoProcesso').submit();
    }
  }

  function onClickBtnExcluir() {

    try {
      var len = jQuery('input[name*=chkInfraItem]:checked').length;
      if (len > 0) {
        if (confirm('Confirma a exclusão do relacionamento entre unidades?')) {
          var form = jQuery('#frmAcompanharEstadoProcesso');
          form.attr('action', '<?php print $objSessao->assinarLink('controlador.php?acao=' . PEN_RECURSO_BASE . '_excluir&acao_origem=' . $acaoOrigem . '&acao_retorno=' . PEN_RECURSO_BASE . '_listar'); ?>');
          form.submit();
        }
      } else {
        alert('Selecione pelo menos um mapeamento para excluir');
      }
    } catch (e) {
      alert('Erro : ' + e.message);
    }
  }

  function acaoReativar(id) {

    if (confirm("Confirma a reativação do relacionamento entre unidades?")) {
      document.getElementById('hdnInfraItemId').value = id;
      document.getElementById('frmAcompanharEstadoProcesso').action = '<?php echo $strLinkReativar ?>';
      document.getElementById('frmAcompanharEstadoProcesso').submit();
    }
  }

  function onClickBtnReativar() {
    try {
      var len = jQuery('input[name*=chkInfraItem]:checked').length;
      if (len > 0) {
        if (confirm('Confirma a reativação de ' + len + ' relacionamento(s) entre unidades ?')) {
          var form = jQuery('#frmAcompanharEstadoProcesso');
          var acaoReativar = $("<input>").attr({
            type: "hidden",
            name: "hdnAcaoReativar",
            value: "1"
          });
          form.append(acaoReativar);
          form.attr('action', '<?php print $objSessao->assinarLink('controlador.php?acao=' . PEN_RECURSO_BASE . '_reativar&acao_origem=' . $acaoOrigem . '&acao_retorno=' . PEN_RECURSO_BASE . '_listar'); ?>');
          form.submit();
        }
      } else {
        alert('Selecione pelo menos um relacionamento para reativar');
      }
    } catch (e) {
      alert('Erro : ' + e.message);
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
</script>
<?php
$objPagina->fecharHead();
$objPagina->abrirBody(PEN_PAGINA_TITULO, 'onload="inicializar();"');
?>
<input style="display: none" type="file" id="importArquivoCsv" encoding="ISO-8859-1" accept=".csv" onchange="importarCsv(event)">
<form id="frmAcompanharEstadoProcesso" method="post" action="">
  <?php $objPagina->montarBarraComandosSuperior($arrComandos); ?>
  <?php $objPagina->abrirAreaDados('8em'); ?>

  <?php
    $txtSiglaOrigem = $_POST['txtSiglaOrigem'] ?? '';
    $txtSiglaDestino = $_POST['txtSiglaDestino'] ?? '';
    $txtEstado = isset($_POST['txtEstado']) && $_POST['txtEstado'] != "S" ? 'selected="selected"' : '';
    $idTxtEstado = $_POST['txtEstado'] ?? '';
  ?>
  <label for="txtSiglaOrigem" id="lblSiglaOrigem" class="lblSigla infraLabelOpcional">Unidade Origem:</label>
  <input type="text" id="txtSiglaOrigem" name="txtSiglaOrigem" class="infraText" value="<?php echo PaginaSEI::tratarHTML($txtSiglaOrigem); ?>" />

  <label for="txtSiglaDestino" id="lblSiglaDestino" class="lblSigla infraLabelOpcional">Unidade Destino:</label>
  <input type="text" id="txtSiglaDestino" name="txtSiglaDestino" class="infraText" value="<?php echo PaginaSEI::tratarHTML($txtSiglaDestino); ?>" />

  <label for="txtEstado" id="lblEstado" class="infraLabelOpcional">Estado:</label>
  <input type="hidden" id="txtEstado" name="txtEstado" class="infraText" value="<?php echo PaginaSEI::tratarHTML($idTxtEstado); ?>" />
  <select id="txtEstadoSelect" name="txtEstado" onchange="this.form.submit();" class="infraSelect" tabindex="<?php echo PaginaSEI::getInstance()->getProxTabDados() ?>">
    <option value="S" <?php echo PaginaSEI::tratarHTML($txtEstado); ?>>Ativo</option>
    <option value="N" <?php echo PaginaSEI::tratarHTML($txtEstado); ?>>Inativo</option>
  </select>
  <input type="hidden" id="tableTipoProcessos" name="tableTipoProcessos" value="" />

  <?php $objPagina->fecharAreaDados(); ?>
  <?php if ($numRegistros > 0) { ?>
        <?php PaginaSEI::getInstance()->montarAreaTabela($strResultado, $numRegistros); ?>
        <?php PaginaSEI::getInstance()->montarBarraComandosInferior($arrComandosFinal, true); ?>
  <?php } ?>
</form>
<form id="formImportarDados" method="post" action="<?php print $objSessao->assinarLink('controlador.php?acao=pen_map_orgaos_importar_tipos_processos&acao_origem=' . $acaoOrigem . '&acao_retorno=' . PEN_RECURSO_BASE . '_listar'); ?>" style="display: none;">
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
<?php $objPagina->fecharBody(); ?>
<?php $objPagina->fecharHtml(); ?>
