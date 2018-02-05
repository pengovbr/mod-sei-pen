<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

/**
 * Description of PenRelHipoteseLegalEnvioRN
 *
 * @author Join Tecnologia
 */
class PenRelHipoteseLegalEnvioRN extends PenRelHipoteseLegalRN {
    
    public function listar(PenRelHipoteseLegalDTO $objDTO){
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_hipotese_legal_envio_listar', __METHOD__, $objDTO);
        return parent::listarConectado($objDTO);
    }
    public function alterar(PenRelHipoteseLegalDTO $objDTO){
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_hipotese_legal_envio_alterar', __METHOD__, $objDTO);
        return parent::alterarConectado($objDTO);
    }
    public function cadastrar(PenRelHipoteseLegalDTO $objDTO){
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_hipotese_legal_envio_cadastrar', __METHOD__, $objDTO);
        return parent::cadastrarConectado($objDTO);
    }
    public function excluir(PenRelHipoteseLegalDTO $objDTO){
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_hipotese_legal_envio_excluir', __METHOD__, $objDTO);
        return parent::excluirConectado($objDTO);
    }
    public function consultar(PenRelHipoteseLegalDTO $objDTO){
        return parent::consultarConectado($objDTO);
    }
    
    /**
     * Pega o ID hipotese sei para buscar o ID do barramento
     * @param integer $numIdHipoteseSEI
     * @return integer
     */
    public function getIdHipoteseLegalPEN($numIdHipoteseSEI) {
        $objBanco = BancoSEI::getInstance();
        $objGenericoBD = new GenericoBD($objBanco);
        
        // Mapeamento da hipotese legal remota
        $objPenRelHipoteseLegalDTO = new PenRelHipoteseLegalDTO();
        $objPenRelHipoteseLegalDTO->setStrTipo('E');
        $objPenRelHipoteseLegalDTO->retNumIdentificacao();
        $objPenRelHipoteseLegalDTO->setNumIdHipoteseLegal($numIdHipoteseSEI);
                
        $objPenRelHipoteseLegal = $objGenericoBD->consultar($objPenRelHipoteseLegalDTO);
        
        if ($objPenRelHipoteseLegal) {
            return $objPenRelHipoteseLegal->getNumIdentificacao();
        } else {
            return null;
        }
    }
}
