<?php

/**
 * Testes de mapeamento de tipos de processo e relacionamento entre org�os
 * Desativar e reativar mapeamento entre org�os
 *
 * Execution Groups
 * @group execute_alone_group1
 */
class MapeamentoTipoProcessoDesativarReativarTest extends FixtureCenarioBaseTestCase
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
     * Teste de desativa��o de um Relacionamento entre �rg�os
     *
     * @large
     *
     * @return void
     */
    public function test_desativacao_mapeamento_orgao_externo()
    {
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaTramiteMapeamentoOrgaoExterno->navegarRelacionamentoEntreOrgaos();

        $this->paginaTramiteMapeamentoOrgaoExterno->selectEstado("Ativo");
        $this->paginaTramiteMapeamentoOrgaoExterno->desativarMapeamento();
        $this->waitUntil(function ($testCase)  {
            $testCase->frame(null);
            $menssagemValidacao = mb_convert_encoding('Relacionamento entre Unidades foi desativado com sucesso.', 'UTF-8', 'ISO-8859-1');
            $this->assertStringContainsString($menssagemValidacao, $testCase->byId('divInfraMsg0')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);

        $this->sairSistema();
    }

    /**
     * Teste de reativa��o de um Relacionamento entre �rg�os
     * 
     * @large
     *
     * @return void
     */
    public function test_reativacao_mapeamento_orgao_externo()
    {
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaTramiteMapeamentoOrgaoExterno->navegarRelacionamentoEntreOrgaos();

        $this->paginaTramiteMapeamentoOrgaoExterno->selectEstado("Inativo");
        $this->paginaTramiteMapeamentoOrgaoExterno->reativarMapeamento();
        $this->waitUntil(function ($testCase)  {
            $testCase->frame(null);
            $menssagemValidacao = mb_convert_encoding('Relacionamento entre Unidades foi reativado com sucesso.', 'UTF-8', 'ISO-8859-1');
            $this->assertStringContainsString($menssagemValidacao, $testCase->byId('divInfraMsg0')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);
        
        $this->sairSistema();
    }

    /**
     * Teste de desativa��o de um Relacionamento entre �rg�os via checkbox
     *
     * @large
     *
     * @return void
     */
    public function test_desativacao_checkbox_mapeamento_orgao_externo()
    {
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaTramiteMapeamentoOrgaoExterno->navegarRelacionamentoEntreOrgaos();

        $this->paginaTramiteMapeamentoOrgaoExterno->selectEstado("Ativo");
        $this->paginaTramiteMapeamentoOrgaoExterno->desativarMapeamentoCheckbox();
        $this->waitUntil(function ($testCase)  {
            $testCase->frame(null);
            $menssagemValidacao = mb_convert_encoding('Relacionamento entre Unidades foi desativado com sucesso.', 'UTF-8', 'ISO-8859-1');
            $this->assertStringContainsString($menssagemValidacao, $testCase->byId('divInfraMsg0')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);
        
        $this->sairSistema();
    }

    /**
     * Teste de desativa��o de um Relacionamento entre �rg�os via checkbox
     *
     * @large
     *
     * @return void
     */
    public function test_reativar_checkbox_mapeamento_orgao_externo()
    {
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaTramiteMapeamentoOrgaoExterno->navegarRelacionamentoEntreOrgaos();

        $this->paginaTramiteMapeamentoOrgaoExterno->selectEstado("Inativo");
        $this->paginaTramiteMapeamentoOrgaoExterno->reativarMapeamentoCheckbox();
        $this->waitUntil(function ($testCase)  {
            $testCase->frame(null);
            $menssagemValidacao = mb_convert_encoding('Relacionamento entre Unidades foi reativado com sucesso.', 'UTF-8', 'ISO-8859-1');
            $this->assertStringContainsString($menssagemValidacao, $testCase->byId('divInfraMsg0')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);
        
        $this->sairSistema();
    }

    public static function tearDownAfterClass(): void
    {
        $importacaoTiposProcessoFixture = new \ImportacaoTiposProcessoFixture();
        $arrObjPenMapTipoProcedimentoDTO = $importacaoTiposProcessoFixture->buscar([
            'IdMapeamento' => self::$penOrgaoExternoId
        ]);

        foreach ($arrObjPenMapTipoProcedimentoDTO as $objPenMapTipoProcedimentoDTO) {
            $importacaoTiposProcessoFixture->remover([
                'Id' => $objPenMapTipoProcedimentoDTO->getDblId()
            ]);
        }

        $penOrgaoExternoFixture = new \PenOrgaoExternoFixture();
        $penOrgaoExternoFixture->remover([
            'Id' => self::$penOrgaoExternoId,
        ]);

        parent::tearDownAfterClass();
    }
}