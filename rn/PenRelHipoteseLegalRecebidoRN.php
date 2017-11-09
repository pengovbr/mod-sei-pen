<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

/**
 * Description of PenRelHipoteseLegalEnvioRN
 *
 * @author michael
 */
class PenRelHipoteseLegalRecebidoRN extends PenRelHipoteseLegalRN {

    public function listar(PenRelHipoteseLegalDTO $objDTO){
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_hipotese_legal_recebimento_listar', __METHOD__, $objDTO);
        return parent::listarConectado($objDTO);
    }
    public function alterar(PenRelHipoteseLegalDTO $objDTO){
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_hipotese_legal_recebimento_alterar', __METHOD__, $objDTO);
        return parent::alterarConectado($objDTO);
    }
    public function cadastrar(PenRelHipoteseLegalDTO $objDTO){
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_hipotese_legal_recebimento_cadastrar', __METHOD__, $objDTO);
        return parent::cadastrarConectado($objDTO);
    }
    public function excluir(PenRelHipoteseLegalDTO $objDTO){
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_hipotese_legal_recebimento_excluir', __METHOD__, $objDTO);
        return parent::excluirConectado($objDTO);
    }
}
