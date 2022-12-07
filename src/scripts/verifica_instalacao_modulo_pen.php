<?php

$dirSeiWeb = !defined("DIR_SEI_WEB") ? getenv("DIR_SEI_WEB") ?: __DIR__."/../../web" : DIR_SEI_WEB;
require_once $dirSeiWeb . '/SEI.php';

// Garante que cï¿½digo abaixo foi executado unicamente via linha de comando
if ($argv && $argv[0] && realpath($argv[0]) === __FILE__) {
    InfraDebug::getInstance()->setBolLigado(true);
    InfraDebug::getInstance()->setBolDebugInfra(false);
    InfraDebug::getInstance()->setBolEcho(true);
    InfraDebug::getInstance()->limpar();

    $resultado = 0;

    $fnPrint = function($strMensagem, $numIdentacao=0) {
        DebugPen::getInstance()->gravar($strMensagem, $numIdentacao, false, false);
    };


    try {
        SessaoSEI::getInstance(false);

        $objVerificadorInstalacaoRN = new VerificadorInstalacaoRN();

        $fnPrint("INICIANDO VERIFICAï¿½ï¿½O DA INSTALAï¿½ï¿½O DO Mï¿½DULO MOD-SEI-PEN:", 0);

        sleep(1);
        if($objVerificadorInstalacaoRN->verificarPosicionamentoScripts()){
            $fnPrint("- Arquivos do mï¿½dulo posicionados corretamente", 1);
        }

        sleep(1);
        if($objVerificadorInstalacaoRN->verificarAtivacaoModulo()){
            $fnPrint("- Mï¿½dulo corretamente ativado no arquivo de configuracao do sistema", 1);
        }

        sleep(1);
        if($objVerificadorInstalacaoRN->verificarArquivoConfiguracao()){
            $fnPrint("- Parï¿½metros tï¿½cnicos obrigatï¿½rios de integraï¿½ï¿½o atribuï¿½dos em ConfiguracaoModPEN.php", 1);
        }        

        sleep(1);
        if($objVerificadorInstalacaoRN->verificarCertificadoDigital()){
            $fnPrint("- Certificado digital localizado e corretamente configurado", 1);
        }

        sleep(1);
        if($objVerificadorInstalacaoRN->verificarConexaoBarramentoPEN()){
            $fnPrint("- Conexï¿½o com o Barramento de Serviï¿½os do PEN realizada com sucesso", 1);
        }

        sleep(1);
        if($objVerificadorInstalacaoRN->verificarAcessoPendenciasTramitePEN()){
            $fnPrint("- Acesso aos dados do Comitï¿½ de Protocolo vinculado ao certificado realizado com sucesso", 1);
        }

        sleep(1);
        if($objVerificadorInstalacaoRN->verificarConfiguracaoGearman()){
            $fnPrint("- Conexï¿½o com o servidor de processamento de tarefas Gearman realizada com sucesso", 1);
        } else {
            throw new ErrorException($fnPrint("- servidor identificados para o gearmand, tentando proceder com a conexão -", 0));
            exit(1);
        }

        sleep(1);
        if($objVerificadorInstalacaoRN->verificarCompatibilidadeModulo()){
            $fnPrint("- Verificada a compatibilidade do mod-sei-pen com a atual versï¿½o do SEI", 1);
        }

        sleep(1);
        if($objVerificadorInstalacaoRN->verificarCompatibilidadeBanco()){
            $fnPrint("- Base de dados do SEI corretamente atualizada com a versï¿½o atual do mod-sei-pen", 1);
        }

        $fnPrint("", 0);
        $fnPrint("** VERIFICAï¿½ï¿½O DA INSTALAï¿½ï¿½O DO Mï¿½DULO MOD-SEI-PEN FINALIZADA COM SUCESSO **", 0);

        exit(0);
    } finally {
        InfraDebug::getInstance()->setBolLigado(false);
        InfraDebug::getInstance()->setBolDebugInfra(false);
        InfraDebug::getInstance()->setBolEcho(false);
    }
}
