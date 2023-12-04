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
        $this->test->frame(null);
        $xpath = "//a[contains(@href, 'acao=pen_map_tipo_processo_reativar')]";
        $link = $this->test->byXPath($xpath);
        $url = $link->attribute('href');
        $this->test->url($url);
    }

    public function reativarMapeamento()
    {
        $this->test->byXPath("//a[contains(@class, 'reativar')]")->click();
        $bolExisteAlerta=$this->alertTextAndClose();
        if($bolExisteAlerta!=null)$this->test->keys(Keys::ENTER);
    }

    public function reativarMapeamentoCheckbox() 
    {
        $this->test->byXPath("(//input[@id='chkInfraItem0'])")->click();
        $this->test->byXPath("(//input[@id='chkInfraItem1'])")->click();
        $this->test->byId("btnReativar")->click();
        $bolExisteAlerta=$this->alertTextAndClose();
        if($bolExisteAlerta!=null)$this->test->keys(Keys::ENTER);
    }

    /**
     * Buscar mensagem de alerta da página
     *
     * @return string
     */
    public function buscarMensagemAlerta()
    {
        $bolExisteAlerta = $this->alertTextAndClose();
        $bolExisteAlerta != null ? $this->test->keys(Keys::ENTER) : null;

        return $bolExisteAlerta;
    }
}
