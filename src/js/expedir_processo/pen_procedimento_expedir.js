
/**
 * Função de sinalização da conclusão do envio de processo/documento, modificando a aparência da barra de progresso 
 * 
 * @param {string} linkProcedimento Link para visualização do procedimento
 */
function sinalizarStatusConclusao(linkProcedimento,versao402=false)
{
    let componente = document.querySelector('div.infraBarraProgresso');
    if(componente) {
        componente.querySelector('.infraBarraProgressoMiolo').style.display = "none";
        componente.querySelector(".infraBarraProgressoBorda").style.backgroundColor = "green";

        let btnFechar = _criarBotaoFechar(linkProcedimento);
        btnFechar.classList.add('acaoBarraProgresso');
        if(!versao402) {
            btnFechar.classList.remove('infraButton');
        }
        document.getElementById('divInfraAreaDadosDinamica').appendChild(btnFechar);
    }
}

/**
 * Funcão de cancelamento do do trâmite em andamento
 * 
 * @param {number} idTramite 
 * @param {string} linkCancelarEnvioAjax 
 * @param {string} linkProcedimento Link para visualização do procedimento
 */
function cancelarEnvioProcesso(idTramite, linkCancelarEnvioAjax, linkProcedimento)
{    
    let btnCancelarEnvio = document.getElementById('btnCancelarEnvio');
    btnCancelarEnvio.disabled = true;
    btnCancelarEnvio.value = 'Cancelando...';    
    document.querySelector(".infraBarraProgressoMiolo").style.backgroundColor = "red";    
    window.stop();

    // Executa o procedimento de cancelamento do trâmite
    $.ajax(
        {
            type:"POST",
            url: linkCancelarEnvioAjax,
            dataType: "json",
            data: "id_tramite="+idTramite,
            success: function (result) {
                window.top.location = linkProcedimento;
            }
        }
    );
}

/**
 * Função responsável por adicionar o botão de fechar na janela modal de barra de progresso
 * 
 * @param {string} linkProcedimento Link para visualização do procedimento
 */
function adicionarBotaoFecharErro(linkProcedimento)
{
    let barraComandos = document.getElementById('divInfraBarraComandosSuperior')
    if(barraComandos) {
        barraComandos.appendChild(_criarBotaoFechar(linkProcedimento));
    }
}

/**
 * Função responsável por adicionar o botão de cancelamento de trâmite na janela modal de berra de progresso
 * 
 * @param {number} idTramite 
 * @param {string} linkCancelarEnvioAjax 
 * @param {string} linkProcedimento Link para visualização do procedimento
 */
function adicionarBotaoCancelarEnvio(idTramite, linkCancelarEnvioAjax, linkProcedimento)
{
    let btnCancelar = document.createElement('input');
    btnCancelar.id = 'btnCancelarEnvio';
    btnCancelar.type = 'button';
    btnCancelar.value = 'Cancelar';
    btnCancelar.classList.add('infraButton', 'acaoBarraProgresso');
    btnCancelar.addEventListener(
        "click", function (event) {
            cancelarEnvioProcesso(idTramite, linkCancelarEnvioAjax, linkProcedimento);
        }
    );

    let barraComandos = document.getElementById('divInfraAreaDadosDinamica')
    if(barraComandos) {
        barraComandos.appendChild(btnCancelar);
    }
}

/**
 * Função de criação do botão para fechar a janela modal da barra de progresso
 * 
 * @param {string} linkProcedimento Link para visualização do procedimento
 */
function _criarBotaoFechar(linkProcedimento)
{
    let btnFechar = document.createElement('input');
    btnFechar.id = 'btnFechar';
    btnFechar.type = 'button';
    btnFechar.value = 'Fechar';
    btnFechar.className = 'infraButton';
    btnFechar.addEventListener(
        "click", function (event) {
            window.top.location = linkProcedimento;
        }
    );

    return btnFechar;
}
