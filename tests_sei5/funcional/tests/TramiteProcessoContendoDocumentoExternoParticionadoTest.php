<?php

use PHPUnit\Framework\Attributes\{Group,Large,Depends};
use PHPUnit\Framework\AssertionFailedError;

/**
 * #[Group('rodarseparado')]
 * #[Group('rodarseparado2')]
 * #[Group('execute_alone_group1')]
 */
class TramiteProcessoContendoDocumentoExternoParticionadoTest extends FixtureCenarioBaseTestCase
{
  public static $remetente;
  public static $destinatario;
  public static $processoTeste;
  public static $documentoTeste;
  public static $protocoloTeste;

  public static function setUpBeforeClass(): void {
        
      parent::setUpBeforeClass();
      $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);    
      $bancoOrgaoA->execute("update infra_parametro set valor = ? where nome = ?", array(70, 'SEI_TAM_MB_DOC_EXTERNO'));

  }      
        
  public static function tearDownAfterClass(): void {

      parent::tearDownAfterClass();
      $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);    
      $bancoOrgaoA->execute("update infra_parametro set valor = ? where nome = ?", array(50, 'SEI_TAM_MB_DOC_EXTERNO'));

  }


    /**
     * Teste de trâmite externo de processo contendo documento externo particionado acima de 60Mb
     *
     * #[Group('envio')]
     * #[Large]
     * 
     * @Depends CenarioBaseTestCase::setUpBeforeClass
     *
     * @return void
     */
  public function test_tramitar_processo_contendo_documento_externo_60mb()
    {
      //Aumenta o tempo de timeout devido ao tamanho do arquivo arquivo_060.pdf
      // $this->setSeleniumServerRequestsTimeout(6000);

      // Configuração do dados para teste do cenário
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
      self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      putenv("DATABASE_HOST=org2-database");

      self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
      self::$documentoTeste = $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_060.pdf');
        
      $objProtocoloDTO  = $this->cadastrarProcessoFixture(self::$processoTeste);
      self::$protocoloTeste = $objProtocoloDTO->getStrProtocoloFormatado(); 

      // Altera tamanho máximo permitido para permitir o envio de arquivo superior à 50MBs
      $bancoOrgaoB = new DatabaseUtils(CONTEXTO_ORGAO_B);
    try {
        $bancoOrgaoB->execute("update infra_parametro set valor = ? where nome = ?", array(70, 'SEI_TAM_MB_DOC_EXTERNO'));
        $this->cadastrarDocumentoExternoFixture(self::$documentoTeste, $objProtocoloDTO->getDblIdProtocolo());
    } finally {
        $bancoOrgaoB->execute("update infra_parametro set valor = ? where nome = ?", array(50, 'SEI_TAM_MB_DOC_EXTERNO'));
    }
      putenv("DATABASE_HOST=org1-database");
        
      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      $this->abrirProcesso(self::$protocoloTeste);

      $this->tramitarProcessoExternamente(
        self::$protocoloTeste, 
        self::$destinatario['REP_ESTRUTURAS'], 
        self::$destinatario['NOME_UNIDADE'],
        self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], 
        false, 
        null, 
        PEN_WAIT_TIMEOUT_ARQUIVOS_GRANDES,
        true
      );
  }


    /**
     * Teste de verificação do correto envio do processo no sistema remetente
     *
     * #[Group('verificacao_envio')]
     * #[Large]
     *
     * #[Depends('test_tramitar_processo_contendo_documento_externo_60mb')]
     *
     * @return void
     */
  public function test_verificar_origem_processo_contendo_documento_externo_60mb()
    {
      //Aumenta o tempo de timeout devido ao tamanho do arquivo arquivo_060.pdf
      // $this->setSeleniumServerRequestsTimeout(60000);

      $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];
        
      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
      $this->abrirProcesso(self::$protocoloTeste);

      $this->waitUntil(function() use (&$orgaosDiferentes) {
          sleep(2);
          $this->paginaBase->refresh();
        try { 
            $this->assertStringNotContainsString(mb_convert_encoding("Processo em trâmite externo para ", 'UTF-8', 'ISO-8859-1'), $this->paginaProcesso->informacao());
            $this->assertFalse($this->paginaProcesso->processoAberto());
            $this->assertEquals($orgaosDiferentes, $this->paginaProcesso->processoBloqueado());
            return true;
        } catch (AssertionFailedError $e) {
            return false;
        }
      }, PEN_WAIT_TIMEOUT_ARQUIVOS_GRANDES);

      $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
      $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTeste, $unidade);
      $this->validarRecibosTramite($mensagemRecibo, true, true);
      $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);
      $this->validarProcessosTramitados(self::$protocoloTeste, $orgaosDiferentes);
  }


    /**
     * Teste de verificação do correto recebimento do processo contendo apenas um documento interno (gerado)
     *
     * #[Group('verificacao_recebimento')]
     * #[Large]
     *
     * #[Depends('test_verificar_origem_processo_contendo_documento_externo_60mb')]
     *
     * @return void
     */
  public function test_verificar_destino_processo_contendo_documento_externo_60mb()
    {
      $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, self::$documentoTeste, self::$destinatario);
  }

      /**
     * Teste de realizar reprodução de último tramite
     *
     * #[Group('envio')]
     * #[Large]
     *
     * #[Depends('test_verificar_destino_processo_contendo_documento_externo_60mb')]
     * @return void
     */
    // public function test_realizar_pedido_reproducao_ultimo_tramite()
    // {
    //     $strProtocoloTeste = self::$protocoloTeste;

    //     $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);

    //     // 11 - Reproduzir último trâmite
    //     $this->abrirProcesso($strProtocoloTeste);
    //     $resultadoReproducao = $this->paginaProcesso->reproduzirUltimoTramite();
    //     $this->assertStringContainsString(mb_convert_encoding("Reprodução de último trâmite executado com sucesso!", 'UTF-8', 'ISO-8859-1'), $resultadoReproducao);

    //     $this->waitUntil(function() {
    //         sleep(5);
    //         $this->paginaBase->refresh();
    //         $this->paginaProcesso->navegarParaConsultarAndamentos();
    //         $mensagemTramite = mb_convert_encoding("Reprodução de último trâmite iniciado para o protocolo ".  $strProtocoloTeste, 'UTF-8', 'ISO-8859-1');
    //       try {
    //           $this->assertTrue($this->paginaConsultarAndamentos->contemTramite($mensagemTramite));
    //           return true;
    //       } catch (AssertionFailedError $e) {
    //           return false;
    //       }

    //     }, PEN_WAIT_TIMEOUT);
    // }

    /**
     * Teste para verificar a reprodução de último tramite no destinatario
     *
     * #[Group('envio')]
     * #[Large]
     *
     * #[Depends('test_verificar_destino_processo_contendo_documento_externo_60mb')]
     *
     * @return void
     */
    // public function test_reproducao_ultimo_tramite()
    // {
    //     $strProtocoloTeste = self::$protocoloTeste;

    //     $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

    //     $this->abrirProcesso($strProtocoloTeste);

    //     $this->waitUntil(function() {
    //         sleep(5);
    //         $this->paginaBase->refresh();
    //         $this->paginaProcesso->navegarParaConsultarAndamentos();
    //         $mensagemTramite = mb_convert_encoding("Reprodução de último trâmite recebido na entidade", 'UTF-8', 'ISO-8859-1');
    //       try {
    //           $this->assertTrue($this->paginaConsultarAndamentos->contemTramite($mensagemTramite));
    //           return true;
    //       } catch (AssertionFailedError $e) {
    //           return false;
    //       }

    //     }, PEN_WAIT_TIMEOUT);

    // }

    /**
     * Teste para verificar a reprodução de último tramite no remetente
     *
     * #[Group('envio')]
     * #[Large]
     *
     * #[Depends('test_verificar_destino_processo_contendo_documento_externo_60mb')]
     *
     * @return void
     */
    // public function test_reproducao_ultimo_tramite_remetente_finalizado()
    // {
    //     $strProtocoloTeste = self::$protocoloTeste;

    //     $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);

    //     // 11 - Abrir protocolo na tela de controle de processos
    //     $this->abrirProcesso($strProtocoloTeste);
        
    //     $this->waitUntil(function() {
    //         sleep(5);
    //         $this->paginaBase->refresh();
    //         $this->paginaProcesso->navegarParaConsultarAndamentos();
    //         $mensagemTramite = mb_convert_encoding("Reprodução de último trâmite finalizado para o protocolo ".  $strProtocoloTeste, 'UTF-8', 'ISO-8859-1');
    //       try {
    //           $this->assertTrue($this->paginaConsultarAndamentos->contemTramite($mensagemTramite));
    //           return true;
    //       } catch (AssertionFailedError $e) {
    //           return false;
    //       }

    //     }, PEN_WAIT_TIMEOUT);

    //     $this->sairSistema();
    //     $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, self::$documentoTeste, self::$destinatario);

    // }

}
