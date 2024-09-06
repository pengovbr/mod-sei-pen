<?php

use Tests\Funcional\Sei\Fixtures\{ProtocoloFixture,ProcedimentoFixture,AtividadeFixture,ParticipanteFixture,RelProtocoloAssuntoFixture,AtributoAndamentoFixture,DocumentoFixture,AssinaturaFixture};

/**
 * Teste de tramite de processos em bloco
 *
 * Execution Groups
 * @group execute_alone_group1
 */
class TramiteBlocoExternoLimiteTest extends CenarioBaseTestCase
{
    protected static $numQtyProcessos = 4; // max: 99
    protected static $tramitar = false; // mude para false, caso queira rodar o script sem o tramite final

    public static $remetente;
    public static $destinatario;
    public static $penOrgaoExternoId;
    public static $protocoloTestePrincipal;

    function setUp(): void 
    {
        parent::setUp();
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
    }

    public function teste_tramite_bloco_externo()
    {

        $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
        $objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();

        for ($i = 0; $i < self::$numQtyProcessos; $i++) {
            $objProtocoloFixture = new ProtocoloFixture();
            $objProtocoloFixtureDTO = $objProtocoloFixture->carregar([
                'Descricao' => 'teste'
            ]);

            $objProcedimentoFixture = new ProcedimentoFixture();
            $objProcedimentoDTO = $objProcedimentoFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo()
            ]);

            $objAtividadeFixture = new AtividadeFixture();
            $objAtividadeDTO = $objAtividadeFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo(),
                'IdTarefa' => TarefaRN::$TI_GERACAO_PROCEDIMENTO,
            ]);

            $objParticipanteFixture = new ParticipanteFixture();
            $objParticipanteFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo(),
                'IdContato' => 100000006,
            ]);

            $objProtocoloAssuntoFixture = new RelProtocoloAssuntoFixture();
            $objProtocoloAssuntoFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo()
            ]);

            $objAtributoAndamentoFixture = new AtributoAndamentoFixture();
            $objAtributoAndamentoFixture->carregar([
                'IdAtividade' => $objAtividadeDTO->getNumIdAtividade()
            ]);

            $objDocumentoFixture = new DocumentoFixture();
            $objDocumentoDTO = $objDocumentoFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo(),
                'IdProcedimento' => $objProcedimentoDTO->getDblIdProcedimento(),
            ]);

            $objAssinaturaFixture = new AssinaturaFixture();
            $objAssinaturaFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo(),
                'IdDocumento' => $objDocumentoDTO->getDblIdDocumento(),
            ]);

            $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
            $objBlocoDeTramiteProtocoloFixtureDTO = $objBlocoDeTramiteProtocoloFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo(),
                'IdBloco' => $objBlocoDeTramiteDTO->getNumId()
            ]);

            self::$protocoloTestePrincipal = $objProtocoloFixtureDTO->getStrProtocoloFormatado();
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
                function ($testCase) {
                  try {
                      $testCase->frame('ifrEnvioProcesso');
                      $mensagemSucesso = utf8_encode('Processo(s) aguardando envio. Favor acompanhar a tramitação por meio do bloco, na funcionalidade \'Blocos de Trâmite Externo\'');
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

        $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $testCase->refresh();
            $linhasDaTabela = $testCase->elements($testCase->using('xpath')->value('//table[@id="tblBlocos"]/tbody/tr'));

            $totalConcluidos = 0;
            foreach ($linhasDaTabela as $linha) {
                $statusTd = $linha->byXPath('./td[7]');
                if (self::$tramitar == true) {
                    $statusImg = $statusTd->byXPath(utf8_encode("(//img[@title='Concluído'])"));
                } else {
                    $statusImg = $statusTd->byXPath(utf8_encode("(//img[@title='Em aberto'])"));
                }
                $totalConcluidos++;
            }
            $this->assertEquals($totalConcluidos, self::$numQtyProcessos);
            return true;
        }, PEN_WAIT_TIMEOUT);
        
        sleep(5);

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
        $this->waitUntil(function ($testCase) {
          sleep(5);
          $testCase->refresh();
          $novoStatus = $this->paginaCadastrarProcessoEmBloco->retornarTextoColunaDaTabelaDeBlocos();
          $this->assertNotEquals('Aguardando Processamento', $novoStatus);
          return true;
        }, PEN_WAIT_TIMEOUT);
        
        $novoStatus = $this->paginaCadastrarProcessoEmBloco->retornarTextoColunaDaTabelaDeBlocos();
        $this->assertEquals(utf8_encode("Concluído"), $novoStatus);
      } else {
        $this->assertEquals("Aberto", $novoStatus);
      }

      $this->sairSistema();
    }
}