<?php

require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Description of PenUnidadeEnvioRN
 */
class PenRestricaoEnvioComponentesDigitaisRN extends InfraRN
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
   * M�todo utilizado para listagem de dados.
   * @param PenRestricaoEnvioComponentesDigitaisDTO $objDTO
   * @return array
   * @throws InfraException
   */
  protected function listarConectado(PenRestricaoEnvioComponentesDigitaisDTO $objDTO)
  {
    try {
      //Valida Permissao
      SessaoSEI::getInstance()->validarAuditarPermissao(
        'pen_map_envio_parcial_listar',
        __METHOD__,
        $objDTO
      );
      $objBD = new PenRestricaoEnvioComponentesDigitaisBD($this->getObjInfraIBanco());
      return $objBD->listar($objDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro listando Unidades.', $e);
    }
  }

  /**
   * M�todo utilizado para consulta de dados.
   * @param PenRestricaoEnvioComponentesDigitaisDTO $objDTO
   * @return array
   * @throws InfraException
   */
  protected function consultarControlado(PenRestricaoEnvioComponentesDigitaisDTO $objDTO)
  {
    try {
      //Valida Permissao
      SessaoSEI::getInstance()->validarAuditarPermissao(
        'pen_map_envio_parcial_visualizar',
        __METHOD__,
        $objDTO
      );
      $objBD = new PenRestricaoEnvioComponentesDigitaisBD(BancoSEI::getInstance());
      return $objBD->consultar($objDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro consultar mapeamento de unidades.', $e);
    }
  }

  /**
   * M�todo utilizado para altera��o de dados.
   * @param PenRestricaoEnvioComponentesDigitaisDTO $objDTO
   * @return array
   * @throws InfraException
   */
  protected function alterarControlado(PenRestricaoEnvioComponentesDigitaisDTO $objDTO)
  {
    try {
      //Valida Permissao
      SessaoSEI::getInstance()->validarAuditarPermissao(
        'pen_map_envio_parcial_atualizar',
        __METHOD__,
        $objDTO
      );
      $objBD = new PenRestricaoEnvioComponentesDigitaisBD(BancoSEI::getInstance());
      return $objBD->alterar($objDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro alterando mapeamento de unidades.', $e);
    }
  }

  /**
   * M�todo utilizado para cadastro de dados.
   * @param PenRestricaoEnvioComponentesDigitaisDTO $objDTO
   * @return array
   * @throws InfraException
   */
  protected function cadastrarConectado(PenRestricaoEnvioComponentesDigitaisDTO $objDTO)
  {
    try {
      //Valida Permissao
      SessaoSEI::getInstance()->validarAuditarPermissao(
        'pen_map_envio_parcial_salvar',
        __METHOD__,
        $objDTO
      );
      $objBD = new PenRestricaoEnvioComponentesDigitaisBD(BancoSEI::getInstance());
      return $objBD->cadastrar($objDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro cadastrando mapeamento de unidades.', $e);
    }
  }

  /**
   * M�todo utilizado para exclus�o de dados.
   * @param PenRestricaoEnvioComponentesDigitaisDTO $objDTO
   * @return array
   * @throws InfraException
   */
  protected function excluirControlado(PenRestricaoEnvioComponentesDigitaisDTO $objDTO)
  {
    try {
      //Valida Permissao
      SessaoSEI::getInstance()->validarAuditarPermissao(
        'pen_map_envio_parcial_excluir',
        __METHOD__,
        $objDTO
      );
      $objBD = new PenRestricaoEnvioComponentesDigitaisBD(BancoSEI::getInstance());
      return $objBD->excluir($objDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro excluindo mapeamento de unidades.', $e);
    }
  }

  /**
   * M�todo utilizado para contagem de unidades mapeadas
   * @param PenRestricaoEnvioComponentesDigitaisDTO $objUnidadeDTO
   * @return array
   * @throws InfraException
   */
  protected function contarConectado(PenRestricaoEnvioComponentesDigitaisDTO $objDTO)
  {
    try {
      //Valida Permissao
      $objBD = new PenRestricaoEnvioComponentesDigitaisBD($this->getObjInfraIBanco());
      return $objBD->contar($objDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro contando mapeamento de unidades.', $e);
    }
  }
}
