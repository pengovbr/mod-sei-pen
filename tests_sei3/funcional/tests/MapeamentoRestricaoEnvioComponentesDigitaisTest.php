<?php

/**
 * Testes de mapeamento de envio de componentes digitais
 */
class MapeamentoRestricaoEnvioComponentesDigitaisTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $remetenteB;

    /**
     * Teste inicial de cadastro de mapeamento de componentes digitais
     *
     * @group mapeamento
     *
     * @return void
     */
    public function test_novo_mapeamento_componentes_digitais()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->navegarPara('pen_map_restricao_envio_comp_digitais_listar');
        $this->paginaCadastroMapEnvioCompDigitais->novo();
        $this->paginaCadastroMapEnvioCompDigitais->setarParametros(
            self::$remetente['REP_ESTRUTURAS'],
            self::$remetente['NOME_UNIDADE_ESTRUTURA']
        );
        $this->paginaCadastroMapEnvioCompDigitais->salvar();

        $nomeRepositorioCadastrado = $this->paginaCadastroMapEnvioCompDigitais->buscarNome(self::$remetente['REP_ESTRUTURAS']);
        $nomeUnidadeCadastrado = $this->paginaCadastroMapEnvioCompDigitais->buscarNome(self::$remetente['NOME_UNIDADE_ESTRUTURA']);  

        sleep(2);
        $this->assertNotNull($nomeRepositorioCadastrado);
        $this->assertNotNull($nomeUnidadeCadastrado);
        $mensagem = $this->paginaCadastroMapEnvioCompDigitais->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Mapeamentos de restrição de envio de componentes digitais cadastrado com sucesso.'),
            $mensagem
        );
    }

    /**
     * Teste para editar mapeamento de componentes digitais
     *
     * @group mapeamento
     *
     * @return void
     */
    public function test_editar_mapeamento_componentes_digitais() 
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$remetenteB = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->navegarPara('pen_map_restricao_envio_comp_digitais_listar');
        $this->paginaCadastroMapEnvioCompDigitais->editar();
        $this->paginaCadastroMapEnvioCompDigitais->setarParametros(
            self::$remetenteB['REP_ESTRUTURAS'],
            self::$remetenteB['NOME_UNIDADE_ESTRUTURA']
        );
        $this->paginaCadastroMapEnvioCompDigitais->salvar();

        $nomeRepositorioCadastrado = $this->paginaCadastroMapEnvioCompDigitais->buscarNome(self::$remetenteB['REP_ESTRUTURAS']);
        $nomeUnidadeCadastrado = $this->paginaCadastroMapEnvioCompDigitais->buscarNome(self::$remetenteB['NOME_UNIDADE_ESTRUTURA']);

        sleep(1);
        $this->assertNotNull($nomeRepositorioCadastrado);
        $this->assertNotNull($nomeUnidadeCadastrado);
        $mensagem = $this->paginaCadastroMapEnvioCompDigitais->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Mapeamentos de restrição de envio de componentes digitais atualizado com sucesso.'),
            $mensagem
        );
    }

    /**
     * Teste para pesquisar mapeamento de componentes digitais
     *
     * @group mapeamento
     *
     * @return void
     */
    public function test_pesquisar_mapeamento_componentes_digitais()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->navegarPara('pen_map_restricao_envio_comp_digitais_listar');

        // Buscar pesquisa vazia
        $this->paginaCadastroMapEnvioCompDigitais->selecionarPesquisa(self::$remetente['REP_ESTRUTURAS'] . 'B');
        $nomeRepositorioCadastrado = $this->paginaCadastroMapEnvioCompDigitais->buscarNome(self::$remetente['REP_ESTRUTURAS']);
        $this->assertNull($nomeRepositorioCadastrado);

        // Buscar pesquisa com sucesso
        $this->paginaCadastroMapEnvioCompDigitais->selecionarPesquisa(self::$remetente['REP_ESTRUTURAS']);
        $nomeRepositorioCadastrado = $this->paginaCadastroMapEnvioCompDigitais->buscarNome(self::$remetente['REP_ESTRUTURAS']);
        $this->assertNotNull($nomeRepositorioCadastrado);
    }

    /**
     * Teste para imprimir mapeamento de componentes digitais
     *
     * @group mapeamento
     *
     * @return void
     */
    public function test_imprimir_mapeamento_componentes_digitais()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->navegarPara('pen_map_restricao_envio_comp_digitais_listar');

        // Buscar pesquisa vazia
        $this->paginaCadastroMapEnvioCompDigitais->selecionarImprimir();
        $this->assertTrue(true);
    }

    /**
     * Teste para excluir mapeamento de componentes digitais
     *
     * @group mapeamento
     *
     * @return void
     */
    public function test_excluir_mapeamento_componentes_digitais()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->navegarPara('pen_map_restricao_envio_comp_digitais_listar');
        $this->paginaCadastroMapEnvioCompDigitais->selecionarExcluir();

        sleep(1);
        $mensagem = $this->paginaCadastroMapEnvioCompDigitais->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Mapeamento excluído com sucesso.'),
            $mensagem
        );
    }
}
