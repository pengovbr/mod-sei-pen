
<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaEnvioParcialListar extends PaginaTeste
{
    /**
     * M�todo contrutor
     * 
     * @return void
     */
    public function __construct($test)
    {
        parent::__construct($test);
    }

    public function navegarEnvioParcialListar()
    {
        $this->test->byId("txtInfraPesquisarMenu")->value(utf8_encode('Mapeamento de Envio Parcial'));
        $this->test->byXPath("//a[@link='pen_map_envio_parcial_listar']")->click();
    }

}
