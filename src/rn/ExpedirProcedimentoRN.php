<?php

require_once DIR_SEI_WEB.'/SEI.php';

class ExpedirProcedimentoRN extends InfraRN
{

    const STA_SIGILO_PUBLICO = '1';
    const STA_SIGILO_RESTRITO = '2';
    const STA_SIGILO_SIGILOSO = '3';

    const STA_TIPO_PESSOA_FISICA = 'fisica';
    const STA_TIPO_PESSOA_JURIDICA = 'juridica';
    const STA_TIPO_PESSOA_ORGAOPUBLICO = 'orgaopublico';

    const ALGORITMO_HASH_DOCUMENTO = 'SHA256';
    const ALGORITMO_HASH_ASSINATURA = 'SHA256withRSA';

    const REGEX_ARQUIVO_TEXTO = '/^application\/|^text\//';
    const REGEX_ARQUIVO_IMAGEM = '/^image\//';
    const REGEX_ARQUIVO_AUDIO = '/^audio\//';
    const REGEX_ARQUIVO_VIDEO = '/^video\//';

    const TC_TIPO_CONTEUDO_TEXTO = 'txt';
    const TC_TIPO_CONTEUDO_IMAGEM = 'img';
    const TC_TIPO_CONTEUDO_AUDIO = 'aud';
    const TC_TIPO_CONTEUDO_VIDEO = 'vid';
    const TC_TIPO_CONTEUDO_OUTROS = 'out';

    //TODO: Alterar codificao do SEI para reconhecer esse novo estado do processo
    //Esse estado ser utilizado juntamente com os estados da expedio
    const TE_PROCEDIMENTO_BLOQUEADO = '4';
    const TE_PROCEDIMENTO_EM_PROCESSAMENTO = '5';

    //Verso com mudana na API relacionada  obrigatoriedade do carimbo de publicao
    const VERSAO_CARIMBO_PUBLICACAO_OBRIGATORIO = '3.0.7';

    private $objProcessoEletronicoRN;
    private $objParticipanteRN;
    private $objProcedimentoRN;
    private $objProtocoloRN;
    private $objDocumentoRN;
    private $objAtividadeRN;
    private $objUsuarioRN;
    private $objUnidadeRN;
    private $objOrgaoRN;
    private $objSerieRN;
    private $objAnexoRN;
    private $objPenParametroRN;
    private $objPenRelTipoDocMapEnviadoRN;
    private $objAssinaturaRN;
    private $barraProgresso;
    private $objProcedimentoAndamentoRN;
    private $fnEventoEnvioMetadados;
    private $objPenDebug;
    private $objCacheMetadadosProtocolo=[];

    private $arrPenMimeTypes = ["application/pdf", "application/vnd.oasis.opendocument.text", "application/vnd.oasis.opendocument.formula", "application/vnd.oasis.opendocument.spreadsheet", "application/vnd.oasis.opendocument.presentation", "text/xml", "text/rtf", "text/html", "text/plain", "text/csv", "image/gif", "image/jpeg", "image/png", "image/svg+xml", "image/tiff", "image/bmp", "audio/mp4", "audio/midi", "audio/ogg", "audio/vnd.wave", "video/avi", "video/mpeg", "video/mp4", "video/ogg", "video/webm"];


    private $contadorDaBarraDeProgresso;

  public function __construct()
    {
      parent::__construct();

      //TODO: Remover criao de objetos de negcio no construtor da classe para evitar problemas de performance desnecessrios
      $this->objProcessoEletronicoRN = new ProcessoEletronicoRN();
      $this->objParticipanteRN = new ParticipanteRN();
      $this->objProcedimentoRN = new ProcedimentoRN();
      $this->objProtocoloRN = new ProtocoloRN();
      $this->objDocumentoRN = new DocumentoRN();
      $this->objAtividadeRN = new AtividadeRN();
      $this->objUsuarioRN = new UsuarioRN();
      $this->objUnidadeRN = new UnidadeRN();
      $this->objOrgaoRN = new OrgaoRN();
      $this->objSerieRN = new SerieRN();
      $this->objAnexoRN = new AnexoRN();
      $this->objPenParametroRN = new PenParametroRN();
      $this->objPenRelTipoDocMapEnviadoRN = new PenRelTipoDocMapEnviadoRN();
      $this->objAssinaturaRN = new AssinaturaRN();
      $this->objProcedimentoAndamentoRN = new ProcedimentoAndamentoRN();
      $this->objPenDebug = DebugPen::getInstance("PROCESSAMENTO");

      $this->barraProgresso = new InfraBarraProgresso();
      $this->barraProgresso->setNumMin(0);
  }

  protected function inicializarObjInfraIBanco()
    {
      return BancoSEI::getInstance();
  }

  private function gravarLogDebug($parStrMensagem, $parNumIdentacao = 0, $parBolLogTempoProcessamento = true)
    {
      $this->objPenDebug->gravar($parStrMensagem, $parNumIdentacao, $parBolLogTempoProcessamento);
  }

  protected function expedirProcedimentoControlado(ExpedirProcedimentoDTO $objExpedirProcedimentoDTO)
    {
      $numIdTramite = 0;
    try {
        //Valida Permiss�o
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_procedimento_expedir', __METHOD__, $objExpedirProcedimentoDTO);
        $dblIdProcedimento = $objExpedirProcedimentoDTO->getDblIdProcedimento();

        $objPenBlocoProcessoRN = new PenBlocoProcessoRN();
        $bolSinProcessamentoEmBloco = $objExpedirProcedimentoDTO->getBolSinProcessamentoEmBloco();
        $numIdBloco = $objExpedirProcedimentoDTO->getNumIdBloco();
        $numIdAtividade = $objExpedirProcedimentoDTO->getNumIdAtividade();
        $numIdUnidade = $objExpedirProcedimentoDTO->getNumIdUnidade();

      if(!$bolSinProcessamentoEmBloco) {
        $this->barraProgresso->exibir();
        $this->barraProgresso->setStrRotulo(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_VALIDACAO);
      }else{
          $this->gravarLogDebug("Processando envio de processo [expedirProcedimento] com Procedimento $dblIdProcedimento", 0, true);
          $numTempoInicialRecebimento = microtime(true);

          $this->gravarLogDebug(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_VALIDACAO, 2);
          $objPenBlocoProcessoDTO = new PenBlocoProcessoDTO();
          $objPenBlocoProcessoDTO->setDblIdProtocolo($dblIdProcedimento);
          $objPenBlocoProcessoDTO->setNumIdBlocoProcesso($numIdBloco);
          $objPenBlocoProcessoDTO->retTodos();

          $objPenBlocoProcessoRN = new PenBlocoProcessoRN();
          $objPenBlocoProcessoDTO = $objPenBlocoProcessoRN->consultar($objPenBlocoProcessoDTO);
      }

        $objInfraException = new InfraException();
        //Carregamento dos dados de processo e documento para valida��o e envio externo
        $objProcedimentoDTO = $this->consultarProcedimento($dblIdProcedimento);
        $objProcedimentoDTO->setArrObjDocumentoDTO($this->listarDocumentos($dblIdProcedimento));
        $objProcedimentoDTO->setArrObjParticipanteDTO($this->listarInteressados($dblIdProcedimento));
        $this->validarPreCondicoesExpedirProcedimento($objInfraException, $objProcedimentoDTO, null, $bolSinProcessamentoEmBloco);
        $this->validarParametrosExpedicao($objInfraException, $objExpedirProcedimentoDTO);

        //Apresentao da mensagens de validao na janela da barra de progresso
      if($objInfraException->contemValidacoes()) {
        if(!$bolSinProcessamentoEmBloco) {
            $this->barraProgresso->mover(0);
            $this->barraProgresso->setStrRotulo('Erro durante valida��o dos dados do processo.');
            $objInfraException->lancarValidacoes();
        }else{

            $arrErros = [];
          foreach($objInfraException->getArrObjInfraValidacao() as $objInfraValidacao) {
              $strAtributo = $objInfraValidacao->getStrAtributo();
            if(!array_key_exists($strAtributo, $arrErros)) {
              $arrErros[$strAtributo] = [];
            }
              $arrErros[$strAtributo][] = mb_convert_encoding($objInfraValidacao->getStrDescricao(), 'UTF-8', 'ISO-8859-1');
          }

            $this->gravarLogDebug(sprintf('Erro durante valida��o dos dados do processo %s.', $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado(), $arrErros), 2);
            LogSEI::getInstance()->gravar("Erro(s) observado(s) na valida��o do tr�mite de bloco externo $numIdBloco: ".InfraException::inspecionar($objInfraException));
              
            $objPenBlocoProcessoRN->desbloquearProcessoBloco($dblIdProcedimento);
            return false;
        }
      }

        //Busca metadados do processo registrado em tr�mite anterior
        $objMetadadosProcessoTramiteAnterior = $this->consultarMetadadosPEN($dblIdProcedimento);

        //Constru��o do cabe�alho para envio do processo
        $objProcessoEletronicoPesquisaDTO = new ProcessoEletronicoDTO();
        $objProcessoEletronicoPesquisaDTO->setDblIdProcedimento($dblIdProcedimento);
        $objUltimoTramiteRecebidoDTO = $this->objProcessoEletronicoRN->consultarUltimoTramiteRecebido($objProcessoEletronicoPesquisaDTO);

      if(isset($objMetadadosProcessoTramiteAnterior->documento)) {
          $strNumeroRegistro = null;
      }else{
          $strNumeroRegistro = isset($objUltimoTramiteRecebidoDTO) ? $objUltimoTramiteRecebidoDTO->getStrNumeroRegistro() : $objMetadadosProcessoTramiteAnterior?->NRE;
      }

        $objCabecalho = $this->construirCabecalho($objExpedirProcedimentoDTO, $strNumeroRegistro, $dblIdProcedimento);

        //Constru��o do processo para envio
        $arrProcesso = $this->construirProcessoREST($dblIdProcedimento, $objExpedirProcedimentoDTO->getArrIdProcessoApensado(), $objMetadadosProcessoTramiteAnterior);

        //Obt�m o tamanho total da barra de progreso
        $nrTamanhoTotalBarraProgresso = $this->obterTamanhoTotalDaBarraDeProgressoREST($arrProcesso);

      if(!$bolSinProcessamentoEmBloco) {
          //Atribui o tamanho m�ximo da barra de progresso
          $this->barraProgresso->setNumMax($nrTamanhoTotalBarraProgresso);

          //Exibe a barra de progresso ap�s definir o seu tamanho
          $this->barraProgresso->mover(ProcessoEletronicoINT::NEE_EXPEDICAO_ETAPA_PROCEDIMENTO);
          $this->barraProgresso->setStrRotulo(sprintf(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_PROCEDIMENTO, $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado()));
      }else{
          $this->gravarLogDebug(sprintf(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_PROCEDIMENTO, $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado()), 2);
      }

        //Cancela tr�mite anterior caso este esteja travado em status inconsistente 1 - STA_SITUACAO_TRAMITE_INICIADO
        $objTramitesAnteriores = $this->consultarTramitesAnteriores($strNumeroRegistro);
      if($objTramiteInconsistente = $this->necessitaCancelamentoTramiteAnterior($objTramitesAnteriores)) {
          $this->objProcessoEletronicoRN->cancelarTramite($objTramiteInconsistente->IDT);
      }

        $param = [
        'novoTramiteDeProcesso' => [
            'cabecalho' => $objCabecalho,
            'processo' => $arrProcesso
        ],
        'dblIdProcedimento' => $dblIdProcedimento
        ];

        $novoTramite = $this->objProcessoEletronicoRN->enviarProcessoREST($param);

        $numIdTramite = $novoTramite->IDT;
        $this->lancarEventoEnvioMetadados($numIdTramite);

        $this->atualizarPenProtocolo($dblIdProcedimento);

        if (isset($novoTramite)) {
            $objTramite = $novoTramite;
            $this->objProcedimentoAndamentoRN->setOpts($objTramite->NRE, $objTramite->IDT, ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO), $dblIdProcedimento);

          try {
              $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento('Envio do metadados do processo', 'S'));

            if($bolSinProcessamentoEmBloco) {
              $this->gravarLogDebug(sprintf('Envio do metadados do processo %s', $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado()), 2);
              $objPenBlocoProcessoDTO->setNumIdAndamento(ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO);
              $objPenBlocoProcessoRN->alterar($objPenBlocoProcessoDTO);
              $idAtividadeExpedicao = $numIdAtividade;
            }else{
                $idAtividadeExpedicao = $this->bloquearProcedimentoExpedicao($objExpedirProcedimentoDTO, $arrProcesso['idProcedimentoSEI']);
            }

              $this->objProcessoEletronicoRN->cadastrarTramiteDeProcesso(
                  $arrProcesso['idProcedimentoSEI'],
                  $objTramite->NRE,
                  $objTramite->IDT,
                  ProcessoEletronicoRN::$STA_TIPO_TRAMITE_ENVIO,
                  $objTramite->dataHoraDeRegistroDoTramite,
                  $objExpedirProcedimentoDTO->getNumIdRepositorioOrigem(),
                  $objExpedirProcedimentoDTO->getNumIdUnidadeOrigem(),
                  $objExpedirProcedimentoDTO->getNumIdRepositorioDestino(),
                  $objExpedirProcedimentoDTO->getNumIdUnidadeDestino(),
                  $arrProcesso,
                  $objTramite->ticketParaEnvioDeComponentesDigitais,
                  $objTramite->processosComComponentesDigitaisSolicitados,
                  $bolSinProcessamentoEmBloco,
                  $numIdUnidade
              );


                $this->objProcessoEletronicoRN->cadastrarTramitePendente($objTramite->IDT, $idAtividadeExpedicao);

                //TODO: Erro no BARRAMENTO: Processo no pode ser enviado se possuir 2 documentos iguais(mesmo hash)
                //TODO: Melhoria no barramento de servios. O mtodo solicitar metadados no deixa claro quais os componentes digitais que
                //precisam ser baixados. No cenrio de retorno de um processo existente, a nica forma  consultar o status do tr�mite para
                //saber quais precisam ser baixados. O processo poderia ser mais otimizado se o retorno nos metadados j informasse quais os
                //componentes precisam ser baixados, semelhante ao que ocorre no enviarProcesso onde o barramento informa quais os componentes
                //que precisam ser enviados

                $this->enviarComponentesDigitais($objTramite->NRE, $objTramite->IDT, $arrProcesso['protocolo'], $bolSinProcessamentoEmBloco);

                //TODO: Ao enviar o processo e seus documentos, necessrio bloquear os documentos para alterao
                //pois eles j foram visualizados
                //$objDocumentoRN = new DocumentoRN();
                //$objDocumentoRN->bloquearConsultado($objDocumentoRN->consultarRN0005($objDocumentoDTO));

                //TODO: Implementar o registro de auditoria, armazenando os metadados xml enviados para o PEN

                //TODO: Alterar atualizao para somente apresentar ao final de todo o tr�mite
                //$this->barraProgresso->mover(ProcessoEletronicoINT::NEE_EXPEDICAO_ETAPA_CONCLUSAO);

            if(!$bolSinProcessamentoEmBloco) {
              $this->barraProgresso->mover($this->barraProgresso->getNumMax());
              $this->barraProgresso->setStrRotulo(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_CONCLUSAO);
            }else{
              $this->gravarLogDebug('Conclu�do envio dos componentes do processo', 2);
              $objPenBlocoProcessoDTO->setNumIdAndamento(ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_ENVIADOS_REMETENTE);
              $objPenBlocoProcessoRN->alterar($objPenBlocoProcessoDTO);
            }

              $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento('Conclu�do envio dos componentes do processo', 'S'));

              $this->receberReciboDeEnvio($objTramite->IDT);

              $this->gravarLogDebug(sprintf('Tr�mite do processo %s foi conclu�do', $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado()), 2);

              $numTempoTotalRecebimento = round(microtime(true) - $numTempoInicialRecebimento, 2);
              $this->gravarLogDebug("Finalizado o envio de protocolo n�mero " . $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado() . " (Tempo total: {$numTempoTotalRecebimento}s)", 0, true);
          }
          catch (\Exception $e) {
              //Realiza o desbloqueio do processo
            try{ $this->desbloquearProcessoExpedicao($arrProcesso['idProcedimentoSEI']); 
            } catch (Exception $ex) { 
            }

              //Realiza o cancelamento do tramite
            try{
              if($numIdTramite != 0) {
                  $this->objProcessoEletronicoRN->cancelarTramite($numIdTramite);
              }
            } catch (InfraException) { 
            }

              $this->registrarAndamentoExpedicaoAbortada($arrProcesso['idProcedimentoSEI']);

              $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento('Conclu�do envio dos componentes do processo', 'N'));
              throw $e;
          }
        }

    } catch (\Exception $e) {
        $this->gravarLogDebug("Erro processando envio de processo: $e", 0, true);
      if($bolSinProcessamentoEmBloco) {
          $objPenBlocoProcessoRN->desbloquearProcessoBloco($dblIdProcedimento);
      } else {
          throw new InfraException('M�dulo do Tramita: Falha de comunica��o com o servi�os de integra��o. Por favor, tente novamente mais tarde.', $e);
      }
    }
  }

    /**
     * Busca metadados do processo registrado no Barramento de Servi�os do PEN em tr�mites anteriores
     *
     * @return stdClass Metadados do Processo
     */
  private function consultarMetadadosPEN($parDblIdProcedimento)
    {
      $objMetadadosProtocolo = null;
    if(array_key_exists($parDblIdProcedimento, $this->objCacheMetadadosProtocolo)) {
        $objMetadadosProtocolo = $this->objCacheMetadadosProtocolo[$parDblIdProcedimento];
    } else {
      try{
          $objTramiteDTO = new TramiteDTO();
          $objTramiteDTO->setNumIdProcedimento($parDblIdProcedimento);
          $objTramiteDTO->setStrStaTipoTramite(ProcessoEletronicoRN::$STA_TIPO_TRAMITE_RECEBIMENTO);
          $objTramiteDTO->setOrd('IdTramite', InfraDTO::$TIPO_ORDENACAO_DESC);
          $objTramiteDTO->setNumMaxRegistrosRetorno(1);
          $objTramiteDTO->retNumIdTramite();

          $objTramiteBD = new TramiteBD($this->getObjInfraIBanco());
          $objTramiteDTO = $objTramiteBD->consultar($objTramiteDTO);

        if(isset($objTramiteDTO)) {
          $parNumIdentificacaoTramite = $objTramiteDTO->getNumIdTramite();
          $objRetorno = $this->objProcessoEletronicoRN->solicitarMetadados($parNumIdentificacaoTramite);

          if(isset($objRetorno)) {
                $objMetadadosProtocolo = $objRetorno->metadados;
          }
        }
      }
      catch(Exception){
          //Em caso de falha na comunica��o com o barramento neste ponto, o procedimento deve serguir em frente considerando
          //que os metadados do protocolo n�o pode ser obtida
          LogSEI::getInstance()->gravar("Falha na obten��o dos metadados de tr�mites anteriores do processo ($parDblIdProcedimento) durante tr�mite externo.", LogSEI::$AVISO);
      }
    }

      $this->objCacheMetadadosProtocolo[$parDblIdProcedimento] = $objMetadadosProtocolo;
      return $objMetadadosProtocolo;
  }

        /**
         * M�todo respons�vel por obter o tamanho total que ter� a barra de progresso, considerando os diversos componentes digitais
         * a quantidade de partes em que cada um ser� particionado
         *
         * @author Josinaldo J�nior <josinaldo.junior@basis.com.br>
         * @param  $parObjProcesso
         * @return float|int $totalBarraProgresso
         */
  private function obterTamanhoTotalDaBarraDeProgressoREST($parObjProcesso)
    {

      $nrTamanhoMegasMaximo = ProcessoEletronicoRN::obterTamanhoBlocoTransferencia();
      $nrTamanhoBytesMaximo = ($nrTamanhoMegasMaximo * 1024 ** 2); //Qtd de MB definido como parametro

      $totalBarraProgresso = 2;
      $this->contadorDaBarraDeProgresso = 2;
      $arrHashIndexados = [];
    foreach ($parObjProcesso['documentos'] as $objDoc)
      {
        $arrComponentesDigitais = is_array($objDoc['componentesDigitais']) ? $objDoc['componentesDigitais'] : [$objDoc['componentesDigitais']];       
      foreach ($arrComponentesDigitais as $objComponenteDigital) {
        $strHashComponente = ProcessoEletronicoRN::getHashFromMetaDadosREST($objComponenteDigital['hash']);
        if(!in_array($strHashComponente, $arrHashIndexados)) {
            $arrHashIndexados[] = $strHashComponente;
            $nrTamanhoComponente = $objComponenteDigital['tamanhoEmBytes'];
          if($nrTamanhoComponente > $nrTamanhoBytesMaximo) {
            $qtdPartes = ceil($nrTamanhoComponente / $nrTamanhoBytesMaximo);
            $totalBarraProgresso += $qtdPartes;
            continue;
          }
            $totalBarraProgresso++;
        }
      }
    }

      return $totalBarraProgresso;
  }

  public function listarRepositoriosDeEstruturas()
    {
      $dadosArray = [];
      $arrObjRepositorioDTO = $this->objProcessoEletronicoRN->listarRepositoriosDeEstruturas();
    foreach ($arrObjRepositorioDTO as $repositorio) {
        $dadosArray[$repositorio->getNumId()] = $repositorio->getStrNome();
    }

      return $dadosArray;
  }

  public function consultarMotivosUrgencia()
    {
      return $this->objProcessoEletronicoRN->consultarMotivosUrgencia();
  }

  private function construirCabecalho(ExpedirProcedimentoDTO $objExpedirProcedimentoDTO, $strNumeroRegistro, $dblIdProcedimento = null)
    {
    if(!isset($objExpedirProcedimentoDTO)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $objExpedirProcedimentoDTO n�o informado.');
    }

      // Aten��o: Comportamento desativado at� que seja tratado o recebimento de um processo recebendo um novo documento
      // com mesmo arquivo/hash de outro documento j� existente no processo
      $bolObrigarEnvioDeTodosOsComponentesDigitais = !$this->enviarApenasComponentesDigitaisPendentes(
          $objExpedirProcedimentoDTO->getNumIdRepositorioDestino(),
          $objExpedirProcedimentoDTO->getNumIdUnidadeDestino()
      );

      return $this->objProcessoEletronicoRN->construirCabecalho(
          $strNumeroRegistro,
          $objExpedirProcedimentoDTO->getNumIdRepositorioOrigem(),
          $objExpedirProcedimentoDTO->getNumIdUnidadeOrigem(),
          $objExpedirProcedimentoDTO->getNumIdRepositorioDestino(),
          $objExpedirProcedimentoDTO->getNumIdUnidadeDestino(),
          $objExpedirProcedimentoDTO->getBolSinUrgente(),
          $objExpedirProcedimentoDTO->getNumIdMotivoUrgencia(),
          $bolObrigarEnvioDeTodosOsComponentesDigitais,
          $dblIdProcedimento
      );
  }

    /**
     * Verifica se a unidade tem mapeamento de apenas envio de componentes digitais pendentes
     *
     * @param  $numIdRepositorioDestino
     * @param  $numIdUnidadeDestino
     * @return bool
     */
  private function enviarApenasComponentesDigitaisPendentes($numIdRepositorioDestino, $numIdUnidadeDestino)
    {
      $objEnvioParcialDTO = new PenRestricaoEnvioComponentesDigitaisDTO();
      $objEnvioParcialDTO->retNumIdEstrutura();
      $objEnvioParcialDTO->retNumIdUnidadePen();
      $objEnvioParcialDTO->setNumIdEstrutura($numIdRepositorioDestino);

      $objEnvioParcialRN = new PenRestricaoEnvioComponentesDigitaisRN();
      $arrObjEnvioParcialDTO = $objEnvioParcialRN->listar($objEnvioParcialDTO);

    if (!is_null($arrObjEnvioParcialDTO) && count($arrObjEnvioParcialDTO) > 0) {
      if (count($arrObjEnvioParcialDTO) > 1) {
        $arrIdUnidadesParaEnvioPendentes = [];
        foreach ($arrObjEnvioParcialDTO as $value) {
            $arrIdUnidadesParaEnvioPendentes[] = $value->getNumIdUnidadePen();
        }

        return in_array($numIdUnidadeDestino, $arrIdUnidadesParaEnvioPendentes);
      } elseif (!empty($arrObjEnvioParcialDTO[0]->getNumIdUnidadePen())) {
          return $arrObjEnvioParcialDTO[0]->getNumIdUnidadePen() == $numIdUnidadeDestino;
      }

        return true;
    }

      return false;
  }

  public function construirProcessoREST($dblIdProcedimento, $arrIdProcessoApensado = null, $parObjMetadadosTramiteAnterior = null)
    {
    if(!isset($dblIdProcedimento)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $dblIdProcedimento n�o informado.');
    }

  
      $objProcedimentoDTO = $this->consultarProcedimento($dblIdProcedimento);
      $objPenRelHipoteseLegalRN = new PenRelHipoteseLegalEnvioRN();

      $objProcesso = [
      'staTipoProtocolo' => ProcessoEletronicoRN::$STA_TIPO_PROTOCOLO_PROCESSO,
      'protocolo' => mb_convert_encoding($objProcedimentoDTO->getStrProtocoloProcedimentoFormatado(), 'UTF-8', 'ISO-8859-1'),
      'nivelDeSigilo' => $this->obterNivelSigiloPEN($objProcedimentoDTO->getStrStaNivelAcessoLocalProtocolo()),
      'processoDeNegocio' => mb_convert_encoding($this->objProcessoEletronicoRN->reduzirCampoTexto($objProcedimentoDTO->getStrNomeTipoProcedimento(), 100), 'UTF-8', 'ISO-8859-1'),
      'descricao' => mb_convert_encoding($this->objProcessoEletronicoRN->reduzirCampoTexto($objProcedimentoDTO->getStrDescricaoProtocolo(), 100), 'UTF-8', 'ISO-8859-1'),
      'dataHoraDeProducao' => $this->objProcessoEletronicoRN->converterDataWebService($objProcedimentoDTO->getDtaGeracaoProtocolo())
      ];

  
    
      if ($objProcedimentoDTO->getStrStaNivelAcessoLocalProtocolo() == ProtocoloRN::$NA_RESTRITO) {
          $objProcesso['hipoteseLegal'] = [
          'identificacao' => $objPenRelHipoteseLegalRN->getIdHipoteseLegalPEN($objProcedimentoDTO->getNumIdHipoteseLegalProtocolo())
          ];
      }

      $objProcesso = $this->atribuirProdutorProcessoREST($objProcesso, $objProcedimentoDTO->getNumIdUsuarioGeradorProtocolo());
    
      $objProcesso = $this->atribuirDataHoraDeRegistroREST($objProcesso, $objProcedimentoDTO->getDblIdProcedimento());
    
      $objProcesso = $this->atribuirDocumentosREST($objProcesso, $dblIdProcedimento, $parObjMetadadosTramiteAnterior);
    
      $objProcesso = $this->atribuirDadosInteressadosREST($objProcesso, $dblIdProcedimento);
    
      $objProcesso = $this->adicionarProcessosApensadosREST($objProcesso, $arrIdProcessoApensado);
    
      $objProcesso = $this->atribuirDadosHistoricoREST($objProcesso, $dblIdProcedimento);

      $objProcesso['idProcedimentoSEI'] = $dblIdProcedimento;
      return $objProcesso;
  }


    //TODO: Implementar mapeamento de atividades que sero enviadas para barramento (semelhante Protocolo Integrado)
  private function atribuirDadosHistoricoREST($objProcesso, $dblIdProcedimento)
    {
      $objProcedimentoHistoricoDTO = new ProcedimentoHistoricoDTO();
      $objProcedimentoHistoricoDTO->setDblIdProcedimento($dblIdProcedimento);
      $objProcedimentoHistoricoDTO->setStrStaHistorico(ProcedimentoRN::$TH_TOTAL);
      $objProcedimentoHistoricoDTO->setStrSinGerarLinksHistorico('N');

      $objProcedimentoRN = new ProcedimentoRN();
      $objProcedimentoDTO = $objProcedimentoRN->consultarHistoricoRN1025($objProcedimentoHistoricoDTO);
      $arrObjAtividadeDTO = $objProcedimentoDTO->getArrObjAtividadeDTO();

    if($arrObjAtividadeDTO == null || count($arrObjAtividadeDTO) == 0) {
        throw new InfraException("M�dulo do Tramita: N�o foi poss�vel obter andamentos do processo {$objProcesso['protocolo']}");
    }

      $arrObjOperacao = [];
    foreach ($arrObjAtividadeDTO as $objAtividadeDTO) {

        $objOperacao = [
        'dataHoraOperacao' => $this->objProcessoEletronicoRN->converterDataWebService($objAtividadeDTO->getDthAbertura()),
        'unidadeOperacao' => $objAtividadeDTO->getStrDescricaoUnidade() ? mb_convert_encoding($objAtividadeDTO->getStrDescricaoUnidade(), 'UTF-8', 'ISO-8859-1') : "NA",
        'operacao' => $objAtividadeDTO->getStrNomeTarefa() ? $this->objProcessoEletronicoRN->reduzirCampoTexto(strip_tags(mb_convert_encoding($objAtividadeDTO->getStrNomeTarefa(), 'UTF-8', 'ISO-8859-1')), 1000) : "NA",
        'usuario' => $objAtividadeDTO->getStrNomeUsuarioOrigem() ? mb_convert_encoding($objAtividadeDTO->getStrNomeUsuarioOrigem(), 'UTF-8', 'ISO-8859-1') : "NA"
        ];

        $arrObjOperacao[] = $objOperacao;
    }

      usort(
          $arrObjOperacao, function ($obj1, $obj2) {
              $dt1 = new DateTime($obj1['dataHoraOperacao']);
              $dt2 = new DateTime($obj2['dataHoraOperacao']);
              return $dt1 > $dt2;
          }
      );

      $objProcesso['itensHistorico'] = $arrObjOperacao;

      return $objProcesso;
  }

    /**
     * Muda o estado de um procedimento
     *
     * @param  object $objProcesso
     * @param  string $strStaEstado
     * @throws InfraException
     */
  public static function mudarEstadoProcedimento($objProcesso, $strStaEstado)
    {
    if(!isset($objProcesso)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $objProcesso n�o informado.');
    }

    try {

        //muda estado do protocolo
        $objProtocoloDTO = new ProtocoloDTO();
        $objProtocoloDTO->setStrStaEstado($strStaEstado);
        $objProtocoloDTO->setDblIdProtocolo($objProcesso->idProcedimentoSEI);

        $objProtocoloRN = new ProtocoloRN();
        $objProtocoloRN->alterarRN0203($objProtocoloDTO);

        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDblIdProtocolo($objProcesso->idProcedimentoSEI);
        $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
        $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO);

        $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTO->setStrNome('MOTIVO');
        $objAtributoAndamentoDTO->setStrIdOrigem(null);
        $objAtributoAndamentoDTO->setStrValor('Processo est� em processamento devido ao seu tr�mite externo para outra unidade.');
        $objAtividadeDTO->setArrObjAtributoAndamentoDTO([$objAtributoAndamentoDTO]);

        $objAtividadeRN = new AtividadeRN();
        $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);
    }
    catch(Exception $e){
        throw new InfraException('M�dulo do Tramita: Erro ao mudar o estado do processo.', $e);
    }

    if (isset($objProcesso->processoApensado) && is_array($objProcesso->processoApensado)) {
      foreach ($objProcesso->processoApensado as $objProcessoApensado) {
          static::mudarEstadoProcedimento($objProcessoApensado, $strStaEstado);
      }
    }
  }

    /**
     * Muda o estado de um procedimento
     *
     * @param  object $objProcesso
     * @param  string $strStaEstado
     * @throws InfraException
     */
  public static function mudarEstadoProcedimentoNormal($objProcesso, $strStaEstado)
    {
      //Muda o estado do Protocolo para normal
      $objProtocoloDTO = new ProtocoloDTO();
      $objProtocoloDTO->setStrStaEstado($strStaEstado);
      $objProtocoloDTO->setDblIdProtocolo($objProcesso->idProcedimentoSEI);

      $objProtocoloRN = new ProtocoloRN();
      $objProtocoloRN->alterarRN0203($objProtocoloDTO);
  }


  public function bloquearProcedimentoExpedicao($objExpedirProcedimentoDTO, $numIdProcedimento)
    {
      //Instancia a API do SEI para bloquei do processo
      $objEntradaBloquearProcessoAPI = new EntradaBloquearProcessoAPI();
      $objEntradaBloquearProcessoAPI->setIdProcedimento($numIdProcedimento);

      //Realiza o bloquei do processo
      $objSeiRN = new SeiRN();
      $objSeiRN->bloquearProcesso($objEntradaBloquearProcessoAPI);

      $arrObjAtributoAndamentoDTO = [];

      //Seta o repositrio de destino para constar no histrico
      $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
      $objAtributoAndamentoDTO->setStrNome('REPOSITORIO_DESTINO');
      $objAtributoAndamentoDTO->setStrValor($objExpedirProcedimentoDTO->getStrRepositorioDestino());
      $objAtributoAndamentoDTO->setStrIdOrigem($objExpedirProcedimentoDTO->getNumIdRepositorioOrigem());
      $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

      //Compe o atributo que ir compor a estrutura
      $objEstrutura = $this->objProcessoEletronicoRN->consultarEstrutura(
          $objExpedirProcedimentoDTO->getNumIdRepositorioDestino(), $objExpedirProcedimentoDTO->getNumIdUnidadeDestino(), true
      );
        $nome=$objEstrutura->nome;
        $numeroDeIdentificacaoDaEstrutura=$objEstrutura->numeroDeIdentificacaoDaEstrutura;

        $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTO->setStrNome('UNIDADE_DESTINO_HIRARQUIA');
        $objAtributoAndamentoDTO->setStrValor($nome);
        $objAtributoAndamentoDTO->setStrIdOrigem($numeroDeIdentificacaoDaEstrutura);
        $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;


      //Seta a unidade de destino
      $arrUnidadeDestino = preg_split('/\s?\/\s?/', (string) $objExpedirProcedimentoDTO->getStrUnidadeDestino());
      $arrUnidadeDestino = preg_split('/\s+\-\s+/', current($arrUnidadeDestino));
      $strUnidadeDestino = array_shift($arrUnidadeDestino);

      $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
      $objAtributoAndamentoDTO->setStrNome('UNIDADE_DESTINO');
      $objAtributoAndamentoDTO->setStrValor($strUnidadeDestino);
      $objAtributoAndamentoDTO->setStrIdOrigem($objExpedirProcedimentoDTO->getNumIdUnidadeDestino());
      $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

      $objAtividadeDTO = new AtividadeDTO();
      $objAtividadeDTO->setDblIdProtocolo($numIdProcedimento);
      $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
      $objAtividadeDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
      $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO));
      $objAtividadeDTO->setArrObjAtributoAndamentoDTO($arrObjAtributoAndamentoDTO);

      //Registra o andamento no histrico e
      $objAtividadeRN = new AtividadeRN();
      $atividade = $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);

      return $atividade->getNumIdAtividade();
  }

  public function desbloquearProcessoExpedicao($numIdProcedimento)
    {
      ProcessoEletronicoRN::desbloquearProcesso($numIdProcedimento);
  }

  public function registrarAndamentoExpedicaoAbortada($dblIdProtocolo)
    {
      //Seta todos os atributos do hist�rico de aborto da expedio
      $objAtividadeDTO = new AtividadeDTO();
      $objAtividadeDTO->setDblIdProtocolo($dblIdProtocolo);
      $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
      $objAtividadeDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
      $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_ABORTADO));
      $objAtividadeDTO->setArrObjAtributoAndamentoDTO([]);

      //Gera o andamento de expedio abortada
      $objAtividadeRN = new AtividadeRN();
      $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);
  }

  public static function receberRecusaProcedimento($motivo, $unidade_destino, $idProtocolo, $numUnidadeDestino = null)
    {
    try{
        //Muda o status do protocolo para "Normal"
        $arrObjAtributoAndamentoDTO = [];

        $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTO->setStrNome('MOTIVO');
        $objAtributoAndamentoDTO->setStrValor($motivo);
        $objAtributoAndamentoDTO->setStrIdOrigem($numUnidadeDestino);
        $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

        $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTO->setStrNome('UNIDADE_DESTINO');
        $objAtributoAndamentoDTO->setStrValor($unidade_destino);
        $objAtributoAndamentoDTO->setStrIdOrigem($numUnidadeDestino);
        $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDblIdProtocolo($idProtocolo);
        $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
        $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_RECUSADO);
        $objAtividadeDTO->setArrObjAtributoAndamentoDTO($arrObjAtributoAndamentoDTO);

        $objAtividadeRN = new AtividadeRN();
        $atividade = $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);

        $objProtocoloDTO = new ProtocoloDTO();
        $objProtocoloDTO->setStrStaEstado(ProtocoloRN::$TE_NORMAL);
        $objProtocoloDTO->setDblIdProtocolo($idProtocolo);

        $objProtocoloRN = new ProtocoloRN();
        $objProtocoloRN->alterarRN0203($objProtocoloDTO);


    }catch (InfraException $e){
        throw new InfraException($e->getStrDescricao());
    }
    catch(Exception $e){
        throw new InfraException($e->getMessage());
    }
  }

  private function atribuirDataHoraDeRegistroREST($objContexto, $dblIdProcedimento, $dblIdDocumento = null)
    {
      //Validar par�metro $objContexto
    if(!isset($objContexto)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $objContexto n�o informado.');
    }

      //Validar par�metro $dbIdProcedimento
    if(!isset($dblIdProcedimento)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $dbIdProcedimento n�o informado.');
    }

      $objProcedimentoHistoricoDTO = new ProcedimentoHistoricoDTO();
      $objProcedimentoHistoricoDTO->setDblIdProcedimento($dblIdProcedimento);
      $objProcedimentoHistoricoDTO->setStrStaHistorico(ProcedimentoRN::$TH_TOTAL);
      $objProcedimentoHistoricoDTO->adicionarCriterio(['IdTarefa', 'IdTarefa'], [InfraDTO::$OPER_IGUAL, InfraDTO::$OPER_IGUAL], [TarefaRN::$TI_GERACAO_PROCEDIMENTO, ProcessoeletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO], InfraDTO::$OPER_LOGICO_OR);
      $objProcedimentoHistoricoDTO->setStrSinGerarLinksHistorico('N');
      $objProcedimentoHistoricoDTO->setNumMaxRegistrosRetorno(1);
      $objProcedimentoHistoricoDTO->setOrdNumIdTarefa(InfraDTO::$TIPO_ORDENACAO_ASC);

    if(isset($dblIdDocumento)) {
        $objProcedimentoHistoricoDTO->setDblIdDocumento($dblIdDocumento);
        $objProcedimentoHistoricoDTO->setNumIdTarefa([TarefaRN::$TI_GERACAO_DOCUMENTO, TarefaRN::$TI_RECEBIMENTO_DOCUMENTO, TarefaRN::$TI_DOCUMENTO_MOVIDO_DO_PROCESSO], InfraDTO::$OPER_IN);
    }

      $objProcedimentoDTOHistorico = $this->objProcedimentoRN->consultarHistoricoRN1025($objProcedimentoHistoricoDTO);
      $arrObjAtividadeDTOHistorico = $objProcedimentoDTOHistorico->getArrObjAtividadeDTO();

    if(isset($arrObjAtividadeDTOHistorico) && count($arrObjAtividadeDTOHistorico) == 1) {
        $objContexto['dataHoraDeRegistro'] = $this->objProcessoEletronicoRN->converterDataWebService($arrObjAtividadeDTOHistorico[0]->getDthAbertura());
    }

      return $objContexto;
  }
    
  private function atribuirProdutorProcessoREST($objProcesso, $dblIdProcedimento)
    {
    if(!isset($objProcesso)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $objProcesso n�o informado.');
    }

      $objUsuarioProdutor = $this->consultarUsuario($dblIdProcedimento);
    if (isset($objUsuarioProdutor)) {
        // Dados do produtor do processo
        $objProcesso['produtor'] = [
          'nome' => mb_convert_encoding($this->objProcessoEletronicoRN->reduzirCampoTexto($objUsuarioProdutor->getStrNome(), 150), 'UTF-8', 'ISO-8859-1'),
          'tipo' => self::STA_TIPO_PESSOA_FISICA
        ];

        if ($objUsuarioProdutor->getDblCpfContato()) {
            $objProcesso['produtor']['numeroDeIdentificacao'] = $objUsuarioProdutor->getDblCpfContato();
        }
        // TODO: Informar dados da estrutura organizacional (estruturaOrganizacional)
    }
      
      $objUnidadeGeradora = $this->consultarUnidade($dblIdProcedimento);
    if (isset($objUnidadeGeradora)) {
        $objProcesso['produtor']['unidade'] = [
          'nome' => mb_convert_encoding($this->objProcessoEletronicoRN->reduzirCampoTexto($objUnidadeGeradora->getStrDescricao(), 150), 'UTF-8', 'ISO-8859-1'),
          'tipo' => self::STA_TIPO_PESSOA_ORGAOPUBLICO
        ];
        // TODO: Informar dados da estrutura organizacional (estruturaOrganizacional)
    }

      return $objProcesso;
  }


  private function atribuirDadosInteressadosREST($objProcesso, $dblIdProcedimento)
    {
    if (!isset($objProcesso)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $objProcesso n�o informado.');
    }
    
      $arrParticipantesDTO = $this->listarInteressados($dblIdProcedimento);
    
    if (isset($arrParticipantesDTO) && count($arrParticipantesDTO) > 0) {
        $objProcesso['interessados'] = [];
    
      foreach ($arrParticipantesDTO as $participanteDTO) {
          $interessado = [
          'nome' => mb_convert_encoding($this->objProcessoEletronicoRN->reduzirCampoTexto($participanteDTO->getStrNomeContato(), 150), 'UTF-8', 'ISO-8859-1')
          ];
          $objProcesso['interessados'][] = $interessado;
      }
    } 
      
      return $objProcesso;
  }

  private function atribuirDocumentosREST($objProcesso, $dblIdProcedimento, $parObjMetadadosTramiteAnterior)
    {

       
    if(!isset($objProcesso)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $objProcesso n�o informado.');
    }

      $arrDocumentosRelacionados = $this->listarDocumentosRelacionados($dblIdProcedimento);

    if(!isset($arrDocumentosRelacionados)) {
        throw new InfraException('M�dulo do Tramita: Documentos n�o encontrados.');
    }

      $arrObjCompIndexadoPorIdDocumentoDTO = [];
      $objProcessoEletronicoPesquisaDTO = new ProcessoEletronicoDTO();
      $objProcessoEletronicoPesquisaDTO->setDblIdProcedimento($dblIdProcedimento);
      $objUltimoTramiteRecebidoDTO = $this->objProcessoEletronicoRN->consultarUltimoTramiteRecebido($objProcessoEletronicoPesquisaDTO);
    if(!is_null($objUltimoTramiteRecebidoDTO)) {
      if ($this->objProcessoEletronicoRN->possuiComponentesComDocumentoReferenciado($objUltimoTramiteRecebidoDTO)) {
          $arrObjComponentesDigitaisDTO = $this->objProcessoEletronicoRN->listarComponentesDigitais($objUltimoTramiteRecebidoDTO);
          $arrObjCompIndexadoPorIdDocumentoDTO = InfraArray::indexarArrInfraDTO($arrObjComponentesDigitaisDTO, 'IdDocumento');
      }
    }
      
      $objProcesso['documentos'] = [];
    foreach ($arrDocumentosRelacionados as $ordem => $objDocumentosRelacionados) {
        $documentoDTO = $objDocumentosRelacionados["Documento"];
        $staAssociacao = $objDocumentosRelacionados["StaAssociacao"];

        $objPenRelHipoteseLegalRN = new PenRelHipoteseLegalEnvioRN();

        //Considera o n�mero/nome do documento externo para descri��o do documento
        $boolDocumentoRecebidoComNumero = $documentoDTO->getStrStaProtocoloProtocolo() == ProtocoloRN::$TP_DOCUMENTO_RECEBIDO && $documentoDTO->getStrNumero() != null;
        $strDescricaoDocumento = ($boolDocumentoRecebidoComNumero) ? $documentoDTO->getStrNumero() : "***";

        $documento = []; // Inicializando $documento como um array
        $documento['ordem'] = $ordem + 1;
        $documento['descricao'] = mb_convert_encoding($this->objProcessoEletronicoRN->reduzirCampoTexto($strDescricaoDocumento, 100), 'UTF-8', 'ISO-8859-1');
        
        
        $documento['retirado'] = ($documentoDTO->getStrStaEstadoProtocolo() == ProtocoloRN::$TE_DOCUMENTO_CANCELADO) ? true : false;
        $documento['nivelDeSigilo'] = $this->obterNivelSigiloPEN($documentoDTO->getStrStaNivelAcessoLocalProtocolo());
        

        //Verifica se o documento faz parte de outro processo devido � sua anexa��o ou � sua movimenta��o
      if($staAssociacao != RelProtocoloProtocoloRN::$TA_DOCUMENTO_MOVIDO) {
        if ($documentoDTO->getStrProtocoloProcedimentoFormatado() != $objProcesso['protocolo']) {
          // Caso o documento n�o tenha sido movido, seu protocolo � diferente devido � sua anexa��o � outro processo
          $documento['protocoloDoProcessoAnexado'] = $documentoDTO->getStrProtocoloProcedimentoFormatado();
          $documento['idProcedimentoAnexadoSEI'] = $documentoDTO->getDblIdProcedimento();
            
        }
      } else {
          // Em caso de documento movido, ele ser� tratado como cancelado para tr�mites externos
          $documento['retirado'] = true;
      }
      if($documentoDTO->getStrStaNivelAcessoLocalProtocolo() == ProtocoloRN::$NA_RESTRITO) {
          $documento['hipoteseLegal'] = []; // Inicializando a chave 'hipoteseLegal' como um array
          $documento['hipoteseLegal']['identificacao'] = $objPenRelHipoteseLegalRN->getIdHipoteseLegalPEN($documentoDTO->getNumIdHipoteseLegalProtocolo());          
          //TODO: Adicionar nome da hip�tese legal atribuida ao documento
      }
        $documento['dataHoraDeProducao'] = $this->objProcessoEletronicoRN->converterDataWebService($documentoDTO->getDtaGeracaoProtocolo());
        $documento['dataHoraDeRegistro'] = $this->objProcessoEletronicoRN->converterDataWebService($documentoDTO->getDtaGeracaoProtocolo());
        $documento['produtor'] = []; // Inicializando a chave 'produtor' como um array        
        $usuarioDTO = $this->consultarUsuario($documentoDTO->getNumIdUsuarioGeradorProtocolo());
      if(isset($usuarioDTO)) {
          $documento['produtor']['nome'] = mb_convert_encoding($this->objProcessoEletronicoRN->reduzirCampoTexto($usuarioDTO->getStrNome(), 150), 'UTF-8', 'ISO-8859-1');
          $documento['produtor']['numeroDeIdentificacao'] = $usuarioDTO->getDblCpfContato();
          // TODO: Obter tipo de pessoa f�sica dos contextos/contatos do SEI
          $documento['produtor']['tipo'] = self::STA_TIPO_PESSOA_FISICA;
          
      }
        $unidadeDTO = $this->consultarUnidade($documentoDTO->getNumIdUnidadeResponsavel());
      if(isset($unidadeDTO)) {
          $documento['produtor']['unidade'] = []; // Inicializando a chave 'unidade' como um array
          $documento['produtor']['unidade']['nome'] = mb_convert_encoding($this->objProcessoEletronicoRN->reduzirCampoTexto($unidadeDTO->getStrDescricao(), 150), 'UTF-8', 'ISO-8859-1');
          $documento['produtor']['unidade']['tipo'] = self::STA_TIPO_PESSOA_ORGAOPUBLICO;

          //TODO: Informar dados da estrutura organizacional (estruturaOrganizacional)
      }
      if(array_key_exists($documentoDTO->getDblIdDocumento(), $arrObjCompIndexadoPorIdDocumentoDTO)) {
          $objComponenteDigitalDTO = $arrObjCompIndexadoPorIdDocumentoDTO[$documentoDTO->getDblIdDocumento()];
        if(!empty($objComponenteDigitalDTO->getNumOrdemDocumentoReferenciado())) {
            $documento['ordemDoDocumentoReferenciado'] = $objComponenteDigitalDTO->getNumOrdemDocumentoReferenciado();
        }
      }
        $documento['produtor']['numeroDeIdentificacao'] = $documentoDTO->getStrProtocoloDocumentoFormatado();
        $this->atribuirDataHoraDeRegistroREST($documento, $documentoDTO->getDblIdProcedimento(), $documentoDTO->getDblIdDocumento());
        $documento = $this->atribuirEspecieDocumentalREST($documento, $documentoDTO, $parObjMetadadosTramiteAnterior);
        $documento = $this->atribuirNumeracaoDocumentoREST($documento, $documentoDTO);
        
      if($documento['retirado'] === true) {
          $objComponenteDigitalDTO = new ComponenteDigitalDTO();
          $objComponenteDigitalDTO->retTodos();
          $objComponenteDigitalDTO->setDblIdDocumento($documentoDTO->getDblIdDocumento());
          $objComponenteDigitalBD = new ComponenteDigitalBD($this->getObjInfraIBanco());
       
        if($objComponenteDigitalBD->contar($objComponenteDigitalDTO) > 0) {
            $arrobjComponenteDigitalDTO = $objComponenteDigitalBD->listar($objComponenteDigitalDTO);
            $componenteDigital = $arrobjComponenteDigitalDTO[0];

            $objComponenteDigital = array();
            $objComponenteDigital['ordem'] = 1;
            $objComponenteDigital['nome'] = mb_convert_encoding($componenteDigital->getStrNome(), 'UTF-8', 'ISO-8859-1');
            $objComponenteDigital['hash'] = [
            'algoritmo' => $componenteDigital->getStrAlgoritmoHash(),
            'conteudo' => $componenteDigital->getStrHashConteudo()
            ];

            $objComponenteDigital['tamanhoEmBytes'] = $componenteDigital->getNumTamanho();
            $objComponenteDigital['mimeType'] = $componenteDigital->getStrMimeType();
            $objComponenteDigital['tipoDeConteudo'] = $componenteDigital->getStrTipoConteudo();
            $objComponenteDigital['idAnexo'] = $componenteDigital->getNumIdAnexo();

            if($componenteDigital->getStrMimeType() == 'outro') {
                $objComponenteDigital['dadosComplementaresDoTipoDeArquivo'] = 'outro';
            }

            $objComponenteDigital = $this->atribuirDadosAssinaturaDigitalREST($documentoDTO, $objComponenteDigital, $componenteDigital->getStrHashConteudo());
            $documento['componentesDigitais'][] = $objComponenteDigital;

        }else{
            $documento = $this->atribuirComponentesDigitaisREST($documento, $documentoDTO, $dblIdProcedimento);
        }
      }else{
          $documento = $this->atribuirComponentesDigitaisREST($documento, $documentoDTO, $dblIdProcedimento);
      }
        // TODO: Necess�rio tratar informa��es abaixo
        //- protocoloDoDocumentoAnexado
        //- protocoloDoProcessoAnexado
        //- protocoloAnterior
        //- historico
        $documento['idDocumentoSEI'] = $documentoDTO->getDblIdDocumento();
        $objProcesso['documentos'][] = $documento;
    }
      return $objProcesso;
  }


  public function atribuirComponentesDigitaisRetirados($documentoDTO)
    {

  }

    /**
     * Obt�m a esp�cie documental relacionada ao documento do processo.
     * A esp�cie documental, por padr�o, � obtida do mapeamento de esp�cies realizado pelo administrador
     * nas configura��es do m�dulo.
     * Caso o documento tenha sido produzido por outro �rg�o externamente, a esp�cie a ser considerada ser�
     * aquela definida originalmente pelo seu produtor
     *
     * @param  int $parDblIdProcedimento Identificador do processo
     * @param  int $parDblIdDocumento    Identificador do documento
     * @return int C�digo da esp�cie documental
     */
  private function atribuirEspecieDocumentalREST($parMetaDocumento, $parDocumentoDTO, $parObjMetadadosTramiteAnterior)
    {
      //Valida��o dos par�metros da fun��o
    if(!isset($parDocumentoDTO)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $parDocumentoDTO n�o informado.');
    }

    if(!isset($parMetaDocumento)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $parMetaDocumento n�o informado.');
    }
      $numCodigoEspecie = null;
      $strNomeEspecieProdutor = null;
      $dblIdProcedimento = $parDocumentoDTO->getDblIdProcedimento();
      $dblIdDocumento = $parDocumentoDTO->getDblIdDocumento();

      //Inicialmente, busca esp�cie documental atribuida pelo produtor em tr�mite realizado anteriormente
      $objComponenteDigitalDTO = new ComponenteDigitalDTO();
      $objComponenteDigitalDTO->retNumCodigoEspecie();
      $objComponenteDigitalDTO->retStrNomeEspecieProdutor();

      // Verifica se o documento � de um processo anexado ou n�o e busca no
      // campo correto
    if(isset($parMetaDocumento['idProcedimentoAnexadoSEI'])) {
        $objComponenteDigitalDTO->setDblIdProcedimentoAnexado($dblIdProcedimento);
    }
    else{
        $objComponenteDigitalDTO->setDblIdProcedimento($dblIdProcedimento);
    }
      $objComponenteDigitalDTO->setDblIdDocumento($dblIdDocumento);
      $objComponenteDigitalDTO->setNumMaxRegistrosRetorno(1);
      $objComponenteDigitalDTO->setOrd('IdTramite', InfraDTO::$TIPO_ORDENACAO_DESC);

      $objComponenteDigitalBD = new ComponenteDigitalBD(BancoSEI::getInstance());
      $objComponenteDigitalDTO = $objComponenteDigitalBD->consultar($objComponenteDigitalDTO);

    if($objComponenteDigitalDTO != null) {
        $numCodigoEspecie = $objComponenteDigitalDTO->getNumCodigoEspecie();
        $strNomeEspecieProdutor = mb_convert_encoding($objComponenteDigitalDTO->getStrNomeEspecieProdutor(), 'UTF-8', 'ISO-8859-1');
    }
      //Caso a informa��o sobre mapeamento esteja nulo, necess�rio buscar tal informa��o no Barramento
      //A lista de documentos recuperada do tr�mite anterior ser� indexada pela sua ordem no protocolo e
      //a esp�cie documental e o nome do produtor ser�o obtidos para atribui��o ao documento
    if($objComponenteDigitalDTO != null && $numCodigoEspecie == null) {
      if(isset($parObjMetadadosTramiteAnterior)) {
          $arrObjMetaDocumentosTramiteAnterior = [];

          //Obten��o de lista de documentos do processo
          $objProcesso = $parObjMetadadosTramiteAnterior->processo;
          $objDocumento = $parObjMetadadosTramiteAnterior->documento;
          $objProtocolo = $objProcesso ?? $objDocumento;

          $arrObjMetaDocumentosTramiteAnterior = ProcessoEletronicoRN::obterDocumentosProtocolo($objProtocolo);
        if(isset($arrObjMetaDocumentosTramiteAnterior) && !is_array($arrObjMetaDocumentosTramiteAnterior)) {
          $arrObjMetaDocumentosTramiteAnterior = [$arrObjMetaDocumentosTramiteAnterior];
        }

          //Indexa��o dos documentos pela sua ordem
          $arrMetaDocumentosAnteriorIndexado = [];
        foreach ($arrObjMetaDocumentosTramiteAnterior as $objMetaDoc) {
            $arrMetaDocumentosAnteriorIndexado[$objMetaDoc->ordem] = $objMetaDoc;
        }

          //Atribui esp�cie documental definida pelo produtor do documento e registrado no PEN, caso exista
        if(count($arrMetaDocumentosAnteriorIndexado) > 0 && array_key_exists($parMetaDocumento['ordem'], $arrMetaDocumentosAnteriorIndexado)) {
          if (is_array($arrMetaDocumentosAnteriorIndexado[$parMetaDocumento['ordem']]->especie)) {
              $arrMetaDocumentosAnteriorIndexado[$parMetaDocumento['ordem']]->especie  = (object) $arrMetaDocumentosAnteriorIndexado[$parMetaDocumento['ordem']]->especie;
          }
            $numCodigoEspecie = $arrMetaDocumentosAnteriorIndexado[$parMetaDocumento['ordem']]->especie->codigo;
            $strNomeEspecieProdutor = mb_convert_encoding($arrMetaDocumentosAnteriorIndexado[$parMetaDocumento['ordem']]->especie->nomeNoProdutor, 'UTF-8', 'ISO-8859-1');
        }
      }
    }
      //Aplica o mapeamento de esp�cies definida pelo administrador para os novos documentos
    if($numCodigoEspecie == null || (isset($numCodigoEspecie) && $numCodigoEspecie == 0)) {
        $numCodigoEspecie = $this->obterEspecieMapeada($parDocumentoDTO->getNumIdSerie());
        $strNomeEspecieProdutor = mb_convert_encoding($parDocumentoDTO->getStrNomeSerie(), 'UTF-8', 'ISO-8859-1');
    }

      $parMetaDocumento['especie'] = ['codigo' => $numCodigoEspecie, 'nomeNoProdutor' => $strNomeEspecieProdutor];
  

      return $parMetaDocumento;
  }

  private function obterEspecieMapeada($parNumIdSerie)
    {
    if(!isset($parNumIdSerie) || $parNumIdSerie == 0) {
        throw new InfraException('M�dulo do Tramita: Par�metro $parNumIdSerie n�o informado.');
    }

      $objPenRelTipoDocMapEnviadoDTO = new PenRelTipoDocMapEnviadoDTO();
      $objPenRelTipoDocMapEnviadoDTO->setNumIdSerie($parNumIdSerie);
      $objPenRelTipoDocMapEnviadoDTO->retNumCodigoEspecie();

      $objGenericoBD = new GenericoBD($this->getObjInfraIBanco());
      $objPenRelTipoDocMapEnviadoDTO = $objGenericoBD->consultar($objPenRelTipoDocMapEnviadoDTO);
        
      //Mapeamento achado
      $numCodigoEspecieMapeada = isset($objPenRelTipoDocMapEnviadoDTO) ? $objPenRelTipoDocMapEnviadoDTO->getNumCodigoEspecie() : null;
      $numCodigoEspecieMapeada = $numCodigoEspecieMapeada ?: $this->objPenRelTipoDocMapEnviadoRN->consultarEspeciePadrao();
      //O padr�o de recebimento est� nulo e n�o achou mapeamento
    if($numCodigoEspecieMapeada == null) {
        $objPenRelTipoDocMapEnviadoDTO = new PenRelTipoDocMapEnviadoDTO();
        $objPenRelTipoDocMapEnviadoDTO->retNumCodigoEspecie();
        $objPenRelTipoDocMapEnviadoDTO->setNumMaxRegistrosRetorno(1);
        $objPenRelTipoDocMapEnviadoDTO = $objGenericoBD->consultar($objPenRelTipoDocMapEnviadoDTO);
        $numCodigoEspecieMapeada = isset($objPenRelTipoDocMapEnviadoDTO) ? $objPenRelTipoDocMapEnviadoDTO->getNumCodigoEspecie() : null;
    }
      
    if(!isset($numCodigoEspecieMapeada)) {
        throw new InfraException("M�dulo do Tramita: N�o foi encontrado nenhum mapeamento de tipo documental. C�digo de identifica��o da esp�cie documental n�o pode ser localizada para o tipo de documento {$parNumIdSerie}.");
    }

      return $numCodigoEspecieMapeada;
  }

  private function atribuirComponentesDigitaisREST($objDocumento, DocumentoDTO $objDocumentoDTO, $dblIdProcedimento = null)
    {
    if(!isset($objDocumento)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $objDocumento n�o informado.');
    }

    if(!isset($objDocumentoDTO)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $objDocumentoDTO n�o informado.');
    }
      $arrObjDocumentoDTOAssociacao = $this->listarDocumentosRelacionados($dblIdProcedimento, $objDocumentoDTO->getDblIdDocumento());
      $strStaAssociacao = count($arrObjDocumentoDTOAssociacao) == 1 ? $arrObjDocumentoDTOAssociacao[0]['StaAssociacao'] : null;
      $arrObjDadosArquivos = $this->listarDadosArquivos($objDocumentoDTO, $strStaAssociacao);
      $objDocumento['componentesDigitais'] = [];
    foreach ($arrObjDadosArquivos as $numOrdemComponente => $objDadosArquivos) {

      if(!isset($objDadosArquivos) || count($objDadosArquivos) == 0) {
          throw new InfraException('M�dulo do Tramita: Erro durante obten��o de informa��es sobre o componente digital do documento {$objDocumentoDTO->getStrProtocoloDocumentoFormatado()}.');
      }

        $strAlgoritmoHash = self::ALGORITMO_HASH_DOCUMENTO;
        $hashDoComponenteDigital = $objDadosArquivos['HASH_CONTEUDO'];
        $strAlgoritmoHash = $objDadosArquivos['ALGORITMO_HASH_CONTEUDO'];

        //TODO: Revisar tal implementa��o para atender a gerao de hash de arquivos grandes
        $objComponenteDigital = [];
        $objComponenteDigital['ordem'] = $numOrdemComponente;
        $objComponenteDigital['nome'] = mb_convert_encoding($objDadosArquivos["NOME"], 'UTF-8', 'ISO-8859-1');
        $objComponenteDigital['hash'] = [
        'algoritmo' => $strAlgoritmoHash,
        'conteudo' => $hashDoComponenteDigital
        ];
        $objComponenteDigital['tamanhoEmBytes'] = $objDadosArquivos['TAMANHO'];
        //TODO: Validar os tipos de mimetype de acordo com o WSDL do SEI
        //Caso no identifique o tipo correto, informar o valor [outro]
        $objComponenteDigital['mimeType'] = $objDadosArquivos['MIME_TYPE'];
        $objComponenteDigital['tipoDeConteudo'] = $this->obterTipoDeConteudo($objDadosArquivos['MIME_TYPE']);
        $objComponenteDigital = $this->atribuirDadosAssinaturaDigitalREST($objDocumentoDTO, $objComponenteDigital, $hashDoComponenteDigital);
        if($objDadosArquivos['MIME_TYPE'] == 'outro') {
            $objComponenteDigital['dadosComplementaresDoTipoDeArquivo'] = $objDadosArquivos['dadosComplementaresDoTipoDeArquivo'];
        }

        //TODO: Preencher dados complementares do tipo de arquivo
        //$objComponenteDigital->dadosComplementaresDoTipoDeArquivo = '';

        //TODO: Carregar informa��es da assinatura digital
        //$this->atribuirAssinaturaEletronica($objComponenteDigital, $objDocumentoDTO);

        if (isset($objDadosArquivos['ID_ANEXO']) && !empty($objDadosArquivos['ID_ANEXO'])) {
            $objComponenteDigital['idAnexo'] = $objDadosArquivos['ID_ANEXO'];
        }

        $objDocumento['componentesDigitais'][] = $objComponenteDigital;
    }
      return $objDocumento;
  }


    /**
     * Atribui a informa��o textual das tarjas de assinatura em metadados para envio, removendo os conte�dos de script e html
     *
     * @param  DocumentoDTO $objDocumentoDTO
     * @param  stdClass     $objDocumento
     * @param  string       $strHashDocumento
     * @return void
     */
  public function atribuirDadosAssinaturaDigitalREST($objDocumentoDTO, $objComponenteDigital, $strHashDocumento)
    {
      $objDocumentoDTOTarjas = new DocumentoDTO();
      $objDocumentoDTOTarjas->retDblIdDocumento();
      $objDocumentoDTOTarjas->retStrNomeSerie();
      $objDocumentoDTOTarjas->retStrProtocoloDocumentoFormatado();
      $objDocumentoDTOTarjas->retStrProtocoloProcedimentoFormatado();
      $objDocumentoDTOTarjas->retStrCrcAssinatura();
      $objDocumentoDTOTarjas->retStrQrCodeAssinatura();
      $objDocumentoDTOTarjas->retObjPublicacaoDTO();
      $objDocumentoDTOTarjas->retNumIdConjuntoEstilos();
      $objDocumentoDTOTarjas->retStrSinBloqueado();
      $objDocumentoDTOTarjas->retStrStaDocumento();
      $objDocumentoDTOTarjas->retStrStaProtocoloProtocolo();
      $objDocumentoDTOTarjas->retNumIdUnidadeGeradoraProtocolo();
      $objDocumentoDTOTarjas->retStrDescricaoTipoConferencia();
      $objDocumentoDTOTarjas->setDblIdDocumento($objDocumentoDTO->getDblIdDocumento());

      $objDocumentoRN = new DocumentoRN();
      $objDocumentoDTOTarjas = $objDocumentoRN->consultarRN0005($objDocumentoDTOTarjas);

      $dataTarjas = [];
      $arrObjTarjas = $this->listarTarjasHTML($objDocumentoDTOTarjas);
    foreach ($arrObjTarjas as $strConteudoTarja) {
        $strConteudoTarja = trim(strip_tags($strConteudoTarja));
      if (!empty($strConteudoTarja)) {
        $dataTarjas[] = html_entity_decode($strConteudoTarja);
      }
    }

      $objAssinaturaDTO = new AssinaturaDTO();
      $objAssinaturaDTO->setDblIdDocumento($objDocumentoDTO->getDblIdDocumento());
      $objAssinaturaDTO->retNumIdAtividade();
      $objAssinaturaDTO->retStrStaFormaAutenticacao();
      $objAssinaturaDTO->retStrP7sBase64();
      $resAssinatura = $this->objAssinaturaRN->listarRN1323($objAssinaturaDTO);

    foreach ($resAssinatura as $keyOrder => $assinatura) {
        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setNumIdAtividade($assinatura->getNumIdAtividade()); //7
        $objAtividadeDTO->setNumIdTarefa([TarefaRN::$TI_ASSINATURA_DOCUMENTO, TarefaRN::$TI_AUTENTICACAO_DOCUMENTO], InfraDTO::$OPER_IN); // 5, 115
        $objAtividadeDTO->retDthAbertura();
        $objAtividadeDTO->retNumIdAtividade();
        $objAtividadeRN = new AtividadeRN();
        $objAtividade = $objAtividadeRN->consultarRN0033($objAtividadeDTO);

        $objAssinaturaDigital = [];
        $objAssinaturaDigital['razao'] = mb_convert_encoding($dataTarjas[$keyOrder], 'UTF-8', 'ISO-8859-1');
        $objAssinaturaDigital['observacao'] = mb_convert_encoding($dataTarjas[count($dataTarjas) - 1], 'UTF-8', 'ISO-8859-1');
        $objAssinaturaDigital['dataHora'] = $this->objProcessoEletronicoRN->converterDataWebService($objAtividade->getDthAbertura());      

      if($assinatura->getStrStaFormaAutenticacao() == AssinaturaRN::$TA_CERTIFICADO_DIGITAL) {
          $objAssinaturaDigital['hash'] = [
          'algoritmo' => self::ALGORITMO_HASH_ASSINATURA,
          'conteudo' => $strHashDocumento
          ];
          $objAssinaturaDigital['cadeiaDoCertificado'] = [
          'formato' => 'PKCS7',
          'conteudo' => $assinatura->getStrP7sBase64() ?: 'vazio'
          ];
      } else {
          $objAssinaturaDigital['hash'] = [
          'algoritmo' => self::ALGORITMO_HASH_ASSINATURA,
          'conteudo' => 'vazio'
          ];
        
          $objAssinaturaDigital['cadeiaDoCertificado'] = [
          'formato' => 'PKCS7',
          'conteudo' => 'vazio'
          ];
      }  
    }

      
    if ($objAssinaturaDigital != null) {
        $objComponenteDigital['assinaturasDigitais'][] = $objAssinaturaDigital;
    }

      return $objComponenteDigital;
  }


  private function consultarComponenteDigital($parDblIdDocumento)
    {
      $objComponenteDigitalDTO = new ComponenteDigitalDTO();
      $objComponenteDigitalDTO->setDblIdDocumento($parDblIdDocumento);
      $objComponenteDigitalDTO->setNumMaxRegistrosRetorno(1);
      $objComponenteDigitalDTO->setOrd('IdTramite', InfraDTO::$TIPO_ORDENACAO_DESC);
      $objComponenteDigitalDTO->retTodos();

      $objComponenteDigitalBD = new ComponenteDigitalBD($this->getObjInfraIBanco());
      $arrObjComponenteDigitalDTO = $objComponenteDigitalBD->listar($objComponenteDigitalDTO);
      return (count($arrObjComponenteDigitalDTO) > 0) ? $arrObjComponenteDigitalDTO[0] : null;
  }

    // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
  private function obterDadosArquivo(DocumentoDTO $objDocumentoDTO, $paramStrStaAssociacao, $bolMultiplosComponentes = false, $numOrdemComponenteDigital = 1)
    {
    if(!isset($objDocumentoDTO)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $objDocumentoDTO n�o informado.');
    }

      $objInformacaoArquivo = [];
      $objInformacaoArquivo['ALGORITMO_HASH_CONTEUDO'] = self::ALGORITMO_HASH_DOCUMENTO;
      $strProtocoloDocumentoFormatado = $objDocumentoDTO->getStrProtocoloDocumentoFormatado();

    if($objDocumentoDTO->getStrStaDocumento() == DocumentoRN::$TD_EDITOR_INTERNO) {
        $strConteudoAssinatura = null;
        $objComponenteDigital = $this->consultarComponenteDigital($objDocumentoDTO->getDblIdDocumento());
        $hashDoComponenteDigitalAnterior = (isset($objComponenteDigital)) ? $objComponenteDigital->getStrHashConteudo() : null;

        // Inicialmente, busca o conte�do original que foi enviado anteriormente pelo Tramita.gov.br, evitando a gera��o
        // din�mica de uma nova vers�o do documento, o que pode acarretar falhas de hash
        $strConteudoFS = $this->recuperarConteudoComponenteImutavel($objDocumentoDTO);
      if(!empty($strConteudoFS)) {
          $hashDoComponenteDigital = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $strConteudoFS, true));
        if(isset($hashDoComponenteDigitalAnterior) && $hashDoComponenteDigital == $hashDoComponenteDigitalAnterior) {
          $strConteudoAssinatura = $strConteudoFS;
        }
      }

      if(empty($strConteudoAssinatura)) {
          $strConteudoAssinatura = $this->obterConteudoInternoAssinatura($objDocumentoDTO);
          $hashDoComponenteDigital = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $strConteudoAssinatura, true));

          //Busca registro de tramita��es anteriores para este componente digital para identificar se o Barramento do PEN j� havia registrado o hash do documento gerado da
          //forma antiga, ou seja, considerando o link do N�mero SEI. Este link foi removido para manter o padr�o de conte�do de documentos utilizado pelo SEI para assinatura
          //Para n�o bloquear os documentos gerados anteriormente, aqueles j� registrados pelo Barramento com o hash antigo dever�o manter a gera��o de conte�do anteriormente utilizada.
        if(isset($hashDoComponenteDigitalAnterior) && ($hashDoComponenteDigitalAnterior <> $hashDoComponenteDigital)) {
            $strConteudoAssinatura = $this->obterConteudoInternoAssinatura($objDocumentoDTO, true);
        }

          //Testa o hash com a tarja de valida��o contendo antigos URLs do �rg�o
          $hashDoComponenteDigital = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $strConteudoAssinatura, true));
          $objConfiguracaoModPEN = ConfiguracaoModPEN::getInstance();
          $arrControleURL = $objConfiguracaoModPEN->getValor("PEN", "ControleURL", false);

        if($arrControleURL!=null && isset($hashDoComponenteDigitalAnterior) && $hashDoComponenteDigital <> $hashDoComponenteDigitalAnterior) {

          foreach($arrControleURL["antigos"] as $urlAntigos){
              $dadosURL=[
              "atual"=>$arrControleURL["atual"],
              "antigo"=>$urlAntigos,
              ];
              $hashDoComponenteDigital = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $strConteudoAssinatura, true));
              if(isset($hashDoComponenteDigitalAnterior) && ($hashDoComponenteDigitalAnterior <> $hashDoComponenteDigital)) {
                  $strConteudoAssinatura = $this->obterConteudoInternoAssinatura($objDocumentoDTO, false, false, $dadosURL);
              }

              //verificar versao SEI4
              $hashDoComponenteDigital = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $strConteudoAssinatura, true));
              if(InfraUtil::compararVersoes(SEI_VERSAO, ">=", "4.0.0") && isset($hashDoComponenteDigitalAnterior) && ($hashDoComponenteDigitalAnterior <> $hashDoComponenteDigital)) {
                  $strConteudoAssinatura = $this->obterConteudoInternoAssinatura($objDocumentoDTO, false, false, $dadosURL, true);
              }

              //verificar versao SEI4 e verificar se a sigla do sistema mudou para SUPER
              $hashDoComponenteDigital = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $strConteudoAssinatura, true));
              if(InfraUtil::compararVersoes(SEI_VERSAO, ">=", "4.0.0") && isset($hashDoComponenteDigitalAnterior) && ($hashDoComponenteDigitalAnterior <> $hashDoComponenteDigital)) {
                  $strConteudoAssinatura = $this->obterConteudoInternoAssinatura($objDocumentoDTO, false, false, $dadosURL, true, false, true);
              }
          }
        }

          //Caso o hash ainda esteja inconsistente iremos usar a logica do  SEI 3.1.0
          $hashDoComponenteDigital = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $strConteudoAssinatura, true));
        if(isset($hashDoComponenteDigitalAnterior) && $hashDoComponenteDigital <> $hashDoComponenteDigitalAnterior) {
            $strConteudoAssinatura = $this->obterConteudoInternoAssinatura($objDocumentoDTO, false, true);
        }

          //Caso o hash ainda esteja inconsistente iremos usar a logica do  SEI 3.1.0
          // e verificar se a sigla do sistema mudou para SUPER
          $hashDoComponenteDigital = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $strConteudoAssinatura, true));
        if(isset($hashDoComponenteDigitalAnterior) && $hashDoComponenteDigital <> $hashDoComponenteDigitalAnterior) {
            $strConteudoAssinatura = $this->obterConteudoInternoAssinatura($objDocumentoDTO, false, true, null, false, false, true);
        }

          //Caso o hash ainda esteja inconsistente testaremos o caso de uso envio SEI4 e atualizado pra SEI4.0.3
          $hashDoComponenteDigital = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $strConteudoAssinatura, true));
        if(InfraUtil::compararVersoes(SEI_VERSAO, ">=", "4.0.0") && isset($hashDoComponenteDigitalAnterior) && $hashDoComponenteDigital <> $hashDoComponenteDigitalAnterior) {
            $strConteudoAssinatura = $this->obterConteudoInternoAssinatura($objDocumentoDTO, false, false, null, true, true);
        }

          //Caso o hash ainda esteja inconsistente testaremos o caso de uso envio SEI4 e atualizado pra SEI4.0.3
          // e verificar se a sigla do sistema mudou para SUPER
          $hashDoComponenteDigital = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $strConteudoAssinatura, true));
        if(InfraUtil::compararVersoes(SEI_VERSAO, ">=", "4.0.0") && isset($hashDoComponenteDigitalAnterior) && $hashDoComponenteDigital <> $hashDoComponenteDigitalAnterior) {
            $strConteudoAssinatura = $this->obterConteudoInternoAssinatura($objDocumentoDTO, false, false, null, true, true, true);
        }

          //Caso o hash ainda esteja inconsistente testaremos o caso de uso envio SEI3 e atualizado pra SEI4.0.3
          $hashDoComponenteDigital = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $strConteudoAssinatura, true));
        if(isset($hashDoComponenteDigitalAnterior) && $hashDoComponenteDigital <> $hashDoComponenteDigitalAnterior) {
            $strConteudoAssinatura = $this->obterConteudoInternoAssinatura($objDocumentoDTO, false, false, null, false, true);
        }

          //Caso o hash ainda esteja inconsistente testaremos o caso de uso envio SEI3 e atualizado pra SEI4.0.3
          // e verificar se a sigla do sistema mudou para SUPER
          $hashDoComponenteDigital = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $strConteudoAssinatura, true));
        if(isset($hashDoComponenteDigitalAnterior) && $hashDoComponenteDigital <> $hashDoComponenteDigitalAnterior) {
            $strConteudoAssinatura = $this->obterConteudoInternoAssinatura($objDocumentoDTO, false, false, null, false, true, true);
        }

          //Caso o hash ainda esteja inconsistente teremos que forcar a geracao do arquivo usando as fun��es do sei 3.0.11
          $hashDoComponenteDigital = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $strConteudoAssinatura, true));
        if(isset($hashDoComponenteDigitalAnterior) && $hashDoComponenteDigital <> $hashDoComponenteDigitalAnterior) {
            $strConteudoAssinatura = $this->obterConteudoInternoAssinatura($objDocumentoDTO, true, true);
        }

          //Caso o hash ainda esteja inconsistente teremos que forcar a geracao do arquivo usando as fun��es do sei 3.0.11
          $hashDoComponenteDigital = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $strConteudoAssinatura, true));
        if(isset($hashDoComponenteDigitalAnterior) && $hashDoComponenteDigital <> $hashDoComponenteDigitalAnterior) {
            $strConteudoAssinatura = $this->obterConteudoInternoAssinatura($objDocumentoDTO, true, true, null, false, false, true);
        }
      }


        $objInformacaoArquivo['NOME'] = $strProtocoloDocumentoFormatado . ".html";
        $objInformacaoArquivo['CONTEUDO'] = $strConteudoAssinatura;
        $objInformacaoArquivo['TAMANHO'] = strlen($strConteudoAssinatura);
        $objInformacaoArquivo['MIME_TYPE'] = 'text/html';
        $objInformacaoArquivo['ID_ANEXO'] = null;
        $objInformacaoArquivo['HASH_CONTEUDO'] = $hashDoComponenteDigitalAnterior ?: $hashDoComponenteDigital;

    } else if($objDocumentoDTO->getStrStaProtocoloProtocolo() == ProtocoloRN::$TP_DOCUMENTO_RECEBIDO) {
        $objAnexoDTO = $this->consultarAnexo($objDocumentoDTO->getDblIdDocumento());
      if(isset($objAnexoDTO)) {
          $strCaminhoAnexoTemporario = null;
          $strNomeComponenteDigital = "";
        if($bolMultiplosComponentes) {
          $strCaminhoAnexoCompactado = $this->objAnexoRN->obterLocalizacao($objAnexoDTO);
          [$strCaminhoAnexoTemporario, $strNomeComponenteDigital] = ProcessoEletronicoRN::descompactarComponenteDigital($strCaminhoAnexoCompactado, $numOrdemComponenteDigital);
          $strCaminhoAnexo = $strCaminhoAnexoTemporario;
        } else {
            $strCaminhoAnexo = $this->objAnexoRN->obterLocalizacao($objAnexoDTO);
            $strNomeComponenteDigital = $objAnexoDTO->getStrNome();
        } 

          $strConteudoAssinatura = null;

          $nrTamanhoBytesArquivo = filesize($strCaminhoAnexo);
          [$strDadosComplementares, $strMimeType] = $this->obterDadosComplementaresDoTipoDeArquivo($strCaminhoAnexo, $this->arrPenMimeTypes, $strProtocoloDocumentoFormatado);

          $objInformacaoArquivo['NOME'] = $strNomeComponenteDigital;
          $objInformacaoArquivo['CONTEUDO'] = $strConteudoAssinatura;
          $objInformacaoArquivo['TAMANHO'] = $nrTamanhoBytesArquivo;
          $objInformacaoArquivo['MIME_TYPE'] = $strMimeType;
          $objInformacaoArquivo['ID_ANEXO'] = $objAnexoDTO->getNumIdAnexo();
          $objInformacaoArquivo['dadosComplementaresDoTipoDeArquivo'] = $strDadosComplementares;
          $strHashConteudoAssinatura = hash_file("sha256", $strCaminhoAnexo, true);
          $objInformacaoArquivo['HASH_CONTEUDO'] = base64_encode($strHashConteudoAssinatura);

        if(file_exists($strCaminhoAnexoTemporario)) {
          try {
              unlink(DIR_SEI_TEMP . "/" . basename((string) $strCaminhoAnexoTemporario));
          } catch (Exception $e) {
              LogSEI::getInstance()->gravar($e, InfraLog::$ERRO);
          }
        }

      } elseif ($objDocumentoDTO->getStrStaEstadoProtocolo() == ProtocoloRN::$TE_DOCUMENTO_CANCELADO || $paramStrStaAssociacao == RelProtocoloProtocoloRN::$TA_DOCUMENTO_MOVIDO) {
          //Quando n�o � localizado um Anexo para um documento cancelado, os dados de componente digital precisam ser enviados
          //pois o Barramento considera o componente digital do documento de forma obrigat�ria
          $objInformacaoArquivo['NOME'] = 'cancelado.html';
          $objInformacaoArquivo['CONTEUDO'] = "[documento cancelado]";
          $objInformacaoArquivo['TAMANHO'] = 0;
          $objInformacaoArquivo['ID_ANEXO'] = null;
          $objInformacaoArquivo['MIME_TYPE'] = 'text/html';
          $objInformacaoArquivo['dadosComplementaresDoTipoDeArquivo'] = 'outro';
          $hashDoComponenteDigital = hash(self::ALGORITMO_HASH_DOCUMENTO, $objInformacaoArquivo['CONTEUDO'], true);
          $objInformacaoArquivo['HASH_CONTEUDO'] = base64_encode($hashDoComponenteDigital);
      } else {
          throw new InfraException("M�dulo do Tramita: Componente digital do documento {$strProtocoloDocumentoFormatado} n�o pode ser localizado.");
      }
    } elseif(in_array($objDocumentoDTO->getStrStaDocumento(), [DocumentoRN::$TD_FORMULARIO_GERADO, DocumentoRN::$TD_FORMULARIO_AUTOMATICO])) {
        $strConteudoAssinatura = null;
        $strConteudoFS = $this->recuperarConteudoComponenteImutavel($objDocumentoDTO);
      if(!empty($strConteudoFS)) {
          $objComponenteDigital = $this->consultarComponenteDigital($objDocumentoDTO->getDblIdDocumento());
          $hashDoComponenteDigitalAnterior = (isset($objComponenteDigital)) ? $objComponenteDigital->getStrHashConteudo() : null;
          $hashDoComponenteDigital = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $strConteudoFS, true));
        if(isset($hashDoComponenteDigitalAnterior) && $hashDoComponenteDigital == $hashDoComponenteDigitalAnterior) {
          $strConteudoAssinatura = $strConteudoFS;
        }
      }

      if(empty($strConteudoAssinatura)) {
          $objDocumentoDTO2 = new DocumentoDTO();
          $objDocumentoDTO2->setDblIdDocumento($objDocumentoDTO->getDblIdDocumento());
          $objDocumentoDTO2->setObjInfraSessao(SessaoSEI::getInstance());
          $objDocumentoRN = new DocumentoRN();
          $strConteudoAssinatura = $objDocumentoRN->consultarHtmlFormulario($objDocumentoDTO2);

          $objComponenteDigital = $this->consultarComponenteDigital($objDocumentoDTO->getDblIdDocumento());
          $hashDoComponenteDigitalAnterior = (isset($objComponenteDigital)) ? $objComponenteDigital->getStrHashConteudo() : null;

          $hashDoComponenteDigital = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $strConteudoAssinatura, true));
        if(isset($hashDoComponenteDigitalAnterior) && $hashDoComponenteDigital <> $hashDoComponenteDigitalAnterior) {
            // Caso 1: Verificar se a diferen�a de hash foi causada por mudan�a no fechamento das tags meta
            $strConteudoAssinatura = str_replace(
                '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />',
                '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">',
                $strConteudoAssinatura
            );
        }
      }

        $objInformacaoArquivo['NOME'] = $strProtocoloDocumentoFormatado . ".html";
        $objInformacaoArquivo['TAMANHO'] = strlen((string) $strConteudoAssinatura);
        $objInformacaoArquivo['MIME_TYPE'] = 'text/html';
        $objInformacaoArquivo['ID_ANEXO'] = null;
        $objInformacaoArquivo['CONTEUDO'] = $strConteudoAssinatura;
        $objInformacaoArquivo['HASH_CONTEUDO'] = $hashDoComponenteDigitalAnterior ?: $hashDoComponenteDigital;
    } else {
        $strStaDocumento = $objDocumentoDTO->getStrStaDocumento();
        throw new InfraException("M�dulo do Tramita: Tipo interno do documento n�o reconhecido pelo m�dulo de integra��o com o Tramita.gov.br (StaDocumento: $strStaDocumento)");
    }

      return $objInformacaoArquivo;
  }


    /**
     * Recupera o conte�do de documento interno imut�vel armazenado no Filesystem durante o envio de processos para o
     * Tramita.gov.br, garantindo o envio da vers�o correta enviado originalmente e impedindo erros de hash por conta de
     * mudan�a na forma din�mica de recupera��o do conte�do do documento
     *
     * @return str String contendo o conte�do do documento
     */
  private function recuperarConteudoComponenteImutavel(DocumentoDTO $objDocumentoDTO)
    {

      $strConteudoFS = null;
      $arrComponenteDigital = $this->retornaComponentesImutaveis($objDocumentoDTO);
    if(!empty($arrComponenteDigital)) {
        $objAnexoRN = new AnexoRN();
        $objAnexoDTO = new AnexoDTO();
        $objAnexoDTO->setNumIdAnexo($arrComponenteDigital[0]->getDblIdAnexoImutavel());
        $objAnexoDTO->setStrSinAtivo("S");
        $objAnexoDTO->retTodos();

        $objAnexoDTO = $objAnexoRN->consultarRN0736($objAnexoDTO);
        $strConteudoFS = file_get_contents($objAnexoRN->obterLocalizacao($objAnexoDTO));
    }

      return $strConteudoFS;
  }


  private function obterDadosComplementaresDoTipoDeArquivo($strCaminhoAnexo, $arrPenMimeTypes, $strProtocoloDocumentoFormatado)
    {
      $strDadosComplementaresDoTipoDeArquivo = "";
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
    try {
        $strMimeType = finfo_file($finfo, $strCaminhoAnexo);
      if(array_search($strMimeType, $arrPenMimeTypes) === false) {
        $strDadosComplementaresDoTipoDeArquivo = $strMimeType;
        $strMimeType = 'outro';
      }
    } catch(Exception $e) {
        throw new InfraException("M�dulo do Tramita: Erro obtendo informa��es do anexo do documento {$strProtocoloDocumentoFormatado}", $e);
    }
    finally{
        finfo_close($finfo);
    }

      return [$strDadosComplementaresDoTipoDeArquivo, $strMimeType];
  }

  private function listarDadosArquivos($objDocumentoDTO, $strStaAssociacao)
    {
      $numOrdemComponenteInicial = 1;
      $arrObjInformacaoArquivo = [];
      $arrObjComponentesDigitaisDTO = $this->listarComponentesDigitaisUltimoTramite($objDocumentoDTO);

    if(empty($arrObjComponentesDigitaisDTO)) {
        $arrObjInformacaoArquivo[$numOrdemComponenteInicial] = $this->obterDadosArquivo($objDocumentoDTO, $strStaAssociacao);
    } else {
        $bolMultiplosComponentes = count($arrObjComponentesDigitaisDTO) > 1;
        $this->corrigirNumeroOrdemComponentes($arrObjComponentesDigitaisDTO, $objDocumentoDTO->getStrProtocoloDocumentoFormatado());
      foreach ($arrObjComponentesDigitaisDTO as $objComponentesDigitaisDTO) {
          $numOrdemComponenteDigital = $objComponentesDigitaisDTO->getNumOrdem();
          $arrObjInformacaoArquivo[$numOrdemComponenteDigital] = $this->obterDadosArquivo($objDocumentoDTO, $strStaAssociacao, $bolMultiplosComponentes, $numOrdemComponenteDigital);
      }
    }

      return $arrObjInformacaoArquivo;
  }


  private function listarComponentesDigitaisUltimoTramite($objDocumentoDTO)
    {
      $arrObjComponentesDigitais = null;
      $dblIdProcedimento = $objDocumentoDTO->getDblIdProcedimento();
      $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
      $objProcessoEletronicoDTO->setDblIdProcedimento($dblIdProcedimento);

      $objProcessoEletronicoRN = new ProcessoEletronicoRN();
      $objUltimoTramiteDTO = $objProcessoEletronicoRN->consultarUltimoTramiteRecebido($objProcessoEletronicoDTO);

    if(!is_null($objUltimoTramiteDTO)) {
        $objComponenteDigitalBD = new ComponenteDigitalBD($this->getObjInfraIBanco());
        $arrObjComponentesDigitais = $objComponenteDigitalBD->listarComponentesDigitaisPeloTramite($objUltimoTramiteDTO->getNumIdTramite(), $objDocumentoDTO->getDblIdDocumento());
    }

      return $arrObjComponentesDigitais;
  }


    /**
     * M�todo de obten��o do conte�do do documento interno para envio e c�lculo de hash
     *
     * Anteriormente, os documentos enviados para o Barramento de Servi�os do PEN continham o link para o n�mero SEI do documento.
     * Este link passou a n�o ser mais considerado pois � uma informa��o din�mica e pertinente apenas quando o documento � visualizado
     * dentro do sistema SEI. Quando o documento � tramitado externamente, este link n�o possui mais sentido.
     *
     * Para tratar esta transi��o entre os formatos de documentos, existe o par�metro $bolFormatoLegado para indicar qual formato dever�
     * ser utilizado na montagem dos metadados para envio.     *
     *
     * @param  Double  $parDblIdDocumento Identificador do documento
     * @param  boolean $bolFormatoLegado  Flag indicando se a forma antiga de recupera��o de conte�do para envio dever� ser utilizada
     * @return String                     Conte�do completo do documento para envio
     */
  private function obterConteudoInternoAssinatura(DocumentoDTO $objDocumentoDTO, $bolFormatoLegado = false, $bolFormatoLegado3011 = false, $dadosURL = null, $bolSeiVersao4 = false, $bolTarjaLegada402 = false, $bolSiglaSistemaSUPER = false)
    {
      $objConfiguracaoModPEN = ConfiguracaoModPEN::getInstance();
      $arrSiglaOrgaoLegado = $objConfiguracaoModPEN->getValor("PEN", "SiglaOrgaoLegado", false);

      $objEditorDTO = new EditorDTO();
      $objEditorDTO->setDblIdDocumento($objDocumentoDTO->getDblIdDocumento());
      $objEditorDTO->setNumIdBaseConhecimento(null);
      $objEditorDTO->setStrSinCabecalho('S');
      $objEditorDTO->setStrSinRodape('S');
      $objEditorDTO->setStrSinIdentificacaoVersao('N');

    if($bolFormatoLegado) {
        $objEditorDTO->setStrSinIdentificacaoVersao('S');
        $objEditorDTO->setStrSinProcessarLinks('S');
    }

    if (InfraUtil::compararVersoes(SEI_VERSAO, ">=", self::VERSAO_CARIMBO_PUBLICACAO_OBRIGATORIO)) {
        $objEditorDTO->setStrSinCarimboPublicacao('N');
    }

      //para o caso de URLs antigos do �rg�o, ele testa o html com a tarja antiga
      $dados=[
        "parObjEditorDTO" => $objEditorDTO,
        "montarTarja" => $dadosURL==null?false:true,
        "controleURL" => $dadosURL,
        "bolTarjaLegada402" => $bolTarjaLegada402,
        "bolSiglaSistemaSUPER" => $bolSiglaSistemaSUPER
      ];

      $objEditorRN = new EditorRN();

      if($dadosURL!=null && $bolSeiVersao4==false) {
          $objEditorRN = new Editor3011RN();
      }elseif($dadosURL!=null && $bolSeiVersao4==true) {
          $objEditorRN = new EditorSEI4RN();
      }elseif($bolSeiVersao4 && $bolTarjaLegada402) {
          $objEditorRN = new EditorSEI4RN();
      }elseif(!$bolSeiVersao4 && $bolTarjaLegada402) {
          $objEditorRN = new Editor3011RN();
      }elseif($bolFormatoLegado3011) {
          //fix-107. Gerar doc exatamente da forma como estava na v3.0.11
          $objEditorRN = new Editor3011RN();
      }else{
          $dados = $objEditorDTO;
      }
      $bolSessao = SessaoSEI::getInstance()->isBolHabilitada();
      SessaoSEI::getInstance()->setBolHabilitada(false);
      $strResultado = $objEditorRN->consultarHtmlVersao($dados);
      SessaoSEI::getInstance()->setBolHabilitada($bolSessao);
      if(!empty($arrSiglaOrgaoLegado)) {
          $alterarTitle = true;

          //Busca metadados do processo registrado em tr�mite anterior
          $objMetadadosProcessoTramiteAnterior = $this->consultarMetadadosPEN($objDocumentoDTO->getDblIdProcedimento());

          //gerar o hash do conte�do do documento
          $hashDoComponenteDigital = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $strResultado, true));

        if(!empty($objMetadadosProcessoTramiteAnterior)) {
          foreach($objMetadadosProcessoTramiteAnterior->processo->documento as $documento){
            $strHashConteudo = ProcessoEletronicoRN::getHashFromMetaDados($documento->componenteDigital->hash);
            if($strHashConteudo == $hashDoComponenteDigital) {
                $alterarTitle = false;
            }
          }

          if($alterarTitle && $bolSiglaSistemaSUPER) {
              $pattern = '/<title>SUPER\/'.$arrSiglaOrgaoLegado["atual"].'/';
              $replacement = "<title>SEI/".$arrSiglaOrgaoLegado["antiga"];
              $strResultado = preg_replace($pattern, $replacement, $strResultado);
          }

          if($alterarTitle && !$bolSiglaSistemaSUPER) {
              $pattern = '/<title>SEI\/'.$arrSiglaOrgaoLegado["atual"].'/';
              $replacement = "<title>SEI/".$arrSiglaOrgaoLegado["antiga"];
              $strResultado = preg_replace($pattern, $replacement, $strResultado);
          }
        }
      }

      return $strResultado;
  }


  private function obterTipoDeConteudo($strMimeType)
    {
    if(!isset($strMimeType)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $strMimeType n�o informado.');
    }

      $resultado = self::TC_TIPO_CONTEUDO_OUTROS;

    if(preg_match(self::REGEX_ARQUIVO_TEXTO, $strMimeType)) {
        $resultado = self::TC_TIPO_CONTEUDO_TEXTO;
    } else if(preg_match(self::REGEX_ARQUIVO_IMAGEM, $strMimeType)) {
        $resultado = self::TC_TIPO_CONTEUDO_IMAGEM;
    } else if(preg_match(self::REGEX_ARQUIVO_AUDIO, $strMimeType)) {
        $resultado = self::TC_TIPO_CONTEUDO_AUDIO;
    } else if(preg_match(self::REGEX_ARQUIVO_VIDEO, $strMimeType)) {
        $resultado = self::TC_TIPO_CONTEUDO_VIDEO;
    }

      return $resultado;
  }

  private function atribuirNumeracaoDocumentoREST($objDocumento, DocumentoDTO $parObjDocumentoDTO)
    {
      $objSerieDTO = $this->consultarSerie($parObjDocumentoDTO->getNumIdSerie());

    if(!isset($objSerieDTO)) {
        throw new InfraException("Tipo de Documento n�o pode ser localizado. (C�digo: ".$parObjDocumentoDTO->getNumIdSerie().")");
    }

      $strStaNumeracao = $objSerieDTO->getStrStaNumeracao();

    if ($strStaNumeracao == SerieRN::$TN_SEQUENCIAL_UNIDADE) {
        $objDocumento['identificacao']['numero'] = intval(mb_convert_encoding($parObjDocumentoDTO->getStrNumero(), 'UTF-8', 'ISO-8859-1'));
        $objDocumento['identificacao']['siglaDaUnidadeProdutora'] = mb_convert_encoding($parObjDocumentoDTO->getStrSiglaUnidadeGeradoraProtocolo(), 'UTF-8', 'ISO-8859-1');
        $objDocumento['identificacao']['complemento'] = mb_convert_encoding($this->objProcessoEletronicoRN->reduzirCampoTexto($parObjDocumentoDTO->getStrDescricaoUnidadeGeradoraProtocolo(), 100), 'UTF-8', 'ISO-8859-1');
    } elseif ($strStaNumeracao == SerieRN::$TN_SEQUENCIAL_ORGAO) {
        $objOrgaoDTO = $this->consultarOrgao($parObjDocumentoDTO->getNumIdOrgaoUnidadeGeradoraProtocolo());
        $objDocumento['identificacao']['numero'] = intval(mb_convert_encoding($parObjDocumentoDTO->getStrNumero(), 'UTF-8', 'ISO-8859-1'));
        $objDocumento['identificacao']['siglaDaUnidadeProdutora'] = mb_convert_encoding($objOrgaoDTO->getStrSigla(), 'UTF-8', 'ISO-8859-1');
        $objDocumento['identificacao']['complemento'] = mb_convert_encoding($this->objProcessoEletronicoRN->reduzirCampoTexto($objOrgaoDTO->getStrDescricao(), 100), 'UTF-8', 'ISO-8859-1');
    } elseif ($strStaNumeracao == SerieRN::$TN_SEQUENCIAL_ANUAL_UNIDADE) {
        $objDocumento['identificacao']['siglaDaUnidadeProdutora'] = mb_convert_encoding($parObjDocumentoDTO->getStrSiglaUnidadeGeradoraProtocolo(), 'UTF-8', 'ISO-8859-1');
        $objDocumento['identificacao']['complemento'] = mb_convert_encoding($this->objProcessoEletronicoRN->reduzirCampoTexto($parObjDocumentoDTO->getStrDescricaoUnidadeGeradoraProtocolo(), 100), 'UTF-8', 'ISO-8859-1');
        $objDocumento['identificacao']['numero'] = intval(mb_convert_encoding($parObjDocumentoDTO->getStrNumero(), 'UTF-8', 'ISO-8859-1'));
        $objDocumento['identificacao']['ano'] = substr($parObjDocumentoDTO->getDtaGeracaoProtocolo(), 6, 4);
    } elseif ($strStaNumeracao == SerieRN::$TN_SEQUENCIAL_ANUAL_ORGAO) {
        $objOrgaoDTO = $this->consultarOrgao($parObjDocumentoDTO->getNumIdOrgaoUnidadeGeradoraProtocolo());
        $objDocumento['identificacao']['numero'] = intval(mb_convert_encoding($parObjDocumentoDTO->getStrNumero(), 'UTF-8', 'ISO-8859-1'));
        $objDocumento['identificacao']['siglaDaUnidadeProdutora'] = mb_convert_encoding($objOrgaoDTO->getStrSigla(), 'UTF-8', 'ISO-8859-1');
        $objDocumento['identificacao']['complemento'] = mb_convert_encoding($this->objProcessoEletronicoRN->reduzirCampoTexto($objOrgaoDTO->getStrDescricao(), 100), 'UTF-8', 'ISO-8859-1');
        $objDocumento['identificacao']['ano'] = substr($parObjDocumentoDTO->getDtaGeracaoProtocolo(), 6, 4);
    }
    
      return $objDocumento;
  }

  private function adicionarProcessosApensadosREST($objProcesso, $arrIdProcessoApensado)
    {
    if (isset($arrIdProcessoApensado) && is_array($arrIdProcessoApensado) && count($arrIdProcessoApensado) > 0) {
        $objProcesso['processoApensado'] = [];
  
      foreach ($arrIdProcessoApensado as $idProcedimentoApensado) {
        $objProcesso['processoApensado'][] = $this->construirProcessoREST($idProcedimentoApensado);
      }
    }

      return $objProcesso;
  }

  private function consultarUnidade($numIdUnidade)
    {
    if(!isset($numIdUnidade)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $numIdUnidade n�o informado.');
    }

      $objUnidadeDTO = new UnidadeDTO();
      $objUnidadeDTO->setNumIdUnidade($numIdUnidade);
      $objUnidadeDTO->setBolExclusaoLogica(false);
      $objUnidadeDTO->retStrDescricao();

      return $this->objUnidadeRN->consultarRN0125($objUnidadeDTO);
  }

  private function consultarSerie($numIdSerie)
    {
    if(!isset($numIdSerie)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $numIdSerie n�o informado.');
    }

      $objSerieDTO = new SerieDTO();
      $objSerieDTO->setNumIdSerie($numIdSerie);
      $objSerieDTO->setBolExclusaoLogica(false);
      $objSerieDTO->retStrStaNumeracao();

      return $this->objSerieRN->consultarRN0644($objSerieDTO);
  }

  private function consultarOrgao($numIdOrgao)
    {
      $objOrgaoDTO = new OrgaoDTO();
      $objOrgaoDTO->setNumIdOrgao($numIdOrgao);
      $objOrgaoDTO->retStrSigla();
      $objOrgaoDTO->retStrDescricao();
      $objOrgaoDTO->setBolExclusaoLogica(false);

      return $this->objOrgaoRN->consultarRN1352($objOrgaoDTO);
  }

  public function consultarProcedimento($numIdProcedimento)
    {
    if(!isset($numIdProcedimento)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $numIdProcedimento n�o informado.');
    }

      $objProcedimentoDTO = new ProcedimentoDTO();
      $objProcedimentoDTO->setDblIdProcedimento($numIdProcedimento);
      $objProcedimentoDTO->retStrProtocoloProcedimentoFormatado();
      $objProcedimentoDTO->retStrStaNivelAcessoGlobalProtocolo();
      $objProcedimentoDTO->retStrStaNivelAcessoLocalProtocolo();
      $objProcedimentoDTO->retNumIdUnidadeGeradoraProtocolo();
      $objProcedimentoDTO->retNumIdUsuarioGeradorProtocolo();
      $objProcedimentoDTO->retStrNomeTipoProcedimento();
      $objProcedimentoDTO->retStrDescricaoProtocolo();
      $objProcedimentoDTO->retDtaGeracaoProtocolo();
      $objProcedimentoDTO->retStrStaEstadoProtocolo();
      $objProcedimentoDTO->retDblIdProcedimento();
      $objProcedimentoDTO->retNumIdHipoteseLegalProtocolo();
      $objProcedimentoDTO->retStrProtocoloProcedimentoFormatadoPesquisa();

      return $this->objProcedimentoRN->consultarRN0201($objProcedimentoDTO);
  }

  public function listarInteressados($numIdProtocolo)
    {
    if(!isset($numIdProtocolo)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $numIdProtocolo n�o informado.');
    }

      $objParticipanteDTO = new ParticipanteDTO();
      $objParticipanteDTO->retNumIdContato();
      $objParticipanteDTO->retStrNomeContato();
      $objParticipanteDTO->setDblIdProtocolo($numIdProtocolo);
      $objParticipanteDTO->setStrStaParticipacao(ParticipanteRN::$TP_INTERESSADO);

      return $this->objParticipanteRN->listarRN0189($objParticipanteDTO);
  }

  private function consultarAnexo($dblIdDocumento)
    {
    if(!isset($dblIdDocumento)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $dblIdDocumento n�o informado.');
    }

      $objAnexoDTO = new AnexoDTO();
      $objAnexoDTO->retNumIdAnexo();
      $objAnexoDTO->retStrNome();
      $objAnexoDTO->retDblIdProtocolo();
      $objAnexoDTO->retDthInclusao();
      $objAnexoDTO->retNumTamanho();
      $objAnexoDTO->retStrProtocoloFormatadoProtocolo();
      $objAnexoDTO->setDblIdProtocolo($dblIdDocumento);

      return $this->objAnexoRN->consultarRN0736($objAnexoDTO);
  }

  private function consultarUsuario($numIdUsuario)
    {
    if(!isset($numIdUsuario)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $numIdUsuario n�o informado.');
    }

      $objUsuarioDTO = new UsuarioDTO();
      $objUsuarioDTO->setNumIdUsuario($numIdUsuario);
      $objUsuarioDTO->setBolExclusaoLogica(false);
      $objUsuarioDTO->retStrNome();
      $objUsuarioDTO->retDblCpfContato();

      return $this->objUsuarioRN->consultarRN0489($objUsuarioDTO);
  }

    /**
     * Recupera a lista de documentos do processo, mantendo sua ordem conforme definida pelo usu�rio ap�s reordena��es e
     * movimenta��es de documentos
     *
     * Esta fun��o basicamente aplica a desestrutura��o do retorno da fun��o listarDocumentosRelacionados para obter somente
     * as inst�ncias dos objetos DocumentoDTO
     *
     * @param  num $idProcedimento
     * @return array
     */
  public function listarDocumentos($idProcedimento)
    {
      return array_map(
          function ($item) {
              return $item["Documento"];
          },
          $this->listarDocumentosRelacionados($idProcedimento)
      );
  }


  public function listarDocumentosRelacionados($idProcedimento, $idDblDocumentoFiltro = null)
    {
    if(!isset($idProcedimento)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $idProcedimento n�o informado.');
    }

      $arrObjDocumentoDTO = [];
      $arrAssociacaoDocumentos = $this->objProcessoEletronicoRN->listarAssociacoesDocumentos($idProcedimento);
      $arrIdDocumentos = array_map(
          function ($item) {
              return $item["IdProtocolo"];
          }, $arrAssociacaoDocumentos
      );

    if(!empty($arrIdDocumentos)) {
        $objDocumentoDTO = new DocumentoDTO();
        $objDocumentoDTO->retStrDescricaoUnidadeGeradoraProtocolo();
        $objDocumentoDTO->retNumIdOrgaoUnidadeGeradoraProtocolo();
        $objDocumentoDTO->retStrProtocoloProcedimentoFormatado();
        $objDocumentoDTO->retStrSiglaUnidadeGeradoraProtocolo();
        $objDocumentoDTO->retStrStaNivelAcessoLocalProtocolo();
        $objDocumentoDTO->retStrProtocoloDocumentoFormatado();
        $objDocumentoDTO->retNumIdUsuarioGeradorProtocolo();
        $objDocumentoDTO->retStrStaProtocoloProtocolo();
        $objDocumentoDTO->retNumIdUnidadeResponsavel();
        $objDocumentoDTO->retStrStaEstadoProtocolo();
        $objDocumentoDTO->retStrDescricaoProtocolo();
        $objDocumentoDTO->retStrConteudoAssinatura();
        $objDocumentoDTO->retDtaGeracaoProtocolo();
        $objDocumentoDTO->retDblIdProcedimento();
        $objDocumentoDTO->retDblIdDocumento();
        $objDocumentoDTO->retStrNomeSerie();
        $objDocumentoDTO->retNumIdSerie();
        $objDocumentoDTO->retStrNumero();
        $objDocumentoDTO->retNumIdTipoConferencia();
        $objDocumentoDTO->retStrStaDocumento();
        $objDocumentoDTO->retNumIdHipoteseLegalProtocolo();
        $objDocumentoDTO->setDblIdDocumento($arrIdDocumentos, InfraDTO::$OPER_IN);

        $arrObjDocumentoDTOBanco = $this->objDocumentoRN->listarRN0008($objDocumentoDTO);
        $arrObjDocumentoDTOIndexado = InfraArray::indexarArrInfraDTO($arrObjDocumentoDTOBanco, 'IdDocumento');

        //Mantem ordena��o definida pelo usu�rio, indicando qual a sua associa��o com o processo
        $arrObjDocumentoDTO = [];
      foreach($arrAssociacaoDocumentos as $objAssociacaoDocumento){
        $dblIdDocumento = $objAssociacaoDocumento["IdProtocolo"];
        $bolIdDocumentoExiste = array_key_exists($dblIdDocumento, $arrObjDocumentoDTOIndexado) && isset($arrObjDocumentoDTOIndexado[$dblIdDocumento]);
        $bolIdDocumentoFiltrado = is_null($idDblDocumentoFiltro) || ($dblIdDocumento == $idDblDocumentoFiltro);

        if ($bolIdDocumentoExiste && $bolIdDocumentoFiltrado) {
            $arrObjDocumentoDTO[] = ["Documento" => $arrObjDocumentoDTOIndexado[$dblIdDocumento], "StaAssociacao" => $objAssociacaoDocumento["StaAssociacao"]];
        }
      }
    }

      return $arrObjDocumentoDTO;
  }

    /**
     * Retorna o nome do documento no PEN
     *
     * @param  int
     * @return string
     */
  private function consultarNomeDocumentoPEN(DocumentoDTO $objDocumentoDTO)
    {

      $objMapDTO = new PenRelTipoDocMapEnviadoDTO(true);
      $objMapDTO->setNumMaxRegistrosRetorno(1);
      $objMapDTO->setNumIdSerie($objDocumentoDTO->getNumIdSerie());
      $objMapDTO->retStrNomeSerie();

      $objMapBD = new GenericoBD($this->getObjInfraIBanco());
      $objMapDTO = $objMapBD->consultar($objMapDTO);

    if(empty($objMapDTO)) {
        $strNome = '[ref '.$objDocumentoDTO->getStrNomeSerie().']';
    }
    else {
        $strNome = $objMapDTO->getStrNomeSerie();

    }

      return $strNome;
  }

  private function enviarComponentesDigitais($strNumeroRegistro, $numIdTramite, $strProtocolo, $bolSinProcessamentoEmBloco = false)
    {
    if (!isset($strNumeroRegistro)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $strNumeroRegistro n�o informado.');
    }

    if (!isset($numIdTramite)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $numIdTramite n�o informado.');
    }

    if (!isset($strProtocolo)) {
        throw new InfraException('M�dulo do Tramita: Par�metro $strProtocolo n�o informado.');
    }

      //Obter dados dos componetes digitais
      $objComponenteDigitalBD = new ComponenteDigitalBD($this->getObjInfraIBanco());
      $objComponenteDigitalDTO = new ComponenteDigitalDTO();
      $objComponenteDigitalDTO->setStrNumeroRegistro($strNumeroRegistro);
      $objComponenteDigitalDTO->setNumIdTramite($numIdTramite);
      $objComponenteDigitalDTO->setStrSinEnviar("S");
      $objComponenteDigitalDTO->retDblIdDocumento();
      $objComponenteDigitalDTO->retNumTicketEnvioComponentes();
      $objComponenteDigitalDTO->retStrProtocoloDocumentoFormatado();
      $objComponenteDigitalDTO->retStrHashConteudo();
      $objComponenteDigitalDTO->retStrProtocolo();
      $objComponenteDigitalDTO->retNumOrdem();
      $objComponenteDigitalDTO->retStrNome();
      $objComponenteDigitalDTO->retDblIdProcedimento();
      $objComponenteDigitalDTO->setOrdNumOrdem(InfraDTO::$TIPO_ORDENACAO_ASC);
      $objComponenteDigitalDTO->setOrdNumOrdemDocumento(InfraDTO::$TIPO_ORDENACAO_ASC);

      $arrComponentesDigitaisDTOBanco = $objComponenteDigitalBD->listar($objComponenteDigitalDTO);
    if (!empty($arrComponentesDigitaisDTOBanco)) {
        $arrComponentesDigitaisIndexadosDTO = InfraArray::indexarArrInfraDTO($arrComponentesDigitaisDTOBanco, "IdDocumento", true);

        //Construir objeto Componentes digitais
        $arrHashComponentesEnviados = [];

      foreach ($arrComponentesDigitaisIndexadosDTO as $arrComponentesDigitaisDTO) {
          $bolMultiplosComponentes = count($arrComponentesDigitaisDTO) > 1;
          $this->corrigirNumeroOrdemComponentes($arrComponentesDigitaisDTO, $arrComponentesDigitaisDTO[0]->getStrProtocoloDocumentoFormatado());
        foreach ($arrComponentesDigitaisDTO as $objComponenteDigitalDTO) {

          if(!$bolSinProcessamentoEmBloco) {
                $this->barraProgresso->setStrRotulo(sprintf(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_DOCUMENTO, $objComponenteDigitalDTO->getStrProtocoloDocumentoFormatado()));
          }else{
                  $this->gravarLogDebug(sprintf(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_DOCUMENTO, $objComponenteDigitalDTO->getStrProtocoloDocumentoFormatado()), 2);
          }

                $dadosDoComponenteDigital = new stdClass();
                $dadosDoComponenteDigital->ticketParaEnvioDeComponentesDigitais = $objComponenteDigitalDTO->getNumTicketEnvioComponentes();

              //Processos apensados. Mesmo erro relatado com dois arquivos iguais em docs diferentes no mesmo processo
                $dadosDoComponenteDigital->protocolo = $objComponenteDigitalDTO->getStrProtocolo();
                $dadosDoComponenteDigital->hashDoComponenteDigital = $objComponenteDigitalDTO->getStrHashConteudo();

                $arrObjDocumentoDTOAssociacao = $this->listarDocumentosRelacionados($objComponenteDigitalDTO->getDblIdProcedimento(), $objComponenteDigitalDTO->getDblIdDocumento());
                $objDocumentoDTO = null;
                $strStaAssociacao = null;
          foreach ($arrObjDocumentoDTOAssociacao as $objDocumentoDTOAssociacao) {
            $strStaAssociacao = $objDocumentoDTOAssociacao['StaAssociacao'];
            if($strStaAssociacao != RelProtocoloProtocoloRN::$TA_DOCUMENTO_MOVIDO) {
                        $objDocumentoDTO = $objDocumentoDTOAssociacao['Documento'];
            }
          }
                $strNomeDocumento = $this->consultarNomeDocumentoPEN($objDocumentoDTO);

              //Verifica se existe o objeto anexoDTO para recuperar informa��es do arquivo
                $nrTamanhoArquivoMb = 0;
                $nrTamanhoBytesArquivo = 0;
                $nrTamanhoMegasMaximo = ProcessoEletronicoRN::obterTamanhoBlocoTransferencia();
                $nrTamanhoBytesMaximo = ($nrTamanhoMegasMaximo * 1024 ** 2); //Qtd de MB definido como parametro

          try {
          //Verifica se o arquivo � maior que o tamanho m�ximo definido para envio, se for, realiza o particionamento do arquivo
            if(!in_array($objComponenteDigitalDTO->getStrHashConteudo(), $arrHashComponentesEnviados)) {
              if($objDocumentoDTO->getStrStaProtocoloProtocolo() == ProtocoloRN::$TP_DOCUMENTO_RECEBIDO) {
                $objAnexoDTO = $this->consultarAnexo($objDocumentoDTO->getDblIdDocumento());
                if(!$objAnexoDTO) {
                            $strProtocoloDocumento = $objDocumentoDTO->getStrProtocoloDocumentoFormatado();
                            throw new InfraException("M�dulo do Tramita: Anexo do documento $strProtocoloDocumento n�o pode ser localizado.");
                }

                  $strCaminhoAnexoTemporario = null;
                if($bolMultiplosComponentes) {
                            $numOrdemComponenteDigital = $objComponenteDigitalDTO->getNumOrdem();
                            $strCaminhoAnexoCompactado = $this->objAnexoRN->obterLocalizacao($objAnexoDTO);
                            [$strCaminhoAnexoTemporario, ] = ProcessoEletronicoRN::descompactarComponenteDigital($strCaminhoAnexoCompactado, $numOrdemComponenteDigital);
                            $strCaminhoAnexo = $strCaminhoAnexoTemporario;
                } else {
                          $strCaminhoAnexo = $this->objAnexoRN->obterLocalizacao($objAnexoDTO);
                }

                                $nrTamanhoBytesArquivo = filesize($strCaminhoAnexo); //Tamanho total do arquivo
                                $nrTamanhoArquivoMb = ($nrTamanhoBytesArquivo / 1024 ** 2);

                        //M�todo que ir� particionar o arquivo em partes para realizar o envio
                                $this->particionarComponenteDigitalParaEnvio(
                                    $strCaminhoAnexo, $dadosDoComponenteDigital, $nrTamanhoArquivoMb, $nrTamanhoMegasMaximo,
                                    $nrTamanhoBytesMaximo, $bolSinProcessamentoEmBloco
                      );

                          //Finalizar o envio das partes do componente digital
                          $parametros = new stdClass();
                          $parametros->dadosDoTerminoDeEnvioDePartes = $dadosDoComponenteDigital;
                          $this->objProcessoEletronicoRN->sinalizarTerminoDeEnvioDasPartesDoComponente($parametros);

                if(file_exists($strCaminhoAnexoTemporario)) {
                  try {
                              unlink(DIR_SEI_TEMP . "/" . basename((string) $strCaminhoAnexoTemporario));
                  } catch (Exception $e) {
                                        LogSEI::getInstance()->gravar($e, InfraLog::$ERRO);
                  }
                }
              } else {
                $objDadosArquivo = $this->obterDadosArquivo($objDocumentoDTO, $strStaAssociacao);
                $dados=[
                "objDocumentoDTO"=>$objDocumentoDTO,
                "objDadosArquivo"=>$objDadosArquivo,
                "dadosDoComponenteDigital"=>$dadosDoComponenteDigital,
                "idProcedimentoPrincipal"=>$objComponenteDigitalDTO->getDblIdProcedimento()
                          ];

                          $this->salvarAnexoImutavel($dados);
                          $dadosDoComponenteDigital->conteudoDoComponenteDigital = $objDadosArquivo['CONTEUDO'];

                          $parametros = new stdClass();
                          $parametros->dadosDoComponenteDigital = $dadosDoComponenteDigital;
                          $this->objProcessoEletronicoRN->enviarComponenteDigital($parametros);

                if(!$bolSinProcessamentoEmBloco) {
                  $this->barraProgresso->mover($this->contadorDaBarraDeProgresso);
                  $this->contadorDaBarraDeProgresso++;
                }
              }

                        $arrHashComponentesEnviados[] = $objComponenteDigitalDTO->getStrHashConteudo();

                        //Bloquea documento para atualizao, j� que ele foi visualizado
                        $this->objDocumentoRN->bloquearConteudo($objDocumentoDTO);
                        $this->objProcedimentoAndamentoRN->cadastrar(
                            ProcedimentoAndamentoDTO::criarAndamento(
                                sprintf(
                                    'Enviando %s %s', $strNomeDocumento,
                                    $objComponenteDigitalDTO->getStrProtocoloDocumentoFormatado()
                                ), 'S'
                            )
                        );
            }
          } catch (\Exception $e) {
              $strProtocoloDocumento = $objComponenteDigitalDTO->getStrProtocoloDocumentoFormatado();
              $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento(sprintf('Enviando %s %s', $strNomeDocumento, $strProtocoloDocumento), 'N'));
              throw new InfraException("M�dulo do Tramita: Erro processando envio do componentes digitais do documento $strProtocoloDocumento", $e);
          }
        }
      }
    }
  }


  protected function retornaComponentesImutaveisControlado($objDocumentoDTO)
    {

      $objComponenteDigitalDTO = new ComponenteDigitalDTO();
      $objComponenteDigitalDTO->setDblIdDocumento($objDocumentoDTO->getDblIdDocumento());
      $objComponenteDigitalDTO->setDblIdAnexoImutavel(null, InfraDTO::$OPER_DIFERENTE);
      $objComponenteDigitalDTO->setStrTarjaLegada("N");
      $objComponenteDigitalDTO->retTodos();

      $objComponenteDigitalBD = new ComponenteDigitalBD($this->getObjInfraIBanco());

      return $objComponenteDigitalBD->listar($objComponenteDigitalDTO);


  }


  protected function salvarAnexoImutavelControlado($dados)
    {

    try{

        $objDocumentoDTO=$dados["objDocumentoDTO"];
        $objDadosArquivo=$dados["objDadosArquivo"];
        $dadosDoComponenteDigital=$dados["dadosDoComponenteDigital"];
        $idProcedimentoPrincipal=$dados["idProcedimentoPrincipal"];


        $arrComponenteDigital=$this->retornaComponentesImutaveis($objDocumentoDTO);

      if(empty($arrComponenteDigital)) {

        $objAnexoRN = new AnexoRN();

        $strConteudoAssinatura=$objDadosArquivo['CONTEUDO'];
        $strNomeArquivoUploadHtml = $objAnexoRN->gerarNomeArquivoTemporario();

        if (file_put_contents(DIR_SEI_TEMP.'/'.$strNomeArquivoUploadHtml, $strConteudoAssinatura) === false) {
            throw new InfraException('M�dulo do Tramita: Erro criando arquivo html tempor�rio para envio do e-mail.');
        }

        $objAnexoDTO = new AnexoDTO();
        $objAnexoDTO->setNumIdAnexo($strNomeArquivoUploadHtml);
        $objAnexoDTO->setDblIdProtocolo($objDocumentoDTO->getDblIdDocumento());
        $objAnexoDTO->setDthInclusao(InfraData::getStrDataHoraAtual());
        $objAnexoDTO->setNumTamanho(filesize(DIR_SEI_TEMP.'/'.$strNomeArquivoUploadHtml));
        $objAnexoDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
        $objAnexoDTO->setStrNome($objDocumentoDTO->getStrProtocoloDocumentoFormatado() . ".html");
        $objAnexoDTO->setNumIdUnidade($objDocumentoDTO->getNumIdUnidadeResponsavel());
        $objAnexoDTO->setStrSinAtivo("S");

        $objAnexoDTO=$objAnexoRN->cadastrarRN0172($objAnexoDTO);

        $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
        $objProcessoEletronicoDTO->setDblIdProcedimento($idProcedimentoPrincipal);

        $objTramiteBD = new TramiteBD($this->getObjInfraIBanco());
        $objTramiteDTO=$objTramiteBD->consultarUltimoTramite($objProcessoEletronicoDTO, ProcessoEletronicoRN::$STA_TIPO_TRAMITE_ENVIO);

        $objComponenteDigitalDTO = new ComponenteDigitalDTO();
        $objComponenteDigitalDTO->setDblIdDocumento($objDocumentoDTO->getDblIdDocumento());
        $objComponenteDigitalDTO->setNumIdTramite($objTramiteDTO->getNumIdTramite());
        $objComponenteDigitalDTO->retTodos();

        $objComponenteDigitalBD = new ComponenteDigitalBD($this->getObjInfraIBanco());
        $objComponenteDigitalDTO=$objComponenteDigitalBD->consultar($objComponenteDigitalDTO);

        $objComponenteDigitalDTO->setDblIdAnexoImutavel($objAnexoDTO->getNumIdAnexo());
        $objComponenteDigitalDTO->setDblIdProcedimento($objComponenteDigitalDTO->getDblIdProcedimento());
        $objComponenteDigitalDTO->setStrNumeroRegistro($objComponenteDigitalDTO->getStrNumeroRegistro());
        $objComponenteDigitalDTO=$objComponenteDigitalBD->alterar($objComponenteDigitalDTO);
      }
    }catch(Exception $e){
        throw new InfraException("M�dulo do Tramita: Erro salvando anexo imut�vel", $e);
    }
  }



  private function corrigirNumeroOrdemComponentes($arrComponentesDigitaisDTO, $strProtocoloDocumento)
    {
      $arrOrdensComponentes = InfraArray::converterArrInfraDTO($arrComponentesDigitaisDTO, "Ordem");
    if(min($arrOrdensComponentes) <= 0) {
      foreach ($arrComponentesDigitaisDTO as $objComponentesDigitaisDTO) {
        $numOrdemCorrigido = $objComponentesDigitaisDTO->getNumOrdem() + 1;
        $objComponentesDigitaisDTO->setNumOrdem($numOrdemCorrigido);
      }
    }

      $arrOrdensAtualizadas = InfraArray::converterArrInfraDTO($arrComponentesDigitaisDTO, "Ordem");
    if(count($arrOrdensAtualizadas) != count(array_unique($arrOrdensAtualizadas))) {
        throw new InfraException("Falha identificada na defini��o da ordem dos componentes digitais do documento $strProtocoloDocumento");
    }
  }


  private function validarParametrosExpedicao(InfraException $objInfraException, ExpedirProcedimentoDTO $objExpedirProcedimentoDTO)
    {
    if(!isset($objExpedirProcedimentoDTO)) {
        $objInfraException->adicionarValidacao('Par�metro $objExpedirProcedimentoDTO n�o informado.');
    }      

      //TODO: Validar se repositrio de origem foi informado
    if (InfraString::isBolVazia($objExpedirProcedimentoDTO->getNumIdRepositorioOrigem())) {
        $objInfraException->adicionarValidacao('Identifica��o do reposit�rio de estruturas da unidade atual n�o informado. ID do processo: '.$objExpedirProcedimentoDTO->getDblIdProcedimento().". ");
    }

      //TODO: Validar se unidade de origem foi informado
    if (InfraString::isBolVazia($objExpedirProcedimentoDTO->getNumIdUnidadeOrigem())) {
        $objInfraException->adicionarValidacao('Identifica��o da unidade atual no reposit�rio de estruturas organizacionais n�o informado. ID do processo: '.$objExpedirProcedimentoDTO->getDblIdProcedimento().". ");
    }

      //TODO: Validar se repositrio foi devidamente informado
    if (InfraString::isBolVazia($objExpedirProcedimentoDTO->getNumIdRepositorioDestino())) {
        $objInfraException->adicionarValidacao('Reposit�rio de estruturas organizacionais n�o informado. ID do processo: '.$objExpedirProcedimentoDTO->getDblIdProcedimento().". ");
    }

      //TODO: Validar se unidade foi devidamente informada
    if (InfraString::isBolVazia($objExpedirProcedimentoDTO->getNumIdUnidadeDestino())) {
        $objInfraException->adicionarValidacao('Unidade de destino n�o informado. ID do processo: '.$objExpedirProcedimentoDTO->getDblIdProcedimento().". ");
    }

      //TODO: Validar se motivo de urgncia foi devidamente informado, caso expedio urgente
    if ($objExpedirProcedimentoDTO->getBolSinUrgente() && InfraString::isBolVazia($objExpedirProcedimentoDTO->getNumIdMotivoUrgencia())) {
        $objInfraException->adicionarValidacao('Motivo de urg�ncia n�o informado. ID do processo: '.$objExpedirProcedimentoDTO->getDblIdProcedimento().". ");
    }
  }

  private function validarDocumentacaoExistende(InfraException $objInfraException, ProcedimentoDTO $objProcedimentoDTO, $strAtributoValidacao, $sinProcessoEmBloco = false)
      {
    $arrObjDocumentoDTO = $objProcedimentoDTO->getArrObjDocumentoDTO();
    if(!isset($arrObjDocumentoDTO) || count($arrObjDocumentoDTO) == 0) {
      if ($sinProcessoEmBloco) {
        $strProtocoloFormatado = $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado();
        $mensagem = "Prezado(a) usu�rio(a), o processo $strProtocoloFormatado n�o possui documentos. "
          . "Dessa forma, n�o foi poss�vel realizar sua inser��o no bloco selecionado.";
        $objInfraException->adicionarValidacao($mensagem, $strAtributoValidacao);
      } else {
        $objInfraException->adicionarValidacao('N�o � poss�vel tramitar um processo sem documentos', $strAtributoValidacao);
      }
    }
  }

  private function validarDadosProcedimento(InfraException $objInfraException, ProcedimentoDTO $objProcedimentoDTO, $strAtributoValidacao)
    {
    if($objProcedimentoDTO->isSetStrDescricaoProtocolo() && InfraString::isBolVazia($objProcedimentoDTO->getStrDescricaoProtocolo())) {
        $objInfraException->adicionarValidacao("Descri��o do processo {$objProcedimentoDTO->getStrProtocoloProcedimentoFormatado()} n�o informado.", $strAtributoValidacao);
    }

    if(!$objProcedimentoDTO->isSetArrObjParticipanteDTO() || count($objProcedimentoDTO->getArrObjParticipanteDTO()) == 0) {
        $objInfraException->adicionarValidacao("Interessados do processo {$objProcedimentoDTO->getStrProtocoloProcedimentoFormatado()} n�o informados.", $strAtributoValidacao);
    }
  }

  private function validarDadosDocumentos(InfraException $objInfraException, $arrDocumentoDTO, $strAtributoValidacao)
    {
    if(!empty($arrDocumentoDTO)) {
        $objDocMapDTO = new PenRelTipoDocMapEnviadoDTO();
        $objGenericoBD = new GenericoBD($this->inicializarObjInfraIBanco());
        $objPenRelHipoteseLegalEnvioRN = new PenRelHipoteseLegalEnvioRN();
        $strMapeamentoEnvioPadrao = $this->objPenParametroRN->getParametro("PEN_ESPECIE_DOCUMENTAL_PADRAO_ENVIO");

      foreach($arrDocumentoDTO as $objDocumentoDTO) {
        $objDocMapDTO->unSetTodos();
        $objDocMapDTO->setNumIdSerie($objDocumentoDTO->getNumIdSerie());

        if($objDocumentoDTO->getStrStaEstadoProtocolo() != ProtocoloRN::$TE_DOCUMENTO_CANCELADO) {
          if(empty($strMapeamentoEnvioPadrao) && $objGenericoBD->contar($objDocMapDTO) == 0) {
            $strDescricao = sprintf(
              'N�o existe mapeamento de envio para %s no documento %s',
              $objDocumentoDTO->getStrNomeSerie(),
              $objDocumentoDTO->getStrProtocoloDocumentoFormatado()
            );

            $objInfraException->adicionarValidacao($strDescricao, $strAtributoValidacao);
          }

            $objHipoteseLegalDTO = new HipoteseLegalDTO();
            $objHipoteseLegalDTO->setNumIdHipoteseLegal($objDocumentoDTO->getNumIdHipoteseLegalProtocolo());
            $objHipoteseLegalDTO->setBolExclusaoLogica(false);
            $objHipoteseLegalDTO->retStrNome();
            $objHipoteseLegalDTO->retStrSinAtivo();
            $objHipoteseLegalRN = new HipoteseLegalRN();
            $dados = $objHipoteseLegalRN->consultar($objHipoteseLegalDTO);

          if ($objDocumentoDTO->getStrStaNivelAcessoLocalProtocolo()!=ProtocoloRN::$NA_PUBLICO) {
            if(!$dados) {
              return;
            }

            if (!empty($objDocumentoDTO->getNumIdHipoteseLegalProtocolo()) && empty($objPenRelHipoteseLegalEnvioRN->getIdHipoteseLegalPEN($objDocumentoDTO->getNumIdHipoteseLegalProtocolo()))) {
              $objInfraException->adicionarValidacao('Hip�tese legal "'.$dados->getStrNome().'" do documento '.$objDocumentoDTO->getStrNomeSerie(). ' ' . $objDocumentoDTO->getStrProtocoloDocumentoFormatado() .' n�o mapeada', $strAtributoValidacao);
            }else{
              if($dados->getStrSinAtivo() == 'N') {
                  $objInfraException->adicionarValidacao('Hip�tese legal "'.$dados->getStrNome().'" do documento '.$objDocumentoDTO->getStrNomeSerie(). ' ' . $objDocumentoDTO->getStrProtocoloDocumentoFormatado() .' est� inativa', $strAtributoValidacao);
              }
            }
          }
        }
      }
    }
  }

  private function validarProcessoAbertoUnidade(InfraException $objInfraException, ProcedimentoDTO $objProcedimentoDTO, $strAtributoValidacao)
    {
      $objAtividadeDTO = new AtividadeDTO();
      $objAtividadeDTO->setDistinct(true);
      $objAtividadeDTO->retStrSiglaUnidade();
      $objAtividadeDTO->setOrdStrSiglaUnidade(InfraDTO::$TIPO_ORDENACAO_ASC);
      $objAtividadeDTO->setDblIdProtocolo($objProcedimentoDTO->getDblIdProcedimento());
      $objAtividadeDTO->setDthConclusao(null);

      $arrObjAtividadeDTO = $this->objAtividadeRN->listarRN0036($objAtividadeDTO);

    if(isset($arrObjAtividadeDTO) && count($arrObjAtividadeDTO) > 1) {
        $strSiglaUnidade = implode(', ', InfraArray::converterArrInfraDTO($arrObjAtividadeDTO, 'SiglaUnidade'));
        $objInfraException->adicionarValidacao("N�o � poss�vel tramitar um processo aberto em mais de uma unidade. ($strSiglaUnidade)", $strAtributoValidacao);
    }
  }

  public function validarProcessoIncluidoBlocoEmAndamento(InfraException $objInfraException, ProcedimentoDTO $objProcedimentoDTO, $strAtributoValidacao)
    {
      $concluido = [ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CIENCIA_RECUSA, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO_AUTOMATICAMENTE, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE];

      $objPenBlocoProcessoDTO = new PenBlocoProcessoDTO();
      $objPenBlocoProcessoDTO->setDblIdProtocolo($objProcedimentoDTO->getDblIdProcedimento());
      $objPenBlocoProcessoDTO->retNumIdAndamento();
      $objPenBlocoProcessoDTO->retStrProtocoloFormatadoProtocolo();
      $objPenBlocoProcessoDTO->retNumIdBloco();

      $objPenBlocoProcessoRN = new PenBlocoProcessoRN();
      $arrPenBlocoProcessoDTO = $objPenBlocoProcessoRN->listar($objPenBlocoProcessoDTO);

    foreach ($arrPenBlocoProcessoDTO as $objPenBlocoProcessoDTO) {
      if (!in_array($objPenBlocoProcessoDTO->getNumIdAndamento(), $concluido)) {
        $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
        $objTramiteEmBlocoDTO->setNumId($objPenBlocoProcessoDTO->getNumIdBloco());
        $objTramiteEmBlocoDTO->retNumOrdem();
        $objTramiteEmBlocoDTO->retStrSiglaUnidade();
        $objTramiteEmBlocoDTO->retStrDescricao();

        $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
        $objTramiteEmBlocoDTO = $objTramiteEmBlocoRN->consultar($objTramiteEmBlocoDTO);

        $mensagem = "Prezado(a) usu�rio(a), o processo {$objPenBlocoProcessoDTO->getStrProtocoloFormatadoProtocolo()} encontra-se inserido no bloco {$objTramiteEmBlocoDTO->getNumOrdem()} - "
        . " {$objTramiteEmBlocoDTO->getStrDescricao()} da unidade {$objTramiteEmBlocoDTO->getStrSiglaUnidade()}."
        . " Para continuar com essa a��o � necess�rio que o processo seja removido do bloco em quest�o.";
        $objInfraException->adicionarValidacao($mensagem, $strAtributoValidacao);
      }
    }
  }

  private function validarNivelAcessoProcesso(InfraException $objInfraException, ProcedimentoDTO $objProcedimentoDTO, $strAtributoValidacao)
    {
    if ($objProcedimentoDTO->getStrStaNivelAcessoLocalProtocolo() == ProtocoloRN::$NA_SIGILOSO) {
        $objInfraException->adicionarValidacao('N�o � poss�vel tramitar um processo com informa��es sigilosas.', $strAtributoValidacao);
    }
  }

    /**
     * Valida existncia da Hiptese legal de Envio
     *
     * @param string $strAtributoValidacao
     */
  private function validarHipoteseLegalEnvio(InfraException $objInfraException, ProcedimentoDTO $objProcedimentoDTO, $strAtributoValidacao)
    {
    if ($objProcedimentoDTO->getStrStaNivelAcessoLocalProtocolo() == ProtocoloRN::$NA_RESTRITO) {
      if (empty($objProcedimentoDTO->getNumIdHipoteseLegalProtocolo())) {
        $objInfraException->adicionarValidacao('N�o � poss�vel tramitar um processo de n�vel restrito sem a hip�tese legal mapeada.', $strAtributoValidacao);
      }

        $objHipoteseLegalDTO = new HipoteseLegalDTO();
        $objHipoteseLegalDTO->setNumIdHipoteseLegal($objProcedimentoDTO->getNumIdHipoteseLegalProtocolo());
        $objHipoteseLegalDTO->setBolExclusaoLogica(false);
        $objHipoteseLegalDTO->retStrNome();
        $objHipoteseLegalDTO->retStrSinAtivo();
        $objHipoteseLegalRN = new HipoteseLegalRN();
        $dados = $objHipoteseLegalRN->consultar($objHipoteseLegalDTO);

        $objPenRelHipoteseLegalEnvioRN = new PenRelHipoteseLegalEnvioRN();
      if(!empty($dados)) {
        if (!empty($objProcedimentoDTO->getNumIdHipoteseLegalProtocolo()) && empty($objPenRelHipoteseLegalEnvioRN->getIdHipoteseLegalPEN($objProcedimentoDTO->getNumIdHipoteseLegalProtocolo()))) {
          $objInfraException->adicionarValidacao('Hip�tese legal "' . $dados->getStrNome() . '" do processo ' . $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado() . ' n�o mapeada', $strAtributoValidacao);
        }else{
          if($dados->getStrSinAtivo() == 'N') {
            $objInfraException->adicionarValidacao('Hip�tese legal "' . $dados->getStrNome() . '" do processo ' . $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado() . ' est� inativa', $strAtributoValidacao);
          }
        }
      }
    }
  }

  private function validarAssinaturas(InfraException $objInfraException, $objProcedimentoDTO, $strAtributoValidacao, $sinProcessoEmBloco = false) {

    $bolAssinaturaCorretas = true;

    $objDocumentoDTO = new DocumentoDTO();
    $objDocumentoDTO->setDblIdProcedimento($objProcedimentoDTO->getDblIdProcedimento());
    $objDocumentoDTO->retDblIdDocumento();
    $objDocumentoDTO->retStrStaDocumento();
    $objDocumentoDTO->retStrStaEstadoProtocolo();

    $objDocumentoRN = new DocumentoRN();
    $arrObjDocumentoDTO = (array)$objDocumentoRN->listarRN0008($objDocumentoDTO);

    if(!empty($arrObjDocumentoDTO)) {

        $objAssinaturaDTO = new AssinaturaDTO();
        $objAssinaturaDTO->setDistinct(true);
        $objAssinaturaDTO->retDblIdDocumento();

      foreach($arrObjDocumentoDTO as $objDocumentoDTO) {
        $objAssinaturaDTO->setDblIdDocumento($objDocumentoDTO->getDblIdDocumento());

        // Se o documento no tem assinatura e n�o foi cancelado ent�o cai na regra de validao
        if($this->objAssinaturaRN->contarRN1324($objAssinaturaDTO) == 0 && $objDocumentoDTO->getStrStaEstadoProtocolo() != ProtocoloRN::$TE_DOCUMENTO_CANCELADO && ($objDocumentoDTO->getStrStaDocumento() == DocumentoRN::$TD_EDITOR_EDOC || $objDocumentoDTO->getStrStaDocumento() == DocumentoRN::$TD_EDITOR_INTERNO) ) {
            $bolAssinaturaCorretas = false;
        }
      }
    }

    if($bolAssinaturaCorretas !== true) {
      if ($sinProcessoEmBloco) {
        $strProtocoloFormatado = $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado();
        $mensagem = "Prezado(a) usu�rio(a), o processo $strProtocoloFormatado possui documentos gerados n�o assinados. "
              . "Dessa forma, n�o foi poss�vel realizar sua inser��o no bloco selecionado.";
        $objInfraException->adicionarValidacao($mensagem, $strAtributoValidacao);
      } else {
        $objInfraException->adicionarValidacao('N�o � poss�vel tramitar um processos com documentos gerados e n�o assinados', $strAtributoValidacao);
      }
    }
  }

  private function validarProcedimentoCompartilhadoSeiFederacao(InfraException $objInfraException, $objProcedimentoDTO, $strAtributoValidacao)
    {
      $bolProcedimentoCompartilhado = false;
      $objProtocoloFederacaoDTO = new ProtocoloFederacaoDTO();
      $objProtocoloFederacaoDTO->setStrProtocoloFormatadoPesquisa($objProcedimentoDTO->getStrProtocoloProcedimentoFormatadoPesquisa());
      $objProtocoloFederacaoDTO->retStrProtocoloFormatado();

      $objProtocoloFederacaoRN = new ProtocoloFederacaoRN();
      $arrObjProtocoloFederacaoDTO = (array) $objProtocoloFederacaoRN->listar($objProtocoloFederacaoDTO);

    if(!empty($arrObjProtocoloFederacaoDTO)) {

      if (count($arrObjProtocoloFederacaoDTO) > 0) {
        $bolProcedimentoCompartilhado = true;
      }
    }

    if($bolProcedimentoCompartilhado) {
        $objInfraException->adicionarValidacao('N�o � poss�vel tramitar o processo pois ele foi compartilhado atrav�s do SEI Federa��o.', $strAtributoValidacao);
    }
  }

    /**
     * Valida se o processo pode ser bloqueado pelo sistema antes do seu envio
     *
     * Regra necess�ria para evitar que regras internas do SEI ou m�dulos possam impedir o bloqueio do processo ap�s o seu envio externo,
     * exce��o esta que pode deixar o processo aberto tanto no remetente como no destinat�rio.
     */
  protected function validarPossibilidadeBloqueioControlado($objProcedimentoDTO)
    {
      // Bloqueia temporariamente o processo para garantir que n�o exista restri��es sobre ele
      $objProcedimentoRN = new ProcedimentoRN();
      $objProcedimentoRN->bloquear([$objProcedimentoDTO]);

      // Desfaz a opera��o anterior para voltar ao estado original do processo
      $objProtocoloDTOBanco = new ProcedimentoDTO();
      $objProtocoloDTOBanco->setDblIdProcedimento($objProcedimentoDTO->getDblIdProcedimento());
      $objProtocoloDTOBanco->retStrStaEstadoProtocolo();
      $objProcedimentoRN->consultarRN0201($objProtocoloDTOBanco);
      $objProcedimentoRN->desbloquear([$objProcedimentoDTO]);
  }

    /**
    * Valida��o das pr�-condi��es necess�rias para que um processo e seus documentos possam ser expedidos para outra entidade
    * @param  InfraException  $objInfraException  Instncia da classe de exceo para registro dos erros
    * @param  ProcedimentoDTO $objProcedimentoDTO Informa��es sobre o procedimento a ser expedido
    * @param string $strAtributoValidacao �ndice para o InfraException separar os processos
    */
  public function validarPreCondicoesExpedirProcedimento(
      InfraException $objInfraException,
      ProcedimentoDTO $objProcedimentoDTO,
      $strAtributoValidacao = null,
      $bolSinProcessamentoEmBloco = false,
      $sinProcessoEmBloco = false
    ) {
    $this->validarDadosProcedimento($objInfraException, $objProcedimentoDTO, $strAtributoValidacao);
    $this->validarDadosDocumentos($objInfraException, $objProcedimentoDTO->getArrObjDocumentoDTO(), $strAtributoValidacao);
    $this->validarDocumentacaoExistende($objInfraException, $objProcedimentoDTO, $strAtributoValidacao, $sinProcessoEmBloco);
    $this->validarProcessoAbertoUnidade($objInfraException, $objProcedimentoDTO, $strAtributoValidacao);
    $this->validarNivelAcessoProcesso($objInfraException, $objProcedimentoDTO, $strAtributoValidacao);
    $this->validarHipoteseLegalEnvio($objInfraException, $objProcedimentoDTO, $strAtributoValidacao);
    $this->validarAssinaturas($objInfraException, $objProcedimentoDTO, $strAtributoValidacao, $sinProcessoEmBloco);

    try{
      if(!$bolSinProcessamentoEmBloco) {
        $this->validarPossibilidadeBloqueio($objProcedimentoDTO);
      }
    }catch(Exception $e){
        $objInfraException->adicionarValidacao($e, $strAtributoValidacao);
    }

    if (InfraUtil::compararVersoes(SEI_VERSAO, ">=", "4.0.0")) {
        $this->validarProcedimentoCompartilhadoSeiFederacao($objInfraException, $objProcedimentoDTO, $strAtributoValidacao);
    }
  }

  public function verificarProcessosAbertoNaUnidade(InfraException $objInfraException, array $arrProtocolosOrigem)
    {
      $naoAbertoUnidadeAtual = false;
    foreach ($arrProtocolosOrigem as $dblIdProcedimento) {
        $objExpedirProcedimentosRN = new ExpedirProcedimentoRN();
        $objProcedimentoDTO = $objExpedirProcedimentosRN->consultarProcedimento($dblIdProcedimento);

        $strProtocoloFormatado = $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado();

        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDistinct(true);
        $objAtividadeDTO->retStrSiglaUnidade();
        $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
        $objAtividadeDTO->setOrdStrSiglaUnidade(InfraDTO::$TIPO_ORDENACAO_ASC);
        $objAtividadeDTO->setDblIdProtocolo($objProcedimentoDTO->getDblIdProcedimento());
        $objAtividadeDTO->setDthConclusao(null);

        $arrObjAtividadeDTO = $this->objAtividadeRN->listarRN0036($objAtividadeDTO);
      if(count($arrObjAtividadeDTO) == 0) {
        if ($naoAbertoUnidadeAtual == false) {
            $naoAbertoUnidadeAtual = true;
            $objInfraException->adicionarValidacao("Verifique o(s) seguinte(s) impedimento(s) para a realiza��o do tr�mite:");
        }
        $objInfraException->adicionarValidacao("O processo {$strProtocoloFormatado} n�o possui andamento aberto nesta unidade;");
      }
    }

    if ($naoAbertoUnidadeAtual == true) {
        $objInfraException->adicionarValidacao("� necess�rio excluir o(s) processo(s) citado(s) do bloco.");
    }
  }

  public function validarProcessoAbertoEmOutraUnidade($objInfraException, $arrProtocolosOrigem, $sinProcessoEmBloco = false)
    {
    foreach ($arrProtocolosOrigem as $dblIdProcedimento) {

        $objExpedirProcedimentosRN = new ExpedirProcedimentoRN();
        $objProcedimentoDTO = $objExpedirProcedimentosRN->consultarProcedimento($dblIdProcedimento);

        $strProtocoloFormatado = $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado();

      if (empty($objProcedimentoDTO)) {
        throw new InfraException('M�dulo do Tramita: Procedimento ' . $strProtocoloFormatado . ' n�o foi localizado', 'Desconhecido');
      }

        $objProcedimentoDTO->setArrObjDocumentoDTO($objExpedirProcedimentosRN->listarDocumentos($dblIdProcedimento));
        $objProcedimentoDTO->setArrObjParticipanteDTO($objExpedirProcedimentosRN->listarInteressados($dblIdProcedimento));
        $objExpedirProcedimentosRN->validarPreCondicoesExpedirProcedimento($objInfraException, $objProcedimentoDTO, null, false, $sinProcessoEmBloco);
    }
  }

  public function trazerTextoSeContemValidacoes($objInfraException)
    {
    if ($objInfraException->contemValidacoes()) {
        $arrErros = [];
        $message = "";
      foreach ($objInfraException->getArrObjInfraValidacao() as $objInfraValidacao) {
        $strAtributo = $objInfraValidacao->getStrAtributo();
        if (!array_key_exists($strAtributo, $arrErros)) {
            $arrErros[$strAtributo] = [];
        }
        $arrErros[$strAtributo][] = mb_convert_encoding($objInfraValidacao->getStrDescricao(), 'UTF-8', 'ISO-8859-1');
        $message .= $objInfraValidacao->getStrDescricao() . "\n";
      }

        return $message;
    }

      return null;
  }

  private function obterNivelSigiloPEN($strNivelSigilo)
    {
    switch ($strNivelSigilo) {
      case ProtocoloRN::$NA_PUBLICO:
          return self::STA_SIGILO_PUBLICO;
      case ProtocoloRN::$NA_RESTRITO:
          return self::STA_SIGILO_RESTRITO;
      case ProtocoloRN::$NA_SIGILOSO:
          return self::STA_SIGILO_SIGILOSO;
    }
  }


  public function listarProcessosApensados($dblIdProcedimentoAtual, $idUnidadeAtual, $strPalavrasPesquisa = '', $numRegistros = 15)
    {

      $arrObjProcessosApensados = [];

    try{
        $objInfraException = new InfraException();
        $idUnidadeAtual = filter_var($idUnidadeAtual, FILTER_SANITIZE_NUMBER_INT);

      if(!$idUnidadeAtual) {
        $objInfraException->adicionarValidacao('Processo inv�lido.');
      }

        $objInfraException->lancarValidacoes();
        //Pesquisar procedimentos que esto abertos na unidade atual
        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDistinct(true);
        $objAtividadeDTO->retDblIdProtocolo();
        $objAtividadeDTO->retStrProtocoloFormatadoProtocolo();
        $objAtividadeDTO->retNumIdUnidade();
        $objAtividadeDTO->retStrDescricaoUnidadeOrigem();
        $objAtividadeDTO->setNumIdUnidade($idUnidadeAtual);
        $objAtividadeDTO->setDblIdProtocolo($dblIdProcedimentoAtual, InfraDTO::$OPER_DIFERENTE);
        $objAtividadeDTO->setDthConclusao(null);
        $objAtividadeDTO->setStrStaEstadoProtocolo(ProtocoloRN::$TE_NORMAL);

        $arrPalavrasPesquisa = explode(' ', (string) $strPalavrasPesquisa);
      for($i=0; $i<count($arrPalavrasPesquisa); $i++) {
          $arrPalavrasPesquisa[$i] = '%'.$arrPalavrasPesquisa[$i].'%';
      }

      if (count($arrPalavrasPesquisa)==1) {
          $objAtividadeDTO->setStrProtocoloFormatadoProtocolo($arrPalavrasPesquisa[0], InfraDTO::$OPER_LIKE);
      }else{
          $objAtividadeDTO->unSetStrProtocoloFormatadoProtocolo();
          $a = array_fill(0, count($arrPalavrasPesquisa), 'ProtocoloFormatadoProtocolo');
          $b = array_fill(0, count($arrPalavrasPesquisa), InfraDTO::$OPER_LIKE);
          $d = array_fill(0, count($arrPalavrasPesquisa)-1, InfraDTO::$OPER_LOGICO_AND);
          $objAtividadeDTO->adicionarCriterio($a, $b, $arrPalavrasPesquisa, $d);
      }

        $arrResultado = [];
        $arrObjAtividadeDTO = $this->objAtividadeRN->listarRN0036($objAtividadeDTO);
        $arrObjAtividadeDTOIndexado = InfraArray::indexarArrInfraDTO($arrObjAtividadeDTO, 'ProtocoloFormatadoProtocolo', true);

      foreach ($arrObjAtividadeDTOIndexado as $value) {

        if(is_array($value) && count($value) == 1) {
            $arrResultado[] = $value[0];
        }
      }

        $arrObjProcessosApensados = array_slice($arrResultado, 0, $numRegistros);

    } catch(Exception $e) {
        throw new InfraException("M�dulo do Tramita: Error Processing Request", $e);
    }

      return $arrObjProcessosApensados;
  }


  public function listarProcessosAbertos($dblIdProcedimentoAtual, $idUnidadeAtual)
    {
      $objAtividadeDTO = new AtividadeDTO();
      $objAtividadeDTO->setDistinct(true);
      $objAtividadeDTO->retDblIdProtocolo();
      $objAtividadeDTO->retNumIdUnidade();
      $objAtividadeDTO->setDblIdProtocolo($dblIdProcedimentoAtual, InfraDTO::$OPER_DIFERENTE);
      $objAtividadeDTO->setDthConclusao(null);
      $objAtividadeDTO->setStrStaEstadoProtocolo(ProtocoloRN::$TE_NORMAL);

      $arrObjAtividadeDTO = $this->objAtividadeRN->listarRN0036($objAtividadeDTO);

      $arrayProcedimentos = [];

    foreach($arrObjAtividadeDTO as $atividade){
        $arrayProcedimentos[$atividade->getDblIdProtocolo()][$atividade->getNumIdUnidade()] = 1;
    }

      return $arrayProcedimentos;
  }

  public function listarProcessosApensadosAvancado(AtividadeDTO $objAtividadeDTO, $dblIdProcedimentoAtual, $idUnidadeAtual, $strPalavrasPesquisa = '', $strDescricaoPesquisa = '', $numRegistros = 15)
    {

      $arrObjProcessosApensados = [];

    try {
        $objInfraException = new InfraException();
        $idUnidadeAtual = filter_var($idUnidadeAtual, FILTER_SANITIZE_NUMBER_INT);

      if(!$idUnidadeAtual) {
        $objInfraException->adicionarValidacao('Processo inv�lido.');
      }

        $objInfraException->lancarValidacoes();
        //Pesquisar procedimentos que esto abertos na unidade atual

        $objAtividadeDTO->setDistinct(true);
        $objAtividadeDTO->retDblIdProtocolo();
        $objAtividadeDTO->retStrProtocoloFormatadoProtocolo();
        $objAtividadeDTO->retNumIdUnidade();
        $objAtividadeDTO->retStrDescricaoUnidadeOrigem();
        $objAtividadeDTO->setNumIdUnidade($idUnidadeAtual);
        $objAtividadeDTO->setDblIdProtocolo($dblIdProcedimentoAtual, InfraDTO::$OPER_DIFERENTE);
        $objAtividadeDTO->setDthConclusao(null);
        $objAtividadeDTO->setStrStaEstadoProtocolo(ProtocoloRN::$TE_NORMAL);

        $arrPalavrasPesquisa = explode(' ', (string) $strPalavrasPesquisa);
      for($i=0; $i<count($arrPalavrasPesquisa); $i++) {
          $arrPalavrasPesquisa[$i] = '%'.$arrPalavrasPesquisa[$i].'%';
      }

      if (count($arrPalavrasPesquisa)==1) {
          $objAtividadeDTO->setStrProtocoloFormatadoProtocolo($arrPalavrasPesquisa[0], InfraDTO::$OPER_LIKE);
      }else{
          $objAtividadeDTO->unSetStrProtocoloFormatadoProtocolo();
          $a = array_fill(0, count($arrPalavrasPesquisa), 'ProtocoloFormatadoProtocolo');
          $b = array_fill(0, count($arrPalavrasPesquisa), InfraDTO::$OPER_LIKE);
          $d = array_fill(0, count($arrPalavrasPesquisa)-1, InfraDTO::$OPER_LOGICO_AND);
          $objAtividadeDTO->adicionarCriterio($a, $b, $arrPalavrasPesquisa, $d);
      }

        $arrResultado = [];
        $arrObjAtividadeDTO = $this->objAtividadeRN->listarRN0036($objAtividadeDTO);
        $arrObjAtividadeDTOIndexado = InfraArray::indexarArrInfraDTO($arrObjAtividadeDTO, 'ProtocoloFormatadoProtocolo', true);

      foreach ($arrObjAtividadeDTOIndexado as $value) {

        if(is_array($value) && count($value) == 1) {
            $arrResultado[] = $value[0];
        }
      }

        $arrObjProcessosApensados = array_slice($arrResultado, 0, $numRegistros);

    } catch(Exception $e) {
        throw new InfraException("M�dulo do Tramita: Error Processing Request", $e);
    }

      return $arrObjProcessosApensados;
  }

    /**
     * M�todo respons�vel por realizar o particionamento do componente digital a ser enviado, de acordo com o parametro (TamanhoBlocoArquivoTransferencia)
     *
     * @author Josinaldo J�nior <josinaldo.junior@basis.com.br>
     * @param  $strCaminhoAnexo
     * @param  $dadosDoComponenteDigital
     * @param  $nrTamanhoArquivoMb
     * @param  $nrTamanhoMegasMaximo
     * @param  $nrTamanhoBytesMaximo
     * @param  $objComponenteDigitalDTO
     * @throws InfraException
     */
  private function particionarComponenteDigitalParaEnvio($strCaminhoAnexo, $dadosDoComponenteDigital, $nrTamanhoArquivoMb, $nrTamanhoMegasMaximo,
        $nrTamanhoBytesMaximo, $bolSinProcessamentoEmBloco = false
    ) {
      //Faz o c�lculo para obter a quantidade de partes que o arquivo ser� particionado, sempre arrendondando para cima
      $qtdPartes = ceil($nrTamanhoArquivoMb / $nrTamanhoMegasMaximo);
      //Abre o arquivo para leitura
      $fp = fopen($strCaminhoAnexo, "rb");

    try {
        $inicio = 0;
        //L� o arquivo em partes para realizar o envio
      for ($i = 1; $i <= $qtdPartes; $i++)
        {
        $parteDoArquivo      = stream_get_contents($fp, $nrTamanhoBytesMaximo, $inicio);
        $tamanhoParteArquivo = strlen($parteDoArquivo);
        $fim = $inicio + $tamanhoParteArquivo;
        try{
            $this->enviarParteDoComponenteDigital($inicio, $fim, $parteDoArquivo, $dadosDoComponenteDigital);
          if(!$bolSinProcessamentoEmBloco) {
            $this->barraProgresso->mover($this->contadorDaBarraDeProgresso);
          }
            $this->contadorDaBarraDeProgresso++;
        }catch (Exception){
            //Armazena as partes que n�o foram enviadas para tentativa de reenvio posteriormente
            $arrPartesComponentesDigitaisNaoEnviadas[] = $inicio;
        }
        $inicio = ($nrTamanhoBytesMaximo * $i);
      }

        //Verifica se existem partes do componente digital que n�o foram enviadas para tentar realizar o envio novamente
      if(isset($arrPartesComponentesDigitaisNaoEnviadas)) {
          $nrTotalPartesNaoEnviadas = count($arrPartesComponentesDigitaisNaoEnviadas);
          $i = 1;
          //Percorre as partes que n<E3>o foram enviadas para reenvia-las
        foreach ($arrPartesComponentesDigitaisNaoEnviadas as $parteComponenteNaoEnviada)
          {
          $conteudoDaParteNaoEnviadaDoArquivo = stream_get_contents($fp, $nrTamanhoBytesMaximo, $parteComponenteNaoEnviada);
          $fim = ($parteComponenteNaoEnviada + strlen($conteudoDaParteNaoEnviadaDoArquivo));
          $this->enviarParteDoComponenteDigital($parteComponenteNaoEnviada, $fim, $conteudoDaParteNaoEnviadaDoArquivo, $dadosDoComponenteDigital);
          $i++;
        }
      }
    } finally {
        fclose($fp);
    }
  }


    /**
     * M�todo responsavel por realizar o envio de uma parte especifica de um componente digital
     *
     * @author Josinaldo J�nior <josinaldo.junior@basis.com.br>
     * @param  $parInicio
     * @param  $parFim
     * @param  $parParteDoArquivo
     * @param  $parDadosDoComponenteDigital
     */
  private function enviarParteDoComponenteDigital($parInicio, $parFim, $parParteDoArquivo, $parDadosDoComponenteDigital)
    {
      //Cria um objeto com as informa<E7><F5>es da parte do componente digital
      $identificacaoDaParte = new stdClass();
      $identificacaoDaParte->inicio = $parInicio;
      $identificacaoDaParte->fim = $parFim;
      $parDadosDoComponenteDigital->identificacaoDaParte = $identificacaoDaParte;
      $parDadosDoComponenteDigital->conteudoDaParteDeComponenteDigital = $parParteDoArquivo;

      $parametros = new stdClass();
      $parametros->dadosDaParteDeComponenteDigital = $parDadosDoComponenteDigital;

      //Envia uma parte de um componente digital para o barramento
      $this->objProcessoEletronicoRN->enviarParteDeComponenteDigital($parametros);
  }


    /**
     * M�todo respons�vel por realizar o envio da parte de um componente digital
     *
     * @author Josinaldo J�nior <josinaldo.junior@basis.com.br>
     * @param  $parametros
     * @return mixed
     * @throws InfraException
     */
  public function enviarParteDeComponenteDigital($parametros)
    {
    try {
        return $this->getObjPenWs()->enviarParteDeComponenteDigital($parametros);
    } catch (\Exception $e) {
        $mensagem = "Falha no envio de parte componente digital";
        $detalhes = InfraString::formatarJavaScript($this->tratarFalhaWebService($e));
        throw new InfraException($mensagem, $e, $detalhes);
    }
  }


    /**
     * M�todo respons�vel por sinalizar o t�rmino do envio das partes de um componente digital
     *
     * @author Josinaldo J�nior <josinaldo.junior@basis.com.br>
     * @param  $parametros
     * @return mixed
     * @throws InfraException
     */
  public function sinalizarTerminoDeEnvioDasPartesDoComponente($parametros)
    {
    try {
        return $this->getObjPenWs()->sinalizarTerminoDeEnvioDasPartesDoComponente($parametros);
    } catch (\Exception $e) {
        $mensagem = "Falha em sinalizar o t�rmino de envio das partes do componente digital";
        $detalhes = InfraString::formatarJavaScript($this->tratarFalhaWebService($e));
        throw new InfraException($mensagem, $e, $detalhes);
    }
  }


    /**
     * Recebe o recibo de tramite do procedimento do barramento
     *
     * @param  int $parNumIdTramite
     * @return bool
     */
  protected function receberReciboDeEnvioControlado($parNumIdTramite)
    {
    if (empty($parNumIdTramite)) {
        return false;
    }

    try {
        $objReciboTramiteEnviadoDTO = new ReciboTramiteEnviadoDTO();
        $objReciboTramiteEnviadoDTO->setNumIdTramite($parNumIdTramite);
        $objGenericoBD = new GenericoBD($this->inicializarObjInfraIBanco());

      if ($objGenericoBD->contar($objReciboTramiteEnviadoDTO) > 0) {
          return false;
      }

        $objReciboEnvio = $this->objProcessoEletronicoRN->receberReciboDeEnvio($parNumIdTramite);
        $objDateTime = new DateTime($objReciboEnvio->reciboDeEnvio->dataDeRecebimentoDoUltimoComponenteDigital);

        $objReciboTramiteDTO = new ReciboTramiteEnviadoDTO();
        $objReciboTramiteDTO->setStrNumeroRegistro($objReciboEnvio->reciboDeEnvio->NRE);
        $objReciboTramiteDTO->setNumIdTramite($objReciboEnvio->reciboDeEnvio->IDT);
        $objReciboTramiteDTO->setDthRecebimento($objDateTime->format('d/m/Y H:i:s'));
        $objReciboTramiteDTO->setStrCadeiaCertificado($objReciboEnvio->cadeiaDoCertificado);
        $objReciboTramiteDTO->setStrHashAssinatura($objReciboEnvio->hashDaAssinatura);
        $objGenericoBD->cadastrar($objReciboTramiteDTO);

      if(isset($objReciboEnvio->reciboDeEnvio->hashDoComponenteDigital)) {
          $objReciboEnvio->reciboDeEnvio->hashDoComponenteDigital = !is_array($objReciboEnvio->reciboDeEnvio->hashDoComponenteDigital) ? [$objReciboEnvio->reciboDeEnvio->hashDoComponenteDigital] : $objReciboEnvio->reciboDeEnvio->hashDoComponenteDigital;
        if($objReciboEnvio->reciboDeEnvio->hashDoComponenteDigital && is_array($objReciboEnvio->reciboDeEnvio->hashDoComponenteDigital)) {
          foreach($objReciboEnvio->reciboDeEnvio->hashDoComponenteDigital as $strHashComponenteDigital){
            $objReciboTramiteHashDTO = new ReciboTramiteHashDTO();
            $objReciboTramiteHashDTO->setStrNumeroRegistro($objReciboEnvio->reciboDeEnvio->NRE);
            $objReciboTramiteHashDTO->setNumIdTramite($objReciboEnvio->reciboDeEnvio->IDT);
            $objReciboTramiteHashDTO->setStrHashComponenteDigital($strHashComponenteDigital);
            $objReciboTramiteHashDTO->setStrTipoRecibo(ProcessoEletronicoRN::$STA_TIPO_RECIBO_ENVIO);

            $objGenericoBD->cadastrar($objReciboTramiteHashDTO);
          }
        }
      }

        return true;

    } catch (\Exception $e) {
        $strMensagem = "Falha na obten��o do recibo de envio de protocolo do tr�mite $parNumIdTramite. $e";
        LogSEI::getInstance()->gravar($strMensagem, InfraLog::$ERRO);
    }
  }

    /**
     * Atualiza os dados do protocolo somente para o modulo PEN
     *
     * @param int $dblIdProtocolo
     */
  private function atualizarPenProtocolo($dblIdProtocolo = 0)
    {

      $objProtocoloDTO = new PenProtocoloDTO();
      $objProtocoloDTO->setDblIdProtocolo($dblIdProtocolo);
      $objProtocoloDTO->retTodos();
      $objProtocoloDTO->getNumMaxRegistrosRetorno(1);

      $objProtocoloBD = new ProtocoloBD($this->getObjInfraIBanco());
      $objProtocoloDTO = $objProtocoloBD->consultar($objProtocoloDTO);

    if(empty($objProtocoloDTO)) {

        $objProtocoloDTO = new PenProtocoloDTO();
        $objProtocoloDTO->setDblIdProtocolo($dblIdProtocolo);
        $objProtocoloDTO->setStrSinObteveRecusa('N');

        $objProtocoloBD->cadastrar($objProtocoloDTO);
    }
    else {

        $objProtocoloDTO->setStrSinObteveRecusa('N');
        $objProtocoloBD->alterar($objProtocoloDTO);
    }
  }

    /**
     * Cancela uma expedio de um Procedimento para outra unidade
     *
     * @param  int $dblIdProcedimento
     * @throws InfraException
     */
  protected function cancelarTramiteControlado($dblIdProcedimento)
    {
      //Busca os dados do protocolo
      $objProtocoloDTO = new ProtocoloDTO();
      $objProtocoloDTO->retStrProtocoloFormatado();
      $objProtocoloDTO->retDblIdProtocolo();
      $objProtocoloDTO->setDblIdProtocolo($dblIdProcedimento);

      $objProtocoloBD = new ProtocoloBD($this->getObjInfraIBanco());
      $objProtocoloDTO = $objProtocoloBD->consultar($objProtocoloDTO);

      $this->cancelarTramiteInterno($objProtocoloDTO);

  }

  protected function cancelarTramiteInternoControlado(ProtocoloDTO $objDtoProtocolo)
    {
      //Obtem o id_rh que representa a unidade no barramento
      $numIdRespositorio = $this->objPenParametroRN->getParametro('PEN_ID_REPOSITORIO_ORIGEM');

      //Obtem os dados da unidade
      $objPenUnidadeDTO = new PenUnidadeDTO();
      $objPenUnidadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
      $objPenUnidadeDTO->retNumIdUnidadeRH();

      $objGenericoBD = new GenericoBD($this->inicializarObjInfraIBanco());
      $objPenUnidadeDTO = $objGenericoBD->consultar($objPenUnidadeDTO);

      $dblIdProcedimento = $objDtoProtocolo->getDblIdProtocolo();

      // Atualizar aqui PenBlocoProcessoDTO PenBlocoProcessoRN
      $objPenBlocoProcessoDTO = new PenBlocoProcessoDTO();
      $objPenBlocoProcessoDTO->retTodos();
      $objPenBlocoProcessoDTO->setDblIdProtocolo($dblIdProcedimento);
      $objPenBlocoProcessoDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
      $objPenBlocoProcessoDTO->setNumIdAndamento([ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_NAO_INICIADO, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_ENVIADOS_REMETENTE], InfraDTO::$OPER_IN);
      $objPenBlocoProcessoDTO->setNumMaxRegistrosRetorno(1);

      $objPenBlocoProcessoRN = new PenBlocoProcessoRN();
      $objPenBlocoProcessoDTO = $objPenBlocoProcessoRN->consultar($objPenBlocoProcessoDTO);
      $cancelarLote=false;

    if(!is_null($objPenBlocoProcessoDTO)) {
        $cancelarLote=true;
    }

    if(!$cancelarLote) {

        $objTramiteDTO = new TramiteDTO();
        $objTramiteDTO->setNumIdProcedimento($objDtoProtocolo->getDblIdProtocolo());
        $objTramiteDTO->setStrStaTipoTramite(ProcessoEletronicoRN::$STA_TIPO_TRAMITE_ENVIO);
        $objTramiteDTO->setOrd('Registro', InfraDTO::$TIPO_ORDENACAO_DESC);
        $objTramiteDTO->setNumMaxRegistrosRetorno(1);
        $objTramiteDTO->retNumIdTramite();

        $objTramiteBD = new TramiteBD($this->getObjInfraIBanco());
        $objTramiteDTO = $objTramiteBD->consultar($objTramiteDTO);

      if(!isset($objTramiteDTO)) {
          throw new InfraException("Tr�mite n�o encontrado para o processo {$objDtoProtocolo->getDblIdProtocolo()}.");
      }

        $tramites = $this->objProcessoEletronicoRN->consultarTramites($objTramiteDTO->getNumIdTramite(), null, $objPenUnidadeDTO->getNumIdUnidadeRH(), null, null, $numIdRespositorio);
        $tramite = $tramites ? $tramites[0] : null;

      if (!$tramite) {
          $numIdTramite = $objTramiteDTO->getNumIdTramite();
          $numIdProtoloco = $objDtoProtocolo->getDblIdProtocolo();
          throw new InfraException("M�dulo do Tramita: Tr�mite $numIdTramite n�o encontrado para o processo $numIdProtoloco.");
      }

        //Verifica se o tr�mite est com o status de iniciado
      if ($tramite->situacaoAtual == ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO) {
          $this->objProcessoEletronicoRN->cancelarTramite($tramite->IDT);
          return true;
      }

        //Busca o processo eletr�nico
        $objDTOFiltro = new ProcessoEletronicoDTO();
        $objDTOFiltro->setDblIdProcedimento($dblIdProcedimento);
        $objDTOFiltro->retStrNumeroRegistro();
        $objDTOFiltro->setNumMaxRegistrosRetorno(1);

        $objBD = new ProcessoEletronicoBD($this->getObjInfraIBanco());
        $objProcessoEletronicoDTO = $objBD->consultar($objDTOFiltro);

      if (empty($objProcessoEletronicoDTO)) {
          throw new InfraException('M�dulo do Tramita: N�o foi encontrado o processo pelo ID ' . $dblIdProcedimento);
      }

        //Armazena a situao atual
        $numSituacaoAtual = $tramite->situacaoAtual;

        //Valida os status
      switch ($numSituacaoAtual) {
        case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_ENVIADO_DESTINATARIO:
            throw new InfraException("M�dulo do Tramita: O sistema destinat�rio j� iniciou o recebimento desse processo, portanto n�o � poss�vel realizar o cancelamento");
        case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE:
            throw new InfraException("M�dulo do Tramita: O sistema destinat�rio j� recebeu esse processo, portanto n�o � possivel realizar o cancelamento");
        case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO:
            throw new InfraException("M�dulo do Tramita: O tr�mite externo para esse processo encontra-se recusado.");
      }

        // Solicita��o de cancelamento de tramite de processo ao TramitaGOV.br
        // Somente solicita cancelamento ao PEN se processo ainda n�o estiver cancelado
      if(!in_array($numSituacaoAtual, [ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO_AUTOMATICAMENTE, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO])) {
          $this->objProcessoEletronicoRN->cancelarTramite($tramite->IDT);
      }
    }

      //Desbloqueia o processo
      ProcessoEletronicoRN::desbloquearProcesso($dblIdProcedimento);

    if(is_object($objPenBlocoProcessoDTO)) {
        // Atualizar aqui PenBlocoProcessoDTO PenBlocoProcessoRN
        $objPenBlocoProcessoDTO->setDblIdProtocolo($dblIdProcedimento);
        $objPenBlocoProcessoDTO->setNumIdAndamento(ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO);

        $objPenBlocoProcessoRN = new PenBlocoProcessoRN();
        $objPenBlocoProcessoRN->alterar($objPenBlocoProcessoDTO);
    }

      // Cancelmento de tramite do processo no MOD_PEN
    if(isset($objTramiteDTO)) {
        $objDTOFiltro = new TramiteDTO();
        $objDTOFiltro->setNumIdTramite($tramite->IDT);
        $objDTOFiltro->setNumMaxRegistrosRetorno(1);
        $objDTOFiltro->setOrdNumIdTramite(InfraDTO::$TIPO_ORDENACAO_DESC);
        $objDTOFiltro->retNumIdTramite();
        $objDTOFiltro->retStrNumeroRegistro();

        $objTramiteBD = new TramiteBD($this->getObjInfraIBanco());
        $objTramiteDTO = $objTramiteBD->consultar($objDTOFiltro);

        $objTramiteDTO->setNumIdAndamento(ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO);
        $objTramiteDTO = $objTramiteBD->alterar($objTramiteDTO);
    }

      //Cria o Objeto que registrar a Atividade de cancelamento
      $objAtividadeDTO = new AtividadeDTO();
      $objAtividadeDTO->setDblIdProtocolo($dblIdProcedimento);
      $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
      $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_CANCELADO));

      //Seta os atributos do tamplate de descrio dessa atividade
      $objAtributoAndamentoDTOHora = new AtributoAndamentoDTO();
      $objAtributoAndamentoDTOHora->setStrNome('DATA_HORA');
      $objAtributoAndamentoDTOHora->setStrIdOrigem(null);
      $objAtributoAndamentoDTOHora->setStrValor(date('d/m/Y H:i'));

      $objAtributoAndamentoDTOUser = new AtributoAndamentoDTO();
      $objAtributoAndamentoDTOUser->setStrNome('USUARIO');
      $objAtributoAndamentoDTOUser->setStrIdOrigem(null);
      $objAtributoAndamentoDTOUser->setStrValor(SessaoSEI::getInstance()->getStrNomeUsuario());

      $objAtividadeDTO->setArrObjAtributoAndamentoDTO([$objAtributoAndamentoDTOHora, $objAtributoAndamentoDTOUser]);

      $objAtividadeRN = new AtividadeRN();
      $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);
  }

    /**
     * Verifica se o processo se encontra em expedio
     *
     * @param  integer $parNumIdProcedimento
     * @return boolean|object
     */
  public function verificarProcessoEmExpedicao($parNumIdProcedimento)
    {
      $objProcedimentoDTO = new ProcedimentoDTO();
      $objProcedimentoDTO->setDblIdProcedimento($parNumIdProcedimento);
      $objProcedimentoDTO->retStrStaEstadoProtocolo();
      $objProcedimentoDTO->retDblIdProcedimento();

      $objProcedimentoRN = new ProcedimentoRN();
      $objProcedimentoDTO = $objProcedimentoRN->consultarRN0201($objProcedimentoDTO);


    if($objProcedimentoDTO && $objProcedimentoDTO->getStrStaEstadoProtocolo() == ProtocoloRN::$TE_PROCEDIMENTO_BLOQUEADO) {

        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDblIdProtocolo($objProcedimentoDTO->getDblIdProcedimento());
        $objAtividadeDTO->setNumIdTarefa(
            [ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO), ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO), ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_CANCELADO), ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_RECUSADO), ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_EXTERNO), ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_ABORTADO)],
            InfraDTO::$OPER_IN
        );
        $objAtividadeDTO->setNumMaxRegistrosRetorno(1);
        $objAtividadeDTO->setOrdDthAbertura(InfraDTO::$TIPO_ORDENACAO_DESC);
        $objAtividadeDTO->retNumIdUnidade();
        $objAtividadeDTO->retNumIdAtividade();
        $objAtividadeDTO->retNumIdTarefa();

        $objAtividadeRN = new AtividadeRN();
        $arrAtividadeDTO = (array) $objAtividadeRN->listarRN0036($objAtividadeDTO);

      if($arrAtividadeDTO) {
        return $arrAtividadeDTO[0];
      }else{
        return false;
      }
    }else{
        return false;
    }
  }


  public function consultaUnidadePk($idUnidade)
    {

      $objUnidadeDTO = new UnidadeDTO();
      $objUnidadeDTO->setNumIdUnidade($idUnidade);
      $objUnidadeDTO->retTodos();

      return $this->objUnidadeRN->consultarRN0125($objUnidadeDTO);
  }

  public function consultaUsuarioPk($idUsuario)
    {

      $objUsuarioDTO = new UsuarioDTO();
      $objUsuarioDTO->setNumIdUsuario($idUsuario);
      $objUsuarioDTO->retTodos();

      return $this->objUsuarioRN->consultarRN0489($objUsuarioDTO);
  }

  public function consultarProtocoloPk($idPrtocedimento)
    {

      $idPrtocedimento = (int)$idPrtocedimento;
      $objProtocoloDTO = new ProtocoloDTO();
      $objProtocoloDTO->setDblIdProtocolo($idPrtocedimento);
      $objProtocoloDTO->retTodos();

      $objProtocoloDTO = $this->objProtocoloRN->consultarRN0186($objProtocoloDTO);
      $objProtocoloDTO->UnidadeGeradora = $this->consultaUnidadePk($objProtocoloDTO->getNumIdUnidadeGeradora());
      $objProtocoloDTO->UsuarioCriador = $this->consultaUsuarioPk($objProtocoloDTO->getNumIdUsuarioGerador());
      $objProtocoloDTO->Documentos = $this->consultaDocumentosProcesso($idPrtocedimento);

      return $objProtocoloDTO;
  }


  public function consultaDocumentosProcesso($idPrtocedimento)
    {
      $documentoDTO = new DocumentoDTO();
      $documentoDTO->setDblIdProcedimento($idPrtocedimento);
      $documentoDTO->retTodos();
      return $this->objDocumentoRN->listarRN0008($documentoDTO);
  }


  private function consultarTramitesAnteriores($parStrNumeroRegistro)
    {
      return isset($parStrNumeroRegistro) ? $this->objProcessoEletronicoRN->consultarTramites(null, $parStrNumeroRegistro) : null;
  }

  private function necessitaCancelamentoTramiteAnterior($parArrTramitesAnteriores)
    {
    if(!empty($parArrTramitesAnteriores) && is_array($parArrTramitesAnteriores)) {
        $objUltimoTramite = $parArrTramitesAnteriores[count($parArrTramitesAnteriores) - 1];
      if($objUltimoTramite->situacaoAtual == ProcessoeletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO) {
        return $objUltimoTramite;
      }
    }
      return null;
  }


    /**
     * Recupera lista de tarjas de assinaturas aplicadas ao documento em seu formato HTML
     *
     * Este m�todo foi baseado na implementa��o presente em AssinaturaRN::montarTarjas.
     * Devido a estrutura interna do SEI, n�o existe uma forma de reaproveitar as regras de montagem de tarjas
     * de forma individual, restando como �ltima alternativa a reprodu��o das regras at� que esta seja encapsulado pelo core do SEI
     *
     * @return array
     */
  protected function listarTarjasHTMLConectado(DocumentoDTO $objDocumentoDTO)
    {
    try {

        $arrResposta = [];

        $objAssinaturaDTO = new AssinaturaDTO();
        $objAssinaturaDTO->retStrNome();
        $objAssinaturaDTO->retNumIdAssinatura();
        $objAssinaturaDTO->retNumIdTarjaAssinatura();
        $objAssinaturaDTO->retStrTratamento();
        $objAssinaturaDTO->retStrStaFormaAutenticacao();
        $objAssinaturaDTO->retStrNumeroSerieCertificado();
        $objAssinaturaDTO->retDthAberturaAtividade();
        $objAssinaturaDTO->setDblIdDocumento($objDocumentoDTO->getDblIdDocumento());
        $objAssinaturaDTO->setOrdNumIdAssinatura(InfraDTO::$TIPO_ORDENACAO_ASC);

        $arrObjAssinaturaDTO = $this->objAssinaturaRN->listarRN1323($objAssinaturaDTO);

      if (count($arrObjAssinaturaDTO)) {
        $objTarjaAssinaturaDTO = new TarjaAssinaturaDTO();
        $objTarjaAssinaturaDTO->setBolExclusaoLogica(false);
        $objTarjaAssinaturaDTO->retNumIdTarjaAssinatura();
        $objTarjaAssinaturaDTO->retStrStaTarjaAssinatura();
        $objTarjaAssinaturaDTO->retStrTexto();
        $objTarjaAssinaturaDTO->retStrLogo();
        $objTarjaAssinaturaDTO->setNumIdTarjaAssinatura(array_unique(InfraArray::converterArrInfraDTO($arrObjAssinaturaDTO, 'IdTarjaAssinatura')), InfraDTO::$OPER_IN);

        $objTarjaAssinaturaRN = new TarjaAssinaturaRN();
        $arrObjTarjaAssinaturaDTO = InfraArray::indexarArrInfraDTO($objTarjaAssinaturaRN->listar($objTarjaAssinaturaDTO), 'IdTarjaAssinatura');

        foreach ($arrObjAssinaturaDTO as $objAssinaturaDTO) {
          if (!isset($arrObjTarjaAssinaturaDTO[$objAssinaturaDTO->getNumIdTarjaAssinatura()])) {
            throw new InfraException('M�dulo do Tramita: Tarja associada com a assinatura "' . $objAssinaturaDTO->getNumIdAssinatura() . '" n�o encontrada.');
          }

            $objTarjaAutenticacaoDTOAplicavel = $arrObjTarjaAssinaturaDTO[$objAssinaturaDTO->getNumIdTarjaAssinatura()];
            $strTarja = $objTarjaAutenticacaoDTOAplicavel->getStrTexto();
            $strTarja = preg_replace("/@logo_assinatura@/s", '<img alt="logotipo" src="data:image/png;base64,' . $objTarjaAutenticacaoDTOAplicavel->getStrLogo() . '" />', (string) $strTarja);
            $strTarja = preg_replace("/@nome_assinante@/s", (string) $objAssinaturaDTO->getStrNome(), $strTarja);
            $strTarja = preg_replace("/@tratamento_assinante@/s", (string) $objAssinaturaDTO->getStrTratamento(), $strTarja);
            $strTarja = preg_replace("/@data_assinatura@/s", substr($objAssinaturaDTO->getDthAberturaAtividade(), 0, 10), $strTarja);
            $strTarja = preg_replace("/@hora_assinatura@/s", substr($objAssinaturaDTO->getDthAberturaAtividade(), 11, 5), $strTarja);
            $strTarja = preg_replace("/@codigo_verificador@/s", $objDocumentoDTO->getStrProtocoloDocumentoFormatado(), $strTarja);
            $strTarja = preg_replace("/@crc_assinatura@/s", $objDocumentoDTO->getStrCrcAssinatura(), $strTarja);
            $strTarja = preg_replace("/@numero_serie_certificado_digital@/s", (string) $objAssinaturaDTO->getStrNumeroSerieCertificado(), $strTarja);
            $strTarja = preg_replace("/@tipo_conferencia@/s", InfraString::transformarCaixaBaixa($objDocumentoDTO->getStrDescricaoTipoConferencia()), $strTarja);
            $arrResposta[] = EditorRN::converterHTML($strTarja);
        }

        $objTarjaAssinaturaDTO = new TarjaAssinaturaDTO();
        $objTarjaAssinaturaDTO->retStrTexto();
        $objTarjaAssinaturaDTO->setStrStaTarjaAssinatura(TarjaAssinaturaRN::$TT_INSTRUCOES_VALIDACAO);

        $objTarjaAssinaturaDTO = $objTarjaAssinaturaRN->consultar($objTarjaAssinaturaDTO);

        if ($objTarjaAssinaturaDTO != null) {
            $strLinkAcessoExterno = '';
          if (str_contains((string) $objTarjaAssinaturaDTO->getStrTexto(), '@link_acesso_externo_processo@')) {
                $objEditorRN = new EditorRN();
                $strLinkAcessoExterno = $objEditorRN->recuperarLinkAcessoExterno($objDocumentoDTO);
          }
            $strTarja = $objTarjaAssinaturaDTO->getStrTexto();
            $strTarja = preg_replace("/@qr_code@/s", '<img align="center" alt="QRCode Assinatura" title="QRCode Assinatura" src="data:image/png;base64,' . $objDocumentoDTO->getStrQrCodeAssinatura() . '" />', (string) $strTarja);
            $strTarja = preg_replace("/@codigo_verificador@/s", $objDocumentoDTO->getStrProtocoloDocumentoFormatado(), $strTarja);
            $strTarja = preg_replace("/@crc_assinatura@/s", $objDocumentoDTO->getStrCrcAssinatura(), $strTarja);
            $strTarja = preg_replace("/@link_acesso_externo_processo@/s", $strLinkAcessoExterno, $strTarja);
            $arrResposta[] = EditorRN::converterHTML($strTarja);
        }
      }

        return $arrResposta;

    } catch (Exception $e) {
        throw new InfraException('M�dulo do Tramita: Erro montando tarja de assinatura.', $e);
    }
  }

  public function setEventoEnvioMetadados(callable $callback)
    {
      $this->fnEventoEnvioMetadados = $callback;
  }

  private function lancarEventoEnvioMetadados($parNumIdTramite)
    {
    if(isset($this->fnEventoEnvioMetadados)) {
        $evento = $this->fnEventoEnvioMetadados;
        $evento($parNumIdTramite);
    }
  }

}