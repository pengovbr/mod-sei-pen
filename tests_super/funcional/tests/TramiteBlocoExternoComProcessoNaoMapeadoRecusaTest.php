<?php

class TramiteBlocoExternoComProcessoNaoMapeadoRecusaTest extends FixtureCenarioBaseTestCase
{
  public static $remetente;
  public static $destinatario;

  public static $processoTeste1;
  public static $objProtocoloDTO1;
  public static $documentoTeste1;

  public static $processoTeste2;
  public static $objProtocoloDTO2;
  public static $documentoTeste2;

  public static $processoTeste3;
  public static $objProtocoloDTO3;
  public static $documentoTeste3;

  public static $objBlocoDeTramiteDTO1;
  public static $objBlocoDeTramiteDTO2;

  /**
   * @inheritdoc
   * @return void
   */
  function setUp(): void
  {
    parent::setUp();

    putenv("DATABASE_HOST=org2-database");
    $objTipoProcessoPadraoFixture = new \TipoProcessoPadraoFixture();
    $objTipoProcessoPadraoFixture->carregar([
      'Nome' => 'PEN_TIPO_PROCESSO_EXTERNO',
      'Valor' => null
    ]);
    putenv("DATABASE_HOST=org1-database");
  }

  public function test_tramite_bloco_externo_com_processo_nao_mapeado()
  {
    // Configuração do dados para teste do cenário
    self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
    self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

    $this->prepararCenariosFixtures();

    // Acesso ao sistema
    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
    $this->paginaCadastrarProcessoEmBloco->bntTramitarBloco();
    $this->paginaCadastrarProcessoEmBloco->tramitarProcessoExternamente(
      self::$destinatario['REP_ESTRUTURAS'],
      self::$destinatario['NOME_UNIDADE'],
      self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
      false,
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

    // Saída do sistema
    $this->sairSistema();
  }

  public function test_verificar_envio_processo()
  {
    $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

    $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
    $this->paginaCadastrarProcessoEmBloco->bntVisualizarProcessos();

    $this->waitUntil(function ($testCase) {
      sleep(5);
      $testCase->refresh();
      $linhasDaTabela = $testCase->elements($testCase->using('xpath')->value('//table[@id="tblBlocos"]/tbody/tr'));

      $totalEmProcessamento = 0;
      foreach ($linhasDaTabela as $linha) {
        $statusTd = $linha->byXPath('./td[7]');
        try {
            $statusImg = $statusTd->byXPath(mb_convert_encoding(".//img[@title='Aguardando Processamento']", 'UTF-8', 'ISO-8859-1'));
            if ($statusImg){
                $totalEmProcessamento++;
            }
        } catch (Exception $e) {
            // Ignora a exceção se a imagem não for encontrada
        }
      }
      $this->assertEquals($totalEmProcessamento,0); // Todos processos enviados
      return true;
    }, PEN_WAIT_TIMEOUT);

    sleep(5);
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
    $this->assertEquals(mb_convert_encoding("Concluído", 'UTF-8', 'ISO-8859-1'), $novoStatus);

    $this->paginaCadastrarProcessoEmBloco->bntVisualizarProcessos();

    $quantidadeLinhasRecusadas = $this->paginaCadastrarProcessoEmBloco->buscarQuantidadeProcessosRecusados();

    $this->assertEquals($quantidadeLinhasRecusadas, 1);

    // Saída do sistema
    $this->sairSistema();
  }

  public function test_incluir_processo_recusado_em_novo_bloco()
  {
    // Carregar dados do bloco de trâmite
    $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
    self::$objBlocoDeTramiteDTO2 = $objBlocoDeTramiteFixture->carregar();

    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    $this->paginaBase->navegarParaControleProcesso();

    // Seleção do processo e do bloco de trâmite
    $protocoloFormatado = self::$objProtocoloDTO3->getStrProtocoloFormatado();
    $this->paginaTramiteEmBloco->selecionarProcesso($protocoloFormatado);
    $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();

    // Verificação do título da página
    $titulo = mb_convert_encoding("Incluir Processo(s) no Bloco de Trâmite", 'UTF-8', 'ISO-8859-1');
    $tituloRetorno = $this->paginaTramiteEmBloco->verificarTituloDaPagina($titulo);
    $this->assertEquals($titulo, $tituloRetorno);

    // Inclusão do processo no bloco de trâmite
    $this->paginaTramiteEmBloco->selecionarBloco(self::$objBlocoDeTramiteDTO2->getNumId());
    $this->paginaTramiteEmBloco->clicarSalvar();

    // Espera para a mensagem de sucesso aparecer
    sleep(2);
    $mensagem = $this->paginaTramiteEmBloco->buscarMensagemAlerta();
    $this->assertStringContainsString(
      mb_convert_encoding('Processo(s) incluído(s) com sucesso no bloco ' . self::$objBlocoDeTramiteDTO2->getNumOrdem(), 'UTF-8', 'ISO-8859-1'),
      $mensagem
    );

    // Saída do sistema
    $this->sairSistema();
  }

  private function prepararCenariosFixtures()
  {
    // Carregar dados do bloco de trâmite
    $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
    $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
    self::$objBlocoDeTramiteDTO1 = $objBlocoDeTramiteFixture->carregar();

    // Geração dos dados para o processo e documento de teste 1
    self::$processoTeste1 = $this->gerarDadosProcessoTeste(self::$remetente);
    self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
    // Cadastro do processo e documento 1
    self::$objProtocoloDTO1 = $this->cadastrarProcessoFixture(self::$processoTeste1);
    $this->cadastrarDocumentoInternoFixture(self::$documentoTeste1, self::$objProtocoloDTO1->getDblIdProtocolo());
    $objBlocoDeTramiteProtocoloFixture->carregar([
      'IdProtocolo' => self::$objProtocoloDTO1->getDblIdProtocolo(),
      'IdBloco' => self::$objBlocoDeTramiteDTO1->getNumId()
    ]);

    // Geração dos dados para o processo e documento de teste 2
    self::$processoTeste2 = $this->gerarDadosProcessoTeste(self::$remetente);
    self::$documentoTeste2 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

    // Cadastro do processo e documento 2
    self::$objProtocoloDTO2 = $this->cadastrarProcessoFixture(self::$processoTeste2);
    $this->cadastrarDocumentoInternoFixture(self::$documentoTeste2, self::$objProtocoloDTO2->getDblIdProtocolo());
    $objBlocoDeTramiteProtocoloFixture->carregar([
      'IdProtocolo' => self::$objProtocoloDTO2->getDblIdProtocolo(),
      'IdBloco' => self::$objBlocoDeTramiteDTO1->getNumId()
    ]);

    // Geração dos dados para o processo e documento de teste 3 recusa
    self::$processoTeste3 = $this->gerarDadosProcessoTeste(self::$remetente);
    self::$documentoTeste3 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

    $objTipoProcedimentoDTO = $this->cadastrarTipoProcedimentoFixture([
      'NOME' => 'Recusa: Teste Funcional',
    ]);
    self::$processoTeste3['ID_TIPO_PROCESSO'] = $objTipoProcedimentoDTO->getNumIdTipoProcedimento();

    // Cadastro do processo e documento 3 recusa
    self::$objProtocoloDTO3 = $this->cadastrarProcessoFixture(self::$processoTeste3);
    $this->cadastrarDocumentoInternoFixture(self::$documentoTeste3, self::$objProtocoloDTO3->getDblIdProtocolo());
    $objBlocoDeTramiteProtocoloFixture->carregar([
      'IdProtocolo' => self::$objProtocoloDTO3->getDblIdProtocolo(),
      'IdBloco' => self::$objBlocoDeTramiteDTO1->getNumId()
    ]);
  }

  public static function tearDownAfterClass(): void
  {
    putenv("DATABASE_HOST=org2-database");
    parent::tearDownAfterClass();
    $objTipoProcessoPadraoFixture = new \TipoProcessoPadraoFixture();
    $objTipoProcessoPadraoFixture->carregar([
      'Nome' => 'PEN_TIPO_PROCESSO_EXTERNO',
      'Valor' => '100000256'
    ]);
    putenv("DATABASE_HOST=org1-database");
  }
}
