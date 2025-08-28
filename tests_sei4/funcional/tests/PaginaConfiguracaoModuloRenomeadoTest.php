<?php

class PaginaConfiguracaoModuloRenomeadoTest extends FixtureCenarioBaseTestCase
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

        $menssagemValidacao = mb_convert_encoding('Parâmetros de Configuração do Módulo Tramita GOV.BR', 'UTF-8', 'ISO-8859-1');
 
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

        $menssagemValidacao = mb_convert_encoding('Unidades do Tramita GOV.BR (Estruturas Organizacionais):', 'UTF-8', 'ISO-8859-1');
 
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

        $menssagemValidacao = mb_convert_encoding('Hipótese de Restrição Padrão - Tramita GOV.BR', 'UTF-8', 'ISO-8859-1');
 
        $this->assertStringContainsString(
            $menssagemValidacao,
            $value
        );
    }
}
