<?php

require_once __DIR__ . '/../../SEI.php';
SessaoSEI::getInstance(false)->simularLogin(SessaoSEI::$USUARIO_SEI, SessaoSEI::$UNIDADE_TESTE);
/*
  ###############################CONSULTAR ASSUNTO#############################################
  //FILTROS PASSADOS POR PARÂMETRO
  $ID = 0;
  $FILTER = '';
  $START = 0;
  $LIMIT = 0;

  //INSTANCIA O DTO E INFORMA OS FILTROS DA BUSCA
  $assuntoDTO = new AssuntoDTO();

  IF($ID)
  $assuntoDTO->setNumIdAssunto($ID);

  if($FILTER)
  $assuntoDTO->setStrCodigoEstruturado('%'.$FILTER.'%',InfraDTO::$OPER_LIKE);
  $assuntoDTO->setStrDescricao('%'.$FILTER.'%',InfraDTO::$OPER_LIKE);

  IF($START)
  $assuntoDTO->setNumMaxRegistrosRetorno($LIMIT);

  IF($LIMIT)
  $assuntoDTO->setNumPaginaAtual($START);

  $assuntoDTO->retNumIdAssunto();
  $assuntoDTO->retStrCodigoEstruturado();
  $assuntoDTO->retStrDescricao();

  // REALIZA A CHAMADA DA DE ASSUNTOS
  $assuntoRN = new AssuntoRN();
  $arrAssuntoDTO = $assuntoRN->listarRN0247($assuntoDTO);


  ###################PESQUISAR ASSUNTOS###################################################
  $ID = 0;
  $FILTER = '';
  $START = 0;
  $LIMIT = 5;

  $contatoDTO = new ContatoDTO();

  if($ID)
  $contatoDTO->setNumIdContato($ID);

  if($FILTER)
  $contatoDTO->setStrNome('%'.$FILTER.'%',InfraDTO::$OPER_LIKE);

  IF($LIMIT)
  $contatoDTO->setNumMaxRegistrosRetorno($LIMIT);

  IF($START)
  $contatoDTO->setNumPaginaAtual($START);

  $contatoDTO->retNumIdContato();
  $contatoDTO->retStrSigla();
  $contatoDTO->retStrNome();

  $contatoRN = new ContatoRN();
  $arrContatoDTO = $contatoRN->listarRN0325($contatoDTO);

  ###################PESQUISAR TIPOS DE DOCUMENTO###################################################
 */

###################TEMPLATE DE CRIAÇÃO DE DOCUMENTO DE UM TIPO ESPECÍFICO############################################
$ID_TIPO_DOCUMENTO = 46;

//Consulta os assuntos sugeridos para um tipo de documento
$relSerieAssuntoDTO = new RelSerieAssuntoDTO();
$relSerieAssuntoDTO->setNumIdSerie($ID_TIPO_DOCUMENTO); // FILTRO PELO TIPO DE DOCUMENTO
$relSerieAssuntoDTO->retNumIdAssuntoProxy(); // ID DO ASSUNTO QUE DEVE SE RETORNADO
$relSerieAssuntoDTO->retStrCodigoEstruturadoAssunto(); // CÓDIGO DO ASSUNTO QUE DEVE SE RETORNADO
$relSerieAssuntoDTO->retStrDescricaoAssunto(); // DESCRIÇÃO DO ASSUNTO

$relSerieAssuntoRN = new RelSerieAssuntoRN();
$arrRelSerieAssuntoDTO = $relSerieAssuntoRN->listar($relSerieAssuntoDTO);

// Consulta se o tipo de documento permite a inclusão de destinatários e interessados
$serieDTO = new SerieDTO();
$serieDTO->setNumIdSerie($ID_TIPO_DOCUMENTO);
$serieDTO->retStrSinDestinatario();
$serieDTO->retStrSinInteressado();

$serieRN = new SerieRN();
$arrSerieDTO = $serieRN->listarRN0646($serieDTO);

######################PESQUISAR HIPÓTESES LEGAIS ############################################

$ID = 0;
$FILTER = '';
$NIVEL_ACESSO = 2;
$START = 0;
$LIMIT = 5;

$hipoteseLegalDTO = new HipoteseLegalDTO();

if ($ID)
    $hipoteseLegalDTO->setNumIdHipoteseLegal($ID);

if ($NIVEL_ACESSO)
    $hipoteseLegalDTO->setStrStaNivelAcesso($NIVEL_ACESSO);

if ($FILTER)
    $hipoteseLegalDTO->setStrNome('%' . $FILTER . '%', InfraDTO::$OPER_LIKE);

IF ($LIMIT)
    $hipoteseLegalDTO->setNumMaxRegistrosRetorno($LIMIT);

IF ($START)
    $hipoteseLegalDTO->setNumPaginaAtual($START);

$hipoteseLegalDTO->retNumIdHipoteseLegal();
$hipoteseLegalDTO->retStrNome();

$hipoteseLegalRN = new HipoteseLegalRN();
$arrHipoteseLegalDTO = $hipoteseLegalRN->listar($hipoteseLegalDTO);

######################PESQUISAR HIPÓTESES LEGAIS ############################################

var_dump($arrHipoteseLegalDTO);