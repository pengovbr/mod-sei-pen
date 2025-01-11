<?
/**
Criado a partir da classe EditorRN da versao 3.0.11
A modificacao nessa classe impediu gerar o documento exatamente da mesma forma que foi
gerado originalmente (v3.0.11 gerava de uma forma e na v3.1.0 esta gerando diferente)
a diferenca esta no metodo consultarHtmlIdentificacaoVersao (uma gera sem link e na outra com link) que eh privado
desta forma n ha como herdar a classe e alterar somente ele
Por seguranca replicamos a classe inteira aqui
 */


class Editor3011RN extends InfraRN
{
  public static $VERSAO_CK = '15032016c';

  public static $REGEXP_LINK_ASSINADO="@<a[^>]*href=\"[^>]*controlador\\.php\\?acao=([^&]*)&(?:amp;)?.*?id_pro(?:tocolo|cedimento)=(\\d+)&.*?infra_sistema=(\\d+)&(?:amp;)?infra_unidade_atual=\\d+&(?:amp;)?infra_hash=[^\"]*\"[^>]*>(.*?)<\\/a>@i";
  public static $REGEXP_LINK_ASSINADO_SIMPLES='@(<a[^>]*href=")([^"]*_sistema=\d+&(?:amp;)?infra_unidade_atual=\d+&(?:amp;)?infra_hash=[^"]*)"([^>]*>)([^<]*)</a>@i';
  public static $REGEXP_SPAN_LINKSEI='@(?><span[^>]*>)?<a[^>]*id="lnkSei(\d+)[^>]*>([^<]+)<\/a>(?><\/span>)?@i';
  public static $REGEXP_ATRIB_VALOR="%'[^']*':'[^']*'%";
  public static $REGEXP_VARIAVEL_EDITOR='/md_([a-z0-9]+)_[a-z0-9]+(?>_[a-z0-9]+)*]*/';

  private static $arrTags;
  private $arrProtocolos;

  public function __construct()
  {
    parent::__construct();
  }

  protected function inicializarObjInfraIBanco()
  {
    return BancoSEI::getInstance();
  }

  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
  protected function montarSimplesConectado(EditorDTO $objEditorDTO)
  {

    $ret=new EditorDTO();
    $objOrgaoDTO = new OrgaoDTO();
    $objOrgaoDTO->retStrServidorCorretorOrtografico();
    $objOrgaoDTO->retStrStaCorretorOrtografico();
    $objOrgaoDTO->setNumIdOrgao(SessaoSEI::getInstance()->getNumIdOrgaoUnidadeAtual());


    if ($objEditorDTO->isSetStrSinEstilos()){
      $strEstilos=$objEditorDTO->getStrSinEstilos();
    } else {
      $strEstilos='S';
    }
    if ($objEditorDTO->isSetStrSinImagens()){
      $bolImagens=($objEditorDTO->getStrSinImagens()=='S');
    } else {
      $bolImagens=true;
    }
    if ($objEditorDTO->isSetStrSinCodigoFonte()){
      $strCodigoFonte=$objEditorDTO->getStrSinCodigoFonte();
    } else {
      $strCodigoFonte='';
    }
    if ($objEditorDTO->isSetStrSinAutoTexto()){
      $bolAutotexto=($objEditorDTO->getStrSinAutoTexto()=='S');
    } else {
      $bolAutotexto=false;
    }
    if ($objEditorDTO->isSetStrSinLinkSei()){
      $bolLinkSei=($objEditorDTO->getStrSinLinkSei()=='S');
    } else {
      $bolLinkSei=false;
    }
    $objOrgaoRN = new OrgaoRN();
    $objOrgaoDTO = $objOrgaoRN->consultarRN1352($objOrgaoDTO);

    $objImagemFormatoDTO = new ImagemFormatoDTO();
    $objImagemFormatoDTO->retStrFormato();

    $objImagemFormatoRN = new ImagemFormatoRN();
    $arrImagemPermitida = InfraArray::converterArrInfraDTO($objImagemFormatoRN->listar($objImagemFormatoDTO), 'Formato');
    if (in_array('jpg', $arrImagemPermitida) && !in_array('jpeg', $arrImagemPermitida)) { $arrImagemPermitida[] = 'jpeg';
    }

    $includePlugins = array('simpleLink', 'notification', 'extenso', 'maiuscula', 'stylesheetparser', 'tableresize', 'tableclean', 'symbol','pastesei');
    if ($bolAutotexto) { $includePlugins[]='autotexto';
    }
    $removePlugins = array('resize', 'maximize', 'link','wsc','assinatura','save');

    if ($bolLinkSei) {
      $includePlugins[]='linksei';
    }
    else {
      $removePlugins[]='linksei';
    }

    $scayt="";
    if ($objOrgaoDTO->getStrStaCorretorOrtografico()==OrgaoRN::$TCO_LICENCIADO) {
      try {
        $scayt = InfraUtil::isBolUrlValida($objOrgaoDTO->getStrServidorCorretorOrtografico()."/spellcheck/lf/scayt3/ckscayt/ckscayt.js")?"scayt3":"scayt";
      }
      catch(Exception $e){
        $scayt="";
        LogSEI::getInstance()->gravar("Falha na conexão com o servidor de correção ortográfica:\n".InfraException::inspecionar($e));
      }
    }
    if ($scayt!="") {
        $includePlugins[] = $scayt;
    }

    $ie = PaginaSEI::getInstance()->isBolNavegadorIE();
    if ($ie) { $ie = PaginaSEI::getInstance()->getNumVersaoInternetExplorer();
    }
    if ($bolImagens && count($arrImagemPermitida)>0 && !PaginaSEI::getInstance()->isBolNavegadorSafariIpad() && (!$ie || $ie>7)) {
      $includePlugins[] = 'base64image';
    } else {
      $removePlugins[] = 'base64image';
    }
    $strInicializacao = '<script type="text/javascript" charset="utf-8" src="editor/ck/ckeditor.js?t=' . self::$VERSAO_CK . '"></script>';
    $strLinkAnexos = SessaoSEI::getInstance()->assinarLink('controlador.php?acao=editor_imagem_upload');
    $strLinkAjax = SessaoSEI::getInstance()->assinarLink('controlador_ajax.php?acao_ajax=upload_buscar');

    $strUsuario = strtolower(SessaoSEI::getInstance()->getStrSiglaUsuario() . '.' . SessaoSEI::getInstance()->getStrSiglaOrgaoUsuario());

    $arrConfig = ConfiguracaoSEI::getInstance()->getArrConfiguracoes();
    $strRegexSistema = str_replace('.', '\.', $arrConfig['SEI']['URL']).'.*infra_hash=.*';
    $strRegexSistema = preg_replace("@http[s]?://@", '', $strRegexSistema);

    $strInicializacao .= '<style type="text/css" >';
    $strInicializacao .= "\n.cke_combo__styles .cke_combo_text {width:230px !important;}\n";
    $strInicializacao .= ".cke_button__save_label {display:inline !important;}\n";
    $strInicializacao .= ".cke_button__autotexto_label {display:inline !important;}\n";
    $strInicializacao .= ".cke_combopanel__styles {width:400px !important;}\n";
    $strInicializacao .= '</style>';
    $strInicializacao .= '<script type="text/javascript">';
    $strInicializacao .= "CKEDITOR.config.url_sei_re='".$strRegexSistema."';\n";
    $strInicializacao .= "CKEDITOR.config.removePlugins='" . implode(',', $removePlugins) . "';\n";
    $strInicializacao .= "CKEDITOR.config.extraPlugins='" . implode(',', $includePlugins) . "';\n";
    $strInicializacao .= "CKEDITOR.config.base64image_filetypes='" . strtolower(implode('|', $arrImagemPermitida)) . "';\n";
    $strInicializacao .= "CKEDITOR.config.base64imageUploadUrl='" . $strLinkAnexos . "';\n";
    $strInicializacao .= "CKEDITOR.config.base64imageAjaxUrl='" . $strLinkAjax . "';\n";
    $strInicializacao .= "CKEDITOR.config.scayt_userDictionaryName = '" . $strUsuario . "';\n";
    $strInicializacao .= "CKEDITOR.config.wsc_userDictionaryName = '" . $strUsuario . "';\n";

    if ($objOrgaoDTO->getStrStaCorretorOrtografico()==OrgaoRN::$TCO_LICENCIADO) {
      $strInicializacao .= "CKEDITOR.config.wsc_customLoaderScript = '" . $objOrgaoDTO->getStrServidorCorretorOrtografico() . "/spellcheck/lf/22/js/wsc_fck2plugin.js';\n";
      if ($scayt=="scayt"){
        $strInicializacao .= "CKEDITOR.config.scayt_srcUrl = '" . $objOrgaoDTO->getStrServidorCorretorOrtografico() . "/spellcheck/lf/scayt/scayt.js?".self::$VERSAO_CK."';\n";
      } elseif ($scayt=="scayt3") {
        $strInicializacao .="CKEDITOR.config.scayt_srcUrl = '".$objOrgaoDTO->getStrServidorCorretorOrtografico()."/spellcheck/lf/scayt3/ckscayt/ckscayt.js?".self::$VERSAO_CK."';\n";
      }
    }
    $strInicializacao .= "CKEDITOR.config.height=";
    if ($objEditorDTO->isSetNumTamanhoEditor()) {
      $strInicializacao .= $objEditorDTO->getNumTamanhoEditor();
    } else {
      $strInicializacao .= "500";
    }
    $strInicializacao .= ";\n";

    if ($objEditorDTO->getStrSinSomenteLeitura()=='S') {
      $strInicializacao .= "CKEDITOR.config.readOnly=true;\n";
    }
    $ret->setStrCss($this->jsEncode($this->montarCssEditor(0)));
    if ($strEstilos=='S') {
      $strInicializacao .= "CKEDITOR.config.contentsCss=" . $ret->getStrCss() . ";\n";
    }
    $strInicializacao .= "</script>\n";
    $ret->setStrInicializacao($strInicializacao);

    $strEditor = "CKEDITOR.replace('" . $objEditorDTO->getStrNomeCampo() . "',{ 'toolbar':";
    $strEditor .= $this->jsEncode($this->montarBarraFerramentas($bolAutotexto, false, ($scayt!=""), $strCodigoFonte, $strEstilos));

    if ($objOrgaoDTO->getStrStaCorretorOrtografico()==OrgaoRN::$TCO_NATIVO_NAVEGADOR || ($objOrgaoDTO->getStrStaCorretorOrtografico()!=OrgaoRN::$TCO_NENHUM && $objOrgaoDTO->getStrStaCorretorOrtografico()!=OrgaoRN::$TCO_LICENCIADO)){
      $strEditor .= ",disableNativeSpellChecker:false";
    }
    $strEditor .= "});";
    $ret->setStrEditores($strEditor);


    return $ret;
  }

  private function montarBarraFerramentas($bolAdicionarTextoPadrao, $bolBtnWSC, $bolBtnScayt, $strBtnSource = '', $strEstilos = '')
  {

    $arrGrupoEstilos = array();

    if ($bolAdicionarTextoPadrao) {
      $arrGrupoEstilos[] = 'autotexto';
    }
    if ($strEstilos=='' || $strEstilos=='S') {
      $arrGrupoEstilos[] = 'Styles';
    }

    $arrRetorno = array();
    if ($strBtnSource!='N' && ($strBtnSource=='S' || SessaoSEI::getInstance()->verificarPermissao('editor_visualizar_codigo_fonte'))) {
      $arrRetorno[] = array('Source');
    }

    $arrRetorno[] = array('Save');
    if (SessaoSEI::getInstance()->verificarPermissao('documento_assinar')) {
      $arrRetorno[] = array('assinatura');
    }

    $arrRetorno[] = array('Find', 'Replace', '-', 'RemoveFormat', 'Bold', 'Italic', 'Underline', 'Strike','Subscript','Superscript', 'Maiuscula', 'Minuscula', 'TextColor', 'BGColor');

    $temp = array('Cut', 'Copy', 'PasteFromWord', 'PasteText', '-', 'Undo', 'Redo', 'ShowBlocks','Symbol');
    if ($bolBtnWSC) { $temp[] = 'SpellChecker';
    }
    if ($bolBtnScayt) { $temp[] = 'Scayt';
    }
    $arrRetorno[] = $temp;

    $arrRetorno[] = array('NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', 'base64image');
    if (!PaginaSEI::getInstance()->isBolNavegadorFirefox() || PaginaSEI::getInstance()->getNumVersaoFirefox()>=16) { // não disponibiliza zoom no firefox<16 devido a bug
      $arrRetorno[] = array('Table', 'SpecialChar', 'SimpleLink', 'linksei', 'Extenso', 'Zoom');
    } else {
      $arrRetorno[] = array('Table', 'SpecialChar', 'SimpleLink', 'linksei', 'Extenso');
    }
    $arrRetorno[] = $arrGrupoEstilos;

    return $arrRetorno;
  }

  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
  protected function montarControlado(EditorDTO $parObjEditorDTO)
  {
    try {

      //gerar nova versao igual a anterior
      $objVersaoSecaoDocumentoDTO = new VersaoSecaoDocumentoDTO();
      $objVersaoSecaoDocumentoDTO->retNumIdSecaoModeloSecaoDocumento();
      $objVersaoSecaoDocumentoDTO->retStrSinAssinaturaSecaoDocumento();
      $objVersaoSecaoDocumentoDTO->retStrSinSomenteLeituraSecaoDocumento();
      $objVersaoSecaoDocumentoDTO->retStrSinPrincipalSecaoDocumento();
      $objVersaoSecaoDocumentoDTO->retStrSinDinamicaSecaoDocumento();
      $objVersaoSecaoDocumentoDTO->retStrSinCabecalhoSecaoDocumento();
      $objVersaoSecaoDocumentoDTO->retStrSinRodapeSecaoDocumento();
      $objVersaoSecaoDocumentoDTO->retStrConteudo();
      $objVersaoSecaoDocumentoDTO->setDblIdDocumentoSecaoDocumento($parObjEditorDTO->getDblIdDocumento());
      $objVersaoSecaoDocumentoDTO->setNumIdBaseConhecimentoSecaoDocumento($parObjEditorDTO->getNumIdBaseConhecimento());
      $objVersaoSecaoDocumentoDTO->setStrSinUltima('S');
      $objVersaoSecaoDocumentoDTO->setOrdNumOrdemSecaoDocumento(InfraDTO::$TIPO_ORDENACAO_ASC);

      $objVersaoSecaoDocumentoRN = new VersaoSecaoDocumentoRN();
      $arrObjVersaoSecaoDocumentoDTO = $objVersaoSecaoDocumentoRN->listar($objVersaoSecaoDocumentoDTO);

      $arrObjSecaoDocumentoDTO = array();
      $bolDinamica = false;
      foreach ($arrObjVersaoSecaoDocumentoDTO as $objVersaoSecaoDocumentoDTO2) {

        if ($objVersaoSecaoDocumentoDTO2->getStrSinAssinaturaSecaoDocumento()=='N') {
          $objSecaoDocumentoDTO = new SecaoDocumentoDTO();
          $objSecaoDocumentoDTO->setNumIdSecaoModelo($objVersaoSecaoDocumentoDTO2->getNumIdSecaoModeloSecaoDocumento());
          $objSecaoDocumentoDTO->setStrConteudo($objVersaoSecaoDocumentoDTO2->getStrConteudo());
          $arrObjSecaoDocumentoDTO[] = $objSecaoDocumentoDTO;
        }

        if ($objVersaoSecaoDocumentoDTO2->getStrSinDinamicaSecaoDocumento()=='N') {
          $bolDinamica = true;
        }
      }

      $bolValidacao = false;
      $strValidacoes = '';
      try {

        $parObjEditorDTO->setArrObjSecaoDocumentoDTO($arrObjSecaoDocumentoDTO);
        $numVersao = $this->adicionarVersao($parObjEditorDTO);

      } catch (InfraException $e) {
        if ($e->contemValidacoes()) {
          $ret=new EditorDTO();
          $ret->setNumVersao(null);
          $ret->setStrToolbar('[]');
          $ret->setStrTextareas(null);
          $ret->setStrCss(null);
          $ret->setStrInicializacao(null);
          $ret->setStrEditores(null);
          $ret->setStrValidacao(true);
          $ret->setStrMensagens($e->__toString());
          return $ret;
        } else {
          throw $e;
        }
      }

      //se possui secoes dinamicas entao lista novamente para exibir o conteudo atualizado na ultima versao
      if ($bolDinamica) {
        $objVersaoSecaoDocumentoRN = new VersaoSecaoDocumentoRN();
        $arrObjVersaoSecaoDocumentoDTO = $objVersaoSecaoDocumentoRN->listar($objVersaoSecaoDocumentoDTO);
      }

      if (!$parObjEditorDTO->isSetNumIdConjuntoEstilos()) {
        $objConjuntoEstilosDTO = new ConjuntoEstilosDTO();
        $objConjuntoEstilosRN = new ConjuntoEstilosRN();
        $objConjuntoEstilosDTO->setStrSinUltimo('S');
        $objConjuntoEstilosDTO->retNumIdConjuntoEstilos();
        $objConjuntoEstilosDTO = $objConjuntoEstilosRN->consultar($objConjuntoEstilosDTO);
        $parObjEditorDTO->setNumIdConjuntoEstilos($objConjuntoEstilosDTO->getNumIdConjuntoEstilos());
      }
      $strConteudoCss = $this->montarCssEditor($parObjEditorDTO->getNumIdConjuntoEstilos());
      $strEditores = '';
      $strTextareas = '';

      //busca os estilos permitidos por seção-modelo
      $objRelSecaoModCjEstilosItemDTO = new RelSecaoModCjEstilosItemDTO();
      $objRelSecaoModCjEstilosItemDTO->retNumIdSecaoModelo();
      $objRelSecaoModCjEstilosItemDTO->retStrNomeEstilo();
      $objRelSecaoModCjEstilosItemDTO->retStrFormatacao();
      $objRelSecaoModCjEstilosItemDTO->setNumIdSecaoModelo(InfraArray::converterArrInfraDTO($arrObjVersaoSecaoDocumentoDTO, 'IdSecaoModeloSecaoDocumento'), InfraDTO::$OPER_IN);
      $objRelSecaoModCjEstilosItemDTO->setOrdStrNomeEstilo(InfraDTO::$TIPO_ORDENACAO_ASC);
      $objRelSecaoModCjEstilosItemDTO->setNumIdConjuntoEstilos($parObjEditorDTO->getNumIdConjuntoEstilos());
      $objRelSecaoModCjEstilosItemRN = new RelSecaoModCjEstilosItemRN();
      $arrObjRelSecaoModCjEstilosItemDTO = InfraArray::indexarArrInfraDTO($objRelSecaoModCjEstilosItemRN->listar($objRelSecaoModCjEstilosItemDTO), 'IdSecaoModelo', true);

      $strLinkAnexos = SessaoSEI::getInstance()->assinarLink('controlador.php?acao=documento_upload_anexo');

      $objOrgaoDTO = new OrgaoDTO();
      $objOrgaoDTO->retStrServidorCorretorOrtografico();
      $objOrgaoDTO->retStrStaCorretorOrtografico();
      $objOrgaoDTO->setNumIdOrgao(SessaoSEI::getInstance()->getNumIdOrgaoUnidadeAtual());

      $objOrgaoRN = new OrgaoRN();
      $objOrgaoDTO = $objOrgaoRN->consultarRN1352($objOrgaoDTO);

      foreach ($arrObjVersaoSecaoDocumentoDTO as $objVersaoSecaoDocumentoDTO) {
        if ($objVersaoSecaoDocumentoDTO->getStrSinAssinaturaSecaoDocumento()=='N') {

          $strFormatos = "";
          if (isset($arrObjRelSecaoModCjEstilosItemDTO[$objVersaoSecaoDocumentoDTO->getNumIdSecaoModeloSecaoDocumento()])) {
            foreach ($arrObjRelSecaoModCjEstilosItemDTO[$objVersaoSecaoDocumentoDTO->getNumIdSecaoModeloSecaoDocumento()] as $objRelSecaoModCjEstilosItemDTO) {
              $strFormatos .= $objRelSecaoModCjEstilosItemDTO->getStrNomeEstilo() . "|";
            }
          }
          $strFormatos = rtrim($strFormatos, '|');

          $strTextareas .= '<textarea name="txaEditor_' . $objVersaoSecaoDocumentoDTO->getNumIdSecaoModeloSecaoDocumento() . '" style="display:none;">';
          $strTextareas .= PaginaSEI::tratarHTML($this->filtrarTags($objVersaoSecaoDocumentoDTO->getStrConteudo()));
          $strTextareas .= '</textarea>';


          $strEditores .= "CKEDITOR.replace('txaEditor_" . $objVersaoSecaoDocumentoDTO->getNumIdSecaoModeloSecaoDocumento() . "',";
          $strEditores .= '{filebrowserUploadUrl:"' . $strLinkAnexos . '","toolbar":toolbar,"stylesheetParser_validSelectors":/^(p)\.(';
          $strEditores .= $strFormatos . ')$/i,';

          if ($objVersaoSecaoDocumentoDTO->getStrSinDinamicaSecaoDocumento()=='S') {
            $strEditores .= '"dinamico":true,';
          }

          if ($objOrgaoDTO->getStrStaCorretorOrtografico()==OrgaoRN::$TCO_NATIVO_NAVEGADOR || ($objOrgaoDTO->getStrStaCorretorOrtografico()!=OrgaoRN::$TCO_NENHUM && $objOrgaoDTO->getStrStaCorretorOrtografico()!=OrgaoRN::$TCO_LICENCIADO)){
            $strEditores .= "disableNativeSpellChecker:false,";
          }

          $strEditores .= '"readOnly":';

          if ($objVersaoSecaoDocumentoDTO->getStrSinSomenteLeituraSecaoDocumento()=='S' || $bolValidacao) {
            $strEditores .= 'true});' . "\n";
          } else {
            $strEditores .= 'false,autoGrow_bottomSpace:0});' . "\n";
          }
        }
      }



      $objImagemFormatoDTO = new ImagemFormatoDTO();
      $objImagemFormatoDTO->retStrFormato();

      $objImagemFormatoRN = new ImagemFormatoRN();
      $arrImagemPermitida = InfraArray::converterArrInfraDTO($objImagemFormatoRN->listar($objImagemFormatoDTO), 'Formato');
      if (in_array('jpg', $arrImagemPermitida) && !in_array('jpeg', $arrImagemPermitida)) { $arrImagemPermitida[] = 'jpeg';
      }


      $includePlugins = array('autogrow', 'notification', 'linksei', 'sharedspace', 'autotexto', 'simpleLink', 'extenso', 'maiuscula', 'stylesheetparser', 'stylesdefault', 'tableresize', 'symbol', 'tableclean','pastesei','widget','widgetform');
      $removePlugins = array('resize', 'maximize', 'link', 'wsc');

      if (SessaoSEI::getInstance()->verificarPermissao('documento_assinar') && $parObjEditorDTO->getNumIdBaseConhecimento()==null){
        $includePlugins[]='assinatura';
      } else {
        $removePlugins[]='assinatura';
      }

      $ie = PaginaSEI::getInstance()->isBolNavegadorIE();
      if ($ie) { $ie = PaginaSEI::getInstance()->getNumVersaoInternetExplorer();
      }
      if (count($arrImagemPermitida)>0 && !PaginaSEI::getInstance()->isBolNavegadorSafariIpad() && (!$ie || $ie>7)) {
        $includePlugins[] = 'base64image';
      } else {
        $removePlugins[] = 'base64image';
      }

      $scayt="";
      if ($objOrgaoDTO->getStrStaCorretorOrtografico()==OrgaoRN::$TCO_LICENCIADO) {
        try {
          $scayt = InfraUtil::isBolUrlValida($objOrgaoDTO->getStrServidorCorretorOrtografico()."/spellcheck/lf/scayt3/ckscayt/ckscayt.js")?"scayt3":"scayt";
        }
        catch(Exception $e){
          $scayt="";
          LogSEI::getInstance()->gravar("Falha na conexão com o servidor de correção ortográfica:\n".InfraException::inspecionar($e));
        }
      }
      if ($scayt!="") {
          $includePlugins[] = $scayt;
      }
      //desabilita zoom devido a bug do firefox <16
      if (!PaginaSEI::getInstance()->isBolNavegadorFirefox() || PaginaSEI::getInstance()->getNumVersaoFirefox()>=16) {
        $includePlugins[] = "zoom";
      }

      $strUsuarioDicionario = strtolower(SessaoSEI::getInstance()->getStrSiglaUsuario() . '.' . SessaoSEI::getInstance()->getStrSiglaOrgaoUsuario());
      $strLinkAnexos = SessaoSEI::getInstance()->assinarLink('controlador.php?acao=editor_imagem_upload');
      $strLinkAjax = SessaoSEI::getInstance()->assinarLink('controlador_ajax.php?acao_ajax=upload_buscar');
      $arrConfig = ConfiguracaoSEI::getInstance()->getArrConfiguracoes();
      $strRegexSistema = str_replace('.', '\.', $arrConfig['SEI']['URL']).'.*infra_hash=.*';
      $strRegexSistema = preg_replace("@http[s]?://@", '', $strRegexSistema);

      $strInicializacao = '<script type="text/javascript" charset="utf-8" src="editor/ck/ckeditor.js?t=' . self::$VERSAO_CK . '"></script>';//prod
//      $strInicializacao = '<script type="text/javascript" charset="utf-8" src="../cksei/ckeditor.js?t=' . self::$VERSAO_CK . '"></script>';//dev-source
      //$strInicializacao = '<script type="text/javascript" charset="utf-8" src="../cksei/dev/builder/release/ckeditor/ckeditor.js?t=' . self::$VERSAO_CK . '"></script>';//dev-compilado
      $strInicializacao .= '<script type="text/javascript">';
      $strInicializacao .= "CKEDITOR.config.url_sei_re='".$strRegexSistema."';\n";
      $strInicializacao .= "CKEDITOR.config.removePlugins='" . implode(',', $removePlugins) . "';\n";
      $strInicializacao .= "CKEDITOR.config.extraPlugins='" . implode(',', $includePlugins) . "';\n";
      $strInicializacao .= "CKEDITOR.config.base64image_filetypes='" . implode('|', $arrImagemPermitida) . "';\n";
      $strInicializacao .= "CKEDITOR.config.base64imageUploadUrl='" . $strLinkAnexos . "';\n";
      $strInicializacao .= "CKEDITOR.config.base64imageAjaxUrl='" . $strLinkAjax . "';\n";
      $strInicializacao .= "CKEDITOR.config.height=100;\n";
      $strInicializacao .= "CKEDITOR.config.scayt_userDictionaryName = '" . $strUsuarioDicionario . "';\n";
      $strInicializacao .= "CKEDITOR.config.wsc_userDictionaryName = '" . $strUsuarioDicionario . "';\n";
      $strInicializacao .= "CKEDITOR.config.readOnly=true;\n";

      if ($objOrgaoDTO->getStrStaCorretorOrtografico()==OrgaoRN::$TCO_LICENCIADO) {
        $strInicializacao .= "CKEDITOR.config.wsc_customLoaderScript = '" . $objOrgaoDTO->getStrServidorCorretorOrtografico() . "/spellcheck/lf/22/js/wsc_fck2plugin.js';\n";
        if ($scayt=="scayt"){
          $strInicializacao .= "CKEDITOR.config.scayt_srcUrl = '" . $objOrgaoDTO->getStrServidorCorretorOrtografico() . "/spellcheck/lf/scayt/scayt.js?".self::$VERSAO_CK."';\n";
        } elseif ($scayt=="scayt3") {
          $strInicializacao .="CKEDITOR.config.scayt_srcUrl = '".$objOrgaoDTO->getStrServidorCorretorOrtografico()."/spellcheck/lf/scayt3/ckscayt/ckscayt.js?".self::$VERSAO_CK."';\n";
        }
      }

      $strInicializacao .= "</script>\n";

      $ret=new EditorDTO();

      $ret->setNumVersao($numVersao);
      $ret->setStrToolbar($this->jsEncode($this->montarBarraFerramentas(true, false, ($scayt!=""))));
      $ret->setStrTextareas($strTextareas);
      $ret->setStrCss($strConteudoCss);
      $ret->setStrInicializacao($strInicializacao);
      $ret->setStrEditores($strEditores);
      $ret->setStrValidacao($bolValidacao);
      $ret->setStrMensagens($strValidacoes);

      return $ret;

    } catch (Exception $e) {
      throw new InfraException('Módulo do Tramita: Erro montando editor.', $e);
    }
  }

  public function jsEncode($val)
  {
    if (is_null($val)) {
      return 'null';
    }
    if (is_bool($val)) {
      return $val ? 'true' : 'false';
    }
    if (is_int($val)) {
      return $val;
    }
    if (is_float($val)) {
      return str_replace(',', '.', $val);
    }
    if (is_array($val) || is_object($val)) {
      if (is_array($val) && (array_keys($val)===range(0, count($val) - 1))) {
        return '[' . implode(',', array_map(array($this, 'jsEncode'), $val)) . ']';
      }
      $temp = array();
      foreach ($val as $k => $v) {
        $temp[] = $this->jsEncode("{$k}") . ':' . $this->jsEncode($v);
      }
      return '{' . implode(',', $temp) . '}';
    }
    // String otherwise
    if (strpos($val, '@@')===0) {
      return substr($val, 2);
    }
    if (strtoupper(substr($val, 0, 9))=='CKEDITOR.') {
      return $val;
    }

    return '"' . str_replace(array("\\", "/", "\n", "\t", "\r", "\x08", "\x0c", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'), $val) . '"';
  }

  protected function montarCssEditorConectado($numIdConjuntoEstilos)
  {
    $objConjuntoEstilosDTO = new ConjuntoEstilosDTO();
    $objConjuntoEstilosRN = new ConjuntoEstilosRN();
    $objConjuntoEstilosItemDTO = new ConjuntoEstilosItemDTO();
    $objConjuntoEstilosItemRN = new ConjuntoEstilosItemRN();
    if ($numIdConjuntoEstilos==0 || $numIdConjuntoEstilos==null) {
      $objConjuntoEstilosDTO->setStrSinUltimo('S');
      $objConjuntoEstilosDTO->retNumIdConjuntoEstilos();
      $objConjuntoEstilosDTO = $objConjuntoEstilosRN->consultar($objConjuntoEstilosDTO);
      $objConjuntoEstilosItemDTO->setNumIdConjuntoEstilos($objConjuntoEstilosDTO->getNumIdConjuntoEstilos());
    } else {
      $objConjuntoEstilosItemDTO->setNumIdConjuntoEstilos($numIdConjuntoEstilos);
    }
    $objConjuntoEstilosItemDTO->retTodos();
    $objConjuntoEstilosItemDTO->setOrdStrNome(InfraDTO::$TIPO_ORDENACAO_ASC);
    $arrObjConjuntoEstilosItemDTO = $objConjuntoEstilosItemRN->listar($objConjuntoEstilosItemDTO);

    $strCssEditor = "";
    //converte estilos do formato antigo para css
    foreach ($arrObjConjuntoEstilosItemDTO as $objConjuntoEstilosItemDTO) {
      $strCssEditor .= "p." . $objConjuntoEstilosItemDTO->getStrNome() . " {";
      $strFormatacao = $objConjuntoEstilosItemDTO->getStrFormatacao();
      preg_match_all(self::$REGEXP_ATRIB_VALOR, $strFormatacao, $arrStrConteudo);
      foreach ($arrStrConteudo[0] as $value) {
        $value = str_replace("'", "", $value);
        $strCssEditor .= $value . ";";
      }
      $strCssEditor .= "} ";
    }

    return $strCssEditor;
  }

  public function filtrarTags($strConteudo)
  {
    $strConteudo = preg_replace("%<font[^>]*>%si", "", $strConteudo);
    $strConteudo = preg_replace("%</font>%si", "", $strConteudo);
    return str_replace(array('<o:p>', '</o:p>'), '', $strConteudo);
  }

  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
  protected function gerarVersaoInicialControlado(EditorDTO $parObjEditorDTO)
  {
    try {

      self::$arrTags = array();
      self::$arrTags['versao'] = 1;

      $objSecaoModeloRN = new SecaoModeloRN();
      $objSecaoDocumentoRN = new SecaoDocumentoRN();
      $objVersaoSecaoDocumentoRN = new VersaoSecaoDocumentoRN();
      $objDocumentoRN = new DocumentoRN();
      $dthAtual = InfraData::getStrDataHoraAtual();

      $parObjEditorDTO->setNumIdConjuntoEstilos(null);

      if ($parObjEditorDTO->isSetDblIdDocumentoBase() || $parObjEditorDTO->isSetDblIdDocumentoTextoBase()) {
        $objDocumentoDTO = new DocumentoDTO();
        $objDocumentoDTO->retNumIdConjuntoEstilos();
        if ($parObjEditorDTO->isSetDblIdDocumentoBase()) {
          $objDocumentoDTO->setDblIdDocumento($parObjEditorDTO->getDblIdDocumentoBase());
        } else {
          $objDocumentoDTO->setDblIdDocumento($parObjEditorDTO->getDblIdDocumentoTextoBase());
        }
        $objDocumentoDTO = $objDocumentoRN->consultarRN0005($objDocumentoDTO);
        $parObjEditorDTO->setNumIdConjuntoEstilos($objDocumentoDTO->getNumIdConjuntoEstilos());

      } else if ($parObjEditorDTO->isSetNumIdBaseConhecimentoBase()) {
        $objBaseConhecimentoDTO = new BaseConhecimentoDTO();
        $objBaseConhecimentoDTO->retNumIdConjuntoEstilos();
        $objBaseConhecimentoDTO->setNumIdBaseConhecimento($parObjEditorDTO->getNumIdBaseConhecimentoBase());
        $objBaseConhecimentoRN = new BaseConhecimentoRN();
        $objBaseConhecimentoDTO = $objBaseConhecimentoRN->consultar($objBaseConhecimentoDTO);
        $parObjEditorDTO->setNumIdConjuntoEstilos($objBaseConhecimentoDTO->getNumIdConjuntoEstilos());
      } else if ($parObjEditorDTO->isSetNumIdTextoPadraoInterno()) {
        $objTextoPadraoInternoDTO = new TextoPadraoInternoDTO();
        $objTextoPadraoInternoRN = new TextoPadraoInternoRN();
        $objTextoPadraoInternoDTO->retNumIdConjuntoEstilos();
        $objTextoPadraoInternoDTO->setNumIdTextoPadraoInterno($parObjEditorDTO->getNumIdTextoPadraoInterno());
        $objTextoPadraoInternoDTO = $objTextoPadraoInternoRN->consultar($objTextoPadraoInternoDTO);
        $parObjEditorDTO->setNumIdConjuntoEstilos($objTextoPadraoInternoDTO->getNumIdConjuntoEstilos());
      }

      if ($parObjEditorDTO->getNumIdConjuntoEstilos()==null) {
        $objConjuntoEstilosDTO = new ConjuntoEstilosDTO();
        $objConjuntoEstilosDTO->setStrSinUltimo('S');
        $objConjuntoEstilosDTO->retNumIdConjuntoEstilos();
        $objConjuntoEstilosRN = new ConjuntoEstilosRN();
        $objConjuntoEstilosDTO = $objConjuntoEstilosRN->consultar($objConjuntoEstilosDTO);
        $parObjEditorDTO->setNumIdConjuntoEstilos($objConjuntoEstilosDTO->getNumIdConjuntoEstilos());
      }

      if ($parObjEditorDTO->getNumIdBaseConhecimento()!=null) {
        $objBaseConhecimentoDTO = new BaseConhecimentoDTO();
        $objBaseConhecimentoRN = new BaseConhecimentoRN();
        $objBaseConhecimentoDTO->setNumIdBaseConhecimento($parObjEditorDTO->getNumIdBaseConhecimento());
        $objBaseConhecimentoDTO->setNumIdConjuntoEstilos($parObjEditorDTO->getNumIdConjuntoEstilos());
        $objBaseConhecimentoRN->configurarEstilos($objBaseConhecimentoDTO);
      } else {
        $objDocumentoDTO = new DocumentoDTO();
        $objDocumentoDTO->setDblIdDocumento($parObjEditorDTO->getDblIdDocumento());
        $objDocumentoDTO->setNumIdConjuntoEstilos($parObjEditorDTO->getNumIdConjuntoEstilos());
        $objDocumentoRN->configurarEstilos($objDocumentoDTO);
      }

      $arrConteudoInicalSecoes = null;
      if ($parObjEditorDTO->isSetArrConteudoInicialSecoes()) {
        $arrConteudoInicalSecoes = $parObjEditorDTO->getArrConteudoInicialSecoes();
      }

      if (!$parObjEditorDTO->isSetDblIdDocumentoBase() && !$parObjEditorDTO->isSetNumIdBaseConhecimentoBase()) {

        //recupera seções do modelo
        $objSecaoModeloDTO = new SecaoModeloDTO();
        $objSecaoModeloDTO->retNumIdSecaoModelo();
        $objSecaoModeloDTO->retStrNome();
        $objSecaoModeloDTO->retStrSinSomenteLeitura();
        $objSecaoModeloDTO->retStrSinAssinatura();
        $objSecaoModeloDTO->retStrSinPrincipal();
        $objSecaoModeloDTO->retStrSinDinamica();
        $objSecaoModeloDTO->retStrSinCabecalho();
        $objSecaoModeloDTO->retStrSinRodape();
        $objSecaoModeloDTO->retStrSinHtml();
        $objSecaoModeloDTO->retStrConteudo();
        $objSecaoModeloDTO->retNumOrdem();
        $objSecaoModeloDTO->setNumIdModelo($parObjEditorDTO->getNumIdModelo());
        $objSecaoModeloDTO->setOrdNumOrdem(InfraDTO::$TIPO_ORDENACAO_ASC);

        $arrObjSecaoModeloDTO = $objSecaoModeloRN->listar($objSecaoModeloDTO);

        if (count($arrObjSecaoModeloDTO)==0) {
          throw new InfraException('Módulo do Tramita: Modelo do documento não contém seções.');
        }


        //recupera estilos padrão das seções do modelo
        $objRelSecaoModCjEstilosItemDTO = new RelSecaoModCjEstilosItemDTO();
        $objRelSecaoModCjEstilosItemDTO->retNumIdSecaoModelo();
        $objRelSecaoModCjEstilosItemDTO->retStrNomeEstilo();
        $objRelSecaoModCjEstilosItemDTO->setNumIdSecaoModelo(InfraArray::converterArrInfraDTO($arrObjSecaoModeloDTO, 'IdSecaoModelo'), InfraDTO::$OPER_IN);
        $objRelSecaoModCjEstilosItemDTO->setStrSinPadrao('S');
        $objRelSecaoModCjEstilosItemDTO->setNumIdConjuntoEstilos($parObjEditorDTO->getNumIdConjuntoEstilos());
        $objRelSecaoModCjEstilosItemRN = new RelSecaoModCjEstilosItemRN();
        $arrObjRelSecaoModCjEstilosItemDTO = InfraArray::indexarArrInfraDTO($objRelSecaoModCjEstilosItemRN->listar($objRelSecaoModCjEstilosItemDTO), 'IdSecaoModelo');

        $objImagemFormatoDTO = new ImagemFormatoDTO();
        $objImagemFormatoDTO->retStrFormato();
        $objImagemFormatoDTO->setBolExclusaoLogica(false);

        $objImagemFormatoRN = new ImagemFormatoRN();
        $arrImagemPermitida = InfraArray::converterArrInfraDTO($objImagemFormatoRN->listar($objImagemFormatoDTO), 'Formato');
        if (in_array('jpg', $arrImagemPermitida) && !in_array('jpeg', $arrImagemPermitida)) { $arrImagemPermitida[] = 'jpeg';
        }

        //gera copia das secoes do modelo, ja formatando o conteudo com a formatacao padrao
        foreach ($arrObjSecaoModeloDTO as $objSecaoModeloDTO) {

          $objSecaoDocumentoDTO = new SecaoDocumentoDTO();
          $objSecaoDocumentoDTO->setDblIdDocumento($parObjEditorDTO->getDblIdDocumento());
          $objSecaoDocumentoDTO->setNumIdBaseConhecimento($parObjEditorDTO->getNumIdBaseConhecimento());
          $objSecaoDocumentoDTO->setNumIdSecaoModelo($objSecaoModeloDTO->getNumIdSecaoModelo());
          $objSecaoDocumentoDTO->setNumOrdem($objSecaoModeloDTO->getNumOrdem());
          $objSecaoDocumentoDTO->setStrSinSomenteLeitura($objSecaoModeloDTO->getStrSinSomenteLeitura());
          $objSecaoDocumentoDTO->setStrSinAssinatura($objSecaoModeloDTO->getStrSinAssinatura());
          $objSecaoDocumentoDTO->setStrSinPrincipal($objSecaoModeloDTO->getStrSinPrincipal());
          $objSecaoDocumentoDTO->setStrSinDinamica($objSecaoModeloDTO->getStrSinDinamica());
          $objSecaoDocumentoDTO->setStrSinHtml($objSecaoModeloDTO->getStrSinHtml());
          $objSecaoDocumentoDTO->setStrSinCabecalho($objSecaoModeloDTO->getStrSinCabecalho());
          $objSecaoDocumentoDTO->setStrSinRodape($objSecaoModeloDTO->getStrSinRodape());
          $objSecaoDocumentoDTO->setStrConteudo($objSecaoModeloDTO->getStrConteudo());

          $objSecaoDocumentoDTO = $objSecaoDocumentoRN->cadastrar($objSecaoDocumentoDTO);

          if ($objSecaoModeloDTO->getStrSinAssinatura()=='N') {

            //cadastra primeiro registro de versão da seção
            $objVersaoSecaoDocumentoDTO = new VersaoSecaoDocumentoDTO();
            $objVersaoSecaoDocumentoDTO->setDblIdVersaoSecaoDocumento(null);
            $objVersaoSecaoDocumentoDTO->setNumIdSecaoDocumento($objSecaoDocumentoDTO->getNumIdSecaoDocumento());

            $strEstiloPadrao = '';
            if (isset($arrObjRelSecaoModCjEstilosItemDTO[$objSecaoModeloDTO->getNumIdSecaoModelo()])) {
              $strEstiloPadrao = 'class="' . $arrObjRelSecaoModCjEstilosItemDTO[$objSecaoModeloDTO->getNumIdSecaoModelo()]->getStrNomeEstilo() . '"';
            }

            $strConteudo = '';
            $bolConteudoEdoc = false;
            $bolConteudoSecaoPrincipal = false;
            $bolConteudoTextoPadrao = false;
            $bolConteudoTextoBase = false;

            //conteúdo informado especificamente para esta seção
            if ($arrConteudoInicalSecoes!=null && isset($arrConteudoInicalSecoes[$objSecaoModeloDTO->getStrNome()])) {
              $strConteudo = $arrConteudoInicalSecoes[$objSecaoModeloDTO->getStrNome()];

              //se deve copiar o conteúdo de um documento do eDoc então aplica na seção principal do documento
            } else if ($objSecaoModeloDTO->getStrSinPrincipal()=='S' && $parObjEditorDTO->isSetDblIdDocumentoEdocBase()) {

              $objDocumentoDTO = new DocumentoDTO();
              $objDocumentoDTO->setDblIdDocumentoEdoc($parObjEditorDTO->getDblIdDocumentoEdocBase());

              $objEDocRN = new EDocRN();
              $strConteudo = EDocINT::converterParaEditorInterno($objEDocRN->consultarHTMLDocumentoRN1204($objDocumentoDTO));
              $bolConteudoEdoc = true;

              //configurar conteudo da seção editável com o conteúdo da mesma seção no documento usado para texto base
            } else if ($objSecaoModeloDTO->getStrSinSomenteLeitura()=='N' && $parObjEditorDTO->isSetDblIdDocumentoTextoBase()) {

              $objVersaoSecaoDocumentoDTOTextoBase = new VersaoSecaoDocumentoDTO();
              $objVersaoSecaoDocumentoDTOTextoBase->retStrConteudo();
              $objVersaoSecaoDocumentoDTOTextoBase->setStrSinUltima('S');
              $objVersaoSecaoDocumentoDTOTextoBase->setDblIdDocumentoSecaoDocumento($parObjEditorDTO->getDblIdDocumentoTextoBase());
              $objVersaoSecaoDocumentoDTOTextoBase->setStrNomeSecaoModelo($objSecaoModeloDTO->getStrNome());

              $objVersaoSecaoDocumentoDTOTextoBase = $objVersaoSecaoDocumentoRN->consultar($objVersaoSecaoDocumentoDTOTextoBase);

              if ($objVersaoSecaoDocumentoDTOTextoBase!=null) {
                $strConteudo = $objVersaoSecaoDocumentoDTOTextoBase->getStrConteudo();
                $bolConteudoTextoBase = true;
              }

              //conteudo informado para seção principal
            } else if ($objSecaoModeloDTO->getStrSinPrincipal()=='S' && $parObjEditorDTO->isSetStrConteudoSecaoPrincipal()) {
              $strConteudo = $parObjEditorDTO->getStrConteudoSecaoPrincipal();
              $bolConteudoSecaoPrincipal = true;

              //texto padrão deve ser aplicado na seção principal
            } else if ($objSecaoModeloDTO->getStrSinPrincipal()=='S' && $parObjEditorDTO->isSetNumIdTextoPadraoInterno()) {

              $objTextoPadraoInternoDTO = new TextoPadraoInternoDTO();
              $objTextoPadraoInternoDTO->retStrConteudo();
              $objTextoPadraoInternoDTO->retNumIdConjuntoEstilos();
              $objTextoPadraoInternoDTO->setNumIdTextoPadraoInterno($parObjEditorDTO->getNumIdTextoPadraoInterno());

              $objTextoPadraoInternoRN = new TextoPadraoInternoRN();
              $objTextoPadraoInternoDTO = $objTextoPadraoInternoRN->consultar($objTextoPadraoInternoDTO);

              $strConteudo = $objTextoPadraoInternoDTO->getStrConteudo();
              $bolConteudoTextoPadrao = true;

              //coloca conteúdo inicial definido no modelo
            } else {
              $strConteudo = $objSecaoModeloDTO->getStrConteudo();
            }

            if (trim($strConteudo)=='') {
              if ($objSecaoModeloDTO->getStrSinSomenteLeitura()=='S') {
                $objVersaoSecaoDocumentoDTO->setStrConteudo(null);
              } else {
                $objVersaoSecaoDocumentoDTO->setStrConteudo('<p ' . $strEstiloPadrao . '>&nbsp;</p>' . "\r\n");
              }
            } else {

              //efetua limpeza de tags para documentos gerados com conteudo inicial
              $this->validarTagsCriticas($arrImagemPermitida, $strConteudo, $parObjEditorDTO->getDblIdDocumento());
              $strConteudo=$this->processarLinksSei($strConteudo);

              $strConteudo=$this->substituirTagsInterno($parObjEditorDTO, $strConteudo);

              if ($bolConteudoTextoBase) {

                $objVersaoSecaoDocumentoDTO->setStrConteudo($strConteudo);

              } else {

                if ($bolConteudoEdoc || $bolConteudoTextoPadrao || $bolConteudoSecaoPrincipal) {
                  $objVersaoSecaoDocumentoDTO->setStrConteudo($strConteudo);
                } else { //conteúdo inicial de seção (ex.: nome da base de conhecimento passada para a seção de título) ou conteúdo definido nas seções do modelo

                  if ($objSecaoModeloDTO->getStrSinHtml() == 'N') {

                    $strConteudoFormatado = '';
                    $arrConteudo = explode("\n", $strConteudo);
                    foreach ($arrConteudo as $strItemConteudo) {
                      $strConteudoFormatado .= '<p ' . $strEstiloPadrao . '>'.$strItemConteudo . '</p>' . "\r\n";
                    }

                    $objVersaoSecaoDocumentoDTO->setStrConteudo($strConteudoFormatado);

                  } else {
                    $objVersaoSecaoDocumentoDTO->setStrConteudo($strConteudo);
                  }
                }
              }
            }

            $objVersaoSecaoDocumentoDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
            $objVersaoSecaoDocumentoDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
            $objVersaoSecaoDocumentoDTO->setDthAtualizacao($dthAtual);
            $objVersaoSecaoDocumentoDTO->setNumVersao(1);

            $objVersaoSecaoDocumentoRN->cadastrar($objVersaoSecaoDocumentoDTO);
          }
        }
      } else {

        $objSecaoDocumentoDTO = new SecaoDocumentoDTO();
        $objSecaoDocumentoDTO->retStrNomeSecaoModelo();
        $objSecaoDocumentoDTO->retNumIdSecaoDocumento();
        $objSecaoDocumentoDTO->retNumIdSecaoModelo();
        $objSecaoDocumentoDTO->retNumOrdem();
        $objSecaoDocumentoDTO->retStrSinSomenteLeitura();
        $objSecaoDocumentoDTO->retStrSinAssinatura();
        $objSecaoDocumentoDTO->retStrSinPrincipal();
        $objSecaoDocumentoDTO->retStrSinDinamica();
        $objSecaoDocumentoDTO->retStrSinHtml();
        $objSecaoDocumentoDTO->retStrSinCabecalho();
        $objSecaoDocumentoDTO->retStrSinRodape();
        $objSecaoDocumentoDTO->retStrConteudo();

        if ($parObjEditorDTO->isSetDblIdDocumentoBase()) {
          $objSecaoDocumentoDTO->setDblIdDocumento($parObjEditorDTO->getDblIdDocumentoBase());
        }

        if ($parObjEditorDTO->isSetNumIdBaseConhecimentoBase()) {
          $objSecaoDocumentoDTO->setNumIdBaseConhecimento($parObjEditorDTO->getNumIdBaseConhecimentoBase());
        }

        $arrObjSecaoDocumentoDTOBase = $objSecaoDocumentoRN->listar($objSecaoDocumentoDTO);

        //bloquear registros de versão
        $objVersaoSecaoDocumentoDTO = new VersaoSecaoDocumentoDTO();
        $objVersaoSecaoDocumentoDTO->retDblIdVersaoSecaoDocumento();
        $objVersaoSecaoDocumentoDTO->retNumIdSecaoDocumento();
        $objVersaoSecaoDocumentoDTO->retStrConteudo();
        $objVersaoSecaoDocumentoDTO->setNumIdSecaoDocumento(InfraArray::converterArrInfraDTO($arrObjSecaoDocumentoDTOBase, 'IdSecaoDocumento'), InfraDTO::$OPER_IN);
        $objVersaoSecaoDocumentoDTO->setStrSinUltima('S');

        $arrObjVersaoSecaoDocumentoDTOBase = InfraArray::indexarArrInfraDTO($objVersaoSecaoDocumentoRN->listar($objVersaoSecaoDocumentoDTO), 'IdSecaoDocumento');

        //busca estilo padrao da secao
        $objRelSecaoModCjEstilosItemDTO = new RelSecaoModCjEstilosItemDTO();
        $objRelSecaoModCjEstilosItemDTO->retNumIdSecaoModelo();
        $objRelSecaoModCjEstilosItemDTO->retStrNomeEstilo();
        $objRelSecaoModCjEstilosItemDTO->setNumIdSecaoModelo(InfraArray::converterArrInfraDTO($arrObjSecaoDocumentoDTOBase, 'IdSecaoModelo'), InfraDTO::$OPER_IN);
        $objRelSecaoModCjEstilosItemDTO->setStrSinPadrao('S');
        $objRelSecaoModCjEstilosItemDTO->setNumIdConjuntoEstilos($parObjEditorDTO->getNumIdConjuntoEstilos());
        $objRelSecaoModCjEstilosItemRN = new RelSecaoModCjEstilosItemRN();
        $arrObjRelSecaoModCjEstilosItemDTO = InfraArray::indexarArrInfraDTO($objRelSecaoModCjEstilosItemRN->listar($objRelSecaoModCjEstilosItemDTO), 'IdSecaoModelo');

        foreach ($arrObjSecaoDocumentoDTOBase as $objSecaoDocumentoDTOBase) {

          $objSecaoDocumentoDTO = clone($objSecaoDocumentoDTOBase);
          $objSecaoDocumentoDTO->setDblIdDocumento($parObjEditorDTO->getDblIdDocumento());
          $objSecaoDocumentoDTO->setNumIdBaseConhecimento($parObjEditorDTO->getNumIdBaseConhecimento());
          $objSecaoDocumentoDTO = $objSecaoDocumentoRN->cadastrar($objSecaoDocumentoDTO);

          if ($objSecaoDocumentoDTOBase->getStrSinAssinatura()=='N') {

            $objVersaoSecaoDocumentoDTO = new VersaoSecaoDocumentoDTO();
            $objVersaoSecaoDocumentoDTO->setDblIdVersaoSecaoDocumento(null);
            $objVersaoSecaoDocumentoDTO->setNumIdSecaoDocumento($objSecaoDocumentoDTO->getNumIdSecaoDocumento());


            $strEstiloPadrao = '';
            if (isset($arrObjRelSecaoModCjEstilosItemDTO[$objSecaoDocumentoDTOBase->getNumIdSecaoModelo()])) {
              $strEstiloPadrao = 'class="' . $arrObjRelSecaoModCjEstilosItemDTO[$objSecaoDocumentoDTOBase->getNumIdSecaoModelo()]->getStrNomeEstilo() . '"';
            }

            //conteúdo informado especificamente para esta seção
            if ($arrConteudoInicalSecoes!=null && isset($arrConteudoInicalSecoes[$objSecaoDocumentoDTOBase->getStrNomeSecaoModelo()])) {

              $strConteudo = $arrConteudoInicalSecoes[$objSecaoDocumentoDTOBase->getStrNomeSecaoModelo()];

              if ($objSecaoDocumentoDTOBase->getStrSinHtml()=='N') {
                $strConteudoFormatado = '';
                $arrConteudo = explode("\n", $strConteudo);
                foreach ($arrConteudo as $strItemConteudo) {
                  $strConteudoFormatado .= '<p ' . $strEstiloPadrao . '>'.$strItemConteudo . '</p>' . "\r\n";
                }
                $strConteudo = $strConteudoFormatado;
              }

            } else if ($objSecaoDocumentoDTOBase->getStrSinSomenteLeitura()=='S' || $objSecaoDocumentoDTOBase->getStrSinDinamica()=='S') {

              $strConteudo = $objSecaoDocumentoDTOBase->getStrConteudo();

              $strConteudo=$this->substituirTagsInterno($parObjEditorDTO, $strConteudo);

              if ($objSecaoDocumentoDTOBase->getStrSinHtml()=='N') {
                $strConteudoFormatado = '';
                $arrConteudo = explode("\n", $strConteudo);
                foreach ($arrConteudo as $strItemConteudo) {
                  $strConteudoFormatado .= '<p ' . $strEstiloPadrao . '>'. $strItemConteudo . '</p>' . "\r\n";
                }
                $strConteudo = $strConteudoFormatado;
              }

            } else {
              $strConteudo = $this->substituirTagsInterno($parObjEditorDTO, $arrObjVersaoSecaoDocumentoDTOBase[$objSecaoDocumentoDTOBase->getNumIdSecaoDocumento()]->getStrConteudo());
            }

            $objVersaoSecaoDocumentoDTO->setStrConteudo($strConteudo);
            $objVersaoSecaoDocumentoDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
            $objVersaoSecaoDocumentoDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
            $objVersaoSecaoDocumentoDTO->setDthAtualizacao($dthAtual);
            $objVersaoSecaoDocumentoDTO->setNumVersao(1);

            $objVersaoSecaoDocumentoRN->cadastrar($objVersaoSecaoDocumentoDTO);
          }
        }
      }

      //cadastrar conjunto de estilos
      $this->atualizarConteudo($parObjEditorDTO);

    } catch (Exception $e) {
      throw new InfraException('Módulo do Tramita: Erro gerando versão inicial documento.', $e);
    }
  }

  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
  protected function adicionarVersaoControlado(EditorDTO $parObjEditorDTO)
  {
    try {
      $objInfraException = new InfraException();

      if ($parObjEditorDTO->getDblIdDocumento()!=null) {

        $objDocumentoDTO = new DocumentoDTO();
        $objDocumentoDTO->retDblIdProcedimento();
        $objDocumentoDTO->retNumIdConjuntoEstilos();
        $objDocumentoDTO->setDblIdDocumento($parObjEditorDTO->getDblIdDocumento());

        $objDocumentoRN = new DocumentoRN();
        $objDocumentoDTO = $objDocumentoRN->consultarRN0005($objDocumentoDTO);

        if ($objDocumentoDTO==null) {
          $objInfraException->lancarValidacao('Documento não encontrado.');
        } else {

          $numIdConjuntoEstilos = $objDocumentoDTO->getNumIdConjuntoEstilos();

          $objPesquisaProtocoloDTO = new PesquisaProtocoloDTO();
          $objPesquisaProtocoloDTO->setStrStaTipo(ProtocoloRN::$TPP_DOCUMENTOS_GERADOS);
          $objPesquisaProtocoloDTO->setStrStaAcesso(ProtocoloRN::$TAP_TODOS);
          $objPesquisaProtocoloDTO->setDblIdProtocolo($parObjEditorDTO->getDblIdDocumento());

          $objProtocoloRN = new ProtocoloRN();
          $arrObjProtocoloDTO = $objProtocoloRN->pesquisarRN0967($objPesquisaProtocoloDTO);

          if (count($arrObjProtocoloDTO) == 0){
            $objInfraException->lancarValidacao('Protocolo não encontrado.');
          }

          $objProtocoloDTO = $arrObjProtocoloDTO[0];

          if ($objProtocoloDTO->getNumCodigoAcesso() < 0) {
            if ($objProtocoloDTO->getStrStaNivelAcessoGlobal()==ProtocoloRN::$NA_SIGILOSO) {
              $objInfraException->lancarValidacao('Usuário sem acesso para alteração do documento.');
            }else{
              $objInfraException->lancarValidacao('Unidade sem acesso para alteração do documento.');
            }
          }

          if ($objProtocoloDTO->getNumIdUnidadeGeradora()==SessaoSEI::getInstance()->getNumIdUnidadeAtual()) {
            $objAtividadeDTO = new AtividadeDTO();
            $objAtividadeDTO->setDblIdProtocolo($objDocumentoDTO->getDblIdProcedimento());
            $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
            $objAtividadeDTO->setDthConclusao(null);
            $objAtividadeDTO->setNumMaxRegistrosRetorno(1);

            $objAtividadeRN = new AtividadeRN();
            if ($objAtividadeRN->contarRN0035($objAtividadeDTO) == 0) {
              $objInfraException->lancarValidacao('Processo não está aberto na unidade.');
            }
          }

          $objProcedimentoDTO = new ProcedimentoDTO();
          $objProcedimentoDTO->setDblIdProcedimento($objProtocoloDTO->getDblIdProcedimentoDocumento());
          $objProcedimentoDTO->setStrSinDocTodos('S');
          $objProcedimentoDTO->setArrDblIdProtocoloAssociado(array($parObjEditorDTO->getDblIdDocumento()));

          $objProcedimentoRN = new ProcedimentoRN();
          $arrObjProcedimentoDTO = $objProcedimentoRN->listarCompleto($objProcedimentoDTO);

          if (count($arrObjProcedimentoDTO) == 0){
            $objInfraException->lancarValidacao('Processo não encontrado.');
          }

          $objProcedimentoDTO = $arrObjProcedimentoDTO[0];

          $objProcedimentoRN->verificarEstadoProcedimento($objProcedimentoDTO);

          $arrObjRelProtocoloProtocoloDTO = $objProcedimentoDTO->getArrObjRelProtocoloProtocoloDTO();

          if (count($arrObjRelProtocoloProtocoloDTO) == 0){
            $objInfraException->lancarValidacao('Documento não encontrado.');
          }

          $objDocumentoDTO = $arrObjRelProtocoloProtocoloDTO[0]->getObjProtocoloDTO2();

          if ($objDocumentoDTO->getStrSinPublicado() == 'S'){
            $objInfraException->lancarValidacao('Documento foi publicado.');
          }

          if ($objDocumentoDTO->getStrSinBloqueado() == 'S'){
            $objInfraException->lancarValidacao('Documento foi assinado e não pode mais ser alterado.');
          }

          if (SessaoSEI::getInstance()->getNumIdUnidadeAtual() != $objDocumentoDTO->getNumIdUnidadeGeradoraProtocolo()){

            if ($objDocumentoDTO->getStrSinAssinadoPorOutraUnidade() == 'S') {
              $objInfraException->lancarValidacao('Documento foi assinado em outra unidade.');
            }

          }else {

            if ((!$parObjEditorDTO->isSetStrSinMontandoEditor() || $parObjEditorDTO->getStrSinMontandoEditor()=='N') && $objProtocoloDTO->getStrSinAssinado() == 'S'){
              $objInfraException->lancarValidacao('Documento foi assinado.');
            }

            if ($objProtocoloDTO->getStrSinDisponibilizadoParaOutraUnidade() == 'S'){
              $objInfraException->lancarValidacao('Documento disponibilizado em bloco de assinatura.');
            }

          }

          if ($objDocumentoDTO->getStrSinAssinado() == 'S') {
            $parObjEditorDTO->setStrSinForcarNovaVersao('S');
          }

          if ($numIdConjuntoEstilos==null || ($parObjEditorDTO->isSetStrSinForcarNovaVersao() && $parObjEditorDTO->getStrSinForcarNovaVersao()=='S')){
            $this->converterDocumento($parObjEditorDTO);
          } else {
            $parObjEditorDTO->setNumIdConjuntoEstilos($numIdConjuntoEstilos);
          }
        }

      } else {
        $objBaseConhecimentoDTO = new BaseConhecimentoDTO();
        $objBaseConhecimentoDTO->setNumIdBaseConhecimento($parObjEditorDTO->getNumIdBaseConhecimento());
        $objBaseConhecimentoDTO->retNumIdConjuntoEstilos();

        $objBaseConhecimentoRN = new BaseConhecimentoRN();
        $objBaseConhecimentoDTO = $objBaseConhecimentoRN->consultar($objBaseConhecimentoDTO);
        if ($objBaseConhecimentoDTO==null) {
          $objInfraException->lancarValidacao('Base de conhecimento não encontrada.');
        } else {
          if ($objBaseConhecimentoDTO->getNumIdConjuntoEstilos()==null ||
              ($parObjEditorDTO->isSetStrSinForcarNovaVersao() && $parObjEditorDTO->getStrSinForcarNovaVersao()=='S')
          ) {
            $this->converterDocumento($parObjEditorDTO);
          } else {
            $parObjEditorDTO->setNumIdConjuntoEstilos($objBaseConhecimentoDTO->getNumIdConjuntoEstilos());
          }
        }
      }

      $dthAtual = InfraData::getStrDataHoraAtual();

      $arrObjSecaoDocumentoDTO = $parObjEditorDTO->getArrObjSecaoDocumentoDTO();

      if (count($arrObjSecaoDocumentoDTO)==0) {
        throw new InfraException('Módulo do Tramita: Documento sem seções.');
      }

      $objSecaoDocumentoDTO = new SecaoDocumentoDTO();
      $objSecaoDocumentoDTO->retNumIdSecaoDocumento();
      $objSecaoDocumentoDTO->retNumIdSecaoModelo();
      $objSecaoDocumentoDTO->retStrSinDinamica();
      $objSecaoDocumentoDTO->retStrSinSomenteLeitura();
      $objSecaoDocumentoDTO->retStrSinHtml();
      $objSecaoDocumentoDTO->retStrSinCabecalho();
      $objSecaoDocumentoDTO->retStrSinRodape();
      $objSecaoDocumentoDTO->retStrConteudo();
      $objSecaoDocumentoDTO->setDblIdDocumento($parObjEditorDTO->getDblIdDocumento());
      $objSecaoDocumentoDTO->setNumIdBaseConhecimento($parObjEditorDTO->getNumIdBaseConhecimento());
      $objSecaoDocumentoDTO->setStrSinAssinatura('N');

      $objSecaoDocumentoRN = new SecaoDocumentoRN();
      $arrObjSecaoDocumentoDTOBanco = $objSecaoDocumentoRN->listar($objSecaoDocumentoDTO);

      $numSecoesDocumento = count($arrObjSecaoDocumentoDTO);
      $numSecoesDocumentoBanco = count($arrObjSecaoDocumentoDTOBanco);

      if ($numSecoesDocumentoBanco!=$numSecoesDocumento) {
        throw new InfraException('Módulo do Tramita: Número de seções do documento inconsistente.');
      }

      for ($i = 0; $i<$numSecoesDocumentoBanco; $i++) {
        for ($j = 0; $j<$numSecoesDocumento; $j++) {
          if ($arrObjSecaoDocumentoDTOBanco[$i]->getNumIdSecaoModelo()==$arrObjSecaoDocumentoDTO[$j]->getNumIdSecaoModelo()) {
            $arrObjSecaoDocumentoDTO[$j]->setNumIdSecaoDocumento($arrObjSecaoDocumentoDTOBanco[$i]->getNumIdSecaoDocumento());
            $arrObjSecaoDocumentoDTO[$j]->setStrSinDinamica($arrObjSecaoDocumentoDTOBanco[$i]->getStrSinDinamica());
            $arrObjSecaoDocumentoDTO[$j]->setStrSinSomenteLeitura($arrObjSecaoDocumentoDTOBanco[$i]->getStrSinSomenteLeitura());
            $arrObjSecaoDocumentoDTO[$j]->setStrSinHtml($arrObjSecaoDocumentoDTOBanco[$i]->getStrSinHtml());
            $arrObjSecaoDocumentoDTO[$j]->setStrSinCabecalho($arrObjSecaoDocumentoDTOBanco[$i]->getStrSinCabecalho());
            $arrObjSecaoDocumentoDTO[$j]->setStrSinRodape($arrObjSecaoDocumentoDTOBanco[$i]->getStrSinRodape());
            $arrObjSecaoDocumentoDTO[$j]->setStrConteudoOriginal($arrObjSecaoDocumentoDTOBanco[$i]->getStrConteudo());
            break;
          }
        }
        if ($j==$numSecoesDocumento) {
          throw new InfraException('Módulo do Tramita: Seção [' . $arrObjSecaoDocumentoDTOBanco[$i]->getNumIdSecaoModelo() . '] do documento não encontrada.');
        }
      }

      //bloquear registros de versão
      $objVersaoSecaoDocumentoDTO = new VersaoSecaoDocumentoDTO();
      $objVersaoSecaoDocumentoDTO->retDblIdVersaoSecaoDocumento();
      $objVersaoSecaoDocumentoDTO->retNumIdSecaoDocumento();
      $objVersaoSecaoDocumentoDTO->retStrSiglaUsuario();
      $objVersaoSecaoDocumentoDTO->retStrNomeUsuario();
      $objVersaoSecaoDocumentoDTO->retDthAtualizacao();
      $objVersaoSecaoDocumentoDTO->retStrConteudo();
      $objVersaoSecaoDocumentoDTO->retNumVersao();
      $objVersaoSecaoDocumentoDTO->setNumIdSecaoDocumento(InfraArray::converterArrInfraDTO($arrObjSecaoDocumentoDTOBanco, 'IdSecaoDocumento'), InfraDTO::$OPER_IN);
      $objVersaoSecaoDocumentoDTO->setStrSinUltima('S');

      $objVersaoSecaoDocumentoRN = new VersaoSecaoDocumentoRN();

      $arrObjVersaoSecaoDocumentoDTO = $objVersaoSecaoDocumentoRN->listar($objVersaoSecaoDocumentoDTO);

      $numVersao = 0;
      $objVersaoSecaoDocumentoDTOUltima = null;
      foreach ($arrObjVersaoSecaoDocumentoDTO as $dto) {
        if ($dto->getNumVersao()>$numVersao) {
          $numVersao = $dto->getNumVersao();
          $objVersaoSecaoDocumentoDTOUltima = $dto;
        }
      }

      if (count($arrObjVersaoSecaoDocumentoDTO)!=$numSecoesDocumento) {
        throw new InfraException('Módulo do Tramita: Número de seções da última versão não corresponde ao número de seções do documento.');
      }

      if ($parObjEditorDTO->isSetNumVersao() && $parObjEditorDTO->getNumVersao()!=$numVersao) {
        if (!$parObjEditorDTO->isSetStrSinIgnorarNovaVersao() || $parObjEditorDTO->getStrSinIgnorarNovaVersao()=='N') {
          //IMPORTANTE: o texto da validacao é verificado na interface, se houver mudança deve ser refletida no ponto correspondente da interface
          $objInfraException->lancarValidacao('Existe uma nova versão (nº ' . $numVersao . ') para este documento atualizada por ' . $objVersaoSecaoDocumentoDTOUltima->getStrSiglaUsuario() . ' (' . $objVersaoSecaoDocumentoDTOUltima->getStrNomeUsuario() . ') em ' . $objVersaoSecaoDocumentoDTOUltima->getDthAtualizacao() . '.');
        }
      }


      //aplica estilo padrao da secao
      $objRelSecaoModCjEstilosItemDTO = new RelSecaoModCjEstilosItemDTO();
      $objRelSecaoModCjEstilosItemDTO->retNumIdSecaoModelo();
      $objRelSecaoModCjEstilosItemDTO->retStrNomeEstilo();
      $objRelSecaoModCjEstilosItemDTO->setNumIdSecaoModelo(InfraArray::converterArrInfraDTO($arrObjSecaoDocumentoDTO, 'IdSecaoModelo'), InfraDTO::$OPER_IN);
      $objRelSecaoModCjEstilosItemDTO->setStrSinPadrao('S');
      $objRelSecaoModCjEstilosItemDTO->setNumIdConjuntoEstilos($parObjEditorDTO->getNumIdConjuntoEstilos());
      $objRelSecaoModCjEstilosItemRN = new RelSecaoModCjEstilosItemRN();
      $arrObjRelSecaoModCjEstilosItemDTO = InfraArray::indexarArrInfraDTO($objRelSecaoModCjEstilosItemRN->listar($objRelSecaoModCjEstilosItemDTO), 'IdSecaoModelo');

      $arrEstilosFormatados = array();
      foreach ($arrObjRelSecaoModCjEstilosItemDTO as $objRelSecaoModCjEstilosItemDTO) {
        $strEstiloFormatado = 'class="' . $objRelSecaoModCjEstilosItemDTO->getStrNomeEstilo() . '"';
        $arrEstilosFormatados[$objRelSecaoModCjEstilosItemDTO->getNumIdSecaoModelo()] = $strEstiloFormatado;
      }

      $bolSecaoAlterada = false;

      $objImagemFormatoDTO = new ImagemFormatoDTO();
      $objImagemFormatoDTO->retStrFormato();
      $objImagemFormatoDTO->setBolExclusaoLogica(false);
      $objImagemFormatoRN = new ImagemFormatoRN();
      $arrImagemPermitida = InfraArray::converterArrInfraDTO($objImagemFormatoRN->listar($objImagemFormatoDTO), 'Formato');
      if (in_array('jpg', $arrImagemPermitida) && !in_array('jpeg', $arrImagemPermitida)) { $arrImagemPermitida[] = 'jpeg';
      }

      foreach ($arrObjSecaoDocumentoDTO as $objSecaoDocumentoDTO) {
        foreach ($arrObjVersaoSecaoDocumentoDTO as $objVersaoSecaoDocumentoDTO) {
          if ($objSecaoDocumentoDTO->getNumIdSecaoDocumento()==$objVersaoSecaoDocumentoDTO->getNumIdSecaoDocumento()) {

            $strConteudo = $this->montarConteudoSecao($objSecaoDocumentoDTO, $arrEstilosFormatados, $numVersao, $parObjEditorDTO);
            $strConteudo=$this->processarLinksSei($strConteudo);
            $strConteudo = self::converterHTML($strConteudo);

            if ($objSecaoDocumentoDTO->getStrSinCabecalho()=='N' && $objSecaoDocumentoDTO->getStrSinRodape()=='N') {
              $this->validarTagsCriticas($arrImagemPermitida, $strConteudo, $parObjEditorDTO->getDblIdDocumento());
            }

            if ($strConteudo!=$objVersaoSecaoDocumentoDTO->getStrConteudo()) {
              $bolSecaoAlterada = true;
            }

            break;
          }
        }
      }
      if ($bolSecaoAlterada || ($parObjEditorDTO->isSetStrSinForcarNovaVersao() && $parObjEditorDTO->getStrSinForcarNovaVersao()=='S')) {
        $numVersao++;
        foreach ($arrObjSecaoDocumentoDTO as $objSecaoDocumentoDTO) {
          foreach ($arrObjVersaoSecaoDocumentoDTO as $objVersaoSecaoDocumentoDTO) {
            if ($objSecaoDocumentoDTO->getNumIdSecaoDocumento()==$objVersaoSecaoDocumentoDTO->getNumIdSecaoDocumento()) {

              $strConteudo = $this->montarConteudoSecao($objSecaoDocumentoDTO, $arrEstilosFormatados, $numVersao, $parObjEditorDTO);
              $strConteudo=$this->processarLinksSei($strConteudo);
              $strConteudo = self::converterHTML($strConteudo);

              if ($strConteudo!=$objVersaoSecaoDocumentoDTO->getStrConteudo()) {
                $objVersaoSecaoDocumentoRN->anular($objVersaoSecaoDocumentoDTO);
                $dto = new VersaoSecaoDocumentoDTO();
                $dto->setNumIdSecaoDocumento($objSecaoDocumentoDTO->getNumIdSecaoDocumento());
                $dto->setStrConteudo($strConteudo);
                $dto->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
                $dto->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
                $dto->setDthAtualizacao($dthAtual);
                $dto->setNumVersao($numVersao);
                $objVersaoSecaoDocumentoRN->cadastrar($dto);
              }

              break;
            }
          }
        }
        $this->atualizarConteudo($parObjEditorDTO);
      }
      /*
      else{
        if ($_GET['acao']=='editor_salvar'){
          LogSEI::getInstance()->gravar('Nenhuma alteração foi encontrada no conteúdo do documento: '.$parObjEditorDTO->getDblIdDocumento().' ['.SessaoSEI::getInstance()->getStrSiglaUsuario().'/'.SessaoSEI::getInstance()->getStrSiglaUnidadeAtual().']');
        }
      }
      */

      return $numVersao;

    } catch (Exception $e) {
      throw new InfraException('Módulo do Tramita: Erro adicionando versão do documento.', $e);
    }
  }

  protected function getArrayCssConectado($numIdConjuntoEstilos)
  {
    /*
     * transforma o conjunto de estilos em um array
     * p.Texto_Centralizado {font-size:1;text-align:center;}
     *
     * -> array { [Texto_Centralizado] => array {
     *                    [font-size] => "1"
     *                    [text-align] => "center"
     *                    }
     *          }
     */
    $strCss = $this->montarCssEditor($numIdConjuntoEstilos);
    //seleciona classes p.[nome_estilo]
    preg_match_all("%p\\.([^\\s]*) {([^}]*)}%", $strCss, $arrClassesCss);

    $arrResult = array();
    //para cada classe css
    for ($i = 0; $i<count($arrClassesCss[1]); $i++) {
      //cria item no array de resultado com nome da classe css
      $arrResult[$arrClassesCss[1][$i]] = array();
      //explode os atributos da classe (estilos)
      $arrEstilos = explode(';', $arrClassesCss[2][$i]);
      foreach ($arrEstilos as $value) {
        //se não for vazio
        if (strlen($value)>0) {
          $arrValor = explode(':', $value);
          //inclui no arrResult[nome_do_estilo][atributo]=valor_atributo;
          if ($arrValor[1]=='0 3pt 0 3pt') { $arrValor[1] = '0px 3pt';
          }
          $arrResult[$arrClassesCss[1][$i]][trim($arrValor[0])] = InfraString::transformarCaixaBaixa(trim($arrValor[1]));
        }
      }
    }
    return $arrResult;
  }

  private function comparaEstilo($arrEstilos, $strEstilo)
  {
    // verificar se strestilo está definida em arrestilos
    $strEstilo = str_replace(' 0px', ' 0', $strEstilo);
    $strEstilo = str_replace(' 0pt', ' 0', $strEstilo);
    $arrEstilos2 = array();
    $temp = explode(';', $strEstilo);
    foreach ($temp as $value) {
      //se não for vazio
      if (strlen($value)>0) {
        $arrValor = explode(':', $value);
        //inclui no arrEstilos2[atributo]=valor_atributo;
        if ($arrValor[1]=='0 3pt 0 3pt') { $arrValor[1] = '0 3pt';
        }
        $arrEstilos2[InfraString::transformarCaixaBaixa(trim($arrValor[0]))] = InfraString::transformarCaixaBaixa(trim($arrValor[1]));
      }
    }
    $numEstilos2 = count($arrEstilos2);
    //verifica se tem atributos definidos

    if ($numEstilos2>0) {
      //compara com todos os estilos do arrEstilos
      foreach ($arrEstilos as $key => $value) {
        if (!is_array($value[0])) {
          //se tiver mesma quantidade de atributos
          if (count($value)==$numEstilos2) {
            //compara as diferenças, que devem ser 0
            if (count(array_diff_assoc($value, $arrEstilos2))==0 &&
                count(array_diff_assoc($arrEstilos2, $value))==0
            ) {
              return $key;
            }
          }
        } else {
          foreach ($value as $value2) {

            //se tiver mesma quantidade de atributos
            if (count($value2)==$numEstilos2) {
              //compara as diferenças, que devem ser 0
              if (count(array_diff_assoc($value2, $arrEstilos2))==0 &&
                  count(array_diff_assoc($arrEstilos2, $value2))==0
              ) {
                return $key;
              }
            }
          }
        }
      }
    }
    return null;
  }

  private function converterDocumento(EditorDTO $parObjEditorDTO)
  {
    try {
      if ($parObjEditorDTO->isSetNumIdConjuntoEstilos() && $parObjEditorDTO->getNumIdConjuntoEstilos()!=null) {
        $arrEstilos = $this->getArrayCss($parObjEditorDTO->getNumIdConjuntoEstilos());
      } else {
        $objConjuntoEstilosRN = new ConjuntoEstilosRN();
        $objConjuntoEstilosDTO = new ConjuntoEstilosDTO();
        $objConjuntoEstilosDTO->setStrSinUltimo('S');
        $objConjuntoEstilosDTO->retNumIdConjuntoEstilos();
        $objConjuntoEstilosDTO = $objConjuntoEstilosRN->consultar($objConjuntoEstilosDTO);
        if ($objConjuntoEstilosDTO==null) { throw new InfraException('Módulo do Tramita: Erro consultando conjunto de estilos.');
        }
        $arrEstilos = $this->getArrayCss($objConjuntoEstilosDTO->getNumIdConjuntoEstilos());
        $parObjEditorDTO->setNumIdConjuntoEstilos($objConjuntoEstilosDTO->getNumIdConjuntoEstilos());
      }
      $arrObjSecaoDocumentoDTO = $parObjEditorDTO->getArrObjSecaoDocumentoDTO();
      foreach ($arrObjSecaoDocumentoDTO as $objSecaoDocumentoDTO) {
        //converter seção_documento
        $strConteudo = $objSecaoDocumentoDTO->getStrConteudo();
        $objSecaoDocumentoDTO->setStrConteudo($this->converteTextoEstiloCss($arrEstilos, $strConteudo));
        ///////
      }
    } catch (Exception $e) {
      throw new InfraException('Módulo do Tramita: Erro convertendo documento.', $e);
    }

    if ($parObjEditorDTO->getDblIdDocumento()!=null) {
      $objDocumentoRN = new DocumentoRN();
      $objDocumentoDTO = new DocumentoDTO();
      $objDocumentoDTO->setDblIdDocumento($parObjEditorDTO->getDblIdDocumento());
      $objDocumentoDTO->setNumIdConjuntoEstilos($parObjEditorDTO->getNumIdConjuntoEstilos());
      $objDocumentoRN->configurarEstilos($objDocumentoDTO);
    } else if ($parObjEditorDTO->getNumIdBaseConhecimento()!=null) {
      $objBaseConhecimentoRN = new BaseConhecimentoRN();
      $objBaseConhecimentoDTO = new BaseConhecimentoDTO();
      $objBaseConhecimentoDTO->setNumIdBaseConhecimento($parObjEditorDTO->getNumIdBaseConhecimento());
      $objBaseConhecimentoDTO->setNumIdConjuntoEstilos($parObjEditorDTO->getNumIdConjuntoEstilos());
      $objBaseConhecimentoRN->configurarEstilos($objBaseConhecimentoDTO);
    }
  }

  public function converteTextoEstiloCss($arrEstilosCss, $strConteudo)
  {

    $strConteudoNovo = "";
    $posAnterior = 0;
    $cntNaoEncontrados = 0;
    $cntEncontrados = 0;
    while (($posAtual = strpos($strConteudo, 'style="', $posAnterior))!==false) {
      //copia conteudo até encontrar style
      $strConteudoNovo .= substr($strConteudo, $posAnterior, $posAtual - $posAnterior);
      $posFimEstilo = strpos($strConteudo, '"', $posAtual + 7);
      if ($posFimEstilo===false) {
        throw new InfraException('Módulo do Tramita: Erro localizando fim do estilo.');
      } else if ($posFimEstilo==$posAtual + 7) {
        $posAnterior = $posAtual + 8;
      } else {
        $strEstilo = substr($strConteudo, $posAtual + 7, $posFimEstilo - $posAtual - 7);
        $nomeClasse = $this->comparaEstilo($arrEstilosCss, $strEstilo);
        if ($nomeClasse==null) {
          $cntNaoEncontrados++;
          $posAnterior = $posAtual + 1;
          $strConteudoNovo .= 's';
          //InfraDebug::getInstance()->gravar("Nao encontrado estilo para: /".$strEstilo."/");
        } else {
          $posAnterior = $posFimEstilo + 1;
          $cntEncontrados++;
          $strConteudoNovo .= 'class="' . $nomeClasse . '"';
        }
      }
    }
    $strConteudoNovo .= substr($strConteudo, $posAnterior);
    //InfraDebug::getInstance()->gravar("Conversão: encontrados ".strval($cntEncontrados)." não encontrados ".strval($cntNaoEncontrados));
    return $strConteudoNovo;

  }

  private function montarConteudoSecao($objSecaoDocumentoDTO, $arrEstilosFormatados, $numVersao, $parObjEditorDTO)
  {

    self::$arrTags['versao']=$numVersao;
    $strConteudo = '';
    $strEstiloPadrao = '';
    if (isset($arrEstilosFormatados[$objSecaoDocumentoDTO->getNumIdSecaoModelo()])) {
      $strEstiloPadrao = $arrEstilosFormatados[$objSecaoDocumentoDTO->getNumIdSecaoModelo()];
    }

    if ($objSecaoDocumentoDTO->getStrSinDinamica()=='S') {

      $strConteudo = $objSecaoDocumentoDTO->getStrConteudoOriginal();

      $strConteudo=$this->substituirTagsInterno($parObjEditorDTO, $strConteudo);

      if ($objSecaoDocumentoDTO->getStrSinSomenteLeitura()=='S') {
        if (trim($strConteudo)!='' && $objSecaoDocumentoDTO->getStrSinHtml()=='N') {
          $strConteudo = '<p ' . $strEstiloPadrao . '>'.$strConteudo . '</p>' . "\r\n";
        }
      }

    } else {
      $strConteudo = $objSecaoDocumentoDTO->getStrConteudo();
      if (trim($strConteudo)=='' && $objSecaoDocumentoDTO->getStrSinSomenteLeitura()=='N') {
        $strConteudo = '<p ' . $strEstiloPadrao . '>'.'&nbsp;</p>' . "\r\n";
      }
    }

    return $strConteudo;
  }

  private function atualizarConteudo(EditorDTO $parObjEditorDTO)
  {
    try {

      $objEditorDTO = new EditorDTO();
      $objEditorDTO->setDblIdDocumento($parObjEditorDTO->getDblIdDocumento());
      $objEditorDTO->setNumIdBaseConhecimento($parObjEditorDTO->getNumIdBaseConhecimento());
      $objEditorDTO->setStrSinCabecalho('N');
      $objEditorDTO->setStrSinRodape('N');
      $objEditorDTO->setStrSinCarimboPublicacao('N');
      $objEditorDTO->setStrSinIdentificacaoVersao('N');
      $objEditorDTO->setNumIdConjuntoEstilos($parObjEditorDTO->getNumIdConjuntoEstilos());

      $strHtml = $this->consultarHtmlVersao($objEditorDTO);

      if ($parObjEditorDTO->getDblIdDocumento()!=null) {

        $objDocumentoDTO = new DocumentoDTO();
        $objDocumentoDTO->setStrConteudo($strHtml);
        $objDocumentoDTO->setDblIdDocumento($parObjEditorDTO->getDblIdDocumento());

        $objDocumentoRN = new DocumentoRN();
        $objDocumentoRN->atualizarConteudoRN1205($objDocumentoDTO);

      } else {

        $objBaseConhecimentoDTO = new BaseConhecimentoDTO();
        $objBaseConhecimentoDTO->setStrConteudo($strHtml);
        $objBaseConhecimentoDTO->setNumIdBaseConhecimento($parObjEditorDTO->getNumIdBaseConhecimento());

        $objBaseConhecimentoRN = new BaseConhecimentoRN();
        $objBaseConhecimentoRN->alterar($objBaseConhecimentoDTO);
      }

    } catch (Exception $e) {
      throw new InfraException('Módulo do Tramita: Erro atualizando conteúdo.', $e);
    }
  }

  private function consultarHtmlIdentificacaoVersao(EditorDTO $parObjEditorDTO)
  {

    $objVersaoSecaoDocumentoDTO = new VersaoSecaoDocumentoDTO();
    $objVersaoSecaoDocumentoDTO->setDistinct(true);
    $objVersaoSecaoDocumentoDTO->retNumVersao();
    $objVersaoSecaoDocumentoDTO->retStrSiglaUsuario();
    $objVersaoSecaoDocumentoDTO->retStrNomeUsuario();
    $objVersaoSecaoDocumentoDTO->retDthAtualizacao();

    $objVersaoSecaoDocumentoDTO->setDblIdDocumentoSecaoDocumento($parObjEditorDTO->getDblIdDocumento());
    $objVersaoSecaoDocumentoDTO->setNumIdBaseConhecimentoSecaoDocumento($parObjEditorDTO->getNumIdBaseConhecimento());

    if ($parObjEditorDTO->isSetNumVersao()) {
      $objVersaoSecaoDocumentoDTO->setNumVersao($parObjEditorDTO->getNumVersao(), InfraDTO::$OPER_MENOR_IGUAL);
    }

    $objVersaoSecaoDocumentoDTO->setOrdNumVersao(InfraDTO::$TIPO_ORDENACAO_ASC);


    $objVersaoSecaoDocumentoRN = new VersaoSecaoDocumentoRN();
    $arrObjVersaoSecaoDocumentoDTO = $objVersaoSecaoDocumentoRN->listar($objVersaoSecaoDocumentoDTO);

    $qtdVersoes = count($arrObjVersaoSecaoDocumentoDTO);
    $numVersao = 0;
    if ($qtdVersoes) {
      $strSiglaUsuarioGerador = $arrObjVersaoSecaoDocumentoDTO[0]->getStrSiglaUsuario();
      $strNomeUsuarioGerador = $arrObjVersaoSecaoDocumentoDTO[0]->getStrNomeUsuario();

      $strSiglaUsuarioVersao = $arrObjVersaoSecaoDocumentoDTO[$qtdVersoes - 1]->getStrSiglaUsuario();
      $strNomeUsuarioVersao = $arrObjVersaoSecaoDocumentoDTO[$qtdVersoes - 1]->getStrNomeUsuario();
      $numVersao = $arrObjVersaoSecaoDocumentoDTO[$qtdVersoes - 1]->getNumVersao();
      $dthVersao = $arrObjVersaoSecaoDocumentoDTO[$qtdVersoes - 1]->getDthAtualizacao();
    }

    /*
    $html = '<hr style="border:1px solid #c0c0c0;" />';
    $html .= 'Criado por ';
    $html .= '<a onclick="alert(\'' . PaginaSEI::getInstance()->formatarParametrosJavaScript($strNomeUsuarioGerador) . '\')" alt="' . $strNomeUsuarioGerador . '" title="' . $strNomeUsuarioGerador . '" style="color:#0066cc;text-decoration:none;cursor:pointer;">' . $strSiglaUsuarioGerador . '</a>';
    $html .= ', versão ' . $numVersao . ' por ';
    $html .= '<a onclick="alert(\'' . PaginaSEI::getInstance()->formatarParametrosJavaScript($strNomeUsuarioVersao) . '\')" alt="' . $strNomeUsuarioVersao . '" title="' . $strNomeUsuarioVersao . '" style="color:#0066cc;text-decoration:none;cursor:pointer;">' . $strSiglaUsuarioVersao . '</a>';
    $html .= ' em ' . $dthVersao . '.' . "\n";
    */

    $html = '<hr style="border:1px solid #c0c0c0;" />';
    $html .= 'Criado por  '. $strSiglaUsuarioGerador . ', versão ' . $numVersao . ' por '.$strSiglaUsuarioVersao . ' em ' . $dthVersao . '.' . "\n";

    return $html;
  }

  private function consultarHtmlIdentificacaoVersao3015(EditorDTO $parObjEditorDTO)
  {

    $objVersaoSecaoDocumentoDTO = new VersaoSecaoDocumentoDTO();
    $objVersaoSecaoDocumentoDTO->setDistinct(true);
    $objVersaoSecaoDocumentoDTO->retNumVersao();
    $objVersaoSecaoDocumentoDTO->retStrSiglaUsuario();
    $objVersaoSecaoDocumentoDTO->retStrNomeUsuario();
    $objVersaoSecaoDocumentoDTO->retDthAtualizacao();

    $objVersaoSecaoDocumentoDTO->setDblIdDocumentoSecaoDocumento($parObjEditorDTO->getDblIdDocumento());
    $objVersaoSecaoDocumentoDTO->setNumIdBaseConhecimentoSecaoDocumento($parObjEditorDTO->getNumIdBaseConhecimento());

    if ($parObjEditorDTO->isSetNumVersao()) {
      $objVersaoSecaoDocumentoDTO->setNumVersao($parObjEditorDTO->getNumVersao(), InfraDTO::$OPER_MENOR_IGUAL);
    }

    $objVersaoSecaoDocumentoDTO->setOrdNumVersao(InfraDTO::$TIPO_ORDENACAO_ASC);


    $objVersaoSecaoDocumentoRN = new VersaoSecaoDocumentoRN();
    $arrObjVersaoSecaoDocumentoDTO = $objVersaoSecaoDocumentoRN->listar($objVersaoSecaoDocumentoDTO);

    $qtdVersoes = count($arrObjVersaoSecaoDocumentoDTO);
    $numVersao = 0;
    if ($qtdVersoes) {
      $strSiglaUsuarioGerador = $arrObjVersaoSecaoDocumentoDTO[0]->getStrSiglaUsuario();
      $strNomeUsuarioGerador = $arrObjVersaoSecaoDocumentoDTO[0]->getStrNomeUsuario();

      $strSiglaUsuarioVersao = $arrObjVersaoSecaoDocumentoDTO[$qtdVersoes - 1]->getStrSiglaUsuario();
      $strNomeUsuarioVersao = $arrObjVersaoSecaoDocumentoDTO[$qtdVersoes - 1]->getStrNomeUsuario();
      $numVersao = $arrObjVersaoSecaoDocumentoDTO[$qtdVersoes - 1]->getNumVersao();
      $dthVersao = $arrObjVersaoSecaoDocumentoDTO[$qtdVersoes - 1]->getDthAtualizacao();
    }

    $html = '<hr style="border:1px solid #c0c0c0;" />';
    $html .= 'Criado por ';
    $html .= '<a onclick="alert(\'' . PaginaSEI::getInstance()->formatarParametrosJavaScript($strNomeUsuarioGerador) . '\')" alt="' . $strNomeUsuarioGerador . '" title="' . $strNomeUsuarioGerador . '" style="color:#0066cc;text-decoration:none;cursor:pointer;">' . $strSiglaUsuarioGerador . '</a>';
    $html .= ', versão ' . $numVersao . ' por ';
    $html .= '<a onclick="alert(\'' . PaginaSEI::getInstance()->formatarParametrosJavaScript($strNomeUsuarioVersao) . '\')" alt="' . $strNomeUsuarioVersao . '" title="' . $strNomeUsuarioVersao . '" style="color:#0066cc;text-decoration:none;cursor:pointer;">' . $strSiglaUsuarioVersao . '</a>';
    $html .= ' em ' . $dthVersao . '.' . "\n";

    return $html;
  }

  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
  protected function consultarHtmlVersaoConectado($dados)
  {
    $parObjEditorDTO=$dados["parObjEditorDTO"];
    $montarTarja=$dados["montarTarja"];
    $controleURL=$dados["controleURL"];
    $bolTarjaLegada402=$dados["bolTarjaLegada402"];
    $bolSiglaSistemaSUPER = $dados["bolSiglaSistemaSUPER"];

    if ($parObjEditorDTO->getDblIdDocumento()!=null) {
      $objDocumentoDTO = new DocumentoDTO();
      $objDocumentoDTO->retDblIdDocumento();
      $objDocumentoDTO->retStrNomeSerie();
      $objDocumentoDTO->retStrProtocoloDocumentoFormatado();
      $objDocumentoDTO->retStrProtocoloProcedimentoFormatado();
      $objDocumentoDTO->retStrCrcAssinatura();
      $objDocumentoDTO->retStrQrCodeAssinatura();
      $objDocumentoDTO->retObjPublicacaoDTO();
      $objDocumentoDTO->retNumIdConjuntoEstilos();
      $objDocumentoDTO->retStrSinBloqueado();
      $objDocumentoDTO->retStrStaDocumento();
      $objDocumentoDTO->retStrStaProtocoloProtocolo();
      $objDocumentoDTO->retNumIdUnidadeGeradoraProtocolo();
      $objDocumentoDTO->retStrDescricaoTipoConferencia();

      $objDocumentoDTO->setDblIdDocumento($parObjEditorDTO->getDblIdDocumento());

      $objDocumentoRN = new DocumentoRN();
      $objDocumentoDTO = $objDocumentoRN->consultarRN0005($objDocumentoDTO);

      if ($objDocumentoDTO==null) {
        throw new InfraException('Módulo do Tramita: Documento não encontrado.');
      }

      if ($objDocumentoDTO->getNumIdConjuntoEstilos()!=null) {
        $strConteudoCss = $this->montarCssEditor($objDocumentoDTO->getNumIdConjuntoEstilos());
      } else {
        $strConteudoCss = "";
      }
      $strTitulo = DocumentoINT::montarTitulo($objDocumentoDTO);

      $objDocumentoRN->bloquearConsultado($objDocumentoDTO);

    } else {
      $objBaseConhecimentoDTO = new BaseConhecimentoDTO();
      $objBaseConhecimentoDTO->retNumIdBaseConhecimento();
      $objBaseConhecimentoDTO->retStrDescricao();
      $objBaseConhecimentoDTO->retStrSiglaUnidade();
      $objBaseConhecimentoDTO->retNumIdConjuntoEstilos();
      $objBaseConhecimentoDTO->setNumIdBaseConhecimento($parObjEditorDTO->getNumIdBaseConhecimento());

      $objBaseConhecimentoRN = new BaseConhecimentoRN();
      $objBaseConhecimentoDTO = $objBaseConhecimentoRN->consultar($objBaseConhecimentoDTO);

      if ($objBaseConhecimentoDTO==null) {
        throw new InfraException('Módulo do Tramita: Base de conhecimento não encontrada.');
      }

      if ($objBaseConhecimentoDTO->getNumIdConjuntoEstilos()!=null) {
        $strConteudoCss = $this->montarCssEditor($objBaseConhecimentoDTO->getNumIdConjuntoEstilos());
      } else {
        $strConteudoCss = "";
      }
      $strTitulo = BaseConhecimentoINT::montarTitulo($objBaseConhecimentoDTO);
    }

    //regex reset de contadores
    $qtd=preg_match_all('/p\.(\S*) \{[^}]*counter-increment:([^;]*);/', $strConteudoCss, $arrCssContadores);
    if ($qtd>0){
      $arrCssContadores=array_combine($arrCssContadores[1], $arrCssContadores[2]);
    } else {
      $arrCssContadores=null;
    }

    if($bolSiglaSistemaSUPER){
      $strSiglaSistema = ConfiguracaoSEI::getInstance()->getValor("SessaoSEI", "SiglaSistema");
      $strPadrao = ($strSiglaSistema === "SEI") ? '/^SEI\//' : '/^SUPER\//';
      $strSubstituicao = ($strSiglaSistema === "SEI") ? "SUPER/" : "SEI/";
      $strTitulo = preg_replace($strPadrao, $strSubstituicao, $strTitulo);
    }

    $strHtml = '';
    $strHtml .= '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">' . "\n";
    $strHtml .= '<html lang="pt-br" >' . "\n";
    $strHtml .= '<head>' . "\n";
    $strHtml .= '<meta http-equiv="Pragma" content="no-cache" />' . "\n";
    $strHtml .= '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">' . "\n";
    if ($strConteudoCss!="") {
      $strHtml .= '<style type="text/css">' . "\n";
      $strHtml .= $strConteudoCss;
      $strHtml .= "\n</style>";
    }
    $strHtml .= '<title>' . $strTitulo . '</title>' . "\n";
    $strHtml .= '</head>' . "\n";
    $strHtml .= '<body>' . "\n";

    if ($parObjEditorDTO->getStrSinCarimboPublicacao()=='S' && $objDocumentoDTO != null) {
      $strTextoPublicacao = PublicacaoINT::obterTextoInformativoPublicacao($objDocumentoDTO);
      if ($strTextoPublicacao != null) {
        $strHtml .= $this->montarCarimboPublicacao($strTextoPublicacao)."\n";
      }
    }

    $objSecaoDocumentoDTO = new SecaoDocumentoDTO();
    $objSecaoDocumentoDTO->retNumIdSecaoDocumento();
    $objSecaoDocumentoDTO->retStrSinAssinatura();
    $objSecaoDocumentoDTO->setDblIdDocumento($parObjEditorDTO->getDblIdDocumento());
    $objSecaoDocumentoDTO->setNumIdBaseConhecimento($parObjEditorDTO->getNumIdBaseConhecimento());

    if ($parObjEditorDTO->getStrSinCabecalho()=='N') {
      $objSecaoDocumentoDTO->setStrSinCabecalho('N');
    }

    if ($parObjEditorDTO->getStrSinRodape()=='N') {
      $objSecaoDocumentoDTO->setStrSinRodape('N');
    }

    $objSecaoDocumentoDTO->setOrdNumOrdem(InfraDTO::$TIPO_ORDENACAO_ASC);

    $objSecaoDocumentoRN = new SecaoDocumentoRN();
    $objVersaoSecaoDocumentoRN = new VersaoSecaoDocumentoRN();

    $arrObjSecaoDocumentoDTO = $objSecaoDocumentoRN->listar($objSecaoDocumentoDTO);

    $numVersao = null;

    if (!$parObjEditorDTO->isSetNumVersao()) {
      $objVersaoSecaoDocumentoDTO = new VersaoSecaoDocumentoDTO();
      $objVersaoSecaoDocumentoDTO->retNumIdSecaoDocumento();
      $objVersaoSecaoDocumentoDTO->retNumVersao();
      $objVersaoSecaoDocumentoDTO->retStrConteudo();
      $objVersaoSecaoDocumentoDTO->setNumIdSecaoDocumento(InfraArray::converterArrInfraDTO($arrObjSecaoDocumentoDTO, 'IdSecaoDocumento'), InfraDTO::$OPER_IN);
      $objVersaoSecaoDocumentoDTO->setStrSinUltima('S');
      $objVersaoSecaoDocumentoDTO->setOrdNumVersao(InfraDTO::$TIPO_ORDENACAO_DESC);

      $arrObjVersaoSecaoDocumentoDTO = $objVersaoSecaoDocumentoRN->listar($objVersaoSecaoDocumentoDTO);

      if (count($arrObjVersaoSecaoDocumentoDTO)) {
        $numVersao = $arrObjVersaoSecaoDocumentoDTO[0]->getNumVersao();
        $arrObjVersaoSecaoDocumentoDTO = InfraArray::indexarArrInfraDTO($arrObjVersaoSecaoDocumentoDTO, 'IdSecaoDocumento');
      }
    }

    foreach ($arrObjSecaoDocumentoDTO as $objSecaoDocumentoDTO) {
      if ($objSecaoDocumentoDTO->getStrSinAssinatura()=='N') {

        if (!$parObjEditorDTO->isSetNumVersao()) {

          if (isset($arrObjVersaoSecaoDocumentoDTO[$objSecaoDocumentoDTO->getNumIdSecaoDocumento()])) {
            $strHtml .= $this->resetContadoresCss($arrObjVersaoSecaoDocumentoDTO[$objSecaoDocumentoDTO->getNumIdSecaoDocumento()]->getStrConteudo(), $arrCssContadores);
          }

        } else {

          $objVersaoSecaoDocumentoDTO = new VersaoSecaoDocumentoDTO();
          $objVersaoSecaoDocumentoDTO->retStrConteudo();
          $objVersaoSecaoDocumentoDTO->setNumIdSecaoDocumento($objSecaoDocumentoDTO->getNumIdSecaoDocumento());
          $objVersaoSecaoDocumentoDTO->setNumVersao($parObjEditorDTO->getNumVersao(), InfraDTO::$OPER_MENOR_IGUAL);
          $objVersaoSecaoDocumentoDTO->setOrdNumVersao(InfraDTO::$TIPO_ORDENACAO_DESC);
          $objVersaoSecaoDocumentoDTO->setNumMaxRegistrosRetorno(1);

          $arrObjVersaoSecaoDocumentoDTO = $objVersaoSecaoDocumentoRN->listar($objVersaoSecaoDocumentoDTO);
          $strHtml .= $this->resetContadoresCss($arrObjVersaoSecaoDocumentoDTO[0]->getStrConteudo(), $arrCssContadores);
        }

      } else {

        if ($parObjEditorDTO->isSetStrSinAssinaturas() && $parObjEditorDTO->getStrSinAssinaturas()=='N') {
          continue;
        }

        //só mostrar a tarja se consultando a última versão
        if ($parObjEditorDTO->isSetNumVersao()) {

          $objVersaoSecaoDocumentoDTO = new VersaoSecaoDocumentoDTO();
          $objVersaoSecaoDocumentoDTO->retNumVersao();
          $objVersaoSecaoDocumentoDTO->setDblIdDocumentoSecaoDocumento($parObjEditorDTO->getDblIdDocumento());
          $objVersaoSecaoDocumentoDTO->setStrSinUltima('S');
          $objVersaoSecaoDocumentoDTO->setNumMaxRegistrosRetorno(1);
          $objVersaoSecaoDocumentoDTO->setOrdNumVersao(InfraDTO::$TIPO_ORDENACAO_DESC);

          $arrObjVersaoSecaoDocumentoDTO = $objVersaoSecaoDocumentoRN->listar($objVersaoSecaoDocumentoDTO);

          if ($arrObjVersaoSecaoDocumentoDTO[0]->getNumVersao()!=$parObjEditorDTO->getNumVersao()) {
            continue;
          }
        }

        if ($objDocumentoDTO!=null) {
          if(!$montarTarja){
            if($bolTarjaLegada402){
              $objAssinaturaRN = new AssinaturaHashRN();
              $strHtml .= $objAssinaturaRN->montarTarjasLegado($objDocumentoDTO);
            }else{
              $objAssinaturaRN = new AssinaturaRN();
              $strHtml .= $objAssinaturaRN->montarTarjas($objDocumentoDTO);
            }
          }else{
            $objAssinaturaRN = new AssinaturaHashRN();
            $dados=[
              "objDocumentoDTO"=>$objDocumentoDTO,
              "controleURL"=>$controleURL
            ];
            $strHtml .= $objAssinaturaRN->montarTarjasURL($dados);
          }
        }
      }
    }


    if ($parObjEditorDTO->getStrSinIdentificacaoVersao()=='S') {
      if(!$montarTarja){
        $strHtml .= $this->consultarHtmlIdentificacaoVersao($parObjEditorDTO);
      }else{
        $strHtml .= $this->consultarHtmlIdentificacaoVersao3015($parObjEditorDTO);
      }
    }

    $strHtml .= '</body>' . "\n";
    $strHtml .= '</html>' . "\n";

    if (!$parObjEditorDTO->isSetNumVersao()) {
      $parObjEditorDTO->setNumVersao($numVersao);
    }

    if ($parObjEditorDTO->isSetStrSinProcessarLinks() && $parObjEditorDTO->getStrSinProcessarLinks()=='S') {

      $strHtml=$this->processarLinksSei($strHtml);
//      $strHtml=preg_replace(self::$REGEXP_LINK_ASSINADO,'$4',$strHtml);

      $posLinkSeiIni = 0;
      $strChaveBusca = 'id="lnkSei';
      while (($posLinkSeiIni = strpos($strHtml, $strChaveBusca, $posLinkSeiIni))!==false) {

        $posLinkSeiIni = $posLinkSeiIni + strlen($strChaveBusca);
        $posLinkSeiFim = strpos($strHtml, '"', $posLinkSeiIni);

        if ($posLinkSeiIni!==false && $posLinkSeiFim!==false) {

          $dblIdProtocolo = substr($strHtml, $posLinkSeiIni, $posLinkSeiFim - $posLinkSeiIni);

          $strLink = SessaoSEI::getInstance()->assinarLink('controlador.php?acao=protocolo_visualizar&id_protocolo=' . $dblIdProtocolo);

          $strHtml = substr($strHtml, 0, $posLinkSeiIni - strlen($strChaveBusca)) . ' href="' . $strLink . '" target="_blank" ' . substr($strHtml, $posLinkSeiFim + 1);
        }
      }
    } else {
      $strHtml=preg_replace(self::$REGEXP_LINK_ASSINADO, '$4', $strHtml);
    }

    $dblIdDocumento = '';
    $strIdentificacao = '';
    if ($objDocumentoDTO!=null){
      $dblIdDocumento = $objDocumentoDTO->getDblIdDocumento();
      $strIdentificacao = $objDocumentoDTO->getStrProtocoloDocumentoFormatado();
    }else{
      $strIdentificacao = 'base de conhecimento '.$objBaseConhecimentoDTO->getStrDescricao().'/'.$objBaseConhecimentoDTO->getStrSiglaUnidade();
    }

    SeiINT::validarXss($strHtml, $strIdentificacao, false, '', $dblIdDocumento);

    return $strHtml;
  }
  protected function compararHtmlVersaoConectado(EditorDTO $parObjEditorDTO)
  {


    if ($parObjEditorDTO->getDblIdDocumento()!=null) {
      $objDocumentoDTO = new DocumentoDTO();
      $objDocumentoDTO->retDblIdDocumento();
      $objDocumentoDTO->retStrNomeSerie();
      $objDocumentoDTO->retStrProtocoloDocumentoFormatado();
      $objDocumentoDTO->retStrProtocoloProcedimentoFormatado();
      $objDocumentoDTO->retStrCrcAssinatura();
      $objDocumentoDTO->retStrQrCodeAssinatura();
      $objDocumentoDTO->retObjPublicacaoDTO();
      $objDocumentoDTO->retNumIdConjuntoEstilos();
      $objDocumentoDTO->retStrSinBloqueado();
      $objDocumentoDTO->retStrStaDocumento();
      $objDocumentoDTO->retStrStaProtocoloProtocolo();
      $objDocumentoDTO->retNumIdUnidadeGeradoraProtocolo();
      $objDocumentoDTO->retStrDescricaoTipoConferencia();

      $objDocumentoDTO->setDblIdDocumento($parObjEditorDTO->getDblIdDocumento());

      $objDocumentoRN = new DocumentoRN();
      $objDocumentoDTO = $objDocumentoRN->consultarRN0005($objDocumentoDTO);

      if ($objDocumentoDTO==null) {
        throw new InfraException('Módulo do Tramita: Documento não encontrado.');
      }

      if ($objDocumentoDTO->getNumIdConjuntoEstilos()!=null) {
        $strConteudoCss = $this->montarCssEditor($objDocumentoDTO->getNumIdConjuntoEstilos());
      } else {
        $strConteudoCss = "";
      }
      $strTitulo = DocumentoINT::montarTitulo($objDocumentoDTO);

      $objDocumentoRN->bloquearConsultado($objDocumentoDTO);

    } else {
      $objBaseConhecimentoDTO = new BaseConhecimentoDTO();
      $objBaseConhecimentoDTO->retNumIdBaseConhecimento();
      $objBaseConhecimentoDTO->retStrDescricao();
      $objBaseConhecimentoDTO->retStrSiglaUnidade();
      $objBaseConhecimentoDTO->retNumIdConjuntoEstilos();
      $objBaseConhecimentoDTO->setNumIdBaseConhecimento($parObjEditorDTO->getNumIdBaseConhecimento());

      $objBaseConhecimentoRN = new BaseConhecimentoRN();
      $objBaseConhecimentoDTO = $objBaseConhecimentoRN->consultar($objBaseConhecimentoDTO);

      if ($objBaseConhecimentoDTO==null) {
        throw new InfraException('Módulo do Tramita: Base de conhecimento não encontrada.');
      }

      if ($objBaseConhecimentoDTO->getNumIdConjuntoEstilos()!=null) {
        $strConteudoCss = $this->montarCssEditor($objBaseConhecimentoDTO->getNumIdConjuntoEstilos());
      } else {
        $strConteudoCss = "";
      }
      $strTitulo = BaseConhecimentoINT::montarTitulo($objBaseConhecimentoDTO);
    }

    //regex reset de contadores
    $qtd=preg_match_all('/p\.(\S*) \{[^}]*counter-increment:([^;]*);/', $strConteudoCss, $arrCssContadores);
    if ($qtd>0){
      $arrCssContadores=array_combine($arrCssContadores[1], $arrCssContadores[2]);
    } else {
      $arrCssContadores=null;
    }

    $strHtml = '';
    $strHtml .= '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">' . "\n";
    $strHtml .= '<html lang="pt-br" >' . "\n";
    $strHtml .= '<head>' . "\n";
    $strHtml .= '<meta http-equiv="Pragma" content="no-cache" />' . "\n";
    $strHtml .= '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">' . "\n";
    if ($strConteudoCss!="") {
      $strHtml .= '<style type="text/css"><!--/*--><![CDATA[/*><!--*/' . "\n";
      $strHtml .= $strConteudoCss."\n";
      $strHtml .= InfraHTML::getCssComparacao();
      $strHtml .= "\n/*]]>*/-->\n</style>";
    }
    $strHtml .= '<title>' . $strTitulo . ' - Comparando versões '.$parObjEditorDTO->getNumVersao().' e '.$parObjEditorDTO->getNumVersaoComparacao().'</title>' . "\n";
    $strHtml .= '</head>' . "\n";
    $strHtml .= '<body>' . "\n";

    if ($parObjEditorDTO->getStrSinCarimboPublicacao()=='S' && $objDocumentoDTO != null) {
      $strTextoPublicacao = PublicacaoINT::obterTextoInformativoPublicacao($objDocumentoDTO);
      if ($strTextoPublicacao != null) {
        $strHtml .= $this->montarCarimboPublicacao($strTextoPublicacao)."\n";
      }
    }

    $objSecaoDocumentoDTO = new SecaoDocumentoDTO();
    $objSecaoDocumentoDTO->retNumIdSecaoDocumento();
    $objSecaoDocumentoDTO->retStrSinAssinatura();
    $objSecaoDocumentoDTO->setDblIdDocumento($parObjEditorDTO->getDblIdDocumento());
    $objSecaoDocumentoDTO->setNumIdBaseConhecimento($parObjEditorDTO->getNumIdBaseConhecimento());

    if ($parObjEditorDTO->getStrSinCabecalho()=='N') {
      $objSecaoDocumentoDTO->setStrSinCabecalho('N');
    }

    if ($parObjEditorDTO->getStrSinRodape()=='N') {
      $objSecaoDocumentoDTO->setStrSinRodape('N');
    }

    $objSecaoDocumentoDTO->setOrdNumOrdem(InfraDTO::$TIPO_ORDENACAO_ASC);

    $objSecaoDocumentoRN = new SecaoDocumentoRN();
    $objVersaoSecaoDocumentoRN = new VersaoSecaoDocumentoRN();

    $arrObjSecaoDocumentoDTO = $objSecaoDocumentoRN->listar($objSecaoDocumentoDTO);

    $numVersao = $parObjEditorDTO->getNumVersao();
    $numVersaoComparacao = $parObjEditorDTO->getNumVersaoComparacao();


    $objVersaoSecaoDocumentoDTO = new VersaoSecaoDocumentoDTO();
    $objVersaoSecaoDocumentoDTO->retStrConteudo();
    $objVersaoSecaoDocumentoDTO->retNumVersao();
    $objVersaoSecaoDocumentoDTO->setOrdNumVersao(InfraDTO::$TIPO_ORDENACAO_DESC);
    $objVersaoSecaoDocumentoDTO->setNumMaxRegistrosRetorno(1);

    foreach ($arrObjSecaoDocumentoDTO as $objSecaoDocumentoDTO) {
      if ($objSecaoDocumentoDTO->getStrSinAssinatura()=='N') {
        $objVersaoSecaoDocumentoDTO->setNumIdSecaoDocumento($objSecaoDocumentoDTO->getNumIdSecaoDocumento());
        $objVersaoSecaoDocumentoDTO->setNumVersao($numVersao, InfraDTO::$OPER_MENOR_IGUAL);

        $objVersaoSecaoDocumentoDTO1 = $objVersaoSecaoDocumentoRN->consultar($objVersaoSecaoDocumentoDTO);

        $objVersaoSecaoDocumentoDTO->setNumVersao($numVersaoComparacao, InfraDTO::$OPER_MENOR_IGUAL);

        $objVersaoSecaoDocumentoDTO2 = $objVersaoSecaoDocumentoRN->consultar($objVersaoSecaoDocumentoDTO);

        if ($objVersaoSecaoDocumentoDTO1->getNumVersao() == $objVersaoSecaoDocumentoDTO2->getNumVersao() ||
            $objVersaoSecaoDocumentoDTO1->getStrConteudo() == $objVersaoSecaoDocumentoDTO2->getStrConteudo()
        ) {
          $strConteudo = $objVersaoSecaoDocumentoDTO1->getStrConteudo();
        } else {
          $str1=$objVersaoSecaoDocumentoDTO1->getStrConteudo();
          $str2=$objVersaoSecaoDocumentoDTO2->getStrConteudo();
          $strConteudo = InfraHTML::comparar($str1, $str2);
        }

        $strHtml .= $this->resetContadoresCss($strConteudo, $arrCssContadores);
      }

    }

    $strHtml .= '</body>' . "\n";
    $strHtml .= '</html>' . "\n";


    if ($parObjEditorDTO->isSetStrSinProcessarLinks() && $parObjEditorDTO->getStrSinProcessarLinks()=='S') {

      $strHtml=$this->processarLinksSei($strHtml);
//      $strHtml=preg_replace(self::$REGEXP_LINK_ASSINADO,'$4',$strHtml);

      $posLinkSeiIni = 0;
      $strChaveBusca = 'id="lnkSei';
      while (($posLinkSeiIni = strpos($strHtml, $strChaveBusca, $posLinkSeiIni))!==false) {

        $posLinkSeiIni = $posLinkSeiIni + strlen($strChaveBusca);
        $posLinkSeiFim = strpos($strHtml, '"', $posLinkSeiIni);

        if ($posLinkSeiIni!==false && $posLinkSeiFim!==false) {

          $dblIdProtocolo = substr($strHtml, $posLinkSeiIni, $posLinkSeiFim - $posLinkSeiIni);

          $strLink = SessaoSEI::getInstance()->assinarLink('controlador.php?acao=protocolo_visualizar&id_protocolo=' . $dblIdProtocolo);

          $strHtml = substr($strHtml, 0, $posLinkSeiIni - strlen($strChaveBusca)) . ' href="' . $strLink . '" target="_blank" ' . substr($strHtml, $posLinkSeiFim + 1);
        }
      }
    } else {
      $strHtml=preg_replace(self::$REGEXP_LINK_ASSINADO, '$4', $strHtml);
    }

    return $strHtml;
  }

  private function resetContadoresCss($strConteudoHtml, $arrClasses)
  {
    if(is_array($arrClasses) && count($arrClasses)>1){
      $arrContadoresUsados=array();
      $qtd=preg_match_all('/<p\w*\s*class="([^"]*)/', $strConteudoHtml, $arrMatches);
      if ($qtd>0){
        $arrClassesUsadas=array_unique($arrMatches[1]);
        foreach ($arrClassesUsadas as $strClasse) {
          if(isset($arrClasses[$strClasse])){
            $arrContadoresUsados[]=$arrClasses[$strClasse];
          }
        }
        if (count($arrContadoresUsados)>0){
          $arrContadoresUsados=array_unique($arrContadoresUsados);
          $strDiv="\n<div style=\"counter-reset:";
          foreach ($arrContadoresUsados as $strContador) {
            $strDiv.=" ".$strContador;
          }
          $strDiv.=';"></div>'."\n";
          return $strConteudoHtml.$strDiv;
        }
      }
    }
    return $strConteudoHtml;
  }
  private function substituirTagsInterno(EditorDTO $parObjEditorDTO, $strConteudo){
    $strConteudo=preg_replace_callback("/@([a-zA-Z0-9_-]+)@/", function($match) use ($parObjEditorDTO) {
      if (isset(self::$arrTags[$match[1]])) { return self::$arrTags[$match[1]];
      }
      $this->obterParametros($parObjEditorDTO, $match[1]);
      if (isset(self::$arrTags[$match[1]])) {
        return self::$arrTags[$match[1]];
      } else {
        return $match[0];
      }
    }, $strConteudo);
    return $strConteudo;
  }

  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
  private function obterParametros(EditorDTO $parObjEditorDTO, $parStrTag){

    global $SEI_MODULOS;

    $bolDocumento=!isset(self::$arrTags['#idUnidadeResponsavel']);
    $bolUnidade=false;
    $bolUsuario=false;
    $bolHierarquia=false;
    $bolParticipante=false;
    $bolModulo=false;



    switch ($parStrTag){
      case 'processo':
      case 'tipo_processo':
      case 'especificacao_processo':
      case 'codigo_barras_processo':
      case 'documento':
      case 'codigo_barras_documento':
      case 'descricao_documento':
      case 'serie':
      case 'numeracao_serie':
      case 'dia':
      case 'mes':
      case 'ano':
      case 'mes_extenso':
        $bolDocumento=true;
          break;

      case 'nome_autor':
        $bolUsuario=!isset(self::$arrTags['nome_autor']);
          break;

      case 'sigla_orgao_origem':
      case 'descricao_orgao_origem':
      case 'descricao_orgao_maiusculas':
      case 'timbre_orgao':
      case 'endereco_unidade':
      case 'telefone_unidade':
      case 'telefone_fixo_unidade':
      case 'telefone_celular_unidade':
      case 'cep_unidade':
      case 'sigla_uf_unidade':
      case 'hifen_bairro_unidade':
      case 'cidade_unidade':
      case 'hifen_sitio_internet_orgao':
      case 'complemento_endereco_unidade':
      case 'sigla_unidade':
      case 'descricao_unidade':
      case 'descricao_unidade_maiusculas':
      case 'observacao_unidade':
        $bolUnidade=!isset(self::$arrTags['sigla_orgao_origem']);
          break;

      case 'hierarquia_unidade':
      case 'hierarquia_unidade_invertida':
      case 'hierarquia_unidade_descricao_quebra_linha':
      case 'hierarquia_unidade_invertida_descricao_quebra_linha':
      case 'hierarquia_unidade_raiz_sigla':
      case 'hierarquia_unidade_raiz_descricao':
      case 'hierarquia_unidade_superior_sigla':
      case 'hierarquia_unidade_superior_descricao':
        $bolUnidade=!isset(self::$arrTags['sigla_orgao_origem']);
        $bolHierarquia=!isset(self::$arrTags['hierarquia_unidade']);
          break;

      case 'destinatarios':
      case 'destinatarios_virgula_espaco':
      case 'destinatarios_virgula_espaco_maiusculas':
      case 'destinatarios_quebra_linha':
      case 'destinatarios_quebra_linha_maiusculas':
      case 'artigo_destinatario_minuscula':
      case 'artigo_destinatario_maiuscula':
      case 'nome_destinatario':
      case 'nome_destinatario_maiusculas':
      case 'tratamento_destinatario':
      case 'vocativo_destinatario':
      case 'cargo_destinatario':
      case 'origem_destinatario':
      case 'nome_contexto_destinatario':
      case 'nome_pessoa_juridica_associada_destinatario':
      case 'endereco_destinatario':
      case 'complemento_endereco_destinatario':
      case 'bairro_destinatario':
      case 'cidade_destinatario':
      case 'cep_destinatario':
      case 'hifen_uf_destinatario':
      case 'sigla_uf_destinatario':
      case 'pais_destinatario':
      case 'cpf_destinatario':
      case 'cnpj_destinatario':
      case 'cnpj_pessoa_juridica_associada_destinatario':
      case 'sitio_internet_destinatario':
      case 'rg_destinatario':
      case 'orgao_expedidor_rg_destinatario':
      case 'matricula_destinatario':
      case 'matricula_oab_destinatario':
      case 'email_destinatario':
      case 'telefone_fixo_destinatario':
      case 'telefone_celular_destinatario':
      case 'interessados':
      case 'interessados_virgula_espaco':
      case 'interessados_virgula_espaco_maiusculas':
      case 'interessados_quebra_linha':
      case 'interessados_quebra_linha_maiusculas':
      case 'artigo_interessado_minuscula':
      case 'artigo_interessado_maiuscula':
      case 'nome_interessado':
      case 'nome_interessado_maiusculas':
      case 'tratamento_interessado':
      case 'vocativo_interessado':
      case 'cargo_interessado':
      case 'origem_interessado':
      case 'nome_contexto_interessado':
      case 'nome_pessoa_juridica_associada_interessado':
      case 'endereco_interessado':
      case 'complemento_endereco_interessado':
      case 'bairro_interessado':
      case 'cidade_interessado':
      case 'cep_interessado':
      case 'hifen_uf_interessado':
      case 'sigla_uf_interessado':
      case 'pais_interessado':
      case 'cpf_interessado':
      case 'cnpj_interessado':
      case 'cnpj_pessoa_juridica_associada_interessado':
      case 'sitio_internet_interessado':
      case 'rg_interessado':
      case 'orgao_expedidor_rg_interessado':
      case 'matricula_interessado':
      case 'matricula_oab_interessado':
      case 'email_interessado':
      case 'telefone_fixo_interessado':
      case 'telefone_celular_interessado':
        $bolParticipante=!(isset(self::$arrTags['interessados_virgula_espaco'])||isset(self::$arrTags['destinatarios_virgula_espaco']));
          break;
      case 'link_acesso_externo_processo':
        $objDocumentoDTO = new DocumentoDTO();
        $objDocumentoDTO->setDblIdDocumento($parObjEditorDTO->getDblIdDocumento());
        self::$arrTags[$parStrTag] = $this->recuperarLinkAcessoExterno($objDocumentoDTO);
          break;
      default:
        if(preg_match(EditorRN::$REGEXP_VARIAVEL_EDITOR, $parStrTag)===1){
          $bolModulo=true;
          break;
        }
          return;
    }
    try {

      $objInfraException = new InfraException();

      if ($bolDocumento){
        if ($parObjEditorDTO->getDblIdDocumento()!=null) {

          $objDocumentoDTO = new DocumentoDTO();
          $objDocumentoDTO->retDblIdDocumento();
          $objDocumentoDTO->retDblIdProcedimento();
          $objDocumentoDTO->retDblIdDocumentoEdoc();
          $objDocumentoDTO->retStrProtocoloDocumentoFormatado();
          $objDocumentoDTO->retStrEspecificacaoProcedimento();
          $objDocumentoDTO->retStrProtocoloProcedimentoFormatado();
          $objDocumentoDTO->retStrNomeTipoProcedimentoProcedimento();
          $objDocumentoDTO->retStrNomeSerie();
          $objDocumentoDTO->retNumIdModeloSerie();
          $objDocumentoDTO->retNumIdModeloEdocSerie();
          $objDocumentoDTO->retNumIdUnidadeResponsavel();
          $objDocumentoDTO->retNumIdUsuarioGeradorProtocolo();
          $objDocumentoDTO->retDtaGeracaoProtocolo();
          $objDocumentoDTO->retStrNumero();
          $objDocumentoDTO->retStrDescricaoProtocolo();
          $objDocumentoDTO->retStrCodigoBarrasProcedimento();
          $objDocumentoDTO->retStrCodigoBarrasDocumento();

          $objDocumentoDTO->setDblIdDocumento($parObjEditorDTO->getDblIdDocumento());

          $objDocumentoRN = new DocumentoRN();
          $objDocumentoDTO = $objDocumentoRN->consultarRN0005($objDocumentoDTO);

          if ($objDocumentoDTO==null) {
            $objInfraException->lancarValidacao('Documento não encontrado.');
          }

          self::$arrTags['#dblIdProcedimento']=$objDocumentoDTO->getDblIdProcedimento();
          self::$arrTags['processo']=$objDocumentoDTO->getStrProtocoloProcedimentoFormatado();
          self::$arrTags['tipo_processo']= $objDocumentoDTO->getStrNomeTipoProcedimentoProcedimento();
          self::$arrTags['especificacao_processo']= $objDocumentoDTO->getStrEspecificacaoProcedimento();
          self::$arrTags['codigo_barras_processo']= '<img alt="Código de Barras do Processo" src="data:image/png;base64,' . $objDocumentoDTO->getStrCodigoBarrasProcedimento() . '" />';
          self::$arrTags['documento']= $objDocumentoDTO->getStrProtocoloDocumentoFormatado();
          self::$arrTags['codigo_barras_documento']='<img alt="Código de Barras do Documento" src="data:image/png;base64,' . $objDocumentoDTO->getStrCodigoBarrasDocumento() . '" />';
          self::$arrTags['descricao_documento']=$objDocumentoDTO->getStrDescricaoProtocolo();
          self::$arrTags['serie']=$objDocumentoDTO->getStrNomeSerie();

          if (!InfraString::isBolVazia($objDocumentoDTO->getStrNumero())) {
            self::$arrTags['numeracao_serie']= $objDocumentoDTO->getStrNumero();
          } else {
            self::$arrTags['numeracao_serie']=$objDocumentoDTO->getStrProtocoloDocumentoFormatado();
          }

          $numIdUnidadeResponsavel = $objDocumentoDTO->getNumIdUnidadeResponsavel();
          $numIdUsuarioGerador = $objDocumentoDTO->getNumIdUsuarioGeradorProtocolo();
          $dtaGeracao = $objDocumentoDTO->getDtaGeracaoProtocolo();

        } else if ($parObjEditorDTO->getNumIdBaseConhecimento()!=null) {

          $objBaseConhecimentoDTO = new BaseConhecimentoDTO();
          $objBaseConhecimentoDTO->retNumIdUnidade();
          $objBaseConhecimentoDTO->retNumIdUsuarioGerador();
          $objBaseConhecimentoDTO->retDthGeracao();
          $objBaseConhecimentoDTO->setNumIdBaseConhecimento($parObjEditorDTO->getNumIdBaseConhecimento());

          $objBaseConhecimentoRN = new BaseConhecimentoRN();
          $objBaseConhecimentoDTO = $objBaseConhecimentoRN->consultar($objBaseConhecimentoDTO);

          $numIdUnidadeResponsavel = $objBaseConhecimentoDTO->getNumIdUnidade();
          $numIdUsuarioGerador = $objBaseConhecimentoDTO->getNumIdUsuarioGerador();
          $dtaGeracao = substr($objBaseConhecimentoDTO->getDthGeracao(), 0, 10);
        }

        self::$arrTags['#numIdUnidadeResponsavel']=$numIdUnidadeResponsavel;
        self::$arrTags['#numIdUsuarioGerador']=$numIdUsuarioGerador;
        self::$arrTags['#dtaGeracao']=$dtaGeracao;
        //usa data de geracao do protocolo, nas republicacoes, retificações, apostilamentos de atos, portarias... deve manter a data do original
        //para os outros casos o uso da data de geracao do protocolo ou do dia atual não faz diferença já que são iguais
        self::$arrTags['dia']=substr($dtaGeracao, 0, 2);
        self::$arrTags['mes']=substr($dtaGeracao, 3, 2);
        self::$arrTags['ano']=substr($dtaGeracao, 6, 4);
        self::$arrTags['mes_extenso']=strtolower(InfraData::descreverMes(substr($dtaGeracao, 3, 2)));
      }

      if ($bolUnidade) {
        /* Unidade Responsável ************************************************************************************/
        $objUnidadeDTO = new UnidadeDTO();
        $objUnidadeDTO->retNumIdOrgao();
        $objUnidadeDTO->retNumIdUnidade();
        $objUnidadeDTO->retNumIdContato();
        $objUnidadeDTO->retStrSigla();
        $objUnidadeDTO->retStrDescricao();
        $objUnidadeDTO->retStrSiglaOrgao();
        $objUnidadeDTO->retStrDescricaoOrgao();
        $objUnidadeDTO->retStrTimbreOrgao();
        $objUnidadeDTO->retStrSitioInternetOrgaoContato();

        $objUnidadeDTO->setNumIdUnidade(self::$arrTags['#numIdUnidadeResponsavel']);

        $objUnidadeRN = new UnidadeRN();
        $objUnidadeDTO = $objUnidadeRN->consultarRN0125($objUnidadeDTO);

        if ($objUnidadeDTO==null){
          throw new InfraException('Módulo do Tramita: Unidade não encontrada.');
        }

        $objContatoDTO = new ContatoDTO();
        $objContatoDTO->setBolExclusaoLogica(false);
        $objContatoDTO->retStrTelefoneFixo();
        $objContatoDTO->retStrTelefoneCelular();
        $objContatoDTO->retStrSitioInternet();
        $objContatoDTO->retStrObservacao();
        $objContatoDTO->setNumIdContato($objUnidadeDTO->getNumIdContato());

        $objContatoRN = new ContatoRN();
        $arrObjContatoDTOUnidade = $objContatoRN->listarComEndereco($objContatoDTO);

        if (count($arrObjContatoDTOUnidade)==0){
          throw new InfraException('Módulo do Tramita: Contato associado com a unidade não encontrado.');
        }

        $objContatoDTO = $arrObjContatoDTOUnidade[0];

        self::$arrTags['sigla_unidade']= InfraString::transformarCaixaAlta($objUnidadeDTO->getStrSigla());
        self::$arrTags['sigla_orgao_origem']= $objUnidadeDTO->getStrSiglaOrgao();
        self::$arrTags['descricao_unidade']= $objUnidadeDTO->getStrDescricao();
        self::$arrTags['descricao_unidade_maiusculas']= InfraString::transformarCaixaAlta($objUnidadeDTO->getStrDescricao());
        self::$arrTags['descricao_orgao_origem']= $objUnidadeDTO->getStrDescricaoOrgao();
        self::$arrTags['descricao_orgao_maiusculas']= InfraString::transformarCaixaAlta($objUnidadeDTO->getStrDescricaoOrgao());
        self::$arrTags['timbre_orgao']='<img alt="Timbre" src="data:image/png;base64,' . $objUnidadeDTO->getStrTimbreOrgao() . '" />';
        self::$arrTags['telefone_unidade']= $objContatoDTO->getStrTelefoneFixo();
        self::$arrTags['telefone_fixo_unidade']= $objContatoDTO->getStrTelefoneFixo();
        self::$arrTags['telefone_celular_unidade']= $objContatoDTO->getStrTelefoneCelular();
        self::$arrTags['observacao_unidade']= nl2br($objContatoDTO->getStrObservacao());

        $strTag = '';
        if (!InfraString::isBolVazia($objUnidadeDTO->getStrSitioInternetOrgaoContato())) {
          $strTag = ' - ' . $objUnidadeDTO->getStrSitioInternetOrgaoContato();
        }
        self::$arrTags['hifen_sitio_internet_orgao']= $strTag;

        self::$arrTags['endereco_unidade'] = $objContatoDTO->getStrEndereco();

        if (InfraString::isBolVazia(self::$arrTags['endereco_unidade'])) {
          throw new InfraException('Módulo do Tramita: Unidade ' . $objUnidadeDTO->getStrSigla() . ' não possui endereço cadastrado.', null, null, true, InfraLog::$AVISO);
        }

        self::$arrTags['cep_unidade'] = 'CEP ' . $objContatoDTO->getStrCep();
        self::$arrTags['sigla_uf_unidade'] = $objContatoDTO->getStrSiglaUf();

        $strTag = '';
        if (!InfraString::isBolVazia($objContatoDTO->getStrBairro())) {
          $strTag = ' - Bairro ' . $objContatoDTO->getStrBairro();
        }
        self::$arrTags['hifen_bairro_unidade']= $strTag;

        if ($objContatoDTO->getStrNomeCidade() != '') {
          self::$arrTags['cidade_unidade'] = $objContatoDTO->getStrNomeCidade();
        }

        $strTag = '';
        if (!InfraString::isBolVazia($objContatoDTO->getStrComplemento())) {
          $strTag .= $objContatoDTO->getStrComplemento();
        }
        self::$arrTags['complemento_endereco_unidade']= $strTag;

      }

      if ($bolUsuario) {
        /* Usuário Gerador ****************************************************************************************/
        $objUsuarioDTO = new UsuarioDTO();
        $objUsuarioDTO->setBolExclusaoLogica(false);
        $objUsuarioDTO->retStrNome();
        $objUsuarioDTO->setNumIdUsuario(self::$arrTags['#numIdUsuarioGerador']);

        $objUsuarioRN = new UsuarioRN();
        $objUsuarioDTO = $objUsuarioRN->consultarRN0489($objUsuarioDTO);
        if ($objUsuarioDTO->getStrNome() != '') {
          self::$arrTags['nome_autor']= $objUsuarioDTO->getStrNome();
        }
      }

      if ($bolHierarquia){
        $objUnidadeDTO = new UnidadeDTO();
        $objUnidadeDTO->setNumIdUnidade(self::$arrTags['#numIdUnidadeResponsavel']);
        $objUnidadeDTO->retStrSigla();
        $objUnidadeDTO->retStrSiglaOrgao();
        $objUnidadeDTO->retNumIdUnidade();
        $objUnidadeRN = new UnidadeRN();
        $objUnidadeDTO=$objUnidadeRN->consultarRN0125($objUnidadeDTO);

        $arrDadosHierarquia = $objUnidadeRN->obterDadosHierarquia($objUnidadeDTO);

        self::$arrTags['hierarquia_unidade'] = $arrDadosHierarquia['RAMIFICACAO'];
        $arrHierarquiaUnidade = explode('/', $arrDadosHierarquia['RAMIFICACAO']);
        $strHierarquiaUnidade = '';
        for ($i = count($arrHierarquiaUnidade) - 1; $i>=0; $i--) {
          if ($strHierarquiaUnidade!='') {
            $strHierarquiaUnidade .= '/';
          }
          $strHierarquiaUnidade .= $arrHierarquiaUnidade[$i];
        }
        self::$arrTags['hierarquia_unidade_invertida']= $strHierarquiaUnidade;

        self::$arrTags['hierarquia_unidade_descricao_quebra_linha']= $arrDadosHierarquia['DESCRICAO'];
        $arrHierarquiaDescricao = explode('<br />', $arrDadosHierarquia['DESCRICAO']);
        $strHierarquiaDescricao = '';
        for ($i = count($arrHierarquiaDescricao) - 1; $i>=0; $i--) {
          if ($strHierarquiaDescricao!='') {
            $strHierarquiaDescricao .= '<br />';
          }
          $strHierarquiaDescricao .= $arrHierarquiaDescricao[$i];
        }
        self::$arrTags['hierarquia_unidade_invertida_descricao_quebra_linha']= $strHierarquiaDescricao;

        self::$arrTags['hierarquia_unidade_raiz_sigla']= $arrDadosHierarquia['RAIZ_SIGLA'];
        self::$arrTags['hierarquia_unidade_raiz_descricao']= $arrDadosHierarquia['RAIZ_DESCRICAO'];
        self::$arrTags['hierarquia_unidade_superior_sigla']= $arrDadosHierarquia['SUPERIOR_SIGLA'];
        self::$arrTags['hierarquia_unidade_superior_descricao']= $arrDadosHierarquia['SUPERIOR_DESCRICAO'];
      }

      if ($parObjEditorDTO->getDblIdDocumento()!=null && $bolModulo) {
        $objDocumentoAPI=new DocumentoAPI();
        $objDocumentoAPI->setIdDocumento($parObjEditorDTO->getDblIdDocumento());
        $objDocumentoAPI->setIdProcedimento(self::$arrTags['#dblIdProcedimento']);

        foreach($SEI_MODULOS as $seiModulo){
          if (($arrVariaveisModulo = $seiModulo->executar('obterRelacaoVariaveisEditor'))!=null && isset($arrVariaveisModulo[$parStrTag])){
            if (($arrModulo = $seiModulo->executar('processarVariaveisEditor', $objDocumentoAPI))!=null){
              foreach ($arrModulo as $strVariavel => $strValor) {
                if(isset(self::$arrTags[$strVariavel])) {
                  throw new InfraException('Módulo do Tramita: Tentativa de sobrescrever variavel [' . $strVariavel . '] no módulo ' . $seiModulo->getNome());
                }
                if(!isset($arrVariaveisModulo[$strVariavel])){
                  throw new InfraException('Módulo do Tramita: Variável [' . $strVariavel . '] não relacionada no módulo ' . $seiModulo->getNome());
                }
                if(preg_match(EditorRN::$REGEXP_VARIAVEL_EDITOR, $strVariavel)!==1){
                  throw new InfraException('Módulo do Tramita: Tentativa de definir variavel inválida [' . $strVariavel . '] no módulo ' . $seiModulo->getNome());
                }
                self::$arrTags[$strVariavel]=$strValor;
              }
            }
            break;
          }
        }
      }

      if ($parObjEditorDTO->getDblIdDocumento()!=null && $bolParticipante) {

        /* Participantes ******************************************************************************************/
        $objParticipanteDTO = new ParticipanteDTO();
        $objParticipanteDTO->retNumIdContato();
        $objParticipanteDTO->retStrStaParticipacao();
        $objParticipanteDTO->retNumSequencia();
        $objParticipanteDTO->setStrStaParticipacao(array(ParticipanteRN::$TP_INTERESSADO, ParticipanteRN::$TP_DESTINATARIO), InfraDTO::$OPER_IN);
        $objParticipanteDTO->setDblIdProtocolo($parObjEditorDTO->getDblIdDocumento());
        $objParticipanteDTO->setOrdStrStaParticipacao(InfraDTO::$TIPO_ORDENACAO_ASC);
        $objParticipanteDTO->setOrdNumSequencia(InfraDTO::$TIPO_ORDENACAO_ASC);

        $objParticipanteRN = new ParticipanteRN();
        $arrObjParticipanteDTO = $objParticipanteRN->listarRN0189($objParticipanteDTO);

        if (count($arrObjParticipanteDTO)){

          $objContatoDTO = new ContatoDTO();
          $objContatoDTO->setBolExclusaoLogica(false);
          $objContatoDTO->retNumIdTipoContato();
          $objContatoDTO->retNumIdTipoContatoAssociado();
          $objContatoDTO->retNumIdContato();
          $objContatoDTO->retStrNome();
          $objContatoDTO->retStrNomeContatoAssociado();
          $objContatoDTO->retStrSigla();
          $objContatoDTO->retStrSiglaContatoAssociado();
          $objContatoDTO->retStrExpressaoCargo();
          $objContatoDTO->retStrExpressaoTratamentoCargo();
          $objContatoDTO->retStrExpressaoVocativoCargo();
          $objContatoDTO->retStrStaGenero();
          $objContatoDTO->retDblCpf();
          $objContatoDTO->retDblCnpj();
          $objContatoDTO->retDblCnpjContatoAssociado();
          $objContatoDTO->retDblRg();
          $objContatoDTO->retStrOrgaoExpedidor();
          $objContatoDTO->retStrMatricula();
          $objContatoDTO->retStrMatriculaOab();
          $objContatoDTO->retStrEmail();
          $objContatoDTO->retStrTelefoneFixo();
          $objContatoDTO->retStrTelefoneCelular();
          $objContatoDTO->retStrSitioInternet();

          $objContatoDTO->setNumIdContato(InfraArray::converterArrInfraDTO($arrObjParticipanteDTO, 'IdContato'), InfraDTO::$OPER_IN);

          $objContatoRN = new ContatoRN();
          $arrObjContatoDTO = InfraArray::indexarArrInfraDTO($objContatoRN->listarComEndereco($objContatoDTO), 'IdContato');

        }

        /* Interessados *******************************************************************************************/
        $arr = InfraArray::converterArrInfraDTO(InfraArray::filtrarArrInfraDTO($arrObjParticipanteDTO, 'StaParticipacao', ParticipanteRN::$TP_INTERESSADO), 'IdContato');
        $arrObjContatoDTOInteressados = null;
        if (count($arr)) {
          //manter ordem realizada no cadastro
          $arrObjContatoDTOInteressados = array();
          foreach($arr as $numIdContatoInteressado){
            if (isset($arrObjContatoDTO[$numIdContatoInteressado])){
              $arrObjContatoDTOInteressados[] = $arrObjContatoDTO[$numIdContatoInteressado];
            }
          }
        }

        /* Destinatários ******************************************************************************************/
        $arr = InfraArray::converterArrInfraDTO(InfraArray::filtrarArrInfraDTO($arrObjParticipanteDTO, 'StaParticipacao', ParticipanteRN::$TP_DESTINATARIO), 'IdContato');
        $arrObjContatoDTODestinatarios = null;
        if (count($arr)) {
          //manter ordem realizada no cadastro
          $arrObjContatoDTODestinatarios = array();
          foreach($arr as $numIdContatoDestinatario){
            if (isset($arrObjContatoDTO[$numIdContatoDestinatario])){
              $arrObjContatoDTODestinatarios[] = $arrObjContatoDTO[$numIdContatoDestinatario];
            }
          }
        }


        $numDestinatarios = count($arrObjContatoDTODestinatarios);

        if ($numDestinatarios) {

          $strTag = '';
          for ($i = 0; $i<$numDestinatarios; $i++) {
            if ($strTag!='') {
              $strTag .= ', ';
            }
            $strTag .= $arrObjContatoDTODestinatarios[$i]->getStrNome();
          }

          if ($strTag!='') {
            self::$arrTags['destinatarios'] = $strTag; //deprecated
            self::$arrTags['destinatarios_virgula_espaco'] = $strTag;
            self::$arrTags['destinatarios_virgula_espaco_maiusculas'] = InfraString::transformarCaixaAlta($strTag);
          }

          $strTag = '';
          for ($i = 0; $i<$numDestinatarios; $i++) {
            if ($strTag!='') {
              $strTag .= '<br />';
            }
            $strTag .= $arrObjContatoDTODestinatarios[$i]->getStrNome();
          }

          if ($strTag!='') {
            self::$arrTags['destinatarios_quebra_linha'] = $strTag;
          }

          $strTag = '';
          for ($i = 0; $i<$numDestinatarios; $i++) {
            if ($strTag!='') {
              $strTag .= '<br />';
            }
            $strTag .= InfraString::transformarCaixaAlta($arrObjContatoDTODestinatarios[$i]->getStrNome());
          }

          if ($strTag!='') {
            self::$arrTags['destinatarios_quebra_linha_maiusculas'] = $strTag;
          }

          $this->montarTagsContato($arrObjContatoDTODestinatarios[0], 'destinatario');
        }

        $numInteressados = count($arrObjContatoDTOInteressados);

        if ($numInteressados) {

          $strTag = '';
          for ($i = 0; $i<$numInteressados; $i++) {
            if ($strTag!='') {
              $strTag .= ', ';
            }
            $strTag .= $arrObjContatoDTOInteressados[$i]->getStrNome();
          }

          if ($strTag!='') {
            self::$arrTags['interessados'] = $strTag; //deprecated
            self::$arrTags['interessados_virgula_espaco'] = $strTag;
            self::$arrTags['interessados_virgula_espaco_maiusculas'] = InfraString::transformarCaixaAlta($strTag);
          }

          $strTag = '';
          for ($i = 0; $i<$numInteressados; $i++) {
            if ($strTag!='') {
              $strTag .= '<br />';
            }
            $strTag .= $arrObjContatoDTOInteressados[$i]->getStrNome();
          }

          if ($strTag!='') {
            self::$arrTags['interessados_quebra_linha']=$strTag;
          }

          $strTag = '';
          for ($i = 0; $i<$numInteressados; $i++) {
            if ($strTag!='') {
              $strTag .= '<br />';
            }
            $strTag .= InfraString::transformarCaixaAlta($arrObjContatoDTOInteressados[$i]->getStrNome());
          }

          if ($strTag!='') {
            self::$arrTags['interessados_quebra_linha_maiusculas']=$strTag;
          }

          $this->montarTagsContato($arrObjContatoDTOInteressados[0], 'interessado');
        }
      }

      if (array_key_exists($parStrTag, self::$arrTags) && self::$arrTags[$parStrTag]==null){
        self::$arrTags[$parStrTag]='';
      }

    } catch (Exception $e) {
      throw new InfraException('Módulo do Tramita: Erro obtendo parâmetros do editor.', $e);
    }
  }

  public function buscarImagemUpload($nomeArquivo)
  {
    $objImagemFormatoDTO = new ImagemFormatoDTO();
    $objImagemFormatoDTO->retStrFormato();
    $objImagemFormatoRN = new ImagemFormatoRN();
    $arrImagemPermitida = InfraArray::converterArrInfraDTO($objImagemFormatoRN->listar($objImagemFormatoDTO), 'Formato');
    if (in_array('jpg', $arrImagemPermitida) && !in_array('jpeg', $arrImagemPermitida)) { $arrImagemPermitida[] = 'jpeg';
    }

    $ext = pathinfo(DIR_SEI_TEMP . '/' . $nomeArquivo);

    $ret = print_r($ext, true);
    if (!in_array($ext['extension'], $arrImagemPermitida)) { return 'Tipo de Arquivo não permitido.';
    }

    return 'data:image/' . $ext['extension'] . ';base64,' . base64_encode(file_get_contents(DIR_SEI_TEMP . '/' . $nomeArquivo));
  }

  protected function recuperarVersaoControlado(EditorDTO $parObjEditorDTO)
  {
    try {
      if ($parObjEditorDTO->getDblIdDocumento()!=null) {
        $objDocumentoDTO = new DocumentoDTO();
        $objDocumentoDTO->retDblIdDocumento();
        $objDocumentoDTO->retStrNomeSerie();
        $objDocumentoDTO->retStrProtocoloDocumentoFormatado();
        $objDocumentoDTO->retStrProtocoloProcedimentoFormatado();
        $objDocumentoDTO->setDblIdDocumento($parObjEditorDTO->getDblIdDocumento());

        $objDocumentoRN = new DocumentoRN();
        $objDocumentoDTO = $objDocumentoRN->consultarRN0005($objDocumentoDTO);

        if ($objDocumentoDTO==null) {
          throw new InfraException('Módulo do Tramita: Documento não encontrado.');
        }
      }

      $objSecaoDocumentoDTO = new SecaoDocumentoDTO();
      $objSecaoDocumentoDTO->retNumIdSecaoDocumento();
      $objSecaoDocumentoDTO->retNumIdSecaoModelo();
      $objSecaoDocumentoDTO->retStrSinAssinatura();
      $objSecaoDocumentoDTO->setDblIdDocumento($parObjEditorDTO->getDblIdDocumento());
      $objSecaoDocumentoDTO->setOrdNumOrdem(InfraDTO::$TIPO_ORDENACAO_ASC);
      $objSecaoDocumentoDTO->setStrSinAssinatura('N');

      $objSecaoDocumentoRN = new SecaoDocumentoRN();
      $arrObjSecaoDocumentoDTO = $objSecaoDocumentoRN->listar($objSecaoDocumentoDTO);
      $arrNovoObjSecaoDocumentoDTO = array();

      foreach ($arrObjSecaoDocumentoDTO as $objSecaoDocumentoDTO) {

        $objVersaoSecaoDocumentoDTO = new VersaoSecaoDocumentoDTO();
        $objVersaoSecaoDocumentoDTO->retStrConteudo();
        $objVersaoSecaoDocumentoDTO->setNumIdSecaoDocumento($objSecaoDocumentoDTO->getNumIdSecaoDocumento());
        $objVersaoSecaoDocumentoDTO->setNumVersao($parObjEditorDTO->getNumVersao(), InfraDTO::$OPER_MENOR_IGUAL);
        $objVersaoSecaoDocumentoDTO->setOrdNumVersao(InfraDTO::$TIPO_ORDENACAO_DESC);
        $objVersaoSecaoDocumentoDTO->setNumMaxRegistrosRetorno(1);

        $objVersaoSecaoDocumentoRN = new VersaoSecaoDocumentoRN();
        $arrObjVersaoSecaoDocumentoDTO = $objVersaoSecaoDocumentoRN->listar($objVersaoSecaoDocumentoDTO);

        if (count($arrObjVersaoSecaoDocumentoDTO)>0) {
          $objNovoSecaoDocumentoDTO = new SecaoDocumentoDTO();
          $objNovoSecaoDocumentoDTO->setNumIdSecaoModelo($objSecaoDocumentoDTO->getNumIdSecaoDocumento());
          $objNovoSecaoDocumentoDTO->setNumIdSecaoModelo($objSecaoDocumentoDTO->getNumIdSecaoModelo());
          $objNovoSecaoDocumentoDTO->setStrConteudo($arrObjVersaoSecaoDocumentoDTO[0]->getStrConteudo());
          $arrNovoObjSecaoDocumentoDTO[] = $objNovoSecaoDocumentoDTO;
        }
      }
      $objEditorDTO = new EditorDTO();
      $objEditorDTO->setDblIdDocumento($parObjEditorDTO->getDblIdDocumento());
      $objEditorDTO->setNumIdBaseConhecimento(null);
      $objEditorDTO->setArrObjSecaoDocumentoDTO($arrNovoObjSecaoDocumentoDTO);
      $objEditorDTO->setStrSinForcarNovaVersao('S');
      $this->adicionarVersao($objEditorDTO);

    } catch (Exception $e) {
      throw new InfraException('Módulo do Tramita: Erro recuperando versão.', $e);
    }
  }

  public function validarTagsCriticas($arrImagemPermitida, $str, $dblIdDocumento = null)
  {

    $objInfraException = new InfraException();

    $arrRemoverTags = array('img', 'script', 'iframe', 'frame', 'embed', 'object', 'param', 'video', 'audio', 'button', 'input', 'select');

    foreach ($arrRemoverTags as $tag) {
      if ($str!=preg_replace("%<" . $tag . "[^>]*>(.*?)<\\/" . $tag . ">%si", "", $str) || $str!=preg_replace("%<" . $tag . "[^>]*\\/>%si", "", $str)) {
        switch ($tag) {
          case 'script':
            $objInfraException->lancarValidacao('Documento possui código de script oculto no conteúdo.');
              break;

          case 'img':
            if (count($arrImagemPermitida)==0) {
              $objInfraException->lancarValidacao('Documento possui imagem no conteúdo.');
            }

            $arrImagensConteudo = array();
            preg_match_all('/src="([^"]*)"/i', $str, $arrImagensConteudo);

            foreach ($arrImagensConteudo[1] as $strImagem) {
              $posIni = strpos($strImagem, '/');
              $posFim = strpos($strImagem, ';', $posIni);
              if ($posIni!==false && $posFim!==false) {
                $posIni = $posIni + 1;
                if (!in_array(InfraString::transformarCaixaBaixa(substr($strImagem, $posIni, ($posFim - $posIni))), $arrImagemPermitida)) {
                  $objInfraException->lancarValidacao('Documento possui imagem no formato "' . substr($strImagem, $posIni, ($posFim - $posIni)) . '" não permitido no conteúdo.');
                }
              } else {
                $objInfraException->lancarValidacao('Documento possui imagem não permitida no conteúdo.');
              }
            }
              break;

          case 'button':
          case 'input':
          case 'select':
            $objInfraException->lancarValidacao('Documento possui componente HTML não permitido no conteúdo.');
              break;
          case 'iframe':
            $objInfraException->lancarValidacao('Documento possui formulário oculto no conteúdo.');
              break;

          case 'frame':
            $objInfraException->lancarValidacao('Documento possui formulário no conteúdo.');
              break;
          case 'embed':
          case 'object':
          case 'param':
            $objInfraException->lancarValidacao('Documento possui um objeto não autorizado no conteúdo.');
              break;
          case 'video':
            $objInfraException->lancarValidacao('Documento possui vídeo no conteúdo.');
              break;
          case 'audio':
            $objInfraException->lancarValidacao('Documento possui áudio no conteúdo.');
        }
      }
    }

    SeiINT::validarXss($str, '', true, '', $dblIdDocumento);
  }

  public function processarLinksSei($str)
  {
    if ($str==null) { return null;
    }

    $ret = preg_replace_callback(self::$REGEXP_LINK_ASSINADO, "self::validarLink", $str);
    if($ret!==null){
      $str=$ret;
    } else {
      LogSEI::getInstance()->gravar('[processarLinksSei] REGEXP_LINK_ASSINADO: '.preg_last_error(), InfraLog::$DEBUG);
    }

    if (preg_match_all(self::$REGEXP_SPAN_LINKSEI, $str, $matches)>0){
      $arrIdProtocolo=array_unique($matches[1]);
      if (count($arrIdProtocolo)>0) {
        $objProtocoloDTO = new ProtocoloDTO();
        $objProtocoloRN = new ProtocoloRN();
        $objProtocoloDTO->setDblIdProtocolo($arrIdProtocolo, InfraDTO::$OPER_IN);
        $objProtocoloDTO->retDblIdProtocolo();
        $objProtocoloDTO->retStrProtocoloFormatado();
        $arrObjProtocoloDTO = $objProtocoloRN->listarRN0668($objProtocoloDTO);
        if (count($arrObjProtocoloDTO) > 0) {
          $this->arrProtocolos = InfraArray::converterArrInfraDTO($arrObjProtocoloDTO, 'ProtocoloFormatado', 'IdProtocolo');
        }
      }

    } else if(preg_last_error()!=0) {
      LogSEI::getInstance()->gravar('[processarLinksSei] Match_all REGEXP_SPAN_LINKSEI: '.preg_last_error(), InfraLog::$DEBUG);
    }
    $ret= preg_replace_callback(self::$REGEXP_SPAN_LINKSEI, 'self::processarLinkProtocolo', $str);
    if($ret!==null){
      $str=$ret;
    } else {
      LogSEI::getInstance()->gravar('[processarLinksSei] REGEXP_SPAN_LINKSEI: '.preg_last_error(), InfraLog::$DEBUG);
    }
    return $str;
  }

  /**
   * @param $matches  origem da REGEXP_LINK_ASSINADO ([0]=match [1]=acao [2]=id_protocolo [3]=id_sistema [4]=texto do link)
   * @return string
   */
  private function validarLink($matches)
  {
//    LogSEI::getInstance()->gravar('M3='.$matches[3].'  - '.SessaoSEI::getInstance()->getNumIdSistema());
    if($matches[3]!=SessaoSEI::getInstance()->getNumIdSistema()){
      return $matches[0];
    }
    if($matches[1]=='protocolo_visualizar'){
      return '<a class="ancoraSei" id="lnkSei'.$matches[2].'" style="text-indent:0;">'.$matches[4].'</a>';
    }
    return $matches[4];
  }


  /**
   * @param $matches  origem da REGEXP_SPAN_LINKSEI ([0]=match [1]=id_protocolo [2]==texto do link)
   * @return string
   */
  private function processarLinkProtocolo($matches)
  {
    if(!isset($this->arrProtocolos[$matches[1]]) || $this->arrProtocolos[$matches[1]]!=$matches[2] ) {
      //não foi encontrado protocolo correspondente, retorna somente o texto
      return $matches[2];
    } else {
      return '<span contenteditable="false" style="text-indent:0;"><a class="ancoraSei" id="lnkSei'.$matches[1].'" style="text-indent:0;">'.$matches[2].'</a></span>';
    }
  }

  private function limparTagsCriticas($str)
  {
    //remove tags mas deixa conteúdo
    $arrRemoverTags = array('html', 'body');
    foreach ($arrRemoverTags as $tag) {
      $str = preg_replace("%<" . $tag . "[^>]*>%si", "", $str);
      $str = preg_replace("%</" . $tag . "[^>]*>%si", "", $str);
    }
    //remove tags e todo o seu conteúdo
    $arrRemoverTags = array('img', 'script', 'iframe', 'frame', 'embed', 'object', 'param', 'video', 'audio', 'button', 'input', 'select', 'link', 'head', 'title');
    foreach ($arrRemoverTags as $tag) {
      $str = preg_replace("%<" . $tag . "[^>]*>(.*?)<\\/" . $tag . ">%si", "", $str);
      $str = preg_replace("%<" . $tag . "[^>]*\\/>%si", "", $str);
    }
    return $str;
  }

  protected function obterNumeroUltimaVersaoConectado(DocumentoDTO $objDocumentoDTO)
  {
    try {

      $objSecaoDocumentoDTO = new SecaoDocumentoDTO();
      $objSecaoDocumentoDTO->retNumIdSecaoDocumento();
      $objSecaoDocumentoDTO->setDblIdDocumento($objDocumentoDTO->getDblIdDocumento());

      $objSecaoDocumentoRN = new SecaoDocumentoRN();
      $arrObjSecaoDocumentoDTO = $objSecaoDocumentoRN->listar($objSecaoDocumentoDTO);

      $objVersaoSecaoDocumentoDTO = new VersaoSecaoDocumentoDTO();
      $objVersaoSecaoDocumentoDTO->retNumVersao();
      $objVersaoSecaoDocumentoDTO->setNumIdSecaoDocumento(InfraArray::converterArrInfraDTO($arrObjSecaoDocumentoDTO, 'IdSecaoDocumento'), InfraDTO::$OPER_IN);
      $objVersaoSecaoDocumentoDTO->setStrSinUltima('S');
      $objVersaoSecaoDocumentoDTO->setNumMaxRegistrosRetorno(1);
      $objVersaoSecaoDocumentoDTO->setOrdNumVersao(InfraDTO::$TIPO_ORDENACAO_DESC);

      $objVersaoSecaoDocumentoRN = new VersaoSecaoDocumentoRN();
      $objVersaoSecaoDocumentoDTO = $objVersaoSecaoDocumentoRN->consultar($objVersaoSecaoDocumentoDTO);

      if ($objVersaoSecaoDocumentoDTO!=null) {
        return $objVersaoSecaoDocumentoDTO->getNumVersao();
      }

      return null;

    } catch (Exception $e) {
      throw new InfraException('Módulo do Tramita: Erro obtendo número da última versão.', $e);
    }
  }

  protected function recuperarLinkAcessoExternoControlado(DocumentoDTO $parObjDocumentoDTO){
    try {
      $objInfraParametro = new InfraParametro(BancoSEI::getInstance());
      $objUsuarioDTO = new UsuarioDTO();
      $objUsuarioDTO->retNumIdContato();
      $objUsuarioDTO->setNumIdUsuario($objInfraParametro->getValor('ID_USUARIO_SEI'));

      $objUsuarioRN = new UsuarioRN();
      $objUsuarioDTO = $objUsuarioRN->consultarRN0489($objUsuarioDTO);

      $objDocumentoDTO = new DocumentoDTO();
      $objDocumentoRN = new DocumentoRN();
      $objDocumentoDTO->setDblIdDocumento($parObjDocumentoDTO->getDblIdDocumento());
      $objDocumentoDTO->retDblIdProcedimento();
      $objDocumentoDTO->retStrProtocoloProcedimentoFormatado();
      $objDocumentoDTO = $objDocumentoRN->consultarRN0005($objDocumentoDTO);

      $objAcessoExternoDTO = new AcessoExternoDTO();
      $objAcessoExternoDTO->retNumIdAcessoExterno();
      $objAcessoExternoDTO->setDblIdProtocoloAtividade($objDocumentoDTO->getDblIdProcedimento());
      $objAcessoExternoDTO->setNumIdContatoParticipante($objUsuarioDTO->getNumIdContato());
      $objAcessoExternoDTO->setStrStaTipo(AcessoExternoRN::$TA_SISTEMA);

      $objAcessoExternoRN = new AcessoExternoRN();
      $objAcessoExternoDTO = $objAcessoExternoRN->consultar($objAcessoExternoDTO);

      if ($objAcessoExternoDTO == null) {

        $objParticipanteDTO = new ParticipanteDTO();
        $objParticipanteDTO->setDblIdProtocolo($objDocumentoDTO->getDblIdProcedimento());
        $objParticipanteDTO->setNumIdContato($objUsuarioDTO->getNumIdContato());
        $objParticipanteDTO->setStrStaParticipacao(ParticipanteRN::$TP_ACESSO_EXTERNO);
        $objParticipanteDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
        $objParticipanteDTO->setNumSequencia(0);

        $objParticipanteRN = new ParticipanteRN();
        $objParticipanteDTO = $objParticipanteRN->cadastrarRN0170($objParticipanteDTO);

        $objAcessoExternoDTO = new AcessoExternoDTO();
        $objAcessoExternoDTO->setNumIdParticipante($objParticipanteDTO->getNumIdParticipante());
        $objAcessoExternoDTO->setStrStaTipo(AcessoExternoRN::$TA_SISTEMA);

        $objAcessoExternoRN = new AcessoExternoRN();
        $objAcessoExternoDTO = $objAcessoExternoRN->cadastrar($objAcessoExternoDTO);
      }

      $numIdAcessoExterno = SessaoSEIExterna::getInstance()->getNumIdAcessoExterno();
      SessaoSEIExterna::getInstance()->configurarAcessoExterno($objAcessoExternoDTO->getNumIdAcessoExterno());
      $ret = '<a target="_blank" href="' . SessaoSEIExterna::getInstance()->assinarLink(ConfiguracaoSEI::getInstance()->getValor('SEI', 'URL') . '/processo_acesso_externo_consulta.php?id_acesso_externo=' . $objAcessoExternoDTO->getNumIdAcessoExterno()) . '" style="text-decoration:none">' . $objDocumentoDTO->getStrProtocoloProcedimentoFormatado() . '</a>';
      SessaoSEIExterna::getInstance()->configurarAcessoExterno($numIdAcessoExterno);

      return $ret;

    }catch(Exception $e){
      throw new InfraException('Módulo do Tramita: Erro gerando link de acesso externo.', $e);
    }

  }

  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
  private function montarTagsContato(ContatoDTO $objContatoDTO, $strTipo){

    if ($objContatoDTO->getStrStaGenero()==ContatoRN::$TG_MASCULINO){
      self::$arrTags['artigo_'.$strTipo.'_minuscula'] = 'o';
      self::$arrTags['artigo_'.$strTipo.'_maiuscula'] = 'O';
    }else if ($objContatoDTO->getStrStaGenero()==ContatoRN::$TG_FEMININO){
      self::$arrTags['artigo_'.$strTipo.'_minuscula'] = 'a';
      self::$arrTags['artigo_'.$strTipo.'_maiuscula'] = 'A';
    }

    if (($strTag = $objContatoDTO->getStrNome())!=''){
      self::$arrTags['nome_'.$strTipo] = $strTag;
    }

    if (($strTag = InfraString::transformarCaixaAlta($objContatoDTO->getStrNome()))!=''){
      self::$arrTags['nome_'.$strTipo.'_maiusculas'] = $strTag;
    }

    if (($strTag = $objContatoDTO->getStrExpressaoTratamentoCargo())!=''){
      self::$arrTags['tratamento_'.$strTipo] = $strTag;
    }

    if (($strTag = $objContatoDTO->getStrExpressaoVocativoCargo())!=''){
      self::$arrTags['vocativo_'.$strTipo] = $strTag;
    }

    if (($strTag = $objContatoDTO->getStrExpressaoCargo())!=''){
      self::$arrTags['cargo_'.$strTipo] = $strTag;
    }

    if (($strTag = $objContatoDTO->getStrNomeContatoAssociado())!=''){
      self::$arrTags['origem_'.$strTipo] = $strTag;
      self::$arrTags['nome_contexto_'.$strTipo] = $strTag;
      self::$arrTags['nome_pessoa_juridica_associada_'.$strTipo] = $strTag;
    }

    $strTag = $objContatoDTO->getStrEndereco();
    if ($strTag != '') {
      self::$arrTags['endereco_'.$strTipo] = $strTag;
    }

    $strTag = $objContatoDTO->getStrComplemento();
    if ($strTag != '') {
      self::$arrTags['complemento_endereco_'.$strTipo] = $strTag;
    }

    $strTag = $objContatoDTO->getStrBairro();
    if ($strTag!='') {
      self::$arrTags['bairro_'.$strTipo] = $strTag;
    }

    $strTag = $objContatoDTO->getStrNomeCidade();
    if ($strTag!='') {
      self::$arrTags['cidade_'.$strTipo] = $strTag;
    }

    $strTag = $objContatoDTO->getStrCep();
    if ($strTag!='') {
      self::$arrTags['cep_'.$strTipo] = $strTag;
    }

    $strTag = '';
    if ($objContatoDTO->getStrSiglaUf()!='') {
      $strTag = ' - ' . $objContatoDTO->getStrSiglaUf();
    }

    if ($strTag!='') {
      self::$arrTags['hifen_uf_'.$strTipo] = $strTag;
    }

    $strTag = $objContatoDTO->getStrSiglaUf();
    if ($strTag!='') {
      self::$arrTags['sigla_uf_'.$strTipo] = $strTag;
    }

    $strTag = $objContatoDTO->getStrNomePais();
    if ($strTag!='') {
      self::$arrTags['pais_'.$strTipo] = $strTag;
    }

    if (($strTag = $objContatoDTO->getDblCpf())!=''){
      self::$arrTags['cpf_'.$strTipo] = InfraUtil::formatarCpf($strTag);
    }

    if (($strTag = $objContatoDTO->getDblCnpj())!=''){
      self::$arrTags['cnpj_'.$strTipo] = InfraUtil::formatarCnpj($strTag);
    }

    if (($strTag = $objContatoDTO->getDblCnpjContatoAssociado())!=''){
      self::$arrTags['cnpj_pessoa_juridica_associada_'.$strTipo] = InfraUtil::formatarCnpj($strTag);
    }

    if (($strTag = $objContatoDTO->getStrSitioInternet())!=''){
      self::$arrTags['sitio_internet_'.$strTipo] = $strTag;
    }

    if (($strTag = $objContatoDTO->getDblRg())!=''){
      self::$arrTags['rg_'.$strTipo] = $strTag;
    }

    if (($strTag = $objContatoDTO->getStrOrgaoExpedidor())!=''){
      self::$arrTags['orgao_expedidor_rg_'.$strTipo] = $strTag;
    }

    if (($strTag = $objContatoDTO->getStrMatricula())!=''){
      self::$arrTags['matricula_'.$strTipo] = $strTag;
    }

    if (($strTag = $objContatoDTO->getStrMatriculaOab())!=''){
      self::$arrTags['matricula_oab_'.$strTipo] = $strTag;
    }

    if (($strTag = $objContatoDTO->getStrEmail())!=''){
      self::$arrTags['email_'.$strTipo] = $strTag;
    }

    if (($strTag = $objContatoDTO->getStrTelefoneFixo())!=''){
      self::$arrTags['telefone_fixo_'.$strTipo] = $strTag;
    }

    if (($strTag = $objContatoDTO->getStrTelefoneCelular())!=''){
      self::$arrTags['telefone_celular_'.$strTipo] = $strTag;
    }

  }

  public static function converterHTML($strConteudo){
    return str_replace(array('°','º','ª','¹','²','³','£','¢','§','¬'),
                      array('&deg;','&ordm;','&ordf;','&sup1;','&sup2;','&sup3;','&pound;','&cent;','&sect;','&not;'),
                      InfraString::acentuarHTML($strConteudo));
  }

  private function montarCarimboPublicacao($strTextoPublicacao){
    return '<div style="font-weight: 500; text-align: left; font-size: 9pt; border: 2px solid #777; position: absolute; left: 67%; padding: 4px;">' . nl2br($strTextoPublicacao) . '</div>';
  }
}
