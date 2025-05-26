<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

class PaginaCancelarDocumento extends PaginaTeste
{
    public function __construct(RemoteWebDriver $driver, $testcase)
    {
        parent::__construct($driver, $testcase);
    }

    /**
     * Cancela o documento inserindo o motivo e salvando.
     *
     * @param string $motivoCancelamento
     */
    public function cancelar(string $motivoCancelamento): void
    {
        $this->setMotivoCancelamento($motivoCancelamento);
        $this->salvar();
    }

    /**
     * Preenche o motivo de cancelamento no campo apropriado dentro dos frames.
     *
     * @param string $valor
     */
    private function setMotivoCancelamento(string $valor): void
    {
        // Navega para o frame de visualização
        $this->frame(null);
        $this->frame('ifrConteudoVisualizacao');
        $this->frame('ifrVisualizacao');

        // Localiza e preenche o textarea de motivo
        $input = $this->elById('txaMotivo');
        $input->clear();
        $valor = mb_convert_encoding($valor, 'UTF-8', 'ISO-8859-1');
        $input->sendKeys($valor);
    }

    /**
     * Clica no botão salvar para confirmar o cancelamento.
     */
    private function salvar(): void
    {
        $this->elById('sbmSalvar')->click();
    }
}
