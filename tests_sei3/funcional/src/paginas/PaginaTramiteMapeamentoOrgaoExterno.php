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
        $this->test->frame(null);
        $xpath = "//a[contains(@href, 'acao=pen_map_orgaos_externos_listar')]";
        $link = $this->test->byXPath($xpath);
        $url = $link->attribute('href');
        $this->test->url($url);
    }

    public function reativarMapeamento()
    {
        $this->test->select($this->test->byId('txtEstadoSelect'))->selectOptionByLabel("Inativo");
        $this->test->byXPath("//a[contains(@class, 'reativar')]")->click();
        $bolExisteAlerta = $this->alertTextAndClose();
        $bolExisteAlerta != null ? $this->test->keys(Keys::ENTER) : null;

        return $this->alertTextAndClose();
    }

    public function reativarMapeamentoCheckbox()
    {
        $this->test->byXPath("(//input[@id='chkInfraItem0'])[1]")->click();
        $this->test->byId("btnReativar")->click();
        $bolExisteAlerta = $this->alertTextAndClose();
        $bolExisteAlerta != null ? $this->test->keys(Keys::ENTER) : null;

        return $this->alertTextAndClose();
    }

    public function desativarMapeamento()
    {
        $this->test->select($this->test->byId('txtEstadoSelect'))->selectOptionByLabel("Ativo");
        $this->test->byXPath("//a[contains(@class, 'desativar')]")->click();
        $bolExisteAlerta = $this->alertTextAndClose();  
        $bolExisteAlerta != null ? $this->test->keys(Keys::ENTER) : null;

        return $this->alertTextAndClose();
    }

    public function desativarMapeamentoCheckbox()
    {
        $this->test->byXPath("(//input[@id='chkInfraItem0'])[1]")->click();
        $this->test->byId("btnDesativar")->click();
        $bolExisteAlerta = $this->alertTextAndClose();
        $bolExisteAlerta != null ? $this->test->keys(Keys::ENTER) : null;

        return $this->alertTextAndClose();
    }

    public function selectEstado($estado)
    {
        $this->test->select($this->test->byId('txtEstadoSelect'))->selectOptionByLabel($estado);
    }

    public function mensagemValidacao($status)
    {
        return utf8_encode("Relacionamento entre Órgãos foi {$status} com sucesso.");
    }
}
