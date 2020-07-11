<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Regra de negócio para o parâmetros do módulo PEN
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
            throw new InfraException('Erro ao contar parâmetro.', $e);
        }
    }

    protected function consultarConectado(PenParametroDTO $objDTO){

        try {
            $objBD = new PenParametroBD($this->inicializarObjInfraIBanco());
            return $objBD->consultar($objDTO);
        }
        catch (Exception $e) {
            throw new InfraException('Erro ao listar parâmetro.', $e);
        }
    }

    protected function listarConectado(PenParametroDTO $objDTO){

        try {
            SessaoSEI::getInstance()->validarAuditarPermissao('pen_parametros_configuracao', __METHOD__, $objDTO);
            $objBD = new PenParametroBD($this->inicializarObjInfraIBanco());
            return $objBD->listar($objDTO);
        }
        catch (Exception $e) {
            throw new InfraException('Erro ao listar parâmetro.', $e);
        }
    }

    protected function cadastrarControlado(PenParametroDTO $objDTO){

        try {
            $objBD = new PenParametroBD($this->inicializarObjInfraIBanco());
            return $objBD->cadastrar($objDTO);
        }
        catch (Exception $e) {
            throw new InfraException('Erro ao cadastrar parâmetro.', $e);
        }
    }

    protected function alterarControlado(PenParametroDTO $objDTO){

        try {
            SessaoSEI::getInstance()->validarAuditarPermissao('pen_parametros_configuracao_alterar', __METHOD__, $objDTO);
            $objBD = new PenParametroBD($this->inicializarObjInfraIBanco());
            return $objBD->alterar($objDTO);
        }
        catch (Exception $e) {
            throw new InfraException('Erro ao alterar parâmetro.', $e);
        }
    }

    protected function excluirControlado(PenParametroDTO $objDTO){

        try {
            $objBD = new PenParametroBD($this->inicializarObjInfraIBanco());
            return $objBD->excluir($objDTO);
        }
        catch (Exception $e) {
            throw new InfraException('Erro ao excluir parâmetro.', $e);
        }
    }

    public function setValor($strNome, $strValor){

        try {
            $objBD = new PenParametroBD($this->inicializarObjInfraIBanco());
            return $objBD->setValor($strNome, $strValor);
        }
        catch (Exception $e) {
            throw new InfraException('Erro ao reativar parâmetro.', $e);
        }
    }

    /**
     * Resgata o valor do parâmetro configura
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
     * Insere ou alterar o valor de um parâmetro de configuração do módulo de integração PEN
     *
     * @param string $parStrNome Nome do parâmetro
     * @param string $parStrValor valor do parâmetro
     * @return void
     */
    public static function persistirParametro($parStrNome, $parStrValor, $parStrDescricao=null, $parNumSequencia=null)
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
            throw new InfraException("Erro ao persistir parâmetro $parStrNome", $e);
        }
    }
}
