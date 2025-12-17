<?php
require_once DIR_SEI_WEB . '/SEI.php';

session_start();

//////////////////////////////////////////////////////////////////////////////
//InfraDebug::getInstance()->setBolLigado(true);
//InfraDebug::getInstance()->setBolDebugInfra(true);
//InfraDebug::getInstance()->limpar();
//////////////////////////////////////////////////////////////////////////////

$objSessaoSEI = SessaoSEI::getInstance();
$objPaginaSEI = PaginaSEI::getInstance();
$objDebug = InfraDebug::getInstance();
$objInfraException = new InfraException();

define('TITULO_PAGINA', 'Mapeamento de Envio Parcial');

try {
    $objSessaoSEI->validarLink();
    $objSessaoSEI->validarPermissao($_GET['acao']);

    $objPenRestricaoEnvioComponentesDigitaisRN = new PenRestricaoEnvioComponentesDigitaisRN();

    $id = null;
  if (array_key_exists('Id', $_GET) && !empty($_GET['Id'])) {
      $id = $_GET['Id'];
      $strParametros .= "&Id=" . $id;
  }
    $strLinkValidacao = $objPaginaSEI->formatarXHTML(
        $objSessaoSEI->assinarLink(
            'controlador.php?acao=pen_map_envio_parcial_salvar&acao_='
            . $_GET['acao']
            . $strParametros
        )
    );

    $disabilitarVisualizar = "";
  switch ($_GET['acao']) {
    case 'pen_map_envio_parcial_salvar':
        $acao = !empty($id) ?
        'pen_map_envio_parcial_atualizar' :
        'pen_map_envio_parcial_cadastrar';

      if (empty($_POST['selRepositorioEstruturas']) || empty($_POST['txtRepositorioEstruturas']) || $_POST['txtRepositorioEstruturas'] == "0") {
          $objPaginaSEI->adicionarMensagem('O Repositório de Estruturas Organizacionais não foi selecionado', InfraPagina::$TIPO_MSG_ERRO);
          header('Location: ' . $objSessaoSEI->assinarLink('controlador.php?acao=' . $acao . '&acao_=' . $_GET['acao_']));
          exit(0);
      }

      if (empty($_POST['hdnIdUnidade']) || empty($_POST['txtUnidade']) || $_POST['txtUnidade'] == "0") {
          $objPaginaSEI->adicionarMensagem('O Órgao não foi selecionado.', InfraPagina::$TIPO_MSG_ERRO);
          header('Location: ' . $objSessaoSEI->assinarLink('controlador.php?acao=' . $acao . '&acao_=' . $_GET['acao_']));
          exit(0);
      }

        $numIdUnidadePen = $_POST['hdnIdUnidade'];
        $strUnidadePen = $_POST['txtUnidade'];
        $numIdRepositorio = $_POST['selRepositorioEstruturas'];
        $txtRepositorioEstruturas = $_POST['txtRepositorioEstruturas'];
        $sinMultiplosOrgaos = $_POST['sinMultiplosOrgaos'] ? 'S' : 'N';

        $objDTO = new PenRestricaoEnvioComponentesDigitaisDTO();
        $objDTO->setNumIdEstrutura($numIdRepositorio);
        $objDTO->setNumIdUnidadePen($numIdUnidadePen);
      if (!empty($id)) {
          $objDTO->setDblId([$id], InfraDTO::$OPER_NOT_IN);
      }
        $objDTO->setNumMaxRegistrosRetorno(1);

        $objDTO = $objPenRestricaoEnvioComponentesDigitaisRN->contar($objDTO);
      if ($objDTO > 0) {
          $objPaginaSEI->adicionarMensagem(
              TITULO_PAGINA . ' já existente.',
              InfraPagina::$TIPO_MSG_ERRO
          );
          header(
              'Location: ' . $objSessaoSEI->assinarLink(
                  'controlador.php?acao=pen_map_envio_parcial_cadastrar&acao_=' . $_GET['acao_']
              )
          );
          exit(0);
      }

        $objDTO = new PenRestricaoEnvioComponentesDigitaisDTO();
        $objDTO->setNumIdEstrutura($numIdRepositorio);
        $objDTO->setStrStrEstrutura($txtRepositorioEstruturas);
        $objDTO->setNumIdUnidadePen($numIdUnidadePen);
        $objDTO->setStrStrUnidadePen($strUnidadePen);
        $objDTO->setStrSinMultiplosOrgaos($sinMultiplosOrgaos);

        $messagem = TITULO_PAGINA . " cadastrado com sucesso.";
      if (!empty($_GET['Id'])) {
          $objDTO->setDblId($id);
          $objPenRestricaoEnvioComponentesDigitaisRN->alterar($objDTO);
          $messagem = TITULO_PAGINA . " atualizado com sucesso.";
      } else {
        if ($objPenRestricaoEnvioComponentesDigitaisRN->contar($objDTO) > 0) {
            $objPaginaSEI->adicionarMensagem('Já existe um registro cadastrado para a estrutura selecionada.', InfraPagina::$TIPO_MSG_ERRO);
            header('Location: ' . $objSessaoSEI->assinarLink('controlador.php?acao=' . $acao . '&acao_=' . $_GET['acao_']));
            exit(0);
        }
          $objPenRestricaoEnvioComponentesDigitaisRN->cadastrar($objDTO);
      }
        $objPaginaSEI->adicionarMensagem($messagem, 5);
        header(
            'Location: ' . $objSessaoSEI->assinarLink(
                'controlador.php?acao=pen_map_envio_parcial_listar&acao_=' . $_GET['acao_']
            )
        );
        exit(0);
    case 'pen_map_envio_parcial_visualizar':
    case 'pen_map_envio_parcial_atualizar':
    case 'pen_map_envio_parcial_cadastrar':
      if (array_key_exists('Id', $_GET) && !empty($_GET['Id'])) {
          $nomeTitle = 'Editar';
        if ($_GET['acao'] == 'pen_map_envio_parcial_visualizar') {
            $nomeTitle = 'Visualizar';
        }
          $strTitulo = $nomeTitle . ' Mapeamento de Envio Parcial';
      } else {
          $strTitulo = 'Novo Mapeamento de Envio Parcial';
      }

        //Monta os botões do topo
      if ($_GET['acao'] != 'pen_map_envio_parcial_visualizar'
            && $objSessaoSEI->verificarPermissao('pen_map_envio_parcial_cadastrar')
            && $objSessaoSEI->verificarPermissao('pen_map_envio_parcial_atualizar')
        ) {
          $arrComandos[] = '<button type="submit" id="btnSalvar" value="Salvar" class="infraButton">'
          . '<span class="infraTeclaAtalho">S</span>alvar</button>';
          $arrComandos[] = '<button type="button" id="btnCancelar" value="Cancelar" onclick="location.href=\''
          . $objPaginaSEI->formatarXHTML(
              $objSessaoSEI->assinarLink(
                  'controlador.php?acao=pen_map_envio_parcial_listar&acao_='
                  . $_GET['acao']
              )
          ) . '\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';
      } else {
          $disabilitarVisualizar = " disabled='disabled' ";
          $arrComandos[] = '<button type="button" id="btnCancelar" value="Voltar" onclick="location.href=\''
          . $objPaginaSEI->formatarXHTML(
              $objSessaoSEI->assinarLink(
                  'controlador.php?acao=pen_map_envio_parcial_listar&acao_=' . $_GET['acao']
              )
          ) . '\';" class="infraButton"><span class="infraTeclaAtalho">V</span>oltar</button>';
      }

        //Preparação dos dados para montagem da tela de expedição de processos
        $objExpedirProcedimentosRN = new ExpedirProcedimentoRN();
        $repositorios = $objExpedirProcedimentosRN->listarRepositoriosDeEstruturas();

      if (array_key_exists('Id', $_GET) && !empty($_GET['Id'])) {
          $objPenRestricaoEnvioComponentesDigitaisDTO = new PenRestricaoEnvioComponentesDigitaisDTO();
          $objPenRestricaoEnvioComponentesDigitaisDTO->retDblId();
          $objPenRestricaoEnvioComponentesDigitaisDTO->retNumIdEstrutura();
          $objPenRestricaoEnvioComponentesDigitaisDTO->retStrStrEstrutura();
          $objPenRestricaoEnvioComponentesDigitaisDTO->retNumIdUnidadePen();
          $objPenRestricaoEnvioComponentesDigitaisDTO->retStrStrUnidadePen();
          $objPenRestricaoEnvioComponentesDigitaisDTO->retStrSinMultiplosOrgaos();
          $objPenRestricaoEnvioComponentesDigitaisDTO->setDblId($_GET['Id']);

          $objPenRestricaoEnvioComponentesDigitaisDTO =
          $objPenRestricaoEnvioComponentesDigitaisRN->consultar($objPenRestricaoEnvioComponentesDigitaisDTO);

        if (!is_null($objPenRestricaoEnvioComponentesDigitaisDTO)) {
            $numIdRepositorio = $objPenRestricaoEnvioComponentesDigitaisDTO->getNumIdEstrutura();
            $hdnIdUnidade = $objPenRestricaoEnvioComponentesDigitaisDTO->getNumIdUnidadePen();
            $strNomeUnidade = $objPenRestricaoEnvioComponentesDigitaisDTO->getStrStrUnidadePen();
            $sinMultiplosOrgaos = $objPenRestricaoEnvioComponentesDigitaisDTO->getStrSinMultiplosOrgaos();
        }
      }

        $idRepositorioSelecionado = $numIdRepositorio ?? '';
        $strItensSelRepositorioEstruturas = InfraINT::montarSelectArray(
            '',
            'Selecione',
            $idRepositorioSelecionado,
            $repositorios
        );

        $strLinkAjaxProcedimentoApensado = $objSessaoSEI->assinarLink(
            'controlador_ajax.php?acao_ajax=pen_apensados_auto_completar_expedir_procedimento'
        );
        $strLinkUnidadesAdministrativasSelecao = $objSessaoSEI->assinarLink(
            'controlador.php?acao=pen_unidades_administrativas_externas_selecionar_expedir_procedimento'
            . '&tipo_pesquisa=1&id_object=objLupaUnidadesAdministrativas&idRepositorioEstrutura=1'
        );

        try {
          $podeEnvioMultiplosOrgaos = ConfiguracaoModPEN::getInstance()->getValor("PEN", "EnvioMultiplosOrgaos", null);
          // Garantir que seja um array
          if (!is_null($podeEnvioMultiplosOrgaos) && !is_array($podeEnvioMultiplosOrgaos)) {
            $podeEnvioMultiplosOrgaos = explode(',', $podeEnvioMultiplosOrgaos);
          }
        } catch (Throwable $e) {
          $podeEnvioMultiplosOrgaos = null;
        }
        break;
    default:
        throw new InfraException("Módulo do Tramita: Ação '" . $_GET['acao'] . "' não reconhecida.");
  }

    $strLinkAjaxUnidade = $objSessaoSEI->assinarLink('controlador_ajax.php?acao_ajax=pen_unidade_auto_completar_expedir_procedimento&acao=' . $_GET['acao']);
} catch (Exception $e) {
    $objPaginaSEI->adicionarMensagem(
        'Falha no cadastro do relacionamento. Consulte o log do SEI para mais informações.',
        InfraPagina::$TIPO_MSG_ERRO
    );
    throw new InfraException("Módulo do Tramita: Erro processando requisição de envio externo de processo", $e);
}

$objPaginaSEI->montarDocType();
$objPaginaSEI->abrirHtml();
$objPaginaSEI->abrirHead();
$objPaginaSEI->montarMeta();
$objPaginaSEI->montarTitle(':: ' . $objPaginaSEI->getStrNomeSistema() . ' - ' . $strTitulo . ' ::');
$objPaginaSEI->montarStyle();

?>
<style type="text/css">
  div.infraAreaDados {
    margin-bottom: 10px;
  }

  #lblProtocoloExibir {
    position: absolute;
    left: 0%;
    top: 0%;
  }

  #txtProtocoloExibir {
    position: absolute;
    left: 0%;
    top: 38%;
    width: 50%;
  }

  #lblRepositorioEstruturas {
    position: absolute;
    left: 0%;
    top: 0%;
    width: 50%;
  }

  #selRepositorioEstruturas {
    position: absolute;
    left: 0%;
    top: 38%;
    width: 51%;
  }

  #lblUnidades {
    position: absolute;
    left: 0%;
    top: 0%;
  }

  #txtUnidade {
    left: 0%;
    top: 38%;
    width: 50%;
    border: .1em solid #666;
  }

  #imgLupaUnidades {
    position: absolute;
    left: 52%;
    top: 48%;
  }

  .alinhamentoBotaoImput {
    position: absolute;
    left: 0%;
    top: 48%;
    width: 85%;
  }

  #imgPesquisaAvancada {
    vertical-align: middle;
    margin-left: 10px;
    width: 20px;
    height: 20px;
  }

  #lblProcedimentosApensados {
    position: absolute;
    left: 0%;
    top: 0%;
  }

  #txtProcedimentoApensado {
    position: absolute;
    left: 0%;
    top: 25%;
    width: 50%;
    border: .1em solid #666;
  }

  #selProcedimentosApensados {
    position: absolute;
    left: 0%;
    top: 43%;
    width: 86%;
  }

  #imgLupaProcedimentosApensados {
    position: absolute;
    left: 87%;
    top: 43%;
  }

  #imgExcluirProcedimentosApensados {
    position: absolute;
    left: 87%;
    top: 60%;
  }

  #lblMotivosUrgencia {
    position: absolute;
    left: 0%;
    top: 0%;
    width: 50%;
  }

  #selMotivosUrgencia {
    position: absolute;
    left: 0%;
    top: 38%;
    width: 51%;
  }
</style>
<?php
$objPaginaSEI->montarJavaScript();
?>
<script type="text/javascript">
  var idRepositorioEstrutura = null;
  var objAutoCompletarEstrutura = null;

  var objLupaUnidades = null;
  var objLupaUnidadesAdministrativas = null;

  //Caso não tenha unidade encontrada
  $(document).ready(function() {
    $(document).on('click', '#txtUnidade', function() {
      if ($(this).val() == "Órgão  não Encontrado.") {
        $(this).val('');
      }
    });
  });

  function inicializar() {
    objLupaUnidadesAdministrativas = new infraLupaSelect(
      'selRepositorioEstruturas',
      'hdnUnidadesAdministrativas',
      '<?php echo $strLinkUnidadesAdministrativasSelecao ?>'
    );

    objAutoCompletarEstrutura = new infraAjaxAutoCompletar(
      'hdnIdUnidade', 'txtUnidade', '<?php echo $strLinkAjaxUnidade ?>', "Nenhuma unidade foi encontrada"
    );
    objAutoCompletarEstrutura.bolExecucaoAutomatica = false;
    objAutoCompletarEstrutura.mostrarAviso = true;
    objAutoCompletarEstrutura.limparCampo = false;
    objAutoCompletarEstrutura.tempoAviso = 10000000;

    objAutoCompletarEstrutura.prepararExecucao = function() {
      var selRepositorioEstruturas = document.getElementById('selRepositorioEstruturas');
      var parametros = 'palavras_pesquisa=' + document.getElementById('txtUnidade').value;
      parametros += '&id_repositorio=' +
        selRepositorioEstruturas.options[selRepositorioEstruturas.selectedIndex].value
      return parametros;
    };

    objAutoCompletarEstrutura.processarResultado = function(id, descricao, complemento) {
      window.infraAvisoCancelar();
      $('#divSinMultiplosOrgaos').css('display', 'none');
      if (id!='' && '<?php echo !is_null($podeEnvioMultiplosOrgaos) && is_array($podeEnvioMultiplosOrgaos) ? '1' : '0'; ?>'=='1') {
          $arrIdsMultiplosOrgaos = ('<?php echo is_array($podeEnvioMultiplosOrgaos) ? implode(',', $podeEnvioMultiplosOrgaos) : $podeEnvioMultiplosOrgaos; ?>').split(',');
          if ($arrIdsMultiplosOrgaos.indexOf(id) !== -1) {
            $('#divSinMultiplosOrgaos').css('display', 'block');
          }
      }
    };

    $('#btnIdUnidade').click(function() {
      objAutoCompletarEstrutura.executar();
      objAutoCompletarEstrutura.procurar();
    });


    //Botão de pesquisa avançada
    $('#imgPesquisaAvancada').click(function() {
      var idRepositorioEstrutura = $('#selRepositorioEstruturas :selected').val();
      if ((idRepositorioEstrutura != '') && (idRepositorioEstrutura != 'null')) {
        $("#hdnUnidadesAdministrativas").val(idRepositorioEstrutura);
        objLupaUnidadesAdministrativas.selecionar(700, 500);
      } else {
        alert('Selecione um repositório de Estruturas Organizacionais');
      }
    });
    document.getElementById('selRepositorioEstruturas').focus();
    <?php if ($_GET['acao'] == 'pen_map_envio_parcial_cadastrar') { ?>
      iniciarRepositorio();
    <?php } ?>
  }

  function iniciarRepositorio() {
    var txtUnidade = $('#txtUnidade');
    var selRepositorioEstruturas = $('#selRepositorioEstruturas');

    <?php if ($_GET['acao'] != 'pen_map_envio_parcial_visualizar') { ?>
      var txtUnidadeEnabled = selRepositorioEstruturas.val() > 0;
      txtUnidade.prop('disabled', !txtUnidadeEnabled);
        <?php if (empty($strNomeUnidade)) { ?>
        $('#hdnIdUnidade').val('');
        txtUnidade.val('');
        <?php } ?>

      if (!txtUnidadeEnabled) {
        txtUnidade.addClass('infraReadOnly');
      } else {
        txtUnidade.removeClass('infraReadOnly');
        $('#txtRepositorioEstruturas').val($("#selRepositorioEstruturas option:selected").text());
      }
    <?php } ?>
  }

  function selecionarRepositorio() {
    var txtUnidade = $('#txtUnidade');
    var selRepositorioEstruturas = $('#selRepositorioEstruturas');

    <?php if ($_GET['acao'] != 'pen_map_envio_parcial_visualizar') { ?>
      var txtUnidadeEnabled = selRepositorioEstruturas.val() > 0;
      txtUnidade.prop('disabled', !txtUnidadeEnabled);
      $('#hdnIdUnidade').val('');
      txtUnidade.val('');

      if (!txtUnidadeEnabled) {
        txtUnidade.addClass('infraReadOnly');
      } else {
        txtUnidade.removeClass('infraReadOnly');
        $('#txtRepositorioEstruturas').val($("#selRepositorioEstruturas option:selected").text());
      }
    <?php } ?>
  }

  function criarIFrameBarraProgresso() {

    nomeIFrameEnvioProcesso = 'ifrEnvioProcesso';
    var iframe = document.getElementById(nomeIFrameEnvioProcesso);
    if (iframe != null) {
      iframe.parentElement.removeChild(iframe);
    }

    var iframe = document.createElement('iframe');
    iframe.id = nomeIFrameEnvioProcesso;
    iframe.name = nomeIFrameEnvioProcesso;
    iframe.setAttribute('frameBorder', '0');
    iframe.setAttribute('scrolling', 'yes');

    return iframe;
  }

  function exibirBarraProgresso(elemBarraProgresso) {
    // Exibe camada de fundo da barra de progresso
    var divFundo = document.createElement('div');
    divFundo.id = 'divFundoBarraProgresso';
    divFundo.className = 'infraFundoTransparente';
    divFundo.style.visibility = 'visible';

    var divAviso = document.createElement('div');
    divAviso.id = 'divBarraProgresso';
    divAviso.appendChild(elemBarraProgresso);
    divFundo.appendChild(divAviso);

    document.body.appendChild(divFundo);

    redimencionarBarraProgresso();
    infraAdicionarEvento(window, 'resize', redimencionarBarraProgresso);
  }

  function redimencionarBarraProgresso() {
    var divFundo = document.getElementById('divFundoBarraProgresso');
    if (divFundo != null) {
      divFundo.style.width = infraClientWidth() + 'px';
      divFundo.style.height = infraClientHeight() + 'px';
    }
  }
</script>
<?php
$objPaginaSEI->fecharHead();
$objPaginaSEI->abrirBody($strTitulo, 'onload="infraEfeitoTabelas(); inicializar();"');
?>
<form id="frmGravarOrgaoExterno" name="frmGravarOrgaoExterno" method="post" action="<?php echo $strLinkValidacao ?>">
  <?php $objPaginaSEI->montarBarraComandosSuperior($arrComandos); ?>

  <div id="divRepositorioEstruturas" class="infraAreaDados" style="height: 4.5em;">
    <label id="lblRepositorioEstruturas" for="selRepositorioEstruturas" accesskey="" class="infraLabelObrigatorio">Repositório de Estruturas Organizacionais:</label>
    <select id="selRepositorioEstruturas" name="selRepositorioEstruturas" class="infraSelect" onchange="selecionarRepositorio();" <?php echo $disabilitarVisualizar ?> tabindex="<?php echo $objPaginaSEI->getProxTabDados() ?>">
      <?php echo $strItensSelRepositorioEstruturas ?>
    </select>

    <input type="hidden" id="txtRepositorioEstruturas" name="txtRepositorioEstruturas" <?php echo $disabilitarVisualizar ?> class="infraText" value="<?php echo $txtRepositorioEstruturas; ?>" />
  </div>

  <div id="divUnidadesUnidades" class="infraAreaDados" style="height: 4.5em;">
    <label id="lblUnidades" for="selUnidades" class="infraLabelObrigatorio">Órgão :</label>
    <div class="alinhamentoBotaoImput">
      <input type="text" id="txtUnidade" name="txtUnidade" class="infraText infraReadOnly" <?php echo $disabilitarVisualizar ?> placeholder="Digite o nome/sigla da unidade e pressione ENTER para iniciar a pesquisa rápida" value="<?php echo PaginaSEI::tratarHTML($strNomeUnidade); ?>" tabindex="<?php echo $objPaginaSEI->getProxTabDados() ?>" />
      <?php if ($_GET['acao'] != 'pen_map_envio_parcial_visualizar') { ?>
        <button id="btnIdUnidade" type="button" class="infraButton">Consultar</button>
        <!-- <img id="imgPesquisaAvancada" src="imagens/organograma.gif" alt="Consultar organograma" title="Consultar organograma" class="infraImg" /> -->
      <?php } ?>
    </div>

    <input type="hidden" id="hdnIdUnidade" name="hdnIdUnidade" class="infraText" value="<?php echo $hdnIdUnidade; ?>" />
  </div>

  <?php
    $displayNone = (is_array($podeEnvioMultiplosOrgaos) && in_array($hdnIdUnidade, $podeEnvioMultiplosOrgaos)) ? '' : 'display: none;';
  ?>
  <div id="divSinMultiplosOrgaos" class="infraDivCheckbox" style="padding-top: 20px; <?php echo $displayNone; ?>">
    <input type="checkbox" id="sinMultiplosOrgaos" name="sinMultiplosOrgaos" class="infraCheckbox" tabindex="<?php echo PaginaSEI::getInstance()->getProxTabDados() ?>" <?php echo $sinMultiplosOrgaos === 'S' ? 'checked' : '' ?> />
    <label id="lblSinMultiplosOrgaos" for="sinMultiplosOrgaos" class="infraLabelCheckbox">
      Manter o processo aberto na unidade selecionada?
      <?php $mensagemAjuda = 'O processo permanecerá aberto para que possa ser enviada para múltiplos órgãos'; ?>
      <a class='pen_ajuda' id='ajuda_processo_aberto' <?php echo PaginaSEI::montarTitleTooltip($mensagemAjuda); ?>><img src="<?php echo PaginaSEI::getInstance()->getDiretorioImagensGlobal() ?>/ajuda.gif" class='infraImg'/></a>
    </label>
  </div>

  <input type="hidden" id="hdnErrosValidacao" name="hdnErrosValidacao" value="<?php echo $bolErrosValidacao ?>" />
  <input type="hidden" id="hdnUnidadesAdministrativas" name="hdnUnidadesAdministrativas" value="" />

</form>
<?php
$objPaginaSEI->montarAreaDebug();
$objPaginaSEI->fecharBody();
$objPaginaSEI->fecharHtml();
?>