<?php

require_once DIR_SEI_WEB . '/SEI.php';

session_start();

try {
    InfraDebug::getInstance()->setBolLigado(false);
    InfraDebug::getInstance()->setBolDebugInfra(false);
    InfraDebug::getInstance()->limpar();

    SessaoSEI::getInstance()->validarLink();

    $objPaginaSEI = PaginaSEI::getInstance();
    $objSessaoSEI = SessaoSEI::getInstance();

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
    $objSincronizacaoExpedirProcedimentoRN = new SincronizacaoExpedirProcedimentoRN();
    $objSincronizacaoExpedirProcedimentoRN->validarSincronizacaoProcessoSigiloso($idProcedimento);
    
    $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
    $objProcessoEletronicoDTO->setDblIdProcedimento($idProcedimento);
    $objTramiteBD = new TramiteBD(BancoSEI::getInstance());

    $objTramiteDTO = $objTramiteBD->consultarPrimeiroTramite($objProcessoEletronicoDTO, ProcessoEletronicoRN::$STA_TIPO_TRAMITE_RECEBIMENTO);
    if ($objTramiteDTO) {
      $numNRE = $objTramiteDTO->getStrNumeroRegistro();

      $objExpedirProcedimentoRN = new ExpedirProcedimentoRN();
      $objProcedimentoDTO = $objExpedirProcedimentoRN->consultarProcedimento($idProcedimento);

      $objProcessoEletronicoRN = new ProcessoEletronicoRN();
      $tramitePendencia = $objProcessoEletronicoRN->consultarTramites(
        null, null, null,
        $objTramiteDTO->getNumIdEstruturaDestino(),
        $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado(),
        $objTramiteDTO->getNumIdRepositorioDestino()
      );
      if (count($tramitePendencia) == 0) {
        throw new InfraException('Ainda não e possível solicitar a sincronização para esse processo. É necessário realizar o envio do processo para outro órgão primeiro.');
      }

      $objProcessoEletronicoRN->validarProcessoRecusaCancelamento($idProcedimento);
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

        $objProcessoEletronicoRN->gravarAtividadeMuiltiplosOrgaos($objProcedimentoDTO, $objTramiteDTO->getNumIdTramite(), ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MANUAL_MULTIPLOS_ORGAOS);

        $objProcessoEletronicoRN->solicitarSincronizarTramite($objTramiteDTO->getNumIdTramite());
      } else {
        // Já existe uma pendência de sincronização para esse processo. Aguarde a finalização da sincronização.
      }
    }

    $objPaginaSEI->setStrMensagem('Solicitação de sincronização realizada com sucesso.', InfraPagina::$TIPO_MSG_AVISO);
    header('Location: '.$objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_sincronizar&acao_origem='.$_GET['acao'].'&arvore='.$_GET['arvore'].'&sincronizado=1'.$objPaginaSEI->montarAncora($idProcedimento).$strParametros));
    exit(0);

  } else {
    $mensagem = $objPaginaSEI->getStrMensagens();
    if ($mensagem !== '') {
      $objPaginaSEI->setStrMensagem('');
      ?>
      <script type="text/javascript">
        alert('<?php echo $mensagem ?>');
        parent.parent.location.reload();
      </script>
      <?php
    }
    // echo 'A sincronização do processo foi solicitada com sucesso.';
    exit(0);
  }
  
} catch (InfraException $e) {
  $objPaginaSEI->setStrMensagem($e->getMessage(), InfraPagina::$TIPO_MSG_AVISO);
  header('Location: '.$objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_sincronizar&acao_origem='.$_GET['acao'].'&arvore='.$_GET['arvore'].'&sincronizado=1'.$objPaginaSEI->montarAncora($idProcedimento).$strParametros));
  exit(0);
} catch (Exception $e) {
  $objPaginaSEI->setStrMensagem($e->getMessage(), InfraPagina::$TIPO_MSG_AVISO);
  header('Location: '.$objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_sincronizar&acao_origem='.$_GET['acao'].'&arvore='.$_GET['arvore'].'&sincronizado=1'.$objPaginaSEI->montarAncora($idProcedimento).$strParametros));
  exit(0);
}

?>