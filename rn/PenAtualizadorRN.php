<?php
/**
 * Atualizador abstrato para sistema do SEI para instalar/atualizar o módulo PEN
 * 
 * @autor Join Tecnologia
 */
abstract class PenAtualizadorRN extends InfraRN  {
    
    const VER_NONE = '0.0.0';// Modulo não instalado
    const VER_001 = '0.0.1';
    const VER_002 = '0.0.2';
    const VER_003 = '0.0.3';
    const VER_004 = '0.0.4';
    const VER_005 = '0.0.5';
    const VER_006 = '0.0.6';
    const VER_007 = '0.0.7';
   // const VER_008 = '0.0.8';
    
    protected $sei_versao;
    
    /**
     * @var string Versão mínima requirida pelo sistema para instalação do PEN
     */
    protected $versaoMinRequirida;
 
    /**
     * @var InfraIBanco Instância da classe de persistência com o banco de dados
     */
    protected $objBanco;

    /**
     * @var InfraMetaBD Instância do metadata do banco de dados
     */
    protected $objMeta;

    /**
     * @var InfraDebug Instância do debuger
     */
    protected $objDebug;

    /**
     * @var integer Tempo de execução do script
     */
    protected $numSeg = 0;

    /**
     * @var array Argumentos passados por linha de comando ao script
     */
    protected $arrArgs = array();
    
    /**
     * Inicia a conexão com o banco de dados
     */
    protected function inicializarObjMetaBanco(){
        if(empty($this->objMeta)) {
            $this->objMeta = new PenMetaBD($this->inicializarObjInfraIBanco());
        }
        return $this->objMeta;
    }
    
    /**
     * Adiciona uma mensagem ao output para o usuário
     * 
     * @return null
     */
    protected function logar($strMsg) {
        $this->objDebug->gravar($strMsg);
    }
    
    /**
     * Inicia o script criando um contator interno do tempo de execução
     * 
     * @return null
     */
    protected function inicializar($strTitulo) {

        $this->numSeg = InfraUtil::verificarTempoProcessamento();

        $this->logar($strTitulo);
    }

    /**
     * Finaliza o script informando o tempo de execução.
     * 
     * @return null
     */
    protected function finalizar() {

        $this->logar('TEMPO TOTAL DE EXECUCAO: ' . InfraUtil::verificarTempoProcessamento($this->numSeg) . ' s');
 
        $this->objDebug->setBolLigado(false);
        $this->objDebug->setBolDebugInfra(false);
        $this->objDebug->setBolEcho(false);
        
        print PHP_EOL;
        die();
    }
    
    /**
     * Método criado em função de um bug com a InfraRN na linha 69, onde usamos
     * uma instância do banco do SIP e a versão esta no banco SEI, essa verificação
     * e lançamento de uma excessão pelos bancos terem nome diferentes tava o 
     * atualizado
     * 
     * @todo Migrar para classe PenMetaBD
     * @return null
     */
    protected function setVersao($strRegexVersao, $objInfraBanco = null){
        
       InfraDebug::getInstance()->gravarInfra(sprintf('[%s->%s]', get_class($this), __FUNCTION__)); 
        
      
       if($this->getVersao($objInfraBanco)) {
           
           $sql = sprintf("UPDATE infra_parametro SET valor = '%s' WHERE nome = '%s'", $strRegexVersao, $this->sei_versao); 
       }
       else {
           
          $sql = sprintf("INSERT INTO infra_parametro(nome, valor) VALUES('%s', '%s')", $this->sei_versao, $strRegexVersao); 
       }
       
       if(empty($objInfraBanco)) {
          
            $objInfraBanco = $this->inicializarObjInfraIBanco();
        }
        
        $objInfraBanco->executarSql($sql);
        
        return $strRegexVersao;
    }
    
    /**
     * Retorna a versão atual do modulo, se já foi instalado
     * 
     * @todo Migrar para classe PenMetaBD
     * @param InfraBanco $objInfraBanco Conexão com o banco SEI ou SIP
     * @return string
     */
    protected function getVersao($objInfraBanco = null){
        
        InfraDebug::getInstance()->gravarInfra(sprintf('[%s->%s]', get_class($this), __FUNCTION__)); 

        $sql = sprintf("SELECT valor FROM infra_parametro WHERE nome = '%s'", $this->sei_versao);

        if(empty($objInfraBanco)) {
          
            $objInfraBanco = $this->inicializarObjInfraIBanco();
        }
        
        $arrResultado = $objInfraBanco->consultarSql($sql);

        if(empty($arrResultado)) {
            return null;
        }
        
        $arrLinha = current($arrResultado);
        
        return $arrLinha['valor'];
    }
    
    /**
     * Verifica se o número da versão é valido
     * 
     * @param string $strVersao Versão a ser instalada
     * @return bool
     */
    protected function isVersaoValida($strVersao = self::VER_NONE){
        
	if(empty($strVersao)) {
            return false;
        }

        // Remove os caracteres não númericos
        $strVersao = preg_replace('/\D+/', '', $strVersao);
        
        // Tem que no mínimo 3 digitos
        if (strlen($strVersao) < 3) {
            return false;
        }

        return is_numeric($strVersao) ? true : false;
    }
    
    /**
     * Verifica se um paramêtro existe, caso sim retorna o seu valor, senão
     * retorna o default especificado.
     * 
     * @param string $strChave Nome do paramêtro
     * @param string $strParam String a ser formatada com o valor do paramêtro
     * @param string $strParamDefault String que retorna caso o valor do 
     * paramêtro não exista
     * @param bool $bolAlgumFiltroUsado Ponteiro de controle para verificar se 
     * pelo menos um paramêtro foi encontrado
     * @return string
     */
    private function getStrArg($strChave = '', $strParam = '', $strParamDefault = '', &$bolAlgumFiltroUsado){
        
        if(array_key_exists($strChave, $this->arrArgs)) { 
            $bolAlgumFiltroUsado = true;
            return sprintf($strParam, str_pad($this->arrArgs[$strChave], 3, '0', STR_PAD_LEFT));
        }
        return $strParamDefault;
    }
    
    /**
     * Retorna a última versão disponivel. Verifica as constantes que iniciam
     * com VER_
     */
    private function getUltimaVersao(){
        
        $objReflection = new ReflectionClass(__CLASS__);
        $arrVersao = array_flip(preg_grep('/^VER\_/', array_flip($objReflection->getConstants())));
        sort($arrVersao);
        return array_pop($arrVersao);
    }
    
    /**
     * Encontra os métodos com notação para instalar a versão selecionada
     * 
     * @return string Número da versão
     */
    protected function executarControlado(){
        
        $this->inicializarObjMetaBanco()
            ->isDriverSuportado()
            ->isDriverPermissao()
            ->isVersaoSuportada(SEI_VERSAO, $this->versaoMinRequirida);
        
        $arrMetodo = array();
        
        // Retorna a última versão disponibilizada pelo script. Sempre tenta atualizar
        // para versão mais antiga
        $strVersaoInstalar = $this->getUltimaVersao();
        
        //throw new InfraException($strVersaoInstalar);
        $objInfraBanco = $this->inicializarObjInfraIBanco();
        // Versão atual
        $strPenVersao = $this->getVersao($objInfraBanco);
        if(!$this->isVersaoValida($strPenVersao)) {
            // Não instalado
            $strPenVersao = $this->setVersao(self::VER_NONE, $objInfraBanco); 
        }

        $numPenVersao = substr($strPenVersao, -1);
        $numVersaoInstalar = intval(substr($strVersaoInstalar, -1));
        
        $bolAlgumFiltroUsado = false;
        $strRegexRelease = $this->getStrArg('release', '(R%s)', '(R[0-9]{1,3})?', $bolAlgumFiltroUsado);
        $strRegexSprint = $this->getStrArg('sprint', '(S%s)', '(S[0-9]{1,3})?', $bolAlgumFiltroUsado);
        $strRegexItem = $this->getStrArg('user-story', '(US%s)', '(US|IW[0-9]{1,3})?', $bolAlgumFiltroUsado);
        $strRegexItem = $this->getStrArg('item-worker', '(IW%s)', $strRegexItem, $bolAlgumFiltroUsado);
        
        // Instalar todas atualizações
        if($bolAlgumFiltroUsado === false) {

            $strRegexVersao = sprintf('[%d-%d]', ($numPenVersao + 1), $numVersaoInstalar); 
        }
        // Instalar somente a solicitada
        else {
            // Caso algum paramêtro seja adicionado não deve passar para próxima versão
            $strVersaoInstalar = $strPenVersao;
            $strRegexVersao = intval(substr($strPenVersao, -1) + 1);
        }
        
        // instalarV[0-9]{1,2}[0-9](R[0-9]{1,3})?(S[0-9]{1,3})?(US|IW[0-9]{1,4})?
        $strRegex = sprintf('/^instalarV[0-9][0-9]%s%s%s%s/i',
            $strRegexVersao,
            $strRegexRelease,
            $strRegexSprint,
            $strRegexItem
        );

        // Tenta encontrar métodos que iniciem com instalar
        $arrMetodo  = (array)preg_grep ($strRegex, get_class_methods($this)); 
        
        if(empty($arrMetodo)) {
            
            throw new InfraException(sprintf('NENHUMA ATUALIZACAO FOI ENCONTRADA SUPERIOR A VERSAO %s DO MODULO PEN', $strPenVersao));
        }
        else {
            
            foreach($arrMetodo as $strMetodo) {

                $this->{$strMetodo}();
            } 
        }
        $this->setVersao($strVersaoInstalar, $objInfraBanco);
        
        return $strVersaoInstalar;
    }
    
    /**
     * Método que inicia o processo
     */
    public function atualizarVersao() {
        
        $this->inicializar('INICIANDO ATUALIZACAO DO MODULO PEN NO SEI VERSAO '.SEI_VERSAO);
        
        try {

            $strRegexVersao = $this->executar();
            $this->logar('ATUALIZADA VERSAO: '.$strRegexVersao);  
        }
        catch(InfraException $e) {
            
            $this->logar('Erro: '.$e->getStrDescricao());
        }
        catch (\Exception $e) {
            
            $this->logar('Erro: '.$e->getMessage());
        }
        
        $this->finalizar();
    }
    
    /**
     * Construtor
     * 
     * @param array $arrArgs Argumentos enviados pelo script
     */
    public function __construct($arrArgs = array()) {
                
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '-1');
        @ini_set('zlib.output_compression', '0');
        @ini_set('implicit_flush', '1');
        ob_implicit_flush();
        
        $this->arrArgs = $arrArgs;
        
        $this->inicializarObjMetaBanco();
        
        $this->objDebug = InfraDebug::getInstance();
        $this->objDebug->setBolLigado(true);
        $this->objDebug->setBolDebugInfra(true);
        $this->objDebug->setBolEcho(true);
        $this->objDebug->limpar();
    }
}