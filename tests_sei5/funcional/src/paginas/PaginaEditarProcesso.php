<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverSelect;

class PaginaEditarProcesso extends PaginaTeste
{
    const STA_NIVEL_ACESSO_PUBLICO  = 0;
    const STA_NIVEL_ACESSO_RESTRITO = 1;
    const STA_NIVEL_ACESSO_SIGILOSO = 2;

  public function __construct(RemoteWebDriver $driver, $testcase)
    {
      parent::__construct($driver, $testcase);
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
      $this->frame(null);
      $this->frame('ifrConteudoVisualizacao');
      $this->frame('ifrVisualizacao');

      $input = $this->elById('txaObservacoes');
    if ($value !== null) {
        $input->clear();
        $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
        $input->sendKeys($value);
    }

      return $input->getAttribute('value');
  }

  public function protocoloInformado(string $value = null): ?string
    {
      $this->frame(null);
      $this->frame('ifrConteudoVisualizacao');
      $this->frame('ifrVisualizacao');

      $input = $this->elById('txtProtocoloInformar');
    if ($value !== null) {
        $input->clear();
        $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
        $input->sendKeys($value);
    }

      return $input->getAttribute('value');
  }

  public function dataGeracaoProtocolo(string $value = null): ?string
    {
      $this->frame(null);
      $this->frame('ifrConteudoVisualizacao');
      $this->frame('ifrVisualizacao');

      $input = $this->elById('txtDtaGeracaoInformar');
    if ($value !== null) {
        $input->clear();
        $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
        $input->sendKeys($value);
    }

      return $input->getAttribute('value');
  }

  public function restricao(int $nivel = null): ?int
    {
      $this->frame(null);
      $this->frame('ifrConteudoVisualizacao');
      $this->frame('ifrVisualizacao');

    if ($nivel !== null) {
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

  public function adicionarInteressado($nomes): void
    {
      $lista = is_array($nomes) ? $nomes : [$nomes];

      $this->frame(null);
      $this->frame('ifrConteudoVisualizacao');
      $this->frame('ifrVisualizacao');

    foreach ($lista as $nome) {
        $input = $this->elById('txtInteressadoProcedimento');
        $input->clear();
        $nome = mb_convert_encoding($nome, 'UTF-8', 'ISO-8859-1');
        $input->sendKeys($nome. WebDriverKeys::ENTER);
        $this->acceptAlert();
        sleep(2);
    }
  }

  public function listarInteressados(): array
    {
      $this->frame(null);
      $this->frame('ifrConteudoVisualizacao');
      $this->frame('ifrVisualizacao');

      $select = new WebDriverSelect($this->elById('selInteressadosProcedimento'));
      return array_map(function($opt){ return $opt->getText();
      }, $select->getOptions());
  }

  public function salvarProcesso(): void
    {
      $this->frame(null);
      $this->frame('ifrConteudoVisualizacao');
      $this->frame('ifrVisualizacao');

      $this->elById('btnSalvar')->click();
  }

  public function selecionarRestricao(int $nivel, string $hipotese = '', string $grauSigilo = ''): void
    {
      $this->restricao($nivel);

    if ($nivel === self::STA_NIVEL_ACESSO_RESTRITO || $nivel === self::STA_NIVEL_ACESSO_SIGILOSO) {
        $hipSelect = new WebDriverSelect($this->elById('selHipoteseLegal'));
        $hipSelect->selectByVisibleText($hipotese);
    }
    if ($nivel === self::STA_NIVEL_ACESSO_SIGILOSO) {
        $sigSelect = new WebDriverSelect($this->elById('selGrauSigilo'));
        $sigSelect->selectByVisibleText($grauSigilo);
    }
  }

  public function recuperarHipoteseLegal(): string
    {
      $this->frame(null);
      $this->frame('ifrConteudoVisualizacao');
      $this->frame('ifrVisualizacao');

      $select = new WebDriverSelect($this->elById('selHipoteseLegal'));
      return $select->getFirstSelectedOption()->getText();
  }

  public function gerarProtocolo(): string
    {
      $seq = str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);
      return "999990.{$seq}/2015-00";
  }
}
