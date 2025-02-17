<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Consulta os logs do estado do procedimento ao ser expedido
 */
try {
    session_start();

    InfraDebug::getInstance()->setBolLigado(false);
    InfraDebug::getInstance()->setBolDebugInfra(true);
    InfraDebug::getInstance()->limpar();

    $objSessaoSEI = SessaoSEI::getInstance();

    $objSessaoSEI->validarPermissao('pen_procedimento_expedir');
    $objGenericoBD = new GenericoBD(BancoSEI::getInstance());

  if(array_key_exists('metodo', $_GET)) {

      ob_clean();
      header('Content-type: text/xml');


    switch ($_GET['metodo']){

      // @join_tec US008.02 (#23092)
      case 'baixarReciboEnvio':
          header('Content-Disposition: attachment; filename="recibo_de_envio_do_tramite.xml"');

        try {


          if(array_key_exists('id_tramite', $_GET) && array_key_exists('id_tarefa', $_GET)) {

            $objReciboTramiteRN = new ReciboTramiteRN();
            $arrObjReciboTramiteDTO = $objReciboTramiteRN->downloadReciboEnvio($_GET['id_tramite']);

            if(empty($arrObjReciboTramiteDTO)) {
                throw new InfraException('Módulo do Tramita: O recibo ainda não foi recebido.');
            }

            $objReciboTramiteHashDTO = new ReciboTramiteHashDTO();
            $objReciboTramiteHashDTO->setNumIdTramite($_GET['id_tramite']);
            $objReciboTramiteHashDTO->setStrTipoRecibo(ProcessoEletronicoRN::$STA_TIPO_RECIBO_ENVIO);
            $objReciboTramiteHashDTO->retStrHashComponenteDigital();

            $arrObjReciboTramiteHashDTO = $objGenericoBD->listar($objReciboTramiteHashDTO);

            foreach($arrObjReciboTramiteDTO as $objReciboTramiteDTO) {

                $dthTimeStamp = InfraData::getTimestamp($objReciboTramiteDTO->getDthRecebimento());

                print '<reciboDeEnvio>';
                print '<IDT>'.$objReciboTramiteDTO->getNumIdTramite().'</IDT>';
                print '<NRE>'.$objReciboTramiteDTO->getStrNumeroRegistro().'</NRE>';
                print '<dataDeRecebimentoDoUltimoComponenteDigital>'.date('c', $dthTimeStamp).'</dataDeRecebimentoDoUltimoComponenteDigital>';

              if($arrObjReciboTramiteHashDTO && is_array($arrObjReciboTramiteHashDTO)) {
                    $arrObjReciboTramiteHashDTO = InfraArray::converterArrInfraDTO($arrObjReciboTramiteHashDTO, 'HashComponenteDigital');
                    ksort($arrObjReciboTramiteHashDTO);

                foreach($arrObjReciboTramiteHashDTO as $hash){
                  print '<hashDoComponenteDigital>'.$hash.'</hashDoComponenteDigital>';
                }
              }
                print '</reciboDeEnvio>';
                print '<cadeiaDoCertificado>'.$objReciboTramiteDTO->getStrCadeiaCertificado().'</cadeiaDoCertificado>';
                print '<hashDaAssinatura>'.$objReciboTramiteDTO->getStrHashAssinatura().'</hashDaAssinatura>';
            }

          }
        }
        catch(InfraException $e){

            ob_clean();
            print '<?xml version="1.0" encoding="UTF-8" ? >'.PHP_EOL;
            print '<erro>';
            print '<mensagem>'.$e->getStrDescricao().'</mensagem>';
            print '</erro>';
        }

          break;

      case 'baixarReciboRecebimento':
          header('Content-Disposition: attachment; filename="recibo_de_conclusao_do_tramite.xml"');

        try {

          if(array_key_exists('id_tramite', $_GET) && array_key_exists('id_tarefa', $_GET)) {

            $objReciboTramiteRN = new ReciboTramiteRN();
            $arrParametros = ["id_tramite" => $_GET['id_tramite'], "id_tarefa" => $_GET['id_tarefa']];
            $arrObjReciboTramiteDTO = $objReciboTramiteRN->listarPorAtividade($arrParametros);

            if(empty($arrObjReciboTramiteDTO)) {
                throw new InfraException('Módulo do Tramita: O recibo ainda não foi recebido.');
            }

            $objReciboTramiteHashDTO = new ReciboTramiteHashDTO();
            $objReciboTramiteHashDTO->setNumIdTramite($_GET['id_tramite']);
            $objReciboTramiteHashDTO->retStrHashComponenteDigital();

            if($_GET['id_tarefa'] == ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO)) {
                $objReciboTramiteHashDTO->setStrTipoRecibo(ProcessoEletronicoRN::$STA_TIPO_RECIBO_CONCLUSAO_RECEBIDO);

            }else{
                $objReciboTramiteHashDTO->setStrTipoRecibo(ProcessoEletronicoRN::$STA_TIPO_RECIBO_CONCLUSAO_ENVIADO);

            }

            $arrObjReciboTramiteHashDTO = $objGenericoBD->listar($objReciboTramiteHashDTO);

            foreach($arrObjReciboTramiteDTO as $objReciboTramiteDTO) {

                        $dthTimeStamp = InfraData::getTimestamp($objReciboTramiteDTO->getDthRecebimento());

                        print '<recibo>';
                        print '<IDT>'.$objReciboTramiteDTO->getNumIdTramite().'</IDT>';
                        print '<NRE>'.$objReciboTramiteDTO->getStrNumeroRegistro().'</NRE>';
                        print '<dataDeRecebimento>'.date('c', $dthTimeStamp).'</dataDeRecebimento>';

                        $strHashAssinatura = $objReciboTramiteDTO->getStrHashAssinatura();

              if($arrObjReciboTramiteHashDTO && is_array($arrObjReciboTramiteHashDTO)) {
                    $arrObjReciboTramiteHashDTO = InfraArray::converterArrInfraDTO($arrObjReciboTramiteHashDTO, 'HashComponenteDigital');
                    ksort($arrObjReciboTramiteHashDTO);

                foreach($arrObjReciboTramiteHashDTO as $hash){
                  print '<hashDoComponenteDigital>'.$hash.'</hashDoComponenteDigital>';
                }
              }

                print '</recibo>';
                print '<cadeiaDoCertificado>'.$objReciboTramiteDTO->getStrCadeiaCertificado().'</cadeiaDoCertificado>';
                print '<hashDaAssinatura>'.$objReciboTramiteDTO->getStrHashAssinatura().'</hashDaAssinatura>';
            }

          }
        }
        catch(InfraException $e){

            ob_clean();
            print '<?xml version="1.0" encoding="UTF-8" ? >'.PHP_EOL;
            print '<erro>';
            print '<mensagem>'.$e->getStrDescricao().'</mensagem>';
            print '</erro>';
        }
          break;
    }

      exit(0);
  }

    $strProprioLink = 'controlador.php?acao='.$_GET['acao'].'&acao_origem='.$_GET['acao_origem'].'&acao_retorno='.$_GET['acao_retorno'].'&id_procedimento='.$_GET['id_procedimento'];
    $strTitulo = 'Consultar Recibos';

  if(!array_key_exists('id_procedimento', $_GET) || empty($_GET['id_procedimento'])) {

      throw new InfraException('Módulo do Tramita: Código do procedimento não foi informado');
  }

    $objProcedimentoAndamentoDTO = new ProcedimentoAndamentoDTO();
    $objProcedimentoAndamentoDTO->retTodos();
    $objProcedimentoAndamentoDTO->retNumIdEstruturaDestino();
    $objProcedimentoAndamentoDTO->retNumIdEstruturaOrigem();
    $objProcedimentoAndamentoDTO->setOrdDblIdTramite(InfraDTO::$TIPO_ORDENACAO_ASC);
    $objProcedimentoAndamentoDTO->setOrdDthData(InfraDTO::$TIPO_ORDENACAO_ASC);
    $objProcedimentoAndamentoDTO->setDblIdProcedimento($_GET['id_procedimento']);

  if(array_key_exists('txtTextoPesquisa', $_POST) && !empty($_POST['txtTextoPesquisa'])) {
      $objProcedimentoAndamentoDTO->setStrMensagem('%'.$_POST['txtTextoPesquisa'].'%', InfraDTO::$OPER_LIKE);
  }

    $objPaginaSEI = PaginaSEI::getInstance();
    $objPaginaSEI->setTipoPagina(InfraPagina::$TIPO_PAGINA_SIMPLES);
    $objPaginaSEI->prepararPaginacao($objProcedimentoAndamentoDTO, 400);

    $objProcedimentoAndamentoRN = new ProcedimentoAndamentoRN();
    $arrObjProcedimentoAndamentoDTO = $objProcedimentoAndamentoRN->listar($objProcedimentoAndamentoDTO);

    $objPaginaSEI->processarPaginacao($objProcedimentoAndamentoDTO);

    $numRegistros = count($arrObjProcedimentoAndamentoDTO);

  if(!empty($arrObjProcedimentoAndamentoDTO)) {

      $arrAgruparProcedimentoAndamentoDTO = [];
    foreach($arrObjProcedimentoAndamentoDTO as &$objProcedimentoAndamentoDTO){
      if(ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO) == $objProcedimentoAndamentoDTO->getNumTarefa()) {
        $numIdEstrutura = $objProcedimentoAndamentoDTO->getNumIdEstruturaDestino();
      } elseif (ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO) == $objProcedimentoAndamentoDTO->getNumTarefa()) {
          $numIdEstrutura = $objProcedimentoAndamentoDTO->getNumIdEstruturaOrigem();
      } elseif (ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_DOCUMENTO_AVULSO_RECEBIDO) == $objProcedimentoAndamentoDTO->getNumTarefa()) {
          $numIdEstrutura = $objProcedimentoAndamentoDTO->getNumIdEstruturaOrigem();
      }


        $key = $objProcedimentoAndamentoDTO->getDblIdTramite() . '-' . $numIdEstrutura . '-' . $objProcedimentoAndamentoDTO->getNumTarefa();
        $arrAgruparProcedimentoAndamentoDTO[$key][] = $objProcedimentoAndamentoDTO;
    }

      $strResultado = '';
      $strResultado .= '<table width="99%" class="infraTable">'."\n";
      $strResultado .= '<tr>';
      $strResultado .= '<th class="infraTh" width="20%">Data</th>'."\n";
      $strResultado .= '<th class="infraTh">Operação</th>'."\n";
      $strResultado .= '<th class="infraTh" width="15%">Situação</th>'."\n";
      $strResultado .= '</tr>'."\n";
      $strCssTr = '';

      $idCount = 1;
    foreach($arrAgruparProcedimentoAndamentoDTO as $key => $arrObjProcedimentoAndamentoDTO) {
        [$dblIdTramite, $numIdEstrutura, $numTarefa] = explode('-', $key);
        $objReturn = PenAtividadeRN::retornaAtividadeDoTramiteFormatado($dblIdTramite, $numIdEstrutura, $numTarefa);
        $strResultado .= '<tr>';
        $strResultado .= '<td valign="middle" colspan="2">'
        . '<img class="imagPlus" align="absbottom" src=' . ProcessoEletronicoINT::getCaminhoIcone("/infra_js/arvore/plus.gif") . ' onclick="toggleTr('.$idCount.', this)" title="Maximizar" />'
        . ''.$objReturn->strMensagem.'</td>';
        $strResultado .= '<td valign="middle" align="center">';

      if($numTarefa == ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO)) {
          $strResultado .= '<a href="'.$objSessaoSEI->assinarLink($strProprioLink.'&metodo=baixarReciboEnvio&id_tarefa='.$numTarefa.'&id_tramite='.$dblIdTramite).'"><img class="infraImg" src="'.PENIntegracao::getDiretorio().'/imagens/page_red.png" alt="Recibo de Confirmação de Envio" title="Recibo de Confirmação de Envio" /></a>';
      }

      if($objReturn->bolReciboExiste) {
          $strResultado .= '<a href="'.$objSessaoSEI->assinarLink($strProprioLink.'&metodo=baixarReciboRecebimento&id_tarefa='.$numTarefa.'&id_tramite='.$dblIdTramite).'"><img class="infraImg" src="'.PENIntegracao::getDiretorio().'/imagens/page_green.png" alt="Recibo de Conclusão de Trâmite" title="Recibo de Conclusão de Trâmite" /></a>';
      }
        $strResultado .= '</td>';
        $strResultado .= '<tr>';

      foreach($arrObjProcedimentoAndamentoDTO as $objProcedimentoAndamentoDTO) {

          $strCssTr = ($strCssTr == 'infraTrClara') ? 'infraTrEscura' : 'infraTrClara';
          $strResultado .= '<tr class="'.$strCssTr.' extra_hidden_'.$idCount.'" style="display:none;">';
          $strResultado .= '<td align="center">'.$objProcedimentoAndamentoDTO->getDthData().'</td>';
          $strResultado .= '<td>'.$objProcedimentoAndamentoDTO->getStrMensagem().'</td>';
          $strResultado .= '<td align="center">';

        if($objProcedimentoAndamentoDTO->getStrSituacao() == 'S') {
            $strResultado .= '<img src="'.PENIntegracao::getDiretorio().'/imagens/estado_sucesso.png" title="Concluído" alt="Concluído" />';
        }
        else {
            $strResultado .= '<img src="'.PENIntegracao::getDiretorio().'/imagens/estado_falhou.png" title="Falhou" alt="Falhou" />';
        }

          $strResultado .= '</td>';
          $strResultado .= '</tr>'."\n";

          $i++;
      }
        $idCount++;
    }
      $strResultado .= '</table>';
  }
}
catch(Exception $e){
    $objPaginaSEI->processarExcecao($e);
}


$objPaginaSEI->montarDocType();
$objPaginaSEI->abrirHtml();
$objPaginaSEI->abrirHead();
$objPaginaSEI->montarMeta();
$objPaginaSEI->montarTitle(':: '.$objPaginaSEI->getStrNomeSistema().' - '.$strTitulo.' ::');
$objPaginaSEI->montarStyle();
$objPaginaSEI->abrirStyle();
?>
#lblTextoPesquisa {position:absolute;left:0%;top:10%;}
#txtTextoPesquisa {position:absolute;left:0%;top:26%;width:50%;}

#lblContextoSubstituicao {position:absolute;left:0%;top:62%;}
#txtContextoSubstituicao {position:absolute;left:0%;top:77%;width:50%;}

<?php
$objPaginaSEI->fecharStyle();
$objPaginaSEI->montarJavaScript();
$objPaginaSEI->abrirJavaScript();
?>

var objAutoCompletarInteressadoRI1225 = null;

function inicializar(){
  infraEfeitoTabelas();
}

function toggleTr(number, obj) {
    jQuery('.extra_hidden_'+number).toggle();
    if (jQuery('.extra_hidden_'+number).is(':hidden')) {
        jQuery(obj).attr('src', <?php ProcessoEletronicoINT::getCaminhoIcone("/infra_js/arvore/plus.gif") ?>);
    } else {
        jQuery(obj).attr('src', <?php ProcessoEletronicoINT::getCaminhoIcone("/infra_js/arvore/minus.gif") ?>);
    }
}


function pesquisar(){
  document.getElementById('frmAcompanharEstadoProcesso').action='<?php print $objSessaoSEI->assinarLink($strProprioLink); ?>';
  document.getElementById('frmAcompanharEstadoProcesso').submit();
}

function tratarEnter(ev){
    var key = infraGetCodigoTecla(ev);
    if (key == 13){
        pesquisar();
    }
    return true;
}
<?php
$objPaginaSEI->fecharJavaScript();
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody($strTitulo, 'onload="inicializar();"');
?>
<form id="frmAcompanharEstadoProcesso" method="post" action="<?php print $objSessaoSEI->assinarLink($strProprioLink); ?>">
  <?php if($numRegistros > 0) : ?>
        <?php $objPaginaSEI->montarAreaTabela($strResultado, $numRegistros); ?>
    <?php else: ?>
        <div style="clear:both"></div>
        <p>Nenhum trâmite realizado para esse processo.</p>
    <?php endif; ?>
</form>
<?php $objPaginaSEI->fecharBody(); ?>
<?php $objPaginaSEI->fecharHtml(); ?>
