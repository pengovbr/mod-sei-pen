<?php

use PHPUnit\Framework\Attributes\{Group, Large, Depends};
use PHPUnit\Framework\AssertionFailedError;

/**
 * Teste de sincronização de processos com múltiplos órgãos no módulo PEN
 * 
 * Esta classe de teste valida o fluxo completo de tramitação e sincronização de processos
 * entre múltiplos órgãos através do Barramento PEN. O teste abrange:
 * 
 * - Criação e tramitação de processos com múltiplos órgãos habilitado
 * - Validação de recibos de envio e recebimento
 * - Adição de documentos (internos e externos) após tramitação
 * - Cancelamento e movimentação de documentos
 * - Solicitação de sincronização manual
 * - Validação da sincronização bidirecional entre órgãos
 * - Envio de correspondência eletrônica
 * 
 * Execution Groups
 * #[Group('execute_alone_group4')]
 * 
 * @package ModPEN\Tests\Funcional
 */
class TramiteSincronizacaoMultiplosOrgaoProcessoGrandeTest extends FixtureCenarioBaseTestCase
{

  /** @var array Dados do contexto do órgão remetente */
  public static $remetente;
  
  /** @var array Dados do contexto do órgão destinatário */
  public static $destinatario;
  
  /** @var array Dados do processo principal de teste */
  public static $processoTeste;
  
  /** @var array Dados do processo secundário de teste */
  public static $processoTesteSecundario;
  
  /** @var object Protocolo do processo principal */
  public static $protocoloTeste;
  
  /** @var object Protocolo do processo secundário */
  public static $protocoloTesteSecundario;
  
  /** @var array Primeiro documento interno de teste */
  public static $documentoTeste1;
  
  /** @var array Segundo documento interno de teste */
  public static $documentoTeste2;
  
  /** @var array Primeiro documento externo de teste */
  public static $documentoExternoTeste;
  
  /** @var array Segundo documento externo de teste */
  public static $documentoExternoTeste2;
  
  /** @var array Terceiro documento externo de teste */
  public static $documentoExternoTeste3;

  /** @var array IDs dos mapeamentos de envio parcial do órgão A */
  public static $arrIdMapEnvioParcialOrgaoA;
  
  /** @var array IDs dos mapeamentos de envio parcial do órgão B */
  public static $arrIdMapEnvioParcialOrgaoB;
  
  /** @var array Terceiro documento interno de teste */
  public static $documentoTeste3;
  
  /** @var array Quarto documento interno de teste */
  public static $documentoTeste4;
  
  /** @var array Quinto documento interno de teste */
  public static $documentoTeste5;

  /**
   * Configuração inicial do teste
   * 
   * Define os contextos de teste para órgão remetente e destinatário.
   * Este método é executado antes de cada método de teste.
   * 
   * #[Depends('CenarioBaseTestCase::setUpBeforeClass')]
   *
   * @return void
   */
  public function setUp(): void
  {
    parent::setUp();

    // Carregar contexto de testes e dados sobre certificado digital
    self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
    self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
  }

  /**
   * Teste 1: Criação de processo e tramitação para múltiplos órgãos
   * 
   * Cria o cenário de teste com mapeamentos de envio parcial, gera um processo
   * com um documento interno e realiza a tramitação externa com a opção de
   * múltiplos órgãos habilitada.
   * 
   * @return void
   */
  public function test_criar_processo_principal_tramitar_multiplos_orgaos()
  {
    $this->criarCenarioTramiteEnvioParcialTest();

    self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
    self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

    self::$protocoloTeste = $this->cadastrarProcessoFixture(self::$processoTeste);
    $this->cadastrarDocumentoInternoFixture(self::$documentoTeste1, self::$protocoloTeste->getDblIdProtocolo());

    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    $this->abrirProcesso(self::$protocoloTeste->getStrProtocoloFormatado());
    $this->tramitarProcessoExternamente(
      self::$protocoloTeste->getStrProtocoloFormatado(),
      self::$destinatario['REP_ESTRUTURAS'],
      self::$destinatario['NOME_UNIDADE'],
      self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
      false, null, PEN_WAIT_TIMEOUT,
      true // multíplos órgãos
    );

    $this->sairSistema();
  }

  /**
   * Teste 2: Verificação de recibos no órgão de origem
   * 
   * Valida que o processo tramitado no órgão remetente possui os recibos
   * de envio e conclusão corretamente registrados.
   * 
   * @return void
   */
  public function test_verificar_origem_processo_multiplos_orgaos_um_documento()
  {
    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    $this->abrirProcesso(self::$protocoloTeste->getStrProtocoloFormatado());

    $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
    $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTeste->getStrProtocoloFormatado(), $unidade);
    $this->validarRecibosTramite($mensagemRecibo, true, true);

    $this->sairSistema();
  }

  /**
   * Teste 3: Cadastro de documentos adicionais no processo de origem
   * 
   * Após a tramitação inicial, adiciona novos documentos ao processo:
   * - Documento externo
   * - Documento interno para ser movido posteriormente
   * - Documento interno para ser cancelado posteriormente
   * - Processo secundário anexado
   * 
   * Também realiza as operações de cancelamento e movimentação de documentos,
   * além de enviar correspondência eletrônica.
   * 
   * @return void
   */
  public function test_cadastrar_documentos_no_processo_origem_multiplos_orgaos()
  {
    // Gerar documento externo no processo principal
    self::$documentoExternoTeste = $this->gerarDadosDocumentoExternoTeste(self::$remetente);
    $this->cadastrarDocumentoExternoFixture(self::$documentoExternoTeste, self::$protocoloTeste->getDblIdProtocolo());

    // Cadastrar documento interno no processo principal para ser movido depois
    self::$documentoTeste2 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
    $this->cadastrarDocumentoInternoFixture(self::$documentoTeste2, self::$protocoloTeste->getDblIdProtocolo());

    // Cadastrar documento interno no processo principal para ser cancelado depois
    self::$documentoTeste3 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
    $this->cadastrarDocumentoInternoFixture(self::$documentoTeste3, self::$protocoloTeste->getDblIdProtocolo());

    // Cadastrar processo secundário no remetente
    self::$processoTesteSecundario = $this->gerarDadosProcessoTeste(self::$remetente);
    self::$protocoloTesteSecundario = $this->cadastrarProcessoFixture(self::$processoTesteSecundario);

    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    $this->abrirProcesso(self::$protocoloTeste->getStrProtocoloFormatado());

    // Realizar a anexação de processos
    $this->anexarProcessoFixture(self::$protocoloTeste->getDblIdProtocolo(), self::$protocoloTesteSecundario->getDblIdProtocolo());

    $this->abrirProcesso(self::$protocoloTeste->getStrProtocoloFormatado());

    // listar documentos do Processo Principal
    $listaDocumentosProcessoPrincipal = $this->paginaProcesso->listarDocumentos();
    $this->assertEquals(5, count($listaDocumentosProcessoPrincipal));

    //Tramitar internamento para liberação da funcionalidade de cancelar
    $this->tramitarProcessoInternamenteParaCancelamento(
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['SIGLA_UNIDADE_SECUNDARIA'],
      ['PROTOCOLO' => self::$protocoloTeste->getStrProtocoloFormatado()]
    );

    $this->navegarParaCancelarDocumento(3);
    $this->paginaCancelarDocumento->cancelar("Cancelamento do documento no teste automatizado.");
      
    // Mover documento externo (documentoTeste2) do Processo Principal para o Processo Secundário
    $this->paginaProcesso->selecionarDocumento($listaDocumentosProcessoPrincipal[1]);
    $this->paginaDocumento->navegarParaMoverDocumento();
    $this->paginaMoverDocumento->moverDocumentoParaProcesso(
      self::$protocoloTesteSecundario->getStrProtocoloFormatado(),
      "Documento movido para o processo secundário no teste automatizado."
    );

    // Validar que o documento foi movido
    $this->paginaProcesso->navegarSelecionarEnviarEmail();
    $this->paginaEnviarEmail->enviar();

    $this->sairSistema();
  }

  /**
   * Teste 4: Verificação do processo recebido no órgão de destino
   * 
   * Valida que o processo foi corretamente recebido no órgão destinatário
   * com apenas o documento inicial (antes da sincronização). Verifica:
   * - Dados do processo (descrição, restrição, observações, interessados)
   * - Recibo de recebimento
   * - Contagem de documentos (deve ser apenas 1)
   * 
   * @return void
   */
  public function test_verificar_destino_processo_multiplos_orgaos_um_documento()
  {
    $strProtocoloTeste = self::$protocoloTeste->getStrProtocoloFormatado();
    $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

    $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);
    $this->abrirProcesso(self::$protocoloTeste->getStrProtocoloFormatado());

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
    $this->assertEquals(1, count($listaDocumentosProcessoPrincipal));
    $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[0], self::$documentoTeste1, self::$destinatario);
    
    $this->sairSistema();
  }

  /**
   * Teste 5: Cadastro de documentos no processo de destino
   * 
   * Adiciona documentos externos ao processo no órgão destinatário.
   * Valida que após a adição, o processo possui 3 documentos
   * (1 original + 2 novos documentos externos).
   * 
   * @return void
   */
  public function test_cadastrar_documentos_no_processo_destino_multiplos_orgaos()
  {
    putenv("DATABASE_HOST=org2-database");

    $protocoloTestePrincipalOrg2 = $this->consultarProcessoFixture(self::$protocoloTeste->getStrProtocoloFormatado(), \ProtocoloRN::$TP_PROCEDIMENTO);

    // Gerar documento externo no processo principal destino
    self::$documentoExternoTeste2 = $this->gerarDadosDocumentoExternoTeste(self::$destinatario);
    $this->cadastrarDocumentoExternoFixture(self::$documentoExternoTeste2, $protocoloTestePrincipalOrg2->getDblIdProtocolo());

    // Gerar documento externo no processo principal destino
    self::$documentoExternoTeste3 = $this->gerarDadosDocumentoExternoTeste(self::$destinatario);
    $this->cadastrarDocumentoExternoFixture(self::$documentoExternoTeste3, $protocoloTestePrincipalOrg2->getDblIdProtocolo());
    $this->acessarSistema(
      self::$destinatario['URL'],
      self::$destinatario['SIGLA_UNIDADE'],
      self::$destinatario['LOGIN'],
      self::$destinatario['SENHA']
    );

    $this->abrirProcesso(self::$protocoloTeste->getStrProtocoloFormatado());

    // listar documentos do Processo Principal
    $listaDocumentosProcessoPrincipal = $this->paginaProcesso->listarDocumentos();
    $this->assertEquals(3, count($listaDocumentosProcessoPrincipal));

    $this->sairSistema();
  }

  /**
   * Teste 6: Solicitação de sincronização manual no órgão de destino
   * 
   * Realiza a solicitação de sincronização manual do processo no órgão
   * destinatário e valida:
   * - Existência do botão "Sincronizar Processo"
   * - Mensagem de sucesso após a solicitação
   * - Registro do pedido de sincronização nos andamentos do processo
   * 
   * @return void
   */
  public function test_solicitar_sincronizar_processo_destino_multiplos_orgaos()
  {
    $this->acessarSistema(
      self::$destinatario['URL'],
      self::$destinatario['SIGLA_UNIDADE'],
      self::$destinatario['LOGIN'],
      self::$destinatario['SENHA']
    );

    $this->abrirProcesso(self::$protocoloTeste->getStrProtocoloFormatado());

    // Verificar se o botão de sincronizar existe
    $btnSincronizar = $this->paginaProcesso->validarBotaoExiste("Sincronizar Processo");
    $this->assertNotNull($btnSincronizar, "Botão 'Sincronizar Processo' não encontrado na tela do processo.");

    // Clicar no botão de sincronizar
    $this->paginaProcesso->solicitarSincronizacao("Sincronizar Processo");

    // Capturar a mensagem do alert
    $mensagemAlerta = $this->paginaBase->alertTextAndClose(true);

    // Verificar se a mensagem esperada aparece
    $mensagemEsperada = mb_convert_encoding("Solicitação de sincronização realizada com sucesso", 'UTF-8', 'ISO-8859-1');
    $this->assertStringContainsString(
      $mensagemEsperada,
      $mensagemAlerta,
      mb_convert_encoding("A mensagem de alerta não corresponde à esperada", 'UTF-8', 'ISO-8859-1')
    );

    $this->paginaProcesso->navegarParaConsultarAndamentos();
    $this->assertTrue(
      $this->paginaProcesso->validarHistorioSincronizacao("Pedido de sincronização manual múltiplos órgãos"),
      'Pedido de sincronização manual múltiplos órgãos não encontrado nos andamentos do processo.'
    );

    if (DESATIVAR_AGENDAMENTO == 'true') {
      // Equivalente ao: make tramitar-pendencias-simples, após clicar no botão enviar (para órgão externo)
      $this->executarTramitarPendenciasSimples();
    }

    $this->sairSistema();
  }

  /**
   * Teste 7: Validação do recebimento do pedido de sincronização no órgão de origem
   * 
   * Verifica no órgão remetente que o pedido de sincronização enviado pelo
   * órgão destinatário foi recebido corretamente. Aguarda até que a mensagem
   * de recebimento apareça nos andamentos do processo.
   * 
   * Utiliza waitUntil para polling com timeout configurado.
   * 
   * @return void
   */
  public function test_validar_pedido_sincronizar_processo_origem_recebido_multiplos_orgaos()
  {
    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    $this->abrirProcesso(self::$protocoloTeste->getStrProtocoloFormatado());

    $this->waitUntil(function() {
        sleep(5);
        $this->paginaBase->refresh();
      try {
        $this->paginaProcesso->navegarParaConsultarAndamentos();
        $this->assertTrue(
          $this->paginaProcesso->validarHistorioSincronizacao("Pedido de sincronização múltiplos órgãos recebida"), 
          'Pedido de sincronização múltiplos órgãos recebida não encontrada nos andamentos do processo.'
        );
          return true;
      } catch (AssertionFailedError $e) {
          return false;
      }
    }, PEN_WAIT_TIMEOUT);

    $this->sairSistema();
  }

  /**
   * Teste 8: Validação da sincronização realizada no órgão de destino
   * 
   * Verifica no órgão destinatário que a sincronização foi concluída com sucesso.
   * Valida:
   * - Mensagem de envio automático nos andamentos
   * - Contagem final de documentos (deve ser 8 documentos após sincronização completa)
   * 
   * Este teste confirma que todos os documentos adicionados após o trâmite inicial
   * foram sincronizados corretamente entre os órgãos.
   * 
   * @return void
   */
  public function test_validar_sincronizar_processo_destino_realizado_multiplos_orgaos()
  {
    if (DESATIVAR_AGENDAMENTO == 'true') {
      // Equivalente ao: make tramitar-pendencias-simples, após clicar no botão enviar (para órgão externo)
      $this->executarTramitarPendenciasSimples();
    }
    
    $this->acessarSistema(
      self::$destinatario['URL'],
      self::$destinatario['SIGLA_UNIDADE'],
      self::$destinatario['LOGIN'],
      self::$destinatario['SENHA']
    );

    $this->abrirProcesso(self::$protocoloTeste->getStrProtocoloFormatado());

    $this->paginaProcesso->navegarParaConsultarAndamentos();
    $this->assertTrue(
      $this->paginaProcesso->validarHistorioSincronizacao("Pedido de sincronização manual múltiplos órgãos"), 
      'Pedido de sincronização manual múltiplos órgãos não encontrada nos andamentos do processo.'
    );

    // listar documentos do Processo Principal
    $listaDocumentosProcessoPrincipal = $this->paginaProcesso->listarDocumentos();
    $this->assertEquals(8, count($listaDocumentosProcessoPrincipal));

    $this->sairSistema();
  }

  /**
   * Limpeza após todos os testes da classe
   * 
   * Remove os mapeamentos de envio parcial criados durante os testes
   * nos órgãos remetente (A) e destinatário (B).
   * 
   * #[Group('mapeamento')]
   * 
   * @return void
   */
  public static function tearDownAfterClass(): void
  {
    $penMapEnvioParcialFixture = new \PenMapEnvioParcialFixture();

    putenv("DATABASE_HOST=org2-database");
    foreach (self::$arrIdMapEnvioParcialOrgaoB as $idMapEnvioParcial) {
      $penMapEnvioParcialFixture->remover([
        'Id' => $idMapEnvioParcial
      ]);
    }
    
    putenv("DATABASE_HOST=org1-database");
    foreach (self::$arrIdMapEnvioParcialOrgaoA as $idMapEnvioParcial) {
      $penMapEnvioParcialFixture->remover([
        'Id' => $idMapEnvioParcial
      ]);
    }

    parent::tearDownAfterClass();
  }

  /**
   * Cria o cenário de teste com mapeamentos de envio parcial
   * 
   * Configura os mapeamentos necessários para envio parcial entre os órgãos:
   * - Mapeia o órgão destinatário no remetente com flag de múltiplos órgãos
   * - Mapeia o órgão remetente no destinatário para sincronização bidirecional
   * 
   * Os IDs dos mapeamentos criados são armazenados para posterior remoção
   * no tearDown.
   * 
   * #[Group('mapeamento')]
   * 
   * @return void
   */
  private function criarCenarioTramiteEnvioParcialTest()
  {
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

    // Mapear Envio Parcial no Remetente
    self::$arrIdMapEnvioParcialOrgaoA = array();
    putenv("DATABASE_HOST=org1-database");
    $objPenMapEnvioParcialFixture = new PenMapEnvioParcialFixture();
    $objMapEnvioParcial = $objPenMapEnvioParcialFixture->carregar([
      'IdEstrutura' => self::$destinatario['ID_REP_ESTRUTURAS'],
      'StrEstrutura' => self::$destinatario['REP_ESTRUTURAS'],
      'IdUnidadePen' => self::$destinatario['ID_ESTRUTURA'],
      'StrUnidadePen' => self::$destinatario['NOME_UNIDADE'],
      'SinMultiplosOrgaos' => 'S'
    ]);
    self::$arrIdMapEnvioParcialOrgaoA[] = $objMapEnvioParcial->getDblId();
  }
}
