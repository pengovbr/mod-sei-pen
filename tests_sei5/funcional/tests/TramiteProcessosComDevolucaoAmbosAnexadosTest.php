<?php

use PHPUnit\Framework\Attributes\{Group,Large,Depends};
use PHPUnit\Framework\AssertionFailedError;

/**
 * Testes de trâmite de processos anexado considerando a devolução do mesmo para a entidade de origem
 *
 * Execution Groups
 * #[Group('execute_alone_group6')]
 */
class TramiteProcessosComDevolucaoAmbosAnexadosTest extends FixtureCenarioBaseTestCase
{
  public static $remetente;
  public static $destinatario;
  public static $processoTestePrincipal;
  public static $processoTesteAnexado;
  public static $documentoTeste1;
  public static $documentoTeste2;
  public static $documentoTeste3;
  public static $documentoTeste4;
  public static $documentoTeste5;
  public static $documentoTeste6;
  public static $protocoloTestePrincipal;
  public static $protocoloTesteAnexado;

    /**
     * Teste inicial de trâmite de dois processos apartados para o sistema de origem
     *
     * Posteriormente os dois serão anexados e enviados de volta
     *
     * #[Group('envio')]
     * #[Large]
     * 
     * #[Depends('CenarioBaseTestCase::setUpBeforeClass')]
     *
     * @return void
     */
  public function test_tramitar_processos_separados_da_origem()
    {
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

      // Criação e envio do primeiro processo, representando o principal em seu retorno
      self::$processoTestePrincipal = $this->gerarDadosProcessoTeste(self::$remetente);
      self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
      self::$documentoTeste2 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

      $documentos = array(self::$documentoTeste1, self::$documentoTeste2);
      $this->realizarTramiteExternoComValidacaoNoRemetenteFixture(self::$processoTestePrincipal, $documentos, self::$remetente, self::$destinatario);
      self::$protocoloTestePrincipal = self::$processoTestePrincipal["PROTOCOLO"];

      $this->sairSistema();

      // Criação e envio do segundo processo, representando o que será anexado ao processo principal
      self::$processoTesteAnexado = $this->gerarDadosProcessoTeste(self::$remetente);
      self::$documentoTeste3 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
      self::$documentoTeste4 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

      $documentos = array(self::$documentoTeste3, self::$documentoTeste4);
      $this->realizarTramiteExternoComValidacaoNoRemetenteFixture(self::$processoTesteAnexado, $documentos, self::$remetente, self::$destinatario);
      self::$protocoloTesteAnexado = self::$processoTesteAnexado["PROTOCOLO"];
  }


    /**
     * Teste de verificação do correto recebimento dos dois processos separados no destino
     *
     * #[Group('verificacao_recebimento')]
     * #[Large]
     *
     * #[Depends('test_tramitar_processos_separados_da_origem')]
     *
     * @return void
     */
  public function test_verificar_recebimento_processos_separados_destino()
    {
      $documentos = array(self::$documentoTeste1, self::$documentoTeste2);
      $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTestePrincipal, $documentos, self::$destinatario);

      $this->sairSistema();

      $documentos = array(self::$documentoTeste3, self::$documentoTeste4);
      $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTesteAnexado, $documentos, self::$destinatario);
  }


    /**
     * Teste de trâmite externo de processo realizando a anexação e a devolução para a mesma unidade de origem
     *
     * #[Group('envio')]
     * #[Large]
     *
     * #[Depends('test_verificar_recebimento_processos_separados_destino')]
     *
     * @return void
     */
  public function test_devolucao_processo_anexado_para_origem()
    {
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
      self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      putenv("DATABASE_HOST=org2-database");

      // Definição de dados de teste do processo principal
      self::$documentoTeste5 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);
      self::$documentoTeste6 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

      $objProtocoloAnexadoDTO = $this->consultarProcessoFixture(self::$protocoloTesteAnexado, \ProtocoloRN::$TP_PROCEDIMENTO);
      $objProtocoloPrincipalDTO = $this->consultarProcessoFixture(self::$protocoloTestePrincipal, \ProtocoloRN::$TP_PROCEDIMENTO);
        
      // Cadastra documento Externo ao processo anexado
      $this->cadastrarDocumentoExternoFixture(self::$documentoTeste5, $objProtocoloAnexadoDTO->getDblIdProtocolo());

      // Anexa processo ao processo principal
      $this->anexarProcessoFixture($objProtocoloPrincipalDTO->getDblIdProtocolo(), $objProtocoloAnexadoDTO->getDblIdProtocolo());

      // Cadastra documento Externo ao processo principal
      $this->cadastrarDocumentoExternoFixture(self::$documentoTeste6, $objProtocoloPrincipalDTO->getDblIdProtocolo());
        
      putenv("DATABASE_HOST=org1-database");

      // Acessar sistema do this->REMETENTE do processo
      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      $this->abrirProcesso(self::$protocoloTestePrincipal);

      // Trâmitar Externamento processo para órgão/unidade destinatária
      $this->tramitarProcessoExternamente(
        self::$protocoloTestePrincipal,
        self::$destinatario['REP_ESTRUTURAS'],
        self::$destinatario['NOME_UNIDADE'],
        self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
        false,
        null,
        PEN_WAIT_TIMEOUT,
        true
      );
  }


    /**
     * Teste de verificação do correto envio do processo anexado no sistema remetente
     *
     * #[Group('verificacao_envio')]
     * #[Large]
     *
     * #[Depends('test_devolucao_processo_anexado_para_origem')]
     *
     * @return void
     */
  public function test_verificar_devolucao_origem_processo_anexado_ja_tramitado_com_sucesso_recusado()
    {
      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      // Mensagem parcial de recusa esperada no histórico do processo principal
      $mensagemRecusaParcial = sprintf(
          "O processo %s não pode ser anexado ao processo %s pois já foi tramitado, com sucesso, para outro órgão.",
          self::$protocoloTesteAnexado,
          self::$protocoloTestePrincipal
      );
      $mensagemRecusaParcial = mb_convert_encoding($mensagemRecusaParcial, 'UTF-8', 'ISO-8859-1');

      // Aguarda a recusa do trâmite: valida o ícone de recusa e o histórico contendo a mensagem parcial
      $this->waitUntil(function() use ($mensagemRecusaParcial) {
          sleep(2);
          $this->paginaBase->refresh();
        try {
            // Verifica se o ícone de alerta de recusa foi adicionado ao processo
            $this->paginaBase->navegarParaControleProcessoIcone();
            $this->assertTrue($this->paginaControleProcesso->contemAlertaProcessoRecusado(self::$protocoloTestePrincipal));

            // Verifica se o histórico de recusa contém a mensagem parcial esperada
            $this->abrirProcesso(self::$protocoloTestePrincipal);
            $this->paginaProcesso->navegarParaConsultarAndamentos();
            $this->assertTrue($this->paginaConsultarAndamentos->contemTramite($mensagemRecusaParcial));

            return true;
        } catch (AssertionFailedError $e) {
            return false;
        }
      }, PEN_WAIT_TIMEOUT);
  }

    /**
     * Teste de realizar reprodução de último tramite
     *
     * #[Group('envio')]
     * #[Large]
     *
     * #[Depends('test_verificar_devolucao_destino_processo_anexado')]
     * @return void
     */
    // public function test_realizar_pedido_reproducao_ultimo_tramite()
    // {
    //     $strProtocoloTeste = self::$protocoloTestePrincipal;

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
     * #[Depends('test_realizar_pedido_reproducao_ultimo_tramite')]
     *
     * @return void
     */
    // public function test_reproducao_ultimo_tramite()
    // {
    //     $strProtocoloTeste = self::$protocoloTestePrincipal;

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
     * #[Depends('test_reproducao_ultimo_tramite')]
     *
     * @return void
     */
    // public function test_reproducao_ultimo_tramite_remetente_finalizado()
    // {
    //     $strProtocoloTeste = self::$protocoloTestePrincipal;

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

    //   // Validação dos dados do processo principal
    //   $listaDocumentosProcessoPrincipal = $this->paginaProcesso->listarDocumentos();
    //   $this->assertEquals(4, count($listaDocumentosProcessoPrincipal));
    //   $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[0], self::$documentoTeste1, self::$destinatario);
    //   $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[1], self::$documentoTeste2, self::$destinatario);
    //   $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[3], self::$documentoTeste6, self::$destinatario);

    //   $this->paginaProcesso->selecionarDocumento(self::$protocoloTesteAnexado);
    //   $this->assertTrue($this->paginaDocumento->ehProcessoAnexado());

    //   // Validação dos dados do processo anexado
    //   $this->paginaProcesso->pesquisar(self::$protocoloTesteAnexado);
    //   $listaDocumentosProcessoAnexado = $this->paginaProcesso->listarDocumentos();
    //   $this->assertEquals(3, count($listaDocumentosProcessoAnexado));
    //   $this->validarDadosDocumento($listaDocumentosProcessoAnexado[0], self::$documentoTeste3, self::$destinatario);
    //   $this->validarDadosDocumento($listaDocumentosProcessoAnexado[1], self::$documentoTeste4, self::$destinatario);
    //   $this->validarDadosDocumento($listaDocumentosProcessoAnexado[2], self::$documentoTeste5, self::$destinatario);

    // }

}
