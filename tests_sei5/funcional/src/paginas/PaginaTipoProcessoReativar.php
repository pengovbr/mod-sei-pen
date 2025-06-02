<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaTipoProcessoReativar extends PaginaTeste
{
  public function __construct(RemoteWebDriver $driver, $testcase)
    {
      parent::__construct($driver, $testcase);
  }

    /**
     * Navega até a página de reativação de mapeamento de tipos de processo.
     */
  public function navegarTipoProcessoReativar(): void
    {
      $input = $this->elById('txtInfraPesquisarMenu');
      $input->clear();
      $input->sendKeys('Reativar Mapeamento de Tipos de Processo');

      $this->elByXPath("//a[@link='pen_map_tipo_processo_reativar']")->click();
  }

    /**
     * Reativa o mapeamento através do link de reativação.
     */
  public function reativarMapeamento(): void
    {
      $this->elByXPath("//a[contains(@class, 'reativar')]")->click();
      $this->handleAlert();
  }

    /**
     * Reativa o mapeamento usando checkbox e botão de reativar.
     */
  public function reativarMapeamentoCheckbox(): void
    {
      $this->elByXPath("//div[contains(@class, 'infraCheckboxDiv')]")->click();
      $this->elById('btnReativar')->click();
      $this->handleAlert();
  }

    /**
     * Trata o alerta e confirma com Enter se houver mensagem.
     */
  private function handleAlert(): void
    {
      $msg = parent::alertTextAndClose();
    if ($msg !== null) {
        $this->driver->getKeyboard()->pressKey(WebDriverKeys::ENTER);
    }
  }
}
