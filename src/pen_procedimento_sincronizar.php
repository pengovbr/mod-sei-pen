<?php

require_once DIR_SEI_WEB . '/SEI.php';

session_start();

try {
    InfraDebug::getInstance()->setBolLigado(false);
    InfraDebug::getInstance()->setBolDebugInfra(false);
    InfraDebug::getInstance()->limpar();

    SessaoSEI::getInstance()->validarLink();

    $objPaginaSEI = PaginaSEI::getInstance();

    $sincronizado = filter_var($_GET['sincronizado'], FILTER_SANITIZE_NUMBER_INT);
    $idProcedimento = filter_var($_GET['id_procedimento'], FILTER_SANITIZE_NUMBER_INT);
    

    $strParametros = '';
  if (isset($_GET['arvore'])) {
      $objPaginaSEI->setBolArvore($_GET['arvore']);
      $strParametros .= '&arvore=' . $_GET['arvore'];
  }

  if (isset($_GET['id_procedimento'])) {
      $strParametros .= '&id_procedimento=' . $_GET['id_procedimento'];
  }

  if (is_null($sincronizado) || $sincronizado == '') {
    $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
    $objProcessoEletronicoDTO->setDblIdProcedimento($idProcedimento);
    $objTramiteBD = new TramiteBD(BancoSEI::getInstance());

    $objTramiteDTO = $objTramiteBD->consultarPrimeiroTramite($objProcessoEletronicoDTO, ProcessoEletronicoRN::$STA_TIPO_TRAMITE_RECEBIMENTO);
    if ($objTramiteDTO) {
      $objProcessoEletronicoRN = new ProcessoEletronicoRN();
      $numNRE = $objTramiteDTO->getStrNumeroRegistro();

      $tramitePendencia = $objProcessoEletronicoRN->consultarTramites(null, $numNRE, null, null, null, null, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_SOLICITACAO_PENDENCIA);
      
      if (count($tramitePendencia) == 0) {
        $idTarefa = ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MANUAL_MULTIPLOS_ORGAOS);

        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDblIdProtocolo($idProcedimento);
        $objAtividadeDTO->setDthConclusao(null);
        $objAtividadeDTO->setDistinct(true);
        $objAtividadeDTO->setNumIdTarefa($idTarefa);
        $objAtividadeDTO->setNumMaxRegistrosRetorno(1);
        $objAtividadeDTO->retTodos();

        $objAtividadeRN = new AtividadeRN();
        $objAtividadeDTO = $objAtividadeRN->consultarRN0033($objAtividadeDTO);

        if($objAtividadeDTO) {
          $objAtividadeRN = new AtividadeRN();
          $objAtividadeRN->concluirRN0726([$objAtividadeDTO]);
        }

        $objExpedirProcedimentoRN = new ExpedirProcedimentoRN();
        $objProcedimentoDTO = $objExpedirProcedimentoRN->consultarProcedimento($idProcedimento);

        $objProcessoEletronicoRN->gravarAtividadeMuiltiplosOrgaos($objProcedimentoDTO, $objTramiteDTO->getNumIdTramite(), ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MANUAL_MULTIPLOS_ORGAOS);

        
        $objProcessoEletronicoRN->solicitarSincronizarTramite($objTramiteDTO->getNumIdTramite());
      }
    } 

    header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_procedimento_sincronizar&acao_origem=' . $_GET['acao'] .'&arvore=1&sincronizado=1' . $strParametros . PaginaSEI::getInstance()->montarAncora($idProcedimento)));
    exit(0);
  } else {
    echo 'A sincronização do processo foi solicitada com sucesso.';
    exit(0);
  }
  
} catch (InfraException $e) {
    echo 'Erro: ' . $e->getMessage();
} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage();
}

?>