<?php

// use Tests\Funcional\Fixture;
use InfraData;

class AtributoAndamentoFixture extends \FixtureBase
{
    protected $objAtributoAndamentoDTO;

    public function __construct()
    {
        $this->objAtributoAndamentoDTO = new \AtributoAndamentoDTO();
    }
 
    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    protected function cadastrar($dados = [])
    {
        $this->objAtributoAndamentoDTO->setNumIdAtividade($dados['IdAtividade']);
        $this->objAtributoAndamentoDTO->setStrNome($dados['Nome'] ?: 'NIVEL_ACESSO');
        $this->objAtributoAndamentoDTO->setStrValor($dados['Valor'] ?: null);
        $this->objAtributoAndamentoDTO->setStrIdOrigem($dados['IdOrigem'] ?: 0);
        
        $objAtributoAndamentoDB = new \AtributoAndamentoBD(\BancoSEI::getInstance());
        $objAtributoAndamentoDB->cadastrar($this->objAtributoAndamentoDTO);

        return $this->objAtributoAndamentoDTO;
    }
}
