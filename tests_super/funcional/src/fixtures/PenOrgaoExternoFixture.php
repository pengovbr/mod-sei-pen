<?php

class PenOrgaoExternoFixture extends \FixtureBase
{
    protected $objPenOrgaoExternoDTO;

  public function __construct()
    {
      $this->objPenOrgaoExternoDTO = new \PenOrgaoExternoDTO();
  }

  protected function inicializarObjInfraIBanco()
    {
      return \BancoSEI::getInstance();
  }

  public function cadastrar($dados = [])
    {
      $this->objPenOrgaoExternoDTO = $this->consultar($dados);
    if ($this->objPenOrgaoExternoDTO) {
        return $this->objPenOrgaoExternoDTO;
    }

      $this->objPenOrgaoExternoDTO = new \PenOrgaoExternoDTO();
      $this->objPenOrgaoExternoDTO->setNumIdOrgaoOrigem($dados['IdOrigem']);
      $this->objPenOrgaoExternoDTO->setStrOrgaoOrigem($dados['NomeOrigem']);
      $this->objPenOrgaoExternoDTO->setNumIdEstrutaOrganizacionalOrigem($dados['IdRepositorio']);
      $this->objPenOrgaoExternoDTO->setStrEstrutaOrganizacionalOrigem($dados['RepositorioEstruturas']);

      $this->objPenOrgaoExternoDTO->setNumIdOrgaoDestino($dados['Id']);
      $this->objPenOrgaoExternoDTO->setStrOrgaoDestino($dados['Nome']);

      $this->objPenOrgaoExternoDTO->setDthRegistro($dados['DataRegistro'] ?: \InfraData::getStrDataAtual());
      $this->objPenOrgaoExternoDTO->setStrAtivo($dados['SinAtivo'] ?: 'S');
      $this->objPenOrgaoExternoDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);

      $objPenOrgaoExternoBD = new \PenOrgaoExternoBD($this->inicializarObjInfraIBanco());
      $this->objPenOrgaoExternoDTO = $objPenOrgaoExternoBD->cadastrar($this->objPenOrgaoExternoDTO);

      return $this->objPenOrgaoExternoDTO;
  }

  public function consultar($dados = [])
    {
      $objPenOrgaoExternoDTO = new \PenOrgaoExternoDTO();
      $objPenOrgaoExternoDTO->setNumIdOrgaoOrigem($dados['IdOrigem']);
      $objPenOrgaoExternoDTO->setNumIdOrgaoDestino($dados['Id']);
      $objPenOrgaoExternoDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
      $objPenOrgaoExternoDTO->retTodos();

      $objPenOrgaoExternoBD = new \PenOrgaoExternoBD($this->inicializarObjInfraIBanco());
      return $objPenOrgaoExternoBD->consultar($objPenOrgaoExternoDTO);
  }

  public function excluir($dados = [])
    {
      $objPenOrgaoExternoDTO = new \PenOrgaoExternoDTO();
      $objPenOrgaoExternoDTO->setDblId($dados['Id']);

      $objPenOrgaoExternoBD = new \PenOrgaoExternoBD($this->inicializarObjInfraIBanco());
      return $objPenOrgaoExternoBD->excluir($objPenOrgaoExternoDTO);
  }
}