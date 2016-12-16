<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

/**
 * Repostório da entidade ReciboTramite
 * 
 * @author Join Tecnologia
 */
class ReciboTramiteRN extends InfraRN {

    public function __construct() {
        parent::__construct();
    }

    protected function inicializarObjInfraIBanco() {
        return BancoSEI::getInstance();
    }

    /**
     * retorna um array de recibos de tramites ( geralmente é somente um )
     * 
     * @param string $strAtividade pode ser RECEBER ou ENVIAR
     * @return array
     */
    public function listarPorAtividade($numIdTramite, $numIdTarefa = 501) {

        $objInfraIBanco = $this->inicializarObjInfraIBanco();

        $arrObjDTO = array();

        switch ($numIdTarefa) {
            case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO):
                $objReciboTramiteDTO = new ReciboTramiteDTO();
                $objReciboTramiteDTO->setNumIdTramite($numIdTramite);
                $objReciboTramiteDTO->retStrNumeroRegistro();
                $objReciboTramiteDTO->retNumIdTramite();
                $objReciboTramiteDTO->retDthRecebimento();
                $objReciboTramiteDTO->retStrHashAssinatura();
                $objReciboTramiteDTO->retStrCadeiaCertificado();
                
                $objReciboTramiteBD = new ReciboTramiteBD($objInfraIBanco);
                $arrObjDTO = $objReciboTramiteBD->listar($objReciboTramiteDTO);
                break;

            case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO):
                $objReciboTramiteDTO = new ReciboTramiteRecebidoDTO();
                $objReciboTramiteDTO->setNumIdTramite($numIdTramite);
                $objReciboTramiteDTO->retStrNumeroRegistro();
                $objReciboTramiteDTO->retNumIdTramite();
                $objReciboTramiteDTO->retDthRecebimento();
                $objReciboTramiteDTO->retStrHashAssinatura();
                $objReciboTramiteDTO->retStrCadeiaCertificado();
                
                $objReciboTramiteBD = new ReciboTramiteRecebidoBD($objInfraIBanco);
                $arrObjDTO = $objReciboTramiteBD->listar($objReciboTramiteDTO);
                break;
        }

        return $arrObjDTO;
    }

    public function downloadReciboEnvio($numIdTramite) {
        
        $objReciboTramiteDTO = new ReciboTramiteEnviadoDTO();
        $objReciboTramiteDTO->setNumIdTramite($numIdTramite);
        $objReciboTramiteDTO->retStrNumeroRegistro();
        $objReciboTramiteDTO->retNumIdTramite();
        $objReciboTramiteDTO->retDthRecebimento();
        $objReciboTramiteDTO->retStrHashAssinatura();
        $objReciboTramiteDTO->retStrCadeiaCertificado();

        $objReciboTramiteBD = new ReciboTramiteRecebidoBD($this->getObjInfraIBanco());
        $arrObjDTO = $objReciboTramiteBD->listar($objReciboTramiteDTO);
        
        return $arrObjDTO;
    }

}
