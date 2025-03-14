<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de Teste para a funcionalidade de consulta de hip�teses legais.
 *
 * Esta classe cont�m os testes unit�rios para o m�todo `consultarHipotesesLegais`
 * da classe `ProcessoEletronicoRN`. S�o verificadas duas condi��es:
 * - Quando a consulta � realizada com sucesso e retorna um valor nulo.
 * - Quando ocorre uma exce��o ao tentar realizar a consulta.
 */
class ConsultarHipotesesLegaisTest extends TestCase
{
    /**
     * @var ProcessoEletronicoRN Mock da classe ProcessoEletronicoRN para testes unit�rios.
     */
    private $mockService;

    /**
     * Configura��o do ambiente de teste.
     *
     * Inicializa o mock do servi�o ProcessoEletronicoRN, substituindo o m�todo 'get'
     * para controlar o comportamento do teste.
     */
    protected function setUp(): void
    {
        $this->mockService = $this->getMockBuilder(ProcessoEletronicoRN::class)
                                  ->onlyMethods(['get'])
                                  ->getMock();
    }

    /**
     * Testa o sucesso da consulta de hip�teses legais.
     *
     * Este teste verifica se o m�todo `consultarHipotesesLegais` retorna um array de hipoteses
     * quando o m�todo 'get' � chamado.
     *
     * @return void
     */
    public function testConsultarHipotesesLegaisSucesso()
    {
        // Define o valor retornado pelo m�todo 'get' mockado
        $mockResponse = [
            'hipoteseslegais' => [
            ]
        ];

        // Configura o mock para esperar que o m�todo 'get' seja chamado uma vez e retorne $mockResponse
        $this->mockService->expects($this->once())
                          ->method('get')
                          ->willReturn($mockResponse);

        // Chama o m�todo que est� sendo testado
        $resultado = $this->mockService->consultarHipotesesLegais(true);

        // Verifica se o resultado � nulo, conforme esperado
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('hipoteseslegais', $resultado);
    }

    /**
     * Testa o sucesso da consulta de hip�teses legais com retorno vazio.
     *
     * Este teste verifica se o m�todo `consultarHipotesesLegais` retorna um array vazio
     * quando o m�todo 'get' � chamado.
     *
     * @return void
     */
    public function testConsultarHipotesesLegaisRetornoVazioSucesso()
    {
        // Define o valor retornado pelo m�todo 'get' mockado
        $mockResponse = [];

        // Configura o mock para esperar que o m�todo 'get' seja chamado uma vez e retorne $mockResponse
        $this->mockService->expects($this->once())
                          ->method('get')
                          ->willReturn($mockResponse);

        // Chama o m�todo que est� sendo testado
        $resultado = $this->mockService->consultarHipotesesLegais(true);

        // Verifica se o resultado � nulo, conforme esperado
        $this->assertIsArray($resultado);
        $this->assertEquals(0, count($resultado));
    }

    /**
     * Testa o lan�amento de exce��o ao tentar consultar as hip�teses legais.
     *
     * Este teste verifica se uma exce��o � lan�ada corretamente quando ocorre um erro
     * ao tentar realizar a consulta (simulando a exce��o gerada pelo m�todo 'get').
     *
     * @return void
     * @throws InfraException Se a exce��o de infra-estrutura for gerada.
     */
    public function testConsultarHipotesesLegaisLancaExcecao()
    {
        // Configura o mock para lan�ar uma exce��o quando o m�todo 'get' for chamado
        $this->mockService->expects($this->once())
                          ->method('get')
                          ->willThrowException(new Exception('Erro na requisi��o'));

        // Define a expectativa de que a exce��o InfraException ser� lan�ada
        $this->expectException(InfraException::class);
        $this->expectExceptionMessage('Falha na obten��o de hip�teses legais');

        // Chama o m�todo que deve lan�ar a exce��o
        $this->mockService->consultarHipotesesLegais(true);
    }
}