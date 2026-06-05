<?php

use PHPUnit\Framework\Attributes\{Group, Large, Depends};
use PHPUnit\Framework\AssertionFailedError;

/**
 * Teste de validação de restrição de tramitação de documentos sigilosos com múltiplos órgãos
 * 
 * Esta classe de teste valida que o sistema corretamente impede a tramitação de processos
 * que contenham documentos sigilosos através do Barramento PEN quando utilizando a 
 * funcionalidade de múltiplos órgãos.
 * 
 * Cenário testado:
 * - Criação e tramitação inicial bem-sucedida de processo com múltiplos órgãos
 * - Adição de documento sigiloso ao processo já tramitado
 * - Tentativa de nova tramitação e validação da mensagem de erro esperada
 * 
 * Regra de negócio validada:
 * Apenas documentos de nível de acesso público ou restrito podem ser tramitados
 * pelo Barramento PEN. Documentos sigilosos devem resultar em erro de tramitação.
 * 
 * @package ModPEN\Tests\Funcional
 */
class TramiteSincronizacaoMultiplosOrgaoDocumentoSigilosoTest extends FixtureCenarioBaseTestCase
{

  /** @var array Dados do contexto do órgão remetente */
  public static $remetente;
  
  /** @var array Dados do contexto do órgão destinatário */
  public static $destinatario;
  
  /** @var array Dados do processo principal de teste */
  public static $processoTeste;
  
  /** @var object Protocolo do processo principal */
  public static $protocoloTeste;
  
  /** @var array Primeiro documento interno de teste */
  public static $documentoTeste1;
  
  /** @var array Segundo documento interno de teste (sigiloso) */
  public static $documentoTeste2;

  /** @var array IDs dos mapeamentos de envio parcial do órgão A */
  public static $arrIdMapEnvioParcialOrgaoA;
  
  /** @var array IDs dos mapeamentos de envio parcial do órgão B */
  public static $arrIdMapEnvioParcialOrgaoB;

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
      false, 
      null, 
      PEN_WAIT_TIMEOUT,
      true, // executarTramitarPendencias
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
   * Teste 3: Validação de erro ao tentar tramitar processo com documento sigiloso
   * 
   * Adiciona um documento com nível de acesso SIGILOSO ao processo e tenta realizar
   * nova tramitação externa com múltiplos órgãos. O teste valida que o sistema:
   * 
   * 1. Permite o cadastro do documento sigiloso no processo
   * 2. Impede a tramitação do processo pelo Barramento PEN
   * 3. Exibe a mensagem de erro esperada: 
   *    "Falha no envio externo do processo. Erro: 0004 - Apenas entidades de 
   *     nível de sigílo público ou restrito podem ser tramitados pelo barramento."
   * 
   * Este teste garante a conformidade com as regras de segurança do Barramento PEN,
   * que não permite tramitação de informações sigilosas através da rede.
   * 
   * @return void
   */
  public function test_cadastrar_documento_sigiloso_no_processo_origem_com_envio_restricao()
  {
    // Cadastrar documento interno no processo principal para ser movido depois
    self::$documentoTeste2 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

    self::$documentoTeste2["RESTRICAO"] = \ProtocoloRN::$NA_SIGILOSO; // Configuração de documento sigiloso
    self::$documentoTeste2["HIPOTESE_LEGAL"] = self::$remetente["HIPOTESE_RESTRICAO_NAO_MAPEADO"];

    $this->cadastrarDocumentoInternoFixture(self::$documentoTeste2, self::$protocoloTeste->getDblIdProtocolo());

    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    $this->abrirProcesso(self::$protocoloTeste->getStrProtocoloFormatado());

    $this->tramitarProcessoExternamenteErroEsperado(
      self::$protocoloTeste->getStrProtocoloFormatado(),
      self::$destinatario['REP_ESTRUTURAS'],
      self::$destinatario['NOME_UNIDADE'],
      self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
      'Falha no envio externo do processo. Erro: 0004 - Apenas entidades de nível de sigílo público ou restrito podem ser tramitados pelo barramento.',
      urgente: false,       
      multiplosOrgaos: true,
      timeout: PEN_WAIT_TIMEOUT,
      executarTramitarPendencias:true
    );

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
