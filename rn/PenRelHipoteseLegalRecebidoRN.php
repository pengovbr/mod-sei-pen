<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

/**
 * Description of PenRelHipoteseLegalEnvioRN
 *
 * @author michael
 */
class PenRelHipoteseLegalRecebidoRN extends PenRelHipoteseLegalRN {

    protected function listarConectado(PenRelHipoteseLegalDTO $objDTO) {
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_hipotese_legal_recebimento_listar', __METHOD__, $objDTO);
        return parent::listarConectado($objDTO);
    }

    protected function alterarControlado(PenRelHipoteseLegalDTO $objDTO) {
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_hipotese_legal_recebimento_alterar', __METHOD__, $objDTO);
        return parent::alterarConectado($objDTO);
    }

    protected function cadastrarControlado(PenRelHipoteseLegalDTO $objDTO) {
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_hipotese_legal_recebimento_cadastrar', __METHOD__, $objDTO);
        return parent::cadastrarConectado($objDTO);
    }

    protected function excluirControlado(PenRelHipoteseLegalDTO $objDTO) {
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_hipotese_legal_recebimento_excluir', __METHOD__, $objDTO);
        return parent::excluirConectado($objDTO);
    }

    /**
     * Pega o ID hipotese PEN para buscar o ID do SEI
     * @param integer $numIdentificacao
     * @return integer
     */
    protected function getIdHipoteseLegalSEIConectado($numIdentificacao) {
        $objBanco = BancoSEI::getInstance();
        $objGenericoBD = new GenericoBD($objBanco);

        $objPenHipoteseLegalDTO = new PenHipoteseLegalDTO();
        $objPenHipoteseLegalDTO->setNumIdentificacao($numIdentificacao);
        $objPenHipoteseLegalDTO->retNumIdHipoteseLegal();
        $objPenHipoteseLegalDTO = $objGenericoBD->consultar($objPenHipoteseLegalDTO);

        if ($objPenHipoteseLegalDTO) {
        
            // Mapeamento da hipotese legal remota
            $objPenRelHipoteseLegalDTO = new PenRelHipoteseLegalDTO();
            $objPenRelHipoteseLegalDTO->setStrTipo('R');
            $objPenRelHipoteseLegalDTO->retNumIdHipoteseLegal();
            $objPenRelHipoteseLegalDTO->setNumIdBarramento($objPenHipoteseLegalDTO->getNumIdHipoteseLegal());

            $objPenRelHipoteseLegal = $objGenericoBD->consultar($objPenRelHipoteseLegalDTO);
            
            if ($objPenRelHipoteseLegal) {
                return $objPenRelHipoteseLegal->getNumIdHipoteseLegal();
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

}
