<?php

/**
 * Responsável por cadastrar novo mapeamento de unidades caso não exista
 */
class PenUnidadeRestricaoFixture extends \FixtureBase
{
    protected $objPenUnidadeRestricaoDTO;

  public function __construct()
    {
      $this->objPenUnidadeRestricaoDTO = new \PenUnidadeRestricaoDTO();
  }

  protected function inicializarObjInfraIBanco()
    {
      return \BancoSEI::getInstance();
  }

  public function cadastrar($dados = [])
    {
      $objPenUnidadeRestricaoDTO = $this->consultar($dados);
    if ($objPenUnidadeRestricaoDTO) {
        return $objPenUnidadeRestricaoDTO;
    }

      $this->objPenUnidadeRestricaoDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
      $this->objPenUnidadeRestricaoDTO->setNumIdUnidadeRH($dados['IdUnidadeRH']);
      $this->objPenUnidadeRestricaoDTO->setNumIdUnidadeRestricao($dados['IdUnidadeRestricao']);
      $this->objPenUnidadeRestricaoDTO->setStrNomeUnidadeRestricao($dados['NomeUnidadeRestricao']);
      $this->objPenUnidadeRestricaoDTO->setNumIdUnidadeRHRestricao($dados['IdUnidadeRHRestricao']);
      $this->objPenUnidadeRestricaoDTO->setStrNomeUnidadeRHRestricao($dados['NomeUnidadeRHRestricao']);

      $objPenUnidadeRestricaoBD = new \PenUnidadeRestricaoBD($this->inicializarObjInfraIBanco());
      return $objPenUnidadeRestricaoBD->cadastrar($this->objPenUnidadeRestricaoDTO);
  }
    
  public function consultar($dados = [])
    {
      $objPenUnidadeRestricaoDTO = new \PenUnidadeRestricaoDTO();

      $objPenUnidadeRestricaoDTO->setStrNomeUnidadeRestricao($dados['NomeUnidadeRestricao']);
      $objPenUnidadeRestricaoDTO->setStrNomeUnidadeRHRestricao($dados['NomeUnidadeRHRestricao']."%", InfraDTO::$OPER_LIKE);
      $objPenUnidadeRestricaoDTO->retTodos();

      $objPenUnidadeRestricaoBD = new \PenUnidadeRestricaoBD($this->inicializarObjInfraIBanco());
      return $objPenUnidadeRestricaoBD->consultar($objPenUnidadeRestricaoDTO);
  }

  public function excluir($dados = [])
    {

      $objPenUnidadeRestricaoDTO = $this->consultar($dados);
    if (!$objPenUnidadeRestricaoDTO) {
        return false;
    }

      $objPenUnidadeRestricaoBD = new \PenUnidadeRestricaoBD($this->inicializarObjInfraIBanco());
      return $objPenUnidadeRestricaoBD->excluir($objPenUnidadeRestricaoDTO);
  }
}