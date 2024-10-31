<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

/**
 * Classe responsável por teste funcional de 
 * mapeamento de envio parcial de componentes digitais
 */
class PaginaCadastroMapEnvioCompDigitais extends PaginaTeste
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

    /**
     * Clicar no botão novo
     *
     * @return void
     */
  public function novo()
    {
      $this->test->byId("btnNovo")->click();
  }

    /**
     * Selecionar repositório
     * Selecionar unidade
     *
     * @param string $estrutura
     * @param string $unidade
     * @return void
     */
  public function setarParametros($estrutura, $unidade)
    {
      $this->selectRepositorio($estrutura);
      $this->selectUnidade($unidade);
  }

    /**
     * Seleciona repositório por sigla
     *
     * @param string $siglaRepositorio
     * @return string
     */
  private function selectRepositorio($siglaRepositorio)
    {
      $this->repositorioSelect = $this->test->select($this->test->byId('selRepositorioEstruturas'));

    if(isset($siglaRepositorio)){
        $this->repositorioSelect->selectOptionByLabel($siglaRepositorio);
    }

      return $this->test->byId('selRepositorioEstruturas')->value();
  }

    /**
     * Seleciona unidade por nome
     *
     * @param string $nomeUnidade
     * @param ?string $hierarquia
     * @return string
     */
  private function selectUnidade($nomeUnidade, $hierarquia = null)
    {
      $this->unidadeInput = $this->test->byId('txtUnidade');
      $this->unidadeInput->clear();
      $this->unidadeInput->value($nomeUnidade);
      $this->test->keys(Keys::ENTER);
      $this->test->waitUntil(function($testCase) use($hierarquia) {
          $bolExisteAlerta=null;
          $nomeUnidade = $testCase->byId('txtUnidade')->value();
        if(!empty($hierarquia)){
            $nomeUnidade .= ' - ' . $hierarquia;
        }

        try{
            $bolExisteAlerta=$this->alertTextAndClose();
          if($bolExisteAlerta!=null) { $this->test->keys(Keys::ENTER);
          }
        }catch(Exception $e){}

          $testCase->byPartialLinkText($nomeUnidade)->click();
          return true;
      }, PEN_WAIT_TIMEOUT);

      return $this->unidadeInput->value();
  }

    /**
     * Clicar no botão salvar
     *
     * @return void
     */
  public function salvar()
    {
      $this->test->byId("btnSalvar")->click();
  }

    /**
     * Seleciona botão editar da primeira linha de tabela
     * 
     * @return void
     */
  public function editar()
    {
      $this->test->byXPath("(//img[@title='Alterar Mapeamento'])[1]")->click();
  }

    /**
     * Exluir mapeamentos existentes
     *  
     * @return void
     */
  public function excluirMapeamentosExistentes()
    {
    try{
        $lnkInfraCheck=$this->test->byXPath('//*[@id="lnkInfraCheck"]');
        $lnkInfraCheck->click();
        $this->excluirSelecionados();
        sleep(1);
        $mensagem = $this->buscarMensagemAlerta();
        $this->test->assertStringContainsString(
            mb_convert_encoding('Mapeamento excluído com sucesso.', 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );
    } catch (Exception $e) {
    }
  }

    /**
     * Selecionar todos os resultados
     *  
     * @return void
     */
  public function selecionarTodos()
    {
      $this->test->byXPath('//*[@id="lnkInfraCheck"]')->click();
  }

    /**
     * Excluir selecionados
     *  
     * @return void
     */
  public function excluirSelecionados()
    {
      $this->test->byId("btnExcluir")->click();
      $this->test->acceptAlert();
  }

    /**
     * Selecionar primeira checkbox de exclusão
     * Seleciona botão excluir
     * Seleciona botão de confirmação
     *  
     * @return void
     */
  public function selecionarExcluir()
    {
      $this->test->byXPath("(//label[@for='chkInfraItem0'])[1]")->click();
      $this->test->byId("btnExcluir")->click();
      $this->test->acceptAlert();
  }

    /**
     * Lispar campo de pesquisa
     * Colocar texto para pesquisa
     * Clicar no botão pesquisar
     *
     * @param string $textoPesquisa
     * @return void
     */
  public function selecionarPesquisa($textoPesquisa)
    {
      $this->test->byId('txtNomeEstrutura')->clear();
      $this->test->byId('txtNomeEstrutura')->value($textoPesquisa);
      $this->test->byId("btnPesquisar")->click();
  }

    /**
     * Selecionar todos os intens para impressão
     *  
     * @return void
     */
  public function selecionarImprimir()
    {
      $this->test->byId("lnkInfraCheck")->click();
      // $this->test->byId("btnImprimir")->click();
  }

    /**
     * Buscar item de tabela por nome
     *
     * @param string $nome
     * @return string|null
     */
  public function buscarNome($nome)
    {
    try {
        $nomeSelecionado = $this->test->byXPath("//td[contains(.,'" . $nome . "')]")->text();
        return !empty($nomeSelecionado) && !is_null($nomeSelecionado) ?
            $nomeSelecionado : 
            null;
    } catch (Exception $ex) {
          return null;
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
}