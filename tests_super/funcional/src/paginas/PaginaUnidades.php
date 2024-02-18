<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaUnidades extends PaginaTeste
{
    public function __construct($test)
    {
        parent::__construct($test);
    }

    public function navegarUnidades()
    {
        $this->test->byId("txtInfraPesquisarMenu")->value("Unidades");
        $this->test->byXPath("//span[text()='Unidades']")->click();
        $this->test->byXPath("//a[@link='unidade_listar']")->click();
    }

    public function desativarUnidades()
    {
        $this->test->byXPath("(//img[contains(@title, 'Desativar Unidade')])[1]")->click();
        sleep(1);
        $this->test->acceptAlert();
        sleep(1);

    }

    public function excluirUnidades()
    {
        $this->test->byXPath("(//img[contains(@title, 'Excluir Unidade')])[1]")->click();
        sleep(1);
        $this->test->acceptAlert();
        sleep(1);

    }

}
