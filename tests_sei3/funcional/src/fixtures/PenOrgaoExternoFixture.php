<?php

// use Tests\Funcional\Fixture;

class PenOrgaoExternoFixture // extends Fixture
{
    private static $contexto;

    public function __construct(string $contexto)
    {
        self::$contexto = $contexto;
    }

    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    public function cadastrar($dados = [])
    {
        // $objSessaoSEI = \SessaoSEI::getInstance();

        // $objPenOrgaoExternoDTO = new \PenOrgaoExternoDTO();
        // $objPenOrgaoExternoDTO->setNumIdUnidade($objSessaoSEI->getNumIdUnidadeAtual());
        // $objPenOrgaoExternoDTO->setDthRegistro(date('d/m/Y H:i:s'));
        // // Órgão de origem
        // $objPenOrgaoExternoDTO->setNumIdEstrutaOrganizacionalOrigem($dados['idRepositorioOrigem']);
        // $objPenOrgaoExternoDTO->setStrEstrutaOrganizacionalOrigem($dados['repositorioEstruturasOrigem']);
        // $objPenOrgaoExternoDTO->setNumIdOrgaoOrigem($dados['idOrgaoOrigem']);
        // $objPenOrgaoExternoDTO->setStrOrgaoOrigem($dados['nomeOrgaoOrigem']);
        // // Órgão de destino
        // $objPenOrgaoExternoDTO->setNumIdOrgaoDestino($dados['idOrgaoDestino']);
        // $objPenOrgaoExternoDTO->setStrOrgaoDestino($dados['nomeOrgaoDestino']);

        // $objPenOrgaoExternoRN = new \PenOrgaoExternoRN();
        // $objRelProtocoloProtocoloDTO = $objPenOrgaoExternoRN->cadastrar($objPenOrgaoExternoDTO);

        // return $objRelProtocoloProtocoloDTO;

        $bancoOrgaoA = new DatabaseUtils(self::$contexto);
        $bancoOrgaoA->execute(
            "insert into md_pen_orgao_externo ".
            "(id,id_orgao_origem,str_orgao_origem,id_estrutura_origem,str_estrutura_origem,id_orgao_destino,str_orgao_destino,sin_ativo,id_unidade,dth_criacao) ".
            "values (?,?,?,?,?,?,?,?,?,?) ",
            array(
                999999,
                $dados['idOrgaoOrigem'], $dados['nomeOrgaoOrigem'], $dados['idRepositorioOrigem'], $dados['repositorioEstruturasOrigem'],
                $dados['idOrgaoDestino'], $dados['nomeOrgaoDestino'],
                'S', 110000001, date('Y-m-d H:i:s')
            )
        );

        return 999999;
    }

    public function deletar(int $id): void
    {
        // $objPenOrgaoExternoDTO = new \PenOrgaoExternoDTO();
        // $objPenOrgaoExternoDTO->setDblId($id);

        // $objPenOrgaoExternoRN = new \PenOrgaoExternoRN();
        // $objPenOrgaoExternoRN->excluir($objPenOrgaoExternoDTO);

        $bancoOrgaoA = new DatabaseUtils(self::$contexto);
        $bancoOrgaoA->execute("delete from md_pen_orgao_externo where id = ?", array($id));
    }
}
