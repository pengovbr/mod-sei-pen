<?php
/**
 * 
 *
 */
require_once DIR_SEI_WEB.'/SEI.php';

try {
    session_start();
    $objSessao = SessaoSEI::getInstance(); 
    $objPaginaSEI = PaginaSEI::getInstance();

    SessaoSEI::getInstance()->validarLink();
    SessaoSEI::getInstance()->validarPermissao($_GET['acao']);

    $arrComandos = [];

    $bolSomenteLeitura = false;

  switch ($_GET['acao']) {
    case 'pen_map_tipo_documento_envio_cadastrar':
        $arrComandos[] = '<button type="submit" name="sbmCadastrarSerie" value="Salvar" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
        $arrComandos[] = '<button type="button" value="Cancelar" onclick="location.href=\'' . $objPaginaSEI->formatarXHTML(SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_tipo_documento_envio_listar&acao_origem=' . $_GET['acao'])) . '\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';   

      if(array_key_exists('codigo_especie', $_GET) && !empty($_GET['codigo_especie'])) {
          $strTitulo = 'Editar Mapeamento de Tipo de Documento para Envio';
      }
      else {
          $strTitulo = 'Novo Mapeamento de Tipo de Documento para Envio';
      }
        break;

    case 'pen_map_tipo_documento_envio_visualizar':
        $arrComandos[] = '<button type="button" name="btnFechar" value="Fechar class="infraButton" onclick="location.href=\'' . $objPaginaSEI->formatarXHTML(SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_tipo_documento_envio_listar&acao_origem=' . $_GET['acao'])) . '\';"><span class="infraTeclaAtalho">F</span>echar</button>';
        $bolSomenteLeitura = true;
        $strTitulo = 'Consultar Mapeamento de Tipo de Documento para Envio';           
        break;        

    default:
        throw new InfraException("Módulo do Tramita: Ação '" . $_GET['acao'] . "' não reconhecida.");
  }  

    //--------------------------------------------------------------------------
    // Ação por POST esta salvando o formulário
  if(strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {

    if(!array_key_exists('codigo_especie', $_POST) || empty($_POST['codigo_especie'])) {
        throw new InfraException('Módulo do Tramita: Nenhuma "Espécie Documental" foi selecionada');
    }

    if(!array_key_exists('id_serie', $_POST) || empty($_POST['id_serie'])) {
        throw new InfraException('Módulo do Tramita: Nenhum "Tipo de Documento" foi selecionado');
    }

      $objPenRelTipoDocMapEnviadoDTO = new PenRelTipoDocMapEnviadoDTO();
      $objPenRelTipoDocMapEnviadoDTO->setNumCodigoEspecie($_POST['codigo_especie']);
      $objPenRelTipoDocMapEnviadoDTO->setNumIdSerie($_POST['id_serie']);

    if(array_key_exists('id_mapeamento', $_GET) && !empty($_GET['id_mapeamento'])) {
        $objPenRelTipoDocMapEnviadoDTO->setDblIdMap($_GET['id_mapeamento']);
    }

      $objPenRelTipoDocMapEnviadoRN = new PenRelTipoDocMapEnviadoRN();
      $objPenRelTipoDocMapEnviadoRN->cadastrar($objPenRelTipoDocMapEnviadoDTO);

      $objPaginaSEI->adicionarMensagem('Salvo com sucesso', InfraPagina::$TIPO_MSG_INFORMACAO);

      header('Location: '.SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_tipo_documento_envio_listar&acao_origem='.$_GET['acao']));
      exit(0);
  }
    // Ação por GET + ID esta carregando o formulário
  else if(array_key_exists('id_mapeamento', $_GET) && !empty($_GET['id_mapeamento'])) {

      $objPenRelTipoDocMapEnviadoDTO = new PenRelTipoDocMapEnviadoDTO();
      $objPenRelTipoDocMapEnviadoDTO->setDblIdMap($_GET['id_mapeamento']);
      $objPenRelTipoDocMapEnviadoDTO->retTodos();

      $objPenRelTipoDocMapEnviadoRN = new PenRelTipoDocMapEnviadoRN(BancoSEI::getInstance());
      $objPenRelTipoDocMapEnviadoDTO = $objPenRelTipoDocMapEnviadoRN->consultar($objPenRelTipoDocMapEnviadoDTO);
  }

  if(empty($objPenRelTipoDocMapEnviadoDTO)) {
      $objPenRelTipoDocMapEnviadoDTO = new PenRelTipoDocMapEnviadoDTO();
      $objPenRelTipoDocMapEnviadoDTO->setNumCodigoEspecie(0);
      $objPenRelTipoDocMapEnviadoDTO->setNumIdSerie(0);
  }

    $objTipoDocMapRN = new TipoDocMapRN();
    $objPenRelTipoDocMapEnviadoRN = new PenRelTipoDocMapEnviadoRN();

    $arrSerie = $objTipoDocMapRN->listarParesSerie(
        $objPenRelTipoDocMapEnviadoRN->listarEmUso($objPenRelTipoDocMapEnviadoDTO->getNumIdSerie()),
        true
    );
} 
catch (InfraException $e) {

  if(preg_match('/Duplicate/', $e->getStrDescricao())) {

      $objPaginaSEI->adicionarMensagem('Nenhuma das duas chaves pode estar sendo utilizada em outra relação', InfraPagina::$TIPO_MSG_INFORMACAO);
  }
  else {

      $objPaginaSEI->processarExcecao($e);
  }
}
?>

<?php 
// View
ob_clean();

$objPaginaSEI->montarDocType();
$objPaginaSEI->abrirHtml();
$objPaginaSEI->abrirHead();
$objPaginaSEI->montarMeta();
$objPaginaSEI->montarTitle(':: ' . $objPaginaSEI->getStrNomeSistema() . ' - ' . $strTitulo . ' ::');
$objPaginaSEI->montarStyle();
?>
<style type="text/css">

.input-label-first{position:absolute;left:0%;top:0%;width:25%; color: #666!important}
.input-field-first{position:absolute;left:0%;top:15%;width:25%}    

.input-label-third {position:absolute;left:0%;top:40%;width:25%; color:#666!important}
.input-field-third {position:absolute;left:0%;top:55%;width:25%;}

</style>
<?php $objPaginaSEI->montarJavaScript(); ?>
<script type="text/javascript">

function inicializar(){

   var strMensagens = '<?php print str_replace("\n", '\n', $objPaginaSEI->getStrMensagens()); ?>';

   if(strMensagens) {

       alert(strMensagens);
   }
}

function onSubmit() {

    var form = jQuery('#pen-map-tipo-doc-enviado');
    var field = jQuery('select[name=codigo_especie]', form);

    if(field.val() === 'null'){
        alert('Nenhuma "Espécie Documental" foi selecionada');
        field.focus();
        return false;
    }

    field = jQuery('select[name=id_serie]', form);

    if(field.val() === 'null'){
        alert('Nenhum "Tipo de Documento" foi selecionado');
        field.focus();
        return false;
    }  
}

</script>
<?php
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody($strTitulo, 'onload="inicializar();"');
?>
<form id="pen-map-tipo-doc-enviado" onsubmit="return onSubmit();" method="post" action="">
    <?php $objPaginaSEI->montarBarraComandosSuperior($arrComandos); ?>
    <?php //$objPaginaSEI->montarAreaValidacao(); ?>
    <?php $objPaginaSEI->abrirAreaDados('12em'); ?>

    <label for="id_serie" class="infraLabelObrigatorio input-label-first">Tipo de Documento SEI - <?php echo PaginaSEI::tratarHTML($objSessao->getStrSiglaOrgaoUnidadeAtual())?>:</label>
    <select name="id_serie" class="infraSelect input-field-first"<?php if($bolSomenteLeitura) : ?> disabled="disabled" readonly="readonly"<?php 
   endif; ?>>
        <?php print InfraINT::montarSelectArray('', 'Selecione', $objPenRelTipoDocMapEnviadoDTO->getNumIdSerie(), $arrSerie); ?>
    </select>

    <label for="codigo_especie" class="infraLabelObrigatorio input-label-third">Espécie Documental Tramita GOV.BR:</label>    
    <select name="codigo_especie" class="infraSelect input-field-third"<?php if($bolSomenteLeitura) : ?>  disabled="disabled" readonly="readonly"<?php 
   endif; ?>>
        <?php print InfraINT::montarSelectArray('', 'Selecione', $objPenRelTipoDocMapEnviadoDTO->getNumCodigoEspecie(), $objTipoDocMapRN->listarParesEspecie()); ?>
    </select>

    <?php print $objPaginaSEI->fecharAreaDados(); ?>
</form>
<?php $objPaginaSEI->fecharBody(); ?>
<?php $objPaginaSEI->fecharHtml(); ?>
