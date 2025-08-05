<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverSelect;
use Facebook\WebDriver\WebDriverExpectedCondition;

class PaginaDocumento extends PaginaTeste
{
    const STA_NIVEL_ACESSO_PUBLICO  = 0;
    const STA_NIVEL_ACESSO_RESTRITO = 1;
    const STA_NIVEL_ACESSO_SIGILOSO = 2;

  public function __construct(RemoteWebDriver $driver, $testcase)
    {
      parent::__construct($driver, $testcase);
  }

  public function navegarParaAssinarDocumento(): void
    {
      // voltar ao conteúdo principal e entrar no frame de visualização
      $this->frame(null);
      $this->frame('ifrConteudoVisualizacao');
      // clica no ícone de assinatura
      $this->elByXPath("//img[@alt='Assinar Documento']")->click();
  }

  public function navegarParaConsultarDocumento(): void
    {
      sleep(2);
      $this->frame(null);
      $this->frame('ifrConteudoVisualizacao');
      $this->elByXPath("//img[contains(@alt, 'Consultar/Alterar Documento')]")->click();
  }

  public function navegarParaCancelarDocumento(): void
    {
      sleep(2);
      $this->frame(null);
      $this->frame('ifrConteudoVisualizacao');
      $this->elByXPath("//img[contains(@alt, 'Cancelar Documento')]")->click();
  }

  public function navegarParaMoverDocumento(): void
    {
      sleep(2);
      $this->frame(null);
      $this->frame('ifrConteudoVisualizacao');
      $this->elByXPath("//img[contains(@alt, 'Mover Documento para outro Processo')]")->click();
  }

  public function ehProcessoAnexado(): bool
    {
      sleep(2);
    try {
        $this->frame(null);
        $this->frame('ifrConteudoVisualizacao');
        // verifica se botão de desanexar aparece
        $this->elByXPath("//img[contains(@alt, 'Desanexar Processo')]");
        // dentro do sub-frame de visualização, verifica link de procedimento
        $this->frame('ifrVisualizacao');
        $this->elByXPath("//div[@id='divArvoreInformacao']/a[contains(@href, 'acao=procedimento_trabalhar')]");
        return true;
    } catch (\Exception $e) {
        return false;
    }
  }

  public function descricao(string $value = null): ?string
    {
      $this->frame(null);
      $this->frame('ifrConteudoVisualizacao');
      $this->frame('ifrVisualizacao');
      $input = $this->elById('txtDescricao');
    if ($value !== null) {
        $input->clear();
        $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
        $input->sendKeys($value);
    }
      return $input->getAttribute('value');
  }

  public function observacoes(string $value = null): ?string
    {
      $input = $this->elById('txaObservacoes');
    if ($value !== null) {
        $input->clear();
        $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
        $input->sendKeys($value);
    }
      return $input->getAttribute('value');
  }

  public function observacoesNaTabela(): string
    {
      $this->frame(null);
      $this->frame('ifrConteudoVisualizacao');
      return $this->elByXPath("//table[@class='infraTable']//tr[2]/td[2]")->getText();
  }

  public function dataElaboracao(string $value = null): ?string
    {
      $input = $this->elById('txtDataElaboracao');
    if ($value !== null) {
        $input->clear();
        $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
        $input->sendKeys($value);
    }
      return $input->getAttribute('value');
  }

  public function nomeAnexo(): string
    {
      $this->frame(null);
      $this->frame('ifrConteudoVisualizacao');
      $this->frame('ifrVisualizacao');
      return $this->elByXPath("//table[@id='tblAnexos']/tbody/tr/td[2]/div")->getText();
  }

  public function adicionarInteressado($arrayNomeInteressado): void
    {
      $nomes = (array) $arrayNomeInteressado;
    foreach ($nomes as $nome) {
        $input = $this->elById('txtInteressadoProcedimento');
        $input->clear();
        $nome = mb_convert_encoding($nome, 'UTF-8', 'ISO-8859-1');
        $input->sendKeys($nome. WebDriverKeys::ENTER);
        // aceita o alerta de confirmação
        $this->acceptAlert();
        sleep(2);
    }
  }

  public function listarInteressados(): array
    {
      $select = new WebDriverSelect(
          $this->elById('selInteressadosProcedimento')
      );
      return array_map(function($opt) {return $opt->getText();
      }, $select->getOptions());
  }

  public function restricao(int $nivel = null): ?int
    {
    if ($nivel !== null) {
        // seleciona o radio correspondente
      switch ($nivel) {
        case self::STA_NIVEL_ACESSO_PUBLICO:
            $this->elById('optPublico')->click();
            break;
        case self::STA_NIVEL_ACESSO_RESTRITO:
            $this->elById('optRestrito')->click();
            break;
        case self::STA_NIVEL_ACESSO_SIGILOSO:
            $this->elById('optSigiloso')->click();
            break;
      }
    }
      // retorna o valor selecionado
    if ($this->elById('optPublico')->isSelected()) {
        return self::STA_NIVEL_ACESSO_PUBLICO;
    }
    if ($this->elById('optRestrito')->isSelected()) {
        return self::STA_NIVEL_ACESSO_RESTRITO;
    }
    if ($this->elById('optSigiloso')->isSelected()) {
        return self::STA_NIVEL_ACESSO_SIGILOSO;
    }
      return null;
  }

  public function selecionarRestricao(
        int $nivel,
        string $hipotese = '',
        string $grauSigilo = ''
    ): void {
      // clica no radio
      $this->restricao($nivel);

    if ($nivel === self::STA_NIVEL_ACESSO_RESTRITO
          || $nivel === self::STA_NIVEL_ACESSO_SIGILOSO
      ) {
        $hipSelect = new WebDriverSelect(
            $this->elById('selHipoteseLegal')
        );
        $hipSelect->selectByVisibleText($hipotese);
    }
    if ($nivel === self::STA_NIVEL_ACESSO_SIGILOSO) {
        $sigSelect = new WebDriverSelect(
            $this->elById('selGrauSigilo')
        );
        $sigSelect->selectByVisibleText($grauSigilo);
    }
  }

  public function recuperarHipoteseLegal(): string
    {
      $this->frame(null);
      $this->frame('ifrConteudoVisualizacao');
      $select = new WebDriverSelect(
          $this->elById('selHipoteseLegal')
      );
      return $select->getFirstSelectedOption()->getText();
  }

  public function salvarDocumento(): void
    {
      $this->elById('btnSalvar')->click();
  }
}
