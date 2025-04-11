<?php

require_once DIR_SEI_WEB.'/SEI.php';

session_start();

$arrResponse = ['sucesso' => false, 'mensagem' => '', 'erros' => []];
$objInfraException = new InfraException();


try {

    $arrProtocolosOrigem = $_POST['selProcedimentos'];

  if(count($arrProtocolosOrigem) == 0) {
      throw new InfraException('Módulo do Tramita: Nenhum procedimento foi informado', 'Desconhecido');
  }    

    $objExpedirProcedimentosRN = new ExpedirProcedimentoRN();
    $objExpedirProcedimentosRN->verificarProcessosAbertoNaUnidade($objInfraException, $arrProtocolosOrigem);
  if ($objInfraException->contemValidacoes()) {
      $arrErros = [];
    foreach ($objInfraException->getArrObjInfraValidacao() as $objInfraValidacao) {
        $strAtributo = $objInfraValidacao->getStrAtributo();
      if (!array_key_exists($strAtributo, $arrErros)) {
        $arrErros[$strAtributo] = [];
      }
        $arrErros[$strAtributo][] = mb_convert_encoding($objInfraValidacao->getStrDescricao(), 'UTF-8', 'ISO-8859-1');
    }

      $arrResponse['erros'] = $arrErros;
      print json_encode($arrResponse);
      exit(0);
  }

  foreach ($arrProtocolosOrigem as $dblIdProcedimento) {

      $objExpedirProcedimentosRN = new ExpedirProcedimentoRN();
      $objProcedimentoDTO = $objExpedirProcedimentosRN->consultarProcedimento($dblIdProcedimento);

      // Utilizamos o protocolo para criar um indice para separar os erros entre o
      // processo e os seus processos apensados
      $strProtocoloFormatado = $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado();

    if(empty($objProcedimentoDTO)) {
        throw new InfraException('Módulo do Tramita: Procedimento '.$strProtocoloFormatado.' não foi localizado', 'Desconhecido');
    }        
        
    if(!array_key_exists('selProcedimentos', $_POST) || empty($_POST['selProcedimentos'])) {
        $objInfraException->adicionarValidacao('Informe o Protocolo', $strProtocoloFormatado);
    }

    if(!array_key_exists('selRepositorioEstruturas', $_POST) || empty($_POST['selRepositorioEstruturas'])) {
        $objInfraException->adicionarValidacao('Informe o Repositório de Estruturas Organizacionais', $strProtocoloFormatado);
    }

    if(!array_key_exists('hdnIdUnidade', $_POST) || empty($_POST['hdnIdUnidade'])) {
        $objInfraException->adicionarValidacao('Informe Unidade de destino', $strProtocoloFormatado);
    }

      $objProcedimentoDTO->setArrObjDocumentoDTO($objExpedirProcedimentosRN->listarDocumentos($dblIdProcedimento));
      $objProcedimentoDTO->setArrObjParticipanteDTO($objExpedirProcedimentosRN->listarInteressados($dblIdProcedimento));
      $objExpedirProcedimentosRN->validarPreCondicoesExpedirProcedimento($objInfraException, $objProcedimentoDTO);
  }
}
catch(\InfraException $e) {
    $strmensagemErro = InfraException::inspecionar($e);
    $objInfraException->adicionarValidacao($strmensagemErro);
    LogSEI::getInstance()->gravar($strmensagemErro);
}

if ($objInfraException->contemValidacoes()) {

    $arrErros = [];
  foreach ($objInfraException->getArrObjInfraValidacao() as $objInfraValidacao) {
      $strAtributo = $objInfraValidacao->getStrAtributo();
    if (!array_key_exists($strAtributo, $arrErros)) {
        $arrErros[$strAtributo] = [];
    }
      $arrErros[$strAtributo][] = mb_convert_encoding($objInfraValidacao->getStrDescricao(), 'UTF-8', 'ISO-8859-1');
  }

    $arrResponse['erros'] = $arrErros;
} else {
    $arrResponse['sucesso'] = true;
}

print json_encode($arrResponse);
exit(0);
