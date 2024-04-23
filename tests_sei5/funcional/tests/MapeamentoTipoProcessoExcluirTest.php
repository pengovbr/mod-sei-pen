<?php

/**
 * Testes de mapeamento de tipos de processo e relacionamento entre orgãos
 * Excluir mapeamento entre orgãos
 *
 * Execution Groups
 * @group execute_alone_group1
 */
class MapeamentoTipoProcessoExcluirTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $penOrgaoExternoId;

    /**
     * @inheritdoc
     * @return void
     */
    function setUp(): void
    {
        parent::setUp();
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        $penOrgaoExternoFixture = new PenOrgaoExternoFixture(CONTEXTO_ORGAO_A);
        self::$penOrgaoExternoId = $penOrgaoExternoFixture->cadastrar([
            'idRepositorio' => self::$remetente['ID_REP_ESTRUTURAS'],
            'repositorioEstruturas' => self::$remetente['REP_ESTRUTURAS'],
            'id' => self::$remetente['ID_ESTRUTURA'],
            'sigla' => self::$remetente['SIGLA_ESTRUTURA'],
            'nome' => self::$remetente['NOME_UNIDADE'],
            'idOrigem' => self::$destinatario['ID_ESTRUTURA'],
            'nomeOrigem' => self::$destinatario['NOME_UNIDADE']
        ]);
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
        $this->markTestIncomplete(
            'Teste refatorado a partir da entrega 3.7.0.'
        );
        
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaTramiteMapeamentoOrgaoExterno->navegarRelacionamentoEntreOrgaos();

        $this->paginaCadastroOrgaoExterno->selecionarExcluirMapOrgao(self::$penOrgaoExternoId);
        sleep(1);
        $mensagem = $this->paginaCadastroOrgaoExterno->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Relacionamento entre unidades foi excluído com sucesso.'),
            $mensagem
        );
    }

    function tearDown(): void
    {
        $penOrgaoExternoFixture = new PenOrgaoExternoFixture(CONTEXTO_ORGAO_A);
        $penOrgaoExternoFixture->deletar(self::$penOrgaoExternoId);

        parent::tearDown();
    }
}
