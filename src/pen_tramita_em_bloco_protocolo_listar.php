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
        $objTramitaEmBlocoProtocoloRN = new PenBlocoProcessoRN();

        $arrStrIds = $objPaginaSEI->getArrStrItensSelecionados();
        $arrObjTramiteBlocoProtocoloDTO = array();
        if (count($arrStrIds) > 0) {
          for ($i = 0; $i < count($arrStrIds); $i++) {     
            $arrStrIdComposto = explode('-', $arrStrIds[$i]);
            $objTramiteEmBlocoProtocoloDTO = new PenBlocoProcessoDTO();
            $objTramiteEmBlocoProtocoloDTO->setNumIdBlocoProcesso($arrStrIdComposto[0]);
            $objTramiteEmBlocoProtocoloDTO->setDblIdProtocolo($arrStrIdComposto[1]);
            $objTramiteEmBlocoProtocoloDTO->setNumIdBloco($arrStrIdComposto[2]);
            $objTramiteEmBlocoProtocoloDTO->retNumIdBloco();
            $objTramiteEmBlocoProtocoloDTO->retNumIdAndamento();
            $arrObjTramiteBlocoProtocoloDTO[] = $objTramiteEmBlocoProtocoloDTO;
          }
        } elseif (isset($_GET['hdnInfraItensSelecionados'])) {
    
          $arrStrIdComposto = explode('-', $_GET['hdnInfraItensSelecionados']);
          $objTramiteEmBlocoProtocoloDTO = new PenBlocoProcessoDTO();
          $objTramiteEmBlocoProtocoloDTO->setNumIdBlocoProcesso($arrStrIdComposto[0]);
          $objTramiteEmBlocoProtocoloDTO->setDblIdProtocolo($arrStrIdComposto[1]);
          $objTramiteEmBlocoProtocoloDTO->setNumIdBloco($arrStrIdComposto[2]);
          $objTramiteEmBlocoProtocoloDTO->retNumIdAndamento();
          $arrObjTramiteBlocoProtocoloDTO[] = $objTramiteEmBlocoProtocoloDTO;
        }
        
          $contemValidacoes = $objTramitaEmBlocoProtocoloRN->verificarExclusaoBloco($arrObjTramiteBlocoProtocoloDTO);
          // print_r($arrObjTramiteBlocoProtocoloDTO);
          $arrExcluidos = $objTramitaEmBlocoProtocoloRN->excluir($arrObjTramiteBlocoProtocoloDTO);
          if (!empty($arrExcluidos)) {
            $dblIdBloco = $arrObjTramiteBlocoProtocoloDTO[0]->getNumIdBloco();
            $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
            $objTramiteEmBlocoDTO->setNumId($dblIdBloco);
            // $objTramiteEmBlocoDTO->setStrStaEstado(TramiteEmBlocoRN::$TE_CONCLUIDO_PARCIALMENTE);
            $objTramiteEmBlocoDTO->retNumId();
            $objTramiteEmBlocoDTO->retStrStaEstado();
            $objTramiteEmBlocoDTO->retNumOrdem();
      
            $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
            $blocoResultado = $objTramiteEmBlocoRN->consultar($objTramiteEmBlocoDTO);

            if ($blocoResultado != null) {
              $objTramiteEmBlocoProtocoloDTO = new PenBlocoProcessoDTO();
              $objTramiteEmBlocoProtocoloDTO->setNumIdBloco($dblIdBloco);
              $objTramiteEmBlocoProtocoloDTO->retNumIdAndamento();
              $objTramiteEmBlocoProtocoloDTO->retNumIdBloco();

              $idAndamentoBloco = TramiteEmBlocoRN::$TE_ABERTO;

              $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
              $tramitaEmBlocoProtocoloRN = new PenBlocoProcessoRN();
              $arrObjTramiteEmBlocoProtocoloDTO = $tramitaEmBlocoProtocoloRN->listar($objTramiteEmBlocoProtocoloDTO);
              if (count($arrObjTramiteEmBlocoProtocoloDTO) > 0) {
                $concluido = ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE;
                $parcialmenteConcluido = array(
                  ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CIENCIA_RECUSA,
                  ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO,
                  ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO,
                  ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO_AUTOMATICAMENTE,
                );
                $emAndamento = array(
                  ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO,
                  ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_ENVIADOS_REMETENTE,
                  ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_METADADOS_RECEBIDO_DESTINATARIO,
                  ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO,
                  ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_ENVIADO_DESTINATARIO
                );
                foreach ($arrObjTramiteEmBlocoProtocoloDTO as $objDTO) {
                  if (
                    in_array($objDTO->getNumIdAndamento(), $emAndamento)
                    && $idAndamentoBloco != TramiteEmBlocoRN::$TE_CONCLUIDO_PARCIALMENTE
                  ) {
                    $idAndamentoBloco = TramiteEmBlocoRN::$TE_DISPONIBILIZADO;
                  }
                  if (in_array($objDTO->getNumIdAndamento(), $parcialmenteConcluido)) {
                    $idAndamentoBloco = TramiteEmBlocoRN::$TE_CONCLUIDO_PARCIALMENTE;
                  }
                  if ($objDTO->getNumIdAndamento() == $concluido
                    && (
                      $idAndamentoBloco == TramiteEmBlocoRN::$TE_CONCLUIDO
                      || $idAndamentoBloco == TramiteEmBlocoRN::$TE_ABERTO
                    )
                  ) {
                    $idAndamentoBloco = TramiteEmBlocoRN::$TE_CONCLUIDO;
                  }
                }

                $objTramiteEmBlocoDTO->setStrStaEstado($idAndamentoBloco);
              } else {
                $objTramiteEmBlocoDTO->setStrStaEstado($idAndamentoBloco);
              }
              
              $objTramiteEmBlocoDTO->setNumId($dblIdBloco);
              $objTramiteEmBlocoRN->alterar($objTramiteEmBlocoDTO);
            }
         }
        if (!is_null($contemValidacoes)) {
          PaginaSEI::getInstance()->setStrMensagem($contemValidacoes);
        } else {
          PaginaSEI::getInstance()->setStrMensagem('Opera��o realizada com sucesso.');
        }
          
      } catch (Exception $e) {
        PaginaSEI::getInstance()->processarExcecao($e);
      }
      header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao'].'&id_bloco='.$_GET['id_bloco']));
        die;
    case 'pen_tramita_em_bloco_protocolo_listar':
      $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
      $objTramiteEmBlocoDTO->setNumId($_GET['id_bloco']);
      $objTramiteEmBlocoDTO->retNumOrdem();
 
      $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
      $blocoResultado = $objTramiteEmBlocoRN->consultar($objTramiteEmBlocoDTO);

      $strTitulo = 'Processos do Bloco ' . $blocoResultado->getNumOrdem() . ':';
        break;
    default:
        throw new InfraException("A��o '" . $_GET['acao'] . "' n�o reconhecida.");
  }
  $arrComandos = array();
  $arrComandos[] = '<button type="button" accesskey="T" id="sbmTramitarBloco" value="Tramitar processos selecionados" onclick="onClickBtnTramitarProcessos();" class="infraButton"><span class="infraTeclaAtalho">T</span>ramitar processo(s) selecionado(s)</button>';
  $arrComandos[] = '<button type="submit" accesskey="P" id="sbmPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';

  $objTramitaEmBlocoProtocoloDTO = new PenBlocoProcessoDTO();
  $objTramitaEmBlocoProtocoloDTO->setNumIdBloco($_GET['id_bloco']);
  $objTramitaEmBlocoProtocoloDTO->retDblIdProtocolo();
  $objTramitaEmBlocoProtocoloDTO->retNumIdBloco();
  $objTramitaEmBlocoProtocoloDTO->retNumIdBlocoProcesso();
  $objTramitaEmBlocoProtocoloDTO->retNumSequencia();
  $objTramitaEmBlocoProtocoloDTO->retStrNomeUsuario();
  $objTramitaEmBlocoProtocoloDTO->retDthEnvio();
  $objTramitaEmBlocoProtocoloDTO->retStrUnidadeDestino();
  $objTramitaEmBlocoProtocoloDTO->retNumIdUsuario();
  $objTramitaEmBlocoProtocoloDTO->retNumIdUnidadeBloco();
  $objTramitaEmBlocoProtocoloDTO->retStrStaEstadoProtocolo();
  $objTramitaEmBlocoProtocoloDTO->retStrStaEstadoBloco();
  $objTramitaEmBlocoProtocoloDTO->retNumIdAndamento();

  $strPalavrasPesquisa = PaginaSEI::getInstance()->recuperarCampo('txtProcedimentoFormatado');
  if ($strPalavrasPesquisa!=''){
    $objTramitaEmBlocoProtocoloDTO->setStrPalavrasPesquisa($strPalavrasPesquisa);
  }

  $objTramitaEmBlocoProtocoloRN = new PenBlocoProcessoRN();
  $arrTramitaEmBlocoProtocoloDTO = $objTramitaEmBlocoProtocoloRN->listarProtocolosBloco($objTramitaEmBlocoProtocoloDTO);

  $arrComandos = array();

  $arrComandos[] = '<button type="button" accesskey="I" id="btnImprimir" value="Imprimir" onclick="infraImprimirTabela();" class="infraButton"><span class="infraTeclaAtalho">I</span>mprimir</button>';
  $arrComandos[] = '<button type="submit" accesskey="P" onclick="onClickBtnPesquisar()" id="sbmPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';

  $objPaginaSEI->prepararPaginacao($objTramitaEmBlocoProtocoloDTO);
  $objPaginaSEI->processarPaginacao($objTramitaEmBlocoProtocoloDTO);
  $objPaginaSEI->prepararOrdenacao($objTramitaEmBlocoProtocoloDTO, 'IdProtocolo', InfraDTO::$TIPO_ORDENACAO_DESC);

  $numRegistros = count($arrTramitaEmBlocoProtocoloDTO);
  if ($numRegistros > 0) {
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
    $strResultado .= '<div class="infraDivSetaOrdenacao"><a href="javascript:void(0);" tabindex="1002"><img src="' . PaginaSEI::getInstance()->getIconeOrdenacaoColunaAcima() .'" title="Ordenar Processo Ascendente" alt="Ordenar Processo Ascendente" class="infraImgOrdenacao"></a></div>';
    $strResultado .= '<div class="infraDivSetaOrdenacao"><a href="javascript:void(0);" tabindex="1003"><img src="' . PaginaSEI::getInstance()->getIconeOrdenacaoColunaAbaixo() .'" title="Ordenar Processo Descendente" alt="Ordenar Processo Descendente" class="infraImgOrdenacao"></a></div>';
    $strResultado .= '</div>';
    $strResultado .= '</th>' . "\n";
    $strResultado .= '<th class="infraTh">';
    $strResultado .= '<div class="infraDivOrdenacao">';
    $strResultado .= '<div class="infraDivRotuloOrdenacao">Processo</div>';
    $strResultado .= '<div class="infraDivSetaOrdenacao"><a href="javascript:void(0);" tabindex="1002"><img src="' . PaginaSEI::getInstance()->getIconeOrdenacaoColunaAcima() .'" title="Ordenar Processo Ascendente" alt="Ordenar Processo Ascendente" class="infraImgOrdenacao"></a></div>';
    $strResultado .= '<div class="infraDivSetaOrdenacao"><a href="javascript:void(0);" tabindex="1003"><img src="' . PaginaSEI::getInstance()->getIconeOrdenacaoColunaAbaixo() .'" title="Ordenar Processo Descendente" alt="Ordenar Processo Descendente" class="infraImgOrdenacao"></a></div>';
    $strResultado .= '</div>';
    $strResultado .= '</th>' . "\n";
    $strResultado .= '<th class="infraTh">Usu�rio</th>' . "\n";
    $strResultado .= '<th class="infraTh">Data do Envio</th>' . "\n";
    $strResultado .= '<th class="infraTh">Unidade Destino</th>' . "\n";
    $strResultado .= '<th class="infraTh">Situa��o</th>' . "\n";
    $strResultado .= '<th class="infraTh" width="10%">A��es</th>' . "\n";
    $strResultado .= '</thead>' . "\n";
    $strCssTr = '';

    $situacaoPodeExcluir = array(
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO,
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO,
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CIENCIA_RECUSA,
      ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO_AUTOMATICAMENTE,
    );
    
    foreach ($arrTramitaEmBlocoProtocoloDTO as $i => $objTramitaEmBlocoProtocoloDTO) {
      $strCssTr = ($strCssTr == '<tr class="infraTrClara">') ? '<tr class="infraTrEscura">' : '<tr class="infraTrClara">';
      $strResultado .= $strCssTr;

      $numIdBlocoProtocolo = $objTramitaEmBlocoProtocoloDTO->getNumIdBlocoProcesso().'-'.$objTramitaEmBlocoProtocoloDTO->getDblIdProtocolo().'-'.$_GET['id_bloco'];
      $strResultado .= '<td valign="top">' . $objPaginaSEI->getTrCheck($i, $numIdBlocoProtocolo, $numIdBlocoProtocolo) . '</td>';
      $strResultado .= '<td align="center">' . ($i + 1) . '</td>';

      $strResultado .= '<td align="center">';
      $objProcedimentoDTO = new ProcedimentoDTO();
      $objProcedimentoDTO->retStrProtocoloProcedimentoFormatado();
      $objProcedimentoDTO->setDblIdProcedimento($objTramitaEmBlocoProtocoloDTO->getDblIdProtocolo());

      $objProcedimentoRN = new ProcedimentoRN();
      $procedimento = $objProcedimentoRN->consultarRN0201($objProcedimentoDTO);

      $strResultado .= '<a onclick="infraLimparFormatarTrAcessada(this.parentNode.parentNode);" href="' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=procedimento_trabalhar&acao_origem=' . $_GET['acao'] . '&acao_retorno=' . $_GET['acao'] . '&id_procedimento=' . $objTramitaEmBlocoProtocoloDTO->getDblIdProtocolo()) . '" target="_blank" tabindex="' . PaginaSEI::getInstance()->getProxTabTabela() . '" class="' . $strClassProtocolo . '" alt="" title="">' . $procedimento->getStrProtocoloProcedimentoFormatado() . '</a>';
      $strResultado .= '</td>';

      if ($objTramitaEmBlocoProtocoloDTO->getStrStaEstadoBloco() != TramiteEmBlocoRN::$TE_ABERTO) {
        $strResultado .= '<td align="center">'. PaginaSEI::tratarHTML($objTramitaEmBlocoProtocoloDTO->getStrNomeUsuario()) . '</td>';
        $strResultado .= '<td align="center">'. PaginaSEI::tratarHTML($objTramitaEmBlocoProtocoloDTO->getDthEnvio()) . '</td>';
        $strResultado .= '<td align="center">'. PaginaSEI::tratarHTML($objTramitaEmBlocoProtocoloDTO->getStrUnidadeDestino()) . '</td>';
      } else {
        $strResultado .= str_repeat('<td align="center"></td>' . "\n", 3);
      }


      // print_r($objTramitaEmBlocoProtocoloDTO->getNumIdAndamento()); die('asas');
      $strResultado .= '<td align="center">' . "\n";
      switch ($objTramitaEmBlocoProtocoloDTO->getNumIdAndamento()) {
        case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO:
        case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_ENVIADOS_REMETENTE:
        case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_METADADOS_RECEBIDO_DESTINATARIO:
        case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO:
        case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_ENVIADO_DESTINATARIO:
          $strResultado .= '<img src="' . PENIntegracao::getDiretorioImagens() . '/em_processamento.png" title="Aguardando Processamento" style="width:16px; alt="Aguardando Processamento" />';
            break;
        case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE:
          $strResultado .= '<img src="' . PENIntegracao::getDiretorioImagens() . '/icone-concluido.svg" title="Conclu�do" style="width:16px; alt="Conclu�do" />';
            break;
        case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CIENCIA_RECUSA:
        case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO:
          $strResultado .= '<img src="' . PENIntegracao::getDiretorioImagens() . '/icone-recusa.svg" title="Recusado" style="width:16px; alt="Recusado" />';
            break;
        case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO:
        case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO_AUTOMATICAMENTE:
          $strResultado .= '<img src="' . PENIntegracao::getDiretorioImagens() . '/falhou.png" title="Cancelado" style="width:16px; alt="Cancelado" />';
            break;
        case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_NAO_INICIADO:       
          if(is_null($objTramitaEmBlocoProtocoloDTO->getNumIdAndamento())){
          $strResultado .= '<img src="' . PENIntegracao::getDiretorioImagens() . '/nao_iniciado.png" title="Em aberto" style="width:16px;" alt="Em aberto" />';
              break;
          }
          $strResultado .= '<img src="' . PENIntegracao::getDiretorioImagens() . '/em_processamento.png" title="Aguardando Processamento" style="width:16px; alt="Aguardando Processamento" />';
            break;
          default:
          $strResultado .= '<img src="' . PENIntegracao::getDiretorioImagens() . '/nao_iniciado.png" title="Em aberto" style="width:16px;" alt="Em aberto" />';
            break;
      }
      $strResultado .= '</td>' . "\n";

      $strResultado .= '<td align="center">'. "\n";

      $estadosBloqueados = array(
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_ENVIADOS_REMETENTE,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_METADADOS_RECEBIDO_DESTINATARIO,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_ENVIADO_DESTINATARIO,
        ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE
      );
      if (        
        $objTramitaEmBlocoProtocoloDTO->getNumIdUnidadeBloco() == SessaoSEI::getInstance()->getNumIdUnidadeAtual()
        && (
          !in_array($objTramitaEmBlocoProtocoloDTO->getNumIdAndamento(), $estadosBloqueados)
          || is_null($objTramitaEmBlocoProtocoloDTO->getNumIdAndamento())
        )
        ) {
        $strResultado .= '<a onclick="onCLickLinkDelete(\''
          .$objSessaoSEI->assinarLink('controlador.php?acao=pen_tramita_em_bloco_protocolo_excluir&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao'].'&hdnInfraItensSelecionados='.$numIdBlocoProtocolo.'&id_bloco='.$_GET['id_bloco']).'\', this)" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="'.PaginaSEI::getInstance()->getIconeExcluir().'" title="Excluir processo" alt="Excluir processo" class="infraImg" /></a>&nbsp;';
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

 /* Personalize o estilo da pagina��o */
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
$objPaginaSEI->montarJavaScript();
$acaoOrigem=$_GET['acao_origem'];
$acao=$_GET['acao'];
$idBloco=$_GET['id_bloco']; ?>
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
    form.attr('action', '<?php print $objSessaoSEI->assinarLink('controlador.php?acao=pen_tramita_em_bloco_protocolo_listar&acao_origem='.$acaoOrigem.'&acao_retorno='.$acao.'&id_bloco='.$idBloco); ?>');
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

    if (confirm('Confirma retirada do processo ' + strTipoDocumento + ' do bloco de tr�mite externo?')) {
      window.location = url;
    }
  }

  function onClickBtnExcluir() {

    try {
      var len = jQuery('input[name*=chkInfraItem]:checked').length;

      if (len > 0) {
        if (confirm('Confirma retirada de ' + len + ' protocolo(s) selecionado(s) do bloco de tr�mite externo?')) {
          var form = jQuery('#frmProcessosListar');
          form.attr('action', '<?php print $objSessaoSEI->assinarLink('controlador.php?acao=pen_tramita_em_bloco_protocolo_excluir&acao_origem='.$acaoOrigem.'&acao_retorno='.$acao.'&id_bloco='.$idBloco); ?>');
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
          if (confirm('Confirma a exclus�o de ' + len + ' mapeamento(s) ?')) {
            var form = jQuery('#frmProcessosListar');
              form.attr('action', '<?php print $objSessaoSEI->assinarLink('controlador.php?acao=pen_tramita_em_bloco_protocolo_cancelar&acao_origem='.$acaoOrigem.'&acao_retorno='.$acao.'&id_bloco='.$idBloco); ?>');
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
    if (confirm('Confirma a cancelamento do tr�mite "' + strTipoDocumento + '"?')) {
        window.location = url;
    }
  }

  function onClickBtnTramitarProcessos() {
    try {
      var len = jQuery('input[name*=chkInfraItem]:checked').length;
      if (len > 0) {
        var form = jQuery('#frmLoteListar');
        form.attr('action', '<?php print $objSessaoSEI->assinarLink('controlador.php?acao=pen_expedir_bloco&acao_origem=pen_tramita_em_bloco_protocolo_listar&acao_retorno=pen_tramita_em_bloco_protocolo_listar&tramite_em_bloco=1'); ?>');
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
          { targets: [0, 3, 4], orderable: false } // Define as colunas 1 e 5 como n�o orden�veis
        ],

      "language": {
        "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
        "lengthMenu": "Mostrar _MENU_ registros por p�gina",
        "infoEmpty": "Mostrando 0 a 0 de 0 registros",
        "zeroRecords": "Nenhum registro encontrado",
        "paginate": {
          "previous": "Anterior",
          "next": "Pr�ximo"
        },
      }
    } );
  } );

</script>
<?
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody($strTitulo, 'onload="inicializar();"');
?>
<form id="frmProcessosListar" method="post" action="<?= $objSessaoSEI->assinarLink('controlador.php?acao=' . $acao . '&acao_origem=' . $acao.'&id_bloco='.$idBloco) ?>">
  <?
  $objPaginaSEI->montarBarraComandosSuperior($arrComandos);
  $objPaginaSEI->abrirAreaDados('4.5em');
  ?>

  <label id="lblProcedimentoFormatado" for="txtProcedimentoFormatado" accesskey="" class="infraLabelOpcional">N�mero do Processo:</label>
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