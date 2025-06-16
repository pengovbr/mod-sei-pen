<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverSelect;

/**
 * Página de mapeamento de unidades
 */
class PaginaMapUnidades extends PaginaTeste
{
    /**
     * Construtor.
     */
  public function __construct(RemoteWebDriver $driver, $testcase)
    {
      parent::__construct($driver, $testcase);
  }

    /**
     * Navega até a listagem de mapeamento de unidades.
     */
  public function navegarPenMapeamentoUnidades(): void
    {
      $input = $this->elById('txtInfraPesquisarMenu');
      $input->clear();
      $input->sendKeys('Listar'. WebDriverKeys::ENTER);

      $this->elByXPath("//a[@link='pen_map_unidade_listar']")->click();
  }

    /**
     * Pesquisa unidades pelo texto informado.
     */
  public function pesquisarUnidade(string $texto): void
    {
      $input = $this->elById('txtSiglaUnidade');
      $input->clear();
      $texto = mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
      $input->sendKeys($texto);
      $this->elById('btnPesquisar')->click();
  }

    /**
     * Seleciona o botão de editar do primeiro mapeamento.
     */
  public function selecionarEditar(): void
    {
      $this->elByXPath("(//img[@title='Alterar Mapeamento'])[1]")
           ->click();
  }

    /**
     * Seleciona repositório de estruturas pelo texto e confirma.
     */
  public function selecionarRepoEstruturas(string $texto): void
    {
      $input = $this->elById('txtRepoEstruturas');
      $input->clear();
      $texto = mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
      $input->sendKeys($texto. WebDriverKeys::ENTER);

      // Usa o helper waitUntil da classe base
      $this->waitUntil(function() {
          $current = $this->driver->findElement(WebDriverBy::id('txtRepoEstruturas'))
                            ->getAttribute('value');
        try {
            $msg = parent::alertTextAndClose();
          if ($msg !== null) {
            $this->driver->getKeyboard()->pressKey(WebDriverKeys::ENTER);
          }
        } catch (\Exception $e) {
            // sem alerta
        }
          $this->driver->findElement(
              WebDriverBy::partialLinkText($current)
          )->click();
          return true;
      }, PEN_WAIT_TIMEOUT);
  }

    /**
     * Seleciona unidade pelo texto e confirma.
     */
  public function selecionarUnidade(string $texto): void
    {
      $input = $this->elById('txtUnidade');
      $input->clear();
      $texto = mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
      $input->sendKeys($texto . WebDriverKeys::ENTER);

      // Usa o helper waitUntil da classe base
      $this->waitUntil(function() {
          $current = $this->driver->findElement(WebDriverBy::id('txtUnidade'))
                            ->getAttribute('value');
        try {
            $msg = parent::alertTextAndClose();
          if ($msg !== null) {
            $this->driver->getKeyboard()->pressKey(WebDriverKeys::ENTER);
          }
        } catch (\Exception $e) {
            // sem alerta
        }
          $this->driver->findElement(
              WebDriverBy::partialLinkText($current)
          )->click();
          return true;
      }, PEN_WAIT_TIMEOUT);
  }

  public function selecionarUnidadeComAlert($textoUnidade)
  {
    $input = $this->elById('txtUnidade');
    $input->sendKeys($textoUnidade . WebDriverKeys::ENTER);
  }

    /**
     * Clica no botão Salvar.
     */
  public function salvar(): void
    {
      $this->elById('btnSalvar')->click();
  }

    /**
     * Remove todas as restrições de repositório se existirem.
     */
  public function limparRestricoes(): void
    {
      $options = (new WebDriverSelect(
          $this->elById('selRepoEstruturas')
      ))->getOptions();

    if (count($options) > 0) {
        $this->elByXPath("//img[@title='Remover Estruturas Selecionadas']")
             ->click();
    }
  }

    /**
     * Busca mensagem de alerta da página.
     */
  public function buscarMensagemAlerta(): string
    {
    try {
        return $this->elByXPath("(//div[@id='divInfraMsg0'])[1]")
                    ->getText();
    } catch (\Exception $e) {
        return '';
    }
  }

    /**
     * Valida se o repositório possui exatamente uma opção não-vazia e seleciona o informado.
     */
  public function validarRepositorio(string $sigla): ?string
    {
      $select = new WebDriverSelect(
          $this->elById('selRepositorioEstruturas')
      );
      $options = $select->getOptions();
      $count = 0;
    foreach ($options as $opt) {
        $val = trim($opt->getAttribute('value'));
      if ($val !== '' && $val !== 'null') {
        $count++;
      }
    }
      $this->test->assertEquals(1, $count);

      $select->selectByVisibleText($sigla);
      return $select->getFirstSelectedOption()->getAttribute('value');
  }
}