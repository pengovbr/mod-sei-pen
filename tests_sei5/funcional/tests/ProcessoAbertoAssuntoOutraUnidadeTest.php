<?php

use PHPUnit\Framework\Attributes\{Depends, Group};

/**
 * Assunto cadastrado pela unidade que trabalha o processo deve manter no
 * recebimento de devolucao e de sincronizacao em modo multiplos orgaos.
 *
 * o SEI cria o processo, envia com o
 * processo aberto e, ao receber a devolucao, o recebimento inteiro aborta com
 * "O assunto ... nao pode ser excluido porque foi adicionado por outra unidade".
 *
 * Mecanismo: ao atualizar um processo existente, a sincronizacao de metadados
 * monta a lista de assuntos apenas com os sugeridos do tipo de processo, com a
 * sessao na unidade receptora do modulo. O nucleo (ProtocoloRN::alterarRN0203)
 * remove todo assunto fora da lista e recusa remover o de unidade diferente da
 * sessao. E o mesmo defeito ja corrigido para interessados.
 *
 * O gatilho e um assunto fora dos sugeridos, incluido pela unidade de trabalho -
 * como faz o usuario em "Alterar Processo". Os dois lados sao cobertos:
 *   - origem recebendo a devolucao (caso de campo);
 *   - destino recebendo a sincronizacao.
 *
 * A prova de que o recebimento concluiu e o documento novo ter chegado: sem a
 * correcao, nada chega.
 *
 * Execution Groups
 * #[Group('execute_alone_group13')]
 */
class ProcessoAbertoAssuntoOutraUnidadeTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;

    /** Maior id de infra_log de cada orgao antes do cenario, para ler so os erros dele. */
    public static $arrIdLogInicial = array();

    /** Assunto extra (id_assunto_proxy) incluido pela unidade de trabalho de cada orgao. */
    public static $arrIdAssuntoProxyExtra = array();

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
     * Origem com a flag 'S'; destino apenas com o mapeamento.
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

        $arrFlag = $objBanco->query(
            'select sin_multiplos_orgaos from md_pen_envio_comp_digitais where id_unidade_pen = ?',
            array($arrContrapartida['ID_ESTRUTURA'])
        );
        $this->assertNotEmpty(
            $arrFlag,
            'Pre-condicao: Mapeamento de Envio Parcial nao gravado no contexto ' . $strContexto . '.'
        );
    }

    /**
     * Abre o processo e espera a arvore montar (ver ProcessoAbertoSincronizacaoFluxoTest).
     */
    private function abrirProcessoEAguardarArvore(string $strProtocolo): void
    {
        $this->abrirProcesso($strProtocolo);

        $this->waitUntil(function () {
            try {
                $this->paginaBase->frame(null);
                $this->paginaBase->elByXPath("//iframe[@id='ifrArvore' or @name='ifrArvore']");
                return true;
            } catch (\Exception $e) {
                return null;
            }
        }, PEN_WAIT_TIMEOUT);

        $this->paginaBase->frame(null);
    }

    /**
     * Processa pendencias ate o efeito chegar ou o erro de assunto aparecer no
     * log de qualquer dos orgaos. Os dois, porque a devolucao faz o destino
     * receber uma sincronizacao automatica antes. Parar no erro evita gastar
     * todos os ciclos num recebimento que ja abortou.
     */
    private function processarPendenciasAte(callable $fnCondicao, int $numCiclosMax = 45): void
    {
        for ($i = 0; $i < $numCiclosMax; $i++) {
            $this->executarTramitarPendenciasSimples();

            try {
                if ($fnCondicao() || $this->descreverErros() !== '') {
                    return;
                }
            } catch (\Exception $e) {
                // segue para o proximo ciclo
            }

            sleep(3);
        }
    }

    private function obterIdProtocolo(string $strContexto): int
    {
        $objBanco = new DatabaseUtils($strContexto);
        $arr = $objBanco->query(
            'select id_protocolo from protocolo where protocolo_formatado = ?',
            array(self::$processoTeste['PROTOCOLO'])
        );
        $this->assertNotEmpty($arr, 'Processo nao encontrado no contexto ' . $strContexto . '.');

        return (int) $arr[0]['ID_PROTOCOLO'];
    }

    private function contarDocumentosNoBanco(string $strContexto): int
    {
        $objBanco = new DatabaseUtils($strContexto);
        $arr = $objBanco->query(
            'select count(*) as total from documento
              where id_procedimento = (select id_protocolo from protocolo where protocolo_formatado = ?)',
            array(self::$processoTeste['PROTOCOLO'])
        );

        return empty($arr) ? 0 : (int) $arr[0]['TOTAL'];
    }

    private function registrarIdLogInicial(string $strContexto): void
    {
        $objBanco = new DatabaseUtils($strContexto);
        $arr = $objBanco->query('select max(id_infra_log) as maximo from infra_log', array());
        self::$arrIdLogInicial[$strContexto] = (int) ($arr[0]['MAXIMO'] ?? 0);
    }

    /**
     * Mensagem de "adicionado por outra unidade" gravada no log desde o inicio
     * do cenario. Aparece na falha para explicar por que nada chegou.
     */
    private function obterErroAssuntoOutraUnidade(string $strContexto)
    {
        $objBanco = new DatabaseUtils($strContexto);
        $arr = $objBanco->query(
            "select texto_log from infra_log where id_infra_log > ? and texto_log like '%por outra unidade%'",
            array(self::$arrIdLogInicial[$strContexto] ?? 0)
        );

        return empty($arr) ? null : substr((string) $arr[0]['TEXTO_LOG'], 0, 500);
    }

    private function descreverErros(): string
    {
        $strRetorno = '';
        foreach (array(CONTEXTO_ORGAO_A, CONTEXTO_ORGAO_B) as $strContexto) {
            $strErro = $this->obterErroAssuntoOutraUnidade($strContexto);
            if ($strErro !== null) {
                $strRetorno .= ' Log ' . $strContexto . ': ' . $strErro;
            }
        }

        return $strRetorno;
    }

    /**
     * Inclui no processo um assunto fora dos sugeridos, em nome da unidade de
     * trabalho do orgao - o que o usuario faz em "Alterar Processo".
     *
     * O assunto e escolhido entre os sugeridos de outros tipos de processo, para
     * garantir que existe e esta ativo, e fica fora dos que o processo ja tem.
     * rel_protocolo_assunto nao tem id proprio, entao a insercao nao disputa
     * sequencia com a aplicacao.
     */
    private function incluirAssuntoDaUnidadeDeTrabalho(array $arrOrgao, string $strContexto): void
    {
        $numIdProtocolo = $this->obterIdProtocolo($strContexto);
        $objBanco = new DatabaseUtils($strContexto);

        $arrUnidade = $objBanco->query(
            'select id_unidade from unidade where sigla = ?',
            array($arrOrgao['SIGLA_UNIDADE'])
        );
        $this->assertNotEmpty($arrUnidade, 'Unidade de trabalho nao encontrada no contexto ' . $strContexto . '.');

        $arrAssunto = $objBanco->query(
            'select min(id_assunto_proxy) as id_assunto_proxy from rel_tipo_procedimento_assunto
              where id_assunto_proxy not in (select id_assunto_proxy from rel_protocolo_assunto where id_protocolo = ?)
                and id_tipo_procedimento <> (select id_tipo_procedimento from procedimento where id_procedimento = ?)',
            array($numIdProtocolo, $numIdProtocolo)
        );
        $this->assertNotEmpty(
            $arrAssunto[0]['ID_ASSUNTO_PROXY'] ?? null,
            'Nenhum assunto disponivel para o cenario no contexto ' . $strContexto . '.'
        );

        $arrSequencia = $objBanco->query(
            'select coalesce(max(sequencia), 0) + 1 as prox from rel_protocolo_assunto where id_protocolo = ?',
            array($numIdProtocolo)
        );

        self::$arrIdAssuntoProxyExtra[$strContexto] = (int) $arrAssunto[0]['ID_ASSUNTO_PROXY'];

        $objBanco->execute(
            'insert into rel_protocolo_assunto (id_protocolo, id_protocolo_procedimento, id_assunto_proxy, id_unidade, sequencia)
             values (?, ?, ?, ?, ?)',
            array(
                $numIdProtocolo,
                $numIdProtocolo,
                self::$arrIdAssuntoProxyExtra[$strContexto],
                (int) $arrUnidade[0]['ID_UNIDADE'],
                (int) $arrSequencia[0]['PROX'],
            )
        );

        $this->assertTrue(
            $this->possuiAssuntoExtra($strContexto),
            'Pre-condicao: o assunto da unidade de trabalho nao foi incluido no contexto ' . $strContexto . '.'
        );
    }

    private function possuiAssuntoExtra(string $strContexto): bool
    {
        $objBanco = new DatabaseUtils($strContexto);
        $arr = $objBanco->query(
            'select count(*) as total from rel_protocolo_assunto where id_protocolo = ? and id_assunto_proxy = ?',
            array($this->obterIdProtocolo($strContexto), self::$arrIdAssuntoProxyExtra[$strContexto])
        );

        return (int) $arr[0]['TOTAL'] === 1;
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
     * Origem cria o processo com um assunto da unidade de trabalho alem do
     * sugerido e envia mantendo o processo aberto.
     *
     * #[Depends('CenarioBaseTestCase::setUpBeforeClass')]
     */
    public function test_origem_envia_processo_com_assunto_da_unidade_de_trabalho()
    {
        putenv('DATABASE_HOST=org1-database');

        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        $this->registrarIdLogInicial(CONTEXTO_ORGAO_A);
        $this->registrarIdLogInicial(CONTEXTO_ORGAO_B);

        $this->entrarComo(self::$destinatario);
        $this->configurarMapeamento(self::$remetente, CONTEXTO_ORGAO_B, false);

        $this->entrarComo(self::$remetente);
        $this->configurarMapeamento(self::$destinatario, CONTEXTO_ORGAO_A, true);

        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        $objProtocoloDTO = $this->cadastrarProcessoFixture(self::$processoTeste);

        $arrDoc = $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_pequeno_A.pdf');
        $this->cadastrarDocumentoExternoFixture($arrDoc, $objProtocoloDTO->getDblIdProtocolo());

        $this->incluirAssuntoDaUnidadeDeTrabalho(self::$remetente, CONTEXTO_ORGAO_A);

        $this->abrirProcessoEAguardarArvore(self::$processoTeste['PROTOCOLO']);
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
            return $this->contarDocumentosNoBanco(CONTEXTO_ORGAO_B) === 1;
        });

        $this->assertEquals(
            1,
            $this->contarDocumentosNoBanco(CONTEXTO_ORGAO_B),
            'Pre-condicao: o destino deveria ter recebido o processo.'
        );
    }

    /**
     * Caso de campo: a origem recebe a devolucao com o assunto da sua unidade de
     * trabalho fora dos sugeridos.
     */
    #[Depends('test_origem_envia_processo_com_assunto_da_unidade_de_trabalho')]
    public function test_origem_recebe_devolucao_preservando_assunto_da_unidade_de_trabalho()
    {
        // O assunto extra do destino so entra no proximo passo: a devolucao
        // dispara antes uma sincronizacao automatica recebida pelo PROPRIO
        // destino, e o defeito do destino abortaria a devolucao
        // antes de ela chegar a origem - mascarando o caso de campo.
        $this->incluirDocumentoExterno(self::$destinatario, 'org2-database', 'arquivo_pequeno_C.pdf');

        $this->entrarComo(self::$destinatario);
        $this->abrirProcessoEAguardarArvore(self::$processoTeste['PROTOCOLO']);
        $this->tramitarProcessoExternamenteMultiplosOrgaoDestinatario(true);

        putenv('DATABASE_HOST=org1-database');
        $this->processarPendenciasAte(function () {
            return $this->contarDocumentosNoBanco(CONTEXTO_ORGAO_A) === 2;
        });

        $this->assertEquals(
            2,
            $this->contarDocumentosNoBanco(CONTEXTO_ORGAO_A),
            'A devolucao nao foi recebida na origem.' . $this->descreverErros()
        );

        $this->assertTrue(
            $this->possuiAssuntoExtra(CONTEXTO_ORGAO_A),
            'O assunto incluido pela unidade de trabalho da origem foi removido no recebimento.'
        );
    }

    /**
     * Mesmo defeito no destino: sincronizacao recebida com o assunto da unidade
     * de trabalho fora dos sugeridos.
     */
    #[Depends('test_origem_recebe_devolucao_preservando_assunto_da_unidade_de_trabalho')]
    public function test_destino_recebe_sincronizacao_preservando_assunto_da_unidade_de_trabalho()
    {
        $this->incluirAssuntoDaUnidadeDeTrabalho(self::$destinatario, CONTEXTO_ORGAO_B);
        $this->incluirDocumentoExterno(self::$remetente, 'org1-database', 'arquivo_pequeno_B.pdf');

        $this->entrarComo(self::$destinatario);
        $this->abrirProcessoEAguardarArvore(self::$processoTeste['PROTOCOLO']);
        $this->paginaProcesso->solicitarSincronizacao('Sincronizar Processo');
        $this->assertStringContainsString(
            'de sincroniza',
            $this->paginaBase->alertTextAndClose(true),
            'A solicitacao de sincronizacao nao foi confirmada.'
        );

        putenv('DATABASE_HOST=org1-database');
        $this->processarPendenciasAte(function () {
            return $this->contarDocumentosNoBanco(CONTEXTO_ORGAO_B) === 3;
        });

        $this->assertEquals(
            3,
            $this->contarDocumentosNoBanco(CONTEXTO_ORGAO_B),
            'A sincronizacao nao foi recebida no destino.' . $this->descreverErros()
        );

        $this->assertTrue(
            $this->possuiAssuntoExtra(CONTEXTO_ORGAO_B),
            'O assunto incluido pela unidade de trabalho do destino foi removido no recebimento.'
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
