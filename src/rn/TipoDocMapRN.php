<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Reposit�rio para m�todos para Tipo de Documento Mapeados pelo m�dulo PEN
 *
 *
 */
class TipoDocMapRN extends InfraRN {

  protected function inicializarObjInfraIBanco() {
      return BancoSEI::getInstance();
  }

    /**
     * Retorna um array de chave => valor, onde a chave � a ID e o valor � a descri��o
     * do registro no banco de dados
     *
     * @param bool $bolRemoverUtilizados remover os que j� tem alguma rela��o
     * @param int $dblCodigoEspecie C�digo do Tipo de Documento. S� funciona em
     * conjunto com o primeiro param�tro
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
        throw $e;
    }
  }

    /**
     * Retorna um array de chave => valor, onde a chave � a ID e o valor � a descri��o
     * do registro no banco de dados.
     * Utilizado na View, ent�o processa as exception na p�gina de erro
     *
     * @param int $dblIdSerie C�digo selecionado da Esp�cie Documental.
     * S� funciona em conjunto com o primeiro param�tro
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
        throw $e;
    }
  }

}
