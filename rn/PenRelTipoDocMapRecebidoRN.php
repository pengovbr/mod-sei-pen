<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

class PenRelTipoDocMapRecebidoRN extends InfraRN {

    public function __construct() {
        parent::__construct();
    }

    protected function inicializarObjInfraIBanco() {
        return BancoSEI::getInstance();
    }

    public function listarEmUso($dblCodigoEspecie = 0)
    {    
        $arrNumCodigoEspecie = array();
        $objInfraIBanco = $this->inicializarObjInfraIBanco();  
                        
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
            // Tira da lista de ignorados o que foi selecionado, em caso de edição
            $numIndice = array_search($dblCodigoEspecie, $arrNumCodigoEspecie);
            if($numIndice !== false) {
                unset($arrNumCodigoEspecie[$numIndice]);
            }
        }
        
        return $arrNumCodigoEspecie;
    }
    
    public function cadastrarControlado(PenRelTipoDocMapRecebidoDTO $objParamDTO){
          
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


    protected function contarConectado(PenRelTipoDocMapRecebidoDTO $parObjPenRelTipoDocMapRecebidoDTO)
    {
        try {
          $objPenRelTipoDocMapRecebidoBD = new PenRelTipoDocMapRecebidoBD($this->getObjInfraIBanco());
          return $objPenRelTipoDocMapRecebidoBD->contar($parObjPenRelTipoDocMapRecebidoDTO);
        }catch(Exception $e){
          throw new InfraException('Erro contando Mapeamento de Tipos de Documento para Recebimento.',$e);
        }
      }
    
}
