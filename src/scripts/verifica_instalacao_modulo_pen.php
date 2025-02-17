<?php

$dirSeiWeb = !defined("DIR_SEI_WEB") ? getenv("DIR_SEI_WEB") ?: __DIR__."/../../web" : DIR_SEI_WEB;
require_once $dirSeiWeb . '/SEI.php';

// Garante que código abaixo foi executado unicamente via linha de comando
if ($argv && $argv[0] && realpath($argv[0]) === __FILE__) {
    InfraDebug::getInstance()->setBolLigado(true);
    InfraDebug::getInstance()->setBolDebugInfra(false);
    InfraDebug::getInstance()->setBolEcho(true);
    InfraDebug::getInstance()->limpar();

    $resultado = 0;

    $fnPrint = function ($strMensagem, $numIdentacao = 0): void {
        DebugPen::getInstance()->gravar($strMensagem, $numIdentacao, false, false);
    };


  try {
      SessaoSEI::getInstance(false);

      $objVerificadorInstalacaoRN = new VerificadorInstalacaoRN();

      $fnPrint("INICIANDO VERIFICAÇÃO DA INSTALAÇÃO DO MÓDULO MOD-SEI-PEN:", 0);

      sleep(1);
    if($objVerificadorInstalacaoRN->verificarPosicionamentoScripts()) {
        $fnPrint("- Arquivos do módulo posicionados corretamente", 1);
    }

      sleep(1);
    if($objVerificadorInstalacaoRN->verificarAtivacaoModulo()) {
        $fnPrint("- Módulo corretamente ativado no arquivo de configuracao do sistema", 1);
    }

      sleep(1);
    if($objVerificadorInstalacaoRN->verificarArquivoConfiguracao()) {
        $fnPrint("- Parâmetros técnicos obrigatórios de integração atribuídos em ConfiguracaoModPEN.php", 1);
    }        

      sleep(1);
    if($objVerificadorInstalacaoRN->verificarCertificadoDigital()) {
        $fnPrint("- Certificado digital localizado e corretamente configurado", 1);
    }

      sleep(1);
    if($objVerificadorInstalacaoRN->verificarConexaoBarramentoPEN()) {
        $fnPrint("- Conexão com o Tramita GOV.BR realizada com sucesso", 1);
    }

      sleep(1);
    if($objVerificadorInstalacaoRN->verificarAcessoPendenciasTramitePEN()) {
        $fnPrint("- Acesso aos dados do Comitê de Protocolo vinculado ao certificado realizado com sucesso", 1);
    }

      sleep(1);
    if($objVerificadorInstalacaoRN->verificarConfiguracaoGearman()) {
        $fnPrint("- Conexão com o servidor de processamento de tarefas Gearman realizada com sucesso", 1);
    }

      sleep(1);
    if($objVerificadorInstalacaoRN->verificarCompatibilidadeModulo()) {
        $fnPrint("- Verificada a compatibilidade do mod-sei-pen com a atual versão do SEI", 1);
    }

      sleep(1);
    if($objVerificadorInstalacaoRN->verificarCompatibilidadeBanco()) {
        $fnPrint("- Base de dados do SEI corretamente atualizada com a versão atual do mod-sei-pen", 1);
    }

      $fnPrint("", 0);
      $fnPrint("** VERIFICAÇÃO DA INSTALAÇÃO DO MÓDULO MOD-SEI-PEN FINALIZADA COM SUCESSO **", 0);

      exit(0);
  } finally {
      InfraDebug::getInstance()->setBolLigado(false);
      InfraDebug::getInstance()->setBolDebugInfra(false);
      InfraDebug::getInstance()->setBolEcho(false);
  }
}
