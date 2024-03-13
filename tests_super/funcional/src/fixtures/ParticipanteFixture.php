<?php

class ParticipanteFixture extends \FixtureBase
{
    protected $objParticipanteDTO;

    CONST INTERESSADO = 'I';
    CONST DESTINATARIO = 'D';
    CONST REMETENTE = 'R';
    CONST ACESSO_EXTERNO = 'A';

    public function __construct()
    {
        $this->objParticipanteDTO = new \ParticipanteDTO();
    }
 
    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    protected function cadastrar($dados = [])
    {

        $this->objParticipanteDTO->setDblIdProtocolo($dados['IdProtocolo'] ?: null);
        $this->objParticipanteDTO->setStrStaParticipacao($dados['StaParticipacao'] ?: self::INTERESSADO);
        $this->objParticipanteDTO->setNumIdContato($dados['IdContato'] ?: 100000006);
        $this->objParticipanteDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
        $this->objParticipanteDTO->setNumSequencia($dados['Sequencia'] ?: 0);

        
        $objParticipanteDB = new \ParticipanteBD(\BancoSEI::getInstance());
        $objParticipanteDB->cadastrar($this->objParticipanteDTO);

        return $this->objParticipanteDTO;
    }
}
