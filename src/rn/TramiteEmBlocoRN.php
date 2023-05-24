<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Description of TramiteEmBloco
 *
 * Tramitar em bloco
 */
class TramiteEmBloco extends InfraRN {
    
    /**
     * Inicializa o obj do banco da Infra
     * @return obj
     */
  protected function inicializarObjInfraIBanco(){
      return BancoSEI::getInstance();
  }

}
