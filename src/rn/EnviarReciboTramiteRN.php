<?php

require_once DIR_SEI_WEB.'/SEI.php';

class EnviarReciboTramiteRN extends InfraRN
{
  private $objProcessoEletronicoRN;
  private $objInfraParametro;
  private $objPenParametroRN;

  public function __construct()
  {
    parent::__construct();

    $this->objInfraParametro = new InfraParametro(BancoSEI::getInstance());
    $this->objProcessoEletronicoRN = new ProcessoEletronicoRN();
    $this->objPenParametroRN = new PenParametroRN();
  }

  protected function inicializarObjInfraIBanco()
  {
    return BancoSEI::getInstance();
  }

    /**
     * Gera o recibo do tramite para o destinário informando o recebimento
     * do procedimento.
     *
     * @param int $numIdTramite
     * @return array
     */
  protected function gerarReciboTramite($numIdTramite){

      $arrStrHashConteudo = array();

      $objMetaRetorno = $this->objProcessoEletronicoRN->solicitarMetadados($numIdTramite);

      $objMetaProcesso = $objMetaRetorno->metadados->processo;

      $arrObjMetaDocumento = is_array($objMetaProcesso->documentos) ? $objMetaProcesso->documentos : array($objMetaProcesso->documentos);

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

  protected function cadastrarReciboTramiteRecebimento($strNumeroRegistro = '', $parNumIdTramite = 0, $strHashConteudo = '', $parArrayHash = array())
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
      $strMensagem = "Falha na obtenção do recibo de recebimento de protocolo do trâmite $parNumIdTramite. $e";
      LogSEI::getInstance()->gravar($strMensagem, InfraLog::$ERRO);
    } 
  }

  public function enviarReciboTramiteProcesso($parNumIdTramite, $parArrayHash, $parDthRecebimento = null)
  {
    try{
        ModPenUtilsRN::simularLoginUnidadeRecebimento();
        date_default_timezone_set('America/Sao_Paulo');

      if(!isset($parNumIdTramite) || $parNumIdTramite == 0) {
        throw new InfraException('Parâmetro $parNumIdTramite não informado.');
      }

        //Verifica se todos os componentes digitais já foram devidamente recebido
        $arrObjTramite = $this->objProcessoEletronicoRN->consultarTramites($parNumIdTramite);
      if(!isset($arrObjTramite) || count($arrObjTramite) != 1) {
        throw new InfraException("Trâmite não pode ser localizado pelo identificador $parNumIdTramite.");
      }

        $objTramite = $arrObjTramite[0];
        $strNumeroRegistro = $objTramite->NRE;

      if($objTramite->situacaoAtual != ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO) {
        throw new InfraException(sprintf('Situação do Trâmite diferente da permitida para o envio do recibo de conclusão de trâmite (%s).', $objTramite->situacaoAtual));
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
          $detalhes = "Falha na validação do recibo de conclusão do trâmite do processo. Recibo: \n" . $strReciboTramite;
      }

        throw new InfraException($mensagem, $e, $detalhes);
    }
  }

  private function obterDataRecebimentoComponentesDigitais($parObjTramite){

    if(!isset($parObjTramite)) {
      throw new InfraException('Parâmetro $parObjTramite não informado.');
    }

    if(!is_array($parObjTramite->itensHistorico->operacao)) {
      $parObjTramite->itensHistorico->operacao = array($parObjTramite->itensHistorico->operacao);
    }

    foreach ($parObjTramite->itensHistorico->operacao as $operacao) {
      if($operacao['situacao'] == ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO) {
        return ProcessoEletronicoRN::converterDataSEI($operacao['dataHora']);
      }
    }

    return null;
  }
}
