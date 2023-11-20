<?php

/**
 * Testes de mapeamento de orgãos
 *
 * Esse teste é responsável por executar e validar todos os testes referentes a mapeamentos.
 */
class MapeamentoTipoProcessoDesativarReativarTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $remetenteB;
     
    /**
     * Teste de cadastro de um Relacionamento entre Órgãos
     *
     * @return void
     */
    public function test_cadastrar_novo_mapeamento_orgao_externo()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->navegarPara('pen_map_orgaos_externos_listar');
        $this->paginaCadastroOrgaoExterno->novoMapOrgao();
        $this->paginaCadastroOrgaoExterno->setarParametros(
            self::$remetente['REP_ESTRUTURAS'], self::$remetente['NOME_UNIDADE_ESTRUTURA'], self::$remetente['SIGLA_UNIDADE']
        );
        $this->paginaCadastroOrgaoExterno->salvar();

        $orgaoOrigem = $this->paginaCadastroOrgaoExterno->buscarOrgaoOrigem(self::$remetente['NOME_UNIDADE_ESTRUTURA']);
        $orgaoDestino = $this->paginaCadastroOrgaoExterno->buscarOrgaoDestino(self::$remetente['SIGLA_UNIDADE']);

        sleep(2);
        $this->assertNotNull($orgaoOrigem);
        $this->assertNotNull($orgaoDestino);
        $mensagem = $this->paginaCadastroOrgaoExterno->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Relacionamento cadastrado com sucesso.'),
            $mensagem
        );
    }

    /**
     * Teste de desativação de um Relacionamento entre Órgãos
     *
     * 
     * @large
     *
     * @Depends test_novo_mapeamento_orgao_externo
     *
     * @return void
     */
    public function test_desativacao_mapeamento_orgao_externo() 
    {
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->navegarPara('pen_map_orgaos_externos_listar');
        
        $this->paginaTramiteMapeamentoOrgaoExterno->selectEstado("Ativo");
        $this->paginaTramiteMapeamentoOrgaoExterno->desativarMapeamento();
        
        sleep(1);
        $mensagem = $this->paginaCadastroOrgaoExterno->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Relacionamento entre Órgãos foi desativado com sucesso.'),
            $mensagem
        );
    }

    /**
     * Teste de reativação de um Relacionamento entre Órgãos
     * 
     * @large
     *
     * @Depends test_desativacao_mapeamento_orgao_externo
     *
     * @return void
     */
    public function test_reativacao_mapeamento_orgao_externo() 
    {
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->navegarPara('pen_map_orgaos_externos_listar');

        $this->paginaTramiteMapeamentoOrgaoExterno->selectEstado("Inativo");
        $this->paginaTramiteMapeamentoOrgaoExterno->reativarMapeamento();
        
        sleep(1);
        $mensagem = $this->paginaCadastroOrgaoExterno->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Relacionamento entre Órgãos foi reativado com sucesso.'),
            $mensagem
        );
    }

    /**
     * Teste de desativação de um Relacionamento entre Órgãos via checkbox
     *
     * @large
     *
     * @return void
     */
    public function test_desativacao_checkbox_mapeamento_orgao_externo()
    {
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->navegarPara('pen_map_orgaos_externos_listar');
        
        $this->paginaTramiteMapeamentoOrgaoExterno->selectEstado("Ativo");
        $this->paginaTramiteMapeamentoOrgaoExterno->desativarMapeamentoCheckbox();
        
        sleep(1);
        $mensagem = $this->paginaCadastroOrgaoExterno->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Relacionamento entre Órgãos foi desativado com sucesso.'),
            $mensagem
        );
    }

    /**
     * Teste de desativação de um Relacionamento entre Órgãos via checkbox
     *
     * @large
     *
     * @return void
     */
    public function test_reativacao_checkbox_mapeamento_orgao_externo()
    {
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->navegarPara('pen_map_orgaos_externos_listar');

        $this->paginaTramiteMapeamentoOrgaoExterno->selectEstado("Inativo");
        $this->paginaTramiteMapeamentoOrgaoExterno->reativarMapeamentoCheckbox();
        
        sleep(1);
        $mensagem = $this->paginaCadastroOrgaoExterno->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Relacionamento entre Órgãos foi reativado com sucesso.'),
            $mensagem
        );
    }

    /**
     * Teste para excluir de mapeamento de orgão exteno
     *
     * @group MapeamentoOrgaoExterno
     *
     * @return void
     */
    public function test_excluir_mapeamento_orgao_externo()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->navegarPara('pen_map_orgaos_externos_listar');

        $this->paginaCadastroOrgaoExterno->selecionarExcluirMapOrgao();
        sleep(1);
        $mensagem = $this->paginaCadastroOrgaoExterno->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Relacionamento entre órgãos foi excluído com sucesso.'),
            $mensagem
        );
    }
}
