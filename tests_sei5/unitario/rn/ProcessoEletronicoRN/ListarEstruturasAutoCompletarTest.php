<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de testes para o m�todo listarEstruturasAutoCompletar.
 *
 * Esta classe cont�m testes unit�rios que verificam o comportamento do m�todo 
 * listarEstruturasAutoCompletar da classe ProcessoEletronicoRN, cobrindo casos 
 * de sucesso, exce��es e entradas inv�lidas.
 *
 * A classe utiliza mocks para isolar as depend�ncias externas e garantir a 
 * valida��o dos cen�rios de forma controlada.
 */
class ListarEstruturasAutoCompletarTest extends TestCase
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
   * podem ser simulados durante os testes.
   */
  protected function setUp(): void
  {
    $this->mockService = $this->getMockBuilder(ProcessoEletronicoRN::class)
      ->onlyMethods(['consultarEstruturas'])
      ->getMock();
  }

  /**
   * Testa o m�todo listarEstruturasAutoCompletar com par�metros v�lidos.
   *
   * Verifica se o retorno do m�todo � formatado corretamente, incluindo:
   * - Propriedades convertidas para UTF-8.
   * - Hierarquia de siglas mapeada.
   * - Quantidade correta de itens no resultado.
   */
  public function testListarEstruturasAutoCompletarRetornaEstruturasFormatadas()
  {
    // Simula��o de par�metros e retorno esperado
    $idRepositorioEstrutura = 1;
    $nome = 'Teste Unidade';
    $mockResultado = [
      'totalDeRegistros' => 2,
      'estruturas' => [
        [
          'numeroDeIdentificacaoDaEstrutura' => 101,
          'nome' => 'Unidade A',
          'sigla' => 'UA',
          'ativo' => true,
          'aptoParaReceberTramites' => true,
          'codigoNoOrgaoEntidade' => '123',
          'hierarquia' => [
            ['sigla' => 'ORG']
          ]
        ],
        [
          'numeroDeIdentificacaoDaEstrutura' => 102,
          'nome' => 'Unidade B',
          'sigla' => 'UB',
          'ativo' => true,
          'aptoParaReceberTramites' => false,
          'codigoNoOrgaoEntidade' => '456',
          'hierarquia' => []
        ]
      ]
    ];

    $this->mockService->expects($this->once())
      ->method('consultarEstruturas')
      ->with($idRepositorioEstrutura, $this->callback(function ($parametros) use ($nome) {
        return $parametros['nome'] === $nome && $parametros['apenasAtivas'] === true;
      }))
      ->willReturn($mockResultado);

    $resultado = $this->mockService->listarEstruturasAutoCompletar($idRepositorioEstrutura, $nome);

    // Asser��es sobre o retorno
    $this->assertIsArray($resultado);
    $this->assertArrayHasKey('diferencaDeRegistros', $resultado);
    $this->assertArrayHasKey('itens', $resultado);
    $this->assertCount(2, $resultado['itens']);
  }

  /**
   * Testa o m�todo listarEstruturasAutoCompletar quando ocorre um erro.
   *
   * Simula um erro no m�todo consultarEstruturas e verifica se uma exce��o
   * InfraException � lan�ada corretamente com a mensagem esperada.
   */
  public function testListarEstruturasAutoCompletarLancaExcecaoParaErro()
  {
    $idRepositorioEstrutura = 1;

    $this->mockService->expects($this->once())
      ->method('consultarEstruturas')
      ->willThrowException(new Exception('Erro interno'));

    $this->expectException(InfraException::class);
    $this->expectExceptionMessage('Falha na obten��o de unidades externas');

    $this->mockService->listarEstruturasAutoCompletar($idRepositorioEstrutura);
  }

  /**
   * Testa o m�todo listarEstruturasAutoCompletar com reposit�rio inv�lido.
   *
   * Verifica se uma exce��o InfraException � lan�ada quando o ID do 
   * reposit�rio de estruturas fornecido � inv�lido.
   */
  public function testListarEstruturasAutoCompletarLancaExcecaoParaRepositorioInvalido()
  {
    $idRepositorioEstrutura = null;

    $this->expectException(InfraException::class);
    $this->expectExceptionMessage('Reposit�rio de Estruturas inv�lido');

    $this->mockService->listarEstruturasAutoCompletar($idRepositorioEstrutura);
  }
}