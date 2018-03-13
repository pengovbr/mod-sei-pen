<?php

require_once __DIR__ . '/../../SEI.php';
SessaoSEI::getInstance(false)->simularLogin(null, null, '100000001', '110000001');

$idProcesso = 12;
$idTipoDocumento = 12;
$descricao = 'descrição de teste';
$nivelAcesso = 1;
$hipoteseLegal = 1;
$grauSigilo = '';
$arrAssuntos = array(array('id' => 2), array('id' => 4));
$arrInteressados = array(array('id' => 100000008), array('id' => 100000010), array('id' => 100000002), array('id' => 100000006));
$arrDestinatarios = array(array('id' => 100000008));
$arrRemetentes = array(array('id' => 100000008));


$objDocumentoDTO = new DocumentoDTO();
$objDocumentoDTO->setDblIdDocumento(null);
$objDocumentoDTO->setDblIdProcedimento($idProcesso);


$objProtocoloDTO = new ProtocoloDTO();
$objProtocoloDTO->setDblIdProtocolo(null);
$objProtocoloDTO->setStrStaProtocolo('G');
// $objProtocoloDTO->setDtaGeracao($dtaGeracao);

$objDocumentoDTO->setNumIdSerie($idTipoDocumento);
// $objDocumentoDTO->setStrNomeSerie($nomeTipo);

$objDocumentoDTO->setDblIdDocumentoEdoc(null);
$objDocumentoDTO->setDblIdDocumentoEdocBase(null);
$objDocumentoDTO->setNumIdUnidadeResponsavel(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
$objDocumentoDTO->setNumIdTipoConferencia(null);
$objDocumentoDTO->setStrNumero('');
// $objDocumentoDTO->setNumIdTipoConferencia($objDocumentoAPI->getIdTipoConferencia());

$objProtocoloDTO->setStrStaNivelAcessoLocal($nivelAcesso);
$objProtocoloDTO->setNumIdHipoteseLegal($hipoteseLegal);
$objProtocoloDTO->setStrDescricao($descricao);
$objProtocoloDTO->setStrStaGrauSigilo($grauSigilo);

$arrParticipantesDTO = array();

foreach ($arrRemetentes as $k => $remetente) {
    $objParticipanteDTO = new ParticipanteDTO();
    $objParticipanteDTO->setNumIdContato($remetente['id']);
    $objParticipanteDTO->setStrStaParticipacao(ParticipanteRN::$TP_REMETENTE);
    $objParticipanteDTO->setNumSequencia($k);
    $arrParticipantesDTO[] = $objParticipanteDTO;
}

foreach ($arrInteressados as $k => $interessado) {
    $objParticipanteDTO = new ParticipanteDTO();
    $objParticipanteDTO->setNumIdContato($interessado['id']);
    $objParticipanteDTO->setStrStaParticipacao(ParticipanteRN::$TP_INTERESSADO);
    $objParticipanteDTO->setNumSequencia($k);
    $arrParticipantesDTO[] = $objParticipanteDTO;
}

foreach ($arrDestinatarios as $k => $destinatario) {
    $objParticipanteDTO = new ParticipanteDTO();
    $objParticipanteDTO->setNumIdContato($destinatario['id']);
    $objParticipanteDTO->setStrStaParticipacao(ParticipanteRN::$TP_DESTINATARIO);
    $objParticipanteDTO->setNumSequencia($k);
    $arrParticipantesDTO[] = $objParticipanteDTO;
}
$arrRelProtocoloAssuntoDTO = array();

foreach ($arrAssuntos as $k => $assunto) {
    $relProtocoloAssuntoDTO = new RelProtocoloAssuntoDTO();
    $relProtocoloAssuntoDTO->setNumIdAssunto($assunto['id']);
    $relProtocoloAssuntoDTO->setDblIdProtocolo($idDocumento);
    $relProtocoloAssuntoDTO->setNumSequencia($k);
    $arrRelProtocoloAssuntoDTO[] = $relProtocoloAssuntoDTO;
}

$objProtocoloDTO->setArrObjParticipanteDTO($arrParticipantesDTO);
$objProtocoloDTO->setArrObjRelProtocoloAssuntoDTO($arrRelProtocoloAssuntoDTO);

//OBSERVACOES
$objObservacaoDTO = new ObservacaoDTO();
$objObservacaoDTO->setStrDescricao($observacao);
$objProtocoloDTO->setArrObjObservacaoDTO(array($objObservacaoDTO));

$objDocumentoDTO->setObjProtocoloDTO($objProtocoloDTO);
$objDocumentoDTO->setStrStaDocumento(DocumentoRN::$TD_EDITOR_INTERNO);

try {
    $objDocumentoRN = new DocumentoRN();
   $obj =  $objDocumentoRN->cadastrarRN0003($objDocumentoDTO);
   
   var_dump($obj);
} catch (Exception $ex) {
    var_dump($ex);
}
