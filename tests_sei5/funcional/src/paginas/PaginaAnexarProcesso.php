<?php

class PaginaAnexarProcesso extends PaginaTeste
{
  public function __construct($test)
    {
      parent::__construct($test);
  }

  public function protocolo($protocolo)
    {
      $protocoloInput = $this->test->byId('txtProtocolo');

    if(isset($protocolo)){
        $protocoloInput->value($protocolo);
        $this->test->byId('btnPesquisar')->click();
    }

      return $protocoloInput->value();
  }

  public function anexar()
    {
      $anexarButton = $this->test->byId('sbmAnexar');
      $anexarButton->click();
      $this->alertTextAndClose();
  }


  public function anexarProcesso($protocolo)
    {
      $this->protocolo($protocolo);
      $this->anexar();
  }
}
