<?php

require_once DIR_SEI_WEB.'/SEI.php';

class ProcessoEletronicoINT extends InfraINT {

    //Situação de cada uma das etapas da envio externo de processos
    const NEE_EXPEDICAO_ETAPA_PROCEDIMENTO = 1;
    const TEE_EXPEDICAO_ETAPA_VALIDACAO = 'Validando informações do processo...';
    const TEE_EXPEDICAO_ETAPA_PROCEDIMENTO = 'Enviando dados do processo %s';
    const TEE_EXPEDICAO_ETAPA_DOCUMENTO = 'Enviando documento %s';
    const TEE_EXPEDICAO_ETAPA_CONCLUSAO = 'Trâmite externo do processo finalizado com sucesso!';

    /**
     * Concate as siglas das hierarquias no nome da unidade
     *
     * @param array(EstruturaDTO) $estruturas
     * @return array
     */
  public static function gerarHierarquiaEstruturas($estruturas = array()){

    if(empty($estruturas)) {
        return $estruturas;
    }

    foreach($estruturas as &$estrutura) {
      if($estrutura->isSetArrHierarquia()) {
          $nome  = $estrutura->getStrNome();
          $nome .= ' - ';

          $array = array($estrutura->getStrSigla());
        foreach($estrutura->getArrHierarquia() as $sigla) {
          if(trim($sigla) !== '' && !in_array($sigla, array('PR', 'PE', 'UNIAO'))) {
                $array[] = $sigla;
          }
        }

          $nome .= implode(' / ', $array);
          $estrutura->setStrNome($nome);
      }
    }

      return $estruturas;
  }

  public static function autoCompletarEstruturas($idRepositorioEstrutura, $strPalavrasPesquisa, $bolPermiteEnvio = false) {
       
       
      $objConecaoWebServerRN = new ProcessoEletronicoRN();
      $arrObjEstruturas = $objConecaoWebServerRN->listarEstruturas(
          $idRepositorioEstrutura,
          $strPalavrasPesquisa,
          null, null, null, null, null, true, $bolPermiteEnvio
      );

      return static::gerarHierarquiaEstruturas($arrObjEstruturas);
  }

  public static function autoCompletarProcessosApensados($dblIdProcedimentoAtual, $numIdUnidadeAtual, $strPalavrasPesquisa) {
      $objExpedirProcedimentoRN = new ExpedirProcedimentoRN();
      return $objExpedirProcedimentoRN->listarProcessosApensados($dblIdProcedimentoAtual, $numIdUnidadeAtual, $strPalavrasPesquisa);
  }


  public static function formatarHierarquia($ObjEstrutura)
    {
      $nome = "";

    if(isset($ObjEstrutura->hierarquia)) {

        $arrObjNivel = $ObjEstrutura->hierarquia->nivel;

        $siglasUnidades = array();
        $siglasUnidades[] = $ObjEstrutura->sigla;

      foreach($arrObjNivel as $key => $objNivel){
        $siglasUnidades[] = $objNivel->sigla  ;
      }

      for($i = 1; $i <= 3; $i++){
        if(isset($siglasUnidades[count($siglasUnidades) - 1])){
          unset($siglasUnidades[count($siglasUnidades) - 1]);
        }
      }

      foreach($siglasUnidades as $key => $nomeUnidade){
        if($key == (count($siglasUnidades) - 1)){
            $nome .= $nomeUnidade." ";
        }else{
            $nome .= $nomeUnidade." / ";
        }
      }

        $objNivel=current($arrObjNivel);

    }
      $dados=["nome"=>$nome,"objNivel"=>$objNivel];

      return $dados;

  }


  public static function getCaminhoIcone($imagem, $relPath = null) {
      $arrConfig = ConfiguracaoSEI::getInstance()->getValor('SEI', 'Modulos');
      $strModulo = $arrConfig['PENIntegracao'];

    if (InfraUtil::compararVersoes(SEI_VERSAO, ">=", "4.0.0")){

      switch ($imagem) {
        case 'imagens/consultar.gif':
            return '/infra_css/svg/consultar.svg';
            break;
        case 'imagens/alterar.gif':
            return '/infra_css/svg/alterar.svg';
            break;
        case 'imagens/excluir.gif':
            return '/infra_css/svg/excluir.svg';
            break;
        case '/pen_expedir_procedimento.gif':
            // return '/infra_css/svg/upload.svg';
            // return 'svg/arquivo_mapeamento_assunto.svg';
            return 'modulos/' . $strModulo . '/imagens/pen_enviar.png';
            break;
        case '/pen_consultar_recibos.png':
            // return '/infra_css/svg/pesquisar.svg';
            return 'modulos/' . $strModulo . '/imagens/processo_pesquisar_pen.png';
            break;
        case '/pen_cancelar_tramite.gif':
            // return '/infra_css/svg/remover.svg';
            return 'modulos/' . $strModulo . '/imagens/pen_cancelar_envio.png';
            break;
        case '/infra_js/arvore/plus.gif':
            return '/infra_css/svg/mais.svg';
            break;
        case '/infra_js/arvore/minus.gif':
            return '/infra_css/svg/menos.svg';
            break;
        case 'imagens/anexos.gif':
            return '/infra_css/imagens/anexos.gif';
            break;
        case 'imagens/sei_erro.png':
          return 'modulos/' . $strModulo . '/imagens/sei_erro.png';
          break;
        default:
          if($relPath==null){
                return $imagem;
          }
            return $relPath . $imagem;
            break;
      }
    }

    if($relPath==null){
        return $imagem;
    }

      return $relPath . $imagem;
  }

  public static function getCssCompatibilidadeSEI4($arquivo)
    {
    if (InfraUtil::compararVersoes(SEI_VERSAO, ">=", "4.0.0") && InfraUtil::compararVersoes(SEI_VERSAO, "<=", "4.0.1")) {

      switch ($arquivo) {
        case 'pen_procedimento_expedir.css':
            return 'pen_procedimento_expedir_sei4.css';
            break;

        default:
            return $arquivo;
            break;
      }
    }elseif (InfraUtil::compararVersoes(SEI_VERSAO, ">", "4.0.1")) {

      switch ($arquivo) {
        case 'pen_procedimento_expedir.css':
            return 'pen_procedimento_expedir_sei402.css';
              break;

        default:
            return $arquivo;
              break;
      }
    }

      return $arquivo;
  }

  public static function montarRestricaoOrgaoUnidade($numIdTipoProcedimento, $numIdSerie, &$strCss, &$strHtml, &$strJsGlobal, &$strJsInicializar)
	{
		if ($numIdTipoProcedimento != null) {

			$objTipoProcedRestricaoDTO = new TipoProcedRestricaoDTO();
			$objTipoProcedRestricaoDTO->setDistinct(true);
			$objTipoProcedRestricaoDTO->retNumIdOrgao();
			$objTipoProcedRestricaoDTO->retStrSiglaOrgao();
			$objTipoProcedRestricaoDTO->setNumIdTipoProcedimento($numIdTipoProcedimento);
			$objTipoProcedRestricaoDTO->setOrdStrSiglaOrgao(InfraDTO::$TIPO_ORDENACAO_ASC);

			$objTipoProcedRestricaoRN = new TipoProcedRestricaoRN();
			$arrObjTipoProcedRestricaoDTO = $objTipoProcedRestricaoRN->listar($objTipoProcedRestricaoDTO);

			$strItensSelOrgaosRestricao = parent::montarSelectArrInfraDTO(null, null, null, $arrObjTipoProcedRestricaoDTO, 'IdOrgao', 'SiglaOrgao');

			$objTipoProcedRestricaoDTO = new TipoProcedRestricaoDTO();
			$objTipoProcedRestricaoDTO->retNumIdOrgao();
			$objTipoProcedRestricaoDTO->retNumIdUnidade();
			$objTipoProcedRestricaoDTO->retStrSiglaUnidade();
			$objTipoProcedRestricaoDTO->retStrDescricaoUnidade();
			$objTipoProcedRestricaoDTO->setNumIdTipoProcedimento($numIdTipoProcedimento);
			$objTipoProcedRestricaoDTO->setNumTipoFkUnidade(InfraDTO::$TIPO_FK_OBRIGATORIA);
			$objTipoProcedRestricaoDTO->setOrdStrSiglaUnidade(InfraDTO::$TIPO_ORDENACAO_ASC);

			$arrObjRestricaoDTO = InfraArray::indexarArrInfraDTO($objTipoProcedRestricaoRN->listar($objTipoProcedRestricaoDTO), 'IdOrgao', true);
		} else if ($numIdSerie != null) {

			$objSerieRestricaoDTO = new SerieRestricaoDTO();
			$objSerieRestricaoDTO->setDistinct(true);
			$objSerieRestricaoDTO->retNumIdOrgao();
			$objSerieRestricaoDTO->retStrSiglaOrgao();
			$objSerieRestricaoDTO->setNumIdSerie($numIdSerie);
			$objSerieRestricaoDTO->setOrdStrSiglaOrgao(InfraDTO::$TIPO_ORDENACAO_ASC);

			$objSerieRestricaoRN = new SerieRestricaoRN();
			$arrObjSerieRestricaoDTO = $objSerieRestricaoRN->listar($objSerieRestricaoDTO);

			$strItensSelOrgaosRestricao = parent::montarSelectArrInfraDTO(null, null, null, $arrObjSerieRestricaoDTO, 'IdOrgao', 'SiglaOrgao');

			$objSerieRestricaoDTO = new SerieRestricaoDTO();
			$objSerieRestricaoDTO->retNumIdOrgao();
			$objSerieRestricaoDTO->retNumIdUnidade();
			$objSerieRestricaoDTO->retStrSiglaUnidade();
			$objSerieRestricaoDTO->retStrDescricaoUnidade();
			$objSerieRestricaoDTO->setNumIdSerie($numIdSerie);
			$objSerieRestricaoDTO->setNumTipoFkUnidade(InfraDTO::$TIPO_FK_OBRIGATORIA);
			$objSerieRestricaoDTO->setOrdStrSiglaUnidade(InfraDTO::$TIPO_ORDENACAO_ASC);

			$arrObjRestricaoDTO = InfraArray::indexarArrInfraDTO($objSerieRestricaoRN->listar($objSerieRestricaoDTO), 'IdOrgao', true);
		}

		$objOrgaoDTO = new OrgaoDTO();
		$objOrgaoDTO->setBolExclusaoLogica(false);
		$objOrgaoDTO->retNumIdOrgao();

		$objOrgaoRN = new OrgaoRN();
		$arrObjOrgaoDTO = $objOrgaoRN->listarRN1353($objOrgaoDTO);
		$strHtmlOrgaoUnidades = '';
		foreach ($arrObjOrgaoDTO as $objOrgaoDTO) {

			$numIdOrgao = $objOrgaoDTO->getNumIdOrgao();

			$strValor = '';
			if (isset($arrObjRestricaoDTO[$numIdOrgao])) {
				$arr = array();
				foreach ($arrObjRestricaoDTO[$numIdOrgao] as $objResticaoDTO) {
					$arr[] = array($objResticaoDTO->getNumIdUnidade(), UnidadeINT::formatarSiglaDescricao($objResticaoDTO->getStrSiglaUnidade(), $objResticaoDTO->getStrDescricaoUnidade()));
				}
				$strValor = PaginaSEI::getInstance()->gerarItensLupa($arr);
			}

			$strHtmlOrgaoUnidades .= '<input type="hidden" id="hdnOrgao' . $numIdOrgao . '" name="hdnOrgao' . $numIdOrgao . '" value="' . $strValor . '" />' . "\n";
			$strHtmlOrgaoUnidades .= '<input type="hidden" id="lnkOrgao' . $numIdOrgao . '" name="lnkOrgao' . $numIdOrgao . '" value="' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=unidade_selecionar_orgao&tipo_selecao=2&id_object=objLupaUnidades&id_orgao=' . $numIdOrgao) . '" />' . "\n";
		}

		$strCss = ''
			. ' #lblOrgaos {position:absolute;left:0%;top:0%;width:20%;}'
			. ' #txtOrgao {position:absolute;left:0%;top:13%;width:19.5%;}'
			. ' #selOrgaos {position:absolute;left:0%;top:29%;width:20%;}'
			. ' #divOpcoesOrgaos {position:absolute;left:21%;top:29%;}'
			. ' '
			. ' #lblUnidades {position:absolute;left:25%;top:0%;}'
			. ' #txtUnidade {position:absolute;left:25%;top:13%;width:54.5%;}'
			. ' #selUnidades {position:absolute;left:25%;top:29%;width:55%;}'
			. ' #divOpcoesUnidades {position:absolute;left:81%;top:29%;}';

		$strJsGlobal = ''
			. ' var objLupaOrgaos = null;'
			. ' var objAutoCompletarOrgao = null;'
			. ' var objLupaUnidades = null;'
			. ' var objAutoCompletarUnidade = null;'
			. ' '
			. ' function trocarOrgaoRestricao(){'
			. ' document.getElementById(\'hdnUnidades\').value = document.getElementById(\'hdnOrgao\' + document.getElementById(\'selOrgaos\').value).value;'
			. ' objLupaUnidades.montar();'
			. ' }';

		$strJsInicializar = ''
			. ' objLupaOrgaos	= new infraLupaSelect(\'selOrgaos\',\'hdnOrgaos\',\'' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=orgao_selecionar&tipo_selecao=2&id_object=objLupaOrgaos') . '\');'
			. ' objLupaOrgaos.processarRemocao = function(itens){'
			. ' 	objLupaUnidades.limpar();'
			. ' 	for(var i=0;i < itens.length;i++){'
			. ' 	document.getElementById(\'hdnOrgao\' + itens[i].value).value = \'\';'
			. ' 	}'
			. ' 	return true;'
			. ' }'
			. ' '
			. ' objLupaOrgaos.finalizarSelecao = function(){'
			. ' 	objLupaUnidades.limpar();'
			. ' }'
			. ' '
			. ' objAutoCompletarOrgao = new infraAjaxAutoCompletar(\'hdnIdOrgao\',\'txtOrgao\',\'' . SessaoSEI::getInstance()->assinarLink('controlador_ajax.php?acao_ajax=orgao_auto_completar') . '\');'
			. ' objAutoCompletarOrgao.limparCampo = true;'
			. ' objAutoCompletarOrgao.prepararExecucao = function(){'
			. ' 	return \'palavras_pesquisa=\'+document.getElementById(\'txtOrgao\').value;'
			. ' };'
			. ' '
			. ' objAutoCompletarOrgao.processarResultado = function(id,descricao,complemento){'
			. ' 	if (id!=\'\'){'
			. ' 	objLupaOrgaos.adicionar(id,descricao,document.getElementById(\'txtOrgao\'));'
			. ' 	objLupaUnidades.limpar();'
			. ' 	}'
			. ' };'
			. ' '
			. ' objLupaUnidades = new infraLupaSelect(\'selUnidades\',\'hdnUnidades\',\'\');'
			. ' objLupaUnidades.validarSelecionar = function(){'
			. ' 	if (document.getElementById(\'selOrgaos\').selectedIndex==-1){'
			. ' 	alert(\'Nenhum Órgão selecionado.\');'
			. ' 	return false;'
			. ' 	}'
			. ' 	objLupaUnidades.url = document.getElementById(\'lnkOrgao\' + document.getElementById(\'selOrgaos\').value).value;'
			. ' 	return true;'
			. ' }'
			. ' '
			. ' objLupaUnidades.finalizarRemocao = function(){'
			. ' 	document.getElementById(\'hdnOrgao\' + document.getElementById(\'selOrgaos\').value).value = document.getElementById(\'hdnUnidades\').value;'
			. ' 	return true;'
			. ' }'
			. ' '
			. ' objLupaUnidades.finalizarSelecao = function(){'
			. ' 	document.getElementById(\'hdnOrgao\' + document.getElementById(\'selOrgaos\').value).value = document.getElementById(\'hdnUnidades\').value;'
			. ' }'
			. ' '
			. ' objAutoCompletarUnidade = new infraAjaxAutoCompletar(\'hdnIdUnidade\',\'txtUnidade\',\'' . SessaoSEI::getInstance()->assinarLink('controlador_ajax.php?acao_ajax=unidade_auto_completar_todas') . '\');'
			. ' objAutoCompletarUnidade.limparCampo = true;'
			. ' objAutoCompletarUnidade.prepararExecucao = function(){'
			. ' 	if (document.getElementById(\'selOrgaos\').selectedIndex==-1){'
			. ' 	alert(\'Nenhum Órgão selecionado.\');'
			. ' 	return false;'
			. ' 	}'
			. ' 	return \'palavras_pesquisa=\'+document.getElementById(\'txtUnidade\').value+\'&id_orgao=\'+document.getElementById(\'selOrgaos\').value;'
			. ' };'
			. ' '
			. ' objAutoCompletarUnidade.processarResultado = function(id,descricao,complemento){'
			. ' 	if (id!=\'\'){'
			. ' 	objLupaUnidades.adicionar(id,descricao,document.getElementById(\'txtUnidade\'));'
			. ' 	document.getElementById(\'hdnOrgao\' + document.getElementById(\'selOrgaos\').value).value = document.getElementById(\'hdnUnidades\').value;'
			. ' 	}'
			. ' };'
			. ' '
			. ' if (document.getElementById(\'selOrgaos\').options.length){'
			. ' 	document.getElementById(\'selOrgaos\').disabled = false;'
			. ' 	document.getElementById(\'selOrgaos\').options[0].selected = true;'
			. ' 	trocarOrgaoRestricao();'
			. ' }';

		$strHtml = ''
			. ' <div id="divRestricao" class="infraAreaDados" style="height:16em;">'
			. ' <label id="lblOrgaos" for="selOrgaos" class="infraLabelOpcional">Restringir aos Órgãos:</label>'
			. ' <input type="text" id="txtOrgao" name="txtOrgao" class="infraText" />'
			. ' <input type="hidden" id="hdnIdOrgao" name="hdnIdOrgao" class="infraText" value="" />'
			. ' <select id="selOrgaos" name="selOrgaos" size="6" multiple="multiple" class="infraSelect" onchange="trocarOrgaoRestricao()" >'
			. ' ' . $strItensSelOrgaosRestricao . ''
			. ' </select>'
			. ' <div id="divOpcoesOrgaos">'
			. ' <img id="imgLupaOrgaos" onclick="objLupaOrgaos.selecionar(700,500);" src="' . PaginaSEI::getInstance()->getIconePesquisar() . '" alt="Selecionar Órgãos" title="Selecionar Órgãos" class="infraImgNormal"  />'
			. ' <br />'
			. ' <img id="imgExcluirOrgaos" onclick="objLupaOrgaos.remover();" src="' . PaginaSEI::getInstance()->getIconeRemover() . '" alt="Remover Órgãos Selecionados" title="Remover Órgãos Selecionados" class="infraImgNormal"  />'
			. ' </div>'
			. ' <input type="hidden" id="hdnOrgaos" name="hdnOrgaos" value="' . $_POST['hdnOrgaos'] . '" />'
			. ' <label id="lblUnidades" for="selUnidades" class="infraLabelOpcional">Restringir às Unidades:</label>'
			. ' <input type="text" id="txtUnidade" name="txtUnidade" class="infraText" />'
			. ' <input type="hidden" id="hdnIdUnidade" name="hdnIdUnidade" class="infraText" value="" />'
			. ' <select id="selUnidades" name="selUnidades" size="6" multiple="multiple" class="infraSelect" >'
			. ' </select>'
			. ' <div id="divOpcoesUnidades">'
			. ' <img id="imgLupaUnidades" onclick="objLupaUnidades.selecionar(700,500);" src="' . PaginaSEI::getInstance()->getIconePesquisar() . '" alt="Selecionar Unidades" title="Selecionar Unidades" class="infraImg"  />'
			. ' <br />'
			. ' <img id="imgExcluirUnidades" onclick="objLupaUnidades.remover();" src="' . PaginaSEI::getInstance()->getIconeRemover() . '" alt="Remover Unidades Selecionadas" title="Remover Unidades Selecionadas" class="infraImg"  />'
			. ' </div>'
			. ' <input type="hidden" id="hdnUnidades" name="hdnUnidades" value="' . $_POST['hdnUnidades'] . '" />'
			. ' ' . $strHtmlOrgaoUnidades . ''
			. ' </div>';
	}
}
