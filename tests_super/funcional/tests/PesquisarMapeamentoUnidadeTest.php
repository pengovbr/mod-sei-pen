<?php

class PesquisarMapeamentoUnidadeTest extends CenarioBaseTestCase
{
    public static $remetente;

    /**
     * Teste da listagem de mapeamento de unidades
     *
     * @group envio
     * @large
     * 
     * @Depends CenarioBaseTestCase::setUpBeforeClass
     *
     * @return void
     */
    public function test_listagem_mapeamento_unidade_sigla_nao_encontrada()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->navegarMapeamentoUnidade();
        $this->byId('txtSiglaUnidade')->value('00000');
        $this->byId('btnPesquisar')->click();

        $mensagem = utf8_encode('Nenhum mapeamento foi encontrado');

        $this->waitUntil(function ($testCase) use ($mensagem) {
            $this->assertStringContainsString($mensagem, $testCase->byCssSelector('body')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);
    }

    public function test_listagem_mapeamento_unidade_sigla_encontrada()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->navegarMapeamentoUnidade();
        $this->byId('txtSiglaUnidade')->value('TESTE');
        $this->byId('btnPesquisar')->click();

        $mensagem = utf8_encode('TESTE');
        
        $this->waitUntil(function ($testCase) use ($mensagem) {
            $this->assertStringContainsString($mensagem, $testCase->byCssSelector('body')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);
    }

    public function test_listagem_mapeamento_unidade_descricao_nao_encontrada()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->navegarMapeamentoUnidade();
        $this->byId('txtDescricaoUnidade')->value('00000');
        $this->byId('btnPesquisar')->click();

        $mensagem = utf8_encode('Nenhum mapeamento foi encontrado');
        
        $this->waitUntil(function ($testCase) use ($mensagem) {
            $this->assertStringContainsString($mensagem, $testCase->byCssSelector('body')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);
    }

    public function test_listagem_mapeamento_unidade_descricao_encontrada()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->navegarMapeamentoUnidade();
        $this->byId('txtDescricaoUnidade')->value('Unidade de Teste');
        $this->byId('btnPesquisar')->click();

        $mensagem = utf8_encode('Unidade de Teste');
        
        $this->waitUntil(function ($testCase) use ($mensagem) {
            $this->assertStringContainsString($mensagem, $testCase->byCssSelector('body')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);
    }

}