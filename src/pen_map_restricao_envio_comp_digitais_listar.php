<?php

require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Consulta os logs do estado do procedimento ao ser expedido
 */

session_start();

InfraDebug::getInstance()->setBolLigado(false);
InfraDebug::getInstance()->setBolDebugInfra(true);
InfraDebug::getInstance()->limpar();

$objPaginaSEI = PaginaSEI::getInstance();
$objSessaoSEI = SessaoSEI::getInstance();

$strProprioLink = 'controlador.php?acao='
    . $_GET['acao']
    . '&acao_origem='
    . $_GET['acao_origem']
    . '&acao_retorno='
    . $_GET['acao_retorno'];

try {
    $objSessaoSEI->validarLink();
    $objSessaoSEI->validarPermissao('pen_map_restricao_envio_comp_digitais_listar');

    $objPenRestricaoEnvioComponentesDigitaisRN = new PenRestricaoEnvioComponentesDigitaisRN();

    //--------------------------------------------------------------------------
    // A��es
    if (array_key_exists('acao', $_GET)) {
        $arrParam = array_merge($_GET, $_POST);
        switch ($_GET['acao']) {
            case 'pen_map_restricao_envio_comp_digitais_excluir':
                if (array_key_exists('hdnInfraItensSelecionados', $arrParam) && !empty($arrParam['hdnInfraItensSelecionados'])) {
                    if (is_array($arrParam['hdnInfraItensSelecionados'])) {
                        $ids = explode(',', $arrParam['hdnInfraItensSelecionados']);
                        foreach ($ids as $key => $id) {
                            $objDTO = new PenRestricaoEnvioComponentesDigitaisDTO();
                            $objDTO->setDblId($id);
                            $objPenRestricaoEnvioComponentesDigitaisRN->excluir($objDTO);
                        }
                    } else {
                        $objDTO = new PenRestricaoEnvioComponentesDigitaisDTO();
                        $objDTO->setDblId($arrParam['hdnInfraItensSelecionados']);
                        $objPenRestricaoEnvioComponentesDigitaisRN->excluir($objDTO);
                    }
                    $objPaginaSEI->adicionarMensagem('Mapeamento exclu�do com sucesso.', InfraPagina::$TIPO_MSG_AVISO);
                    header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . $_GET['acao_retorno'] . '&acao_origem=' . $_GET['acao_origem']));
                    exit(0);
                } else {
                    throw new InfraException('Nenhum registro foi selecionado para executar esta a��o');
                }
                break;

            case 'pen_map_restricao_envio_comp_digitais_listar':
                // A��o padr�o desta tela
                break;

            default:
                throw new InfraException('A��o n�o permitida nesta tela');
        }
    }
    //--------------------------------------------------------------------------

    $strTitulo = 'Lista dos Mapeamentos de Restri��o de Envio de Componentes Digitais';

    $strBotaoEspeciePadrao = "";
    if (SessaoSEI::getInstance()->verificarPermissao('pen_map_restricao_envio_comp_digitais_consultar')) {
        $strBotaoEspeciePadrao = '<button type="button" accesskey="C" onclick="location.href=\'' . $objSessaoSEI->assinarLink('controlador.php?acao=pen_map_restricao_envio_comp_digitais_consultar&acao_origem=' . $_GET['acao'] . '&acao_retorno=' . $_GET['acao']) . '\'" id="btnConsultarPadrao" value="Consultar Esp�cie Documental padr�o do PEN" class="infraButton"><span class="infraTeclaAtalho">C</span>onsultar Esp�cie Padr�o</button>';
    }

    if (SessaoSEI::getInstance()->verificarPermissao('pen_map_restricao_envio_comp_digitais_atribuir')) {
        $bolPadraoNaoAtribuido = empty((new PenParametroRN())->getParametro("PEN_ESPECIE_DOCUMENTAL_PADRAO_ENVIO"));
        $strClassePendencia = ($bolPadraoNaoAtribuido) ? "pendencia" : "";
        $strAltPendencia = ($bolPadraoNaoAtribuido) ? "Pendente atribui��o de esp�cie documental padr�o para envio de processos" : "";
        $strBotaoEspeciePadrao = '<button type="button" accesskey="A" onclick="location.href=\'' . $objSessaoSEI->assinarLink('controlador.php?acao=pen_map_restricao_envio_comp_digitais_padrao_atribuir&acao_origem=' . $_GET['acao'] . '&acao_retorno=' . $_GET['acao']) . '\'" id="btnAtribuirPadrao" title="' . $strAltPendencia . '" class="infraButton"><span class="' . $strClassePendencia . '"></span><span class="infraTeclaAtalho">A</span>tribuir Esp�cie Padr�o</button>';
    }

    $arrComandos = array();
    $btnPesquisar = '<button type="button" accesskey="P" onclick="onClickBtnPesquisar();" id="btnPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';
    $btnNovo = '<button type="button" value="Novo" id="btnNovo" onclick="onClickBtnNovo()" class="infraButton"><span class="infraTeclaAtalho">N</span>ovo</button>';
    $btnExcluir = '<button type="button" id="btnExcluir" value="Excluir" onclick="onClickBtnExcluir()" class="infraButton"><span class="infraTeclaAtalho">E</span>xcluir</button>';
    $btnImprimir = '<button type="button" accesskey="I" id="btnImprimir" value="Imprimir" onclick="infraImprimirTabela();" class="infraButton"><span class="infraTeclaAtalho">I</span>mprimir</button>';

    $arrComandos = array($btnPesquisar, $strBotaoEspeciePadrao, $btnNovo, $btnExcluir, $btnImprimir);
    $arrComandosFinal = array($btnNovo, $btnExcluir, $btnImprimir);

    $objPenRestricaoEnvioComponentesDigitaisDTO = new PenRestricaoEnvioComponentesDigitaisDTO();
    $objPenRestricaoEnvioComponentesDigitaisDTO->retTodos(true);
    $objPenRestricaoEnvioComponentesDigitaisDTO->setOrdDblId(InfraDTO::$TIPO_ORDENACAO_ASC);

    if (array_key_exists('nome_estrutura', $_POST) && !empty($_POST['nome_estrutura'])) {
        $objPenRestricaoEnvioComponentesDigitaisDTO->setStrStrEstrutura('%' . $_POST['nome_estrutura'] . '%', InfraDTO::$OPER_LIKE);
    }

    if (array_key_exists('nome_unidade', $_POST) && !empty($_POST['nome_unidade'])) {
        $objPenRestricaoEnvioComponentesDigitaisDTO->setStrStrUnidadeRh('%' . $_POST['nome_unidade'] . '%', InfraDTO::$OPER_LIKE);
    }

    $objPaginaSEI->prepararOrdenacao(
        $objPenRestricaoEnvioComponentesDigitaisDTO,
        'Id',
        InfraDTO::$TIPO_ORDENACAO_ASC
    );
    $objPaginaSEI->prepararPaginacao($objPenRestricaoEnvioComponentesDigitaisDTO);

    $arrObjPenRestricaoEnvioComponentesDigitaisDTO = $objPenRestricaoEnvioComponentesDigitaisRN->listar(
        $objPenRestricaoEnvioComponentesDigitaisDTO
    );

    $objPaginaSEI->processarPaginacao($objPenRestricaoEnvioComponentesDigitaisDTO);
    $numRegistros = count($arrObjPenRestricaoEnvioComponentesDigitaisDTO);

    if (!empty($arrObjPenRestricaoEnvioComponentesDigitaisDTO)) {

        $strResultado = '';
        $strResultado .= '<table width="99%" class="infraTable">' . "\n";
        $strResultado .= '<caption class="infraCaption">'
            . $objPaginaSEI->gerarCaptionTabela('estados do processo', $numRegistros)
            . '</caption>';
        $strResultado .= '<tr>';
        $strResultado .= '<th class="infraTh" width="1%">' . $objPaginaSEI->getThCheck() . '</th>' . "\n";
        $strResultado .= '<th class="infraTh" width="18%">ID do Reposit�rio</th>' . "\n";
        $strResultado .= '<th class="infraTh" width="18%">Nome do Reposit�rio</th>' . "\n";
        $strResultado .= '<th class="infraTh" width="18%">ID da Unidade</th>' . "\n";
        $strResultado .= '<th class="infraTh" width="18%">Nome da Unidade</th>' . "\n";
        $strResultado .= '<th class="infraTh" width="14%">A��es</th>' . "\n";
        $strResultado .= '</tr>' . "\n";
        $strCssTr = '';

        $index = 0;
        foreach ($arrObjPenRestricaoEnvioComponentesDigitaisDTO as $objPenRestricaoEnvioComponentesDigitaisDTO) {

            $strCssTr = ($strCssTr == 'infraTrClara') ? 'infraTrEscura' : 'infraTrClara';

            $strResultado .= '<tr class="' . $strCssTr . '">';
            $strResultado .= '<td>'
                . $objPaginaSEI->getTrCheck(
                    $index,
                    $objPenRestricaoEnvioComponentesDigitaisDTO->getDblId(),
                    ''
                ) . '</td>';

            $strResultado .= '<td style="text-align: center;">'
                . $objPenRestricaoEnvioComponentesDigitaisDTO->getNumIdEstrutura()
                . '</td>';
            $strResultado .= '<td style="text-align: center;">'
                . $objPenRestricaoEnvioComponentesDigitaisDTO->getStrStrEstrutura()
                . '</td>';

            $strResultado .= '<td style="text-align: center;">'
                . $objPenRestricaoEnvioComponentesDigitaisDTO->getNumIdUnidadeRh()
                . '</td>';
            $strResultado .= '<td style="text-align: center;">'
                . $objPenRestricaoEnvioComponentesDigitaisDTO->getStrStrUnidadeRh()
                . '</td>';
            $strResultado .= '<td align="center">';

            if ($objSessaoSEI->verificarPermissao('pen_map_restricao_envio_comp_digitais_visualizar')) {
                $strResultado .= '<a href="' . $objSessaoSEI->assinarLink(
                    'controlador.php?acao=pen_map_restricao_envio_comp_digitais_visualizar&acao_origem='
                        . $_GET['acao_origem'] . '&acao_retorno=' . $_GET['acao']
                        . '&Id=' . $objPenRestricaoEnvioComponentesDigitaisDTO->getDblId()
                    ) . '"><img src=' . ProcessoEletronicoINT::getCaminhoIcone(
                        "imagens/consultar.gif")
                    . '  title="Consultar Mapeamento" alt="Consultar Mapeamento" class="infraImg"></a>';
            }
            if ($objSessaoSEI->verificarPermissao('pen_map_restricao_envio_comp_digitais_atualizar')) {
                $strResultado .= '<a href="' . $objSessaoSEI->assinarLink(
                    'controlador.php?acao=pen_map_restricao_envio_comp_digitais_cadastrar&acao_origem='
                        . $_GET['acao_origem'] . '&acao_retorno=' . $_GET['acao']
                        . '&Id=' . $objPenRestricaoEnvioComponentesDigitaisDTO->getDblId())
                        . '"><img src=' . ProcessoEletronicoINT::getCaminhoIcone("imagens/alterar.gif")
                    . ' title="Alterar Mapeamento" alt="Alterar Mapeamento" class="infraImg"></a>';
            }
            if ($objSessaoSEI->verificarPermissao('pen_map_restricao_envio_comp_digitais_excluir')) {
                $strResultado .= '<a href="#" onclick="onCLickLinkDelete(\'' . $objSessaoSEI->assinarLink(
                    'controlador.php?acao=pen_map_restricao_envio_comp_digitais_excluir&acao_origem='
                    . $_GET['acao_origem'] . '&acao_retorno='
                    . $_GET['acao'] . '&hdnInfraItensSelecionados='
                    . $objPenRestricaoEnvioComponentesDigitaisDTO->getDblId())
                    . '\', this)"><img src=' . ProcessoEletronicoINT::getCaminhoIcone("imagens/excluir.gif")
                    . ' title="Excluir Mapeamento" alt="Excluir Mapeamento" class="infraImg"></a>';
            }

            $strResultado .= '</td>';
            $strResultado .= '</tr>' . "\n";

            $index++;
        }
        $strResultado .= '</table>';
    }
} catch (InfraException $e) {
    $objPaginaSEI->processarExcecao($e);
}

$objPaginaSEI->montarDocType();
$objPaginaSEI->abrirHtml();
$objPaginaSEI->abrirHead();
$objPaginaSEI->montarMeta();
$objPaginaSEI->montarTitle(':: ' . $objPaginaSEI->getStrNomeSistema() . ' - ' . $strTitulo . ' ::');
$objPaginaSEI->montarStyle();
?>
<style type="text/css">
    .input-label-first {
        position: absolute;
        left: 0%;
        top: 0%;
        width: 25%;
        color: #666 !important
    }

    .input-field-first {
        position: absolute;
        left: 0%;
        top: 50%;
        width: 25%
    }

    .input-label-second {
        position: absolute;
        left: 30%;
        top: 0%;
        width: 25%;
        color: #666 !important
    }

    .input-field-second {
        position: absolute;
        left: 30%;
        top: 50%;
        width: 25%;
    }

    .input-label-third {
        position: absolute;
        left: 0%;
        top: 40%;
        width: 25%;
        color: #666 !important
    }

    .input-field-third {
        position: absolute;
        left: 0%;
        top: 55%;
        width: 25%;
    }

    #btnAtribuirPadrao {
        position: relative;
    }

    #btnAtribuirPadrao .pendencia {
        position: absolute;
        top: -5px;
        right: -4px;
        padding: 5px 5px;
        border-radius: 50%;
        background: red;
        color: white;
    }
</style>
<?php $objPaginaSEI->montarJavaScript(); ?>
<script type="text/javascript">
    var objAutoCompletarInteressadoRI1225 = null;

    function onClickBtnPesquisar() {
        document.getElementById('frmAcompanharEstadoProcesso').action =
            '<?php print $objSessaoSEI->assinarLink($strProprioLink); ?>';
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

        if (confirm('Confirma a exclus�o do mapeamento "' + strEspecieDocumental + ' x ' + strTipoDocumento + '"?')) {
            window.location = url;
        }
    }

    function onClickBtnNovo() {
        window.location = '<?=
            $objSessaoSEI->assinarLink(
                'controlador.php?acao=pen_map_restricao_envio_comp_digitais_cadastrar&acao_origem='
                . $_GET['acao_origem']
                . '&acao_retorno='
                . $_GET['acao_origem']);
        ?>';
    }

    function onClickBtnExcluir() {

        try {
            var len = jQuery('input[name*=chkInfraItem]:checked').length;

            if (len > 0) {
                if (confirm('Confirma a exclus�o de ' + len + ' mapeamento(s) ?')) {
                    var form = jQuery('#frmAcompanharEstadoProcesso');
                    form.attr('action', '<?php print $objSessaoSEI->assinarLink('controlador.php?acao=pen_map_restricao_envio_comp_digitais_excluir&acao_origem=' . $_GET['acao_origem'] . '&acao_retorno=pen_map_restricao_envio_comp_digitais_listar'); ?>');
                    form.submit();
                }
            } else {
                alert('Selecione pelo menos um mapeamento para Excluir');
            }
        } catch (e) {
            alert('Erro : ' + e.message);
        }
    }
</script>
<?php
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody($strTitulo, 'onload="infraEfeitoTabelas();"');
?>
<form id="frmAcompanharEstadoProcesso" method="post" action="">

    <?php $objPaginaSEI->montarBarraComandosSuperior($arrComandos); ?>
    <?php $objPaginaSEI->abrirAreaDados('5em'); ?>

    <label for="nome_estrutura" class="infraLabelOpcional input-label-first">Nome Estrutura Organizacional:</label>
    <input type="text" name="nome_estrutura" id="txtNomeEstrutura" class="infraText input-field-first" onkeyup="return tratarEnter(event)" value="<?php print $_POST['nome_estrutura']; ?>" />

    <label for="nome_unidade" class="infraLabelOpcional input-label-second">Nome Unidade:</label>
    <input type="text" name="nome_unidade" class="infraText input-field-second" onkeyup="return tratarEnter(event)" value="<?php print $_POST['nome_unidade']; ?>" />

    <?php $objPaginaSEI->fecharAreaDados(); ?>

    <?php if ($numRegistros > 0) { ?>
        <?php $objPaginaSEI->montarAreaTabela($strResultado, $numRegistros); ?>
    <?php } ?>

    <input type="hidden" id="hdnErrosValidacao" name="hdnErrosValidacao" value="<?= $bolErrosValidacao ?>" />
    <?php $objPaginaSEI->montarBarraComandosSuperior($arrComandosFinal); ?>
</form>
<?php $objPaginaSEI->fecharBody(); ?>
<?php $objPaginaSEI->fecharHtml(); ?>
