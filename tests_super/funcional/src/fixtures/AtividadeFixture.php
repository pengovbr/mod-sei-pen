<?php

namespace Modpen\Tests\fixtures;

// use Tests\Funcional\Fixture;
// use Faker\Factory;
use InfraData;

class AtividadeFixture extends FixtureBase
{
    protected $objAtividadeDTO;

    public function __construct()
    {
        $this->objAtividadeDTO = new \AtividadeDTO();
    }
 
    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    protected function cadastrar($dados = [])
    {
        // $this->objAtividadeDTO->setNumIdAtividade($dados['setNumIdAtividade'] ?: null);
        $this->objAtividadeDTO->setDblIdProtocolo($dados['IdProtocolo'] ?: null);
        $this->objAtividadeDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
        $this->objAtividadeDTO->setNumIdUnidadeOrigem($dados['IdUnidadeOrigem'] ?: 110000001);
        $this->objAtividadeDTO->setNumIdUsuario($dados['IdUsuario'] ?: null);
        $this->objAtividadeDTO->setNumIdUsuarioOrigem($dados['IdUsuarioOrigem'] ?: 100000001);
        $this->objAtividadeDTO->setDthAbertura($dados['Abertura'] ?: InfraData::getStrDataHoraAtual());
        $this->objAtividadeDTO->setDthConclusao($dados['Conclusao'] ?: null);
        $this->objAtividadeDTO->setNumIdTarefa($dados['IdTarefa'] ?: 1);
        $this->objAtividadeDTO->setNumIdUsuarioAtribuicao($dados['IdUsuarioAtribuicao'] ?: null);
        $this->objAtividadeDTO->setNumIdUsuarioConclusao($dados['IdUsuarioConclusao'] ?: null);
        $this->objAtividadeDTO->setStrSinInicial($dados['SinInicial'] ?: 'S');
        $this->objAtividadeDTO->setNumIdUsuarioVisualizacao($dados['IdUsuarioVisualizacao'] ?: null);
        $this->objAtividadeDTO->setNumTipoVisualizacao($dados['TipoVisualizacao'] ?: 0);
        $this->objAtividadeDTO->setDtaPrazo($dados['Prazo'] ?: null);
        
        $objProtocoloDB = new \AtividadeBD(\BancoSEI::getInstance());
        $objProtocoloDB->cadastrar($this->objAtividadeDTO);

        return $this->objAtividadeDTO;
    }
}
