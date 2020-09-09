<?php

require_once DIR_SEI_WEB.'/SEI.php';

class TramiteBD extends InfraBD {

    public function __construct(InfraIBanco $objInfraIBanco){
        parent::__construct($objInfraIBanco);
    }

    /**
     * Recupera os dados do último trâmite válido realizado para determinado número de processo eletrônico
     *
     * @param ProcessoEletronicoDTO $parObjProcessoEletronicoDTO
     * @return void
     */
    public function consultarUltimoTramiteRecebido(ProcessoEletronicoDTO $parObjProcessoEletronicoDTO)
    {
        if(is_null($parObjProcessoEletronicoDTO)){
            throw new InfraException('Parâmetro [parObjProcessoEletronicoDTO] não informado');
        }

        if(!$parObjProcessoEletronicoDTO->isSetDblIdProcedimento() && !$parObjProcessoEletronicoDTO->isSetStrNumeroRegistro()){
            throw new InfraException('Nenhuma das chaves de localização do processo eletrônico foi atribuído. Informe o IdProcedimento ou NumeroRegistro.');
        }

        $objTramiteDTOPesquisa = new TramiteDTO();
        $objTramiteDTOPesquisa->retTodos();
        $objTramiteDTOPesquisa->setStrStaTipoProtocolo(ProcessoEletronicoRN::$STA_TIPO_PROTOCOLO_PROCESSO);
        $objTramiteDTOPesquisa->setStrStaTipoTramite(ProcessoEletronicoRN::$STA_TIPO_TRAMITE_RECEBIMENTO);
        $objTramiteDTOPesquisa->setOrdNumIdTramite(InfraDTO::$TIPO_ORDENACAO_DESC);
        $objTramiteDTOPesquisa->setNumMaxRegistrosRetorno(1);

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
