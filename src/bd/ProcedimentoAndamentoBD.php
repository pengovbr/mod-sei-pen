<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Persistência de dados no banco de dados
 */
class ProcedimentoAndamentoBD extends InfraBD
{

  public function __construct(InfraIBanco $objInfraIBanco)
    {
      parent::__construct($objInfraIBanco);
  }
}
