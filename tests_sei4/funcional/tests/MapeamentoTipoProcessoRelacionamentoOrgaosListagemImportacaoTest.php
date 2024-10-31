<?php

/**
 * Testes de mapeamento de tipos de processo e relacionamento entre orgãos
 * Listar mapeamento entre orgãos 
 * Importar tipos de processo para relacionamento
 *
 * Execution Groups
 * @group execute_alone_group1
 */
class MapeamentoTipoProcessoRelacionamentoOrgaosListagemImportacaoTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $penOrgaoExternoId;

    /**
     * @inheritdoc
     * @return void
     */
    function setUp(): void
    {
        parent::setUp();
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        
        $penOrgaoExternoFixture = new \PenOrgaoExternoFixture();
        $objPenOrgaoExternoDTO = $penOrgaoExternoFixture->carregar([
            'IdRepositorio' => self::$remetente['ID_REP_ESTRUTURAS'],
            'RepositorioEstruturas' => self::$remetente['REP_ESTRUTURAS'],
            'Id' => self::$remetente['ID_ESTRUTURA'],
            'Sigla' => self::$remetente['SIGLA_ESTRUTURA'],
            'Nome' => self::$remetente['NOME_UNIDADE'],
            'IdOrigem' => self::$destinatario['ID_ESTRUTURA'],
            'NomeOrigem' => self::$destinatario['NOME_UNIDADE']
        ]);
    
        self::$penOrgaoExternoId = $objPenOrgaoExternoDTO->getDblId();
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
        $this->paginaCadastroOrgaoExterno->selecionarPesquisa(self::$destinatario['NOME_UNIDADE'] . 'B');
        $nomeRepositorioCadastrado = $this->paginaCadastroOrgaoExterno->buscarNome(self::$destinatario['NOME_UNIDADE']);
        $this->assertNull($nomeRepositorioCadastrado);

        // Buscar pesquisa com sucesso
        $this->paginaCadastroOrgaoExterno->selecionarPesquisa(self::$destinatario['NOME_UNIDADE']);
        $nomeRepositorioCadastrado = $this->paginaCadastroOrgaoExterno->buscarNome(self::$destinatario['NOME_UNIDADE']);
        $this->assertNotNull($nomeRepositorioCadastrado);
        
        $this->sairSistema();
    }

    public static function tearDownAfterClass(): void
    {
        $penOrgaoExternoFixture = new \PenOrgaoExternoFixture();
        $penOrgaoExternoFixture->remover([
            'Id' => self::$penOrgaoExternoId,
        ]);

        parent::tearDownAfterClass();
    }
}