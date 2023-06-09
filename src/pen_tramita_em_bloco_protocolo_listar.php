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
    case 'rel_bloco_protocolo_excluir':
      try {
        $arrStrIds = PaginaSEI::getInstance()->getArrStrItensSelecionados();
        $arrObjTramiteBlocoDTO = array();
        for ($i = 0; $i < count($arrStrIds); $i++) {
          $arrStrIdComposto = explode('-', $arrStrIds[$i]);
          $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
          $objTramiteEmBlocoDTO->setDblIdProtocolo($arrStrIdComposto[0]);
          $objTramiteEmBlocoDTO->setNumIdBloco($arrStrIdComposto[1]);
          $arrObjTramiteBlocoDTO[] = $objTramiteEmBlocoDTO;
        }
        $objRelBlocoProtocoloRN = new RelBlocoProtocoloRN();
        $objRelBlocoProtocoloRN->excluirRN1289($arrObjRelBlocoProtocoloDTO);
        PaginaSEI::getInstance()->setStrMensagem('Operação realizada com sucesso.');
      } catch (Exception $e) {
        PaginaSEI::getInstance()->processarExcecao($e);
      }
      header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . $_GET['acao_origem'] . '&acao_origem=' . $_GET['acao'] . $strParametros));
      die;
    case 'pen_tramita_em_bloco_protocolo_listar':
      $strTitulo = 'Processos do Bloco: ' . $_GET['id_bloco'];
      break;

        default:
            throw new InfraException("Ação '" . $_GET['acao'] . "' não reconhecida.");
      }

  $arrComandos = array();
  $arrComandos[] = '<button type="button" accesskey="T" id="sbmTramitarBloco" value="Tramitar processos selecionados" onclick="onClickBtnTramitarProcessos();" class="infraButton"><span class="infraTeclaAtalho">T</span>ramitar processo(s) selecionado(s)</button>';
  $arrComandos[] = '<button type="submit" accesskey="P" id="sbmPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';

  $objTramitaEmBlocoProtocoloDTO = new TramitaEmBlocoProtocoloDTO();
  $objTramitaEmBlocoProtocoloDTO->retTodos();
  $objTramitaEmBlocoProtocoloDTO->setNumIdTramitaEmBloco($_GET['id_bloco']);

  $objTramitaEmBlocoProtocoloRN = new TramitaEmBlocoProtocoloRN();
  $arrTramitaEmBlocoProtocoloDTO = $objTramitaEmBlocoProtocoloRN->listar($objTramitaEmBlocoProtocoloDTO);
  // echo "<pre>"; var_dump($_GET['id_bloco']); echo "</pre>"; die(1);

  $objPaginaSEI->prepararPaginacao($objTramitaEmBlocoProtocoloDTO);
  $objPaginaSEI->processarPaginacao($objTramitaEmBlocoProtocoloDTO);
  $objPaginaSEI->prepararOrdenacao($objTramitaEmBlocoProtocoloDTO, 'IdxRelBlocoProtocolo', InfraDTO::$TIPO_ORDENACAO_ASC);

  $numRegistros = count($arrTramitaEmBlocoProtocoloDTO);
  if ($numRegistros > 0) {
    $objPenLoteProcedimentoDTO = new PenLoteProcedimentoDTO();
    $arrComandos[] = '<button type="button" accesskey="I" id="btnImprimir" value="Imprimir" onclick="infraImprimirTabela();" class="infraButton"><span class="infraTeclaAtalho">I</span>mprimir</button>';

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

      //echo "<pre>"; var_dump($objDTO); echo "</pre>"; 

      $strResultado .= '<td valign="top">' . $objPaginaSEI->getTrCheck($i, $objDTO->getNumId(), $objDTO->getNumId()) . '</td>';
      $strResultado .= '<td align="center">' . $objDTO->getNumSequencia() . '</td>';

      $objPenLoteProcedimentoDTO = $objDTO->getObjPenLoteProcedimentoDTO();

      $strResultado .= '<td align="center">';
      //' . PaginaSEI::tratarHTML($objProtocoloDTO->getStrNomeTipoProcedimentoProcedimento()) . '
      $strResultado .= '<a onclick="infraLimparFormatarTrAcessada(this.parentNode.parentNode);" href="' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=procedimento_trabalhar&acao_origem=' . $_GET['acao'] . '&acao_retorno=' . $_GET['acao'] . '&id_procedimento=' . $objDTO->getDblIdProtocolo()) . '" target="_blank" tabindex="' . PaginaSEI::getInstance()->getProxTabTabela() . '" class="' . $strClassProtocolo . '" alt="" title="">' . $objDTO->getStrIdxRelBlocoProtocolo() . '</a>';
      $strResultado .= '</td>';
      // }

      $strResultado .= '<td>' . nl2br(InfraString::formatarXML($objDTO->getStrAnotacao())) . '</td>';

      $objTramiteDTO = $objDTO->getObjTramiteDTO();
      if ($objTramiteDTO) {
        $strResultado .= '<td align="center">' . PaginaSEI::tratarHTML($objTramiteDTO->getStrNomeUsuario()) . '</td>';
        $strResultado .= '<td align="center">' . PaginaSEI::tratarHTML($objTramiteDTO->getDthRegistro()) . '</td>';
        $strResultado .= '<td align="center">' . /*PaginaSEI::tratarHTML($objTramiteDTO->getStrUnidadeDestino()) .*/ '</td>';

        $strResultado .= '<td align="center">' . "\n";

        $objPenProtocoloDTO = new PenProtocoloDTO();
        $objPenProtocoloDTO->setDblIdProtocolo($objDTO->getDblIdProtocolo());
        $objPenProtocoloDTO->retStrSinObteveRecusa();
        $objPenProtocoloDTO->setNumMaxRegistrosRetorno(1);

        $objProtocoloBD = new ProtocoloBD(BancoSEI::getInstance());
        $objPenProtocoloDTO = $objProtocoloBD->consultar($objPenProtocoloDTO);

        if (!empty($objPenProtocoloDTO) && $objPenProtocoloDTO->getStrSinObteveRecusa() == 'S') {
          $strResultado .= '<img src="' . PENIntegracao::getDiretorio() . '/imagens/pen_tramite_recusado.png" title="Um trâmite para esse processo foi recusado" alt="Um trâmite para esse processo foi recusado" />';
        } else {
          switch ($objPenLoteProcedimentoDTO->getNumIdAndamento()) {
            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_ENVIADOS_REMETENTE:
              $strResultado .= '<img src="' . PENIntegracao::getDiretorio() . '/imagens/estado_sucesso.png" title="Concluído" alt="Concluído" />';
              break;
            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO:
              $strResultado .= '<img src="' . PENIntegracao::getDiretorio() . '/imagens/estado_falhou.png" title="Cancelado" alt="Cancelado" />';
              break;
            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_NAO_INICIADO:
            default:
              $strResultado .= '<img src="' . PENIntegracao::getDiretorio() . '/imagens/pen_em_processamento.png" title="Em processamento" alt="Em processamento" />';
              break;
          }
        }
        $strResultado .= '</td>' . "\n";
      } else {
        $strResultado .= '<td align="center"></td>' . "\n";
        $strResultado .= '<td align="center"></td>' . "\n";
        $strResultado .= '<td align="center"></td>' . "\n";
        $strResultado .= '<td align="center"></td>' . "\n";
      }

      $strResultado .= '<td align="center">' . "\n";

      if ($objDTO->getStrSinObteveRecusa() == 'S') {
        $strResultado .= '<img src="' . PENIntegracao::getDiretorio() . '/imagens/pen_tramite_recusado.png" title="Um trâmite para esse processo foi recusado" alt="Um trâmite para esse processo foi recusado" />';
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
            // $strResultado .= '<img src="' . PENIntegracao::getDiretorio() . '/imagens/pen_em_processamento.png" title="Em processamento" alt="Em processamento" />';
            break;
        }
      }
      $strResultado .= '</td>' . "\n";

      $strResultado .= '<td align="center">' . "\n";
      // if (
      //     $objDTO->getStrStaEstado() != TramiteEmBlocoRN::$TE_DISPONIBILIZADO &&
      //     $objRelBlocoProtocoloDTO->getNumIdUnidadeBloco() == SessaoSEI::getInstance()->getNumIdUnidadeAtual()
      // ) {
      $strId = $objDTO->getDblIdProtocolo() . '-' . $objDTO->getNumId();
      $strDescricao = PaginaSEI::getInstance()->formatarParametrosJavaScript($objDTO->getStrIdxRelBlocoProtocolo());
      $strResultado .= '<a onclick="onCLickLinkDelete(\''.$objSessaoSEI->assinarLink('controlador.php?acao=pen_tramita_em_bloco_protocolo_excluir&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao'].'&hdnInfraItensSelecionados='.$id.'&id_bloco='.$_GET['id_bloco']).'\', this)" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="'.PaginaSEI::getInstance()->getIconeExcluir().'" title="Excluir Bloco" alt="Excluir Bloco" class="infraImg" /></a>&nbsp;';
      // }
      $strResultado .= '</td>' . "\n";
      $strResultado .= '</tr>' . "\n";
    }
    $strResultado .= '</table>';
  }

  $arrComandos[] = '<button type="button" accesskey="F" id="btnFechar" value="Fechar" onclick="location.href=\'' . $objSessaoSEI->assinarLink('controlador.php?acao=' . $objPaginaSEI->getAcaoRetorno() . '&acao_origem=' . $_GET['acao'] . $objPaginaSEI->montarAncora($numIdGrupoSerie)) . '\'" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';
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

<?
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody($strTitulo, 'onload="inicializar();"');
?>
<form id="frmLoteListar" method="post" action="<?= $objSessaoSEI->assinarLink('controlador.php?acao=' . $_GET['acao'] . '&acao_origem=' . $_GET['acao']) ?>">
  <?
  $objPaginaSEI->montarBarraComandosSuperior($arrComandos);
  $objPaginaSEI->abrirAreaDados('4.5em');
  ?>

  <label id="lblNomeUsuario" for="txtNomeUsuario" accesskey="" class="infraLabelOpcional">Nome do Usuário:</label>
  <input type="text" id="txtNomeUsuario" name="txtNomeUsuario" value="<?= $strNomeUsuario ?>" class="infraText" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />

  <label id="lblProcedimentoFormatado" for="txtProcedimentoFormatado" accesskey="" class="infraLabelOpcional">Número do Processo:</label>
  <input type="text" id="txtProcedimentoFormatado" name="txtProcedimentoFormatado" value="<?= $strProcedimentoFormatado ?>" class="infraText" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />

  <label id="lblUnidadeDestino" for="txtUnidadeDestino" accesskey="" class="infraLabelOpcional">Unidade de Destino:</label>
  <input type="text" id="txtUnidadeDestino" name="txtUnidadeDestino" value="<?= $strUnidadeDestino ?>" class="infraText" tabindex="<?= $objPaginaSEI->getProxTabDados() ?>" />

  <label id="lblAndamento" for="selAndamento" accesskey="" class="infraLabelOpcional">Situação:</label>
  <select id="selAndamento" name="selAndamento" onchange="this.form.submit();" class="infraSelect" tabindex="<?= PaginaSEI::getInstance()->getProxTabDados() ?>">
    <option value="" <?= strval($numIdAndamento) == "" ? ' selected="selected" ' : '' ?>>Todos</option>
    <option value="<?= $staCancelado ?>" <?= strval($numIdAndamento) == strval($staCancelado) ? ' selected="selected" ' : '' ?>>Cancelado</option>
    <option value="<?= $staConcluido ?>" <?= strval($numIdAndamento) == strval($staConcluido) ? ' selected="selected" ' : '' ?>>Concluído</option>
    <option value="<?= $staEmProcessamento ?>" <?= strval($numIdAndamento) == strval($staEmProcessamento) ? ' selected="selected" ' : '' ?>>Em Processamento</option>
  </select>

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
