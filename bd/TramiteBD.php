<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

class TramiteBD extends InfraBD {

  public function __construct(InfraIBanco $objInfraIBanco){
     parent::__construct($objInfraIBanco);
  }

}
?>