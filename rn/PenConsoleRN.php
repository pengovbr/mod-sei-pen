<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

/**
 * Executa comandos por console do PHP
 * 
 * @author Join Tecnologia
 */
class PenConsoleRN extends InfraRN {
        
    protected $objRN;
    protected $strAction;
    protected $arrTokens = array();
    protected $objInfraBanco;

    public function __construct($objRN = null, $tokens = array()) {
        
        if(!is_null($objRN)) {
            
            parent::__construct();
            
            if(!is_object($objRN)) {
                throw new InfraException('Requerido objeto Infra');
            }

            if(get_parent_class($objRN) !== 'InfraRN') {
                throw new InfraException('Requerido objeto Infra que seja extendido de InfraRN');
            }

            $this->objRN = $objRN;
        }
        
        if(empty($tokens)) {
            $tokens = $_SERVER['argv'];
        }
        
        $this->criarTokens($tokens);
    }
    
    /**
     * Inicializador o banco de dados
     */
    protected function inicializarObjInfraIBanco() {
        if(empty($this->objInfraBanco)){
            $this->objInfraBanco = BancoSEI::getInstance();  
        }
        return $this->objInfraBanco;
    }
    
    /**
     * Processa os parâmetros passados ao script pelo cli
     * 
     * @param array $arguments
     * @return null
     */
    protected function criarTokens($arguments = array()){
        
        if(empty($arguments)) {
            throw new InfraException('Script não pode ser executado pela web');
        }
        
        $strScript = array_shift($arguments);

        if(!empty($this->objRN)) {
            
            $strAction = array_shift($arguments);
        
            if(substr($strAction, 0, 2) == '--') {
                throw new InfraException('O primeiro paramêtro deve ser uma action da RN');
            }
        
            $this->strAction = $strAction;
        }
        
        foreach($arguments as $key => $argument) {

            if(substr($argument, 0, 2) === '--'){

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
    public function getTokens(){
        return $this->arrTokens;
    }
    
    public function run(){
        
        if(empty($this->objRN)) {
            throw new InfraException('Nenhuma RN foi adicionada ao console');
        }
        
        if(!method_exists($this->objRN, $this->strAction)) {
            
            throw new InfraException(sprintf('Nenhuma ação "%s" foi encontrada em %s '.PHP_EOL.$this->objRN->ajuda(), $this->strAction, get_class($this->objRN)));
        }
        
        if(array_key_exists('ajuda', $this->arrTokens)) {
            
            print $this->objRN->ajuda();
            return true;
        }
        
        return call_user_func(array($this->objRN, $this->strAction), $this->getTokens());
    }
    
    public static function format($strMensagem = '', $strFonte = '', $bolBold = false){
        
       $strBold = ($bolBold !== false) ? '1' : '0';
        
       //$strMensagem = escapeshellarg($strMensagem);
        
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
    
    public static function resetAfter($strMensagem = ''){
        
        return $strMensagem. "\033[0m";
    }
}