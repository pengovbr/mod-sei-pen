<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Description of PenHipoteseLegalRN
 *
 * @author michael
 */
class PenHipoteseLegalRN extends InfraRN 
{

  protected function inicializarObjInfraIBanco(){
      return BancoSEI::getInstance();
  }
    
  protected function listarConectado(PenHipoteseLegalDTO $objDTO)
    {               
    try {
        //SessaoSEI::getInstance()->validarAuditarPermissao('email_sistema_excluir', __METHOD__, $arrObjEmailSistemaDTO);
        $objBD = new GenericoBD($this->inicializarObjInfraIBanco());            
        return $objBD->listar($objDTO);
    } 
    catch (Exception $e) {
        throw new InfraException('Erro excluindo E-mail do Sistema.', $e);
    }
  }

  protected function consultarControlado(PenHipoteseLegalDTO $objDTO)
    {               
    try {
        //SessaoSEI::getInstance()->validarAuditarPermissao('email_sistema_excluir', __METHOD__, $arrObjEmailSistemaDTO);
        $objBD = new GenericoBD($this->inicializarObjInfraIBanco());            
        return $objBD->consultar($objDTO);
    } 
    catch (Exception $e) {
        throw new InfraException('Erro excluindo E-mail do Sistema.', $e);
    }
  }
}
