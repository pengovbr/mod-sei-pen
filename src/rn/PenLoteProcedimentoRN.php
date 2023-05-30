<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Regra de negócio para o parâmetros do módulo PEN
 */
class PenLoteProcedimentoRN extends InfraRN {

  protected function inicializarObjInfraIBanco()
    {
      return BancoSEI::getInstance();
  }

  protected function cadastrarLoteProcedimentoControlado(PenLoteProcedimentoDTO $objPenLoteProcedimentoDTO){

    try {
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_expedir_lote', __METHOD__, $objPenLoteProcedimentoDTO);
        $objPenLoteProcedimentoBD = new PenLoteProcedimentoBD($this->getObjInfraIBanco());
        return $objPenLoteProcedimentoBD->cadastrar($objPenLoteProcedimentoDTO);
    }
    catch (Exception $e) {
        throw new InfraException('Erro ao cadastrar procedimento lote.', $e);
    }
  }

  public function alterarLoteProcedimentoControlado(PenLoteProcedimentoDTO $objPenLoteProcedimentoDTO)
    {
    try {
        //Valida Permissão
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_expedir_lote', __METHOD__, $objPenLoteProcedimentoDTO);

        $objPenLoteProcedimentoBD = new PenLoteProcedimentoBD($this->getObjInfraIBanco());
        $objPenLoteProcedimentoBD->alterar($objPenLoteProcedimentoDTO);

    } catch (\Exception $e) {
        throw new InfraException('Falha na alteração da pendência de trâmite de processos em lote.', $e);
    }

  }

  protected function consultarLoteProcedimentoConectado(PenLoteProcedimentoDTO $objPenLoteProcedimentoDTO)
    {

    try {
        //Valida Permissão
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_expedir_lote', __METHOD__, $objPenLoteProcedimentoDTO);

        $objPenLoteProcedimentoBD = new PenLoteProcedimentoBD($this->getObjInfraIBanco());
        $objPenLoteProcedimento = $objPenLoteProcedimentoBD->consultar($objPenLoteProcedimentoDTO);

        return $objPenLoteProcedimento;

    } catch (\Exception $e) {
        throw new InfraException('Falha na consulta de trâmite de processos em lote.', $e);
    }

  }

  protected function listarLoteProcedimentoConectado(PenLoteProcedimentoDTO $objPenLoteProcedimentoDTO)
    {

    try {
        //Valida Permissão
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_expedir_lote', __METHOD__, $objPenLoteProcedimentoDTO);

        $objPenLoteProcedimentoBD = new PenLoteProcedimentoBD($this->getObjInfraIBanco());
        $arrObjPenLoteProcedimento = $objPenLoteProcedimentoBD->listar($objPenLoteProcedimentoDTO);

        return $arrObjPenLoteProcedimento;

    } catch (\Exception $e) {
        throw new InfraException('Falha na listagem de pendências de trâmite de processos em lote.', $e);
    }

  }

  protected function obterPendenciasLoteControlado(PenLoteProcedimentoDTO $objPenLoteProcedimentoDTO)
    {
    try {

        //Valida PermissãoTipo
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_expedir_lote', __METHOD__, $objPenLoteProcedimentoDTO);

        //Obter todos os processos pendentes antes de iniciar o monitoramento
        $arrObjPendenciasLoteDTO = $this->listarLoteProcedimento($objPenLoteProcedimentoDTO) ?: array();

        shuffle($arrObjPendenciasLoteDTO);

      foreach ($arrObjPendenciasLoteDTO as $objPendenciasLoteDTO) {
        //Captura todas as pendências e status retornadas para impedir duplicidade
        $arrPendenciasLoteRetornadas[] = sprintf("%d-%s", $objPendenciasLoteDTO->getDblIdProcedimento(), $objPendenciasLoteDTO->getNumIdAndamento());
        yield $objPendenciasLoteDTO;
      }
    } catch (\Exception $e) {
        throw new InfraException('Falha em obter pendências de trâmite de processos em lote.', $e);
    }

  }

    /**
     * Registra a tentativa de trâmite do processo em lote para posterior verificação de estouro do limite de envios
     *
     * @param PenLoteProcedimentoDTO $objPenLoteProcedimentoDTO
     * @return void
     */
  protected function registrarTentativaEnvioControlado(PenLoteProcedimentoDTO $objPenLoteProcedimentoDTO){
      $numTentativas = $objPenLoteProcedimentoDTO->getNumTentativas() ?: 0;
      $numTentativas += 1;

      $objPenLoteProcedimentoDTO->setNumTentativas($numTentativas);
      $objPenLoteProcedimentoBD = new PenLoteProcedimentoBD($this->getObjInfraIBanco());
      $objPenLoteProcedimentoBD->alterar($objPenLoteProcedimentoDTO);
  }


  protected function desbloquearProcessoLoteControlado($dblIdProcedimento)
    {
    try{

        $objPenLoteProcedimentoDTO = new PenLoteProcedimentoDTO();
        $objPenLoteProcedimentoDTO->retNumIdLote();
        $objPenLoteProcedimentoDTO->retNumIdUsuario();
        $objPenLoteProcedimentoDTO->retNumIdUnidade();
        $objPenLoteProcedimentoDTO->setDblIdProcedimento($dblIdProcedimento);
        $objPenLoteProcedimentoDTO->setNumIdAndamento(array(ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_NAO_INICIADO, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO), InfraDTO::$OPER_IN);

        $objPenLoteProcedimentoRN = new PenLoteProcedimentoRN();
        $objPenLoteProcedimentoDTO = $objPenLoteProcedimentoRN->consultarLoteProcedimento($objPenLoteProcedimentoDTO);

      if(!is_null($objPenLoteProcedimentoDTO)){
          $objPenExpedirLoteDTO = new PenLoteProcedimentoDTO();
          $objPenExpedirLoteDTO->setNumIdLote($objPenLoteProcedimentoDTO->getNumIdLote());
          $objPenExpedirLoteDTO->setDblIdProcedimento($dblIdProcedimento);
          $objPenExpedirLoteDTO->setNumIdAndamento(ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO);

          $objPenLoteProcedimentoRN = new PenLoteProcedimentoRN();
          $objPenLoteProcedimentoRN->alterarLoteProcedimento($objPenExpedirLoteDTO);
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
        $objAtividadeDTO->setNumIdUnidade($objPenLoteProcedimentoDTO->getNumIdUnidade());
        $objAtividadeDTO->setNumIdTarefa(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_CANCELADO));

        //Seta os atributos do tamplate de descrio dessa atividade
        $objAtributoAndamentoDTOHora = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTOHora->setStrNome('DATA_HORA');
        $objAtributoAndamentoDTOHora->setStrIdOrigem(null);
        $objAtributoAndamentoDTOHora->setStrValor(date('d/m/Y H:i'));

        $objUsuarioDTO = new UsuarioDTO();
        $objUsuarioDTO->setNumIdUsuario($objPenLoteProcedimentoDTO->getNumIdUsuario());
        $objUsuarioDTO->setBolExclusaoLogica(false);
        $objUsuarioDTO->retStrNome();

        $objUsuarioRN = new UsuarioRN();
        $objUsuario = $objUsuarioRN->consultarRN0489($objUsuarioDTO);

        $objAtributoAndamentoDTOUser = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTOUser->setStrNome('USUARIO');
        $objAtributoAndamentoDTOUser->setStrIdOrigem(null);
        $objAtributoAndamentoDTOUser->setStrValor($objUsuario->getStrNome());

        $objAtividadeDTO->setArrObjAtributoAndamentoDTO(array($objAtributoAndamentoDTOHora, $objAtributoAndamentoDTOUser));

        $objAtividadeRN = new AtividadeRN();
        $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);

    } catch (\Exception $e) {
        throw new InfraException('Falha em obter pendências de trâmite de processos em lote.', $e);
    }
  }

  protected function cancelarTramiteLoteControlado($dblIdLote)
  {
      $loteDTO = new PenLoteProcedimentoDTO();
      $loteDTO->setNumIdLote($dblIdLote);
      $loteDTO->retTodos();
      $loteRN = new PenLoteProcedimentoRN();
      $lotes = $loteRN->listarLoteProcedimento($loteDTO);
      foreach ($lotes as $lote) {
          if ($lote->getNumIdAndamento() == 7) {
              continue;
          }
          $objExpedirProcedimentoRN = new ExpedirProcedimentoRN();
          $objExpedirProcedimentoRN->cancelarTramite($lote->getDblIdProcedimento());
      }
  }

}
