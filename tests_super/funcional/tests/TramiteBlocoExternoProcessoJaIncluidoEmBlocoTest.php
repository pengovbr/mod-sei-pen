<?php

/**
 * Teste de inclusão de processo em bloco
 * @group group
 */
class TramiteBlocoExternoProcessoJaIncluidoEmBlocoTest extends CenarioBaseTestCase
{
    private $objBlocoDeTramiteDTO;
    private $objProtocoloDTO;
    public static $remetente;
    public static $penOrgaoExternoId;

    /**
     * @inheritdoc
     */
    function setUp(): void
    {
        parent::setUp();
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $penMapUnidadesFixture = new \PenMapUnidadesFixture(CONTEXTO_ORGAO_A, [
            'id' => self::$remetente['ID_ESTRUTURA'],
            'sigla' => self::$remetente['SIGLA_ESTRUTURA'],
            'nome' => self::$remetente['NOME_UNIDADE']
        ]);
        $penMapUnidadesFixture->cadastrar();

        $this->cadastrarBlocoDeTramite();
        $this->cadastrarProcessos();
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

        $this->paginaBase->navegarParaControleProcesso();
        $this->paginaTramiteEmBloco->selecionarProcessos([$this->objProtocoloDTO->getStrProtocoloFormatado()]);
        $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
        $this->paginaTramiteEmBloco->selecionarBloco($this->objBlocoDeTramiteDTO->getNumId());
        $this->paginaTramiteEmBloco->clicarSalvar();
        sleep(2);
        $mensagem = $this->paginaTramiteEmBloco->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Processo(s) incluído(s) com sucesso no bloco ' . $this->objBlocoDeTramiteDTO->getNumId()),
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
        $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
        $objBlocoDeTramiteProtocoloFixture->carregar([
            'IdProtocolo' => $this->objProtocoloDTO->getDblIdProtocolo(),
            'IdTramitaEmBloco' => $this->objBlocoDeTramiteDTO->getNumId(),
            'IdxRelBlocoProtocolo' => $this->objProtocoloDTO->getStrProtocoloFormatado()
        ]);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaBase->navegarParaControleProcesso();
        $this->paginaTramiteEmBloco->selecionarProcessos([$this->objProtocoloDTO->getStrProtocoloFormatado()]);
        $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
        $this->paginaTramiteEmBloco->selecionarBloco($this->objBlocoDeTramiteDTO->getNumId());
        $this->paginaTramiteEmBloco->clicarSalvar();
        sleep(2);
        $mensagem = $this->paginaTramiteEmBloco->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode(
                'Prezado(a) usuário(a), o processo ' . $this->objProtocoloDTO->getStrProtocoloFormatado()
                . ' encontra-se inserido no bloco de número ' . $this->objBlocoDeTramiteDTO->getNumId() . '.'
                . ' Para continuar com essa ação é necessário que o processo seja removido do bloco em questão.'
            ),
            $mensagem
        );

        $this->paginaBase->sairSistema();
    }

    /**
     * Cadastra o bloco de tramite
     * @return void
     */
    private function cadastrarBlocoDeTramite()
    {
        $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
        $this->objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();
    }

    /**
     * Cadastra o bloco de tramite
     * @return void
     */
    private function cadastrarProcessos()
    {
        $parametros = [];
        $objProtocoloFixture = new \ProtocoloFixture();
        $this->objProtocoloDTO = $objProtocoloFixture->carregar(
            $parametros,
            function($objProtocoloDTO) {
                $objProcedimentoFixture = new \ProcedimentoFixture();
                $objProcedimentoDTO = $objProcedimentoFixture->carregar([
                    'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
                ]);

                $objAtividadeFixture = new \AtividadeFixture();
                $objAtividadeDTO = $objAtividadeFixture->carregar([
                    'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
                ]);

                $objParticipanteFixture = new \ParticipanteFixture();
                $objParticipanteFixture->carregar([
                    'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
                ]);

                $objProtocoloAssuntoFixture = new \RelProtocoloAssuntoFixture();
                $objProtocoloAssuntoFixture->carregar([
                    'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
                ]);

                $objAtributoAndamentoFixture = new \AtributoAndamentoFixture();
                $objAtributoAndamentoFixture->carregar([
                    'IdAtividade' => $objAtividadeDTO->getNumIdAtividade()
                ]);

                $objDocumentoFixture = new \DocumentoFixture();
                $objDocumentoDTO = $objDocumentoFixture->carregar([
                    'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
                    'IdProcedimento' => $objProcedimentoDTO->getDblIdProcedimento(),
                ]);

                $objAssinaturaFixture = new \AssinaturaFixture();
                $objAssinaturaFixture->carregar([
                    'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
                    'IdDocumento' => $objDocumentoDTO->getDblIdDocumento(),
                    'IdAtividade' => $objProtocoloDTO->getDblIdProtocolo()
                ]);
            }
        );
    }
}