<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de teste para o m�todo buscarEstrutura da classe ProcessoEletronicoRN.
 *
 * Esta classe utiliza o PHPUnit para verificar o comportamento do m�todo
 * `buscarEstrutura` em diferentes cen�rios, simulando respostas e verificando 
 * exce��es e resultados esperados.
 */
class BuscarEstruturaTest extends TestCase
{
  /**
   * Mock da classe ProcessoEletronicoRN.
   *
   * Este mock � utilizado para simular comportamentos espec�ficos da classe 
   * ProcessoEletronicoRN, evitando a execu��o de implementa��es reais e 
   * focando no teste do m�todo buscarEstrutura.
   *
   * @var ProcessoEletronicoRN|\PHPUnit\Framework\MockObject\MockObject
   */
  private $mockService;

  /**
   * Configura��o inicial do teste.
   *
   * Este m�todo configura o mock da classe ProcessoEletronicoRN, redefinindo 
   * os m�todos `get` e `buscarEstruturaRest` para possibilitar a simula��o
   * dos cen�rios de teste.
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
   * Testa o comportamento do m�todo buscarEstrutura em caso de sucesso.
   *
   * Simula uma resposta v�lida para o m�todo `buscarEstruturaRest` e verifica
   * se o retorno do m�todo testado � uma inst�ncia v�lida da classe EstruturaDTO.
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

    // Mock do m�todo buscarEstruturaRest
    $this->mockService->expects($this->once())
      ->method('buscarEstruturaRest')
      ->with(1, 'Estrutura Raiz')
      ->willReturn($estruturaMock);

    // Chamada do m�todo
    $resultado = $this->mockService->buscarEstrutura(
      1, // idRepositorioEstrutura
      'Estrutura Raiz' // Nome ou identificador raiz
    );

    $this->assertInstanceOf(EstruturaDTO::class, $resultado, 'O retorno deve ser uma inst�ncia da classe EstruturaDTO.');
  }

  /**
   * Testa o comportamento do m�todo buscarEstrutura com um reposit�rio inv�lido.
   *
   * Verifica se o m�todo lan�a a exce��o correta ao receber um ID de reposit�rio inv�lido.
   *
   * @return void
   */
  public function testBuscarEstruturaComRepositorioInvalido()
  {
    $this->expectException(InfraException::class);
    $this->expectExceptionMessage('Falha na obten��o de unidades externas');

    $this->mockService->buscarEstrutura(0); // Passando um ID inv�lido
  }

  /**
   * Testa o comportamento do m�todo buscarEstrutura quando a estrutura n�o � encontrada.
   *
   * Simula o retorno `null` do m�todo `buscarEstruturaRest` e verifica se o
   * m�todo principal retorna `null` como esperado.
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

    // Chamada do m�todo
    $resultado = $this->mockService->buscarEstrutura(
      1, // idRepositorioEstrutura
      'Estrutura Raiz' // Nome ou identificador raiz
    );

    // Asser��o de retorno nulo
    $this->assertNull($resultado);
  }

  /**
   * Testa o comportamento do m�todo buscarEstrutura quando ocorre uma exce��o.
   *
   * Simula uma exce��o no m�todo `get` e verifica se a exce��o correta � lan�ada 
   * pelo m�todo testado.
   *
   * @return void
   */
  public function testBuscarEstruturaLancaExcecao()
  {
    $this->mockService->expects($this->once())
      ->method('get')
      ->willThrowException(new Exception());

    $this->expectException(InfraException::class);
    $this->expectExceptionMessage('Falha na obten��o de unidades externas');

    $this->mockService->consultarEstrutura(159098, 152254, false);
  }
}