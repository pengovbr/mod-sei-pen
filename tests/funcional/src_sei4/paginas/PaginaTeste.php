<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaTeste
{
    public function __construct($test)
    {
        $this->test = $test;
        $this->test->timeouts()->implicitWait(10000);
    }

    public function titulo()
    {
        return $this->test->title();
    }

    public function alertTextAndClose($confirm=true)
    {
        sleep(2);
        $result = $this->test->alertText();
        $result = (!is_array($result) ? $result : null);

        if(isset($confirm) && $confirm)
            $this->test->acceptAlert();
        else
            $this->dismissAlert();

        #var_dump($result);
        return $result;
    }

    public function unidadeContexto($unidadeContexto)
    {
        $this->test->frame(null);
            $this->test->byXPath("(//a[@id='lnkInfraUnidade'])[2]")->click();
            $this->test->byXPath("//td[contains(.,'" . $unidadeContexto . "')]")->click();

    }

    public function navegarParaControleProcesso()
    {
        $this->test->frame(null);
        $this->test->byXPath("//img[@title='Controle de Processos']")->click();
 
    }

    public function sairSistema()
    {
        $this->test->frame(null);
        $this->test->byXPath("(//a[@id='lnkSairSistema'])")->click();
    }

    public static function selecionarUnidadeContexto($test, $unidadeContexto)
    {
        $paginaTeste = new PaginaTeste($test);
        $paginaTeste->unidadeContexto($unidadeContexto);
    }

    public function pesquisar($termoPesquisa)
    {
        $this->test->frame(null);
        $this->test->byId("txtPesquisaRapida")->value($termoPesquisa);
        $this->test->keys(Keys::ENTER);
    }

}
