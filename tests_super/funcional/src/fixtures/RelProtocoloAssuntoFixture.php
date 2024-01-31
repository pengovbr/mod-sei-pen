<?php

class RelProtocoloAssuntoFixture extends FixtureBase
{
    const ID_ASSUNTO_PADRAO_TEST = 377;

    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    protected function cadastrar($dados = [])
    {
        $objRelProtocoloAssuntoDTO = new \RelProtocoloAssuntoDTO();

        $objRelProtocoloAssuntoDTO->setDblIdProtocolo($dados['IdProtocolo']);
        $objRelProtocoloAssuntoDTO->setNumIdAssuntoProxy($dados['IdAssunto'] ?: 2);
        $objRelProtocoloAssuntoDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
        $objRelProtocoloAssuntoDTO->setNumSequencia($dados['Sequencia'] ?: 0);

        $objRelProtocoloAssuntoDB = new \RelProtocoloAssuntoBD(\BancoSEI::getInstance());
        $objRelProtocoloAssuntoDB->cadastrar($objRelProtocoloAssuntoDTO);

        return $objRelProtocoloAssuntoDTO;
    }
}
