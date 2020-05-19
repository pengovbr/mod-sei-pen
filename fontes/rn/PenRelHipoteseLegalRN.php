<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Description of PenRelHipoteseLegalRN
 *
 * @author michael
 */
abstract class PenRelHipoteseLegalRN extends InfraRN {
     
    protected function inicializarObjInfraIBanco(){
        return BancoSEI::getInstance();
    }
    
    protected function listarConectado(PenRelHipoteseLegalDTO $objDTO){
        
        try {

            $objBD = new GenericoBD($this->inicializarObjInfraIBanco());

            return $objBD->listar($objDTO);
        } 
        catch (Exception $e) {
            throw new InfraException('Erro excluindo E-mail do Sistema.', $e);
        }
    }
    
    protected function alterarConectado(PenRelHipoteseLegalDTO $objDTO){
        
        try {

            $objBD = new GenericoBD($this->inicializarObjInfraIBanco());

            return $objBD->alterar($objDTO);
        } 
        catch (Exception $e) {
            throw new InfraException('Erro excluindo E-mail do Sistema.', $e);
        }
    }
    
    protected function cadastrarConectado(PenRelHipoteseLegalDTO $objDTO){
        
        try {

            $objBD = new GenericoBD($this->inicializarObjInfraIBanco());

            return $objBD->cadastrar($objDTO);
        } 
        catch (Exception $e) {
            throw new InfraException('Erro excluindo E-mail do Sistema.', $e);
        }
    }
    
    protected function excluirConectado(PenRelHipoteseLegalDTO $objDTO){
        
        try {

            $objBD = new GenericoBD($this->inicializarObjInfraIBanco());

            return $objBD->excluir($objDTO);
        } 
        catch (Exception $e) {
            throw new InfraException('Erro excluindo E-mail do Sistema.', $e);
        }
    }
    
    public function getIdBarramentoEmUso(PenRelHipoteseLegalDTO $objFiltroDTO, $strTipo = 'E'){

        $objDTO = new PenRelHipoteseLegalDTO();
        $objDTO->setDistinct(true);
        $objDTO->setStrTipo($strTipo);
        $objDTO->retNumIdBarramento();
        
        if($objFiltroDTO->isSetNumIdBarramento()) {
            $objDTO->setNumIdBarramento($objFiltroDTO->getNumIdBarramento(), InfraDTO::$OPER_DIFERENTE);
        }

        $arrObjDTO = $this->listar($objDTO);
        
        $arrIdBarramento = array();
        
        if(!empty($arrObjDTO)) {
            $arrIdBarramento = InfraArray::converterArrInfraDTO($arrObjDTO, 'IdBarramento');
        }
        return $arrIdBarramento;
    }
    
    public function getIdHipoteseLegalEmUso(PenRelHipoteseLegalDTO $objFiltroDTO, $strTipo = 'E'){

        $objDTO = new PenRelHipoteseLegalDTO();
        $objDTO->setDistinct(true);
        $objDTO->setStrTipo($strTipo);
        $objDTO->retNumIdHipoteseLegal();
        
        if($objFiltroDTO->isSetNumIdHipoteseLegal()) {
            $objDTO->setNumIdHipoteseLegal($objFiltroDTO->getNumIdHipoteseLegal(), InfraDTO::$OPER_DIFERENTE);
        }

        $arrObjDTO = $this->listar($objDTO);
        
        $arrIdBarramento = array();
        
        if(!empty($arrObjDTO)) {
            $arrIdBarramento = InfraArray::converterArrInfraDTO($arrObjDTO, 'IdHipoteseLegal');
        }
        return $arrIdBarramento;
    }
}
