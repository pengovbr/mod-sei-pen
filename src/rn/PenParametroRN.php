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
        throw new InfraException('M�dulo do Tramita: Erro ao contar par�metro.', $e);
    }
  }

  protected function consultarConectado(PenParametroDTO $objDTO){

    try {
        $objBD = new PenParametroBD($this->inicializarObjInfraIBanco());
        return $objBD->consultar($objDTO);
    }
    catch (Exception $e) {
        throw new InfraException('M�dulo do Tramita: Erro ao listar par�metro.', $e);
    }
  }

  protected function listarConectado(PenParametroDTO $objDTO){

    try {
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_parametros_configuracao', __METHOD__, $objDTO);
        $objBD = new PenParametroBD($this->inicializarObjInfraIBanco());
        return $objBD->listar($objDTO);
    }
    catch (Exception $e) {
        throw new InfraException('M�dulo do Tramita: Erro ao listar par�metro.', $e);
    }
  }

  protected function cadastrarControlado(PenParametroDTO $objPenParametroDTO){

    try {
        $objInfraException = new InfraException();
        $this->validarUnidadeRecebimento($objPenParametroDTO, $objInfraException);
        $this->validarTipoProcessoExterno($objPenParametroDTO, $objInfraException);
        $objInfraException->lancarValidacoes();

        $objBD = new PenParametroBD($this->inicializarObjInfraIBanco());
        return $objBD->cadastrar($objPenParametroDTO);
    }
    catch (Exception $e) {
        throw new InfraException('M�dulo do Tramita: Erro ao cadastrar par�metro.', $e);
    }
  }

  protected function alterarControlado(PenParametroDTO $objPenParametroDTO){

    try {
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_parametros_configuracao_alterar', __METHOD__, $objPenParametroDTO);
        $objInfraException = new InfraException();
        $this->validarUnidadeRecebimento($objPenParametroDTO, $objInfraException);
        $this->validarTipoProcessoExterno($objPenParametroDTO, $objInfraException);
        $objInfraException->lancarValidacoes();

        $objBD = new PenParametroBD($this->inicializarObjInfraIBanco());
        return $objBD->alterar($objPenParametroDTO);
    }
    catch (Exception $e) {
        throw new InfraException('M�dulo do Tramita: Erro ao alterar par�metro.', $e);
    }
  }

  protected function excluirControlado(PenParametroDTO $objDTO){

    try {
        $objBD = new PenParametroBD($this->inicializarObjInfraIBanco());
        return $objBD->excluir($objDTO);
    }
    catch (Exception $e) {
        throw new InfraException('M�dulo do Tramita: Erro ao excluir par�metro.', $e);
    }
  }

  public function setValor($strNome, $strValor){

    try {
        $objBD = new PenParametroBD($this->inicializarObjInfraIBanco());
        return $objBD->setValor($strNome, $strValor);
    }
    catch (Exception $e) {
        throw new InfraException('M�dulo do Tramita: Erro ao reativar par�metro.', $e);
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
        throw new InfraException("M�dulo do Tramita: Erro ao persistir par�metro $parStrNome", $e);
    }
  }



  private function validarTipoProcessoExterno(PenParametroDTO $objPenParametroDTO, InfraException $objInfraException){

    if($objPenParametroDTO->getStrNome() == "PEN_TIPO_PROCESSO_EXTERNO"){
        $objRelTipoProcedimentoAssuntoDTO = new RelTipoProcedimentoAssuntoDTO();
        $objRelTipoProcedimentoAssuntoDTO->retNumIdTipoProcedimento();
        $objRelTipoProcedimentoAssuntoDTO->setNumIdTipoProcedimento($objPenParametroDTO->getStrValor());
        $objRelTipoProcedimentoAssuntoDTO->setDistinct(true);

        $objRelTipoProcedimentoAssuntoRN = new RelTipoProcedimentoAssuntoRN();
        $arrObjTipoProcedimentoAssunto=InfraArray::converterArrInfraDTO($objRelTipoProcedimentoAssuntoRN->listarRN0192($objRelTipoProcedimentoAssuntoDTO), "IdTipoProcedimento");

      if (empty($arrObjTipoProcedimentoAssunto)) {
          $strMensagemErro = "Tipo de processo externo n�o possui sugest�o de assuntos atribu�da.";
          $objInfraException->adicionarValidacao($strMensagemErro);
      }
    }
  }

  private function validarUnidadeRecebimento(PenParametroDTO $objPenParametroDTO, InfraException $objInfraException){

    if($objPenParametroDTO->getStrNome() == "PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO"){
        $strIdUnidadeRecebimento = $objPenParametroDTO->getStrValor();

        $objUnidadeDTO = new UnidadeDTO();
        $objUnidadeDTO->retNumIdUnidade();
        $objUnidadeDTO->retStrSinEnvioProcesso();
        $objUnidadeDTO->setNumIdUnidade($strIdUnidadeRecebimento);

        $objUnidadeBD = new UnidadeBD($this->inicializarObjInfraIBanco());
        $objUnidadeDTO = $objUnidadeBD->consultar($objUnidadeDTO);

      if(!is_null($objUnidadeDTO) && $objUnidadeDTO->getStrSinEnvioProcesso() == "N"){
          $strMensagemErro = "N�o � permitido a configura��o de uma \"Unidade SEI para Representa��o de �rg�os Externos\" que n�o esteja dispon�vel para envio de processo, ";
          $strMensagemErro .= "op��o \"Dispon�vel para envio de processos\" desmarcado no cadastro da unidade.";
          $objInfraException->adicionarValidacao($strMensagemErro);
      }
    }
  }

  /**
   * @param array $arrObjTipoProcedimentoDTO
   * @param string $mensagem
   * @return void
   * @throws InfraException
   */
  public function validarAcaoTipoProcessoPadrao($arrObjTipoProcedimentoDTO, $mensagem)
  {
    $mapeamentos = [];
    foreach ($arrObjTipoProcedimentoDTO as $objTipoProcedimentoDTO) {
      $objPenParametroDTO = new PenParametroDTO();
      $objPenParametroDTO->setStrNome('PEN_TIPO_PROCESSO_EXTERNO');
      $objPenParametroDTO->retStrNome();
      $objPenParametroDTO->retStrValor();
      $objPenParametroDTO = $this->consultarConectado($objPenParametroDTO);
      if (
        !is_null($objPenParametroDTO) 
        && !is_null($objPenParametroDTO->getStrValor())
        && $objPenParametroDTO->getStrValor() == $objTipoProcedimentoDTO->getIdTipoProcedimento()
      ) {
        $mapeamentos[$objTipoProcedimentoDTO->getIdTipoProcedimento()] =
          $objTipoProcedimentoDTO->getIdTipoProcedimento() . '-' .  $objTipoProcedimentoDTO->getNome();
      }
    }
    
    if (count($mapeamentos) > 0) {
      $mensagem = sprintf($mensagem, implode('", "', $mapeamentos));
      LogSEI::getInstance()->gravar($mensagem, LogSEI::$AVISO);
      $objInfraException = new InfraException();
      $objInfraException->adicionarValidacao($mensagem);
      $objInfraException->lancarValidacoes();
    }
  }
}
