<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;

/**
 * Página de listagem de hipótese legal
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
     * Verifica se a tabela de hipótese legal está presente na página.
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
