<?php


use PHPUnit\Framework\Attributes\{Group,Large,Depends};

/**
 * Execution Groups
 * #[Group('execute_alone_group4')]
 */
class RecebimentoRecusaJustificativaGrandeTest extends FixtureCenarioBaseTestCase
{

    protected $destinatarioWs;
    protected $servicoPEN;
  public static $remetente;
  public static $destinatario;    
  public static $processoTeste;
  public static $documentoTeste;
  public static $protocoloTeste;


  public function setUp(): void
    {
      parent::setup();

      // Carregar contexto de testes e dados sobre certificado digital
      $this->destinatarioWs = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        
      $localCertificado = $this->destinatarioWs['LOCALIZACAO_CERTIFICADO_DIGITAL'];
      $senhaCertificado = $this->destinatarioWs['SENHA_CERTIFICADO_DIGITAL'];
      $this->servicoPEN = $this->instanciarApiDeIntegracao($localCertificado, $senhaCertificado);
  }

    /**
     * Teste de trâmite externo de processo com devolução para a mesma unidade de origem
     *
     * #[Group('envio')]
     *
     * @return void
     */
  public function test_tramitar_processo_da_origem()
    {

      // Configuração do dados para teste do cenário
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
      self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
      self::$documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

      $this->realizarTramiteExternoSemValidacaoNoRemetenteFixture(
        self::$processoTeste, 
        self::$documentoTeste, 
        self::$remetente, 
        self::$destinatario,
        false
      );
      self::$protocoloTeste = self::$processoTeste["PROTOCOLO"];

      $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);
      $id_tramite = $bancoOrgaoA->query("select max(id_tramite) as id_tramite from md_pen_componente_digital where protocolo = ?", array(self::$protocoloTeste));
      //recusa o tramite contendo justificativa grande
    if (array_key_exists("id_tramite", $id_tramite[0])) {
        $id_tramite=$id_tramite[0]["id_tramite"];
    }else{
        $id_tramite=$id_tramite[0]["ID_TRAMITE"];
    }
    
      $this->recusarTramite($id_tramite);        
  }

    /**
     * Teste de verificação do correto recebimento do processo no destinatário
     *
     * #[Group('verificacao_recebimento')]
     *
     * #[Depends('test_tramitar_processo_da_origem')]
     *
     * @return void
     */
  public function test_verificar_destino_processo_para_devolucao()
    {
      if (DESATIVAR_AGENDAMENTO == 'true') {
        $this->executarTramitarPendenciasSimples();
      }

      // Configuração do dados para teste do cenário
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
      $this->abrirProcesso(self::$protocoloTeste);
      $this->assertTrue($this->paginaProcesso->processoAberto());

      $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
      $this->validarRecibosTramite(sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTeste, $unidade), true, false);
      $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, false, true, sprintf("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Lorem ipsum dolor sit amet, consectetur adipiscing ..."));

      //Verifica se os í­cones de alerta de recusa foram adicionados e se o processo continua aberto na unidade
      $this->paginaBase->navegarParaControleProcessoIcone();
      $this->assertTrue($this->paginaControleProcesso->contemProcesso(self::$protocoloTeste));
      $this->assertTrue($this->paginaControleProcesso->contemAlertaProcessoRecusado(self::$protocoloTeste));
  }

    
  private function recusarTramite($id_tramite)
    {
      $justificativa = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea con";

      $parametros = new stdClass();
      $parametros->recusaDeTramite = new stdClass();
      $parametros->recusaDeTramite->IDT = $id_tramite;
      $parametros->recusaDeTramite->justificativa = mb_convert_encoding($justificativa, 'UTF-8', 'ISO-8859-1');
      $parametros->recusaDeTramite->motivo = "99";
        
      return $this->recusarTramiteAPI($parametros);
  }


  private function instanciarApiDeIntegracao($localCertificado, $senhaCertificado) 
    {
      // TODO: lembrar de pegar url dinamicamente quando SOAP for removido
      $strBaseUri = PEN_ENDERECO_WEBSERVICE;
      $arrheaders = [
          'Accept' => '*/*',
          'Content-Type' => 'application/json',
      ];
        
      $strClientGuzzle = new GuzzleHttp\Client([
          'base_uri' => $strBaseUri,
          'timeout'  => ProcessoEletronicoRN::WS_TIMEOUT_CONEXAO,
          'headers'  => $arrheaders,
          'cert'     => [$localCertificado, $senhaCertificado],
      ]);

      return $strClientGuzzle;
  }


  public function recusarTramiteAPI($parametros)
    {
      $idt = $parametros->recusaDeTramite->IDT;
      $justificativa = $parametros->recusaDeTramite->justificativa;
      $motivo = $parametros->recusaDeTramite->motivo;

      $endpoint = "tramites/{$idt}/recusa";

      $objProcessoEletronicoRN = new ProcessoEletronicoRN();
      $parametros = [
          'justificativa' => mb_convert_encoding($objProcessoEletronicoRN->reduzirCampoTexto($justificativa, 1000), 'UTF-8', 'ISO-8859-1'),
          'motivo' => $motivo
      ];

      $response = $this->servicoPEN->request('POST', $endpoint, [
          'json' => $parametros
      ]);

      return $response;
  }
}
