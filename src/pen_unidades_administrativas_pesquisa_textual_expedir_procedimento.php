<?php
/**
 * 10/04/2019 - criado por Josinaldo Júnior <josinaldo.junior@basis.com.br>
 **/
require_once DIR_SEI_WEB.'/SEI.php';

try {

        session_start();

        $objSessaoSEI = SessaoSEI::getInstance();
        $objPaginaSEI = PaginaSEI::getInstance();

        //////////////////////////////////////////////////////////////////////////////
        // InfraDebug::getInstance()->setBolLigado(false);
        // InfraDebug::getInstance()->setBolDebugInfra(true);
        // InfraDebug::getInstance()->limpar();
        //////////////////////////////////////////////////////////////////////////////
        SessaoSEI::getInstance()->validarLink();

        $strTitulo = 'Seleção de Unidade Externa (Pesquisa Textual)';
        $arrComandos = [];
        $arrComandos[] = '<button type="button" accesskey="P" id="btnPesquisar" value="Pesquisar" class="infraButton" onclick="recuperarEstruturaDeFilhosDeUnidadeExterna(1);"><span class="infraTeclaAtalho">P</span>esquisar</button>';
        $arrComandos[] = '<button type="button" accesskey="S" id="btnTransportarSelecao" value="Selecionar" onclick="transportarSelecao();" class="infraButton"><span class="infraTeclaAtalho">S</span>elecionar</button>';
        $arrComandos[] = '<button type="button" accesskey="F" id="btnFecharSelecao" value="Fechar" onclick="window.close();" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';

        $strResultado = '';
        $numRegistros = 1;
        $strSumarioTabela = 'Tabela de Unidades.';
        $strCaptionTabela = 'Unidades';
        $strResultado .= '<table id="tabelaUnidades" width="99%" class="infraTable" summary="'.$strSumarioTabela.'">'."\n";
        $strResultado .= '<caption class="infraCaption">Lista de Unidades<span id="totalDeRegistros"> <span></caption>';
        $strResultado .= '<tr>';
        $strResultado .= '<th class="infraTh" width="5%"> '."\n";
        $strResultado .= '<th class="infraTh" width="15%">Sigla</th>'."\n";
        $strResultado .= '<th class="infraTh" width="70%">Nome</th>'."\n";
        $strResultado .= '<th class="infraTh" width="10%">Ações</th>'."\n";
        $strResultado .= '</tr>'."\n";
        $strResultado .= '</table>';

}catch(Exception $e){
    PaginaSEI::getInstance()->processarExcecao($e);
}

    PaginaSEI::getInstance()->montarDocType();
    PaginaSEI::getInstance()->abrirHtml();
    PaginaSEI::getInstance()->abrirHead();
    PaginaSEI::getInstance()->montarMeta();
    PaginaSEI::getInstance()->montarTitle(PaginaSEI::getInstance()->getStrNomeSistema().' - '.$strTitulo);
    PaginaSEI::getInstance()->montarStyle();
    PaginaSEI::getInstance()->abrirStyle();
?>
    body {
        margin: 8px 8px;
    }      

    #lblSiglaUnidade {position:absolute;left:0%;top:45%;width:20%;}
    #txtSiglaUnidade {position:absolute;left:0%;top:65%;width:20%;}

    #lblNomeUnidade {position:absolute;left:25%;top:45%;width:73%;}
    #txtNomeUnidade {position:absolute;left:25%;top:65%;width:73%;}

    #lblUnidadeRaiz {position:absolute;left:0%;top:0%;width: 98%;}
    #txtUnidadeRaiz {position:absolute;left:0%;top:20%;width:98%;}

    .unidadeSelecionada{ background-color: #79e5e5 !important; }
<?php
    PaginaSEI::getInstance()->fecharStyle();
    PaginaSEI::getInstance()->montarJavaScript();
//    PaginaSEI::getInstance()->abrirJavaScript();
?>
<script>
    var strUrl = '<?php print $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador_ajax.php?acao_ajax=pen_pesquisar_unidades_administrativas_estrutura_pai_textual'));?>';
    var idRepositorioDeEstuturaSelecionado = null;
    var idUnidadeRaizSelecionada = null;
    var siglaUnidade = null;
    var nomeUnidade = null;
    var offset = 0;
    var totalInicial = 0;
    var paginaAtual = 0;
    var totalDePaginas = 0;

    $(document).ready(function(){
        const nomeUnidadeRaizSelecionada = window.opener.nomeUnidadeRaiz;
        const parentWindow = window?.opener?.opener ?? window.opener.parent.document.getElementById('ifrVisualizacao').contentWindow;
        idRepositorioDeEstuturaSelecionado = $("#selRepositorioEstruturas", parentWindow.document).val();
        console.log('Unidade raiz selecionada:' + nomeUnidadeRaizSelecionada + ' ('+idUnidadeRaizSelecionada +')');

        $("#tabelaUnidades").hide();
        $("#divInfraAreaPaginacaoInferior").hide();
        $("#txtUnidadeRaiz").val(nomeUnidadeRaizSelecionada);
        $("#hdnIdUnidadeRaiz").val(idUnidadeRaizSelecionada);

        recuperarEstruturaDeFilhosDeUnidadeExterna();
    });

    /**
     * Realiza a consulta dos filhos da unidade externa selecionada, conforme os filtros passados
     * @author: Josinaldo Júnior <josinaldo.junior@basis.com.br>
     * @param paramPagina
     **/
    function recuperarEstruturaDeFilhosDeUnidadeExterna(paramPagina) {
        //Apresenta loading
        $("#divInfraAvisoFundo").show();

        if(paramPagina){
            paginaAtual = 0;
        }

        var label = $('#btnPesquisar').html();
        $('#btnPesquisar').attr('disabled', true);
        $('#btnPesquisar').html('Pesquisando...');

        var objParametros = atribuirParametrosDePesquisa();
        console.log(objParametros);

        $.ajax({
            url:strUrl,
            method:'POST',
            dataType:'json',
            data: objParametros,
            cache: false,
            success:function(result) {
                //verifica se o resultado é null
                if(result['estrutura'][0] != null) {
                    console.log(result);
                    montarTabela(result);
                }else{
                    $("tr[id^='tr_']").remove();
                    $("#totalDeRegistros").html(' (0) registros:');
                    $('#divInfraAreaPaginacaoInferior').hide();
                    $('#tabelaUnidades').append( '<tr id="tr_0" class="infraTrClara linhas"><td colspan="4" align="center">Nenhuma unidade encontrada.</td></tr>');
                }
            },
            error: function (data) {
                alert('Não foi possível recuperar as unidades.');
                $("#divInfraAvisoFundo").hide();
                $('#divInfraAreaPaginacaoInferior').hide();
            }
        }).done(function(){
            $("#divInfraAvisoFundo").hide();
            $('#tabelaUnidades').show();
            $('#btnPesquisar').html(label);
            $('#btnPesquisar').attr('disabled', false);
        });
    }

    /**
     * Função responsável por montar os parametros para pesquisa
     * @author: Josinaldo Júnior <josinaldo.junior@basis.com.br>
     **/
    function atribuirParametrosDePesquisa(){
        var objData = {};
        objData['idRepositorioEstruturaOrganizacional'] = idRepositorioDeEstuturaSelecionado;
        objData['numeroDeIdentificacaoDaEstrutura'] = idUnidadeRaizSelecionada;

        //Verifica se os parametros foram alterados para realizar nova consulta com offset 0
        if($("#txtSiglaUnidade").val() != siglaUnidade || $("#txtNomeUnidade").val() != nomeUnidade){
            paginaAtual = 0;
            totalDePaginas = 1;
        }

        siglaUnidade = $("#txtSiglaUnidade").val() != siglaUnidade  ? $("#txtSiglaUnidade").val() : siglaUnidade;
        nomeUnidade  = $("#txtNomeUnidade").val()  != nomeUnidade   ? $("#txtNomeUnidade").val()  : nomeUnidade;

        objData['siglaUnidade'] = siglaUnidade;
        objData['nomeUnidade']  = nomeUnidade;
        objData['offset'] = paginaAtual;
        return objData;
    }

    /**
     * Adiciona a estrutura de filhos na árvore da págin
     * @author: Josinaldo Júnior <josinaldo.junior@basis.com.br>
     * @param arrItens
     **/
    function montarTabela(arrItens){
        var i = 0;
        var totalRegistros = arrItens['totalDeRegistros'];
        var registrosPorPagina = arrItens['registrosPorPagina']
        var desc = (totalRegistros > 1) ? 'registros:' : 'registro:';

        if(totalInicial != totalRegistros){
            totalInicial = totalRegistros;
            montarPaginacao(totalRegistros, registrosPorPagina);
        }

        $("#totalDeRegistros").html(' ('+totalRegistros +') ' + desc );
        $("tr[id^='tr_']").remove();
        for(var j in arrItens['estrutura']){
            var desabilitado = arrItens['estrutura'][j]['aptoParaReceberTramites'] != 1 ?  ' disabled ' : '';
            var strCssTr = (i % 2 == 0) ? 'infraTrClara' : 'infraTrEscura';
            var hint = arrItens['estrutura'][j]['aptoParaReceberTramites'] != 1 ?  ' Unidade não está apta para receber tramitação.' : '';
            var acao = arrItens['estrutura'][j]['aptoParaReceberTramites'] != 1 ?  ' - ' : '<img src="/infra_css/imagens/transportar.gif" title="Selecionar este item e Fechar" alt="Selecionar este item e Fechar" class="infraImg" onclick="transportarSelecao(' + arrItens['estrutura'][j]['numeroDeIdentificacaoDaEstrutura'] + ', 1)">';
            $('#tabelaUnidades').append( '<tr id="tr_'+arrItens['estrutura'][j]['numeroDeIdentificacaoDaEstrutura']+'" class="'+strCssTr+'"> ' +
                '<td><input ' + desabilitado + ' title="' + hint + '" type="radio" name="unidade_externa" id="unidade'+arrItens['estrutura'][j]['numeroDeIdentificacaoDaEstrutura']+'" value="' + arrItens['estrutura'][j]['numeroDeIdentificacaoDaEstrutura'] + '" onclick="selecionarUnidade('+arrItens['estrutura'][j]['numeroDeIdentificacaoDaEstrutura']+');"></td>' +
                '<td>' + arrItens['estrutura'][j]['sigla']  + '</td>' +
                '<td>' + arrItens['estrutura'][j]['nome'] + '<input type="hidden" name="hdnNomeUnidade" id="hdnNomeUnidade'+arrItens['estrutura'][j]['numeroDeIdentificacaoDaEstrutura']+'" value="' + arrItens['estrutura'][j]['nome'] + '"></td>' +
                '<td align="center">' + acao + '</td>' +
                '</tr>');
            i++;
        }

        validaExibicaoAreaPaginacao(paginaAtual);
    }

    /**
     * Função responsável por montar o select da paginação
     * @author: Josinaldo Júnior <josinaldo.junior@basis.com.br>
     * @param totalRegistros
     **/
    function montarPaginacao(totalRegistros, registrosPorPagina) {
        totalDePaginas = Math.ceil(totalRegistros / registrosPorPagina);
        $("#nrTotalPaginas").val(totalDePaginas);

        //Remove as opções da paginação
        $("option[id^='optionPaginate']").remove();

        //Cria as options da paginação
        for (var i = 0; i < totalDePaginas; i++) {
            var nrPagina = i + 1;
            $('#selInfraPaginacaoInferior').append('<option id="optionPaginate'+ i +'" value="'+ i +'">'+ nrPagina +'</option>');
        }
    }

    /**
     * Função responsável por realizar a paginação dos registros
     * @author: Josinaldo Júnior <josinaldo.junior@basis.com.br>
     * @param param, valor
     **/
    function acaoPaginar(param, valor){
        if(param == '='){
            paginaAtual = parseInt(valor);
            $('#selInfraPaginacaoInferior').val(paginaAtual);
        }else if(param == '+'){
            paginaAtual++;
            $('#selInfraPaginacaoInferior').val(paginaAtual);
        }else if(param == '-'){
            paginaAtual--;
            if (paginaAtual < 0){
                paginaAtual = 0;
            }
            $('#selInfraPaginacaoInferior').val(paginaAtual);
        }else{
            paginaAtual = (totalDePaginas - 1);
            $('#selInfraPaginacaoInferior').val(paginaAtual);
        }

        validaExibicaoAreaPaginacao(paginaAtual);
        recuperarEstruturaDeFilhosDeUnidadeExterna();
    }

    /**
     * Função responsável por validar a exibição das ações da paginação
     * @author: Josinaldo Júnior <josinaldo.junior@basis.com.br>
     * @param paginaAtual
     **/
    function validaExibicaoAreaPaginacao(paginaAtual){
        //verifica se possue mais de uma pagina
        if(totalDePaginas > 1) {
            $("#lnkInfraPrimeiraPaginaInferior, #lnkInfraPaginaAnteriorInferior").show();
            if (parseInt(paginaAtual) == 0) {
                $("#lnkInfraPrimeiraPaginaInferior, #lnkInfraPaginaAnteriorInferior").hide();
            }

            $("#lnkInfraProximaPaginaInferior, #lnkInfraUltimaPaginaInferior").show();
            if ((parseInt(paginaAtual) + 1) == totalDePaginas) {
                $("#lnkInfraProximaPaginaInferior, #lnkInfraUltimaPaginaInferior").hide();
            }
            $("#divInfraAreaPaginacaoInferior").show();
        }else{
            $("#divInfraAreaPaginacaoInferior").hide();
        }
    }

    /**
     * Função responsável por realizar o transporte da unidade selecionada para a tela de tramitação
     * @author: Josinaldo Júnior <josinaldo.junior@basis.com.br>
     * @param idElemento, paramFechar
     **/
    function transportarSelecao(idElemento, paramFechar) {
        var nomeUnidadeSelecionada = null;
        var idUnidadeSelecionada = null;

        if(idElemento) { $("#unidade"+idElemento).prop('checked', true); }

        //percorre os inputs radio para identificar qual está selecionado
        $("input[name^='unidade']").each(function(){
            if($(this).prop('checked')){
                idUnidadeSelecionada   = $(this).val();
                nomeUnidadeSelecionada = $('#hdnNomeUnidade'+idUnidadeSelecionada).val();
                return false;
            }
        });

        //verifica se existem itens selecionados, se hover realiza o trasporte dos valores selecionados
        if(nomeUnidadeSelecionada != null && idUnidadeSelecionada != null){
            const parentWindow = window?.opener?.opener ?? window.opener.parent.document.getElementById('ifrVisualizacao').contentWindow;
            $("#txtUnidade", parentWindow.document).val(nomeUnidadeSelecionada);
            $("#hdnIdUnidade", parentWindow.document).val(idUnidadeSelecionada);
            if(paramFechar){
                if(window.opener){
                    window.opener.close();
                }
                if(window.opener.infraFecharJanelaSelecao){
                    window.opener.infraFecharJanelaSelecao();
                }
                window.close(); 
            }
        }else{
            alert('Nenhum item foi selecionado.');
        }
    }

    /**
     * Função responsável por marcar a unidade selecionada
     * @author: Josinaldo Júnior <josinaldo.junior@basis.com.br>
     * @param idElemento
     **/
    function selecionarUnidade(idElemento){
        //Remove a classe .unidadeSelecionada da tr que a possuir
        $("tr[id^='tr_']").each(function(){
            $(this).removeClass('unidadeSelecionada');
        });

        //Adiciona a classe .unidadeSelecionada
        $("#tr_"+idElemento).addClass('unidadeSelecionada');
    }

    //Caso seja pressionada a tecla enter, realiza a pesquisa textual
    $(document).keypress(function(e) {
        if (e.which == 13) {
            $('#btnPesquisar').click();
        }
    });
</script>
<?
    PaginaSEI::getInstance()->fecharHead();
?>
    <div id="divInfraAreaTela" class="infraAreaTela">
        <div id="divInfraBarraLocalizacao" class="infraBarraLocalizacao" >Pesquisa textual de unidades externas</div> &nbsp;
        <form id="frmUnidadeLista" method="post" action="<?php echo SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.htmlspecialchars($_GET['acao']).'&acao_origem='.htmlspecialchars($_GET['acao']).'&id_orgao='.htmlspecialchars($_GET['id_orgao']))?>">
            <input type="hidden" id="hdnIdUnidadeRaiz" name="hdnIdUnidadeRaiz" value="" />
            <?php
                PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandos);
                PaginaSEI::getInstance()->abrirAreaDados('10em');
            ?>
                <label id="lblUnidadeRaiz" for="lblUnidadeRaiz" class="infraLabelOpcional">Unidade raiz da pesquisa:</label>
                <input type="text" id="txtUnidadeRaiz" name="txtUnidadeRaiz" class="infraText" value="" maxlength="15" tabindex="<?php echo PaginaSEI::getInstance()->getProxTabDados()?>" disabled />

                <label id="lblSiglaUnidade" for="txtSiglaUnidade" class="infraLabelOpcional">Sigla:</label>
                <input type="text" id="txtSiglaUnidade" name="txtSiglaUnidade" class="infraText" value="<?php echo $strSiglaPesquisa?>" maxlength="15" tabindex="<?php echo PaginaSEI::getInstance()->getProxTabDados()?>" />
                <label id="lblNomeUnidade" for="txtNomeUnidade" class="infraLabelOpcional">Nome:</label>
                <input type="text" id="txtNomeUnidade" name="txtNomeUnidade" class="infraText" tabindex="<?php echo PaginaSEI::getInstance()->getProxTabDados()?>" value="" />
            <?php
                PaginaSEI::getInstance()->fecharAreaDados();
                PaginaSEI::getInstance()->montarAreaTabela($strResultado, $numRegistros);
                PaginaSEI::getInstance()->montarAreaDebug();
                // PaginaSEI::getInstance()->montarBarraComandosInferior($arrComandos);
            ?>
            <div id="divInfraAreaPaginacaoInferior" class="infraAreaPaginacao">
                <input type="hidden" name="nrTotalPaginas" id="nrTotalPaginas" value="">
                <a id="lnkInfraPrimeiraPaginaInferior" href="javascript:void(0);" onclick="acaoPaginar('=',0);" title="Primeira Página" tabindex="56"><img src="/infra_css/imagens/primeira_pagina.gif" title="Primeira Página" alt="Primeira Página" class="infraImg"></a>&nbsp;&nbsp;
                <a id="lnkInfraPaginaAnteriorInferior" href="javascript:void(0);" onclick="acaoPaginar('-',0);" title="Página Anterior" tabindex="57"><img src="/infra_css/imagens/pagina_anterior.gif" title="Página Anterior" alt="Página Anterior" class="infraImg"></a>&nbsp;&nbsp;
                <select id="selInfraPaginacaoInferior" name="selInfraPaginacaoInferior" onchange="acaoPaginar('=', this.value);" class="infraSelect" tabindex="58" style="display:inline;"></select>&nbsp;&nbsp;
                <a id="lnkInfraProximaPaginaInferior" href="javascript:void(0);" onclick="acaoPaginar('+',0);" title="Próxima Página" tabindex="59"><img src="/infra_css/imagens/proxima_pagina.gif" title="Próxima Página" alt="Próxima Página" class="infraImg"></a>&nbsp;&nbsp;
                &nbsp;<a id="lnkInfraUltimaPaginaInferior" href="javascript:void(0);" onclick="acaoPaginar('>', 0)" title="Última Página" tabindex="60"><img src="/infra_css/imagens/ultima_pagina.gif" title="Última Página" alt="Última Página" class="infraImg"></a>
            </div>
            <div id="divInfraAvisoFundo" class="infraFundoTransparente" style="position: fixed; width: 100%; height: 100%; visibility: visible;">
                <div id="divInfraAviso" class="infraAviso" style="top: 45%; left: 40%; width: 200px;">
                    <table border="0" width="100%" cellspacing="4">
                        <tbody>
                        <tr>
                            <td><img id="imgInfraAviso" src="/infra_css/imagens/aguarde.gif" alt="..."></td>
                            <td align="left"><span id="spnInfraAviso">Processando...</span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
<?php
    PaginaSEI::getInstance()->fecharHtml();
?>
