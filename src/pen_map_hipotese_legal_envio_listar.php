<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Consulta os logs do estado do procedimento ao ser expedido
 */

session_start();

define('PEN_RECURSO_ATUAL', 'pen_map_hipotese_legal_envio_listar');
define('PEN_RECURSO_BASE', 'pen_map_hipotese_legal_envio');
define('PEN_PAGINA_TITULO', 'Mapeamento de Hipóteses Legais para Envio');
define('PEN_PAGINA_GET_ID', 'id_mapeamento');


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
  if(array_key_exists('acao', $_GET)) {

      $arrParam = array_merge($_GET, $_POST);

    switch($_GET['acao']) {

      case PEN_RECURSO_BASE.'_excluir':
        if(array_key_exists('hdnInfraItensSelecionados', $arrParam) && !empty($arrParam['hdnInfraItensSelecionados'])) {

            $objPenRelHipoteseLegalDTO = new PenRelHipoteseLegalDTO();
            $objPenRelHipoteseLegalRN = new PenRelHipoteseLegalEnvioRN();

            $arrParam['hdnInfraItensSelecionados'] = explode(',', $arrParam['hdnInfraItensSelecionados']);

          if(is_array($arrParam['hdnInfraItensSelecionados'])) {

            foreach($arrParam['hdnInfraItensSelecionados'] as $dblIdMap) {

                $objPenRelHipoteseLegalDTO->setDblIdMap($dblIdMap);
                $objPenRelHipoteseLegalRN->excluir($objPenRelHipoteseLegalDTO);
            }
          }
          else {

              $objPenRelHipoteseLegalDTO->setDblIdMap($arrParam['hdnInfraItensSelecionados']);
              $objPenRelHipoteseLegalRN->excluir($objPenRelHipoteseLegalDTO);
          }

            $objPagina->adicionarMensagem(sprintf('%s foi excluido com sucesso.', PEN_PAGINA_TITULO), InfraPagina::$TIPO_MSG_AVISO);

            header('Location: '.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao_retorno'].'&acao_origem='.$_GET['acao_origem']));
            exit(0);
        }
        else {
                    
            throw new InfraException('Módulo do Tramita: Nenhum Registro foi selecionado para executar esta ação');
        }

      case PEN_RECURSO_BASE.'_listar':
          // Ação padrão desta tela
          break;

      default:
          throw new InfraException('Módulo do Tramita: Ação não permitida nesta tela');
            
    }
  }
    //--------------------------------------------------------------------------

    $arrComandos = [];
    $arrComandos[] = '<button type="button" accesskey="P" onclick="onClickBtnPesquisar();" id="btnPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';
    $arrComandos[] = '<button type="button" value="Novo" onclick="onClickBtnNovo()" class="infraButton"><span class="infraTeclaAtalho">N</span>ovo</button>';
    $arrComandos[] = '<button type="button" value="Excluir" onclick="onClickBtnExcluir()" class="infraButton"><span class="infraTeclaAtalho">E</span>xcluir</button>';
    $arrComandos[] = '<button type="button" accesskey="I" id="btnImprimir" value="Imprimir" onclick="infraImprimirTabela();" class="infraButton"><span class="infraTeclaAtalho">I</span>mprimir</button>';

    //--------------------------------------------------------------------------
    // DTO de paginao

    $objPenRelHipoteseLegalDTO = new PenRelHipoteseLegalDTO();
    $objPenRelHipoteseLegalDTO->setStrTipo('E');
    $objPenRelHipoteseLegalDTO->retTodos();
    //--------------------------------------------------------------------------
    // Filtragem 
  if(array_key_exists('id_barramento', $_POST) && (!empty($_POST['id_barramento']) && $_POST['id_barramento'] !== 'null')) {
      $objPenRelHipoteseLegalDTO->setNumIdBarramento($_POST['id_barramento']);
  }

  if(array_key_exists('id_hipotese_legal', $_POST) && (!empty($_POST['id_hipotese_legal']) && $_POST['id_barramento'] !== 'null')) {
      $objPenRelHipoteseLegalDTO->setNumIdHipoteseLegal($_POST['id_hipotese_legal']);
  } 

    $objFiltroDTO = clone $objPenRelHipoteseLegalDTO;

  if(!$objFiltroDTO->isSetNumIdBarramento()) {
      $objFiltroDTO->setNumIdBarramento('');   
  }

  if(!$objFiltroDTO->isSetNumIdHipoteseLegal()) {
      $objFiltroDTO->setNumIdHipoteseLegal('');
  }
    //--------------------------------------------------------------------------
    $objGenericoBD = new GenericoBD($objBanco);

    // Mapeamento da hipotese legal remota
    $objPenHipoteseLegalDTO = new PenHipoteseLegalDTO();
    $objPenHipoteseLegalDTO->setDistinct(true);
    $objPenHipoteseLegalDTO->setOrdStrNome(InfraDTO::$TIPO_ORDENACAO_ASC);
    $objPenHipoteseLegalDTO->retNumIdHipoteseLegal();
    $objPenHipoteseLegalDTO->setStrAtivo('S');
    $objPenHipoteseLegalDTO->retStrNome();

    $objPenHipoteseLegalRN = new PenHipoteseLegalRN();
    $arrMapIdBarramento = InfraArray::converterArrInfraDTO($objPenHipoteseLegalRN->listar($objPenHipoteseLegalDTO), 'Nome', 'IdHipoteseLegal');

    // Mapeamento da hipotese legal local
    $objHipoteseLegalDTO = new HipoteseLegalDTO();
    $objHipoteseLegalDTO->setDistinct(true);
    $objHipoteseLegalDTO->setStrStaNivelAcesso(ProtocoloRN::$NA_RESTRITO); //Restrito
    $objHipoteseLegalDTO->setStrSinAtivo('S');
    $objHipoteseLegalDTO->setOrdStrNome(InfraDTO::$TIPO_ORDENACAO_ASC);
    $objHipoteseLegalDTO->retNumIdHipoteseLegal();
    $objHipoteseLegalDTO->retStrNome();

    $objHipoteseLegalRN = new HipoteseLegalRN();
    $arrMapIdHipoteseLegal = InfraArray::converterArrInfraDTO($objHipoteseLegalRN->listar($objHipoteseLegalDTO), 'Nome', 'IdHipoteseLegal');

    $objPagina->prepararOrdenacao($objPenRelHipoteseLegalDTO, 'IdMap', InfraDTO::$TIPO_ORDENACAO_ASC);
    $objPagina->prepararPaginacao($objPenRelHipoteseLegalDTO);
    $arrObjPenRelHipoteseLegalDTO = $objGenericoBD->listar($objPenRelHipoteseLegalDTO);    
    $objPagina->processarPaginacao($objPenRelHipoteseLegalDTO);

    $numRegistros = count($arrObjPenRelHipoteseLegalDTO);

  if(!empty($arrObjPenRelHipoteseLegalDTO)) {

      $strResultado = '';

      $strResultado .= '<table width="99%" class="infraTable">'."\n";
      $strResultado .= '<caption class="infraCaption">'.$objPagina->gerarCaptionTabela(PEN_PAGINA_TITULO, $numRegistros).'</caption>';

      $strResultado .= '<tr>';
      $strResultado .= '<th class="infraTh" width="1%">'.$objPagina->getThCheck().'</th>'."\n";
      $strResultado .= '<th class="infraTh" width="35%">Hipótese Legal SEI - '.$objSessao->getStrSiglaOrgaoUnidadeAtual().'</th>'."\n";
      $strResultado .= '<th class="infraTh" width="35%">Hipótese Legal Tramita GOV.BR</th>'."\n";
      $strResultado .= '<th class="infraTh" width="14%">Ações</th>'."\n";
      $strResultado .= '</tr>'."\n";
      $strCssTr = '';

      $index = 0;
    foreach($arrObjPenRelHipoteseLegalDTO as $objPenRelHipoteseLegalDTO) {

        $strCssTr = ($strCssTr == 'infraTrClara') ? 'infraTrEscura' : 'infraTrClara';

        $strResultado .= '<tr class="'.$strCssTr.'">';
        $strResultado .= '<td>'.$objPagina->getTrCheck($index, $objPenRelHipoteseLegalDTO->getDblIdMap(), '').'</td>';
        $strResultado .= '<td>'.$arrMapIdHipoteseLegal[$objPenRelHipoteseLegalDTO->getNumIdHipoteseLegal()].'</td>';
        $strResultado .= '<td>'.$arrMapIdBarramento[$objPenRelHipoteseLegalDTO->getNumIdBarramento()].'</td>';
        $strResultado .= '<td align="center">';

      if($objSessao->verificarPermissao('pen_map_hipotese_legal_envio_alterar')) {
        $strResultado .= '<a href="'.$objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_cadastrar&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao'].'&'.PEN_PAGINA_GET_ID.'='.$objPenRelHipoteseLegalDTO->getDblIdMap()).'"><img src=' . ProcessoEletronicoINT::getCaminhoIcone("imagens/alterar.gif") . ' title="Alterar Mapeamento" alt="Alterar Mapeamento" class="infraImg"></a>';
      }

      if($objSessao->verificarPermissao('pen_map_hipotese_legal_envio_excluir')) {
          $strResultado .= '<a href="#" onclick="onCLickLinkDelete(\''.$objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_excluir&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao'].'&hdnInfraItensSelecionados='.$objPenRelHipoteseLegalDTO->getDblIdMap()).'\', this)"><img src=' . ProcessoEletronicoINT::getCaminhoIcone("imagens/excluir.gif") . ' title="Excluir Mapeamento" alt="Excluir Mapeamento" class="infraImg"></a>';
      }

        $strResultado .= '</td>';
        $strResultado .= '</tr>'."\n";

        $index++;
    }
      $strResultado .= '</table>';
  }
}
catch(InfraException $e){

    print '<pre>';
    print_r($e);
    print '</pre>';
    exit(0);
} 


$objPagina->montarDocType();
$objPagina->abrirHtml();
$objPagina->abrirHead();
$objPagina->montarMeta();
$objPagina->montarTitle(':: '.$objPagina->getStrNomeSistema().' - '.PEN_PAGINA_TITULO.' ::');
$objPagina->montarStyle();
?>
<style type="text/css">

.input-label-first{position:absolute;left:0%;top:0%;width:25%; color: #666!important}
.input-field-first{position:absolute;left:0%;top:50%;width:25%}    

.input-label-second{position:absolute;left:30%;top:0%;width:25%; color: #666!important}
.input-field-second{position:absolute;left:30%;top:50%;width:25%;}

.input-label-third {position:absolute;left:0%;top:40%;width:25%; color:#666!important}
.input-field-third {position:absolute;left:0%;top:55%;width:25%;}

</style>
<?php $objPagina->montarJavaScript(); ?>
<script type="text/javascript">

function inicializar(){

  infraEfeitoTabelas();

}

function onClickBtnPesquisar(){
  document.getElementById('frmAcompanharEstadoProcesso').action='<?php print $objSessao->assinarLink('controlador.php?acao='.htmlspecialchars($_GET['acao']).'&acao_origem='.htmlspecialchars($_GET['acao_origem']).'&acao_retorno='.htmlspecialchars($_GET['acao_retorno'])); ?>';
  document.getElementById('frmAcompanharEstadoProcesso').submit();
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

    window.location = '<?php print $objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_cadastrar&acao_origem='.htmlspecialchars($_GET['acao_origem']).'&acao_retorno='.htmlspecialchars($_GET['acao_origem'])); ?>';
}

function onClickBtnAtivar(){

   try {

        var form = jQuery('#frmAcompanharEstadoProcesso');
        form.attr('action', '<?php print $objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_ativar&acao_origem='.htmlspecialchars($_GET['acao_origem']).'&acao_retorno='.PEN_RECURSO_BASE.'_listar'); ?>');
        form.submit();
    }
    catch(e){

        alert('Erro : ' + e.message);
    } 

}

function onClickBtnDesativar(){

    try {

        var form = jQuery('#frmAcompanharEstadoProcesso');
        form.attr('action', '<?php print $objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_desativar&acao_origem='.htmlspecialchars($_GET['acao_origem']).'&acao_retorno='.PEN_RECURSO_BASE.'_listar'); ?>');
        form.submit();
    }
    catch(e){

        alert('Erro : ' + e.message);
    }
}

function onClickBtnExcluir(){

    try {

        var len = jQuery('input[name*=chkInfraItem]:checked').length;

        if(len > 0){

            if(confirm('Confirma a exclusão de ' + len + ' mapeamento(s) ?')) {
                var form = jQuery('#frmAcompanharEstadoProcesso');
                form.attr('action', '<?php print $objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_excluir&acao_origem='.htmlspecialchars($_GET['acao_origem']).'&acao_retorno='.PEN_RECURSO_BASE.'_listar'); ?>');
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

</script>
<?php
$objPagina->fecharHead();
$objPagina->abrirBody(PEN_PAGINA_TITULO, 'onload="inicializar();"');
?>
<form id="frmAcompanharEstadoProcesso" method="post" action="">

    <?php $objPagina->montarBarraComandosSuperior($arrComandos); ?>
    <?php //$objPagina->montarAreaValidacao(); ?>
    <?php $objPagina->abrirAreaDados('5em'); ?>

        <label for="id_hipotese_legal" class="infraLabelObrigatorio input-label-first">Hipótese Legal SEI - <?php print $objSessao->getStrSiglaOrgaoUnidadeAtual(); ?>:</label>
        <select name="id_hipotese_legal" class="infraSelect input-field-first"<?php if($bolSomenteLeitura) : ?>  disabled="disabled" readonly="readonly"<?php 
       endif; ?>>
            <?php print InfraINT::montarSelectArray('', 'Selecione', $objFiltroDTO->getNumIdHipoteseLegal(), $arrMapIdHipoteseLegal); ?>
        </select>

        <label for="id_barramento" class="infraLabelObrigatorio input-label-second">Hipótese Legal Tramita GOV.BR:</label>
        <select name="id_barramento" class="infraSelect input-field-second"<?php if($bolSomenteLeitura) : ?> disabled="disabled" readonly="readonly"<?php 
       endif; ?>>
            <?php print InfraINT::montarSelectArray('', 'Selecione', $objFiltroDTO->getNumIdBarramento(), $arrMapIdBarramento); ?>
        </select> 


    <?php $objPagina->fecharAreaDados(); ?>

    <?php if($numRegistros > 0) : ?>
        <?php $objPagina->montarAreaTabela($strResultado, $numRegistros); ?>
        <?php //$objPagina->montarAreaDebug(); ?>
<?php else: ?>
        <div style="clear:both;margin:2em"></div>
        <p>Nenhum mapeamento foi encontrado</p>
<?php endif; ?>
</form>
<?php $objPagina->fecharBody(); ?>
<?php $objPagina->fecharHtml(); ?>
