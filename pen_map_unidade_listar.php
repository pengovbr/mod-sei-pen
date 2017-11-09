<?php

require_once dirname(__FILE__) . '/../../SEI.php';

/**
 * Consulta os logs do estado do procedimento ao ser expedido
 * 
 * @author Join Tecnologia
 */

session_start();

define('PEN_RECURSO_ATUAL', 'pen_map_unidade_listar');
define('PEN_RECURSO_BASE', 'pen_map_unidade');
define('PEN_PAGINA_TITULO', 'Mapeamento de Unidade');
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
    
    $objBanco->abrirConexao();
    
    
    //--------------------------------------------------------------------------
    // Ações
    if(array_key_exists('acao', $_GET)) {
        
        $arrParam = array_merge($_GET, $_POST);
        
        switch($_GET['acao']) {
            
            case PEN_RECURSO_BASE.'_excluir':
                
                if(array_key_exists('hdnInfraItensSelecionados', $arrParam) && !empty($arrParam['hdnInfraItensSelecionados'])) {
                    
                    $objPenUnidadeDTO = new PenUnidadeDTO();
                    $objPenUnidadeRN = new PenUnidadeRN();
                    
                    $arrParam['hdnInfraItensSelecionados'] = explode(',',$arrParam['hdnInfraItensSelecionados']);
                    
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
                    
                    header('Location: '.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao_retorno'].'&acao_origem='.$_GET['acao_origem']));
                    exit(0);
                }
                else {
                    
                    throw new InfraException('Nenhum Registro foi selecionado para executar esta ação');
                }
                break;
            
            case PEN_RECURSO_BASE.'_desativar':
                
                if(array_key_exists('hdnInfraItensSelecionados', $arrParam) && !empty($arrParam['hdnInfraItensSelecionados'])) {
                    
                    PenUnidadeRN::mudarEstado(explode(',', $arrParam['hdnInfraItensSelecionados']), 'N');
                    
                    $objPagina->adicionarMensagem('Desativado com sucesso.', InfraPagina::$TIPO_MSG_AVISO);
                    
                    header('Location: '.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao_retorno'].'&acao_origem='.$_GET['acao_origem']));
                    exit(0);
                }
                else {
                    
                    throw new InfraException('Nenhum Registro foi selecionado para executar esta ação');
                }
                break;
                
            case PEN_RECURSO_BASE.'_ativar':
                if(array_key_exists('hdnInfraItensSelecionados', $arrParam) && !empty($arrParam['hdnInfraItensSelecionados'])) {
                    
                    PenUnidadeRN::mudarEstado(explode(',', $arrParam['hdnInfraItensSelecionados']), 'S');
                    
                    $objPagina->adicionarMensagem('Ativado com sucesso.', InfraPagina::$TIPO_MSG_AVISO);
                    
                    header('Location: '.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao_retorno'].'&acao_origem='.$_GET['acao_origem']));
                    exit(0);
                }
                break;
                
            case PEN_RECURSO_BASE.'_listar':
                // Ação padrão desta tela
                break;
                
            default:
                throw new InfraException('Ação não permitida nesta tela');
            
        }
    }
    //--------------------------------------------------------------------------
  
    $arrComandos = array();
    $arrComandos[] = '<button type="button" accesskey="P" onclick="onClickBtnPesquisar();" id="btnPesquisar" value="Pesquisar" class="infraButton">Pesquisar</button>';
    $arrComandos[] = '<button type="button" value="Novo" onclick="onClickBtnNovo()" class="infraButton">Novo</button>';
    //$arrComandos[] = '<button type="button" value="Ativar" onclick="onClickBtnAtivar()" class="infraButton">Ativar</button>';
    //$arrComandos[] = '<button type="button" value="Desativar" onclick="onClickBtnDesativar()" class="infraButton">Desativar</button>';
    $arrComandos[] = '<button type="button" value="Excluir" onclick="onClickBtnExcluir()" class="infraButton">Excluir</button>';
    $arrComandos[] = '<button type="button" accesskey="I" id="btnImprimir" value="Imprimir" onclick="infraImprimirTabela();" class="infraButton">Imprimir</button>';

    //--------------------------------------------------------------------------
    // DTO de paginao
    
    $objPenUnidadeDTOFiltro = new PenUnidadeDTO();
    $objPenUnidadeDTOFiltro->retTodos();
    
    //--------------------------------------------------------------------------
    // Filtragem 
    if(array_key_exists('id_unidade_rh', $_POST) && (!empty($_POST['id_unidade_rh']) && $_POST['id_unidade_rh'] !== 'null')) {
        $objPenUnidadeDTOFiltro->setNumIdUnidadeRH($_POST['id_unidade_rh']);
    }
    
    if(array_key_exists('id_unidade', $_POST) && (!empty($_POST['id_unidade']) && $_POST['id_unidade_rh'] !== 'null')) {
        $objPenUnidadeDTOFiltro->setNumIdUnidade($_POST['id_unidade']);
    } 
    
    $objFiltroDTO = clone $objPenUnidadeDTOFiltro;
    
    if(!$objFiltroDTO->isSetNumIdUnidadeRH()) {
        $objFiltroDTO->setNumIdUnidadeRH('');   
    }

    if(!$objFiltroDTO->isSetNumIdUnidade()) {
        $objFiltroDTO->setNumIdUnidade('');
    }
    //--------------------------------------------------------------------------
    $objGenericoBD = new GenericoBD($objBanco);
    
    // Unidade Local
    $objPenUnidadeDTO = new PenUnidadeDTO();
    $objPenUnidadeDTO->setDistinct(true);
    $objPenUnidadeDTO->retNumIdUnidade();
    $objPenUnidadeDTO->retNumIdUnidadeRH();
   
    $objPenUnidadeRN = new PenUnidadeRN();
    $arrMapIdUnidade = InfraArray::converterArrInfraDTO($objPenUnidadeRN->listar($objPenUnidadeDTO), 'IdUnidade', 'IdUnidade');
    $arrMapIdUnidadeRH = InfraArray::converterArrInfraDTO($objPenUnidadeRN->listar($objPenUnidadeDTO), 'IdUnidadeRH', 'IdUnidadeRH');
    
    
    $objPagina->prepararPaginacao($objPenUnidadeDTOFiltro);
    $arrObjPenUnidadeDTO = $objGenericoBD->listar($objPenUnidadeDTOFiltro);
    $objPagina->processarPaginacao($objPenUnidadeDTOFiltro);
    
    $numRegistros = count($arrObjPenUnidadeDTO);
    if(!empty($arrObjPenUnidadeDTO)){
        
        $strResultado = '';
        
        $strResultado .= '<table width="99%" class="infraTable">'."\n";
        $strResultado .= '<caption class="infraCaption">'.$objPagina->gerarCaptionTabela(PEN_PAGINA_TITULO, $numRegistros).'</caption>';

        $strResultado .= '<tr>';
        $strResultado .= '<th class="infraTh" width="1%">'.$objPagina->getThCheck().'</th>'."\n";
        $strResultado .= '<th class="infraTh" width="35%">Unidade</th>'."\n";
        $strResultado .= '<th class="infraTh" width="35%">Unidade RH</th>'."\n";
        $strResultado .= '<th class="infraTh" width="14%">Ações</th>'."\n";
        $strResultado .= '</tr>'."\n";
        $strCssTr = '';

        $index = 0;
        
        foreach($arrObjPenUnidadeDTO as $objPenUnidadeDTO) {
            $strCssTr = ($strCssTr == 'infraTrClara') ? 'infraTrEscura' : 'infraTrClara';
            
            $objPenUnidadeSiglaDTO = new UnidadeDTO();
            $objPenUnidadeSiglaDTO->setNumIdUnidade($objPenUnidadeDTO->getNumIdUnidade());
            $objPenUnidadeSiglaDTO->retStrSigla();

            $objPenUnidadeRN = new UnidadeRN();
            $objResultadoSigla = $objGenericoBD->consultar($objPenUnidadeSiglaDTO);
            
            $strResultado .= '<tr class="'.$strCssTr.'">';
            $strResultado .= '<td>'.$objPagina->getTrCheck($index, $objPenUnidadeDTO->getNumIdUnidade(), '').'</td>';
            $strResultado .= '<td>'.$objResultadoSigla->getStrSigla().'</td>';
            $strResultado .= '<td>'.$arrMapIdUnidadeRH[$objPenUnidadeDTO->getNumIdUnidadeRH()].'</td>';
            $strResultado .= '<td align="center">';
            
            //$strResultado .= '<a href="'.$objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_visualizar&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao'].'&'.PEN_PAGINA_GET_ID.'='.$objPenUnidadeDTO->getNumIdUnidade()).'"><img src="imagens/consultar.gif" title="Consultar Mapeamento" alt="Consultar Mapeamento" class="infraImg"></a>';
            if($objSessao->verificarPermissao('pen_map_unidade_alterar')) {
                $strResultado .= '<a href="'.$objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_cadastrar&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao'].'&'.PEN_PAGINA_GET_ID.'='.$objPenUnidadeDTO->getNumIdUnidade()).'"><img src="imagens/alterar.gif" title="Alterar Mapeamento" alt="Alterar Mapeamento" class="infraImg"></a>';
            }
            
            if($objSessao->verificarPermissao('pen_map_unidade_excluir')) {
                $strResultado .= '<a href="#" onclick="onCLickLinkDelete(\''.$objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_excluir&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao'].'&hdnInfraItensSelecionados='.$objPenUnidadeDTO->getNumIdUnidade()).'\', this)"><img src="imagens/excluir.gif" title="Excluir Mapeamento" alt="Excluir Mapeamento" class="infraImg"></a>';
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
    //$objPagina->processarExcecao($e);
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
.input-field-first{position:absolute;left:0%;top:15%;width:25%}    

.input-label-second{position:absolute;left:30%;top:0%;width:25%; color: #666!important}
.input-field-second{position:absolute;left:30%;top:15%;width:25%;}

.input-label-third {position:absolute;left:0%;top:40%;width:25%; color:#666!important}
.input-field-third {position:absolute;left:0%;top:55%;width:25%;}

</style>
<?php $objPagina->montarJavaScript(); ?>
<script type="text/javascript">
    
function inicializar(){

  infraEfeitoTabelas();

}

function onClickBtnPesquisar(){
  document.getElementById('frmAcompanharEstadoProcesso').action='<?php print $objSessao->assinarLink('controlador.php?acao='.$_GET['acao'].'&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao_retorno']); ?>';
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
    
    window.location = '<?php print $objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_cadastrar&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao_origem']); ?>';
}

function onClickBtnAtivar(){
    
   try {
        
        var form = jQuery('#frmAcompanharEstadoProcesso');
        form.attr('action', '<?php print $objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_ativar&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.PEN_RECURSO_BASE.'_listar'); ?>');
        form.submit();
    }
    catch(e){
            
        alert('Erro : ' + e.message);
    } 
    
}

function onClickBtnDesativar(){
    
    try {
        
        var form = jQuery('#frmAcompanharEstadoProcesso');
        form.attr('action', '<?php print $objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_desativar&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.PEN_RECURSO_BASE.'_listar'); ?>');
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
                form.attr('action', '<?php print $objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_excluir&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.PEN_RECURSO_BASE.'_listar'); ?>');
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
$objPagina->abrirBody(PEN_PAGINA_TITULO,'onload="inicializar();"');
?>
<form id="frmAcompanharEstadoProcesso" method="post" action="<?php// print $objSessao->assinarLink($strProprioLink); ?>">
    
    <?php $objPagina->montarBarraComandosSuperior($arrComandos); ?>
    <?php //$objPagina->montarAreaValidacao(); ?>
    <?php $objPagina->abrirAreaDados('12em'); ?>
        
        <label for="id_unidade" class="infraLabelObrigatorio input-label-first">Unidade:</label>
        <select name="id_unidade" class="infraSelect input-field-first"<?php if($bolSomenteLeitura): ?>  disabled="disabled" readonly="readonly"<?php endif; ?>>
            <?php print InfraINT::montarSelectArray('', 'Selecione', $objFiltroDTO->getNumIdUnidade(), $arrMapIdUnidade); ?>
        </select>
        
        <label for="id_unidade_rh" class="infraLabelObrigatorio input-label-second">Unidade RH</label>
        <select name="id_unidade_rh" class="infraSelect input-field-second"<?php if($bolSomenteLeitura): ?> disabled="disabled" readonly="readonly"<?php endif; ?>>
            <?php print InfraINT::montarSelectArray('', 'Selecione', $objFiltroDTO->getNumIdUnidadeRH(),  $arrMapIdUnidadeRH); ?>
        </select> 
        
        
    <?php $objPagina->fecharAreaDados(); ?>
    
    <?php if($numRegistros > 0): ?>
        <?php $objPagina->montarAreaTabela($strResultado, $numRegistros); ?>
        <?php //$objPagina->montarAreaDebug(); ?>
    <?php else: ?>
        <div style="clear:both"></div>
        <p>Nenhum mapeamento foi encontrado</p>
    <?php endif; ?>
</form>
<?php $objPagina->fecharBody(); ?>
<?php $objPagina->fecharHtml(); ?>
