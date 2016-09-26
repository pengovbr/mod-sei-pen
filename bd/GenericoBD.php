<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

/**
 * Classe gererica de persistência com o banco de dados
 * 
 * @author Join Tecnologia
 */
class GenericoBD extends InfraBD {

    public function __construct(InfraIBanco $objInfraIBanco) {
        parent::__construct($objInfraIBanco);
    }

}
