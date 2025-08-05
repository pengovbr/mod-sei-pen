<?php

use PHPUnit\Framework\Attributes\{Group,Large,Depends};

/**
 *
 * Execution Groups
 * #[Group('execute_parallel_group1')]
 */
class TramiteBlocoDeTramiteSituacaoProcessoConcluidoTest extends FixtureCenarioBaseTestCase
{
  public static $remetente;
  public static $destinatario;


    /**
     * Teste pra validar mensagem de documento não assinado ao ser inserido em bloco
     *
     * #[Group('envio')]
     * #[Large]
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
      function () {
        try {
            $this->paginaCadastrarProcessoEmBloco->frame('ifrEnvioProcesso');
            $mensagemSucesso = mb_convert_encoding('Processo(s) aguardando envio. Favor acompanhar a tramitação por meio do bloco, na funcionalidade \'Blocos de Trâmite Externo\'', 'UTF-8', 'ISO-8859-1');
            $this->assertStringContainsString($mensagemSucesso, $this->paginaCadastrarProcessoEmBloco->elByCss('body')->getText());
            $btnFechar = $this->paginaCadastrarProcessoEmBloco->elByXPath("//input[@id='btnFechar']");
            $btnFechar->click();
        } finally {
          try {
              $this->paginaCadastrarProcessoEmBloco->frame(null);
              $this->paginaCadastrarProcessoEmBloco->frame("ifrVisualizacao");
          } catch (Exception $e) {
          }
        }

        return true;
      }
    );

    $estadoEsperado = mb_convert_encoding('Concluído', 'UTF-8', 'ISO-8859-1');

    $this->waitUntil(function() use ($objProtocoloDTO, $estadoEsperado) {
      sleep(5);
      $this->paginaBase->refresh();

      $colunasEstado = $this->paginaCadastrarProcessoEmBloco->elementsByXPath('//table[@id="tblBlocos"]/tbody/tr/td[3]');
      // se não houver nenhuma célula, continua esperando
      if (count($colunasEstado) === 0) {
          return false;
      }
        
       // verifica se o texto da primeira célula contém o estado esperado
      if (mb_strpos($colunasEstado[0]->getText(), $estadoEsperado) === false) {
          return false;
      }
      $this->assertEquals($estadoEsperado, $colunasEstado[0]->getText());
        
      $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
      $objBlocoDeTramiteProtocolo = $objBlocoDeTramiteProtocoloFixture->buscar([
        'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
      ]);

      // precisa ter ao menos um resultado e o numIdAndamento ser 6
      if (empty($objBlocoDeTramiteProtocolo) || $objBlocoDeTramiteProtocolo[0]->getNumIdAndamento() !== 6) {
          return false;
      }
      return true;
    }, PEN_WAIT_TIMEOUT);

    $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
    $objBlocoDeTramiteProtocolo = $objBlocoDeTramiteProtocoloFixture->buscar([
      'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
    ]);

    $this->assertEquals(6, $objBlocoDeTramiteProtocolo[0]->getNumIdAndamento());
  }

    /**
     * Teste pra validar mensagem de documento não assinado ao ser inserido em bloco
     *
     * #[Group('envio')]
     * #[Large]
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
      function () {
        try {
            $this->paginaCadastrarProcessoEmBloco->frame('ifrEnvioProcesso');
            $mensagemSucesso = mb_convert_encoding('Processo(s) aguardando envio. Favor acompanhar a tramitação por meio do bloco, na funcionalidade \'Blocos de Trâmite Externo\'', 'UTF-8', 'ISO-8859-1');
            $this->assertStringContainsString($mensagemSucesso, $this->paginaCadastrarProcessoEmBloco->elByCss('body')->getText());
            $btnFechar = $this->paginaCadastrarProcessoEmBloco->elByXPath("//input[@id='btnFechar']");
            $btnFechar->click();
        } finally {
          try {
              $this->paginaCadastrarProcessoEmBloco->frame(null);
              $this->paginaCadastrarProcessoEmBloco->frame("ifrVisualizacao");
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
    $this->waitUntil(function() use ($objProtocoloDTO) {
      sleep(5);
      $this->paginaBase->refresh();
      $colunaEstado = $this->paginaBase->elementsByXPath('//table[@id="tblBlocos"]/tbody/tr/td[3]');
      $this->assertEquals(mb_convert_encoding("Concluído", 'UTF-8', 'ISO-8859-1'), $colunaEstado[0]->getText());
      
      return true;
    }, PEN_WAIT_TIMEOUT);

    $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
    $objBlocoDeTramiteProtocolo = $objBlocoDeTramiteProtocoloFixture->buscar([
      'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
    ]);

    $this->assertEquals(7, $objBlocoDeTramiteProtocolo[0]->getNumIdAndamento());
  }

    /**
     * Teste pra validar mensagem de documento não assinado ao ser inserido em bloco
     *
     * #[Group('envio')]
     * #[Large]
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
      function () {
        try {
            $this->paginaCadastrarProcessoEmBloco->frame('ifrEnvioProcesso');
            $mensagemSucesso = mb_convert_encoding('Processo(s) aguardando envio. Favor acompanhar a tramitação por meio do bloco, na funcionalidade \'Blocos de Trâmite Externo\'', 'UTF-8', 'ISO-8859-1');
            $this->assertStringContainsString($mensagemSucesso, $this->paginaCadastrarProcessoEmBloco->elByCss('body')->getText());
            $btnFechar = $this->paginaCadastrarProcessoEmBloco->elByXPath("//input[@id='btnFechar']");
            $btnFechar->click();
        } finally {
          try {
              $this->paginaCadastrarProcessoEmBloco->frame(null);
              $this->paginaCadastrarProcessoEmBloco->frame("ifrVisualizacao");
          } catch (Exception $e) {
          }
        }

        return true;
      }
    );

    $estadoEsperado = mb_convert_encoding('Concluído', 'UTF-8', 'ISO-8859-1');

    $this->waitUntil(function() use ($objProtocoloDTO, $estadoEsperado) {
      sleep(5);
      $this->paginaBase->refresh();

      $colunasEstado = $this->paginaCadastrarProcessoEmBloco->elementsByXPath('//table[@id="tblBlocos"]/tbody/tr/td[3]');
      // se não houver nenhuma célula, continua esperando
      if (count($colunasEstado) === 0) {
          return false;
      }
        
       // verifica se o texto da primeira célula contém o estado esperado
      if (mb_strpos($colunasEstado[0]->getText(), $estadoEsperado) === false) {
          return false;
      }
      $this->assertEquals($estadoEsperado, $colunasEstado[0]->getText());
        
      $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
      $objBlocoDeTramiteProtocolo = $objBlocoDeTramiteProtocoloFixture->buscar([
        'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
      ]);

      // precisa ter ao menos um resultado e o numIdAndamento ser 9
      if (empty($objBlocoDeTramiteProtocolo) || $objBlocoDeTramiteProtocolo[0]->getNumIdAndamento() !== 9) {
          return false;
      }

      // condição satisfeita
      return true;
    }, PEN_WAIT_TIMEOUT);

    // após a espera, reafirma a condição com assert
    $fixture = new \BlocoDeTramiteProtocoloFixture();
    $resultSet = $fixture->buscar([
        'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
    ]);
    $this->assertEquals(9, $resultSet[0]->getNumIdAndamento());

  }

}
