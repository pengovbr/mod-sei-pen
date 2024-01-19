<?php

namespace Modpen\Tests\fixtures;

use Tests\Funcional\Fixture;

class RelBlocoUnidadeFixture extends Fixture
{

    protected function inicializarObjInfraIBanco(){
        return \BancoSEI::getInstance();
    }

    protected function cadastrar($dados = [])
    {

        $objRelBlocoUnidadeDTO = new \RelBlocoUnidadeDTO();
        // $objBlocoDTO->setNumIdBloco();
        $objRelBlocoUnidadeDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
        $objRelBlocoUnidadeDTO->setNumIdBloco($dados['IdBloco'] ?: null);
        $objRelBlocoUnidadeDTO->setNumIdGrupoBloco($dados['IdGrupoBloco'] ?: null);
        $objRelBlocoUnidadeDTO->setNumIdUsuarioRevisao($dados['IdUsuarioRevisao'] ?: null);
        $objRelBlocoUnidadeDTO->setNumIdUsuarioPrioridade($dados['IdUsuarioPrioridade'] ?: null);
        $objRelBlocoUnidadeDTO->setNumIdUsuarioAtribuicao($dados['IdUsuarioAtribuicao'] ?: null);
        $objRelBlocoUnidadeDTO->setNumIdUsuarioComentario($dados['IdUsuarioComentario'] ?: null);
        $objRelBlocoUnidadeDTO->setStrSinRetornado($dados['SinRetornado'] ?: 'N');
        $objRelBlocoUnidadeDTO->setStrSinRevisao($dados['SinRevisao'] ?: 'N');
        $objRelBlocoUnidadeDTO->setStrSinPrioridade($dados['SinPrioridade'] ?: 'N');
        $objRelBlocoUnidadeDTO->setStrSinComentario($dados['SinComentario'] ?: 'N');
        $objRelBlocoUnidadeDTO->setStrTextoComentario($dados['TextoComentario'] ?: null);
        $objRelBlocoUnidadeDTO->setDthRevisao($dados['Revisao'] ?: null);
        $objRelBlocoUnidadeDTO->setDthPrioridade($dados['Prioridade'] ?: null);
        $objRelBlocoUnidadeDTO->setDthComentario($dados['Comentario'] ?: null);                       
        
        $objBlocoUnidadeDB = new \RelBlocoUnidadeBD(\BancoSEI::getInstance());
        $objBlocoUnidadeDB->cadastrar($objRelBlocoUnidadeDTO);

        return $objRelBlocoUnidadeDTO;
    }
}