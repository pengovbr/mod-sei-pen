<?php

use PHPUnit\Framework\Attributes\{Group,Large,Depends};
use PHPUnit\Framework\AssertionFailedError;

/**
 * Execution Groups
 * #[Group('execute_alone_group1')]
 */
class TramiteProcessoContendoDocumentoGeradoTest extends FixtureCenarioBaseTestCase
{
  public static $remetente;
  public static $destinatario;
  public static $processoTeste;
  public static $documentoTeste;
  public static $protocoloTeste;

    /**
     * Teste de trâmite externo de processo contendo apenas um documento interno (gerado)
     *
     * #[Group('envio')]
     * #[Large]
     * 
     * @Depends CenarioBaseTestCase::setUpBeforeClass
     *
     * @return void
     */
  public function test_tramitar_processo_contendo_documento_gerado()
    {
      // Configuração do dados para teste do cenário
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
      self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
      self::$documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);


      // 1 - Cadastrar novo processo de teste
      $objProtocoloDTO = $this->cadastrarProcessoFixture(self::$processoTeste);
      self::$protocoloTeste = $objProtocoloDTO->getStrProtocoloFormatado(); 
        
      // 2 - Incluir Documentos no Processo
      $this->cadastrarDocumentoInternoFixture(self::$documentoTeste, $objProtocoloDTO->getDblIdProtocolo());

      // 3 - Acessar sistema do this->REMETENTE do processo
      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      // 4 - Abrir processo
      $this->abrirProcesso(self::$protocoloTeste);

      // 5 - Trâmitar Externamento processo para órgão/unidade destinatária
      $this->tramitarProcessoExternamente(
              self::$protocoloTeste, self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'],
              self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);
  }


    /**
     * Teste de verificação do correto envio do processo no sistema remetente
     *
     * #[Group('verificacao_envio')]
     * #[Large]
     *
     * #[Depends('test_tramitar_processo_contendo_documento_gerado')]
     *
     * @return void
     */
  public function test_verificar_origem_processo_contendo_documento_gerado()
    {
      $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      $this->abrirProcesso(self::$protocoloTeste);

      // 6 - Verificar se situação atual do processo está como bloqueado
      $this->waitUntil(function() use (&$orgaosDiferentes) {
          sleep(5);
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

      // 7 - Validar se recibo de trâmite foi armazenado para o processo (envio e conclusão)
      $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
      $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTeste, $unidade);
      $this->validarRecibosTramite($mensagemRecibo, true, true);

      // 8 - Validar histórico de trâmite do processo
      $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);

      // 9 - Verificar se processo está na lista de Processos Tramitados Externamente
      $this->validarProcessosTramitados(self::$protocoloTeste, $orgaosDiferentes);
  }


    /**
     * Teste de verificação do correto recebimento do processo contendo apenas um documento interno (gerado)
     *
     * #[Group('verificacao_recebimento')]
     * #[Large]
     *
     * #[Depends('test_verificar_origem_processo_contendo_documento_gerado')]
     *
     * @return void
     */
  public function test_verificar_destino_processo_contendo_documento_gerado()
    {
      $strProtocoloTeste = self::$protocoloTeste;
      $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

      $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);

      // 11 - Abrir protocolo na tela de controle de processos
      $this->abrirProcesso(self::$protocoloTeste);
      $listaDocumentos = $this->paginaProcesso->listarDocumentos();

      // 12 - Validar dados  do processo
      $strTipoProcesso = mb_convert_encoding("Tipo de processo no órgão de origem: ", 'UTF-8', 'ISO-8859-1');
      $strTipoProcesso .= self::$processoTeste['TIPO_PROCESSO'];
      self::$processoTeste['OBSERVACOES'] = $orgaosDiferentes ? $strTipoProcesso : null;
      $this->validarDadosProcesso(self::$processoTeste['DESCRICAO'], self::$processoTeste['RESTRICAO'], self::$processoTeste['OBSERVACOES'], array(self::$processoTeste['INTERESSADOS']));

      // 13 - Verificar recibos de trâmite
      $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);

      // 14 - Validar dados do documento
      $this->assertTrue(count($listaDocumentos) == 1);
      $this->validarDadosDocumento($listaDocumentos[0], self::$documentoTeste, self::$destinatario);
  }

      /**
     * Teste de realizar reprodução de último tramite
     *
     * #[Group('envio')]
     * #[Large]
     *
     * #[Depends('test_verificar_origem_processo_contendo_documento_gerado')]
     * @return void
     */
    public function test_realizar_pedido_reproducao_ultimo_tramite()
    {
        $strProtocoloTeste = self::$protocoloTeste;

        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);

        // 11 - Reproduzir último trâmite
        $this->abrirProcesso($strProtocoloTeste);
        $resultadoReproducao = $this->paginaProcesso->reproduzirUltimoTramite();
        $this->assertStringContainsString(mb_convert_encoding("Reprodução de último trâmite executado com sucesso!", 'UTF-8', 'ISO-8859-1'), $resultadoReproducao);

        $this->waitUntil(function() {
            sleep(5);
            $this->paginaBase->refresh();
            $this->paginaProcesso->navegarParaConsultarAndamentos();
            $mensagemTramite = mb_convert_encoding("Reprodução de último trâmite iniciado para o protocolo ".  $strProtocoloTeste, 'UTF-8', 'ISO-8859-1');
          try {
              $this->assertTrue($this->paginaConsultarAndamentos->contemTramite($mensagemTramite));
              return true;
          } catch (AssertionFailedError $e) {
              return false;
          }

        }, PEN_WAIT_TIMEOUT);
    }

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
    public function test_reproducao_ultimo_tramite()
    {
        $strProtocoloTeste = self::$protocoloTeste;

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->abrirProcesso($strProtocoloTeste);

        $this->waitUntil(function() {
            sleep(5);
            $this->paginaBase->refresh();
            $this->paginaProcesso->navegarParaConsultarAndamentos();
            $mensagemTramite = mb_convert_encoding("Reprodução de último trâmite recebido na entidade", 'UTF-8', 'ISO-8859-1');
          try {
              $this->assertTrue($this->paginaConsultarAndamentos->contemTramite($mensagemTramite));
              return true;
          } catch (AssertionFailedError $e) {
              return false;
          }

        }, PEN_WAIT_TIMEOUT);

    }

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
    public function test_reproducao_ultimo_tramite_remetente_finalizado()
    {
        $strProtocoloTeste = self::$protocoloTeste;

        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);

        // 11 - Abrir protocolo na tela de controle de processos
        $this->abrirProcesso($strProtocoloTeste);
        
        $this->waitUntil(function() {
            sleep(5);
            $this->paginaBase->refresh();
            $this->paginaProcesso->navegarParaConsultarAndamentos();
            $mensagemTramite = mb_convert_encoding("Reprodução de último trâmite finalizado para o protocolo ".  $strProtocoloTeste, 'UTF-8', 'ISO-8859-1');
          try {
              $this->assertTrue($this->paginaConsultarAndamentos->contemTramite($mensagemTramite));
              return true;
          } catch (AssertionFailedError $e) {
              return false;
          }

        }, PEN_WAIT_TIMEOUT);

        $listaDocumentos = $this->paginaProcesso->listarDocumentos();

        // 12 - Validar dados  do processo
        $strTipoProcesso = mb_convert_encoding("Tipo de processo no órgão de origem: ", 'UTF-8', 'ISO-8859-1');
        $strTipoProcesso .= self::$processoTeste['TIPO_PROCESSO'];
        self::$processoTeste['OBSERVACOES'] = $orgaosDiferentes ? $strTipoProcesso : null;
        $this->validarDadosProcesso(self::$processoTeste['DESCRICAO'], self::$processoTeste['RESTRICAO'], self::$processoTeste['OBSERVACOES'], array(self::$processoTeste['INTERESSADOS']));

        // 13 - Verificar recibos de trâmite
        $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);

        // 14 - Validar dados do documento
        $this->assertTrue(count($listaDocumentos) == 1);
        $this->validarDadosDocumento($listaDocumentos[0], self::$documentoTeste, self::$destinatario);
    }

}
