<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

/**
 * Repositório para métodos para Tipo de Documento Mapeados pelo módulo PEN
 * 
 * @autor Join Tecnologia
 */
class TipoDocMapRN extends InfraRN {

    protected function inicializarObjInfraIBanco() {
        return BancoSEI::getInstance();
    }

    /**
     * Retorna um array de chave => valor, onde a chave é a ID e o valor é a descrição
     * do registro no banco de dados
     * 
     * @param bool $bolRemoverUtilizados remover os que já tem alguma relação
     * @param int $dblCodigoEspecie Código do Tipo de Documento. Só funciona em
     * conjunto com o primeiro paramêtro
     * @return array
     */
    protected function listarParesEspecieConectado($arrNumCodigoEspecie = array()) 
    {
        try {
            $objInfraIBanco = $this->inicializarObjInfraIBanco();
            $objGenericoBD = new GenericoBD($objInfraIBanco);

            $objEspecieDocumentalDTO = new EspecieDocumentalDTO();
            $objEspecieDocumentalDTO->retDblIdEspecie();
            $objEspecieDocumentalDTO->retStrNomeEspecie();
            $objEspecieDocumentalDTO->setOrdStrNomeEspecie(InfraDTO::$TIPO_ORDENACAO_ASC);

            if (!empty($arrNumCodigoEspecie)) {
                $objEspecieDocumentalDTO->setDblIdEspecie($arrNumCodigoEspecie, InfraDTO::$OPER_NOT_IN);
            }

            $arrEspecieDocumentalDTO = $objGenericoBD->listar($objEspecieDocumentalDTO);
            $arrRetorno = array();

            if (!empty($arrEspecieDocumentalDTO)) {
                foreach ($arrEspecieDocumentalDTO as $objEspecieDocumentalDTO) {

                    $strChave = strval($objEspecieDocumentalDTO->getDblIdEspecie());
                    $strValor = InfraString::formatarXML($objEspecieDocumentalDTO->getStrNomeEspecie());
                    $arrRetorno[$strChave] = $strValor;
                }
            }
            return $arrRetorno;
        } catch (InfraException $e) {
            
        }
    }
    
    /**
     * Retorna um array de chave => valor, onde a chave é a ID e o valor é a descrição
     * do registro no banco de dados.
     * Utilizado na View, então processa as exception na página de erro
     * 
     * @param int $dblIdSerie Código selecionado da Espécie Documental. 
     * Só funciona em conjunto com o primeiro paramêtro
     * @return array
     */
    public function listarParesSerie($arrNumIdSerie = array(), $bolListarTodos = false)
    {         
        try {
            $arrRetorno = array();
            $objInfraIBanco = $this->inicializarObjInfraIBanco();

            $objSerieDTO = new SerieDTO();
            $objSerieDTO->retNumIdSerie();
            $objSerieDTO->retStrNome();
            
            if($bolListarTodos === false) {
                $objSerieDTO->setStrStaAplicabilidade('I', InfraDTO::$OPER_DIFERENTE);
            }
            $objSerieDTO->setOrdStrNome(InfraDTO::$TIPO_ORDENACAO_ASC);

            if(!empty($arrNumIdSerie)) {
                $objSerieDTO->setNumIdSerie($arrNumIdSerie, InfraDTO::$OPER_NOT_IN);
            } 

            $objSerieRN = new SerieRN($objInfraIBanco);
            $arrObjSerieDTO = $objSerieRN->listarRN0646($objSerieDTO);
                        
            if(!empty($arrObjSerieDTO)) {                
                foreach($arrObjSerieDTO as $objSerieDTO) {
                    $strChave = strval($objSerieDTO->getNumIdSerie());
                    $strValor = InfraString::formatarXML($objSerieDTO->getStrNome());
                    $arrRetorno[$strChave] = $strValor;
                }            
            } 
            return $arrRetorno;        
        }
        catch (InfraException $e) {
            PaginaSEI::getInstance()->processarExcecao($e);
        }
    } 

}
