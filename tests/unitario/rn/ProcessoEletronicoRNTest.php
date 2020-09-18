<?php

use PHPUnit\Framework\TestCase;

final class ProcessoEletronicoRNTest extends TestCase
{
    private $ProcessoEletronicoRN;

    public function setUp()
    {
        $this->objProcessoEletronicoRN = new ProcessoEletronicoRN();
    }

    /**
     * Testes do método privado reduzirCampoTexto
     *
     * @return void
     */
    public function testReduzirCampoTexto()
    {
        $numTamanhoMaximo = 53;
        // Teste considerando uma palavra pequena ao final do texto
        $strTexto =             "aaaaaaaaa bbbbbbbbb ccccccccc ddddddddd eeeeeeeee fffffffff ggggggggg hhhhhhhhh iiiiiiiii";
        $strResultadoEsperado = "aaaaaaaaa bbbbbbbbb ccccccccc ddddddddd eeeeeeeee ...";
        $strResultadoAtual = $this->objProcessoEletronicoRN->reduzirCampoTexto($strTexto, $numTamanhoMaximo);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);

        // Teste considerando um texto longo com apenas uma palavra
        $strTexto =             "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";
        $strResultadoEsperado = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa ...";
        $strResultadoAtual = $this->objProcessoEletronicoRN->reduzirCampoTexto($strTexto, $numTamanhoMaximo);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);

        // Teste considerando um texto longo com uma palavra grande ao final
        $strTexto =             "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa aaaaaaaaaaaaaaaaaaaaaa";
        $strResultadoEsperado = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa ...";
        $strResultadoAtual = $this->objProcessoEletronicoRN->reduzirCampoTexto($strTexto, $numTamanhoMaximo);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);

        // Teste considerando texto longo e palavro curta ao finals
        $strTexto =             "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa aaaaaaaa aaaaaaaaaaaaaaa aaaaaaaaaaaaaaaaaaaaaa";
        $strResultadoEsperado = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa ...";
        $strResultadoAtual = $this->objProcessoEletronicoRN->reduzirCampoTexto($strTexto, $numTamanhoMaximo);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);

        // Teste considerando um texto curto abaixo do limite
        $strTexto =             "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";
        $strResultadoEsperado = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";
        $strResultadoAtual = $this->objProcessoEletronicoRN->reduzirCampoTexto($strTexto, $numTamanhoMaximo);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);

        // Teste considerando um texto longo com apenas um caracter fora do limite
        $strTexto =             "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";
        $strResultadoEsperado = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa ...";
        $strResultadoAtual = $this->objProcessoEletronicoRN->reduzirCampoTexto($strTexto, $numTamanhoMaximo);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);

        // Teste considerando um texto nulo
        $strTexto = null;
        $strResultadoEsperado = null;
        $strResultadoAtual = $this->objProcessoEletronicoRN->reduzirCampoTexto($strTexto, $numTamanhoMaximo);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);
    }
}
