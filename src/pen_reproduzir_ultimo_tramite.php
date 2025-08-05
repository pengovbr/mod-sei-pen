<?
try {

  require_once DIR_SEI_WEB . '/SEI.php';

  session_start();

  //////////////////////////////////////////////////////////////////////////////
  //InfraDebug::getInstance()->setBolLigado(false);
  //InfraDebug::getInstance()->setBolDebugInfra(true);
  //InfraDebug::getInstance()->limpar();
  //////////////////////////////////////////////////////////////////////////////

  SessaoSEI::getInstance()->validarLink();
  // SessaoSEI::getInstance()->validarPermissao($_GET['acao']);
  // PaginaSEI::getInstance()->salvarCamposPost(array('selTipoDocumentoPadrao'));

  $strParametros = '';

  $arrComandos = array();
  $objPenParametroDTO = new PenParametroDTO();
  $objPenParametroRN = new PenParametroRN();

  switch ($_GET['acao']) {
    case 'pen_reproduzir_ultimo_tramite':
      try {
        $objProcessoEletronicoRN = new ProcessoEletronicoRN();
        $result = $objProcessoEletronicoRN->reproduzirUltimoTramite($_GET['nre'], $_GET['id_repositorio'], $_GET['id_estrutura']);
        $error = is_array($result) && $result['error'] == true ? true : false;
        if (!$error) {
          $ticketComponentesDigitais = $result->tramiteDeProcessoCriado->ticketParaEnvioDeComponentesDigitais;
          $novoIDT = $result->tramiteDeProcessoCriado->IDT;
          $nre = $result->tramiteDeProcessoCriado->NRE;
          $strProtocoloFormatado = $result->tramiteDeProcessoCriado->processosComComponentesDigitaisSolicitados[0]->protocolo;
          if ($strProtocoloFormatado == null) {
            $objProtocoloDTO = new ProtocoloDTO();
            $objProtocoloDTO->setDblIdProtocolo($_GET['id_procedimento']);
            $objProtocoloDTO->retStrProtocoloFormatado();
            $objProtocoloRN = new ProtocoloRN();
            $objProtocoloDTO = $objProtocoloRN->consultarRN0186($objProtocoloDTO);
            $strProtocoloFormatado = $objProtocoloDTO->getStrProtocoloFormatado();
          }
          
          $arrObjAtributoAndamentoDTO = array();

          $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
          $objAtributoAndamentoDTO->setStrNome('PROTOCOLO_FORMATADO');
          $objAtributoAndamentoDTO->setStrValor($strProtocoloFormatado);
          $objAtributoAndamentoDTO->setStrIdOrigem($_GET['id_procedimento']);
          $arrObjAtributoAndamentoDTO[] = $objAtributoAndamentoDTO;

          $idTarefa = ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_REPRODUCAO_ULTIMO_TRAMITE_EXPEDIDO);

          $objAtividadeDTO = new AtividadeDTO();
          $objAtividadeDTO->setDblIdProtocolo($_GET['id_procedimento']);
          $objAtividadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
          $objAtividadeDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
          $objAtividadeDTO->setNumIdTarefa($idTarefa);
          $objAtividadeDTO->setArrObjAtributoAndamentoDTO($arrObjAtributoAndamentoDTO);

          $objAtividadeRN = new AtividadeRN();
          $objAtividadeRN->gerarInternaRN0727($objAtividadeDTO);
        }
        


        $msgAlert = !$error ? 'Reprodução de último trâmite executado com sucesso! NRE:'.$nre.', IDT:'.$novoIDT.',TicketEnvioComponentesDigitais:'.$ticketComponentesDigitais : 'Erro! '.mb_convert_encoding($result['message'], 'ISO-8859-1', 'UTF-8');
        echo '
        <script type="text/javascript">
          alert("'.$msgAlert.'");
        </script>
        ';
      } catch (Exception $e) {
        PaginaSEI::getInstance()->processarExcecao($e);
      }
      die;
      break;
    default:
      throw new InfraException("Módulo do Tramita: Ação '" . $_GET['acao'] . "' não reconhecida.");
  }
} catch (Exception $e) {
  PaginaSEI::getInstance()->processarExcecao($e);
}
?>
