<?php
try {
  require_once DIR_SEI_WEB . '/SEI.php';

  session_start();

  $objSessaoSEI = SessaoSEI::getInstance();
  $objPaginaSEI = PaginaSEI::getInstance();

  $objSessaoSEI->validarLink();
  $objSessaoSEI->validarPermissao($_GET['acao']);

  $objPaginaSEI->salvarCamposPost(array('txtProcedimentoFormatado')); 

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
            $objTramiteEmBlocoProtocoloDTO->retStrIdxRelBlocoProtocolo();
            $objTramiteEmBlocoProtocoloDTO->retNumIdTramitaEmBloco();
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

        $dblIdBloco = $arrObjTramiteBlocoProtocoloDTO[0]->getNumIdTramitaEmBloco();
        $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
        $objTramiteEmBlocoDTO->setNumId($dblIdBloco);
        $objTramiteEmBlocoDTO->setStrStaEstado(TramiteEmBlocoRN::$TE_CONCLUIDO_PARCIALMENTE);
        $objTramiteEmBlocoDTO->retNumId();
        $objTramiteEmBlocoDTO->retStrStaEstado();
   
        $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
        $blocoResultado = $objTramiteEmBlocoRN->consultar($objTramiteEmBlocoDTO);

        if ($blocoResultado != null) {
          // atualizar bloco de tramite externo
          $objTramiteEmBlocoProtocoloDTO = new TramitaEmBlocoProtocoloDTO();
          $objTramiteEmBlocoProtocoloDTO->setNumIdTramitaEmBloco($dblIdBloco);
          $objTramiteEmBlocoProtocoloDTO->retNumIdTramitaEmBloco();
          $objTramiteEmBlocoProtocoloDTO->setNumMaxRegistrosRetorno(1);
          $objTramiteEmBlocoProtocoloDTO->setOrdNumId(InfraDTO::$TIPO_ORDENACAO_DESC);
        
          $tramitaEmBlocoProtocoloRN = new TramitaEmBlocoProtocoloRN();
          $tramiteEmBlocoProtocoloDTO = $tramitaEmBlocoProtocoloRN->consultar($objTramiteEmBlocoProtocoloDTO);

          if ($tramiteEmBlocoProtocoloDTO == null) {
            $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
            $objTramiteEmBlocoDTO->setNumId($dblIdBloco);
            $objTramiteEmBlocoDTO->setStrStaEstado(TramiteEmBlocoRN::$TE_ABERTO);
        
            $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
            $objTramiteEmBlocoRN->alterar($objTramiteEmBlocoDTO);
          } else {
            $tramitaEmBlocoProtocoloRN->atualizarEstadoDoBloco($tramiteEmBlocoProtocoloDTO, TramiteEmBlocoRN::$TE_CONCLUIDO);
          }
        } 
        
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
  $objTramitaEmBlocoProtocoloDTO->retStrIdxRelBlocoProtocolo();
  $objTramitaEmBlocoProtocoloDTO->retNumIdUsuario();
  $objTramitaEmBlocoProtocoloDTO->retNumIdUnidadeBloco();
  $objTramitaEmBlocoProtocoloDTO->retStrStaEstadoProtocolo();
  $objTramitaEmBlocoProtocoloDTO->setNumIdTramitaEmBloco($_GET['id_bloco']);

  $strPalavrasPesquisa = PaginaSEI::getInstance()->recuperarCampo('txtProcedimentoFormatado');
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

    // $arrComandos[] = '<button type="button" value="Cancelar" onclick="onClickBtnCancelarTramites()" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar Trâmites</button>';
    $arrComandos[] = '<button type="button" value="Excluir" onclick="onClickBtnExcluir()" class="infraButton"><span class="infraTeclaAtalho">E</span>xcluir</button>';

    $strResultado = '';
    $strSumarioTabela = 'Tabela de Processo em Lote.';
    $strCaptionTabela = 'Processo em Lote';

    $strResultado .= '<table width="99%" id="tblBlocos" class="infraTable" summary="' . $strSumarioTabela . '">' . "\n";
    $strResultado .= '<caption class="infraCaption">' . $objPaginaSEI->gerarCaptionTabela($strCaptionTabela, $numRegistros) . '</caption>';
    $strResultado .= '<thead>';
    $strResultado .= '<th class="infraTh" width="1%">' . $objPaginaSEI->getThCheck() . '</th>' . "\n";
    $strResultado .= '<th class="infraTh" width="10%">';

    $strResultado .= '<div class="infraDivOrdenacao">';
    $strResultado .= '<div class="infraDivRotuloOrdenacao">Seq</div>';
    $strResultado .= '<div class="infraDivSetaOrdenacao"><a href="javascript:void(0);" tabindex="1002"><img src="/infra_css/svg/seta_acima.svg" title="Ordenar Processo Ascendente" alt="Ordenar Processo Ascendente" class="infraImgOrdenacao"></a></div>';
    $strResultado .= '<div class="infraDivSetaOrdenacao"><a href="javascript:void(0);" tabindex="1003"><img src="/infra_css/svg/seta_abaixo_selecionada.svg" title="Ordenar Processo Descendente" alt="Ordenar Processo Descendente" class="infraImgOrdenacao"></a></div>';
    $strResultado .= '</div>';

    $strResultado .= '</th>' . "\n";
    $strResultado .= '<th class="infraTh">';

    $strResultado .= '<div class="infraDivOrdenacao">';
    $strResultado .= '<div class="infraDivRotuloOrdenacao">Processo</div>';
    $strResultado .= '<div class="infraDivSetaOrdenacao"><a href="javascript:void(0);" tabindex="1002"><img src="/infra_css/svg/seta_acima.svg" title="Ordenar Processo Ascendente" alt="Ordenar Processo Ascendente" class="infraImgOrdenacao"></a></div>';
    $strResultado .= '<div class="infraDivSetaOrdenacao"><a href="javascript:void(0);" tabindex="1003"><img src="/infra_css/svg/seta_abaixo_selecionada.svg" title="Ordenar Processo Descendente" alt="Ordenar Processo Descendente" class="infraImgOrdenacao"></a></div>';
    $strResultado .= '</div>';

    $strResultado .= '</th>' . "\n";
   // $strResultado .= '<th class="infraTh">' . $objPaginaSEI->getThOrdenacao($objTramitaEmBlocoProtocoloDTO, 'Anotações', 'Anotacoes', $arrTramitaEmBlocoProtocoloDTO) . '</th>' . "\n";
    $strResultado .= '<th class="infraTh">Usuário</th>' . "\n";
    $strResultado .= '<th class="infraTh">Data do Envio</th>' . "\n";
    $strResultado .= '<th class="infraTh">Unidade Destino</th>' . "\n";
    $strResultado .= '<th class="infraTh">Situação</th>' . "\n";
    $strResultado .= '<th class="infraTh" width="10%">Ações</th>' . "\n";
    $strResultado .= '</thead>' . "\n";
    $strCssTr = '';
    foreach ($arrTramitaEmBlocoProtocoloDTO as $i => $objTramitaEmBlocoProtocoloDTO) {

      $strCssTr = ($strCssTr == '<tr class="infraTrClara">') ? '<tr class="infraTrEscura">' : '<tr class="infraTrClara">';
      $strResultado .= $strCssTr;

      $id = $objTramitaEmBlocoProtocoloDTO->getNumId().'-'.$objTramitaEmBlocoProtocoloDTO->getDblIdProtocolo().'-'.$_GET['id_bloco'];
      $strResultado .= '<td valign="top">' . $objPaginaSEI->getTrCheck($i, $id, $id) . '</td>';
      $strResultado .= '<td align="center">' . ($i + 1) . '</td>';

      $strResultado .= '<td align="center">';
      $strResultado .= '<a onclick="infraLimparFormatarTrAcessada(this.parentNode.parentNode);" href="' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=procedimento_trabalhar&acao_origem=' . $_GET['acao'] . '&acao_retorno=' . $_GET['acao'] . '&id_procedimento=' . $objTramitaEmBlocoProtocoloDTO->getDblIdProtocolo()) . '" target="_blank" tabindex="' . PaginaSEI::getInstance()->getProxTabTabela() . '" class="' . $strClassProtocolo . '" alt="" title="">' . $objTramitaEmBlocoProtocoloDTO->getStrIdxRelBlocoProtocolo() . '</a>';
      $strResultado .= '</td>';

      $objPenLoteProcedimento = $objTramitaEmBlocoProtocoloDTO->getObjPenLoteProcedimentoDTO();

      if ($objPenLoteProcedimento && $objTramitaEmBlocoProtocoloDTO->getStrStaEstadoBloco() != TramiteEmBlocoRN::$TE_ABERTO) {
          $strResultado .= '<td align="center">' . PaginaSEI::tratarHTML($objPenLoteProcedimento->getStrNomeUsuario()) . '</td>';
          $strResultado .= '<td align="center">' . PaginaSEI::tratarHTML($objPenLoteProcedimento->getDthRegistro()) . '</td>';
          $strResultado .= '<td align="center">' . PaginaSEI::tratarHTML($objPenLoteProcedimento->getStrUnidadeDestino()) . '</td>';
         
      } else {
          $strResultado .= str_repeat('<td align="center"></td>' . "\n", 3);
      }

      $strResultado .= '<td align="center">' . "\n";

      if ($objTramitaEmBlocoProtocoloDTO->getStrStaEstadoBloco() == TramiteEmBlocoRN::$TE_ABERTO) {
        $strResultado .= '<img src="' . PENIntegracao::getDiretorio() . '/imagens/nao_iniciado.png" title="Em aberto" style="width:16px;" alt="Em aberto" />';   
      } elseif ($objTramitaEmBlocoProtocoloDTO->getStrSinObteveRecusa() == 'S') {
        $strResultado .= '<img src="' . PENIntegracao::getDiretorio() . '/imagens/falhou.png" title="Um trâmite para esse processo foi recusado" style="width:16px;" alt="Um trâmite para esse processo foi recusado" />';
      } else {

        $PROCESSO_CONCLUIDO_RECEBIDO = ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO); // 1002
        $PROCESSO_CONCLUIDO_AVULSO = ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_DOCUMENTO_AVULSO_RECEBIDO); // 1007
        $PROCESSO_TRAMITE_EXPEDIDO = ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_EXTERNO); // 1005
        $PROCESSO_TRAMITE_CANCELADO_ID = ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_CANCELADO); // 1004
        $PROCESSO_TRAMITE_PROCESSAMENTO = ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO); // 1001
        $PROCESSO_TRAMITE_ABERTO = ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_NAO_INICIADO);
       
        switch ($objTramitaEmBlocoProtocoloDTO->getNumStaIdTarefa()) {
          case $PROCESSO_CONCLUIDO_AVULSO:
          case $PROCESSO_TRAMITE_EXPEDIDO:
          case $PROCESSO_CONCLUIDO_RECEBIDO:
              $strResultado .= '<img src="' . PENIntegracao::getDiretorio() . '/imagens/estado_sucesso.png" title="Concluído" style="width:16px; alt="Concluído" />';
              break;
          case $PROCESSO_TRAMITE_PROCESSAMENTO:
            if ($objTramitaEmBlocoProtocoloDTO->getStrStaEstadoBloco() == TramiteEmBlocoRN::$TE_CONCLUIDO) {
              $strResultado .= '<img src="' . PENIntegracao::getDiretorio() . '/imagens/estado_sucesso.png" title="Concluído" style="width:16px; alt="Concluído" />';
            } else {
              $strResultado .= '<img src="' . PENIntegracao::getDiretorio() . '/imagens/em_processamento.png" title="Em processamento" style="width:16px; alt="Em processamento" />';
            }
            break;
          case $PROCESSO_TRAMITE_CANCELADO_ID:
            $strResultado .= '<img src="' . PENIntegracao::getDiretorio() . '/imagens/falhou.png" title="Cancelado" style="width:16px; alt="Cancelado" />';
            break;   
          default:
            $strResultado .= '<img src="' . PENIntegracao::getDiretorio() . '/imagens/nao_iniciado.png" title="Em aberto" style="width:16px;" alt="Em aberto" />';
            break;
        }
      }
      $strResultado .= '</td>' . "\n";

      $strResultado .= '<td align="center">' . "\n";

      if ($objTramitaEmBlocoProtocoloDTO->getNumIdUnidadeBloco() == SessaoSEI::getInstance()->getNumIdUnidadeAtual()) {
        $strId = $objTramitaEmBlocoProtocoloDTO->getDblIdProtocolo() . '-' . $objTramitaEmBlocoProtocoloDTO->getNumId();
        $strProtocoloId = $objTramitaEmBlocoProtocoloDTO->getDblIdProtocolo();
        $strDescricao = PaginaSEI::getInstance()->formatarParametrosJavaScript($objTramitaEmBlocoProtocoloDTO->getStrIdxRelBlocoProtocolo());

        if ($objTramitaEmBlocoProtocoloDTO->getNumStaIdTarefa() != $PROCESSO_TRAMITE_EXPEDIDO &&
            $objTramitaEmBlocoProtocoloDTO->getStrSinObteveRecusa() == 'S' ||
            $objTramitaEmBlocoProtocoloDTO->getStrStaEstadoBloco() == TramiteEmBlocoRN::$TE_ABERTO
            ) {
          $strResultado .= '<a onclick="onCLickLinkDelete(\''.$objSessaoSEI->assinarLink('controlador.php?acao=pen_tramita_em_bloco_protocolo_excluir&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao'].'&hdnInfraItensSelecionados='.$id.'&id_bloco='.$_GET['id_bloco']).'\', this)" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="'.PaginaSEI::getInstance()->getIconeExcluir().'" title="Excluir processo" alt="Excluir processo" class="infraImg" /></a>&nbsp;';
        }

       //  $strResultado .= $objTramitaEmBlocoProtocoloDTO->getNumStaIdTarefa() == $PROCESSO_EXPEDIDO_ID ? '<a onclick="onClickBtnCancelarTramite(\''.$objSessaoSEI->assinarLink('controlador.php?acao=pen_tramita_em_bloco_protocolo_cancelar&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao'].'&hdnInfraItensSelecionados='.$id.'&id_bloco='.$_GET['id_bloco']).'\', this)" tabindex="' . ProcessoEletronicoINT::getCaminhoIcone("/pen_cancelar_envio.png", $this->getDiretorioImagens()) . '"><img src="' . ProcessoEletronicoINT::getCaminhoIcone("/pen_cancelar_envio.png", $this->getDiretorioImagens()) . '" title="Cancelar Tramite" alt="Cancelar Tramite" class="infraImg iconTramita" /></a>&nbsp;' : '';
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
$objPaginaSEI->montarStyle(); ?>
<style>
#lblProcedimentoFormatado {
    position:absolute;left:0%;top:0%;width:20%;
}
#txtProcedimentoFormatado {
  position:absolute;left:0%;top:40%;width:20%;
}
input.infraText {
  width: 100%;
}
.iconTramita {max-width: 1.5rem;}

 /* Personalize o estilo da paginação */
.dataTables_paginate {
  margin: 10px;
  text-align: end;
}

.dataTables_paginate .paginate_button {
  padding: 5px 10px;
  margin-right: 5px;
  border: 1px solid #ccc;
  background-color: #f2f2f2;
  color: #333;
  cursor: pointer;
}

.dataTables_paginate .paginate_button.current {
  background-color: var(--color-primary-default);
  color: #fff;
}

</style>

<?php
$objPaginaSEI->montarJavaScript(); ?>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.js"></script>
<script type="text/javascript">


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

  $(document).ready(function() {
    $('#tblBlocos').dataTable({
      "searching": false,
      "columnDefs": [
          { targets: [0, 3, 4], orderable: false } // Define as colunas 1 e 5 como não ordenáveis
        ],

      "language": {
        "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
        "lengthMenu": "Mostrar _MENU_ registros por página",
        "infoEmpty": "Mostrando 0 a 0 de 0 registros",
        "zeroRecords": "Nenhum registro encontrado",
        "paginate": {
          "previous": "Anterior",
          "next": "Próximo"
        },
      }
    } );
  } );

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
  <input type="text" id="txtProcedimentoFormatado" name="txtProcedimentoFormatado" value="<?= $strPalavrasPesquisa ?>" class="infraText" />
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