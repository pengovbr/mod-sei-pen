<?php

/**
 * Testes de mapeamento de tipos de processo e relacionamento entre orgãos
 * Listar mapeamento entre orgãos 
 * Importar tipos de processo para relacionamento
 */
class MapeamentoTipoProcessoRelacionamentoOrgaosListagemImportacaoTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $penOrgaoExternoId;

    /**
     * Teste para pesquisar mapeamento entre orgãos
     *
     * @Depends test_desativacao_mapeamento_orgao_externo
     *
     * @return void
     */
    public function test_pesquisar_mapeamento_orgao_externo()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        $penOrgaoExternoFixture = new PenOrgaoExterno2Fixture();
        $penOrgaoExternoDTO = $penOrgaoExternoFixture->carregar([
            'IdEstrutaOrganizacionalOrigem' => self::$remetente['ID_REP_ESTRUTURAS'],
            'EstrutaOrganizacionalOrigem' => self::$remetente['REP_ESTRUTURAS'],
            'IdOrgaoDestino' => self::$remetente['ID_ESTRUTURA'],
            'OrgaoDestino' => self::$remetente['NOME_UNIDADE'],
            'IdOrgaoOrigem' => self::$destinatario['ID_ESTRUTURA'],
            'OrgaoOrigem' => self::$destinatario['NOME_UNIDADE']
        ]);

        self::$penOrgaoExternoId = $penOrgaoExternoDTO->getDblId();

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaCadastroOrgaoExterno->navegarCadastroOrgaoExterno();

        // Buscar pesquisa vazia
        $this->paginaCadastroOrgaoExterno->selecionarPesquisa(self::$destinatario['NOME_UNIDADE'] . 'B');
        $nomeRepositorioCadastrado = $this->paginaCadastroOrgaoExterno->buscarNome(self::$destinatario['NOME_UNIDADE']);
        $this->assertNull($nomeRepositorioCadastrado);

        // Buscar pesquisa com sucesso
        $this->paginaCadastroOrgaoExterno->selecionarPesquisa(self::$destinatario['NOME_UNIDADE']);
        $nomeRepositorioCadastrado = $this->paginaCadastroOrgaoExterno->buscarNome(self::$destinatario['NOME_UNIDADE']);
        $this->assertNotNull($nomeRepositorioCadastrado);
    }

    function tearDown(): void
    {
        $penOrgaoExternoFixture = new PenOrgaoExternoFixture(CONTEXTO_ORGAO_A);
        $penOrgaoExternoFixture->deletar(self::$penOrgaoExternoId);

        parent::tearDown();
    }
}
