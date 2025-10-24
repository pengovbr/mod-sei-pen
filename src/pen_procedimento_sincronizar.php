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