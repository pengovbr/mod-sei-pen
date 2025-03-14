<?php

/**
 * 
 * Execution Groups
 * @group execute_alone_group3
 */
class TramiteProcessoTamanhoAcimaLimiteDestinoTest extends FixtureCenarioBaseTestCase
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

        // Redu��o de limite m�ximo de tamanho de documento externo
        $bancoOrgaoB = new DatabaseUtils(CONTEXTO_ORGAO_B);
        $bancoOrgaoB->execute("update infra_parametro set valor = ? where nome = ?", array(2, 'SEI_TAM_MB_DOC_EXTERNO'));

    }      
        
    public static function tearDownAfterClass() :void {

        // Ajuste do tamanho m�ximo de arquivo externo permitido para padr�o
        $bancoOrgaoB = new DatabaseUtils(CONTEXTO_ORGAO_B);
        $bancoOrgaoB->execute("update infra_parametro set valor = ? where nome = ?", array(50, 'SEI_TAM_MB_DOC_EXTERNO'));

    }   

    /**
     * Teste de tr�mite externo de processo contendo documento com tamanho acima do limite no destinatario
     *
     * @group envio
     * @large
     * 
     * @Depends CenarioBaseTestCase::setUpBeforeClass
     *
     * @return void
     */
    public function test_tramitar_processo_tamanho_acima_limite_destino()
    {
        // Configura��o do dados para teste do cen�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste = $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_003.pdf');
        
        // Cadastrar novo processo de teste
        $objProtocoloDTO = $this->cadastrarProcessoFixture(self::$processoTeste);
        self::$protocoloTeste = $objProtocoloDTO->getStrProtocoloFormatado();

        $this->cadastrarDocumentoExternoFixture(self::$documentoTeste, $objProtocoloDTO->getDblIdProtocolo());

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->abrirProcesso(self::$protocoloTeste);

        $this->tramitarProcessoExternamente(
                self::$protocoloTeste, self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'],
                self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);
    }


    /**
     * Teste de verifica��o do correto envio do processo no sistema remetente
     *
     * @group verificacao_envio
     * @large
     *
     * @depends test_tramitar_processo_tamanho_acima_limite_destino
     *
     * @return void
     */
    public function test_verificar_origem_processo_tamanho_acima_limite_destino()
    {
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->abrirProcesso(self::$protocoloTeste);

        // 6 - Verificar se situa��o atual do processo est� como bloqueado
        $this->waitUntil(function($testCase)  {
            sleep(5);
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringContainsString(mb_convert_encoding("Processo aberto somente na unidade", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
            $testCase->assertTrue($paginaProcesso->processoAberto());
            $testCase->assertFalse($paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        // Validar hist�rico de tr�mite do processo
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, false, true, "O tamanho m�ximo geral permitido para documentos externos");

        // Validar se recibo de tr�mite foi armazenado para o processo (envio e conclus�o)
        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Tr�mite externo do Processo %s para %s", self::$protocoloTeste, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, false);
        $this->validarProcessosTramitados(self::$protocoloTeste, false);

        //Verifica se os �cones de alerta de recusa foram adicionados e se o processo continua aberto na unidade
        $this->paginaBase->navegarParaControleProcesso();
        $this->assertTrue($this->paginaControleProcesso->contemProcesso(self::$protocoloTeste));
        $this->assertTrue($this->paginaControleProcesso->contemAlertaProcessoRecusado(self::$protocoloTeste));
    }


    /**
     * Teste de verifica��o do correto recebimento do processo contendo apenas um documento interno (gerado)
     *
     * @group verificacao_recebimento
     * @large
     *
     * @depends test_verificar_origem_processo_tamanho_acima_limite_destino
     *
     * @return void
     */
    public function test_verificar_destino_processo_tamanho_acima_limite_destino()
    {
        $this->realizarValidacaoNAORecebimentoProcessoNoDestinatario(self::$destinatario, self::$processoTeste);
    }
}
