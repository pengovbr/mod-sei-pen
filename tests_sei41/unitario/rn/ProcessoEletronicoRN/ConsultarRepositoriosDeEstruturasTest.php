<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de testes para o método `consultarRepositoriosDeEstruturas`.
 *
 * Esta classe utiliza PHPUnit para validar o comportamento do método
 * `consultarRepositoriosDeEstruturas` da classe `ProcessoEletronicoRN`.
 *
 * O método é responsável por buscar informações sobre repositórios de estruturas
 * organizacionais, cobrindo cenários de sucesso, ausência de resultados e tratamento de erros.
 *
 * A classe utiliza mocks para simular interações com a dependência externa
 * `ProcessoEletronicoRN`.
 */
class ConsultarRepositoriosDeEstruturasTest extends TestCase
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
     * Testa o método `consultarRepositoriosDeEstruturas` com retorno bem-sucedido.
     *
     * Verifica se o método:
     * - Retorna uma instância de `RepositorioDTO`.
     * - Preenche os atributos da instância com os valores corretos.
     */
    public function testConsultarRepositoriosDeEstruturasComSucesso()
    {
        // Mock do retorno esperado do método get
        $resultadoMock = [
            [
                'id' => 5,
                'nome' => 'Repositório 1',
                'ativo' => true
            ]
        ];

        $this->mockService->expects($this->once())
            ->method('get')
            ->with('repositorios-de-estruturas', ['ativo' => true])
            ->willReturn($resultadoMock);

        // Chamada do método
        $resultado = $this->mockService->consultarRepositoriosDeEstruturas(5);

        // Validações
        $this->assertInstanceOf(RepositorioDTO::class, $resultado);
        $this->assertEquals(5, $resultado->getNumId());
        $this->assertEquals(
            mb_convert_encoding('Repositório 1', 'ISO-8859-1', 'UTF-8'),
            $resultado->getStrNome()
        );
        $this->assertTrue($resultado->getBolAtivo());
    }

    /**
     * Testa o método `consultarRepositoriosDeEstruturas` quando não há resultados.
     *
     * Verifica se o método:
     * - Retorna `null` quando não há repositórios disponíveis.
     */
    public function testConsultarRepositoriosDeEstruturasSemResultados()
    {
        // Mock do retorno esperado do método get
        $resultadoMock = [];

        $this->mockService->expects($this->once())
            ->method('get')
            ->with('repositorios-de-estruturas', ['ativo' => true])
            ->willReturn($resultadoMock);

        // Chamada do método
        $resultado = $this->mockService->consultarRepositoriosDeEstruturas(123);

        // Validações
        $this->assertNull($resultado);
    }

    /**
     * Testa o método `consultarRepositoriosDeEstruturas` quando ocorre um erro.
     *
     * Verifica se o método:
     * - Lança a exceção esperada (`InfraException`).
     * - Contém a mensagem correta de erro.
     */
    public function testConsultarRepositoriosDeEstruturasComErro()
    {
        // Configuração do mock para lançar uma exceção
        $this->mockService->expects($this->once())
            ->method('get')
            ->willThrowException(new Exception('Erro simulado'));

        // Expectativa de exceção
        $this->expectException(InfraException::class);
        $this->expectExceptionMessage('Falha na obtenção dos Repositórios de Estruturas Organizacionais');

        // Chamada do método (deve lançar exceção)
        $this->mockService->consultarRepositoriosDeEstruturas(123);
    }
}