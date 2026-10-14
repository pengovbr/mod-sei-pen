<?php

use PHPUnit\Framework\Attributes\{Group,Large,Depends};

/**
 *
 * Execution Groups
 * #[Group('execute_parallel_group1')]
 */
class TramiteBlocoDeTramiteSituacaoProcessoTest extends FixtureCenarioBaseTestCase
{
  public static $remetente;
  public static $destinatario;
  public static $idsEmAndamento;

    /**
     * Teste para validar situação de processo ao ser inserido em bloco
     *
     * #[Group('envio')]
     * #[Large]
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
      self::$destinatario['REP_ESTRUTURAS'], 
      self::$destinatario['NOME_UNIDADE'],
      self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], 
      false,
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
      },
      PEN_WAIT_TIMEOUT,
      false
    );

    // Atualiza a página para refletir o envio
    $this->paginaBase->refresh();

    // Valida se o texto na tabela exibe "Aguardando Processamento"
    $colunaEstado = $this->paginaBase->elementsByXPath('//table[@id="tblBlocos"]/tbody/tr/td[3]');
    $this->assertEquals("Aguardando Processamento", $colunaEstado[0]->getText());

    // Valida que o protocolo foi vinculado corretamente ao bloco no banco de dados
    $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
    $objBlocoDeTramiteProtocoloFixtureDTO = $objBlocoDeTramiteProtocoloFixture->buscar([
      'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
    ])[0];

    $this->assertNotNull($objBlocoDeTramiteProtocoloFixtureDTO);
  }
}