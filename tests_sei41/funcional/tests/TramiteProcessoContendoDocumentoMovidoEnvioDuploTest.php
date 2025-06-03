<?php

/* Resumo:
Cria processo no org1
    cria um doc externo
    cria um doc interno
    move o doc externo para outro processo
    recebe de volta o doc externo enviado anteriormente
    envia o processo para org2
Org2 recebe o processo, n�o faz nada, e devolve pro org1;
Org1 envia novamente pro Org2, sem anexar nada;
*/


/**
 * Testes de tr�mite de processos contendo um documento movido
 *
 * Este mesmo documento deve ser recebido e assinalado como cancelado no destinat�rio e
 * a devolu��o do mesmo processo n�o deve ser impactado pela inser��o de outros documentos
 *
 * Execution Groups
 * @group execute_parallel_group1
 */
class TramiteProcessoContendoDocumentoMovidoEnvioDuploTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste1;
    public static $documentoTeste2;
    public static $documentoTeste3;
    public static $documentoTeste4;
    public static $protocoloTeste;
    public static $protocoloTesteFormatado;


    /**
     * Teste inicial de tr�mite de um processo contendo um documento movido
     *
     * @group envio
     * @large
     * 
     * @Depends CenarioBaseTestCase::setUpBeforeClass
     *
     * @return void
     */
    public function test_tramitar_processo_contendo_documento_movido()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Defini��o de dados de teste do processo principal
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        $processoSecundarioTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste1 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);
        self::$documentoTeste2 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        // Criar processo principal e processo secund�rio
        $protocoloSecundarioTeste = $this->cadastrarProcessoFixture($processoSecundarioTeste);
        self::$protocoloTeste = $this->cadastrarProcessoFixture(self::$processoTeste);

        // Cadastrando documentos no processo principal
        $this->cadastrarDocumentoExternoFixture(self::$documentoTeste1, self::$protocoloTeste->getDblIdProtocolo());
        $this->cadastrarDocumentoInternoFixture(self::$documentoTeste2, self::$protocoloTeste->getDblIdProtocolo());

        // Acessar sistema do this->REMETENTE do processo
        self::$protocoloTesteFormatado = self::$protocoloTeste->getStrProtocoloFormatado();
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->abrirProcesso(self::$protocoloTesteFormatado);


        // Movendo documento do processo principal para o processo secund�rio
        $documentoParaMover = $this->paginaProcesso->listarDocumentos()[0];
        $this->paginaProcesso->selecionarDocumento($documentoParaMover);
        $this->paginaDocumento->navegarParaMoverDocumento();
        $this->paginaMoverDocumento->moverDocumentoParaProcesso($protocoloSecundarioTeste->getStrProtocoloFormatado(), "Move doc externo para outro processo.");

        $protocoloSecundarioTeste = $protocoloSecundarioTeste->getStrProtocoloFormatado();
        $this->abrirProcesso($protocoloSecundarioTeste);

        // Movendo documento do processo secund�rio para o processo principal
        $documentoParaMover = $this->paginaProcesso->listarDocumentos()[0];
        $this->paginaProcesso->selecionarDocumento($documentoParaMover);
        $this->paginaDocumento->navegarParaMoverDocumento();
        $this->paginaMoverDocumento->moverDocumentoParaProcesso(self::$protocoloTesteFormatado, "Devolvendo documento externo");

        $this->abrirProcesso(self::$protocoloTesteFormatado);


        $this->tramitarProcessoExternamente(
            self::$protocoloTesteFormatado,
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false
        );

        $this->abrirProcesso(self::$protocoloTesteFormatado);

        
        $this->waitUntil(function ($testCase) {
            sleep(5);
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(mb_convert_encoding("Processo em tr�mite externo para ", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertTrue($paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Tr�mite externo do Processo %s para %s", self::$protocoloTesteFormatado, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);
        $this->validarProcessosTramitados(self::$protocoloTesteFormatado, true);

    }

    
     /**
     * Teste de devolu��o do processo recebido no destinat�rio
     *
     * @group verificacao_envio
     * @large
     *
     * @depends test_tramitar_processo_contendo_documento_movido
     *
     * @return void
     */
    public function test_somente_devolucao()
    {
        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);
        $this->abrirProcesso(self::$protocoloTesteFormatado);

        $this->tramitarProcessoExternamente(
            self::$protocoloTesteFormatado,
            self::$remetente['REP_ESTRUTURAS'],
            self::$remetente['NOME_UNIDADE'],
            self::$remetente['SIGLA_UNIDADE_HIERARQUIA'],
            false
        );

        $this->abrirProcesso(self::$protocoloTesteFormatado);

        $this->waitUntil(function ($testCase) {
            sleep(5);
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(mb_convert_encoding("Processo em tr�mite externo para ", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertTrue($paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);       
    }   



 /**
     * Teste de tr�mite secund�rio do processo para destinat�rio
     *
     * @group envio
     * @large
     * 
     * @Depends test_somente_devolucao
     *
     * @return void
     */
    public function test_tramite_novamente_para_org2()
    {
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->abrirProcesso(self::$protocoloTesteFormatado);       

        $this->tramitarProcessoExternamente(
            self::$protocoloTesteFormatado,
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false
        );

        $this->abrirProcesso(self::$protocoloTesteFormatado);

        $this->waitUntil(function ($testCase) {
            sleep(5);
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(mb_convert_encoding("Processo em tr�mite externo para ", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertTrue($paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Tr�mite externo do Processo %s para %s", self::$protocoloTesteFormatado, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);
        $this->validarProcessosTramitados(self::$protocoloTesteFormatado, true);        

    }    


}