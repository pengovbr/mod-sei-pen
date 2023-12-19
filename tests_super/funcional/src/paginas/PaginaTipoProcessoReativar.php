<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaTipoProcessoReativar extends PaginaTeste
{
    public function __construct($test)
    {
        parent::__construct($test);
    }

    public function navegarTipoProcessoReativar()
    {
        $this->test->byId("txtInfraPesquisarMenu")->value("Mapeamento de Tipos de Processo");
        $this->test->byXPath("//a[@link='pen_map_tipo_processo_reativar']")->click();
    }

    public function reativarMapeamento()
    {
        $this->test->byXPath("//a[contains(@class, 'reativar')]")->click();
        $bolExisteAlerta=$this->alertTextAndClose();
        if($bolExisteAlerta!=null)$this->test->keys(Keys::ENTER);
    }

    public function reativarMapeamentoCheckbox() 
    {
        $this->test->byXPath("(//div[contains(@class, 'infraCheckboxDiv')])[1]")->click();
        $this->test->byXPath("(//div[contains(@class, 'infraCheckboxDiv')])[2]")->click();
        $this->test->byId("btnReativar")->click();
        $bolExisteAlerta=$this->alertTextAndClose();
        if($bolExisteAlerta!=null)$this->test->keys(Keys::ENTER);
    }
}
