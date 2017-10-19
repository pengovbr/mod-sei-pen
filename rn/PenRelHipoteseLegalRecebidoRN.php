<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

/**
 * Description of PenRelHipoteseLegalEnvioRN
 *
 * @author michael
 */
class PenRelHipoteseLegalRecebidoRN extends PenRelHipoteseLegalRN {

    public function listar(PenRelHipoteseLegalDTO $objDTO){
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_hipotese_legal_recebido_listar', __METHOD__, $objDTO);
        return parent::listarConectado($objDTO);
    }
    public function alterar(PenRelHipoteseLegalDTO $objDTO){
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_hipotese_legal_recebido_alterar', __METHOD__, $objDTO);
        return parent::alterarConectado($objDTO);
    }
    public function cadastrar(PenRelHipoteseLegalDTO $objDTO){
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_hipotese_legal_recebido_cadastrar', __METHOD__, $objDTO);
        return parent::cadastrarConectado($objDTO);
    }
    public function excluir(PenRelHipoteseLegalDTO $objDTO){
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_hipotese_legal_recebido_excluir', __METHOD__, $objDTO);
        return parent::excluirConectado($objDTO);
    }
}
