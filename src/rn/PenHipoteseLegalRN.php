<?php

require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Description of PenHipoteseLegalRN
 *
 * @author michael
 */
class PenHipoteseLegalRN extends InfraRN
{

    /**
     * Inicializador de banco de dados
     *
     * @return object
     */
  protected function inicializarObjInfraIBanco()
    {
      return BancoSEI::getInstance();
  }

    /**
     * Listar hipoteses legais
     *
     * @return array
     * @throws InfraException
     */
  protected function listarConectado(PenHipoteseLegalDTO $objDTO)
    {
    try {
        $objBD = new GenericoBD($this->inicializarObjInfraIBanco());
        return $objBD->listar($objDTO);
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro listando hipotese legal.', $e);
    }
  }

  protected function consultarConectado(PenHipoteseLegalDTO $objDTO)
    {
    try {
        //Valida Permissao
        $objBD = new GenericoBD($this->inicializarObjInfraIBanco());
        return $objBD->consultar($objDTO);
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro ao consultar Hipotese Legal.', $e);
    }
  }
}
