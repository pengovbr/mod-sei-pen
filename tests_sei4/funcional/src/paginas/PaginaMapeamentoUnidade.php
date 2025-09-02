<?php

class PaginaMapeamentoUnidade extends PaginaTeste
{

  public function __construct($test)
    {
      parent::__construct($test);
  }

  public function navegarMapeamentoUnidade() {
      $this->test->byId("txtInfraPesquisarMenu")->value("Mapeamento de Unidades");
        
      $this->test->byLinkText("Mapeamento de Unidades")->click();
      $this->test->byXPath("//a[@link='pen_map_unidade_listar']")->click();
  }
}