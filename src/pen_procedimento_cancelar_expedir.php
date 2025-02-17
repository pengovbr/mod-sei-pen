<?php

require_once DIR_SEI_WEB . '/SEI.php';

session_start();

$strMensagem = "O trâmite externo do processo foi cancelado com sucesso!";

try {
    InfraDebug::getInstance()->setBolLigado(false);
    InfraDebug::getInstance()->setBolDebugInfra(false);
    InfraDebug::getInstance()->limpar();

    SessaoSEI::getInstance()->validarLink();

    $objPaginaSEI = PaginaSEI::getInstance();

    $strParametros = '';
  if (isset($_GET['arvore'])) {
      $objPaginaSEI->setBolArvore($_GET['arvore']);
      $strParametros .= '&arvore=' . $_GET['arvore'];
  }

  if (isset($_GET['id_procedimento'])) {
      $strParametros .= '&id_procedimento=' . $_GET['id_procedimento'];
  }

    $idProcedimento = filter_var($_GET['id_procedimento'], FILTER_SANITIZE_NUMBER_INT);

    $objPenBlocoProcessoDTO = new PenBlocoProcessoDTO();
    $objPenBlocoProcessoDTO->setDblIdProtocolo($idProcedimento);
    $objPenBlocoProcessoDTO->setOrdNumIdBloco(InfraDTO::$TIPO_ORDENACAO_DESC);
    $objPenBlocoProcessoDTO->retDblIdProtocolo();
    $objPenBlocoProcessoDTO->retNumIdBloco();
    $objPenBlocoProcessoDTO->setNumIdAtividade(
        [ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CIENCIA_RECUSA, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO_AUTOMATICAMENTE, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE],
        InfraDTO::$OPER_NOT_IN
    );
    $objPenBlocoProcessoDTO->setNumMaxRegistrosRetorno(1);

    $objPenBlocoProcessoRN = new PenBlocoProcessoRN();
    $PenBlocoProcessoDTO = $objPenBlocoProcessoRN->consultar($objPenBlocoProcessoDTO);

    $objExpedirProcedimentosRN = new ExpedirProcedimentoRN();
    $objExpedirProcedimentosRN->cancelarTramite($idProcedimento);

  if ($PenBlocoProcessoDTO != null) {
      // TODO: tratar atualização a partir de um metodo
      $objPenBlocoProcessoRN = new PenBlocoProcessoRN();
      $objPenBlocoProcessoRN->atualizarEstadoDoBloco($PenBlocoProcessoDTO->getNumIdBloco());
  }
} catch (InfraException $e) {
    $strMensagem = $e->getStrDescricao();
} catch (Exception $e) {
    $strMensagem = $e->getMessage();
}
?>
<?php
$objPaginaSEI->montarDocType();
$objPaginaSEI->abrirHtml();
$objPaginaSEI->abrirHead();
$objPaginaSEI->montarMeta();
$objPaginaSEI->montarTitle(':: ' . $objPaginaSEI->getStrNomeSistema() . ' - ' . $strTitulo . ' ::');
$objPaginaSEI->montarStyle();
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody();
?>
<link rel="stylesheet" href="<?php print PENIntegracao::getDiretorio(); ?>/css/style-modulos.css" type="text/css" />

<script type="text/javascript">
  alert('<?php echo $strMensagem ?>');
  parent.parent.location.reload();
</script>
<?php
$objPaginaSEI->montarAreaDebug();
$objPaginaSEI->fecharBody();
$objPaginaSEI->fecharHtml();
?>