<?php

/**
 * Responsável por cadastrar novo mapeamento de unidades caso não exista
 */
class PenMapUnidadesFixture
{
    private static $bancoOrgaoA;
    private static $dados;

    /**
     * @param string $contexto
     * @param array $dados
     */
    public function __construct(string $contexto, array $dados)
    {
        self::$bancoOrgaoA = new DatabaseUtils($contexto);
        self::$dados = $dados;
    }

    /**
     * Consulta mapeamento de unidade
     * Se existe atualiza sigla e nome
     * Se não existe cadastra um novo
     *
     * @return void
     */
    public function gravar(): void
    {
        $penUnidade = $this->consultar();
        if (!empty($penUnidade)) {
            $this->atualizar();
        } else {
            $this->cadastrar();
        }
    }

    /**
     * Consultar mapeamento de unidade
     *
     * @return array|null
     */
    public function consultar()
    {
        return self::$bancoOrgaoA->query(
            'select id_unidade, id_unidade_rh from md_pen_unidade where id_unidade = ? and id_unidade_rh = ?',
            array(110000001, self::$dados['id'])
        );
    }

    /**
     * Cadastrar mapeamento de unidade
     *
     * @return void
     */
    public function cadastrar(): void
    {
        self::$bancoOrgaoA->execute(
            "INSERT INTO md_pen_unidade (id_unidade, id_unidade_rh, sigla_unidade_rh, nome_unidade_rh) ".
            "VALUES(?, ?, ?, ?)",
            array(110000001, self::$dados['id'], self::$dados['sigla'], self::$dados['nome'])
        );
    }

    /**
     * Atualizar mapeamento de unidade
     *
     * @return void
     */
    public function atualizar(): void
    {
        self::$bancoOrgaoA->execute(
            "UPDATE md_pen_unidade SET sigla_unidade_rh = ?, nome_unidade_rh = ? ".
            "WHERE id_unidade = ?  AND id_unidade_rh = ?",
            array(self::$dados['sigla'], self::$dados['nome'], 110000001, self::$dados['id'])
        );
    }

    /**
     * Deletear mapeamento de unidade
     *
     * @return void
     */
    public function deletar(): void
    {
        self::$bancoOrgaoA->execute(
            "DELETE FROM md_pen_unidade WHERE id_unidade = ? and id_unidade_rh = ?",
            array(110000001, self::$dados['id'])
        );
    }
}
