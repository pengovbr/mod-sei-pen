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
        $this->test->byXPath("(//label[@for='chkInfraItem0'])[1]")->click();
        $this->test->byXPath("(//label[@for='chkInfraItem2'])[1]")->click();
        $this->test->byXPath("(//label[@for='chkInfraItem3'])[1]")->click();
        $this->test->byXPath("(//label[@for='chkInfraItem5'])[1]")->click();
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

    /**
     * Lispar campo de pesquisa
     * Colocar texto para pesquisa
     *
     * @param string $textoPesquisa
     * @return void
     */
    public function selecionarPesquisaSinalizacao()
    {
        try {
            $this->test->select($this->test->byId('selSinalizacaoTipoProcedimento'))
                ->selectOptionByLabel("Exclusivo da ouvidoria");
            $elementos = $this->test->byXPath("//td[contains(.,'Ouvidoria:')]")->text();
            return !empty($elementos) && !is_null($elementos);
        } catch (Exception $e) {
            return false;
        }
    }
}