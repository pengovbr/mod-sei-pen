<?php
/**
 *
 *
 * Construção e moldura do arquivo, equivalente a exemplos já existentes no sistema.
 */
require_once DIR_SEI_WEB.'/SEI.php';

session_start();

define('PEN_RECURSO_ATUAL', 'pen_map_unidade_cadastrar');
define('PEN_RECURSO_BASE', 'pen_map_unidade');
define('PEN_PAGINA_TITULO', 'Mapeamento de Unidades');
define('PEN_PAGINA_GET_ID', 'id_unidade');

$objPagina = PaginaSEI::getInstance();
$objBanco = BancoSEI::getInstance();
$objSessao = SessaoSEI::getInstance();

try {

    $objSessao->validarLink();
    $objSessao->validarPermissao(PEN_RECURSO_ATUAL);

    $arrComandos = array();

    //Obter dados do repositório em que o SEI está registrado (Repositório de Origem)
    $objPenParametroRN = new PenParametroRN();
    $numIdRepositorioOrigem = $objPenParametroRN->getParametro('PEN_ID_REPOSITORIO_ORIGEM');
    $strLinkAjaxUnidade = $objSessao->assinarLink('controlador_ajax.php?acao_ajax=pen_unidade_auto_completar_cadastro');

    $bolSomenteLeitura = false;

  switch ($_GET['acao']) {
    case PEN_RECURSO_BASE.'_cadastrar':
        $arrComandos[] = '<button type="submit" id="btnSalvar" value="Salvar" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
        $arrComandos[] = '<button type="button" id="btnCancelar" value="Cancelar" onclick="location.href=\'' . $objPagina->formatarXHTML($objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_listar&acao_origem=' . $_GET['acao'])) . '\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';

      if(array_key_exists(PEN_PAGINA_GET_ID, $_GET) && !empty($_GET[PEN_PAGINA_GET_ID])){
        $strTitulo = sprintf('Editar %s', PEN_PAGINA_TITULO);
      }
      else {
          $strTitulo =  sprintf('Novo %s', PEN_PAGINA_TITULO);
      }
        break;

    case PEN_RECURSO_BASE.'_visualizar':
        $arrComandos[] = '<button type="button" name="btnFechar" value="Fechar" class="infraButton" onclick="location.href=\'' . $objPagina->formatarXHTML($objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_listar&acao_origem=' . $_GET['acao'])) . '\';">Fechar</button>';
        $bolSomenteLeitura = true;
       $strTitulo =  sprintf('Consultar %s', PEN_PAGINA_TITULO);
        break;


    default:
        throw new InfraException("Ação '" . $_GET['acao'] . "' não reconhecida.");
  }

    $objPenUnidadeRN = new PenUnidadeRN();

    //--------------------------------------------------------------------------
    // Ao por POST esta salvando o formulrio
  if(strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {

    if(!array_key_exists('id_unidade', $_POST) || empty($_POST['id_unidade'])) {
      $params = http_build_query($_POST);
      $objPagina->adicionarMensagem('Nenhuma "Unidade" foi selecionada', InfraPagina::$TIPO_MSG_AVISO);
      header('Location: '.$objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_cadastrar&acao_origem='.$_GET['acao'].'&id_mapeamento='.$numIdUnidade.PaginaSEI::getInstance()->montarAncora($numIdUnidade). '&'.$params));
      exit(0);
    }

    if(!array_key_exists('id_unidade_rh', $_POST) || $_POST['id_unidade_rh'] === '' || $_POST['id_unidade_rh'] === null) {
      $params = http_build_query($_POST);
      $objPagina->adicionarMensagem('Nenhuma "Unidade RH" foi selecionada', InfraPagina::$TIPO_MSG_AVISO);
      header('Location: '.$objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_cadastrar&acao_origem='.$_GET['acao'].'&id_mapeamento='.$numIdUnidade.PaginaSEI::getInstance()->montarAncora($numIdUnidade). '&'.$params));
      exit(0);
    }

      $objGenericoBD = new GenericoBD($objBanco);
      $objPenUnidadeRHDTO = new PenUnidadeDTO();
    if ($_POST['id_unidade']) {
        $objPenUnidadeRHDTO->setNumIdUnidade($_POST['id_unidade'], InfraDTO::$OPER_DIFERENTE);
    }
      $objPenUnidadeRHDTO->setNumIdUnidadeRH($_POST['id_unidade_rh']);
      $objPenUnidadeRHDTO->retTodos();
      $objPenUnidadeRN = new PenUnidadeRN();
      $objResultado = $objPenUnidadeRN->listar($objPenUnidadeRHDTO);

    if (count($objResultado) > 0) {

        $unidadeDTO = new UnidadeDTO();
        $unidadeDTO->setNumIdUnidade($objResultado[0]->getNumIdUnidade(), InfraDTO::$OPER_IGUAL);

        $unidadeDTO->retNumIdUnidade();
        $unidadeDTO->retStrSigla();

        $penUnidadeRN = new PenUnidadeRN();
        $dados = $penUnidadeRN->listar($unidadeDTO);
   
      foreach ($penUnidadeRN->listar($unidadeDTO) as $dados) {
          $mapIdUnidade[$dados->getNumIdUnidade()] = $dados->getStrSigla();
      }

        $objInfraException = new InfraException();
        $objInfraException->lancarValidacao('A unidade ' . $mapIdUnidade[$objResultado[0]->getNumIdUnidade()] .' do sistema já está mapeada com a unidade '.$_POST['txtUnidadePen'].' do Portal de Administração.');     }
      // CARREGAR NOME E SIGLA DA ESTRUTURA
      $objProcessoEletronico     = new ProcessoEletronicoRN();
      $objProcessoEletronicoDTO  = $objProcessoEletronico->buscarEstrutura($numIdRepositorioOrigem, $_POST['id_unidade_rh']);

      $nomeUnidadeRH = $objProcessoEletronicoDTO->getStrNome();
      $siglaUnidadeRH = $objProcessoEletronicoDTO->getStrSigla();
      $objPenUnidadeDTO = new PenUnidadeDTO();
      $objPenUnidadeDTO->setNumIdUnidade($_POST['id_unidade']);
      $objPenUnidadeDTO->setNumIdUnidadeRH($_POST['id_unidade_rh']);
      $objPenUnidadeDTO->setStrNomeUnidadeRH($nomeUnidadeRH);
      $objPenUnidadeDTO->setStrSiglaUnidadeRH($siglaUnidadeRH);

      $numIdUnidade = '';
    if(array_key_exists(PEN_PAGINA_GET_ID, $_GET) && !empty($_GET[PEN_PAGINA_GET_ID])) {
      $objPenUnidadeDTO->setNumIdUnidade($_GET[PEN_PAGINA_GET_ID]);
      $unidade = $objPenUnidadeRN->alterar($objPenUnidadeDTO);
      $numIdUnidade = $_GET[PEN_PAGINA_GET_ID];
    }
    else {
      $unidade = $objPenUnidadeRN->cadastrar($objPenUnidadeDTO);
      $numIdUnidade = $unidade->getNumIdUnidade();
    }

      $objPenUnidadeRestricaoRN = new PenUnidadeRestricaoRN();

      $objPenUnidadeRestricaoDTO = new PenUnidadeRestricaoDTO();
      $objPenUnidadeRestricaoDTO->setNumIdUnidade($_POST['id_unidade']);
      $objPenUnidadeRestricaoDTO->setNumIdUnidadeRH($_POST['id_unidade_rh']);
      $objPenUnidadeRestricaoRN->prepararExcluir($objPenUnidadeRestricaoDTO);

      $arrObjPenUnidadeRestricaoDTO = $objPenUnidadeRestricaoRN->prepararRepoEstruturas(
        $_POST['id_unidade'],
        $_POST['id_unidade_rh'],
        !empty($_POST['hdnRepoEstruturas']) ? $_POST['hdnRepoEstruturas'] : ""
      );

    if (count($arrObjPenUnidadeRestricaoDTO) > 0) {
      $objPenUnidadeRestricaoRN->cadastrar($arrObjPenUnidadeRestricaoDTO);
    }

    $objPagina->adicionarMensagem('Mapeamento de Unidade gravado com sucesso.', 5);

      header('Location: '.$objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_listar&acao_origem='.$_GET['acao'].'&id_mapeamento='.$numIdUnidade.PaginaSEI::getInstance()->montarAncora($numIdUnidade)));
      exit(0);
  }
    // Ao por GET + ID esta carregando o formulrio
  else if(array_key_exists(PEN_PAGINA_GET_ID, $_GET) && !empty($_GET[PEN_PAGINA_GET_ID])){

      $objPenUnidadeDTO = new PenUnidadeDTO();
      $objPenUnidadeDTO->setNumIdUnidade($_GET[PEN_PAGINA_GET_ID]);
      $objPenUnidadeDTO->retTodos();

      $objEspecieDocumentalBD = new GenericoBD($objBanco);
      $objPenUnidadeDTO = $objEspecieDocumentalBD->consultar($objPenUnidadeDTO);
  }

  if(empty($objPenUnidadeDTO)){
      $objPenUnidadeDTO = new PenUnidadeDTO();
      $objPenUnidadeDTO->setNumIdUnidade('');
      $objPenUnidadeDTO->setNumIdUnidadeRH('');
  }


  if(array_key_exists(PEN_PAGINA_GET_ID, $_GET) && !empty($_GET[PEN_PAGINA_GET_ID])) {
      $objPenUnidadeDTO->setNumIdUnidade($_GET[PEN_PAGINA_GET_ID]);
  }

    //Monta o select das unidades
    $objUnidadeDTO = new UnidadeDTO();
    $arrNumIdUnidadeUsados = $objPenUnidadeRN->getIdUnidadeEmUso($objPenUnidadeDTO);

  if(!empty($arrNumIdUnidadeUsados)) {
      // Remove os que já estão em uso
      $objUnidadeDTO->setNumIdUnidade($arrNumIdUnidadeUsados, InfraDTO::$OPER_NOT_IN);
  }

    $objUnidadeDTO->retNumIdUnidade();
    $objUnidadeDTO->retStrSigla();
    $objUnidadeDTO->retStrDescricao();
    $arrMapIdUnidade = array();
    $objPenUnidadeRN = new PenUnidadeRN();
    foreach ($objPenUnidadeRN->listar($objUnidadeDTO) as $dados) {
        $arrMapIdUnidade[$dados->getNumIdUnidade()] = $dados->getStrSigla() . ' - ' . $dados->getStrDescricao();
    }

    //Verifica se o numero da unidade esta vazio, senão estiver busca o nome da unidade para exibição
    $strNomeUnidadeSelecionada = '';
  if(!empty($objPenUnidadeDTO->getNumIdUnidadeRH())){

    $objProcessoEletronico     = new ProcessoEletronicoRN();
    $objProcessoEletronicoDTO  = $objProcessoEletronico->buscarEstrutura($numIdRepositorioOrigem, $objPenUnidadeDTO->getNumIdUnidadeRH());

    if(!is_null($objProcessoEletronicoDTO)){
        $strNomeUnidadeSelecionada = $objProcessoEletronicoDTO->getStrNome();
    }else{
        $strNomeUnidadeSelecionada = 'Unidade não encontrada.';
    }
  } else if (!empty($_GET['id_unidade_rh']) && !empty($_GET['txtUnidadePen'])){
    $strNomeUnidadeSelecionada = $_GET['txtUnidadePen'];
    $objPenUnidadeDTO->setNumIdUnidadeRH($_GET['id_unidade_rh']);
  }

  $strCssRestricao = "";
  $strHtmlRestricao = "";
  $strJsGlobalRestricao = "";
  $strJsInicializarRestricao = "";
  ProcessoEletronicoINT::montarRestricaoTramitaGovBr($objPenUnidadeDTO->getNumIdUnidade(), $strCssRestricao, $strHtmlRestricao, $strJsGlobalRestricao, $strJsInicializarRestricao);
}
catch (InfraException $e) {
    $objPagina->processarExcecao($e);
}
catch(Exception $e) {
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
if ($objPenUnidadeDTO!= null)
{
    $classMarcacao = $objPenUnidadeDTO->getNumIdUnidadeRH() != '' ? 'infraAjaxMarcarSelecao' : '';
}else
{
  if(empty($objPenUnidadeDTO)){
      $objPenUnidadeDTO = new PenUnidadeDTO();
      $objPenUnidadeDTO->setNumIdUnidade('');
      $objPenUnidadeDTO->setNumIdUnidadeRH('');
  }
  
  
  if(array_key_exists(PEN_PAGINA_GET_ID, $_GET) && !empty($_GET[PEN_PAGINA_GET_ID])) {
      $objPenUnidadeDTO->setNumIdUnidade($_GET[PEN_PAGINA_GET_ID]);
  }
  
      //Monta o select das unidades
      $objUnidadeDTO = new UnidadeDTO();
      $arrNumIdUnidadeUsados = $objPenUnidadeRN->getIdUnidadeEmUso($objPenUnidadeDTO);
  
  if(!empty($arrNumIdUnidadeUsados)) {
      // Remove os que já estão em uso
      $objUnidadeDTO->setNumIdUnidade($arrNumIdUnidadeUsados, InfraDTO::$OPER_NOT_IN);
  }
  
      $objUnidadeDTO->retNumIdUnidade();
      $objUnidadeDTO->retStrSigla();
      $objUnidadeDTO->retStrDescricao();
      $arrMapIdUnidade = array();
      $objPenUnidadeRN = new PenUnidadeRN();
  foreach ($objPenUnidadeRN->listar($objUnidadeDTO) as $dados) {
      $arrMapIdUnidade[$dados->getNumIdUnidade()] = $dados->getStrSigla() . ' - ' . $dados->getStrDescricao();
  }
    $classMarcacao = '';

}
?>
<style type="text/css">

#lblUnidadeSei{position:absolute;left:0%;top:0%;width:60%;min-width:250px;}
#selUnidadeSei{position:absolute;left:0%;top:13%;width:60%;min-width:250px;}

#lblUnidadePen{position:absolute;left:0%;top:40%;width:60%;}
#txtUnidadePen{position:absolute;left:0%;top:53%;width:60%;}
#btnUnidadeRh2{position:absolute;left:61%;top:53%;}

<?=$strCssRestricao?>

</style>

<?php
$objPagina->fecharHead();
$objPagina->abrirBody($strTitulo, 'onload="inicializar();"');
?>
<form id="<?php print PEN_RECURSO_BASE; ?>_form" onsubmit="return onSubmit();" method="post" action="">
    <?php $objPagina->montarBarraComandosSuperior($arrComandos); ?>
    <?php $objPagina->abrirAreaDados('15em'); ?>

    <label id="lblUnidadeSei" for="id_unidade" class="infraLabelObrigatorio">Unidades - SEI <?php print $objSessao->getStrSiglaOrgaoUnidadeAtual(); ?>:</label>
    <select id="selUnidadeSei" name="id_unidade" class="infraSelect" >
        <?php print InfraINT::montarSelectArray('', 'Selecione', $objPenUnidadeDTO->getNumIdUnidade(), $arrMapIdUnidade); ?>
    </select>

    <label id="lblUnidadePen" for="txtUnidadePen" class="infraLabelObrigatorio">Unidades do Tramita GOV.BR (Estruturas Organizacionais):</label>
    <input type="text" id="txtUnidadePen" name="txtUnidadePen" class="infraText infraReadOnly <?php echo $classMarcacao; ?>" value="<?= PaginaSEI::tratarHTML($strNomeUnidadeSelecionada); ?>" tabindex=""/>
    <button id="btnUnidadeRh2" type="button" class="infraButton">Pesquisar</button>
    <input type="hidden" id="hdnUnidadeRh" name="id_unidade_rh" value="<?php echo PaginaSEI::tratarHTML($objPenUnidadeDTO->getNumIdUnidadeRH()); ?>" />

    <?php print $objPagina->fecharAreaDados(); ?>

    <?php $objPagina->abrirAreaDados('15em'); ?>
    <?=$strHtmlRestricao?>
    <?php print $objPagina->fecharAreaDados(); ?>
</form>
<?php $objPagina->fecharBody(); ?>
<?php $objPagina->montarJavaScript(); ?>
<script type="text/javascript">

<?=$strJsGlobalRestricao?>

var objAutoCompletarEstrutura = null;
var numIdRepositorioOrigem = '<? echo $numIdRepositorioOrigem; ?>';
var strNomeUnidadeSelecionada = '<? echo $strNomeUnidadeSelecionada; ?>';

function inicializar(){
    objAutoCompletarEstrutura = new infraAjaxAutoCompletar('hdnUnidadeRh','txtUnidadePen','<?=$strLinkAjaxUnidade?>', "Nenhuma unidade foi encontrada");
    objAutoCompletarEstrutura.bolExecucaoAutomatica = false;
    objAutoCompletarEstrutura.mostrarAviso = true;
    objAutoCompletarEstrutura.limparCampo = false;
    objAutoCompletarEstrutura.tempoAviso = 10000000;
    objAutoCompletarEstrutura.prepararExecucao = function(){
        var parametros = 'palavras_pesquisa=' + document.getElementById('txtUnidadePen').value;
        parametros += '&id_repositorio=' + numIdRepositorioOrigem
        return parametros;
    };
    objAutoCompletarEstrutura.processarResultado = function(id,descricao,complemento){
        window.infraAvisoCancelar();
    };
    $('#btnUnidadeRh2').click(function() {
        $('#hdnUnidadeRh').val('');
        objAutoCompletarEstrutura.executar();
        objAutoCompletarEstrutura.procurar();
    });
}

function onSubmit() {

    var form = jQuery('#<?php print PEN_RECURSO_BASE; ?>_form');
    var field = jQuery('select[name=id_unidade]', form);
    if(field.val() === 'null' || field.val() == ''){
        alert('Nenhuma "Unidades - SEI <?php print $objSessao->getStrSiglaOrgaoUnidadeAtual(); ?>" foi selecionada');
        field.focus();
        return false;
    }
    field = jQuery('#hdnUnidadeRh', form);
    if(field.val() === 'null' || field.val() == '' || field.val() == '0' || field.val() == 0){
        alert('Nenhum "ID da Unidade - Tramita GOV.BR" foi selecionada');
        field.focus();
        return false;
    }
}

<?=$strJsInicializarRestricao?>

</script>
<?php $objPagina->fecharHtml(); ?>
