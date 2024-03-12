<?php

/**
 * Testes de mapeamento de tipos de processo reativar
 * Reativar tipos de processos
 */
class MapeamentoTipoProcessoReativarTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $penOrgaoExternoId;

    /**
     * Teste de reativação de um Relacionamento entre Órgãos
     * 
     * @large
     *
     * @return void
     */
    public function test_reativacao_mapeamento_orgao_externo()
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

        $importacaoTiposProcessoFixture = new ImportacaoTiposProcessoFixture(CONTEXTO_ORGAO_A);
        $importacaoTiposProcessoFixture->cadastrar([
            'idMapeamento' => self::$penOrgaoExternoId,
            'sinAtivo' => 'N'
        ]);
    
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
