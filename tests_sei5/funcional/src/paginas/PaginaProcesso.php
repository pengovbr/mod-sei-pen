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
		$this->test->frame("ifrConteudoVisualizacao");
		$concluirProcessoButton = $this->test->byXPath("//img[@alt='Concluir Processo']");
		$concluirProcessoButton->click();
	}

	public function incluirDocumento()
	{
		$this->test->frame(null);
		$this->test->frame("ifrConteudoVisualizacao");
		$incluirDocumentoButton = $this->test->byXPath("//img[@alt='Incluir Documento']");
		$incluirDocumentoButton->click();
	}

	public function enviarProcesso()
	{
		$this->test->frame(null);
		$this->test->frame("ifrConteudoVisualizacao");
		$this->test->byXPath("//img[@alt='Enviar Processo']")->click();
	}

	public function cancelarTramitacaoExterna()
	{
		$this->test->frame(null);
		$this->test->frame("ifrConteudoVisualizacao");
		$this->test->byXPath(utf8_encode("//img[@alt='Cancelar Tramita