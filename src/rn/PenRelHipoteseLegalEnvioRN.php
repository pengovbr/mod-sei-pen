<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Description of PenRelHipoteseLegalEnvioRN
 */
class PenRelHipoteseLegalEnvioRN extends PenRelHipoteseLegalRN
{

  protected function listarConectado(PenRelHipoteseLegalDTO $objDTO)
    {
      SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_hipotese_legal_envio_listar', __METHOD__, $objDTO);
      return parent::listarInterno($objDTO);
  }

  protected function consultarConectado(PenRelHipoteseLegalDTO $objDTO)
    {
      SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_hipotese_legal_envio_consultar', __METHOD__, $objDTO);
      return parent::consultarInterno($objDTO);
  }

  protected function alterarControlado(PenRelHipoteseLegalDTO $objDTO)
    {
      SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_hipotese_legal_envio_alterar', __METHOD__, $objDTO);
      return parent::alterarInterno($objDTO);
  }

  protected function cadastrarControlado(PenRelHipoteseLegalDTO $objDTO)
    {
      SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_hipotese_legal_envio_cadastrar', __METHOD__, $objDTO);
      return parent::cadastrarInterno($objDTO);
  }

  protected function excluirControlado(PenRelHipoteseLegalDTO $objDTO)
    {
      SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_hipotese_legal_envio_excluir', __METHOD__, $objDTO);
      return parent::excluirInterno($objDTO);
  }


    /**
     * Pega o ID hipotese sei para buscar o ID do barramento
     *
     * @param  integer $numIdHipoteseSEI
     * @return integer
     */
  protected function getIdHipoteseLegalPENConectado($numIdHipoteseSEI)
    {
      $objGenericoBD = new GenericoBD($this->inicializarObjInfraIBanco());

      // Mapeamento da hipotese legal remota
      $objPenRelHipoteseLegalDTO = new PenRelHipoteseLegalDTO();
      $objPenRelHipoteseLegalDTO->setStrTipo('E');
      $objPenRelHipoteseLegalDTO->retNumIdentificacao();
      $objPenRelHipoteseLegalDTO->setNumIdHipoteseLegal($numIdHipoteseSEI);

      $objPenRelHipoteseLegal = $objGenericoBD->consultar($objPenRelHipoteseLegalDTO);

    if ($objPenRelHipoteseLegal) {
        return $objPenRelHipoteseLegal->getNumIdentificacao();
    } else {
        return null;
    }
  }

    /**
     * Contar HipoteseLegal
     *
     * @return int
     * @throws InfraException
     */
  protected function contarConectado(PenRelHipoteseLegalDTO $objDTO)
    {
    try {
        $objGenericoBD = new GenericoBD($this->inicializarObjInfraIBanco());
        return $objGenericoBD->contar($objDTO);
    }
    catch(Exception $e){
        throw new InfraException('Módulo do Tramita: Erro contando HipoteseLegal.', $e);
    }
  }
}
