<?php

/* Resumo:
Cria processo no org1
    cria um doc externo
    cria um doc interno
    move o doc externo para outro processo
    recebe de volta o doc externo enviado anteriormente
    envia o processo para org2
Org2 recebe o processo, não faz nada, e devolve pro org1;
Org1 envia novamente pro Org2, sem anexar nada;
*/

/**
 * Testes de trâmite de processos contendo um documento movido
 *
 * Este mesmo documento deve ser recebido e assinalado como cancelado no destinatário e
 * a devolução do mesmo processo não deve ser impactado pela inserção de outros documentos
 *
 * Execution Groups
 * @group execute_parallel_group1
 */
class TramiteEnvioParcialProcessoContendoDocumentoMovidoDestinatarioTest extends FixtureCenarioBaseTestCase
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


    public static $arrIdMapEnvioParcialOrgaoA;
    public static $arrIdMapEnvioParcialOrgaoB;

    /**
     * Teste inicial de trâmite de um processo contendo um documento movido
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

        $this->criarCenarioTramiteEnvioParcialTest();

        // Definição de dados de teste do processo principal
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        $processoSecundarioTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        // Criar processo principal
        self::$protocoloTeste = $this->cadastrarProcessoFixture(self::$processoTeste);

        // Cadastrando documentos no processo principal
        $this->cadastrarDocumentoInternoFixture(self::$documentoTeste1, self::$protocoloTeste->getDblIdProtocolo());

        // Acessar sistema do this->REMETENTE do processo
        self::$protocoloTesteFormatado = self::$protocoloTeste->getStrProtocoloFormatado();
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
            $testCase->assertStringNotContainsString(mb_convert_encoding("Processo em trâmite externo para ", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertTrue($paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTesteFormatado, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);
        $this->validarProcessosTramitados(self::$protocoloTesteFormatado, true);

    }

     /**
     * Teste de devolução do processo recebido no destinatário
     *
     * @group verificacao_envio
     * @large
     *
     * @depends test_tramitar_processo_contendo_documento_movido
     *
     * @return void
     */
    public function test_adiciona_documentos_move_e_devolve()
    {

        putenv("DATABASE_HOST=org2-database");

        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);

        self::$documentoTeste2 = $this->gerarDadosDocumentoExternoTeste(self::$destinatario);
        self::$documentoTeste3 = $this->gerarDadosDocumentoExternoTeste(self::$destinatario);
        self::$documentoTeste4 = $this->gerarDadosDocumentoExternoTeste(self::$destinatario);
        $protocoloTestePrincipalOrg2 = $this->consultarProcessoFixture(self::$protocoloTesteFormatado, \ProtocoloRN::$TP_PROCEDIMENTO);

        $this->cadastrarDocumentoExternoFixture(self::$documentoTeste2, $protocoloTestePrincipalOrg2->getDblIdProtocolo());
        $this->cadastrarDocumentoExternoFixture(self::$documentoTeste3, $protocoloTestePrincipalOrg2->getDblIdProtocolo());
        $this->cadastrarDocumentoExternoFixture(self::$documentoTeste4, $protocoloTestePrincipalOrg2->getDblIdProtocolo());

        $protocoloSecundarioTeste = $this->cadastrarProcessoFixture($processoSecundarioTeste);
        $this->abrirProcesso(self::$protocoloTesteFormatado);

        // Movendo documento do processo principal para o processo secundário
        $documentoParaMover = $this->paginaProcesso->listarDocumentos()[1];
        $this->paginaProcesso->selecionarDocumento($documentoParaMover);
        $this->paginaDocumento->navegarParaMoverDocumento();
        $this->paginaMoverDocumento->moverDocumentoParaProcesso($protocoloSecundarioTeste->getStrProtocoloFormatado(), "Move doc externo para outro processo.");

        $protocoloSecundarioTeste = $protocoloSecundarioTeste->getStrProtocoloFormatado();
        $this->abrirProcesso($protocoloSecundarioTeste);

        // Movendo documento do processo secundário para o processo principal
        $documentoParaMover = $this->paginaProcesso->listarDocumentos()[0];
        $this->paginaProcesso->selecionarDocumento($documentoParaMover);
        $this->paginaDocumento->navegarParaMoverDocumento();
        $this->paginaMoverDocumento->moverDocumentoParaProcesso(self::$protocoloTesteFormatado, "Devolvendo documento externo");

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
            $testCase->assertStringNotContainsString(mb_convert_encoding("Processo em trâmite externo para ", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertTrue($paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);       
    }   



 /**
     * Teste de trâmite secundário do processo para destinatário
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
            $testCase->assertStringNotContainsString(mb_convert_encoding("Processo em trâmite externo para ", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertTrue($paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTesteFormatado, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);
        $this->validarProcessosTramitados(self::$protocoloTesteFormatado, true);        

    }

    /**
     * Teste de realizar reprodução de último tramite
     *
     * @group envio
     * @large
     *
     * @depends test_tramite_novamente_para_org2
     *
     * @return void
     */
    public function test_realizar_pedido_reproducao_ultimo_tramite()
    {
        $strProtocoloTeste = self::$protocoloTesteFormatado;
        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);
        
        // 11 - Reproduzir último trâmite
        $this->abrirProcesso($strProtocoloTeste);
        $resultadoReproducao = $this->paginaProcesso->reproduzirUltimoTramite();
        $this->assertStringContainsString(mb_convert_encoding("Reprodução de último trâmite executado com sucesso!", 'UTF-8', 'ISO-8859-1'), $resultadoReproducao);
        $this->refresh();
        $this->waitUntil(function ($testCase) {
            sleep(5);
            $testCase->refresh();
            $testCase->paginaProcesso->navegarParaConsultarAndamentos();
            $mensagemTramite = mb_convert_encoding("Reprodução de último trâmite iniciado para o protocolo ".  $strProtocoloTeste, 'UTF-8', 'ISO-8859-1');
            $testCase->assertTrue($testCase->paginaConsultarAndamentos->contemTramite($mensagemTramite));
            return true;
        }, PEN_WAIT_TIMEOUT);

    }

    /**
     * Teste para verificar a reprodução de último tramite no destinatario
     *
     * @group envio
     * @large
     *
     * @depends test_tramite_novamente_para_org2
     *
     * @return void
     */
    public function test_reproducao_ultimo_tramite()
    {
        $strProtocoloTeste = self::$protocoloTesteFormatado;

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->abrirProcesso($strProtocoloTeste);
       
        $this->waitUntil(function ($testCase) {
            sleep(5);
            $testCase->refresh();
            $testCase->paginaProcesso->navegarParaConsultarAndamentos();
            $mensagemTramite = mb_convert_encoding("Reprodução de último trâmite recebido na entidade", 'UTF-8', 'ISO-8859-1');
            $testCase->assertTrue($testCase->paginaConsultarAndamentos->contemTramite($mensagemTramite));
            return true;
        }, PEN_WAIT_TIMEOUT);

    }

    /**
     * Teste para verificar a reprodução de último tramite no remetente
     *
     * @group envio
     * @large
     *
     * @depends test_tramite_novamente_para_org2
     *
     * @return void
     */
    public function test_reproducao_ultimo_tramite_remetente_finalizado()
    {
        $strProtocoloTeste = self::$protocoloTesteFormatado;

        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);

        // 11 - Abrir protocolo na tela de controle de processos
        $this->abrirProcesso($strProtocoloTeste);

        $this->waitUntil(function ($testCase) {
            sleep(5);
            $testCase->refresh();
            $testCase->paginaProcesso->navegarParaConsultarAndamentos();
            $mensagemTramite = mb_convert_encoding("Reprodução de último trâmite finalizado para o protocolo ".  $strProtocoloTeste, 'UTF-8', 'ISO-8859-1');
            $testCase->assertTrue($testCase->paginaConsultarAndamentos->contemTramite($mensagemTramite));
            return true;
        }, PEN_WAIT_TIMEOUT);
        
    }


    /**
     * Excluir mapeamentos de Envio Parcial no Remetente e Destinatário 
     * @group mapeamento
     */
  public static function tearDownAfterClass(): void
  {
    $penMapEnvioParcialFixture = new \PenMapEnvioParcialFixture();

    putenv("DATABASE_HOST=org1-database");
    foreach (self::$arrIdMapEnvioParcialOrgaoA as $idMapEnvioParcial) {
      $penMapEnvioParcialFixture->remover([
        'Id' => $idMapEnvioParcial
      ]);
    }

    putenv("DATABASE_HOST=org2-database");
    foreach (self::$arrIdMapEnvioParcialOrgaoB as $idMapEnvioParcial) {
      $penMapEnvioParcialFixture->remover([
        'Id' => $idMapEnvioParcial
      ]);
    }
    putenv("DATABASE_HOST=org1-database");
    parent::tearDownAfterClass();
  }


    /*
    * Criar processo e mapear Envio Parcial no Remetente e Destinatário
    * @group mapeamento
    *
    * @return void
    */
  private function criarCenarioTramiteEnvioParcialTest()
  {

    // Mapear Envio Parcial no Remetente
    self::$arrIdMapEnvioParcialOrgaoA = array();
    putenv("DATABASE_HOST=org1-database");
    $objPenMapEnvioParcialFixture = new PenMapEnvioParcialFixture();
    $objMapEnvioParcial = $objPenMapEnvioParcialFixture->carregar([
      'IdEstrutura' => self::$destinatario['ID_REP_ESTRUTURAS'],
      'StrEstrutura' => self::$destinatario['REP_ESTRUTURAS'],
      'IdUnidadePen' => self::$destinatario['ID_ESTRUTURA'],
      'StrUnidadePen' => self::$destinatario['NOME_UNIDADE']
    ]);
    self::$arrIdMapEnvioParcialOrgaoA[] = $objMapEnvioParcial->getDblId();

    // Mapear Envio Parcial no Destinatário
    self::$arrIdMapEnvioParcialOrgaoB = array();
    putenv("DATABASE_HOST=org2-database");
    $objPenMapEnvioParcialFixture = new PenMapEnvioParcialFixture();
    $objMapEnvioParcial = $objPenMapEnvioParcialFixture->carregar([
      'IdEstrutura' => self::$remetente['ID_REP_ESTRUTURAS'],
      'StrEstrutura' => self::$remetente['REP_ESTRUTURAS'],
      'IdUnidadePen' => self::$remetente['ID_ESTRUTURA'],
      'StrUnidadePen' => self::$remetente['NOME_UNIDADE']
    ]);
    self::$arrIdMapEnvioParcialOrgaoB[] = $objMapEnvioParcial->getDblId();

    putenv("DATABASE_HOST=org1-database");
  }
}