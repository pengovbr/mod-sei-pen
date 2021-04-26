<?php

class TramiteProcessoDocumentoNaoMapeadoDestinoTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste;
    public static $protocoloTeste;

    function setUp(): void
    {
        parent::setUp();
        $parametrosOrgaoA = new ParameterUtils(CONTEXTO_ORGAO_B);
        $parametrosOrgaoA->setParameter('PEN_TIPO_DOCUMENTO_PADRAO_RECEBIMENTO', null);
    }

    function tearDown(): void
    {
        parent::tearDown();
        $parametrosOrgaoA = new ParameterUtils(CONTEXTO_ORGAO_B);
        $parametrosOrgaoA->setParameter('PEN_TIPO_DOCUMENTO_PADRAO_RECEBIMENTO', 999);
    }

    /**
     * Teste de tr�mite externo de processo contendo documento n�o mapeado no destino
     *
     * @group envio
     *
     * @return void
     */
    public function test_tramitar_processo_contendo_documento_nao_mapeado_destino()
    {
        // Configura��o do dados para teste do cen�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        self::$documentoTeste['TIPO_DOCUMENTO'] = self::$destinatario['TIPO_DOCUMENTO_NAO_MAPEADO'];

        // 1 - Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        // 2 - Cadastrar novo processo de teste
        self::$protocoloTeste = $this->cadastrarProcesso(self::$processoTeste);

        // 3 - Incluir Documentos no Processo
        $this->cadastrarDocumentoInterno(self::$documentoTeste);

        // 4 - Assinar documento interno criado anteriormente
        $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);

        // 5 - Tr�mitar Externamento processo para �rg�o/unidade destinat�ria
        $this->tramitarProcessoExternamente(
                self::$protocoloTeste, self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'],
                self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);
    }


    /**
     * Teste de verifica��o do correto envio do processo no sistema remetente
     *
     * @group verificacao_envio
     *
     * @depends test_tramitar_processo_contendo_documento_nao_mapeado_destino
     *
     * @return void
     */
    public function test_verificar_origem_processo_contendo_documento_nao_mapeado_destino()
    {
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->abrirProcesso(self::$protocoloTeste);

        // 6 - Verificar se situa��o atual do processo est� como bloqueado
        $this->waitUntil(function($testCase)  {
            sleep(5);
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringContainsString(utf8_encode("Processo aberto somente na unidade"), $paginaProcesso->informacao());
            $testCase->assertTrue($paginaProcesso->processoAberto());
            $testCase->assertFalse($paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        // Validar hist�rico de tr�mite do processo
        $nomeTipoDocumentoNaoMapeado = mb_convert_encoding(self::$destinatario['TIPO_DOCUMENTO_NAO_MAPEADO'], "ISO-8859-1");
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, false, true,
            sprintf("Documento do tipo %s n�o est� mapeado", $nomeTipoDocumentoNaoMapeado));


        // Validar se recibo de tr�mite foi armazenado para o processo (envio e conclus�o)
        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Tr�mite externo do Processo %s para %s", self::$protocoloTeste, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, false);

        // Verificar se processo est� na lista de Processos Tramitados Externamente
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
     *
     * @depends test_verificar_origem_processo_contendo_documento_nao_mapeado_destino
     *
     * @return void
     */
    public function test_verificar_destino_processo_contendo_documento_gerado()
    {
        $this->realizarValidacaoNAORecebimentoProcessoNoDestinatario(self::$destinatario, self::$processoTeste);
    }
}
