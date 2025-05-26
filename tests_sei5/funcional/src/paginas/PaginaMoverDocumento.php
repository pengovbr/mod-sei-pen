<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;

class PaginaMoverDocumento extends PaginaTeste
{
    public function __construct(RemoteWebDriver $driver, $testcase)
    {
        parent::__construct($driver, $testcase);
    }

    /**
     * Move o documento para outro processo, preenchendo destino e motivo.
     *
     * @param string $protocoloDestino
     * @param string $motivoMovimentacao
     */
    public function moverDocumentoParaProcesso(string $protocoloDestino, string $motivoMovimentacao): void
    {
        $this->setProcessoDestino($protocoloDestino);
        $this->setMotivoMovimentacao($motivoMovimentacao);
        $this->executarMover();
    }

    /**
     * Preenche o campo de processo destino e confirma com Enter.
     */
    private function setProcessoDestino(string $valor): void
    {
        $this->frame(null);
        $this->frame('ifrConteudoVisualizacao');
        $this->frame('ifrVisualizacao');

        $input = $this->elById('txtProcedimentoDestino');
        $input->clear();
        $valor = mb_convert_encoding($valor, 'UTF-8', 'ISO-8859-1');
        $input->sendKeys($valor. WebDriverKeys::ENTER);

        // Opcional: aguarda o valor ser aplicado
        $this->waitUntil(function() use ($valor) {
            return stripos($this->elById('txtProcedimentoDestino')->getAttribute('value'), $valor) !== false;
        }, PEN_WAIT_TIMEOUT);
    }

    /**
     * Preenche o motivo de movimentação.
     */
    private function setMotivoMovimentacao(string $valor): void
    {
        $this->frame(null);
        $this->frame('ifrConteudoVisualizacao');
        $this->frame('ifrVisualizacao');

        $input = $this->elById('txaMotivo');
        $input->clear();
        $valor = mb_convert_encoding($valor, 'UTF-8', 'ISO-8859-1');
        $input->sendKeys($valor);
    }

    /**
     * Executa o clique no botão mover.
     */
    private function executarMover(): void
    {
        $this->elById('sbmMover')->click();
    }
}