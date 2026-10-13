<?php

use PHPUnit\Framework\Attributes\{Depends, Group};

/**
 * Issue #1238 - falha no recebimento de processo apos movimentacao de documento
 * externo entre processos, no ambito de envio parcial / multiplos orgaos.
 *
 *   1. ORG1 cria o processo A com documento externo e envia para ORG2 (processo aberto);
 *   2. ORG1 move o documento de A para outro processo B;
 *   3. ORG2 sincroniza - o documento passa a constar como cancelado;
 *   4. ORG1 devolve o documento, movendo-o de B para A;
 *   5. ORG2 sincroniza novamente - o documento deve voltar a aparecer.
 *
 * Ao mover de volta, o SEI cria uma NOVA associacao do documento em A e mantem a
 * antiga como movida. O mesmo documento segue entao duas vezes nos metadados:
 * uma retirada e outra ativa, com o mesmo hash. Antes da correcao, o passo 5 era
 * recusado com "Componente digital de pelo menos um dos documentos do processo
 * nao pode ser recebido".
 *
 * Execution Groups
 * #[Group('execute_alone_group9')]
 */
class TramiteSincronizacaoDocumentoMovidoRetornadoTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $processoSecundario;

    private function configurarMapeamento(array $arrContrapartida, string $strContexto, bool $bolAtivar): void
    {
        $this->paginaEnvioParcialListar->navegarEnvioParcialListar();
        $this->paginaCadastroMapEnvioCompDigitais->excluirMapeamentosExistentes();

        $this->paginaEnvioParcialListar->navegarEnvioParcialListar();
        $this->paginaCadastroMapEnvioCompDigitais->novo();
        $this->paginaCadastroMapEnvioCompDigitais->setarParametros(
            $arrContrapartida['REP_ESTRUTURAS'],
            $arrContrapartida['NOME_UNIDADE']
        );
        $this->paginaCadastroMapEnvioCompDigitais->salvar();
        sleep(1);

        $objBanco = new DatabaseUtils($strContexto);
        $objBanco->execute(
            'update md_pen_envio_comp_digitais set sin_multiplos_orgaos = ? where id_unidade_pen = ?',
            array($bolAtivar ? 'S' : 'N', $arrContrapartida['ID_ESTRUTURA'])
        );

        $arrFlag = $objBanco->query(
            'select sin_multiplos_orgaos from md_pen_envio_comp_digitais where id_unidade_pen = ?',
            array($arrContrapartida['ID_ESTRUTURA'])
        );
        $this->assertNotEmpty($arrFlag, 'Pre-condicao: Mapeamento de Envio Parcial nao gravado no contexto ' . $strContexto . '.');
    }

    private function encerrarSessoes(): void
    {
        foreach (array(CONTEXTO_ORGAO_A_URL, CONTEXTO_ORGAO_B_URL) as $strUrl) {
            try {
                $this->url($strUrl);
                $this->sairSistema();
            } catch (\Exception $e) {
                // ja estava deslogado neste orgao
            }
        }
    }

    private function entrarComo(array $arrOrgao): void
    {
        $this->encerrarSessoes();
        $this->acessarSistema($arrOrgao['URL'], $arrOrgao['SIGLA_UNIDADE'], $arrOrgao['LOGIN'], $arrOrgao['SENHA']);
    }

    private function processarPendenciasAte(callable $fnCondicao, int $numCiclosMax = 45): bool
    {
        for ($i = 0; $i < $numCiclosMax; $i++) {
            $this->executarTramitarPendenciasSimples();
            try {
                if ($fnCondicao()) {
                    return true;
                }
            } catch (\Exception $e) {
                // segue para o proximo ciclo
            }
            sleep(3);
        }
        return false;
    }

    /**
     * Situacao dos documentos do processo no banco do orgao: estado do protocolo
     * e quantidade de anexos, na ordem da arvore.
     */
    private function listarDocumentosNoBanco(string $strContexto): array
    {
        $objBanco = new DatabaseUtils($strContexto);
        return $objBanco->query(
            'select d.id_documento, p.sta_estado,
                    (select count(*) from anexo a where a.id_protocolo = d.id_documento) as anexos
               from documento d
               join protocolo p on p.id_protocolo = d.id_documento
              where d.id_procedimento = (select id_protocolo from protocolo where protocolo_formatado = ?)
              order by d.id_documento',
            array(self::$processoTeste['PROTOCOLO'])
        );
    }

    private function contarDocumentosAtivosComAnexo(string $strContexto): int
    {
        $numTotal = 0;
        foreach ($this->listarDocumentosNoBanco($strContexto) as $arrDoc) {
            if ($arrDoc['STA_ESTADO'] != ProtocoloRN::$TE_DOCUMENTO_CANCELADO && (int) $arrDoc['ANEXOS'] > 0) {
                $numTotal++;
            }
        }
        return $numTotal;
    }

    private function contarDocumentosCancelados(string $strContexto): int
    {
        $numTotal = 0;
        foreach ($this->listarDocumentosNoBanco($strContexto) as $arrDoc) {
            if ($arrDoc['STA_ESTADO'] == ProtocoloRN::$TE_DOCUMENTO_CANCELADO) {
                $numTotal++;
            }
        }
        return $numTotal;
    }

    private function moverPrimeiroDocumento(string $strProtocoloOrigem, string $strProtocoloDestino, string $strMotivo): void
    {
        $this->abrirProcesso($strProtocoloOrigem);
        $documentoParaMover = $this->paginaProcesso->listarDocumentos()[0];
        $this->paginaProcesso->selecionarDocumento($documentoParaMover);
        $this->paginaDocumento->navegarParaMoverDocumento();
        $this->paginaMoverDocumento->moverDocumentoParaProcesso($strProtocoloDestino, $strMotivo);
    }

    private function sincronizarNoDestino(): void
    {
        $this->entrarComo(self::$destinatario);
        $this->abrirProcesso(self::$processoTeste['PROTOCOLO']);
        $this->paginaProcesso->solicitarSincronizacao('Sincronizar Processo');
        $this->paginaBase->alertTextAndClose(true);
        putenv('DATABASE_HOST=org1-database');
    }

    /**
     * #[Depends('CenarioBaseTestCase::setUpBeforeClass')]
     */
    public function test_enviar_processo_com_documento_externo()
    {
        putenv('DATABASE_HOST=org1-database');
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        $this->entrarComo(self::$destinatario);
        $this->configurarMapeamento(self::$remetente, CONTEXTO_ORGAO_B, false);

        $this->entrarComo(self::$remetente);
        $this->configurarMapeamento(self::$destinatario, CONTEXTO_ORGAO_A, true);

        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        $objProtocoloDTO = $this->cadastrarProcessoFixture(self::$processoTeste);
        $arrDocumento = $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_pequeno_A.pdf');
        $this->cadastrarDocumentoExternoFixture($arrDocumento, $objProtocoloDTO->getDblIdProtocolo());

        $arrProcessoSecundario = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$processoSecundario = $this->cadastrarProcessoFixture($arrProcessoSecundario)->getStrProtocoloFormatado();

        $this->abrirProcesso(self::$processoTeste['PROTOCOLO']);
        $this->tramitarProcessoExternamente(
            self::$processoTeste['PROTOCOLO'],
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false,
            null,
            PEN_WAIT_TIMEOUT,
            true,
            true
        );

        $this->processarPendenciasAte(function () {
            return $this->contarDocumentosAtivosComAnexo(CONTEXTO_ORGAO_B) === 1;
        });

        $this->assertEquals(1, $this->contarDocumentosAtivosComAnexo(CONTEXTO_ORGAO_B));
    }

    #[Depends('test_enviar_processo_com_documento_externo')]
    public function test_mover_documento_para_outro_processo_e_sincronizar()
    {
        $this->entrarComo(self::$remetente);
        $this->moverPrimeiroDocumento(self::$processoTeste['PROTOCOLO'], self::$processoSecundario, 'Move doc externo para outro processo.');

        $this->sincronizarNoDestino();
        $this->processarPendenciasAte(function () {
            return $this->contarDocumentosCancelados(CONTEXTO_ORGAO_B) === 1;
        });

        $this->assertEquals(
            1,
            $this->contarDocumentosCancelados(CONTEXTO_ORGAO_B),
            'O documento movido na origem deveria constar como cancelado no destino.'
        );
    }

    #[Depends('test_mover_documento_para_outro_processo_e_sincronizar')]
    public function test_devolver_documento_ao_processo_e_sincronizar()
    {
        $this->entrarComo(self::$remetente);
        $this->moverPrimeiroDocumento(self::$processoSecundario, self::$processoTeste['PROTOCOLO'], 'Devolvendo documento externo.');

        $this->sincronizarNoDestino();
        $this->processarPendenciasAte(function () {
            return $this->contarDocumentosAtivosComAnexo(CONTEXTO_ORGAO_B) === 1;
        });

        $this->assertEquals(
            1,
            $this->contarDocumentosAtivosComAnexo(CONTEXTO_ORGAO_B),
            'ISSUE #1238: o documento devolvido ao processo na origem nao foi recebido no destino. Documentos no destino: '
            . json_encode($this->listarDocumentosNoBanco(CONTEXTO_ORGAO_B))
        );
    }

    public static function tearDownAfterClass(): void
    {
        foreach (array(CONTEXTO_ORGAO_A, CONTEXTO_ORGAO_B) as $strContexto) {
            try {
                $objBanco = new DatabaseUtils($strContexto);
                $objBanco->execute('delete from md_pen_envio_comp_digitais', array());
            } catch (\Exception $e) {
                // ambiente pode nao estar disponivel no encerramento
            }
        }

        parent::tearDownAfterClass();
    }
}
