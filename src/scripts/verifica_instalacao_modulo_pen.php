<?php

$dirSeiWeb = !defined("DIR_SEI_WEB") ? getenv("DIR_SEI_WEB") ?: __DIR__."/../../web" : DIR_SEI_WEB;
require_once $dirSeiWeb . '/SEI.php';

// Garante que c�digo abaixo foi executado unicamente via linha de comando
if ($argv && $argv[0] && realpath($argv[0]) === __FILE__) {
    InfraDebug::getInstance()->setBolLigado(true);
    InfraDebug::getInstance()->setBolDebugInfra(false);
    InfraDebug::getInstance()->setBolEcho(true);
    InfraDebug::getInstance()->limpar();

    $resultado = 0;

    $fnPrint = function($strMensagem, $numIdentacao = 0) {
        DebugPen::getInstance()->gravar($strMensagem, $numIdentacao, false, false);
    };


  try {
      SessaoSEI::getInstance(false);

      $objVerificadorInstalacaoRN = new VerificadorInstalacaoRN();

      $fnPrint("INICIANDO VERIFICA��O DA INSTALA��O DO M�DULO MOD-SEI-PEN:", 0);

      sleep(1);
    if($objVerificadorInstalacaoRN->verificarPosicionamentoScripts()){
        $fnPrint("- Arquivos do m�dulo posicionados corretamente", 1);
    }

      sleep(1);
    if($objVerificadorInstalacaoRN->verificarAtivacaoModulo()){
        $fnPrint("- M�dulo corretamente ativado no arquivo de configuracao do sistema", 1);
    }

      sleep(1);
    if($objVerificadorInstalacaoRN->verificarArquivoConfiguracao()){
        $fnPrint("- Par�metros t�cnicos obrigat�rios de integra��o atribu�dos em ConfiguracaoModPEN.php", 1);
    }        

      sleep(1);
    if($objVerificadorInstalacaoRN->verificarCertificadoDigital()){
        $fnPrint("- Certificado digital localizado e corretamente configurado", 1);
    }

      sleep(1);
    if($objVerificadorInstalacaoRN->verificarConexaoBarramentoPEN()){
        $fnPrint("- Conex�o com o Barramento de Servi�os do PEN realizada com sucesso", 1);
    }

      sleep(1);
    if($objVerificadorInstalacaoRN->verificarAcessoPendenciasTramitePEN()){
        $fnPrint("- Acesso aos dados do Comit� de Protocolo vinculado ao certificado realizado com sucesso", 1);
    }

      sleep(1);
    if($objVerificadorInstalacaoRN->verificarConfiguracaoGearman()){
        $fnPrint("- Conex�o com o servidor de processamento de tarefas Gearman realizada com sucesso", 1);
    }

      sleep(1);
    if($objVerificadorInstalacaoRN->verificarCompatibilidadeModulo()){
        $fnPrint("- Verificada a compatibilidade do mod-sei-pen com a atual vers�o do SEI", 1);
    }

      sleep(1);
    if($objVerificadorInstalacaoRN->verificarCompatibilidadeBanco()){
        $fnPrint("- Base de dados do SEI corretamente atualizada com a vers�o atual do mod-sei-pen", 1);
    }

      $fnPrint("", 0);
      $fnPrint("** VERIFICA��O DA INSTALA��O DO M�DULO MOD-SEI-PEN FINALIZADA COM SUCESSO **", 0);

      exit(0);
  } finally {
      InfraDebug::getInstance()->setBolLigado(false);
      InfraDebug::getInstance()->setBolDebugInfra(false);
      InfraDebug::getInstance()->setBolEcho(false);
  }
}
