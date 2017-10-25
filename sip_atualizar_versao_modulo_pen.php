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

    protected $sip_versao;

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
    
    protected $objInfraBanco ;

    protected function inicializarObjInfraIBanco() {
        
        if (empty($this->objInfraBanco)) {
            $this->objInfraBanco = BancoSip::getInstance();
            $this->objInfraBanco->abrirConexao();
        }
        
        return $this->objInfraBanco;
    }

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
     * Construtor
     * 
     * @param array $arrArgs Argumentos enviados pelo script
     */
    public function __construct() {
        
        parent::__construct();
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '-1');
        @ini_set('zlib.output_compression', '0');
        @ini_set('implicit_flush', '1');
        ob_implicit_flush();
        
        $this->inicializarObjInfraIBanco();
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
    protected $sip_versao = 'PEN_VERSAO_MODULO_SIP';
    private $arrRecurso = array();
    private $arrMenu = array();
    
    public function atualizarVersao() {
        try {
            $this->inicializar('INICIANDO ATUALIZACAO DO MODULO PEN NO SIP VERSAO 1.0.0');

            //testando versao do framework
//            $numVersaoInfraRequerida = '1.415';
//            if (VERSAO_INFRA >= $numVersaoInfraRequerida) {
//                $this->finalizar('VERSAO DO FRAMEWORK PHP INCOMPATIVEL (VERSAO ATUAL ' . VERSAO_INFRA . ', VERSAO REQUERIDA ' . $numVersaoInfraRequerida . ')', true);
//            }

            //testando se esta usando BDs suportados
            if (!(BancoSip::getInstance() instanceof InfraMySql) &&
                    !(BancoSip::getInstance() instanceof InfraSqlServer) &&
                    !(BancoSip::getInstance() instanceof InfraOracle)) {

                $this->finalizar('BANCO DE DADOS NAO SUPORTADO: ' . get_parent_class(BancoSip::getInstance()), true);
            }
            
           
            //testando permissoes de criações de tabelas
            $objInfraMetaBD = new InfraMetaBD($this->objInfraBanco);
            
            if (count($objInfraMetaBD->obterTabelas('pen_sip_teste')) == 0) {
                BancoSip::getInstance()->executarSql('CREATE TABLE pen_sip_teste (id ' . $objInfraMetaBD->tipoNumero() . ' null)');
            }
            BancoSip::getInstance()->executarSql('DROP TABLE pen_sip_teste');


            $objInfraParametro = new InfraParametro($this->objInfraBanco);

            //$strVersaoAtual = $objInfraParametro->getValor('SEI_VERSAO', false);
            $strVersaoModuloPen = $objInfraParametro->getValor($this->sip_versao, false);

            //VERIFICANDO QUAL VERSAO DEVE SER INSTALADA NESTA EXECUCAO
            if (InfraString::isBolVazia($strVersaoModuloPen)) {
                //nao tem nenhuma versao ainda, instalar todas
                $this->instalarV100();
                $this->instalarV101();
            } else if ($strVersaoModuloPen == '1.0.0') {
                $this->instalarV101();
            }


            InfraDebug::getInstance()->setBolDebugInfra(true);
        } catch (Exception $e) {

            InfraDebug::getInstance()->setBolLigado(false);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(false);
            throw new InfraException('Erro atualizando VERSAO.', $e);
        }
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
     * Adiciona uma mensagem ao output para o usuário
     * 
     * @return null
     */
    protected function logar($strMsg) {
        $this->objDebug->gravar($strMsg);
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

        $objBD = new ItemMenuBD($this->objInfraBanco);
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
            $objBD = new RelPerfilRecursoBD($this->objInfraBanco);

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
            $objBD = new RelPerfilItemMenuBD($this->objInfraBanco);

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
        $objBD = new PerfilBD($this->objInfraBanco);
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

        //$fnCadastrar('ADMINISTRADOR', $numIdSistema);
        //$fnCadastrar('BASICO', $numIdSistema);
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.0
     */
    protected function instalarV100() {
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

        $objItemMenuBD = new ItemMenuBD($this->objInfraBanco);
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
        
        // ---------- antigo método (instalarV003R003S003IW001) ---------- //
        $objBD = new ItemMenuBD($this->objInfraBanco);

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
        
        $objInfraParametroDTO = new InfraParametroDTO();
        $objInfraParametroDTO->setStrNome($this->sip_versao);
        $objInfraParametroDTO->setStrValor('1.0.0');
        
        $objInfraParametroBD = new InfraParametroBD($this->inicializarObjInfraIBanco());
        $objInfraParametroBD->cadastrar($objInfraParametroDTO);
    }
    
    /**
     * Instala/Atualiza os módulo PEN para versão 1.0.1
     */
    protected function instalarV101() {
        // ---------- antigo método (instalarV006R004S001US039) ---------- //
        $objItemMenuBD = new ItemMenuBD($this->inicializarObjInfraIBanco());

        $numIdSistema = $this->getNumIdSistema('SEI');
        $numIdMenu = $this->getNumIdMenu('Principal', $numIdSistema);

        $objItemMenuDTO = new ItemMenuDTO();
        $objItemMenuDTO->setNumIdSistema($numIdSistema);
        $objItemMenuDTO->setNumIdMenu($numIdMenu);
        $objItemMenuDTO->setStrRotulo('Processo Eletrônico Nacional');       
        $objItemMenuDTO->setNumMaxRegistrosRetorno(1);
        $objItemMenuDTO->retNumIdItemMenu();

        $objItemMenuDTO = $objItemMenuBD->consultar($objItemMenuDTO);

        if(empty($objItemMenuDTO)) {
            throw new InfraException('Menu "Processo Eletrônico Nacional" no foi localizado');
        }

        // Administrao > Mapeamento de Hipteses Legais de Envio
        $numIdItemMenu = $this->criarMenu('Mapeamento de Hipóteses Legais', 20, $objItemMenuDTO->getNumIdItemMenu(), $numIdMenu, null, $numIdSistema);       

        // Administrao > Mapeamento de Hipteses Legais de Envio > Envio
        $numIdItemMenu = $this->criarMenu('Envio', 10, $numIdItemMenu, $numIdMenu, null, $numIdSistema);

        // Administrao > Mapeamento de Hipteses Legais de Envio > Envio > Cadastrar
        $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_enviado_alterar', 'Alterar de mapeamento de Hipóteses Legais de Envio', $numIdSistema);
        $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_enviado_cadastrar', 'Cadastro de mapeamento de Hipóteses Legais de Envio', $numIdSistema);
        $this->criarMenu('Cadastrar', 10, $numIdItemMenu, $numIdMenu, $numIdRecurso, $numIdSistema);

        // Administrao > Mapeamento de Hipteses Legais de Envio > Envio > Listar
        $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_enviado_excluir', 'Excluir mapeamento de Hipóteses Legais de Envio', $numIdSistema);
        $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_enviado_listar', 'Listagem de mapeamento de Hipóteses Legais de Envio', $numIdSistema);
        $this->criarMenu('Listar', 20, $numIdItemMenu, $numIdMenu, $numIdRecurso, $numIdSistema);  

        //Atribui as permisses aos recursos e menus
        $this->atribuirPerfil($numIdSistema);
    

        // ---------- antigo método (instalarV006R004S001US040) ---------- //
        $objBD = new ItemMenuBD($this->inicializarObjInfraIBanco());

        //----------------------------------------------------------------------
        // Achar o root

        $numIdSistema = $this->getNumIdSistema('SEI');
        $numIdMenu = $this->getNumIdMenu('Principal', $numIdSistema);

        $objDTO = new ItemMenuDTO();
        $objDTO->setNumIdSistema($numIdSistema);
        $objDTO->setNumIdMenu($numIdMenu);
        $objDTO->setStrRotulo('Mapeamento de Hipóteses Legais');       
        $objDTO->setNumMaxRegistrosRetorno(1);
        $objDTO->retNumIdItemMenu();

        $objDTO = $objBD->consultar($objDTO);

        if(empty($objDTO)) {
            throw new InfraException('Menu "Processo Eletrônico Nacional" no foi localizado');
        }

        // Administrao > Mapeamento de Hipteses Legais de Envio > Envio
        $numIdItemMenu = $this->criarMenu('Recebimento', 20, $objDTO->getNumIdItemMenu(), $numIdMenu, null, $numIdSistema);

        // Administrao > Mapeamento de Hipteses Legais de Envio > Envio > Cadastrar
        $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_recebido_alterar', 'Alterar de mapeamento de Hipóteses Legais de Recebimento', $numIdSistema);
        $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_recebido_cadastrar', 'Cadastro de mapeamento de Hipóteses Legais de Recebimento', $numIdSistema);
        $this->criarMenu('Cadastrar', 10, $numIdItemMenu, $numIdMenu, $numIdRecurso, $numIdSistema);

        // Administrao > Mapeamento de Hipteses Legais de Envio > Envio > Listar
        $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_recebido_excluir', 'Excluir mapeamento de Hipóteses Legais de Recebimento', $numIdSistema);
        $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_recebido_listar', 'Listagem de mapeamento de Hipóteses Legais de Recebimento', $numIdSistema);
        $this->criarMenu('Listar', 20, $numIdItemMenu, $numIdMenu, $numIdRecurso, $numIdSistema);  

        //Atribui as permisses aos recursos e menus
        $this->atribuirPerfil($numIdSistema);
        
        // ---------- antigo método (instalarV006R004S001US043) ---------- //
        $objBD = new ItemMenuBD($this->inicializarObjInfraIBanco());

        $numIdSistema = $this->getNumIdSistema('SEI');
        $numIdMenu = $this->getNumIdMenu('Principal', $numIdSistema);

        $objDTO = new ItemMenuDTO();
        $objDTO->setNumIdSistema($numIdSistema);
        $objDTO->setNumIdMenu($numIdMenu);
        $objDTO->setStrRotulo('Mapeamento de Hipóteses Legais');       
        $objDTO->setNumMaxRegistrosRetorno(1);
        $objDTO->retNumIdItemMenu();

        $objDTO = $objBD->consultar($objDTO);

        if(empty($objDTO)) {
            throw new InfraException('Menu "Processo Eletrônico Nacional" no foi localizado');
        }

        $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_padrao_cadastrar', 'Acesso ao formulário de cadastro de mapeamento de Hipóteses Legais Padrão', $numIdSistema);

        $this->criarMenu('Indicar Hiptese de Restrio Padro', 30, $objDTO->getNumIdItemMenu(), $numIdMenu, $numIdRecurso, $numIdSistema);
        $this->criarRecurso('pen_map_hipotese_legal_padrao', 'Método Cadastrar Padrão da RN de mapeamento de Hipóteses Legais', $numIdSistema);
        $this->atribuirPerfil($numIdSistema);
        
        /* altera o parâmetro da versão de banco */
        $objInfraParametroDTO = new InfraParametroDTO();
        $objInfraParametroDTO->setStrNome($this->sip_versao);
        $objInfraParametroDTO->retTodos();
        
        $objInfraParametroBD = new InfraParametroBD($this->inicializarObjInfraIBanco());
        $objInfraParametroDTO = $objInfraParametroBD->consultar($objInfraParametroDTO);
        $objInfraParametroDTO->setStrValor('1.0.1');
        $objInfraParametroBD->alterar($objInfraParametroDTO);
        
    }
}

try {
 
    $objAtualizarRN = new PenAtualizarSipRN($arrArgs);
    $objAtualizarRN->atualizarVersao();

    exit(0);
} catch (Exception $e) {

    print InfraException::inspecionar($e);

    try {
//        LogSEI::getInstance()->gravar(InfraException::inspecionar($e));
    } catch (Exception $e) {
        
    }

    exit(1);
}

print PHP_EOL;
