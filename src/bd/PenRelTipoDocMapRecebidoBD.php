<?php

require_once DIR_SEI_WEB.'/SEI.php';

class PenRelTipoDocMapRecebidoBD extends InfraBD
{

  public function __construct(InfraIBanco $objInfraIBanco)
    {
      parent::__construct($objInfraIBanco);
  }
}
