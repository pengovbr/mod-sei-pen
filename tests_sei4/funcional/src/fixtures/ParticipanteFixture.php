<?php

class ParticipanteFixture extends FixtureBase
{
    protected $objParticipanteDTO;
    
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
        $dados['IdParticipante'] = $this->getObjInfraIBanco()->getValorSequencia('seq_participante');

        $this->objParticipanteDTO->setNumIdParticipante($dados['IdParticipante']);
        $this->objParticipanteDTO->setDblIdProtocolo($dados['IdProtocolo']);
        $this->objParticipanteDTO->setNumIdContato($dados['IdContato'] ?: 100000011); // tem que criar fixture contato
        $this->objParticipanteDTO->setNumSequencia($dados['Sequencia'] ?: 0);
        $this->objParticipanteDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
        $this->objParticipanteDTO->setStrStaParticipacao($dados['StaParticipacao'] ?: 'I');
        
        $objParticipanteBD = new \ParticipanteBD(\BancoSEI::getInstance());
        $objParticipanteBD->cadastrar($this->objParticipanteDTO);

        return $this->objParticipanteDTO;
    }

}