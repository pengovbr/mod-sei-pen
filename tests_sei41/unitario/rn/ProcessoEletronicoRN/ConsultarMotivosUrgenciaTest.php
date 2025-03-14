<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de testes para o m�todo `consultarMotivosUrgencia`.
 *
 * Essa classe utiliza PHPUnit para validar o comportamento do m�todo
 * `consultarMotivosUrgencia` da classe `ProcessoEletronicoRN`. 
 * 
 * O m�todo � respons�vel por buscar os motivos de urg�ncia, e os testes
 * simulam cen�rios com resultados bem-sucedidos, aus�ncia de resultados e
 * ocorr�ncia de erros.
 *
 * A classe faz uso de mocks para simular intera��es com a depend�ncia externa
 * `ProcessoEletronicoRN`.
 */
class ConsultarMotivosUrgenciaTest extends TestCase
{
  /**
   * Mock da classe `ProcessoEletronicoRN`.
   *
   * Este mock � usado para simular os comportamentos da classe sem executar
   * a implementa��o real, permitindo testar os m�todos que dependem dela.
   *
   * @var ProcessoEletronicoRN|\PHPUnit\Framework\MockObject\MockObject
   */
  private $mockService;

  /**
   * Configura��o inicial antes de cada teste.
   *
   * Este m�todo cria o mock da classe `ProcessoEletronicoRN` e define os
   * m�todos que podem ser simulados durante os testes.
   *
   * M�todos simulados:
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
   * Testa o m�todo `consultarMotivosUrgencia` com retorno bem-sucedido.
   *
   * Verifica se o m�todo:
   * - Retorna um array.
   * - Cont�m os motivos de urg�ncia esperados.
   */
  public function testConsultarMotivosUrgenciaComSucesso()
  {
    // Mock do retorno esperado do m�todo get
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

    // Chamada do m�todo
    $resultado = $this->mockService->consultarMotivosUrgencia();

    // Valida��es
    $this->assertIsArray($resultado);
    $this->assertCount(2, $resultado);
    $this->assertEquals('Motivo 1', $resultado[0]);
    $this->assertEquals('Motivo 2', $resultado[1]);
  }

  /**
   * Testa o m�todo `consultarMotivosUrgencia` quando n�o h� resultados.
   *
   * Verifica se o m�todo:
   * - Retorna um array vazio.
   */
  public function testConsultarMotivosUrgenciaSemResultados()
  {
    // Mock do retorno esperado do m�todo get
    $resultadoMock = [
      'motivosUrgencia' => []
    ];

    $this->mockService->expects($this->once())
      ->method('get')
      ->with('motivosUrgencia', [])
      ->willReturn($resultadoMock);

    // Chamada do m�todo
    $resultado = $this->mockService->consultarMotivosUrgencia();

    // Valida��es
    $this->assertIsArray($resultado);
    $this->assertEmpty($resultado);
  }

  /**
   * Testa o m�todo `consultarMotivosUrgencia` quando ocorre um erro.
   *
   * Verifica se o m�todo:
   * - Lan�a a exce��o esperada (`InfraException`).
   * - Cont�m a mensagem correta de erro.
   */
  public function testConsultarMotivosUrgenciaComErro()
  {
    // Configura��o do mock para lan�ar uma exce��o
    $this->mockService->expects($this->once())
      ->method('get')
      ->willThrowException(new Exception('Erro simulado'));

    // Expectativa de exce��o
    $this->expectException(InfraException::class);
    $this->expectExceptionMessage('Falha na obten��o de unidades externas');

    // Chamada do m�todo (deve lan�ar exce��o)
    $this->mockService->consultarMotivosUrgencia();
  }
}