<?php

class ImportacaoTiposProcessoFixture extends \FixtureBase
{
    protected $objPenMapTipoProcedimentoDTO;
    protected $dthRegistro;

  public function __construct()
    {
      $this->objPenMapTipoProcedimentoDTO = new \PenMapTipoProcedimentoDTO();
      $this->dthRegistro = \InfraData::getStrDataAtual();
  }

  protected function inicializarObjInfraIBanco()
    {
      return \BancoSEI::getInstance();
  }

  public function cadastrar($dados = [])
    {
      $objPenMapTipoProcedimentoDTO = $this->consultar($dados);
    if ($objPenMapTipoProcedimentoDTO) {
        return $objPenMapTipoProcedimentoDTO;
    }
        
      $this->objPenMapTipoProcedimentoDTO->setNumIdMapOrgao($dados['IdMapeamento']);
      $this->objPenMapTipoProcedimentoDTO->setNumIdTipoProcessoOrigem($dados['IdProcedimento']);
      $this->objPenMapTipoProcedimentoDTO->setStrNomeTipoProcesso($dados['NomeProcedimento']);
      $this->objPenMapTipoProcedimentoDTO->setNumIdUnidade(($dados['IdUnidade'] ?: 110000001));
      $this->objPenMapTipoProcedimentoDTO->setStrAtivo(($dados['SinAtivo'] ?: 'S'));
      $this->objPenMapTipoProcedimentoDTO->setDthRegistro(\InfraData::getStrDataAtual());
        
      $objPenMapTipoProcedimentoBD = new \PenMapTipoProcedimentoBD($this->inicializarObjInfraIBanco());
      return $objPenMapTipoProcedimentoBD->cadastrar($this->objPenMapTipoProcedimentoDTO);
  }
    
  public function consultar($dados = [])
    {
      $objPenMapTipoProcedimentoDTO = new \PenMapTipoProcedimentoDTO();
      $objPenMapTipoProcedimentoDTO->setNumIdMapOrgao($dados['IdMapeamento']);
      $objPenMapTipoProcedimentoDTO->setNumIdTipoProcessoOrigem($dados['IdProcedimento']);
      $objPenMapTipoProcedimentoDTO->setNumIdUnidade(($dados['IdUnidade'] ?: 110000001));
      $objPenMapTipoProcedimentoDTO->setStrAtivo(($dados['SinAtivo'] ?: 'S'));
      $objPenMapTipoProcedimentoDTO->retTodos();
        
      $objPenMapTipoProcedimentoBD = new \PenMapTipoProcedimentoBD($this->inicializarObjInfraIBanco());
      return $objPenMapTipoProcedimentoBD->consultar($objPenMapTipoProcedimentoDTO);
  }

  public function listar($dados = [])
    {
      $objPenMapTipoProcedimentoDTO = new \PenMapTipoProcedimentoDTO();
      $objPenMapTipoProcedimentoDTO->setNumIdMapOrgao($dados['IdMapeamento']);
    if ($dados['IdProcedimento']) {
        $objPenMapTipoProcedimentoDTO->setNumIdTipoProcessoOrigem($dados['IdProcedimento']);
    }
    if ($dados['IdUnidade']) {
        $objPenMapTipoProcedimentoDTO->setNumIdUnidade($dados['IdUnidade']);

    }
    if ($dados['SinAtivo']) {
        $objPenMapTipoProcedimentoDTO->setStrAtivo($dados['SinAtivo']);
    }
      $objPenMapTipoProcedimentoDTO->retTodos();
        
      $objPenMapTipoProcedimentoBD = new \PenMapTipoProcedimentoBD($this->inicializarObjInfraIBanco());
      return $objPenMapTipoProcedimentoBD->listar($objPenMapTipoProcedimentoDTO);
  }

  public function excluir($dados = [])
    {
      $objPenMapTipoProcedimentoDTO = new \PenMapTipoProcedimentoDTO();
      $objPenMapTipoProcedimentoDTO->setDblId($dados['Id']);

      $objPenMapTipoProcedimentoBD = new \PenMapTipoProcedimentoBD($this->inicializarObjInfraIBanco());
      return $objPenMapTipoProcedimentoBD->excluir($objPenMapTipoProcedimentoDTO);
  }
}