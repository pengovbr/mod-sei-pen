<?php

use PHPUnit\Framework\Attributes\{Depends, Group};

/**
 * Fluxo completo de processo aberto e sincronizado.
 *
 * Cobre os casos de uso CU-04, CU-06, CU-09 e CU-10 de
 * docs/casos-uso-processo-aberto.md:
 *
 *   CU-04  ORG1 envia mantendo o processo aberto
 *   CU-06  ORG1 inclui documento depois do envio; ORG2 sincroniza e passa a ve-lo
 *   CU-09  ORG2 inclui documento e devolve; ORG1 passa a ve-lo
 *   CU-10  ORG1 inclui outro documento; ORG2 sincroniza de novo
 *
 * Configuracao proposital (armadilha 3 do documento): a flag sin_multiplos_orgaos
 * fica 'S' apenas na ORIGEM. O destino recebe mapeamento com 'N', que e o estado
 * real de campo - o array EnvioMultiplosOrgaos lista quem RECEBE, entao a unidade
 * de origem nunca esta nele. Habilitar os dois lados mascara defeitos de devolucao.
 *
 * Arquivos distintos em cada documento, de proposito: conteudo repetido cai na
 * ACH-03 e falharia por motivo alheio a este cenario.
 *
 * Execution Groups
 * #[Group('execute_alone_group9')]
 */
class ProcessoAbertoSincronizacaoFluxoTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;

    /**
     * Cria o Mapeamento de Envio Parcial para a contraparte.
     * $bolAtivar so vale para quem ORIGINA o envio com processo aberto.
     */
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
        $this->acessarSistema(
            $arrOrgao['URL'],
            $arrOrgao['SIGLA_UNIDADE'],
            $arrOrgao['LOGIN'],
            $arrOrgao['SENHA']
        );
    }

    /**
     * A devolucao e a sincronizacao sao sequencias de dois tramites, e cada um
     * avanca alguns status por ciclo (armadilha 4). Uma execucao so nao basta.
     */
    private function processarPendencias(int $numCiclos = 8): void
    {
        for ($i = 0; $i < $numCiclos; $i++) {
            $this->executarTramitarPendenciasSimples();
            sleep(3);
        }
    }

    private function incluirDocumentoExterno(array $arrOrgao, string $strContextoBanco, string $strArquivo): void
    {
        putenv('DATABASE_HOST=' . $strContextoBanco);
        $arrDados = $this->gerarDadosDocumentoExternoTeste($arrOrgao, $strArquivo);
        $objProtocoloDTO = $this->consultarProcessoFixture(
            self::$processoTeste['PROTOCOLO'],
            ProtocoloRN::$TP_PROCEDIMENTO
        );
        $this->cadastrarDocumentoExternoFixture($arrDados, $objProtocoloDTO->getDblIdProtocolo());
    }

    /**
     * Aciona o botao de sincronizar e fecha o alerta de confirmacao.
     * Sem fechar, o proximo comando do Selenium falha com "unexpected alert open".
     */
    private function solicitarSincronizacaoEConfirmar(): void
    {
        $this->paginaProcesso->solicitarSincronizacao('Sincronizar Processo');

        $strAlerta = $this->paginaBase->alertTextAndClose(true);

        $this->assertStringContainsString(
            'de sincroniza',
            $strAlerta,
            'A solicitacao de sincronizacao nao foi confirmada.'
        );
    }

    private function contarDocumentos(array $arrOrgao): int
    {
        $this->entrarComo($arrOrgao);
        $this->abrirProcesso(self::$processoTeste['PROTOCOLO']);
        $arrDocumentos = $this->paginaProcesso->listarDocumentos();

        return is_array($arrDocumentos) ? count($arrDocumentos) : 0;
    }

    /**
     * CU-04: envio mantendo o processo aberto na origem.
     *
     * #[Depends('CenarioBaseTestCase::setUpBeforeClass')]
     */
    public function test_cu04_enviar_mantendo_processo_aberto()
    {
        putenv('DATABASE_HOST=org1-database');

        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Destino: mapeamento SEM a flag - estado real de campo.
        $this->entrarComo(self::$destinatario);
        $this->configurarMapeamento(self::$remetente, CONTEXTO_ORGAO_B, false);

        // Origem: mapeamento COM a flag - so ela origina.
        $this->entrarComo(self::$remetente);
        $this->configurarMapeamento(self::$destinatario, CONTEXTO_ORGAO_A, true);

        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        $objProtocoloDTO = $this->cadastrarProcessoFixture(self::$processoTeste);

        $arrDoc1 = $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_pequeno_A.pdf');
        $this->cadastrarDocumentoExternoFixture($arrDoc1, $objProtocoloDTO->getDblIdProtocolo());

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

        $this->assertEquals(
            1,
            $this->contarDocumentos(self::$destinatario),
            'CU-04: o destino deveria exibir o documento enviado.'
        );
    }

    /**
     * CU-06: documento incluido na origem apos o envio chega por sincronizacao.
     */
    #[Depends('test_cu04_enviar_mantendo_processo_aberto')]
    public function test_cu06_documento_da_origem_chega_por_sincronizacao()
    {
        $this->incluirDocumentoExterno(self::$remetente, 'org1-database', 'arquivo_pequeno_B.pdf');

        $this->assertEquals(
            2,
            $this->contarDocumentos(self::$remetente),
            'Pre-condicao: a origem deveria ter dois documentos antes da sincronizacao.'
        );

        $this->entrarComo(self::$destinatario);
        $this->abrirProcesso(self::$processoTeste['PROTOCOLO']);
        $this->assertTrue(
            $this->paginaProcesso->validarBotaoExiste('Sincronizar Processo'),
            'CU-06: o botao Sincronizar Processo deveria estar disponivel no destino.'
        );
        $this->solicitarSincronizacaoEConfirmar();

        putenv('DATABASE_HOST=org1-database');
        $this->processarPendencias();

        $this->assertEquals(
            2,
            $this->contarDocumentos(self::$destinatario),
            'CU-06: o documento incluido na origem nao chegou ao destino apos a sincronizacao.'
        );
    }

    /**
     * CU-09: destino inclui documento e devolve o processo.
     */
    #[Depends('test_cu06_documento_da_origem_chega_por_sincronizacao')]
    public function test_cu09_destino_inclui_documento_e_devolve()
    {
        $this->incluirDocumentoExterno(self::$destinatario, 'org2-database', 'arquivo_pequeno_C.pdf');

        $this->entrarComo(self::$destinatario);
        $this->abrirProcesso(self::$processoTeste['PROTOCOLO']);

        // Tela de devolucao em modo multiplos orgaos: destino fixo (armadilha 5).
        $this->tramitarProcessoExternamenteMultiplosOrgaoDestinatario(true);

        putenv('DATABASE_HOST=org1-database');
        $this->processarPendencias();

        $this->assertEquals(
            3,
            $this->contarDocumentos(self::$remetente),
            'CU-09: o documento incluido pelo destino nao chegou a origem apos a devolucao.'
        );
    }

    /**
     * CU-10: origem inclui novo documento e o destino sincroniza de novo.
     */
    #[Depends('test_cu09_destino_inclui_documento_e_devolve')]
    public function test_cu10_ciclo_completo_com_nova_sincronizacao()
    {
        $this->incluirDocumentoExterno(self::$remetente, 'org1-database', 'arquivo_pequeno.txt');

        $this->entrarComo(self::$destinatario);
        $this->abrirProcesso(self::$processoTeste['PROTOCOLO']);
        $this->solicitarSincronizacaoEConfirmar();

        putenv('DATABASE_HOST=org1-database');
        $this->processarPendencias();

        $this->assertEquals(
            4,
            $this->contarDocumentos(self::$destinatario),
            'CU-10: o destino deveria exibir os quatro documentos apos a nova sincronizacao.'
        );
    }

    /**
     * Deixa o ambiente neutro (armadilha 2): a flag sobrevive a classe e faz os
     * testes seguintes tramitarem em modo multiplos orgaos sem pedir.
     */
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
