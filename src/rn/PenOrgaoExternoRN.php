<?php

require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Description of PenOrgaoExternoRN
 */
class PenOrgaoExternoRN extends InfraRN
{

    /**
     * Inicializa o obj do banco da Infra
     *
     * @return obj
     */
  protected function inicializarObjInfraIBanco()
    {
      return BancoSEI::getInstance();
  }

    /**
     * M�todo utilizado para listagem de dados.
     *
     * @return array
     * @throws InfraException
     */
  protected function listarConectado(PenOrgaoExternoDTO $objDTO)
    {
    try {
        //Valida Permissao
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_orgaos_externos_listar', __METHOD__, $objDTO);
        $objBD = new PenOrgaoExternoBD($this->getObjInfraIBanco());
        return $objBD->listar($objDTO);
    } catch (Exception $e) {
        throw new InfraException('M�dulo do Tramita: Erro listando org�os externos.', $e);
    }
  }

    /**
     * M�todo utilizado para consulta de dados.
     *
     * @return PenOrgaoExternoDTO
     * @throws InfraException
     */
  protected function consultarConectado(PenOrgaoExternoDTO $objDTO)
    {
    try {
        //Valida Permissao
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_orgaos_externos_visualizar', __METHOD__, $objDTO);
        $objBD = new PenOrgaoExternoBD($this->inicializarObjInfraIBanco());
        return $objBD->consultar($objDTO);
    } catch (Exception $e) {
        throw new InfraException('M�dulo do Tramita: Erro consultando mapeamento de unidades.', $e);
    }
  }

    /**
     * M�todo utilizado para altera��o de dados.
     *
     * @return PenOrgaoExternoDTO
     * @throws InfraException
     */
  protected function alterarControlado(PenOrgaoExternoDTO $objDTO)
    {
    try {
        //Valida Permissao
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_orgaos_externos_salvar', __METHOD__, $objDTO);
        $objBD = new PenOrgaoExternoBD($this->inicializarObjInfraIBanco());
        return $objBD->alterar($objDTO);
    } catch (Exception $e) {
        throw new InfraException('M�dulo do Tramita: Erro alterando mapeamento de unidades.', $e);
    }
  }

    /**
     * M�todo utilizado para cadastro de dados.
     *
     * @return array
     * @throws InfraException
     */
  protected function cadastrarConectado(PenOrgaoExternoDTO $objDTO)
    {
    try {
        //Valida Permissao
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_orgaos_externos_salvar', __METHOD__, $objDTO);
        $objBD = new PenOrgaoExternoBD($this->inicializarObjInfraIBanco());
        return $objBD->cadastrar($objDTO);
    } catch (Exception $e) {
        throw new InfraException('M�dulo do Tramita: Erro cadastrando mapeamento de unidades.', $e);
    }
  }

    /**
     * M�todo utilizado para exclus�o de dados.
     *
     * @return array
     * @throws InfraException
     */
  protected function excluirControlado(PenOrgaoExternoDTO $objDTO)
    {
    try {
        //Valida Permissao
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_orgaos_externos_excluir', __METHOD__, $objDTO);
        $objBD = new PenOrgaoExternoBD($this->inicializarObjInfraIBanco());
        return $objBD->excluir($objDTO);
    } catch (Exception $e) {
        throw new InfraException('M�dulo do Tramita: Erro excluindo mapeamento de unidades.', $e);
    }
  }

    /**
     * M�todo utilizado para contagem de unidades mapeadas
     *
     * @return array
     * @throws InfraException
     */
  protected function contarConectado(PenOrgaoExternoDTO $objDTO)
    {
    try {
        //Valida Permissao
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_orgaos_externos_listar', __METHOD__, $objDTO);
        $objBD = new PenOrgaoExternoBD($this->getObjInfraIBanco());
        return $objBD->contar($objDTO);
    } catch (Exception $e) {
        throw new InfraException('M�dulo do Tramita: Erro contando mapeamento de unidades.', $e);
    }
  }
}
