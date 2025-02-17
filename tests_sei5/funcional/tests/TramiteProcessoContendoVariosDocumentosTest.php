<?php

/**
 *
 * Execution Groups
 * @group execute_parallel_group1
 */
class TramiteProcessoContendoVariosDocumentosTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentosTeste;
    public static $protocoloTeste;

    /**
     * Teste de trâmite externo de processo contendo vários documentos
     *
     * @group envio
     * @large
     * 
     * @Depends CenarioBaseTestCase::setUpBeforeClass
     *
     * @return void
     */
    public function test_tramitar_processo_contendo_varios_documentos()
    {
        //Aumenta o tempo de timeout devido à quantidade de arquivos
        $this->setSeleniumServerRequestsTimeout(6000);

        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentosTeste = array_merge(
            array_fill(0, 3, $this->gerarDadosDocumentoInternoTeste(self::$remetente)),
            array_fill(0, 3, $this->gerarDadosDocumentoExternoTeste(self::$remetente))
        );

        shuffle(self::$documentosTeste);

        $this->realizarTramiteExternoSemValidacaoNoRemetenteFixture(self::$processoTeste, self::$documentosTeste, self::$remetente, self::$destinatario);
        self::$protocoloTeste = self::$processoTeste["PROTOCOLO"];
    }


    /**
     * Teste de verificação do correto envio do processo no sistema remetente
     *
     * @group verificacao_envio
     * @large
     *
     * @depends test_tramitar_processo_contendo_varios_documentos
     *
     * @return void
     */
    public function test_verificar_origem_processo_contendo_varios_documentos()
    {
        $this->setSeleniumServerRequestsTimeout(6000);

        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->abrirProcesso(self::$protocoloTeste);

        // 6 - Verificar se situação atual do processo está como bloqueado
        $this->waitUntil(function($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(mb_convert_encoding("Processo em trâmite externo para ", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT_ARQUIVOS_GRANDES);

        // 7 - Validar se recibo de trâmite foi armazenado para o processo (envio e conclusão)
        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTeste, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);

        // 8 - Validar histórico de trâmite do processo
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);

        // 9 - Verificar se processo está na lista de Processos Tramitados Externamente
        $this->validarProcessosTramitados(self::$protocoloTeste, $orgaosDiferentes);
    }


    /**
     * Teste de verificação do correto recebimento do processo
     *
     * @group verificacao_recebimento
     * @large
     *
     * @depends test_verificar_origem_processo_contendo_varios_documentos
     *
     * @return void
     */
    public function test_verificar_destino_processo_contendo_varios_documentos()
    {
        $this->setSeleniumServerRequestsTimeout(6000);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, self::$documentosTeste, self::$destinatario);
    }
}
