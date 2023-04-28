<?php

class PaginaProcesso extends PaginaTeste
{
	const STA_STATUS_PROCESSO_ABERTO = 1;
	const STA_STATUS_PROCESSO_CONCLUIDO = 2;

	public function __construct($test)
    {
        parent::__construct($test);
    }

    public function concluirProcesso()
    {
        $this->test->frame(null);
        $this->test->frame("ifrVisualizacao");
        $concluirProcessoButton = $this->test->byXPath("//img[@alt='Concluir Processo']");
    	$concluirProcessoButton->click();
    }

    public function incluirDocumento()
    {
        $this->test->frame(null);
        $this->test->frame("ifrVisualizacao");
        $incluirDocumentoButton = $this->test->byXPath("//img[@alt='Incluir Documento']");
        $incluirDocumentoButton->click();
    }

    public function enviarProcesso()
    {
        $this->test->frame(null);
        $this->test->frame("ifrVisualizacao");
        $this->test->byXPath("//img[@alt='Enviar Processo']")->click();
    }

    public function cancelarTramitacaoExterna()
    {
        $this->test->frame(null);
        $this->test->frame("ifrVisualizacao");
        $this->test->byXPath(utf8_encode("//img[@alt='Cancelar Tramitação Externa']"))->click();
    }

    public function navegarParaEditarProcesso()
    {
        $this->test->frame(null);
        $this->test->frame("ifrVisualizacao");
        $this->editarProcessoButton = $this->test->byXPath("//img[@alt='Consultar/Alterar Processo']");
        $this->editarProcessoButton->click();
    }

    public function navegarParaTramitarProcesso()
    {
        $this->test->waitUntil(function($testCase) {
            $this->selecionarProcesso();

            $this->test->frame(null);
            $this->test->frame("ifrVisualizacao");
            $this->editarProcessoButton = $this->test->byXPath("//img[@alt='Envio Externo de Processo']");
            $this->editarProcessoButton->click();
            sleep(2);
            $testCase->assertStringContainsString('Envio Externo de Processo', $testCase->byCssSelector('body')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);
    }

    public function navegarParaConsultarAndamentos()
    {
        $this->test->waitUntil(function($testCase) {
            $this->test->frame(null);
            $this->test->frame("ifrArvore");
            $testCase->byLinkText('Consultar Andamento')->click();

            $this->test->frame(null);
            $this->test->frame("ifrVisualizacao");
            sleep(2);
            $testCase->assertStringContainsString(utf8_encode('Histórico do Processo'), $testCase->byCssSelector('body')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);
    }

    public function navegarParaConsultarRecibos()
    {
        $this->test->waitUntil(function($testCase) {
            // Selecionar processo na Ã¡rvore
            $this->selecionarProcesso();

            $this->test->frame(null);
            $this->test->frame("ifrVisualizacao");
            $this->editarProcessoButton = $this->test->byXPath("//img[@alt='Consultar Recibos']");
            $this->editarProcessoButton->click();
            sleep(2);
            $testCase->assertStringContainsString('Consultar Recibos', $testCase->byCssSelector('body')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);
    }

    public function navegarParaAnexarProcesso()
    {
        $this->test->waitUntil(function($testCase) {
            $this->selecionarProcesso();

            $this->test->frame(null);
            $this->test->frame("ifrVisualizacao");
            $this->editarProcessoButton = $this->test->byXPath("//img[@alt='Anexar Processo']");
            $this->editarProcessoButton->click();
            sleep(2);
            $testCase->assertStringContainsString(utf8_encode('Anexação de Processos'), $testCase->byCssSelector('body')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);
    }

    public function informacao()
    {
        $this->test->frame(null);
        $this->test->frame("ifrVisualizacao");
        return $this->test->byId('divInformacao')->text();
    }

    public function processoAberto()
    {
    	try
    	{
			$this->test->frame(null);
    		$this->test->frame("ifrVisualizacao");
    		$this->test->byXPath("//img[@alt='Reabrir Processo']");
    		return false;
    	}
    	catch(Exception $e)
    	{
			return true;
    	}
    }

    public function processoBloqueado()
    {
        try
        {
            $this->test->frame(null);
            $this->test->frame("ifrArvore");
            $this->test->byXPath("//img[@title='Processo Bloqueado']");
            return true;
        }
        catch(Exception $e)
        {
            return false;
        }
    }

    public function deveSerDocumentoAnexo($bolDevePossuir, $nomeDocumentoArvore)
    {
    	try
    	{
            $this->test->frame(null);
            $this->test->frame("ifrArvore");
            if($bolDevePossuir){
                $this->test->byLinkText($nomeDocumentoArvore)->byXPath(".//following-sibling::a[1]/img[@src='imagens/anexos.gif']");
            }
            return true;
    	}
    	catch(Exception $e)
    	{
			return false;
    	}
    }

    public function ehDocumentoCancelado($nomeDocumentoArvore)
    {
    	try
    	{
            $this->test->frame(null);
            $this->test->frame("ifrArvore");
            $this->test->byLinkText($nomeDocumentoArvore)->byXPath(".//preceding-sibling::a[1]/img[@src='imagens/protocolo_cancelado.gif']");
            return true;
    	}
    	catch(Exception $e)
    	{
			return false;
    	}
    }

    public function ehDocumentoMovido($nomeDocumentoArvore)
    {
    	try
    	{
            $this->test->frame(null);
            $this->test->frame("ifrArvore");
            $this->test->byLinkText($nomeDocumentoArvore)->byXPath(".//preceding-sibling::a[1]/img[@src='imagens/sei_documento_movido.gif']");
            return true;
    	}
    	catch(Exception $e)
    	{
			return false;
    	}
    }

    private function selecionarItemArvore($nomeArvore)
    {
        $this->test->frame(null);
        $this->test->frame("ifrArvore");
        $this->test->byLinkText($nomeArvore)->click();
    }

    public function selecionarDocumento($nomeDocumentoArvore)
    {
        $this->selecionarItemArvore($nomeDocumentoArvore);
    }

    public function selecionarProcesso()
    {
        $this->selecionarItemArvore($this->listarArvoreProcesso()[0]);
        sleep(1);
    }

    public function listarDocumentos()
    {
        $itens = $this->listarArvoreProcesso();
        return (count($itens) > 1) ? array_slice($itens, 1) : null;
    }

    private function listarArvoreProcesso()
    {
        $this->test->frame(null);
        $this->test->frame("ifrArvore");
        $itens = $this->test->elements($this->test->using('css selector')->value('div.infraArvore > a > span'));
        return array_map(function($item) {return $item->text();}, $itens);
    }

}
