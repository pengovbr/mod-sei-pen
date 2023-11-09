<?php

require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Consulta os logs do estado do procedimento ao ser expedido
 *
 */

session_start();

define('PEN_RECURSO_ATUAL', 'pen_map_orgaos_externos_listar');
define('PEN_RECURSO_BASE', 'pen_map_orgaos_externos');
define('PEN_PAGINA_TITULO', 'Relacionamento entre Orgãos');
define('PEN_PAGINA_GET_ID', 'id');


$objPagina = PaginaSEI::getInstance();
$objBanco = BancoSEI::getInstance();
$objSessao = SessaoSEI::getInstance();
$objDebug = InfraDebug::getInstance();


try {

    $objDebug->setBolLigado(false);
    $objDebug->setBolDebugInfra(true);
    $objDebug->limpar();

    $objSessao->validarLink();
    $objSessao->validarPermissao(PEN_RECURSO_ATUAL);
    
    $idOrgaoExterno = $_GET['id'];

    switch($_GET['acao']){
        case 'pen_map_orgaos_externos_mapeamento_gerenciar':
          try{

            $arrTiposProcessos = array_filter($_POST);
            foreach(array_keys($arrTiposProcessos) as $strKeyPost){
              if (substr($strKeyPost,0,10) == 'txtAssunto'){

                $objConsultaTipoProcedimentoDTO = new PenMapTipoProcedimentoDTO();
                $objConsultaTipoProcedimentoDTO->setNumIdTipoProcessoOrigem(substr($strKeyPost,10));
                $objConsultaTipoProcedimentoDTO->retDblId();
                $objConsultaTipoProcedimentoDTO->retNumIdTipoProcessoOrigem();

                $objConsultaTipoProcedimentoRN = new PenMapTipoProcedimentoRN();
                $objConsultaTipoProcedimentoDTO = $objConsultaTipoProcedimentoRN->consultar($objConsultaTipoProcedimentoDTO);

                $objMapeamentoAssuntoDTO = new PenMapTipoProcedimentoDTO();
                $objMapeamentoAssuntoDTO->setDblId($objConsultaTipoProcedimentoDTO->getDblId());
                $objMapeamentoAssuntoDTO->setNumIdTipoProcessoOrigem(substr($strKeyPost,10));
                $objMapeamentoAssuntoDTO->setNumIdTipoProcessoDestino($_POST['hdnIdAssunto'.substr($strKeyPost,10)]);
               
                $objAlterTipoProcedimentoRN = new PenMapTipoProcedimentoRN();
                $objAlterTipoProcedimentoRN->alterar($objMapeamentoAssuntoDTO);
               
              }
            }
            
            PaginaSEI::getInstance()->adicionarMensagem('Operação realizada com sucesso.');
            
    
          }catch(Exception $e){
            PaginaSEI::getInstance()->processarExcecao($e);
          } 

          header('Location: '.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao_origem'].'&acao_origem='.$_GET['acao'].'&id='.$_POST['hdnFlag']));
          die;
        case 'pen_map_orgaos_externos_mapeamento':
          $strTitulo = 'Mapeamento de Tipo de Processo';
          break;
    
        default:
          throw new InfraException("Ação '".$_GET['acao']."' não reconhecida.");
      }

    $arrComandos = array();

    $arrComandos[] = '<button type="submit" accesskey="P" id="btnPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';
  
    $objMapeamentoTipoProcedimentoDTO = new PenMapTipoProcedimentoDTO();
    $objMapeamentoTipoProcedimentoDTO->setNumIdMapOrgao($idOrgaoExterno);
    $objMapeamentoTipoProcedimentoDTO->retNumIdMapOrgao();
    $objMapeamentoTipoProcedimentoDTO->retNumIdTipoProcessoOrigem();
    $objMapeamentoTipoProcedimentoDTO->retNumIdTipoProcessoDestino();
    $objMapeamentoTipoProcedimentoDTO->retStrNomeTipoProcesso();
    $objMapeamentoTipoProcedimentoDTO->retStrAtivo();

    $objPenOrgaoExternoDTO = new PenOrgaoExternoDTO();
    $objPenOrgaoExternoDTO->setDblId($idOrgaoExterno);
    $objPenOrgaoExternoDTO->retStrOrgaoDestino();
    $objPenOrgaoExternoDTO->retStrOrgaoOrigem();
    

    $objPenOrgaoExternoRN = new PenOrgaoExternoRN();
    $objPenOrgaoExternoDTO = $objPenOrgaoExternoRN->consultar($objPenOrgaoExternoDTO);

  
      PaginaSEI::getInstance()->prepararPaginacao($objMapeamentoTipoProcedimentoDTO,100);
  
      $objMapeamentoTipoProcedimentoRN = new PenMapTipoProcedimentoRN();
      $arrObjMapeamentoAssuntoDTO = $objMapeamentoTipoProcedimentoRN->listar($objMapeamentoTipoProcedimentoDTO);
  
      PaginaSEI::getInstance()->processarPaginacao($objMapeamentoTipoProcedimentoDTO);
  
  
    $numRegistros = InfraArray::contar($arrObjMapeamentoAssuntoDTO);
  
    if ($numRegistros > 0){
  
        $arrComandos[] = '<button type="button" accesskey="S" id="btnSalvar" value="Salvar" onclick="gerenciar();" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
        $strLinkGerenciar = SessaoSEI::getInstance()->assinarLink('controlador.php?acao=pen_map_orgaos_externos_mapeamento_gerenciar&acao_origem='.$_GET['acao'].'&acao_retorno='.$_GET['acao'].$strParametros);
  
      $bolAcaoExcluir = SessaoSEI::getInstance()->verificarPermissao('mapeamento_assunto_excluir');
  
      $strResultado = '';
      $strAjaxVariaveis = '';
      $strAjaxInicializar = '';
  
      $strSumarioTabela = 'Tabela de mapeamento de tipo de processo.';
      $strCaptionTabela = 'tipos de processos para mapeamento';
  
      $strResultado .= '<table width="99%" class="infraTable" summary="'.$strSumarioTabela.'">'."\n";
      $strResultado .= '<caption class="infraCaption">'.PaginaSEI::getInstance()->gerarCaptionTabela($strCaptionTabela,$numRegistros).'</caption>';
      $strResultado .= '<tr>';
  
      $strResultado .= '<th width="45%" class="infraTh">'.PaginaSEI::getInstance()->getThOrdenacao($objMapeamentoTipoProcedimentoDTO,'Tipo de Processo Origem','id',$arrObjMapeamentoAssuntoDTO).'</th>'."\n";
      $strResultado .= '<th width="45%" class="infraTh">Tipo de Processo Destino</th>'."\n";
  
      $strResultado .= '</tr>'."\n";
      $strCssTr='';
      for($i = 0;$i < $numRegistros; $i++){
  
        $numIdAssuntoOrigem = $arrObjMapeamentoAssuntoDTO[$i]->getNumIdTipoProcessoOrigem();
        $numIdAssuntoDestino = $arrObjMapeamentoAssuntoDTO[$i]->getNumIdTipoProcessoDestino();
       
        if ($arrObjMapeamentoAssuntoDTO[$i]->getStrAtivo()=='S'){
          $strCssTr = ($strCssTr=='<tr class="infraTrClara">')?'<tr class="infraTrEscura">':'<tr class="infraTrClara">';
        }else{
          $strCssTr = '<tr class="trVermelha">';
        }
  
        $strResultado .= $strCssTr;

        $strResultado .= '<td>'.PaginaSEI::tratarHTML(AssuntoINT::formatarCodigoDescricaoRI0568($numIdAssuntoOrigem, $arrObjMapeamentoAssuntoDTO[$i]->getStrNomeTipoProcesso())).'</td>';

        $descricaoTipoProcedimento = '';
        if ($numIdAssuntoDestino != null) {
            $tipoProcedimentoDTO = new TipoProcedimentoDTO();
            $tipoProcedimentoDTO->retNumIdTipoProcedimento();
            $tipoProcedimentoDTO->retStrNome();
            $tipoProcedimentoDTO->setNumIdTipoProcedimento($numIdAssuntoDestino);

            $tipoProcedimentoRN = new TipoProcedimentoRN();
            $objTipoProcedimentoDTO = $tipoProcedimentoRN->consultarRN0267($tipoProcedimentoDTO);
            $descricaoTipoProcedimento = $numIdAssuntoDestino . ' - ' . $objTipoProcedimentoDTO->getStrNome();
        }

        $strResultado .= '<td> <input type="text" value="'.$descricaoTipoProcedimento.'" id="txtAssunto'.$numIdAssuntoOrigem.'" name="txtAssunto'.$numIdAssuntoOrigem.'" class="infraText" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'" style="width:99.5%" />
        <input type="hidden" id="hdnIdAssunto'.$numIdAssuntoOrigem.'" name="hdnIdAssunto'.$numIdAssuntoOrigem.'" class="infraText" value="'.$numIdAssuntoDestino.'" /></td>';
  
        $strResultado .= '</tr>'."\n";
  
        $strAjaxVariaveis .= 'var objAutoCompletarAssunto'.$numIdAssuntoOrigem.';'."\n";
  
        $strAjaxInicializar .= '  objAutoCompletarAssunto'.$numIdAssuntoOrigem.' = new infraAjaxAutoCompletar(\'hdnIdAssunto'.$numIdAssuntoOrigem.'\',\'txtAssunto'.$numIdAssuntoOrigem.'\', linkAutoCompletar);'."\n".
        '  objAutoCompletarAssunto'.$numIdAssuntoOrigem.'.prepararExecucao = function(){'."\n".
        '    return \'id_tabela_assuntos='. '1'.'&palavras_pesquisa=\'+document.getElementById(\'txtAssunto'.$numIdAssuntoOrigem.'\').value;'."\n".
        '  }'."\n".
        '  objAutoCompletarAssunto'.$numIdAssuntoOrigem.'.processarResultado = function(){'."\n".
        '    bolAlteracao = true;'."\n".
        '  }'."\n\n";
  
        //processarResultado
  
        // if ($numIdAssuntoDestino!=null){
        //   $strAjaxInicializar .= '  objAutoCompletarAssunto'.$numIdAssuntoOrigem.'.selecionar(\''.$numIdAssuntoDestino.'\',\''.PaginaSEI::getInstance()->formatarParametrosJavaScript(AssuntoINT::formatarCodigoDescricaoRI0568($codigoEstruturado, 'descricaoAssuntoOrigem')).'\');'."\n\n";
        // }
  
      }
  
      $strResultado .= '</table>';
    }

  
    $arrComandos[] = '<button type="button" accesskey="F" id="btnFechar" value="Fechar" onclick="location.href=\''.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.PaginaSEI::getInstance()->getAcaoRetorno().'&acao_origem='.$_GET['acao'].$strParametros.PaginaSEI::getInstance()->montarAncora($idOrgaoExterno)).'\'" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';
  
    $objTabelaAssuntosDTO = new TabelaAssuntosDTO();
    $objTabelaAssuntosDTO->retStrNome();
    $objTabelaAssuntosDTO->retStrSinAtual();
  
    if (!PaginaSEI::getInstance()->isBolPaginaSelecao()) {
      $objTabelaAssuntosDTO->setNumIdTabelaAssuntos(1);
    }else{
      $objTabelaAssuntosDTO->setStrSinAtual('S');
    }
  
    $objTabelaAssuntosRN = new TabelaAssuntosRN();
    $objTabelaAssuntosDTOOrigem = $objTabelaAssuntosRN->consultar($objTabelaAssuntosDTO);
  
    $strItensSelTabelaAssuntosDestino = TabelaAssuntosINT::montarSelectNomeMapeamento('null','&nbsp;',2,1);
  
  }catch(Exception $e){
    PaginaSEI::getInstance()->processarExcecao($e);
  } 
  
  PaginaSEI::getInstance()->montarDocType();
  PaginaSEI::getInstance()->abrirHtml();
  PaginaSEI::getInstance()->abrirHead();
  PaginaSEI::getInstance()->montarMeta();
  PaginaSEI::getInstance()->montarTitle(PaginaSEI::getInstance()->getStrNomeSistema().' - '.$strTitulo);
  PaginaSEI::getInstance()->montarStyle();
  PaginaSEI::getInstance()->abrirStyle();
  ?>
  #lblTabelaAssuntosOrigem {position:absolute;left:0%;top:0%;width:40%;}
  #txtTabelaAssuntosOrigem {position:absolute;left:0%;top:11%;width:40%;}
  
  #lblTabelaAssuntosDestino {position:absolute;left:0%;top:28%;width:40%;}
  #selTabelaAssuntosDestino {position:absolute;left:0%;top:39%;width:40%;}
  
  #lblPalavrasPesquisaMapeamentoAssuntos {position:absolute;left:0%;top:56%;width:70%;}
  #txtPalavrasPesquisaMapeamentoAssuntos {position:absolute;left:0%;top:67%;width:70%;}
  
  #divSinAssuntosNaoMapeados {position:absolute;left:0%;top:85%;}

  .inputCenter{top: 11%;}
  
  table input.infraText {font-size:1em}
  
  <?
  PaginaSEI::getInstance()->fecharStyle();
  PaginaSEI::getInstance()->montarJavaScript();
  PaginaSEI::getInstance()->abrirJavaScript();
  ?>
  //<script type="text/javascript">
  
  <?=$strAjaxVariaveis?>
  
  var bolAlteracao = false;
  
  function inicializar(){
  
    var linkAutoCompletar = '<?=SessaoSEI::getInstance()->assinarLink('controlador_ajax.php?acao_ajax=tipo_procedimento_auto_completar')?>';
  
  <?=$strAjaxInicializar?>
  
    bolAlteracao = false;
  
    //infraEfeitoTabelas();
  }
  

  
  function gerenciar() {

    document.getElementById('frmMapeamentoOrgaosLista').target = '_self';
    document.getElementById('frmMapeamentoOrgaosLista').action = '<?=$strLinkGerenciar?>';
    document.getElementById('frmMapeamentoOrgaosLista').submit();
  
    infraExibirAviso(false);
  }
  
  
  function OnSubmitForm() {
  
    if (bolAlteracao && !confirm('Existem alterações que não foram salvas.\n\nDeseja continuar?')){
      return false;
    }
  
    return true;
  }
  
  
  //</script>
  <?
  PaginaSEI::getInstance()->fecharJavaScript();
  PaginaSEI::getInstance()->fecharHead();
  PaginaSEI::getInstance()->abrirBody($strTitulo,'onload="inicializar();"');
  ?>
  <form id="frmMapeamentoOrgaosLista" method="post" onsubmit="return OnSubmitForm();" action="<?=SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao'].'&acao_origem='.$_GET['acao'].$strParametros)?>">
    <?
    PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandos);
    PaginaSEI::getInstance()->abrirAreaDados('17em');
    ?>
    <!-- <label id="lblTabelaAssuntosOrigem" class="infraLabelObrigatorio">Órgão Origem:</label>
    <input type="text" id="txtTabelaAssuntosOrigem" name="txtTabelaAssuntosOrigem" readonly="readonly" class="infraText infraReadOnly" value=" <?=PaginaSEI::tratarHTML($objTabelaAssuntosDTOOrigem->getStrNome())?>" tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" />
    <br>
    <label id="lblTabelaAssuntosOrigem" class="infraLabelObrigatorio">Órgão Destino:</label>
    <input type="text" id="txtTabelaAssuntosOrigem" name="txtTabelaAssuntosDestino" readonly="readonly" class="infraText infraReadOnly" value=" <?=PaginaSEI::tratarHTML($objTabelaAssuntosDTOOrigem->getStrNome())?>" tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" /> -->

    <div style="display:grid; width: 40% ">
         <label id="" class="infraLabelObrigatorio">Órgão Origem:</label>
        <input type="text" id="" disabled="disabled" name="txtTabelaAssuntosOrigem" readonly="readonly" class="infraText infraReadOnly inputCenter" value=" <?=PaginaSEI::tratarHTML($objPenOrgaoExternoDTO->getStrOrgaoOrigem())?>" tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" />

        <label class="infraLabelObrigatorio">Órgão Destino:</label>
        <input type="text" id="" disabled="disabled" name="" class="infraText infraReadOnly inputCenter" value=" <?=PaginaSEI::tratarHTML($objPenOrgaoExternoDTO->getStrOrgaoDestino())?>" tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" />

        <label id="" for="txtPalavrasPesquisaMapeamento" class="infraLabelOpcional">Palavras para Pesquisa:</label>
        <input type="text" id="txtPalavrasPesquisaMapeamentoA" name="txtPalavrasPesquisaMapeamentoAssuntos" value="" class="infraText inputCenter" tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" />
    </div>
    
    <div id="divSinAssuntosNaoMapeados" class="infraDivCheckbox">
        <input type="checkbox" id="chkSinAssuntosNaoMapeados" name="chkSinAssuntosNaoMapeados" onchange="this.form.submit()" class="infraCheckbox" tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" />
        <label id="lblSinAssuntosNaoMapeados" for="chkSinAssuntosNaoMapeados" accesskey="" class="infraLabelCheckbox" >Exibir apenas assuntos sem mapeamento definido</label>
    </div>
  
    <input type="hidden" name="hdnFlag" value="<?php echo $idOrgaoExterno; ?>" />
    <?
    PaginaSEI::getInstance()->fecharAreaDados();
    PaginaSEI::getInstance()->montarAreaTabela($strResultado,$numRegistros);
    //PaginaSEI::getInstance()->montarAreaDebug();
    PaginaSEI::getInstance()->montarBarraComandosInferior($arrComandos);
    ?>
  
  </form>
  <?
  PaginaSEI::getInstance()->fecharBody();
  PaginaSEI::getInstance()->fecharHtml();
  ?>