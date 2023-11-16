<?php

require_once DIR_SEI_WEB . '/SEI.php';

session_start();

$objPagina = PaginaSEI::getInstance();
$objBanco = BancoSEI::getInstance();
$objSessao = SessaoSEI::getInstance();
$objDebug = InfraDebug::getInstance();

try {
    $objDebug->setBolLigado(false);
    $objDebug->setBolDebugInfra(true);
    $objDebug->limpar();

    // $objSessao->validarLink();

    $cadastrou = null;
    if (array_key_exists('acao', $_GET)) {

        $arrParam = array_merge($_GET, $_POST);

        switch ($_GET['acao']) {

            case 'pen_map_orgaos_externos_tipo_processo_listar':
                break;
            case 'pen_importar_tipos_processos':

                try{
                    $penMapTipoProcedimentoRN = new PenMapTipoProcedimentoRN();
                    $arrProcedimentoDTO = [];
                    $tipoDeProcedimentos = array();
                    $procedimentos = explode(',', $_POST['dados']);
                    for ($i = 0; $i < count($procedimentos); $i += 2) {
                        $key = trim($procedimentos[$i]);
                        $value = trim($procedimentos[$i + 1], '"');
                        $tipoDeProcedimentos[$key] = $value;
                    }

                    
                    foreach ($tipoDeProcedimentos as $idProcedimento => $nomeProcedimento) {
                        $procedimentoDTO = new PenMapTipoProcedimentoDTO();
                        $procedimentoDTO->setNumIdMapOrgao($_POST['mapId']);
                        $procedimentoDTO->setNumIdTipoProcessoOrigem($idProcedimento);
                        $procedimentoDTO->setStrNomeTipoProcesso($nomeProcedimento);
                        $procedimentoDTO->setNumIdUnidade($_GET['infra_unidade_atual']);
                        $procedimentoDTO->setDthRegistro(date('d/m/Y H:i:s'));
                        if ($penMapTipoProcedimentoRN->contar($procedimentoDTO)) {
                            continue;
                        }
                        $penMapTipoProcedimentoRN->cadastrar($procedimentoDTO);
                    }
                    
                    $cadastrou = 1;
                    $messagemAlerta = "Importação de tipos de processos realizada com sucesso1.";
                } catch (Exception $e) {
                    throw new InfraException($e->getMessage());
                }
                break;
            default:
                throw new InfraException('Ação não permitida nesta tela');
        }
    }

    $arrComandos = array();
    if (empty($cadastrou)) {
        $arrComandos[] = '<button type="button" accesskey="F" id="btnImportar" value="Fechar" onclick="infraImportarCsv('. $_GET['idMapOrgao'] . ');" class="infraButton"><span class="infraTeclaAtalho">I</span>mportar</button>';
        $arrComandos[] = '<button type="button" accesskey="F" style="display: none" id="btnProsseguir" value="Fechar" onclick="enviarFormulario();" class="infraButton"><span class="infraTeclaAtalho">P</span>rosseguir</button>'; 
    }
    
    $arrComandos[] = '<button type="button" accesskey="F" id="btnFecharSelecao" value="Fechar" onclick="window.close();" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';
    $arrComandosFinal = $arrComandos;

    if (empty($cadastrou)) {
        $strResultado = '';

        $strResultado .= '<table width="99%" class="infraTable" id="tableImportar">' . "\n";
        $strResultado .= '<caption class="infraCaption" id="tableTotal"></caption>';

        $strResultado .= '<tr>';
        $strResultado .= '<th class="infraTh" width="30%">ID</th>' . "\n";
        $strResultado .= '<th class="infraTh">Nome</th>' . "\n";
        $strResultado .= '</tr>' . "\n";
        $strResultado .= '</table>';
    }

} catch (InfraException $e) {
    $objPagina->processarExcecao($e);
}

PaginaSEI::getInstance()->montarDocType();
PaginaSEI::getInstance()->abrirHtml();
PaginaSEI::getInstance()->abrirHead();
PaginaSEI::getInstance()->montarMeta();
PaginaSEI::getInstance()->montarTitle(PaginaSEI::getInstance()->getStrNomeSistema().' - Importar');
PaginaSEI::getInstance()->montarStyle();
?>
<style type="text/css">
#lblTabelaAssuntos {position:absolute;left:0%;top:0%;width:40%;}
#txtTabelaAssuntos {position:absolute;left:0%;top:20%;width:40%;}

#lblPalavrasPesquisaAssuntos {position:absolute;left:0%;top:50%;width:70%;}
#txtPalavrasPesquisaAssuntos {position:absolute;left:0%;top:70%;width:70%;}

#divSinAssuntosDesativados {position:absolute;left:72%;top:70%;}
</style>
<?php $objPagina->montarJavaScript(); ?>
<script type="text/javascript">
    function infraImportarCsv(orgaoId) {
        document.getElementById('mapId').value = orgaoId;
        $('#importArquivoCsv').click();
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
            console.log(processarDados(csvContent));

            const dataInput = document.getElementById('dadosInput');
            dataInput.value = processarDados(csvContent);
            document.getElementById('tableTipoProcessos').value = dataInput.value;
        };
        reader.readAsText(file);
    }

    function enviarFormulario() {
        const form = jQuery('#formImportarDados');
        form.attr('action', '<?= $objSessao->assinarLink(
            'controlador.php?acao=pen_importar_tipos_processos&acao_origem=pen_map_orgaos_externos_listar&acao_retorno=pen_map_orgaos_externos_tipo_processo_listar'
            ); ?>'
        );
        form.submit();
    }

    function processarDados(csv) {
        const lines = csv.split(/\r\n|\n/);
        const data = [];

        document.getElementById('tableTotal').text = lines.length;
        //const strCssTr = "infraTrClara";
        for (let i = 1; i < lines.length; i++) {
            //strCssTr = (strCssTr == 'infraTrClara') ? 'infraTrEscura' : 'infraTrClara';
            const formatLine = lines[i]
            const lineData = formatLine.toString().split(';');
            
            if (isNaN(parseInt(lineData[0]))) {
                continue;
            }
            const tipoProcessoId = parseInt(lineData[0]);
            const tipoProcessoNome = lineData[1];

            data.push(tipoProcessoId);
            data.push(tipoProcessoNome);

            const td1 = $('<td>', {'align': "center"})
                .text(tipoProcessoId);
            const td2 = $('<td>', {'align': "center"})
                .text(tipoProcessoNome);
            $('<tr>')
                //.addClass(strCssTr)
                .append(td1)
                .append(td2)
                .appendTo($('#tableImportar'));
        }

        document.getElementById('btnImportar').style = "display: none;";
        document.getElementById('btnProsseguir').style = "";

        return data.join(',').replaceAll('""', '"');
    }

    $(document).ready(function () {
        <?php if (!empty($cadastrou)) { ?>
            alert('Importação de tipos de processos realizada com sucesso.');
            $('#btnFecharSelecao').click();
        <?php } ?>
    });

</script>
<div id="divInfraBarraLocalizacao" class="infraBarraLocalizacao" tabindex="450">Pré-visualização de Improtar Tipos de Processos</div>
<br />
<input style="display: none" type="file" id="importArquivoCsv" accept=".csv" onchange="importarCsv(event)">
<form id="formImportarDados" method="post" action="">
    <input type="hidden" name="mapId" id="mapId">
    <input type="hidden" name="dados" id="dadosInput">
    <input type="hidden" name="orgaoId" id="orgaoId" value="<?= $_GET['idMapOrgao'] ?>'">
    <input type="hidden" name="dataCsv" id="dataCsv">
</form>
<form id="frmAssuntoLista" method="post" action="">
  <?
  PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandos);
  PaginaSEI::getInstance()->abrirAreaDados('10em');
  ?>
  <?php
  PaginaSEI::getInstance()->montarAreaTabela($strResultado, 1);
  PaginaSEI::getInstance()->fecharAreaDados();
  PaginaSEI::getInstance()->montarAreaDebug();
  PaginaSEI::getInstance()->montarBarraComandosInferior($arrComandosFinal);
  ?>
  
  <input type="hidden" name="hdnFlag" value="1" />  
  
</form>