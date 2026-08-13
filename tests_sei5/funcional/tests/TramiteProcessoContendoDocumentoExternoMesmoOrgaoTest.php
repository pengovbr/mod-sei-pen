<?php

use PHPUnit\Framework\Attributes\{Group,Large};

/**
 * #[Group('execute_parallel_group1')]
 */
class TramiteProcessoContendoDocumentoExternoMesmoOrgaoTest extends FixtureCenarioBaseTestCase
{
  /**
   * #[Large]
   */
  public function test_bloquear_envio_externo_para_unidade_do_mesmo_orgao()
    {
      $remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      $processoTeste = $this->gerarDadosProcessoTeste($remetente);
      $documentoTeste = $this->gerarDadosDocumentoExternoTeste($remetente);

      $destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      $destinatario['NOME_UNIDADE'] = $remetente['NOME_UNIDADE_SECUNDARIA'];
      $destinatario['SIGLA_UNIDADE_HIERARQUIA'] = $remetente['SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA'];

      $this->expectExceptionMessage(
          mb_convert_encoding(
              'Não é possível realizar o envio externo para o próprio órgão remetente.',
              'UTF-8',
              'ISO-8859-1'
          )
      );

      $this->realizarTramiteExternoSemValidacaoNoRemetenteFixture(
          $processoTeste,
          $documentoTeste,
          $remetente,
          $destinatario
      );
  }
}
