<?php

class ContatoFixture extends FixtureBase
{
    protected $objContatoDTO;
    
    public function __construct()
    {
        $this->objContatoDTO = new \ContatoDTO();
    }
 
    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }
    
    protected function cadastrar($dados = [])
    {

        $dados['IdContato'] = $this->getObjInfraIBanco()->getValorSequencia('seq_contato');
        $dados['Nome'] = $dados['Nome'] ?: 'teste';

        $this->objContatoDTO->setNumIdContato($dados['IdContato']);
        $this->objContatoDTO->setNumIdContatoAssociado($dados['IdContatoAssociado'] ?: $dados['IdContato']);
        $this->objContatoDTO->setStrNome($dados['Nome']);
        $this->objContatoDTO->setStrNomeRegistroCivil($dados['NomeRegistroCivil'] ?: $dados['Nome']);
        // $this->objContatoDTO->setNumIdCargo($dados['IdCargo'] ?: 0);
        // $this->objContatoDTO->setNumIdCategoria($dados['IdCategoria'] ?: 110000001);
        $this->objContatoDTO->setStrSinAtivo($dados['SinAtivo'] ?: 'S');
        $this->objContatoDTO->setStrIdxContato($dados['IdxContato'] ?: strtolower($dados['Nome']));
        $this->objContatoDTO->setNumIdUnidadeCadastro($dados['IdUnidadeCadastro'] ?: 110000001);
        $this->objContatoDTO->setNumIdUsuarioCadastro($dados['IdUsuarioCadastro'] ?: 100000001);
        $this->objContatoDTO->setDthCadastro($dados['Cadastro'] ?: \InfraData::getStrDataHoraAtual());
        $this->objContatoDTO->setNumIdTipoContato($dados['IdTipoContato'] ?: 4);
        $this->objContatoDTO->setStrStaNatureza($dados['StaNatureza'] ?: 'F');
        $this->objContatoDTO->setStrSinEnderecoAssociado($dados['SinEnderecoAssociado'] ?: 'N');

        $objContatoBD = new \ContatoBD(\BancoSEI::getInstance());
        $objContatoBD->cadastrar($this->objContatoDTO);

        return $this->objContatoDTO;
    }

}