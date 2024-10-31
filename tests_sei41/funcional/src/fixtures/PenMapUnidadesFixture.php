<?php

/**
 * Responsável por cadastrar novo mapeamento de unidades caso não exista
 */
class PenMapUnidadesFixture extends \FixtureBase
{
    protected $objPenUnidadeDTO;

  public function __construct()
    {
      $this->objPenUnidadeDTO = new \PenUnidadeDTO();
  }

  protected function inicializarObjInfraIBanco()
    {
      return \BancoSEI::getInstance();
  }

  public function cadastrar($dados = [])
    {
      $objPenUnidadeDTO = $this->consultar($dados);
    if ($objPenUnidadeDTO) {
        return $objPenUnidadeDTO;
    }

      $this->objPenUnidadeDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
      $this->objPenUnidadeDTO->setNumIdUnidadeRH($dados['Id']);
      $this->objPenUnidadeDTO->setStrNomeUnidadeRH($dados['Nome']);
      $this->objPenUnidadeDTO->setStrSiglaUnidadeRH($dados['Sigla']);

      $objPenUnidadeBD = new \PenUnidadeBD($this->inicializarObjInfraIBanco());
      return $objPenUnidadeBD->cadastrar($this->objPenUnidadeDTO);
  }
    
  public function consultar($dados = [])
    {
      $objPenUnidadeDTO = new \PenUnidadeDTO();
      $objPenUnidadeDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
      $objPenUnidadeDTO->setNumIdUnidadeRH($dados['Id']);
      $objPenUnidadeDTO->setStrNomeUnidadeRH($dados['Nome']);
      $objPenUnidadeDTO->setStrSiglaUnidadeRH($dados['Sigla']);
      $objPenUnidadeDTO->retTodos();

      $objPenUnidadeBD = new \PenUnidadeBD($this->inicializarObjInfraIBanco());
      return $objPenUnidadeBD->consultar($objPenUnidadeDTO);
  }
}