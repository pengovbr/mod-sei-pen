<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Repostório da entidade ReciboTramite
 */
class ReciboTramiteRN extends InfraRN
{

  public function __construct()
    {
      parent::__construct();
  }

  protected function inicializarObjInfraIBanco()
    {
      return BancoSEI::getInstance();
  }

    /**
     * Retorna um array de recibos de tramites
     *
     * @return array
     */
  protected function listarPorAtividadeConectado($parArrParametros)
    {
      $numIdTramite = $parArrParametros['id_tramite'];
      $numIdTarefa = $parArrParametros['id_tarefa'];

      $arrObjDTO = [];
    switch ($numIdTarefa) {
      case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO):
          $objReciboTramiteDTO = new ReciboTramiteDTO();
          $objReciboTramiteDTO->setNumIdTramite($numIdTramite);
          $objReciboTramiteDTO->retStrNumeroRegistro();
          $objReciboTramiteDTO->retNumIdTramite();
          $objReciboTramiteDTO->retDthRecebimento();
          $objReciboTramiteDTO->retStrHashAssinatura();
          $objReciboTramiteDTO->retStrCadeiaCertificado();

          $objReciboTramiteBD = new ReciboTramiteBD($this->getObjInfraIBanco());
          $arrObjDTO = $objReciboTramiteBD->listar($objReciboTramiteDTO);
          break;

      case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO):
      case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_DOCUMENTO_AVULSO_RECEBIDO):
          $objReciboTramiteDTO = new ReciboTramiteRecebidoDTO();
          $objReciboTramiteDTO->setNumIdTramite($numIdTramite);
          $objReciboTramiteDTO->retStrNumeroRegistro();
          $objReciboTramiteDTO->retNumIdTramite();
          $objReciboTramiteDTO->retDthRecebimento();
          $objReciboTramiteDTO->retStrHashAssinatura();
          $objReciboTramiteDTO->retStrCadeiaCertificado();

          $objReciboTramiteBD = new ReciboTramiteRecebidoBD($this->getObjInfraIBanco());
          $arrObjDTO = $objReciboTramiteBD->listar($objReciboTramiteDTO);
          break;
    }

      return $arrObjDTO;
  }

  protected function downloadReciboEnvioConectado($numIdTramite)
    {

      $objReciboTramiteDTO = new ReciboTramiteEnviadoDTO();
      $objReciboTramiteDTO->setNumIdTramite($numIdTramite);
      $objReciboTramiteDTO->retStrNumeroRegistro();
      $objReciboTramiteDTO->retNumIdTramite();
      $objReciboTramiteDTO->retDthRecebimento();
      $objReciboTramiteDTO->retStrHashAssinatura();
      $objReciboTramiteDTO->retStrCadeiaCertificado();

      $objReciboTramiteBD = new ReciboTramiteRecebidoBD($this->getObjInfraIBanco());

      return $objReciboTramiteBD->listar($objReciboTramiteDTO);
  }

}
