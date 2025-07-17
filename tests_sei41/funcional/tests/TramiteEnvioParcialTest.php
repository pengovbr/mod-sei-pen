<?php

/**
 * Teste de tr�mite com envio parcial habilitado
 * 
 * Execution Groups
 * @group execute_alone_group1
 */
class TramiteEnvioParcialTest extends FixtureCenarioBaseTestCase
{
  private $objProtocoloFixture;
  public static $remetente;
  public static $destinatario;
  public static $processoTestePrincipal;
  public static $protocoloTestePrincipal;
  public static $documentoTeste1;
  public static $documentoTeste2;
  public static $arrIdMapEnvioParcialOrgaoA;
  public static $arrIdMapEnvioParcialOrgaoB;

  /**
   * @inheritdoc
   * @return void
   */
  function setUp(): void
  {
    parent::setUp();
    self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
    self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
  }

  /*
     * Tramitar processo para o �rg�o 2 com envio parcial mapeado
     * @group mapeamento
     *
     * @return void
     */
  public function test_criar_processo_contendo_documento_tramitar_remetente_envio_parcial()
  {
    $this->criarCenarioTramiteEnvioParcialTest();

    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    $this->paginaBase->navegarParaControleProcesso();
    $this->paginaControleProcesso->abrirProcesso(self::$protocoloTestePrincipal->getStrProtocoloFormatado());
    $this->tramitarProcessoExternamente(
      self::$protocoloTestePrincipal,
      self::$destinatario['REP_ESTRUTURAS'],
      self::$destinatario['NOME_UNIDADE'],
      self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
      false
    );

    $this->sairSistema();
  }

  /*
     * Verificar processo recebido no �rg�o 2 com envio parcial mapeado
     * @group mapeamento
     *
     * @depends test_criar_processo_contendo_documento_tramitar_remetente_envio_parcial
     * @return void
     */
  public function test_verificar_processo_recebido_tramitar_destinatario_envio_parcial()
  {
    $strProtocoloTeste = self::$protocoloTestePrincipal->getStrProtocoloFormatado();
    $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

    $this->acessarSistema(
      self::$destinatario['URL'],
      self::$destinatario['SIGLA_UNIDADE'],
      self::$destinatario['LOGIN'],
      self::$destinatario['SENHA']
    );

    $this->paginaBase->navegarParaControleProcesso();
    $this->waitUntil(function ($testCase) use ($strProtocoloTeste) {
        sleep(5);
        $testCase->refresh();
        $this->paginaControleProcesso->abrirProcesso($strProtocoloTeste);
        return true;
    }, PEN_WAIT_TIMEOUT);
    
    $listaDocumentos = $this->paginaProcesso->listarDocumentos();

    // 12 - Validar dados  do processo
    $strTipoProcesso = mb_convert_encoding("Tipo de processo no �rg�o de origem: ", 'UTF-8', 'ISO-8859-1');
    $strTipoProcesso .= self::$processoTestePrincipal['TIPO_PROCESSO'];
    self::$processoTestePrincipal['OBSERVACOES'] = $orgaosDiferentes ? $strTipoProcesso : null;
    $this->validarDadosProcesso(
      self::$processoTestePrincipal['DESCRICAO'],
      self::$processoTestePrincipal['RESTRICAO'],
      self::$processoTestePrincipal['OBSERVACOES'],
      array(self::$processoTestePrincipal['INTERESSADOS'])
    );

    $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);

    $this->assertTrue(count($listaDocumentos) == 1);
    
    $this->sairSistema();
  }

  /*
     * Devolver processo ao �rg�o 1 com envio parcial mapeado
     * @group mapeamento
     *
     * @depends test_verificar_processo_recebido_tramitar_destinatario_envio_parcial
     * @return void
     */
  public function test_criar_documento_processo_recebido_tramitar_destinatario_envio_parcial()
  {
    $this->acessarSistema(
      self::$destinatario['URL'],
      self::$destinatario['SIGLA_UNIDADE'],
      self::$destinatario['LOGIN'],
      self::$destinatario['SENHA']
    );

    putenv("DATABASE_HOST=org2-database");

    $this->paginaBase->navegarParaControleProcesso();

    self::$documentoTeste2 = $this->gerarDadosDocumentoInternoTeste(self::$destinatario);
    $protocoloTestePrincipalOrg2 = $this->consultarProcessoFixture(self::$protocoloTestePrincipal->getStrProtocoloFormatado(), \ProtocoloRN::$TP_PROCEDIMENTO);
    $this->cadastrarDocumentoInternoFixture(self::$documentoTeste2, $protocoloTestePrincipalOrg2->getDblIdProtocolo());

    $this->paginaControleProcesso->abrirProcesso(self::$protocoloTestePrincipal->getStrProtocoloFormatado());

    sleep(5);

    $this->tramitarProcessoExternamente(
      self::$protocoloTestePrincipal,
      self::$remetente['REP_ESTRUTURAS'],
      self::$remetente['NOME_UNIDADE'],
      self::$remetente['SIGLA_UNIDADE_HIERARQUIA'],
      false
    );

    $this->sairSistema();
  }

  /*
     * Verificar processo recebido no �rg�o 1 com envio parcial mapeado
     * @group mapeamento
     *
     * @depends test_criar_documento_processo_recebido_tramitar_destinatario_envio_parcial
     * @return void
     */
  public function test_verificar_processo_recebido_tramitar_remetente_envio_parcial()
  {
    $strProtocoloTeste = self::$protocoloTestePrincipal->getStrProtocoloFormatado();

    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    $this->paginaBase->navegarParaControleProcesso();
    $this->waitUntil(function ($testCase) use ($strProtocoloTeste) {
        sleep(5);
        $testCase->refresh();
        $this->paginaControleProcesso->abrirProcesso($strProtocoloTeste);
        return true;
    }, PEN_WAIT_TIMEOUT);
    
    $listaDocumentos = $this->paginaProcesso->listarDocumentos();

    // 12 - Validar dados  do processo
    $strTipoProcesso = mb_convert_encoding("Tipo de processo no �rg�o de origem: ", 'UTF-8', 'ISO-8859-1');
    $strTipoProcesso .= self::$processoTestePrincipal['TIPO_PROCESSO'];
    self::$processoTestePrincipal['OBSERVACOES'] = $orgaosDiferentes ? $strTipoProcesso : null;
    $this->validarDadosProcesso(
      self::$processoTestePrincipal['DESCRICAO'],
      self::$processoTestePrincipal['RESTRICAO'],
      self::$processoTestePrincipal['OBSERVACOES'],
      array(self::$processoTestePrincipal['INTERESSADOS'])
    );

    $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);

    $this->assertTrue(count($listaDocumentos) == 2);

    $documentosTeste = array(self::$documentoTeste1, self::$documentoTeste2);
    for ($i = 0; $i < count($listaDocumentos); $i++) {
      $this->validarDadosDocumento($listaDocumentos[$i], $documentosTeste[$i], self::$remetente, false, null);
    }
    
    $this->sairSistema();
  }

    public function test_realizar_pedido_reproducao_ultimo_tramite()
    {
        $strProtocoloTeste = self::$protocoloTestePrincipal->getStrProtocoloFormatado();
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        
        // 11 - Reproduzir �ltimo tr�mite
        $this->abrirProcesso($strProtocoloTeste);
        $resultadoReproducao = $this->paginaProcesso->reproduzirUltimoTramite();
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

    public function test_reproducao_ultimo_tramite()
    {
        $strProtocoloTeste = self::$protocoloTestePrincipal->getStrProtocoloFormatado();

        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);

        $this->abrirProcesso($strProtocoloTeste);
       
        $this->waitUntil(function ($testCase) {
            sleep(5);
            $testCase->refresh();
            $testCase->paginaProcesso->navegarParaConsultarAndamentos();
            $mensagemTramite = mb_convert_encoding("Reprodu��o de �ltimo tr�mite recebido na entidade", 'UTF-8', 'ISO-8859-1');
            $testCase->assertTrue($testCase->paginaConsultarAndamentos->contemTramite($mensagemTramite));
            return true;
        }, PEN_WAIT_TIMEOUT);

    }

    public function test_reproducao_ultimo_tramite_remetente_finalizado()
    {
        $strProtocoloTeste = self::$protocoloTestePrincipal->getStrProtocoloFormatado();

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        // 11 - Abrir protocolo na tela de controle de processos
        $this->abrirProcesso($strProtocoloTeste);

        $this->waitUntil(function ($testCase) {
            sleep(5);
            $testCase->refresh();
            $testCase->paginaProcesso->navegarParaConsultarAndamentos();
            $mensagemTramite = mb_convert_encoding("Reprodu��o de �ltimo tr�mite finalizado para o protocolo ".  $strProtocoloTeste, 'UTF-8', 'ISO-8859-1');
            $testCase->assertTrue($testCase->paginaConsultarAndamentos->contemTramite($mensagemTramite));
            return true;
        }, PEN_WAIT_TIMEOUT);
        
        $listaDocumentos = $this->paginaProcesso->listarDocumentos();

        $this->assertTrue(count($listaDocumentos) == 2);

        $documentosTeste = array(self::$documentoTeste1, self::$documentoTeste2);
        for ($i = 0; $i < count($listaDocumentos); $i++) {
          $this->validarDadosDocumento($listaDocumentos[$i], $documentosTeste[$i], self::$remetente, false, null);
        }
    }

  /**
   * Excluir mapeamentos de Envio Parcial no Remetente e Destinat�rio 
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
     * Criar processo e mapear Envio Parcial no Remetente e Destinat�rio
     * @group mapeamento
     *
     * @return void
     */
  private function criarCenarioTramiteEnvioParcialTest()
  {
    self::$processoTestePrincipal = $this->gerarDadosProcessoTeste(self::$remetente);
    self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

    self::$protocoloTestePrincipal = $this->cadastrarProcessoFixture(self::$processoTestePrincipal);
    $this->cadastrarDocumentoInternoFixture(self::$documentoTeste1, self::$protocoloTestePrincipal->getDblIdProtocolo());

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

    // Mapear Envio Parcial no Destinat�rio
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
