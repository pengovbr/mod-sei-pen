<?php

use Tests\Funcional\Sei\Fixtures\{ProtocoloFixture,ProcedimentoFixture,AtividadeFixture,ParticipanteFixture,RelProtocoloAssuntoFixture,AtributoAndamentoFixture,DocumentoFixture,AssinaturaFixture};

/**
 * Teste de inclusão de processo em bloco
 *
 * Execution Groups
 * @group execute_alone_group1
 */
class TramiteBlocoExternoProcessoJaIncluidoEmBlocoTest extends CenarioBaseTestCase
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
            utf8_encode('Processo(s) incluído(s) com sucesso no bloco ' . self::$objBlocoDeTramiteDTO->getNumOrdem()),
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
            utf8_encode(
                'Prezado(a) usuário(a), o processo ' . self::$objProtocoloDTO->getStrProtocoloFormatado()
                . ' encontra-se inserido no bloco ' . self::$objBlocoDeTramiteDTO->getNumId() . ' - '
                .  self::$objBlocoDeTramiteDTO->getStrDescricao() 
                . '. Para continuar com essa ação é necessário que o processo seja removido do bloco em questão.'
            ),
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
        $parametros = ['Descricao' => 'teste'];
        $objProtocoloFixture = new ProtocoloFixture();
        return $objProtocoloFixture->carregar(
            $parametros,
            function($objProtocoloDTO) {
                $objProcedimentoFixture = new ProcedimentoFixture();
                $objProcedimentoDTO = $objProcedimentoFixture->carregar([
                    'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
                ]);

                $objAtividadeFixture = new AtividadeFixture();
                $objAtividadeDTO = $objAtividadeFixture->carregar([
                    'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
                ]);

                $objParticipanteFixture = new ParticipanteFixture();
                $objParticipanteFixture->carregar([
                    'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
                    'IdContato' => 100000006,
                ]);

                $objProtocoloAssuntoFixture = new RelProtocoloAssuntoFixture();
                $objProtocoloAssuntoFixture->carregar([
                    'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
                ]);

                $objAtributoAndamentoFixture = new AtributoAndamentoFixture();
                $objAtributoAndamentoFixture->carregar([
                    'IdAtividade' => $objAtividadeDTO->getNumIdAtividade()
                ]);

                $objDocumentoFixture = new DocumentoFixture();
                $objDocumentoDTO = $objDocumentoFixture->carregar([
                    'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
                    'IdProcedimento' => $objProcedimentoDTO->getDblIdProcedimento(),
                ]);

                $objAssinaturaFixture = new AssinaturaFixture();
                $objAssinaturaFixture->carregar([
                    'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
                    'IdDocumento' => $objDocumentoDTO->getDblIdDocumento(),
                ]);
            }
        );
    }
}