<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

/**
 * Description of PenHipoteseLegalRN
 *
 * @author michael
 */
class PenHipoteseLegalRN extends InfraRN {

    protected function inicializarObjInfraIBanco(){
        return BancoSEI::getInstance();
    }
    

    protected function listarControlado(PenHipoteseLegalDTO $objDTO){
               
        try {

            //SessaoSEI::getInstance()->validarAuditarPermissao('email_sistema_excluir', __METHOD__, $arrObjEmailSistemaDTO);

            $objBD = new GenericoBD($this->inicializarObjInfraIBanco());
            
            return $objBD->listar($objDTO);
        } 
        catch (Exception $e) {
            throw new InfraException('Erro excluindo E-mail do Sistema.', $e);
        }
    }
}
