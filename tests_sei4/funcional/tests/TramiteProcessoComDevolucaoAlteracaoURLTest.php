<?php

/**
  * Execution Groups
  * @group execute_alone_group3
 */
class TramiteProcessoComDevolucaoAlteracaoURLTest extends FixtureCenarioBaseTestCase
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
        $result=$bancoOrgaoA->query("SELECT texto FROM tarja_assinatura where sta_tarja_assinatura=? and sin_ativo=?", array("V","S"));
        if ($bancoOrgaoA->getBdType()!="oci") {
            $strTarja=$result[0]["texto"];
        }else{
            $strTarja=stream_get_contents($result[0]["TEXTO"]);
        }
        $strTarja = str_replace($arrControleURL['novo'],$arrControleURL['antigo'], $strTarja);
        $bancoOrgaoA->execute("update tarja_assinatura set texto=? where sta_tarja_assinatura=? and sin_ativo=?", array($strTarja,"V","S"));
        
    }


    /**
     * Teste de trâmite externo de processo com devolução para a mesma unidade de origem
     *
     * @group envio
     * @large
     *
     * @Depends CenarioBaseTestCase::setUpBeforeClass
     * 
     * @return void
     */
    public function test_tramitar_processo_da_origem()
    {

        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        $documentos = array(self::$documentoTeste1);
        $this->realizarTramiteExternoComValidacaoNoRemetenteFixture(self::$processoTeste, $documentos, self::$remetente, self::$destinatario);
        self::$protocoloTeste = self::$processoTeste["PROTOCOLO"];
    }


    /**
     * Teste de verificação do correto recebimento do processo no destinatário
     *
     * @group verificacao_recebimento
     * @large
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
     * Teste de trâmite externo de processo com devolução para a mesma unidade de origem
     *
     * @group envio
     * @large
     *
     * @depends test_verificar_destino_processo_para_devolucao
     *
     * @return void
     */
    public function test_devolucao_processo_para_origem()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$documentoTeste3 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        $documentos = array(self::$documentoTeste3);
        putenv("DATABASE_HOST=org2-database");
        $this->realizarTramiteExternoComValidacaoNoRemetenteFixture(self::$processoTeste, $documentos, self::$remetente, self::$destinatario);
    }


    /**
     * Teste de verificação do correto recebimento do processo no destinatário
     *
     * @group verificacao_recebimento
     * @large
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
     * Teste de trâmite externo de processo com devolução para a mesma unidade de origem
     *
     * @group envio
     * @large
     * 
     * @depends test_verificar_processo_apos_devolucao
     *
     * @return void
     */
    public function test_tramitar_processo_da_origem_novo_url()
    {
        
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$documentoTeste5 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        putenv("DATABASE_HOST=org1-database");

        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);        
        $result=$bancoOrgaoA->query("SELECT texto FROM tarja_assinatura where sta_tarja_assinatura=? and sin_ativo=?", array("V","S"));
        if ($bancoOrgaoA->getBdType()!="oci") {
            $strTarja=$result[0]["texto"];
        }else{
            $strTarja=stream_get_contents($result[0]["TEXTO"]);
        }

        $arrControleURL=[
            "antigo"=>"[servidor_php]",
            "novo"=>"servidor.gov.br"
        ];

        $strTarja = str_replace($arrControleURL['antigo'],$arrControleURL['novo'], $strTarja);
        $bancoOrgaoA->execute("update tarja_assinatura set texto=? where sta_tarja_assinatura=? and sin_ativo=?", array($strTarja,"V","S"));

        $documentos = array(self::$documentoTeste5);
        $this->realizarTramiteExternoComValidacaoNoRemetenteFixture(self::$processoTeste, $documentos, self::$remetente, self::$destinatario);
        self::$protocoloTeste = self::$processoTeste["PROTOCOLO"];
    }
    

    /**
     * Teste de verificação do correto recebimento do processo no destinatário
     *
     * @group verificacao_recebimento
     * @large
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

    /**
     * Teste de realizar reprodução de último tramite
     *
     * @group envio
     * @large
     *
     * @depends test_verificar_destino_processo_para_devolucao_apos_troca_url
     *
     * @return void
     */
    public function test_realizar_pedido_reproducao_ultimo_tramite()
    {
        $strProtocoloTeste = self::$protocoloTeste;
        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);
        
        // 11 - Reproduzir último trâmite
        $this->abrirProcesso($strProtocoloTeste);
        $resultadoReproducao = $this->paginaProcesso->reproduzirUltimoTramite();
        $this->assertStringContainsString(mb_convert_encoding("Reprodução de último trâmite executado com sucesso!", 'UTF-8', 'ISO-8859-1'), $resultadoReproducao);
        $this->refresh();
        $this->waitUntil(function ($testCase) {
            sleep(5);
            $testCase->refresh();
            $testCase->paginaProcesso->navegarParaConsultarAndamentos();
            $mensagemTramite = mb_convert_encoding("Reprodução de último trâmite iniciado para o protocolo ".  $strProtocoloTeste, 'UTF-8', 'ISO-8859-1');
            $testCase->assertTrue($testCase->paginaConsultarAndamentos->contemTramite($mensagemTramite));
            return true;
        }, PEN_WAIT_TIMEOUT);

    }

    /**
     * Teste para verificar a reprodução de último tramite no destinatario
     *
     * @group envio
     * @large
     *
     * @depends test_verificar_destino_processo_para_devolucao_apos_troca_url
     *
     * @return void
     */
    public function test_reproducao_ultimo_tramite()
    {
        $strProtocoloTeste = self::$protocoloTeste;

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->abrirProcesso($strProtocoloTeste);
       
        $this->waitUntil(function ($testCase) {
            sleep(5);
            $testCase->refresh();
            $testCase->paginaProcesso->navegarParaConsultarAndamentos();
            $mensagemTramite = mb_convert_encoding("Reprodução de último trâmite recebido na entidade", 'UTF-8', 'ISO-8859-1');
            $testCase->assertTrue($testCase->paginaConsultarAndamentos->contemTramite($mensagemTramite));
            return true;
        }, PEN_WAIT_TIMEOUT);

    }

    /**
     * Teste para verificar a reprodução de último tramite no remetente
     *
     * @group envio
     * @large
     *
     * @depends test_verificar_destino_processo_para_devolucao_apos_troca_url
     *
     * @return void
     */
    public function test_reproducao_ultimo_tramite_remetente_finalizado()
    {
        $strProtocoloTeste = self::$protocoloTeste;

        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);

        // 11 - Abrir protocolo na tela de controle de processos
        $this->abrirProcesso($strProtocoloTeste);

        $this->waitUntil(function ($testCase) {
            sleep(5);
            $testCase->refresh();
            $testCase->paginaProcesso->navegarParaConsultarAndamentos();
            $mensagemTramite = mb_convert_encoding("Reprodução de último trâmite finalizado para o protocolo ".  $strProtocoloTeste, 'UTF-8', 'ISO-8859-1');
            $testCase->assertTrue($testCase->paginaConsultarAndamentos->contemTramite($mensagemTramite));
            return true;
        }, PEN_WAIT_TIMEOUT);

        $documentos = array(self::$documentoTeste1, self::$documentoTeste3, self::$documentoTeste5);
        $this->sairSistema();
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, $documentos, self::$destinatario);
    }


}
