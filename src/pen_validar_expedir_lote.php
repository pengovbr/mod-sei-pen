<?php

require_once DIR_SEI_WEB.'/SEI.php';

session_start();

$arrResponse = array('sucesso' => false, 'mensagem' => '', 'erros' => array());
$objInfraException = new InfraException();


try {

    $arrProtocolosOrigem = $_POST['selProcedimentos'];

    if(count($arrProtocolosOrigem) == 0) {
        throw new InfraException('Nenhum procedimento foi informado', 'Desconhecido');
    }    

    foreach ($arrProtocolosOrigem as $dblIdProcedimento) {

        $objExpedirProcedimentosRN = new ExpedirProcedimentoRN();
        $objProcedimentoDTO = $objExpedirProcedimentosRN->consultarProcedimento($dblIdProcedimento);

        // Utilizamos o protocolo para criar um indice para separar os erros entre o
        // processo e os seus processos apensados
        $strProtocoloFormatado = $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado();

        if(empty($objProcedimentoDTO)) {
            throw new InfraException('Procedimento '.$strProtocoloFormatado.' não foi localizado', 'Desconhecido');
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

        if(!$objInfraException->contemValidacoes()) {
            $objProcedimentoDTO->setArrObjDocumentoDTO($objExpedirProcedimentosRN->listarDocumentos($dblIdProcedimento));
            $objProcedimentoDTO->setArrObjParticipanteDTO($objExpedirProcedimentosRN->listarInteressados($dblIdProcedimento));
            $objExpedirProcedimentosRN->validarPreCondicoesExpedirProcedimento($objInfraException, $objProcedimentoDTO, $strProtocoloFormatado);
        }
    }
}
catch(\InfraException $e) {
    $strmensagemErro = InfraException::inspecionar($e);
    $objInfraException->adicionarValidacao($strmensagemErro);
    LogSEI::getInstance()->gravar($strmensagemErro);
}

if($objInfraException->contemValidacoes()) {

    $arrErros = array();
    foreach($objInfraException->getArrObjInfraValidacao() as $objInfraValidacao) {
        $strAtributo = $objInfraValidacao->getStrAtributo();
        if(!array_key_exists($strAtributo, $arrErros)){
            $arrErros[$strAtributo] = array();
        }
        $arrErros[$strAtributo][] = utf8_encode($objInfraValidacao->getStrDescricao());
    }

    $arrResponse['erros'] = $arrErros;
}
else {
    $arrResponse['sucesso'] = true;
}

print json_encode($arrResponse);
exit(0);
