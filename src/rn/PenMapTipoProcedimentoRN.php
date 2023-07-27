<?php

require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Description of PenOrgaoExternoRN
 */
class PenMapTipoProcedimentoRN extends InfraRN
{

  /**
   * Inicializa o obj do banco da Infra
   * @return obj
   */
  protected function inicializarObjInfraIBanco()
  {
    return BancoSEI::getInstance();
  }

  /**
   * Método utilizado para listagem de dados.
   * @param PenMapTipoProcedimentoDTO $objDTO
   * @return array
   * @throws InfraException
   */
  protected function listarConectado(PenMapTipoProcedimentoDTO $objDTO)
  {
    try {
      $objBD = new PenMapTipoProcedimentoBD($this->getObjInfraIBanco());
      return $objBD->listar($objDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro listando mapeamento externos.', $e);
    }
  }

  /**
   * Método utilizado para alteração de dados.
   * @param PenMapTipoProcedimentoDTO $objDTO
   * @return array
   * @throws InfraException
   */
  protected function alterarControlado(PenMapTipoProcedimentoDTO $objDTO)
  {
    try {
      $objBD = new PenMapTipoProcedimentoBD(BancoSEI::getInstance());
      return $objBD->alterar($objDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro alterando mapeamento de procedimento.', $e);
    }
  }

  /**
   * Método utilizado para cadastro de dados.
   * @param PenMapTipoProcedimentoDTO $objDTO
   * @return array
   * @throws InfraException
   */
  protected function cadastrarConectado(PenMapTipoProcedimentoDTO $objDTO)
  {
    try {
      $objBD = new PenMapTipoProcedimentoBD(BancoSEI::getInstance());
      return $objBD->cadastrar($objDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro cadastrando mapeamento de procedimento.', $e);
    }
  }

  /**
   * Método utilizado para exclusão de dados.
   * @param PenMapTipoProcedimentoDTO $objDTO
   * @return array
   * @throws InfraException
   */
  protected function excluirControlado(PenMapTipoProcedimentoDTO $objDTO)
  {
    try {
      $objBD = new PenMapTipoProcedimentoBD(BancoSEI::getInstance());
      return $objBD->excluir($objDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro excluindo mapeamento de procedimento.', $e);
    }
  }

  /**
   * Método utilizado para contagem de procedimento mapeadas
   * @param PenMapTipoProcedimentoDTO $objDTO
   * @return array
   * @throws InfraException
   */
  protected function contarConectado(PenMapTipoProcedimentoDTO $objDTO)
  {
    try {
      $objBD = new PenMapTipoProcedimentoBD($this->getObjInfraIBanco());
      return $objBD->contar($objDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro contando mapeamento de procedimento.', $e);
    }
  }

  public function importarTipoProcedimentoControlado(array $objDTO)
  {
    try {
      $objBD = new PenMapTipoProcedimentoBD($this->getObjInfraIBanco());
      foreach ($objDTO as $procedimentoDTO) {

      }
    } catch (Exception $e) {
        throw new InfraException('Erro importando mapeamento de procedimento.', $e);
    }
  }
}
