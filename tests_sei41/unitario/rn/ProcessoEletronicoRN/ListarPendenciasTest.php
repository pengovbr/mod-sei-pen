<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de teste para o m�todo listarPendencias da classe ProcessoEletronicoRN.
 * 
 * Esta classe utiliza PHPUnit para verificar o comportamento do m�todo listarPendencias
 * em diferentes cen�rios, garantindo que ele funcione conforme o esperado.
 */
class ListarPendenciasTest extends TestCase
{
    /**
     * Mock da classe ProcessoEletronicoRN.
     * 
     * @var ProcessoEletronicoRN|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockService;

    /**
     * Configura��o inicial do teste.
     * 
     * Este m�todo cria um mock da classe ProcessoEletronicoRN e redefine
     * o m�todo 'get' para simular comportamentos durante os testes.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->mockService = $this->getMockBuilder(ProcessoEletronicoRN::class)
                                  ->onlyMethods(['get'])
                                  ->getMock();
    }

    public function testListarPendenciasSucesso()
    {
        $mockResponse = [
            [
                'status' => 2,
                'IDT' => 999
            ]
        ];

        // Configura o mock para retornar a resposta
        $this->mockService->expects($this->once())
                          ->method('get')
                          ->willReturn($mockResponse);

        $resultado = $this->mockService->listarPendencias(true);

        $this->assertIsArray($resultado, 'O retorno deve ser um array.');
        $this->assertCount(count($mockResponse), $resultado, 'A quantidade de objetos no retorno est� incorreta.');
        $this->assertInstanceOf(PendenciaDTO::class, $resultado[0], 'O primeiro objeto na lista deve ser uma inst�ncia da classe RepositorioDTO.');
    }

    public function testListarPendenciasLancaExcecao()
    {
        $this->mockService->expects($this->once())
                          ->method('get')
                          ->willThrowException(new Exception('Erro na requisi��o'));

        $this->expectException(InfraException::class);
        $this->expectExceptionMessage('Falha na listagem de pend�ncias de tr�mite de processos');

        $this->mockService->listarPendencias(true);
    }
}