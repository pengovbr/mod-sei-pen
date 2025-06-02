<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaCadastrarProcessoEmBloco extends PaginaTeste
{
  public function __construct(RemoteWebDriver $driver, $testcase)
    {
      parent::__construct($driver, $testcase);
  }

    /**
     * Método contrutor
     * 
     * @return void
     */
  public function navegarListagemBlocoDeTramite(): void
    {
      $input = $this->elById('txtInfraPesquisarMenu');
      $input->clear();
      $input->sendKeys(mb_convert_encoding('Blocos de Trâmite Externo', 'UTF-8', 'ISO-8859-1'));

      $this->elByXPath("//a[@link='md_pen_tramita_em_bloco']")->click();
  }

    /**
     * Setar parametro para novo mapeamento de orgãos externos
     * 
     * @return void
     */
  public function setarParametros(string $estrutura, string $origem): void
    {
      $this->selectRepositorio($estrutura);
      $this->selectUnidade($origem, 'Origem');
  }

    /**
     * Seleciona o repositório pelo label.
     */
  private function selectRepositorio(string $sigla): void
    {
      $select = new WebDriverSelect(
          $this->elById('selRepositorioEstruturas')
      );
    if ($sigla !== '') {
        $select->selectByVisibleText($sigla);
    }
  }

    /**
     * Seleciona a unidade origem no campo de busca.
     */
  private function selectUnidade(
      string $nomeUnidade,
      string $origemDestino,
      ?string $hierarquia = null
  ): string {
    // 1) Prepara o input e já envia ENTER para disparar o autocomplete
    $input = $this->elById('txtUnidade');
    $input->clear();
    $input->sendKeys($nomeUnidade . WebDriverKeys::ENTER);
  
    // 2) Monta o texto que esperamos no link (com hierarquia, se houver)
    $labelEsperado = $nomeUnidade . ($hierarquia ? " - {$hierarquia}" : '');
  
    // 3) Aguarda até o link aparecer e clicar nele, lidando com eventual alerta
    $this->waitUntil(function() use ($labelEsperado, $input) {
        // Fecha alerta de autocomplete, caso apareça
      try {
        if ($this->alertTextAndClose()) {
          // re-dispara ENTER para recarregar sugestões
          $input->sendKeys(WebDriverKeys::ENTER);
        }
      } catch (\Exception $e) {
          // nenhum alerta => segue
      }
  
        // Clica no link parcial pelo texto completo
        $this->elByPartialLinkText($labelEsperado)->click();
        return true;
    }, PEN_WAIT_TIMEOUT);
  
    // 4) Retorna o valor final do input
    return $input->getAttribute('value');
  }

    /**
     * Inicia um novo bloco de trâmite.
     */
  public function novoBlocoDeTramite(): void
    {
      $this->elById('bntNovo')->click();
  }

    /**
     * Preenche a descrição do novo bloco.
     */
  public function criarNovoBloco(string $descricao = 'Bloco para teste automatizado'): void
    {
      $input = $this->elById('txtDescricao');
      $input->clear();
      $descricao = mb_convert_encoding($descricao, 'UTF-8', 'ISO-8859-1');
      $input->sendKeys($descricao);
  }

    /**
     * Edita o primeiro bloco e opcionalmente altera a descrição.
     */
  public function editarBlocoDeTramite(string $descricao = null): void
    {
      $this->elByXPath("(//img[@title='Alterar Bloco'])[1]")
           ->click();
    if ($descricao !== null) {
        $input = $this->elById('txtDescricao');
        $input->clear();
        $descricao = mb_convert_encoding($descricao, 'UTF-8', 'ISO-8859-1');
        $input->sendKeys($descricao);
    }
  }

    /**
     * Seleciona o primeiro bloco e exclui.
     */
  public function selecionarExcluirBloco(): void
    {
      $this->elByXPath("(//label[@for='chkInfraItem0'])[1]")
           ->click();
      $this->elById('btnExcluir')->click();
      $this->acceptAlert();
  }

    /**
     * Retorna a mensagem de alerta, se houver.
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

    /**
     * Conta quantas imagens de 'Recusado' estão na tela.
     */
  public function buscarQuantidadeProcessosRecusados(): int
    {
      return count($this->driver->findElements(
          WebDriverBy::xpath("//img[@title='Recusado']")
      ));
  }

    /**
     * Executa o trâmite externo do processo em bloco.
     */
  public function tramitarProcessoExternamente(
      string $repositorio,
      string $unidadeDestino,
      string $unidadeDestinoHierarquia,
      bool $urgente = false,
      ?callable $callbackEnvio = null,
      int $timeout = PEN_WAIT_TIMEOUT
  ): void {
    // 1) Executa os selects e o botão Enviar
    $this->selectRepositorio($repositorio);
    $this->selectUnidade($unidadeDestino, 'origem', $unidadeDestinoHierarquia);
    $this->btnEnviar();
  
    // 2) Captura (e fecha) o alerta inicial, mas só lança exceção
    //    se veio texto e não foi passado callback personalizado
    try {
        $mensagemAlerta = $this->alertTextAndClose(true);
    } catch (\Exception $e) {
        $mensagemAlerta = null;
    }
    if ($mensagemAlerta && $callbackEnvio === null) {
        throw new \Exception($mensagemAlerta);
    }
  
    // 3) Define o callback padrão, se não vier um por parâmetro
    if ($callbackEnvio === null) {
        $sucessoMensagem = mb_convert_encoding(
            'Trâmite externo do processo finalizado com sucesso!',
            'UTF-8',
            'ISO-8859-1'
        );
  
        $callbackEnvio = function (RemoteWebDriver $driver) use ($sucessoMensagem) {
            // Entra no frame de confirmação
            $this->frame('ifrEnvioProcesso');
  
            // Verifica se a mensagem de sucesso apareceu
            $texto = $this->buscarMensagemAlerta();
          if (mb_strpos($texto, $sucessoMensagem) !== false) {
              // Fecha a modal
              $this->elById('btnFechar')->click();
              return true;
          }
  
            return false;
        };
    }
  
    // 4) Aguarda a condição do callback
    try {
        $this->waitUntil($callbackEnvio, $timeout);
    } finally {
        // Sempre restaura o frame principal e a visualização
        $this->frame(null);
        // $this->frame('ifrVisualizacao');
    }
  
    // 5) Pausa curta para dar tempo de estabilizar a UI
    sleep(1);
  }
  

    /**
     * Realiza validação de recebimento no destinatário.
     */
  public function realizarValidacaoRecebimentoProcessoNoDestinatario(array $processoTeste): void
    {
      $protocolo = $processoTeste['PROTOCOLO'];
      $this->waitUntil(function() use ($protocolo) {
          sleep(5);
          $this->paginaBase->navegarParaControleProcesso();
          $this->paginaControleProcesso->abrirProcesso($protocolo);
          return true;
      }, PEN_WAIT_TIMEOUT);

      $this->paginaProcesso->listarDocumentos();
  }

    /**
     * Retorna o texto da terceira coluna da primeira linha.
     */
  public function retornarTextoColunaDaTabelaDeBlocos(): string
    {
      $row = $this->elByXPath('//tr[@class="infraTrClara odd"]');
      return $row->findElement(
          WebDriverBy::xpath('./td[3]')
      )->getText();
  }

    /**
     * Conta linhas na tabela de blocos.
     */
  public function retornarQuantidadeDeProcessosNoBloco(): int
    {
      return count($this->driver->findElements(
          WebDriverBy::cssSelector('#tblBlocos tbody tr')
      ));
  }

  public function bntTramitarBloco(): void
    {
      $this->elByXPath("(//img[@title='Tramitar Bloco'])[1]")
           ->click();
  }

  public function bntVisualizarProcessos(): void
    {
      $this->elByXPath("(//img[@title='Visualizar Processos'])[1]")
           ->click();
  }

  public function btnSelecionarTodosProcessos(): void
    {
      $this->elByXPath("//*[@id='imgInfraCheck']")->click();
  }

  public function btnComandoSuperiorExcluir(): void
    {
      $this->elByXPath(
          "//*[@id='divInfraBarraComandosSuperior']/button[@value='Excluir']"
      )->click();
      $this->acceptAlert();
  }

  public function btnComandoSuperiorFechar(): void
    {
      $this->elByXPath(
          "//*[@id='divInfraBarraComandosSuperior']/button[@value='Fechar']"
      )->click();
  }

  public function btnSalvar(): void
    {
      $this->elByXPath("//button[@type='submit' and @value='Salvar']")->click();
  }

  public function btnEnviar(): void
    {
      $this->elByXPath("//button[@type='button' and @value='Enviar']")->click();
  }
}