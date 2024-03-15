<?php
require_once DIR_SEI_WEB.'/SEI.php';

class ReceberReciboTramiteRN extends InfraRN
{
    private $objProcessoEletronicoRN;
    private $objProcedimentoAndamentoRN;
    private $objPenDebug;
    private $objPenParametroRN;

  public function __construct()
    {
          parent::__construct();
          $this->objProcessoEletronicoRN = new ProcessoEletronicoRN();
          $this->objProcedimentoAndamentoRN = new ProcedimentoAndamentoRN();
          $this->objPenDebug = DebugPen::getInstance("PROCESSAMENTO");
          $this->objPenParametroRN = new PenParametroRN();
  }

  protected function inicializarObjInfraIBanco()
    {
      return BancoSEI::getInstance();
  }


  public function receberReciboDeTramite($parNumIdTramite)
    {
    try{
      if (!isset($parNumIdTramite)) {
        throw new InfraException('Par�metro $parNumIdTramite n�o informado.');
      }

        $this->objPenDebug->gravar("Solicitando recibo de conclus�o do tr�mite $parNumIdTramite");
        $objReciboTramite = $this->objProcessoEletronicoRN->receberReciboDeTramite($parNumIdTramite);

      if (!$objReciboTramite) {
          throw new InfraException("N�o foi poss�vel obter recibo de conclus�o do tr�mite '$parNumIdTramite'");
      }

        $objReciboTramite = $objReciboTramite->conteudoDoReciboDeTramite;

        // Inicializa��o do recebimento do processo, abrindo nova transa��o e controle de concorr�ncia,
        // evitando processamento simult�neo de cadastramento do mesmo processo
        $arrChavesSincronizacao = array(
            "NumeroRegistro" => $objReciboTramite->recibo->NRE,
            "IdTramite" => $objReciboTramite->recibo->IDT,
            "IdTarefa" => ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO)
        );

        if($this->objProcedimentoAndamentoRN->sinalizarInicioRecebimento($arrChavesSincronizacao)){
            $this->receberReciboDeTramiteInterno($objReciboTramite);
        }

    } catch(Exception $e) {
        $mensagemErro = InfraException::inspecionar($e);
        $this->objPenDebug->gravar($mensagemErro);
        LogSEI::getInstance()->gravar($mensagemErro);
        throw $e;
    }
  }


  protected function receberReciboDeTramiteInternoControlado($objReciboTramite)
    {
      //SessaoSEI::getInstance(false)->simularLogin('SEI', null, null, $this->objPenParametroRN->getParametro('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO'));
      ModPenUtilsRN::simularLoginUnidadeRecebimento();

      $strNumeroRegistro = $objReciboTramite->recibo->NRE;
      $numIdTramite = $objReciboTramite->recibo->IDT;
      $numIdTarefa = ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO);
      $objDateTime = new DateTime($objReciboTramite->recibo->dataDeRecebimento);

      // Tratamento para evitar o recebimento simult�neo de evento de conclus�o de tramite
    if(!$this->objProcedimentoAndamentoRN->sincronizarRecebimentoProcessos($strNumeroRegistro, $numIdTramite, $numIdTarefa)){
        $this->objPenDebug->gravar("Evento de conclus�o do tr�mite $numIdTramite j� se encontra em processamento", 3);
        return false;
    }

      $objReciboTramiteDTO = new ReciboTramiteDTO();
      $objReciboTramiteDTO->setStrNumeroRegistro($objReciboTramite->recibo->NRE);
      $objReciboTramiteDTO->setNumIdTramite($objReciboTramite->recibo->IDT);
      $objReciboTramiteDTO->setDthRecebimento($objDateTime->format('d/m/Y H:i:s'));
      $objReciboTramiteDTO->setStrCadeiaCertificado($objReciboTramite->cadeiaDoCertificado);
      $objReciboTramiteDTO->setStrHashAssinatura($objReciboTramite->hashDaAssinatura);

      //Verifica se o tr�mite do processo se encontra devidamente registrado no sistema
      $objTramiteDTO = new TramiteDTO();
      $objTramiteDTO->setNumIdTramite($numIdTramite);
      $objTramiteDTO->retNumIdUnidade();
      $objTramiteBD = new TramiteBD(BancoSEI::getInstance());

    if ($objTramiteBD->contar($objTramiteDTO) > 0) {
        $objTramiteDTO = $objTramiteBD->consultar($objTramiteDTO);
        SessaoSEI::getInstance(false)->simularLogin('SEI', null, null, $objTramiteDTO->getNumIdUnidade());

        $objReciboTramiteDTOExistente = new ReciboTramiteDTO();
        $objReciboTramiteDTOExistente->setNumIdTramite($numIdTramite);
        $objReciboTramiteDTOExistente->retNumIdTramite();

        $objReciboTramiteBD = new ReciboTramiteBD(BancoSEI::getInstance());
      if ($objReciboTramiteBD->contar($objReciboTramiteDTOExistente) == 0) {
          //Armazenar dados do recibo de conclus�o do tr�mite
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

        try {
            // Consulta pelo n�mero do tramite
            $objTramiteDTO = new TramiteDTO();
            $objTramiteDTO->setNumIdTramite($numIdTramite);
            $objTramiteDTO->retStrNumeroRegistro();

            $objTramiteBD = new TramiteBD(BancoSEI::getInstance());
            $objTramiteDTO = $objTramiteBD->consultar($objTramiteDTO);

            // Consulta o n�mero do registro
            $objProcessoEletronicoDTO = new ProcessoEletronicoDTO(BancoSEI::getInstance());
            $objProcessoEletronicoDTO->setStrNumeroRegistro($objTramiteDTO->getStrNumeroRegistro());
            $objProcessoEletronicoDTO->retDblIdProcedimento();

            $objProcessoEletronicoBD = new ProcessoEletronicoBD(BancoSEI::getInstance());
            $objProcessoEletronicoDTO = $objProcessoEletronicoBD->consultar($objProcessoEletronicoDTO);

            // Consulta pelo n�mero do procedimento
            $objProtocoloDTO = new ProtocoloDTO();
            $objProtocoloDTO->retTodos();
            $objProtocoloDTO->setDblIdProtocolo($objProcessoEletronicoDTO->getDblIdProcedimento());

            $objProtocoloBD = new ProtocoloBD(BancoSEI::getInstance());
            $objProtocoloDTO = $objProtocoloBD->consultar($objProtocoloDTO);

            $this->objProcedimentoAndamentoRN->setOpts($objTramiteDTO->getStrNumeroRegistro(), $numIdTramite, ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO), $objProcessoEletronicoDTO->getDblIdProcedimento());
            $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento(sprintf('Tr�mite do processo %s foi conclu�do', $objProtocoloDTO->getStrProtocoloFormatado()), 'S'));
            // Registra o recbimento do recibo no hist�rico e realiza a conclus�o do processo
            $this->registrarRecebimentoRecibo($objProtocoloDTO->getDblIdProtocolo(), $objProtocoloDTO->getStrProtocoloFormatado(), $numIdTramite);

        } catch (Exception $e) {
            $strMessage = 'Falha ao modificar o estado do procedimento para bloqueado.';
            LogSEI::getInstance()->gravar($strMessage . PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
            throw new InfraException($strMessage, $e);
        }
      }
    }
  }

  private function registrarRecebimentoRecibo($numIdProcedimento, $strProtocoloFormatado, $numIdTramite)
    {
        //REALIZA A CONCLUS�O DO PROCESSO
        $objEntradaConcluirProcessoAPI = new EntradaConcluirProcessoAPI();
        $objEntradaConcluirProcessoAPI->setIdProcedimento($numIdProcedimento);

        $objSeiRN = new SeiRN();
    try {
      $objSeiRN->concluirProcesso($objEntradaConcluirProcessoAPI);
    } catch (Exception $e) {
        //Registra falha em log de debug mas n�o gera rollback na transa��o.
        //O rollback da transa��o poderia deixar a situa��o do processo inconsist�nte j� que o Barramento registrou anteriormente que o
        //recibo j� havia sido obtido. O erro no fechamento n�o provoca impacto no andamento do processo
        $this->objPenDebug->gravar("Processo $strProtocoloFormatado n�o est� aberto na unidade.");
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
}
