<?php

/**
 * @group rodarseparado
 * @group rodarseparado2
 * @group execute_alone_group1
 */
class TramiteProcessoContendoDocumentoExternoParticionadoTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste;
    public static $protocoloTeste;

    public static function setUpBeforeClass() :void {
        
        parent::setUpBeforeClass();
        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);    
        $bancoOrgaoA->execute("update infra_parametro set valor = ? where nome = ?", array(70, 'SEI_TAM_MB_DOC_EXTERNO'));

    }      
        
    public static function tearDownAfterClass() :void {

        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);    
        $bancoOrgaoA->execute("update infra_parametro set valor = ? where nome = ?", array(50, 'SEI_TAM_MB_DOC_EXTERNO'));

    }


    /**
     * Teste de tr�mite externo de processo contendo documento externo particionado acima de 60Mb
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

        // Configura��o do dados para teste do cen�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        putenv("DATABASE_HOST=org2-database");

        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste = $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_060.pdf');
        
        $objProtocoloDTO  = $this->cadastrarProcessoFixture(self::$processoTeste);
        self::$protocoloTeste = $objProtocoloDTO->getStrProtocoloFormatado(); 

        // Altera tamanho m�ximo permitido para permitir o envio de arquivo superior � 50MBs
        $bancoOrgaoB = new DatabaseUtils(CONTEXTO_ORGAO_B);
        try {
            $bancoOrgaoB->execute("update infra_parametro set valor = ? where nome = ?", array(70, 'SEI_TAM_MB_DOC_EXTERNO'));
            $this->cadastrarDocumentoExternoFixture(self::$documentoTeste, $objProtocoloDTO->getDblIdProtocolo());
        } finally {
            $bancoOrgaoB->execute("update infra_parametro set valor = ? where nome = ?", array(50, 'SEI_TAM_MB_DOC_EXTERNO'));
        }
        putenv("DATABASE_HOST=org1-database");
        
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->abrirProcesso(self::$protocoloTeste);

        $this->tramitarProcessoExternamente(
            self::$protocoloTeste, self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false, null, PEN_WAIT_TIMEOUT_ARQUIVOS_GRANDES
        );
    }


    /**
     * Teste de verifica��o do correto envio do processo no sistema remetente
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
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(mb_convert_encoding("Processo em tr�mite externo para ", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT_ARQUIVOS_GRANDES);

        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Tr�mite externo do Processo %s para %s", self::$protocoloTeste, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);
        $this->validarProcessosTramitados(self::$protocoloTeste, $orgaosDiferentes);
    }


    /**
     * Teste de realizar reprodu��o de �ltimo tramite
     *
     * @group envio
     * @large
     *
     * @depends test_verificar_origem_processo_contendo_documento_externo_60mb
     *
     * @return void
     */
    public function test_realizar_pedido_reproducao_ultimo_tramite()
    {
        $strProtocoloTeste = self::$protocoloTeste;
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);
        
        // 11 - Reproduzir �ltimo tr�mite
        $this->abrirProcesso($strProtocoloTeste);
        $resultadoReproducao = $this->paginaProcesso->reproduzirUltimoTramite();
        sleep(5);
        $this->assertStringContainsString(mb_convert_encoding("Reprodu��o de �ltimo tr�mite executado com sucesso!", 'UTF-8', 'ISO-8859-1'), $resultadoReproducao);
        $this->refresh();
        $this->waitUntil(function ($testCase) {
            sleep(5);
            $testCase->refresh();
            $testCase->paginaProcesso->navegarParaConsultarAndamentos();
            $mensagemTramite = mb_convert_encoding("Reprodu��o de �ltimo tr�mite iniciado para o protocolo ".  $strProtocoloTeste, 'UTF-8', 'ISO-8859-1');
            $testCase->assertTrue($testCase->paginaConsultarAndamentos->contemTramite($mensagemTramite));
            return true;
        }, PEN_WAIT_TIMEOUT);

    }


    /**
     * Teste de verifica��o do correto recebimento do processo contendo apenas um documento interno (gerado)
     *
     * @group verificacao_recebimento
     * @large
     *
     * @depends test_realizar_pedido_reproducao_ultimo_tramite
     *
     * @return void
     */
    public function test_verificar_destino_processo_contendo_documento_externo_60mb()
    {
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, self::$documentoTeste, self::$destinatario);
    }
}
