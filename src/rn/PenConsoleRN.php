<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Executa comandos por console do PHP
 */
class PenConsoleRN extends InfraRN
{
        
    protected $objRN;
    protected $strAction;
    protected $arrTokens = [];
    protected $objInfraBanco;

  public function __construct($objRN = null, $tokens = [])
    {
        
    if(!is_null($objRN)) {
            
        parent::__construct();
            
      if(!is_object($objRN)) {
        throw new InfraException('Módulo do Tramita: Requerido objeto Infra');
      }
        throw new InfraException('Módulo do Tramita: Requerido objeto Infra que seja extendido de InfraRN');
    }
        
    if(empty($tokens)) {
        $tokens = $_SERVER['argv'];
    }
        
      $this->criarTokens($tokens);
  }
    
    /**
     * Inicializador o banco de dados
     */
  protected function inicializarObjInfraIBanco()
    {
    if(empty($this->objInfraBanco)) {
        $this->objInfraBanco = BancoSEI::getInstance();  
    }
      return $this->objInfraBanco;
  }
    
    /**
     * Processa os parâmetros passados ao script pelo cli
     *
     * @param array $arguments
     */
  protected function criarTokens($arguments = [])
    {
        
    if(empty($arguments)) {
        throw new InfraException('Módulo do Tramita: Script não pode ser executado pela web');
    }
        
      array_shift($arguments);

    if(!empty($this->objRN)) {
            
        $strAction = array_shift($arguments);
        
      if(substr($strAction, 0, 2) == '--') {
          throw new InfraException('Módulo do Tramita: O primeiro paramêtro deve ser uma action da RN');
      }
        
        $this->strAction = $strAction;
    }
        
    foreach($arguments as $key => $argument) {

      if(substr($argument, 0, 2) === '--') {

          $string = preg_replace('/^--/', '', $argument);
          $array = explode('=', $string);

          $key = array_shift($array);
          $value = (count($array) > 0) ? array_shift($array) : true;

          $this->arrTokens[$key] = $value;
      }
    } 
  }
    
    /**
     * Retorna os parâmetros
     */
  public function getTokens()
    {
      return $this->arrTokens;
  }
    
  public function run()
    {
        
    if(empty($this->objRN)) {
        throw new InfraException('Módulo do Tramita: Nenhuma RN foi adicionada ao console');
    }
        
    if(!method_exists($this->objRN, $this->strAction)) {
            
        throw new InfraException(sprintf('Nenhuma ação "%s" foi encontrada em %s '.PHP_EOL.$this->objRN->ajuda(), $this->strAction, get_class($this->objRN)));
    }
        
    if(array_key_exists('ajuda', $this->arrTokens)) {
            
        print $this->objRN->ajuda();
        return true;
    }
        
      return call_user_func([$this->objRN, $this->strAction], $this->getTokens());
  }
    
  public static function format($strMensagem = '', $strFonte = '', $bolBold = false)
    {
        
      $strBold = ($bolBold !== false) ? '1' : '0';
                
    if(!empty($strFonte)) {
            
      switch($strFonte){

        case 'green':  
             $strMensagem = "\033[".$strBold.";32m".$strMensagem; 
            break;
                
        case 'red':  
            $strMensagem = "\033[".$strBold.";31m".$strMensagem; 
            break;
                
        case 'blue':  
              $strMensagem = "\033[".$strBold.";34m".$strMensagem; 
            break;
                
        case 'yellow':
              $strMensagem = "\033[".$strBold.";33m".$strMensagem;
            break;

      }
    }
      return static::resetAfter($strMensagem);
  }
    
  public static function resetAfter($strMensagem = '')
    {
        
      return $strMensagem. "\033[0m";
  }
}
