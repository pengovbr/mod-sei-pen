<?php

class PaginaCadastrarProcessoEmBloco extends PaginaTeste
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

    public function navegarListagemBlocoDeTramite()
    {
        $this->test->byId("txtInfraPesquisarMenu")->value(utf8_encode('Blocos de Trâmite Externo'));
        $this->test->byXPath("//a[@link='md_pen_tramita_em_bloco']")->click();
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
                if($bolExisteAlerta!=null)$this->test->keys(Keys::ENTER);
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
                if($bolExisteAlerta!=null)$this->test->keys(Keys::ENTER);
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
    public function novoBlocoDeTramite()
    {
        $this->test->byId("bntNovo")->click();
    }

                /**
     * Description 
     * @return void
     */
    public function criarNovoBloco()
    {
        $this->test->byId('txtDescricao')->value('Bloco para teste automatizado');
    }

    /**
     * Seleciona botão editar da primeira linha de tabela
     * 
     * @return void
     */
    public function editarBlocoDeTramite($descricao = null)
    {
        $this->test->byXPath("(//img[@title='Alterar Bloco'])[1]")->click();

        if ($descricao != null) {
            $this->test->byId('txtDescricao')->clear();
            $this->test->byId('txtDescricao')->value($descricao);
        }
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
     * Selecionar Botão de salvar
     * @return void
     */
    public function btnSalvar()
    {
        $buttonElement = $this->test->byXPath("//button[@type='submit' and @value='Salvar']");
        $buttonElement->click();
    }
}