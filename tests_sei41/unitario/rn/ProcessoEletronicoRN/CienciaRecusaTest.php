<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de testes para o m�todo `cienciaRecusa`.
 *
 * Esta classe utiliza o PHPUnit para validar o comportamento do m�todo
 * `cienciaRecusa` da classe `ProcessoEletronicoRN`.
 *
 * O m�todo � respons�vel por registrar a ci�ncia de uma recusa em um tr�mite,
 * cobrindo cen�rios de sucesso e tratamento de erros.
 *
 * A classe utiliza mocks para simular intera��es com a depend�ncia externa
 * `ProcessoEletronicoRN`.
 */
class CienciaRecusaTest extends TestCase
{
    /**
     * Mock da classe `ProcessoEletronicoRN`.
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
     * Este m�todo cria o mock da classe `ProcessoEletronicoRN` e define o
     * m�todo que pode ser simulado durante os testes.
     *
     * M�todos simulados:
     * - `get`
     */
    protected function setUp(): void
    {
        $this->mockService = $this->getMockBuilder(ProcessoEletronicoRN::class)
                                  ->onlyMethods(['get'])
                                  ->getMock();
    }

    /**
     * Testa o m�todo `cienciaRecusa` com retorno bem-sucedido.
     *
     * Verifica se o m�todo:
     * - Retorna um array com as chaves `status` e `mensagem`.
     * - Cont�m os valores esperados no retorno.
     */
    public function testCienciaRecusaComSucesso()
    {
        // Mock do retorno esperado do m�todo get
        $resultadoMock = [
            'status' => 'sucesso',
            'mensagem' => 'Ci�ncia registrada com sucesso.'
        ];

        $this->mockService->expects($this->once())
            ->method('get')
            ->with('tramites/123/ciencia', ['IDT' => 123])
            ->willReturn($resultadoMock);

        // Chamada do m�todo
        $resultado = $this->mockService->cienciaRecusa(123);

        // Valida��es
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertEquals('sucesso', $resultado['status']);
        $this->assertEquals('Ci�ncia registrada com sucesso.', $resultado['mensagem']);
    }

    /**
     * Testa o m�todo `cienciaRecusa` quando ocorre um erro.
     *
     * Verifica se o m�todo:
     * - Lan�a a exce��o esperada (`InfraException`).
     * - Cont�m a mensagem correta de erro.
     */
    public function testCienciaRecusaComErro()
    {
        // Configura��o do mock para lan�ar uma exce��o
        $this->mockService->expects($this->once())
            ->method('get')
            ->willThrowException(new Exception('Erro simulado'));

        // Expectativa de exce��o
        $this->expectException(InfraException::class);
        $this->expectExceptionMessage('Falha no registro de ci�ncia da recusa de tr�mite');

        // Chamada do m�todo (deve lan�ar exce��o)
        $this->mockService->cienciaRecusa(123);
    }
}