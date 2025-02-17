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
    SessaoSEI::getInstance()->validarPermissao('pen_map_tipo_documento_recebimento_cadastrar');

    $objPenRelTipoDocMapRecebidoRN = new PenRelTipoDocMapRecebidoRN();

    $arrComandos = [];

    $bolSomenteLeitura = false;

  switch ($_GET['acao']) {
    case 'pen_map_tipo_documento_recebimento_cadastrar':
        $arrComandos[] = '<button type="submit" name="sbmCadastrarSerie" value="Salvar" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
        $arrComandos[] = '<button type="button" value="Cancelar" onclick="location.href=\'' . $objPaginaSEI->formatarXHTML(SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_tipo_documento_recebimento_listar&acao_origem=' . $_GET['acao'])) . '\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';   
        $bolDesativarCampos = false;

      if(array_key_exists('codigo_especie', $_GET) && !empty($_GET['codigo_especie'])) {
          $strTitulo = 'Editar Mapeamento de Tipo de Documento para Recebimento';
      }
      else {
          $strTitulo = 'Novo Mapeamento de Tipo de Documento para Recebimento';
      }
        break;

    case 'pen_map_tipo_documento_recebimento_visualizar':
        $arrComandos[] = '<button type="button" name="btnFechar" value="Fechar class="infraButton" onclick="location.href=\'' . $objPaginaSEI->formatarXHTML(SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_tipo_documento_recebimento_listar&acao_origem=' . $_GET['acao'])) . '\';">Fechar</button>';
        $bolSomenteLeitura = true;
        $strTitulo = 'Consultar Mapeamento Tipo de Documento para Recebimento';           
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

      $objPenRelTipoDocMapRecebidoDTO = new PenRelTipoDocMapRecebidoDTO();
      $objPenRelTipoDocMapRecebidoDTO->setNumCodigoEspecie($_POST['codigo_especie']);
      $objPenRelTipoDocMapRecebidoDTO->setNumIdSerie($_POST['id_serie']);

    if(array_key_exists('id_mapeamento', $_GET) && !empty($_GET['id_mapeamento'])) {
        $objPenRelTipoDocMapRecebidoDTO->setDblIdMap($_GET['id_mapeamento']);
    }

      $objPenRelTipoDocMapRecebidoRN->cadastrar($objPenRelTipoDocMapRecebidoDTO);
      $objPaginaSEI->adicionarMensagem('Salvo com sucesso', InfraPagina::$TIPO_MSG_INFORMACAO);

      header('Location: '.SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_tipo_documento_recebimento_listar&acao_origem='.$_GET['acao']));
      exit(0);
  }
    // Ação por GET + ID esta carregando o formulário
  else if(array_key_exists('id_mapeamento', $_GET) && !empty($_GET['id_mapeamento'])) {

      $objPenRelTipoDocMapRecebidoDTO = new PenRelTipoDocMapRecebidoDTO();
      $objPenRelTipoDocMapRecebidoDTO->setDblIdMap($_GET['id_mapeamento']);
      $objPenRelTipoDocMapRecebidoDTO->retTodos();

      $objPenRelTipoDocMapRecebidoDTO = $objPenRelTipoDocMapRecebidoRN->consultar($objPenRelTipoDocMapRecebidoDTO);
  }

  if(empty($objPenRelTipoDocMapRecebidoDTO)) {
      $objPenRelTipoDocMapRecebidoDTO = new PenRelTipoDocMapRecebidoDTO();
      $objPenRelTipoDocMapRecebidoDTO->setNumCodigoEspecie(0);
      $objPenRelTipoDocMapRecebidoDTO->setNumIdSerie(0);
  }

    $objTipoDocMapRN = new TipoDocMapRN();
    $arrEspecieDocumental = $objTipoDocMapRN->listarParesEspecie(
        $objPenRelTipoDocMapRecebidoRN->listarEmUso($objPenRelTipoDocMapRecebidoDTO->getNumCodigoEspecie())
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

    var form = jQuery('#pen-map-tipo-doc-recebido');
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
<form id="pen-map-tipo-doc-recebido" onsubmit="return onSubmit();" method="post" action="">
    <?php $objPaginaSEI->montarBarraComandosSuperior($arrComandos); ?>
    <?php //$objPaginaSEI->montarAreaValidacao(); ?>
    <?php $objPaginaSEI->abrirAreaDados('12em'); ?>

    <label for="codigo_especie" class="infraLabelObrigatorio input-label-first">Espécie Documental Tramita GOV.BR:</label>
    <select name="codigo_especie" class="infraSelect input-field-first"<?php if($bolSomenteLeitura) : ?> disabled="disabled" readonly="readonly"<?php 
   endif; ?>>
        <?php print InfraINT::montarSelectArray('', 'Selecione', $objPenRelTipoDocMapRecebidoDTO->getNumCodigoEspecie(), $arrEspecieDocumental); ?>
    </select>

    <label for="id_serie" class="infraLabelObrigatorio input-label-third">Tipo de Documento SEI - <?php echo PaginaSEI::tratarHTML($objSessao->getStrSiglaOrgaoUnidadeAtual())?>:</label>
    <select name="id_serie" class="infraSelect input-field-third"<?php if($bolSomenteLeitura) : ?> disabled="disabled" readonly="readonly"<?php 
   endif; ?>>
        <?php print InfraINT::montarSelectArray('', 'Selecione', $objPenRelTipoDocMapRecebidoDTO->getNumIdSerie(), $objTipoDocMapRN->listarParesSerie()); ?>
    </select>

    <?php print $objPaginaSEI->fecharAreaDados(); ?>
</form>
<?php $objPaginaSEI->fecharBody(); ?>
<?php $objPaginaSEI->fecharHtml(); ?>
