<?php

/**
 * Testes de mapeamento de tipos de processo e relacionamento entre orgãos
 * Cadastro mapeamento de orgãos
 *
 * Execution Groups
 * @group execute_alone_group1
 */
class MapeamentoTipoProcessoRelacionamentoOrgaosCadastroTest extends FixtureCenarioBaseTestCase
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

        $orgaoOrigem = $this->paginaCadastroOrgaoExterno->buscarOrgaoOrigem(self::$destinatario['NOME_UNIDADE']);
        $orgaoDestino = $this->paginaCadastroOrgaoExterno->buscarOrgaoDestino(self::$remetente['NOME_UNIDADE']);

        $this->assertNotNull($orgaoOrigem);
        $this->assertNotNull($orgaoDestino);
        sleep(1);
        $mensagem = $this->paginaCadastroOrgaoExterno->buscarMensagemAlerta();
        $this->assertStringContainsString(
            mb_convert_encoding('Relacionamento entre Unidades cadastrado com sucesso.', 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );
        
        $this->sairSistema();
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
            mb_convert_encoding('Cadastro de relacionamento entre unidades já existente.', 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );
        
        $this->sairSistema();
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

        $orgaoOrigem = $this->paginaCadastroOrgaoExterno->buscarOrgaoOrigem(self::$destinatario['NOME_UNIDADE']);
        $orgaoDestino = $this->paginaCadastroOrgaoExterno->buscarOrgaoDestino(self::$remetente['NOME_UNIDADE']);

        $this->assertNotNull($orgaoOrigem);
        $this->assertNotNull($orgaoDestino);
        sleep(1);
        $mensagem = $this->paginaCadastroOrgaoExterno->buscarMensagemAlerta();
        $this->assertStringContainsString(
            mb_convert_encoding('Relacionamento entre Unidades atualizado com sucesso.', 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );
        
        $this->sairSistema();
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
    }
}