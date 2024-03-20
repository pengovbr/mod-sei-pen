<?php
try {
  require_once DIR_SEI_WEB . '/SEI.php';

  session_start();

  $objSessaoSEI = SessaoSEI::getInstance();
  $objPaginaSEI = PaginaSEI::getInstance();

  $objSessaoSEI->validarLink();

  $objPaginaSEI->salvarCamposPost(array('hdnIdProtocolo', 'selBlocos'));
  $strIdItensSelecionados = $objPaginaSEI->recuperarCampo('hdnIdProtocolo');
  $idBlocoExterno = $objPaginaSEI->recuperarCampo('selBlocos');

  $strParametros = '';
  if (isset($_GET['arvore'])) {
    PaginaSEI::getInstance()->setBolArvore($_GET['arvore']);
    $strParametros .= '&arvore=' . $_GET['arvore'];
  }

  if (isset($_GET['id_procedimento'])) {
    $strParametros .= "&id_procedimento=" . $_GET['id_procedimento'];
  }

  if (isset($strIdItensSelecionados)) {
    $strParametros .= "&processos=" . $strIdItensSelecionados;
  }

  if (isset($_GET['processos']) && !empty($_GET['processos'])) {
    $strParametros .= "&processos=" . $_GET['processos'];
  }

  $arrComandos = [];
  $arrComandos[] = '<button type="submit" accesskey="S" name="sbmCadastrarProcessoEmBloco" value="Salvar" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
  $arrComandos[] = '<button type="button" accesskey="C" name="btnCancelar" id="btnCancelar" value="Cancelar" onclick="location.href=\'' . $objSessaoSEI->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $acao . $strParametros) . '\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';
  switch ($_GET['acao']) {
    case 'pen_incluir_processo_em_bloco_tramite':
      $objSessaoSEI->validarPermissao($_GET['acao']);

      $strTitulo = 'Incluir Processo no Bloco de Trâmite';

      $objProcedimentoDTO = new ProcedimentoDTO();
      $objProcedimentoDTO->retStrProtocoloProcedimentoFormatado();
      $objProcedimentoDTO->setDblIdProcedimento($_GET['id_procedimento']);

      $objProcedimentoRN = new ProcedimentoRN();
      $procedimento = $objProcedimentoRN->consultarRN0201($objProcedimentoDTO);

      if (isset($_POST['sbmCadastrarProcessoEmBloco'])) {
        try {
          if ($_POST['selBlocos'] == null) {
            header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao']));
            exit(0);
          }

          $objTramiteEmBlocoProtocoloDTO = new TramitaEmBlocoProtocoloDTO();

          $objTramiteEmBlocoProtocoloDTO->setNumId(null);
          $objTramiteEmBlocoProtocoloDTO->setDblIdProtocolo($_GET['id_procedimento']);
          $objTramiteEmBlocoProtocoloDTO->setNumIdTramitaEmBloco($_POST['selBlocos']);
          $objTramiteEmBlocoProtocoloDTO->setStrIdxRelBlocoProtocolo($procedimento->getStrProtocoloProcedimentoFormatado());

          $objTramiteEmBlocoProtocoloRN = new TramitaEmBlocoProtocoloRN();
          $validar = $objTramiteEmBlocoProtocoloRN->validarBlocoDeTramite($_GET['id_procedimento']);

          if ($validar) {
            $objPaginaSEI->adicionarMensagem($validar, InfraPagina::$TIPO_MSG_AVISO);
            header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao']));
            exit(0);
          }

          $objTramiteEmBlocoProtocoloDTO = $objTramiteEmBlocoProtocoloRN->cadastrar($objTramiteEmBlocoProtocoloDTO);
          $objPaginaSEI->adicionarMensagem('Processo "' . $procedimento->getStrProtocoloProcedimentoFormatado() . '" adicionado ao bloco', InfraPagina::$TIPO_MSG_AVISO);
        } catch (Exception $e) {
          PaginaSEI::getInstance()->processarExcecao($e);
        }
        header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao']));
        exit(0);
      }
        break;
    case 'pen_tramita_em_bloco_adicionar':
      $arrProtocolosOrigem = array_merge($objPaginaSEI->getArrStrItensSelecionados('Gerados'), $objPaginaSEI->getArrStrItensSelecionados('Recebidos'));
      $strIdItensSelecionados = $strIdItensSelecionados ?: $_GET['processos'];
      $strTitulo = 'Incluir Processo(s) no Bloco de Trâmite';

      if (isset($_POST['sbmCadastrarProcessoEmBloco'])) {

        try {
          $erros = [];
          $sucesso = false;
          $arrProtocolosOrigemProtocolo = explode(',', $strIdItensSelecionados);

          foreach ($arrProtocolosOrigemProtocolo as $idItensSelecionados) {

            $tramitaEmBlocoProtocoloDTO = new TramitaEmBlocoProtocoloDTO();
            $tramitaEmBlocoProtocoloDTO->setDblIdProtocolo($idItensSelecionados);
            $tramitaEmBlocoProtocoloDTO->setNumIdTramitaEmBloco($idBlocoExterno);
            $tramitaEmBlocoProtocoloDTO->retNumId();
            $tramitaEmBlocoProtocoloDTO->retNumIdTramitaEmBloco();
            $tramitaEmBlocoProtocoloDTO->retStrIdxRelBlocoProtocolo();

            $tramitaEmBlocoProtocoloRN = new TramitaEmBlocoProtocoloRN();
            $validar = $tramitaEmBlocoProtocoloRN->validarBlocoDeTramite($idItensSelecionados);

            if ($validar == false) {
              $sucesso = true;
              $objProcedimentoDTO = new ProcedimentoDTO();
              $objProcedimentoDTO->retStrProtocoloProcedimentoFormatado();
              $objProcedimentoDTO->setDblIdProcedimento($idItensSelecionados);

              $objProcedimentoRN = new ProcedimentoRN();
              $procedimento = $objProcedimentoRN->consultarRN0201($objProcedimentoDTO);

              $tramitaEmBlocoProtocoloDTO->setStrIdxRelBlocoProtocolo($procedimento->getStrProtocoloProcedimentoFormatado());
              $objTramiteEmBlocoProtocoloDTO = $tramitaEmBlocoProtocoloRN->cadastrar($tramitaEmBlocoProtocoloDTO);
            } else {
              $erros = true;
              $objPaginaSEI->adicionarMensagem($validar, InfraPagina::$TIPO_MSG_ERRO);
            }
          }

          if ($sucesso) {
            $mensagemSucesso = "Processo(s) incluído(s) com sucesso no bloco {$idBlocoExterno}";
          }

          if ($sucesso && $erros) {
            $mensagemSucesso = "Os demais processos selecionados foram incluídos com sucesso no bloco {$idBlocoExterno}";
          }

          $objPaginaSEI->adicionarMensagem($mensagemSucesso, 5);
        } catch (Exception $e) {
          PaginaSEI::getInstance()->processarExcecao($e);
        }
      }
        break;
    default:
        throw new InfraException("Ação '" . $_GET['acao'] . "' não reconhecida.");
  }

  //Monta o select dos blocos
  $arrMapIdBloco = array();

  $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
  $objTramiteEmBlocoDTO->setStrStaEstado(TramiteEmBlocoRN::$TE_ABERTO);
  $objTramiteEmBlocoDTO->retNumId();
  $objTramiteEmBlocoDTO->retNumIdUnidade();
  $objTramiteEmBlocoDTO->retStrDescricao();
  PaginaSEI::getInstance()->prepararOrdenacao($objTramiteEmBlocoDTO, 'Id', InfraDTO::$TIPO_ORDENACAO_DESC);

  $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
  foreach ($objTramiteEmBlocoRN->listar($objTramiteEmBlocoDTO) as $dados) {
    $arrMapIdBloco[$dados->getNumId()] = "{$dados->getNumId()} - {$dados->getStrDescricao()}";
  }
} catch (Exception $e) {
  PaginaSEI::getInstance()->processarExcecao($e);
}
// View
ob_clean();

$objPaginaSEI->montarDocType();
$objPaginaSEI->abrirHtml();
$objPaginaSEI->abrirHead();
$objPaginaSEI->montarMeta();
$objPaginaSEI->montarTitle($objPaginaSEI->getStrNomeSistema() . ' - ' . $strTitulo);
$objPaginaSEI->montarStyle();
$objPaginaSEI->abrirStyle();

?>
#divIdentificacao {display:none;}
#lblBlocos{position:absolute;left:0%;top:0%;width:60%;min-width:250px;}
#selBlocos{position:absolute;left:0%;top:13%;width:60%;min-width:250px;}

<?
$objPaginaSEI->fecharStyle();
$objPaginaSEI->montarJavaScript();
$objPaginaSEI->abrirJavaScript();
$acao=$_GET['acao'];
?>

function inicializar(){

if ('<?=$acao ?>'=='pen_incluir_processo_em_bloco_tramite') {
document.getElementById('divIdentificacao').style.display = 'none';
document.getElementById('selBlocos').focus();
} else {
document.getElementById('divIdentificacao').style.display = 'block';
document.getElementById('btnCancelar').focus();
}
}

<?php
$objPaginaSEI->fecharJavaScript();
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody($strTitulo, 'onload="inicializar();"');
?>

<form id="frmProcessoEmBlocoCadastro" method="post" action="<?= SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . $acao . '&acao_origem=' . $acao . $strParametros) ?>">

  <?php
  $objPaginaSEI->montarBarraComandosSuperior($arrComandos);
  $objPaginaSEI->abrirAreaDados('15em');
  $padrao = null;
  if (isset($arrMapIdBloco[$idBlocoExterno])) {
    $padrao = $idBlocoExterno;
  }
  ?>
  

  <label id="lblBlocos" for="lblIdBloco" class="infraLabelObrigatorio">Blocos que estão em aberto:</label>
  <select id="selBlocos" name="selBlocos" class="infraSelect">
    <?php print InfraINT::montarSelectArray(null, $padrao, $padrao, array_filter($arrMapIdBloco)); ?>
  </select>

  <input type="hidden" id="hdnIdBloco" name="hdnIdBloco" value="" />
  <input type="hidden" id="hdnIdProtocolo" name="hdnIdProtocolo" tabindex="<?= PaginaSEI::getInstance()->getProxTabDados() ?>" value="<?= $arrProtocolosOrigem ? implode(',', $arrProtocolosOrigem) : '' ?>" />

  <?php
  $objPaginaSEI->fecharAreaDados();
  $objPaginaSEI->montarBarraComandosInferior($arrComandos);
  ?>

</form>

<?php
$objPaginaSEI->fecharBody();
$objPaginaSEI->fecharHtml();
?>