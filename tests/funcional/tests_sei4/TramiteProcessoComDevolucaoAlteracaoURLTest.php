<?php

class TramiteProcessoComDevolucaoAlteracaoURLTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste1;
    public static $documentoTeste2;
    public static $documentoTeste3;
    public static $documentoTeste4;
    public static $documentoTeste5;
    public static $protocoloTeste;


    public static function tearDownAfterClass() :void {


        $arrControleURL=[
            "antigo"=>"[servidor_php]",
            "novo"=>"servidor.gov.br"
        ];

        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);        
        $result=$bancoOrgaoA->query("SELECT texto FROM sei.tarja_assinatura where sta_tarja_assinatura=? and sin_ativo=?;", array("V","S"));
        $strTarja=$result[0]["texto"];
        $strTarja = str_replace($arrControleURL['novo'],$arrControleURL['antigo'], $strTarja);
        $bancoOrgaoA->execute("update sei.tarja_assinatura set texto=? where sta_tarja_assinatura=? and sin_ativo=?  ", array($strTarja,"V","S"));
        
    }


    /**
     * Teste de tr�mite externo de processo com devolu��o para a mesma unidade de origem
     *
     * @group envio
     *
     * @return void
     */
    public function test_tramitar_processo_da_origem()
    {

        // Configura��o do dados para teste do cen�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        $documentos = array(self::$documentoTeste1);
        $this->realizarTramiteExternoComValidacaoNoRemetente(self::$processoTeste, $documentos, self::$remetente, self::$destinatario);
        self::$protocoloTeste = self::$processoTeste["PROTOCOLO"];
    }


    /**
     * Teste de verifica��o do correto recebimento do processo no destinat�rio
     *
     * @group verificacao_recebimento
     *
     * @depends test_tramitar_processo_da_origem
     *
     * @return void
     */
    public function test_verificar_destino_processo_para_devolucao()
    {
        $documentos = array(self::$documentoTeste1);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, $documentos, self::$destinatario);
    }


    /**
     * Teste de tr�mite externo de processo com devolu��o para a mesma unidade de origem
     *
     * @group envio
     *
     * @depends test_verificar_destino_processo_para_devolucao
     *
     * @return void
     */
    public function test_devolucao_processo_para_origem()
    {
        // Configura��o do dados para teste do cen�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$documentoTeste3 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        $documentos = array(self::$documentoTeste3);
        $this->realizarTramiteExternoComValidacaoNoRemetente(self::$processoTeste, $documentos, self::$remetente, self::$destinatario);
    }


    /**
     * Teste de verifica��o do correto recebimento do processo no destinat�rio
     *
     * @group verificacao_recebimento
     *
     * @depends test_devolucao_processo_para_origem
     *
     * @return void
     */
    public function test_verificar_processo_apos_devolucao()
    {
        $documentos = array(self::$documentoTeste1,self::$documentoTeste3);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, $documentos, self::$destinatario);
    }


     /**
     * Teste de tr�mite externo de processo com devolu��o para a mesma unidade de origem
     *
     * @group envio
     * 
     * @depends test_verificar_processo_apos_devolucao
     *
     * @return void
     */
    public function test_tramitar_processo_da_origem_novo_url()
    {
        
        // Configura��o do dados para teste do cen�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$documentoTeste5 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);        
        $result=$bancoOrgaoA->query("SELECT texto FROM sei.tarja_assinatura where sta_tarja_assinatura=? and sin_ativo=?;", array("V","S"));
        $strTarja=$result[0]["texto"];

        $arrControleURL=[
            "antigo"=>"[servidor_php]",
            "novo"=>"servidor.gov.br"
        ];

        $strTarja = str_replace($arrControleURL['antigo'],$arrControleURL['novo'], $strTarja);
        $bancoOrgaoA->execute("update sei.tarja_assinatura set texto=? where sta_tarja_assinatura=? and sin_ativo=?  ", array($strTarja,"V","S"));

        $documentos = array(self::$documentoTeste5);
        $this->realizarTramiteExternoComValidacaoNoRemetente(self::$processoTeste, $documentos, self::$remetente, self::$destinatario);
        self::$protocoloTeste = self::$processoTeste["PROTOCOLO"];
    }


    /**
     * Teste de verifica��o do correto recebimento do processo no destinat�rio
     *
     * @group verificacao_recebimento
     *
     * @depends test_tramitar_processo_da_origem_novo_url
     *
     * @return void
     */
    public function test_verificar_destino_processo_para_devolucao_apos_troca_url()
    {
        $documentos = array(self::$documentoTeste1, self::$documentoTeste3, self::$documentoTeste5);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, $documentos, self::$destinatario);
    }



}
