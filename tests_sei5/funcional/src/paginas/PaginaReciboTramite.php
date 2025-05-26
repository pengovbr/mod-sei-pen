<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverWait;
use Facebook\WebDriver\Exception\TimeOutException;

class PaginaReciboTramite extends PaginaTeste
{
  public function __construct(RemoteWebDriver $driver, $testcase)
    {
      parent::__construct($driver, $testcase);
  }

    /**
     * Verifica se existe um trâmite no histórico, opcionalmente conferindo recibos.
     *
     * @param string $mensagemTramite
     * @param bool   $verificaReciboEnvio
     * @param bool   $verificaReciboConclusao
     * @return bool
     */
  public function contemTramite(
        string $mensagemTramite,
        bool $verificaReciboEnvio = false,
        bool $verificaReciboConclusao = false
    ): bool {
      // Navega até o frame de visualização
      $this->frame(null);
      $this->frame('ifrConteudoVisualizacao');
      $this->frame('ifrVisualizacao');

      // Busca todas as linhas de histórico
      $linhas = $this->elementsByCss('div.infraAreaTabela > table tr');
      $msg_confirmacao = mb_convert_encoding('Recibo de Confirmação de Envio', 'UTF-8', 'ISO-8859-1');
      $msg_conclusao = mb_convert_encoding('Recibo de Conclusão de Trâmite', 'UTF-8', 'ISO-8859-1');

    foreach ($linhas as $linha) {
        $colunas = $linha->findElements(WebDriverBy::cssSelector('td'));
      if (count($colunas) !== 2) {
        continue;
      }

        // Verifica o texto do trâmite
        $texto = $colunas[0]->getText();
      if (strpos($texto, $mensagemTramite) === false) {
          continue;
      }

        // Verifica recibo de envio, se solicitado
      if ($verificaReciboEnvio && ! $this->elementContains(
            $colunas[1],
            'a > img[title="'.$msg_confirmacao.'"]'
        )) {
          return false;
      }

        // Verifica recibo de conclusão, se solicitado
      if ($verificaReciboConclusao && ! $this->elementContains(
            $colunas[1],
            'a > img[title="'.$msg_conclusao.'"]'
        )) {
          return false;
      }

        // Encontrou o trâmite (e recibos, se exigidos)
        return true;
    }

      // Não encontrou nenhuma linha válida
      return false;
  }

    /**
     * Verifica se um elemento contém um seletor CSS dentro de dado timeout.
     *
     * @param WebDriverElement $element
     * @param string           $cssSelector
     * @param int              $timeoutSeconds
     * @return bool
     */
  protected function elementContains(
        WebDriverElement $element,
        string $cssSelector,
        int $timeoutSeconds = 1
    ): bool {
      $by = WebDriverBy::cssSelector($cssSelector);
    try {
        $wait = new WebDriverWait($this->driver, $timeoutSeconds);
        $wait->until(function() use ($element, $by) {
            return count($element->findElements($by)) > 0;
        });
        return true;
    } catch (TimeOutException $e) {
        return false;
    }
  }
}