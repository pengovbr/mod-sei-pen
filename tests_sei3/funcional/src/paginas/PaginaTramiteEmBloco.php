<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

/**
 * Classe de teste da p�gina de tramite em bloco
 */
class PaginaTramiteEmBloco extends PaginaTeste
{
    const STA_ANDAMENTO_PROCESSAMENTO = "Em Processamento";
    const STA_ANDAMENTO_CANCELADO = "Cancelado";
    const STA_ANDAMENTO_CONCLUIDO = "Conclu�do";

    /**
     * @inheritdoc
     */
    public function __construct($test)
    {
        parent::__construct($test);
    }

    /**
     * Selecionar processo
     * @param array $arrNumProtocolo
     * @return void
     */
    public function selecionarProcessos($numProtocolo)
    {
        if(!is_null($numProtocolo)){
            $checkbox = $this->test->byCssSelector('input[title="' . $numProtocolo . '"]');
            $checkbox->click();
        }
    }

    /**
     * Selecionar tramite em bloco
     * @return void
     */
    public function selecionarTramiteEmBloco()
    {
        $btnTramiteEmBloco = $this->test->byXPath(
            "//img[@alt='". utf8_encode("Incluir Processos no Bloco de Tr�mite") ."']"
        );
        $btnTramiteEmBloco->click();
    }

    /**
     * Selecionar bloco
     * @param string $selAndamento
     * @return void
     */
    public function selecionarBloco()
    {
        $select = $this->test->select($this->test->byId('selBlocos'));
        $select->selectOptionByValue();
    }

    /**
     * Clicar em salvar
     * @return void
     */
    public function clicarSalvar()
    {
        $btnSalvar = $this->test->byXPath("//button[@name='sbmCadastrarProcessoEmBloco']");
        $btnSalvar->click();
    }

    /**
     * Buscar mensagem de alerta da p�gina
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