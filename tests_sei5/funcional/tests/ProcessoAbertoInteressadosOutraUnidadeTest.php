<?php

use PHPUnit\Framework\Attributes\{Depends, Group};

/**
 * CU-19 de docs/casos-uso-processo-aberto.md.
 *
 * Interessado cadastrado por OUTRA unidade do orgao de destino deve sobreviver a
 * sincronizacao.
 *
 * O nucleo do SEI (ProtocoloRN::alterarRN0202) recusa remover participante que
 * pertence a unidade diferente da unidade atual da sessao. Se a sincronizacao
 * montar a lista de interessados apenas com os recebidos, ela remove
 * implicitamente os das outras unidades - e o nucleo derruba o recebimento
 * INTEIRO, nao so o interessado.
 *
 * Por isso a assercao e dupla:
 *   1. o interessado da outra unidade continua la;
 *   2. o recebimento concluiu - provado pelo documento novo ter chegado.
 *
 * A segunda e a que pega a regressao real: sem a preservacao, nada chega.
 *
 * Execution Groups
 * #[Group('execute_alone_group12')]
 */
class ProcessoAbertoInteressadosOutraUnidadeTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;

    /** Unidade do ORG2 diferente da que recebe o processo. */
    const ID_UNIDADE_OUTRA = 110000002;

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

    private function obterIdProtocoloNoDestino(): int
    {
        $objBanco = new DatabaseUtils(CONTEXTO_ORGAO_B);
        $arr = $objBanco->query(
            'select id_protocolo from protocolo where protocolo_formatado = ?',
            array(self::$processoTeste['PROTOCOLO'])
        );
        $this->assertNotEmpty($arr, 'Processo nao encontrado no destino.');

        return (int) $arr[0]['ID_PROTOCOLO'];
    }

    private function contarInteressadosDaOutraUnidade(int $numIdProtocolo): int
    {
        $objBanco = new DatabaseUtils(CONTEXTO_ORGAO_B);
        $arr = $objBanco->query(
            'select count(*) as total from participante
              where id_protocolo = ? and id_unidade = ? and sta_participacao = ?',
            array($numIdProtocolo, self::ID_UNIDADE_OUTRA, 'I')
        );

        return (int) $arr[0]['TOTAL'];
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
     * Cadastra, no destino, um interessado pertencente a OUTRA unidade.
     */
    #[Depends('test_enviar_processo_aberto_para_o_destino')]
    public function test_cadastrar_interessado_de_outra_unidade_no_destino()
    {
        $numIdProtocolo = $this->obterIdProtocoloNoDestino();

        $objBanco = new DatabaseUtils(CONTEXTO_ORGAO_B);

        // min() em vez de "limit 1": LIMIT nao existe no Oracle 11g e produz
        // ORA-00933. min() e portavel entre os quatro bancos suportados.
        $arrContato = $objBanco->query('select min(id_contato) as id_contato from contato', array());
        $this->assertNotEmpty(
            $arrContato[0]['ID_CONTATO'] ?? null,
            'Nenhum contato disponivel no destino.'
        );

        $arrProxId = $objBanco->query('select coalesce(max(id_participante), 0) + 1 as prox from participante', array());
        $arrProxSeq = $objBanco->query(
            'select coalesce(max(sequencia), 0) + 1 as prox from participante where id_protocolo = ?',
            array($numIdProtocolo)
        );

        $objBanco->execute(
            'insert into participante (id_participante, id_protocolo, id_contato, id_unidade, sta_participacao, sequencia)
             values (?, ?, ?, ?, ?, ?)',
            array(
                (int) $arrProxId[0]['PROX'],
                $numIdProtocolo,
                (int) $arrContato[0]['ID_CONTATO'],
                self::ID_UNIDADE_OUTRA,
                'I',
                (int) $arrProxSeq[0]['PROX'],
            )
        );

        $this->assertEquals(
            1,
            $this->contarInteressadosDaOutraUnidade($numIdProtocolo),
            'Pre-condicao: o interessado da outra unidade deveria ter sido cadastrado.'
        );
    }

    /**
     * CU-19: apos a sincronizacao, o interessado da outra unidade sobrevive e o
     * recebimento conclui.
     */
    #[Depends('test_cadastrar_interessado_de_outra_unidade_no_destino')]
    public function test_cu19_interessado_de_outra_unidade_sobrevive_a_sincronizacao()
    {
        // Documento novo na origem: e a prova de que o recebimento concluiu.
        putenv('DATABASE_HOST=org1-database');
        $arrDoc2 = $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_pequeno_B.pdf');
        $objProtocoloOrigemDTO = $this->consultarProcessoFixture(
            self::$processoTeste['PROTOCOLO'],
            ProtocoloRN::$TP_PROCEDIMENTO
        );
        $this->cadastrarDocumentoExternoFixture($arrDoc2, $objProtocoloOrigemDTO->getDblIdProtocolo());

        $this->entrarComo(self::$destinatario);
        $this->abrirProcesso(self::$processoTeste['PROTOCOLO']);
        $this->paginaProcesso->solicitarSincronizacao('Sincronizar Processo');
        $this->paginaBase->alertTextAndClose(true);

        putenv('DATABASE_HOST=org1-database');
        for ($i = 0; $i < 8; $i++) {
            $this->executarTramitarPendenciasSimples();
            sleep(3);
        }

        $numIdProtocolo = $this->obterIdProtocoloNoDestino();

        $this->assertEquals(
            1,
            $this->contarInteressadosDaOutraUnidade($numIdProtocolo),
            'CU-19: o interessado cadastrado por outra unidade foi removido pela sincronizacao.'
        );

        $this->assertEquals(
            2,
            $this->contarDocumentos(self::$destinatario),
            'CU-19: o recebimento nao concluiu. Sem preservar os participantes de outras unidades, '
            . 'ProtocoloRN::alterarRN0202 derruba a transacao inteira e nada chega.'
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
