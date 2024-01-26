<?php

class ProtocoloFixture extends \FixtureBase
{
    protected $objProtocoloDTO;
    protected $objProtocoloRN;

    public function __construct()
    {
        $this->objProtocoloRN = new \ProtocoloRN();
        $this->objProtocoloDTO = new \ProtocoloDTO();
    }
 
    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    protected function cadastrar($dados = [])
    {
        $documento = $dados['documento'] ?: false;

        $dados['IdProtocolo'] = $this->getObjInfraIBanco()->getValorSequencia('seq_protocolo');

        $strProtocoloFormatado = $this->getProtocoloTeste($dados['IdProtocolo'], $documento);
        $strProtocoloSemFormato = preg_replace("/[^0-9]/", "", $strProtocoloFormatado);

        $this->objProtocoloDTO->setDblIdProtocolo($dados['IdProtocolo']);
        $this->objProtocoloDTO->setStrIdProtocoloFederacao($dados["IdProtocoloFederacao"] ?: null);
        $this->objProtocoloDTO->setDblIdProtocoloAgrupador($dados["IdProtocolo"] ?: 2);
        $this->objProtocoloDTO->setStrProtocoloFormatado($dados["ProtocoloFormatado"] ?: $strProtocoloFormatado);
        $this->objProtocoloDTO->setStrProtocoloFormatadoPesquisa($strProtocoloSemFormato);
        $this->objProtocoloDTO->setStrProtocoloFormatadoPesqInv(strrev($strProtocoloSemFormato));
        $this->objProtocoloDTO->setStrDescricao($dados["Descricao"] ?: "teste");
        $this->objProtocoloDTO->setStrStaProtocolo($dados["StaProtocolo"] ?: $this->objProtocoloRN::$TP_PROCEDIMENTO);
        $this->objProtocoloDTO->setStrStaEstado($dados["StaEstado"] ?: $this->objProtocoloRN::$TE_NORMAL);
        $this->objProtocoloDTO->setStrStaNivelAcessoGlobal(
            $dados["StaNivelAcessoGlobal"] ?: $this->objProtocoloRN::$NA_PUBLICO
        );
        $this->objProtocoloDTO->setStrStaNivelAcessoLocal(
            $dados["StaNivelAcessoLocal"] ?: $this->objProtocoloRN::$NA_PUBLICO
        );
        $this->objProtocoloDTO->setStrStaNivelAcessoOriginal($dados["StaNivelAcessoOriginal"] ?: null);
        $this->objProtocoloDTO->setNumIdUnidadeGeradora($dados["IdUnidadeGeradora"] ?: "110000001");
        $this->objProtocoloDTO->setNumIdUsuarioGerador($dados["IdUsuarioGerador"] ?: "100000001");
        $this->objProtocoloDTO->setDtaGeracao($dados["Geracao"] ?: InfraData::getStrDataAtual());
        $this->objProtocoloDTO->setDtaInclusao($dados["Inclusao"] ?: InfraData::getStrDataAtual());
        $this->objProtocoloDTO->setStrCodigoBarras($dados["CodigoBarras"] ?: "XXXXXXXXXXXXXXXXXXXXXXXX");
        $this->objProtocoloDTO->setNumIdHipoteseLegal($dados["IdHipoteseLegal"] ?: null);
        $this->objProtocoloDTO->setStrStaGrauSigilo($dados["StaGrauSigilo"] ?: null);
        $this->objProtocoloDTO->retDblIdProtocolo();
        $this->objProtocoloDTO->retStrProtocoloFormatado();
        
        $objProtocoloDB = new \ProtocoloBD(\BancoSEI::getInstance());
        $objProtocoloDB->cadastrar($this->objProtocoloDTO);

        return $this->objProtocoloDTO;
    }

    protected function cadastrarVariados($dados = [])
    {
        foreach ($dados as $dado) {
            $this->cadastrar($dado);
        }
    }

    private function getProtocoloTeste($protocolo, $documento = false)
    {
        $numSequencial = str_pad($protocolo, 6, 0, STR_PAD_LEFT);
        if ($documento == true) {
            return $numSequencial;
        }

        $anoAtual = date('Y');

        return "99990.{$numSequencial}/{$anoAtual}-00";
    }
}
