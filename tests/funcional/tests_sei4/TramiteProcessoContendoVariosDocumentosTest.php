<?php

class TramiteProcessoContendoVariosDocumentosTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentosTeste;
    public static $protocoloTeste;

    /**
     * Teste de tr�mite externo de processo contendo v�rios documentos
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
        //Aumenta o tempo de timeout devido � quantidade de arquivos
        $this->setSeleniumServerRequestsTimeout(6000);

        // Configura��o do dados para teste do cen�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentosTeste = array_merge(
            array_fill(0, 3, $this->gerarDadosDocumentoInternoTeste(self::$remetente)),
            array_fill(0, 3, $this->gerarDadosDocumentoExternoTeste(self::$remetente))
        );

        shuffle(self::$documentosTeste);

        $this->realizarTramiteExternoSemvalidacaoNoRemetente(self::$processoTeste, self::$documentosTeste, self::$remetente, self::$destinatario);
        self::$protocoloTeste = self::$processoTeste["PROTOCOLO"];
    }


    /**
     * Teste de verifica��o do correto envio do processo no sistema remetente
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

        // 6 - Verificar se situa��o atual do processo est� como bloqueado
        $this->waitUntil(function($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $this->atualizarTramitesPEN();
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(utf8_encode("Processo em tr�mite externo para "), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT_ARQUIVOS_GRANDES);

        // 7 - Validar se recibo de tr�mite foi armazenado para o processo (envio e conclus�o)
        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Tr�mite externo do Processo %s para %s", self::$protocoloTeste, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);

        // 8 - Validar hist�rico de tr�mite do processo
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);

        // 9 - Verificar se processo est� na lista de Processos Tramitados Externamente
        $this->validarProcessosTramitados(self::$protocoloTeste, $orgaosDiferentes);
    }


    /**
     * Teste de verifica��o do correto recebimento do processo
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
