<?php

/**
 * Testes de mapeamento de tipos de processo e relacionamento entre orgãos
 * Excluir mapeamento entre orgãos
 */
class MapeamentoTipoProcessoExcluirTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $penOrgaoExternoId;

    /**
     * @inheritdoc
     * @return void
     */
    function setUp(): void
    {
        parent::setUp();
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $penOrgaoExternoFixture = new PenOrgaoExternoFixture(CONTEXTO_ORGAO_A);
        self::$penOrgaoExternoId = $penOrgaoExternoFixture->cadastrar([
            'idRepositorio' => self::$remetente['ID_REP_ESTRUTURAS'],
            'repositorioEstruturas' => self::$remetente['REP_ESTRUTURAS'],
            'id' => self::$remetente['ID_UNIDADE_ESTRUTURA'],
            'sigla' => self::$remetente['SIGLA_UNIDADE_ESTRUTURAS'],
            'nome' => self::$remetente['NOME_UNIDADE_ESTRUTURA'],
            'idOrigem' => self::$remetente['ID_UNIDADE_MAPEAMENTO_ORGAO_ORIGEM'],
            'nomeOrigem' => self::$remetente['NOME_UNIDADE_MAPEAMENTO_ORGAO_ORIGEM']
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
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->paginaTramiteMapeamentoOrgaoExterno->navegarRelacionamentoEntreOrgaos();

        $this->paginaCadastroOrgaoExterno->selecionarExcluirMapOrgao(self::$penOrgaoExternoId);
        sleep(1);
        $mensagemRetornoAlert = $this->paginaCadastroOrgaoExterno->buscarMensagemAlerta();
        $menssagemValidacao = utf8_encode('Relacionamento entre órgãos foi excluído com sucesso.');

        $this->assertStringContainsString(
            $menssagemValidacao,
            $mensagemRetornoAlert
        );
    }

    function tearDown(): void
    {
        $penOrgaoExternoFixture = new PenOrgaoExternoFixture(CONTEXTO_ORGAO_A);
        $penOrgaoExternoFixture->deletar(self::$penOrgaoExternoId);

        parent::tearDown();
    }
}
