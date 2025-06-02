<?php

/**
 * Teste de tramite de processos em bloco
 *
 * Execution Groups
 * @group execute_alone_group1
 */
class TramiteBlocoExternoLimiteAnexoTest extends FixtureCenarioBaseTestCase
{
  protected static $numQtyProcessos = 2; // max: 99
  protected static $tramitar = false; // mude para false, caso queira rodar o script sem o tramite final

  public static $remetente;
  public static $destinatario;

  function setUp(): void 
    {
      parent::setUp();
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

  }

    /**
     * Teste inicial de trâmite de um processo contendo outro anexado
     *
     * @group envio
     * @large
     * 
     * @return void
     */
  public function test_tramitar_processo_anexado_da_origem()
    {
      // Definição de dados de teste do processo principal
      $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
      $documentoTeste = $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_pequeno_A.pdf');

      $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
      $objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();

    for ($i = 0; $i < self::$numQtyProcessos; $i++) {

        $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);
        $this->cadastrarDocumentoExternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());

        $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
        $objBlocoDeTramiteProtocoloFixtureDTO = $objBlocoDeTramiteProtocoloFixture->carregar([
            'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
            'IdBloco' => $objBlocoDeTramiteDTO->getNumId()
        ]);

    }

      $this->acessarSistema(
          self::$remetente['URL'],
          self::$remetente['SIGLA_UNIDADE'],
          self::$remetente['LOGIN'],
          self::$remetente['SENHA']
      );

      $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
    if (self::$tramitar == true) {
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
        sleep(10);
    } else {
        $this->paginaCadastrarProcessoEmBloco->bntVisualizarProcessos();
        $qtyProcessos = $this->paginaCadastrarProcessoEmBloco->retornarQuantidadeDeProcessosNoBloco();
            
        $this->assertEquals($qtyProcessos, self::$numQtyProcessos);
    }

      $this->sairSistema();
  }

  public function test_verificar_envio_processo()
    {      
      $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
      $this->paginaCadastrarProcessoEmBloco->bntVisualizarProcessos();

      $expectedQty = self::$numQtyProcessos;
      $statusTitle = self::$tramitar
          ? 'Concluído'
          : 'Em aberto';
      $cssSelector = sprintf(
          '#tblBlocos tbody tr td:nth-child(7) img[title="%s"]',
          $statusTitle
      );
        
      // aguarda até que o número de imagens de status seja o esperado
      $this->waitUntil(function() use ($cssSelector, $expectedQty) {
          // atualiza a tabela
          $this->paginaBase->refresh();
        
          // captura todas as imagens com o título correto na 7ª coluna
          $imgs = $this->paginaBase->elementsByCss($cssSelector);
        
          // continua esperando enquanto a quantidade não for a esperada
          return count($imgs) === $expectedQty;
      }, PEN_WAIT_TIMEOUT);
        
      // depois do wait, reafirma com assert para mensagens claras em caso de falha
      $imgs = $this->paginaBase->elementsByCss($cssSelector);
      $this->assertCount(
          $expectedQty,
          $imgs,
          "Esperava {$expectedQty} processos com status ?{$statusTitle}?, mas encontrou " . count($imgs)
      );
        
  }

  public function test_verificar_envio_tramite_em_bloco()
    {
      $this->acessarSistema(
          self::$remetente['URL'],
          self::$remetente['SIGLA_UNIDADE'],
          self::$remetente['LOGIN'],
          self::$remetente['SENHA']
      );
      $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
      $novoStatus = $this->paginaCadastrarProcessoEmBloco->retornarTextoColunaDaTabelaDeBlocos();

    if (self::$tramitar == true) {
        $this->assertEquals(mb_convert_encoding("Concluído", 'UTF-8', 'ISO-8859-1'), $novoStatus);
    } else {
        $this->assertEquals(mb_convert_encoding("Aberto", 'UTF-8', 'ISO-8859-1'), $novoStatus);
    }  

      $this->sairSistema();
  }
}