<?php

// Identifica��o da vers�o do m�dulo. Este dever� ser atualizado e sincronizado com constante VERSAO_MODULO
define("VERSAO_MODULO_PEN", "3.6.0");

class PENIntegracao extends SeiIntegracao
{
  const VERSAO_MODULO = VERSAO_MODULO_PEN;
  const PARAMETRO_VERSAO_MODULO_ANTIGO = 'PEN_VERSAO_MODULO_SEI';
  const PARAMETRO_VERSAO_MODULO = 'VERSAO_MODULO_PEN';

  private static $instance = null;

  public static function getInstance()
  {
    if (self::$instance == null) {
      self::$instance = new PENIntegracao();
    }

    return self::$instance;
  }

  public function getNome() {
    return 'Integra��o Tramita GOV.BR';
  }


  public function getVersao() {
    return self::VERSAO_MODULO;
  }


  public function getInstituicao() {
      return 'Minist�rio da Gest�o e da Inova��o em Servi�os P�blicos - MGI';
  }

  public function obterDiretorioIconesMenu() {
    return self::getDiretorioImagens() . '/menu/';
  }

  public function inicializar($strVersaoSEI)
  {
      define('DIR_SEI_WEB', realpath(DIR_SEI_CONFIG.'/../web'));
    require_once DIR_SEI_CONFIG . '/mod-pen/ConfiguracaoModPEN.php';
  }

  public function montarBotaoControleProcessos() {

    if(!PENIntegracao::verificarCompatibilidadeConfiguracoes()){
      return false;
    }

    $objSessaoSEI = SessaoSEI::getInstance();
    $strAcoesProcedimento = "";

    $bolAcaoGerarPendencia = $objSessaoSEI->verificarPermissao('pen_expedir_lote');

    if ($bolAcaoGerarPendencia) {
      $objPaginaSEI = PaginaSEI::getInstance();

      $objAtividadeDTO = new AtividadeDTO();
      $objAtividadeDTO->setDistinct(true);
      $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
      $objAtividadeDTO->setDthConclusao(null);
      $objAtividadeDTO->retNumIdUnidade();

      $objAtividadeRN = new AtividadeRN();
      $numRegistros = $objAtividadeRN->contarRN0035($objAtividadeDTO);

      $objPenUnidadeDTO = new PenUnidadeDTO();
      $objPenUnidadeDTO->retNumIdUnidade();
      $objPenUnidadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
      $objPenUnidadeRN = new PenUnidadeRN();

      if ($numRegistros > 0 && $objPenUnidadeRN->contar($objPenUnidadeDTO) != 0) {
        $numTabBotao = $objPaginaSEI->getProxTabBarraComandosSuperior();
        $strAcoesProcedimento .= '<a href="#" onclick="return acaoControleProcessos(\'' . $objSessaoSEI->assinarLink('controlador.php?acao=pen_tramita_em_bloco_adicionar&acao_origem=' . $_GET['acao'] . '&acao_retorno=' . $_GET['acao']) . '\', true, false);" tabindex="' . $numTabBotao . '" class="botaoSEI">';
        $strAcoesProcedimento .= '<img class="infraCorBarraSistema" src="' . ProcessoEletronicoINT::getCaminhoIcone("/pen_processo_bloco.svg", $this->getDiretorioImagens()) . '" class="infraCorBarraSistema" alt="Incluir Processos no Bloco de Tr�mite" title="Incluir Processos no Bloco de Tr�mite" />';
      }
    }

    return array($strAcoesProcedimento);
  }

  public function montarBotaoProcesso(ProcedimentoAPI $objSeiIntegracaoDTO)
  {
    if(!PENIntegracao::verificarCompatibilidadeConfiguracoes()){
      return false;
    }

    $objProcedimentoDTO = new ProcedimentoDTO();
    $objProcedimentoDTO->setDblIdProcedimento($objSeiIntegracaoDTO->getIdProcedimento());
    $objProcedimentoDTO->retTodos();

    $objProcedimentoRN = new ProcedimentoRN();
    $objProcedimentoDTO = $objProcedimentoRN->consultarRN0201($objProcedimentoDTO);

    $objSessaoSEI = SessaoSEI::getInstance();
    $objPaginaSEI = PaginaSEI::getInstance();
    $strAcoesProcedimento = "";

    $dblIdProcedimento = $objProcedimentoDTO->getDblIdProcedimento();
    $numIdUsuario = SessaoSEI::getInstance()->getNumIdUsuario();
    $numIdUnidadeAtual = SessaoSEI::getInstance()->getNumIdUnidadeAtual();
    $objInfraParametro = new InfraParametro(BancoSEI::getInstance());

    //Verifica se o processo encontra-se aberto na unidade atual
    $objAtividadeRN = new AtividadeRN();
    $objPesquisaPendenciaDTO = new PesquisaPendenciaDTO();
    $objPesquisaPendenciaDTO->setDblIdProtocolo($dblIdProcedimento);
    $objPesquisaPendenciaDTO->setNumIdUsuario($numIdUsuario);
    $objPesquisaPendenciaDTO->setNumIdUnidade($numIdUnidadeAtual);
    $objPesquisaPendenciaDTO->setStrSinMontandoArvore('N');
    $arrObjProcedimentoDTO = $objAtividadeRN->listarPendenciasRN0754($objPesquisaPendenciaDTO);
    $bolFlagAberto = count($arrObjProcedimentoDTO) == 1;

    //Verifica��o da Restri��o de Acesso a Funcionalidade
    $bolAcaoExpedirProcesso = $objSessaoSEI->verificarPermissao('pen_procedimento_expedir');
    $objExpedirProcedimentoRN = new ExpedirProcedimentoRN();
    $objProcedimentoDTO = $objExpedirProcedimentoRN->consultarProcedimento($dblIdProcedimento);

    $bolProcessoEstadoNormal = !in_array($objProcedimentoDTO->getStrStaEstadoProtocolo(), array(
      ProtocoloRN::$TE_PROCEDIMENTO_SOBRESTADO,
      ProtocoloRN::$TE_PROCEDIMENTO_BLOQUEADO
    ));

    //Apresenta o bot�o de expedir processo
    if ($bolFlagAberto && $bolAcaoExpedirProcesso && $bolProcessoEstadoNormal && $objProcedimentoDTO->getStrStaNivelAcessoGlobalProtocolo() != ProtocoloRN::$NA_SIGILOSO) {

      $objPenUnidadeDTO = new PenUnidadeDTO();
      $objPenUnidadeDTO->retNumIdUnidade();
      $objPenUnidadeDTO->setNumIdUnidade($numIdUnidadeAtual);
      $objPenUnidadeRN = new PenUnidadeRN();

      if($objPenUnidadeRN->contar($objPenUnidadeDTO) != 0) {
        $numTabBotao = $objPaginaSEI->getProxTabBarraComandosSuperior();
        $strAcoesProcedimento .= '<a id="validar_expedir_processo" href="' . $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_expedir&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_procedimento=' . $dblIdProcedimento . '&arvore=1')) . '" tabindex="' . $numTabBotao . '" class="botaoSEI"><img class="infraCorBarraSistema" src=' . ProcessoEletronicoINT::getCaminhoIcone("/pen_expedir_procedimento.gif", $this->getDiretorioImagens()) . ' alt="Envio Externo de Processo" title="Envio Externo de Processo" /></a>';
      }
    }

    //Apresenta o bot�o da p�gina de recibos
    if($bolAcaoExpedirProcesso){
      $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
      $objProcessoEletronicoDTO->retDblIdProcedimento();
      $objProcessoEletronicoDTO->setDblIdProcedimento($dblIdProcedimento);
      $objProcessoEletronicoRN = new ProcessoEletronicoRN();
      if($objProcessoEletronicoRN->contar($objProcessoEletronicoDTO) != 0){
        $numTabBotao = $objPaginaSEI->getProxTabBarraComandosSuperior();
        $strAcoesProcedimento .= '<a href="' . $objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_estado&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_procedimento=' . $dblIdProcedimento . '&arvore=1') . '" tabindex="' . $numTabBotao . '" class="botaoSEI">';
        $strAcoesProcedimento .= '<img class="infraCorBarraSistema" src=' . ProcessoEletronicoINT::getCaminhoIcone("/pen_consultar_recibos.png", $this->getDiretorioImagens()) . ' alt="Consultar Recibos" title="Consultar Recibos"/>';
        $strAcoesProcedimento .= '</a>';
      }
    }

    //Apresenta o bot�o de cancelar tr�mite
    $objAtividadeDTO = $objExpedirProcedimentoRN->verificarProcessoEmExpedicao($objSeiIntegracaoDTO->getIdProcedimento());
    if (
            $objAtividadeDTO &&
            $objAtividadeDTO->getNumIdTarefa() == ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO) &&
            $objAtividadeDTO->getNumIdUnidade() == $numIdUnidadeAtual
        ) {
        $numTabBotao = $objPaginaSEI->getProxTabBarraComandosSuperior();
        $strAcoesProcedimento .= '<a href="' . $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_cancelar_expedir&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_procedimento=' . $dblIdProcedimento . '&arvore=1')) . '" tabindex="' . $numTabBotao . '" class="botaoSEI">';
        $strAcoesProcedimento .= '<img class="infraCorBarraSistema" src=' . ProcessoEletronicoINT::getCaminhoIcone("/pen_cancelar_tramite.gif", $this->getDiretorioImagens()) . '  alt="Cancelar Tramita��o Externa" title="Cancelar Tramita��o Externa" />';
        $strAcoesProcedimento .= '</a>';
    }

    //Apresenta o bot�o de incluir processo no bloco de tr�mite
    $numTabBotao = $objPaginaSEI->getProxTabBarraComandosSuperior();
    $strAcoesProcedimento .= '<a href="' . $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=pen_incluir_processo_em_bloco_tramite&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_procedimento=' . $dblIdProcedimento . '&arvore=1')) . '" tabindex="' . $numTabBotao . '" class="botaoSEI"> <img src="'.ProcessoEletronicoINT::getCaminhoIcone("/pen_processo_bloco.svg", $this->getDiretorioImagens()) .'" title="Incluir Processo no Bloco de Tr�mite" alt="Incluir Processo no Bloco de Tr�mite"/></a>';


    return array($strAcoesProcedimento);
  }


  public function montarIconeControleProcessos($arrObjProcedimentoAPI = array())
  {
    if(!PENIntegracao::verificarCompatibilidadeConfiguracoes()){
      return false;
    }

      $arrStrIcone = array();
      $arrDblIdProcedimento = array();

    foreach ($arrObjProcedimentoAPI as $ObjProcedimentoAPI) {
        $arrDblIdProcedimento[] = $ObjProcedimentoAPI->getIdProcedimento();
    }

      $arrStrIcone = $this->montarIconeRecusa($arrDblIdProcedimento, $arrStrIcone);
      $arrStrIcone = $this->montarIconeTramite($arrDblIdProcedimento, $arrStrIcone);

      return $arrStrIcone;
  }

  private function montarIconeRecusa($arrDblIdProcedimento = array(), $arrStrIcone = array())
  {
    $objProcedimentoDTO = new ProcedimentoDTO();
    $objProcedimentoDTO->setDblIdProcedimento($arrDblIdProcedimento, InfraDTO::$OPER_IN);
    $objProcedimentoDTO->retDblIdProcedimento();
    $objProcedimentoDTO->retStrStaEstadoProtocolo();
    $objProcedimentoBD = new ProcedimentoBD(BancoSEI::getInstance());
    $arrObjProcedimentoDTO = $objProcedimentoBD->listar($objProcedimentoDTO);

    if (!empty($arrObjProcedimentoDTO)) {

      foreach ($arrObjProcedimentoDTO as $objProcedimentoDTO) {
        $dblIdProcedimento = $objProcedimentoDTO->getDblIdProcedimento();
        $objPenProtocoloDTO = new PenProtocoloDTO();
        $objPenProtocoloDTO->setDblIdProtocolo($dblIdProcedimento);
        $objPenProtocoloDTO->retStrSinObteveRecusa();
        $objPenProtocoloDTO->setNumMaxRegistrosRetorno(1);
        $objProtocoloBD = new ProtocoloBD(BancoSEI::getInstance());
        $objPenProtocoloDTO = $objProtocoloBD->consultar($objPenProtocoloDTO);

        if (!empty($objPenProtocoloDTO) && $objPenProtocoloDTO->getStrSinObteveRecusa() == 'S') {
          $arrStrIcone[$dblIdProcedimento] = array('<img src="' . $this->getDiretorioImagens() . '/pen_tramite_recusado.png" title="Um tr�mite para esse processo foi recusado" />');
        }
      }
    }

    return $arrStrIcone;
  }

  private function montarIconeTramite($arrDblIdProcedimento = array(), $arrStrIcone = array())
  {
    $arrTiProcessoEletronico = array(
      ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO),
      ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_CANCELADO),
      ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_ABORTADO),
      ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO),
      ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_DOCUMENTO_AVULSO_RECEBIDO)
    );
    
    foreach ($arrDblIdProcedimento as $dblIdProcedimento) {
      $objAtividadeDTO = new AtividadeDTO();
      $objAtividadeDTO->setDblIdProtocolo($dblIdProcedimento);
      $objAtividadeDTO->setNumIdTarefa($arrTiProcessoEletronico, InfraDTO::$OPER_IN);
      $objAtividadeDTO->setOrdDthAbertura(InfraDTO::$TIPO_ORDENACAO_DESC);
      $objAtividadeDTO->setNumMaxRegistrosRetorno(1);
      $objAtividadeDTO->retNumIdAtividade();
      $objAtividadeDTO->retNumIdTarefa();
      $objAtividadeDTO->retDblIdProcedimentoProtocolo();
      
      $objAtividadeRN = new AtividadeRN();
      $ObjAtividadeDTO = $objAtividadeRN->consultarRN0033($objAtividadeDTO);
      
      if (!empty($ObjAtividadeDTO)) {
        switch ($ObjAtividadeDTO->getNumIdTarefa()) {
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO):
            $arrayIcone = array('<img src="' . $this->getDiretorioImagens() . '/icone-ENVIADO-tramita.png" title="Um tr�mite para esse processo foi enviado" />');
            if (!isset($arrStrIcone[$dblIdProcedimento])) {
              $arrStrIcone[$dblIdProcedimento] = $arrayIcone;
            } else {
              $arrStrIcone[$dblIdProcedimento] = array_merge($arrStrIcone[$dblIdProcedimento], $arrayIcone);
            }
              break;
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO):
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_DOCUMENTO_AVULSO_RECEBIDO):
            $arrayIcone = array('<img src="' . $this->getDiretorioImagens() . '/icone-RECEBIDO-tramita.png" title="Um tr�mite para esse processo foi recebido" />');
            if (!isset($arrStrIcone[$dblIdProcedimento])) {
              $arrStrIcone[$dblIdProcedimento] = $arrayIcone;
            } else {
              $arrStrIcone[$dblIdProcedimento] = array_merge($arrStrIcone[$dblIdProcedimento], $arrayIcone);
            }
              break;
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_CANCELADO):
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_ABORTADO):
            if ($this->consultarProcessoRecebido($dblIdProcedimento)) {
              $arrayIcone = array('<img src="' . $this->getDiretorioImagens() . '/icone-RECEBIDO-tramita.png" title="Um tr�mite para esse processo foi recebido" />');
              if (!isset($arrStrIcone[$dblIdProcedimento])) {
                $arrStrIcone[$dblIdProcedimento] = $arrayIcone;
              } else {
                $arrStrIcone[$dblIdProcedimento] = array_merge($arrStrIcone[$dblIdProcedimento], $arrayIcone);
              }
            }
              break;
          default:
              break;
        }
      }
    }

    return $arrStrIcone;
  }
  
  private function consultarProcessoRecebido($dblIdProtocolo)
  {
    $objAtividadeDTO = new AtividadeDTO();
    $objAtividadeDTO->setDblIdProtocolo($dblIdProtocolo);
    $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO));
    $objAtividadeDTO->setOrdDthAbertura(InfraDTO::$TIPO_ORDENACAO_DESC);
    $objAtividadeDTO->setNumMaxRegistrosRetorno(1);
    $objAtividadeDTO->retNumIdAtividade();
    $objAtividadeDTO->retNumIdTarefa();
    $objAtividadeDTO->retDblIdProcedimentoProtocolo();
    $objAtividadeRN = new AtividadeRN();
    $arrObjAtividadeDTO = $objAtividadeRN->consultarRN0033($objAtividadeDTO);

    return !empty($arrObjAtividadeDTO);
  }

  public function montarIconeProcesso(ProcedimentoAPI $objProcedimentoAP)
  {
    if(!PENIntegracao::verificarCompatibilidadeConfiguracoes()){
      return false;
    }

    $dblIdProcedimento = $objProcedimentoAP->getIdProcedimento();

    $objArvoreAcaoItemAPI = new ArvoreAcaoItemAPI();
    $objArvoreAcaoItemAPI->setTipo('MD_TRAMITE_PROCESSO');
    $objArvoreAcaoItemAPI->setId('MD_TRAMITE_PROC_' . $dblIdProcedimento);
    $objArvoreAcaoItemAPI->setIdPai($dblIdProcedimento);
    $objArvoreAcaoItemAPI->setTitle('Um tr�mite para esse processo foi recusado');
    $objArvoreAcaoItemAPI->setIcone($this->getDiretorioImagens() . '/pen_tramite_recusado.png');

    $objArvoreAcaoItemAPI->setTarget(null);
    $objArvoreAcaoItemAPI->setHref('javascript:alert(\'Um tr�mite para esse processo foi recusado\');');

    $objArvoreAcaoItemAPI->setSinHabilitado('S');

    $objProcedimentoDTO = new ProcedimentoDTO();
    $objProcedimentoDTO->setDblIdProcedimento($dblIdProcedimento);
    $objProcedimentoDTO->retDblIdProcedimento();
    $objProcedimentoDTO->retStrStaEstadoProtocolo();

    $objProcedimentoBD = new ProcedimentoBD(BancoSEI::getInstance());
    $arrObjProcedimentoDTO = $objProcedimentoBD->consultar($objProcedimentoDTO);

    if (!empty($arrObjProcedimentoDTO)) {
      $dblIdProcedimento = $objProcedimentoDTO->getDblIdProcedimento();
      $objPenProtocoloDTO = new PenProtocoloDTO();
      $objPenProtocoloDTO->setDblIdProtocolo($dblIdProcedimento);
      $objPenProtocoloDTO->retStrSinObteveRecusa();
      $objPenProtocoloDTO->setNumMaxRegistrosRetorno(1);

      $objProtocoloBD = new ProtocoloBD(BancoSEI::getInstance());
      $objPenProtocoloDTO = $objProtocoloBD->consultar($objPenProtocoloDTO);

      if (!empty($objPenProtocoloDTO) && $objPenProtocoloDTO->getStrSinObteveRecusa() == 'S') {
        $arrObjArvoreAcaoItemAPI[] = $objArvoreAcaoItemAPI;
      }

      $arrObjArvoreAcaoItemAPI = $this->getObjArvoreAcao(
        $dblIdProcedimento,
        $arrObjArvoreAcaoItemAPI
      );
    } else {
      return array();
    }

    return $arrObjArvoreAcaoItemAPI;
  }

  private function getObjArvoreAcao($dblIdProcedimento, $arrObjArvoreAcaoItemAPI)
  {
    // $objAtividadeDTO = new AtividadeDTO();
    // $objAtividadeDTO->setDblIdProtocolo($dblIdProcedimento);
    // $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::obterIdTarefaModulo($idTarefaAtividade));
    // $objAtividadeDTO->setNumMaxRegistrosRetorno(1);
    // $objAtividadeDTO->setOrdDthAbertura(InfraDTO::$TIPO_ORDENACAO_DESC);
    // $objAtividadeDTO->retNumIdAtividade();
    
    // $objAtividadeRN = new AtividadeRN();
    // $objAtividadeDTO = $objAtividadeRN->consultarRN0033($objAtividadeDTO);

    
    // if (!empty($objAtividadeDTO)) {
    //   if ($idTarefaAtividade == ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO) {
    //     $arrObjArvoreAcaoItemAPI[] = $this->getObjArvoreAcaoEnviado($dblIdProcedimento);
    //   } elseif ($idTarefaAtividade == ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO) {
    //     $arrObjArvoreAcaoItemAPI[] = $this->getObjArvoreAcaoRecebido($dblIdProcedimento);
    //   }
    // }

    $arrTiProcessoEletronico = array(
      ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO),
      ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO),
      ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_CANCELADO),
      ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_ABORTADO),
      ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_DOCUMENTO_AVULSO_RECEBIDO)
    );

    $objAtividadeDTO = new AtividadeDTO();
    $objAtividadeDTO->setDblIdProtocolo($dblIdProcedimento);
    $objAtividadeDTO->setNumIdTarefa($arrTiProcessoEletronico, InfraDTO::$OPER_IN);
    $objAtividadeDTO->setNumMaxRegistrosRetorno(1);
    $objAtividadeDTO->setOrdDthAbertura(InfraDTO::$TIPO_ORDENACAO_DESC);
    $objAtividadeDTO->retNumIdTarefa();
    $objAtividadeDTO->retNumIdAtividade();
    
    $objAtividadeRN = new AtividadeRN();
    $objAtividadeDTO = $objAtividadeRN->consultarRN0033($objAtividadeDTO);
    
    if (!empty($objAtividadeDTO)) {
      switch ($objAtividadeDTO->getNumIdTarefa()) {
        case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO):
          $arrObjArvoreAcaoItemAPI[] = $this->getObjArvoreAcaoEnviado($dblIdProcedimento);
            break;
        case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_DOCUMENTO_AVULSO_RECEBIDO):
        case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO):
          $arrObjArvoreAcaoItemAPI[] = $this->getObjArvoreAcaoRecebido($dblIdProcedimento);
            break;
        case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_CANCELADO):
        case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_ABORTADO):
          if ($this->consultarProcessoRecebido($dblIdProcedimento)) {
            $arrObjArvoreAcaoItemAPI[] = $this->getObjArvoreAcaoRecebido($dblIdProcedimento);
          }
            break;
        default:
            break;
      }
    }

    return $arrObjArvoreAcaoItemAPI;
  }

  private function getObjArvoreAcaoRecebido($dblIdProcedimento)
  {
    $objArvoreAcaoItemAPI = new ArvoreAcaoItemAPI();
    $objArvoreAcaoItemAPI->setTipo('MD_TRAMITE_PROCESSO');
    $objArvoreAcaoItemAPI->setId('MD_TRAMITE_PROC_' . $dblIdProcedimento);
    $objArvoreAcaoItemAPI->setIdPai($dblIdProcedimento);
    $objArvoreAcaoItemAPI->setTitle('Um tr�mite para esse processo foi recebido');
    $objArvoreAcaoItemAPI->setIcone($this->getDiretorioImagens() . '/icone-RECEBIDO-tramita.png');

    $objArvoreAcaoItemAPI->setTarget(null);
    $objArvoreAcaoItemAPI->setHref('javascript:alert(\'Um tr�mite para esse processo foi recebido\');');

    $objArvoreAcaoItemAPI->setSinHabilitado('S');

    return $objArvoreAcaoItemAPI;
  }

  private function getObjArvoreAcaoEnviado($dblIdProcedimento)
  {
    $objArvoreAcaoItemAPI = new ArvoreAcaoItemAPI();
    $objArvoreAcaoItemAPI->setTipo('MD_TRAMITE_PROCESSO');
    $objArvoreAcaoItemAPI->setId('MD_TRAMITE_PROC_' . $dblIdProcedimento);
    $objArvoreAcaoItemAPI->setIdPai($dblIdProcedimento);
    $objArvoreAcaoItemAPI->setTitle('Um tr�mite para esse processo foi enviado');
    $objArvoreAcaoItemAPI->setIcone($this->getDiretorioImagens() . '/icone-ENVIADO-tramita.png');

    $objArvoreAcaoItemAPI->setTarget(null);
    $objArvoreAcaoItemAPI->setHref('javascript:alert(\'Um tr�mite para esse processo foi enviado\');');

    $objArvoreAcaoItemAPI->setSinHabilitado('S');

    return $objArvoreAcaoItemAPI;
  }

  public function montarIconeAcompanhamentoEspecial($arrObjProcedimentoDTO)
  {
    if(!PENIntegracao::verificarCompatibilidadeConfiguracoes()){
      return false;
    }
  }

  public function getDiretorioImagens()
  {
    return static::getDiretorio() . '/imagens';
  }


  public function montarMensagemProcesso(ProcedimentoAPI $objProcedimentoAPI)
  {
    if(!PENIntegracao::verificarCompatibilidadeConfiguracoes()){
      return false;
    }

    $objExpedirProcedimentoRN = new ExpedirProcedimentoRN();
    $objAtividadeDTO = $objExpedirProcedimentoRN->verificarProcessoEmExpedicao($objProcedimentoAPI->getIdProcedimento());

    if ($objAtividadeDTO && $objAtividadeDTO->getNumIdTarefa() == ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO)) {
      $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
      $objAtributoAndamentoDTO->setStrNome('UNIDADE_DESTINO');
      $objAtributoAndamentoDTO->setNumIdAtividade($objAtividadeDTO->getNumIdAtividade());
      $objAtributoAndamentoDTO->retStrValor();

      $objAtributoAndamentoRN = new AtributoAndamentoRN();
      $objAtributoAndamentoDTO = $objAtributoAndamentoRN->consultarRN1366($objAtributoAndamentoDTO);

      return sprintf('Processo em tr�mite externo para "%s".', $objAtributoAndamentoDTO->getStrValor());
    }
  }



  public function montarIconeDocumento(ProcedimentoAPI $objProcedimentoAPI, $arrObjDocumentoAPI)
  {
    if(!PENIntegracao::verificarCompatibilidadeConfiguracoes()){
      return false;
    }

    $arrIcones = array();

    if ($objProcedimentoAPI->getCodigoAcesso() > 0) {
      $objProcessoEletronicoRN = new ProcessoEletronicoRN();
      $objPenRelTipoDocMapRecebidoRN = new PenRelTipoDocMapRecebidoRN();

      $objProcessoEletronicoPesquisaDTO = new ProcessoEletronicoDTO();
      $objProcessoEletronicoPesquisaDTO->setDblIdProcedimento($objProcedimentoAPI->getIdProcedimento());
      $objUltimoTramiteRecebidoDTO = $objProcessoEletronicoRN->consultarUltimoTramiteRecebido($objProcessoEletronicoPesquisaDTO);

      if (!is_null($objUltimoTramiteRecebidoDTO)) {
        if ($objProcessoEletronicoRN->possuiComponentesComDocumentoReferenciado($objUltimoTramiteRecebidoDTO)) {
          $arrObjComponentesDigitaisDTO = $objProcessoEletronicoRN->listarComponentesDigitais($objUltimoTramiteRecebidoDTO);
          $arrObjCompIndexadoPorOrdemDTO = InfraArray::indexarArrInfraDTO($arrObjComponentesDigitaisDTO, 'OrdemDocumento');
          $arrObjCompIndexadoPorIdDocumentoDTO = InfraArray::indexarArrInfraDTO($arrObjComponentesDigitaisDTO, 'IdDocumento');

          $arrObjDocumentoAPIIndexado = array();
          foreach ($arrObjDocumentoAPI as $objDocumentoAPI) {
            $arrObjDocumentoAPIIndexado[$objDocumentoAPI->getIdDocumento()] = $objDocumentoAPI;

            if ($objDocumentoAPI->getCodigoAcesso() > 0) {
              $dblIdDocumento = $objDocumentoAPI->getIdDocumento();
              if (array_key_exists($dblIdDocumento, $arrObjCompIndexadoPorIdDocumentoDTO)) {
                $objComponenteDTO = $arrObjCompIndexadoPorIdDocumentoDTO[$dblIdDocumento];
                if (!is_null($objComponenteDTO->getNumOrdemDocumentoReferenciado())) {
                  $arrIcones[$dblIdDocumento] = array();

                  $objComponenteReferenciadoDTO = $arrObjCompIndexadoPorOrdemDTO[$objComponenteDTO->getNumOrdemDocumentoReferenciado()];
                  $objDocumentoReferenciadoAPI = $arrObjDocumentoAPIIndexado[$objComponenteReferenciadoDTO->getDblIdDocumento()];

                          $strTextoInformativo = sprintf("Anexo do %s \(%s\)",
                    $objDocumentoReferenciadoAPI->getNomeSerie(),
                    $objDocumentoReferenciadoAPI->getNumeroProtocolo()
                  );

                  $objSerieDTO = $objPenRelTipoDocMapRecebidoRN->obterSerieMapeada($objComponenteDTO->getNumCodigoEspecie());
                  if(!is_null($objSerieDTO)){
                    $strTextoInformativo .= " - " . $objSerieDTO->getStrNome();
                  }

                  $objArvoreAcaoItemAPI = new ArvoreAcaoItemAPI();
                  $objArvoreAcaoItemAPI->setTipo('MD_PEN_DOCUMENTO_REFERENCIADO');
                  $objArvoreAcaoItemAPI->setId('MD_PEN_DOC_REF' . $dblIdDocumento);
                  $objArvoreAcaoItemAPI->setIdPai($dblIdDocumento);
                  $objArvoreAcaoItemAPI->setTitle($strTextoInformativo);
                  $objArvoreAcaoItemAPI->setIcone(ProcessoEletronicoINT::getCaminhoIcone("imagens/anexos.gif"));
                  $objArvoreAcaoItemAPI->setTarget(null);
                  $objArvoreAcaoItemAPI->setHref("javascript:alert('$strTextoInformativo');");
                  $objArvoreAcaoItemAPI->setSinHabilitado('S');

                  $arrIcones[$dblIdDocumento][] = $objArvoreAcaoItemAPI;
                }
              }
            }
          }
        }
      }
    }

    return $arrIcones;
  }

  /**
   * @param array $arrObjTipoProcedimentoDTO
   * @return void
   */
  public function desativarTipoProcesso($arrObjTipoProcedimentoDTO)
  {
    if(!PENIntegracao::verificarCompatibilidadeConfiguracoes()){
      return false;
    }

    $mensagem = "Prezado(a) usu�rio(a), voc� est� tentando desativar um Tipo de Processo que se encontra mapeado para o(s) relacionamento(s) "
          ."\"%s\". Para continuar com essa a��o � necess�rio remover do(s) mapeamentos "
          ."mencionados o Tipo de Processo: \"%s\".";

    $objMapeamentoTipoProcedimentoRN = new PenMapTipoProcedimentoRN();
    $objMapeamentoTipoProcedimentoRN->validarAcaoTipoProcesso($arrObjTipoProcedimentoDTO, $mensagem);

    $mensagem = 'Prezado(a) usu�rio(a), voc� est� tentando desativar o Tipo de Processo "%s" '
      . 'que se encontra mapeado para o Tipo de Processo Padr�o. '
      . 'Para continuar com essa a��o � necess�rio alterar o Tipo de Processo Padr�o. '
      . 'O Tipo de Processo padr�o se encontra dispon�vel em: '
      . 'Administra��o -> Tramita GOV.BR -> Mapeamento de Tipos de Processo -> Relacionamento entre Unidades';

    $objPenParametroRN = new PenParametroRN();
    $objPenParametroRN->validarAcaoTipoProcessoPadrao($arrObjTipoProcedimentoDTO, $mensagem);
  }

  /**
   * @param array $arrObjTipoProcedimentoDTO
   * @return void
   */
  public function excluirTipoProcesso($arrObjTipoProcedimentoDTO)
  {
    if(!PENIntegracao::verificarCompatibilidadeConfiguracoes()){
      return false;
    }
    
    $mensagem = "Prezado(a) usu�rio(a), voc� est� tentando excluir um Tipo de Processo que se encontra mapeado para o(s) relacionamento(s) "
      ."\"%s\". Para continuar com essa a��o � necess�rio remover do(s) mapeamentos "
      ."mencionados o Tipo de Processo: \"%s\".";

    $objMapeamentoTipoProcedimentoRN = new PenMapTipoProcedimentoRN();
    $objMapeamentoTipoProcedimentoRN->validarAcaoTipoProcesso($arrObjTipoProcedimentoDTO, $mensagem);

    $mensagem = 'Prezado(a) usu�rio(a), voc� est� tentando excluir o Tipo de Processo "%s" '
      . 'que se encontra mapeado para o Tipo de Processo Padr�o. '
      . 'Para continuar com essa a��o � necess�rio alterar o Tipo de Processo Padr�o. '
      . 'O Tipo de Processo padr�o se encontra dispon�vel em: '
      . 'Administra��o -> Tramita GOV.BR -> Mapeamento de Tipos de Processo -> Relacionamento entre Unidades';

    $objPenParametroRN = new PenParametroRN();
    $objPenParametroRN->validarAcaoTipoProcessoPadrao($arrObjTipoProcedimentoDTO, $mensagem);
  }

  /**
   * M�todo respons�vel de criar listagem de item para XML
   */
  public static function gerarXMLItensArrInfraDTOAutoCompletar(
    $arr,
    $strAtributoId,
    $strAtributoDescricao,
    $strAtributoComplemento = null,
    $strAtributoGrupo = null
  ) {
    $xml = '';
    $xml .= '<itens>';
    if ($arr !== null && $arr['itens']) {
      foreach ($arr['itens'] as $dto) {
        $xml .= '<item id="' . self::formatarXMLAjax($dto->get($strAtributoId)) . '"';
        $xml .= ' descricao="' . self::formatarXMLAjax($dto->get($strAtributoDescricao)) . '"';

        if ($strAtributoComplemento !== null) {
          $xml .= ' complemento="' . self::formatarXMLAjax($dto->get($strAtributoComplemento)) . '"';
        }

        if ($strAtributoGrupo !== null) {
          $xml .= ' grupo="' . self::formatarXMLAjax($dto->get($strAtributoGrupo)) . '"';
        }

        $xml .= '></item>';
      }
    }
    if ($arr !== null && $arr['diferencaDeRegistros'] && $arr['diferencaDeRegistros'] > 0) {
      $xml .= '<item disabled="" id="diferencaDeRegistros"';
      $xml .= ' grupo="autoCompletar" descricao="... outro(s) ' . $arr['diferencaDeRegistros'] . ' resultado(s) identificado(s),';
      $xml .= ' favor refinar a pesquisa."';
      $xml .= '></item>';
    }
    $xml .= '</itens>';
    return $xml;
  }

  /**
   * Método de formatação para caracteres especiais XML
   */
  private static function formatarXMLAjax($str)
  {
    if (!is_numeric($str)){
      $str = str_replace('&', '&amp;', $str);
      $str = str_replace('<', '&amp;lt;', $str);
      $str = str_replace('>', '&amp;gt;', $str);
      $str = str_replace('\"', '&amp;quot;', $str);
      $str = str_replace('"', '&amp;quot;', $str);
      //$str = str_replace("\n",'_',$str);
    }
    return $str;
  }

  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
  public function processarControlador($strAcao)
  {
    //Configura��o de p�ginas do contexto da �rvore do processo para apresenta��o de erro de forma correta
    $bolArvore = in_array($strAcao, array('pen_procedimento_estado'));
    PaginaSEI::getInstance()->setBolArvore($bolArvore);

    if (strpos($strAcao, 'pen_') === false) {
      return false;
    }

    if(!PENIntegracao::verificarCompatibilidadeConfiguracoes()){
      return false;
    }

    switch ($strAcao) {
      case 'pen_procedimento_expedir':
        require_once dirname(__FILE__) . '/pen_procedimento_expedir.php';
          break;

      case 'pen_tramite_bloco_listar':
      case 'md_pen_tramita_em_bloco':
      case 'md_pen_tramita_em_bloco_excluir':
      case 'pen_tramite_em_bloco_cancelar':
        require_once dirname(__FILE__) . '/pen_tramite_bloco_listar.php';
          break;

      case 'pen_tramite_em_bloco_cadastrar':
      case 'pen_tramite_em_bloco_alterar':
        require_once dirname(__FILE__) . '/pen_tramite_em_bloco_cadastrar.php';
          break;
     
      case 'pen_tramita_em_bloco_protocolo_excluir':
      case 'pen_tramita_em_bloco_protocolo_listar':
          require_once dirname(__FILE__) . '/pen_tramita_em_bloco_protocolo_listar.php';
          break;

      case 'pen_incluir_processo_em_bloco_tramite':
      case 'pen_tramita_em_bloco_adicionar':
        require_once dirname(__FILE__) . '/pen_tramite_processo_em_bloco_cadastrar.php';
          break;

      case 'pen_unidade_sel_expedir_procedimento':
        require_once dirname(__FILE__) . '/pen_unidade_sel_expedir_procedimento.php';
          break;

      case 'pen_procedimento_processo_anexado':
        require_once dirname(__FILE__) . '/pen_procedimento_processo_anexado.php';
          break;

      case 'pen_procedimento_cancelar_expedir':
        require_once dirname(__FILE__) . '/pen_procedimento_cancelar_expedir.php';
          break;

      case 'pen_procedimento_expedido_listar':
        require_once dirname(__FILE__) . '/pen_procedimento_expedido_listar.php';
          break;

      case 'pen_map_tipo_documento_envio_listar':
      case 'pen_map_tipo_documento_envio_excluir':
      case 'pen_map_tipo_documento_envio_desativar':
      case 'pen_map_tipo_documento_envio_ativar':
        require_once dirname(__FILE__) . '/pen_map_tipo_documento_envio_listar.php';
          break;

      case 'pen_map_tipo_documento_envio_cadastrar':
      case 'pen_map_tipo_documento_envio_visualizar':
        require_once dirname(__FILE__) . '/pen_map_tipo_documento_envio_cadastrar.php';
          break;

      case 'pen_map_tipo_documento_recebimento_listar':
      case 'pen_map_tipo_documento_recebimento_excluir':
        require_once dirname(__FILE__) . '/pen_map_tipo_documento_recebimento_listar.php';
          break;

      case 'pen_map_tipo_documento_recebimento_cadastrar':
      case 'pen_map_tipo_documento_recebimento_visualizar':
        require_once dirname(__FILE__) . '/pen_map_tipo_documento_recebimento_cadastrar.php';
          break;

      case 'pen_apensados_selecionar_expedir_procedimento':
        require_once dirname(__FILE__) . '/apensados_selecionar_expedir_procedimento.php';
          break;

      case 'pen_unidades_administrativas_externas_selecionar_expedir_procedimento':
        //verifica qual o tipo de sele��o passado para carregar o arquivo especifico.
        if($_GET['tipo_pesquisa'] == 1){
          require_once dirname(__FILE__) . '/pen_unidades_administrativas_selecionar_expedir_procedimento.php';
        }else {
          require_once dirname(__FILE__) . '/pen_unidades_administrativas_pesquisa_textual_expedir_procedimento.php';
        }
          break;

      case 'pen_procedimento_estado':
        require_once dirname(__FILE__) . '/pen_procedimento_estado.php';
          break;

        // Mapeamento de Hip�teses Legais de Envio
      case 'pen_map_hipotese_legal_envio_cadastrar':
      case 'pen_map_hipotese_legal_envio_visualizar':
        require_once dirname(__FILE__) . '/pen_map_hipotese_legal_envio_cadastrar.php';
          break;

      case 'pen_map_hipotese_legal_envio_listar':
      case 'pen_map_hipotese_legal_envio_excluir':
        require_once dirname(__FILE__) . '/pen_map_hipotese_legal_envio_listar.php';
          break;

        // Mapeamento de Hip�teses Legais de Recebimento
      case 'pen_map_hipotese_legal_recebimento_cadastrar':
      case 'pen_map_hipotese_legal_recebimento_visualizar':
        require_once dirname(__FILE__) . '/pen_map_hipotese_legal_recebimento_cadastrar.php';
          break;

      case 'pen_map_hipotese_legal_recebimento_listar':
      case 'pen_map_hipotese_legal_recebimento_excluir':
        require_once dirname(__FILE__) . '/pen_map_hipotese_legal_recebimento_listar.php';
          break;

      case 'pen_map_hipotese_legal_padrao_cadastrar':
      case 'pen_map_hipotese_legal_padrao_visualizar':
        require_once dirname(__FILE__) . '/pen_map_hipotese_legal_padrao_cadastrar.php';
          break;

      case 'pen_map_unidade_cadastrar':
      case 'pen_map_unidade_visualizar':
        require_once dirname(__FILE__) . '/pen_map_unidade_cadastrar.php';
          break;

      case 'pen_map_orgaos_externos_salvar':
      case 'pen_map_orgaos_externos_atualizar':
      case 'pen_map_orgaos_externos_cadastrar':
      case 'pen_map_orgaos_externos_visualizar':
        require_once dirname(__FILE__) . '/pen_map_orgaos_externos_cadastrar.php';
          break;

      case 'pen_map_orgaos_externos_reativar':
      case 'pen_map_orgaos_externos_desativar':  
      case 'pen_map_orgaos_externos_listar':
      case 'pen_map_orgaos_externos_excluir':
      case 'pen_map_orgaos_importar_tipos_processos':
        require_once dirname(__FILE__) . '/pen_map_orgaos_externos_listar.php';
          break;

      case 'pen_map_tipo_processo_padrao':
      case 'pen_map_tipo_processo_padrao_salvar':
        require_once dirname(__FILE__) . '/pen_map_tipo_processo_padrao.php';
          break;

      case 'pen_map_tipo_processo_reativar':
        require_once dirname(__FILE__) . '/pen_map_tipo_processo_reativar.php';
          break;

      case 'pen_map_orgaos_exportar_tipos_processos':
        require_once dirname(__FILE__) . '/pen_tipo_procedimento_lista.php';
          break;

      case 'pen_map_orgaos_externos_mapeamento_desativar':
      case 'pen_map_orgaos_externos_mapeamento':
      case 'pen_map_orgaos_externos_mapeamento_gerenciar':
      case 'pen_map_orgaos_externos_mapeamento_excluir':
        require_once dirname(__FILE__) . '/pen_map_orgaos_mapeamento_tipo_listar.php';
          break;

      case 'pen_map_unidade_listar':
      case 'pen_map_unidade_excluir':
        require_once dirname(__FILE__) . '/pen_map_unidade_listar.php';
          break;

      case 'pen_parametros_configuracao':
      case 'pen_parametros_configuracao_salvar':
        require_once dirname(__FILE__) . '/pen_parametros_configuracao.php';
          break;

      case 'pen_map_tipo_documento_envio_padrao_atribuir':
      case 'pen_map_tipo_documento_envio_padrao_consultar':
        require_once dirname(__FILE__) . '/pen_map_tipo_documento_envio_padrao.php';
          break;

      case 'pen_map_tipo_doc_recebimento_padrao_atribuir':
      case 'pen_map_tipo_doc_recebimento_padrao_consultar':
        require_once dirname(__FILE__) . '/pen_map_tipo_doc_recebimento_padrao.php';
          break;

      case 'pen_envio_processo_lote_cadastrar':
        require_once dirname(__FILE__) . '/pen_envio_processo_lote_cadastrar.php';
          break;

      case 'pen_expedir_lote':
        require_once dirname(__FILE__) . '/pen_expedir_lote.php';
          break;

      case 'pen_expedir_lote_listar':
        require_once dirname(__FILE__) . '/pen_expedir_lote_listar.php';
          break;

      case 'pen_map_envio_parcial_listar':
      case 'pen_map_envio_parcial_excluir':
          require_once dirname(__FILE__) . '/pen_map_envio_parcial_listar.php';
          break;

      case 'pen_map_envio_parcial_salvar':
      case 'pen_map_envio_parcial_cadastrar':
      case 'pen_map_envio_parcial_visualizar':
          require_once dirname(__FILE__) . '/pen_map_envio_parcial_cadastrar.php';
          break;

      default:
          return false;

    }
    return true;
  }


  public function processarControladorAjax($strAcao) {
    $xml = null;

    switch ($_GET['acao_ajax']) {

      case 'pen_unidade_auto_completar_expedir_procedimento':
        $bolPermiteEnvio = false;
        if ($_GET['acao'] != 'pen_procedimento_expedir') {
          $bolPermiteEnvio = true;
        }

        $arrObjEstruturaDTO = (array) ProcessoEletronicoINT::autoCompletarEstruturasAutoCompletar($_POST['id_repositorio'], $_POST['palavras_pesquisa'], $bolPermiteEnvio);

        if (count($arrObjEstruturaDTO['itens']) > 0) {
          $xml = self::gerarXMLItensArrInfraDTOAutoCompletar($arrObjEstruturaDTO, 'NumeroDeIdentificacaoDaEstrutura', 'Nome');
        } else {
          return '<itens><item id="0" descricao="Unidade n�o Encontrada."></item></itens>';
        }
          break;

      case 'pen_unidade_auto_completar_mapeados':
        // DTO de paginao
        $objPenUnidadeDTOFiltro = new PenUnidadeDTO();
        $objPenUnidadeDTOFiltro->retStrSiglaUnidadeRH();
        $objPenUnidadeDTOFiltro->retStrNomeUnidadeRH();
        $objPenUnidadeDTOFiltro->retNumIdUnidade();
        $objPenUnidadeDTOFiltro->retNumIdUnidadeRH();

          // Filtragem
        if(!empty($_POST['palavras_pesquisa']) && $_POST['palavras_pesquisa'] !== 'null') {
          $objPenUnidadeDTOFiltro->setStrNomeUnidadeRH('%'.$_POST['palavras_pesquisa'].'%', InfraDTO::$OPER_LIKE);
        }

        $objPenUnidadeRN = new PenUnidadeRN();
        $objArrPenUnidadeDTO = (array) $objPenUnidadeRN->listar($objPenUnidadeDTOFiltro);
        if (count($objArrPenUnidadeDTO) > 0) {
          foreach ($objArrPenUnidadeDTO as $dto) {
            $dto->setNumIdUnidadeMap($dto->getNumIdUnidadeRH());
            $dto->setStrDescricaoMap($dto->getStrNomeUnidadeRH(). '-' . $dto->getStrSiglaUnidadeRH());
          }
          $xml = InfraAjax::gerarXMLItensArrInfraDTO($objArrPenUnidadeDTO, 'IdUnidadeMap', 'DescricaoMap');
        } else {
          return '<itens><item id="0" descricao="Unidade n�o Encontrada."></item></itens>';
        }
          break;

      case 'pen_apensados_auto_completar_expedir_procedimento':
        $dblIdProcedimentoAtual = $_POST['id_procedimento_atual'];
        $numIdUnidadeAtual = SessaoSEI::getInstance()->getNumIdUnidadeAtual();
        $arrObjProcedimentoDTO = ProcessoEletronicoINT::autoCompletarProcessosApensados($dblIdProcedimentoAtual, $numIdUnidadeAtual, $_POST['palavras_pesquisa']);
        $xml = InfraAjax::gerarXMLItensArrInfraDTO($arrObjProcedimentoDTO, 'IdProtocolo', 'ProtocoloFormatadoProtocolo');
          break;


      case 'pen_procedimento_expedir_validar':
        require_once dirname(__FILE__) . '/pen_procedimento_expedir_validar.php';
          break;

      case 'pen_validar_expedir_lote':
        require_once dirname(__FILE__) . '/pen_validar_expedir_lote.php';
          break;

      case 'pen_procedimento_expedir_cancelar':
        $numIdTramite = $_POST['id_tramite'];
        $objProcessoEletronicoRN = new ProcessoEletronicoRN();
        $result = json_encode($objProcessoEletronicoRN->cancelarTramite($numIdTramite));
        InfraAjax::enviarJSON($result);
          exit(0);
        break;

      case 'pen_pesquisar_unidades_administrativas_estrutura_pai':
        $idRepositorioEstruturaOrganizacional = $_POST['idRepositorioEstruturaOrganizacional'];
        $numeroDeIdentificacaoDaEstrutura = $_POST['numeroDeIdentificacaoDaEstrutura'];

        $objProcessoEletronicoRN = new ProcessoEletronicoRN();
        $arrEstruturas = $objProcessoEletronicoRN->consultarEstruturasPorEstruturaPai($idRepositorioEstruturaOrganizacional, $numeroDeIdentificacaoDaEstrutura == "" ? null : $numeroDeIdentificacaoDaEstrutura);

        print json_encode($arrEstruturas);
          exit(0);
        break;


      case 'pen_pesquisar_unidades_administrativas_estrutura_pai_textual':
        $registrosPorPagina = 50;
        $idRepositorioEstruturaOrganizacional = $_POST['idRepositorioEstruturaOrganizacional'];
        $numeroDeIdentificacaoDaEstrutura     = $_POST['numeroDeIdentificacaoDaEstrutura'];
        $siglaUnidade = ($_POST['siglaUnidade'] == '') ? null : utf8_encode($_POST['siglaUnidade']);
        $nomeUnidade  = ($_POST['nomeUnidade']  == '') ? null : utf8_encode($_POST['nomeUnidade']);
        $offset       = $_POST['offset'] * $registrosPorPagina;

        $objProcessoEletronicoRN = new ProcessoEletronicoRN();
        $arrObjEstruturaDTO = $objProcessoEletronicoRN->listarEstruturas($idRepositorioEstruturaOrganizacional, null, $numeroDeIdentificacaoDaEstrutura, $nomeUnidade, $siglaUnidade, $offset, $registrosPorPagina);

        $interface = new ProcessoEletronicoINT();
        //Gera a hierarquia de SIGLAS das estruturas
        $arrHierarquiaEstruturaDTO = $interface->gerarHierarquiaEstruturas($arrObjEstruturaDTO);

        $arrEstruturas['estrutura'] = [];
        if(!is_null($arrHierarquiaEstruturaDTO[0])){
          foreach ($arrHierarquiaEstruturaDTO as $key => $estrutura) {
            //Monta um array com as estruturas para retornar o JSON
            $arrEstruturas['estrutura'][$key]['nome'] = utf8_encode($estrutura->get('Nome'));
            $arrEstruturas['estrutura'][$key]['numeroDeIdentificacaoDaEstrutura'] = $estrutura->get('NumeroDeIdentificacaoDaEstrutura');
            $arrEstruturas['estrutura'][$key]['sigla'] = utf8_encode($estrutura->get('Sigla'));
            $arrEstruturas['estrutura'][$key]['ativo'] = $estrutura->get('Ativo');
            $arrEstruturas['estrutura'][$key]['aptoParaReceberTramites'] = $estrutura->get('AptoParaReceberTramites');
            $arrEstruturas['estrutura'][$key]['codigoNoOrgaoEntidade'] = $estrutura->get('CodigoNoOrgaoEntidade');

          }
          $arrEstruturas['totalDeRegistros']   = $estrutura->get('TotalDeRegistros');
          $arrEstruturas['registrosPorPagina'] = $registrosPorPagina;
        }

        print json_encode($arrEstruturas);
          exit(0);
        break;
    }

    return $xml;
  }


  public function processarControladorWebServices($servico)
  {
    $strArq = null;
    switch ($_GET['servico']) {
      case 'modpen':
        $strArq =  dirname(__FILE__) . '/ws/modpen.wsdl';
          break;
    }

    return $strArq;
  }


  /**
   * M�todo respons�vel por recuperar a hierarquia da unidade e montar o seu nome com as SIGLAS da hierarquia
   * @param $idRepositorioEstruturaOrganizacional
   * @param $arrEstruturas
   * @return mixed
   * @throws InfraException
   */
  private function obterHierarquiaEstruturaDeUnidadeExterna($idRepositorioEstruturaOrganizacional, $arrEstruturas)
  {
    //Monta o nome da unidade com a hierarquia de SIGLAS
    $objProcessoEletronicoRN = new ProcessoEletronicoRN();
    foreach ($arrEstruturas as $key => $estrutura) {
      if(!is_null($estrutura)) {
        $arrObjEstruturaDTO = $objProcessoEletronicoRN->listarEstruturas($idRepositorioEstruturaOrganizacional, $estrutura->numeroDeIdentificacaoDaEstrutura);
        if (!is_null($arrObjEstruturaDTO[0])) {
          $interface = new ProcessoEletronicoINT();
          $arrHierarquiaEstruturaDTO = $interface->gerarHierarquiaEstruturas($arrObjEstruturaDTO);
          $arrEstruturas[$key]->nome = utf8_encode($arrHierarquiaEstruturaDTO[0]->get('Nome'));
        }
      }
    }

    return $arrEstruturas;
  }

  public static function getDiretorio()
  {
    $arrConfig = ConfiguracaoSEI::getInstance()->getValor('SEI', 'Modulos');
    $strModulo = $arrConfig['PENIntegracao'];
      return "modulos/".$strModulo;
  }

  /**
   * Verifica a compatibilidade e correta configuracao do m�dulo de Barramento, registrando mensagem de alerta no log do sistema
   *
   * Regras de verifica��o da disponibilidade do PEN n�o devem ser aplicadas neste ponto pelo risco de erro geral no sistema em
   * caso de indisponibilidade moment�nea do Barramento de Servi�os.
   */
  public static function verificarCompatibilidadeConfiguracoes(){
    $objVerificadorInstalacaoRN = new VerificadorInstalacaoRN();

    try {
      $objVerificadorInstalacaoRN->verificarArquivoConfiguracao();
    } catch (\Exception $e) {
      LogSEI::getInstance()->gravar($e, LogSEI::$ERRO);
      return false;
    }

    try {
      $objVerificadorInstalacaoRN->verificarCompatibilidadeModulo();
    } catch (\Exception $e) {
      LogSEI::getInstance()->gravar($e, LogSEI::$AVISO);
      return false;
    }

    // Desativado verificações de compatibilidade do banco de dados por não ser todas as versões
    // que necessitam mudanças no banco de dados
    try {
        $objVerificadorInstalacaoRN->verificarCompatibilidadeBanco();
    } catch (\Exception $e) {
        LogSEI::getInstance()->gravar($e, LogSEI::$AVISO);
        return false;
    }

    return true;
  }

  /**
   * Compara duas diferentes versões do sistem para avaliar a precedência de ambas
   *
   * Normaliza o formato de número de versão considerando dois caracteres para cada item (3.0.15 -> 030015)
   * - Se resultado for IGUAL a 0, versões iguais
   * - Se resultado for MAIOR que 0, versão 1 é posterior a versão 2
   * - Se resultado for MENOR que 0, versão 1 é anterior a versão 2
   */
  public static function compararVersoes($strVersao1, $strVersao2){
    $numVersao1 = explode('.', $strVersao1);
      $numVersao1 = array_map(function($item){ return str_pad($item, 2, '0', STR_PAD_LEFT);
      }, $numVersao1);
    $numVersao1 = intval(join($numVersao1));

    $numVersao2 = explode('.', $strVersao2);
      $numVersao2 = array_map(function($item){ return str_pad($item, 2, '0', STR_PAD_LEFT);
      }, $numVersao2);
    $numVersao2 = intval(join($numVersao2));

    return $numVersao1 - $numVersao2;
  }


  public function processarPendencias()
  {
    SessaoSEI::getInstance(false);
    ProcessarPendenciasRN::getInstance()->processarPendencias();
  }
}
class ModuloIncompativelException extends InfraException { }