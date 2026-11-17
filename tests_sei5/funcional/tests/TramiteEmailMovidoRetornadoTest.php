<?php

use PHPUnit\Framework\Attributes\{Depends, Group};

/**
 * Issue #1239 - falha no recebimento de processo apos movimentacao de e-mail entre processos.
 *
 * O processo A e criado no ORG1 por fixture e o e-mail (com arquivo anexado) e enviado pela tela
 * "Enviar Correspondencia Eletronica".
 *
 *   1. ORG1 envia o processo A para ORG2 (processo aberto);
 *   2. ORG2 move o e-mail para outro processo B e devolve A para ORG1 - o e-mail e cancelado no ORG1;
 *   3. ORG2 move o e-mail de B para A e envia A novamente para ORG1;
 *   4. ORG1 deve receber o e-mail novamente.
 *
 * Execution Groups
 * #[Group('execute_alone_group9')]
 */
class TramiteEmailMovidoRetornadoTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $protocoloA;
    public static $protocoloB;

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

    private function listarDocumentosNoBanco(string $strContexto): array
    {
        $objBanco = new DatabaseUtils($strContexto);
        return $objBanco->query(
            'select d.id_documento, p.sta_estado, d.sta_documento,
                    (select count(*) from anexo a where a.id_protocolo = d.id_documento) as anexos
               from documento d
               join protocolo p on p.id_protocolo = d.id_documento
              where d.id_procedimento = (select id_protocolo from protocolo where protocolo_formatado = ?)
              order by d.id_documento',
            array(self::$protocoloA)
        );
    }

    private function contarDocumentos(string $strContexto, bool $bolCancelados): int
    {
        $numTotal = 0;
        foreach ($this->listarDocumentosNoBanco($strContexto) as $arrDoc) {
            if (($arrDoc['STA_ESTADO'] == ProtocoloRN::$TE_DOCUMENTO_CANCELADO) === $bolCancelados) {
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

    /**
     * Cria no ORG1 um processo contendo um e-mail com arquivo anexado, enviado pela tela do SEI
     */
    private function criarProcessoComEmailNaOrigem(): string
    {
        $arrProcesso = $this->gerarDadosProcessoTeste(self::$remetente);
        $this->cadastrarProcessoFixture($arrProcesso);

        $this->abrirProcesso($arrProcesso['PROTOCOLO']);
        $this->paginaProcesso->navegarSelecionarEnviarEmail();
        $this->paginaEnviarEmail->enviar(self::PASTA_ARQUIVOS_TESTE . '/arquivo_pequeno_A.pdf');

        return $arrProcesso['PROTOCOLO'];
    }

    private function devolverParaOrigem(): void
    {
        $this->entrarComo(self::$destinatario);
        $this->abrirProcesso(self::$protocoloA);
        $this->tramitarProcessoExternamenteMultiplosOrgaoDestinatario(true);
        putenv('DATABASE_HOST=org1-database');
    }

    /**
     * #[Depends('CenarioBaseTestCase::setUpBeforeClass')]
     */
    public function test_enviar_processo_com_email()
    {
        putenv('DATABASE_HOST=org1-database');
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        $this->entrarComo(self::$destinatario);
        $this->configurarMapeamento(self::$remetente, CONTEXTO_ORGAO_B, false);

        $this->entrarComo(self::$remetente);
        $this->configurarMapeamento(self::$destinatario, CONTEXTO_ORGAO_A, true);

        self::$protocoloA = $this->criarProcessoComEmailNaOrigem();
        $arrDocumentos = $this->listarDocumentosNoBanco(CONTEXTO_ORGAO_A);
        $this->assertCount(1, $arrDocumentos, 'O e-mail deveria ter sido gerado no processo.');
        $this->assertEquals(1, $arrDocumentos[0]['ANEXOS'], 'O e-mail deveria possuir o arquivo anexado.');

        $this->abrirProcesso(self::$protocoloA);
        $this->tramitarProcessoExternamente(
            self::$protocoloA,
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
            return $this->contarDocumentos(CONTEXTO_ORGAO_B, false) === 1;
        });

        $this->assertEquals(1, $this->contarDocumentos(CONTEXTO_ORGAO_B, false), 'O e-mail deveria chegar ao ORG2.');
    }

    #[Depends('test_enviar_processo_com_email')]
    public function test_mover_email_no_destino_e_devolver()
    {
        // Processo B no ORG2, para onde o e-mail sera movido
        putenv('DATABASE_HOST=org2-database');
        $arrProcessoB = $this->gerarDadosProcessoTeste(self::$destinatario);
        self::$protocoloB = $this->cadastrarProcessoFixture($arrProcessoB)->getStrProtocoloFormatado();

        $this->entrarComo(self::$destinatario);
        $this->moverPrimeiroDocumento(self::$protocoloA, self::$protocoloB, 'Move e-mail para outro processo.');

        $this->devolverParaOrigem();
        $this->processarPendenciasAte(function () {
            return $this->contarDocumentos(CONTEXTO_ORGAO_A, true) === 1;
        });

        $this->assertEquals(1, $this->contarDocumentos(CONTEXTO_ORGAO_A, true), 'O e-mail movido no ORG2 deveria constar como cancelado no ORG1.');
    }

    #[Depends('test_mover_email_no_destino_e_devolver')]
    public function test_retornar_email_ao_processo_e_reenviar()
    {
        putenv('DATABASE_HOST=org2-database');
        $this->entrarComo(self::$destinatario);
        $this->moverPrimeiroDocumento(self::$protocoloB, self::$protocoloA, 'Devolvendo e-mail ao processo.');

        $this->devolverParaOrigem();
        $this->processarPendenciasAte(function () {
            return $this->contarDocumentos(CONTEXTO_ORGAO_A, false) === 1;
        });

        $this->assertEquals(
            1,
            $this->contarDocumentos(CONTEXTO_ORGAO_A, false),
            'ISSUE #1239: o e-mail devolvido ao processo no ORG2 nao foi recebido no ORG1. Documentos no ORG1: '
            . json_encode($this->listarDocumentosNoBanco(CONTEXTO_ORGAO_A))
        );

        // O arquivo do e-mail recebido deve ser o seu componente digital (html) e nao outro anexo do e-mail original
        $objBanco = new DatabaseUtils(CONTEXTO_ORGAO_A);
        $arrAnexos = $objBanco->query(
            'select a.nome, a.tamanho,
                    (select max(c.tamanho) from md_pen_componente_digital c where c.id_documento = d.id_documento) as tamanho_componente
               from documento d
               join protocolo p on p.id_protocolo = d.id_documento
               join anexo a on a.id_protocolo = d.id_documento
              where d.id_procedimento = (select id_protocolo from protocolo where protocolo_formatado = ?)
                and p.sta_estado <> ?',
            array(self::$protocoloA, ProtocoloRN::$TE_DOCUMENTO_CANCELADO)
        );

        $this->assertCount(1, $arrAnexos, 'ISSUE #1239: o e-mail recebido deveria possuir um unico anexo.');
        $this->assertEquals(
            $arrAnexos[0]['TAMANHO_COMPONENTE'],
            $arrAnexos[0]['TAMANHO'],
            'ISSUE #1239: o anexo do e-mail recebido nao corresponde ao componente digital tramitado: ' . json_encode($arrAnexos)
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
