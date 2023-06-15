<?php

class TramiteProcessoContendoDocumentoExternoParticionadoTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste;
    public static $protocoloTeste;

    public static function setUpBeforeClass() :void {

        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);    
        $bancoOrgaoA->execute("update infra_parametro set valor = ? where nome = ?", array(70, 'SEI_TAM_MB_DOC_EXTERNO'));

    }      
        
    public static function tearDownAfterClass() :void {

        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);    
        $bancoOrgaoA->execute("update infra_parametro set valor = ? where nome = ?", array(50, 'SEI_TAM_MB_DOC_EXTERNO'));

    }


    /**
     * Teste de trâmite externo de processo contendo documento externo particionado acima de 60Mb
     *
     * @group envio
     * @large
     * 
     * @Depends CenarioBaseTestCase::setUpBeforeClass
     *
     * @return void
     */
    public function test_tramitar_processo_contendo_documento_externo_60mb()
    {
        //Aumenta o tempo de timeout devido ao tamanho do arquivo arquivo_060.pdf
        $this->setSeleniumServerRequestsTimeout(6000);

        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste = $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_060.pdf');

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        self::$protocoloTeste = $this->cadastrarProcesso(self::$processoTeste);

        // Altera tamanho máximo permitido para permitir o envio de arquivo superior à 50MBs
        $bancoOrgaoB = new DatabaseUtils(CONTEXTO_ORGAO_B);
        try {
            $bancoOrgaoB->execute("update infra_parametro set valor = ? where nome = ?", array(70, 'SEI_TAM_MB_DOC_EXTERNO'));
            $this->cadastrarDocumentoExterno(self::$documentoTeste);
        } finally {
            $bancoOrgaoB->execute("update infra_parametro set valor = ? where nome = ?", array(50, 'SEI_TAM_MB_DOC_EXTERNO'));
        }

        $this->tramitarProcessoExternamente(
            self::$protocoloTeste, self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false, null, PEN_WAIT_TIMEOUT_ARQUIVOS_GRANDES
        );
    }


    /**
     * Teste de verificação do correto envio do processo no sistema remetente
     *
     * @group verificacao_envio
     * @large
     *
     * @depends test_tramitar_processo_contendo_documento_externo_60mb
     *
     * @return void
     */
    public function test_verificar_origem_processo_contendo_documento_externo_60mb()
    {
        //Aumenta o tempo de timeout devido ao tamanho do arquivo arquivo_060.pdf
        $this->setSeleniumServerRequestsTimeout(60000);

        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->abrirProcesso(self::$protocoloTeste);

        $this->waitUntil(function($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $this->atualizarTramitesPEN();
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(utf8_encode("Processo em trâmite externo para "), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT_ARQUIVOS_GRANDES);

        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTeste, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);
        $this->validarProcessosTramitados(self::$protocoloTeste, $orgaosDiferentes);
    }


    /**
     * Teste de verificação do correto recebimento do processo contendo apenas um documento interno (gerado)
     *
     * @group verificacao_recebimento
     * @large
     *
     * @depends test_verificar_origem_processo_contendo_documento_externo_60mb
     *
     * @return void
     */
    public function test_verificar_destino_processo_contendo_documento_externo_60mb()
    {
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, self::$documentoTeste, self::$destinatario);
    }
}
