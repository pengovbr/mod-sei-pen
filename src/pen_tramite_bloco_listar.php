<?php
/**
 *
 */
require_once DIR_SEI_WEB.'/SEI.php';

try {

    session_start();

    $objPaginaSEI = PaginaSEI::getInstance();
    $objSessaoSEI = SessaoSEI::getInstance();

  $objSessaoSEI->validarLink();
  SessaoSEI::getInstance()->validarPermissao($_GET['acao']);
  $objSessaoSEI->validarPermissao($_GET['acao']);


  $strActionPadrao = SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao'].'&acao_origem='.$_GET['acao'].'&id_documento=222'.$_GET['id_documento']);
  PaginaSEI::getInstance()->salvarCamposPost(array('txtPalavrasPesquisaBloco', 'chakSinEstadoGerado', 'selUnidadeGeradora', 'hdnMeusBlocos'));


  $strTitulo = 'Tramite em Bloco';

  switch ($_GET['acao']) {
    case 'md_pen_tramita_em_bloco_excluir':
      try {
        $arrStrIds = PaginaSEI::getInstance()->getArrStrItensSelecionados();
        $arrObjTramiteEmBlocoDTO = array();
        if (count($arrStrIds) > 0) {
          for ($i = 0; $i < count($arrStrIds); $i++) {
            $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
            $objTramiteEmBlocoDTO->setNumId($arrStrIds[$i]);
            $arrObjTramiteEmBlocoDTO[] = $objTramiteEmBlocoDTO;
          }
        } elseif (isset($_GET['hdnInfraItensSelecionados'])) {
          $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
          $objTramiteEmBlocoDTO->setNumId(intval($_GET['hdnInfraItensSelecionados']));
          $arrObjTramiteEmBlocoDTO[] = $objTramiteEmBlocoDTO;
        }
        $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
        $objTramiteEmBlocoRN->excluir($arrObjTramiteEmBlocoDTO);
        PaginaSEI::getInstance()->setStrMensagem('Operação realizada com sucesso.');
      } catch (Exception $e) {
        PaginaSEI::getInstance()->processarExcecao($e);
      }
      header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao']));
      die;
    case 'md_pen_tramita_em_bloco':
      $arrEstadosSelecionados = [];
      $checkboxesEstados = [
          'chkSinEstadoGerado' => TramiteEmBlocoRN::$TE_ABERTO,
          'chkSinEstadoDisponibilizado' => TramiteEmBlocoRN::$TE_DISPONIBILIZADO,
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
  if(get_parent_class(BancoSEI::getInstance()) != 'InfraMySqli') {
      $objFiltroDTO->retDthConclusaoAtividade();
  }
    // $objPaginaSEI->prepararPaginacao($objFiltroDTO, 50);

    $obgTramiteEmBloco = new TramiteEmBlocoRN();
    $arrObjBlocosListar = $obgTramiteEmBloco->listar($objFiltroDTO);

    // Cabeçalho da tabela
    $colunas = array(
      'id' => 'Número',
      'sinalizacao' => 'Sinalizações',
      'estado' => 'Estados',
      'descricao' => 'Descrições',
      'acao' => 'Ações'
    );

    // Corpo Tabela
    $tabelaLinhas = [];
    foreach ($arrObjBlocosListar as $objFiltro) {
     // print_r($objFiltro->getNumId()); die('aki2');
      $arr['id'] = $objFiltro->getNumId();
      $arr['sinalizacao'] = 'R';
      $arr['estado'] = $objFiltro->getStrStaEstado();
      $arr['descricao'] = $objFiltro->getStrDescricao();
      $arr['acao'] = '';

      $tabelaLinhas[] = $arr;
    }

    $numRegistros = count($arrObjBlocosListar);

  $arrComandos = [];
  $arrComandos[] = '<button type="button" accesskey="I" id="btnImprimir" value="Imprimir" onclick="infraImprimirTabela();" class="infraButton"><span class="infraTeclaAtalho">I</span>mprimir</button>';
  $arrComandos[] = '<button type="button" value="Novo" onclick="onClickBtnNovo()" class="infraButton"><span class="infraTeclaAtalho">N</span>ovo</button>';
  $arrComandos[] = '<button type="button" value="Cancelar" onclick="onClickBtnCancelar()" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';
  $arrComandos[] = '<button type="button" accesskey="P" onclick="onClickBtnPesquisar();" id="btnPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';
  $arrComandos[] = '<button type="button" value="Excluir" onclick="onClickBtnExcluir()" class="infraButton"><span class="infraTeclaAtalho">E</span>xcluir</button>';

  // Início da tabela
  $strSumarioTabela = 'Tabela de Blocos Tramitados.';
  $strCaptionTabela = 'Blocos';

  $strResultado = "<table width='99%' class='infraTable' summary='{$strSumarioTabela}'>" . "\n";
  $strResultado .= '<caption class="infraCaption">'.PaginaSEI::getInstance()->gerarCaptionTabela($strCaptionTabela, $numRegistros).'</caption>';
  $strResultado .= "<tr>";
  $strResultado .= '<th class="infraTh" width="1%">'.PaginaSEI::getInstance()->getThCheck().'</th>'."\n";

  foreach ($colunas as $key => $coluna) {
    $strResultado .= "<th class='infraTh'>{$coluna}</th>";
  }
  $strResultado .= "</tr>";

  foreach ($tabelaLinhas as $linha) {
      $strResultado .= "<tr class='infraTrClara'>";
      $strResultado .= '<td>'.PaginaSEI::getInstance()->getTrCheck($linha['id'], $linha['id'], $linha['id']).'</td>';
       // $strResultado .= '<td>'.PaginaSEI::getInstance()->getTrCheck($i,$idBlocoTramite,$idBlocoTramite).'</td>';
      foreach ($colunas as $key => $coluna) {
        $idBlocoTramite = $linha['id']; // $idBlocoTramite = $idBlocoTramite;

        if ($linha[$key]) {
          $strResultado .= "<td align='center'> {$linha[$key]} </td>";
        }

      // Adiciona botões na coluna de ações
      if ($coluna == 'Ações') {
        $strResultado .= "<td align='center'>";
        // visualizar
        $strResultado .= '<a href="' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_tramita_em_bloco_protocolo_listar&acao_origem=' . $_GET['acao'] . '&acao_retorno=' . $_GET['acao'] . '&id_bloco=' . $idBlocoTramite) . '" tabindex="' . PaginaSEI::getInstance()->getProxTabTabela() . '"><img src="' . Icone::BLOCO_CONSULTAR_PROTOCOLOS . '" title="Visualizar Processos" alt="Visualizar Processos" class="infraImg" /></a>&nbsp;';

        // atribuir
        $strResultado .= '<a onclick="acaoAtribuir(\'' . $idBlocoTramite . '\');" tabindex="' . PaginaSEI::getInstance()->getProxTabTabela() . '"><img src="' . Icone::BLOCO_USUARIO . '" title="Atribuir Bloco" alt="Atribuir Bloco" class="infraImg" /></a>&nbsp;';

        // alterar
        $strResultado .= '<a href="'.SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_tramite_em_bloco_alterar&acao_origem='.$_GET['acao'].'&acao_retorno='.$_GET['acao'].'&id_bloco='.$idBlocoTramite).'" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="'.PaginaSEI::getInstance()->getIconeAlterar().'" title="Alterar Bloco" alt="Alterar Bloco" class="infraImg" /></a>&nbsp;';

        // concluir
        $strResultado .= '<a onclick="acaoConcluir(\'' . $idBlocoTramite . '\');" tabindex="' . PaginaSEI::getInstance()->getProxTabTabela() . '"><img src="' . Icone::BLOCO_CONCLUIR . '" title="Concluir Bloco" alt="Concluir Bloco" class="infraImg" /></a>&nbsp;';

        // Excluir
        //$strResultado .= '<a onclick="" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="'.PaginaSEI::getInstance()->getIconeExcluir().'" title="Excluir Bloco" alt="Excluir Bloco" class="infraImg" /></a>&nbsp;';

        $strResultado .= '<a onclick="onCLickLinkDelete(\''.$objSessaoSEI->assinarLink('controlador.php?acao=md_pen_tramita_em_bloco_excluir&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao'].'&hdnInfraItensSelecionados='.$idBlocoTramite).'\', this)" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="'.PaginaSEI::getInstance()->getIconeExcluir().'" title="Excluir Bloco" alt="Excluir Bloco" class="infraImg" /></a>&nbsp;';

        // Tramitar bloco
        $strResultado .= '<a href="'.SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_expedir_lote&acao_origem='.$_GET['acao'].'&acao_retorno='.$_GET['acao'].'&id_tramita_em_bloco='.$idBlocoTramite.'&tramite_em_bloco=1').'" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="' . ProcessoEletronicoINT::getCaminhoIcone("/pen_expedir_procedimento.gif", $this->getDiretorioImagens()) . '" title="Tramitar Bloco" alt="Tramitar Bloco" class="infraImg iconTramita" /></a>&nbsp;';

        $strResultado .= '<a href="'.SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_tramite_em_bloco_cancelar&acao_origem='.$_GET['acao'].'&acao_retorno='.$_GET['acao'].'&id_tramita_em_bloco='.$idBlocoTramite.'&tramite_em_bloco=1').'" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="' . ProcessoEletronicoINT::getCaminhoIcone("/pen_cancelar_envio.png", $this->getDiretorioImagens()) . '" title="Tramitar Bloco" alt="Tramitar Bloco" class="infraImg iconTramita" /></a>&nbsp;';

        $strResultado .= "</td>";
      }
    }
     $strResultado .= "</tr>";
  }

  // Fim da tabela
  $strResultado .= "</table>";

}
catch (Exception $e) {
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
    background-color:white;
    border:0px solid white;
    border-spacing:.1em;
}

table.tabelaProcessos tr{
    margin:0;
    border:0;
    padding:0;
}

table.tabelaProcessos img{
    width:1.1em;
    height:1.1em;
}

table.tabelaProcessos a{
    text-decoration:none;
}

table.tabelaProcessos a:hover{
    text-decoration:underline;
}


table.tabelaProcessos caption{
    font-size: 1em;
    text-align: right;
    color: #666;
}

th.tituloProcessos{
    font-size:1em;
    font-weight: bold;
    text-align: center;
    color: #000;
    background-color: #dfdfdf;
    border-spacing: 0;
}

a.processoNaoVisualizado{
    color:red;
}

#divTabelaRecebido {
    margin:2em;
    float:left;
    display:inline;
    width:40%;
}

#divTabelaRecebido table{
    width:100%;
}

#divTabelaGerado {
    margin:2em;
    float:right;
    display:inline;
    width:40%;
}

#divTabelaGerado table{
    width:100%;
}

  select.infraSelect,
  input.infraText {
    width: 100%;
  }
  .iconTramita {
    max-width: 1.5rem;
  }
</style>
<?php $objPaginaSEI->montarJavaScript(); ?>
<script type="text/javascript">

function inicializar(){

    infraEfeitoTabelas();
}

function inicializar(){
  infraEfeitoTabelas();
  var strMensagens = '<?php print str_replace("\n", '\n', $objPaginaSEI->getStrMensagens()); ?>';
   if(strMensagens) {
       alert(strMensagens);
   }
}

  function onClickBtnPesquisar() {

    var form = jQuery('#frmBlocoLista');
    form.attr('action', '<?php print $objSessaoSEI->assinarLink('controlador.php?acao=md_pen_tramita_em_bloco&acao_origem=md_pen_tramita_em_bloco&acao_retorno=md_pen_tramita_em_bloco'); ?>');
    form.submit();
  }

function tratarEnter(ev){
    var key = infraGetCodigoTecla(ev);
    if (key == 13){
        onClickBtnPesquisar();
    }
    return true;
}

function onCLickLinkDelete(url, link) {
    var row = jQuery(link).parents('tr:first');
    var strEspecieDocumental = row.find('td:eq(1)').text();
    var strTipoDocumento     = row.find('td:eq(2)').text();

    if(confirm('Confirma a exclusão do mapeamento "' + strEspecieDocumental + ' x ' + strTipoDocumento +'"?')){
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
              alert('Selecione pelo menos um mapeamento para Excluir');
          }
      } catch (e) {
          alert('Erro : ' + e.message);
      }
  }

  function onClickBtnExcluir() {

    try {
        var len = jQuery('input[name*=chkInfraItem]:checked').length;

        if(len > 0){
            if(confirm('Confirma a exclusão de ' + len + ' mapeamento(s) ?')) {
                var form = jQuery('#frmAcompanharEstadoProcesso');
                form.attr('action', '<?php print $objSessaoSEI->assinarLink('controlador.php?acao=pen_map_tipo_documento_envio_excluir&acao_origem='.$_GET['acao_origem'].'&acao_retorno=pen_map_tipo_documento_envio_listar'); ?>');
                form.submit();
            }
        }
        else {
            alert('Selecione pelo menos um mapeamento para Excluir');
        }
    }
    catch(e){
        alert('Erro : ' + e.message);
    }
}

  function validarCadastro() {

  }

  function OnSubmitForm() {
    return validarCadastro();
  }
</script>

<?php
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody( $strTitulo, 'onload="inicializar();"');
?>
<form id="frmBlocoLista" method="post" onsubmit="return OnSubmitForm();" action="<?=$strActionPadrao?>">
  <?php
  $objPaginaSEI->montarBarraComandosSuperior($arrComandos);
  ?>
<div class="row">
  <div id="divPesquisa1" class="col-12 col-md-3">
    <label id="lblPalavrasPesquisaBloco" for="txtPalavrasPesquisaBloco" accesskey="" class="infraLabelOpcional">Palavras-chave para pesquisa:</label>
    <input type="text" id="txtPalavrasPesquisaBloco" name="txtPalavrasPesquisaBloco" class="infraText" value="<?=PaginaSEI::tratarHTML($strPalavrasPesquisa)?>" onkeypress="return tratarDigitacao(event);" tabindex="<?=$objPaginaSEI->getProxTabDados()?>" />

    <div id="divLinkVisualizacao">
    <?  if ($strTipoAtribuicao == BlocoRN::$TA_MINHAS) { ?>
      <a id="ancVisualizacao" href="javascript:void(0);" onclick="verBlocos('<?=BlocoRN::$TA_TODAS?>');" class="ancoraPadraoPreta" tabindex="<?=$objPaginaSEI->getProxTabDados()?>">Ver todos os blocos</a>
    <? } else { ?>
      <a id="ancVisualizacao" href="javascript:void(0);" onclick="verBlocos('<?=BlocoRN::$TA_MINHAS?>');" class="ancoraPadraoPreta" tabindex="<?=$objPaginaSEI->getProxTabDados()?>">Ver blocos atribuídos a mim</a>
    <? } ?>
    </div>

  </div>
  <div class="col-md-6 col-12 ">
    <div class="d-flex flex-row flex-md-row ">
        <fieldset id="fldSinalizacao" class=" mr-2 flex-grow-1 mr-md-2 flex-md-grow-0 infraFieldset">
        <legend class="infraLegend"> Sinalizações </legend>

          <div id="divSinPrioridade" class="infraDivCheckbox">
            <input type="checkbox" id="chkSinPrioridade" name="chkSinPrioridade" class="infraCheckbox" <?= $objPaginaSEI->setCheckbox($strSinPrioridade) ?> tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />
            <label id="lblSinPrioridade" for="chkSinPrioridade" accesskey="" class="infraLabelCheckbox">Prioritários</label>
          </div>

          <div id="divSinRevisao" class="infraDivCheckbox">
            <input type="checkbox" id="chkSinRevisao" name="chkSinRevisao" class="infraCheckbox" <?= $objPaginaSEI->setCheckbox($strSinRevisao) ?> tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />
            <label id="lblSinRevisao" for="chkSinRevisao" accesskey="" class="infraLabelCheckbox">Revisados</label>
          </div>
          <div id="divSinComentario" class="infraDivCheckbox">
            <input type="checkbox"  id="chkSinComentario" name="chkSinComentario" class="infraCheckbox" <?= $objPaginaSEI->setCheckbox($strSinComentario) ?> tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />
            <label id="lblSinComentario" for="chkSinComentario" accesskey="" class="infraLabelCheckbox">Comentados</label>
          </div>
        </fieldset>
        <fieldset id="fldEstado" class="ml-md-4 infraFieldset">
          <legend class="infraLegend">Estado</legend>

          <div id="divSinEstadoGerado" class="infraDivCheckbox">
            <input type="checkbox" <?php echo in_array(TramiteEmBlocoRN::$TE_ABERTO, $arrEstadosSelecionados) ? "checked" : ""; ?> id="chkSinEstadoGerado" name="chkSinEstadoGerado" class="infraCheckbox CheckboxEstado" <?= $objPaginaSEI->setCheckbox($strSinEstadoGerado) ?> tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />
            <label id="lblSinEstadoGerado" for="chkSinEstadoGerado" accesskey="" class="infraLabelCheckbox">Aberto</label>
          </div>

          <div id="divSinEstadoDisponibilizado" class="infraDivCheckbox">
            <input type="checkbox" <?php echo in_array(TramiteEmBlocoRN::$TE_DISPONIBILIZADO, $arrEstadosSelecionados) ? "checked" : ""; ?> id="chkSinEstadoDisponibilizado" name="chkSinEstadoDisponibilizado" class="infraCheckbox CheckboxEstado" <?= $objPaginaSEI->setCheckbox($strSinEstadoDisponibilizado) ?> tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />
            <label id="lblSinEstadoDisponibilizado" for="chkSinEstadoDisponibilizado" accesskey="" class="infraLabelCheckbox">Em Processamento</label>
          </div>

          <div id="divSinEstadoConcluido" class="infraDivCheckbox">
            <input type="checkbox" id="chkSinEstadoConcluido" <?php echo in_array(TramiteEmBlocoRN::$TE_CONCLUIDO, $arrEstadosSelecionados) ? "checked" : ""; ?> name="chkSinEstadoConcluido" class="infraCheckbox CheckboxEstado" <?= $objPaginaSEI->setCheckbox($strSinEstadoConcluido) ?> tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />
            <label id="lblSinEstadoConcluido" for="chkSinEstadoConcluido" accesskey="" class="infraLabelCheckbox">Concluído</label>
          </div>

        </fieldset>
    </div>
  </div>
</div>
  <input type="hidden" id="hdnMeusBlocos" name="hdnMeusBlocos" value="<?=$strTipoAtribuicao?>" />
  <input type="hidden" id="hdnFlagBlocos" name="hdnFlagBlocos" value="1" />
  <?php

  $objPaginaSEI->montarAreaTabela($strResultado, $numRegistros, true);
  $objPaginaSEI->montarAreaDebug();
  $objPaginaSEI->montarBarraComandosInferior($arrComandos);
  ?>
</form>
<?
$objPaginaSEI->fecharBody();
$objPaginaSEI->fecharHtml();
?>
