<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de testes para a funcionalidade de listagem de estruturas.
 */
class LitarEstruturasTest extends TestCase
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
      ->onlyMethods(['consultarEstruturas'])
      ->getMock();
  }

  /**
   * Testa a listagem de estruturas com sucesso.
   *
   * Este teste verifica se o método listarEstruturas retorna corretamente
   * as estruturas esperadas quando os dados são fornecidos corretamente.
   */
  public function testListarEstruturasComSucesso()
  {
    $idRepositorioEstrutura = 1;
    $nome = 'Estrutura Teste';
    $mockRetornoConsulta = [
      'totalDeRegistros' => 1,
      'estruturas' => [
        [
          'numeroDeIdentificacaoDaEstrutura' => 123,
          'nome' => 'Estrutura 1',
          'sigla' => 'E1',
          'ativo' => true,
          'aptoParaReceberTramites' => false,
          'codigoNoOrgaoEntidade' => '001',
          'hierarquia' => [
            ['sigla' => 'H1'],
            ['sigla' => 'H2'],
          ],
        ],
      ],
    ];

    // Define a expectativa para o mock do método consultarEstruturas
    $this->mockService
      ->expects($this->once())
      ->method('consultarEstruturas')
      ->willReturn($mockRetornoConsulta);

    // Executa o método sob teste
    $result = $this->mockService->listarEstruturas($idRepositorioEstrutura, $nome);

    // Validações dos resultados
    $this->assertCount(1, $result);
    $this->assertInstanceOf(EstruturaDTO::class, $result[0]);
    $this->assertEquals(123, $result[0]->getNumNumeroDeIdentificacaoDaEstrutura());
    $this->assertEquals('Estrutura 1', $result[0]->getStrNome());
    $this->assertEquals('E1', $result[0]->getStrSigla());
    $this->assertEquals(['H1', 'H2'], $result[0]->getArrHierarquia());
  }

  /**
   * Testa a listagem de estruturas com repositório inválido.
   *
   * Este teste verifica se uma exceção é lançada quando o ID do repositório
   * de estruturas é inválido (null).
   */
  public function testListarEstruturasComRepositorioInvalido()
  {
    $idRepositorioEstrutura = null;

    // Configura as expectativas de exceção
    $this->expectException(InfraException::class);
    $this->expectExceptionMessage('Repositório de Estruturas inválido');

    // Executa o método sob teste
    $this->mockService->listarEstruturas($idRepositorioEstrutura);
  }

  /**
   * Testa a listagem de estruturas com erro na consulta.
   *
   * Este teste verifica se uma exceção é lançada corretamente quando ocorre
   * um erro durante a consulta de estruturas.
   */
  public function testListarEstruturasComErroNaConsulta()
  {
    $idRepositorioEstrutura = 1;

    // Configura o mock para lançar uma exceção no método consultarEstruturas
    $this->mockService
      ->expects($this->once())
      ->method('consultarEstruturas')
      ->willThrowException(new Exception('Erro na consulta'));

    // Configura as expectativas de exceção
    $this->expectException(InfraException::class);
    $this->expectExceptionMessage('Falha na obtenção de unidades externas');

    // Executa o método sob teste
    $this->mockService->listarEstruturas($idRepositorioEstrutura);
  }
}