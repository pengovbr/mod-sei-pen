<?php

/**
 *
 * Execution Groups
 * @group execute_parallel_group1
 */
class TramiteBlocoDeTramiteSituacaoProcessoConcluidoTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;


    /**
     * Teste pra validar mensagem de documento não assinado ao ser inserido em bloco
     *
     * @group envio
     * @large
     *
     * @return void
     */
    public function test_validar_situacao_do_processo_no_bloco_status6()
    {
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
      $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
      $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

      // Cadastrar novo processo de teste
      $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);
      $this->cadastrarDocumentoInternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());    

      $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
      $objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();

      $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
      $objBlocoDeTramiteProtocoloFixtureDTO = $objBlocoDeTramiteProtocoloFixture->carregar([
        'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
        'IdBloco' => $objBlocoDeTramiteDTO->getNumId()
      ]);

      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
      $this->paginaCadastrarProcessoEmBloco->bntTramitarBloco();
      $this->paginaCadastrarProcessoEmBloco->tramitarProcessoExternamente(
        self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'],
        self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false,
        function ($testCase) {
          try {
              $testCase->frame('ifrEnvioProcesso');
              $mensagemSucesso = mb_convert_encoding('Processo(s) aguardando envio. Favor acompanhar a tramitação por meio do bloco, na funcionalidade \'Blocos de Trâmite Externo\'', 'UTF-8', 'ISO-8859-1');
              $testCase->assertStringContainsString($mensagemSucesso, $testCase->byCssSelector('body')->text());
              $btnFechar = $testCase->byXPath("//input[@id='btnFechar']");
              $btnFechar->click();
          } finally {
              try {
                  $testCase->frame(null);
                  $testCase->frame("ifrVisualizacao");
              } catch (Exception $e) {
              }
          }

          return true;
        }
      );

      $this->waitUntil(function ($testCase) use ($objProtocoloDTO) {
        sleep(5);
        $testCase->refresh();

        $colunaEstado = $testCase->elements($testCase->using('xpath')->value('//table[@id="tblBlocos"]/tbody/tr/td[3]'));
        $this->assertEquals(mb_convert_encoding("Concluído", 'UTF-8', 'ISO-8859-1'), $colunaEstado[0]->text());

        $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
        $objBlocoDeTramiteProtocolo = $objBlocoDeTramiteProtocoloFixture->buscar([
          'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
        ]);

        $this->assertEquals(6, $objBlocoDeTramiteProtocolo[0]->getNumIdAndamento());
        return true;
      }, PEN_WAIT_TIMEOUT);
    }

    /**
     * Teste pra validar mensagem de documento não assinado ao ser inserido em bloco
     *
     * @group envio
     * @large
     *
     * @return void
     */
    public function test_validar_situacao_do_processo_no_bloco_status7()
    {
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
      $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
      $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

      // Cadastrar novo processo de teste
      $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);
      $this->cadastrarDocumentoInternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());    

      $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
      $objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();

      $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
      $objBlocoDeTramiteProtocoloFixtureDTO = $objBlocoDeTramiteProtocoloFixture->carregar([
        'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
        'IdBloco' => $objBlocoDeTramiteDTO->getNumId()
      ]);
      
      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
      $this->paginaCadastrarProcessoEmBloco->bntTramitarBloco();
      $this->paginaCadastrarProcessoEmBloco->tramitarProcessoExternamente(
        self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'],
        self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false,
        function ($testCase) {
          try {
              $testCase->frame('ifrEnvioProcesso');
              $mensagemSucesso = mb_convert_encoding('Processo(s) aguardando envio. Favor acompanhar a tramitação por meio do bloco, na funcionalidade \'Blocos de Trâmite Externo\'', 'UTF-8', 'ISO-8859-1');
              $testCase->assertStringContainsString($mensagemSucesso, $testCase->byCssSelector('body')->text());
              $btnFechar = $testCase->byXPath("//input[@id='btnFechar']");
              $btnFechar->click();
          } finally {
              try {
                  $testCase->frame(null);
                  $testCase->frame("ifrVisualizacao");
              } catch (Exception $e) {
              }
          }

          return true;
        }
      );

      $this->paginaBase->navegarParaControleProcesso();
      $this->abrirProcesso($objProtocoloDTO->getStrProtocoloFormatado());
      $this->paginaProcesso->cancelarTramitacaoExterna();
      $this->paginaTramitar->alertTextAndClose(true);

      $this->paginaBase->navegarParaControleProcesso();
      $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
      $this->waitUntil(function ($testCase) use ($objProtocoloDTO) {
        sleep(5);
        $testCase->refresh();

        $colunaEstado = $testCase->elements($testCase->using('xpath')->value('//table[@id="tblBlocos"]/tbody/tr/td[3]'));
        $this->assertEquals(mb_convert_encoding("Concluído", 'UTF-8', 'ISO-8859-1'), $colunaEstado[0]->text());
        
        $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
        $objBlocoDeTramiteProtocolo = $objBlocoDeTramiteProtocoloFixture->buscar([
          'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
        ]);

        $this->assertEquals(7, $objBlocoDeTramiteProtocolo[0]->getNumIdAndamento());
        return true;
      }, PEN_WAIT_TIMEOUT);
    }

    /**
     * Teste pra validar mensagem de documento não assinado ao ser inserido em bloco
     *
     * @group envio
     * @large
     *
     * @return void
     */
    public function test_validar_situacao_do_processo_no_bloco_status9()
    {
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
      $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
      $documentoTeste = $this->gerarDadosDocumentoExternoTeste($remetente, 'arquivo_extensao_nao_permitida.docx');

      // Cadastrar novo processo de teste
      $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);
      $this->cadastrarDocumentoExternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());    

      $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
      $objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();

      $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
      $objBlocoDeTramiteProtocoloFixtureDTO = $objBlocoDeTramiteProtocoloFixture->carregar([
        'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
        'IdBloco' => $objBlocoDeTramiteDTO->getNumId()
      ]);

      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
      $this->paginaCadastrarProcessoEmBloco->bntTramitarBloco();
      $this->paginaCadastrarProcessoEmBloco->tramitarProcessoExternamente(
        self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'],
        self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false,
        function ($testCase) {
          try {
              $testCase->frame('ifrEnvioProcesso');
              $mensagemSucesso = mb_convert_encoding('Processo(s) aguardando envio. Favor acompanhar a tramitação por meio do bloco, na funcionalidade \'Blocos de Trâmite Externo\'', 'UTF-8', 'ISO-8859-1');
              $testCase->assertStringContainsString($mensagemSucesso, $testCase->byCssSelector('body')->text());
              $btnFechar = $testCase->byXPath("//input[@id='btnFechar']");
              $btnFechar->click();
          } finally {
              try {
                  $testCase->frame(null);
                  $testCase->frame("ifrVisualizacao");
              } catch (Exception $e) {
              }
          }

          return true;
        }
      );

      $this->waitUntil(function ($testCase) use ($objProtocoloDTO) {
        sleep(5);
        $testCase->refresh();

        $colunaEstado = $testCase->elements($testCase->using('xpath')->value('//table[@id="tblBlocos"]/tbody/tr/td[3]'));
        $this->assertEquals(mb_convert_encoding("Concluído", 'UTF-8', 'ISO-8859-1'), $colunaEstado[0]->text());
        
        $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
        $objBlocoDeTramiteProtocolo = $objBlocoDeTramiteProtocoloFixture->buscar([
          'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
        ]);

        $this->assertEquals(9, $objBlocoDeTramiteProtocolo[0]->getNumIdAndamento());
        return true;
      }, PEN_WAIT_TIMEOUT);
    }

}
