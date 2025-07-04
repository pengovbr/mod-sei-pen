<?php

class TramiteProcessoGestorNaoResponsavelPelaUnidadeMapeadaTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste;
    public static $protocoloTeste;
    protected static $nomeOrgNaoMapeada = 'MELO_SEGES_ORG1';
    protected static $idOrgNaoMapeada = '155043';

    public static function tearDownAfterClass(): void
    {
        // Reverte alteração feita em alterarMapeamentoDeUnidadeOrgaoA para evitar quebra de testes subsequentes
        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);
        $bancoOrgaoA->execute("update md_pen_unidade set id_unidade_rh=?, sigla_unidade_rh=?, nome_unidade_rh=? where id_unidade=?", array(self::$remetente['ID_ESTRUTURA'], self::$remetente['SIGLA_ESTRUTURA'], self::$remetente['SIGLA_ESTRUTURA'], 110000001));

        parent::tearDownAfterClass();
    }

    /**
     * Set up
     * Cria pameamento de unidades para o teste por Fixture
     *
     * @return void
     */
    function setUp(): void
    {
        parent::setUp();
        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);
        $bancoOrgaoA->execute("update md_pen_unidade set id_unidade_rh=?, sigla_unidade_rh=?, nome_unidade_rh=? where id_unidade=?", array(self::$idOrgNaoMapeada, self::$nomeOrgNaoMapeada, self::$nomeOrgNaoMapeada, 110000001));
    }
    
    /**
     * Teste de trâmite de processo para organização não-mapeada à unidade corrente
     *
     * @group envio
     * @large
     * 
     * @Depends CenarioBaseTestCase::setUpBeforeClass
     *
     * @return void
     */
    public function test_tramite_gestor_nao_responsavel_unidade()
    {
        //Altera o mapeamento da unidade atual para uma organização que não reconhece a unidade
        $this->alterarMapeamentoDeUnidadeOrgaoA();

        // Configuração dos dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        // Criar processo principal
        self::$protocoloTeste = $this->cadastrarProcessoFixture(self::$processoTeste);

        // Cadastrando documentos no processo
        $this->cadastrarDocumentoInternoFixture(self::$documentoTeste, self::$protocoloTeste->getDblIdProtocolo());

        // Acessa o processo criado
        self::$protocoloTeste = self::$protocoloTeste->getStrProtocoloFormatado();
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->abrirProcesso(self::$protocoloTeste);

        $this->tramitarProcessoExternamenteGestorNaoResponsavelUnidade([
            'repositorio' => mb_convert_encoding(self::$destinatario['REP_ESTRUTURAS'], 'UTF-8', 'ISO-8859-1'),
            'unidadeDestino' => mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], 'UTF-8', 'ISO-8859-1'),
            'nomeUnidadeMalMapeada' => self::$nomeOrgNaoMapeada,
            'idUnidadeMalMapeada' => self::$idOrgNaoMapeada
        ]);

    }

    private function alterarMapeamentoDeUnidadeOrgaoA()
    {
        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);
        $bancoOrgaoA->execute("update md_pen_unidade set id_unidade_rh=?, sigla_unidade_rh=?, nome_unidade_rh=? where id_unidade=?", array(self::$idOrgNaoMapeada, self::$nomeOrgNaoMapeada, self::$nomeOrgNaoMapeada, 110000001));
    }

    private function tramitarProcessoExternamenteGestorNaoResponsavelUnidade ($dados) {

        // Acessar funcionalidade de trâmite externo
        try {
            $this->paginaTramitarProcessoEmLote->navegarControleProcessos();
        } catch (Exception $e) {
            $this->paginaProcesso->navegarParaTramitarProcesso();
        }

        // Preencher parâmetros do trâmite
        $this->paginaTramitar->repositorio($dados['repositorio']);
        $this->paginaTramitar->unidade($dados['unidadeDestino'], '');
        $this->paginaTramitar->tramitar();

        $callbackEnvio = function ($testCase) use ($dados) {
            try {
                $testCase->frame(null);
                $testCase->frame('ifrConteudoVisualizacao');
                $testCase->frame('ifrVisualizacao');
                $testCase->frame('ifrEnvioProcesso');
                $mensagemValidacao = mb_convert_encoding('Falha no envio externo do processo.', 'UTF-8', 'ISO-8859-1');
                $testCase->assertStringContainsString($mensagemValidacao, $testCase->byCssSelector('body')->text());
                $testCase->byXPath("//input[@id='btnInfraDetalhesExcecao']")->click();
                $mensagemValidacao2 = mb_convert_encoding('A unidade ' . $dados['nomeUnidadeMalMapeada'] . ' (' . $dados['idUnidadeMalMapeada'] . ') foi mapeada de forma errada. Desse modo, entre em contato com os Gestores do seu órgão e informe que o mapeamento não está correto.', 'UTF-8', 'ISO-8859-1');             
                $testCase->assertStringContainsString($mensagemValidacao2, $testCase->byCssSelector('body')->text());

                $btnFechar = $testCase->byXPath("//input[@id='btnFechar']");
                $btnFechar->click();
            } finally {
                try {
                    $this->frame(null);
                    $this->frame("ifrVisualizacao");
                } catch (Exception $e) {
                }
            }

            return true;
        };

        try {
            $this->waitUntil($callbackEnvio, PEN_WAIT_TIMEOUT);
        } finally {
            try {
                $this->frame(null);
                $this->frame("ifrVisualizacao");
            } catch (Exception $e) {
            }
        }
    }
}
