<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;

/**
 * Classe responsável por representar a página de envio de correspondência eletrônica (e-mail) do SEI
 * 
 * Esta classe encapsula as interações com o formulário de envio de e-mail do SEI,
 * permitindo o preenchimento automatizado de campos e validação do envio.
 */
class PaginaEnviarEmail extends PaginaTeste
{
    /**
     * Construtor da classe
     * 
     * @param RemoteWebDriver $driver Instância do WebDriver para controle do navegador
     * @param mixed $testcase Instância do caso de teste PHPUnit
     */
    public function __construct(RemoteWebDriver $driver, $testcase)
    {
        parent::__construct($driver, $testcase);
    }

    /**
     * Realiza o envio de correspondência eletrônica através do formulário do SEI
     * 
     * Este método executa as seguintes ações:
     * 1. Aguarda a abertura da janela popup de envio de e-mail
     * 2. Troca o contexto para a nova janela
     * 3. Preenche o campo de assunto
     * 4. Seleciona o nível de acesso como "Público"
     * 5. Preenche o corpo da mensagem
     * 6. Adiciona destinatário de teste usando Select2
     * 7. Clica no botão Enviar
     * 8. Valida a mensagem de sucesso no alert
     * 9. Retorna para a janela principal
     * 
     * @param string|null $strCaminhoArquivoAnexo Caminho (acessível pelo navegador) de arquivo a ser anexado ao e-mail
     * @throws \Exception Se o texto do alert não confirmar o envio do e-mail
     * @return void
     */
    public function enviar(?string $strCaminhoArquivoAnexo = null): void
    {
        // Aguardar a nova janela abrir
        sleep(3);
        
        // Trocar para a última janela aberta
        $this->switchToLastWindow();
        
        // Aguardar até que o campo de destinatários esteja disponível
        $this->waitUntil(function() {
            try {
                $this->driver->findElement(WebDriverBy::id('txtAssunto'));
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }, PEN_WAIT_TIMEOUT);
        
        sleep(1);
        
        // Preencher assunto
        $campoAssunto = $this->elById('txtAssunto');
        $campoAssunto->clear();
        $campoAssunto->sendKeys('Assunto Teste');
        
        sleep(1);
        
        // Clicar no label do radio button Público (ao invés do input que está interceptado)
        $this->elById('lblPublico')->click();
        
        sleep(1);
        
        // Preencher mensagem
        $campoMensagem = $this->elById('txaMensagem');
        $campoMensagem->clear();
        $campoMensagem->sendKeys('Mensagem de teste');
        
        sleep(1);
        
        // Preencher destinatários usando Select2
        // Clicar no campo Select2 para abrir
        $campoSelect2 = $this->elByCss('.select2-search-field input');
        $campoSelect2->click();
        
        sleep(1);
        
        // Digitar o email no campo Select2
        $campoSelect2->sendKeys('teste@teste.com');
        sleep(1);
        $campoSelect2->sendKeys(WebDriverKeys::ENTER);
        
        sleep(1);

        if (!is_null($strCaminhoArquivoAnexo)) {
            $this->anexarArquivo($strCaminhoArquivoAnexo);
        }

        // Clicar no botão Enviar
        $this->elByName('btnEnviar')->click();
        
        sleep(1);
        
        // Validar e aceitar o alert
        $textoAlert = $this->alertTextAndClose(true);
        
        if (strpos($textoAlert, 'E-mail enviado') === false) {
            throw new \Exception("Texto do alert não corresponde ao esperado. Recebido: {$textoAlert}");
        }
        
        sleep(1);
        
        // Fechar a janela e voltar para a janela original
        $this->switchToFirstWindow();
    }

    /**
     * Anexa um arquivo ao e-mail, aguardando a conclusão do upload e sua inclusão na tabela de anexos
     *
     * @param string $strCaminhoArquivo Caminho do arquivo acessível pelo navegador
     * @return void
     */
    private function anexarArquivo(string $strCaminhoArquivo): void
    {
        $this->elById('filArquivo')->sendKeys($strCaminhoArquivo);

        $strNomeArquivo = basename($strCaminhoArquivo);
        $this->waitUntil(function () use ($strNomeArquivo) {
            $this->elByXPath("//table[@id='tblAnexos']//td[contains(.,'{$strNomeArquivo}')]");
            return true;
        }, PEN_WAIT_TIMEOUT);

        sleep(1);
    }
}
