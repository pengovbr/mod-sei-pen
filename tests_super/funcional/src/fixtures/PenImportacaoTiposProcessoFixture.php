<?php

class PenImportacaoTiposProcessoFixture extends FixtureBase
{
    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    public function cadastrar($dados = [])
    {
        $objPenImportacaoTiposProcessoDTO = new \PenImportacaoTiposProcessoDTO();

        $objPenImportacaoTiposProcessoDTO->setDblId($dados['Id']);
        $objPenImportacaoTiposProcessoDTO->setNumIdMapOrgao($dados['id_map_orgao']);
        $objPenImportacaoTiposProcessoDTO->setNumIdTipoProcessoOrigem($dados['id_tipo_processo_origem']);
        $objPenImportacaoTiposProcessoDTO->setNumIdTipoProcessoDestino($dados['id_tipo_processo_destino']);
        $objPenImportacaoTiposProcessoDTO->setStrNomeTipoProcesso($dados['nome_tipo_processo']);
        $objPenImportacaoTiposProcessoDTO->setStrAtivo($dados['sin_ativo'] ?: 'S');
        $objPenImportacaoTiposProcessoDTO->setNumIdUnidade($dados['id_unidade'] ?: 110000001);
        $objPenImportacaoTiposProcessoDTO->setDthCriacao($dados['dth_criacao'] ?: \InfraData::getStrDataAtual());

        $objPenImportacaoTiposProcessoBD = new \PenImportacaoTiposProcessoBD(\BancoSEI::getInstance());
        $arrImportacaoTiposProcessoDTO = $objPenImportacaoTiposProcessoBD->cadastrar($objPenImportacaoTiposProcessoDTO);
        return $arrImportacaoTiposProcessoDTO;
    }

    public function consultar()
    {
        $objPenImportacaoTiposProcessoDTO = new \PenImportacaoTiposProcessoDTO();
        $objPenImportacaoTiposProcessoDTO->retTodos();
        $objPenImportacaoTiposProcessoBD = new \PenImportacaoTiposProcessoDTO(\BancoSEI::getInstance());
        $arrPenImportacaoTiposProcessoDTO = $objPenImportacaoTiposProcessoBD->listar($objPenImportacaoTiposProcessoDTO);
        return $arrPenImportacaoTiposProcessoDTO;
    }

    public function deletar(int $id): void
    {
        $objPenImportacaoTiposProcessoDTO = new \PenImportacaoTiposProcessoDTO();
        $objPenImportacaoTiposProcessoDTO->setDblId($id);
        
        $objPenImportacaoTiposProcessoBD = new \PenImportacaoTiposProcessoBD(\BancoSEI::getInstance());
        $objPenImportacaoTiposProcessoBD->excluir($objPenImportacaoTiposProcessoDTO);
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
    //     $bancoOrgaoA = new DatabaseUtils(self::$contexto);
    //     $tiposProcessos = $this->getTiposProcessos($dados['idMapeamento'], $dados['sinAtivo']);

    //     foreach ($tiposProcessos as $tipoProcesso) {
    //         $bancoOrgaoA->execute(
    //             "INSERT INTO md_pen_map_tipo_processo (id, id_map_orgao, id_tipo_processo_origem, nome_tipo_processo, sin_ativo, id_unidade, dth_criacao) ".
    //             "VALUES(?,?,?,?,?,?,?)",
    //             array(
    //                 $tipoProcesso[0],
    //                 $tipoProcesso[1],
    //                 $tipoProcesso[2],
    //                 $tipoProcesso[3],
    //                 $tipoProcesso[4],
    //                 $tipoProcesso[5],
    //                 $tipoProcesso[6]
    //             )
    //         );
    //     }

    //     return $tiposProcessos;
    // }

    // public function deletar($dados = []): void
    // {
    //     $bancoOrgaoA = new DatabaseUtils(self::$contexto);
    //     $tiposProcessos = $this->getTiposProcessos($dados['idMapeamento']);

    //     foreach ($tiposProcessos as $tipoProcesso) {
    //         $bancoOrgaoA->execute(
    //             "DELETE FROM md_pen_map_tipo_processo WHERE id = ?",
    //             array($tipoProcesso[0])
    //         );
    //     }

    // }

    // public function getTiposProcessos(int $idMapeamento, string $sinAtivo = 'S') 
    // {
    //     $tiposProcessos = array();

    //     $tiposProcessos[] = [9997, $idMapeamento, 100000347, utf8_encode('Acompanhamento Legislativo: Câmara dos Deputados'), $sinAtivo, 110000001, date('Y-m-d H:i:s')];
    //     $tiposProcessos[] = [9998, $idMapeamento, 100000348, utf8_encode('Acompanhamento Legislativo: Congresso Nacional'), $sinAtivo, 110000001, date('Y-m-d H:i:s')];
    //     $tiposProcessos[] = [9999, $idMapeamento, 100000425, utf8_encode('mauro teste'), $sinAtivo, 110000001, date('Y-m-d H:i:s')];

    //     return $tiposProcessos;
    // }
}
