<?php

class PaginaReordenarProcesso extends PaginaTeste
{
    public function irParaPaginaMudarOrdem()
    {
        $this->test->frame(null);
        $this->test->frame("ifrVisualizacao");
        $this->test->byXPath(utf8_encode("//img[@alt='Ordenar Árvore do Processo']"))->click();
    }

    public function clicarReordenar()
    {
        $this->test->byId('imgRelProtocoloProtocoloReordenar')->click();
    }

}