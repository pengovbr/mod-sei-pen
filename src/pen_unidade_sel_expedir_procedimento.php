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

    session_start();

    //////////////////////////////////////////////////////////////////////////////
    InfraDebug::getInstance()->setBolLigado(false);
    InfraDebug::getInstance()->setBolDebugInfra(true);
    InfraDebug::getInstance()->limpar();
    //////////////////////////////////////////////////////////////////////////////

    SessaoSEI::getInstance()->validarLink();

  switch ($_GET['acao']) {

    case 'pen_unidade_sel_expedir_procedimento':
        $strTitulo = 'Selecionar Unidade';
        break;

    default:
        throw new InfraException("Módulo do Tramita: Ação '" . htmlspecialchars($_GET['acao']) . "' não reconhecida.");
  }

    $arrComandos = array();




    $arrComandos[] = '<button type="button" accesskey="F" id="btnFecharSelecao" value="Fechar" onclick="window.close();" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';
} catch (Exception $e) {
    PaginaSEI::getInstance()->processarExcecao($e);
}
?>

<!DOCTYPE html>
<html>
    <header>
        <link rel="stylesheet" href="<?php print PENIntegracao::getDiretorio(); ?>/css/style-modulos.css"  type="text/css" />
        <script type="text/javascript" src="<?php print PENIntegracao::getDiretorio(); ?>/js/jquery/jquery-1.10.2.min.js"></script>
        <script type="text/javascript"> var PEN_BASE_PATH = '<?php print PENIntegracao::getDiretorio(); ?>'; </script>
        <script type="text/javascript" src="<?php print PENIntegracao::getDiretorio(); ?>/js/expedir_processo/expedir-processo.js"></script>


    </header>
    <body>

        <form id="frmUnidadeLista" method="post" action="<?php echo PaginaSEI::getInstance()->formatarXHTML(SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . htmlspecialchars($_GET['acao']) . '&acao_origem=' . htmlspecialchars($_GET['acao']))) ?>">
<?
//PaginaSEI::getInstance()->montarBarraLocalizacao($strTitulo);
PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandos);
PaginaSEI::getInstance()->abrirAreaDados('5em');
?>
            
            <div class="" id="recebeUnudadesHierarquias">
                
                
                
            </div>

            <div class="row">
                <div class="span10">&nbsp;</div>
            </div> <div class="row">
                <div class="span10"><input type="hidden" name="repositorioSelecionadao" id="repositorioSelecionadao" value="" class="span10" ></div>
            </div>



<?
PaginaSEI::getInstance()->fecharAreaDados();
PaginaSEI::getInstance()->montarAreaTabela($strResultado, $numRegistros);
PaginaSEI::getInstance()->montarAreaDebug();
PaginaSEI::getInstance()->montarBarraComandosInferior($arrComandos);
?>
        </form>


      
    </body>
</html>

