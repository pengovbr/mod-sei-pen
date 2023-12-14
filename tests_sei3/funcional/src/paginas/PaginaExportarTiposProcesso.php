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

    public function navegarExportarTiposProcessos()
    {
        $this->test->frame(null);
        $xpath = "//a[contains(@href, 'acao=pen_map_orgaos_exportar_tipos_processos')]";
        $link = $this->test->byXPath($xpath);
        $url = $link->attribute('href');
        $this->test->url($url);
    }

    /**
     * Seleciona botão editar da primeira linha de tabela
     * 
     * @return void
     */
    public function selecionarParaExportar()
    {
        $this->test->byXPath("(//input[@id='chkInfraItem0'])")->click();
        $this->test->byXPath("(//input[@id='chkInfraItem2'])")->click();
        $this->test->byXPath("(//input[@id='chkInfraItem3'])")->click();
        $this->test->byXPath("(//input[@id='chkInfraItem5'])")->click();
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
     * @return void
     */
    public function selecionarPesquisa()
    {
        $this->test->byId('txtNomeTipoProcessoPesquisa')->clear();
        $this->test->byId('txtNomeTipoProcessoPesquisa')->value('Ouvidoria');
        $this->test->byId("sbmPesquisar")->click();
    }

    /**
     * Buscar se foi pesquisado
     *
     * @return void
     */
    public function buscarPesquisa()
    {
        try {
            $elementos = $this->test->byXPath("//td[contains(.,'Ouvidoria:')]")->text();
            return !empty($elementos) && !is_null($elementos);
        } catch (Exception $e) {
            return false;
        }
    }
}