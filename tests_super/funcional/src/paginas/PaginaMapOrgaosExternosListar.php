<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaMapOrgaosExternosListar extends PaginaTeste
{
    public function __construct($test)
    {
        parent::__construct($test);

    }

    public function reativarMapOrgaosExterno () {
        $this->test->select($this->test->byId('txtEstadoSelect'))->selectOptionByLabel("Inativo");
        $this->test->byXPath("//a[contains(@class, 'reativar')]")->click();
        $bolExisteAlerta=$this->alertTextAndClose();
        if($bolExisteAlerta!=null)$this->test->keys(Keys::ENTER);
        sleep(3);
        
        // Teste para a reativação selecionando no checkbox
        $this->test->byXPath("//button[contains(@class, 'close media h-100')]")->click();
        $this->test->byXPath("//div[contains(@class, 'infraCheckboxDiv')]")->click();
        $this->test->byId("btnReativar")->click();
        $bolExisteAlerta=$this->alertTextAndClose();
        if($bolExisteAlerta!=null)$this->test->keys(Keys::ENTER);
    }

    
}
