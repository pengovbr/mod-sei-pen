<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaExportarTiposProcesso extends PaginaTeste
{
    /**
     * Método contrutor
     * 
     * @return void
     */
    public function __construct($test)
    {
        parent::__construct($test);
    }

    /**
     * Seleciona botão editar da primeira linha de tabela
     * 
     * @return void
     */
    public function selecionarParaExportar()
    {
        $this->test->byXPath("(//input[@id='chkInfraItem0'])[1]")->click();
        $this->test->byXPath("(//input[@id='chkInfraItem2'])[1]")->click();
        $this->test->byXPath("(//input[@id='chkInfraItem3'])[1]")->click();
        $this->test->byXPath("(//input[@id='chkInfraItem5'])[1]")->click();
        $this->test->byId("btnExportar")->click();
    }

    public function verificarExisteBotao(string $nomeBtn)
    {
        try {
            return $this->test->byXPath("(//button[@id='".$nomeBtn."'])")->text();
        } catch (Exception $e) {
            return null;
        }
    }

    public function verificarQuantidadeDeLinhasSelecionadas()
    {
        $this->test->waitUntil(function($testCase) {
            $trs = $this->test->byId('tableExportar')
                ->elements($this->test->using('css selector')->value('tr'));
            $testCase->assertEquals(count($trs), 5);
            return true;
        });
    }

    public function btnExportar()
    {
        $this->test->byId("btnExportarModal")->click();
        sleep(5);
    }
}