<?php
  
require_once DIR_SEI_WEB.'/SEI.php';

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

    $idProcedimento = filter_var( $_GET['id_procedimento'], FILTER_SANITIZE_NUMBER_INT);
   
// verificar se o processo está em algum bloco
    $objTramiteEmBlocoProtocoloDTO = new TramitaEmBlocoProtocoloDTO();
    $objTramiteEmBlocoProtocoloDTO->setDblIdProtocolo($idProcedimento);
    $objTramiteEmBlocoProtocoloDTO->retDblIdProtocolo();
    $objTramiteEmBlocoProtocoloDTO->retNumIdTramitaEmBloco();

    $objTramitaEmBlocoProtocoloRN = new TramitaEmBlocoProtocoloRN();
    $tramiteEmBlocoProtocoloDTO = $objTramitaEmBlocoProtocoloRN->consultar($objTramiteEmBlocoProtocoloDTO);

    if ($tramiteEmBlocoProtocoloDTO != null) {
        $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
        $objTramiteEmBlocoDTO->setNumId($tramiteEmBlocoProtocoloDTO->getNumIdTramitaEmBloco());
        $objTramiteEmBlocoDTO->setStrStaEstado(TramiteEmBlocoRN::$TE_CONCLUIDO_PARCIALMENTE);
    
        $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
        $objTramiteEmBlocoRN->alterar($objTramiteEmBlocoDTO);
    }
   
    $objExpedirProcedimentosRN = new ExpedirProcedimentoRN();
    $objExpedirProcedimentosRN->cancelarTramite($idProcedimento); 

}
catch(InfraException $e){
    $strMensagem = $e->getStrDescricao();
}
catch(Exception $e) {
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
<?php //$objPaginaSEI->montarBarraComandosSuperior($arrComandos); ?>
<script type="text/javascript">
    alert('<?php echo $strMensagem ?>');
    parent.parent.location.reload();
</script>
<?php
$objPaginaSEI->montarAreaDebug();
$objPaginaSEI->fecharBody();
$objPaginaSEI->fecharHtml();
?>
