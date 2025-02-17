<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de Teste para a funcionalidade de consulta de hipóteses legais.
 *
 * Esta classe contém os testes unitários para o método `consultarHipotesesLegais`
 * da classe `ProcessoEletronicoRN`. São verificadas duas condições:
 * - Quando a consulta é realizada com sucesso e retorna um valor nulo.
 * - Quando ocorre uma exceção ao tentar realizar a consulta.
 */
class ConsultarHipotesesLegaisTest extends TestCase
{
    /**
     * @var ProcessoEletronicoRN Mock da classe ProcessoEletronicoRN para testes unitários.
     */
    private $mockService;

    /**
     * Configuração do ambiente de teste.
     *
     * Inicializa o mock do serviço ProcessoEletronicoRN, substituindo o método 'get'
     * para controlar o comportamento do teste.
     */
    protected function setUp(): void
    {
        $this->mockService = $this->getMockBuilder(ProcessoEletronicoRN::class)
                                  ->onlyMethods(['get'])
                                  ->getMock();
    }

    /**
     * Testa o sucesso da consulta de hipóteses legais.
     *
     * Este teste verifica se o método `consultarHipotesesLegais` retorna um array de hipoteses
     * quando o método 'get' é chamado.
     *
     * @return void
     */
    public function testConsultarHipotesesLegaisSucesso()
    {
        // Define o valor retornado pelo método 'get' mockado
        $mockResponse = [
            'hipoteseslegais' => [
            ]
        ];

        // Configura o mock para esperar que o método 'get' seja chamado uma vez e retorne $mockResponse
        $this->mockService->expects($this->once())
                          ->method('get')
                          ->willReturn($mockResponse);

        // Chama o método que está sendo testado
        $resultado = $this->mockService->consultarHipotesesLegais(true);

        // Verifica se o resultado é nulo, conforme esperado
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('hipoteseslegais', $resultado);
    }

    /**
     * Testa o sucesso da consulta de hipóteses legais com retorno vazio.
     *
     * Este teste verifica se o método `consultarHipotesesLegais` retorna um array vazio
     * quando o método 'get' é chamado.
     *
     * @return void
     */
    public function testConsultarHipotesesLegaisRetornoVazioSucesso()
    {
        // Define o valor retornado pelo método 'get' mockado
        $mockResponse = [];

        // Configura o mock para esperar que o método 'get' seja chamado uma vez e retorne $mockResponse
        $this->mockService->expects($this->once())
                          ->method('get')
                          ->willReturn($mockResponse);

        // Chama o método que está sendo testado
        $resultado = $this->mockService->consultarHipotesesLegais(true);

        // Verifica se o resultado é nulo, conforme esperado
        $this->assertIsArray($resultado);
        $this->assertEquals(0, count($resultado));
    }

    /**
     * Testa o lançamento de exceção ao tentar consultar as hipóteses legais.
     *
     * Este teste verifica se uma exceção é lançada corretamente quando ocorre um erro
     * ao tentar realizar a consulta (simulando a exceção gerada pelo método 'get').
     *
     * @return void
     * @throws InfraException Se a exceção de infra-estrutura for gerada.
     */
    public function testConsultarHipotesesLegaisLancaExcecao()
    {
        // Configura o mock para lançar uma exceção quando o método 'get' for chamado
        $this->mockService->expects($this->once())
                          ->method('get')
                          ->willThrowException(new Exception('Erro na requisição'));

        // Define a expectativa de que a exceção InfraException será lançada
        $this->expectException(InfraException::class);
        $this->expectExceptionMessage('Falha na obtenção de hipóteses legais');

        // Chama o método que deve lançar a exceção
        $this->mockService->consultarHipotesesLegais(true);
    }
}