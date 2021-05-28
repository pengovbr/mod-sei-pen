<?php

class PaginaCancelarDocumento extends PaginaTeste
{
	public function __construct($test)
    {
        parent::__construct($test);
    }

    public function cancelar($motivoCancelamento)
    {
        $this->motivoCancelamento($motivoCancelamento);
        $this->salvar();
    }

    private function motivoCancelamento($value)
    {
        $input = $this->test->byId("txaMotivo");
        if(isset($value)) {
            $input->value($value);
        }

        return $input->value();
    }

    private function salvar()
    {
        $this->test->byId("sbmSalvar")->click();
    }
}
