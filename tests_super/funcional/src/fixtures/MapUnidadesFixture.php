<?php

/**
 * Responsável por cadastrar novo mapeamento de unidades caso não exista
 */
class MapUnidadesFixture extends \FixtureBase
{
    protected $objPenUnidadeDTO;
 
    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    /**
     * Cadastrar mapeamento de unidade
     *
     * @return \PenUnidadeDTO
     */
    protected function cadastrar($dados = [])
    {
        $this->objPenUnidadeDTO = $this->consultar($dados);
        if ($this->objPenUnidadeDTO != null) {
            return $this->objPenUnidadeDTO;
        }

        $this->objPenUnidadeDTO = new \PenUnidadeDTO();
        $this->objPenUnidadeDTO->setNumIdUnidade(110000001);
        $this->objPenUnidadeDTO->setNumIdUnidadeRH($dados['id']);
        $this->objPenUnidadeDTO->setStrSiglaUnidadeRH($dados['sigla']);
        $this->objPenUnidadeDTO->setStrNomeUnidadeRH($dados['nome']);

        $objPenUnidadeBD = new \PenUnidadeBD(\BancoSEI::getInstance());
        $this->objPenUnidadeDTO = $objPenUnidadeBD->cadastrar($this->objPenUnidadeDTO);

        return $this->objPenUnidadeDTO;
    }

    /**
     * Cadastrar mapeamento de unidade
     *
     * @return \PenUnidadeDTO
     */
    protected function consultar($dados = [])
    {
        $objPenUnidadeDTO = new \PenUnidadeDTO();
        $objPenUnidadeDTO->setNumIdUnidade(110000001);
        $objPenUnidadeDTO->setNumIdUnidadeRH($dados['id']);
        $objPenUnidadeDTO->retTodos();

        $objPenUnidadeBD = new \PenUnidadeBD(\BancoSEI::getInstance());

        return $objPenUnidadeBD->consultar($objPenUnidadeDTO);
    }
}
