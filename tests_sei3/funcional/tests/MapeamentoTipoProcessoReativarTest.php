<?php

/**
 * Testes de mapeamento de tipos de processo reativar
 * Reativar tipos de processos
 */
class MapeamentoTipoProcessoReativarTest extends CenarioBaseTestCase
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
