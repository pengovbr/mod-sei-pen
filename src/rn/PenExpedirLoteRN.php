<?php

require_once DIR_SEI_WEB . '/SEI.php';

class PenExpedirLoteRN extends InfraRN
{

    private $barraProgresso;
    private $objExpedirProcedimentoRN;
    private $objPenDebug;

    public function __construct()
    {
        parent::__construct();

        //TODO: Remover criao de objetos de negcio no construtor da classe para evitar problemas de performance desnecessrios
        $this->objProcessoEletronicoRN = new ProcessoEletronicoRN();
        $this->objProcedimentoAndamentoRN = new ProcedimentoAndamentoRN();
        $this->objExpedirProcedimentoRN = new ExpedirProcedimentoRN();

        $this->barraProgresso = new InfraBarraProgresso();
        $this->barraProgresso->setNumMin(0);
        $this->objPenDebug = DebugPen::getInstance("PROCESSAMENTO");
    }

    protected function inicializarObjInfraIBanco()
    {
        return BancoSEI::getInstance();
    }

    public function gravarLogDebug($parStrMensagem, $parNumIdentacao=0, $parBolLogTempoProcessamento=true)
    {
        $this->objPenDebug->gravar($parStrMensagem, $parNumIdentacao, $parBolLogTempoProcessamento);
    }
    
    private function validarParametrosLote(InfraException $objInfraException, PenExpedirLoteDTO $objLoteDTO)
    {
        if(!isset($objLoteDTO)){
            $objInfraException->adicionarValidacao('Parâmetro $objLoteDTO não informado.');
        }

        //TODO: Validar se repositrio de origem foi informado
        if (InfraString::isBolVazia($objLoteDTO->getNumIdRepositorioOrigem())){
            $objInfraException->adicionarValidacao('Identificação do repositório de estruturas da unidade atual não informado.');
        }

        //TODO: Validar se unidade de origem foi informado
        if (InfraString::isBolVazia($objLoteDTO->getNumIdUnidadeOrigem())){
            $objInfraException->adicionarValidacao('Identificação da unidade atual no repositório de estruturas organizacionais não informado.');
        }

        //TODO: Validar se repositrio foi devidamente informado
        if (InfraString::isBolVazia($objLoteDTO->getNumIdRepositorioDestino())){
            $objInfraException->adicionarValidacao('Repositório de estruturas organizacionais não informado.');
        }

        //TODO: Validar se unidade foi devidamente informada
        if (InfraString::isBolVazia($objLoteDTO->getNumIdUnidadeDestino()) || InfraString::isBolVazia($objLoteDTO->getStrUnidadeDestino())){
            $objInfraException->adicionarValidacao('Unidade de destino não informado.');
        }

        //TODO: Validar se usuário foi devidamente informada
        if (InfraString::isBolVazia($objLoteDTO->getNumIdUsuario())){
            $objInfraException->adicionarValidacao('Usuário não informado.');
        }
        
        //TODO: Validar se usuário foi devidamente informada
        if (InfraString::isBolVazia($objLoteDTO->getDthRegistro())){
            $objInfraException->adicionarValidacao('Data do registro não informada.');
        }

    }

    protected function cadastrarLoteControlado(PenExpedirLoteDTO $objPenExpedirLoteDTO)
    {
        try {
            //Valida Permissão
            SessaoSEI::getInstance()->validarAuditarPermissao('pen_expedir_lote', __METHOD__, $objPenExpedirLoteDTO);

            $this->barraProgresso->exibir();
            $this->barraProgresso->setStrRotulo(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_VALIDACAO);          

            //Obtém o tamanho total da barra de progreso
            $nrTamanhoTotalBarraProgresso = count($objPenExpedirLoteDTO->getArrIdProcedimento());

            //Atribui o tamanho máximo da barra de progresso
            $this->barraProgresso->setNumMax($nrTamanhoTotalBarraProgresso);

            //Exibe a barra de progresso após definir o seu tamanho
            $this->barraProgresso->mover(ProcessoEletronicoINT::NEE_EXPEDICAO_ETAPA_PROCEDIMENTO);

            $objPenExpedirLoteBD = new PenExpedirLoteBD($this->getObjInfraIBanco());

            $objInfraException = new InfraException();
            $this->validarParametrosLote($objInfraException, $objPenExpedirLoteDTO);
            $ret = $objPenExpedirLoteBD->cadastrar($objPenExpedirLoteDTO);

            if ($objPenExpedirLoteDTO->isSetArrIdProcedimento()) {

                $objPenLoteProcedimentoRN = new PenLoteProcedimentoRN();
                $objPenLoteProcedimentoDTO = new PenLoteProcedimentoDTO(); 

                foreach ($objPenExpedirLoteDTO->getArrIdProcedimento() as $dblIdProcedimento) {
                    try {

                        $objProcedimentoDTO = $this->objExpedirProcedimentoRN->consultarProcedimento($dblIdProcedimento);
                        $this->barraProgresso->setStrRotulo(sprintf(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_PROCEDIMENTO, $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado()));

                        //Bloquea o processo para atualização
                        $idAtividadeExpedicao = $this->objExpedirProcedimentoRN->bloquearProcedimentoExpedicao($objPenExpedirLoteDTO, $dblIdProcedimento);

                        $objPenLoteProcedimentoDTO->setNumIdLote($ret->getNumIdLote());
                        $objPenLoteProcedimentoDTO->setDblIdProcedimento($dblIdProcedimento);
                        $objPenLoteProcedimentoDTO->setNumIdAndamento(ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_NAO_INICIADO);
                        $objPenLoteProcedimentoDTO->setNumIdAtividade($idAtividadeExpedicao);
                        $objPenLoteProcedimentoRN->cadastrarLoteProcedimento($objPenLoteProcedimentoDTO);

                        $this->barraProgresso->mover($this->barraProgresso->getNumMax());
                        $this->barraProgresso->setStrRotulo(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_CONCLUSAO);
                    } catch (\Exception $e) {
                        //Realiza o desbloqueio do processo
                        try {
                            $this->objExpedirProcedimentoRN->desbloquearProcessoExpedicao($objPenExpedirLoteDTO->getDblIdProcedimento());
                        } catch (InfraException $ex) {
                        }
                        throw $e;
                    }
                }
            }
        } catch (\Exception $e) {
            throw new InfraException('Falha de comunicação com o serviços de integração. Por favor, tente novamente mais tarde.', $e);
        }
    }

}
