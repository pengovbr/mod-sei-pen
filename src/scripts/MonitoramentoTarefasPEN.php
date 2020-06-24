<?php

$dirSeiWeb = !defined("DIR_SEI_WEB") ? getenv("DIR_SEI_WEB") ?: __DIR__."/../../web" : DIR_SEI_WEB;
require_once $dirSeiWeb . '/SEI.php';

// PHP internal, faz com que o tratamento de sinais funcione corretamente
// TODO: Substituir declaração por pcntl_async_signal no php 7
declare(ticks=1);

$bolEmExecucao = true;
function tratarSinalInterrupcaoMonitoramento($sinal)
{
    global $bolEmExecucao;
    $bolEmExecucao = false;
    printf("\nAtenção: Sinal de interrupção do monitoramento de pendências recebido. Finalizando processamento ...%s", PHP_EOL);
}

// Garante que código abaixo foi executado unicamente via linha de comando
if ($argv && $argv[0] && realpath($argv[0]) === __FILE__) {

    ini_set('max_execution_time','0');
    ini_set('memory_limit','-1');

    pcntl_signal(SIGINT, 'tratarSinalInterrupcaoMonitoramento');
    pcntl_signal(SIGTERM, 'tratarSinalInterrupcaoMonitoramento');
    pcntl_signal(SIGHUP, 'tratarSinalInterrupcaoMonitoramento');

    InfraDebug::getInstance()->setBolLigado(true);
    InfraDebug::getInstance()->setBolDebugInfra(false);
    InfraDebug::getInstance()->setBolEcho(true);
    InfraDebug::getInstance()->limpar();

    try {
        SessaoSEI::getInstance(false);
        $arrParametros = getopt("fd", array("monitorar", "segundo-plano", "debug", "wsdl-cache:"));
        $bolMonitorar = array_key_exists("f", $arrParametros) || array_key_exists("monitorar", $arrParametros);
        $parBolSegundoPlano = array_key_exists("d", $arrParametros) || array_key_exists("segundo-plano", $arrParametros);
        $parBoldebug = array_key_exists("debug", $arrParametros);
        $parStrWsdlCacheDir = array_key_exists("wsdl-cache", $arrParametros) ? $arrParametros["wsdl-cache"] : null;

        if(is_dir($parStrWsdlCacheDir)){
            ini_set('soap.wsdl_cache_dir', $parStrWsdlCacheDir);
        }

        $objPendenciasTramiteRN = new PendenciasTramiteRN("MONITORAMENTO");
        $resultado = $objPendenciasTramiteRN->encaminharPendencias($bolMonitorar, $parBolSegundoPlano, $parBoldebug);
        exit($resultado);

    } finally {
        InfraDebug::getInstance()->setBolLigado(false);
        InfraDebug::getInstance()->setBolDebugInfra(false);
        InfraDebug::getInstance()->setBolEcho(false);
    }
}
