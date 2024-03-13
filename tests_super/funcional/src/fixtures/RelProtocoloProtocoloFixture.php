<?php

class RelProtocoloProtocoloFixture extends \FixtureBase
{
    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    protected function cadastrar($dados = [])
    {
        $objRelProtocoloProtocoloDTO = new \RelProtocoloProtocoloDTO();

        $objRelProtocoloProtocoloDTO->setDblIdProtocolo1($dados['IdProtocolo']);
        $objRelProtocoloProtocoloDTO->setDblIdProtocolo2($dados['IdDocumento']);
        $objRelProtocoloProtocoloDTO->setNumIdUsuario($dados['IdUsuario'] ?: 100000001);
        $objRelProtocoloProtocoloDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
        $objRelProtocoloProtocoloDTO->setStrSinCiencia($dados['Ciencia'] ?: 'N');
        $objRelProtocoloProtocoloDTO->setNumSequencia($dados['Sequencia'] ?: 0);
        $objRelProtocoloProtocoloDTO->setStrStaAssociacao($dados['Associacao'] ?: 1);
        $objRelProtocoloProtocoloDTO->setDthAssociacao(InfraData::getStrDataHoraAtual());

        $objRelProtocoloAssuntoDB = new \RelProtocoloProtocoloBD(\BancoSEI::getInstance());
        $objRelProtocoloAssuntoDB->cadastrar($objRelProtocoloProtocoloDTO);

        return $objRelProtocoloProtocoloDTO;
    }
}
