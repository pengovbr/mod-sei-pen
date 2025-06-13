<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverSelect;

/**
 * Classe de teste da p�gina de tr�mite em bloco
 */
class PaginaTramiteEmBloco extends PaginaTeste
{
  public const STA_ANDAMENTO_PROCESSAMENTO = 'Aguardando Processamento';
  public const STA_ANDAMENTO_CANCELADO      = 'Cancelado';
  public const STA_ANDAMENTO_CONCLUIDO      = 'Conclu�do';

    /**
     * @inheritdoc
     */
  public function __construct(RemoteWebDriver $driver, $testcase)
    {
      parent::__construct($driver, $testcase);
  }

    /**
     * Seleciona m�ltiplos processos pelo n�mero de protocolo.
     *
     * @param string[] $protocolos
     */
  public function selecionarProcessos(array $protocolos): void
    {
    foreach ($protocolos as $protocolo) {
        $this->elByXPath("//tr[contains(.,'{$protocolo}')]/td/div/label")
             ->click();
    }
  }

    /**
     * Abre o modal de inclus�o de processos no bloco de tr�mite.
     */
  public function selecionarTramiteEmBloco(): void
    {
      $this->elByXPath("//img[@alt='Incluir Processos no Bloco de Tr�mite']")
           ->click();
  }

    /**
     * Verifica se a visualiza��o detalhada est� aberta.
     *
     * @return bool
     */
  public function visualizacaoDetalhadaAberta(): bool
    {
    try {
        $this->elByXPath("//a[@onclick=\"trocarVisualizacao('R');\"]");
        return true;
    } catch (\Exception $e) {
        return false;
    }
      return true;
  }

    /**
     * Seleciona a visualiza��o detalhada do processo.
     */
  public function selecionarVisualizacaoDetalhada(): void
    {
      $this->elByXPath("//a[@onclick=\"trocarVisualizacao('D');\"]")->click();
  }

    /**
     * Retorna para a visualiza��o resumida do processo.
     */
  public function fecharVisualizacaoDetalhada(): void
    {
      $this->elByXPath("//a[@onclick=\"trocarVisualizacao('R');\"]")->click();
  }

    /**
     * Seleciona um processo espec�fico via r�tulo do protocolo.
     *
     * @param string $numProtocoloFormatado
     */
  public function selecionarProcesso(string $numProtocoloFormatado): void
    {
      $this->elByXPath("//label[@title='{$numProtocoloFormatado}']")->click();
  }

    /**
     * Retorna o t�tulo da p�gina atual.
     *
     * @param string $tituloEsperado
     * @return string
     */
  public function verificarTituloDaPagina(string $tituloEsperado): string
    {
      return $this->elByXPath("//div[text()='{$tituloEsperado}']")->getText();
  }

    /**
     * Seleciona um bloco pelo valor de andamento.
     *
     * @param string $andamento
     */
  public function selecionarBloco(string $andamento): void
    {
      $select = new WebDriverSelect($this->elById('selBlocos'));
      $select->selectByValue($andamento);
  }

    /**
     * Clica em Salvar.
     */
  public function clicarSalvar(): void
    {
      $this->elByXPath("//button[@name='sbmCadastrarProcessoEmBloco']")->click();
  }

    /**
     * Obt�m mensagem de alerta da p�gina, se houver.
     *
     * @return string
     */
  public function buscarMensagemAlerta(): string
    {
    try {
        return $this->elByXPath("(//div[@id='divInfraMsg0'])[1]")
                    ->getText();
    } catch (\Exception $e) {
        return '';
    }
  }
}
