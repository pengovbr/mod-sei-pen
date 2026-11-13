<?php

use PHPUnit\Framework\AssertionFailedError;

/**
 * Ciencia de recusa quando o processo foi concluido na unidade remetente
 * enquanto aguardava o tramite (issue #1265).
 *
 * Sem a reabertura automatica, o desbloqueio do processo falhava com
 * "Processo X nao possui andamento aberto na unidade Y" e a pendencia de
 * recusa ficava sendo reprocessada indefinidamente.
 *
 * Execution Groups
 * #[Group('execute_alone_group4')]
 */
class RecebimentoRecusaProcessoConcluidoOrigemTest extends FixtureCenarioBaseTestCase
{
  protected $destinatarioWs;
  protected $servicoPEN;
  public static $remetente;
  public static $destinatario;
  public static $processoTeste;
  public static $documentoTeste;
  public static $protocoloTeste;
  public static $arrMultiplosOrgaosOriginal = array();

  const MOTIVO_RECUSA = 'Recusa de teste com processo concluido na origem';

  public function setUp(): void
    {
      parent::setUp();

      $this->destinatarioWs = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

      $localCertificado = $this->destinatarioWs['LOCALIZACAO_CERTIFICADO_DIGITAL'];
      $senhaCertificado = $this->destinatarioWs['SENHA_CERTIFICADO_DIGITAL'];
      $this->servicoPEN = $this->instanciarApiDeIntegracao($localCertificado, $senhaCertificado);
  }

    /**
     * Tramita o processo, conclui-o na unidade remetente enquanto bloqueado e recusa o tramite no destino
     *
     * #[Group('envio')]
     *
     * @return void
     */
  public function test_tramitar_concluir_na_origem_e_recusar()
    {
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
      self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
      self::$documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

      // Unidade de protocolo pode lancar andamento em processo fechado, o que mascara o erro.
      // Remove o flag para reproduzir o cenario de uma unidade comum.
      $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);
      $bancoOrgaoA->execute("update unidade set sin_protocolo=? where sigla=?", array('N', CONTEXTO_ORGAO_A_SIGLA_UNIDADE));

      // Envio para multiplos orgaos nao mantem o processo bloqueado; forca o envio comum para o destino
      $arrMapeamentos = $bancoOrgaoA->query("select id_comp_digitais, sin_multiplos_orgaos from md_pen_envio_comp_digitais where id_unidade_pen = ?", array(self::$destinatario['ID_ESTRUTURA']));
      foreach ((array) $arrMapeamentos as $arrMapeamento) {
        $arrMapeamento = array_change_key_case($arrMapeamento, CASE_UPPER);
        self::$arrMultiplosOrgaosOriginal[$arrMapeamento['ID_COMP_DIGITAIS']] = $arrMapeamento['SIN_MULTIPLOS_ORGAOS'];
      }
      $bancoOrgaoA->execute("update md_pen_envio_comp_digitais set sin_multiplos_orgaos = ? where id_unidade_pen = ?", array('N', self::$destinatario['ID_ESTRUTURA']));

      $this->realizarTramiteExternoSemValidacaoNoRemetenteFixture(
        self::$processoTeste,
        self::$documentoTeste,
        self::$remetente,
        self::$destinatario,
        false
      );
      self::$protocoloTeste = self::$processoTeste["PROTOCOLO"];

      // Conclui o processo na unidade remetente enquanto ele esta bloqueado aguardando o tramite
      $this->abrirProcesso(self::$protocoloTeste);
      $this->assertTrue($this->paginaProcesso->processoBloqueado());
      $this->paginaProcesso->concluirProcesso();
      $this->abrirProcesso(self::$protocoloTeste);
      $this->assertFalse($this->paginaProcesso->processoAberto());

      $idTramite = $bancoOrgaoA->query("select max(id_tramite) as id_tramite from md_pen_componente_digital where protocolo = ?", array(self::$protocoloTeste));
      $idTramite = array_key_exists("id_tramite", $idTramite[0]) ? $idTramite[0]["id_tramite"] : $idTramite[0]["ID_TRAMITE"];

      $this->recusarTramiteAPI($idTramite, self::MOTIVO_RECUSA);
  }

    /**
     * Verifica que a ciencia da recusa reabriu e desbloqueou o processo na unidade remetente
     *
     * #[Group('verificacao_recebimento')]
     *
     * #[Depends('test_tramitar_concluir_na_origem_e_recusar')]
     *
     * @return void
     */
  public function test_verificar_processo_reaberto_e_desbloqueado_na_origem()
    {
      if (DESATIVAR_AGENDAMENTO == 'true') {
        $this->executarTramitarPendenciasSimples();
      }

      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
      $this->abrirProcesso(self::$protocoloTeste);

      $this->waitUntil(function () {
          sleep(1);
          $this->paginaProcesso->refresh();
        try {
            $this->assertTrue($this->paginaProcesso->processoAberto());
            $this->assertFalse($this->paginaProcesso->processoBloqueado());
            return true;
        } catch (AssertionFailedError $e) {
            return false;
        }
      }, PEN_WAIT_TIMEOUT);

      $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, false, true, self::MOTIVO_RECUSA);

      $this->paginaBase->navegarParaControleProcessoIcone();
      $this->assertTrue($this->paginaControleProcesso->contemProcesso(self::$protocoloTeste));
      $this->assertTrue($this->paginaControleProcesso->contemAlertaProcessoRecusado(self::$protocoloTeste));
  }

  public static function tearDownAfterClass(): void
    {
      parent::tearDownAfterClass();
      $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);
      $bancoOrgaoA->execute("update unidade set sin_protocolo=? where sigla=?", array('S', CONTEXTO_ORGAO_A_SIGLA_UNIDADE));
    foreach (self::$arrMultiplosOrgaosOriginal as $numIdCompDigitais => $strSinMultiplosOrgaos) {
        $bancoOrgaoA->execute("update md_pen_envio_comp_digitais set sin_multiplos_orgaos = ? where id_comp_digitais = ?", array($strSinMultiplosOrgaos, $numIdCompDigitais));
    }
  }

  private function instanciarApiDeIntegracao($localCertificado, $senhaCertificado)
    {
      return new GuzzleHttp\Client([
          'base_uri' => PEN_ENDERECO_WEBSERVICE,
          'timeout'  => ProcessoEletronicoRN::WS_TIMEOUT_CONEXAO,
          'headers'  => [
              'Accept' => '*/*',
              'Content-Type' => 'application/json',
          ],
          'cert'     => [$localCertificado, $senhaCertificado],
      ]);
  }

  private function recusarTramiteAPI($idTramite, $justificativa)
    {
      return $this->servicoPEN->request('POST', "tramites/{$idTramite}/recusa", [
          'json' => [
              'justificativa' => $justificativa,
              'motivo' => '99',
          ]
      ]);
  }
}
