<?php

/**
 * Testes de mapeamento de tipos de processo e relacionamento entre orgãos
 * Exportar tipos de processos
 * Pesquisar tipos de processos
 *
 * Execution Groups
 * @group exxecute_alone
 */
class MapeamentoTipoProcessoExportarTest extends CenarioBaseTestCase
{
    public static $remetente;

    /**
     * Teste de exportação de tipos de processos
     *
     * @return void
     */
    public function test_exportar_tipos_de_processo()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaExportarTiposProcesso->navegarExportarTiposProcessos();

        $this->paginaExportarTiposProcesso->selecionarParaExportar();
        $this->assertEquals(
            $this->paginaExportarTiposProcesso->verificarExisteBotao('btnExportarModal'),
            'Exportar'
        );
        $this->assertEquals(
            $this->paginaExportarTiposProcesso->verificarExisteBotao('btnFecharModal'),
            'Fechar'
        );
        $this->paginaExportarTiposProcesso->verificarQuantidadeDeLinhasSelecionadas();
        $this->paginaExportarTiposProcesso->btnExportar();
    }

    /**
     * Teste para pesquisar tipos de processos
     *
     * @return void
     */
    public function test_pesquisar_tipos_de_processos()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaExportarTiposProcesso->navegarExportarTiposProcessos();
        $this->paginaExportarTiposProcesso->selecionarPesquisa();
        sleep(1);
        $this->assertTrue($this->paginaExportarTiposProcesso->buscarPesquisa());
    }
}
