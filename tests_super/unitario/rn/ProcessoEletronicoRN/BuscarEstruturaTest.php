<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de teste para o método buscarEstrutura da classe ProcessoEletronicoRN.
 *
 * Esta classe utiliza o PHPUnit para verificar o comportamento do método
 * `buscarEstrutura` em diferentes cenários, simulando respostas e verificando 
 * exceções e resultados esperados.
 */
class BuscarEstruturaTest extends TestCase
{
  /**
   * Mock da classe ProcessoEletronicoRN.
   *
   * Este mock é utilizado para simular comportamentos específicos da classe 
   * ProcessoEletronicoRN, evitando a execução de implementações reais e 
   * focando no teste do método buscarEstrutura.
   *
   * @var ProcessoEletronicoRN|\PHPUnit\Framework\MockObject\MockObject
   */
  private $mockService;

  /**
   * Configuração inicial do teste.
   *
   * Este método configura o mock da classe ProcessoEletronicoRN, redefinindo 
   * os métodos `get` e `buscarEstruturaRest` para possibilitar a simulação
   * dos cenários de teste.
   *
   * @return void
   */
  protected function setUp(): void
  {
    $this->mockService = $this->getMockBuilder(ProcessoEletronicoRN::class)
      ->onlyMethods(['get', 'buscarEstruturaRest'])
      ->getMock();
  }

  /**
   * Testa o comportamento do método buscarEstrutura em caso de sucesso.
   *
   * Simula uma resposta válida para o método `buscarEstruturaRest` e verifica
   * se o retorno do método testado é uma instância válida da classe EstruturaDTO.
   *
   * @return void
   */
  public function testBuscarEstruturaSucesso()
  {
    // Mock do retorno esperado da API
    $estruturaMock = [
      'numeroDeIdentificacaoDaEstrutura' => '12345',
      'nome' => 'Estrutura Teste',
      'sigla' => 'ET',
      'ativo' => true,
      'aptoParaReceber' => true,
      'codigoNoOrgaoEntidade' => 'CNOE123',
      'hierarquia' => [
        ['sigla' => 'Nivel1'],
        ['sigla' => 'Nivel2']
      ]
    ];

    // Mock do método buscarEstruturaRest
    $this->mockService->expects($this->once())
      ->method('buscarEstruturaRest')
      ->with(1, 'Estrutura Raiz')
      ->willReturn($estruturaMock);

    // Chamada do método
    $resultado = $this->mockService->buscarEstrutura(
      1, // idRepositorioEstrutura
      'Estrutura Raiz' // Nome ou identificador raiz
    );

    $this->assertInstanceOf(EstruturaDTO::class, $resultado, 'O retorno deve ser uma instância da classe EstruturaDTO.');
  }

  /**
   * Testa o comportamento do método buscarEstrutura com um repositório inválido.
   *
   * Verifica se o método lança a exceção correta ao receber um ID de repositório inválido.
   *
   * @return void
   */
  public function testBuscarEstruturaComRepositorioInvalido()
  {
    $this->expectException(InfraException::class);
    $this->expectExceptionMessage('Falha na obtenção de unidades externas');

    $this->mockService->buscarEstrutura(0); // Passando um ID inválido
  }

  /**
   * Testa o comportamento do método buscarEstrutura quando a estrutura não é encontrada.
   *
   * Simula o retorno `null` do método `buscarEstruturaRest` e verifica se o
   * método principal retorna `null` como esperado.
   *
   * @return void
   */
  public function testBuscarEstruturaNaoEncontrada()
  {
    // Mock para retorno nulo da API
    $this->mockService->expects($this->once())
      ->method('buscarEstruturaRest')
      ->with(1, 'Estrutura Raiz')
      ->willReturn(null);

    // Chamada do método
    $resultado = $this->mockService->buscarEstrutura(
      1, // idRepositorioEstrutura
      'Estrutura Raiz' // Nome ou identificador raiz
    );

    // Asserção de retorno nulo
    $this->assertNull($resultado);
  }

  /**
   * Testa o comportamento do método buscarEstrutura quando ocorre uma exceção.
   *
   * Simula uma exceção no método `get` e verifica se a exceção correta é lançada 
   * pelo método testado.
   *
   * @return void
   */
  public function testBuscarEstruturaLancaExcecao()
  {
    $this->mockService->expects($this->once())
      ->method('get')
      ->willThrowException(new Exception());

    $this->expectException(InfraException::class);
    $this->expectExceptionMessage('Falha na obtenção de unidades externas');

    $this->mockService->consultarEstrutura(159098, 152254, false);
  }
}