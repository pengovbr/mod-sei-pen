<?php
require_once dirname(__FILE__) . '/../../../SEI.php';

//TODO: Implementar validação sobre tamanho do documento a ser recebido (Parâmetros SEI)

class ReceberProcedimentoRN extends InfraRN
{
  const STR_APENSACAO_PROCEDIMENTOS = 'Relacionamento representando a apensação de processos recebidos externamente';

  private $objProcessoEletronicoRN;
  private $objInfraParametro;
  private $objProcedimentoAndamentoRN;
  private $documentosRetirados = array();

  public function __construct()
  {
    parent::__construct();

    $this->objInfraParametro = new InfraParametro(BancoSEI::getInstance());
    $this->objProcessoEletronicoRN = new ProcessoEletronicoRN();
    $this->objProcedimentoAndamentoRN = new ProcedimentoAndamentoRN();
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
  
    // TODO: Adicionar comandos de debug. Vide SeiWs.php gerarProcedimento
  protected function receberProcedimentoControlado($parNumIdentificacaoTramite)
  {     
    $objPenParametroRN = new PenParametroRN();  
    SessaoSEI::getInstance(false)->simularLogin('SEI', null, null, $objPenParametroRN->getParametro('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO')); 
    
    $objSeiRN = new SeiRN();
    
    error_log(__METHOD__.'('.$parNumIdentificacaoTramite.')');
      
    if (!isset($parNumIdentificacaoTramite)) {
      throw new InfraException('Parâmetro $parNumIdentificacaoTramite não informado.');
    }

    //TODO: Urgente: Verificar o status do trâmite e verificar se ele já foi salvo na base de dados
    $objMetadadosProcedimento = $this->objProcessoEletronicoRN->solicitarMetadados($parNumIdentificacaoTramite);

    if (isset($objMetadadosProcedimento)) {

      $strNumeroRegistro = $objMetadadosProcedimento->metadados->NRE;
      $objProcesso = $objMetadadosProcedimento->metadados->processo;

      //Verifica se processo já foi registrado para esse trâmite
      //TODO: Avaliar também processos apensados
      if($this->tramiteRegistrado($strNumeroRegistro, $parNumIdentificacaoTramite)) {
        return ;
      }
      
      // Validação dos dados do processo recebido
      $objInfraException = new InfraException();
      $this->validarDadosDestinatario($objInfraException, $objMetadadosProcedimento);
      $objInfraException->lancarValidacoes();
       
      #############################INICIA O RECEBIMENTO DOS COMPONENTES DIGITAIS US010################################################
      $arrObjTramite = $this->objProcessoEletronicoRN->consultarTramites($parNumIdentificacaoTramite);
      $objTramite = $arrObjTramite[0];
             
      //Obtém lista de componentes digitais que precisam ser obtidos
      if(!is_array($objTramite->componenteDigitalPendenteDeRecebimento)){
        $objTramite->componenteDigitalPendenteDeRecebimento = array($objTramite->componenteDigitalPendenteDeRecebimento);
      }

      //Faz a validação do tamanho e espécie dos componentes digitais 
      $this->validarComponentesDigitais($objProcesso, $parNumIdentificacaoTramite);
      
      //Faz a validação da extensão dos componentes digitais a serem recebidos
      $this->validarExtensaoComponentesDigitais($parNumIdentificacaoTramite, $objProcesso);
      
      //Faz a validação das permissões de leitura e escrita 
      $this->verificarPermissoesDiretorios($parNumIdentificacaoTramite);
      
      $arrStrNomeDocumento = $this->listarMetaDadosComponentesDigitais($objProcesso);
      
      //Instancia a RN que faz o recebimento dos componentes digitais
      $receberComponenteDigitalRN = new ReceberComponenteDigitalRN();

      //Cria o array que receberá os anexos após os arquivos físicos serem salvos
      $arrAnexosComponentes = array();
      
      //Cria o array com a lista de hash
      $arrayHash = array();
                    
      
     //Percorre os componentes que precisam ser recebidos
      foreach($objTramite->componenteDigitalPendenteDeRecebimento as $key => $componentePendente){
          
          if(!is_null($componentePendente)){
              
                //Adiciona o hash do componente digital ao array
                $arrayHash[] = $componentePendente;
                
                //Obter os dados do componente digital
                $objComponenteDigital = $this->objProcessoEletronicoRN->receberComponenteDigital($parNumIdentificacaoTramite, $componentePendente, $objTramite->protocolo);
                //Copia o componente para a pasta temporária
                $arrAnexosComponentes[$key][$componentePendente] = $receberComponenteDigitalRN->copiarComponenteDigitalPastaTemporaria($objComponenteDigital);
                $arrAnexosComponentes[$key]['recebido'] = false;
                
                //Valida a integridade do hash
                $receberComponenteDigitalRN->validarIntegridadeDoComponenteDigital($arrAnexosComponentes[$key][$componentePendente], $componentePendente, $parNumIdentificacaoTramite);
          }
      }
           
      if(count($arrAnexosComponentes) > 0){
          
            $receberComponenteDigitalRN->setArrAnexos($arrAnexosComponentes);
      }
      #############################TERMINA O RECEBIMENTO DOS COMPONENTES DIGITAIS US010################################################
      
      $arrObjTramite = $this->objProcessoEletronicoRN->consultarTramites($parNumIdentificacaoTramite);
      $objTramite = $arrObjTramite[0];
      
      //Verifica se o trâmite está recusado
      if($objTramite->situacaoAtual == ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO) {
            return;
      }
 
    $objProcedimentoDTO = $this->registrarProcesso($strNumeroRegistro, $parNumIdentificacaoTramite, $objProcesso, $objMetadadosProcedimento);

          

    
    
    // @join_tec US008.08 (#23092)
      $this->objProcedimentoAndamentoRN->setOpts($objProcedimentoDTO->getDblIdProcedimento(), $parNumIdentificacaoTramite, ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO));
      $this->objProcedimentoAndamentoRN->cadastrar('Obtendo metadados do processo', 'S');  

      //Verificar se procedimento já existia na base de dados do sistema
      //$dblIdProcedimento = $this->consultarProcedimentoExistente($strNumeroRegistro, $strProtocolo);

      //if(isset($dblIdProcedimento)){
      //TODO: Tratar situação em que o processo (NUP) já existia na base do sistema mas não havia nenhum NRE registrado para ele
      //  $objProcedimentoDTO = $this->atualizarProcedimento($dblIdProcedimento, $objMetadadosProcedimento, $objProcesso);                
      //}
      //else {            
                //TODO: Gerar Procedimento com status BLOQUEADO, aguardando o recebimento dos componentes digitais
      //  $objProcedimentoDTO = $this->gerarProcedimento($objMetadadosProcedimento, $objProcesso);
      //}

      //TODO: Fazer o envio de cada um dos procedimentos apensados (Processo principal e seus apensados, caso exista)
      //...        
      //TODO: Parei aqui!!! Recebimento de processos apensados
    
      $objProcessoEletronicoDTO = $this->objProcessoEletronicoRN->cadastrarTramiteDeProcesso($objProcedimentoDTO->getDblIdProcedimento(), 
        $strNumeroRegistro, $parNumIdentificacaoTramite, null, $objProcesso);
      

                  
      //TODO: Passar implementação para outra classe de negócio
      //Verifica se o tramite se encontra na situação correta 
      $arrObjTramite = $this->objProcessoEletronicoRN->consultarTramites($parNumIdentificacaoTramite);
      if(!isset($arrObjTramite) || count($arrObjTramite) != 1) {
        throw new InfraException("Trâmite não pode ser localizado pelo identificado $parNumIdentificacaoTramite.");
      }


      $objTramite = $arrObjTramite[0];
      

      if($objTramite->situacaoAtual != ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO) {
        return;
      }
      

            
    //  throw new InfraException("COMPONENTES DIGITAIS A SEREM ANEXADOS: ".var_export($arrayHash, true));
      if(count($arrayHash) > 0){
          
            //Obter dados dos componetes digitais            
            $objComponenteDigitalDTO = new ComponenteDigitalDTO();
            $objComponenteDigitalDTO->setStrNumeroRegistro($strNumeroRegistro);
            $objComponenteDigitalDTO->setNumIdTramite($parNumIdentificacaoTramite);
            $objComponenteDigitalDTO->setStrHashConteudo($arrayHash, InfraDTO::$OPER_IN);
            $objComponenteDigitalDTO->setOrdNumOrdem(InfraDTO::$TIPO_ORDENACAO_ASC);
            $objComponenteDigitalDTO->retDblIdDocumento();
            $objComponenteDigitalDTO->retNumTicketEnvioComponentes();
       //     $objComponenteDigitalDTO->retStrConteudoAssinaturaDocumento();
            $objComponenteDigitalDTO->retStrProtocoloDocumentoFormatado();
            $objComponenteDigitalDTO->retStrHashConteudo();
            $objComponenteDigitalDTO->retStrProtocolo();
            $objComponenteDigitalDTO->retStrNumeroRegistro();
            $objComponenteDigitalDTO->retNumIdTramite();
            $objComponenteDigitalDTO->retStrNome();
            $objComponenteDigitalDTO->retStrStaEstadoProtocolo();
            
            $objComponenteDigitalBD = new ComponenteDigitalBD($this->getObjInfraIBanco());
            $arrObjComponentesDigitaisDTO = $objComponenteDigitalBD->listar($objComponenteDigitalDTO);
                  
          //  throw new InfraException('Componentes encontrados: '.var_export($arrObjComponentesDigitaisDTO, true));
            
          if ($objComponenteDigitalBD->contar($objComponenteDigitalDTO) > 0) {
              
                  $objReceberComponenteDigitalRN = $receberComponenteDigitalRN;
                  
                  foreach($arrObjComponentesDigitaisDTO as $objComponenteDigitalDTOEnviado) {
                        if($objComponenteDigitalDTOEnviado->getStrStaEstadoProtocolo() != ProtocoloRN::$TE_DOCUMENTO_CANCELADO){
                             $strHash = $objComponenteDigitalDTOEnviado->getStrHashConteudo();                        
                             $strNomeDocumento = (array_key_exists($strHash, $arrStrNomeDocumento)) ? $arrStrNomeDocumento[$strHash]['especieNome'] : '[Desconhecido]';
                      
                             $objReceberComponenteDigitalRN->receberComponenteDigital($objComponenteDigitalDTOEnviado);

                             // @join_tec US008.09 (#23092)
                              $this->objProcedimentoAndamentoRN->cadastrar(sprintf('Recebendo %s %s', $strNomeDocumento, $objComponenteDigitalDTOEnviado->getStrProtocoloDocumentoFormatado()), 'S');
                        }
                       
                  }
                  // @join_tec US008.10 (#23092)
                $this->objProcedimentoAndamentoRN->cadastrar('Todos os componentes digitais foram recebidos', 'S');

            }else{
              $this->objProcedimentoAndamentoRN->cadastrar('Nenhum componente digital para receber', 'S');
            }                        
          }
    }
    
    //$this->fecharProcedimentoEmOutraUnidades($objProcedimentoDTO, $objMetadadosProcedimento);
   
    $objEnviarReciboTramiteRN = new EnviarReciboTramiteRN();
   $objEnviarReciboTramiteRN->enviarReciboTramiteProcesso($parNumIdentificacaoTramite, $arrayHash);
   
   $objPenTramiteProcessadoRN = new PenTramiteProcessadoRN(PenTramiteProcessadoRN::STR_TIPO_PROCESSO);
   $objPenTramiteProcessadoRN->setRecebido($parNumIdentificacaoTramite);
   
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
                $this->objProcessoEletronicoRN->recusarTramite($parNumIdentificacaoTramite, sprintf('Documento do tipo %s não está mapeado', $objDocument->especie->nomeNoProdutor), ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_ESPECIE_NAO_MAPEADA);
                throw new InfraException(sprintf('Documento do tipo %s não está mapeado. Motivo da Recusa no Barramento: %s', $objDocument->especie->nomeNoProdutor, ProcessoEletronicoRN::$MOTIVOS_RECUSA[ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_ESPECIE_NAO_MAPEADA]));
            } 
        }


        $objInfraParametro = new InfraParametro(BancoSEI::getInstance());
        $numTamDocExterno = $objInfraParametro->getValor('SEI_TAM_MB_DOC_EXTERNO');


        foreach($arrObjDocumentos as $objDocument) {


            if (is_null($objDocument->componenteDigital->tamanhoEmBytes) || $objDocument->componenteDigital->tamanhoEmBytes == 0){  
                
                throw new InfraException('Tamanho de componente digital não informado.', null, 'RECUSA: '.ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_OUTROU);
                
            }

            if($objDocument->componenteDigital->tamanhoEmBytes > ($numTamDocExterno * 1024 * 1024)){

                $numTamanhoMb = $objDocument->componenteDigital->tamanhoEmBytes / ( 1024 * 1024);
                $this->objProcessoEletronicoRN->recusarTramite($parNumIdentificacaoTramite, 'Componente digital não pode ultrapassar '.$numTamDocExterno.', o tamanho do anexo é '.$numTamanhoMb.' .', ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_OUTROU);
                throw new InfraException('Componente digital não pode ultrapassar '.$numTamDocExterno.', o tamanho do anexo é '.$numTamanhoMb);

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

    if(isset($dblIdProcedimento)){
         
     //TODO: Tratar situação em que o processo (NUP) já existia na base do sistema mas não havia nenhum NRE registrado para ele
      $objProcedimentoDTO = $this->atualizarProcedimento($dblIdProcedimento, $parObjMetadadosProcedimento, $parObjProcesso);                
    }
    else {            
       
      //TODO: Gerar Procedimento com status BLOQUEADO, aguardando o recebimento dos componentes digitais
      $objProcedimentoDTO = $this->gerarProcedimento($parObjMetadadosProcedimento, $parObjProcesso);
    }

    //TODO: Fazer o envio de cada um dos procedimentos apensados (Processo principal e seus apensados, caso exista)
    //...        

    //Chamada recursiva para registro dos processos apensados
    if(isset($objProcesso->processoApensado)) {
      if(!is_array($objProcesso->processoApensado)) {
        $objProcesso->processoApensado = array($objProcesso->processoApensado);
      }

      foreach ($objProcesso->processoApensado as $objProcessoApensado) {
        $this->registrarProcesso($parStrNumeroRegistro, $parNumIdentificacaoTramite, $objProcessoApensado, $parObjMetadadosProcedimento);
      }                
    }

    return $objProcedimentoDTO;
  }

  private function tramiteRegistrado($parStrNumeroRegistro, $parNumIdentificacaoTramite) {

    $objTramiteDTO = new TramiteDTO();
    $objTramiteDTO->setStrNumeroRegistro($parStrNumeroRegistro);
    $objTramiteDTO->setNumIdTramite($parNumIdentificacaoTramite);

    $objTramiteBD = new TramiteBD($this->getObjInfraIBanco());
    return $objTramiteBD->contar($objTramiteDTO) > 0;
  }

  private function consultarProcedimentoExistente($parStrNumeroRegistro, $parStrProtocolo = null) {

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

 private function atualizarProcedimento($parDblIdProcedimento, $objMetadadosProcedimento, $objProcesso){
   
    
    if(!isset($parDblIdProcedimento)){
      throw new InfraException('Parâmetro $parDblIdProcedimento não informado.');
    }        

    if(!isset($objMetadadosProcedimento)){
      throw new InfraException('Parâmetro $objMetadadosProcedimento não informado.');
    }
    
    $objDestinatario = $objMetadadosProcedimento->metadados->destinatario;

        //TODO: Refatorar código para criar método de pesquisa do procedimento e reutilizá-la

        //$objProcedimentoDTO = new ProcedimentoDTO();
        //$objProcedimentoDTO->setDblIdProcedimento($parDblIdProcedimento);
        //$objProcedimentoDTO->retTodos();
        //$objProcedimentoDTO->retStrProtocoloProcedimentoFormatado();        
        //$objProcedimentoDTO->setStrSinDocTodos('S');
    
        //$objProcedimentoRN = new ProcedimentoRN();
        //$arrObjProcedimentoDTO = $objProcedimentoRN->listarCompleto($objProcedimentoDTO);

        //if(count($arrObjProcedimentoDTO) == 0){
        //    throw new InfraException('Processo não pode ser localizado. ('.$parDblIdProcedimento.')');
        //}

        //$objProcedimentoDTO = $arrObjProcedimentoDTO[0];
     
        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setStrIdTarefaModuloTarefa(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO);
        $objAtividadeDTO->setDblIdProcedimentoProtocolo($parDblIdProcedimento);
        $objAtividadeDTO->setOrd('Conclusao', InfraDTO::$TIPO_ORDENACAO_DESC);
        $objAtividadeDTO->setNumMaxRegistrosRetorno(1);
        $objAtividadeDTO->retNumIdUnidade();
        
        $objAtividadeRN = new AtividadeRN();
        $arrObjAtividadeDTO = $objAtividadeRN->listarRN0036($objAtividadeDTO);
        $numIdUnidade = SessaoSEI::getInstance()->getNumIdUnidadeAtual();
        
        if($arrObjAtividadeDTO){
            $objAtividadeDTO = $arrObjAtividadeDTO[0];
            $numIdUnidade = $objAtividadeDTO->getNumIdUnidade();
        }
        
        
        $objSeiRN = new SeiRN();
    
        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->retDthConclusao();
        $objAtividadeDTO->setDblIdProtocolo($parDblIdProcedimento);
        $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());

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
        
        $this->registrarAndamentoRecebimentoProcesso($objProcedimentoDTO, $objMetadadosProcedimento);
        
        
      if($flgReabrir){
            $objEntradaReabrirProcessoAPI = new EntradaReabrirProcessoAPI();
            $objEntradaReabrirProcessoAPI->setIdProcedimento($parDblIdProcedimento);
            $objSeiRN->reabrirProcesso($objEntradaReabrirProcessoAPI);
      }

     
      
    $objEntradaDesbloquearProcessoAPI = new EntradaDesbloquearProcessoAPI();
    $objEntradaDesbloquearProcessoAPI->setIdProcedimento($parDblIdProcedimento);    
    $objSeiRN->desbloquearProcesso($objEntradaDesbloquearProcessoAPI);
   
       //TODO: Obter código da unidade através de mapeamento entre SEI e Barramento
    $objUnidadeDTO = $this->atribuirDadosUnidade($objProcedimentoDTO, $objDestinatario);

    $this->atribuirDocumentos($objProcedimentoDTO, $objProcesso, $objUnidadeDTO, $objMetadadosProcedimento); 
    $this->registrarProcedimentoNaoVisualizado($objProcedimentoDTO);

        //TODO: Avaliar necessidade de restringir referência circular entre processos
        //TODO: Registrar que o processo foi recebido com outros apensados. Necessário para posterior reenvio
    $this->atribuirProcessosApensados($objProcedimentoDTO, $objProcesso->processoApensado);

        //TODO: Finalizar o envio do documento para a respectiva unidade
    $this->enviarProcedimentoUnidade($objProcedimentoDTO, true);

        //TODO: Avaliar necessidade de criar acesso externo para o processo recebido
        //TODO: Avaliar necessidade de tal recurso
        //FeedSEIProtocolos::getInstance()->setBolAcumularFeeds(false);
        //FeedSEIProtocolos::getInstance()->indexarFeeds();

        //InfraDebug::getInstance()->gravar('RETORNO:'.print_r($ret,true));
        //LogSEI::getInstance()->gravar(InfraDebug::getInstance()->getStrDebug());

    $this->removerAndamentosProcedimento($objProcedimentoDTO);
    return $objProcedimentoDTO;


  }

  private function gerarProcedimento($objMetadadosProcedimento, $objProcesso){

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
        $numIdHipoteseLegal = $objHipoteseLegalRecebido->getIdHipoteseLegalSEI($objProcesso->hipoteseLegal);
        if (empty($numIdHipoteseLegal)) {
            $objPenParametroRN = new PenParametroRN();
            $objProtocoloDTO->setNumIdHipoteseLegal($objPenParametroRN->getParametro('HIPOTESE_LEGAL_PADRAO'));
        } else {
            $objProtocoloDTO->setNumIdHipoteseLegal($numIdHipoteseLegal);
        }
    }
    
    $objProtocoloDTO->setStrProtocoloFormatado(utf8_decode($objProcesso->protocolo));
    $objProtocoloDTO->setDtaGeracao($this->objProcessoEletronicoRN->converterDataSEI($objProcesso->dataHoraDeProducao));
    $objProtocoloDTO->setArrObjAnexoDTO(array());
    $objProtocoloDTO->setArrObjRelProtocoloAssuntoDTO(array());
    $objProtocoloDTO->setArrObjRelProtocoloProtocoloDTO(array());
    //$objProtocoloDTO->setStrStaEstado(ProtocoloRN::$TE_BLOQUEADO);
    $this->atribuirRemetente($objProtocoloDTO, $objRemetente);
    $this->atribuirParticipantes($objProtocoloDTO, $objProcesso->interessado);
     
  
    
    $strDescricao  = sprintf('Tipo de processo no órgão de origem: %s', utf8_decode($objProcesso->processoDeNegocio)).PHP_EOL;
    $strDescricao .= $objProcesso->observacao;
    
    $objObservacaoDTO  = new ObservacaoDTO();
    $objObservacaoDTO->setStrDescricao($strDescricao);
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

        //TODO: Finalizar o envio do documento para a respectiva unidade
        $this->enviarProcedimentoUnidade($objProcedimentoDTO);

        //TODO: Avaliar necessidade de criar acesso externo para o processo recebido
        //TODO: Avaliar necessidade de tal recurso
        //FeedSEIProtocolos::getInstance()->setBolAcumularFeeds(false);
        //FeedSEIProtocolos::getInstance()->indexarFeeds();

        //InfraDebug::getInstance()->gravar('RETORNO:'.print_r($ret,true));
        //LogSEI::getInstance()->gravar(InfraDebug::getInstance()->getStrDebug());

        $this->removerAndamentosProcedimento($objProcedimentoDTO);
        return $objProcedimentoDTO;
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

      protected function atribuirDadosUnidade(ProcedimentoDTO $objProcedimentoDTO, $objDestinatario){

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
        $objComponenteDigitalDTO->retDblIdDocumento();
        $objComponenteDigitalDTO->retStrHashConteudo();
        $objComponenteDigitalDTO->setStrNumeroRegistro($strNumeroRegistro);
        $objComponenteDigitalDTO->setOrdNumOrdem(InfraDTO::$TIPO_ORDENACAO_ASC);

        $objComponenteDigitalBD = new ComponenteDigitalBD($this->getObjInfraIBanco());
        $arrObjComponenteDigitalDTO = $objComponenteDigitalBD->listar($objComponenteDigitalDTO);
        $arrObjComponenteDigitalDTOIndexado = InfraArray::indexarArrInfraDTO($arrObjComponenteDigitalDTO, "Ordem");
       // $arrStrHashConteudo = InfraArray::converterArrInfraDTO($arrObjComponenteDigitalDTO, 'IdDocumento', 'HashConteudo');

        $objProtocoloBD = new ProtocoloBD($this->getObjInfraIBanco());
        $objSeiRN = new SeiRN();
		
        $arrObjDocumentoDTO = array();
        
        foreach($arrObjDocumentos as $objDocumento){
            
            // @join_tec US027 (#3498)
           if(isset($objDocumento->retirado) && $objDocumento->retirado === true) {

                //$strHashConteudo = ProcessoEletronicoRN::getHashFromMetaDados($objDocumento->componenteDigital->hash);
                
                // Caso já esteja cadastrado, de um reenvio anterior, então move para bloqueado
                if(array_key_exists($objDocumento->ordem, $arrObjComponenteDigitalDTOIndexado)) {
                    
                    //Busca o ID do protocolo
                    //$dblIdProtocolo = $arrStrHashConteudo[$strHashConteudo];
                    $objComponenteIndexado = $arrObjComponenteDigitalDTOIndexado[$objDocumento->ordem];
                    $dblIdProtocolo = $objComponenteIndexado->getDblIdDocumento();
                    
                    //Instancia o DTO do protocolo
                    $objProtocoloDTO = new ProtocoloDTO();
                    $objProtocoloDTO->setDblIdProtocolo($dblIdProtocolo);
                    $objProtocoloDTO->retStrStaEstado();
                    
                    $objProtocoloDTO = $objProtocoloBD->consultar($objProtocoloDTO);
                    
                    if($objProtocoloDTO->getStrStaEstado() != ProtocoloRN::$TE_DOCUMENTO_CANCELADO){
                        //Instancia o DTO do protocolo
                       $objEntradaCancelarDocumentoAPI = new EntradaCancelarDocumentoAPI();
                       $objEntradaCancelarDocumentoAPI->setIdDocumento($dblIdProtocolo);
                       $objEntradaCancelarDocumentoAPI->setMotivo('Cancelado pelo remetente');

                       $objSeiRN->cancelarDocumento($objEntradaCancelarDocumentoAPI);
          
                    }
 
                    
                    continue;
            
                }
                //continue;
            }

            if(array_key_exists($objDocumento->ordem, $arrObjComponenteDigitalDTOIndexado)){
                continue;
            }

            //Validação dos dados dos documentos
          if(!isset($objDocumento->especie)){
            throw new InfraException('Espécie do documento ['.$objDocumento->descricao.'] não informada.');
          }
          
//---------------------------------------------------------------------------------------------------            

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
         // $objDocumentoDTO->setNumVersaoLock(0);

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
            $objAtividadeRN = new AtividadeRN();
            if ($objAtividadeRN->contarRN0035($objAtividadeDTO) == 0) {
              throw new InfraException('Unidade '.$objUnidadeDTO->getStrSigla().' não possui acesso ao Procedimento '.$objProcedimentoDTO->getStrProtocoloProcedimentoFormatado().'.');
            }

            $objAtividadeDTO = new AtividadeDTO();
            $objAtividadeDTO->setDblIdProtocolo($objDocumentoDTO->getDblIdProcedimento());
            $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
            $objAtividadeDTO->setDthConclusao(null);

            if ($objAtividadeRN->contarRN0035($objAtividadeDTO) == 0) {
                    //reabertura automática
              $objReabrirProcessoDTO = new ReabrirProcessoDTO();
              $objReabrirProcessoDTO->setDblIdProcedimento($objDocumentoDTO->getDblIdProcedimento());
              $objReabrirProcessoDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
              $objReabrirProcessoDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
              $objProcedimentoRN->reabrirRN0966($objReabrirProcessoDTO);
              $bolReabriuAutomaticamente = true;
            }             
          }
          
            //$objOperacaoServicoDTO = new OperacaoServicoDTO();
            //$this->adicionarCriteriosUnidadeProcessoDocumento ($objOperacaoServicoDTO,$objUnidadeDTO,$objProcedimentoDTO,$objDocumentoDTO);
            //$objOperacaoServicoDTO->setNumStaOperacaoServico(OperacaoServicoRN::$TS_INCLUIR_DOCUMENTO);
            //$objOperacaoServicoDTO->setNumIdServico($objServicoDTO->getNumIdServico());

            //$objOperacaoServicoRN = new OperacaoServicoRN();
            //if ($objOperacaoServicoRN->contar($objOperacaoServicoDTO)==0){
            //    $objInfraException->lancarValidacao('Nenhuma operação configurada para inclusão de documento do Tipo ['.$objSerieDTO->getStrNome().'] no Tipo de Processo ['.$objProcedimentoDTO->getStrNomeTipoProcedimento().'] na Unidade ['.$objUnidadeDTO->getStrSigla().'] pelo Serviço ['.$objServicoDTO->getStrIdentificacao().'] do Sistema ['.$objServicoDTO->getStrSiglaUsuario().'].');
            //}

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
            $numIdHipoteseLegal = $objHipoteseLegalRecebido->getIdHipoteseLegalSEI($objDocumento->hipoteseLegal);
            if (empty($numIdHipoteseLegal)) {
                $objPenParametroRN = new PenParametroRN();
                $objDocumentoDTO->getObjProtocoloDTO()->setNumIdHipoteseLegal($objPenParametroRN->getParametro('HIPOTESE_LEGAL_PADRAO'));
            } else {
                $objDocumentoDTO->getObjProtocoloDTO()->setNumIdHipoteseLegal($numIdHipoteseLegal);
            }
          }
          
          $objDocumentoDTO->getObjProtocoloDTO()->setArrObjParticipanteDTO($this->prepararParticipantes($objDocumentoDTO->getObjProtocoloDTO()->getArrObjParticipanteDTO()));

          $objDocumentoRN = new DocumentoRN();

          $strConteudoCodificado = $objDocumentoDTO->getStrConteudo();
          $objDocumentoDTO->setStrConteudo(null);
          //$objDocumentoDTO->setStrSinFormulario('N');
          
          $objDocumentoDTO->getObjProtocoloDTO()->setNumIdUnidadeGeradora(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
          $objDocumentoDTO->setStrSinBloqueado('S');
            
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
        
      /*  if($numIdUnidadeAtual != $numIdUnidadeGeradora){
            SessaoSEI::getInstance(false)->simularLogin('SEI', null, null, $numIdUnidadeAtual);   
        } */
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

      private function atribuirAssunto(ProtocoloDTO $objProtocoloDTO, $numIdAssunto)
      {
        //TODO: Removido. Serão utilizados os tipos de procedimento enviados atribuídos ao tipo de processo externo (PEN_TIPO_PROCESSO_EXTERNO)
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

      private function bloquearProcedimento($objProcesso){

      }

      private function atribuirDataHoraDeRegistro(){

      }    

      private function cadastrarTramiteDeProcesso($objTramite, $objProcesso){

      }

      private function validarDadosDestinatario(InfraException $objInfraException, $objMetadadosProcedimento){

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
          $objInfraException->adicionarValidacao("Unidade de destino [Estrutura: XXXX] não localizada.");
          $objInfraException->adicionarValidacao("Dados: {$numeroDeIdentificacaoDaEstrutura}");
          
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

      /**
       * 
       * @return SerieDTO
       */
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

      private function enviarProcedimentoUnidade(ProcedimentoDTO $parObjProcedimentoDTO, $retransmissao = false) 
      {
        $objAtividadeRN = new PenAtividadeRN();
        $objInfraException = new InfraException();

        if(!$parObjProcedimentoDTO->isSetArrObjUnidadeDTO() || count($parObjProcedimentoDTO->getArrObjUnidadeDTO()) == 0) {
          $objInfraException->lancarValidacao('Unidade de destino do processo não informada.');            
        }

        $arrObjUnidadeDTO = $parObjProcedimentoDTO->getArrObjUnidadeDTO();

        if(count($parObjProcedimentoDTO->getArrObjUnidadeDTO()) > 1) {
          $objInfraException->lancarValidacao('Não permitido a indicação de múltiplas unidades de destino para um processo recebido externamente.');
        }

        $arrObjUnidadeDTO = array_values($parObjProcedimentoDTO->getArrObjUnidadeDTO());
        $objUnidadeDTO = $arrObjUnidadeDTO[0];

        $objProcedimentoDTO = new ProcedimentoDTO();
        $objProcedimentoDTO->retDblIdProcedimento();
        $objProcedimentoDTO->retNumIdTipoProcedimento();
        $objProcedimentoDTO->retStrProtocoloProcedimentoFormatado();
        $objProcedimentoDTO->retNumIdTipoProcedimento();
        $objProcedimentoDTO->retStrNomeTipoProcedimento();
        $objProcedimentoDTO->retStrStaNivelAcessoGlobalProtocolo();
//        $objProcedimentoDTO->retStrStaEstadoProtocolo();
        $objProcedimentoDTO->setStrProtocoloProcedimentoFormatado($parObjProcedimentoDTO->getStrProtocoloProcedimentoFormatado());

        $objProcedimentoRN = new ProcedimentoRN();
        $objProcedimentoDTO = $objProcedimentoRN->consultarRN0201($objProcedimentoDTO);

        if ($objProcedimentoDTO == null || $objProcedimentoDTO->getStrStaNivelAcessoGlobalProtocolo()==ProtocoloRN::$NA_SIGILOSO) {
          $objInfraException->lancarValidacao('Processo ['.$parObjProcedimentoDTO->getStrProtocoloProcedimentoFormatado().'] não encontrado.');
        }

        if ($objProcedimentoDTO->getStrStaNivelAcessoGlobalProtocolo()==ProtocoloRN::$NA_RESTRITO) {
          $objAcessoDTO = new AcessoDTO();
          $objAcessoDTO->setDblIdProtocolo($objProcedimentoDTO->getDblIdProcedimento());
          $objAcessoDTO->setNumIdUnidade($objUnidadeDTO->getNumIdUnidade());

          $objAcessoRN = new AcessoRN();
          if ($objAcessoRN->contar($objAcessoDTO)==0) {
            $objInfraException->adicionarValidacao('Unidade ['.$objUnidadeDTO->getStrSigla().'] não possui acesso ao processo ['.$objProcedimentoDTO->getStrProtocoloProcedimentoFormatado().'].');
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
          $objReabrirProcessoDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
          $objReabrirProcessoDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
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
        $objAtividadeDTO->setNumIdUnidade($objUnidadeDTO->getNumIdUnidade());
        $objAtividadeDTO->setNumIdUnidadeOrigem(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
        $objEnviarProcessoDTO->setArrAtividades(array($objAtividadeDTO));    
        
        $objPenParametroRN = new PenParametroRN();
        
        $objEnviarProcessoDTO->setStrSinManterAberto('N');
        $strEnviaEmailNotificacao = $objPenParametroRN->getParametro('PEN_ENVIA_EMAIL_NOTIFICACAO_RECEBIMENTO');
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
      
      
    public function receberTramitesRecusados($parNumIdentificacaoTramite) {

        if (empty($parNumIdentificacaoTramite)) {
            throw new InfraException('Parâmetro $parNumIdentificacaoTramite não informado.');
        }
        
        //Busca os dados do trâmite no barramento
        $tramite = $this->objProcessoEletronicoRN->consultarTramites($parNumIdentificacaoTramite);
        
        if(!isset($tramite[0])){
            throw new InfraException("Não foi encontrado o trâmite de número {$parNumIdentificacaoTramite} para realizar a ciência da recusa");
        }
        
        $tramite = $tramite[0];
        
        $objTramiteDTO = new TramiteDTO();
        $objTramiteDTO->setNumIdTramite($parNumIdentificacaoTramite);
        $objTramiteDTO->retNumIdUnidade();
        
        $objTramiteBD = new TramiteBD(BancoSEI::getInstance());
        $objTramiteDTO = $objTramiteBD->consultar($objTramiteDTO);
        
        SessaoSEI::getInstance(false)->simularLogin('SEI', null, null, $objTramiteDTO->getNumIdUnidade());
        
        //Busca os dados do procedimento
        $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
        $objProcessoEletronicoDTO->setStrNumeroRegistro($tramite->NRE);
        $objProcessoEletronicoDTO->retDblIdProcedimento();

        $objProcessoEletronicoBD = new ProcessoEletronicoBD($this->getObjInfraIBanco());
        $objProcessoEletronicoDTO = $objProcessoEletronicoBD->consultar($objProcessoEletronicoDTO);
        
        //Busca a última atividade de expedição
        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDblIdProtocolo($objProcessoEletronicoDTO->getDblIdProcedimento());
        $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO));
        $objAtividadeDTO->setNumMaxRegistrosRetorno(1);
        $objAtividadeDTO->setOrdDthAbertura(InfraDTO::$TIPO_ORDENACAO_DESC);
        $objAtividadeDTO->retNumIdAtividade();

        $objAtividadeBD = new AtividadeBD($this->getObjInfraIBanco());
        $objAtividadeDTO = $objAtividadeBD->consultar($objAtividadeDTO);
        
        //Busca a unidade de destino
        $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTO->setNumIdAtividade($objAtividadeDTO->getNumIdAtividade());
        $objAtributoAndamentoDTO->setStrNome('UNIDADE_DESTINO');
        $objAtributoAndamentoDTO->retStrValor();

        $objAtributoAndamentoBD = new AtributoAndamentoBD($this->getObjInfraIBanco());
        $objAtributoAndamentoDTO = $objAtributoAndamentoBD->consultar($objAtributoAndamentoDTO);
        
        //Monta o DTO de receber tramite recusado
        $objReceberTramiteRecusadoDTO = new ReceberTramiteRecusadoDTO();
        $objReceberTramiteRecusadoDTO->setNumIdTramite($parNumIdentificacaoTramite);
        $objReceberTramiteRecusadoDTO->setNumIdProtocolo($objProcessoEletronicoDTO->getDblIdProcedimento());
        $objReceberTramiteRecusadoDTO->setNumIdUnidadeOrigem(null);
        $objReceberTramiteRecusadoDTO->setNumIdTarefa(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_RECUSADO));
        $objReceberTramiteRecusadoDTO->setStrMotivoRecusa(ProcessoEletronicoRN::$MOTIVOS_RECUSA[$tramite->motivoDaRecusa]);
        $objReceberTramiteRecusadoDTO->setStrNomeUnidadeDestino($objAtributoAndamentoDTO->getStrValor());
        
        //Faz o tratamento do processo e do trâmite recusado
        $this->receberTramiteRecusadoInterno($objReceberTramiteRecusadoDTO);
        
        
    }

    protected function receberTramiteRecusadoInternoControlado(ReceberTramiteRecusadoDTO $objReceberTramiteRecusadoDTO){
        
        
        //Realiza o desbloqueio do processo
        $objEntradaDesbloquearProcessoAPI = new EntradaDesbloquearProcessoAPI();
        $objEntradaDesbloquearProcessoAPI->setIdProcedimento($objReceberTramiteRecusadoDTO->getNumIdProtocolo());
        
        $objSeiRN = new SeiRN();
        $objSeiRN->desbloquearProcesso($objEntradaDesbloquearProcessoAPI);
        
        //Adiciona um andamento para o trâmite recusado
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
        $objProtocolo = new PenProtocoloDTO();
        $objProtocolo->setDblIdProtocolo($objReceberTramiteRecusadoDTO->getNumIdProtocolo());
        $objProtocolo->setStrSinObteveRecusa('S');
        
        $objProtocoloBD = new ProtocoloBD($this->getObjInfraIBanco());
        $objProtocoloBD->alterar($objProtocolo);
        
        
        $this->objProcessoEletronicoRN->cienciaRecusa($objReceberTramiteRecusadoDTO->getNumIdTramite());
        

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
    public function verificarPermissoesDiretorios($parIdTramite){
        
        //Verifica se o usuário possui permissões de escrita no repositório de arquivos externos
        if(!is_writable(ConfiguracaoSEI::getInstance()->getValor('SEI', 'RepositorioArquivos'))){
            
            $this->objProcessoEletronicoRN->recusarTramite($parIdTramite, 'O sistema não possui permissão de escrita no diretório de armazenamento de documentos externos', ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_OUTROU);
            throw new InfraException('O sistema não possui permissão de escrita no diretório de armazenamento de documentos externos');
            
        }
        
        //Verifica se o usuário possui permissões de escrita no diretório temporário de arquivos
        if(!is_writable(DIR_SEI_TEMP)){
            
            $this->objProcessoEletronicoRN->recusarTramite($parIdTramite, 'O sistema não possui permissão de escrita no diretório de armazenamento de arquivos temporários do sistema.', ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_OUTROU);
            throw new InfraException('O sistema não possui permissão de escrita no diretório de armazenamento de arquivos temporários do sistema.');
            
        }
        
        
    }
}
