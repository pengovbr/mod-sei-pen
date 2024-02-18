<?php

use Tests\Funcional\Sei\Fixtures\{ProtocoloFixture,ProcedimentoFixture,AtividadeFixture,ContatoFixture,ParticipanteFixture,RelProtocoloAssuntoFixture,AtributoAndamentoFixture,DocumentoFixture,AssinaturaFixture,AnexoProcessoFixture};

/**
 * Testes de trâmite de processos anexado
 */
class TramiteProcessoAnexadoTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTestePrincipal;
    public static $processoTesteAnexado;
    public static $documentoTeste1;
    public static $documentoTeste2;
    public static $documentoTeste3;
    public static $documentoTeste4;
    public static $protocoloTestePrincipal;
    public static $protocoloTesteAnexado;
    public static $protocoloTesteAnexadoId;
    public static $protocoloTestePrincipalId;

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
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Definição de dados de teste do processo principal
        self::$processoTestePrincipal = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        self::$documentoTeste2 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        // Definição de dados de teste do processo a ser anexado
        self::$processoTesteAnexado = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste3 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        self::$documentoTeste4 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        $parametros = [
            [
            'Descricao' => self::$processoTestePrincipal['DESCRICAO'],
            'Interessados' => self::$processoTestePrincipal['INTERESSADOS'],
            'Documentos' => [self::$documentoTeste1, self::$documentoTeste2],
            ],
            [
            'Descricao' => self::$processoTesteAnexado['DESCRICAO'],
            'Interessados' => self::$processoTesteAnexado['INTERESSADOS'],
            'Documentos' => [self::$documentoTeste3, self::$documentoTeste4],
            ]
        ];

        $objProtocoloFixture = new ProtocoloFixture();
        $objProtocolosDTO = $objProtocoloFixture->carregarVariados($parametros);
        
        // Cadastrar novo processo de teste principal e incluir documentos relacionados
        $i = 0;
        foreach($objProtocolosDTO as $objProtocoloDTO) {
            $objProcedimentoFixture = new ProcedimentoFixture();

            $objProcedimentoDTO = $objProcedimentoFixture->carregar([
                'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
            ]);

            $objAtividadeFixture = new AtividadeFixture();
            $objAtividadeDTO = $objAtividadeFixture->carregar([
                'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
                'Conclusao' => \InfraData::getStrDataHoraAtual(),
                'IdTarefa' => \TarefaRN::$TI_GERACAO_PROCEDIMENTO,
                'IdUsuarioConclusao' => 100000001
            ]);

            $objContatoFixture = new ContatoFixture();
            $objContatoDTO = $objContatoFixture->carregar([
                'Nome' => self::$processoTestePrincipal['INTERESSADOS']
            ]);

            $objParticipanteFixture = new ParticipanteFixture();
            $objParticipanteDTO = $objParticipanteFixture->carregar([
                'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
                'IdContato' => $objContatoDTO->getNumIdContato()
            ]);

            $objProtocoloAssuntoFixture = new RelProtocoloAssuntoFixture();
            $objProtocoloAssuntoFixture->carregar([
                'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
            ]);

            $objAtributoAndamentoFixture = new AtributoAndamentoFixture();
            $objAtributoAndamentoFixture->carregar([
                'IdAtividade' => $objAtividadeDTO->getNumIdAtividade()
            ]);
            
            // Incluir e assinar documentos relacionados
            foreach($parametros[$i]['Documentos'] as $documento) {
                $objDocumentoFixture = new DocumentoFixture();
                $objDocumentoDTO = $objDocumentoFixture->carregar([
                    'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
                    'IdProcedimento' => $objProcedimentoDTO->getDblIdProcedimento(),
                    'Descricao' => $documento['DESCRICAO'],
                ]);
                
                // Armazenar nome que o arquivo receberá no org destinatário
                $docs[$i][] = str_pad($objDocumentoDTO->getDblIdDocumento(), 6, 0, STR_PAD_LEFT).'.html';

                $objAssinaturaFixture = new AssinaturaFixture();
                $objAssinaturaFixture->carregar([
                    'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
                    'IdDocumento' => $objDocumentoDTO->getDblIdDocumento(),
                ]);
            }
            $protocolo[$i]['formatado'] = $objProtocoloDTO->getStrProtocoloFormatado();
            $protocolo[$i]['id'] = $objProtocoloDTO->getDblIdProtocolo();
            $i++;
        }

        // Preencher variaveis que serão usadas posteriormente nos testes
        self::$documentoTeste1['ARQUIVO'] = $docs[0][0];
        self::$documentoTeste2['ARQUIVO'] = $docs[0][1];
        self::$documentoTeste3['ARQUIVO'] = $docs[1][0];
        self::$documentoTeste4['ARQUIVO'] = $docs[1][1];
        self::$protocoloTestePrincipal = $protocolo[0]['formatado'];
        self::$protocoloTestePrincipalId = $protocolo[0]['id'];
        self::$protocoloTesteAnexado = $protocolo[1]['formatado'];
        self::$protocoloTesteAnexadoId = $protocolo[1]['id'];

        // Realizar a anexação de processos
        $objAnexoProcessoFixture = new AnexoProcessoFixture();
        $objAnexoProcessoFixture->carregar([
            'IdProtocolo' => self::$protocoloTestePrincipalId,
            'IdDocumento' => self::$protocoloTesteAnexadoId,
        ]);
        
        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->abrirProcesso(self::$protocoloTestePrincipal);

        // Trâmitar Externamento processo para órgão/unidade destinatária
        $this->tramitarProcessoExternamente(
            self::$protocoloTestePrincipal,
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false
        );
        
    }
    
    /**
     * Teste de verificação do correto envio do processo anexado no sistema remetente
     *
     * @group verificacao_envio
     * @large
     *
     * @depends test_tramitar_processo_anexado_da_origem
     *
     * @return void
     */
    public function test_verificar_origem_processo_anexado()
    {
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->abrirProcesso(self::$protocoloTestePrincipal);

        $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $this->atualizarTramitesPEN();
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(utf8_encode("Processo em trâmite externo para "), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTestePrincipal, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);
        $this->validarProcessosTramitados(self::$protocoloTestePrincipal, $orgaosDiferentes);
    }
    
    /**
     * Teste de verificação do correto recebimento do processo anexado no destinatário
     *
     * @group verificacao_recebimento
     * @large
     *
     * @depends test_verificar_origem_processo_anexado
     *
     * @return void
     */
    public function test_verificar_destino_processo_anexado()
    {
        $strProtocoloTeste = self::$protocoloTestePrincipal;
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);
        $this->abrirProcesso(self::$protocoloTestePrincipal);
        $strTipoProcesso = utf8_encode("Tipo de processo no órgão de origem: ");
        $strTipoProcesso .= self::$processoTestePrincipal['TIPO_PROCESSO'];
        $strObservacoes = $orgaosDiferentes ? $strTipoProcesso : null;
        $this->validarDadosProcesso(
            self::$processoTestePrincipal['DESCRICAO'],
            self::$processoTestePrincipal['RESTRICAO'],
            $strObservacoes,
            array(self::$processoTestePrincipal['INTERESSADOS'])
        );

        $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);

        // Validação dos dados do processo principal
        $listaDocumentosProcessoPrincipal = $this->paginaProcesso->listarDocumentos();
        $this->assertEquals(3, count($listaDocumentosProcessoPrincipal));
        $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[0], self::$documentoTeste1, self::$destinatario);
        $this->validarDadosDocumento($listaDocumentosProcessoPrincipal[1], self::$documentoTeste2, self::$destinatario);

        $this->paginaProcesso->selecionarDocumento(self::$protocoloTesteAnexado);
        $this->assertTrue($this->paginaDocumento->ehProcessoAnexado());

        // Validação dos dados do processo anexado
        $this->paginaProcesso->pesquisar(self::$protocoloTesteAnexado);
        $listaDocumentosProcessoAnexado = $this->paginaProcesso->listarDocumentos();
        $this->assertEquals(2, count($listaDocumentosProcessoAnexado));
        $this->validarDadosDocumento($listaDocumentosProcessoAnexado[0], self::$documentoTeste3, self::$destinatario);
        $this->validarDadosDocumento($listaDocumentosProcessoAnexado[1], self::$documentoTeste4, self::$destinatario);
    }
}
