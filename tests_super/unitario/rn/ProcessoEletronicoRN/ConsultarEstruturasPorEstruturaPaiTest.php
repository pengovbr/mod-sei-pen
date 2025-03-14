<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de teste para o m�todo `consultarEstruturasPorEstruturaPai` da classe ProcessoEletronicoRN.
 *
 * Esta classe cont�m testes unit�rios para verificar o comportamento do m�todo
 * `consultarEstruturasPorEstruturaPai`, garantindo que ele retorne os resultados
 * esperados em diferentes cen�rios, incluindo casos de sucesso e situa��es de erro.
 */
class ConsultarEstruturasPorEstruturaPaiTest extends TestCase
{
  /**
   * Mock da classe ProcessoEletronicoRN.
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
   * Este m�todo cria o mock de ProcessoEletronicoRN e define quais m�todos
   * podem ser simulados.
   */
  protected function setUp(): void
  {
    $this->mockService = $this->getMockBuilder(ProcessoEletronicoRN::class)
      ->onlyMethods(['consultarEstruturas', 'validarRestricaoUnidadesCadastradas', 'buscarEstruturasPorEstruturaPai'])
      ->getMock();
  }

  /**
   * Testa o m�todo consultarEstruturasPorEstruturaPai para um caso de sucesso.
   *
   * Verifica se a lista de estruturas � retornada e ordenada corretamente com base nos nomes.
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
   * Testa o m�todo consultarEstruturasPorEstruturaPai para uma unidade pai espec�fica.
   *
   * Verifica se a busca por estrutura pai � realizada corretamente ao fornecer
   * um n�mero de identifica��o espec�fico.
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
   * Testa o m�todo consultarEstruturasPorEstruturaPai quando ocorre um erro.
   *
   * Verifica se uma exce��o InfraException � lan�ada corretamente ao ocorrer
   * um erro no m�todo validarRestricaoUnidadesCadastradas.
   *
   * @return void
   */
  public function testConsultarEstruturasPorEstruturaPaiLancaExcecaoParaErro()
  {
    $idRepositorioEstrutura = 1;

    $this->mockService->expects($this->once())
      ->method('validarRestricaoUnidadesCadastradas')
      ->willThrowException(new Exception('Erro no servi�o'));

    $this->expectException(InfraException::class);
    $this->expectExceptionMessage('Falha na obten��o de unidades externas');

    $this->mockService->consultarEstruturasPorEstruturaPai($idRepositorioEstrutura);
  }
}