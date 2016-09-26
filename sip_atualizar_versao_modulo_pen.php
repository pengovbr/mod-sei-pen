<?php
/**
 * Script para atualização do sistema SEI.
 * 
 * Modificar o "short_open_tag" para "On" no php.ini
 * 
 * PHP 5.3.3 (cli) (built: Jul  9 2015 17:39:00) 
 * Copyright (c) 1997-2010 The PHP Group
 * Zend Engine v2.3.0, Copyright (c) 1998-2010 Zend Technologies
 * 
 * @author Join Tecnologia
 */

set_include_path(implode(PATH_SEPARATOR, array(
    realpath(__DIR__.'/../infra_php'),
    get_include_path(),
)));

try {

    require_once __DIR__.'/../sei/SEI.php';
    
    $objPenConsoleRN = new PenConsoleRN();
    $arrArgs = $objPenConsoleRN->getTokens();
    
    $objAtualizarRN = new PenAtualizarSipRN($arrArgs);
    $objAtualizarRN->atualizarVersao();
    
    exit(0);
}
catch(Exception $e) {
    
    print InfraException::inspecionar($e);
    
    try {
        LogSEI::getInstance()->gravar(InfraException::inspecionar($e));
    } catch (Exception $e) {
        
    }
    
    exit(1);
}

print PHP_EOL;

/**
    # Apagar modulo PEN no SEI pelo MySQL

    # Apagar modulo PEN no SIP pelo MySQL
    SET FOREIGN_KEY_CHECKS = 1;

    DELETE FROM sip.recurso WHERE nome IN(
        'pen_procedimento_expedir',
        'pen_procedimento_expedido_listar',
        'pen_map_tipo_doc_enviado_visualizar',
        'pen_map_tipo_doc_enviado_cadastrar',
        'pen_map_tipo_doc_enviado_listar',
        'pen_map_tipo_doc_recebido_cadastrar',
        'pen_map_tipo_doc_recebido_listar'
    );
    SET FOREIGN_KEY_CHECKS = 0;

 */