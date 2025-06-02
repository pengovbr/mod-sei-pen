<?php

use PHPUnit\Extensions\Selenium2TestCase\WebDriverException;

class PaginaLogin extends PaginaTeste
{
    /** @var \Facebook\WebDriver\WebDriverElement */
    private $usuarioInput;

    /** @var \Facebook\WebDriver\WebDriverElement */
    private $passwordInput;

    /** @var \Facebook\WebDriver\WebDriverElement */
    private $loginButton;

  public function __construct(RemoteWebDriver $driver, $testcase)
    {
      parent::__construct($driver, $testcase);
  }

    /**
     * Preenche o campo de usu�rio
     */
  public function usuario(string $value): void
    {
    if(isset($value)) {
      $this->usuarioInput->clear();
      $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
      $this->usuarioInput->sendKeys($value);
    }
  }

    /**
     * Retorna o valor atual do campo de usu�rio
     */
  public function obterUsuario(): string
    {
      return $this->usuarioInput->getAttribute('value');
  }

    /**
     * Preenche o campo de senha
     */
  public function senha(string $value): void
    {
    if(isset($value)) {
      $this->passwordInput->clear();
      $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
      $this->passwordInput->sendKeys($value);
    }
  }

    /**
     * Retorna o valor atual do campo de senha
     */
  public function obterSenha(): string
    {
      return $this->passwordInput->getAttribute('value');
  }

    /**
     * Retorna o �rg�o exibido na barra superior
     */
  public function orgao(): string
    {
      return  $this->elById('divInfraBarraSuperior')->getText();
  }

    /**
     * Clica no bot�o de login e mant�m o webdriver para pr�xima a��o
     */
  public function submit()
    {
      $this->loginButton->click();
      return $this->driver;
  }

    /**
     * Executa o fluxo de autentica��o completo
     *
     * @param string $usuario
     * @param string $senha
     */
  public function executarAutenticacao(string $usuario = 'teste', string $senha = 'teste'): void
    {
      // Campos de usu�rio e senha
      $this->usuarioInput = $this->elById('txtUsuario');
      $this->passwordInput =  $this->elById('pwdSenha');

      // Bot�o de login pode ter ID diferente em vers�es
    try {
        $this->loginButton =  $this->elById('Acessar');
    } catch (NoSuchElementException $e) {
        $this->loginButton =  $this->elById('sbmAcessar');
    }
      $this->usuario($usuario);
      $this->senha($senha);
      $this->submit();
  }
}
