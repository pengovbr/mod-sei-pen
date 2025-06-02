<?php

class PaginaProcessosTramitadosExternamente extends PaginaTeste
{
  public function __construct(RemoteWebDriver $driver, $testcase)
    {
      parent::__construct($driver, $testcase);
  }

    /**
     * Verifica se o número do processo aparece na página de processos tramitados externamente
     *
     * @param string $numeroProcesso
     * @return bool
     */
  public function contemProcesso(string $numeroProcesso): bool
    {
      $texto = $this->elByCss('body')->getText();
      return strpos($texto, $numeroProcesso) !== false;
  }
}