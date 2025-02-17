<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Classe gererica de persistência com o banco de dados
 */
class PenBlocoProcessoBD extends InfraBD
{

  public function __construct(InfraIBanco $objInfraIBanco)
    {
      parent::__construct($objInfraIBanco);
  }

}
