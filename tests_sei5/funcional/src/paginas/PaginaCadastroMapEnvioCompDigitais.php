<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

/**
 * Classe respons�vel por teste funcional de 
 * mapeamento de envio parcial de componentes digitais
 */
class PaginaCadastroMapEnvioCompDigitais extends PaginaTeste
{
  public function __construct(RemoteWebDriver $driver, $testcase)
    {
      parent::__construct($driver, $testcase);
  }

    /**
     * Clica no bot�o Novo
     */
  public function novo(): void
    {
      $this->elById('btnNovo')->click();
  }

    /**
     * Configura reposit�rio e unidade para o envio
     */
  public function setarParametros(string $estrutura, string $unidade): void
    {
      $this->selectRepositorio($estrutura);
      $this->selectUnidade($unidade);
  }

    /**
     * Seleciona reposit�rio por sigla e retorna o valor selecionado
     */
  private function selectRepositorio(string $sigla): ?string
    {
      $select = new WebDriverSelect(
          $this->elById('selRepositorioEstruturas')
      );
    if ($sigla !== '') {
        $select->selectByVisibleText($sigla);
    }
      return $select->getFirstSelectedOption()->getAttribute('value');
  }

    /**
     * Seleciona unidade por nome e retorna o valor final
     */
  private function selectUnidade(string $nome, ?string $hierarquia = null): ?string
    {
      $input = $this->elById('txtUnidade');
      $input->clear();
      $nome = mb_convert_encoding($nome, 'UTF-8', 'ISO-8859-1');
      $input->sendKeys($nome. WebDriverKeys::ENTER);

      // Aguarda a listagem aparecer e trata alertas
      $this->waitUntil(function() use ($hierarquia) {
          $current = $this->elById('txtUnidade')->getAttribute('value');
          $label   = $hierarquia ? "{$current} - {$hierarquia}" : $current;

        try {
            $msg = parent::alertTextAndClose();
          if ($msg !== null) {
            $this->driver->getKeyboard()->pressKey(WebDriverKeys::ENTER);
          }
        } catch (\Exception $e) {
            // sem alerta
        }

          $this->driver
               ->findElement(WebDriverBy::partialLinkText($label))
               ->click();
          return true;
      }, PEN_WAIT_TIMEOUT);

      return $this->elById('txtUnidade')->getAttribute('value');
  }

    /**
     * Clicar no bot�o salvar
     *
     * @return void
     */
  public function salvar(): void
    {
      $this->elById('btnSalvar')->click();
  }

    /**
     * Seleciona bot�o editar da primeira linha de tabela
     * 
     * @return void
     */
  public function editar(): void
    {
      $this->driver
           ->findElement(WebDriverBy::xpath("(//img[@title='Alterar Mapeamento'])[1]"))
           ->click();
  }

    /**
     * Exluir mapeamentos existentes
     *  
     * @return void
     */
  public function excluirMapeamentosExistentes(): void
    {
    try {
        $this->elByXPath('//*[@id="lnkInfraCheck"]')->click();
        $this->excluirSelecionados();
        sleep(1);

        $mensagem = $this->buscarMensagemAlerta();
        $this->test->assertStringContainsString(
            'Mapeamento exclu�do com sucesso.',
            $mensagem
        );
    } catch (\Exception $e) {
        // nenhum mapeamento para excluir
    }
  }

    /**
     * Seleciona todos os registros
     */
  public function selecionarTodos(): void
    {
      $this->elByXPath('//*[@id="lnkInfraCheck"]')->click();
  }

    /**
     * Excluir selecionados
     *  
     * @return void
     */
  public function excluirSelecionados(): void
    {
      $this->elById('btnExcluir')->click();
      $this->acceptAlert();
  }

    /**
     * Seleciona primeiro checkbox e exclui
     */
  public function selecionarExcluir(): void
    {
      $this->elByXPath("(//label[@for='chkInfraItem0'])[1]")
           ->click();
      $this->excluirSelecionados();
  }

    /**
     * Efetua pesquisa pelo texto informado
     */
  public function selecionarPesquisa(string $texto): void
    {
      $input = $this->elById('txtNomeEstrutura');
      $input->clear();
      $texto = mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
      $input->sendKeys($texto);
      $this->elById('btnPesquisar')->click();
  }

    /**
     * Marca todos para impress�o
     */
  public function selecionarImprimir(): void
    {
      $this->elById('lnkInfraCheck')->click();
      // $this->elById('btnImprimir')->click();
  }

    /**
     * Busca nome em c�lula da tabela
     */
  public function buscarNome(string $nome): ?string
    {
    try {
        $text = $this->elByXPath("//td[contains(.,'{$nome}')]")->getText();
        return $text !== '' ? $text : null;
    } catch (\Exception $e) {
        return null;
    }
  }

    /**
     * Retorna mensagem de alerta da p�gina
     */
  public function buscarMensagemAlerta(): string
    {
    try {
        return $this->driver
                    ->findElement(WebDriverBy::xpath("(//div[@id='divInfraMsg0'])[1]"))
                    ->getText();
    } catch (\Exception $e) {
        return '';
    }
  }
}
