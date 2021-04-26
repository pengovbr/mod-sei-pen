<?php

/**
 * Testes de tr�mite de processos contendo um documento movido
 *
 * Este mesmo documento deve ser recebido e assinalado como cancelado no destinat�rio e
 * a devolu��o do mesmo processo n�o deve ser impactado pela inser��o de outros documentos
 */
class TramiteProcessoContendoDocumentoMovidoTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste1;
    public static $documentoTeste2;
    public static $documentoTeste3;
    public static $documentoTeste4;
    public static $protocoloTeste;

    /**
     * Teste inicial de tr�mite de um processo contendo um documento movido
     *
     * @group envio
     *
     * @return void
     */
    public function test_tramitar_processo_contendo_documento_movido()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Defini��o de dados de teste do processo principal
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        $processoSecundarioTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste1 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);
        self::$documentoTeste2 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        // Criar processo secund�rio para o qual o documento ser� movido
        $protocoloSecundarioTeste = $this->cadastrarProcesso($processoSecundarioTeste);

        // Cadastrar novo processo de teste e incluir documentos a ser movido
        $this->paginaBase->navegarParaControleProcesso();
        self::$protocoloTeste = $this->cadastrarProcesso(self::$processoTeste);
        $this->cadastrarDocumentoExterno(self::$documentoTeste1);
        $this->paginaDocumento->navegarParaMoverDocumento();
        $this->paginaMoverDocumento->moverDocumentoParaProcesso($protocoloSecundarioTeste, "Motivo de teste");

        // Cadastramento de documento adicional
        $this->cadastrarDocumentoInterno(self::$documentoTeste2);
        $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);

        // Tr�mitar Externamento processo para �rg�o/unidade destinat�ria
        $this->tramitarProcessoExternamente(
            self::$protocoloTeste,
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false
        );
    }


    /**
     * Teste de verifica��o do correto envio do processo no sistema remetente
     *
     * @group verificacao_envio
     *
     * @depends test_tramitar_processo_contendo_documento_movido
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
            $testCase->assertStringNotContainsString(utf8_encode("Processo em tr�mite externo para "), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Tr�mite externo do Processo %s para %s", self::$protocoloTeste, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);
        $this->validarProcessosTramitados(self::$protocoloTeste, $orgaosDiferentes);
    }

    /**
     * Teste de verifica��o do correto recebimento do processo com documento movido no destinat�rio
     *
     * @group verificacao_recebimento
     *
     * @depends test_verificar_origem_processo
     *
     * @return void
     */
    public function test_verificar_destino_processo_com_documento_movido()
    {
        $strProtocoloTeste = self::$protocoloTeste;
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);
        $this->abrirProcesso(self::$protocoloTeste);

        $strTipoProcesso = utf8_encode("Tipo de processo no �rg�o de origem: ");
        $strTipoProcesso .= self::$processoTeste['TIPO_PROCESSO'];
        $strObservacoes = $orgaosDiferentes ? $strTipoProcesso : null;
        $this->validarDadosProcesso(
            self::$processoTeste['DESCRICAO'],
            self::$processoTeste['RESTRICAO'],
            $strObservacoes,
            array(self::$processoTeste['INTERESSADOS'])
        );

        $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);

        // Valida��o dos dados do processo principal
        $listaDocumentosProcessoPrincipal = $this->paginaProcesso->listarDocumentos();
        $this->assertEquals(2, count($listaDocumentosProcessoPrincipal));
        $this->validarDocumentoCancelado($listaDocumentosProcessoPrincipal[0]);
        $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[1], self::$documentoTeste2, self::$destinatario);
    }


    /**
     * Teste de tr�mite externo de processo realizando a devolu��o para a mesma unidade de origem contendo
     * mais dois documentos, sendo um deles movido
     *
     * @group envio
     *
     * @depends test_verificar_destino_processo_com_documento_movido
     *
     * @return void
     */
    public function test_devolucao_processo_para_origem_com_novo_documento_movido()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        // Criar processo secund�rio para o qual o novo documento ser� movido
        $processoSecundarioTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste3 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);
        self::$documentoTeste4 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $protocoloSecundarioTeste = $this->cadastrarProcesso($processoSecundarioTeste);

        // Incluir novos documentos ao processo para ser movido
        $this->abrirProcesso(self::$protocoloTeste);
        $this->cadastrarDocumentoExterno(self::$documentoTeste3);
        $this->paginaDocumento->navegarParaMoverDocumento();
        $this->paginaMoverDocumento->moverDocumentoParaProcesso($protocoloSecundarioTeste, "Motivo de teste");

        // Cadastramento de documento adicional
        $this->cadastrarDocumentoInterno(self::$documentoTeste4);
        $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);

        // Tr�mitar Externamento processo para �rg�o/unidade destinat�ria
        $this->tramitarProcessoExternamente(
            self::$protocoloTeste,
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false
        );
    }


    /**
     * Teste de verifica��o do correto envio do processo no sistema remetente
     *
     * @group verificacao_envio
     *
     * @depends test_devolucao_processo_para_origem_com_novo_documento_movido
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
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(utf8_encode("Processo em tr�mite externo para "), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Tr�mite externo do Processo %s para %s", self::$protocoloTeste, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);
        $this->validarProcessosTramitados(self::$protocoloTeste, $orgaosDiferentes);
    }

    /**
     * Teste de verifica��o da correta devolu��o do processo no destinat�rio
     *
     * @group verificacao_recebimento
     *
     * @depends test_verificar_devolucao_origem_processo
     *
     * @return void
     */
    public function test_verificar_devolucao_destino_processo_com_dois_documentos_movidos()
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

        // Valida��o dos dados do processo principal
        $listaDocumentosProcesso = $this->paginaProcesso->listarDocumentos();
        $this->assertEquals(4, count($listaDocumentosProcesso));
        $this->validarDocumentoMovido($listaDocumentosProcesso[0]);
        $this->validarDadosDocumento($listaDocumentosProcesso[1], self::$documentoTeste2, self::$destinatario);
        $this->validarDocumentoCancelado($listaDocumentosProcesso[2]);
        $this->validarDadosDocumento($listaDocumentosProcesso[3], self::$documentoTeste4, self::$destinatario);
    }
}
