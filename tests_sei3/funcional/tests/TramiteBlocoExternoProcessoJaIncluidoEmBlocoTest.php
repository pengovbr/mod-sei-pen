<?php

/**
 * Teste de inclus�o de processo em bloco
 * @group group
 */
class TramiteBlocoExternoProcessoJaIncluidoEmBlocoTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $penOrgaoExternoId;
    public static $processoTeste;
    public static $documentoTeste;
    public static $protocoloTeste;
    public static $bloco;

    /**
     * @inheritdoc
     */
    function setUpPage(): void
    {
        parent::setUpPage();

        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

    }

    /**
     * Teste de inclus�o de processo em bloco
     * @return void
     */
    public function teste_incluir_processo_em_bloco()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->cadastrarBlocoDeTramite();
        $this->paginaBase->navegarParaControleProcesso();
        $this->cadastrarProcessos();

        $this->paginaBase->navegarParaControleProcesso();
        // $this->paginaTramiteEmBloco->selecionarProcessos([self::$protocoloTeste]);
        $this->paginaTramiteEmBloco->selecionarProcessos(self::$protocoloTeste);
        $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
        $this->paginaTramiteEmBloco->clicarSalvar();

        sleep(2);
        $mensagem = $this->paginaTramiteEmBloco->buscarMensagemAlerta();

        $this->assertStringContainsString(
            utf8_encode('Processo(s) inclu�do(s) com sucesso no bloco'),
            $mensagem
        );

        // $this->paginaBase->sairSistema();
    }

    /**
     * Teste de inclus�o do mesmo processo em bloco
     * @return void
     */
    public function teste_mesmo_processo_em_bloco()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaBase->navegarParaControleProcesso();
        // $this->paginaTramiteEmBloco->selecionarProcessos([self::$protocoloTeste]);
        $this->paginaTramiteEmBloco->selecionarProcessos(self::$protocoloTeste);
        $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
        $this->paginaTramiteEmBloco->clicarSalvar();

        sleep(2);
        $mensagem = $this->paginaTramiteEmBloco->buscarMensagemAlerta();

        $this->assertStringContainsString(
            utf8_encode('Prezado(a) usu�rio(a), o processo ' . self::$protocoloTeste
           . ' encontra-se inserido no bloco de n�mero '),
            $mensagem
        );

        $this->paginaBase->sairSistema();

        // $this->paginaBase->navegarParaControleProcesso();
        // $this->paginaTramiteEmBloco->selecionarProcessos([$this->objProtocoloDTO->getStrProtocoloFormatado()]);
        // $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
        // $this->paginaTramiteEmBloco->clicarSalvar();
        // sleep(2);
        // $mensagem = $this->paginaTramiteEmBloco->buscarMensagemAlerta();
        // $this->assertStringContainsString(
        //     utf8_encode(
        //         'Prezado(a) usu�rio(a), o processo ' . $this->objProtocoloDTO->getStrProtocoloFormatado()
        //         . ' encontra-se inserido no bloco de n�mero ' . $this->objBlocoDeTramiteDTO->getNumId() . '.'
        //         . ' Para continuar com essa a��o � necess�rio que o processo seja removido do bloco em quest�o.'
        //     ),
        //     $mensagem
        // );
    }

    /**
     * Cadastra o bloco de tramite
     * @return void
     */
    private function cadastrarBlocoDeTramite()
    {

        // Configura��o do dados para teste do cen�rio
        $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
        $this->paginaCadastrarProcessoEmBloco->novoBlocoDeTramite();
        $this->paginaCadastrarProcessoEmBloco->criarNovoBloco();
        $this->paginaCadastrarProcessoEmBloco->btnSalvar();

        sleep(1);
        $mensagemRetornoAlert = $this->paginaCadastroOrgaoExterno->buscarMensagemAlerta();
        $menssagemValidacao = utf8_encode('Bloco de Tr�mite externo criado com sucesso!');

        $this->assertStringContainsString(
            $menssagemValidacao,
            $mensagemRetornoAlert
        );
    }

    /**
     * Cadastra o bloco de tramite
     * @return void
     */
    private function cadastrarProcessos()
    {
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste = $this->gerarDadosDocumentoExternoTeste(self::$remetente);
        self::$protocoloTeste = $this->cadastrarProcesso(self::$processoTeste);
    }
}