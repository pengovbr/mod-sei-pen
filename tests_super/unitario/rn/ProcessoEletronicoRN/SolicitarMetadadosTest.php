<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de teste unitário para o método solicitarMetadados.
 * 
 * Esta classe testa o comportamento do método solicitarMetadados da classe ProcessoEletronicoRN,
 * simulando dependências e verificando casos de sucesso e falha.
 */
class SolicitarMetadadosTest extends TestCase
{
    /**
     * Mock da classe ProcessoEletronicoRN.
     * 
     * Este mock é usado para simular o comportamento da classe real durante os testes.
     * 
     * @var ProcessoEletronicoRN|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockService;

    /**
     * Configuração inicial do teste.
     * 
     * Este método cria um mock da classe ProcessoEletronicoRN e redefine
     * os métodos 'get' e 'converterArrayParaObjeto' para simular comportamentos.
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
     * Testa o método solicitarMetadados para um caso de sucesso.
     * 
     * Este teste verifica se o método retorna um objeto stdClass corretamente
     * configurado quando os dados retornados pelo método get são válidos.
     * 
     * @return void
     */
    public function testSolicitarMetadadosRetornaObjetoCorreto()
    {
        $parNumIdentificacaoTramite = 123;

        // Simular a resposta do método get
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
            'nre' => '123456',
            'processo' => (object) [
                'documentos' => [(object) ['algum_valor']],
                'interessados' => [(object) ['algum_interessado']]
            ]
        ];

        // Configura o mock para o método get
        $this->mockService->expects($this->once())
                        ->method('get')
                        ->willReturn($mockResponse);

        // Configura o mock para o método converterArrayParaObjeto
        $this->mockService->expects($this->once())
                        ->method('converterArrayParaObjeto')
                        ->willReturn($mockConvertedObject);

        // Chama o método a ser testado
        $resultado = $this->mockService->solicitarMetadados($parNumIdentificacaoTramite);

        // Verifica o retorno
        $this->assertInstanceOf(stdClass::class, $resultado);
        $this->assertEquals($parNumIdentificacaoTramite, $resultado->IDT);
        $this->assertEquals('123456', $resultado->metadados->NRE);
    }

    /**
     * Testa o método solicitarMetadados para um caso de falha.
     * 
     * Este teste verifica se uma exceção InfraException é lançada corretamente
     * quando o método get falha ao buscar os dados necessários.
     * 
     * @return void
     */
    public function testSolicitarMetadadosLancaExcecao()
    {
        $parNumIdentificacaoTramite = 123;

        // Configura o mock para o método get para lançar uma exceção
        $this->mockService->expects($this->once())
                        ->method('get')
                        ->willThrowException(new Exception('Erro no web service'));

        $this->expectException(InfraException::class);
        $this->expectExceptionMessage('Falha na solicitação de metadados do processo');

        // Chama o método e espera uma exceção
        $this->mockService->solicitarMetadados($parNumIdentificacaoTramite);
    }
}
