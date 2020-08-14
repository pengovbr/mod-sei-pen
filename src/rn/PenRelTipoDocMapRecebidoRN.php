<?php

require_once DIR_SEI_WEB.'/SEI.php';

class PenRelTipoDocMapRecebidoRN extends InfraRN {

    public function __construct() {
        parent::__construct();
    }

    protected function inicializarObjInfraIBanco() {
        return BancoSEI::getInstance();
    }

    protected function listarEmUsoConectado($dblCodigoEspecie)
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

        if(!is_null($dblCodigoEspecie) && $dblCodigoEspecie > 0) {
            // Tira da lista de ignorados o que foi selecionado, em caso de edi��o
            $numIndice = array_search($dblCodigoEspecie, $arrNumCodigoEspecie);
            if($numIndice !== false) {
                unset($arrNumCodigoEspecie[$numIndice]);
            }
        }

        return $arrNumCodigoEspecie;
    }

    /**
     * Cadastra mapeamentos de tipos de documentos para envio de processos pelo Barramento PEN
     *
     * @param PenRelTipoDocMapRecebidoDTO $parObjPenRelTipoDocMapRecebidoDTO
     * @return void
     */
    public function cadastrarControlado(PenRelTipoDocMapRecebidoDTO $objParamDTO)
    {
        $objDTO = new PenRelTipoDocMapRecebidoDTO();
        $objDTO->setNumCodigoEspecie($objParamDTO->getNumCodigoEspecie());
        $objDTO->retTodos();

        $objBD = new GenericoBD($this->inicializarObjInfraIBanco());
        $objDTO = $objBD->consultar($objDTO);

        if(empty($objDTO)) {
            SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_tipo_documento_recebimento_cadastrar', __METHOD__, $objParamDTO);
            $objDTO = new PenRelTipoDocMapRecebidoDTO();
            $objDTO->setNumIdSerie($objParamDTO->getNumIdSerie());
            $objDTO->setNumCodigoEspecie($objParamDTO->getNumCodigoEspecie());
            $objBD->cadastrar($objDTO);
        }
        else {
            SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_tipo_documento_recebimento_alterar', __METHOD__, $objParamDTO);
            $objDTO->setNumIdSerie($objParamDTO->getNumIdSerie());
            $objBD->alterar($objDTO);
        }
    }


    /**
     * Consulta os mapeamentos de tipos de documentos para envio de processos pelo Barramento PEN para recebimento
     *
     * @param PenRelTipoDocMapRecebidoDTO $parObjPenRelTipoDocMapRecebidoDTO
     * @return void
     */
    protected function consultarConectado(PenRelTipoDocMapRecebidoDTO $parObjPenRelTipoDocMapRecebidoDTO)
    {
        try {
            $objPenRelTipoDocMapRecebidoBD = new PenRelTipoDocMapEnviadoBD($this->getObjInfraIBanco());
            return $objPenRelTipoDocMapRecebidoBD->consultar($parObjPenRelTipoDocMapRecebidoDTO);
        }catch(Exception $e){
            throw new InfraException('Erro consultando mapeamento de documentos para recebimento.',$e);
        }
    }


    /**
     * Remove uma esp�cie documental da base de dados do SEI baseado em um c�digo de esp�cie do Barramento
     *
     * @param int $parNumIdEspecieDocumentla
     * @return void
     */
    protected function excluirPorEspecieDocumentalControlado($parNumIdEspecieDocumental)
    {
        try {
            $objPenRelTipoDocMapRecebidoBD = new PenRelTipoDocMapRecebidoBD($this->getObjInfraIBanco());
            $objPenRelTipoDocMapRecebidoDTO = new PenRelTipoDocMapRecebidoDTO();
            $objPenRelTipoDocMapRecebidoDTO->setNumCodigoEspecie($parNumIdEspecieDocumental);
            $objPenRelTipoDocMapRecebidoDTO->retDblIdMap();

            foreach ($objPenRelTipoDocMapRecebidoBD->listar($objPenRelTipoDocMapRecebidoDTO) as $objDTO) {
                $objPenRelTipoDocMapRecebidoBD->excluir($objDTO);
            }

          }catch(Exception $e){
            throw new InfraException('Erro removendo Mapeamento de Tipos de Documento para recebimento pelo c�digo de esp�cie.',$e);
          }
    }

    /**
     * Lista mapeamentos de tipos de documentos para recebimento de processos pelo Barramento PEN
     *
     * @param PenRelTipoDocMapRecebidoDTO $parObjPenRelTipoDocMapRecebidoDTO
     * @return array
     */
    protected function listarConectado(PenRelTipoDocMapRecebidoDTO $parObjPenRelTipoDocMapRecebidoDTO)
    {
        try {
            SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_tipo_documento_recebimento_listar', __METHOD__, $parObjPenRelTipoDocMapRecebidoDTO);
            $objPenRelTipoDocMapRecebidoBD = new PenRelTipoDocMapRecebidoBD($this->getObjInfraIBanco());
            return $objPenRelTipoDocMapRecebidoBD->listar($parObjPenRelTipoDocMapRecebidoDTO);
        }catch(Exception $e){
            throw new InfraException('Erro listando mapeamento de Tipos de Documento para recebimento.',$e);
        }
    }

    /**
     * Conta a lista de mapeamentos de tipos de documentos para envio de processos pelo Barramento PEN
     *
     * @param PenRelTipoDocMapRecebidoDTO $parObjPenRelTipoDocMapRecebidoDTO
     * @return int
     */
    protected function contarConectado(PenRelTipoDocMapRecebidoDTO $parObjPenRelTipoDocMapRecebidoDTO)
    {
        try {
          $objPenRelTipoDocMapRecebidoBD = new PenRelTipoDocMapRecebidoBD($this->getObjInfraIBanco());
          return $objPenRelTipoDocMapRecebidoBD->contar($parObjPenRelTipoDocMapRecebidoDTO);
        }catch(Exception $e){
          throw new InfraException('Erro contando Mapeamento de Tipos de Documento para Recebimento.',$e);
        }
    }

    /**
     * Exclui lista de mapeamentos de tipos de documentos para envio de processos pelo Barramento PEN
     *
     * @param PenRelTipoDocMapRecebidoDTO $parObjPenRelTipoDocMapRecebidoDTO
     * @return void
     */
    protected function excluirControlado($parArrObjPenRelTipoDocMapRecebidoDTO)
    {
        try {
            SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_tipo_documento_recebimento_excluir', __METHOD__, $parArrObjPenRelTipoDocMapRecebidoDTO);
            $objPenRelTipoDocMapRecebidoBD = new PenRelTipoDocMapRecebidoBD($this->getObjInfraIBanco());

            foreach ($parArrObjPenRelTipoDocMapRecebidoDTO as $objPenRelTipoDocMapRecebidoDTO) {
                $objPenRelTipoDocMapRecebidoBD->excluir($objPenRelTipoDocMapRecebidoDTO);
            }
        }catch(Exception $e){
            throw new InfraException('Erro excluindo Mapeamento de Tipos de Documento para Recebimento.',$e);
        }
    }

    /**
     * Registra o mapeamento de esp�cies documentais para RECEBIMENTO com os Tipos de Documentos similares do SEI
     *
     * A an�lise de simularidade utiliza o algor�tmo para calcular a dist�ncia entre os dois nomes
     * Mais informa��es sobre o algor�tmo podem ser encontradas no link abaixo:
     * https://www.php.net/manual/pt_BR/function.similar-text.php
     *
     * @return void
     */
    protected function mapearEspeciesDocumentaisRecebimentoControlado()
    {
        $objTipoDocMapRN = new TipoDocMapRN();
        $objPenRelTipoDocMapRecebidoRN = new PenRelTipoDocMapRecebidoRN();

        //Persentual de similaridade m�nimo aceito para que a esp�cie documental possa ser automaticamente mapeada
        $numPercentualSimilaridadeValido = 85;

        $arrTiposDocumentos = $objTipoDocMapRN->listarParesSerie(null, true);

        // Obter todas as esp�cies documentais do Barramento de Servi�os do PEN
        // Antes separa as esp�cies com nomes separados por '/' em itens diferentes
        $arrEspeciesDocumentais = array();
        $arrEspecies = $objTipoDocMapRN->listarParesEspecie($objPenRelTipoDocMapRecebidoRN->listarEmUso(null));
        foreach ($arrEspecies as $numCodigo => $strItem) {
            foreach (preg_split('/\//', $strItem) as $strNomeEspecie) {
                $arrEspeciesDocumentais[] = array("codigo" => $numCodigo, "nome" => $strNomeEspecie);
            }
        }

        foreach ($arrEspeciesDocumentais as $objEspecieDocumental) {
            $numIdEspecieDocumental = $objEspecieDocumental["codigo"];
            $strNomeEspecieDocumental = $objEspecieDocumental["nome"];
            $numMelhorSimilaridade = null;
            $numIdTipDocumentoSimilar = null;

            foreach ($arrTiposDocumentos as $numIdTipoDocumento => $strNomeTipoDocumento) {
                $numSimilaridade = 0;
                $numTamNomeTipoDoc = strlen($strNomeTipoDocumento);
                $numTamNomeEspecie = strlen($strNomeEspecieDocumental);
                $numPosEspacoAdicional = strpos($strNomeTipoDocumento, ' ', min($numTamNomeEspecie, $numTamNomeTipoDoc));

                if($numPosEspacoAdicional){
                    // Avalia��o com tamanho reduzido, caso seja um termo composto
                    $numTamanhoReducao = max($numTamNomeEspecie, $numPosEspacoAdicional);
                    $strNomeTipoDocReduzido = substr($strNomeTipoDocumento, 0, $numTamanhoReducao);
                    similar_text(strtolower($strNomeEspecieDocumental), strtolower($strNomeTipoDocReduzido), $numSimilaridadeReduzido);
                    $numSimilaridade = $numSimilaridadeReduzido;
                } else {
                    // Avalia��o de termo em tamanho normal
                    similar_text(strtolower($strNomeEspecieDocumental), strtolower($strNomeTipoDocumento), $numSimilaridadeNormal);
                    $numSimilaridade = $numSimilaridadeNormal;
                }

                if($numMelhorSimilaridade < $numSimilaridade && $numSimilaridade > $numPercentualSimilaridadeValido) {
                    $numMelhorSimilaridade = $numSimilaridade;
                    $numIdTipDocumentoSimilar = $numIdTipoDocumento;
                }

            }

            if(isset($numMelhorSimilaridade)){
                // Realiza o mapeamento do tipo de documento com a esp�cie documental similar
                $objPenRelTipoDocMapRecebidoDTO = new PenRelTipoDocMapRecebidoDTO();
                $objPenRelTipoDocMapRecebidoDTO->setNumCodigoEspecie($numIdEspecieDocumental);
                if($objPenRelTipoDocMapRecebidoRN->contar($objPenRelTipoDocMapRecebidoDTO) == 0){
                    $objPenRelTipoDocMapRecebidoDTO->setNumIdSerie($numIdTipDocumentoSimilar);
                    $objPenRelTipoDocMapRecebidoRN->cadastrar($objPenRelTipoDocMapRecebidoDTO);
                }
            }
        }
    }

    /**
     * Recupera o tipo de mapeamento padr�o para recebimento de processos, verificando se o mesmo se encontra ativo
     *
     * @return num
     */
    protected function consultarTipoDocumentoPadraoConectado()
    {
        $objSerieDTO = null;
        $objPenParametro = new PenParametroRN();
        $strIdTipoDocumentoPadrao = $objPenParametro->getParametro("PEN_TIPO_DOCUMENTO_PADRAO_RECEBIMENTO");

        if(!empty($strIdTipoDocumentoPadrao)) {
            $objSerieDTO = new SerieDTO();
            $objSerieDTO->retNumIdSerie();
            $objSerieDTO->setNumIdSerie($strIdTipoDocumentoPadrao);
            $objSerieRN = new SerieRN();
            $objSerieDTO = $objSerieRN->consultarRN0644($objSerieDTO);
        }

        return isset($objSerieDTO) ? intval($objSerieDTO->getNumIdSerie()) : null;
    }

    /**
     * Atribui tipo de documento padr�o para recebimento de processos
     *
     * @return void
     */
    protected function atribuirTipoDocumentoPadraoControlado($numTipoDocumentoPadrao)
    {
        try{
            SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_tipo_doc_recebimento_padrao_atribuir', __METHOD__, $numTipoDocumentoPadrao);
            $objPenParametroRN = new PenParametroRN();
            $objPenParametroRN->persistirParametro("PEN_TIPO_DOCUMENTO_PADRAO_RECEBIMENTO", $numTipoDocumentoPadrao);
        }catch(Exception $e){
            throw new InfraException('Erro atribuindo Tipos de Documento padr�o para recebimento.',$e);
        }
    }

}
