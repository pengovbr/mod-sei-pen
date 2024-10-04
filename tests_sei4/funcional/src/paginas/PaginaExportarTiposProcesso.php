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
      $this->test->byId("txtInfraPesquisarMenu")->value(mb_convert_encoding('Exportação de Tipos de Processo', 'UTF-8', 'ISO-8859-1'));
      $this->test->byXPath("//a[@link='pen_map_orgaos_exportar_tipos_processos']")->click();
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

  public function verificarExisteBotao($nomeBtn)
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
