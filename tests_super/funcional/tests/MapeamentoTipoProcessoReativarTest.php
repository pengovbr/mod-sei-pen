<?php

/**
 * Testes de mapeamento de tipos de processo reativar
 * Reativar tipos de processos
 *
 * Execution Groups
 * @group execute_alone_group1
 */
class MapeamentoTipoProcessoReativarTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $penOrgaoExternoId;
    public static $arrImportacaoTiposProcessoId;

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

        $importacaoTiposProcessoFixture = new \ImportacaoTiposProcessoFixture();
        $tiposProcessos = $this->getTiposProcessos($objPenOrgaoExternoDTO->getDblId(), 'N');
        $arrObjPenMapTipoProcedimentoDTO = $importacaoTiposProcessoFixture->carregarVariados($tiposProcessos);

        foreach ($arrObjPenMapTipoProcedimentoDTO as $objPenMapTipoProcedimentoDTO) {
            self::$arrImportacaoTiposProcessoId[] = $objPenMapTipoProcedimentoDTO->getDblId();
        }
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

        $this->paginaTipoProcessoReativar->navegarTipoProcessoReativar();
        $this->paginaTipoProcessoReativar->reativarMapeamento();
        $this->waitUntil(function ($testCase)  {
            $testCase->frame(null);
            $menssagemValidacao = utf8_encode('Mapeamento de Tipo de Processo foi reativado com sucesso.');
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

        $this->paginaTipoProcessoReativar->navegarTipoProcessoReativar();
        $this->paginaTipoProcessoReativar->reativarMapeamentoCheckbox();
        $this->waitUntil(function ($testCase)  {
            $testCase->frame(null);
            $menssagemValidacao = utf8_encode('Mapeamento de Tipo de Processo foi reativado com sucesso.');
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

    private function getTiposProcessos(int $idMapeamento, string $sinAtivo = 'S')
    {
        return array(
            array(
                'IdMapeamento' => $idMapeamento,
                'IdProcedimento' => 100000348,
                'NomeProcedimento' => utf8_encode('Acompanhamento Legislativo: Congresso Nacional'),
                'SimAtivo' => $sinAtivo
            ),
            array(
                'IdMapeamento' => $idMapeamento,
                'IdProcedimento' => 100000425,
                'NomeProcedimento' => utf8_encode('mauro teste'),
                'SimAtivo' => $sinAtivo
            )
        );
    }
}