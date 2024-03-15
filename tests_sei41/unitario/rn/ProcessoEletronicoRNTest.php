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
        $this->assertTrue(strlen($strResultadoAtual) <= $numTamanhoMaximo);

        // Teste considerando um texto longo com apenas uma palavra
        $strTexto =             "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";
        $strResultadoEsperado = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa ...";
        $strResultadoAtual = $this->objProcessoEletronicoRN->reduzirCampoTexto($strTexto, $numTamanhoMaximo);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);
        $this->assertTrue(strlen($strResultadoAtual) <= $numTamanhoMaximo);

        // Teste considerando um texto longo com uma palavra grande ao final
        $strTexto =             "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa aaaaaaaaaaaaaaaaaaaaaa";
        $strResultadoEsperado = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa ...";
        $strResultadoAtual = $this->objProcessoEletronicoRN->reduzirCampoTexto($strTexto, $numTamanhoMaximo);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);
        $this->assertTrue(strlen($strResultadoAtual) <= $numTamanhoMaximo);

        // Teste considerando texto longo e palavro curta ao finals
        $strTexto =             "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa aaaaaaaa aaaaaaaaaaaaaaa aaaaaaaaaaaaaaaaaaaaaa";
        $strResultadoEsperado = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa ...";
        $strResultadoAtual = $this->objProcessoEletronicoRN->reduzirCampoTexto($strTexto, $numTamanhoMaximo);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);
        $this->assertTrue(strlen($strResultadoAtual) <= $numTamanhoMaximo);

        // Teste considerando um texto curto abaixo do limite
        $strTexto =             "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";
        $strResultadoEsperado = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";
        $strResultadoAtual = $this->objProcessoEletronicoRN->reduzirCampoTexto($strTexto, $numTamanhoMaximo);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);
        $this->assertTrue(strlen($strResultadoAtual) <= $numTamanhoMaximo);

        // Teste considerando um texto longo com apenas um caracter fora do limite
        $strTexto =             "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";
        $strResultadoEsperado = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa ...";
        $strResultadoAtual = $this->objProcessoEletronicoRN->reduzirCampoTexto($strTexto, $numTamanhoMaximo);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);
        $this->assertTrue(strlen($strResultadoAtual) <= $numTamanhoMaximo);

        // Teste considerando um texto longo com apenas um caracter fora do limite
        $strTexto =             "aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa a";
        $strResultadoEsperado = "aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa ...";
        $strResultadoAtual = $this->objProcessoEletronicoRN->reduzirCampoTexto($strTexto, 150);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);
        $this->assertTrue(strlen($strResultadoAtual) <= 150);

        // Teste considerando um texto nulo
        $strTexto = null;
        $strResultadoEsperado = null;
        $strResultadoAtual = $this->objProcessoEletronicoRN->reduzirCampoTexto($strTexto, $numTamanhoMaximo);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);
        $this->assertTrue(strlen($strResultadoAtual) <= $numTamanhoMaximo);

        // Teste considerando um texto longo com ultima palavra menor que a reticencias
        $strTexto =             "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut lbore et dolore magna aliqua. Ut enim ad minim veniamr quis";
        $strResultadoEsperado = "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut lbore et dolore magna aliqua. Ut enim ad minim veniam ...";
        $strResultadoAtual = $this->objProcessoEletronicoRN->reduzirCampoTexto($strTexto, 150);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);
        $this->assertTrue(strlen($strResultadoAtual) <= 150);

    }

    public function testCompararVersoes(){
        $this->assertTrue(InfraUtil::compararVersoes("0.0.1", "<", "0.0.2"));
        $this->assertTrue(InfraUtil::compararVersoes("0.1.0", "<", "0.2.0"));
        $this->assertTrue(InfraUtil::compararVersoes("1.0.0", "<", "2.0.0"));
        $this->assertTrue(InfraUtil::compararVersoes("4.0.3", "==", "4.0.3.0"));
        $this->assertTrue(InfraUtil::compararVersoes("4.0.3", "<", "4.0.3.1"));
        $this->assertTrue(InfraUtil::compararVersoes("4.0.4", ">", "4.0.3.0"));
        $this->assertTrue(InfraUtil::compararVersoes("4.0.3.0", "==", "4.0.3.5", 3, true));
    }
}
