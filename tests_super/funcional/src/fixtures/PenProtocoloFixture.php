<?php

class PenProtocoloFixture extends \FixtureBase
{
    protected $objProtocoloDTO;

    public function __construct()
    {
        $this->objProtocoloDTO = new \ProtocoloDTO();
    }

    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    public function cadastrar($dados = [])
    {
    }
    
    protected function listar($dados = []) 
    {
        $objProtocoloDTO = new \ProtocoloDTO();
        $objProtocoloDTO->setStrProtocoloFormatado($dados['ProtocoloFormatado']);
        $objProtocoloDTO->retTodos();

        $objProtocoloDB = new \ProtocoloBD(\BancoSEI::getInstance());
        return $objProtocoloDB->listar($objProtocoloDTO);
    }
}