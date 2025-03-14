<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

/**
 * Pagina de listagem de hip�tese legal
 */
class PaginaPenHipoteseLegalListar extends PaginaTeste
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

    /**
     * Navegar para a p�gina de listagem de hip�tese legal
     *
     * @return void
     */
  public function navegarMapeamentoHipoteseLegalListar()
    {
      $this->test->byId("txtInfraPesquisarMenu")->value(mb_convert_encoding('Listar', 'UTF-8', 'ISO-8859-1'));
      $this->test->byXPath("//a[@link='pen_map_hipotese_legal_envio_listar']")->click();
  }

    /**
     * Verificar se a tabela de hip�tese legal � exibida
     *
     * @return bool
     */
  public function existeTabela()
    {
    try {
        $trTh = $this->test->byXPath('//*[@id="divInfraAreaTabela"]/table/tbody/tr[1]/th[2]')->text();
        return !empty($trTh) && !is_null($trTh);
    } catch (Exception $ex) {
        return false;
    }
  }
}
