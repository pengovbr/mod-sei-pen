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

        $objPenOrgaoExternoDTO->setNumIdOrgaoOrigem($dados['IdOrgaoOrigem']);
        $objPenOrgaoExternoDTO->setStrOrgaoOrigem($dados['OrgaoOrigem']);
        $objPenOrgaoExternoDTO->setNumIdEstrutaOrganizacionalOrigem($dados['IdEstrutaOrganizacionalOrigem'] ?: 5);
        $objPenOrgaoExternoDTO->setStrEstrutaOrganizacionalOrigem($dados['EstrutaOrganizacionalOrigem'] ?: 'RE CGPRO');
        $objPenOrgaoExternoDTO->setNumIdOrgaoDestino($dados['IdOrgaoDestino']);
        $objPenOrgaoExternoDTO->setStrOrgaoDestino($dados['OrgaoDestino']);
        $objPenOrgaoExternoDTO->setStrAtivo($dados['Ativo'] ?: 'S');
        $objPenOrgaoExternoDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
        $objPenOrgaoExternoDTO->setDthRegistro($dados['Registro'] ?: \InfraData::getStrDataAtual());

        $objPenOrgaoExternoBD = new \PenOrgaoExternoBD(\BancoSEI::getInstance());
        $arrPenOrgaoExternoDTO = $objPenOrgaoExternoBD->cadastrar($objPenOrgaoExternoDTO);
        return $arrPenOrgaoExternoDTO;
    }

}
