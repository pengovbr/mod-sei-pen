<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;

class PaginaExportarTiposProcesso extends PaginaTeste
{
  public function __construct(RemoteWebDriver $driver, $testcase)
    {
      parent::__construct($driver, $testcase);
  }

    /**
     * Navega até a tela de exportação de tipos de processo.
     */
  public function navegarExportarTiposProcessos(): void
    {
      $input = $this->elById('txtInfraPesquisarMenu');
      $input->clear();
      $input->sendKeys(mb_convert_encoding('Exportação de Tipos de Processo', 'UTF-8', 'ISO-8859-1'));

      $this->elByXPath("//a[@link='pen_map_orgaos_exportar_tipos_processos']")->click();
  }

    /**
     * Seleciona botão editar da primeira linha de tabela
     * 
     * @return void
     */
  public function selecionarParaExportar(array $indices = [0,2,3,5]): void
    {
    foreach ($indices as $i) {
        $this->elByXPath("(//label[@for='chkInfraItem{$i}'])[1]")
             ->click();
    }
      $this->elById('btnExportar')->click();
  }

    /**
     * Retorna o texto do botão identificado ou null se não existir.
     */
  public function verificarExisteBotao(string $nomeBtn): ?string
    {
    try {
        return $this->elByXPath("//button[@id='{$nomeBtn}']")->getText();
    } catch (\Exception $e) {
        return null;
    }
  }

    /**
     * Verifica a quantidade de linhas marcadas na tabela de exportação.
     */
  public function verificarQuantidadeDeLinhasSelecionadas(int $esperado = 5): void
    {
      $this->waitUntil(function() use ($esperado) {
          $rows = $this->elById('tableExportar')
                       ->findElements(WebDriverBy::cssSelector('tr'));
          $this->test->assertEquals($esperado, count($rows));
          return true;
      }, PEN_WAIT_TIMEOUT);
  }

  public function btnExportar()
    {
    $this->elById("btnExportarModal")->click();
    sleep(5);
  }

    /**
     * Clica no botão de confirmação de exportação.
     */
  public function confirmarExportacao(): void
    {
      $this->elById('btnExportarModal')->click();
      // Opcional: aguardar download ou confirmação
      sleep(5);
  }

    /**
     * Realiza pesquisa pelo nome de tipo de processo.
     */
  public function selecionarPesquisa(string $termo = 'Ouvidoria'): void
    {
      $input = $this->elById('txtNomeTipoProcessoPesquisa');
      $input->clear();
      $termo = mb_convert_encoding($termo, 'UTF-8', 'ISO-8859-1');
      $input->sendKeys($termo, WebDriverKeys::ENTER);
      $this->elById('sbmPesquisar')->click();
  }

    /**
     * Buscar se foi pesquisado
     *
     * @return void
     */
  public function buscarPesquisa(string $termo = 'Ouvidoria:'): bool
    {
    try {
        $text = $this->elByXPath("//td[contains(.,'{$termo}')]")->getText();
        return $text !== '';
    } catch (\Exception $e) {
        return false;
    }
  }
}
