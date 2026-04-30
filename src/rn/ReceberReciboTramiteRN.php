<?php
require_once DIR_SEI_WEB . '/SEI.php';

class ReceberReciboTramiteRN extends InfraRN
{
    private $objProcessoEletronicoRN;
    private $objProcedimentoAndamentoRN;
    private $objPenDebug;

  public function __construct()
    {
      parent::__construct();
      $this->objProcessoEletronicoRN = new ProcessoEletronicoRN();
      $this->objProcedimentoAndamentoRN = new ProcedimentoAndamentoRN();
      $this->objPenDebug = DebugPen::getInstance("PROCESSAMENTO");
  }

  protected function inicializarObjInfraIBanco()
    {
      return BancoSEI::getInstance();
  }


  public function receberReciboDeTramite($parNumIdTramite)
    {
    try {
      if (!isset($parNumIdTramite)) {
        throw new InfraException('Módulo do Tramita: Parâmetro $parNumIdTramite não informado.');
      }

        $this->objPenDebug->gravar("Solicitando recibo de conclusão do trâmite $parNumIdTramite");
        $objReciboTramite = $this->objProcessoEletronicoRN->receberReciboDeTramite($parNumIdTramite);

      if (!$objReciboTramite) {
          throw new InfraException("Módulo do Tramita: Não foi possível obter recibo de conclusão do trâmite '$parNumIdTramite'");
      }


        // Inicialização do recebimento do processo, abrindo nova transação e controle de concorrência,
        // evitando processamento simultâneo de cadastramento do mesmo processo
        $arrChavesSincronizacao = ["NumeroRegistro" => $objReciboTramite->recibo->NRE, "IdTramite" => $objReciboTramite->recibo->IDT, "IdTarefa" => ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO)];

      if ($this->objProcedimentoAndamentoRN->sinalizarInicioRecebimento($arrChavesSincronizacao)) {
          $this->receberReciboDeTramiteInterno($objReciboTramite);
      }
    } catch (Exception $e) {
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

      $this->objPenDebug->gravar("Recebimento ORG1");

      $strNumeroRegistro = $objReciboTramite->recibo->NRE;
      $numIdTramite = $objReciboTramite->recibo->IDT;
      $numIdTarefa = ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO);
      $objDateTime = new DateTime($objReciboTramite->recibo->dataDeRecebimento);

      // Tratamento para evitar o recebimento simultâneo de evento de conclusão de tramite
    if (!$this->objProcedimentoAndamentoRN->sincronizarRecebimentoProcessos($strNumeroRegistro, $numIdTramite, $numIdTarefa)) {
        $this->objPenDebug->gravar("Evento de conclusão do trâmite $numIdTramite já se encontra em processamento", 3);
        return false;
    }

      $objReciboTramiteDTO = new ReciboTramiteDTO();
      $objReciboTramiteDTO->setStrNumeroRegistro($objReciboTramite->recibo->NRE);
      $objReciboTramiteDTO->setNumIdTramite($objReciboTramite->recibo->IDT);
      $objReciboTramiteDTO->setDthRecebimento($objDateTime->format('d/m/Y H:i:s'));
      $objReciboTramiteDTO->setStrCadeiaCertificado($objReciboTramite->cadeiaDoCertificado);
      $objReciboTramiteDTO->setStrHashAssinatura($objReciboTramite->hashDaAssinatura);

      //Verifica se o trâmite do processo se encontra devidamente registrado no sistema
      $objTramiteDTO = new TramiteDTO();
      $objTramiteDTO->setNumIdTramite($numIdTramite);
      $objTramiteDTO->retNumIdUnidade();
      $objTramiteBD = new TramiteBD($this->inicializarObjInfraIBanco());

    if ($objTramiteBD->contar($objTramiteDTO) > 0) {
        $objTramiteDTO = $objTramiteBD->consultar($objTramiteDTO);
        SessaoSEI::getInstance(false)->simularLogin('SEI', null, null, $objTramiteDTO->getNumIdUnidade());

        $objReciboTramiteDTOExistente = new ReciboTramiteDTO();
        $objReciboTramiteDTOExistente->setNumIdTramite($numIdTramite);
        $objReciboTramiteDTOExistente->retNumIdTramite();

        $objReciboTramiteBD = new ReciboTramiteBD($this->inicializarObjInfraIBanco());
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

        try {
            // Consulta pelo número do tramite
            $objTramiteDTO = new TramiteDTO();
            $objTramiteDTO->setNumIdTramite($numIdTramite);
            $objTramiteDTO->retStrNumeroRegistro();

            $objTramiteBD = new TramiteBD($this->inicializarObjInfraIBanco());
            $objTramiteDTO = $objTramiteBD->consultar($objTramiteDTO);

            // Consulta o número do registro
            $objProcessoEletronicoDTO = new ProcessoEletronicoDTO($this->inicializarObjInfraIBanco());
            $objProcessoEletronicoDTO->setStrNumeroRegistro($objTramiteDTO->getStrNumeroRegistro());
            $objProcessoEletronicoDTO->retDblIdProcedimento();

            $objProcessoEletronicoBD = new ProcessoEletronicoBD($this->inicializarObjInfraIBanco());
            $objProcessoEletronicoDTO = $objProcessoEletronicoBD->consultar($objProcessoEletronicoDTO);

            // Consulta pelo número do procedimento
            $objProtocoloDTO = new ProtocoloDTO();
            $objProtocoloDTO->retTodos();
            $objProtocoloDTO->setDblIdProtocolo($objProcessoEletronicoDTO->getDblIdProcedimento());

            $objProtocoloBD = new ProtocoloBD($this->inicializarObjInfraIBanco());
            $objProtocoloDTO = $objProtocoloBD->consultar($objProtocoloDTO);

            // Atualizar Bloco para concluido
            $objPenBlocoProcessoDTO = new PenBlocoProcessoDTO();
            $objPenBlocoProcessoDTO->setDblIdProtocolo($objProtocoloDTO->getDblIdProtocolo());
            $objPenBlocoProcessoDTO->setNumIdAndamento(
                [ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CIENCIA_RECUSA, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO_AUTOMATICAMENTE, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO],
                InfraDTO::$OPER_NOT_IN
            );
            $objPenBlocoProcessoDTO->setOrdNumIdBlocoProcesso(InfraDTO::$TIPO_ORDENACAO_DESC);
            $objPenBlocoProcessoDTO->retTodos();

            $objPenBlocoProcessoRN = new PenBlocoProcessoRN();
            $arrPenBlocoProcesso = $objPenBlocoProcessoRN->listar($objPenBlocoProcessoDTO);

          if ($arrPenBlocoProcesso != null) {
            $blocos = [];
            foreach ($arrPenBlocoProcesso as $PenBlocoProcesso) {
                  $PenBlocoProcesso->setNumIdAndamento(ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE);
                  $objPenBlocoProcessoRN->alterar($PenBlocoProcesso);

                  $blocos[] = $PenBlocoProcesso->getNumIdBloco();
            }

            foreach ($blocos as $idBloco) {
                    $objPenBlocoProcessoRN->atualizarEstadoDoBloco($idBloco);
            }
          }

            $this->objProcedimentoAndamentoRN->setOpts($objTramiteDTO->getStrNumeroRegistro(), $numIdTramite, ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO), $objProcessoEletronicoDTO->getDblIdProcedimento());
            $this->objProcedimentoAndamentoRN->cadastrar(ProcedimentoAndamentoDTO::criarAndamento(sprintf('Trâmite do processo %s foi concluído', $objProtocoloDTO->getStrProtocoloFormatado()), 'S'));
            // Registra o recbimento do recibo no histórico e realiza a conclusão do processo
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
      //REALIZA A CONCLUSÃO DO PROCESSO
      $objEntradaConcluirProcessoAPI = new EntradaConcluirProcessoAPI();
      $objEntradaConcluirProcessoAPI->setIdProcedimento($numIdProcedimento);

      $objSeiRN = new SeiRN();
      $bolReproducaoUltimoTramite = false;
      
      $arrObjTramite = $this->objProcessoEletronicoRN->consultarTramites($numIdTramite);

      $objTramite = array_pop($arrObjTramite);
      
      $manterProcessoAberto = false;
      $objMetadados = $this->objProcessoEletronicoRN->solicitarMetadados($numIdTramite);
      // Verificar se o processo é de múltiplos órgãos
      $propriedadesAdicionais = isset($objMetadados->propriedadesAdicionais)
              ? ($objMetadados->propriedadesAdicionais ?: [])
              : [];
    if (in_array('multiplosOrgaos', array_column($propriedadesAdicionais, 'chave'))) {
      foreach ($propriedadesAdicionais as $valor) {
        if ($valor->chave === 'multiplosOrgaos' && $valor->valor === 'true') {
          $manterProcessoAberto = true;
          break;
        }
      }
    }

    try {
      if ($manterProcessoAberto == false) {
        $objSeiRN->concluirProcesso($objEntradaConcluirProcessoAPI);
      }
    } catch (Exception $e) {
        //Registra falha em log de debug mas não gera rollback na transação.
        //O rollback da transação poderia deixar a situação do processo inconsistênte já que o Barramento registrou anteriormente que o
        //recibo já havia sido obtido. O erro no fechamento não provoca impacto no andamento do processo
        $this->objPenDebug->gravar("Processo $strProtocoloFormatado não está aberto na unidade.");
        $bolReproducaoUltimoTramite = true; // Caso já esteja concluído é reprodução
    }

      $arrObjAtributoAndamentoDTO = [];

      $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
      $objAtributoAndamentoDTO->setStrNome('PROTOCOLO_FORMATADO');
      $objAtributoAndamentoDTO->setStrValor($strProtocoloFormatado);
      $objAtributoAndamentoDTO->setStrIdOrigem($numIdProcedimento);
      $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

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

    if (isset($objEstrutura->hierarquia)) {

        $arrObjNivel = $objEstrutura->hierarquia;

        $nome = "";
        $siglasUnidades = [];
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
        $objAtributoAndamentoDTO->setStrIdOrigem($objEstrutura->numeroDeIdentificacaoDaEstrutura);
        $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;
    }

      $objRepositorioDTO = $this->objProcessoEletronicoRN->consultarRepositoriosDeEstruturas($objTramite->destinatario->identificacaoDoRepositorioDeEstruturas);

    if (!empty($objRepositorioDTO)) {

        $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTO->setStrNome('REPOSITORIO_DESTINO');
        $objAtributoAndamentoDTO->setStrValor($objRepositorioDTO->getStrNome());
        $objAtributoAndamentoDTO->setStrIdOrigem($objRepositorioDTO->getNumId());
        $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;
    }

      $objProcessoExpedidoRN = new ProcessoExpedidoRN();
      $bolProcessoExpedido = $objProcessoExpedidoRN->existeProcessoExpedidoProtocolo($numIdProcedimento, ProtocoloRN::$TE_PROCEDIMENTO_BLOQUEADO);

      $idTarefa = ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_EXTERNO);
    if ($bolProcessoExpedido && $bolReproducaoUltimoTramite) { // Reprodução último trâmite
      $idTarefa = ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_REPRODUCAO_ULTIMO_TRAMITE_RECEBIDO);
    }
      $objAtividadeDTO = new AtividadeDTO();
      $objAtividadeDTO->setDblIdProtocolo($numIdProcedimento);
      $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
      $objAtividadeDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
      $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_EXTERNO));
      $objAtividadeDTO->setNumIdTarefa($idTarefa);
      $objAtividadeDTO->setArrObjAtributoAndamentoDTO($arrObjAtributoAndamentoDTO);

      $objAtividadeRN = new AtividadeRN();
      $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);

    if ($manterProcessoAberto == true) {
      $arrTiProcessoEletronico = [
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MULTIPLOS_ORGAOS),
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MULTIPLOS_ORGAOS_CONCLUIR),
        ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MANUAL_MULTIPLOS_ORGAOS)
      ];

      $objAtividadeDTO = new AtividadeDTO();
      $objAtividadeDTO->setDblIdProtocolo($numIdProcedimento);
      $objAtividadeDTO->setNumIdTarefa($arrTiProcessoEletronico, InfraDTO::$OPER_IN);
      $objAtividadeDTO->setOrdDthAbertura(InfraDTO::$TIPO_ORDENACAO_DESC);
      $objAtividadeDTO->setNumMaxRegistrosRetorno(1);
      $objAtividadeDTO->retNumIdAtividade();
      $objAtividadeDTO->retNumIdTarefa();
      
      $objAtividadeRN = new AtividadeRN();
      $objAtividadeDTO = $objAtividadeRN->consultarRN0033($objAtividadeDTO);

      if ($objAtividadeDTO == null) {
        try {
          $objReabrirProcessoDTO = new ReabrirProcessoDTO();
          $objReabrirProcessoDTO->setDblIdProcedimento($numIdProcedimento);
          $objReabrirProcessoDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
          $objReabrirProcessoDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());

          $objProcedimentoRN = new ProcedimentoRN();
          $objProcedimentoRN->reabrirRN0966($objReabrirProcessoDTO);
        } catch (\Throwable $th) {
          //throw $th;
        }
      }
    }
  }
}
