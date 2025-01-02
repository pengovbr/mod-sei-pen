<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de teste para o método listarRepositoriosDeEstruturas da classe ProcessoEletronicoRN.
 * 
 * Esta classe utiliza PHPUnit para verificar o comportamento do método listarRepositoriosDeEstruturas
 * em diferentes cenários, garantindo que ele funcione conforme o esperado.
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
     * Configuração inicial do teste.
     * 
     * Este método cria um mock da classe ProcessoEletronicoRN e redefine
     * o método 'get' para simular comportamentos durante os testes.
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
     * Testa o método listarRepositoriosDeEstruturas para garantir que ele
     * retorna uma lista de repositórios de estruturas com sucesso.
     *
     * Cenário testado:
     * - O método 'get' retorna uma lista simulada de repositórios.
     * - O retorno do método deve ser um array contendo objetos do tipo RepositorioDTO.
     *
     * Asserções:
     * - O retorno deve ser um array.
     * - A quantidade de itens no retorno deve ser igual à quantidade simulada.
     * - O primeiro item do retorno deve ser uma instância de RepositorioDTO.
     * 
     * @return void
     */
    public function testListarRepositoriosDeEstruturasRetornaListaSucesso()
    {
        $mockResponse = [
            ["id" => 49, "nome" => "Acre - AC", "ativo" => true],
            ["id" => 2, "nome" => "Advocacia-Geral da União", "ativo" => true],
            ["id" => 7, "nome" => "Alagoas( Estado)", "ativo" => true],
            ["id" => 20, "nome" => "Banco Central do Brasil 2", "ativo" => true],
        ];

        // Configura o mock para retornar a resposta
        $this->mockService->expects($this->once())
                          ->method('get')
                          ->willReturn($mockResponse);

        $resultado = $this->mockService->listarRepositoriosDeEstruturas();

        $this->assertIsArray($resultado, 'O retorno deve ser um array.');
        $this->assertCount(count($mockResponse), $resultado, 'A quantidade de objetos no retorno está incorreta.');
        $this->assertInstanceOf(RepositorioDTO::class, $resultado[0], 'O primeiro objeto na lista deve ser uma instância da classe RepositorioDTO.');
    }

    /**
     * Testa o método listarRepositoriosDeEstruturas para garantir que ele lança
     * uma exceção quando a requisição falha.
     *
     * Cenário testado:
     * - O método 'get' lança uma exceção simulada.
     * - O método listarRepositoriosDeEstruturas deve capturar a exceção e
     *   relançar uma InfraException com a mensagem apropriada.
     *
     * Asserções:
     * - Uma exceção do tipo InfraException deve ser lançada.
     * - A mensagem da exceção deve ser "Falha na obtenção dos Repositórios de Estruturas Organizacionais".
     * 
     * @return void
     */
    public function testListarRepositoriosDeEstruturasLancaExcecao()
    {
        $this->mockService->expects($this->once())
                          ->method('get')
                          ->willThrowException(new Exception('Erro na requisição'));

        $this->expectException(InfraException::class);
        $this->expectExceptionMessage('Falha na obtenção dos Repositórios de Estruturas Organizacionais');

        $this->mockService->listarRepositoriosDeEstruturas();
    }
}