<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaTipoProcesso extends PaginaTeste
{
    public function __construct($test)
    {
        parent::__construct($test);
    }

    public function navegarTipoProcesso()
    {
        $this->test->byId("txtInfraPesquisarMenu")->value("Tipos de Processo");
        $this->test->byXPath("//span[text()='Tipos de Processo']")->click();
        $this->test->byXPath("//a[@link='tipo_procedimento_listar']")->click();
    }

    public function pesquisarTipoProcesso($tipoProcesso)
    {
        $this->test->byId("txtNomeTipoProcessoPesquisa")->value($tipoProcesso);
        $this->test->byId("sbmPesquisar")->click();
    }

    public function desativarTipoProcesso()
    {
        $this->test->byXPath("//img[contains(@title, 'Desativar Tipo de Processo')]")->click();
        sleep(1);
        $this->test->acceptAlert();
        sleep(1);
    }

    public function excluirTipoProcesso()
    {
        $this->test->byXPath("//img[contains(@title, 'Excluir Tipo de Processo')]")->click();
        sleep(1);
        $this->test->acceptAlert();
        sleep(1);
    }

}
