<?
/**
* TRIBUNAL REGIONAL FEDERAL DA 4ª REGIÃO
*
* 14/04/2008 - criado por mga
*
* Versão do Gerador de Código: 1.14.0
*
* Versão no CVS: $Id$
*/

try {
  require_once DIR_SEI_WEB.'/SEI.php';
  require_once DIR_SEI_WEB.'/SEI.php';

  session_start();

  //////////////////////////////////////////////////////////////////////////////
  InfraDebug::getInstance()->setBolLigado(false);
  InfraDebug::getInstance()->setBolDebugInfra(true);
  InfraDebug::getInstance()->limpar();
  //////////////////////////////////////////////////////////////////////////////

  SessaoSEI::getInstance()->validarLink();
  PaginaSEI::getInstance()->prepararSelecao('pen_apensados_selecionar_expedir_procedimento');  

  //SessaoSEI::getInstance()->validarPermissao($_GET['acao']);
  
  PaginaSEI::getInstance()->salvarCamposPost(array('txtNumeroProcesso','txtDescricaoProcesso'));

  $strTitulo = PaginaSEI::getInstance()->getTituloSelecao('Selecionar Processos Apensados', 'Selecionar Processos Apensados');

  $arrComandos = array();
  
  $arrComandos[] = '<input type="submit" id="btnPesquisar" value="Pesquisar" class="infraButton" />';  
  
  $arrComandos[] = '<button type="button" accesskey="T" id="btnTransportarSelecao" value="Transportar" onclick="validarProcessos();" class="infraButton"><span class="infraTeclaAtalho">T</span>ransportar</button>';
 
  $obtAtividadeDTO = new AtividadeDTO();

  PaginaSEI::getInstance()->prepararPaginacao($obtAtividadeDTO);


  $numeroProcesso = PaginaSEI::getInstance()->recuperarCampo('txtNumeroProcesso');
  $decricaoProcesso = PaginaSEI::getInstance()->recuperarCampo('txtDescricaoProcesso');
  
  $objExpedirProcedimentoRN = new ExpedirProcedimentoRN();
  $processosAbertos = $objExpedirProcedimentoRN->listarProcessosAbertos($_GET['id_procedimento'], $_GET['infra_unidade_atual']);
  
  $arrProcessos = $objExpedirProcedimentoRN->listarProcessosApensadosAvancado($obtAtividadeDTO, $_GET['id_procedimento'], $_GET['infra_unidade_atual'], $numeroProcesso, $decricaoProcesso);
  
  PaginaSEI::getInstance()->processarPaginacao($obtAtividadeDTO);
  $numRegistros = count($arrProcessos);

  if ($numRegistros > 0){

    $bolCheck = true;

    $strResultado = '';

    $strSumarioTabela = 'Tabela de Processos Apensados.';
    $strCaptionTabela = 'Processos Apensados';

    $strResultado .= '<table width="99%" class="infraTable" summary="'.$strSumarioTabela.'">'."\n";
    $strResultado .= '<caption class="infraCaption">'.PaginaSEI::getInstance()->gerarCaptionTabela($strCaptionTabela, $numRegistros).'</caption>';
    $strResultado .= '<tr>';
    $strResultado .= '<th class="infraTh" width="1%">'.PaginaSEI::getInstance()->getThCheck().'</th>'."\n";
    $strResultado .= '<th align="left" class="infraTh">Processo</th>'."\n";
    $strResultado .= '<th class="infraTh">Ações</th>'."\n";
    $strResultado .= '</tr>'."\n";
    $strCssTr='';
    
    for($i = 0; $i < $numRegistros; $i++){

      $strCssTr = ($strCssTr=='<tr class="infraTrClara">')?'<tr class="infraTrEscura">':'<tr class="infraTrClara">';
      $strResultado .= $strCssTr;

      //Se o processo não se encontra aberto em mais de uma unidade
      if(isset($processosAbertos[$arrProcessos[$i]->getDblIdProtocolo()]) && count($processosAbertos[$arrProcessos[$i]->getDblIdProtocolo()]) == 1 ){
            $strResultado .= '<td valign="top">'.PaginaSEI::getInstance()->getTrCheck($i, $arrProcessos[$i]->getDblIdProtocolo(), $arrProcessos[$i]->getStrProtocoloFormatadoProtocolo()).'</td>';
      }else{
            $strResultado .= '<td valign="top">'.PaginaSEI::getInstance()->getTrCheck($i, $arrProcessos[$i]->getDblIdProtocolo(), $arrProcessos[$i]->getStrProtocoloFormatadoProtocolo(), 'N', 'Infra', 'class="abertoUnidades"').'</td>';
      }
      
      $strResultado .= '<td width="85%" class="numeroProcesso">'.$arrProcessos[$i]->getStrProtocoloFormatadoProtocolo().'</td>';
      $strResultado .= '<td align="center">';
      
      //Se o processo não se encontra aberto em mais de uma unidade
      if(isset($processosAbertos[$arrProcessos[$i]->getDblIdProtocolo()]) && count($processosAbertos[$arrProcessos[$i]->getDblIdProtocolo()]) == 1 ){
        $strResultado .= PaginaSEI::getInstance()->getAcaoTransportarItem($i, $arrProcessos[$i]->getDblIdProtocolo(), 'Infra', 'class="teste"');
      }else{
         $strResultado .= '<a id="" href="#" onclick="alertaImpossibilidade();" ><img src="/infra_css/imagens/transportar.gif" title="Transportar este item e Fechar" alt="Transportar este item e Fechar" class="infraImg"></a>';
      }
      
      $strResultado .= '</td></tr>'."\n";
    }
    $strResultado .= '</table>';
  }
  $arrComandos[] = '<button type="button" accesskey="F" id="btnFecharSelecao" value="Fechar" onclick="window.close();" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';

}catch(Exception $e){
  PaginaSEI::getInstance()->processarExcecao($e);
} 

PaginaSEI::getInstance()->montarDocType();
PaginaSEI::getInstance()->abrirHtml();
PaginaSEI::getInstance()->abrirHead();
PaginaSEI::getInstance()->montarMeta();
PaginaSEI::getInstance()->montarTitle(':: '.PaginaSEI::getInstance()->getStrNomeSistema().' - '.$strTitulo.' ::');
PaginaSEI::getInstance()->montarStyle();
PaginaSEI::getInstance()->abrirStyle();
?>

#lblNumeroProcesso {position:absolute;left:0%;top:0%;width:20%;}
#txtNumeroProcesso {position:absolute;left:0%;top:40%;width:20%;}

#lblDescricaoProcesso {position:absolute;left:25%;top:0%;width:73%;}
#txtDescricaoProcesso {position:absolute;left:25%;top:40%;width:73%;}

<?
PaginaSEI::getInstance()->fecharStyle();
PaginaSEI::getInstance()->montarJavaScript();
PaginaSEI::getInstance()->abrirJavaScript();
?>

function inicializar(){
  infraReceberSelecao();
  document.getElementById('btnFecharSelecao').focus();
  
  infraEfeitoTabelas();
}
<?
PaginaSEI::getInstance()->fecharJavaScript();
PaginaSEI::getInstance()->fecharHead();
PaginaSEI::getInstance()->abrirBody($strTitulo, 'onload="inicializar();"');
?>
<form id="frmApensadosLista" method="post" action="<?php echo PaginaSEI::getInstance()->formatarXHTML(SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.htmlspecialchars($_GET['acao']).'&acao_origem='.htmlspecialchars($_GET['acao']).'&id_procedimento='.htmlspecialchars($_GET['id_procedimento'])))?>">
  <?
  //PaginaSEI::getInstance()->montarBarraLocalizacao($strTitulo);
  PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandos);
  PaginaSEI::getInstance()->abrirAreaDados('5em');
  ?>
  <label id="lblNumeroProcesso" for="txtNumeroProcesso" class="infraLabelOpcional">Nº do Processo:</label>
  <input type="text" id="txtNumeroProcesso" name="txtNumeroProcesso" class="infraText" value="<?php echo $strSiglaPesquisa?>"  tabindex="<?php echo PaginaSEI::getInstance()->getProxTabDados()?>" />
  
  <label id="lblDescricaoProcesso" for="txtDescricaoProcesso" class="infraLabelOpcional">Descrição do Procesoo:</label>
  <input type="text" id="txtDescricaoProcesso" name="txtDescricaoProcesso" class="infraText" tabindex="<?php echo PaginaSEI::getInstance()->getProxTabDados()?>" value="<?php echo $strDescricaoPesquisa?>" />
  
  <?
  PaginaSEI::getInstance()->fecharAreaDados();  
  PaginaSEI::getInstance()->montarAreaTabela($strResultado, $numRegistros);
  PaginaSEI::getInstance()->montarAreaDebug();
  PaginaSEI::getInstance()->montarBarraComandosInferior($arrComandos);
  ?>
</form>

<script type="text/javascript">
    function alertaImpossibilidade(){
        alert("O processo selecionado não pode ser apensado pois está aberto em outra unidade.")
    }
    
    function validarProcessos(){
        var abertoUnidades = '';
        
        $('input[type="checkbox"]:checked').each(function(){
            if($(this).attr('class') == "abertoUnidades"){
                abertoUnidades += $(this).attr('title') ;
            }
        });

        if(abertoUnidades != ''){
            alert('Os seguintes processos não podem ser apensados pois estão abertos em outra unidade: '+abertoUnidades);
        }else{
            infraTransportarSelecao();
        }
    }
</script>
<?
PaginaSEI::getInstance()->fecharBody();
PaginaSEI::getInstance()->fecharHtml();
?>
