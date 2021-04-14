<?php

require_once DIR_SEI_WEB.'/SEI.php';

abstract class PenRelHipoteseLegalRN extends InfraRN {

    protected function inicializarObjInfraIBanco(){
        return BancoSEI::getInstance();
    }

    protected function listarInternoConectado(PenRelHipoteseLegalDTO $objDTO)
    {
        try {
            $objBD = new GenericoBD($this->inicializarObjInfraIBanco());
            return $objBD->listar($objDTO);
        }
        catch (Exception $e) {
            throw new InfraException('Erro ao listar mapeamento de hipóteses legais', $e);
        }
    }

    protected function consultarInternoConectado(PenRelHipoteseLegalDTO $objDTO)
    {
        try {
            $objBD = new GenericoBD($this->inicializarObjInfraIBanco());
            return $objBD->consultar($objDTO);
        }
        catch (Exception $e) {
            throw new InfraException('Erro ao consultar mapeamento de hipóteses legais', $e);
        }
    }

    protected function alterarInternoControlado(PenRelHipoteseLegalDTO $objDTO)
    {
        try {

            //Regras de Negocio
            $objInfraException = new InfraException();
            $this->validarCadastroExistente($objDTO, $objInfraException);
            if($objInfraException->contemValidacoes()){
                return $objInfraException;
            }

            $objBD = new GenericoBD($this->inicializarObjInfraIBanco());
            return $objBD->alterar($objDTO);
        }
        catch (Exception $e) {
            throw new InfraException('Erro ao alterar mapeamento de hipótese legal', $e);
        }
    }

    protected function cadastrarInternoControlado(PenRelHipoteseLegalDTO $objDTO)
    {
        try {

            //Regras de Negocio
            $objInfraException = new InfraException();
            $this->validarCadastroExistente($objDTO, $objInfraException);
            if($objInfraException->contemValidacoes()){
                return $objInfraException;
            }

            $objBD = new GenericoBD($this->inicializarObjInfraIBanco());
            return $objBD->cadastrar($objDTO);
        }
        catch (Exception $e) {
            throw new InfraException('Erro ao cadastrar mapeamento de hipótese legal', $e);
        }
    }

    protected function excluirInternoControlado(PenRelHipoteseLegalDTO $objDTO)
    {
        try {
            $objBD = new GenericoBD($this->inicializarObjInfraIBanco());
            return $objBD->excluir($objDTO);
        }
        catch (Exception $e) {
            throw new InfraException('Erro ao excluir mapeamento de hipóteses legais', $e);
        }
    }

    private function validarCadastroExistente(PenRelHipoteseLegalDTO $objDTO, InfraException $objInfraException){

        $objPenRelHipoteseLegalDTO = new PenRelHipoteseLegalDTO();
        $objPenRelHipoteseLegalDTO->setNumIdHipoteseLegal($objDTO->getNumIdHipoteseLegal());
        $objPenRelHipoteseLegalDTO->setStrTipo($objDTO->getStrTipo());
        $objPenRelHipoteseLegalDTO->retDblIdMap();

        $ret = $this->consultarInterno($objPenRelHipoteseLegalDTO);

        if(!empty($ret)){
            $objInfraException->adicionarValidacao('já cadastrada.');
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

    public function getIdHipoteseLegalEmUso(PenRelHipoteseLegalDTO $objFiltroDTO, $strTipo = 'E')
    {
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
