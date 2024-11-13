<?php

/**
 * Classe de testes para o cancelamento de trâmite de processos em bloco externo.
 *
 * Esta classe realiza testes para garantir que o cancelamento de trâmite de processos 
 * enviados em blocos externos seja executado corretamente. Além disso, testa se o 
 * status do bloco é atualizado após o cancelamento.
 */
class TramiteBlocoExternoCancelamentoTest extends FixtureCenarioBaseTestCase
{
  public static $remetente;
  public static $destinatario;

  /**
   * Configura o cenário de teste.
   *
   * Método executado antes de cada teste, configurando o contexto de remetente e destinatário.
   */
  function setUp(): void
  {
    parent::setUp();

    // Define o contexto do remetente para o teste usando uma constante pré-configurada.
    self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
    // Define o contexto do destinatário para o teste usando uma constante pré-configurada.
    self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
  }

  /**
   * Testa o cancelamento de trâmite em bloco externo para destinatário não recebido.
   *
   * Verifica se é possível cancelar a tramitação externa de um processo em bloco e valida
   * as mensagens de sucesso após a ação.
   *
   * @return void
   */
  public function test_cancelar_tramite_em_bloco_externo_nao_recebido_destinatario()
  {
    // Desativa os agendamentos automáticos, permitindo que o cancelamento possa ocorrer.
    $this->desativarReativarAgendamentos('N');

    // Gera um processo de teste com os dados do remetente.
    $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
    // Gera um documento interno para o teste com os dados do remetente.
    $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

    // Cadastra o processo de teste na fixture e obtém o objeto de protocolo.
    $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);
    // Cadastra o documento interno de teste relacionado ao protocolo do processo.
    $this->cadastrarDocumentoInternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());

    // Carrega um novo bloco de trâmite para uso no teste.
    $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
    $objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();

    // Associa o protocolo ao bloco de trâmite carregado.
    $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
    $objBlocoDeTramiteProtocoloFixture->carregar([
      'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(), // Define o ID do protocolo.
      'IdBloco' => $objBlocoDeTramiteDTO->getNumId() // Define o ID do bloco de trâmite.
    ]);

    // Acessa o sistema com as credenciais do remetente.
    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    // Navega para a listagem de blocos de trâmite na página do sistema.
    $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
    // Clica no botão para tramitar o bloco.
    $this->paginaCadastrarProcessoEmBloco->bntTramitarBloco();
    
    // Inicia a tramitação externa do processo configurando os dados do destinatário.
    $this->paginaCadastrarProcessoEmBloco->tramitarProcessoExternamente(
      self::$destinatario['REP_ESTRUTURAS'],
      self::$destinatario['NOME_UNIDADE'],
      self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
      false,
      function ($testCase) {
        try {
          // Acessa o frame da interface de envio de processos.
          $testCase->frame('ifrEnvioProcesso');
          // Define a mensagem de sucesso esperada no processo de tramitação.
          $mensagemSucesso = mb_convert_encoding('Processo(s) aguardando envio. Favor acompanhar a tramitação por meio do bloco, na funcionalidade \'Blocos de Trâmite Externo\'', 'UTF-8', 'ISO-8859-1');
          // Valida se a mensagem de sucesso está presente no corpo da página.
          $testCase->assertStringContainsString($mensagemSucesso, $testCase->byCssSelector('body')->text());
          // Localiza e clica no botão para fechar a janela de tramitação.
          $btnFechar = $testCase->byXPath("//input[@id='btnFechar']");
          $btnFechar->click();
        } finally {
          try {
            // Retorna ao frame principal e então ao frame de visualização.
            $testCase->frame(null);
            $testCase->frame("ifrVisualizacao");
          } catch (Exception $e) {
            // Ignora erros na tentativa de manipular frames.
          }
        }

        return true;
      }
    );

    // Navega para a página de controle de processos.
    $this->paginaBase->navegarParaControleProcesso();
    // Pesquisa o processo tramitado usando o número do protocolo formatado.
    $this->paginaProcesso->pesquisar($objProtocoloDTO->getStrProtocoloFormatado());
    // Realiza o cancelamento da tramitação externa do processo.
    $this->paginaProcesso->cancelarTramitacaoExterna();

    // Obtém e fecha o alerta exibido após o cancelamento.
    $mensagemAlerta = $this->paginaTramitar->alertTextAndClose(true);
    // Define a mensagem esperada após o cancelamento da tramitação.
    $mensagemEsperada = mb_convert_encoding("O trâmite externo do processo foi cancelado com sucesso!", 'UTF-8', 'ISO-8859-1');
    // Verifica se a mensagem de alerta contém a mensagem esperada.
    $this->assertStringContainsString($mensagemEsperada, $mensagemAlerta);
    // Verifica se o processo não está bloqueado após o cancelamento.
    $this->assertFalse($this->paginaProcesso->processoBloqueado());
    // Verifica se o processo está aberto após o cancelamento.
    $this->assertTrue($this->paginaProcesso->processoAberto());

    // Encerra a sessão no sistema.
    $this->sairSistema();

    // Reativa os agendamentos automáticos após o teste.
    $this->desativarReativarAgendamentos('S');
  }

  /**
   * Testa a validação do status de um bloco após o cancelamento.
   *
   * Verifica se o status do bloco é atualizado corretamente para "Concluído" 
   * após o cancelamento da tramitação.
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

    // Navega para a listagem de blocos de trâmite.
    $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
    // Aguarda até que o estado do bloco seja atualizado para "Concluído".
    $this->waitUntil(function ($testCase) {
      // Espera alguns segundos antes de atualizar a página.
      sleep(3);
      $testCase->refresh();

      // Localiza a coluna de estado e verifica se o valor é "Concluído".
      $colunaEstado = $testCase->elements($testCase->using('xpath')->value('//table[@id="tblBlocos"]/tbody/tr/td[3]'));
      $this->assertEquals(mb_convert_encoding("Concluído", 'UTF-8', 'ISO-8859-1'), $colunaEstado[0]->text());
      
      return true;
    }, PEN_WAIT_TIMEOUT);

    // Encerra a sessão no sistema.
    $this->sairSistema();
  }

  /**
   * Reativa ou desativa agendamentos conforme o parâmetro.
   *
   * Configura os agendamentos de tarefas de envio e recebimento de trâmite, 
   * conforme o parâmetro de entrada.
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
