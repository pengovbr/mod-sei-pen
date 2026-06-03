<?php


use function PHPSTORM_META\map;

use PHPUnit\Framework\TestCase;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;
use Facebook\WebDriver\Chrome\ChromeOptions;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Classe base contendo rotinas comuns utilizadas nos casos de teste do módulo
 */
class CenarioBaseTestCase extends TestCase
{

    const PASTA_ARQUIVOS_TESTE = "/tmp";
    const STA_NIVEL_ACESSO_PUBLICO  = 0;
    const STA_NIVEL_ACESSO_RESTRITO = 1;
    const STA_NIVEL_ACESSO_SIGILOSO = 2;

  protected static $driver;

    //Referência para unidades que serão consideradas no fluxo de trâmite (Remetente -> Destinatário)
  protected static $urlSistemaRemetente = null;
  protected static $siglaOrgaoRemetente = null;
  protected static $siglaUnidadeRemetente = null;
  protected static $nomeUnidadeRemetente = null;

  protected static $urlSistemaDestinatario = null;
  protected static $siglaOrgaoDestinatario = null;
  protected static $siglaUnidadeDestinatario = null;
  protected static $nomeUnidadeDestinatario = null;

    //Referências para as páginas do SEI utilizadas nos cenarios de teste
    protected $paginaBase = null;
    protected $paginaLogin = null;
    protected $paginaEditarProcesso = null;
    protected $paginaControleProcesso = null;
    protected $paginaDocumento = null;
    protected $paginaProcesso = null;
    protected $paginaTramitar = null;
    protected $paginaReciboTramite = null;
    protected $paginaConsultarAndamentos = null;
    protected $paginaProcessosTramitadosExternamente = null;
    protected $paginaCancelarDocumento = null;
    protected $paginaMoverDocumento = null;
    protected $paginaCadastroMapEnvioCompDigitais = null;
    protected $paginaTramiteMapeamentoOrgaoExterno = null;
    protected $paginaCadastroOrgaoExterno = null;
    protected $paginaExportarTiposProcesso = null;
    protected $paginaTipoProcessoReativar = null;
    protected $paginaCadastrarProcessoEmBloco = null;
    protected $paginaTramiteEmBloco = null;
    protected $paginaEnvioParcialListar = null;
    protected $paginaPenHipoteseLegalListar = null;
    protected $paginaMapUnidades = null;
    protected $paginaAgendamentos = null;
    protected $paginaEnviarEmail = null;

  public function setUpPage(): void
    {
      $this->paginaBase = new PaginaTeste(self::$driver, $this);
      $this->paginaLogin = new PaginaLogin(self::$driver, $this);
      $this->paginaEditarProcesso = new PaginaEditarProcesso(self::$driver, $this);
      $this->paginaControleProcesso = new PaginaControleProcesso(self::$driver, $this);
      $this->paginaDocumento = new PaginaDocumento(self::$driver, $this);
      $this->paginaProcesso = new PaginaProcesso(self::$driver, $this);
      $this->paginaTramitar = new PaginaTramitarProcesso(self::$driver, $this);
      $this->paginaReciboTramite = new PaginaReciboTramite(self::$driver, $this);
      $this->paginaConsultarAndamentos = new PaginaConsultarAndamentos(self::$driver, $this);
      $this->paginaProcessosTramitadosExternamente = new PaginaProcessosTramitadosExternamente(self::$driver, $this);
      $this->paginaCancelarDocumento = new PaginaCancelarDocumento(self::$driver, $this);
      $this->paginaMoverDocumento = new PaginaMoverDocumento(self::$driver, $this);
      $this->paginaCadastroMapEnvioCompDigitais = new PaginaCadastroMapEnvioCompDigitais(self::$driver, $this);
      $this->paginaTramiteMapeamentoOrgaoExterno = new PaginaTramiteMapeamentoOrgaoExterno(self::$driver, $this);
      $this->paginaCadastroOrgaoExterno = new PaginaCadastroOrgaoExterno(self::$driver, $this);
      $this->paginaExportarTiposProcesso = new PaginaExportarTiposProcesso(self::$driver, $this);
      $this->paginaTipoProcessoReativar = new PaginaTipoProcessoReativar(self::$driver, $this);
      $this->paginaCadastrarProcessoEmBloco = new PaginaCadastrarProcessoEmBloco(self::$driver, $this);
      $this->paginaTramiteEmBloco = new PaginaTramiteEmBloco(self::$driver, $this);
      $this->paginaEnvioParcialListar = new PaginaEnvioParcialListar(self::$driver, $this);
      $this->paginaPenHipoteseLegalListar = new PaginaPenHipoteseLegalListar(self::$driver, $this);
      $this->paginaMapUnidades = new PaginaMapUnidades(self::$driver, $this);
      $this->paginaAgendamentos = new PaginaAgendamentos(self::$driver, $this);
      $this->paginaEnviarEmail = new PaginaEnviarEmail(self::$driver, $this);

  }

  public static function setUpBeforeClass(): void
    {

      $seleniumUrl = 'http://'. PHPUNIT_HOST .':'. PHPUNIT_PORT . '/wd/hub';
    switch (PHPUNIT_BROWSER) {
      case 'firefox':
        $browser = DesiredCapabilities::microsoftEdge();
          break;
      case 'internetExplorer':
          $browser = DesiredCapabilities::internetExplorer();
          break;
      case 'microsoftEdge':
          $browser = DesiredCapabilities::firefox();
          break;
      case 'safari':
          $browser = DesiredCapabilities::safari();
          break;
      case 'opera':
          $browser = DesiredCapabilities::opera();
          break;
      case 'chrome':
          $options = new ChromeOptions();
          $options->addArguments([
              'disable-features=AvoidFlashBetweenNavigation,PaintHolding',
          ]);
          $options->setExperimentalOption('w3c', false);

          // 2) Gera as capabilities e instância o driver
          $browser = $options->toCapabilities();
          // $browser = DesiredCapabilities::chrome();
          break;
      default:
          $browser = DesiredCapabilities::htmlUnitWithJS();
          break;
    }
      self::$driver = RemoteWebDriver::create(
          $seleniumUrl,
          $browser
      );

      self::$driver->manage()->window()->maximize();
      //TODO: Migrar todo o código abaixo para uma classe utilitária de configuração dos testes
      /***************** CONFIGURAÇÃO PRELIMINAR DO ÓRGÃO 1 *****************/
      $parametrosOrgaoA = new ParameterUtils(CONTEXTO_ORGAO_A);
      $parametrosOrgaoA->setParameter('PEN_ID_REPOSITORIO_ORIGEM', CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS);
      $parametrosOrgaoA->setParameter('PEN_TIPO_PROCESSO_EXTERNO', '100000256');
      $parametrosOrgaoA->setParameter('HIPOTESE_LEGAL_PADRAO', '1'); // Controle Interno
      $parametrosOrgaoA->setParameter('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO', '110000003');

      $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);
      $bancoOrgaoA->execute("update unidade set sin_envio_processo=? where sigla=?", array('S', 'TESTE_1_2'));

      // Configuração do mapeamento de unidades
      putenv("DATABASE_HOST=org1-database");
      $penMapUnidadesFixture = new \PenMapUnidadesFixture();
      $penMapUnidadesFixture->carregar([
          'IdUnidade' => 110000001,
          'Id' => CONTEXTO_ORGAO_A_ID_ESTRUTURA,
          'Sigla' => CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA,
          'Nome' => CONTEXTO_ORGAO_A_NOME_UNIDADE,
      ]);
    if (is_numeric(CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA)) {
        $penMapUnidadesFixture->carregar([
            'IdUnidade' => 110000002,
            'Id' => CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA,
            'Sigla' => CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA,
            'Nome' => CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA,
        ]);
    }
      // Configuração do prefíxo de processos
      $bancoOrgaoA->execute("update orgao set codigo_sei=? where sigla=?", array(CONTEXTO_ORGAO_A_NUMERO_SEI, CONTEXTO_ORGAO_A_SIGLA_ORGAO));
      $bancoOrgaoA->execute("update unidade set sin_protocolo=? where sigla=?", array('S', CONTEXTO_ORGAO_A_SIGLA_UNIDADE));
      $bancoOrgaoA->execute("update infra_agendamento_tarefa set parametro='debug=true' where comando='PENAgendamentoRN::processarTarefasEnvioPEN'", null);
      $bancoOrgaoA->execute("update infra_agendamento_tarefa set parametro='debug=true' where comando='PENAgendamentoRN::processarTarefasRecebimentoPEN'", null);

      // Remoção de mapeamento de espécie não mapeada na origem
      $nomeSerieNaoMapeada = mb_convert_encoding(CONTEXTO_ORGAO_A_TIPO_DOCUMENTO_NAO_MAPEADO, 'UTF-8', 'ISO-8859-1');
      $serieNaoMapeadaOrigem = $bancoOrgaoA->query('select ID_SERIE from serie where nome = ?', array($nomeSerieNaoMapeada));
      $serieNaoMapeadaOrigem[0] = array_change_key_case($serieNaoMapeadaOrigem[0], CASE_UPPER);
        
      $bancoOrgaoA->execute("delete from md_pen_rel_doc_map_enviado where id_serie = ?", array($serieNaoMapeadaOrigem[0]["ID_SERIE"]));
      $bancoOrgaoA->execute("update infra_parametro set valor = ? where nome = ?", array(50, 'SEI_TAM_MB_DOC_EXTERNO'));

      // Habilitação da extensão docx
      $bancoOrgaoA->execute("update arquivo_extensao set sin_ativo=? where extensao=?", array('S', 'docx'));

      /***************** CONFIGURAÇÃO PRELIMINAR DO ÓRGÃO 2 *****************/
      $parametrosOrgaoB = new ParameterUtils(CONTEXTO_ORGAO_B);
      $parametrosOrgaoB->setParameter('PEN_ID_REPOSITORIO_ORIGEM', CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS);
      $parametrosOrgaoB->setParameter('PEN_TIPO_PROCESSO_EXTERNO', '100000256');
      $parametrosOrgaoB->setParameter('HIPOTESE_LEGAL_PADRAO', '1'); // Controle Interno
      $parametrosOrgaoB->setParameter('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO', '110000003');

      $bancoOrgaoB = new DatabaseUtils(CONTEXTO_ORGAO_B);
      $bancoOrgaoB->execute("update unidade set sin_envio_processo=? where sigla=?", array('S', 'TESTE_1_2'));

      putenv("DATABASE_HOST=org2-database");
      $penMapUnidadesFixture = new \PenMapUnidadesFixture();
      $penMapUnidadesFixture->carregar([
          'IdUnidade' => 110000001,
          'Id' => CONTEXTO_ORGAO_B_ID_ESTRUTURA,
          'Sigla' => CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA,
          'Nome' => CONTEXTO_ORGAO_B_NOME_UNIDADE,
      ]);        
      putenv("DATABASE_HOST=org1-database");

    if (is_numeric(CONTEXTO_ORGAO_B_ID_ESTRUTURA_SECUNDARIA)) {
        $penMapUnidadesFixture->carregar([
            'IdUnidade' => 110000002,
            'Id' => CONTEXTO_ORGAO_B_ID_ESTRUTURA_SECUNDARIA,
            'Sigla' => CONTEXTO_ORGAO_B_NOME_UNIDADE_SECUNDARIA,
            'Nome' => CONTEXTO_ORGAO_B_NOME_UNIDADE_SECUNDARIA,
        ]);
    }

      $bancoOrgaoB->execute("update orgao set codigo_sei=? where sigla=?", array(CONTEXTO_ORGAO_B_NUMERO_SEI, CONTEXTO_ORGAO_B_SIGLA_ORGAO));
      $bancoOrgaoB->execute("update unidade set sin_protocolo=? where sigla=?", array('S', CONTEXTO_ORGAO_B_SIGLA_UNIDADE));
      $bancoOrgaoB->execute("update infra_agendamento_tarefa set parametro='debug=true' where comando='PENAgendamentoRN::processarTarefasEnvioPEN'", null);
      $bancoOrgaoB->execute("update infra_agendamento_tarefa set parametro='debug=true' where comando='PENAgendamentoRN::processarTarefasRecebimentoPEN'", null);
      $bancoOrgaoB->execute("update infra_parametro set valor = ? where nome = ?", array(50, 'SEI_TAM_MB_DOC_EXTERNO'));

      // Remoção de mapeamento de espécie não mapeada na origem
      $nomeSerieNaoMapeada = mb_convert_encoding(CONTEXTO_ORGAO_B_TIPO_DOCUMENTO_NAO_MAPEADO, 'UTF-8', 'ISO-8859-1');
      $serieNaoMapeadaOrigem = $bancoOrgaoB->query('select ID_SERIE from serie where nome = ?', array($nomeSerieNaoMapeada));
      $serieNaoMapeadaOrigem[0] = array_change_key_case($serieNaoMapeadaOrigem[0], CASE_UPPER);
        
      $bancoOrgaoB->execute("delete from md_pen_rel_doc_map_recebido where id_serie = ?", array($serieNaoMapeadaOrigem[0]["ID_SERIE"]));
      $bancoOrgaoB->execute("update infra_parametro set valor = ? where nome = ?", array(50, 'SEI_TAM_MB_DOC_EXTERNO'));

      //para corrigir o erro do oracle que retorna stream sem acentuação das palavras no teste de URL
    if ($bancoOrgaoA->getBdType() == "oci") {
        $result = $bancoOrgaoA->query("SELECT texto FROM tarja_assinatura where sta_tarja_assinatura=? and sin_ativo=?", array("V", "S"));
        $strTarja = stream_get_contents($result[0]["TEXTO"]);
        $bancoOrgaoA->execute("update tarja_assinatura set texto=? where sta_tarja_assinatura=? and sin_ativo=?", array($strTarja, "V", "S"));
    }

    if (DESATIVAR_AGENDAMENTO == 'true') {
        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);    
        $bancoOrgaoA->execute("update infra_agendamento_tarefa set sin_ativo = ? where comando = ?", array('N', 'PENAgendamentoRN::processarTarefasEnvioPEN'));
        $bancoOrgaoA->execute("update infra_agendamento_tarefa set sin_ativo = ? where comando = ?", array('N', 'PENAgendamentoRN::processarTarefasRecebimentoPEN'));

        $bancoOrgaoB = new DatabaseUtils(CONTEXTO_ORGAO_B);
        $bancoOrgaoB->execute("update infra_agendamento_tarefa set sin_ativo = ? where comando = ?", array('N', 'PENAgendamentoRN::processarTarefasEnvioPEN'));
        $bancoOrgaoB->execute("update infra_agendamento_tarefa set sin_ativo = ? where comando = ?", array('N', 'PENAgendamentoRN::processarTarefasRecebimentoPEN'));
      }
  }

  public static function tearDownAfterClass(): void
    {
    if (self::$driver) {
        self::$driver->quit();
    }

    if (DESATIVAR_AGENDAMENTO == 'true') {
        
            parent::tearDownAfterClass();
            $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);    
            $bancoOrgaoA->execute("update infra_agendamento_tarefa set sin_ativo = ? where comando = ?", array('S', 'PENAgendamentoRN::processarTarefasEnvioPEN'));
            $bancoOrgaoA->execute("update infra_agendamento_tarefa set sin_ativo = ? where comando = ?", array('S', 'PENAgendamentoRN::processarTarefasRecebimentoPEN'));

            $bancoOrgaoB = new DatabaseUtils(CONTEXTO_ORGAO_B);
            $bancoOrgaoB->execute("update infra_agendamento_tarefa set sin_ativo = ? where comando = ?", array('S', 'PENAgendamentoRN::processarTarefasEnvioPEN'));
            $bancoOrgaoB->execute("update infra_agendamento_tarefa set sin_ativo = ? where comando = ?", array('S', 'PENAgendamentoRN::processarTarefasRecebimentoPEN'));

        }

  }

  public function setUp(): void
    {
      self::$driver->manage()->deleteAllCookies();
      $this->setUpPage();
  }

    /**
     * Vai para a URL informada
     */
  protected function url(string $url): void
    {
      self::$driver->get($url);
  }

  protected function definirContextoTeste($nomeContexto)
    {
      $objContexto = array(
          'URL' => constant($nomeContexto . '_URL'),
          'ORGAO' => constant($nomeContexto . '_SIGLA_ORGAO'),
          'SIGLA_UNIDADE' => constant($nomeContexto . '_SIGLA_UNIDADE'),
          'SIGLA_UNIDADE_HIERARQUIA' => constant($nomeContexto . '_SIGLA_UNIDADE_HIERARQUIA'),
          'NOME_UNIDADE' => constant($nomeContexto . '_NOME_UNIDADE'),
          'LOGIN' => constant($nomeContexto . '_USUARIO_LOGIN'),
          'SENHA' => constant($nomeContexto . '_USUARIO_SENHA'),
          'TIPO_PROCESSO' => constant($nomeContexto . '_TIPO_PROCESSO'),
          'TIPO_DOCUMENTO' => constant($nomeContexto . '_TIPO_DOCUMENTO'),
          'TIPO_DOCUMENTO_NAO_MAPEADO' => constant($nomeContexto . '_TIPO_DOCUMENTO_NAO_MAPEADO'),
          'CARGO_ASSINATURA' => constant($nomeContexto . '_CARGO_ASSINATURA'),
          'SIGLA_UNIDADE_HIERARQUIA' => constant($nomeContexto . '_SIGLA_UNIDADE_HIERARQUIA'),
          'SIGLA_UNIDADE_SECUNDARIA' => constant($nomeContexto . '_SIGLA_UNIDADE_SECUNDARIA'),
          'SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA' => constant($nomeContexto . '_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA'),
          'NOME_UNIDADE_SECUNDARIA' => constant($nomeContexto . '_NOME_UNIDADE_SECUNDARIA'),
          'HIPOTESE_RESTRICAO_ID' => constant($nomeContexto . '_HIPOTESE_RESTRICAO_ID'),
          'HIPOTESE_RESTRICAO' => constant($nomeContexto . '_HIPOTESE_RESTRICAO'),
          'HIPOTESE_RESTRICAO_NAO_MAPEADO' => constant($nomeContexto . '_HIPOTESE_RESTRICAO_NAO_MAPEADO'),
          'REP_ESTRUTURAS' => constant($nomeContexto . '_REP_ESTRUTURAS'),
          'HIPOTESE_RESTRICAO_PADRAO' => constant($nomeContexto . '_HIPOTESE_RESTRICAO_PADRAO'),
          'ID_REP_ESTRUTURAS' => constant($nomeContexto . '_ID_REP_ESTRUTURAS'),
          'ID_ESTRUTURA' => constant($nomeContexto . '_ID_ESTRUTURA'),
          'SIGLA_ESTRUTURA' => constant($nomeContexto . '_SIGLA_ESTRUTURA'),
          'HIPOTESE_RESTRICAO_INATIVA' => constant($nomeContexto . '_HIPOTESE_RESTRICAO_INATIVA'),
          'TIPO_PROCESSO_SIGILOSO' => constant($nomeContexto . '_TIPO_PROCESSO_SIGILOSO'),
          'HIPOTESE_SIGILOSO' => constant($nomeContexto . '_HIPOTESE_SIGILOSO'),
      );
      switch ($nomeContexto) {
        case CONTEXTO_ORGAO_A:
            $objContexto['LOCALIZACAO_CERTIFICADO_DIGITAL'] = getenv('ORG1_CERTIFICADO');
            $objContexto['SENHA_CERTIFICADO_DIGITAL'] = getenv('ORG1_CERTIFICADO_SENHA');
            break;

        case CONTEXTO_ORGAO_B:
            $objContexto['LOCALIZACAO_CERTIFICADO_DIGITAL'] = getenv('ORG2_CERTIFICADO');
            $objContexto['SENHA_CERTIFICADO_DIGITAL'] = getenv('ORG2_CERTIFICADO_SENHA');
            break;

        default:
            $objContexto['LOCALIZACAO_CERTIFICADO_DIGITAL'] = getenv('ORG1_CERTIFICADO');
            $objContexto['SENHA_CERTIFICADO_DIGITAL'] = getenv('ORG1_CERTIFICADO_SENHA');
            break;
      }

      return $objContexto;
  }

  protected function acessarSistema($url, $siglaUnidade, $login, $senha)
    {
      $this->url($url);
      $this->paginaLogin->executarAutenticacao($login, $senha);
      $this->selecionarUnidadeInterna($siglaUnidade);
      $this->url($url);
  }

  protected function selecionarUnidadeInterna($unidadeDestino)
    {
      $this->paginaBase->selecionarUnidadeContexto($unidadeDestino);
  }

  protected function sairSistema()
    {
      $this->paginaBase->sairSistema();
  }

  protected function abrirProcesso($protocolo)
    {
      $this->paginaBase->navegarParaControleProcesso();
    try {
        $this->paginaControleProcesso->abrirProcesso($protocolo);
    } catch (\Exception $e) {
        $this->paginaBase->pesquisar($protocolo);
        sleep(1);
        $this->paginaBase->elByXPath('(//a[@id="lnkInfraMenuSistema"])[2]')->click(); //ícone de 3 risquinhos horizontais que abre/fecha o menu
    }
  }

  protected function abrirProcessoControleProcesso($protocolo)
    {
      $this->paginaBase->navegarParaControleProcesso();
      $this->paginaControleProcesso->abrirProcesso($protocolo);
  }

  protected function abrirProcessoPelaDescricao($descricao)
    {
      $this->paginaBase->navegarParaControleProcesso();
      $protocolo = $this->paginaControleProcesso->localizarProcessoPelaDescricao($descricao);
    if ($protocolo) {
        $this->paginaControleProcesso->abrirProcesso($protocolo);
    }
      return $protocolo;
  }

  protected function tramitarProcessoExternamente(
    $protocolo,
    $repositorio,
    $unidadeDestino,
    $unidadeDestinoHierarquia,
    $urgente = false,
    $callbackEnvio = null,
    $timeout = PEN_WAIT_TIMEOUT,
    $multiplosOrgaos = false
  )
    {
      $this->paginaProcesso->navegarParaTramitarProcesso();
    
      // Preencher parâmetros do trâmite
      $this->paginaTramitar->repositorio($repositorio);
      $this->paginaTramitar->unidade($unidadeDestino, $unidadeDestinoHierarquia);
      if ($multiplosOrgaos) { $this->paginaTramitar->selecionarMultiplosOrgaos(); }
      $this->paginaTramitar->tramitar();

    try {
        $mensagemAlerta = $this->paginaTramitar->alertTextAndClose(true);
    } catch (Exception $e) {
    }

    if (isset($mensagemAlerta)) {
        throw new Exception($mensagemAlerta);
    }

      $callbackEnvio = $callbackEnvio ?: function () {
        try {
            $this->paginaTramitar->frame('ifrEnvioProcesso');
            $mensagemSucesso = mb_convert_encoding('Trâmite externo do processo finalizado com sucesso!', 'UTF-8', 'ISO-8859-1');
            $this->assertStringContainsString($mensagemSucesso, $this->paginaTramitar->elByCss('body')->getText());
            $btnFechar = $this->paginaTramitar->elByXPath("//input[@id='btnFechar']");
            $btnFechar->click();
        } finally {
          try {
              $this->paginaTramitar->frame(null);
              $this->paginaTramitar->frame("ifrConteudoVisualizacao");
              $this->paginaTramitar->frame("ifrVisualizacao");
          } catch (Exception $e) {
          }
        }

          return true;
      };

    try {
        $this->waitUntil($callbackEnvio, $timeout);
    } finally {
      try {
          $this->paginaTramitar->frame(null);
          $this->paginaTramitar->frame("ifrVisualizacao");
      } catch (Exception $e) {
      }
    }

    // executa pendências APÓS confirmação de envio
    if (DESATIVAR_AGENDAMENTO == 'true' && $executarTramitarPendencias) {
      // Equivalente ao: make tramitar-pendencias-simples, após clicar no botão enviar (para órgão externo)
      $this->executarTramitarPendenciasSimples();
    }
  }

  /**
   * Realiza tramitação externa de processo esperando um erro específico
   * 
   * Este método é uma variação do tramitarProcessoExternamente() que, ao invés de
   * validar o sucesso da tramitação, valida que um erro específico foi retornado
   * pelo sistema. É utilizado em testes que precisam validar comportamentos de rejeição,
   * como tentativa de tramitar documentos sigilosos ou com restrições de segurança.
   * 
   * Fluxo de execução:
   * 1. Navega para a tela de tramitação externa
   * 2. Preenche os dados do trâmite (repositório, unidade, múltiplos órgãos)
   * 3. Envia o trâmite
   * 4. Aguarda a janela de feedback do sistema
   * 5. Valida que a mensagem de erro esperada está presente
   * 6. Fecha a janela de feedback
   * 
   * @param string|object $protocolo Número do processo a ser tramitado
   * @param string $repositorio Nome do repositório de estruturas destino
   * @param string $unidadeDestino Nome da unidade de destino
   * @param string $unidadeDestinoHierarquia Hierarquia da unidade (opcional)
   * @param string $erroEsperado Texto do erro que deve ser retornado pelo sistema
   * @param bool $urgente Define se o trâmite é urgente (padrão: false)
   * @param bool $multiplosOrgaos Define se deve marcar opção de múltiplos órgãos (padrão: false)
   * @param int $timeout Tempo máximo de espera em segundos (padrão: PEN_WAIT_TIMEOUT)
   * 
   * @throws Exception Se houver um alert antes do processamento
   * @throws AssertionFailedError Se o erro esperado não for encontrado
   * 
   * @return void
   */
  protected function tramitarProcessoExternamenteErroEsperado(
    $protocolo,
    $repositorio,
    $unidadeDestino,
    $unidadeDestinoHierarquia,
    $erroEsperado,
    $urgente = false,
    $multiplosOrgaos = false,
    $timeout = PEN_WAIT_TIMEOUT
  )
    {
      $this->paginaProcesso->navegarParaTramitarProcesso();
    
      // Preencher parâmetros do trâmite
      $this->paginaTramitar->repositorio($repositorio);
      $this->paginaTramitar->unidade($unidadeDestino, $unidadeDestinoHierarquia);
      if ($multiplosOrgaos) { $this->paginaTramitar->selecionarMultiplosOrgaos(); }
      $this->paginaTramitar->tramitar();

    try {
        $mensagemAlerta = $this->paginaTramitar->alertTextAndClose(true);
    } catch (Exception $e) {
    }

    if (isset($mensagemAlerta)) {
        throw new Exception($mensagemAlerta);
    }

      $callbackValidacaoErro = function () use ($erroEsperado) {
        try {
            $this->paginaTramitar->frame('ifrEnvioProcesso');
            $conteudoBody = $this->paginaTramitar->elByCss('body')->getText();
            $erroEsperadoConvertido = mb_convert_encoding($erroEsperado, 'UTF-8', 'ISO-8859-1');
            $this->assertStringContainsString($erroEsperadoConvertido, $conteudoBody);
            $btnFechar = $this->paginaTramitar->elByXPath("//input[@id='btnFechar']");
            $btnFechar->click();
        } finally {
          try {
              $this->paginaTramitar->frame(null);
              $this->paginaTramitar->frame("ifrConteudoVisualizacao");
              $this->paginaTramitar->frame("ifrVisualizacao");
          } catch (Exception $e) {
          }
        }

          return true;
      };

    try {
        $this->waitUntil($callbackValidacaoErro, $timeout);
    } finally {
      try {
          $this->paginaTramitar->frame(null);
          $this->paginaTramitar->frame("ifrVisualizacao");
      } catch (Exception $e) {
      }
    }

      sleep(1);
  }

  protected function tramitarProcessoExternamenteComValidacaoRemetente($protocolo, $repositorio, $unidadeDestino, $unidadeDestinoHierarquia, $urgente = false, $callbackEnvio = null, $timeout = PEN_WAIT_TIMEOUT)
    {
      $this->tramitarProcessoExternamente(
        $protocolo, 
        $repositorio, 
        $unidadeDestino, 
        $unidadeDestinoHierarquia, 
        $urgente, 
        $callbackEnvio, 
        $timeout, 
        $executarTramitarPendencias
      );
      $this->waitUntil(function() {
          sleep(2);
          $this->paginaBase->refresh();
        try {
            $this->assertStringNotContainsString(mb_convert_encoding("Processo em trâmite externo para ", 'UTF-8', 'ISO-8859-1'), $this->paginaProcesso->informacao());
            $this->assertFalse($this->paginaProcesso->processoAberto());
            $this->assertTrue($this->paginaProcesso->processoBloqueado());
            return true;
        } catch (AssertionFailedError $e) {
            return false;
        }
      }, PEN_WAIT_TIMEOUT);

      $unidade = mb_convert_encoding($unidadeDestino, "ISO-8859-1");
      $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", $protocolo, $unidade);
      $this->validarRecibosTramite($mensagemRecibo, true, true);
      $this->validarHistoricoTramite($unidadeDestino, true, true);
      $this->validarProcessosTramitados($protocolo, true);
  } 

  protected function tramitarProcessoInternamente($unidadeDestino, $manterAbertoNaUnidadeAtual = false)
    {
      // Acessar funcionalidade de trâmite interno
      $this->paginaProcesso->navegarParaTramitarProcessoInterno();

      // Preencher parâmetros do trâmite
      $this->paginaTramitar->unidadeInterna($unidadeDestino);
    if ($manterAbertoNaUnidadeAtual) {
        $this->paginaTramitar->manterAbertoNaUnidadeAtual();
    }
      $this->paginaTramitar->tramitarInterno();
      sleep(1);
  }

  protected function navegarParaCancelarDocumento($ordemDocumento)
    {
      $listaDocumentos = $this->paginaProcesso->listarDocumentos();
      $this->paginaProcesso->selecionarDocumento($listaDocumentos[$ordemDocumento]);
      $this->paginaDocumento->navegarParaCancelarDocumento();
  }

  protected function tramitarProcessoInternamenteParaCancelamento($unidadeOrigem, $unidadeDestino, $protocolo)
    {
      //Tramitar internamento para liberação da funcionalidade de cancelar
      $this->tramitarProcessoInternamente($unidadeDestino);

      //Selecionar unidade interna
      $this->selecionarUnidadeInterna($unidadeDestino);
    if ($protocolo) {
        $this->paginaControleProcesso->abrirProcesso($protocolo['PROTOCOLO']);
    }

      //Tramitar internamento para liberação da funcionalidade de cancelar
      $this->tramitarProcessoInternamente($unidadeOrigem);

      //Selecionar unidade interna
      $this->selecionarUnidadeInterna($unidadeOrigem);
    if ($protocolo) {
        $this->paginaControleProcesso->abrirProcesso($protocolo['PROTOCOLO']);
    }
  }

  protected function validarRecibosTramite($mensagem, $verificarReciboEnvio, $verificarReciboConclusao)
    {
      $mensagem = mb_convert_encoding($mensagem, 'UTF-8', 'ISO-8859-1');
      $this->waitUntil(function() use ($mensagem, $verificarReciboEnvio, $verificarReciboConclusao) {
          sleep(2);
          $this->paginaProcesso->refresh();
          $this->paginaProcesso->navegarParaConsultarRecibos();
        if($this->paginaReciboTramite->contemTramite($mensagem, $verificarReciboEnvio, $verificarReciboConclusao)) {
            return true;
        }
      }, PEN_WAIT_TIMEOUT);
      $this->assertTrue($this->paginaReciboTramite->contemTramite($mensagem, $verificarReciboEnvio, $verificarReciboConclusao));
  }

  protected function validarHistoricoTramite(
        $unidadeDestino,
        $verificarProcessoEmTramitacao = true,
        $verificarProcessoRecebido = true,
        $verificarProcessoRejeitado = false,
        $motivoRecusa = null
    ) {
      $this->paginaProcesso->navegarParaConsultarAndamentos();

    if ($verificarProcessoEmTramitacao) {
        $this->assertTrue($this->paginaConsultarAndamentos->contemTramiteProcessoEmTramitacao($unidadeDestino));
    }

    if ($verificarProcessoRecebido) {
        $this->assertTrue($this->paginaConsultarAndamentos->contemTramiteProcessoRecebido($unidadeDestino));
    }

    if ($verificarProcessoRejeitado) {

        $this->waitUntil(function() use ($unidadeDestino, $motivoRecusa) {
            $this->paginaProcesso->refresh();
            $this->paginaProcesso->navegarParaConsultarAndamentos();
            $andamento = $this->paginaConsultarAndamentos->contemTramiteProcessoRejeitado($unidadeDestino, $motivoRecusa);
          if ($andamento){
              return true;
          }
        }, PEN_WAIT_TIMEOUT);

        $this->assertTrue($this->paginaConsultarAndamentos->contemTramiteProcessoRejeitado($unidadeDestino, $motivoRecusa));
    }
  }



  protected function validarDadosProcesso($descricao, $restricao, $observacoes, $listaInteressados, $hipoteseLegal = null)
    {
      $this->paginaProcesso->navegarParaEditarProcesso();
      $this->assertEquals(mb_convert_encoding($descricao, 'UTF-8', 'ISO-8859-1'), $this->paginaEditarProcesso->descricao());
      $this->assertEquals($restricao, $this->paginaEditarProcesso->restricao());

      $listaInteressados = is_array($listaInteressados) ? $listaInteressados : array($listaInteressados);
    for ($i = 0; $i < count($listaInteressados); $i++) {
        $this->assertStringStartsWith(substr($listaInteressados[$i], 0, 100), $this->paginaEditarProcesso->listarInteressados()[$i]);
    }

    if ($observacoes) {
        $this->assertStringContainsString($observacoes, $this->paginaBase->elByCss('body')->getText());
    }

    if ($hipoteseLegal != null) {
        $hipoteseLegalDocumento = $this->paginaEditarProcesso->recuperarHipoteseLegal();
        $this->assertEquals($hipoteseLegal, $hipoteseLegalDocumento);
    }
  }

  protected function validarDocumentoCancelado($nomeDocArvore)
    {
      $this->assertTrue($this->paginaProcesso->ehDocumentoCancelado($nomeDocArvore));
  }

  protected function validarDocumentoMovido($nomeDocArvore)
    {
      $this->assertTrue($this->paginaProcesso->ehDocumentoMovido($nomeDocArvore));
  }

  protected function validarDadosDocumento($nomeDocArvore, $dadosDocumento, $destinatario, $unidadeSecundaria = false, $hipoteseLegal = null)
    {

      // Verifica se documento possui marcação de documento anexado
      $bolPossuiDocumentoReferenciado = !is_null($dadosDocumento['ORDEM_DOCUMENTO_REFERENCIADO']);

    if (($this->paginaProcesso->ehDocumentoCancelado($nomeDocArvore) == false) and ($this->paginaProcesso->ehDocumentoMovido($nomeDocArvore) == false)) {

        $this->paginaProcesso->selecionarDocumento($nomeDocArvore);
        $this->paginaDocumento->navegarParaConsultarDocumento();
                        
        $mesmoOrgao = $dadosDocumento['ORIGEM'] == $destinatario['URL'];

      if ($mesmoOrgao && $dadosDocumento['TIPO'] == 'G') {
        $this->assertEquals($dadosDocumento["DESCRICAO"], $this->paginaDocumento->descricao());
        if (!$mesmoOrgao) {
            $observacoes = ($unidadeSecundaria) ? $this->paginaDocumento->observacoesNaTabela() : $this->paginaDocumento->observacoes();
            $this->assertEquals($dadosDocumento['OBSERVACOES'], $observacoes);
        }
      } else {
          $this->assertNotNull($this->paginaDocumento->nomeAnexo());
          $contemVariosComponentes = is_array($dadosDocumento['ARQUIVO']);
        if (!$contemVariosComponentes) {
              $nomeArquivo = $dadosDocumento['ARQUIVO'];
              $this->assertStringContainsString(basename($nomeArquivo), $this->paginaDocumento->nomeAnexo());
          if ($hipoteseLegal != null) {
            $hipoteseLegalDocumento = $this->paginaDocumento->recuperarHipoteseLegal();
            $this->assertEquals($hipoteseLegal, $hipoteseLegalDocumento);
          }
        }
      }
    }
  }

  protected function validarProcessosTramitados($protocolo, $deveExistir)
    {
      $this->paginaBase->frame(null);
      $this->paginaBase->navegarParaControleProcesso();
      $txtPesquisaMenu = $this->paginaBase->elById("txtInfraPesquisarMenu");
      if (!$txtPesquisaMenu->isDisplayed()) {
          $this->paginaBase->elByXPath('(//a[@id="lnkInfraMenuSistema"])[2]')->click();//ícone de 3 risquinhos horizontais que abre/fecha o menu
      }
      $this->paginaBase->navegarPara("Processos em Tramitação Externa");
      $this->assertEquals($deveExistir, $this->paginaProcessosTramitadosExternamente->contemProcesso($protocolo));
  }

  protected function validarProcessoRejeitado()
    {
      $this->paginaBase->navegarParaControleProcesso();
      $this->assertTrue($this->paginaControleProcesso->contemProcesso(self::$protocoloTeste));
      $this->assertTrue($this->paginaControleProcesso->contemAlertaProcessoRecusado(self::$protocoloTeste));
  }

  /**
   * Espera até a condição ser verdadeira
   */
  public function waitUntil(callable $condition, int $timeout = PEN_WAIT_TIMEOUT): void
  {
    $wait = new WebDriverWait(self::$driver, $timeout);
    $wait->until($condition);
  }

  public function executarTramitarPendenciasSimples(): void
    {
        $scriptEnvio       = 'php /opt/sei/scripts/mod-pen/MonitoramentoEnvioTarefasPEN.php';
        $scriptRecebimento = 'php /opt/sei/scripts/mod-pen/MonitoramentoRecebimentoTarefasPEN.php';

        shell_exec("docker exec -e XDEBUG_MODE=off funcional-org1-http-1 {$scriptEnvio}");
        shell_exec("docker exec -e XDEBUG_MODE=off funcional-org1-http-1 {$scriptRecebimento}");
        shell_exec("docker exec -e XDEBUG_MODE=off funcional-org2-http-1 {$scriptEnvio}");
        shell_exec("docker exec -e XDEBUG_MODE=off funcional-org2-http-1 {$scriptRecebimento}");
        shell_exec("docker exec -e XDEBUG_MODE=off funcional-org1-http-1 {$scriptRecebimento}");
    }

  public function gerarDadosProcessoTeste($contextoProducao)
    {
      return array(
          "TIPO_PROCESSO" => $contextoProducao['TIPO_PROCESSO'],
          "DESCRICAO" => randomString(100),
          "OBSERVACOES" => null,
          "INTERESSADOS" => str_repeat(randomString(9) . ' ', 25),
          "RESTRICAO" => self::STA_NIVEL_ACESSO_PUBLICO,
          "ORIGEM" => $contextoProducao['URL'],
      );
  }

  public function gerarDadosDocumentoInternoTeste($contextoProducao)
    {
      return array(
          'TIPO' => 'G', // Documento do tipo Gerado pelo sistema
          "NUMERO" => null, //Gerado automaticamente no cadastramento do documento
          "TIPO_DOCUMENTO" => $contextoProducao['TIPO_DOCUMENTO'],
          "DESCRICAO" => trim(str_repeat(randomString(9) . ' ', 10)),
          "OBSERVACOES" => null,
          "INTERESSADOS" => str_repeat(randomString(9) . ' ', 25),
          "RESTRICAO" => self::STA_NIVEL_ACESSO_PUBLICO,
          "ORDEM_DOCUMENTO_REFERENCIADO" => null,
          "ARQUIVO" => ".html",
          "ORIGEM" => $contextoProducao['URL'],
      );
  }

  public function gerarDadosDocumentoExternoTeste($contextoProducao, $nomesArquivos = 'arquivo_pequeno.txt', $ordemDocumentoReferenciado = null)
    {
      // Tratamento para lista de arquivos em casos de documentos com mais de um componente digital
      $pasta = self::PASTA_ARQUIVOS_TESTE;
      $arquivos = is_array($nomesArquivos) ? array_map(function ($item) use ($pasta) {
          return "$pasta/$item";
      }, $nomesArquivos) : "$pasta/$nomesArquivos";

      return array(
          'TIPO' => 'R', // Documento do tipo Recebido pelo sistema
          "NUMERO" => null, //Gerado automaticamente no cadastramento do documento
          "TIPO_DOCUMENTO" => $contextoProducao['TIPO_DOCUMENTO'],
          "DATA_ELABORACAO" => '01/01/2017',
          "DESCRICAO" => str_repeat(randomString(9) . ' ', 10),
          "OBSERVACOES" => randomString(500),
          "INTERESSADOS" => str_repeat(randomString(9) . ' ', 25),
          "ORDEM_DOCUMENTO_REFERENCIADO" => $ordemDocumentoReferenciado,
          "RESTRICAO" => self::STA_NIVEL_ACESSO_PUBLICO,
          "ARQUIVO" => $arquivos,
          "ORIGEM" => $contextoProducao['URL'],
      );
  }

  public function gerarDadosDocumentoExternoGrandeTeste($contextoProducao, $nomesArquivo = 'arquivo_grande_gerado.txt', $tamanhoMB = 100, $ordemDocumentoReferenciado = null)
    {
      // Tratamento para lista de arquivos em casos de documentos com mais de um componente digital
      $pasta = self::PASTA_ARQUIVOS_TESTE;
      shell_exec('dd if=/dev/zero of=' . self::PASTA_ARQUIVOS_TESTE . '/' . $nomesArquivo . ' bs=1M count=' . $tamanhoMB);
      $arquivos = "$pasta/$nomesArquivo";

      return array(
          'TIPO' => 'R', // Documento do tipo Recebido pelo sistema
          "NUMERO" => null, //Gerado automaticamente no cadastramento do documento
          "TIPO_DOCUMENTO" => $contextoProducao['TIPO_DOCUMENTO'],
          "DATA_ELABORACAO" => '01/01/2017',
          "DESCRICAO" => str_repeat(randomString(9) . ' ', 10),
          "OBSERVACOES" => randomString(500),
          "INTERESSADOS" => str_repeat(randomString(9) . ' ', 25),
          "ORDEM_DOCUMENTO_REFERENCIADO" => $ordemDocumentoReferenciado,
          "RESTRICAO" => self::STA_NIVEL_ACESSO_PUBLICO,
          "ARQUIVO" => $arquivos,
          "ORIGEM" => $contextoProducao['URL'],
      );
  }

  public function realizarValidacaoRecebimentoProcessoNoDestinatario($processoTeste, $documentosTeste, $destinatario, $devolucao = false, $unidadeSecundaria = false)
    {
      $strProtocoloTeste = $processoTeste['PROTOCOLO'];

      // 10 - Acessar sistema de REMETENTE do processo
      $this->acessarSistema($destinatario['URL'], $destinatario['SIGLA_UNIDADE'], $destinatario['LOGIN'], $destinatario['SENHA']);

      // 11 - Abrir protocolo na tela de controle de processos
      $this->waitUntil(function() use ($strProtocoloTeste) {
          $this->abrirProcessoControleProcesso($strProtocoloTeste);
          return true;
      }, PEN_WAIT_TIMEOUT);

      $listaDocumentos = $this->paginaProcesso->listarDocumentos();

      // 12 - Validar dados  do processo
      $devolucao = $processoTeste['ORIGEM'] == $destinatario['URL'];
      $strTipoProcesso = mb_convert_encoding("Tipo de processo no órgão de origem: ", 'UTF-8', 'ISO-8859-1');
      $strTipoProcesso .= $processoTeste['TIPO_PROCESSO'];
      $processoTeste['OBSERVACOES'] = (!$devolucao) ? $strTipoProcesso : $processoTeste['OBSERVACOES'];
      $this->validarDadosProcesso($processoTeste['DESCRICAO'], $processoTeste['RESTRICAO'], $processoTeste['OBSERVACOES'], $processoTeste['INTERESSADOS']);

      // 13 - Verificar recibos de trâmite
      $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);

      // 14 - Validar dados do documento
      $documentosTeste = array_key_exists('TIPO', $documentosTeste) ? array($documentosTeste) : $documentosTeste;
      $this->assertEquals(count($listaDocumentos), count($documentosTeste));

    for ($i = 0; $i < count($listaDocumentos); $i++) {
        $this->validarDadosDocumento($listaDocumentos[$i], $documentosTeste[$i], $destinatario, $unidadeSecundaria, null);
    }
  }

  public function realizarValidacaoRecebimentoDocumentoAvulsoNoDestinatario($documentosTeste, $destinatario, $devolucao = false, $unidadeSecundaria = false)
    {
      $strProtocoloTeste = null;
      $strDescricao = $documentosTeste['DESCRICAO'];

      // Acessar sistema de REMETENTE do processo
      $this->acessarSistema($destinatario['URL'], $destinatario['SIGLA_UNIDADE'], $destinatario['LOGIN'], $destinatario['SENHA']);

      // Abrir protocolo na tela de controle de processos pelo texto da descrição
      $this->waitUntil(function() use ($strDescricao, &$strProtocoloTeste) {
        sleep(2);
        try {
            $this->paginaBase->refresh();
            $strProtocoloTeste = $this->abrirProcessoPelaDescricao($strDescricao);    
            if ($strProtocoloTeste){
              return true;
            }
        } catch (\Exception $e) {
            return false;
        }
      }, PEN_WAIT_TIMEOUT);
        
      $this->assertNotFalse($strProtocoloTeste);
      $listaDocumentos = $this->paginaProcesso->listarDocumentos();

      // Validar dados  do processo
      $this->validarDadosProcesso($documentosTeste['DESCRICAO'], $documentosTeste['RESTRICAO'], null, array($documentosTeste['INTERESSADOS']));

      // Verificar recibos de trâmite
      $this->validarRecibosTramite("Recebimento do Documento $strProtocoloTeste", false, true);

      // Validar dados do documento
      $documentosTeste = array_key_exists('TIPO', $documentosTeste) ? array($documentosTeste) : $documentosTeste;
      $this->assertEquals(count($listaDocumentos), count($documentosTeste));

    for ($i = 0; $i < count($listaDocumentos); $i++) {
        $this->validarDadosDocumento($listaDocumentos[$i], $documentosTeste[$i], $destinatario, $unidadeSecundaria);
    }

      return array(
          "TIPO_PROCESSO" => $destinatario['TIPO_PROCESSO'],
          "DESCRICAO" => $documentosTeste[0]['DESCRICAO'],
          "OBSERVACOES" => null,
          "INTERESSADOS" => $documentosTeste[0]['INTERESSADOS'],
          "RESTRICAO" => $documentosTeste[0]['RESTRICAO'],
          "ORIGEM" => $destinatario['URL'],
          "PROTOCOLO" => $strProtocoloTeste
      );
  }

  public function realizarValidacaoNAORecebimentoProcessoNoDestinatario($destinatario, $processoTeste)
    {
      $this->acessarSistema($destinatario['URL'], $destinatario['SIGLA_UNIDADE'], $destinatario['LOGIN'], $destinatario['SENHA']);
      $this->paginaBase->navegarParaControleProcesso();
      $this->assertFalse($this->paginaControleProcesso->contemProcesso($processoTeste['PROTOCOLO'], false, false));
  }

}