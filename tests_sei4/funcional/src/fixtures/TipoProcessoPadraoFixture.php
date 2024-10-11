<?php

use InfraData;

class TipoProcessoPadraoFixture extends \FixtureBase
{
  protected $objPenParametroDTO;

  public function __construct()
  {
    $this->objPenParametroDTO = new \PenParametroDTO();
  }

  protected function inicializarObjInfraIBanco()
  {
    return \BancoSEI::getInstance();
  }

  public function cadastrar($dados = [])
  {
    $objPenParametroDTO = new \PenParametroDTO();
    $objPenParametroDTO->setStrNome($dados['Nome']);

    $objPenParametroBD = new \PenParametroBD($this->inicializarObjInfraIBanco());
    if($objPenParametroBD->contar($objPenParametroDTO) > 0) {
      $objPenParametroDTO->setStrValor($dados['Valor']);
      $objPenParametroBD->alterar($objPenParametroDTO);
    }
  }
}
