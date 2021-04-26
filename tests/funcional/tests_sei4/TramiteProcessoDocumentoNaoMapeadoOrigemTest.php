<?php

class TramiteProcessoDocumentoNaoMapeadoOrigemTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste;
    public static $protocoloTeste;

    function setUp(): void
    {
        parent::setUp();
        $parametrosOrgaoA = new ParameterUtils(CONTEXTO_ORGAO_A);
        $parametrosOrgaoA->setParameter('PEN_ESPECIE_DOCUMENTAL_PADRAO_ENVIO', null);
    }

    function tearDown(): void
    {
        parent::tearDown();
        $parametrosOrgaoA = new ParameterUtils(CONTEXTO_ORGAO_A);
        $parametrosOrgaoA->setParameter('PEN_ESPECIE_DOCUMENTAL_PADRAO_ENVIO', 999);
    }

    /**
     * Teste de tr�mite externo de processo contendo um documento interno com esp�cie documental n�o mapeada
     *
     * @group envio
     *
     * @return void
     */
    public function test_tramitar_processo_documento_interno_nao_mapeado()
    {
        // Configura��o do dados para teste do cen�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        self::$documentoTeste['TIPO_DOCUMENTO'] = self::$remetente['TIPO_DOCUMENTO_NAO_MAPEADO'];

        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        // Cadastrar novo processo de teste
        self::$protocoloTeste = $this->cadastrarProcesso(self::$processoTeste);

        // Incluir Documentos no Processo
        $this->cadastrarDocumentoInterno(self::$documentoTeste);

        // Assinar documento interno criado anteriormente
        $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);

        $tipoDocumento = mb_convert_encoding(self::$documentoTeste["TIPO_DOCUMENTO"], "ISO-8859-1");
        $mensagemEsperada = sprintf("N�o existe mapeamento de envio para %s no documento", $tipoDocumento);
        $this->expectExceptionMessage(utf8_encode($mensagemEsperada));
        $this->tramitarProcessoExternamente(self::$protocoloTeste, self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'], self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);
    }


    /**
     * Teste de tr�mite externo de processo contendo um documento externo com esp�cie documental n�o mapeada
     *
     * @group envio
     *
     * @return void
     */
    public function test_tramitar_processo_documento_externo_nao_mapeado()
    {
        // Configura��o do dados para teste do cen�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste = $this->gerarDadosDocumentoExternoTeste(self::$remetente);
        self::$documentoTeste['TIPO_DOCUMENTO'] = self::$remetente['TIPO_DOCUMENTO_NAO_MAPEADO'];

        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        // Cadastrar novo processo de teste
        self::$protocoloTeste = $this->cadastrarProcesso(self::$processoTeste);

        // Incluir Documentos no Processo
        $this->cadastrarDocumentoExterno(self::$documentoTeste);

        $tipoDocumento = mb_convert_encoding(self::$documentoTeste["TIPO_DOCUMENTO"], "ISO-8859-1");
        $mensagemEsperada = sprintf("N�o existe mapeamento de envio para %s no documento", $tipoDocumento);
        $this->expectExceptionMessage(utf8_encode($mensagemEsperada));
        $this->tramitarProcessoExternamente(self::$protocoloTeste, self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'], self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);
    }
}
