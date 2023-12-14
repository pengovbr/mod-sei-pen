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
}
