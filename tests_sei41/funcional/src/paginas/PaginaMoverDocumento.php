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
      $this->test->frame(null);
      $this->test->frame("ifrConteudoVisualizacao");
      $this->test->frame("ifrVisualizacao");
      $input = $this->test->byId("txtProcedimentoDestino");
    if(isset($value)) {
        $input->value($value);
        sleep(2);
        $this->test->keys(Keys::ENTER);
    }

      sleep(2);
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
