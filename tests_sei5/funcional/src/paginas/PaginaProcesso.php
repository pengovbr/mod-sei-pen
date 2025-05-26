<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;
use Facebook\WebDriver\Exception\TimeOutException;

class PaginaProcesso extends PaginaTeste
{
    const STA_STATUS_PROCESSO_ABERTO   = 1;
    const STA_STATUS_PROCESSO_CONCLUIDO = 2;

    public function __construct(RemoteWebDriver $driver, $testcase)
    {
        parent::__construct($driver, $testcase);
    }

    public function concluirProcesso(): void
    {
        $this->frame(null);
        $this->frame('ifrConteudoVisualizacao');
        $this->elByXPath("//img[@alt='Concluir Processo']")->click();
        $this->frame('ifrVisualizacao');
        $this->elById('sbmSalvar')->click();
    }

    public function incluirDocumento(): void
    {
        $this->frame(null);
        $this->frame('ifrConteudoVisualizacao');
        $this->elByXPath("//img[@alt='Incluir Documento']")->click();
    }

    public function enviarProcesso(): void
    {
        $this->frame(null);
        $this->frame('ifrConteudoVisualizacao');
        $this->elByXPath("//img[@alt='Enviar Processo']")->click();
    }

    public function cancelarTramitacaoExterna(): void
    {
        $this->frame(null);
        $this->frame('ifrConteudoVisualizacao');
        $this->elByXPath("//img[@alt='Cancelar Tramitação Externa']")->click();
    }

    public function navegarParaEditarProcesso(): void
    {
        $this->frame(null);
        $this->frame('ifrConteudoVisualizacao');
        $this->elByXPath("//img[@alt='Consultar/Alterar Processo']")->click();
    }

    public function navegarParaOrdenarDocumentos(): void
    {
        $this->frame(null);
        $this->frame('ifrConteudoVisualizacao');
        $this->elByXPath("//img[@alt='Ordenar Árvore do Processo']")->click();
    }

    public function trocarOrdenacaoDocumentos(): void
    {
        $this->frame(null);
        $this->frame('ifrConteudoVisualizacao');
        $this->frame('ifrVisualizacao');
        $this->elByXPath("//*[@id='selRelProtocoloProtocolo']/option[1]")->click();
        sleep(1);
        $this->elByXPath("//a[@onclick='objLupaRelProtocoloProtocolo.moverAbaixo();']")->click();
        sleep(1);
        $this->elByXPath("//*[@id='divInfraBarraComandosSuperior']/button[@value='Salvar']")->click();
    }

    public function navegarParaTramitarProcesso(): void
    {
        $this->waitUntil(function() {
            $this->selecionarProcesso();
            $this->frame(null);
            $this->frame('ifrConteudoVisualizacao');
            $this->elByXPath("//img[@alt='Envio Externo de Processo']")->click();
            sleep(2);
            $this->frame('ifrVisualizacao');
            return true;
        }, PEN_WAIT_TIMEOUT);
    }

    public function navegarParaConsultarAndamentos(): void
    {
        $this->waitUntil(function() {
            $this->frame(null);
            $this->frame('ifrArvore');
            $this->elByLinkText('Consultar Andamento')->click();
            $this->frame(null);
            $this->frame('ifrConteudoVisualizacao');
            $this->frame('ifrVisualizacao');
            sleep(2);
            return true;
        }, PEN_WAIT_TIMEOUT);
    }

    public function navegarParaConsultarRecibos(): void
    {
        $this->waitUntil(function() {
            $this->selecionarProcesso();
            $this->frame(null);
            $this->frame('ifrConteudoVisualizacao');
            $this->elByXPath("//img[@alt='Consultar Recibos']")->click();
            sleep(2);
            $this->frame('ifrVisualizacao');
            return true;
        }, PEN_WAIT_TIMEOUT);
    }

    public function navegarParaAnexarProcesso(): void
    {
        $this->waitUntil(function() {
            $this->selecionarProcesso();
            $this->frame(null);
            $this->frame('ifrConteudoVisualizacao');
            $this->elByXPath("//img[@alt='Anexar Processo']")->click();
            sleep(2);
            $this->frame('ifrVisualizacao');
            return true;
        }, PEN_WAIT_TIMEOUT);
    }

    public function navegarParaTramitarProcessoInterno(): void
    {
        $this->enviarProcesso();
    }

    public function informacao(): string
    {
        $this->frame(null);
        $this->frame('ifrConteudoVisualizacao');
        sleep(2);
        $this->frame('ifrVisualizacao');
        return $this->elById('divArvoreInformacao')->getText();
    }

    public function processoAberto(): bool
    {
        try {
            $this->frame(null);
            $this->frame('ifrConteudoVisualizacao');
            $this->elByXPath("//img[@alt='Reabrir Processo']");
            return false;
        } catch (\Exception $e) {
            return true;
        }
    }

    public function processoBloqueado(): bool
    {
        try {
            $this->frame(null);
            $this->frame('ifrArvore');
            $this->elByXPath("//img[@title='Processo Bloqueado']");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deveSerDocumentoAnexo(bool $devePossuir, string $nomeDocumentoArvore): bool
    {
        try {
            $this->frame(null);
            $this->frame('ifrArvore');
            if ($devePossuir) {
                $span = $this->driver->findElement(
                    WebDriverBy::xpath("//span[contains(text(), '{$nomeDocumentoArvore}')]"));
                $id   = str_replace('span', '', $span->getAttribute('id'));
                $this->driver->findElement(
                    WebDriverBy::xpath("//img[contains(@id, 'iconMD_PEN_DOC_REF{$id}')]"));
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function ehDocumentoCancelado(string $nomeDocumentoArvore): bool
    {
    try
      {
        sleep(1);
        $this->frame(null);
        $this->frame("ifrArvore");
        $this->elByLinkText($nomeDocumentoArvore)->findElement(
            WebDriverBy::xpath(".//preceding-sibling::a[1]/img[contains(@src,'svg/documento_cancelado.svg?')]"));
        return true;
    }
    catch(Exception $e)
    {
        return false;
    }
  }

  public function ehDocumentoMovido(string $nomeDocumentoArvore): bool
    {
        try
        {
            sleep(2);
            $this->frame(null);
            $this->frame("ifrArvore");
            $this->elByLinkText($nomeDocumentoArvore)->findElement(
                WebDriverBy::xpath(".//preceding-sibling::a[1]/img[contains(@src,'svg/documento_movido.svg?')]"));
            return true;
        }
        catch(Exception $e)
        {
            return false;
        }
    }
    private function selecionarItemArvore(string $nomeArvore): void
    {
        $this->frame(null);
        $this->frame('ifrArvore');
        $this->elByLinkText($nomeArvore)->click();
    }

    public function selecionarDocumento(string $nomeDocumentoArvore): void
    {
        $this->selecionarItemArvore($nomeDocumentoArvore);
    }

    public function selecionarProcesso(): void
    {
        $items = $this->listarArvoreProcesso();
        if (!empty($items)) {
            $this->selecionarItemArvore($items[0]);
        }
        sleep(1);
    }

    public function listarDocumentos(): ?array
    {
        $items = $this->listarArvoreProcesso();
        return count($items) > 1 ? array_slice($items, 1) : null;
    }

    private function listarArvoreProcesso(): array
    {
        $this->frame(null);
        $this->frame('ifrArvore');
        $elements = $this->elementsByCss('div.infraArvore > a > span[id]');
        return array_map(function($el){return $el->getText();}, $elements);
    }

    public function validarBotaoExiste(string $botao): bool
    {
        try {
            $this->frame(null);
            $this->frame('ifrConteudoVisualizacao');
            $this->elByXPath("//img[@alt='{$botao}']");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
