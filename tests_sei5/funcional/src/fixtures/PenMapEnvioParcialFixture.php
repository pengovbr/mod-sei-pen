<?php

use InfraData;

class PenMapEnvioParcialFixture extends \FixtureBase
{
    protected $objMapEnvioParcialDTO;

  public function __construct()
    {
      $this->objMapEnvioParcialDTO = new \PenRestricaoEnvioComponentesDigitaisDTO();
  }
 
  protected function inicializarObjInfraIBanco()
    {
      return \BancoSEI::getInstance();
  }

  protected function cadastrar($dados = [])
    {
      $objMapEnvioParcialDTO = $this->consultar($dados);
    if ($objMapEnvioParcialDTO) {
        return $objMapEnvioParcialDTO;
    }

      $this->objMapEnvioParcialDTO->setNumIdEstrutura($dados['IdEstrutura'] ?: 5);
      $this->objMapEnvioParcialDTO->setStrStrEstrutura($dados['StrEstrutura'] ?: 'RE CGPRO');
      $this->objMapEnvioParcialDTO->setNumIdUnidadePen($dados['IdUnidadePen']);
      $this->objMapEnvioParcialDTO->setStrStrUnidadePen($dados['StrUnidadePen']);
        
      $objMapEnvioParcialDB = new \PenRestricaoEnvioComponentesDigitaisBD($this->inicializarObjInfraIBanco());
      $objMapEnvioParcialDB->cadastrar($this->objMapEnvioParcialDTO);

      return $this->objMapEnvioParcialDTO;
  }

  public function consultar($dados = [])
    {
      $objMapEnvioParcialDTO = new \PenRestricaoEnvioComponentesDigitaisDTO();
      $objMapEnvioParcialDTO->setNumIdEstrutura($dados['IdEstrutura'] ?: 5);
      $objMapEnvioParcialDTO->setStrStrEstrutura($dados['StrEstrutura'] ?: 'RE CGPRO');
      $objMapEnvioParcialDTO->setNumIdUnidadePen($dados['IdUnidadePen']);
      $objMapEnvioParcialDTO->setStrStrUnidadePen($dados['StrUnidadePen']);
      $objMapEnvioParcialDTO->retTodos();

      $objMapEnvioParcialDB = new \PenRestricaoEnvioComponentesDigitaisBD($this->inicializarObjInfraIBanco());
      return $objMapEnvioParcialDB->consultar($objMapEnvioParcialDTO);
  }

  public function excluir($dados = [])
    {
      $this->$objMapEnvioParcialDTO = new \PenRestricaoEnvioComponentesDigitaisDTO();
      $this->$objMapEnvioParcialDTO->setDblId($dados['Id']);

      $objMapEnvioParcialDB = new \PenRestricaoEnvioComponentesDigitaisBD($this->inicializarObjInfraIBanco());
      $objMapEnvioParcialDB->excluir($this->$objMapEnvioParcialDTO);
  }
}
