<?php
/**
* 04/04/2019 - criado por Josinaldo Júnior
*/

    try {
require_once DIR_SEI_WEB.'/SEI.php';

        session_start();
        $objSessaoSEI = SessaoSEI::getInstance();
        $objPaginaSEI = PaginaSEI::getInstance();

        //////////////////////////////////////////////////////////////////////////////
        InfraDebug::getInstance()->setBolLigado(false);
        InfraDebug::getInstance()->setBolDebugInfra(true);
        InfraDebug::getInstance()->limpar();
        //////////////////////////////////////////////////////////////////////////////

        SessaoSEI::getInstance()->validarLink();
        $strTitulo     = "Seleção de Unidade Externa (Pesquisa em Árvore)";
        $arrComandos   = array();
        $arrComandos[] = '<button type="button" accesskey="P" id="btnPesquisar" value="Pesquisa Textual" onclick="abrirTelaDePesquisaTextual()" class="infraButton" disabled="disabled"><span class="infraTeclaAtalho">P</span>esquisa Textual</button>';
        $arrComandos[] = '<button type="button" accesskey="T" id="btnTransportarSelecao" value="Selecionar" onclick="transportarSelecao();" class="infraButton"><span class="infraTeclaAtalho">S</span>elecionar</button>';
        $arrComandos[] = '<button type="button" accesskey="F" id="btnFecharSelecao" value="Fechar" onclick="window.close();" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';

    }catch(Exception $e){
        PaginaSEI::getInstance()->processarExcecao($e);
    }

    PaginaSEI::getInstance()->montarDocType();
    PaginaSEI::getInstance()->abrirHtml();
    PaginaSEI::getInstance()->abrirHead();
    PaginaSEI::getInstance()->montarMeta();
    PaginaSEI::getInstance()->montarTitle(PaginaSEI::getInstance()->getStrNomeSistema().' - '. $strTitulo);
    PaginaSEI::getInstance()->montarStyle();
    PaginaSEI::getInstance()->abrirStyle();
?>
    .setaParaBaixo {
        vertical-align: top!important;
        margin-top: 3px!important;
    }

    .setaParaCima {
        vertical-align: middle!important;
        margin-bottom: 2px!important;
    }

    div.infraArvore{
        padding-left: 14px!important;
    }

    .unidadeSelecionada{ background-color: #79e5e5 !important; }
<?php
    PaginaSEI::getInstance()->fecharStyle();
    PaginaSEI::getInstance()->montarJavaScript();
//    PaginaSEI::getInstance()->abrirJavaScript();
?>
<script>
    var nivelEstrutura = 1;
    var mais  = '/infra_css/imagens/seta_abaixo.gif';
    var menos = '/infra_css/imagens/seta_acima.gif';
    var vazio = '/infra_js/arvore/empty.gif';
    var objJanelaPesquisaTextual = null;
    var evnJanelaPesquisaTextual = null;
    var nomeUnidadeRaiz = null;
    var idUnidadeRaiz = null;
    var idRepositorioDeEstuturaSelecionado = null;

    $(document).ready(function(){
       console.log('Repositorio selecionado:' + window.opener.$("#selRepositorioEstruturas").val());
       idRepositorioDeEstuturaSelecionado = window.opener.$("#selRepositorioEstruturas").val();
       recuperarEstruturaDeFilhosDeUnidadeExterna(null, 0, nivelEstrutura);
    });

    /**
     * Realiza a consulta dos filhos da unidade externa selecionada
     * Josinaldo Júnior <josinaldo.junior@basis.com.br>
     **/
    function recuperarEstruturaDeFilhosDeUnidadeExterna(idUnidadeExterna, nrDivPai, paramNivelEstrutura) {
        console.log('Unidade externa selecionada: '+idUnidadeExterna);

        if($('#controlador_'+idUnidadeExterna).val() != 1){
            //Exibe o gif de loading somente do nivel de estrutura 2 acima
            if(paramNivelEstrutura != 1){ carregaLoading(nrDivPai); }

            var strUrl = '<?php print $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador_ajax.php?acao_ajax=pen_pesquisar_unidades_administrativas_estrutura_pai'));?>';

            var objData = {};
            objData['idRepositorioEstruturaOrganizacional'] = idRepositorioDeEstuturaSelecionado;
            objData['numeroDeIdentificacaoDaEstrutura'] = idUnidadeExterna;
            console.log('Post: ' + strUrl);

            $.ajax({
                url:strUrl,
                method:'POST',
                dataType:'json',
                data: objData,
                cache: false,
                success:function(result) {
                    $('#controlador_'+idUnidadeExterna).val(1);
                    //verifica se o resultado é null
                    console.log(result);
                    if(result[0] != null) {
                        nivelEstrutura++;
                        adicionarEstruturaDeFilhos(nrDivPai, result, paramNivelEstrutura);
                    }else{
                        $('#icon' + nrDivPai).attr('alt', 'vazio');
                    }
                },
                error: function (data) {
                    alert("Não foi possível recuperar as unidades");
                    console.log('error: ' + data);
                }
            }).done(function(){
                $("#loading").hide();
                $("#divVazia").hide();
                paramNivelEstrutura != 1 ? trocaImagem(nrDivPai) : $("#icon"+nrDivPai).hide();
            });
        }else {
            if ($('#controlador_'+idUnidadeExterna).val() == 1 && $("#icon"+nrDivPai).attr("alt") == 'mais'){
                trocaImagem(nrDivPai)
                $(".divPai"+nrDivPai).show();
            }else{
                trocaImagem(nrDivPai)
                $(".divPai"+nrDivPai).hide();
            }
        }
    }

    /**
     * Exibe o gif de loading para mostrar para o usuário o processamento
     * Josinaldo Júnior <josinaldo.junior@basis.com.br>
     **/
    function carregaLoading(nrDiv)
    {
        $('#icon'+nrDiv).attr('src', '/infra_css/imagens/aguarde_pequeno.gif');
    }

    /**
     * Adiciona a estrutura de filhos na árvore da págin
     * Josinaldo Júnior <josinaldo.junior@basis.com.br>
     * */
    function adicionarEstruturaDeFilhos(nrDivPai, arrFilho, paramNivelEstrutura)
    {
        var novoNivelDeEstrutura = (paramNivelEstrutura + 1);

        for(var j in arrFilho){

            var imgArvore = (j == (arrFilho.length - 1) ? 'joinbottom' : 'join');
            var desabilitado = arrFilho[j]['aptoParaReceberTramites'] != 1 ?  ' disabled ' : '';
            var hint = arrFilho[j]['aptoParaReceberTramites'] != 1 ?  ' Unidade não está apta para receber tramitação.' : '';
            var arvore = (paramNivelEstrutura == 1) ? '' : '<img src="/infra_js/arvore/' + imgArvore + '.gif">';

            $('#divArvore' + nrDivPai).append('<div id="divArvore' + arrFilho[j]['numeroDeIdentificacaoDaEstrutura'] + '" class="infraArvore divPai'+nrDivPai+'" >' +
                 arvore +
                '&nbsp;<img class="setaParaCima" alt="mais" src="/infra_css/imagens/seta_abaixo.gif" id="icon'+arrFilho[j]['numeroDeIdentificacaoDaEstrutura']+'"' +
                'onclick="recuperarEstruturaDeFilhosDeUnidadeExterna(' + arrFilho[j]['numeroDeIdentificacaoDaEstrutura'] + ', ' + arrFilho[j]['numeroDeIdentificacaoDaEstrutura'] + ', ' + novoNivelDeEstrutura + ')">' +
                '<a id="anchorImg18">' +
                '<input '+ desabilitado +' title="'+ hint +'" name="estrutura_externa" id="estrutura_externa'+ arrFilho[j]['numeroDeIdentificacaoDaEstrutura'] +'" type="radio" value="'+ arrFilho[j]['numeroDeIdentificacaoDaEstrutura'] +'" onclick="selecionarUnidade('+arrFilho[j]['numeroDeIdentificacaoDaEstrutura']+');">' +
                '<input type="hidden" name="controlador_'+arrFilho[j]['numeroDeIdentificacaoDaEstrutura']+'" id="controlador_'+arrFilho[j]['numeroDeIdentificacaoDaEstrutura']+'" value="" />' +
                '<span style="cursor: pointer;" onclick="selecionarUnidade('+arrFilho[j]['numeroDeIdentificacaoDaEstrutura']+');" title="'+ arrFilho[j]['nome'] + '" name="span'+arrFilho[j]['numeroDeIdentificacaoDaEstrutura']+'" id="span'+arrFilho[j]['numeroDeIdentificacaoDaEstrutura']+'">' + arrFilho[j]['nome'] +'</span></a></div>');
        }
    }

    function selecionarUnidade(idElemento) {
        //Remove a classe .unidadeSelecionada do span que a possuir
        $("span[name^='span']").each(function(){
            $(this).removeClass('unidadeSelecionada');
        });

        //Adiciona a classe .unidadeSelecionada
        $("#span"+idElemento).addClass('unidadeSelecionada');

        //Verifica se o elemento está desabilitado, se não estiver já o seleciona
        //se estiver desabilitado verifica se já existe algum elemento selecionado e desfaz a seleção
        if(!$("#estrutura_externa"+idElemento).is(':disabled')){
            $("#estrutura_externa"+idElemento).prop('checked', true);
        }else{
            //remove a seleção do elemento que estiver selecionado
            $("input[name^='estrutura_externa']").each(function(){
                if($(this).prop('checked')) {
                    $(this).prop('checked', false);
                    return false;
                }
            });
        }

        //Define os parametros para a pesquisa textual
        idUnidadeRaiz   = idElemento;
        nomeUnidadeRaiz = $('#span'+idElemento).html();

        //habilita o botão de pesquisa textual
        $("#btnPesquisar").attr('disabled', false);
    }

    /**
     * Realiza a troca da imagem (seta para cima/para baixo)
     * Josinaldo Júnior <josinaldo.junior@basis.com.br>
     **/
    function trocaImagem(idElemento)
    {
        $('#icon'+idElemento).css('width' , '');
        if ($("#icon" + idElemento).attr("alt") == 'mais') {
            $('#icon' + idElemento).attr('src', menos);
            $('#icon' + idElemento).removeClass('setaParaCima');
            $('#icon' + idElemento).addClass('setaParaBaixo');
            $('#icon' + idElemento).attr('alt', 'menos');
        } else if($("#icon" + idElemento).attr("alt") == 'menos'){
            $('#icon' + idElemento).attr('src', mais);
            $('#icon' + idElemento).removeClass('setaParaBaixo');
            $('#icon' + idElemento).addClass('setaParaCima');
            $('#icon' + idElemento).attr('alt', 'mais');
        }else{
            $('#icon'+idElemento).attr('src', vazio);
            $('#icon'+idElemento).css('width' , '7px');
        }
    }

    /**
     * Função responsável por abrir a janela para realizar a pesquisa textual
     * @author: Josinaldo Júnior <josinaldo.junior@basis.com.br>
     **/
    function abrirTelaDePesquisaTextual()
    {
        var url = '<?php echo $objSessaoSEI->assinarLink('controlador.php?acao=pen_unidades_administrativas_externas_selecionar_expedir_procedimento&tipo_pesquisa=2'); ?>';
        objJanelaPesquisaTextual = abrirJanela(url, 'Pesquisa Textual', 700, 500);
    }

    /**
     * Gera a pop-up de expedir procedimento e cria os gatilho para o próprio fechamento
     **/
    function abrirJanela(url,nome,largura,altura){

        var opcoes = 'location=0,status=0,resizable=0,scrollbars=1,width=' + largura + ',height=' + altura;
        var largura = largura || 100;
        var altura = altura || 100;

        var janela = window.open(url, nome, opcoes);

        try{
            if (INFRA_CHROME>17) {
                setTimeout(function() {
                    janela.moveTo(((screen.availWidth/2) - (largura/2)),((screen.availHeight/2) - (altura/2)));
                },100);
            }
            else {
                janela.moveTo(((screen.availWidth/2) - (largura/2)),((screen.availHeight/2) - (altura/2)));
            }
            janela.focus();
        }
        catch(e){}


        infraJanelaModal = janela;

        var div = parent.document.getElementById('divInfraModalFundo');

        if (div==null){
            div = parent.document.createElement('div');
            div.id = 'divInfraModalFundo';
            div.className = 'infraFundoTransparente';

            if (INFRA_IE > 0 && INFRA_IE < 7){
                ifr = parent.document.createElement('iframe');
                ifr.className =  'infraFundoIE';
                div.appendChild(ifr);
            }else{
                div.onclick = function(){
                    try{
                        infraJanelaModal.focus();
                    }catch(exc){ }
                }
            }
            parent.document.body.appendChild(div);
        }

        if (INFRA_IE==0 || INFRA_IE>=7){
            div.style.position = 'fixed';
        }

        div.style.width = parent.infraClientWidth() + 'px';
        div.style.height = parent.infraClientHeight() + 'px';
        div.style.visibility = 'visible';

        evnJanelaPesquisaTextual = window.setInterval('monitorarJanela()', 100);

        return janela;
    }

    /**
     * Simula o evento onclose do pop-up
     *
     * @return {null}
     */
    function monitorarJanela(){

        if(objJanelaPesquisaTextual.closed) {
            window.clearInterval(evnJanelaPesquisaTextual);
            jQuery('#divInfraModalFundo', window.parent.document).css('visibility', 'hidden');
            //var strDestino = '<?php //print $objSessaoSEI->assinarLink('controlador.php?acao=procedimento_trabalhar&acao_origem=procedimento_controlar&acao_retorno=procedimento_controlar&id_procedimento='.$idProcedimento); ?>//';
            //if(strDestino) {
            //    window.top.location =  strDestino;
            //}
        }
    }

    /**
     * Função responsável por realizar o transporte da unidade selecionada para a tela de tramitação
     * @author: Josinaldo Júnior <josinaldo.junior@basis.com.br>
     **/
    function transportarSelecao() {
        var nomeUnidadeSelecionada = null;
        var idUnidadeSelecionada = null;

        //percorre os inputs radio para identificar qual está selecionado
        $("input[name^='estrutura_externa']").each(function(){
            if($(this).prop('checked')){
                idUnidadeSelecionada   = $(this).val();
                nomeUnidadeSelecionada = $('#span'+idUnidadeSelecionada).html();
            }
        });

        //verifica se existem itens selecionados
        if(nomeUnidadeSelecionada != null && idUnidadeSelecionada != null){
            console.log('Id repositorio de estruturas: ' + window.opener.$("#selRepositorioEstruturas").val());
            window.opener.$("#txtUnidade").val(nomeUnidadeSelecionada);
            window.opener.$("#hdnIdUnidade").val(idUnidadeSelecionada);
        }else{
            alert('Nenhum item foi selecionado.');
        }
    }

    /**
     * Função responsável por codificar string para utf8
     * @author: Josinaldo Júnior <josinaldo.junior@basis.com.br>
     **/
    function encode_utf8(string) {
        return encodeURIComponent(string);
    }
</script>
<?
    PaginaSEI::getInstance()->fecharHead();
?>
    <div id="divInfraBarraLocalizacao" class="infraBarraLocalizacao">Seleção de Unidade Externa</div>
    <form id="frmApensadosLista" method="post" action="">
        <?php
            PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandos);
            PaginaSEI::getInstance()->abrirAreaDados();
        ?>
        <div>
            <fieldset>
                <legend><label>Unidades</label></legend>
                <div id="divArvore0">
                    <div style="position:absolute;left:50%;top:17.5%;" id="loading" width="99%"><img src="/infra_css/imagens/aguarde.gif"></div>
                    <img src="" id="icon0" style="text-align: center;"/>
                    <div id="divVazia">&nbsp;</div>
                </div>
            </fieldset>
            <input type="hidden" id="hdnInfraNroItens" name="hdnInfraNroItens" value="" />
        </div>
        <?php
            PaginaSEI::getInstance()->montarAreaDebug();
        ?>
    </form>
<?php
    PaginaSEI::getInstance()->fecharHtml();
?>
