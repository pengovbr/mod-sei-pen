<?php

/**
 *
 * Execution Groups
 * @group execute_parallel_group1
 */
class TramiteProcessoValidacaoBotaoIncluirTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste;
    public static $protocoloTeste;


    /**
     * 
     * @Depends TramiteProcessoGrandeTest::tearDownAfterClass
     *
     * @return void
     */
    public static function setUpBeforeClass() :void {

        // Altera status de qualquer Bloco aberto
        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);
        $bancoOrgaoA->execute("update md_pen_bloco set sta_estado=? where sta_estado=?", array('C', 'A'));

        // Limpa os mapeamentos de unidade
        $bancoOrgaoA->execute("delete from md_pen_unidade", array());
    }      
        
    public static function tearDownAfterClass() :void {

        // Recadastra os mapeamentos da unidade
        putenv("DATABASE_HOST=org1-database");
        $penMapUnidadesFixture = new \PenMapUnidadesFixture();
        $penMapUnidadesFixture->carregar([
            'IdUnidade' => 110000001,
            'Id' => CONTEXTO_ORGAO_A_ID_ESTRUTURA,
            'Sigla' => CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA,
            'Nome' => CONTEXTO_ORGAO_A_NOME_UNIDADE,
        ]);
    
        $penMapUnidadesFixture->carregar([
            'IdUnidade' => 110000002,
            'Id' => CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA,
            'Sigla' => CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA,
            'Nome' => CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA,
        ]);
    }   

    /**
     * Teste de trâmite externo de processo com restrição de acesso
     *
     * @group envio
     * @large
     * 
     *
     * @return void
     */
    public function test_tramitar_processo_restrito()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        // Cadastrar novo processo de teste
        $objProtocoloDTO = $this->cadastrarProcessoFixture(self::$processoTeste);
        self::$protocoloTeste = $objProtocoloDTO->getStrProtocoloFormatado();

        // Incluir e assinar documento no processo
        $this->cadastrarDocumentoInternoFixture(self::$documentoTeste, $objProtocoloDTO->getDblIdProtocolo());

        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        // Abrir processo
        $this->abrirProcesso(self::$protocoloTeste);

        $this->assertNotTrue($this->paginaProcesso->validarBotaoExiste(mb_convert_encoding("Incluir Processo no Bloco de Trâmite", 'UTF-8', 'ISO-8859-1')));
    }
}
