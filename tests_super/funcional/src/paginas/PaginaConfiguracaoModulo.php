
<?php

class PaginaConfiguracaoModulo extends PaginaTeste
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

    public function navegarPaginaConfiguracaoModulo()
    {
        $this->test->byId("txtInfraPesquisarMenu")->value(utf8_encode('Parâmetros de Configuração'));
        $this->test->byXPath("//a[@link='pen_parametros_configuracao']")->click();
    }

    public function getTituloPaginaConfiguracao()
    {  
        return $this->test->byId("divInfraBarraLocalizacao")->text();
    }

    public function navegarPaginaNovoMapeamentoUnidade()
    {
        $this->test->byId("txtInfraPesquisarMenu")->value(utf8_encode('Tramita GOV.BR'));
        $this->test->byXPath("//a[@href='#submenu114']")->click();
        $this->test->byXPath("//a[@link='pen_map_unidade_cadastrar']")->click();
    }

    public function getTituloPaginaNovoMapeamentoUnidade()
    {  
        return $this->test->byId("lblUnidadePen")->text();
    }

    public function navegarPaginaHipoteseRestricaoPadrao()
    {
        $this->test->byId("txtInfraPesquisarMenu")->value(utf8_encode('Hipótese de Restrição Padrão'));
        $this->test->byXPath("//a[@link='pen_map_hipotese_legal_padrao_cadastrar']")->click();
    }

    public function getTituloPaginaHipoteseRestricaoPadrao()
    {  
        return $this->test->byId("divInfraBarraLocalizacao")->text();
    }

}
