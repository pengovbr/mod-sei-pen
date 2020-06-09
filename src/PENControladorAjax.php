<?php

class PENControladorAjax implements ISeiControladorAjax {

	public function processar($strAcaoAjax){
		$xml = null;

    switch($_GET['acao_ajax']){

      case 'pen_unidade_auto_completar_expedir_procedimento':
        $arrObjEstruturaDTO = (array)ProcessoEletronicoINT::autoCompletarEstruturas($_POST['id_repositorio'], $_POST['palavras_pesquisa']);
        
        if(count($arrObjEstruturaDTO) > 0) {
            $xml = InfraAjax::gerarXMLItensArrInfraDTO($arrObjEstruturaDTO, 'NumeroDeIdentificacaoDaEstrutura', 'Nome'); 
        }
        else {            
            throw new InfraException("Unidade não Encontrada.", $e); 
        }
        break;

      case 'pen_apensados_auto_completar_expedir_procedimento':   
        //TODO: Validar parâmetros passados via ajax     
        $dblIdProcedimentoAtual = $_POST['id_procedimento_atual'];
        $numIdUnidadeAtual = SessaoSEI::getInstance()->getNumIdUnidadeAtual();
        $arrObjProcedimentoDTO = ProcessoEletronicoINT::autoCompletarProcessosApensados($dblIdProcedimentoAtual, $numIdUnidadeAtual, $_POST['palavras_pesquisa']);
        $xml = InfraAjax::gerarXMLItensArrInfraDTO($arrObjProcedimentoDTO, 'IdProtocolo', 'ProtocoloFormatadoProtocolo');
        break;
    
        case 'pen_procedimento_expedir_validar':
            require_once dirname(__FILE__) . '/pen_procedimento_expedir_validar.php';
            break;
      }          

    return $xml;
	}
}