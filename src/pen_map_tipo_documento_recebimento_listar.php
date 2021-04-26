<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Consulta os logs do estado do procedimento ao ser expedido
 * 
 *
 */

session_start();

InfraDebug::getInstance()->setBolLigado(false);
InfraDebug::getInstance()->setBolDebugInfra(true);
InfraDebug::getInstance()->limpar();

$objPaginaSEI = PaginaSEI::getInstance();
$objSessaoSEI = SessaoSEI::getInstance();

$objPenRelTipoDocMapRecebidoRN = new PenRelTipoDocMapRecebidoRN();
 
$strProprioLink = 'controlador.php?acao='.$_GET['acao'].'&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao_retorno'];

try {
    
    $objSessaoSEI->validarLink();
    $objSessaoSEI->validarPermissao('pen_map_tipo_documento_recebimento_listar');
    
    $objPenRelTipoDocMapRecebidoRN = new PenRelTipoDocMapRecebidoRN();

    //--------------------------------------------------------------------------
    // A��es
    if(array_key_exists('acao', $_GET)) {
        
        $arrParam = array_merge($_GET, $_POST);
        
        switch($_GET['acao']) {
            
            case 'pen_map_tipo_documento_recebimento_excluir':                
                if(array_key_exists('hdnInfraItensSelecionados', $arrParam) && !empty($arrParam['hdnInfraItensSelecionados'])) {                    
                    $arrDblIdMap = explode(',', $arrParam['hdnInfraItensSelecionados']);
                    $arrPenRelTipoDocMapRecebidoDTO = array_map(function($parDblIdMap){
                        $objPenRelTipoDocMapRecebidoDTO = new PenRelTipoDocMapRecebidoDTO();
                        $objPenRelTipoDocMapRecebidoDTO->setDblIdMap($parDblIdMap);
                        return $objPenRelTipoDocMapRecebidoDTO;
                    }, $arrDblIdMap);

                    $objPenRelTipoDocMapRecebidoRN->excluir($arrPenRelTipoDocMapRecebidoDTO);
                    $objPaginaSEI->adicionarMensagem('Excluido com sucesso.', InfraPagina::$TIPO_MSG_INFORMACAO);                    
                    header('Location: '.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao_retorno'].'&acao_origem='.$_GET['acao_origem']));
                    exit(0);
                }
                else {                    
                    throw new InfraException('Nenhum Registro foi selecionado para executar esta a��o');
                }
                break;
                
            case 'pen_map_tipo_documento_recebimento_listar':
                // A��o padr�o desta tela
                break;
                
            default:
                throw new InfraException('A��o n�o permitida nesta tela');
            
        }
    }
    //--------------------------------------------------------------------------
    
    $strTitulo = 'Lista dos Mapeamentos de Tipos de Documento para Recebimento';

    $strBotaoTipoDocumentoPadrao = "";
    if(SessaoSEI::getInstance()->verificarPermissao('pen_map_tipo_doc_recebimento_padrao_consultar')){
        $strBotaoTipoDocumentoPadrao = '<button type="button" accesskey="C" onclick="location.href=\''.$objSessaoSEI->assinarLink('controlador.php?acao=pen_map_tipo_doc_recebimento_padrao_consultar&acao_origem='.$_GET['acao'].'&acao_retorno='.$_GET['acao']).'\'" id="btnConsultarPadrao" value="Consultar Tipo de Documento padr�o" class="infraButton"><span class="infraTeclaAtalho">C</span>onsultar Tipo de Documento Padr�o</button>';
    }
        
    if(SessaoSEI::getInstance()->verificarPermissao('pen_map_tipo_doc_recebimento_padrao_atribuir')){
        $objPenRelTipoDocMapRecebidoRN = new PenRelTipoDocMapRecebidoRN();
        $bolPadraoNaoAtribuido = empty($objPenRelTipoDocMapRecebidoRN->consultarTipoDocumentoPadrao());
        $strClassePendencia = ($bolPadraoNaoAtribuido) ? "pendencia" : "";
        $strAltPendencia = ($bolPadraoNaoAtribuido) ? "Pendente atribui��o de Tipo de Documento padr�o para recebimento de processos" : "";
        $strBotaoTipoDocumentoPadrao = '<button type="button" accesskey="A" onclick="location.href=\''.$objSessaoSEI->assinarLink('controlador.php?acao=pen_map_tipo_doc_recebimento_padrao_atribuir&acao_origem='.$_GET['acao'].'&acao_retorno='.$_GET['acao']).'\'" id="btnAtribuirPadrao" title="'.$strAltPendencia.'" class="infraButton"><span class="'.$strClassePendencia.'"></span><span class="infraTeclaAtalho">A</span>tribuir Tipo de Documento Padr�o</button>';
    }    

    $arrComandos = array();
    $arrComandos[] = '<button type="button" accesskey="P" onclick="onClickBtnPesquisar();" id="btnPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';
    $arrComandos[] = $strBotaoTipoDocumentoPadrao;
    $arrComandos[] = '<button type="button" value="Novo" onclick="onClickBtnNovo()" class="infraButton"><span class="infraTeclaAtalho">N</span>ovo</button>';
    $arrComandos[] = '<button type="button" value="Excluir" onclick="onClickBtnExcluir()" class="infraButton"><span class="infraTeclaAtalho">E</span>xcluir</button>';
    $arrComandos[] = '<button type="button" accesskey="I" id="btnImprimir" value="Imprimir" onclick="infraImprimirTabela();" class="infraButton"><span class="infraTeclaAtalho">I</span>mprimir</button>';

    //--------------------------------------------------------------------------
    // DTO de pagina��o
    
    $objPenRelTipoDocMapRecebidoDTO = new PenRelTipoDocMapRecebidoDTO();
    $objPenRelTipoDocMapRecebidoDTO->retTodos(true);
    $objPenRelTipoDocMapRecebidoDTO->setOrdStrNomeEspecie(InfraDTO::$TIPO_ORDENACAO_ASC);
    //--------------------------------------------------------------------------
    // Filtragem 
    
    if(array_key_exists('nome_serie', $_POST) && !empty($_POST['nome_serie'])) {       
        $objPenRelTipoDocMapRecebidoDTO->setStrNomeSerie('%'.$_POST['nome_serie'].'%', InfraDTO::$OPER_LIKE);
    } 
    
    if(array_key_exists('nome_especie', $_POST) && !empty($_POST['nome_especie'])) {       
        $objPenRelTipoDocMapRecebidoDTO->setStrNomeEspecie('%'.$_POST['nome_especie'].'%', InfraDTO::$OPER_LIKE);
    } 
    //--------------------------------------------------------------------------

    $objPaginaSEI->prepararOrdenacao($objPenRelTipoDocMapRecebidoDTO, 'CodigoEspecie', InfraDTO::$TIPO_ORDENACAO_ASC);
    $objPaginaSEI->prepararPaginacao($objPenRelTipoDocMapRecebidoDTO);
    
    $arrObjPenRelTipoDocMapRecebidoDTO = $objPenRelTipoDocMapRecebidoRN->listar($objPenRelTipoDocMapRecebidoDTO);
    
    $objPaginaSEI->processarPaginacao($objPenRelTipoDocMapRecebidoDTO);
    $numRegistros = count($arrObjPenRelTipoDocMapRecebidoDTO);

    if(!empty($arrObjPenRelTipoDocMapRecebidoDTO)){
        
        $strResultado = '';

        $strResultado .= '<table width="99%" class="infraTable">'."\n";
        $strResultado .= '<caption class="infraCaption">'.$objPaginaSEI->gerarCaptionTabela('estados do processo', $numRegistros).'</caption>';

        $strResultado .= '<tr>';
        $strResultado .= '<th class="infraTh" width="1%">'.$objPaginaSEI->getThCheck().'</th>'."\n";
        $strResultado .= '<th class="infraTh" width="35%">Esp�cie Documental PEN</th>'."\n";
        $strResultado .= '<th class="infraTh" width="35%">Tipo de Documento SEI</th>'."\n";
        $strResultado .= '<th class="infraTh" width="14%">A��es</th>'."\n";
        $strResultado .= '</tr>'."\n";
        $strCssTr = '';

        $index = 0;
        foreach($arrObjPenRelTipoDocMapRecebidoDTO as $objPenRelTipoDocMapRecebidoDTO) {

            $strCssTr = ($strCssTr == 'infraTrClara') ? 'infraTrEscura' : 'infraTrClara';

            $strResultado .= '<tr class="'.$strCssTr.'">';
            $strResultado .= '<td>'.$objPaginaSEI->getTrCheck($index, $objPenRelTipoDocMapRecebidoDTO->getDblIdMap(), '').'</td>';
            $strResultado .= '<td>'.$objPenRelTipoDocMapRecebidoDTO->getStrNomeEspecie().'</td>';
            $strResultado .= '<td>'.$objPenRelTipoDocMapRecebidoDTO->getStrNomeSerie().'</td>';
            $strResultado .= '<td align="center">';
            
            if($objSessaoSEI->verificarPermissao('pen_map_tipo_documento_recebimento_visualizar')) {
                $strResultado .= '<a href="'.$objSessaoSEI->assinarLink('controlador.php?acao=pen_map_tipo_documento_recebimento_visualizar&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao'].'&id_mapeamento='.$objPenRelTipoDocMapRecebidoDTO->getDblIdMap()).'"><img src=' . ProcessoEletronicoINT::getCaminhoIcone("imagens/consultar.gif") . ' title="Consultar Mapeamento" alt="Consultar Mapeamento" class="infraImg"></a>';
            }
            if($objSessaoSEI->verificarPermissao('pen_map_tipo_documento_recebimento_alterar')) {
                $strResultado .= '<a href="'.$objSessaoSEI->assinarLink('controlador.php?acao=pen_map_tipo_documento_recebimento_cadastrar&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao'].'&id_mapeamento='.$objPenRelTipoDocMapRecebidoDTO->getDblIdMap()).'"><img src=' . ProcessoEletronicoINT::getCaminhoIcone("imagens/alterar.gif") . ' title="Alterar Mapeamento" alt="Alterar Mapeamento" class="infraImg"></a>';
            }
            if($objSessaoSEI->verificarPermissao('pen_map_tipo_documento_recebimento_excluir')) {   
                $strResultado .= '<a href="#" onclick="onCLickLinkDelete(\''.$objSessaoSEI->assinarLink('controlador.php?acao=pen_map_tipo_documento_recebimento_excluir&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao'].'&hdnInfraItensSelecionados='.$objPenRelTipoDocMapRecebidoDTO->getDblIdMap()).'\', this)"><img src=' . ProcessoEletronicoINT::getCaminhoIcone("imagens/excluir.gif") . ' title="Excluir Mapeamento" alt="Excluir Mapeamento" class="infraImg"></a>';
            }
            
            $strResultado .= '</td>';
            $strResultado .= '</tr>'."\n";

            $index++;
        }
        $strResultado .= '</table>';
    }
}
catch(InfraException $e){
    $objPaginaSEI->processarExcecao($e);
} 

$objPaginaSEI->montarDocType();
$objPaginaSEI->abrirHtml();
$objPaginaSEI->abrirHead();
$objPaginaSEI->montarMeta();
$objPaginaSEI->montarTitle(':: '.$objPaginaSEI->getStrNomeSistema().' - '.$strTitulo.' ::');
$objPaginaSEI->montarStyle();
?>
<style type="text/css">

.input-label-first{position:absolute;left:0%;top:0%;width:25%; color: #666!important}
.input-field-first{position:absolute;left:0%;top:50%;width:25%}    

.input-label-second{position:absolute;left:30%;top:0%;width:25%; color: #666!important}
.input-field-second{position:absolute;left:30%;top:50%;width:25%;}

.input-label-third {position:absolute;left:0%;top:40%;width:25%; color:#666!important}
.input-field-third {position:absolute;left:0%;top:55%;width:25%;}

#btnAtribuirPadrao{
    position: relative;
}

#btnAtribuirPadrao .pendencia {
  position: absolute;
  top: -5px;
  right: -4px;
  padding: 5px 5px;
  border-radius: 50%;
  background: red;
  color: white;
}

</style>
<?php $objPaginaSEI->montarJavaScript(); ?>
<script type="text/javascript">

function inicializar(){

  infraEfeitoTabelas();
  
  var strMensagens = '<?php print str_replace("\n", '\n', $objPaginaSEI->getStrMensagens()); ?>';
   
   if(strMensagens) {
       
       alert(strMensagens);
   }
}

function onClickBtnPesquisar(){
  document.getElementById('frmAcompanharEstadoProcesso').action='<?php print $objSessaoSEI->assinarLink($strProprioLink); ?>';
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
    
    if(confirm('Confirma a exclus�o do mapeamento "' + strEspecieDocumental + ' x ' + strTipoDocumento +'"?')){        
        window.location = url;
    }
    
}

function onClickBtnNovo(){    
    window.location = '<?php print $objSessaoSEI->assinarLink('controlador.php?acao=pen_map_tipo_documento_recebimento_cadastrar&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao_origem']); ?>';
}

function onClickBtnExcluir(){    
    try {        
        var len = jQuery('input[name*=chkInfraItem]:checked').length;        
        if(len > 0){        
            if(confirm('Confirma a exclus�o de ' + len + ' mapeamento(s) ?')) {
                var form = jQuery('#frmAcompanharEstadoProcesso');
                form.attr('action', '<?php print $objSessaoSEI->assinarLink('controlador.php?acao=pen_map_tipo_documento_recebimento_excluir&acao_origem='.$_GET['acao_origem'].'&acao_retorno=pen_map_tipo_documento_recebimento_listar'); ?>');
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
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody($strTitulo,'onload="inicializar();"');
?>
<form id="frmAcompanharEstadoProcesso" method="post" action="">
    <?php $objPaginaSEI->montarBarraComandosSuperior($arrComandos); ?>
    <?php $objPaginaSEI->abrirAreaDados('40px'); ?>
        <label for="nome_especie" class="infraLabelObrigatorio input-label-first">Esp�cie Documental PEN:</label>
        <input type="text" name="nome_especie"  class="infraText input-field-first" onkeyup="return tratarEnter(event)" value="<?php print $_POST['nome_especie']; ?>"/>

        <label for="nome_serie" class="infraLabelObrigatorio input-label-second">Tipo de Documento SEI:</label>
        <input type="text" name="nome_serie"  class="infraText input-field-second" onkeyup="return tratarEnter(event)" value="<?php print $_POST['nome_serie']; ?>"/>
    <?php $objPaginaSEI->fecharAreaDados(); ?>
    
    <?php if($numRegistros > 0): ?>
        <?php $objPaginaSEI->montarAreaTabela($strResultado, $numRegistros); ?>
    <?php else: ?>
        <div style="clear:both"></div>
        <p>Nenhum estado foi encontrado para este procedimento</p>
    <?php endif; ?>
</form>
<?php $objPaginaSEI->fecharBody(); ?>
<?php $objPaginaSEI->fecharHtml(); ?>

