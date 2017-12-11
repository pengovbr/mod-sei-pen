<?php
        
require_once dirname(__FILE__) . '/../../../SEI.php';

class ExpedirProcedimentoRN extends InfraRN {

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

    //TODO: Alterar codificação do SEI para reconhecer esse novo estado do processo
    //Esse estado será utilizado juntamente com os estados da expedição 
  const TE_PROCEDIMENTO_BLOQUEADO = '4';
  const TE_PROCEDIMENTO_EM_PROCESSAMENTO = '5';
  
  //Versão com mudança na API relacionada à obrigatoriedade do carimbo de publicação
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
  private $barraProgresso;
  private $objProcedimentoAndamentoRN;
  private $arrPenMimeTypes = array(
        "application/vnd.oasis.opendocument.text",
        "application/vnd.oasis.opendocument.formula",
        "application/vnd.oasis.opendocument.spreadsheet",
        "application/vnd.oasis.opendocument.presentation",
        "text/xml",
        "text/rtf",
        "text/html",
        "text/plain",
        "text/csv",
        "image/gif",
        "image/jpeg",
        "image/png",
        "image/svg+xml",
        "image/tiff",
        "image/bmp",
        "audio/mp4",
        "audio/midi",
        "audio/ogg",
        "audio/vnd.wave",
        "video/avi",
        "video/mpeg",
        "video/mp4",
        "video/ogg",
        "video/webm"
    );
  
  public function __construct() {
    parent::__construct();

    //TODO: Remover criação de objetos de negócio no construtor da classe para evitar problemas de performance desnecessários
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
    $this->objProcedimentoAndamentoRN = new ProcedimentoAndamentoRN();

    $this->barraProgresso = new InfraBarraProgresso();
    $this->barraProgresso->setNumMin(0);
    $this->barraProgresso->setNumMax(ProcessoEletronicoINT::NEE_EXPEDICAO_ETAPA_CONCLUSAO);
  }

  protected function inicializarObjInfraIBanco() 
  {
    return BancoSEI::getInstance();
  }

  public function listarRepositoriosDeEstruturas() 
  {
    $dadosArray = array();        
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

  public function expedirProcedimentoControlado(ExpedirProcedimentoDTO $objExpedirProcedimentoDTO) {
      
    $numIdTramite = 0;
    
    try {            
            //Valida Permissao
      SessaoSEI::getInstance()->validarAuditarPermissao('pen_procedimento_expedir',__METHOD__, $objExpedirProcedimentoDTO);

      $dblIdProcedimento = $objExpedirProcedimentoDTO->getDblIdProcedimento();

      $this->barraProgresso->exibir();

            //Valida regras de negócio
      $this->barraProgresso->mover(ProcessoEletronicoINT::NEE_EXPEDICAO_ETAPA_VALIDACAO);
      $this->barraProgresso->setStrRotulo(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_VALIDACAO);

      $objInfraException = new InfraException();            

            //Carregamento dos dados de processo e documento para validação e expedição
      $objProcedimentoDTO = $this->consultarProcedimento($dblIdProcedimento);
      $objProcedimentoDTO->setArrObjDocumentoDTO($this->listarDocumentos($dblIdProcedimento));
      $objProcedimentoDTO->setArrObjParticipanteDTO($this->listarInteressados($dblIdProcedimento));

      $this->validarPreCondicoesExpedirProcedimento($objInfraException, $objProcedimentoDTO);
      $this->validarParametrosExpedicao($objInfraException, $objExpedirProcedimentoDTO);

            //Apresentação da mensagens de validação na janela da barra de progresso
      if($objInfraException->contemValidacoes()){
        $this->barraProgresso->mover(0);
        $this->barraProgresso->setStrRotulo('Erro durante validação dos dados do processo.');
        $objInfraException->lancarValidacoes();
      }            

      $this->barraProgresso->mover(ProcessoEletronicoINT::NEE_EXPEDICAO_ETAPA_PROCEDIMENTO);
      $this->barraProgresso->setStrRotulo(sprintf(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_PROCEDIMENTO, $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado()));

            //Construção dos cabecalho para envio do processo
      $objCabecalho = $this->construirCabecalho($objExpedirProcedimentoDTO);
      
            //Construção do processo para envio            
      $objProcesso = $this->construirProcesso($dblIdProcedimento, $objExpedirProcedimentoDTO->getArrIdProcessoApensado());
      
      try {
        $param = new stdClass();
        $param->novoTramiteDeProcesso = new stdClass();
        $param->novoTramiteDeProcesso->cabecalho = $objCabecalho;
        $param->novoTramiteDeProcesso->processo = $objProcesso;
        $novoTramite = $this->objProcessoEletronicoRN->enviarProcesso($param);  
        $numIdTramite = $novoTramite->dadosTramiteDeProcessoCriado->IDT;
        
      } catch (\Exception $e) {
        throw new InfraException("Error Processing Request", $e);
      }    


      
        $this->atualizarPenProtocolo($dblIdProcedimento);
 
      if (isset($novoTramite->dadosTramiteDeProcessoCriado)) {
        
        $objTramite = $novoTramite->dadosTramiteDeProcessoCriado;
          
        $this->objProcedimentoAndamentoRN->setOpts($dblIdProcedimento, $objTramite->IDT, ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO));
        try {
      
        $this->objProcedimentoAndamentoRN->cadastrar('Envio do metadados do processo', 'S');
        
        $idAtividadeExpedicao = $this->bloquearProcedimentoExpedicao($objExpedirProcedimentoDTO, $objProcesso->idProcedimentoSEI);
        //$this->registrarAndamentoExpedicaoProcesso($objExpedirProcedimentoDTO, $objProcesso);

        
        $this->objProcessoEletronicoRN->cadastrarTramiteDeProcesso(
          $objProcesso->idProcedimentoSEI, 
          $objTramite->NRE, 
          $objTramite->IDT, 
          $objTramite->dataHoraDeRegistroDoTramite, $objProcesso, 
          $objTramite->ticketParaEnvioDeComponentesDigitais, 
          $objTramite->componentesDigitaisSolicitados);


        $this->objProcessoEletronicoRN->cadastrarTramitePendente($objTramite->IDT, $idAtividadeExpedicao);
                //error_log('TRAMITE: ' . print_r($objTramite, true));
                //error_log('before enviarComponentesDigitais');

                //TODO: Erro no BARRAMENTO: Processo não pode ser enviado se possuir 2 documentos iguais(mesmo hash)
                //TODO: Melhoria no barramento de serviços. O método solicitar metadados não deixa claro quais os componentes digitais que 
                //precisam ser baixados. No cenário de retorno de um processo existente, a única forma é consultar o status do trâmite para
                //saber quais precisam ser baixados. O processo poderia ser mais otimizado se o retorno nos metadados já informasse quais os 
                //componentes precisam ser baixados, semelhante ao que ocorre no enviarProcesso onde o barramento informa quais os componentes
                //que precisam ser enviados
        $this->enviarComponentesDigitais($objTramite->NRE, $objTramite->IDT, $objProcesso->protocolo);                
                //error_log('after enviarComponentesDigitais');
                //$strNumeroRegistro, $numIdTramite, $strProtocolo
                //error_log('==========================>>>>' . print_r($objTramite, true));

                //TODO: Ao enviar o processo e seus documentos, necessário bloquear os documentos para alteração
                //pois eles já foram visualizados
                //$objDocumentoRN = new DocumentoRN();
                //$objDocumentoRN->bloquearConsultado($objDocumentoRN->consultarRN0005($objDocumentoDTO));


                //TODO: Implementar o registro de auditoria, armazenando os metadados xml enviados para o PEN

                //return ;

                # $this->enviarDocProdimentoTramite();              
                // $this->gravarAuditoria(__METHOD__ , $objExpedirProcedimentoDTO->getDblIdProcedimento());
                //$this->bloquearProcesso($objExpedirProcedimentoDTO->getDblIdProcedimento());
                #$this->enviarDocProdimentoTramite();
                //return array('mensagem' => 'Processo em expedição!', 'retorno' => 1);                

                //TODO: Alterar atualização para somente apresentar ao final de todo o trâmite
        $this->barraProgresso->mover(ProcessoEletronicoINT::NEE_EXPEDICAO_ETAPA_CONCLUSAO);
        $this->barraProgresso->setStrRotulo(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_CONCLUSAO);
        
            // @join_tec US008.06 (#23092)
            $this->objProcedimentoAndamentoRN->cadastrar('Concluído envio dos componentes do processo', 'S');
        
            
            $this->receberReciboDeEnvio($objTramite->IDT);
        } 
        catch (\Exception $e) {
            //@TODO: Melhorar essa estrutura
            //Realiza o desbloqueio do processo
            try{
                $this->desbloquearProcessoExpedicao($objProcesso->idProcedimentoSEI);
            } catch (InfraException $ex) { }  
            
            //@TODO: Melhorar essa estrutura
            //Realiza o cancelamento do tramite
            try{
                if($numIdTramite != 0){
                    $this->objProcessoEletronicoRN->cancelarTramite($numIdTramite);
                }
            } catch (InfraException $ex) { }  
             
             $this->registrarAndamentoExpedicaoAbortada($objProcesso->idProcedimentoSEI);
             
             // @join_tec US008.06 (#23092)
             $this->objProcedimentoAndamentoRN->cadastrar('Concluído envio dos componentes do processo', 'N');
             throw $e;
         }
      }
      
    } catch (\Exception $e) {
      throw new InfraException('Falha de comunicação com o Barramento de Serviços. Por favor, tente novamente mais tarde.', $e);
    }
  }

  private function registrarAndamentoExpedicaoProcesso($objExpedirProcedimentoDTO, $objProcesso)
  {
        //Processo expedido para a entidade @ENTIDADE_DESTINO@ - @REPOSITORIO_ESTRUTURA@ (@PROCESSO@, @UNIDADE@, @USUARIO@)
        //TODO: Atribuir atributos necessários para formação da mensagem do andamento
        //TODO: Especificar quais andamentos serão registrados
    $arrObjAtributoAndamentoDTO = array();
    
    $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
    $objAtributoAndamentoDTO->setStrNome('REPOSITORIO_DESTINO');
    $objAtributoAndamentoDTO->setStrValor($objExpedirProcedimentoDTO->getStrRepositorioDestino());
    $objAtributoAndamentoDTO->setStrIdOrigem($objExpedirProcedimentoDTO->getNumIdRepositorioOrigem());
    $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

    $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
    $objAtributoAndamentoDTO->setStrNome('UNIDADE_DESTINO');
    $objAtributoAndamentoDTO->setStrValor($objExpedirProcedimentoDTO->getStrUnidadeDestino());
    $objAtributoAndamentoDTO->setStrIdOrigem($objExpedirProcedimentoDTO->getNumIdUnidadeDestino());
    $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

    $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
    $objAtributoAndamentoDTO->setStrNome('PROCESSO');
    $objAtributoAndamentoDTO->setStrValor($objProcesso->protocolo);
    $objAtributoAndamentoDTO->setStrIdOrigem($objProcesso->idProcedimentoSEI);
    $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

    $objUnidadeDTO = new UnidadeDTO();
    $objUnidadeDTO->retStrSigla();
    $objUnidadeDTO->retStrDescricao();
    $objUnidadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());        
    $objUnidadeDTO = $this->objUnidadeRN->consultarRN0125($objUnidadeDTO);

    $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
    $objAtributoAndamentoDTO->setStrNome('UNIDADE');
    $objAtributoAndamentoDTO->setStrValor($objUnidadeDTO->getStrSigla().'¥'.$objUnidadeDTO->getStrDescricao());
    $objAtributoAndamentoDTO->setStrIdOrigem(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
    $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

        //TODO: Avaliar qual o usuário que deveria ser registrado no atributo andamento abaixo
    $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
    $objAtributoAndamentoDTO->setStrNome('USUARIO');
    $objAtributoAndamentoDTO->setStrValor(SessaoSEI::getInstance()->getStrSiglaUsuario() . '¥' . SessaoSEI::getInstance()->getStrNomeUsuario());
    $objAtributoAndamentoDTO->setStrIdOrigem(SessaoSEI::getInstance()->getNumIdUsuario());
    $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

    $objAtividadeDTO = new AtividadeDTO();
    $objAtividadeDTO->setDblIdProtocolo($objProcesso->idProcedimentoSEI);
    $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
    $objAtividadeDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
    $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO);
    $objAtividadeDTO->setArrObjAtributoAndamentoDTO($arrObjAtributoAndamentoDTO);

    $objAtividadeRN = new AtividadeRN();
    $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);

        //Registra andamento para processos apensados que foram expedidos
    if(isset($objProcesso->processoApensado) && is_array($objProcesso->processoApensado)) {
      foreach($objProcesso->processoApensado as $objProcessoApensado) {
        $this->registrarAndamentoExpedicaoProcesso($objExpedirProcedimentoDTO, $objProcessoApensado);
      }
    }


  }

  private function construirCabecalho(ExpedirProcedimentoDTO $objExpedirProcedimentoDTO) 
  {
    if(!isset($objExpedirProcedimentoDTO)){
      throw new InfraException('Parâmetro $objExpedirProcedimentoDTO não informado.');
    }

    //Obtenção do número de registro eletrônico do processo
    $strNumeroRegistro = null;
    $objProcessoEletronicoBD = new ProcessoEletronicoBD($this->getObjInfraIBanco());
    $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
    $objProcessoEletronicoDTO->setDblIdProcedimento($objExpedirProcedimentoDTO->getDblIdProcedimento());
        //TODO: Tratar situação de quando é localizado dois registros para o mesmo processo
    $objProcessoEletronicoDTO->setNumMaxRegistrosRetorno(1);
    $objProcessoEletronicoDTO->setOrd('IdProcedimento', InfraDTO::$TIPO_ORDENACAO_DESC);
    $objProcessoEletronicoDTO->retStrNumeroRegistro();

    $objProcessoEletronicoDTO = $objProcessoEletronicoBD->consultar($objProcessoEletronicoDTO);        
    if(isset($objProcessoEletronicoDTO)) {
      $strNumeroRegistro = $objProcessoEletronicoDTO->getStrNumeroRegistro();
    }
    
    // Consultar se processo eletrônico existe no PEN algum trâmite CANCELADO, caso
    // sim deve ser gerada uma nova NRE, pois a atual será recusada pelo PEN quando
    // for enviado
   /* if(!InfraString::isBolVazia($strNumeroRegistro)) {      
        $arrObjTramite = $this->objProcessoEletronicoRN->consultarTramites(null, $strNumeroRegistro);
        if(!empty($arrObjTramite) && is_array($arrObjTramite) && count($arrObjTramite) === 1) {
            $objTramite = current($arrObjTramite);
            if($objTramite->situacaoAtual == ProcessoeletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO || $objTramite->situacaoAtual == ProcessoeletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO){
                $strNumeroRegistro = null;
            }  
        }
    }    */
    

    return $this->objProcessoEletronicoRN->construirCabecalho(
      //TODO: Desabilitado consulta do NRE para questões de teste
      $strNumeroRegistro, 
      $objExpedirProcedimentoDTO->getNumIdRepositorioOrigem(),
      $objExpedirProcedimentoDTO->getNumIdUnidadeOrigem(),
      $objExpedirProcedimentoDTO->getNumIdRepositorioDestino(),
      $objExpedirProcedimentoDTO->getNumIdUnidadeDestino(),
      $objExpedirProcedimentoDTO->getBolSinUrgente(),
      $objExpedirProcedimentoDTO->getNumIdMotivoUrgencia(),                
      false /*obrigarEnvioDeTodosOsComponentesDigitais*/);                        

  }

  private function construirProcesso($dblIdProcedimento, $arrIdProcessoApensado = null)
  {
    if(!isset($dblIdProcedimento)){
      throw new InfraException('Parâmetro $dblIdProcedimento não informado.');
    }
    
        //TODO: Passar dados do ProcedimentoDTO via parâmetro já carregado anteriormente
    $objProcedimentoDTO = $this->consultarProcedimento($dblIdProcedimento);
    $objPenRelHipoteseLegalRN = new PenRelHipoteseLegalEnvioRN();
    
    $objProcesso = new stdClass();
    $objProcesso->protocolo = utf8_encode($objProcedimentoDTO->getStrProtocoloProcedimentoFormatado());
    $objProcesso->nivelDeSigilo = $this->obterNivelSigiloPEN($objProcedimentoDTO->getStrStaNivelAcessoLocalProtocolo());
    $objProcesso->processoDeNegocio  = utf8_encode($objProcedimentoDTO->getStrNomeTipoProcedimento());
    $objProcesso->descricao          = utf8_encode($objProcedimentoDTO->getStrDescricaoProtocolo());
    $objProcesso->dataHoraDeProducao = $this->objProcessoEletronicoRN->converterDataWebService($objProcedimentoDTO->getDtaGeracaoProtocolo());
   
    if($objProcedimentoDTO->getStrStaNivelAcessoLocalProtocolo() == ProtocoloRN::$NA_RESTRITO){
        $objProcesso->hipoteseLegal = new stdClass();
        $objProcesso->hipoteseLegal->identificacao = $objPenRelHipoteseLegalRN->getIdHipoteseLegalPEN($objProcedimentoDTO->getNumIdHipoteseLegalProtocolo());
    //    $objProcesso->hipoteseLegal->nome = 'Nomee';
      //   $objProcesso->hipoteseLegal->baseLegal = 'Base Legall';

    }
  
    $this->atribuirProdutorProcesso($objProcesso, 
      $objProcedimentoDTO->getNumIdUsuarioGeradorProtocolo(), 
      $objProcedimentoDTO->getNumIdUnidadeGeradoraProtocolo());        
    
    $this->atribuirDataHoraDeRegistro($objProcesso, $objProcedimentoDTO->getDblIdProcedimento());

    $this->atribuirDocumentos($objProcesso, $dblIdProcedimento);     
    $this->atribuirDadosInteressados($objProcesso, $dblIdProcedimento);
    $this->adicionarProcessosApensados($objProcesso, $arrIdProcessoApensado);

        //TODO:Adicionar demais informações do processo
        //<protocoloAnterior>
        
   // $this->atribuirDadosHistorico($objProcesso, $dblIdProcedimento);

    $objProcesso->idProcedimentoSEI = $dblIdProcedimento;
    return $objProcesso;
  }

    //TODO: Implementar mapeamento de atividades que serão enviadas para barramento (semelhante Protocolo Integrado)
  private function atribuirDadosHistorico($objProcesso, $dblIdProcedimento) 
  {
    $objProcedimentoHistoricoDTO = new ProcedimentoHistoricoDTO();
    $objProcedimentoHistoricoDTO->setDblIdProcedimento($dblIdProcedimento);
    $objProcedimentoHistoricoDTO->setStrStaHistorico(ProcedimentoRN::$TH_TOTAL);

    $objProcedimentoRN = new ProcedimentoRN();
    $objProcedimentoDTO = $objProcedimentoRN->consultarHistoricoRN1025($objProcedimentoHistoricoDTO);
    $arrObjAtividadeDTO = $objProcedimentoDTO->getArrObjAtividadeDTO();

    if($arrObjAtividadeDTO == null || count($arrObjAtividadeDTO) == 0) {
      throw new InfraException("Não foi possível obter andamentos do processo {$objProcesso->protocolo}");
    }

    $arrObjOperacao = array();
    foreach ($arrObjAtividadeDTO as $objAtividadeDTO) {

            //TODO: Avaliar necessidade de repassar dados da pessoa que realizou a operação
      $objOperacao = new stdClass();

            //TODO: Adicionar demais informações da pessoa e sua unidade
      $objOperacao->pessoa = new stdClass();
      $objOperacao->pessoa->nome = utf8_encode($objAtividadeDTO->getStrNomeUsuarioOrigem());
      $objOperacao->codigo = $this->objProcessoEletronicoRN->obterCodigoOperacaoPENMapeado($objAtividadeDTO->getNumIdTarefa());
      $objOperacao->dataHora = $this->objProcessoEletronicoRN->converterDataWebService($objAtividadeDTO->getDthAbertura());
      $strComplemento = strip_tags($objAtividadeDTO->getStrNomeTarefa());
      $objOperacao->complemento = utf8_encode($strComplemento);

      $arrObjOperacao[] = $objOperacao;
    }

    $objProcesso->historico = new stdClass();
    $objProcesso->historico->operacao = $arrObjOperacao;
  }

  /**
   * Muda o estado de um procedimento
   * 
   * @param object $objProcesso
   * @param string $strStaEstado
   * @throws InfraException
   * @return null
   */
    public static function mudarEstadoProcedimento($objProcesso, $strStaEstado){
       
        if(!isset($objProcesso)) {
            throw new InfraException('Parâmetro $objProcesso não informado.');
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
            $objAtributoAndamentoDTO->setStrValor('Processo esta em processamento devido sua expedição para outra entidade.');
            $objAtividadeDTO->setArrObjAtributoAndamentoDTO(array($objAtributoAndamentoDTO));

            $objAtividadeRN = new AtividadeRN();
            $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);
        }
        catch(Exception $e){
            throw new InfraException('Erro ao mudar o estado do processo.',$e);
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
     * @param object $objProcesso
     * @param string $strStaEstado
     * @throws InfraException
     * @return null
    */
    public static function mudarEstadoProcedimentoNormal($objProcesso, $strStaEstado){

        //Muda o estado do Protocolo para normal
        $objProtocoloDTO = new ProtocoloDTO();    	
        $objProtocoloDTO->setStrStaEstado($strStaEstado);
        $objProtocoloDTO->setDblIdProtocolo($objProcesso->idProcedimentoSEI);    	

        $objProtocoloRN = new ProtocoloRN();
        $objProtocoloRN->alterarRN0203($objProtocoloDTO);

    }
    
        
    public function bloquearProcedimentoExpedicao($objExpedirProcedimentoDTO, $numIdProcedimento) {

        //Instancia a API do SEI para bloquei do processo
        $objEntradaBloquearProcessoAPI = new EntradaBloquearProcessoAPI();
        $objEntradaBloquearProcessoAPI->setIdProcedimento($numIdProcedimento);

        //Realiza o bloquei do processo
        $objSeiRN = new SeiRN();
        $objSeiRN->bloquearProcesso($objEntradaBloquearProcessoAPI);


        $arrObjAtributoAndamentoDTO = array();

        //Seta o repositório de destino para constar no histórico
        $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTO->setStrNome('REPOSITORIO_DESTINO');
        $objAtributoAndamentoDTO->setStrValor($objExpedirProcedimentoDTO->getStrRepositorioDestino());
        $objAtributoAndamentoDTO->setStrIdOrigem($objExpedirProcedimentoDTO->getNumIdRepositorioOrigem());
        $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

        //Compõe o atributo que irá compor a estrutura
        $objEstrutura = $this->objProcessoEletronicoRN->consultarEstrutura(
                $objExpedirProcedimentoDTO->getNumIdRepositorioDestino(), $objExpedirProcedimentoDTO->getNumIdUnidadeDestino(), true
        );

        if (isset($objEstrutura->hierarquia)) {

            $arrObjNivel = $objEstrutura->hierarquia->nivel;

            $nome = "";
            $siglasUnidades = array();
            $siglasUnidades[] = $objEstrutura->sigla;

            foreach ($arrObjNivel as $key => $objNivel) {
                $siglasUnidades[] = $objNivel->sigla;
            }

            for ($i = 1; $i <= 3; $i++) {
                if (isset($siglasUnidades[count($siglasUnidades) - 1])) {
                    unset($siglasUnidades[count($siglasUnidades) - 1]);
                }
            }

            foreach ($siglasUnidades as $key => $nomeUnidade) {
                if ($key == (count($siglasUnidades) - 1)) {
                    $nome .= $nomeUnidade . " ";
                } else {
                    $nome .= $nomeUnidade . " / ";
                }
            }

            $objNivel = current($arrObjNivel);

            $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
            $objAtributoAndamentoDTO->setStrNome('UNIDADE_DESTINO_HIRARQUIA');
            $objAtributoAndamentoDTO->setStrValor($nome);
            $objAtributoAndamentoDTO->setStrIdOrigem($objNivel->numeroDeIdentificacaoDaEstrutura);
            $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;
        }

        //Seta a unidade de destino
        $arrUnidadeDestino = preg_split('/\s?\/\s?/', $objExpedirProcedimentoDTO->getStrUnidadeDestino());
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
        
        //Registra o andamento no histórico e 
        $objAtividadeRN = new AtividadeRN();
        $atividade = $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);

        return $atividade->getNumIdAtividade();
    }

    public function desbloquearProcessoExpedicao($numIdProcedimento){
        
        //Intancia o objeto de desbloqueio da API do SEI
        $objEntradaDesbloquearProcessoAPI = new EntradaDesbloquearProcessoAPI();
        $objEntradaDesbloquearProcessoAPI->setIdProcedimento($numIdProcedimento);

        //Solicita o Desbloqueio do Processo
        $objSeiRN = new SeiRN();
        $objSeiRN->desbloquearProcesso($objEntradaDesbloquearProcessoAPI);

    }
    
    public function registrarAndamentoExpedicaoAbortada($dblIdProtocolo) {
        
        //Seta todos os atributos do histórico de aborto da expedição
        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDblIdProtocolo($dblIdProtocolo);
        $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
        $objAtividadeDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
        $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_ABORTADO));
        $objAtividadeDTO->setArrObjAtributoAndamentoDTO(array());

        //Gera o andamento de expedição abortada
        $objAtividadeRN = new AtividadeRN();
        $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);
    }

    public static function receberRecusaProcedimento($motivo, $unidade_destino, $numUnidadeDestino = null, $idProtocolo)
    {

        try{
        //Muda o status do protocolo para "Normal"
       
            
        $arrObjAtributoAndamentoDTO = array();

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
    
  private function bloquearProcedimento($objProcesso)
  {
    if(!isset($objProcesso)) {
      throw new InfraException('Parâmetro $objProcesso não informado.');
    }

        //TODO: Solicitar ao TRF4 um meio de bloquear o processo, indicando que ele encontra-se em 
        //expedição e possui tratamento diferenciado

    $objRelProtocoloProtocoloDTO = new RelProtocoloProtocoloDTO();     
        //$objRelProtocoloProtocoloDTO->setDblIdProtocolo1($dblIdProcedimentoApensado);
    $objRelProtocoloProtocoloDTO->setDblIdProtocolo1(null);
    $objRelProtocoloProtocoloDTO->setDblIdProtocolo2($objProcesso->idProcedimentoSEI);
    $objRelProtocoloProtocoloDTO->setStrMotivo("Processo sobrestado devido sua expedição para outra entidade.");        
    $this->objProcedimentoRN->sobrestarRN1014(array($objRelProtocoloProtocoloDTO));

    if(isset($objProcesso->processoApensado) && is_array($objProcesso->processoApensado)) {
      foreach($objProcesso->processoApensado as $objProcessoApensado) {
        $this->bloquearProcedimento($objProcessoApensado);
      }
    }

  }

  private function atribuirDataHoraDeRegistro($objContexto, $dblIdProcedimento, $dblIdDocumento = null)
  {
        //Validar parâmetro $objContexto
    if(!isset($objContexto)) {
      throw new InfraException('Parâmetro $objContexto não informado.');
    }

        //Validar parâmetro $dbIdProcedimento
    if(!isset($dblIdProcedimento)) {
      throw new InfraException('Parâmetro $dbIdProcedimento não informado.');
    }

    $objProcedimentoHistoricoDTO = new ProcedimentoHistoricoDTO();
    $objProcedimentoHistoricoDTO->setDblIdProcedimento($dblIdProcedimento);
    $objProcedimentoHistoricoDTO->setStrStaHistorico(ProcedimentoRN::$TH_PERSONALIZADO);
    $objProcedimentoHistoricoDTO->adicionarCriterio(array('IdTarefa','IdTarefa'), array(InfraDTO::$OPER_IGUAL,InfraDTO::$OPER_IGUAL), array(TarefaRN::$TI_GERACAO_PROCEDIMENTO, ProcessoeletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO), InfraDTO::$OPER_LOGICO_OR);
    $objProcedimentoHistoricoDTO->setStrSinGerarLinksHistorico('N');            
    $objProcedimentoHistoricoDTO->setNumMaxRegistrosRetorno(1);
    $objProcedimentoHistoricoDTO->setOrdNumIdTarefa(InfraDTO::$TIPO_ORDENACAO_ASC);

    if(isset($dblIdDocumento)){
      $objProcedimentoHistoricoDTO->setDblIdDocumento($dblIdDocumento);
      $objProcedimentoHistoricoDTO->setNumIdTarefa(array(TarefaRN::$TI_GERACAO_DOCUMENTO, TarefaRN::$TI_RECEBIMENTO_DOCUMENTO), InfraDTO::$OPER_IN);
    }

    $objProcedimentoDTOHistorico = $this->objProcedimentoRN->consultarHistoricoRN1025($objProcedimentoHistoricoDTO);
    $arrObjAtividadeDTOHistorico = $objProcedimentoDTOHistorico->getArrObjAtividadeDTO();

    if(isset($arrObjAtividadeDTOHistorico) && count($arrObjAtividadeDTOHistorico) == 1){
      $objContexto->dataHoraDeRegistro = $this->objProcessoEletronicoRN->converterDataWebService($arrObjAtividadeDTOHistorico[0]->getDthAbertura());
    }
  }

  private function atribuirProdutorProcesso($objProcesso, $dblIdProcedimento, $numIdUnidadeGeradora)
  {
    if(!isset($objProcesso)){
      throw new InfraException('Parâmetro $objProcesso não informado.');
    }

    $objProcesso->produtor = new stdClass();

    $objUsuarioProdutor = $this->consultarUsuario($dblIdProcedimento);
    if(isset($objUsuarioProdutor)) {
            //Dados do produtor do processo            
      $objProcesso->produtor->nome = utf8_encode($objUsuarioProdutor->getStrNome());
            //TODO: Obter tipo de pessoa física dos contatos do SEI
      $objProcesso->produtor->numeroDeIdentificacao = $objUsuarioProdutor->getDblCpfContato();
      $objProcesso->produtor->tipo = self::STA_TIPO_PESSOA_FISICA;
            //TODO: Informar dados da estrutura organizacional (estruturaOrganizacional)

    }

    $objUnidadeGeradora = $this->consultarUnidade($dblIdProcedimento);
    if(isset($objUnidadeGeradora)){
      $objProcesso->produtor->unidade = new stdClass();
      $objProcesso->produtor->unidade->nome = utf8_encode($objUnidadeGeradora->getStrDescricao());
      $objProcesso->produtor->unidade->tipo = self::STA_TIPO_PESSOA_ORGAOPUBLICO;
            //TODO: Informar dados da estrutura organizacional (estruturaOrganizacional)
    }
  }

  private function atribuirDadosInteressados($objProcesso, $dblIdProcedimento)
  {
    if(!isset($objProcesso)){
      throw new InfraException('Parâmetro $objProcesso não informado.');
    }

    $arrParticipantesDTO = $this->listarInteressados($dblIdProcedimento);

    if(isset($arrParticipantesDTO) && count($arrParticipantesDTO) > 0) 
    {
      $objProcesso->interessado = array();

      foreach ($arrParticipantesDTO as $participanteDTO) {
        $interessado = new stdClass();
        $interessado->nome = utf8_encode($participanteDTO->getStrNomeContato());
        $objProcesso->interessado[] = $interessado;
      }       
    }
  }


  private function atribuirDocumentos($objProcesso, $dblIdProcedimento)
  {
    if(!isset($objProcesso)) {
      throw new InfraException('Parâmetro $objProcesso não informado.');
    }

        //TODO: Passar dados do ProcedimentoDTO via parâmetro já carregado anteriormente
    $arrDocumentosDTO = $this->listarDocumentos($dblIdProcedimento);

    if(!isset($arrDocumentosDTO)) {
      throw new InfraException('Documentos não encontrados.');
    }

    $ordemDocumento = 1;
    $objProcesso->documento = array();

    

    foreach ($arrDocumentosDTO as $documentoDTO) {

            //$protocoloDocumentoDTO = $this->consultarProtocoloDocumento($documeto->getDblIdProcedimento());                        

      $documento = new stdClass();
      $objPenRelHipoteseLegalRN = new PenRelHipoteseLegalEnvioRN();
            //TODO: Atribuir das informações abaixo ao documento
            //<protocoloDoDocumentoAnexado>123</protocoloDoDocumentoAnexado>
            //<protocoloDoProcessoAnexado>456</protocoloDoProcessoAnexado>            
            //Retirado

            //Considera o número/nome do documento externo para descrição do documento
      if($documentoDTO->getStrStaProtocoloProtocolo() == ProtocoloRN::$TP_DOCUMENTO_RECEBIDO && $documentoDTO->getStrNumero() != null) {
        $strDescricaoDocumento = $documentoDTO->getStrNumero();
        
      }else{
        $strDescricaoDocumento = "***";
      }
      
      // Não é um documento externo
      /*elseif($documentoDTO->isSetNumIdTipoConferencia()){
          
        $objTipoProcedimentoDTO = new PenTipoProcedimentoDTO(true);
        $objTipoProcedimentoDTO->retStrNome();
        $objTipoProcedimentoDTO->setBolExclusaoLogica(false);
        $objTipoProcedimentoDTO->setDblIdProcedimento($dblIdProcedimento);

        $objTipoProcedimentoBD = new TipoProcedimentoBD(BancoSEI::getInstance());

        $objTipoProcedimentoDTO = $objTipoProcedimentoBD->consultar($objTipoProcedimentoDTO);
        
        $strDescricaoDocumento = $objTipoProcedimentoDTO->getStrNome();
      }*/
      
      $documento->retirado = ($documentoDTO->getStrStaEstadoProtocolo() == ProtocoloRN::$TE_DOCUMENTO_CANCELADO) ? true : false;

      $documento->ordem = $ordemDocumento++;
      $documento->descricao = utf8_encode($strDescricaoDocumento);
      $documento->nivelDeSigilo = $this->obterNivelSigiloPEN($documentoDTO->getStrStaNivelAcessoLocalProtocolo());

     if($documentoDTO->getStrStaNivelAcessoLocalProtocolo() == ProtocoloRN::$NA_RESTRITO){
 
        $documento->hipoteseLegal = new stdClass();
        $documento->hipoteseLegal->identificacao = $objPenRelHipoteseLegalRN->getIdHipoteseLegalPEN($documentoDTO->getNumIdHipoteseLegalProtocolo());
      //  $documento->hipoteseLegal->nome = 'Nomee';
      //  $documento->hipoteseLegal->baseLegal = 'Base Legall';
      }
          $documento->dataHoraDeProducao = $this->objProcessoEletronicoRN->converterDataWebService($documentoDTO->getDtaGeracaoProtocolo());

      $usuarioDTO = $this->consultarUsuario($documentoDTO->getNumIdUsuarioGeradorProtocolo());
      if(isset($usuarioDTO)) {
        $documento->produtor = new stdClass();
        $documento->produtor->nome = utf8_encode($usuarioDTO->getStrNome());
        $documento->produtor->numeroDeIdentificacao = $usuarioDTO->getDblCpfContato();
                //TODO: Obter tipo de pessoa física dos contextos/contatos do SEI
        $documento->produtor->tipo = self::STA_TIPO_PESSOA_FISICA;;            
      }
      
 
      $unidadeDTO = $this->consultarUnidade($documentoDTO->getNumIdUnidadeResponsavel());
      if(isset($unidadeDTO)) {
        $documento->produtor->unidade = new stdClass();
        $documento->produtor->unidade->nome = utf8_encode($unidadeDTO->getStrDescricao());
        $documento->produtor->unidade->tipo = self::STA_TIPO_PESSOA_ORGAOPUBLICO;
                //TODO: Informar dados da estrutura organizacional (estruturaOrganizacional)                
      }
      
      $documento->produtor->numeroDeIdentificacao = $documentoDTO->getStrProtocoloDocumentoFormatado();
      
      $this->atribuirDataHoraDeRegistro($documento, $documentoDTO->getDblIdProcedimento(), $documentoDTO->getDblIdDocumento());

            //TODO: Implementar mapeamento de espécies documentais
      $documento->especie = new stdClass();
      $documento->especie->codigo = $this->obterEspecieMapeada($documentoDTO->getNumIdSerie());
      $documento->especie->nomeNoProdutor = utf8_encode($documentoDTO->getStrNomeSerie());
            //TODO: Tratar campos adicionais do documento

            //Identificação do documento
      $this->atribuirNumeracaoDocumento($documento, $documentoDTO);

      if($documento->retirado === true){
          
          $penComponenteDigitalDTO = new ComponenteDigitalDTO();
          $penComponenteDigitalDTO->retTodos();
          $penComponenteDigitalDTO->setDblIdDocumento($documentoDTO->getDblIdDocumento());
          
          $penComponenteDigitalBD = new ComponenteDigitalBD($this->getObjInfraIBanco());
 
          if($penComponenteDigitalBD->contar($penComponenteDigitalDTO) > 0){
              
              $arrPenComponenteDigitalDTO = $penComponenteDigitalBD->listar($penComponenteDigitalDTO);
              $componenteDigital = $arrPenComponenteDigitalDTO[0];
              
              $documento->componenteDigital = new stdClass();
              $documento->componenteDigital->ordem = 1;
              $documento->componenteDigital->nome = utf8_encode($componenteDigital->getStrNome());
              $documento->componenteDigital->hash = new SoapVar("<hash algoritmo='{$componenteDigital->getStrAlgoritmoHash()}'>{$componenteDigital->getStrHashConteudo()}</hash>", XSD_ANYXML);
              $documento->componenteDigital->tamanhoEmBytes = $componenteDigital->getNumTamanho();
              $documento->componenteDigital->mimeType = $componenteDigital->getStrMimeType();
              $documento->componenteDigital->tipoDeConteudo = $componenteDigital->getStrTipoConteudo();
              $documento->componenteDigital->idAnexo = $componenteDigital->getNumIdAnexo();
              
              
              
              // -------------------------- INICIO DA TAREFA US074 -------------------------------//
              $documento = $this->atribuirDadosAssinaturaDigital($documentoDTO, $documento, $componenteDigital->getStrHashConteudo());
              // -------------------------- FIM TAREFA US074 -------------------------------//
              
              
              if($componenteDigital->getStrMimeType() == 'outro'){
                  $documento->componenteDigital->dadosComplementaresDoTipoDeArquivo = 'outro';
              }
              
          }else{
              $this->atribuirComponentesDigitais($documento, $documentoDTO);
              
          }
          
          
      }else{
        $this->atribuirComponentesDigitais($documento, $documentoDTO);
      }

            //TODO: Necessário tratar informações abaixo
            //protocoloDoDocumentoAnexado
            //protocoloDoProcessoAnexado
            //retirado
            //protocoloAnterior
            //historico
      $documento->idDocumentoSEI = $documentoDTO->getDblIdDocumento();
      $objProcesso->documento[] = $documento;
    }
    
  }
  
  public function atribuirComponentesDigitaisRetirados($documentoDTO){
      
  }

  private function obterEspecieMapeada($parNumIdSerie)
  {
    if(!isset($parNumIdSerie) || $parNumIdSerie == 0) {
      throw new InfraException('Parâmetro $parNumIdSerie não informado.');
    }   

    $objPenRelTipoDocMapEnviadoDTO = new PenRelTipoDocMapEnviadoDTO();
    $objPenRelTipoDocMapEnviadoDTO->setNumIdSerie($parNumIdSerie);
    $objPenRelTipoDocMapEnviadoDTO->retNumCodigoEspecie();

    $objGenericoBD = new GenericoBD($this->getObjInfraIBanco());
    $objPenRelTipoDocMapEnviadoDTO = $objGenericoBD->consultar($objPenRelTipoDocMapEnviadoDTO);

    if($objPenRelTipoDocMapEnviadoDTO == null) {
      $objPenRelTipoDocMapEnviadoDTO = new PenRelTipoDocMapEnviadoDTO();
      $objPenRelTipoDocMapEnviadoDTO->retNumCodigoEspecie();
      $objPenRelTipoDocMapEnviadoDTO->setStrPadrao('S');
      $objPenRelTipoDocMapEnviadoDTO->setNumMaxRegistrosRetorno(1);
      $objPenRelTipoDocMapEnviadoDTO = $objGenericoBD->consultar($objPenRelTipoDocMapEnviadoDTO);
    }

    if($objPenRelTipoDocMapEnviadoDTO == null) {
      throw new InfraException("Código de identificação da espécie documental não pode ser localizada para o tipo de documento {$parNumIdSerie}.");
    }

    return $objPenRelTipoDocMapEnviadoDTO->getNumCodigoEspecie();
  }


  private function atribuirAssinaturaEletronica($objComponenteDigital, AssinaturaDTO $objAssinaturaDTO)
  {
    if(!isset($objComponenteDigital)){
      throw new InfraException('Parâmetro $objComponenteDigital não informado.');
    }

    if(isset($objAssinaturaDTO)) {            
      $objComponenteDigital->assinaturaDigital = new stdClass();
            //TODO: Obter as informações corretas dos metadados da assinatura digital
      $objComponenteDigital->assinaturaDigital->dataHora = $this->objProcessoEletronicoRN->converterDataWebService($objComponenteDigital->getDthAberturaAtividade());
      $objComponenteDigital->assinaturaDigital->cadeiaDoCertificado = new SoapVar('<cadeiaDoCertificado formato="PKCS7"></cadeiaDoCertificado>', XSD_ANYXML);
      $objComponenteDigital->assinaturaDigital->hash = new SoapVar("<hash algoritmo='{self::ALGORITMO_HASH_ASSINATURA}'>{$objAssinaturaDTO->getStrP7sBase64()}</hash>", XSD_ANYXML);
    }
  }

  private function atribuirComponentesDigitais($objDocumento, DocumentoDTO $objDocumentoDTO)
  {

    if(!isset($objDocumento)){
      throw new InfraException('Parâmetro $objDocumento não informado.');
    }

    if(!isset($objDocumentoDTO)){
      throw new InfraException('Parâmetro $objDocumentoDTO não informado.');
    }

    $arrInformacaoArquivo = $this->obterDadosArquivo($objDocumentoDTO);

    if(!isset($arrInformacaoArquivo) || count($arrInformacaoArquivo) == 0){
      throw new InfraException('Erro durante obtenção de informações sobre o componente digital do documento {$objDocumentoDTO->getStrProtocoloDocumentoFormatado()}.');
    }

        //TODO: Revisar tal implementação para atender a geração de hash de arquivos grandes
    $strAlgoritmoHash = self::ALGORITMO_HASH_DOCUMENTO;
    $strConteudoAssinatura = $arrInformacaoArquivo['CONTEUDO'];
    $hashDoComponenteDigital = hash($strAlgoritmoHash, $strConteudoAssinatura, true);
    $hashDoComponenteDigital = base64_encode($hashDoComponenteDigital);
        
    $objDocumento->componenteDigital = new stdClass();
    $objDocumento->componenteDigital->ordem = 1;
    $objDocumento->componenteDigital->nome = utf8_encode($arrInformacaoArquivo["NOME"]);
    $objDocumento->componenteDigital->hash = new SoapVar("<hash algoritmo='{$strAlgoritmoHash}'>{$hashDoComponenteDigital}</hash>", XSD_ANYXML);
    $objDocumento->componenteDigital->tamanhoEmBytes = $arrInformacaoArquivo['TAMANHO'];

        //TODO: Validar os tipos de mimetype de acordo com o WSDL do SEI
        //Caso não identifique o tipo correto, informar o valor [outro]        
    $objDocumento->componenteDigital->mimeType = $arrInformacaoArquivo['MIME_TYPE'];
    $objDocumento->componenteDigital->tipoDeConteudo = $this->obterTipoDeConteudo($arrInformacaoArquivo['MIME_TYPE']);
    
    
    // -------------------------- INICIO DA TAREFA US074 -------------------------------/
    $objDocumento = $this->atribuirDadosAssinaturaDigital($objDocumentoDTO, $objDocumento, $hashDoComponenteDigital);
    // -------------------------- FIM TAREFA US074 -------------------------------//
    
    
    if($arrInformacaoArquivo['MIME_TYPE'] == 'outro'){
        $objDocumento->componenteDigital->dadosComplementaresDoTipoDeArquivo = $arrInformacaoArquivo['dadosComplementaresDoTipoDeArquivo'];
    }
    
        //TODO: Preencher dados complementares do tipo de arquivo
        //$objDocumento->componenteDigital->dadosComplementaresDoTipoDeArquivo = '';


        //TODO: Carregar informações da assinatura digital
        //$this->atribuirAssinaturaEletronica($objDocumento->componenteDigital, $objDocumentoDTO);

    $objDocumento->componenteDigital->idAnexo = $arrInformacaoArquivo['ID_ANEXO'];
    return $objDocumento;
  }
  
    public function atribuirDadosAssinaturaDigital($objDocumentoDTO, $objDocumento, $strHashDocumento) {
        
        
        //Busca as Tarjas
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
        $objAssinaturaRN = new AssinaturaRN();
        $tarjas = $objAssinaturaRN->montarTarjas($objDocumentoDTOTarjas); 


        //Remove todos os 12 espaços padrões após remover as tags.
        $dataTarjas = explode('            ', strip_tags($tarjas));
        foreach ($dataTarjas as $key => $content) {
            $contentTrim = trim($content); //Limpa os espaços no inicio e fim de cada texto.
            if (empty($contentTrim)) {
               unset($dataTarjas[$key]);
            } else {
                $dataTarjas[$key] = html_entity_decode($contentTrim); //Decodifica por causa do strip_tags
            }
        }

        $dataTarjas = array_values($dataTarjas); //Reseta os valores da array

        $objAssinaturaDTO = new AssinaturaDTO();
        $objAssinaturaDTO->setDblIdDocumento($objDocumentoDTO->getDblIdDocumento());
        $objAssinaturaDTO->retNumIdAtividade();
        $objAssinaturaDTO->retStrP7sBase64();
        $objAssinaturaRN = new AssinaturaRN();
        $resAssinatura = $objAssinaturaRN->listarRN1323($objAssinaturaDTO);
        

        $objDocumento->componenteDigital->assinaturaDigital = array();
        //Para cada assinatura
        foreach ($resAssinatura as $keyOrder => $assinatura) {
            
            //Busca data da assinatura
            $objAtividadeDTO = new AtividadeDTO();
            $objAtividadeDTO->setNumIdAtividade($assinatura->getNumIdAtividade());
            $objAtividadeDTO->setNumIdTarefa(TarefaRN::$TI_ASSINATURA_DOCUMENTO);
            $objAtividadeDTO->retDthAbertura();
            $objAtividadeDTO->retNumIdAtividade();
            $objAtividadeRN = new AtividadeRN();
            $objAtividade = $objAtividadeRN->consultarRN0033($objAtividadeDTO);
            
            $objAssinaturaDigital = new stdClass();
            $objAssinaturaDigital->dataHora = $this->objProcessoEletronicoRN->converterDataWebService($objAtividade->getDthAbertura());
            $objAssinaturaDigital->hash =  new SoapVar("<hash algoritmo='".self::ALGORITMO_HASH_ASSINATURA."'>{$strHashDocumento}</hash>", XSD_ANYXML);
            $objAssinaturaDigital->cadeiaDoCertificado = new SoapVar('<cadeiaDoCertificado formato="PKCS7">'.($assinatura->getStrP7sBase64() ? $assinatura->getStrP7sBase64() : 'null').'</cadeiaDoCertificado>', XSD_ANYXML);
            $objAssinaturaDigital->razao = utf8_encode($dataTarjas[$keyOrder]);
            $objAssinaturaDigital->observacao = utf8_encode($dataTarjas[count($dataTarjas) - 1]);
        
            $objDocumento->componenteDigital->assinaturaDigital[] = $objAssinaturaDigital;    
        }
        
        
        
        return $objDocumento;
    }

  private function obterDadosArquivo(DocumentoDTO $objDocumentoDTO)
  {
      
    if(!isset($objDocumentoDTO)){
      throw new InfraException('Parâmetro $objDocumentoDTO não informado.');
    }

    $arrInformacaoArquivo = array();
    $strProtocoloDocumentoFormatado = $objDocumentoDTO->getStrProtocoloDocumentoFormatado();
    
    $objInfraParametro = new InfraParametro($this->getObjInfraIBanco());
    $idSerieEmail = $objInfraParametro->getValor('ID_SERIE_EMAIL');
    $docEmailEnviado = $objDocumentoDTO->getNumIdSerie() == $idSerieEmail && $objDocumentoDTO->getStrStaDocumento() == DocumentoRN::$TD_FORMULARIO_AUTOMATICO ? true : false;
    
    if($objDocumentoDTO->getStrStaDocumento() == DocumentoRN::$TD_EDITOR_INTERNO) {

      $objEditorDTO = new EditorDTO();
      $objEditorDTO->setDblIdDocumento($objDocumentoDTO->getDblIdDocumento());
      $objEditorDTO->setNumIdBaseConhecimento(null);
      $objEditorDTO->setStrSinCabecalho('S');
      $objEditorDTO->setStrSinRodape('S');
      $objEditorDTO->setStrSinIdentificacaoVersao('S');
      $objEditorDTO->setStrSinProcessarLinks('S');
      
      $numVersaoAtual = intval(str_replace('.', '', SEI_VERSAO));
      $numVersaoCarimboObrigatorio = intval(str_replace('.', '', self::VERSAO_CARIMBO_PUBLICACAO_OBRIGATORIO));
      if ($numVersaoAtual >= $numVersaoCarimboObrigatorio) {
        $objEditorDTO->setStrSinCarimboPublicacao('N');  
      }   
      
      $objEditorRN = new EditorRN();
      $strConteudoAssinatura = $objEditorRN->consultarHtmlVersao($objEditorDTO);

            //$strConteudoAssinatura = $objDocumentoDTO->getStrConteudoAssinatura();
      $arrInformacaoArquivo['NOME'] = $strProtocoloDocumentoFormatado . ".html";
      $arrInformacaoArquivo['CONTEUDO'] = $strConteudoAssinatura;
      $arrInformacaoArquivo['TAMANHO'] = strlen($strConteudoAssinatura);
      $arrInformacaoArquivo['MIME_TYPE'] = 'text/html';
      $arrInformacaoArquivo['ID_ANEXO'] = null;

    } else if($objDocumentoDTO->getStrStaProtocoloProtocolo() == ProtocoloRN::$TP_DOCUMENTO_RECEBIDO)  {
   
      $objAnexoDTO = $this->consultarAnexo($objDocumentoDTO->getDblIdDocumento());

      if(!isset($objAnexoDTO)){
        throw new InfraException("Componente digital do documento {$strProtocoloDocumentoFormatado} não pode ser localizado.");
      }
      
      //VALIDAÇÃO DE TAMANHO DE DOCUMENTOS EXTERNOS PARA A EXPEDIÇÃO
      $objPenParametroRN = new PenParametroRN();
      if($objAnexoDTO->getNumTamanho() > ($objPenParametroRN->getParametro('PEN_TAMANHO_MAXIMO_DOCUMENTO_EXPEDIDO') * 1024 * 1024) && $objDocumentoDTO->getStrStaEstadoProtocolo() != ProtocoloRN::$TE_DOCUMENTO_CANCELADO){
           throw new InfraException("O tamanho do documento {$objAnexoDTO->getStrProtocoloFormatadoProtocolo()} é maior que os {$objPenParametroRN->getParametro('PEN_TAMANHO_MAXIMO_DOCUMENTO_EXPEDIDO')} MB permitidos para a expedição de documentos externos.");
      } 

            //Obtenção do conteudo do documento externo
            //TODO: Particionar o documento em tamanho menor caso ultrapasse XX megabytes
      $strCaminhoAnexo = $this->objAnexoRN->obterLocalizacao($objAnexoDTO);

      $fp = fopen($strCaminhoAnexo, "rb");
      try {            
        $strConteudoAssinatura = fread($fp, filesize($strCaminhoAnexo));
        fclose($fp);
      } catch(Exception $e) {
        fclose($fp);
        throw new InfraException("Erro obtendo conteudo do anexo do documento {$strProtocoloDocumentoFormatado}", $e);
      }       

      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      try {
        $strMimeType = finfo_file($finfo, $strCaminhoAnexo);
        
        $strDadosComplementaresDoTipoDeArquivo = "";
        
        if(!array_search($strMimeType, $this->arrPenMimeTypes)){
          $strDadosComplementaresDoTipoDeArquivo = $strMimeType;
          $strMimeType = 'outro';
        }
        
        finfo_close($finfo);
      } catch(Exception $e) {
        finfo_close($finfo);
        throw new InfraException("Erro obtendo informações do anexo do documento {$strProtocoloDocumentoFormatado}", $e);
      }       

      $arrInformacaoArquivo['NOME'] = $objAnexoDTO->getStrNome();
      $arrInformacaoArquivo['CONTEUDO'] = $strConteudoAssinatura;
      $arrInformacaoArquivo['TAMANHO'] = $objAnexoDTO->getNumTamanho();
      $arrInformacaoArquivo['MIME_TYPE'] = $strMimeType;
      $arrInformacaoArquivo['ID_ANEXO'] = $objAnexoDTO->getNumIdAnexo();
      $arrInformacaoArquivo['dadosComplementaresDoTipoDeArquivo'] = $strDadosComplementaresDoTipoDeArquivo;

    } 
    else {

        $objDocumentoDTO2 = new DocumentoDTO();
        $objDocumentoDTO2->setDblIdDocumento($objDocumentoDTO->getDblIdDocumento());
        $objDocumentoDTO2->setObjInfraSessao(SessaoSEI::getInstance());
       // $objDocumentoDTO2->setStrLinkDownload('controlador.php?acao=documento_download_anexo');
        
        $objDocumentoRN = new DocumentoRN();
        $strResultado = $objDocumentoRN->consultarHtmlFormulario($objDocumentoDTO2);
        
        $arrInformacaoArquivo['NOME'] = $strProtocoloDocumentoFormatado . ".html";
        $arrInformacaoArquivo['CONTEUDO'] = $strResultado;
        $arrInformacaoArquivo['TAMANHO'] = strlen($strResultado);
        $arrInformacaoArquivo['MIME_TYPE'] = 'text/html';
        $arrInformacaoArquivo['ID_ANEXO'] = null;
        
    }

    return $arrInformacaoArquivo;
  }

  private function obterTipoDeConteudo($strMimeType)
  {
    if(!isset($strMimeType)){
      throw new InfraException('Parâmetro $strMimeType não informado.');
    }

    $resultado = self::TC_TIPO_CONTEUDO_OUTROS;

    if(preg_match(self::REGEX_ARQUIVO_TEXTO, $strMimeType)){
      $resultado = self::TC_TIPO_CONTEUDO_TEXTO;
    } else if(preg_match(self::REGEX_ARQUIVO_IMAGEM, $strMimeType)){
      $resultado = self::TC_TIPO_CONTEUDO_IMAGEM;
    } else if(preg_match(self::REGEX_ARQUIVO_AUDIO, $strMimeType)){
      $resultado = self::TC_TIPO_CONTEUDO_AUDIO;
    } else if(preg_match(self::REGEX_ARQUIVO_VIDEO, $strMimeType)){
      $resultado = self::TC_TIPO_CONTEUDO_VIDEO;
    }
    
    return $resultado;
  }

  private function atribuirNumeracaoDocumento($objDocumento, DocumentoDTO $parObjDocumentoDTO)
  {
    $objSerieDTO = $this->consultarSerie($parObjDocumentoDTO->getNumIdSerie());
    $strStaNumeracao = $objSerieDTO->getStrStaNumeracao();

    if($strStaNumeracao == SerieRN::$TN_SEQUENCIAL_UNIDADE) {
      $objDocumento->identificacao = new stdClass();
      $objDocumento->identificacao->numero = $parObjDocumentoDTO->getStrNumero();
      $objDocumento->identificacao->siglaDaUnidadeProdutora = $parObjDocumentoDTO->getStrSiglaUnidadeGeradoraProtocolo();
      $objDocumento->identificacao->complemento = utf8_encode($parObjDocumentoDTO->getStrDescricaoUnidadeGeradoraProtocolo());
    }else if($strStaNumeracao == SerieRN::$TN_SEQUENCIAL_ORGAO){
      $objOrgaoDTO = $this->consultarOrgao($parObjDocumentoDTO->getNumIdOrgaoUnidadeGeradoraProtocolo());
      $objDocumento->identificacao = new stdClass();            
      $objDocumento->identificacao->numero = $parObjDocumentoDTO->getStrNumero();
      $objDocumento->identificacao->siglaDaUnidadeProdutora = $objOrgaoDTO->getStrSigla();
      $objDocumento->identificacao->complemento = utf8_encode($objOrgaoDTO->getStrDescricao());
    }else if($strStaNumeracao == SerieRN::$TN_SEQUENCIAL_ANUAL_UNIDADE){
      $objDocumento->identificacao = new stdClass();
      $objDocumento->identificacao->siglaDaUnidadeProdutora = $parObjDocumentoDTO->getStrSiglaUnidadeGeradoraProtocolo();
      $objDocumento->identificacao->complemento = utf8_encode($parObjDocumentoDTO->getStrDescricaoUnidadeGeradoraProtocolo());
      $objDocumento->identificacao->numero = $parObjDocumentoDTO->getStrNumero();
      $objDocumento->identificacao->ano = substr($parObjDocumentoDTO->getDtaGeracaoProtocolo(),6,4);
    }else if($strStaNumeracao == SerieRN::$TN_SEQUENCIAL_ANUAL_ORGAO){
      $objOrgaoDTO = $this->consultarOrgao($parObjDocumentoDTO->getNumIdOrgaoUnidadeGeradoraProtocolo());
      $objDocumento->identificacao = new stdClass();
      $objDocumento->identificacao->numero = $parObjDocumentoDTO->getStrNumero();
      $objDocumento->identificacao->siglaDaUnidadeProdutora = $objOrgaoDTO->getStrSigla();
      $objDocumento->identificacao->complemento = utf8_encode($objOrgaoDTO->getStrDescricao());
      $objDocumento->identificacao->ano = substr($parObjDocumentoDTO->getDtaGeracaoProtocolo(),6,4);
    }
  }

  private function adicionarProcessosApensados($objProcesso, $arrIdProcessoApensado) 
  {
    if(isset($arrIdProcessoApensado) && is_array($arrIdProcessoApensado) && count($arrIdProcessoApensado) > 0) {
      $objProcesso->processoApensado = array();
      foreach($arrIdProcessoApensado as $idProcedimentoApensado) {
        $objProcesso->processoApensado[] = $this->construirProcesso($idProcedimentoApensado);
      }
    }
  }

  private function consultarUnidade($numIdUnidade) 
  {
    if(!isset($numIdUnidade)){
      throw new InfraException('Parâmetro $numIdUnidade não informado.');
    }

    $objUnidadeDTO = new UnidadeDTO();
    $objUnidadeDTO->setNumIdUnidade($numIdUnidade);
    $objUnidadeDTO->retStrDescricao();

    return $this->objUnidadeRN->consultarRN0125($objUnidadeDTO);
  }

  private function consultarSerie($numIdSerie)
  {
    if(!isset($numIdSerie)){
      throw new InfraException('Parâmetro $numIdSerie não informado.');
    }

    $objSerieDTO = new SerieDTO();
    $objSerieDTO->setNumIdSerie($numIdSerie);
    $objSerieDTO->retStrStaNumeracao();

    return $this->objSerieRN->consultarRN0644($objSerieDTO);
  }

  private function consultarOrgao($numIdOrgao)
  {
    $objOrgaoDTO = new OrgaoDTO();
    $objOrgaoDTO->setNumIdOrgao($numIdOrgao);
    $objOrgaoDTO->retStrSigla();
    $objOrgaoDTO->retStrDescricao();

    return $this->objOrgaoRN->consultarRN1352($objOrgaoDTO);
  }

  public function consultarProcedimento($numIdProcedimento) 
  {
    if(!isset($numIdProcedimento)){
      throw new InfraException('Parâmetro $numIdProcedimento não informado.');
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

    return $this->objProcedimentoRN->consultarRN0201($objProcedimentoDTO);
  }

  public function listarInteressados($numIdProtocolo)
  {
    if(!isset($numIdProtocolo)){
      throw new InfraException('Parâmetro $numIdProtocolo não informado.');
    }

    $objParticipanteDTO = new ParticipanteDTO();
    $objParticipanteDTO->retNumIdContato();
    $objParticipanteDTO->retStrNomeContato();
    $objParticipanteDTO->setDblIdProtocolo($numIdProtocolo);
    $objParticipanteDTO->setStrStaParticipacao(ParticipanteRN::$TP_INTERESSADO);

    return $this->objParticipanteRN->listarRN0189($objParticipanteDTO);
  }

  private function consultarProtocoloDocumento($numIdProcedimento) 
  {
    $objProtocoloDTO = new ProtocoloDTO();

    $objProtocoloDTO->setStrStaProtocolo(array(ProtocoloRN::$TP_DOCUMENTO_GERADO,ProtocoloRN::$TP_DOCUMENTO_RECEBIDO),InfraDTO::$OPER_IN);
    $objProtocoloDTO->setStrStaProtocolo($staProtocolo);
    $objProtocoloDTO->setDblIdProtocolo($numIdProcedimento);
    $objProtocoloDTO->retTodos();
    $objProtocoloDTO->retNumIdSerie();

        return $this->objProtocoloRN->consultarRN0186($objProtocoloDTO); //TODO: Verificar regra de busca
      }

      private function consultarAnexo($dblIdDocumento)
      {
        if(!isset($dblIdDocumento)){
          throw new InfraException('Parâmetro $dblIdDocumento não informado.');
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
        if(!isset($numIdUsuario)){
          throw new InfraException('Parâmetro $numIdUsuario não informado.');
        }

        $objUsuarioDTO = new UsuarioDTO();    
        $objUsuarioDTO->setNumIdUsuario($numIdUsuario);
        $objUsuarioDTO->retStrNome();
        $objUsuarioDTO->retDblCpfContato();

        return $this->objUsuarioRN->consultarRN0489($objUsuarioDTO);
      }

      public function listarDocumentos($idProcedimento) 
      {   
        if(!isset($idProcedimento)){
          throw new InfraException('Parâmetro $idProcedimento não informado.');
        }

        $documentoDTO = new DocumentoDTO();        
        $documentoDTO->setDblIdProcedimento($idProcedimento);
        $documentoDTO->retStrDescricaoUnidadeGeradoraProtocolo();
        $documentoDTO->retNumIdOrgaoUnidadeGeradoraProtocolo();        
        $documentoDTO->retStrSiglaUnidadeGeradoraProtocolo();
        $documentoDTO->retStrStaNivelAcessoLocalProtocolo();
        $documentoDTO->retStrProtocoloDocumentoFormatado();
        $documentoDTO->retStrStaEstadoProtocolo();
        $documentoDTO->retNumIdUsuarioGeradorProtocolo();
        $documentoDTO->retStrStaProtocoloProtocolo();        
        $documentoDTO->retNumIdUnidadeResponsavel();
        $documentoDTO->retStrDescricaoProtocolo();
        $documentoDTO->retDtaGeracaoProtocolo();
        $documentoDTO->retDblIdProcedimento();
        $documentoDTO->retDblIdDocumento();
        $documentoDTO->retStrNomeSerie();
        $documentoDTO->retNumIdSerie();
        $documentoDTO->retStrConteudoAssinatura();
        $documentoDTO->retStrNumero();
        $documentoDTO->retNumIdTipoConferencia();
        $documentoDTO->retStrStaDocumento();
        $documentoDTO->retNumIdHipoteseLegalProtocolo();
        $documentoDTO->setOrdStrProtocoloDocumentoFormatado(InfraDTO::$TIPO_ORDENACAO_ASC);
        
        return $this->objDocumentoRN->listarRN0008($documentoDTO);
      }

    /**
     * Retorna o nome do documento no PEN
     * 
     * @param int
     * @return string
    */
    private function consultarNomeDocumentoPEN(DocumentoDTO $objDocumentoDTO){
          
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
      
      private function consultarDocumento($dblIdDocumento)
      {
        if(!isset($dblIdDocumento)){
          throw new InfraException('Parâmetro $dblIdDocumento não informado.');
        }

        $documentoDTO = new DocumentoDTO();        
        $documentoDTO->setDblIdDocumento($dblIdDocumento);
        $documentoDTO->retStrDescricaoUnidadeGeradoraProtocolo();
        //$documentoDTO->retNumIdOrgaoUnidadeGeradoraProtocolo();        
        //$documentoDTO->retStrSiglaUnidadeGeradoraProtocolo();
        //$documentoDTO->retStrStaNivelAcessoLocalProtocolo();
        $documentoDTO->retStrProtocoloDocumentoFormatado();
        //$documentoDTO->retNumIdUsuarioGeradorProtocolo();
        $documentoDTO->retStrStaProtocoloProtocolo();        
        //$documentoDTO->retNumIdUnidadeResponsavel();
        $documentoDTO->retStrDescricaoProtocolo();
        //$documentoDTO->retDtaGeracaoProtocolo();
        //$documentoDTO->retDblIdProcedimento();
        $documentoDTO->retDblIdDocumento();
        $documentoDTO->retStrNomeSerie();
        $documentoDTO->retNumIdSerie();
        $documentoDTO->retStrConteudoAssinatura();
        $documentoDTO->retStrStaDocumento();
        $documentoDTO->retStrStaEstadoProtocolo();
        $documentoDTO->retNumIdHipoteseLegalProtocolo();
        //$documentoDTO->retStrNumero();
        
        return $this->objDocumentoRN->consultarRN0005($documentoDTO);
      }

      private function enviarComponentesDigitais($strNumeroRegistro, $numIdTramite, $strProtocolo) {
        if (!isset($strNumeroRegistro)) {
            throw new InfraException('Parâmetro $strNumeroRegistro não informado.');
        }

        if (!isset($numIdTramite)) {
            throw new InfraException('Parâmetro $numIdTramite não informado.');
        }

        if (!isset($strProtocolo)) {
            throw new InfraException('Parâmetro $strProtocolo não informado.');
        }

        //Obter dados dos componetes digitais
        $objComponenteDigitalBD = new ComponenteDigitalBD($this->getObjInfraIBanco());
        $objComponenteDigitalDTO = new ComponenteDigitalDTO();
        $objComponenteDigitalDTO->setStrNumeroRegistro($strNumeroRegistro);
        $objComponenteDigitalDTO->setNumIdTramite($numIdTramite);
        $objComponenteDigitalDTO->setStrSinEnviar("S");
        $objComponenteDigitalDTO->setOrdNumOrdem(InfraDTO::$TIPO_ORDENACAO_ASC);
        $objComponenteDigitalDTO->retDblIdDocumento();
        $objComponenteDigitalDTO->retNumTicketEnvioComponentes();
        //  $objComponenteDigitalDTO->retStrConteudoAssinaturaDocumento();
        $objComponenteDigitalDTO->retStrProtocoloDocumentoFormatado();
        $objComponenteDigitalDTO->retStrHashConteudo();
        $objComponenteDigitalDTO->retStrProtocolo();
        $objComponenteDigitalDTO->retStrNome();
        $objComponenteDigitalDTO->retDblIdProcedimento();
        
        $arrComponentesDigitaisDTO = $objComponenteDigitalBD->listar($objComponenteDigitalDTO);

        if (isset($arrComponentesDigitaisDTO) && count($arrComponentesDigitaisDTO) > 0) {

            //TODO: Valida inconsistência da quantidade de documentos solicitados e aqueles cadastrados no SEI
   
            
            //Construir objeto Componentes digitais                  
            foreach ($arrComponentesDigitaisDTO as $objComponenteDigitalDTO) {
                
                    $this->barraProgresso->mover(ProcessoEletronicoINT::NEE_EXPEDICAO_ETAPA_DOCUMENTO);
                    $this->barraProgresso->setStrRotulo(sprintf(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_DOCUMENTO, $objComponenteDigitalDTO->getStrProtocoloDocumentoFormatado()));

                    $dadosDoComponenteDigital = new stdClass();
                    $dadosDoComponenteDigital->ticketParaEnvioDeComponentesDigitais = $objComponenteDigitalDTO->getNumTicketEnvioComponentes();

                    //TODO: Problema no barramento de serviços quando um mesmo arquivo está contido em dois diferentes
                    //processos apensados. Mesmo erro relatado com dois arquivos iguais em docs diferentes no mesmo processo
                    $dadosDoComponenteDigital->protocolo = $objComponenteDigitalDTO->getStrProtocolo();
                    $dadosDoComponenteDigital->hashDoComponenteDigital = $objComponenteDigitalDTO->getStrHashConteudo();

                    //TODO: Particionar o arquivo em várias partes caso for muito grande seu tamanho
                    //TODO: Obter dados do conteudo do documento, sendo Interno ou Externo
                    //$strConteudoDocumento = $this->consultarConteudoDocumento($objComponenteDigitalDTO->getDblIdDocumento());
                    //$strConteudoAssinatura = $objComponenteDigitalDTO->getStrConteudoAssinaturaDocumento();
                    $objDocumentoDTO = $this->consultarDocumento($objComponenteDigitalDTO->getDblIdDocumento());
                    $strNomeDocumento = $this->consultarNomeDocumentoPEN($objDocumentoDTO);
                    $arrInformacaoArquivo = $this->obterDadosArquivo($objDocumentoDTO);
                    $dadosDoComponenteDigital->conteudoDoComponenteDigital = new SoapVar($arrInformacaoArquivo['CONTEUDO'], XSD_BASE64BINARY);


            
                    try {
                        //Enviar componentes digitais
                        $parametros = new stdClass();
                        $parametros->dadosDoComponenteDigital = $dadosDoComponenteDigital;
                        $result = $this->objProcessoEletronicoRN->enviarComponenteDigital($parametros);

                        //Bloquea documento para atualização, já que ele foi visualizado
                        $this->objDocumentoRN->bloquearConteudo($objDocumentoDTO);
                        // @join_tec US008.05 (#23092)
                        $this->objProcedimentoAndamentoRN->cadastrar(sprintf('Enviando %s %s', $strNomeDocumento, $objComponenteDigitalDTO->getStrProtocoloDocumentoFormatado()), 'S');
                    } catch (Exception $e) {
                        // @join_tec US008.05 (#23092)
                        $this->objProcedimentoAndamentoRN->cadastrar(sprintf('Enviando %s %s', $strNomeDocumento, $objComponenteDigitalDTO->getStrProtocoloDocumentoFormatado()), 'N');
                        throw new InfraException("Error Processing Request", $e);
                    }
            }

        }
    }

    private function validarParametrosExpedicao(InfraException $objInfraException, ExpedirProcedimentoDTO $objExpedirProcedimentoDTO)
      {
        if(!isset($objExpedirProcedimentoDTO)){
          $objInfraException->adicionarValidacao('Parâmetro $objExpedirProcedimentoDTO não informado.');
        }

        //TODO: Validar se repositório de origem foi informado                    
        if (InfraString::isBolVazia($objExpedirProcedimentoDTO->getNumIdRepositorioOrigem())){
          $objInfraException->adicionarValidacao('Identificação do Repositório de Estruturas da unidade atual não informado.');
        }
        
        //TODO: Validar se unidade de origem foi informado
        if (InfraString::isBolVazia($objExpedirProcedimentoDTO->getNumIdUnidadeOrigem())){
          $objInfraException->adicionarValidacao('Identificação da unidade atual no Repositório de Estruturas Organizacionais não informado.');
        }
        
        //TODO: Validar se repositório foi devidamente informado
        if (InfraString::isBolVazia($objExpedirProcedimentoDTO->getNumIdRepositorioDestino())){
          $objInfraException->adicionarValidacao('Repositório de Estruturas Organizacionais não informado.');
        }
        
        //TODO: Validar se unidade foi devidamente informada
        if (InfraString::isBolVazia($objExpedirProcedimentoDTO->getNumIdUnidadeDestino())){
          $objInfraException->adicionarValidacao('Unidade de destino não informado.');
        }

        //TODO: Validar se motivo de urgência foi devidamente informado, caso expedição urgente            
        if ($objExpedirProcedimentoDTO->getBolSinUrgente() && InfraString::isBolVazia($objExpedirProcedimentoDTO->getNumIdMotivoUrgencia())){
          $objInfraException->adicionarValidacao('Motivo de urgência da expedição do processo não informado .');
        }
      }

      private function validarDocumentacaoExistende(InfraException $objInfraException, ProcedimentoDTO $objProcedimentoDTO, $strAtributoValidacao = null) 
      {
        $arrObjDocumentoDTO = $objProcedimentoDTO->getArrObjDocumentoDTO();
        if(!isset($arrObjDocumentoDTO) || count($arrObjDocumentoDTO) == 0) {
          $objInfraException->adicionarValidacao('Não é possível expedir um processo sem documentos!', $strAtributoValidacao);
        }
      }

      private function validarDadosProcedimento(InfraException $objInfraException, ProcedimentoDTO $objProcedimentoDTO, $strAtributoValidacao = null)
      {
        if($objProcedimentoDTO->isSetStrDescricaoProtocolo() && InfraString::isBolVazia($objProcedimentoDTO->getStrDescricaoProtocolo())) { 
          $objInfraException->adicionarValidacao("Descrição do processo {$objProcedimentoDTO->getStrProtocoloProcedimentoFormatado()} não informado.", $strAtributoValidacao);
        }

        if(!$objProcedimentoDTO->isSetArrObjParticipanteDTO() || count($objProcedimentoDTO->getArrObjParticipanteDTO()) == 0) {
          $objInfraException->adicionarValidacao("Interessados do processo {$objProcedimentoDTO->getStrProtocoloProcedimentoFormatado()} não informados.", $strAtributoValidacao);
        }
      }

    private function validarDadosDocumentos(InfraException $objInfraException, $arrDocumentoDTO, $strAtributoValidacao = null) {
      
        if(!empty($arrDocumentoDTO)) {
            
            $objDocMapDTO = new PenRelTipoDocMapEnviadoDTO();
            $objGenericoBD = new GenericoBD($this->inicializarObjInfraIBanco());
            $objPenRelHipoteseLegalEnvioRN = new PenRelHipoteseLegalEnvioRN();
            
            foreach($arrDocumentoDTO as $objDocumentoDTO) {
                
                $objDocMapDTO->unSetTodos();
                $objDocMapDTO->setNumIdSerie($objDocumentoDTO->getNumIdSerie());
                
                if($objGenericoBD->contar($objDocMapDTO) == 0) {  
                     
                    $strDescricao = sprintf(
                        'Não existe mapeamento de envio para %s no documento %s', 
                        $objDocumentoDTO->getStrNomeSerie(),
                        $objDocumentoDTO->getStrProtocoloDocumentoFormatado() 
                    );
                    
                    $objInfraException->adicionarValidacao($strDescricao, $strAtributoValidacao);
                }
                
                if (!empty($objDocumentoDTO->getNumIdHipoteseLegalProtocolo()) 
                        && empty($objPenRelHipoteseLegalEnvioRN->getIdHipoteseLegalPEN($objDocumentoDTO->getNumIdHipoteseLegalProtocolo()))) {
                    
                    $objHipoteseLegalDTO = new HipoteseLegalDTO();
                    $objHipoteseLegalDTO->setNumIdHipoteseLegal($objDocumentoDTO->getNumIdHipoteseLegalProtocolo());
                    $objHipoteseLegalDTO->retStrNome();
                    $objHipoteseLegalRN = new HipoteseLegalRN();
                    $dados = $objHipoteseLegalRN->consultar($objHipoteseLegalDTO);
                    
                    $objInfraException->adicionarValidacao('Hipótese Legal "'.$dados->getStrNome().'" do Documento Não foi Mapeada', $strAtributoValidacao);
                }
            }
        }
    }

    private function validarProcessoAbertoUnidade(InfraException $objInfraException, ProcedimentoDTO $objProcedimentoDTO, $strAtributoValidacao = null) 
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
        $objInfraException->adicionarValidacao("Não é possível expedir processo aberto em mais de uma unidades. ($strSiglaUnidade)", $strAtributoValidacao);
      }
    }

    private function validarNivelAcessoProcesso(InfraException $objInfraException, ProcedimentoDTO $objProcedimentoDTO, $strAtributoValidacao = null) 
    {
        // $objProcedimentoDTO = new ProcedimentoDTO();            

        // $objProcedimentoDTO->setDblIdProcedimento($idProcedimento);
        // $objProcedimentoDTO->retStrStaNivelAcessoGlobalProtocolo();

        // $objProcedimentoDTO = $this->objProcedimentoRN->consultarRN0201($objProcedimentoDTO);
  
      if ($objProcedimentoDTO->getStrStaNivelAcessoLocalProtocolo() == ProtocoloRN::$NA_SIGILOSO) {
          $objInfraException->adicionarValidacao('Não é possível expedir processo com informações sigilosas.', $strAtributoValidacao);
      }
    }
    
    /**
     * Valida existência da Hipótese legal de Envio
     * @param InfraException $objInfraException
     * @param ProcedimentoDTO $objProcedimentoDTO
     * @param string $strAtributoValidacao
     */
    private function validarHipoteseLegalEnvio(InfraException $objInfraException, ProcedimentoDTO $objProcedimentoDTO, $strAtributoValidacao = null) {
        if ($objProcedimentoDTO->getStrStaNivelAcessoLocalProtocolo() == ProtocoloRN::$NA_RESTRITO) {
            if (empty($objProcedimentoDTO->getNumIdHipoteseLegalProtocolo())) {
                $objInfraException->adicionarValidacao('Não é possível expedir processo de nível restrito sem a hipótese legal mapeada.', $strAtributoValidacao);
            }
            
        }
    }

    private function validarAssinaturas(InfraException $objInfraException, $objProcedimentoDTO, $strAtributoValidacao = null) {
    
        $bolAssinaturaCorretas = true;
        
        $objDocumentoDTO = new DocumentoDTO();
        $objDocumentoDTO->setDblIdProcedimento($objProcedimentoDTO->getDblIdProcedimento());
       // $objDocumentoDTO->setStrStaEditor(array(EditorRN::$TE_EDOC, EditorRN::$TE_INTERNO), InfraDTO::$OPER_IN);
        $objDocumentoDTO->retDblIdDocumento();
        $objDocumentoDTO->retStrStaDocumento();
        $objDocumentoDTO->retStrStaEstadoProtocolo();

        $objDocumentoRN = new DocumentoRN();
        $arrObjDocumentoDTO = (array)$objDocumentoRN->listarRN0008($objDocumentoDTO);
        
        if(!empty($arrObjDocumentoDTO)) {
        
            $objAssinaturaDTO = new AssinaturaDTO();
            $objAssinaturaDTO->setDistinct(true);
            $objAssinaturaDTO->retDblIdDocumento();

            $objAssinaturaRN = new AssinaturaRN();

            foreach($arrObjDocumentoDTO as $objDocumentoDTO) {

                $objAssinaturaDTO->setDblIdDocumento($objDocumentoDTO->getDblIdDocumento());

                // Se o documento não tem assinatura e não foi cancelado então
                // cai na regra de validação
                if($objAssinaturaRN->contarRN1324($objAssinaturaDTO) == 0 && $objDocumentoDTO->getStrStaEstadoProtocolo() != ProtocoloRN::$TE_DOCUMENTO_CANCELADO && ($objDocumentoDTO->getStrStaDocumento() == DocumentoRN::$TD_EDITOR_EDOC || $objDocumentoDTO->getStrStaDocumento() == DocumentoRN::$TD_EDITOR_INTERNO) ){
                    
                    $bolAssinaturaCorretas = false;
                }
            }
        }

        if($bolAssinaturaCorretas !== true) {
            $objInfraException->adicionarValidacao('Não é possível expedir processos com documentos gerados e não assinados!', $strAtributoValidacao);
        }
    }

    /**
     * Validação das pré-conidições necessárias para que um processo e seus documentos possam ser expedidos para outra entidade
     * @param  InfraException  $objInfraException  Instância da classe de exceção para registro dos erros
     * @param  ProcedimentoDTO $objProcedimentoDTO Informações sobre o procedimento a ser expedido
     * @param string $strAtributoValidacao indice para o InfraException separar os processos
     */
    public function validarPreCondicoesExpedirProcedimento(InfraException $objInfraException, ProcedimentoDTO $objProcedimentoDTO, $strAtributoValidacao = null) 
    {
        //TODO: Validar pré-condições dos processos e documentos apensados
      $this->validarDadosProcedimento($objInfraException, $objProcedimentoDTO, $strAtributoValidacao);
      $this->validarDadosDocumentos($objInfraException, $objProcedimentoDTO->getArrObjDocumentoDTO(), $strAtributoValidacao);        

      $this->validarDocumentacaoExistende($objInfraException, $objProcedimentoDTO, $strAtributoValidacao);
      $this->validarProcessoAbertoUnidade($objInfraException, $objProcedimentoDTO, $strAtributoValidacao);
      $this->validarNivelAcessoProcesso($objInfraException, $objProcedimentoDTO, $strAtributoValidacao);
      $this->validarHipoteseLegalEnvio($objInfraException, $objProcedimentoDTO, $strAtributoValidacao);
      $this->validarAssinaturas($objInfraException, $objProcedimentoDTO, $strAtributoValidacao);        
    }


    private function obterNivelSigiloPEN($strNivelSigilo) {
      switch ($strNivelSigilo) {
        case ProtocoloRN::$NA_PUBLICO: return self::STA_SIGILO_PUBLICO;
        break;
        case ProtocoloRN::$NA_RESTRITO: return self::STA_SIGILO_RESTRITO;
        break;
        case ProtocoloRN::$NA_SIGILOSO: return self::STA_SIGILO_SIGILOSO;
        break;
        default:
        break;
      }
    }


    public function listarProcessosApensados($dblIdProcedimentoAtual, $idUnidadeAtual, $strPalavrasPesquisa = '', $numRegistros = 15) {

      $arrObjProcessosApensados = array();

      try{
        $objInfraException = new InfraException();
        $idUnidadeAtual = filter_var($idUnidadeAtual, FILTER_SANITIZE_NUMBER_INT);

        if(!$idUnidadeAtual){
          $objInfraException->adicionarValidacao('Processo inválido.');
        }
        
        $objInfraException->lancarValidacoes();    
            //Pesquisar procedimentos que estão abertos na unidade atual            
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

        $arrPalavrasPesquisa = explode(' ',$strPalavrasPesquisa);
        for($i=0; $i<count($arrPalavrasPesquisa); $i++) {
          $arrPalavrasPesquisa[$i] = '%'.$arrPalavrasPesquisa[$i].'%';
        }

        if (count($arrPalavrasPesquisa)==1){
          $objAtividadeDTO->setStrProtocoloFormatadoProtocolo($arrPalavrasPesquisa[0],InfraDTO::$OPER_LIKE);
        }else{
          $objAtividadeDTO->unSetStrProtocoloFormatadoProtocolo();
          $a = array_fill(0,count($arrPalavrasPesquisa),'ProtocoloFormatadoProtocolo');
          $b = array_fill(0,count($arrPalavrasPesquisa),InfraDTO::$OPER_LIKE);
          $d = array_fill(0,count($arrPalavrasPesquisa)-1,InfraDTO::$OPER_LOGICO_AND);
          $objAtividadeDTO->adicionarCriterio($a,$b,$arrPalavrasPesquisa,$d);
        }

        $arrResultado = array();
        $arrObjAtividadeDTO = $this->objAtividadeRN->listarRN0036($objAtividadeDTO);
        //$arrObjAtividadeDTOIndexado = $arrObjAtividadeDTO;
        $arrObjAtividadeDTOIndexado = InfraArray::indexarArrInfraDTO($arrObjAtividadeDTO, 'ProtocoloFormatadoProtocolo', true);

        foreach ($arrObjAtividadeDTOIndexado as $key => $value) {

          if(is_array($value) && count($value) == 1) {
            $arrResultado[] = $value[0];
          }                
        }

        $arrObjProcessosApensados = array_slice($arrResultado, 0, $numRegistros);

      } catch(Exception $e) {
        throw new InfraException("Error Processing Request", $e);            
      }

      return $arrObjProcessosApensados;
    }


    public function listarProcessosAbertos($dblIdProcedimentoAtual, $idUnidadeAtual){
        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDistinct(true);
        $objAtividadeDTO->retDblIdProtocolo();
        $objAtividadeDTO->retNumIdUnidade();
        //$objAtividadeDTO->setNumIdUnidade($idUnidadeAtual);
        $objAtividadeDTO->setDblIdProtocolo($dblIdProcedimentoAtual, InfraDTO::$OPER_DIFERENTE);            
        $objAtividadeDTO->setDthConclusao(null);
        $objAtividadeDTO->setStrStaEstadoProtocolo(ProtocoloRN::$TE_NORMAL);
        
        $arrObjAtividadeDTO = $this->objAtividadeRN->listarRN0036($objAtividadeDTO);
        
        $arrayProcedimentos = array();
        
        foreach($arrObjAtividadeDTO as $atividade){
            $arrayProcedimentos[$atividade->getDblIdProtocolo()][$atividade->getNumIdUnidade()] = 1;
        }
        
        return $arrayProcedimentos;
    }
    
    public function listarProcessosApensadosAvancado(AtividadeDTO $objAtividadeDTO, $dblIdProcedimentoAtual, $idUnidadeAtual, $strPalavrasPesquisa = '', $strDescricaoPesquisa = '', $numRegistros = 15) {

      $arrObjProcessosApensados = array();

      try{
        $objInfraException = new InfraException();
        $idUnidadeAtual = filter_var($idUnidadeAtual, FILTER_SANITIZE_NUMBER_INT);

        if(!$idUnidadeAtual){
          $objInfraException->adicionarValidacao('Processo inválido.');
        }
        
        $objInfraException->lancarValidacoes();    
            //Pesquisar procedimentos que estão abertos na unidade atual            
        
        $objAtividadeDTO->setDistinct(true);
        $objAtividadeDTO->retDblIdProtocolo();
        $objAtividadeDTO->retStrProtocoloFormatadoProtocolo();
        $objAtividadeDTO->retNumIdUnidade();
        $objAtividadeDTO->retStrDescricaoUnidadeOrigem();
        $objAtividadeDTO->setNumIdUnidade($idUnidadeAtual);
        $objAtividadeDTO->setDblIdProtocolo($dblIdProcedimentoAtual, InfraDTO::$OPER_DIFERENTE);            
        $objAtividadeDTO->setDthConclusao(null);
        $objAtividadeDTO->setStrStaEstadoProtocolo(ProtocoloRN::$TE_NORMAL);
        ///$objAtividadeDTO->setStrDescricaoProtocolo('%'.$strDescricaoPesquisa.'%',InfraDTO::$OPER_LIKE);
        
        $arrPalavrasPesquisa = explode(' ',$strPalavrasPesquisa);
        for($i=0; $i<count($arrPalavrasPesquisa); $i++) {
          $arrPalavrasPesquisa[$i] = '%'.$arrPalavrasPesquisa[$i].'%';
        }

        if (count($arrPalavrasPesquisa)==1){
          $objAtividadeDTO->setStrProtocoloFormatadoProtocolo($arrPalavrasPesquisa[0],InfraDTO::$OPER_LIKE);
        }else{
          $objAtividadeDTO->unSetStrProtocoloFormatadoProtocolo();
          $a = array_fill(0,count($arrPalavrasPesquisa),'ProtocoloFormatadoProtocolo');
          $b = array_fill(0,count($arrPalavrasPesquisa),InfraDTO::$OPER_LIKE);
          $d = array_fill(0,count($arrPalavrasPesquisa)-1,InfraDTO::$OPER_LOGICO_AND);
          $objAtividadeDTO->adicionarCriterio($a,$b,$arrPalavrasPesquisa,$d);
        }

        $arrResultado = array();
        $arrObjAtividadeDTO = $this->objAtividadeRN->listarRN0036($objAtividadeDTO);
        //$arrObjAtividadeDTOIndexado = $arrObjAtividadeDTO;
        $arrObjAtividadeDTOIndexado = InfraArray::indexarArrInfraDTO($arrObjAtividadeDTO, 'ProtocoloFormatadoProtocolo', true);

        foreach ($arrObjAtividadeDTOIndexado as $key => $value) {

          if(is_array($value) && count($value) == 1) {
            $arrResultado[] = $value[0];
          }                
        }

        $arrObjProcessosApensados = array_slice($arrResultado, 0, $numRegistros);

      } catch(Exception $e) {
        throw new InfraException("Error Processing Request", $e);            
      }

      return $arrObjProcessosApensados;
    }


    /**
     * Recebe o recibo de tramite do procedimento do barramento
     * 
     * @param int $parNumIdTramite
     * @return bool
     */
    protected function receberReciboDeEnvioControlado($parNumIdTramite){
        
        if (empty($parNumIdTramite)) {
            return false;
        }
        
        $objReciboTramiteEnviadoDTO = new ReciboTramiteEnviadoDTO();
        $objReciboTramiteEnviadoDTO->setNumIdTramite($parNumIdTramite);
        
        $objGenericoBD = new GenericoBD(BancoSEI::getInstance());
        
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
		    $objReciboEnvio->reciboDeEnvio->hashDoComponenteDigital = !is_array($objReciboEnvio->reciboDeEnvio->hashDoComponenteDigital) ? array($objReciboEnvio->reciboDeEnvio->hashDoComponenteDigital) : $objReciboEnvio->reciboDeEnvio->hashDoComponenteDigital;
		    if($objReciboEnvio->reciboDeEnvio->hashDoComponenteDigital && is_array($objReciboEnvio->reciboDeEnvio->hashDoComponenteDigital)){
		        
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
    }
    
    /**
     * Atualiza os dados do protocolo somente para o modulo PEN
     * 
     * @param int $dblIdProtocolo
     * @return null
     */
    private function atualizarPenProtocolo($dblIdProtocolo = 0){

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






// //---------------------------------------------------------------------------------------------//
// //                                      IMPLEMENTAÇÃO SOFTMAIS                                  //
// //---------------------------------------------------------------------------------------------//
//     public function consultarUnidadesHierarquia() {
//         $selRepositorioEstruturas = (int) $_POST['selRepositorioEstruturas'];

//         $i = 0;
//         $dadosArray = array();
//         $dadosArrayHierarquia = array();


//         $result = $this->objProcessoEletronicoRN->serviceConsultarEstruturas($selRepositorioEstruturas);

//         if (is_object($result->estruturasEncontradas->estrutura))
//             $result->estruturasEncontradas->estrutura = array($result->estruturasEncontradas->estrutura);

//         foreach ($result->estruturasEncontradas->estrutura as $estrutura) {

//             $j = 0;

//             if (isset($estrutura->nome))
//                 $dadosArray[$i]['estrutura'] = array('nome' => $estrutura->nome, 'numeroDeIdentificacaoDaEstrutura' => $estrutura->numeroDeIdentificacaoDaEstrutura);

//             if (isset($estrutura->hierarquia)) {
//                 if (is_object($estrutura->hierarquia->nivel))
//                     $estrutura->hierarquia->nivel = array($estrutura->hierarquia->nivel);
//                 foreach ($estrutura->hierarquia->nivel as $key => $nivel) {
//                     $dadosArrayHierarquia[$j] = array('nome' => $nivel->nome, 'numeroDeIdentificacaoDaEstrutura' => $nivel->numeroDeIdentificacaoDaEstrutura);
//                     $j++;
//                 }
//             }

//             $dadosArray[$i]['hierarquia'] = $dadosArrayHierarquia;


//             $i++;
//         }

//         echo json_encode($dadosArray);
//     }

    
//     /**
//      * @author Fabio.braga@softimais.com.br
//      * @deprecated  enviar docTramites
//      * data : 28/05/2015
//      * @return 1 
//      */
//     public function enviarDocProdimentoTramite()
//     {

//             $resultListaPendencia  = $this->consultarListarPendencias();
// //             echo "<pre>";
// //           var_dump($resultListaPendencia);
// //           exit;

//             foreach ($resultListaPendencia->listaDePendencias->IDT as $listaPendencia)
//             {  

//                 if($listaPendencia->status == 1  )
//                 {

//                  $resultTramite  = $this->objProcessoEletronicoRN->serviceConsultarTramitesComFiltros( $listaPendencia->_  );

//                  $resultDocumentos = $this->consultarProtocoloNumeroProtocolo($resultTramite->tramitesEncontrados->tramite->protocolo);

//                  foreach ($resultDocumentos->Documentos as  $key => $documentos)
//                  {

//                     $fp = fopen( 'modulos/pen/binario1.txt', "w+");
//                     fwrite($fp, $documentos->getStrConteudo() );
//                     fclose($fp);

//                   $caminhoRelativoArquivo = 'modulos/pen/binario1.txt';
//                   $conteudoComponenteDigital = file_get_contents($caminhoRelativoArquivo);


//                   $hashDoComponenteDigitalEmBytes = hash('sha256', $conteudoComponenteDigital, true);
//                   $hashDoComponenteDigital = base64_encode($hashDoComponenteDigitalEmBytes);

//                   $param = new stdClass();
//                   $param->dadosDoComponenteDigital = new stdClass();
//                   $param->dadosDoComponenteDigital->ticketParaEnvioDeComponentesDigitais = $listaPendencia->_ ;
//                   $param->dadosDoComponenteDigital->protocolo = $resultTramite->tramitesEncontrados->tramite->protocolo;

//                   $param->dadosDoComponenteDigital->hashDoComponenteDigital = $hashDoComponenteDigital;
//                   $param->dadosDoComponenteDigital->conteudoDoComponenteDigital = new SoapVar($conteudoComponenteDigital, XSD_BASE64BINARY);
//                   $this->objProcessoEletronicoRN->serviceEnviarComponenteDigital($param);


//                   $param = new stdClass();
//                   $param->IDT =$listaPendencia->_  ;

//                  $metadados = $this->objProcessoEletronicoRN->serviceSolicitarMetadados($param);

//                  }


//                 }


//             }





//     }
    
    
    
//     /**
//      * @author Fabio.braga@softimais.com.br
//      * @deprecated  consulta  processo  
//      * data : 28/05/2015
//      * @return objet 
//      */
    
//     public function consultarListarPendencias()
//     {

//      $result =  $this->objProcessoEletronicoRN->serviceListarTodasPendencias( );

//      return $result; 
//     }


//     /**
//      * @author Fabio.braga@softimais.com.br
//      * @deprecated  consulta  processo  
//      * data : 28/05/2015
//      * @return objet 
//      */
    
//     public function consultarListaPendenciasFiltro( $IDT )
//     {

//      $result =  $this->objProcessoEletronicoRN->serviceListarTodasPendencias( );

//      return $result; 
//     }

     /**
      * @author Fabio.braga@softimais.com.br
      * @deprecated  consulta  processo  
      * data : 28/05/2015
      * @return objet 
      */
     public function listarTramiteParaCancelar($idProcedimento) {
         $objProtocoloDTO  = $this->consultarProtocoloPk($idProcedimento);
         
         echo "DEBUG protocolo <hr> <pre>";
         var_dump($objProtocoloDTO);
         die("</pre>");


         $result = $this->objProcessoEletronicoRN->serviceConsultarTramitesProtocolo( $objProtocoloDTO->getStrProtocoloFormatado( ) );
         return $result;
     }
    
    
    
//         /**
//      * @author Fabio.braga@softimais.com.br
//      * @deprecated  consulta  processo  
//      * data : 28/05/2015
//      * @return objet 
//      */
//     public function listarTramiteIDT($iIDT) {



//         $result = $this->objProcessoEletronicoRN->serviceConsultarTramitesIDT( $iIDT );

//         return $result;

//     }
    
    

    /**
     * Cancela uma expedição de um Procedimento para outra unidade
     * 
     * @param int $dblIdProcedimento
     * @throws InfraException
     */
    public function cancelarTramite($dblIdProcedimento) {
      
        //Busca os dados do protocolo
        $objDtoProtocolo = new ProtocoloDTO();
        $objDtoProtocolo->retStrProtocoloFormatado();
        $objDtoProtocolo->retDblIdProtocolo();
        $objDtoProtocolo->setDblIdProtocolo($dblIdProcedimento);

        $objProtocoloBD = new ProtocoloBD($this->getObjInfraIBanco());
        $objDtoProtocolo = $objProtocoloBD->consultar($objDtoProtocolo);

        $this->cancelarTramiteInternoControlado($objDtoProtocolo);
        
    }
    
    protected function cancelarTramiteInternoControlado(ProtocoloDTO $objDtoProtocolo) {
        
        
        //Obtem o id_rh que representa a unidade no barramento
        $objPenParametroRN = new PenParametroRN();
        $numIdRespositorio = $objPenParametroRN->getParametro('PEN_ID_REPOSITORIO_ORIGEM');
        
        //Obtem os dados da unidade
        $objPenUnidadeDTO = new PenUnidadeDTO();
        $objPenUnidadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
        $objPenUnidadeDTO->retNumIdUnidadeRH();
                
        $objGenericoBD = new GenericoBD($this->inicializarObjInfraIBanco());
        $objPenUnidadeDTO = $objGenericoBD->consultar($objPenUnidadeDTO);
        
        $objTramiteDTO = new TramiteDTO();
        $objTramiteDTO->setNumIdProcedimento($objDtoProtocolo->retDblIdProtocolo());
        $objTramiteDTO->setOrd('Registro', InfraDTO::$TIPO_ORDENACAO_DESC);
        $objTramiteDTO->setNumMaxRegistrosRetorno(1);
        $objTramiteDTO->retNumIdTramite();
        
        $objTramiteBD = new TramiteBD($this->getObjInfraIBanco());
        $arrObjTramiteDTO = $objTramiteBD->listar($objTramiteDTO);
   
        if(!$arrObjTramiteDTO){
            throw new InfraException('Trâmite não encontrado para esse processo. ');
        }
        
        $objTramiteDTO = $arrObjTramiteDTO[0];
        
        //Armazena o id do protocolo
        $dblIdProcedimento = $objDtoProtocolo->getDblIdProtocolo();

        $tramites = $this->objProcessoEletronicoRN->consultarTramites($objTramiteDTO->getNumIdTramite(), null, $objPenUnidadeDTO->getNumIdUnidadeRH(), null, null, $numIdRespositorio);
        $tramite = $tramites ? $tramites[0] : null;

        if (!$tramite) {
            throw new InfraException('Trâmite não encontrado para esse processo. ');
        }

        //Verifica se o trâmite está com o status de iniciado
        if ($tramite->situacaoAtual == ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO) {
            $this->objProcessoEletronicoRN->cancelarTramite($tramite->IDT);

            return true;
        }

        //Busca o processo eletrônico
        $objDTOFiltro = new ProcessoEletronicoDTO();
        $objDTOFiltro->setDblIdProcedimento($dblIdProcedimento);
        $objDTOFiltro->retStrNumeroRegistro();
        $objDTOFiltro->setNumMaxRegistrosRetorno(1);

        $objBD = new ProcessoEletronicoBD($this->getObjInfraIBanco());
        $objProcessoEletronicoDTO = $objBD->consultar($objDTOFiltro);

        if (empty($objProcessoEletronicoDTO)) {
            throw new InfraException('Não foi Encontrado o Processo pelo ID ' . $dblIdProcedimento);
        }

        //Armazena a situação atual
        $numSituacaoAtual = $tramite->situacaoAtual;

        //Valida os status
        switch ($numSituacaoAtual) {
            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_ENVIADO_DESTINATARIO:
                throw new InfraException("O sistema destinatário já iniciou o recebimento desse processo, portanto não é possivel realizar o cancelamento");
                break;
            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE:
                throw new InfraException("O sistema destinatário já recebeu esse processo, portanto não é possivel realizar o cancelamento");
                break;
            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO:
                throw new InfraException("O processo já se encontra cancelado");
                break;
            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO:
                throw new InfraException("O processo se encontra recusado");
                break;
        }

        $this->objProcessoEletronicoRN->cancelarTramite($tramite->IDT);

        //Desbloqueia o processo
        $objEntradaDesbloquearProcessoAPI = new EntradaDesbloquearProcessoAPI();
        $objEntradaDesbloquearProcessoAPI->setIdProcedimento($dblIdProcedimento);

        $objSeiRN = new SeiRN();
        $objSeiRN->desbloquearProcesso($objEntradaDesbloquearProcessoAPI);

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

        //Cria o Objeto que registrará a Atividade de cancelamento
        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDblIdProtocolo($dblIdProcedimento);
        $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
        $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_CANCELADO));

        //Seta os atributos do tamplate de descrição dessa atividade
        $objAtributoAndamentoDTOHora = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTOHora->setStrNome('DATA_HORA');
        $objAtributoAndamentoDTOHora->setStrIdOrigem(null);
        $objAtributoAndamentoDTOHora->setStrValor(date('d/m/Y H:i'));

        $objAtributoAndamentoDTOUser = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTOUser->setStrNome('USUARIO');
        $objAtributoAndamentoDTOUser->setStrIdOrigem(null);
        $objAtributoAndamentoDTOUser->setStrValor(SessaoSEI::getInstance()->getStrNomeUsuario());

        $objAtividadeDTO->setArrObjAtributoAndamentoDTO(array($objAtributoAndamentoDTOHora, $objAtributoAndamentoDTOUser));

        $objAtividadeRN = new AtividadeRN();
        $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);
    }
    
    /**
     * Verifica se o processo se encontra em expedição
     * 
     * @param integer $parNumIdProcedimento
     * @return boolean|object
     */
    public function verificarProcessoEmExpedicao($parNumIdProcedimento){
        
        $objProcedimentoDTO = new ProcedimentoDTO();
        $objProcedimentoDTO->setDblIdProcedimento($parNumIdProcedimento);
        $objProcedimentoDTO->retStrStaEstadoProtocolo();
        $objProcedimentoDTO->retDblIdProcedimento();
                 
        $objProcedimentoRN = new ProcedimentoRN();
        $objProcedimentoDTO = $objProcedimentoRN->consultarRN0201($objProcedimentoDTO);
        
        
        if($objProcedimentoDTO && $objProcedimentoDTO->getStrStaEstadoProtocolo() == ProtocoloRN::$TE_PROCEDIMENTO_BLOQUEADO){
             
            $objAtividadeDTO = new AtividadeDTO();
            $objAtividadeDTO->setDblIdProtocolo($objProcedimentoDTO->getDblIdProcedimento());
            $objAtividadeDTO->setNumIdTarefa(
                    array(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO), 
                        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO),
                        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_CANCELADO),
                        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_RECUSADO),
                        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_EXTERNO),
                         ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_ABORTADO),
                    ), 
                    InfraDTO::$OPER_IN);
            $objAtividadeDTO->setNumMaxRegistrosRetorno(1);
            $objAtividadeDTO->setOrdDthAbertura(InfraDTO::$TIPO_ORDENACAO_DESC);
            $objAtividadeDTO->retNumIdAtividade();
            $objAtividadeDTO->retNumIdTarefa();
            
            $objAtividadeRN = new AtividadeRN();
            $arrAtividadeDTO = (array) $objAtividadeRN->listarRN0036($objAtividadeDTO);
            
            if($arrAtividadeDTO){
                return $arrAtividadeDTO[0];
            }else{
                return false;
            }
           
            
        }else{
            return false;
        }
        
    }
    

//   // private function validarStrSinGerarPendenciaRN0901(ProcedimentoDTO $objProcedimentoDTO, InfraException $objInfraException){
//   //   if (InfraString::isBolVazia($objProcedimentoDTO->getStrSinGerarPendencia())){
//   //     $objInfraException->adicionarValidacao('Sinalizador de geração de andamento automático não informado.');
//   //   }else{
//   //     if (!InfraUtil::isBolSinalizadorValido($objProcedimentoDTO->getStrSinGerarPendencia())){
//   //       $objInfraException->adicionarValidacao('Sinalizador de geração de andamento automático inválido.');
//   //     }
//   //   }    
//   // }


//     // public function consultarProcessosApensados($idProcedimento) {

//     //     $protocolosArray = array();
//     //     $idProcedimento = filter_var($idProcedimento, FILTER_SANITIZE_NUMBER_INT);

//     //     $objExpedirProcedimenroBD = new ExpedirProcedimentoBD($this->inicializarObjInfraIBanco());
//     //     $valoresProtocolo = $objExpedirProcedimenroBD->buscarProcessosAnexados($idProcedimento);

//     //     foreach ($valoresProtocolo as $protocolos) {
//     //         $resultProtocolo = $this->validarProcessoAbertoUnidade($protocolos->id_protocolo);
//     //         if ($resultProtocolo['retorno'] == 1)
//     //             $protocolosArray[] = $protocolos;
//     //     }


//     //     return $protocolosArray;
//     // }



//     public function consultarProcessosApensadosDetalahar($idPrtocedimento) {

//         $idPrtocedimento = (int) $idPrtocedimento[0];

//         $objProtocoloDTO = new ProtocoloDTO();

//         $objProtocoloDTO->setDblIdProtocolo($idPrtocedimento);
//         $objProtocoloDTO->retTodos();
//         $objProtocoloDTO = $this->objProtocoloRN->consultarRN0186($objProtocoloDTO);


//         $objProtocoloDTO->UnidadeGeradora = $this->consultaUnidadePk($objProtocoloDTO->getNumIdUnidadeGeradora());
//         $objProtocoloDTO->UsuarioCriador = $this->consultaUsuarioPk($objProtocoloDTO->getNumIdUsuarioGerador());


//         return $objProtocoloDTO;
//     }

     public function consultaUnidadePk($idUnidade) {

         $objUnidadeDTO = new UnidadeDTO();
         $objUnidadeDTO->setNumIdUnidade($idUnidade);
         $objUnidadeDTO->retTodos();

         $objUnidadeDTO = $this->objUnidadeRN->consultarRN0125($objUnidadeDTO);

         return $objUnidadeDTO;
     }

     public function consultaUsuarioPk($idUsuario) {

         $objUsuarioDTO = new UsuarioDTO();
         $objUsuarioDTO->setNumIdUsuario($idUsuario);
         $objUsuarioDTO->retTodos();

         $objUsuarioDTO = $this->objUsuarioRN->consultarRN0489($objUsuarioDTO);

         return $objUsuarioDTO;
     }

//     public function setValoresModal() {
//         session_start();
//         $this->limparValoresModal();
//         $_SESSION['param'] = (array) $_POST['param'];
//     }

//     public function getValoresModal() {
//         if (isset($_SESSION['param']))
//             return $_SESSION['param'];
//     }

//     public function limparValoresModal() {
//         unset($_SESSION["param"]);
//     }

     public function consultarProtocoloPk($idPrtocedimento) {

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

    
//        /**
//      * @author Fabio.braga@softimais.com.br
//      * @deprecated  consulta  processo  
//      * data : 28/05/2015
//      * @return objet 
//      */
//     public function consultarProtocoloNumeroProtocolo($protocolo) 
//     {  
//         $objProtocoloDTO = new ProtocoloDTO();
//         $objProtocoloDTO->setStrProtocoloFormatado($protocolo);
//         $objProtocoloDTO->retTodos();
//         $objProtocoloDTO = $this->objProtocoloRN->consultarRN0186($objProtocoloDTO);


//         $objProtocoloDTO->UnidadeGeradora = $this->consultaUnidadePk($objProtocoloDTO->getNumIdUnidadeGeradora());
//         $objProtocoloDTO->UsuarioCriador = $this->consultaUsuarioPk($objProtocoloDTO->getNumIdUsuarioGerador());

//         $objProtocoloDTO->Documentos = $this->consultaDocumentosProcesso($objProtocoloDTO->getDblIdProtocolo());

//         return $objProtocoloDTO;
//     }


     public function consultaDocumentosProcesso($idPrtocedimento) {

         $documentoRespArray = array();

         $documentoDTO = new DocumentoDTO();
         $documentoDTO->setDblIdProcedimento($idPrtocedimento);
         $documentoDTO->retTodos();

         $documentoDTO = $this->objDocumentoRN->listarRN0008($documentoDTO);

         return $documentoDTO;
     }
    
    

//     // public function consultaProcessoStatus($idProcedimento) {

//     //     $objProtocoloDTO = new ProtocoloDTO();
//     //     $objProtocoloDTO->setDblIdProtocolo($idProcedimento);
//     //     $objProtocoloDTO->retTodos();

//     //     $objProtocoloDTO = $this->objProtocoloRN->consultarRN0186($objProtocoloDTO);

//     //     if($objProtocoloDTO->getStrStaNivelAcessoLocal( ) != 0 )
//     //     {
//     //         return array('retorno'=>0);
//     //     }
//     //         return array('retorno'=>1);
//     // }

//     public function converterDataWebService($dataHora) 
//     {
//         $resultado = '';
//         if(isset($dataHora)){
//             $resultado = InfraData::getTimestamp($dataHora);
//             $resultado = date(DateTime::W3C, $resultado);
//         }

//         return $resultado;
//     }

//     public function converterDataSEI($dataHoraWebService) 
//     {
//         $resultado = '';
//         if(isset($dataHoraWebService)){
//             $resultado = strtotime($dataHoraWebService);
//             $resultado = date('d/m/Y H:i:s', $resultado);
//         }

//         return $resultado;
//     }

//     /**
//      * @deprecated   Processo deve ser bloqueado após expedição
//      * data 28/05/2015
//      * @author Fabiol Braga <fabio.braga@softimais.com.br>
//      * 
//      */
//     public function bloquearProcesso($idProcedimento) {
//         $idProcedimento = filter_var($idProcedimento, FILTER_SANITIZE_NUMBER_INT);

//         $objExpedirProcedimenroBD = new ExpedirProcedimentoBD($this->inicializarObjInfraIBanco());
//         $resultProtocolo = $objExpedirProcedimenroBD->alterarProcesso(" sta_estado = 1 ", $idProcedimento);

//         return $resultProtocolo;
//     }
    
//     /**
//      * @deprecated valida se o processo se encontra em expedição
//      * @author Fabio Braga <fabio.braga@softimais.com.bt>
//      * data 01/06/2015
//      * 
//      */
    
//     public function validarSeProcessoExpedicao($idPrtocedimento)
//     {
//        $menssagem = ' ';
//        $retorno = 1;

//       $resultListaTramiter =  $this->listarTramiteParaCancelar($idPrtocedimento);


//       if(is_object($resultListaTramiter->tramitesEncontrados->tramite))
//           $resultListaTramiter->tramitesEncontrados->tramite = array($resultListaTramiter->tramitesEncontrados->tramite);

//       $resultTodasPendencias = $this->objProcessoEletronicoRN->serviceListarTodasPendencias();




//       foreach ($resultListaTramiter->tramitesEncontrados->tramite as $tramite) 
//       {


//         foreach ($resultTodasPendencias->listaDePendencias as $lista)
//         {
//             if( $lista->IDT  == $tramite->IDT)
//             {
//                 $menssagem = 'Esse processo ja foi expedido  ! ';
//                 $retorno = 0;
//                 break;
//             }
//         }

//         if($retorno === 0)
//            break;

//       } 

//       return array ('mensagem'=>$menssagem,'retorno'=>$retorno) ;
//     }

//     public function gravarAuditoria($metodo, $idPrtocedimento) {
//         $objExpedirProcedimentoBD = new ExpedirProcedimentoBD($this->inicializarObjInfraIBanco());
//         $result = $this->consultarProtocoloPk($idPrtocedimento);

//         $resultUltimaAuditoria = $objExpedirProcedimentoBD->consultaUltmaAuditoria();

//         $dados = array
//             (
//             'id_infra_auditoria' => $resultUltimaAuditoria->id_infra_auditoria + 1,
//             'recurso' => 'pen_procedimento_expedir',
//             'dth_acesso' => date('Y-m-d H:m:i'),
//             'ip' => $_SERVER['REMOTE_ADDR'],
//             'id_usuario' => SessaoSEI::getInstance()->getNumIdUsuario(),
//             'sigla_usuario' => SessaoSEI::getInstance()->getStrSiglaUsuario(),
//             'nome_usuario' => SessaoSEI::getInstance()->getStrNomeUsuario(),
//             'id_orgao_usuario' => SessaoSEI::getInstance()->getNumIdOrgaoUsuario(),
//             'sigla_orgao_usuario' => SessaoSEI::getInstance()->getStrSiglaOrgaoUsuario(),
//             'id_unidade' => SessaoSEI::getInstance()->getNumIdUnidadeAtual(),
//             'sigla_unidade' => SessaoSEI::getInstance()->getStrSiglaUnidadeAtual(),
//             'servidor' => $_SERVER['REMOTE_ADDR'],
//             'user_agent' => $_SERVER['HTTP_USER_AGENT'],
//             'requisicao' => $metodo,
//             'operacao' => 'Expedir Processo',
//         );


//         $objExpedirProcedimentoBD->gravarAuditoria($dados);
//     }

//     public function cadeiaDoCertificado() {
//         return $cadeiaDoCertificadoPEM = "-----BEGIN CERTIFICATE-----
// MIIHZTCCBU2gAwIBAgIQMjAxMjA5MDQxODEzNTI1MTANBgkqhkiG9w0BAQsFADCB
// pjELMAkGA1UEBhMCQlIxEzARBgNVBAoTCklDUC1CcmFzaWwxOzA5BgNVBAsTMlNl
// cnZpY28gRmVkZXJhbCBkZSBQcm9jZXNzYW1lbnRvIGRlIERhZG9zIC0gU0VSUFJP
// MQ8wDQYDVQQLEwZDU1BCLTExNDAyBgNVBAMTK0F1dG9yaWRhZGUgQ2VydGlmaWNh
// ZG9yYSBkbyBTRVJQUk8gRmluYWwgdjMwHhcNMTIwOTA0MTgyNTIzWhcNMTUwOTA0
// MTgxNzIzWjCBnjELMAkGA1UEBhMCQlIxEzARBgNVBAoTCklDUC1CcmFzaWwxKzAp
// BgNVBAsTIkF1dG9yaWRhZGUgQ2VydGlmaWNhZG9yYSBTRVJQUk9BQ0YxETAPBgNV
// BAsTCE1QLVNJQVBFMRkwFwYDVQQLExBQZXNzb2EgRmlzaWNhIEEzMR8wHQYDVQQD
// ExZXQUdORUwgQUxWRVMgUk9EUklHVUVTMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8A
// MIIBCgKCAQEAu9ixyMUZZDbZ27js6bv2PgRr+XFgMYgzIJOnX7X3bx/5h3niBD5s
// YUiCFrGwO+DxonhNRFHwp6YLZfEIZC7xaC94SXqYijeJxlPfCzLWwh87AqFv+qmc
// HvyK0j5g3EeiDnWDk3M/PMQmKiI2qehhP0P+f8J75QqGvZHUfIUy18QCsi47+6zJ
// 04AbzJPPSCJohlrLgSTs49ju6HIbpxojCn0Je3oIVRumn7Gl3eTJuxGdjUwn9wQB
// fUV4D7AuD6eKdRfpoqZwiEDvga9/OtCd9Nk/4kCt+3+C7xNDt2gH0Uq79dibGbjv
// xKsfSjTE0WM/xcyNEHGuLFxPfpt0QMuK2QIDAQABo4ICkzCCAo8wDAYDVR0TAQH/
// BAIwADAfBgNVHSMEGDAWgBTafg+Cizblsgaq9mtG4adzriNffTAOBgNVHQ8BAf8E
// BAMCBeAwXAYDVR0gBFUwUzBRBgZgTAECAw0wRzBFBggrBgEFBQcCARY5aHR0cHM6
// Ly9jY2Quc2VycHJvLmdvdi5ici9zZXJwcm9hY2YvZG9jcy9kcGNzZXJwcm9hY2Yu
// cGRmMIG2BgNVHREEga4wgaugPQYFYEwBAwGgNAQyMTAwODE5NzQ2NDYwNzc2MzE4
// NzEyMzQ4NTUyOTc1MDAwMDAwMDAxNTM2NjcwU1NQREagNAYFYEwBAwWgKwQpMDEx
// MDI5MzQyMDAzMDI4MDE0MkFHVUFTIExJTkRBUyBERSBHT0lBR0+gFwYFYEwBAwag
// DgQMMDAwMDAwMDAwMDAwgRt3YWduZWwucm9kcmlndWVzQGFndS5nb3YuYnIwLAYD
// VR0lAQH/BCIwIAYIKwYBBQUHAwIGCCsGAQUFBwMEBgorBgEEAYI3FAICMIG6BgNV
// HR8EgbIwga8wMqAwoC6GLGh0dHA6Ly9jY2Quc2VycHJvLmdvdi5ici9sY3Ivc2Vy
// cHJvYWNmdjMuY3JsMDOgMaAvhi1odHRwOi8vY2NkMi5zZXJwcm8uZ292LmJyL2xj
// ci9zZXJwcm9hY2Z2My5jcmwwRKBCoECGPmh0dHA6Ly9yZXBvc2l0b3Jpby5pY3Bi
// cmFzaWwuZ292LmJyL2xjci9zZXJwcm8vc2VycHJvYWNmdjMuY3JsMEwGCCsGAQUF
// BwEBBEAwPjA8BggrBgEFBQcwAoYwaHR0cDovL2NjZC5zZXJwcm8uZ292LmJyL2Nh
// ZGVpYXMvc2VycHJvYWNmdjMucDdiMA0GCSqGSIb3DQEBCwUAA4ICAQAYReCBluZ2
// Nb9kALBGGIZMlefk2s1G0q/MBoMnp58xjLf9qQneHWww/fNeXhVAJPvbDJgZB+Fx
// mJTINU1Fk7NvqUZqEkL5rmTIyPcORcNHQ70ityDdlx/xCGXtFpPQGo8JlAPNOj2S
// /nnn6wovElwGzLUTliejSdo4VadfBrJ7T0ZomcX5Mag7VBuoAfWo5QH9CrUZ/ryC
// XlAmPEUqfAqt4IrT4phTybVozsPm3FoJgD52p2FN127gKmZBugt5wUvx6ulDTd/s
// 1zqTRL/c135SKt1e2kSR0K4zmCNpXRJmYd8kWTHbj9MP4NhAmfMSKJaqDVrGU3pH
// ei899F15H+Q/Nr3jECfn81XvwRupVq9INWB8Y55Ev6nrh7/oLtI5sjhl71IEP5aw
// AFGOq0M6+R6iFMi26SfccalgmhQ92+7Vh04j64SsycOPl+ryiVUZbkpf6QCm8cmT
// vLn6GTezCafazuTn3mEIIJSZ8kNqh3j3uT70fvPAnBSCbhceo6NLkVFDYvJ4sS/I
// BOuAozQzb+pZfKKAyIyIpAKxxXNWArYLzam905xyFKy7tacDZDb1nkRodoENJxIY
// V93cG4m1xG4ihstkquxG4R7aUdPxAuZsWp5evFdTJU4JSawZGsKzuD1jxQwz9zPD
// eZhzZfrZ5dG0GvQcM8XCJ6u1TXYRrZakUg==
// -----END CERTIFICATE-----
// -----BEGIN CERTIFICATE-----
// MIIIdjCCBl6gAwIBAgIBATANBgkqhkiG9w0BAQ0FADCBhTELMAkGA1UEBhMCQlIx
// EzARBgNVBAoTCklDUC1CcmFzaWwxNDAyBgNVBAsTK0F1dG9yaWRhZGUgQ2VydGlm
// aWNhZG9yYSBSYWl6IEJyYXNpbGVpcmEgdjIxKzApBgNVBAMTIkF1dG9yaWRhZGUg
// Q2VydGlmaWNhZG9yYSBTRVJQUk8gdjMwHhcNMTExMTE2MTMzNzI3WhcNMTkxMTE2
// MTMzNzI3WjCBpjELMAkGA1UEBhMCQlIxEzARBgNVBAoTCklDUC1CcmFzaWwxOzA5
// BgNVBAsTMlNlcnZpY28gRmVkZXJhbCBkZSBQcm9jZXNzYW1lbnRvIGRlIERhZG9z
// IC0gU0VSUFJPMQ8wDQYDVQQLEwZDU1BCLTExNDAyBgNVBAMTK0F1dG9yaWRhZGUg
// Q2VydGlmaWNhZG9yYSBkbyBTRVJQUk8gRmluYWwgdjMwggIiMA0GCSqGSIb3DQEB
// AQUAA4ICDwAwggIKAoICAQDm66/6/+CW5eKxXP0KUXdRBpKzcOY0MF4O4bV2bi+N
// a7dcCRSJW7AZ9Vt3+iqRokfFa55mvNvZobWXrybvbY9nj1GJ//nGFaqRQLUFqOuj
// l9gH4QTQPWLfiUGg229KYWAwzjEZs/kC87RNZPGlz5Wf1B4KpUo3YpN2VSJgj3h/
// qDDKp0ONBQGtZZLcf2BP+f0xpbLUIJJ+/RAEuAyiCK9Xs/lx87wWa6lOrunh/PIY
// EH5XP4ai7oB/JUtnEs0Q3Ud66ygRrpHo7QozEVzwE7Ujib9vKFWteCpYgavgemPU
// btLvUtk6lMMvFCxEjNyqq5tZHACw71ppOv4jvkPBvS4Rm2Fn01glCAZau3EO44N+
// G6oSkx2C22+VPPQsTvYfzz/CHw6f8C5Mkx6bnysC+KoY0NhZgIlsZVgr/fLtgETs
// 8Z56tUU2/U6o+5pIKfhjHgbFN5Cy9jk5I3tW/Dvj6wFYNLBZ7YMMPMNCBufPVobP
// 7TOGVRI9UNMvbgD3fFHgLKRWjs+oXxzs51FufhwM9yDmFUe2U1xh2WLNDROSJaaK
// chbk9cLfmukEvjwllwXWwd9NVd3s8+WpOG/s0dOsJHVOMOJh+Jq2SeNrfcizfEyF
// 4wfZ8dflOHra2Rrc4FBl2kyF84Y9gLRxv6RaEgE6mtfoubppxyZ/z8ZiRrH0q5AF
// dQIDAQABo4ICzDCCAsgwDgYDVR0PAQH/BAQDAgEGMIIB7gYDVR0gBIIB5TCCAeEw
// TgYGYEwBAgEQMEQwQgYIKwYBBQUHAgEWNmh0dHA6Ly9jY2Quc2VycHJvLmdvdi5i
// ci9hY3NlcnByby9kb2NzL2RwY2Fjc2VycHJvLnBkZjBOBgZgTAECAw0wRDBCBggr
// BgEFBQcCARY2aHR0cDovL2NjZC5zZXJwcm8uZ292LmJyL2Fjc2VycHJvL2RvY3Mv
// ZHBjYWNzZXJwcm8ucGRmME4GBmBMAQIBETBEMEIGCCsGAQUFBwIBFjZodHRwOi8v
// Y2NkLnNlcnByby5nb3YuYnIvYWNzZXJwcm8vZG9jcy9kcGNhY3NlcnByby5wZGYw
// TgYGYEwBAmUMMEQwQgYIKwYBBQUHAgEWNmh0dHA6Ly9jY2Quc2VycHJvLmdvdi5i
// ci9hY3NlcnByby9kb2NzL2RwY2Fjc2VycHJvLnBkZjBOBgZgTAECZwowRDBCBggr
// BgEFBQcCARY2aHR0cDovL2NjZC5zZXJwcm8uZ292LmJyL2Fjc2VycHJvL2RvY3Mv
// ZHBjYWNzZXJwcm8ucGRmME8GB2BMAQKCLwMwRDBCBggrBgEFBQcCARY2aHR0cDov
// L2NjZC5zZXJwcm8uZ292LmJyL2Fjc2VycHJvL2RvY3MvZHBjYWNzZXJwcm8ucGRm
// MHAGA1UdHwRpMGcwMaAvoC2GK2h0dHA6Ly9jY2Quc2VycHJvLmdvdi5ici9sY3Iv
// YWNzZXJwcm92My5jcmwwMqAwoC6GLGh0dHA6Ly9jY2QyLnNlcnByby5nb3YuYnIv
// bGNyL2Fjc2VycHJvdjMuY3JsMB8GA1UdIwQYMBaAFMjW6vmDj0xYOxzZi5uZd2Wv
// yVjaMB0GA1UdDgQWBBTafg+Cizblsgaq9mtG4adzriNffTASBgNVHRMBAf8ECDAG
// AQH/AgEAMA0GCSqGSIb3DQEBDQUAA4ICAQBdidAAyIUZigjF5ohrws487tcL6a6H
// h/nMOoY/bKjxHu4vkcfinfkvN1kIxV5mDqjQvVWG5TOZeazcit7UcbySvwH+eErm
// gmh1WlId2hCjfkRFRiQMzjN1zuLZkYY02hr0TtFvi604ZS6AGecOuMZBRJIgfDv6
// lWHMxaH+3VFy7GGgW4DdHvqjNWKLVU95weqpdOWBY5gkVB4NaxOXd4l0652y3Znw
// mIf0QP2ZsAG9OBzdexBDOzSbH8460X1ueIQ5SpK3CPCo3H1qn9Z9PM9X0HPoopKh
// iSPSxAa7tYGO3i/2YW0/Y+oLdtGjyZo4/tuQtrWmmvHw3ckr4V/uOHbdGV6f+etX
// lQeXOvhKDEZUhdjRCtPZLlASrsSWSk9BIWDcBEdNVww19RX9QsOFOyS06syqb8DF
// j3jn5envTgrTBloeuby2/nm62gqGSgszpqaNYeEc3oQsMdTdZgy/k4OqYMQbUShs
// ogJOOw4i3dY7cz+Ub22XVM65c5DrjOmXpQTUqw+i4QpK008weXRmLE3W0ujDulDO
// QkGSwk4q1JNibx2zbgfjzqTG9mj0XfIIMSnnhsg0Xl0IZjIIEPGSqmShV5zmIyrC
// rH/+BkKPFZa4AZFu+QNhZGFJlTkM27nJG4/6gjIgXveLKs4boE2KuMIK5L9M75pF
// xn7r9ET0CLYOZg==
// -----END CERTIFICATE-----
// -----BEGIN CERTIFICATE-----
// MIIGVTCCBD2gAwIBAgIBBzANBgkqhkiG9w0BAQ0FADCBlzELMAkGA1UEBhMCQlIx
// EzARBgNVBAoTCklDUC1CcmFzaWwxPTA7BgNVBAsTNEluc3RpdHV0byBOYWNpb25h
// bCBkZSBUZWNub2xvZ2lhIGRhIEluZm9ybWFjYW8gLSBJVEkxNDAyBgNVBAMTK0F1
// dG9yaWRhZGUgQ2VydGlmaWNhZG9yYSBSYWl6IEJyYXNpbGVpcmEgdjIwHhcNMTEx
// MDIxMTIwMjQ3WhcNMjExMDIxMTIwMjQ3WjCBhTELMAkGA1UEBhMCQlIxEzARBgNV
// BAoTCklDUC1CcmFzaWwxNDAyBgNVBAsTK0F1dG9yaWRhZGUgQ2VydGlmaWNhZG9y
// YSBSYWl6IEJyYXNpbGVpcmEgdjIxKzApBgNVBAMTIkF1dG9yaWRhZGUgQ2VydGlm
// aWNhZG9yYSBTRVJQUk8gdjMwggIiMA0GCSqGSIb3DQEBAQUAA4ICDwAwggIKAoIC
// AQDxb+z1eCFkAqtiDxv/Qyh/kyuyjnAWQKG47bumr+CvI6XYU9i3rgJCrsh3qh1Q
// aANDTxMi2IjUOsGop5rd1hvMS20KaBNgz8JKmsoaeJxtk2lQNX5jQMeJPXbW+qHg
// LpIHBe5UWAmkhNSg01RejukOndR13KpKXBRjfD1EuT8YrbVHItUKFacGUQdP3ud7
// ds6jGeDdVMywmKUIMbREnZQukMtN0COFiHMI+DeEhwupp1+8xRyCbOtD/yw7/Xea
// hGpDnpQPqpbkb9hT7cjAtjVsZZt/CwlqAUSgO2/fsFb4NWd5s76edq0qvfLv4AKj
// hzzB8LHAb0R+DMEDSfseJ/BDFkg9+EqWMDROSnVakQegUmx8sfOMF7aF66uNf7r6
// 8rwVpch01UGPQqVFvJTLLUpOKPAHYMZ1zT9V39+X2NwmAFfjw5yjDQZ5rBYkm3V9
// /i65/nI4XKAEL6+a3kcEbZjTmX2EwCzvpTKWt6dE2L5LvdMHkp8jPpC72/25zPSM
// 3xGQAGnQ9wkL3wQLu+ya2xHi4xDkP6T7ELTBZEN2Kqfk2/2ZKhjrs6ImjbI5KOqs
// //qqLQLkf9ij0AUiOIozbrWbHQmpOXI6SZzm32C8ES5+4HebILB9d6GVJva09hyG
// gesuaIWZe4tGnXm7QIqHBDu+iHOgVA7hWG9/K/meA+MqvQIDAQABo4G7MIG4MB0G
// A1UdDgQWBBTI1ur5g49MWDsc2YubmXdlr8lY2jAPBgNVHRMBAf8EBTADAQH/MA4G
// A1UdDwEB/wQEAwIBBjAUBgNVHSAEDTALMAkGBWBMAQECMAAwPwYDVR0fBDgwNjA0
// oDKgMIYuaHR0cDovL2FjcmFpei5pY3BicmFzaWwuZ292LmJyL0xDUmFjcmFpenYy
// LmNybDAfBgNVHSMEGDAWgBQMOSA6twEfy9cofUGgx/pKrTIkvjANBgkqhkiG9w0B
// AQ0FAAOCAgEAIybRY3G1KgJb33HvcDnNPEdFpC1J88C+hTWZuQyqamb85Eaiwl3Q
// tiw59W+7u4LMrZdJJuur4NYHUNzeHfs3Ce/sVULf2Ord1d7VvQPKSwrOdPIDuMbB
// vQRyZgSPy9kpPSQZ+h60+kqG8er39eNnqcCj1J17TpQZpWjKJn8hocqGCGeY7Tu8
// XaPKhzshDqPCwWEvQB0uj8Mt/OSKcarvzBpmPlcotH7dcdKP0Ur0UNfQLqD7Yu/E
// DSGQ/WqQ2nS3vIxeRL2ULn3Lq27EWaWmRD2mfQHyE5yCUUoWwDrJJrkE5u8dGG61
// VcNaKtZYwaHHTQuf8LiV7pK4NqcjCNXX9SvKvz1DydFAJheBlPaJ1xnzSbD4tPeR
// VqkXC6WlJBGXcREYt1EUpJ+LESkQy1j9ooerXUcNnZKipBYxFyB3WTKaGjrJ1JDx
// zHe7x402F3jQKDIILRekN0UrRjygDUaMmH/RikTVPFt2+f2c4FqdvVTdXJ7zjAgO
// 92z0QzjSEZBSLaVvQSr2kEV9C2n0CsCuztHu6PycpwZeZS/eH/yk8LMZAH76TG0l
// Et+CKwNUUPgjK+fIedCs87sxU2QQ09pDptAkKQWm3fPotWaVD4NrHivZj4tX8W/I
// soDk7v6OV+H2B9eJ2Rsdszr0abUOIrf8cMtPG2wvDZ5wXAxy5jL73VM=
// -----END CERTIFICATE-----
// -----BEGIN CERTIFICATE-----
// MIIGoTCCBImgAwIBAgIBATANBgkqhkiG9w0BAQ0FADCBlzELMAkGA1UEBhMCQlIx
// EzARBgNVBAoTCklDUC1CcmFzaWwxPTA7BgNVBAsTNEluc3RpdHV0byBOYWNpb25h
// bCBkZSBUZWNub2xvZ2lhIGRhIEluZm9ybWFjYW8gLSBJVEkxNDAyBgNVBAMTK0F1
// dG9yaWRhZGUgQ2VydGlmaWNhZG9yYSBSYWl6IEJyYXNpbGVpcmEgdjIwHhcNMTAw
// NjIxMTkwNDU3WhcNMjMwNjIxMTkwNDU3WjCBlzELMAkGA1UEBhMCQlIxEzARBgNV
// BAoTCklDUC1CcmFzaWwxPTA7BgNVBAsTNEluc3RpdHV0byBOYWNpb25hbCBkZSBU
// ZWNub2xvZ2lhIGRhIEluZm9ybWFjYW8gLSBJVEkxNDAyBgNVBAMTK0F1dG9yaWRh
// ZGUgQ2VydGlmaWNhZG9yYSBSYWl6IEJyYXNpbGVpcmEgdjIwggIiMA0GCSqGSIb3
// DQEBAQUAA4ICDwAwggIKAoICAQC6RqQO3edA8rWgfFKVV0X8bYTzhgHJhQOtmKvS
// 8l4Fmcm7b2Jn/XdEuQMHPNIbAGLUcCxCg3lmq5lWroG8akm983QPYrfrWwdmlEIk
// nUasmkIYMPAkqFFB6quV8agrAnhptSknXpwuc8b+I6Xjps79bBtrAFTrAK1POkw8
// 5wqIW9pemgtW5LVUOB3yCpNkTsNBklMgKs/8dG7U2zM4YuT+jkxYHPePKk3/xZLZ
// CVK9z3AAnWmaM2qIh0UhmRZRDTTfgr20aah8fNTd0/IVXEvFWBDqhRnLNiJYKnIM
// mpbeys8IUWG/tAUpBiuGkP7pTcMEBUfLz3bZf3Gmh3sVQOQzgHgHHaTyjptAO8ly
// UN9pvvAslh+QtdWudONltIwa6Wob+3JcxYJU6uBTB8TMEun33tcv1EgvRz8mYQSx
// Epoza7WGSxMr0IadR+1p+/yEEmb4VuUOimx2xGsaesKgWhLRI4lYAXwIWNoVjhXZ
// fn03tqRF9QOFzEf6i3lFuGZiM9MmSt4c6dR/5m0muTx9zQ8oCikPm91jq7mmRxqE
// 14WkA2UGBEtSjYM0Qn8xjhEu5rNnlUB+l3pAAPkRbIM4WK0DM1umxMHFsKwNqQbw
// pmkBNLbp+JRITz6mdQnsSsU74MlesDL/n2lZzzwwbw3OJ1fsWhto/+xPb3gyPnnF
// tF2VfwIDAQABo4H1MIHyME4GA1UdIARHMEUwQwYFYEwBAQAwOjA4BggrBgEFBQcC
// ARYsaHR0cDovL2FjcmFpei5pY3BicmFzaWwuZ292LmJyL0RQQ2FjcmFpei5wZGYw
// PwYDVR0fBDgwNjA0oDKgMIYuaHR0cDovL2FjcmFpei5pY3BicmFzaWwuZ292LmJy
// L0xDUmFjcmFpenYyLmNybDAfBgNVHSMEGDAWgBQMOSA6twEfy9cofUGgx/pKrTIk
// vjAdBgNVHQ4EFgQUDDkgOrcBH8vXKH1BoMf6Sq0yJL4wDwYDVR0TAQH/BAUwAwEB
// /zAOBgNVHQ8BAf8EBAMCAQYwDQYJKoZIhvcNAQENBQADggIBAFmaFGkYbX0pQ3B9
// dpth33eOGnbkqdbLdqQWDEyUEsaQ0YEDxa0G2S1EvLIJdgmAOWcAGDRtBgrmtRBZ
// SLp1YPw/jh0YVXArnkuVrImrCncke2HEx5EmjkYTUTe2jCcK0w3wmisig4OzvYM1
// rZs8vHiDKTVhNvgRcTMgVGNTRQHYE1qEO9dmEyS3xEbFIthzJO4cExeWyCXoGx7P
// 34VQbTzq91CeG5fep2vb1nPSz3xQwLCM5VMSeoY5rDVbZ8fq1PvRwl3qDpdzmK4p
// v+Q68wQ2UCzt3h7bhegdhAnu86aDM1tvR3lPSLX8uCYTq6qz9GER+0Vn8x0+bv4q
// SyZEGp+xouA82uDkBTp4rPuooU2/XSx3KZDNEx3vBijYtxTzW8jJnqd+MRKKeGLE
// 0QW8BgJjBCsNid3kXFsygETUQuwq8/JAhzHVPuIKMgwUjdVybQvm/Y3kqPMFjXUX
// d5sKufqQkplliDJnQwWOLQsVuzXxYejZZ3ftFuXoAS1rND+Og7P36g9KHj41hJ2M
// gDQ/qZXow63EzZ7KFBYsGZ7kNou5uaNCJQc+w+XVaE+gZhyms7ZzHJAaP0C5GlZC
// cIf/by0PEf0e//eFMBUO4xcx7ieVzMnpmR6Xx21bB7UFaj3yRd+6gnkkcC6bgh9m
// qaVtJ8z2KqLRX4Vv4EadqtKlTlUO
// -----END CERTIFICATE-----";
//     }

  }
