<?php

use PHPUnit\Framework\Attributes\{Group,Large};

/**
 * #[Group('execute_parallel_group1')]
 */
class TramiteProcessoOrgaosDiferentesMesmoRepositorioTest extends FixtureCenarioBaseTestCase
{
  /**
   * #[Large]
   */
  public function test_permitir_envio_entre_orgaos_diferentes_no_mesmo_repositorio()
    {
      $this->assertSame(
          CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS,
          CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS
      );

      $remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      $destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
      $processoTeste = $this->gerarDadosProcessoTeste($remetente);
      $documentoTeste = $this->gerarDadosDocumentoInternoTeste($remetente);

      $this->realizarTramiteExternoComValidacaoNoRemetenteFixture(
          $processoTeste,
          $documentoTeste,
          $remetente,
          $destinatario
      );
  }
}
