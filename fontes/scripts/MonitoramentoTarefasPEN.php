<?php

if(!defined("DIR_SEI_WEB")){
    define("DIR_SEI_WEB", getenv("DIR_SEI_WEB"));
}

require_once DIR_SEI_WEB.'/SEI.php';

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
        $arrParametros = getopt("md", array("monitorar", "segundo-plano"));
        $bolMonitorar = array_key_exists("f", $arrParametros) || array_key_exists("monitorar", $arrParametros);
        $parBolSegundoPlano = array_key_exists("d", $arrParametros) || array_key_exists("segundo-plano", $arrParametros);
        $objPendenciasTramiteRN = new PendenciasTramiteRN("MONITORAMENTO");
        $resultado = $objPendenciasTramiteRN->encaminharPendencias($bolMonitorar, $parBolSegundoPlano);
        exit($resultado);
    } finally {
        InfraDebug::getInstance()->setBolLigado(false);
        InfraDebug::getInstance()->setBolDebugInfra(false);
        InfraDebug::getInstance()->setBolEcho(false);
    }
}
