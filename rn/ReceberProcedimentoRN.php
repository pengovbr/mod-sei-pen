<?php
require_once dirname(__FILE__) . '/../../../SEI.php';

class ReceberProcedimentoRN extends InfraRN
{
    const STR_APENSACAO_PROCEDIMENTOS = 'Relacionamento representando a apensação de processos recebidos externamente';

    private $objProcessoEletronicoRN;
    private $objInfraParametro;
    private $objProcedimentoAndamentoRN;
    private $documentosRetirados = array();
    public $destinatarioReal = null;

    public function __construct()
    {
        parent::__construct();
        $this->objInfraParametro = new InfraParametro(BancoSEI::getInstance());
        $this->objProcessoEletronicoRN = new ProcessoEletronicoRN();
        $this->objProcedimentoAndamentoRN = new ProcedimentoAndamentoRN();
        $this->objReceberComponenteDigitalRN = new ReceberComponenteDigitalRN();
    }

    protected function inicializarObjInfraIBanco()
    {
        return BancoSEI::getInstance();
    }

    protected function listarPendenciasConectado()
    {
        $arrObjPendencias = $this->objProcessoEletronicoRN->listarPendencias(true);
        return $arrObjPendencias;
    }

    protected function receberProcedimentoControlado($parNumIdentificacaoTramite)
    {
        try {
            $objPenParametroRN = new PenParametroRN();
            SessaoSEI::getInstance(false)->simularLogin('SEI', null, null, $objPenParametroRN->getParametro('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO'));

            $objSeiRN = new SeiRN();

            if (!isset($parNumIdentificacaoTramite)) {
                throw new InfraException('Parâmetro $parNumIdentificacaoTramite não informado.');
            }

            //TODO: Urgente: Verificar o status do trâmite e verificar se ele já foi salvo na base de dados
            $this->gravarLogDebug("Solicitando metadados do trâmite " . $parNumIdentificacaoTramite, 4);
            $objMetadadosProcedimento = $this->objProcessoEletronicoRN->solicitarMetadados($parNumIdentificacaoTramite);

            if (isset($objMetadadosProcedimento)) {
                $strNumeroRegistro = $objMetadadosProcedimento->metadados->NRE;
                $objProcesso = $objMetadadosProcedimento->metadados->processo;

                $this->objProcedimentoAndamentoRN->setOpts($strNumeroRegistro, $parNumIdentificacaoTramite, ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO));
                $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento('Iniciando recebimento de processo externo', 'S'));

                //Verifica se processo já foi registrado para esse trâmite
                //Tratamento para evitar o recebimento simultâneo do mesmo procedimento em serviços/processos concorrentes
                $this->sincronizarRecebimentoProcessos($strNumeroRegistro, $parNumIdentificacaoTramite);
                if($this->tramiteRecebimentoRegistrado($strNumeroRegistro, $parNumIdentificacaoTramite)) {
                    $this->gravarLogDebug("Trâmite de recebimento $parNumIdentificacaoTramite já registrado para o processo " . $objProcesso->protocolo, 4);
                    return;
                }

                //Substituir a unidade destinatária para a receptora
                if (isset($objMetadadosProcedimento->metadados->unidadeReceptora)) {
                    $unidadeReceptora = $objMetadadosProcedimento->metadados->unidadeReceptora;
                    $this->destinatarioReal = $objMetadadosProcedimento->metadados->destinatario;
                    $objMetadadosProcedimento->metadados->destinatario->identificacaoDoRepositorioDeEstruturas = $unidadeReceptora->identificacaoDoRepositorioDeEstruturas;
                    $objMetadadosProcedimento->metadados->destinatario->numeroDeIdentificacaoDaEstrutura = $unidadeReceptora->numeroDeIdentificacaoDaEstrutura;
                    $numUnidadeReceptora = $unidadeReceptora->numeroDeIdentificacaoDaEstrutura;
                    $this->gravarLogDebug("Atribuindo unidade centralizadora $numUnidadeReceptora para o trâmite $parNumIdentificacaoTramite", 4);
                }

                // Validação dos dados do processo recebido
                $objInfraException = new InfraException();
                $this->validarDadosDestinatario($objInfraException, $objMetadadosProcedimento);
                $objInfraException->lancarValidacoes();

                #############################INICIA O RECEBIMENTO DOS COMPONENTES DIGITAIS US010################################################
                $arrObjTramite = $this->objProcessoEletronicoRN->consultarTramites($parNumIdentificacaoTramite);
                $objTramite = $arrObjTramite[0];

                //Obtém lista de componentes digitais que precisam ser obtidos
                $this->gravarLogDebug("Obtém lista de componentes digitais que precisam ser obtidos", 4);
                if(!is_array($objTramite->componenteDigitalPendenteDeRecebimento)){
                    $objTramite->componenteDigitalPendenteDeRecebimento = array($objTramite->componenteDigitalPendenteDeRecebimento);
                }

                $this->validarComponentesDigitais($objProcesso, $parNumIdentificacaoTramite);
                $this->validarExtensaoComponentesDigitais($parNumIdentificacaoTramite, $objProcesso);
                $this->verificarPermissoesDiretorios($parNumIdentificacaoTramite);

                $this->gravarLogDebug("Obtendo metadados dos componentes digitais do processo", 4);
                $arrAnexosComponentes = array();
                $arrayHash = array();

                // Lista todos os componentes digitais presente no protocolo
                // Esta verificação é necessária pois existem situações em que a lista de componentes digitais
                // pendentes de recebimento informado pelo PEN não está de acordo com a lista atual de arquivos
                // mantida pela aplicação.
                $arrHashComponentesProtocolo = $this->listarHashDosComponentesMetadado($objProcesso);
                $arrHashPendentesDownload = $objTramite->componenteDigitalPendenteDeRecebimento;

                $numQtdComponentes = count($arrHashComponentesProtocolo);
                $this->gravarLogDebug("$numQtdComponentes componentes digitais identificados no protocolo {$objProcesso->protocolo}", 6);

                //Percorre os componentes que precisam ser recebidos
                foreach($arrHashComponentesProtocolo as $key => $componentePendente){

                    $numOrdemComponente = $key + 1;
                    if(!is_null($componentePendente)){
                        //TODO: Necessário otimizar trecho abaixo para evitar download desnecessário de documentos
                        //TODO: Download do componente digital é realizado, mesmo já existindo na base de dados, devido a comportamento obrigatório do Barramento para mudança de status
                        //Ajuste deverá ser feito em versões futuas
                        $arrayHash[] = $componentePendente;

                        //Obter os dados do componente digital
                        $this->gravarLogDebug("Baixando componente digital $numOrdemComponente", 6);
                        $objComponenteDigital = $this->objProcessoEletronicoRN->receberComponenteDigital($parNumIdentificacaoTramite, $componentePendente, $objTramite->protocolo);
                        $arrAnexosComponentes[$key][$componentePendente] = $this->objReceberComponenteDigitalRN->copiarComponenteDigitalPastaTemporaria($objComponenteDigital);
                        $arrAnexosComponentes[$key]['recebido'] = false;

                        //Valida a integridade do hash
                        $this->gravarLogDebug("Validando integridade de componente digital $numOrdemComponente", 6);
                        $this->objReceberComponenteDigitalRN->validarIntegridadeDoComponenteDigital($arrAnexosComponentes[$key][$componentePendente],
                            $componentePendente, $parNumIdentificacaoTramite);
                    }
                }

                if(count($arrAnexosComponentes) > 0){
                    $this->objReceberComponenteDigitalRN->setArrAnexos($arrAnexosComponentes);
                }

                #############################TERMINA O RECEBIMENTO DOS COMPONENTES DIGITAIS US010################################################

                $arrObjTramite = $this->objProcessoEletronicoRN->consultarTramites($parNumIdentificacaoTramite);
                $objTramite = $arrObjTramite[0];

                //Verifica se o trâmite está recusado
                //TODO: Testar o erro de interrupção forçado para certificar que o rollback está sendo realizado da forma correta
                if($objTramite->situacaoAtual == ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO) {
                    throw new InfraException("Trâmite $parNumIdentificacaoTramite já se encontra recusado. Cancelando o recebimento do processo");
                }

                $this->gravarLogDebug("Persistindo/atualizando dados do processo com NRE " . $strNumeroRegistro, 4);
                list($objProcedimentoDTO, $bolProcedimentoExistente) = $this->registrarProcesso(
                    $strNumeroRegistro,
                    $parNumIdentificacaoTramite,
                    $objProcesso,
                    $objMetadadosProcedimento);

                // @join_tec US008.08 (#23092)
                $this->objProcedimentoAndamentoRN->setOpts($strNumeroRegistro, $parNumIdentificacaoTramite, ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO), $objProcedimentoDTO->getDblIdProcedimento());
                $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento('Obtendo metadados do processo', 'S'));

                $this->gravarLogDebug("Registrando trâmite externo do processo", 4);
                $objProcessoEletronicoDTO = $this->objProcessoEletronicoRN->cadastrarTramiteDeProcesso(
                    $objProcedimentoDTO->getDblIdProcedimento(),
                    $strNumeroRegistro,
                    $parNumIdentificacaoTramite,
                    ProcessoEletronicoRN::$STA_TIPO_TRAMITE_RECEBIMENTO,
                    null,
                    $objMetadadosProcedimento->metadados->remetente->identificacaoDoRepositorioDeEstruturas,
                    $objMetadadosProcedimento->metadados->remetente->numeroDeIdentificacaoDaEstrutura,
                    $objMetadadosProcedimento->metadados->destinatario->identificacaoDoRepositorioDeEstruturas,
                    $objMetadadosProcedimento->metadados->destinatario->numeroDeIdentificacaoDaEstrutura,
                    $objProcesso);

                //Verifica se o tramite se encontra na situação correta
                $arrObjTramite = $this->objProcessoEletronicoRN->consultarTramites($parNumIdentificacaoTramite);
                if(!isset($arrObjTramite) || count($arrObjTramite) != 1) {
                    throw new InfraException("Trâmite não pode ser localizado pelo identificado $parNumIdentificacaoTramite.");
                }

                $objTramite = $arrObjTramite[0];
                if($objTramite->situacaoAtual != ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO) {
                    throw new InfraException("Desconsiderando recebimento do processo devido a situação de trâmite inconsistente: " . $objTramite->situacaoAtual);
                }

                //Atribui componentes digitais baixados anteriormente aos documentos do processo
                $this->atribuirComponentesDigitaisAosDocumentos($objProcedimentoDTO, $strNumeroRegistro, $parNumIdentificacaoTramite, $arrayHash, $arrayHashPendentes);

                //Finalizar o envio do documento para a respectiva unidade
                $this->enviarProcedimentoUnidade($objProcedimentoDTO, null, $bolProcedimentoExistente);

                $this->validarPosCondicoesTramite($objMetadadosProcedimento->metadados, $objProcedimentoDTO);

                $this->gravarLogDebug("Enviando recibo de conclusão do trâmite $parNumIdentificacaoTramite", 6);
                $objEnviarReciboTramiteRN = new EnviarReciboTramiteRN();
                $objEnviarReciboTramiteRN->enviarReciboTramiteProcesso($parNumIdentificacaoTramite, $arrayHash);

                $this->gravarLogDebug("Registrando a conclusão do recebimento do trâmite $parNumIdentificacaoTramite", 6);
                $objPenTramiteProcessadoRN = new PenTramiteProcessadoRN(PenTramiteProcessadoRN::STR_TIPO_PROCESSO);
                $objPenTramiteProcessadoRN->setRecebido($parNumIdentificacaoTramite);
            }
        } catch (Exception $e) {
            $mensagemErro = InfraException::inspecionar($e);
            $this->gravarLogDebug($mensagemErro);
            LogSEI::getInstance()->gravar($mensagemErro);
            throw $e;
        }
    }

    /**
     * Método responsável por atribuir a lista de componentes digitais baixados do PEN aos seus respectivos documentos no SEI
     *
     * @return [type]                                      [description]
     */
    private function atribuirComponentesDigitaisAosDocumentos(ProcedimentoDTO $parObjProcedimentoDTO, $parStrNumeroRegistro, $parNumIdentificacaoTramite,
        $parArrHashComponentes)
    {
        if(count($parArrHashComponentes) > 0){
            //Obter dados dos componetes digitais
            $this->gravarLogDebug("Iniciando o recebimento dos componentes digitais pendentes", 4);
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

                $arrStrNomeDocumento = $this->listarMetaDadosComponentesDigitais($objProcesso);
                foreach($arrObjComponentesDigitaisDTO as $objComponenteDigitalDTOEnviado) {
                    if($objComponenteDigitalDTOEnviado->getStrStaEstadoProtocolo() != ProtocoloRN::$TE_DOCUMENTO_CANCELADO){

                        $dblIdProcedimento = $objComponenteDigitalDTOEnviado->getDblIdProcedimento();
                        $dblIdDocumento = $objComponenteDigitalDTOEnviado->getDblIdDocumento();
                        $strHash = $objComponenteDigitalDTOEnviado->getStrHashConteudo();

                        //Verificar se documento já foi recebido anteriormente para poder registrar
                        if($this->documentosPendenteRegistro($dblIdProcedimento, $dblIdDocumento, $strHash)){
                            $strNomeDocumento = array_key_exists($strHash, $arrStrNomeDocumento) ? $arrStrNomeDocumento[$strHash]['especieNome'] : '[Desconhecido]';
                            $this->objReceberComponenteDigitalRN->receberComponenteDigital($objComponenteDigitalDTOEnviado);
                            $strMensagemRecebimento = sprintf('Recebendo %s %s', $strNomeDocumento, $objComponenteDigitalDTOEnviado->getStrProtocoloDocumentoFormatado());
                            $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento($strMensagemRecebimento, 'S'));
                            $this->gravarLogDebug($strMensagemRecebimento, 6);
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
     * Método para recuperar a lista de todos os hashs dos componentes digitais presentes no protocolo recebido
     *
     * @return Array Lista de hashs dos componentes digitais
     */
    private function listarHashDosComponentesMetadado($parObjProtocolo)
    {
        if(!isset($parObjProtocolo->documento)){
            throw new InfraException("Metadados do componente digital do documento de ordem {$objDocumento->ordem} não informado.");
        }

        $arrHashsComponentesDigitais = array();
        $arrObjDocumento = is_array($parObjProtocolo->documento) ? $parObjProtocolo->documento : array($parObjProtocolo->documento);
        foreach($arrObjDocumento as $objDocumento){

            //Desconsidera os componendes digitais de documentos cancelados
            if(!isset($objDocumento->retirado) || $objDocumento->retirado == false) {
                if(!isset($objDocumento->componenteDigital)){
                    throw new InfraException("Metadados do componente digital do documento de ordem {$objDocumento->ordem} não informado.");
                }

                $arrObjComponentesDigitais = is_array($objDocumento->componenteDigital) ? $objDocumento->componenteDigital : array($objDocumento->componenteDigital);
                foreach ($arrObjComponentesDigitais as $objComponenteDigital) {
                    $arrHashsComponentesDigitais[] = ProcessoEletronicoRN::getHashFromMetaDados($objComponenteDigital->hash);
                }
            }
        }

        return $arrHashsComponentesDigitais;
    }

    public function fecharProcedimentoEmOutraUnidades(ProcedimentoDTO $objProcedimentoDTO, $parObjMetadadosProcedimento){

        $objPenUnidadeDTO = new PenUnidadeDTO();
        $objPenUnidadeDTO->setNumIdUnidadeRH($parObjMetadadosProcedimento->metadados->destinatario->numeroDeIdentificacaoDaEstrutura);
        $objPenUnidadeDTO->retNumIdUnidade();

        $objGenericoBD = new GenericoBD($this->inicializarObjInfraIBanco());
        $objPenUnidadeDTO = $objGenericoBD->consultar($objPenUnidadeDTO);

        if(empty($objPenUnidadeDTO)) {
            return false;
        }

        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDistinct(true);
        $objAtividadeDTO->setNumIdUnidade($objPenUnidadeDTO->getNumIdUnidade(), InfraDTO::$OPER_DIFERENTE);
        $objAtividadeDTO->setDblIdProtocolo($objProcedimentoDTO->getDblIdProcedimento());
        $objAtividadeDTO->setDthConclusao(null);
        $objAtividadeDTO->setOrdStrSiglaUnidade(InfraDTO::$TIPO_ORDENACAO_ASC);
        $objAtividadeDTO->setOrdStrSiglaUsuarioAtribuicao(InfraDTO::$TIPO_ORDENACAO_DESC);
        $objAtividadeDTO->retStrSiglaUnidade();
        $objAtividadeDTO->retStrDescricaoUnidade();
        $objAtividadeDTO->retNumIdUsuarioAtribuicao();
        $objAtividadeDTO->retStrSiglaUsuarioAtribuicao();
        $objAtividadeDTO->retStrNomeUsuarioAtribuicao();
        $objAtividadeDTO->retNumIdUnidade();

        $objAtividadeRN = new AtividadeRN();
        $arrObjAtividadeDTO = (array)$objAtividadeRN->listarRN0036($objAtividadeDTO);

        $objInfraSessao = SessaoSEI::getInstance();
        $numIdUnidade = $objInfraSessao->getNumIdUnidadeAtual();

        foreach($arrObjAtividadeDTO as $objAtividadeDTO) {

            $objInfraSessao->setNumIdUnidadeAtual($objAtividadeDTO->getNumIdUnidade());
            $objInfraSessao->trocarUnidadeAtual();

            $objProcedimentoRN = new ProcedimentoRN();
            $objProcedimentoRN->concluir(array($objProcedimentoDTO));
        }
        $objInfraSessao->setNumIdUnidadeAtual($numIdUnidade);
        $objInfraSessao->trocarUnidadeAtual();
    }

    /**
     * Retorna um array com alguns metadados, onde o indice de é o hash do arquivo
     *
     * @return array[String]
     */
    private function listarMetaDadosComponentesDigitais($objProcesso){

        $objMapBD = new GenericoBD($this->getObjInfraIBanco());
        $arrMetadadoDocumento = array();
        $arrObjDocumento = is_array($objProcesso->documento) ? $objProcesso->documento : array($objProcesso->documento);

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

    /**
     * Valida cada componente digital, se não algum não for aceito recusa o tramite
     * do procedimento para esta unidade
     */
    private function validarComponentesDigitais($objProcesso, $parNumIdentificacaoTramite){

        $arrObjDocumentos = is_array($objProcesso->documento) ? $objProcesso->documento : array($objProcesso->documento);

        foreach($arrObjDocumentos as $objDocument){

            $objPenRelTipoDocMapEnviadoDTO = new PenRelTipoDocMapRecebidoDTO();
            $objPenRelTipoDocMapEnviadoDTO->retTodos();
            $objPenRelTipoDocMapEnviadoDTO->setNumCodigoEspecie($objDocument->especie->codigo);

            $objProcessoEletronicoDB = new PenRelTipoDocMapRecebidoBD(BancoSEI::getInstance());
            $numContador = (integer)$objProcessoEletronicoDB->contar($objPenRelTipoDocMapEnviadoDTO);

            // Não achou, ou seja, não esta cadastrado na tabela, então não é
            // aceito nesta unidade como válido
            if($numContador <= 0) {
                $this->objProcessoEletronicoRN->recusarTramite($parNumIdentificacaoTramite, sprintf('Documento do tipo %s não está mapeado', utf8_decode($objDocument->especie->nomeNoProdutor)), ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_ESPECIE_NAO_MAPEADA);
                throw new InfraException(sprintf('Documento do tipo %s não está mapeado. Motivo da Recusa no Barramento: %s', $objDocument->especie->nomeNoProdutor, ProcessoEletronicoRN::$MOTIVOS_RECUSA[ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_ESPECIE_NAO_MAPEADA]));
            }
        }

        $objPenParametroRN = new PenParametroRN();
        $numTamDocExterno = $objPenParametroRN->getParametro('PEN_TAMANHO_MAXIMO_DOCUMENTO_EXPEDIDO');

        foreach($arrObjDocumentos as $objDocument) {

            //Não valida informações do componente digital caso o documento esteja cancelado
            if(isset($objDocumento->retirado) && $objDocumento->retirado === true){
                if (is_null($objDocument->componenteDigital->tamanhoEmBytes) || $objDocument->componenteDigital->tamanhoEmBytes == 0){
                    throw new InfraException('Tamanho de componente digital não informado.', null, 'RECUSA: '.ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_OUTROU);
                }

                if($objDocument->componenteDigital->tamanhoEmBytes > ($numTamDocExterno * 1024 * 1024)){
                    $numTamanhoMb = $objDocument->componenteDigital->tamanhoEmBytes / ( 1024 * 1024);
                    $this->objProcessoEletronicoRN->recusarTramite($parNumIdentificacaoTramite, 'Componente digital não pode ultrapassar '.round($numTamDocExterno, 2).'MBs, o tamanho do anexo é '.round($numTamanhoMb, 2).'MBs .', ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_OUTROU);
                    throw new InfraException('Componente digital não pode ultrapassar '.round($numTamDocExterno, 2).'MBs, o tamanho do anexo é '.round($numTamanhoMb).'MBs');

                }
            }
        }

    }


    private function registrarProcesso($parStrNumeroRegistro, $parNumIdentificacaoTramite, $parObjProcesso, $parObjMetadadosProcedimento)
    {
        // Validação dos dados do processo recebido
        $objInfraException = new InfraException();
        $this->validarDadosProcesso($objInfraException, $parObjProcesso);
        $this->validarDadosDocumentos($objInfraException, $parObjProcesso);

        //TODO: Regra de Negócio - Processos recebidos pelo Barramento não poderão disponibilizar a opção de reordenação e cancelamento de documentos
        //para o usuário final, mesmo possuindo permissão para isso

        $objInfraException->lancarValidacoes();

        //Verificar se procedimento já existia na base de dados do sistema
        $dblIdProcedimento = $this->consultarProcedimentoExistente($parStrNumeroRegistro, $parObjProcesso->protocolo);
        $bolProcedimentoExistente = isset($dblIdProcedimento);

        if($bolProcedimentoExistente){
            $objProcedimentoDTO = $this->atualizarProcedimento($dblIdProcedimento, $parObjMetadadosProcedimento, $parObjProcesso, $parNumIdentificacaoTramite);
        }
        else {
            $objProcedimentoDTO = $this->gerarProcedimento($parObjMetadadosProcedimento, $parObjProcesso, $parNumIdentificacaoTramite);
        }

        //Chamada recursiva para registro dos processos apensados
        if(isset($objProcesso->processoApensado)) {
            if(!is_array($objProcesso->processoApensado)) {
                $objProcesso->processoApensado = array($objProcesso->processoApensado);
            }

            foreach ($objProcesso->processoApensado as $objProcessoApensado) {
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



    private function consultarProcedimentoExistente($parStrNumeroRegistro, $parStrProtocolo = null)
    {
        $dblIdProcedimento = null;
        $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
        $objProcessoEletronicoDTO->retDblIdProcedimento();
        $objProcessoEletronicoDTO->setStrNumeroRegistro($parStrNumeroRegistro);

        //TODO: Manter o padrão o sistema em chamar uma classe de regra de negócio (RN) e não diretamente um classe BD
        $objProcessoEletronicoBD = new ProcessoEletronicoBD($this->getObjInfraIBanco());
        $objProcessoEletronicoDTO = $objProcessoEletronicoBD->consultar($objProcessoEletronicoDTO);

        if(isset($objProcessoEletronicoDTO)){
            $dblIdProcedimento = $objProcessoEletronicoDTO->getDblIdProcedimento();
        }

        return $dblIdProcedimento;
    }

    private function atualizarProcedimento($parDblIdProcedimento, $objMetadadosProcedimento, $objProcesso, $parNumIdTramite)
    {
        if(!isset($parDblIdProcedimento)){
            throw new InfraException('Parâmetro $parDblIdProcedimento não informado.');
        }

        if(!isset($objMetadadosProcedimento)){
            throw new InfraException('Parâmetro $objMetadadosProcedimento não informado.');
        }

        if ($this->destinatarioReal) {
            $objDestinatario = $this->destinatarioReal;
        } else {
            $objDestinatario = $objMetadadosProcedimento->metadados->destinatario;
        }

        //Busca a unidade em ao qual o processo foi anteriormente expedido
        //Esta unidade deverá ser considerada para posterior desbloqueio do processo e reabertura
        $numIdUnidade = ProcessoEletronicoRN::obterUnidadeParaRegistroDocumento($parDblIdProcedimento);
        SessaoSEI::getInstance()->setNumIdUnidadeAtual($numIdUnidade);

        $objPenParametroRN = new PenParametroRN();

        try {
            $objSeiRN = new SeiRN();
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
                $objSeiRN->reabrirProcesso($objEntradaReabrirProcessoAPI);
            }

            ProcessoEletronicoRN::desbloquearProcesso($parDblIdProcedimento);

            $numUnidadeReceptora = $objPenParametroRN->getParametro('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO');
            $this->enviarProcedimentoUnidade($objProcedimentoDTO, $numUnidadeReceptora);

        } finally {
            $numUnidadeReceptora = $objPenParametroRN->getParametro('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO');
            SessaoSEI::getInstance()->setNumIdUnidadeAtual($numUnidadeReceptora);
        }

        $this->registrarAndamentoRecebimentoProcesso($objProcedimentoDTO, $objMetadadosProcedimento);

        //Cadastro das atividades para quando o destinatário é desviado pelo receptor (!3!)
        if ($this->destinatarioReal->numeroDeIdentificacaoDaEstrutura) {
            $this->gerarAndamentoUnidadeReceptora($parDblIdProcedimento);
        }

        //TODO: Obter código da unidade através de mapeamento entre SEI e Barramento
        $objUnidadeDTO = $this->atribuirDadosUnidade($objProcedimentoDTO, $objDestinatario);

        $this->atribuirDocumentos($objProcedimentoDTO, $objProcesso, $objUnidadeDTO, $objMetadadosProcedimento);

        $this->registrarProcedimentoNaoVisualizado($objProcedimentoDTO);

        //TODO: Avaliar necessidade de restringir referência circular entre processos
        //TODO: Registrar que o processo foi recebido com outros apensados. Necessário para posterior reenvio
        $this->atribuirProcessosApensados($objProcedimentoDTO, $objProcesso->processoApensado);

        //Realiza a alteração dos metadados do processo
        //TODO: Implementar alteração de todos os metadados
        $this->alterarMetadadosProcedimento($objProcedimentoDTO->getDblIdProcedimento(), $objProcesso);

        //Finaliza o envio do documento para a respectiva unidade
        //$this->enviarProcedimentoUnidade($objProcedimentoDTO, true);

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

    private function gerarProcedimento($objMetadadosProcedimento, $objProcesso, $parNumIdTramite)
    {
        if(!isset($objMetadadosProcedimento)){
            throw new InfraException('Parâmetro $objMetadadosProcedimento não informado.');
        }

        //TODO: Usar dados do destinatário em outro método específico para envio
        // Dados do procedimento enviados pelos órgão externo integrado ao PEN
        //$objProcesso = $objMetadadosProcedimento->metadados->processo;
        $objRemetente = $objMetadadosProcedimento->metadados->remetente;
        $objDestinatario = $objMetadadosProcedimento->metadados->destinatario;

        //TODO: TESTES DE RECEBIMENTO DE PROCESSOS
        //REMOVER APOS TESTES DO SISTEMA
        //$objProcesso->protocolo = rand(100000000, 999999999);

        //Atribuição de dados do protocolo
        //TODO: Validar cada uma das informações de entrada do webservice
        $objProtocoloDTO = new ProtocoloDTO();
        $objProtocoloDTO->setDblIdProtocolo(null);
        $objProtocoloDTO->setStrDescricao(utf8_decode($objProcesso->descricao));
        $objProtocoloDTO->setStrStaNivelAcessoLocal($this->obterNivelSigiloSEI($objProcesso->nivelDeSigilo));

        if($this->obterNivelSigiloSEI($objProcesso->nivelDeSigilo) == ProtocoloRN::$NA_RESTRITO){
            $objHipoteseLegalRecebido = new PenRelHipoteseLegalRecebidoRN();
            $objPenParametroRN = new PenParametroRN();
            $numIdHipoteseLegalPadrao = $objPenParametroRN->getParametro('HIPOTESE_LEGAL_PADRAO');

            if (!isset($objProcesso->hipoteseLegal) || (isset($objProcesso->hipoteseLegal) && empty($objProcesso->hipoteseLegal->identificacao))) {
                $objProtocoloDTO->setNumIdHipoteseLegal($numIdHipoteseLegalPadrao);
            } else {

                $numIdHipoteseLegal = $objHipoteseLegalRecebido->getIdHipoteseLegalSEI($objProcesso->hipoteseLegal->identificacao);
                if (empty($numIdHipoteseLegal)) {
                    $objProtocoloDTO->setNumIdHipoteseLegal($numIdHipoteseLegalPadrao);
                } else {
                    $objProtocoloDTO->setNumIdHipoteseLegal($numIdHipoteseLegal);
                }
            }
        }

        $objProtocoloDTO->setStrProtocoloFormatado(utf8_decode($objProcesso->protocolo));
        $objProtocoloDTO->setDtaGeracao($this->objProcessoEletronicoRN->converterDataSEI($objProcesso->dataHoraDeProducao));
        $objProtocoloDTO->setArrObjAnexoDTO(array());
        $objProtocoloDTO->setArrObjRelProtocoloAssuntoDTO(array());
        $objProtocoloDTO->setArrObjRelProtocoloProtocoloDTO(array());
        //$objProtocoloDTO->setStrStaEstado(ProtocoloRN::$TE_BLOQUEADO);
        $this->atribuirParticipantes($objProtocoloDTO, $objProcesso->interessado);

        $strDescricao  = sprintf('Tipo de processo no órgão de origem: %s', utf8_decode($objProcesso->processoDeNegocio)).PHP_EOL;
        $strDescricao .= $objProcesso->observacao;

        $objObservacaoDTO  = new ObservacaoDTO();

        //!Criação da observação de aviso para qual é a real unidade emitida (!2!)
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

        //throw new InfraException(var_export($objObservacaoDTO, true));

        $objProtocoloDTO->setArrObjObservacaoDTO(array($objObservacaoDTO));

        //Atribuição de dados do procedimento
        //TODO: Validar cada uma das informações de entrada do webservice
        $objProcedimentoDTO = new ProcedimentoDTO();
        $objProcedimentoDTO->setDblIdProcedimento(null);
        $objProcedimentoDTO->setObjProtocoloDTO($objProtocoloDTO);
        $objProcedimentoDTO->setStrNomeTipoProcedimento(utf8_decode($objProcesso->processoDeNegocio));
        $objProcedimentoDTO->setDtaGeracaoProtocolo($this->objProcessoEletronicoRN->converterDataSEI($objProcesso->dataHoraDeProducao));
        $objProcedimentoDTO->setStrProtocoloProcedimentoFormatado(utf8_decode($objProcesso->protocolo));
        $objProcedimentoDTO->setStrSinGerarPendencia('S');
       // $objProcedimentoDTO->setNumVersaoLock(0);  //TODO: Avaliar o comportamento desse campo no cadastro do processo
        $objProcedimentoDTO->setArrObjDocumentoDTO(array());

        //TODO: Identificar o tipo de procedimento correto para atribuição ao novo processo
        $objPenParametroRN = new PenParametroRN();
        $numIdTipoProcedimento = $objPenParametroRN->getParametro('PEN_TIPO_PROCESSO_EXTERNO');
        $this->atribuirTipoProcedimento($objProcedimentoDTO, $numIdTipoProcedimento, $objProcesso->processoDeNegocio);

        //TODO: Obter código da unidade através de mapeamento entre SEI e Barramento
        $objUnidadeDTO = $this->atribuirDadosUnidade($objProcedimentoDTO, $objDestinatario);

        //TODO: Tratar processamento de atributos procedimento_cadastro:177
        //...

        //TODO: Atribuir Dados do produtor do processo
        //$this->atribuirProdutorProcesso($objProcesso,
        //    $objProcedimentoDTO->getNumIdUsuarioGeradorProtocolo(),
        //    $objProcedimentoDTO->getNumIdUnidadeGeradoraProtocolo());




        //TODO:Adicionar demais informações do processo
        //<protocoloAnterior>
        //<historico>

        //$objProcesso->idProcedimentoSEI = $dblIdProcedimento;

        //TODO: Avaliar necessidade de tal recurso
        //FeedSEIProtocolos::getInstance()->setBolAcumularFeeds(true);

        //TODO: Analisar impacto do parâmetro SEI_HABILITAR_NUMERO_PROCESSO_INFORMADO no recebimento do processo
        //$objSeiRN = new SeiRN();
        //$objWSRetornoGerarProcedimentoDTO = $objSeiRN->gerarProcedimento($objWSEntradaGerarProcedimentoDTO);

        //TODO: Finalizar criação do procedimento
        $objProcedimentoRN = new ProcedimentoRN();
        $objProcedimentoDTOGerado = $objProcedimentoRN->gerarRN0156($objProcedimentoDTO);
        $objProcedimentoDTO->setDblIdProcedimento($objProcedimentoDTOGerado->getDblIdProcedimento());

        $this->registrarAndamentoRecebimentoProcesso($objProcedimentoDTO, $objMetadadosProcedimento);
        $this->atribuirDocumentos($objProcedimentoDTO, $objProcesso, $objUnidadeDTO, $objMetadadosProcedimento);

        $this->registrarProcedimentoNaoVisualizado($objProcedimentoDTOGerado);

        //TODO: Avaliar necessidade de restringir referência circular entre processos
        //TODO: Registrar que o processo foi recebido com outros apensados. Necessário para posterior reenvio
        $this->atribuirProcessosApensados($objProcedimentoDTO, $objProcesso->processoApensado);

        //Finalizar o envio do documento para a respectiva unidade
        //$this->enviarProcedimentoUnidade($objProcedimentoDTO);

        //$this->removerAndamentosProcedimento($objProcedimentoDTO);
        return $objProcedimentoDTO;
    }

    private function alterarMetadadosProcedimento($parNumIdProcedimento, $parObjMetadadoProcedimento)
    {
        //Realiza a alteração dos metadados do processo(Por hora, apenas do nível de sigilo e hipótese legal)
        $objProtocoloDTO = new ProtocoloDTO();
        $objProtocoloDTO->setDblIdProtocolo($parNumIdProcedimento);
        $objProtocoloDTO->setStrStaNivelAcessoLocal($this->obterNivelSigiloSEI($parObjMetadadoProcedimento->nivelDeSigilo));

        if($parObjMetadadoProcedimento->hipoteseLegal && $parObjMetadadoProcedimento->hipoteseLegal->identificacao){
            $objProtocoloDTO->setNumIdHipoteseLegal($this->obterHipoteseLegalSEI($parObjMetadadoProcedimento->hipoteseLegal->identificacao));
        }

        $objProtocoloRN = new ProtocoloRN();
        $objProtocoloRN->alterarRN0203($objProtocoloDTO);
    }

    private function alterarMetadadosDocumento($parNumIdProcedimento, $parNumIdDocumento, $parObjMetadadoDocumento)
    {
        //Realiza a alteração dos metadados do documento(Por hora, apenas do nível de sigilo e hipótese legal)
        $objProtocoloDTO = new ProtocoloDTO();
        $objProtocoloDTO->setDblIdProtocolo($parNumIdDocumento);
        $objProtocoloDTO->setStrStaNivelAcessoLocal($this->obterNivelSigiloSEI($parObjMetadadoDocumento->nivelDeSigilo));

        if($parObjMetadadoDocumento->hipoteseLegal && $parObjMetadadoDocumento->hipoteseLegal->identificacao){
            $objProtocoloDTO->setNumIdHipoteseLegal($this->obterHipoteseLegalSEI($parObjMetadadoDocumento->hipoteseLegal->identificacao));
        }

        $objProtocoloRN = new ProtocoloRN();
        $objProtocoloRN->alterarRN0203($objProtocoloDTO);
    }


    private function removerAndamentosProcedimento($parObjProtocoloDTO)
    {
        //TODO: Remover apenas as atividades geradas pelo recebimento do processo, não as atividades geradas anteriormente
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
        //TODO: Atribuir atributos necessários para formação da mensagem do andamento
        //TODO: Especificar quais andamentos serão registrados
        $objRemetente = $parObjMetadadosProcedimento->metadados->remetente;
        $objProcesso = $objMetadadosProcedimento->metadados->processo;

        $arrObjAtributoAndamentoDTO = array();

        //TODO: Otimizar código. Pesquisar 1 único elemento no barramento de serviços
        $objRepositorioDTO = $this->objProcessoEletronicoRN->consultarRepositoriosDeEstruturas(
          $objRemetente->identificacaoDoRepositorioDeEstruturas);

        //TODO: Otimizar código. Apenas buscar no barramento os dados da estrutura 1 única vez (AtribuirRemetente também utiliza)
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

        if(isset($objEstrutura->hierarquia)) {

            $arrObjNivel = $objEstrutura->hierarquia->nivel;

            $nome = "";
            $siglasUnidades = array();
            $siglasUnidades[] = $objEstrutura->sigla;

            foreach($arrObjNivel as $key => $objNivel){
                $siglasUnidades[] = $objNivel->sigla  ;
            }

            for($i = 1; $i <= 3; $i++){
                if(isset($siglasUnidades[count($siglasUnidades) - 1])){
                    unset($siglasUnidades[count($siglasUnidades) - 1]);
                }
            }

            foreach($siglasUnidades as $key => $nomeUnidade){
                if($key == (count($siglasUnidades) - 1)){
                    $nome .= $nomeUnidade." ";
                }else{
                    $nome .= $nomeUnidade." / ";
                }
            }

            $objNivel = current($arrObjNivel);

            $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
            $objAtributoAndamentoDTO->setStrNome('ENTIDADE_ORIGEM_HIRARQUIA');
            $objAtributoAndamentoDTO->setStrValor($nome);
            $objAtributoAndamentoDTO->setStrIdOrigem($objNivel->numeroDeIdentificacaoDaEstrutura);
            $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;
        }


        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDblIdProtocolo($objProcedimentoDTO->getDblIdProcedimento());
        $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
        $objAtividadeDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
        $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO));
        $objAtividadeDTO->setArrObjAtributoAndamentoDTO($arrObjAtributoAndamentoDTO);
        $objAtividadeDTO->setDthConclusao(null);
        $objAtividadeDTO->setNumIdUsuarioConclusao(null);
        $objAtividadeDTO->setStrSinInicial('N');

        $objAtividadeRN = new AtividadeRN();
        $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);

    }


    //TODO: Avaliar a necessidade de registrar os dados do remetente como participante do processo
    private function atribuirRemetente(ProtocoloDTO $objProtocoloDTO, $objRemetente)
    {
        $arrObjParticipantesDTO = array();
        if($objProtocoloDTO->isSetArrObjParticipanteDTO()) {
            $arrObjParticipantesDTO = $objProtocoloDTO->getArrObjParticipanteDTO();
        }

        //Obtenção de detalhes do remetente na infraestrutura do PEN
        $objEstruturaDTO = $this->objProcessoEletronicoRN->consultarEstrutura(
            $objRemetente->identificacaoDoRepositorioDeEstruturas,
            $objRemetente->numeroDeIdentificacaoDaEstrutura);

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
        $objProtocoloDTO->setArrObjParticipanteDTO($arrObjParticipantesDTO);
    }

    private function atribuirTipoProcedimento(ProcedimentoDTO $objProcedimentoDTO, $numIdTipoProcedimento)
    {
        if(!isset($numIdTipoProcedimento)){
            throw new InfraException('Parâmetro $numIdTipoProcedimento não informado.');
        }

        $objTipoProcedimentoDTO = new TipoProcedimentoDTO();
        $objTipoProcedimentoDTO->retNumIdTipoProcedimento();
        $objTipoProcedimentoDTO->retStrNome();
        $objTipoProcedimentoDTO->setNumIdTipoProcedimento($numIdTipoProcedimento);

        $objTipoProcedimentoRN = new TipoProcedimentoRN();
        $objTipoProcedimentoDTO = $objTipoProcedimentoRN->consultarRN0267($objTipoProcedimentoDTO);

        if ($objTipoProcedimentoDTO==null){
            throw new InfraException('Tipo de processo não encontrado.');
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

    protected function atribuirDadosUnidade(ProcedimentoDTO $objProcedimentoDTO, $objDestinatario)
    {

        if(!isset($objDestinatario)){
            throw new InfraException('Parâmetro $objDestinatario não informado.');
        }

        $objUnidadeDTOEnvio = $this->obterUnidadeMapeada($objDestinatario->numeroDeIdentificacaoDaEstrutura);

        if(!isset($objUnidadeDTOEnvio))
            throw new InfraException('Unidade de destino não pode ser encontrada. Repositório: '.$objDestinatario->identificacaoDoRepositorioDeEstruturas.', Número: ' . $objDestinatario->numeroDeIdentificacaoDaEstrutura);

        $arrObjUnidadeDTO = array();
        $arrObjUnidadeDTO[] = $objUnidadeDTOEnvio;
        $objProcedimentoDTO->setArrObjUnidadeDTO($arrObjUnidadeDTO);

        return $objUnidadeDTOEnvio;
    }

    //TODO: Grande parte da regra de negócio se baseou em SEIRN:199 - incluirDocumento.
    //Avaliar a refatoração para impedir a duplicação de código
    private function atribuirDocumentos($objProcedimentoDTO, $objProcesso, $objUnidadeDTO, $parObjMetadadosProcedimento)
    {
        if(!isset($objProcesso)) {
            throw new InfraException('Parâmetro $objProcesso não informado.');
        }

        if(!isset($objUnidadeDTO)) {
            throw new InfraException('Unidade responsável pelo documento não informada.');
        }

        if(!isset($objProcesso->documento)) {
            throw new InfraException('Lista de documentos do processo não informada.');
        }

        $arrObjDocumentos = $objProcesso->documento;
        if(!is_array($arrObjDocumentos)) {
            $arrObjDocumentos = array($arrObjDocumentos);
        }

        $strNumeroRegistro = $parObjMetadadosProcedimento->metadados->NRE;
        //$numTramite = $parObjMetadadosProcedimento->metadados->IDT;

        //Ordenação dos documentos conforme informado pelo remetente. Campo documento->ordem
        usort($arrObjDocumentos, array("ReceberProcedimentoRN", "comparacaoOrdemDocumentos"));

        //Obter dados dos documentos já registrados no sistema
        $objComponenteDigitalDTO = new ComponenteDigitalDTO();
        $objComponenteDigitalDTO->retNumOrdem();
        $objComponenteDigitalDTO->retDblIdProcedimento();
        $objComponenteDigitalDTO->retDblIdDocumento();
        $objComponenteDigitalDTO->retStrHashConteudo();
        $objComponenteDigitalDTO->setStrNumeroRegistro($strNumeroRegistro);
        $objComponenteDigitalDTO->setOrdNumOrdem(InfraDTO::$TIPO_ORDENACAO_ASC);

        $objComponenteDigitalBD = new ComponenteDigitalBD($this->getObjInfraIBanco());
        $arrObjComponenteDigitalDTO = $objComponenteDigitalBD->listar($objComponenteDigitalDTO);
        $arrObjComponenteDigitalDTOIndexado = InfraArray::indexarArrInfraDTO($arrObjComponenteDigitalDTO, "Ordem");

        $objProtocoloBD = new ProtocoloBD($this->getObjInfraIBanco());
        $objSeiRN = new SeiRN();

        $arrObjDocumentoDTO = array();
        foreach($arrObjDocumentos as $objDocumento){

            if(isset($objDocumento->retirado) && $objDocumento->retirado === true) {

                if(array_key_exists($objDocumento->ordem, $arrObjComponenteDigitalDTOIndexado)) {

                    //Busca o ID do protocolo
                    $objComponenteIndexado = $arrObjComponenteDigitalDTOIndexado[$objDocumento->ordem];
                    $dblIdProtocolo = $objComponenteIndexado->getDblIdDocumento();

                    //Instancia o DTO do protocolo
                    $objProtocoloDTO = new ProtocoloDTO();
                    $objProtocoloDTO->setDblIdProtocolo($dblIdProtocolo);
                    $objProtocoloDTO->retStrStaEstado();

                    $objProtocoloDTO = $objProtocoloBD->consultar($objProtocoloDTO);

                    if($objProtocoloDTO->getStrStaEstado() != ProtocoloRN::$TE_DOCUMENTO_CANCELADO){
                        $objEntradaCancelarDocumentoAPI = new EntradaCancelarDocumentoAPI();
                        $objEntradaCancelarDocumentoAPI->setIdDocumento($dblIdProtocolo);
                        $objEntradaCancelarDocumentoAPI->setMotivo('Cancelado pelo remetente');
                        $objSeiRN->cancelarDocumento($objEntradaCancelarDocumentoAPI);
                    }

                    continue;
                }
            }

            if(array_key_exists($objDocumento->ordem, $arrObjComponenteDigitalDTOIndexado)){
                $objComponenteDigitalDTO = $arrObjComponenteDigitalDTOIndexado[$objDocumento->ordem];
                $this->alterarMetadadosDocumento($objComponenteDigitalDTO->getDblIdProcedimento(), $objComponenteDigitalDTO->getDblIdDocumento(), $objDocumento);
                $objDocumento->idDocumentoSEI = $objComponenteDigitalDTO->getDblIdDocumento();
                continue;
            }

            //Validação dos dados dos documentos
            if(!isset($objDocumento->especie)){
                throw new InfraException('Espécie do documento ['.$objDocumento->descricao.'] não informada.');
            }

            $objDocumentoDTO = new DocumentoDTO();
            $objDocumentoDTO->setDblIdDocumento(null);
            $objDocumentoDTO->setDblIdProcedimento($objProcedimentoDTO->getDblIdProcedimento());

            $objSerieDTO = $this->obterSerieMapeada($objDocumento->especie->codigo);

            if ($objSerieDTO==null){
                throw new InfraException('Tipo de documento [Espécie '.$objDocumento->especie->codigo.'] não encontrado.');
            }

            if (InfraString::isBolVazia($objDocumento->dataHoraDeProducao)) {
                $objInfraException->lancarValidacao('Data do documento não informada.');
            }

            $objProcedimentoDTO2 = new ProcedimentoDTO();
            $objProcedimentoDTO2->retDblIdProcedimento();
            $objProcedimentoDTO2->retNumIdUsuarioGeradorProtocolo();
            $objProcedimentoDTO2->retNumIdTipoProcedimento();
            $objProcedimentoDTO2->retStrStaNivelAcessoGlobalProtocolo();
            $objProcedimentoDTO2->retStrProtocoloProcedimentoFormatado();
            $objProcedimentoDTO2->retNumIdTipoProcedimento();
            $objProcedimentoDTO2->retStrNomeTipoProcedimento();
            $objProcedimentoDTO2->adicionarCriterio(array('IdProcedimento','ProtocoloProcedimentoFormatado','ProtocoloProcedimentoFormatadoPesquisa'),
                array(InfraDTO::$OPER_IGUAL,InfraDTO::$OPER_IGUAL,InfraDTO::$OPER_IGUAL),
                array($objDocumentoDTO->getDblIdProcedimento(),$objDocumentoDTO->getDblIdProcedimento(),$objDocumentoDTO->getDblIdProcedimento()),
                array(InfraDTO::$OPER_LOGICO_OR,InfraDTO::$OPER_LOGICO_OR));

            $objProcedimentoRN = new ProcedimentoRN();
            $objProcedimentoDTO = $objProcedimentoRN->consultarRN0201($objProcedimentoDTO2);

            if ($objProcedimentoDTO==null){
                throw new InfraException('Processo ['.$objDocumentoDTO->getDblIdProcedimento().'] não encontrado.');
            }

            $objDocumentoDTO->setDblIdProcedimento($objProcedimentoDTO->getDblIdProcedimento());
            $objDocumentoDTO->setNumIdSerie($objSerieDTO->getNumIdSerie());
            $objDocumentoDTO->setStrNomeSerie($objSerieDTO->getStrNome());

            $objDocumentoDTO->setDblIdDocumentoEdoc(null);
            $objDocumentoDTO->setDblIdDocumentoEdocBase(null);
            $objDocumentoDTO->setNumIdUnidadeResponsavel($objUnidadeDTO->getNumIdUnidade());
            $objDocumentoDTO->setNumIdTipoConferencia(null);
            $objDocumentoDTO->setStrConteudo(null);
            $objDocumentoDTO->setStrStaDocumento(DocumentoRN::$TD_EXTERNO);

            $objProtocoloDTO = new ProtocoloDTO();
            $objDocumentoDTO->setObjProtocoloDTO($objProtocoloDTO);
            $objProtocoloDTO->setDblIdProtocolo(null);
            $objProtocoloDTO->setStrStaProtocolo(ProtocoloRN::$TP_DOCUMENTO_RECEBIDO);

            if($objDocumento->descricao != '***'){
                $objProtocoloDTO->setStrDescricao(utf8_decode($objDocumento->descricao));
                $objDocumentoDTO->setStrNumero(utf8_decode($objDocumento->descricao));
            }else{
                $objProtocoloDTO->setStrDescricao("");
                $objDocumentoDTO->setStrNumero("");
            }

            //TODO: Avaliar regra de formação do número do documento
            $objProtocoloDTO->setStrStaNivelAcessoLocal($this->obterNivelSigiloSEI($objDocumento->nivelDeSigilo));
            $objProtocoloDTO->setDtaGeracao($this->objProcessoEletronicoRN->converterDataSEI($objDocumento->dataHoraDeProducao));
            $objProtocoloDTO->setArrObjAnexoDTO(array());
            $objProtocoloDTO->setArrObjRelProtocoloAssuntoDTO(array());
            $objProtocoloDTO->setArrObjRelProtocoloProtocoloDTO(array());
            $objProtocoloDTO->setArrObjParticipanteDTO(array());

            //TODO: Analisar se o modelo de dados do PEN possui destinatários específicos para os documentos
            //caso não possua, analisar o repasse de tais informações via parãmetros adicionais
            $objObservacaoDTO  = new ObservacaoDTO();
            $objObservacaoDTO->setStrDescricao("Número SEI do Documento na Origem: ".$objDocumento->produtor->numeroDeIdentificacao);
            $objProtocoloDTO->setArrObjObservacaoDTO(array($objObservacaoDTO));

            $bolReabriuAutomaticamente = false;
            if ($objProcedimentoDTO->getStrStaNivelAcessoGlobalProtocolo()==ProtocoloRN::$NA_PUBLICO || $objProcedimentoDTO->getStrStaNivelAcessoGlobalProtocolo()==ProtocoloRN::$NA_RESTRITO) {
                $objAtividadeDTO = new AtividadeDTO();
                $objAtividadeDTO->setDblIdProtocolo($objDocumentoDTO->getDblIdProcedimento());
                $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());

                //TODO: Possivelmente, essa regra é desnecessária já que o processo pode ser enviado para outra unidade do órgão através da expedição
                //$objAtividadeRN = new AtividadeRN();
                //if ($objAtividadeRN->contarRN0035($objAtividadeDTO) == 0) {
                //    throw new InfraException('Unidade '.$objUnidadeDTO->getStrSigla().' não possui acesso ao Procedimento '.$objProcedimentoDTO->getStrProtocoloProcedimentoFormatado().'.');
                //}

                $objAtividadeDTO = new AtividadeDTO();
                $objAtividadeDTO->setDblIdProtocolo($objDocumentoDTO->getDblIdProcedimento());
                $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
                $objAtividadeDTO->setDthConclusao(null);

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
            $objTipoProcedimentoDTO->setNumIdTipoProcedimento($objProcedimentoDTO->getNumIdTipoProcedimento());

            $objTipoProcedimentoRN = new TipoProcedimentoRN();
            $objTipoProcedimentoDTO = $objTipoProcedimentoRN->consultarRN0267($objTipoProcedimentoDTO);

            if (InfraString::isBolVazia($objDocumentoDTO->getObjProtocoloDTO()->getStrStaNivelAcessoLocal()) || $objDocumentoDTO->getObjProtocoloDTO()->getStrStaNivelAcessoLocal()==$objTipoProcedimentoDTO->getStrStaNivelAcessoSugestao()) {
                $objDocumentoDTO->getObjProtocoloDTO()->setStrStaNivelAcessoLocal($objTipoProcedimentoDTO->getStrStaNivelAcessoSugestao());
                $objDocumentoDTO->getObjProtocoloDTO()->setStrStaGrauSigilo($objTipoProcedimentoDTO->getStrStaGrauSigiloSugestao());
                $objDocumentoDTO->getObjProtocoloDTO()->setNumIdHipoteseLegal($objTipoProcedimentoDTO->getNumIdHipoteseLegalSugestao());
            }

            if ($this->obterNivelSigiloSEI($objDocumento->nivelDeSigilo) == ProtocoloRN::$NA_RESTRITO) {
                $objHipoteseLegalRecebido = new PenRelHipoteseLegalRecebidoRN();
                $objPenParametroRN = new PenParametroRN();
                $numIdHipoteseLegalPadrao = $objPenParametroRN->getParametro('HIPOTESE_LEGAL_PADRAO');

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
            $strConteudoCodificado = $objDocumentoDTO->getStrConteudo();
            $objDocumentoDTO->setStrConteudo(null);
            $objDocumentoDTO->getObjProtocoloDTO()->setNumIdUnidadeGeradora(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
            $objDocumentoDTO->setStrSinBloqueado('N');

            //TODO: Fazer a atribuição dos componentes digitais do processo a partir desse ponto
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

            $objDocumento->idDocumentoSEI = $objDocumentoDTO->getDblIdDocumento();
            $arrObjDocumentoDTO[] = $objDocumentoDTO;

            if(isset($objDocumento->retirado) && $objDocumento->retirado === true) {
                $this->documentosRetirados[] = $objDocumento->idDocumentoSEI;
            }
        }

        foreach($this->documentosRetirados as $documentoCancelado){
            //Instancia o DTO do protocolo
            $objEntradaCancelarDocumentoAPI = new EntradaCancelarDocumentoAPI();
            $objEntradaCancelarDocumentoAPI->setIdDocumento($documentoCancelado);
            $objEntradaCancelarDocumentoAPI->setMotivo('Cancelado pelo remetente');
            $objSeiRN = new SeiRN();
            $objSeiRN->cancelarDocumento($objEntradaCancelarDocumentoAPI);
        }

        $objProcedimentoDTO->setArrObjDocumentoDTO($arrObjDocumentoDTO);
    }

    //TODO: Método deverá poderá ser transferido para a classe responsável por fazer o recebimento dos componentes digitais
    private function atribuirComponentesDigitais(DocumentoDTO $parObjDocumentoDTO, $parArrObjComponentesDigitais)
    {
        if(!isset($parArrObjComponentesDigitais)) {
            throw new InfraException('Componentes digitais do documento não informado.');
        }

        //TODO: Aplicar mesmas validações realizadas no momento do upload de um documento InfraPagina::processarUpload
        //TODO: Avaliar a refatoração do código abaixo para impedir a duplicação de regras de negócios


        $arrObjAnexoDTO = array();
        if($parObjDocumentoDTO->getObjProtocoloDTO()->isSetArrObjAnexoDTO()) {
            $arrObjAnexoDTO = $parObjDocumentoDTO->getObjProtocoloDTO()->getArrObjAnexoDTO();
        }

        if (!is_array($parArrObjComponentesDigitais)) {
            $parArrObjComponentesDigitais = array($parArrObjComponentesDigitais);
        }

        //TODO: Tratar a ordem dos componentes digitais
        //...

        $parObjDocumentoDTO->getObjProtocoloDTO()->setArrObjAnexoDTO($arrObjAnexoDTO);
    }


    private function atribuirProcessosApensados(ProcedimentoDTO $objProtocoloDTO, $objProcedimento)
    {
        if(isset($objProcedimento->processoApensado)) {
            if(!is_array($objProcedimento->processoApensado)){
                $objProcedimento->processoApensado = array($objProcedimento->processoApensado);
            }

            $objProcedimentoDTOApensado = null;
            foreach ($objProcedimento->processoApensado as $processoApensado) {
                $objProcedimentoDTOApensado = $this->gerarProcedimento($objMetadadosProcedimento, $processoApensado);
                $this->relacionarProcedimentos($objProcedimentoDTOPrincipal, $objProcedimentoDTOApensado);
                $this->registrarProcedimentoNaoVisualizado($objProcedimentoDTOApensado);
            }
        }
    }


    private function validarDadosDestinatario(InfraException $objInfraException, $objMetadadosProcedimento)
    {
        if(isset($objDestinatario)){
            throw new InfraException("Parâmetro $objDestinatario não informado.");
        }

        $objDestinatario = $objMetadadosProcedimento->metadados->destinatario;

        $objPenParametroRN = new PenParametroRN();
        $numIdRepositorioOrigem = $objPenParametroRN->getParametro('PEN_ID_REPOSITORIO_ORIGEM');
        $numIdRepositorioDestinoProcesso = $objDestinatario->identificacaoDoRepositorioDeEstruturas;
        $numeroDeIdentificacaoDaEstrutura = $objDestinatario->numeroDeIdentificacaoDaEstrutura;

        //Validação do repositório de destino do processo
        if($numIdRepositorioDestinoProcesso != $numIdRepositorioOrigem){
            $objInfraException->adicionarValidacao("Identificação do repositório de origem do processo [$numIdRepositorioDestinoProcesso] não reconhecida.");
        }

        //Validação do unidade de destino do processo
        $objUnidadeDTO = new PenUnidadeDTO();
        $objUnidadeDTO->setNumIdUnidadeRH($numeroDeIdentificacaoDaEstrutura);
        $objUnidadeDTO->setStrSinAtivo('S');
        $objUnidadeDTO->retNumIdUnidade();

        $objUnidadeRN = new UnidadeRN();
        $objUnidadeDTO = $objUnidadeRN->consultarRN0125($objUnidadeDTO);

        if(!isset($objUnidadeDTO)){
            $objInfraException->adicionarValidacao("Unidade [Estrutura: {$numeroDeIdentificacaoDaEstrutura}] não configurada para receber processos externos no sistema de destino.");
        }
    }

    private function validarDadosRemetente(InfraException $objInfraException, $objMetadadosProcedimento){

    }

    private function validarDadosProcesso(InfraException $objInfraException, $objMetadadosProcedimento){

    }

    private function validarDadosDocumentos(InfraException $objInfraException, $objMetadadosProcedimento){

    }

    private function obterNivelSigiloSEI($strNivelSigiloPEN) {
        switch ($strNivelSigiloPEN) {

            case ProcessoEletronicoRN::$STA_SIGILO_PUBLICO: return ProtocoloRN::$NA_PUBLICO;
            break;
            case ProcessoEletronicoRN::$STA_SIGILO_RESTRITO: return ProtocoloRN::$NA_RESTRITO;
            break;
            case ProcessoEletronicoRN::$STA_SIGILO_SIGILOSO: return ProtocoloRN::$NA_SIGILOSO;
            break;
            default:
            break;
        }
    }

    private function obterHipoteseLegalSEI($parNumIdHipoteseLegalPEN) {
        //Atribuí a hipótese legal
        $objHipoteseLegalRecebido = new PenRelHipoteseLegalRecebidoRN();
        $objPenParametroRN = new PenParametroRN();
        $numIdHipoteseLegalPadrao = $objPenParametroRN->getParametro('HIPOTESE_LEGAL_PADRAO');

        $numIdHipoteseLegal = $objHipoteseLegalRecebido->getIdHipoteseLegalSEI($parNumIdHipoteseLegalPEN);

        if (empty($numIdHipoteseLegal)) {
            return $numIdHipoteseLegalPadrao;
        } else {
            return $numIdHipoteseLegal;
        }
    }

    //TODO: Implementar o mapeamento entre as unidade do SEI e Barramento de Serviços (Secretaria de Saúde: 218794)
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


    private function obterSerieMapeada($numCodigoEspecie)
    {
        $objSerieDTO = null;

        $objMapDTO = new PenRelTipoDocMapRecebidoDTO();
        $objMapDTO->setNumCodigoEspecie($numCodigoEspecie);
        $objMapDTO->retNumIdSerie();

        $objGenericoBD = new GenericoBD($this->getObjInfraIBanco());
        $objMapDTO = $objGenericoBD->consultar($objMapDTO);

        if(empty($objMapDTO)) {
            $objMapDTO = new PenRelTipoDocMapRecebidoDTO();
            $objMapDTO->retNumIdSerie();
            $objMapDTO->setStrPadrao('S');
            $objMapDTO->setNumMaxRegistrosRetorno(1);
            $objMapDTO = $objGenericoBD->consultar($objMapDTO);
        }

        if(!empty($objMapDTO)) {
            $objSerieDTO = new SerieDTO();
            $objSerieDTO->retStrNome();
            $objSerieDTO->retNumIdSerie();
            $objSerieDTO->setNumIdSerie($objMapDTO->getNumIdSerie());

            $objSerieRN = new SerieRN();
            $objSerieDTO = $objSerieRN->consultarRN0644($objSerieDTO);
        }

        return $objSerieDTO;
    }

    private function relacionarProcedimentos($objProcedimentoDTO1, $objProcedimentoDTO2)
    {
        if(!isset($objProcedimentoDTO1) || !isset($objProcedimentoDTO1)) {
            throw new InfraException('Parâmetro $objProcedimentoDTO não informado.');
        }

        $objRelProtocoloProtocoloDTO = new RelProtocoloProtocoloDTO();
        $objRelProtocoloProtocoloDTO->setDblIdProtocolo1($objProcedimentoDTO2->getDblIdProcedimento());
        $objRelProtocoloProtocoloDTO->setDblIdProtocolo2($objProcedimentoDTO1->getDblIdProcedimento());
        $objRelProtocoloProtocoloDTO->setStrStaAssociacao(RelProtocoloProtocoloRN::$TA_PROCEDIMENTO_RELACIONADO);
        $objRelProtocoloProtocoloDTO->setStrMotivo(self::STR_APENSACAO_PROCEDIMENTOS);

        $objProcedimentoRN = new ProcedimentoRN();
        $objProcedimentoRN->relacionarProcedimentoRN1020($objRelProtocoloProtocoloDTO);
    }

    //TODO: Método identico ao localizado na classe SeiRN:2214
    //Refatorar código para evitar problemas de manutenção
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
                    throw new InfraException('Destinatário vazio ou nulo.');
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
                        $objContatoDTO = $dto;
                        break;
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
        $objPenParametroRN = new PenParametroRN();
        $objInfraException = new InfraException();

        $strEnviaEmailNotificacao = 'N';
        $numIdUnidade = $parUnidadeDestino;

        //Caso a unidade de destino não tenha sido informada, considerar as unidades atribuídas ao processo
        if(is_null($numIdUnidade)){
            if(!$parObjProcedimentoDTO->isSetArrObjUnidadeDTO() || count($parObjProcedimentoDTO->getArrObjUnidadeDTO()) == 0) {
                $objInfraException->lancarValidacao('Unidade de destino do processo não informada.');
            }

            $arrObjUnidadeDTO = $parObjProcedimentoDTO->getArrObjUnidadeDTO();
            if(count($parObjProcedimentoDTO->getArrObjUnidadeDTO()) > 1) {
                $objInfraException->lancarValidacao('Não permitido a indicação de múltiplas unidades de destino para um processo recebido externamente.');
            }

            $arrObjUnidadeDTO = array_values($parObjProcedimentoDTO->getArrObjUnidadeDTO());
            $objUnidadeDTO = $arrObjUnidadeDTO[0];
            $numIdUnidade = $objUnidadeDTO->getNumIdUnidade();

            //Somente considera regra de envio de e-mail para unidades vinculadas ao processo
            $strEnviaEmailNotificacao = $objPenParametroRN->getParametro('PEN_ENVIA_EMAIL_NOTIFICACAO_RECEBIMENTO');
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
            $objInfraException->lancarValidacao('Processo ['.$parObjProcedimentoDTO->getStrProtocoloProcedimentoFormatado().'] não encontrado.');
        }

        if ($objProcedimentoDTO->getStrStaNivelAcessoGlobalProtocolo()==ProtocoloRN::$NA_RESTRITO) {
            $objAcessoDTO = new AcessoDTO();
            $objAcessoDTO->setDblIdProtocolo($objProcedimentoDTO->getDblIdProcedimento());
            $objAcessoDTO->setNumIdUnidade($numIdUnidade);

            $objAcessoRN = new AcessoRN();
            if ($objAcessoRN->contar($objAcessoDTO)==0) {
            //  AVALIAR $objInfraException->adicionarValidacao('Unidade ['.$objUnidadeDTO->getStrSigla().'] não possui acesso ao processo ['.$objProcedimentoDTO->getStrProtocoloProcedimentoFormatado().'].');
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
            //reabertura automática
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

    /* Essa é a função estática de comparação */
    static function comparacaoOrdemDocumentos($parDocumento1, $parDocumento2)
    {
        $numOrdemDocumento1 = strtolower($parDocumento1->ordem);
        $numOrdemDocumento2 = strtolower($parDocumento2->ordem);
        return $numOrdemDocumento1 - $numOrdemDocumento2;
    }


    protected function receberTramitesRecusadosControlado($parNumIdentificacaoTramite)
    {
        try {
            if (empty($parNumIdentificacaoTramite)) {
                throw new InfraException('Parâmetro $parNumIdentificacaoTramite não informado.');
            }

            //Busca os dados do trâmite no barramento
            $tramite = $this->objProcessoEletronicoRN->consultarTramites($parNumIdentificacaoTramite);

            if(!isset($tramite[0])){
                throw new InfraException("Não foi encontrado no PEN o trâmite de número {$parNumIdentificacaoTramite} para realizar a ciência da recusa");
            }

            $tramite = $tramite[0];

            $objTramiteDTO = new TramiteDTO();
            $objTramiteDTO->setNumIdTramite($parNumIdentificacaoTramite);
            $objTramiteDTO->retNumIdUnidade();

            $objTramiteBD = new TramiteBD(BancoSEI::getInstance());
            $objTramiteDTO = $objTramiteBD->consultar($objTramiteDTO);

            if(isset($objTramiteDTO)){
                //throw new InfraException("Não foi encontrado no sistema o trâmite de número {$parNumIdentificacaoTramite} para realizar a ciência da recusa");
                SessaoSEI::getInstance(false)->simularLogin('SEI', null, null, $objTramiteDTO->getNumIdUnidade());

                //Busca os dados do procedimento
                $this->gravarLogDebug("Buscando os dados de procedimento com NRE " . $tramite->NRE, 2);
                $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
                $objProcessoEletronicoDTO->setStrNumeroRegistro($tramite->NRE);
                $objProcessoEletronicoDTO->retDblIdProcedimento();
                $objProcessoEletronicoBD = new ProcessoEletronicoBD($this->getObjInfraIBanco());
                $objProcessoEletronicoDTO = $objProcessoEletronicoBD->consultar($objProcessoEletronicoDTO);

                //Busca a última atividade de trâmite externo
                $this->gravarLogDebug("Buscando última atividade de trâmite externo do processo " . $objProcessoEletronicoDTO->getDblIdProcedimento(), 2);
                $objAtividadeDTO = new AtividadeDTO();
                $objAtividadeDTO->setDblIdProtocolo($objProcessoEletronicoDTO->getDblIdProcedimento());
                $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO));
                $objAtividadeDTO->setNumMaxRegistrosRetorno(1);
                $objAtividadeDTO->setOrdDthAbertura(InfraDTO::$TIPO_ORDENACAO_DESC);
                $objAtividadeDTO->retNumIdAtividade();
                $objAtividadeBD = new AtividadeBD($this->getObjInfraIBanco());
                $objAtividadeDTO = $objAtividadeBD->consultar($objAtividadeDTO);

                //Busca a unidade de destino
                $this->gravarLogDebug("Buscando informações sobre a unidade de destino", 2);
                $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
                $objAtributoAndamentoDTO->setNumIdAtividade($objAtividadeDTO->getNumIdAtividade());
                $objAtributoAndamentoDTO->setStrNome('UNIDADE_DESTINO');
                $objAtributoAndamentoDTO->retStrValor();
                $objAtributoAndamentoBD = new AtributoAndamentoBD($this->getObjInfraIBanco());
                $objAtributoAndamentoDTO = $objAtributoAndamentoBD->consultar($objAtributoAndamentoDTO);

                //Monta o DTO de receber tramite recusado
                $this->gravarLogDebug("Preparando recebimento de trâmite " . $parNumIdentificacaoTramite . " recusado", 2);
                $objReceberTramiteRecusadoDTO = new ReceberTramiteRecusadoDTO();
                $objReceberTramiteRecusadoDTO->setNumIdTramite($parNumIdentificacaoTramite);
                $objReceberTramiteRecusadoDTO->setNumIdProtocolo($objProcessoEletronicoDTO->getDblIdProcedimento());
                $objReceberTramiteRecusadoDTO->setNumIdUnidadeOrigem(null);
                $objReceberTramiteRecusadoDTO->setNumIdTarefa(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_RECUSADO));
                $objReceberTramiteRecusadoDTO->setStrMotivoRecusa(utf8_decode($tramite->justificativaDaRecusa));
                $objReceberTramiteRecusadoDTO->setStrNomeUnidadeDestino($objAtributoAndamentoDTO->getStrValor());

                //Faz o tratamento do processo e do trâmite recusado
                $this->gravarLogDebug("Atualizando dados do processo " . $objProcessoEletronicoDTO->getDblIdProcedimento() ." e do trâmite recusado " . $parNumIdentificacaoTramite, 2);
                $this->receberTramiteRecusadoInterno($objReceberTramiteRecusadoDTO);
            }

            $this->gravarLogDebug("Notificando serviços do PEN sobre ciência da recusa do trâmite " . $parNumIdentificacaoTramite, 4);
            $this->objProcessoEletronicoRN->cienciaRecusa($parNumIdentificacaoTramite);

        } catch (Exception $e) {
            $mensagemErro = InfraException::inspecionar($e);
            $this->gravarLogDebug($mensagemErro);
            LogSEI::getInstance()->gravar($mensagemErro);
            throw $e;
        }
    }


    protected function receberTramiteRecusadoInternoControlado(ReceberTramiteRecusadoDTO $objReceberTramiteRecusadoDTO)
    {
        //Verifica se processo está fechado, reabrindo-o caso necessário
        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDblIdProtocolo($objReceberTramiteRecusadoDTO->getNumIdProtocolo());
        $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
        $objAtividadeDTO->setDthConclusao(null);
        $objAtividadeRN = new AtividadeRN();
        if ($objAtividadeRN->contarRN0035($objAtividadeDTO) == 0) {
            $this->gravarLogDebug("Reabrindo automaticamente o processo", 4);
            $objReabrirProcessoDTO = new ReabrirProcessoDTO();
            $objReabrirProcessoDTO->setDblIdProcedimento($objReceberTramiteRecusadoDTO->getNumIdProtocolo());
            $objReabrirProcessoDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
            $objReabrirProcessoDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
            $objProcedimentoRN = new ProcedimentoRN();
            $objProcedimentoRN->reabrirRN0966($objReabrirProcessoDTO);
        }

        //Realiza o desbloqueio do processo
        $this->gravarLogDebug("Realizando o desbloqueio do processo", 4);
        $objProtocoloDTO = new ProtocoloDTO();
        $objProtocoloDTO->setDblIdProtocolo($objReceberTramiteRecusadoDTO->getNumIdProtocolo());
        $objProtocoloDTO->setStrStaEstado(ProtocoloRN::$TE_PROCEDIMENTO_BLOQUEADO);
        $objProtocoloRN = new ProtocoloRN();
        if($objProtocoloRN->contarRN0667($objProtocoloDTO) != 0) {
            ProcessoEletronicoRN::desbloquearProcesso($objReceberTramiteRecusadoDTO->getNumIdProtocolo());
        } else {
            $this->gravarLogDebug("Processo " . $objReceberTramiteRecusadoDTO->getNumIdProtocolo() . " já se encontra desbloqueado!", 6);
        }

        //Adiciona um andamento para o trâmite recusado
        $this->gravarLogDebug("Adicionando andamento para registro da recusa do trâmite", 4);
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

        //Sinaliza na PenProtocolo que o processo obteve recusa
        $this->gravarLogDebug("Atualizando protocolo sobre obtenção da ciência de recusa", 4);
        $objProtocolo = new PenProtocoloDTO();
        $objProtocolo->setDblIdProtocolo($objReceberTramiteRecusadoDTO->getNumIdProtocolo());
        $objProtocolo->setStrSinObteveRecusa('S');
        $objProtocoloBD = new ProtocoloBD($this->getObjInfraIBanco());
        $objProtocoloBD->alterar($objProtocolo);
    }


    /**
     * Método que realiza a validação da extensão dos componentes digitais a serem recebidos
     *
     * @param integer $parIdTramite
     * @param object $parObjProcesso
     * @throws InfraException
     */
    public function validarExtensaoComponentesDigitais($parIdTramite, $parObjProcesso){

        //Armazena o array de documentos
        $arrDocumentos = is_array($parObjProcesso->documento) ? $parObjProcesso->documento : array($parObjProcesso->documento) ;

        //Instancia o bd do arquivoExtensão
        $arquivoExtensaoBD = new ArquivoExtensaoBD($this->getObjInfraIBanco());

        //Percorre os documentos
        foreach($arrDocumentos as $documento){

            //Busca o nome do documento
            $nomeDocumento = $documento->componenteDigital->nome;

            //Busca pela extensão do documento
            $arrNomeDocumento = explode('.', $nomeDocumento);
            $extDocumento = $arrNomeDocumento[count($arrNomeDocumento) - 1];

            //Verifica se a extensão do arquivo está cadastrada e ativa
            $arquivoExtensaoDTO = new ArquivoExtensaoDTO();
            $arquivoExtensaoDTO->setStrSinAtivo('S');
            $arquivoExtensaoDTO->setStrExtensao($extDocumento);
            $arquivoExtensaoDTO->retStrExtensao();

            if($arquivoExtensaoBD->contar($arquivoExtensaoDTO) == 0){
                $this->objProcessoEletronicoRN->recusarTramite($parIdTramite, 'Componentes digitais com formato inválido no destinatário. ', ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_FORMATO);
                throw new InfraException("Processo recusado devido a existência de documento em formato {$extDocumento} não permitido pelo sistema. ");
            }
        }
    }

    /**
     * Método que verifica as permissões de escrita nos diretórios utilizados no recebimento de processos e documentos
     *
     * @param integer $parIdTramite
     * @throws InfraException
     */
    public function verificarPermissoesDiretorios($parIdTramite)
    {
        //Verifica se o usuário possui permissões de escrita no repositório de arquivos externos
        if(!is_writable(ConfiguracaoSEI::getInstance()->getValor('SEI', 'RepositorioArquivos'))) {
            $this->objProcessoEletronicoRN->recusarTramite($parIdTramite, 'O sistema não possui permissão de escrita no diretório de armazenamento de documentos externos', ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_OUTROU);
            throw new InfraException('O sistema não possui permissão de escrita no diretório de armazenamento de documentos externos');
        }

        //Verifica se o usuário possui permissões de escrita no diretório temporário de arquivos
        if(!is_writable(DIR_SEI_TEMP)){
            $this->objProcessoEletronicoRN->recusarTramite($parIdTramite, 'O sistema não possui permissão de escrita no diretório de armazenamento de arquivos temporários do sistema.', ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_OUTROU);
            throw new InfraException('O sistema não possui permissão de escrita no diretório de armazenamento de arquivos temporários do sistema.');

        }
    }

    private function sincronizarRecebimentoProcessos($parStrNumeroRegistro, $parNumIdentificacaoTramite)
    {
        $objProcedimentoAndamentoDTO = new ProcedimentoAndamentoDTO();
        $objProcedimentoAndamentoDTO->retDblIdAndamento();
        $objProcedimentoAndamentoDTO->setStrNumeroRegistro($parStrNumeroRegistro);
        $objProcedimentoAndamentoDTO->setDblIdTramite($parNumIdentificacaoTramite);
        $objProcedimentoAndamentoDTO->setNumTarefa(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO));
        $objProcedimentoAndamentoDTO->setNumMaxRegistrosRetorno(1);

        $objProcedimentoAndamentoBD = new ProcedimentoAndamentoBD($this->getObjInfraIBanco());
        $objProcedimentoAndamentoDTORet = $objProcedimentoAndamentoBD->consultar($objProcedimentoAndamentoDTO);

        $this->gravarLogDebug("Sincronizando o recebimento de processos concorrentes...", 4);
        $objProcedimentoAndamentoDTO = $objProcedimentoAndamentoBD->bloquear($objProcedimentoAndamentoDTORet) ? isset($objProcedimentoAndamentoDTORet) : false;
        $this->gravarLogDebug("Liberando processo concorrente de recebimento de processo ...", 4);
        return $objProcedimentoAndamentoDTO;
    }

    private function gravarLogDebug($strMensagem, $numIdentacao=0)
    {
        $strDataLog = date("d/m/Y H:i:s");
        $strLog = sprintf("[%s] [PROCESSAMENTO] %s %s", $strDataLog, str_repeat(" ", $numIdentacao * 4), $strMensagem);
        InfraDebug::getInstance()->gravar($strLog);
    }


    /**
     * Verifica se existe documentos com pendência de download de seus componentes digitais
     * @param  [type] $parNumIdProcedimento        Identificador do processo
     * @param  [type] $parNumIdDocumento           Identificador do documento
     * @param  [type] $parStrHashComponenteDigital Hash do componente digital
     * @return [type]                              Indicação se existe pendência ou não
     */
    private function documentosPendenteRegistro($parNumIdProcedimento, $parNumIdDocumento=null, $parStrHashComponenteDigital=null)
    {
        //Valida se algum documento ficou sem seus respectivos componentes digitais
        $sql = "select doc.id_documento as id_documento, comp.hash_conteudo as hash_conteudo
                from procedimento proc join documento doc on (doc.id_procedimento = proc.id_procedimento)
                                       join protocolo prot_doc on (doc.id_documento = prot_doc.id_protocolo)
                                       left join md_pen_componente_digital comp on (comp.id_documento = doc.id_documento)
                where proc.id_procedimento = $parNumIdProcedimento and prot_doc.sta_protocolo = 'R' and
                not exists (select 1 from anexo where anexo.id_protocolo = prot_doc.id_protocolo) ";

        //Adiciona filtro adicional para verificar pelo identificador do documento, caso parâmetro tenha sido informado
        if(!is_null($parNumIdDocumento)){
            $sql .= " and doc.id_documento = $parNumIdDocumento";
        }

        $recordset = $this->getObjInfraIBanco()->consultarSql($sql);

        $bolDocumentoPendente = !empty($recordset);

        //Verifica especificamente um determinado hash através da verificação do hash do componente, caso parâmetro tenha sido informado
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
     * Validação de pós condições para garantir que nenhuma inconsistência foi identificada no recebimento do processo
     *
     * @param  [type] $parObjMetadadosProcedimento Metadados do Protocolo
     * @param  [type] $parObjProcedimentoDTO       Dados do Processo gerado no recebimento
     */
    private function validarPosCondicoesTramite($parObjMetadadosProcedimento, $parObjProcedimentoDTO)
    {
        $strMensagemPadrao = "Inconsistência identificada no recebimento de processo: \n";
        $strMensagemErro = "";

        //Valida se metadados do trâmite e do protocolo foram identificado
        if(is_null($parObjMetadadosProcedimento)){
            $strMensagemErro = "- Metadados do trâmite não identificado. \n";
        }

        //Valida se metadados do trâmite e do protocolo foram identificado
        if(is_null($parObjProcedimentoDTO)){
            $strMensagemErro = "- Dados do processo não identificados \n";
        }

        //Valida se algum documento ficou sem seus respectivos componentes digitais
        if($this->documentosPendenteRegistro($parObjProcedimentoDTO->getDblIdProcedimento())){
            $strProtocoloFormatado = $parObjProcedimentoDTO->getStrProtocoloProcedimentoFormatado();
            $strMensagemErro = "- Componente digital de pelo menos um dos documentos do processo [$strProtocoloFormatado] não pode ser recebido. \n";
        }

        if(!InfraString::isBolVazia($strMensagemErro)){
            throw new InfraException($strMensagemPadrao . $strMensagemErro);
        }
    }
}
