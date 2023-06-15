<?php

class TramiteProcessoValidacaoEnvioTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste;
    public static $protocoloTeste;

    function setUp(): void
    {
        parent::setUp();
    }

    function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Teste de trâmite externo com processo não contendo nenhum documento cadastrado
     *
     * @group envio
     * @large
     * 
     * @Depends CenarioBaseTestCase::setUpBeforeClass
     *
     * @return void
     */
    public function test_tramitar_processo_sem_documentos()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);

        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        // Cadastrar novo processo de teste
        self::$protocoloTeste = $this->cadastrarProcesso(self::$processoTeste);

        $this->expectExceptionMessage(utf8_encode("Não é possível tramitar um processo sem documentos"));
        $this->tramitarProcessoExternamente(
            self::$protocoloTeste,
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false
        );
    }
}
