<?php

class PaginaProcesso extends PaginaTeste
{
    const STA_STATUS_PROCESSO_ABERTO = 1;
    const STA_STATUS_PROCESSO_CONCLUIDO = 2;

  public function __construct($test)
    {
      parent::__construct($test);
  }

  public function concluirProcesso()
    {
        $this->test->frame(null);
        $this->test->frame("ifrConteudoVisualizacao");
        $concluirProcessoButton = $this->test->byXPath("//img[@alt='Concluir Processo']");
        $concluirProcessoButton->click();
        $this->test->frame("ifrVisualizacao");
        $confirmarConcluirProcessoButton = $this->test->byId('sbmSalvar');
        $confirmarConcluirProcessoButton->click();
  }

  public function incluirDocumento()
    {
      $this->test->frame(null);
      $this->test->frame("ifrConteudoVisualizacao");
      $incluirDocumentoButton = $this->test->byXPath("//img[@alt='Incluir Documento']");
      $incluirDocumentoButton->click();
  }

  public function enviarProcesso()
    {
      $this->test->frame(null);
      $this->test->frame("ifrConteudoVisualizacao");
      $this->test->byXPath("//img[@alt='Enviar Processo']")->click();
  }

  public function cancelarTramitacaoExterna()
    {
      $this->test->frame(null);
      $this->test->frame("ifrConteudoVisualizacao");
      $this->test->byXPath(mb_convert_encoding("//img[@alt='Cancelar Tramitação Externa']", 'UTF-8', 'ISO-8859-1'))->click();
  }

  public function navegarParaEditarProcesso()
    {
      $this->test->frame(null);
      $this->test->frame("ifrConteudoVisualizacao");
      $this->editarProcessoButton = $this->test->byXPath("//img[@alt='Consultar/Alterar Processo']");
      $this->editarProcessoButton->click();
  }

  public function navegarParaOrdenarDocumentos()
  {
    $this->test->frame(null);
    $this->test->frame("ifrConteudoVisualizacao");
    $button = $this->test->byXPath(mb_convert_encoding("//img[@alt='Ordenar Árvore do Processo']", 'UTF-8', 'ISO-8859-1'));
    $button->click();
  }

  public function trocarOrdenacaoDocumentos()
  {
    $this->test->frame(null);
    $this->test->frame("ifrConteudoVisualizacao");
    $this->test->frame("ifrVisualizacao");
    $this->test->byXPath("//*[@id='selRelProtocoloProtocolo']/option[1]")->click();
    sleep(1);
    $this->test->byXPath("//a[@onclick='objLupaRelProtocoloProtocolo.moverAbaixo();']")->click();
    sleep(1);
    $this->test->byXPath("//*[@id='divInfraBarraComandosSuperior']/button[@value='Salvar']")->click();
  }
  
  public function navegarParaTramitarProcesso()
    {
      $this->test->waitUntil(function($testCase) {
          $this->selecionarProcesso();

          $this->test->frame(null);
          $this->test->frame("ifrConteudoVisualizacao");
            
          $this->editarProcessoButton = $this->test->byXPath("//img[@alt='Envio Externo de Processo']");
          $this->editarProcessoButton->click();
          sleep(2);

          $this->test->frame("ifrVisualizacao");
          $testCase->assertStringContainsString('Envio Externo de Processo', $testCase->byCssSelector('body')->text());
          return true;
      }, PEN_WAIT_TIMEOUT);
  }

  public function navegarParaConsultarAndamentos()
    {
      $this->test->waitUntil(function($testCase) {
          $this->test->frame(null);
          $this->test->frame("ifrArvore");
          $testCase->byLinkText('Consultar Andamento')->click();

          $this->test->frame(null);
          $this->test->frame("ifrConteudoVisualizacao");
          $this->test->frame("ifrVisualizacao");
          sleep(2);
          $testCase->assertStringContainsString(mb_convert_encoding('Histórico do Processo', 'UTF-8', 'ISO-8859-1'), $testCase->byCssSelector('body')->text());
          return true;
      }, PEN_WAIT_TIMEOUT);
  }

  public function navegarParaConsultarRecibos()
    {
      $this->test->waitUntil(function($testCase) {
          // Selecionar processo na Ã¡rvore
          $this->selecionarProcesso();

          $this->test->frame(null);
          $this->test->frame("ifrConteudoVisualizacao");
          $this->editarProcessoButton = $this->test->byXPath("//img[@alt='Consultar Recibos']");
          $this->editarProcessoButton->click();
          sleep(2);
            
          $this->test->frame("ifrVisualizacao");
          $testCase->assertStringContainsString('Consultar Recibos', $testCase->byCssSelector('body')->text());
          return true;
      }, PEN_WAIT_TIMEOUT);
  }

  public function navegarParaAnexarProcesso()
    {
      $this->test->waitUntil(function($testCase) {
          $this->selecionarProcesso();

          $this->test->frame(null);
          $this->test->frame("ifrConteudoVisualizacao");
          $this->editarProcessoButton = $this->test->byXPath("//img[@alt='Anexar Processo']");
          $this->editarProcessoButton->click();
          sleep(2);

          $this->test->frame("ifrVisualizacao");
          $testCase->assertStringContainsString(mb_convert_encoding('Anexação de Processos', 'UTF-8', 'ISO-8859-1'), $testCase->byCssSelector('body')->text());
          return true;
      }, PEN_WAIT_TIMEOUT);
  }

  public function navegarParaTramitarProcessoInterno()
    {
      $this->enviarProcesso();
  }

  public function informacao()
    {
      $this->test->frame(null);
      $this->test->frame("ifrConteudoVisualizacao");
      sleep(10);
      $this->test->frame("ifrVisualizacao");
      return $this->test->byId('divArvoreInformacao')->text();
  }

  public function processoAberto()
    {
    try
      {
        $this->test->frame(null);
        $this->test->frame("ifrConteudoVisualizacao");
        $this->test->byXPath("//img[@alt='Reabrir Processo']");
        return false;
    }
    catch(Exception $e)
      {
        return true;
    }
  }

  public function processoBloqueado()
    {
    try
      {
        $this->test->frame(null);
        $this->test->frame("ifrArvore");
        $this->test->byXPath("//img[@title='Processo Bloqueado']");
        return true;
    }
    catch(Exception $e)
      {
        return false;
    }
  }

  public function deveSerDocumentoAnexo($bolDevePossuir, $nomeDocumentoArvore)
    {
    try
      {
        $this->test->frame(null);
        $this->test->frame("ifrArvore");
      if($bolDevePossuir){
            $idAnexo=$this->test->byXPath("//span[contains(text(),'" . $nomeDocumentoArvore . "')]")->attribute('id');
            $idAnexo=str_replace("span", "", $idAnexo);
            $this->test->byXPath("//img[contains(@id,'iconMD_PEN_DOC_REF" . $idAnexo . "')]");
      }
        return true;
    }
    catch(Exception $e)
      {
        return false;
    }
  }

  public function ehDocumentoCancelado($nomeDocumentoArvore)
    {
    try
      {
        $to = $this->test->timeouts()->getLastImplicitWaitValue();
        $this->test->timeouts()->implicitWait(300);
        $this->test->frame(null);
        $this->test->frame("ifrArvore");
        $this->test->byLinkText($nomeDocumentoArvore)->byXPath(".//preceding-sibling::a[1]/img[contains(@src,'svg/documento_cancelado.svg?')]");
        return true;
    }
    catch(Exception $e)
      {
        return false;
    }finally{
        $this->test->timeouts()->implicitWait($to);
    }
  }

  public function ehDocumentoMovido($nomeDocumentoArvore)
    {
    try
      {
        $to = $this->test->timeouts()->getLastImplicitWaitValue();
        $this->test->timeouts()->implicitWait(300);
        $this->test->frame(null);
        $this->test->frame("ifrArvore");
        $this->test->byLinkText($nomeDocumentoArvore)->byXPath(".//preceding-sibling::a[1]/img[contains(@src,'svg/documento_movido.svg?')]");
        return true;
    }
    catch(Exception $e)
      {
        return false;
    }finally{
        $this->test->timeouts()->implicitWait($to);
    }
  }

  private function selecionarItemArvore($nomeArvore)
    {
      $this->test->frame(null);
      $this->test->frame("ifrArvore");
      $this->test->byLinkText($nomeArvore)->click();
  }

  public function selecionarDocumento($nomeDocumentoArvore)
    {
      $this->selecionarItemArvore($nomeDocumentoArvore);
  }

  public function selecionarProcesso()
    {
      $this->selecionarItemArvore($this->listarArvoreProcesso()[0]);
      sleep(1);
  }

  public function listarDocumentos()
    {
      $itens = $this->listarArvoreProcesso();
      return (count($itens) > 1) ? array_slice($itens, 1) : null;
  }

  private function listarArvoreProcesso()
    {
      $this->test->frame(null);
      $this->test->frame("ifrArvore");
      $itens = $this->test->elements($this->test->using('css selector')->value('div.infraArvore > a > span[id]'));
      return array_map(function($item) {return $item->text();
      }, $itens);
  }

  public function validarBotaoExiste($botao)
  {
    try {
        $this->test->frame(null);
        $this->test->frame("ifrConteudoVisualizacao");
        $botao = $this->test->byXPath("//img[@alt='$botao']");
        return true;
    } catch (\Exception $e) {
        return false;
    }
  }
  
}
