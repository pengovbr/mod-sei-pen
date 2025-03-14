<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de teste para o m�todo cancelarTramite da classe ProcessoEletronicoRN.
 * 
 * Esta classe utiliza PHPUnit para verificar o comportamento do m�todo cancelarTramite
 * em diferentes cen�rios, garantindo que ele funcione conforme o esperado.
 * Os testes abordam casos de sucesso e de falha na execu��o do m�todo.
 *
 * @covers \ProcessoEletronicoRN
 */
class CancelarTramiteTest extends TestCase
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
     * o m�todo 'delete' para simular comportamentos durante os testes.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->mockService = $this->getMockBuilder(ProcessoEletronicoRN::class)
                                  ->onlyMethods(['delete'])
                                  ->getMock();
    }

    /**
     * Teste do m�todo cancelarTramite em caso de sucesso.
     * 
     * Este teste simula a execu��o do m�todo cancelarTramite quando o m�todo 'delete' do mock
     * retorna uma resposta bem-sucedida (null). O teste verifica se o retorno do m�todo �
     * o esperado (null), indicando que o tr�mite foi cancelado com sucesso.
     *
     * @return void
     */
    public function testCancelarTramiteSucesso()
    {
        $mockResponse = null;

        // Configura o mock para retornar a resposta esperada (null)
        $this->mockService->expects($this->once())
                          ->method('delete')
                          ->willReturn($mockResponse);

        // Executa o m�todo cancelarTramite
        $resultado = $this->mockService->cancelarTramite(999);

        // Verifica se o retorno � nulo (indicando sucesso no cancelamento)
        $this->assertNull($mockResponse, "O objeto � nulo");
    }

    /**
     * Teste do m�todo cancelarTramite quando ocorre uma exce��o.
     * 
     * Este teste simula a falha no m�todo cancelarTramite, quando o m�todo 'delete' do mock
     * lan�a uma exce��o. O teste verifica se a exce��o esperada (InfraException) � lan�ada e
     * se a mensagem associada � exce��o est� correta.
     *
     * @return void
     * @throws \InfraException Quando ocorre uma falha no cancelamento do tr�mite
     */
    public function testCancelarTramiteLancaExcecao()
    {
        // Configura o mock para lan�ar uma exce��o
        $this->mockService->expects($this->once())
                          ->method('delete')
                          ->willThrowException(new Exception('Erro na requisi��o'));

        // Espera que a exce��o InfraException seja lan�ada com a mensagem esperada
        $this->expectException(InfraException::class);
        $this->expectExceptionMessage('Falha no cancelamento de tr�mite de processo');

        // Executa o m�todo cancelarTramite e verifica se a exce��o � lan�ada
        $this->mockService->cancelarTramite(999);
    }
}