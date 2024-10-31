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
        putenv("DATABASE_HOST=org2-database");

        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste = $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_060.pdf');
        
        $objProtocoloDTO  = $this->cadastrarProcessoFixture(self::$processoTeste);
        self::$protocoloTeste = $objProtocoloDTO->getStrProtocoloFormatado(); 

        // Altera tamanho máximo permitido para permitir o envio de arquivo superior à 50MBs
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
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(mb_convert_encoding("Processo em trâmite externo para ", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
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
