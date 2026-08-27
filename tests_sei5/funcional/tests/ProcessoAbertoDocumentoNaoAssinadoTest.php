<?php

use PHPUnit\Framework\Attributes\{Depends, Group};

/**
 * CU-17 de docs/casos-uso-processo-aberto.md.
 *
 * Documento interno GERADO e NAO ASSINADO impede a tramitacao do processo aberto.
 *
 * O modulo tem a guarda em varios pontos:
 *   pen_procedimento_expedir.php          - bloqueia o envio pela tela
 *   SincronizacaoExpedirProcedimentoRN    - recusa sincronizacao e envio
 *
 * Este teste cobre o caminho de tela, que e deterministico. A assercao e sobre o
 * EFEITO: o documento nao assinado nao pode chegar ao outro orgao.
 *
 * Execution Groups
 * #[Group('execute_alone_group11')]
 */
class ProcessoAbertoDocumentoNaoAssinadoTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;

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

    private function contarDocumentos(array $arrOrgao): int
    {
        $this->entrarComo($arrOrgao);
        $this->abrirProcesso(self::$processoTeste['PROTOCOLO']);
        $arrDocumentos = $this->paginaProcesso->listarDocumentos();

        return is_array($arrDocumentos) ? count($arrDocumentos) : 0;
    }

    /**
     * #[Depends('CenarioBaseTestCase::setUpBeforeClass')]
     */
    public function test_enviar_processo_aberto_para_o_destino()
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

        $arrDoc = $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_pequeno_A.pdf');
        $this->cadastrarDocumentoExternoFixture($arrDoc, $objProtocoloDTO->getDblIdProtocolo());

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
            'Pre-condicao: o destino deveria ter recebido o documento externo.'
        );
    }

    /**
     * CU-17: documento interno nao assinado impede a devolucao.
     */
    #[Depends('test_enviar_processo_aberto_para_o_destino')]
    public function test_cu17_documento_interno_nao_assinado_impede_tramitacao()
    {
        putenv('DATABASE_HOST=org2-database');

        $objProtocoloDTO = $this->consultarProcessoFixture(
            self::$processoTeste['PROTOCOLO'],
            ProtocoloRN::$TP_PROCEDIMENTO
        );

        // Documento GERADO e deliberadamente NAO assinado.
        $arrDocInterno = $this->gerarDadosDocumentoInternoTeste(self::$destinatario);
        $this->cadastrarDocumentoInternoFixture(
            $arrDocInterno,
            $objProtocoloDTO->getDblIdProtocolo(),
            false
        );

        $this->assertEquals(
            2,
            $this->contarDocumentos(self::$destinatario),
            'Pre-condicao: o destino deveria ter dois documentos, um deles nao assinado.'
        );

        // Tentativa de devolucao. A guarda deve impedir - por excecao na tela ou
        // por recusa no processamento. Qualquer das duas e aceitavel aqui.
        $this->entrarComo(self::$destinatario);
        $this->abrirProcesso(self::$processoTeste['PROTOCOLO']);

        try {
            $this->tramitarProcessoExternamenteMultiplosOrgaoDestinatario(true);
        } catch (\Exception $e) {
            // esperado: a guarda interrompeu a tramitacao
        }

        putenv('DATABASE_HOST=org1-database');
        for ($i = 0; $i < 6; $i++) {
            $this->executarTramitarPendenciasSimples();
            sleep(3);
        }

        $this->assertEquals(
            1,
            $this->contarDocumentos(self::$remetente),
            'CU-17: a origem nao pode receber o documento interno nao assinado. Se contar 2, a '
            . 'guarda de documento gerado e nao assinado deixou de funcionar.'
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
