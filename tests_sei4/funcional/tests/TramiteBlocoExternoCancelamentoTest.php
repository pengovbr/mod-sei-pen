<?php

/**
 * Classe de testes para o cancelamento de tr�mite de processos em bloco externo.
 *
 * Esta classe realiza testes para garantir que o cancelamento de tr�mite de processos 
 * enviados em blocos externos seja executado corretamente. Al�m disso, testa se o 
 * status do bloco � atualizado ap�s o cancelamento.
 */
class TramiteBlocoExternoCancelamentoTest extends FixtureCenarioBaseTestCase
{
  public static $remetente;
  public static $destinatario;

  /**
   * Configura o cen�rio de teste.
   *
   * M�todo executado antes de cada teste, configurando o contexto de remetente e destinat�rio.
   */
  function setUp(): void
  {
    parent::setUp();

    // Define o contexto do remetente para o teste usando uma constante pr�-configurada.
    self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
    // Define o contexto do destinat�rio para o teste usando uma constante pr�-configurada.
    self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
  }

  /**
   * Testa o cancelamento de tr�mite em bloco externo para destinat�rio n�o recebido.
   *
   * Verifica se � poss�vel cancelar a tramita��o externa de um processo em bloco e valida
   * as mensagens de sucesso ap�s a a��o.
   *
   * @return void
   */
  public function test_cancelar_tramite_em_bloco_externo_nao_recebido_destinatario()
  {
    // Desativa os agendamentos autom�ticos, permitindo que o cancelamento possa ocorrer.
    $this->desativarReativarAgendamentos('N');

    // Gera um processo de teste com os dados do remetente.
    $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
    // Gera um documento interno para o teste com os dados do remetente.
    $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

    // Cadastra o processo de teste na fixture e obt�m o objeto de protocolo.
    $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);
    // Cadastra o documento interno de teste relacionado ao protocolo do processo.
    $this->cadastrarDocumentoInternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());

    // Carrega um novo bloco de tr�mite para uso no teste.
    $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
    $objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();

    // Associa o protocolo ao bloco de tr�mite carregado.
    $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
    $objBlocoDeTramiteProtocoloFixture->carregar([
      'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(), // Define o ID do protocolo.
      'IdBloco' => $objBlocoDeTramiteDTO->getNumId() // Define o ID do bloco de tr�mite.
    ]);

    // Acessa o sistema com as credenciais do remetente.
    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    // Navega para a listagem de blocos de tr�mite na p�gina do sistema.
    $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
    // Clica no bot�o para tramitar o bloco.
    $this->paginaCadastrarProcessoEmBloco->bntTramitarBloco();
    
    // Inicia a tramita��o externa do processo configurando os dados do destinat�rio.
    $this->paginaCadastrarProcessoEmBloco->tramitarProcessoExternamente(
      self::$destinatario['REP_ESTRUTURAS'],
      self::$destinatario['NOME_UNIDADE'],
      self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
      false,
      function ($testCase) {
        try {
          // Acessa o frame da interface de envio de processos.
          $testCase->frame('ifrEnvioProcesso');
          // Define a mensagem de sucesso esperada no processo de tramita��o.
          $mensagemSucesso = mb_convert_encoding('Processo(s) aguardando envio. Favor acompanhar a tramita��o por meio do bloco, na funcionalidade \'Blocos de Tr�mite Externo\'', 'UTF-8', 'ISO-8859-1');
          // Valida se a mensagem de sucesso est� presente no corpo da p�gina.
          $testCase->assertStringContainsString($mensagemSucesso, $testCase->byCssSelector('body')->text());
          // Localiza e clica no bot�o para fechar a janela de tramita��o.
          $btnFechar = $testCase->byXPath("//input[@id='btnFechar']");
          $btnFechar->click();
        } finally {
          try {
            // Retorna ao frame principal e ent�o ao frame de visualiza��o.
            $testCase->frame(null);
            $testCase->frame("ifrVisualizacao");
          } catch (Exception $e) {
            // Ignora erros na tentativa de manipular frames.
          }
        }

        return true;
      }
    );

    // Navega para a p�gina de controle de processos.
    $this->paginaBase->navegarParaControleProcesso();
    // Pesquisa o processo tramitado usando o n�mero do protocolo formatado.
    $this->paginaProcesso->pesquisar($objProtocoloDTO->getStrProtocoloFormatado());
    // Realiza o cancelamento da tramita��o externa do processo.
    $this->paginaProcesso->cancelarTramitacaoExterna();

    // Obt�m e fecha o alerta exibido ap�s o cancelamento.
    $mensagemAlerta = $this->paginaTramitar->alertTextAndClose(true);
    // Define a mensagem esperada ap�s o cancelamento da tramita��o.
    $mensagemEsperada = mb_convert_encoding("O tr�mite externo do processo foi cancelado com sucesso!", 'UTF-8', 'ISO-8859-1');
    // Verifica se a mensagem de alerta cont�m a mensagem esperada.
    $this->assertStringContainsString($mensagemEsperada, $mensagemAlerta);
    // Verifica se o processo n�o est� bloqueado ap�s o cancelamento.
    $this->assertFalse($this->paginaProcesso->processoBloqueado());
    // Verifica se o processo est� aberto ap�s o cancelamento.
    $this->assertTrue($this->paginaProcesso->processoAberto());

    // Encerra a sess�o no sistema.
    $this->sairSistema();

    // Reativa os agendamentos autom�ticos ap�s o teste.
    $this->desativarReativarAgendamentos('S');
  }

  /**
   * Testa a valida��o do status de um bloco ap�s o cancelamento.
   *
   * Verifica se o status do bloco � atualizado corretamente para "Conclu�do" 
   * ap�s o cancelamento da tramita��o.
   *
   * @return void
   */
  public function test_validar_status_bloco_cancelado_concluido()
  {
    // Acessa o sistema com as credenciais do remetente.
    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    // Navega para a listagem de blocos de tr�mite.
    $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
    // Aguarda at� que o estado do bloco seja atualizado para "Conclu�do".
    $this->waitUntil(function ($testCase) {
      // Espera alguns segundos antes de atualizar a p�gina.
      sleep(3);
      $testCase->refresh();

      // Localiza a coluna de estado e verifica se o valor � "Conclu�do".
      $colunaEstado = $testCase->elements($testCase->using('xpath')->value('//table[@id="tblBlocos"]/tbody/tr/td[3]'));
      $this->assertEquals(mb_convert_encoding("Conclu�do", 'UTF-8', 'ISO-8859-1'), $colunaEstado[0]->text());
      
      return true;
    }, PEN_WAIT_TIMEOUT);

    // Encerra a sess�o no sistema.
    $this->sairSistema();
  }

  /**
   * Reativa ou desativa agendamentos conforme o par�metro.
   *
   * Configura os agendamentos de tarefas de envio e recebimento de tr�mite, 
   * conforme o par�metro de entrada.
   *
   * @param string $sinAtivo Define o status dos agendamentos ('S' para ativo, 'N' para inativo)
   */
  private function desativarReativarAgendamentos($sinAtivo = 'S')
  {
    $this->desativarReativarAgendamentoTarefas([
      'COMANDO' => 'PENAgendamentoRN::processarTarefasEnvioPEN',
      'SIN_ATIVO' => $sinAtivo
    ]);

    $this->desativarReativarAgendamentoTarefas([
      'COMANDO' => 'PENAgendamentoRN::processarTarefasRecebimentoPEN',
      'SIN_ATIVO' => $sinAtivo
    ]);
  }
}
