<?php

require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Regra de negócio para o parâmetros do módulo PEN
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
        throw new InfraException('Módulo do Tramita: Erro consultando mapeamento de documentos para envio.', $e);
    }
  }

  protected function consultarConectado(EspecieDocumentalDTO $objEspecieDocumentalDTO)
    {
    try {
        $objEspecieDocumentalBD = new EspecieDocumentalBD($this->getObjInfraIBanco());
        return $objEspecieDocumentalBD->consultar($objEspecieDocumentalDTO);
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro consultando mapeamento de documentos para envio.', $e);
    }
  }

  protected function excluirConectado(EspecieDocumentalDTO $objEspecieDocumentalDTO)
    {
    try {
        $objEspecieDocumentalBD = new EspecieDocumentalBD($this->getObjInfraIBanco());
        return $objEspecieDocumentalBD->excluir($objEspecieDocumentalDTO);
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro consultando mapeamento de documentos para envio.', $e);
    }
  }

  public function verificarEspecieOutra() {

    $objEspecieDocumentalDTO = new EspecieDocumentalDTO();
    $objEspecieDocumentalDTO->setStrNomeEspecie('Outra');
    $objEspecieDocumentalDTO->retStrNomeEspecie();
    $objEspecieDocumentalDTO->retDblIdEspecie();

    $objEspecieDocumentalRN = new EspecieDocumentalRN();
    $objEspecieDocumentalDTO = $objEspecieDocumentalRN->consultar($objEspecieDocumentalDTO);

    return $objEspecieDocumentalDTO; 
  }
}
