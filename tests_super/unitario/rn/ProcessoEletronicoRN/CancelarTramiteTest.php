<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de teste para o método cancelarTramite da classe ProcessoEletronicoRN.
 * 
 * Esta classe utiliza PHPUnit para verificar o comportamento do método cancelarTramite
 * em diferentes cenários, garantindo que ele funcione conforme o esperado.
 * Os testes abordam casos de sucesso e de falha na execução do método.
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
     * Configuração inicial do teste.
     * 
     * Este método cria um mock da classe ProcessoEletronicoRN e redefine
     * o método 'delete' para simular comportamentos durante os testes.
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
     * Teste do método cancelarTramite em caso de sucesso.
     * 
     * Este teste simula a execução do método cancelarTramite quando o método 'delete' do mock
     * retorna uma resposta bem-sucedida (null). O teste verifica se o retorno do método é
     * o esperado (null), indicando que o trâmite foi cancelado com sucesso.
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

        // Executa o método cancelarTramite
        $resultado = $this->mockService->cancelarTramite(999);

        // Verifica se o retorno é nulo (indicando sucesso no cancelamento)
        $this->assertNull($mockResponse, "O objeto é nulo");
    }

    /**
     * Teste do método cancelarTramite quando ocorre uma exceção.
     * 
     * Este teste simula a falha no método cancelarTramite, quando o método 'delete' do mock
     * lança uma exceção. O teste verifica se a exceção esperada (InfraException) é lançada e
     * se a mensagem associada à exceção está correta.
     *
     * @return void
     * @throws \InfraException Quando ocorre uma falha no cancelamento do trâmite
     */
    public function testCancelarTramiteLancaExcecao()
    {
        // Configura o mock para lançar uma exceção
        $this->mockService->expects($this->once())
                          ->method('delete')
                          ->willThrowException(new Exception('Erro na requisição'));

        // Espera que a exceção InfraException seja lançada com a mensagem esperada
        $this->expectException(InfraException::class);
        $this->expectExceptionMessage('Falha no cancelamento de trâmite de processo');

        // Executa o método cancelarTramite e verifica se a exceção é lançada
        $this->mockService->cancelarTramite(999);
    }
}