<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de testes para o m�todo `consultarEspeciesDocumentais`.
 *
 * Esta classe utiliza PHPUnit para validar o comportamento do m�todo
 * `consultarEspeciesDocumentais` da classe `ProcessoEletronicoRN`.
 *
 * O m�todo � respons�vel por buscar esp�cies documentais, e os testes
 * cobrem cen�rios com resultados bem-sucedidos, aus�ncia de resultados e
 * tratamento de erros.
 *
 * A classe utiliza mocks para simular intera��es com a depend�ncia externa
 * `ProcessoEletronicoRN`.
 */
class ConsultarEspeciesDocumentaisTest extends TestCase
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
   * - `get`
   */
  protected function setUp(): void
  {
    $this->mockService = $this->getMockBuilder(ProcessoEletronicoRN::class)
      ->onlyMethods(['get'])
      ->getMock();
  }

  /**
   * Testa o m�todo `consultarEspeciesDocumentais` com retorno bem-sucedido.
   *
   * Verifica se o m�todo:
   * - Retorna um array com as esp�cies documentais.
   * - Converte corretamente as strings para o formato esperado (`ISO-8859-1`).
   */
  public function testConsultarEspeciesDocumentaisComSucesso()
  {
    // Mock do retorno esperado do m�todo get
    $resultadoMock = [
      'especies' => [
        ['nomeNoProdutor' => 'Esp�cie 1'],
        ['nomeNoProdutor' => 'Esp�cie 2'],
      ]
    ];

    $this->mockService->expects($this->once())
      ->method('get')
      ->with('especies', [])
      ->willReturn($resultadoMock);

    // Chamada do m�todo
    $resultado = $this->mockService->consultarEspeciesDocumentais();

    // Valida��es
    $this->assertIsArray($resultado);
    $this->assertCount(2, $resultado);
    $this->assertEquals(
      mb_convert_encoding('Esp�cie 1', 'ISO-8859-1', 'UTF-8'),
      $resultado[0]
    );
    $this->assertEquals(
      mb_convert_encoding('Esp�cie 2', 'ISO-8859-1', 'UTF-8'),
      $resultado[1]
    );
  }

  /**
   * Testa o m�todo `consultarEspeciesDocumentais` quando n�o h� resultados.
   *
   * Verifica se o m�todo:
   * - Retorna um array vazio.
   */
  public function testConsultarEspeciesDocumentaisSemResultados()
  {
    // Mock do retorno esperado do m�todo get
    $resultadoMock = [
      'especies' => []
    ];

    $this->mockService->expects($this->once())
      ->method('get')
      ->with('especies', [])
      ->willReturn($resultadoMock);

    // Chamada do m�todo
    $resultado = $this->mockService->consultarEspeciesDocumentais();

    // Valida��es
    $this->assertIsArray($resultado);
    $this->assertEmpty($resultado);
  }

  /**
   * Testa o m�todo `consultarEspeciesDocumentais` quando ocorre um erro.
   *
   * Verifica se o m�todo:
   * - Lan�a a exce��o esperada (`InfraException`).
   * - Cont�m a mensagem correta de erro.
   */
  public function testConsultarEspeciesDocumentaisComErro()
  {
    // Configura��o do mock para lan�ar uma exce��o
    $this->mockService->expects($this->once())
      ->method('get')
      ->willThrowException(new Exception('Erro simulado'));

    // Expectativa de exce��o
    $this->expectException(InfraException::class);
    $this->expectExceptionMessage('N�o foi encontrado nenhuma esp�cie documental.');

    // Chamada do m�todo (deve lan�ar exce��o)
    $this->mockService->consultarEspeciesDocumentais();
  }
}