<?php

class RelProtocoloProtocoloFixture extends FixtureBase
{
    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    protected function cadastrar($dados = [])
    {
        
        $dados['Sequencia'] = $this->getSequencia($dados);

        $objRelProtocoloProtocoloDTO = new \RelProtocoloProtocoloDTO();

        $objRelProtocoloProtocoloDTO->setDblIdProtocolo1($dados['IdProtocolo']);
        $objRelProtocoloProtocoloDTO->setDblIdProtocolo2($dados['IdDocumento']);
        $objRelProtocoloProtocoloDTO->setNumIdUsuario($dados['IdUsuario'] ?: 100000001);
        $objRelProtocoloProtocoloDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
        $objRelProtocoloProtocoloDTO->setStrSinCiencia($dados['Ciencia'] ?: 'N');
        $objRelProtocoloProtocoloDTO->setNumSequencia($dados['Sequencia'] ?: 0);
        $objRelProtocoloProtocoloDTO->setStrStaAssociacao($dados['Associacao'] ?: 1);
        $objRelProtocoloProtocoloDTO->setDthAssociacao(\InfraData::getStrDataHoraAtual());

        $objRelProtocoloProtocoloBD = new \RelProtocoloProtocoloBD(\BancoSEI::getInstance());
        $objRelProtocoloProtocoloBD->cadastrar($objRelProtocoloProtocoloDTO);

        return $objRelProtocoloProtocoloDTO;
    }

    public function getSequencia($dados =[]){

        $objRelProtocoloProtocoloDTO = new \RelProtocoloProtocoloDTO();
        $objRelProtocoloProtocoloDTO->retTodos();
        $objRelProtocoloProtocoloDTO->setDblIdProtocolo1($dados['IdProtocolo']);
        
        $objRelProtocoloProtocoloBD = new \RelProtocoloProtocoloBD(\BancoSEI::getInstance());
        $DTOs = $objRelProtocoloProtocoloBD->listar($objRelProtocoloProtocoloDTO);
        $seq = 0;
        foreach ($DTOs as $dto) {
            if ($seq < $dto->getNumSequencia()){
                $seq = $dto->getNumSequencia();
            }
        }
        return $seq+1;

    }
}
