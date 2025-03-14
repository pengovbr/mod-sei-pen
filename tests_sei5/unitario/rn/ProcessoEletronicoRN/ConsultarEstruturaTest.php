<?php

use PHPUnit\Framework\TestCase;

/**
 * Classe de teste para o m�todo listarRepositoriosDeEstruturas da classe ProcessoEletronicoRN.
 * 
 * Esta classe utiliza PHPUnit para verificar o comportamento do m�todo listarRepositoriosDeEstruturas
 * em diferentes cen�rios, garantindo que ele funcione conforme o esperado.
 */
class ConsultarEstruturaTest extends TestCase
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

    public function testConsultarEstruturaListaSucesso()
    {
        $mockResponse = [
          "numeroDeIdentificacaoDaEstrutura" => "159098",
          "nome" => "Mauro ORG1 Filha",
          "sigla" => "Mauro ORG1 Filha",
          "ativo" => true,
          "unidadeReceptora" => false,
          "aptoParaReceberTramites" => true,
          "codigoNoOrgaoEntidade" => "",
          "codigoUnidadeReceptora" => "",
          "tipoDeTramitacao" => 0,
          "hierarquia" => [
              [
                  "numeroDeIdentificacaoDaEstrutura" => "152254",
                  "nome" => "�rg�o de Desenvolvimento ABC (FIRST) - ORGABC",
                  "sigla" => "ORGABC"
              ]
          ]
        ];

        // Configura o mock para retornar a resposta
        $this->mockService->expects($this->once())
                          ->method('get')
                          ->willReturn($mockResponse);

        $resultado = $this->mockService->consultarEstrutura(159098, 152254, false);

        $this->assertInstanceOf(EstruturaDTO::class, $resultado, 'O retorno deve ser uma inst�ncia da classe EstruturaDTO.');
    }

    public function testConsultarEstruturaListaBolRetornoRawTrueSucesso()
    {
        $mockResponse = [
          "numeroDeIdentificacaoDaEstrutura" => "159098",
          "nome" => "Mauro ORG1 Filha",
          "sigla" => "Mauro ORG1 Filha",
          "ativo" => true,
          "unidadeReceptora" => false,
          "aptoParaReceberTramites" => true,
          "codigoNoOrgaoEntidade" => "",
          "codigoUnidadeReceptora" => "",
          "tipoDeTramitacao" => 0,
          "hierarquia" => [
              [
                  "numeroDeIdentificacaoDaEstrutura" => "152254",
                  "nome" => "�rg�o de Desenvolvimento ABC (FIRST) - ORGABC",
                  "sigla" => "ORGABC"
              ]
          ]
        ];

        // Configura o mock para retornar a resposta
        $this->mockService->expects($this->once())
                          ->method('get')
                          ->willReturn($mockResponse);

        $resultado = $this->mockService->consultarEstrutura(159098, 152254, true);

        $this->assertInstanceOf(stdClass::class, $resultado, 'O retorno deve ser uma inst�ncia da classe EstruturaDTO.');
    }

    public function testConsultarEstruturaListaLancaExcecao()
    {
        $this->mockService->expects($this->once())
                          ->method('get')
                          ->willThrowException(new Exception('Erro na requisi��o'));

        $this->expectException(InfraException::class);
        $this->expectExceptionMessage('Falha na obten��o de unidades externas');

        $this->mockService->consultarEstrutura(159098, 152254, false);
    }
}