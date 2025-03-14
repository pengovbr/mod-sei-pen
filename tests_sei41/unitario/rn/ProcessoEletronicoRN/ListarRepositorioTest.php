<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de teste para o m�todo listarRepositoriosDeEstruturas da classe ProcessoEletronicoRN.
 * 
 * Esta classe utiliza PHPUnit para verificar o comportamento do m�todo listarRepositoriosDeEstruturas
 * em diferentes cen�rios, garantindo que ele funcione conforme o esperado.
 */
class ListarRepositorioTest extends TestCase
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

    /**
     * Testa o m�todo listarRepositoriosDeEstruturas para garantir que ele
     * retorna uma lista de reposit�rios de estruturas com sucesso.
     *
     * Cen�rio testado:
     * - O m�todo 'get' retorna uma lista simulada de reposit�rios.
     * - O retorno do m�todo deve ser um array contendo objetos do tipo RepositorioDTO.
     *
     * Asser��es:
     * - O retorno deve ser um array.
     * - A quantidade de itens no retorno deve ser igual � quantidade simulada.
     * - O primeiro item do retorno deve ser uma inst�ncia de RepositorioDTO.
     * 
     * @return void
     */
    public function testListarRepositoriosDeEstruturasRetornaListaSucesso()
    {
        $mockResponse = [
            ["id" => 49, "nome" => "Acre - AC", "ativo" => true],
            ["id" => 2, "nome" => "Advocacia-Geral da Uni�o", "ativo" => true],
            ["id" => 7, "nome" => "Alagoas( Estado)", "ativo" => true],
            ["id" => 20, "nome" => "Banco Central do Brasil 2", "ativo" => true],
        ];

        // Configura o mock para retornar a resposta
        $this->mockService->expects($this->once())
                          ->method('get')
                          ->willReturn($mockResponse);

        $resultado = $this->mockService->listarRepositoriosDeEstruturas();

        $this->assertIsArray($resultado, 'O retorno deve ser um array.');
        $this->assertCount(count($mockResponse), $resultado, 'A quantidade de objetos no retorno est� incorreta.');
        $this->assertInstanceOf(RepositorioDTO::class, $resultado[0], 'O primeiro objeto na lista deve ser uma inst�ncia da classe RepositorioDTO.');
    }

    /**
     * Testa o m�todo listarRepositoriosDeEstruturas para garantir que ele lan�a
     * uma exce��o quando a requisi��o falha.
     *
     * Cen�rio testado:
     * - O m�todo 'get' lan�a uma exce��o simulada.
     * - O m�todo listarRepositoriosDeEstruturas deve capturar a exce��o e
     *   relan�ar uma InfraException com a mensagem apropriada.
     *
     * Asser��es:
     * - Uma exce��o do tipo InfraException deve ser lan�ada.
     * - A mensagem da exce��o deve ser "Falha na obten��o dos Reposit�rios de Estruturas Organizacionais".
     * 
     * @return void
     */
    public function testListarRepositoriosDeEstruturasLancaExcecao()
    {
        $this->mockService->expects($this->once())
                          ->method('get')
                          ->willThrowException(new Exception('Erro na requisi��o'));

        $this->expectException(InfraException::class);
        $this->expectExceptionMessage('Falha na obten��o dos Reposit�rios de Estruturas Organizacionais');

        $this->mockService->listarRepositoriosDeEstruturas();
    }
}