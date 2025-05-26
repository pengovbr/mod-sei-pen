<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverSelect;

/**
 * Classe para cadastro de órgãos externos no mapeamento de tramitação.
 */
class PaginaCadastroOrgaoExterno extends PaginaTeste
{
    public function __construct(RemoteWebDriver $driver, $testcase)
    {
        parent::__construct($driver, $testcase);
    }

    /**
     * Navega até a listagem de órgãos externos.
     */
    public function navegarCadastroOrgaoExterno(): void
    {
        $input = $this->elById('txtInfraPesquisarMenu');
        $input->clear();
        $input->sendKeys('Relacionamento entre Unidades');

        $this->elByXPath("//a[@link='pen_map_orgaos_externos_listar']")->click();
    }

    /**
     * Configura parâmetros para novo mapeamento de órgão externo.
     */
    public function setarParametros(string $estrutura, string $origem, string $destino): void
    {
        $this->selectRepositorio($estrutura, 'Origem');
        $this->selectUnidade('Origem', $origem);
        $this->selectUnidade('Destino', $destino);
    }

    /**
     * Seleciona repositório por sigla.
     */
    private function selectRepositorio(string $sigla, string $tipo): void
    {
        $select = new WebDriverSelect(
            $this->elById('selRepositorioEstruturas' . $tipo)
        );
        if ($sigla !== '') {
            $select->selectByVisibleText($sigla);
        }
    }

    /**
     * Seleciona unidade (Origem ou Destino) e confirma Enter.
     */
    private function selectUnidade(string $tipo, string $nome, ?string $hierarquia = null): void
    {
        $input = $this->elById('txtUnidade' . $tipo);
        $input->clear();
        $nome = mb_convert_encoding($nome, 'UTF-8', 'ISO-8859-1');
        $input->sendKeys($nome. WebDriverKeys::ENTER);

        $this->waitUntil(function() use ($tipo, $hierarquia) {
            $current = $this->elById('txtUnidade' . $tipo)->getAttribute('value');
            $label = $hierarquia ? "{$current} - {$hierarquia}" : $current;

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
    }

    /**
     * Clica no botão Novo.
     */
    public function novoMapOrgao(): void
    {
        $this->elById('btnNovo')->click();
    }

    /**
     * Clica no botão Editar do primeiro mapeamento.
     */
    public function editarMapOrgao(): void
    {
        $this->elByXPath("(//img[@title='Alterar Relacionamento'])[1]")
             ->click();
    }

    /**
     * Seleciona e exclui o primeiro órgão mapeado.
     */
    public function selecionarExcluirMapOrgao(): void
    {
        $this->elByXPath("(//label[@for='chkInfraItem0'])[1]")
             ->click();
        $this->elById('btnExcluir')->click();
        $this->acceptAlert();
    }

    /**
     * Clica no botão Salvar.
     */
    public function salvar(): void
    {
        $this->elById('btnSalvar')->click();
    }

    /**
     * Abre o seletor de arquivo e faz upload do CSV.
     */
    public function abrirSelecaoDeArquivoParaImportacao(string $caminhoCsv): void
    {
        $this->elByXPath("(//img[@title='Importar CSV'])[1]")
             ->click();

        $fileInput = $this->elById('importArquivoCsv');
        $this->waitUntil(function() use ($fileInput, $caminhoCsv) {
            $caminhoCsv = mb_convert_encoding($caminhoCsv, 'UTF-8', 'ISO-8859-1');
            $fileInput->sendKeys($caminhoCsv);
            return true;
        }, PEN_WAIT_TIMEOUT);
    }

    /**
     * Busca nome de órgão na tabela.
     */
    public function buscarOrgao(string $nome): ?string
    {
        try {
            $text = $this->elByXPath("//td[contains(.,'{$nome}')]")->getText();
            return $text !== '' ? $text : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Pesquisa pelo SIGLA de origem.
     */
    public function selecionarPesquisa(string $texto): void
    {
        $input = $this->elById('txtSiglaOrigem');
        $input->clear();
        $texto = mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
        $input->sendKeys($texto);
        $this->elById('btnPesquisar')->click();
    }
}