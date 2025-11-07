<?php
try {
    include_once DIR_SEI_WEB . '/SEI.php';

    session_start();

    $objSessaoSEI = SessaoSEI::getInstance();
    $objPaginaSEI = PaginaSEI::getInstance();

    $objSessaoSEI->validarLink();

    $objPaginaSEI->salvarCamposPost(['hdnIdProtocolo', 'selBlocos']);
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
    case 'pen_excluir_processo_em_bloco_tramite':
      try {
          $objProcedimentoDTO = new ProcedimentoDTO();
          $objProcedimentoDTO->retStrProtocoloProcedimentoFormatado();
          $objProcedimentoDTO->setDblIdProcedimento($_GET['id_procedimento']);

          $objProcedimentoRN = new ProcedimentoRN();
          $procedimento = $objProcedimentoRN->consultarRN0201($objProcedimentoDTO);

          $objPenBlocoProcessoDTO = new PenBlocoProcessoDTO();
          $objPenBlocoProcessoDTO->setDblIdProtocolo($_GET['id_procedimento']);
          $objPenBlocoProcessoDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
          $objPenBlocoProcessoDTO->retNumIdAndamento();
          $objPenBlocoProcessoDTO->retDblIdProtocolo();
          $objPenBlocoProcessoDTO->retNumIdBlocoProcesso();
          $objPenBlocoProcessoDTO->retNumIdBloco();
 
          $objPenBlocoProcessoRN = new PenBlocoProcessoRN();
          $arrObjPenBlocoProcessoDTO = $objPenBlocoProcessoRN->listar($objPenBlocoProcessoDTO);
        foreach($arrObjPenBlocoProcessoDTO as $objPenBlocoProcessoDTO){
            $concluido = [ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CIENCIA_RECUSA, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO_AUTOMATICAMENTE, ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE];
          if ($objPenBlocoProcessoDTO->getNumIdAndamento() === null || !in_array($objPenBlocoProcessoDTO->getNumIdAndamento(), $concluido)) {
            $objPenBlocoProcessoRN->excluir([$objPenBlocoProcessoDTO]);
           
            $objPenBlocoProcessoRN = new PenBlocoProcessoRN();
            $objPenBlocoProcessoRN->atualizarEstadoDoBloco($objPenBlocoProcessoDTO->getNumIdBloco());
          }          
        }       

          $strMensagem = 'O processo "' . $procedimento->getStrProtocoloProcedimentoFormatado() . '" foi removido com sucesso do bloco de trâmite externo';
      } catch (Exception $e) {
          $strMensagem = $e->getMessage();
          PaginaSEI::getInstance()->processarExcecao($e);
      }
      ?>
      <script type="text/javascript">
        alert('<?php echo $strMensagem ?>');
        parent.parent.document.getElementById('ifrArvore').src = '<?=SessaoSEI::getInstance()->assinarLink('controlador.php?acao=procedimento_visualizar&id_procedimento='.$_GET['id_procedimento'].'&acao_origem='.$_GET['acao'].'&montar_visualizacao=1')?>';
      </script>
        <?php
        exit(0);
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
          if ($idBlocoExterno == null) {
            header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao']));
            exit(0);
          }

            $objPenBlocoProcessoRN = new PenBlocoProcessoRN();
            $validar = $objPenBlocoProcessoRN->validarQuantidadeDeItensNoBloco($idBlocoExterno, [$_GET['id_procedimento']]);

          if ($validar !== false) {
              $objPaginaSEI->adicionarMensagem($validar, InfraPagina::$TIPO_MSG_ERRO);

              header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao']));
              exit(0);
          }

            // Esse quem vai ficar
            $objPenBlocoProcessoDTO = new PenBlocoProcessoDTO();
            $objPenBlocoProcessoDTO->setNumIdBlocoProcesso(null);
            $objPenBlocoProcessoDTO->setDblIdProtocolo($_GET['id_procedimento']);
            $objPenBlocoProcessoDTO->setNumIdBloco($idBlocoExterno);
            $dthRegistro = date('d/m/Y H:i:s');
            $objPenBlocoProcessoDTO->setDthRegistro($dthRegistro);
            $objPenBlocoProcessoDTO->setDthAtualizado($dthRegistro);
            $objPenBlocoProcessoDTO->setNumIdUsuario($objSessaoSEI->getNumIdUsuario());
            $objPenBlocoProcessoDTO->setNumIdUnidade($objSessaoSEI->getNumIdUnidadeAtual());

            $validar = $objPenBlocoProcessoRN->validarBlocoDeTramite($_GET['id_procedimento']);

          if ($validar) {
              $objPaginaSEI->adicionarMensagem($validar, InfraPagina::$TIPO_MSG_AVISO);
              header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao']));
              exit(0);
          }
         
            //Verifica processo aberto em outra unidade.
            $objInfraException = new InfraException();
            $objExpedirProcedimentosRN = new ExpedirProcedimentoRN();
            $objExpedirProcedimentosRN->verificarProcessosAbertoNaUnidade($objInfraException, [$_GET['id_procedimento']]);
            $mensagemDeErro = $objExpedirProcedimentosRN->trazerTextoSeContemValidacoes($objInfraException);
          if (!is_null($mensagemDeErro)) {
              $objPaginaSEI->adicionarMensagem($mensagemDeErro, InfraPagina::$TIPO_MSG_ERRO);
              header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao']));
              exit(0);
          }

            $objExpedirProcedimentosRN->validarProcessoAbertoEmOutraUnidade($objInfraException, [$_GET['id_procedimento']]);
            $mensagemDeErro = $objExpedirProcedimentosRN->trazerTextoSeContemValidacoes($objInfraException);
          if (!is_null($mensagemDeErro)) {
              $objPaginaSEI->adicionarMensagem($mensagemDeErro, InfraPagina::$TIPO_MSG_ERRO);
              header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . PaginaSEI::getInstance()->getAcaoRetorno() . '&acao_origem=' . $_GET['acao']));
              exit(0);
          }          

            $objPenBlocoProcessoDTO = $objPenBlocoProcessoRN->cadastrar($objPenBlocoProcessoDTO);
            $strMensagem = 'Processo "' . $procedimento->getStrProtocoloProcedimentoFormatado() . '" adicionado ao bloco';
        } catch (Exception $e) {
            $strMensagem = $e->getMessage();
            PaginaSEI::getInstance()->processarExcecao($e);
        }
        ?>
        <script type="text/javascript">
          alert('<?php echo $strMensagem ?>');
          parent.parent.document.getElementById('ifrArvore').src = '<?=SessaoSEI::getInstance()->assinarLink('controlador.php?acao=procedimento_visualizar&id_procedimento='.$_GET['id_procedimento'].'&acao_origem='.$_GET['acao'].'&montar_visualizacao=1')?>';
        </script>
            <?php
            exit(0);
      }
        break;
    case 'pen_tramita_em_bloco_adicionar':
        $arrProtocolosOrigem = array_merge(
            $objPaginaSEI->getArrStrItensSelecionados('Gerados'),
            $objPaginaSEI->getArrStrItensSelecionados('Recebidos'),
            $objPaginaSEI->getArrStrItensSelecionados('Detalhado')
        );
        $strIdItensSelecionados = $strIdItensSelecionados ?: $_GET['processos'];
        $strTitulo = 'Incluir Processo(s) no Bloco de Trâmite';

      if (isset($_POST['sbmCadastrarProcessoEmBloco'])) {

        try {
            $bolInclusaoSucesso = false;
            $arrMensagensErros = [];
            $arrProtocolosOrigemProtocolo = explode(',', $strIdItensSelecionados);

            // Refatorar validarQuantidadeDeItensNoBloco
            $objPenBlocoProcessoRN = new PenBlocoProcessoRN();
            $validar = $objPenBlocoProcessoRN->validarQuantidadeDeItensNoBloco($idBlocoExterno, $arrProtocolosOrigemProtocolo);

          if ($validar !== false) {
            $objPaginaSEI->adicionarMensagem($validar, InfraPagina::$TIPO_MSG_ERRO);

            header('Location: '.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao'].'&acao_origem='.$_GET['acao']));
            exit(0);
          }

          foreach ($arrProtocolosOrigemProtocolo as $idItensSelecionados) {
              $bolInclusaoErro = false;
              $objPenBlocoProcessoDTO = new PenBlocoProcessoDTO();
              $objPenBlocoProcessoDTO->setDblIdProtocolo($idItensSelecionados);
              $objPenBlocoProcessoDTO->setNumIdBloco($idBlocoExterno);
              $objPenBlocoProcessoDTO->retNumIdBlocoProcesso();
              $objPenBlocoProcessoDTO->retNumIdBloco();
              $dtRegistro = date('d/m/Y H:i:s');
              $objPenBlocoProcessoDTO->setDthRegistro($dtRegistro);
              $objPenBlocoProcessoDTO->setDthAtualizado($dtRegistro);
              $objPenBlocoProcessoDTO->setNumIdUsuario($objSessaoSEI->getNumIdUsuario());
              $objPenBlocoProcessoDTO->setNumIdUnidade($objSessaoSEI->getNumIdUnidadeAtual());

              $validarPreCondicoesIncluir = $objPenBlocoProcessoRN->validarBlocoDeTramite($idItensSelecionados);

            if ($validarPreCondicoesIncluir != false) {
              $bolInclusaoErro = true;
              $arrMensagensErros[] = $validarPreCondicoesIncluir;
            }else{
                $objInfraException = new InfraException();
                $objExpedirProcedimentosRN = new ExpedirProcedimentoRN();
                $objExpedirProcedimentosRN->verificarProcessosAbertoNaUnidade($objInfraException, [$idItensSelecionados]);
                $mensagemDeErro = $objExpedirProcedimentosRN->trazerTextoSeContemValidacoes($objInfraException);
              if (!is_null($mensagemDeErro)) {
                $bolInclusaoErro = true;
                $arrMensagensErros[] = $mensagemDeErro;
              }
  
              $objExpedirProcedimentosRN->validarProcessoAbertoEmOutraUnidade($objInfraException, array($idItensSelecionados), true);
              $mensagemDeErro = $objExpedirProcedimentosRN->trazerTextoSeContemValidacoes($objInfraException);
              if (!is_null($mensagemDeErro)) {
                  $bolInclusaoErro = true;
                  $arrMensagensErros[] = $mensagemDeErro;
              }

              if ($bolInclusaoErro === false) {
                  $bolInclusaoSucesso = true;
                  $objPenBlocoProcessoDTO = $objPenBlocoProcessoRN->cadastrar($objPenBlocoProcessoDTO);
              }
             
            }
          }

          foreach ($arrMensagensErros as $mensagemErro) {
              $objPaginaSEI->adicionarMensagem($mensagemErro, InfraPagina::$TIPO_MSG_ERRO);
          }
            
            $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
            $objTramiteEmBlocoDTO->setNumId($idBlocoExterno);
            $objTramiteEmBlocoDTO->retNumOrdem();       
            $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
            $blocoResultado = $objTramiteEmBlocoRN->consultar($objTramiteEmBlocoDTO);

          if ($bolInclusaoSucesso) {
              $mensagemSucesso = "Processo(s) incluído(s) com sucesso no bloco {$blocoResultado->getNumOrdem()}";
          }

          if ($bolInclusaoSucesso && !empty($arrMensagensErros)) {
              $mensagemSucesso = "Os demais processos selecionados foram incluídos com sucesso no bloco {$blocoResultado->getNumOrdem()}";
          }

            $objPaginaSEI->adicionarMensagem($mensagemSucesso, 5);
        } catch (Exception $e) {
            PaginaSEI::getInstance()->processarExcecao($e);
        }
      }
        break;
    default:
        throw new InfraException("Módulo do Tramita: Ação '" . $_GET['acao'] . "' não reconhecida.");
  }

    //Monta o select dos blocos
    $arrMapIdBloco = [];

    $objTramiteEmBlocoDTO = new TramiteEmBlocoDTO();
    $objTramiteEmBlocoDTO->setStrStaEstado(TramiteEmBlocoRN::$TE_ABERTO); //($objSessaoSEI->getNumIdUnidadeAtual());
    $objTramiteEmBlocoDTO->setNumIdUnidade($objSessaoSEI->getNumIdUnidadeAtual());
    $objTramiteEmBlocoDTO->retNumId();
    $objTramiteEmBlocoDTO->retNumOrdem();
    $objTramiteEmBlocoDTO->retNumIdUnidade();
    $objTramiteEmBlocoDTO->retStrDescricao();
  if ($_GET['acao'] != 'pen_tramita_em_bloco_adicionar') {
      PaginaSEI::getInstance()->prepararOrdenacao($objTramiteEmBlocoDTO, 'Id', InfraDTO::$TIPO_ORDENACAO_DESC);
  }

    $objTramiteEmBlocoRN = new TramiteEmBlocoRN();
  foreach ($objTramiteEmBlocoRN->listar($objTramiteEmBlocoDTO) as $dados) {
      $arrMapIdBloco[$dados->getNumId()] = "{$dados->getNumOrdem()} - {$dados->getStrDescricao()}"; 
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

if ('<?php echo $acao ?>'=='pen_incluir_processo_em_bloco_tramite') {
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

<form id="frmProcessoEmBlocoCadastro" method="post" action="<?php echo SessaoSEI::getInstance()->assinarLink('controlador.php?acao=' . $acao . '&acao_origem=' . $acao . $strParametros) ?>">

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
  <input type="hidden" id="hdnIdProtocolo" name="hdnIdProtocolo" tabindex="<?php echo PaginaSEI::getInstance()->getProxTabDados() ?>" value="<?php echo $arrProtocolosOrigem ? implode(',', $arrProtocolosOrigem) : '' ?>" />

  <?php
    $objPaginaSEI->fecharAreaDados();
    $objPaginaSEI->montarBarraComandosInferior($arrComandos);
  ?>

</form>

<?php
$objPaginaSEI->fecharBody();
$objPaginaSEI->fecharHtml();
?>