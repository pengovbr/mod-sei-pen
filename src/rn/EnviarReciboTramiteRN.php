<?php

require_once DIR_SEI_WEB.'/SEI.php';

class EnviarReciboTramiteRN extends InfraRN
{
  private $objProcessoEletronicoRN;

  public function __construct()
  {
    parent::__construct();
    $this->objProcessoEletronicoRN = new ProcessoEletronicoRN();
  }

  protected function inicializarObjInfraIBanco()
  {
    return BancoSEI::getInstance();
  }

    /**
     * Gera o recibo do tramite para o destin�rio informando o recebimento
     * do procedimento.
     *
     * @param int $numIdTramite
     * @return array
     */
  protected function gerarReciboTramite($numIdTramite){

      $arrStrHashConteudo = [];

      $objMetaRetorno = $this->objProcessoEletronicoRN->solicitarMetadados($numIdTramite);

      $objMetaProcesso = $objMetaRetorno->metadados->processo;

      $arrObjMetaDocumento = is_array($objMetaProcesso->documentos) ? $objMetaProcesso->documentos : [$objMetaProcesso->documentos];

      $objDTO = new ComponenteDigitalDTO();
      $objBD = new ComponenteDigitalBD($this->inicializarObjInfraIBanco());

    foreach($arrObjMetaDocumento as $objMetaDocumento) {

        $strHashConteudo = ProcessoEletronicoRN::getHashFromMetaDados($objMetaDocumento->componenteDigital->hash);

        $objDTO->setStrHashConteudo($strHashConteudo);

      if($objBD->contar($objDTO) > 0) {

        $arrStrHashConteudo[] = $strHashConteudo;
      }
    }

      return $arrStrHashConteudo;
  }

  protected function cadastrarReciboTramiteRecebimento($strNumeroRegistro = '', $parNumIdTramite = 0, $strHashConteudo = '', $parArrayHash = [])
    {
    try {
      $objBD = new ReciboTramiteRecebidoBD($this->inicializarObjInfraIBanco());

      $objDTO = new ReciboTramiteRecebidoDTO();
      $objDTO->setStrNumeroRegistro($strNumeroRegistro);
      $objDTO->setNumIdTramite($parNumIdTramite);

      if(!empty($strHashConteudo)) { $objDTO->setStrHashAssinatura($strHashConteudo);
      }

      if(intval($objBD->contar($objDTO)) == 0) {

          $objDTO->setDthRecebimento(date('d/m/Y H:i:s'));
          $objBD->cadastrar($objDTO);
      }

      foreach($parArrayHash as $strHashComponenteDigital){

          $objReciboTramiteHashDTO = new ReciboTramiteHashDTO();
          $objReciboTramiteHashDTO->setStrNumeroRegistro($strNumeroRegistro);
          $objReciboTramiteHashDTO->setNumIdTramite($parNumIdTramite);
          $objReciboTramiteHashDTO->setStrHashComponenteDigital($strHashComponenteDigital);
          $objReciboTramiteHashDTO->setStrTipoRecibo(ProcessoEletronicoRN::$STA_TIPO_RECIBO_CONCLUSAO_ENVIADO);
          $objBD->cadastrar($objReciboTramiteHashDTO);
      }
    } catch (Exception $e) {
      $strMensagem = "Falha na obten��o do recibo de recebimento de protocolo do tr�mite $parNumIdTramite. $e";
      LogSEI::getInstance()->gravar($strMensagem, InfraLog::$ERRO);
    } 
  }

  public function enviarReciboTramiteProcesso($parNumIdTramite, $parArrayHash, $parDthRecebimento = null)
  {
    try{
        ModPenUtilsRN::simularLoginUnidadeRecebimento();
        date_default_timezone_set('America/Sao_Paulo');

      if(!isset($parNumIdTramite) || $parNumIdTramite == 0) {
        throw new InfraException('M�dulo do Tramita: Par�metro $parNumIdTramite n�o informado.');
      }

        //Verifica se todos os componentes digitais j� foram devidamente recebido
        $arrObjTramite = $this->objProcessoEletronicoRN->consultarTramites($parNumIdTramite);
      if(!isset($arrObjTramite) || count($arrObjTramite) != 1) {
        throw new InfraException("M�dulo do Tramita: Tr�mite n�o pode ser localizado pelo identificador $parNumIdTramite.");
      }

        $objTramite = $arrObjTramite[0];
        $strNumeroRegistro = $objTramite->NRE;

      if($objTramite->situacaoAtual != ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO) {
        throw new InfraException(sprintf('Situa��o do Tr�mite diferente da permitida para o envio do recibo de conclus�o de tr�mite (%s).', $objTramite->situacaoAtual));
      }

        $dthRecebimentoComponentesDigitais = $this->obterDataRecebimentoComponentesDigitais($objTramite);
        $dthRecebimentoComponentesDigitais = $dthRecebimentoComponentesDigitais ?: date();
        $dthRecebimento = gmdate("Y-m-d\TH:i:s.000\Z", InfraData::getTimestamp($dthRecebimentoComponentesDigitais));

        $strReciboTramite  = "<recibo>";
        $strReciboTramite .= "<IDT>$parNumIdTramite</IDT>";
        $strReciboTramite .= "<NRE>$strNumeroRegistro</NRE>";
        $strReciboTramite .= "<dataDeRecebimento>$dthRecebimento</dataDeRecebimento>";
        sort($parArrayHash);

      foreach ($parArrayHash as $strHashConteudo) {
        if(!empty($strHashConteudo)){
              $strReciboTramite .= "<hashDoComponenteDigital>$strHashConteudo</hashDoComponenteDigital>";
        }
      }
        $strReciboTramite  .= "</recibo>";

        //Envia o Recibo de salva no banco
        $hashAssinatura = $this->objProcessoEletronicoRN->enviarReciboDeTramite($parNumIdTramite, $dthRecebimento, $strReciboTramite);
        $this->cadastrarReciboTramiteRecebimento($strNumeroRegistro, $parNumIdTramite, $hashAssinatura, $parArrayHash);

    } catch (Exception $e) {
        $detalhes = null;
        $mensagem = InfraException::inspecionar($e);

      if(isset($strReciboTramite)){
          $detalhes = "Falha na valida��o do recibo de conclus�o do tr�mite do processo. Recibo: \n" . $strReciboTramite;
      }

        throw new InfraException($mensagem, $e, $detalhes);
    }
  }

  private function obterDataRecebimentoComponentesDigitais($parObjTramite){

    if(!isset($parObjTramite)) {
      throw new InfraException('M�dulo do Tramita: Par�metro $parObjTramite n�o informado.');
    }

    if(!is_array($parObjTramite->itensHistorico->operacao)) {
      $parObjTramite->itensHistorico->operacao = [$parObjTramite->itensHistorico->operacao];
    }

    foreach ($parObjTramite->itensHistorico->operacao as $operacao) {
      if($operacao['situacao'] == ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO) {
        return ProcessoEletronicoRN::converterDataSEI($operacao['dataHora']);
      }
    }

    return null;
  }
}
