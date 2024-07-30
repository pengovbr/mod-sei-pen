<?php

use InfraData;

class BlocoDeTramiteFixture extends \FixtureBase
{
    protected $objBlocoDeTramiteDTO;

    CONST TRATAMENTO = 'Presidente, Substituto';
    CONST ID_TARJA_ASSINATURA = 2;
 
    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    protected function cadastrar($dados = [])
    {
        $objBlocoDeTramiteDTO = new \TramiteEmBlocoDTO();

        $ordem = $this->pegarProximaOrdem($dados['IdUnidade'] ?: 110000001);

        $objBlocoDeTramiteDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
        $objBlocoDeTramiteDTO->setNumIdUsuario($dados['IdUsuario'] ?: 100000001);
        $objBlocoDeTramiteDTO->setStrDescricao($dados['Descricao'] ?: 'Bloco para envio');
        $objBlocoDeTramiteDTO->setStrIdxBloco($dados['IdxBloco'] ?: 'Bloco para envio');
        $objBlocoDeTramiteDTO->setStrStaTipo($dados['IdxBloco'] ?: 'I');
        $objBlocoDeTramiteDTO->setStrStaEstado($dados['IdxBloco'] ?: 'A');
        $objBlocoDeTramiteDTO->setNumOrdem($ordem);

        $objBlocoDeTramiteDB = new \TramiteEmBlocoBD(\BancoSEI::getInstance());
        $objBlocoDeTramiteDB->cadastrar($objBlocoDeTramiteDTO);

        return $objBlocoDeTramiteDTO;
    }

    public function excluir($id)
    {
        $dto = new \TramiteEmBlocoDTO();
        $dto->setNumId($id);
        $dto->retNumId();

        $objBD = new \TramiteEmBlocoBD(\BancoSEI::getInstance());
        $objBD->excluir($dto);
    }

    private function pegarProximaOrdem($unidade)
    {
        $tramiteEmBlocoDTO = new \TramiteEmBlocoDTO();
        $tramiteEmBlocoDTO->setNumIdUnidade($unidade);
        $tramiteEmBlocoDTO->setOrdNumOrdem(\InfraDTO::$TIPO_ORDENACAO_DESC);
        $tramiteEmBlocoDTO->retNumOrdem();
        $tramiteEmBlocoDTO->setNumMaxRegistrosRetorno(1);

        $objBD = new \TramiteEmBlocoBD(\BancoSEI::getInstance());
        $tramiteEmBlocoDTO = $objBD->consultar($tramiteEmBlocoDTO);

        if ($tramiteEmBlocoDTO == null) {
          $ordem = 1;
        } else {
          $ordem = $tramiteEmBlocoDTO->getNumOrdem() + 1;
        }

        return $ordem;
    }
}
