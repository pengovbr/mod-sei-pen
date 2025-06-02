<?php

/**
 * Teste de tramite de processos em bloco
 *
 * Execution Groups
 * @group execute_alone_group1
 */
class TramiteBlocoExternoLimiteTest extends FixtureCenarioBaseTestCase
{
  protected static $numQtyProcessos = 4; // max: 99
  protected static $tramitar = false; // mude para false, caso queira rodar o script sem o tramite final

  public static $remetente;
  public static $destinatario;

  function setUp(): void 
    {
      parent::setUp();
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
  }

  public function test_tramite_bloco_externo()
    {
      // Definição de dados de teste do processo principal
      $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
      $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

      $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
      $objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();

    for ($i = 0; $i < self::$numQtyProcessos; $i++) {

        $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);
        $this->cadastrarDocumentoInternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());

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
        sleep(5);

    } else {
        $this->paginaCadastrarProcessoEmBloco->bntVisualizarProcessos();
        $qtyProcessos = $this->paginaCadastrarProcessoEmBloco->retornarQuantidadeDeProcessosNoBloco();
            
        $this->assertEquals($qtyProcessos, self::$numQtyProcessos);
    }

      $this->sairSistema();
  }

     /**
     * Verificar se o bloco foi enviado
     *
     *
     * @return void
     */
  public function test_verificar_envio_processo()
    {
      $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
      $this->paginaCadastrarProcessoEmBloco->bntVisualizarProcessos();
      // Define título de status e quantidade esperada
      $statusTitle  = self::$tramitar ? 'Concluído' : 'Em aberto';
      $expectedQty  = self::$numQtyProcessos;
      $cssSelector  = sprintf(
          '#tblBlocos tbody tr td:nth-child(7) img[title="%s"]',
          $statusTitle
      );

      // Aguarda até que apareça exatamente a quantidade esperada de ícones
      $this->waitUntil(function() use ($cssSelector, $expectedQty) {
          // Atualiza a tabela
          $this->paginaBase->refresh();

          // Captura todos os imgs na 7ª coluna com o title correto
          $imgs = $this->paginaBase->elementsByCss($cssSelector);

          // Continua esperando enquanto a quantidade não bater
          return count($imgs) === $expectedQty;
      }, PEN_WAIT_TIMEOUT);

      // Depois do wait, reafirma com assert para mensagem de erro clara
      $imgs = $this->paginaBase->elementsByCss($cssSelector);
      $this->assertCount(
          $expectedQty,
          $imgs,
          "Esperava {$expectedQty} processos com status \"{$statusTitle}\", mas encontrou " . count($imgs)
      );
        
      $this->sairSistema();
  }

    /**
     * Verificar atualização do bloco
     *
     *
     * @return void
     */
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
      $this->waitUntil(function() {
        sleep(5);
        $this->paginaBase->refresh();
        $novoStatus = $this->paginaCadastrarProcessoEmBloco->retornarTextoColunaDaTabelaDeBlocos();
        $this->assertNotEquals('Aguardando Processamento', $novoStatus);
        return true;
      }, PEN_WAIT_TIMEOUT);
        
      $novoStatus = $this->paginaCadastrarProcessoEmBloco->retornarTextoColunaDaTabelaDeBlocos();
      $this->assertEquals(mb_convert_encoding("Concluído", 'UTF-8', 'ISO-8859-1'), $novoStatus);
    } else {
      $this->assertEquals("Aberto", $novoStatus);
    }

    $this->sairSistema();
  }
}