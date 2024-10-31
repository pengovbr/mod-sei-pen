<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaMapUnidades extends PaginaTeste
{
    /**
     * Método contrutor
     * 
     * @return void
     */
  public function __construct($test)
    {
      parent::__construct($test);
  }

  public function navegarPenMapeamentoUnidades()
    {
      $this->test->byId("txtInfraPesquisarMenu")->value('Listar');
      $this->test->byXPath("//a[@link='pen_map_unidade_listar']")->click();
  }

    /**
     * Lispar campo de pesquisa
     * Colocar texto para pesquisa
     * Clicar no bot?o pesquisar
     *
     * @param string $textoPesquisa
     * @return void
     */
  public function pesquisarUnidade($textoPesquisa)
    {
      $this->test->byId('txtSiglaUnidade')->clear();
      $this->test->byId('txtSiglaUnidade')->value($textoPesquisa);
      $this->test->byId("btnPesquisar")->click();
  }

    /**
     * Seleciona botão editar da primeira linha de tabela
     *
     * @return void
     */
  public function selecionarEditar()
    {
      $this->test->byXPath("(//img[@title='Alterar Mapeamento'])[1]")->click();
  }

  public function selecionarRepoEstruturas($textoEstruturas)
    {
      $this->repoEstruturaInput = $this->test->byId('txtRepoEstruturas');
      $this->repoEstruturaInput->clear();
      $this->repoEstruturaInput->value($textoEstruturas);
      $this->test->keys(Keys::ENTER);
      $sucesso = $this->test->waitUntil(function($testCase) {
          $bolExisteAlerta=null;
          $nomeEstrutura = $testCase->byId('txtRepoEstruturas')->value();

        try{
            $bolExisteAlerta = $this->alertTextAndClose();
          if($bolExisteAlerta!=null) { $this->test->keys(Keys::ENTER);
          }
        }catch(Exception $e){}
          $testCase->byPartialLinkText($nomeEstrutura)->click();
          return true;
      }, PEN_WAIT_TIMEOUT);

      $this->test->assertTrue($sucesso);
  }

  public function selecionarUnidade($textoUnidade)
    {
      $this->repoUnidadeInput = $this->test->byId('txtUnidade');
      $this->repoUnidadeInput->clear();
      $this->repoUnidadeInput->value($textoUnidade);
      $this->test->keys(Keys::ENTER);
      $sucesso = $this->test->waitUntil(function($testCase) {
          $bolExisteAlerta=null;
          $nomeUnidade = $testCase->byId('txtUnidade')->value();

        try{
            $bolExisteAlerta = $this->alertTextAndClose();
          if($bolExisteAlerta!=null) { $this->test->keys(Keys::ENTER);
          }
        }catch(Exception $e){}
          $testCase->byPartialLinkText($nomeUnidade)->click();
          return true;
      }, PEN_WAIT_TIMEOUT);

      $this->test->assertTrue($sucesso);
  }

    /**
     * Selcionar botão salvar da página
     *
     * @return void
     */
  public function salvar()
    {
      $this->test->byId("btnSalvar")->click();
  }

  public function limparRestricoes()
    {
      $options = $this->test->byId('selRepoEstruturas')
          ->elements($this->test->using('css selector')->value('option'));
    if (count($options)) {
        $this->test->byXPath("//img[@title='Remover Estruturas Selecionadas']")->click();
    }
  }

    /**
     * Buscar mensagem de alerta da página
     *
     * @return string
     */
  public function buscarMensagemAlerta()
    {
      $alerta = $this->test->byXPath("(//div[@id='divInfraMsg0'])[1]");
      return !empty($alerta->text()) ? $alerta->text() : "";
  }

  public function validarRepositorio($siglaRepositorio)
    {
      $repositorioSelect = $this->test->select($this->test->byId('selRepositorioEstruturas'));

      $options = $repositorioSelect
          ->elements($this->test->using('css selector')->value('option'));

      $contador = 0;
    foreach ($options as $option) {
        $value = trim($option->value());
      if (empty($value) || is_null($value) || $value == "null") {
        continue;
      }

        $contador++;
    }

    if(isset($siglaRepositorio)){
        $repositorioSelect->selectOptionByLabel($siglaRepositorio);
    }

      $this->test->assertEquals(1, $contador);

      return $this->test->byId('selRepositorioEstruturas')->value();
  }
}
