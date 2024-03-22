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
        $objPenMapTipoProcedimentoDTO->setNumIdTipoProcessoOrigem($dados['IdTipoProcessoOrigem'] ?: 100000269);
        $objPenMapTipoProcedimentoDTO->setNumIdTipoProcessoDestino($dados['IdTipoProcessoDestino'] ?: 100000269);
        $objPenMapTipoProcedimentoDTO->setStrNomeTipoProcesso($dados['NomeTipoProcesso'] ?: "Contabilidade: DIRF");
        $objPenMapTipoProcedimentoDTO->setStrAtivo($dados['Ativo'] ?: 'S');
        $objPenMapTipoProcedimentoDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
        $objPenMapTipoProcedimentoDTO->setDthRegistro($dados['Registro'] ?: \InfraData::getStrDataAtual());

        $objPenMapTipoProcedimentoBD = new \PenMapTipoProcedimentoBD(\BancoSEI::getInstance());
        $arrPenMapTipoProcedimentoDTO = $objPenMapTipoProcedimentoBD->cadastrar($objPenMapTipoProcedimentoDTO);
        return $arrPenMapTipoProcedimentoDTO;
    }
   
    public function excluir($dados = [])
    {
        $objPenMapTipoProcedimentoDTO = new \PenMapTipoProcedimentoDTO();
        $objPenMapTipoProcedimentoDTO->setDblId($dados['Id']);

        $objPenMapTipoProcedimentoBD = new \PenMapTipoProcedimentoBD(\BancoSEI::getInstance());
        return $objPenMapTipoProcedimentoBD->excluir($objPenMapTipoProcedimentoDTO);
    }
}
