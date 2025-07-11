<?php

// Identifica��o da vers�o do m�dulo mod-sei-pen. Este deve estar sempre sincronizado com a vers�o definida em PENIntegracao.php
define("VERSAO_MODULO_PEN", "4.0.1");

$dirSipWeb = !defined("DIR_SIP_WEB") ? getenv("DIR_SIP_WEB") ?: __DIR__ . "/../../web" : DIR_SIP_WEB;
require_once $dirSipWeb . '/Sip.php';


class VersaoSip4RN extends InfraScriptVersao
{
  public function __construct()
    {
      parent::__construct();
  }

  protected function inicializarObjInfraIBanco()
    {
      return BancoSip::getInstance();
  }

  protected function verificarVersaoInstaladaControlado()
    {
      $objInfraParametroDTOFiltro = new InfraParametroDTO();
      $objInfraParametroDTOFiltro->setStrNome(PenAtualizarSipRN::PARAMETRO_VERSAO_MODULO);
      $objInfraParametroDTOFiltro->retStrNome();

      $objInfraParametroBD = new InfraParametroBD(BancoSip::getInstance());
      $objInfraParametroDTO = $objInfraParametroBD->consultar($objInfraParametroDTOFiltro);
    if (is_null($objInfraParametroDTO)) {
        $objInfraParametroDTO = new InfraParametroDTO();
        $objInfraParametroDTO->setStrNome(PenAtualizarSipRN::PARAMETRO_VERSAO_MODULO);
        $objInfraParametroDTO->setStrValor('0.0.0');
        $objInfraParametroBD->cadastrar($objInfraParametroDTO);
    }

      return $objInfraParametroDTO->getStrNome();
  }
  
  // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
  public function versao_0_0_0($strVersaoAtual)
    {
  }

  public function atualizarVersaoCompatibilidade($strVersaoAtual)
    {
      $objAtualizarRN = new PenAtualizarSipRN();
      $objAtualizarRN->atualizarVersao();
  }
}

class PenAtualizarSipRN extends InfraRN
{
    const NOME_MODULO = 'Integra��o Tramita GOV.BR';
    const PARAMETRO_VERSAO_MODULO_ANTIGO = 'PEN_VERSAO_MODULO_SIP';
    const PARAMETRO_VERSAO_MODULO = 'VERSAO_MODULO_PEN';

    protected $versaoMinRequirida = '1.30.0';
    private $arrRecurso = [];
    private $arrMenu = [];

  public function __construct()
    {
      parent::__construct();
  }

  protected function inicializarObjInfraIBanco()
    {
      return BancoSip::getInstance();
  }

    /**
     * Inicia o script criando um contator interno do tempo de execu��o
     */
  protected function inicializar($strTitulo)
    {
      InfraDebug::getInstance()->setBolLigado(true);
      InfraDebug::getInstance()->setBolDebugInfra(true);
      InfraDebug::getInstance()->setBolEcho(true);
      InfraDebug::getInstance()->limpar();

      $this->numSeg = InfraUtil::verificarTempoProcessamento();
      $this->logar($strTitulo);
  }

  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
  protected function atualizarVersaoConectado()
    {
    try {
        $this->inicializar('INICIANDO ATUALIZACAO DO MODULO TRAMITA GOV.BR NO SIP VERSAO');

        //testando se esta usando BDs suportados
      if (!(BancoSip::getInstance() instanceof InfraMySql) 
            && !(BancoSip::getInstance() instanceof InfraSqlServer) 
            && !(BancoSip::getInstance() instanceof InfraOracle) 
            && !(BancoSip::getInstance() instanceof InfraPostgreSql)
        ) {

        $this->finalizar('BANCO DE DADOS NAO SUPORTADO: ' . get_parent_class(BancoSip::getInstance()), true);
        return;
      }

        //testando permissoes de cria��es de tabelas
        $objInfraMetaBD = new InfraMetaBD(BancoSip::getInstance());

      if (count($objInfraMetaBD->obterTabelas('pen_sip_teste')) == 0) {
          BancoSip::getInstance()->executarSql('CREATE TABLE pen_sip_teste (id ' . $objInfraMetaBD->tipoNumero() . ' null)');
      }
        BancoSip::getInstance()->executarSql('DROP TABLE pen_sip_teste');

        $objInfraParametro = new InfraParametro(BancoSip::getInstance());

        // Aplica��o de scripts de atualiza��o de forma incremental
        // Aus�ncia de [break;] proposital para realizar a atualiza��o incremental de vers�es
        $strVersaoModuloPen = $objInfraParametro->getValor(self::PARAMETRO_VERSAO_MODULO, false) ?: $objInfraParametro->getValor(self::PARAMETRO_VERSAO_MODULO_ANTIGO, false);
        // phpcs:disable PSR2.ControlStructures.SwitchDeclaration.TerminatingComment
      switch ($strVersaoModuloPen) {
        case '': //case '' - Nenhuma vers�o instalada
        case '0.0.0':
            $this->instalarV100();
        case '1.0.0':
            $this->instalarV101();
        case '1.0.1':
            $this->instalarV102();
        case '1.0.2':
            $this->instalarV103();
        case '1.0.3':
            $this->instalarV104();
        case '1.0.4':
            $this->instalarV111();
        case '1.1.1': //N�o houve atualiza��o no banco de dados
        case '1.1.2': //N�o houve atualiza��o no banco de dados
        case '1.1.3': //N�o houve atualiza��o no banco de dados
        case '1.1.4': //N�o houve atualiza��o no banco de dados
        case '1.1.5': //N�o houve atualiza��o no banco de dados
        case '1.1.6': //N�o houve atualiza��o no banco de dados
        case '1.1.7': //N�o houve atualiza��o no banco de dados
        case '1.1.8': 
            $this->instalarV119();
        case '1.1.9':
            $this->instalarV1110();
        case '1.1.10':
            $this->instalarV1111();
        case '1.1.11':
            $this->instalarV1112();
        case '1.1.12':
            $this->instalarV1113();
        case '1.1.13':
            $this->instalarV1114();
        case '1.1.14':
            $this->instalarV1115();
        case '1.1.15':
            $this->instalarV1116();
        case '1.1.16':
            $this->instalarV1117();
        case '1.1.17':
            $this->instalarV1200();
        case '1.2.0':
            $this->instalarV1201();
        case '1.2.1':
            $this->instalarV1202();
        case '1.2.2':
            $this->instalarV1203();
        case '1.2.3':
            $this->instalarV1204();
        case '1.2.4':
            $this->instalarV1205();
        case '1.2.5':
            $this->instalarV1206();
        case '1.2.6':
            $this->instalarV1300();
        case '1.3.0':
            $this->instalarV1400();
        case '1.4.0':
            $this->instalarV1401();
        case '1.4.1':
            $this->instalarV1402();
        case '1.4.2':
            $this->instalarV1403();
        case '1.4.3':
            $this->instalarV1500();
        case '1.5.0':
            $this->instalarV1501();
        case '1.5.1':
            $this->instalarV1502();
        case '1.5.2':
            $this->instalarV1503();
        case '1.5.3': // Faixa de poss�veis vers�es da release 1.5.x de retrocompatibilidade
        case '1.5.4': // Faixa de poss�veis vers�es da release 1.5.x de retrocompatibilidade
        case '1.5.5': // Faixa de poss�veis vers�es da release 1.5.x de retrocompatibilidade
        case '1.5.6': // Faixa de poss�veis vers�es da release 1.5.x de retrocompatibilidade
        case '1.5.7': 
            $this->instalarV2000_beta1();
        case '2.0.0-beta1':
            $this->instalarV2000_beta2();
        case '2.0.0-beta2':
            $this->instalarV2000_beta3();
        case '2.0.0-beta3':
            $this->instalarV2000_beta4();
        case '2.0.0-beta4':
            $this->instalarV2000_beta5();
        case '2.0.0-beta5':
            $this->instalarV2000();
        case '2.0.0':
            $this->instalarV2001();
        case '2.0.1':
            $this->instalarV2100();
        case '2.1.0':
            $this->instalarV2101();
        case '2.1.1':
            $this->instalarV2102();
        case '2.1.2':
            $this->instalarV2103();
        case '2.1.3':
            $this->instalarV2104();
        case '2.1.4':
            $this->instalarV2105();
        case '2.1.5':
            $this->instalarV2106();
        case '2.1.6':
            $this->instalarV2107();
        case '2.1.7':
            $this->instalarV3000();
        case '3.0.0':
            $this->instalarV3001();
        case '3.0.1':
            $this->instalarV3010();
        case '3.1.0':
            $this->instalarV3011();
        case '3.1.1':
            $this->instalarV3012();
        case '3.1.2':
            $this->instalarV3013();
        case '3.1.3':
            $this->instalarV3014();
        case '3.1.4':
            $this->instalarV3015();
        case '3.1.5':
            $this->instalarV3016();
        case '3.1.6':
            $this->instalarV3017();
        case '3.1.7':
            $this->instalarV3018();
        case '3.1.8':
            $this->instalarV3019();
        case '3.1.9':
            $this->instalarV30110();
        case '3.1.10':
            $this->instalarV30111();
        case '3.1.11':
            $this->instalarV30112();
        case '3.1.12':
            $this->instalarV30113();
        case '3.1.13':
            $this->instalarV30114();
        case '3.1.14':
            $this->instalarV30115();
        case '3.1.15':
            $this->instalarV30116();
        case '3.1.16':
            $this->instalarV30117();
        case '3.1.17':
            $this->instalarV30118();
        case '3.1.18':
            $this->instalarV30119();
        case '3.1.19':
            $this->instalarV30120();
        case '3.1.20':
            $this->instalarV30121();
        case '3.1.21':
            $this->instalarV30122();
        case '3.1.22':
            $this->instalarV3020();
        case '3.2.0':
            $this->instalarV3021();
        case '3.2.1':
            $this->instalarV3022();
        case '3.2.2':
            $this->instalarV3023();
        case '3.2.3':
            $this->instalarV3024();
        case '3.2.4':
            $this->instalarV3030();
        case '3.3.0':
            $this->instalarV3031();
        case '3.3.1':
            $this->instalarV3032();
        case '3.3.2':
            $this->instalarV3040();
        case '3.4.0':
            $this->instalarV3050();
        case '3.5.0':
            $this->instalarV3060();
        case '3.6.0':
            $this->instalarV3061();
        case '3.6.1':
            $this->instalarV3062();
        case '3.6.2':
            $this->instalarV3070();
        case '3.7.0':
            $this->instalarV3080();
        case (preg_match('/3.8.*/', $strVersaoModuloPen) ? true : false):
            $this->instalarV4000();
        case '4.0.0':
            $this->instalarV4010();
        
            break; // Aus�ncia de [break;] proposital para realizar a atualiza��o incremental de vers�es
        default:
            $this->finalizar('VERSAO DO M�DULO J� CONSTA COMO ATUALIZADA');
            return;
      }
        // phpcs:enable PSR2.ControlStructures.SwitchDeclaration.TerminatingComment


        $this->finalizar('FIM');
        InfraDebug::getInstance()->setBolDebugInfra(true);
    } catch (Exception $e) {

        InfraDebug::getInstance()->setBolLigado(false);
        InfraDebug::getInstance()->setBolDebugInfra(false);
        InfraDebug::getInstance()->setBolEcho(false);
        throw new InfraException('M�dulo do Tramita: Erro atualizando VERSAO.', $e);
    }
  }


    /**
     * Finaliza o script informando o tempo de execu��o.
     */
  protected function finalizar($strMsg = null, $bolErro = false)
    {
    if (!$bolErro) {
        $this->numSeg = InfraUtil::verificarTempoProcessamento($this->numSeg);
        $this->logar('TEMPO TOTAL DE EXECUCAO: ' . $this->numSeg . ' s');
    } else {
        $strMsg = 'ERRO: ' . $strMsg;
    }

    if ($strMsg != null) {
        $this->logar($strMsg);
    }

      InfraDebug::getInstance()->setBolLigado(false);
      InfraDebug::getInstance()->setBolDebugInfra(false);
      InfraDebug::getInstance()->setBolEcho(false);
      $this->numSeg = 0;
  }

    /**
     * Adiciona uma mensagem ao output para o usu�rio
     */
  protected function logar($strMsg)
    {
      InfraDebug::getInstance()->gravar($strMsg);
  }

    /**
     * Retorna o ID do sistema
     *
     * @return int
     */
  protected function getNumIdSistema($strSigla = 'SIP')
    {

      $objDTO = new SistemaDTO();
      $objDTO->setStrSigla($strSigla);
      $objDTO->setNumMaxRegistrosRetorno(1);
      $objDTO->retNumIdSistema();

      $objRN = new SistemaRN();
      $objDTO = $objRN->consultar($objDTO);

      return (empty($objDTO)) ? null : $objDTO->getNumIdSistema();
  }

    /**
     *
     * @return int C�digo do Menu
     */
  protected function getNumIdMenu($strMenu = 'Principal', $numIdSistema = 0)
    {

      $objDTO = new MenuDTO();
      $objDTO->setNumIdSistema($numIdSistema);
      $objDTO->setStrNome($strMenu);
      $objDTO->setNumMaxRegistrosRetorno(1);
      $objDTO->retNumIdMenu();

      $objRN = new MenuRN();
      $objDTO = $objRN->consultar($objDTO);

    if (empty($objDTO)) {
        throw new InfraException('M�dulo do Tramita: Menu ' . $strMenu . ' n�o encontrado.');
    }

      return $objDTO->getNumIdMenu();
  }

    /**
     * Cria novo recurso no SIP
     *
     * @return int C�digo do Recurso gerado
     */
  protected function criarRecurso($strNome, $strDescricao, $numIdSistema)
    {

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

  protected function renomearRecurso($numIdSistema, $strNomeAtual, $strNomeNovo)
    {

      $objRecursoDTO = new RecursoDTO();
      $objRecursoDTO->setBolExclusaoLogica(false);
      $objRecursoDTO->retNumIdRecurso();
      $objRecursoDTO->retStrCaminho();
      $objRecursoDTO->setNumIdSistema($numIdSistema);
      $objRecursoDTO->setStrNome($strNomeAtual);

      $objRecursoRN = new RecursoRN();
      $objRecursoDTO = $objRecursoRN->consultar($objRecursoDTO);

    if ($objRecursoDTO != null) {
        $objRecursoDTO->setStrNome($strNomeNovo);
        $objRecursoDTO->setStrCaminho(str_replace($strNomeAtual, $strNomeNovo, $objRecursoDTO->getStrCaminho()));
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

    if ($objRecursoDTO == null) {
        throw new InfraException("M�dulo do Tramita: Recurso com nome {$strNomeRecurso} n�o pode ser localizado.");
    }

      return $objRecursoDTO->getNumIdRecurso();
  }

  protected function consultarItemMenu($numIdSistema, $strNomeRecurso)
    {
      $numIdRecurso = $this->consultarRecurso($numIdSistema, $strNomeRecurso);

      $objItemMenuDTO = new ItemMenuDTO();
      $objItemMenuDTO->setBolExclusaoLogica(false);
      $objItemMenuDTO->setNumIdSistema($numIdSistema);
      $objItemMenuDTO->setNumIdRecurso($numIdRecurso);
      $objItemMenuDTO->retNumIdMenu();
      $objItemMenuDTO->retNumIdItemMenu();

      $objItemMenuRN = new ItemMenuRN();
      $objItemMenuDTO = $objItemMenuRN->consultar($objItemMenuDTO);

    if ($objItemMenuDTO == null) {
        throw new InfraException("M�dulo do Tramita: Item de menu n�o pode ser localizado.");
    }

      return [$objItemMenuDTO->getNumIdItemMenu(), $objItemMenuDTO->getNumIdMenu(), $numIdRecurso];
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
        $this->arrMenu[] = [$objDTO->getNumIdItemMenu(), $numIdMenu, $numIdRecurso];
    }

      return $objDTO->getNumIdItemMenu();
  }


    //TODO: Necess�rio refatorar m�todo abaixo devido a baixa qualidade da codifica��o
  public function addRecursosToPerfil($numIdPerfil, $numIdSistema)
    {

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

    //TODO: Necess�rio refatorar m�todo abaixo devido a baixa qualidade da codifica��o
  public function addMenusToPerfil($numIdPerfil, $numIdSistema)
    {

    if (!empty($this->arrMenu)) {

        $objDTO = new RelPerfilItemMenuDTO();
        $objBD = new RelPerfilItemMenuBD(BancoSip::getInstance());

      foreach ($this->arrMenu as $array) {

        [$numIdItemMenu, $numIdMenu, $numIdRecurso] = $array;

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

  public function atribuirPerfil($numIdSistema)
    {
      $objDTO = new PerfilDTO();
      $objBD = new PerfilBD(BancoSip::getInstance());
      $objRN = $this;

      // Vincula a um perfil os recursos e menus adicionados nos m�todos criarMenu e criarReturso
      $fnCadastrar = function ($strNome, $numIdSistema) use ($objDTO, $objBD, $objRN): void {

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
     * Atualiza o n�mero de vers�o do m�dulo nas tabelas de par�metro do sistema
     *
     * @param  string $parStrNumeroVersao
     * @return void
     */
  private function atualizarNumeroVersao($parStrNumeroVersao)
    {
      $objInfraParametroDTO = new InfraParametroDTO();
      $objInfraParametroDTO->setStrNome([self::PARAMETRO_VERSAO_MODULO, self::PARAMETRO_VERSAO_MODULO_ANTIGO], InfraDTO::$OPER_IN);
      $objInfraParametroDTO->retTodos();
      $objInfraParametroBD = new InfraParametroBD(BancoSip::getInstance());
      $arrObjInfraParametroDTO = $objInfraParametroBD->listar($objInfraParametroDTO);
    foreach ($arrObjInfraParametroDTO as $objInfraParametroDTO) {
        $objInfraParametroDTO->setStrValor($parStrNumeroVersao);
        $objInfraParametroBD->alterar($objInfraParametroDTO);
    }
  }

    /**
     * Obt�m id do item de menu, baseado no sistema, r�tulo e id do item superior
     *
     * A mesma fun��o disponibilizada pelas classe ScriptSip, n�o existe a possibilidade de filtra a pesquisa
     * pelo id do item superior, o que pode gerar conflito entre diferentes m�dulos.
     */
  public function obterIdItemMenu($numIdSistema, $numIdMenu, $numIdMenuPai, $strRotulo)
    {
    try {
        $objItemMenuDTO = new ItemMenuDTO();
        $objItemMenuDTO->retNumIdItemMenu();
        $objItemMenuDTO->setNumIdSistema($numIdSistema);
        $objItemMenuDTO->setNumIdMenu($numIdMenu);
        $objItemMenuDTO->setNumIdItemMenuPai($numIdMenuPai);
        $objItemMenuDTO->setStrRotulo($strRotulo);

        $objItemMenuRN = new ItemMenuRN();
        $objItemMenuDTO = $objItemMenuRN->consultar($objItemMenuDTO);
      if ($objItemMenuDTO == null) {
        throw new InfraException('M�dulo do Tramita: Item de menu ' . $strRotulo . ' n�o encontrado.');
      }

        return $objItemMenuDTO->getNumIdItemMenu();
    } catch (Exception $e) {
        throw new InfraException('M�dulo do Tramita: Erro obtendo ID do item de menu.', $e);
    }
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.0
     */
  private function instalarV100()
    {
      $numIdSistema = $this->getNumIdSistema('SEI');
      $numIdMenu = $this->getNumIdMenu('Principal', $numIdSistema);

      //----------------------------------------------------------------------
      // Expedir procedimento
      //----------------------------------------------------------------------
      $this->criarRecurso('pen_procedimento_expedir', 'Expedir Procedimento', $numIdSistema);
      $this->criarRecurso('apensados_selecionar_expedir_procedimento', 'Processos Apensados', $numIdSistema);
      $numIdRecurso = $this->criarRecurso('pen_procedimento_expedido_listar', 'Processos Tr�mitados Externamente', $numIdSistema);
      $this->criarMenu('Processos Tr�mitados Externamente', 55, null, $numIdMenu, $numIdRecurso, $numIdSistema);
      //----------------------------------------------------------------------
      // Mapeamento de documentos enviados
      //----------------------------------------------------------------------
      $this->criarRecurso('pen_map_tipo_documento_envio_visualizar', 'Visualiza��o de mapeamento de documentos enviados', $numIdSistema);

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
        throw new InfraException('M�dulo do Tramita: Menu "Tipo de Documentos" n�o foi localizado');
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

      //Atribui as permiss�es aos recursos e menus
      $this->atribuirPerfil($numIdSistema);

      // ---------- antigo m�todo (instalarV003R003S003IW001) ---------- //
      $objBD = new ItemMenuBD(BancoSip::getInstance());

      // Achar o root
      $numIdSistema = $this->getNumIdSistema('SEI');
      $numIdMenu = $this->getNumIdMenu('Principal', $numIdSistema);

      $objDTO = new ItemMenuDTO();
      $objDTO->setNumIdSistema($numIdSistema);
      $objDTO->setNumIdMenu($numIdMenu);
      $objDTO->setStrRotulo('Administra��o');
      $objDTO->setNumMaxRegistrosRetorno(1);
      $objDTO->retNumIdItemMenu();

      $objDTO = $objBD->consultar($objDTO);

    if (empty($objDTO)) {
        throw new InfraException('M�dulo do Tramita: Menu "Administra��o" n�o foi localizado');
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
          $numIdItemMenuPai = $this->criarMenu('Processo Eletr�nico Nacional', 0, $numIdItemMenuRoot, $numIdMenu, null, $numIdSistema);
          $numIdItemMenuPai = $this->criarMenu('Mapeamento de Tipos de Documento', 10, $numIdItemMenuPai, $numIdMenu, null, $numIdSistema);

        foreach ($arrObjDTO as $objDTO) {
          $objDTO->setNumIdItemMenuPai($numIdItemMenuPai);
          $objBD->alterar($objDTO);
        }

          $objBD->excluir($objItemMenuDTO);
      }
    }

      $this->atualizarNumeroVersao('1.0.0');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.0.1
     */
  private function instalarV101()
    {
      // ---------- antigo m�todo (instalarV006R004S001US039) ---------- //
      $objItemMenuBD = new ItemMenuBD(BancoSip::getInstance());

      $numIdSistema = $this->getNumIdSistema('SEI');
      $numIdMenu = $this->getNumIdMenu('Principal', $numIdSistema);

      $objItemMenuDTO = new ItemMenuDTO();
      $objItemMenuDTO->setNumIdSistema($numIdSistema);
      $objItemMenuDTO->setNumIdMenu($numIdMenu);
      $objItemMenuDTO->setStrRotulo('Processo Eletr�nico Nacional');
      $objItemMenuDTO->setNumMaxRegistrosRetorno(1);
      $objItemMenuDTO->retNumIdItemMenu();

      $objItemMenuDTO = $objItemMenuBD->consultar($objItemMenuDTO);

    if (empty($objItemMenuDTO)) {
        throw new InfraException('M�dulo do Tramita: Menu "Processo Eletr�nico Nacional" n�o foi localizado');
    }

      // Administrao > Mapeamento de Hip�teses Legais de Envio
      $numIdItemMenu = $this->criarMenu('Mapeamento de Hip�teses Legais', 20, $objItemMenuDTO->getNumIdItemMenu(), $numIdMenu, null, $numIdSistema);

      // Administrao > Mapeamento de Hip�teses Legais de Envio > Envio
      $numIdItemMenu = $this->criarMenu('Envio', 10, $numIdItemMenu, $numIdMenu, null, $numIdSistema);

      // Administrao > Mapeamento de Hip�teses Legais de Envio > Envio > Cadastrar
      $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_enviado_alterar', 'Alterar de mapeamento de Hip�teses Legais de Envio', $numIdSistema);
      $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_enviado_cadastrar', 'Cadastro de mapeamento de Hip�teses Legais de Envio', $numIdSistema);
      $this->criarMenu('Cadastrar', 10, $numIdItemMenu, $numIdMenu, $numIdRecurso, $numIdSistema);

      // Administrao > Mapeamento de Hip�teses Legais de Envio > Envio > Listar
      $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_enviado_excluir', 'Excluir mapeamento de Hip�teses Legais de Envio', $numIdSistema);
      $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_enviado_listar', 'Listagem de mapeamento de Hip�teses Legais de Envio', $numIdSistema);
      $this->criarMenu('Listar', 20, $numIdItemMenu, $numIdMenu, $numIdRecurso, $numIdSistema);

      //Atribui as permisses aos recursos e menus
      $this->atribuirPerfil($numIdSistema);


      // ---------- antigo m�todo (instalarV006R004S001US040) ---------- //
      $objBD = new ItemMenuBD(BancoSip::getInstance());

      //----------------------------------------------------------------------
      // Achar o root

      $numIdSistema = $this->getNumIdSistema('SEI');
      $numIdMenu = $this->getNumIdMenu('Principal', $numIdSistema);

      $objDTO = new ItemMenuDTO();
      $objDTO->setNumIdSistema($numIdSistema);
      $objDTO->setNumIdMenu($numIdMenu);
      $objDTO->setStrRotulo('Mapeamento de Hip�teses Legais');
      $objDTO->setNumMaxRegistrosRetorno(1);
      $objDTO->retNumIdItemMenu();

      $objDTO = $objBD->consultar($objDTO);

    if (empty($objDTO)) {
        throw new InfraException('M�dulo do Tramita: Menu "Processo Eletr�nico Nacional" n�o foi localizado');
    }

      // Administrao > Mapeamento de Hip�teses Legais de Envio > Envio
      $numIdItemMenu = $this->criarMenu('Recebimento', 20, $objDTO->getNumIdItemMenu(), $numIdMenu, null, $numIdSistema);

      // Administrao > Mapeamento de Hip�teses Legais de Envio > Envio > Cadastrar
      $this->criarRecurso('pen_map_hipotese_legal_recebido_alterar', 'Altera��o de mapeamento de Hip�teses Legais de Recebimento', $numIdSistema);
      $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_recebido_cadastrar', 'Cadastro de mapeamento de Hip�teses Legais de Recebimento', $numIdSistema);
      $this->criarMenu('Cadastrar', 10, $numIdItemMenu, $numIdMenu, $numIdRecurso, $numIdSistema);

      // Administrao > Mapeamento de Hip�teses Legais de Envio > Envio > Listar
      $this->criarRecurso('pen_map_hipotese_legal_recebido_excluir', 'Exclus�o de mapeamento de Hip�teses Legais de Recebimento', $numIdSistema);
      $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_recebido_listar', 'Listagem de mapeamento de Hip�teses Legais de Recebimento', $numIdSistema);
      $this->criarMenu('Listar', 20, $numIdItemMenu, $numIdMenu, $numIdRecurso, $numIdSistema);

      //Atribui as permisses aos recursos e menus
      $this->atribuirPerfil($numIdSistema);

      // ---------- antigo m�todo (instalarV006R004S001US043) ---------- //
      $objBD = new ItemMenuBD(BancoSip::getInstance());

      $numIdSistema = $this->getNumIdSistema('SEI');
      $numIdMenu = $this->getNumIdMenu('Principal', $numIdSistema);

      $objDTO = new ItemMenuDTO();
      $objDTO->setNumIdSistema($numIdSistema);
      $objDTO->setNumIdMenu($numIdMenu);
      $objDTO->setStrRotulo('Mapeamento de Hip�teses Legais');
      $objDTO->setNumMaxRegistrosRetorno(1);
      $objDTO->retNumIdItemMenu();

      $objDTO = $objBD->consultar($objDTO);

    if (empty($objDTO)) {
        throw new InfraException('M�dulo do Tramita: Menu "Processo Eletr�nico Nacional" n�o foi localizado');
    }

      $numIdRecurso = $this->criarRecurso('pen_map_hipotese_legal_padrao_cadastrar', 'Acesso ao formul�rio de cadastro de mapeamento de Hip�teses Legais Padr�o', $numIdSistema);

      $this->criarMenu('Hip�tese de Restri��o Padr�o', 30, $objDTO->getNumIdItemMenu(), $numIdMenu, $numIdRecurso, $numIdSistema);
      $this->criarRecurso('pen_map_hipotese_legal_padrao', 'M�todo Cadastrar Padr�o da RN de mapeamento de Hip�teses Legais', $numIdSistema);
      $this->atribuirPerfil($numIdSistema);

      $this->atualizarNumeroVersao('1.0.1');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.0.2
     */
  private function instalarV102()
    {

      $objBD = new ItemMenuBD(BancoSip::getInstance());

      //----------------------------------------------------------------------
      // Achar o sistema
      $numIdSistema = $this->getNumIdSistema('SEI');
      $numIdMenu = $this->getNumIdMenu('Principal', $numIdSistema);

      $objDTO = new ItemMenuDTO();
      $objDTO->setNumIdSistema($numIdSistema);
      $objDTO->setNumIdMenu($numIdMenu);
      $objDTO->setStrRotulo('Processo Eletr�nico Nacional');
      $objDTO->setNumMaxRegistrosRetorno(1);
      $objDTO->retNumIdItemMenu();

      $objDTO = $objBD->consultar($objDTO);

    if (empty($objDTO)) {
        throw new InfraException('M�dulo do Tramita: Menu "Processo Eletr�nico Nacional" n�o foi localizado');
    }

      // Administrao > Mapeamento de Hip�teses Legais de Envio > Envio
      $numIdItemMenu = $this->criarMenu('Mapeamento de Unidades', 20, $objDTO->getNumIdItemMenu(), $numIdMenu, null, $numIdSistema);

      // Cadastro do menu de administra��o par�metros
      $numIdRecurso = $this->criarRecurso('pen_parametros_configuracao', 'Parametros de Configura��o', $numIdSistema);
      $this->criarMenu('Par�metros de Configura��o', 20, $objDTO->getNumIdItemMenu(), $numIdMenu, $numIdRecurso, $numIdSistema);

      // Administrao > Mapeamento de Hip�teses Legais de Envio > Envio > Cadastrar
      $this->criarRecurso('pen_map_unidade_alterar', 'Altera��o de mapeamento de Unidades', $numIdSistema);
      $numIdRecurso = $this->criarRecurso('pen_map_unidade_cadastrar', 'Cadastro de mapeamento de Unidades', $numIdSistema);
      $this->criarMenu('Cadastrar', 10, $numIdItemMenu, $numIdMenu, $numIdRecurso, $numIdSistema);

      // Administrao > Mapeamento de Hip�teses Legais de Envio > Envio > Listar
      $this->criarRecurso('pen_map_unidade_excluir', 'Exclus�o de mapeamento de Unidades', $numIdSistema);
      $numIdRecurso = $this->criarRecurso('pen_map_unidade_listar', 'Listagem de mapeamento de Unidades', $numIdSistema);
      $this->criarMenu('Listar', 20, $numIdItemMenu, $numIdMenu, $numIdRecurso, $numIdSistema);


      // ------------------ Atribui as permisses aos recursos e menus ----------------------//
      $this->atribuirPerfil($numIdSistema);

      $this->atualizarNumeroVersao('1.0.2');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.0.3
     */
  private function instalarV103()
    {
      $numIdSistema = $this->getNumIdSistema('SEI');

      //Alterar rotulo do menu
      $objDTO = new ItemMenuDTO();
      $objDTO->setStrRotulo('Indicar Hiptese de Restrio Padro');
      $objDTO->setNumIdSistema($numIdSistema);
      $objDTO->retNumIdItemMenu();
      $objDTO->retNumIdMenu();
      $objBD = new ItemMenuBD($this->getObjInfraIBanco());
      $objDTO = $objBD->consultar($objDTO);
    if ($objDTO) {
        $objDTO->setStrRotulo('Hip�tese de Restri��o Padr�o');
        $objBD->alterar($objDTO);
    }

      //Alterar nomeclatura do recurso
      $objDTO = new RecursoDTO();
      $objDTO->setStrNome('pen_map_hipotese_legal_recebido_listar');
      $objDTO->setNumIdSistema($numIdSistema);
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
      $objDTO->setNumIdSistema($numIdSistema);
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
      $objDTO->setNumIdSistema($numIdSistema);
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
      $objDTO->setNumIdSistema($numIdSistema);
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
      $objDTO->setNumIdSistema($numIdSistema);
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
      $objDTO->setNumIdSistema($numIdSistema);
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
      $objDTO->setNumIdSistema($numIdSistema);
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
      $objDTO->setNumIdSistema($numIdSistema);
      $objDTO->retNumIdRecurso();
      $objBD = new RecursoBD($this->getObjInfraIBanco());
      $objDTO = $objBD->consultar($objDTO);
    if ($objDTO) {
        $objDTO->setStrNome('pen_map_hipotese_legal_envio_alterar');
        $objDTO->setStrCaminho('controlador.php?acao=pen_map_hipotese_legal_envio_alterar');
        $objBD->alterar($objDTO);
    }

      //Cadastrar recurso de altera��o dos par�metros
      $this->criarRecurso('pen_parametros_configuracao_alterar', 'Altera��o de parametros de configura��o do m�dulo Processo Eletr�nico Nacional', $numIdSistema);

      $this->atualizarNumeroVersao('1.0.3');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.0.4
     */
  private function instalarV104()
    {
      $numIdSistema = $this->getNumIdSistema('SEI');

      //Cadastrar recurso Mapeamento dos Tipo de documentos enviados
      $this->criarRecurso('pen_map_tipo_documento_envio_alterar', 'Altera��o de mapeamento de documentos enviados', $numIdSistema);
      $this->criarRecurso('pen_map_tipo_documento_envio_excluir', 'Exclus�o de mapeamento de documentos enviados', $numIdSistema);

      //Cadastrar recurso Mapeamento dos Tipo de documentos recebido
      $this->criarRecurso('pen_map_tipo_documento_recebimento_alterar', 'Altera��o de mapeamento de documentos recebimento', $numIdSistema);
      $this->criarRecurso('pen_map_tipo_documento_recebimento_excluir', 'Exclus�o de mapeamento de documentos recebimento', $numIdSistema);
      $this->criarRecurso('pen_map_tipo_documento_recebimento_visualizar', 'Visualiza��o de mapeamento de documentos recebimento', $numIdSistema);

      //Alterar nomeclatura do recurso (recebido)
      $objDTO = new RecursoDTO();
      $objDTO->setStrNome('pen_map_tipo_doc_recebido_cadastrar');
      $objDTO->setNumIdSistema($numIdSistema);
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
      $objDTO->setNumIdSistema($numIdSistema);
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
      $objDTO->setNumIdSistema($numIdSistema);
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
      $objDTO->setNumIdSistema($numIdSistema);
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
      $objDTO->setNumIdSistema($numIdSistema);
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
     * Instala/Atualiza os m�dulo PEN para vers�o 1.1.1
     */
  private function instalarV111()
    {
      $numIdSistema = $this->getNumIdSistema('SEI');

      //Ajuste em nome da vari�vel de vers�o do m�dulo VERSAO_MODULO_PEN
      BancoSIP::getInstance()->executarSql("update infra_parametro set nome = '" . self::PARAMETRO_VERSAO_MODULO . "' where nome = '" . self::PARAMETRO_VERSAO_MODULO_ANTIGO . "'");

      //Adequa��o em nome de recursos do m�dulo
      $this->renomearRecurso($numIdSistema, 'apensados_selecionar_expedir_procedimento', 'pen_apensados_selecionar_expedir_procedimento');

      //Atualiza��o com recursos n�o adicionados automaticamente em vers�es anteriores
      $this->arrRecurso = array_merge($this->arrRecurso, [$this->consultarRecurso($numIdSistema, "pen_map_tipo_documento_envio_alterar"), $this->consultarRecurso($numIdSistema, "pen_map_tipo_documento_envio_excluir"), $this->consultarRecurso($numIdSistema, "pen_map_tipo_documento_recebimento_alterar"), $this->consultarRecurso($numIdSistema, "pen_map_tipo_documento_recebimento_excluir"), $this->consultarRecurso($numIdSistema, "pen_map_tipo_documento_recebimento_visualizar"), $this->consultarRecurso($numIdSistema, "pen_parametros_configuracao_alterar")]);

      $this->atribuirPerfil($numIdSistema);

      $objPerfilRN = new PerfilRN();
      $objPerfilDTO = new PerfilDTO();
      $objPerfilDTO->retNumIdPerfil();
      $objPerfilDTO->setNumIdSistema($numIdSistema);
      $objPerfilDTO->setStrNome('Administrador');
      $objPerfilDTO = $objPerfilRN->consultar($objPerfilDTO);
    if ($objPerfilDTO == null) {
        throw new InfraException('M�dulo do Tramita: Perfil Administrador do sistema SEI n�o encontrado.');
    }

      $numIdPerfilSeiAdministrador = $objPerfilDTO->getNumIdPerfil();

      $objRelPerfilRecursoDTO = new RelPerfilRecursoDTO();
      $objRelPerfilRecursoDTO->retTodos();
      $objRelPerfilRecursoDTO->setNumIdSistema($numIdSistema);
      $objRelPerfilRecursoDTO->setNumIdPerfil($numIdPerfilSeiAdministrador);
      $arrRecursosRemoverAdministrador = [$this->consultarRecurso($numIdSistema, "pen_procedimento_expedido_listar"), $this->consultarRecurso($numIdSistema, "pen_procedimento_expedir")];
      $objRelPerfilRecursoDTO->setNumIdRecurso($arrRecursosRemoverAdministrador, InfraDTO::$OPER_IN);
      $objRelPerfilRecursoRN = new RelPerfilRecursoRN();
      $objRelPerfilRecursoRN->excluir($objRelPerfilRecursoRN->listar($objRelPerfilRecursoDTO));

      $this->atualizarNumeroVersao('1.1.1');
  }


    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.1.9
     */
  private function instalarV119()
    {
      /* Corrige nome de menu de tr�mite de documentos */
      $numIdSistema = $this->getNumIdSistema('SEI');
      $this->getNumIdMenu('Principal', $numIdSistema);

      //Corrige nome do recurso
      $objRecursoDTO = new RecursoDTO();
      $objRecursoDTO->setNumIdSistema($numIdSistema);
      $objRecursoDTO->setStrNome('pen_procedimento_expedido_listar');
      $objRecursoDTO->retNumIdRecurso();
      $objRecursoBD = new RecursoBD($this->getObjInfraIBanco());
      $objRecursoDTO = $objRecursoBD->consultar($objRecursoDTO);
    if (isset($objRecursoDTO)) {
        $numIdRecurso = $objRecursoDTO->getNumIdRecurso();
        $objRecursoDTO->setStrDescricao('Processos Tramitados Externamente');
        $objRecursoBD->alterar($objRecursoDTO);
    }

      $objItemMenuDTO = new ItemMenuDTO();
      $objItemMenuDTO->setNumIdItemMenuPai(null);
      $objItemMenuDTO->setNumIdSistema($numIdSistema);
      $objItemMenuDTO->setNumIdRecurso($numIdRecurso);
      $objItemMenuDTO->setStrRotulo('Processos Tr�mitados Externamente');
      $objItemMenuDTO->retNumIdMenu();
      $objItemMenuDTO->retNumIdItemMenu();
      $objItemMenuBD = new ItemMenuBD(BancoSip::getInstance());
      $objItemMenuDTO = $objItemMenuBD->consultar($objItemMenuDTO);
    if (isset($objItemMenuDTO)) {
        $objItemMenuDTO->setStrDescricao('Processos Tramitados Externamente');
        $objItemMenuDTO->setStrRotulo('Processos Tramitados Externamente');
        $objItemMenuBD->alterar($objItemMenuDTO);
    }


      $this->atualizarNumeroVersao('1.1.9');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.1.10
     */
  private function instalarV1110()
    {
      $this->atualizarNumeroVersao('1.1.10');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.1.11
     */
  private function instalarV1111()
    {
      $this->atualizarNumeroVersao('1.1.11');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.1.12
     */
  private function instalarV1112()
    {
      $this->atualizarNumeroVersao('1.1.12');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.1.13
     */
  private function instalarV1113()
    {
      $this->atualizarNumeroVersao('1.1.13');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.1.14
     */
  private function instalarV1114()
    {
      $this->atualizarNumeroVersao('1.1.14');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.1.15
     */
  private function instalarV1115()
    {
      $this->atualizarNumeroVersao('1.1.15');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.1.16
     */
  private function instalarV1116()
    {
      $this->atualizarNumeroVersao('1.1.16');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.1.17
     */
  private function instalarV1117()
    {
      $this->atualizarNumeroVersao('1.1.17');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.2.0
     */
  private function instalarV1200()
    {
      $this->atualizarNumeroVersao('1.2.0');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.2.1
     */
  private function instalarV1201()
    {
      $this->atualizarNumeroVersao('1.2.1');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.2.2
     */
  private function instalarV1202()
    {
      $this->atualizarNumeroVersao('1.2.2');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.2.3
     */
  private function instalarV1203()
    {
      $this->atualizarNumeroVersao('1.2.3');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.2.4
     */
  private function instalarV1204()
    {
      $this->atualizarNumeroVersao('1.2.4');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.2.5
     */
  private function instalarV1205()
    {
      $this->atualizarNumeroVersao('1.2.5');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.2.6
     */
  private function instalarV1206()
    {
      $this->atualizarNumeroVersao('1.2.6');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.3.0
     */
  private function instalarV1300()
    {
      $this->atualizarNumeroVersao('1.3.0');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.4.0
     */
  private function instalarV1400()
    {
      $this->atualizarNumeroVersao('1.4.0');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.4.1
     */
  private function instalarV1401()
    {
      $this->atualizarNumeroVersao('1.4.1');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.4.2
     */
  private function instalarV1402()
    {
      $this->atualizarNumeroVersao('1.4.2');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.4.3
     */
  private function instalarV1403()
    {
      $this->atualizarNumeroVersao('1.4.3');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.5.0
     */
  private function instalarV1500()
    {
      $this->atualizarNumeroVersao('1.5.0');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.5.1
     */
  private function instalarV1501()
    {
      $this->atualizarNumeroVersao('1.5.1');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.5.2
     */
  private function instalarV1502()
    {
      $this->atualizarNumeroVersao('1.5.2');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 1.5.3
     */
  private function instalarV1503()
    {
      $this->atualizarNumeroVersao('1.5.3');
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 2.0.0
     */
  // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
  private function instalarV2000_beta1()
    {
      // Criar novos recursos de configura��o de esp�cie documental padr�o para envio de processos
      $this->logar('ATRIBUI��O DE PERMISS�O DE ATRIBU���O DE ESP�CIES/TIPO DE DOCUMENTO PADR�O AO PERFIL ADMINISTRADOR');
      $numIdSistemaSei = $this->getNumIdSistema('SEI');
      $numIdPerfilSeiAdministrador = ScriptSip::obterIdPerfil($numIdSistemaSei, "Administrador");
      $this->criarRecurso('pen_map_tipo_documento_envio_padrao_atribuir', 'Atribuir esp�cie documental padr�o para envio de processos', $numIdSistemaSei);
      $this->criarRecurso('pen_map_tipo_documento_envio_padrao_consultar', 'Consultar esp�cie documental padr�o para envio de processos', $numIdSistemaSei);
      $this->criarRecurso('pen_map_tipo_doc_recebimento_padrao_atribuir', 'Atribuir tipo de documento padr�o para recebimento de processos', $numIdSistemaSei);
      $this->criarRecurso('pen_map_tipo_doc_recebimento_padrao_consultar', 'Consultar tipo de documento padr�o para recebimento de processos', $numIdSistemaSei);
      ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, 'pen_map_tipo_documento_envio_padrao_atribuir');
      ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, 'pen_map_tipo_documento_envio_padrao_consultar');
      ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, 'pen_map_tipo_doc_recebimento_padrao_atribuir');
      ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, 'pen_map_tipo_doc_recebimento_padrao_consultar');

      $this->logar('RECONFIGURA��O DE MENUS DE FUNCIONALIDADES DE MAPEAMENTO DE ESP�CIES DOCUMENTAIS DO Processo Eletr�nico Nacional');
      $numIdPerfilSeiAdministrador = ScriptSip::obterIdPerfil($numIdSistemaSei, "Administrador");
      $numIdMenuSEI = ScriptSip::obterIdMenu($numIdSistemaSei, 'Principal');

    try {
        // Remove item de menu anterior e seus submenus configurados de forma errada
        $numIdItemMenuMapTipDoc = ScriptSip::obterIdItemMenu($numIdSistemaSei, $numIdMenuSEI, 'Mapeamento de Tipos de Documento');
        ScriptSip::removerItemMenu($numIdSistemaSei, $numIdMenuSEI, $numIdItemMenuMapTipDoc);
    } catch (\Exception $e) {
        $this->logar("Item de menu de mapeamento de tipos de documentos n�o pode ser localizado");
    }

      // Recriar item de menu agrupador de mapeamento de tipos de documento
      $numIdItemMenuPEN = ScriptSip::obterIdItemMenu($numIdSistemaSei, $numIdMenuSEI, "Processo Eletr�nico Nacional");
      $objItemMenuMapeamentoDTO = ScriptSip::adicionarItemMenu(
          $numIdSistemaSei,
          $numIdPerfilSeiAdministrador,
          $numIdMenuSEI,
          $numIdItemMenuPEN,
          null,
          "Mapeamento de Tipos de Documentos",
          20
      );

      // Recriar item de menu de mapeamento de envio de documentos, acionando o recurso listar correspondente
      $numIdItemMenuMapeamento = $objItemMenuMapeamentoDTO->getNumIdItemMenu();
      $objRecursoMapEnvioListar = ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, "pen_map_tipo_documento_envio_listar");
      $numIdRecursoMapEnvioListar = $objRecursoMapEnvioListar->getNumIdRecurso();
      ScriptSip::adicionarItemMenu($numIdSistemaSei, $numIdPerfilSeiAdministrador, $numIdMenuSEI, $numIdItemMenuMapeamento, $numIdRecursoMapEnvioListar, "Envio", 10);

      // Recriar item de menu de mapeamento de recebimento de documentos, acionando o recurso listar correspondente
      $objRecursoMapRecebimentoListar = ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, "pen_map_tipo_documento_recebimento_listar");
      $numIdRecursoMapRecebimentoListar = $objRecursoMapRecebimentoListar->getNumIdRecurso();
      ScriptSip::adicionarItemMenu($numIdSistemaSei, $numIdPerfilSeiAdministrador, $numIdMenuSEI, $numIdItemMenuMapeamento, $numIdRecursoMapRecebimentoListar, "Recebimento", 20);

      // Redefinir ordem de apresenta��o dos menus de administra��o do m�dulo
      $arrOrdemMenusAdministracaoPEN = [["rotulo" => "Par�metros de Configura��o", "sequencia" => 10, "rotuloMenuSuperior" => "Processo Eletr�nico Nacional"], ["rotulo" => "Mapeamento de Tipos de Documentos", "sequencia" => 20, "rotuloMenuSuperior" => "Processo Eletr�nico Nacional"], ["rotulo" => "Mapeamento de Unidades", "sequencia" => 30, "rotuloMenuSuperior" => "Processo Eletr�nico Nacional"], ["rotulo" => "Mapeamento de Hip�teses Legais", "sequencia" => 40, "rotuloMenuSuperior" => "Processo Eletr�nico Nacional"]];

      array_map(
          function ($item) use ($numIdSistemaSei, $numIdMenuSEI): void {
              $objItemMenuRN = new ItemMenuRN();
              $numIdItemMenuPai = ScriptSip::obterIdItemMenu($numIdSistemaSei, $numIdMenuSEI, $item["rotuloMenuSuperior"]);

              // Obt�m id do item de menu, baseado no sistema, r�tulo e, principalmente, ID DO ITEM SUPERIOR
              $numIdItemMenu = $this->obterIdItemMenu($numIdSistemaSei, $numIdMenuSEI, $numIdItemMenuPai, $item["rotulo"]);
            if (isset($numIdItemMenu)) {
                  $objItemMenuDTO = new ItemMenuDTO();
                  $objItemMenuDTO->setNumIdMenu($numIdMenuSEI);
                  $objItemMenuDTO->setNumIdItemMenu($numIdItemMenu);
                  $objItemMenuDTO->setNumIdItemMenuPai($numIdItemMenuPai);
                  $objItemMenuDTO->setNumSequencia($item["sequencia"]);
                  $objItemMenuRN->alterar($objItemMenuDTO);
            }
          }, $arrOrdemMenusAdministracaoPEN
      );


      $this->logar('Atribui��o de permiss�es do m�dulo ao perfil B�sico do SEI');
      $strNomeMenuProcessosTramitados = "Processos Tramitados Externamente";
      $numIdSistemaSei = ScriptSip::obterIdSistema('SEI');
      $numIdPerfilSeiBasico = ScriptSip::obterIdPerfil($numIdSistemaSei, "B�sico");
      $numIdMenuSei = ScriptSip::obterIdMenu($numIdSistemaSei, 'Principal');

      // Remove item de menu e adiciona-o novamente para cri�-lo seguindo o padr�o definido na rotina adicionarItemMenu
      ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiBasico, 'pen_procedimento_expedir');
      $objRecursoDTO = ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiBasico, 'pen_procedimento_expedido_listar');
      $numIdMenuProcessoTramitados = ScriptSip::obterIdItemMenu($numIdSistemaSei, $numIdMenuSei, $strNomeMenuProcessosTramitados);
      ScriptSip::removerItemMenu($numIdSistemaSei, $numIdMenuSei, $numIdMenuProcessoTramitados);
      ScriptSip::adicionarItemMenu($numIdSistemaSei, $numIdPerfilSeiBasico, $numIdMenuSei, null, $objRecursoDTO->getNumIdRecurso(), $strNomeMenuProcessosTramitados, 55);

      $this->atualizarNumeroVersao("2.0.0-beta1");
  }


    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 2.0.0-beta2
     */
  // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
  protected function instalarV2000_beta2()
    {
      $this->atualizarNumeroVersao("2.0.0-beta2");
  }


    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 2.0.0-beta3
     */
  // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
  protected function instalarV2000_beta3()
    {
      $this->atualizarNumeroVersao("2.0.0-beta3");
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 2.0.0-beta4
     */
  // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
  protected function instalarV2000_beta4()
    {
      $this->atualizarNumeroVersao("2.0.0-beta4");
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 2.0.0-beta5
     */
  // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
  protected function instalarV2000_beta5()
    {
      $this->atualizarNumeroVersao("2.0.0-beta5");
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 2.0.0
     */
  protected function instalarV2000()
    {
      $this->atualizarNumeroVersao("2.0.0");
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 2.0.1
     */
  protected function instalarV2001()
    {
      $this->atualizarNumeroVersao("2.0.1");
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 2.1.0
     */
  protected function instalarV2100()
    {
      $this->atualizarNumeroVersao("2.1.0");
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 2.0.2
     */
  protected function instalarV2101()
    {
      // Adi��o de recursos relacionados � consulta de mapeamento de hip�teses legais de envio e recebimento
      $numIdSistemaSei = $this->getNumIdSistema('SEI');
      $numIdPerfilSeiAdministrador = ScriptSip::obterIdPerfil($numIdSistemaSei, "Administrador");
      $this->criarRecurso('pen_map_hipotese_legal_recebimento_consultar', 'Consulta de mapeamento de Hip�teses Legais de Recebimento', $numIdSistemaSei);
      ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, 'pen_map_hipotese_legal_recebimento_consultar');
      $this->criarRecurso('pen_map_hipotese_legal_envio_consultar', 'Consulta de mapeamento de Hip�teses Legais de Envio', $numIdSistemaSei);
      ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, 'pen_map_hipotese_legal_envio_consultar');
      $this->atualizarNumeroVersao("2.1.1");
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 2.1.2
     */
  protected function instalarV2102()
    {
      $this->atualizarNumeroVersao("2.1.2");
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 2.1.3
     */
  protected function instalarV2103()
    {
      $this->atualizarNumeroVersao("2.1.3");
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 2.1.4
     */
  protected function instalarV2104()
    {
      $this->atualizarNumeroVersao("2.1.4");
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 2.1.5
     */
  protected function instalarV2105()
    {
      $this->atualizarNumeroVersao("2.1.5");
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 2.1.6
     */
  protected function instalarV2106()
    {
      $this->atualizarNumeroVersao("2.1.6");
  }

    /**
     * Instala/Atualiza os m�dulo PEN para vers�o 2.1.6
     */
  protected function instalarV2107()
    {
      $this->atualizarNumeroVersao("2.1.7");
  }

  protected function instalarV3000()
    {
      $this->atualizarNumeroVersao("3.0.0");
  }
  protected function instalarV3001()
    {
      $this->atualizarNumeroVersao("3.0.1");
  }
  protected function instalarV3010()
    {
      $this->logar('Atribui��o de permiss�es do m�dulo ao perfil B�sico do SEI');
      $numIdSistema = $this->getNumIdSistema('SEI');
      $numIdMenu = $this->getNumIdMenu('Principal', $numIdSistema);

      $numIdPerfilSeiAdministrador = ScriptSip::obterIdPerfil($numIdSistema, "Administrador");
      $this->criarRecurso('pen_expedir_lote', 'Expedir Procedimento em Lote', $numIdSistema);
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $numIdPerfilSeiAdministrador, 'pen_expedir_lote');

      $numIdRecurso = $this->criarRecurso('pen_expedir_lote_listar', 'Listar Processos Tramitados em Lote', $numIdSistema);
      ScriptSip::adicionarItemMenu($numIdSistema, $numIdPerfilSeiAdministrador, $numIdMenu, null, $numIdRecurso, "Processos Tramitados em Lote", 55);

      $this->atualizarNumeroVersao("3.1.0");
  }
  protected function instalarV3011()
    {
      $this->atualizarNumeroVersao("3.1.1");
  }

  protected function instalarV3012()
    {
      $this->atualizarNumeroVersao("3.1.2");
  }

  protected function instalarV3013()
    {
      $this->atualizarNumeroVersao("3.1.3");
  }

  protected function instalarV3014()
    {
      $this->atualizarNumeroVersao("3.1.4");
  }

  protected function instalarV3015()
    {
      $this->atualizarNumeroVersao("3.1.5");
  }

  protected function instalarV3016()
    {
      $this->atualizarNumeroVersao("3.1.6");
  }

  protected function instalarV3017()
    {
      $this->atualizarNumeroVersao("3.1.7");
  }

  protected function instalarV3018()
    {
      $this->atualizarNumeroVersao("3.1.8");
  }

  protected function instalarV3019()
    {
      $this->atualizarNumeroVersao("3.1.9");
  }

  protected function instalarV30110()
    {
      $this->atualizarNumeroVersao("3.1.10");
  }

  protected function instalarV30111()
    {
      $this->atualizarNumeroVersao("3.1.11");
  }

  protected function instalarV30112()
    {
      $this->atualizarNumeroVersao("3.1.12");
  }

  protected function instalarV30113()
    {
      $atualizarIconeMenu = function ($numIdSistema, $numIdMenuPai, $strNomeRecurso, $strIcone, $numSequencia): void {
          $objRecursoDTO = new RecursoDTO();
          $objRecursoDTO->setNumIdSistema($numIdSistema);
          $objRecursoDTO->setStrNome($strNomeRecurso);
          $objRecursoDTO->retNumIdRecurso();
          $objRecursoBD = new RecursoBD(BancoSip::getInstance());
          $objRecursoDTO = $objRecursoBD->consultar($objRecursoDTO);

        if (isset($objRecursoDTO)) {
            $numIdRecurso = $objRecursoDTO->getNumIdRecurso();
            $objItemMenuDTO = new ItemMenuDTO();
            $objItemMenuDTO->setNumIdSistema($numIdSistema);
            $objItemMenuDTO->setNumIdRecurso($numIdRecurso);
            $objItemMenuDTO->setNumIdItemMenuPai(null);
            $objItemMenuDTO->retNumIdMenu();
            $objItemMenuDTO->retNumIdItemMenu();

            $objItemMenuBD = new ItemMenuBD(BancoSip::getInstance());
            $arrObjItemMenuDTO = $objItemMenuBD->listar($objItemMenuDTO);
          if (isset($arrObjItemMenuDTO)) {
            foreach ($arrObjItemMenuDTO as $objItemMenuDTO) {
                $objItemMenuDTO->setStrIcone($strIcone);
                $objItemMenuDTO->setNumSequencia($numSequencia);
                $objItemMenuBD->alterar($objItemMenuDTO);
            }
          }
        }
      };

      // A partir da vers�o 3.0.0 � que o SIP passa a dar suporte � �cones
    if (InfraUtil::compararVersoes(SIP_VERSAO, ">=", "3.0.0")) {
        $numIdSistema = $this->getNumIdSistema('SEI');
        $numIdMenuPai = $this->getNumIdMenu('Principal', $numIdSistema);

        $atualizarIconeMenu($numIdSistema, $numIdMenuPai, 'pen_procedimento_expedido_listar', 'pen_tramite_externo.svg', 55);
        $atualizarIconeMenu($numIdSistema, $numIdMenuPai, 'pen_expedir_lote_listar', 'pen_tramite_externo_lote.svg', 56);
    }

      $this->atualizarNumeroVersao("3.1.13");
  }

  protected function instalarV30114()
    {
      $this->atualizarNumeroVersao("3.1.14");
  }

  protected function instalarV30115()
    {
      $this->atualizarNumeroVersao("3.1.15");
  }

  protected function instalarV30116()
    {
      $this->atualizarNumeroVersao("3.1.16");
  }

  protected function instalarV30117()
    {
      $this->atualizarNumeroVersao("3.1.17");
  }

  protected function instalarV30118()
    {
      $this->atualizarNumeroVersao("3.1.18");
  }

  protected function instalarV30119()
    {
      $this->atualizarNumeroVersao("3.1.19");
  }

  protected function instalarV30120()
    {
      $this->atualizarNumeroVersao("3.1.20");
  }

  protected function instalarV30121()
    {
      $this->atualizarNumeroVersao("3.1.21");
  }

  protected function instalarV30122()
    {
      $this->atualizarNumeroVersao("3.1.22");
  }

  protected function instalarV3020()
    {
      $this->atualizarNumeroVersao("3.2.0");
  }

  protected function instalarV3021()
    {
      $this->atualizarNumeroVersao("3.2.1");
  }

  protected function instalarV3022()
    {
      $this->atualizarNumeroVersao("3.2.2");
  }

  protected function instalarV3023()
    {
      $this->atualizarNumeroVersao("3.2.3");
  }

  protected function instalarV3024()
    {
      $this->atualizarNumeroVersao("3.2.4");
  }

  protected function instalarV3030()
    {
      $this->atualizarNumeroVersao("3.3.0");
  }

  protected function instalarV3031()
    {
      $this->atualizarNumeroVersao("3.3.1");
  }

  protected function instalarV3032()
    {
      $this->atualizarNumeroVersao("3.3.2");
  }

  protected function instalarV3040()
    {
      $this->atualizarNumeroVersao("3.4.0");
  }

  protected function instalarV3050()
    {
      /* Corrige nome de menu de tr�mite de documentos */
      $objItemMenuBD = new ItemMenuBD(BancoSip::getInstance());

      $numIdSistema = $this->getNumIdSistema('SEI');
      $numIdMenu = $this->getNumIdMenu('Principal', $numIdSistema);

      $objItemMenuDTO = new ItemMenuDTO();
      $objItemMenuDTO->setNumIdSistema($numIdSistema);
      $objItemMenuDTO->setNumIdMenu($numIdMenu);
      $objItemMenuDTO->setStrRotulo('Processo Eletr�nico Nacional');
      $objItemMenuDTO->setNumMaxRegistrosRetorno(1);
      $objItemMenuDTO->retNumIdItemMenu();

      $objItemMenuDTO = $objItemMenuBD->consultar($objItemMenuDTO);

    if (empty($objItemMenuDTO)) {
        throw new InfraException('M�dulo do Tramita: Menu "Processo Eletr�nico Nacional" n�o foi localizado');
    }

      // Adicionar submenu
      $this->logar('Atribui��o de permiss�es do m�dulo ao perfil do SEI');

      $this->criarRecurso('pen_map_orgaos_externos_salvar', 'Salvar relacionamento entre unidades', $numIdSistema);
      $this->criarRecurso('pen_map_orgaos_externos_excluir', 'Excluir relacionamento entre unidades', $numIdSistema);
      $this->criarRecurso('pen_map_orgaos_externos_cadastrar', 'Cadastro de relacionamento entre unidades', $numIdSistema);
      $this->criarRecurso('pen_map_orgaos_externos_desativar', 'Desativar relacionamento entre unidades', $numIdSistema);
      $this->criarRecurso('pen_map_orgaos_externos_reativar', 'Reativar relacionamento entre unidades', $numIdSistema);
      $this->criarRecurso('pen_map_orgaos_externos_mapeamento', 'Mapeamento de tipo de processo', $numIdSistema);
      $this->criarRecurso('pen_map_orgaos_externos_mapeamento_excluir', 'Excluir mapeamento de tipo de processo', $numIdSistema);
      $this->criarRecurso('pen_map_orgaos_externos_atualizar', 'Atualizar relacionamento entre unidades', $numIdSistema);
      $this->criarRecurso('pen_map_orgaos_externos_visualizar', 'Visualizar relacionamento entre unidades', $numIdSistema);
      $this->criarRecurso('pen_map_orgaos_importar_tipos_processos', 'Importar tipos de processo', $numIdSistema);
      $this->criarRecurso('pen_map_tipo_processo_padrao', 'Consultar tipo de processo padr�o', $numIdSistema);
      $this->criarRecurso('pen_map_tipo_processo_padrao_salvar', 'Cadastrar tipo de processo padr�o', $numIdSistema);

      $numIdRecursoListar = $this->criarRecurso('pen_map_orgaos_externos_listar', 'Listagem de relacionamento entre unidades', $numIdSistema);
      $numIdRecursoExportar = $this->criarRecurso('pen_map_orgaos_exportar_tipos_processos', 'Exportar tipos de processo', $numIdSistema);
      $numIdRecursoReativar = $this->criarRecurso('pen_map_tipo_processo_reativar', 'Reativar mapeamento de tipo de processo', $numIdSistema);

      $numIdPerfilSeiAdministrador = ScriptSip::obterIdPerfil($numIdSistema, "Administrador");
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $numIdPerfilSeiAdministrador, 'pen_map_orgaos_externos_listar');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $numIdPerfilSeiAdministrador, 'pen_map_orgaos_externos_salvar');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $numIdPerfilSeiAdministrador, 'pen_map_orgaos_externos_excluir');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $numIdPerfilSeiAdministrador, 'pen_map_orgaos_externos_cadastrar');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $numIdPerfilSeiAdministrador, 'pen_map_orgaos_externos_atualizar');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $numIdPerfilSeiAdministrador, 'pen_map_orgaos_externos_visualizar');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $numIdPerfilSeiAdministrador, 'pen_map_orgaos_externos_desativar');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $numIdPerfilSeiAdministrador, 'pen_map_orgaos_externos_reativar');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $numIdPerfilSeiAdministrador, 'pen_map_orgaos_importar_tipos_processos');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $numIdPerfilSeiAdministrador, 'pen_map_orgaos_exportar_tipos_processos');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $numIdPerfilSeiAdministrador, 'pen_map_orgaos_externos_mapeamento');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $numIdPerfilSeiAdministrador, 'pen_map_orgaos_externos_mapeamento_excluir'); 
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $numIdPerfilSeiAdministrador, 'pen_map_tipo_processo_padrao');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $numIdPerfilSeiAdministrador, 'pen_map_tipo_processo_padrao_salvar');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $numIdPerfilSeiAdministrador, 'pen_map_tipo_processo_reativar');

      // Administrao > Processo Eletr�nico Nacional > Mapeamento de Tipos de Processo
      $numIdItemMenu = $this->criarMenu('Mapeamento de Tipos de Processo', 40, $objItemMenuDTO->getNumIdItemMenu(), $numIdMenu, null, $numIdSistema);

      // Administrao > Processo Eletr�nico Nacional > �rg�os Externos > Listar
      $numIdItemMenuRecuso = $this->criarMenu('Relacionamento entre Unidades', 20, $numIdItemMenu, $numIdMenu, $numIdRecursoListar, $numIdSistema);
      $this->cadastrarRelPerfilItemMenu($numIdPerfilSeiAdministrador, $numIdRecursoListar, $numIdMenu, $numIdItemMenuRecuso);

      // Administrao > Processo Eletr�nico Nacional > �rg�os Externos > Exportar Tipo de Processo
      $numIdItemMenuRecuso = $this->criarMenu('Exporta��o de Tipos de Processo', 21, $numIdItemMenu, $numIdMenu, $numIdRecursoExportar, $numIdSistema);
      $this->cadastrarRelPerfilItemMenu($numIdPerfilSeiAdministrador, $numIdRecursoExportar, $numIdMenu, $numIdItemMenuRecuso);

      // Administrao > Processo Eletr�nico Nacional > �rg�os Externos > Reativar Tipo de Processo
      $numIdItemMenuRecuso = $this->criarMenu('Reativar Mapeamento de Tipos de Processo', 22, $numIdItemMenu, $numIdMenu, $numIdRecursoReativar, $numIdSistema);
      $this->cadastrarRelPerfilItemMenu($numIdPerfilSeiAdministrador, $numIdRecursoReativar, $numIdMenu, $numIdItemMenuRecuso);

      // Nova vers�o
      $this->atualizarNumeroVersao("3.5.0");
  }

  protected function instalarV3060()
    {

      $numIdSistema = $this->getNumIdSistema('SEI');
      $numIdMenu = $this->getNumIdMenu('Principal', $numIdSistema);

      $objItemMenuDTO = new ItemMenuDTO();
      $objItemMenuDTO->setNumIdMenu($numIdMenu);
      $objItemMenuDTO->setNumIdSistema($numIdSistema);
      $objItemMenuDTO->setStrRotulo('Processo Eletr�nico Nacional');
      $objItemMenuDTO->retNumIdMenu();
      $objItemMenuDTO->retNumIdItemMenu();
      $objItemMenuBD = new ItemMenuBD(BancoSip::getInstance());
      $objItemMenuDTO = $objItemMenuBD->consultar($objItemMenuDTO);

      $objItemMenuDTO->setStrRotulo('Tramita GOV.BR');
      $objItemMenuDTO = $objItemMenuBD->alterar($objItemMenuDTO);

      // adicionar permiss�o
      $idPerfilAdm = ScriptSip::obterIdPerfil($numIdSistema, "Administrador");
      $idPerfilBasico = ScriptSip::obterIdPerfil($numIdSistema, "B�sico");

      // Adicionar menu
      $this->logar('Atribui��o de permiss�es do m�dulo ao perfil do SEI');

    try {
        // Remove item de menu anterior e seus submenus configurados de forma errada
        $numIdItemMenuMapTipDoc = ScriptSip::obterIdItemMenu($numIdSistema, $numIdMenu, 'Processos Tramitados Externamente');
        ScriptSip::removerItemMenu($numIdSistema, $numIdMenu, $numIdItemMenuMapTipDoc);

        $numIdItemMenuMapTipDoc = ScriptSip::obterIdItemMenu($numIdSistema, $numIdMenu, 'Processos Tramitados em Lote');
        ScriptSip::removerItemMenu($numIdSistema, $numIdMenu, $numIdItemMenuMapTipDoc);
    } catch (\Exception $e) {
        $this->logar("Item de menu de mapeamento de tipos de documentos n�o pode ser localizado");
    }

      //----------------------------------------------------------------------
      // Tramita.GOV.BR
      //----------------------------------------------------------------------
      $numIdRecurso1 = $this->criarRecurso('pen_procedimento_expedido_listar', 'Tramita GOV.BR', $numIdSistema);
      $numIdRecurso2 = $this->criarRecurso('md_pen_tramita_em_bloco', 'Blocos de Tr�mite Externo', $numIdSistema);
      $numIdRecurso3 = $this->criarRecurso('pen_procedimento_expedido_listar', 'Processos Tramitados Externamente', $numIdSistema);
      $this->criarRecurso('pen_expedir_lote_listar', 'Processos Tramitados em Bloco', $numIdSistema);
    
      $this->criarRecurso('md_pen_tramita_em_bloco_cadastrar', 'Cadastrar Bloco de Tramite Externo', $numIdSistema);
      $this->criarRecurso('md_pen_tramita_em_bloco_alterar', 'Alterar Descri��o do bloco de Tramite Externo', $numIdSistema);
      $this->criarRecurso('md_pen_tramita_em_bloco_excluir', 'Excluir processos do bloco de Tramite Externo', $numIdSistema);
      $this->criarRecurso('pen_tramite_em_bloco_consultar', 'Alterar Descri��o do bloco de Tramite Externo', $numIdSistema);
      $this->criarRecurso('pen_tramita_em_bloco_protocolo_listar', 'Listar Processos do bloco de Tramite Externo', $numIdSistema);
      $this->criarRecurso('pen_tramita_em_bloco_protocolo_excluir', 'Excluir processos do bloco de Tramite Externo', $numIdSistema);
      $this->criarRecurso('pen_tramita_em_bloco_protocolo_cancelar', 'Cancelar processos do bloco de Tramite Externo', $numIdSistema);

      ScriptSip::adicionarRecursoPerfil($numIdSistema, $idPerfilAdm, 'pen_procedimento_expedido_listar');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $idPerfilAdm, 'md_pen_tramita_em_bloco');

      ScriptSip::adicionarRecursoPerfil($numIdSistema, $idPerfilAdm, 'pen_expedir_lote_listar');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $idPerfilBasico, 'md_pen_tramita_em_bloco');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $idPerfilBasico, 'md_pen_tramita_em_bloco_excluir');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $idPerfilBasico, 'pen_tramite_em_bloco_cadastrar');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $idPerfilBasico, 'pen_tramite_em_bloco_alterar');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $idPerfilBasico, 'pen_tramite_em_bloco_consultar');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $idPerfilBasico, 'pen_tramite_em_bloco_cancelar');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $idPerfilBasico, 'pen_tramita_em_bloco_protocolo_listar');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $idPerfilBasico, 'pen_tramita_em_bloco_protocolo_excluir');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $idPerfilBasico, 'pen_tramita_em_bloco_protocolo_cancelar');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $idPerfilBasico, 'pen_incluir_processo_em_bloco_tramite');

      $idMenuTramita = $this->criarMenu('Tramita GOV.BR', 55, null, $numIdMenu, $numIdRecurso1, $numIdSistema);
      $this->cadastrarRelPerfilItemMenu($idPerfilAdm, $numIdRecurso1, $numIdMenu, $idMenuTramita);

      $idMenuBlocoTramiteExterno = $this->criarMenu('Blocos de Tr�mite Externo', 56, $idMenuTramita, $numIdMenu, $numIdRecurso2, $numIdSistema);
      $this->cadastrarRelPerfilItemMenu($idPerfilAdm, $numIdRecurso2, $numIdMenu, $idMenuBlocoTramiteExterno);

      $idMenuProcessoTramitadosExterno = $this->criarMenu('Processos Tramitados Externamente', 57, $idMenuTramita, $numIdMenu, $numIdRecurso3, $numIdSistema);
      $this->cadastrarRelPerfilItemMenu($idPerfilAdm, $numIdRecurso3, $numIdMenu, $idMenuProcessoTramitadosExterno);
    
    if (InfraUtil::compararVersoes(SIP_VERSAO, ">=", "3.0.0")) {
        $objItemMenuDTO = new ItemMenuDTO();
        $objItemMenuDTO->setNumIdItemMenu($idMenuTramita);
        $objItemMenuDTO->setNumIdSistema($numIdSistema);
        $objItemMenuDTO->setStrRotulo('Tramita GOV.BR');
        $objItemMenuDTO->retNumIdMenu();
        $objItemMenuDTO->retNumIdItemMenu();
        $objItemMenuBD = new ItemMenuBD(BancoSip::getInstance());
        $objItemMenuDTO = $objItemMenuBD->consultar($objItemMenuDTO);

      if (isset($objItemMenuDTO)) {
          $objItemMenuDTO->setStrIcone('pen_tramite_externo_lote.svg');
          $objItemMenuDTO->setStrDescricao('Blocos de Tr�mite Externo');
          $objItemMenuBD->alterar($objItemMenuDTO);
      }

    }

      /* Corrige nome de menu de trâmite de documentos */
      $objItemMenuBD = new ItemMenuBD(BancoSip::getInstance());
      $numIdSistema = $this->getNumIdSistema('SEI');
      $numIdMenu = $this->getNumIdMenu('Principal', $numIdSistema);
      $objItemMenuDTO = new ItemMenuDTO();
      $objItemMenuDTO->setNumIdSistema($numIdSistema);
      $objItemMenuDTO->setNumIdMenu($numIdMenu);
      $objItemMenuDTO->setStrRotulo('Tramita GOV.BR');
      $objItemMenuDTO->setNumMaxRegistrosRetorno(1);
      $objItemMenuDTO->retNumIdItemMenu();
      $objItemMenuDTO = $objItemMenuBD->consultar($objItemMenuDTO);

    if (empty($objItemMenuDTO)) { 
        throw new InfraException('M�dulo do Tramita: Menu "Tramita GOV.BR" n�o foi localizado');
    }

      // Adicionar item de menu Mapeamento de Envio Parcial
      $this->logar('Atribui��o de permiss�es do m�dulo ao perfil do SEI');

      $this->criarMenu('Mapeamento de Envio Parcial', 90, $objItemMenuDTO->getNumIdItemMenu(), $numIdMenu, $numIdRecurso, $numIdSistema);
      $numIdRecurso = $this->criarRecurso('pen_map_envio_parcial_listar', 'Mapeamento de Envio Parcial', $numIdSistema);

      $this->criarRecurso('pen_map_envio_parcial_salvar', 'Salvar Mapeamento de Envio Parcial', $numIdSistema);
      $this->criarRecurso('pen_map_envio_parcial_excluir', 'Excluir Mapeamento de Envio Parcial', $numIdSistema);
      $this->criarRecurso('pen_map_envio_parcial_cadastrar', 'Cadastro de Mapeamento de Envio Parcial', $numIdSistema);
      $this->criarRecurso('pen_map_envio_parcial_atualizar', 'Atualizar Mapeamento de Envio Parcial', $numIdSistema);
      $this->criarRecurso('pen_map_envio_parcial_visualizar', 'Visualizar Mapeamento de Envio Parcial', $numIdSistema);

      $numIdSistemaSei = $this->getNumIdSistema('SEI');
      $numIdPerfilSeiAdministrador = ScriptSip::obterIdPerfil($numIdSistema, "Administrador");
      ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, 'pen_map_envio_parcial_listar');
      ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, 'pen_map_envio_parcial_salvar');
      ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, 'pen_map_envio_parcial_cadastrar');
      ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, 'pen_map_envio_parcial_atualizar');
      ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, 'pen_map_envio_parcial_visualizar');
      ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador, 'pen_map_envio_parcial_excluir');
      ScriptSip::adicionarItemMenu($numIdSistemaSei, $numIdPerfilSeiAdministrador, $numIdMenu, $objItemMenuDTO->getNumIdItemMenu(), $numIdRecurso, "Mapeamento de Envio Parcial", 90);

      $this->atualizarNumeroVersao("3.6.0");
  }

  protected function instalarV3061()
    {
    try{
        $numIdSistema = $this->getNumIdSistema('SEI');

        $numIdMenu = ScriptSip::obterIdMenu($numIdSistema, 'Principal');
        $numIdItemMenuMapTipDoc = ScriptSip::obterIdItemMenu($numIdSistema, $numIdMenu, 'Processos Tramitados em Bloco');
        ScriptSip::removerItemMenu($numIdSistema, $numIdMenu, $numIdItemMenuMapTipDoc);
    } catch (\Exception $e) {
        $this->logar("Item de menu de mapeamento de tipos de documentos n�o pode ser localizado");
    }
      $this->atualizarNumeroVersao("3.6.1");
  }

  protected function instalarV3062()
    {
      $numIdSistemaSei = $this->getNumIdSistema('SEI');
      $idPerfilBasico = ScriptSip::obterIdPerfil($numIdSistemaSei, "B�sico");    
      ScriptSip::adicionarRecursoPerfil($numIdSistemaSei, $idPerfilBasico, 'pen_map_envio_parcial_listar');
      $this->atualizarNumeroVersao("3.6.2");  
  }
  
  protected function instalarV3070()
    {
      $numIdSistema = $this->getNumIdSistema('SEI');
      $numIdMenu = $this->getNumIdMenu('Principal', $numIdSistema);
    
      $idPerfilAdm = ScriptSip::obterIdPerfil($numIdSistema, "Administrador");
      $idPerfilBasico = ScriptSip::obterIdPerfil($numIdSistema, "B�sico");

    try {
        // Remove item de menu anterior e seus submenus configurados de forma errada
        $numIdItemMenu = ScriptSip::obterIdItemMenu($numIdSistema, $numIdMenu, 'Processos Tramitados Externamente');
        ScriptSip::removerItemMenu($numIdSistema, $numIdMenu, $numIdItemMenu);

        $numIdItemMenu = ScriptSip::obterIdItemMenu($numIdSistema, $numIdMenu, 'Processos Tramitados em Bloco');
        ScriptSip::removerItemMenu($numIdSistema, $numIdMenu, $numIdItemMenu);
    } catch (\Exception $e) {
        $this->logar("Item de menu 'Processos Tramitados em Bloco' e/ou 'Processos Tramitados Externamente' n�o localizado(s)");
    }

      /* Corrige nome de menu de tr�mite de documentos */
      $objItemMenuBD = new ItemMenuBD(BancoSip::getInstance());

      $objItemMenuDTO = new ItemMenuDTO();
      $objItemMenuDTO->setNumIdSistema($numIdSistema);
      $objItemMenuDTO->setNumIdMenu($numIdMenu);
      $objItemMenuDTO->setNumIdItemMenuPai(null);
      $objItemMenuDTO->setStrRotulo('Tramita GOV.BR');
      $objItemMenuDTO->setNumMaxRegistrosRetorno(1);
      $objItemMenuDTO->retNumIdItemMenu();
      $objItemMenuDTO->retNumIdMenu();

      $objItemMenuDTO = $objItemMenuBD->consultar($objItemMenuDTO);

    if (empty($objItemMenuDTO)) {
        throw new InfraException('M�dulo do Tramita: Menu "Tramita GOV.BR" n�o foi localizado');
    }

      $idMenuTramita = $objItemMenuDTO->getNumIdItemMenu();
      $numIdRecurso = $this->criarRecurso('pen_procedimento_expedido_listar', 'Processos em Tramita��o Externa', $numIdSistema);

      $idMenuProcessoTramitadosExterno = $this->criarMenu('Processos em Tramita��o Externa', 57, $idMenuTramita, $numIdMenu, $numIdRecurso, $numIdSistema);
      $this->cadastrarRelPerfilItemMenu($idPerfilBasico, $numIdRecurso, $numIdMenu, $idMenuProcessoTramitadosExterno);
      $this->excluirRelPerfilItemMenu($idPerfilAdm, $numIdRecurso, $numIdMenu, $idMenuProcessoTramitadosExterno);

      $this->renomearRecurso($numIdSistema, 'pen_expedir_lote', 'pen_expedir_bloco');

      ScriptSip::adicionarRecursoPerfil($numIdSistema, $idPerfilBasico, 'pen_map_envio_parcial_visualizar');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $idPerfilBasico, 'pen_procedimento_expedido_listar');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $idPerfilBasico, 'md_pen_tramita_em_bloco');
      ScriptSip::adicionarRecursoPerfil($numIdSistema, $idPerfilBasico, 'pen_expedir_bloco');
      ScriptSip::removerRecursoPerfil($numIdSistema, 'pen_expedir_bloco', $idPerfilAdm);
    
      $numIdRecurso1 = $this->criarRecurso('pen_procedimento_expedido_listar', 'Tramita GOV.BR', $numIdSistema);
      $idMenuTramita = $this->criarMenu('Tramita GOV.BR', 55, null, $numIdMenu, $numIdRecurso1, $numIdSistema);
      $this->cadastrarRelPerfilItemMenu($idPerfilBasico, $numIdRecurso1, $numIdMenu, $idMenuTramita);
      $this->excluirRelPerfilItemMenu($idPerfilAdm, $numIdRecurso1, $numIdMenu, $idMenuTramita);

      $numIdRecurso2 = $this->criarRecurso('md_pen_tramita_em_bloco', 'Blocos de Tr�mite Externo', $numIdSistema);
      $idMenuBlocoTramiteExterno = $this->criarMenu('Blocos de Tr�mite Externo', 56, $idMenuTramita, $numIdMenu, $numIdRecurso2, $numIdSistema);
      $this->cadastrarRelPerfilItemMenu($idPerfilBasico, $numIdRecurso2, $numIdMenu, $idMenuBlocoTramiteExterno);
      $this->excluirRelPerfilItemMenu($idPerfilAdm, $numIdRecurso2, $numIdMenu, $idMenuBlocoTramiteExterno);


      $this->atualizarNumeroVersao("3.7.0");
  }

  protected function instalarV3080()
    {
      $this->atualizarNumeroVersao("3.8.0");
  }

  protected function instalarV4000()
    {
      $this->atualizarNumeroVersao("4.0.0");
  }

    protected function instalarV4010()
    {
      $this->atualizarNumeroVersao("4.0.1");
  }

    /**
     * Cadastrar item do menu em um perfil expecifico
     * 
     * @return void
     */
  private function cadastrarRelPerfilItemMenu($numIdPerfil, $numIdRecurso, $numIdMenu, $numIdItemMenuRecuso)
    {
      $numIdSistema = $this->getNumIdSistema('SEI');

      $objDTO = new RelPerfilItemMenuDTO();
      $objBD = new RelPerfilItemMenuBD(BancoSip::getInstance());

      $objDTO->setNumIdPerfil($numIdPerfil);
      $objDTO->setNumIdSistema($numIdSistema);
      $objDTO->setNumIdRecurso($numIdRecurso);
      $objDTO->setNumIdMenu($numIdMenu);
      $objDTO->setNumIdItemMenu($numIdItemMenuRecuso);

    if ($objBD->contar($objDTO) == 0) {
        $objBD->cadastrar($objDTO);
    }
  }

    /**
     * Excluir item do menu em um perfil expecifico
     * 
     * @return void
     */
  private function excluirRelPerfilItemMenu($numIdPerfil, $numIdRecurso, $numIdMenu, $numIdItemMenuRecuso)
    {
      $numIdSistema = $this->getNumIdSistema('SEI');

      $objDTO = new RelPerfilItemMenuDTO();
      $objBD = new RelPerfilItemMenuBD(BancoSip::getInstance());

      $objDTO->setNumIdPerfil($numIdPerfil);
      $objDTO->setNumIdSistema($numIdSistema);
      $objDTO->setNumIdRecurso($numIdRecurso);
      $objDTO->setNumIdMenu($numIdMenu);
      $objDTO->setNumIdItemMenu($numIdItemMenuRecuso);

    if ($objBD->contar($objDTO) == 1) {
        $objBD->excluir($objDTO);
    }
  }


}

try {
    session_start();
    SessaoSip::getInstance(false);
    $objVersaoSipRN = null;

  if (InfraUtil::compararVersoes(SIP_VERSAO, ">=", "3.0.0")) {
      $objInfraParametro = new InfraParametro(BancoSip::getInstance());

      SessaoSip::getInstance(false);
      BancoSip::getInstance()->setBolScript(true);

      $objVersaoSipRN = new VersaoSip4RN();
      $objVersaoSipRN->verificarVersaoInstalada();
      $strVersaoModuloPen = $objInfraParametro->getValor(PenAtualizarSipRN::PARAMETRO_VERSAO_MODULO, false) ?: $objInfraParametro->getValor(PenAtualizarSipRN::PARAMETRO_VERSAO_MODULO_ANTIGO, false);
      $objVersaoSipRN->setStrNome(PenAtualizarSipRN::NOME_MODULO);
      $objVersaoSipRN->setStrParametroVersao(PenAtualizarSipRN::PARAMETRO_VERSAO_MODULO);
      $objVersaoSipRN->setArrVersoes(
          ['0.0.0' => 'versao_0_0_0', $strVersaoModuloPen => 'atualizarVersaoCompatibilidade', VERSAO_MODULO_PEN => 'atualizarVersaoCompatibilidade']
      );

      $objVersaoSipRN->setStrVersaoAtual(VERSAO_MODULO_PEN);
      $objVersaoSipRN->setStrVersaoInfra("1.583.4");
      $objVersaoSipRN->setBolMySql(true);
      $objVersaoSipRN->setBolOracle(true);
      $objVersaoSipRN->setBolSqlServer(true);
      $objVersaoSipRN->setBolPostgreSql(true);
      $objVersaoSipRN->setBolErroVersaoInexistente(false);

      $objVersaoSipRN->atualizarVersao();
  } else {
      BancoSip::getInstance()->setBolScript(true);

    if (!ConfiguracaoSip::getInstance()->isSetValor('BancoSip', 'UsuarioScript')) {
        throw new InfraException('M�dulo do Tramita: Chave BancoSip/UsuarioScript n�o encontrada.');
    }

    if (InfraString::isBolVazia(ConfiguracaoSip::getInstance()->getValor('BancoSip', 'UsuarioScript'))) {
        throw new InfraException('M�dulo do Tramita: Chave BancoSip/UsuarioScript n�o possui valor.');
    }

    if (!ConfiguracaoSip::getInstance()->isSetValor('BancoSip', 'SenhaScript')) {
        throw new InfraException('M�dulo do Tramita: Chave BancoSip/SenhaScript n�o encontrada.');
    }

    if (InfraString::isBolVazia(ConfiguracaoSip::getInstance()->getValor('BancoSip', 'SenhaScript'))) {
        throw new InfraException('M�dulo do Tramita: Chave BancoSip/SenhaScript n�o possui valor.');
    }

      $objAtualizarRN = new PenAtualizarSipRN();
      $objAtualizarRN->atualizarVersao();
  }

    exit(0);
} catch (Exception $e) {
    echo (InfraException::inspecionar($e));
  try {
      LogSip::getInstance()->gravar(InfraException::inspecionar($e));
  } catch (Exception $e) {
  }

    exit(1);
}