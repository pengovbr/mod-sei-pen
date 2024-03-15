<?php

/**
 * Testes de tr�mite de processos em lote
 *
 * Este mesmo documento deve ser recebido e assinalado como cancelado no destinat�rio e
 * a devolu��o do mesmo processo n�o deve ser impactado pela inser��o de outros documentos
 */
class TramiteProcessoEmLoteTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste1;
    public static $documentoTeste2;
    public static $protocoloTeste;

    /**
     * Teste inicial de tr�mite de um processo contendo um documento movido
     *
     * @group envio
     *
     * @return void
     */
    public function test_tramitar_processo_em_lote()
    {

        // Configura��o do dados para teste do cen�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        self::$documentoTeste2 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        self::$protocoloTeste = $this->cadastrarProcesso(self::$processoTeste);
        $this->cadastrarDocumentoInterno(self::$documentoTeste1);
        $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);

        $this->cadastrarDocumentoExterno(self::$documentoTeste2);

        // Seleciona todos os processos para tramita��o em lote
        $this->selecionarProcessos(self::$protocoloTeste);

        // Tr�mitar Externamento processo para �rg�o/unidade destinat�ria
        $this->tramitarProcessoExternamente(
            self::$protocoloTeste, 
            self::$destinatario['REP_ESTRUTURAS'], 
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false);

    }

    /**
     * Teste de verifica��o do correto envio do processo no sistema remetente
     *
     * @group verificacao_envio
     *
     * @depends test_tramitar_processo_em_lote
     *
     * @return void
     */
    public function test_verificar_envio_processo()
    {
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->visualizarProcessoTramitadosEmLote($this);
        $this->navegarProcessoEmLote(0);

        $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $testCase->refresh();
            $paginaTramitarProcessoEmLote = new PaginaTramitarProcessoEmLote($testCase);
            $testCase->assertStringContainsString(utf8_encode("Nenhum registro encontrado."), $paginaTramitarProcessoEmLote->informacaoLote());
            return true;
        }, PEN_WAIT_TIMEOUT_PROCESSAMENTO_EM_LOTE);
        
        sleep(5);
    }

    /**
     * Teste de verifica��o do correto recebimento do processo no destino
     *
     * @group verificacao_recebimento
     * @large
     *
     * @depends test_verificar_envio_processo
     *
     * @return void
     */
    public function test_verificar_recebimento_processo_destino()
    {
        $documentos = array(self::$documentoTeste1, self::$documentoTeste2);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, $documentos, self::$destinatario);
    }

}
