<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaTramitarProcessoEmLote extends PaginaTeste
{
    const STA_ANDAMENTO_PROCESSAMENTO = "Em Processamento";
    const STA_ANDAMENTO_CANCELADO = "Cancelado";
    const STA_ANDAMENTO_CONCLUIDO = "Concluído";

  public function __construct($test)
    {
      parent::__construct($test);

  }

  public function selecionarProcessos($numProtocolo = null)
    {

    if(is_null($numProtocolo)){
      try{
        $chkGerados = $this->test->byXPath("//*[@id='imgGeradosCheck']");
        $chkGerados->click();
      } catch(Exception $e){}

      try{
          $chkRecebidos = $this->test->byXPath("//*[@id='imgRecebidosCheck']");
          $chkRecebidos->click();
      } catch(Exception $e){}
    }else{
       $chkProtocolo = $this->test->byXPath('//tr[contains(.,"'.$numProtocolo.'")]/td/div/label');
       $chkProtocolo->click();
    }
     
  }  
    
  public function navegarControleProcessos()
    {
      $this->editarProcessoButton = $this->test->byXPath("//img[@alt='Envio Externo de Processo em Lote']");
      $this->editarProcessoButton->click();
  }

  public function informacaoLote()
    {
      return $this->test->byId('divInfraAreaTelaD')->text();
  }     

  public function navegarProcessoEmLote($selAndamento, $numProtocolo = null)
    {
    if(!is_null($selAndamento)){
        $select = $this->test->select($this->test->byId('selAndamento'));
        $select->selectOptionByLabel($selAndamento); 
    }

    if(!is_null($numProtocolo)){
        $this->protocoloInput=$this->test->byId('txtProcedimentoFormatado');
        $this->protocoloInput->value($numProtocolo);
    }

      $this->presquisarProcessoButton = $this->test->byXPath("//*[@id='sbmPesquisar']");
      $this->presquisarProcessoButton->click();
       
  }
}
