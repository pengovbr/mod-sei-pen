<?php

use Tests\Funcional\Fixture;

class RelBlocoProtocoloFixture extends Fixture
{

    protected function inicializarObjInfraIBanco(){
        return \BancoSEI::getInstance();
    }

    protected function cadastrar($dados = [])
    {
        $IdProtocolo = $this->getObjInfraIBanco()->getValorSequencia('seq_protocolo');
        // $dados['IdDocumento'] = $this->getObjInfraIBanco()->getValorSequencia('seq_documento');

        $objRelBlocoProtocoloDTO = new \RelBlocoProtocoloDTO();
        // $objBlocoDTO->setNumIdBloco();
        $objRelBlocoProtocoloDTO->setDblIdProtocolo($IdProtocolo ?: null);
        $objRelBlocoProtocoloDTO->setNumIdBloco($dados['IdBloco'] ?: null);
        $objRelBlocoProtocoloDTO->setStrAnotacao($dados['Anotacao'] ?: null);
        $objRelBlocoProtocoloDTO->setNumSequencia($dados['Sequencia'] ?: 1);
        $objRelBlocoProtocoloDTO->setStrIdxRelBlocoProtocolo($IdProtocolo);                  
        
        $objRelBlocoProtocoloDB = new \RelBlocoProtocoloBD(\BancoSEI::getInstance());
        $objRelBlocoProtocoloDB->cadastrar($objRelBlocoProtocoloDTO);

        return $objRelBlocoProtocoloDTO;
    }
}