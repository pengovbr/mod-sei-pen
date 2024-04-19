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
   * Método para inicializar o objeto de banco
   *
   * @return BancoSEI
   */
  protected function inicializarObjInfraIBanco()
  {
    return BancoSEI::getInstance();
  }

  /**
   * Método para listar hipotese legal
   *
   * @param PenHipoteseLegalDTO $objDTO
   * @return array
   * @throws InfraException
   */
  protected function listarConectado(PenHipoteseLegalDTO $objDTO)
  {
    try {
      //SessaoSEI::getInstance()->validarAuditarPermissao('email_sistema_excluir', __METHOD__, $arrObjEmailSistemaDTO);
      $objBD = new PenHipoteseLegalBD($this->inicializarObjInfraIBanco());
      return $objBD->listar($objDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro ao buscar lista de hipotese legal.', $e);
    }
  }

  /**
   * Método para consultar hipotese legal
   *
   * @param PenHipoteseLegalDTO $objDTO
   * @return PenHipoteseLegalDTO
   * @throws InfraException
   */
  protected function consultarConectado(PenHipoteseLegalDTO $objDTO)
  {
    try {
      $objBD = new PenHipoteseLegalBD($this->inicializarObjInfraIBanco());
      return $objBD->consultar($objDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro ao buscar hipotese legal', $e);
    }
  }
}
