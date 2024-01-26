<?php

use Tests\Funcional\Fixture;
use InfraData;
use ProcedimentoDTO;
use ProtocoloDTO;

class ProcedimentoFixture extends \FixtureBase
{
    const TIPO_PROCEDIMENTO_PADRAO = '100000256'; // cobrança
    
    protected $objProcedimentoDTO;

    public function __construct()
    {
        $this->objProcedimentoDTO = new \ProcedimentoDTO();
    }
 
    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    protected function cadastrar($dados = [])
    {
        $this->objProcedimentoDTO->setDblIdProcedimento($dados["IdProtocolo"] ?: null);
        $this->objProcedimentoDTO->setNumIdTipoProcedimento(
            $dados["IdTipoProcedimento"] ?: self::TIPO_PROCEDIMENTO_PADRAO
        );
        $this->objProcedimentoDTO->setStrStaOuvidoria($dados["StaOuvidoria"] ?: '-');
        $this->objProcedimentoDTO->setStrSinCiencia($dados["SinCiencia"] ?: 'N');
        
        $objProcedimentoDB = new \ProcedimentoBD(\BancoSEI::getInstance());
        $objProcedimentoDB->cadastrar($this->objProcedimentoDTO);

        return $this->objProcedimentoDTO;
    }

}