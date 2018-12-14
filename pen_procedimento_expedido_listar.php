<?php
/**
 * @author Join Tecnologia
 */
require_once dirname(__FILE__) . '/../../SEI.php';

try {

    session_start();

    $objPaginaSEI = PaginaSEI::getInstance();
    $objSessaoSEI = SessaoSEI::getInstance();

    $objSessaoSEI->validarLink();
    $objSessaoSEI->validarPermissao($_GET['acao']);
    $arrComandos = array();

    $strTitulo = 'Processos Tramitados Externamente';

    $objFiltroDTO = new ProtocoloDTO();
    $objFiltroDTO->setStrStaEstado(ProtocoloRN::$TE_PROCEDIMENTO_BLOQUEADO);
    $objFiltroDTO->retDblIdProtocolo();
    $objFiltroDTO->retStrProtocoloFormatado();

    // Verificar no DTO sobre funções de agragação para clausula DISTINCT
    if(get_parent_class(BancoSEI::getInstance()) != 'InfraMySqli') {
        $objFiltroDTO->retDthConclusaoAtividade();
    }
    $objPaginaSEI->prepararPaginacao($objFiltroDTO, 50);

    BancoSEI::getInstance()->abrirConexao();

    $objProcessoExpedidoRN = new ProcessoExpedidoRN();
    $arrObjProcessoExpedidoDTO = $objProcessoExpedidoRN->listarProcessoExpedido($objFiltroDTO);

    $numRegistros = 0;

    if(!empty($arrObjProcessoExpedidoDTO)) {
        $arrObjProcessoExpedidoDTO = InfraArray::distinctArrInfraDTO($arrObjProcessoExpedidoDTO, 'IdProtocolo');
        $numRegistros = count($arrObjProcessoExpedidoDTO);
    }

    $objPaginaSEI->processarPaginacao($objFiltroDTO);

   if (!empty($arrObjProcessoExpedidoDTO)) {

        $arrComandos[] = '<button type="button" accesskey="I" id="btnImprimir" value="Imprimir" onclick="infraImprimirTabela();" class="infraButton"><span class="infraTeclaAtalho">I</span>mprimir</button>';

        $strSumarioTabela = 'Tabela de Processos.';
        $strCaptionTabela = 'Processos';

        $strResultado .= '<table width="99%" class="infraTable" summary="' . $strSumarioTabela . '">' . "\n";
        $strResultado .= '<caption class="infraCaption">' . $objPaginaSEI->gerarCaptionTabela($strCaptionTabela, $numRegistros) . '</caption>';
        $strResultado .= '<tr>';
        $strResultado .= '<th class="infraTh" width="1%">' . $objPaginaSEI->getThCheck() . '</th>' . "\n";
        $strResultado .= '<th class="infraTh">Processo</th>' . "\n";
        $strResultado .= '<th class="infraTh">Usuário</th>' . "\n";
        $strResultado .= '<th class="infraTh">Data do Envio</th>' . "\n";
        $strResultado .= '<th class="infraTh">Unidade Destino</th>' . "\n";
        $strResultado .= '</tr>' . "\n";
        $strCssTr = '';

        $numIndice = 1;

        foreach($arrObjProcessoExpedidoDTO as $objProcessoExpedidoDTO) {

            $strCssTr = ($strCssTr == '<tr class="infraTrClara">') ? '<tr class="infraTrEscura">' : '<tr class="infraTrClara">';
            $strResultado .= $strCssTr;

            $strResultado .= '<td valign="top">'.$objPaginaSEI->getTrCheck($numIndice,$objProcessoExpedidoDTO->getDblIdProtocolo(),$objProcessoExpedidoDTO->getStrProtocoloFormatado()).'</td>'."\n";
            $strResultado .= '<td width="17%" align="center"><a onclick="abrirProcesso(\'' .$objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=procedimento_trabalhar&acao_origem=' . $_GET['acao'] . '&acao_retorno=' . $_GET['acao'] . '&id_procedimento=' . $objProcessoExpedidoDTO->getDblIdProtocolo())).'\');" tabindex="' . $objPaginaSEI->getProxTabTabela() . '" title="" class="protocoloNormal" style="font-size:1em !important;">'.$objProcessoExpedidoDTO->getStrProtocoloFormatado().'</a></td>' . "\n";
            $strResultado .= '<td align="center"><a alt="Teste" title="Teste" class="ancoraSigla">' . $objProcessoExpedidoDTO->getStrNomeUsuario() . '</a></td>';
            $strResultado .= '<td width="17%" align="center">' . $objProcessoExpedidoDTO->getDthExpedido() . '</td>';
            $strResultado .= '<td align="left">' . $objProcessoExpedidoDTO->getStrDestino();

            if ($bolAcaoRemoverSobrestamento) {
                $strResultado .= '<a href="' . $objPaginaSEI->montarAncora($objProcessoExpedidoDTO->getDblIdProtocolo()) . '" onclick="acaoRemoverSobrestamento(\'' . $objProcessoExpedidoDTO->getDblIdProtocolo() . '\',\'' . $objProcessoExpedidoDTO->getStrProtocoloFormatado() . '\');" tabindex="' . $objPaginaSEI->getProxTabTabela() . '"><img src="imagens/sei_remover_sobrestamento_processo_pequeno.gif" title="Remover Sobrestamento" alt="Remover Sobrestamento" class="infraImg" /></a>&nbsp;';
            }

            $strResultado .= '</td></tr>' . "\n";
            $numIndice++;
        }
        $strResultado .= '</table>';
    }
}
catch (Exception $e) {
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

table.tabelaProcessos {
    background-color:white;
    border:0px solid white;
    border-spacing:.1em;
}

table.tabelaProcessos tr{
    margin:0;
    border:0;
    padding:0;
}

table.tabelaProcessos img{
    width:1.1em;
    height:1.1em;
}

table.tabelaProcessos a{
    text-decoration:none;
}

table.tabelaProcessos a:hover{
    text-decoration:underline;
}


table.tabelaProcessos caption{
    font-size: 1em;
    text-align: right;
    color: #666;
}

th.tituloProcessos{
    font-size:1em;
    font-weight: bold;
    text-align: center;
    color: #000;
    background-color: #dfdfdf;
    border-spacing: 0;
}

a.processoNaoVisualizado{
    color:red;
}

#divTabelaRecebido {
    margin:2em;
    float:left;
    display:inline;
    width:40%;
}

#divTabelaRecebido table{
    width:100%;
}

#divTabelaGerado {
    margin:2em;
    float:right;
    display:inline;
    width:40%;
}

#divTabelaGerado table{
    width:100%;
}
</style>
<?php $objPaginaSEI->montarJavaScript(); ?>
<script type="text/javascript">

function inicializar(){

    infraEfeitoTabelas();
}

function abrirProcesso(link){
    document.getElementById('divInfraBarraComandosSuperior').style.visibility = 'hidden';
    document.getElementById('divInfraAreaTabela').style.visibility = 'hidden';
    infraOcultarMenuSistemaEsquema();
    document.getElementById('frmProcedimentoExpedido').action = link;
    document.getElementById('frmProcedimentoExpedido').submit();
}
</script>
<?php
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody($strTitulo, 'onload="inicializar();"');
?>
<form id="frmProcedimentoExpedido" method="post" action="<?= $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=' . $_GET['acao'] . '&acao_origem=' . $_GET['acao'])) ?>">
<?php
    $objPaginaSEI->montarBarraComandosSuperior($arrComandos);
    $objPaginaSEI->montarAreaTabela($strResultado, $numRegistros, true);
    $objPaginaSEI->montarBarraComandosInferior($arrComandos);
?>
</form>
<?php
$objPaginaSEI->fecharBody();
$objPaginaSEI->fecharHtml();
