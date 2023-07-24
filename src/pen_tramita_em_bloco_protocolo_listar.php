<?php
try {
  require_once DIR_SEI_WEB . '/SEI.php';

  session_start();

  $objSessaoSEI = SessaoSEI::getInstance();
  $objPaginaSEI = PaginaSEI::getInstance();

  $objSessaoSEI->validarLink();
  $objSessaoSEI->validarPermissao($_GET['acao']);

  $staCancelado = ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO;
  $staConcluido = ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_ENVIADOS_REMETENTE;
  $staEmProcessamento = ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_NAO_INICIADO;

  $objPaginaSEI->salvarCamposPost(array('txtProcedimentoFormatado', 'txtNomeUsuario', 'txtUnidadeDestino', 'selAndamento'));

  switch ($_GET['acao']) {
    case 'pen_tramita_em_bloco_protocolo_excluir':
      try {
        $arrStrIds = PaginaSEI::getInstance()->getArrStrItensSelecionados();
        $arrObjTramiteBlocoProtocoloDTO = array();
        if (count($arrStrIds) > 0) {
          for ($i = 0; $i < count($arrStrIds); $i++) {
            $arrStrIdComposto = explode('-', $arrStrIds[$i]);
            $objTramiteEmBlocoProtocoloDTO = new TramitaEmBlocoProtocoloDTO();
            $objTramiteEmBlocoProtocoloDTO->setNumId($arrStrIdComposto[0]);
            $objTramiteEmBlocoProtocoloDTO->setDblIdProtocolo($arrStrIdComposto[1]);
            $objTramiteEmBlocoProtocoloDTO->setNumIdTramitaEmBloco($arrStrIdComposto[2]);
            $arrObjTramiteBlocoProtocoloDTO[] = $objTramiteEmBlocoProtocoloDTO;
          }
        } elseif (isset($_GET['hdnInfraItensSelecionados'])) {
          $arrStrIdComposto = explode('-', $_GET['hdnInfraItensSelecionados']);
          $objTramiteEmBlocoProtocoloDTO = new TramitaEmBlocoProtocoloDTO();
          $objTramiteEmBlocoProtocoloDTO->setNumId($arrStrIdComposto[0]);
          $objTramiteEmBlocoProtocoloDTO->setDblIdProtocolo($arrStrIdComposto[1]);
          $objTramiteEmBlocoProtocoloDTO->setNumIdTramitaEmBloco($arrStrIdComposto[2]);
          $arrObjTramiteBlocoProtocoloDTO[] = $objTramiteEmBlocoProtocoloDTO;
        }
        $objTramitaEmBlocoProtocoloRN = new TramitaEmBlocoProtocoloRN();
        $objTramitaEmBlocoProtocoloRN->excluir($arrObjTramiteBlocoProtocoloDTO);
        PaginaSEI::getInstance()->setStrMensagem('Operação realizada com sucesso.');
      } catch (Exception $e) {
        PaginaSEI::getInstance()->processarExcecao($e);
      }
      header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao'].'&id_bloco='.$_GET['id_bloco']));
      die;
    case 'pen_tramita_em_bloco_protocolo_listar':
      $strTitulo = 'Processos do Bloco: ' . $_GET['id_bloco'];
      break;
    case 'pen_tramita_em_bloco_protocolo_cancelar': {
      try{
        $arrStrIds = PaginaSEI::getInstance()->getArrStrItensSelecionados();
        $arrObjTramiteBlocoProtocoloDTO = array();
        var_dump($arrStrIds);
        if (count($arrStrIds) > 0) {
          foreach ($arrStrIds as $arrStrId) {
              $arrStrIdComposto = explode('-', $arrStrId);
              $expedirProcedimentoRN = new ExpedirProcedimentoRN();
              $expedirProcedimentoRN->cancelarTramite($arrStrIdComposto[1]);
          }
        } elseif (isset($_GET['hdnInfraItensSelecionados'])) {
            $arrStrIdComposto = explode('-', $_GET['hdnInfraItensSelecionados']);
            $expedirProcedimentoRN = new ExpedirProcedimentoRN();
            $expedirProcedimentoRN->cancelarTramite($arrStrIdComposto[1]);
        }
        PaginaSEI::getInstance()->setStrMensagem('Operação realizada com sucesso.');
      } catch (Exception $e) {
        PaginaSEI::getInstance()->processarExcecao($e);
      }
      header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao'].'&id_bloco='.$_GET['id_bloco']));
      //die;
    }
    break;
    default:
      throw new InfraException("Ação '" . $_GET['acao'] . "' não reconhecida.");
  }

  $arrComandos = array();
  $arrComandos[] = '<button type="button" accesskey="T" id="sbmTramitarBloco" value="Tramitar processos selecionados" onclick="onClickBtnTramitarProcessos();" class="infraButton"><span class="infraTeclaAtalho">T</span>ramitar processo(s) selecionado(s)</button>';
  $arrComandos[] = '<button type="submit" accesskey="P" id="sbmPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';

  $objTramitaEmBlocoProtocoloDTO = new TramitaEmBlocoProtocoloDTO();
  $objTramitaEmBlocoProtocoloDTO->retNumId();
  $objTramitaEmBlocoProtocoloDTO->retDblIdProtocolo();
  $objTramitaEmBlocoProtocoloDTO->retNumSequencia();
  $objTramitaEmBlocoProtocoloDTO->retStrAnotacao();
  $objTramitaEmBlocoProtocoloDTO->retStrIdxRelBlocoProtocolo();
  $objTramitaEmBlocoProtocoloDTO->retNumIdUsuario();
  $objTramitaEmBlocoProtocoloDTO->retNumIdUnidadeBloco();
  $objTramitaEmBlocoProtocoloDTO->retStrStaEstadoProtocolo();
  $objTramitaEmBlocoProtocoloDTO->setNumIdTramitaEmBloco($_GET['id_bloco']);

  $strPalavrasPesquisa = PaginaSEI::getInstance()->recuperarCampo('txtPalavrasPesquisaBloco');
  if ($strPalavrasPesquisa!=''){
    $objTramitaEmBlocoProtocoloDTO->setStrPalavrasPesquisa($strPalavrasPesquisa);
  }

  $objTramitaEmBlocoProtocoloRN = new TramitaEmBlocoProtocoloRN();
  $arrTramitaEmBlocoProtocoloDTO = $objTramitaEmBlocoProtocoloRN->listarProtocolosBloco($objTramitaEmBlocoProtocoloDTO);

  $arrComandos = array();

  $arrComandos[] = '<button type="button" accesskey="I" id="btnImprimir" value="Imprimir" onclick="infraImprimirTabela();" class="infraButton"><span class="infraTeclaAtalho">I</span>mprimir</button>';
  $arrComandos[] = '<button type="submit" accesskey="P" onclick="onClickBtnPesquisar()" id="sbmPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';

  $objPaginaSEI->prepararPaginacao($objTramitaEmBlocoProtocoloDTO);
  $objPaginaSEI->processarPaginacao($objTramitaEmBlocoProtocoloDTO);
  $objPaginaSEI->prepararOrdenacao($objTramitaEmBlocoProtocoloDTO, 'IdxRelBlocoProtocolo', InfraDTO::$TIPO_ORDENACAO_DESC);

  $numRegistros = count($arrTramitaEmBlocoProtocoloDTO);
  if ($numRegistros > 0) {
    $objPenLoteProcedimentoDTO = new PenLoteProcedimentoDTO();
    $arrComandos[] = '<button type="button" value="Cancelar" onclick="onClickBtnCancelarTramites()" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar Trâmites</button>';
    $arrComandos[] = '<button type="button" value="Excluir" onclick="onClickBtnExcluir()" class="infraButton"><span class="infraTeclaAtalho">E</span>xcluir</button>';

    $strResultado = '';
    $strSumarioTabela = 'Tabela de Processo em Lote.';
    $strCaptionTabela = 'Processo em Lote';

    $strResultado .= '<table width="99%" class="infraTable" summary="' . $strSumarioTabela . '">' . "\n";
    $strResultado .= '<caption class="infraCaption">' . $objPaginaSEI->gerarCaptionTabela($strCaptionTabela, $numRegistros) . '</caption>';
    $strResultado .= '<tr>';
    $strResultado .= '<th class="infraTh" width="1%">' . $objPaginaSEI->getThCheck() . '</th>' . "\n";
    $strResultado .= '<th class="infraTh" width="10%">' . $objPaginaSEI->getThOrdenacao($objTramitaEmBlocoProtocoloDTO, 'Seq', 'Sequencia', $arrTramitaEmBlocoProtocoloDTO) . '</th>' . "\n";
    $strResultado .= '<th class="infraTh">' . $objPaginaSEI->getThOrdenacao($objTramitaEmBlocoProtocoloDTO, 'Processo', 'IdxRelBlocoProtocolo', $arrTramitaEmBlocoProtocoloDTO) . '</th>' . "\n";
    $strResultado .= '<th class="infraTh">' . $objPaginaSEI->getThOrdenacao($objTramitaEmBlocoProtocoloDTO, 'Anotações', 'Anotacoes', $arrTramitaEmBlocoProtocoloDTO) . '</th>' . "\n";
    $strResultado .= '<th class="infraTh">Usuário</th>' . "\n";
    $strResultado .= '<th class="infraTh">Data do Envio</th>' . "\n";
    $strResultado .= '<th class="infraTh">Unidade Destino</th>' . "\n";
    $strResultado .= '<th class="infraTh">Situação</th>' . "\n";
    $strResultado .= '<th class="infraTh" width="10%">Ações</th>' . "\n";
    $strResultado .= '</tr>' . "\n";
    $strCssTr = '';
    foreach ($arrTramitaEmBlocoProtocoloDTO as $i => $objDTO) {
      $strCssTr = ($strCssTr == '<tr class="infraTrClara">') ? '<tr class="infraTrEscura">' : '<tr class="infraTrClara">';
      $strResultado .= $strCssTr;

      $id = $objDTO->getNumId().'-'.$objDTO->getDblIdProtocolo().'-'.$_GET['id_bloco'];
      $strResultado .= '<td valign="top">' . $objPaginaSEI->getTrCheck($i, $id, $id) . '</td>';
      $strResultado .= '<td align="center">' . ($i + 1) . '</td>';

      $strResultado .= '<td align="center">';
      $strResultado .= '<a onclick="infraLimparFormatarTrAcessada(this.parentNode.parentNode);" href="' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=procedimento_trabalhar&acao_origem=' . $_GET['acao'] . '&acao_retorno=' . $_GET['acao'] . '&id_procedimento=' . $objDTO->getDblIdProtocolo()) . '" target="_blank" tabindex="' . PaginaSEI::getInstance()->getProxTabTabela() . '" class="' . $strClassProtocolo . '" alt="" title="">' . $objDTO->getStrIdxRelBlocoProtocolo() . '</a>';
      $strResultado .= '</td>';

      $strResultado .= '<td>' . nl2br(InfraString::formatarXML($objDTO->getStrAnotacao())) . '</td>';

      $objTramiteDTO = $objDTO->getObjTramiteDTO();
      if ($objTramiteDTO) {
        $strResultado .= '<td align="center">' . PaginaSEI::tratarHTML($objTramiteDTO->getStrNomeUsuario()) . '</td>';
        $strResultado .= '<td align="center">' . PaginaSEI::tratarHTML($objTramiteDTO->getDthRegistro()) . '</td>';
        $strResultado .= '<td align="center">' . /*PaginaSEI::tratarHTML($objTramiteDTO->getStrUnidadeDestino()) .*/ '</td>';

      } else {
        $strResultado .= '<td align="center"></td>' . "\n";
        $strResultado .= '<td align="center"></td>' . "\n";
        $strResultado .= '<td align="center"></td>' . "\n";
      }

      $strResultado .= '<td align="center">' . "\n";

      if ($objDTO->getStrSinObteveRecusa() == 'S') {
        $strResultado .= '<img src="' . PENIntegracao::getDiretorio() . '/imagens/pen_tramite_recusado.png" title="Um trâmite para esse processo foi recusado" alt="Um trâmite para esse processo foi recusado" />';
      } else if ($objDTO->getStrStaEstadoProtocolo() == ProtocoloRN::$TE_PROCEDIMENTO_BLOQUEADO) {
        $strResultado .= '<img src="' . PENIntegracao::getDiretorio() . '/imagens/pen_em_processamento.png" title="Em processamento" alt="Em processamento" />';
      } else {
        $PROCESSO_EXPEDIDO_ID = ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO);
        $PROCESSO_TRAMITE_CANCELADO_ID = ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_CANCELADO);

        switch ($objDTO->getNumStaIdTarefa()) {
          case $PROCESSO_EXPEDIDO_ID:
            $strResultado .= '<img src="' . PENIntegracao::getDiretorio() . '/imagens/estado_sucesso.png" title="Concluído" alt="Concluído" />';
            break;
          case $PROCESSO_TRAMITE_CANCELADO_ID:
            $strResultado .= '<img src="' . PENIntegracao::getDiretorio() . '/imagens/estado_falhou.png" title="Cancelado" alt="Cancelado" />';
            break;
          default:
            break;
        }
      }
      $strResultado .= '</td>' . "\n";

      $strResultado .= '<td align="center">' . "\n";
      // $objDTO->getStrStaEstado() != TramiteEmBlocoRN::$TE_DISPONIBILIZADO &&
      if ($objDTO->getNumIdUnidadeBloco() == SessaoSEI::getInstance()->getNumIdUnidadeAtual()) {
        $strId = $objDTO->getDblIdProtocolo() . '-' . $objDTO->getNumId();
        $strProtocoloId = $objDTO->getDblIdProtocolo();
        $strDescricao = PaginaSEI::getInstance()->formatarParametrosJavaScript($objDTO->getStrIdxRelBlocoProtocolo());
        $strResultado .= '<a onclick="onCLickLinkDelete(\''.$objSessaoSEI->assinarLink('controlador.php?acao=pen_tramita_em_bloco_protocolo_excluir&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao'].'&hdnInfraItensSelecionados='.$id.'&id_bloco='.$_GET['id_bloco']).'\', this)" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="'.PaginaSEI::getInstance()->getIconeExcluir().'" title="Excluir Bloco" alt="Excluir Bloco" class="infraImg" /></a>&nbsp;';
        $strResultado .= $objDTO->getNumStaIdTarefa() == $PROCESSO_EXPEDIDO_ID ? '<a onclick="onClickBtnCancelarTramite(\''.$objSessaoSEI->assinarLink('controlador.php?acao=pen_tramita_em_bloco_protocolo_cancelar&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao'].'&hdnInfraItensSelecionados='.$id.'&id_bloco='.$_GET['id_bloco']).'\', this)" tabindex="' . ProcessoEletronicoINT::getCaminhoIcone("/pen_cancelar_envio.png", $this->getDiretorioImagens()) . '"><img src="' . ProcessoEletronicoINT::getCaminhoIcone("/pen_cancelar_envio.png", $this->getDiretorioImagens()) . '" title="Cancelar Tramite" alt="Cancelar Tramite" class="infraImg iconTramita" /></a>&nbsp;' : '';
      }
      $strResultado .= '</td>' . "\n";
      $strResultado .= '</tr>' . "\n";
    }
    $strResultado .= '</table>';
  }

  $arrComandos[] = '<button type="button" accesskey="F" id="btnFechar" value="Fechar" onclick="location.href=\'' . $objSessaoSEI->assinarLink('controlador.php?acao=md_pen_tramita_em_bloco&acao_origem=' . $_GET['acao'] . $objPaginaSEI->montarAncora($numIdGrupoSerie)) . '\'" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';
} catch (Exception $e) {
  $objPaginaSEI->processarExcecao($e);
}

$objPaginaSEI->montarDocType();
$objPaginaSEI->abrirHtml();
$objPaginaSEI->abrirHead();
$objPaginaSEI->montarMeta();
$objPaginaSEI->montarTitle($objPaginaSEI->getStrNomeSistema() . ' - ' . $strTitulo);
$objPaginaSEI->montarStyle();
$objPaginaSEI->abrirStyle();
?>

#lblProcedimentoFormatado {position:absolute;left:0%;top:0%;width:20%;}
#txtProcedimentoFormatado {position:absolute;left:0%;top:40%;width:20%;}
input.infraText {width: 100%;}
.iconTramita {max-width: 1.5rem;}

<?
$objPaginaSEI->fecharStyle();
$objPaginaSEI->montarJavaScript();
$objPaginaSEI->abrirJavaScript();
?>

function inicializar(){
if ('<?= $_GET['acao'] ?>'=='serie_selecionar'){
infraReceberSelecao();
document.getElementById('btnFecharSelecao').focus();
}else{
document.getElementById('btnFechar').focus();
}

infraEfeitoTabelas();
}

<?php $objPaginaSEI->fecharStyle(); ?>
<?php $objPaginaSEI->montarJavaScript(); ?>
<script type="text/javascript">
  function inicializar() {
    infraEfeitoTabelas();
  }

  function inicializar() {
    infraEfeitoTabelas();
    var strMensagens = '<?php print str_replace("\n", '\n', $objPaginaSEI->getStrMensagens()); ?>';
    if (strMensagens) {
      alert(strMensagens);
    }
  }

  function onClickBtnPesquisar() {
    var form = jQuery('#frmProcessosListar');
    form.attr('action', '<?php print $objSessaoSEI->assinarLink('controlador.php?acao=pen_tramita_em_bloco_protocolo_listar&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao'].'&id_bloco='.$_GET['id_bloco']); ?>');
    form.submit();
  }

  function tratarEnter(ev) {
    var key = infraGetCodigoTecla(ev);
    if (key == 13) {
      onClickBtnPesquisar();
    }
    return true;
  }

  function onCLickLinkDelete(url, link) {
    var row = jQuery(link).parents('tr:first');
    var strEspecieDocumental = row.find('td:eq(1)').text();
    var strTipoDocumento = row.find('td:eq(2)').text();

    if (confirm('Confirma retirada do processo ' + strTipoDocumento + ' do bloco de trâmite externo?')) {
      window.location = url;
    }
  }

  function onClickBtnExcluir() {

    try {
      var len = jQuery('input[name*=chkInfraItem]:checked').length;

      if (len > 0) {
        if (confirm('Confirma retirada de ' + len + ' protocolo(s) selecionado(s) do bloco de trâmite externo?')) {
          var form = jQuery('#frmProcessosListar');
          form.attr('action', '<?php print $objSessaoSEI->assinarLink('controlador.php?acao=pen_tramita_em_bloco_protocolo_excluir&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao'].'&id_bloco='.$_GET['id_bloco']); ?>');
          form.submit();
        }
      } else {
        alert('Selecione pelo menos um mapeamento para Excluir');
      }
    } catch (e) {
      alert('Erro : ' + e.message);
    }
  }

  function onClickBtnCancelarTramites()
  {
    try {
      var len = jQuery('input[name*=chkInfraItem]:checked').length;
        if (len > 0) {
          if (confirm('Confirma a exclusão de ' + len + ' mapeamento(s) ?')) {
            var form = jQuery('#frmProcessosListar');
              form.attr('action', '<?php print $objSessaoSEI->assinarLink('controlador.php?acao=pen_tramita_em_bloco_protocolo_cancelar&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao'].'&id_bloco='.$_GET['id_bloco']); ?>');
              form.submit();
          }
        } else {
          alert('Selecione pelo menos um mapeamento para Excluir');
        }
      } catch (e) {
        alert('Erro : ' + e.message);
      }
  }

  function onClickBtnCancelarTramite(url, link) {
    var row = jQuery(link).parents('tr:first');
    var strTipoDocumento = row.find('td:eq(2)').text();
      console.log(link)
    if (confirm('Confirma a cancelamento do trâmite "' + strTipoDocumento + '"?')) {
        window.location = url;
    }
  }

  function onClickBtnTramitarProcessos() {
    try {
      var len = jQuery('input[name*=chkInfraItem]:checked').length;
      if (len > 0) {
        var form = jQuery('#frmLoteListar');
        form.attr('action', '<?php print $objSessaoSEI->assinarLink('controlador.php?acao=pen_expedir_lote&acao_origem=pen_tramita_em_bloco_protocolo_listar&acao_retorno=pen_tramita_em_bloco_protocolo_listar&tramite_em_bloco=1'); ?>');
        form.submit();
      } else {
        alert('Selecione pelo menos um processo');
      }
    } catch (e) {
      alert('Erro : ' + e.message);
    }
  }
</script>
<?
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody($strTitulo, 'onload="inicializar();"');
?>
<form id="frmProcessosListar" method="post" action="<?= $objSessaoSEI->assinarLink('controlador.php?acao=' . $_GET['acao'] . '&acao_origem=' . $_GET['acao'].'&id_bloco='.$_GET['id_bloco']) ?>">
  <?
  $objPaginaSEI->montarBarraComandosSuperior($arrComandos);
  $objPaginaSEI->abrirAreaDados('4.5em');
  ?>
  <label id="lblProcedimentoFormatado" for="txtProcedimentoFormatado" accesskey="" class="infraLabelOpcional">Número do Processo:</label>
  <input type="text" id="txtProcedimentoFormatado" name="txtProcedimentoFormatado" value="<?= $strProcedimentoFormatado ?>" class="infraText" />
<?
  $objPaginaSEI->fecharAreaDados();
  $objPaginaSEI->montarAreaTabela($strResultado, $numRegistros);
  $objPaginaSEI->montarAreaDebug();
  $objPaginaSEI->montarBarraComandosInferior($arrComandos);
  ?>
</form>
<?
$objPaginaSEI->fecharBody();
$objPaginaSEI->fecharHtml();
?>