<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de testes para o m�todo `consultarRepositoriosDeEstruturas`.
 *
 * Esta classe utiliza PHPUnit para validar o comportamento do m�todo
 * `consultarRepositoriosDeEstruturas` da classe `ProcessoEletronicoRN`.
 *
 * O m�todo � respons�vel por buscar informa��es sobre reposit�rios de estruturas
 * organizacionais, cobrindo cen�rios de sucesso, aus�ncia de resultados e tratamento de erros.
 *
 * A classe utiliza mocks para simular intera��es com a depend�ncia externa
 * `ProcessoEletronicoRN`.
 */
class ConsultarRepositoriosDeEstruturasTest extends TestCase
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
     * Testa o m�todo `consultarRepositoriosDeEstruturas` com retorno bem-sucedido.
     *
     * Verifica se o m�todo:
     * - Retorna uma inst�ncia de `RepositorioDTO`.
     * - Preenche os atributos da inst�ncia com os valores corretos.
     */
    public function testConsultarRepositoriosDeEstruturasComSucesso()
    {
        // Mock do retorno esperado do m�todo get
        $resultadoMock = [
            [
                'id' => 5,
                'nome' => 'Reposit�rio 1',
                'ativo' => true
            ]
        ];

        $this->mockService->expects($this->once())
            ->method('get')
            ->with('repositorios-de-estruturas', ['ativo' => true])
            ->willReturn($resultadoMock);

        // Chamada do m�todo
        $resultado = $this->mockService->consultarRepositoriosDeEstruturas(5);

        // Valida��es
        $this->assertInstanceOf(RepositorioDTO::class, $resultado);
        $this->assertEquals(5, $resultado->getNumId());
        $this->assertEquals(
            mb_convert_encoding('Reposit�rio 1', 'ISO-8859-1', 'UTF-8'),
            $resultado->getStrNome()
        );
        $this->assertTrue($resultado->getBolAtivo());
    }

    /**
     * Testa o m�todo `consultarRepositoriosDeEstruturas` quando n�o h� resultados.
     *
     * Verifica se o m�todo:
     * - Retorna `null` quando n�o h� reposit�rios dispon�veis.
     */
    public function testConsultarRepositoriosDeEstruturasSemResultados()
    {
        // Mock do retorno esperado do m�todo get
        $resultadoMock = [];

        $this->mockService->expects($this->once())
            ->method('get')
            ->with('repositorios-de-estruturas', ['ativo' => true])
            ->willReturn($resultadoMock);

        // Chamada do m�todo
        $resultado = $this->mockService->consultarRepositoriosDeEstruturas(123);

        // Valida��es
        $this->assertNull($resultado);
    }

    /**
     * Testa o m�todo `consultarRepositoriosDeEstruturas` quando ocorre um erro.
     *
     * Verifica se o m�todo:
     * - Lan�a a exce��o esperada (`InfraException`).
     * - Cont�m a mensagem correta de erro.
     */
    public function testConsultarRepositoriosDeEstruturasComErro()
    {
        // Configura��o do mock para lan�ar uma exce��o
        $this->mockService->expects($this->once())
            ->method('get')
            ->willThrowException(new Exception('Erro simulado'));

        // Expectativa de exce��o
        $this->expectException(InfraException::class);
        $this->expectExceptionMessage('Falha na obten��o dos Reposit�rios de Estruturas Organizacionais');

        // Chamada do m�todo (deve lan�ar exce��o)
        $this->mockService->consultarRepositoriosDeEstruturas(123);
    }
}