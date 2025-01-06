<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de testes para o método `cienciaRecusa`.
 *
 * Esta classe utiliza o PHPUnit para validar o comportamento do método
 * `cienciaRecusa` da classe `ProcessoEletronicoRN`.
 *
 * O método é responsável por registrar a ciência de uma recusa em um trâmite,
 * cobrindo cenários de sucesso e tratamento de erros.
 *
 * A classe utiliza mocks para simular interações com a dependência externa
 * `ProcessoEletronicoRN`.
 */
class CienciaRecusaTest extends TestCase
{
    /**
     * Mock da classe `ProcessoEletronicoRN`.
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
     * Este método cria o mock da classe `ProcessoEletronicoRN` e define o
     * método que pode ser simulado durante os testes.
     *
     * Métodos simulados:
     * - `get`
     */
    protected function setUp(): void
    {
        $this->mockService = $this->getMockBuilder(ProcessoEletronicoRN::class)
                                  ->onlyMethods(['get'])
                                  ->getMock();
    }

    /**
     * Testa o método `cienciaRecusa` com retorno bem-sucedido.
     *
     * Verifica se o método:
     * - Retorna um array com as chaves `status` e `mensagem`.
     * - Contém os valores esperados no retorno.
     */
    public function testCienciaRecusaComSucesso()
    {
        // Mock do retorno esperado do método get
        $resultadoMock = [
            'status' => 'sucesso',
            'mensagem' => 'Ciência registrada com sucesso.'
        ];

        $this->mockService->expects($this->once())
            ->method('get')
            ->with('tramites/123/ciencia', ['IDT' => 123])
            ->willReturn($resultadoMock);

        // Chamada do método
        $resultado = $this->mockService->cienciaRecusa(123);

        // Validações
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertEquals('sucesso', $resultado['status']);
        $this->assertEquals('Ciência registrada com sucesso.', $resultado['mensagem']);
    }

    /**
     * Testa o método `cienciaRecusa` quando ocorre um erro.
     *
     * Verifica se o método:
     * - Lança a exceção esperada (`InfraException`).
     * - Contém a mensagem correta de erro.
     */
    public function testCienciaRecusaComErro()
    {
        // Configuração do mock para lançar uma exceção
        $this->mockService->expects($this->once())
            ->method('get')
            ->willThrowException(new Exception('Erro simulado'));

        // Expectativa de exceção
        $this->expectException(InfraException::class);
        $this->expectExceptionMessage('Falha no registro de ciência da recusa de trâmite');

        // Chamada do método (deve lançar exceção)
        $this->mockService->cienciaRecusa(123);
    }
}