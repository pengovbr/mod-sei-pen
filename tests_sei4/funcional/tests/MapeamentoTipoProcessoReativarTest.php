<?php

/**
 * Testes de mapeamento de tipos de processo reativar
 * Reativar tipos de processos
 *
 * Execution Groups
 * @group execute_alone_group2
 */
class MapeamentoTipoProcessoReativarTest extends CenarioBaseTestCase
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

        $penOrgaoExternoFixture = new PenOrgaoExternoFixture(CONTEXTO_ORGAO_A);
        self::$penOrgaoExternoId = $penOrgaoExternoFixture->cadastrar([
            'idRepositorio' => self::$remetente['ID_REP_ESTRUTURAS'],
            'repositorioEstruturas' => self::$remetente['REP_ESTRUTURAS'],
            'id' => self::$remetente['ID_ESTRUTURA'],
            'sigla' => self::$remetente['SIGLA_ESTRUTURA'],
            'nome' => self::$remetente['NOME_UNIDADE'],
            'idOrigem' => self::$destinatario['ID_ESTRUTURA'],
            'nomeOrigem' => self::$destinatario['NOME_UNIDADE']
        ]);

        $importacaoTiposProcessoFixture = new ImportacaoTiposProcessoFixture(CONTEXTO_ORGAO_A);
        $importacaoTiposProcessoFixture->cadastrar([
            'idMapeamento' => self::$penOrgaoExternoId,
            'sinAtivo' => 'N'
        ]);
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

        $this->paginaTipoProcessoReativar->navegarTipoProcessoReativar();

        $this->paginaTipoProcessoReativar->reativarMapeamento();
        $this->waitUntil(function ($testCase)  {
            $testCase->frame(null);
            $menssagemValidacao = utf8_encode('Mapeamento de Tipo de Processo foi reativado com sucesso.');
            $this->assertStringContainsString($menssagemValidacao, $testCase->byId('divInfraMsg0')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);
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

        $this->paginaTipoProcessoReativar->navegarTipoProcessoReativar();

        $this->paginaTipoProcessoReativar->reativarMapeamentoCheckbox();
        $this->waitUntil(function ($testCase)  {
            $testCase->frame(null);
            $menssagemValidacao = utf8_encode('Mapeamento de Tipo de Processo foi reativado com sucesso.');
            $this->assertStringContainsString($menssagemValidacao, $testCase->byId('divInfraMsg0')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);
    }

    public static function tearDownAfterClass(): void
    {
        $importacaoTiposProcessoFixture = new ImportacaoTiposProcessoFixture(CONTEXTO_ORGAO_A);
        $importacaoTiposProcessoFixture->deletar(['idMapeamento' => self::$penOrgaoExternoId]);

        $penOrgaoExternoFixture = new PenOrgaoExternoFixture(CONTEXTO_ORGAO_A);
        $penOrgaoExternoFixture->deletar(self::$penOrgaoExternoId);

        parent::tearDownAfterClass();
    }
}
