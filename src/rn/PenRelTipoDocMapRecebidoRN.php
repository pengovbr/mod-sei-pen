<?php

require_once DIR_SEI_WEB.'/SEI.php';

class PenRelTipoDocMapRecebidoRN extends InfraRN
{
  public function __construct()
    {
      parent::__construct();
  }

  protected function inicializarObjInfraIBanco()
    {
      return BancoSEI::getInstance();
  }

  protected function listarEmUsoConectado($dblCodigoEspecie)
    {
      $arrNumCodigoEspecie = [];

      $objDTO = new PenRelTipoDocMapRecebidoDTO();
      $objDTO->retNumCodigoEspecie();
      $objDTO->setDistinct(true);
      $objDTO->setBolExclusaoLogica(false);

      $objGenericoBD = new GenericoBD($this->inicializarObjInfraIBanco());
      $arrObjPenRelTipoDocMapRecebidoDTO = $objGenericoBD->listar($objDTO);

    if(!empty($arrObjPenRelTipoDocMapRecebidoDTO)) {
      foreach($arrObjPenRelTipoDocMapRecebidoDTO as $objDTO) {
        $arrNumCodigoEspecie[] = $objDTO->getNumCodigoEspecie();
      }
    }

    if(!is_null($dblCodigoEspecie) && $dblCodigoEspecie > 0) {
        // Tira da lista de ignorados o que foi selecionado, em caso de edição
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
     * @param  PenRelTipoDocMapRecebidoDTO $parObjPenRelTipoDocMapRecebidoDTO
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
     * @return void
     */
  protected function consultarConectado(PenRelTipoDocMapRecebidoDTO $parObjPenRelTipoDocMapRecebidoDTO)
    {
    try {
        $objPenRelTipoDocMapRecebidoBD = new PenRelTipoDocMapEnviadoBD($this->getObjInfraIBanco());
        return $objPenRelTipoDocMapRecebidoBD->consultar($parObjPenRelTipoDocMapRecebidoDTO);
    }catch(Exception $e){
        throw new InfraException('Módulo do Tramita: Erro consultando mapeamento de documentos para recebimento.', $e);
    }
  }


    /**
     * Remove uma espécie documental da base de dados do SEI baseado em um código de espécie do Barramento
     *
     * @param  int $parNumIdEspecieDocumentla
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
        throw new InfraException('Módulo do Tramita: Erro removendo Mapeamento de Tipos de Documento para recebimento pelo código de espécie.', $e);
    }
  }

    /**
     * Lista mapeamentos de tipos de documentos para recebimento de processos pelo Barramento PEN
     *
     * @return array
     */
  protected function listarConectado(PenRelTipoDocMapRecebidoDTO $parObjPenRelTipoDocMapRecebidoDTO)
    {
    try {
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_tipo_documento_recebimento_listar', __METHOD__, $parObjPenRelTipoDocMapRecebidoDTO);
        $objPenRelTipoDocMapRecebidoBD = new PenRelTipoDocMapRecebidoBD($this->getObjInfraIBanco());
        return $objPenRelTipoDocMapRecebidoBD->listar($parObjPenRelTipoDocMapRecebidoDTO);
    }catch(Exception $e){
        throw new InfraException('Módulo do Tramita: Erro listando mapeamento de Tipos de Documento para recebimento.', $e);
    }
  }

      /**
     * Lista mapeamentos de tipos de documentos para recebimento de processos pelo Barramento PEN
     *
     * @param PenRelTipoDocMapRecebidoDTO $parObjPenRelTipoDocMapRecebidoDTO
     * @return array
     */
  protected function alterarConectado(PenRelTipoDocMapRecebidoDTO $parObjPenRelTipoDocMapRecebidoDTO)
    {
    try {
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_tipo_documento_recebimento_listar', __METHOD__, $parObjPenRelTipoDocMapRecebidoDTO);
        $objPenRelTipoDocMapRecebidoBD = new PenRelTipoDocMapRecebidoBD($this->getObjInfraIBanco());
        return $objPenRelTipoDocMapRecebidoBD->alterar($parObjPenRelTipoDocMapRecebidoDTO);
    }catch(Exception $e){
        throw new InfraException('Módulo do Tramita: Erro listando mapeamento de Tipos de Documento para recebimento.', $e);
    }
  }

    /**
     * Conta a lista de mapeamentos de tipos de documentos para envio de processos pelo Barramento PEN
     *
     * @return int
     */
  protected function contarConectado(PenRelTipoDocMapRecebidoDTO $parObjPenRelTipoDocMapRecebidoDTO)
    {
    try {
        $objPenRelTipoDocMapRecebidoBD = new PenRelTipoDocMapRecebidoBD($this->getObjInfraIBanco());
        return $objPenRelTipoDocMapRecebidoBD->contar($parObjPenRelTipoDocMapRecebidoDTO);
    }catch(Exception $e){
        throw new InfraException('Módulo do Tramita: Erro contando Mapeamento de Tipos de Documento para Recebimento.', $e);
    }
  }

    /**
     * Exclui lista de mapeamentos de tipos de documentos para envio de processos pelo Barramento PEN
     *
     * @param  PenRelTipoDocMapRecebidoDTO $parObjPenRelTipoDocMapRecebidoDTO
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
        throw new InfraException('Módulo do Tramita: Erro excluindo Mapeamento de Tipos de Documento para Recebimento.', $e);
    }
  }

    /**
     * Registra o mapeamento de espécies documentais para RECEBIMENTO com os Tipos de Documentos similares do SEI
     *
     * A análise de simularidade utiliza o algorítmo para calcular a distãncia entre os dois nomes
     * Mais informações sobre o algorítmo podem ser encontradas no link abaixo:
     * https://www.php.net/manual/pt_BR/function.similar-text.php
     *
     * @return void
     */
  protected function mapearEspeciesDocumentaisRecebimentoControlado()
    {
      $objTipoDocMapRN = new TipoDocMapRN();

      //Persentual de similaridade mínimo aceito para que a espécie documental possa ser automaticamente mapeada
      $numPercentualSimilaridadeValido = 85;

      $arrTiposDocumentos = $objTipoDocMapRN->listarParesSerie(null, true);

      // Obter todas as espécies documentais do Barramento de Serviços do PEN
      // Antes separa as espécies com nomes separados por '/' em itens diferentes
      $arrEspeciesDocumentais = [];
      $arrEspecies = $objTipoDocMapRN->listarParesEspecie($this->listarEmUso(null));
    foreach ($arrEspecies as $numCodigo => $strItem) {
      foreach (preg_split('/\//', $strItem) as $strNomeEspecie) {
        $arrEspeciesDocumentais[] = ["codigo" => $numCodigo, "nome" => $strNomeEspecie];
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

        if($numPosEspacoAdicional) {
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
            $numIdTipDocumentoSimilar = $numIdTipoDocumento;
        }

      }

      if(isset($numMelhorSimilaridade)) {
          // Realiza o mapeamento do tipo de documento com a espécie documental similar
          $objPenRelTipoDocMapRecebidoDTO = new PenRelTipoDocMapRecebidoDTO();
          $objPenRelTipoDocMapRecebidoDTO->setNumCodigoEspecie($numIdEspecieDocumental);
        if($this->contar($objPenRelTipoDocMapRecebidoDTO) == 0) {
            $objPenRelTipoDocMapRecebidoDTO->setNumIdSerie($numIdTipDocumentoSimilar);
            $this->cadastrar($objPenRelTipoDocMapRecebidoDTO);
        }
      }
    }
  }

    /**
     * Recupera o tipo de mapeamento padrão para recebimento de processos, verificando se o mesmo se encontra ativo
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
     * Atribui tipo de documento padrão para recebimento de processos
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
        throw new InfraException('Módulo do Tramita: Erro atribuindo Tipos de Documento padrão para recebimento.', $e);
    }
  }

  protected function obterSerieMapeadaConectado($numCodigoEspecie)
    {
      $objSerieDTO = null;
      $objMapDTO = new PenRelTipoDocMapRecebidoDTO();
      $objMapDTO->setNumCodigoEspecie($numCodigoEspecie);
      $objMapDTO->retNumIdSerie();

      // Busca mapeamento de tipos de documento definido pelo
      $objGenericoBD = new GenericoBD($this->getObjInfraIBanco());
      $objMapDTO = $objGenericoBD->consultar($objMapDTO);

      $numIdSerieMapeada = (isset($objMapDTO)) ? $objMapDTO->getNumIdSerie() : $this->consultarTipoDocumentoPadrao();
    if(!empty($numIdSerieMapeada)) {
        $objSerieDTO = new SerieDTO();
        $objSerieDTO->retStrNome();
        $objSerieDTO->retNumIdSerie();
        $objSerieDTO->setNumIdSerie($numIdSerieMapeada);
        $objSerieRN = new SerieRN();
        $objSerieDTO = $objSerieRN->consultarRN0644($objSerieDTO);
    }

      return $objSerieDTO;
  }

}
