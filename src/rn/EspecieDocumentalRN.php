<?php

require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Regra de neg�cio para o par�metros do m�dulo PEN
 */
class EspecieDocumentalRN extends InfraRN
{

  public function __construct()
    {
      parent::__construct();
  }

  protected function inicializarObjInfraIBanco()
    {
      return BancoSEI::getInstance();
  }

  protected function cadastrarConectado(EspecieDocumentalDTO $objEspecieDocumentalDTO)
    {
    try {
        $objEspecieDocumentalBD = new EspecieDocumentalBD($this->getObjInfraIBanco());
        return $objEspecieDocumentalBD->cadastrar($objEspecieDocumentalDTO);
    } catch (Exception $e) {
        throw new InfraException('M�dulo do Tramita: Erro consultando mapeamento de documentos para envio.', $e);
    }
  }

  protected function consultarConectado(EspecieDocumentalDTO $objEspecieDocumentalDTO)
    {
    try {
        $objEspecieDocumentalBD = new EspecieDocumentalBD($this->getObjInfraIBanco());
        return $objEspecieDocumentalBD->consultar($objEspecieDocumentalDTO);
    } catch (Exception $e) {
        throw new InfraException('M�dulo do Tramita: Erro consultando mapeamento de documentos para envio.', $e);
    }
  }

  protected function excluirConectado(EspecieDocumentalDTO $objEspecieDocumentalDTO)
    {
    try {
        $objEspecieDocumentalBD = new EspecieDocumentalBD($this->getObjInfraIBanco());
        return $objEspecieDocumentalBD->excluir($objEspecieDocumentalDTO);
    } catch (Exception $e) {
        throw new InfraException('M�dulo do Tramita: Erro consultando mapeamento de documentos para envio.', $e);
    }
  }
}
