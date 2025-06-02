<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaTeste
{
    /** @var RemoteWebDriver */
    protected $driver;

    /** @var PHPUnit\Framework\TestCase */
    protected $test;

  public function __construct(RemoteWebDriver $driver, $testcase)
    {
      $this->test = $testcase;
      $this->driver = $driver;
      // Implicit wait de 2 segundos
      $this->driver->manage()->timeouts()->implicitlyWait(2);
  }

    /**
     * Espera até a condição ser verdadeira
     */
  public function waitUntil(callable $condition, int $timeout = PEN_WAIT_TIMEOUT): void
    {
      $wait = new WebDriverWait($this->driver, $timeout);
      $wait->until($condition);
  }

    /**
     * Encontra elemento por CSS selector
     */
  public function elByCss(string $css)
    {
      return $this->driver->findElement(WebDriverBy::cssSelector($css));
  }

    /**
     * Encontra elemento por CSS selector
     */
  public function elementsByCss(string $css)
    {
      return $this->driver->findElements(WebDriverBy::cssSelector($css));
  }
    

    /**
     * Encontra elemento por id
     */
  public function elById(string $id)
    {
      return $this->driver->findElement(WebDriverBy::id($id));
  }

    /**
     * Encontra elemento por xpath
     */
  public function elByXPath(string $xpath)
    {
      $xpath = mb_convert_encoding($xpath, 'UTF-8', 'ISO-8859-1');
      return $this->driver->findElement(WebDriverBy::xpath($xpath));
  }
    
    /**
     * Encontra elementos por xpath
     */
  public function elementsByXPath(string $xpath)
    {
      return $this->driver->findElements(WebDriverBy::xpath($xpath));
  }
    /**
     * Encontra elemento por linktext
     */
  public function elByLinkText(string $linktext)
    {
      return $this->driver->findElement(WebDriverBy::linkText($linktext));
  }

    /**
     * Encontra elemento por partialLinkText
     */
  public function elByPartialLinkText(string $linktext)
    {
      return $this->driver->findElement(WebDriverBy::partialLinkText($linktext));
  }
    
        /**
     * Retorna o texto do body dentro de ifrConteudoVisualizacao
     */
  public function getConteudoBody(): string
    {
      $this->frame(null);
      $this->frame('ifrConteudoVisualizacao');
      return $this->elByCss('body')->getText();
  }

    /**
     * Retorna o texto do body dentro de ifrConteudoVisualizacao > ifrVisualizacao
     */
  public function getVisualizacaoBody(): string
    {
      $this->frame(null);
      $this->frame('ifrConteudoVisualizacao');
      $this->frame('ifrVisualizacao');
      return $this->elByCss('body')->getText();
  }

    /**
     * Aceita alert e retorna texto
     */
  protected function acceptAlert(): string
    {
      $alert = $this->driver->switchTo()->alert();
      $text = $alert->getText();
      $alert->accept();
      return $text;
  }

    /**
     * Retorna o título da página atual
     */
  public function titulo(): string
    {
      return $this->driver->getTitle();
  }

  public function navegarPara(string $para): void
    {
      $menu = $this->elById(('txtInfraPesquisarMenu'));
      $para = mb_convert_encoding($para, 'UTF-8', 'ISO-8859-1');
      $menu->sendKeys($para);
      $menu->sendKeys(WebDriverKeys::ENTER);
      $this->elByLinkText($para)->click();
  }
    /**
     * Acessa o menu "Controle de Processos"
     */
  public function navegarParaControleProcesso(): void
    {
    try {
        $menu = $this->elById(('txtInfraPesquisarMenu'));
        $menu->sendKeys('Controle de Processos');
        $menu->sendKeys(WebDriverKeys::ENTER);
        $this->elByLinkText('Controle de Processos')->click();
    } catch (\Exception $e) {
        $this->navegarParaControleProcessoIcone();
    }
  }

    /**
     * Acessa o menu "Controle de Processos" pelo Icone
     */
  public function navegarParaControleProcessoIcone(): void
    {
      $this->frame(null);
      $this->elByXPath("//a[@id='lnkInfraControleProcessos'] | //a[@id='lnkControleProcessos']")->click();
  }


    /**
     * Seleciona a unidade de contexto pelo link de sigla
     */
  public function selecionarUnidadeContexto(string $siglaUnidade): void
    {
      // Volta para o conteúdo principal (sem iframe)
      $this->frame(null);

      // Localiza todos os links com id 'lnkInfraUnidade' e clica no segundo
      $links = $this->driver->findElements(WebDriverBy::id('lnkInfraUnidade'));
    if (count($links) < 2) {
        throw new \RuntimeException('Link lnkInfraUnidade[2] não encontrado');
    }
      $links[1]->click();

      // Aguarda (opcional) até a tabela ser carregada, se precisar:
      $this->driver->wait(10, 500)->until(
          WebDriverExpectedCondition::presenceOfElementLocated(
              WebDriverBy::xpath("//td[contains(text(), '{$siglaUnidade}')]")
          )
      );

      // Clica na célula <td> que contém o texto da unidade de contexto
      $td = $this->elByXPath("//td[contains(normalize-space(.), '{$siglaUnidade}')]");
      $td->click();
  }

    /**
     * Troca de frame (null para voltar ao content)
     */
  public function frame(?string $nomeFrame): void
    {
    if ($nomeFrame === null) {
        $this->driver->switchTo()->defaultContent();
    } else {
        // Localiza o <iframe> pelo id ou name
        /** @var WebDriverElement $iframe */
        $iframe = $this->elByXPath("//iframe[@id='{$nomeFrame}' or @name='{$nomeFrame}']");
        $this->driver->switchTo()->frame($iframe);
    }
  }

    /**
     * Realiza pesquisa rápida
     */
  public function pesquisar(string $termoPesquisa): void
    {
      $this->driver->switchTo()->defaultContent();
      $campo = $this->elById(('txtPesquisaRapida'));
      $termoPesquisa = mb_convert_encoding($termoPesquisa, 'UTF-8', 'ISO-8859-1');
      $campo->sendKeys($termoPesquisa);
      $this->driver->getKeyboard()->pressKey(WebDriverKeys::ENTER);
  }

    /**
     * Realiza logout
     */
  public function sairSistema()
    {
    $this->frame(null);
    $this->elByXPath("//a[@id='lnkInfraSairSistema'] | //a[@id='lnkSairSistema']")->click();
  }

    /**
     * Atualiza a página
     */
  public function refresh(): void
    {
      $this->driver->navigate()->refresh();
  }


    /**
     * Retorna mensagem de alerta.
     */
  public function buscarMensagemAlerta(): string
    {
    try {
        return $this->elByXPath("(//div[@id='divInfraMsg0'])[1]")->getText();
    } catch (\Exception $e) {
        return '';
    }
  }
    /**
     * Lê o texto do alert e depois o aceita ou o descarta
     *
     * @param bool $confirm Se true, faz accept(); se false, dismiss()
     * @return string|null O texto do alerta, ou null se for array (mimic RC)
     */
  public function alertTextAndClose(bool $confirm = true): ?string
    {
      // (Opcional) aguarda até o alerta aparecer, em vez de sleep fixo
      $this->driver
           ->wait(5, 500)
           ->until(WebDriverExpectedCondition::alertIsPresent());

      // troca para o alert
      $alert = $this->driver->switchTo()->alert();

      // lê o texto
      $text = $alert->getText();
      $result = is_array($text) ? null : $text;

      // aceita ou descarta
    if ($confirm) {
        $alert->accept();
    } else {
        $alert->dismiss();
    }

      return $result;
  }
}
