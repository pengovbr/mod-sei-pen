<?php

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\AssertionFailedError;
use Tests\Funcional\Sei\Fixtures\OrgaoFixture;

/**
 * Execution Groups
 */
#[Group('execute_alone_group4')]
class TramiteSincronizacaoMultiplosOrgaoProcessoAlteradoRestritoTest extends FixtureCenarioBaseTestCase
{
  public static $remetente;
  public static $destinatario;
  public static $processoTeste;
  public static $documentoTeste;
  public static $protocoloTeste;
  public static $arrIdMapEnvioParcialOrgaoA = [];
  public static $arrIdMapEnvioParcialOrgaoB = [];

  public static function setUpBeforeClass(): void
  {
    parent::setUpBeforeClass();
    self::$arrIdMapEnvioParcialOrgaoA = [];
    self::$arrIdMapEnvioParcialOrgaoB = [];
  }

  public function setUp(): void
  {
    parent::setUp();
    self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
    self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
  }

  public function test_criar_processo_tramitar_multiplos_orgaos(): void
  {
    $this->criarCenarioTramiteEnvioParcialTest();

    self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
    self::$documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

    self::$protocoloTeste = $this->cadastrarProcessoFixture(self::$processoTeste);
    $this->cadastrarDocumentoInternoFixture(self::$documentoTeste, self::$protocoloTeste->getDblIdProtocolo());

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
      false,
      null,
      PEN_WAIT_TIMEOUT,
      true
    );

    $this->shellExecutarTramites();
    $this->sairSistema();
  }

  #[Depends('test_criar_processo_tramitar_multiplos_orgaos')]
  public function test_verificar_destino_inicial_publico(): void
  {
    $strProtocoloTeste = self::$protocoloTeste->getStrProtocoloFormatado();

    $this->acessarSistema(
      self::$destinatario['URL'],
      self::$destinatario['SIGLA_UNIDADE'],
      self::$destinatario['LOGIN'],
      self::$destinatario['SENHA']
    );

    $this->paginaBase->navegarParaControleProcesso();
    $this->waitUntil(function () use ($strProtocoloTeste) {
      sleep(5);

      try {
        $this->paginaBase->refresh();
        $this->paginaControleProcesso->abrirProcesso($strProtocoloTeste);
        return true;
      } catch (\Exception $e) {
        return false;
      }
    }, PEN_WAIT_TIMEOUT);

    $this->validarDadosProcesso(
      self::$processoTeste['DESCRICAO'],
      self::$processoTeste['RESTRICAO'],
      null,
      [self::$processoTeste['INTERESSADOS']]
    );

    $listaDocumentos = $this->paginaProcesso->listarDocumentos();
    $this->assertNotEmpty($listaDocumentos);
    $this->assertDocumentoRestricao($listaDocumentos[0], PaginaDocumento::STA_NIVEL_ACESSO_PUBLICO);

    $this->sairSistema();
  }

  #[Depends('test_verificar_destino_inicial_publico')]
  public function test_alterar_origem_e_solicitar_sincronizacao(): void
  {
    $this->criarCenarioTramiteEnvioParcialTest();

    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    $this->abrirProcesso(self::$protocoloTeste->getStrProtocoloFormatado());
    $this->alterarProcessoParaRestrito();
    $this->sairSistema();

    $this->acessarSistema(
      self::$destinatario['URL'],
      self::$destinatario['SIGLA_UNIDADE'],
      self::$destinatario['LOGIN'],
      self::$destinatario['SENHA']
    );

    $this->paginaBase->navegarParaControleProcesso();
    $this->paginaControleProcesso->abrirProcesso(self::$protocoloTeste->getStrProtocoloFormatado());

    $btnSincronizar = $this->paginaProcesso->validarBotaoExiste("Sincronizar Processo");
    $this->assertTrue($btnSincronizar, "Botão 'Sincronizar Processo' não foi encontrado");

    $this->paginaProcesso->solicitarSincronizacao("Sincronizar Processo");
    $mensagemAlerta = $this->paginaBase->alertTextAndClose(true);
    $mensagemEsperada = mb_convert_encoding("Solicitação de sincronização realizada com sucesso", 'UTF-8', 'ISO-8859-1');
    $this->assertStringContainsString($mensagemEsperada, $mensagemAlerta);

    $this->shellExecutarTramites();
    $this->sairSistema();
  }

  #[Depends('test_alterar_origem_e_solicitar_sincronizacao')]
  public function test_validar_destino_sincronizado_restrito(): void
  {
    $this->acessarSistema(
      self::$destinatario['URL'],
      self::$destinatario['SIGLA_UNIDADE'],
      self::$destinatario['LOGIN'],
      self::$destinatario['SENHA']
    );

    $this->paginaBase->navegarParaControleProcesso();
    $this->paginaControleProcesso->abrirProcesso(self::$protocoloTeste->getStrProtocoloFormatado());
    $this->validarDadosProcesso(
      self::$processoTeste['DESCRICAO'],
      self::$processoTeste['RESTRICAO'],
      null,
      [self::$processoTeste['INTERESSADOS']],
      self::$destinatario['HIPOTESE_RESTRICAO']
    );

    $this->sairSistema();
  }

  public static function tearDownAfterClass(): void
  {
    $penMapEnvioParcialFixture = new PenMapEnvioParcialFixture();

    putenv("DATABASE_HOST=org2-database");
    foreach (self::$arrIdMapEnvioParcialOrgaoB as $idMapEnvioParcial) {
      $penMapEnvioParcialFixture->remover(['Id' => $idMapEnvioParcial]);
    }

    putenv("DATABASE_HOST=org1-database");
    foreach (self::$arrIdMapEnvioParcialOrgaoA as $idMapEnvioParcial) {
      $penMapEnvioParcialFixture->remover(['Id' => $idMapEnvioParcial]);
    }

    parent::tearDownAfterClass();
  }

  protected function alterarProcessoParaRestrito(): void
  {
    self::$processoTeste['RESTRICAO'] = self::STA_NIVEL_ACESSO_RESTRITO;
    self::$processoTeste['HIPOTESE_LEGAL'] = self::$remetente['HIPOTESE_RESTRICAO'];

    $this->paginaProcesso->navegarParaEditarProcesso();
    $this->paginaEditarProcesso->selecionarRestricao(
      PaginaEditarProcesso::STA_NIVEL_ACESSO_RESTRITO,
      self::$remetente['HIPOTESE_RESTRICAO']
    );
    $this->paginaEditarProcesso->salvarProcesso();
    sleep(2);
  }

  protected function assertDocumentoRestricao(string $nomeDocumentoArvore, int $nivel, ?string $hipoteseLegal = null): void
  {
    $this->paginaProcesso->selecionarDocumento($nomeDocumentoArvore);
    $this->paginaDocumento->navegarParaConsultarDocumento();
    $this->paginaBase->frame(null);
    $this->paginaBase->frame('ifrConteudoVisualizacao');
    $this->paginaBase->frame('ifrVisualizacao');

    $this->assertEquals($nivel, $this->paginaDocumento->restricao());

    if ($hipoteseLegal !== null) {
      $this->assertEquals($hipoteseLegal, $this->paginaDocumento->recuperarHipoteseLegal());
    }
  }

  private function criarCenarioTramiteEnvioParcialTest(): void
  {
    self::$arrIdMapEnvioParcialOrgaoB = [];
    putenv("DATABASE_HOST=org2-database");
    $objPenMapEnvioParcialFixture = new PenMapEnvioParcialFixture();
    $objMapEnvioParcial = $objPenMapEnvioParcialFixture->carregar([
      'IdEstrutura' => self::$remetente['ID_REP_ESTRUTURAS'],
      'StrEstrutura' => self::$remetente['REP_ESTRUTURAS'],
      'IdUnidadePen' => self::$remetente['ID_ESTRUTURA'],
      'StrUnidadePen' => self::$remetente['NOME_UNIDADE']
    ]);
    self::$arrIdMapEnvioParcialOrgaoB[] = $objMapEnvioParcial->getDblId();

    self::$arrIdMapEnvioParcialOrgaoA = [];
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
