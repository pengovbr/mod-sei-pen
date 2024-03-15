<?php

use Tests\Funcional\Sei\Fixtures\{ProtocoloFixture,ProcedimentoFixture,AtividadeFixture,ParticipanteFixture,RelProtocoloAssuntoFixture,AtributoAndamentoFixture,DocumentoFixture,AssinaturaFixture};

class TramiteBlocoExternoLimiteTest extends CenarioBaseTestCase
{
    protected static $numQtyProcessos = 2; // max: 99
    protected static $tramitar = false; // mude para false, caso queira rodar o script sem o tramite final

    public static $remetente;
    public static $destinatario;
    public static $penOrgaoExternoId;
    public static $protocoloTestePrincipal;

    function setUp(): void 
    {
        parent::setUp();
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
    }

    public function teste_tramite_bloco_externo()
    {

        $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
        $objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();

        for ($i = 0; $i < self::$numQtyProcessos; $i++) {
            $objProtocoloFixture = new ProtocoloFixture();
            $objProtocoloFixtureDTO = $objProtocoloFixture->carregar([
                'Descricao' => 'teste'
            ]);

            $objProcedimentoFixture = new ProcedimentoFixture();
            $objProcedimentoDTO = $objProcedimentoFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo()
            ]);

            $objAtividadeFixture = new AtividadeFixture();
            $objAtividadeDTO = $objAtividadeFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo(),
                'IdTarefa' => TarefaRN::$TI_GERACAO_PROCEDIMENTO,
            ]);

            $objParticipanteFixture = new ParticipanteFixture();
            $objParticipanteFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo(),
                'IdContato' => 100000006,
            ]);

            $objProtocoloAssuntoFixture = new RelProtocoloAssuntoFixture();
            $objProtocoloAssuntoFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo()
            ]);

            $objAtributoAndamentoFixture = new AtributoAndamentoFixture();
            $objAtributoAndamentoFixture->carregar([
                'IdAtividade' => $objAtividadeDTO->getNumIdAtividade()
            ]);

            $objDocumentoFixture = new DocumentoFixture();
            $objDocumentoDTO = $objDocumentoFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo(),
                'IdProcedimento' => $objProcedimentoDTO->getDblIdProcedimento(),
            ]);

            $objAssinaturaFixture = new AssinaturaFixture();
            $objAssinaturaFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo(),
                'IdDocumento' => $objDocumentoDTO->getDblIdDocumento(),
            ]);

            $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
            $objBlocoDeTramiteProtocoloFixtureDTO = $objBlocoDeTramiteProtocoloFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo(),
                'IdTramitaEmBloco' => $objBlocoDeTramiteDTO->getNumId(),
                'IdxRelBlocoProtocolo' => $objProtocoloFixtureDTO->getStrProtocoloFormatado()
            ]);

            self::$protocoloTestePrincipal = $objProtocoloFixtureDTO->getStrProtocoloFormatado();
        }

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();

        if (self::$tramitar == true) {
            $this->paginaCadastrarProcessoEmBloco->bntTramitarBloco();
            $this->paginaCadastrarProcessoEmBloco->tramitarProcessoExternamente(
                self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'],
                self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false
            );
            sleep(5);

        } else {
            $this->paginaCadastrarProcessoEmBloco->bntVisualizarProcessos();
            $qtyProcessos = $this->paginaCadastrarProcessoEmBloco->retornarQuantidadeDeProcessosNoBloco();
            
            $this->assertEquals($qtyProcessos, self::$numQtyProcessos);
        }

        $this->sairSistema();
    }

     /**
     * Verificar se o bloco foi enviado
     *
     *
     * @return void
     */
    public function test_verificar_envio_processo()
    {
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->visualizarProcessoTramitadosEmLote($this);
        $this->navegarProcessoEmLote(0);

        $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $testCase->refresh();
            $paginaTramitarProcessoEmLote = new PaginaTramitarProcessoEmLote($testCase);
            $testCase->assertStringContainsString(utf8_encode("Nenhum registro encontrado."), $paginaTramitarProcessoEmLote->informacaoLote());
            return true;
        }, PEN_WAIT_TIMEOUT_PROCESSAMENTO_EM_LOTE);
        
        sleep(5);

        $this->sairSistema();
    }

    /**
     * Verificar atualização do bloco
     *
     *
     * @return void
     */
    public function test_verificar_envio_tramite_em_bloco()
    {
        
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
        $novoStatus = $this->paginaCadastrarProcessoEmBloco->retornarTextoColunaDaTabelaDeBlocos();

        if (self::$tramitar == true) {
            $this->assertNotEquals('Aberto', $novoStatus);
        } else {
            $this->assertEquals('Aberto', $novoStatus);
        }  

        $this->sairSistema();
    }
}