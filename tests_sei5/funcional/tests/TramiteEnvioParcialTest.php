<?php

/**
 * Teste de trâmite com envio parcial habilitado
 *
 * Execution Groups
 * @group execute_alone_group1
 */
class TramiteEnvioParcialTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTestePrincipal;
    public static $protocoloTestePrincipal;
    public static $documentoTeste1;
    public static $documentoTeste2;

    /**
     * @inheritdoc
     * @return void
     */
    function setUp(): void
    {
        parent::setUp();
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
    }

    /**
     * Mapeamento do Envio Parcial no Remetente (Orgão 1) e Destinatário (Orgão 2)
     *
     * @group mapeamento
     *
     * @return void
     */
    public function test_novo_mapeamento_envio_parcial_test()
    {

        // Mapeamento do Envio Parcial no Remetente
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaEnvioParcialListar->navegarEnvioParcialListar();
        $this->paginaCadastroMapEnvioCompDigitais->excluirMapeamentosExistentes();

        $this->paginaCadastroMapEnvioCompDigitais->novo();
        $this->paginaCadastroMapEnvioCompDigitais->setarParametros(
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE']
        );
        $this->paginaCadastroMapEnvioCompDigitais->salvar();

        sleep(1);
        // buscar e fechar mensagem de sucesso antes de validar os elementos de tela
        // frame/alert sobrepõem os elementos da tela
        $mensagem = $this->paginaCadastroMapEnvioCompDigitais->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Mapeamento de Envio Parcial cadastrado com sucesso.'),
            $mensagem
        );

        // valida se o mapeamento foi realizado no Órgão 1
        $nomeRepositorioCadastrado = $this->paginaCadastroMapEnvioCompDigitais->buscarNome(self::$destinatario['REP_ESTRUTURAS']);
        $nomeUnidadeCadastrado = $this->paginaCadastroMapEnvioCompDigitais->buscarNome(self::$destinatario['NOME_UNIDADE']);
        $this->assertNotNull($nomeRepositorioCadastrado);
        $this->assertNotNull($nomeUnidadeCadastrado);

        // Mapeamento do Envio Parcial no Destinatário
        $this->acessarSistema(
            self::$destinatario['URL'],
            self::$destinatario['SIGLA_UNIDADE'],
            self::$destinatario['LOGIN'],
            self::$destinatario['SENHA']
        );

        $this->paginaEnvioParcialListar->navegarEnvioParcialListar();
        $this->paginaCadastroMapEnvioCompDigitais->excluirMapeamentosExistentes();

        $this->paginaCadastroMapEnvioCompDigitais->novo();
        $this->paginaCadastroMapEnvioCompDigitais->setarParametros(
            self::$remetente['REP_ESTRUTURAS'],
            self::$remetente['NOME_UNIDADE']
        );
        $this->paginaCadastroMapEnvioCompDigitais->salvar();

        sleep(1);
        // buscar e fechar mensagem de sucesso antes de validar os elementos de tela
        // frame/alert sobrepõem os elementos da tela
        $mensagem = $this->paginaCadastroMapEnvioCompDigitais->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Mapeamento de Envio Parcial cadastrado com sucesso.'),
            $mensagem
        );

        // valida se o mapeamento foi realizado no Órgão 2
        $nomeRepositorioCadastrado = $this->paginaCadastroMapEnvioCompDigitais->buscarNome(self::$remetente['REP_ESTRUTURAS']);
        $nomeUnidadeCadastrado = $this->paginaCadastroMapEnvioCompDigitais->buscarNome(self::$remetente['NOME_UNIDADE']);
        $this->assertNotNull($nomeRepositorioCadastrado);
        $this->assertNotNull($nomeUnidadeCadastrado);
    }

    /*
     * Tramitar processo para o Órgão 2 com envio parcial mapeado
     * @group mapeamento
     *
     * @return void
     */
    public function test_criar_processo_contendo_documento_tramitar_remetente_envio_parcial()
    {
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        self::$processoTestePrincipal = $this->gerarDadosProcessoTeste(self::$remetente);
        $this->paginaBase->navegarParaControleProcesso();
        self::$protocoloTestePrincipal = $this->cadastrarProcesso(self::$processoTestePrincipal);

        self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        $this->cadastrarDocumentoInterno(self::$documentoTeste1);
        $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);

        $this->tramitarProcessoExternamente(self::$protocoloTestePrincipal, self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'], self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);
    }

    /*
     * Verificação de processo recebido no Órgão 2 com envio parcial mapeado
     * @group mapeamento
     *
     * @return void
     */
    public function test_verificar_processo_recebido_tramitar_destinatario_envio_parcial()
    {
        $documentos = array(self::$documentoTeste1);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTestePrincipal, $documentos, self::$destinatario);
    }

    /*
     * Devolução do processo ao Órgão 1 com envio parcial mapeado
     * @group mapeamento
     *
     * @return void
     */
    public function test_criar_documento_processo_recebido_tramitar_destinatario_envio_parcial()
    {
        $this->acessarSistema(
            self::$destinatario['URL'],
            self::$destinatario['SIGLA_UNIDADE'],
            self::$destinatario['LOGIN'],
            self::$destinatario['SENHA']
        );

        $this->abrirProcesso(self::$protocoloTestePrincipal);

        self::$documentoTeste2 = $this->gerarDadosDocumentoInternoTeste(self::$destinatario);
        $this->cadastrarDocumentoInterno(self::$documentoTeste2);
        $this->assinarDocumento(self::$destinatario['ORGAO'], self::$destinatario['CARGO_ASSINATURA'], self::$destinatario['SENHA']);

        $this->tramitarProcessoExternamente(self::$protocoloTestePrincipal, self::$remetente['REP_ESTRUTURAS'], self::$remetente['NOME_UNIDADE'], self::$remetente['SIGLA_UNIDADE_HIERARQUIA'], false);
    }

    /*
     * Verificação de processo recebido no Órgão 1 com envio parcial mapeado
     * @group mapeamento
     *
     * @return void
     */
    public function test_verificar_processo_recebido_tramitar_remetente_envio_parcial()
    {
        $documentos = array(self::$documentoTeste1, self::$documentoTeste2);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTestePrincipal, $documentos, self::$remetente);
    }

    /**
     * Excluir mapeamento de envio parcial no remetente e destinatário
     *
     * @group mapeamento
     *
     * @return void
     */
    public function test_excluir_mapeamento_envio_parcial_test()
    {
        // Excluir mapeamento de envio parcial no remetente
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaEnvioParcialListar->navegarEnvioParcialListar();
        $this->paginaCadastroMapEnvioCompDigitais->excluirMapeamentosExistentes();

        // Excluir mapeamento de envio parcial no destinatário
        $this->acessarSistema(
            self::$destinatario['URL'],
            self::$destinatario['SIGLA_UNIDADE'],
            self::$destinatario['LOGIN'],
            self::$destinatario['SENHA']
        );

        $this->paginaEnvioParcialListar->navegarEnvioParcialListar();
        $this->paginaCadastroMapEnvioCompDigitais->excluirMapeamentosExistentes();

        $this->sairSistema();
    }

    /**
     * @group mapeamento
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
    }
}