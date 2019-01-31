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

  protected function registrarRecebimentoRecibo($numIdProcedimento, $strProtocoloFormatado, $numIdTramite)
  {
        //REALIZA A CONCLUSÃO DO PROCESSO
        $objEntradaConcluirProcessoAPI = new EntradaConcluirProcessoAPI();
        $objEntradaConcluirProcessoAPI->setIdProcedimento($numIdProcedimento);

        $objSeiRN = new SeiRN();
        try {
            $objSeiRN->concluirProcesso($objEntradaConcluirProcessoAPI);
        } catch (Exception $e) {
            //Registra falha em log de debug mas não gera rollback na transação.
            //O rollback da transação poderia deixar a situação do processo inconsistênte já que o Barramento registrou anteriormente que o
            //recibo já havia sido obtido. O erro no fechamento não provoca impacto no andamento do processo
            InfraDebug::getInstance()->gravar("Processo $strProtocoloFormatado não está aberto na unidade.");
        }

        $arrObjAtributoAndamentoDTO = array();

        $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTO->setStrNome('PROTOCOLO_FORMATADO');
        $objAtributoAndamentoDTO->setStrValor($strProtocoloFormatado);
        $objAtributoAndamentoDTO->setStrIdOrigem($numIdProcedimento);
        $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

        $arrObjTramite = $this->objProcessoEletronicoRN->consultarTramites($numIdTramite);

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

        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDblIdProtocolo($numIdProcedimento);
        $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
        $objAtividadeDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
        $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_EXTERNO));
        $objAtividadeDTO->setArrObjAtributoAndamentoDTO($arrObjAtributoAndamentoDTO);

        $objAtividadeRN = new AtividadeRN();
        $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);

    }

    protected function receberReciboDeTramiteControlado($parNumIdTramite)
    {
        if (!isset($parNumIdTramite)) {
            throw new InfraException('Parâmetro $parNumIdTramite não informado.');
        }

        $objReciboTramite = $this->objProcessoEletronicoRN->receberReciboDeTramite($parNumIdTramite);

        if (!$objReciboTramite) {
            throw new InfraException("Não foi possível obter recibo de conclusão do trâmite '$parNumIdTramite'");
        }

        $objReciboTramite = $objReciboTramite->conteudoDoReciboDeTramite;
        $objDateTime = new DateTime($objReciboTramite->recibo->dataDeRecebimento);

        $objReciboTramiteDTO = new ReciboTramiteDTO();
        $objReciboTramiteDTO->setStrNumeroRegistro($objReciboTramite->recibo->NRE);
        $objReciboTramiteDTO->setNumIdTramite($objReciboTramite->recibo->IDT);
        $objReciboTramiteDTO->setDthRecebimento($objDateTime->format('d/m/Y H:i:s'));
        $objReciboTramiteDTO->setStrCadeiaCertificado($objReciboTramite->cadeiaDoCertificado);
        $objReciboTramiteDTO->setStrHashAssinatura($objReciboTramite->hashDaAssinatura);

        //Verifica se o trâmite do processo se encontra devidamente registrado no sistema
        $objTramiteDTO = new TramiteDTO();
        $objTramiteDTO->setNumIdTramite($parNumIdTramite);
        $objTramiteDTO->retNumIdUnidade();

        $objTramiteBD = new TramiteBD(BancoSEI::getInstance());

        if ($objTramiteBD->contar($objTramiteDTO) > 0) {


            $objTramiteDTO = $objTramiteBD->consultar($objTramiteDTO);
            SessaoSEI::getInstance(false)->simularLogin('SEI', null, null, $objTramiteDTO->getNumIdUnidade());

            $objReciboTramiteDTOExistente = new ReciboTramiteDTO();
            $objReciboTramiteDTOExistente->setNumIdTramite($parNumIdTramite);
            $objReciboTramiteDTOExistente->retNumIdTramite();

            $objReciboTramiteBD = new ReciboTramiteBD(BancoSEI::getInstance());
            if ($objReciboTramiteBD->contar($objReciboTramiteDTOExistente) == 0) {
                //Armazenar dados do recibo de conclusão do trãmite
                $objReciboTramiteBD->cadastrar($objReciboTramiteDTO);
                 if ($objReciboTramite->recibo->hashDoComponenteDigital && is_array($objReciboTramite->recibo->hashDoComponenteDigital)) {
                    foreach ($objReciboTramite->recibo->hashDoComponenteDigital as $strHashComponenteDigital) {
                        $objReciboTramiteHashDTO = new ReciboTramiteHashDTO();
                        $objReciboTramiteHashDTO->setStrNumeroRegistro($objReciboTramite->recibo->NRE);
                        $objReciboTramiteHashDTO->setNumIdTramite($objReciboTramite->recibo->IDT);
                        $objReciboTramiteHashDTO->setStrHashComponenteDigital($strHashComponenteDigital);
                        $objReciboTramiteHashDTO->setStrTipoRecibo(ProcessoEletronicoRN::$STA_TIPO_RECIBO_CONCLUSAO_RECEBIDO);

                        $objGenericoBD = new GenericoBD($this->getObjInfraIBanco());
                        $objGenericoBD->cadastrar($objReciboTramiteHashDTO);
                    }
                }
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

                    $this->objProcedimentoAndamentoRN->setOpts($objProcessoEletronicoDTO->getDblIdProcedimento(), $parNumIdTramite, ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO));
                    $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento(sprintf('Trâmite do processo %s foi concluído', $objProtocoloDTO->getStrProtocoloFormatado()), 'S'));
                    //Registra o recbimento do recibo no histórico e realiza a conclusão do processo
                    $this->registrarRecebimentoRecibo($objProtocoloDTO->getDblIdProtocolo(), $objProtocoloDTO->getStrProtocoloFormatado(), $parNumIdTramite);
                    $objPenTramiteProcessadoRN = new PenTramiteProcessadoRN(PenTramiteProcessadoRN::STR_TIPO_RECIBO);
                    $objPenTramiteProcessadoRN->setRecebido($parNumIdTramite);

                } catch (Exception $e) {
                    $strMessage = 'Falha ao modificar o estado do procedimento para bloqueado.';
                    LogSEI::getInstance()->gravar($strMessage . PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
                    throw new InfraException($strMessage, $e);
                }
            }
        }


    }
}
