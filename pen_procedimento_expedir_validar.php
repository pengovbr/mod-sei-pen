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