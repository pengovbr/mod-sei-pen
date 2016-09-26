<?php 
require_once dirname(__FILE__) . '/../../../SEI.php';

class EnviarReciboTramiteRN extends InfraRN
{
  private $objProcessoEletronicoRN;
  private $objInfraParametro;

  public function __construct()
  {
    parent::__construct();

    $this->objInfraParametro = new InfraParametro(BancoSEI::getInstance());
    $this->objProcessoEletronicoRN = new ProcessoEletronicoRN();
  }

  protected function inicializarObjInfraIBanco()
  {
    return BancoSEI::getInstance();
  }
  
    /**
     * Gera o recibo do tramite para o destinário informando o recebimento
     * do procedimento.
     * 
     * @param int $numIdTramite
     * @return array
     */
    protected function gerarReciboTramite($numIdTramite){
    
        $arrStrHashConteudo = array();
        
        $objMetaRetorno = $this->objProcessoEletronicoRN->solicitarMetadados($numIdTramite);
        
        $objMetaProcesso = $objMetaRetorno->metadados->processo;

        $arrObjMetaDocumento = is_array($objMetaProcesso->documento) ? $objMetaProcesso->documento : array($objMetaProcesso->documento);
        
        $objDTO = new ComponenteDigitalDTO();
        $objBD = new ComponenteDigitalBD($this->inicializarObjInfraIBanco());
        
        foreach($arrObjMetaDocumento as $objMetaDocumento) {
            
            $strHashConteudo = ProcessoEletronicoRN::getHashFromMetaDados($objMetaDocumento->componenteDigital->hash);
            
            $objDTO->setStrHashConteudo($strHashConteudo);
            
            if($objBD->contar($objDTO) > 0) {
                
                $arrStrHashConteudo[] = $strHashConteudo;
            }
        }
        
        return $arrStrHashConteudo;
    }
    
    protected function cadastrarReciboTramiteRecebimento($strNumeroRegistro = '', $parNumIdTramite = 0, $strHashConteudo = ''){
      
        $objBD = new ReciboTramiteRecebidoBD($this->inicializarObjInfraIBanco());
      
        $objDTO = new ReciboTramiteRecebidoDTO();
        $objDTO->setStrNumeroRegistro($strNumeroRegistro);
        $objDTO->setNumIdTramite($parNumIdTramite);
        
        if(!empty($strHashConteudo)) $objDTO->setStrHashAssinatura($strHashConteudo); 

        if(intval($objBD->contar($objDTO)) == 0) {
            
            $objDTO->setDthRecebimento(date('d/m/Y H:i:s'));
            $objBD->cadastrar($objDTO);
        }
    }

  public function enviarReciboTramiteProcesso($parNumIdTramite, $parArrayHash = null, $parDthRecebimento = null)
  {        

    date_default_timezone_set('America/Sao_Paulo');
    //$parametro->dadosDoReciboDeTramite->dataDeRecebimento = date('Y-m-d\TH:i:s.000P');

    if(!isset($parNumIdTramite) || $parNumIdTramite == 0) {
      throw new InfraException('Parâmetro $parNumIdTramite não informado.');            
    }            

    //TODO: Reavaliar validação
    //Verifica se todos os componentes digitais já foram devidamente recebido
    $arrObjTramite = $this->objProcessoEletronicoRN->consultarTramites($parNumIdTramite);
    if(!isset($arrObjTramite) || count($arrObjTramite) != 1) {
      throw new InfraException("Trâmite não pode ser localizado pelo identificador $parNumIdTramite.");
    }

    $objTramite = $arrObjTramite[0];
    $strNumeroRegistro = $objTramite->NRE;

    if($objTramite->situacaoAtual != ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO) {
      return;
    }
    
    


    //TODO: Verificar necessidade de dessa validação
    //Verificar se todos os componentes digitais foram obtidos e validados
    //$objComponenteDigitalDTO = new ComponenteDigitalDTO();
    //$objComponenteDigitalDTO->setNumIdTramite($parNumIdTramite);
    //$objComponenteDigitalDTO->setStrNumeroRegistro($strNumeroRegistro);
    //$objComponenteDigitalDTO->setNumIdAnexo(null);

    //$objComponenteDigitalBD = new ComponenteDigitalBD($this->getObjInfraIBanco());
    //if($objComponenteDigitalBD->contar($objComponenteDigitalDTO) > 0) {
    //  throw new InfraException("Pendente obtenção de componentes digitais do trâmite $parNumIdTramite.");
    //}

    //TODO: Tratar data correta para envio do recibo de trâmite
    //A data atual gera erros no barramento devido a falta de sincronização entre o relógio do servidor e o relógio do barramento
    //Avaliar a possibilidade de utilizar a data em que foi realizado o último download. ConsultarTramite - Situação 04

    //if(!isset($parDthRecebimento)) {
    //  $parDthRecebimento = InfraData::getStrDataHoraAtual();
    //}

    //TODO: Analisar necessidade do tratamento de datas abaixo já que todos os servidores que integrarem ao PEN deverão estar sincronizados 
    //com o 
    $dthRecebimentoComponentesDigitais = $this->obterDataRecebimentoComponentesDigitais($objTramite);    
    $dthRecebimentoComponentesDigitais = $dthRecebimentoComponentesDigitais ?: date();
    $dthRecebimento = gmdate("Y-m-d\TH:i:s.000\Z", InfraData::getTimestamp($dthRecebimentoComponentesDigitais));

//    if($parArrObjComponenteDigitalDTO == null) { 
//      $parArrObjComponenteDigitalDTO = $this->listarComponenteDigitalDTO($parNumIdTramite);
//    }
//    $arrHashConteudo = InfraArray::converterArrInfraDTO($parArrObjComponenteDigitalDTO, 'HashConteudo', null);
//    sort($arrHashConteudo);
//
//    // @join_tec US008.11 (#23092)
//    if(empty($arrHashConteudo)) {
//        // Caso de retrasmição os componentes digitais não são reenviados, então
//        // não é gravado o recibo. Adicionamos alguma possição no array para 
//        // gravar pelo menos um recibo.
//        $arrHashConteudo[] = '';
//    }   
    
    //Busca os metadados do processo
   /* $objMetaRetorno = $this->objProcessoEletronicoRN->solicitarMetadados($numIdTramite);
    
    //Obtem os dados necessários para consultar os trâmites relacionados a unidade destinatário e ao processo recebido    
    $parProtocolo = $objMetaRetorno->metadados->processo->protocolo;
    $parNumeroUnidadeDestino = $objMetaRetorno->metadados->destinatario->numeroDeIdentificacaoDaEstrutura;
    $parNumeroRepositorioEstruturas = $objMetaRetorno->metadados->destinatario->identificacaoDoRepositorioDeEstruturas;
        
    //Consulta os trâmites relacionados
    $arrObjTramites = new $this->objProcessoEletronicoRN->consultarTramites($parNumIdTramite, null, null, $parNumeroUnidadeDestino, $parProtocolo, $parNumeroRepositorioEstruturas);
    $arrObjTramites = is_array($arrObjTramites->tramitesEncontrados->tramite) ? $arrObjTramites->tramitesEncontrados->tramite : array($arrObjTramites->tramitesEncontrados->tramite); 
    
    $arrTramitesAnteriores = array();
    
    foreach($arrObjTramite as $tramiteAntigo){
        if($tramiteAntigo->IDT != $parNumIdTramite){
            $arrTramitesAnteriores[] = $tramiteAntigo;
        }
    }
    
    $arrHashTramitesAnteriores = array();
    
    foreach($arrTramitesAnteriores as $tramiteAntigo){
        
        //Solicita os metadados desse trâmite
        $objMetaTramiteAntigo = $this->objProcessoEletronicoRN->solicitarMetadados($tramiteAntigo);
        
        //Transforma os documento em um array
        $arrDocumentosTramiteAntigo = is_array($objMetaTramiteAntigo->metadados->processo->documento) ? $objMetaTramiteAntigo->metadados->processo->documento : array($objMetaTramiteAntigo->metadados->processo->documento);
        
        
    }
    
    
    */
    
    /*
     $arrStrHashConteudo = array();
        
        $objMetaRetorno = $this->objProcessoEletronicoRN->solicitarMetadados($numIdTramite);
        
        $objMetaProcesso = $objMetaRetorno->metadados->processo;

        $arrObjMetaDocumento = is_array($objMetaProcesso->documento) ? $objMetaProcesso->documento : array($objMetaProcesso->documento);
        
        $objDTO = new ComponenteDigitalDTO();
        $objBD = new ComponenteDigitalBD($this->inicializarObjInfraIBanco());
        
        foreach($arrObjMetaDocumento as $objMetaDocumento) {
            
            $strHashConteudo = ProcessoEletronicoRN::getHashFromMetaDados($objMetaDocumento->componenteDigital->hash);
            
            $objDTO->setStrHashConteudo($strHashConteudo);
            
            if($objBD->contar($objDTO) > 0) {
                
                $arrStrHashConteudo[] = $strHashConteudo;
            }
        }
        
        return $arrStrHashConteudo;*/
    
    //$arrHashConteudo = $this->gerarReciboTramite($parNumIdTramite);
    
   /* foreach($parArrayHash as $strHashConteudo) {
        
        // @join_tec R003 S001 US036 (#3869)
        $this->cadastrarReciboTramiteRecebimento($strNumeroRegistro, $parNumIdTramite, $strHashConteudo);
    }*/
    
    $strReciboTramite  = "<recibo>";
    $strReciboTramite .= "<IDT>$parNumIdTramite</IDT>";
    $strReciboTramite .= "<NRE>$strNumeroRegistro</NRE>";
    $strReciboTramite .= "<dataDeRecebimento>$dthRecebimento</dataDeRecebimento>";
    sort($parArrayHash);
    
    foreach ($parArrayHash as $strHashConteudo) {
      if(!empty($strHashConteudo)){
            $strReciboTramite .= "<hashDoComponenteDigital>$strHashConteudo</hashDoComponenteDigital>";
      }
    }        
    $strReciboTramite  .= "</recibo>";
        
    
    $this->objProcessoEletronicoRN->enviarReciboDeTramite($parNumIdTramite, $dthRecebimento, $strReciboTramite);
  }    

  private function obterDataRecebimentoComponentesDigitais($parObjTramite){
    
    if(!isset($parObjTramite)) {
      throw new InfraException('Parâmetro $parObjTramite não informado.');            
    }

    if(!is_array($parObjTramite->historico->operacao)) {
      $parObjTramite->historico->operacao = array($parObjTramite->historico->operacao);
    }

    foreach ($parObjTramite->historico->operacao as $operacao) {
      if($operacao->situacao == ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO) {
        return ProcessoEletronicoRN::converterDataSEI($operacao->dataHora); 
      }
    }

    return null;
  }

    /**
     * Consulta o componente digital no barramento. Utilizado para casos de retrasmissão,
     * onde esta unidade esta recebendo um componente digital que pertence à ela
     * própria, então o id_tramite de envio, que foi gravado, é diferente do de recebimento
     * 
     * @param int $numIdTramite
     * @return array[ComponenteDigitalDTO]
     */
    private function recarregarComponenteDigitalDTO($numIdTramite){
        
        $arrObjComponenteDigitalDTO = array();
        
        $objMetaRetorno = $this->objProcessoEletronicoRN->solicitarMetadados($numIdTramite);

        if(!empty($objMetaRetorno)) {

            $objMetaProcesso = $objMetaRetorno->metadados->processo;

            $arrObjMetaDocumento = is_array($objMetaProcesso->documento) ? $objMetaProcesso->documento : array($objMetaProcesso->documento);
            
            foreach($arrObjMetaDocumento as $objMetaDocumento) {

                $dblIdProcedimento = null;
                $dblIdDocumento = null;
                
                $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
                $objProcessoEletronicoDTO->setStrNumeroRegistro($objMetaRetorno->metadados->NRE);
                $objProcessoEletronicoDTO->retDblIdProcedimento();
                
                $objProcessoEletronicoBD = new ProcessoEletronicoBD($this->getObjInfraIBanco());
                $objProcessoEletronicoDTO = $objProcessoEletronicoBD->consultar($objProcessoEletronicoDTO);
                
                if(empty($objProcessoEletronicoDTO)) {
                    
                    $dblIdProcedimento = $objProcessoEletronicoDTO->getDblIdProcedimento();
                    
                    $objDocumentoDTO = new DocumentoDTO();
                    $objDocumentoDTO->setDblIdProcedimento($dblIdProcedimento);
                    $objDocumentoDTO->retDblIdDocumento();
                    
                    $objDocumentoBD = new DocumentoBD();
                    $objDocumentoDTO = $objDocumentoBD->consultar($objDocumentoDTO);
                    
                    if(empty($objDocumentoDTO)) {
                        
                        $dblIdDocumento = $objDocumentoDTO->getDblIdDocumento();
                    }                    
                }

                $objMetaComponenteDigital = $objMetaDocumento->componenteDigital;
                
                $objComponenteDigitalDTO = new ComponenteDigitalDTO();
                $objComponenteDigitalDTO->setStrNumeroRegistro($objMetaRetorno->metadados->NRE);
                $objComponenteDigitalDTO->setDblIdProcedimento($dblIdProcedimento);
                $objComponenteDigitalDTO->setDblIdDocumento($dblIdDocumento);
                $objComponenteDigitalDTO->setNumIdTramite($numIdTramite);
                $objComponenteDigitalDTO->setNumIdAnexo($objMetaComponenteDigital->idAnexo);
                $objComponenteDigitalDTO->setStrNome($objMetaComponenteDigital->nome);
                $objComponenteDigitalDTO->setStrHashConteudo(ProcessoEletronicoRN::getHashFromMetaDados($objMetaComponenteDigital->hash));
                $objComponenteDigitalDTO->setStrProtocolo($objMetaProcesso->protocolo);
                $objComponenteDigitalDTO->setStrAlgoritmoHash(ProcessoEletronicoRN::ALGORITMO_HASH_DOCUMENTO);
                $objComponenteDigitalDTO->setStrTipoConteudo($objMetaComponenteDigital->tipoDeConteudo);
                $objComponenteDigitalDTO->setStrMimeType($objMetaComponenteDigital->mimeType);
                $objComponenteDigitalDTO->setStrDadosComplementares($objMetaComponenteDigital->dadosComplementaresDoTipoDeArquivo);
                $objComponenteDigitalDTO->setNumTamanho($objMetaComponenteDigital->tamanhoEmBytes);
                $objComponenteDigitalDTO->setNumOrdem($objMetaDocumento->ordem);
                $objComponenteDigitalDTO->setStrSinEnviar('S');
                
                $arrObjComponenteDigitalDTO[] = $objComponenteDigitalDTO;
            }   
        } 
        
        return $arrObjComponenteDigitalDTO;
    }
  
    /**
     */
    private function listarComponenteDigitalDTO($parNumIdTramite) {
        
        $objComponenteDigitalDTO = new ComponenteDigitalDTO();
        $objComponenteDigitalDTO->retTodos();
        $objComponenteDigitalDTO->setNumIdTramite($parNumIdTramite);
        
        $objComponenteDigitalBD = new ComponenteDigitalBD($this->getObjInfraIBanco());
        $arrObjComponenteDigitalDTO = $objComponenteDigitalBD->listar($objComponenteDigitalDTO);

        if (empty($arrObjComponenteDigitalDTO)) {
         
            //$arrObjComponenteDigitalDTO = $this->recarregarComponenteDigitalDTO($parNumIdTramite); 
        }

        return $arrObjComponenteDigitalDTO;
    }
}