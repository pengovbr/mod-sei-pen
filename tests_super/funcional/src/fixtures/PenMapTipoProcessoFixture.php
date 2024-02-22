<?php

/**
 * Responsável por cadastrar novo mapeamento de tipo de processo
 */
class PenMapTipoProcessoFixture extends FixtureBase
{

    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    public function cadastrar($dados = [])
    {

        $objPenMapTipoProcedimentoDTO = new \PenMapTipoProcedimentoDTO();
        $objPenMapTipoProcedimentoDTO->setNumIdMapOrgao($dados['IdMapOrgao']);
        $objPenMapTipoProcedimentoDTO->setNumIdTipoProcessoOrigem($dados['IdTipoProcessoOrigem'] ?: 100000261);
        $objPenMapTipoProcedimentoDTO->setNumIdTipoProcessoDestino($dados['IdTipoProcessoDestino'] ?: 100000261);
        $objPenMapTipoProcedimentoDTO->setStrNomeTipoProcesso($dados['NomeTipoProcesso'] ?: "Arrecadação: Receita");
        $objPenMapTipoProcedimentoDTO->setStrAtivo($dados['Ativo'] ?: 'S');
        $objPenMapTipoProcedimentoDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
        $objPenMapTipoProcedimentoDTO->setDthRegistro($dados['Registro'] ?: \InfraData::getStrDataAtual());

        $objPenMapTipoProcedimentoBD = new \PenMapTipoProcedimentoBD(\BancoSEI::getInstance());
        $arrPenMapTipoProcedimentoDTO = $objPenMapTipoProcedimentoBD->cadastrar($objPenMapTipoProcedimentoDTO);
        return $arrPenMapTipoProcedimentoDTO;
    }
   
}
