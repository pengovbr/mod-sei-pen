<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaCadastroOrgaoExterno extends PaginaTeste
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

  public function navegarCadastroOrgaoExterno()
    {
      $this->test->byId("txtInfraPesquisarMenu")->value(mb_convert_encoding('Relacionamento entre Unidades', 'UTF-8', 'ISO-8859-1'));
      $this->test->byXPath("//a[@link='pen_map_orgaos_externos_listar']")->click();
  }

    /**
     * Setar parametro para novo mapeamento de orgãos externos
     * 
     * @return void
     */
  public function setarParametros($estrutura, $origem, $destino)
    {
      $this->selectRepositorio($estrutura, 'Origem');
      $this->selectUnidade($origem, 'Origem'); // Seleciona Orgão de Origem
      $this->selectUnidadeDestino($destino, 'Destino'); // Seleciona Orgão de Destino
  }

    /**
     * Seleciona repositório por sigla
     * 
     * @param string $siglaRepositorio
     * @param string $origemDestino
     * @return string
     */
  private function selectRepositorio($siglaRepositorio, $origemDestino)
    {
      $this->repositorioSelect = $this->test->select($this->test->byId('selRepositorioEstruturas' . $origemDestino));

    if(isset($siglaRepositorio)){
        $this->repositorioSelect->selectOptionByLabel($siglaRepositorio);
    }

      return $this->test->byId('selRepositorioEstruturas' . $origemDestino)->value();
  }

    /**
     * Seleciona unidade por nome
     * 
     * @param string $nomeUnidade
     * @param string $origemDestino
     * @param ?string $hierarquia
     * @return string
     */
  private function selectUnidade($nomeUnidade, $origemDestino, $hierarquia = null)
    {
      $this->unidadeInput = $this->test->byId('txtUnidade' . $origemDestino);
      $this->unidadeInput->clear();
      $this->unidadeInput->value($nomeUnidade);
      $this->test->keys(Keys::ENTER);
      $this->test->waitUntil(function($testCase) use($origemDestino, $hierarquia) {
          $bolExisteAlerta=null;
          $nomeUnidade = $testCase->byId('txtUnidade' . $origemDestino)->value();
        if(!empty($hierarquia)){
            $nomeUnidade .= ' - ' . $hierarquia;
        }

        try{
            $bolExisteAlerta=$this->alertTextAndClose();
          if($bolExisteAlerta!=null) { $this->test->keys(Keys::ENTER);
          }
        }catch(Exception $e){
        }
          $testCase->byPartialLinkText($nomeUnidade)->click();
          return true;
      }, PEN_WAIT_TIMEOUT);

      return $this->unidadeInput->value();
  }

    /**
     * Seleciona unidade por nome
     * 
     * @param string $nomeUnidade
     * @param string $origemDestino
     * @param ?string $hierarquia
     * @return string
     */
  private function selectUnidadeDestino($nomeUnidade, $origemDestino, $hierarquia = null)
    {
      $this->unidadeInput = $this->test->byId('txtUnidade' . $origemDestino);
      $this->unidadeInput->clear();
      $this->unidadeInput->value($nomeUnidade);
      $this->test->keys(Keys::ENTER);
      $this->test->waitUntil(function($testCase) use($origemDestino, $hierarquia) {
          $bolExisteAlerta=null;
          $nomeUnidade = $testCase->byId('txtUnidade' . $origemDestino)->value();
        if(!empty($hierarquia)){
            $nomeUnidade .= ' - ' . $hierarquia;
        }

        try{
            $bolExisteAlerta=$this->alertTextAndClose();
          if($bolExisteAlerta!=null) { $this->test->keys(Keys::ENTER);
          }
        }catch(Exception $e){
        }
          $testCase->byPartialLinkText($nomeUnidade)->click();
          return true;
      }, PEN_WAIT_TIMEOUT);

      return $this->unidadeInput->value();
  }

    /**
     * Seleciona botão novo da página
     * 
     * @return void
     */
  public function novoMapOrgao()
    {
      $this->test->byId("btnNovo")->click();
  }

    /**
     * Seleciona botão editar da primeira linha de tabela
     * 
     * @return void
     */
  public function editarMapOrgao()
    {
      $this->test->byXPath("(//img[@title='Alterar Relacionamento'])[1]")->click();
  }

    /**
     * Selecionar primeira checkbox de exclusão
     * Seleciona botão excluir
     * Seleciona botão de confirmação
     *  
     * @return void
     */
  public function selecionarExcluirMapOrgao()
    {
      $this->test->byXPath("(//label[@for='chkInfraItem0'])[1]")->click();
      $this->test->byId("btnExcluir")->click();
      $this->test->acceptAlert();
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

  public function abrirSelecaoDeArquivoParaImportacao()
    {
      $this->test->byXPath("(//img[@title='Importar CSV'])[1]")->click();
      sleep(2);
      $fileChooser = $this->test->byId('importArquivoCsv');
      $this->test->waitUntil(function ($testCase) use ($fileChooser) {
          $fileChooser
              ->sendKeys('/opt/sei/web/modulos/mod-sei-pen/tests_super/funcional/assets/arquivos/tipos_processos.csv')
              ->keys(Keys::CLEAR);
      }, PEN_WAIT_TIMEOUT);
      $this->test->waitUntil(function($testCase) {
          return true;
      });
  }  

    /**
     * Buscar orgão de origem por nome
     *
     * @param string $origem
     * @return string|null
     */
  public function buscarOrgaoOrigem($origem)
    {
    try {
        $orgaoOrigem = $this->test->byXPath("//td[contains(.,'" . $origem . "')]")->text();
        return !empty($orgaoOrigem) && !is_null($orgaoOrigem) ?
            $orgaoOrigem : 
            null;
    } catch (Exception $ex) {
        return null;
    }
  }

    /**
     * Buscar orgão de destino por nome
     *
     * @param string $origem
     * @return string|null
     */
  public function buscarOrgaoDestino($destino)
    {
    try {
        $orgaoDestino = $this->test->byXPath("//td[contains(.,'" . $destino . "')]")->text();
        return !empty($orgaoDestino) && !is_null($orgaoDestino) ?
            $orgaoDestino : 
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

    /**
     * Lispar campo de pesquisa
     * Colocar texto para pesquisa
     * Clicar no bot?o pesquisar
     *
     * @param string $textoPesquisa
     * @return void
     */
  public function selecionarPesquisa($textoPesquisa)
    {
      $this->test->byId('txtSiglaOrigem')->clear();
      $this->test->byId('txtSiglaOrigem')->value($textoPesquisa);
      $this->test->byId("btnPesquisar")->click();
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
}
