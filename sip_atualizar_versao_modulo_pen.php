<?php
require_once dirname(__FILE__).'/../web/Sip.php';

set_include_path(implode(PATH_SEPARATOR, array(
    realpath(__DIR__ . '/../../infra/infra_php'),
    get_include_path(),
)));


/**
 * Mapeamento dos metadados sobre a estrutura do banco de dados
 *
 * @author Join Tecnologia
 */
class PenMetaBD extends InfraMetaBD {
    
    const NNULLO = 'NOT NULL';
    const SNULLO = 'NULL';

    /**
     * 
     * @return string
     */
    public function adicionarValorPadraoParaColuna($strNomeTabela, $strNomeColuna, $strValorPadrao, $bolRetornarQuery = false){
        
        $objInfraBanco = $this->getObjInfraIBanco();
        
        $strTableDrive = get_parent_class($objInfraBanco);
        $strQuery = '';
        
        switch($strTableDrive) {

            case 'InfraMySqli':
                $strQuery = sprintf("ALTER TABLE `%s` ALTER COLUMN `%s` SET DEFAULT '%s'", $strNomeTabela, $strNomeColuna, $strValorPadrao);
                break;
                
            case 'InfraSqlServer':
                 $strQuery =  sprintf("ALTER TABLE [%s] ADD DEFAULT('%s') FOR [%s]", $strNomeTabela, $strValorPadrao, $strNomeColuna);
            
            case 'InfraOracle':
                break;
        }
        
        if($bolRetornarQuery === false) {
            
            $objInfraBanco->executarSql($strQuery);
        }
        else {
        
            return  $strQuery;
        }
    }
    
    /**
     * Verifica se o usuário do drive de conexão possui permissão para criar/ remover
     * estruturas
     * 
     * @return PenMetaBD
     */
    public function isDriverPermissao(){
        
        $objInfraBanco = $this->getObjInfraIBanco();

        if(count($this->obterTabelas('sei_teste'))==0){
            $objInfraBanco->executarSql('CREATE TABLE sei_teste (id '.$this->tipoNumero().' NULL)');
        }
      
        $objInfraBanco->executarSql('DROP TABLE sei_teste');
        
        return $this;
    }
    
    /**
     * Verifica se o banco do SEI é suportador pelo atualizador
     * 
     * @throws InfraException
     * @return PenMetaBD
     */
    public function isDriverSuportado(){
        
        $strTableDrive = get_parent_class($this->getObjInfraIBanco());
            
        switch($strTableDrive) {

           case 'InfraMySqli':
                // Fix para bug de MySQL versão inferior ao 5.5 o default engine
                // é MyISAM e não tem suporte a FOREING KEYS
                $version = $this->getObjInfraIBanco()->consultarSql('SELECT VERSION() as versao');
                $version = $version[0]['versao'];
                $arrVersion = explode('.', $version);
                
                if($arrVersion[0].$arrVersion[1] < 56){
                    $this->getObjInfraIBanco()->executarSql('@SET STORAGE_ENGINE=InnoDB'); 
                }
            case 'InfraSqlServer':
            case 'InfraOracle':
                break;

            default:
                throw new InfraException('BANCO DE DADOS NAO SUPORTADO: ' . $strTableDrive);

        }
        
        return $this;
    }
    
    /**
     * Verifica se a versão sistema é compativel com a versão do módulo PEN
     * 
     * @throws InfraException
     * @return PenMetaBD
     */
    public function isVersaoSuportada($strRegexVersaoSistema, $strVerMinRequirida){
        
        $numVersaoRequerida = intval(preg_replace('/\D+/', '', $strVerMinRequirida));
        $numVersaoSistema = intval(preg_replace('/\D+/', '', $strRegexVersaoSistema));
        
        if($numVersaoRequerida > $numVersaoSistema){
            throw new InfraException('VERSAO DO FRAMEWORK PHP INCOMPATIVEL (VERSAO ATUAL '.$strRegexVersaoSistema.', VERSAO REQUERIDA '.$strVerMinRequirida.')');
        }
        
        return $this;
    }
    
    /**
     * Apaga a chave primária da tabela
     * 
     * @throws InfraException
     * @return PenMetaBD
     */
    public function removerChavePrimaria($strNomeTabela, $strNomeChave){
        
        if($this->isChaveExiste($strNomeTabela, $strNomeChave)) {
        
            $strTableDrive = get_parent_class($this->getObjInfraIBanco());

            switch($strTableDrive) {

                case 'InfraMySqli':
                    $this->getObjInfraIBanco()->executarSql('ALTER TABLE '.$strNomeTabela.' DROP PRIMARY KEY');
                    break;

                case 'InfraSqlServer':
                    $this->getObjInfraIBanco()->executarSql('ALTER TABLE '.$strNomeTabela.' DROP CONSTRAINT '.$strNomeChave);
                    break;

                case 'InfraOracle':
                    break;
            }
        }
        return $this;
    }
        
    public function isChaveExiste($strNomeTabela = '', $strNomeChave = ''){
        
        $objInfraBanco = $this->getObjInfraIBanco();
        $strTableDrive = get_parent_class($objInfraBanco);
            
        switch($strTableDrive) {

            case 'InfraMySqli':
                $strSql = " SELECT COUNT(CONSTRAINT_NAME) AS EXISTE
                            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                            WHERE CONSTRAINT_SCHEMA = '".$objInfraBanco->getBanco()."'
                            AND TABLE_NAME = '".$strNomeTabela."'
                            AND CONSTRAINT_NAME = '".$strNomeChave."'";
                break;
            
            case 'InfraSqlServer':
                
                $strSql = " SELECT COUNT(CONSTRAINT_NAME) AS EXISTE 
                            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                            WHERE CONSTRAINT_CATALOG = '".$objInfraBanco->getBanco()."'
                            AND TABLE_NAME = '".$strNomeTabela."'
                            AND CONSTRAINT_NAME = '".$strNomeChave."'";
                break;
                
            case 'InfraOracle':
                 $strSql = "SELECT 0 AS EXISTE";
                break;
        }
        
        $strSql = preg_replace('/\s+/', ' ', $strSql);
        $arrDados = $objInfraBanco->consultarSql($strSql);

        return (intval($arrDados[0]['EXISTE']) > 0) ? true : false;
    }
    
    public function adicionarChaveUnica($strNomeTabela = '', $arrNomeChave = array()){
        
        $this->getObjInfraIBanco()
                ->executarSql('ALTER TABLE '.$strNomeTabela.' ADD CONSTRAINT UK_'.$strNomeTabela.' UNIQUE('.implode(', ', $arrNomeChave).')');
    }
    
    public function renomearTabela($strNomeTabelaAtual, $strNomeTabelaNovo){
        
        if($this->isTabelaExiste($strNomeTabelaAtual)) {
            
            $objInfraBanco = $this->getObjInfraIBanco();
        
            $strTableDrive = get_parent_class($objInfraBanco);
            $strQuery = '';

            switch ($strTableDrive) {

                    case 'InfraMySqli':
                        $strQuery = sprintf("ALTER TABLE `%s` RENAME TO `%s`", $strNomeTabelaAtual, $strNomeTabelaNovo);
                        break;

                    case 'InfraSqlServer':
                        $strQuery = sprintf("sp_rename '%s', '%s'", $strNomeTabelaAtual, $strNomeTabelaNovo);

                    case 'InfraOracle':
                        $strQuery = sprintf("RENAME TABLE %s TO %s", $strNomeTabelaAtual, $strNomeTabelaNovo);
                        break;
            }
            
            $objInfraBanco->executarSql($strQuery);
        }
    }
    
    
    /**
     * Verifica se uma tabela existe no banco
     * 
     * @throws InfraException
     * @return bool
     */
    public function isTabelaExiste($strNomeTabela = ''){
        
        $objInfraBanco = $this->getObjInfraIBanco();
        $strTableDrive = get_parent_class($objInfraBanco);
            
        switch($strTableDrive) {

            case 'InfraMySqli':
                $strSql = "SELECT COUNT(TABLE_NAME) AS EXISTE
                            FROM INFORMATION_SCHEMA.TABLES
                            WHERE TABLE_SCHEMA = '".$objInfraBanco->getBanco()."'
                            AND TABLE_NAME = '".$strNomeTabela."'";
                break;
            
            case 'InfraSqlServer':
                
                $strSql = "SELECT COUNT(TABLE_NAME) AS EXISTE 
                            FROM INFORMATION_SCHEMA.TABLES
                            WHERE TABLE_CATALOG = '".$objInfraBanco->getBanco()."'
                            AND TABLE_NAME = '".$strNomeTabela."'";
                break;
                
            case 'InfraOracle':
                 $strSql = "SELECT 0 AS EXISTE";
                break;
        }
        
        $strSql = preg_replace('/\s+/', ' ', $strSql);
        $arrDados = $objInfraBanco->consultarSql($strSql);

        return (intval($arrDados[0]['EXISTE']) > 0) ? true : false;
    }
    
    public function isColuna($strNomeTabela = '', $strNomeColuna = ''){
              
        $objInfraBanco = $this->getObjInfraIBanco();
        $strTableDrive = get_parent_class($objInfraBanco);
            
        switch($strTableDrive) {

            case 'InfraMySqli':
                $strSql = "SELECT COUNT(TABLE_NAME) AS EXISTE
                            FROM INFORMATION_SCHEMA.COLUMNS
                            WHERE TABLE_SCHEMA = '".$objInfraBanco->getBanco()."'
                            AND TABLE_NAME = '".$strNomeTabela."' 
                            AND COLUMN_NAME = '".$strNomeColuna."'";
                break;
            
            case 'InfraSqlServer':
                
                $strSql = "SELECT COUNT(COLUMN_NAME) AS EXISTE
                           FROM INFORMATION_SCHEMA.COLUMNS
                           WHERE TABLE_CATALOG = '".$objInfraBanco->getBanco()."'
                           AND TABLE_NAME = '".$strNomeTabela."' 
                           AND COLUMN_NAME = '".$strNomeColuna."'";
                break;
                
            case 'InfraOracle':
                 $strSql = "SELECT 0 AS EXISTE";
                break;
        }
        
        $strSql = preg_replace('/\s+/', ' ', $strSql);
        $arrDados = $objInfraBanco->consultarSql($strSql);

        return (intval($arrDados[0]['EXISTE']) > 0) ? true : false;
        
        
    }
    
    /**
     * Cria a estrutura da tabela no padrão ANSI
     * 
     * @throws InfraException
     * @return PenMetaBD
     */
    public function criarTabela($arrSchema = array()){
        
        $strNomeTabela = $arrSchema['tabela'];
        
        
        if($this->isTabelaExiste($strNomeTabela)) {
            return $this;
        }
        
        $objInfraBanco = $this->getObjInfraIBanco();
        $arrColunas = array();
        $arrStrQuery = array();

        foreach($arrSchema['cols'] as $strNomeColuna => $arrColunaConfig) {
            
            list($strTipoDado, $strValorPadrao) = $arrColunaConfig;
            
            if($strValorPadrao != self::SNULLO && $strValorPadrao != self::NNULLO) {
                
                $arrStrQuery[] = $this->adicionarValorPadraoParaColuna($strNomeTabela, $strNomeColuna, $strValorPadrao, true);
                $strValorPadrao = self::NNULLO;
            }

            $arrColunas[] = $strNomeColuna.' '.$strTipoDado.' '.$strValorPadrao;
        }
        
        $objInfraBanco->executarSql('CREATE TABLE '.$strNomeTabela.' ('.implode(', ', $arrColunas).')');
        
        if(!empty($arrSchema['pk'])) {
            
           $this->adicionarChavePrimaria($strNomeTabela, 'pk_'.$strNomeTabela, $arrSchema['pk']); 
           
           if(count($arrSchema['pk']) > 1) {
               
               foreach($arrSchema['pk'] as $strPk) {
           
                    $objInfraBanco->executarSql('CREATE INDEX idx_'.$strNomeTabela.'_'.$strPk.' ON '.$strNomeTabela.'('.$strPk.')');
               }
           }
        }
        
        if(array_key_exists('uk', $arrSchema) && !empty($arrSchema['uk'])) {
            
            $this->adicionarChaveUnica($strNomeTabela, $arrSchema['uk']);
        }
        
        if(!empty($arrSchema['fks'])) {
            
            foreach($arrSchema['fks'] as $strTabelaOrigem => $array) {
                
                $strNomeFK = 'fk_'.$strNomeTabela.'_'.$strTabelaOrigem;
                $arrCamposOrigem = (array)array_shift($array);
                $arrCampos = $arrCamposOrigem;

                if(!empty($array)) {
                    $arrCampos = (array)array_shift($array);
                }

                $this->adicionarChaveEstrangeira($strNomeFK, $strNomeTabela, $arrCampos, $strTabelaOrigem, $arrCamposOrigem);   
            }
        }
        
        if(!empty($arrStrQuery)) {
            
            foreach($arrStrQuery as $strQuery) {    
                $objInfraBanco->executarSql($strQuery);
            }
        }
        
        return $this;
    }
    
    /**
     * Apagar a estrutura da tabela no banco de dados
     * 
     * @throws InfraException
     * @return PenMetaBD
     */
    public function removerTabela($strNomeTabela = ''){
        
        $this->getObjInfraIBanco()->executarSql('DROP TABLE '.$strNomeTabela);
        return $this;
    }
    
    public function adicionarChaveEstrangeira($strNomeFK, $strTabela, $arrCampos, $strTabelaOrigem, $arrCamposOrigem) {
        
        if(!$this->isChaveExiste($strTabela, $strNomeFK)) {
            parent::adicionarChaveEstrangeira($strNomeFK, $strTabela, $arrCampos, $strTabelaOrigem, $arrCamposOrigem);
        }
        return $this;
    }
    
    public function adicionarChavePrimaria($strTabela, $strNomePK, $arrCampos) {
        
        if(!$this->isChaveExiste($strTabela, $strNomePK)) {
            parent::adicionarChavePrimaria($strTabela, $strNomePK, $arrCampos);
        }
        return $this;
    }
    
    public function alterarColuna($strTabela, $strColuna, $strTipo, $strNull = '') {
        parent::alterarColuna($strTabela, $strColuna, $strTipo, $strNull);
        return $this;
    }
    
    public function excluirIndice($strTabela, $strIndex) {
        if($this->isChaveExiste($strTabela, $strFk)) {
            parent::excluirIndice($strTabela, $strIndex);
        }
        return $this;
    }
    
    public function excluirChaveEstrangeira($strTabela, $strFk) {
        if($this->isChaveExiste($strTabela, $strFk)) {
            parent::excluirChaveEstrangeira($strTabela, $strFk);
        }
        return $this;
    }
}

abstract class PenAtualizadorRN extends InfraRN {

    const VER_NONE = '0.0.0'; // Modulo não instalado
    const VER_001 = '0.0.1';
    const VER_002 = '0.0.2';
    const VER_003 = '0.0.3';
    const VER_004 = '0.0.4';
    const VER_005 = '0.0.5';
    const VER_100 = '1.0.0';
    
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
    protected function inicializarObjMetaBanco() {
        if (empty($this->objMeta)) {
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
    protected function setVersao($strRegexVersao, $objInfraBanco = null) {

        InfraDebug::getInstance()->gravarInfra(sprintf('[%s->%s]', get_class($this), __FUNCTION__));


        if ($this->getVersao($objInfraBanco)) {

            $sql = sprintf("UPDATE infra_parametro SET valor = '%s' WHERE nome = '%s'", $strRegexVersao, $this->sei_versao);
        } else {

            $sql = sprintf("INSERT INTO infra_parametro(nome, valor) VALUES('%s', '%s')", $this->sei_versao, $strRegexVersao);
        }

        if (empty($objInfraBanco)) {

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
    protected function getVersao($objInfraBanco = null) {

        InfraDebug::getInstance()->gravarInfra(sprintf('[%s->%s]', get_class($this), __FUNCTION__));

        $sql = sprintf("SELECT valor FROM infra_parametro WHERE nome = '%s'", $this->sei_versao);

        if (empty($objInfraBanco)) {

            $objInfraBanco = $this->inicializarObjInfraIBanco();
        }

        $arrResultado = $objInfraBanco->consultarSql($sql);

        if (empty($arrResultado)) {
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
    protected function isVersaoValida($strVersao = self::VER_NONE) {

        if (empty($strVersao)) {
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
    private function getStrArg($strChave = '', $strParam = '', $strParamDefault = '', &$bolAlgumFiltroUsado) {

        if (array_key_exists($strChave, $this->arrArgs)) {
            $bolAlgumFiltroUsado = true;
            return sprintf($strParam, str_pad($this->arrArgs[$strChave], 3, '0', STR_PAD_LEFT));
        }
        return $strParamDefault;
    }

    /**
     * Retorna a última versão disponivel. Verifica as constantes que iniciam
     * com VER_
     */
    private function getUltimaVersao() {

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
    protected function executarControlado() {

        $this->inicializarObjMetaBanco()
                ->isDriverSuportado()
                ->isDriverPermissao();
                //->isVersaoSuportada(SEI_VERSAO, $this->versaoMinRequirida);

        $arrMetodo = array();

        // Retorna a última versão disponibilizada pelo script. Sempre tenta atualizar
        // para versão mais antiga
        $strVersaoInstalar = $this->getUltimaVersao();

        //throw new InfraException($strVersaoInstalar);
        $objInfraBanco = $this->inicializarObjInfraIBanco();
        // Versão atual
        $strPenVersao = $this->getVersao($objInfraBanco);
        if (!$this->isVersaoValida($strPenVersao)) {
            // Não instalado
            $strPenVersao = $this->setVersao(self::VER_NONE, $objInfraBanco);
        }

        $numPenVersao = str_replace('.', '', $strPenVersao);
        
        $numVersaoInstalar = intval(preg_replace('/\D+/', '', $strVersaoInstalar));
    //$numVersaoInstalar = intval(substr($strVersaoInstalar, -1));

        $bolAlgumFiltroUsado = false;
        $strRegexRelease = $this->getStrArg('release', '(R%s)', '(R[0-9]{1,3})?', $bolAlgumFiltroUsado);
        $strRegexSprint = $this->getStrArg('sprint', '(S%s)', '(S[0-9]{1,3})?', $bolAlgumFiltroUsado);
        $strRegexItem = $this->getStrArg('user-story', '(US%s)', '(US|IW[0-9]{1,3})?', $bolAlgumFiltroUsado);
        $strRegexItem = $this->getStrArg('item-worker', '(IW%s)', $strRegexItem, $bolAlgumFiltroUsado);

        // Instalar todas atualizações
        if ($bolAlgumFiltroUsado === false) {
               
            /*list($v1, $r1, $s1) = explode('.', '1.0.1');
            list($v2, $r2, $s2) = explode('.', $strVersaoInstalar);

            $s1 = intval($s1) + 1;
            $r1 = intval($r1) + 1; */

            // 0.0.5 - 1.5.0
            // 1.1.1 - 1.5.0

            // (00[6-9]|1[5-9][0-9])
            // (11[1-9]|1[5-9][0-9])
           // $strRegexVersao = sprintf('(%s[%s-9][%s-9]|%s[%s-9][%s-9])', $v1, $r1, $s1, $v2, $r2, $s2);
            
             $strRegexVersao = sprintf('[%d-%d]', ($numPenVersao + 1), $numVersaoInstalar);
        }
        // Instalar somente a solicitada
        else {
            // Caso algum paramêtro seja adicionado não deve passar para próxima versão
            $strVersaoInstalar = $strPenVersao;
            $strRegexVersao = intval(substr($strPenVersao, -1) + 1);
        }

        // instalarV[0-9]{1,2}[0-9](R[0-9]{1,3})?(S[0-9]{1,3})?(US|IW[0-9]{1,4})?
        $strRegex = sprintf('/^instalarV%s%s%s%s/i', $strRegexVersao, $strRegexRelease, $strRegexSprint, $strRegexItem
        );

        
        
        // Tenta encontrar métodos que iniciem com instalar
        $arrMetodo = (array) preg_grep($strRegex, get_class_methods($this));

        $proximaVersao = $numPenVersao + 1;
        
        foreach($arrMetodo as $key => $metodo){
            $vers = str_replace('instalarV', '', $metodo);
            $vers = (int) substr($vers, 0, 3);
            
            if($proximaVersao > $vers){
                unset($arrMetodo[$key]);
            }
        } 
        
        if (empty($arrMetodo)) {

            throw new InfraException(sprintf('NENHUMA ATUALIZACAO FOI ENCONTRADA SUPERIOR A VERSAO %s DO MODULO PEN', $strPenVersao));
        } else {

            foreach ($arrMetodo as $strMetodo) {

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

        $this->inicializar('INICIANDO ATUALIZACAO DO MODULO PEN NO SEI VERSAO ' . SIP_VERSAO);

        try {

            $strRegexVersao = $this->executar();
            $this->logar('ATUALIZADA VERSAO: ' . $strRegexVersao);
        } catch (InfraException $e) {

            $this->logar('Erro: ' . $e->getStrDescricao());
        } catch (\Exception $e) {

            $this->logar('Erro: ' . $e->getMessage());
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

class PenAtualizarSipRN extends PenAtualizadorRN {

    protected $versaoMinRequirida = '1.30.0';
    protected $sei_versao = 'PEN_VERSAO_MODULO_SIP';
    private $arrRecurso = array();
    private $arrMenu = array();

    /**
     * Retorna/Cria a conexão com o banco de dados
     * 
     * @return InfraIBanco
     */
    protected function inicializarObjInfraIBanco() {
        if (empty($this->objBanco)) {

            $this->objBanco = BancoSip::getInstance();
        }
        return $this->objBanco;
    }

    /**
     * Retorna o ID do sistema
     * 
     * @return int
     */
    protected function getNumIdSistema($strSigla = 'SIP') {

        $objDTO = new SistemaDTO();
        $objDTO->setStrSigla($strSigla);
        $objDTO->setNumMaxRegistrosRetorno(1);
        $objDTO->retNumIdSistema();

        $objRN = new SistemaRN();
        $objDTO = $objRN->consultar($objDTO);

        return (empty($objDTO)) ? '0' : $objDTO->getNumIdSistema();
    }

    /**
     * 
     * @return int Código do Menu
     */
    protected function getNumIdMenu($strMenu = 'Principal', $numIdSistema = 0) {

        $objDTO = new MenuDTO();
        $objDTO->setNumIdSistema($numIdSistema);
        $objDTO->setStrNome($strMenu);
        $objDTO->setNumMaxRegistrosRetorno(1);
        $objDTO->retNumIdMenu();

        $objRN = new MenuRN();
        $objDTO = $objRN->consultar($objDTO);

        if (empty($objDTO)) {
            throw new InfraException('Menu ' . $strMenu . ' não encontrado.');
        }

        return $objDTO->getNumIdMenu();
    }

    /**
     * 
     * @return int Código do Recurso gerado
     */
    protected function criarRecurso($strNome = '', $strDescricao = null, $numIdSistema = 0) {

        $objDTO = new RecursoDTO();
        $objDTO->setNumIdSistema($numIdSistema);
        $objDTO->setStrNome($strNome);
        $objDTO->setNumMaxRegistrosRetorno(1);
        $objDTO->retNumIdRecurso();

        $objBD = new RecursoBD($this->getObjInfraIBanco());
        $objDTO = $objBD->consultar($objDTO);

        if (empty($objDTO)) {

            $objDTO = new RecursoDTO();
            $objDTO->setNumIdRecurso(null);
            $objDTO->setStrDescricao($strDescricao);
            $objDTO->setNumIdSistema($numIdSistema);
            $objDTO->setStrNome($strNome);
            $objDTO->setStrCaminho('controlador.php?acao=' . $strNome);
            $objDTO->setStrSinAtivo('S');

            $objDTO = $objBD->cadastrar($objDTO);
        }

        $this->arrRecurso[] = $objDTO->getNumIdRecurso();

        return $objDTO->getNumIdRecurso();
    }

    /**
     * Cria um menu
     * 
     * @return int
     */
    protected function criarMenu($strRotulo = '', $numSequencia = 10, $numIdItemMenuPai = null, $numIdMenu = null, $numIdRecurso = null, $numIdSistema = 0) {

        $objDTO = new ItemMenuDTO();
        $objDTO->setNumIdItemMenuPai($numIdItemMenuPai);
        $objDTO->setNumIdSistema($numIdSistema);
        $objDTO->setStrRotulo($strRotulo);
        $objDTO->setNumIdRecurso($numIdRecurso);
        $objDTO->setNumMaxRegistrosRetorno(1);
        $objDTO->retNumIdItemMenu();

        $objBD = new ItemMenuBD($this->inicializarObjInfraIBanco());
        $objDTO = $objBD->consultar($objDTO);

        if (empty($objDTO)) {

            $objDTO = new ItemMenuDTO();
            $objDTO->setNumIdMenu($numIdMenu);
            $objDTO->setNumIdMenuPai($numIdMenu);
            $objDTO->setNumIdItemMenu(null);
            $objDTO->setNumIdItemMenuPai($numIdItemMenuPai);
            $objDTO->setNumIdSistema($numIdSistema);
            $objDTO->setNumIdRecurso($numIdRecurso);
            $objDTO->setStrRotulo($strRotulo);
            $objDTO->setStrDescricao(null);
            $objDTO->setNumSequencia($numSequencia);
            $objDTO->setStrSinNovaJanela('N');
            $objDTO->setStrSinAtivo('S');

            $objDTO = $objBD->cadastrar($objDTO);
        }

        if (!empty($numIdRecurso)) {

            $this->arrMenu[] = array($objDTO->getNumIdItemMenu(), $numIdMenu, $numIdRecurso);
        }

        return $objDTO->getNumIdItemMenu();
    }

    public function addRecursosToPerfil($numIdPerfil, $numIdSistema) {

        if (!empty($this->arrRecurso)) {

            $objDTO = new RelPerfilRecursoDTO();
            $objBD = new RelPerfilRecursoBD($this->inicializarObjInfraIBanco());

            foreach ($this->arrRecurso as $numIdRecurso) {

                $objDTO->setNumIdSistema($numIdSistema);
                $objDTO->setNumIdPerfil($numIdPerfil);
                $objDTO->setNumIdRecurso($numIdRecurso);

                if ($objBD->contar($objDTO) == 0) {
                    $objBD->cadastrar($objDTO);
                }
            }
        }
    }

    public function addMenusToPerfil($numIdPerfil, $numIdSistema) {

        if (!empty($this->arrMenu)) {

            $objDTO = new RelPerfilItemMenuDTO();
            $objBD = new RelPerfilItemMenuBD($this->inicializarObjInfraIBanco());

            foreach ($this->arrMenu as $array) {

                list($numIdItemMenu, $numIdMenu, $numIdRecurso) = $array;

                $objDTO->setNumIdPerfil($numIdPerfil);
                $objDTO->setNumIdSistema($numIdSistema);
                $objDTO->setNumIdRecurso($numIdRecurso);
                $objDTO->setNumIdMenu($numIdMenu);
                $objDTO->setNumIdItemMenu($numIdItemMenu);

                if ($objBD->contar($objDTO) == 0) {
                    $objBD->cadastrar($objDTO);
                }
            }
        }
    }

    public function atribuirPerfil($numIdSistema) {

        $objDTO = new PerfilDTO();
        $objBD = new PerfilBD($this->inicializarObjInfraIBanco());
        $objRN = $this;

        // Vincula a um perfil os recursos e menus adicionados nos métodos criarMenu e criarReturso
        $fnCadastrar = function($strNome, $numIdSistema) use($objDTO, $objBD, $objRN) {

            $objDTO->unSetTodos();
            $objDTO->setNumIdSistema($numIdSistema);
            $objDTO->setStrNome($strNome, InfraDTO::$OPER_LIKE);
            $objDTO->setNumMaxRegistrosRetorno(1);
            $objDTO->retNumIdPerfil();

            $objPerfilDTO = $objBD->consultar($objDTO);

            if (!empty($objPerfilDTO)) {
                $objRN->addRecursosToPerfil($objPerfilDTO->getNumIdPerfil(), $numIdSistema);
                $objRN->addMenusToPerfil($objPerfilDTO->getNumIdPerfil(), $numIdSistema);
            }
        };

        $fnCadastrar('ADMINISTRADOR', $numIdSistema);
        $fnCadastrar('BASICO', $numIdSistema);
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.0
     */
    protected function instalarV001() {
         $numIdSistema = $this->getNumIdSistema('SEI');
        $numIdMenu = $this->getNumIdMenu('Principal', $numIdSistema);

        //----------------------------------------------------------------------
        // Expedir procedimento
        //----------------------------------------------------------------------
        $this->criarRecurso('pen_procedimento_expedir', 'Expedir Procedimento', $numIdSistema);
        $this->criarRecurso('apensados_selecionar_expedir_procedimento', 'Processos Apensados', $numIdSistema);
        $numIdRecurso = $this->criarRecurso('pen_procedimento_expedido_listar', 'Processos Trâmitados Externamente', $numIdSistema);
        $this->criarMenu('Processos Trâmitados Externamente', 55, null, $numIdMenu, $numIdRecurso, $numIdSistema);
        //----------------------------------------------------------------------
        // Mapeamento de documentos enviados 
        //----------------------------------------------------------------------
        $this->criarRecurso('pen_map_tipo_doc_enviado_visualizar', 'Visualização de mapeamento de documentos enviados', $numIdSistema);

        // Acha o menu existente de Tipos de Documento
        $objItemMenuDTO = new ItemMenuDTO();
        $objItemMenuDTO->setNumIdSistema($numIdSistema);
        $objItemMenuDTO->setNumIdMenu($numIdMenu);
        $objItemMenuDTO->setStrRotulo('Tipos de Documento');
        $objItemMenuDTO->setNumMaxRegistrosRetorno(1);
        $objItemMenuDTO->retNumIdItemMenu();

        $objItemMenuBD = new ItemMenuBD($this->inicializarObjInfraIBanco());
        $objItemMenuDTO = $objItemMenuBD->consultar($objItemMenuDTO);

        if (empty($objItemMenuDTO)) {
            throw new InfraException('Menu "Tipo de Documentos" não foi localizado');
        }

        $numIdItemMenuPai = $objItemMenuDTO->getNumIdItemMenu();

        // Gera o submenu Mapeamento
        $_numIdItemMenuPai = $this->criarMenu('Mapeamento', 50, $numIdItemMenuPai, $numIdMenu, null, $numIdSistema);

        // Gera o submenu Mapeamento > Envio
        $numIdItemMenuPai = $this->criarMenu('Envio', 10, $_numIdItemMenuPai, $numIdMenu, null, $numIdSistema);

        // Gera o submenu Mapeamento > Envio > Cadastrar
        $numIdRecurso = $this->criarRecurso('pen_map_tipo_doc_enviado_cadastrar', 'Cadastro de mapeamento de documentos enviados', $numIdSistema);
        $this->criarMenu('Cadastrar', 10, $numIdItemMenuPai, $numIdMenu, $numIdRecurso, $numIdSistema);

        // Gera o submenu Mapeamento > Envio > Listar
        $numIdRecurso = $this->criarRecurso('pen_map_tipo_doc_enviado_listar', 'Listagem de mapeamento de documentos enviados', $numIdSistema);
        $this->criarMenu('Listar', 20, $numIdItemMenuPai, $numIdMenu, $numIdRecurso, $numIdSistema);

        // Gera o submenu Mapeamento > Recebimento
        $numIdItemMenuPai = $this->criarMenu('Recebimento', 20, $_numIdItemMenuPai, $numIdMenu, null, $numIdSistema);

        // Gera o submenu Mapeamento > Recebimento > Cadastrar
        $numIdRecurso = $this->criarRecurso('pen_map_tipo_doc_recebido_cadastrar', 'Cadastro de mapeamento de documentos recebidos', $numIdSistema);
        $this->criarMenu('Cadastrar', 10, $numIdItemMenuPai, $numIdMenu, $numIdRecurso, $numIdSistema);

        // Gera o submenu Mapeamento > Recebimento > Listar
        $numIdRecurso = $this->criarRecurso('pen_map_tipo_doc_recebido_listar', 'Listagem de mapeamento de documentos recebidos', $numIdSistema);
        $this->criarMenu('Listar', 20, $numIdItemMenuPai, $numIdMenu, $numIdRecurso, $numIdSistema);

        //Atribui as permissões aos recursos e menus
        $this->atribuirPerfil($numIdSistema); 
    }

    protected function instalarV003R003S003IW001() {
            
        $objBD = new ItemMenuBD($this->inicializarObjInfraIBanco());

        //----------------------------------------------------------------------
        // Achar o root

        $numIdSistema = $this->getNumIdSistema('SEI');
        $numIdMenu = $this->getNumIdMenu('Principal', $numIdSistema);

        $objDTO = new ItemMenuDTO();
        $objDTO->setNumIdSistema($numIdSistema);
        $objDTO->setNumIdMenu($numIdMenu);
        $objDTO->setStrRotulo('Administração');
        $objDTO->setNumMaxRegistrosRetorno(1);
        $objDTO->retNumIdItemMenu();

        $objDTO = $objBD->consultar($objDTO);

        if (empty($objDTO)) {
            throw new InfraException('Menu "Administração" não foi localizado');
        }

        $numIdItemMenuRoot = $objDTO->getNumIdItemMenu();
        //----------------------------------------------------------------------
        // Acha o nodo do mapeamento

        $objItemMenuDTO = new ItemMenuDTO();
        $objItemMenuDTO->setNumIdSistema($numIdSistema);
        $objItemMenuDTO->setNumIdMenu($numIdMenu);
        $objItemMenuDTO->setStrRotulo('Mapeamento');
        $objItemMenuDTO->setNumSequencia(50);
        $objItemMenuDTO->setNumMaxRegistrosRetorno(1);
        $objItemMenuDTO->retTodos();

        $objItemMenuDTO = $objBD->consultar($objItemMenuDTO);
        if (!empty($objItemMenuDTO)) {

            $numIdItemMenuMapeamento = $objItemMenuDTO->getNumIdItemMenu();

            $objDTO = new ItemMenuDTO();
            $objDTO->setNumIdSistema($numIdSistema);
            $objDTO->setNumIdMenu($numIdMenu);
            $objDTO->setNumIdItemMenuPai($numIdItemMenuMapeamento);
            $objDTO->retTodos();

            $arrObjDTO = $objBD->listar($objDTO);

            if (!empty($arrObjDTO)) {

                $numIdItemMenuPai = $this->criarMenu('Processo Eletrônico Nacional', 0, $numIdItemMenuRoot, $numIdMenu, null, $numIdSistema);
                $numIdItemMenuPai = $this->criarMenu('Mapeamento de Tipos de Documento', 10, $numIdItemMenuPai, $numIdMenu, null, $numIdSistema);

                foreach ($arrObjDTO as $objDTO) {

                    $objDTO->setNumIdItemMenuPai($numIdItemMenuPai);

                    $objBD->alterar($objDTO);
                }

                $objBD->excluir($objItemMenuDTO);
            }
        } 
    }
    
    protected function instalarV100R001S001IW001(){
        
    }

}

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

try {

    $objPenConsoleRN = new PenConsoleRN();
    $arrArgs = $objPenConsoleRN->getTokens();

    $objAtualizarRN = new PenAtualizarSipRN($arrArgs);
    $objAtualizarRN->atualizarVersao();

    exit(0);
} catch (Exception $e) {

    print InfraException::inspecionar($e);

    try {
        LogSEI::getInstance()->gravar(InfraException::inspecionar($e));
    } catch (Exception $e) {
        
    }

    exit(1);
}

print PHP_EOL;
