<?php
require_once dirname(__FILE__) . '/../../../../sip/Sip.php';

/**
 * Atualizador do sistema SIP para instalar/atualizar o módulo PEN
 * 
 * @author Join Tecnologia
 */
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
        if(empty($this->objBanco)) {
            
            $this->objBanco = BancoSip::getInstance();
        }
        return $this->objBanco;
    }
    
    /**
     * Retorna o ID do sistema
     * 
     * @return int
     */
    protected function getNumIdSistema($strSigla = 'SIP'){
        
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
    protected function getNumIdMenu($strMenu = 'Principal', $numIdSistema = 0){
        
        $objDTO = new MenuDTO();
        $objDTO->setNumIdSistema($numIdSistema);
        $objDTO->setStrNome($strMenu);
        $objDTO->setNumMaxRegistrosRetorno(1);
        $objDTO->retNumIdMenu();
        
        $objRN = new MenuRN();
        $objDTO = $objRN->consultar($objDTO);

        if(empty($objDTO)){
            throw new InfraException('Menu '.$strMenu.' não encontrado.');
        }

        return $objDTO->getNumIdMenu();  
    }
    
    /**
     * 
     * @return int Código do Recurso gerado
     */
    protected function criarRecurso($strNome = '', $strDescricao = null, $numIdSistema = 0){
        
        $objDTO = new RecursoDTO();
	$objDTO->setNumIdSistema($numIdSistema);
	$objDTO->setStrNome($strNome);
        $objDTO->setNumMaxRegistrosRetorno(1);
        $objDTO->retNumIdRecurso();

        $objBD = new RecursoBD($this->getObjInfraIBanco());
        $objDTO = $objBD->consultar($objDTO);

        if(empty($objDTO)){

            $objDTO = new RecursoDTO();
            $objDTO->setNumIdRecurso(null);
            $objDTO->setStrDescricao($strDescricao);
            $objDTO->setNumIdSistema($numIdSistema);
            $objDTO->setStrNome($strNome);
            $objDTO->setStrCaminho('controlador.php?acao='.$strNome);
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
    protected function criarMenu($strRotulo = '', $numSequencia = 10, $numIdItemMenuPai = null,  $numIdMenu = null, $numIdRecurso = null, $numIdSistema = 0){
        
        $objDTO = new ItemMenuDTO();
        $objDTO->setNumIdItemMenuPai($numIdItemMenuPai);
        $objDTO->setNumIdSistema($numIdSistema);
        $objDTO->setStrRotulo($strRotulo);
        $objDTO->setNumIdRecurso($numIdRecurso);        
        $objDTO->setNumMaxRegistrosRetorno(1);
        $objDTO->retNumIdItemMenu();
        
        $objBD = new ItemMenuBD($this->inicializarObjInfraIBanco());
        $objDTO = $objBD->consultar($objDTO);
        
        if(empty($objDTO)){
        
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
        
        if(!empty($numIdRecurso)) {
        
            $this->arrMenu[] = array($objDTO->getNumIdItemMenu(), $numIdMenu, $numIdRecurso);
        }
        
        return $objDTO->getNumIdItemMenu();
    }
    
    public function addRecursosToPerfil($numIdPerfil, $numIdSistema){

        if(!empty($this->arrRecurso)) {
        
            $objDTO = new RelPerfilRecursoDTO();
            $objBD = new RelPerfilRecursoBD($this->inicializarObjInfraIBanco());

            foreach($this->arrRecurso as $numIdRecurso) {

                $objDTO->setNumIdSistema($numIdSistema);
                $objDTO->setNumIdPerfil($numIdPerfil);
                $objDTO->setNumIdRecurso($numIdRecurso);

                if($objBD->contar($objDTO) == 0) {
                    $objBD->cadastrar($objDTO);
                }   
            }
        }
    }
    
    public function addMenusToPerfil($numIdPerfil, $numIdSistema){

        if(!empty($this->arrMenu)) {
            
            $objDTO = new RelPerfilItemMenuDTO();
            $objBD = new RelPerfilItemMenuBD($this->inicializarObjInfraIBanco());
            
            foreach($this->arrMenu as $array) {
            
                list($numIdItemMenu, $numIdMenu, $numIdRecurso) = $array;

                $objDTO->setNumIdPerfil($numIdPerfil);
                $objDTO->setNumIdSistema($numIdSistema);        
                $objDTO->setNumIdRecurso($numIdRecurso);
                $objDTO->setNumIdMenu($numIdMenu);
                $objDTO->setNumIdItemMenu($numIdItemMenu);

                if($objBD->contar($objDTO) == 0) {
                    $objBD->cadastrar($objDTO);
                }
            }
        }
    }
    
    public function atribuirPerfil($numIdSistema){
         
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

            if(!empty($objPerfilDTO)) {
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
    protected function instalarV001(){
        
        $numIdSistema = $this->getNumIdSistema('SEI');
        $numIdMenu = $this->getNumIdMenu('Principal', $numIdSistema);
        
        //----------------------------------------------------------------------
        // Expedir procedimento
        //----------------------------------------------------------------------
        $this->criarRecurso('pen_procedimento_expedir', 'Expedir Procedimento', $numIdSistema);
        $this->criarRecurso('apensados_selecionar_expedir_procedimento', 'Processos Apensados', $numIdSistema);        
        $numIdRecurso = $this->criarRecurso('pen_procedimento_expedido_listar', 'Processos Expedidos', $numIdSistema);
        $this->criarMenu('Processos Expedidos', 55, null, $numIdMenu, $numIdRecurso, $numIdSistema);
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
        
        if(empty($objItemMenuDTO)) {
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
    
    protected function instalarV002(){
        
    }
    
    protected function instalarV003R003S003IW001(){
        
        
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
        
        if(empty($objDTO)) {
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
        if(!empty($objItemMenuDTO)) {
                   
            $numIdItemMenuMapeamento = $objItemMenuDTO->getNumIdItemMenu();

            $objDTO = new ItemMenuDTO();
            $objDTO->setNumIdSistema($numIdSistema);
            $objDTO->setNumIdMenu($numIdMenu);
            $objDTO->setNumIdItemMenuPai($numIdItemMenuMapeamento);
            $objDTO->retTodos();

            $arrObjDTO = $objBD->listar($objDTO);

            if(!empty($arrObjDTO)) {

                $numIdItemMenuPai = $this->criarMenu('Processo Eletrônico Nacional', 0, $numIdItemMenuRoot, $numIdMenu, null, $numIdSistema);
                $numIdItemMenuPai = $this->criarMenu('Mapeamento de Tipos de Documento', 10, $numIdItemMenuPai, $numIdMenu, null, $numIdSistema);

                foreach($arrObjDTO as $objDTO) {

                    $objDTO->setNumIdItemMenuPai($numIdItemMenuPai);

                    $objBD->alterar($objDTO);  
                }

                $objBD->excluir($objItemMenuDTO);
            }
        }
    }
}
