<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaTramitarProcessoEmLote extends PaginaTeste
{
    const STA_ANDAMENTO = "Em Processamento"; 

    public function __construct($test)
    {
        parent::__construct($test);

    }

    public function selecionarProcessos()
    {

        try{
            $chkGerados = $this->test->byXPath("//*[@id='imgGeradosCheck']");
            $chkGerados->click();
        } catch(Exception $e){}

        try{
            $chkRecebidos = $this->test->byXPath("//*[@id='imgRecebidosCheck']");
            $chkRecebidos->click();
        } catch(Exception $e){}
     
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

    public function selecionarSituacao()
    {
        //$this->test->frame(null);
        $select = $this->test->select($this->test->byId('selAndamento'));
        $select->selectOptionByLabel(self::STA_ANDAMENTO);        
    }
}
