<?php

class TramiteProcessoTamanhoAcimaLimiteDestinoTest extends CenarioBaseTestCase
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

        // Redução de limite máximo de tamanho de documento externo
        $bancoOrgaoB = new DatabaseUtils(CONTEXTO_ORGAO_B);
        $bancoOrgaoB->execute("update infra_parametro set valor = ? where nome = ?", array(2, 'SEI_TAM_MB_DOC_EXTERNO'));

    }      
        
    public static function tearDownAfterClass() :void {

        // Ajuste do tamanho máximo de arquivo externo permitido para padrão
        $bancoOrgaoB = new DatabaseUtils(CONTEXTO_ORGAO_B);
        $bancoOrgaoB->execute("update infra_parametro set valor = ? where nome = ?", array(50, 'SEI_TAM_MB_DOC_EXTERNO'));

    }   

    /**
     * Teste de trâmite externo de processo contendo documento com tamanho acima do limite no destinatario
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
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste = $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_003.pdf');

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        self::$protocoloTeste = $this->cadastrarProcesso(self::$processoTeste);
        $this->cadastrarDocumentoExterno(self::$documentoTeste);
        $this->tramitarProcessoExternamente(
                self::$protocoloTeste, self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'],
                self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);
    }


    /**
     * Teste de verificação do correto envio do processo no sistema remetente
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

        // 6 - Verificar se situação atual do processo está como bloqueado
        $this->waitUntil(function($testCase)  {
            sleep(5);
            $this->atualizarTramitesPEN();
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringContainsString(utf8_encode("Processo aberto somente na unidade"), $paginaProcesso->informacao());
            $testCase->assertTrue($paginaProcesso->processoAberto());
            $testCase->assertFalse($paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        // Validar histórico de trâmite do processo
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, false, true, "O tamanho máximo geral permitido para documentos externos");

        // Validar se recibo de trâmite foi armazenado para o processo (envio e conclusão)
        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTeste, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, false);
        $this->validarProcessosTramitados(self::$protocoloTeste, false);

        //Verifica se os ícones de alerta de recusa foram adicionados e se o processo continua aberto na unidade
        $this->paginaBase->navegarParaControleProcesso();
        $this->assertTrue($this->paginaControleProcesso->contemProcesso(self::$protocoloTeste));
        $this->assertTrue($this->paginaControleProcesso->contemAlertaProcessoRecusado(self::$protocoloTeste));
    }


    /**
     * Teste de verificação do correto recebimento do processo contendo apenas um documento interno (gerado)
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
