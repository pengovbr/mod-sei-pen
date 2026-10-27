<?php

use PHPUnit\Framework\Attributes\{Group,Large};

/**
 * Issue #1259: orgaos com certificados (SPEs) distintos que pertencem a mesma
 * estrutura hierarquica do repositorio devem tramitar entre si. O bloqueio de
 * envio externo vale apenas para estruturas do mesmo SPE do remetente.
 *
 * Cenario 1: a unidade secundaria do Orgao A (CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA)
 * esta mapeada no proprio SEI do Orgao A (mesmo certificado), entao o envio externo
 * para ela deve ser bloqueado.
 *
 * Cenario 2: a mesma estrutura CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA (filha da
 * estrutura do Orgao A no repositorio) deixa de estar mapeada no SEI do Orgao A,
 * como uma estrutura da mesma hierarquia que pertence a outro SPE (o ORGAO7 sob o
 * ORGAO6 relatado pelo IBAMA). O envio nao deve ser bloqueado e o Tramita deve
 * aceita-lo. Como no Tramita a estrutura e do certificado do proprio Orgao A, o
 * recebimento e recusado por falta de mapeamento, o que conclui o tramite e
 * desbloqueia o processo. O mapeamento original e restaurado no tearDown.
 *
 * #[Group('execute_parallel_group1')]
 */
class TramiteProcessoOrgaosDistintosMesmaHierarquiaTest extends FixtureCenarioBaseTestCase
{
    // Pausa para acompanhar a execucao no navegador do Selenium (VNC)
    private const SEGUNDOS_OBSERVACAO = 5;

    private $penMapUnidadesFixture;
    private $objMapeamentoOriginalOrgaoA;

  public function tearDown(): void
  {
      sleep(self::SEGUNDOS_OBSERVACAO);

    if ($this->penMapUnidadesFixture) {
        putenv("DATABASE_HOST=org1-database");
        $this->restaurarMapeamento($this->objMapeamentoOriginalOrgaoA);
    }

      parent::tearDown();
  }

  /**
   * #[Large]
   */
  public function test_bloquear_envio_externo_para_unidade_do_mesmo_spe()
    {
      $remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      $processoTeste = $this->gerarDadosProcessoTeste($remetente);
      $documentoTeste = $this->gerarDadosDocumentoInternoTeste($remetente);

      $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);
      $this->cadastrarDocumentoInternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());
      $strProtocoloTeste = $objProtocoloDTO->getStrProtocoloFormatado();

      $this->acessarSistema($remetente['URL'], $remetente['SIGLA_UNIDADE'], $remetente['LOGIN'], $remetente['SENHA']);
      $this->abrirProcesso($strProtocoloTeste);

      $this->paginaProcesso->navegarParaTramitarProcesso();
      $this->paginaTramitar->repositorio($remetente['REP_ESTRUTURAS']);
      $this->paginaTramitar->unidade($remetente['NOME_UNIDADE_SECUNDARIA'], $remetente['SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA']);
      sleep(self::SEGUNDOS_OBSERVACAO);
      $this->paginaTramitar->tramitar();
      sleep(self::SEGUNDOS_OBSERVACAO);
      $mensagemAlerta = $this->paginaTramitar->alertTextAndClose(true);

      $mensagemEsperada = mb_convert_encoding('Não é possível realizar o envio externo para o próprio órgão remetente.', 'UTF-8', 'ISO-8859-1');
      $this->assertStringContainsString($mensagemEsperada, $mensagemAlerta);

      $this->abrirProcesso($strProtocoloTeste);
      $this->assertFalse($this->paginaProcesso->processoBloqueado());
      $this->assertTrue($this->paginaProcesso->processoAberto());
      sleep(self::SEGUNDOS_OBSERVACAO);
  }

  /**
   * #[Large]
   */
  public function test_permitir_envio_para_estrutura_da_mesma_hierarquia_de_outro_spe()
    {
      $this->penMapUnidadesFixture = new \PenMapUnidadesFixture();

      // Retira a estrutura secundaria do SEI do Orgao A para que ela deixe de ser do mesmo SPE do remetente
      putenv("DATABASE_HOST=org1-database");
      $this->objMapeamentoOriginalOrgaoA = $this->penMapUnidadesFixture->consultarMapeamento(['Id' => CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA]);
      $this->penMapUnidadesFixture->remover(['Id' => CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA]);

      $remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      $processoTeste = $this->gerarDadosProcessoTeste($remetente);
      $documentoTeste = $this->gerarDadosDocumentoInternoTeste($remetente);

      $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);
      $this->cadastrarDocumentoInternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());
      $strProtocoloTeste = $objProtocoloDTO->getStrProtocoloFormatado();

      $this->acessarSistema($remetente['URL'], $remetente['SIGLA_UNIDADE'], $remetente['LOGIN'], $remetente['SENHA']);
      $this->abrirProcesso($strProtocoloTeste);
      sleep(self::SEGUNDOS_OBSERVACAO);

      // Lanca excecao se o alerta de envio para o proprio orgao aparecer e valida
      // a mensagem de sucesso do envio ao Tramita
      $this->tramitarProcessoExternamente(
          $strProtocoloTeste,
          $remetente['REP_ESTRUTURAS'],
          $remetente['NOME_UNIDADE_SECUNDARIA'],
          $remetente['SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA'],
          false
      );
      sleep(self::SEGUNDOS_OBSERVACAO);

      // A recusa no recebimento conclui o tramite e devolve o processo desbloqueado ao remetente
      $this->waitUntil(function () {
          sleep(1);
          $this->paginaProcesso->refresh();
          return !$this->paginaProcesso->processoBloqueado();
      }, PEN_WAIT_TIMEOUT);
      sleep(self::SEGUNDOS_OBSERVACAO);
  }

  private function restaurarMapeamento($objPenUnidadeDTO)
    {
    if ($objPenUnidadeDTO) {
        $this->penMapUnidadesFixture->carregar([
            'IdUnidade' => $objPenUnidadeDTO->getNumIdUnidade(),
            'Id' => $objPenUnidadeDTO->getNumIdUnidadeRH(),
            'Sigla' => $objPenUnidadeDTO->getStrSiglaUnidadeRH(),
            'Nome' => $objPenUnidadeDTO->getStrNomeUnidadeRH(),
        ]);
    }
  }
}
