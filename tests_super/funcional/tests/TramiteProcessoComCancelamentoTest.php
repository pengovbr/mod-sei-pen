<?php

class CancelamentoTramiteProcessoTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste;
    public static $protocoloTeste;

    /**
     * Teste de cancelamento de trâmite com processo contendo documento gerado (interno)
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

        $this->tramitarProcessoExternamente(
            self::$protocoloTeste, self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false
        );

        $this->paginaProcesso->cancelarTramitacaoExterna();
        $mensagemAlerta = $this->paginaTramitar->alertTextAndClose(true);
        $mensagemEsperada = utf8_encode("O trâmite externo do processo foi cancelado com sucesso!");
        $this->assertStringContainsString($mensagemEsperada, $mensagemAlerta);
        $this->assertFalse($this->paginaProcesso->processoBloqueado());
        $this->assertTrue($this->paginaProcesso->processoAberto());

        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $this->validarRecibosTramite(sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTeste, $unidade) , true, false);
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, false);
        $this->validarProcessosTramitados(self::$protocoloTeste, false);

        //Verifica se os í­cones de alerta de recusa foram adicionados e se o processo continua aberto na unidade
        $this->paginaBase->navegarParaControleProcesso();
        $this->assertTrue($this->paginaControleProcesso->contemProcesso(self::$protocoloTeste));
        $this->assertFalse($this->paginaControleProcesso->contemAlertaProcessoRecusado(self::$protocoloTeste));

    }

    /**
     * Teste de verificação que o processo cancelado não foi efetivamente recebido no sistema destinatário
     *
     * @group verificacao_recebimento
     * @large
     *
     * @depends test_cancelamento_tramite_contendo_documento_interno
     *
     * @return void
     */
    public function test_verificar_nao_recebimento_processo_destinatario_documento_interno()
    {
        $this->realizarValidacaoNAORecebimentoProcessoNoDestinatario(self::$destinatario, self::$processoTeste);
    }


    /**
     * Teste de cancelamento de trâmite com processo contendo documento externo
     *
     * @group envio
     * @large
     * 
     * @depends test_verificar_nao_recebimento_processo_destinatario_documento_interno
     *
     * @return void
     */
    public function test_cancelamento_tramite_contendo_documento_externo()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste = $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_001.pdf');

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        self::$protocoloTeste = $this->cadastrarProcesso(self::$processoTeste);
        $this->cadastrarDocumentoExterno(self::$documentoTeste);

        $this->tramitarProcessoExternamente(
            self::$protocoloTeste, self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false
        );

        $this->paginaProcesso->cancelarTramitacaoExterna();
        $mensagemAlerta = $this->paginaTramitar->alertTextAndClose(true);
        $mensagemEsperada = utf8_encode("O trâmite externo do processo foi cancelado com sucesso!");
        $this->assertStringContainsString($mensagemEsperada, $mensagemAlerta);
        $this->assertFalse($this->paginaProcesso->processoBloqueado());
        $this->assertTrue($this->paginaProcesso->processoAberto());

        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $this->validarRecibosTramite(sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTeste, $unidade) , true, false);
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, false);
        $this->validarProcessosTramitados(self::$protocoloTeste, false);

        //Verifica se os í­cones de alerta de recusa foram adicionados e se o processo continua aberto na unidade
        $this->paginaBase->navegarParaControleProcesso();
        $this->assertTrue($this->paginaControleProcesso->contemProcesso(self::$protocoloTeste));
        $this->assertFalse($this->paginaControleProcesso->contemAlertaProcessoRecusado(self::$protocoloTeste));

    }

    /**
     * Teste de verificação que o processo cancelado não foi efetivamente recebido no sistema destinatário
     *
     * @group verificacao_recebimento
     * @large
     *
     * @depends test_cancelamento_tramite_contendo_documento_externo
     *
     * @return void
     */
    public function test_verificar_nao_recebimento_processo_destinatario_documento_externo()
    {
        $this->realizarValidacaoNAORecebimentoProcessoNoDestinatario(self::$destinatario, self::$processoTeste);
    }
}
