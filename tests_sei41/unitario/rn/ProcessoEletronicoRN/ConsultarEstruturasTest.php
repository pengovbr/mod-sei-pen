<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de teste para o método listarRepositoriosDeEstruturas da classe ProcessoEletronicoRN.
 * 
 * Esta classe utiliza PHPUnit para verificar o comportamento do método listarRepositoriosDeEstruturas
 * em diferentes cenários, garantindo que ele funcione conforme o esperado.
 */
class ConsultarEstruturasTest extends TestCase
{
  /**
   * Mock da classe ProcessoEletronicoRN.
   * 
   * @var ProcessoEletronicoRN|\PHPUnit\Framework\MockObject\MockObject
   */
  private $mockService;

  /**
   * Configuração inicial do teste.
   * 
   * Este método cria um mock da classe ProcessoEletronicoRN e redefine
   * o método 'get' para simular comportamentos durante os testes.
   *
   * @return void
   */
  protected function setUp(): void
  {
    $this->mockService = $this->getMockBuilder(ProcessoEletronicoRN::class)
      ->onlyMethods(['get'])
      ->getMock();
  }

  public function testConsultarEstruturaListaSucesso()
  {
    $mockResponse = [
      "estruturas" => [
        [
          "numeroDeIdentificacaoDaEstrutura" => "159098",
          "nome" => "Mauro ORG1 Filha",
          "sigla" => "Mauro ORG1 Filha",
          "ativo" => true,
          "unidadeReceptora" => false,
          "aptoParaReceberTramites" => true,
          "codigoNoOrgaoEntidade" => "",
          "codigoUnidadeReceptora" => "",
          "tipoDeTramitacao" => 0,
          "hierarquia" => [
            [
              "numeroDeIdentificacaoDaEstrutura" => "152254",
              "nome" => "Órgão de Desenvolvimento ABC (FIRST) - ORGABC",
              "sigla" => "ORGABC"
            ]
          ]
        ]
      ],
      "totalDeRegistros" => 1
    ];

    // Configura o mock para retornar a resposta
    $this->mockService->expects($this->once())
      ->method('get')
      ->willReturn($mockResponse);

    $resultado = $this->mockService->consultarEstruturas(159098, []);

    $this->assertIsArray($resultado, 'O retorno deve ser um array.');
  }

  public function testConsultarEstruturaListaLancaExcecao()
  {
    $this->mockService->expects($this->once())
      ->method('get')
      ->willThrowException(new Exception('Erro na requisição'));

    $this->expectException(InfraException::class);
    $this->expectExceptionMessage('Falha na obtenção de unidades externas');

    $this->mockService->consultarEstruturas(159098, []);
  }
}