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
      $objPenUnidadeBD = new \PenUnidadeBD($this->inicializarObjInfraIBanco());

    if ($objPenUnidadeDTO) {
      if ($objPenUnidadeDTO->getNumIdUnidadeRH() != $dados['Id'] ||
        $objPenUnidadeDTO->getStrNomeUnidadeRH() != $dados['Nome'] ||
        $objPenUnidadeDTO->getStrSiglaUnidadeRH() != $dados['Sigla']) {
          $objPenUnidadeDTO->setNumIdUnidadeRH($dados['Id']);
          $objPenUnidadeDTO->setStrNomeUnidadeRH($dados['Nome']);
          $objPenUnidadeDTO->setStrSiglaUnidadeRH($dados['Sigla']);
          $objPenUnidadeDTO = $objPenUnidadeBD->alterar($objPenUnidadeDTO);
      }
        return $objPenUnidadeDTO;
    }

      $this->objPenUnidadeDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
      $this->objPenUnidadeDTO->setNumIdUnidadeRH($dados['Id']);
      $this->objPenUnidadeDTO->setStrNomeUnidadeRH($dados['Nome']);
      $this->objPenUnidadeDTO->setStrSiglaUnidadeRH($dados['Sigla']);

      return $objPenUnidadeBD->cadastrar($this->objPenUnidadeDTO);
  }
    
  public function consultar($dados = [])
    {
      $objPenUnidadeDTO = new \PenUnidadeDTO();
      $objPenUnidadeDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
      $objPenUnidadeDTO->retTodos();

      $objPenUnidadeBD = new \PenUnidadeBD($this->inicializarObjInfraIBanco());
      return $objPenUnidadeBD->consultar($objPenUnidadeDTO);
  }
  
  protected function listar($dados = [])
  { 
    $this->objPenUnidadeDTO->setNumIdUnidade($dados['IdUnidade']);
    $this->objPenUnidadeDTO->retTodos();

    $objPenUnidadeBD = new \PenUnidadeBD($this->inicializarObjInfraIBanco());
    return $objPenUnidadeBD->listar($this->objPenUnidadeDTO);
  }

  public function excluir($dados = [])
  {
    if (is_numeric($dados['Id'])){
      $objPenUnidadeDTO = new \PenUnidadeDTO();
      $objPenUnidadeDTO->setNumIdUnidadeRH($dados['Id']);
      $objPenUnidadeDTO->retTodos();
      
      $objPenUnidadeBD = new \PenUnidadeBD($this->inicializarObjInfraIBanco());
      $objPenUnidadeDTO = $objPenUnidadeBD->consultar($objPenUnidadeDTO);
      if ($objPenUnidadeDTO != null) {
        return $objPenUnidadeBD->excluir($objPenUnidadeDTO);
      }
    }
    return false;
  }

}