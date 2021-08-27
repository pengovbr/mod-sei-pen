<?php
try {
    require_once DIR_SEI_WEB . '/SEI.php';

    session_start();

    $objSessaoSEI = SessaoSEI::getInstance();
    $objPaginaSEI = PaginaSEI::getInstance();

    $objSessaoSEI->validarLink();
    $objSessaoSEI->validarPermissao($_GET['acao']);

    $staCancelado = ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO;
    $staConcluido = ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_ENVIADOS_REMETENTE;
    $staEmProcessamento = ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_NAO_INICIADO;

    $objPaginaSEI->salvarCamposPost(array('txtProcedimentoFormatado', 'txtNomeUsuario', 'txtUnidadeDestino', 'selAndamento'));

    switch ($_GET['acao']) {

        case 'pen_expedir_lote_listar':
            $strTitulo = 'Processos Tramitados em Lote';
            break;

        default:
            throw new InfraException("Ação '" . $_GET['acao'] . "' não reconhecida.");
    }

    $arrComandos = array();
    $arrComandos[] = '<button type="submit" accesskey="P" id="sbmPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';

    $objPenLoteProcedimentoDTO = new PenLoteProcedimentoDTO(true);

    $objPenLoteProcedimentoDTO->retNumIdLote();
    $objPenLoteProcedimentoDTO->retStrProcedimentoFormatado();
    $objPenLoteProcedimentoDTO->retNumIdAndamento();
    $objPenLoteProcedimentoDTO->retStrUnidadeDestino();
    $objPenLoteProcedimentoDTO->retStrNomeUsuario();
    $objPenLoteProcedimentoDTO->retDthRegistro();

    $strNomeUsuario = $objPaginaSEI->recuperarCampo('txtNomeUsuario');
    if (trim($strNomeUsuario) != '') {
        $objPenLoteProcedimentoDTO->setStrNomeUsuario('%' . trim($strNomeUsuario . '%'), InfraDTO::$OPER_LIKE);
    }

    $strProcedimentoFormatado = $objPaginaSEI->recuperarCampo('txtProcedimentoFormatado');
    if (trim($strProcedimentoFormatado) != '') {
        $objPenLoteProcedimentoDTO->setStrProcedimentoFormatado(trim($strProcedimentoFormatado));
    }

    $strUnidadeDestino = $objPaginaSEI->recuperarCampo('txtUnidadeDestino');
    if (trim($strUnidadeDestino) != '') {
        $objPenLoteProcedimentoDTO->setStrUnidadeDestino(trim($strUnidadeDestino));
    }

    $numIdAndamento = $objPaginaSEI->recuperarCampo('selAndamento');
    if ($numIdAndamento !== '') {
        $objPenLoteProcedimentoDTO->setNumIdAndamento($numIdAndamento);
    }

    $objPaginaSEI->prepararOrdenacao($objPenLoteProcedimentoDTO, 'IdLote', InfraDTO::$TIPO_ORDENACAO_ASC);
    $objPaginaSEI->prepararPaginacao($objPenLoteProcedimentoDTO);

    $objPenLoteProcedimentoRN = new PenLoteProcedimentoRN();
    $arrObjPenLoteProcedimentoRN = $objPenLoteProcedimentoRN->listarLoteProcedimento($objPenLoteProcedimentoDTO);

    $objPaginaSEI->processarPaginacao($objPenLoteProcedimentoDTO);
    $numRegistros = count($arrObjPenLoteProcedimentoRN);

    $objExpedirProcedimentoRN = new ExpedirProcedimentoRN();

    if ($numRegistros > 0) {
        $arrComandos[] = '<button type="button" accesskey="I" id="btnImprimir" value="Imprimir" onclick="infraImprimirTabela();" class="infraButton"><span class="infraTeclaAtalho">I</span>mprimir</button>';

        $strResultado = '';
        $strSumarioTabela = 'Tabela de Processo em Lote.';
        $strCaptionTabela = 'Processo em Lote';

        $strResultado .= '<table width="99%" class="infraTable" summary="' . $strSumarioTabela . '">' . "\n";
        $strResultado .= '<caption class="infraCaption">' . $objPaginaSEI->gerarCaptionTabela($strCaptionTabela, $numRegistros) . '</caption>';
        $strResultado .= '<tr>';
        $strResultado .= '<th class="infraTh" width="1%">' . $objPaginaSEI->getThCheck() . '</th>' . "\n";
        $strResultado .= '<th class="infraTh" width="10%">' . $objPaginaSEI->getThOrdenacao($objPenLoteProcedimentoDTO, 'ID Lote', 'IdLote', $arrObjPenLoteProcedimentoRN) . '</th>' . "\n";
        $strResultado .= '<th class="infraTh">' . $objPaginaSEI->getThOrdenacao($objPenLoteProcedimentoDTO, 'Processo', 'ProcedimentoFormatado', $arrObjPenLoteProcedimentoRN) . '</th>' . "\n";
        $strResultado .= '<th class="infraTh">' . $objPaginaSEI->getThOrdenacao($objPenLoteProcedimentoDTO, 'Usuário', 'IdUsuario', $arrObjPenLoteProcedimentoRN) . '</th>' . "\n";
        $strResultado .= '<th class="infraTh">' . $objPaginaSEI->getThOrdenacao($objPenLoteProcedimentoDTO, 'Data do Envio', 'Registro', $arrObjPenLoteProcedimentoRN) . '</th>' . "\n";
        $strResultado .= '<th class="infraTh">' . $objPaginaSEI->getThOrdenacao($objPenLoteProcedimentoDTO, 'Unidade Destino', 'UnidadeDestino', $arrObjPenLoteProcedimentoRN) . '</th>' . "\n";
        $strResultado .= '<th class="infraTh">' . $objPaginaSEI->getThOrdenacao($objPenLoteProcedimentoDTO, 'Situação', 'IdAndamento', $arrObjPenLoteProcedimentoRN) . '</th>' . "\n";
        $strResultado .= '</tr>' . "\n";
        $strCssTr = '';
        for ($i = 0; $i < $numRegistros; $i++) {

            $strCssTr = ($strCssTr == '<tr class="infraTrClara">') ? '<tr class="infraTrEscura">' : '<tr class="infraTrClara">';
            $strResultado .= $strCssTr;

            $strResultado .= '<td valign="top">' . $objPaginaSEI->getTrCheck($i, $arrObjPenLoteProcedimentoRN[$i]->getNumIdLote(), $arrObjPenLoteProcedimentoRN[$i]->getNumIdLote()) . '</td>';
            $strResultado .= '<td align="center">' . $arrObjPenLoteProcedimentoRN[$i]->getNumIdLote() . '</td>';
            $strResultado .= '<td align="center">' . PaginaSEI::tratarHTML($arrObjPenLoteProcedimentoRN[$i]->getStrProcedimentoFormatado()) . '</td>';
            $strResultado .= '<td align="center">' . PaginaSEI::tratarHTML($arrObjPenLoteProcedimentoRN[$i]->getStrNomeUsuario()) . '</td>';
            $strResultado .= '<td align="center">' . PaginaSEI::tratarHTML($arrObjPenLoteProcedimentoRN[$i]->getDthRegistro()) . '</td>';
            $strResultado .= '<td align="center">' . PaginaSEI::tratarHTML($arrObjPenLoteProcedimentoRN[$i]->getStrUnidadeDestino()) . '</td>';
            $strResultado .= '<td align="center">';

            switch ($arrObjPenLoteProcedimentoRN[$i]->getNumIdAndamento()) {
                case $staConcluido:
                    $strResultado .= '<img src="'.PENIntegracao::getDiretorio().'/imagens/estado_sucesso.png" title="Concluído" alt="Concluído" />';
                    break;
                case $staCancelado:
                    $strResultado .= '<img src="'.PENIntegracao::getDiretorio().'/imagens/estado_falhou.png" title="Cancelado" alt="Cancelado" />';
                    break;
                default:
                    $strResultado .= '<img src="'.PENIntegracao::getDiretorio().'/imagens/pen_em_processamento.png" title="Em tramitação" alt="Em tramitação" />';
                    break;
            }
            
            $strResultado .= '</td></tr>' . "\n";
            
        }
        $strResultado .= '</table>';
    }

    $arrComandos[] = '<button type="button" accesskey="F" id="btnFechar" value="Fechar" onclick="location.href=\'' . $objSessaoSEI->assinarLink('controlador.php?acao=' . $objPaginaSEI->getAcaoRetorno() . '&acao_origem=' . $_GET['acao'] . $objPaginaSEI->montarAncora($numIdGrupoSerie)) . '\'" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';

} catch (Exception $e) {
    $objPaginaSEI->processarExcecao($e);
}

$objPaginaSEI->montarDocType();
$objPaginaSEI->abrirHtml();
$objPaginaSEI->abrirHead();
$objPaginaSEI->montarMeta();
$objPaginaSEI->montarTitle($objPaginaSEI->getStrNomeSistema() . ' - ' . $strTitulo);
$objPaginaSEI->montarStyle();
$objPaginaSEI->abrirStyle();
?>

#lblNomeUsuario {position:absolute;left:0%;top:0%;width:20%;}
#txtNomeUsuario {position:absolute;left:0%;top:40%;width:20%;}

#lblProcedimentoFormatado {position:absolute;left:23%;top:0%;width:20%;}
#txtProcedimentoFormatado {position:absolute;left:23%;top:40%;width:20%;}

#lblUnidadeDestino {position:absolute;left:46%;top:0%;width:20%;}
#txtUnidadeDestino {position:absolute;left:46%;top:40%;width:25%;}

#lblAndamento {position:absolute;left:74%;top:0%;width:20%;}
#selAndamento {position:absolute;left:74%;top:40%;width:20%;}

<?
$objPaginaSEI->fecharStyle();
$objPaginaSEI->montarJavaScript();
$objPaginaSEI->abrirJavaScript();
?>

function inicializar(){
    if ('<?= $_GET['acao'] ?>'=='serie_selecionar'){
        infraReceberSelecao();
        document.getElementById('btnFecharSelecao').focus();
    }else{
        document.getElementById('btnFechar').focus();
    }

    infraEfeitoTabelas();
}

<?
$objPaginaSEI->fecharJavaScript();
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody($strTitulo, 'onload="inicializar();"');
?>
<form id="frmLoteListar" method="post" action="<?= $objSessaoSEI->assinarLink('controlador.php?acao=' . $_GET['acao'] . '&acao_origem=' . $_GET['acao']) ?>">
    <?
    $objPaginaSEI->montarBarraComandosSuperior($arrComandos);
    $objPaginaSEI->abrirAreaDados('4.5em');
    ?>

    <label id="lblNomeUsuario" for="txtNomeUsuario" accesskey="" class="infraLabelOpcional">Nome do Usuário:</label>
    <input type="text" id="txtNomeUsuario" name="txtNomeUsuario" value="<?= $strNomeUsuario ?>" class="infraText" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />

    <label id="lblProcedimentoFormatado" for="txtProcedimentoFormatado" accesskey="" class="infraLabelOpcional">Número do Processo:</label>
    <input type="text" id="txtProcedimentoFormatado" name="txtProcedimentoFormatado" value="<?= $strProcedimentoFormatado ?>" class="infraText" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />

    <label id="lblUnidadeDestino" for="txtUnidadeDestino" accesskey="" class="infraLabelOpcional">Unidade de Destino:</label>
    <input type="text" id="txtUnidadeDestino" name="txtUnidadeDestino" value="<?= $strUnidadeDestino ?>" class="infraText" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />

    <label id="lblAndamento" for="selAndamento" accesskey="" class="infraLabelOpcional">Situação:</label>
    <select id="selAndamento" name="selAndamento" onchange="this.form.submit();" class="infraSelect" tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" >
        <option value="" <?=strval($numIdAndamento)=="" ? ' selected="selected" ' : ''?>>Todos</option>
        <option value="<?=$staCancelado?>"<?=strval($numIdAndamento)==strval($staCancelado) ? ' selected="selected" ' : ''?>>Cancelado</option>
        <option value="<?=$staConcluido?>"<?=strval($numIdAndamento)==strval($staConcluido) ? ' selected="selected" ' : ''?>>Concluído</option>
        <option value="<?=$staEmProcessamento?>" <?=strval($numIdAndamento)==strval($staEmProcessamento) ? ' selected="selected" ' : ''?>>Em Processamento</option>
    </select>

    <?
    $objPaginaSEI->fecharAreaDados();
    $objPaginaSEI->montarAreaTabela($strResultado, $numRegistros);
    $objPaginaSEI->montarAreaDebug();
    $objPaginaSEI->montarBarraComandosInferior($arrComandos);
    ?>
</form>
<?
$objPaginaSEI->fecharBody();
$objPaginaSEI->fecharHtml();
?>