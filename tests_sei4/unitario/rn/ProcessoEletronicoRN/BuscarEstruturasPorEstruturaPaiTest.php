<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de testes para o m�todo privado `buscarEstruturasPorEstruturaPai`.
 *
 * Essa classe utiliza PHPUnit para validar o comportamento de m�todos da classe
 * `ProcessoEletronicoRN`, especialmente os que envolvem intera��o com servi�os externos
 * simulados por mocks. 
 *
 * M�todos testados:
 * - `buscarEstruturasPorEstruturaPai` com e sem identifica��o.
 *
 * A classe demonstra o uso de mocks para simular depend�ncias e reflex�es para testar
 * m�todos privados.
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
   * Configura��o inicial antes de cada teste.
   *
   * Cria o mock da classe `ProcessoEletronicoRN` e define os m�todos que podem ser
   * simulados durante os testes.
   *
   * M�todos simulados:
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
   * Testa o m�todo `buscarEstruturasPorEstruturaPai` com par�metros de identifica��o.
   *
   * Verifica se o m�todo:
   * - Retorna um array.
   * - Retorna o n�mero correto de estruturas.
   * - As estruturas t�m os valores esperados.
   */
  public function testBuscarEstruturasPorEstruturaPaiComIdentificacao()
  {
    // Configura��o do mock
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

    // Reflex�o para acessar o m�todo privado
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

    // Valida��es
    $this->assertIsArray($resultado, 'Deve retornar um array');
    $this->assertCount(2, $resultado, 'Deve retornar duas estruturas');
    $this->assertEquals('Estrutura 1', $resultado[0]->nome, 'Deve retornar a estrutura 1');
    $this->assertEquals('Estrutura 2', $resultado[1]->nome, 'Deve retornar a estrutura 2');
  }

  /**
   * Testa o m�todo `buscarEstruturasPorEstruturaPai` sem par�metros de identifica��o.
   *
   * Verifica se o m�todo:
   * - Retorna um array.
   * - Retorna uma estrutura �nica com os valores esperados.
   */
  public function testBuscarEstruturasPorEstruturaPaiSemIdentificacao()
  {
    // Configura��o do mock
    $resultadoMock = new stdClass();
    $resultadoMock->estruturasEncontradasNoFiltroPorEstruturaPai = new stdClass();
    $resultadoMock->estruturasEncontradasNoFiltroPorEstruturaPai->estrutura = (object) ['id' => 1, 'nome' => 'Estrutura �nica'];

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

    // Reflex�o para acessar o m�todo privado
    $reflexao = new ReflectionClass($this->mockService);
    $metodoPrivado = $reflexao->getMethod('buscarEstruturasPorEstruturaPai');
    $metodoPrivado->setAccessible(true);

    // Teste
    $idRepositorioEstrutura = 123;

    $resultado = $metodoPrivado->invokeArgs($this->mockService, [$idRepositorioEstrutura]);

    // Valida��es
    $this->assertIsArray($resultado);
    $this->assertCount(1, $resultado);
    $this->assertEquals('Estrutura �nica', $resultado[0]->nome);
  }
}