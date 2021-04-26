<?php

/**
 * Testes de tr�mite de um processo tendo a sua devolu��o atrav�s de sua anexa��o � outro processo
 * criado no �rg�o de destino.
 *
 * O resultado esperado � que o novo processo recebido seja criado no remetente e o processo tramitado anteriormente
 * seja reaberto, atualizado e anexado ao novo processo recem criado
 */
class TramiteProcessoComDevolucaoAnexadoOutroTest extends CenarioBaseTestCase
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
     * Teste inicial de tr�mite de dois processos apartados para o sistema de origem
     *
     * Posteriormente os dois ser�o anexados e enviados de volta
     *
     * @group envio
     *
     * @return void
     */
    public function test_tramitar_processo_da_origem()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Cria��o e envio do primeiro processo, representando o principal em seu retorno
        self::$processoTesteAnexado = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        self::$documentoTeste2 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

        $documentos = array(self::$documentoTeste1, self::$documentoTeste2);
        $this->realizarTramiteExternoComValidacaoNoRemetente(self::$processoTesteAnexado, $documentos, self::$remetente, self::$destinatario);
        self::$protocoloTesteAnexado = self::$processoTesteAnexado["PROTOCOLO"];
    }


    /**
     * Teste de verifica��o do correto recebimento do processo no destino
     *
     * @group verificacao_recebimento
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
     * Teste de tr�mite externo de processo realizando a anexa��o e a devolu��o para a mesma unidade de origem
     *
     * @group envio
     *
     * @depends test_verificar_recebimento_processo_destino
     *
     * @return void
     */
    public function test_devolucao_processo_anexado_em_outro_para_origem()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        self::$documentoTeste3 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        // Incluir novos documentos relacionados no processo anexado
        $this->abrirProcesso(self::$protocoloTesteAnexado);
        $this->cadastrarDocumentoExterno(self::$documentoTeste3);

        // Gerar dados de testes para representar o processo principal
        self::$processoTestePrincipal = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste4 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        self::$documentoTeste5 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

        // Cadastra processo principal, seus documentos e anexa processo recebido anteriormente
        self::$protocoloTestePrincipal = $this->cadastrarProcesso(self::$processoTestePrincipal);
        $this->cadastrarDocumentoInterno(self::$documentoTeste4);
        $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);

        $this->anexarProcesso(self::$protocoloTesteAnexado);

        $this->cadastrarDocumentoExterno(self::$documentoTeste5);


        // Tr�mitar Externamento processo para �rg�o/unidade destinat�ria
        $this->tramitarProcessoExternamente(
            self::$protocoloTestePrincipal,
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false
        );
    }


    /**
     * Teste de verifica��o do correto envio do processo anexado no sistema remetente
     *
     * @group verificacao_envio
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
            $testCase->assertStringNotContainsString(utf8_encode("Processo em tr�mite externo para "), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Tr�mite externo do Processo %s para %s", self::$protocoloTestePrincipal, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);
        $this->validarProcessosTramitados(self::$protocoloTestePrincipal, $orgaosDiferentes);
    }

    /**
     * Teste de verifica��o da correta devolu��o do processo anexado no destinat�rio
     *
     * @group verificacao_recebimento
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

        // Valida��o dos dados do processo principal
        $listaDocumentosProcessoPrincipal = $this->paginaProcesso->listarDocumentos();
        $this->assertEquals(3, count($listaDocumentosProcessoPrincipal));
        $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[0], self::$documentoTeste4, self::$destinatario);
        $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[2], self::$documentoTeste5, self::$destinatario);

        $this->paginaProcesso->selecionarDocumento(self::$protocoloTesteAnexado);
        $this->assertTrue($this->paginaDocumento->ehProcessoAnexado());

        // Valida��o dos dados do processo anexado
        $this->paginaProcesso->pesquisar(self::$protocoloTesteAnexado);
        $listaDocumentosProcessoAnexado = $this->paginaProcesso->listarDocumentos();
        $this->assertEquals(3, count($listaDocumentosProcessoAnexado));
        $this->validarDadosDocumento($listaDocumentosProcessoAnexado[0], self::$documentoTeste1, self::$destinatario);
        $this->validarDadosDocumento($listaDocumentosProcessoAnexado[1], self::$documentoTeste2, self::$destinatario);
        $this->validarDadosDocumento($listaDocumentosProcessoAnexado[2], self::$documentoTeste3, self::$destinatario);
    }
}
