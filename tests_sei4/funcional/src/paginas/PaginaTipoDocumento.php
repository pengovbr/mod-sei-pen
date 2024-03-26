<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaTipoDocumento extends PaginaTeste
{
    public function __construct($test)
    {
        parent::__construct($test);
    }

    public function navegarTipoDocumento()
    {
        $this->test->frame(null);
        $xpath = "//a[contains(@href, 'acao=serie_listar')]";
        $link = $this->test->byXPath($xpath);
        $url = $link->attribute('href');
        $this->test->url($url);
    }

    public function pesquisarTipoDocumento($tipoDocumento)
    {
        $this->test->byId("txtNomeSeriePesquisa")->value($tipoDocumento);
        $this->test->byId("sbmPesquisar")->click();
    }

    public function desativarTipoDocumento()
    {
        $this->test->byXPath("//img[contains(@title, 'Desativar Tipo de Documento')]")->click();
        sleep(1);
        $this->test->acceptAlert();
        sleep(1);
    }

    public function excluirTipoDocumento()
    {
        $this->test->byXPath("//img[contains(@title, 'Excluir Tipo de Documento')]")->click();
        sleep(1);
        $this->test->acceptAlert();
        sleep(1);

    }

}
