<?php

use PHPUnit\Framework\Attributes\{Group,Large,Depends};
use PHPUnit\Framework\AssertionFailedError;

/**
 * Testes de trâmite de processos anexados considerando cenário específico de trâmites e devoluções sucessivas
 *
 * O cenário descreve uma falha relatada pelos usuários em que um erro de inconsistência era causado após a realização dos seguintes passos:
 *
 *  - Trâmite de processo simples X do órgão A para o órgão B
 *  - Adição de novos documentos e devolução do processo para órgão A
 *  - Adição de novos documentos no processo X e anexação ao processo Y
 *  - Trâmite do processo Y para órgão B
 *  - Adição de novos documentos ao processo Y e devolução para o órgão A
 *  - Adição de novos documentos e devolução para órgão B
 *
 * Execution Groups
 * #[Group('execute_alone_group2')]
 */
class TramiteProcessosComDevolucoesEAnexacoesTest extends FixtureCenarioBaseTestCase
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
  public static $documentoTeste7;
  public static $documentoTeste8;
  public static $protocoloTestePrincipal;
  public static $protocoloTesteAnexado;
  public static $objProtocoloTestePrincipalDTO;
  public static $objProtocoloTesteAnexadoDTO;




    /**
     * Teste inicial de trâmite de processo apartado para o órgão B
     *
     * #[Group('envio')]
     * #[Large]
     * 
     * #[Depends('CenarioBaseTestCase::setUpBeforeClass')]
     *
     * @return void
     */
  public function test_tramitar_processo_simples_para_orgaoB()
    {
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

      // Criação e envio do segundo processo, representando o que será anexado ao processo principal
      self::$processoTesteAnexado = $this->gerarDadosProcessoTeste(self::$remetente);
      self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
      self::$documentoTeste2 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

      $documentos = array(self::$documentoTeste1, self::$documentoTeste2);
      $this->realizarTramiteExternoComValidacaoNoRemetenteFixture(self::$processoTesteAnexado, $documentos, self::$remetente, self::$destinatario);
      self::$protocoloTesteAnexado = self::$processoTesteAnexado["PROTOCOLO"];
  }


    /**
     * Teste de verificação do correto recebimento do processo simples no órgão B
     *
     * #[Group('verificacao_recebimento')]
     * #[Large]
     *
     * #[Depends('test_tramitar_processo_simples_para_orgaoB')]
     *
     * @return void
     */
  public function test_verificar_recebimento_processo_simples_destino()
    {
      $documentos = array(self::$documentoTeste1, self::$documentoTeste2);
      $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTesteAnexado, $documentos, self::$destinatario);
  }


    /**
     * Teste de trâmite externo de processo realizando a devolução para a mesma unidade de origem
     *
     * #[Group('envio')]
     * #[Large]
     *
     * #[Depends('test_verificar_recebimento_processo_simples_destino')]
     *
     * @return void
     */
  public function test_devolucao_processo_simples_para_origem()
    {
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
      self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      putenv("DATABASE_HOST=org2-database");

      // Definição de dados de teste do processo principal
      self::$documentoTeste3 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
      self::$documentoTeste4 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

      $documentos = array(self::$documentoTeste3, self::$documentoTeste4);
      $this->realizarTramiteExternoComValidacaoNoRemetenteFixture(self::$processoTesteAnexado, $documentos, self::$remetente, self::$destinatario);
        
      putenv("DATABASE_HOST=org1-database");
  }


    /**
     * Teste de verificação da correta devolução do processo simples para o órgão A
     *
     * #[Group('verificacao_recebimento')]
     * #[Large]
     *
     * #[Depends('test_devolucao_processo_simples_para_origem')]
     *
     * @return void
     */
  public function test_verificar_devolucao_processo_simples_origem()
    {
      $documentos = array(self::$documentoTeste1, self::$documentoTeste2, self::$documentoTeste3, self::$documentoTeste4);
      $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTesteAnexado, $documentos, self::$destinatario);
  }



    /**
     * Teste de trâmite de processos contendo o processo simples anexado à outro
     *
     * #[Group('envio')]
     * #[Large]
     *
     * #[Depends('test_verificar_devolucao_processo_simples_origem')]
     *
     * @return void
     */
  public function test_tramitar_processo_anexado_para_orgaoB()
    {
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

      // Definição de dados de teste do processo principal
      self::$processoTestePrincipal = $this->gerarDadosProcessoTeste(self::$remetente);
      self::$documentoTeste5 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);
      self::$documentoTeste6 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

      $objProtocoloTesteAnexadoDTO = $this->cadastrarProcessoFixture(self::$processoTestePrincipal);

      $this->cadastrarDocumentoExternoFixture(self::$documentoTeste5, $objProtocoloTesteAnexadoDTO->getDblIdProtocolo());

      $objProtocoloTestePrincipalDTO = $this->consultarProcessoFixture(self::$protocoloTesteAnexado, \ProtocoloRN::$TP_PROCEDIMENTO);
      self::$protocoloTestePrincipal = $objProtocoloTestePrincipalDTO->getStrProtocoloFormatado();

      // Realizar a anexação de processos
      $this->anexarProcessoFixture($objProtocoloTestePrincipalDTO->getDblIdProtocolo(), $objProtocoloTesteAnexadoDTO->getDblIdProtocolo());

      $this->cadastrarDocumentoInternoFixture(self::$documentoTeste6, $objProtocoloTestePrincipalDTO->getDblIdProtocolo());

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
     * #[Depends('test_tramitar_processo_anexado_para_orgaoB')]
     *
     * @return void
     */
  public function test_verificar_origem_processo_anexado()
    {
      $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];
      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
      $this->abrirProcesso(self::$protocoloTestePrincipal);

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
      }, PEN_WAIT_TIMEOUT);

      $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
      $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTestePrincipal, $unidade);
      $this->validarRecibosTramite($mensagemRecibo, true, true);
      $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);
      $this->validarProcessosTramitados(self::$protocoloTestePrincipal, $orgaosDiferentes);
  }



    /**
     * Teste de verificação do correto recebimento do processo anexado no destinatário
     *
     * #[Group('verificacao_recebimento')]
     * #[Large]
     *
     * #[Depends('test_verificar_origem_processo_anexado')]
     *
     * @return void
     */
  public function test_verificar_destino_processo_anexado()
    {
      $strProtocoloTeste = self::$protocoloTestePrincipal;
      $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];
      $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);
      $this->abrirProcesso(self::$protocoloTestePrincipal);

      $strTipoProcesso = mb_convert_encoding("Tipo de processo no órgão de origem: ", 'UTF-8', 'ISO-8859-1');
      $strTipoProcesso .= self::$processoTestePrincipal['TIPO_PROCESSO'];
      $strObservacoes = $orgaosDiferentes ? $strTipoProcesso : null;
      $this->validarDadosProcesso(
          self::$processoTestePrincipal['DESCRICAO'],
          self::$processoTestePrincipal['RESTRICAO'],
          $strObservacoes,
          array(self::$processoTestePrincipal['INTERESSADOS'])
      );

      $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);

      // Validação dos dados do processo principal
      $listaDocumentosProcessoPrincipal = $this->paginaProcesso->listarDocumentos();
      $this->assertEquals(3, count($listaDocumentosProcessoPrincipal));
      $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[0], self::$documentoTeste5, self::$destinatario);
      $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[2], self::$documentoTeste6, self::$destinatario);

      $this->paginaProcesso->selecionarDocumento(self::$protocoloTesteAnexado);
      $this->assertTrue($this->paginaDocumento->ehProcessoAnexado());

      // Validação dos dados do processo anexado
      $this->paginaProcesso->pesquisar(self::$protocoloTesteAnexado);
      $listaDocumentosProcessoAnexado = $this->paginaProcesso->listarDocumentos();
      $this->assertEquals(4, count($listaDocumentosProcessoAnexado));
      $this->validarDadosDocumento($listaDocumentosProcessoAnexado[0], self::$documentoTeste1, self::$destinatario);
      $this->validarDadosDocumento($listaDocumentosProcessoAnexado[1], self::$documentoTeste2, self::$destinatario);
      $this->validarDadosDocumento($listaDocumentosProcessoAnexado[2], self::$documentoTeste3, self::$destinatario);
      $this->validarDadosDocumento($listaDocumentosProcessoAnexado[3], self::$documentoTeste4, self::$destinatario);
  }

    /**
     * Teste de trâmite externo de processo realizando nova devolução para a mesma unidade de origem
     *
     * #[Group('envio')]
     * #[Large]
     *
     * #[Depends('test_verificar_destino_processo_anexado')]
     *
     * @return void
     */
  public function test_devolucao_processo_para_origem()
    {
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
      self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      putenv("DATABASE_HOST=org2-database");

      // Definição de dados de teste do processo principal
      self::$documentoTeste7 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);
      self::$documentoTeste8 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

      // Acessar sistema do this->REMETENTE do processo
      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      // Consulta no Org2/B o DTO do Protocolo Principal
      $objProtocoloTestePrincipalDTO = $this->consultarProcessoFixture(self::$protocoloTestePrincipal, \ProtocoloRN::$TP_PROCEDIMENTO);

      // Incluir novos documentos relacionados
      $this->cadastrarDocumentoExternoFixture(self::$documentoTeste7, $objProtocoloTestePrincipalDTO->getDblIdProtocolo());
      $this->cadastrarDocumentoInternoFixture(self::$documentoTeste8, $objProtocoloTestePrincipalDTO->getDblIdProtocolo());

      putenv("DATABASE_HOST=org1-database");

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
     * #[Depends('test_devolucao_processo_para_origem')]
     *
     * @return void
     */
  public function test_verificar_devolucao_origem_processo_anexado()
    {
      $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];
      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
      $this->abrirProcesso(self::$protocoloTestePrincipal);

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
      }, PEN_WAIT_TIMEOUT);

      $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
      $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTestePrincipal, $unidade);
      $this->validarRecibosTramite($mensagemRecibo, true, true);
      $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);
      $this->validarProcessosTramitados(self::$protocoloTestePrincipal, $orgaosDiferentes);
  }

    /**
     * Teste de verificação da correta devolução do processo anexado no destinatário
     *
     * #[Group('verificacao_recebimento')]
     * #[Large]
     *
     * #[Depends('test_verificar_devolucao_origem_processo_anexado')]
     *
     * @return void
     */
  public function test_verificar_devolucao_destino_processo_anexado()
    {
      $strProtocoloTeste = self::$protocoloTestePrincipal;
      $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);
      $this->abrirProcesso(self::$protocoloTestePrincipal);

      $this->validarDadosProcesso(
          self::$processoTestePrincipal['DESCRICAO'],
          self::$processoTestePrincipal['RESTRICAO'],
          self::$processoTestePrincipal['OBSERVACOES'],
          array(self::$processoTestePrincipal['INTERESSADOS'])
      );

      $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);

      // Validação dos dados do processo principal
      $listaDocumentosProcessoPrincipal = $this->paginaProcesso->listarDocumentos();
      $this->assertEquals(5, count($listaDocumentosProcessoPrincipal));
      $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[0], self::$documentoTeste5, self::$destinatario);
      $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[2], self::$documentoTeste6, self::$destinatario);
      $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[3], self::$documentoTeste7, self::$destinatario);
      $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[4], self::$documentoTeste8, self::$destinatario);

      $this->paginaProcesso->selecionarDocumento(self::$protocoloTesteAnexado);
      $this->assertTrue($this->paginaDocumento->ehProcessoAnexado());

      // Validação dos dados do processo anexado
      $this->paginaProcesso->pesquisar(self::$protocoloTesteAnexado);
      $listaDocumentosProcessoAnexado = $this->paginaProcesso->listarDocumentos();
      $this->assertEquals(4, count($listaDocumentosProcessoAnexado));
      $this->validarDadosDocumento($listaDocumentosProcessoAnexado[0], self::$documentoTeste1, self::$destinatario);
      $this->validarDadosDocumento($listaDocumentosProcessoAnexado[1], self::$documentoTeste2, self::$destinatario);
      $this->validarDadosDocumento($listaDocumentosProcessoAnexado[2], self::$documentoTeste3, self::$destinatario);
      $this->validarDadosDocumento($listaDocumentosProcessoAnexado[3], self::$documentoTeste4, self::$destinatario);
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
    //   $this->assertEquals(5, count($listaDocumentosProcessoPrincipal));
    //   $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[0], self::$documentoTeste5, self::$destinatario);
    //   $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[2], self::$documentoTeste6, self::$destinatario);
    //   $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[3], self::$documentoTeste7, self::$destinatario);
    //   $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[4], self::$documentoTeste8, self::$destinatario);

    //   $this->paginaProcesso->selecionarDocumento(self::$protocoloTesteAnexado);
    //   $this->assertTrue($this->paginaDocumento->ehProcessoAnexado());

    //   // Validação dos dados do processo anexado
    //   $this->paginaProcesso->pesquisar(self::$protocoloTesteAnexado);
    //   $listaDocumentosProcessoAnexado = $this->paginaProcesso->listarDocumentos();
    //   $this->assertEquals(4, count($listaDocumentosProcessoAnexado));
    //   $this->validarDadosDocumento($listaDocumentosProcessoAnexado[0], self::$documentoTeste1, self::$destinatario);
    //   $this->validarDadosDocumento($listaDocumentosProcessoAnexado[1], self::$documentoTeste2, self::$destinatario);
    //   $this->validarDadosDocumento($listaDocumentosProcessoAnexado[2], self::$documentoTeste3, self::$destinatario);
    //   $this->validarDadosDocumento($listaDocumentosProcessoAnexado[3], self::$documentoTeste4, self::$destinatario);
    // }

}
