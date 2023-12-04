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
            'idRepositorioOrigem' => self::$remetente['ID_REP_ESTRUTURAS'],
            'repositorioEstruturasOrigem' => self::$remetente['REP_ESTRUTURAS'],
            'idOrgaoOrigem' => self::$remetente['ID_ESTRUTURA'],
            'nomeOrgaoOrigem' => self::$remetente['NOME_UNIDADE_ESTRUTURA'],
            'idOrgaoDestino' => 110000001,
            'nomeOrgaoDestino' => 'TESTE-Unidade de Teste 1',
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
        $this->paginaCadastroOrgaoExterno->selecionarPesquisa(self::$remetente['NOME_UNIDADE_ESTRUTURA'] . 'B');
        $nomeRepositorioCadastrado = $this->paginaCadastroOrgaoExterno->buscarNome(self::$remetente['NOME_UNIDADE_ESTRUTURA']);
        $this->assertNull($nomeRepositorioCadastrado);

        // Buscar pesquisa com sucesso
        $this->paginaCadastroOrgaoExterno->selecionarPesquisa(self::$remetente['NOME_UNIDADE_ESTRUTURA']);
        $nomeRepositorioCadastrado = $this->paginaCadastroOrgaoExterno->buscarNome(self::$remetente['NOME_UNIDADE_ESTRUTURA']);
        $this->assertNotNull($nomeRepositorioCadastrado);
    }
}
