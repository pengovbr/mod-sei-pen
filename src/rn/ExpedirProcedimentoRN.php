<?php

require_once DIR_SEI_WEB.'/SEI.php';

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
    private $objPenParametroRN;
    private $objPenRelTipoDocMapEnviadoRN;
    private $objAssinaturaRN;
    private $barraProgresso;
    private $objProcedimentoAndamentoRN;
    private $fnEventoEnvioMetadados;

    private $arrPenMimeTypes = array(
        "application/pdf",
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

        $this->barraProgresso = new InfraBarraProgresso();
        $this->barraProgresso->setNumMin(0);
    }

    protected function inicializarObjInfraIBanco()
    {
        return BancoSEI::getInstance();
    }

    protected function expedirProcedimentoControlado(ExpedirProcedimentoDTO $objExpedirProcedimentoDTO)
    {
        $numIdTramite = 0;
        try {
            //Valida Permissão
            SessaoSEI::getInstance()->validarAuditarPermissao('pen_procedimento_expedir',__METHOD__, $objExpedirProcedimentoDTO);
            $dblIdProcedimento = $objExpedirProcedimentoDTO->getDblIdProcedimento();

            $this->barraProgresso->exibir();
            $this->barraProgresso->setStrRotulo(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_VALIDACAO);

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

            //Busca metadados do processo registrado em trâmite anterior
            $objMetadadosProcessoTramiteAnterior = $this->consultarMetadadosPEN($dblIdProcedimento);

            //Construção do cabeçalho para envio do processo
            $objTramitesAnteriores = $this->consultarTramitesAnteriores($objMetadadosProcessoTramiteAnterior->NRE);
            $objCabecalho = $this->construirCabecalho($objExpedirProcedimentoDTO, $objTramitesAnteriores,$dblIdProcedimento);

            //Construção do processo para envio
            $objProcesso = $this->construirProcesso($dblIdProcedimento, $objExpedirProcedimentoDTO->getArrIdProcessoApensado(), $objMetadadosProcessoTramiteAnterior);

            //Obtém o tamanho total da barra de progreso
            $nrTamanhoTotalBarraProgresso = $this->obterTamanhoTotalDaBarraDeProgresso($objProcesso);

            //Atribui o tamanho máximo da barra de progresso
            $this->barraProgresso->setNumMax($nrTamanhoTotalBarraProgresso);

            //Exibe a barra de progresso após definir o seu tamanho
            $this->barraProgresso->mover(ProcessoEletronicoINT::NEE_EXPEDICAO_ETAPA_PROCEDIMENTO);
            $this->barraProgresso->setStrRotulo(sprintf(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_PROCEDIMENTO, $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado()));

            //Cancela trâmite anterior caso este esteja travado em status inconsistente 1 - STA_SITUACAO_TRAMITE_INICIADO
            if($objTramiteInconsistente = $this->necessitaCancelamentoTramiteAnterior($objTramitesAnteriores)){
                $this->objProcessoEletronicoRN->cancelarTramite($objTramiteInconsistente->IDT);
            }

            $param = new stdClass();
            $param->novoTramiteDeProcesso = new stdClass();
            $param->novoTramiteDeProcesso->cabecalho = $objCabecalho;
            $param->novoTramiteDeProcesso->processo = $objProcesso;
            $novoTramite = $this->objProcessoEletronicoRN->enviarProcesso($param);
            $numIdTramite = $novoTramite->dadosTramiteDeProcessoCriado->IDT;
            $this->lancarEventoEnvioMetadados($numIdTramite);

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

                        //TODO: Erro no BARRAMENTO: Processo no pode ser enviado se possuir 2 documentos iguais(mesmo hash)
                        //TODO: Melhoria no barramento de servios. O mtodo solicitar metadados no deixa claro quais os componentes digitais que
                        //precisam ser baixados. No cenrio de retorno de um processo existente, a nica forma  consultar o status do trâmite para
                        //saber quais precisam ser baixados. O processo poderia ser mais otimizado se o retorno nos metadados j informasse quais os
                        //componentes precisam ser baixados, semelhante ao que ocorre no enviarProcesso onde o barramento informa quais os componentes
                        //que precisam ser enviados

                        $this->enviarComponentesDigitais($objTramite->NRE, $objTramite->IDT, $objProcesso->protocolo);

                        //TODO: Ao enviar o processo e seus documentos, necessrio bloquear os documentos para alterao
                        //pois eles j foram visualizados
                        //$objDocumentoRN = new DocumentoRN();
                        //$objDocumentoRN->bloquearConsultado($objDocumentoRN->consultarRN0005($objDocumentoDTO));

                        //TODO: Implementar o registro de auditoria, armazenando os metadados xml enviados para o PEN

                        //TODO: Alterar atualizao para somente apresentar ao final de todo o trâmite
                        //$this->barraProgresso->mover(ProcessoEletronicoINT::NEE_EXPEDICAO_ETAPA_CONCLUSAO);
                        $this->barraProgresso->mover($this->barraProgresso->getNumMax());
                        $this->barraProgresso->setStrRotulo(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_CONCLUSAO);

                        $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento('Concluído envio dos componentes do processo', 'S'));

                        $this->receberReciboDeEnvio($objTramite->IDT);
                    }
                    catch (\Exception $e) {
                        //Realiza o desbloqueio do processo
                        try{ $this->desbloquearProcessoExpedicao($objProcesso->idProcedimentoSEI); } catch (InfraException $ex) { }

                        //Realiza o cancelamento do tramite
                        try{
                            if($numIdTramite != 0){
                                $this->objProcessoEletronicoRN->cancelarTramite($numIdTramite);
                            }
                        } catch (InfraException $ex) { }

                        $this->registrarAndamentoExpedicaoAbortada($objProcesso->idProcedimentoSEI);

                        $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento('Concluído envio dos componentes do processo', 'N'));
                        throw $e;
                    }
                }

            } catch (\Exception $e) {
                throw new InfraException('Falha de comunicação com o serviços de integração. Por favor, tente novamente mais tarde.', $e);
            }
        }

        /**
        * Busca metadados do processo registrado no Barramento de Serviços do PEN em trâmites anteriores
        * @return stdClass Metadados do Processo
        */
        private function consultarMetadadosPEN($parDblIdProcedimento)
        {
            $objMetadadosProtocolo = null;

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

                    if(isset($objRetorno)){
                        $objMetadadosProtocolo = $objRetorno->metadados;
                    }
                }
            }
            catch(Exception $e){
                //Em caso de falha na comunicação com o barramento neste ponto, o procedimento deve serguir em frente considerando
                //que os metadados do protocolo não pode ser obtida
                $objMetadadosProtocolo = null;
                LogSEI::getInstance()->gravar("Falha na obtenção dos metadados de trâmites anteriores do processo ($parDblIdProcedimento) durante trâmite externo.");
                throw $e;
            }

            return $objMetadadosProtocolo;
        }

        /**
        * Método responsável por obter o tamanho total que terá a barra de progresso, considerando os diversos componentes digitais
        * a quantidade de partes em que cada um será particionado
        * @author Josinaldo Júnior <josinaldo.junior@basis.com.br>
        * @param $parObjProcesso
        * @return float|int $totalBarraProgresso
        */
        private function obterTamanhoTotalDaBarraDeProgresso($parObjProcesso)
        {
            $nrTamanhoMegasMaximo  = $this->objPenParametroRN->getParametro('PEN_TAMANHO_MAXIMO_DOCUMENTO_EXPEDIDO');
            $nrTamanhoBytesMaximo  = ($nrTamanhoMegasMaximo * pow(1024, 2)); //Qtd de MB definido como parametro

            $totalBarraProgresso = 2;
            $this->contadorDaBarraDeProgresso = 2;
            $arrHashIndexados = array();
            foreach ($parObjProcesso->documento as $objDoc)
            {
                $strHashComponente = ProcessoEletronicoRN::getHashFromMetaDados($objDoc->componenteDigital->hash);
                if(!in_array($strHashComponente, $arrHashIndexados)){
                    $arrHashIndexados[] = $strHashComponente;
                    $nrTamanhoComponente = $objDoc->componenteDigital->tamanhoEmBytes;
                    if($nrTamanhoComponente > $nrTamanhoBytesMaximo){
                        $qtdPartes = ceil($nrTamanhoComponente / $nrTamanhoBytesMaximo);
                        $totalBarraProgresso += $qtdPartes;
                        continue;
                    }
                    $totalBarraProgresso++;
                }
            }

            return $totalBarraProgresso;
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

        private function construirCabecalho(ExpedirProcedimentoDTO $objExpedirProcedimentoDTO, $parObjTramitesAnteriores,$dblIdProcedimento=null)
        {
            if(!isset($objExpedirProcedimentoDTO)){
                throw new InfraException('Parâmetro $objExpedirProcedimentoDTO não informado.');
            }

            //Obtenção do número de registro eletrônico do processo
            $strNumeroRegistro = null;

            $objTramiteBD = new TramiteBD($this->getObjInfraIBanco());
            $objTramiteDTOFiltro = new TramiteDTO();
            $objTramiteDTOFiltro->retStrNumeroRegistro();
            $objTramiteDTOFiltro->setNumIdProcedimento($objExpedirProcedimentoDTO->getDblIdProcedimento());
            $objTramiteDTOFiltro->setStrStaTipoProtocolo(ProcessoEletronicoRN::$STA_TIPO_PROTOCOLO_PROCESSO);
            $objTramiteDTOFiltro->setOrdNumIdTramite(InfraDTO::$TIPO_ORDENACAO_DESC);
            $objTramiteDTOFiltro->setNumMaxRegistrosRetorno(1);

            $objTramiteDTO = $objTramiteBD->consultar($objTramiteDTOFiltro);
            if(isset($objTramiteDTO)) {
                $strNumeroRegistro = $objTramiteDTO->getStrNumeroRegistro();
            }

            // Consultar se processo eletrônico existe no PEN algum trâmite CANCELADO, caso
            // sim deve ser gerada uma nova NRE, pois a atual ser recusada pelo PEN quando
            // for enviado
            if(!InfraString::isBolVazia($strNumeroRegistro)) {
                if(!empty($parObjTramitesAnteriores) && is_array($parObjTramitesAnteriores)) {
                    $arrNumSituacoesTramiteEfetivado = array(
                        ProcessoeletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_ENVIADO_DESTINATARIO,
                        ProcessoeletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE,
                    );

                    $bolExisteTramiteConcluido = false;
                    foreach ($parObjTramitesAnteriores as $objTramite) {
                        //Caso exista algum trâmite realizado com sucesso para outro destinatário, número do NRE precisa ser reutilizado
                        if(in_array($objTramite->situacaoAtual, $arrNumSituacoesTramiteEfetivado)){
                            $bolExisteTramiteConcluido = true;
                        break;
                    }
                }

                if(!$bolExisteTramiteConcluido){
                    $strNumeroRegistro = null;
                }
            }
        }

        return $this->objProcessoEletronicoRN->construirCabecalho(
            $strNumeroRegistro,
            $objExpedirProcedimentoDTO->getNumIdRepositorioOrigem(),
            $objExpedirProcedimentoDTO->getNumIdUnidadeOrigem(),
            $objExpedirProcedimentoDTO->getNumIdRepositorioDestino(),
            $objExpedirProcedimentoDTO->getNumIdUnidadeDestino(),
            $objExpedirProcedimentoDTO->getBolSinUrgente(),
            $objExpedirProcedimentoDTO->getNumIdMotivoUrgencia(),
            false /*obrigarEnvioDeTodosOsComponentesDigitais*/,
            $dblIdProcedimento
        );
    }

    private function construirProcesso($dblIdProcedimento, $arrIdProcessoApensado=null, $parObjMetadadosTramiteAnterior=null)
    {
        if(!isset($dblIdProcedimento)){
            throw new InfraException('Parâmetro $dblIdProcedimento não informado.');
        }

        $objProcedimentoDTO = $this->consultarProcedimento($dblIdProcedimento);
        $objPenRelHipoteseLegalRN = new PenRelHipoteseLegalEnvioRN();

        $objProcesso = new stdClass();
        $objProcesso->staTipoProtocolo = ProcessoEletronicoRN::$STA_TIPO_PROTOCOLO_PROCESSO;
        $objProcesso->protocolo = utf8_encode($objProcedimentoDTO->getStrProtocoloProcedimentoFormatado());
        $objProcesso->nivelDeSigilo = $this->obterNivelSigiloPEN($objProcedimentoDTO->getStrStaNivelAcessoLocalProtocolo());
        $objProcesso->processoDeNegocio  = utf8_encode($this->objProcessoEletronicoRN->reduzirCampoTexto($objProcedimentoDTO->getStrNomeTipoProcedimento(), 100));
        $objProcesso->descricao          = utf8_encode($this->objProcessoEletronicoRN->reduzirCampoTexto($objProcedimentoDTO->getStrDescricaoProtocolo(), 100));
        $objProcesso->dataHoraDeProducao = $this->objProcessoEletronicoRN->converterDataWebService($objProcedimentoDTO->getDtaGeracaoProtocolo());
        if($objProcedimentoDTO->getStrStaNivelAcessoLocalProtocolo() == ProtocoloRN::$NA_RESTRITO){
            $objProcesso->hipoteseLegal = new stdClass();
            $objProcesso->hipoteseLegal->identificacao = $objPenRelHipoteseLegalRN->getIdHipoteseLegalPEN($objProcedimentoDTO->getNumIdHipoteseLegalProtocolo());
        }

        $this->atribuirProdutorProcesso($objProcesso, $objProcedimentoDTO->getNumIdUsuarioGeradorProtocolo(), $objProcedimentoDTO->getNumIdUnidadeGeradoraProtocolo());
        $this->atribuirDataHoraDeRegistro($objProcesso, $objProcedimentoDTO->getDblIdProcedimento());
        $this->atribuirDocumentos($objProcesso, $dblIdProcedimento, $parObjMetadadosTramiteAnterior);
        $this->atribuirDadosInteressados($objProcesso, $dblIdProcedimento);
        $this->adicionarProcessosApensados($objProcesso, $arrIdProcessoApensado);
        $this->atribuirDadosHistorico($objProcesso, $dblIdProcedimento);

        $objProcesso->idProcedimentoSEI = $dblIdProcedimento;
        return $objProcesso;
    }

    //TODO: Implementar mapeamento de atividades que sero enviadas para barramento (semelhante Protocolo Integrado)
    private function atribuirDadosHistorico($objProcesso, $dblIdProcedimento)
    {
        $objProcedimentoHistoricoDTO = new ProcedimentoHistoricoDTO();
        $objProcedimentoHistoricoDTO->setDblIdProcedimento($dblIdProcedimento);
        $objProcedimentoHistoricoDTO->setStrStaHistorico(ProcedimentoRN::$TH_TOTAL);
        $objProcedimentoHistoricoDTO->setStrSinGerarLinksHistorico('N');
        
        $objProcedimentoRN = new ProcedimentoRN();
        $objProcedimentoDTO = $objProcedimentoRN->consultarHistoricoRN1025($objProcedimentoHistoricoDTO);
        $arrObjAtividadeDTO = $objProcedimentoDTO->getArrObjAtividadeDTO();

        if($arrObjAtividadeDTO == null || count($arrObjAtividadeDTO) == 0) {
            throw new InfraException("Não foi possível obter andamentos do processo {$objProcesso->protocolo}");
        }

        $arrObjOperacao = array();
        foreach ($arrObjAtividadeDTO as $objAtividadeDTO) {

            $objOperacao = new stdClass();
            $objOperacao->dataHoraOperacao = $this->objProcessoEletronicoRN->converterDataWebService($objAtividadeDTO->getDthAbertura());
            $objOperacao->unidadeOperacao = utf8_encode($objAtividadeDTO->getStrDescricaoUnidade());
            $objOperacao->operacao = $this->objProcessoEletronicoRN->reduzirCampoTexto(strip_tags(utf8_encode($objAtividadeDTO->getStrNomeTarefa())),1000);
            $objOperacao->usuario = utf8_encode($objAtividadeDTO->getStrNomeUsuarioOrigem());            
            $arrObjOperacao[] = $objOperacao;
        }

        usort($arrObjOperacao,function($obj1,$obj2){
            $dt1=new DateTime($obj1->dataHoraOperacao);
            $dt2=new DateTime($obj2->dataHoraOperacao);
            return $dt1>$dt2;
            });

        $objProcesso->historico = $arrObjOperacao;
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

            $dados=ProcessoEletronicoINT::formatarHierarquia($objEstrutura);
            $nome=$dados['nome'];
            $objNivel=$dados['objNivel'];

            $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
            $objAtributoAndamentoDTO->setStrNome('UNIDADE_DESTINO_HIRARQUIA');
            $objAtributoAndamentoDTO->setStrValor($nome);
            $objAtributoAndamentoDTO->setStrIdOrigem($objNivel->numeroDeIdentificacaoDaEstrutura);
            $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;
        

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
        ProcessoEletronicoRN::desbloquearProcesso($numIdProcedimento);
    }

    public function registrarAndamentoExpedicaoAbortada($dblIdProtocolo)
    {
        //Seta todos os atributos do histórico de aborto da expedio
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
            $objProcesso->produtor->nome = utf8_encode($this->objProcessoEletronicoRN->reduzirCampoTexto($objUsuarioProdutor->getStrNome(), 150));
            //TODO: Obter tipo de pessoa física dos contatos do SEI
            $objProcesso->produtor->numeroDeIdentificacao = $objUsuarioProdutor->getDblCpfContato();
            $objProcesso->produtor->tipo = self::STA_TIPO_PESSOA_FISICA;
            //TODO: Informar dados da estrutura organizacional (estruturaOrganizacional)
        }

        $objUnidadeGeradora = $this->consultarUnidade($dblIdProcedimento);
        if(isset($objUnidadeGeradora)){
            $objProcesso->produtor->unidade = new stdClass();
            $objProcesso->produtor->unidade->nome = utf8_encode($this->objProcessoEletronicoRN->reduzirCampoTexto($objUnidadeGeradora->getStrDescricao(), 150));
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
                $interessado->nome = utf8_encode($this->objProcessoEletronicoRN->reduzirCampoTexto($participanteDTO->getStrNomeContato(), 150));
                $objProcesso->interessado[] = $interessado;
            }
        }
    }

    private function atribuirDocumentos($objProcesso, $dblIdProcedimento, $parObjMetadadosTramiteAnterior)
    {
        if(!isset($objProcesso)) {
            throw new InfraException('Parâmetro $objProcesso não informado.');
        }

        //$arrDocumentosDTO = $this->listarDocumentos($dblIdProcedimento);
        $arrDocumentosRelacionados = $this->listarDocumentosRelacionados($dblIdProcedimento);

        if(!isset($arrDocumentosRelacionados)) {
            throw new InfraException('Documentos não encontrados.');
        }

        $arrObjCompIndexadoPorIdDocumentoDTO = array();
        $objProcessoEletronicoPesquisaDTO = new ProcessoEletronicoDTO();
        $objProcessoEletronicoPesquisaDTO->setDblIdProcedimento($dblIdProcedimento);
        $objUltimoTramiteRecebidoDTO = $this->objProcessoEletronicoRN->consultarUltimoTramiteRecebido($objProcessoEletronicoPesquisaDTO);
        if(!is_null($objUltimoTramiteRecebidoDTO)){
            if ($this->objProcessoEletronicoRN->possuiComponentesComDocumentoReferenciado($objUltimoTramiteRecebidoDTO)) {
                $arrObjComponentesDigitaisDTO = $this->objProcessoEletronicoRN->listarComponentesDigitais($objUltimoTramiteRecebidoDTO);
                $arrObjCompIndexadoPorIdDocumentoDTO = InfraArray::indexarArrInfraDTO($arrObjComponentesDigitaisDTO, 'IdDocumento');
            }
        }

        $objProcesso->documento = array();
        foreach ($arrDocumentosRelacionados as $ordem => $objDocumentosRelacionados) {
            $documentoDTO = $objDocumentosRelacionados["Documento"];
            $staAssociacao = $objDocumentosRelacionados["StaAssociacao"];

            $documento = new stdClass();
            $objPenRelHipoteseLegalRN = new PenRelHipoteseLegalEnvioRN();

            //Considera o número/nome do documento externo para descrição do documento
            $boolDocumentoRecebidoComNumero = $documentoDTO->getStrStaProtocoloProtocolo() == ProtocoloRN::$TP_DOCUMENTO_RECEBIDO && $documentoDTO->getStrNumero() != null;
            $strDescricaoDocumento = ($boolDocumentoRecebidoComNumero) ? $documentoDTO->getStrNumero() : "***";

            $documento->ordem = $ordem + 1;
            $documento->descricao = utf8_encode($this->objProcessoEletronicoRN->reduzirCampoTexto($strDescricaoDocumento, 100));
            $documento->retirado = ($documentoDTO->getStrStaEstadoProtocolo() == ProtocoloRN::$TE_DOCUMENTO_CANCELADO) ? true : false;
            $documento->nivelDeSigilo = $this->obterNivelSigiloPEN($documentoDTO->getStrStaNivelAcessoLocalProtocolo());

            //Verifica se o documento faz parte de outro processo devido à sua anexação ou à sua movimentação
            if($documentoDTO->getStrProtocoloProcedimentoFormatado() != $objProcesso->protocolo){
                if($staAssociacao != RelProtocoloProtocoloRN::$TA_DOCUMENTO_MOVIDO){
                    // Caso o documento não tenha sido movido, seu protocolo é diferente devido à sua anexação à outro processo
                    $documento->protocoloDoProcessoAnexado = $documentoDTO->getStrProtocoloProcedimentoFormatado();
                    $documento->idProcedimentoAnexadoSEI = $documentoDTO->getDblIdProcedimento();
                } else {
                    // Em caso de documento movido, ele será tratado como cancelado para trâmites externos
                    $documento->retirado = true;
                }
            }

            if($documentoDTO->getStrStaNivelAcessoLocalProtocolo() == ProtocoloRN::$NA_RESTRITO){
                $documento->hipoteseLegal = new stdClass();
                $documento->hipoteseLegal->identificacao = $objPenRelHipoteseLegalRN->getIdHipoteseLegalPEN($documentoDTO->getNumIdHipoteseLegalProtocolo());
                //TODO: Adicionar nome da hipótese legal atribuida ao documento
            }
            $documento->dataHoraDeProducao = $this->objProcessoEletronicoRN->converterDataWebService($documentoDTO->getDtaGeracaoProtocolo());
            $documento->produtor = new stdClass();
            $usuarioDTO = $this->consultarUsuario($documentoDTO->getNumIdUsuarioGeradorProtocolo());
            if(isset($usuarioDTO)) {
                $documento->produtor->nome = utf8_encode($this->objProcessoEletronicoRN->reduzirCampoTexto($usuarioDTO->getStrNome(), 150));
                $documento->produtor->numeroDeIdentificacao = $usuarioDTO->getDblCpfContato();
                //TODO: Obter tipo de pessoa fsica dos contextos/contatos do SEI
                $documento->produtor->tipo = self::STA_TIPO_PESSOA_FISICA;;
            }

            $unidadeDTO = $this->consultarUnidade($documentoDTO->getNumIdUnidadeResponsavel());
            if(isset($unidadeDTO)) {
                $documento->produtor->unidade = new stdClass();
                $documento->produtor->unidade->nome = utf8_encode($this->objProcessoEletronicoRN->reduzirCampoTexto($unidadeDTO->getStrDescricao(), 150));
                $documento->produtor->unidade->tipo = self::STA_TIPO_PESSOA_ORGAOPUBLICO;
                //TODO: Informar dados da estrutura organizacional (estruturaOrganizacional)
            }

            if(array_key_exists($documentoDTO->getDblIdDocumento(), $arrObjCompIndexadoPorIdDocumentoDTO)){
                $objComponenteDigitalDTO = $arrObjCompIndexadoPorIdDocumentoDTO[$documentoDTO->getDblIdDocumento()];
                if(!empty($objComponenteDigitalDTO->getNumOrdemDocumentoReferenciado())){
                    $documento->ordemDoDocumentoReferenciado = $objComponenteDigitalDTO->getNumOrdemDocumentoReferenciado();
                }
            }

            $documento->produtor->numeroDeIdentificacao = $documentoDTO->getStrProtocoloDocumentoFormatado();

            $this->atribuirDataHoraDeRegistro($documento, $documentoDTO->getDblIdProcedimento(), $documentoDTO->getDblIdDocumento());
            $this->atribuirEspecieDocumental($documento, $documentoDTO, $parObjMetadadosTramiteAnterior);

            //TODO: Tratar campos adicionais do documento identifição do documento
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

                    $documento = $this->atribuirDadosAssinaturaDigital($documentoDTO, $documento, $componenteDigital->getStrHashConteudo());

                    if($componenteDigital->getStrMimeType() == 'outro'){
                        $documento->componenteDigital->dadosComplementaresDoTipoDeArquivo = 'outro';
                    }

                }else{
                    $this->atribuirComponentesDigitais($documento, $documentoDTO);
                }
            }else{
                $this->atribuirComponentesDigitais($documento, $documentoDTO);
            }

            // TODO: Necessário tratar informações abaixo
            //- protocoloDoDocumentoAnexado
            //- protocoloDoProcessoAnexado
            //- protocoloAnterior
            //- historico
            $documento->idDocumentoSEI = $documentoDTO->getDblIdDocumento();
            $objProcesso->documento[] = $documento;
        }
    }

    public function atribuirComponentesDigitaisRetirados($documentoDTO){

    }

    /**
    * Obtém a espécie documental relacionada ao documento do processo.
    * A espécie documental, por padrão, é obtida do mapeamento de espécies realizado pelo administrador
    * nas configurações do módulo.
    * Caso o documento tenha sido produzido por outro órgão externamente, a espécie a ser considerada será
    * aquela definida originalmente pelo seu produtor
    *
    * @param int $parDblIdProcedimento Identificador do processo
    * @param int $parDblIdDocumento Identificador do documento
    * @return int Código da espécie documental
    *
    */
    private function atribuirEspecieDocumental($parMetaDocumento, $parDocumentoDTO, $parObjMetadadosTramiteAnterior)
    {
        //Validação dos parâmetros da função
        if(!isset($parDocumentoDTO)){
            throw new InfraException('Parâmetro $parDocumentoDTO não informado.');
        }

        if(!isset($parMetaDocumento)){
            throw new InfraException('Parâmetro $parMetaDocumento não informado.');
        }

        $numCodigoEspecie = null;
        $strNomeEspecieProdutor = null;
        $dblIdProcedimento = $parDocumentoDTO->getDblIdProcedimento();
        $dblIdDocumento = $parDocumentoDTO->getDblIdDocumento();

        //Inicialmente, busca espécie documental atribuida pelo produtor em trâmite realizado anteriormente
        $objComponenteDigitalDTO = new ComponenteDigitalDTO();
        $objComponenteDigitalDTO->retNumCodigoEspecie();
        $objComponenteDigitalDTO->retStrNomeEspecieProdutor();
        $objComponenteDigitalDTO->setDblIdProcedimento($dblIdProcedimento);
        $objComponenteDigitalDTO->setDblIdDocumento($dblIdDocumento);
        $objComponenteDigitalDTO->setNumMaxRegistrosRetorno(1);
        $objComponenteDigitalDTO->setOrd('IdTramite', InfraDTO::$TIPO_ORDENACAO_DESC);

        $objComponenteDigitalBD = new ComponenteDigitalBD(BancoSEI::getInstance());
        $objComponenteDigitalDTO = $objComponenteDigitalBD->consultar($objComponenteDigitalDTO);

        if($objComponenteDigitalDTO != null){
            $numCodigoEspecie = $objComponenteDigitalDTO->getNumCodigoEspecie();
            $strNomeEspecieProdutor = utf8_encode($objComponenteDigitalDTO->getStrNomeEspecieProdutor());
        }

        //Caso a informação sobre mapeamento esteja nulo, necessário buscar tal informação no Barramento
        //A lista de documentos recuperada do trâmite anterior será indexada pela sua ordem no protocolo e
        //a espécie documental e o nome no produtar serão obtidos para atribuição ao documento
        if($objComponenteDigitalDTO != null && $numCodigoEspecie == null) {
            if(isset($parObjMetadadosTramiteAnterior)){
                $arrObjMetaDocumentosTramiteAnterior = [];

                //Obtenção de lista de documentos do processo
                $objProcesso = $parObjMetadadosTramiteAnterior->processo;
                $objDocumento = $parObjMetadadosTramiteAnterior->documento;
                $objProtocolo = isset($objProcesso) ? $objProcesso : $objDocumento;

                $arrObjMetaDocumentosTramiteAnterior = ProcessoEletronicoRN::obterDocumentosProtocolo($objProtocolo);
                if(isset($arrObjMetaDocumentosTramiteAnterior) && !is_array($arrObjMetaDocumentosTramiteAnterior)){
                    $arrObjMetaDocumentosTramiteAnterior = array($arrObjMetaDocumentosTramiteAnterior);
                }

                //Indexação dos documentos pela sua ordem
                $arrMetaDocumentosAnteriorIndexado = [];
                foreach ($arrObjMetaDocumentosTramiteAnterior as $objMetaDoc) {
                    $arrMetaDocumentosAnteriorIndexado[$objMetaDoc->ordem] = $objMetaDoc;
                }

                //Atribui espécie documental definida pelo produtor do documento e registrado no PEN, caso exista
                if(count($arrMetaDocumentosAnteriorIndexado) > 0 && array_key_exists($parMetaDocumento->ordem, $arrMetaDocumentosAnteriorIndexado)){
                    $numCodigoEspecie = $arrMetaDocumentosAnteriorIndexado[$parMetaDocumento->ordem]->especie->codigo;
                    $strNomeEspecieProdutor = utf8_encode($arrMetaDocumentosAnteriorIndexado[$parMetaDocumento->ordem]->especie->nomeNoProdutor);
                }
            }
        }

        //Aplica o mapeamento de espécies definida pelo administrador para os novos documentos
        if($numCodigoEspecie == null) {
            $numCodigoEspecie = $this->obterEspecieMapeada($parDocumentoDTO->getNumIdSerie());
            $strNomeEspecieProdutor = utf8_encode($parDocumentoDTO->getStrNomeSerie());
        }

        $parMetaDocumento->especie = new stdClass();
        $parMetaDocumento->especie->codigo = $numCodigoEspecie;
        $parMetaDocumento->especie->nomeNoProdutor = $strNomeEspecieProdutor;

        return $parMetaDocumento;
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
            $objPenRelTipoDocMapEnviadoDTO->setNumMaxRegistrosRetorno(1);
            $objPenRelTipoDocMapEnviadoDTO = $objGenericoBD->consultar($objPenRelTipoDocMapEnviadoDTO);
        }

        $numCodigoEspecieMapeada = isset($objPenRelTipoDocMapEnviadoDTO) ? $objPenRelTipoDocMapEnviadoDTO->getNumCodigoEspecie() : null;
        $numCodigoEspecieMapeada = $numCodigoEspecieMapeada ?: $this->objPenRelTipoDocMapEnviadoRN->consultarEspeciePadrao();

        if(!isset($numCodigoEspecieMapeada)) {
            throw new InfraException("Código de identificação da espécie documental não pode ser localizada para o tipo de documento {$parNumIdSerie}.");
        }

        return $numCodigoEspecieMapeada;
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
        $objDocumento = $this->atribuirDadosAssinaturaDigital($objDocumentoDTO, $objDocumento, $hashDoComponenteDigital);

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


    /**
     * Atribui a informação textual das tarjas de assinatura em metadados para envio, removendo os conteúdos de script e html
     *
     * @param DocumentoDTO $objDocumentoDTO
     * @param stdClass $objDocumento
     * @param string $strHashDocumento
     * @return void
     */
    public function atribuirDadosAssinaturaDigital($objDocumentoDTO, $objDocumento, $strHashDocumento)
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

        $dataTarjas = array();
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

        $objDocumento->componenteDigital->assinaturaDigital = array();
        foreach ($resAssinatura as $keyOrder => $assinatura) {
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

            //Testa o hash com a tarja de validação contendo antigos URLs do órgão
            $hashDoComponenteDigital = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $strConteudoAssinatura, true));
            $objConfiguracaoModPEN = ConfiguracaoModPEN::getInstance();
            $arrControleURL = $objConfiguracaoModPEN->getValor("PEN", "ControleURL", false);

            if($arrControleURL!=null && isset($hashDoComponenteDigitalAnterior) && $hashDoComponenteDigital <> $hashDoComponenteDigitalAnterior){

                foreach($arrControleURL["antigos"] as $urlAntigos){
                    $dadosURL=[
                        "atual"=>$arrControleURL["atual"],
                        "antigo"=>$urlAntigos,
                    ];
                    $hashDoComponenteDigital = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $strConteudoAssinatura, true));
                    if(isset($hashDoComponenteDigitalAnterior) && ($hashDoComponenteDigitalAnterior <> $hashDoComponenteDigital)){
                        $strConteudoAssinatura = $this->obterConteudoInternoAssinatura($objDocumentoDTO->getDblIdDocumento(), false,false,$dadosURL);
                    }
                }
            }

            //Caso o hash ainda esteja inconsistente teremos que forcar a geracao do arquivo usando as funções do sei 3.0.11
            $hashDoComponenteDigital = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $strConteudoAssinatura, true));
            if(isset($hashDoComponenteDigitalAnterior) && $hashDoComponenteDigital <> $hashDoComponenteDigitalAnterior){
                $strConteudoAssinatura = $this->obterConteudoInternoAssinatura($objDocumentoDTO->getDblIdDocumento(), true, true);
            }

            $arrInformacaoArquivo['NOME'] = $strProtocoloDocumentoFormatado . ".html";
            $arrInformacaoArquivo['CONTEUDO'] = $strConteudoAssinatura;
            $arrInformacaoArquivo['TAMANHO'] = strlen($strConteudoAssinatura);
            $arrInformacaoArquivo['MIME_TYPE'] = 'text/html';
            $arrInformacaoArquivo['ID_ANEXO'] = null;
        } else if($objDocumentoDTO->getStrStaProtocoloProtocolo() == ProtocoloRN::$TP_DOCUMENTO_RECEBIDO)  {
            $objAnexoDTO = $this->consultarAnexo($objDocumentoDTO->getDblIdDocumento());
            if(isset($objAnexoDTO)){
                //Obtenção do conteúdo do documento externo
                $strCaminhoAnexo = $this->objAnexoRN->obterLocalizacao($objAnexoDTO);

                $fp = fopen($strCaminhoAnexo, "rb");

                try {
                    $nrTamanhoMegasMaximo = $this->objPenParametroRN->getParametro('PEN_TAMANHO_MAXIMO_DOCUMENTO_EXPEDIDO');
                    $nrTamanhoBytesMaximo = $nrTamanhoMegasMaximo * pow(1024, 2);
                    $nrTamanhoBytesArquivo = filesize($strCaminhoAnexo);

                    //Verifica se o arquivo é maior que 50MB, se for, gera o hash do conteúdo do arquivo para enviar para o barramento
                    //Se não for carrega o conteúdo do arquivo e envia para o barramento
                    if ($nrTamanhoBytesArquivo > $nrTamanhoBytesMaximo) {
                        $componenteDigitalParticionado = true;
                        $strHashConteudoAssinatura = hash_file("sha256", $strCaminhoAnexo, true);
                    } else {
                        $fp = fopen($strCaminhoAnexo, "rb");
                        $strConteudoAssinatura = fread($fp, filesize($strCaminhoAnexo));
                    }

                } catch(Exception $e) {
                    throw new InfraException("Erro obtendo conteúdo do anexo do documento {$strProtocoloDocumentoFormatado}", $e);
                } finally{
                    fclose($fp);
                }

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                try {
                    $strMimeType = finfo_file($finfo, $strCaminhoAnexo);
                    $strDadosComplementaresDoTipoDeArquivo = "";
                    if(array_search($strMimeType, $this->arrPenMimeTypes) === false){
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

            } elseif ($objDocumentoDTO->getStrStaEstadoProtocolo() == ProtocoloRN::$TE_DOCUMENTO_CANCELADO) {
                //Quando não é localizado um Anexo para um documento cancelado, os dados de componente digital precisam ser enviados
                //pois o Barramento considera o componente digital do documento de forma obrigatória
                $arrInformacaoArquivo['NOME'] = 'cancelado.html';
                $arrInformacaoArquivo['CONTEUDO'] = "[documento cancelado]";
                $arrInformacaoArquivo['TAMANHO'] = 0;
                $arrInformacaoArquivo['ID_ANEXO'] = null;
                $arrInformacaoArquivo['MIME_TYPE'] = 'text/html';
                $arrInformacaoArquivo['dadosComplementaresDoTipoDeArquivo'] = 'outro';
            } else {
                throw new InfraException("Componente digital do documento {$strProtocoloDocumentoFormatado} não pode ser localizado.");
            }
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

        //Verifica se o componente digital será particionado
        if (isset($componenteDigitalParticionado)) {
            $arrInformacaoArquivo['HASH_CONTEUDO'] = base64_encode($strHashConteudoAssinatura);
        }else{
            $hashDoComponenteDigital = hash(self::ALGORITMO_HASH_DOCUMENTO, $arrInformacaoArquivo['CONTEUDO'], true);
            $arrInformacaoArquivo['HASH_CONTEUDO'] = base64_encode($hashDoComponenteDigital);
        }

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
    private function obterConteudoInternoAssinatura($parDblIdDocumento, $bolFormatoLegado=false, $bolFormatoLegado3011=false, $dadosURL=null)
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


        //para o caso de URLs antigos do órgão, ele testa o html com a tarja antiga
        $dados=[
            "parObjEditorDTO" => $objEditorDTO,
            "montarTarja" => $dadosURL==null?false:true,
            "controleURL" => $dadosURL
        ];

        if($dadosURL!=null){  
            $objEditorRN = new Editor3011RN();
            $strResultado = $objEditorRN->consultarHtmlVersao($dados);
            return $strResultado;
        }
            
            
        //fix-107. Gerar doc exatamente da forma como estava na v3.0.11
        //Raramente vai entrar aqui e para diminuir a complexidade ciclomatica
        //n encadeei com elseif nas instrucoes acima
        if($bolFormatoLegado3011){
            $objEditor3011RN = new Editor3011RN();
            $strResultado = $objEditor3011RN->consultarHtmlVersao($dados);
            return $strResultado;
        }

        $objEditorRN = new EditorRN();
        $strResultado = $objEditorRN->consultarHtmlVersao($objEditorDTO);

        return $strResultado;
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

        if(!isset($objSerieDTO)){
            throw new InfraException("Tipo de Documento não pode ser localizado. (Código: ".$parObjDocumentoDTO->getNumIdSerie().")");
        }

        $strStaNumeracao = $objSerieDTO->getStrStaNumeracao();

        if($strStaNumeracao == SerieRN::$TN_SEQUENCIAL_UNIDADE) {
            $objDocumento->identificacao = new stdClass();
            $objDocumento->identificacao->numero = utf8_encode($parObjDocumentoDTO->getStrNumero());
            $objDocumento->identificacao->siglaDaUnidadeProdutora = utf8_encode($parObjDocumentoDTO->getStrSiglaUnidadeGeradoraProtocolo());
            $objDocumento->identificacao->complemento = $this->objProcessoEletronicoRN->reduzirCampoTexto(utf8_encode($parObjDocumentoDTO->getStrDescricaoUnidadeGeradoraProtocolo()), 100);
        }else if($strStaNumeracao == SerieRN::$TN_SEQUENCIAL_ORGAO){
            $objOrgaoDTO = $this->consultarOrgao($parObjDocumentoDTO->getNumIdOrgaoUnidadeGeradoraProtocolo());
            $objDocumento->identificacao = new stdClass();
            $objDocumento->identificacao->numero = utf8_encode($parObjDocumentoDTO->getStrNumero());
            $objDocumento->identificacao->siglaDaUnidadeProdutora = utf8_encode($objOrgaoDTO->getStrSigla());
            $objDocumento->identificacao->complemento = $this->objProcessoEletronicoRN->reduzirCampoTexto(utf8_encode($objOrgaoDTO->getStrDescricao()), 100);
        }else if($strStaNumeracao == SerieRN::$TN_SEQUENCIAL_ANUAL_UNIDADE){
            $objDocumento->identificacao = new stdClass();
            $objDocumento->identificacao->siglaDaUnidadeProdutora = utf8_encode($parObjDocumentoDTO->getStrSiglaUnidadeGeradoraProtocolo());
            $objDocumento->identificacao->complemento = $this->objProcessoEletronicoRN->reduzirCampoTexto(utf8_encode($parObjDocumentoDTO->getStrDescricaoUnidadeGeradoraProtocolo()), 100);
            $objDocumento->identificacao->numero = utf8_encode($parObjDocumentoDTO->getStrNumero());
            $objDocumento->identificacao->ano = substr($parObjDocumentoDTO->getDtaGeracaoProtocolo(),6,4);
        }else if($strStaNumeracao == SerieRN::$TN_SEQUENCIAL_ANUAL_ORGAO){
            $objOrgaoDTO = $this->consultarOrgao($parObjDocumentoDTO->getNumIdOrgaoUnidadeGeradoraProtocolo());
            $objDocumento->identificacao = new stdClass();
            $objDocumento->identificacao->numero = utf8_encode($parObjDocumentoDTO->getStrNumero());
            $objDocumento->identificacao->siglaDaUnidadeProdutora = utf8_encode($objOrgaoDTO->getStrSigla());
            $objDocumento->identificacao->complemento = $this->objProcessoEletronicoRN->reduzirCampoTexto(utf8_encode($objOrgaoDTO->getStrDescricao()), 100);
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
        $objUnidadeDTO->setBolExclusaoLogica(false);
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

    /**
     * Recupera a lista de documentos do processo, mantendo sua ordem conforme definida pelo usuário após reordenações e
     * movimentações de documentos
     *
     * Esta função basicamente aplica a desestruturação do retorno da função listarDocumentosRelacionados para obter somente
     * as instãncias dos objetos DocumentoDTO
     *
     * @param num $idProcedimento
     * @return array
     */
    public function listarDocumentos($idProcedimento)
    {
        return array_map(
            function($item){
                return $item["Documento"];
            },
            $this->listarDocumentosRelacionados($idProcedimento)
        );
    }


    public function listarDocumentosRelacionados($idProcedimento)
    {
        if(!isset($idProcedimento)){
            throw new InfraException('Parâmetro $idProcedimento não informado.');
        }

        $arrObjDocumentoDTO = array();
        $arrAssociacaoDocumentos = $this->objProcessoEletronicoRN->listarAssociacoesDocumentos($idProcedimento);
        $arrIdDocumentos = array_map(function($item){ return $item["IdProtocolo"];}, $arrAssociacaoDocumentos);

        if(!empty($arrIdDocumentos)){
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

            //Mantem ordenação definida pelo usuário, indicando qual a sua associação com o processo
            $arrObjDocumentoDTO = array();
            foreach($arrAssociacaoDocumentos as $objAssociacaoDocumento){
                $dblIdDocumento = $objAssociacaoDocumento["IdProtocolo"];
                if (isset($arrObjDocumentoDTOIndexado[$dblIdDocumento])){
                    $arrObjDocumentoDTO[] = array(
                        "Documento" => $arrObjDocumentoDTOIndexado[$dblIdDocumento],
                        "StaAssociacao" => $objAssociacaoDocumento["StaAssociacao"]
                    );
                }
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
        $documentoDTO->retStrStaProtocoloProtocolo();
        //$documentoDTO->retStrNumero();

        return $this->objDocumentoRN->consultarRN0005($documentoDTO);
    }

    private function enviarComponentesDigitais($strNumeroRegistro, $numIdTramite, $strProtocolo)
    {
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
        $objComponenteDigitalDTO->setOrdNumOrdem(InfraDTO::$TIPO_ORDENACAO_ASC); //TODO-Ref: Ordenar por dois campos
        $objComponenteDigitalDTO->retDblIdDocumento();
        $objComponenteDigitalDTO->retNumTicketEnvioComponentes();
        $objComponenteDigitalDTO->retStrProtocoloDocumentoFormatado();
        $objComponenteDigitalDTO->retStrHashConteudo();
        $objComponenteDigitalDTO->retStrProtocolo();
        $objComponenteDigitalDTO->retStrNome();
        $objComponenteDigitalDTO->retDblIdProcedimento();

        $arrComponentesDigitaisDTO = $objComponenteDigitalBD->listar($objComponenteDigitalDTO);

        if (isset($arrComponentesDigitaisDTO) && count($arrComponentesDigitaisDTO) > 0) {

            //Construir objeto Componentes digitais
            $arrHashComponentesEnviados = array();
            foreach ($arrComponentesDigitaisDTO as $objComponenteDigitalDTO) {
                $this->barraProgresso->setStrRotulo(sprintf(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_DOCUMENTO, $objComponenteDigitalDTO->getStrProtocoloDocumentoFormatado()));

                $dadosDoComponenteDigital = new stdClass();
                $dadosDoComponenteDigital->ticketParaEnvioDeComponentesDigitais = $objComponenteDigitalDTO->getNumTicketEnvioComponentes();

                //Processos apensados. Mesmo erro relatado com dois arquivos iguais em docs diferentes no mesmo processo
                $dadosDoComponenteDigital->protocolo = $objComponenteDigitalDTO->getStrProtocolo();
                $dadosDoComponenteDigital->hashDoComponenteDigital = $objComponenteDigitalDTO->getStrHashConteudo();

                $objDocumentoDTO = $this->consultarDocumento($objComponenteDigitalDTO->getDblIdDocumento());
                $strNomeDocumento = $this->consultarNomeDocumentoPEN($objDocumentoDTO);
                $arrInformacaoArquivo = $this->obterDadosArquivo($objDocumentoDTO);

                //Verifica se existe o objeto anexoDTO para recuperar informações do arquivo
                $nrTamanhoArquivoMb = 0;
                $nrTamanhoBytesArquivo = 0;
                $nrTamanhoMegasMaximo = $this->objPenParametroRN->getParametro('PEN_TAMANHO_MAXIMO_DOCUMENTO_EXPEDIDO');
                $nrTamanhoBytesMaximo = ($nrTamanhoMegasMaximo * pow(1024, 2)); //Qtd de MB definido como parametro

                try {
                    //Verifica se o arquivo é maior que o tamanho máximo definido para envio, se for, realiza o particionamento do arquivo
                    if(!in_array($objComponenteDigitalDTO->getStrHashConteudo(), $arrHashComponentesEnviados)){
                        if($objDocumentoDTO->getStrStaProtocoloProtocolo() == ProtocoloRN::$TP_DOCUMENTO_RECEBIDO){
                            $objAnexoDTO = $this->consultarAnexo($objDocumentoDTO->getDblIdDocumento());
                            if(!$objAnexoDTO){
                                $strProtocoloDocumento = $documentoDTO->retStrProtocoloDocumentoFormatado();
                                throw new InfraException("Anexo do documento $strProtocoloDocumento não pode ser localizado.");
                            }

                            $strCaminhoAnexo = $this->objAnexoRN->obterLocalizacao($objAnexoDTO);
                            $nrTamanhoBytesArquivo = filesize($strCaminhoAnexo); //Tamanho total do arquivo
                            $nrTamanhoArquivoMb = ($nrTamanhoBytesArquivo / pow(1024, 2));

                            //Método que irá particionar o arquivo em partes para realizar o envio
                            $this->particionarComponenteDigitalParaEnvio($strCaminhoAnexo, $dadosDoComponenteDigital, $nrTamanhoArquivoMb,
                            $nrTamanhoMegasMaximo, $nrTamanhoBytesMaximo, $objComponenteDigitalDTO, $numIdTramite);

                            //Finalizar o envio das partes do componente digital
                            $parametros = new stdClass();
                            $parametros->dadosDoTerminoDeEnvioDePartes = $dadosDoComponenteDigital;
                            $this->objProcessoEletronicoRN->sinalizarTerminoDeEnvioDasPartesDoComponente($parametros);

                        } else {
                            $arrInformacaoArquivo = $this->obterDadosArquivo($objDocumentoDTO);
                            $dadosDoComponenteDigital->conteudoDoComponenteDigital = new SoapVar($arrInformacaoArquivo['CONTEUDO'], XSD_BASE64BINARY);

                            $parametros = new stdClass();
                            $parametros->dadosDoComponenteDigital = $dadosDoComponenteDigital;
                            $result = $this->objProcessoEletronicoRN->enviarComponenteDigital($parametros);

                            $this->barraProgresso->mover($this->contadorDaBarraDeProgresso);
                            $this->contadorDaBarraDeProgresso++;
                        }

                        $arrHashComponentesEnviados[] = $objComponenteDigitalDTO->getStrHashConteudo();
                    }

                    //Bloquea documento para atualizao, j que ele foi visualizado
                    $this->objDocumentoRN->bloquearConteudo($objDocumentoDTO);
                    $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento(sprintf('Enviando %s %s', $strNomeDocumento,
                    $objComponenteDigitalDTO->getStrProtocoloDocumentoFormatado()), 'S'));
                } catch (\Exception $e) {
                    $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento(sprintf('Enviando %s %s', $strNomeDocumento,
                    $objComponenteDigitalDTO->getStrProtocoloDocumentoFormatado()), 'N'));
                    throw new InfraException("Error Processing Request", $e);
                }
            }

        }
    }


    /**
    * Método responsável por realizar o particionamento do componente digital a ser enviado, de acordo com o parametro (PEN_TAMANHO_MAXIMO_DOCUMENTO_EXPEDIDO)
    * @author Josinaldo Júnior <josinaldo.junior@basis.com.br>
    * @param $strCaminhoAnexo
    * @param $dadosDoComponenteDigital
    * @param $nrTamanhoArquivoMb
    * @param $nrTamanhoMegasMaximo
    * @param $nrTamanhoBytesMaximo
    * @param $objComponenteDigitalDTO
    * @throws InfraException
    */
    private function enviarComponenteDigitalParticionado($strCaminhoAnexo, $dadosDoComponenteDigital, $nrTamanhoArquivoMb, $nrTamanhoMegasMaximo, $nrTamanhoBytesMaximo, $objComponenteDigitalDTO)
    {
        $qtdPartes = ceil($nrTamanhoArquivoMb / $nrTamanhoMegasMaximo);

        //Abre o arquivo para leitura
        $fp = fopen($strCaminhoAnexo, "rb");

        try{
            $inicio = 0;
            //Lê o arquivo em partes para realizar o envio
            for ($i = 1; $i <= $qtdPartes; $i++)
            {
                $this->barraProgresso->setStrRotulo(sprintf(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_DOCUMENTO  , $objComponenteDigitalDTO->getStrProtocoloDocumentoFormatado())." (Componente digital: parte $i de $qtdPartes)");
                $parteDoArquivo      = stream_get_contents($fp, $nrTamanhoBytesMaximo, $inicio);
                $tamanhoParteArquivo = strlen($parteDoArquivo);

                //Cria um objeto com as informa<E7><F5>es da parte do componente digital
                $identificacaoDaParte = new stdClass();
                $identificacaoDaParte->inicio = $inicio;
                $identificacaoDaParte->fim = ($inicio + $tamanhoParteArquivo);

                $dadosDoComponenteDigital->identificacaoDaParte = $identificacaoDaParte;
                $dadosDoComponenteDigital->conteudoDaParteDeComponenteDigital = new SoapVar($parteDoArquivo, XSD_BASE64BINARY);

                $parametros = new stdClass();
                $parametros->dadosDaParteDeComponenteDigital = $dadosDoComponenteDigital;

                //Envia uma parte de um componente digital
                $resultado = $this->objProcessoEletronicoRN->enviarParteDeComponenteDigital($parametros);
                $inicio = ($nrTamanhoBytesMaximo * $i);
            }
        }
        finally{
            fclose($fp);
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
            $objInfraException->adicionarValidacao('Não é possível tramitar um processo sem documentos', $strAtributoValidacao);
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
            $strMapeamentoEnvioPadrao = $this->objPenParametroRN->getParametro("PEN_ESPECIE_DOCUMENTAL_PADRAO_ENVIO");

            foreach($arrDocumentoDTO as $objDocumentoDTO) {
                $objDocMapDTO->unSetTodos();
                $objDocMapDTO->setNumIdSerie($objDocumentoDTO->getNumIdSerie());

                if(empty($strMapeamentoEnvioPadrao) && $objGenericoBD->contar($objDocMapDTO) == 0) {
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
            $objInfraException->adicionarValidacao("Não é possível tramitar um processo aberto em mais de uma unidade. ($strSiglaUnidade)", $strAtributoValidacao);
        }
    }

    private function validarNivelAcessoProcesso(InfraException $objInfraException, ProcedimentoDTO $objProcedimentoDTO, $strAtributoValidacao = null)
    {
        if ($objProcedimentoDTO->getStrStaNivelAcessoLocalProtocolo() == ProtocoloRN::$NA_SIGILOSO) {
            $objInfraException->adicionarValidacao('Não é possível tramitar um processo com informações sigilosas.', $strAtributoValidacao);
        }
    }

    /**
    * Valida existncia da Hiptese legal de Envio
    * @param InfraException $objInfraException
    * @param ProcedimentoDTO $objProcedimentoDTO
    * @param string $strAtributoValidacao
    */
    private function validarHipoteseLegalEnvio(InfraException $objInfraException, ProcedimentoDTO $objProcedimentoDTO, $strAtributoValidacao=null)
    {
        if ($objProcedimentoDTO->getStrStaNivelAcessoLocalProtocolo() == ProtocoloRN::$NA_RESTRITO) {
            if (empty($objProcedimentoDTO->getNumIdHipoteseLegalProtocolo())) {
                $objInfraException->adicionarValidacao('Não é possível tramitar um processo de nível restrito sem a hipótese legal mapeada.', $strAtributoValidacao);
            }

            $objPenRelHipoteseLegalEnvioRN = new PenRelHipoteseLegalEnvioRN();
            if (!empty($objProcedimentoDTO->getNumIdHipoteseLegalProtocolo()) && empty($objPenRelHipoteseLegalEnvioRN->getIdHipoteseLegalPEN($objProcedimentoDTO->getNumIdHipoteseLegalProtocolo()))) {
                $objHipoteseLegalDTO = new HipoteseLegalDTO();
                $objHipoteseLegalDTO->setNumIdHipoteseLegal($objProcedimentoDTO->getNumIdHipoteseLegalProtocolo());
                $objHipoteseLegalDTO->retStrNome();
                $objHipoteseLegalRN = new HipoteseLegalRN();
                $dados = $objHipoteseLegalRN->consultar($objHipoteseLegalDTO);
                $objInfraException->adicionarValidacao('Hipótese legal "' . $dados->getStrNome() . '" do processo ' . $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado() . ' não mapeada', $strAtributoValidacao);
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

            foreach($arrObjDocumentoDTO as $objDocumentoDTO) {
                $objAssinaturaDTO->setDblIdDocumento($objDocumentoDTO->getDblIdDocumento());

                // Se o documento no tem assinatura e não foi cancelado então cai na regra de validao
                if($this->objAssinaturaRN->contarRN1324($objAssinaturaDTO) == 0 && $objDocumentoDTO->getStrStaEstadoProtocolo() != ProtocoloRN::$TE_DOCUMENTO_CANCELADO && ($objDocumentoDTO->getStrStaDocumento() == DocumentoRN::$TD_EDITOR_EDOC || $objDocumentoDTO->getStrStaDocumento() == DocumentoRN::$TD_EDITOR_INTERNO) ){
                    $bolAssinaturaCorretas = false;
                }
            }
        }

        if($bolAssinaturaCorretas !== true) {
            $objInfraException->adicionarValidacao('Não é possível tramitar um processos com documentos gerados e não assinados', $strAtributoValidacao);
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
            case ProtocoloRN::$NA_PUBLICO:
                return self::STA_SIGILO_PUBLICO;
            break;
            case ProtocoloRN::$NA_RESTRITO:
                return self::STA_SIGILO_RESTRITO;
            break;
            case ProtocoloRN::$NA_SIGILOSO:
                return self::STA_SIGILO_SIGILOSO;
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
    * Método responsável por realizar o particionamento do componente digital a ser enviado, de acordo com o parametro (PEN_TAMANHO_MAXIMO_DOCUMENTO_EXPEDIDO)
    * @author Josinaldo Júnior <josinaldo.junior@basis.com.br>
    * @param $strCaminhoAnexo
    * @param $dadosDoComponenteDigital
    * @param $nrTamanhoArquivoMb
    * @param $nrTamanhoMegasMaximo
    * @param $nrTamanhoBytesMaximo
    * @param $objComponenteDigitalDTO
    * @throws InfraException
    */
    private function particionarComponenteDigitalParaEnvio($strCaminhoAnexo, $dadosDoComponenteDigital, $nrTamanhoArquivoMb, $nrTamanhoMegasMaximo,
    $nrTamanhoBytesMaximo, $objComponenteDigitalDTO, $numIdTramite)
    {
        //Faz o cálculo para obter a quantidade de partes que o arquivo será particionado, sempre arrendondando para cima
        $qtdPartes = ceil($nrTamanhoArquivoMb / $nrTamanhoMegasMaximo);
        //Abre o arquivo para leitura
        $fp = fopen($strCaminhoAnexo, "rb");
        $inicio = 0;
        //Lê o arquivo em partes para realizar o envio
        for ($i = 1; $i <= $qtdPartes; $i++)
        {
            $parteDoArquivo      = stream_get_contents($fp, $nrTamanhoBytesMaximo, $inicio);
            $tamanhoParteArquivo = strlen($parteDoArquivo);
            $fim = $inicio + $tamanhoParteArquivo;
            try{
                $this->enviarParteDoComponenteDigital($inicio, $fim, $parteDoArquivo, $dadosDoComponenteDigital);
                $this->barraProgresso->mover($this->contadorDaBarraDeProgresso);
                $this->contadorDaBarraDeProgresso++;
            }catch (Exception $e){
                //Armazena as partes que não foram enviadas para tentativa de reenvio posteriormente
                $arrPartesComponentesDigitaisNaoEnviadas[] = $inicio;
            }
            $inicio = ($nrTamanhoBytesMaximo * $i);
        }

        //Verifica se existem partes do componente digital que não foram enviadas para tentar realizar o envio novamente
        if(isset($arrPartesComponentesDigitaisNaoEnviadas)){
            $nrTotalPartesNaoEnviadas = count($arrPartesComponentesDigitaisNaoEnviadas);
            $i = 1;
            //Percorre as partes que n<E3>o foram enviadas para reenvia-las
            foreach ($arrPartesComponentesDigitaisNaoEnviadas as $parteComponenteNaoEnviada)
            {
                $conteudoDaParteNaoEnviadaDoArquivo = stream_get_contents($fp, $nrTamanhoBytesMaximo, $parteComponenteNaoEnviada);
                $fim = ($parteComponenteNaoEnviada + strlen($conteudoDaParteNaoEnviadaDoArquivo));
                try{
                    $this->enviarParteDoComponenteDigital($parteComponenteNaoEnviada, $fim, $conteudoDaParteNaoEnviadaDoArquivo, $dadosDoComponenteDigital);
                }catch (Exception $e){
                    throw $e;
                }
                $i++;
            }
        }
        fclose($fp);
    }


    /**
    * Método responsavel por realizar o envio de uma parte especifica de um componente digital
    * @author Josinaldo Júnior <josinaldo.junior@basis.com.br>
    * @param $parInicio
    * @param $parFim
    * @param $parParteDoArquivo
    * @param $parDadosDoComponenteDigital
    */
    private function enviarParteDoComponenteDigital($parInicio, $parFim, $parParteDoArquivo, $parDadosDoComponenteDigital){
        //Cria um objeto com as informa<E7><F5>es da parte do componente digital
        $identificacaoDaParte = new stdClass();
        $identificacaoDaParte->inicio = $parInicio;
        $identificacaoDaParte->fim = $parFim;
        $parDadosDoComponenteDigital->identificacaoDaParte = $identificacaoDaParte;
        $parDadosDoComponenteDigital->conteudoDaParteDeComponenteDigital = new SoapVar($parParteDoArquivo, XSD_BASE64BINARY);

        $parametros = new stdClass();
        $parametros->dadosDaParteDeComponenteDigital = $parDadosDoComponenteDigital;

        //Envia uma parte de um componente digital para o barramento
        $this->objProcessoEletronicoRN->enviarParteDeComponenteDigital($parametros);
    }


    /**
    * Método responsável por realizar o envio da parte de um componente digital
    * @author Josinaldo Júnior <josinaldo.junior@basis.com.br>
    * @param $parametros
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
    * Método responsável por sinalizar o término do envio das partes de um componente digital
    * @author Josinaldo Júnior <josinaldo.junior@basis.com.br>
    * @param $parametros
    * @return mixed
    * @throws InfraException
    */
    public function sinalizarTerminoDeEnvioDasPartesDoComponente($parametros)
    {
        try {
            return $this->getObjPenWs()->sinalizarTerminoDeEnvioDasPartesDoComponente($parametros);
        } catch (\Exception $e) {
            $mensagem = "Falha em sinalizar o término de envio das partes do componente digital";
            $detalhes = InfraString::formatarJavaScript($this->tratarFalhaWebService($e));
            throw new InfraException($mensagem, $e, $detalhes);
        }
    }


    /**
    * Recebe o recibo de tramite do procedimento do barramento
    *
    * @param int $parNumIdTramite
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

        } catch (\Exception $e) {
            $strMensagem = "Falha na obtenção do recibo de envio de protocolo do trâmite $parNumIdTramite. $e";
            LogSEI::getInstance()->gravar($strMensagem, InfraLog::$ERRO);
        }
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
    * Cancela uma expedio de um Procedimento para outra unidade
    *
    * @param int $dblIdProcedimento
    * @throws InfraException
    */
    protected function cancelarTramiteControlado($dblIdProcedimento)
    {
        //Busca os dados do protocolo
        $objDtoProtocolo = new ProtocoloDTO();
        $objDtoProtocolo->retStrProtocoloFormatado();
        $objDtoProtocolo->retDblIdProtocolo();
        $objDtoProtocolo->setDblIdProtocolo($dblIdProcedimento);

        $objProtocoloBD = new ProtocoloBD($this->getObjInfraIBanco());
        $objDtoProtocolo = $objProtocoloBD->consultar($objDtoProtocolo);

        $this->cancelarTramiteInterno($objDtoProtocolo);

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

        $objTramiteDTO = new TramiteDTO();
        $objTramiteDTO->setNumIdProcedimento($objDtoProtocolo->getDblIdProtocolo());
        $objTramiteDTO->setStrStaTipoTramite(ProcessoEletronicoRN::$STA_TIPO_TRAMITE_ENVIO);
        $objTramiteDTO->setOrd('Registro', InfraDTO::$TIPO_ORDENACAO_DESC);
        $objTramiteDTO->setNumMaxRegistrosRetorno(1);
        $objTramiteDTO->retNumIdTramite();

        $objTramiteBD = new TramiteBD($this->getObjInfraIBanco());
        $objTramiteDTO = $objTramiteBD->consultar($objTramiteDTO);

        if(!isset($objTramiteDTO)){
            throw new InfraException("Trâmite não encontrado para o processo {$objDtoProtocolo->getDblIdProtocolo()}.");
        }

        //Armazena o id do protocolo
        $dblIdProcedimento = $objDtoProtocolo->getDblIdProtocolo();

        $tramites = $this->objProcessoEletronicoRN->consultarTramites($objTramiteDTO->getNumIdTramite(), null, $objPenUnidadeDTO->getNumIdUnidadeRH(), null, null, $numIdRespositorio);
        $tramite = $tramites ? $tramites[0] : null;

        if (!$tramite) {
            $numIdTramite = $objTramiteDTO->getNumIdTramite();
            $numIdProtoloco = $objDtoProtocolo->getDblIdProtocolo();
            throw new InfraException("Trâmite $numIdTramite não encontrado para o processo $numIdProtoloco.");
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
            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO:
                throw new InfraException("O trâmite externo para esse processo encontra-se recusado.");
            break;
        }

        //Somente solicita cancelamento ao PEN se processo ainda não estiver cancelado
        if(!in_array($numSituacaoAtual, array(ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO_AUTOMATICAMENTE, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO))) {
            $this->objProcessoEletronicoRN->cancelarTramite($tramite->IDT);
        }

        //Desbloqueia o processo
        ProcessoEletronicoRN::desbloquearProcesso($dblIdProcedimento);

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


    private function consultarTramitesAnteriores($parStrNumeroRegistro)
    {
        return isset($parStrNumeroRegistro) ? $this->objProcessoEletronicoRN->consultarTramites(null, $parStrNumeroRegistro) : null;
    }

    private function necessitaCancelamentoTramiteAnterior($parArrTramitesAnteriores)
    {
        if(!empty($parArrTramitesAnteriores) && is_array($parArrTramitesAnteriores)){
            $objUltimoTramite = $parArrTramitesAnteriores[count($parArrTramitesAnteriores) - 1];
            if($objUltimoTramite->situacaoAtual == ProcessoeletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO){
                return $objUltimoTramite;
            }
        }
        return null;
    }


    /**
     * Recupera lista de tarjas de assinaturas aplicadas ao documento em seu formato HTML
     *
     * Este método foi baseado na implementação presente em AssinaturaRN::montarTarjas.
     * Devido a estrutura interna do SEI, não existe uma forma de reaproveitar as regras de montagem de tarjas
     * de forma individual, restando como última alternativa a reprodução das regras até que esta seja encapsulado pelo core do SEI
     *
     * @param DocumentoDTO $objDocumentoDTO
     * @return array
     */
    protected function listarTarjasHTMLConectado(DocumentoDTO $objDocumentoDTO) {
        try {

          $arrResposta = array();

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
            $objTarjaAssinaturaDTO->setNumIdTarjaAssinatura(array_unique(InfraArray::converterArrInfraDTO($arrObjAssinaturaDTO,'IdTarjaAssinatura')),InfraDTO::$OPER_IN);

            $objTarjaAssinaturaRN = new TarjaAssinaturaRN();
            $arrObjTarjaAssinaturaDTO = InfraArray::indexarArrInfraDTO($objTarjaAssinaturaRN->listar($objTarjaAssinaturaDTO),'IdTarjaAssinatura');

            foreach ($arrObjAssinaturaDTO as $objAssinaturaDTO) {
              if (!isset($arrObjTarjaAssinaturaDTO[$objAssinaturaDTO->getNumIdTarjaAssinatura()])) {
                throw new InfraException('Tarja associada com a assinatura "' . $objAssinaturaDTO->getNumIdAssinatura() . '" não encontrada.');
              }

              $objTarjaAutenticacaoDTOAplicavel = $arrObjTarjaAssinaturaDTO[$objAssinaturaDTO->getNumIdTarjaAssinatura()];
              $strTarja = $objTarjaAutenticacaoDTOAplicavel->getStrTexto();
              $strTarja = preg_replace("/@logo_assinatura@/s", '<img alt="logotipo" src="data:image/png;base64,' . $objTarjaAutenticacaoDTOAplicavel->getStrLogo() . '" />', $strTarja);
              $strTarja = preg_replace("/@nome_assinante@/s", $objAssinaturaDTO->getStrNome(), $strTarja);
              $strTarja = preg_replace("/@tratamento_assinante@/s", $objAssinaturaDTO->getStrTratamento(), $strTarja);
              $strTarja = preg_replace("/@data_assinatura@/s", substr($objAssinaturaDTO->getDthAberturaAtividade(), 0, 10), $strTarja);
              $strTarja = preg_replace("/@hora_assinatura@/s", substr($objAssinaturaDTO->getDthAberturaAtividade(), 11, 5), $strTarja);
              $strTarja = preg_replace("/@codigo_verificador@/s", $objDocumentoDTO->getStrProtocoloDocumentoFormatado(), $strTarja);
              $strTarja = preg_replace("/@crc_assinatura@/s", $objDocumentoDTO->getStrCrcAssinatura(), $strTarja);
              $strTarja = preg_replace("/@numero_serie_certificado_digital@/s", $objAssinaturaDTO->getStrNumeroSerieCertificado(), $strTarja);
              $strTarja = preg_replace("/@tipo_conferencia@/s", InfraString::transformarCaixaBaixa($objDocumentoDTO->getStrDescricaoTipoConferencia()), $strTarja);
              $arrResposta[] = EditorRN::converterHTML($strTarja);
            }

            $objTarjaAssinaturaDTO = new TarjaAssinaturaDTO();
            $objTarjaAssinaturaDTO->retStrTexto();
            $objTarjaAssinaturaDTO->setStrStaTarjaAssinatura(TarjaAssinaturaRN::$TT_INSTRUCOES_VALIDACAO);

            $objTarjaAssinaturaDTO = $objTarjaAssinaturaRN->consultar($objTarjaAssinaturaDTO);

            if ($objTarjaAssinaturaDTO != null){
              $strLinkAcessoExterno = '';
              if (strpos($objTarjaAssinaturaDTO->getStrTexto(),'@link_acesso_externo_processo@')!==false){
                $objEditorRN = new EditorRN();
                $strLinkAcessoExterno = $objEditorRN->recuperarLinkAcessoExterno($objDocumentoDTO);
              }
              $strTarja = $objTarjaAssinaturaDTO->getStrTexto();
              $strTarja = preg_replace("/@qr_code@/s", '<img align="center" alt="QRCode Assinatura" title="QRCode Assinatura" src="data:image/png;base64,' . $objDocumentoDTO->getStrQrCodeAssinatura() . '" />', $strTarja);
              $strTarja = preg_replace("/@codigo_verificador@/s", $objDocumentoDTO->getStrProtocoloDocumentoFormatado(), $strTarja);
              $strTarja = preg_replace("/@crc_assinatura@/s", $objDocumentoDTO->getStrCrcAssinatura(), $strTarja);
              $strTarja = preg_replace("/@link_acesso_externo_processo@/s", $strLinkAcessoExterno, $strTarja);
              $arrResposta[] = EditorRN::converterHTML($strTarja);
            }
          }

          return $arrResposta;

        } catch (Exception $e) {
          throw new InfraException('Erro montando tarja de assinatura.',$e);
        }
      }

    public function setEventoEnvioMetadados(callable $callback)
    {
        $this->fnEventoEnvioMetadados = $callback;
    }

    private function lancarEventoEnvioMetadados($parNumIdTramite)
    {
        if(isset($this->fnEventoEnvioMetadados)){
            $evento = $this->fnEventoEnvioMetadados;
            $evento($parNumIdTramite);
        }
    }

}
