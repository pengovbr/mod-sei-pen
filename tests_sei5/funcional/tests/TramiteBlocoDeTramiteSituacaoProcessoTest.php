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
  public static $estadosValidosAposDespacho = ['Aguardando Processamento', 'Concluído'];

    /**
     * Teste pra validar mensagem de documento não assinado ao ser inserido em bloco
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
      // Estado de sucesso terminal: o remetente recebeu o recibo de conclusao.
      // Faltava na lista, que so previa estados intermediarios (1 a 5) e a recusa
      // (8). Como o ambiente conclui o tramite em segundos, o teste quase sempre
      // observa o processo JA concluido - e reprovava o proprio caminho feliz.
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE,
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
      true
    );

    $this->waitUntil(function() use ($objProtocoloDTO) {
      $this->paginaBase->refresh();
      $colunaEstado = $this->paginaBase->elementsByXPath('//table[@id="tblBlocos"]/tbody/tr/td[3]');
      // A coluna mostra estado TRANSITORIO: "Aguardando Processamento" enquanto a
      // pendencia nao foi processada e "Concluido" depois. Fixar um dos dois deixa
      // o teste dependente da velocidade do processamento - e, por ser assercao
      // dentro do waitUntil, ela ABORTA a espera em vez de repetir (WebDriverWait
      // repete o RETORNO da closure, nao sobrevive a uma excecao de assercao).
      // Os demais estados - Aberto, Concluido Parcialmente e Retornado - continuam
      // reprovando, entao o teste nao perde poder de deteccao.
      // getText() devolve UTF-8; a fonte deste arquivo e ISO-8859-1. Sem converter,
      // "Concluído" nunca casa. Mesmo idioma usado na mensagem de sucesso acima.
      $arrEstadosValidos = array_map(
          function ($str) { return mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1'); },
          self::$estadosValidosAposDespacho
      );
      $this->assertContains($colunaEstado[0]->getText(), $arrEstadosValidos);
      $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
      $objBlocoDeTramiteProtocoloFixtureDTO = $objBlocoDeTramiteProtocoloFixture->buscar([
        'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
      ])[0];

      if (in_array($objBlocoDeTramiteProtocoloFixtureDTO->getNumIdAndamento(), self::$idsEmAndamento)) {
        return true;
      }
    }, PEN_WAIT_TIMEOUT);
    $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
    $objBlocoDeTramiteProtocoloFixtureDTO = $objBlocoDeTramiteProtocoloFixture->buscar([
      'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
    ])[0];

    $statusEmAndamento = in_array($objBlocoDeTramiteProtocoloFixtureDTO->getNumIdAndamento(), self::$idsEmAndamento);
    $this->assertTrue($statusEmAndamento);
  }

}
