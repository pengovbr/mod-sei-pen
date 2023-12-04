<?php

/**
 * Testes de mapeamento de tipos de processo e relacionamento entre orgãos
 * Cadastro mapeamento de orgãos
 */
class MapeamentoTipoProcessoRelacionamentoOrgaosCadastroTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $remetenteB;

    /**
     * @inheritdoc
     * @return void
     */
    function setUp(): void
    {
        parent::setUp();
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        
        $penMapUnidadesFixture = new PenMapUnidadesFixture(CONTEXTO_ORGAO_A, [
            'id' => self::$remetente['ID_UNIDADE_ESTRUTURA'],
            'sigla' => self::$remetente['SIGLA_UNIDADE_ESTRUTURAS'],
            'nome' => self::$remetente['NOME_UNIDADE_ESTRUTURA']
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
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaCadastroOrgaoExterno->navegarCadastroOrgaoExterno();
        $this->paginaCadastroOrgaoExterno->novoMapOrgao();
        $this->paginaCadastroOrgaoExterno->setarParametros(
            self::$remetente['REP_ESTRUTURAS'],
            self::$remetente['NOME_UNIDADE_MAPEAMENTO_ORGAO_ORIGEM'],
            self::$remetente['NOME_UNIDADE_ESTRUTURA']
        );
        $this->paginaCadastroOrgaoExterno->salvar();

        $orgaoOrigem = $this->paginaCadastroOrgaoExterno->buscarOrgaoOrigem(self::$remetente['NOME_UNIDADE_MAPEAMENTO_ORGAO_ORIGEM']);
        $orgaoDestino = $this->paginaCadastroOrgaoExterno->buscarOrgaoDestino(self::$remetente['NOME_UNIDADE_ESTRUTURA']);

        $this->assertNotNull($orgaoOrigem);
        $this->assertNotNull($orgaoDestino);
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
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaCadastroOrgaoExterno->navegarCadastroOrgaoExterno();
        $this->paginaCadastroOrgaoExterno->novoMapOrgao();
        $this->paginaCadastroOrgaoExterno->setarParametros(
            self::$remetente['REP_ESTRUTURAS'],
            self::$remetente['NOME_UNIDADE_ESTRUTURA'],
            self::$remetente['NOME_UNIDADE_ORGAO_DESTINO']
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
        self::$remetenteB = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaCadastroOrgaoExterno->navegarCadastroOrgaoExterno();

        $this->paginaCadastroOrgaoExterno->editarMapOrgao();
        $this->paginaCadastroOrgaoExterno->setarParametros(
            self::$remetente['REP_ESTRUTURAS'],
            self::$remetente['NOME_UNIDADE_MAPEAMENTO_ORGAO_ORIGEM'],
            self::$remetente['NOME_UNIDADE_ESTRUTURA']
        );
        $this->paginaCadastroOrgaoExterno->salvar();

        $orgaoOrigem = $this->paginaCadastroOrgaoExterno->buscarOrgaoOrigem(self::$remetenteB['NOME_UNIDADE_MAPEAMENTO_ORGAO_ORIGEM']);
        $orgaoDestino = $this->paginaCadastroOrgaoExterno->buscarOrgaoDestino(self::$remetente['NOME_UNIDADE_ESTRUTURA']);

        $this->assertNotNull($orgaoOrigem);
        $this->assertNotNull($orgaoDestino);
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
