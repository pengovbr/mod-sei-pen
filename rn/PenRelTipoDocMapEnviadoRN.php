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

    
    /**
     * Remove uma espécie documental da base de dados do SEI baseado em um código de espécie do Barramento
     *
     * @param int $parNumIdEspecieDocumentla
     * @return void
     */
    protected function excluirPorEspecieDocumentalControlado($parNumIdEspecieDocumental)
    {        
        try {
            $objPenRelTipoDocMapEnviadoBD = new PenRelTipoDocMapEnviadoBD($this->getObjInfraIBanco());
            $objPenRelTipoDocMapEnviadoDTO = new PenRelTipoDocMapEnviadoDTO();
            $objPenRelTipoDocMapEnviadoDTO->setNumCodigoEspecie($parNumIdEspecieDocumental);
            $objPenRelTipoDocMapEnviadoDTO->retDblIdMap();
            
            foreach ($objPenRelTipoDocMapEnviadoBD->listar($objPenRelTipoDocMapEnviadoDTO) as $objDTO) {
                $objPenRelTipoDocMapEnviadoBD->excluir($objDTO);
            }
                                    
          }catch(Exception $e){
            throw new InfraException('Erro removendo Mapeamento de Tipos de Documento para envio pelo código de espécie.',$e);
          }
    }

    protected function contarConectado(PenRelTipoDocMapEnviadoDTO $parObjPenRelTipoDocMapEnviadoDTO)
    {
        try {
          $objPenRelTipoDocMapEnviadoBD = new PenRelTipoDocMapEnviadoBD($this->getObjInfraIBanco());
          return $objPenRelTipoDocMapEnviadoBD->contar($parObjPenRelTipoDocMapEnviadoDTO);
        }catch(Exception $e){
          throw new InfraException('Erro contando Mapeamento de Tipos de Documento para Envio.',$e);
        }
    }        

    /**
     * Registra o mapeamento de Tipos de Documentos para ENVIO com as espécies documentais similares do Barramento do PEN
     * 
     * A análise de simularidade utiliza o algorítmo para calcular a distãncia entre os dois nomes
     * Mais informações sobre o algorítmo podem ser encontradas no link abaixo:
     * https://www.php.net/manual/pt_BR/function.similar-text.php
     *
     * @return void
     */
    protected function mapearEspeciesDocumentaisEnvioControlado()
    {        
        $objTipoDocMapRN = new TipoDocMapRN();
        $objPenRelTipoDocMapEnviadoRN = new PenRelTipoDocMapEnviadoRN();

        //Persentual de similaridade mínimo aceito para que a espécie documental possa ser automaticamente mapeada
        $numPercentualSimilaridadeValido = 85;

        // Obter todas as espécies documentais do Barramento de Serviços do PEN
        $arrEspeciesDocumentais = array();
        $arrEspecies = $objTipoDocMapRN->listarParesEspecie();
        foreach ($arrEspecies as $numCodigo => $strItem) {
            foreach (preg_split('/\//', $strItem) as $strNomeEspecie) {
                $arrEspeciesDocumentais[] = array("codigo" => $numCodigo, "nome" => $strNomeEspecie);
            }            
        }                
        
        $objPenRelTipoDocMapEnviadoRN = new PenRelTipoDocMapEnviadoRN();
        $arrTiposDocumentos = $objTipoDocMapRN->listarParesSerie($objPenRelTipoDocMapEnviadoRN->listarEmUso(null), true);

        // Verificar se existe Tipo de Documento com nome semelhante na base de dados
        foreach ($arrTiposDocumentos as $numIdTipoDocumento => $strNomeTipoDocumento) {
            $numMelhorSimilaridade = null;
            $numIdEspecieSimilar = null;
            $numTamNomeTipoDoc = strlen($strNomeTipoDocumento);

            foreach ($arrEspeciesDocumentais as $objEspecieDocumental) {
                $numIdEspecieDocumental = $objEspecieDocumental["codigo"];
                $strNomeEspecieDocumental = $objEspecieDocumental["nome"];
                $numSimilaridade = 0;
                
                $numTamNomeEspecie = strlen($strNomeEspecieDocumental);                
                $numPosEspacoAdicional = strpos($strNomeTipoDocumento, ' ', min($numTamNomeEspecie, $numTamNomeTipoDoc));
                if($numPosEspacoAdicional){
                    // Avaliação com tamanho reduzido, caso seja um termo composto
                    $numTamanhoReducao = max($numTamNomeEspecie, $numPosEspacoAdicional);
                    $strNomeTipoDocReduzido = substr($strNomeTipoDocumento, 0, $numTamanhoReducao);
                    similar_text(strtolower($strNomeEspecieDocumental), strtolower($strNomeTipoDocReduzido), $numSimilaridadeReduzido);
                    $numSimilaridade = $numSimilaridadeReduzido;
                    
                } else {
                    // Avaliação de termo em tamanho normal
                    similar_text(strtolower($strNomeEspecieDocumental), strtolower($strNomeTipoDocumento), $numSimilaridadeNormal);
                    $numSimilaridade = $numSimilaridadeNormal;
                }

                if($numMelhorSimilaridade < $numSimilaridade && $numSimilaridade > $numPercentualSimilaridadeValido) {
                    $numMelhorSimilaridade = $numSimilaridade;
                    $numIdEspecieSimilar = $numIdEspecieDocumental;
                }
            }
            
            if(isset($numMelhorSimilaridade)){
                // Realiza o mapeamento do tipo de documento com a espécie documental similar
                $objPenRelTipoDocMapEnviadoDTO = new PenRelTipoDocMapEnviadoDTO();
                $objPenRelTipoDocMapEnviadoDTO->setNumIdSerie($numIdTipoDocumento);
                if($objPenRelTipoDocMapEnviadoRN->contar($objPenRelTipoDocMapEnviadoDTO) == 0){
                    $objPenRelTipoDocMapEnviadoDTO->setNumCodigoEspecie($numIdEspecieSimilar);
                    $objPenRelTipoDocMapEnviadoRN->cadastrar($objPenRelTipoDocMapEnviadoDTO);
                }
            }
        }    
    }    
}