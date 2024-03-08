<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaTramiteMapeamentoOrgaoExterno extends PaginaTeste
{
    public function __construct($test)
    {
        parent::__construct($test);

    }

    public function navegarRelacionamentoEntreOrgaos()
    {
        $this->test->byId("txtInfraPesquisarMenu")->value(utf8_encode('Relacionamento entre Unidades'));

        $this->test->byLinkText(utf8_encode('Relacionamento entre Unidades'))->click();
        $this->test->byXPath("//a[@link='pen_map_orgaos_externos_listar']")->click();
    }

    public function reativarMapeamento () {
        $this->test->select($this->test->byId('txtEstadoSelect'))->selectOptionByLabel("Inativo");
        $this->test->byXPath("//a[contains(@class, 'reativar')]")->click();
        $bolExisteAlerta=$this->alertTextAndClose();
        if($bolExisteAlerta!=null)$this->test->keys(Keys::ENTER);
    }

    public function reativarMapeamentoCheckbox() {
        $this->test->byXPath("//div[contains(@class, 'infraCheckboxDiv')]")->click();
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
        $this->test->byXPath("//div[contains(@class, 'infraCheckboxDiv')]")->click();
        $this->test->byId("btnDesativar")->click();
        $bolExisteAlerta=$this->alertTextAndClose();
        if($bolExisteAlerta!=null)$this->test->keys(Keys::ENTER);
    }


    public function selectEstado($estado) {
        $this->test->select($this->test->byId('txtEstadoSelect'))->selectOptionByLabel($estado);
    }
}
