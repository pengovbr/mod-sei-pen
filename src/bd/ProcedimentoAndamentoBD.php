<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Persist�ncia de dados no banco de dados
 * 
 *
 */
class ProcedimentoAndamentoBD extends InfraBD {

  public function __construct(InfraIBanco $objInfraIBanco) {
      parent::__construct($objInfraIBanco);
  }
}
