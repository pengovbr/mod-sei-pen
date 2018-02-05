<?php

require_once dirname(__FILE__) . '/../../SEI.php';

/**
 * Consulta os logs do estado do procedimento ao ser expedido
 * 
 * @author Join Tecnologia
 */
try {
    
  
    session_start();

    InfraDebug::getInstance()->setBolLigado(false);
    InfraDebug::getInstance()->setBolDebugInfra(true);
    InfraDebug::getInstance()->limpar();

    $objSessaoSEI = SessaoSEI::getInstance();
    
    $objSessaoSEI->validarLink();
    $objSessaoSEI->validarPermissao('pen_procedimento_expedir');
    
    if(array_key_exists('metodo', $_GET)) {
        
        ob_clean();
        
        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="recibo_de_confirmacao_do_envio.xml"');
        
        print '<?xml version="1.0" encoding="UTF-8" ? >'.PHP_EOL;
        
        $objBancoSEI = BancoSEI::getInstance();
        $objBancoSEI->abrirConexao();

        switch ($_GET['metodo']){
            
            // @join_tec US008.02 (#23092)
            case 'baixarReciboEnvio':
                print '<recibosDeEnvio>'.PHP_EOL;
                print '  <reciboDeEnvio>'.PHP_EOL;
                print '    <IDT><IDT>'.PHP_EOL;
                print '    <NRE><NRE>'.PHP_EOL;
                print '    <dataDeRecebimento><dataDeRecebimento>'.PHP_EOL;
                print '    <hashDoComponenteDigital><hashDoComponenteDigital>'.PHP_EOL;
                print '  </reciboDeEnvio>'.PHP_EOL;
                print '</recibosDeEnvio>'.PHP_EOL;
                break;
            
            // @join_tec US008.03 (#23092)
            case 'baixarReciboRecebimento':
                
                if(array_key_exists('atividade', $_GET)) {
                    
                    print '<recibosDeTramites>'.PHP_EOL;

                    $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
                    $objProcessoEletronicoDTO->setDblIdProcedimento($_GET['id_procedimento']);
                    $objProcessoEletronicoDTO->retStrNumeroRegistro();

                    $objProcessoEletronicoBD = new ProcessoEletronicoBD($objBancoSEI);
                    $objProcessoEletronicoDTO = $objProcessoEletronicoBD->consultar($objProcessoEletronicoDTO);

                    $objReciboTramiteDTO = new ReciboTramiteDTO();
                    $objReciboTramiteDTO->setStrNumeroRegistro($objProcessoEletronicoDTO->getStrNumeroRegistro());
                    $objReciboTramiteDTO->retNumIdTramite();
                    $objReciboTramiteDTO->retDthRecebimento();
                    $objReciboTramiteDTO->retStrHashAssinatura();

                    $objReciboTramiteBD = new ReciboTramiteBD($objBancoSEI);
                    $arrObjReciboTramiteDTO = $objReciboTramiteBD->listar($objReciboTramiteDTO);

                    if(!empty($arrObjReciboTramiteDTO)) {

                        foreach($arrObjReciboTramiteDTO as $objReciboTramiteDTO) {

                            print '  <reciboDeTramite>'.PHP_EOL;
                            print '    <IDT>'.$objReciboTramiteDTO->getNumIdTramite().'<IDT>'.PHP_EOL;
                            print '    <NRE>'.$objProcessoEletronicoDTO->getStrNumeroRegistro().'<NRE>'.PHP_EOL;
                            print '    <dataDeRecebimento>'.$objReciboTramiteDTO->retDthRecebimento().'<dataDeRecebimento>'.PHP_EOL;
                            print '    <hashDoComponenteDigital>'.$objReciboTramiteDTO->retStrHashAssinatura().'<hashDoComponenteDigital>'.PHP_EOL;
                            print '  </reciboDeTramite>'.PHP_EOL;

                        }
                    }
                    print '</recibosDeTramites>'.PHP_EOL;
                }
                break;
        }
        
        exit(0);
    }
    
    $strTitulo = 'Consultar Recibos';
        
    //$arrComandos = array();
    //$arrComandos[] = '<button type="button" accesskey="P" onclick="pesquisar();" id="btnPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';
    //$arrComandos[] = '<button type="button" accesskey="I" id="btnImprimir" value="Imprimir" onclick="infraImprimirTabela();" class="infraButton"><span class="infraTeclaAtalho">I</span>mprimir</button>';
    
    if(!array_key_exists('id_procedimento', $_GET) || empty($_GET['id_procedimento'])) {

        throw new InfraException('Código do procedimento não foi informado');
    }
    
    $objProcedimentoAndamentoDTO = new ProcedimentoAndamentoDTO();
    $objProcedimentoAndamentoDTO->retTodos();
    $objProcedimentoAndamentoDTO->setDblIdProcedimento($_GET['id_procedimento']);
    if(array_key_exists('txtTextoPesquisa', $_POST) && !empty($_POST['txtTextoPesquisa'])) {
       
        $objProcedimentoAndamentoDTO->setStrMensagem('%'.$_POST['txtTextoPesquisa'].'%', InfraDTO::$OPER_LIKE);
    } 
    
    $objPaginaSEI = PaginaSEI::getInstance();
    $objPaginaSEI->setTipoPagina(InfraPagina::$TIPO_PAGINA_SIMPLES);
    $objPaginaSEI->prepararOrdenacao($objProcedimentoAndamentoDTO, 'IdProcedimento', InfraDTO::$TIPO_ORDENACAO_ASC);
    $objPaginaSEI->prepararPaginacao($objProcedimentoAndamentoDTO);

    $objBancoSEI = BancoSEI::getInstance();
    $objBancoSEI->abrirConexao();
    
    $objProcedimentoAndamentoBD = new ProcedimentoAndamentoBD($objBancoSEI);
    $arrObjProcedimentoAndamentoDTO = $objProcedimentoAndamentoBD->listar($objProcedimentoAndamentoDTO);
    
    $objPaginaSEI->processarPaginacao($objProcedimentoAndamentoDTO);

    $numRegistros = count($arrObjProcedimentoAndamentoDTO);

    if(!empty($arrObjProcedimentoAndamentoDTO)){
        
        
        $arrAgruparProcedimentoAndamentoDTO = array();
        
        foreach($arrObjProcedimentoAndamentoDTO as &$objProcedimentoAndamentoDTO){

            $dblIdTramite = $objProcedimentoAndamentoDTO->getDblIdTramite();
            $arrAgruparProcedimentoAndamentoDTO[$dblIdTramite][] = $objProcedimentoAndamentoDTO;
        }
        
        $strResultado = '';

        $strResultado .= '<table width="99%" class="infraTable">'."\n";
        $strResultado .= '<caption class="infraCaption">'.$objPaginaSEI->gerarCaptionTabela('estados do processo', $numRegistros).'</caption>';

        $strResultado .= '<tr>';
        //$strResultado .= '<th class="infraTh" width="1%">'.$objPaginaSEI->getThCheck().'</th>'."\n";
        $strResultado .= '<th class="infraTh" width="20%">Data</th>'."\n";
        $strResultado .= '<th class="infraTh">Operação</th>'."\n";
        $strResultado .= '<th class="infraTh" width="15%">Situação</th>'."\n";
        $strResultado .= '</tr>'."\n";
        $strCssTr = '';
                
        foreach($arrAgruparProcedimentoAndamentoDTO as $dblIdTramite => $arrObjProcedimentoAndamentoDTO) {
            
            
            $objReturn = AtividadeRN::retornaAtividadeDoTramiteFormatado($dblIdTramite);
            
            $strResultado .= '<tr>';
            $strResultado .= '<td valign="middle" colspan="3">'.$objReturn->strMensagem;
            $strResultado .= '<div style="float:right; display:block">'; 
            // @join_tec US008.01 (#23092)
            $strResultado .= '<a href="#" onclick="baixarReciboRecebimento();" disabled="disabled"><img class="infraImg" src="sei/modulos/pen/imagens/page_green.png" alt="Download de Recibo de Recebimento" title="Download de Recibo de Recebimento" /></a>';                           
            // @join_tec US008.03 (#23092) | @join_tec US008.13 (#23092)
            if($objReturn->strAtividade == 'RECEBER'){
                $strResultado .= '<a href="#" onclick="baixarReciboEnvio();"><img class="infraImg" src="sei/modulos/pen/imagens/page_red.png" alt="Download de Recibo de Envio" title="Download de Recibo de Envio" /></a>';
            } 
            $strResultado .= '</div></td>';
            $strResultado .= '<tr>';
          
            foreach($arrObjProcedimentoAndamentoDTO as $ObjProcedimentoAndamentoDTO) {
            
                $strCssTr = ($strCssTr == 'infraTrClara') ? 'infraTrEscura' : 'infraTrClara';

                $strResultado .= '<tr class="'.$strCssTr.'">';
                //$strResultado .= '<td>'.$objPaginaSEI->getTrCheck($i, $ObjProcedimentoAndamentoDTO->getDblIdAndamento(), '').'</td>';
                $strResultado .= '<td align="center">'.$ObjProcedimentoAndamentoDTO->getDthData().'</td>';
                $strResultado .= '<td>'.$ObjProcedimentoAndamentoDTO->getStrMensagem().'</td>';
                $strResultado .= '<td align="center">';

                if($ObjProcedimentoAndamentoDTO->getStrSituacao() == 'S') {
                    $strResultado .= '<img src="modulos/pen/imagens/estado_sucesso.png" title="Concluído" alt="Concluído" />';
                }
                else {
                   $strResultado .= '<img src="modulos/pen/imagens/estado_falhou.png" title="Falhou" alt="Falhou" />';   
                }

                $strResultado .= '</td>'; 
                $strResultado .= '</tr>'."\n";

                $i++;
            }
        }
        $strResultado .= '</table>';
    }
}
catch(Exception $e){
    $objPaginaSEI->processarExcecao($e);
} 

$strProprioLink = 'controlador.php?acao='.$_GET['acao'].'&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao_retorno'].'&id_procedimento='.$_GET['id_procedimento'];


$objPaginaSEI->montarDocType();
$objPaginaSEI->abrirHtml();
$objPaginaSEI->abrirHead();
$objPaginaSEI->montarMeta();
$objPaginaSEI->montarTitle(':: '.$objPaginaSEI->getStrNomeSistema().' - '.$strTitulo.' ::');
$objPaginaSEI->montarStyle();
?>
<style type="text/css">
#lblTextoPesquisa {position:absolute;left:0%;top:10%;}
#txtTextoPesquisa {position:absolute;left:0%;top:26%;width:50%;}

#lblContextoSubstituicao {position:absolute;left:0%;top:62%;}
#txtContextoSubstituicao {position:absolute;left:0%;top:77%;width:50%;}
</style>
<?php $objPaginaSEI->montarJavaScript(); ?>
<script type="text/javascript">
var objAutoCompletarInteressadoRI1225 = null;

function inicializar(){

  infraEfeitoTabelas();
  document.getElementById('txtTextoPesquisa').focus();

}

function pesquisar(){
  document.getElementById('frmAcompanharEstadoProcesso').action='<?php print $objSessaoSEI->assinarLink($strProprioLink); ?>';
  document.getElementById('frmAcompanharEstadoProcesso').submit();
}

function tratarEnter(ev){
    var key = infraGetCodigoTecla(ev);
    if (key == 13){
        pesquisar();
    }
    return true;
}

function baixarReciboEnvio(){

   //window.open('<?php print $objSessaoSEI->assinarLink($strProprioLink.'&metodo=baixarReciboEnvio'); ?>', '_blank',"toolbar=yes, scrollbars=yes, resizable=yes, top=500, left=500, width=320, height=240");
}
function baixarReciboRecebimento(){

    window.open('<?php print $objSessaoSEI->assinarLink($strProprioLink.'&metodo=baixarReciboRecebimento'); ?>', '_blank',"toolbar=yes, scrollbars=yes, resizable=yes, top=500, left=500, width=320, height=240");
}
</script>
<?php
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody($strTitulo,'onload="inicializar();"');
?>
<form id="frmAcompanharEstadoProcesso" method="post" action="<?php print $objSessaoSEI->assinarLink($strProprioLink); ?>">
  <?php if($numRegistros > 0): ?>
        <?php //$objPaginaSEI->montarBarraComandosSuperior($arrComandos); ?>
        <?php /*$objPaginaSEI->abrirAreaDados('12em'); ?>
        <label id="lblTextoPesquisa" class="infraLabel" tabindex="<?=$objPaginaSEI->getProxTabDados()?>">Pesquisar em ação:</label>
        <input type="text" name="txtTextoPesquisa" id="txtTextoPesquisa" onkeyup="return tratarEnter(event);" class="infraText" value="<?php echo $_POST['txtTextoPesquisa']; ?>"/>
        <?php $objPaginaSEI->fecharAreaDados();*/ ?>
        <?php $objPaginaSEI->montarAreaTabela($strResultado, $numRegistros); ?>
        <?php //$objPaginaSEI->montarAreaDebug(); ?>
    <?php else: ?>
        <div style="clear:both"></div>
        <p>Nenhum estado foi encontrado para este procedimento</p>
    <?php endif; ?>
</form>
<?php $objPaginaSEI->fecharBody(); ?>
<?php $objPaginaSEI->fecharHtml(); ?>
