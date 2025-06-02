<?php

/**
 * Blocos não tramitados devem possuir estado "Aberto".
 * Blocos tramitados não podem ser mais alterados (processos excluídos).
 */
class TramiteProcessoBlocoDeTramitePermissoesTest extends FixtureCenarioBaseTestCase
{
  public static $remetente;
  public static $destinatario;
  public static $processoTeste;
  public static $documentoTeste1;
  public static $documentoTeste2;
  public static $protocoloTeste;
  protected static $numQtyProcessos = 1; // max: 99

    /**
     * Diminui tamanho de doc externo no ORG2 para obter processo com status recusado
     */
  public static function setUpBeforeClass(): void {
      parent::setUpBeforeClass();
      $bancoOrgaoB = new DatabaseUtils(CONTEXTO_ORGAO_B);    
      $bancoOrgaoB->execute("update infra_parametro set valor = ? where nome = ?", array(2, 'SEI_TAM_MB_DOC_EXTERNO'));

  }      
    
    /**
     * Volta tamanho de doc externo no ORG2 para default
     */
  public static function tearDownAfterClass(): void {

      $bancoOrgaoB = new DatabaseUtils(CONTEXTO_ORGAO_B);    
      $bancoOrgaoB->execute("update infra_parametro set valor = ? where nome = ?", array(50, 'SEI_TAM_MB_DOC_EXTERNO'));

  }

  function setUp(): void
    {
    parent::setUp();
    self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
    self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
  }

    /**
     * Tramite para obter posteriormente processo com status cancelado
     */
  public function test_tramite_contendo_documento_interno()
    {
      // Configuração do dados para teste do cenário
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
      self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      putenv("DATABASE_HOST=org2-database");

      self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
      self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

      $this->realizarTramiteExternoComValidacaoNoRemetenteFixture(self::$processoTeste, self::$documentoTeste1, self::$remetente, self::$destinatario);
      self::$protocoloTeste = self::$processoTeste["PROTOCOLO"];
  }


    /**
     * Troca ordenação de documento para ao tramitar obter status cancelado
     * @depends test_tramite_contendo_documento_interno
     */
  public function test_trocar_ordenacao_documento()
    {
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
      putenv("DATABASE_HOST=org1-database");

      // Definição de dados de teste
      self::$documentoTeste2 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

      $objProtocoloDTO = $this->consultarProcessoFixture(self::$protocoloTeste, \ProtocoloRN::$TP_PROCEDIMENTO);
      $this->cadastrarDocumentoInternoFixture(self::$documentoTeste2, $objProtocoloDTO->getDblIdProtocolo());
        
      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      $this->abrirProcesso(self::$protocoloTeste);

      // Validação dos dados do processo principal
    try {
        $listaDocumentosProcesso = $this->paginaProcesso->listarDocumentos();
        $this->assertEquals(2, count($listaDocumentosProcesso));
        $this->validarDadosDocumento($listaDocumentosProcesso[0], self::$documentoTeste1, self::$remetente);
        $this->validarDadosDocumento($listaDocumentosProcesso[1], self::$documentoTeste2, self::$remetente);
    } catch (Exception $e) {
        // Ignora a exceção se a imagem não for encontrada]
        print_r($listaDocumentosProcesso);
    }


      $this->paginaProcesso->selecionarProcesso();
      $this->paginaProcesso->navegarParaOrdenarDocumentos();
      sleep(1);
      $this->paginaProcesso->trocarOrdenacaoDocumentos();

      // Validação dos dados do processo principal
    try {
        $listaDocumentosProcesso = $this->paginaProcesso->listarDocumentos();
        $this->assertEquals(2, count($listaDocumentosProcesso));
        $this->validarDadosDocumento($listaDocumentosProcesso[1], self::$documentoTeste1, self::$remetente);
        $this->validarDadosDocumento($listaDocumentosProcesso[0], self::$documentoTeste2, self::$remetente);
    } catch (Exception $e) {
        // Ignora a exceção se a imagem não for encontrada]
        print_r($listaDocumentosProcesso);
    }
      sleep(1);
      $this->sairSistema();

  }

    /**
     * Inclui processos ao bloco de tramite, excluir e adiciona novamente
     * @depends test_trocar_ordenacao_documento
     */
  public function test_criar_excluir_processos_em_bloco_externo()
    {
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
      putenv("DATABASE_HOST=org1-database");

      // Definição de dados de teste
      $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
      $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

      $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
      $objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();

      $arrProtocolos = array();
      // cancelado
      $objProtocoloDTO = $this->consultarProcessoFixture(self::$protocoloTeste, \ProtocoloRN::$TP_PROCEDIMENTO);
      $arrProtocolos[] = $objProtocoloDTO;

    for ($i = 0; $i < self::$numQtyProcessos; $i++) {
        // sucesso
        $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);
        $this->cadastrarDocumentoInternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());
        $arrProtocolos[] = $objProtocoloDTO;

    }

      // recusado
      $documentoTeste3 = $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_003.pdf');
      $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);
      $this->cadastrarDocumentoExternoFixture($documentoTeste3, $objProtocoloDTO->getDblIdProtocolo());
      $arrProtocolos[] = $objProtocoloDTO;

      $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();

    foreach ($arrProtocolos as $objProtocoloDTO) {
        $objBlocoDeTramiteProtocoloFixtureDTO = $objBlocoDeTramiteProtocoloFixture->carregar([
            'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
            'IdBloco' => $objBlocoDeTramiteDTO->getNumId()
        ]);
    }

      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
      $this->paginaCadastrarProcessoEmBloco->bntVisualizarProcessos();
      sleep(1);
        
      // Executa remoção de protocolos do bloco e verifica status
      $this->paginaCadastrarProcessoEmBloco->btnSelecionarTodosProcessos();
      $this->paginaCadastrarProcessoEmBloco->btnComandoSuperiorExcluir();
      $qtdProcessoBloco = $this->paginaCadastrarProcessoEmBloco->retornarQuantidadeDeProcessosNoBloco();
      $this->assertEquals($qtdProcessoBloco, 0);
      $this->paginaCadastrarProcessoEmBloco->btnComandoSuperiorFechar();
      sleep(1);

      // $linhasDaTabela = $this->elements($this->using('xpath')->value('//table[@id="tblBlocos"]/tbody/tr'));

      $linhasDaTabela = $this->paginaCadastrarProcessoEmBloco->elementsByXPath('//table[@id="tblBlocos"]/tbody/tr');
    foreach ($linhasDaTabela as $linha) {
        // $numOrdem = $linha->byXPath('./td[2]')->text();
        $numOrdem = $linha->findElement(
            WebDriverBy::xpath('./td[2]')
        )->getText();
      if ($numOrdem == $objBlocoDeTramiteDTO->getNumOrdem()) {
        // $status = $linha->byXPath('./td[3]')->text();
        $status = $linha->findElement(
          WebDriverBy::xpath('./td[3]')
        )->getText();
        $this->assertEquals($status, 'Aberto');
        $this->assertEquals($objBlocoDeTramiteDTO->getStrStaEstado(), 'A');
        break;
      }
    }

      // Adiciona novamente protocolos ao bloco
    foreach ($arrProtocolos as $objProtocoloDTO) {
        $objBlocoDeTramiteProtocoloFixtureDTO = $objBlocoDeTramiteProtocoloFixture->carregar([
            'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
            'IdBloco' => $objBlocoDeTramiteDTO->getNumId()
        ]);
    }

      $this->sairSistema();
  }

    /**
     * Tramitar bloco externamente
     * @depends test_criar_excluir_processos_em_bloco_externo
     */
  public function test_tramite_bloco_externo()
    {
      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();        
      // Tramitar Bloco
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

  }


    /**
     * Verificar se o bloco foi enviado
     * @depends test_tramite_bloco_externo
     * @return void
     */
  public function test_verificar_envio_processo()
    {
      $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
      $this->paginaCadastrarProcessoEmBloco->bntVisualizarProcessos();

      $this->waitUntil(function() use (&$orgaosDiferentes) {
          sleep(5);
          $this->paginaBase->refresh();
          $selector = sprintf(
              '#tblBlocos tbody tr td:nth-child(7) img[title="%s"]',
              mb_convert_encoding('Concluído', 'UTF-8', 'ISO-8859-1')
          );
            
          // Captura todas as imagens de ?Concluído? na 7ª coluna
          $imgsConcluidos = $this->paginaBase->elementsByCss($selector);
            
          // Total de processos concluídos é simplesmente a quantidade de imgs encontradas
          $totalConcluidos = count($imgsConcluidos);
        try {
            $this->assertEquals($totalConcluidos, self::$numQtyProcessos);
            return true;
        } catch (AssertionFailedError $e) {
            return false;
        }
      }, PEN_WAIT_TIMEOUT);
        
      $this->sairSistema();
  }
    /**
     * Verificar se é possivel excluir processos do bloco após tramite
     * @depends test_verificar_envio_processo
     * @return void
     */
  public function test_verificar_possivel_exclusao_processo_bloco()
    {
      $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
      $this->paginaCadastrarProcessoEmBloco->bntVisualizarProcessos();
      $qtdProcessoBloco = $this->paginaCadastrarProcessoEmBloco->retornarQuantidadeDeProcessosNoBloco();
        
      $this->paginaCadastrarProcessoEmBloco->btnSelecionarTodosProcessos();
      $this->paginaCadastrarProcessoEmBloco->btnComandoSuperiorExcluir();
      $qtdProcessoBlocoPos = $this->paginaCadastrarProcessoEmBloco->retornarQuantidadeDeProcessosNoBloco(); 

      $this->assertEquals($qtdProcessoBloco, $qtdProcessoBlocoPos);

      sleep(2);

      $this->sairSistema();
  }

}
