<?php
/**
 * @author Join Tecnologia
 */
require_once dirname(__FILE__) . '/../../../SEI.php';

class PenAtividadeRN extends AtividadeRN {

    private $statusPesquisa = true;

    public function setStatusPesquisa($statusPesquisa) {

        $this->statusPesquisa = $statusPesquisa;
    }

    /**
     * Retorna a atividade da ação do tramite, ou seja, se estava enviando
     * ou recebendo um tramite
     *
     * @param int $numIdTramite
     * @return object (bool bolReciboExiste, string mensagem)
     */
    public static function retornaAtividadeDoTramiteFormatado($numIdTramite, $numIdEstrutura, $numIdTarefa){

        $objReturn = (object)array(
            'strMensagem' => '',
            'bolReciboExiste' => false
        );

        $objBancoSEI = BancoSEI::getInstance();

        $objTramiteDTO = new TramiteDTO();
        $objTramiteDTO->setNumIdTramite($numIdTramite);
        $objTramiteDTO->retStrNumeroRegistro();

        $objTramiteBD = new TramiteBD($objBancoSEI);
        $objTramiteDTO = $objTramiteBD->consultar($objTramiteDTO);

        if(!empty($objTramiteDTO)) {

            $objProcessoEletronicoDTO = new ProcessoEletronicoDTO();
            $objProcessoEletronicoDTO->setStrNumeroRegistro($objTramiteDTO->getStrNumeroRegistro());
            $objProcessoEletronicoDTO->retDblIdProcedimento();

            $objProcessoEletronicoDB = new ProcessoEletronicoBD($objBancoSEI);
            $objProcessoEletronicoDTO = $objProcessoEletronicoDB->consultar($objProcessoEletronicoDTO);

            $objAtividadeDTO = new AtividadeDTO();
            $objAtividadeDTO->setDblIdProtocolo($objProcessoEletronicoDTO->getDblIdProcedimento());
            $objAtividadeDTO->setNumIdTarefa($numIdTarefa);
            $objAtividadeDTO->retNumIdAtividade();

            $objAtividadeBD = new AtividadeBD($objBancoSEI);
            $arrObjAtividadeDTO = $objAtividadeBD->listar($objAtividadeDTO);



            if(!empty($arrObjAtividadeDTO)) {

                $arrNumAtividade = InfraArray::converterArrInfraDTO($arrObjAtividadeDTO, 'IdAtividade', 'IdAtividade');

                switch($numIdTarefa){
                    case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_EXPEDIDO):
                        $strMensagem = 'Trâmite externo do Processo %s para %s';
                        $strNome = 'UNIDADE_DESTINO';

                        $objReciboTramiteDTO = new ReciboTramiteDTO();
                        $objReciboTramiteDTO->setNumIdTramite($numIdTramite);
                        $objReciboTramiteDTO->retNumIdTramite();
                        $objReciboTramiteBD = new ReciboTramiteRecebidoBD($objBancoSEI);
                        $objReturn->bolReciboExiste = ($objReciboTramiteBD->contar($objReciboTramiteDTO) > 0) ? true : false;
                        break;

                    case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_DOCUMENTO_AVULSO_RECEBIDO):
                        $strMensagem = 'Recebimento do Documento %s remetido por %s';
                        $strNome = 'ENTIDADE_ORIGEM';

                        $objReciboTramiteDTO = new ReciboTramiteRecebidoDTO();
                        $objReciboTramiteDTO->setNumIdTramite($numIdTramite);
                        $objReciboTramiteBD = new ReciboTramiteBD($objBancoSEI);
                        $objReturn->bolReciboExiste = ($objReciboTramiteBD->contar($objReciboTramiteDTO) > 0) ? true : false;
                        break;

                    case ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO):
                        $strMensagem = 'Recebimento do Processo %s remetido por %s';
                        $strNome = 'ENTIDADE_ORIGEM';

                        $objReciboTramiteDTO = new ReciboTramiteRecebidoDTO();
                        $objReciboTramiteDTO->setNumIdTramite($numIdTramite);

                        $objReciboTramiteBD = new ReciboTramiteRecebidoBD($objBancoSEI);
                        $objReturn->bolReciboExiste = ($objReciboTramiteBD->contar($objReciboTramiteDTO) > 0) ? true : false;
                        break;
                }

                $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
                $objAtributoAndamentoDTO->setNumIdAtividade($arrNumAtividade, InfraDTO::$OPER_IN);
                $objAtributoAndamentoDTO->setStrNome($strNome);
                $objAtributoAndamentoDTO->setStrIdOrigem($numIdEstrutura);
                $objAtributoAndamentoDTO->retStrValor();

                $objAtributoAndamentoBD = new AtributoAndamentoBD($objBancoSEI);
                $arrAtributoAndamentoDTO = $objAtributoAndamentoBD->listar($objAtributoAndamentoDTO);

                //$objAtributoAndamentoDTO = current($arrAtributoAndamentoDTO);
                $objAtributoAndamentoDTO = $arrAtributoAndamentoDTO[0];
                //print_r($objAtributoAndamentoDTO);

                //echo "objAtributoAndamentoDTO->getStrValor(): " . $objAtributoAndamentoDTO->getStrValor();
                //die();
                $obProtocoloDTO = new ProtocoloDTO();
                $obProtocoloDTO->setDblIdProtocolo($objProcessoEletronicoDTO->getDblIdProcedimento());
                $obProtocoloDTO->retStrProtocoloFormatado();

                $objProtocoloBD = new ProtocoloBD($objBancoSEI);
                $obProtocoloDTO = $objProtocoloBD->consultar($obProtocoloDTO);
                $objReturn->strMensagem = sprintf($strMensagem, $obProtocoloDTO->getStrProtocoloFormatado(), $objAtributoAndamentoDTO->getStrValor());
            }
        }

        return $objReturn;
    }
}
