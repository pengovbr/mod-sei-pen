<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

/**
 * Pagina de listagem de hipótese legal
 */
class PaginaPenHipoteseLegalListar extends PaginaTeste
{
    /**
     * Construtor.
     */
  public function __construct(RemoteWebDriver $driver, $testcase)
    {
      parent::__construct($driver, $testcase);
  }

    /**
     * Navega até a página de listagem de hipótese legal.
     */
  public function navegarMapeamentoHipoteseLegalListar(): void
    {
      $input = $this->elById('txtInfraPesquisarMenu');
      $input->clear();
      $input->sendKeys('Listar'. WebDriverKeys::ENTER);

      $this->elByXPath("//a[@link='pen_map_hipotese_legal_envio_listar']")->click();
  }

    /**
     * Verificar se a tabela de hipótese legal é exibida
     *
     * @return bool
     */
  public function existeTabela(): bool
    {
    try {
        $text = $this->elByXPath(
            "//*[@id='divInfraAreaTabela']/table/tbody/tr[1]/th[2]"
        )->getText();
        return $text !== '';
    } catch (\Exception $ex) {
        return false;
    }
  }
}
