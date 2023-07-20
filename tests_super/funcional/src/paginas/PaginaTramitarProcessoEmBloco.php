<?php
use PHPUnit\Extensions\Selenium2TestCase\Element\Select;
use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;


class PaginaTramitarProcessoEmBloco extends PaginaTramitarProcesso
{
    /**
     * Método contrutor
     * 
     */
    public function __construct($test)
    {
        parent::__construct($test);
    }


    public function tramitar()
    {
        $this->repositorio('RE CGPRO');
        $this->unidade('Fabrica-org2');
        $this->btnEnviar();
        sleep(2);

    }

    /**
     * Description 
     * @return void
     */
    public function criarNovoBloco()
    {
        $this->btnNovoBlocoDeTramite();
        $this->test->byId('txtDescricao')->value('Bloco para teste funcional');
        $this->btnSalvar();
    }

    public function adicionarProcessoAoBloco()
    {
        $this->bntAdicionarProcessoAoBloco();

        $selectElement = $this->test->byId('selBlocos');

        $lastOptionScript = "var options = document.getElementById('selBlocos').options; options[options.length - 1].selected = true;";
        $this->test->execute(['script' => $lastOptionScript, 'args' => []]);

        $selectedOptionValue = $selectElement->value();
        $lastOptionElement = $this->test->byXPath("//select[@id='selBlocos']/option[last()]");
        $lastOptionValue = $lastOptionElement->attribute('value');

        $this->test->assertEquals($lastOptionValue, $selectedOptionValue);
        $this->btnSalvar();
        sleep(2);
    }

    public function btnTramitarBloco()
    {
        // Localizar o ultimo bloco criado
        $imageElement = $this->test->element($this->test->using('css selector')->value('img[alt="Bloco-0"]'));
        $imageElement->click();
        sleep(2);
    }

    /**
     * Description 
     * @return void
     */
    private function btnNovoBlocoDeTramite()
    {
        $buttonElement = $this->test->byXPath("//button[@type='button' and @value='Novo']");
        $buttonElement->click();
    }

    /**
     * Description 
     * @return void
     */
    private function btnEnviar()
    {
        $buttonElement = $this->test->byXPath("//button[@type='button' and @value='Enviar']");
        $buttonElement->click();
    }

    /**
     * Description 
     * @return void
     */
    private function btnSalvar()
    {
        $buttonElement = $this->test->byXPath("//button[@type='submit' and @value='Salvar']");
        $buttonElement->click();
    }

    private function bntAdicionarProcessoAoBloco() 
    {
        $this->test->frame(null);
        $this->test->frame("ifrVisualizacao");
        $this->test->byXPath("//img[contains(@alt, 'Incluir Processo no Bloco de Tramite')]")->click();
    }
}