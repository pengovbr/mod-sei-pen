<?php

use PHPUnit\Framework\Attributes\{Depends, Group};

/**
 * Issue #1278 - tipo de processo, prioridade e assuntos nao sao sincronizados entre orgaos.
 *
 * Na devolucao ou sincronizacao de processo compartilhado, quando a origem havia cancelado
 * um documento, o recebimento era recusado com "Processo ... nao possui andamento aberto na
 * unidade ... Tarefa: 126". O cancelamento conclui o processo na unidade receptora, e a
 * troca de tipo (tarefa 126) ou de prioridade (tarefa 137) feita pela sincronizacao exige
 * processo aberto.
 *
 * A sincronizacao de processo existente passou a atualizar apenas especificacao e
 * interessados (alem de nivel de acesso e hipotese legal). Tipo, prioridade e assuntos
 * ficam como cada orgao os definiu. Ver docs/USAGE.md, secao "Metadados do Processo
 * Sincronizados entre Orgaos".
 *
 * Cenarios:
 *   1. devolucao classica (sem multiplos orgaos): ORG2 troca tipo e prioridade e devolve;
 *      ORG1 mantem os seus;
 *   2. processo aberto: ORG1 cancela um documento e troca tipo e prioridade; ORG2, com tipo e
 *      prioridade proprios, sincroniza. Sem recusa; o cancelamento chega e o ORG2 mantem os seus
 *      valores. (A especificacao nao e verificada aqui: no pedido de sincronizacao o ORG1 reenvia
 *      os metadados do tramite original, comportamento do envio fora do escopo da #1278.);
 *   3. processo aberto: ORG1 cancela outro documento; ORG2 troca o tipo, inclui documento e
 *      devolve (cenario da #1278). Sem recusa; cada orgao fica com os seus valores;
 *   4. novo processo aberto: ORG2 troca so a prioridade (tarefa 137, que tambem causava a
 *      recusa), altera a especificacao, inclui interessado e assunto; ORG1 cancela um documento;
 *      ORG2 sincroniza. Sem recusa: a especificacao e gravada com o processo concluido na
 *      unidade receptora, e o ORG2 mantem prioridade, interessado e assunto proprios;
 *   5. ORG1 cancela outro documento; ORG2 altera especificacao, inclui interessado e documento e
 *      devolve. Sem recusa: ORG1 recebe os interessados e mantem tipo, prioridade e assuntos;
 *      ORG2 mantem prioridade, interessado e assunto proprios. A especificacao fica a do ORG1 nos
 *      dois orgaos: antes de devolver, o ORG2 sincroniza com o ORG1, que e quem mantem o processo
 *      aberto, e a alteracao local da especificacao e sobrescrita (comportamento anterior a #1278).
 *
 * Tipo e prioridade sao trocados pela tela "Alterar Processo". Dados sem acentuacao.
 *
 * Execution Groups
 * #[Group('execute_alone_group14')]
 */
class TramiteSincronizacaoMetadadosLocaisTest extends FixtureCenarioBaseTestCase
{
    /** Prioridades de teste (mesmo id e nome nos dois orgaos). */
    const PRIORIDADES = array(990001 => 'Prioridade Tramita 1', 990002 => 'Prioridade Tramita 2', 990003 => 'Prioridade Tramita 3');

    public static $remetente;
    public static $destinatario;
    public static $processoClassico;
    public static $processoAberto;
    public static $processoAberto2;
    /** Assunto incluido pelo ORG2 no processo do cenario 4 (id_assunto_proxy). */
    public static $strAssuntoLocalOrg2;
    /** Tipos de processo comuns aos dois orgaos: lista de array('A' => id no ORG1, 'B' => id no ORG2). */
    public static $arrTipos = array();
    /** Estado do processo aberto em cada orgao, capturado apos a ultima alteracao local. */
    public static $arrEstadoOrg1 = array();
    public static $arrEstadoOrg2 = array();

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

    /**
     * Cria o Mapeamento de Envio Parcial para a contraparte. $bolAtivar so vale para quem
     * ORIGINA o envio com processo aberto (ver ProcessoAbertoSincronizacaoFluxoTest).
     */
    private function configurarMapeamento(array $arrContrapartida, string $strContexto, bool $bolAtivar): void
    {
        $this->paginaEnvioParcialListar->navegarEnvioParcialListar();
        $this->paginaCadastroMapEnvioCompDigitais->excluirMapeamentosExistentes();

        $this->paginaEnvioParcialListar->navegarEnvioParcialListar();
        $this->paginaCadastroMapEnvioCompDigitais->novo();
        $this->paginaCadastroMapEnvioCompDigitais->setarParametros($arrContrapartida['REP_ESTRUTURAS'], $arrContrapartida['NOME_UNIDADE']);
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
        $this->assertEquals($bolAtivar ? 'S' : 'N', trim((string) $arrFlag[0]['SIN_MULTIPLOS_ORGAOS']));
    }

    /** Cria as prioridades de teste nos dois orgaos, se ainda nao existirem. */
    private function garantirPrioridades(): void
    {
        foreach (array(CONTEXTO_ORGAO_A, CONTEXTO_ORGAO_B) as $strContexto) {
            $objBanco = new DatabaseUtils($strContexto);
            foreach (self::PRIORIDADES as $numId => $strNome) {
                $arr = $objBanco->query('select id_tipo_prioridade from tipo_prioridade where id_tipo_prioridade = ?', array($numId));
                if (empty($arr)) {
                    $objBanco->execute(
                        'insert into tipo_prioridade (id_tipo_prioridade, nome, descricao, sin_ativo) values (?, ?, ?, ?)',
                        array($numId, $strNome, $strNome, 'S')
                    );
                }
            }
        }
    }

    /**
     * Tipos de processo selecionaveis na tela "Alterar Processo" dos dois orgaos, com o mesmo
     * nome: ativos, nao internos, nao de ouvidoria, nao individuais, sem restricao de unidade
     * e com nivel de acesso sugerido publico (para a troca nao alterar o nivel de acesso).
     */
    private function carregarTiposComuns(int $numIdTipoExcluido): void
    {
        $strSql = "select id_tipo_procedimento, nome from tipo_procedimento"
            . " where sin_ativo = 'S' and sin_interno = 'N' and sin_ouvidoria = 'N' and sin_individual = 'N'"
            . " and sta_nivel_acesso_sugestao = '0'"
            . " and id_tipo_procedimento not in (select id_tipo_procedimento from tipo_proced_restricao)"
            . " and id_tipo_procedimento in (select id_tipo_procedimento from nivel_acesso_permitido where sta_nivel_acesso = '0')"
            . " and id_tipo_procedimento <> ?"
            . " order by id_tipo_procedimento";

        $arrOrg2 = array();
        foreach ((new DatabaseUtils(CONTEXTO_ORGAO_B))->query($strSql, array($numIdTipoExcluido)) as $r) {
            $arrOrg2[$r['NOME']] = $r['ID_TIPO_PROCEDIMENTO'];
        }
        foreach ((new DatabaseUtils(CONTEXTO_ORGAO_A))->query($strSql, array($numIdTipoExcluido)) as $r) {
            if (isset($arrOrg2[$r['NOME']])) {
                self::$arrTipos[] = array('A' => (string) $r['ID_TIPO_PROCEDIMENTO'], 'B' => (string) $arrOrg2[$r['NOME']]);
            }
            if (count(self::$arrTipos) == 3) {
                break;
            }
        }
        $this->assertCount(3, self::$arrTipos, 'Pre-condicao: sao necessarios 3 tipos de processo comuns aos dois orgaos.');
    }

    /** Tipo, prioridade, especificacao, assuntos e documentos cancelados do processo, direto do banco. */
    private function estadoProcesso(string $strContexto, string $strProtocolo): array
    {
        $objBanco = new DatabaseUtils($strContexto);
        $arr = $objBanco->query(
            'select p.id_procedimento, p.id_tipo_procedimento, p.id_tipo_prioridade, pr.descricao from procedimento p'
            . ' inner join protocolo pr on pr.id_protocolo = p.id_procedimento where pr.protocolo_formatado = ?',
            array($strProtocolo)
        );
        if (empty($arr)) {
            return array();
        }

        $arrAssuntos = array();
        foreach ($objBanco->query('select id_assunto_proxy from rel_protocolo_assunto where id_protocolo = ?', array($arr[0]['ID_PROCEDIMENTO'])) as $r) {
            $arrAssuntos[] = (string) $r['ID_ASSUNTO_PROXY'];
        }
        sort($arrAssuntos);

        $arrDocs = $objBanco->query(
            'select pr.sta_estado from documento d inner join protocolo pr on pr.id_protocolo = d.id_documento where d.id_procedimento = ?',
            array($arr[0]['ID_PROCEDIMENTO'])
        );
        $numCancelados = 0;
        foreach ($arrDocs as $r) {
            if (trim((string) $r['STA_ESTADO']) === ProtocoloRN::$TE_DOCUMENTO_CANCELADO) {
                $numCancelados++;
            }
        }

        return array(
            'tipo' => (string) $arr[0]['ID_TIPO_PROCEDIMENTO'],
            'prioridade' => $arr[0]['ID_TIPO_PRIORIDADE'] === null ? null : (string) $arr[0]['ID_TIPO_PRIORIDADE'],
            'descricao' => (string) $arr[0]['DESCRICAO'],
            'assuntos' => $arrAssuntos,
            'documentos' => count($arrDocs),
            'cancelados' => $numCancelados,
        );
    }

    /** Nomes dos interessados do processo, direto do banco. */
    private function interessadosProcesso(string $strContexto, string $strProtocolo): array
    {
        $arrNomes = array();
        foreach ((new DatabaseUtils($strContexto))->query(
            'select c.nome from participante pa inner join contato c on c.id_contato = pa.id_contato'
            . ' inner join protocolo pr on pr.id_protocolo = pa.id_protocolo'
            . ' where pr.protocolo_formatado = ? and pa.sta_participacao = ?',
            array($strProtocolo, ParticipanteRN::$TP_INTERESSADO)
        ) as $r) {
            $arrNomes[] = (string) $r['NOME'];
        }
        return $arrNomes;
    }

    /**
     * Inclui no processo, pela unidade de trabalho, um assunto fora dos que ele ja tem (mesmo
     * mecanismo de ProcessoAbertoAssuntoOutraUnidadeTest: rel_protocolo_assunto nao tem id
     * proprio). Retorna o id_assunto_proxy incluido.
     */
    private function incluirAssuntoLocal(string $strContexto, string $strSiglaUnidade, string $strProtocolo): string
    {
        $objBanco = new DatabaseUtils($strContexto);
        $arrProc = $objBanco->query('select id_protocolo from protocolo where protocolo_formatado = ?', array($strProtocolo));
        $numIdProtocolo = $arrProc[0]['ID_PROTOCOLO'];
        $arrUnidade = $objBanco->query('select id_unidade from unidade where sigla = ?', array($strSiglaUnidade));
        $this->assertNotEmpty($arrUnidade, 'Pre-condicao: unidade ' . $strSiglaUnidade . ' nao encontrada.');
        $arrAssunto = $objBanco->query(
            'select min(id_assunto_proxy) as id_assunto_proxy from rel_tipo_procedimento_assunto'
            . ' where id_assunto_proxy not in (select id_assunto_proxy from rel_protocolo_assunto where id_protocolo = ?)',
            array($numIdProtocolo)
        );
        $this->assertNotEmpty($arrAssunto[0]['ID_ASSUNTO_PROXY'] ?? null, 'Pre-condicao: nenhum assunto disponivel.');
        $arrSequencia = $objBanco->query(
            'select coalesce(max(sequencia), 0) + 1 as prox from rel_protocolo_assunto where id_protocolo = ?',
            array($numIdProtocolo)
        );
        $objBanco->execute(
            'insert into rel_protocolo_assunto (id_protocolo, id_protocolo_procedimento, id_assunto_proxy, id_unidade, sequencia)'
            . ' values (?, ?, ?, ?, ?)',
            array($numIdProtocolo, $numIdProtocolo, (int) $arrAssunto[0]['ID_ASSUNTO_PROXY'], (int) $arrUnidade[0]['ID_UNIDADE'], (int) $arrSequencia[0]['PROX'])
        );
        return (string) $arrAssunto[0]['ID_ASSUNTO_PROXY'];
    }

    /** Altera especificacao e inclui interessado pela tela; prioridade opcional. */
    private function alterarEspecificacaoEInteressadoPelaTela(string $strProtocolo, string $strDescricao, string $strInteressado, ?string $strIdPrioridade = null): void
    {
        $this->abrirProcesso($strProtocolo);
        $this->paginaProcesso->navegarParaEditarProcesso();
        if ($strIdPrioridade !== null) {
            $this->paginaEditarProcesso->selecionarPrioridade($strIdPrioridade);
        }
        $this->paginaEditarProcesso->descricao($strDescricao);
        $this->paginaEditarProcesso->adicionarInteressado($strInteressado);
        $this->paginaEditarProcesso->salvarProcesso();
        sleep(2);
    }

    /** Executa o processamento de pendencias ate a condicao ser atendida (espera pelo efeito). */
    private function processarPendenciasAte(callable $fnCondicao, int $numCiclosMax = 45): bool
    {
        putenv('DATABASE_HOST=org1-database');
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

    /** Altera o processo pela tela: tipo, prioridade e, opcionalmente, especificacao. */
    private function alterarProcessoPelaTela(string $strProtocolo, ?string $strIdTipo, ?string $strIdPrioridade, ?string $strDescricao = null): void
    {
        $this->abrirProcesso($strProtocolo);
        $this->paginaProcesso->navegarParaEditarProcesso();
        if ($strIdTipo !== null) {
            $this->paginaEditarProcesso->selecionarTipoProcesso($strIdTipo);
        }
        if ($strIdPrioridade !== null) {
            $this->paginaEditarProcesso->selecionarPrioridade($strIdPrioridade);
        }
        if ($strDescricao !== null) {
            $this->paginaEditarProcesso->descricao($strDescricao);
        }
        $this->paginaEditarProcesso->salvarProcesso();
        sleep(2);
    }

    private function solicitarSincronizacaoEConfirmar(): void
    {
        $this->paginaProcesso->solicitarSincronizacao('Sincronizar Processo');
        $strAlerta = $this->paginaBase->alertTextAndClose(true);
        $this->assertStringContainsString('de sincroniza', $strAlerta, 'A solicitacao de sincronizacao nao foi confirmada.');
    }

    /**
     * Cenario 1: devolucao classica. ORG2 troca tipo e prioridade e devolve; ORG1 mantem os seus.
     *
     * #[Depends('CenarioBaseTestCase::setUpBeforeClass')]
     */
    public function test_devolucao_classica_mantem_tipo_e_prioridade_da_origem()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Sem mapeamento de envio parcial: tramite classico.
        foreach (array(CONTEXTO_ORGAO_A, CONTEXTO_ORGAO_B) as $strContexto) {
            (new DatabaseUtils($strContexto))->execute('delete from md_pen_envio_comp_digitais', array());
        }
        $this->garantirPrioridades();

        putenv('DATABASE_HOST=org1-database');
        self::$processoClassico = $this->gerarDadosProcessoTeste(self::$remetente);
        $objProtocoloDTO = $this->cadastrarProcessoFixture(self::$processoClassico);
        $this->cadastrarDocumentoExternoFixture(
            $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_pequeno_A.pdf'),
            $objProtocoloDTO->getDblIdProtocolo()
        );
        (new DatabaseUtils(CONTEXTO_ORGAO_A))->execute(
            'update procedimento set id_tipo_prioridade = ? where id_procedimento = ?',
            array(990001, $objProtocoloDTO->getDblIdProtocolo())
        );
        $strProtocolo = self::$processoClassico['PROTOCOLO'];
        $arrOrg1Antes = $this->estadoProcesso(CONTEXTO_ORGAO_A, $strProtocolo);
        $this->carregarTiposComuns((int) $arrOrg1Antes['tipo']);

        $this->entrarComo(self::$remetente);
        $this->abrirProcesso($strProtocolo);
        $this->tramitarProcessoExternamente(
            $strProtocolo,
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false
        );
        $this->assertTrue(
            $this->processarPendenciasAte(function () use ($strProtocolo) {
                return !empty($this->estadoProcesso(CONTEXTO_ORGAO_B, $strProtocolo));
            }),
            'ORG2 nao recebeu o processo.'
        );

        // ORG2 troca tipo e prioridade, inclui um documento e devolve
        $this->entrarComo(self::$destinatario);
        $this->alterarProcessoPelaTela($strProtocolo, self::$arrTipos[0]['B'], '990003');
        putenv('DATABASE_HOST=org2-database');
        $objProtocoloOrg2 = $this->consultarProcessoFixture($strProtocolo, ProtocoloRN::$TP_PROCEDIMENTO);
        $this->cadastrarDocumentoExternoFixture(
            $this->gerarDadosDocumentoExternoTeste(self::$destinatario, 'arquivo_pequeno_B.pdf'),
            $objProtocoloOrg2->getDblIdProtocolo()
        );
        $arrOrg2Antes = $this->estadoProcesso(CONTEXTO_ORGAO_B, $strProtocolo);
        $this->assertEquals(self::$arrTipos[0]['B'], $arrOrg2Antes['tipo'], 'Pre-condicao: ORG2 nao trocou o tipo pela tela.');
        $this->assertEquals('990003', $arrOrg2Antes['prioridade'], 'Pre-condicao: ORG2 nao trocou a prioridade pela tela.');

        $this->abrirProcesso($strProtocolo);
        $this->tramitarProcessoExternamente(
            $strProtocolo,
            self::$remetente['REP_ESTRUTURAS'],
            self::$remetente['NOME_UNIDADE'],
            self::$remetente['SIGLA_UNIDADE_HIERARQUIA'],
            false
        );
        $this->assertTrue(
            $this->processarPendenciasAte(function () use ($strProtocolo) {
                return $this->estadoProcesso(CONTEXTO_ORGAO_A, $strProtocolo)['documentos'] == 2;
            }),
            'ORG1 nao recebeu a devolucao (2 documentos esperados).'
        );

        $arrOrg1Depois = $this->estadoProcesso(CONTEXTO_ORGAO_A, $strProtocolo);
        $this->assertEquals($arrOrg1Antes['tipo'], $arrOrg1Depois['tipo'], 'ORG1 nao deveria receber o tipo de processo do ORG2.');
        $this->assertEquals('990001', $arrOrg1Depois['prioridade'], 'ORG1 nao deveria receber a prioridade do ORG2.');
        $this->assertEquals($arrOrg1Antes['assuntos'], $arrOrg1Depois['assuntos'], 'Os assuntos do ORG1 nao deveriam mudar.');
    }

    /**
     * Cenario 2: processo aberto. ORG1 cancela documento e troca tipo e prioridade;
     * ORG2 sincroniza e mantem tipo, prioridade e assuntos proprios.
     */
    #[Depends('test_devolucao_classica_mantem_tipo_e_prioridade_da_origem')]
    public function test_sincronizacao_com_documento_cancelado_na_origem_mantem_metadados_locais()
    {
        $this->entrarComo(self::$destinatario);
        $this->configurarMapeamento(self::$remetente, CONTEXTO_ORGAO_B, false);
        $this->entrarComo(self::$remetente);
        $this->configurarMapeamento(self::$destinatario, CONTEXTO_ORGAO_A, true);

        putenv('DATABASE_HOST=org1-database');
        self::$processoAberto = $this->gerarDadosProcessoTeste(self::$remetente);
        $objProtocoloDTO = $this->cadastrarProcessoFixture(self::$processoAberto);
        $this->cadastrarDocumentoExternoFixture(
            $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_pequeno_A.pdf'),
            $objProtocoloDTO->getDblIdProtocolo()
        );
        for ($i = 0; $i < 2; $i++) {
            $this->cadastrarDocumentoInternoFixture($this->gerarDadosDocumentoInternoTeste(self::$remetente), $objProtocoloDTO->getDblIdProtocolo());
        }
        (new DatabaseUtils(CONTEXTO_ORGAO_A))->execute(
            'update procedimento set id_tipo_prioridade = ? where id_procedimento = ?',
            array(990001, $objProtocoloDTO->getDblIdProtocolo())
        );
        $strProtocolo = self::$processoAberto['PROTOCOLO'];

        $this->abrirProcesso($strProtocolo);
        $this->tramitarProcessoExternamente(
            $strProtocolo,
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false,
            null,
            PEN_WAIT_TIMEOUT,
            true,
            true
        );
        $this->assertTrue(
            $this->processarPendenciasAte(function () use ($strProtocolo) {
                return ($this->estadoProcesso(CONTEXTO_ORGAO_B, $strProtocolo)['documentos'] ?? 0) == 3;
            }),
            'ORG2 nao recebeu o processo com 3 documentos.'
        );

        // ORG2 define tipo e prioridade proprios
        $this->entrarComo(self::$destinatario);
        $this->alterarProcessoPelaTela($strProtocolo, self::$arrTipos[1]['B'], '990003');
        self::$arrEstadoOrg2 = $this->estadoProcesso(CONTEXTO_ORGAO_B, $strProtocolo);
        $this->assertEquals(self::$arrTipos[1]['B'], self::$arrEstadoOrg2['tipo'], 'Pre-condicao: ORG2 nao trocou o tipo pela tela.');

        // ORG1 cancela um documento e troca tipo e prioridade
        $this->entrarComo(self::$remetente);
        $this->abrirProcesso($strProtocolo);
        $this->navegarParaCancelarDocumento(0);
        $this->paginaCancelarDocumento->cancelar('Cancelamento validacao 1278');
        $this->alterarProcessoPelaTela($strProtocolo, self::$arrTipos[2]['A'], '990002');
        self::$arrEstadoOrg1 = $this->estadoProcesso(CONTEXTO_ORGAO_A, $strProtocolo);
        $this->assertEquals(self::$arrTipos[2]['A'], self::$arrEstadoOrg1['tipo'], 'Pre-condicao: ORG1 nao trocou o tipo pela tela.');
        $this->assertEquals(1, self::$arrEstadoOrg1['cancelados'], 'Pre-condicao: ORG1 deveria ter 1 documento cancelado.');

        // ORG2 sincroniza
        $this->entrarComo(self::$destinatario);
        $this->abrirProcesso($strProtocolo);
        $this->solicitarSincronizacaoEConfirmar();
        $this->assertTrue(
            $this->processarPendenciasAte(function () use ($strProtocolo) {
                return $this->estadoProcesso(CONTEXTO_ORGAO_B, $strProtocolo)['cancelados'] == 1;
            }),
            'A sincronizacao nao chegou ao ORG2 (documento cancelado esperado): verifique recusa no ORG2.'
        );

        $arrOrg2Depois = $this->estadoProcesso(CONTEXTO_ORGAO_B, $strProtocolo);
        $this->assertEquals(self::$arrEstadoOrg2['tipo'], $arrOrg2Depois['tipo'], 'ORG2 nao deveria receber o tipo de processo do ORG1.');
        $this->assertEquals('990003', $arrOrg2Depois['prioridade'], 'ORG2 nao deveria receber a prioridade do ORG1.');
        $this->assertEquals(self::$arrEstadoOrg2['assuntos'], $arrOrg2Depois['assuntos'], 'Os assuntos do ORG2 nao deveriam mudar.');
        self::$arrEstadoOrg2 = $arrOrg2Depois;
    }

    /**
     * Cenario 3 (#1278): ORG1 cancela outro documento; ORG2 troca o tipo, inclui documento e
     * devolve. ORG1 recebe sem recusa e cada orgao fica com os seus valores.
     */
    #[Depends('test_sincronizacao_com_documento_cancelado_na_origem_mantem_metadados_locais')]
    public function test_devolucao_com_documento_cancelado_na_origem_mantem_metadados_locais()
    {
        $strProtocolo = self::$processoAberto['PROTOCOLO'];

        $this->entrarComo(self::$remetente);
        $this->abrirProcesso($strProtocolo);
        $this->navegarParaCancelarDocumento(1);
        $this->paginaCancelarDocumento->cancelar('Cancelamento validacao 1278 (2)');
        self::$arrEstadoOrg1 = $this->estadoProcesso(CONTEXTO_ORGAO_A, $strProtocolo);
        $this->assertEquals(2, self::$arrEstadoOrg1['cancelados'], 'Pre-condicao: ORG1 deveria ter 2 documentos cancelados.');

        $this->entrarComo(self::$destinatario);
        $this->alterarProcessoPelaTela($strProtocolo, self::$arrTipos[0]['B'], null);
        putenv('DATABASE_HOST=org2-database');
        $objProtocoloOrg2 = $this->consultarProcessoFixture($strProtocolo, ProtocoloRN::$TP_PROCEDIMENTO);
        $this->cadastrarDocumentoExternoFixture(
            $this->gerarDadosDocumentoExternoTeste(self::$destinatario, 'arquivo_pequeno_C.pdf'),
            $objProtocoloOrg2->getDblIdProtocolo()
        );
        $arrOrg2Antes = $this->estadoProcesso(CONTEXTO_ORGAO_B, $strProtocolo);
        $this->assertEquals(self::$arrTipos[0]['B'], $arrOrg2Antes['tipo'], 'Pre-condicao: ORG2 nao trocou o tipo pela tela.');

        $this->abrirProcesso($strProtocolo);
        $this->tramitarProcessoExternamenteMultiplosOrgaoDestinatario(true);
        $this->assertTrue(
            $this->processarPendenciasAte(function () use ($strProtocolo) {
                return $this->estadoProcesso(CONTEXTO_ORGAO_A, $strProtocolo)['documentos'] == 4;
            }),
            'ORG1 nao recebeu a devolucao (4 documentos esperados): verifique recusa no ORG2.'
        );

        $arrOrg1Depois = $this->estadoProcesso(CONTEXTO_ORGAO_A, $strProtocolo);
        $this->assertEquals(self::$arrEstadoOrg1['tipo'], $arrOrg1Depois['tipo'], 'ORG1 nao deveria receber o tipo de processo do ORG2.');
        $this->assertEquals('990002', $arrOrg1Depois['prioridade'], 'ORG1 nao deveria receber a prioridade do ORG2.');
        $this->assertEquals(self::$arrEstadoOrg1['assuntos'], $arrOrg1Depois['assuntos'], 'Os assuntos do ORG1 nao deveriam mudar.');
        $this->assertEquals(2, $arrOrg1Depois['cancelados'], 'ORG1 deveria manter os 2 documentos cancelados.');

        $arrOrg2Depois = $this->estadoProcesso(CONTEXTO_ORGAO_B, $strProtocolo);
        $this->assertEquals(self::$arrTipos[0]['B'], $arrOrg2Depois['tipo'], 'ORG2 nao deveria receber o tipo de processo do ORG1.');
        $this->assertEquals('990003', $arrOrg2Depois['prioridade'], 'ORG2 nao deveria receber a prioridade do ORG1.');
        $this->assertEquals($arrOrg2Antes['assuntos'], $arrOrg2Depois['assuntos'], 'Os assuntos do ORG2 nao deveriam mudar.');
    }

    /**
     * Cenario 4: novo processo aberto. ORG2 troca so a prioridade, altera a especificacao e inclui
     * interessado e assunto; ORG1 cancela um documento; ORG2 sincroniza sem recusa.
     */
    #[Depends('test_devolucao_com_documento_cancelado_na_origem_mantem_metadados_locais')]
    public function test_sincronizacao_com_documento_cancelado_e_prioridade_especificacao_interessado_e_assunto_locais()
    {
        $this->entrarComo(self::$remetente);
        putenv('DATABASE_HOST=org1-database');
        self::$processoAberto2 = $this->gerarDadosProcessoTeste(self::$remetente);
        $objProtocoloDTO = $this->cadastrarProcessoFixture(self::$processoAberto2);
        $this->cadastrarDocumentoExternoFixture(
            $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_pequeno_A.pdf'),
            $objProtocoloDTO->getDblIdProtocolo()
        );
        for ($i = 0; $i < 2; $i++) {
            $this->cadastrarDocumentoInternoFixture($this->gerarDadosDocumentoInternoTeste(self::$remetente), $objProtocoloDTO->getDblIdProtocolo());
        }
        (new DatabaseUtils(CONTEXTO_ORGAO_A))->execute(
            'update procedimento set id_tipo_prioridade = ? where id_procedimento = ?',
            array(990001, $objProtocoloDTO->getDblIdProtocolo())
        );
        $strProtocolo = self::$processoAberto2['PROTOCOLO'];

        $this->abrirProcesso($strProtocolo);
        $this->tramitarProcessoExternamente(
            $strProtocolo,
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false,
            null,
            PEN_WAIT_TIMEOUT,
            true,
            true
        );
        $this->assertTrue(
            $this->processarPendenciasAte(function () use ($strProtocolo) {
                return ($this->estadoProcesso(CONTEXTO_ORGAO_B, $strProtocolo)['documentos'] ?? 0) == 3;
            }),
            'ORG2 nao recebeu o processo com 3 documentos.'
        );
        $arrOrg2Recebido = $this->estadoProcesso(CONTEXTO_ORGAO_B, $strProtocolo);

        // ORG2: so a prioridade (sem tipo), especificacao, interessado e assunto proprios
        $this->entrarComo(self::$destinatario);
        $this->alterarEspecificacaoEInteressadoPelaTela($strProtocolo, 'Especificacao local ORG2 1278', 'Interessado Local ORG2 1278', '990003');
        self::$strAssuntoLocalOrg2 = $this->incluirAssuntoLocal(CONTEXTO_ORGAO_B, self::$destinatario['SIGLA_UNIDADE'], $strProtocolo);
        self::$arrEstadoOrg2 = $this->estadoProcesso(CONTEXTO_ORGAO_B, $strProtocolo);
        $this->assertEquals($arrOrg2Recebido['tipo'], self::$arrEstadoOrg2['tipo'], 'Pre-condicao: ORG2 nao deveria trocar o tipo neste cenario.');
        $this->assertEquals('990003', self::$arrEstadoOrg2['prioridade'], 'Pre-condicao: ORG2 nao trocou a prioridade pela tela.');
        $this->assertEquals('Especificacao local ORG2 1278', self::$arrEstadoOrg2['descricao'], 'Pre-condicao: ORG2 nao alterou a especificacao.');
        $this->assertContains('Interessado Local ORG2 1278', $this->interessadosProcesso(CONTEXTO_ORGAO_B, $strProtocolo), 'Pre-condicao: ORG2 nao incluiu o interessado.');
        $this->assertContains(self::$strAssuntoLocalOrg2, self::$arrEstadoOrg2['assuntos'], 'Pre-condicao: ORG2 nao incluiu o assunto.');

        // ORG1 cancela um documento
        $this->entrarComo(self::$remetente);
        $this->abrirProcesso($strProtocolo);
        $this->navegarParaCancelarDocumento(0);
        $this->paginaCancelarDocumento->cancelar('Cancelamento validacao 1278 (3)');
        self::$arrEstadoOrg1 = $this->estadoProcesso(CONTEXTO_ORGAO_A, $strProtocolo);
        $this->assertEquals(1, self::$arrEstadoOrg1['cancelados'], 'Pre-condicao: ORG1 deveria ter 1 documento cancelado.');

        // ORG2 sincroniza
        $this->entrarComo(self::$destinatario);
        $this->abrirProcesso($strProtocolo);
        $this->solicitarSincronizacaoEConfirmar();
        $this->assertTrue(
            $this->processarPendenciasAte(function () use ($strProtocolo) {
                return $this->estadoProcesso(CONTEXTO_ORGAO_B, $strProtocolo)['cancelados'] == 1;
            }),
            'A sincronizacao nao chegou ao ORG2 (documento cancelado esperado): verifique recusa no ORG2.'
        );

        $arrOrg2Depois = $this->estadoProcesso(CONTEXTO_ORGAO_B, $strProtocolo);
        $this->assertEquals(self::$arrEstadoOrg2['tipo'], $arrOrg2Depois['tipo'], 'ORG2 nao deveria ter o tipo alterado.');
        $this->assertEquals('990003', $arrOrg2Depois['prioridade'], 'ORG2 nao deveria receber a prioridade do ORG1.');
        $this->assertEquals(self::$arrEstadoOrg2['assuntos'], $arrOrg2Depois['assuntos'], 'Os assuntos do ORG2 nao deveriam mudar.');
        $this->assertEquals(self::$arrEstadoOrg1['descricao'], $arrOrg2Depois['descricao'], 'ORG2 deveria receber a especificacao do ORG1.');
        $this->assertContains('Interessado Local ORG2 1278', $this->interessadosProcesso(CONTEXTO_ORGAO_B, $strProtocolo), 'O interessado incluido pelo ORG2 deveria ser preservado.');
        self::$arrEstadoOrg2 = $arrOrg2Depois;
    }

    /**
     * Cenario 5: ORG1 cancela outro documento; ORG2 altera especificacao, inclui interessado e
     * documento e devolve. ORG1 recebe sem recusa; a especificacao do ORG1 prevalece (ver cabecalho).
     */
    #[Depends('test_sincronizacao_com_documento_cancelado_e_prioridade_especificacao_interessado_e_assunto_locais')]
    public function test_devolucao_com_documento_cancelado_sincroniza_interessados()
    {
        $strProtocolo = self::$processoAberto2['PROTOCOLO'];

        $this->entrarComo(self::$remetente);
        $this->abrirProcesso($strProtocolo);
        $this->navegarParaCancelarDocumento(1);
        $this->paginaCancelarDocumento->cancelar('Cancelamento validacao 1278 (4)');
        self::$arrEstadoOrg1 = $this->estadoProcesso(CONTEXTO_ORGAO_A, $strProtocolo);
        $this->assertEquals(2, self::$arrEstadoOrg1['cancelados'], 'Pre-condicao: ORG1 deveria ter 2 documentos cancelados.');

        $this->entrarComo(self::$destinatario);
        $this->alterarEspecificacaoEInteressadoPelaTela($strProtocolo, 'Especificacao devolvida ORG2 1278', 'Interessado Devolucao ORG2 1278');
        putenv('DATABASE_HOST=org2-database');
        $objProtocoloOrg2 = $this->consultarProcessoFixture($strProtocolo, ProtocoloRN::$TP_PROCEDIMENTO);
        $this->cadastrarDocumentoExternoFixture(
            $this->gerarDadosDocumentoExternoTeste(self::$destinatario, 'arquivo_pequeno_C.pdf'),
            $objProtocoloOrg2->getDblIdProtocolo()
        );

        $this->abrirProcesso($strProtocolo);
        $this->tramitarProcessoExternamenteMultiplosOrgaoDestinatario(true);
        $this->assertTrue(
            $this->processarPendenciasAte(function () use ($strProtocolo) {
                return $this->estadoProcesso(CONTEXTO_ORGAO_A, $strProtocolo)['documentos'] == 4;
            }),
            'ORG1 nao recebeu a devolucao (4 documentos esperados): verifique recusa no ORG2.'
        );

        $arrOrg1Depois = $this->estadoProcesso(CONTEXTO_ORGAO_A, $strProtocolo);
        $this->assertEquals(self::$arrEstadoOrg1['tipo'], $arrOrg1Depois['tipo'], 'ORG1 nao deveria receber o tipo de processo do ORG2.');
        $this->assertEquals('990001', $arrOrg1Depois['prioridade'], 'ORG1 nao deveria receber a prioridade do ORG2.');
        $this->assertEquals(self::$arrEstadoOrg1['assuntos'], $arrOrg1Depois['assuntos'], 'Os assuntos do ORG1 nao deveriam mudar.');
        $this->assertEquals(2, $arrOrg1Depois['cancelados'], 'ORG1 deveria manter os 2 documentos cancelados.');
        $this->assertEquals(self::$arrEstadoOrg1['descricao'], $arrOrg1Depois['descricao'], 'ORG1 deveria manter a sua especificacao.');
        $this->assertContains('Interessado Devolucao ORG2 1278', $this->interessadosProcesso(CONTEXTO_ORGAO_A, $strProtocolo), 'ORG1 deveria receber o interessado do ORG2.');

        $arrOrg2Depois = $this->estadoProcesso(CONTEXTO_ORGAO_B, $strProtocolo);
        $this->assertEquals('990003', $arrOrg2Depois['prioridade'], 'ORG2 nao deveria receber a prioridade do ORG1.');
        $this->assertEquals(self::$arrEstadoOrg1['descricao'], $arrOrg2Depois['descricao'], 'A sincronizacao antes da devolucao deveria levar ao ORG2 a especificacao do ORG1.');
        $this->assertContains(self::$strAssuntoLocalOrg2, $arrOrg2Depois['assuntos'], 'O assunto incluido pelo ORG2 deveria ser preservado.');
        $this->assertContains('Interessado Local ORG2 1278', $this->interessadosProcesso(CONTEXTO_ORGAO_B, $strProtocolo), 'O interessado incluido pelo ORG2 deveria ser preservado.');
    }

    /**
     * Deixa o ambiente neutro: o mapeamento de envio parcial sobrevive a classe e faria os
     * testes seguintes tramitarem em modo multiplos orgaos.
     */
    public static function tearDownAfterClass(): void
    {
        foreach (array(CONTEXTO_ORGAO_A, CONTEXTO_ORGAO_B) as $strContexto) {
            try {
                (new DatabaseUtils($strContexto))->execute('delete from md_pen_envio_comp_digitais', array());
            } catch (\Exception $e) {
                // ambiente pode nao estar disponivel no encerramento
            }
        }
        parent::tearDownAfterClass();
    }
}
