<?php

class TramiteProcessoContendoDocumentoGeradoMesmoOrgaoTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste;
    public static $protocoloTeste;

    /**
     * Test tramitar processo contendo documento gerado
     * 
     * @Depends CenarioBaseTestCase::setUpBeforeClass
     * @large
     *
     * @return void
     */
    public function test_tramitar_processo_contendo_documento_gerado()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        //Configuração da unidade destinatário como outra unidade do mesmo órgão
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario['SIGLA_UNIDADE'] = self::$remetente['SIGLA_UNIDADE_SECUNDARIA'];
        self::$destinatario['NOME_UNIDADE'] = self::$remetente['NOME_UNIDADE_SECUNDARIA'];
        self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'] = self::$remetente['SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA'];

        $this->realizarTramiteExternoSemvalidacaoNoRemetente(self::$processoTeste, self::$documentoTeste, self::$remetente, self::$destinatario);
        self::$protocoloTeste = self::$processoTeste["PROTOCOLO"];
    }

    /**
     * @depends test_tramitar_processo_contendo_documento_gerado
     * @large
     */
    public function test_verificar_origem_processo_contendo_documento_gerado()
    {
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->abrirProcesso(self::$protocoloTeste);

        // 6 - Verificar se situação atual do processo está como bloqueado
        $this->waitUntil(function($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $this->atualizarTramitesPEN();
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(utf8_encode("Processo em trâmite externo para "), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

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
     * @depends test_verificar_origem_processo_contendo_documento_gerado
     * @large
     */
    public function test_verificar_destino_processo_contendo_documento_gerado()
    {
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, self::$documentoTeste, self::$destinatario);
    }
}
