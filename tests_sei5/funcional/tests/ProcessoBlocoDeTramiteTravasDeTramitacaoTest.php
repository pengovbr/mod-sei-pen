<?php

/**
 * Testes de mapeamento de envio de envio parcial
 */
class ProcessoBlocoDeTramiteTravasDeTramitacaoTest extends FixtureCenarioBaseTestCase
{
  public static $remetente;
  public static $destinatario;
  public static $objProtocoloDTO;

  /**
   * Teste inicial que gera processsos com documentos assinados e bloco, em seguida move para unidade secundaria mantendo-o aberto na atual
   * e tenta executar o tramite em bloco para receber a mensagem de erro
   *
   * @group mapeamento
   *
   * @return void
   */
  public function test_validar_tramite_bloco_mensagem_multi_unidade()
  {
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

      $processoTeste = $this->gerarDadosProcessoTeste($remetente);
      $documentoTeste = $this->gerarDadosDocumentoInternoTeste($remetente);

      // Cadastrar novo processo de teste e documento
      self::$objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);
      $this->cadastrarDocumentoInternoFixture($documentoTeste, self::$objProtocoloDTO->getDblIdProtocolo());

      // Cadastrar novo bloco de tramite e insere protocolo
      $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
      $objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();

      $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
      $objBlocoDeTramiteProtocoloFixtureDTO = $objBlocoDeTramiteProtocoloFixture->carregar([
        'IdProtocolo' => self::$objProtocoloDTO->getDblIdProtocolo(),
        'IdBloco' => $objBlocoDeTramiteDTO->getNumId()
      ]);

      // Acessar sistema do this->REMETENTE do processo
      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      // Abre processo e tramita internamente ele para a unidade secundária, deixando ele aberto na atual
      $this->abrirProcesso(self::$objProtocoloDTO->getStrProtocoloFormatado());
      $this->tramitarProcessoInternamente(self::$remetente['SIGLA_UNIDADE_SECUNDARIA'], true);

      $this->paginaBase->navegarParaControleProcesso();
      $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
      $this->paginaCadastrarProcessoEmBloco->bntTramitarBloco();
      try {
        $this->paginaCadastrarProcessoEmBloco->tramitarProcessoExternamente(
            self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);
      } catch (Exception $ex) {
        $this->assertStringContainsString(
            utf8_encode('Não é possível tramitar um processo aberto em mais de uma unidade.'),
            $ex->getMessage()
        );
      }
  }

  /**
   * Teste seguinte que finaliza o processo na unidade atual deixando-o aberto apenas na unidade secundaria
   * e tenta executar o tramite em bloco para receber a mensagem de erro
   * 
   * @group mapeamento
   *
   * @return void
   */
  public function test_validar_tramite_bloco_mensagem_nao_possui_andamento_aberto()
  {
      // Acessar sistema do this->REMETENTE do processo
      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      // Abre e conclui processo na unidade atual, desta forma ficando aberto apenas na unidade secundaria
      $this->abrirProcesso(self::$objProtocoloDTO->getStrProtocoloFormatado());
      $this->paginaProcesso->concluirProcesso();

      $this->paginaBase->navegarParaControleProcesso();
      $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
      $this->paginaCadastrarProcessoEmBloco->bntTramitarBloco();
      try {
        $this->paginaCadastrarProcessoEmBloco->tramitarProcessoExternamente(
            self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);
      } catch (Exception $ex) {
        $this->assertStringContainsString(
            utf8_encode('O processo ' . self::$objProtocoloDTO->getStrProtocoloFormatado() . ' não possui andamento aberto nesta unidade'),
            $ex->getMessage()
        );
      }
  }

}