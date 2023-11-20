<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaTramiteMapeamentoOrgaoExterno extends PaginaTeste
{
    public function __construct($test)
    {
        parent::__construct($test);

    }

    public function reativarMapeamento () {
        $this->test->select($this->test->byId('txtEstadoSelect'))->selectOptionByLabel("Inativo");
        $this->test->byXPath("//a[contains(@class, 'reativar')]")->click();
        $bolExisteAlerta=$this->alertTextAndClose();
        if($bolExisteAlerta!=null)$this->test->keys(Keys::ENTER);
    }

    public function reativarMapeamentoCheckbox() {
        $this->test->byXPath("(//input[@id='chkInfraItem0'])[1]")->click();
        $this->test->byId("btnReativar")->click();
        $bolExisteAlerta=$this->alertTextAndClose();
        if($bolExisteAlerta!=null)$this->test->keys(Keys::ENTER);
    }


    public function desativarMapeamento () {
        $this->test->select($this->test->byId('txtEstadoSelect'))->selectOptionByLabel("Ativo");
        $this->test->byXPath("//a[contains(@class, 'desativar')]")->click();
        $bolExisteAlerta=$this->alertTextAndClose();
        if($bolExisteAlerta!=null)$this->test->keys(Keys::ENTER);
    }

    public function desativarMapeamentoCheckbox() {
        $this->test->byXPath("(//input[@id='chkInfraItem0'])[1]")->click();
        $this->test->byId("btnDesativar")->click();
        $bolExisteAlerta=$this->alertTextAndClose();
        if($bolExisteAlerta!=null)$this->test->keys(Keys::ENTER);
    }


    public function selectEstado($estado) {
        $this->test->select($this->test->byId('txtEstadoSelect'))->selectOptionByLabel($estado);
    }

    
}
