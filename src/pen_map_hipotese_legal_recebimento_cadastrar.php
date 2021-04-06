<?php

require_once DIR_SEI_WEB.'/SEI.php';

session_start();

define('PEN_RECURSO_ATUAL', 'pen_map_hipotese_legal_recebimento_cadastrar');
define('PEN_RECURSO_BASE', 'pen_map_hipotese_legal_recebimento');
define('PEN_PAGINA_TITULO', 'Mapeamento de Hipótese Legal para Recebimento');
define('PEN_PAGINA_GET_ID', 'id_mapeamento');


$objPagina = PaginaSEI::getInstance();
$objBanco = BancoSEI::getInstance();
$objSessao = SessaoSEI::getInstance();


try {
    $objSessao->validarLink();
    $objSessao->validarPermissao(PEN_RECURSO_ATUAL);
    $arrComandos = array();
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
            $arrComandos[] = '<button type="button" name="btnFechar" value="Fechar class="infraButton" onclick="location.href=\'' . $objPagina->formatarXHTML($objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_listar&acao_origem=' . $_GET['acao'])) . '\';"><span class="infraTeclaAtalho">F</span>echar</button>';
            $bolSomenteLeitura = true;
           $strTitulo =  sprintf('Consultar %s', PEN_PAGINA_TITULO);
            break;


        default:
            throw new InfraException("Ação '" . $_GET['acao'] . "' não reconhecida.");
    }

    $objPenRelHipoteseLegalRN = new PenRelHipoteseLegalRecebidoRN();

    //--------------------------------------------------------------------------
    // Ao por POST esta salvando o formulrio
    if(strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {

        if(!array_key_exists('id_hipotese_legal', $_POST) || empty($_POST['id_hipotese_legal'])) {
            throw new InfraException('Nenhuma "Espécie Documental" foi selecionada');
        }

        if(!array_key_exists('id_barramento', $_POST) || empty($_POST['id_barramento'])) {
            throw new InfraException('Nenhum "Tipo de Documento" foi selecionado');
        }

        $objPenRelHipoteseLegalDTO = new PenRelHipoteseLegalDTO();
        $objPenRelHipoteseLegalDTO->setNumIdHipoteseLegal($_POST['id_hipotese_legal']);
        $objPenRelHipoteseLegalDTO->setNumIdBarramento($_POST['id_barramento']);
        $objPenRelHipoteseLegalDTO->setStrTipo('R');// Recebido

        $numIdMapeamento = 0;
        if(array_key_exists(PEN_PAGINA_GET_ID, $_GET) && !empty($_GET[PEN_PAGINA_GET_ID])) {
            $objPenRelHipoteseLegalDTO->setDblIdMap($_GET[PEN_PAGINA_GET_ID]);
            $objPenRelHipoteseLegalRN->alterar($objPenRelHipoteseLegalDTO);
            
            if(!method_exists($mapeamento, 'contemValidacoes')){
                $numIdMapeamento = $_GET[PEN_PAGINA_GET_ID];
            }            
        }
        else {
            $mapeamento = $objPenRelHipoteseLegalRN->cadastrar($objPenRelHipoteseLegalDTO);
            
            if(!method_exists($mapeamento, 'contemValidacoes')){
                $numIdMapeamento = $mapeamento->getDblIdMap();
            }
        }

        if(method_exists($mapeamento, 'contemValidacoes')){
            $arrObjInfraValidacao = $mapeamento->getArrObjInfraValidacao(0);
            $objPagina->adicionarMensagem(sprintf('%s '. $arrObjInfraValidacao[0]->getStrDescricao(), PEN_PAGINA_TITULO), InfraPagina::$TIPO_MSG_AVISO);
        }

        header('Location: '.$objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_listar&acao_origem='.$_GET['acao'].'&acao_origem='.$_GET['acao'].'&id_mapeamento='.$numIdMapeamento.PaginaSEI::getInstance()->montarAncora($numIdMapeamento)));
        exit(0);
    }
    // Ao por GET + ID esta carregando o formulrio
    else if(array_key_exists(PEN_PAGINA_GET_ID, $_GET) && !empty($_GET[PEN_PAGINA_GET_ID])){

        $objPenRelHipoteseLegalDTO = new PenRelHipoteseLegalDTO();
        $objPenRelHipoteseLegalDTO->setDblIdMap($_GET[PEN_PAGINA_GET_ID]);
        $objPenRelHipoteseLegalDTO->retTodos();

        $objPenRelHipoteseLegalRN = new PenRelHipoteseLegalRecebidoRN();
        $objPenRelHipoteseLegalDTO = $objPenRelHipoteseLegalRN->consultar($objPenRelHipoteseLegalDTO);
    }

    if(empty($objPenRelHipoteseLegalDTO)){
        $objPenRelHipoteseLegalDTO = new PenRelHipoteseLegalDTO();
        $objPenRelHipoteseLegalDTO->setNumIdHipoteseLegal(0);
        $objPenRelHipoteseLegalDTO->setNumIdBarramento(0);
    }

    if(array_key_exists(PEN_PAGINA_GET_ID, $_GET) && !empty($_GET[PEN_PAGINA_GET_ID])) {
        $objPenRelHipoteseLegalDTO->setDblIdMap($_GET[PEN_PAGINA_GET_ID]);
    }

    //--------------------------------------------------------------------------
    // Auto-Complete
    //--------------------------------------------------------------------------
    // Mapeamento da hipotese legal local
    $objHipoteseLegalDTO = new HipoteseLegalDTO();
    $objHipoteseLegalDTO->setStrStaNivelAcesso(1);
    $objHipoteseLegalDTO->setOrdStrNome(InfraDTO::$TIPO_ORDENACAO_ASC);
    $objHipoteseLegalDTO->retNumIdHipoteseLegal();
    $objHipoteseLegalDTO->retStrNome();

    $objHipoteseLegalRN = new HipoteseLegalRN();
    $arrMapIdHipoteseLegal = InfraArray::converterArrInfraDTO($objHipoteseLegalRN->listar($objHipoteseLegalDTO), 'Nome', 'IdHipoteseLegal');

    // Mapeamento da hipotese legal do barramento j utilizados
    $arrNumIdHipoteseLegal = $objPenRelHipoteseLegalRN->getIdBarramentoEmUso($objPenRelHipoteseLegalDTO, 'R');

    // Mapeamento da hipotese legal remota
    $objPenHipoteseLegalDTO = new PenHipoteseLegalDTO();
    $objPenHipoteseLegalDTO->setOrdStrNome(InfraDTO::$TIPO_ORDENACAO_ASC);
    if(!empty($arrNumIdHipoteseLegal)) {
        // Remove os que j esto em uso
        $objPenHipoteseLegalDTO->setNumIdHipoteseLegal($arrNumIdHipoteseLegal, InfraDTO::$OPER_NOT_IN);
    }
    $objPenHipoteseLegalDTO->retNumIdHipoteseLegal();
    $objPenHipoteseLegalDTO->retStrNome();

    $objPenHipoteseLegalRN = new PenHipoteseLegalRN();
    $arrMapIdBarramento = InfraArray::converterArrInfraDTO($objPenHipoteseLegalRN->listar($objPenHipoteseLegalDTO), 'Nome', 'IdHipoteseLegal');
    //--------------------------------------------------------------------------
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
?>
<style type="text/css">

.input-label-first{position:absolute;left:0%;top:40%;width:50%; color: #666!important}
.input-field-first{position:absolute;left:0%;top:55%;width:50%}

.input-label-third {position:absolute;left:0%;top:00%;width:50%; color:#666!important}
.input-field-third {position:absolute;left:0%;top:15%;width:50%;}

</style>
<?php $objPagina->montarJavaScript(); ?>
<script type="text/javascript">

function inicializar(){


}

function onSubmit() {

    var form = jQuery('#<?php print PEN_RECURSO_BASE; ?>_form');
    var field = jQuery('select[name=id_hipotese_legal]', form);

    if(field.val() === 'null' || !field.val()){
        alert('Nenhuma "Hipótese Legal SEI - <?php print $objSessao->getStrSiglaOrgaoUnidadeAtual(); ?>" foi selecionada');
        field.focus();
        return false;
    }

    field = jQuery('select[name=id_barramento]', form);

    if(field.val() === 'null' || !field.val()){
        alert('Nenhum "Hipótese Legal PEN" foi selecionado');
        field.focus();
        return false;
    }
}

</script>
<?php
$objPagina->fecharHead();
$objPagina->abrirBody($strTitulo,'onload="inicializar();"');
?>
<form id="<?php print PEN_RECURSO_BASE; ?>_form" onsubmit="return onSubmit();" method="post" action="<?php //print $objSessaoSEI->assinarLink($strProprioLink);  ?>">
    <?php $objPagina->montarBarraComandosSuperior($arrComandos); ?>
    <?php $objPagina->montarAreaValidacao(); ?>
    <?php $objPagina->abrirAreaDados('12em'); ?>

    <label for="id_barramento" class="infraLabelObrigatorio input-label-third">Hipótese Legal PEN:</label>
    <select name="id_barramento" class="infraSelect input-field-third"<?php if($bolSomenteLeitura): ?> disabled="disabled" readonly="readonly"<?php endif; ?>>
        <?php print InfraINT::montarSelectArray('null', '', $objPenRelHipoteseLegalDTO->getNumIdBarramento(),  $arrMapIdBarramento); ?>
    </select>

    <label for="id_hipotese_legal" class="infraLabelObrigatorio input-label-first">Hipótese Legal SEI - <?php print $objSessao->getStrSiglaOrgaoUnidadeAtual(); ?>:</label>
    <select name="id_hipotese_legal" class="infraSelect input-field-first"<?php if($bolSomenteLeitura): ?>  disabled="disabled" readonly="readonly"<?php endif; ?>>
        <?php print InfraINT::montarSelectArray('null', '', $objPenRelHipoteseLegalDTO->getNumIdHipoteseLegal(), $arrMapIdHipoteseLegal); ?>
    </select>

    <?php print $objPagina->fecharAreaDados(); ?>
</form>
<?php $objPagina->fecharBody(); ?>
<?php $objPagina->fecharHtml(); ?>
