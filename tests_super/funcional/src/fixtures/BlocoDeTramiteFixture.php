<?php

namespace Modpen\Tests\fixtures;

//use Tests\Funcional\Fixture;
// use Faker\Factory;
use InfraData;
// use TramiteEmBlocoDTO;

class BlocoDeTramiteFixture extends FixtureBase
{
    protected $objBlocoDeTramiteDTO;

    CONST TRATAMENTO = 'Presidente, Substituto';
    CONST ID_TARJA_ASSINATURA = 2;

    public function __construct()
    {
        $this->objBlocoDeTramiteDTO = new \TramiteEmBlocoDTO();
    }
 
    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    protected function cadastrar($dados = [])
    {
        //$dados['IdAssinatura'] = $this->getObjInfraIBanco()->getValorSequencia('seq_assinatura');

        // print_R($dados); die('aki');
        $this->$objBlocoDeTramiteDTO->setNumIdUnidade(2);
        $this->$objBlocoDeTramiteDTO->setNumIdUsuario(110000001);
        $this->$objBlocoDeTramiteDTO->setStrDescricao(100000001);
        $this->$objBlocoDeTramiteDTO->setStrIdxBloco('STATUC Novo bloco');
        $this->$objBlocoDeTramiteDTO->setStrStaTipo('1 novo bloco');
        $this->$objBlocoDeTramiteDTO->setStrStaEstado('A');

        
        $objBlocoDeTramiteDB = new \TramiteEmBlocoBD(\BancoSEI::getInstance());
        $objBlocoDeTramiteDB->cadastrar($this->objBlocoDeTramiteDTO);

        return $this->objBlocoDeTramiteDTO;
    }
}
