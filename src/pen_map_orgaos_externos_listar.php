<?php

require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Consulta os logs do estado do procedimento ao ser expedido
 *
 *
 */

session_start();

define('PEN_RECURSO_ATUAL', 'pen_map_orgaos_externos_listar');
define('PEN_RECURSO_BASE', 'pen_map_orgaos_externos');
define('PEN_PAGINA_TITULO', 'Relacionamento entre Org�os');
define('PEN_PAGINA_GET_ID', 'id');


$objPagina = PaginaSEI::getInstance();
$objBanco = BancoSEI::getInstance();
$objSessao = SessaoSEI::getInstance();
$objDebug = InfraDebug::getInstance();


try {

    $objDebug->setBolLigado(false);
    $objDebug->setBolDebugInfra(true);
    $objDebug->limpar();

    $objSessao->validarLink();
    $objSessao->validarPermissao(PEN_RECURSO_ATUAL);


    //--------------------------------------------------------------------------
    // A��es
    if (array_key_exists('acao', $_GET)) {

        $arrParam = array_merge($_GET, $_POST);

        switch ($_GET['acao']) {

            case 'pen_map_orgaos_externos_excluir':
                if (array_key_exists('hdnInfraItensSelecionados', $arrParam) && !empty($arrParam['hdnInfraItensSelecionados'])) {

                    $objPenOrgaoExternoDTO = new PenOrgaoExternoDTO();
                    $objPenOrgaoExternoRN = new PenOrgaoExternoRN();

                    $arrParam['hdnInfraItensSelecionados'] = explode(',', $arrParam['hdnInfraItensSelecionados']);
                    
                    if (is_array($arrParam['hdnInfraItensSelecionados'])) {
                        foreach ($arrParam['hdnInfraItensSelecionados'] as $dblId) {
                            $objPenOrgaoExternoDTO->setDblId($dblId);
                            $objPenOrgaoExternoRN->excluir($objPenOrgaoExternoDTO);
                        }
                    } else {
                        $objPenOrgaoExternoDTO->setDblId($arrParam['hdnInfraItensSelecionados']);
                        $objPenOrgaoExternoRN->excluir($objPenOrgaoExternoDTO);
                    }

                    $objPagina->adicionarMensagem(sprintf('%s foi excluido com sucesso.', PEN_PAGINA_TITULO), InfraPagina::$TIPO_MSG_AVISO);

                    header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . $_GET['acao_retorno'] . '&acao_origem=' . $_GET['acao_origem']));
                    exit(0);
                } else {

                    throw new InfraException('Nenhum Registro foi selecionado para executar esta a��o');
                }
                break;

            case 'pen_map_orgaos_externos_listar':
                // A��o padr�o desta tela
                break;

            case 'pen_map_orgaos_externos_reativar':
                if(isset($_POST['hdnInfraItensSelecionados']) && !empty($_POST['hdnInfraItensSelecionados'])) {
                    $arrHdnInInfraItensSelecionados = explode(",", $_POST['hdnInfraItensSelecionados']);
                    foreach ($arrHdnInInfraItensSelecionados as $id) {
                        $objPenOrgaoExternoDTO = new PenOrgaoExternoDTO();
                        $objPenOrgaoExternoDTO->setDblId($id);
                        $objPenOrgaoExternoDTO->setStrAtivo('S');
    
                        $objPenOrgaoExternoRN = new PenOrgaoExternoRN();
                        $objPenOrgaoExternoRN->alterar($objPenOrgaoExternoDTO);
                    }
                    $objPagina->adicionarMensagem(sprintf('%s foi reativado com sucesso.', PEN_PAGINA_TITULO), InfraPagina::$TIPO_MSG_AVISO);
                }
    
                if (isset($_POST['hdnInfraItemId']) && is_numeric($_POST['hdnInfraItemId'])) {
                    $objPenOrgaoExternoDTO = new PenOrgaoExternoDTO();
                    $objPenOrgaoExternoDTO->setDblId($_POST['hdnInfraItemId']);
                    $objPenOrgaoExternoDTO->setStrAtivo('S');
    
                    $objPenOrgaoExternoRN = new PenOrgaoExternoRN();
                    $objPenOrgaoExternoRN->alterar($objPenOrgaoExternoDTO);
                    $objPagina->adicionarMensagem(sprintf('%s foi reativado com sucesso.', PEN_PAGINA_TITULO), InfraPagina::$TIPO_MSG_AVISO);
                }
                break;


            default:
                throw new InfraException('A��o n�o permitida nesta tela');
        }
    }
    //--------------------------------------------------------------------------

    $arrComandos = array();
    $arrComandos[] = '<button type="button" accesskey="P" onclick="onClickBtnPesquisar();" id="btnPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';

    //--------------------------------------------------------------------------
    // DTO de paginao
    $objPenOrgaoExternoDTO = new PenOrgaoExternoDTO();
    $objPenOrgaoExternoDTO->setNumIdUnidade($objSessao->getNumIdUnidadeAtual());
    $objPenOrgaoExternoDTO->retDblId();
    $objPenOrgaoExternoDTO->retNumIdOrgaoOrigem();
    $objPenOrgaoExternoDTO->retStrOrgaoOrigem();
    $objPenOrgaoExternoDTO->retNumIdOrgaoDestino();
    $objPenOrgaoExternoDTO->retStrOrgaoDestino();
    $objPenOrgaoExternoDTO->retStrAtivo();

    //--------------------------------------------------------------------------
    // Filtragem
    if (array_key_exists('txtSiglaOrigem', $_POST) && (!empty($_POST['txtSiglaOrigem']) && $_POST['txtSiglaOrigem'] !== 'null')) {
        $objPenOrgaoExternoDTO->setStrOrgaoOrigem('%' . $_POST['txtSiglaOrigem'] . '%', InfraDTO::$OPER_LIKE);
    }

    if (array_key_exists('txtSiglaDestino', $_POST) && (!empty($_POST['txtSiglaDestino']) && $_POST['txtSiglaDestino'] !== 'null')) {
        $objPenOrgaoExternoDTO->setStrOrgaoDestino('%' . $_POST['txtSiglaDestino'] . '%', InfraDTO::$OPER_LIKE);
    }

    if (array_key_exists('txtEstado', $_POST) && (!empty($_POST['txtEstado']) && $_POST['txtEstado'] !== 'null')) {
        $objPenOrgaoExternoDTO->setStrAtivo($_POST['txtEstado']);
    }

    //--------------------------------------------------------------------------

    $objPagina->prepararOrdenacao($objPenOrgaoExternoDTO, 'Id', InfraDTO::$TIPO_ORDENACAO_ASC);
    $objPagina->prepararPaginacao($objPenOrgaoExternoDTO, 10);

    $objPenOrgaoExternoRN = new PenOrgaoExternoRN();
    $respObjPenOrgaoExternoDTO = $objPenOrgaoExternoRN->listar($objPenOrgaoExternoDTO);

    $objPagina->processarPaginacao($objPenOrgaoExternoDTO);

    $numRegistros = count($respObjPenOrgaoExternoDTO);
    if (!empty($respObjPenOrgaoExternoDTO)) {

        $strResultado = '';

        $strResultado .= '<table width="99%" class="infraTable">' . "\n";
        $strResultado .= '<caption class="infraCaption">' . $objPagina->gerarCaptionTabela(PEN_PAGINA_TITULO, $numRegistros) . '</caption>';

        $strResultado .= '<tr>';
        $strResultado .= '<th class="infraTh" width="1%">' . $objPagina->getThCheck() . '</th>' . "\n";
        $strResultado .= '<th class="infraTh" width="12%">' . $objPagina->getThOrdenacao($objPenOrgaoExternoDTO, 'ID <br><small>Origem</small>', 'IdOrgaoOrigem', $respObjPenOrgaoExternoDTO) . '</th>' . "\n";
        $strResultado .= '<th class="infraTh" width="25%">' . $objPagina->getThOrdenacao($objPenOrgaoExternoDTO, '�rg�o Origem', 'OrgaoOrigem', $respObjPenOrgaoExternoDTO) . '</th>' . "\n";
        $strResultado .= '<th class="infraTh" width="12%">' . $objPagina->getThOrdenacao($objPenOrgaoExternoDTO, 'ID <br><small>Destino</small>', 'IdOrgaoDestino', $respObjPenOrgaoExternoDTO) . '</th>' . "\n";
        $strResultado .= '<th class="infraTh" width="25%">' . $objPagina->getThOrdenacao($objPenOrgaoExternoDTO, '�rg�o Destino', 'OrgaoDestino', $respObjPenOrgaoExternoDTO) . '</th>' . "\n";
        $strResultado .= '<th class="infraTh" width="15%">A��es</th>' . "\n";
        $strResultado .= '</tr>' . "\n";
        $strCssTr = '';

        $index = 0;
        $botaoReativarAdicionado = 'N';
        foreach ($respObjPenOrgaoExternoDTO as $objPenOrgaoExternoDTO) {
            $strCssTr = ($strCssTr == 'infraTrClara') ? 'infraTrEscura' : 'infraTrClara';

            $strResultado .= '<tr class="' . $strCssTr . '">';
            $strResultado .= '<td align="center">' . $objPagina->getTrCheck($index, $objPenOrgaoExternoDTO->getDblId(), '') . '</td>';
            $strResultado .= '<td align="center">' . $objPenOrgaoExternoDTO->getNumIdOrgaoOrigem() . '</td>';
            $strResultado .= '<td align="center">' . $objPenOrgaoExternoDTO->getStrOrgaoOrigem() . '</td>';
            $strResultado .= '<td align="center">' . $objPenOrgaoExternoDTO->getNumIdOrgaoDestino() . '</td>';
            $strResultado .= '<td align="center">' . $objPenOrgaoExternoDTO->getStrOrgaoDestino() . '</td>';
            $strResultado .= '<td align="center">';

            if($objSessao->verificarPermissao('pen_map_orgaos_externos_reativar') && $objPenOrgaoExternoDTO->getStrAtivo() == 'N') {
                $strLinkReativar = $objSessao->assinarLink('controlador.php?acao=pen_map_orgaos_externos_reativar&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao'].'&'.PEN_PAGINA_GET_ID.'='.$objPenOrgaoExternoDTO->getDblId());
                $strId = $objPenOrgaoExternoDTO->getDblId();
                if ($botaoReativarAdicionado == 'N') {
                    $arrComandos[] = '<button type="button" id="btnReativar" value="Reativar" onclick="onClickBtnReativar()" class="infraButton">Reativar</button>';
                    $botaoReativarAdicionado = 'S';
                } 
                $strResultado .= '<a class="reativar" href="'.PaginaSEI::getInstance()->montarAncora($strId).'" onclick="acaoReativar(\''.$strId.'\')"><img src="'. PaginaSEI::getInstance()->getIconeReativar() .'" title="Reativar Relacionamento entre �rg�os" alt="Reativar Relacionamento entre �rg�os" class="infraImg"></a>';
            }

            if ($objSessao->verificarPermissao('pen_map_orgaos_externos_excluir')) {
                $strResultado .= '<a href="#" onclick="onCLickLinkDelete(\''
                    . $objSessao->assinarLink(
                        'controlador.php?acao=pen_map_orgaos_externos_excluir&acao_origem='
                            . $_GET['acao_origem']
                            . '&acao_retorno=' . $_GET['acao']
                            . '&hdnInfraItensSelecionados=' . $objPenOrgaoExternoDTO->getDblId()
                    )
                    . '\', this)">'
                    . '<img src='
                    . ProcessoEletronicoINT::getCaminhoIcone("imagens/excluir.gif")
                    . ' title="Excluir Mapeamento" alt="Excluir Mapeamento" class="infraImg">'
                    . '</a>';
            }

            $strResultado .= '</td>';
            $strResultado .= '</tr>' . "\n";

            $index++;
        }
        $strResultado .= '</table>';
    }
    $arrComandos[] = '<button type="button" value="Novo" onclick="onClickBtnNovo()" class="infraButton"><span class="infraTeclaAtalho">N</span>ovo</button>';
    //$arrComandos[] = '<button type="button" value="Desativar" onclick="onClickBtnDesativar()" class="infraButton">Desativar</button>';
    $arrComandos[] = '<button type="button" value="Excluir" onclick="onClickBtnExcluir()" class="infraButton"><span class="infraTeclaAtalho">E</span>xcluir</button>';
    $arrComandos[] = '<button type="button" accesskey="I" id="btnImprimir" value="Imprimir" onclick="infraImprimirTabela();" class="infraButton"><span class="infraTeclaAtalho">I</span>mprimir</button>';
} catch (InfraException $e) {
    $objPagina->processarExcecao($e);
}


$objPagina->montarDocType();
$objPagina->abrirHtml();
$objPagina->abrirHead();
$objPagina->montarMeta();
$objPagina->montarTitle(':: ' . $objPagina->getStrNomeSistema() . ' - ' . PEN_PAGINA_TITULO . ' ::');
$objPagina->montarStyle();
?>
<style type="text/css">
    .lblSiglaOrigem {
        position: absolute;
        left: 0%;
        top: 0%;
        width: 25%;
    }

    #txtSiglaOrigem {
        position: absolute;
        left: 0%;
        top: 50%;
        width: 25%
    }

    #lblSiglaDestino {
        position: absolute;
        left: 26%;
        top: 0%;
        width: 25%;
    }

    #txtSiglaDestino {
        position: absolute;
        left: 26%;
        top: 50%;
        width: 25%;
    }

    #lblEstado {
        position: absolute;
        left: 52%;
        top: 0%;
        width: 25%;
    }

    #txtEstado {
        position: absolute;
        left: 52%;
        top: 50%;
        width: 25%;
    }
    #txtEstadoSelect {
        position: absolute;
        left: 52%;
        top: 50%;
        width: 25%;
    }
</style>
<?php $objPagina->montarJavaScript(); ?>
<script type="text/javascript">
    function inicializar() {

        infraEfeitoTabelas();

    }

    function onClickBtnPesquisar() {
        document.getElementById('frmAcompanharEstadoProcesso').action = '<?php print $objSessao->assinarLink('controlador.php?acao=' . $_GET['acao'] . '&acao_origem=' . $_GET['acao_origem'] . '&acao_retorno=' . $_GET['acao_retorno']); ?>';
        document.getElementById('frmAcompanharEstadoProcesso').submit();
    }

    function tratarEnter(ev) {
        var key = infraGetCodigoTecla(ev);
        if (key == 13) {
            onClickBtnPesquisar();
        }
        return true;
    }

    function onCLickLinkDelete(url, link) {

        var row = jQuery(link).parents('tr:first');

        var strEspecieDocumental = row.find('td:eq(1)').text();
        var strTipoDocumento = row.find('td:eq(2)').text();

        if (confirm('Confirma a exclus�o do mapeamento do orgao?')) {

            window.location = url;
        }

    }

    function onClickBtnNovo() {

        window.location = '<?php print $objSessao->assinarLink('controlador.php?acao=' . PEN_RECURSO_BASE . '_cadastrar&acao_origem=' . $_GET['acao_origem'] . '&acao_retorno=' . $_GET['acao_origem']); ?>';
    }

    function onClickBtnDesativar() {

        try {

            var form = jQuery('#frmAcompanharEstadoProcesso');
            form.attr('action', '<?php print $objSessao->assinarLink('controlador.php?acao=' . PEN_RECURSO_BASE . '_desativar&acao_origem=' . $_GET['acao_origem'] . '&acao_retorno=' . PEN_RECURSO_BASE . '_listar'); ?>');
            form.submit();
        } catch (e) {

            alert('Erro : ' + e.message);
        }
    }

    function onClickBtnExcluir() {

        try {

            var len = jQuery('input[name*=chkInfraItem]:checked').length;

            if (len > 0) {

                if (confirm('Confirma a exclus�o de ' + len + ' mapeamento(s) ?')) {
                    var form = jQuery('#frmAcompanharEstadoProcesso');
                    form.attr('action', '<?php print $objSessao->assinarLink('controlador.php?acao=' . PEN_RECURSO_BASE . '_excluir&acao_origem=' . $_GET['acao_origem'] . '&acao_retorno=' . PEN_RECURSO_BASE . '_listar'); ?>');
                    form.submit();
                }
            } else {

                alert('Selecione pelo menos um mapeamento para Excluir');
            }
        } catch (e) {

            alert('Erro : ' + e.message);
        }
    }

    function acaoReativar(id){

        if (confirm("Confirma a reativa��o do relacionamento entre �rg�os?")) {
            document.getElementById('hdnInfraItemId').value=id;
            document.getElementById('frmAcompanharEstadoProcesso').action='<?=$strLinkReativar?>';
            document.getElementById('frmAcompanharEstadoProcesso').submit();
        }
    }

    function onClickBtnReativar(){
        try {
            var len = jQuery('input[name*=chkInfraItem]:checked').length;
            if (len > 0) {
                if (confirm('Confirma a reativa��o de ' + len + ' relacionamento(s) entre �rg�os ?')) {
                    var form = jQuery('#frmAcompanharEstadoProcesso');
                    form.attr('action', '<?php print $objSessao->assinarLink('controlador.php?acao='.PEN_RECURSO_BASE.'_reativar&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.PEN_RECURSO_BASE.'_listar'); ?>');
                    form.submit();
                }
            } else {
                alert('Selecione pelo menos um relacionamento para reativar');
            }
        } catch(e) {
            alert('Erro : ' + e.message);
        }
    }

</script>
<?php
$objPagina->fecharHead();
$objPagina->abrirBody(PEN_PAGINA_TITULO, 'onload="inicializar();"');
?>
<form id="frmAcompanharEstadoProcesso" method="post" action="">

    <?php $objPagina->montarBarraComandosSuperior($arrComandos); ?>
    <?php $objPagina->abrirAreaDados('5em'); ?>

    <label for="txtSiglaOrigem" id="lblSiglaOrigem" class="lblSigla infraLabelOpcional">�rg�o Origem:</label>
    <input type="text" id="txtSiglaOrigem" name="txtSiglaOrigem" class="infraText" value="<?= PaginaSEI::tratarHTML(isset($_POST['txtSiglaOrigem']) ? $_POST['txtSiglaOrigem'] : ''); ?>">

    <label for="txtSiglaDestino" id="lblSiglaDestino" class="lblSigla infraLabelOpcional">�rg�o Destino:</label>
    <input type="text" id="txtSiglaDestino" name="txtSiglaDestino" class="infraText" value="<?= PaginaSEI::tratarHTML(isset($_POST['txtSiglaDestino']) ? $_POST['txtSiglaDestino'] : ''); ?>">

    <label for="txtEstado" id="lblEstado" class="infraLabelOpcional">Estado:</label>
    <input type="text" id="txtEstado" name="txtEstado" class="infraText" value="<?= PaginaSEI::tratarHTML(isset($_POST['txtEstado']) ? $_POST['txtEstado'] : ''); ?>">
    <select id="txtEstadoSelect" name="txtEstado" onchange="this.form.submit();" class="infraSelect" tabindex="<?= PaginaSEI::getInstance()->getProxTabDados() ?>">
        <option value=""></option>
        <option value="S" <?= PaginaSEI::tratarHTML(isset($_POST['txtEstado']) && $_POST['txtEstado'] == "S" ? 'selected="selected"' : ''); ?>>Ativo</option>
        <option value="N" <?= PaginaSEI::tratarHTML(isset($_POST['txtEstado']) && $_POST['txtEstado'] == "N" ? 'selected="selected"' : ''); ?>>Inativo</option>
    </select>

    <?php $objPagina->fecharAreaDados(); ?>
    <br />
    <?php if ($numRegistros > 0) : ?>
        <?php $objPagina->montarAreaTabela($strResultado, $numRegistros); ?>
        <?php $objPagina->montarAreaDebug(); ?>
    <?php else : ?>
        <div style="clear:both;margin:2em"></div>
        <p>Nenhum registro encontrado</p>
    <?php endif; ?>
</form>
<?php $objPagina->fecharBody(); ?>
<?php $objPagina->fecharHtml(); ?>