<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de teste unit�rio para o m�todo solicitarMetadados.
 * 
 * Esta classe testa o comportamento do m�todo solicitarMetadados da classe ProcessoEletronicoRN,
 * simulando depend�ncias e verificando casos de sucesso e falha.
 */
class SolicitarMetadadosTest extends TestCase
{
    /**
     * Mock da classe ProcessoEletronicoRN.
     * 
     * Este mock � usado para simular o comportamento da classe real durante os testes.
     * 
     * @var ProcessoEletronicoRN|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockService;

    /**
     * Configura��o inicial do teste.
     * 
     * Este m�todo cria um mock da classe ProcessoEletronicoRN e redefine
     * os m�todos 'get' e 'converterArrayParaObjeto' para simular comportamentos.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->mockService = $this->getMockBuilder(ProcessoEletronicoRN::class)
                    ->onlyMethods(['get', 'converterArrayParaObjeto'])
                    ->getMock();
    }

    /**
     * Testa o m�todo solicitarMetadados para um caso de sucesso.
     * 
     * Este teste verifica se o m�todo retorna um objeto stdClass corretamente
     * configurado quando os dados retornados pelo m�todo get s�o v�lidos.
     * 
     * @return void
     */
    public function testSolicitarMetadadosRetornaObjetoCorreto()
    {
        $parNumIdentificacaoTramite = 123;

        // Simular a resposta do m�todo get
        $mockResponse = [
            'propriedadesAdicionais' => ['algum_valor'],
            'processo' => [
                'documentos' => [
                    [
                        'componentesDigitais' => [
                            [
                                'assinaturasDigitais' => [
                                    ['alguma_assinatura']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $mockConvertedObject = (object) [
            'NRE' => '123456',
            'processo' => (object) [
                'documentos' => [(object) ['algum_valor']],
                'interessados' => [(object) ['algum_interessado']]
            ]
        ];

        // Configura o mock para o m�todo get
        $this->mockService->expects($this->once())
                        ->method('get')
                        ->willReturn($mockResponse);

        // Configura o mock para o m�todo converterArrayParaObjeto
        $this->mockService->expects($this->once())
                        ->method('converterArrayParaObjeto')
                        ->willReturn($mockConvertedObject);

        // Chama o m�todo a ser testado
        $resultado = $this->mockService->solicitarMetadados($parNumIdentificacaoTramite);

        // Verifica o retorno
        $this->assertInstanceOf(stdClass::class, $resultado);
        $this->assertEquals($parNumIdentificacaoTramite, $resultado->IDT);
        $this->assertEquals('123456', $resultado->metadados->NRE);
    }

    /**
     * Testa o m�todo solicitarMetadados para um caso de falha.
     * 
     * Este teste verifica se uma exce��o InfraException � lan�ada corretamente
     * quando o m�todo get falha ao buscar os dados necess�rios.
     * 
     * @return void
     */
    public function testSolicitarMetadadosLancaExcecao()
    {
        $parNumIdentificacaoTramite = 123;

        // Configura o mock para o m�todo get para lan�ar uma exce��o
        $this->mockService->expects($this->once())
                        ->method('get')
                        ->willThrowException(new Exception('Erro no web service'));

        $this->expectException(InfraException::class);
        $this->expectExceptionMessage('Falha na solicita��o de metadados do processo');

        // Chama o m�todo e espera uma exce��o
        $this->mockService->solicitarMetadados($parNumIdentificacaoTramite);
    }
}