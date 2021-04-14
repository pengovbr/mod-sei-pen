<?php

class TramiteProcessoRestritoComDocumentoRestritoCanceladoComHipoteseInativaTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste1;
    public static $documentoTeste2;
    public static $protocoloTeste;

    /**
     * Teste de trâmite externo de processo com documento restrito com hipótese legal inativa cancelado
     *
     * @group envio
     *
     * @return void
     */
    public function test_tramitar_processo_restrivo_com_documento_restrito_cancelado_hipotese_inativa()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        self::$documentoTeste2 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        // Configuração de processo restrito
        self::$processoTeste["RESTRICAO"] = PaginaIniciarProcesso::STA_NIVEL_ACESSO_RESTRITO;
        self::$processoTeste["HIPOTESE_LEGAL"] = self::$remetente["HIPOTESE_RESTRICAO"];        

        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        // Cadastrar novo processo de teste
        self::$protocoloTeste = $this->cadastrarProcesso(self::$processoTeste);

        // Configuração de documento restrito
        self::$documentoTeste1["RESTRICAO"] = PaginaIncluirDocumento::STA_NIVEL_ACESSO_RESTRITO;
        self::$documentoTeste1["HIPOTESE_LEGAL"] = self::$remetente["HIPOTESE_RESTRICAO"];

        // Incluir Documentos no Processo
        $this->cadastrarDocumentoInterno(self::$documentoTeste1);

        // Assinar documento interno criado anteriormente
        $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);        

        // Configuração de documento restrito
        self::$documentoTeste2["RESTRICAO"] = PaginaIncluirDocumento::STA_NIVEL_ACESSO_RESTRITO;
        self::$documentoTeste2["HIPOTESE_LEGAL"] = self::$remetente["HIPOTESE_RESTRICAO_INATIVA"];

        // Incluir Documentos no Processo
        $this->cadastrarDocumentoInterno(self::$documentoTeste2);

        // Assinar documento interno criado anteriormente
        $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);

        $this->paginaDocumento->navegarParaCancelarDocumento();
        $this->paginaCancelarDocumento->cancelar("Motivo de teste"); 

        // Inativa hipótese legal Situação Econômico-Financeira de Sujeito Passivo
        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);
        $bancoOrgaoA->execute("update hipotese_legal set sin_ativo=? where id_hipotese_legal=?", array('N', '18'));

        // Trâmitar Externamento processo para órgão/unidade destinatária
        $this->tramitarProcessoExternamente(
                self::$protocoloTeste, self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'],
                self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);
    }


    /**
     * Teste de verificação do correto envio do processo no sistema remetente
     *
     * @group verificacao_envio
     *
     * @depends test_tramitar_processo_restrivo_com_documento_restrito_cancelado_hipotese_inativa
     *
     * @return void
     */
    public function test_verificar_origem_processo_com_documento_restrito_cancelado_hipotese_inativa()
    {
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->abrirProcesso(self::$protocoloTeste);

        // 6 - Verificar se situação atual do processo está como bloqueado
        $this->waitUntil(function($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(utf8_encode("Processo em trâmite externo para "), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        // 7 - Validar se recibo de trâmite foi armazenado para o processo (envio e conclusão)
        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTeste, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);

        // 8 - Validar histórico de trâmite do processo
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);

        // 9 - Verificar se processo está na lista de Processos Tramitados Externamente
        $this->validarProcessosTramitados(self::$protocoloTeste, $orgaosDiferentes);
    }


    /**
     * Teste de verificação do correto recebimento do processo contendo um documento com hipótese legal inativa cancelado
     *
     * @group verificacao_recebimento
     *
     * @depends test_tramitar_processo_restrivo_com_documento_restrito_cancelado_hipotese_inativa
     *
     * @return void
     */
    public function test_verificar_destino_processo_com_documento_restrito_cancelado_hipotese_inativa()
    {
        $strProtocoloTeste = self::$protocoloTeste;
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);

        // 11 - Abrir protocolo na tela de controle de processos
        $this->abrirProcesso(self::$protocoloTeste);
        $listaDocumentos = $this->paginaProcesso->listarDocumentos();

        // 12 - Validar dados  do processo
        $strTipoProcesso = utf8_encode("Tipo de processo no órgão de origem: ");
        $strTipoProcesso .= self::$processoTeste['TIPO_PROCESSO'];
        self::$processoTeste['OBSERVACOES'] = $orgaosDiferentes ? $strTipoProcesso : null;
        $this->validarDadosProcesso(self::$processoTeste['DESCRICAO'], self::$processoTeste['RESTRICAO'], self::$processoTeste['OBSERVACOES'], array(self::$processoTeste['INTERESSADOS']));

        // 13 - Verificar recibos de trâmite
        $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);

        // 14 - Validar dados do documento
        $this->assertTrue(count($listaDocumentos) == 2);
        $this->validarDadosDocumento($listaDocumentos[0], self::$documentoTeste1, self::$destinatario);
        
        // Ativa hipótese legal Situação Econômico-Financeira de Sujeito Passivo
        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);
        $bancoOrgaoA->execute("update hipotese_legal set sin_ativo=? where id_hipotese_legal=?", array('S', '18'));

    }
}
