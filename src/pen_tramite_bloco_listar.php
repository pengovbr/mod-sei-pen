<?php

/**
 *
 */
require_once DIR_SEI_WEB . '/SEI.php';

try {

  session_start();

  $objPaginaSEI = PaginaSEI::getInstance();
  $objSessaoSEI = SessaoSEI::getInstance();

  $objSessaoSEI->validarLink();
  // $objSessaoSEI->validarPermissao($_GET['acao']);
  $arrComandos = array();

  $strTitulo = 'Tramite em Bloco';

  switch ($_GET['acao']) {
    case 'md_pen_tramita_em_bloco_excluir':
      try {
        $arrStrIds = PaginaSEI::getInstance()->getArrStrItensSelecionados();
        $arrObjTramiteEmBlocoDTO = array();
        for ($i = 0; $i < count($arrStrIds); $i++) {
          $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
          $objTramiteEmBlocoDTO->setNumId($arrStrIds[$i]);
          $arrObjTramiteEmBlocoDTO[] = $objTramiteEmBlocoDTO;
        }
        $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
        $objTramiteEmBlocoRN->excluir($arrObjTramiteEmBlocoDTO);
        PaginaSEI::getInstance()->setStrMensagem('Operação realizada com sucesso.');
      } catch (Exception $e) {
        PaginaSEI::getInstance()->processarExcecao($e);
      }
      header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . $_GET['acao_origem'] . '&acao_origem=' . $_GET['acao']));
      die;
    case 'md_pen_tramita_em_bloco':
      break;
    default:
      throw new InfraException("Ação '" . $_GET['acao'] . "' não reconhecida.");
  }

  $objFiltroDTO = new TramiteEmBlocoDTO();
  $objFiltroDTO->retNumId();
  $objFiltroDTO->retStrStaEstado();
  $objFiltroDTO->retStrDescricao();

  // Verificar no DTO sobre funções de agragação para clausula DISTINCT
  if (get_parent_class(BancoSEI::getInstance()) != 'InfraMySqli') {
    $objFiltroDTO->retDthConclusaoAtividade();
  }
    // $objPaginaSEI->prepararPaginacao($objFiltroDTO, 50);

    $obgTramiteEmBloco = new TramiteEmBlocoRN();
    $arrObjBlocosListar = $obgTramiteEmBloco->listar($objFiltroDTO);

    $dados = [];
    foreach ($arrObjBlocosListar as $objFiltro) {
     // print_r($objFiltro->getNumId()); die('aki2');
      $arr['Número'] = $objFiltro->getNumId();
      $arr['Sinalizações'] = 'Sem';
      $arr['Estados'] = $objFiltro->getStrStaEstado();
      $arr['Descrições'] = $objFiltro->getStrDescricao();
      $arr['Ação'] = '';

      $dados[] = $arr;
    }

    $numRegistros = count($arrObjBlocosListar);

  // if(!empty($arrObjProcessoExpedidoDTO)) {
  //     $arrObjBlocosListarDTO = InfraArray::distinctArrInfraDTO($objFiltroDTO, 'IdProtocolo');
  //     $numRegistros = count($arrObjProcessoExpedidoDTO);
  // }

  //   $objPaginaSEI->processarPaginacao($objFiltroDTO);

  // if (!empty($arrObjProcessoExpedidoDTO)) {

  //      $arrComandos[] = '<button type="button" accesskey="I" id="btnImprimir" value="Imprimir" onclick="infraImprimirTabela();" class="infraButton"><span class="infraTeclaAtalho">I</span>mprimir</button>';

  //      $strSumarioTabela = 'Tabela de Processos.';
  //      $strCaptionTabela = 'Processos';

  //      $strResultado .= '<table width="99%" class="infraTable" summary="' . $strSumarioTabela . '">' . "\n";
  //      $strResultado .= '<caption class="infraCaption">' . $objPaginaSEI->gerarCaptionTabela($strCaptionTabela, $numRegistros) . '</caption>';
  //      $strResultado .= '<tr>';
  //      $strResultado .= '<th class="infraTh" width="1%">' . $objPaginaSEI->getThCheck() . '</th>' . "\n";
  //      $strResultado .= '<th class="infraTh">Processo</th>' . "\n";
  //      $strResultado .= '<th class="infraTh">Usuário</th>' . "\n";
  //      $strResultado .= '<th class="infraTh">Data do Envio</th>' . "\n";
  //      $strResultado .= '<th class="infraTh">Unidade Destino</th>' . "\n";
  //      $strResultado .= '</tr>' . "\n";
  //      $strCssTr = '';

  //      $numIndice = 1;

  //   foreach($arrObjProcessoExpedidoDTO as $objProcessoExpedidoDTO) {

  //     $strCssTr = ($strCssTr == '<tr class="infraTrClara">') ? '<tr class="infraTrEscura">' : '<tr class="infraTrClara">';
  //     $strResultado .= $strCssTr;

  //     $strResultado .= '<td valign="top">'.$objPaginaSEI->getTrCheck($numIndice, $objProcessoExpedidoDTO->getDblIdProtocolo(), $objProcessoExpedidoDTO->getStrProtocoloFormatado()).'</td>'."\n";
  //     $strResultado .= '<td width="17%" align="center"><a onclick="abrirProcesso(\'' .$objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=procedimento_trabalhar&acao_origem=' . $_GET['acao'] . '&acao_retorno=' . $_GET['acao'] . '&id_procedimento=' . $objProcessoExpedidoDTO->getDblIdProtocolo())).'\');" tabindex="' . $objPaginaSEI->getProxTabTabela() . '" title="" class="protocoloNormal" style="font-size:1em !important;">'.$objProcessoExpedidoDTO->getStrProtocoloFormatado().'</a></td>' . "\n";
  //     $strResultado .= '<td align="center"><a alt="Teste" title="Teste" class="ancoraSigla">' . $objProcessoExpedidoDTO->getStrNomeUsuario() . '</a></td>';
  //     $strResultado .= '<td width="17%" align="center">' . $objProcessoExpedidoDTO->getDthExpedido() . '</td>';
  //     $strResultado .= '<td align="left">' . $objProcessoExpedidoDTO->getStrDestino();

  //     if ($bolAcaoRemoverSobrestamento) {
  //         $strResultado .= '<a href="' . $objPaginaSEI->montarAncora($objProcessoExpedidoDTO->getDblIdProtocolo()) . '" onclick="acaoRemoverSobrestamento(\'' . $objProcessoExpedidoDTO->getDblIdProtocolo() . '\',\'' . $objProcessoExpedidoDTO->getStrProtocoloFormatado() . '\');" tabindex="' . $objPaginaSEI->getProxTabTabela() . '"><img src="imagens/sei_remover_sobrestamento_processo_pequeno.gif" title="Remover Sobrestamento" alt="Remover Sobrestamento" class="infraImg" /></a>&nbsp;';
  //     }

  //     $strResultado .= '</td></tr>' . "\n";
  //     $numIndice++;
  //   }
  //      $strResultado .= '</table>';
  // }

  $arrComandos[] = '<button type="button" accesskey="I" id="btnImprimir" value="Imprimir" onclick="infraImprimirTabela();" class="infraButton"><span class="infraTeclaAtalho">I</span>mprimir</button>';
  $arrComandos[] = '<button type="button" value="Novo" onclick="onClickBtnNovo()" class="infraButton"><span class="infraTeclaAtalho">N</span>ovo</button>';
  $arrComandos[] = '<button type="button" accesskey="P" onclick="onClickBtnPesquisar();" id="btnPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';
  $arrComandos[] = '<button type="button" value="Excluir" onclick="onClickBtnExcluir()" class="infraButton"><span class="infraTeclaAtalho">E</span>xcluir</button>';


  // Cabeçalho da tabela
  $colunas = array("Número", "Sinalizações", "Estados", "Descrições", "Ações");

  // Início da tabela
  $strSumarioTabela = 'Tabela de Blocos Tramitados.';
  $strCaptionTabela = 'Blocos';


  $strResultado = "<table width='99%' class='infraTable' summary='{$strSumarioTabela}'>" . "\n";
  $strResultado .= '<caption class="infraCaption">' . PaginaSEI::getInstance()->gerarCaptionTabela($strCaptionTabela, $numRegistros) . '</caption>';
  $strResultado .= "<tr>";
  $strResultado .= '<th class="infraTh" width="1%">' . PaginaSEI::getInstance()->getThCheck() . '</th>' . "\n";

  foreach ($colunas as $coluna) {
    $strResultado .= "<th class='infraTh'>{$coluna}</th>";
  }
  $strResultado .= "</tr>";

  foreach ($dados as $linha) {
      $strResultado .= "<tr class='infraTrClara'>";
      $strResultado .= '<td>'.PaginaSEI::getInstance()->getTrCheck($linha['Número'], $linha['Número'], $linha['Número']).'</td>';
       // $strResultado .= '<td>'.PaginaSEI::getInstance()->getTrCheck($i,$objBlocoDTO->getNumIdBloco(),$objBlocoDTO->getNumIdBloco()).'</td>';
      foreach ($colunas as $key => $coluna) {

          if ($linha[$coluna]) {
            $strResultado .= "<td align='center'> {$linha[$coluna]} </td>";
          }

      // Adiciona botões na coluna de ações
      if ($key === count($linha) - 1) {

        $id = $linha['Número']; // $id = $objBlocoDTO->getNumIdBloco();
        $strResultado .= "<td align='center'>";
        // visualizar
        $strResultado .= '<a href="' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_tramita_em_bloco_protocolo_listar&acao_origem=' . $_GET['acao'] . '&acao_retorno=' . $_GET['acao'] . '&id_bloco=' . $id) . '" tabindex="' . PaginaSEI::getInstance()->getProxTabTabela() . '"><img src="' . Icone::BLOCO_CONSULTAR_PROTOCOLOS . '" title="Visualizar Processos" alt="Visualizar Processos" class="infraImg" /></a>&nbsp;';

        // atribuir
        $strResultado .= '<a onclick="acaoAtribuir(\'' . $id . '\');" tabindex="' . PaginaSEI::getInstance()->getProxTabTabela() . '"><img src="' . Icone::BLOCO_USUARIO . '" title="Atribuir Bloco" alt="Atribuir Bloco" class="infraImg" /></a>&nbsp;';

        // alterar
        $strResultado .= '<a href="'.SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_tramite_em_bloco_alterar&acao_origem='.$_GET['acao'].'&acao_retorno='.$_GET['acao'].'&id_bloco='.$id).'" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="'.PaginaSEI::getInstance()->getIconeAlterar().'" title="Alterar Bloco" alt="Alterar Bloco" class="infraImg" /></a>&nbsp;';

        // concluir
        $strResultado .= '<a onclick="acaoConcluir(\'' . $id . '\');" tabindex="' . PaginaSEI::getInstance()->getProxTabTabela() . '"><img src="' . Icone::BLOCO_CONCLUIR . '" title="Concluir Bloco" alt="Concluir Bloco" class="infraImg" /></a>&nbsp;';

        // Excluir
        $strResultado .= '<a onclick="onClickBtnExcluir(\''.$id.'\');" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="'.PaginaSEI::getInstance()->getIconeExcluir().'" title="Excluir Bloco" alt="Excluir Bloco" class="infraImg" /></a>&nbsp;';
        $strResultado .= "</td>";
      }

  }

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
</style>
<?php $objPaginaSEI->montarJavaScript(); ?>
<script type="text/javascript">
  function inicializar() {

    infraEfeitoTabelas();
  }

  function inicializar() {
    infraEfeitoTabelas();
    var strMensagens = '<?php print str_replace("\n", '\n', $objPaginaSEI->getStrMensagens()); ?>';
    if (strMensagens) {
      alert(strMensagens);
    }
  }

  function onClickBtnPesquisar() {
    document.getElementById('frmAcompanharEstadoProcesso').action = '<?php print $objSessaoSEI->assinarLink($strProprioLink); ?>';
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

    if (confirm('Confirma a exclusão do mapeamento "' + strEspecieDocumental + ' x ' + strTipoDocumento + '"?')) {
      window.location = url;
    }
  }

function onClickBtnNovo(){
    window.location = '<?php print $objSessaoSEI->assinarLink('controlador.php?acao=pen_tramite_em_bloco_cadastrar&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao_origem']); ?>';
}

  function onClickBtnExcluir() {

    try {
      var len = jQuery('input[name*=chkInfraItem]:checked').length;

      if (len > 0) {
        if (confirm('Confirma a exclusão de ' + len + ' mapeamento(s) ?')) {
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

  function validarCadastro() {

  }

  function OnSubmitForm() {
    return validarCadastro();
  }
  // function abrirProcesso(link){
  //     document.getElementById('divInfraBarraComandosSuperior').style.visibility = 'hidden';
  //     document.getElementById('divInfraAreaTabela').style.visibility = 'hidden';
  //     infraOcultarMenuSistemaEsquema();
  //     document.getElementById('frmProcedimentoExpedido').action = link;
  //     document.getElementById('frmProcedimentoExpedido').submit();
  // }
  //
</script>

<?php
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody($strTitulo, 'onload="inicializar();"');
?>
<form id="frmBlocoLista" method="post" onsubmit="return OnSubmitForm();" action="<?= $strActionPadrao ?>">
  <?php
  $objPaginaSEI->montarBarraComandosSuperior($arrComandos);
  ?>
  <div class="row">
    <div id="divPesquisa1" class="col-12 col-md-3">
      <label id="lblPalavrasPesquisaBloco" for="txtPalavrasPesquisaBloco" accesskey="" class="infraLabelOpcional">Palavras-chave para pesquisa:</label>
      <input type="text" id="txtPalavrasPesquisaBloco" name="txtPalavrasPesquisaBloco" class="infraText" value="<?= PaginaSEI::tratarHTML($strPalavrasPesquisa) ?>" onkeypress="return tratarDigitacao(event);" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />

      <div id="divLinkVisualizacao">
        <? if ($strTipoAtribuicao == BlocoRN::$TA_MINHAS) { ?>
          <a id="ancVisualizacao" href="javascript:void(0);" onclick="verBlocos('<?= BlocoRN::$TA_TODAS ?>');" class="ancoraPadraoPreta" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>">Ver todos os blocos</a>
        <? } else { ?>
          <a id="ancVisualizacao" href="javascript:void(0);" onclick="verBlocos('<?= BlocoRN::$TA_MINHAS ?>');" class="ancoraPadraoPreta" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>">Ver blocos atribuídos a mim</a>
        <? } ?>
      </div>

    </div>
    <div class="col-md-6 col-12 ">
      <div class="d-flex flex-row flex-md-row ">
        <fieldset id="fldSinalizacao" class=" mr-2 flex-grow-1 mr-md-2 flex-md-grow-0 infraFieldset">
          <legend class="infraLegend"> Sinalizações </legend>

          <div id="divSinPrioridade" class="infraDivCheckbox">
            <input type="checkbox" id="chkSinPrioridade" name="chkSinPrioridade" onchange="this.form.submit()" class="infraCheckbox" <?= $objPaginaSEI->setCheckbox($strSinPrioridade) ?> tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />
            <label id="lblSinPrioridade" for="chkSinPrioridade" accesskey="" class="infraLabelCheckbox">Prioritários</label>
          </div>

          <div id="divSinRevisao" class="infraDivCheckbox">
            <input type="checkbox" id="chkSinRevisao" name="chkSinRevisao" onchange="this.form.submit()" class="infraCheckbox" <?= $objPaginaSEI->setCheckbox($strSinRevisao) ?> tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />
            <label id="lblSinRevisao" for="chkSinRevisao" accesskey="" class="infraLabelCheckbox">Revisados</label>
          </div>
          <div id="divSinComentario" class="infraDivCheckbox">
            <input type="checkbox" id="chkSinComentario" name="chkSinComentario" onchange="this.form.submit()" class="infraCheckbox" <?= $objPaginaSEI->setCheckbox($strSinComentario) ?> tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />
            <label id="lblSinComentario" for="chkSinComentario" accesskey="" class="infraLabelCheckbox">Comentados</label>
          </div>
        </fieldset>
        <fieldset id="fldEstado" class="ml-md-4 infraFieldset">
          <legend class="infraLegend">Estado</legend>

          <div id="divSinEstadoGerado" class="infraDivCheckbox">
            <input type="checkbox" id="chkSinEstadoGerado" name="chkSinEstadoGerado" onchange="this.form.submit()" class="infraCheckbox" <?= $objPaginaSEI->setCheckbox($strSinEstadoGerado) ?> tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />
            <label id="lblSinEstadoGerado" for="chkSinEstadoGerado" accesskey="" class="infraLabelCheckbox">Aberto</label>
          </div>

          <div id="divSinEstadoDisponibilizado" class="infraDivCheckbox">
            <input type="checkbox" id="chkSinEstadoDisponibilizado" name="chkSinEstadoDisponibilizado" onchange="this.form.submit()" class="infraCheckbox" <?= $objPaginaSEI->setCheckbox($strSinEstadoDisponibilizado) ?> tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />
            <label id="lblSinEstadoDisponibilizado" for="chkSinEstadoDisponibilizado" accesskey="" class="infraLabelCheckbox">Em Andamento</label>
          </div>

          <!-- <div id="divSinEstadoRecebido" class="infraDivCheckbox">
            <input type="checkbox" id="chkSinEstadoRecebido" name="chkSinEstadoRecebido" onchange="this.form.submit()" class="infraCheckbox" <?= $objPaginaSEI->setCheckbox($strSinEstadoRecebido) ?>  tabindex="<?= $objPaginaSEI->getProxTabDados() ?>"/>
            <label id="lblSinEstadoRecebido" for="chkSinEstadoRecebido" accesskey="" class="infraLabelCheckbox">Recebido</label>
          </div> -->

          <div id="divSinEstadoRetornado" class="infraDivCheckbox">
            <input type="checkbox" id="chkSinEstadoRetornado" name="chkSinEstadoRetornado" onchange="this.form.submit()" class="infraCheckbox" <?= $objPaginaSEI->setCheckbox($strSinEstadoRetornado) ?> tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />
            <label id="lblSinEstadoRetornado" for="chkSinEstadoRetornado" accesskey="" class="infraLabelCheckbox">Retornado</label>
          </div>

          <div id="divSinEstadoConcluido" class="infraDivCheckbox">
            <input type="checkbox" id="chkSinEstadoConcluido" name="chkSinEstadoConcluido" onchange="this.form.submit()" class="infraCheckbox" <?= $objPaginaSEI->setCheckbox($strSinEstadoConcluido) ?> tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />
            <label id="lblSinEstadoConcluido" for="chkSinEstadoConcluido" accesskey="" class="infraLabelCheckbox">Concluído</label>
          </div>

        </fieldset>
      </div>
    </div>
  </div>
  <input type="hidden" id="hdnMeusBlocos" name="hdnMeusBlocos" value="<?= $strTipoAtribuicao ?>" />
  <input type="hidden" id="hdnFlagBlocos" name="hdnFlagBlocos" value="1" />
  <?php
  $numRegistros = 1;
  $objPaginaSEI->montarAreaTabela($strResultado, $numRegistros, true);
  $objPaginaSEI->montarAreaDebug();
  $objPaginaSEI->montarBarraComandosInferior($arrComandos);
  ?>
</form>
<?
$objPaginaSEI->fecharBody();
$objPaginaSEI->fecharHtml();
?>
