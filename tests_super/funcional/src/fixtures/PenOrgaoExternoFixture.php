<?php

class PenOrgaoExternoFixture extends FixtureBase
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

    public function consultar()
    {
        $objPenOrgaoExternoDTO = new \PenOrgaoExternoDTO();
        $objPenOrgaoExternoDTO->retTodos();
        $objPenOrgaoExternoBD = new \PenOrgaoExternoBD(\BancoSEI::getInstance());
        $arrPenOrgaoExternoDTO = $objPenOrgaoExternoBD->listar($objPenOrgaoExternoDTO);
        return $arrPenOrgaoExternoDTO;
    }

    public function deletar(int $id): void
    {
        $objPenOrgaoExternoDTO = new \PenOrgaoExternoDTO();
        $objPenOrgaoExternoDTO->setDblId($id);
        
        $objPenOrgaoExternoBD = new \PenOrgaoExternoBD(\BancoSEI::getInstance());
        $objPenOrgaoExternoBD->excluir($objPenOrgaoExternoDTO);
    }

    // private static $contexto;

    // public function __construct(string $contexto)
    // {
    //     self::$contexto = $contexto;
    // }

    // protected function inicializarObjInfraIBanco()
    // {
    //     return \BancoSEI::getInstance();
    // }

    // public function cadastrar($dados = [])
    // {
    //     $penMapUnidadesFixture = new PenMapUnidadesFixture(self::$contexto, $dados);
    //     $penMapUnidadesFixture->gravar();

    //     $bancoOrgaoA = new DatabaseUtils(self::$contexto);
        //     $bancoOrgaoA->execute(
    //         "insert into md_pen_orgao_externo ".
    //         "(id,id_orgao_origem,str_orgao_origem,id_estrutura_origem,str_estrutura_origem,id_orgao_destino,str_orgao_destino,sin_ativo,id_unidade,dth_criacao) ".
    //         "values (?,?,?,?,?,?,?,?,?,?) ",
    //         array(
    //             999999,
    //             $dados['idOrigem'], $dados['nomeOrigem'], $dados['idRepositorio'], $dados['repositorioEstruturas'],
    //             $dados['id'], $dados['nome'],
    //             'S', 110000001, date('Y-m-d H:i:s')
    //         )
    //     );

    //     return 999999;
    // }

    // public function deletar(int $id): void
    // {
    //     $bancoOrgaoA = new DatabaseUtils(self::$contexto);
    //     $bancoOrgaoA->execute("delete from md_pen_orgao_externo where id = ?", array($id));
    // }
}
