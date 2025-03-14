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
      ->onlyMethods(['consultarEstruturas'])
      ->getMock();
  }

  /**
   * Testa a listagem de estruturas com sucesso.
   *
   * Este teste verifica se o m�todo listarEstruturas retorna corretamente
   * as estruturas esperadas quando os dados s�o fornecidos corretamente.
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

    // Define a expectativa para o mock do m�todo consultarEstruturas
    $this->mockService
      ->expects($this->once())
      ->method('consultarEstruturas')
      ->willReturn($mockRetornoConsulta);

    // Executa o m�todo sob teste
    $result = $this->mockService->listarEstruturas($idRepositorioEstrutura, $nome);

    // Valida��es dos resultados
    $this->assertCount(1, $result);
    $this->assertInstanceOf(EstruturaDTO::class, $result[0]);
    $this->assertEquals(123, $result[0]->getNumNumeroDeIdentificacaoDaEstrutura());
    $this->assertEquals('Estrutura 1', $result[0]->getStrNome());
    $this->assertEquals('E1', $result[0]->getStrSigla());
    $this->assertEquals(['H1', 'H2'], $result[0]->getArrHierarquia());
  }

  /**
   * Testa a listagem de estruturas com reposit�rio inv�lido.
   *
   * Este teste verifica se uma exce��o � lan�ada quando o ID do reposit�rio
   * de estruturas � inv�lido (null).
   */
  public function testListarEstruturasComRepositorioInvalido()
  {
    $idRepositorioEstrutura = null;

    // Configura as expectativas de exce��o
    $this->expectException(InfraException::class);
    $this->expectExceptionMessage('Reposit�rio de Estruturas inv�lido');

    // Executa o m�todo sob teste
    $this->mockService->listarEstruturas($idRepositorioEstrutura);
  }

  /**
   * Testa a listagem de estruturas com erro na consulta.
   *
   * Este teste verifica se uma exce��o � lan�ada corretamente quando ocorre
   * um erro durante a consulta de estruturas.
   */
  public function testListarEstruturasComErroNaConsulta()
  {
    $idRepositorioEstrutura = 1;

    // Configura o mock para lan�ar uma exce��o no m�todo consultarEstruturas
    $this->mockService
      ->expects($this->once())
      ->method('consultarEstruturas')
      ->willThrowException(new Exception('Erro na consulta'));

    // Configura as expectativas de exce��o
    $this->expectException(InfraException::class);
    $this->expectExceptionMessage('Falha na obten��o de unidades externas');

    // Executa o m�todo sob teste
    $this->mockService->listarEstruturas($idRepositorioEstrutura);
  }
}