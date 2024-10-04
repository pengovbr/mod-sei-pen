<?php

/**
 * Execution Groups
 * @group execute_parallel_group1
 */
class TramiteProcessoContendoDocumentoInternoExternoTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoInternoTeste;
    public static $documentoExternoTeste;
    public static $protocoloTeste;

    /**
     * Teste de trâmite externo de processo contendo um documento interno e outro externo
     *
     * @group envio
     * @large
     * 
     * @Depends CenarioBaseTestCase::setUpBeforeClass
     *
     * @return void
     */
    public function test_tramitar_processo_contendo_documento_interno_externo()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoInternoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        self::$documentoExternoTeste = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

        // 1 - Cadastrar novo processo de teste
        $objProtocoloDTO = $this->cadastrarProcessoFixture(self::$processoTeste);
        self::$protocoloTeste = $objProtocoloDTO->getStrProtocoloFormatado(); 

        // 2 - Incluir e assinar documentos no processo
        $this->cadastrarDocumentoInternoFixture(self::$documentoInternoTeste, $objProtocoloDTO->getDblIdProtocolo());

        // 3 - Incluir documento externo ao processo
        $this->cadastrarDocumentoExternoFixture(self::$documentoExternoTeste, $objProtocoloDTO->getDblIdProtocolo());

        // 4 - Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        // 5 - Abrir processo
        $this->abrirProcesso(self::$protocoloTeste);

        // 6 - Trâmitar Externamento processo para órgão/unidade destinatária
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
     * @depends test_tramitar_processo_contendo_documento_interno_externo
     *
     * @return void
     */
    public function test_verificar_origem_processo_contendo_documento_interno_externo()
    {
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->abrirProcesso(self::$protocoloTeste);

        // 7 - Verificar se situação atual do processo está como bloqueado
        $this->waitUntil(function($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(mb_convert_encoding("Processo em trâmite externo para ", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        // 8 - Validar se recibo de trâmite foi armazenado para o processo (envio e conclusão)
        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTeste, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);

        // 9 - Validar histórico de trâmite do processo
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);

        // 10 - Verificar se processo está na lista de Processos Tramitados Externamente
        $this->validarProcessosTramitados(self::$protocoloTeste, $orgaosDiferentes);
    }


    /**
     * Teste de verificação do correto recebimento do processo contendo apenas um documento interno (gerado)
     *
     * @group verificacao_recebimento
     * @large
     *
     * @depends test_verificar_origem_processo_contendo_documento_interno_externo
     *
     * @return void
     */
    public function test_verificar_destino_processo_contendo_documento_interno_externo()
    {
        $strProtocoloTeste = self::$protocoloTeste;
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);

        // 11 - Abrir protocolo na tela de controle de processos
        $this->abrirProcesso(self::$protocoloTeste);
        $listaDocumentos = $this->paginaProcesso->listarDocumentos();

        // 12 - Validar dados  do processo
        $strTipoProcesso = mb_convert_encoding("Tipo de processo no órgão de origem: ", 'UTF-8', 'ISO-8859-1');
        $strTipoProcesso .= self::$processoTeste['TIPO_PROCESSO'];
        self::$processoTeste['OBSERVACOES'] = $orgaosDiferentes ? $strTipoProcesso : null;
        $this->validarDadosProcesso(self::$processoTeste['DESCRICAO'], self::$processoTeste['RESTRICAO'], self::$processoTeste['OBSERVACOES'], array(self::$processoTeste['INTERESSADOS']));

        // 13 - Verificar recibos de trâmite
        $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);

        // 14 - Validar dados do documento
        $this->assertTrue(count($listaDocumentos) == 2);
        $this->validarDadosDocumento($listaDocumentos[0], self::$documentoInternoTeste, self::$destinatario);
        $this->validarDadosDocumento($listaDocumentos[1], self::$documentoExternoTeste, self::$destinatario);
    }
}
