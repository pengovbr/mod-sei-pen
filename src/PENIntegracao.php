<?php

// Identificação da versão do módulo. Este deverá ser atualizado e sincronizado com constante VERSAO_MODULO
define("VERSAO_MODULO_PEN", "4.1.0");

class PENIntegracao extends SeiIntegracao
{
    const VERSAO_MODULO = VERSAO_MODULO_PEN;
    const PARAMETRO_VERSAO_MODULO_ANTIGO = 'PEN_VERSAO_MODULO_SEI';
    const PARAMETRO_VERSAO_MODULO = 'VERSAO_MODULO_PEN';

  private static $instance;

  public static function getInstance()
    {
    if (self::$instance == null) {
        self::$instance = new PENIntegracao();
    }

      return self::$instance;
  }

  public function getNome()
    {
      return 'Integração Tramita GOV.BR';
  }


  public function getVersao()
    {
      return self::VERSAO_MODULO;
  }


  public function getInstituicao()
    {
      return 'Ministério da Gestão e da Inovação em Serviços Públicos - MGI';
  }

  public function obterDiretorioIconesMenu()
    {
      return self::getDiretorioImagens() . '/menu/';
  }

  /**
   * Valida se o processo anexado pode participar da anexacao quando houver tramite no Tramita GOV.BR.
   *
   * @param ProcedimentoAPI $objProcedimentoAPIPrincipal
   * @param ProcedimentoAPI $objProcedimentoAPIAnexado
   * @return null
   */
  public function anexarProcesso(ProcedimentoAPI $objProcedimentoAPIPrincipal, ProcedimentoAPI $objProcedimentoAPIAnexado)
    {
      $numIdAndamento = $this->consultarStatusTramitaGovBrProcesso($objProcedimentoAPIAnexado->getIdProcedimento());

      if ($this->possuiTramitacaoEmAndamento($numIdAndamento)) {
        $this->lancarValidacaoImpedimentoAnexacao(
          $objProcedimentoAPIPrincipal,
          $objProcedimentoAPIAnexado,
          'andamento'
        );
      }

      if ($this->possuiTramitacaoConcluidaComSucesso($numIdAndamento)) {
        $this->lancarValidacaoImpedimentoAnexacao(
          $objProcedimentoAPIPrincipal,
          $objProcedimentoAPIAnexado,
          'sucesso'
        );
      }

      return null;
  }

  /**
   * Consulta a situacao atual do ultimo tramite do processo no Tramita GOV.BR.
   *
   * @param int $dblIdProcedimento
   * @return int|null
   */
  private function consultarStatusTramitaGovBrProcesso($dblIdProcedimento)
    {
      if (empty($dblIdProcedimento)) {
        return null;
      }

      $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
      $objProcessoEletronicoDTO->setDblIdProcedimento($dblIdProcedimento);

      $objTramiteBD = new TramiteBD(BancoSEI::getInstance());
      $objTramiteDTO = $objTramiteBD->consultarUltimoTramite($objProcessoEletronicoDTO);

      if ($objTramiteDTO == null) {
        return null;
      }

      $numIdTramite = $objTramiteDTO->getNumIdTramite();
      if (empty($numIdTramite)) {
        return null;
      }

      // aqui consulta o tramite
      $objProcessoEletronicoRN = new ProcessoEletronicoRN();
      $objTramiteTramitaGovBr = $objProcessoEletronicoRN->consultarTramites($numIdTramite);

      if (empty($objTramiteTramitaGovBr) || !isset($objTramiteTramitaGovBr[0])) {
        return null;
      }

      if (!isset($objTramiteTramitaGovBr[0]->situacaoAtual)) {
        return null;
      }

      return $objTramiteTramitaGovBr[0]->situacaoAtual;
  }

  /**
   * Indica se o status informado representa tramite em andamento.
   *
   * @param int|null $numIdAndamento
   * @return bool
   */
  private function possuiTramitacaoEmAndamento($numIdAndamento)
    {
      return in_array($numIdAndamento, $this->getStatusTramitacaoEmAndamento());
  }

  /**
   * Indica se o status informado representa tramite concluido com sucesso.
   *
   * @param int|null $numIdAndamento
   * @return bool
   */
  private function possuiTramitacaoConcluidaComSucesso($numIdAndamento)
    {
      return $numIdAndamento == ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE;
  }

  /**
   * Retorna os status do Tramita GOV.BR que impedem anexacao por tramite em andamento.
   *
   * @return array
   */
  private function getStatusTramitacaoEmAndamento()
    {
      return [
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_ENVIADOS_REMETENTE,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_METADADOS_RECEBIDO_DESTINATARIO,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_ENVIADO_DESTINATARIO,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO,
      ];
  }

  /**
   * Lanca a validacao de impedimento de anexacao com a mensagem apropriada para a tela.
   *
   * @param ProcedimentoAPI $objProcedimentoAPIPrincipal
   * @param ProcedimentoAPI $objProcedimentoAPIAnexado
   * @param string $strTipoImpedimento
   * @return void
   */
  private function lancarValidacaoImpedimentoAnexacao(ProcedimentoAPI $objProcedimentoAPIPrincipal, ProcedimentoAPI $objProcedimentoAPIAnexado, $strTipoImpedimento)
    {
      $objInfraException = new InfraException();
      $objInfraException->lancarValidacao($this->montarMensagemImpedimentoAnexacao(
        $objProcedimentoAPIPrincipal,
        $objProcedimentoAPIAnexado,
        $strTipoImpedimento
      ));
  }

  /**
   * Monta a mensagem apresentada quando a anexacao e impedida pelo Tramita GOV.BR.
   *
   * @param ProcedimentoAPI $objProcedimentoAPIPrincipal
   * @param ProcedimentoAPI $objProcedimentoAPIAnexado
   * @param string $strTipoImpedimento
   * @return string
   */
  private function montarMensagemImpedimentoAnexacao(ProcedimentoAPI $objProcedimentoAPIPrincipal, ProcedimentoAPI $objProcedimentoAPIAnexado, $strTipoImpedimento)
    {
      $strMensagem = 'Tramita GOV.BR: O processo ' . $objProcedimentoAPIAnexado->getNumeroProtocolo()
        . ' não pode ser anexado ao processo ' . $objProcedimentoAPIPrincipal->getNumeroProtocolo();

      if ($strTipoImpedimento == 'andamento') {
        return $strMensagem . ' pois encontra-se com tramitação em andamento para outro órgão.';
      }

      return $strMensagem . ' pois já foi tramitado, com sucesso, para outro órgão.';
  }

  public function inicializar($strVersaoSEI)
    {
    if (!defined('DIR_SEI_WEB')) {
        define('DIR_SEI_WEB', realpath(DIR_SEI_CONFIG.'/../web'));
    }      
      include_once DIR_SEI_CONFIG . '/mod-pen/ConfiguracaoModPEN.php';
  }

  public function montarBotaoControleProcessos()
    {

    if(!PENIntegracao::verificarCompatibilidadeConfiguracoes()) {
        return false;
    }

      $objSessaoSEI = SessaoSEI::getInstance();
      $strAcoesProcedimento = "";

      $bolAcaoIncluirProcessoEmBloco = $objSessaoSEI->verificarPermissao('pen_incluir_processo_em_bloco_tramite');
      $bolAcaoAcessarTramiteEmBloco = $objSessaoSEI->verificarPermissao('md_pen_tramita_em_bloco');

      $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
      $bolBlocoAbertoUnidade = false; 
    if ($bolAcaoAcessarTramiteEmBloco) {
      $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
      $objTramiteEmBlocoDTO->setStrStaEstado(TramiteEmBlocoRN::$TE_ABERTO);
      $objTramiteEmBlocoDTO->setNumIdUnidade($objSessaoSEI->getNumIdUnidadeAtual());
      $objTramiteEmBlocoDTO->retNumId();
      $objTramiteEmBlocoDTO->retNumIdUnidade();
      $objTramiteEmBlocoDTO->retStrDescricao();
    
      $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
      if (count($objTramiteEmBlocoRN->listar($objTramiteEmBlocoDTO)) > 0) {
          $bolBlocoAbertoUnidade = true;
      }
    }

    if ($bolAcaoIncluirProcessoEmBloco && $bolBlocoAbertoUnidade) {
        $objPaginaSEI = PaginaSEI::getInstance();

        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDistinct(true);
        $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
        $objAtividadeDTO->setDthConclusao(null);
        $objAtividadeDTO->retNumIdUnidade();

        $objAtividadeRN = new AtividadeRN();
        $numRegistros = $objAtividadeRN->contarRN0035($objAtividadeDTO);

        // Verifica se existe uma unidade mapeada
        $bolUnidadeMapeada = $objTramiteEmBlocoRN->existeUnidadeMapeadaParaUnidadeLogada();
      
      if ($numRegistros > 0 && $bolUnidadeMapeada) {
          $numTabBotao = $objPaginaSEI->getProxTabBarraComandosSuperior();
          $strAcoesProcedimento .= '<a href="#" onclick="return acaoControleProcessos(\'' . $objSessaoSEI->assinarLink('controlador.php?acao=pen_tramita_em_bloco_adicionar&acao_origem=' . $_GET['acao'] . '&acao_retorno=' . $_GET['acao']) . '\', true, false);" tabindex="' . $numTabBotao . '" class="botaoSEI">';
          $strAcoesProcedimento .= '<img class="infraCorBarraSistema" src="' . ProcessoEletronicoINT::getCaminhoIcone("/pen_processo_bloco.svg", $this->getDiretorioImagens()) . '" class="infraCorBarraSistema" alt="Incluir Processos no Bloco de Trâmite" title="Incluir Processos no Bloco de Trâmite" />';
      }
    }

      return [$strAcoesProcedimento];
  }

  public function montarBotaoProcesso(ProcedimentoAPI $objSeiIntegracaoDTO)
    {
    if(!PENIntegracao::verificarCompatibilidadeConfiguracoes()) {
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

      //Verifica se o processo encontra-se aberto na unidade atual
      $objAtividadeRN = new AtividadeRN();
      $objPesquisaPendenciaDTO = new PesquisaPendenciaDTO();
      $objPesquisaPendenciaDTO->setDblIdProtocolo($dblIdProcedimento);
      $objPesquisaPendenciaDTO->setNumIdUsuario($numIdUsuario);
      $objPesquisaPendenciaDTO->setNumIdUnidade($numIdUnidadeAtual);
      $objPesquisaPendenciaDTO->setStrSinMontandoArvore('N');
      $arrObjProcedimentoDTO = $objAtividadeRN->listarPendenciasRN0754($objPesquisaPendenciaDTO);
      $bolFlagAberto = count($arrObjProcedimentoDTO) == 1;

      //Verificação da Restrição de Acesso a Funcionalidade
      $bolAcaoExpedirProcesso = $objSessaoSEI->verificarPermissao('pen_procedimento_expedir');
    
      $objExpedirProcedimentoRN = new ExpedirProcedimentoRN();
      $objProcedimentoDTO = $objExpedirProcedimentoRN->consultarProcedimento($dblIdProcedimento);

      $bolProcessoEstadoNormal = !in_array($objProcedimentoDTO->getStrStaEstadoProtocolo(), [ProtocoloRN::$TE_PROCEDIMENTO_SOBRESTADO, ProtocoloRN::$TE_PROCEDIMENTO_BLOQUEADO]);

      $bolAcaoAcessarTramiteEmBloco = $objSessaoSEI->verificarPermissao('md_pen_tramita_em_bloco');
      $bolBlocoAbertoUnidade = false;
      $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
      if ($bolAcaoAcessarTramiteEmBloco) {
        $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
        $objTramiteEmBlocoDTO->setStrStaEstado(TramiteEmBlocoRN::$TE_ABERTO);
        $objTramiteEmBlocoDTO->setNumIdUnidade($objSessaoSEI->getNumIdUnidadeAtual());
        $objTramiteEmBlocoDTO->retNumId();
        $objTramiteEmBlocoDTO->retNumIdUnidade();
        $objTramiteEmBlocoDTO->retStrDescricao();
        PaginaSEI::getInstance()->prepararOrdenacao($objTramiteEmBlocoDTO, 'Id', InfraDTO::$TIPO_ORDENACAO_DESC);
    
        if (count($objTramiteEmBlocoRN->listar($objTramiteEmBlocoDTO)) > 0) {
            $bolBlocoAbertoUnidade = true;
        }
      }

      $bolAcaoAcessarTramiteEmBlocoProtocoloListar = $objSessaoSEI->verificarPermissao('pen_tramita_em_bloco_protocolo_listar');
      $bolProcessoEmBloco = false;
      if ($bolAcaoAcessarTramiteEmBlocoProtocoloListar) {
        $objPenBlocoProcessoDTO = new PenBlocoProcessoDTO();
        $objPenBlocoProcessoDTO->setDblIdProtocolo($dblIdProcedimento);
        $objPenBlocoProcessoDTO->setNumIdUnidade($objSessaoSEI->getNumIdUnidadeAtual());
        $objPenBlocoProcessoDTO->retNumIdAndamento();
        $objPenBlocoProcessoDTO->retNumIdBloco();

        $objPenBlocoProcessoRN = new PenBlocoProcessoRN();
        $arrObjPenBlocoProcessoDTO = $objPenBlocoProcessoRN->listar($objPenBlocoProcessoDTO);
        if (count($arrObjPenBlocoProcessoDTO) > 0) {
            $concluido = [ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CIENCIA_RECUSA, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO_AUTOMATICAMENTE, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE];
          foreach ($arrObjPenBlocoProcessoDTO as $objBlocoProcessoDTO) {
            if (!in_array($objBlocoProcessoDTO->getNumIdAndamento(), $concluido)) {
              $bolProcessoEmBloco = true;
            }
          }
        }
      }

      // Verifica se existe uma unidade mapeada
      $bolUnidadeMapeada = $objTramiteEmBlocoRN->existeUnidadeMapeadaParaUnidadeLogada();

      //Apresenta o botão de expedir processo
    if ($bolUnidadeMapeada && !$bolProcessoEmBloco && $bolFlagAberto && $bolAcaoExpedirProcesso && $bolProcessoEstadoNormal) {
        $numTabBotao = $objPaginaSEI->getProxTabBarraComandosSuperior();
        $strAcoesProcedimento .= '<a id="validar_expedir_processo" href="' . $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_expedir&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_procedimento=' . $dblIdProcedimento . '&arvore=1')) . '" tabindex="' . $numTabBotao . '" class="botaoSEI"><img class="infraCorBarraSistema" src=' . ProcessoEletronicoINT::getCaminhoIcone("/pen_expedir_procedimento.gif", $this->getDiretorioImagens()) . ' alt="Envio Externo de Processo" title="Envio Externo de Processo" /></a>';
    }

      //Apresenta o botão de cancelar trâmite
      $objAtividadeDTO = $objExpedirProcedimentoRN->verificarProcessoEmExpedicao($objSeiIntegracaoDTO->getIdProcedimento());
    if ($objAtividadeDTO 
          && $objAtividadeDTO->getNumIdTarefa() == ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO) 
          && $objAtividadeDTO->getNumIdUnidade() == $numIdUnidadeAtual
      ) {
        $numTabBotao = $objPaginaSEI->getProxTabBarraComandosSuperior();
        $strAcoesProcedimento .= '<a href="' . $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_cancelar_expedir&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_procedimento=' . $dblIdProcedimento . '&arvore=1')) . '" tabindex="' . $numTabBotao . '" class="botaoSEI">';
        $strAcoesProcedimento .= '<img class="infraCorBarraSistema" src=' . ProcessoEletronicoINT::getCaminhoIcone("/pen_cancelar_tramite.gif", $this->getDiretorioImagens()) . '  alt="Cancelar Tramitação Externa" title="Cancelar Tramitação Externa" />';
        $strAcoesProcedimento .= '</a>';
    }
     
      //Apresenta o botão de incluir processo no bloco de trâmite
      $bolAcaoIncluirProcessoEmBloco = $objSessaoSEI->verificarPermissao('pen_incluir_processo_em_bloco_tramite');
    if ($bolUnidadeMapeada && !$bolProcessoEmBloco && $bolBlocoAbertoUnidade && $bolFlagAberto && $bolAcaoIncluirProcessoEmBloco && $bolProcessoEstadoNormal && $objProcedimentoDTO->getStrStaNivelAcessoGlobalProtocolo() != ProtocoloRN::$NA_SIGILOSO) {
        $objPenBlocoProcessoRN = new PenBlocoProcessoRN();
        $bolProcessoMultiplosOrgaos = $objPenBlocoProcessoRN->validarProcessoMultiplosOrgaosParaBloco($dblIdProcedimento) !== false;
      if (!$bolProcessoMultiplosOrgaos) {
          $numTabBotao = $objPaginaSEI->getProxTabBarraComandosSuperior();
          $strAcoesProcedimento .= '<a href="' . $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=pen_incluir_processo_em_bloco_tramite&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_procedimento=' . $dblIdProcedimento . '&arvore=1')) . '" tabindex="' . $numTabBotao . '" class="botaoSEI"> <img src="' . ProcessoEletronicoINT::getCaminhoIcone("/pen_processo_bloco.svg", $this->getDiretorioImagens()) . '" title="Incluir Processo no Bloco de Tramite" alt="Incluir Processo no Bloco de Tramite"/></a>';
      }
    }

      //Apresenta o botão de excluir processo no bloco de trâmite
      $bolAcaoExcluirProcessoEmBloco = $objSessaoSEI->verificarPermissao('pen_tramita_em_bloco_protocolo_excluir');
    if ($bolUnidadeMapeada && $bolProcessoEmBloco && $bolFlagAberto && $bolAcaoExcluirProcessoEmBloco && $bolProcessoEstadoNormal && $objProcedimentoDTO->getStrStaNivelAcessoGlobalProtocolo() != ProtocoloRN::$NA_SIGILOSO) {
        $numTabBotao = $objPaginaSEI->getProxTabBarraComandosSuperior();
        $strAcoesProcedimento .= '<a onclick="return confirm(\\\'Confirma a remoção do processo do bloco de trâmite externo?\\\');" href="' . $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=pen_excluir_processo_em_bloco_tramite&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_procedimento=' . $dblIdProcedimento . '&arvore=1')) . '" tabindex="' . $numTabBotao . '" class="botaoSEI"> <img src="'.ProcessoEletronicoINT::getCaminhoIcone("/pen_processo_bloco_excluir.svg", $this->getDiretorioImagens()) .'" title="Remover Processo do Bloco de Trâmite" alt="Remover Processo do Bloco de Trâmite"/></a>';
    }

    $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
    $objProcessoEletronicoDTO->setDblIdProcedimento($dblIdProcedimento);
    $objTramiteBD = new TramiteBD(BancoSEI::getInstance());

    $objTramiteDTO = $objTramiteBD->consultarPrimeiroTramite($objProcessoEletronicoDTO);

    if ($bolFlagAberto && $bolProcessoEstadoNormal && !is_null($objTramiteDTO) && $objProcedimentoDTO->getStrStaNivelAcessoGlobalProtocolo() != ProtocoloRN::$NA_SIGILOSO) {
      $objPenUnidadeDTO = new PenUnidadeDTO();
      $objPenUnidadeDTO->setNumIdUnidade($numIdUnidadeAtual);
      $objPenUnidadeDTO->retNumIdUnidadeRH();
      $objPenUnidadeRN = new PenUnidadeRN();
      $objPenUnidadeDTO = $objPenUnidadeRN->consultar($objPenUnidadeDTO);
      $numIdUnidadeRHAtual = !empty($objPenUnidadeDTO) ? $objPenUnidadeDTO->getNumIdUnidadeRH() : null;
      
      $bolUnidadeAtualEhDestinoRecebimento = !is_null($numIdUnidadeRHAtual) && $objTramiteDTO->getNumIdEstruturaDestino() == $numIdUnidadeRHAtual;
      $bolTramiteRecebimento = $objTramiteDTO->getStrStaTipoTramite() == ProcessoEletronicoRN::$STA_TIPO_TRAMITE_RECEBIMENTO;
      if ($bolTramiteRecebimento && $bolUnidadeAtualEhDestinoRecebimento) {
        $objProcessoEletronicoRN = new ProcessoEletronicoRN();
        if ($objProcessoEletronicoRN->validarProcessoMultiplosOrgaos($dblIdProcedimento)) {
          $objMetadados = $objProcessoEletronicoRN->solicitarMetadados($objTramiteDTO->getNumIdTramite());
          $numIdRepositorioOrigem = $objMetadados->remetente->identificacaoDoRepositorioDeEstruturas ?? null;
          $numIdUnidadeOrigem = $objMetadados->remetente->numeroDeIdentificacaoDaEstrutura ?? null;
          $objEnvioParcialRN = new PenRestricaoEnvioComponentesDigitaisRN();

          if ($objEnvioParcialRN->possuiMapeamentoEnvioParcialAtivoMultiplosOrgaos($numIdRepositorioOrigem, $numIdUnidadeOrigem)) {
            $strAcoesProcedimento .= '<a href="' . $objPaginaSEI->formatarXHTML($objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_sincronizar&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_procedimento=' . $dblIdProcedimento . '&arvore=1')) . '" tabindex="' . $numTabBotao . '" class="botaoSEI">';
            $strAcoesProcedimento .= '<img class="infraCorBarraSistema" style="padding: 3px 6px 0px 6px" src=' . ProcessoEletronicoINT::getCaminhoIcone("/sincronizar_processo.png", $this->getDiretorioImagens()) . '  alt="Sincronizar Processo" title="Sincronizar Processo" />';
            $strAcoesProcedimento .= '</a>';
          }
        }
      }
    }

      //Apresenta o botão da página de recibos
    if($bolAcaoExpedirProcesso) {
        $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
        $objProcessoEletronicoDTO->retDblIdProcedimento();
        $objProcessoEletronicoDTO->setDblIdProcedimento($dblIdProcedimento);
        $objProcessoEletronicoRN = new ProcessoEletronicoRN();
      if($objProcessoEletronicoRN->contar($objProcessoEletronicoDTO) != 0) {
          $numTabBotao = $objPaginaSEI->getProxTabBarraComandosSuperior();
          $strAcoesProcedimento .= '<a href="' . $objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_estado&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_procedimento=' . $dblIdProcedimento . '&arvore=1') . '" tabindex="' . $numTabBotao . '" class="botaoSEI">';
          $strAcoesProcedimento .= '<img class="infraCorBarraSistema" src=' . ProcessoEletronicoINT::getCaminhoIcone("/pen_consultar_recibos.png", $this->getDiretorioImagens()) . ' alt="Consultar Recibos" title="Consultar Recibos"/>';
        $strAcoesProcedimento .= '</a>';
      }
    }

    // $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
    // $objProcessoEletronicoDTO->setDblIdProcedimento($dblIdProcedimento);
    // $objTramiteBD = new TramiteBD(BancoSEI::getInstance());

    // $objTramiteDTO = $objTramiteBD->consultarUltimoTramite($objProcessoEletronicoDTO, ProcessoEletronicoRN::$STA_TIPO_TRAMITE_RECEBIMENTO);

    // $podeSolicitarReproducaoUltimoTramite = ProcessoEletronicoRN::podeSolicitarReproducaoUltimoTramite($dblIdProcedimento);
    // if ($bolFlagAberto && !is_null($objTramiteDTO) && $podeSolicitarReproducaoUltimoTramite){
    //   $strAcoesProcedimento .= '<a onclick="return confirm(\\\'Confirma reproduzir último trâmite deste processo?\\\');" href="' . $objSessaoSEI->assinarLink('controlador.php?acao=pen_reproduzir_ultimo_tramite&acao_origem=procedimento_visualizar&acao_retorno=arvore_visualizar&id_repositorio=' . $objTramiteDTO->getNumIdRepositorioDestino() . '&id_estrutura=' . $objTramiteDTO->getNumIdEstruturaDestino() . '&nre=' . $objTramiteDTO->getStrNumeroRegistro() . '&id_ultimo_tramite=' . $objTramiteDTO->getNumIdTramite() . '&id_procedimento=' . $dblIdProcedimento . '&arvore=1') . '" tabindex="' . $numTabBotao . '" class="botaoSEI">';
    //   $strAcoesProcedimento .= '<img class="infraCorBarraSistema" src=' . ProcessoEletronicoINT::getCaminhoIcone("/pen_reproduzir_ultimo_tramite.svg", $this->getDiretorioImagens()) . ' alt="Reproduzir Último Trâmite" title="Reproduzir Último Trâmite"/>';
    //   $strAcoesProcedimento .= '</a>';
    // }

      return [$strAcoesProcedimento];
  }

  public function excluirHipoteseLegal($arrObjHipoteseLegalDTO)
    {
      $this->validarExcluirDesativarHipoteseLegal($arrObjHipoteseLegalDTO, 'exclusão');
  }

  public function desativarHipoteseLegal($arrObjHipoteseLegalDTO)
    {
      $this->validarExcluirDesativarHipoteseLegal($arrObjHipoteseLegalDTO, 'inativação');
  }

  public function validarExcluirDesativarHipoteseLegal($arrObjHipoteseLegalAPI, $strAcao)
    {
      $excecao = new InfraException();
    foreach ($arrObjHipoteseLegalAPI as $objHipoteseLegalAPI) {
        $objPenHipoteseLegalDTO = new PenRelHipoteseLegalDTO();
        $objPenHipoteseLegalDTO->setNumIdHipoteseLegal($objHipoteseLegalAPI->getIdHipoteseLegal());
        $objPenHipoteseLegalDTO->retNumIdHipoteseLegal();
        $objPenHipoteseLegalDTO->setNumMaxRegistrosRetorno(1);

        $objPenRelHipoteseLegalEnvioRN = new PenRelHipoteseLegalEnvioRN();
        $objPenRelHipoteseLegalEnvioDTO = $objPenRelHipoteseLegalEnvioRN->consultar($objPenHipoteseLegalDTO);

        $objPenRelHipoteseLegalRecebidoRN = new PenRelHipoteseLegalRecebidoRN();
        $objPenRelHipoteseLegalRecebidoDTO = $objPenRelHipoteseLegalRecebidoRN->consultar($objPenHipoteseLegalDTO);
      
      if (!is_null($objPenRelHipoteseLegalEnvioDTO) || !is_null($objPenRelHipoteseLegalRecebidoDTO)) {

        $objPenHipoteseLegalDTO = new PenHipoteseLegalDTO();
        $objPenHipoteseLegalDTO->setNumIdHipoteseLegal($objHipoteseLegalAPI->getIdHipoteseLegal());
        $objPenHipoteseLegalDTO->retStrNome();

        $objPenHipoteseLegalRN = new PenHipoteseLegalRN();
        $objPenHipoteseLegalDTO = $objPenHipoteseLegalRN->consultar($objPenHipoteseLegalDTO);
        $nome = $objPenHipoteseLegalDTO->getStrNome();
        $excecao->lancarValidacao(
            $this->getNome().": 
          A $strAcao da hipótese legal $nome não é permitida. 
          A referida hipótese legal está relacionada a uma hipótese legal do Tramita."
        );
      }
    }
  }

  public function montarIconeControleProcessos($arrObjProcedimentoAPI = [])
    {
    if(!PENIntegracao::verificarCompatibilidadeConfiguracoes()) {
        return false;
    }

      $arrStrIcone = [];
      $arrDblIdProcedimento = [];

    foreach ($arrObjProcedimentoAPI as $ObjProcedimentoAPI) {
        $arrDblIdProcedimento[] = $ObjProcedimentoAPI->getIdProcedimento();
    }

      $arrStrIcone = $this->montarIconeRecusa($arrDblIdProcedimento, $arrStrIcone);
      $arrStrIcone = $this->montarIconeTramite($arrDblIdProcedimento, $arrStrIcone);
      $arrStrIcone = $this->montarIconeSincronizacao($arrDblIdProcedimento, $arrStrIcone);

      return $arrStrIcone;
  }

  private function montarIconeRecusa($arrDblIdProcedimento = [], $arrStrIcone = [])
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
            $arrStrIcone[$dblIdProcedimento] = ['<img src="' . $this->getDiretorioImagens() . '/pen_tramite_recusado.png" title="Um trâmite para esse processo foi recusado" />'];
        }
      }
    }

      return $arrStrIcone;
  }

  private function montarIconeTramite($arrDblIdProcedimento = [], $arrStrIcone = [])
    {
      $arrTiProcessoEletronico = [ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO), ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_CANCELADO), ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_ABORTADO), ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO), ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_DOCUMENTO_AVULSO_RECEBIDO)];
    
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
                $arrayIcone = ['<img src="' . $this->getDiretorioImagens() . '/icone-ENVIADO-tramita.png" title="Um trâmite para esse processo foi enviado" />'];
            if (!isset($arrStrIcone[$dblIdProcedimento])) {
              $arrStrIcone[$dblIdProcedimento] = $arrayIcone;
            } else {
              $arrStrIcone[$dblIdProcedimento] = array_merge($arrStrIcone[$dblIdProcedimento], $arrayIcone);
            }
              break;
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO):
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_DOCUMENTO_AVULSO_RECEBIDO):
              $arrayIcone = ['<img src="' . $this->getDiretorioImagens() . '/icone-RECEBIDO-tramita.png" title="Um trâmite para esse processo foi recebido" />'];
            if (!isset($arrStrIcone[$dblIdProcedimento])) {
              $arrStrIcone[$dblIdProcedimento] = $arrayIcone;
            } else {
                $arrStrIcone[$dblIdProcedimento] = array_merge($arrStrIcone[$dblIdProcedimento], $arrayIcone);
            }
              break;
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_CANCELADO):
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_ABORTADO):
            if ($this->consultarProcessoRecebido($dblIdProcedimento)) {
                          $arrayIcone = ['<img src="' . $this->getDiretorioImagens() . '/icone-RECEBIDO-tramita.png" title="Um trâmite para esse processo foi recebido" />'];
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

  private function montarIconeSincronizacao($arrDblIdProcedimento = [], $arrStrIcone = [])
  {
    foreach ($arrDblIdProcedimento as $dblIdProcedimento) {
      $arrTiProcessoEletronico = [
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MULTIPLOS_ORGAOS),
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MANUAL_MULTIPLOS_ORGAOS),
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_AUTO_ENVIO_MULTIPLOS_ORGAOS),
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_ENVIO_MULTIPLOS_ORGAOS),
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MULTIPLOS_ORGAOS_RECEBIDO),
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_SINC_MULTIPLOS_ORGAOS_CANCELADO),
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_SINC_MULTIPLOS_ORGAOS_RECUSA),
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_SINC_MULTIPLOS_ORGAOS_CANCELADO_AUTO)
      ];

      $objAtividadeDTO = new AtividadeDTO();
      $objAtividadeDTO->setDblIdProtocolo($dblIdProcedimento);
      $objAtividadeDTO->setNumIdTarefa($arrTiProcessoEletronico, InfraDTO::$OPER_IN);
      $objAtividadeDTO->setOrdDthAbertura(InfraDTO::$TIPO_ORDENACAO_DESC);
      $objAtividadeDTO->setNumMaxRegistrosRetorno(1);
      $objAtividadeDTO->retNumIdAtividade();
      $objAtividadeDTO->retNumIdTarefa();
      $objAtividadeDTO->retDblIdProcedimentoProtocolo();
      $objAtividadeDTO->retDthAbertura();
      $objAtividadeDTO->retDthConclusao();
    
      $objAtividadeRN = new AtividadeRN();
      $objAtividadeDTO = $objAtividadeRN->consultarRN0033($objAtividadeDTO);

      if($objAtividadeDTO !== null) {
        $arrayIcone = [];
        switch ($objAtividadeDTO->getNumIdTarefa()) {
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MULTIPLOS_ORGAOS):
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MANUAL_MULTIPLOS_ORGAOS):
            $title = "Pedido de sincronização realizado em " . $objAtividadeDTO->getDthAbertura();
            $arrayIcone = ['<img src="' . $this->getDiretorioImagens() . '/sincronizar_processo_pendente.png" title="'.$title.'" />'];
              break;
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_AUTO_ENVIO_MULTIPLOS_ORGAOS):
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_ENVIO_MULTIPLOS_ORGAOS):
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MULTIPLOS_ORGAOS_RECEBIDO):
            $dataAbertura = $objAtividadeDTO->getDthConclusao() !== null ? $objAtividadeDTO->getDthConclusao() : $objAtividadeDTO->getDthAbertura();
            $title = "Sincronizado com sucesso em " . $dataAbertura;
            $arrayIcone = ['<img src="' . $this->getDiretorioImagens() . '/sincronizar_sucesso.png" title="'.$title.'" />'];
              break;
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_SINC_MULTIPLOS_ORGAOS_CANCELADO):
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_SINC_MULTIPLOS_ORGAOS_RECUSA):
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_SINC_MULTIPLOS_ORGAOS_CANCELADO_AUTO):
            $title = "A sincronização não foi concluída em " . $objAtividadeDTO->getDthConclusao();
            $arrayIcone = ['<img src="' . $this->getDiretorioImagens() . '/sincronizar_falha.png" title="'.$title.'" />'];
              break;
          default:
              break;
        }

        if (empty($arrayIcone)) {
          continue;
        }

        if (!isset($arrStrIcone[$dblIdProcedimento])) {
          $arrStrIcone[$dblIdProcedimento] = $arrayIcone;
        } else {
          $arrStrIcone[$dblIdProcedimento] = array_merge($arrStrIcone[$dblIdProcedimento], $arrayIcone);
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
    if(!PENIntegracao::verificarCompatibilidadeConfiguracoes()) {
        return false;
    }

      $dblIdProcedimento = $objProcedimentoAP->getIdProcedimento();

      $objArvoreAcaoItemAPI = new ArvoreAcaoItemAPI();
      $objArvoreAcaoItemAPI->setTipo('MD_TRAMITE_PROCESSO');
      $objArvoreAcaoItemAPI->setId('MD_TRAMITE_PROC_' . $dblIdProcedimento);
      $objArvoreAcaoItemAPI->setIdPai($dblIdProcedimento);
      $objArvoreAcaoItemAPI->setTitle('Um trâmite para esse processo foi recusado');
      $objArvoreAcaoItemAPI->setIcone($this->getDiretorioImagens() . '/pen_tramite_recusado.png');

      $objArvoreAcaoItemAPI->setTarget(null);
      $objArvoreAcaoItemAPI->setHref('javascript:alert(\'Um trâmite para esse processo foi recusado\');');

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
        return [];
    }

      return $arrObjArvoreAcaoItemAPI;
  }
  // TODO: Diminuir complexidade deste método, extraindo parte de sua lógica para métodos auxiliares
  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
  private function getObjArvoreAcao($dblIdProcedimento, $arrObjArvoreAcaoItemAPI)
    {

      $arrTiProcessoEletronico = [ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO), ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO), ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_CANCELADO), ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_ABORTADO), ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_DOCUMENTO_AVULSO_RECEBIDO)];

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

      $arrTiProcessoEletronico = [
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MULTIPLOS_ORGAOS),
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MANUAL_MULTIPLOS_ORGAOS),
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_AUTO_ENVIO_MULTIPLOS_ORGAOS),
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_ENVIO_MULTIPLOS_ORGAOS),
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MULTIPLOS_ORGAOS_RECEBIDO),
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_SINC_MULTIPLOS_ORGAOS_CANCELADO),
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_SINC_MULTIPLOS_ORGAOS_RECUSA),
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_SINC_MULTIPLOS_ORGAOS_CANCELADO_AUTO)
      ];

      $objAtividadeDTO = new AtividadeDTO();
      $objAtividadeDTO->setDblIdProtocolo($dblIdProcedimento);
      $objAtividadeDTO->setNumIdTarefa($arrTiProcessoEletronico, InfraDTO::$OPER_IN);
      $objAtividadeDTO->setOrdDthAbertura(InfraDTO::$TIPO_ORDENACAO_DESC);
      $objAtividadeDTO->setNumMaxRegistrosRetorno(1);
      $objAtividadeDTO->retNumIdAtividade();
      $objAtividadeDTO->retNumIdTarefa();
      $objAtividadeDTO->retDthAbertura();
      $objAtividadeDTO->retDthConclusao();
    
      $objAtividadeRN = new AtividadeRN();
      $objAtividadeDTO = $objAtividadeRN->consultarRN0033($objAtividadeDTO);

      $objProcedimentoDTO = new ProcedimentoDTO();
      $objProcedimentoDTO->setDblIdProcedimento($dblIdProcedimento);
      $objProcedimentoDTO->retStrStaEstadoProtocolo();
      $objProcedimentoRN = new ProcedimentoRN();
      $objProcedimentoDTO = $objProcedimentoRN->consultarRN0201($objProcedimentoDTO);

      if ($objAtividadeDTO !== null) {
        switch ($objAtividadeDTO->getNumIdTarefa()) {
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MULTIPLOS_ORGAOS):
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MANUAL_MULTIPLOS_ORGAOS):
            try {
              if ($objProcedimentoDTO->getStrStaEstadoProtocolo() == ProtocoloRN::$TE_PROCEDIMENTO_BLOQUEADO && is_null($objAtividadeDTO->getDthConclusao())) {
                $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
                $objProcessoEletronicoDTO->setDblIdProcedimento($dblIdProcedimento);
                $objProcessoEletronicoRN = new ProcessoEletronicoRN();
                $objTramiteDTO = $objProcessoEletronicoRN->consultarUltimoTramite($objProcessoEletronicoDTO);
                $objTramitesAnteriores = $objProcessoEletronicoRN->consultarTramitesTodos(null, $objTramiteDTO->getStrNumeroRegistro());
                $arrayRecusa = [
                  ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO,
                  ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO,
                  ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO_AUTOMATICAMENTE,
                  ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CIENCIA_RECUSA
                ];
                if (isset($objTramitesAnteriores) && count($objTramitesAnteriores) > 0 && in_array($objTramitesAnteriores[count($objTramitesAnteriores)-1]->situacaoAtual, $arrayRecusa)) {
                  $objProcessoEletronicoRN->desbloquearProcesso($dblIdProcedimento);
                  $motivo = isset($objTramitesAnteriores[count($objTramitesAnteriores)-1]->justificativaDaRecusa) ? mb_convert_encoding($objTramitesAnteriores[count($objTramitesAnteriores)-1]->justificativaDaRecusa, 'iso-8859-1', 'utf-8') : 'Cancelado pelo usuário ou pelo administrador da plataforma';
                  $motivo = str_replace('. OBS: A recusa é uma das três formas de conclusão de trâmite. Portanto, não é um erro.', '', $motivo); // Remove duplicidade de mensagem
                  $objProcessoEletronicoRN->validarProcessoRecusaCancelamento($dblIdProcedimento, $motivo);
                  echo "<script>window.location.reload();</script>";
                }
              }
            } catch (Exception $e) {
              // gravar log de erro caso necessário
            }
            $arrObjArvoreAcaoItemAPI[] = $this->getObjArvoreAcaoSincronizadoPendente($dblIdProcedimento, $objAtividadeDTO->getDthAbertura());
              break;
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_AUTO_ENVIO_MULTIPLOS_ORGAOS):
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_ENVIO_MULTIPLOS_ORGAOS):
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MULTIPLOS_ORGAOS_RECEBIDO):
            $dataAbertura = $objAtividadeDTO->getDthConclusao() !== null ? $objAtividadeDTO->getDthConclusao() : $objAtividadeDTO->getDthAbertura();
            $arrObjArvoreAcaoItemAPI[] = $this->getObjArvoreAcaoSincronizadoFinalizado($dblIdProcedimento, $dataAbertura);
              break;
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_SINC_MULTIPLOS_ORGAOS_CANCELADO):
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_SINC_MULTIPLOS_ORGAOS_RECUSA):
          case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_SINC_MULTIPLOS_ORGAOS_CANCELADO_AUTO):
            if ($objProcedimentoDTO->getStrStaEstadoProtocolo() == ProtocoloRN::$TE_PROCEDIMENTO_BLOQUEADO) {
              ProcessoEletronicoRN::desbloquearProcesso($dblIdProcedimento);
              echo "<script>window.location.reload();</script>";
            }
            $arrObjArvoreAcaoItemAPI[] = $this->getObjArvoreAcaoSincronizacaoFalha($dblIdProcedimento, $objAtividadeDTO->getDthConclusao());
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
      $objArvoreAcaoItemAPI->setTitle('Um trâmite para esse processo foi recebido');
      $objArvoreAcaoItemAPI->setIcone($this->getDiretorioImagens() . '/icone-RECEBIDO-tramita.png');

      $objArvoreAcaoItemAPI->setTarget(null);
      $objArvoreAcaoItemAPI->setHref('javascript:alert(\'Um trâmite para esse processo foi recebido\');');

      $objArvoreAcaoItemAPI->setSinHabilitado('S');

      return $objArvoreAcaoItemAPI;
  }

  private function getObjArvoreAcaoEnviado($dblIdProcedimento)
    {
      $objArvoreAcaoItemAPI = new ArvoreAcaoItemAPI();
      $objArvoreAcaoItemAPI->setTipo('MD_TRAMITE_PROCESSO');
      $objArvoreAcaoItemAPI->setId('MD_TRAMITE_PROC_' . $dblIdProcedimento);
      $objArvoreAcaoItemAPI->setIdPai($dblIdProcedimento);
      $objArvoreAcaoItemAPI->setTitle('Um trâmite para esse processo foi enviado');
      $objArvoreAcaoItemAPI->setIcone($this->getDiretorioImagens() . '/icone-ENVIADO-tramita.png');

      $objArvoreAcaoItemAPI->setTarget(null);
      $objArvoreAcaoItemAPI->setHref('javascript:alert(\'Um trâmite para esse processo foi enviado\');');

      $objArvoreAcaoItemAPI->setSinHabilitado('S');

      return $objArvoreAcaoItemAPI;
  }

  private function getObjArvoreAcaoSincronizadoPendente($dblIdProcedimento, $dthSincronizacao)
    {
      $objArvoreAcaoItemAPI = new ArvoreAcaoItemAPI();
      $objArvoreAcaoItemAPI->setTipo('MD_SINC_PROCESSO_PEDIDO');
      $objArvoreAcaoItemAPI->setId('MD_SINC_PROCESSO_' . $dblIdProcedimento);
      $objArvoreAcaoItemAPI->setIdPai($dblIdProcedimento);
      $objArvoreAcaoItemAPI->setTitle('Pedido de sincronização realizado em ' . $dthSincronizacao);
      $objArvoreAcaoItemAPI->setIcone($this->getDiretorioImagens() . '/sincronizar_processo_pendente.png');

      $objArvoreAcaoItemAPI->setTarget(null);
      $objArvoreAcaoItemAPI->setHref('javascript:alert(\'Um trâmite para esse processo foi sincronizado\');');

      $objArvoreAcaoItemAPI->setSinHabilitado('S');

      return $objArvoreAcaoItemAPI;
  }

  private function getObjArvoreAcaoSincronizadoFinalizado($dblIdProcedimento, $dthSincronizacao)
    {
      $objArvoreAcaoItemAPI = new ArvoreAcaoItemAPI();
      $objArvoreAcaoItemAPI->setTipo('MD_SINC_PROCESSO_FINALIZADO');
      $objArvoreAcaoItemAPI->setId('MD_SINC_PROCESSO_' . $dblIdProcedimento);
      $objArvoreAcaoItemAPI->setIdPai($dblIdProcedimento);
      $objArvoreAcaoItemAPI->setTitle('Sincronizado com sucesso em ' . $dthSincronizacao);
      $objArvoreAcaoItemAPI->setIcone($this->getDiretorioImagens() . '/sincronizar_sucesso.png');

      $objArvoreAcaoItemAPI->setTarget(null);
      $objArvoreAcaoItemAPI->setHref('javascript:alert(\'Um trâmite para esse processo foi sincronizado\');');

      $objArvoreAcaoItemAPI->setSinHabilitado('S');

      return $objArvoreAcaoItemAPI;
  }

  private function getObjArvoreAcaoSincronizacaoFalha($dblIdProcedimento, $dthSincronizacao)
  {
    $objArvoreAcaoItemAPI = new ArvoreAcaoItemAPI();
    $objArvoreAcaoItemAPI->setTipo('MD_SINC_PROCESSO_FINALIZADO');
    $objArvoreAcaoItemAPI->setId('MD_SINC_PROCESSO_' . $dblIdProcedimento);
    $objArvoreAcaoItemAPI->setIdPai($dblIdProcedimento);
    $objArvoreAcaoItemAPI->setTitle('A sincronização não foi concluída em ' . $dthSincronizacao);
    $objArvoreAcaoItemAPI->setIcone($this->getDiretorioImagens() . '/sincronizar_falha.png');

    $objArvoreAcaoItemAPI->setTarget(null);
    $objArvoreAcaoItemAPI->setHref('javascript:alert(\'Um trâmite para esse processo foi sincronizado\');');

    $objArvoreAcaoItemAPI->setSinHabilitado('S');

    return $objArvoreAcaoItemAPI;
  }
  

  public function montarIconeAcompanhamentoEspecial($arrObjProcedimentoDTO)
    {
    if(!PENIntegracao::verificarCompatibilidadeConfiguracoes()) {
        return false;
    }
  }

  public function getDiretorioImagens()
    {
      return static::getDiretorio() . '/imagens';
  }


  public function montarMensagemProcesso(ProcedimentoAPI $objProcedimentoAPI)
    {
    if(!PENIntegracao::verificarCompatibilidadeConfiguracoes()) {
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

        return sprintf('Processo em trâmite externo para "%s".', $objAtributoAndamentoDTO->getStrValor());
    }
  }



  public function montarIconeDocumento(ProcedimentoAPI $objProcedimentoAPI, $arrObjDocumentoAPI)
    {
    if(!PENIntegracao::verificarCompatibilidadeConfiguracoes()) {
        return false;
    }

      $arrIcones = [];

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

          $arrObjDocumentoAPIIndexado = [];
          foreach ($arrObjDocumentoAPI as $objDocumentoAPI) {
                $arrObjDocumentoAPIIndexado[$objDocumentoAPI->getIdDocumento()] = $objDocumentoAPI;

            if ($objDocumentoAPI->getCodigoAcesso() > 0) {
              $dblIdDocumento = $objDocumentoAPI->getIdDocumento();
              if (array_key_exists($dblIdDocumento, $arrObjCompIndexadoPorIdDocumentoDTO)) {
                        $objComponenteDTO = $arrObjCompIndexadoPorIdDocumentoDTO[$dblIdDocumento];
                if (!is_null($objComponenteDTO->getNumOrdemDocumentoReferenciado())) {
                  $arrIcones[$dblIdDocumento] = [];

                  $objComponenteReferenciadoDTO = $arrObjCompIndexadoPorOrdemDTO[$objComponenteDTO->getNumOrdemDocumentoReferenciado()];
                  $objDocumentoReferenciadoAPI = $arrObjDocumentoAPIIndexado[$objComponenteReferenciadoDTO->getDblIdDocumento()];

                  $strTextoInformativo = sprintf(
                    "Anexo do %s \(%s\)",
                    $objDocumentoReferenciadoAPI->getNomeSerie(),
                    $objDocumentoReferenciadoAPI->getNumeroProtocolo()
                  );

                  $objSerieDTO = $objPenRelTipoDocMapRecebidoRN->obterSerieMapeada($objComponenteDTO->getNumCodigoEspecie());
                  if(!is_null($objSerieDTO)) {
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

  public function desativarTipoDocumento($arrObjSerieAPI) {
    $this->validarExcluirDesativarTipoDocumento($arrObjSerieAPI, 'desativar');
  }

  public function excluirTipoDocumento($arrObjSerieAPI) {
    $this->validarExcluirDesativarTipoDocumento($arrObjSerieAPI, 'excluir');
  }

  protected function validarExcluirDesativarTipoDocumento($arrObjSerieAPI, $strDesativarExcluir)
  {
    $excecao = new InfraException();
    foreach ($arrObjSerieAPI as $objSerieAPI) {
      $objPenRelTipoDocMapEnviadoDTO = new PenRelTipoDocMapEnviadoDTO();
      $objPenRelTipoDocMapEnviadoDTO->setNumIdSerie($objSerieAPI->getIdSerie());
      $objPenRelTipoDocMapEnviadoDTO->retNumIdSerie();

      $objPenRelTipoDocMapEnviadoRN = new PenRelTipoDocMapEnviadoRN();
      $objPenRelTipoDocMapEnviadoDTO = $objPenRelTipoDocMapEnviadoRN->contar($objPenRelTipoDocMapEnviadoDTO);

      $objPenRelTipoDocMapRecebidoDTO = new PenRelTipoDocMapRecebidoDTO();
      $objPenRelTipoDocMapRecebidoDTO->setNumIdSerie($objSerieAPI->getIdSerie());
      $objPenRelTipoDocMapRecebidoDTO->retNumIdSerie();

      $objPenRelTipoDocMapRecebidoRN = new PenRelTipoDocMapRecebidoRN();
      $objPenRelTipoDocMapRecebidoDTO = $objPenRelTipoDocMapRecebidoRN->contar($objPenRelTipoDocMapRecebidoDTO);
      
      if ($objPenRelTipoDocMapRecebidoDTO > 0 || $objPenRelTipoDocMapEnviadoDTO > 0) {
          $excecao->lancarValidacao('Prezado(a) usuário(a), você está tentando '.$strDesativarExcluir.' o tipo de documento "'. $objSerieAPI->getNome()
          . '". Esse tipo de documento está mapeado em Administração -> Tramita GOV.BR -> Mapeamento de Tipos de Documentos -> Envio/Recebimento');
      }
    }
  }

  public function desativarUnidade($arrObjUnidadeAPI) {
    $this->validarExcluirDesativarUnidade($arrObjUnidadeAPI, 'desativar');
  }

  public function excluirUnidade($arrObjUnidadeAPI) {
    $this->validarExcluirDesativarUnidade($arrObjUnidadeAPI, 'excluir');
  }

  protected function validarExcluirDesativarUnidade($arrObjUnidadeAPI, $strDesativarExcluir)
  {
    $excecao = new InfraException();
    foreach ($arrObjUnidadeAPI as $objUnidadeAPI) {
      $objPenUnidadeDTO = new PenUnidadeDTO();
      $objPenUnidadeDTO->setNumIdUnidade($objUnidadeAPI->getIdUnidade());
      $objPenUnidadeDTO->retNumIdUnidade();

      $objPenUnidadeRN = new PenUnidadeRN();
      $objPenUnidadeDTO = $objPenUnidadeRN->contar($objPenUnidadeDTO);
      if ($objPenUnidadeDTO > 0) {
        $excecao->lancarValidacao('Prezado(a) usuário(a), você está tentando '.$strDesativarExcluir.' a unidade "'. $objUnidadeAPI->getSigla()
        . '". Essa unidade está mapeada em Administração -> Tramita GOV.BR -> Mapeamento de Unidades -> Listar' );
      }
    }
  }

  public function excluirTipoProcesso($arrObjTipoProcedimentoDTO) {
    $this->validarDesativarExcluirTipoProcesso($arrObjTipoProcedimentoDTO, 'excluir');
  }

  public function desativarTipoProcesso($arrObjTipoProcedimentoDTO) {
    $this->validarDesativarExcluirTipoProcesso($arrObjTipoProcedimentoDTO, 'desativar');
  }

  protected function validarDesativarExcluirTipoProcesso($arrObjTipoProcedimentoDTO, $strDesativarExcluir) {
    
    $mensagem = "Prezado(a) usuário(a), você está tentando ".$strDesativarExcluir." um Tipo de Processo que se encontra mapeado para o(s) relacionamento(s) "
    ."\"%s\". Para continuar com essa ação é necessário remover do(s) mapeamentos "
    ."mencionados o Tipo de Processo: \"%s\".";

    $objMapeamentoTipoProcedimentoRN = new PenMapTipoProcedimentoRN();
    $objMapeamentoTipoProcedimentoRN->validarAcaoTipoProcesso($arrObjTipoProcedimentoDTO, $mensagem);

    $mensagem = 'Prezado(a) usuário(a), você está tentando '.$strDesativarExcluir.' o Tipo de Processo "%s" '
      . 'que se encontra mapeado para o Tipo de Processo Padrão. '
      . 'Para continuar com essa ação é necessário alterar o Tipo de Processo Padrão. '
      . 'O Tipo de Processo padrão se encontra disponível em: '
      . 'Administração -> Tramita GOV.BR -> Mapeamento de Tipos de Processo -> Relacionamento entre Unidades';

    $objPenParametroRN = new PenParametroRN();
    $objPenParametroRN->validarAcaoTipoProcessoPadrao($arrObjTipoProcedimentoDTO, $mensagem);

    $exception = new InfraException();
    foreach ($arrObjTipoProcedimentoDTO as $objProcedimento) {
        $objPenParametroDTO = new PenParametroDTO();
        $objPenParametroDTO->setStrNome('PEN_TIPO_PROCESSO_EXTERNO');
        $objPenParametroDTO->setStrValor($objProcedimento->getIdTipoProcedimento());
        $objPenParametroRN = new PenParametroRN();
        $objPenParametroDTO = $objPenParametroRN->contar($objPenParametroDTO);

        $objProcedimentoDTO = new ProcedimentoDTO();
        $objProcedimentoDTO->setNumIdTipoProcedimento($objProcedimento->getIdTipoProcedimento());
        $objProcedimentoRN = new ProcedimentoRN();
        $objProcedimentoDTO = $objProcedimentoRN->contarRN0279($objProcedimentoDTO);

      if ($objPenParametroDTO > 0 || $objProcedimentoDTO > 0) {
          $exception->lancarValidacao('Existem processos utilizando o tipo de processo "'. $objProcedimento->getNome().'".');
      }
    }
  }

    /**
     * Método responsável de criar listagem de item para XML
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
        $xml .= ' descricao="' . mb_convert_encoding(self::formatarXMLAjax($dto->get($strAtributoDescricao)), 'iso-8859-1', 'utf-8') . '"';

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
    return mb_convert_encoding($xml, 'ISO-8859-1', 'UTF-8');
  }

    /**
     * MÃ©todo de formataÃ§Ã£o para caracteres especiais XML
     */
  private static function formatarXMLAjax($str)
    {
    if (!is_numeric($str)) {
        $str = str_replace('&', '&amp;', $str);
        $str = str_replace('<', '&amp;lt;', $str);
        $str = str_replace('>', '&amp;gt;', $str);
        $str = str_replace('\"', '&amp;quot;', $str);
        $str = str_replace('"', '&amp;quot;', $str);
    }
      return $str;
  }

  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
  public function processarControlador($strAcao)
    {
      //Configuração de páginas do contexto da Árvore do processo para apresentação de erro de forma correta
      $bolArvore = in_array($strAcao, ['pen_procedimento_estado']);
      PaginaSEI::getInstance()->setBolArvore($bolArvore);

    if (strpos($strAcao, 'pen_') === false) {
        return false;
    }

    if(!PENIntegracao::verificarCompatibilidadeConfiguracoes()) {
        return false;
    }

    switch ($strAcao) {
      case 'pen_procedimento_expedir':
          include_once __DIR__ . '/pen_procedimento_expedir.php';
          break;

      case 'pen_tramite_bloco_listar':
      case 'md_pen_tramita_em_bloco':
      case 'md_pen_tramita_em_bloco_excluir':
      case 'pen_tramite_em_bloco_cancelar':
          include_once __DIR__ . '/pen_tramite_bloco_listar.php';
          break;

      case 'pen_tramite_em_bloco_cadastrar':
      case 'pen_tramite_em_bloco_alterar':
          include_once __DIR__ . '/pen_tramite_em_bloco_cadastrar.php';
          break;
     
      case 'pen_tramita_em_bloco_protocolo_excluir':
      case 'pen_tramita_em_bloco_protocolo_listar':
          include_once __DIR__ . '/pen_tramita_em_bloco_protocolo_listar.php';
          break;

      case 'pen_excluir_processo_em_bloco_tramite':
      case 'pen_incluir_processo_em_bloco_tramite':
      case 'pen_tramita_em_bloco_adicionar':
          include_once __DIR__ . '/pen_tramite_processo_em_bloco_cadastrar.php';
          break;

      case 'pen_unidade_sel_expedir_procedimento':
          include_once __DIR__ . '/pen_unidade_sel_expedir_procedimento.php';
          break;

      case 'pen_procedimento_processo_anexado':
          include_once __DIR__ . '/pen_procedimento_processo_anexado.php';
          break;

      case 'pen_procedimento_cancelar_expedir':
          include_once __DIR__ . '/pen_procedimento_cancelar_expedir.php';
          break;
      
      case 'pen_procedimento_sincronizar':
          include_once __DIR__ . '/pen_procedimento_sincronizar.php';
          break;

      case 'pen_procedimento_expedido_listar':
          include_once __DIR__ . '/pen_procedimento_expedido_listar.php';
          break;

      case 'pen_map_tipo_documento_envio_listar':
      case 'pen_map_tipo_documento_envio_excluir':
      case 'pen_map_tipo_documento_envio_desativar':
      case 'pen_map_tipo_documento_envio_ativar':
          include_once __DIR__ . '/pen_map_tipo_documento_envio_listar.php';
          break;

      case 'pen_map_tipo_documento_envio_cadastrar':
      case 'pen_map_tipo_documento_envio_visualizar':
          include_once __DIR__ . '/pen_map_tipo_documento_envio_cadastrar.php';
          break;

      case 'pen_map_tipo_documento_recebimento_listar':
      case 'pen_map_tipo_documento_recebimento_excluir':
          include_once __DIR__ . '/pen_map_tipo_documento_recebimento_listar.php';
          break;

      case 'pen_map_tipo_documento_recebimento_cadastrar':
      case 'pen_map_tipo_documento_recebimento_visualizar':
          include_once __DIR__ . '/pen_map_tipo_documento_recebimento_cadastrar.php';
          break;

      case 'pen_apensados_selecionar_expedir_procedimento':
          include_once __DIR__ . '/apensados_selecionar_expedir_procedimento.php';
          break;

      case 'pen_unidades_administrativas_externas_selecionar_expedir_procedimento':
          //verifica qual o tipo de seleção passado para carregar o arquivo especifico.
        if($_GET['tipo_pesquisa'] == 1) {
            include_once __DIR__ . '/pen_unidades_administrativas_selecionar_expedir_procedimento.php';
        }else {
            include_once __DIR__ . '/pen_unidades_administrativas_pesquisa_textual_expedir_procedimento.php';
        }
          break;

      case 'pen_procedimento_estado':
          include_once __DIR__ . '/pen_procedimento_estado.php';
          break;

      // Mapeamento de Hipóteses Legais de Envio
      case 'pen_map_hipotese_legal_envio_cadastrar':
      case 'pen_map_hipotese_legal_envio_visualizar':
          include_once __DIR__ . '/pen_map_hipotese_legal_envio_cadastrar.php';
          break;

      case 'pen_map_hipotese_legal_envio_listar':
      case 'pen_map_hipotese_legal_envio_excluir':
          include_once __DIR__ . '/pen_map_hipotese_legal_envio_listar.php';
          break;

      // Mapeamento de Hipóteses Legais de Recebimento
      case 'pen_map_hipotese_legal_recebimento_cadastrar':
      case 'pen_map_hipotese_legal_recebimento_visualizar':
          include_once __DIR__ . '/pen_map_hipotese_legal_recebimento_cadastrar.php';
          break;

      case 'pen_map_hipotese_legal_recebimento_listar':
      case 'pen_map_hipotese_legal_recebimento_excluir':
          include_once __DIR__ . '/pen_map_hipotese_legal_recebimento_listar.php';
          break;

      case 'pen_map_hipotese_legal_padrao_cadastrar':
      case 'pen_map_hipotese_legal_padrao_visualizar':
          include_once __DIR__ . '/pen_map_hipotese_legal_padrao_cadastrar.php';
          break;

      case 'pen_map_unidade_cadastrar':
      case 'pen_map_unidade_visualizar':
          include_once __DIR__ . '/pen_map_unidade_cadastrar.php';
          break;

      case 'pen_map_orgaos_externos_salvar':
      case 'pen_map_orgaos_externos_atualizar':
      case 'pen_map_orgaos_externos_cadastrar':
      case 'pen_map_orgaos_externos_visualizar':
          include_once __DIR__ . '/pen_map_orgaos_externos_cadastrar.php';
          break;

      case 'pen_map_orgaos_externos_reativar':
      case 'pen_map_orgaos_externos_desativar':  
      case 'pen_map_orgaos_externos_listar':
      case 'pen_map_orgaos_externos_excluir':
      case 'pen_map_orgaos_importar_tipos_processos':
          include_once __DIR__ . '/pen_map_orgaos_externos_listar.php';
          break;

      case 'pen_map_tipo_processo_padrao':
      case 'pen_map_tipo_processo_padrao_salvar':
          include_once __DIR__ . '/pen_map_tipo_processo_padrao.php';
          break;

      case 'pen_map_tipo_processo_reativar':
          include_once __DIR__ . '/pen_map_tipo_processo_reativar.php';
          break;

      case 'pen_map_orgaos_exportar_tipos_processos':
          include_once __DIR__ . '/pen_tipo_procedimento_lista.php';
          break;

      case 'pen_map_orgaos_externos_mapeamento_desativar':
      case 'pen_map_orgaos_externos_mapeamento':
      case 'pen_map_orgaos_externos_mapeamento_gerenciar':
      case 'pen_map_orgaos_externos_mapeamento_excluir':
          include_once __DIR__ . '/pen_map_orgaos_mapeamento_tipo_listar.php';
          break;

      case 'pen_map_unidade_listar':
      case 'pen_map_unidade_excluir':
          include_once __DIR__ . '/pen_map_unidade_listar.php';
          break;

      case 'pen_parametros_configuracao':
      case 'pen_parametros_configuracao_salvar':
          include_once __DIR__ . '/pen_parametros_configuracao.php';
          break;

      case 'pen_map_tipo_documento_envio_padrao_atribuir':
      case 'pen_map_tipo_documento_envio_padrao_consultar':
          include_once __DIR__ . '/pen_map_tipo_documento_envio_padrao.php';
          break;

      case 'pen_map_tipo_doc_recebimento_padrao_atribuir':
      case 'pen_map_tipo_doc_recebimento_padrao_consultar':
          include_once __DIR__ . '/pen_map_tipo_doc_recebimento_padrao.php';
          break;

      case 'pen_envio_processo_lote_cadastrar':
          include_once __DIR__ . '/pen_envio_processo_lote_cadastrar.php';
          break;

      case 'pen_expedir_bloco':
          include_once __DIR__ . '/pen_expedir_bloco.php';
          break;

      case 'pen_map_envio_parcial_listar':
      case 'pen_map_envio_parcial_excluir':
          include_once __DIR__ . '/pen_map_envio_parcial_listar.php';
          break;

      case 'pen_map_envio_parcial_salvar':
      case 'pen_map_envio_parcial_cadastrar':
      case 'pen_map_envio_parcial_visualizar':
          include_once __DIR__ . '/pen_map_envio_parcial_cadastrar.php';
          break;

      case 'pen_reproduzir_ultimo_tramite':
          require_once dirname(__FILE__) . '/pen_reproduzir_ultimo_tramite.php';
          break;

      default:
          return false;

    }
      return true;
  }

  public function autoCompletarUnidadesCadastrar($bolPermiteEnvio = false)
    {
      $arrObjEstruturaDTO = (array) ProcessoEletronicoINT::autoCompletarEstruturasAutoCompletar($_POST['id_repositorio'], $_POST['palavras_pesquisa'], $bolPermiteEnvio);
    if (count($arrObjEstruturaDTO['itens']) == 0) {
        return '<itens><item id="0" descricao="Unidade não Encontrada."></item></itens>';
    }

      return self::gerarXMLItensArrInfraDTOAutoCompletar($arrObjEstruturaDTO, 'NumeroDeIdentificacaoDaEstrutura', 'Nome');
  }

  public function autoCompletarExpedirProcedimento()
    {
      $xml = null;
      $bolPermiteEnvio = false;
    if ($_GET['acao'] != 'pen_procedimento_expedir' && $_GET['acao_ajax'] != 'pen_unidade_auto_completar_expedir_procedimento') {
        $bolPermiteEnvio = true;
    }

      $restricaoCadastrada = false;
    if ($bolPermiteEnvio == false) {
        $objUnidadeDTO = new PenUnidadeDTO();
        $objUnidadeDTO->retNumIdUnidadeRH();
        $objUnidadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());

        $objUnidadeRN = new UnidadeRN();
        $objUnidadeDTO = $objUnidadeRN->consultarRN0125($objUnidadeDTO);

        $arrObjEstruturaDTO = [];
      if (!is_null($objUnidadeDTO)) {
        try {
          $objPenUnidadeRestricaoDTO = new PenUnidadeRestricaoDTO();
          $objPenUnidadeRestricaoDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
          $objPenUnidadeRestricaoDTO->setNumIdUnidadeRH($objUnidadeDTO->getNumIdUnidadeRH());
          $objPenUnidadeRestricaoDTO->setNumIdUnidadeRestricao($_POST['id_repositorio']);
          $objPenUnidadeRestricaoDTO->setNumIdUnidadeRHRestricao(null, InfraDTO::$OPER_DIFERENTE);
          $objPenUnidadeRestricaoDTO->retNumIdUnidadeRHRestricao();
          $objPenUnidadeRestricaoDTO->retStrNomeUnidadeRHRestricao();

          $objPenUnidadeRestricaoRN = new PenUnidadeRestricaoRN();
          $restricaoCadastrada = $objPenUnidadeRestricaoRN->contar($objPenUnidadeRestricaoDTO);
          $restricaoCadastrada = $restricaoCadastrada > 0;

          if ($restricaoCadastrada) {
            if (is_numeric($_POST['palavras_pesquisa'])) {
              $objPenUnidadeRestricaoDTO->setNumIdUnidadeRHRestricao($_POST['palavras_pesquisa']);
            }
          
            if (!is_numeric($_POST['palavras_pesquisa'])) {
              $objPenUnidadeRestricaoDTO->setStrNomeUnidadeRHRestricao('%' . $_POST['palavras_pesquisa'] . '%', InfraDTO::$OPER_LIKE);
            }    
        
            $arrEstruturas = $objPenUnidadeRestricaoRN->listar($objPenUnidadeRestricaoDTO);

            foreach ($arrEstruturas as $unidade) {
              if ($unidade->getNumIdUnidadeRHRestricao() != null) {
                        $arrObjEstruturaDTO[] = $unidade;
              }
            }
          }
        } catch (Exception $e) {
        }
      }

      if (count($arrObjEstruturaDTO) > 0) {
          $xml = InfraAjax::gerarXMLItensArrInfraDTO($arrObjEstruturaDTO, 'IdUnidadeRHRestricao', 'NomeUnidadeRHRestricao');
      } else if ($restricaoCadastrada) {
          return '<itens><item id="0" descricao="Unidade não Encontrada."></item></itens>';
      }
    }
    if (!$restricaoCadastrada && (is_null($arrObjEstruturaDTO) || count($arrObjEstruturaDTO) == 0)) {
        $xml = $this->autoCompletarUnidadesCadastrar($bolPermiteEnvio);
    }

      return $xml;
  }

  public function penUnidadeAutoCompletarMapeados()
    {
      // DTO de paginao
      $objPenUnidadeDTOFiltro = new PenUnidadeDTO();
      $objPenUnidadeDTOFiltro->retStrSiglaUnidadeRH();
      $objPenUnidadeDTOFiltro->retStrNomeUnidadeRH();
      $objPenUnidadeDTOFiltro->retNumIdUnidade();
      $objPenUnidadeDTOFiltro->retNumIdUnidadeRH();

      // Filtragem
    if (!empty($_POST['palavras_pesquisa']) && $_POST['palavras_pesquisa'] !== 'null') {
        $objPenUnidadeDTOFiltro->setStrNomeUnidadeRH('%' . $_POST['palavras_pesquisa'] . '%', InfraDTO::$OPER_LIKE);
    }

      $objPenUnidadeRN = new PenUnidadeRN();
      $objArrPenUnidadeDTO = (array) $objPenUnidadeRN->listar($objPenUnidadeDTOFiltro);
    if (count($objArrPenUnidadeDTO) == 0) {
        return '<itens><item id="0" descricao="Unidade não Encontrada."></item></itens>';
    }

    foreach ($objArrPenUnidadeDTO as $dto) {
        $dto->setNumIdUnidadeMap($dto->getNumIdUnidadeRH());
        $dto->setStrDescricaoMap($dto->getStrNomeUnidadeRH() . '-' . $dto->getStrSiglaUnidadeRH());
    }

      return InfraAjax::gerarXMLItensArrInfraDTO($objArrPenUnidadeDTO, 'IdUnidadeMap', 'DescricaoMap');
  }

  public function processarControladorAjax($strAcao)
    {
      $xml = null;

    switch ($_GET['acao_ajax']) {

      case 'pen_listar_repositorios_estruturas_auto_completar':
        try {
            $arrObjEstruturaDTO = (array) ProcessoEletronicoINT::autoCompletarRepositorioEstruturas($_POST['palavras_pesquisa']);
          if (count($arrObjEstruturaDTO) > 0) {
            $xml = InfraAjax::gerarXMLItensArrInfraDTO($arrObjEstruturaDTO, 'Id', 'Nome');
          } else {
              return '<itens><item grupo="vazio" id="0" descricao="Repositório de Estruturas não Encontrado."></item></itens>';
          }
        }catch(Throwable $e){
            $mensagem = "Falha na obtenção dos Repositórios de Estruturas Organizacionais";
            throw new InfraException($mensagem, $e);
        }
          break;
      case 'pen_unidade_auto_completar_cadastro':
          $xml = $this->autoCompletarUnidadesCadastrar();
          break;
      case 'pen_unidade_auto_completar_expedir_procedimento':
          $xml = $this->autoCompletarExpedirProcedimento();
          break;

      case 'pen_unidade_auto_completar_mapeados':
          $xml = $this->penUnidadeAutoCompletarMapeados();
          break;

      case 'pen_apensados_auto_completar_expedir_procedimento':
          $dblIdProcedimentoAtual = $_POST['id_procedimento_atual'];
          $numIdUnidadeAtual = SessaoSEI::getInstance()->getNumIdUnidadeAtual();
          $arrObjProcedimentoDTO = ProcessoEletronicoINT::autoCompletarProcessosApensados($dblIdProcedimentoAtual, $numIdUnidadeAtual, $_POST['palavras_pesquisa']);
          $xml = InfraAjax::gerarXMLItensArrInfraDTO($arrObjProcedimentoDTO, 'IdProtocolo', 'ProtocoloFormatadoProtocolo');
          break;


      case 'pen_procedimento_expedir_validar':
          include_once __DIR__ . '/pen_procedimento_expedir_validar.php';
          break;

      case 'pen_validar_expedir_lote':
          include_once __DIR__ . '/pen_validar_expedir_lote.php';
          break;

      case 'pen_procedimento_expedir_cancelar':
          $numIdTramite = $_POST['id_tramite'];
          $objProcessoEletronicoRN = new ProcessoEletronicoRN();
          $result = json_encode($objProcessoEletronicoRN->cancelarTramite($numIdTramite));
          InfraAjax::enviarJSON($result);
          exit(0);

      case 'pen_pesquisar_unidades_administrativas_estrutura_pai':
          $idRepositorioEstruturaOrganizacional = $_POST['idRepositorioEstruturaOrganizacional'];
          $numeroDeIdentificacaoDaEstrutura = $_POST['numeroDeIdentificacaoDaEstrutura'];

          $objProcessoEletronicoRN = new ProcessoEletronicoRN();
          $arrEstruturas = $objProcessoEletronicoRN->consultarEstruturasPorEstruturaPai($idRepositorioEstruturaOrganizacional, $numeroDeIdentificacaoDaEstrutura == "" ? null : $numeroDeIdentificacaoDaEstrutura);

          print json_encode($arrEstruturas);
          exit(0);


      case 'pen_pesquisar_unidades_administrativas_estrutura_pai_textual':
          $registrosPorPagina = 50;
          $idRepositorioEstruturaOrganizacional = $_POST['idRepositorioEstruturaOrganizacional'];
          $numeroDeIdentificacaoDaEstrutura     = $_POST['numeroDeIdentificacaoDaEstrutura'];
          $siglaUnidade = ($_POST['siglaUnidade'] == '') ? null : mb_convert_encoding($_POST['siglaUnidade'], 'UTF-8', 'ISO-8859-1');
          $nomeUnidade  = ($_POST['nomeUnidade']  == '') ? null : mb_convert_encoding($_POST['nomeUnidade'], 'UTF-8', 'ISO-8859-1');
          $offset       = $_POST['offset'] * $registrosPorPagina;

          $objProcessoEletronicoRN = new ProcessoEletronicoRN();
          $arrObjEstruturaDTO = $objProcessoEletronicoRN->listarEstruturasBuscaTextual($idRepositorioEstruturaOrganizacional, null, $numeroDeIdentificacaoDaEstrutura, $nomeUnidade, $siglaUnidade, $offset, $registrosPorPagina);

          $interface = new ProcessoEletronicoINT();
          //Gera a hierarquia de SIGLAS das estruturas
          $arrHierarquiaEstruturaDTO = $interface->gerarHierarquiaEstruturas($arrObjEstruturaDTO);

          $arrEstruturas['estrutura'] = [];
        if(!is_null($arrHierarquiaEstruturaDTO[0])) {
          foreach ($arrHierarquiaEstruturaDTO as $key => $estrutura) {
            //Monta um array com as estruturas para retornar o JSON
            $arrEstruturas['estrutura'][$key]['nome'] = mb_convert_encoding($estrutura->get('Nome'), 'UTF-8', 'ISO-8859-1');
            $arrEstruturas['estrutura'][$key]['numeroDeIdentificacaoDaEstrutura'] = $estrutura->get('NumeroDeIdentificacaoDaEstrutura');
            $arrEstruturas['estrutura'][$key]['sigla'] = mb_convert_encoding($estrutura->get('Sigla'), 'UTF-8', 'ISO-8859-1');
            $arrEstruturas['estrutura'][$key]['ativo'] = $estrutura->get('Ativo');
            $arrEstruturas['estrutura'][$key]['aptoParaReceberTramites'] = $estrutura->get('AptoParaReceberTramites');
            $arrEstruturas['estrutura'][$key]['codigoNoOrgaoEntidade'] = $estrutura->get('CodigoNoOrgaoEntidade');

          }
            $arrEstruturas['totalDeRegistros']   = $estrutura->get('TotalDeRegistros');
            $arrEstruturas['registrosPorPagina'] = $registrosPorPagina;
        }

          print json_encode($arrEstruturas);
          exit(0);
    }

      return $xml;
  }


  public function processarControladorWebServices($servico)
    {
      $strArq = null;
    switch ($_GET['servico']) {
      case 'modpen':
          $strArq =  __DIR__ . '/ws/modpen.wsdl';
          break;
    }

      return $strArq;
  }

  public static function getDiretorio()
    {
      $arrConfig = ConfiguracaoSEI::getInstance()->getValor('SEI', 'Modulos');
      $strModulo = $arrConfig['PENIntegracao'];
      return "modulos/".$strModulo;
  }

    /**
     * Verifica a compatibilidade e correta configuracao do módulo de Barramento, registrando mensagem de alerta no log do sistema
     *
     * Regras de verificação da disponibilidade do PEN não devem ser aplicadas neste ponto pelo risco de erro geral no sistema em
     * caso de indisponibilidade momentãnea do Barramento de Serviços.
     */
  public static function verificarCompatibilidadeConfiguracoes()
    {
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

      // Desativado verificaÃ§Ãµes de compatibilidade do banco de dados por nÃ£o ser todas as versÃµes
      // que necessitam mudanÃ§as no banco de dados
    try {
        $objVerificadorInstalacaoRN->verificarCompatibilidadeBanco();
    } catch (\Exception $e) {
        LogSEI::getInstance()->gravar($e, LogSEI::$AVISO);
        return false;
    }

      return true;
  }

    /**
     * Compara duas diferentes versÃµes do sistem para avaliar a precedÃªncia de ambas
     *
     * Normaliza o formato de nÃºmero de versÃ£o considerando dois caracteres para cada item (3.0.15 -> 030015)
     * - Se resultado for IGUAL a 0, versÃµes iguais
     * - Se resultado for MAIOR que 0, versÃ£o 1 Ã© posterior a versÃ£o 2
     * - Se resultado for MENOR que 0, versÃ£o 1 Ã© anterior a versÃ£o 2
     */
  public static function compararVersoes($strVersao1, $strVersao2)
    {
      $numVersao1 = explode('.', $strVersao1);
      $numVersao1 = array_map(
          function ($item) {
              return str_pad($item, 2, '0', STR_PAD_LEFT);
          }, $numVersao1
      );
      $numVersao1 = intval(join('', $numVersao1));

      $numVersao2 = explode('.', $strVersao2);
      $numVersao2 = array_map(
          function ($item) {
              return str_pad($item, 2, '0', STR_PAD_LEFT);
          }, $numVersao2
      );
      $numVersao2 = intval(join('', $numVersao2));

      return $numVersao1 - $numVersao2;
  }


  public function processarPendencias()
    {
      SessaoSEI::getInstance(false);
      ProcessarPendenciasRN::getInstance()->processarPendencias();
  }

  public function assinarDocumento($arrObjDocumentoAPI = [])
    {

      $objProcessoEletronicoRN = new ProcessoEletronicoRN();

    foreach($arrObjDocumentoAPI as $objDocumentoAPI){
        //Pode ser assinado documentos de mais de um processo
        $objProcessoEletronicoPesquisaDTO = new ProcessoEletronicoDTO();
        $objProcessoEletronicoPesquisaDTO->setDblIdProcedimento($objDocumentoAPI->getIdProcedimento());
        $objUltimoTramiteRecebidoDTO = $objProcessoEletronicoRN->consultarUltimoTramite($objProcessoEletronicoPesquisaDTO);

        //Se não tiver trâmite não há o que se checar
      if(!is_null($objUltimoTramiteRecebidoDTO)) {
        $arrObjComponentesDigitais = $objProcessoEletronicoRN->listarComponentesDigitais($objUltimoTramiteRecebidoDTO);
        if(!is_null($arrObjComponentesDigitais)) {
          foreach($arrObjComponentesDigitais as $objComponentesDigitais){
            if ($objComponentesDigitais->getDblIdDocumento() == $objDocumentoAPI->getIdDocumento()) {
                  $nomeModulo = $this->getNome();
                  $mensagem = "$nomeModulo: Prezado(a) usuário(a) esse documento já foi tramitado externamente via Tramita GOV.BR. Por esse motivo, este documento não pode receber uma nova assinatura.";
                  LogSEI::getInstance()->gravar($mensagem, LogSEI::$AVISO);
                  $objInfraException = new InfraException();
                  $objInfraException->adicionarValidacao($mensagem);
                  $objInfraException->lancarValidacoes();
            }
          }
        }
      }
    }
  }

  /**
   * Verifica se os processos estão anexados e se já foram tramitados via Tramita GOV.BR para permitir o desanexar
   *  - Se o processo estiver anexado e tiver sido tramitado, não é permitido desanexar e é lançada uma exceção
   *  - Se o processo não tiver sido tramitado, é permitido desanexar
   *  - Se o processo não estiver anexado, é permitido desanexar
   * 
   * @param ProcedimentoAPI $objProcedimentoAPIPrincipal - Processo principal do qual o outro processo está anexado
   * @param ProcedimentoAPI $objProcedimentoAPIAnexado - Processo que está anexado ao processo principal
   * @return void
   * @throws InfraException - Exceção lançada quando o processo anexado já tiver sido tramitado via Tramita GOV.BR, impossibilitando o desanexar
   */
  public function desanexarProcesso(ProcedimentoAPI $objProcedimentoAPIPrincipal, ProcedimentoAPI $objProcedimentoAPIAnexado)
  {
    // Status permitidos para desanexar
    $statusPermitidos = [
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_SOLICITACAO_PENDENCIA,
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_NAO_INICIADO,
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO,
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_ENVIADOS_REMETENTE,
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_METADADOS_RECEBIDO_DESTINATARIO,
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO,
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_ENVIADO_DESTINATARIO,
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE,
    ];

    $objProcessoEletronicoRN = new ProcessoEletronicoRN();

    // 1. Verifica se o processo principal já foi tramitado com sucesso
    $objProcessoEletronicoPesquisaPaiDTO = new ProcessoEletronicoDTO();
    $objProcessoEletronicoPesquisaPaiDTO->setDblIdProcedimento($objProcedimentoAPIPrincipal->getIdProcedimento());
    $objTramitePaiDTO = $objProcessoEletronicoRN->consultarUltimoTramite($objProcessoEletronicoPesquisaPaiDTO);

    $paiTramitado = false;
    if ($objTramitePaiDTO != null) {
      $numIdTramitePai = $objTramitePaiDTO->getNumIdTramite();
      if (!empty($numIdTramitePai)) {
        $arrTramitesPai = $objProcessoEletronicoRN->consultarTramites($numIdTramitePai);
        if (is_array($arrTramitesPai) && count($arrTramitesPai) > 0) {
          $statusTramitesPai = array_column($arrTramitesPai, 'situacaoAtual');
          // Se algum status do pai for permitido, considera tramitado com sucesso
          $paiTramitado = count(array_intersect($statusTramitesPai, $statusPermitidos)) > 0;
          if ($paiTramitado) {
            $nomeModulo = $this->getNome();
            $mensagem = "$nomeModulo: Prezado(a) usuário(a), não é possível desanexar o processo " . $objProcedimentoAPIAnexado->getNumeroProtocolo() . " do processo " . $objProcedimentoAPIPrincipal->getNumeroProtocolo() . ", pois o processo " . $strNumeroProtocoloAnexado . " já foi tramitado via Tramita GOV.BR.";
            $objInfraException = new InfraException();
            $objInfraException->adicionarValidacao($mensagem);
            $objInfraException->lancarValidacoes();
          }
        }
      }
    }
  }
}
class ModuloIncompativelException extends InfraException
{
 
}