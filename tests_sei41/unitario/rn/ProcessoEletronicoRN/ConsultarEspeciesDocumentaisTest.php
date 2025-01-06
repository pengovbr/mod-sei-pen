<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de testes para o método `consultarEspeciesDocumentais`.
 *
 * Esta classe utiliza PHPUnit para validar o comportamento do método
 * `consultarEspeciesDocumentais` da classe `ProcessoEletronicoRN`.
 *
 * O método é responsável por buscar espécies documentais, e os testes
 * cobrem cenários com resultados bem-sucedidos, ausência de resultados e
 * tratamento de erros.
 *
 * A classe utiliza mocks para simular interações com a dependência externa
 * `ProcessoEletronicoRN`.
 */
class ConsultarEspeciesDocumentaisTest extends TestCase
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
   * - `get`
   */
  protected function setUp(): void
  {
    $this->mockService = $this->getMockBuilder(ProcessoEletronicoRN::class)
      ->onlyMethods(['get'])
      ->getMock();
  }

  /**
   * Testa o método `consultarEspeciesDocumentais` com retorno bem-sucedido.
   *
   * Verifica se o método:
   * - Retorna um array com as espécies documentais.
   * - Converte corretamente as strings para o formato esperado (`ISO-8859-1`).
   */
  public function testConsultarEspeciesDocumentaisComSucesso()
  {
    // Mock do retorno esperado do método get
    $resultadoMock = [
      'especies' => [
        ['nomeNoProdutor' => 'Espécie 1'],
        ['nomeNoProdutor' => 'Espécie 2'],
      ]
    ];

    $this->mockService->expects($this->once())
      ->method('get')
      ->with('especies', [])
      ->willReturn($resultadoMock);

    // Chamada do método
    $resultado = $this->mockService->consultarEspeciesDocumentais();

    // Validações
    $this->assertIsArray($resultado);
    $this->assertCount(2, $resultado);
    $this->assertEquals(
      mb_convert_encoding('Espécie 1', 'ISO-8859-1', 'UTF-8'),
      $resultado[0]
    );
    $this->assertEquals(
      mb_convert_encoding('Espécie 2', 'ISO-8859-1', 'UTF-8'),
      $resultado[1]
    );
  }

  /**
   * Testa o método `consultarEspeciesDocumentais` quando não há resultados.
   *
   * Verifica se o método:
   * - Retorna um array vazio.
   */
  public function testConsultarEspeciesDocumentaisSemResultados()
  {
    // Mock do retorno esperado do método get
    $resultadoMock = [
      'especies' => []
    ];

    $this->mockService->expects($this->once())
      ->method('get')
      ->with('especies', [])
      ->willReturn($resultadoMock);

    // Chamada do método
    $resultado = $this->mockService->consultarEspeciesDocumentais();

    // Validações
    $this->assertIsArray($resultado);
    $this->assertEmpty($resultado);
  }

  /**
   * Testa o método `consultarEspeciesDocumentais` quando ocorre um erro.
   *
   * Verifica se o método:
   * - Lança a exceção esperada (`InfraException`).
   * - Contém a mensagem correta de erro.
   */
  public function testConsultarEspeciesDocumentaisComErro()
  {
    // Configuração do mock para lançar uma exceção
    $this->mockService->expects($this->once())
      ->method('get')
      ->willThrowException(new Exception('Erro simulado'));

    // Expectativa de exceção
    $this->expectException(InfraException::class);
    $this->expectExceptionMessage('Não foi encontrado nenhuma espécie documental.');

    // Chamada do método (deve lançar exceção)
    $this->mockService->consultarEspeciesDocumentais();
  }
}