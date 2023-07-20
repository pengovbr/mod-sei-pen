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

    /**
     * Criar novo mapeamento de orgãos externos
     * Chama seleção de repositorios
     * Chama seleção de unidade
     * Chama botão salvar
     * 
     * @return void
     */
    public function novo()
    {
        $this->selectRepositorio('RE CGPRO', 'Origem');
        $this->selectUnidade('Fabrica-org2', 'Origem');
        $this->selectRepositorio('RE CGPRO', 'Destino');
        $this->selectUnidade('Fabrica-org1', 'Destino');
        $this->salvar();
    }

    /**
     * Seleciona repositório por sigla
     * 
     * @param string $siglaRepositorio
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
     * @param ?string  $hierarquia
     * @return string
     */
    private function selectUnidade($nomeUnidade, $origemDestino, $hierarquia = null)
    {
        $this->unidadeInput = $this->test->byId('txtUnidade' . $origemDestino);
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
     * Description 
     * @return void
     */
    public function novoMapOrgao()
    {
        $this->test->byId("btnNovo")->click();
    }

    /**
     * Description 
     * @return void
     */
    private function salvar()
    {
        $this->test->byId("btnSalvar")->click();
    }
}
