<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

class PenRelTipoDocMapRecebidoRN extends InfraRN {

    public function __construct() {
        parent::__construct();
    }

    protected function inicializarObjInfraIBanco() {
        return BancoSEI::getInstance();
    }

    protected function listarEmUsoConectado($dblCodigoEspecie = 0){
        
        $objInfraIBanco = $this->inicializarObjInfraIBanco();  
        
        $arrNumCodigoEspecie = array();
        
        $objDTO = new PenRelTipoDocMapRecebidoDTO();  
        $objDTO->retNumCodigoEspecie();
        $objDTO->setDistinct(true);
        //$objDTO->setOrdNumCodigoEspecie(InfraDTO::$TIPO_ORDENACAO_ASC);
        $objDTO->setBolExclusaoLogica(false);

        $objGenericoBD = new GenericoBD($objInfraIBanco);
        $arrObjPenRelTipoDocMapRecebidoDTO = $objGenericoBD->listar($objDTO);

        if(!empty($arrObjPenRelTipoDocMapRecebidoDTO)) {

            foreach($arrObjPenRelTipoDocMapRecebidoDTO as $objDTO) {

                $arrNumCodigoEspecie[] = $objDTO->getNumCodigoEspecie();
            }
        }

        if($dblCodigoEspecie > 0) {

            // Tira da lista de ignorados o que foi selecionado, em caso de
            // edição
            $numIndice = array_search($dblCodigoEspecie, $arrNumCodigoEspecie);

            if($numIndice !== false) {
                unset($arrNumCodigoEspecie[$numIndice]);
            }
        }
        
        return $arrNumCodigoEspecie;
    }
    
    protected function listarConectado(PenRelTipoDocMapRecebidoDTO $objPenRelTipoDocMapRecebidoDTO) 
    {
        try {        
            $objPenRelTipoDocMapRecebidoBD = new PenRelTipoDocMapEnviadoBD($this->getObjInfraIBanco());
            return $objPenRelTipoDocMapRecebidoBD->listar($objPenRelTipoDocMapRecebidoDTO);
        }catch(Exception $e){
            throw new InfraException('Erro listando mapeamento de documentos para recebimento.',$e);
        }
    }

    protected function consultarConectado(PenRelTipoDocMapRecebidoDTO $objPenRelTipoDocMapRecebidoDTO) 
    {
        try {        
            $objPenRelTipoDocMapRecebidoBD = new PenRelTipoDocMapRecebidoBD($this->getObjInfraIBanco());
            return $objPenRelTipoDocMapRecebidoBD->consultar($objPenRelTipoDocMapRecebidoDTO);
        }catch(Exception $e){
            throw new InfraException('Erro consultando mapeamento de documentos para recebimento.',$e);
        }
    }


    protected function alterarControlado(PenRelTipoDocMapRecebidoDTO $objPenRelTipoDocMapRecebidoDTO) 
    {
        try {        
            $objPenRelTipoDocMapRecebidoBD = new PenRelTipoDocMapRecebidoBD($this->getObjInfraIBanco());
            return $objPenRelTipoDocMapRecebidoBD->alterar($objPenRelTipoDocMapRecebidoDTO);
        }catch(Exception $e){
            throw new InfraException('Erro alterando mapeamento de documentos para recebimento.',$e);
        }
    }    


    protected function excluirControlado($parDlbIdMap) 
    {
        try {
            $arrDblIdMap = !is_array($parDlbIdMap) ? array($parDlbIdMap) : $parDlbIdMap;
            $objPenRelTipoDocMapRecebidoBD = new PenRelTipoDocMapRecebidoBD($this->getObjInfraIBanco());
            foreach ($arrDblIdMap as $dblIdMap) {
                $objPenRelTipoDocMapRecebidoDTO = new PenRelTipoDocMapRecebidoDTO();
                $objPenRelTipoDocMapRecebidoDTO->setDblIdMap($dblIdMap);
                $objPenRelTipoDocMapRecebidoBD->excluir($objPenRelTipoDocMapRecebidoDTO);
            } 
        }catch(Exception $e){
            throw new InfraException('Erro excluir mapeamento de documentos para recebimento.', $e);
        }
    }


    /**
     * Exclui um ou um bloco de registros entre ativado/desativado
     * 
     * @param int|array Codigo da Especie
     * @throws InfraException
     * @return null
     */
    protected function excluir2Controlado($dblIdMap)
    {        
        $objPenRelTipoDocMapRecebidoBD = new PenRelTipoDocMapRecebidoBD(BancoSEI::getInstance());
        
        if(is_array($dblIdMap)) {
            foreach($dblIdMap as $_dblIdMap){
                $objDTO = new PenRelTipoDocMapRecebidoDTO();
                $objDTO->setDblIdMap($_dblIdMap);                
                $objPenRelTipoDocMapRecebidoBD->excluir($objDTO);
            }
        }
        else {
            $objDTO = new PenRelTipoDocMapRecebidoDTO();
            $objDTO->setDblIdMap($dblIdMap); 
            $objPenRelTipoDocMapRecebidoBD->alterar($objDTO);
        }  
    }

    protected function cadastrarControlado(PenRelTipoDocMapRecebidoDTO $objParamDTO)
    {          
        $objDTO = new PenRelTipoDocMapRecebidoDTO();
        $objDTO->setNumCodigoEspecie($objParamDTO->getNumCodigoEspecie());
        $objDTO->retTodos();
        
        $objBD = new GenericoBD($this->inicializarObjInfraIBanco());
        $objDTO = $objBD->consultar($objDTO);
        
        if(empty($objDTO)) {            
            $objDTO = new PenRelTipoDocMapRecebidoDTO();
            $objDTO->setNumIdSerie($objParamDTO->getNumIdSerie());
            $objDTO->setNumCodigoEspecie($objParamDTO->getNumCodigoEspecie());
            $objDTO->setStrPadrao('S');
            $objBD->cadastrar($objDTO);  
        }
        else {
            
            $objDTO->setNumIdSerie($objParamDTO->getNumIdSerie()); 
            $objBD->alterar($objDTO);
        }
    }
}
