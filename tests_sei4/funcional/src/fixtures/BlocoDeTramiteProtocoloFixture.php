<?php

class BlocoDeTramiteProtocoloFixture extends \FixtureBase
{
    protected $objBlocoDeTramiteProtocoloDTO;

  public function __construct()
    {
      $this->objBlocoDeTramiteProtocoloDTO = new \PenBlocoProcessoDTO();
  }
 
  protected function inicializarObjInfraIBanco()
    {
      return \BancoSEI::getInstance();
  }

  protected function cadastrar($dados = [])
    {
      $this->objBlocoDeTramiteProtocoloDTO->setDblIdProtocolo($dados['IdProtocolo'] ?: null);
      $this->objBlocoDeTramiteProtocoloDTO->setNumIdBloco($dados['IdBloco'] ?: null);
      $this->objBlocoDeTramiteProtocoloDTO->setNumSequencia($dados['Sequencia'] ?: null);
      $this->objBlocoDeTramiteProtocoloDTO->setNumIdUsuario($dados['IdUsuario'] ?: '100000001');
      $this->objBlocoDeTramiteProtocoloDTO->setNumIdUnidade($dados['IdUnidade'] ?: '110000001');
      $dthRegistro = date('d/m/Y H:i:s');
      $this->objBlocoDeTramiteProtocoloDTO->setDthRegistro($dados['DthRegistro'] ?: $dthRegistro);
      $this->objBlocoDeTramiteProtocoloDTO->setDthAtualizado($dados['DthAtualizado'] ?: $dthRegistro);

      $objBlocoDeTramiteProtocoloBD = new \PenBlocoProcessoBD($this->inicializarObjInfraIBanco());
      $objBlocoDeTramiteProtocoloBD->cadastrar($this->objBlocoDeTramiteProtocoloDTO);

      return $this->objBlocoDeTramiteProtocoloDTO;
  }
}
