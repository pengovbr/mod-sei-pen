<?php

use PHPUnit\Framework\TestCase;

/**
 * Testes do metodo consultarTramiteEmAndamento da classe ProcessoEletronicoRN
 *
 * O Tramita GOV.BR e simulado pelo mock de consultarTramitesTodos, que devolve os tramites da situacao consultada.
 * Estruturas: 154403 (orgao 1, local), 155270 (unidade filha do orgao 1, local), 154404 (orgao 2), repositorio 1.
 *
 * @covers \ProcessoEletronicoRN
 */
class ConsultarTramiteEmAndamentoTest extends TestCase
{
    const PROTOCOLO = '99990.000001/2026-01';
    const NRE = '0000022248602026';
    const REPOSITORIO = 1;
    const ORGAO_LOCAL = 154403;
    const UNIDADE_LOCAL_FILHA = 155270;
    const ORGAO_EXTERNO = 154404;

    private function criarTramite($numIdt, $numSituacao, $numRemetente, $numDestinatario, $numRepositorioRemetente = self::REPOSITORIO)
    {
        return (object) [
            'IDT' => $numIdt,
            'NRE' => self::NRE,
            'situacaoAtual' => $numSituacao,
            'remetente' => (object) [
                'identificacaoDoRepositorioDeEstruturas' => $numRepositorioRemetente,
                'numeroDeIdentificacaoDaEstrutura' => $numRemetente,
            ],
            'destinatario' => (object) [
                'identificacaoDoRepositorioDeEstruturas' => self::REPOSITORIO,
                'numeroDeIdentificacaoDaEstrutura' => $numDestinatario,
            ],
        ];
    }

    /**
     * Mock que devolve os tramites informados na consulta pela situacao de cada um (busca pelo protocolo)
     */
    private function criarServico(array $arrObjTramite)
    {
        $objServico = $this->getMockBuilder(ProcessoEletronicoRN::class)
            ->onlyMethods(['consultarTramitesTodos'])
            ->getMock();

        $objServico->method('consultarTramitesTodos')
            ->willReturnCallback(function ($numIdt, $strNre, $numRemetente, $numDestinatario, $strProtocolo, $numRepositorio, $numSituacao) use ($arrObjTramite) {
                if (is_null($strProtocolo)) {
                    return [];
                }
                return array_values(array_filter($arrObjTramite, function ($objTramite) use ($numSituacao) {
                    return intval($objTramite->situacaoAtual) == intval($numSituacao);
                }));
            });

        return $objServico;
    }

    private function consultar(array $arrObjTramite, $numDestino, array $arrEstruturaLocal = [self::ORGAO_LOCAL, self::UNIDADE_LOCAL_FILHA], $numRepositorioLocal = self::REPOSITORIO)
    {
        return $this->criarServico($arrObjTramite)->consultarTramiteEmAndamento(
            self::PROTOCOLO, [], $numDestino, self::REPOSITORIO, $arrEstruturaLocal, $numRepositorioLocal
        );
    }

    /** Correcao: tramite remetido pelo proprio orgao, aguardando so o processamento local do recibo, nao impede */
    public function testSituacao5RemetidaPeloOrgaoLocalNaoImpedeEnvio()
    {
        $this->assertNull($this->consultar([$this->criarTramite(10, 5, self::ORGAO_LOCAL, self::ORGAO_EXTERNO)], self::ORGAO_EXTERNO));
    }

    /** Correcao: vale para qualquer unidade mapeada neste SEI (unidade filha) */
    public function testSituacao5RemetidaPorUnidadeLocalFilhaNaoImpedeEnvio()
    {
        $this->assertNull($this->consultar([$this->criarTramite(10, 5, self::UNIDADE_LOCAL_FILHA, self::ORGAO_EXTERNO)], self::ORGAO_EXTERNO));
    }

    /** Mantido: situacao 5 em que este orgao e o destinatario (remetente ainda nao processou o recibo) impede */
    public function testSituacao5RecebidaDoOrgaoExternoImpedeEnvio()
    {
        $objTramite = $this->consultar([$this->criarTramite(10, 5, self::ORGAO_EXTERNO, self::ORGAO_LOCAL)], self::ORGAO_EXTERNO);
        $this->assertNotNull($objTramite);
        $this->assertEquals(10, $objTramite->IDT);
    }

    /** Mantido: sem as estruturas locais informadas (ex.: interpretacao do erro 0047) a situacao 5 continua contando */
    public function testSituacao5SemEstruturasLocaisImpedeEnvio()
    {
        $this->assertNotNull($this->consultar([$this->criarTramite(10, 5, self::ORGAO_LOCAL, self::ORGAO_EXTERNO)], self::ORGAO_EXTERNO, []));
    }

    /** Mantido: remetente com a mesma estrutura em outro repositorio nao e o orgao local */
    public function testSituacao5RemetidaPorOutroRepositorioImpedeEnvio()
    {
        $this->assertNotNull($this->consultar([$this->criarTramite(10, 5, self::ORGAO_LOCAL, self::ORGAO_EXTERNO, 99)], self::ORGAO_EXTERNO));
    }

    /** Mantido: situacoes 0, 2, 3 e 4 remetidas pelo orgao local impedem (cenario do erro 0047) */
    public function testSituacoesAnterioresAoReciboRemetidasPeloOrgaoLocalImpedemEnvio()
    {
        foreach ([0, 2, 3, 4] as $numSituacao) {
            $objTramite = $this->consultar([$this->criarTramite(10, $numSituacao, self::ORGAO_LOCAL, self::ORGAO_EXTERNO)], self::ORGAO_EXTERNO);
            $this->assertNotNull($objTramite, "Situacao $numSituacao deveria impedir o envio");
        }
    }

    /** Mantido: situacao 1 para a unidade de destino nao impede (o envio a cancela); de outra origem para o local, impede */
    public function testSituacao1()
    {
        $this->assertNull($this->consultar([$this->criarTramite(10, 1, self::ORGAO_LOCAL, self::ORGAO_EXTERNO)], self::ORGAO_EXTERNO));
        $this->assertNotNull($this->consultar([$this->criarTramite(10, 1, self::ORGAO_EXTERNO, self::ORGAO_LOCAL)], self::ORGAO_EXTERNO));
    }

    /** Mantido: tramite que nao envolve a unidade de destino nao impede */
    public function testTramiteComOutroDestinoNaoImpedeEnvio()
    {
        $this->assertNull($this->consultar([$this->criarTramite(10, 3, self::ORGAO_LOCAL, 999999)], self::ORGAO_EXTERNO));
    }

    /** A situacao 5 liberada nao esconde outro tramite em andamento do mesmo processo */
    public function testSituacao5LiberadaNaoEscondeOutroTramiteEmAndamento()
    {
        $objTramite = $this->consultar([
            $this->criarTramite(20, 5, self::ORGAO_LOCAL, self::ORGAO_EXTERNO),
            $this->criarTramite(10, 3, self::ORGAO_EXTERNO, self::ORGAO_LOCAL),
        ], self::ORGAO_EXTERNO);
        $this->assertNotNull($objTramite);
        $this->assertEquals(10, $objTramite->IDT);
    }

    /** Situacoes finais nao impedem */
    public function testSituacoesFinaisNaoImpedemEnvio()
    {
        foreach ([6, 7, 8, 9, 10] as $numSituacao) {
            $this->assertNull($this->consultar([$this->criarTramite(10, $numSituacao, self::ORGAO_EXTERNO, self::ORGAO_LOCAL)], self::ORGAO_EXTERNO));
        }
    }
}
