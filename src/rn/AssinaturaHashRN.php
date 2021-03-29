<?php

class AssinaturaHashRN extends AssinaturaRN {


public function __construct(){
  parent::__construct();
}

protected function inicializarObjInfraIBanco(){
  return BancoSEI::getInstance();
}


protected function montarTarjasURLConectado($dados) {
    try {

    $objDocumentoDTO=$dados["objDocumentoDTO"];
    $controleURL=$dados["controleURL"];

      $strRet = '';

      $objAssinaturaDTO = new AssinaturaDTO();
      $objAssinaturaDTO->retStrNome();
      $objAssinaturaDTO->retNumIdAssinatura();
      $objAssinaturaDTO->retNumIdTarjaAssinatura();
      $objAssinaturaDTO->retStrTratamento();
      $objAssinaturaDTO->retStrStaFormaAutenticacao();
      $objAssinaturaDTO->retStrNumeroSerieCertificado();
      $objAssinaturaDTO->retDthAberturaAtividade();

      $objAssinaturaDTO->setDblIdDocumento($objDocumentoDTO->getDblIdDocumento());
       
      $objAssinaturaDTO->setOrdNumIdAssinatura(InfraDTO::$TIPO_ORDENACAO_ASC);
       
      $arrObjAssinaturaDTO = $this->listarRN1323($objAssinaturaDTO);

      if (count($arrObjAssinaturaDTO)) {

        $objTarjaAssinaturaDTO = new TarjaAssinaturaDTO();
        $objTarjaAssinaturaDTO->setBolExclusaoLogica(false);
        $objTarjaAssinaturaDTO->retNumIdTarjaAssinatura();
        $objTarjaAssinaturaDTO->retStrStaTarjaAssinatura();
        $objTarjaAssinaturaDTO->retStrTexto();
        $objTarjaAssinaturaDTO->retStrLogo();
        $objTarjaAssinaturaDTO->setNumIdTarjaAssinatura(array_unique(InfraArray::converterArrInfraDTO($arrObjAssinaturaDTO,'IdTarjaAssinatura')),InfraDTO::$OPER_IN);

        $objTarjaAssinaturaRN = new TarjaAssinaturaRN();
        $arrObjTarjaAssinaturaDTO = InfraArray::indexarArrInfraDTO($objTarjaAssinaturaRN->listar($objTarjaAssinaturaDTO),'IdTarjaAssinatura');

        foreach ($arrObjAssinaturaDTO as $objAssinaturaDTO) {

          if (!isset($arrObjTarjaAssinaturaDTO[$objAssinaturaDTO->getNumIdTarjaAssinatura()])) {
            throw new InfraException('Tarja associada com a assinatura "' . $objAssinaturaDTO->getNumIdAssinatura() . '" não encontrada.');
          }

          $objTarjaAutenticacaoDTOAplicavel = $arrObjTarjaAssinaturaDTO[$objAssinaturaDTO->getNumIdTarjaAssinatura()];

          $strTarja = $objTarjaAutenticacaoDTOAplicavel->getStrTexto();
          $strTarja = preg_replace("/@logo_assinatura@/s", '<img alt="logotipo" src="data:image/png;base64,' . $objTarjaAutenticacaoDTOAplicavel->getStrLogo() . '" />', $strTarja);
          $strTarja = preg_replace("/@nome_assinante@/s", $objAssinaturaDTO->getStrNome(), $strTarja);
          $strTarja = preg_replace("/@tratamento_assinante@/s", $objAssinaturaDTO->getStrTratamento(), $strTarja);
          $strTarja = preg_replace("/@data_assinatura@/s", substr($objAssinaturaDTO->getDthAberturaAtividade(), 0, 10), $strTarja);
          $strTarja = preg_replace("/@hora_assinatura@/s", substr($objAssinaturaDTO->getDthAberturaAtividade(), 11, 5), $strTarja);
          $strTarja = preg_replace("/@codigo_verificador@/s", $objDocumentoDTO->getStrProtocoloDocumentoFormatado(), $strTarja);
          $strTarja = preg_replace("/@crc_assinatura@/s", $objDocumentoDTO->getStrCrcAssinatura(), $strTarja);
          $strTarja = preg_replace("/@numero_serie_certificado_digital@/s", $objAssinaturaDTO->getStrNumeroSerieCertificado(), $strTarja);
          $strTarja = preg_replace("/@tipo_conferencia@/s", InfraString::transformarCaixaBaixa($objDocumentoDTO->getStrDescricaoTipoConferencia()), $strTarja);
          $strRet .= $strTarja;
        }

        $objTarjaAssinaturaDTO = new TarjaAssinaturaDTO();
        $objTarjaAssinaturaDTO->retStrTexto();
        $objTarjaAssinaturaDTO->setStrStaTarjaAssinatura(TarjaAssinaturaRN::$TT_INSTRUCOES_VALIDACAO);

        $objTarjaAssinaturaDTO = $objTarjaAssinaturaRN->consultar($objTarjaAssinaturaDTO);

        if ($objTarjaAssinaturaDTO != null){

          $strLinkAcessoExterno = '';
          if (strpos($objTarjaAssinaturaDTO->getStrTexto(),'@link_acesso_externo_processo@')!==false){
            $objEditorRN = new EditorRN();
            $strLinkAcessoExterno = $objEditorRN->recuperarLinkAcessoExterno($objDocumentoDTO);
          }


          $strTarja = $objTarjaAssinaturaDTO->getStrTexto();
          $strTarja = preg_replace("/@qr_code@/s", '<img align="center" alt="QRCode Assinatura" title="QRCode Assinatura" src="data:image/png;base64,' . $objDocumentoDTO->getStrQrCodeAssinatura() . '" />', $strTarja);
          $strTarja = preg_replace("/@codigo_verificador@/s", $objDocumentoDTO->getStrProtocoloDocumentoFormatado(), $strTarja);
          $strTarja = preg_replace("/@crc_assinatura@/s", $objDocumentoDTO->getStrCrcAssinatura(), $strTarja);
          $strTarja = preg_replace("/@link_acesso_externo_processo@/s", $strLinkAcessoExterno, $strTarja);
          $strTarja = str_replace($controleURL["atual"],$controleURL["antigo"], $strTarja);
          $strRet .= $strTarja;

        
        }
      }

      return EditorRN::converterHTML($strRet);

    } catch (Exception $e) {
      throw new InfraException('Erro montando tarja de assinatura.',$e);
    }
  }

}