<?php

/**
 * Testes de mapeamento de envio de envio parcial
 * 
 * Execution Groups
 * @group execute_alone_group1
 */
class MapeamentoEnvioParcialTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $remetenteB;

    /**
     * Teste inicial de cadastro de mapeamento de envio parcial
     *
     * @group mapeamento
     *
     * @return void
     */
    public function test_novo_mapeamento_envio_parcial_test()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaEnvioParcialListar->navegarEnvioParcialListar();
        $this->paginaCadastroMapEnvioCompDigitais->novo();
        $this->paginaCadastroMapEnvioCompDigitais->setarParametros(
            self::$remetente['REP_ESTRUTURAS'],
            self::$remetente['NOME_UNIDADE']
        );
        $this->paginaCadastroMapEnvioCompDigitais->salvar();

        $nomeRepositorioCadastrado = $this->paginaCadastroMapEnvioCompDigitais->buscarNome(self::$remetente['REP_ESTRUTURAS']);
        $nomeUnidadeCadastrado = $this->paginaCadastroMapEnvioCompDigitais->buscarNome(self::$remetente['NOME_UNIDADE']);

        sleep(1);
        $this->assertNotNull($nomeRepositorioCadastrado);
        $this->assertNotNull($nomeUnidadeCadastrado);
        $mensagem = $this->paginaCadastroMapEnvioCompDigitais->buscarMensagemAlerta();
        $this->assertStringContainsString(
            mb_convert_encoding('Mapeamento de Envio Parcial cadastrado com sucesso.', 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );
    }

    /**
     * Teste para editar mapeamento de envio parcial
     *
     * @group mapeamento
     *
     * @return void
     */
    public function test_editar_mapeamento_envio_parcial_test() 
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

        $this->paginaEnvioParcialListar->navegarEnvioParcialListar();
        $this->paginaCadastroMapEnvioCompDigitais->editar();
        $this->paginaCadastroMapEnvioCompDigitais->setarParametros(
            self::$remetenteB['REP_ESTRUTURAS'],
            self::$remetenteB['NOME_UNIDADE']
        );
        $this->paginaCadastroMapEnvioCompDigitais->salvar();

        $nomeRepositorioCadastrado = $this->paginaCadastroMapEnvioCompDigitais->buscarNome(self::$remetenteB['REP_ESTRUTURAS']);
        $nomeUnidadeCadastrado = $this->paginaCadastroMapEnvioCompDigitais->buscarNome(self::$remetenteB['NOME_UNIDADE']);

        sleep(1);
        $this->assertNotNull($nomeRepositorioCadastrado);
        $this->assertNotNull($nomeUnidadeCadastrado);
        $mensagem = $this->paginaCadastroMapEnvioCompDigitais->buscarMensagemAlerta();
        $this->assertStringContainsString(
            mb_convert_encoding('Mapeamento de Envio Parcial atualizado com sucesso.', 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );
    }

    /**
     * Teste para pesquisar mapeamento de envio parcial
     *
     * @group mapeamento
     *
     * @return void
     */
    public function test_pesquisar_mapeamento_envio_parcial_test()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaEnvioParcialListar->navegarEnvioParcialListar();

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
     * Teste para imprimir mapeamento de envio parcial
     *
     * @group mapeamento
     *
     * @return void
     */
    public function test_imprimir_mapeamento_envio_parcial_test()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaEnvioParcialListar->navegarEnvioParcialListar();

        // Buscar pesquisa vazia
        $this->paginaCadastroMapEnvioCompDigitais->selecionarImprimir();
        $this->assertTrue(true);
    }

    /**
     * Teste para excluir mapeamento de envio parcial
     *
     * @group mapeamento
     *
     * @return void
     */
    public function test_excluir_mapeamento_envio_parcial_test()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaEnvioParcialListar->navegarEnvioParcialListar();
        $this->paginaCadastroMapEnvioCompDigitais->selecionarExcluir();

        sleep(1);
        $mensagem = $this->paginaCadastroMapEnvioCompDigitais->buscarMensagemAlerta();
        $this->assertStringContainsString(
            mb_convert_encoding('Mapeamento excluído com sucesso.', 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );
    }
}