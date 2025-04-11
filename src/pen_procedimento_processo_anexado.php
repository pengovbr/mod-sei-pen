<?
/**
* TRIBUNAL REGIONAL FEDERAL DA 4ª REGIÃO
*
* 24/01/2008 - criado por marcio_db//
*
* Versão do Gerador de Código: 1.13.1
*
* Versão no CVS: $Id$
*/

try {
     require_once DIR_SEI_WEB.'/SEI.php';

      session_start();

  //////////////////////////////////////////////////////////////////////////////
  //InfraDebug::getInstance()->setBolLigado(false);
  //InfraDebug::getInstance()->setBolDebugInfra(false);
  //InfraDebug::getInstance()->limpar();
  //////////////////////////////////////////////////////////////////////////////

  SessaoSEI::getInstance()->validarLink();

  PaginaSEI::getInstance()->prepararSelecao('assunto_selecionar');

 //SessaoSEI::getInstance()->validarPermissao($_GET['acao']);

  
  PaginaSEI::getInstance()->salvarCamposPost(array('txtPalavrasPesquisaAssuntos'));
      

  switch($_GET['acao']){
   
   
    case 'pen_procedimento_processo_anexado':
      $strTitulo = 'Processo Anexado';
        break;

    default:
        throw new InfraException("Módulo do Tramita: Ação '".$_GET['acao']."' não reconhecida.");
  }

  $arrComandos = array();
  
   $arrComandos[  ] = '<button type="button" accesskey="F" id="btnFecharSelecao" value="Fechar" onclick="window.close();" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';

    $objExpedirProcedimentoRN = new ExpedirProcedimentoRN();
     $paramsModal  = $objExpedirProcedimentoRN->getValoresModal();
  $objExpedirProcedimentoRN->limparValoresModal();
  $objProtocoloDTO =  $objExpedirProcedimentoRN->consultarProcessosApensadosDetalahar($paramsModal ["idProtocolo"]);

 
}catch(Exception $e){
  PaginaSEI::getInstance()->processarExcecao($e);
} 
?>
<!DOCTYPE html>
<html>
    <header>
        <link rel="stylesheet" href="<?php print PENIntegracao::getDiretorio(); ?>/css/style-modulos.css"  type="text/css" />
          <script type="text/javascript" src="<?php print PENIntegracao::getDiretorio(); ?>/js/jquery/jquery-1.10.2.min.js" ></script>
        <script type="text/javascript" src="<?php print PENIntegracao::getDiretorio(); ?>/js/expedir_processo/detalathar-processo-apensado.js" ></script>
    </header>
    <body>


<form id="frmAssuntoLista" method="post" action="">
  <?
 
  PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandos);
  PaginaSEI::getInstance()->abrirAreaDados('5em');
  
 
  

  ?>
    <div class="row">
        <div class="span5">
        <label>
            Protocolo:
        </label><br>
        <label>
            <?php echo $objProtocoloDTO->getStrProtocoloFormatado() ?>
        </label>
        </div>
        
          <div class="span5">
        <label>
            Data Geração:
        </label><br>
        <label>
            <?php echo $objProtocoloDTO->getDtaGeracao() ?>
        </label>
        </div>
        
    </div>
    
    
    <br>
    <div class="row">
        <div class="span5">
        <label>
            Unidade Geradora:
        </label><br>
        <label>
            <?php echo $objProtocoloDTO->UnidadeGeradora->getStrDescricao() ?>
        </label>
        </div>
        
          <div class="span5">
        <label>
            Criado por:
        </label><br>
        <label>
            <?php echo $objProtocoloDTO->UsuarioCriador->getStrNome() ?>
        </label>
        </div>
        
    </div>
    <br>
    
   
    
    <br>
    <div class="row">
    <div class="span10">
        <label>
         Descrição:
        </label><br>
        <label>
            <?php echo $objProtocoloDTO->getStrDescricao() ?>
        </label>
        </div>      
  </div>
    
    
      
        
        
  
</form>
    </body>
</html>
