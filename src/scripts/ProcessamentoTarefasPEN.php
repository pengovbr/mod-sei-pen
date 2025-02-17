<?php

$dirSeiWeb = !defined("DIR_SEI_WEB") ? getenv("DIR_SEI_WEB") ?: __DIR__."/../../web" : DIR_SEI_WEB;
require_once $dirSeiWeb . '/SEI.php';

class ProcessamentoTarefasPEN
{
  private static $instance;

  public static function getInstance()
    {
    if (self::$instance == null) {
        self::$instance = new ProcessamentoTarefasPEN();
    }
      return self::$instance;
  }

  public function __construct()
    {
      ini_set('max_execution_time', '0');
      ini_set('memory_limit', '-1');
  }


  public function processarPendencias()
    {
      InfraDebug::getInstance()->setBolLigado(false);
      InfraDebug::getInstance()->setBolDebugInfra(false);
      InfraDebug::getInstance()->setBolEcho(false);
      InfraDebug::getInstance()->limpar();

    try {
        SessaoSEI::getInstance(false);
        $objProcessarPendenciasRN = new ProcessarPendenciasRN("PROCESSAMENTO");
        $resultado = $objProcessarPendenciasRN->processarPendencias();
        exit($resultado);
    } finally {
        InfraDebug::getInstance()->setBolLigado(false);
        InfraDebug::getInstance()->setBolDebugInfra(false);
        InfraDebug::getInstance()->setBolEcho(false);
    }
  }
}


// Garante que código abaixo foi executado unicamente via linha de comando
if ($argv && $argv[0] && realpath($argv[0]) === __FILE__) {
    ProcessamentoTarefasPEN::getInstance()->processarPendencias();
}

?>
