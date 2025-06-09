<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverSelect;
use Facebook\WebDriver\WebDriverExpectedCondition;

class PaginaTramitarProcesso extends PaginaTeste
{
  public function __construct(RemoteWebDriver $driver, $testcase)
    {
      parent::__construct($driver, $testcase);
  }

    /**
     * Seleciona repositório e retorna o valor atual
     */
  public function repositorio(string $siglaRepositorio = null): ?string
    {
      $select = new WebDriverSelect(
          $this->elById('selRepositorioEstruturas')
      );
    if ($siglaRepositorio !== null) {
        $select->selectByVisibleText($siglaRepositorio);
    }
      return $select->getFirstSelectedOption()->getAttribute('value');
  }

    /**
     * Escolhe unidade com possível hierarquia e retorna o valor
     */
  public function unidade(string $nomeUnidade, string $hierarquia = null): ?string
    {
      // Navega até o campo dentro dos frames
      $this->frame(null);
      $this->frame('ifrConteudoVisualizacao');
      $this->frame('ifrVisualizacao');

      $input = $this->elById('txtUnidade');
      $input->clear();
      $nomeUnidade = mb_convert_encoding($nomeUnidade, 'UTF-8', 'ISO-8859-1');
      $input->sendKeys($nomeUnidade. WebDriverKeys::ENTER);

      // Aguarda link e trata alertas
      $this->waitUntil(function() use ($nomeUnidade, $hierarquia) {
          $current = $this->elById('txtUnidade')->getAttribute('value');
          $label  = $hierarquia ? "{$current} - {$hierarquia}" : $current;

          // Fecha alerta se aparecer
        try {
            $msg = parent::alertTextAndClose();
          if ($msg !== null) {
            $this->driver->getKeyboard()->pressKey(WebDriverKeys::ENTER);
          }
        } catch (\Exception $e) {
            // sem alerta
        }

          // Clica no link da unidade
          $this->elByPartialLinkText($label)->click();
          return true;
      }, PEN_WAIT_TIMEOUT);

      return $this->elById('txtUnidade')->getAttribute('value');
  }

    /**
     * Marca/desmarca urgente e retorna estado atual
     */
  public function urgente(?bool $urgente): bool
    {
      $checkbox = $this->elById('chkSinUrgente');
      $selected = $checkbox->isSelected();
    if ($urgente !== null && ($urgente !== $selected)) {
        $checkbox->click();
    }
      return $checkbox->isSelected();
  }

    /**
     * Envia para tramitação
     */
  public function tramitar(): void
    {
      $this->elByXPath("//button[@value='Enviar']")->click();
  }

    /**
     * Fecha barra de progresso
     */
  public function fecharBarraProgresso(): void
    {
      $this->elById('btnFechar')->click();
  }

    /**
     * Seleciona unidade interna e retorna valor
     */
  public function unidadeInterna(string $nomeUnidade): ?string
    {
      $this->frame(null);
      $this->frame('ifrConteudoVisualizacao');
      $this->frame('ifrVisualizacao');

      $input = $this->elById('txtUnidade');
      $input->clear();
      $nomeUnidade = mb_convert_encoding($nomeUnidade, 'UTF-8', 'ISO-8859-1');
      $input->sendKeys($nomeUnidade);

      $this->waitUntil(function() use ($nomeUnidade) {
          // Trata alertas se houver
        try {
            $msg = parent::alertTextAndClose();
          if ($msg !== null) {
            $this->driver->getKeyboard()->pressKey(WebDriverKeys::ENTER);
          }
        } catch (\Exception $e) {
            // sem alerta
        }

          $this->elByPartialLinkText($this->elById('txtUnidade')->getAttribute('value'))->click();
          return true;
      }, PEN_WAIT_TIMEOUT);

      sleep(1);
      return $this->elById('txtUnidade')->getAttribute('value');
  }

    /**
     * Clica para manter aberto na unidade atual
     */
  public function manterAbertoNaUnidadeAtual(): void
    {
      $this->elById('lblSinManterAberto')->click();
  }

    /**
     * Interna: tramitar sem mudar de contexto
     */
  public function tramitarInterno(): void
    {
      $this->tramitar();
  }

    /**
     * Sobrescreve alertTextAndClose para aguardar 4s antes de delegar ao pai.
     */
  public function alertTextAndClose(bool $confirm = true): ?string
    {
      sleep(4);
      return parent::alertTextAndClose($confirm);
  }
}
