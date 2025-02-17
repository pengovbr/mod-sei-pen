<?php

require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Regra de negócio para o parâmetros do módulo PEN
 */
class PenBlocoProcessoRN extends InfraRN
{
    /**
     * Inicializa o obj do banco da Infra
     *
     * @return obj
     */
  protected function inicializarObjInfraIBanco()
    {
      return BancoSEI::getInstance();
  }

    /**
     * Verifica se o bloco pode ser excluído
     *
     * @return string|null
     */
  public function verificarExclusaoBloco(array $arrObjDTO)
    {
      $podeExcluir = true;
      $messagem = "Existem protocolos em andamento que não pode ser excluídos.";

    foreach ($arrObjDTO as $objPenBlocoProcessoDTO) {
        $objPenBlocoProcessoDTO->retNumIdBlocoProcesso();
        $objPenBlocoProcessoDTO->retNumIdAndamento();
        $objPenBlocoProcessoDTO->retStrProtocoloFormatadoProtocolo();
        $objPenBlocoProcessoDTO->setNumMaxRegistrosRetorno(1);

        $objPenBlocoProcessoBD = new PenBlocoProcessoBD($this->getObjInfraIBanco());
        $objPenBlocoProcessoDTO = $objPenBlocoProcessoBD->consultar($objPenBlocoProcessoDTO);

      if ($objPenBlocoProcessoDTO != null
            && $objPenBlocoProcessoDTO->getNumIdAndamento() !== null
        ) {
        $messagem .= "\n - {$objPenBlocoProcessoDTO->getStrProtocoloFormatadoProtocolo()}";
        $podeExcluir = false;
      }
    }

    if (!$podeExcluir) {
        return $messagem;
    }

      return null;
  }

  protected function obterPendenciasBlocoControlado(PenBlocoProcessoDTO $objPenBlocoProcessoDTO)
    {
    try {

        //Valida PermissãoTipo
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_expedir_bloco', __METHOD__, $objPenBlocoProcessoDTO);

        //Obter todos os processos pendentes antes de iniciar o monitoramento
        $arrObjPendenciasBlocoDTO = $this->listar($objPenBlocoProcessoDTO) ?: [];
        shuffle($arrObjPendenciasBlocoDTO);

        $objPenBlocoProcessoBD = new PenBlocoProcessoBD($this->getObjInfraIBanco());
      foreach ($arrObjPendenciasBlocoDTO as $objPendenciasBlocoDTO) {
        //Captura todas as pendências e status retornadas para impedir duplicidade
        $arrPendenciasBlocoRetornadas[] = sprintf("%d-%s", $objPendenciasBlocoDTO->getDblIdProtocolo(), $objPendenciasBlocoDTO->getNumIdAndamento());

        $objPendenciasBlocoDTO->setNumIdAndamento(ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO);
        $objPenBlocoProcessoBD->alterar($objPendenciasBlocoDTO);

        yield $objPendenciasBlocoDTO;
      }
    } catch (\Exception $e) {
        throw new InfraException('Módulo do Tramita: Falha em obter pendências de trâmite de processos em bloco.', $e);
    }
  }

  protected function desbloquearProcessoBlocoControlado($dblIdProcedimento)
    {
    try {
        $objPenBlocoProcessoDTO = new PenBlocoProcessoDTO();
        $objPenBlocoProcessoDTO->retTodos();
        $objPenBlocoProcessoDTO->setDblIdProtocolo($dblIdProcedimento);
        $objPenBlocoProcessoDTO->setNumIdAndamento([ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_NAO_INICIADO, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO], InfraDTO::$OPER_IN);

        $objPenBlocoProcessoDTO = $this->consultar($objPenBlocoProcessoDTO);

      if (!is_null($objPenBlocoProcessoDTO)) {
        $objPenBlocoProcessoDTO->setNumIdAndamento(ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO);

        $this->alterar($objPenBlocoProcessoDTO);

        $idBloco = $objPenBlocoProcessoDTO->getNumIdBloco();
        $this->atualizarEstadoDoBloco($idBloco);
      }

        //Desbloqueia o processo
        $objProtocoloRN = new ProtocoloRN();
        $objProtocoloDTO = new ProtocoloDTO();
        $objProtocoloDTO->setStrStaEstado(ProtocoloRN::$TE_NORMAL);
        $objProtocoloDTO->setDblIdProtocolo($dblIdProcedimento);
        $objProtocoloRN->alterarRN0203($objProtocoloDTO);

        //Cria o Objeto que registrar a Atividade de cancelamento
        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDblIdProtocolo($dblIdProcedimento);
        $objAtividadeDTO->setNumIdUnidade($objPenBlocoProcessoDTO->getNumIdUnidade());
        $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_CANCELADO));

        //Seta os atributos do tamplate de descrio dessa atividade
        $objAtributoAndamentoDTOHora = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTOHora->setStrNome('DATA_HORA');
        $objAtributoAndamentoDTOHora->setStrIdOrigem(null);
        $objAtributoAndamentoDTOHora->setStrValor(date('d/m/Y H:i'));

        $objUsuarioDTO = new UsuarioDTO();
        $objUsuarioDTO->setNumIdUsuario($objPenBlocoProcessoDTO->getNumIdUsuario());
        $objUsuarioDTO->setBolExclusaoLogica(false);
        $objUsuarioDTO->retStrNome();

        $objUsuarioRN = new UsuarioRN();
        $objUsuario = $objUsuarioRN->consultarRN0489($objUsuarioDTO);

        $objAtributoAndamentoDTOUser = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTOUser->setStrNome('USUARIO');
        $objAtributoAndamentoDTOUser->setStrIdOrigem(null);
        $objAtributoAndamentoDTOUser->setStrValor($objUsuario->getStrNome());

        $objAtividadeDTO->setArrObjAtributoAndamentoDTO([$objAtributoAndamentoDTOHora, $objAtributoAndamentoDTOUser]);

        $objAtividadeRN = new AtividadeRN();
        $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);
    } catch (\Exception $e) {
        throw new InfraException('Módulo do Tramita: Falha em obter pendências de trâmite de processos em bloco.', $e);
    }
  }

    /**
     * Registra a tentativa de trâmite do processo em bloco para posterior verificação de estouro do limite de envios
     *
     * @return void
     */
  protected function registrarTentativaEnvioControlado(PenBlocoProcessoDTO $objPenBlocoProcessoDTO)
    {
      $numTentativas = $objPenBlocoProcessoDTO->getNumTentativas() ?: 0;
      $numTentativas += 1;

      $objPenBlocoProcessoDTO->setNumTentativas($numTentativas);
      $objPenBlocoProcessoBD = new PenBlocoProcessoBD($this->getObjInfraIBanco());
      $objPenBlocoProcessoBD->alterar($objPenBlocoProcessoDTO);
  }

  protected function listarProtocolosBlocoConectado(PenBlocoProcessoDTO $parObjTramitaEmBlocoProtocoloDTO)
    {
    try {

        $ret = [];

        //Valida Permissao
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_tramita_em_bloco_protocolo_listar', __METHOD__, $parObjTramitaEmBlocoProtocoloDTO);

        $parObjRelBlocoProtocoloDTO = InfraString::prepararPesquisaDTO($parObjTramitaEmBlocoProtocoloDTO, "PalavrasPesquisa", "ProtocoloFormatadoProtocolo");
        $parObjRelBlocoProtocoloDTO->setStrStaNivelAcessoGlobalProtocolo(ProtocoloRN::$NA_SIGILOSO, InfraDTO::$OPER_DIFERENTE);
        $arrObjRelProtocoloBlocoDTO = $this->listar($parObjRelBlocoProtocoloDTO);

      foreach ($arrObjRelProtocoloBlocoDTO as $dto) {

        $objPenProtocoloDTO = new PenProtocoloDTO();
        $objPenProtocoloDTO->setDblIdProtocolo($dto->getDblIdProtocolo());
        $objPenProtocoloDTO->retStrSinObteveRecusa();
        $objPenProtocoloDTO->setNumMaxRegistrosRetorno(1);

        $objProtocoloBD = new ProtocoloBD($this->getObjInfraIBanco());
        $objPenProtocoloDTO = $objProtocoloBD->consultar($objPenProtocoloDTO);

        if (!empty($objPenProtocoloDTO)) {
            $dto->setStrSinObteveRecusa($objPenProtocoloDTO->getStrSinObteveRecusa());
        } else {
            $dto->setStrSinObteveRecusa('N');
        }

        $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
        $objTramiteEmBlocoDTO->setNumId($parObjTramitaEmBlocoProtocoloDTO->getNumIdBloco());
        $objTramiteEmBlocoDTO->retNumId();
        $objTramiteEmBlocoDTO->retStrStaEstado();

        $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
        $blocoResultado = $objTramiteEmBlocoRN->consultar($objTramiteEmBlocoDTO);

        if (!empty($blocoResultado)) {
            $dto->setStrStaEstadoBloco($blocoResultado->getStrStaEstado());
        } else {
            $dto->setStrStaEstadoBloco('A');
        }

        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDblIdProtocolo($dto->getDblIdProtocolo());
        $objAtividadeDTO->setNumIdTarefa(
            [
            ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO),
            ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO),
            ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_CANCELADO),
            ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_RECUSADO),
            ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_EXTERNO),
            ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_ABORTADO)
            ], InfraDTO::$OPER_IN
        );
        $objAtividadeDTO->setOrdDthAbertura(InfraDTO::$TIPO_ORDENACAO_DESC);
        $objAtividadeDTO->setNumMaxRegistrosRetorno(1);
        $objAtividadeDTO->retNumIdAtividade();
        $objAtividadeDTO->retNumIdTarefa();
        $objAtividadeDTO->retDblIdProcedimentoProtocolo();
        $objAtividadeRN = new AtividadeRN();
        $arrObjAtividadeDTO = $objAtividadeRN->listarRN0036($objAtividadeDTO);

        if (!empty($arrObjAtividadeDTO) && $arrObjAtividadeDTO[0]->getNumIdTarefa() != null) {
          $dto->setNumStaIdTarefa($arrObjAtividadeDTO[0]->getNumIdTarefa());
        } else {
            $dto->setNumStaIdTarefa(0);
        }

        $ret[] = $dto;
      }

        return $ret;
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro listando protocolos do bloco.', $e);
    }
  }

  protected function consultarConectado(PenBlocoProcessoDTO $objDTO)
    {
    try {
        $objTramitaEmBlocoProtocoloBD = new PenBlocoProcessoBD($this->getObjInfraIBanco());
        return $objTramitaEmBlocoProtocoloBD->consultar($objDTO);
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro consutando blocos.', $e);
    }
  }

    /**
     * Método utilizado para exclusão de dados.
     *
     * @param  TramitaEmBlocoProtocoloDTO $objDTO
     * @return array
     * @throws InfraException
     */
  protected function listarControlado(PenBlocoProcessoDTO $objDTO)
    {
    try {
        //Valida Permissão
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_tramita_em_bloco_protocolo_listar', __METHOD__, $objDTO);

        $objPenBlocoProcessoBD = new PenBlocoProcessoBD($this->getObjInfraIBanco());

        return $objPenBlocoProcessoBD->listar($objDTO);
    } catch (\Exception $e) {
        throw new InfraException('Módulo do Tramita: Falha na listagem de pendências de trâmite de processos em bloco.', $e);
    }
  }

  protected function excluirControlado(array $arrayObjDTO)
    {
    try {
        //Valida Permissão
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_tramita_em_bloco_protocolo_excluir', __METHOD__, $arrayObjDTO);

        $arrExcluido = [];

      foreach ($arrayObjDTO as $objDTO) {

        $objBD = new PenBlocoProcessoBD($this->getObjInfraIBanco());

        $tramiteEmBlocoDTO = new TramiteEmBlocoDTO();
        $tramiteEmBlocoDTO->setNumId($objDTO->getNumIdBloco());
        $tramiteEmBlocoDTO->setStrStaEstado(TramiteEmBlocoRN::$TE_ABERTO);
        $tramiteEmBlocoDTO->retStrStaEstado();
        $tramiteEmBlocoDTO->retNumId();

        $tramiteEmBlocoRN = new TramiteEmBlocoRN();
        $tramiteEmBloco = $tramiteEmBlocoRN->consultar($tramiteEmBlocoDTO);

        if ($tramiteEmBloco != null) {
            $arrayExcluido[] = $objBD->excluir($objDTO);
            continue;
        }

        $objPenProtocoloDTO = new PenBlocoProcessoDTO();
        $objPenProtocoloDTO->setNumIdBlocoProcesso($objDTO->getNumIdBlocoProcesso());
        $objPenProtocoloDTO->setDblIdProtocolo($objDTO->getDblIdProtocolo());
        $objPenProtocoloDTO->setNumIdBloco($objDTO->getNumIdBloco());
        $objPenProtocoloDTO->retNumIdAndamento();
        $objPenProtocoloDTO->retDblIdProtocolo();

        $objPenProtocoloDTO = $this->consultar($objPenProtocoloDTO);

        if ($objPenProtocoloDTO != null && $objPenProtocoloDTO->getNumIdAndamento() === null) {
            $arrExcluido[] = $objBD->excluir($objDTO);
            continue;
        }
      }

        return $arrExcluido;
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro excluindo Bloco.', $e);
    }
  }

  protected function montarIndexacaoControlado(PenBlocoProcessoDTO $objPenBlocoProcessoDTO)
    {
    try {

        $dto = new PenBlocoProcessoDTO();
        $dto->retNumIdBlocoProcesso();

      if (is_array($objPenBlocoProcessoDTO->getNumIdBlocoProcesso())) {
        $dto->setNumIdBlocoProcesso($objPenBlocoProcessoDTO->getNumIdBlocoProcesso(), InfraDTO::$OPER_IN);
      } else {
          $dto->setNumIdBlocoProcesso($objPenBlocoProcessoDTO->getNumIdBlocoProcesso());
      }

        $objPenBlocoProcessoDTOIdx = new PenBlocoProcessoDTO();

        $arrObjPenBlocoProcessoDTO = $this->listar($dto);

      foreach ($arrObjPenBlocoProcessoDTO as $dto) {

          $objPenBlocoProcessoDTOIdx->setNumBlocoProcesso($dto->getNumIdBlocoProcesso());
      }
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro montando indexação de processos em bloco.', $e);
    }
  }

  protected function cadastrarControlado(PenBlocoProcessoDTO $objPenBlocoProcessoDTO)
    {
    try {

        //Valida Permissao
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_tramite_em_bloco_cadastrar', __METHOD__, $objPenBlocoProcessoDTO);

        //Regras de Negocio
        $objInfraException = new InfraException();

        $objPenBlocoProcessoBD = new PenBlocoProcessoBD($this->getObjInfraIBanco());

        return $objPenBlocoProcessoBD->cadastrar($objPenBlocoProcessoDTO);
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro cadastrando Processo em Bloco.', $e);
    }
  }

  protected function alterarControlado(PenBlocoProcessoDTO $objPenBlocoProcessoDTO)
    {
    try {
        $objPenBlocoProcessoBD = new PenBlocoProcessoBD($this->getObjInfraIBanco());

        return $objPenBlocoProcessoBD->alterar($objPenBlocoProcessoDTO);
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro cadastrando Processo em Bloco.', $e);
    }
  }

  protected function validarBlocoDeTramiteControlado($idProtocolo)
    {
      $objPenBlocoProcessoDTO = new PenBlocoProcessoDTO();
      $objPenBlocoProcessoDTO->retNumIdBlocoProcesso();
      $objPenBlocoProcessoDTO->setDblIdProtocolo($idProtocolo);
      $objPenBlocoProcessoDTO->retNumIdBloco();
      $objPenBlocoProcessoDTO->retDblIdProtocolo();
      $objPenBlocoProcessoDTO->retNumIdAndamento();
      $objPenBlocoProcessoDTO->retStrProtocoloFormatadoProtocolo();

      $concluidos = [ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CIENCIA_RECUSA, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO_AUTOMATICAMENTE];

      $arrTramitaEmBloco = $this->listar($objPenBlocoProcessoDTO);
    if (!is_null($arrTramitaEmBloco) && count($arrTramitaEmBloco) > 0) {
      foreach ($arrTramitaEmBloco as $tramitaEmBloco) {
        if ($tramitaEmBloco->getNumIdAndamento() !== null && in_array($tramitaEmBloco->getNumIdAndamento(), $concluidos)) {
            continue;
        }

        $tramiteEmBlocoDTO = new TramiteEmBlocoDTO();
        $tramiteEmBlocoDTO->setNumId($tramitaEmBloco->getNumIdBloco());
        $tramiteEmBlocoDTO->retStrDescricao();
        $tramiteEmBlocoDTO->retStrStaEstado();
        $tramiteEmBlocoDTO->retStrSiglaUnidade();
        $tramiteEmBlocoDTO->retNumId();
        $tramiteEmBlocoDTO->retNumOrdem();

        $tramiteEmBlocoRN = new TramiteEmBlocoRN();
        $tramiteEmBloco = $tramiteEmBlocoRN->consultar($tramiteEmBlocoDTO);

        return "Prezado(a) usuário(a), o processo {$tramitaEmBloco->getStrProtocoloFormatadoProtocolo()} encontra-se inserido no bloco {$tramiteEmBloco->getNumOrdem()} - {$tramiteEmBloco->getStrDescricao()} da unidade {$tramiteEmBloco->getStrSiglaUnidade()}."
        . " Para continuar com essa ação é necessário que o processo seja removido do bloco em questão.";
      }
    }

      $objExpedirProcedimentoRN = new ExpedirProcedimentoRN();
      $objProcedimentoDTO = $objExpedirProcedimentoRN->consultarProcedimento($idProtocolo);

      $bolProcessoEstadoNormal = !in_array($objProcedimentoDTO->getStrStaEstadoProtocolo(), [ProtocoloRN::$TE_PROCEDIMENTO_SOBRESTADO, ProtocoloRN::$TE_PROCEDIMENTO_BLOQUEADO]);
    if (!$bolProcessoEstadoNormal) {
        return "Prezado(a) usuário(a), o processo {$objProcedimentoDTO->getStrProtocoloProcedimentoFormatado()} encontra-se bloqueado."
        . " Dessa forma, não foi possível realizar a sua inserção no bloco selecionado.";
    }

      return false;
  }

  public function validarQuantidadeDeItensNoBloco($dblIdbloco, $arrProtocolosOrigem)
    {
      $tramitaEmBlocoProtocoloDTO = new PenBlocoProcessoDTO();
      $tramitaEmBlocoProtocoloDTO->setNumIdBloco($dblIdbloco);
      $tramitaEmBlocoProtocoloDTO->retNumIdBloco();

      $tramitaEmBlocoProtocoloRN = new PenBlocoProcessoRN();
      $arrTramitaEmBlocoProtocolo = $tramitaEmBlocoProtocoloRN->listar($tramitaEmBlocoProtocoloDTO);
      $numRegistroBloco = count($arrTramitaEmBlocoProtocolo);
      $numRegistroItens = count($arrProtocolosOrigem);

      $numMaximoDeProcessos = 100;
    if (!empty($numRegistroBloco) && $numRegistroBloco >= $numMaximoDeProcessos) {
        return "Não é possível incluir mais que {$numMaximoDeProcessos} processos em um único bloco. O bloco selecionado já atingiu sua capacidade máxima.";
    }

    if ($numRegistroBloco + $numRegistroItens > $numMaximoDeProcessos) {
        return "Não é possível incluir mais que {$numMaximoDeProcessos} processos em um único bloco. Por favor, selecione outro bloco ou selecione uma quantidade menor de processos.";
    }

      return false;
  }

  public function validarBlocosEmAndamento()
    {
      $tramiteEmBlocoDTO = new TramiteEmBlocoDTO();
      $tramiteEmBlocoDTO->setStrStaEstado(TramiteEmBlocoRN::$TE_DISPONIBILIZADO);
      $tramiteEmBlocoDTO->retNumId();
      $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
      $arrTramiteEmBloco = $objTramiteEmBlocoRN->listar($tramiteEmBlocoDTO);
    foreach ($arrTramiteEmBloco as $blocoDTO) {
        $this->atualizarEstadoDoBloco($blocoDTO->getNumId());
    }
  } 

    /**
     * Atualizar Bloco  de tramite externo para concluído
     *
     * @param  int $idBloco
     * @throws InfraException
     */
  public function atualizarEstadoDoBloco($idBloco)
    {
      $blocoResultado = $this->buscarBloco($idBloco);

    if ($blocoResultado != null) {
        $arrObjTramiteEmBlocoProtocoloDTO = $this->buscarBlocoProcessos($idBloco);
      
        $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
        $idAndamentoBloco = TramiteEmBlocoRN::$TE_ABERTO;
      if (count($arrObjTramiteEmBlocoProtocoloDTO) > 0) {
        $idAndamentoBloco = $this->validarStatusProcessoParaBloco($arrObjTramiteEmBlocoProtocoloDTO, $idAndamentoBloco);
        $objTramiteEmBlocoDTO->setStrStaEstado($idAndamentoBloco);
      } else {
          $objTramiteEmBlocoDTO->setStrStaEstado($idAndamentoBloco);
      }

        $objTramiteEmBlocoDTO->setNumId($idBloco);

        $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
        $objTramiteEmBlocoRN->alterar($objTramiteEmBlocoDTO);
    }
  }

    /**
     * Busca um bloco pelo ID
     *
     * @param int $dblIdBloco
     */
  private function buscarBloco($idBloco)
    {
      $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
      $objTramiteEmBlocoDTO->setNumId($idBloco);
      $objTramiteEmBlocoDTO->retNumId();
      $objTramiteEmBlocoDTO->retStrStaEstado();

      $objTramiteEmBlocoRN = new TramiteEmBlocoRN();

      return $objTramiteEmBlocoRN->consultar($objTramiteEmBlocoDTO);
  }

    /**
     * Busca todos os processos de um bloco
     *
     * @param int $dblIdBloco
     */
  private function buscarBlocoProcessos($idBloco)
    {
      $objTramiteEmBlocoProtocoloDTO = new PenBlocoProcessoDTO();
      $objTramiteEmBlocoProtocoloDTO->setNumIdBloco($idBloco);
      $objTramiteEmBlocoProtocoloDTO->retNumIdAndamento();
      $objTramiteEmBlocoProtocoloDTO->retNumIdBloco();

      $tramitaEmBlocoProtocoloRN = new PenBlocoProcessoRN();

      return $tramitaEmBlocoProtocoloRN->listar($objTramiteEmBlocoProtocoloDTO);
  }

    /**
     * Valida o status do processo para o bloco
     *
     * @param array $arrObjTramiteEmBlocoProtocoloDTO
     * @param int   $idAndamentoBloco
     */
  private function validarStatusProcessoParaBloco($arrObjTramiteEmBlocoProtocoloDTO, $idAndamentoBloco)
    {
      $concluido = [ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CIENCIA_RECUSA, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO_AUTOMATICAMENTE, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE];
      $emAndamento = [ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_ENVIADOS_REMETENTE, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_METADADOS_RECEBIDO_DESTINATARIO, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_ENVIADO_DESTINATARIO, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO];
    
      $qtdProcesos = count($arrObjTramiteEmBlocoProtocoloDTO);
      $arrayConcluidos = [];
      $arrayEmAndamento = [];
    foreach ($arrObjTramiteEmBlocoProtocoloDTO as $objDTO) {
      if (in_array($objDTO->getNumIdAndamento(), $concluido)) {
        $arrayConcluidos[] = $objDTO;
      }

      if (in_array($objDTO->getNumIdAndamento(), $emAndamento)) {
          $arrayEmAndamento[] = $objDTO;
      }
    }

    if ($qtdProcesos == count($arrayConcluidos)) {
        $idAndamentoBloco = TramiteEmBlocoRN::$TE_CONCLUIDO;
    }
    if (count($arrayEmAndamento) > 0) {
        $idAndamentoBloco = TramiteEmBlocoRN::$TE_DISPONIBILIZADO;
    }

      return $idAndamentoBloco;
  }
}
