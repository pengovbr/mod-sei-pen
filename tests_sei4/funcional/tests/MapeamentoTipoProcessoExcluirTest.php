<?php

/**
 * Testes de mapeamento de tipos de processo e relacionamento entre orgãos
 * Excluir mapeamento entre orgãos
 *
 * Execution Groups
 * @group execute_alone_group1
 */
class MapeamentoTipoProcessoExcluirTest extends FixtureCenarioBaseTestCase
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
        
        $penOrgaoExternoFixture = new \PenOrgaoExternoFixture();
        $objPenOrgaoExternoDTO = $penOrgaoExternoFixture->carregar([
            'IdRepositorio' => self::$remetente['ID_REP_ESTRUTURAS'],
            'RepositorioEstruturas' => self::$remetente['REP_ESTRUTURAS'],
            'Id' => self::$remetente['ID_ESTRUTURA'],
            'Sigla' => self::$remetente['SIGLA_ESTRUTURA'],
            'Nome' => self::$remetente['NOME_UNIDADE'],
            'IdOrigem' => self::$destinatario['ID_ESTRUTURA'],
            'NomeOrigem' => self::$destinatario['NOME_UNIDADE']
        ]);
    
        self::$penOrgaoExternoId = $objPenOrgaoExternoDTO->getDblId();
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

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaTramiteMapeamentoOrgaoExterno->navegarRelacionamentoEntreOrgaos();
        sleep(5);
        $this->paginaCadastroOrgaoExterno->selecionarExcluirMapOrgao(self::$penOrgaoExternoId);
        sleep(1);
        $mensagem = $this->paginaCadastroOrgaoExterno->buscarMensagemAlerta();
        $this->assertStringContainsString(
            mb_convert_encoding('Relacionamento entre unidades foi excluído com sucesso.', 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );
        
        $this->sairSistema();
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
    }
}