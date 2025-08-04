<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverSelect;

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
     * Seleciona botão editar da primeira linha de tabela
     *
     * @return void
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

   /**
   * Clica em botão novo da página
   *
   * @return void
   */
  public function btnNovo()
  {
    $buttonElement = $this->elByXPath("//button[@type='button' and @value='Novo']");
    $buttonElement->click();  
  }

  /**
   * Seleciona a unidade na pagina de cadastro
   *
   * @return void
   */
  public function selecionarUnidadeCadastro($numUnidade = '110000001'){

    $select = new WebDriverSelect(
          $this->elById('selUnidadeSei')
      );
    $select->selectByValue($numUnidade);
    sleep(2);
    $selectedOption = $select->getFirstSelectedOption()->getAttribute('value');
    $this->test->assertEquals($numUnidade, $selectedOption);
  }

  /**
   * Seleciona a unidade PEN na pagina de cadastro
   *
   * @return void
   */
  public function selecionarUnidadePenCadastro($textoUnidade)
  {
    $this->repoUnidadeInput = $this->elById('txtUnidadePen');
    $this->repoUnidadeInput->clear();
    $this->repoUnidadeInput->sendKeys($textoUnidade);
    $this->repoUnidadeInput->sendKeys(WebDriverKeys::ENTER);

    $sucesso = $this->waitUntil(function() {
      $bolExisteAlerta = null;
      $nomeUnidade = $this->repoUnidadeInput->getAttribute('value');
      try {

        $bolExisteAlerta = $this->alertTextAndClose();
        if($bolExisteAlerta != null) { 
          // re-dispara ENTER para recarregar sugestões
          $this->repoUnidadeInput->sendKeys(WebDriverKeys::ENTER);
        }

      } catch (\Exception $e) {
          // nenhum alerta => segue
      }
        // Clica no link parcial pelo texto completo
        $this->elByPartialLinkText($nomeUnidade)->click();
        return true;
    }, PEN_WAIT_TIMEOUT);

    // $this->test->assertTrue($sucesso);
  }

  /**
   * Cadastrar novo mapeamento
   *  
   * @return void
   */
  public function cadastrarNovoMapeamento($dados = []){
      $this->btnNovo();
      $this->selecionarUnidadeCadastro($dados['numUnidade']);
      $this->selecionarUnidadePenCadastro($dados['nomeUnidade']);
      $this->salvar();

      sleep(2);
      
      $mensagem = $this->buscarMensagemAlerta();
      $this->test->assertStringContainsString(
          mb_convert_encoding('Mapeamento de Unidade gravado com sucesso.', 'UTF-8', 'ISO-8859-1'),
          $mensagem
      );
  }

  /**
   * Excluir mapeamentos existentes
   *  
   * @return void
   */
  public function excluirMapeamentosExistentes()
    {
    try{
        $lnkInfraCheck=$this->elByXPath('//*[@id="lnkInfraCheck"]');
        $lnkInfraCheck->click();
        $this->excluir();
        sleep(1);
        $mensagem = $this->buscarMensagemAlerta();
        $this->test->assertStringContainsString(
            mb_convert_encoding('Mapeamento de Unidades foi excluido com sucesso.', 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );
    } catch (Exception $e) {
    }
  }

  public function listarMapeamentos()
  {
    $this->refresh();

    $this->test->assertTrue($this->existeTabela());
      
    $linhasListagem = $this->elementsByXPath('//*[@id="divInfraAreaTabela"]//table[contains(@class,"infraTable")]/tbody/tr[contains(@class,"infraTr")]');

    return $linhasListagem;
  }

  /**
   * Valida mapeamentos existentes
   *  
   * @return void
   */
  public function validarMapeamentoExistente($mapeamento)
  {
    $linhasDaTabela = $this->listarMapeamentos();
    foreach ($linhasDaTabela as $linha) {
      $td = $linha->findElement(WebDriverBy::xpath('./td[6]'));
      if ($td->getText() == $mapeamento) {
        return true;
      }
    }
    return false;
  }

  /**
   * btn Excluir
   *  
   * @return void
   */
  public function excluir()
    {
    $this->elByXPath("//button[@type='button' and @value='Excluir']")->click();
    $this->acceptAlert();
  }

  /**
   * Verificar se a tabela de hipótese legal é exibida
   *
   * @return bool
   */
  public function existeTabela()
    {
    try {
        // Procura por qualquer linha de dados (ignorando o cabeçalho)
        $linhas = $this->elementsByXPath(('//*[@id="divInfraAreaTabela"]//table/tbody/tr[position()>1]'));
        return !empty($linhas) && count($linhas) > 0;
    } catch (Exception $ex) {
        return false;
    }
  }

  public function selecionarUnidadeComAlert($textoUnidade)
    {
      $this->repoUnidadeInput = $this->elById('txtUnidadePen');
      $this->repoUnidadeInput->clear();
      $this->repoUnidadeInput->sendKeys($textoUnidade);
      $this->repoUnidadeInput->sendKeys(WebDriverKeys::ENTER);
    }
}
