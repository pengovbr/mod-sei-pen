<?
/**
 * Join Tecnologia
 */
try {
    require_once dirname(__FILE__) . '/../../SEI.php';

    session_start();

    define('PEN_RECURSO_ATUAL', 'pen_parametros_configuracao');
    define('PEN_PAGINA_TITULO', 'Parâmetros de Configuração do Módulo de Tramitações PEN');

    $objPagina = PaginaSEI::getInstance();
    $objBanco = BancoSEI::getInstance();
    $objSessao = SessaoSEI::getInstance();


    $o = new PenRelHipoteseLegalEnvioRN();
    $os = new PenRelHipoteseLegalRecebidoRN();

    $objSessao->validarPermissao('pen_parametros_configuracao');

    $objPenParametroDTO = new PenParametroDTO();
    $objPenParametroDTO->retTodos();
    $objPenParametroRN = new PenParametroRN();
    $retParametros = $objPenParametroRN->listar($objPenParametroDTO);

    /* Busca os dados para montar dropdown ( TIPO DE PROCESSO EXTERNO ) */
    $objTipoProcedimentoDTO = new TipoProcedimentoDTO();
    $objTipoProcedimentoDTO->retNumIdTipoProcedimento();
    $objTipoProcedimentoDTO->retStrNome();
    $objTipoProcedimentoDTO->setOrdStrNome(InfraDTO::$TIPO_ORDENACAO_ASC);
    $objTipoProcedimentoRN = new TipoProcedimentoRN();
    $arrObjTipoProcedimentoDTO = $objTipoProcedimentoRN->listarRN0244($objTipoProcedimentoDTO);

    /* Busca os dados para montar dropdown ( UNIDADE GERADORA DOCUMENTO RECEBIDO ) */
    $objUnidadeDTO = new UnidadeDTO();
    $objUnidadeDTO->retNumIdUnidade();
    $objUnidadeDTO->retStrSigla();
    $objUnidadeDTO->setOrdStrSigla(InfraDTO::$TIPO_ORDENACAO_ASC);
    $objUnidadeRN = new UnidadeRN();
    $arrObjUnidade = $objUnidadeRN->listarRN0127($objUnidadeDTO);

    if ($objPenParametroDTO===null){
        throw new PENException("Registros não encontrados.");
    }

    switch ($_GET['acao']) {
        case 'pen_parametros_configuracao_salvar':
            try {
                $objPenParametroRN = new PenParametroRN();

                if (!empty(count($_POST['parametro']))) {
                    foreach ($_POST['parametro'] as $nome => $valor) {
                        $objPenParametroDTO = new PenParametroDTO();
                        $objPenParametroDTO->setStrNome($nome);
                        $objPenParametroDTO->retStrNome();

                        if($objPenParametroRN->contar($objPenParametroDTO) > 0) {
                            $objPenParametroDTO->setStrValor($valor);
                            $objPenParametroRN->alterar($objPenParametroDTO);
                        }
                    }
                }

            } catch (Exception $e) {
                $objPagina->processarExcecao($e);
            }
            header('Location: ' . $objSessao->assinarLink('controlador.php?acao=' . $_GET['acao_origem'] . '&acao_origem=' . $_GET['acao']));
            die;

        case 'pen_parametros_configuracao':
            $strTitulo = 'Parâmetros de Configuração do Módulo de Tramitações PEN';
            break;

        default:
            throw new PENException("Ação '" . $_GET['acao'] . "' não reconhecida.");
    }

} catch (Exception $e) {
    $objPagina->processarExcecao($e);
}

//Monta os botões do topo
if ($objSessao->verificarPermissao('pen_parametros_configuracao_alterar')) {
    $arrComandos[] = '<button type="submit" id="btnSalvar" value="Salvar" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
}
$arrComandos[] = '<button type="button" id="btnCancelar" value="Cancelar" onclick="location.href=\'' . $objPagina->formatarXHTML($objSessao->assinarLink('controlador.php?acao=pen_parametros_configuracao&acao_origem=' . $_GET['acao'])) . '\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';

$objPagina->montarDocType();
$objPagina->abrirHtml();
$objPagina->abrirHead();
$objPagina->montarMeta();
$objPagina->montarTitle($objPagina->getStrNomeSistema() . ' - ' . $strTitulo);
$objPagina->montarStyle();
$objPagina->abrirStyle();
?>
<?
$objPagina->fecharStyle();
$objPagina->montarJavaScript();
$objPagina->abrirJavaScript();
?>

function inicializar(){
    if ('<?= $_GET['acao'] ?>'=='pen_parametros_configuracao_selecionar'){
        infraReceberSelecao();
        document.getElementById('btnFecharSelecao').focus();
    }else{
        document.getElementById('btnFechar').focus();
    }
    infraEfeitoImagens();
    infraEfeitoTabelas();
}

<?
$objPagina->fecharJavaScript();
$objPagina->fecharHead();
$objPagina->abrirBody($strTitulo, 'onload="inicializar();"');
?>
<style>
    .input-field-input {
        width: 35%;
        margin-bottom: 8px;
        margin-top: 2px;
    }
    .input-field {
        margin-bottom: 8px;
        margin-top: 2px;
    }
</style>

<form id="frmInfraParametroCadastro" method="post" onsubmit="return OnSubmitForm();" action="<?=$objSessao->assinarLink('controlador.php?acao='.$_GET['acao'].'_salvar&acao_origem='.$_GET['acao'])?>">
    <?
    $objPagina->montarBarraComandosSuperior($arrComandos);
    foreach ($retParametros as $parametro) {

        //Esse parâmetro não aparece, por já existencia de uma tela só para alteração do próprio.
        if ($parametro->getStrNome() != 'HIPOTESE_LEGAL_PADRAO') {
            //Constroi o label
            ?> <label id="lbl<?= PaginaSEI::tratarHTML($parametro->getStrNome()); ?>" for="txt<?= PaginaSEI::tratarHTML($parametro->getStrNome()); ?>" accesskey="N" class="infraLabelObrigatorio"><?=  PaginaSEI::tratarHTML($parametro->getStrDescricao()); ?>:</label><br> <?php
        }

        //Constroi o campo de valor
        switch ($parametro->getStrNome()) {

            //Esse parâmetro não aparece, por já existencia de uma tela só para alteração do próprio.
            case 'HIPOTESE_LEGAL_PADRAO':
                echo '';
                break;

            case 'PEN_SENHA_CERTIFICADO_DIGITAL':
                echo '<input type="password" id="PARAMETRO_'.$parametro->getStrNome().'" name="parametro['.$parametro->getStrNome().']" class="infraText input-field-input" value="'.$objPagina->tratarHTML($parametro->getStrValor()).'" onkeypress="return infraMascaraTexto(this,event);" tabindex="'.$objPagina->getProxTabDados().'" maxlength="100" /><br>';
                break;

            case 'PEN_ENVIA_EMAIL_NOTIFICACAO_RECEBIMENTO':
                echo '<select id="PARAMETRO_PEN_ENVIA_EMAIL_NOTIFICACAO_RECEBIMENTO" name="parametro[PEN_ENVIA_EMAIL_NOTIFICACAO_RECEBIMENTO]" class="input-field" >';
                echo '    <option value="S" ' . ($parametro->getStrValor() == 'S' ? 'selected="selected"' : '') . '>Sim</option>';
                echo '    <option value="N" ' . ($parametro->getStrValor() == 'N' ? 'selected="selected"' : '') . '>Não</option>';
                echo '<select>';
                break;

            case 'PEN_TIPO_PROCESSO_EXTERNO':
                echo '<select name="parametro[PEN_TIPO_PROCESSO_EXTERNO]" class="input-field" >';
                foreach ($arrObjTipoProcedimentoDTO as $procedimento) {
                    echo '<option ' . ($parametro->getStrValor() == $procedimento->getNumIdTipoProcedimento() ? 'selected="selected"' : '') . ' value="'.$procedimento->getNumIdTipoProcedimento().'">'.$procedimento->getStrNome().'</option>';
                }
                echo '<select>';
                break;

            case 'PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO':
                echo '<select name="parametro[PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO]" class="input-field" >';
                foreach ($arrObjUnidade as $unidade) {
                    echo '<option ' . ($parametro->getStrValor() == $unidade->getNumIdUnidade() ? 'selected="selected"' : '') . ' value="'.$unidade->getNumIdUnidade().'">'.$unidade->getStrSigla().'</option>';
                }
                echo '<select>';
                break;

            default:
                echo '<input type="text" id="PARAMETRO_'.$parametro->getStrNome().'" name="parametro['.$parametro->getStrNome().']" class="infraText input-field-input" value="'.$objPagina->tratarHTML($parametro->getStrValor()).'" onkeypress="return infraMascaraTexto(this,event);" tabindex="'.$objPagina->getProxTabDados().'" maxlength="100" /><br>';
                break;
        }
        echo '<br>';
    }
    ?>
</form>

<?
$objPagina->fecharBody();
$objPagina->fecharHtml();
?>
