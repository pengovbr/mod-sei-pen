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
        $this->objProcedimentoAndamentoRN = new ProcedimentoAndamentoRN();

        $this->barraProgresso = new InfraBarraProgresso();
        $this->barraProgresso->setNumMin(0);
        $this->barraProgresso->setNumMax(ProcessoEletronicoINT::NEE_EXPEDICAO_ETAPA_CONCLUSAO);
    }

    protected function inicializarObjInfraIBanco()
    {
        return BancoSEI::getInstance();
    }

    public function expedirProcedimentoControlado(ExpedirProcedimentoDTO $objExpedirProcedimentoDTO)
    {
        $numIdTramite = 0;
        try {
            //Valida Permissão
            SessaoSEI::getInstance()->validarAuditarPermissao('pen_procedimento_expedir',__METHOD__, $objExpedirProcedimentoDTO);
            $dblIdProcedimento = $objExpedirProcedimentoDTO->getDblIdProcedimento();

            $this->barraProgresso->exibir();
            $this->barraProgresso->mover(ProcessoEletronicoINT::NEE_EXPEDICAO_ETAPA_VALIDACAO);
            $this->barraProgresso->setStrRotulo(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_VALIDACAO);

            //Valida regras de negócio
            $objInfraException = new InfraException();
            //Carregamento dos dados de processo e documento para validação e envio externo
            $objProcedimentoDTO = $this->consultarProcedimento($dblIdProcedimento);
            $objProcedimentoDTO->setArrObjDocumentoDTO($this->listarDocumentos($dblIdProcedimento));
            $objProcedimentoDTO->setArrObjParticipanteDTO($this->listarInteressados($dblIdProcedimento));
            $this->validarPreCondicoesExpedirProcedimento($objInfraException, $objProcedimentoDTO);
            $this->validarParametrosExpedicao($objInfraException, $objExpedirProcedimentoDTO);

            //Apresentao da mensagens de validao na janela da barra de progresso
            if($objInfraException->contemValidacoes()){
                $this->barraProgresso->mover(0);
                $this->barraProgresso->setStrRotulo('Erro durante validação dos dados do processo.');
                $objInfraException->lancarValidacoes();
            }

            $this->barraProgresso->mover(ProcessoEletronicoINT::NEE_EXPEDICAO_ETAPA_PROCEDIMENTO);
            $this->barraProgresso->setStrRotulo(sprintf(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_PROCEDIMENTO, $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado()));

            //Construo dos cabecalho para envio do processo
            $objCabecalho = $this->construirCabecalho($objExpedirProcedimentoDTO);

            //Construo do processo para envio
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
                $this->objProcedimentoAndamentoRN->setOpts($objTramite->NRE, $objTramite->IDT, ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO), $dblIdProcedimento);

                try {
                    $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento('Envio do metadados do processo', 'S'));
                    $idAtividadeExpedicao = $this->bloquearProcedimentoExpedicao($objExpedirProcedimentoDTO, $objProcesso->idProcedimentoSEI);

                    $this->objProcessoEletronicoRN->cadastrarTramiteDeProcesso(
                        $objProcesso->idProcedimentoSEI,
                        $objTramite->NRE,
                        $objTramite->IDT,
                        ProcessoEletronicoRN::$STA_TIPO_TRAMITE_ENVIO,
                        $objTramite->dataHoraDeRegistroDoTramite,
                        $objExpedirProcedimentoDTO->getNumIdRepositorioOrigem(),
                        $objExpedirProcedimentoDTO->getNumIdUnidadeOrigem(),
                        $objExpedirProcedimentoDTO->getNumIdRepositorioDestino(),
                        $objExpedirProcedimentoDTO->getNumIdUnidadeDestino(),
                        $objProcesso,
                        $objTramite->ticketParaEnvioDeComponentesDigitais,
                        $objTramite->componentesDigitaisSolicitados);


                    $this->objProcessoEletronicoRN->cadastrarTramitePendente($objTramite->IDT, $idAtividadeExpedicao);
                    //error_log('TRAMITE: ' . print_r($objTramite, true));
                    //error_log('before enviarComponentesDigitais');

                    //TODO: Erro no BARRAMENTO: Processo no pode ser enviado se possuir 2 documentos iguais(mesmo hash)
                    //TODO: Melhoria no barramento de servios. O mtodo solicitar metadados no deixa claro quais os componentes digitais que
                    //precisam ser baixados. No cenrio de retorno de um processo existente, a nica forma  consultar o status do trâmite para
                    //saber quais precisam ser baixados. O processo poderia ser mais otimizado se o retorno nos metadados j informasse quais os
                    //componentes precisam ser baixados, semelhante ao que ocorre no enviarProcesso onde o barramento informa quais os componentes
                    //que precisam ser enviados

                    $this->enviarComponentesDigitais($objTramite->NRE, $objTramite->IDT, $objProcesso->protocolo);
                    //error_log('after enviarComponentesDigitais');
                    //$strNumeroRegistro, $numIdTramite, $strProtocolo
                    //error_log('==========================>>>>' . print_r($objTramite, true));

                    //TODO: Ao enviar o processo e seus documentos, necessrio bloquear os documentos para alterao
                    //pois eles j foram visualizados
                    //$objDocumentoRN = new DocumentoRN();
                    //$objDocumentoRN->bloquearConsultado($objDocumentoRN->consultarRN0005($objDocumentoDTO));


                    //TODO: Implementar o registro de auditoria, armazenando os metadados xml enviados para o PEN


                    # $this->enviarDocProdimentoTramite();
                    // $this->gravarAuditoria(__METHOD__ , $objExpedirProcedimentoDTO->getDblIdProcedimento());
                    //$this->bloquearProcesso($objExpedirProcedimentoDTO->getDblIdProcedimento());
                    #$this->enviarDocProdimentoTramite();
                    //return array('mensagem' => 'Processo em expedio!', 'retorno' => 1);

                    //TODO: Alterar atualizao para somente apresentar ao final de todo o trâmite
                    $this->barraProgresso->mover(ProcessoEletronicoINT::NEE_EXPEDICAO_ETAPA_CONCLUSAO);
                    $this->barraProgresso->setStrRotulo(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_CONCLUSAO);

                    // @join_tec US008.06 (#23092)
                    $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento('Concluído envio dos componentes do processo', 'S'));

                    $this->receberReciboDeEnvio($objTramite->IDT);
                }
                catch (\Exception $e) {
                    //@TODO: Melhorar essa estrutura
                    //Realiza o desbloqueio do processo
                    try{ $this->desbloquearProcessoExpedicao($objProcesso->idProcedimentoSEI); } catch (InfraException $ex) { }

                    //@TODO: Melhorar essa estrutura
                    //Realiza o cancelamento do tramite
                    try{
                        if($numIdTramite != 0){
                            $this->objProcessoEletronicoRN->cancelarTramite($numIdTramite);
                        }
                    } catch (InfraException $ex) { }

                    $this->registrarAndamentoExpedicaoAbortada($objProcesso->idProcedimentoSEI);

                    // @join_tec US008.06 (#23092)
                    $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento('Concluído envio dos componentes do processo', 'N'));
                    throw $e;
                }
            }

        } catch (\Exception $e) {
            throw new InfraException('Falha de comunicação com o serviços de integração. Por favor, tente novamente mais tarde.', $e);
        }
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


    private function registrarAndamentoExpedicaoProcesso($objExpedirProcedimentoDTO, $objProcesso)
    {
        //Processo expedido para a entidade @ENTIDADE_DESTINO@ - @REPOSITORIO_ESTRUTURA@ (@PROCESSO@, @UNIDADE@, @USUARIO@)
        //TODO: Atribuir atributos necessrios para formao da mensagem do andamento
        //TODO: Especificar quais andamentos sero registrados
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
        $objAtributoAndamentoDTO->setStrValor($objUnidadeDTO->getStrSigla().''.$objUnidadeDTO->getStrDescricao());
        $objAtributoAndamentoDTO->setStrIdOrigem(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
        $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

        //TODO: Avaliar qual o usurio que deveria ser registrado no atributo andamento abaixo
        $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTO->setStrNome('USUARIO');
        $objAtributoAndamentoDTO->setStrValor(SessaoSEI::getInstance()->getStrSiglaUsuario() . '' . SessaoSEI::getInstance()->getStrNomeUsuario());
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
        //TODO: Tratar situao de quando  localizado dois registros para o mesmo processo
        $objProcessoEletronicoDTO->setNumMaxRegistrosRetorno(1);
        $objProcessoEletronicoDTO->setOrd('IdProcedimento', InfraDTO::$TIPO_ORDENACAO_DESC);
        $objProcessoEletronicoDTO->retStrNumeroRegistro();

        $objProcessoEletronicoDTO = $objProcessoEletronicoBD->consultar($objProcessoEletronicoDTO);
        if(isset($objProcessoEletronicoDTO)) {
            $strNumeroRegistro = $objProcessoEletronicoDTO->getStrNumeroRegistro();
        }

        // Consultar se processo eletrônico existe no PEN algum trâmite CANCELADO, caso
        // sim deve ser gerada uma nova NRE, pois a atual ser recusada pelo PEN quando
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
            $strNumeroRegistro,
            $objExpedirProcedimentoDTO->getNumIdRepositorioOrigem(),
            $objExpedirProcedimentoDTO->getNumIdUnidadeOrigem(),
            $objExpedirProcedimentoDTO->getNumIdRepositorioDestino(),
            $objExpedirProcedimentoDTO->getNumIdUnidadeDestino(),
            $objExpedirProcedimentoDTO->getBolSinUrgente(),
            $objExpedirProcedimentoDTO->getNumIdMotivoUrgencia(),
            false /*obrigarEnvioDeTodosOsComponentesDigitais*/
        );
    }

    private function construirProcesso($dblIdProcedimento, $arrIdProcessoApensado = null)
    {
        if(!isset($dblIdProcedimento)){
            throw new InfraException('Parâmetro $dblIdProcedimento não informado.');
        }

        //TODO: Passar dados do ProcedimentoDTO via parâmetro j carregado anteriormente
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
        }

        $this->atribuirProdutorProcesso($objProcesso, $objProcedimentoDTO->getNumIdUsuarioGeradorProtocolo(), $objProcedimentoDTO->getNumIdUnidadeGeradoraProtocolo());
        $this->atribuirDataHoraDeRegistro($objProcesso, $objProcedimentoDTO->getDblIdProcedimento());
        $this->atribuirDocumentos($objProcesso, $dblIdProcedimento);
        $this->atribuirDadosInteressados($objProcesso, $dblIdProcedimento);
        $this->adicionarProcessosApensados($objProcesso, $arrIdProcessoApensado);

        $objProcesso->idProcedimentoSEI = $dblIdProcedimento;
        return $objProcesso;
    }

    //TODO: Implementar mapeamento de atividades que sero enviadas para barramento (semelhante Protocolo Integrado)
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

            //TODO: Avaliar necessidade de repassar dados da pessoa que realizou a operao
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
    public static function mudarEstadoProcedimento($objProcesso, $strStaEstado)
    {
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
            $objAtributoAndamentoDTO->setStrValor('Processo está em processamento devido ao seu trâmite externo para outra unidade.');
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

        $arrObjAtributoAndamentoDTO = array();

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

        //Registra o andamento no histrico e
        $objAtividadeRN = new AtividadeRN();
        $atividade = $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);

        return $atividade->getNumIdAtividade();
    }

    public function desbloquearProcessoExpedicao($numIdProcedimento)
    {
        //Intancia o objeto de desbloqueio da API do SEI
        $objEntradaDesbloquearProcessoAPI = new EntradaDesbloquearProcessoAPI();
        $objEntradaDesbloquearProcessoAPI->setIdProcedimento($numIdProcedimento);

        //Solicita o Desbloqueio do Processo
        $objSeiRN = new SeiRN();
        $objSeiRN->desbloquearProcesso($objEntradaDesbloquearProcessoAPI);
    }


    public function registrarAndamentoExpedicaoAbortada($dblIdProtocolo)
    {
        //Seta todos os atributos do histrico de aborto da expedio
        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDblIdProtocolo($dblIdProtocolo);
        $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
        $objAtividadeDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
        $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_ABORTADO));
        $objAtividadeDTO->setArrObjAtributoAndamentoDTO(array());

        //Gera o andamento de expedio abortada
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
        //expedio e possui tratamento diferenciado

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
        $objProcedimentoHistoricoDTO->setStrStaHistorico(ProcedimentoRN::$TH_TOTAL);
        $objProcedimentoHistoricoDTO->adicionarCriterio(array('IdTarefa','IdTarefa'), array(InfraDTO::$OPER_IGUAL,InfraDTO::$OPER_IGUAL), array(TarefaRN::$TI_GERACAO_PROCEDIMENTO, ProcessoeletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO), InfraDTO::$OPER_LOGICO_OR);
        $objProcedimentoHistoricoDTO->setStrSinGerarLinksHistorico('N');
        $objProcedimentoHistoricoDTO->setNumMaxRegistrosRetorno(1);
        $objProcedimentoHistoricoDTO->setOrdNumIdTarefa(InfraDTO::$TIPO_ORDENACAO_ASC);

        if(isset($dblIdDocumento)){
            $objProcedimentoHistoricoDTO->setDblIdDocumento($dblIdDocumento);
            $objProcedimentoHistoricoDTO->setNumIdTarefa(array(TarefaRN::$TI_GERACAO_DOCUMENTO, TarefaRN::$TI_RECEBIMENTO_DOCUMENTO, TarefaRN::$TI_DOCUMENTO_MOVIDO_DO_PROCESSO), InfraDTO::$OPER_IN);
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
            //TODO: Obter tipo de pessoa fsica dos contatos do SEI
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

        if(isset($arrParticipantesDTO) && count($arrParticipantesDTO) > 0){
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

        //TODO: Passar dados do ProcedimentoDTO via parâmetro j carregado anteriormente
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
            //Considera o nmero/nome do documento externo para descrio do documento
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
                //TODO: Adicionar nome da hipótese legal atribuida ao documento
            }
            $documento->dataHoraDeProducao = $this->objProcessoEletronicoRN->converterDataWebService($documentoDTO->getDtaGeracaoProtocolo());
            $documento->produtor = new stdClass();
            $usuarioDTO = $this->consultarUsuario($documentoDTO->getNumIdUsuarioGeradorProtocolo());
            if(isset($usuarioDTO)) {
                $documento->produtor->nome = utf8_encode($usuarioDTO->getStrNome());
                $documento->produtor->numeroDeIdentificacao = $usuarioDTO->getDblCpfContato();
                //TODO: Obter tipo de pessoa fsica dos contextos/contatos do SEI
                $documento->produtor->tipo = self::STA_TIPO_PESSOA_FISICA;;
            }

            $unidadeDTO = $this->consultarUnidade($documentoDTO->getNumIdUnidadeResponsavel());
            if(isset($unidadeDTO)) {
                $documento->produtor->unidade = new stdClass();
                $documento->produtor->unidade->nome = utf8_encode($unidadeDTO->getStrDescricao());
                $documento->produtor->unidade->tipo = self::STA_TIPO_PESSOA_ORGAOPUBLICO;
                //TODO: Informar dados da estrutura organizacional (estruturaOrganizacional)
            }

            $documento->produtor->numeroDeIdentificacao = $documentoDTO->getStrProtocoloDocumentoFormatado();  //TODO: Avaliar se informação está correta

            $this->atribuirDataHoraDeRegistro($documento, $documentoDTO->getDblIdProcedimento(), $documentoDTO->getDblIdDocumento());
            //TODO: Implementar mapeamento de espécies documentais
            $documento->especie = new stdClass();
            $documento->especie->codigo = $this->obterEspecieMapeada($documentoDTO->getNumIdSerie());
            $documento->especie->nomeNoProdutor = utf8_encode($documentoDTO->getStrNomeSerie());
            //TODO: Tratar campos adicionais do documento
            //Identificao do documento
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

            //TODO: Necessrio tratar informações abaixo
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

        $strAlgoritmoHash = self::ALGORITMO_HASH_DOCUMENTO;
        $strConteudoAssinatura = $arrInformacaoArquivo['CONTEUDO'];
        $hashDoComponenteDigital = $arrInformacaoArquivo['HASH_CONTEUDO'];
        $strAlgoritmoHash = $arrInformacaoArquivo['ALGORITMO_HASH_CONTEUDO'];

        //TODO: Revisar tal implementao para atender a gerao de hash de arquivos grandes
        $objDocumento->componenteDigital = new stdClass();
        $objDocumento->componenteDigital->ordem = 1;
        $objDocumento->componenteDigital->nome = utf8_encode($arrInformacaoArquivo["NOME"]);
        $objDocumento->componenteDigital->hash = new SoapVar("<hash algoritmo='{$strAlgoritmoHash}'>{$hashDoComponenteDigital}</hash>", XSD_ANYXML);
        $objDocumento->componenteDigital->tamanhoEmBytes = $arrInformacaoArquivo['TAMANHO'];
        //TODO: Validar os tipos de mimetype de acordo com o WSDL do SEI
        //Caso no identifique o tipo correto, informar o valor [outro]
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

    public function atribuirDadosAssinaturaDigital($objDocumentoDTO, $objDocumento, $strHashDocumento)
    {
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

        //Remove todos os 12 espaos padres aps remover as tags.
        $dataTarjas = explode('            ', strip_tags($tarjas));
        foreach ($dataTarjas as $key => $content) {
            $contentTrim = trim($content); //Limpa os espaos no inicio e fim de cada texto.
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
        $objAssinaturaDTO->retStrStaFormaAutenticacao();
        $objAssinaturaDTO->retStrP7sBase64();

        $objAssinaturaRN = new AssinaturaRN();
        $resAssinatura = $objAssinaturaRN->listarRN1323($objAssinaturaDTO);

        $objDocumento->componenteDigital->assinaturaDigital = array();
        //Para cada assinatura
        foreach ($resAssinatura as $keyOrder => $assinatura) {
            //Busca data da assinatura
            $objAtividadeDTO = new AtividadeDTO();
            $objAtividadeDTO->setNumIdAtividade($assinatura->getNumIdAtividade());
            $objAtividadeDTO->setNumIdTarefa(array(TarefaRN::$TI_ASSINATURA_DOCUMENTO, TarefaRN::$TI_AUTENTICACAO_DOCUMENTO), InfraDTO::$OPER_IN);
            $objAtividadeDTO->retDthAbertura();
            $objAtividadeDTO->retNumIdAtividade();
            $objAtividadeRN = new AtividadeRN();
            $objAtividade = $objAtividadeRN->consultarRN0033($objAtividadeDTO);

            $objAssinaturaDigital = new stdClass();
            $objAssinaturaDigital->razao = utf8_encode($dataTarjas[$keyOrder]);
            $objAssinaturaDigital->observacao = utf8_encode($dataTarjas[count($dataTarjas) - 1]);
            $objAssinaturaDigital->dataHora = $this->objProcessoEletronicoRN->converterDataWebService($objAtividade->getDthAbertura());

            if($assinatura->getStrStaFormaAutenticacao() == AssinaturaRN::$TA_CERTIFICADO_DIGITAL){
                $objAssinaturaDigital->hash =  new SoapVar("<hash algoritmo='".self::ALGORITMO_HASH_ASSINATURA."'>{$strHashDocumento}</hash>", XSD_ANYXML);
                $objAssinaturaDigital->cadeiaDoCertificado = new SoapVar('<cadeiaDoCertificado formato="PKCS7">'.($assinatura->getStrP7sBase64() ? $assinatura->getStrP7sBase64() : 'null').'</cadeiaDoCertificado>', XSD_ANYXML);
            } else {
                $objAssinaturaDigital->hash = new SoapVar("<hash algoritmo='".self::ALGORITMO_HASH_ASSINATURA."'>null</hash>", XSD_ANYXML);
                $objAssinaturaDigital->cadeiaDoCertificado = new SoapVar('<cadeiaDoCertificado formato="PKCS7">null</cadeiaDoCertificado>', XSD_ANYXML);
            }

            $objDocumento->componenteDigital->assinaturaDigital[] = $objAssinaturaDigital;
        }

        return $objDocumento;
    }


    private function consultarComponenteDigital($parDblIdDocumento, $parNumIdTramite=null)
    {
        $objComponenteDigitalDTO = new ComponenteDigitalDTO();
        $objComponenteDigitalDTO->setDblIdDocumento($parDblIdDocumento);
        //$objComponenteDigitalDTO->setNumIdTramite($parNumIdTramite, InfraDTO::$OPER_DIFERENTE);
        $objComponenteDigitalDTO->setNumMaxRegistrosRetorno(1);
        $objComponenteDigitalDTO->setOrd('IdTramite', InfraDTO::$TIPO_ORDENACAO_DESC);
        $objComponenteDigitalDTO->retTodos();

        $objComponenteDigitalBD = new ComponenteDigitalBD($this->getObjInfraIBanco());
        $arrObjComponenteDigitalDTO = $objComponenteDigitalBD->listar($objComponenteDigitalDTO);
        return (count($arrObjComponenteDigitalDTO) > 0) ? $arrObjComponenteDigitalDTO[0] : null;
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
            $strConteudoAssinatura = $this->obterConteudoInternoAssinatura($objDocumentoDTO->getDblIdDocumento());
            $hashDoComponenteDigital = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $strConteudoAssinatura, true));

            //Busca registro de tramitações anteriores para este componente digital para identificar se o Barramento do PEN já havia registrado o hash do documento gerado da
            //forma antiga, ou seja, considerando o link do Número SEI. Este link foi removido para manter o padrão de conteúdo de documentos utilizado pelo SEI para assinatura
            //Para não bloquear os documentos gerados anteriormente, aqueles já registrados pelo Barramento com o hash antigo deverão manter a geração de conteúdo anteriormente utilizada.
            $objComponenteDigital = $this->consultarComponenteDigital($objDocumentoDTO->getDblIdDocumento());
            $hashDoComponenteDigitalAnterior = (isset($objComponenteDigital)) ? $objComponenteDigital->getStrHashConteudo() : null;
            if(isset($hashDoComponenteDigitalAnterior) && ($hashDoComponenteDigitalAnterior <> $hashDoComponenteDigital)){
                $strConteudoAssinatura = $this->obterConteudoInternoAssinatura($objDocumentoDTO->getDblIdDocumento(), true);
            }

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

            //VALIDAO DE TAMANHO DE DOCUMENTOS EXTERNOS PARA A EXPEDIO
            $objPenParametroRN = new PenParametroRN();
            if($objAnexoDTO->getNumTamanho() > ($objPenParametroRN->getParametro('PEN_TAMANHO_MAXIMO_DOCUMENTO_EXPEDIDO') * 1024 * 1024) && $objDocumentoDTO->getStrStaEstadoProtocolo() != ProtocoloRN::$TE_DOCUMENTO_CANCELADO){
                $strTamanhoFormatado = round(($objAnexoDTO->getNumTamanho() / 1024) / 1024,2);
                throw new InfraException("O tamanho do documento {$strTamanhoFormatado} MB é maior que os {$objPenParametroRN->getParametro('PEN_TAMANHO_MAXIMO_DOCUMENTO_EXPEDIDO')} MB permitidos para trâmite externo de documentos.");
            }

            //Obtenção do conteúdo do documento externo
            //TODO: Particionar o documento em tamanho menor caso ultrapasse XX megabytes
            $strCaminhoAnexo = $this->objAnexoRN->obterLocalizacao($objAnexoDTO);

            $fp = fopen($strCaminhoAnexo, "rb");
            try {
                $strConteudoAssinatura = fread($fp, filesize($strCaminhoAnexo));
                fclose($fp);
            } catch(Exception $e) {
                fclose($fp);
                throw new InfraException("Erro obtendo conteúdo do anexo do documento {$strProtocoloDocumentoFormatado}", $e);
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
            $objDocumentoRN = new DocumentoRN();
            $strResultado = $objDocumentoRN->consultarHtmlFormulario($objDocumentoDTO2);

            $arrInformacaoArquivo['NOME'] = $strProtocoloDocumentoFormatado . ".html";
            $arrInformacaoArquivo['CONTEUDO'] = $strResultado;
            $arrInformacaoArquivo['TAMANHO'] = strlen($strResultado);
            $arrInformacaoArquivo['MIME_TYPE'] = 'text/html';
            $arrInformacaoArquivo['ID_ANEXO'] = null;
        }

        $arrInformacaoArquivo['ALGORITMO_HASH_CONTEUDO'] = self::ALGORITMO_HASH_DOCUMENTO;
        $hashDoComponenteDigital = hash(self::ALGORITMO_HASH_DOCUMENTO, $arrInformacaoArquivo['CONTEUDO'], true);
        $arrInformacaoArquivo['HASH_CONTEUDO'] = base64_encode($hashDoComponenteDigital);
        return $arrInformacaoArquivo;
    }

    /**
     * Método de obtenção do conteúdo do documento interno para envio e cálculo de hash
     *
     * Anteriormente, os documentos enviados para o Barramento de Serviços do PEN continham o link para o número SEI do documento.
     * Este link passou a não ser mais considerado pois é uma informação dinâmica e pertinente apenas quando o documento é visualizado
     * dentro do sistema SEI. Quando o documento é tramitado externamente, este link não possui mais sentido.
     *
     * Para tratar esta transição entre os formatos de documentos, existe o parâmetro $bolFormatoLegado para indicar qual formato deverá
     * ser utilizado na montagem dos metadados para envio.     *
     *
     * @param  Double  $parDblIdDocumento Identificador do documento
     * @param  boolean $bolFormatoLegado  Flag indicando se a forma antiga de recuperação de conteúdo para envio deverá ser utilizada
     * @return String                     Conteúdo completo do documento para envio
     */
    private function obterConteudoInternoAssinatura($parDblIdDocumento, $bolFormatoLegado=false)
    {
        $objEditorDTO = new EditorDTO();
        $objEditorDTO->setDblIdDocumento($parDblIdDocumento);
        $objEditorDTO->setNumIdBaseConhecimento(null);
        $objEditorDTO->setStrSinCabecalho('S');
        $objEditorDTO->setStrSinRodape('S');
        $objEditorDTO->setStrSinIdentificacaoVersao('N');

        if($bolFormatoLegado) {
            $objEditorDTO->setStrSinIdentificacaoVersao('S');
            $objEditorDTO->setStrSinProcessarLinks('S');
        }

        //Normaliza o formato de número de versão considerando dois caracteres para cada item (3.0.15 -> 030015)
        $numVersaoAtual = explode('.', SEI_VERSAO);
        $numVersaoAtual = array_map(function($item){ return str_pad($item, 2, '0', STR_PAD_LEFT); }, $numVersaoAtual);
        $numVersaoAtual = intval(join($numVersaoAtual));

        //Normaliza o formato de número de versão considerando dois caracteres para cada item (3.0.7 -> 030007)
        $numVersaoCarimboObrigatorio = explode('.', self::VERSAO_CARIMBO_PUBLICACAO_OBRIGATORIO);
        $numVersaoCarimboObrigatorio = array_map(function($item){ return str_pad($item, 2, '0', STR_PAD_LEFT); }, $numVersaoCarimboObrigatorio);
        $numVersaoCarimboObrigatorio = intval(join($numVersaoCarimboObrigatorio));

        if ($numVersaoAtual >= $numVersaoCarimboObrigatorio) {
            $objEditorDTO->setStrSinCarimboPublicacao('N');
        }

        $objEditorRN = new EditorRN();
        return $objEditorRN->consultarHtmlVersao($objEditorDTO);
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
        $objUsuarioDTO->setBolExclusaoLogica(false);
        $objUsuarioDTO->retStrNome();
        $objUsuarioDTO->retDblCpfContato();

        return $this->objUsuarioRN->consultarRN0489($objUsuarioDTO);
    }

    public function listarDocumentos($idProcedimento)
    {
        if(!isset($idProcedimento)){
            throw new InfraException('Parâmetro $idProcedimento não informado.');
        }

        //Recupera toda a lista de documentos vinculados ao processo, considerando a ordenação definida pelo usuário
        $arrTipoAssociacao = array(RelProtocoloProtocoloRN::$TA_DOCUMENTO_ASSOCIADO, RelProtocoloProtocoloRN::$TA_DOCUMENTO_MOVIDO);
        $objRelProtocoloProtocoloDTO = new RelProtocoloProtocoloDTO();
        $objRelProtocoloProtocoloDTO->retDblIdRelProtocoloProtocolo();
        $objRelProtocoloProtocoloDTO->retDblIdProtocolo1();
        $objRelProtocoloProtocoloDTO->retDblIdProtocolo2();
        $objRelProtocoloProtocoloDTO->retStrStaAssociacao();
        $objRelProtocoloProtocoloDTO->setStrStaAssociacao($arrTipoAssociacao, InfraDTO::$OPER_IN);
        $objRelProtocoloProtocoloDTO->setDblIdProtocolo1($idProcedimento);
        $objRelProtocoloProtocoloDTO->setOrdNumSequencia(InfraDTO::$TIPO_ORDENACAO_ASC);

        $objRelProtocoloProtocoloRN = new RelProtocoloProtocoloRN();
        $arrObjRelProtocoloProtocoloDTO = $objRelProtocoloProtocoloRN->listarRN0187($objRelProtocoloProtocoloDTO);

        $arrIdDocumentos = array();
        foreach($arrObjRelProtocoloProtocoloDTO as $objRelProtocoloProtocoloDTO) {
            if ($objRelProtocoloProtocoloDTO->getStrStaAssociacao()==RelProtocoloProtocoloRN::$TA_DOCUMENTO_ASSOCIADO ||
                $objRelProtocoloProtocoloDTO->getStrStaAssociacao()==RelProtocoloProtocoloRN::$TA_DOCUMENTO_MOVIDO) {

                $arrIdDocumentos[] = $objRelProtocoloProtocoloDTO->getDblIdProtocolo2();
            }
        }

        $objDocumentoDTO = new DocumentoDTO();
        $objDocumentoDTO->retStrDescricaoUnidadeGeradoraProtocolo();
        $objDocumentoDTO->retNumIdOrgaoUnidadeGeradoraProtocolo();
        $objDocumentoDTO->retStrSiglaUnidadeGeradoraProtocolo();
        $objDocumentoDTO->retStrStaNivelAcessoLocalProtocolo();
        $objDocumentoDTO->retStrProtocoloDocumentoFormatado();
        $objDocumentoDTO->retStrStaEstadoProtocolo();
        $objDocumentoDTO->retNumIdUsuarioGeradorProtocolo();
        $objDocumentoDTO->retStrStaProtocoloProtocolo();
        $objDocumentoDTO->retNumIdUnidadeResponsavel();
        $objDocumentoDTO->retStrDescricaoProtocolo();
        $objDocumentoDTO->retDtaGeracaoProtocolo();
        $objDocumentoDTO->retDblIdProcedimento();
        $objDocumentoDTO->retDblIdDocumento();
        $objDocumentoDTO->retStrNomeSerie();
        $objDocumentoDTO->retNumIdSerie();
        $objDocumentoDTO->retStrConteudoAssinatura();
        $objDocumentoDTO->retStrNumero();
        $objDocumentoDTO->retNumIdTipoConferencia();
        $objDocumentoDTO->retStrStaDocumento();
        $objDocumentoDTO->retNumIdHipoteseLegalProtocolo();
        $objDocumentoDTO->setDblIdDocumento($arrIdDocumentos, InfraDTO::$OPER_IN);

        $arrObjDocumentoDTOBanco = $this->objDocumentoRN->listarRN0008($objDocumentoDTO);
        $arrObjDocumentoDTOIndexado = InfraArray::indexarArrInfraDTO($arrObjDocumentoDTOBanco, 'IdDocumento');

        //Mantem ordenação definida pelo usuário
        $arrObjDocumentoDTO = array();
        foreach($arrIdDocumentos as $dblIdDocumento){
            if (isset($arrObjDocumentoDTOIndexado[$dblIdDocumento])){
                $arrObjDocumentoDTO[$dblIdDocumento] = $arrObjDocumentoDTOIndexado[$dblIdDocumento];
            }
        }

        return $arrObjDocumentoDTO;
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

            //TODO: Valida inconsistncia da quantidade de documentos solicitados e aqueles cadastrados no SEI

            //Construir objeto Componentes digitais
            foreach ($arrComponentesDigitaisDTO as $objComponenteDigitalDTO) {

                $this->barraProgresso->mover(ProcessoEletronicoINT::NEE_EXPEDICAO_ETAPA_DOCUMENTO);
                $this->barraProgresso->setStrRotulo(sprintf(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_DOCUMENTO, $objComponenteDigitalDTO->getStrProtocoloDocumentoFormatado()));

                $dadosDoComponenteDigital = new stdClass();
                $dadosDoComponenteDigital->ticketParaEnvioDeComponentesDigitais = $objComponenteDigitalDTO->getNumTicketEnvioComponentes();

                    //TODO: Problema no barramento de servios quando um mesmo arquivo est contido em dois diferentes
                    //processos apensados. Mesmo erro relatado com dois arquivos iguais em docs diferentes no mesmo processo
                $dadosDoComponenteDigital->protocolo = $objComponenteDigitalDTO->getStrProtocolo();
                $dadosDoComponenteDigital->hashDoComponenteDigital = $objComponenteDigitalDTO->getStrHashConteudo();

                    //TODO: Particionar o arquivo em vrias partes caso for muito grande seu tamanho
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

                        //Bloquea documento para atualizao, j que ele foi visualizado
                    $this->objDocumentoRN->bloquearConteudo($objDocumentoDTO);
                        // @join_tec US008.05 (#23092)
                    $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento(sprintf('Enviando %s %s', $strNomeDocumento, $objComponenteDigitalDTO->getStrProtocoloDocumentoFormatado()), 'S'));
                } catch (Exception $e) {
                        // @join_tec US008.05 (#23092)
                    $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento(sprintf('Enviando %s %s', $strNomeDocumento, $objComponenteDigitalDTO->getStrProtocoloDocumentoFormatado()), 'N'));
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

        //TODO: Validar se repositrio de origem foi informado
        if (InfraString::isBolVazia($objExpedirProcedimentoDTO->getNumIdRepositorioOrigem())){
            $objInfraException->adicionarValidacao('Identificação do repositório de estruturas da unidade atual não informado.');
        }

        //TODO: Validar se unidade de origem foi informado
        if (InfraString::isBolVazia($objExpedirProcedimentoDTO->getNumIdUnidadeOrigem())){
            $objInfraException->adicionarValidacao('Identificação da unidade atual no repositório de estruturas organizacionais não informado.');
        }

        //TODO: Validar se repositrio foi devidamente informado
        if (InfraString::isBolVazia($objExpedirProcedimentoDTO->getNumIdRepositorioDestino())){
            $objInfraException->adicionarValidacao('Repositório de estruturas organizacionais não informado.');
        }

        //TODO: Validar se unidade foi devidamente informada
        if (InfraString::isBolVazia($objExpedirProcedimentoDTO->getNumIdUnidadeDestino())){
            $objInfraException->adicionarValidacao('Unidade de destino não informado.');
        }

        //TODO: Validar se motivo de urgncia foi devidamente informado, caso expedio urgente
        if ($objExpedirProcedimentoDTO->getBolSinUrgente() && InfraString::isBolVazia($objExpedirProcedimentoDTO->getNumIdMotivoUrgencia())){
            $objInfraException->adicionarValidacao('Motivo de urgência não informado.');
        }
    }

    private function validarDocumentacaoExistende(InfraException $objInfraException, ProcedimentoDTO $objProcedimentoDTO, $strAtributoValidacao = null)
    {
        $arrObjDocumentoDTO = $objProcedimentoDTO->getArrObjDocumentoDTO();
        if(!isset($arrObjDocumentoDTO) || count($arrObjDocumentoDTO) == 0) {
            $objInfraException->adicionarValidacao('Não é possível trâmitar um processo sem documentos', $strAtributoValidacao);
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

    private function validarDadosDocumentos(InfraException $objInfraException, $arrDocumentoDTO, $strAtributoValidacao = null)
    {
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

                if (!empty($objDocumentoDTO->getNumIdHipoteseLegalProtocolo()) && empty($objPenRelHipoteseLegalEnvioRN->getIdHipoteseLegalPEN($objDocumentoDTO->getNumIdHipoteseLegalProtocolo()))) {

                    $objHipoteseLegalDTO = new HipoteseLegalDTO();
                    $objHipoteseLegalDTO->setNumIdHipoteseLegal($objDocumentoDTO->getNumIdHipoteseLegalProtocolo());
                    $objHipoteseLegalDTO->retStrNome();
                    $objHipoteseLegalRN = new HipoteseLegalRN();
                    $dados = $objHipoteseLegalRN->consultar($objHipoteseLegalDTO);

                    $objInfraException->adicionarValidacao('Hipótese legal "'.$dados->getStrNome().'" do documento '.$objDocumentoDTO->getStrNomeSerie(). ' ' . $objDocumentoDTO->getStrProtocoloDocumentoFormatado() .' não mapeada', $strAtributoValidacao);
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
            $objInfraException->adicionarValidacao("Não é possível trâmitar um processo aberto em mais de uma unidade. ($strSiglaUnidade)", $strAtributoValidacao);
        }
    }

    private function validarNivelAcessoProcesso(InfraException $objInfraException, ProcedimentoDTO $objProcedimentoDTO, $strAtributoValidacao = null)
    {
        // $objProcedimentoDTO = new ProcedimentoDTO();

        // $objProcedimentoDTO->setDblIdProcedimento($idProcedimento);
        // $objProcedimentoDTO->retStrStaNivelAcessoGlobalProtocolo();

        // $objProcedimentoDTO = $this->objProcedimentoRN->consultarRN0201($objProcedimentoDTO);

        if ($objProcedimentoDTO->getStrStaNivelAcessoLocalProtocolo() == ProtocoloRN::$NA_SIGILOSO) {
            $objInfraException->adicionarValidacao('Não é possível trâmitar um processo com informações sigilosas.', $strAtributoValidacao);
        }
    }

    /**
     * Valida existncia da Hiptese legal de Envio
     * @param InfraException $objInfraException
     * @param ProcedimentoDTO $objProcedimentoDTO
     * @param string $strAtributoValidacao
     */
    private function validarHipoteseLegalEnvio(InfraException $objInfraException, ProcedimentoDTO $objProcedimentoDTO, $strAtributoValidacao = null) {
        if ($objProcedimentoDTO->getStrStaNivelAcessoLocalProtocolo() == ProtocoloRN::$NA_RESTRITO) {
            if (empty($objProcedimentoDTO->getNumIdHipoteseLegalProtocolo())) {
                $objInfraException->adicionarValidacao('Não é possível trâmitar um processo de nível restrito sem a hipótese legal mapeada.', $strAtributoValidacao);
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

                // Se o documento no tem assinatura e não foi cancelado ento
                // cai na regra de validao
                if($objAssinaturaRN->contarRN1324($objAssinaturaDTO) == 0 && $objDocumentoDTO->getStrStaEstadoProtocolo() != ProtocoloRN::$TE_DOCUMENTO_CANCELADO && ($objDocumentoDTO->getStrStaDocumento() == DocumentoRN::$TD_EDITOR_EDOC || $objDocumentoDTO->getStrStaDocumento() == DocumentoRN::$TD_EDITOR_INTERNO) ){

                    $bolAssinaturaCorretas = false;
                }
            }
        }

        if($bolAssinaturaCorretas !== true) {
            $objInfraException->adicionarValidacao('Não é possível trâmitar um processos com documentos gerados e não assinados', $strAtributoValidacao);
        }
    }

    /**
     * Validao das pr-conidies necessrias para que um processo e seus documentos possam ser expedidos para outra entidade
     * @param  InfraException  $objInfraException  Instncia da classe de exceo para registro dos erros
     * @param  ProcedimentoDTO $objProcedimentoDTO Informações sobre o procedimento a ser expedido
     * @param string $strAtributoValidacao indice para o InfraException separar os processos
     */
    public function validarPreCondicoesExpedirProcedimento(InfraException $objInfraException, ProcedimentoDTO $objProcedimentoDTO, $strAtributoValidacao = null)
    {
        //TODO: Validar pr-condies dos processos e documentos apensados
        $this->validarDadosProcedimento($objInfraException, $objProcedimentoDTO, $strAtributoValidacao);
        $this->validarDadosDocumentos($objInfraException, $objProcedimentoDTO->getArrObjDocumentoDTO(), $strAtributoValidacao);

        $this->validarDocumentacaoExistende($objInfraException, $objProcedimentoDTO, $strAtributoValidacao);
        $this->validarProcessoAbertoUnidade($objInfraException, $objProcedimentoDTO, $strAtributoValidacao);
        $this->validarNivelAcessoProcesso($objInfraException, $objProcedimentoDTO, $strAtributoValidacao);
        $this->validarHipoteseLegalEnvio($objInfraException, $objProcedimentoDTO, $strAtributoValidacao);
        $this->validarAssinaturas($objInfraException, $objProcedimentoDTO, $strAtributoValidacao);
    }


    private function obterNivelSigiloPEN($strNivelSigilo)
    {
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


    public function listarProcessosApensados($dblIdProcedimentoAtual, $idUnidadeAtual, $strPalavrasPesquisa = '', $numRegistros = 15)
    {

        $arrObjProcessosApensados = array();

        try{
            $objInfraException = new InfraException();
            $idUnidadeAtual = filter_var($idUnidadeAtual, FILTER_SANITIZE_NUMBER_INT);

            if(!$idUnidadeAtual){
                $objInfraException->adicionarValidacao('Processo inválido.');
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

        try {
            $objInfraException = new InfraException();
            $idUnidadeAtual = filter_var($idUnidadeAtual, FILTER_SANITIZE_NUMBER_INT);

            if(!$idUnidadeAtual){
                $objInfraException->adicionarValidacao('Processo inválido.');
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


    /**
     * @author Fabio.braga@softimais.com.br
     * @deprecated  consulta  processo
     * data : 28/05/2015
     * @return objet
    */
    public function listarTramiteParaCancelar($idProcedimento)
    {
        $objProtocoloDTO  = $this->consultarProtocoloPk($idProcedimento);
        $result = $this->objProcessoEletronicoRN->serviceConsultarTramitesProtocolo( $objProtocoloDTO->getStrProtocoloFormatado( ) );
        return $result;
    }


    /**
     * Cancela uma expedio de um Procedimento para outra unidade
     *
     * @param int $dblIdProcedimento
     * @throws InfraException
     */
    public function cancelarTramite($dblIdProcedimento)
    {
        //Busca os dados do protocolo
        $objDtoProtocolo = new ProtocoloDTO();
        $objDtoProtocolo->retStrProtocoloFormatado();
        $objDtoProtocolo->retDblIdProtocolo();
        $objDtoProtocolo->setDblIdProtocolo($dblIdProcedimento);

        $objProtocoloBD = new ProtocoloBD($this->getObjInfraIBanco());
        $objDtoProtocolo = $objProtocoloBD->consultar($objDtoProtocolo);

        $this->cancelarTramiteInternoControlado($objDtoProtocolo);

    }

    protected function cancelarTramiteInternoControlado(ProtocoloDTO $objDtoProtocolo)
    {
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

        //Verifica se o trâmite est com o status de iniciado
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
            throw new InfraException('Não foi encontrado o processo pelo ID ' . $dblIdProcedimento);
        }

        //Armazena a situao atual
        $numSituacaoAtual = $tramite->situacaoAtual;

        //Valida os status
        switch ($numSituacaoAtual) {
            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_ENVIADO_DESTINATARIO:
            throw new InfraException("O sistema destinatário já iniciou o recebimento desse processo, portanto não é possível realizar o cancelamento");
            break;
            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE:
            throw new InfraException("O sistema destinatário já recebeu esse processo, portanto não é possivel realizar o cancelamento");
            break;
            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO:
            throw new InfraException("O trâmite externo para esse processo já se encontra cancelado.");
            break;
            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO:
            throw new InfraException("O trâmite externo para esse processo encontra-se recusado.");
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

        $objAtividadeDTO->setArrObjAtributoAndamentoDTO(array($objAtributoAndamentoDTOHora, $objAtributoAndamentoDTOUser));

        $objAtividadeRN = new AtividadeRN();
        $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);
    }

    /**
     * Verifica se o processo se encontra em expedio
     *
     * @param integer $parNumIdProcedimento
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


    public function consultaUnidadePk($idUnidade) {

        $objUnidadeDTO = new UnidadeDTO();
        $objUnidadeDTO->setNumIdUnidade($idUnidade);
        $objUnidadeDTO->retTodos();

        $objUnidadeDTO = $this->objUnidadeRN->consultarRN0125($objUnidadeDTO);

        return $objUnidadeDTO;
    }

    public function consultaUsuarioPk($idUsuario)
    {

        $objUsuarioDTO = new UsuarioDTO();
        $objUsuarioDTO->setNumIdUsuario($idUsuario);
        $objUsuarioDTO->retTodos();

        $objUsuarioDTO = $this->objUsuarioRN->consultarRN0489($objUsuarioDTO);

        return $objUsuarioDTO;
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
       $documentoRespArray = array();
       $documentoDTO = new DocumentoDTO();
       $documentoDTO->setDblIdProcedimento($idPrtocedimento);
       $documentoDTO->retTodos();
       $documentoDTO = $this->objDocumentoRN->listarRN0008($documentoDTO);
       return $documentoDTO;
   }
}
