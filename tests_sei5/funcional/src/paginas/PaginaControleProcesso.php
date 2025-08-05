<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

class PaginaControleProcesso extends PaginaTeste
{

  public function __construct(RemoteWebDriver $driver, $testcase)
    {
      parent::__construct($driver, $testcase);
  }

/**
 * Retorna todas as linhas de processo nos painéis habilitados.
 *
 * @param  bool  $processosGerados
 * @param  bool  $processosRecebidos
 * @return WebDriverElement[]
 */
  protected function obterLinhasProcessos(bool $processosGerados, bool $processosRecebidos): array
  {
    // 1) Define quais painéis buscar
    $paineis = [];
    if ($processosGerados) {
        $paineis[] = 'tblProcessosGerados';
    }
    if($processosRecebidos) { 
        $paineis[] = 'tblProcessosRecebidos';
    }

    $resultado = [];

    // 2) Para cada painel, tenta capturar as <tr> e mescla ao resultado
    foreach ($paineis as $idPainel) {
      try {
          // elById() já faz findElement(WebDriverBy::id(...))
          $painel = $this->elById($idPainel);
          // findElements retorna [] se não achar nenhum <tr>
          $linhas = $painel->findElements(WebDriverBy::cssSelector('tr'));
          $resultado = array_merge($resultado, $linhas);
      } catch (\Exception $e) {
          // painel não existe / inacessível ? ignora e continua
      }
    }

    return $resultado;
  }

  /**
   * Clica no link correspondente ao protocolo informado
   *
   * @param string $strProtocolo
   */
  public function abrirProcesso(string $strProtocolo): void
  {
      sleep(1);
      $this->elByLinkText($strProtocolo)->click();
  }

  protected function listarProcessos($processosGerados, $processosRecebidos)
    {
      $listaProtocolos = array();
      $processosRows = $this->obterLinhasProcessos($processosGerados, $processosRecebidos);
    if (!empty($processosRows)) {
      for ($i = 1; $i < count($processosRows); $i++) {
          $listaProtocolos[] = trim($processosRows[$i]->getText());
      }
    }
      return $listaProtocolos;
  }

  public function processosGerados()
    {
      return $this->listarProcessos(true, false);
  }

  public function processosRecebidos()
    {
      return $this->listarProcessos(false, true);
  }

  public function contemProcesso($numeroProcesso, $processosGerados = true, $processosRecebidos = true)
    {
      $listaProcessos = $this->listarProcessos($processosGerados, $processosRecebidos);
      return ($listaProcessos != null) ? in_array($numeroProcesso, $listaProcessos) : false;
  }

  public function contemAlertaProcessoRecusado($numeroProcesso)
    {
      $processosRows = $this->obterLinhasProcessos(true, true);
    foreach ($processosRows as $row) {
      try {
        if (strpos($row->getText(), $numeroProcesso) !== false) {
            $icones = $row->findElements(WebDriverBy::cssSelector('img'));
          foreach ($icones as $icone) {
            if (strpos($icone->getAttribute('src'), 'pen_tramite_recusado.png') !== false) {
                return true;
            }
          }
        }
      } catch (\Exception $e) {
          return false;
      }
    }

      return false;
  }

    /**
     * Localiza processo pela descrição via atributo onmouseover
     *
     * @param string $descricao
     * @return string|false
     */
  public function localizarProcessoPelaDescricao(string $descricao)
    {
      $processosRows = $this->obterLinhasProcessos(true, true);
    foreach ($processosRows as $row) {
      try {
        $links = $row->findElements(WebDriverBy::cssSelector('a'));
        foreach ($links as $link) {
            $onmouseover = $link->getAttribute('onmouseover') ?: '';
          if (strpos($onmouseover, $descricao) !== false) {
            return $link->getText();
          }
        }
      } catch (\Exception $e) {
          return false;
      }
    }
      return false;
  }

}
