<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Regra de neg�cio para o par�metros do m�dulo PEN
 */
class PenParametroRN extends InfraRN {

  protected function inicializarObjInfraIBanco()
    {
      return BancoSEI::getInstance();
  }

  protected function contarConectado(PenParametroDTO $objDTO){

    try {
        $objBD = new PenParametroBD($this->inicializarObjInfraIBanco());
        return $objBD->contar($objDTO);
    }
    catch (Exception $e) {
        throw new InfraException('Erro ao contar par�metro.', $e);
    }
  }

  protected function consultarConectado(PenParametroDTO $objDTO){

    try {
        $objBD = new PenParametroBD($this->inicializarObjInfraIBanco());
        return $objBD->consultar($objDTO);
    }
    catch (Exception $e) {
        throw new InfraException('Erro ao listar par�metro.', $e);
    }
  }

  protected function listarConectado(PenParametroDTO $objDTO){

    try {
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_parametros_configuracao', __METHOD__, $objDTO);
        $objBD = new PenParametroBD($this->inicializarObjInfraIBanco());
        return $objBD->listar($objDTO);
    }
    catch (Exception $e) {
        throw new InfraException('Erro ao listar par�metro.', $e);
    }
  }

  protected function cadastrarControlado(PenParametroDTO $objDTO){

    try {
        $objBD = new PenParametroBD($this->inicializarObjInfraIBanco());
        return $objBD->cadastrar($objDTO);
    }
    catch (Exception $e) {
        throw new InfraException('Erro ao cadastrar par�metro.', $e);
    }
  }

  protected function alterarControlado(PenParametroDTO $objDTO){

    try {
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_parametros_configuracao_alterar', __METHOD__, $objDTO);
        $objBD = new PenParametroBD($this->inicializarObjInfraIBanco());
        return $objBD->alterar($objDTO);
    }
    catch (Exception $e) {
        throw new InfraException('Erro ao alterar par�metro.', $e);
    }
  }

  protected function excluirControlado(PenParametroDTO $objDTO){

    try {
        $objBD = new PenParametroBD($this->inicializarObjInfraIBanco());
        return $objBD->excluir($objDTO);
    }
    catch (Exception $e) {
        throw new InfraException('Erro ao excluir par�metro.', $e);
    }
  }

  public function setValor($strNome, $strValor){

    try {
        $objBD = new PenParametroBD($this->inicializarObjInfraIBanco());
        return $objBD->setValor($strNome, $strValor);
    }
    catch (Exception $e) {
        throw new InfraException('Erro ao reativar par�metro.', $e);
    }
  }

    /**
     * Resgata o valor do par�metro configura
     * @param string $strNome
     */
  public function getParametro($strNome) {
      $objPenParametroDTO = new PenParametroDTO();
      $objPenParametroDTO->setStrNome($strNome);
      $objPenParametroDTO->retStrValor();

    if($this->contar($objPenParametroDTO) > 0) {
        $objPenParametroDTO = $this->consultar($objPenParametroDTO);
        return $objPenParametroDTO->getStrValor();
    }
  }


    /**
     * Insere ou alterar o valor de um par�metro de configura��o do m�dulo de integra��o PEN
     *
     * @param string $parStrNome Nome do par�metro
     * @param string $parStrValor valor do par�metro
     * @return void
     */
  public static function persistirParametro($parStrNome, $parStrValor, $parStrDescricao = null, $parNumSequencia = null)
    {
    try{
        $objPenParametroRN = new PenParametroRN();
        $objPenParametroDTO = new PenParametroDTO();
        $objPenParametroDTO->setStrNome($parStrNome);

      if($objPenParametroRN->contar($objPenParametroDTO) == 0){
        $objPenParametroDTO->setStrValor($parStrValor);
        $objPenParametroDTO->setStrDescricao($parStrDescricao);
        $objPenParametroDTO->setNumSequencia($parNumSequencia);
        $objPenParametroRN->cadastrar($objPenParametroDTO);
      } else {
          $objPenParametroDTO->setStrValor($parStrValor);
          $objPenParametroDTO->setStrDescricao($parStrDescricao);
          $objPenParametroDTO->setNumSequencia($parNumSequencia);
          $objPenParametroRN->alterar($objPenParametroDTO);
      }
    }
    catch (Exception $e) {
        throw new InfraException("Erro ao persistir par�metro $parStrNome", $e);
    }
  }
}
