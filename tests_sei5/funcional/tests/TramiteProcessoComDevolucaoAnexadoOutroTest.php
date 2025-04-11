<?php

/**
 * Testes de trâmite de um processo tendo a sua devolução através de sua anexação à outro processo
 * criado no órgão de destino.
 *
 * O resultado esperado é que o novo processo recebido seja criado no remetente e o processo tramitado anteriormente
 * seja reaberto, atualizado e anexado ao novo processo recem criado
 *
 * Execution Groups
 * @group execute_alone_group5
 */
class TramiteProcessoComDevolucaoAnexadoOutroTest extends FixtureCenarioBaseTestCase
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
    public static $protocoloTestePrincipal;
    public static $protocoloTesteAnexado;

    /**
     * Teste inicial de trâmite de dois processos apartados para o sistema de origem
     *
     * Posteriormente os dois serão anexados e enviados de volta
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
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Criação e envio do primeiro processo, representando o principal em seu retorno
        self::$processoTesteAnexado = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        self::$documentoTeste2 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

        $documentos = array(self::$documentoTeste1, self::$documentoTeste2);

        $this->realizarTramiteExternoComValidacaoNoRemetenteFixture(self::$processoTesteAnexado, $documentos, self::$remetente, self::$destinatario);
        self::$protocoloTesteAnexado = self::$processoTesteAnexado["PROTOCOLO"];
    }


    /**
     * Teste de verificação do correto recebimento do processo no destino
     *
     * @group verificacao_recebimento
     * @large
     *
     * @depends test_tramitar_processo_da_origem
     *
     * @return void
     */
    public function test_verificar_recebimento_processo_destino()
    {
        $documentos = array(self::$documentoTeste1, self::$documentoTeste2);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTesteAnexado, $documentos, self::$destinatario);
    }


    /**
     * Teste de trâmite externo de processo realizando a anexação e a devolução para a mesma unidade de origem
     *
     * @group envio
     * @large
     *
     * @depends test_verificar_recebimento_processo_destino
     *
     * @return void
     */
    public function test_devolucao_processo_anexado_em_outro_para_origem()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        // Selecionar banco do org2 para fazer inserção dos documentos
        putenv("DATABASE_HOST=org2-database");

        self::$documentoTeste3 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

         // Busca ID que Protocolo principal recebeu no org2
        $objProtocoloAnexadoDTO = $this->consultarProcessoFixture(self::$protocoloTesteAnexado, \ProtocoloRN::$TP_PROCEDIMENTO);
        $this->cadastrarDocumentoExternoFixture(self::$documentoTeste3, $objProtocoloAnexadoDTO->getDblIdProtocolo());

        // Gerar dados de testes para representar o processo principal
        self::$processoTestePrincipal = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste4 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        self::$documentoTeste5 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

        // Cadastra processo principal, seus documentos e anexa processo recebido anteriormente
        $objProtocoloPrincipalDTO = $this->cadastrarProcessoFixture(self::$processoTestePrincipal);
        self::$protocoloTestePrincipal = $objProtocoloPrincipalDTO->getStrProtocoloFormatado(); 

        // Cadastra e assina
        $this->cadastrarDocumentoInternoFixture(self::$documentoTeste4,$objProtocoloPrincipalDTO->getDblIdProtocolo());

        $this->anexarProcessoFixture($objProtocoloPrincipalDTO->getDblIdProtocolo(), $objProtocoloAnexadoDTO->getDblIdProtocolo());

        $this->cadastrarDocumentoExternoFixture(self::$documentoTeste5, $objProtocoloPrincipalDTO->getDblIdProtocolo());
        
        putenv("DATABASE_HOST=org1-database");

        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        // Abre processo principal para tramitar
        $this->abrirProcesso(self::$protocoloTestePrincipal);

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
     * @depends test_devolucao_processo_anexado_em_outro_para_origem
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
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(mb_convert_encoding("Processo em trâmite externo para ", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
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
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

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
        $this->assertEquals(3, count($listaDocumentosProcessoPrincipal));
        $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[0], self::$documentoTeste4, self::$destinatario);
        $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[2], self::$documentoTeste5, self::$destinatario);

        $this->paginaProcesso->selecionarDocumento(self::$protocoloTesteAnexado);
        $this->assertTrue($this->paginaDocumento->ehProcessoAnexado());

        // Validação dos dados do processo anexado
        $this->paginaProcesso->pesquisar(self::$protocoloTesteAnexado);
        $listaDocumentosProcessoAnexado = $this->paginaProcesso->listarDocumentos();
        $this->assertEquals(3, count($listaDocumentosProcessoAnexado));
        $this->validarDadosDocumento($listaDocumentosProcessoAnexado[0], self::$documentoTeste1, self::$destinatario);
        $this->validarDadosDocumento($listaDocumentosProcessoAnexado[1], self::$documentoTeste2, self::$destinatario);
        $this->validarDadosDocumento($listaDocumentosProcessoAnexado[2], self::$documentoTeste3, self::$destinatario);
    }
}
