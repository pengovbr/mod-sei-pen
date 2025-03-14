<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

/**
 * Classe de teste da p�gina de tramite em bloco
 */
class PaginaTramiteEmBloco extends PaginaTeste
{
    const STA_ANDAMENTO_PROCESSAMENTO = "Aguardando Processamento";
    const STA_ANDAMENTO_CANCELADO = "Cancelado";
    const STA_ANDAMENTO_CONCLUIDO = "Conclu�do";

    /**
     * @inheritdoc
     */
  public function __construct($test)
    {
      parent::__construct($test);
  }

    /**
     * Selecionar processo
     * @param array $arrNumProtocolo
     * @return void
     */
  public function selecionarProcessos($arrNumProtocolo = array())
    {
    foreach ($arrNumProtocolo as $numProtocolo) {
        $chkProtocolo = $this->test->byXPath('//tr[contains(.,"'.$numProtocolo.'")]/td/div/label');
        $chkProtocolo->click();
    }
  }

    /**
     * Selecionar tramite em bloco
     * @return void
     */
  public function selecionarTramiteEmBloco()
    {
      $btnTramiteEmBloco = $this->test->byXPath(
          "//img[@alt='". mb_convert_encoding("Incluir Processos no Bloco de Tr�mite", 'UTF-8', 'ISO-8859-1') ."']"
      );
      $btnTramiteEmBloco->click();
  }

  /**
   * Seleciona a visualiza��o detalhada do processo.
   *
   * Este m�todo simula o clique no bot�o que troca a visualiza��o para
   * a op��o detalhada. Ele utiliza o XPath para encontrar o bot�o
   * correspondente na interface da aplica��o.
   *
   * @return void
   */
  public function visualizacaoDetalhadaAberta()
  {
    try {
        $btnVisualizacaoDetalhada = $this->test->byXPath('//a[@onclick="trocarVisualizacao(\'R\');"]');  
        if($btnVisualizacaoDetalhada){
          return true;
        }           
      } catch (Exception $e) {
        return false;
      }  
  }

  /**
   * Seleciona a visualiza��o detalhada do processo.
   *
   * Este m�todo simula o clique no bot�o que troca a visualiza��o para
   * a op��o detalhada. Ele utiliza o XPath para encontrar o bot�o
   * correspondente na interface da aplica��o.
   *
   * @return void
   */
  public function selecionarVisualizacaoDetalhada()
  {
    $btnVisualizacaoDetalhada = $this->test->byXPath('//a[@onclick="trocarVisualizacao(\'D\');"]');      
    $btnVisualizacaoDetalhada->click();
  }

  /**
   * Fecha o visualiza��o detalhada do processo.
   *
   * Este m�todo simula o clique no bot�o que troca a visualiza��o para
   * a op��o resumida.    *
   * @return void
   */
  public function fecharVisualizacaoDetalhada()
  {    
      $btnVisualizacaoResumida = $this->test->byXPath('//a[@onclick="trocarVisualizacao(\'R\');"]');      
      $btnVisualizacaoResumida->click();
  }

  /**
   * Seleciona um processo espec�fico com base no n�mero do protocolo formatado.
   *
   * Este m�todo busca o r�tulo que cont�m o n�mero do protocolo
   * fornecido e simula um clique sobre ele para selecionar o processo.
   *
   * @param string $numProtocoloFormatado O n�mero do protocolo formatado a ser selecionado.
   * @return void
   */
  public function selecionarProcesso($numProtocoloFormatado)
  {
    $btnTramiteEmBloco = $this->test->byXPath('//label[@title="' . $numProtocoloFormatado . '"]');
    $btnTramiteEmBloco->click();
  }

  /**
   * Verifica o t�tulo da p�gina atual.
   *
   * Este m�todo busca e retorna o texto do t�tulo da p�gina
   * atual, comparando-o com o t�tulo fornecido. Ele � �til para
   * garantir que a navega��o ocorreu corretamente.
   *
   * @param string $titulo O t�tulo esperado da p�gina.
   * @return string O t�tulo da p�gina atual.
   */
  public function verificarTituloDaPagina($titulo)
  {
    $tituloDaPagina = $this->test->byXPath('//div[text()="' . $titulo . '"]');
    return $tituloDaPagina->text();
  }

    /**
     * Selecionar bloco
     * @param string $selAndamento
     * @return void
     */
  public function selecionarBloco($selAndamento)
    {
      $select = $this->test->select($this->test->byId('selBlocos'));
      $select->selectOptionByValue($selAndamento);
  }

    /**
     * Clicar em salvar
     * @return void
     */
  public function clicarSalvar()
    {
      $btnSalvar = $this->test->byXPath("//button[@name='sbmCadastrarProcessoEmBloco']");
      $btnSalvar->click();
  }

    /**
     * Buscar mensagem de alerta da p�gina
     *
     * @return string
     */
  public function buscarMensagemAlerta()
    {
      $alerta = $this->test->byXPath("(//div[@id='divInfraMsg0'])[1]");
      return !empty($alerta->text()) ? $alerta->text() : "";
  }
}