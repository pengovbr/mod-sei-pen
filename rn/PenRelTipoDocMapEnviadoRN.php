<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

class PenRelTipoDocMapEnviadoRN extends InfraRN 
{
    public function __construct() {
        parent::__construct();
    }

    protected function inicializarObjInfraIBanco() {
        return BancoSEI::getInstance();
    }
    
    
    protected function listarConectado(PenRelTipoDocMapEnviadoDTO $objPenRelTipoDocMapEnviadoDTO) 
    {
        try {        
            $objPenRelTipoDocMapEnviadoBD = new PenRelTipoDocMapEnviadoBD($this->getObjInfraIBanco());
            return $objPenRelTipoDocMapEnviadoBD->listar($objPenRelTipoDocMapEnviadoDTO);
        }catch(Exception $e){
            throw new InfraException('Erro listando mapeamento de documentos para envio.',$e);
        }
    }

    protected function consultarConectado(PenRelTipoDocMapEnviadoDTO $objPenRelTipoDocMapEnviadoDTO) 
    {
        try {        
            $objPenRelTipoDocMapEnviadoBD = new PenRelTipoDocMapEnviadoBD($this->getObjInfraIBanco());
            return $objPenRelTipoDocMapEnviadoBD->consultar($objPenRelTipoDocMapEnviadoDTO);
        }catch(Exception $e){
            throw new InfraException('Erro consultando mapeamento de documentos para envio.',$e);
        }
    }

    
    protected function listarEmUsoConectado($dblIdSerie = 0) 
    {
        $arrNumIdSerie = array();        
        $objPenRelTipoDocMapRecebidoDTO = new PenRelTipoDocMapEnviadoDTO();
        $objPenRelTipoDocMapRecebidoDTO->retNumIdSerie();
        $objPenRelTipoDocMapRecebidoDTO->setDistinct(true);
        $objPenRelTipoDocMapRecebidoDTO->setOrdNumIdSerie(InfraDTO::$TIPO_ORDENACAO_ASC);

        $objPenRelTipoDocMapEnviadoBD = new PenRelTipoDocMapEnviadoBD(BancoSEI::getInstance());
        $arrObjPenRelTipoDocMapRecebidoDTO = $objPenRelTipoDocMapEnviadoBD->listar($objPenRelTipoDocMapRecebidoDTO);

        if (!empty($arrObjPenRelTipoDocMapRecebidoDTO)) {
            foreach ($arrObjPenRelTipoDocMapRecebidoDTO as $objPenRelTipoDocMapRecebidoDTO) {
                $arrNumIdSerie[] = $objPenRelTipoDocMapRecebidoDTO->getNumIdSerie();
            }
        }

        if ($dblIdSerie > 0) {
            // Tira da lista de ignorados o que foi selecionado, em caso de edição
            $numIndice = array_search($dblIdSerie, $arrNumIdSerie);

            if ($numIndice !== false) {
                unset($arrNumIdSerie[$numIndice]);
            }
        }
        
        return $arrNumIdSerie;
    }
    
    protected function cadastrarControlado(PenRelTipoDocMapEnviadoDTO $objParamDTO)
    {
        $objBD = new PenRelTipoDocMapEnviadoBD($this->inicializarObjInfraIBanco());        
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
     * Exclui um ou um bloco de registros entre ativado/desativado
     * 
     * @param int|array Codigo da Especie
     * @throws InfraException
     * @return null
     */
    protected function excluirControlado($dblIdMap)
    {        
        $objPenRelTipoDocMapEnviadoBD = new PenRelTipoDocMapEnviadoBD(BancoSEI::getInstance());
        
        if(is_array($dblIdMap)) {
                        
            foreach($dblIdMap as $_dblIdMap){

                $objDTO = new PenRelTipoDocMapEnviadoDTO();
                $objDTO->setDblIdMap($_dblIdMap);                
                $objPenRelTipoDocMapEnviadoBD->excluir($objDTO);
            }
        }
        else {
            $objDTO = new PenRelTipoDocMapEnviadoDTO();
            $objDTO->setDblIdMap($dblIdMap); 
            $objPenRelTipoDocMapEnviadoBD->alterar($objDTO);
        }  
    }
}