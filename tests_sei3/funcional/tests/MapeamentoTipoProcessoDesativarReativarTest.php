<?php

/**
 * Testes de mapeamento de tipos de processo e relacionamento entre orgãos
 * Desativar e reativar mapeamento entre orgãos
 */
class MapeamentoTipoProcessoDesativarReativarTest extends CenarioBaseTestCase
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
     * Teste de desativação de um Relacionamento entre Órgãos
     *
     * @large
     *
     * @return void
     */
    public function test_desativacao_mapeamento_orgao_externo()
    {
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaTramiteMapeamentoOrgaoExterno->navegarRelacionamentoEntreOrgaos();

        $this->paginaTramiteMapeamentoOrgaoExterno->selectEstado('Ativo');
        $mensagemRetornoAlert = $this->paginaTramiteMapeamentoOrgaoExterno->desativarMapeamento();

        $menssagemValidacao = $this->paginaTramiteMapeamentoOrgaoExterno->mensagemValidacao('desativado');
        $this->assertStringContainsString($menssagemValidacao, $mensagemRetornoAlert);
    }

    /**
     * Teste de reativação de um Relacionamento entre Órgãos
     * 
     * @large
     *
     * @return void
     */
    public function test_reativacao_mapeamento_orgao_externo()
    {
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaTramiteMapeamentoOrgaoExterno->navegarRelacionamentoEntreOrgaos();

        $this->paginaTramiteMapeamentoOrgaoExterno->selectEstado('Inativo');
        $mensagemRetornoAlert = $this->paginaTramiteMapeamentoOrgaoExterno->reativarMapeamento();

        $menssagemValidacao = $this->paginaTramiteMapeamentoOrgaoExterno->mensagemValidacao('reativado');
        $this->assertStringContainsString($menssagemValidacao, $mensagemRetornoAlert);
    }

    /**
     * Teste de desativação de um Relacionamento entre Órgãos via checkbox
     *
     * @large
     *
     * @return void
     */
    public function test_desativacao_checkbox_mapeamento_orgao_externo()
    {
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaTramiteMapeamentoOrgaoExterno->navegarRelacionamentoEntreOrgaos();

        $this->paginaTramiteMapeamentoOrgaoExterno->selectEstado('Ativo');
        $mensagemRetornoAlert = $this->paginaTramiteMapeamentoOrgaoExterno->desativarMapeamentoCheckbox();

        $menssagemValidacao = $this->paginaTramiteMapeamentoOrgaoExterno->mensagemValidacao('desativado');
        $this->assertStringContainsString($menssagemValidacao, $mensagemRetornoAlert);

    }

    /**
     * Teste de desativação de um Relacionamento entre Órgãos via checkbox
     *
     * @large
     *
     * @return void
     */
    public function test_reativar_checkbox_mapeamento_orgao_externo()
    {
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaTramiteMapeamentoOrgaoExterno->navegarRelacionamentoEntreOrgaos();

        $this->paginaTramiteMapeamentoOrgaoExterno->selectEstado('Inativo');
        $mensagemRetornoAlert = $this->paginaTramiteMapeamentoOrgaoExterno->reativarMapeamentoCheckbox();

        $menssagemValidacao = $this->paginaTramiteMapeamentoOrgaoExterno->mensagemValidacao('reativado');
        $this->assertStringContainsString($menssagemValidacao, $mensagemRetornoAlert);
    }

    public static function tearDownAfterClass(): void
    {
        $penOrgaoExternoFixture = new PenOrgaoExternoFixture(CONTEXTO_ORGAO_A);
        $penOrgaoExternoFixture->deletar(self::$penOrgaoExternoId);

        parent::tearDownAfterClass();
    }
}
