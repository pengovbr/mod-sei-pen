
function sinalizarStatusConclusao(seletorBarraProgresso){
    componente = document.querySelector(seletorBarraProgresso);
    if(componente){
        componente.querySelector('.infraBarraProgressoMiolo').style.display = "none";
        componente.querySelector(".infraBarraProgressoBorda").style.backgroundColor = "green";
    }
}

function sinalizarStatusErro(seletorBarraProgresso){
    componente = document.querySelector(seletorBarraProgresso);
    if(componente){
        componente.querySelector('.infraBarraProgressoMiolo').style.display = "none";
        componente.querySelector(".infraBarraProgressoBorda").style.backgroundColor = "red";
    }
}