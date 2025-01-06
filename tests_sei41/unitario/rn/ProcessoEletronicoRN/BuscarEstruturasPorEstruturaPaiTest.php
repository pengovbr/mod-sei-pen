<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de testes para o método privado `buscarEstruturasPorEstruturaPai`.
 *
 * Essa classe utiliza PHPUnit para validar o comportamento de métodos da classe
 * `ProcessoEletronicoRN`, especialmente os que envolvem interação com serviços externos
 * simulados por mocks. 
 *
 * Métodos testados:
 * - `buscarEstruturasPorEstruturaPai` com e sem identificação.
 *
 * A classe demonstra o uso de mocks para simular dependências e reflexões para testar
 * métodos privados.
 */
class BuscarEstruturasPorEstruturaPaiTest extends TestCase
{
  /**
   * Mock da classe `ProcessoEletronicoRN`.
   *
   * @var ProcessoEletronicoRN|\PHPUnit\Framework\MockObject\MockObject
   */
  private $mockService;

  /**
   * Configuração inicial antes de cada teste.
   *
   * Cria o mock da classe `ProcessoEletronicoRN` e define os métodos que podem ser
   * simulados durante os testes.
   *
   * Métodos simulados:
   * - `consultarEstruturas`
   * - `tentarNovamenteSobErroHTTP`
   */
  protected function setUp(): void
  {
    $this->mockService = $this->getMockBuilder(ProcessoEletronicoRN::class)
      ->onlyMethods(['consultarEstruturas', 'tentarNovamenteSobErroHTTP'])
      ->getMock();
  }

  /**
   * Testa o método `buscarEstruturasPorEstruturaPai` com parâmetros de identificação.
   *
   * Verifica se o método:
   * - Retorna um array.
   * - Retorna o número correto de estruturas.
   * - As estruturas têm os valores esperados.
   */
  public function testBuscarEstruturasPorEstruturaPaiComIdentificacao()
  {
    // Configuração do mock
    $resultadoMock = new stdClass();
    $resultadoMock->estruturasEncontradasNoFiltroPorEstruturaPai = new stdClass();
    $resultadoMock->estruturasEncontradasNoFiltroPorEstruturaPai->estrutura = [
      (object) ['id' => 1, 'nome' => 'Estrutura 1'],
      (object) ['id' => 2, 'nome' => 'Estrutura 2']
    ];

    $this->mockService->expects($this->once())
      ->method('tentarNovamenteSobErroHTTP')
      ->willReturnCallback(function ($callback) use ($resultadoMock) {
          $mockObjPenWs = $this->getMockBuilder(stdClass::class)
            ->addMethods(['consultarEstruturasPorEstruturaPai'])
            ->getMock();
          $mockObjPenWs->method('consultarEstruturasPorEstruturaPai')
              ->willReturn($resultadoMock);

          return $callback($mockObjPenWs);
      });

    // Reflexão para acessar o método privado
    $reflexao = new ReflectionClass($this->mockService);
    $metodoPrivado = $reflexao->getMethod('buscarEstruturasPorEstruturaPai');
    $metodoPrivado->setAccessible(true);

    // Teste
    $idRepositorioEstrutura = 123;
    $numeroDeIdentificacaoDaEstrutura = 'ABC123';

    $resultado = $metodoPrivado->invokeArgs(
      $this->mockService,
      [$idRepositorioEstrutura, $numeroDeIdentificacaoDaEstrutura]
    );

    // Validações
    $this->assertIsArray($resultado, 'Deve retornar um array');
    $this->assertCount(2, $resultado, 'Deve retornar duas estruturas');
    $this->assertEquals('Estrutura 1', $resultado[0]->nome, 'Deve retornar a estrutura 1');
    $this->assertEquals('Estrutura 2', $resultado[1]->nome, 'Deve retornar a estrutura 2');
  }

  /**
   * Testa o método `buscarEstruturasPorEstruturaPai` sem parâmetros de identificação.
   *
   * Verifica se o método:
   * - Retorna um array.
   * - Retorna uma estrutura única com os valores esperados.
   */
  public function testBuscarEstruturasPorEstruturaPaiSemIdentificacao()
  {
    // Configuração do mock
    $resultadoMock = new stdClass();
    $resultadoMock->estruturasEncontradasNoFiltroPorEstruturaPai = new stdClass();
    $resultadoMock->estruturasEncontradasNoFiltroPorEstruturaPai->estrutura = (object) ['id' => 1, 'nome' => 'Estrutura Única'];

    $this->mockService->expects($this->once())
      ->method('tentarNovamenteSobErroHTTP')
      ->willReturnCallback(function ($callback) use ($resultadoMock) {
        $mockObjPenWs = $this->getMockBuilder(stdClass::class)
          ->addMethods(['consultarEstruturasPorEstruturaPai'])
          ->getMock();
        $mockObjPenWs->method('consultarEstruturasPorEstruturaPai')
            ->willReturn($resultadoMock);

        return $callback($mockObjPenWs);
      });

    // Reflexão para acessar o método privado
    $reflexao = new ReflectionClass($this->mockService);
    $metodoPrivado = $reflexao->getMethod('buscarEstruturasPorEstruturaPai');
    $metodoPrivado->setAccessible(true);

    // Teste
    $idRepositorioEstrutura = 123;

    $resultado = $metodoPrivado->invokeArgs($this->mockService, [$idRepositorioEstrutura]);

    // Validações
    $this->assertIsArray($resultado);
    $this->assertCount(1, $resultado);
    $this->assertEquals('Estrutura Única', $resultado[0]->nome);
  }
}