<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de teste para o método de recebimento de recibo de trâmite.
 *
 * Esta classe realiza testes unitários para métodos relacionados ao 
 * recebimento de recibos de trâmite utilizando mocks para simular 
 * dependências e comportamento.
 */
class ReceberReciboDeTramiteTest extends TestCase
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
   *
   * @return void
   */
  protected function setUp(): void
  {
    $this->mockService = $this->getMockBuilder(ProcessoEletronicoRN::class)
      ->onlyMethods(['get', 'converterArrayParaObjeto'])
      ->getMock();
  }

  /**
   * Testa o recebimento de recibo de trâmite com sucesso.
   *
   * Simula a execução do método `get` e o retorno de um objeto 
   * convertido a partir de um array, verificando se o resultado 
   * final corresponde ao esperado.
   *
   * @return void
   */
  public function testReceberReciboDeTramiteComSucesso()
  {
    // Mock do retorno esperado do método get
    $resultadoMock = [
      'recibo' => [
        'hashesDosComponentesDigitais' => ['hash123'],
        'outroDado' => 'valor'
      ]
    ];

    $this->mockService->expects($this->once())
      ->method('get')
      ->willReturn($resultadoMock);

    // Substituir o método estático converterArrayParaObjeto
    $resultadoObjetoMock = (object)[
      'recibo' => (object)[
        'hashDoComponenteDigital' => 'hash123',
        'outroDado' => 'valor'
      ]
    ];

    $this->mockService->expects($this->once())
      ->method('converterArrayParaObjeto')
      ->willReturn($resultadoObjetoMock);

    // Chamada do método
    $resultado = $this->mockService->receberReciboDeTramite(123);

    // Asserções
    $this->assertIsObject($resultado);
    $this->assertEquals('hash123', $resultado->recibo->hashDoComponenteDigital);
    $this->assertEquals('valor', $resultado->recibo->outroDado);
  }

  /**
   * Testa o cenário em que ocorre um erro ao receber o recibo de trâmite.
   *
   * Simula uma exceção no método `get` e verifica se a exceção correta é
   * lançada pelo método testado.
   *
   * @return void
   */
  public function testReceberReciboDeTramiteComErro()
  {
    $this->mockService->expects($this->once())
      ->method('get')
      ->willThrowException(new Exception('Erro simulado'));

    // Verifica se a exceção esperada é lançada
    $this->expectException(InfraException::class);
    $this->expectExceptionMessage('Falha no recebimento de recibo de trâmite.');

    $this->mockService->receberReciboDeTramite(123);
  }
}