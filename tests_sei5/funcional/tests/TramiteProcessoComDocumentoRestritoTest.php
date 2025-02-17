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

    // Configuração do dados para teste do cenário
    self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
    self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
  }

  /**
   * Teste de trâmite externo de processo com documentos restritos
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
    self::$documentoTeste["RESTRICAO"] = \ProtocoloRN::$NA_RESTRITO; // Configuração de documento restrito
    self::$documentoTeste["HIPOTESE_LEGAL"] = self::$remetente["HIPOTESE_RESTRICAO"]; // Configurar Hipotese legal
    $this->cadastrarDocumentoInternoFixture(self::$documentoTeste, self::$protocoloTeste->getDblIdProtocolo()); // Incluir Documentos no Processo

    $this->paginaBase->navegarParaControleProcesso();
    $this->paginaControleProcesso->abrirProcesso(self::$protocoloTeste->getStrProtocoloFormatado());
    
    // Trâmitar Externamento processo para órgão/unidade destinatária
    $this->tramitarProcessoExternamente(
      self::$protocoloTeste,
      self::$destinatario['REP_ESTRUTURAS'],
      self::$destinatario['NOME_UNIDADE'],
      self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
      false
    );

    // A partir da versão SEI 5.0 ao criar um documento restrito o processo torna-se restrito também
    self::$processoTeste["RESTRICAO"] = \ProtocoloRN::$NA_RESTRITO; // Configuração de documento restrito

  }

  /**
   * Teste de verificação do correto envio do processo no sistema remetente
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

    // 6 - Verificar se situação atual do processo está como bloqueado
    $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
      sleep(5);
      $testCase->refresh();
      $paginaProcesso = new PaginaProcesso($testCase);
      $testCase->assertStringNotContainsString(mb_convert_encoding("Processo em trâmite externo para ", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
      $testCase->assertFalse($paginaProcesso->processoAberto());
      $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
      return true;
    }, PEN_WAIT_TIMEOUT);

    // 7 - Validar se recibo de trâmite foi armazenado para o processo (envio e conclusão)
    $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
    $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTeste->getStrProtocoloFormatado(), $unidade);
    $this->validarRecibosTramite($mensagemRecibo, true, true);

    // 8 - Validar histórico de trâmite do processo
    $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);

    // 9 - Verificar se processo está na lista de Processos Tramitados Externamente
    $this->validarProcessosTramitados(self::$protocoloTeste->getStrProtocoloFormatado(), $orgaosDiferentes);
  }

  /**
   * Teste de verificação do correto recebimento do processo contendo apenas um documento interno (gerado)
   * 
   * A partir da versão SEI 5.0 ao criar um documento restrito o processo torna-se restrito também
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
    $strTipoProcesso = mb_convert_encoding("Tipo de processo no órgão de origem: ", 'UTF-8', 'ISO-8859-1');
    $strTipoProcesso .= self::$processoTeste['TIPO_PROCESSO'];
    self::$processoTeste['OBSERVACOES'] = $orgaosDiferentes ? $strTipoProcesso : null;
    $this->validarDadosProcesso(
      self::$processoTeste['DESCRICAO'],
      self::$processoTeste['RESTRICAO'],
      self::$processoTeste['OBSERVACOES'],
      array(self::$processoTeste['INTERESSADOS'])
    );

    // 13 - Verificar recibos de trâmite
    $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);

    // 14 - Validar dados do documento
    $this->assertTrue(count($listaDocumentos) == 1);
    $this->validarDadosDocumento($listaDocumentos[0], self::$documentoTeste, self::$destinatario);
  }

  public static function tearDownAfterClass(): void
  {
    parent::tearDownAfterClass();
  }
}
