<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de testes para o método `consultarMotivosUrgencia`.
 *
 * Essa classe utiliza PHPUnit para validar o comportamento do método
 * `consultarMotivosUrgencia` da classe `ProcessoEletronicoRN`. 
 * 
 * O método é responsável por buscar os motivos de urgência, e os testes
 * simulam cenários com resultados bem-sucedidos, ausência de resultados e
 * ocorrência de erros.
 *
 * A classe faz uso de mocks para simular interações com a dependência externa
 * `ProcessoEletronicoRN`.
 */
class ConsultarMotivosUrgenciaTest extends TestCase
{
  /**
   * Mock da classe `ProcessoEletronicoRN`.
   *
   * Este mock é usado para simular os comportamentos da classe sem executar
   * a implementação real, permitindo testar os métodos que dependem dela.
   *
   * @var ProcessoEletronicoRN|\PHPUnit\Framework\MockObject\MockObject
   */
  private $mockService;

  /**
   * Configuração inicial antes de cada teste.
   *
   * Este método cria o mock da classe `ProcessoEletronicoRN` e define os
   * métodos que podem ser simulados durante os testes.
   *
   * Métodos simulados:
   * - `consultarEstruturas`
   * - `get`
   */
  protected function setUp(): void
  {
    $this->mockService = $this->getMockBuilder(ProcessoEletronicoRN::class)
      ->onlyMethods(['consultarEstruturas', 'get'])
      ->getMock();
  }

  /**
   * Testa o método `consultarMotivosUrgencia` com retorno bem-sucedido.
   *
   * Verifica se o método:
   * - Retorna um array.
   * - Contém os motivos de urgência esperados.
   */
  public function testConsultarMotivosUrgenciaComSucesso()
  {
    // Mock do retorno esperado do método get
    $resultadoMock = [
      'motivosUrgencia' => [
        ['descricao' => 'Motivo 1'],
        ['descricao' => 'Motivo 2'],
      ]
    ];

    $this->mockService->expects($this->once())
      ->method('get')
      ->with('motivosUrgencia', [])
      ->willReturn($resultadoMock);

    // Chamada do método
    $resultado = $this->mockService->consultarMotivosUrgencia();

    // Validações
    $this->assertIsArray($resultado);
    $this->assertCount(2, $resultado);
    $this->assertEquals('Motivo 1', $resultado[0]);
    $this->assertEquals('Motivo 2', $resultado[1]);
  }

  /**
   * Testa o método `consultarMotivosUrgencia` quando não há resultados.
   *
   * Verifica se o método:
   * - Retorna um array vazio.
   */
  public function testConsultarMotivosUrgenciaSemResultados()
  {
    // Mock do retorno esperado do método get
    $resultadoMock = [
      'motivosUrgencia' => []
    ];

    $this->mockService->expects($this->once())
      ->method('get')
      ->with('motivosUrgencia', [])
      ->willReturn($resultadoMock);

    // Chamada do método
    $resultado = $this->mockService->consultarMotivosUrgencia();

    // Validações
    $this->assertIsArray($resultado);
    $this->assertEmpty($resultado);
  }

  /**
   * Testa o método `consultarMotivosUrgencia` quando ocorre um erro.
   *
   * Verifica se o método:
   * - Lança a exceção esperada (`InfraException`).
   * - Contém a mensagem correta de erro.
   */
  public function testConsultarMotivosUrgenciaComErro()
  {
    // Configuração do mock para lançar uma exceção
    $this->mockService->expects($this->once())
      ->method('get')
      ->willThrowException(new Exception('Erro simulado'));

    // Expectativa de exceção
    $this->expectException(InfraException::class);
    $this->expectExceptionMessage('Falha na obtenção de unidades externas');

    // Chamada do método (deve lançar exceção)
    $this->mockService->consultarMotivosUrgencia();
  }
}