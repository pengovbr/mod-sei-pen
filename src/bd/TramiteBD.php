<?php

require_once DIR_SEI_WEB.'/SEI.php';

class TramiteBD extends InfraBD {

  public function __construct(InfraIBanco $objInfraIBanco){
      parent::__construct($objInfraIBanco);
  }

    /**
     * Recupera os dados do �ltimo tr�mite v�lido realizado para determinado n�mero de processo eletr�nico
     *
     * @param ProcessoEletronicoDTO $parObjProcessoEletronicoDTO
     * @return void
     */
  public function consultarUltimoTramite(ProcessoEletronicoDTO $parObjProcessoEletronicoDTO, $parStrStaTipoTramite = null)
    {
    if(is_null($parObjProcessoEletronicoDTO)){
        throw new InfraException('Par�metro [parObjProcessoEletronicoDTO] n�o informado');
    }

    if(!$parObjProcessoEletronicoDTO->isSetDblIdProcedimento() && !$parObjProcessoEletronicoDTO->isSetStrNumeroRegistro()){
        throw new InfraException('Nenhuma das chaves de localiza��o do processo eletr�nico foi atribu�do. Informe o IdProcedimento ou NumeroRegistro.');
    }

      $objTramiteDTOPesquisa = new TramiteDTO();
      $objTramiteDTOPesquisa->retTodos();
      $objTramiteDTOPesquisa->setNumMaxRegistrosRetorno(1);
      $objTramiteDTOPesquisa->setOrdNumIdTramite(InfraDTO::$TIPO_ORDENACAO_DESC);
      $objTramiteDTOPesquisa->setStrStaTipoProtocolo(
          array(
              ProcessoEletronicoRN::$STA_TIPO_PROTOCOLO_PROCESSO,
              ProcessoEletronicoRN::$STA_TIPO_PROTOCOLO_DOCUMENTO_AVULSO
          ),
          InfraDTO::$OPER_IN
      );

    if(!is_null($parStrStaTipoTramite)){
        $objTramiteDTOPesquisa->setStrStaTipoTramite($parStrStaTipoTramite);
    }

    if($parObjProcessoEletronicoDTO->isSetDblIdProcedimento()){
        $objTramiteDTOPesquisa->setNumIdProcedimento($parObjProcessoEletronicoDTO->getDblIdProcedimento());
    }

    if($parObjProcessoEletronicoDTO->isSetStrNumeroRegistro()){
        $objTramiteDTOPesquisa->setStrNumeroRegistro($parObjProcessoEletronicoDTO->getStrNumeroRegistro());
    }

      return $this->consultar($objTramiteDTOPesquisa);
  }
}
?>
