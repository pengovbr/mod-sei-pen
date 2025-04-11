<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Consulta os logs do estado do procedimento ao ser expedido
 */

session_start();

define('PEN_RECURSO_ATUAL', 'pen_map_unidade_listar');
define('PEN_RECURSO_BASE', 'pen_map_unidade');
define('PEN_PAGINA_TITULO', 'Mapeamento de Unidades');
define('PEN_PAGINA_GET_ID', 'id_unidade');


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

            $objPenUnidadeDTO = new PenUnidadeDTO();
            $objPenUnidadeRN = new PenUnidadeRN();

            $arrParam['hdnInfraItensSelecionados'] = explode(',', $arrParam['hdnInfraItensSelecionados']);

          if(is_array($arrParam['hdnInfraItensSelecionados'])) {

            foreach($arrParam['hdnInfraItensSelecionados'] as $NumIdUnidade) {

                $objPenUnidadeDTO->setNumIdUnidade($NumIdUnidade);
                $objPenUnidadeRN->excluir($objPenUnidadeDTO);
            }
          }
          else {

              $objPenUnidadeDTO->setNumIdUnidade($arrParam['hdnInfraItensSelecionados']);
              $objPenUnidadeRN->excluir($objPenUnidadeDTO);
          }

            $objPagina->adicionarMensagem(sprintf('%s foi excluido com sucesso.', PEN_PAGINA_TITULO), InfraPagina::$TIPO_MSG_AVISO);

            header('Location: '.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.htmlspecialchars($_GET['acao_retorno']).'&acao_origem='.htmlspecialchars($_GET['acao_origem'])));
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
    $objPenUnidadeDTOFiltro = new PenUnidadeDTO();
    $objPenUnidadeDTOFiltro->retStrSigla();
    $objPenUnidadeDTOFiltro->retStrDescricao();
    $objPenUnidadeDTOFiltro->retNumIdUnidade();
    $objPenUnidadeDTOFiltro->retNumIdUnidadeRH();
    $objPenUnidadeDTOFiltro->retStrNomeUnidadeRH();
    $objPenUnidadeDTOFiltro->retStrSiglaUnidadeRH();

    //--------------------------------------------------------------------------
    // Filtragem
  if (isset($_POST['txtSiglaUnidade']) && $_POST['txtSiglaUnidade'] !== null) {
      $objPenUnidadeDTOFiltro->setStrSigla('%' . $_POST['txtSiglaUnidade'] . '%', InfraDTO::$OPER_LIKE);
  }

  if (isset($_POST['txtDescricaoUnidade']) && $_POST['txtDescricaoUnidade'] !== null) {
      $objPenUnidadeDTOFiltro->setStrDescricao('%'.$_POST['txtDescricaoUnidade'].'%', InfraDTO::$OPER_LIKE);
  }

    $objFiltroDTO = clone $objPenUnidadeDTOFiltro;

  if(!$objFiltroDTO->isSetStrSigla()) {
      $objFiltroDTO->setStrSigla('');
  }

  if(!$objFiltroDTO->isSetStrDescricao()) {
      $objFiltroDTO->setStrDescricao('');
  }

    //--------------------------------------------------------------------------
    $objGenericoBD = new GenericoBD($objBanco);

    // Unidade Local
    $objPenUnidadeDTO = new PenUnidadeDTO();
    $objPenUnidadeDTO->setDistinct(true);
    $objPenUnidadeDTO->retNumIdUnidade();
    $objPenUnidadeDTO->retNumIdUnidadeRH();

    $objPenUnidadeRN = new PenUnidadeRN();
    $objArrPenUnidadeDTO = $objPenUnidadeRN->listar($objPenUnidadeDTO);
    $arrMapIdUnidade = InfraArray::converterArrInfraDTO($objArrPenUnidadeDTO, 'IdUnidade', 'IdUnidade');
    $arrMapIdUnidadeRH = InfraArray::converterArrInfraDTO($objArrPenUnidadeDTO, 'IdUnidadeRH', 'IdUnidadeRH');

    $objPagina->prepararOrdenacao($objPenUnidadeDTOFiltro, 'IdUnidade', InfraDTO::$TIPO_ORDENACAO_ASC);
    $objPagina->prepararPaginacao($objPenUnidadeDTOFiltro);
    $arrObjPenUnidadeDTO = $objGenericoBD->listar($objPenUnidadeDTOFiltro);
    $objPagina->processarPaginacao($objPenUnidadeDTOFiltro);

    $numRegistros = count($arrObjPenUnidadeDTO);
  if(!empty($arrObjPenUnidadeDTO)) {

      $strResultado = '';

      $strResultado .= '<table width="99%" class="infraTable" border="1">'."\n";
      $strResultado .= '<caption class="infraCaption">'.$objPagina->gerarCaptionTabela(PEN_PAGINA_TITULO, $numRegistros).'</caption>';

      $strResultado .= '<tr>';
      $strResultado .= '<th class="infraTh"></th>';
      $strResultado .= '<th class="infraTh" colspan="3">SEI</th>';
      $strResultado .= '<th class="infraTh" colspan="3">Tramita GOV.BR</th>';
      $strResultado .= '<th class="infraTh"></th>';
      $strResultado .= '</tr>';

      $strResultado .= '<tr>';
      $strResultado .= '<th class="infraTh" id="thCheck" width="1%">'.$objPagina->getThCheck().'</th>'."\n";
      $strResultado .= '<th class="infraTh" id="thIdUnidadeSei" width="2%">ID</th>'."\n";
      $strResultado .= '<th class="infraTh" id="thSiglaUnidadeSei" width="3%">Sigla</th>'."\n";
      $strResultado .= '<th class="infraTh" id="thDescricaoUnidadeSei" width="30%">Descrição</th>'."\n";
      $strResultado .= '<th class="infraTh" id="thIdUnidadeTramitaGovBr" width="1%">ID</th>'."\n";
      $strResultado .= '<th class="infraTh" id="thSiglaUnidadeTramitaGovBr" width="10%">Sigla</th>'."\n";
      $strResultado .= '<th class="infraTh" id="thDescricaoUnidadeTramitaGovBr" width="30%">Descrição</th>'."\n";
      $strResultado .= '<th class="infraTh" id="thAcoes" width="9%">Ações</th>'."\n";
      $strResultado .= '</tr>'."\n";
      $strCssTr = '';

      $index = 0;

    foreach($arrObjPenUnidadeDTO as $objPenUnidadeDTO) {
        $strCssTr = ($strCssTr == 'infraTrClara') ? 'infraTrEscura' : 'infraTrClara';

        $strResultado .= '<tr class="'.$strCssTr.'">';
        $strResultado .= '<td>'.$objPagina->getTrCheck($index, $objPenUnidadeDTO->getNumIdUnidade(), '').'</td>';
        $strResultado .= '<td align="center">'.$objPenUnidadeDTO->getNumIdUnidade().'</td>';
        $strResultado .= '<td align="center">'.$objPenUnidadeDTO->getStrSigla().'</td>';
        $strResultado .= '<td>'.$objPenUnidadeDTO->getStrDescricao().'</td>';
        $strResultado .= '<td>'.$arrMapIdUnidadeRH[$objPenUnidadeDTO->getNumIdUnidadeRH()].'</td>';
        $strResultado .= '<td align="center">'.$objPenUnidadeDTO->getStrSiglaUnidadeRH().'</td>';
        $strResultado .= '<td>'.$objPenUnidadeDTO->getStrNomeUnidadeRH().'</td>';
        $strResultado .= '<td align="center">';

      if($objSessao->verificarPermissao('pen_map_unidade_alterar')) {
        $strResultado .= '<a href="'.$objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_cadastrar&acao_origem='.htmlspecialchars($_GET['acao_origem']).'&acao_retorno='.htmlspecialchars($_GET['acao']).'&'.PEN_PAGINA_GET_ID.'='.$objPenUnidadeDTO->getNumIdUnidade()).'"><img src=' . ProcessoEletronicoINT::getCaminhoIcone("imagens/alterar.gif") . ' title="Alterar Mapeamento" alt="Alterar Mapeamento" class="infraImg"></a>';
      }

      if($objSessao->verificarPermissao('pen_map_unidade_excluir')) {
          $strResultado .= '<a href="#" onclick="onCLickLinkDelete(\''.$objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_excluir&acao_origem='.htmlspecialchars($_GET['acao_origem']).'&acao_retorno='.htmlspecialchars($_GET['acao']).'&hdnInfraItensSelecionados='.$objPenUnidadeDTO->getNumIdUnidade()).'\', this)"><img src=' . ProcessoEletronicoINT::getCaminhoIcone("imagens/excluir.gif") . ' title="Excluir Mapeamento" alt="Excluir Mapeamento" class="infraImg"></a>';
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

#lblSiglaUnidade{position:absolute;left:0%;top:0%;width:25%; }
#txtSiglaUnidade{position:absolute;left:0%;top:50%;width:25%}

#lblDescricaoUnidade{position:absolute;left:30%;top:0%;width:25%; }
#txtDescricaoUnidade{position:absolute;left:30%;top:50%;width:25%;}
#thCheck{min-width:1%;max-width:1%;}
#thIdUnidadeSei{min-width:2%;max-width:2%;}
#thSiglaUnidadeSei{min-width:3%;max-width:3%;}
#thDescricaoUnidadeSei{min-width:30%;max-width:30%;}
#thIdUnidadeTramitaGovBr{min-width:1%;max-width:1%;}
#thSiglaUnidadeTramitaGovBr{min-width:10%;max-width:10%;}
#thDescricaoUnidadeTramitaGovBr{min-width:30%;max-width:30%;}
#thAcoes{min-width:9%;max-width:9%;width:9%}

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

    if(confirm('Confirma a exclusão do mapeamento da unidade?')){

        window.location = url;
    }

}

function onClickBtnNovo(){

    window.location = '<?php print $objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_cadastrar&acao_origem='.htmlspecialchars($_GET['acao_origem']).'&acao_retorno='.htmlspecialchars($_GET['acao_origem'])); ?>';
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

        <label for="txtSiglaUnidade" id="lblSiglaUnidade" class="infraLabelOpcional">Sigla:</label>
        <input type="text" id="txtSiglaUnidade" name="txtSiglaUnidade" class="infraText"  value="<?php echo PaginaSEI::tratarHTML(htmlspecialchars(isset($_POST['sigla'])) ? htmlspecialchars($_POST['sigla']) : ''); ?>">

        <label for="txtDescricaoUnidade" id="lblDescricaoUnidade" class="infraLabelOpcional">Descrição:</label>
        <input type="text" id="txtDescricaoUnidade" name="txtDescricaoUnidade" class="infraText" value="<?php echo PaginaSEI::tratarHTML(htmlspecialchars(isset($_POST['descricao'])) ? htmlspecialchars($_POST['descricao']) : ''); ?>">

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
