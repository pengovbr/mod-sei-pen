<?php

/**
 * Testes de trâmite de processos contendo um documento cancelado
 *
 * Este mesmo documento deve ser recebido e assinalado com cancelado no destinatário e
 * a devolução do mesmo processo não deve ser impactado pela inserção de outros documentos
 */
class TramiteProcessoContendoDocumentoCanceladoTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste1;
    public static $documentoTeste2;
    public static $documentoTeste3;
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
        self::$documentoTeste2 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        // Cadastrar novo processo de teste e incluir documentos relacionados
        $this->paginaBase->navegarParaControleProcesso();
        self::$protocoloTeste = $this->cadastrarProcesso(self::$processoTeste);
        $this->cadastrarDocumentoExterno(self::$documentoTeste1);     

        // Realiza o cancelamento de um documento interno do processo
        $this->cadastrarDocumentoInterno(self::$documentoTeste2);
        $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);

        //Tramitar internamento para liberação da funcionalidade de cancelar
        $this->tramitarProcessoInternamenteParaCancelamento(self::$remetente['SIGLA_UNIDADE'], self::$remetente['SIGLA_UNIDADE_SECUNDARIA'], self::$processoTeste);

        $this->navegarParaCancelarDocumento(1);
        $this->paginaCancelarDocumento->cancelar("Motivo de teste");

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
            $this->atualizarTramitesPEN();
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(utf8_encode("Processo em trâmite externo para "), $paginaProcesso->informacao());
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

        $strTipoProcesso = utf8_encode("Tipo de processo no órgão de origem: ");
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
        $this->assertEquals(2, count($listaDocumentosProcessoPrincipal));
        $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[0], self::$documentoTeste1, self::$destinatario);
        $this->validarDocumentoCancelado($listaDocumentosProcessoPrincipal[1]);
    }


    /**
     * Teste de trâmite externo de processo realizando a devolução para a mesma unidade de origem
     *
     * @group envio
     * @large
     *
     * @depends test_verificar_destino_processo
     *
     * @return void
     */
    public function test_devolucao_processo_para_origem()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        // Definição de dados de teste do processo principal
        self::$documentoTeste3 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        // Incluir novos documentos relacionados
        $this->abrirProcesso(self::$protocoloTeste);
        $this->cadastrarDocumentoExterno(self::$documentoTeste3);

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
     * @depends test_devolucao_processo_para_origem
     *
     * @return void
     */
    public function test_verificar_devolucao_origem_processo()
    {
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->abrirProcesso(self::$protocoloTeste);

        $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $this->atualizarTramitesPEN();
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(utf8_encode("Processo em trâmite externo para "), $paginaProcesso->informacao());
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
     * Teste de verificação da correta devolução do processo no destinatário
     *
     * @group verificacao_recebimento
     * @large
     *
     * @depends test_verificar_devolucao_origem_processo
     *
     * @return void
     */
    public function test_verificar_devolucao_destino_processo()
    {
        $strProtocoloTeste = self::$protocoloTeste;
        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);
        $this->abrirProcesso(self::$protocoloTeste);

        $this->validarDadosProcesso(
            self::$processoTeste['DESCRICAO'],
            self::$processoTeste['RESTRICAO'],
            self::$processoTeste['OBSERVACOES'],
            array(self::$processoTeste['INTERESSADOS'])
        );

        $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);

        // Validação dos dados do processo principal
        $listaDocumentosProcesso = $this->paginaProcesso->listarDocumentos();
        $this->assertEquals(3, count($listaDocumentosProcesso));
        $this->validarDadosDocumento($listaDocumentosProcesso[0], self::$documentoTeste1, self::$destinatario);
        $this->validarDocumentoCancelado($listaDocumentosProcesso[1]);
        $this->validarDadosDocumento($listaDocumentosProcesso[2], self::$documentoTeste3, self::$destinatario);
    }
}
