<?php

class BlocoDeTramiteProtocoloFixture extends \FixtureBase
{
    protected $objBlocoDeTramiteProtocoloDTO;

    public function __construct()
    {
        $this->objBlocoDeTramiteProtocoloDTO = new \TramitaEmBlocoProtocoloDTO();
    }
 
    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    protected function cadastrar($dados = [])
    {

        $this->objBlocoDeTramiteProtocoloDTO->setDblIdProtocolo($dados['IdProtocolo'] ?: null);
        $this->objBlocoDeTramiteProtocoloDTO->setNumIdTramitaEmBloco($dados['IdTramitaEmBloco'] ?: null);
        $this->objBlocoDeTramiteProtocoloDTO->setStrAnotacao($dados['Anotacao'] ?: null);
        $this->objBlocoDeTramiteProtocoloDTO->setNumSequencia($dados['Sequencia'] ?: null);
        $this->objBlocoDeTramiteProtocoloDTO->setStrIdxRelBlocoProtocolo($dados['IdxRelBlocoProtocolo'] ?: null);

        
        $objBlocoDeTramiteProtocoloBD = new \TramitaEmBlocoProtocoloBD(\BancoSEI::getInstance());
        $objBlocoDeTramiteProtocoloBD->cadastrar($this->objBlocoDeTramiteProtocoloDTO);

        return $this->objBlocoDeTramiteProtocoloDTO;
    }
}
