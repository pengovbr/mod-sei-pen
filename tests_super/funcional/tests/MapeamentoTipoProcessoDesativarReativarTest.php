<?php

/**
 * Testes de mapeamento de tipos de processo e relacionamento entre orgãos
 * Desativar e reativar mapeamento entre orgãos
 */
class MapeamentoTipoProcessoDesativarReativarTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $penOrgaoExternoId;

    /**
     * Teste de desativação de um Relacionamento entre Órgãos
     *
     * @large
     *
     * @return void
     */
    public function test_desativacao_mapeamento_orgao_externo()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        $penOrgaoExternoFixture = new PenOrgaoExternoFixture();
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
        $this->paginaTramiteMapeamentoOrgaoExterno->navegarRelacionamentoEntreOrgaos();

        $this->paginaTramiteMapeamentoOrgaoExterno->selectEstado("Ativo");
        $this->paginaTramiteMapeamentoOrgaoExterno->desativarMapeamento();
        $this->waitUntil(function ($testCase)  {
            $testCase->frame(null);
            $menssagemValidacao = utf8_encode('Relacionamento entre Unidades foi desativado com sucesso.');
            $this->assertStringContainsString($menssagemValidacao, $testCase->byId('divInfraMsg0')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);
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

        $this->paginaTramiteMapeamentoOrgaoExterno->selectEstado("Inativo");
        $this->paginaTramiteMapeamentoOrgaoExterno->reativarMapeamento();
        $this->waitUntil(function ($testCase)  {
            $testCase->frame(null);
            $menssagemValidacao = utf8_encode('Relacionamento entre Unidades foi reativado com sucesso.');
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
    public function test_desativacao_checkbox_mapeamento_orgao_externo()
    {
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaTramiteMapeamentoOrgaoExterno->navegarRelacionamentoEntreOrgaos();

        $this->paginaTramiteMapeamentoOrgaoExterno->selectEstado("Ativo");
        $this->paginaTramiteMapeamentoOrgaoExterno->desativarMapeamentoCheckbox();
        $this->waitUntil(function ($testCase)  {
            $testCase->frame(null);
            $menssagemValidacao = utf8_encode('Relacionamento entre Unidades foi desativado com sucesso.');
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
        $this->paginaTramiteMapeamentoOrgaoExterno->navegarRelacionamentoEntreOrgaos();

        $this->paginaTramiteMapeamentoOrgaoExterno->selectEstado("Inativo");
        $this->paginaTramiteMapeamentoOrgaoExterno->reativarMapeamentoCheckbox();
        $this->waitUntil(function ($testCase)  {
            $testCase->frame(null);
            $menssagemValidacao = utf8_encode('Relacionamento entre Unidades foi reativado com sucesso.');
            $this->assertStringContainsString($menssagemValidacao, $testCase->byId('divInfraMsg0')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);
    }

    public static function tearDownAfterClass(): void
    {
        $penOrgaoExternoFixture = new PenOrgaoExternoFixture(CONTEXTO_ORGAO_A);
        $penOrgaoExternoFixture->deletar(self::$penOrgaoExternoId);

        parent::tearDownAfterClass();
    }
}
