<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Classe gererica de persist�ncia com o banco de dados
 *
 *
 */
class GenericoBD extends InfraBD {

  public function __construct(InfraIBanco $objInfraIBanco) {
      parent::__construct($objInfraIBanco);
  }

}
