<?php

/**
 * Testes de trâmite de processos contendo um documento movido sem anexo
 *
 * Este mesmo documento deve ser recebido e assinalado como cancelado no destinatário e
 * a devolução do mesmo processo não deve ser impactado pela inserção de outros documentos
 *
 * Execution Groups
 * @group execute_alone_group6
 */
class TramiteProcessoContendoDocumentoMovidoSemAnexoTest extends FixtureCenarioBaseTestCase
{
  public static $remetente;
  public static $destinatario;
  public static $processoTeste;
  public static $documentoTeste1;
  public static $documentoTeste2;
  public static $documentoTeste3;
  public static $documentoTeste4;
  public static $protocoloTeste;

  /**
   * @inheritdoc
   * @return void
   */
  function setUp(): void
  {
    parent::setUp();
  }

  /**
   * Teste inicial de trâmite de um processo contendo um documento movido
   *
   * @group envio
   *
   * @return void
   */
  public function test_tramitar_processo_contendo_documento_movido_sem_anexo()
  {
    
    self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
    self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

    // Definição de dados de teste do processo principal
    self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
    $processoSecundarioTeste = $this->gerarDadosProcessoTeste(self::$remetente);
    self::$documentoTeste1 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);
    self::$documentoTeste2 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

    // Criar processo secundário para o qual o documento será movido
    $protocoloSecundarioTeste = $this->cadastrarProcessoFixture($processoSecundarioTeste);

    // Cadastrar novo processo de teste
    self::$protocoloTeste = $this->cadastrarProcessoFixture(self::$processoTeste);
    // Incluir documentos a ser movido
    $documentoMover = $this->cadastrarDocumentoExternoFixture(self::$documentoTeste1, self::$protocoloTeste->getDblIdProtocolo());
    
    // Acessar sistema do this->REMETENTE do processo
    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    // Navegar para processo cadastrado
    $this->paginaBase->navegarParaControleProcesso();
    $this->paginaControleProcesso->abrirProcesso(self::$protocoloTeste->getStrProtocoloFormatado());

    // Navegar para mover documento
    $nomeDocArvore = self::$documentoTeste1['TIPO_DOCUMENTO'] . ' 1 (' . str_pad($documentoMover->getDblIdDocumento(), 6, "0", STR_PAD_LEFT) . ')';
    $this->paginaProcesso->selecionarDocumento($nomeDocArvore);
    $this->paginaDocumento->navegarParaMoverDocumento();
    $this->paginaMoverDocumento->moverDocumentoParaProcesso($protocoloSecundarioTeste->getStrProtocoloFormatado(), "Motivo de teste");

    // Cadastramento de documento adicional
    $this->cadastrarDocumentoInternoFixture(self::$documentoTeste2, self::$protocoloTeste->getDblIdProtocolo());
    $this->paginaBase->refresh();

    // Trâmitar Externamento processo para órgão/unidade destinatária
    $this->tramitarProcessoExternamente(
      self::$protocoloTeste,
      self::$destinatario['REP_ESTRUTURAS'],
      self::$destinatario['NOME_UNIDADE'],
      self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
      false
    );
  }

  /**
   * Teste de verificação do correto envio do processo no sistema remetente
   *
   * @group verificacao_envio
   *
   * @depends test_tramitar_processo_contendo_documento_movido_sem_anexo
   *
   * @return void
   */
  public function test_verificar_origem_processo()
  {
    $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    $this->paginaBase->pesquisar(self::$protocoloTeste->getStrProtocoloFormatado());

    $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
      sleep(5);
      $testCase->refresh();
      $paginaProcesso = new PaginaProcesso($testCase);
      $testCase->assertStringNotContainsString(mb_convert_encoding("Processo em trâmite externo para ", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
      $testCase->assertFalse($paginaProcesso->processoAberto());
      $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
      return true;
    }, PEN_WAIT_TIMEOUT);

    $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
    $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTeste->getStrProtocoloFormatado(), $unidade);
    $this->validarRecibosTramite($mensagemRecibo, true, true);
    $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);
    $this->validarProcessosTramitados(self::$protocoloTeste->getStrProtocoloFormatado(), $orgaosDiferentes);
  }

  /**
   * Teste de verificação do correto recebimento do processo com documento movido no destinatário
   *
   * @group verificacao_recebimento
   *
   * @depends test_verificar_origem_processo
   *
   * @return void
   */
  public function test_verificar_destino_processo_com_documento_movido_sem_anexo()
  {
    $strProtocoloTeste = self::$protocoloTeste->getStrProtocoloFormatado();
    $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

    $this->acessarSistema(
      self::$destinatario['URL'],
      self::$destinatario['SIGLA_UNIDADE'],
      self::$destinatario['LOGIN'],
      self::$destinatario['SENHA']
    );

    // Navegar para processo cadastrado
    $this->paginaBase->navegarParaControleProcesso();
    $this->paginaControleProcesso->abrirProcesso(self::$protocoloTeste->getStrProtocoloFormatado());

    $strTipoProcesso = mb_convert_encoding("Tipo de processo no órgão de origem: ", 'UTF-8', 'ISO-8859-1');
    $strTipoProcesso .= self::$processoTeste['TIPO_PROCESSO'];
    $strObservacoes = $orgaosDiferentes ? $strTipoProcesso : null;
    $this->validarDadosProcesso(
      self::$processoTeste['DESCRICAO'],
      self::$processoTeste['RESTRICAO'],
      $strObservacoes,
      array(self::$processoTeste['INTERESSADOS'])
    );

    $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);

    // Validação dos dados do processo principal
    $listaDocumentosProcessoPrincipal = $this->paginaProcesso->listarDocumentos();
    $this->assertEquals(2, count($listaDocumentosProcessoPrincipal));
    $this->validarDocumentoCancelado($listaDocumentosProcessoPrincipal[0]);
    $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[1], self::$documentoTeste2, self::$destinatario);
  }

  /**
   * Teste de trâmite externo de processo realizando a devolução para a mesma unidade de origem contendo
   * mais dois documentos, sendo um deles movido
   *
   * @group envio
   *
   * @depends test_verificar_destino_processo_com_documento_movido_sem_anexo
   *
   * @return void
   */
  public function test_devolucao_processo_para_origem_com_novo_documento_movido_sem_anexo()
  {
    $strProtocoloTeste = self::$protocoloTeste->getStrProtocoloFormatado();
    self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
    self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

    // Mudar banco para cadastrar DTO
    putenv("DATABASE_HOST=org2-database");

    // Definição de dados de teste do processo principal
    $processoSecundarioTeste = $this->gerarDadosProcessoTeste(self::$remetente);
    self::$documentoTeste3 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);
    self::$documentoTeste4 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

    // Consultar processo org-2
    $processoOrg2 = $this->consultarProcessoFixture($strProtocoloTeste, \ProtocoloRN::$TP_PROCEDIMENTO);

    // Criar processo secundário para o qual o documento será movido
    $protocoloSecundarioTeste = $this->cadastrarProcessoFixture($processoSecundarioTeste);

    // Incluir documentos a ser movido
    $documentoMover = $this->cadastrarDocumentoExternoFixture(self::$documentoTeste3, $processoOrg2->getDblIdProtocolo());

    // Acessar sistema do this->REMETENTE do processo
    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    // Navegar para processo cadastrado
    $this->paginaBase->navegarParaControleProcesso();
    $this->paginaControleProcesso->abrirProcesso($strProtocoloTeste);

    // Navegar para mover documento
    $nomeDocArvore = self::$documentoTeste1['TIPO_DOCUMENTO'] . ' 1 (' . str_pad($documentoMover->getDblIdDocumento(), 6, "0", STR_PAD_LEFT) . ')';
    $this->paginaProcesso->selecionarDocumento($nomeDocArvore);
    $this->paginaDocumento->navegarParaMoverDocumento();
    $this->paginaMoverDocumento->moverDocumentoParaProcesso($protocoloSecundarioTeste->getStrProtocoloFormatado(), "Motivo de teste");

    // Cadastramento de documento adicional
    $this->cadastrarDocumentoInternoFixture(self::$documentoTeste4, $processoOrg2->getDblIdProtocolo());
    $this->paginaBase->refresh();

    // Trâmitar Externamento processo para órgão/unidade destinatária
    $this->tramitarProcessoExternamente(
      self::$protocoloTeste,
      self::$destinatario['REP_ESTRUTURAS'],
      self::$destinatario['NOME_UNIDADE'],
      self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
      false
    );
  }

  /**
   * Teste de verificação do correto envio do processo no sistema remetente
   *
   * @group verificacao_envio
   *
   * @depends test_devolucao_processo_para_origem_com_novo_documento_movido_sem_anexo
   *
   * @return void
   */
  public function test_verificar_devolucao_origem_processo()
  {
    $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    $this->paginaBase->pesquisar(self::$protocoloTeste->getStrProtocoloFormatado());

    $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
      sleep(5);
      $testCase->refresh();
      $paginaProcesso = new PaginaProcesso($testCase);
      $testCase->assertStringNotContainsString(mb_convert_encoding("Processo em trâmite externo para ", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
      $testCase->assertFalse($paginaProcesso->processoAberto());
      $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
      return true;
    }, PEN_WAIT_TIMEOUT);

    $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
    $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTeste->getStrProtocoloFormatado(), $unidade);
    $this->validarRecibosTramite($mensagemRecibo, true, true);
    $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);
    $this->validarProcessosTramitados(self::$protocoloTeste->getStrProtocoloFormatado(), $orgaosDiferentes);
  }

  /**
   * Teste de verificação da correta devolução do processo no destinatário
   *
   * @group verificacao_recebimento
   *
   * @depends test_verificar_devolucao_origem_processo
   *
   * @return void
   */
  public function test_verificar_devolucao_destino_processo_com_dois_documentos_movidos_sem_anexo()
  {
    $strProtocoloTeste = self::$protocoloTeste->getStrProtocoloFormatado();
    $this->acessarSistema(
      self::$destinatario['URL'],
      self::$destinatario['SIGLA_UNIDADE'],
      self::$destinatario['LOGIN'],
      self::$destinatario['SENHA']
    );

   // Navegar para processo cadastrado
    $this->paginaBase->navegarParaControleProcesso();
    $this->paginaControleProcesso->abrirProcesso(self::$protocoloTeste->getStrProtocoloFormatado());

    $this->validarDadosProcesso(
      self::$processoTeste['DESCRICAO'],
      self::$processoTeste['RESTRICAO'],
      self::$processoTeste['OBSERVACOES'],
      array(self::$processoTeste['INTERESSADOS'])
    );

    $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);

    // Validação dos dados do processo principal
    $listaDocumentosProcesso = $this->paginaProcesso->listarDocumentos();
    $this->assertEquals(4, count($listaDocumentosProcesso));
    $this->validarDocumentoMovido($listaDocumentosProcesso[0]);
    $this->validarDadosDocumento($listaDocumentosProcesso[1], self::$documentoTeste2, self::$destinatario);
    $this->validarDocumentoCancelado($listaDocumentosProcesso[2]);
    $this->validarDadosDocumento($listaDocumentosProcesso[3], self::$documentoTeste4, self::$destinatario);
  }

  /**
   * @inheritDoc
   */
  public static function tearDownAfterClass(): void
  {
    putenv("DATABASE_HOST=org1-database");
    parent::tearDownAfterClass();
  }
}
