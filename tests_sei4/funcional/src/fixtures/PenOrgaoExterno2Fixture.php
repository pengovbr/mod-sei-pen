<?php

class PenOrgaoExterno2Fixture extends FixtureBase
{

    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    public function cadastrar($dados = [])
    {
        $objPenOrgaoExternoDTO = new \PenOrgaoExternoDTO();

        $objPenOrgaoExternoDTO->setNumIdOrgaoOrigem($dados['idOrgaoOrigem']);
        $objPenOrgaoExternoDTO->setStrOrgaoOrigem($dados['orgaoOrigem']);
        $objPenOrgaoExternoDTO->setNumIdEstrutaOrganizacionalOrigem($dados['idEstrutaOrganizacionalOrigem'] ?: 5);
        $objPenOrgaoExternoDTO->setStrEstrutaOrganizacionalOrigem($dados['estrutaOrganizacionalOrigem'] ?: 'RE CGPRO');
        $objPenOrgaoExternoDTO->setNumIdOrgaoDestino($dados['idOrgaoDestino']);
        $objPenOrgaoExternoDTO->setStrOrgaoDestino($dados['orgaoDestino']);
        $objPenOrgaoExternoDTO->setStrAtivo($dados['ativo'] ?: 'S');
        $objPenOrgaoExternoDTO->setNumIdUnidade($dados['idUnidade'] ?: 110000001);
        $objPenOrgaoExternoDTO->setDthRegistro($dados['registro'] ?: \InfraData::getStrDataAtual());

        $objPenOrgaoExternoBD = new \PenOrgaoExternoBD(\BancoSEI::getInstance());
        $arrPenOrgaoExternoDTO = $objPenOrgaoExternoBD->cadastrar($objPenOrgaoExternoDTO);
        return $arrPenOrgaoExternoDTO;
    }

    /**
     * Deletear mapeamento de unidade
     * 
     * @return void
     */
    public function deletar($dados = [])
    {
        $objPenOrgaoExternoDTO = new \PenOrgaoExternoDTO();
        $objPenOrgaoExternoDTO->setId($dados['id'] ?: 1);
        $objPenUnidadeBD = new \PenUnidadeBD(\BancoSEI::getInstance());
        $arrPenOrgaoExternoDTO = $objPenUnidadeBD->excluir($objPenOrgaoExternoDTO);
        return $arrPenOrgaoExternoDTO;
    }
}
