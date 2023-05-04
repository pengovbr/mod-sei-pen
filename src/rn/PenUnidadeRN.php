<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Description of PenUnidadeEnvioRN
 *
 *
 */
class PenUnidadeRN extends InfraRN {
    
    /**
     * Inicializa o obj do banco da Infra
     * @return obj
     */
  protected function inicializarObjInfraIBanco(){
      return BancoSEI::getInstance();
  }
    
    /**
     * Método para buscar apenas as unidades que já estão em uso
     * @param PenUnidadeDTO $objFiltroDTO
     * @return arrayDTO
     */
  protected function getIdUnidadeEmUsoConectado(PenUnidadeDTO $objFiltroDTO){
      $objDTO = new PenUnidadeDTO();
      $objDTO->setDistinct(true);
      $objDTO->retNumIdUnidade();
        
    if($objFiltroDTO->isSetNumIdUnidade()) {
        $objDTO->setNumIdUnidade($objFiltroDTO->getNumIdUnidade(), InfraDTO::$OPER_DIFERENTE);
    }

      $arrObjDTO = $this->listar($objDTO);
        
      $arrIdUnidade = array();
        
    if(!empty($arrObjDTO)) {
        $arrIdUnidade = InfraArray::converterArrInfraDTO($arrObjDTO, 'IdUnidade');
    }
      return $arrIdUnidade;
  }
    
    /**
     * Método utilizado para listagem de dados.
     * @param UnidadeDTO $objUnidadeDTO
     * @return array
     * @throws InfraException
     */
  protected function listarConectado(UnidadeDTO $objPenUnidadeDTO) {
    try {
        //Valida Permissao
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_map_unidade_listar', __METHOD__, $objUnidadeDTO);
        $objPenUnidadeBD = new PenUnidadeBD($this->getObjInfraIBanco());
        return $objPenUnidadeBD->listar($objPenUnidadeDTO);            
    }catch(Exception $e){
        throw new InfraException('Erro listando Unidades.', $e);
    }
  }
    
  /**
   * Método utilizado para consultar dados.
   * @param PenUnidadeDTO $objDTO
   * @return array
   * @throws InfraException
   */
  protected function consultarControlado(PenUnidadeDTO $objPenUnidadeDTO){
    try {
        $objPenUnidadeBD = new PenUnidadeBD(BancoSEI::getInstance());
        return $objPenUnidadeBD->consultar($objPenUnidadeDTO);
    } 
    catch (Exception $e) {
        throw new InfraException('Erro alterando mapeamento de unidades.', $e);
    }
  }

    /**
     * Método utilizado para alteração de dados.
     * @param UnidadeDTO $objDTO
     * @return array
     * @throws InfraException
     */
  protected function alterarControlado(UnidadeDTO $objPenUnidadeDTO){
    try {
        $objPenUnidadeBD = new PenUnidadeBD(BancoSEI::getInstance());
        return $objPenUnidadeBD->alterar($objPenUnidadeDTO);
    } 
    catch (Exception $e) {
        throw new InfraException('Erro alterando mapeamento de unidades.', $e);
    }
  }
    
    /**
     * Método utilizado para cadastro de dados.
     * @param UnidadeDTO $objDTO
     * @return array
     * @throws InfraException
     */
  protected function cadastrarConectado(UnidadeDTO $objDTO){
    try {
        $objBD = new PenUnidadeBD(BancoSEI::getInstance());
        return $objBD->cadastrar($objDTO);
    } 
    catch (Exception $e) {
        throw new InfraException('Erro cadastrando mapeamento de unidades.', $e);
    }
  }
    
    /**
     * Método utilizado para exclusão de dados.
     * @param UnidadeDTO $objDTO
     * @return array
     * @throws InfraException
     */
  protected function excluirControlado(UnidadeDTO $objDTO){
    try {
        $objBD = new PenUnidadeBD(BancoSEI::getInstance());
        return $objBD->excluir($objDTO);
    } 
    catch (Exception $e) {
        throw new InfraException('Erro excluindo mapeamento de unidades.', $e);
    }
  }

    /**
     * Método utilizado para contagem de unidades mapeadas
     * @param UnidadeDTO $objUnidadeDTO
     * @return array
     * @throws InfraException
     */
  protected function contarConectado(PenUnidadeDTO $objPenUnidadeDTO) {
    try {
        //Valida Permissao
        $objPenUnidadeBD = new PenUnidadeBD($this->getObjInfraIBanco());
        return $objPenUnidadeBD->contar($objPenUnidadeDTO);
    }
    catch(Exception $e){
        throw new InfraException('Erro contando mapeamento de unidades.', $e);
    }
  }    

}
