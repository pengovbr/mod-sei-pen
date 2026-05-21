<?php
require_once DIR_SEI_WEB . '/SEI.php';

session_start();

$objPaginaSEI = null;
$objSessaoSEI = null;
$strParametros = '';
$idProcedimento = '';

$objInfraException = new InfraException();

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
        $mensagem = "Ainda não e possível solicitar a sincronização para esse processo. É necessário realizar o envio do processo para outro órgão primeiro.";
        $objInfraException->adicionarValidacao($mensagem);
        throw new InfraException($mensagem);
      }

      $objAtividadeDTO = new AtividadeDTO();
      $objAtividadeDTO->setDistinct(true);
      $objAtividadeDTO->retStrSiglaUnidade();
      $objAtividadeDTO->setOrdStrSiglaUnidade(InfraDTO::$TIPO_ORDENACAO_ASC);
      $objAtividadeDTO->setDblIdProtocolo($objProcedimentoDTO->getDblIdProcedimento());
      $objAtividadeDTO->setDthConclusao(null);

      $objAtividadeRN = new AtividadeRN();
      $arrObjAtividadeDTO = $objAtividadeRN->listarRN0036($objAtividadeDTO);

    if(isset($arrObjAtividadeDTO) && count($arrObjAtividadeDTO) > 1) {
        $strSiglaUnidade = implode(', ', InfraArray::converterArrInfraDTO($arrObjAtividadeDTO, 'SiglaUnidade'));
        $mensagem = "Atenção! Não é possível iniciar a sincronização de processos abertos em mais de uma unidade. "
          . "Conclua o processo nas demais unidades antes de solicitar uma nova sincronia. (Processo aberto em: $strSiglaUnidade)";
        $objInfraException->adicionarValidacao($mensagem);
        throw new InfraException($mensagem);
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

        $objAtividadeDTO = $objAtividadeRN->consultarRN0033($objAtividadeDTO);

        if($objAtividadeDTO) {
          $objAtividadeRN = new AtividadeRN();
          $objAtividadeRN->concluirRN0726([$objAtividadeDTO]);
        }

        $objProcessoEletronicoRN->bloquearProcesso($idProcedimento);
        $objProcessoEletronicoRN->solicitarSincronizarTramite($objTramiteDTO->getNumIdTramite());
        // Atividade de pedido de sincronização para múltiplos órgãos manual - só adicionada após sucesso na solicitação de sincronização
        $objProcessoEletronicoRN->gravarAtividadeMultiplosOrgaos($objProcedimentoDTO, $objTramiteDTO->getNumIdTramite(), ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PEDIDO_SINC_MANUAL_MULTIPLOS_ORGAOS);
      } else {
        // Já existe uma pendência de sincronização para esse processo. Aguarde a finalização da sincronização.
      }
    }

    $objPaginaSEI->setStrMensagem('Solicitação de sincronização realizada com sucesso.', 5);
    header('Location: '.$objSessaoSEI->assinarLink('controlador.php?acao=pen_procedimento_sincronizar&acao_origem='.$_GET['acao'].'&arvore='.$_GET['arvore'].'&sincronizado=1'.$strParametros.$objPaginaSEI->montarAncora($idProcedimento)));
    exit(0);

  } else {
    $mensagem = '';
    if (isset($_GET['mensagem']) && $_GET['mensagem'] !== '') {
      $mensagem = urldecode($_GET['mensagem']);
    } else {
      $mensagem = $objPaginaSEI->getStrMensagens();
    }
    if ($mensagem !== '') {
      $objPaginaSEI->setStrMensagem('');
      ?>
      <div class="infraAreaAviso" style="background-color: #fff3cd; border: 1px solid #ffc107; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
          <img style="vertical-align: middle; margin-right: 8px; width: 25px;" src="<?php echo PENIntegracao::getDiretorioImagens() . '/icone-recusa.svg'; ?>" alt="Aviso" class="infraImg" />
          <span style="color: #856404; font-weight: bold;">
              <?php echo PaginaSEI::tratarHTML($mensagem); ?></strong>
          </span>
      </div>
      <?php
    }
    exit(0);
  }
  
} catch (InfraException $e) {
  $mensagem = 'Ocorreu um erro na sincronização do processo para múltiplos órgãos.';
  if ($objInfraException->contemValidacoes()) {
    $validacao = $objInfraException->getArrObjInfraValidacao();
    $mensagem = $validacao[0]->getStrDescricao();
  }
  if ($objPaginaSEI !== null) {
    $objPaginaSEI->setStrMensagem($mensagem, InfraPagina::$TIPO_MSG_AVISO);
  }
  $strAcao = isset($_GET['acao']) ? $_GET['acao'] : '';
  $strArvore = isset($_GET['arvore']) ? $_GET['arvore'] : '';
  $strAncora = ($objPaginaSEI !== null && $idProcedimento !== '') ? $objPaginaSEI->montarAncora($idProcedimento) : '';
  $strParametrosErro = $strParametros . '&mensagem=' . urlencode($mensagem);
  $strUrl = 'controlador.php?acao=pen_procedimento_sincronizar&acao_origem=' . $strAcao . '&arvore=' . $strArvore . '&sincronizado=1' . $strParametrosErro . $strAncora;
  if ($objSessaoSEI !== null) {
    $strUrl = $objSessaoSEI->assinarLink($strUrl);
  }
  header('Location: '.$strUrl);
  exit(0);
} catch (Exception $e) {
  $mensagem = 'Ocorreu um erro na sincronização do processo para múltiplos órgãos.';
  if ($objPaginaSEI !== null) {
    $objPaginaSEI->setStrMensagem($mensagem, InfraPagina::$TIPO_MSG_AVISO);
  }
  $strAcao = isset($_GET['acao']) ? $_GET['acao'] : '';
  $strArvore = isset($_GET['arvore']) ? $_GET['arvore'] : '';
  $strAncora = ($objPaginaSEI !== null && $idProcedimento !== '') ? $objPaginaSEI->montarAncora($idProcedimento) : '';
  $strParametrosErro = $strParametros . '&mensagem=' . urlencode($mensagem);
  $strUrl = 'controlador.php?acao=pen_procedimento_sincronizar&acao_origem=' . $strAcao . '&arvore=' . $strArvore . '&sincronizado=1' . $strParametrosErro . $strAncora;
  if ($objSessaoSEI !== null) {
    $strUrl = $objSessaoSEI->assinarLink($strUrl);
  }
  header('Location: '.$strUrl);
  exit(0);
}

?>