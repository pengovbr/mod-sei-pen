
<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;

class PaginaEnvioParcialListar extends PaginaTeste
{
    /**
     * Construtor.
     */
  public function __construct(RemoteWebDriver $driver, $testcase)
    {
      parent::__construct($driver, $testcase);
  }

    /**
     * Navega até a listagem de envio parcial.
     */
  public function navegarEnvioParcialListar(): void
    {
      $input = $this->elById('txtInfraPesquisarMenu');
      $input->clear();
      $input->sendKeys('Mapeamento de Envio Parcial'. WebDriverKeys::ENTER);

      $this->elByXPath("//a[@link='pen_map_envio_parcial_listar']")->click();
  }
}