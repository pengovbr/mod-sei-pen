<?php

require_once dirname(__FILE__) . '/../../SEI.php';

session_start();
  
//print_r($_POST); exit;


$arrResponse = array('sucesso' => false, 'mensagem' => '', 'erros' => array());
$objInfraException = new InfraException();

//txtProtocoloExibir
//selRepositorioEstruturas
//hdnIdUnidade
//selProcedimentosApensados


try {
    
    if(!array_key_exists('id_procedimento', $_GET) || empty($_GET['id_procedimento'])) {
        throw new InfraException('Nenhum procedimento foi informado', 'Desconhecido');
    }
    
    $dblIdProcedimento = $_GET['id_procedimento'];

    $objExpedirProcedimentosRN = new ExpedirProcedimentoRN();
    $objProcedimentoDTO = $objExpedirProcedimentosRN->consultarProcedimento($dblIdProcedimento);
    
    if(empty($objProcedimentoDTO)) {
        throw new InfraException('Procedimento não foi localizado', 'Desconhecido');  
    }
    
    // Utilizamos o protocolo para criar um indice para separar os erros entre o
    // processo e os seus processos apensados
    $strProtocoloFormatado = $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado();
    
    $objRelProtocoloProtocoloRN = new RelProtocoloProtocoloRN();
     
    //Consulta do ID Pai
    $objRelProtocoloProtocoloDTO1 = new RelProtocoloProtocoloDTO();
    $objRelProtocoloProtocoloDTO1->setDblIdProtocolo1($dblIdProcedimento);
    $objRelProtocoloProtocoloDTO1->setStrStaAssociacao(RelProtocoloProtocoloRN ::$TA_PROCEDIMENTO_ANEXADO);
    $objRelProtocoloProtocoloDTO1->retDblIdProtocolo1();
    
    //Consulta do ID Filhos
    $objRelProtocoloProtocoloDTO2 = new RelProtocoloProtocoloDTO();
    $objRelProtocoloProtocoloDTO2->setDblIdProtocolo2($dblIdProcedimento);
    $objRelProtocoloProtocoloDTO2->setStrStaAssociacao(RelProtocoloProtocoloRN ::$TA_PROCEDIMENTO_ANEXADO);
    $objRelProtocoloProtocoloDTO2->retDblIdProtocolo2();
    
    $numCount1 = $objRelProtocoloProtocoloRN->contarRN0843($objRelProtocoloProtocoloDTO1);
    $numCount2 = $objRelProtocoloProtocoloRN->contarRN0843($objRelProtocoloProtocoloDTO2);
    
    if ($numCount1 > 0 && $numCount2 > 0) {
            $objInfraException->adicionarValidacao('Esse processo está anexado a outro processo e possui outros em anexo, portanto não pode ser tramitado.', $strProtocoloFormatado);    
    } else {
        if ($numCount1 > 0) {
            $objInfraException->adicionarValidacao('Esse processo possuí outros em anexo, portanto não pode ser tramitado externamente.', $strProtocoloFormatado);
        }

        if ($numCount2 > 0) {
            $objInfraException->adicionarValidacao('Esse processo está anexado a outro processo, portanto não pode ser tramitado.', $strProtocoloFormatado);
        }
    }
       
    if(!array_key_exists('txtProtocoloExibir', $_POST) || empty($_POST['txtProtocoloExibir'])) {
        $objInfraException->adicionarValidacao('Informe o Protocolo', $strProtocoloFormatado);
    }

    if(!array_key_exists('selRepositorioEstruturas', $_POST) || empty($_POST['selRepositorioEstruturas'])) {
        $objInfraException->adicionarValidacao('Informe o Repositorio de Estruturas Organizacionais', $strProtocoloFormatado);
    }
    
    if(!array_key_exists('hdnIdUnidade', $_POST) || empty($_POST['hdnIdUnidade'])) {
        $objInfraException->adicionarValidacao('Informe Unidade de destino', $strProtocoloFormatado);
    }

    if(!$objInfraException->contemValidacoes()) {

        $objProcedimentoDTO->setArrObjDocumentoDTO($objExpedirProcedimentosRN->listarDocumentos($dblIdProcedimento));
        $objProcedimentoDTO->setArrObjParticipanteDTO($objExpedirProcedimentosRN->listarInteressados($dblIdProcedimento));
        $objExpedirProcedimentosRN->validarPreCondicoesExpedirProcedimento($objInfraException, $objProcedimentoDTO, $strProtocoloFormatado);

        // Processos apensados
        if(array_key_exists('selProcedimentosApensados', $_POST) && is_array($_POST['selProcedimentosApensados'])){

            foreach($_POST['selProcedimentosApensados'] as $dblIdProcedimento) {

                $objProcedimentoDTO = $objExpedirProcedimentosRN->consultarProcedimento($dblIdProcedimento);

                $strProtocoloFormatado = $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado();

                $objProcedimentoDTO->setArrObjDocumentoDTO($objExpedirProcedimentosRN->listarDocumentos($dblIdProcedimento));
                $objProcedimentoDTO->setArrObjParticipanteDTO($objExpedirProcedimentosRN->listarInteressados($dblIdProcedimento));
                $objExpedirProcedimentosRN->validarPreCondicoesExpedirProcedimento($objInfraException, $objProcedimentoDTO, $strProtocoloFormatado);
            }
        }
    }    
} 
catch(\InfraException $e) { 

    $objInfraException->adicionarValidacao($e->getTraceAsString());
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