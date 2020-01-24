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

try {

    require_once dirname(__FILE__).'/../web/SEI.php';


    $objAtualizarRN = new PenAtualizarSeiRN();
    $objAtualizarRN->atualizarVersao();

    exit(0);
}
catch(InfraException $e){

    print $e->getStrDescricao().PHP_EOL;
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
