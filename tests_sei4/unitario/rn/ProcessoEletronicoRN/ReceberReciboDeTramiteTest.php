<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de teste para o m�todo de recebimento de recibo de tr�mite.
 *
 * Esta classe realiza testes unit�rios para m�todos relacionados ao 
 * recebimento de recibos de tr�mite utilizando mocks para simular 
 * depend�ncias e comportamento.
 */
class ReceberReciboDeTramiteTest extends TestCase
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
   * Testa o recebimento de recibo de tr�mite com sucesso.
   *
   * Simula a execu��o do m�todo `get` e o retorno de um objeto 
   * convertido a partir de um array, verificando se o resultado 
   * final corresponde ao esperado.
   *
   * @return void
   */
  public function testReceberReciboDeTramiteComSucesso()
  {
    // Mock do retorno esperado do m�todo get
    $resultadoMock = [
      'recibo' => [
        'hashesDosComponentesDigitais' => ['hash123'],
        'outroDado' => 'valor'
      ]
    ];

    $this->mockService->expects($this->once())
      ->method('get')
      ->willReturn($resultadoMock);

    // Substituir o m�todo est�tico converterArrayParaObjeto
    $resultadoObjetoMock = (object)[
      'recibo' => (object)[
        'hashDoComponenteDigital' => 'hash123',
        'outroDado' => 'valor'
      ]
    ];

    $this->mockService->expects($this->once())
      ->method('converterArrayParaObjeto')
      ->willReturn($resultadoObjetoMock);

    // Chamada do m�todo
    $resultado = $this->mockService->receberReciboDeTramite(123);

    // Asser��es
    $this->assertIsObject($resultado);
    $this->assertEquals('hash123', $resultado->recibo->hashDoComponenteDigital);
    $this->assertEquals('valor', $resultado->recibo->outroDado);
  }

  /**
   * Testa o cen�rio em que ocorre um erro ao receber o recibo de tr�mite.
   *
   * Simula uma exce��o no m�todo `get` e verifica se a exce��o correta �
   * lan�ada pelo m�todo testado.
   *
   * @return void
   */
  public function testReceberReciboDeTramiteComErro()
  {
    $this->mockService->expects($this->once())
      ->method('get')
      ->willThrowException(new Exception('Erro simulado'));

    // Verifica se a exce��o esperada � lan�ada
    $this->expectException(InfraException::class);
    $this->expectExceptionMessage('Falha no recebimento de recibo de tr�mite.');

    $this->mockService->receberReciboDeTramite(123);
  }
}