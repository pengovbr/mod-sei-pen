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

  /**
   * Concate as siglas das hierarquias no nome da unidade
   *
   * @param array(EstruturaDTO) $estruturas
   * @return array
   */
  public static function gerarHierarquiaEstruturasAutoCompletar($estruturas = array())
  {

    if (empty($estruturas['itens'])) {
      return $estruturas;
    }

    foreach ($estruturas['itens'] as &$estrutura) {
      if ($estrutura->isSetArrHierarquia()) {
        $nome  = $estrutura->getStrNome();
        $nome .= ' - ';

        $array = array($estrutura->getStrSigla());
        foreach ($estrutura->getArrHierarquia() as $sigla) {
          if (trim($sigla) !== '' && !in_array($sigla, array(
            'PR', 'PE', 'UNIAO'
          ))) {
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

  public static function autoCompletarEstruturasAutoCompletar($idRepositorioEstrutura, $strPalavrasPesquisa, $bolPermiteEnvio = false)
  {

    $objConecaoWebServerRN = new ProcessoEletronicoRN();
    $arrObjEstruturas = $objConecaoWebServerRN->listarEstruturasAutoCompletar(
      $idRepositorioEstrutura,
      $strPalavrasPesquisa,
      null,
      null,
      null,
      null,
      null,
      true,
      $bolPermiteEnvio
    );

    return static::gerarHierarquiaEstruturasAutoCompletar($arrObjEstruturas);
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
}
