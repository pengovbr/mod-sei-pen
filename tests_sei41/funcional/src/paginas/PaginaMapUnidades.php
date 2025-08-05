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

    public function selecionarUnidadeComAlert($textoUnidade)
    {
      $this->repoUnidadeInput = $this->test->byId('txtUnidade');
      $this->repoUnidadeInput->clear();
      $this->repoUnidadeInput->value($textoUnidade);
      $this->test->keys(Keys::ENTER);
  }

  /**
   * Clica em botão salvar da página
   *
   * @return void
   */
  public function salvar()
    {
      $this->test->byId("btnSalvar")->click();
  }

  /**
   * Clica em botão novo da página
   *
   * @return void
   */
  public function btnNovo()
  {
    $buttonElement = $this->test->byXPath("//button[@type='button' and @value='Novo']");
    $buttonElement->click();  
  }

  /**
   * Seleciona a unidade na pagina de cadastro
   *
   * @return void
   */
  public function selecionarUnidadeCadastro($numUnidade = '110000001'){

    $select = $this->test->byId('selUnidadeSei');
    $this->test->select($select)->selectOptionByValue($numUnidade);
    sleep(2);
    $selectedOption = $this->test->select($select)->selectedValue();
    $this->test->assertEquals($numUnidade, $selectedOption);

  }
  /**
   * Seleciona a unidade PEN na pagina de cadastro
   *
   * @return void
   */
  public function selecionarUnidadePenCadastro($textoUnidade)
  {
    $this->repoUnidadeInput = $this->test->byId('txtUnidadePen');
    $this->repoUnidadeInput->clear();
    $this->repoUnidadeInput->value($textoUnidade);
    $this->test->keys(Keys::ENTER);
    $sucesso = $this->test->waitUntil(function($testCase) {
        $bolExisteAlerta=null;
        $nomeUnidade = $testCase->byId('txtUnidadePen')->value();

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
        $lnkInfraCheck=$this->test->byXPath('//*[@id="lnkInfraCheck"]');
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
    $this->test->refresh();

    $this->test->assertTrue($this->existeTabela());
      
    $linhasListagem = $this->test->elements(
        $this->test->using('xpath')->value('//*[@id="divInfraAreaTabela"]//table[contains(@class,"infraTable")]/tbody/tr[contains(@class,"infraTr")]')
    );

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
      $td = $linha->byXPath('./td[6]');
      if ($td->text() == $mapeamento) {
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
    $this->test->byXPath("//button[@type='button' and @value='Excluir']")->click();
    $this->test->acceptAlert();
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
        $linhas = $this->test->elements(
            $this->test->using('xpath')->value('//*[@id="divInfraAreaTabela"]//table/tbody/tr[position()>1]')
        );
        return !empty($linhas) && count($linhas) > 0;
    } catch (Exception $ex) {
        return false;
    }
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
