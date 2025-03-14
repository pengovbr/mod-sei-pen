<?php

/**
 * Testes de tr�mite de processos anexado considerando a devolu��o do mesmo para a entidade de origem
 *
 * Execution Groups
 * @group execute_without_receiving
 */
class TramiteProcessoComCancelamentoTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste;
    public static $protocoloTeste;

    /**
     * Teste de cancelamento de tr�mite com processo contendo documento gerado (interno)
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
        // Configura��o do dados para teste do cen�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        $this->realizarTramiteExternoSemValidacaoNoRemetenteFixture(self::$processoTeste, self::$documentoTeste, self::$remetente, self::$destinatario);
        self::$protocoloTeste = self::$processoTeste["PROTOCOLO"];

        $this->paginaProcesso->cancelarTramitacaoExterna();
        $mensagemAlerta = $this->paginaTramitar->alertTextAndClose(true);
        $mensagemEsperada = mb_convert_encoding("O tr�mite externo do processo foi cancelado com sucesso!", 'UTF-8', 'ISO-8859-1');
        $this->assertStringContainsString($mensagemEsperada, $mensagemAlerta);
        $this->assertFalse($this->paginaProcesso->processoBloqueado());
        $this->assertTrue($this->paginaProcesso->processoAberto());

        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $this->validarRecibosTramite(sprintf("Tr�mite externo do Processo %s para %s", self::$protocoloTeste, $unidade) , true, false);
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, false);
        $this->validarProcessosTramitados(self::$protocoloTeste, false);

        //Verifica se os �cones de alerta de recusa foram adicionados e se o processo continua aberto na unidade
        $this->paginaBase->navegarParaControleProcesso();
        $this->assertTrue($this->paginaControleProcesso->contemProcesso(self::$protocoloTeste));
        $this->assertFalse($this->paginaControleProcesso->contemAlertaProcessoRecusado(self::$protocoloTeste));

    }

    /**
     * Teste de verifica��o que o processo cancelado n�o foi efetivamente recebido no sistema destinat�rio
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
     * Teste de cancelamento de tr�mite com processo contendo documento externo
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
        // Configura��o do dados para teste do cen�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste = $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_001.pdf');

        $this->realizarTramiteExternoSemValidacaoNoRemetenteFixture(self::$processoTeste, self::$documentoTeste, self::$remetente, self::$destinatario);
        self::$protocoloTeste = self::$processoTeste["PROTOCOLO"];

        $this->paginaProcesso->cancelarTramitacaoExterna();
        $mensagemAlerta = $this->paginaTramitar->alertTextAndClose(true);
        $mensagemEsperada = mb_convert_encoding("O tr�mite externo do processo foi cancelado com sucesso!", 'UTF-8', 'ISO-8859-1');
        $this->assertStringContainsString($mensagemEsperada, $mensagemAlerta);
        $this->assertFalse($this->paginaProcesso->processoBloqueado());
        $this->assertTrue($this->paginaProcesso->processoAberto());

        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $this->validarRecibosTramite(sprintf("Tr�mite externo do Processo %s para %s", self::$protocoloTeste, $unidade) , true, false);
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, false);
        $this->validarProcessosTramitados(self::$protocoloTeste, false);

        //Verifica se os �cones de alerta de recusa foram adicionados e se o processo continua aberto na unidade
        $this->paginaBase->navegarParaControleProcesso();
        $this->assertTrue($this->paginaControleProcesso->contemProcesso(self::$protocoloTeste));
        $this->assertFalse($this->paginaControleProcesso->contemAlertaProcessoRecusado(self::$protocoloTeste));

    }

    /**
     * Teste de verifica��o que o processo cancelado n�o foi efetivamente recebido no sistema destinat�rio
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
