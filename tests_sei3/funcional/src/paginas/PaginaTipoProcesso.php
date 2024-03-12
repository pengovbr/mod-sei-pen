<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaTipoProcesso extends PaginaTeste
{
    public function __construct($test)
    {
        parent::__construct($test);
    }

    public function navegarTipoProcesso()
    {
        $this->test->frame(null);
        $xpath = "//a[contains(@href, 'acao=tipo_procedimento_listar')]";
        $link = $this->test->byXPath($xpath);
        $url = $link->attribute('href');
        $this->test->url($url);
    }

    public function pesquisarTipoProcesso($tipoProcesso)
    {
        $this->test->byId("txtNomeTipoProcessoPesquisa")->value($tipoProcesso);
        $this->test->byId("sbmPesquisar")->click();
    }

    public function desativarTipoProcesso()
    {
        $this->test->byXPath("//img[contains(@title, 'Desativar Tipo de Processo')]")->click();
        $this->alertTextAndClose();

    }

    public function excluirTipoProcesso()
    {
        $this->test->byXPath("//img[contains(@title, 'Excluir Tipo de Processo')]")->click();
        $this->alertTextAndClose();
    }

    /**
     * Buscar mensagem de alerta da página
     *
     * @return string
     */
    public function buscarMensagemAlerta()
    {
        $bolExisteAlerta = $this->alertTextAndClose();
        $bolExisteAlerta != null ? $this->test->keys(Keys::ENTER) : null;

        return $bolExisteAlerta;
    }
}
