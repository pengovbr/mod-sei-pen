<?php

/**
 * Teste de inclusão de processo em bloco
 *
 * Execution Groups
 * @group execute_alone_group1
 */
class TramiteBlocoExternoProcessoJaIncluidoEmBlocoTest extends FixtureCenarioBaseTestCase
{
    public static $objBlocoDeTramiteDTO;
    public static $objProtocoloDTO;
    public static $remetente;
    public static $penOrgaoExternoId;

    /**
     * @inheritdoc
     */
    function setUp(): void
    {
        parent::setUp();
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
    }

    /**
     * Teste de inclusão de processo em bloco
     * @return void
     */
    public function teste_incluir_processo_em_bloco()
    {
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        self::$objBlocoDeTramiteDTO = $this->cadastrarBlocoDeTramite();
        self::$objProtocoloDTO = $this->cadastrarProcessos();
        $this->paginaBase->navegarParaControleProcesso();
        $this->paginaTramiteEmBloco->selecionarProcessos([self::$objProtocoloDTO->getStrProtocoloFormatado()]);
        $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
        $this->paginaTramiteEmBloco->selecionarBloco(self::$objBlocoDeTramiteDTO->getNumId());
        $this->paginaTramiteEmBloco->clicarSalvar();
        sleep(2);
        $mensagem = $this->paginaTramiteEmBloco->buscarMensagemAlerta();
        $this->assertStringContainsString(
            mb_convert_encoding('Processo(s) incluído(s) com sucesso no bloco ' . self::$objBlocoDeTramiteDTO->getNumOrdem(), 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );

        $this->paginaBase->sairSistema();
    }

    /**
     * Teste de inclusão do mesmo processo em bloco
     * @return void
     */
    public function teste_mesmo_processo_em_bloco()
    {

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaBase->navegarParaControleProcesso();
        $this->paginaTramiteEmBloco->selecionarProcessos([self::$objProtocoloDTO->getStrProtocoloFormatado()]);
        $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
        $this->paginaTramiteEmBloco->selecionarBloco(self::$objBlocoDeTramiteDTO->getNumId());
        $this->paginaTramiteEmBloco->clicarSalvar();
        sleep(2);
        $mensagem = $this->paginaTramiteEmBloco->buscarMensagemAlerta();
        
        $this->assertStringContainsString(
            mb_convert_encoding(
                'Prezado(a) usuário(a), o processo ' . self::$objProtocoloDTO->getStrProtocoloFormatado()
                . ' encontra-se inserido no bloco ' . self::$objBlocoDeTramiteDTO->getNumOrdem() . ' - '
                .  self::$objBlocoDeTramiteDTO->getStrDescricao() 
                . ' da unidade ' . self::$objBlocoDeTramiteDTO->getStrSiglaUnidade()
                . '. Para continuar com essa ação é necessário que o processo seja removido do bloco em questão.'
                , 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );

        $this->paginaBase->sairSistema();
    }

    /**
     * Cadastra o bloco de tramite
     */
    public function cadastrarBlocoDeTramite()
    {
        $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
        return $objBlocoDeTramiteFixture->carregar();
    }

    /**
     * Cadastra o bloco de tramite
     */
    private function cadastrarProcessos()
    {
        $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        
        $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);
        $this->cadastrarDocumentoInternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());
        
        return $objProtocoloDTO;
    }
}