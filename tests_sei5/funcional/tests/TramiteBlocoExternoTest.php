<?php

/**
 * Enviar bloco simples
 *
 * Execution Groups
 * @group execute_alone_group1
 */
class TramiteBlocoExternoTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;

    public function teste_tramite_bloco_externo()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        
        $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);
        $this->cadastrarDocumentoInternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());
        
        $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
        $objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();

        $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
        $objBlocoDeTramiteProtocoloFixtureDTO = $objBlocoDeTramiteProtocoloFixture->carregar([
            'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
            'IdBloco' => $objBlocoDeTramiteDTO->getNumId()
        ]);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

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
        sleep(1);

        $this->sairSistema();
    }
}