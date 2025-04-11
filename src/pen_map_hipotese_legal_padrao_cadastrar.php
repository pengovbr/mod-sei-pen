<?php
/**
 *
 *
 */

require_once DIR_SEI_WEB.'/SEI.php';
require_once DIR_SEI_WEB.'/SEI.php';

session_start();

define('PEN_RECURSO_ATUAL', 'pen_map_hipotese_legal_padrao_cadastrar');
define('PEN_RECURSO_BASE', 'pen_map_hipotese_legal_padrao');
define('PEN_PAGINA_TITULO', 'Hipótese de Restrição Padrão - Tramita GOV.BR');
define('PEN_PAGINA_GET_ID', 'id_mapeamento');


$objPagina = PaginaSEI::getInstance();
$objBanco = BancoSEI::getInstance();
$objSessao = SessaoSEI::getInstance();


try {

    $objSessao->validarLink();
    $objSessao->validarPermissao(PEN_RECURSO_ATUAL);

    $arrComandos = [];

    $bolSomenteLeitura = false;

  switch ($_GET['acao']) {
    case PEN_RECURSO_BASE.'_cadastrar':
        $arrComandos[] = '<button type="submit" id="btnSalvar" value="Salvar" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
        $arrComandos[] = '<button type="button" id="btnCancelar" value="Cancelar" onclick="location.href=\'' . $objPagina->formatarXHTML($objSessao->assinarLink('controlador.php?acao=principal&acao_origem=' . $_GET['acao'])) . '\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';
        $strTitulo =  PEN_PAGINA_TITULO;
        break;

    case PEN_RECURSO_BASE.'_visualizar':
        $arrComandos[] = '<button type="button" name="btnFechar" value="Fechar class="infraButton" onclick="location.href=\'' . $objPagina->formatarXHTML($objSessao->assinarLink('controlador.php?acao=principal&acao_origem=' . $_GET['acao'])) . '\';"><span class="infraTeclaAtalho">F</span>echar</button>';
        $bolSomenteLeitura = true;
        $strTitulo =  sprintf('Consultar %s', PEN_PAGINA_TITULO);
        break;


    default:
        throw new InfraException("Módulo do Tramita: Ação '" . $_GET['acao'] . "' não reconhecida.");
  }

    //--------------------------------------------------------------------------
    // Ao por POST esta salvando o formulário

    $objPenParametroRN = new PenParametroRN();

  if(strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {

    if(!array_key_exists('id_hipotese_legal', $_POST) || empty($_POST['id_hipotese_legal'])) {
        throw new InfraException('Módulo do Tramita: Nenhuma "Espécie Documental" foi selecionada');
    }

      $objPenParametroDTO = new PenParametroDTO();
      $objPenParametroDTO->setStrNome('HIPOTESE_LEGAL_PADRAO');
      $objPenParametroDTO->retTodos();

    if($objPenParametroRN->contar($objPenParametroDTO) > 0) {
        $objPenParametroDTO->setStrValor($_POST['id_hipotese_legal']);
        $objPenParametroRN->alterar($objPenParametroDTO);
    }
    else {
        $objPenParametroDTO->setStrValor($_POST['id_hipotese_legal']);
        $objPenParametroRN->cadastrar($objPenParametroDTO);
    }

      $objPagina->adicionarMensagem('Hipótese de Restrição Padrão foi salva com sucesso', InfraPagina::$TIPO_MSG_AVISO);

      header('Location: '.$objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_cadastrar&acao_origem='.$_GET['acao']));
      exit(0);
  }
  else {

      $objPenParametroDTO = new PenParametroDTO();
      $objPenParametroDTO->setStrNome('HIPOTESE_LEGAL_PADRAO');
      $objPenParametroDTO->retTodos();

    if($objPenParametroRN->contar($objPenParametroDTO) > 0) {
        $objPenParametroDTO = $objPenParametroRN->consultar($objPenParametroDTO);
    }
    else {
        $objPenParametroDTO->setStrValor('0');
    }
  }

    //--------------------------------------------------------------------------
    // Auto-Complete
    //--------------------------------------------------------------------------
    // Mapeamento da hipotese legal local
    $objHipoteseLegalDTO = new HipoteseLegalDTO();
    $objHipoteseLegalDTO->setStrStaNivelAcesso(ProtocoloRN::$NA_RESTRITO); //Restrito
    $objHipoteseLegalDTO->setStrSinAtivo('S');
    $objHipoteseLegalDTO->setOrdStrNome(InfraDTO::$TIPO_ORDENACAO_ASC);
    $objHipoteseLegalDTO->retNumIdHipoteseLegal();
    $objHipoteseLegalDTO->retStrNome();

    $objHipoteseLegalRN = new HipoteseLegalRN();
    $arrMapIdHipoteseLegal = InfraArray::converterArrInfraDTO($objHipoteseLegalRN->listar($objHipoteseLegalDTO), 'Nome', 'IdHipoteseLegal');
}
catch(InfraException|Exception $e) {
    $objPagina->processarExcecao($e);
}

// View
ob_clean();

$objPagina->montarDocType();
$objPagina->abrirHtml();
$objPagina->abrirHead();
$objPagina->montarMeta();
$objPagina->montarTitle(':: ' . $objPagina->getStrNomeSistema() . ' - ' . $strTitulo . ' ::');
$objPagina->montarStyle();
?>
<style type="text/css">

.input-label-first{position:absolute;left:0%;top:0%;width:40%; color: #666!important}
.input-field-first{position:absolute;left:0%;top:15%;width:40%}

.input-label-third {position:absolute;left:0%;top:40%;width:25%; color:#666!important}
.input-field-third {position:absolute;left:0%;top:55%;width:25%;}

</style>
<?php $objPagina->montarJavaScript(); ?>
<script type="text/javascript">

function inicializar(){


}

function onSubmit() {

    var form = jQuery('#<?php print PEN_RECURSO_BASE; ?>_form');
    var field = jQuery('select[name=id_hipotese_legal]', form);

    if(field.val() === 'null' || !field.val()){
        alert('Nenhuma "Hipótese Legal" foi selecionada');
        field.focus();
        return false;
    }
}

</script>
<?php
$objPagina->fecharHead();
$objPagina->abrirBody($strTitulo, 'onload="inicializar();"');
?>
<form id="<?php print PEN_RECURSO_BASE; ?>_form" onsubmit="return onSubmit();" method="post" action="">
    <?php $objPagina->montarBarraComandosSuperior($arrComandos); ?>
    <?php //$objPagina->montarAreaValidacao(); ?>
    <?php $objPagina->abrirAreaDados('12em'); ?>

    <label for="id_hipotese_legal" class="infraLabelObrigatorio input-label-first">Hipótese Legal:</label>

    <select name="id_hipotese_legal" class="infraSelect input-field-first"<?php if($bolSomenteLeitura) : ?>  disabled="disabled" readonly="readonly"<?php 
   endif; ?>>
        <?php print InfraINT::montarSelectArray('', 'Selecione', $objPenParametroDTO->getStrValor(), $arrMapIdHipoteseLegal); ?>
    </select>


    <?php print $objPagina->fecharAreaDados(); ?>
</form>
<?php $objPagina->fecharBody(); ?>
<?php $objPagina->fecharHtml(); ?>
