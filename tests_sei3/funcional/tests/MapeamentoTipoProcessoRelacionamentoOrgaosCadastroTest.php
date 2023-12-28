<?php

/**
 * Testes de mapeamento de tipos de processo e relacionamento entre orgãos
 * Cadastro mapeamento de orgãos
 */
class MapeamentoTipoProcessoRelacionamentoOrgaosCadastroTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;

    /**
     * @inheritdoc
     * @return void
     */
    function setUp(): void
    {
        parent::setUp();
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $penMapUnidadesFixture = new PenMapUnidadesFixture(CONTEXTO_ORGAO_A, [
            'id' => self::$remetente['ID_ESTRUTURA'],
            'sigla' => self::$remetente['SIGLA_ESTRUTURA'],
            'nome' => self::$remetente['NOME_UNIDADE']
        ]);
        $penMapUnidadesFixture->gravar();
    }

    /**
     * Teste de cadastro de novo mapeamento entre ogrãos
     *
     * @return void
     */
    public function test_cadastrar_novo_mapeamento_orgao_externo()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaCadastroOrgaoExterno->navegarCadastroOrgaoExterno();
        $this->paginaCadastroOrgaoExterno->novoMapOrgao();
        $this->paginaCadastroOrgaoExterno->setarParametros(
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$remetente['NOME_UNIDADE']
        );
        $this->paginaCadastroOrgaoExterno->salvar();

        sleep(1);
        $mensagem = $this->paginaCadastroOrgaoExterno->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Relacionamento entre Órgãos cadastrado com sucesso.'),
            $mensagem
        );
    }

    /**
     * Teste para cadastro de mapeamento de orgão exteno já existente
     *
     * @group MapeamentoOrgaoExterno
     *
     * @return void
     */
    public function test_cadastrar_mapeamento_orgao_externo_ja_cadastrado()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaCadastroOrgaoExterno->navegarCadastroOrgaoExterno();
        $this->paginaCadastroOrgaoExterno->novoMapOrgao();
        $this->paginaCadastroOrgaoExterno->setarParametros(
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$remetente['NOME_UNIDADE']
        );
        $this->paginaCadastroOrgaoExterno->salvar();

        sleep(1);
        $mensagem = $this->paginaCadastroOrgaoExterno->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Cadastro de relacionamento entre órgãos já existente.'),
            $mensagem 
        );
    }

    /**
     * Teste para editar mapeamento de orgão exteno
     *
     * @group MapeamentoOrgaoExterno
     *
     * @return void
     */
    public function test_editar_mapeamento_orgao_externo()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaCadastroOrgaoExterno->navegarCadastroOrgaoExterno();

        $this->paginaCadastroOrgaoExterno->editarMapOrgao();
        $this->paginaCadastroOrgaoExterno->setarParametros(
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$remetente['NOME_UNIDADE']
        );
        $this->paginaCadastroOrgaoExterno->salvar();

        sleep(1);
        $mensagem = $this->paginaCadastroOrgaoExterno->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Relacionamento entre Órgãos atualizado com sucesso.'),
            $mensagem
        );
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
    }
}
