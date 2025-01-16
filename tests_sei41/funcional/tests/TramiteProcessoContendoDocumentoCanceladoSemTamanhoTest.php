<?php

/**
 * Testes de trâmite de processos contendo um documento cancelado
 *
 * Este mesmo documento deve ser recebido e assinalado com cancelado no destinatário e
 * a devolução do mesmo processo não deve ser impactado pela inserção de outros documentos
 *
 * Execution Groups
 * @group execute_parallel_group1
 */
class TramiteProcessoContendoDocumentoCanceladoSemTamanhoTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste1;
    public static $protocoloTeste;

    /**
     * Teste inicial de trâmite de um processo contendo um documento cancelado
     *
     * @group envio
     * @large
     *
     * @Depends CenarioBaseTestCase::setUpBeforeClass
     *
     * @return void
     */
    public function test_tramitar_processo_contendo_documento_cancelado()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Definição de dados de teste do processo principal
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);        
        self::$documentoTeste1 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

        $objProtocoloPrincipalDTO = $this->cadastrarProcessoFixture(self::$processoTeste);

        $this->cadastrarDocumentoExternoFixture(self::$documentoTeste1, $objProtocoloPrincipalDTO->getDblIdProtocolo());
        self::$protocoloTeste = $objProtocoloPrincipalDTO->getStrProtocoloFormatado(); 

        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->abrirProcesso(self::$protocoloTeste);

        //Tramitar internamento para liberação da funcionalidade de cancelar
        $this->tramitarProcessoInternamenteParaCancelamento(self::$remetente['SIGLA_UNIDADE'], self::$remetente['SIGLA_UNIDADE_SECUNDARIA'], [ 'PROTOCOLO' => self::$protocoloTeste ]);

        $this->navegarParaCancelarDocumento(0);
        $this->paginaCancelarDocumento->cancelar("Motivo de teste");

        $processo=self::$processoTeste;
        
        // Trâmitar Externamento processo para órgão/unidade destinatária
        $this->tramitarProcessoExternamente(
            self::$protocoloTeste,
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false
        );
    }


    /**
     * Teste de verificação do correto envio do processo no sistema remetente
     *
     * @group verificacao_envio
     * @large
     *
     * @depends test_tramitar_processo_contendo_documento_cancelado
     *
     * @return void
     */
    public function test_verificar_origem_processo()
    {
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->abrirProcesso(self::$protocoloTeste);

        $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(mb_convert_encoding("Processo em trâmite externo para ", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTeste, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);
        $this->validarProcessosTramitados(self::$protocoloTeste, $orgaosDiferentes);
    }

    /**
     * Teste de verificação do correto recebimento do processo com documento cancelado no destinatário
     *
     * @group verificacao_recebimento
     * @large
     *
     * @depends test_verificar_origem_processo
     *
     * @return void
     */
    public function test_verificar_destino_processo()
    {
        $strProtocoloTeste = self::$protocoloTeste;
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);
        $this->abrirProcesso(self::$protocoloTeste);

        $strTipoProcesso = mb_convert_encoding("Tipo de processo no órgão de origem: ", 'UTF-8', 'ISO-8859-1');
        $strTipoProcesso .= self::$processoTeste['TIPO_PROCESSO'];
        $strObservacoes = $orgaosDiferentes ? $strTipoProcesso : null;
        $this->validarDadosProcesso(
            self::$processoTeste['DESCRICAO'],
            self::$processoTeste['RESTRICAO'],
            $strObservacoes,
            array(self::$processoTeste['INTERESSADOS'])
        );

        $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);

        // Validação dos dados do processo principal
        $listaDocumentosProcessoPrincipal = $this->paginaProcesso->listarDocumentos();
        $this->assertEquals(1, count($listaDocumentosProcessoPrincipal));
        $this->validarDocumentoCancelado($listaDocumentosProcessoPrincipal[0]);

    }

}
