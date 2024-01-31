<?php

class DocumentoConteudoFixture extends FixtureBase
{
    protected $objDocumentoConteudoDTO;

    public function __construct()
    {
        $this->objDocumentoConteudoDTO = new \DocumentoConteudoDTO();
    }
 
    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }
    
    protected function cadastrar($dados = [])
    {
        $this->objDocumentoConteudoDTO->setDblIdDocumento($dados['IdDocumento'] ?: null);
        $this->objDocumentoConteudoDTO->setStrConteudo($dados['Conteudo'] ?: '<html>Conteudo</html>');
        $this->objDocumentoConteudoDTO->setStrConteudoAssinatura($dados['ConteudoAssinatura'] ?: null);
        $this->objDocumentoConteudoDTO->setStrCrcAssinatura($dados['CrcAssinatura'] ?: null);
        $this->objDocumentoConteudoDTO->setStrQrCodeAssinatura($dados['QrCodeAssinatura'] ?: null);

        $objProtocoloConteudoDB = new \DocumentoConteudoBD(\BancoSEI::getInstance());
        $objProtocoloConteudoDB->cadastrar($this->objDocumentoConteudoDTO);

        return $this->objDocumentoConteudoDTO;
    }
}