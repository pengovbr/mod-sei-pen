<?php

require_once DIR_SEI_WEB.'/SEI.php';


class ReceberProcedimentoRN extends InfraRN
{
    const STR_APENSACAO_PROCEDIMENTOS = 'Relacionamento representando a apensa��o de processos recebidos externamente';
    const NUM_ESPECIE_PEN_ANEXO = 179;

    private $objProcessoEletronicoRN;
    private $objPenRelTipoDocMapRecebidoRN;
    private $objProcedimentoAndamentoRN;
    private $objRelProtocoloProtocoloRN;
    private $objPenParametroRN;
    private $objProcedimentoRN;
    private $objDocumentoRN;
    public $destinatarioReal;
    private $objPenDebug;
    private $objProtocoloRN;
    private $objSeiRN;
    private $objEnviarReciboTramiteRN;

    public function __construct()
    {
        parent::__construct();
        $this->objSeiRN = new SeiRN();
        $this->objProtocoloRN = new ProtocoloRN();
        $this->objProcedimentoRN = new ProcedimentoRN();
        $this->objDocumentoRN = new DocumentoRN();
        $this->objInfraParametro = new InfraParametro(BancoSEI::getInstance());
        $this->objProcessoEletronicoRN = new ProcessoEletronicoRN();
        $this->objProcedimentoAndamentoRN = new ProcedimentoAndamentoRN();
        $this->objReceberComponenteDigitalRN = new ReceberComponenteDigitalRN();
        $this->objRelProtocoloProtocoloRN = new RelProtocoloProtocoloRN();
        $this->objPenRelTipoDocMapRecebidoRN = new PenRelTipoDocMapRecebidoRN();
        $this->objEnviarReciboTramiteRN = new EnviarReciboTramiteRN();
        $this->objPenParametroRN = new PenParametroRN();
        $this->objPenDebug = DebugPen::getInstance("PROCESSAMENTO");
    }

    protected function inicializarObjInfraIBanco()
    {
        return BancoSEI::getInstance();
    }


    /**
     * Processa o recebimento de tr�mites de processos, fazendo o devido controle de concorr�ncia
     *
     * @param int $parNumIdentificacaoTramite
     * @return void
     */
    public function receberProcedimento($parNumIdentificacaoTramite)
    {
        if (!isset($parNumIdentificacaoTramite)) {
            throw new InfraException('Par�metro $parNumIdentificacaoTramite n�o informado.');
        }

        $this->gravarLogDebug("Solicitando metadados do tr�mite " . $parNumIdentificacaoTramite, 1);
        $objMetadadosProcedimento = $this->objProcessoEletronicoRN->solicitarMetadados($parNumIdentificacaoTramite);
        if (!isset($objMetadadosProcedimento)) {
            throw new InfraException("Metadados do tr�mite n�o pode recuperado do PEN.");
        }

        try{
            // Inicializa��o do recebimento do processo, abrindo nova transa��o e controle de concorr�ncia,
            // evitando processamento simult�neo de cadastramento do mesmo processo
            $arrChavesSincronizacao = array();
            $arrChavesSincronizacao["IdTramite"] = $objMetadadosProcedimento->IDT;
            $arrChavesSincronizacao["NumeroRegistro"] = $objMetadadosProcedimento->metadados->NRE;
            $objProtocolo = ProcessoEletronicoRN::obterProtocoloDosMetadados($objMetadadosProcedimento);
            $bolEhProcesso = $objProtocolo->staTipoProtocolo == ProcessoEletronicoRN::$STA_TIPO_PROTOCOLO_PROCESSO;
            $strIdTarefa = $bolEhProcesso ? ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO : ProcessoEletronicoRN::$TI_DOCUMENTO_AVULSO_RECEBIDO;
            $arrChavesSincronizacao["IdTarefa"] = ProcessoEletronicoRN::obterIdTarefaModulo($strIdTarefa);

            if($this->objProcedimentoAndamentoRN->sinalizarInicioRecebimento($arrChavesSincronizacao)){
                $objTramite = $this->consultarTramite($parNumIdentificacaoTramite);

                // Valida os metadados e baixa os documentos antes de iniciar uma transa��o com o banco
                $this->validarMetadadosDoProtocolo($objMetadadosProcedimento);
                $arrHashComponenteBaixados = $this->baixarComponentesDigitais($objTramite, $objMetadadosProcedimento);

                // Processa o recebimento do processo em uma transa��o isolada
                $objMetadadosProcedimento->arrHashComponenteBaixados = $arrHashComponenteBaixados;
                $this->receberProcedimentoInterno($objMetadadosProcedimento);
            }
        } catch(Exception $e) {
            $mensagemErro = InfraException::inspecionar($e);
            $this->gravarLogDebug($mensagemErro);
            LogSEI::getInstance()->gravar($mensagemErro);
            throw $e;
        }
    }


    protected function receberProcedimentoInternoControlado($parObjMetadadosProcedimento)
    {
        try {
            $numIdTramite = $parObjMetadadosProcedimento->IDT;
            $strNumeroRegistro = $parObjMetadadosProcedimento->metadados->NRE;
            $arrHashComponenteBaixados = $parObjMetadadosProcedimento->arrHashComponenteBaixados;
            $objProtocolo = ProcessoEletronicoRN::obterProtocoloDosMetadados($parObjMetadadosProcedimento);
            $this->gravarLogDebug("Inicializado transa��o para recebimento do tr�mite $numIdTramite do protocolo " . $objProtocolo->protocolo, 3);

            $bolEhProcesso = $objProtocolo->staTipoProtocolo == ProcessoEletronicoRN::$STA_TIPO_PROTOCOLO_PROCESSO;
            $strIdTarefa = $bolEhProcesso ? ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO : ProcessoEletronicoRN::$TI_DOCUMENTO_AVULSO_RECEBIDO;
            $numIdTarefa = ProcessoEletronicoRN::obterIdTarefaModulo($strIdTarefa);

            // Tratamento para evitar o recebimento simult�neo do mesmo procedimento em servi�os/processos concorrentes
            if(!$this->objProcedimentoAndamentoRN->sincronizarRecebimentoProcessos($strNumeroRegistro, $numIdTramite, $numIdTarefa)){
                $this->gravarLogDebug("Tr�mite de recebimento $numIdTramite j� se encontra em processamento", 3);
                return false;
            }

            // Verifica se processo j� foi registrado para esse tr�mite, cancelando este recebimento
            if($this->tramiteRecebimentoRegistrado($strNumeroRegistro, $numIdTramite)) {
                $this->gravarLogDebug("Tr�mite de recebimento $numIdTramite j� registrado para o processo " . $objProtocolo->protocolo, 3);
                return false;
            }

            // O recebimento do processo deve ser realizado na unidade definida em [UNIDADE_GERADORA_DOCUMENTO_RECEBIDO] que n�o dever� possuir usu�rios
            // habilitados, funcionando como uma �rea dedicada unicamente para o recebimento de processos e documentos.
            // Isto � necess�rio para que o processo recebido n�o seja criado diretamente dentro da unidade de destino, o que permitiria a altera��o de
            // todos os metadados do processo, comportamento n�o permitido pelas regras de neg�cio do PEN.
            ModPenUtilsRN::simularLoginUnidadeRecebimento();

            //Substituir a unidade destinat�ria pela unidade centralizadora definida pelo Gestor de Protocolo no PEN
            $this->substituirDestinoParaUnidadeReceptora($parObjMetadadosProcedimento, $numIdTramite);

            // Obt�m situa��o do tr�mite antes de iniciar o recebimento dos documentos
            $objTramite = $this->consultarTramite($numIdTramite);

            //Verifica se o tr�mite est� recusado
            if($objTramite->situacaoAtual == ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO) {
                throw new InfraException("Tr�mite $numIdTramite j� se encontra recusado. Cancelando o recebimento do processo");
            }

            if($objProtocolo->staTipoProtocolo == ProcessoEletronicoRN::$STA_TIPO_PROTOCOLO_PROCESSO){
                $objProtocolo = ProcessoEletronicoRN::desmembrarProcessosAnexados($objProtocolo);
            }

            $this->gravarLogDebug("Persistindo/atualizando dados do processo com NRE " . $strNumeroRegistro, 2);
            list($objProcedimentoDTO, $bolProcedimentoExistente) = $this->registrarProcesso(
                $strNumeroRegistro,
                $numIdTramite,
                $objProtocolo,
                $parObjMetadadosProcedimento
            );

            $this->objProcedimentoAndamentoRN->setOpts($strNumeroRegistro, $numIdTramite, $numIdTarefa, $objProcedimentoDTO->getDblIdProcedimento());
            $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento('Obtendo metadados do processo', 'S'));

            $this->gravarLogDebug("Registrando tr�mite externo do processo", 2);
            $objProcessoEletronicoDTO = $this->objProcessoEletronicoRN->cadastrarTramiteDeProcesso(
                $objProcedimentoDTO->getDblIdProcedimento(),
                $strNumeroRegistro,
                $numIdTramite,
                ProcessoEletronicoRN::$STA_TIPO_TRAMITE_RECEBIMENTO,
                null,
                $parObjMetadadosProcedimento->metadados->remetente->identificacaoDoRepositorioDeEstruturas,
                $parObjMetadadosProcedimento->metadados->remetente->numeroDeIdentificacaoDaEstrutura,
                $parObjMetadadosProcedimento->metadados->destinatario->identificacaoDoRepositorioDeEstruturas,
                $parObjMetadadosProcedimento->metadados->destinatario->numeroDeIdentificacaoDaEstrutura,
                $objProtocolo
            );

            //Verifica se o tramite se encontra na situa��o correta
            $arrObjTramite = $this->objProcessoEletronicoRN->consultarTramites($numIdTramite);
            if(!isset($arrObjTramite) || count($arrObjTramite) != 1) {
                throw new InfraException("Tr�mite n�o pode ser localizado pelo identificado $numIdTramite.");
            }

            $objTramite = $arrObjTramite[0];
            if($objTramite->situacaoAtual != ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO) {
                throw new InfraException("Desconsiderando recebimento do processo devido a situa��o de tr�mite inconsistente: " . $objTramite->situacaoAtual);
            }

            $this->atribuirComponentesDigitaisAosDocumentos($objProcedimentoDTO, $strNumeroRegistro, $numIdTramite, $arrHashComponenteBaixados, $objProtocolo);

            $this->atribuirObservacoesSobreDocumentoReferenciado($objProcedimentoDTO, $objProtocolo);

            $this->atribuirProcessosAnexados($objProtocolo);

            $this->enviarProcedimentoUnidade($objProcedimentoDTO, null, $bolProcedimentoExistente);

            $this->validarPosCondicoesTramite($parObjMetadadosProcedimento, $objProcedimentoDTO);

            $this->gravarLogDebug("Enviando recibo de conclus�o do tr�mite $numIdTramite", 2);
            $this->objEnviarReciboTramiteRN->enviarReciboTramiteProcesso($numIdTramite, $arrHashComponenteBaixados);

            $this->gravarLogDebug("Registrando a conclus�o do recebimento do tr�mite $numIdTramite", 2);
        } catch (Exception $e) {
            $mensagemErro = InfraException::inspecionar($e);
            $this->gravarLogDebug($mensagemErro);
            LogSEI::getInstance()->gravar($mensagemErro);
            throw $e;
        }
    }

    /**
     * Valida��o preliminar dos metadados do protocolo
     *
     * Esta valida��o deve ser feita somente sobre os metadados indicados pelo remetente e antes de iniciar
     * o download e cria��o do processo no destinat�rio
     *
     * @param stdClass $parObjMetadadosProcedimento
     * @return void
     */
    private function validarMetadadosDoProtocolo($parObjMetadadosProcedimento)
    {
        // Valida��o dos dados do processo recebido
        $objProtocolo = ProcessoEletronicoRN::obterProtocoloDosMetadados($parObjMetadadosProcedimento);
        $numIdTramite = $parObjMetadadosProcedimento->IDT;

        $this->validarDadosDestinatario($parObjMetadadosProcedimento);
        $this->validarComponentesDigitais($objProtocolo, $numIdTramite);
        $this->validarExtensaoComponentesDigitais($numIdTramite, $objProtocolo);
        $this->verificarPermissoesDiretorios($numIdTramite);
    }

    /**
     * M�todo respons�vel por realizar o download dos componentes digitais do processo
     *
     * @param stdClass $parObjTramite
     * @param stdClass $parObjMetadadosProcedimento
     *
     * @return array
     */
    private function baixarComponentesDigitais($parObjTramite, $parObjMetadadosProcedimento)
    {
        // TODO: Migrar fun��es baixarComponenteDigital, receberComponenteDigital e receberComponenteDigitalParticionado
        // para classe ReceberComponenteDigitalRN
        $arrAnexosComponentes = array();
        $arrHashComponentesBaixados = array();
        $numIdTramite = $parObjMetadadosProcedimento->IDT;
        $objProtocolo = ProcessoEletronicoRN::obterProtocoloDosMetadados($parObjMetadadosProcedimento);
        $numParamTamMaxDocumentoMb = $this->objPenParametroRN->getParametro('PEN_TAMANHO_MAXIMO_DOCUMENTO_EXPEDIDO');

        // Lista todos os componentes digitais presente no protocolo
        // Esta verifica��o � necess�ria pois existem situa��es em que a lista de componentes
        // pendentes de recebimento informado pelo PEN n�o est� de acordo com a lista atual de arquivos
        // mantida pela aplica��o.
        //$arrHashPendentesDownload = $objTramite->componenteDigitalPendenteDeRecebimento;
        $arrHashComponentesProtocolo = $this->listarHashDosComponentesMetadado($objProtocolo);
        $numQtdComponentes = count($arrHashComponentesProtocolo);
        $this->gravarLogDebug("$numQtdComponentes componentes digitais identificados no protocolo {$objProtocolo->protocolo}", 2);

        // Percorre os componentes que precisam ser recebidos
        foreach($arrHashComponentesProtocolo as $key => $strHashComponentePendente){
            $numOrdemComponente = $key + 1;
            if(!is_null($strHashComponentePendente)) {
                //Download do componente digital � realizado, mesmo j� existindo na base de dados, devido a comportamento obrigat�rio do Barramento para mudan�a de status
                //Ajuste dever� ser feito em vers�es futuras do Barramento de Servi�os para baixar somente aqueles necess�rios, ou seja,
                //os hash descritos nos metadados do �ltimo tr�mite mas n�o presentes no processo atual (�ltimo tr�mite)
                $arrHashComponentesBaixados[] = $strHashComponentePendente;
                $nrTamanhoBytesArquivo = $this->obterTamanhoComponenteDigitalPendente($objProtocolo, $strHashComponentePendente);
                $nrTamanhoArquivoKB = round($nrTamanhoBytesArquivo / 1024, 2);
                $nrTamanhoBytesMaximo  = $numParamTamMaxDocumentoMb * pow(1024, 2);

                $arrObjComponenteDigitalIndexado = self::indexarComponenteDigitaisDoProtocolo($objProtocolo);

                //Obter os dados do componente digital particionado
                $this->gravarLogDebug("Baixando componente digital $numOrdemComponente particionado", 3);

                $objAnexoDTO = $this->receberComponenenteDigitalParticionado(
                    $strHashComponentePendente, $nrTamanhoBytesMaximo, $nrTamanhoBytesArquivo, $numParamTamMaxDocumentoMb,
                    $numOrdemComponente, $numIdTramite, $parObjTramite, $arrObjComponenteDigitalIndexado
                );
                $arrAnexosComponentes[$key][$strHashComponentePendente] = $objAnexoDTO;
                $this->criarDiretorioAnexo($objAnexoDTO);

                $objAnexoBaixadoPraPastaTemp = $arrAnexosComponentes[$key][$strHashComponentePendente];
                $objAnexoBaixadoPraPastaTemp->hash = $strHashComponentePendente;

                //Valida a integridade do componente via hash
                $this->gravarLogDebug("Validando integridade de componente digital $numOrdemComponente", 4);
                $numTempoInicialValidacao = microtime(true);
                $this->objReceberComponenteDigitalRN->validarIntegridadeDoComponenteDigital(
                    $arrAnexosComponentes[$key][$strHashComponentePendente], $strHashComponentePendente, $numIdTramite, $numOrdemComponente
                );
                $numTempoTotalValidacao = round(microtime(true) - $numTempoInicialValidacao, 2);
                $numVelocidade = round($nrTamanhoArquivoKB / max([$numTempoTotalValidacao, 1]), 2);
                $this->gravarLogDebug("Tempo total de valida��o de integridade: {$numTempoTotalValidacao}s ({$numVelocidade} kb/s)", 4);
            }

        }

        if(count($arrAnexosComponentes) > 0){
            $this->objReceberComponenteDigitalRN->setArrAnexos($arrAnexosComponentes);
        }

        return $arrHashComponentesBaixados;
    }


    /**
     * Consulta dados de tr�mite espec�ficado no Barramento de Servi�os do PEN
     *
     * @param int $parNumIdTramite
     * @return stdClass
     */
    private function consultarTramite($parNumIdTramite)
    {
        if(is_null($parNumIdTramite)){
            throw new InfraException("N�mero de identifica��o do tr�mite n�o pode ser nulo");
        }

        $objTramite = null;
        $arrObjTramite = $this->objProcessoEletronicoRN->consultarTramites($parNumIdTramite);

        if(!empty($arrObjTramite)){
            if(count($arrObjTramite) > 1){
                throw new InfraException("Identificado mais de um registro de tr�mite para o IDT $parNumIdTramite .");
            }

            $objTramite = $arrObjTramite[0];

            if(!is_array($objTramite->componenteDigitalPendenteDeRecebimento)){
                $objTramite->componenteDigitalPendenteDeRecebimento = array($objTramite->componenteDigitalPendenteDeRecebimento);
            }
        }

        return $objTramite;
    }

    /**
     * Processa o recebimento de tr�mites de processos, fazendo o devido controle de concorr�ncia
     *
     * @param int $parNumIdentificacaoTramite
     * @return void
     */
    public function receberTramitesRecusados($parNumIdentificacaoTramite)
    {
        if (empty($parNumIdentificacaoTramite)) {
            throw new InfraException('Par�metro $parNumIdentificacaoTramite n�o informado.');
        }

        //SessaoSEI::getInstance(false)->simularLogin('SEI', null, null, $this->objPenParametroRN->getParametro('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO'));
        ModPenUtilsRN::simularLoginUnidadeRecebimento();

        $this->gravarLogDebug("Solicitando dados do tr�mite " . $parNumIdentificacaoTramite, 1);
        $arrObjTramite = $this->objProcessoEletronicoRN->consultarTramites($parNumIdentificacaoTramite);
        if(!isset($arrObjTramite) || !array_key_exists(0, $arrObjTramite)){
            throw new InfraException("N�o foi encontrado no PEN o tr�mite de n�mero {$parNumIdentificacaoTramite} para realizar a ci�ncia da recusa");
        }

        $objTramite = $arrObjTramite[0];

        try{
            // Inicializa��o do recebimento do processo, abrindo nova transa��o e controle de concorr�ncia,
            // evitando processamento simult�neo de cadastramento do mesmo processo
            $arrChavesSincronizacao = array(
                "NumeroRegistro" => $objTramite->NRE,
                "IdTramite" => $objTramite->IDT,
                "IdTarefa" => ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_RECUSADO)
            );

            if($this->objProcedimentoAndamentoRN->sinalizarInicioRecebimento($arrChavesSincronizacao)){
                $this->receberTramitesRecusadosInterno($objTramite);
            }
        } catch(Exception $e) {
            $mensagemErro = InfraException::inspecionar($e);
            $this->gravarLogDebug($mensagemErro);
            LogSEI::getInstance()->gravar($mensagemErro);
            throw $e;
        }
    }

    /**
     * Processa o recebimento de um evento de recusa de tr�mite de processo com controle de transa��o e sincronia de processamentos
     *
     * @param object $parObjTramite
     * @return void
     */
    protected function receberTramitesRecusadosInternoControlado($parObjTramite)
    {
        try {
            //SessaoSEI::getInstance(false)->simularLogin('SEI', null, null, $this->objPenParametroRN->getParametro('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO'));
            ModPenUtilsRN::simularLoginUnidadeRecebimento();

            $tramite = $parObjTramite;
            $numIdTramite = $parObjTramite->IDT;
            $strNumeroRegistro = $parObjTramite->NRE;
            $numIdTarefa = ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_RECUSADO);

            // Tratamento para evitar o recebimento simult�neo de evento de recusa de processo
            if(!$this->objProcedimentoAndamentoRN->sincronizarRecebimentoProcessos($strNumeroRegistro, $numIdTramite, $numIdTarefa)){
                $this->gravarLogDebug("Evento de recusa do tr�mite $numIdTramite j� se encontra em processamento", 3);
                return false;
            }

            $objTramiteDTO = new TramiteDTO();
            $objTramiteDTO->setNumIdTramite($numIdTramite);
            $objTramiteDTO->retNumIdUnidade();

            $objTramiteBD = new TramiteBD(BancoSEI::getInstance());
            $objTramiteDTO = $objTramiteBD->consultar($objTramiteDTO);

            if(isset($objTramiteDTO)){
                SessaoSEI::getInstance(false)->simularLogin('SEI', null, null, $objTramiteDTO->getNumIdUnidade());

                //Busca os dados do procedimento
                $this->gravarLogDebug("Buscando os dados de procedimento com NRE " . $tramite->NRE, 2);
                $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
                $objProcessoEletronicoDTO->setStrNumeroRegistro($tramite->NRE);
                $objProcessoEletronicoDTO->retDblIdProcedimento();
                $objProcessoEletronicoBD = new ProcessoEletronicoBD($this->getObjInfraIBanco());
                $objProcessoEletronicoDTO = $objProcessoEletronicoBD->consultar($objProcessoEletronicoDTO);

                // Verifica se a recusa j� foi registrada para o processo
                if($this->tramiteRecusaRegistrado($objProcessoEletronicoDTO->getDblIdProcedimento())) {
                    $objTramiteAtualizado = $this->objProcessoEletronicoRN->consultarTramites($numIdTramite);
                    if(!is_null($objTramiteAtualizado) && $objTramiteAtualizado[0]->situacaoAtual == ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CIENCIA_RECUSA){
                        $this->gravarLogDebug("Evento de recusa do tr�mite $numIdTramite j� registrado", 3);
                        return false;
                    }
                }

                //Busca a �ltima atividade de tr�mite externo
                $this->gravarLogDebug("Buscando �ltima atividade de tr�mite externo do processo " . $objProcessoEletronicoDTO->getDblIdProcedimento(), 2);
                $objAtividadeDTO = new AtividadeDTO();
                $objAtividadeDTO->setDblIdProtocolo($objProcessoEletronicoDTO->getDblIdProcedimento());
                $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO));
                $objAtividadeDTO->setNumMaxRegistrosRetorno(1);
                $objAtividadeDTO->setOrdDthAbertura(InfraDTO::$TIPO_ORDENACAO_DESC);
                $objAtividadeDTO->retNumIdAtividade();
                $objAtividadeBD = new AtividadeBD($this->getObjInfraIBanco());
                $objAtividadeDTO = $objAtividadeBD->consultar($objAtividadeDTO);

                //Busca a unidade de destino
                $this->gravarLogDebug("Buscando informa��es sobre a unidade de destino", 2);
                $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
                $objAtributoAndamentoDTO->setNumIdAtividade($objAtividadeDTO->getNumIdAtividade());
                $objAtributoAndamentoDTO->setStrNome('UNIDADE_DESTINO');
                $objAtributoAndamentoDTO->retStrValor();
                $objAtributoAndamentoBD = new AtributoAndamentoBD($this->getObjInfraIBanco());
                $objAtributoAndamentoDTO = $objAtributoAndamentoBD->consultar($objAtributoAndamentoDTO);

                //Monta o DTO de receber tramite recusado
                $this->gravarLogDebug("Preparando recebimento de tr�mite " . $numIdTramite . " recusado", 2);
                $objReceberTramiteRecusadoDTO = new ReceberTramiteRecusadoDTO();
                $objReceberTramiteRecusadoDTO->setNumIdTramite($numIdTramite);
                $objReceberTramiteRecusadoDTO->setNumIdProtocolo($objProcessoEletronicoDTO->getDblIdProcedimento());
                $objReceberTramiteRecusadoDTO->setNumIdUnidadeOrigem(null);
                $objReceberTramiteRecusadoDTO->setNumIdTarefa(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_RECUSADO));
                $objReceberTramiteRecusadoDTO->setStrMotivoRecusa(utf8_decode($tramite->justificativaDaRecusa));
                $objReceberTramiteRecusadoDTO->setStrNomeUnidadeDestino($objAtributoAndamentoDTO->getStrValor());

                //Faz o tratamento do processo e do tr�mite recusado
                $this->gravarLogDebug("Atualizando dados do processo " . $objProcessoEletronicoDTO->getDblIdProcedimento() ." e do tr�mite recusado " . $numIdTramite, 1);

                //Verifica se processo est� fechado, reabrindo-o caso necess�rio
                $objAtividadeDTO = new AtividadeDTO();
                $objAtividadeDTO->setDblIdProtocolo($objReceberTramiteRecusadoDTO->getNumIdProtocolo());
                $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
                $objAtividadeDTO->setDthConclusao(null);
                $objAtividadeRN = new AtividadeRN();
                if ($objAtividadeRN->contarRN0035($objAtividadeDTO) == 0) {
                    $this->gravarLogDebug("Reabrindo automaticamente o processo", 2);
                    $objReabrirProcessoDTO = new ReabrirProcessoDTO();
                    $objReabrirProcessoDTO->setDblIdProcedimento($objReceberTramiteRecusadoDTO->getNumIdProtocolo());
                    $objReabrirProcessoDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
                    $objReabrirProcessoDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
                    $objProcedimentoRN = new ProcedimentoRN();
                    $objProcedimentoRN->reabrirRN0966($objReabrirProcessoDTO);
                }

                //Realiza o desbloqueio do processo
                $this->gravarLogDebug("Realizando o desbloqueio do processo", 2);
                $objProtocoloDTO = new ProtocoloDTO();
                $objProtocoloDTO->setDblIdProtocolo($objReceberTramiteRecusadoDTO->getNumIdProtocolo());
                $objProtocoloDTO->setStrStaEstado(ProtocoloRN::$TE_PROCEDIMENTO_BLOQUEADO);
                if($this->objProtocoloRN->contarRN0667($objProtocoloDTO) != 0) {
                    ProcessoEletronicoRN::desbloquearProcesso($objReceberTramiteRecusadoDTO->getNumIdProtocolo());
                } else {
                    $this->gravarLogDebug("Processo " . $objReceberTramiteRecusadoDTO->getNumIdProtocolo() . " j� se encontra desbloqueado!", 2);
                }

                //Adiciona um andamento para o tr�mite recusado
                $this->gravarLogDebug("Adicionando andamento para registro da recusa do tr�mite", 2);
                $arrObjAtributoAndamentoDTO = array();
                $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
                $objAtributoAndamentoDTO->setStrNome('MOTIVO');
                $objAtributoAndamentoDTO->setStrValor($objReceberTramiteRecusadoDTO->getStrMotivoRecusa());
                $objAtributoAndamentoDTO->setStrIdOrigem($objReceberTramiteRecusadoDTO->getNumIdUnidadeOrigem());
                $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

                $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
                $objAtributoAndamentoDTO->setStrNome('UNIDADE_DESTINO');
                $objAtributoAndamentoDTO->setStrValor($objReceberTramiteRecusadoDTO->getStrNomeUnidadeDestino());
                $objAtributoAndamentoDTO->setStrIdOrigem($objReceberTramiteRecusadoDTO->getNumIdUnidadeOrigem());
                $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

                $objAtividadeDTO = new AtividadeDTO();
                $objAtividadeDTO->setDblIdProtocolo($objReceberTramiteRecusadoDTO->getNumIdProtocolo());
                $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
                $objAtividadeDTO->setNumIdTarefa($objReceberTramiteRecusadoDTO->getNumIdTarefa());
                $objAtividadeDTO->setArrObjAtributoAndamentoDTO($arrObjAtributoAndamentoDTO);

                $objAtividadeRN = new AtividadeRN();
                $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);

                $this->gravarLogDebug("Atualizando protocolo sobre obten��o da ci�ncia de recusa", 2);
                $objPenProtocolo = new PenProtocoloDTO();
                $objPenProtocolo->setDblIdProtocolo($objReceberTramiteRecusadoDTO->getNumIdProtocolo());
                $objPenProtocolo->setStrSinObteveRecusa('S');
                $objPenProtocoloBD = new ProtocoloBD($this->getObjInfraIBanco());
                $objPenProtocoloBD->alterar($objPenProtocolo);
            }

            $this->gravarLogDebug("Notificando servi�os do PEN sobre ci�ncia da recusa do tr�mite " . $numIdTramite, 2);
            $this->objProcessoEletronicoRN->cienciaRecusa($numIdTramite);

        } catch (Exception $e) {
            $mensagemErro = InfraException::inspecionar($e);
            $this->gravarLogDebug($mensagemErro);
            LogSEI::getInstance()->gravar($mensagemErro);
            throw $e;
        }
    }


    protected function listarPendenciasConectado()
    {
        return $this->objProcessoEletronicoRN->listarPendencias(true);
    }

    /**
    * M�todo respons�vel por atribuir a lista de componentes digitais baixados do PEN aos seus respectivos documentos no SEI
    */
    private function atribuirComponentesDigitaisAosDocumentos(ProcedimentoDTO $parObjProcedimentoDTO, $parStrNumeroRegistro, $parNumIdentificacaoTramite,
        $parArrHashComponentes, $objProtocolo)
    {
        if(count($parArrHashComponentes) > 0){
            //Obter dados dos componetes digitais
            $this->gravarLogDebug("Iniciando o armazenamento dos componentes digitais pendentes", 2);
            $objComponenteDigitalDTO = new ComponenteDigitalDTO();
            $objComponenteDigitalDTO->setStrNumeroRegistro($parStrNumeroRegistro);
            $objComponenteDigitalDTO->setNumIdTramite($parNumIdentificacaoTramite);
            $objComponenteDigitalDTO->setStrHashConteudo($parArrHashComponentes, InfraDTO::$OPER_IN);
            $objComponenteDigitalDTO->setOrdNumOrdem(InfraDTO::$TIPO_ORDENACAO_ASC);
            $objComponenteDigitalDTO->retDblIdProcedimento();
            $objComponenteDigitalDTO->retDblIdDocumento();
            $objComponenteDigitalDTO->retNumTicketEnvioComponentes();
            $objComponenteDigitalDTO->retStrProtocoloDocumentoFormatado();
            $objComponenteDigitalDTO->retStrHashConteudo();
            $objComponenteDigitalDTO->retStrProtocolo();
            $objComponenteDigitalDTO->retStrNumeroRegistro();
            $objComponenteDigitalDTO->retNumIdTramite();
            $objComponenteDigitalDTO->retStrNome();
            $objComponenteDigitalDTO->retStrStaEstadoProtocolo();

            $objComponenteDigitalBD = new ComponenteDigitalBD($this->getObjInfraIBanco());
            $arrObjComponentesDigitaisDTO = $objComponenteDigitalBD->listar($objComponenteDigitalDTO);

            if(!empty($arrObjComponentesDigitaisDTO)){
                $arrStrNomeDocumento = $this->listarMetaDadosComponentesDigitais($objProtocolo);
                $arrCompenentesDigitaisIndexados = InfraArray::indexarArrInfraDTO($arrObjComponentesDigitaisDTO, 'IdDocumento', true);

                foreach ($arrCompenentesDigitaisIndexados as $numIdDocumento => $arrObjComponenteDigitalDTO){
                    if(!empty($arrObjComponenteDigitalDTO)){

                        foreach ($arrObjComponenteDigitalDTO as $objComponenteDigitalDTO) {
                            $dblIdProcedimento = $objComponenteDigitalDTO->getDblIdProcedimento();
                            $dblIdDocumento = $numIdDocumento;
                            $strHash = $objComponenteDigitalDTO->getStrHashConteudo();

                            //Verificar se documento j� foi recebido anteriormente para poder registrar
                            if($this->documentosPendenteRegistro($dblIdProcedimento, $dblIdDocumento, $strHash)){
                                $this->objReceberComponenteDigitalRN->atribuirComponentesDigitaisAoDocumento($numIdDocumento, $arrObjComponenteDigitalDTO);
                                $strMensagemRecebimento = sprintf('Armazenando componente do documento %s', $objComponenteDigitalDTO->getStrProtocoloDocumentoFormatado());
                                $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento($strMensagemRecebimento, 'S'));
                                $this->gravarLogDebug($strMensagemRecebimento, 3);
                            }
                        }
                    }
                }

                $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento('Todos os componentes digitais foram recebidos', 'S'));
            }else{
                $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento('Nenhum componente digital para receber', 'S'));
            }
        }
    }

    /**
    * M�todo para recuperar a lista de todos os hashs dos componentes digitais presentes no protocolo recebido
    *
    * @return Array Lista de hashs dos componentes digitais
    */
    private function listarHashDosComponentesMetadado($parObjProtocolo)
    {
        $arrHashsComponentesDigitais = array();
        $arrObjDocumento = ProcessoEletronicoRN::obterDocumentosProtocolo($parObjProtocolo);
        foreach($arrObjDocumento as $objDocumento){
            //Desconsidera os componendes digitais de documentos cancelados
            if(!isset($objDocumento->retirado) || $objDocumento->retirado == false) {
                if(!isset($objDocumento->componenteDigital)){
                    throw new InfraException("Metadados do componente digital do documento de ordem {$objDocumento->ordem} n�o informado.");
                }

                $arrObjComponentesDigitais = is_array($objDocumento->componenteDigital) ? $objDocumento->componenteDigital : array($objDocumento->componenteDigital);
                foreach ($arrObjComponentesDigitais as $objComponenteDigital) {
                    $arrHashsComponentesDigitais[] = ProcessoEletronicoRN::getHashFromMetaDados($objComponenteDigital->hash);
                }
            }
        }

        return $arrHashsComponentesDigitais;
    }


    /**
    * Retorna um array com alguns metadados, onde o indice de � o hash do arquivo
    *
    * @return array[String]
    */
    private function listarMetaDadosComponentesDigitais($parObjProtocolo)
    {
        $arrMetadadoDocumento = array();
        $objMapBD = new GenericoBD($this->getObjInfraIBanco());

        $arrObjDocumento = ProcessoEletronicoRN::obterDocumentosProtocolo($parObjProtocolo, true);
        foreach($arrObjDocumento as $objDocumento){
            $strHash = ProcessoEletronicoRN::getHashFromMetaDados($objDocumento->componenteDigital->hash);
            $objMapDTO = new PenRelTipoDocMapRecebidoDTO(true);
            $objMapDTO->setNumMaxRegistrosRetorno(1);
            $objMapDTO->setNumCodigoEspecie($objDocumento->especie->codigo);
            $objMapDTO->retStrNomeSerie();

            $objMapDTO = $objMapBD->consultar($objMapDTO);

            if(empty($objMapDTO)) {
                $strNomeDocumento = '[ref '.$objDocumento->especie->nomeNoProdutor.']';
            }
            else {
                $strNomeDocumento = $objMapDTO->getStrNomeSerie();
            }

            $arrMetadadoDocumento[$strHash] = array(
                'especieNome' => $strNomeDocumento
            );
        }

        return $arrMetadadoDocumento;
    }

    private function validarDadosProcesso(InfraException $objInfraException, $objMetadadosProcedimento)
    {

    }

    private function validarDadosDocumentos(InfraException $objInfraException, $objMetadadosProcedimento)
    {

    }

    /**
    * Valida cada componente digital, se n�o algum n�o for aceito recusa o tramite
    * do procedimento para esta unidade
    */
    private function validarComponentesDigitais($parObjProtocolo, $parNumIdentificacaoTramite)
    {
        $arrObjDocumentos = ProcessoEletronicoRN::obterDocumentosProtocolo($parObjProtocolo);
        $numIdTipoDocumentoPadrao = $this->objPenRelTipoDocMapRecebidoRN->consultarTipoDocumentoPadrao();

        if(!isset($numIdTipoDocumentoPadrao)){
            foreach($arrObjDocumentos as $objDocument){

                $objPenRelTipoDocMapEnviadoDTO = new PenRelTipoDocMapRecebidoDTO();
                $objPenRelTipoDocMapEnviadoDTO->retTodos();
                $objPenRelTipoDocMapEnviadoDTO->setNumCodigoEspecie($objDocument->especie->codigo);

                $objProcessoEletronicoDB = new PenRelTipoDocMapRecebidoBD(BancoSEI::getInstance());
                $numContador = (integer)$objProcessoEletronicoDB->contar($objPenRelTipoDocMapEnviadoDTO);

                // N�o achou, ou seja, n�o esta cadastrado na tabela, ent�o n�o � aceito nesta unidade como v�lido
                if($numContador <= 0) {
                    $this->objProcessoEletronicoRN->recusarTramite($parNumIdentificacaoTramite, sprintf('Documento do tipo %s n�o est� mapeado', utf8_decode($objDocument->especie->nomeNoProdutor)), ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_ESPECIE_NAO_MAPEADA);
                    throw new InfraException(sprintf('Documento do tipo %s n�o est� mapeado. Motivo da Recusa no Barramento: %s', $objDocument->especie->nomeNoProdutor, ProcessoEletronicoRN::$MOTIVOS_RECUSA[ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_ESPECIE_NAO_MAPEADA]));
                }
            }
        }

        //N�o valida informa��es do componente digital caso o documento esteja cancelado
        foreach ($arrObjDocumentos as $objDocumento) {
            if (!isset($objDocumento->retirado) || $objDocumento->retirado === false) {
                foreach ($objDocumento->componenteDigital as $objComponenteDigital) {
                    $this->validaTamanhoComponenteDigital($objComponenteDigital);
                }
            }
        }
    }

    private function validaTamanhoComponenteDigital($objComponenteDigital)
    {
        if (is_null($objComponenteDigital->tamanhoEmBytes) || $objComponenteDigital->tamanhoEmBytes == 0){
            throw new InfraException('Tamanho de componente digital n�o informado.', null, 'RECUSA: '.ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_OUTROU);
        }
    }

    private function registrarProcesso($parStrNumeroRegistro, $parNumIdentificacaoTramite, $parObjProtocolo, $parObjMetadadosProcedimento)
    {
        // Valida��o dos dados do processo recebido
        $objInfraException = new InfraException();
        $this->validarDadosProcesso($objInfraException, $parObjProtocolo);
        $this->validarDadosDocumentos($objInfraException, $parObjProtocolo);

        // TODO: Regra de Neg�cio - Processos recebidos pelo Barramento n�o poder�o disponibilizar a op��o de reordena��o e cancelamento de documentos
        // para o usu�rio final, mesmo possuindo permiss�o para isso
        $objInfraException->lancarValidacoes();

        // Verificar se procedimento j� existia na base de dados do sistema
        list($dblIdProcedimento, ) = $this->consultarProcedimentoExistente($parStrNumeroRegistro, $parObjProtocolo->protocolo);
        $bolProcedimentoExistente = isset($dblIdProcedimento);

        if($bolProcedimentoExistente){
            $objProcedimentoDTO = $this->atualizarProcedimento($dblIdProcedimento, $parObjMetadadosProcedimento, $parObjProtocolo);
        }
        else {
            $objProcedimentoDTO = $this->gerarProcedimento($parObjMetadadosProcedimento, $parObjProtocolo);
        }

        // Chamada recursiva para registro dos processos apensados
        if(isset($parObjProtocolo->processoApensado)) {
            if(!is_array($parObjProtocolo->processoApensado)) {
                $parObjProtocolo->processoApensado = array($parObjProtocolo->processoApensado);
            }

            foreach ($parObjProtocolo->processoApensado as $objProcessoApensado) {
                $this->registrarProcesso($parStrNumeroRegistro, $parNumIdentificacaoTramite, $objProcessoApensado, $parObjMetadadosProcedimento);
            }
        }

        return array($objProcedimentoDTO, $bolProcedimentoExistente);
    }

    private function tramiteRecebimentoRegistrado($parStrNumeroRegistro, $parNumIdentificacaoTramite)
    {
        $objTramiteDTO = new TramiteDTO();
        $objTramiteDTO->setStrNumeroRegistro($parStrNumeroRegistro);
        $objTramiteDTO->setNumIdTramite($parNumIdentificacaoTramite);
        $objTramiteDTO->setStrStaTipoTramite(ProcessoEletronicoRN::$STA_TIPO_TRAMITE_RECEBIMENTO);
        $objTramiteBD = new TramiteBD($this->getObjInfraIBanco());
        return $objTramiteBD->contar($objTramiteDTO) > 0;
    }

    private function documentoJaRegistrado($parStrNumeroRegistro, $parNumIdentificacaoTramite, $parStrHashComponenteDigital)
    {
        //Verifica se componente digital j� est� registrado para o documento
        $objComponenteDigitalDTO = new ComponenteDigitalDTO();
        $objComponenteDigitalDTO->setStrNumeroRegistro($parStrNumeroRegistro);
        $objComponenteDigitalDTO->setNumIdTramite($parNumIdentificacaoTramite);
        $objComponenteDigitalDTO->setStrHashConteudo($parStrHashComponenteDigital);

        $objComponenteDigitalBD = new ComponenteDigitalBD($this->getObjInfraIBanco());
        return $objComponenteDigitalBD->contar($objComponenteDigitalDTO) > 0;
    }

    private function consultarProcedimentoExistente($parStrNumeroRegistro, $parStrProtocolo)
    {
        // Recupera a lista de Processos Eletr�nicos registrados para o NRE ou protocolo informado
        $dblIdProcedimento = null;
        $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
        $objProcessoEletronicoDTO->retDblIdProcedimento();
        $objProcessoEletronicoDTO->retStrNumeroRegistro();
        $objProcessoEletronicoDTO->retStrProtocoloProcedimentoFormatado();
        $objProcessoEletronicoDTO->setStrStaTipoProtocolo(ProcessoEletronicoRN::$STA_TIPO_PROTOCOLO_PROCESSO);

        if(!empty($parStrNumeroRegistro)){
            // Busca procedimento existente pelo seu NRE, caso ele seja informado
            // O n�mero de protocolo dever� ser utilizado apenas para valida��o
            $objProcessoEletronicoDTO->setStrNumeroRegistro($parStrNumeroRegistro);
        } else {
            // Sen�o a consulta dever� ser basear unicamente no n�mero de protocolo
            $objProcessoEletronicoDTO->setStrProtocoloProcedimentoFormatado($parStrProtocolo);
        }

        //TODO: Manter o padr�o o sistema em chamar uma classe de regra de neg�cio (RN) e n�o diretamente um classe BD
        $objProcessoEletronicoBD = new ProcessoEletronicoBD($this->getObjInfraIBanco());
        $arrObjProcessoEletronicoDTO = $objProcessoEletronicoBD->listar($objProcessoEletronicoDTO);

        if(!empty($arrObjProcessoEletronicoDTO)){
            $arrObjProcessoEletronicoDTOIndexado = InfraArray::indexarArrInfraDTO($arrObjProcessoEletronicoDTO, "IdProcedimento");

            // Nos casos em que mais de um NRE for encontrado, somente o �ltimo tr�mite dever� ser considerado
            $arrStrNumeroRegistro = InfraArray::converterArrInfraDTO($arrObjProcessoEletronicoDTO, "NumeroRegistro");

            $objTramiteDTOPesquisa = new TramiteDTO();
            $objTramiteDTOPesquisa->setStrNumeroRegistro($arrStrNumeroRegistro, InfraDTO::$OPER_IN);
            $objTramiteDTOPesquisa->setNumMaxRegistrosRetorno(1);
            $objTramiteDTOPesquisa->retNumIdProcedimento();
            $objTramiteDTOPesquisa->retStrNumeroRegistro();
            $objTramiteDTOPesquisa->setOrdNumIdTramite(InfraDTO::$TIPO_ORDENACAO_DESC);

            $objTramiteBD = new TramiteBD(BancoSEI::getInstance());
            $objTramiteDTO = $objTramiteBD->consultar($objTramiteDTOPesquisa);
            if(isset($objTramiteDTO)){
                $dblIdProcedimento = $objTramiteDTO->getNumIdProcedimento();
                $strNumeroRegistro = $objTramiteDTO->getStrNumeroRegistro();

                $strProtocoloFormatado = $arrObjProcessoEletronicoDTOIndexado[$dblIdProcedimento]->getStrProtocoloProcedimentoFormatado();
                if($strProtocoloFormatado !== $parStrProtocolo){
                    throw new InfraException(("N�mero do protocolo obtido n�o confere com o original. (protocolo SEI: $strProtocoloFormatado, protocolo PEN: $parStrProtocolo)"));
                }
            }
        }

        return array($dblIdProcedimento, $strNumeroRegistro);
    }


    private function consultarProcedimentoAnexadoExistente($parStrNumeroRegistro, $parStrProtocolo)
    {
        $objProcedimentoDTO = null;
        $objComponenteDigital = new ComponenteDigitalDTO();
        $objComponenteDigital->retDblIdProcedimentoAnexado();
        $objComponenteDigital->setStrNumeroRegistro($parStrNumeroRegistro);
        $objComponenteDigital->setStrProtocoloProcedimentoAnexado($parStrProtocolo);
        $objComponenteDigital->setNumMaxRegistrosRetorno(1);

        $objComponenteDigitalBD = new ComponenteDigitalBD($this->getObjInfraIBanco());
        $objComponenteDigitalDTO = $objComponenteDigitalBD->consultar($objComponenteDigital);

        if(isset($objComponenteDigitalDTO)){
            $dblIdProcedimentoAnexado = $objComponenteDigitalDTO->getDblIdProcedimentoAnexado();

            $objProcedimentoDTO = new ProcedimentoDTO();
            $objProcedimentoDTO->setDblIdProcedimento($dblIdProcedimentoAnexado);
            $objProcedimentoDTO->setStrSinDocTodos('S');
            $objProcedimentoDTO->setStrSinProcAnexados('S');
            $arrObjProcedimentoPrincipalDTO = $this->objProcedimentoRN->listarCompleto($objProcedimentoDTO);
            $objProcedimentoDTO = $arrObjProcedimentoPrincipalDTO[0];
        }

        return $objProcedimentoDTO;
    }


    private function atualizarProcedimento($parDblIdProcedimento, $objMetadadosProcedimento, $parObjProtocolo, $parNumeroRegistroAnterior=null)
    {
        if(!isset($parDblIdProcedimento)){
            throw new InfraException('Par�metro $parDblIdProcedimento n�o informado.');
        }

        if(!isset($objMetadadosProcedimento)){
            throw new InfraException('Par�metro $objMetadadosProcedimento n�o informado.');
        }

        if ($this->destinatarioReal) {
            $objDestinatario = $this->destinatarioReal;
        } else {
            $objDestinatario = $objMetadadosProcedimento->metadados->destinatario;
        }

        //Busca a unidade em ao qual o processo foi anteriormente expedido
        //Esta unidade dever� ser considerada para posterior desbloqueio do processo e reabertura
        $numIdUnidade = ProcessoEletronicoRN::obterUnidadeParaRegistroDocumento($parDblIdProcedimento);
        SessaoSEI::getInstance()->setNumIdUnidadeAtual($numIdUnidade);

        try {
            $objAtividadeDTO = new AtividadeDTO();
            $objAtividadeDTO->retDthConclusao();
            $objAtividadeDTO->setDblIdProtocolo($parDblIdProcedimento);
            $objAtividadeDTO->setNumIdUnidade($numIdUnidade);

            $objAtividadeRN = new AtividadeRN();
            $arrObjAtividadeDTO = $objAtividadeRN->listarRN0036($objAtividadeDTO);
            $flgReabrir = true;

            foreach ($arrObjAtividadeDTO as $objAtividadeDTO) {
                if ($objAtividadeDTO->getDthConclusao() == null) {
                    $flgReabrir = false;
                }
            }

            $objProcedimentoDTO = new ProcedimentoDTO();
            $objProcedimentoDTO->setDblIdProcedimento($parDblIdProcedimento);
            $objProcedimentoDTO->retTodos();
            $objProcedimentoDTO->retStrProtocoloProcedimentoFormatado();

            $objProcedimentoRN = new ProcedimentoRN();
            $objProcedimentoDTO = $objProcedimentoRN->consultarRN0201($objProcedimentoDTO);

            if($flgReabrir){
                $objEntradaReabrirProcessoAPI = new EntradaReabrirProcessoAPI();
                $objEntradaReabrirProcessoAPI->setIdProcedimento($parDblIdProcedimento);
                $this->objSeiRN->reabrirProcesso($objEntradaReabrirProcessoAPI);
            }

            try{
                ProcessoEletronicoRN::desbloquearProcesso($parDblIdProcedimento);
            } catch (Exception $e){
                $this->gravarLogDebug("Processo $parDblIdProcedimento n�o pode ser desbloqueado", 2);
            }

            //$numUnidadeReceptora = $this->objPenParametroRN->getParametro('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO');
            $numUnidadeReceptora = ModPenUtilsRN::obterUnidadeRecebimento();
            $this->enviarProcedimentoUnidade($objProcedimentoDTO, $numUnidadeReceptora);

        } finally {
            //$numUnidadeReceptora = $this->objPenParametroRN->getParametro('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO');
            $numUnidadeReceptora = ModPenUtilsRN::obterUnidadeRecebimento();
            SessaoSEI::getInstance()->setNumIdUnidadeAtual($numUnidadeReceptora);
        }

        $this->registrarAndamentoRecebimentoProcesso($objProcedimentoDTO, $objMetadadosProcedimento);

        //Cadastro das atividades para quando o destinat�rio � desviado pelo receptor (!3!)
        if ($this->destinatarioReal->numeroDeIdentificacaoDaEstrutura) {
            $this->gerarAndamentoUnidadeReceptora($parDblIdProcedimento);
        }

        $objUnidadeDTO = $this->atribuirDadosUnidade($objProcedimentoDTO, $objDestinatario);

        $strNumeroRegistro = $parNumeroRegistroAnterior ?: $objMetadadosProcedimento->metadados->NRE;
        $this->atribuirDocumentos($objProcedimentoDTO, $parObjProtocolo, $objUnidadeDTO, $objMetadadosProcedimento, $strNumeroRegistro);

        $this->registrarProcedimentoNaoVisualizado($objProcedimentoDTO);

        //TODO: Registrar que o processo foi recebido com outros apensados. Necess�rio para posterior reenvio
        $this->atribuirProcessosApensados($objProcedimentoDTO, $parObjProtocolo->processoApensado, $objMetadadosProcedimento);

        //Realiza a altera��o dos metadados do processo
        $this->alterarMetadadosProcedimento($objProcedimentoDTO->getDblIdProcedimento(), $parObjProtocolo);

        $parObjProtocolo->idProcedimentoSEI = $objProcedimentoDTO->getDblIdProcedimento();

        return $objProcedimentoDTO;
    }


    private function gerarAndamentoUnidadeReceptora($parNumIdProcedimento)
    {
        $objUnidadeDTO = new PenUnidadeDTO();
        $objUnidadeDTO->setNumIdUnidadeRH($this->destinatarioReal->numeroDeIdentificacaoDaEstrutura);
        $objUnidadeDTO->setStrSinAtivo('S');
        $objUnidadeDTO->retStrDescricao(); //descricao

        $objUnidadeRN = new UnidadeRN();
        $objUnidadeDTO = $objUnidadeRN->consultarRN0125($objUnidadeDTO);

        $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTO->setStrNome('DESCRICAO');
        $objAtributoAndamentoDTO->setStrValor('Processo remetido para a unidade ' . $objUnidadeDTO->getStrDescricao());
        $objAtributoAndamentoDTO->setStrIdOrigem($this->destinatarioReal->numeroDeIdentificacaoDaEstrutura);

        $arrObjAtributoAndamentoDTO = array($objAtributoAndamentoDTO);

        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDblIdProtocolo($parNumIdProcedimento);
        $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
        $objAtividadeDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
        $objAtividadeDTO->setNumIdTarefa(TarefaRN::$TI_ATUALIZACAO_ANDAMENTO);
        $objAtividadeDTO->setArrObjAtributoAndamentoDTO($arrObjAtributoAndamentoDTO);
        $objAtividadeDTO->setDthConclusao(null);
        $objAtividadeDTO->setNumIdUsuarioConclusao(null);
        $objAtividadeDTO->setStrSinInicial('N');

        $objAtividadeRN = new AtividadeRN();
        $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);
    }

    private function gerarProcedimento($objMetadadosProcedimento, $parObjProtocolo)
    {
        if(!isset($objMetadadosProcedimento)){
            throw new InfraException('Par�metro $objMetadadosProcedimento n�o informado.');
        }

        //TODO: Usar dados do destinat�rio em outro m�todo espec�fico para envio
        // Dados do procedimento enviados pelos �rg�o externo integrado ao PEN
        $objRemetente = $objMetadadosProcedimento->metadados->remetente;
        $objDestinatario = $objMetadadosProcedimento->metadados->destinatario;

        //Atribui��o de dados do protocolo
        //TODO: Validar cada uma das informa��es de entrada do webservice
        $objProtocoloDTO = new ProtocoloDTO();
        $objProtocoloDTO->setDblIdProtocolo(null);
        $objProtocoloDTO->setStrDescricao(utf8_decode($this->objProcessoEletronicoRN->reduzirCampoTexto($parObjProtocolo->descricao, 100)));
        $objProtocoloDTO->setStrStaNivelAcessoLocal($this->obterNivelSigiloSEI($parObjProtocolo->nivelDeSigilo));

        if($this->obterNivelSigiloSEI($parObjProtocolo->nivelDeSigilo) == ProtocoloRN::$NA_RESTRITO){
            $objHipoteseLegalRecebido = new PenRelHipoteseLegalRecebidoRN();
            $numIdHipoteseLegalPadrao = $this->objPenParametroRN->getParametro('HIPOTESE_LEGAL_PADRAO');

            if (!isset($parObjProtocolo->hipoteseLegal) || (isset($parObjProtocolo->hipoteseLegal) && empty($parObjProtocolo->hipoteseLegal->identificacao))) {
                $objProtocoloDTO->setNumIdHipoteseLegal($numIdHipoteseLegalPadrao);
            } else {
                $numIdHipoteseLegal = $objHipoteseLegalRecebido->getIdHipoteseLegalSEI($parObjProtocolo->hipoteseLegal->identificacao);
                if (empty($numIdHipoteseLegal)) {
                    $objProtocoloDTO->setNumIdHipoteseLegal($numIdHipoteseLegalPadrao);
                } else {
                    $objProtocoloDTO->setNumIdHipoteseLegal($numIdHipoteseLegal);
                }
            }
        }

        // O protocolo formatado do novo processo somente dever� reutilizar o n�mero definido pelo Remetente em caso de
        // tr�mites de processos. No caso de recebimento de documentos avulsos, o n�mero do novo processo sempre dever� ser
        // gerado pelo destinat�rio, conforme regras definidas em legisla��o vigente
        $strProtocoloFormatado = ($parObjProtocolo->staTipoProtocolo == ProcessoEletronicoRN::$STA_TIPO_PROTOCOLO_PROCESSO) ? $parObjProtocolo->protocolo : null;
        $objProtocoloDTO->setStrProtocoloFormatado(utf8_decode($strProtocoloFormatado));
        $objProtocoloDTO->setDtaGeracao($this->objProcessoEletronicoRN->converterDataSEI($parObjProtocolo->dataHoraDeProducao));
        $objProtocoloDTO->setArrObjAnexoDTO(array());
        $objProtocoloDTO->setArrObjRelProtocoloAssuntoDTO(array());
        $objProtocoloDTO->setArrObjRelProtocoloProtocoloDTO(array());
        $this->atribuirParticipantes($objProtocoloDTO, $parObjProtocolo->interessado);

        $strDescricao = "";
        if(isset($parObjProtocolo->processoDeNegocio)){
            $strDescricao  = sprintf('Tipo de processo no �rg�o de origem: %s', utf8_decode($parObjProtocolo->processoDeNegocio)).PHP_EOL;
            $strDescricao .= $parObjProtocolo->observacao;
        }

        $objObservacaoDTO  = new ObservacaoDTO();

        // Cria��o da observa��o de aviso para qual � a real unidade emitida
        if ($this->destinatarioReal) {
            $objUnidadeDTO = new PenUnidadeDTO();
            $objUnidadeDTO->setNumIdUnidadeRH($this->destinatarioReal->numeroDeIdentificacaoDaEstrutura);
            $objUnidadeDTO->setStrSinAtivo('S');
            $objUnidadeDTO->retStrDescricao();

            $objUnidadeRN = new UnidadeRN();
            $objUnidadeDTO = $objUnidadeRN->consultarRN0125($objUnidadeDTO);
            $objObservacaoDTO->setStrDescricao($strDescricao . PHP_EOL .'Processo remetido para a unidade ' . $objUnidadeDTO->getStrDescricao());
        } else {
            $objObservacaoDTO->setStrDescricao($strDescricao);
        }

        $objProtocoloDTO->setArrObjObservacaoDTO(array($objObservacaoDTO));

        //Atribui��o de dados do procedimento
        $objProcedimentoDTO = new ProcedimentoDTO();
        $objProcedimentoDTO->setDblIdProcedimento(null);
        $objProcedimentoDTO->setObjProtocoloDTO($objProtocoloDTO);
        $objProcedimentoDTO->setStrNomeTipoProcedimento(utf8_decode($parObjProtocolo->processoDeNegocio));
        $objProcedimentoDTO->setDtaGeracaoProtocolo($this->objProcessoEletronicoRN->converterDataSEI($parObjProtocolo->dataHoraDeProducao));
        $objProcedimentoDTO->setStrProtocoloProcedimentoFormatado(utf8_decode($parObjProtocolo->protocolo));
        $objProcedimentoDTO->setStrSinGerarPendencia('S');
        $objProcedimentoDTO->setArrObjDocumentoDTO(array());

        $numIdTipoProcedimento = $this->objPenParametroRN->getParametro('PEN_TIPO_PROCESSO_EXTERNO');
        $this->atribuirTipoProcedimento($objProcedimentoDTO, $numIdTipoProcedimento, $parObjProtocolo->processoDeNegocio);

        // Obt�m c�digo da unidade atrav�s de mapeamento entre SEI e Barramento
        $objUnidadeDTO = $this->atribuirDadosUnidade($objProcedimentoDTO, $objDestinatario);

        //TODO: Atribuir Dados do produtor do processo
        //TODO:Adicionar demais informa��es do processo
        //<protocoloAnterior>
        //<historico>

        //TODO: Avaliar necessidade de tal recurso
        //FeedSEIProtocolos::getInstance()->setBolAcumularFeeds(true);

        //TODO: Analisar impacto do par�metro SEI_HABILITAR_NUMERO_PROCESSO_INFORMADO no recebimento do processo
        //$objWSRetornoGerarProcedimentoDTO = $this->objSeiRN->gerarProcedimento($objWSEntradaGerarProcedimentoDTO);

        // Finalizar cria��o do procedimento
        $objProcedimentoRN = new ProcedimentoRN();

        // Verifica se o protocolo � do tipo documento avulso, se for gera um novo n�mero de protocolo
        if($parObjProtocolo->staTipoProtocolo == ProcessoEletronicoRN::$STA_TIPO_PROTOCOLO_DOCUMENTO_AVULSO) {
            $strNumProtocoloDocumentoAvulso = $this->gerarNumeroProtocoloDocumentoAvulso($objUnidadeDTO, $this->objPenParametroRN);
            $objProcedimentoDTO->getObjProtocoloDTO()->setStrProtocoloFormatado($strNumProtocoloDocumentoAvulso);
        }

        $objInfraParametro = new InfraParametro($this->getObjInfraIBanco());
        $objInfraParametro->setValor('SEI_FEDERACAO_NUMERO_PROCESSO', 0);
        $objProcedimentoDTOGerado = $objProcedimentoRN->gerarRN0156($objProcedimentoDTO);

        $objProcedimentoDTO->setDblIdProcedimento($objProcedimentoDTOGerado->getDblIdProcedimento());
        $objProcedimentoDTO->setStrProtocoloProcedimentoFormatado($objProcedimentoDTO->getObjProtocoloDTO()->getStrProtocoloFormatado());

        $this->registrarAndamentoRecebimentoProcesso($objProcedimentoDTO, $objMetadadosProcedimento);

        $strNumeroRegistro = $objMetadadosProcedimento->metadados->NRE;
        $this->atribuirDocumentos($objProcedimentoDTO, $parObjProtocolo, $objUnidadeDTO, $objMetadadosProcedimento, $strNumeroRegistro);

        $this->registrarProcedimentoNaoVisualizado($objProcedimentoDTOGerado);

        //TODO: Avaliar necessidade de restringir refer�ncia circular entre processos
        //TODO: Registrar que o processo foi recebido com outros apensados. Necess�rio para posterior reenvio
        $this->atribuirProcessosApensados($objProcedimentoDTO, $parObjProtocolo->processoApensado, $objMetadadosProcedimento);

        $parObjProtocolo->idProcedimentoSEI = $objProcedimentoDTO->getDblIdProcedimento();

        return $objProcedimentoDTO;
    }

    /**
    * Gera o n�mero de protocolo para Documento avulso
    * @param $parObjUnidadeDTO
    * @param $parObjPenParametroRN
    * @return mixed
    * @throws InfraException
    */
    private function gerarNumeroProtocoloDocumentoAvulso($parObjUnidadeDTO, $parObjPenParametroRN)
    {
        try{
            // Alterado contexto de unidade atual para a unidade de destino do processo para que o n�cleo do SEI possa
            // gerar o n�mero de processo correto do destino e n�o o n�mero da unidade de recebimento do processo
            SessaoSEI::getInstance(false)->setNumIdUnidadeAtual($parObjUnidadeDTO->getNumIdUnidade());
            $strNumeroProcesso = $this->objProtocoloRN->gerarNumeracaoProcesso();
        }
        finally{
            ModPenUtilsRN::simularLoginUnidadeRecebimento();
        }

        return $strNumeroProcesso;
    }

    private function alterarMetadadosProcedimento($parNumIdProcedimento, $parObjMetadadoProcedimento)
    {
        //Realiza a altera��o dos metadados do processo(Por hora, apenas do n�vel de sigilo e hip�tese legal)
        $objProtocoloDTO = new ProtocoloDTO();
        $objProtocoloDTO->setDblIdProtocolo($parNumIdProcedimento);
        $objProtocoloDTO->setStrStaNivelAcessoLocal($this->obterNivelSigiloSEI($parObjMetadadoProcedimento->nivelDeSigilo));

        if($parObjMetadadoProcedimento->hipoteseLegal && $parObjMetadadoProcedimento->hipoteseLegal->identificacao){
            $objProtocoloDTO->setNumIdHipoteseLegal($this->obterHipoteseLegalSEI($parObjMetadadoProcedimento->hipoteseLegal->identificacao));


            if($this->obterNivelSigiloSEI($parObjMetadadoProcedimento->hipoteseLegal->identificacao) == ProtocoloRN::$NA_RESTRITO){
                $objHipoteseLegalRecebido = new PenRelHipoteseLegalRecebidoRN();
                $numIdHipoteseLegalPadrao = $this->objPenParametroRN->getParametro('HIPOTESE_LEGAL_PADRAO');

                if (!isset($parObjProtocolo->hipoteseLegal) || (isset($parObjProtocolo->hipoteseLegal) && empty($parObjProtocolo->hipoteseLegal->identificacao))) {
                    $objProtocoloDTO->setNumIdHipoteseLegal($numIdHipoteseLegalPadrao);
                } else {

                    $numIdHipoteseLegal = $objHipoteseLegalRecebido->getIdHipoteseLegalSEI($parObjProtocolo->hipoteseLegal->identificacao);
                    if (empty($numIdHipoteseLegal)) {
                        $objProtocoloDTO->setNumIdHipoteseLegal($numIdHipoteseLegalPadrao);
                    } else {
                        $objProtocoloDTO->setNumIdHipoteseLegal($numIdHipoteseLegal);
                    }
                }
            }
        }

        $this->objProtocoloRN->alterarRN0203($objProtocoloDTO);
    }

    private function alterarMetadadosDocumento($parNumIdProcedimento, $parNumIdDocumento, $parObjMetadadoDocumento)
    {
        //Realiza a altera��o dos metadados do documento(Por hora, apenas do n�vel de sigilo e hip�tese legal)
        $objProtocoloDTO = new ProtocoloDTO();
        $objProtocoloDTO->setDblIdProtocolo($parNumIdDocumento);
        $objProtocoloDTO->setStrStaNivelAcessoLocal($this->obterNivelSigiloSEI($parObjMetadadoDocumento->nivelDeSigilo));

        if($parObjMetadadoDocumento->hipoteseLegal && $parObjMetadadoDocumento->hipoteseLegal->identificacao){
            $objProtocoloDTO->setNumIdHipoteseLegal($this->obterHipoteseLegalSEI($parObjMetadadoDocumento->hipoteseLegal->identificacao));
        }

        $this->objProtocoloRN->alterarRN0203($objProtocoloDTO);
    }


    private function removerAndamentosProcedimento($parObjProtocoloDTO)
    {
        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->retNumIdAtividade();
        $objAtividadeDTO->setDblIdProtocolo($parObjProtocoloDTO->getDblIdProcedimento());
        $objAtividadeDTO->setNumIdTarefa(TarefaRN::$TI_GERACAO_PROCEDIMENTO);

        $objAtividadeRN = new AtividadeRN();
        $objAtividadeRN->excluirRN0034($objAtividadeRN->listarRN0036($objAtividadeDTO));
    }

    private function registrarAndamentoRecebimentoProcesso(ProcedimentoDTO $objProcedimentoDTO, $parObjMetadadosProcedimento)
    {
        //Processo recebido da entidade @ENTIDADE_ORIGEM@ - @REPOSITORIO_ORIGEM@
        $objRemetente = $parObjMetadadosProcedimento->metadados->remetente;
        $objProcesso = $parObjMetadadosProcedimento->metadados->processo;
        $objProtocolo = ProcessoEletronicoRN::obterProtocoloDosMetadados($parObjMetadadosProcedimento);

        $arrObjAtributoAndamentoDTO = array();

        //TODO: Otimizar c�digo. Pesquisar 1 �nico elemento no barramento de servi�os
        $objRepositorioDTO = $this->objProcessoEletronicoRN->consultarRepositoriosDeEstruturas(
            $objRemetente->identificacaoDoRepositorioDeEstruturas
        );

        //TODO: Otimizar c�digo. Apenas buscar no barramento os dados da estrutura 1 �nica vez (AtribuirRemetente tamb�m utiliza)
        $objEstrutura = $this->objProcessoEletronicoRN->consultarEstrutura(
            $objRemetente->identificacaoDoRepositorioDeEstruturas,
            $objRemetente->numeroDeIdentificacaoDaEstrutura,
            true
        );

        $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTO->setStrNome('REPOSITORIO_ORIGEM');
        $objAtributoAndamentoDTO->setStrValor($objRepositorioDTO->getStrNome());
        $objAtributoAndamentoDTO->setStrIdOrigem($objRepositorioDTO->getNumId());
        $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

        $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTO->setStrNome('ENTIDADE_ORIGEM');
        $objAtributoAndamentoDTO->setStrValor($objEstrutura->nome);
        $objAtributoAndamentoDTO->setStrIdOrigem($objEstrutura->numeroDeIdentificacaoDaEstrutura);
        $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

        $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTO->setStrNome('PROCESSO');
        $objAtributoAndamentoDTO->setStrValor($objProtocolo->protocolo);
        $objAtributoAndamentoDTO->setStrIdOrigem(null);
        $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

        $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTO->setStrNome('USUARIO');
        $objAtributoAndamentoDTO->setStrValor(SessaoSEI::getInstance()->getStrNomeUsuario());
        $objAtributoAndamentoDTO->setStrIdOrigem(SessaoSEI::getInstance()->getNumIdUsuario());
        $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

        //Obt�m dados da unidade de destino atribu�da anteriormente para o protocolo
        if($objProcedimentoDTO->isSetArrObjUnidadeDTO() && count($objProcedimentoDTO->getArrObjUnidadeDTO()) == 1) {
            $arrObjUnidadesDestinoDTO = $objProcedimentoDTO->getArrObjUnidadeDTO();
            $objUnidadesDestinoDTO = $arrObjUnidadesDestinoDTO[0];
            $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
            $objAtributoAndamentoDTO->setStrNome('UNIDADE_DESTINO');
            $objAtributoAndamentoDTO->setStrValor($objUnidadesDestinoDTO->getStrDescricao());
            $objAtributoAndamentoDTO->setStrIdOrigem($objUnidadesDestinoDTO->getNumIdUnidade());
            $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;
        }

            $dados=ProcessoEletronicoINT::formatarHierarquia($objEstrutura);
            $nome=$dados['nome'];
            $objNivel=$dados['objNivel'];

            $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
            $objAtributoAndamentoDTO->setStrNome('ENTIDADE_ORIGEM_HIRARQUIA');
            $objAtributoAndamentoDTO->setStrValor($nome);
            $objAtributoAndamentoDTO->setStrIdOrigem($objNivel->numeroDeIdentificacaoDaEstrutura);
            $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;
        

        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDblIdProtocolo($objProcedimentoDTO->getDblIdProcedimento());
        $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
        $objAtividadeDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());

        $bolEhProcesso = $objProtocolo->staTipoProtocolo == ProcessoEletronicoRN::$STA_TIPO_PROTOCOLO_PROCESSO;
        $strIdTarefa = $bolEhProcesso ? ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO : ProcessoEletronicoRN::$TI_DOCUMENTO_AVULSO_RECEBIDO;

        $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::obterIdTarefaModulo($strIdTarefa));
        $objAtividadeDTO->setArrObjAtributoAndamentoDTO($arrObjAtributoAndamentoDTO);
        $objAtividadeDTO->setDthConclusao(null);
        $objAtividadeDTO->setNumIdUsuarioConclusao(null);
        $objAtividadeDTO->setStrSinInicial('N');

        $objAtividadeRN = new AtividadeRN();
        $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);
    }


    // Avaliar a necessidade de registrar os dados do remetente como participante do processo
    private function atribuirRemetente(ProtocoloDTO $objProtocoloDTO, $objRemetente)
    {
        $arrObjParticipantesDTO = array();
        if($objProtocoloDTO->isSetArrObjParticipanteDTO()) {
            $arrObjParticipantesDTO = $objProtocoloDTO->getArrObjParticipanteDTO();
        }

        //Obten��o de detalhes do remetente na infraestrutura do PEN
        $objEstruturaDTO = $this->objProcessoEletronicoRN->consultarEstrutura(
            $objRemetente->identificacaoDoRepositorioDeEstruturas,
            $objRemetente->numeroDeIdentificacaoDaEstrutura
        );

        if(!empty($objEstruturaDTO)) {
            $objParticipanteDTO  = new ParticipanteDTO();
            $objParticipanteDTO->setStrSiglaContato($objEstruturaDTO->getStrSigla());
            $objParticipanteDTO->setStrNomeContato($objEstruturaDTO->getStrNome());
            $objParticipanteDTO->setStrStaParticipacao(ParticipanteRN::$TP_REMETENTE);
            $objParticipanteDTO->setNumSequencia(0);
            $arrObjParticipantesDTO[] = $objParticipanteDTO;
            $arrObjParticipantesDTO = $this->prepararParticipantes($arrObjParticipantesDTO);
        }

        $objProtocoloDTO->setArrObjParticipanteDTO($arrObjParticipantesDTO);
    }


    private function atribuirParticipantes(ProtocoloDTO $objProtocoloDTO, $arrObjInteressados)
    {
        $arrObjParticipantesDTO = array();
        if($objProtocoloDTO->isSetArrObjParticipanteDTO()) {
            $arrObjParticipantesDTO = $objProtocoloDTO->getArrObjParticipanteDTO();
        }

        if (!is_array($arrObjInteressados)) {
            $arrObjInteressados = array($arrObjInteressados);
        }

        for($i=0; $i < count($arrObjInteressados); $i++){
            $objInteressado = $arrObjInteressados[$i];
            $objParticipanteDTO  = new ParticipanteDTO();
            $objParticipanteDTO->setStrSiglaContato($objInteressado->numeroDeIdentificacao);
            $objParticipanteDTO->setStrNomeContato(utf8_decode($objInteressado->nome));
            $objParticipanteDTO->setStrStaParticipacao(ParticipanteRN::$TP_INTERESSADO);
            $objParticipanteDTO->setNumSequencia($i);
            $arrObjParticipantesDTO[] = $objParticipanteDTO;
        }

        $arrObjParticipanteDTO = $this->prepararParticipantes($arrObjParticipantesDTO);
        $objProtocoloDTO->setArrObjParticipanteDTO($arrObjParticipanteDTO);
    }

    private function atribuirTipoProcedimento(ProcedimentoDTO $objProcedimentoDTO, $numIdTipoProcedimento)
    {
        if(!isset($numIdTipoProcedimento)){
            throw new InfraException('Par�metro $numIdTipoProcedimento n�o informado.');
        }

        $objTipoProcedimentoDTO = new TipoProcedimentoDTO();
        $objTipoProcedimentoDTO->retNumIdTipoProcedimento();
        $objTipoProcedimentoDTO->retStrNome();
        $objTipoProcedimentoDTO->setNumIdTipoProcedimento($numIdTipoProcedimento);

        $objTipoProcedimentoRN = new TipoProcedimentoRN();
        $objTipoProcedimentoDTO = $objTipoProcedimentoRN->consultarRN0267($objTipoProcedimentoDTO);

        if ($objTipoProcedimentoDTO==null){
            throw new InfraException('Tipo de processo n�o encontrado.');
        }

        $objProcedimentoDTO->setNumIdTipoProcedimento($objTipoProcedimentoDTO->getNumIdTipoProcedimento());
        $objProcedimentoDTO->setStrNomeTipoProcedimento($objTipoProcedimentoDTO->getStrNome());

        //Busca e adiciona os assuntos sugeridos para o tipo informado
        $objRelTipoProcedimentoAssuntoDTO = new RelTipoProcedimentoAssuntoDTO();
        $objRelTipoProcedimentoAssuntoDTO->retNumIdAssunto();
        $objRelTipoProcedimentoAssuntoDTO->retNumSequencia();
        $objRelTipoProcedimentoAssuntoDTO->setNumIdTipoProcedimento($objProcedimentoDTO->getNumIdTipoProcedimento());

        $objRelTipoProcedimentoAssuntoRN = new RelTipoProcedimentoAssuntoRN();
        $arrObjRelTipoProcedimentoAssuntoDTO = $objRelTipoProcedimentoAssuntoRN->listarRN0192($objRelTipoProcedimentoAssuntoDTO);
        $arrObjAssuntoDTO = $objProcedimentoDTO->getObjProtocoloDTO()->getArrObjRelProtocoloAssuntoDTO();

        foreach($arrObjRelTipoProcedimentoAssuntoDTO as $objRelTipoProcedimentoAssuntoDTO){
            $objRelProtocoloAssuntoDTO = new RelProtocoloAssuntoDTO();
            $objRelProtocoloAssuntoDTO->setNumIdAssunto($objRelTipoProcedimentoAssuntoDTO->getNumIdAssunto());
            $objRelProtocoloAssuntoDTO->setNumSequencia($objRelTipoProcedimentoAssuntoDTO->getNumSequencia());
            $arrObjAssuntoDTO[] = $objRelProtocoloAssuntoDTO;
        }

        $objProcedimentoDTO->getObjProtocoloDTO()->setArrObjRelProtocoloAssuntoDTO($arrObjAssuntoDTO);
    }

    private function atribuirDadosUnidade(ProcedimentoDTO $objProcedimentoDTO, $objDestinatario)
    {

        if(!isset($objDestinatario)){
            throw new InfraException('Par�metro $objDestinatario n�o informado.');
        }

        $objUnidadeDTOEnvio = $this->obterUnidadeMapeada($objDestinatario->numeroDeIdentificacaoDaEstrutura);

        if(!isset($objUnidadeDTOEnvio)){
            throw new InfraException('Unidade de destino n�o pode ser encontrada. Reposit�rio: ' . $objDestinatario->identificacaoDoRepositorioDeEstruturas .
            ', N�mero: ' . $objDestinatario->numeroDeIdentificacaoDaEstrutura);
        }

        $arrObjUnidadeDTO = array();
        $arrObjUnidadeDTO[] = $objUnidadeDTOEnvio;
        $objProcedimentoDTO->setArrObjUnidadeDTO($arrObjUnidadeDTO);

        return $objUnidadeDTOEnvio;
    }

    private function atribuirDocumentos($parObjProcedimentoDTO, $parObjProtocolo, $objUnidadeDTO, $parObjMetadadosProcedimento, $parStrNumeroRegistro, $parDblIdProcedimentoAnexado=null)
    {
        if(!isset($parObjProtocolo)) {
            throw new InfraException('Par�metro [parObjProtocolo] n�o informado.');
        }

        if(!isset($objUnidadeDTO)) {
            throw new InfraException('Unidade respons�vel pelo documento n�o informada.');
        }

        $arrObjDocumentos = ProcessoEletronicoRN::obterDocumentosProtocolo($parObjProtocolo);
        if(!isset($arrObjDocumentos) || count($arrObjDocumentos) == 0) {
            throw new InfraException('Lista de documentos do processo n�o informada.');
        }

        $strNumeroRegistro = $parStrNumeroRegistro;
        $bolDocumentoAvulso = $parObjProtocolo->staTipoProtocolo == ProcessoEletronicoRN::$STA_TIPO_PROTOCOLO_DOCUMENTO_AVULSO;
        $objProcessoPrincipal = !$bolDocumentoAvulso ? ProcessoEletronicoRN::obterProtocoloDosMetadados($parObjMetadadosProcedimento) : null;
        $bolEhProcedimentoAnexado = !$bolDocumentoAvulso && $objProcessoPrincipal->protocolo !== $parObjProcedimentoDTO->getStrProtocoloProcedimentoFormatado();
        $bolEhProcedimentoAnexadoAnteriormente = $bolEhProcedimentoAnexado && isset($parDblIdProcedimentoAnexado);

        //Obter dados dos documentos j� registrados no sistema
        $objComponenteDigitalDTO = new ComponenteDigitalDTO();
        $objComponenteDigitalDTO->retNumOrdem();
        $objComponenteDigitalDTO->retDblIdProcedimento();
        $objComponenteDigitalDTO->retDblIdDocumento();
        $objComponenteDigitalDTO->retStrHashConteudo();
        $objComponenteDigitalDTO->retNumOrdemDocumento();
        $objComponenteDigitalDTO->retNumOrdemDocumentoAnexado();
        $objComponenteDigitalDTO->retDblIdProcedimentoAnexado();
        $objComponenteDigitalDTO->retStrProtocoloProcedimentoAnexado();

        $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
        $objProcessoEletronicoDTO->setStrNumeroRegistro($parStrNumeroRegistro);
        $objUltimoTramiteDTO = $this->objProcessoEletronicoRN->consultarUltimoTramite($objProcessoEletronicoDTO);
        if(!is_null($objUltimoTramiteDTO)){
            $objComponenteDigitalDTO->setNumIdTramite($objUltimoTramiteDTO->getNumIdTramite());
        }

        if(!isset($parDblIdProcedimentoAnexado)){
            $objComponenteDigitalDTO->setDblIdProcedimento($parObjProcedimentoDTO->getDblIdProcedimento());
            $objComponenteDigitalDTO->setOrdNumOrdemDocumento(InfraDTO::$TIPO_ORDENACAO_ASC);
            $objComponenteDigitalDTO->setDblIdProcedimentoAnexado(null);
        } else {
            // Avalia��o de componentes digitais espec�ficos para o processo anexado
            $objComponenteDigitalDTO->setStrNumeroRegistro($strNumeroRegistro);
            $objComponenteDigitalDTO->setDblIdProcedimentoAnexado($parDblIdProcedimentoAnexado);
            $objComponenteDigitalDTO->setOrdNumOrdemDocumentoAnexado(InfraDTO::$TIPO_ORDENACAO_ASC);
        }

        $strCampoOrdenacao = "OrdemDocumento";
        $objComponenteDigitalBD = new ComponenteDigitalBD($this->getObjInfraIBanco());
        $arrObjComponenteDigitalDTO = $objComponenteDigitalBD->listar($objComponenteDigitalDTO);
        $arrObjComponenteDigitalDTOIndexado = InfraArray::indexarArrInfraDTO($arrObjComponenteDigitalDTO, $strCampoOrdenacao);

        $arrObjDocumentoDTO = array();
        $arrIdDocumentosRetirados = array();
        $count = count($arrObjDocumentos);
        $this->gravarLogDebug("Quantidade de documentos para recebimento: $count", 2);
        foreach($arrObjDocumentos as $objDocumento){
            if(!isset($objDocumento->staTipoProtocolo) || $bolDocumentoAvulso) {

                // Defini��o da ordem do documento para avalia��o do posicionamento
                $numOrdemDocumento = ($bolEhProcedimentoAnexado && !$bolEhProcedimentoAnexadoAnteriormente) ? $objDocumento->ordemAjustada : $objDocumento->ordem;
                $numOrdemDocumento = $numOrdemDocumento ?: $objDocumento->ordem;

                if(array_key_exists($numOrdemDocumento, $arrObjComponenteDigitalDTOIndexado)){
                    $objComponenteDigitalDTO = $arrObjComponenteDigitalDTOIndexado[$numOrdemDocumento];
                    $this->alterarMetadadosDocumento($objComponenteDigitalDTO->getDblIdProcedimento(), $objComponenteDigitalDTO->getDblIdDocumento(), $objDocumento);
                    $objDocumento->idDocumentoSEI = $objComponenteDigitalDTO->getDblIdDocumento();
                    $objDocumento->idProcedimentoSEI = $objComponenteDigitalDTO->getDblIdProcedimento();
                    $objDocumento->idProcedimentoAnexadoSEI = $objComponenteDigitalDTO->getDblIdProcedimentoAnexado();
                    $objDocumento->protocoloProcedimentoSEI = $objComponenteDigitalDTO->getStrProtocoloProcedimentoAnexado();

                    if(isset($objDocumento->retirado) && $objDocumento->retirado === true) {
                        $arrIdDocumentosRetirados[] = $objDocumento->idDocumentoSEI;
                    }

                    continue;
                }

                //Valida��o dos dados dos documentos
                if(!isset($objDocumento->especie)){
                    throw new InfraException('Esp�cie do documento ['.$objDocumento->descricao.'] n�o informada.');
                }

                $objDocumentoDTO = new DocumentoDTO();
                $objDocumentoDTO->setDblIdDocumento(null);
                $objDocumentoDTO->setDblIdProcedimento($parObjProcedimentoDTO->getDblIdProcedimento());

                $objSerieDTO = $this->obterSerieMapeada($objDocumento);

                if ($objSerieDTO==null){
                    throw new InfraException('Tipo de documento [Esp�cie '.$objDocumento->especie->codigo.'] n�o encontrado.');
                }

                if (InfraString::isBolVazia($objDocumento->dataHoraDeProducao)) {
                    throw new InfraException('Data do documento n�o informada.');
                }

                $objProcedimentoDTO2 = new ProcedimentoDTO();
                $objProcedimentoDTO2->retDblIdProcedimento();
                $objProcedimentoDTO2->retNumIdUsuarioGeradorProtocolo();
                $objProcedimentoDTO2->retNumIdTipoProcedimento();
                $objProcedimentoDTO2->retStrStaNivelAcessoGlobalProtocolo();
                $objProcedimentoDTO2->retStrProtocoloProcedimentoFormatado();
                $objProcedimentoDTO2->retNumIdTipoProcedimento();
                $objProcedimentoDTO2->retStrNomeTipoProcedimento();
                $objProcedimentoDTO2->adicionarCriterio(
                    array('IdProcedimento','ProtocoloProcedimentoFormatado','ProtocoloProcedimentoFormatadoPesquisa'),
                    array(InfraDTO::$OPER_IGUAL,InfraDTO::$OPER_IGUAL,InfraDTO::$OPER_IGUAL),
                    array($objDocumentoDTO->getDblIdProcedimento(), $objDocumentoDTO->getDblIdProcedimento(), $objDocumentoDTO->getDblIdProcedimento()),
                    array(InfraDTO::$OPER_LOGICO_OR,InfraDTO::$OPER_LOGICO_OR)
                );

                $objProcedimentoRN = new ProcedimentoRN();
                $objProcedimentoDTO2 = $objProcedimentoRN->consultarRN0201($objProcedimentoDTO2);

                if ($objProcedimentoDTO2==null){
                    throw new InfraException('Processo ['.$objDocumentoDTO->getDblIdProcedimento().'] n�o encontrado.');
                }

                $objDocumentoDTO->setDblIdProcedimento($objProcedimentoDTO2->getDblIdProcedimento());
                $objDocumentoDTO->setNumIdSerie($objSerieDTO->getNumIdSerie());
                $objDocumentoDTO->setStrNomeSerie($objSerieDTO->getStrNome());

                $objDocumentoDTO->setDblIdDocumentoEdoc(null);
                $objDocumentoDTO->setDblIdDocumentoEdocBase(null);
                //$objDocumentoDTO->setNumIdUnidadeResponsavel($objUnidadeDTO->getNumIdUnidade());
                $objDocumentoDTO->setNumIdUnidadeResponsavel(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
                $objDocumentoDTO->setNumIdTipoConferencia(null);
                $objDocumentoDTO->setStrConteudo(null);
                $objDocumentoDTO->setStrStaDocumento(DocumentoRN::$TD_EXTERNO);

                $objProtocoloDTO = new ProtocoloDTO();
                $objDocumentoDTO->setObjProtocoloDTO($objProtocoloDTO);
                $objProtocoloDTO->setDblIdProtocolo(null);
                $objProtocoloDTO->setStrStaProtocolo(ProtocoloRN::$TP_DOCUMENTO_RECEBIDO);

                if($objDocumento->descricao != '***'){
                    $objProtocoloDTO->setStrDescricao(utf8_decode($this->objProcessoEletronicoRN->reduzirCampoTexto($objDocumento->descricao, 100)));
                    $objDocumentoDTO->setStrNumero(utf8_decode($this->objProcessoEletronicoRN->reduzirCampoTexto($objDocumento->descricao, 50)));
                }else{
                    $objProtocoloDTO->setStrDescricao("");
                    $objDocumentoDTO->setStrNumero("");
                }

                //TODO: Avaliar regra de forma��o do n�mero do documento
                $objProtocoloDTO->setStrStaNivelAcessoLocal($this->obterNivelSigiloSEI($objDocumento->nivelDeSigilo));
                $objProtocoloDTO->setDtaGeracao($this->objProcessoEletronicoRN->converterDataSEI($objDocumento->dataHoraDeProducao));
                $objProtocoloDTO->setArrObjAnexoDTO(array());
                $objProtocoloDTO->setArrObjRelProtocoloAssuntoDTO(array());
                $objProtocoloDTO->setArrObjRelProtocoloProtocoloDTO(array());
                $objProtocoloDTO->setArrObjParticipanteDTO(array());

                //TODO: Analisar se o modelo de dados do PEN possui destinat�rios espec�ficos para os documentos
                //caso n�o possua, analisar o repasse de tais informa��es via par�metros adicionais
                $arrObservacoes = $this->adicionarObservacoesSobreNumeroDocumento($objDocumento);
                $objProtocoloDTO->setArrObjObservacaoDTO($arrObservacoes);

                $bolReabriuAutomaticamente = false;
                if ($objProcedimentoDTO2->getStrStaNivelAcessoGlobalProtocolo()==ProtocoloRN::$NA_PUBLICO || $objProcedimentoDTO2->getStrStaNivelAcessoGlobalProtocolo()==ProtocoloRN::$NA_RESTRITO) {
                    $objAtividadeDTO = new AtividadeDTO();
                    $objAtividadeDTO->setDblIdProtocolo($objDocumentoDTO->getDblIdProcedimento());
                    $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
                    $objAtividadeDTO->setDthConclusao(null);

                    // Reabertura autom�tica de processo na unidade
                    $objAtividadeRN = new AtividadeRN();
                    if ($objAtividadeRN->contarRN0035($objAtividadeDTO) == 0) {
                        $objReabrirProcessoDTO = new ReabrirProcessoDTO();
                        $objReabrirProcessoDTO->setDblIdProcedimento($objDocumentoDTO->getDblIdProcedimento());
                        $objReabrirProcessoDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
                        $objReabrirProcessoDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
                        $objProcedimentoRN->reabrirRN0966($objReabrirProcessoDTO);
                        $bolReabriuAutomaticamente = true;
                    }
                }

                $objTipoProcedimentoDTO = new TipoProcedimentoDTO();
                $objTipoProcedimentoDTO->retStrStaNivelAcessoSugestao();
                $objTipoProcedimentoDTO->retStrStaGrauSigiloSugestao();
                $objTipoProcedimentoDTO->retNumIdHipoteseLegalSugestao();
                $objTipoProcedimentoDTO->setBolExclusaoLogica(false);
                $objTipoProcedimentoDTO->setNumIdTipoProcedimento($objProcedimentoDTO2->getNumIdTipoProcedimento());

                $objTipoProcedimentoRN = new TipoProcedimentoRN();
                $objTipoProcedimentoDTO = $objTipoProcedimentoRN->consultarRN0267($objTipoProcedimentoDTO);

                if (InfraString::isBolVazia($objDocumentoDTO->getObjProtocoloDTO()->getStrStaNivelAcessoLocal())
                || $objDocumentoDTO->getObjProtocoloDTO()->getStrStaNivelAcessoLocal() == $objTipoProcedimentoDTO->getStrStaNivelAcessoSugestao()) {
                    $objDocumentoDTO->getObjProtocoloDTO()->setStrStaNivelAcessoLocal($objTipoProcedimentoDTO->getStrStaNivelAcessoSugestao());
                    $objDocumentoDTO->getObjProtocoloDTO()->setStrStaGrauSigilo($objTipoProcedimentoDTO->getStrStaGrauSigiloSugestao());
                    $objDocumentoDTO->getObjProtocoloDTO()->setNumIdHipoteseLegal($objTipoProcedimentoDTO->getNumIdHipoteseLegalSugestao());
                }

                if ($this->obterNivelSigiloSEI($objDocumento->nivelDeSigilo) == ProtocoloRN::$NA_RESTRITO) {
                    $objHipoteseLegalRecebido = new PenRelHipoteseLegalRecebidoRN();
                    $numIdHipoteseLegalPadrao = $this->objPenParametroRN->getParametro('HIPOTESE_LEGAL_PADRAO');

                    if (!isset($objDocumento->hipoteseLegal) || (isset($objDocumento->hipoteseLegal) && empty($objDocumento->hipoteseLegal->identificacao))) {
                        $objDocumentoDTO->getObjProtocoloDTO()->setNumIdHipoteseLegal($numIdHipoteseLegalPadrao);
                    } else {

                        $numIdHipoteseLegal = $objHipoteseLegalRecebido->getIdHipoteseLegalSEI($objDocumento->hipoteseLegal->identificacao);
                        if (empty($numIdHipoteseLegal)) {
                            $objDocumentoDTO->getObjProtocoloDTO()->setNumIdHipoteseLegal($numIdHipoteseLegalPadrao);
                        } else {
                            $objDocumentoDTO->getObjProtocoloDTO()->setNumIdHipoteseLegal($numIdHipoteseLegal);
                        }
                    }
                }

                $objDocumentoDTO->getObjProtocoloDTO()->setArrObjParticipanteDTO($this->prepararParticipantes($objDocumentoDTO->getObjProtocoloDTO()->getArrObjParticipanteDTO()));

                $objDocumentoRN = new DocumentoRN();
                $objDocumentoDTO->setStrConteudo(null);
                $objDocumentoDTO->getObjProtocoloDTO()->setNumIdUnidadeGeradora(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
                $objDocumentoDTO->setStrSinBloqueado('N');

                //TODO: Fazer a atribui��o dos componentes digitais do processo a partir desse ponto
                $this->atribuirComponentesDigitais($objDocumentoDTO, $objDocumento->componenteDigital);
                $objDocumentoDTOGerado = $objDocumentoRN->cadastrarRN0003($objDocumentoDTO);

                $objAtividadeDTOVisualizacao = new AtividadeDTO();
                $objAtividadeDTOVisualizacao->setDblIdProtocolo($objDocumentoDTO->getDblIdProcedimento());
                $objAtividadeDTOVisualizacao->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());

                if (!$bolReabriuAutomaticamente){
                    $objAtividadeDTOVisualizacao->setNumTipoVisualizacao(AtividadeRN::$TV_ATENCAO);
                }else{
                    $objAtividadeDTOVisualizacao->setNumTipoVisualizacao(AtividadeRN::$TV_NAO_VISUALIZADO | AtividadeRN::$TV_ATENCAO);
                }

                $objAtividadeRN = new AtividadeRN();
                $objAtividadeRN->atualizarVisualizacaoUnidade($objAtividadeDTOVisualizacao);

                $objDocumento->idDocumentoSEI = $objDocumentoDTOGerado->getDblIdDocumento();
                $objDocumento->idProcedimentoSEI = $objDocumentoDTO->getDblIdProcedimento();
                $objDocumento->protocoloProcedimentoSEI = $objProcedimentoDTO2->getStrProtocoloProcedimentoFormatado();

                if(!$bolDocumentoAvulso && $objProcessoPrincipal->protocolo != $parObjProtocolo->protocolo){
                    $objDocumento->protocoloProcedimentoSEI = $parObjProtocolo->protocolo;
                    $objDocumento->idProcedimentoAnexadoSEI = $objDocumentoDTO->getDblIdProcedimento();
                }

                if(isset($objDocumento->retirado) && $objDocumento->retirado === true) {
                    $arrIdDocumentosRetirados[] = $objDocumento->idDocumentoSEI;
                }

                $arrObjDocumentoDTO[] = $objDocumentoDTO;

            } elseif($objDocumento->staTipoProtocolo == ProcessoEletronicoRN::$STA_TIPO_PROTOCOLO_PROCESSO) {

                $objProcessoAnexado = $objDocumento;
                // Tratamento para atribui��o de processos anexados
                // 2 tratamentos ser�o necess�rios:
                // - o primeiro para identificar um processo anexado j� existente e que o retorno do processo n�o faz necess�rios
                // que o processo anexado seja criado novamente
                // - O segundo caso � identificar que dois processos independentes foram tramitados para o �rg�o B e estes
                // foram retornados como anexados

                // Verificar se procedimento j� existia no sistema como um processo anexado vinculado ao NRE atual
                $strNumeroRegistroPrincipal = $parObjMetadadosProcedimento->metadados->NRE;
                $objProcedimentoDTOAnexado = $this->consultarProcedimentoAnexadoExistente($strNumeroRegistroPrincipal, $objProcessoAnexado->protocolo);
                if(isset($objProcedimentoDTOAnexado)){
                    // Verifica se este processo j� existia como anexo do processo que est� sendo recebido, fazendo as devidas atualiza��es se necess�rio
                    $dblIdProcedimentoAnexado = $objProcedimentoDTOAnexado->getDblIdProcedimento();
                    $objProcessoAnexado->idProcedimentoSEI = $objProcedimentoDTOAnexado->getDblIdProcedimento();
                    $this->atribuirDocumentos($objProcedimentoDTOAnexado, $objProcessoAnexado, $objUnidadeDTO, $parObjMetadadosProcedimento, $strNumeroRegistroPrincipal, $dblIdProcedimentoAnexado);
                } else {
                    // Busca por um outro processo tramitado anteriormente e que agora est� sendo devolvido como anexo de outro
                    // Neste caso, o processo anterior deve ser localizado, atualizado e anexado ao principal
                    list($dblIdProcedimentoDTOExistente, $strNumeroRegistroAnterior) = $this->consultarProcedimentoExistente(null, $objProcessoAnexado->protocolo);
                    if(isset($dblIdProcedimentoDTOExistente)){
                        $this->atualizarProcedimento($dblIdProcedimentoDTOExistente, $parObjMetadadosProcedimento, $objProcessoAnexado, $strNumeroRegistroAnterior);
                    } else {
                        $this->gerarProcedimento($parObjMetadadosProcedimento, $objProcessoAnexado);
                    }
                }
            }
        }

        $this->cancelarDocumentosProcesso($parObjProcedimentoDTO->getDblIdProcedimento(), $arrIdDocumentosRetirados);

        $parObjProcedimentoDTO->setArrObjDocumentoDTO($arrObjDocumentoDTO);
        return $parObjProcedimentoDTO;
    }


    /**
     * Cancela os documentos no processo, verificando se os mesmos j<E1> tinha sido cancelados anteriormente
     *
     * @param array $parArrIdDocumentosCancelamento Lista de documentos que ser<E3>o cancelados
     * @return void
     */
    private function cancelarDocumentosProcesso($parDblIdProcedimento, $parArrIdDocumentosCancelamento)
    {
        foreach($parArrIdDocumentosCancelamento as $numIdDocumento){
            $objProtocoloDTO = new ProtocoloDTO();
            $objProtocoloDTO->setDblIdProtocolo($numIdDocumento);
            $objProtocoloDTO->retStrStaEstado();
            $objProtocoloDTO = $this->objProtocoloRN->consultarRN0186($objProtocoloDTO);

            // Verifica se documento est� atualmente associado ao processo e n�o foi movido para outro
            $objRelProtocoloProtocoloDTO = new RelProtocoloProtocoloDTO();
            $objRelProtocoloProtocoloDTO->retNumSequencia();
            $objRelProtocoloProtocoloDTO->setStrStaAssociacao(RelProtocoloProtocoloRN::$TA_DOCUMENTO_MOVIDO);
            $objRelProtocoloProtocoloDTO->setDblIdProtocolo1($parDblIdProcedimento);
            $objRelProtocoloProtocoloDTO->setDblIdProtocolo2($numIdDocumento);
            $bolDocumentoMovidoProcesso = $this->objRelProtocoloProtocoloRN->contarRN0843($objRelProtocoloProtocoloDTO) > 0;

            if(!$bolDocumentoMovidoProcesso && ($objProtocoloDTO->getStrStaEstado() != ProtocoloRN::$TE_DOCUMENTO_CANCELADO)){
                $objEntradaCancelarDocumentoAPI = new EntradaCancelarDocumentoAPI();
                $objEntradaCancelarDocumentoAPI->setIdDocumento($numIdDocumento);
                $objEntradaCancelarDocumentoAPI->setMotivo('Documento retirado do processo pelo remetente');
                $this->objSeiRN->cancelarDocumento($objEntradaCancelarDocumentoAPI);
            }
        }
    }


    //TODO: M�todo dever� poder� ser transferido para a classe respons�vel por fazer o recebimento dos componentes digitais
    private function atribuirComponentesDigitais(DocumentoDTO $parObjDocumentoDTO, $parArrObjComponentesDigitais)
    {
        if(!isset($parArrObjComponentesDigitais)) {
            throw new InfraException('Componentes digitais do documento n�o informado.');
        }

        $arrObjAnexoDTO = array();
        if($parObjDocumentoDTO->getObjProtocoloDTO()->isSetArrObjAnexoDTO()) {
            $arrObjAnexoDTO = $parObjDocumentoDTO->getObjProtocoloDTO()->getArrObjAnexoDTO();
        }

        if (!is_array($parArrObjComponentesDigitais)) {
            $parArrObjComponentesDigitais = array($parArrObjComponentesDigitais);
        }

        $parObjDocumentoDTO->getObjProtocoloDTO()->setArrObjAnexoDTO($arrObjAnexoDTO);
    }


    private function atribuirProcessosAnexados($parObjProtocolo)
    {
        $bolExisteProcessoAnexado = ProcessoEletronicoRN::existeProcessoAnexado($parObjProtocolo);
        if($parObjProtocolo->staTipoProtocolo != ProcessoEletronicoRN::$STA_TIPO_PROTOCOLO_DOCUMENTO_AVULSO && $bolExisteProcessoAnexado){
            $objRelProtocoloProtocoloRN = new RelProtocoloProtocoloRN();
            $objRelProtocoloProtocoloDTO = new RelProtocoloProtocoloDTO();
            $objRelProtocoloProtocoloDTO->setStrStaAssociacao(RelProtocoloProtocoloRN ::$TA_PROCEDIMENTO_ANEXADO);
            $objRelProtocoloProtocoloDTO->retDblIdRelProtocoloProtocolo();

            $arrOrdemProtocolos = array();
            $arrObjProtocolos = ProcessoEletronicoRN::obterDocumentosProtocolo($parObjProtocolo);
            foreach ($arrObjProtocolos as $numOrdem => $objProtocolo) {

                if($objProtocolo->staTipoProtocolo == ProcessoEletronicoRN::$STA_TIPO_PROTOCOLO_PROCESSO) {

                    // Verifica se o processo j� se encontra anexado ao principal
                    $objRelProtocoloProtocoloDTO->setDblIdProtocolo1($parObjProtocolo->idProcedimentoSEI);
                    $objRelProtocoloProtocoloDTO->setDblIdProtocolo2($objProtocolo->idProcedimentoSEI);
                    $bolProcessoJaAnexado = $objRelProtocoloProtocoloRN->contarRN0843($objRelProtocoloProtocoloDTO) > 0;

                    if(!$bolProcessoJaAnexado){
                        //Procedimento principal ser� aquele passado como par�metro
                        $objEntradaAnexarProcessoAPI = new EntradaAnexarProcessoAPI();
                        $objEntradaAnexarProcessoAPI->setIdProcedimentoPrincipal($parObjProtocolo->idProcedimentoSEI);
                        $objEntradaAnexarProcessoAPI->setProtocoloProcedimentoPrincipal($parObjProtocolo->protocolo);

                        //Procedimento anexado ser� aquele contido na lista de documentos do processo principal
                        $objEntradaAnexarProcessoAPI->setIdProcedimentoAnexado($objProtocolo->idProcedimentoSEI);
                        $objEntradaAnexarProcessoAPI->setProtocoloProcedimentoAnexado($objProtocolo->protocolo);
                        $this->objSeiRN->anexarProcesso($objEntradaAnexarProcessoAPI);
                    }
                }

                $arrOrdemProtocolos[$objProtocolo->idProtocoloSEI] = $numOrdem;
            }

            // Ap�s a anexa��o de todos os processos, ajusta a ordena��o dos mesmos
            // Busca a ordem atual dos processos anexados e documentos do processo
            $objProcedimentoDTO = new ProcedimentoDTO();
            $objProcedimentoDTO->setDblIdProcedimento($parObjProtocolo->idProcedimentoSEI);
            $objProcedimentoDTO->setStrSinDocTodos('S');
            $objProcedimentoDTO->setStrSinProcAnexados('S');
            $arrObjProcedimentoPrincipalDTO = $this->objProcedimentoRN->listarCompleto($objProcedimentoDTO);
            $objProcedimentoDTO = $arrObjProcedimentoPrincipalDTO[0];
            $arrRelProtocoloIndexadoDTO = InfraArray::indexarArrInfraDTO($objProcedimentoDTO->getArrObjRelProtocoloProtocoloDTO(), "IdProtocolo2");

            foreach ($arrOrdemProtocolos as $numIdProtocolo => $numOrdem) {
                //Atribui��o do posicionamento correto dos processos anexados
                $objRelProtocoloProtocoloDTO = new RelProtocoloProtocoloDTO();
                $numIdProtocoloProtocolo = $arrRelProtocoloIndexadoDTO[$numIdProtocolo]->getDblIdRelProtocoloProtocolo();
                $objRelProtocoloProtocoloDTO->setDblIdRelProtocoloProtocolo($numIdProtocoloProtocolo);
                $objRelProtocoloProtocoloDTO->setNumSequencia($numOrdem);
                $this->objRelProtocoloProtocoloRN->alterar($objRelProtocoloProtocoloDTO);
            }
        }
    }


    private function atribuirProcessosApensados(ProcedimentoDTO $objProtocoloDTO, $objProcedimento, $parMetadadosProcedimento)
    {
        if(isset($objProcedimento->processoApensado)) {
            if(!is_array($objProcedimento->processoApensado)){
                $objProcedimento->processoApensado = array($objProcedimento->processoApensado);
            }

            $objProcedimentoDTOApensado = null;
            foreach ($objProcedimento->processoApensado as $processoApensado) {
                $objProcedimentoDTOApensado = $this->gerarProcedimento($parMetadadosProcedimento, $processoApensado);
                $this->relacionarProcedimentos($objProtocoloDTO, $objProcedimentoDTOApensado);
                $this->registrarProcedimentoNaoVisualizado($objProcedimentoDTOApensado);
            }
        }
    }


    private function validarDadosDestinatario($parObjMetadadosProcedimento)
    {
        $objInfraException = new InfraException();

        $objDestinatario = $parObjMetadadosProcedimento->metadados->destinatario;

        if(!isset($objDestinatario)){
            throw new InfraException("Par�metro $objDestinatario n�o informado.");
        }

        $numIdRepositorioOrigem = $this->objPenParametroRN->getParametro('PEN_ID_REPOSITORIO_ORIGEM');
        if (isset($parObjMetadadosProcedimento->metadados->unidadeReceptora)) {
            $unidadeReceptora = $parObjMetadadosProcedimento->metadados->unidadeReceptora;
            $numIdRepositorioDestinoProcesso = $unidadeReceptora->identificacaoDoRepositorioDeEstruturas;
            $numeroDeIdentificacaoDaEstrutura = $unidadeReceptora->numeroDeIdentificacaoDaEstrutura;
        } else {
            $numIdRepositorioDestinoProcesso = $objDestinatario->identificacaoDoRepositorioDeEstruturas;
            $numeroDeIdentificacaoDaEstrutura = $objDestinatario->numeroDeIdentificacaoDaEstrutura;
        }

        //Valida��o do reposit�rio de destino do processo
        if($numIdRepositorioDestinoProcesso != $numIdRepositorioOrigem){
            $objInfraException->adicionarValidacao("Identifica��o do reposit�rio de origem do processo [$numIdRepositorioDestinoProcesso] n�o reconhecida.");
        }

        //Valida��o do unidade de destino do processo
        $objUnidadeDTO = new PenUnidadeDTO();
        $objUnidadeDTO->setNumIdUnidadeRH($numeroDeIdentificacaoDaEstrutura);
        $objUnidadeDTO->setStrSinAtivo('S');
        $objUnidadeDTO->retNumIdUnidade();

        $objUnidadeRN = new UnidadeRN();
        $objUnidadeDTO = $objUnidadeRN->consultarRN0125($objUnidadeDTO);

        if(!isset($objUnidadeDTO)){
            $objInfraException->adicionarValidacao("Unidade [Estrutura: {$numeroDeIdentificacaoDaEstrutura}] n�o configurada para receber processos externos no sistema de destino.");
        }

        $objInfraException->lancarValidacoes();
    }

    private function obterNivelSigiloSEI($strNivelSigiloPEN) {
        switch ($strNivelSigiloPEN) {
            case ProcessoEletronicoRN::$STA_SIGILO_PUBLICO: return ProtocoloRN::$NA_PUBLICO;
            case ProcessoEletronicoRN::$STA_SIGILO_RESTRITO: return ProtocoloRN::$NA_RESTRITO;
            case ProcessoEletronicoRN::$STA_SIGILO_SIGILOSO: return ProtocoloRN::$NA_SIGILOSO;
        }
    }

    private function obterHipoteseLegalSEI($parNumIdHipoteseLegalPEN) {
        //Atribu� a hip�tese legal
        $objHipoteseLegalRecebido = new PenRelHipoteseLegalRecebidoRN();
        $numIdHipoteseLegalPadrao = $this->objPenParametroRN->getParametro('HIPOTESE_LEGAL_PADRAO');

        $numIdHipoteseLegal = $objHipoteseLegalRecebido->getIdHipoteseLegalSEI($parNumIdHipoteseLegalPEN);

        if (empty($numIdHipoteseLegal)) {
            return $numIdHipoteseLegalPadrao;
        } else {
            return $numIdHipoteseLegal;
        }
    }

    //TODO: Implementar o mapeamento entre as unidade do SEI e Barramento de Servi�os (Secretaria de Sa�de: 218794)
    private function obterUnidadeMapeada($numIdentificacaoDaEstrutura)
    {
        $objUnidadeDTO = new PenUnidadeDTO();
        $objUnidadeDTO->setNumIdUnidadeRH($numIdentificacaoDaEstrutura);
        $objUnidadeDTO->setStrSinAtivo('S');
        $objUnidadeDTO->retNumIdUnidade();
        $objUnidadeDTO->retNumIdOrgao();
        $objUnidadeDTO->retStrSigla();
        $objUnidadeDTO->retStrDescricao();

        $objUnidadeRN = new UnidadeRN();
        return $objUnidadeRN->consultarRN0125($objUnidadeDTO);
    }


    private function obterSerieMapeada($documento)
    {
        $bolPossuiDocumentoReferenciado = isset($documento->ordemDoDocumentoReferenciado);
        $numCodigoEspecie = (!$bolPossuiDocumentoReferenciado) ? intval($documento->especie->codigo) : self::NUM_ESPECIE_PEN_ANEXO;
        return $this->objPenRelTipoDocMapRecebidoRN->obterSerieMapeada($numCodigoEspecie);
    }


    private function relacionarProcedimentos($objProcedimentoDTO1, $objProcedimentoDTO2)
    {
        if(!isset($objProcedimentoDTO1) || !isset($objProcedimentoDTO1)) {
            throw new InfraException('Par�metro $objProcedimentoDTO n�o informado.');
        }

        $objRelProtocoloProtocoloDTO = new RelProtocoloProtocoloDTO();
        $objRelProtocoloProtocoloDTO->setDblIdProtocolo1($objProcedimentoDTO2->getDblIdProcedimento());
        $objRelProtocoloProtocoloDTO->setDblIdProtocolo2($objProcedimentoDTO1->getDblIdProcedimento());
        $objRelProtocoloProtocoloDTO->setStrStaAssociacao(RelProtocoloProtocoloRN::$TA_PROCEDIMENTO_RELACIONADO);
        $objRelProtocoloProtocoloDTO->setStrMotivo(self::STR_APENSACAO_PROCEDIMENTOS);

        $objProcedimentoRN = new ProcedimentoRN();
        $objProcedimentoRN->relacionarProcedimentoRN1020($objRelProtocoloProtocoloDTO);
    }

    //TODO: M�todo identico ao localizado na classe SeiRN:2214
    //Refatorar c�digo para evitar problemas de manuten��o
    private function prepararParticipantes($arrObjParticipanteDTO)
    {
        $objContatoRN = new ContatoRN();
        $objUsuarioRN = new UsuarioRN();

        foreach($arrObjParticipanteDTO as $objParticipanteDTO) {
            $objContatoDTO = new ContatoDTO();
            $objContatoDTO->retNumIdContato();

            if (!InfraString::isBolVazia($objParticipanteDTO->getStrSiglaContato()) && !InfraString::isBolVazia($objParticipanteDTO->getStrNomeContato())) {
                $objContatoDTO->setStrSigla($objParticipanteDTO->getStrSiglaContato());
                $objContatoDTO->setStrNome($objParticipanteDTO->getStrNomeContato());

            }  else if (!InfraString::isBolVazia($objParticipanteDTO->getStrSiglaContato())) {
                $objContatoDTO->setStrSigla($objParticipanteDTO->getStrSiglaContato());

            } else if (!InfraString::isBolVazia($objParticipanteDTO->getStrNomeContato())) {
                $objContatoDTO->setStrNome($objParticipanteDTO->getStrNomeContato());
            } else {
                if ($objParticipanteDTO->getStrStaParticipacao()==ParticipanteRN::$TP_INTERESSADO) {
                    throw new InfraException('Interessado vazio ou nulo.');
                }
                else if ($objParticipanteDTO->getStrStaParticipacao()==ParticipanteRN::$TP_REMETENTE) {
                    throw new InfraException('Remetente vazio ou nulo.');
                }
                else if ($objParticipanteDTO->getStrStaParticipacao()==ParticipanteRN::$TP_DESTINATARIO) {
                    throw new InfraException('Destinat�rio vazio ou nulo.');
                }
            }

            $arrObjContatoDTO = $objContatoRN->listarRN0325($objContatoDTO);
            if (count($arrObjContatoDTO)) {
                $objContatoDTO = null;

                //preferencia para contatos que representam usuarios
                foreach($arrObjContatoDTO as $dto) {
                    $objUsuarioDTO = new UsuarioDTO();
                    $objUsuarioDTO->setBolExclusaoLogica(false);
                    $objUsuarioDTO->setNumIdContato($dto->getNumIdContato());

                    if ($objUsuarioRN->contarRN0492($objUsuarioDTO)) {
                        $objContatoDTO = $dto; break;
                    }
                }

                //nao achou contato de usuario pega o primeiro retornado
                if ($objContatoDTO==null)   {
                    $objContatoDTO = $arrObjContatoDTO[0];
                }
            } else {
                $objContatoDTO = $objContatoRN->cadastrarContextoTemporario($objContatoDTO);
            }

            $objParticipanteDTO->setNumIdContato($objContatoDTO->getNumIdContato());
        }

        return $arrObjParticipanteDTO;
    }

    private function registrarProcedimentoNaoVisualizado(ProcedimentoDTO $parObjProcedimentoDTO)
    {
        $objAtividadeDTOVisualizacao = new AtividadeDTO();
        $objAtividadeDTOVisualizacao->setDblIdProtocolo($parObjProcedimentoDTO->getDblIdProcedimento());
        $objAtividadeDTOVisualizacao->setNumTipoVisualizacao(AtividadeRN::$TV_NAO_VISUALIZADO);

        $objAtividadeRN = new AtividadeRN();
        $objAtividadeRN->atualizarVisualizacao($objAtividadeDTOVisualizacao);
    }

    private function enviarProcedimentoUnidade(ProcedimentoDTO $parObjProcedimentoDTO, $parUnidadeDestino=null, $retransmissao=false)
    {
        $objAtividadeRN = new PenAtividadeRN();
        $objInfraException = new InfraException();

        $strEnviaEmailNotificacao = 'N';
        $numIdUnidade = $parUnidadeDestino;

        //Caso a unidade de destino n�o tenha sido informada, considerar as unidades atribu�das ao processo
        if(is_null($numIdUnidade)){
            if(!$parObjProcedimentoDTO->isSetArrObjUnidadeDTO() || count($parObjProcedimentoDTO->getArrObjUnidadeDTO()) == 0) {
                $objInfraException->lancarValidacao('Unidade de destino do processo n�o informada.');
            }

            $arrObjUnidadeDTO = $parObjProcedimentoDTO->getArrObjUnidadeDTO();
            if(count($parObjProcedimentoDTO->getArrObjUnidadeDTO()) > 1) {
                $objInfraException->lancarValidacao('N�o permitido a indica��o de m�ltiplas unidades de destino para um processo recebido externamente.');
            }

            $arrObjUnidadeDTO = array_values($parObjProcedimentoDTO->getArrObjUnidadeDTO());
            $objUnidadeDTO = $arrObjUnidadeDTO[0];
            $numIdUnidade = $objUnidadeDTO->getNumIdUnidade();

            //Somente considera regra de envio de e-mail para unidades vinculadas ao processo
            $strEnviaEmailNotificacao = $this->objPenParametroRN->getParametro('PEN_ENVIA_EMAIL_NOTIFICACAO_RECEBIMENTO');
        }


        $objProcedimentoDTO = new ProcedimentoDTO();
        $objProcedimentoDTO->retDblIdProcedimento();
        $objProcedimentoDTO->retNumIdTipoProcedimento();
        $objProcedimentoDTO->retStrProtocoloProcedimentoFormatado();
        $objProcedimentoDTO->retNumIdTipoProcedimento();
        $objProcedimentoDTO->retStrNomeTipoProcedimento();
        $objProcedimentoDTO->retStrStaNivelAcessoGlobalProtocolo();
        $objProcedimentoDTO->setDblIdProcedimento($parObjProcedimentoDTO->getDblIdProcedimento());


        $objProcedimentoRN = new ProcedimentoRN();
        $objProcedimentoDTO = $objProcedimentoRN->consultarRN0201($objProcedimentoDTO);

        if ($objProcedimentoDTO == null || $objProcedimentoDTO->getStrStaNivelAcessoGlobalProtocolo()==ProtocoloRN::$NA_SIGILOSO) {
            $objInfraException->lancarValidacao('Processo ['.$parObjProcedimentoDTO->getStrProtocoloProcedimentoFormatado().'] n�o encontrado.');
        }

        if ($objProcedimentoDTO->getStrStaNivelAcessoGlobalProtocolo()==ProtocoloRN::$NA_RESTRITO) {
            $objAcessoDTO = new AcessoDTO();
            $objAcessoDTO->setDblIdProtocolo($objProcedimentoDTO->getDblIdProcedimento());
            $objAcessoDTO->setNumIdUnidade($numIdUnidade);

            $objAcessoRN = new AcessoRN();
            if ($objAcessoRN->contar($objAcessoDTO)==0) {
                //  AVALIAR $objInfraException->adicionarValidacao('Unidade ['.$objUnidadeDTO->getStrSigla().'] n�o possui acesso ao processo ['.$objProcedimentoDTO->getStrProtocoloProcedimentoFormatado().'].');
            }
        }

        $objPesquisaPendenciaDTO = new PesquisaPendenciaDTO();
        $objPesquisaPendenciaDTO->setDblIdProtocolo(array($objProcedimentoDTO->getDblIdProcedimento()));
        $objPesquisaPendenciaDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
        $objPesquisaPendenciaDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());

        if($retransmissao){
            $objAtividadeRN->setStatusPesquisa(false);
        }

        $objAtividadeDTO2 = new AtividadeDTO();
        $objAtividadeDTO2->setDblIdProtocolo($objProcedimentoDTO->getDblIdProcedimento());
        $objAtividadeDTO2->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
        $objAtividadeDTO2->setDthConclusao(null);

        if ($objAtividadeRN->contarRN0035($objAtividadeDTO2) == 0) {
            //reabertura autom�tica
            $objReabrirProcessoDTO = new ReabrirProcessoDTO();
            $objReabrirProcessoDTO->setDblIdProcedimento($objAtividadeDTO2->getDblIdProtocolo());
            $objReabrirProcessoDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
            $objReabrirProcessoDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
            $objProcedimentoRN->reabrirRN0966($objReabrirProcessoDTO);
        }

        //$objPenAtividadeRN = new PenAtividadeRN();
        $arrObjProcedimentoDTO = $objAtividadeRN->listarPendenciasRN0754($objPesquisaPendenciaDTO);

        $objInfraException->lancarValidacoes();

        $objEnviarProcessoDTO = new EnviarProcessoDTO();
        $objEnviarProcessoDTO->setArrAtividadesOrigem($arrObjProcedimentoDTO[0]->getArrObjAtividadeDTO());

        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDblIdProtocolo($objProcedimentoDTO->getDblIdProcedimento());
        $objAtividadeDTO->setNumIdUsuario(null);
        $objAtividadeDTO->setNumIdUsuarioOrigem(SessaoSEI::getInstance()->getNumIdUsuario());
        $objAtividadeDTO->setNumIdUnidade($numIdUnidade);
        $objAtividadeDTO->setNumIdUnidadeOrigem(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
        $objEnviarProcessoDTO->setArrAtividades(array($objAtividadeDTO));

        $objEnviarProcessoDTO->setStrSinManterAberto('N');
        $objEnviarProcessoDTO->setStrSinEnviarEmailNotificacao($strEnviaEmailNotificacao);
        $objEnviarProcessoDTO->setStrSinRemoverAnotacoes('S');
        $objEnviarProcessoDTO->setDtaPrazo(null);
        $objEnviarProcessoDTO->setNumDias(null);
        $objEnviarProcessoDTO->setStrSinDiasUteis('N');

        $objAtividadeRN->enviarRN0023($objEnviarProcessoDTO);
    }


    /**
     * Consulta base de dados para verificar se recusa do tr�mite j� foi processada por outra processo simult�neo
     *
     * @param int $parNumIdProtocolo Identificador do protocolo do processo
     * @return bool
     */
    private function tramiteRecusaRegistrado($parNumIdProtocolo)
    {
        $objPenProtocoloDTO = new PenProtocoloDTO();
        $objPenProtocoloDTO->retDblIdProtocolo();
        $objPenProtocoloDTO->setDblIdProtocolo($parNumIdProtocolo);
        $objPenProtocoloDTO->setStrSinObteveRecusa('S');
        $objPenProtocoloBD = new ProtocoloBD($this->getObjInfraIBanco());
        $result =$objPenProtocoloBD->contar($objPenProtocoloDTO) > 0;
        return $result;
    }


    /**
    * M�todo que realiza a valida��o da extens�o dos componentes digitais a serem recebidos
    *
    * @param integer $parIdTramite
    * @param object $parObjProtocolo
    * @throws InfraException
    */
    public function validarExtensaoComponentesDigitais($parIdTramite, $parObjProtocolo)
    {
        $arrDocumentos = ProcessoEletronicoRN::obterDocumentosProtocolo($parObjProtocolo);
        $arquivoExtensaoBD = new ArquivoExtensaoBD($this->getObjInfraIBanco());

        foreach($arrDocumentos as $objDocumento){
            if(!isset($objDocumento->retirado) || $objDocumento->retirado == false){
                $arrComponentesDigitais = $objDocumento->componenteDigital;
                if(isset($arrComponentesDigitais) && !is_array($arrComponentesDigitais)){
                    $arrComponentesDigitais = array($arrComponentesDigitais);
                }

                foreach ($arrComponentesDigitais as $componenteDigital) {
                    //Busca o nome do documento
                    $nomeDocumento = $componenteDigital->nome;

                    //Busca pela extens�o do documento
                    $arrNomeDocumento = explode('.', $nomeDocumento);
                    $extDocumento = $arrNomeDocumento[count($arrNomeDocumento) - 1];

                    //Verifica se a extens�o do arquivo est� cadastrada e ativa
                    $arquivoExtensaoDTO = new ArquivoExtensaoDTO();
                    $arquivoExtensaoDTO->setStrSinAtivo('S');
                    $arquivoExtensaoDTO->setStrExtensao($extDocumento);
                    $arquivoExtensaoDTO->retStrExtensao();

                    if($arquivoExtensaoBD->contar($arquivoExtensaoDTO) == 0){
                        $strMensagem = "Processo recusado devido a exist�ncia de documento em formato {$extDocumento} n�o permitido pelo sistema.";
                        $this->objProcessoEletronicoRN->recusarTramite($parIdTramite, $strMensagem, ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_FORMATO);
                        throw new InfraException($strMensagem);
                    }
                }
            }
        }
    }

    /**
    * M�todo que verifica as permiss�es de escrita nos diret�rios utilizados no recebimento de processos e documentos
    *
    * @param integer $parIdTramite
    * @throws InfraException
    */
    public function verificarPermissoesDiretorios($parIdTramite)
    {
        //Verifica se o usu�rio possui permiss�es de escrita no reposit�rio de arquivos externos
        if(!is_writable(ConfiguracaoSEI::getInstance()->getValor('SEI', 'RepositorioArquivos'))) {
            $this->objProcessoEletronicoRN->recusarTramite($parIdTramite, 'O sistema n�o possui permiss�o de escrita no diret�rio de armazenamento de documentos externos', ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_OUTROU);
            throw new InfraException('O sistema n�o possui permiss�o de escrita no diret�rio de armazenamento de documentos externos');
        }

        //Verifica se o usu�rio possui permiss�es de escrita no diret�rio tempor�rio de arquivos
        if(!is_writable(DIR_SEI_TEMP)){
            $this->objProcessoEletronicoRN->recusarTramite($parIdTramite, 'O sistema n�o possui permiss�o de escrita no diret�rio de armazenamento de arquivos tempor�rios do sistema.', ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_OUTROU);
            throw new InfraException('O sistema n�o possui permiss�o de escrita no diret�rio de armazenamento de arquivos tempor�rios do sistema.');

        }
    }


    /**
    * Verifica se existe documentos com pend�ncia de download de seus componentes digitais
    * @param  [type] $parNumIdProcedimento        Identificador do processo
    * @param  [type] $parNumIdDocumento           Identificador do documento
    * @param  [type] $parStrHashComponenteDigital Hash do componente digital
    * @return [type]                              Indica��o se existe pend�ncia ou n�o
    */
    private function documentosPendenteRegistro($parNumIdProcedimento, $parNumIdDocumento=null, $parStrHashComponenteDigital=null)
    {
        //Valida se algum documento ficou sem seus respectivos componentes digitais
        $sql = "select doc.id_documento as id_documento, comp.hash_conteudo as hash_conteudo
        from procedimento proced join documento doc on (doc.id_procedimento = proced.id_procedimento)
        join protocolo prot_doc on (doc.id_documento = prot_doc.id_protocolo)
        left join md_pen_componente_digital comp on (comp.id_documento = doc.id_documento)
        where comp.id_procedimento = $parNumIdProcedimento
        and prot_doc.sta_protocolo = 'R'
        and prot_doc.sta_estado <> " . ProtocoloRN::$TE_DOCUMENTO_CANCELADO . "
        and not exists (select 1 from anexo where anexo.id_protocolo = prot_doc.id_protocolo) ";

        //Adiciona filtro adicional para verificar pelo identificador do documento, caso par�metro tenha sido informado
        if(!is_null($parNumIdDocumento)){
            $sql .= " and doc.id_documento = $parNumIdDocumento";
        }

        $recordset = $this->getObjInfraIBanco()->consultarSql($sql);
        $bolDocumentoPendente = !empty($recordset);

        //Verifica especificamente um determinado hash atrav�s da verifica��o do hash do componente, caso par�metro tenha sido informado
        if($bolDocumentoPendente && !is_null($parStrHashComponenteDigital)) {
            foreach ($recordset as $item) {
                if(!is_null($item['hash_conteudo']) && $item['hash_conteudo'] === $parStrHashComponenteDigital){
                    $bolDocumentoPendente = true;
                    return $bolDocumentoPendente;
                }
            }

            $bolDocumentoPendente = false;
        }

        return $bolDocumentoPendente;
    }


    /**
    * M�todo responsav�l por obter o tamanho do componente pendente de recebimento
    * @author Josinaldo J�nior <josinaldo.junior@basis.com.br>
    * @param $parObjProtocolo
    * @param $parComponentePendente
    * @return $tamanhoComponentePendende
    */
    private function obterTamanhoComponenteDigitalPendente($parObjProtocolo, $parComponentePendente)
    {
        //Obt�m os documentos do protocolo em um array
        $arrObjDocumentos = ProcessoEletronicoRN::obterDocumentosProtocolo($parObjProtocolo);

        //Percorre os documentos e compoenntes para pegar o tamanho em bytes do componente
        foreach ($arrObjDocumentos as $objDocumento){
            $arrObjComponentesDigitais = ProcessoEletronicoRN::obterComponentesDigitaisDocumento($objDocumento);
            foreach ($arrObjComponentesDigitais as $objComponentesDigital){
                if($objComponentesDigital->hash->_ == $parComponentePendente){
                    $tamanhoComponentePendende = $objComponentesDigital->tamanhoEmBytes; break;
                }
            }
        }
        return $tamanhoComponentePendende;
    }


    /**
    * M�todo respons�vel por realizar o recebimento do componente digital particionado, de acordo com o parametro (PEN_TAMANHO_MAXIMO_DOCUMENTO_EXPEDIDO)
    * @param $componentePendente
    * @param $nrTamanhoBytesMaximo
    * @param $nrTamanhoBytesArquivo
    * @param $nrTamanhoMegasMaximo
    * @param $numComponentes
    * @param $parNumIdentificacaoTramite
    * @param $objTramite
    * @return AnexoDTO
    * @throws InfraException
    */
    private function receberComponenenteDigitalParticionado($componentePendente, $nrTamanhoBytesMaximo, $nrTamanhoBytesArquivo, $nrTamanhoMegasMaximo, $numComponente,
        $parNumIdentificacaoTramite, $objTramite, $arrObjComponenteDigitalIndexado)
    {
        $receberComponenteDigitalRN = new ReceberComponenteDigitalRN();

        $qtdPartes = ceil(($nrTamanhoBytesArquivo / pow(1024, 2)) / $nrTamanhoMegasMaximo);
        $inicio = 0;
        $fim    = $nrTamanhoBytesMaximo;

        //Realiza o recebimento do arquivo em partes
        for ($i = 1; $i <= $qtdPartes; $i++)
        {
            $objIdentificacaoDaParte = new stdClass();
            $objIdentificacaoDaParte->inicio = $inicio;
            $objIdentificacaoDaParte->fim = ($i == $qtdPartes) ? $nrTamanhoBytesArquivo : $fim;

            $numTempoInicialDownload = microtime(true);
            $objComponenteDigital = $this->objProcessoEletronicoRN->receberComponenteDigital($parNumIdentificacaoTramite, $componentePendente, $objTramite->protocolo, $objIdentificacaoDaParte);
            $numTempoTotalDownload = round(microtime(true) - $numTempoInicialDownload, 2);
            $numTamanhoArquivoKB = round(strlen($objComponenteDigital->conteudoDoComponenteDigital) / 1024, 2);
            $numVelocidade = round($numTamanhoArquivoKB / max([$numTempoTotalDownload, 1]), 2);
            $this->gravarLogDebug("Recuperado parte $i de $qtdPartes do componente digital $numComponente ($numTamanhoArquivoKB kbs). Taxa de transfer�ncia: {$numVelocidade} kb/s", 4);

            //Verifica se � a primeira execu��o do la�o, se for cria do arquivo na pasta temporaria, sen�o incrementa o conteudo no arquivo
            if($i == 1) {
                $infoAnexoRetornado = $receberComponenteDigitalRN->copiarComponenteDigitalPastaTemporaria($arrObjComponenteDigitalIndexado[$componentePendente], $objComponenteDigital);
                $objAnexoDTO = $infoAnexoRetornado;
            }else{
                //Incrementa arquivo na pasta TEMP
                $numIdAnexo = $objAnexoDTO->getNumIdAnexo();
                $strConteudoCodificado = $objComponenteDigital->conteudoDoComponenteDigital;

                $fp = fopen(DIR_SEI_TEMP.'/'.$numIdAnexo,'a');
                fwrite($fp,$strConteudoCodificado);
                fclose($fp);
            }

            $inicio = ($nrTamanhoBytesMaximo * $i);
            $fim += $nrTamanhoBytesMaximo;
        }

        //Atualiza tamanho total do anexo no banco de dados
        $objAnexoDTO->setNumTamanho($nrTamanhoBytesArquivo);

        return $objAnexoDTO;
    }


    private function indexarComponenteDigitaisDoProtocolo($parObjProtocolo)
    {
        $resultado = array();
        $arrObjDocumentos = ProcessoEletronicoRN::obterDocumentosProtocolo($parObjProtocolo);
        foreach($arrObjDocumentos as $objDocumento){
            if(isset($objDocumento->componenteDigital) && !is_array($objDocumento->componenteDigital)){
                $objDocumento->componenteDigital = array($objDocumento->componenteDigital);
            }
            foreach($objDocumento->componenteDigital as $objComponente){
                $resultado[$objComponente->hash->_] = $objComponente;
            }
        }
        return $resultado;
    }


    /**
    * Valida��o de p�s condi��es para garantir que nenhuma inconsist�ncia foi identificada no recebimento do processo
    *
    * @param  [type] $parObjMetadadosProcedimento Metadados do Protocolo
    * @param  [type] $parObjProcedimentoDTO       Dados do Processo gerado no recebimento
    */
    private function validarPosCondicoesTramite($parObjMetadadosProcedimento, $parObjProcedimentoDTO)
    {
        $strMensagemPadrao = "Inconsist�ncia identificada no recebimento de processo: \n";
        $strMensagemErro = "";

        //Valida se metadados do tr�mite e do protocolo foram identificado
        if(is_null($parObjMetadadosProcedimento)){
            $strMensagemErro = "- Metadados do tr�mite n�o identificado. \n";
        }

        //Valida se metadados do tr�mite e do protocolo foram identificado
        if(is_null($parObjProcedimentoDTO)){
            $strMensagemErro = "- Dados do processo n�o identificados \n";
        }

        //Valida se algum documento ficou sem seus respectivos componentes digitais
        if($this->documentosPendenteRegistro($parObjProcedimentoDTO->getDblIdProcedimento())){
            $strProtocoloFormatado = $parObjProcedimentoDTO->getStrProtocoloProcedimentoFormatado();
            $strMensagemErro = "- Componente digital de pelo menos um dos documentos do processo [$strProtocoloFormatado] n�o pode ser recebido. \n";
        }

       // Valida se a quantidade de documentos registrados confere com a quantidade informada nos metadados
       $arrDblIdDocumentosProcesso = $this->objProcessoEletronicoRN->listarAssociacoesDocumentos($parObjProcedimentoDTO->getDblIdProcedimento());
       $objProtocolo = ProcessoEletronicoRN::obterProtocoloDosMetadados($parObjMetadadosProcedimento);
       $arrObjDocumentosMetadados = ProcessoEletronicoRN::obterDocumentosProtocolo($objProtocolo);
       if(count($arrDblIdDocumentosProcesso) <> count($arrObjDocumentosMetadados)){
           $strProtocoloFormatado = $parObjProcedimentoDTO->getStrProtocoloProcedimentoFormatado();
           $strMensagemErro = "- N�mero de documentos do processo n�o confere com o registrado nos dados do processo no enviado externamente. \n";
       }

        if(!InfraString::isBolVazia($strMensagemErro)){
            throw new InfraException($strMensagemPadrao . $strMensagemErro);
        }
    }

    private function gravarLogDebug($parStrMensagem, $parNumIdentacao=0, $parBolLogTempoProcessamento=true)
    {
        $this->objPenDebug->gravar($parStrMensagem, $parNumIdentacao, $parBolLogTempoProcessamento);
    }

    private function listarComponentesDigitais($parStrNumeroRegistro, $parNumIdTramite)
    {
        $objComponenteDigitalDTO = new ComponenteDigitalDTO();
        $objComponenteDigitalDTO->setStrNumeroRegistro($parStrNumeroRegistro);
        $objComponenteDigitalDTO->setNumIdTramite($parNumIdTramite);
        $objComponenteDigitalDTO->retStrNumeroRegistro();
        $objComponenteDigitalDTO->retNumIdTramite();
        $objComponenteDigitalDTO->retDblIdProcedimento();
        $objComponenteDigitalDTO->retDblIdProcedimentoAnexado();
        $objComponenteDigitalDTO->retDblIdDocumento();
        $objComponenteDigitalDTO->retNumIdAnexo();
        $objComponenteDigitalDTO->retStrHashConteudo();
        $objComponenteDigitalDTO->retNumOrdem();
        $objComponenteDigitalDTO->retNumOrdemDocumento();
        $objComponenteDigitalDTO->retNumOrdemDocumentoReferenciado();
        $objComponenteDigitalDTO->retNumOrdemDocumentoAnexado();

        // Ordena obrigatoriamente pela ordem do documento (ordem documento) e depois pela ordem do componente digitial (ordem)
        $objComponenteDigitalDTO->setOrdNumOrdemDocumento(InfraDTO::$TIPO_ORDENACAO_ASC);
        $objComponenteDigitalDTO->setOrdNumOrdem(InfraDTO::$TIPO_ORDENACAO_ASC);

        $objComponenteDigitalBD = new ComponenteDigitalBD($this->getObjInfraIBanco());
        return  $objComponenteDigitalBD->listar($objComponenteDigitalDTO);
    }

    private function substituirDestinoParaUnidadeReceptora($parObjMetadadosTramite, $parNumIdentificacaoTramite)
    {
        if (isset($parObjMetadadosTramite->metadados->unidadeReceptora)) {
            $unidadeReceptora = $parObjMetadadosTramite->metadados->unidadeReceptora;
            $this->destinatarioReal = $parObjMetadadosTramite->metadados->destinatario;
            $parObjMetadadosTramite->metadados->destinatario->identificacaoDoRepositorioDeEstruturas = $unidadeReceptora->identificacaoDoRepositorioDeEstruturas;
            $parObjMetadadosTramite->metadados->destinatario->numeroDeIdentificacaoDaEstrutura = $unidadeReceptora->numeroDeIdentificacaoDaEstrutura;
            $numUnidadeReceptora = $unidadeReceptora->numeroDeIdentificacaoDaEstrutura;
            $this->gravarLogDebug("Atribuindo unidade receptora $numUnidadeReceptora para o tr�mite $parNumIdentificacaoTramite", 2);
        }
    }

    private function criarDiretorioAnexo($parObjAnexoDTO)
    {
        $objAnexoRN = new AnexoRN();
        $strDiretorio = $objAnexoRN->obterDiretorio($parObjAnexoDTO);
        if (is_dir($strDiretorio) === false){
            umask(0);
            if (mkdir($strDiretorio,0777,true) === false){
                throw new InfraException('Erro criando diret�rio "' .$strDiretorio.'".');
            }
        }
    }


    private function adicionarObservacoesSobreNumeroDocumento($parObjDocumento)
    {
        $arrObjObservacoes = array();
        $strNumeroDocumentoOrigem = isset($parObjDocumento->protocolo) ? $parObjDocumento->protocolo : $parObjDocumento->produtor->numeroDeIdentificacao;
        if(!empty($strNumeroDocumentoOrigem)){
            $objObservacaoDTO = new ObservacaoDTO();
            $objObservacaoDTO->setStrDescricao("N�mero do Documento na Origem: " . $strNumeroDocumentoOrigem);
            $arrObjObservacoes[] = $objObservacaoDTO;
        }

        return $arrObjObservacoes;
    }


    private function atribuirObservacoesSobreDocumentoReferenciado($parObjProcedimentoDTO, $parObjProtocolo)
    {
        $objProcessoEletronicoPesquisaDTO = new ProcessoEletronicoDTO();
        $objProcessoEletronicoPesquisaDTO->setDblIdProcedimento($parObjProcedimentoDTO->getDblIdProcedimento());
        $objUltimoTramiteRecebidoDTO = $this->objProcessoEletronicoRN->consultarUltimoTramiteRecebido($objProcessoEletronicoPesquisaDTO);

        if (!is_null($objUltimoTramiteRecebidoDTO)) {
            if ($this->objProcessoEletronicoRN->possuiComponentesComDocumentoReferenciado($objUltimoTramiteRecebidoDTO)) {
                $arrObjComponentesDigitaisDTO = $this->objProcessoEletronicoRN->listarComponentesDigitais($objUltimoTramiteRecebidoDTO);
                $arrObjCompIndexadoPorOrdemDTO = InfraArray::indexarArrInfraDTO($arrObjComponentesDigitaisDTO, 'OrdemDocumento');
                $arrObjCompIndexadoPorIdDocumentoDTO = InfraArray::indexarArrInfraDTO($arrObjComponentesDigitaisDTO, 'IdDocumento');

                $arrObjDocumentoDTOIndexado = array();
                foreach ($parObjProcedimentoDTO->getArrObjDocumentoDTO() as $objDocumentoDTO) {
                    $dblIdDocumento = $objDocumentoDTO->getDblIdDocumento();
                    $arrObjDocumentoDTOIndexado[$dblIdDocumento] = $objDocumentoDTO;

                    if (array_key_exists($dblIdDocumento, $arrObjCompIndexadoPorIdDocumentoDTO)) {
                        $objComponenteDTO = $arrObjCompIndexadoPorIdDocumentoDTO[$dblIdDocumento];
                        if (!is_null($objComponenteDTO->getNumOrdemDocumentoReferenciado())) {
                            $objComponenteReferenciadoDTO = $arrObjCompIndexadoPorOrdemDTO[$objComponenteDTO->getNumOrdemDocumentoReferenciado()];
                            $objDocumentoReferenciadoDTO = $arrObjDocumentoDTOIndexado[$objComponenteReferenciadoDTO->getDblIdDocumento()];

                            $strNumeNomeArvore = (!empty($objDocumentoReferenciadoDTO->getStrNumero())) ? $objDocumentoReferenciadoDTO->getStrNumero() : '';
                            $strTextoInformativo = sprintf("Anexo do %s %s (%s)",
                                $objDocumentoReferenciadoDTO->getStrNomeSerie(),
                                $strNumeNomeArvore,
                                $objDocumentoReferenciadoDTO->getObjProtocoloDTO()->getStrProtocoloFormatado()
                            );

                            $objSerieDTO = $this->objPenRelTipoDocMapRecebidoRN->obterSerieMapeada($objComponenteDTO->getNumCodigoEspecie());
                            if(!is_null($objSerieDTO)){
                                $strTextoInformativo .= " - " . $objSerieDTO->getStrNome();
                            }

                            // Busca outras observa��es da unidade para contatenar com a observa��o de doc referenciado
                            $objObservacaoPesquisaDTO = new ObservacaoDTO();
                            $objObservacaoPesquisaDTO->retStrDescricao();
                            $objObservacaoPesquisaDTO->retNumIdObservacao();
                            $objObservacaoPesquisaDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
                            $objObservacaoPesquisaDTO->setDblIdProtocolo($objDocumentoDTO->getDblIdDocumento());
                            $objObservacaoRN = new ObservacaoRN();
                            $objObservacaoDTO = $objObservacaoRN->consultarRN0221($objObservacaoPesquisaDTO);
                            if(!is_null($objObservacaoDTO) && !empty($objObservacaoDTO->getStrDescricao())){
                                $strTextoInformativo = $objObservacaoDTO->getStrDescricao() . PHP_EOL . $strTextoInformativo;
                            }

                            $objProtocoloDTOPesquisa = new ProtocoloDTO();
                            $objProtocoloDTOPesquisa->setDblIdProtocolo($dblIdDocumento);
                            $objProtocoloDTOPesquisa->retDblIdProtocolo();
                            $objProtocoloDTOPesquisa->retStrProtocoloFormatado();
                            $objProtocoloDTO = $this->objProtocoloRN->consultarRN0186($objProtocoloDTOPesquisa);
                            $objObservacaoDTO = new ObservacaoDTO();
                            $objObservacaoDTO->setStrDescricao($strTextoInformativo);
                            $objProtocoloDTO->setArrObjObservacaoDTO(array($objObservacaoDTO));
                            $this->objProtocoloRN->alterarRN0203($objProtocoloDTO);
                        }
                    }
                }
            }
        }
    }
}
