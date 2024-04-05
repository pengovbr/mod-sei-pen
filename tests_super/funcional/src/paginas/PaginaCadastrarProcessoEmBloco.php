<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

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
    public function setarParametros($estrutura, $origem)
    {
        $this->selectRepositorio($estrutura);
        $this->selectUnidade($origem, 'Origem'); // Seleciona Orgão de Origem
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
     * @param string $origemDestino
     * @param ?string $hierarquia
     * @return string
     */
    private function selectUnidade($nomeUnidade, $origemDestino, $hierarquia = null)
    {
        $this->unidadeInput = $this->test->byId('txtUnidade');
        $this->unidadeInput->clear();
        $this->unidadeInput->value($nomeUnidade);
        $this->test->keys(Keys::ENTER);
        $this->test->waitUntil(function($testCase) use($origemDestino, $hierarquia) {
            $bolExisteAlerta=null;
            $nomeUnidade = $testCase->byId('txtUnidade')->value();
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
    public function selecionarExcluirBloco()
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
     * Tramitar Bloco Externamente
     *
     */
    public function tramitarProcessoExternamente($repositorio, $unidadeDestino, $unidadeDestinoHierarquia, $urgente = false, $callbackEnvio = null, $timeout = PEN_WAIT_TIMEOUT)
    {
        // Preencher parâmetros do trâmite
        $this->selectRepositorio($repositorio);
        $this->selectUnidade($unidadeDestino, 'origem', $unidadeDestinoHierarquia);
        $this->btnEnviar();

        if ($callbackEnvio == null) {
            $mensagemAlerta = null;
            try {
                $mensagemAlerta = $this->alertTextAndClose(true);
            } catch (Exception $e) {
            }
            if ($mensagemAlerta) {
                throw new Exception($mensagemAlerta);
            }
        }

        try {
            $mensagemAlerta = $this->alertTextAndClose(true);
        } catch (Exception $e) {
        }

        if (isset($mensagemAlerta)) {
            throw new Exception($mensagemAlerta);
        }

        $callbackEnvio = $callbackEnvio ?: function ($testCase) {
            try {
                $testCase->frame('ifrEnvioProcesso');
                $mensagemSucesso = utf8_encode('Trâmite externo do processo finalizado com sucesso!');
                $testCase->assertStringContainsString($mensagemSucesso, $testCase->byCssSelector('body')->text());
                $btnFechar = $testCase->byXPath("//input[@id='btnFechar']");
                $btnFechar->click();
            } finally {
                try {
                    $this->test->frame(null);
                    $this->test->frame("ifrVisualizacao");
                } catch (Exception $e) {
                }
            }

            return true;
        };

        try {
           $this->test->waitUntil($callbackEnvio, $timeout);
        } finally {
            try {
               $this->test->frame(null);
               $this->test->frame("ifrVisualizacao");
            } catch (Exception $e) {
            }
        }

        sleep(1);
    }

    public function bntTramitarBloco()
    {
        $this->test->byXPath("(//img[@title='Tramitar Bloco'])[1]")->click();
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

    /**
     * Selecionar Botão de enviar
     * @return void
     */
    public function btnEnviar()
    {
        $buttonElement = $this->test->byXPath("//button[@type='button' and @value='Enviar']");
        $buttonElement->click();
    }

}