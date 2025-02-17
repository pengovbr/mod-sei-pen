<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

/**
 * Classe de teste da página de tramite em bloco
 */
class PaginaTramiteEmBloco extends PaginaTeste
{
    const STA_ANDAMENTO_PROCESSAMENTO = "Aguardando Processamento";
    const STA_ANDAMENTO_CANCELADO = "Cancelado";
    const STA_ANDAMENTO_CONCLUIDO = "Concluído";

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
          "//img[@alt='". mb_convert_encoding("Incluir Processos no Bloco de Trâmite", 'UTF-8', 'ISO-8859-1') ."']"
      );
      $btnTramiteEmBloco->click();
  }

  /**
   * Seleciona a visualização detalhada do processo.
   *
   * Este método simula o clique no botão que troca a visualização para
   * a opção detalhada. Ele utiliza o XPath para encontrar o botão
   * correspondente na interface da aplicação.
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
   * Seleciona a visualização detalhada do processo.
   *
   * Este método simula o clique no botão que troca a visualização para
   * a opção detalhada. Ele utiliza o XPath para encontrar o botão
   * correspondente na interface da aplicação.
   *
   * @return void
   */
  public function selecionarVisualizacaoDetalhada()
  {
    $btnVisualizacaoDetalhada = $this->test->byXPath('//a[@onclick="trocarVisualizacao(\'D\');"]');      
    $btnVisualizacaoDetalhada->click();
  }

  /**
   * Fecha o visualização detalhada do processo.
   *
   * Este método simula o clique no botão que troca a visualização para
   * a opção resumida.    *
   * @return void
   */
  public function fecharVisualizacaoDetalhada()
  {    
      $btnVisualizacaoResumida = $this->test->byXPath('//a[@onclick="trocarVisualizacao(\'R\');"]');      
      $btnVisualizacaoResumida->click();
  }

  /**
   * Seleciona um processo específico com base no número do protocolo formatado.
   *
   * Este método busca o rótulo que contém o número do protocolo
   * fornecido e simula um clique sobre ele para selecionar o processo.
   *
   * @param string $numProtocoloFormatado O número do protocolo formatado a ser selecionado.
   * @return void
   */
  public function selecionarProcesso($numProtocoloFormatado)
  {
    $btnTramiteEmBloco = $this->test->byXPath('//label[@title="' . $numProtocoloFormatado . '"]');
    $btnTramiteEmBloco->click();
  }

  /**
   * Verifica o título da página atual.
   *
   * Este método busca e retorna o texto do título da página
   * atual, comparando-o com o título fornecido. Ele é útil para
   * garantir que a navegação ocorreu corretamente.
   *
   * @param string $titulo O título esperado da página.
   * @return string O título da página atual.
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
     * Buscar mensagem de alerta da página
     *
     * @return string
     */
  public function buscarMensagemAlerta()
    {
      $alerta = $this->test->byXPath("(//div[@id='divInfraMsg0'])[1]");
      return !empty($alerta->text()) ? $alerta->text() : "";
  }
}