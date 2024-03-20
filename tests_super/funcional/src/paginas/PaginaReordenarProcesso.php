<?php

class PaginaReordenarProcesso extends PaginaTeste
{
    public function irParaPaginaMudarOrdem()
    {
        $this->test->frame(null);
        $this->test->frame("ifrVisualizacao");
        $this->test->byXPath(utf8_encode("//img[@alt='Ordenar Árvore do Processo']"))->click();
    }

    public function irParaTramitarProcesso()
    {
        $this->test->frame(null);
        $this->test->frame("ifrVisualizacao");
        $this->test->byXPath(utf8_encode("//img[@alt='Envio Externo de Processo']"))->click();
    }

    public function clicarOptionReordenar(int $index = 0)
    {
        $options = $this->test->byId('selRelProtocoloProtocolo')
            ->elements($this->test->using('css selector')->value('option'));
        $options[$index]->click();
    }

    public function moverParaBaixo(int $vezes = 1)
    {
        $botaoParaBaixo = $this->test->byXPath(utf8_encode("//img[@alt='Mover Abaixo Protocolo Selecionado']"));
        for ($i=0; $i < $vezes; $i++) {
            $botaoParaBaixo->click();
        }
    }

    public function moverParaCima(int $vezes = 1)
    {
        $botaoParaCima = $this->test->byXPath(utf8_encode("//img[@alt='Mover Acima Protocolo Selecionado']"));
        for ($i=0; $i < $vezes; $i++) {
            $botaoParaCima->click();
        }
    }

    public function salvar()
    {
        $this->test->byXPath("//button[@name='sbmSalvar']")->click();
    }
}
