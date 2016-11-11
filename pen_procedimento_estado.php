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
    
    //$objSessaoSEI->validarLink();
    $objSessaoSEI->validarPermissao('pen_procedimento_expedir');
    
    if(array_key_exists('metodo', $_GET)) {
        
        ob_clean();
        header('Content-type: text/xml');
      

        switch ($_GET['metodo']){
            
            // @join_tec US008.02 (#23092)
            case 'baixarReciboEnvio':
                
                header('Content-Disposition: attachment; filename="recibo_de_envio_do_tramite.xml"');
                print '<?xml version="1.0" encoding="UTF-8" ? >'.PHP_EOL;
                $objBancoSEI = BancoSEI::getInstance();
                $objBancoSEI->abrirConexao();
        
              try {
                
                    
                    if(array_key_exists('id_tramite', $_GET) && array_key_exists('id_tarefa', $_GET)) {
                                        
                        $objReciboTramiteRN = new ReciboTramiteRN();
                        $arrObjReciboTramiteDTO = $objReciboTramiteRN->downloadReciboEnvio($_GET['id_tramite']);

                        if(empty($arrObjReciboTramiteDTO)) {
                            throw new InfraException('O recibo ainda não foi recebido.');
                        }
                        
                        print '<recibosDeTramites>';

                        foreach($arrObjReciboTramiteDTO as $objReciboTramiteDTO) {

                            $dthTimeStamp = InfraData::getTimestamp($objReciboTramiteDTO->getDthRecebimento());
                            
                            print '<reciboDeTramite>';
                            print '<IDT>'.$objReciboTramiteDTO->getNumIdTramite().'<IDT>';
                            print '<NRE>'.$objReciboTramiteDTO->getStrNumeroRegistro().'<NRE>';
                            print '<dataDeRecebimento>'.date('c', $dthTimeStamp).'<dataDeRecebimento>';
                            
                            $strHashAssinatura = $objReciboTramiteDTO->getStrHashAssinatura();
                            if(!empty($strHashAssinatura)) {
                                print '<hashDoComponenteDigital>'.$strHashAssinatura.'<hashDoComponenteDigital>';
                            }
                            print '</reciboDeTramite>';
                        }

                        print '</recibosDeTramites>';
                    }
                }
                catch(InfraException $e){
                    
                    ob_clean();
                    print '<?xml version="1.0" encoding="UTF-8" ? >'.PHP_EOL;
                    print '<erro>';
                    print '<mensagem>'.$e->getStrDescricao().'</mensagem>';
                    print '</erro>';
                }
             
                break;
            
            // @join_tec US008.03 (#23092)
            case 'baixarReciboRecebimento':
                header('Content-Disposition: attachment; filename="recibo_de_conclusao_do_tramite.xml"');
                print '<?xml version="1.0" encoding="UTF-8" ? >'.PHP_EOL;
                $objBancoSEI = BancoSEI::getInstance();
                $objBancoSEI->abrirConexao();
                
                try {
                    
                    if(array_key_exists('id_tramite', $_GET) && array_key_exists('id_tarefa', $_GET)) {
                                        
                        $objReciboTramiteRN = new ReciboTramiteRN();
                        $arrObjReciboTramiteDTO = $objReciboTramiteRN->listarPorAtividade($_GET['id_tramite'], $_GET['id_tarefa']);

                        if(empty($arrObjReciboTramiteDTO)) {
                            throw new InfraException('O recibo ainda não foi recebido.');
                        }
                        
                        print '<recibosDeTramites>';

                        foreach($arrObjReciboTramiteDTO as $objReciboTramiteDTO) {

                            $dthTimeStamp = InfraData::getTimestamp($objReciboTramiteDTO->getDthRecebimento());
                            
                            print '<reciboDeTramite>';
                            print '<IDT>'.$objReciboTramiteDTO->getNumIdTramite().'<IDT>';
                            print '<NRE>'.$objReciboTramiteDTO->getStrNumeroRegistro().'<NRE>';
                            print '<dataDeRecebimento>'.date('c', $dthTimeStamp).'<dataDeRecebimento>';
                            
                            $strHashAssinatura = $objReciboTramiteDTO->getStrHashAssinatura();
                            if(!empty($strHashAssinatura)) {
                                print '<hashDoComponenteDigital>'.$strHashAssinatura.'<hashDoComponenteDigital>';
                            }
                            print '</reciboDeTramite>';
                        }

                        print '</recibosDeTramites>';
                    }
                }
                catch(InfraException $e){
                    
                    ob_clean();
                    print '<?xml version="1.0" encoding="UTF-8" ? >'.PHP_EOL;
                    print '<erro>';
                    print '<mensagem>'.$e->getStrDescricao().'</mensagem>';
                    print '</erro>';
                }
                break;
        }
        
        exit(0);
    }
    
    $strProprioLink = 'controlador.php?acao='.$_GET['acao'].'&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao_retorno'].'&id_procedimento='.$_GET['id_procedimento'];
    $strTitulo = 'Consultar Recibos';
        
    //$arrComandos = array();
    //$arrComandos[] = '<button type="button" accesskey="P" onclick="pesquisar();" id="btnPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';
    //$arrComandos[] = '<button type="button" accesskey="I" id="btnImprimir" value="Imprimir" onclick="infraImprimirTabela();" class="infraButton"><span class="infraTeclaAtalho">I</span>mprimir</button>';
    
    if(!array_key_exists('id_procedimento', $_GET) || empty($_GET['id_procedimento'])) {

        throw new InfraException('Código do procedimento não foi informado');
    }
    
    $objProcedimentoAndamentoDTO = new ProcedimentoAndamentoDTO();
    $objProcedimentoAndamentoDTO->retTodos();
    $objProcedimentoAndamentoDTO->setOrdDblIdTramite(InfraDTO::$TIPO_ORDENACAO_ASC);
    $objProcedimentoAndamentoDTO->setOrdDthData(InfraDTO::$TIPO_ORDENACAO_ASC);
    $objProcedimentoAndamentoDTO->setDblIdProcedimento($_GET['id_procedimento']);
    
    if(array_key_exists('txtTextoPesquisa', $_POST) && !empty($_POST['txtTextoPesquisa'])) {
       
        $objProcedimentoAndamentoDTO->setStrMensagem('%'.$_POST['txtTextoPesquisa'].'%', InfraDTO::$OPER_LIKE);
    } 
    
    $objPaginaSEI = PaginaSEI::getInstance();
    $objPaginaSEI->setTipoPagina(InfraPagina::$TIPO_PAGINA_SIMPLES);
    //$objPaginaSEI->prepararOrdenacao($objProcedimentoAndamentoDTO, 'IdProcedimento', InfraDTO::$TIPO_ORDENACAO_ASC);
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

            $key = $objProcedimentoAndamentoDTO->getDblIdTramite() . '-' . $objProcedimentoAndamentoDTO->getNumTarefa();
            
            $arrAgruparProcedimentoAndamentoDTO[$key][] = $objProcedimentoAndamentoDTO;
        }
        
        $strResultado = '';

        $strResultado .= '<table width="99%" class="infraTable">'."\n";
        //$strResultado .= '<caption class="infraCaption">'.$objPaginaSEI->gerarCaptionTabela('estados do processo', $numRegistros).'</caption>';

        $strResultado .= '<tr>';
        //$strResultado .= '<th class="infraTh" width="1%">'.$objPaginaSEI->getThCheck().'</th>'."\n";
        $strResultado .= '<th class="infraTh" width="20%">Data</th>'."\n";
        $strResultado .= '<th class="infraTh">Operação</th>'."\n";
        $strResultado .= '<th class="infraTh" width="15%">Situação</th>'."\n";
        $strResultado .= '</tr>'."\n";
        $strCssTr = '';
                
        foreach($arrAgruparProcedimentoAndamentoDTO as $key => $arrObjProcedimentoAndamentoDTO) {
            
            
            list($dblIdTramite, $numTarefa) = explode('-', $key);
            
            $objReturn = PenAtividadeRN::retornaAtividadeDoTramiteFormatado($dblIdTramite, $numTarefa);
            
            
            $strResultado .= '<tr>';
            $strResultado .= '<td valign="middle" colspan="2">'.$objReturn->strMensagem.'</td>';
            $strResultado .= '<td valign="middle" align="center">';
            
            // @join_tec US008.03 (#23092) | @join_tec US008.13 (#23092)
           
            if($numTarefa == ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO)){
                $strResultado .= '<a href="'.$objSessaoSEI->assinarLink($strProprioLink.'&metodo=baixarReciboEnvio&id_tarefa='.$numTarefa.'&id_tramite='.$dblIdTramite).'"><img class="infraImg" src="'.PENIntegracao::getDiretorio().'/imagens/page_red.png" alt="Recibo de Confirmação de Envio" title="Recibo de Confirmação de Envio" /></a>';
            }
            // @join_tec US008.01 (#23092)
            if($objReturn->bolReciboExiste) {
                $strResultado .= '<a href="'.$objSessaoSEI->assinarLink($strProprioLink.'&metodo=baixarReciboRecebimento&id_tarefa='.$numTarefa.'&id_tramite='.$dblIdTramite).'"><img class="infraImg" src="'.PENIntegracao::getDiretorio().'/imagens/page_green.png" alt="Recibo de Conclusão de Trâmite" title="Recibo de Conclusão de Trâmite" /></a>';                           
            }
            $strResultado .= '</td>';
            $strResultado .= '<tr>';
          
            foreach($arrObjProcedimentoAndamentoDTO as $objProcedimentoAndamentoDTO) {
            
                $strCssTr = ($strCssTr == 'infraTrClara') ? 'infraTrEscura' : 'infraTrClara';

                $strResultado .= '<tr class="'.$strCssTr.'">';
                //$strResultado .= '<td>'.$objPaginaSEI->getTrCheck($i, $objProcedimentoAndamentoDTO->getDblIdAndamento(), '').'</td>';
                $strResultado .= '<td align="center">'.$objProcedimentoAndamentoDTO->getDthData().'</td>';
                $strResultado .= '<td>'.$objProcedimentoAndamentoDTO->getStrMensagem().'</td>';
                $strResultado .= '<td align="center">';

                if($objProcedimentoAndamentoDTO->getStrSituacao() == 'S') {
                    $strResultado .= '<img src="'.PENIntegracao::getDiretorio().'/imagens/estado_sucesso.png" title="Concluído" alt="Concluído" />';
                }
                else {
                   $strResultado .= '<img src="'.PENIntegracao::getDiretorio().'/imagens/estado_falhou.png" title="Falhou" alt="Falhou" />';   
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
        <p>Nenhum trâmite realizado para esse processo.</p>
    <?php endif; ?>
</form>
<?php $objPaginaSEI->fecharBody(); ?>
<?php $objPaginaSEI->fecharHtml(); ?>
