<?php

use PHPUnit\Framework\TestCase;

class ReduzirCampoTextoTest extends TestCase
{    
    /**
     * Testes do método privado reduzirCampoTexto
     *
     * @return void
     */
    public function testReduzirCampoTexto()
    {
        $objProcessoEletronicoRN = new ProcessoEletronicoRN();
        $numTamanhoMaximo = 53;
        // Teste considerando uma palavra pequena ao final do texto
        $strTexto =             "aaaaaaaaa bbbbbbbbb ccccccccc ddddddddd eeeeeeeee fffffffff ggggggggg hhhhhhhhh iiiiiiiii";
        $strResultadoEsperado = "aaaaaaaaa bbbbbbbbb ccccccccc ddddddddd eeeeeeeee ...";
        $strResultadoAtual = $objProcessoEletronicoRN->reduzirCampoTexto($strTexto, $numTamanhoMaximo);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);
        $this->assertTrue(strlen($strResultadoAtual) <= $numTamanhoMaximo);

        // Teste considerando um texto longo com apenas uma palavra
        $strTexto =             "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";
        $strResultadoEsperado = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa ...";
        $strResultadoAtual = $objProcessoEletronicoRN->reduzirCampoTexto($strTexto, $numTamanhoMaximo);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);
        $this->assertTrue(strlen($strResultadoAtual) <= $numTamanhoMaximo);

        // Teste considerando um texto longo com uma palavra grande ao final
        $strTexto =             "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa aaaaaaaaaaaaaaaaaaaaaa";
        $strResultadoEsperado = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa ...";
        $strResultadoAtual = $objProcessoEletronicoRN->reduzirCampoTexto($strTexto, $numTamanhoMaximo);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);
        $this->assertTrue(strlen($strResultadoAtual) <= $numTamanhoMaximo);

        // Teste considerando texto longo e palavro curta ao finals
        $strTexto =             "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa aaaaaaaa aaaaaaaaaaaaaaa aaaaaaaaaaaaaaaaaaaaaa";
        $strResultadoEsperado = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa ...";
        $strResultadoAtual = $objProcessoEletronicoRN->reduzirCampoTexto($strTexto, $numTamanhoMaximo);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);
        $this->assertTrue(strlen($strResultadoAtual) <= $numTamanhoMaximo);

        // Teste considerando um texto curto abaixo do limite
        $strTexto =             "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";
        $strResultadoEsperado = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";
        $strResultadoAtual = $objProcessoEletronicoRN->reduzirCampoTexto($strTexto, $numTamanhoMaximo);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);
        $this->assertTrue(strlen($strResultadoAtual) <= $numTamanhoMaximo);

        // Teste considerando um texto longo com apenas um caracter fora do limite
        $strTexto =             "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";
        $strResultadoEsperado = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa ...";
        $strResultadoAtual = $objProcessoEletronicoRN->reduzirCampoTexto($strTexto, $numTamanhoMaximo);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);
        $this->assertTrue(strlen($strResultadoAtual) <= $numTamanhoMaximo);

        // Teste considerando um texto longo com apenas um caracter fora do limite
        $strTexto =             "aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa a";
        $strResultadoEsperado = "aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa aaaaaaaaa ...";
        $strResultadoAtual = $objProcessoEletronicoRN->reduzirCampoTexto($strTexto, 150);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);
        $this->assertTrue(strlen($strResultadoAtual) <= 150);

        // Teste considerando um texto nulo
        $strTexto = null;
        $strResultadoEsperado = null;
        $strResultadoAtual = $objProcessoEletronicoRN->reduzirCampoTexto($strTexto, $numTamanhoMaximo);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);
        $this->assertTrue(strlen($strResultadoAtual) <= $numTamanhoMaximo);

        // Teste considerando um texto longo com ultima palavra menor que a reticencias
        $strTexto =             "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut lbore et dolore magna aliqua. Ut enim ad minim veniamr quis";
        $strResultadoEsperado = "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut lbore et dolore magna aliqua. Ut enim ad minim veniam ...";
        $strResultadoAtual = $objProcessoEletronicoRN->reduzirCampoTexto($strTexto, 150);
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);
        $this->assertTrue(strlen($strResultadoAtual) <= 150);

        $strTexto =             "ããããã ããããã";
        $strResultadoEsperado = mb_convert_encoding("ããããã ...", 'UTF-8', 'ISO-8859-1');
        $strResultadoAtual = mb_convert_encoding($objProcessoEletronicoRN->reduzirCampoTexto($strTexto, 9), 'UTF-8', 'ISO-8859-1');
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);
        $this->assertTrue(strlen(mb_convert_encoding($strResultadoAtual, 'ISO-8859-1', 'UTF-8')) <= 9);

        $strTexto =             "ããããã ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut lbore et dolore magna aliqua. Ut enim ad minim veniamr quis";
        $strResultadoEsperado = mb_convert_encoding("ããããã ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut lbore et dolore magna aliqua. Ut enim ad minim veniam ...", 'UTF-8', 'ISO-8859-1');
        $strResultadoAtual = mb_convert_encoding($objProcessoEletronicoRN->reduzirCampoTexto($strTexto, 150), 'UTF-8', 'ISO-8859-1');
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);
        $this->assertTrue(mb_strlen($strResultadoAtual) <= 150);

        $strTexto =             "Assessoria de Comunicação do Gabinete do Diretor-Presidente da Autoridade Nacional dede Proteção de dados";
        $strResultadoEsperado = mb_convert_encoding("Assessoria de Comunicação do Gabinete do Diretor-Presidente da Autoridade Nacional dede Proteçã ...", 'UTF-8', 'ISO-8859-1');
        $strResultadoAtual = mb_convert_encoding($objProcessoEletronicoRN->reduzirCampoTexto($strTexto, 100), 'UTF-8', 'ISO-8859-1');
        $this->assertEquals($strResultadoEsperado, $strResultadoAtual);
        $this->assertTrue(strlen(mb_convert_encoding($strResultadoAtual, 'ISO-8859-1', 'UTF-8')) <= 100);

    }
}
