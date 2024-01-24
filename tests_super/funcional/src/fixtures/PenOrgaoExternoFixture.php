<?php

class PenOrgaoExternoFixture
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
        $penMapUnidadesFixture = new PenMapUnidadesFixture(self::$contexto, $dados);
        $penMapUnidadesFixture->gravar();

        $bancoOrgaoA = new DatabaseUtils(self::$contexto);
        $bancoOrgaoA->execute(
            "insert into md_pen_orgao_externo ".
            "(id,id_orgao_origem,str_orgao_origem,id_estrutura_origem,str_estrutura_origem,id_orgao_destino,str_orgao_destino,sin_ativo,id_unidade,dth_criacao) ".
            "values (?,?,?,?,?,?,?,?,?,?) ",
            array(
                999999,
                $dados['idOrigem'], $dados['nomeOrigem'], $dados['idRepositorio'], $dados['repositorioEstruturas'],
                $dados['id'], $dados['nome'],
                'S', 110000001, date('Y-m-d H:i:s')
            )
        );

        return 999999;
    }

    public function deletar(int $id): void
    {
        $bancoOrgaoA = new DatabaseUtils(self::$contexto);
        $bancoOrgaoA->execute("delete from md_pen_orgao_externo where id = ?", array($id));
    }
}
