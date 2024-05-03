<?php

use Tests\Funcional\Sei\Fixtures\{ProtocoloFixture,ProcedimentoFixture,AtividadeFixture,ContatoFixture,ParticipanteFixture,RelProtocoloAssuntoFixture,AtributoAndamentoFixture,DocumentoFixture,AssinaturaFixture,AnexoFixture,AnexoProcessoFixture};

class TramiteBlocoExternoLimiteAnexoTest extends CenarioBaseTestCase
{
    protected static $numQtyProcessos = 2; // max: 99
    protected static $tramitar = true; // mude para false, caso queira rodar o script sem o tramite final

    public static $remetente;
    public static $destinatario;
    public static $documentoTeste1;
    public static $documentoTeste2;
    public static $protocoloTestePrincipal;
    public static $processoTestePrincipal;

    function setUp(): void 
    {
        parent::setUp();
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

    }

    /**
     * Teste inicial de trâmite de um processo contendo outro anexado
     *
     * @group envio
     * @large
     * 
     * @return void
     */
    public function test_tramitar_processo_anexado_da_origem()
    {
        // Definição de dados de teste do processo principal
        self::$documentoTeste1 = $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_pequeno_A.pdf');
        self::$documentoTeste2 = $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_pequeno_A.pdf');

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

            //Incluir novos documentos relacionados
            $objDocumentoFixture = new DocumentoFixture();
            $objDocumentoDTO = $objDocumentoFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo(),
                'IdProcedimento' => $objProcedimentoDTO->getDblIdProcedimento(),
                'Descricao' => self::$documentoTeste1['DESCRICAO'],
                'StaProtocolo' => ProtocoloRN::$TP_DOCUMENTO_RECEBIDO,
                'StaDocumento' => DocumentoRN::$TD_EXTERNO,
                'IdConjuntoEstilos' => NULL,
            ]);

            //Adicionar anexo ao documento
            $objAnexoFixture = new AnexoFixture();
            $objAnexoFixture->carregar([
                'IdProtocolo' => $objDocumentoDTO->getDblIdDocumento(),
                'Nome' => basename(self::$documentoTeste1['ARQUIVO']),
            ]);   

            // $objAssinaturaFixture = new AssinaturaFixture();
            // $objAssinaturaFixture->carregar([
            //     'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo(),
            //     'IdDocumento' => $objDocumentoDTO->getDblIdDocumento(),
            //     'IdAtividade' => $objAtividadeDTO->getNumIdAtividade(),
            // ]);

            $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
            $objBlocoDeTramiteProtocoloFixtureDTO = $objBlocoDeTramiteProtocoloFixture->carregar([
                'IdProtocolo' => $objProtocoloFixtureDTO->getDblIdProtocolo(),
                'IdTramitaEmBloco' => $objBlocoDeTramiteDTO->getNumId(),
                'IdxRelBlocoProtocolo' => $objProtocoloFixtureDTO->getStrProtocoloFormatado()
            ]);

            self::$protocoloTestePrincipal['PROTOCOLO'] = $objProtocoloFixtureDTO->getStrProtocoloFormatado();
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
            sleep(10);
        } else {
            $this->paginaCadastrarProcessoEmBloco->bntVisualizarProcessos();
            $qtyProcessos = $this->paginaCadastrarProcessoEmBloco->retornarQuantidadeDeProcessosNoBloco();
            
            $this->assertEquals($qtyProcessos, self::$numQtyProcessos);
        }

        $this->sairSistema();
    }

    public function test_verificar_envio_processo()
    {
        $this->markTestIncomplete(
            'Tela de confirmação de envio suprimida. Aguardando refatoração da funcionalidade do bloco para refatorar este teste.'
        );
        
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
    }

    public function test_verificar_envio_tramite_em_bloco()
    {
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