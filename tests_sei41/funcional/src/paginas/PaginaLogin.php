<?php

use PHPUnit\Extensions\Selenium2TestCase\WebDriverException;

class PaginaLogin extends PaginaTeste
{
  public function __construct($test)
    {
        
      parent::__construct($test);
      $test->frame(null);

      $test->waitUntil(function($testCase) {
          $this->usuarioInput = $testCase->byId('txtUsuario');
          $this->passwordInput = $testCase->byId('pwdSenha');

          if ($this->usuarioInput && $this->passwordInput){
            return true;
          }
          
          $testCase->refresh();
          return false;
      }, PEN_WAIT_TIMEOUT);
    try{
        $this->loginButton = $test->byId('Acessar');
    }
      //SEI 4.0.12 alterou para sbmAcessar
    catch (WebDriverException $wde){
        $this->loginButton = $test->byId('sbmAcessar');
    }
  }

  public function usuario($value)
    {
    if(isset($value)) {
        $this->usuarioInput->value($value);
    }

      return $this->usuarioInput->value();
  }

  public function senha($value)
    {
    if(isset($value)) {
        $this->passwordInput->value($value);
    }

      return $this->passwordInput->value();
  }

  public function orgao()
    {
      return $this->test->byId('divInfraBarraSuperior')->text();
  }

  public function submit()
    {
      $this->loginButton->click();
      return $this->test;
  }

  public static function executarAutenticacao($test, $usuario = "teste", $senha = "teste")
    {
      $paginaLogin = new PaginaLogin($test);
      $paginaLogin->usuario($usuario);
      $paginaLogin->senha($senha);
      $paginaLogin->submit();
  }
}
