<?php
require_once dirname(__FILE__) . '/../../../SEI.php';

class ReceberReciboTramiteRN extends InfraRN
{
  private $objProcessoEletronicoRN;
  private $objInfraParametro;
  private $objProcedimentoAndamentoRN;

  public function __construct()
  {
    parent::__construct();

    $this->objProcessoEletronicoRN = new ProcessoEletronicoRN();
    $this->objProcedimentoAndamentoRN = new ProcedimentoAndamentoRN();
  }

  protected function inicializarObjInfraIBanco()
  {
    return BancoSEI::getInstance();
  }
  
    protected function mudarEstadoProcedimentoBloqueado($objProcesso) {
        
        $objProtocoloDTO = new ProtocoloDTO();    	
        $objProtocoloDTO->setStrStaEstado(ProtocoloRN::$TE_BLOQUEADO);
        $objProtocoloDTO->setDblIdProtocolo($objProcesso->idProcedimentoSEI);    	
            
        $objProtocoloRN = new ProtocoloRN();
        $objProtocoloRN->alterarRN0203($objProtocoloDTO);

        $arrObjAtributoAndamentoDTO = array();

        $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTO->setStrNome('PROTOCOLO_FORMATADO');
        $objAtributoAndamentoDTO->setStrValor($objProcesso->strProtocoloFormatado);
        $objAtributoAndamentoDTO->setStrIdOrigem($objProcesso->idProcedimentoSEI);
        $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

        $arrObjTramite = $this->objProcessoEletronicoRN->consultarTramites($objProcesso->idTramite);
        
        $objTramite = array_pop($arrObjTramite);
        
        $objEstrutura = $this->objProcessoEletronicoRN->consultarEstrutura(
            $objTramite->destinatario->identificacaoDoRepositorioDeEstruturas, 
            $objTramite->destinatario->numeroDeIdentificacaoDaEstrutura, 
            true
        );
        
        $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTO->setStrNome('UNIDADE_DESTINO');
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
            $objAtributoAndamentoDTO->setStrNome('UNIDADE_DESTINO_HIRARQUIA');
            $objAtributoAndamentoDTO->setStrValor($nome);
            $objAtributoAndamentoDTO->setStrIdOrigem($objNivel->numeroDeIdentificacaoDaEstrutura);
            $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;
            
        }
        
        $objRepositorioDTO = $this->objProcessoEletronicoRN->consultarRepositoriosDeEstruturas($objTramite->destinatario->identificacaoDoRepositorioDeEstruturas);
        if(!empty($objRepositorioDTO)) {
        
            $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
            $objAtributoAndamentoDTO->setStrNome('REPOSITORIO_DESTINO');
            $objAtributoAndamentoDTO->setStrValor($objRepositorioDTO->getStrNome());
            $objAtributoAndamentoDTO->setStrIdOrigem($objRepositorioDTO->getNumId());
            $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;
        }
        
        // Tramitação externa do processo @processo@ concluída com sucesso. Recebido na @UnidadeDestino@ - @hierarquia_superior@ -@repositório_de_estruturas@
        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDblIdProtocolo($objProcesso->idProcedimentoSEI);
        $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
        $objAtividadeDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
        $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_EXTERNO);
        $objAtividadeDTO->setArrObjAtributoAndamentoDTO($arrObjAtributoAndamentoDTO);

        $objAtividadeRN = new AtividadeRN();
        $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);
    }

  protected function receberReciboDeTramiteConectado($parNumIdTramite) {
      
        if (!isset($parNumIdTramite)) {
            throw new InfraException('Parâmetro $parNumIdTramite não informado.');
        }

        $objReciboTramiteDTO = $this->objProcessoEletronicoRN->receberReciboDeTramite($parNumIdTramite);
        
        if (!isset($objReciboTramiteDTO)) {
            throw new InfraException("Não foi possível obter recibo de conclusão do trâmite '$parNumIdTramite'");
        }

        //Verifica se o trâmite do processo se encontra devidamente registrado no sistema
        $objTramiteDTO = new TramiteDTO();
        $objTramiteDTO->setNumIdTramite($parNumIdTramite);
        $objTramiteBD = new TramiteBD(BancoSEI::getInstance());

        if ($objTramiteBD->contar($objTramiteDTO) > 0) {

            $objReciboTramiteDTOExistente = new ReciboTramiteDTO();
            $objReciboTramiteDTOExistente->setNumIdTramite($parNumIdTramite);
            $objReciboTramiteDTOExistente->retNumIdTramite();

            $objReciboTramiteBD = new ReciboTramiteBD(BancoSEI::getInstance());
            if ($objReciboTramiteBD->contar($objReciboTramiteDTOExistente) == 0) {
                
                //Armazenar dados do recibo de conclusão do trãmite      
                $objReciboTramiteBD->cadastrar($objReciboTramiteDTO);

                //ALTERA O ESTADO DO PROCEDIMENTO
                try {

                    // Consulta pelo número do tramite
                    $objTramiteDTO = new TramiteDTO();
                    $objTramiteDTO->setNumIdTramite($parNumIdTramite);
                    $objTramiteDTO->retStrNumeroRegistro();

                    $objTramiteBD = new TramiteBD(BancoSEI::getInstance());
                    $objTramiteDTO = $objTramiteBD->consultar($objTramiteDTO);

                    // Consulta o número do registro
                    $objProcessoEletronicoDTO = new ProcessoEletronicoDTO(BancoSEI::getInstance());
                    $objProcessoEletronicoDTO->setStrNumeroRegistro($objTramiteDTO->getStrNumeroRegistro());
                    $objProcessoEletronicoDTO->retDblIdProcedimento();

                    $objProcessoEletronicoBD = new ProcessoEletronicoBD(BancoSEI::getInstance());
                    $objProcessoEletronicoDTO = $objProcessoEletronicoBD->consultar($objProcessoEletronicoDTO);

                    // Consulta pelo número do procedimento
                    $objProtocoloDTO = new ProtocoloDTO();
                    $objProtocoloDTO->retTodos();
                    $objProtocoloDTO->setDblIdProtocolo($objProcessoEletronicoDTO->getDblIdProcedimento());

                    $objProtocoloBD = new ProtocoloBD(BancoSEI::getInstance());
                    $objProtocoloDTO = $objProtocoloBD->consultar($objProtocoloDTO);

                    //$objProtocoloDTO->setStrStaEstado(ProtocoloRN::$TE_BLOQUEADO);
                    //$objProtocoloBD->alterar($objProtocoloDTO);
                    // @join_tec US008.12 (#23092)
                    $this->objProcedimentoAndamentoRN->setOpts($objProcessoEletronicoDTO->getDblIdProcedimento(), $parNumIdTramite, ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO);
                    $this->objProcedimentoAndamentoRN->cadastrar(sprintf('Trâmite do processo %s foi concluído', $objProtocoloDTO->getStrProtocoloFormatado()), 'S');

                    $objProcesso = new stdClass();
                    $objProcesso->idProcedimentoSEI = $objProtocoloDTO->getDblIdProtocolo();
                    $objProcesso->strProtocoloFormatado = $objProtocoloDTO->getStrProtocoloFormatado();
                    $objProcesso->idTramite = $parNumIdTramite;
                    
                    $this->mudarEstadoProcedimentoBloqueado($objProcesso);
                    
                } catch (Exception $e) {

                    $strMessage = 'Falha o modificar o estado do procedimento para bloqueado.';

                    LogSEI::getInstance()->gravar($strMessage . PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
                    throw new InfraException($strMessage, $e);
                }
            }
        }
        
        $objPenTramiteProcessadoRN = new PenTramiteProcessadoRN(PenTramiteProcessadoRN::STR_TIPO_RECIBO);
        $objPenTramiteProcessadoRN->setRecebido($parNumIdTramite);
    }
}