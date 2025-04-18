<?
/**
 *
 */
try {
    require_once DIR_SEI_WEB.'/SEI.php';

    session_start();

    define('PEN_RECURSO_ATUAL', 'pen_parametros_configuracao');
    define('PEN_PAGINA_TITULO', 'Par�metros de Configura��o do M�dulo Tramita GOV.BR');

    $objPagina = PaginaSEI::getInstance();
    $objBanco = BancoSEI::getInstance();
    $objSessao = SessaoSEI::getInstance();

    $o = new PenRelHipoteseLegalEnvioRN();
    $os = new PenRelHipoteseLegalRecebidoRN();

    $objSessao->validarPermissao('pen_parametros_configuracao');

    $objPenParametroDTO = new PenParametroDTO();
    $objPenParametroDTO->retTodos();
    $objPenParametroDTO->setStrNome(array('PEN_TIPO_PROCESSO_EXTERNO'), InfraDTO::$OPER_NOT_IN);
    $objPenParametroDTO->setNumSequencia(null, InfraDTO::$OPER_DIFERENTE);
    $objPenParametroDTO->setOrdNumSequencia(InfraDTO::$TIPO_ORDENACAO_ASC);

    $objPenParametroRN = new PenParametroRN();
    $retParametros = $objPenParametroRN->listar($objPenParametroDTO);

    /* Busca os dados para montar dropdown ( TIPO DE PROCESSO EXTERNO ) */
    $objNivelAcessoPermitidoDTO = new NivelAcessoPermitidoDTO();
    $objNivelAcessoPermitidoDTO->setStrStaNivelAcesso(ProtocoloRN::$NA_SIGILOSO);
    $objNivelAcessoPermitidoDTO->setDistinct(true);
    $objNivelAcessoPermitidoDTO->retNumIdTipoProcedimento();
    $objNivelAcessoPermitidoRN = new NivelAcessoPermitidoRN();
    $arrObjNivelAcessoPermitido=InfraArray::converterArrInfraDTO($objNivelAcessoPermitidoRN->listar($objNivelAcessoPermitidoDTO), "IdTipoProcedimento");

    $objRelTipoProcedimentoDTO = new TipoProcedimentoDTO();
    $objRelTipoProcedimentoDTO->retNumIdTipoProcedimento();
    $objRelTipoProcedimentoDTO->retStrNome();
    $objRelTipoProcedimentoDTO->setOrdStrNome(InfraDTO::$TIPO_ORDENACAO_ASC);

    $objRelTipoProcedimentoRN = new TipoProcedimentoRN();
    $arrTipoProcedimento=InfraArray::converterArrInfraDTO($objRelTipoProcedimentoRN->listarRN0244($objRelTipoProcedimentoDTO), "IdTipoProcedimento");
    $arrayFiltro = array_diff($arrTipoProcedimento, $arrObjNivelAcessoPermitido);

    $objRelTipoProcedimentoDTO->setNumIdTipoProcedimento($arrayFiltro, InfraDTO::$OPER_IN);
    $arrObjTipoProcedimentoDTO = $objRelTipoProcedimentoRN->listarRN0244($objRelTipoProcedimentoDTO);

  if ($arrayFiltro == null) {
      $arrObjTipoProcedimentoDTO = null;
  }

    /* Busca os dados para montar dropdown ( UNIDADE GERADORA DOCUMENTO RECEBIDO ) */
    $objUnidadeDTO = new UnidadeDTO();
    $objUnidadeDTO->retNumIdUnidade();
    $objUnidadeDTO->retStrSigla();
    $objUnidadeDTO->setOrdStrSigla(InfraDTO::$TIPO_ORDENACAO_ASC);
    $objUnidadeRN = new UnidadeRN();
    $arrObjUnidade = $objUnidadeRN->listarRN0127($objUnidadeDTO);

  if ($objPenParametroDTO===null){
      throw new InfraException("M�dulo do Tramita: Registros n�o encontrados.");
  }

  switch ($_GET['acao']) {
    case 'pen_parametros_configuracao_salvar':
      try {

        $objPenParametroRN = new PenParametroRN();

        if (!empty(count($_POST['parametro']))) {
          foreach ($_POST['parametro'] as $nome => $valor) {
            $objPenParametroDTO = new PenParametroDTO();
            $objPenParametroDTO->setStrNome($nome);
            $objPenParametroDTO->retStrNome();

            if($objPenParametroRN->contar($objPenParametroDTO) > 0) {
                $objPenParametroDTO->setStrValor(trim($valor));
                $objPenParametroRN->alterar($objPenParametroDTO);
            }
          }
        }

        PaginaSEI::getInstance()->adicionarMensagem('Par�metros de Configura��o gravados com sucesso.', 5);
      } catch (Exception $e) {
          $objPagina->processarExcecao($e);
      }
        header('Location: ' . $objSessao->assinarLink('controlador.php?acao=' . $_GET['acao_origem'] . '&acao_origem=' . $_GET['acao']));
        die;

    case 'pen_parametros_configuracao':
        $strTitulo = 'Par�metros de Configura��o do M�dulo Tramita GOV.BR';
        break;

    default:
        throw new InfraException("M�dulo do Tramita: A��o '" . $_GET['acao'] . "' n�o reconhecida.");
  }

} catch (Exception $e) {
    $objPagina->processarExcecao($e);
}

//Monta os bot�es do topo
if ($objSessao->verificarPermissao('pen_parametros_configuracao_alterar')) {
    $arrComandos[] = '<button type="submit" id="btnSalvar" value="Salvar" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
}
$arrComandos[] = '<button type="button" id="btnCancelar" value="Cancelar" onclick="location.href=\'' . $objPagina->formatarXHTML($objSessao->assinarLink('controlador.php?acao=pen_parametros_configuracao&acao_origem=' . $_GET['acao'])) . '\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';

$objPagina->montarDocType();
$objPagina->abrirHtml();
$objPagina->abrirHead();
$objPagina->montarMeta();
$objPagina->montarTitle($objPagina->getStrNomeSistema() . ' - ' . $strTitulo);
$objPagina->montarStyle();
$objPagina->abrirStyle();
?>
.input-field {
    width: 45%;
    margin-top: 2px;
    min-width: 180px;
}

.erro_pen{
    width: 15px;
    height: 15px;
    margin-left: 5px;
}

.div_input{
    display:flex;
    align-items:center;
    margin-bottom:10px;
}

.pen_ajuda{
    margin-left:10px;
}

<?
$objPagina->fecharStyle();
$objPagina->montarJavaScript();
$objPagina->abrirJavaScript();
?>

function inicializar(){
    if ('<?php echo htmlspecialchars($_GET['acao'])?>'=='pen_parametros_configuracao_selecionar'){
        infraReceberSelecao();
        document.getElementById('btnFecharSelecao').focus();
    }else{
        document.getElementById('btnCancelar').focus();
    }
    infraEfeitoImagens();
    infraEfeitoTabelas();
}

function OnSubmitForm() {

    var camposValidacao = [
        {'id': 'PEN_ID_REPOSITORIO_ORIGEM', 'mensagem': 'Selecione um Reposit�rio de Estruturas de Origem.'},
        {'id': 'PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO', 'mensagem': 'Selecione uma Unidade Geradora de Processo e Documento Recebido.'},
        {'id': 'PEN_ENVIA_EMAIL_NOTIFICACAO_RECEBIMENTO', 'mensagem': 'Selecione uma op��o para Envio de E-mail de Notifica��o de Recebimento.'},
    ];

    return camposValidacao.every(validarPreenchimentoCampo);
}

function validarPreenchimentoCampo(campoValidacao){
    var valido = infraSelectSelecionado(campoValidacao.id);

    if (!valido) {
        alert(campoValidacao.mensagem);
        document.getElementById(campoValidacao.id).focus();
    }

    return valido;
}

<?
$objPagina->fecharJavaScript();
$objPagina->fecharHead();
$objPagina->abrirBody($strTitulo, 'onload="inicializar();"');
?>

<form id="frmInfraParametroCadastro" method="post" onsubmit="return OnSubmitForm();" action="<?php echo $objSessao->assinarLink('controlador.php?acao='.htmlspecialchars($_GET['acao']).'_salvar&acao_origem='.htmlspecialchars($_GET['acao']))?>">
    <?
    $objPagina->montarBarraComandosSuperior($arrComandos);
    $objPagina->getInstance()->abrirAreaDados('30em');
    foreach ($retParametros as $parametro) {

       //Esse par�metro n�o aparece, por j� existencia de uma tela s� para altera��o do pr�prio.
        if ($parametro->getStrNome() != 'HIPOTESE_LEGAL_PADRAO' && $parametro->getStrNome() != 'PEN_TIPO_PROCESSO_EXTERNO') {
        ?> <label id="lbl<?php echo PaginaSEI::tratarHTML($parametro->getStrNome()); ?>" for="txt<?php echo PaginaSEI::tratarHTML($parametro->getStrNome()); ?>" accesskey="N" class="infraLabelObrigatorio"><?php echo  PaginaSEI::tratarHTML($parametro->getStrDescricao()); ?>:</label> <?
        }

        //Constr�i o campo de valor
        switch ($parametro->getStrNome()) {
          case 'PEN_ID_REPOSITORIO_ORIGEM':
            try {
              $objExpedirProcedimentosRN = new ExpedirProcedimentoRN();
              $repositorios = $objExpedirProcedimentosRN->listarRepositoriosDeEstruturas();
              $idRepositorioSelecionado = (!is_null($parametro->getStrValor())) ? $parametro->getStrValor() : '';
              $textoAjuda="Selecionar o reposit�rio, configurado no Portal do PEN, que seu �rg�o faz parte";
              echo '<div class="div_input">';
              echo '<select id="PEN_ID_REPOSITORIO_ORIGEM" name="parametro[PEN_ID_REPOSITORIO_ORIGEM]" class="infraSelect input-field">';
              echo InfraINT::montarSelectArray('null', '&nbsp;', $idRepositorioSelecionado, $repositorios);
              echo '</select>';
              echo "<a class='pen_ajuda' id='ajuda_repositorio' " . PaginaSEI::montarTitleTooltip($textoAjuda) . "><img src=" . PaginaSEI::getInstance()->getDiretorioImagensGlobal() . "/ajuda.gif class='infraImg'/></a>";
              echo '</div>';
            } catch (Exception $e) {
              // Caso ocorra alguma falha na obten��o de dados dos servi�os do PEN, apresenta estilo de campo padr�o
              echo '<div class="div_input">';
              echo '<input type="text" id="PEN_ID_REPOSITORIO_ORIGEM" name="parametro[PEN_ID_REPOSITORIO_ORIGEM]" class="infraText" value="'.$objPagina->tratarHTML($parametro->getStrValor()).'" onkeypress="return infraMascaraTexto(this,event);" tabindex="'.$objPagina->getProxTabDados().'" maxlength="100" />';
              echo '<img class="erro_pen" src=" ' . ProcessoEletronicoINT::getCaminhoIcone("imagens/sei_erro.png") . '" title="N�o foi poss�vel carregar os Reposit�rios de Estruturas dispon�veis no Tramita.GOV.BR devido � falha de acesso aos Servi�os. O valor apresenta��o no campo � o c�digo do reposit�rio configurado anteriormente">';
              echo '</div>';
            }
              break;

          case 'PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO':
            $textoAjuda="Selecionar a unidade que representa os �rg�os externos";
            echo '<div class="div_input">';
            echo '<select id="PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO" name="parametro[PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO]" class="infraSelect input-field" >';
            echo InfraINT::montarSelectArrInfraDTO('null', '&nbsp;', $parametro->getStrValor(), $arrObjUnidade, 'IdUnidade', 'Sigla');
            echo '<select>';
            echo "<a class='pen_ajuda' id='ajuda_unidade'" . PaginaSEI::montarTitleTooltip($textoAjuda) . "><img src=" . PaginaSEI::getInstance()->getDiretorioImagensGlobal() . "/ajuda.gif class='infraImg'/></a>";
            echo '</div>';
              break;

          case 'PEN_ENVIA_EMAIL_NOTIFICACAO_RECEBIMENTO':
            $textoAjuda="Selecionar caso queira receber notifica��es de recebimento";
            echo '<div class="div_input">';
            echo '<select id="PEN_ENVIA_EMAIL_NOTIFICACAO_RECEBIMENTO" name="parametro[PEN_ENVIA_EMAIL_NOTIFICACAO_RECEBIMENTO]" class="infraSelect input-field" >';
            echo '    <option value="S" ' . ($parametro->getStrValor() == 'S' ? 'selected="selected"' : '') . '>Sim</option>';
            echo '    <option value="N" ' . ($parametro->getStrValor() == 'N' ? 'selected="selected"' : '') . '>N�o</option>';
            echo '<select>';
            echo "<a class='pen_ajuda' id='ajuda_notificacao' " . PaginaSEI::montarTitleTooltip($textoAjuda) . "><img src=" . PaginaSEI::getInstance()->getDiretorioImagensGlobal() . "/ajuda.gif class='infraImg'/></a>";
            echo '</div>';
              break;

          default:
            echo '<div class="div_input">';
            echo '<input type="text" id="PARAMETRO_'.$parametro->getStrNome().'" name="parametro['.$parametro->getStrNome().']" class="infraText input-field" value="'.$objPagina->tratarHTML($parametro->getStrValor()).'" onkeypress="return infraMascaraTexto(this,event);" tabindex="'.$objPagina->getProxTabDados().'" maxlength="100" />';
            echo '</div>';
              break;
        }
        }
        ?>
</form>

<?
$objPagina->getInstance()->fecharAreaDados();
$objPagina->fecharBody();
$objPagina->fecharHtml();
?>
