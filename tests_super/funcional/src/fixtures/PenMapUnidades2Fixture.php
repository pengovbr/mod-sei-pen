<?php

/**
 * Responsável por cadastrar novo mapeamento de unidades caso não exista
 */
class PenMapUnidades2Fixture extends FixtureBase
{

    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    /**
     * Consulta mapeamento de unidade
     * Se existe atualiza sigla e nome
     * Se não existe cadastra um novo
     *
     * @return void
     */
    protected function cadastrar($dados = [])
    {
        $penUnidade = $this->consultar($dados);
        if (!empty($penUnidade)) {
            $penUnidade = $this->atualizar($dados);
        } else {
            $penUnidade = $this->gravar($dados);
        }
        return $penUnidade;
    }

    /**
     * Consultar mapeamento de unidade
     *
     * @return array|null
     */
    public function consultar($dados = [])
    {
        $objPenUnidadeDTO = new \PenUnidadeDTO();
        $objPenUnidadeDTO->setNumIdUnidade(110000001);
        $objPenUnidadeDTO->retTodos();
        $objPenUnidadeBD = new \PenUnidadeBD(\BancoSEI::getInstance());
        $arrPenUnidadeDTO = $objPenUnidadeBD->listar($objPenUnidadeDTO);
        return $arrPenUnidadeDTO;
    }


    public function gravar($dados = [])
    {
        $objPenUnidadeDTO = new \PenUnidadeDTO();
        $objPenUnidadeDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
        $objPenUnidadeDTO->setNumIdUnidadeRH($dados['IdUnidadeRH'] ?: null);
        $objPenUnidadeDTO->setStrNomeUnidadeRH($dados['NomeUnidadeRH'] ?: null);
        $objPenUnidadeDTO->setStrSiglaUnidadeRH($dados['SiglaUnidadeRH'] ?: null);
        $objPenUnidadeDB = new \PenUnidadeBD(\BancoSEI::getInstance());
        $arrPenUnidadeDTO = $objPenUnidadeDB->cadastrar($objPenUnidadeDTO);
        return $arrPenUnidadeDTO;
    }
    /**
     * Atualizar mapeamento de unidade
     * 
     * @return void
      */
    public function atualizar($dados = [])
    {
        $objPenUnidadeDTO = new \PenUnidadeDTO();
        $objPenUnidadeDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
        $objPenUnidadeDTO->setNumIdUnidadeRH($dados['IdUnidadeRH']);
        $objPenUnidadeDTO->setStrNomeUnidadeRH($dados['NomeUnidadeRH']);
        $objPenUnidadeDTO->setStrSiglaUnidadeRH($dados['SiglaUnidadeRH']);
        $objPenUnidadeBD = new \PenUnidadeBD(\BancoSEI::getInstance());
        $arrPenUnidadeDTO = $objPenUnidadeBD->alterar($objPenUnidadeDTO);
        return $arrPenUnidadeDTO;
    }

    /**
     * Deletear mapeamento de unidade
     * 
     * @return void
     */
    public function deletar($dados = [])
    {
        $objPenUnidadeDTO = new \PenUnidadeDTO();
        $objPenUnidadeDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
        $objPenUnidadeDTO->setNumIdUnidadeRH($dados['IdUnidadeRH']);
        $objPenUnidadeBD = new \PenUnidadeBD(\BancoSEI::getInstance());
        $arrPenUnidadeDTO = $objPenUnidadeBD->excluir($objPenUnidadeDTO);
        return $arrPenUnidadeDTO;
    }
}
