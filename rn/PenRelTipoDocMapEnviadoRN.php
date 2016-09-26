<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

class PenRelTipoDocMapEnviadoRN extends InfraRN {

    public function __construct() {
        parent::__construct();
    }

    protected function inicializarObjInfraIBanco() {
        return BancoSEI::getInstance();
    }
    
    public function listarEmUso($dblIdSerie = 0) {

        $objInfraIBanco = $this->inicializarObjInfraIBanco();   

        $arrNumIdSerie = array();
        
        $objPenRelTipoDocMapRecebidoDTO = new PenRelTipoDocMapEnviadoDTO();
        $objPenRelTipoDocMapRecebidoDTO->retNumIdSerie();
        $objPenRelTipoDocMapRecebidoDTO->setDistinct(true);
        $objPenRelTipoDocMapRecebidoDTO->setOrdNumIdSerie(InfraDTO::$TIPO_ORDENACAO_ASC);

        $objGenericoBD = new GenericoBD($objInfraIBanco);
        $arrObjPenRelTipoDocMapRecebidoDTO = $objGenericoBD->listar($objPenRelTipoDocMapRecebidoDTO);

        if (!empty($arrObjPenRelTipoDocMapRecebidoDTO)) {

            foreach ($arrObjPenRelTipoDocMapRecebidoDTO as $objPenRelTipoDocMapRecebidoDTO) {

                $arrNumIdSerie[] = $objPenRelTipoDocMapRecebidoDTO->getNumIdSerie();
            }
        }

        if ($dblIdSerie > 0) {

            // Tira da lista de ignorados o que foi selecionado, em caso de
            // edição
            $numIndice = array_search($dblIdSerie, $arrNumIdSerie);

            if ($numIndice !== false) {
                unset($arrNumIdSerie[$numIndice]);
            }
        }
        
        return $arrNumIdSerie;
    }
    
    public function cadastrarConectado(PenRelTipoDocMapEnviadoDTO $objParamDTO){
        
        $objBD = new GenericoBD($this->inicializarObjInfraIBanco());
        
        if($objParamDTO->isSetDblIdMap()) {
            
            $objDTO = new PenRelTipoDocMapEnviadoDTO();
            $objDTO->setDblIdMap($objParamDTO->getDblIdMap());
            $objDTO->retTodos();

            $objDTO = $objBD->consultar($objDTO);
            
            if(empty($objDTO)) {
                throw new InfraException(sprintf('Nenhum Registro foi localizado com ao ID %s', $objParamDTO->getNumIdSerie()));   
            }
            
            $objDTO->setNumCodigoEspecie($objParamDTO->getNumCodigoEspecie()); 
            $objDTO->setNumIdSerie($objParamDTO->getNumIdSerie()); 
            $objBD->alterar($objDTO);
        }
        else {
            
            $objDTO = new PenRelTipoDocMapEnviadoDTO();
            $objDTO->setNumCodigoEspecie($objParamDTO->getNumCodigoEspecie()); 
            $objDTO->setNumIdSerie($objParamDTO->getNumIdSerie()); 
            $objDTO->setStrPadrao('S');
            $objBD->cadastrar($objDTO);
        }
    }
    
    /**
     * Muda o estado entre ativado/desativado
     * 
     * @param int|array Codigo da Especie
     * @throws InfraException
     * @return null
     */
    public static function mudarEstado($dblIdMap, $strPadrao = 'N'){
        
        $objBancoSEI = BancoSEI::getInstance();
        
        $objGenericoBD = new GenericoBD($objBancoSEI);
        
        if(is_array($dblIdMap)) {
                        
            foreach($dblIdMap as $_dblIdMap){

                $objDTO = new PenRelTipoDocMapEnviadoDTO();
                $objDTO->setNumCodigoEspecie($_dblIdMap);
                $objDTO->retStrPadrao();
                
                $objDTO->setStrPadrao($strPadrao);
                
                $objGenericoBD->alterar($objDTO);

            }
        }
        else {

            $objDTO = new PenRelTipoDocMapEnviadoDTO();
            $objDTO->setNumCodigoEspecie($dblIdMap);
            $objDTO->retStrPadrao();

            $objDTO->setStrPadrao($strPadrao);

            $objGenericoBD->alterar($objDTO);
        }       
    }
    
    /**
     * Exclui um ou um bloco de registros entre ativado/desativado
     * 
     * @param int|array Codigo da Especie
     * @throws InfraException
     * @return null
     */
    public static function excluir($dblIdMap){
        
        $objBancoSEI = BancoSEI::getInstance();
        
        $objGenericoBD = new GenericoBD($objBancoSEI);
        
        if(is_array($dblIdMap)) {
                        
            foreach($dblIdMap as $_dblIdMap){

                $objDTO = new PenRelTipoDocMapEnviadoDTO();
                $objDTO->setDblIdMap($_dblIdMap);
                
                $objGenericoBD->excluir($objDTO);
            }
        }
        else {

            $objDTO = new PenRelTipoDocMapEnviadoDTO();
            $objDTO->setDblIdMap($dblIdMap);
 
            $objGenericoBD->alterar($objDTO);
        }  
    }
}