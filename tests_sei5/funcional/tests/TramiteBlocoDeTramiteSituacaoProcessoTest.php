<?php

/**
 *
 * Execution Groups
 * @group execute_parallel_group1
 */
class TramiteBlocoDeTramiteSituacaoProcessoTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $idsEmAndamento;

    /**
     * Teste pra validar mensagem de documento não assinado ao ser inserido em bloco
     *
     * @group envio
     * @large
     *
     * @return void
     */
    public function test_validar_situacao_do_processo_no_bloco()
    {
      self::$idsEmAndamento = [
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_ENVIADOS_REMETENTE,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_METADADOS_RECEBIDO_DESTINATARIO,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_ENVIADO_DESTINATARIO,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO        
      ];

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
        $this->assertEquals("Aguardando Processamento", $colunaEstado[0]->text());
        
        $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
        $objBlocoDeTramiteProtocoloFixtureDTO = $objBlocoDeTramiteProtocoloFixture->buscar([
          'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
        ])[0];

        $statusEmAndamento = in_array($objBlocoDeTramiteProtocoloFixtureDTO->getNumIdAndamento(), self::$idsEmAndamento);
        $this->assertTrue($statusEmAndamento);
        return true;
      }, PEN_WAIT_TIMEOUT);
    }

}