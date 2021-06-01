<?php

$dirSipWeb = !defined("DIR_SIP_WEB") ? getenv("DIR_SIP_WEB") ?: __DIR__."/../../web" : DIR_SIP_WEB;
require_once $dirSipWeb . '/Sip.php';

class PenAtualizarSipRN extends InfraRN {

    const PARAMETRO_VERSAO_MODULO_ANTIGO = 'PEN_VERSAO_MODULO_SIP';
    const PARAMETRO_VERSAO_MODULO = 'VERSAO_MODULO_PEN';

    protected $versaoMinRequirida = '1.30.0';
    private $arrRecurso = array();
    private $arrMenu = array();

    public function __construct(){
        parent::__construct();
    }

    protected function inicializarObjInfraIBanco(){
        return BancoSip::getInstance();
    }

    /**
     * Inicia o script criando um contator interno do tempo de execução
     *
     * @return null
     */
    protected function inicializar($strTitulo) {

        session_start();
        SessaoSip::getInstance(false);

        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '-1');
        @ini_set('zlib.output_compression', '0');
        @ini_set('implicit_flush', '1');
        ob_implicit_flush();

        $this->objDebug = InfraDebug::getInstance();
        $this->objDebug->setBolLigado(true);
        $this->objDebug->setBolDebugInfra(true);
        $this->objDebug->setBolEcho(true);
        $this->objDebug->limpar();

        $this->numSeg = InfraUtil::verificarTempoProcessamento();
        $this->logar($strTitulo);
    }

    protected function atualizarVersaoConectado() {
        try {
            $this->inicializar('INICIANDO ATUALIZACAO DO MODULO PEN NO SIP VERSAO 1.0.0');

            //testando se esta usando BDs suportados
            if (!(BancoSip::getInstance() instanceof InfraMySql) &&
                    !(BancoSip::getInstance() instanceof InfraSqlServer) &&
                    !(BancoSip::getInstance() instanceof InfraOracle)) {

                $this->finalizar('BANCO DE DADOS NAO SUPORTADO: ' . get_parent_class(BancoSip::getInstance()), true);
            }

            //testando permissoes de criações de tabelas
            $objInfraMetaBD = new InfraMetaBD(BancoSip::getInstance());

            if (count($objInfraMetaBD->obterTabelas('pen_sip_teste')) == 0) {
                BancoSip::getInstance()->executarSql('CREATE TABLE pen_sip_teste (id ' . $objInfraMetaBD->tipoNumero() . ' null)');
            }
            BancoSip::getInstance()->executarSql('DROP TABLE pen_sip_teste');


            $objInfraParametro = new InfraParametro(BancoSip::getInstance());

            // Aplicação de scripts de atualização de forma incremental
            // Ausência de [break;] proposital para realizar a atualização incremental de versões
            $strVersaoModuloPen = $objInfraParametro->getValor(self::PARAMETRO_VERSAO_MODULO, false) ?: $objInfraParametro->getValor(self::PARAMETRO_VERSAO_MODULO_ANTIGO, false);
            switch ($strVersaoModuloPen) {
                //case '' - Nenhuma versão instalada
                case '':      $this->instalarV100();
                case '1.0.0': $this->instalarV101();
                case '1.0.1': $this->instalarV102();
                case '1.0.2': $this->instalarV103();
                case '1.0.3': $this->instalarV104();
                case '1.0.4': $this->instalarV111();
                case '1.1.1': //Não houve atualização no banco de dados
                case '1.1.2': //Não houve atualização no banco de dados
                case '1.1.3': //Não houve atualização no banco de dados
                case '1.1.4': //Não houve atualização no banco de dados
                case '1.1.5': //Não houve atualização no banco de dados
                case '1.1.6': //Não houve atualização no banco de dados
                case '1.1.7': //Não houve atualização no banco de dados
                case '1.1.8': $this->instalarV119();
                case '1.1.9': $this->instalarV1110();
                case '1.1.10': $this->instalarV1111();
                case '1.1.11': $this->instalarV1112();
                case '1.1.12': $this->instalarV1113();
                case '1.1.13': $this->instalarV1114();
                case '1.1.14': $this->instalarV1115();
                case '1.1.15': $this->instalarV1116();
                case '1.1.16': $this->instalarV1117();
                case '1.1.17': $this->instalarV1200();
                case '1.2.0': $this->instalarV1201();
                case '1.2.1': $this->instalarV1202();
                case '1.2.2': $this->instalarV1203();
                case '1.2.3': $this->instalarV1204();
                case '1.2.4': $this->instalarV1205();
                case '1.2.5': $this->instalarV1206();
                case '1.2.6': $this->instalarV1300();
                case '1.3.0': $this->instalarV1400();
                case '1.4.0': $this->instalarV1401();
                case '1.4.1': $this->instalarV1402();
                case '1.4.2': $this->instalarV1403();
                case '1.4.3': $this->instalarV1500();
                case '1.5.0': $this->instalarV1501();
                case '1.5.1': $this->instalarV1502();
                case '1.5.2': $this->instalarV1503();
                case '1.5.3'; // Faixa de possíveis versões da release 1.5.x de retrocompatibilidade
                case '1.5.4'; // Faixa de possíveis versões da release 1.5.x de retrocompatibilidade
                case '1.5.5'; // Faixa de possíveis versões da release 1.5.x de retrocompatibilidade
                case '1.5.6'; // Faixa de possíveis versões da release 1.5.x de retrocompatibilidade
                case '1.5.7': $this->instalarV2000_beta1();
                case '2.0.0-beta1': $this->instalarV2000_beta2();
                case '2.0.0-beta2': $this->instalarV2000_beta3();
                case '2.0.0-beta3': $this->instalarV2000_beta4();
                case '2.0.0-beta4': $this->instalarV2000_beta5();
                case '2.0.0-beta5': $this->instalarV2000();
                case '2.0.0': $this->instalarV2001();
                case '2.0.1': $this->instalarV2100();
                case '2.1.0': $this->instalarV2101();
                case '2.1.1': $this->instalarV2102();
                case '2.1.2': $this->instalarV2103();
                case '2.1.3': $this->instalarV2104();
                case '2.1.4': $this->instalarV2105();
                case '2.1.5': $this->instalarV2106();
                case '2.1.6': $this->instalarV2107();
                case '2.1.7': $this->instalarV3000();
                    break;

                default:
                    $this->finalizar('VERSAO DO MÓDULO JÁ CONSTA COMO ATUALIZADA');
                    break;
            }

            $this->finalizar('FIM');
            InfraDebug::getInstance()->setBolDebugInfra(true);
        } catch (Exception $e) {

            InfraDebug::getInstance()->setBolLigado(false);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(false);
            throw new InfraException('Erro atualizando VERSAO.', $e);
        }
    }


    /**
     * Finaliza o script informando o tempo de execução.
     *
     * @return null
     */
    protected function finalizar($strMsg=null, $bolErro=false){
        if (!$bolErro) {
          $this->numSeg = InfraUtil::verificarTempoProcessamento($this->numSeg);
          $this->logar('TEMPO TOTAL DE EXECUCAO: ' . $this->numSeg . ' s');
        }else{
          $strMsg = 'ERRO: '.$strMsg;
        }

        if ($strMsg!=null){
          $this->logar($strMsg);
        }

        InfraDebug::getInstance()->setBolLigado(false);
        InfraDebug::getInstance()->setBolDebugInfra(false);
        InfraDebug::getInstance()->setBolEcho(false);
        $this->numSeg = 0;
        die;
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
    protected function getNumIdSistema($strSigla='SIP') {

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
     * Cria novo recurso no SIP
     * @return int Código do Recurso gerado
     */
    protected function criarRecurso($strNome, $strDescricao, $numIdSistema) {

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

    protected function renomearRecurso($numIdSistema, $strNomeAtual, $strNomeNovo){

        $objRecursoDTO = new RecursoDTO();
        $objRecursoDTO->setBolExclusaoLogica(false);
        $objRecursoDTO->retNumIdRecurso();
        $objRecursoDTO->retStrCaminho();
        $objRecursoDTO->setNumIdSistema($numIdSistema);
        $objRecursoDTO->setStrNome($strNomeAtual);

        $objRecursoRN = new RecursoRN();
        $objRecursoDTO = $objRecursoRN->consultar($objRecursoDTO);

        if ($objRecursoDTO!=null){
            $objRecursoDTO->setStrNome($strNomeNovo);
            $objRecursoDTO->setStrCaminho(str_replace($strNomeAtual,$strNomeNovo,$objRecursoDTO->getStrCaminho()));
            $objRecursoRN->alterar($objRecursoDTO);
        }
    }

    protected function consultarRecurso($numIdSistema, $strNomeRecurso)
    {
        $objRecursoDTO = new RecursoDTO();
        $objRecursoDTO->setBolExclusaoLogica(false);
        $objRecursoDTO->setNumIdSistema($numIdSistema);
        $objRecursoDTO->setStrNome($strNomeRecurso);
        $objRecursoDTO->retNumIdRecurso();

        $objRecursoRN = new RecursoRN();
        $objRecursoDTO = $objRecursoRN->consultar($objRecursoDTO);

        if ($objRecursoDTO == null){
            throw new InfraException("Recurso com nome {$strNomeRecurso} não pode ser localizado.");
        }

        return $objRecursoDTO->getNumIdRecurso();
    }

    /**
     * Cria um novo menu lateral para o sistema SEI
     *
     * @return int
     */
    protected function criarMenu($strRotulo, $numSequencia, $numIdItemMenuPai, $numIdMenu, $numIdRecurso, $numIdSistema)
    {
        $objDTO = new ItemMenuDTO();
        $objDTO->setNumIdItemMenuPai($numIdItemMenuPai);
        $objDTO->setNumIdSistema($numIdSistema);
        $objDTO->setStrRotulo($strRotulo);
        $objDTO->setNumIdRecurso($numIdRecurso);
        $objDTO->setNumMaxRegistrosRetorno(1);
        $objDTO->retNumIdItemMenu();

        $objBD = new ItemMenuBD(BancoSip::getInstance());
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


    //TODO: Necessário refatorar método abaixo devido a baixa qualidade da codificação
    public function addRecursosToPerfil($numIdPerfil, $numIdSistema) {

        if (!empty($this->arrRecurso)) {

            $objDTO = new RelPerfilRecursoDTO();
            $objBD = new RelPerfilRecursoBD(BancoSip::getInstance());

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

    //TODO: Necessário refatorar método abaixo devido a baixa qualidade da codificação
    public function addMenusToPerfil($numIdPerfil, $numIdSistema) {

        if (!empty($this->arrMenu)) {

            $objDTO = new RelPerfilItemMenuDTO();
            $objBD = new RelPerfilItemMenuBD(BancoSip::getInstance());

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
        $objBD = new PerfilBD(BancoSip::getInstance());
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
    }


    /**
     * Atualiza o número de versão do módulo nas tabelas de parâmetro do sistema
     *
     * @param string $parStrNumeroVersao
     * @return void
     */
    private function atualizarNumeroVersao($parStrNumeroVersao)
    {
        $objInfraParametroDTO = new InfraParametroDTO();
        $objInfraParametroDTO->setStrNome(array(self::PARAMETRO_VERSAO_MODULO, self::PARAMETRO_VERSAO_MODULO_ANTIGO), InfraDTO::$OPER_IN);
        $objInfraParametroDTO->retTodos();
        $objInfraParametroBD = new InfraParametroBD(BancoSip::getInstance());
        $objInfraParametroDTO = $objInfraParametroBD->consultar($objInfraParametroDTO);
        $objInfraParametroDTO->setStrValor($parStrNumeroVersao);
        $objInfraParametroBD->alterar($objInfraParametroDTO);
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.0
     */
    private function instalarV100() {
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
        $this->criarRecurso('pen_map_tipo_documento_envio_visualizar', 'Visualização de mapeamento de documentos enviados', $numIdSistema);

        // Acha o menu existente de Tipos de Documento
        $objItemMenuDTO = new ItemMenuDTO();
        $objItemMenuDTO->setNumIdSistema($numIdSistema);
        $objItemMenuDTO->setNumIdMenu($numIdMenu);
        $objItemMenuDTO->setStrRotulo('Tipos de Documento');
        $objItemMenuDTO->setNumMaxRegistrosRetorno(1);
        $objItemMenuDTO->retNumIdItemMenu();

        $objItemMenuBD = new ItemMenuBD(BancoSip::getInstance());
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
        $numIdRecurso = $this->criarRecurso('pen_map_tipo_documento_envio_cadastrar', 'Cadastro de mapeamento de documentos enviados', $numIdSistema);
        $this->criarMenu('Cadastrar', 10, $numIdItemMenuPai, $numIdMenu, $numIdRecurso, $numIdSistema);

        // Gera o submenu Mapeamento > Envio > Listar
        $numIdRecurso = $this->criarRecurso('pen_map_tipo_documento_envio_listar', 'Listagem de mapeamento de documentos enviados', $numIdSistema);
        $this->criarMenu('Listar', 20, $numIdItemMenuPai, $numIdMenu, $numIdRecurso, $numIdSistema);

        // Gera o submenu Mapeamento > Recebimento
        $numIdItemMenuPai = $this->criarMenu('Recebimento', 20, $_numIdItemMenuPai, $numIdMenu, null, $numIdSistema);

        // Gera o submenu Mapeamento > Recebimento > Cadastrar
        $numIdRecurso = $this->criarRecurso('pen_map_tipo_documento_recebimento_cadastrar', 'Cadastro de mapeamento de documentos recebidos', $numIdSistema);
        $this->criarMenu('Cadastrar', 10, $numIdItemMenuPai, $numIdMenu, $numIdRecurso, $numIdSistema);

        // Gera o submenu Mapeamento > Recebimento > Listar
        $numIdRecurso = $this->criarRecurso('pen_map_tipo_documento_recebimento_listar', 'Listagem de mapeamento de documentos recebidos', $numIdSistema);
        $this->criarMenu('Listar', 20, $numIdItemMenuPai, $numIdMenu, $numIdRecurso, $numIdSistema);

        //Atribui as permissões aos recursos e menus
        $this->atribuirPerfil($numIdSistema);

        // ---------- antigo método (instalarV003R003S003IW001) ---------- //
        $objBD = new ItemMenuBD(BancoSip::getInstance());

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
        $objInfraParametroDTO->setStrNome(self::PARAMETRO_VERSAO_MODULO_ANTIGO);
        $objInfraParametroDTO->setStrValor('1.0.0');
        $objInfraParametroBD = new InfraParametroBD(BancoSip::getInstance());
        $objInfraParametroBD->cadastrar($objInfraParametroDTO);
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.0.1
     */
    private function instalarV101() {
        // ---------- antigo método (instalarV006R004S001US039) ---------- //
        $objItemMenuBD = new ItemMenuBD(BancoSip::getInstance());

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
            throw new InfraException('Menu "Processo Eletrônico Nacional" não foi localizado');
        }

        // Administrao > Mapeamento de Hipóteses Legais de Envio
        $numIdItemMenu = $this->criarMenu('Mapeamento de Hipóteses Legais', 20, $objItemMenuDTO->getNumIdItemMenu(), $numIdMenu, null, $numIdSistema);

        // Administrao > Mapeamento de Hipóteses Legais de Envio > Envio
        $numIdItemMenu = $this->criarMenu('Envio', 10, $numIdItemMenu, $numIdMenu, null, $numIdSistema);

        // Administrao > Mapeamento de Hipóteses Legais de Envio > Envio > Cadastrar
        $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_enviado_alterar', 'Alterar de mapeamento de Hipóteses Legais de Envio', $numIdSistema);
        $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_enviado_cadastrar', 'Cadastro de mapeamento de Hipóteses Legais de Envio', $numIdSistema);
        $this->criarMenu('Cadastrar', 10, $numIdItemMenu, $numIdMenu, $numIdRecurso, $numIdSistema);

        // Administrao > Mapeamento de Hipóteses Legais de Envio > Envio > Listar
        $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_enviado_excluir', 'Excluir mapeamento de Hipóteses Legais de Envio', $numIdSistema);
        $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_enviado_listar', 'Listagem de mapeamento de Hipóteses Legais de Envio', $numIdSistema);
        $this->criarMenu('Listar', 20, $numIdItemMenu, $numIdMenu, $numIdRecurso, $numIdSistema);

        //Atribui as permisses aos recursos e menus
        $this->atribuirPerfil($numIdSistema);


        // ---------- antigo método (instalarV006R004S001US040) ---------- //
        $objBD = new ItemMenuBD(BancoSip::getInstance());

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
            throw new InfraException('Menu "Processo Eletrônico Nacional" não foi localizado');
        }

        // Administrao > Mapeamento de Hipóteses Legais de Envio > Envio
        $numIdItemMenu = $this->criarMenu('Recebimento', 20, $objDTO->getNumIdItemMenu(), $numIdMenu, null, $numIdSistema);

        // Administrao > Mapeamento de Hipóteses Legais de Envio > Envio > Cadastrar
        $this->criarRecurso('pen_map_hipotese_legal_recebido_alterar', 'Alteração de mapeamento de Hipóteses Legais de Recebimento', $numIdSistema);
        $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_recebido_cadastrar', 'Cadastro de mapeamento de Hipóteses Legais de Recebimento', $numIdSistema);
        $this->criarMenu('Cadastrar', 10, $numIdItemMenu, $numIdMenu, $numIdRecurso, $numIdSistema);

        // Administrao > Mapeamento de Hipóteses Legais de Envio > Envio > Listar
        $this->criarRecurso('pen_map_hipotese_legal_recebido_excluir', 'Exclusão de mapeamento de Hipóteses Legais de Recebimento', $numIdSistema);
        $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_recebido_listar', 'Listagem de mapeamento de Hipóteses Legais de Recebimento', $numIdSistema);
        $this->criarMenu('Listar', 20, $numIdItemMenu, $numIdMenu, $numIdRecurso, $numIdSistema);

        //Atribui as permisses aos recursos e menus
        $this->atribuirPerfil($numIdSistema);

        // ---------- antigo método (instalarV006R004S001US043) ---------- //
        $objBD = new ItemMenuBD(BancoSip::getInstance());

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
            throw new InfraException('Menu "Processo Eletrônico Nacional" não foi localizado');
        }

        $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_padrao_cadastrar', 'Acesso ao formulário de cadastro de mapeamento de Hipóteses Legais Padrão', $numIdSistema);

        $this->criarMenu('Hipótese de Restrição Padrão', 30, $objDTO->getNumIdItemMenu(), $numIdMenu, $numIdRecurso, $numIdSistema);
        $this->criarRecurso('pen_map_hipotese_legal_padrao', 'Método Cadastrar Padrão da RN de mapeamento de Hipóteses Legais', $numIdSistema);
        $this->atribuirPerfil($numIdSistema);

        $this->atualizarNumeroVersao('1.0.1');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.1.0
     */
    private function instalarV102() {

        $objBD = new ItemMenuBD(BancoSip::getInstance());

        //----------------------------------------------------------------------
        // Achar o sistema
        $numIdSistema = $this->getNumIdSistema('SEI');
        $numIdMenu = $this->getNumIdMenu('Principal', $numIdSistema);

        $objDTO = new ItemMenuDTO();
        $objDTO->setNumIdSistema($numIdSistema);
        $objDTO->setNumIdMenu($numIdMenu);
        $objDTO->setStrRotulo('Processo Eletrônico Nacional');
        $objDTO->setNumMaxRegistrosRetorno(1);
        $objDTO->retNumIdItemMenu();

        $objDTO = $objBD->consultar($objDTO);

        if(empty($objDTO)) {
            throw new InfraException('Menu "Processo Eletrônico Nacional" não foi localizado');
        }

        // Administrao > Mapeamento de Hipóteses Legais de Envio > Envio
        $numIdItemMenu = $this->criarMenu('Mapeamento de Unidades', 20, $objDTO->getNumIdItemMenu(), $numIdMenu, null, $numIdSistema);

        // Cadastro do menu de administração parâmetros
        $numIdRecurso = $this->criarRecurso('pen_parametros_configuracao', 'Parametros de Configuração', $numIdSistema);
        $this->criarMenu('Parâmetros de Configuração', 20, $objDTO->getNumIdItemMenu(), $numIdMenu, $numIdRecurso, $numIdSistema);

        // Administrao > Mapeamento de Hipóteses Legais de Envio > Envio > Cadastrar
        $this->criarRecurso('pen_map_unidade_alterar', 'Alteração de mapeamento de Unidades', $numIdSistema);
        $numIdRecurso = $this->criarRecurso('pen_map_unidade_cadastrar', 'Cadastro de mapeamento de Unidades', $numIdSistema);
        $this->criarMenu('Cadastrar', 10, $numIdItemMenu, $numIdMenu, $numIdRecurso, $numIdSistema);

        // Administrao > Mapeamento de Hipóteses Legais de Envio > Envio > Listar
        $this->criarRecurso('pen_map_unidade_excluir', 'Exclusão de mapeamento de Unidades', $numIdSistema);
        $numIdRecurso = $this->criarRecurso('pen_map_unidade_listar', 'Listagem de mapeamento de Unidades', $numIdSistema);
        $this->criarMenu('Listar', 20, $numIdItemMenu, $numIdMenu, $numIdRecurso, $numIdSistema);


        // ------------------ Atribui as permisses aos recursos e menus ----------------------//
        $this->atribuirPerfil($numIdSistema);

        $this->atualizarNumeroVersao('1.1.0');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.0.3
     */
    private function instalarV103() {
        $numIdSistema = $this->getNumIdSistema('SEI');

        //Alterar rotulo do menu
        $objDTO = new ItemMenuDTO();
        $objDTO->setStrRotulo('Indicar Hiptese de Restrio Padro');
        $objDTO->retNumIdItemMenu();
        $objDTO->retNumIdMenu();
        $objBD = new ItemMenuBD($this->getObjInfraIBanco());
        $objDTO = $objBD->consultar($objDTO);
        if ($objDTO) {
            $objDTO->setStrRotulo('Hipótese de Restrição Padrão');
            $objBD->alterar($objDTO);
        }

        //Alterar nomeclatura do recurso
        $objDTO = new RecursoDTO();
        $objDTO->setStrNome('pen_map_hipotese_legal_recebido_listar');
        $objDTO->retNumIdRecurso();
        $objBD = new RecursoBD($this->getObjInfraIBanco());
        $objDTO = $objBD->consultar($objDTO);
        if ($objDTO) {
            $objDTO->setStrNome('pen_map_hipotese_legal_recebimento_listar');
            $objDTO->setStrCaminho('controlador.php?acao=pen_map_hipotese_legal_recebimento_listar');
            $objBD->alterar($objDTO);
        }

        //Alterar nomeclatura do recurso
        $objDTO = new RecursoDTO();
        $objDTO->setStrNome('pen_map_hipotese_legal_recebido_excluir');
        $objDTO->retNumIdRecurso();
        $objBD = new RecursoBD($this->getObjInfraIBanco());
        $objDTO = $objBD->consultar($objDTO);
        if ($objDTO) {
            $objDTO->setStrNome('pen_map_hipotese_legal_recebimento_excluir');
            $objDTO->setStrCaminho('controlador.php?acao=pen_map_hipotese_legal_recebimento_excluir');
            $objBD->alterar($objDTO);
        }

        //Alterar nomeclatura do recurso
        $objDTO = new RecursoDTO();
        $objDTO->setStrNome('pen_map_hipotese_legal_recebido_cadastrar');
        $objDTO->retNumIdRecurso();
        $objBD = new RecursoBD($this->getObjInfraIBanco());
        $objDTO = $objBD->consultar($objDTO);
        if ($objDTO) {
            $objDTO->setStrNome('pen_map_hipotese_legal_recebimento_cadastrar');
            $objDTO->setStrCaminho('controlador.php?acao=pen_map_hipotese_legal_recebimento_cadastrar');
            $objBD->alterar($objDTO);
        }

        //Alterar nomeclatura do recurso
        $objDTO = new RecursoDTO();
        $objDTO->setStrNome('pen_map_hipotese_legal_recebido_alterar');
        $objDTO->retNumIdRecurso();
        $objBD = new RecursoBD($this->getObjInfraIBanco());
        $objDTO = $objBD->consultar($objDTO);
        if ($objDTO) {
            $objDTO->setStrNome('pen_map_hipotese_legal_recebimento_alterar');
            $objDTO->setStrCaminho('controlador.php?acao=pen_map_hipotese_legal_recebimento_alterar');
            $objBD->alterar($objDTO);
        }

        //Alterar nomeclatura do recurso
        $objDTO = new RecursoDTO();
        $objDTO->setStrNome('pen_map_hipotese_legal_enviado_listar');
        $objDTO->retNumIdRecurso();
        $objBD = new RecursoBD($this->getObjInfraIBanco());
        $objDTO = $objBD->consultar($objDTO);
        if ($objDTO) {
            $objDTO->setStrNome('pen_map_hipotese_legal_envio_listar');
            $objDTO->setStrCaminho('controlador.php?acao=pen_map_hipotese_legal_envio_listar');
            $objBD->alterar($objDTO);
        }

        //Alterar nomeclatura do recurso
        $objDTO = new RecursoDTO();
        $objDTO->setStrNome('pen_map_hipotese_legal_enviado_excluir');
        $objDTO->retNumIdRecurso();
        $objBD = new RecursoBD($this->getObjInfraIBanco());
        $objDTO = $objBD->consultar($objDTO);
        if ($objDTO) {
            $objDTO->setStrNome('pen_map_hipotese_legal_envio_excluir');
            $objDTO->setStrCaminho('controlador.php?acao=pen_map_hipotese_legal_envio_excluir');
            $objBD->alterar($objDTO);
        }

        //Alterar nomeclatura do recurso
        $objDTO = new RecursoDTO();
        $objDTO->setStrNome('pen_map_hipotese_legal_enviado_cadastrar');
        $objDTO->retNumIdRecurso();
        $objBD = new RecursoBD($this->getObjInfraIBanco());
        $objDTO = $objBD->consultar($objDTO);
        if ($objDTO) {
            $objDTO->setStrNome('pen_map_hipotese_legal_envio_cadastrar');
            $objDTO->setStrCaminho('controlador.php?acao=pen_map_hipotese_legal_envio_cadastrar');
            $objBD->alterar($objDTO);
        }

        //Alterar nomeclatura do recurso
        $objDTO = new RecursoDTO();
        $objDTO->setStrNome('pen_map_hipotese_legal_enviado_alterar');
        $objDTO->retNumIdRecurso();
        $objBD = new RecursoBD($this->getObjInfraIBanco());
        $objDTO = $objBD->consultar($objDTO);
        if ($objDTO) {
            $objDTO->setStrNome('pen_map_hipotese_legal_envio_alterar');
            $objDTO->setStrCaminho('controlador.php?acao=pen_map_hipotese_legal_envio_alterar');
            $objBD->alterar($objDTO);
        }

        //Cadastrar recurso de alteração dos parâmetros
        $this->criarRecurso('pen_parametros_configuracao_alterar', 'Alteração de parametros de configuração do módulo PEN', $numIdSistema);

        $this->atualizarNumeroVersao('1.0.3');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.0.4
     */
    private function instalarV104() {
        $numIdSistema = $this->getNumIdSistema('SEI');

        //Cadastrar recurso Mapeamento dos Tipo de documentos enviados
        $this->criarRecurso('pen_map_tipo_documento_envio_alterar', 'Alteração de mapeamento de documentos enviados', $numIdSistema);
        $this->criarRecurso('pen_map_tipo_documento_envio_excluir', 'Exclusão de mapeamento de documentos enviados', $numIdSistema);

        //Cadastrar recurso Mapeamento dos Tipo de documentos recebido
        $this->criarRecurso('pen_map_tipo_documento_recebimento_alterar', 'Alteração de mapeamento de documentos recebimento', $numIdSistema);
        $this->criarRecurso('pen_map_tipo_documento_recebimento_excluir', 'Exclusão de mapeamento de documentos recebimento', $numIdSistema);
        $this->criarRecurso('pen_map_tipo_documento_recebimento_visualizar', 'Visualização de mapeamento de documentos recebimento', $numIdSistema);

        //Alterar nomeclatura do recurso (recebido)
        $objDTO = new RecursoDTO();
        $objDTO->setStrNome('pen_map_tipo_doc_recebido_cadastrar');
        $objDTO->retNumIdRecurso();
        $objBD = new RecursoBD($this->getObjInfraIBanco());
        $objDTO = $objBD->consultar($objDTO);
        if ($objDTO) {
            $objDTO->setStrNome('pen_map_tipo_documento_recebimento_cadastrar');
            $objDTO->setStrCaminho('controlador.php?acao=pen_map_tipo_documento_recebimento_cadastrar');
            $objBD->alterar($objDTO);
        }
        $objDTO = new RecursoDTO();
        $objDTO->setStrNome('pen_map_tipo_doc_enviado_visualizar');
        $objDTO->retNumIdRecurso();
        $objBD = new RecursoBD($this->getObjInfraIBanco());
        $objDTO = $objBD->consultar($objDTO);
        if ($objDTO) {
            $objDTO->setStrNome('pen_map_tipo_documento_envio_visualizar');
            $objDTO->setStrCaminho('controlador.php?acao=pen_map_tipo_documento_envio_visualizar');
            $objBD->alterar($objDTO);
        }
        $objDTO = new RecursoDTO();
        $objDTO->setStrNome('pen_map_tipo_doc_recebido_listar');
        $objDTO->retNumIdRecurso();
        $objBD = new RecursoBD($this->getObjInfraIBanco());
        $objDTO = $objBD->consultar($objDTO);
        if ($objDTO) {
            $objDTO->setStrNome('pen_map_tipo_documento_recebimento_listar');
            $objDTO->setStrCaminho('controlador.php?acao=pen_map_tipo_documento_recebimento_listar');
            $objBD->alterar($objDTO);
        }

        //Alterar nomeclatura do recurso (envio)
        $objDTO = new RecursoDTO();
        $objDTO->setStrNome('pen_map_tipo_doc_enviado_cadastrar');
        $objDTO->retNumIdRecurso();
        $objBD = new RecursoBD($this->getObjInfraIBanco());
        $objDTO = $objBD->consultar($objDTO);
        if ($objDTO) {
            $objDTO->setStrNome('pen_map_tipo_documento_envio_cadastrar');
            $objDTO->setStrCaminho('controlador.php?acao=pen_map_tipo_documento_envio_cadastrar');
            $objBD->alterar($objDTO);
        }
        $objDTO = new RecursoDTO();
        $objDTO->setStrNome('pen_map_tipo_doc_enviado_listar');
        $objDTO->retNumIdRecurso();
        $objBD = new RecursoBD($this->getObjInfraIBanco());
        $objDTO = $objBD->consultar($objDTO);
        if ($objDTO) {
            $objDTO->setStrNome('pen_map_tipo_documento_envio_listar');
            $objDTO->setStrCaminho('controlador.php?acao=pen_map_tipo_documento_envio_listar');
            $objBD->alterar($objDTO);
        }

        $this->atualizarNumeroVersao('1.0.4');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.1.1
     */
    private function instalarV111() {
        $numIdSistema = $this->getNumIdSistema('SEI');

        //Ajuste em nome da variável de versão do módulo VERSAO_MODULO_PEN
        BancoSIP::getInstance()->executarSql("update infra_parametro set nome = '" . self::PARAMETRO_VERSAO_MODULO . "' where nome = '" . self::PARAMETRO_VERSAO_MODULO_ANTIGO . "'");

        //Adequação em nome de recursos do módulo
        $this->renomearRecurso($numIdSistema, 'apensados_selecionar_expedir_procedimento', 'pen_apensados_selecionar_expedir_procedimento');

        //Atualização com recursos não adicionados automaticamente em versões anteriores
        $this->arrRecurso = array_merge($this->arrRecurso, array(
            $this->consultarRecurso($numIdSistema, "pen_map_tipo_documento_envio_alterar"),
            $this->consultarRecurso($numIdSistema, "pen_map_tipo_documento_envio_excluir"),
            $this->consultarRecurso($numIdSistema, "pen_map_tipo_documento_recebimento_alterar"),
            $this->consultarRecurso($numIdSistema, "pen_map_tipo_documento_recebimento_excluir"),
            $this->consultarRecurso($numIdSistema, "pen_map_tipo_documento_recebimento_visualizar"),
            $this->consultarRecurso($numIdSistema, "pen_parametros_configuracao_alterar")
            ));

        $this->atribuirPerfil($numIdSistema);

        $objPerfilRN = new PerfilRN();
        $objPerfilDTO = new PerfilDTO();
        $objPerfilDTO->retNumIdPerfil();
        $objPerfilDTO->setNumIdSistema($numIdSistema);
        $objPerfilDTO->setStrNome('Administrador');
        $objPerfilDTO = $objPerfilRN->consultar($objPerfilDTO);
        if ($objPerfilDTO == null){
            throw new InfraException('Perfil Administrador do sistema SEI não encontrado.');
        }

        $numIdPerfilSeiAdministrador = $objPerfilDTO->getNumIdPerfil();

        $objRelPerfilRecursoDTO = new RelPerfilRecursoDTO();
        $objRelPerfilRecursoDTO->retTodos();
        $objRelPerfilRecursoDTO->setNumIdSistema($numIdSistema);
        $objRelPerfilRecursoDTO->setNumIdPerfil($numIdPerfilSeiAdministrador);
        $arrRecursosRemoverAdministrador = array(
            $this->consultarRecurso($numIdSistema, "pen_procedimento_expedido_listar"),
            $this->consultarRecurso($numIdSistema, "pen_procedimento_expedir"),
            );
        $objRelPerfilRecursoDTO->setNumIdRecurso($arrRecursosRemoverAdministrador, InfraDTO::$OPER_IN);
        $objRelPerfilRecursoRN = new RelPerfilRecursoRN();
        $objRelPerfilRecursoRN->excluir($objRelPerfilRecursoRN->listar($objRelPerfilRecursoDTO));

        $this->atualizarNumeroVersao('1.1.1');
    }


    /**
     * Instala/Atualiza os módulo PEN para versão 1.1.9
     */
    private function instalarV119()
    {
        /* Corrige nome de menu de trâmite de documentos */
        $numIdSistema = $this->getNumIdSistema('SEI');
        $numIdMenuPai = $this->getNumIdMenu('Principal', $numIdSistema);

        //Corrige nome do recurso
        $objRecursoDTO = new RecursoDTO();
        $objRecursoDTO->setNumIdSistema($numIdSistema);
        $objRecursoDTO->setStrNome('pen_procedimento_expedido_listar');
        $objRecursoDTO->retNumIdRecurso();
        $objRecursoBD = new RecursoBD($this->getObjInfraIBanco());
        $objRecursoDTO = $objRecursoBD->consultar($objRecursoDTO);
        if(isset($objRecursoDTO)){
            $numIdRecurso = $objRecursoDTO->getNumIdRecurso();
            $objRecursoDTO->setStrDescricao('Processos Tramitados Externamente');
            $objRecursoBD->alterar($objRecursoDTO);
        }

        $objItemMenuDTO = new ItemMenuDTO();
        $objItemMenuDTO->setNumIdItemMenuPai(null);
        $objItemMenuDTO->setNumIdSistema($numIdSistema);
        $objItemMenuDTO->setNumIdRecurso($numIdRecurso);
        $objItemMenuDTO->setStrRotulo('Processos Trâmitados Externamente');
        $objItemMenuDTO->retNumIdMenu();
        $objItemMenuDTO->retNumIdItemMenu();
        $objItemMenuBD = new ItemMenuBD(BancoSip::getInstance());
        $objItemMenuDTO = $objItemMenuBD->consultar($objItemMenuDTO);
        if(isset($objItemMenuDTO)){
            $objItemMenuDTO->setStrDescricao('Processos Tramitados Externamente');
            $objItemMenuDTO->setStrRotulo('Processos Tramitados Externamente');
            $objItemMenuBD->alterar($objItemMenuDTO);
        }


        $this->atualizarNumeroVersao('1.1.9');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.1.10
     */
    private function instalarV1110()
    {
        $this->atualizarNumeroVersao('1.1.10');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.1.11
     */
    private function instalarV1111()
    {
        $this->atualizarNumeroVersao('1.1.11');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.1.12
     */
    private function instalarV1112()
    {
        $this->atualizarNumeroVersao('1.1.12');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.1.13
     */
    private function instalarV1113()
    {
        $this->atualizarNumeroVersao('1.1.13');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.1.14
     */
    private function instalarV1114()
    {
        $this->atualizarNumeroVersao('1.1.14');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.1.15
     */
    private function instalarV1115()
    {
        $this->atualizarNumeroVersao('1.1.15');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.1.16
     */
    private function instalarV1116()
    {
        $this->atualizarNumeroVersao('1.1.16');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.1.17
     */
    private function instalarV1117()
    {
        $this->atualizarNumeroVersao('1.1.17');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.2.0
     */
    private function instalarV1200()
    {
        $this->atualizarNumeroVersao('1.2.0');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.2.1
     */
    private function instalarV1201()
    {
        $this->atualizarNumeroVersao('1.2.1');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.2.2
     */
    private function instalarV1202()
    {
        $this->atualizarNumeroVersao('1.2.2');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.2.3
     */
    private function instalarV1203()
    {
        $this->atualizarNumeroVersao('1.2.3');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.2.4
     */
    private function instalarV1204()
    {
        $this->atualizarNumeroVersao('1.2.4');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.2.5
     */
    private function instalarV1205()
    {
        $this->atualizarNumeroVersao('1.2.5');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.2.6
     */
    private function instalarV1206()
    {
        $this->atualizarNumeroVersao('1.2.6');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.3.0
     */
    private function instalarV1300()
    {
        $this->atualizarNumeroVersao('1.3.0');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.4.0
     */
    private function instalarV1400()
    {
        $this->atualizarNumeroVersao('1.4.0');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.4.1
     */
    private function instalarV1401()
    {
        $this->atualizarNumeroVersao('1.4.1');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.4.2
     */
    private function instalarV1402()
    {
        $this->atualizarNumeroVersao('1.4.2');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.4.3
     */
    private function instalarV1403()
    {
        $this->atualizarNumeroVersao('1.4.3');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.5.0
     */
    private function instalarV1500()
    {
        $this->atualizarNumeroVersao('1.5.0');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.5.1
     */
    private function instalarV1501()
    {
        $this->atualizarNumeroVersao('1.5.1');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.5.2
     */
    private function instalarV1502()
    {
        $this->atualizarNumeroVersao('1.5.2');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 1.5.3
     */
    private function instalarV1503()
    {
        $this->atualizarNumeroVersao('1.5.3');
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 2.0.0
     */
    private function instalarV2000_beta1()
    {
        // Criar novos recursos de configuração de espécie documental padrão para envio de processos
        $this->logar('ATRIBUIÇÃO DE PERMISSÃO DE ATRIBUÍÇÃO DE ESPÉCIES/TIPO DE DOCUMENTO PADRÃO AO PERFIL ADMINISTRADOR');
        $numIdSistemaSei = $this->getNumIdSistema('SEI');
        $numIdPerfilSeiAdministrador = ScriptSip::obterIdPerfil($numIdSistemaSei, "Administrador");
        $this->criarRecurso('pen_map_tipo_documento_envio_padrao_atribuir', 'Atribuir espécie documental padrão para envio de processos', $numIdSistemaSei);
        $this->criarRecurso('pen_map_tipo_documento_envio_padrao_consultar', 'Consultar espécie documental padrão para envio de processos', $numIdSistemaSei);
        $this->criarRecurso('pen_map_tipo_doc_recebimento_padrao_atribuir', 'Atribuir tipo de documento padrão para recebimento de processos', $numIdSistemaSei);
        $this->criarRecurso('pen_map_tipo_doc_recebimento_padrao_consultar', 'Consultar tipo de documento padrão para recebimento de processos', $numIdSistemaSei);
        ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, 'pen_map_tipo_documento_envio_padrao_atribuir');
        ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, 'pen_map_tipo_documento_envio_padrao_consultar');
        ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, 'pen_map_tipo_doc_recebimento_padrao_atribuir');
        ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, 'pen_map_tipo_doc_recebimento_padrao_consultar');

        $this->logar('RECONFIGURAÇÃO DE MENUS DE FUNCIONALIDADES DE MAPEAMENTO DE ESPÉCIES DOCUMENTAIS DO PEN');
        $numIdSistemaSei = $this->getNumIdSistema('SEI');
        $numIdPerfilSeiAdministrador = ScriptSip::obterIdPerfil($numIdSistemaSei, "Administrador");
        $numIdMenuSEI = ScriptSip::obterIdMenu($numIdSistemaSei, 'Principal');

        try {
            // Remove item de menu anterior e seus submenus configurados de forma errada
            $numIdItemMenuMapTipDoc = ScriptSip::obterIdItemMenu($numIdSistemaSei, $numIdMenuSEI,'Mapeamento de Tipos de Documento');
            ScriptSip::removerItemMenu($numIdSistemaSei, $numIdMenuSEI, $numIdItemMenuMapTipDoc);
        } catch (\Exception $e) {
            $this->logar("Item de menu de mapeamento de tipos de documentos não pode ser localizado");
        }

        // Recriar item de menu agrupador de mapeamento de tipos de documento
        $numIdItemMenuPEN = ScriptSip::obterIdItemMenu($numIdSistemaSei, $numIdMenuSEI, "Processo Eletrônico Nacional");
        $objItemMenuMapeamentoDTO = ScriptSip::adicionarItemMenu($numIdSistemaSei, $numIdPerfilSeiAdministrador, $numIdMenuSEI, $numIdItemMenuPEN, null,
            "Mapeamento de Tipos de Documentos", 20
        );

        // Recriar item de menu de mapeamento de envio de documentos, acionando o recurso listar correspondente
        $numIdItemMenuMapeamento = $objItemMenuMapeamentoDTO->getNumIdItemMenu();
        $objRecursoMapEnvioListar = ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, "pen_map_tipo_documento_envio_listar");
        $numIdRecursoMapEnvioListar = $objRecursoMapEnvioListar->getNumIdRecurso();
        ScriptSip::adicionarItemMenu($numIdSistemaSei, $numIdPerfilSeiAdministrador, $numIdMenuSEI, $numIdItemMenuMapeamento, $numIdRecursoMapEnvioListar,"Envio", 10);

        // Recriar item de menu de mapeamento de recebimento de documentos, acionando o recurso listar correspondente
        $objRecursoMapRecebimentoListar = ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, "pen_map_tipo_documento_recebimento_listar");
        $numIdRecursoMapRecebimentoListar = $objRecursoMapRecebimentoListar->getNumIdRecurso();
        ScriptSip::adicionarItemMenu($numIdSistemaSei, $numIdPerfilSeiAdministrador, $numIdMenuSEI, $numIdItemMenuMapeamento, $numIdRecursoMapRecebimentoListar,"Recebimento", 20);

        // Redefinir ordem de apresentação dos menus de administração do módulo
        $arrOrdemMenusAdministracaoPEN = array(
            array("rotulo" => "Parâmetros de Configuração", "sequencia" => 10),
            array("rotulo" => "Mapeamento de Tipos de Documentos", "sequencia" => 20),
            array("rotulo" => "Mapeamento de Unidades", "sequencia" => 30),
            array("rotulo" => "Mapeamento de Hipóteses Legais", "sequencia" => 40),
        );

        array_map(function($item) use ($numIdSistemaSei, $numIdMenuSEI){
            $objItemMenuRN = new ItemMenuRN();
            $numIdItemMenu = ScriptSip::obterIdItemMenu($numIdSistemaSei, $numIdMenuSEI, $item["rotulo"]);
            if(isset($numIdItemMenu)){
                $objItemMenuDTO = new ItemMenuDTO();
                $objItemMenuDTO->setNumIdMenu($numIdMenuSEI);
                $objItemMenuDTO->setNumIdItemMenu($numIdItemMenu);
                $objItemMenuDTO->setNumSequencia($item["sequencia"]);
                $objItemMenuRN->alterar($objItemMenuDTO);
            }
        }, $arrOrdemMenusAdministracaoPEN);


        $this->logar('Atribuição de permissões do módulo ao perfil Básico do SEI');
        $strNomeMenuProcessosTramitados = "Processos Tramitados Externamente";
        $numIdSistemaSei = ScriptSip::obterIdSistema('SEI');
        $numIdPerfilSeiBasico = ScriptSip::obterIdPerfil($numIdSistemaSei, "Básico");
        $numIdMenuSei = ScriptSip::obterIdMenu($numIdSistemaSei,'Principal');

        // Remove item de menu e adiciona-o novamente para criá-lo seguindo o padrão definido na rotina adicionarItemMenu
        ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiBasico, 'pen_procedimento_expedir');
        $objRecursoDTO = ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiBasico, 'pen_procedimento_expedido_listar');
        $numIdMenuProcessoTramitados = ScriptSip::obterIdItemMenu($numIdSistemaSei, $numIdMenuSei, $strNomeMenuProcessosTramitados);
        ScriptSip::removerItemMenu($numIdSistemaSei, $numIdMenuSei, $numIdMenuProcessoTramitados);
        ScriptSip::adicionarItemMenu($numIdSistemaSei, $numIdPerfilSeiBasico, $numIdMenuSei, null, $objRecursoDTO->getNumIdRecurso(), $strNomeMenuProcessosTramitados, 55);

        $this->atualizarNumeroVersao("2.0.0-beta1");
    }


    /**
     * Instala/Atualiza os módulo PEN para versão 2.0.0-beta2
     */
    protected function instalarV2000_beta2()
    {
        $this->atualizarNumeroVersao("2.0.0-beta2");
    }


    /**
     * Instala/Atualiza os módulo PEN para versão 2.0.0-beta3
     */
    protected function instalarV2000_beta3()
    {
        $this->atualizarNumeroVersao("2.0.0-beta3");
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 2.0.0-beta4
     */
    protected function instalarV2000_beta4()
    {
        $this->atualizarNumeroVersao("2.0.0-beta4");
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 2.0.0-beta5
     */
    protected function instalarV2000_beta5()
    {
        $this->atualizarNumeroVersao("2.0.0-beta5");
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 2.0.0
     */
    protected function instalarV2000()
    {
        $this->atualizarNumeroVersao("2.0.0");
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 2.0.1
     */
    protected function instalarV2001()
    {
        $this->atualizarNumeroVersao("2.0.1");
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 2.1.0
     */
    protected function instalarV2100()
    {
	$this->atualizarNumeroVersao("2.1.0");
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 2.0.2
     */
    protected function instalarV2101()
    {
        // Adição de recursos relacionados à consulta de mapeamento de hipóteses legais de envio e recebimento
        $numIdSistemaSei = $this->getNumIdSistema('SEI');
        $numIdPerfilSeiAdministrador = ScriptSip::obterIdPerfil($numIdSistemaSei, "Administrador");
        $this->criarRecurso('pen_map_hipotese_legal_recebimento_consultar', 'Consulta de mapeamento de Hipóteses Legais de Recebimento', $numIdSistemaSei);
        ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, 'pen_map_hipotese_legal_recebimento_consultar');
        $this->criarRecurso('pen_map_hipotese_legal_envio_consultar', 'Consulta de mapeamento de Hipóteses Legais de Envio', $numIdSistemaSei);
        ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, 'pen_map_hipotese_legal_envio_consultar');
        $this->atualizarNumeroVersao("2.1.1");
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 2.1.2
     */
    protected function instalarV2102()
    {
	    $this->atualizarNumeroVersao("2.1.2");
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 2.1.3
     */
    protected function instalarV2103()
    {
	    $this->atualizarNumeroVersao("2.1.3");
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 2.1.4
     */
    protected function instalarV2104()
    {
	    $this->atualizarNumeroVersao("2.1.4");
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 2.1.5
     */
    protected function instalarV2105()
    {
	    $this->atualizarNumeroVersao("2.1.5");
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 2.1.6
     */
    protected function instalarV2106()
    {
	    $this->atualizarNumeroVersao("2.1.6");
    }

    /**
     * Instala/Atualiza os módulo PEN para versão 2.1.6
     */
    protected function instalarV2107()
    {
	    $this->atualizarNumeroVersao("2.1.7");
    }

    protected function instalarV3000()
    {
	    $this->atualizarNumeroVersao("3.0.0");
    }
}

try {

    //Normaliza o formato de número de versão considerando dois caracteres para cada item (3.0.15 -> 030015)
    $numVersaoAtual = explode('.', SIP_VERSAO);
    $numVersaoAtual = array_map(function($item){ return str_pad($item, 2, '0', STR_PAD_LEFT); }, $numVersaoAtual);
    $numVersaoAtual = intval(join($numVersaoAtual));

    //Normaliza o formato de número de versão considerando dois caracteres para cada item (2.1.0 -> 020100)
    // A partir da versão 2.1.0 é que o SIP passa a dar suporte ao UsuarioScript/SenhaScript
    $numVersaoScript = explode('.', "2.1.0");
    $numVersaoScript = array_map(function($item){ return str_pad($item, 2, '0', STR_PAD_LEFT); }, $numVersaoScript);
    $numVersaoScript = intval(join($numVersaoScript));

    if ($numVersaoAtual >= $numVersaoScript) {
        BancoSip::getInstance()->setBolScript(true);

        if (!ConfiguracaoSip::getInstance()->isSetValor('BancoSip','UsuarioScript')){
            throw new InfraException('Chave BancoSip/UsuarioScript não encontrada.');
        }

        if (InfraString::isBolVazia(ConfiguracaoSip::getInstance()->getValor('BancoSip','UsuarioScript'))){
            throw new InfraException('Chave BancoSip/UsuarioScript não possui valor.');
        }

        if (!ConfiguracaoSip::getInstance()->isSetValor('BancoSip','SenhaScript')){
            throw new InfraException('Chave BancoSip/SenhaScript não encontrada.');
        }

        if (InfraString::isBolVazia(ConfiguracaoSip::getInstance()->getValor('BancoSip','SenhaScript'))){
            throw new InfraException('Chave BancoSip/SenhaScript não possui valor.');
        }
    }

    $objAtualizarRN = new PenAtualizarSipRN($arrArgs);
    $objAtualizarRN->atualizarVersao();
    exit(0);
} catch (Exception $e) {
    print InfraException::inspecionar($e);
    exit(1);
}

print PHP_EOL;
