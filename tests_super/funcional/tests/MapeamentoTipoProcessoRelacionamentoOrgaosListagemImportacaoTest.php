<?php

/**
 * Testes de mapeamento de tipos de processo e relacionamento entre orgãos
 * Listar mapeamento entre orgãos 
 * Importar tipos de processo para relacionamento
 */
class MapeamentoTipoProcessoRelacionamentoOrgaosListagemImportacaoTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $penOrgaoExternoId;

    /**
     * @inheritdoc
     * @return void
     */
    function setUp(): void
    {
        parent::setUp();
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $penOrgaoExternoFixture = new PenOrgaoExternoFixture(CONTEXTO_ORGAO_A);
        self::$penOrgaoExternoId = $penOrgaoExternoFixture->cadastrar([
            'idRepositorio' => self::$remetente['ID_REP_ESTRUTURAS'],
            'repositorioEstruturas' => self::$remetente['REP_ESTRUTURAS'],
            'id' => self::$remetente['ID_UNIDADE_ESTRUTURA'],
            'sigla' => self::$remetente['SIGLA_UNIDADE_ESTRUTURAS'],
            'nome' => self::$remetente['NOME_UNIDADE_ESTRUTURA'],
            'idOrigem' => self::$remetente['ID_UNIDADE_MAPEAMENTO_ORGAO_ORIGEM'],
            'nomeOrigem' => self::$remetente['NOME_UNIDADE_MAPEAMENTO_ORGAO_ORIGEM']
        ]);
    }

    /**
     * Teste para pesquisar mapeamento entre orgãos
     *
     * @Depends test_desativacao_mapeamento_orgao_externo
     *
     * @return void
     */
    public function test_pesquisar_mapeamento_orgao_externo()
    {
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaCadastroOrgaoExterno->navegarCadastroOrgaoExterno();

        // Buscar pesquisa vazia
        $this->paginaCadastroOrgaoExterno->selecionarPesquisa(self::$remetente['NOME_UNIDADE_MAPEAMENTO_ORGAO_ORIGEM'] . 'B');
        $nomeRepositorioCadastrado = $this->paginaCadastroOrgaoExterno->buscarNome(self::$remetente['NOME_UNIDADE_MAPEAMENTO_ORGAO_ORIGEM']);
        $this->assertNull($nomeRepositorioCadastrado);

        // Buscar pesquisa com sucesso
        $this->paginaCadastroOrgaoExterno->selecionarPesquisa(self::$remetente['NOME_UNIDADE_MAPEAMENTO_ORGAO_ORIGEM']);
        $nomeRepositorioCadastrado = $this->paginaCadastroOrgaoExterno->buscarNome(self::$remetente['NOME_UNIDADE_MAPEAMENTO_ORGAO_ORIGEM']);
        $this->assertNotNull($nomeRepositorioCadastrado);
    }

    function tearDown(): void
    {
        $penOrgaoExternoFixture = new PenOrgaoExternoFixture(CONTEXTO_ORGAO_A);
        $penOrgaoExternoFixture->deletar(self::$penOrgaoExternoId);

        parent::tearDown();
    }
}
