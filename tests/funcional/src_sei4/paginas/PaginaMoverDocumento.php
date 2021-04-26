<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaMoverDocumento extends PaginaTeste
{
	public function __construct($test)
    {
        parent::__construct($test);
    }

    public function moverDocumentoParaProcesso($protocoloDestino, $motivoMovimentacao)
    {
        $this->processoDestino($protocoloDestino);
        $this->motivoMovimentacao($motivoMovimentacao);
        $this->mover();
    }

    private function processoDestino($value)
    {
        $input = $this->test->byId("txtProcedimentoDestino");
        if(isset($value)) {
            $input->value($value);
            $this->test->keys(Keys::ENTER);
        }

        return $input->value();
    }

    private function motivoMovimentacao($value)
    {
        $input = $this->test->byId("txaMotivo");
        if(isset($value)) {
            $input->value($value);
        }

        return $input->value();
    }

    private function mover()
    {
        $this->test->byId("sbmMover")->click();
    }
}
