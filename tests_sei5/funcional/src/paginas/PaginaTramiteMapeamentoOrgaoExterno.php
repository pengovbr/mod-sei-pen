<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverSelect;

class PaginaTramiteMapeamentoOrgaoExterno extends PaginaTeste
{
    public function __construct(RemoteWebDriver $driver, $testcase)
    {
        parent::__construct($driver, $testcase);
    }

    /**
     * Navega até o cadastro de relacionamento entre unidades externas.
     */
    public function navegarRelacionamentoEntreOrgaos(): void
    {
        $input = $this->elById('txtInfraPesquisarMenu');
        $input->clear();
        $input->sendKeys('Relacionamento entre Unidades');

        $this->elByLinkText('Relacionamento entre Unidades')->click();
        $this->elByXPath("//a[@link='pen_map_orgaos_externos_listar']")->click();
    }

    /**
     * Reativa mapeamentos inativos usando link.
     */
    public function reativarMapeamento(): void
    {
        $this->selectEstado('Inativo');
        $this->elByXPath("//a[contains(@class, 'reativar')]")
             ->click();
        $this->handleAlert();
    }

    /**
     * Reativa mapeamentos inativos via checkbox e botão.
     */
    public function reativarMapeamentoCheckbox(): void
    {
        $this->elByXPath("//div[contains(@class, 'infraCheckboxDiv')]")
             ->click();
        $this->elById('btnReativar')->click();
        $this->handleAlert();
    }

    /**
     * Desativa mapeamentos ativos usando link.
     */
    public function desativarMapeamento(): void
    {
        $this->selectEstado('Ativo');
        $this->elByXPath("//a[contains(@class, 'desativar')]")
             ->click();
        $this->handleAlert();
    }

    /**
     * Desativa mapeamentos ativos via checkbox e botão.
     */
    public function desativarMapeamentoCheckbox(): void
    {
        $this->elByXPath("//div[contains(@class, 'infraCheckboxDiv')]")
             ->click();
        $this->elById('btnDesativar')->click();
        $this->handleAlert();
    }

    /**
     * Seleciona o estado no dropdown de filtro de mapeamentos.
     */
    public function selectEstado(string $estado): void
    {
        $select = new WebDriverSelect(
            $this->elById('txtEstadoSelect')
        );
        $select->selectByVisibleText($estado);
    }

    /**
     * Trata o alerta padrão e confirma via ENTER se houver texto.
     */
    private function handleAlert(): void
    {
        $msg = parent::alertTextAndClose();
        if ($msg !== null) {
            $this->driver->getKeyboard()->pressKey(WebDriverKeys::ENTER);
        }
    }
}
