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
   * @param PenMapTipoProcedimentoDTO $objPenMapTipoProcedimentoDTO
   * @return array
   * @throws InfraException
   */
  protected function listarConectado(PenMapTipoProcedimentoDTO $objPenMapTipoProcedimentoDTO)
  {
    try {
      $objPenMapTipoProcedimentoBD = new PenMapTipoProcedimentoBD($this->getObjInfraIBanco());
      return $objPenMapTipoProcedimentoBD->listar($objPenMapTipoProcedimentoDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro listando mapeamento externos.', $e);
    }
  }

    /**
   * Método utilizado para listagem de dados.
   * @param PenMapTipoProcedimentoDTO $objPenMapTipoProcedimentoDTO
   * @return array
   * @throws InfraException
   */
  protected function consultarConectado(PenMapTipoProcedimentoDTO $objPenMapTipoProcedimentoDTO)
  {
    try {
      $objPenMapTipoProcedimentoBD = new PenMapTipoProcedimentoBD($this->getObjInfraIBanco());
      return $objPenMapTipoProcedimentoBD->consultar($objPenMapTipoProcedimentoDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro listando mapeamento externos.', $e);
    }
  }

  /**
   * Método utilizado para alteração de dados.
   * @param PenMapTipoProcedimentoDTO $objPenMapTipoProcedimentoDTO
   * @return array
   * @throws InfraException
   */
  protected function alterarControlado(PenMapTipoProcedimentoDTO $objPenMapTipoProcedimentoDTO)
  {
    try {
      $objPenMapTipoProcedimentoBD = new PenMapTipoProcedimentoBD(BancoSEI::getInstance());
      return $objPenMapTipoProcedimentoBD->alterar($objPenMapTipoProcedimentoDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro alterando mapeamento de procedimento.', $e);
    }
  }

  /**
   * Método utilizado para cadastro de dados.
   * @param PenMapTipoProcedimentoDTO $objPenMapTipoProcedimentoDTO
   * @return array
   * @throws InfraException
   */
  protected function cadastrarConectado(PenMapTipoProcedimentoDTO $objPenMapTipoProcedimentoDTO)
  {
    try {
      $objPenMapTipoProcedimentoBD = new PenMapTipoProcedimentoBD(BancoSEI::getInstance());
      return $objPenMapTipoProcedimentoBD->cadastrar($objPenMapTipoProcedimentoDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro cadastrando mapeamento de procedimento.', $e);
    }
  }

  /**
   * Método utilizado para exclusão de dados.
   * @param PenMapTipoProcedimentoDTO $objPenMapTipoProcedimentoDTO
   * @return array
   * @throws InfraException
   */
  protected function excluirControlado(PenMapTipoProcedimentoDTO $objPenMapTipoProcedimentoDTO)
  {
    try {
      $objPenMapTipoProcedimentoBD = new PenMapTipoProcedimentoBD(BancoSEI::getInstance());
      return $objPenMapTipoProcedimentoBD->excluir($objPenMapTipoProcedimentoDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro excluindo mapeamento de procedimento.', $e);
    }
  }

  /**
   * Método utilizado para contagem de procedimento mapeadas
   * @param PenMapTipoProcedimentoDTO $objPenMapTipoProcedimentoDTO
   * @return array
   * @throws InfraException
   */
  protected function contarConectado(PenMapTipoProcedimentoDTO $objPenMapTipoProcedimentoDTO)
  {
    try {
      $objPenMapTipoProcedimentoBD = new PenMapTipoProcedimentoBD(BancoSEI::getInstance());
      return $objPenMapTipoProcedimentoBD->contar($objPenMapTipoProcedimentoDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro contando mapeamento de procedimento.', $e);
    }
  }

  /**
   * @param array $arrObjTipoProcedimentoDTO
   * @param string $mensagem
   * @return void
   */
  public function validarAcaoTipoProcesso($arrObjTipoProcedimentoDTO, $mensagem)
  {
    $arrTipoProcedimento = array();
    $mapeamentos = array();
    foreach ($arrObjTipoProcedimentoDTO as $objTipoProcedimentoDTO) {
      $objMapeamentoTipoProcedimentoDTO = new PenMapTipoProcedimentoDTO();
      $objMapeamentoTipoProcedimentoDTO->retNumIdMapOrgao();
      $objMapeamentoTipoProcedimentoDTO->setNumIdTipoProcessoDestino($objTipoProcedimentoDTO->getIdTipoProcedimento());

      if ($this->contarConectado($objMapeamentoTipoProcedimentoDTO)) {
        $arrObjMapeamentoTipoProcedimentoDTO = $this->listarConectado($objMapeamentoTipoProcedimentoDTO);

        foreach ($arrObjMapeamentoTipoProcedimentoDTO as $objPenMapTipoProcedimentoDTO) {
          $objPenOrgaoExternoDTO = new PenOrgaoExternoDTO();
          $objPenOrgaoExternoDTO->retStrOrgaoDestino();
          $objPenOrgaoExternoDTO->setDblId($objPenMapTipoProcedimentoDTO->getNumIdMapOrgao());

          $objPenOrgaoExternoRN = new PenOrgaoExternoRN();
          $objPenOrgaoExternoDTO = $objPenOrgaoExternoRN->consultar($objPenOrgaoExternoDTO);
          $mapeamentos[$objPenOrgaoExternoDTO->getStrOrgaoDestino()] = $objPenOrgaoExternoDTO->getStrOrgaoDestino();
          $arrTipoProcedimento[$objTipoProcedimentoDTO->getNome()] =  $objTipoProcedimentoDTO->getNome();
        }
      }
    }
    if (count($arrTipoProcedimento) > 0) {
      $mensagem = sprintf($mensagem, implode('", "', $mapeamentos), implode('", "', $arrTipoProcedimento));
      LogSEI::getInstance()->gravar($mensagem, LogSEI::$AVISO);
      throw new InfraException($mensagem);
    }
  }
}