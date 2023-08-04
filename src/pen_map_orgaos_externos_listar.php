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
define('PEN_PAGINA_TITULO', 'Relacionamento entre Orgãos');
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
    // Ações
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

                    throw new InfraException('Nenhum Registro foi selecionado para executar esta ação');
                }
                break;
            case 'pen_importar_tipos_processos': {

                try{
                    $penMapTipoProcedimentoRN = new PenMapTipoProcedimentoRN();
                    $arrProcedimentoDTO = [];
                    $procedimentos = explode(',', $_POST['dados']);
                    foreach ($procedimentos as $procedimento) {
                        $procedimentoDTO = new PenMapTipoProcedimentoDTO();
                        $procedimentoDTO->setNumIdMapOrgao($_POST['mapId']);
                        $procedimentoDTO->setNumIdProcessoOrigem($procedimento);
                        $procedimentoDTO->setNumIdUnidade($_GET['infra_unidade_atual']);
                        $procedimentoDTO->setDthRegistro(date('d/m/Y H:i:s'));
                        if ($penMapTipoProcedimentoRN->contar($procedimentoDTO)) {
                            continue;
                        }
                        $penMapTipoProcedimentoRN->cadastrar($procedimentoDTO);
                    }
                    $objPagina->adicionarMensagem('Importação realizada com sucesso.', InfraPagina::$TIPO_MSG_INFORMACAO);
                    header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . $_GET['acao_retorno'] . '&acao_origem=' . $_GET['acao_origem']));
                    exit(0);
                } catch (Exception $e) {
                    throw new InfraException($e->getMessage());
                }

            }

            case 'pen_map_orgaos_externos_listar':
                // Ação padrão desta tela
                break;

            default:
                throw new InfraException('Ação não permitida nesta tela');
        }
    }
    //--------------------------------------------------------------------------

    $arrComandos = array();
    $arrComandos[] = '<button type="button" accesskey="P" onclick="onClickBtnPesquisar();" id="btnPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';
    $arrComandos[] = '<button type="button" value="Novo" id="btnNovo" onclick="onClickBtnNovo()" class="infraButton"><span class="infraTeclaAtalho">N</span>ovo</button>';
    //$arrComandos[] = '<button type="button" value="Ativar" onclick="onClickBtnAtivar()" class="infraButton">Ativar</button>';
    //$arrComandos[] = '<button type="button" value="Desativar" onclick="onClickBtnDesativar()" class="infraButton">Desativar</button>';
    $arrComandos[] = '<button type="button" value="Excluir" onclick="onClickBtnExcluir()" class="infraButton"><span class="infraTeclaAtalho">E</span>xcluir</button>';
    $arrComandos[] = '<button type="button" accesskey="I" id="btnImprimir" value="Imprimir" onclick="infraImprimirTabela();" class="infraButton"><span class="infraTeclaAtalho">I</span>mprimir</button>';
    #$arrComandos[] = '<button type="button" accesskey="M" id="btnImportar" value="Importar" onclick="infraImportarCsv();" class="infraButton"><span class="infraTeclaAtalho">I</span>mportar</button>';

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
        $strResultado .= '<th class="infraTh" width="25%">' . $objPagina->getThOrdenacao($objPenOrgaoExternoDTO, 'Órgão Origem', 'OrgaoOrigem', $respObjPenOrgaoExternoDTO) . '</th>' . "\n";
        $strResultado .= '<th class="infraTh" width="12%">' . $objPagina->getThOrdenacao($objPenOrgaoExternoDTO, 'ID <br><small>Destino</small>', 'IdOrgaoDestino', $respObjPenOrgaoExternoDTO) . '</th>' . "\n";
        $strResultado .= '<th class="infraTh" width="25%">' . $objPagina->getThOrdenacao($objPenOrgaoExternoDTO, 'Órgão Destino', 'OrgaoDestino', $respObjPenOrgaoExternoDTO) . '</th>' . "\n";
        $strResultado .= '<th class="infraTh" width="15%">Ações</th>' . "\n";
        $strResultado .= '</tr>' . "\n";
        $strCssTr = '';

        $index = 0;
        foreach ($respObjPenOrgaoExternoDTO as $objPenOrgaoExternoDTO) {
            $strCssTr = ($strCssTr == 'infraTrClara') ? 'infraTrEscura' : 'infraTrClara';

            $strResultado .= '<tr class="' . $strCssTr . '">';
            $strResultado .= '<td align="center">' . $objPagina->getTrCheck($index, $objPenOrgaoExternoDTO->getDblId(), '') . '</td>';
            $strResultado .= '<td align="center">' . $objPenOrgaoExternoDTO->getNumIdOrgaoOrigem() . '</td>';
            $strResultado .= '<td align="center">' . $objPenOrgaoExternoDTO->getStrOrgaoOrigem() . '</td>';
            $strResultado .= '<td align="center">' . $objPenOrgaoExternoDTO->getNumIdOrgaoDestino() . '</td>';
            $strResultado .= '<td align="center">' . $objPenOrgaoExternoDTO->getStrOrgaoDestino() . '</td>';
            $strResultado .= '<td align="center">';

            if ($objSessao->verificarPermissao('pen_map_orgaos_externos_excluir')) {
                $strResultado .= '<a href="#" id="importarCsvButton" onclick="infraImportarCsv('. $objPenOrgaoExternoDTO->getDblId(). ')">'
                    . '<img src='
                    . ProcessoEletronicoINT::getCaminhoIcone("imagens/clonar.gif")
                    . ' title="Importar CSV" alt="Importar CSV" style="margin-bottom: 2.5px">'
                    . '</a>';
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

        if (confirm('Confirma a exclusão do mapeamento do orgao?')) {

            window.location = url;
        }

    }

    function onClickBtnNovo() {

        window.location = '<?php print $objSessao->assinarLink('controlador.php?acao=' . PEN_RECURSO_BASE . '_cadastrar&acao_origem=' . $_GET['acao_origem'] . '&acao_retorno=' . $_GET['acao_origem']); ?>';
    }

    function onClickBtnAtivar() {

        try {

            var form = jQuery('#frmAcompanharEstadoProcesso');
            form.attr('action', '<?php print $objSessao->assinarLink('controlador.php?acao=' . PEN_RECURSO_BASE . '_ativar&acao_origem=' . $_GET['acao_origem'] . '&acao_retorno=' . PEN_RECURSO_BASE . '_listar'); ?>');
            form.submit();
        } catch (e) {

            alert('Erro : ' + e.message);
        }

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

                if (confirm('Confirma a exclusão de ' + len + ' mapeamento(s) ?')) {
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

    function infraImportarCsv(orgaoId) {
        document.getElementById('mapId').value = orgaoId;
        $('#importArquivoCsv').click();
    }

    function processarDados(csv) {
        const lines = csv.split(/\r\n|\n/);
        const data = [];

        for (let i = 1; i < lines.length; i++) {
            const formatLine = lines[i]
            const lineData = formatLine.toString().split(';');
            if (isNaN(parseInt(lineData[0]))) {
                continue;
            }
            const tipoProcessoId = parseInt(lineData[0]);

            data.push(tipoProcessoId);
        }

        return data.join(',').replaceAll('""', '"');
    }

    function importarCsv(event, orgaoId) {
        const file = event.target.files[0];

        if (!file) {
            console.error("Nenhum arquivo selecionado.");
            return;
        }

        const reader = new FileReader();

        reader.onload = function (event) {
            const csvContent = event.target.result;
            const data = $('#dadosInput').val(JSON.stringify(processarDados(csvContent)));
            console.log(processarDados(csvContent))
            enviarFormulario(processarDados(csvContent))
        };
        reader.readAsText(file);
    }

    function enviarFormulario(data, orgaoId) {
        const dataInput = document.getElementById('dadosInput');
        const orgaoInput = document.getElementById('dadosInput');
        orgaoInput.value = orgaoId;
        dataInput.value = data;

        const form = jQuery('#formImportarDados');
        form.attr('action', '<?php print $objSessao->assinarLink('controlador.php?acao=pen_importar_tipos_processos&acao_origem=' . $_GET['acao_origem'] . '&acao_retorno=' . PEN_RECURSO_BASE . '_listar'); ?>');
        form.submit();
    }
</script>
<?php
$objPagina->fecharHead();
$objPagina->abrirBody(PEN_PAGINA_TITULO, 'onload="inicializar();"');
?>
<input style="display: none" type="file" id="importArquivoCsv" accept=".csv" onchange="importarCsv(event)">
<form id="formImportarDados" method="post" action="">
    <input type="hidden" name="mapId" id="mapId">
    <input type="hidden" name="dados" id="dadosInput">
</form>
<form id="frmAcompanharEstadoProcesso" method="post" action="">
    <?php $objPagina->montarBarraComandosSuperior($arrComandos); ?>
    <?php $objPagina->abrirAreaDados('5em'); ?>

    <label for="txtSiglaOrigem" id="lblSiglaOrigem" class="lblSigla infraLabelOpcional">Órgão Origem:</label>
    <input type="text" id="txtSiglaOrigem" name="txtSiglaOrigem" class="infraText" value="<?= PaginaSEI::tratarHTML(isset($_POST['txtSiglaOrigem']) ? $_POST['txtSiglaOrigem'] : ''); ?>">

    <label for="txtSiglaDestino" id="lblSiglaDestino" class="lblSigla infraLabelOpcional">Órgão Destino:</label>
    <input type="text" id="txtSiglaDestino" name="txtSiglaDestino" class="infraText" value="<?= PaginaSEI::tratarHTML(isset($_POST['txtSiglaDestino']) ? $_POST['txtSiglaDestino'] : ''); ?>">

    <label for="txtEstado" id="lblEstado" class="infraLabelOpcional">Estado:</label>
    <input type="text" id="txtEstado" name="txtEstado" class="infraText" value="<?= PaginaSEI::tratarHTML(isset($_POST['txtEstado']) ? $_POST['txtEstado'] : ''); ?>">
    <select id="txtEstado" name="txtEstado" onchange="this.form.submit();" class="infraSelect" tabindex="<?= PaginaSEI::getInstance()->getProxTabDados() ?>">
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