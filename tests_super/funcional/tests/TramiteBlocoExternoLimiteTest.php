<?php

/**
 * EnviarProcessoTest
 * @group group
 */
class TramiteBlocoExternoLimiteTest extends CenarioBaseTestCase
{
    protected static $strQtyProcessos = 3;

    public static $remetente;
    public static $destinatario;
    public static $penOrgaoExternoId;

    function setUp(): void 
    {
        parent::setUp();
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // $penMapUnidadesFixture = new \PenMapUnidadesFixture(CONTEXTO_ORGAO_A, [
        //     'id' => self::$remetente['ID_ESTRUTURA'],
        //     'sigla' => self::$remetente['SIGLA_ESTRUTURA'],
        //     'nome' => self::$remetente['NOME_UNIDADE']
        // ]);
        // $penMapUnidadesFixture->cadastrar();

        $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
        $objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();

        for ($i = 0; $i < self::$strQtyProcessos; $i++) {
            $objProtocoloFixture = new \ProtocoloFixture();
            $objProtocoloFixtureDTO = $objProtocoloFixture->carregar();

            $objProcedimentoFixture = new \ProcedimentoFixture();
            $objProcedimentoDTO = $objProcedimentoFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo()
            ]);

            $objAtividadeFixture = new \AtividadeFixture();
            $objAtividadeDTO = $objAtividadeFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo(),
                'IdTarefa' => TarefaRN::$TI_GERACAO_PROCEDIMENTO,
            ]);

            $objParticipanteFixture = new \ParticipanteFixture();
            $objParticipanteFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo(),
            ]);

            $objProtocoloAssuntoFixture = new \RelProtocoloAssuntoFixture();
            $objProtocoloAssuntoFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo()
            ]);

            $objAtributoAndamentoFixture = new \AtributoAndamentoFixture();
            $objAtributoAndamentoFixture->carregar([
                'IdAtividade' => $objAtividadeDTO->getNumIdAtividade()
            ]);

            $objDocumentoFixture = new \DocumentoFixture();
            $objDocumentoDTO = $objDocumentoFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo(),
                'IdProcedimento' => $objProcedimentoDTO->getDblIdProcedimento(),
            ]);

            $objAssinaturaFixture = new \AssinaturaFixture();
            $objAssinaturaFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo(),
                'IdDocumento' => $objDocumentoDTO->getDblIdDocumento(),
                'IdAtividade' => $objAtividadeDTO->getNumIdAtividade(),
            ]);

            $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
            $objBlocoDeTramiteProtocoloFixtureDTO = $objBlocoDeTramiteProtocoloFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo(),
                'IdTramitaEmBloco' => $objBlocoDeTramiteDTO->getNumId(),
                'IdxRelBlocoProtocolo' => $objProtocoloFixtureDTO->getStrProtocoloFormatado()
            ]);
        }

    }

    public function teste_tramite_bloco_externo()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
        // $this->paginaCadastrarProcessoEmBloco->bntTramitarBloco();
        // $this->paginaCadastrarProcessoEmBloco->tramitarProcessoExternamente(
        //     self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'],
        //     self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false
        // );
        sleep(10);
    }
}