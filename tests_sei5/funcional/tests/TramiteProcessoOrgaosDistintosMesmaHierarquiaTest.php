<?php

use PHPUnit\Framework\Attributes\{Group,Large};

/**
 * Issue #1259: orgaos com certificados (SPEs) distintos que pertencem a mesma
 * estrutura hierarquica do repositorio devem tramitar entre si. O bloqueio de
 * envio externo vale apenas para estruturas do mesmo SPE do remetente.
 *
 * Cenario: a estrutura CONTEXTO_ORGAO_B_ID_ESTRUTURA_HIERARQUIA_ORGAO_A e filha
 * da estrutura do Orgao A no repositorio, mas esta mapeada no SEI do Orgao B
 * (outro certificado), assim como o ORGAO7 sob o ORGAO6 relatado pelo IBAMA.
 *
 * #[Group('execute_parallel_group1')]
 */
class TramiteProcessoOrgaosDistintosMesmaHierarquiaTest extends FixtureCenarioBaseTestCase
{
    private const ID_UNIDADE_ORGAO_B = 110000002;

    private $penMapUnidadesFixture;

  public function setUp(): void
    {
    if (!is_numeric(CONTEXTO_ORGAO_B_ID_ESTRUTURA_HIERARQUIA_ORGAO_A)) {
        $this->markTestSkipped('Estrutura do Orgao B na hierarquia do Orgao A nao configurada (CONTEXTO_ORGAO_B_ID_ESTRUTURA_HIERARQUIA_ORGAO_A).');
    }

      parent::setUp();

      putenv("DATABASE_HOST=org2-database");
      $this->penMapUnidadesFixture = new \PenMapUnidadesFixture();
      $this->penMapUnidadesFixture->carregar([
          'IdUnidade' => self::ID_UNIDADE_ORGAO_B,
          'Id' => CONTEXTO_ORGAO_B_ID_ESTRUTURA_HIERARQUIA_ORGAO_A,
          'Sigla' => CONTEXTO_ORGAO_B_NOME_ESTRUTURA_HIERARQUIA_ORGAO_A,
          'Nome' => CONTEXTO_ORGAO_B_NOME_ESTRUTURA_HIERARQUIA_ORGAO_A,
      ]);
      putenv("DATABASE_HOST=org1-database");
  }

  public function tearDown(): void
    {
    if ($this->penMapUnidadesFixture) {
        putenv("DATABASE_HOST=org2-database");
        $this->penMapUnidadesFixture->excluir(['Id' => CONTEXTO_ORGAO_B_ID_ESTRUTURA_HIERARQUIA_ORGAO_A]);
        putenv("DATABASE_HOST=org1-database");
    }

      parent::tearDown();
  }

  /**
   * #[Large]
   */
  public function test_permitir_envio_para_orgao_com_certificado_distinto_na_mesma_hierarquia()
    {
      $remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      $processoTeste = $this->gerarDadosProcessoTeste($remetente);
      $documentoTeste = $this->gerarDadosDocumentoInternoTeste($remetente);

      $destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
      $destinatario['SIGLA_UNIDADE'] = CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA_ORGAO_A;
      $destinatario['NOME_UNIDADE'] = CONTEXTO_ORGAO_B_NOME_ESTRUTURA_HIERARQUIA_ORGAO_A;
      $destinatario['SIGLA_UNIDADE_HIERARQUIA'] = CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA_HIERARQUIA_ORGAO_A_HIERARQUIA;

      $this->realizarTramiteExternoComValidacaoNoRemetenteFixture(
          $processoTeste,
          $documentoTeste,
          $remetente,
          $destinatario
      );

      $this->realizarValidacaoRecebimentoProcessoNoDestinatario(
          $processoTeste,
          $documentoTeste,
          $destinatario,
          false,
          true
      );
  }
}
