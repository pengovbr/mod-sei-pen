<?php

require_once DIR_SEI_WEB . '/SEI.php';

try {

  session_start();

  $objPaginaSEI = PaginaSEI::getInstance();
  $objSessaoSEI = SessaoSEI::getInstance();

  // $objSessaoSEI->validarLink();

  $strActionPadrao = SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao'].'&acao_origem='.$_GET['acao'].'&id_documento='.$_GET['id_documento']);
  PaginaSEI::getInstance()->salvarCamposPost(array('txtPalavrasPesquisaBloco', 'chakSinEstadoGerado', 'selUnidadeGeradora', 'hdnMeusBlocos'));


  $strTitulo = 'Blocos de Trâmite Externo';

  switch ($_GET['acao']) {
    case 'md_pen_tramita_em_bloco_excluir':
      try {
        $arrStrIds = PaginaSEI::getInstance()->getArrStrItensSelecionados();
        $arrObjTramiteEmBlocoDTO = array();
        $arrIds = array();

        if (count($arrStrIds) > 0) {
          for ($i = 0; $i < count($arrStrIds); $i++) {
            $arrIds[] = $arrStrIds[$i];
          }
        } elseif (isset($_GET['hdnInfraItensSelecionados'])) {
          $arrIds[] = intval($_GET['hdnInfraItensSelecionados']);
        }
        $dto = new TramiteEmBlocoDTO();
        $dto->setNumId($arrIds, InfraDTO::$OPER_IN);
        $dto->setStrStaEstado(TramiteEmBlocoRN::$TE_ABERTO);
        $dto->retNumId();

        $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
        $arrTramiteEmBloco = $objTramiteEmBlocoRN->listar($dto);

        if ($arrTramiteEmBloco == null) {
          $objPaginaSEI->adicionarMensagem('Blocos que não estão no estado "aberto" não podem ser excluídos.', InfraPagina::$TIPO_MSG_ERRO);
          header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao']));
          exit(0);
        }

        $objTramiteEmBlocoRN->excluir($arrTramiteEmBloco);

        $tramitaEmBlocoProtocoloDTO = new TramitaEmBlocoProtocoloDTO();
        $tramitaEmBlocoProtocoloDTO->setNumIdTramitaEmBloco($arrIds, InfraDTO::$OPER_IN);
        $tramitaEmBlocoProtocoloDTO->retNumIdTramitaEmBloco();
        $tramitaEmBlocoProtocoloDTO->retNumId();
        $tramitaEmBlocoProtocoloDTO->retDblIdProtocolo();

        $tramitaEmBlocoProtocoloRN = new TramitaEmBlocoProtocoloRN();
        $arrtramitaEmBlocoProtocoloRN = $tramitaEmBlocoProtocoloRN->listar($tramitaEmBlocoProtocoloDTO);
        $tramitaEmBlocoProtocoloRN->excluir($arrtramitaEmBlocoProtocoloRN);

        $objPaginaSEI->adicionarMensagem('Bloco excluído com sucesso!', 5);

      } catch (Exception $e) {
        PaginaSEI::getInstance()->processarExcecao($e);
      }
      header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao']));
      exit(0);
    case 'md_pen_tramita_em_bloco':
      $arrEstadosSelecionados = [];
      $checkboxesEstados = [
          'chkSinEstadoGerado' => TramiteEmBlocoRN::$TE_ABERTO,
          'chkSinEstadoDisponibilizado' => TramiteEmBlocoRN::$TE_DISPONIBILIZADO,
          'chkSinEstadoConcluidoParcialmente' => TramiteEmBlocoRN::$TE_CONCLUIDO_PARCIALMENTE,
          'chkSinEstadoConcluido' => TramiteEmBlocoRN::$TE_CONCLUIDO
      ];

      foreach ($checkboxesEstados as $checkbox => $strEstado) {
          if (isset($_POST[$checkbox])) {
              $arrEstadosSelecionados[] = $strEstado;
          }
      }
      $strPalavrasPesquisa = PaginaSEI::getInstance()->recuperarCampo('txtPalavrasPesquisa');
      $setStrPalavrasPesquisa = $strPalavrasPesquisa != '' ? $objBlocoDTOPesquisa->setStrPalavrasPesquisa($strPalavrasPesquisa) : '';

      break;
    case 'pen_tramite_em_bloco_cancelar':
      $arrEstadosSelecionados = [];
      $arrStrIds = isset($_GET['id_tramita_em_bloco']) ? [$_GET['id_tramita_em_bloco']] : PaginaSEI::getInstance()->getArrStrItensSelecionados();
      if (count($arrStrIds) > 0) {
        $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
        $objTramiteEmBlocoRN->cancelar($arrStrIds);

        $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
        $objTramiteEmBlocoDTO->setNumId($_GET['id_tramita_em_bloco']);
        $objTramiteEmBlocoDTO->setStrStaEstado(TramiteEmBlocoRN::$TE_ABERTO);

        $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
        $objTramiteEmBlocoRN->alterar($objTramiteEmBlocoDTO);
      }

      PaginaSEI::getInstance()->setStrMensagem('Operação realizada com sucesso.');
      header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao']));

      break;
    default:
      throw new InfraException("Ação '" . $_GET['acao'] . "' não reconhecida.");
  }


  $objFiltroDTO = new TramiteEmBlocoDTO();
  $objFiltroDTO->retNumId();
  $objFiltroDTO->retStrStaEstado();
  $objFiltroDTO->retStrDescricao();
  $objFiltroDTO->setStrPalavrasPesquisa($setStrPalavrasPesquisa);


  if (count($arrEstadosSelecionados)) {
    $objFiltroDTO->setStrStaEstado($arrEstadosSelecionados, InfraDTO::$OPER_IN);
  }

  PaginaSEI::getInstance()->prepararOrdenacao($objFiltroDTO, 'Id', InfraDTO::$TIPO_ORDENACAO_DESC);

  // Verificar no DTO sobre funções de agragação para clausula DISTINCT
  if (get_parent_class(BancoSEI::getInstance()) != 'InfraMySqli') {
    $objFiltroDTO->retDthConclusaoAtividade();
  }

  $objTramiteEmBloco = new TramiteEmBlocoRN();
  $arrObjBlocosListar = $objTramiteEmBloco->listar($objFiltroDTO);

  // Cabeçalho da tabela
  $colunas = [
    'id' => 'Número',
    'estado' => 'Estado',
    'descricao' => 'Descrição',
  ];

  // Corpo da tabela
  $tabelaLinhas = [];
  foreach ($arrObjBlocosListar as $objFiltro) {
    $tabelaLinhas[] = [
      'id' => $objFiltro->getNumId(),
      'estado' => $objTramiteEmBloco->retornarEstadoDescricao($objFiltro->getStrStaEstado()),
      'descricao' => $objFiltro->getStrDescricao(),
    ];
  }

  $numRegistros = count($arrObjBlocosListar);

  $arrComandos = [];
  $arrComandos[] = '<button type="button" accesskey="P" onclick="onClickBtnPesquisar();" id="btnPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';
  $arrComandos[] = '<button type="button" value="Novo" id="bntNovo" onclick="onClickBtnNovo()" class="infraButton"><span class="infraTeclaAtalho">N</span>ovo</button>';
  $arrComandos[] = '<button type="button" accesskey="I" id="btnImprimir" value="Imprimir" onclick="infraImprimirTabela();" class="infraButton d-none d-md-inline-block"><span class="infraTeclaAtalho">I</span>mprimir</button>';
  if ($numRegistros > 0) {
    $arrComandos[] = '<button type="button" value="Excluir" onclick="onClickBtnExcluir()" class="infraButton"><span class="infraTeclaAtalho">E</span>xcluir</button>';
  }

  // Início da tabela
  $strSumarioTabela = 'Tabela de Blocos Tramitados.';
  $strCaptionTabela = 'Blocos';

  $strResultado = "<table width='99%' id='tblBlocos' class='infraTable' summary='{$strSumarioTabela}'>" . "\n";
  $strResultado .= '<caption class="infraCaption">' . PaginaSEI::getInstance()->gerarCaptionTabela($strCaptionTabela, $numRegistros) . '</caption>';
  $strResultado .= "<thead>";
  $strResultado .= '<th class="infraTh" width="1%">' . PaginaSEI::getInstance()->getThCheck() . '</th>' . "\n";

  // Adicionar colunas dinamicamente
  foreach ($colunas as $coluna) {
    $strResultado .= '<th class="infraTh" width="10%">';

    $strResultado .= '<div class="infraDivOrdenacao">';
    $strResultado .= "<div class='infraDivRotuloOrdenacao'>{$coluna}</div>";
    $strResultado .= '<div class="infraDivSetaOrdenacao"><a href="javascript:void(0);" tabindex="1002"><img src="/infra_css/svg/seta_acima.svg" title="Ordenar Processo Ascendente" alt="Ordenar Processo Ascendente" class="infraImgOrdenacao"></a></div>';
    $strResultado .= '<div class="infraDivSetaOrdenacao"><a href="javascript:void(0);" tabindex="1003"><img src="/infra_css/svg/seta_abaixo_selecionada.svg" title="Ordenar Processo Descendente" alt="Ordenar Processo Descendente" class="infraImgOrdenacao"></a></div>';
    $strResultado .= '</div>';

    $strResultado .= '</th>' . "\n";
  }
  // Adicionar coluna ações
  $strResultado .= '<th class="infraTh" width="10%">';
  $strResultado .= "<div class='infraDivRotuloOrdenacao'>Ações</div>";
  $strResultado .= '</th>' . "\n";
  $strResultado .= "</thead>";

  foreach ($tabelaLinhas as $cont => $linha) {
    $strResultado .= "<tr class='infraTrClara'>";
    $strResultado .= '<td>'.PaginaSEI::getInstance()->getTrCheck($cont, $linha['id'], $linha['id']).'</td>';
      // $strResultado .= '<td>'.PaginaSEI::getInstance()->getTrCheck($i,$idBlocoTramite,$idBlocoTramite).'</td>';
    $idBlocoTramite = '';
    foreach ($colunas as $key => $coluna) {
      $idBlocoTramite = $linha['id']; 

      if ($linha[$key]) {
        $strResultado .= "<td align='center'> {$linha[$key]} </td>";
      }
    }

    $strResultado .= "<td align=''>";
    // visualizar
    $strResultado .= '<a href="' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_tramita_em_bloco_protocolo_listar&acao_origem=' . $_GET['acao'] . '&acao_retorno=' . $_GET['acao'] . '&id_bloco=' . $idBlocoTramite) . '" tabindex="' . PaginaSEI::getInstance()->getProxTabTabela() . '"><img src="' . Icone::BLOCO_CONSULTAR_PROTOCOLOS . '" title="Visualizar Processos" alt="Visualizar Processos" class="infraImg" /></a>&nbsp;';
    // alterar
    $strResultado .= '<a href="'.SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_tramite_em_bloco_alterar&acao_origem='.$_GET['acao'].'&acao_retorno='.$_GET['acao'].'&id_bloco='.$idBlocoTramite).'" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="'.PaginaSEI::getInstance()->getIconeAlterar().'" title="Alterar Bloco" alt="Alterar Bloco" class="infraImg" /></a>&nbsp;';
    if ($linha['estado'] == $objTramiteEmBloco->retornarEstadoDescricao(TramiteEmBlocoRN::$TE_ABERTO)) {
    // Excluir
      $strResultado .= '<a onclick="onCLickLinkDelete(\''.$objSessaoSEI->assinarLink('controlador.php?acao=md_pen_tramita_em_bloco_excluir&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao'].'&hdnInfraItensSelecionados='.$idBlocoTramite).'\', this)" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="'.PaginaSEI::getInstance()->getIconeExcluir().'" title="Excluir Bloco" alt="Excluir Bloco" class="infraImg" /></a>&nbsp;';
    }
    // Tramitar bloco
    $objTramitaEmBlocoProtocoloDTO = new TramitaEmBlocoProtocoloDTO();
    $objTramitaEmBlocoProtocoloDTO->setNumIdTramitaEmBloco($idBlocoTramite);
    $objTramitaEmBlocoProtocoloDTO->retDblIdProtocolo();
    $objTramitaEmBlocoProtocoloDTO->retNumIdTramitaEmBloco();
    $objTramitaEmBlocoProtocoloRN = new TramitaEmBlocoProtocoloRN();
    $arrTramiteEmBlocoProtocolo = $objTramitaEmBlocoProtocoloRN->listar($objTramitaEmBlocoProtocoloDTO);

    if (!empty($arrTramiteEmBlocoProtocolo) && $linha['estado'] == $objTramiteEmBloco->retornarEstadoDescricao(TramiteEmBlocoRN::$TE_ABERTO)) {
      $strResultado .= '<a href="'.SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_expedir_lote&acao_origem='.$_GET['acao'].'&acao_retorno='.$_GET['acao'].'&id_tramita_em_bloco='.$idBlocoTramite.'&tramite_em_bloco=1').'" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="' . ProcessoEletronicoINT::getCaminhoIcone("/pen_expedir_procedimento.gif", $this->getDiretorioImagens()) . '" title="Tramitar Bloco" alt="Bloco-' .$cont.'" class="infraImg iconTramita" /></a>&nbsp;';
    }

    // Cancelar tramite
    // if ($linha['estado'] == $objTramiteEmBloco->retornarEstadoDescricao(TramiteEmBlocoRN::$TE_DISPONIBILIZADO)) {
    //   $strResultado .= '<a onclick="onClickBtnCancelarTramite(\''.$objSessaoSEI->assinarLink('controlador.php?acao=pen_tramite_em_bloco_cancelar&acao_origem='.$_GET['acao'].'&acao_retorno='.$_GET['acao'].'&hdnInfraItensSelecionados='.$idBlocoTramite.'&id_tramita_em_bloco='.$idBlocoTramite.'&tramite_em_bloco=1').'\', this)" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="' . ProcessoEletronicoINT::getCaminhoIcone("/pen_cancelar_envio.png", $this->getDiretorioImagens()) . '" title="Cancelar Tramite do Bloco" alt="Cancelar Tramite do Bloco" class="infraImg iconTramita" /></a>&nbsp;';
    // }
    $strResultado .= "</td>";
    $strResultado .= "</tr>";
    }

  // Fim da tabela
  $strResultado .= "</table>";
} catch (Exception $e) {
  $objPaginaSEI->processarExcecao($e);
}

$objPaginaSEI->montarDocType();
$objPaginaSEI->abrirHtml();
$objPaginaSEI->abrirHead();
$objPaginaSEI->montarMeta();
$objPaginaSEI->montarTitle(':: ' . $objPaginaSEI->getStrNomeSistema() . ' - ' . $strTitulo . ' ::');
$objPaginaSEI->montarStyle();
?>

<style type="text/css">
  table.tabelaProcessos {
    background-color: white;
    border: 0px solid white;
    border-spacing: .1em;
  }

  table.tabelaProcessos tr {
    margin: 0;
    border: 0;
    padding: 0;
  }

  table.tabelaProcessos img {
    width: 1.1em;
    height: 1.1em;
  }

  table.tabelaProcessos a {
    text-decoration: none;
  }

  table.tabelaProcessos a:hover {
    text-decoration: underline;
  }


  table.tabelaProcessos caption {
    font-size: 1em;
    text-align: right;
    color: #666;
  }

  th.tituloProcessos {
    font-size: 1em;
    font-weight: bold;
    text-align: center;
    color: #000;
    background-color: #dfdfdf;
    border-spacing: 0;
  }

  a.processoNaoVisualizado {
    color: red;
  }

  #divTabelaRecebido {
    margin: 2em;
    float: left;
    display: inline;
    width: 40%;
  }

  #divTabelaRecebido table {
    width: 100%;
  }

  #divTabelaGerado {
    margin: 2em;
    float: right;
    display: inline;
    width: 40%;
  }

  #divTabelaGerado table {
    width: 100%;
  }

  select.infraSelect,
  input.infraText {
    width: 100%;
  }

  .iconTramita {
    max-width: 1.5rem;
  }

   /* Personalize o estilo da paginação */
  .dataTables_paginate {
    margin: 10px;
    text-align: end;
  }

.dataTables_paginate .paginate_button {
  padding: 5px 10px;
  margin-right: 5px;
  border: 1px solid #ccc;
  background-color: #f2f2f2;
  color: #333;
  cursor: pointer;
}

.dataTables_paginate .paginate_button.current {
  background-color: var(--color-primary-default);
  color: #fff;
}


#tblBlocos_filter {
  position: absolute;
  opacity: 0;
}

</style>
<?php $objPaginaSEI->montarJavaScript(); ?>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.js"></script>
<script type="text/javascript">
  function inicializar() {

    infraEfeitoTabelas();
  }

  function onClickBtnPesquisar() {

    var form = jQuery('#frmBlocoLista');
    form.attr('action', '<?php print $objSessaoSEI->assinarLink('controlador.php?acao=md_pen_tramita_em_bloco&acao_origem=md_pen_tramita_em_bloco&acao_retorno=md_pen_tramita_em_bloco'); ?>');
    form.submit();
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

    if (confirm('Confirma a exclusão do bloco de trâmite externo: "' +strEspecieDocumental + '"?')) {
      window.location = url;
    }
  }

  function onClickBtnCancelarTramite(url, link) {
      var row = jQuery(link).parents('tr:first');
      var strEspecieDocumental = row.find('td:eq(1)').text();
      var strTipoDocumento = row.find('td:eq(2)').text();
      console.log(link)
      if (confirm('Confirma o cancelamento dos trâmites do Bloco "' + strEspecieDocumental + '"?')) {
          window.location = url;
      }
  }

  function onClickBtnNovo(){
      window.location = '<?php print $objSessaoSEI->assinarLink('controlador.php?acao=pen_tramite_em_bloco_cadastrar&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao_origem']); ?>';
  }

  function onClickBtnCancelar() {
      try {
          var len = jQuery('input[name*=chkInfraItem]:checked').length;

          if (len > 0) {
              if (confirm('Confirma o cancelamento ' + len + ' mapeamento(s) ?')) {
                  var form = jQuery('#frmBlocoLista');
                  form.attr('action', '<?php print $objSessaoSEI->assinarLink('controlador.php?acao=pen_tramite_em_bloco_cancelar&acao_origem=md_pen_tramita_em_bloco&acao_retorno=md_pen_tramita_em_bloco'); ?>');
                  form.submit();
              }
          } else {
              alert('Selecione pelo menos um mapeamento para Cancelar');
          }
      } catch (e) {
          alert('Erro : ' + e.message);
      }
  }

  function onClickBtnExcluir() {
    try {
      var len = jQuery('input[name*=chkInfraItem]:checked').length;
      if (len > 0) {
        if (confirm('Confirma a exclusão de ' + len + ' bloco(s) de trâmite externo?')) {
          var form = jQuery('#frmBlocoLista');
          form.attr('action', '<?php print $objSessaoSEI->assinarLink('controlador.php?acao=md_pen_tramita_em_bloco_excluir&acao_origem=md_pen_tramita_em_bloco&acao_retorno=md_pen_tramita_em_bloco'); ?>');
          form.submit();
        }
      } else {
        alert('Selecione pelo menos um mapeamento para Excluir');
      }
    } catch (e) {
      alert('Erro : ' + e.message);
    }
  }

  $(document).ready(function() {
    $('#tblBlocos').dataTable({
      "searching": true,
      "columnDefs": [
          { targets: [0, 4], orderable: false } 
        ],
      "language": {
        "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
        "lengthMenu": "Mostrar _MENU_ registros por página",
        "infoEmpty": "Mostrando 0 a 0 de 0 registros",
        "zeroRecords": "Nenhum registro encontrado",
        "paginate": {
          "previous": "Anterior",
          "next": "Próximo"
        },
      }
    } );

    let campoDePesquisa = $("#txtPalavrasPesquisaBloco");
    let BuscaNaTabela = $("input[type='search']");

    $("#txtPalavrasPesquisaBloco").on("click", function() {
      BuscaNaTabela.focus();
      $(this).css("background-color", "#dfdfdf");  
    });

    campoDePesquisa.on("input", function() {
      BuscaNaTabela.val($(this).val());
      input2.focus();
    });

    BuscaNaTabela.on("input", function() {
      campoDePesquisa.val($(this).val());
      input2.focus();
    });
  });

</script>

<?php
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody($strTitulo, 'onload="inicializar();"');
?>
<form id="frmBlocoLista" method="post" action="<?= $strActionPadrao ?>">
  <?php
  $objPaginaSEI->montarBarraComandosSuperior($arrComandos);
  ?>
  <div class="row">
    <div id="divPesquisa1" class="col-12 col-md-3">
      <label id="lblPalavrasPesquisaBloco" for="txtPalavrasPesquisaBloco" accesskey="" class="infraLabelOpcional">Filtrar por Palavras-chave:</label>
      <input autocomplete="off" id="txtPalavrasPesquisaBloco" name="txtPalavrasPesquisaBloco" class="infraText" value="<?= PaginaSEI::tratarHTML($strPalavrasPesquisa) ?>" onkeypress="return tratarDigitacao(event);" />
    </div>
    <div class="col-md-6 col-12 ">
      <div class="d-flex flex-row flex-md-row ">
        <fieldset id="fldEstado" class="ml-md-4 infraFieldset">
          <legend class="infraLegend">Estado</legend>

          <div id="divSinEstadoGerado" class="infraDivCheckbox">
            <input type="checkbox"
             <?php echo in_array(TramiteEmBlocoRN::$TE_ABERTO, $arrEstadosSelecionados) || empty($arrEstadosSelecionados) ? "checked" : ""; ?> id="chkSinEstadoGerado" name="chkSinEstadoGerado" class="infraCheckbox CheckboxEstado" <?= $objPaginaSEI->setCheckbox($strSinEstadoGerado) ?> tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />
            <label id="lblSinEstadoGerado" for="chkSinEstadoGerado" accesskey="" class="infraLabelCheckbox">Aberto</label>
          </div>

          <div id="divSinEstadoDisponibilizado" class="infraDivCheckbox">
            <input type="checkbox" <?php echo in_array(TramiteEmBlocoRN::$TE_DISPONIBILIZADO, $arrEstadosSelecionados) || empty($arrEstadosSelecionados) ? "checked" : ""; ?> id="chkSinEstadoDisponibilizado" name="chkSinEstadoDisponibilizado" class="infraCheckbox CheckboxEstado" <?= $objPaginaSEI->setCheckbox($strSinEstadoDisponibilizado) ?> tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />
            <label id="lblSinEstadoDisponibilizado" for="chkSinEstadoDisponibilizado" accesskey="" class="infraLabelCheckbox">Em Processamento</label>
          </div>

          <div id="divSinEstadoConcluidoParcialmente" class="infraDivCheckbox">
            <input type="checkbox" id="chkSinEstadoConcluidoParcialmente" <?php echo in_array(TramiteEmBlocoRN::$TE_CONCLUIDO_PARCIALMENTE, $arrEstadosSelecionados) || empty($arrEstadosSelecionados) ? "checked" : ""; ?> name="chkSinEstadoConcluidoParcialmente" class="infraCheckbox CheckboxEstado" <?= $objPaginaSEI->setCheckbox($strSinEstadoConcluidoParcialmente) ?> tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />
            <label id="lblSinEstadoConcluido" for="chkSinEstadoConcluidoParcialmente" accesskey="" class="infraLabelCheckbox">Concluído Parcialmente</label>
          </div>

          <div id="divSinEstadoConcluido" class="infraDivCheckbox">
            <input type="checkbox" id="chkSinEstadoConcluido" <?php echo in_array(TramiteEmBlocoRN::$TE_CONCLUIDO, $arrEstadosSelecionados) || empty($arrEstadosSelecionados) ? "checked" : ""; ?> name="chkSinEstadoConcluido" class="infraCheckbox CheckboxEstado" <?= $objPaginaSEI->setCheckbox($strSinEstadoConcluido) ?> tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />
            <label id="lblSinEstadoConcluido" for="chkSinEstadoConcluido" accesskey="" class="infraLabelCheckbox">Concluído</label>
          </div>

        </fieldset>
      </div>
    </div>
  </div>

  <input type="hidden" id="hdnMeusBlocos" name="hdnMeusBlocos" value="<?= $strTipoAtribuicao ?>" />
  <input type="hidden" id="hdnFlagBlocos" name="hdnFlagBlocos" value="1" />
  <?php
  $objPaginaSEI->montarAreaTabela($strResultado, $numRegistros, true);
  $objPaginaSEI->montarAreaDebug();
  $objPaginaSEI->montarBarraComandosInferior($arrComandos);
  ?>
</form>
<?php
$objPaginaSEI->fecharBody();
$objPaginaSEI->fecharHtml();
?>