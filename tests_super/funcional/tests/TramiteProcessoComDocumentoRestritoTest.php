<?php

/**
 * Execution Groups
 * @group execute_alone_group2
 */
class TramiteProcessoComDocumentoRestritoTest extends FixtureCenarioBaseTestCase
{
  public static $remetente;
  public static $destinatario;
  public static $processoTeste;
  public static $documentoTeste;
  public static $protocoloTeste;

  function setUp(): void
  {
    parent::setUp();

    // Configura��o do dados para teste do cen�rio
    self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
    self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
  }

  /**
   * Teste de tr�mite externo de processo com documentos restritos
   *
   * @group envio
   * @large
   * 
   * @Depends CenarioBaseTestCase::setUpBeforeClass
   *
   * @return void
   */
  public function test_tramitar_processo_com_documento_restrito()
  {
    self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
    self::$documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

    // Acessar sistema do this->REMETENTE do processo
    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    self::$protocoloTeste = $this->cadastrarProcessoFixture(self::$processoTeste);  // Cadastrar novo processo de teste
    self::$documentoTeste["RESTRICAO"] = \ProtocoloRN::$NA_RESTRITO; // Configura��o de documento restrito
    self::$documentoTeste["HIPOTESE_LEGAL"] = self::$remetente["HIPOTESE_RESTRICAO"]; // Configurar Hipotese legal
    $this->cadastrarDocumentoInternoFixture(self::$documentoTeste, self::$protocoloTeste->getDblIdProtocolo()); // Incluir Documentos no Processo

    $this->paginaBase->navegarParaControleProcesso();
    $this->paginaControleProcesso->abrirProcesso(self::$protocoloTeste->getStrProtocoloFormatado());
    
    // Tr�mitar Externamento processo para �rg�o/unidade destinat�ria
    $this->tramitarProcessoExternamente(
      self::$protocoloTeste,
      self::$destinatario['REP_ESTRUTURAS'],
      self::$destinatario['NOME_UNIDADE'],
      self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
      false
    );
  }

  /**
   * Teste de verifica��o do correto envio do processo no sistema remetente
   *
   * @group verificacao_envio
   * @large
   *
   * @depends test_tramitar_processo_com_documento_restrito
   *
   * @return void
   */
  public function test_verificar_origem_processo_com_documento_restrito()
  {
    $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    $this->paginaBase->pesquisar(self::$protocoloTeste->getStrProtocoloFormatado());

    // 6 - Verificar se situa��o atual do processo est� como bloqueado
    $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
      sleep(5);
      $testCase->refresh();
      $paginaProcesso = new PaginaProcesso($testCase);
      $testCase->assertStringNotContainsString(mb_convert_encoding("Processo em tr�mite externo para ", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
      $testCase->assertFalse($paginaProcesso->processoAberto());
      $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
      return true;
    }, PEN_WAIT_TIMEOUT);

    // 7 - Validar se recibo de tr�mite foi armazenado para o processo (envio e conclus�o)
    $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
    $mensagemRecibo = sprintf("Tr�mite externo do Processo %s para %s", self::$protocoloTeste->getStrProtocoloFormatado(), $unidade);
    $this->validarRecibosTramite($mensagemRecibo, true, true);

    // 8 - Validar hist�rico de tr�mite do processo
    $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);

    // 9 - Verificar se processo est� na lista de Processos Tramitados Externamente
    $this->validarProcessosTramitados(self::$protocoloTeste->getStrProtocoloFormatado(), $orgaosDiferentes);
  }

  /**
   * Teste de verifica��o do correto recebimento do processo contendo apenas um documento interno (gerado)
   *
   * @group verificacao_recebimento
   * @large
   *
   * @depends test_verificar_origem_processo_com_documento_restrito
   *
   * @return void
   */
  public function test_verificar_destino_processo_com_documento_restrito()
  {
    $strProtocoloTeste = self::$protocoloTeste->getStrProtocoloFormatado();
    $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

    $this->acessarSistema(
      self::$destinatario['URL'],
      self::$destinatario['SIGLA_UNIDADE'],
      self::$destinatario['LOGIN'],
      self::$destinatario['SENHA']
    );

    // 11 - Abrir protocolo na tela de controle de processos
    $this->paginaBase->navegarParaControleProcesso();
    $this->paginaControleProcesso->abrirProcesso($strProtocoloTeste);
    $listaDocumentos = $this->paginaProcesso->listarDocumentos();

    // 12 - Validar dados  do processo
    $strTipoProcesso = mb_convert_encoding("Tipo de processo no �rg�o de origem: ", 'UTF-8', 'ISO-8859-1');
    $strTipoProcesso .= self::$processoTeste['TIPO_PROCESSO'];
    self::$processoTeste['OBSERVACOES'] = $orgaosDiferentes ? $strTipoProcesso : null;
    $this->validarDadosProcesso(
      self::$processoTeste['DESCRICAO'],
      self::$processoTeste['RESTRICAO'],
      self::$processoTeste['OBSERVACOES'],
      array(self::$processoTeste['INTERESSADOS'])
    );

    // 13 - Verificar recibos de tr�mite
    $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);

    // 14 - Validar dados do documento
    $this->assertTrue(count($listaDocumentos) == 1);
    $this->validarDadosDocumento($listaDocumentos[0], self::$documentoTeste, self::$destinatario);
  }

    /**
     * Teste de realizar reprodu��o de �ltimo tramite
     *
     * @group envio
     * @large
     *
     * @depends test_verificar_destino_processo_com_documento_restrito
     *
     * @return void
     */
  public function test_realizar_pedido_reproducao_ultimo_tramite()
  {
      $strProtocoloTeste = self::$protocoloTeste->getStrProtocoloFormatado();
      $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);
      
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

    /**
     * Teste para verificar a reprodu��o de �ltimo tramite no destinatario
     *
     * @group envio
     * @large
     *
     * @depends test_verificar_destino_processo_com_documento_restrito
     *
     * @return void
     */
  public function test_reproducao_ultimo_tramite()
  {
      $strProtocoloTeste = self::$protocoloTeste->getStrProtocoloFormatado();

      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

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

    /**
     * Teste para verificar a reprodu��o de �ltimo tramite no remetente
     *
     * @group envio
     * @large
     *
     * @depends test_verificar_destino_processo_com_documento_restrito
     *
     * @return void
     */
  public function test_reproducao_ultimo_tramite_remetente_finalizado()
  {
      $strProtocoloTeste = self::$protocoloTeste->getStrProtocoloFormatado();

      $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);

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

        // 13 - Verificar recibos de tr�mite
      $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);

      $listaDocumentos = $this->paginaProcesso->listarDocumentos();
      $this->assertTrue(count($listaDocumentos) == 1);
      $this->validarDadosDocumento($listaDocumentos[0], self::$documentoTeste, self::$destinatario);
  }
  
  public static function tearDownAfterClass(): void
  {
    parent::tearDownAfterClass();
  }
}
