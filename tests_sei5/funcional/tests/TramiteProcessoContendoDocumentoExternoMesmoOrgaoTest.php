<?php

use PHPUnit\Framework\Attributes\{Group,Large,Depends};
use PHPUnit\Framework\AssertionFailedError;

/**
 * Execution Groups
 * #[Group('execute_parallel_group1')]
 */
class TramiteProcessoContendoDocumentoExternoMesmoOrgaoTest extends FixtureCenarioBaseTestCase
{
  public static $remetente;
  public static $destinatario;
  public static $processoTeste;
  public static $documentoTeste;
  public static $protocoloTeste;

    /**
     * Teste tramitar processo contendo documento gerado
     * 
     * @Depends CenarioBaseTestCase::setUpBeforeClass
     * #[Large]
     *
     *
     * @return void
     */
  public function test_tramitar_processo_contendo_documento_gerado()
    {
      // Configuração do dados para teste do cenário
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
      self::$documentoTeste = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

      //Configuração da unidade destinatário como outra unidade do mesmo órgão
      self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      self::$destinatario['NOME_UNIDADE'] = self::$remetente['NOME_UNIDADE_SECUNDARIA'];
      self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'] = self::$remetente['SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA'];

      $this->realizarTramiteExternoSemValidacaoNoRemetenteFixture(self::$processoTeste, self::$documentoTeste, self::$remetente, self::$destinatario);
      self::$protocoloTeste = self::$processoTeste["PROTOCOLO"];
  }

    /**
     * #[Depends('test_tramitar_processo_contendo_documento_gerado')]
     */
  public function test_verificar_origem_processo_contendo_documento_gerado()
    {
      $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      $this->abrirProcesso(self::$protocoloTeste);

      // 6 - Verificar se situação atual do processo está como bloqueado
      $this->waitUntil(function() use (&$orgaosDiferentes) {
          sleep(2);
          $this->paginaBase->refresh();
        try { 
            $this->assertStringNotContainsString(mb_convert_encoding("Processo em trâmite externo para ", 'UTF-8', 'ISO-8859-1'), $this->paginaProcesso->informacao());
            $this->assertFalse($this->paginaProcesso->processoAberto());
            $this->assertEquals($orgaosDiferentes, $this->paginaProcesso->processoBloqueado());
            return true;
        } catch (AssertionFailedError $e) {
            return false;
        }
      }, PEN_WAIT_TIMEOUT);

      // 7 - Validar se recibo de trâmite foi armazenado para o processo (envio e conclusão)
      $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
      $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTeste, $unidade);
      $this->validarRecibosTramite($mensagemRecibo, true, true);

      // 8 - Validar histórico de trâmite do processo
      $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);

      // 9 - Verificar se processo está na lista de Processos Tramitados Externamente
      $this->validarProcessosTramitados(self::$protocoloTeste, $orgaosDiferentes);
  }


    /**
     * #[Depends('test_verificar_origem_processo_contendo_documento_gerado')]
     * #[Large]
     */
  public function test_verificar_destino_processo_contendo_documento_gerado()
    {

      self::$destinatario['SIGLA_UNIDADE'] = self::$remetente['SIGLA_UNIDADE_SECUNDARIA'];
      $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, self::$documentoTeste, self::$destinatario);
  }
}
