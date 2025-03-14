<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de testes unit�rios para o m�todo listarEstruturasBuscaTextual.
 * 
 * Essa classe verifica o comportamento esperado do m�todo 
 * listarEstruturasBuscaTextual, incluindo casos de sucesso e situa��es de erro.
 */
class ListarEstruturasBuscaTextualTest extends TestCase
{
    /**
     * Mock da classe que cont�m o m�todo listarEstruturasBuscaTextual.
     * 
     * @var ProcessoEletronicoRN|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockService;

    /**
     * Configura��o inicial dos testes.
     * 
     * Este m�todo � executado antes de cada teste. Ele cria um mock da classe 
     * ProcessoEletronicoRN e redefine os m�todos 'get' e 'consultarEstruturas'
     * para simular diferentes comportamentos durante os testes.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->mockService = $this->getMockBuilder(ProcessoEletronicoRN::class)
                                  ->onlyMethods(['get', 'consultarEstruturas'])
                                  ->getMock();
    }

    /**
     * Testa o m�todo listarEstruturasBuscaTextual para um caso de sucesso.
     * 
     * Simula uma resposta v�lida do m�todo 'consultarEstruturas' e verifica 
     * se o retorno � uma lista de objetos EstruturaDTO com os dados corretos.
     *
     * @return void
     */
    public function testListarEstruturasBuscaTextualRetornaEstruturas()
    {
        $idRepositorioEstrutura = 1;
        $nome = 'Estrutura Raiz';
        $mockResponse = [
            'totalDeRegistros' => 2,
            'estruturas' => [
                [
                    'numeroDeIdentificacaoDaEstrutura' => '123',
                    'nome' => 'Estrutura 1',
                    'sigla' => 'E1',
                    'ativo' => true,
                    'aptoParaReceberTramites' => true,
                    'codigoNoOrgaoEntidade' => '001',
                    'hierarquia' => [['sigla' => 'H1'], ['sigla' => 'H2']],
                ],
                [
                    'numeroDeIdentificacaoDaEstrutura' => '456',
                    'nome' => 'Estrutura 2',
                    'sigla' => 'E2',
                    'ativo' => false,
                    'aptoParaReceberTramites' => false,
                    'codigoNoOrgaoEntidade' => '002',
                    'hierarquia' => [['sigla' => 'H3']],
                ]
            ]
        ];

        $this->mockService->expects($this->once())
                          ->method('consultarEstruturas')
                          ->with($idRepositorioEstrutura, $this->arrayHasKey('identificacaoDoRepositorioDeEstruturas'))
                          ->willReturn($mockResponse);

        $resultado = $this->mockService->listarEstruturasBuscaTextual($idRepositorioEstrutura, $nome);

        $this->assertCount(2, $resultado);
        $this->assertInstanceOf(EstruturaDTO::class, $resultado[0]);
        $this->assertEquals('123', $resultado[0]->getNumNumeroDeIdentificacaoDaEstrutura());
        $this->assertEquals('Estrutura 1', $resultado[0]->getStrNome());
        $this->assertEquals(['H1', 'H2'], $resultado[0]->getArrHierarquia());
    }

    /**
     * Testa o m�todo listarEstruturasBuscaTextual para um reposit�rio inv�lido.
     * 
     * Verifica se uma exce��o InfraException � lan�ada ao fornecer um ID de reposit�rio inv�lido.
     *
     * @return void
     */
    public function testListarEstruturasBuscaTextualLancaExcecaoParaRepositorioInvalido()
    {
        $idRepositorioEstrutura = null;

        $this->expectException(InfraException::class);
        $this->expectExceptionMessage('Reposit�rio de Estruturas inv�lido');

        $this->mockService->listarEstruturasBuscaTextual($idRepositorioEstrutura);
    }

    /**
     * Testa o m�todo listarEstruturasBuscaTextual para falhas no web service.
     * 
     * Simula um erro no m�todo 'consultarEstruturas' e verifica se a exce��o 
     * InfraException � lan�ada com a mensagem correta.
     *
     * @return void
     */
    public function testListarEstruturasBuscaTextualLancaExcecaoParaErroWebService()
    {
        $idRepositorioEstrutura = 1;

        $this->mockService->expects($this->once())
                          ->method('consultarEstruturas')
                          ->willThrowException(new Exception('Erro no web service'));

        $this->expectException(InfraException::class);
        $this->expectExceptionMessage('Falha na obten��o de unidades externas');

        $this->mockService->listarEstruturasBuscaTextual($idRepositorioEstrutura);
    }
}