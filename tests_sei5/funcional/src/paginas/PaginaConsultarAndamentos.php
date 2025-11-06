<?php

class PaginaConsultarAndamentos extends PaginaTeste
{
  public function __construct($test)
    {
      parent::__construct($test);
  }

  public function contemTramite($mensagemTramite)
    {
      $this->test->frame(null);
      $this->test->frame("ifrConteudoVisualizacao");
      return strpos($this->test->byCssSelector('body')->text(), $mensagemTramite) !== false;
  }

  public function contemTramiteProcessoEmTramitacao($strUnidadeDestino)
    {
      $this->test->frame(null);
      $this->test->frame("ifrConteudoVisualizacao");
      $this->test->frame("ifrVisualizacao");
      $mensagemTramite = "Processo em tramitação externa para $strUnidadeDestino";
      return strpos($this->test->byCssSelector('body')->text(), $mensagemTramite) !== false;
  }

  public function contemTramiteProcessoRecebido($strUnidadeDestino)
    {
      $this->test->frame(null);
      $this->test->frame("ifrConteudoVisualizacao");
      $this->test->frame("ifrVisualizacao");
      $mensagemTramite = "Recebido em $strUnidadeDestino";
      return strpos($this->test->byCssSelector('body')->text(), $mensagemTramite) !== false;
  }

  public function contemTramiteProcessoRejeitado($strUnidadeDestino, $strMotivo)
    {
      $this->test->frame(null);
      $this->test->frame("ifrConteudoVisualizacao");
      $this->test->frame("ifrVisualizacao");
      $mensagemTramite = mb_convert_encoding("O processo foi recusado pelo org�o $strUnidadeDestino pelo seguinte motivo: $strMotivo", 'UTF-8', 'ISO-8859-1');
      return strpos($this->test->byCssSelector('body')->text(), $mensagemTramite) !== false;
  }
}
