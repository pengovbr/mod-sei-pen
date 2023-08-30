<?php

/**
 * Testes de trâmite de processos anexados considerando cenário específico de trâmites e devoluções sucessivas
 *
 * O cenário descreve uma falha relatada pelos usuários em que um erro de inconsistência era causado após a realização dos seguintes passos:
 *
 *  - Trâmite de processo simples X do órgão A para o órgão B
 *  - Adição de novos documentos e devolução do processo para órgão A
 *  - Adição de novos documentos no processo X e anexação ao processo Y
 *  - Trâmite do processo Y para órgão B
 *  - Adição de novos documentos ao processo Y e devolução para o órgão A
 *  - Adição de novos documentos e devolução para órgão B
 */
class TramiteProcessosComDevolucoesEAnexacoesTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTestePrincipal;
    public static $processoTesteAnexado;
    public static $documentoTeste1;
    public static $documentoTeste2;
    public static $documentoTeste3;
    public static $documentoTeste4;
    public static $documentoTeste5;
    public static $documentoTeste6;
    public static $documentoTeste7;
    public static $documentoTeste8;
    public static $protocoloTestePrincipal;
    public static $protocoloTesteAnexado;

    /**
     * Teste inicial de trâmite de processo apartado para o órgão B
     *
     * @group envio
     * @large
     * 
     * @Depends CenarioBaseTestCase::setUpBeforeClass
     *
     * @return void
     */
    public function test_tramitar_processo_simples_para_orgaoB()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Criação e envio do segundo processo, representando o que será anexado ao processo principal
        self::$processoTesteAnexado = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        self::$documentoTeste2 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

        $documentos = array(self::$documentoTeste1, self::$documentoTeste2);
        $this->realizarTramiteExternoComValidacaoNoRemetente(self::$processoTesteAnexado, $documentos, self::$remetente, self::$destinatario);
        self::$protocoloTesteAnexado = self::$processoTesteAnexado["PROTOCOLO"];
    }


    /**
     * Teste de verificação do correto recebimento do processo simples no órgão B
     *
     * @group verificacao_recebimento
     * @large
     *
     * @depends test_tramitar_processo_simples_para_orgaoB
     *
     * @return void
     */
    public function test_verificar_recebimento_processo_simples_destino()
    {
        $documentos = array(self::$documentoTeste1, self::$documentoTeste2);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTesteAnexado, $documentos, self::$destinatario);
    }


    /**
     * Teste de trâmite externo de processo realizando a devolução para a mesma unidade de origem
     *
     * @group envio
     * @large
     *
     * @depends test_verificar_recebimento_processo_simples_destino
     *
     * @return void
     */
    public function test_devolucao_processo_simples_para_origem()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        // Definição de dados de teste do processo principal
        self::$documentoTeste3 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        self::$documentoTeste4 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

        $documentos = array(self::$documentoTeste3, self::$documentoTeste4);
        $this->realizarTramiteExternoComValidacaoNoRemetente(self::$processoTesteAnexado, $documentos, self::$remetente, self::$destinatario);
    }


    /**
     * Teste de verificação da correta devolução do processo simples para o órgão A
     *
     * @group verificacao_recebimento
     * @large
     *
     * @depends test_devolucao_processo_simples_para_origem
     *
     * @return void
     */
    public function test_verificar_devolucao_processo_simples_origem()
    {
        $documentos = array(self::$documentoTeste1, self::$documentoTeste2, self::$documentoTeste3, self::$documentoTeste4);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTesteAnexado, $documentos, self::$destinatario);
    }



    /**
     * Teste de trâmite de processos contendo o processo simples anexado à outro
     *
     * @group envio
     * @large
     *
     * @depends test_verificar_devolucao_processo_simples_origem
     *
     * @return void
     */
    public function test_tramitar_processo_anexado_para_orgaoB()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Definição de dados de teste do processo principal
        self::$processoTestePrincipal = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste5 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);
        self::$documentoTeste6 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        // Cadastrar novo processo de teste principal e incluir documentos relacionados
        $this->paginaBase->navegarParaControleProcesso();
        self::$protocoloTestePrincipal = $this->cadastrarProcesso(self::$processoTestePrincipal);
        $this->cadastrarDocumentoExterno(self::$documentoTeste5);

        // Realizar a anexação de processos
        $this->anexarProcesso(self::$protocoloTesteAnexado);

        $this->cadastrarDocumentoInterno(self::$documentoTeste6);
        $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);

        // Trâmitar Externamento processo para órgão/unidade destinatária
        $this->tramitarProcessoExternamente(
            self::$protocoloTestePrincipal,
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false
        );
    }


    /**
     * Teste de verificação do correto envio do processo anexado no sistema remetente
     *
     * @group verificacao_envio
     * @large
     *
     * @depends test_tramitar_processo_anexado_para_orgaoB
     *
     * @return void
     */
    public function test_verificar_origem_processo_anexado()
    {
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->abrirProcesso(self::$protocoloTestePrincipal);

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
        $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTestePrincipal, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);
        $this->validarProcessosTramitados(self::$protocoloTestePrincipal, $orgaosDiferentes);
    }



    /**
     * Teste de verificação do correto recebimento do processo anexado no destinatário
     *
     * @group verificacao_recebimento
     * @large
     *
     * @depends test_verificar_origem_processo_anexado
     *
     * @return void
     */
    public function test_verificar_destino_processo_anexado()
    {
        $strProtocoloTeste = self::$protocoloTestePrincipal;
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];
        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);
        $this->abrirProcesso(self::$protocoloTestePrincipal);

        $strTipoProcesso = utf8_encode("Tipo de processo no órgão de origem: ");
        $strTipoProcesso .= self::$processoTestePrincipal['TIPO_PROCESSO'];
        $strObservacoes = $orgaosDiferentes ? $strTipoProcesso : null;
        $this->validarDadosProcesso(
            self::$processoTestePrincipal['DESCRICAO'],
            self::$processoTestePrincipal['RESTRICAO'],
            $strObservacoes,
            array(self::$processoTestePrincipal['INTERESSADOS'])
        );

        $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);

        // Validação dos dados do processo principal
        $listaDocumentosProcessoPrincipal = $this->paginaProcesso->listarDocumentos();
        $this->assertEquals(3, count($listaDocumentosProcessoPrincipal));
        $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[0], self::$documentoTeste5, self::$destinatario);
        $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[2], self::$documentoTeste6, self::$destinatario);

        $this->paginaProcesso->selecionarDocumento(self::$protocoloTesteAnexado);
        $this->assertTrue($this->paginaDocumento->ehProcessoAnexado());

        // Validação dos dados do processo anexado
        $this->paginaProcesso->pesquisar(self::$protocoloTesteAnexado);
        $listaDocumentosProcessoAnexado = $this->paginaProcesso->listarDocumentos();
        $this->assertEquals(4, count($listaDocumentosProcessoAnexado));
        $this->validarDadosDocumento($listaDocumentosProcessoAnexado[0], self::$documentoTeste1, self::$destinatario);
        $this->validarDadosDocumento($listaDocumentosProcessoAnexado[1], self::$documentoTeste2, self::$destinatario);
        $this->validarDadosDocumento($listaDocumentosProcessoAnexado[2], self::$documentoTeste3, self::$destinatario);
        $this->validarDadosDocumento($listaDocumentosProcessoAnexado[3], self::$documentoTeste4, self::$destinatario);
    }

    /**
     * Teste de trâmite externo de processo realizando nova devolução para a mesma unidade de origem
     *
     * @group envio
     * @large
     *
     * @depends test_verificar_destino_processo_anexado
     *
     * @return void
     */
    public function test_devolucao_processo_para_origem()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        // Definição de dados de teste do processo principal
        self::$documentoTeste7 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);
        self::$documentoTeste8 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        // Incluir novos documentos relacionados
        $this->abrirProcesso(self::$protocoloTestePrincipal);
        $this->cadastrarDocumentoExterno(self::$documentoTeste7);
        $this->cadastrarDocumentoInterno(self::$documentoTeste8);
        $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);

        // Trâmitar Externamento processo para órgão/unidade destinatária
        $this->tramitarProcessoExternamente(
            self::$protocoloTestePrincipal,
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false
        );
    }


    /**
     * Teste de verificação do correto envio do processo anexado no sistema remetente
     *
     * @group verificacao_envio
     * @large
     *
     * @depends test_devolucao_processo_para_origem
     *
     * @return void
     */
    public function test_verificar_devolucao_origem_processo_anexado()
    {
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->abrirProcesso(self::$protocoloTestePrincipal);

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
        $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTestePrincipal, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);
        $this->validarProcessosTramitados(self::$protocoloTestePrincipal, $orgaosDiferentes);
    }

    /**
     * Teste de verificação da correta devolução do processo anexado no destinatário
     *
     * @group verificacao_recebimento
     * @large
     *
     * @depends test_verificar_devolucao_origem_processo_anexado
     *
     * @return void
     */
    public function test_verificar_devolucao_destino_processo_anexado()
    {
        $strProtocoloTeste = self::$protocoloTestePrincipal;
        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);
        $this->abrirProcesso(self::$protocoloTestePrincipal);

        $this->validarDadosProcesso(
            self::$processoTestePrincipal['DESCRICAO'],
            self::$processoTestePrincipal['RESTRICAO'],
            self::$processoTestePrincipal['OBSERVACOES'],
            array(self::$processoTestePrincipal['INTERESSADOS'])
        );

        $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);

        // Validação dos dados do processo principal
        $listaDocumentosProcessoPrincipal = $this->paginaProcesso->listarDocumentos();
        $this->assertEquals(5, count($listaDocumentosProcessoPrincipal));
        $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[0], self::$documentoTeste5, self::$destinatario);
        $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[2], self::$documentoTeste6, self::$destinatario);
        $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[3], self::$documentoTeste7, self::$destinatario);
        $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[4], self::$documentoTeste8, self::$destinatario);

        $this->paginaProcesso->selecionarDocumento(self::$protocoloTesteAnexado);
        $this->assertTrue($this->paginaDocumento->ehProcessoAnexado());

        // Validação dos dados do processo anexado
        $this->paginaProcesso->pesquisar(self::$protocoloTesteAnexado);
        $listaDocumentosProcessoAnexado = $this->paginaProcesso->listarDocumentos();
        $this->assertEquals(4, count($listaDocumentosProcessoAnexado));
        $this->validarDadosDocumento($listaDocumentosProcessoAnexado[0], self::$documentoTeste1, self::$destinatario);
        $this->validarDadosDocumento($listaDocumentosProcessoAnexado[1], self::$documentoTeste2, self::$destinatario);
        $this->validarDadosDocumento($listaDocumentosProcessoAnexado[2], self::$documentoTeste3, self::$destinatario);
        $this->validarDadosDocumento($listaDocumentosProcessoAnexado[3], self::$documentoTeste4, self::$destinatario);
    }
}
