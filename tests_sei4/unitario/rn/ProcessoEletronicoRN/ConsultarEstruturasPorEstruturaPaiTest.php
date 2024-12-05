<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de teste para o método `consultarEstruturasPorEstruturaPai` da classe ProcessoEletronicoRN.
 *
 * Esta classe contém testes unitários para verificar o comportamento do método
 * `consultarEstruturasPorEstruturaPai`, garantindo que ele retorne os resultados
 * esperados em diferentes cenários, incluindo casos de sucesso e situações de erro.
 */
class ConsultarEstruturasPorEstruturaPaiTest extends TestCase
{
  /**
   * Mock da classe ProcessoEletronicoRN.
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
   * Este método cria o mock de ProcessoEletronicoRN e define quais métodos
   * podem ser simulados.
   */
  protected function setUp(): void
  {
    $this->mockService = $this->getMockBuilder(ProcessoEletronicoRN::class)
      ->onlyMethods(['consultarEstruturas', 'validarRestricaoUnidadesCadastradas', 'buscarEstruturasPorEstruturaPai'])
      ->getMock();
  }

  /**
   * Testa o método consultarEstruturasPorEstruturaPai para um caso de sucesso.
   *
   * Verifica se a lista de estruturas é retornada e ordenada corretamente com base nos nomes.
   *
   * @return void
   */
  public function testConsultarEstruturasPorEstruturaPaiRetornaEstruturasOrdenadas()
  {
    $idRepositorioEstrutura = 1;
    $mockEstruturas = [
      (object)['nome' => 'Unidade B', 'codigo' => '002'],
      (object)['nome' => 'Unidade A', 'codigo' => '001'],
      (object)['nome' => 'Unidade C', 'codigo' => '003'],
    ];

    $this->mockService->expects($this->once())
      ->method('validarRestricaoUnidadesCadastradas')
      ->with($idRepositorioEstrutura)
      ->willReturn(null);

    $this->mockService->expects($this->once())
      ->method('buscarEstruturasPorEstruturaPai')
      ->with($idRepositorioEstrutura, null)
      ->willReturn($mockEstruturas);

    $resultado = $this->mockService->consultarEstruturasPorEstruturaPai($idRepositorioEstrutura);

    $this->assertCount(3, $resultado);
    $this->assertEquals('Unidade A', $resultado[0]->nome);
    $this->assertEquals('Unidade B', $resultado[1]->nome);
    $this->assertEquals('Unidade C', $resultado[2]->nome);
  }

  /**
   * Testa o método consultarEstruturasPorEstruturaPai para uma unidade pai específica.
   *
   * Verifica se a busca por estrutura pai é realizada corretamente ao fornecer
   * um número de identificação específico.
   *
   * @return void
   */
  public function testConsultarEstruturasPorEstruturaPaiComUnidadePaiEspecifica()
  {
    $idRepositorioEstrutura = 1;
    $numeroDeIdentificacaoDaEstrutura = '001';
    $mockEstruturas = [
      (object)['nome' => 'Unidade D', 'codigo' => '004'],
    ];

    $this->mockService->expects($this->never())
      ->method('validarRestricaoUnidadesCadastradas');

    $this->mockService->expects($this->once())
      ->method('buscarEstruturasPorEstruturaPai')
      ->with($idRepositorioEstrutura, $numeroDeIdentificacaoDaEstrutura)
      ->willReturn($mockEstruturas);

    $resultado = $this->mockService->consultarEstruturasPorEstruturaPai($idRepositorioEstrutura, $numeroDeIdentificacaoDaEstrutura);

    $this->assertCount(1, $resultado);
    $this->assertEquals('Unidade D', $resultado[0]->nome);
  }

  /**
   * Testa o método consultarEstruturasPorEstruturaPai quando ocorre um erro.
   *
   * Verifica se uma exceção InfraException é lançada corretamente ao ocorrer
   * um erro no método validarRestricaoUnidadesCadastradas.
   *
   * @return void
   */
  public function testConsultarEstruturasPorEstruturaPaiLancaExcecaoParaErro()
  {
    $idRepositorioEstrutura = 1;

    $this->mockService->expects($this->once())
      ->method('validarRestricaoUnidadesCadastradas')
      ->willThrowException(new Exception('Erro no serviço'));

    $this->expectException(InfraException::class);
    $this->expectExceptionMessage('Falha na obtenção de unidades externas');

    $this->mockService->consultarEstruturasPorEstruturaPai($idRepositorioEstrutura);
  }
}
