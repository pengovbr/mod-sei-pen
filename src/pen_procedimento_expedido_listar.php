<?php
/**
 *
 */
require_once DIR_SEI_WEB.'/SEI.php';

try {

    session_start();

    $objPaginaSEI = PaginaSEI::getInstance();
    $objSessaoSEI = SessaoSEI::getInstance();

    $objSessaoSEI->validarLink();
    $objSessaoSEI->validarPermissao($_GET['acao']);
    $arrComandos = [];

    $strTitulo = 'Processos em Tramitação Externa';

    $objFiltroDTO = new ProtocoloDTO();
    $objFiltroDTO->setStrStaEstado(ProtocoloRN::$TE_PROCEDIMENTO_BLOQUEADO);
    $objFiltroDTO->retDblIdProtocolo();
    $objFiltroDTO->retStrProtocoloFormatado();

    // Verificar no DTO sobre funções de agragação para clausula DISTINCT
  if(get_parent_class(BancoSEI::getInstance()) != 'InfraMySqli') {
      $objFiltroDTO->retDthConclusaoAtividade();
  }
    $objPaginaSEI->prepararPaginacao($objFiltroDTO, 50);

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

       $strSumarioTabela = 'Tabela de Processos';
       $strCaptionTabela = 'Processos';

       $strResultado .= '<table width="99%" id="tblBlocos" class="infraTable infraTableResponsiva" summary="'.$strSumarioTabela.'">' . "\n";
       $strResultado .= '<caption class="infraCaption">' . $objPaginaSEI->gerarCaptionTabela($strCaptionTabela, $numRegistros) . '</caption>';
      
       $strResultado .= "<thead>";
       $strResultado .= '<tr>';

       $strResultado .= '<th class="infraTh" width="1%">' . $objPaginaSEI->getThCheck() . '</th>' . "\n";
       $strResultado .= '<th class="infraTh">Processo</th>' . "\n";

       $strResultado .= '<th class="infraTh">';
       $strResultado .= '<div class="infraDivOrdenacao">';
       $strResultado .= '<div class="infraDivRotuloOrdenacao">Usuário</div>';
       $strResultado .= '<div class="infraDivSetaOrdenacao"><a href="javascript:void(0);" tabindex="1002"><img src="' . $objPaginaSEI->getIconeOrdenacaoColunaAcima() .'" title="Ordenar Usuário Ascendente" alt="Ordenar Usuário Ascendente" class="infraImgOrdenacao"></a></div>';
       $strResultado .= '<div class="infraDivSetaOrdenacao"><a href="javascript:void(0);" tabindex="1003"><img src="' . $objPaginaSEI->getIconeOrdenacaoColunaAbaixo() .'" title="Ordenar Usuário Descendente" alt="Ordenar Usuário Descendente" class="infraImgOrdenacao"></a></div>';
       $strResultado .= '</div>';
       $strResultado .= '</th>' . "\n";

       $strResultado .= '<th class="infraTh">';
       $strResultado .= '<div class="infraDivOrdenacao">';
       $strResultado .= '<div class="infraDivRotuloOrdenacao">Data do Envio</div>';
       $strResultado .= '<div class="infraDivSetaOrdenacao"><a href="javascript:void(0);" tabindex="1002"><img src="' . $objPaginaSEI->getIconeOrdenacaoColunaAcima() .'" title="Ordenar Data do Envio Ascendente" alt="Ordenar Data do Envio Ascendente" class="infraImgOrdenacao"></a></div>';
       $strResultado .= '<div class="infraDivSetaOrdenacao"><a href="javascript:void(0);" tabindex="1003"><img src="' . $objPaginaSEI->getIconeOrdenacaoColunaAbaixo() .'" title="Ordenar Data do Envio Descendente" alt="Ordenar Data do Envio Descendente" class="infraImgOrdenacao"></a></div>';
       $strResultado .= '</div>';
       $strResultado .= '</th>' . "\n";

       $strResultado .= '<th class="infraTh">';
       $strResultado .= '<div class="infraDivOrdenacao">';
       $strResultado .= '<div class="infraDivRotuloOrdenacao">Unidade Destino</div>';
       $strResultado .= '<div class="infraDivSetaOrdenacao"><a href="javascript:void(0);" tabindex="1002"><img src="' . $objPaginaSEI->getIconeOrdenacaoColunaAcima() .'" title="Ordenar Unidade Destino Ascendente" alt="Ordenar Unidade Destino Ascendente" class="infraImgOrdenacao"></a></div>';
       $strResultado .= '<div class="infraDivSetaOrdenacao"><a href="javascript:void(0);" tabindex="1003"><img src="' . $objPaginaSEI->getIconeOrdenacaoColunaAbaixo() .'" title="Ordenar Unidade Destino Descendente" alt="Ordenar Unidade Destino Descendente" class="infraImgOrdenacao"></a></div>';
       $strResultado .= '</div>';
       $strResultado .= '</th>' . "\n";

       $strResultado .= '</tr>' . "\n";
       $strResultado .= "</thead>";

       $strCssTr = '';

       $numIndice = 0;

    foreach($arrObjProcessoExpedidoDTO as $objProcessoExpedidoDTO) {

        $strCssTr = ($strCssTr == '<tr class="infraTrClara">') ? '<tr class="infraTrEscura">' : '<tr class="infraTrClara">';
        $strResultado .= $strCssTr;

        $strResultado .= '<td valign="top">'.$objPaginaSEI->getTrCheck($numIndice, $objProcessoExpedidoDTO->getDblIdProtocolo(), $objProcessoExpedidoDTO->getStrProtocoloFormatado()).'</td>'."\n";
        $strResultado .= '<td width="17%" align="center"><a onclick="abrirProcesso(\'' .$objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=procedimento_trabalhar&acao_origem=' . $_GET['acao'] . '&acao_retorno=' . $_GET['acao'] . '&id_procedimento=' . $objProcessoExpedidoDTO->getDblIdProtocolo())).'\');" tabindex="' . $objPaginaSEI->getProxTabTabela() . '" title="" class="protocoloNormal" style="font-size:1em !important;">'.$objProcessoExpedidoDTO->getStrProtocoloFormatado().'</a></td>' . "\n";
        $strResultado .= '<td align="center"><a alt="Teste" title="Teste" class="ancoraSigla">' . $objProcessoExpedidoDTO->getStrNomeUsuario() . '</a></td>';
        $strResultado .= '<td width="17%" align="center">' . $objProcessoExpedidoDTO->getDthExpedido() . '</td>';
        $strResultado .= '<td align="center">' . $objProcessoExpedidoDTO->getStrDestino();

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


  /* Personalize o estilo da paginação */
  .dataTables_paginate {
    margin: 10px;
    text-align: end;
  }

  .dataTables_paginate .paginate_button {
    padding: 5px 10px;
    margin-right: 5px;
    border: 1px solid #ccc;
    background-color: #f2f2f2;
    color: #333;
    cursor: pointer;
  }

  .dataTables_paginate .paginate_button.current {
    background-color: var(--infra-esquema-cor-clara);
    color: #fff;
  }


  #tblBlocos_filter {
    position: absolute;
    opacity: 0;
  }
  
  #frmProcedimentoExpedido #tblBlocos_wrapper label:first-of-type{font-size: 12px;}
  #frmProcedimentoExpedido #tblBlocos_wrapper select:first-of-type{font-size: 11px;}
  
</style>
<?php $objPaginaSEI->montarJavaScript(); ?>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.js"></script>
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

$(document).ready(function() {
    $('#tblBlocos').dataTable({
      "searching": false,
      "columnDefs": [{
        targets: [0, 4],
        orderable: true
      }],
      "language": {
        "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
        "lengthMenu": "Mostrar _MENU_ registros por página",
        "infoEmpty": "Mostrando 0 a 0 de 0 registros",
        "zeroRecords": "Nenhum registro encontrado",
        "paginate": {
          "previous": "Anterior",
          "next": "Próximo"
        },
      }
    });
});
</script>
<?php
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody($strTitulo, 'onload="inicializar();"');
?>
<form id="frmProcedimentoExpedido" method="post" action="<?php echo $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=' . htmlspecialchars($_GET['acao']). '&acao_origem=' . htmlspecialchars($_GET['acao']))) ?>">
<?php
    $objPaginaSEI->montarBarraComandosSuperior($arrComandos);
    $objPaginaSEI->montarAreaTabela($strResultado, $numRegistros, true);
    $objPaginaSEI->montarBarraComandosInferior($arrComandos);
?>
</form>
<?php
$objPaginaSEI->fecharBody();
$objPaginaSEI->fecharHtml();
