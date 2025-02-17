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

        $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $testCase->refresh();
            $linhasDaTabela = $testCase->elements($testCase->using('xpath')->value('//table[@id="tblBlocos"]/tbody/tr'));

            $totalConcluidos = 0;
            foreach ($linhasDaTabela as $linha) {
                $statusTd = $linha->byXPath('./td[7]');
                if (self::$tramitar == true) {
                    $statusImg = $statusTd->byXPath(mb_convert_encoding("(//img[@title='Concluído'])", 'UTF-8', 'ISO-8859-1'));
                } else {
                    $statusImg = $statusTd->byXPath(mb_convert_encoding("(//img[@title='Em aberto'])", 'UTF-8', 'ISO-8859-1'));
                }
                $totalConcluidos++;
            }
            $this->assertEquals($totalConcluidos, self::$numQtyProcessos);
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

        if (self::$tramitar == true) {
            $this->assertEquals(mb_convert_encoding("Concluído", 'UTF-8', 'ISO-8859-1'), $novoStatus);
        } else {
            $this->assertEquals(mb_convert_encoding("Aberto", 'UTF-8', 'ISO-8859-1'), $novoStatus);
        }  

        $this->sairSistema();
    }
}