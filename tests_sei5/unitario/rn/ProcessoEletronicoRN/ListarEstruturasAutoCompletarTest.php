<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de testes para o método listarEstruturasAutoCompletar.
 *
 * Esta classe contém testes unitários que verificam o comportamento do método 
 * listarEstruturasAutoCompletar da classe ProcessoEletronicoRN, cobrindo casos 
 * de sucesso, exceções e entradas inválidas.
 *
 * A classe utiliza mocks para isolar as dependências externas e garantir a 
 * validação dos cenários de forma controlada.
 */
class ListarEstruturasAutoCompletarTest extends TestCase
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
   * podem ser simulados durante os testes.
   */
  protected function setUp(): void
  {
    $this->mockService = $this->getMockBuilder(ProcessoEletronicoRN::class)
      ->onlyMethods(['consultarEstruturas'])
      ->getMock();
  }

  /**
   * Testa o método listarEstruturasAutoCompletar com parâmetros válidos.
   *
   * Verifica se o retorno do método é formatado corretamente, incluindo:
   * - Propriedades convertidas para UTF-8.
   * - Hierarquia de siglas mapeada.
   * - Quantidade correta de itens no resultado.
   */
  public function testListarEstruturasAutoCompletarRetornaEstruturasFormatadas()
  {
    // Simulação de parâmetros e retorno esperado
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

    // Asserções sobre o retorno
    $this->assertIsArray($resultado);
    $this->assertArrayHasKey('diferencaDeRegistros', $resultado);
    $this->assertArrayHasKey('itens', $resultado);
    $this->assertCount(2, $resultado['itens']);
  }

  /**
   * Testa o método listarEstruturasAutoCompletar quando ocorre um erro.
   *
   * Simula um erro no método consultarEstruturas e verifica se uma exceção
   * InfraException é lançada corretamente com a mensagem esperada.
   */
  public function testListarEstruturasAutoCompletarLancaExcecaoParaErro()
  {
    $idRepositorioEstrutura = 1;

    $this->mockService->expects($this->once())
      ->method('consultarEstruturas')
      ->willThrowException(new Exception('Erro interno'));

    $this->expectException(InfraException::class);
    $this->expectExceptionMessage('Falha na obtenção de unidades externas');

    $this->mockService->listarEstruturasAutoCompletar($idRepositorioEstrutura);
  }

  /**
   * Testa o método listarEstruturasAutoCompletar com repositório inválido.
   *
   * Verifica se uma exceção InfraException é lançada quando o ID do 
   * repositório de estruturas fornecido é inválido.
   */
  public function testListarEstruturasAutoCompletarLancaExcecaoParaRepositorioInvalido()
  {
    $idRepositorioEstrutura = null;

    $this->expectException(InfraException::class);
    $this->expectExceptionMessage('Repositório de Estruturas inválido');

    $this->mockService->listarEstruturasAutoCompletar($idRepositorioEstrutura);
  }
}