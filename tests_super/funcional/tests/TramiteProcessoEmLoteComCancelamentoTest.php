<?php

class TramiteProcessoEmLoteComCancelamentoTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste;
    public static $protocoloTeste;

    /**
     * Teste de cancelamento de trâmite com processo (em lote) contendo documento gerado (interno)
     *
     * @group envio
     * @large
     * 
     * @Depends CenarioBaseTestCase::setUpBeforeClass
     *
     * @return void
     */
    public function test_cancelamento_tramite_contendo_documento_interno()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        self::$protocoloTeste = $this->cadastrarProcesso(self::$processoTeste);
        $this->cadastrarDocumentoInterno(self::$documentoTeste);
        $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);

        // Seleciona todos os processos para tramitação em lote
        $this->selecionarProcessos(self::$protocoloTeste);

        // Trâmitar Externamento processo para órgão/unidade destinatária
        $this->tramitarProcessoExternamente(
            self::$protocoloTeste, 
            self::$destinatario['REP_ESTRUTURAS'], 
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false);

    }

    /**
     * Teste de cancelamento de trâmite com processo contendo documento externo
     *
     * @group envio
     * @large
     * 
     * @depends test_cancelamento_tramite_contendo_documento_interno
     *
     * @return void
     */
    public function test_cancelamento_tramite_contendo_documento_externo()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->abrirProcesso(self::$protocoloTeste);

        $this->paginaProcesso->cancelarTramitacaoExterna();
        $mensagemAlerta = $this->paginaTramitar->alertTextAndClose(true);
        $mensagemEsperada = utf8_encode("O trâmite externo do processo foi cancelado com sucesso!");
        $this->assertStringContainsString($mensagemEsperada, $mensagemAlerta);
        $this->assertFalse($this->paginaProcesso->processoBloqueado());
        $this->assertTrue($this->paginaProcesso->processoAberto());

    }

    /**
     * Teste de verificação do correto envio do processo no sistema remetente
     *
     * @group verificacao_envio
     *
     * @depends test_cancelamento_tramite_contendo_documento_externo
     *
     * @return void
     */
    public function test_verificar_cancelamento_processo_lote()
    {

        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->visualizarProcessoTramitadosEmLote($this);

        $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $testCase->refresh();
            $this->navegarProcessoEmLote(7, self::$protocoloTeste);
            $paginaTramitarProcessoEmLote = new PaginaTramitarProcessoEmLote($testCase);
            $this->assertStringContainsString(self::$protocoloTeste, $paginaTramitarProcessoEmLote->informacaoLote());
            return true;
        }, PEN_WAIT_TIMEOUT_PROCESSAMENTO_EM_LOTE);
      
    }

}
