<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverSelect;

/**
 * Classe de teste da página de trâmite em bloco
 */
class PaginaTramiteEmBloco extends PaginaTeste
{
  public const STA_ANDAMENTO_PROCESSAMENTO = 'Aguardando Processamento';
  public const STA_ANDAMENTO_CANCELADO      = 'Cancelado';
  public const STA_ANDAMENTO_CONCLUIDO      = 'Concluído';

    /**
     * @inheritdoc
     */
  public function __construct(RemoteWebDriver $driver, $testcase)
    {
      parent::__construct($driver, $testcase);
  }

    /**
     * Seleciona múltiplos processos pelo número de protocolo.
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
     * Abre o modal de inclusão de processos no bloco de trâmite.
     */
  public function selecionarTramiteEmBloco(): void
    {
      $this->elByXPath("//img[@alt='Incluir Processos no Bloco de Trâmite']")
           ->click();
  }

    /**
     * Verifica se a visualização detalhada está aberta.
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
     * Seleciona a visualização detalhada do processo.
     */
  public function selecionarVisualizacaoDetalhada(): void
    {
      $this->elByXPath("//a[@onclick=\"trocarVisualizacao('D');\"]")->click();
  }

    /**
     * Retorna para a visualização resumida do processo.
     */
  public function fecharVisualizacaoDetalhada(): void
    {
      $this->elByXPath("//a[@onclick=\"trocarVisualizacao('R');\"]")->click();
  }

    /**
     * Seleciona um processo específico via rótulo do protocolo.
     *
     * @param string $numProtocoloFormatado
     */
  public function selecionarProcesso(string $numProtocoloFormatado): void
    {
      $this->elByXPath("//label[@title='{$numProtocoloFormatado}']")->click();
  }

    /**
     * Retorna o título da página atual.
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
     * Obtém mensagem de alerta da página, se houver.
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
