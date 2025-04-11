
<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaEnvioParcialListar extends PaginaTeste
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

  public function navegarEnvioParcialListar()
    {
      $this->test->byId("txtInfraPesquisarMenu")->value(mb_convert_encoding('Mapeamento de Envio Parcial', 'UTF-8', 'ISO-8859-1'));
      $this->test->byXPath("//a[@link='pen_map_envio_parcial_listar']")->click();
  }

}
