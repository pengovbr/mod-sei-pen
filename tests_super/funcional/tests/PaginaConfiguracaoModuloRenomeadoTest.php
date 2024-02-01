<?php

class PaginaConfiguracaoModuloRenomeadoTest extends CenarioBaseTestCase
{
    protected static $remetente;
    protected static $destinatario;

    function setUp(): void
    {
        parent::setUp();
    }

    public function test_pagina_configuracao_modulo()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaConfiguracaoModuloRenomeado->navegarPaginaConfiguracaoModulo();

        $value = $this->paginaConfiguracaoModuloRenomeado->getTituloPaginaConfiguracao();

        $menssagemValidacao = utf8_encode('Parâmetros de Configuração do Módulo de Tramitações Tramita GOV.BR');
 
        $this->assertStringContainsString(
            $menssagemValidacao,
            $value
        );
    }

    public function test_pagina_novo_mapeamento_unidade()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaConfiguracaoModuloRenomeado->navegarPaginaNovoMapeamentoUnidade();

        $value = $this->paginaConfiguracaoModuloRenomeado->getTituloPaginaNovoMapeamentoUnidade();

        $menssagemValidacao = utf8_encode('Unidades do Tramita GOV.BR (Estruturas Organizacionais):');
 
        $this->assertStringContainsString(
            $menssagemValidacao,
            $value
        );
    }

    public function test_pagina_hipotese_restricao_padrao()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaConfiguracaoModuloRenomeado->navegarPaginaHipoteseRestricaoPadrao();

        $value = $this->paginaConfiguracaoModuloRenomeado->getTituloPaginaHipoteseRestricaoPadrao();

        $menssagemValidacao = utf8_encode('Hipótese de Restrição Padrão - Tramitação Tramita GOV.BR');
 
        $this->assertStringContainsString(
            $menssagemValidacao,
            $value
        );
    }
}
