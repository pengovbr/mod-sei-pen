<?php

require_once DIR_SEI_WEB.'/SEI.php';

class PenRelTipoDocMapEnviadoRN extends InfraRN
{
  public function __construct() {
      parent::__construct();
  }

  protected function inicializarObjInfraIBanco() {
      return BancoSEI::getInstance();
  }


    /**
     * Lista mapeamentos de tipos de documentos para envio de processos pelo Barramento PEN
     *
     * @param PenRelTipoDocMapEnviadoDTO $parObjPenRelTipoDocMapEnviadoDTO
     * @return array
     */
  protected function listarConectado(PenRelTipoDocMapEnviadoDTO $parObjPenRelTipoDocMapEnviadoDTO)
    {
    try {
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_tipo_documento_envio_listar', __METHOD__, $parObjPenRelTipoDocMapEnviadoDTO);
        $objPenRelTipoDocMapEnviadoBD = new PenRelTipoDocMapEnviadoBD($this->getObjInfraIBanco());
        return $objPenRelTipoDocMapEnviadoBD->listar($parObjPenRelTipoDocMapEnviadoDTO);
    }catch(Exception $e){
        throw new InfraException('Erro listando mapeamento de Tipos de Documento para envio.', $e);
    }
  }

  protected function consultarConectado(PenRelTipoDocMapEnviadoDTO $objPenRelTipoDocMapEnviadoDTO)
    {
    try {
        $objPenRelTipoDocMapEnviadoBD = new PenRelTipoDocMapEnviadoBD($this->getObjInfraIBanco());
        return $objPenRelTipoDocMapEnviadoBD->consultar($objPenRelTipoDocMapEnviadoDTO);
    }catch(Exception $e){
        throw new InfraException('Erro consultando mapeamento de documentos para envio.', $e);
    }
  }


  protected function listarEmUsoConectado($dblIdSerie)
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

    if (!is_null($dblIdSerie) && $dblIdSerie > 0) {
        // Tira da lista de ignorados o que foi selecionado, em caso de edi��o
        $numIndice = array_search($dblIdSerie, $arrNumIdSerie);
      if ($numIndice !== false) {
          unset($arrNumIdSerie[$numIndice]);
      }
    }

      return $arrNumIdSerie;
  }

  public function cadastrarConectado(PenRelTipoDocMapEnviadoDTO $objParamDTO)
    {
      $objBD = new GenericoBD($this->inicializarObjInfraIBanco());

    if($objParamDTO->isSetDblIdMap()) {
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_tipo_documento_envio_alterar', __METHOD__, $objParamDTO);
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
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_tipo_documento_envio_cadastrar', __METHOD__, $objParamDTO);
        $objDTO = new PenRelTipoDocMapEnviadoDTO();
        $objDTO->setNumCodigoEspecie($objParamDTO->getNumCodigoEspecie());
        $objDTO->setNumIdSerie($objParamDTO->getNumIdSerie());
        $objBD->cadastrar($objDTO);
    }
  }

    /**
     * Exclui lista de mapeamentos de tipos de documentos para envio de processos pelo Barramento PEN
     *
     * @param PenRelTipoDocMapEnviadoDTO $parObjPenRelTipoDocMapEnviadoDTO
     * @return void
     */
  protected function excluirControlado($parArrObjPenRelTipoDocMapEnviadoDTO)
    {
    try {
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_tipo_documento_envio_excluir', __METHOD__, $parArrObjPenRelTipoDocMapEnviadoDTO);
        $objPenRelTipoDocMapEnviadoBD = new PenRelTipoDocMapEnviadoBD($this->getObjInfraIBanco());

      foreach ($parArrObjPenRelTipoDocMapEnviadoDTO as $IdMap) {
        $objPenRelTipoDocMapEnviadoDTO = new PenRelTipoDocMapEnviadoDTO();
        $objPenRelTipoDocMapEnviadoDTO->setDblIdMap($IdMap);
        $objPenRelTipoDocMapEnviadoBD->excluir($objPenRelTipoDocMapEnviadoDTO);
      }
    }catch(Exception $e){
        throw new InfraException('Erro excluindo Mapeamento de Tipos de Documento para envio.', $e);
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
        $objPenRelTipoDocMapEnviadoBD = new PenRelTipoDocMapEnviadoBD($this->getObjInfraIBanco());
        $objPenRelTipoDocMapEnviadoDTO = new PenRelTipoDocMapEnviadoDTO();
        $objPenRelTipoDocMapEnviadoDTO->setNumCodigoEspecie($parNumIdEspecieDocumental);
        $objPenRelTipoDocMapEnviadoDTO->retDblIdMap();

      foreach ($objPenRelTipoDocMapEnviadoBD->listar($objPenRelTipoDocMapEnviadoDTO) as $objDTO) {
        $objPenRelTipoDocMapEnviadoBD->excluir($objDTO);
      }

    }catch(Exception $e){
      throw new InfraException('Erro removendo Mapeamento de Tipos de Documento para envio pelo c�digo de esp�cie.', $e);
    }
  }

  protected function contarConectado(PenRelTipoDocMapEnviadoDTO $parObjPenRelTipoDocMapEnviadoDTO)
    {
    try {
      $objPenRelTipoDocMapEnviadoBD = new PenRelTipoDocMapEnviadoBD($this->getObjInfraIBanco());
      return $objPenRelTipoDocMapEnviadoBD->contar($parObjPenRelTipoDocMapEnviadoDTO);
    }catch(Exception $e){
      throw new InfraException('Erro contando Mapeamento de Tipos de Documento para Envio.', $e);
    }
  }

    /**
     * Registra o mapeamento de Tipos de Documentos para ENVIO com as esp�cies documentais similares do Barramento do PEN
     *
     * A an�lise de simularidade utiliza o algor�tmo para calcular a dist�ncia entre os dois nomes
     * Mais informa��es sobre o algor�tmo podem ser encontradas no link abaixo:
     * https://www.php.net/manual/pt_BR/function.similar-text.php
     *
     * @return void
     */
  protected function mapearEspeciesDocumentaisEnvioControlado()
    {
      $objTipoDocMapRN = new TipoDocMapRN();
      $objPenRelTipoDocMapEnviadoRN = new PenRelTipoDocMapEnviadoRN();

      //Persentual de similaridade m�nimo aceito para que a esp�cie documental possa ser automaticamente mapeada
      $numPercentualSimilaridadeValido = 85;

      // Obter todas as esp�cies documentais do Barramento de Servi�os do PEN
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
            $numIdEspecieSimilar = $numIdEspecieDocumental;
        }
      }

      if(isset($numMelhorSimilaridade)){
          // Realiza o mapeamento do tipo de documento com a esp�cie documental similar
          $objPenRelTipoDocMapEnviadoDTO = new PenRelTipoDocMapEnviadoDTO();
          $objPenRelTipoDocMapEnviadoDTO->setNumIdSerie($numIdTipoDocumento);
        if($objPenRelTipoDocMapEnviadoRN->contar($objPenRelTipoDocMapEnviadoDTO) == 0){
            $objPenRelTipoDocMapEnviadoDTO->setNumCodigoEspecie($numIdEspecieSimilar);
            $objPenRelTipoDocMapEnviadoRN->cadastrar($objPenRelTipoDocMapEnviadoDTO);
        }
      }
    }
  }

    /**
     * Recupera esp�cie documental padr�o para envio de processos, verificando se o mesmo se encontra ativo
     *
     * @return num
     */
  protected function consultarEspeciePadraoConectado()
    {
      $objEspecieDocumentalDTO = null;
      $objPenParametro = new PenParametroRN();
      $strIdEspeciePadrao = $objPenParametro->getParametro("PEN_ESPECIE_DOCUMENTAL_PADRAO_ENVIO");

    if(!empty($strIdEspeciePadrao)) {
        $objEspecieDocumentalDTO = new EspecieDocumentalDTO();
        $objEspecieDocumentalDTO->retDblIdEspecie();
        $objEspecieDocumentalDTO->setDblIdEspecie($strIdEspeciePadrao);

        $objGenericoBD = new GenericoBD(BancoSEI::getInstance());
        $objEspecieDocumentalDTO = $objGenericoBD->consultar($objEspecieDocumentalDTO);
    }

      return isset($objEspecieDocumentalDTO) ? intval($objEspecieDocumentalDTO->getDblIdEspecie()) : null;
  }

    /**
     * Atribui especie documental padr�o para envio de processos
     *
     * @return void
     */
  protected function atribuirEspeciePadraoControlado($parNumEspeciePadrao)
    {
    try{
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_tipo_documento_envio_padrao_atribuir', __METHOD__, $parNumEspeciePadrao);
        $objPenParametroRN = new PenParametroRN();
        $objPenParametroRN->persistirParametro("PEN_ESPECIE_DOCUMENTAL_PADRAO_ENVIO", $parNumEspeciePadrao);
    }catch(Exception $e){
        throw new InfraException('Erro atribuindo Esp�cie Documental padr�o para envio.', $e);
    }
  }

}
