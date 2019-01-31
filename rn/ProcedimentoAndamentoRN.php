<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

/**
 * Controla o log de estados da expadição de um procedimento pelo modulo SEI
 *
 * @autor Join Tecnologia
 */
class ProcedimentoAndamentoRN extends InfraRN {

    protected $isSetOpts = false;
    protected $dblIdProcedimento;
    protected $dblIdTramit;
    protected $numTarefa;



    /**
     * Invés de aproveitar o singleton do BancoSEI criamos uma nova instância para
     * não ser afetada pelo transation
     *
     * @return Infra[Driver]
     */
    protected function inicializarObjInfraIBanco(){
        return BancoSEI::getInstance();
    }


    public function setOpts($dblIdProcedimento = 0, $dblIdTramit = 0, $numTarefa){

        $this->dblIdProcedimento = $dblIdProcedimento;
        $this->dblIdTramit = $dblIdTramit;
        $this->numTarefa = $numTarefa;
        $this->isSetOpts = true;
    }

    /**
     * Adiciona um novo andamento à um procedimento que esta sendo expedido para outra unidade
     *
     * @param ProcedimentoAndamentoDTO $parProcedimentoAndamentoDTO
     */
    protected function cadastrarControlado($parProcedimentoAndamentoDTO){

        if($this->isSetOpts === false) {
            throw new InfraException('Log do cadastro de procedimento não foi configurado');
        }

        $strMensagem = ($parProcedimentoAndamentoDTO->isSetStrMensagem()) ? $parProcedimentoAndamentoDTO->getStrMensagem() : 'Não informado';
        $strSituacao = ($parProcedimentoAndamentoDTO->isSetStrSituacao()) ? $parProcedimentoAndamentoDTO->getStrSituacao() : 'N';

        $hash = md5($this->dblIdProcedimento . $strMensagem);
        $objProcedimentoAndamentoDTO = new ProcedimentoAndamentoDTO();
        $objProcedimentoAndamentoDTO->setStrSituacao($strSituacao);
        $objProcedimentoAndamentoDTO->setDthData(date('d/m/Y H:i:s'));
        $objProcedimentoAndamentoDTO->setDblIdProcedimento($this->dblIdProcedimento);
        $objProcedimentoAndamentoDTO->setDblIdTramite($this->dblIdTramit);
        $objProcedimentoAndamentoDTO->setStrSituacao($strSituacao);
        $objProcedimentoAndamentoDTO->setStrMensagem($strMensagem);
        $objProcedimentoAndamentoDTO->setStrHash($hash);
        $objProcedimentoAndamentoDTO->setNumTarefa($this->numTarefa);

        $objProcedimentoAndamentoBD = new ProcedimentoAndamentoBD($this->getObjInfraIBanco());
        $objProcedimentoAndamentoBD->cadastrar($objProcedimentoAndamentoDTO);
    }
}
