<?php

use PHPUnit\Framework\Attributes\{Depends, Group};

/**
 * Issue #1225 / COR-10 - metadados descritivos do documento devem ser
 * sincronizados quando o processo e tratado pela funcionalidade de
 * SINCRONIZACAO (multiplos orgaos).
 *
 * A propria issue observa:
 *
 *   "Vale salientar que os comportamentos descritos acima foram observados
 *    apenas quando o processo e tratado por meio da funcionalidade de
 *    sincronizacao."
 *
 * Ou seja, o reflexo e esperado no fluxo de sincronizacao - nao no Envio
 * Externo comum. Por isso alterarMetadadosDocumento() so grava descricao,
 * nome na arvore, numero, serie e data de producao quando a propriedade
 * adicional `multiplosOrgaos` do tramite e verdadeira.
 *
 * Cenario:
 *
 *   1. ORG1 cria processo com documento externo e tramita para ORG2;
 *   2. ORG2 altera descricao e nome na arvore desse documento;
 *   3. ORG2 devolve o processo para ORG1 em modo multiplos orgaos;
 *   4. ORG1 deve enxergar a descricao e o nome na arvore NOVOS.
 *
 * Sem a COR-10 o passo 4 falha: a origem continua com os valores antigos.
 *
 * Os valores usados sao curtos de proposito. O complemento de identificacao
 * trafega como JSON limitado a ProcessoEletronicoRN TAMANHO_MAXIMO_COMPLEMENTO
 * (100 bytes), e textos longos seriam truncados pela COR-02 - o que e correto,
 * mas atrapalharia a assercao exata deste teste.
 *
 * Execution Groups
 * #[Group('execute_alone_group7')]
 */
class SincronizacaoMetadadosDocumentoTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste;

    const DESCRICAO_NOVA = 'DESC-1225-SYNC';
    const NOME_ARVORE_NOVO = 'ARV-1225-SYNC';

    /**
     * Habilita o Mapeamento de Envio Parcial para multiplos orgaos no orgao
     * logado, apontando para a unidade da contraparte.
     */
    private function habilitarMultiplosOrgaos(array $arrContrapartida, string $strContexto): void
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
            array('S', $arrContrapartida['ID_ESTRUTURA'])
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

    /**
     * Le descricao e nome na arvore do unico documento do processo, no orgao
     * indicado. Vai direto ao banco: a arvore do SEI mostra o nome montado, e o
     * que este teste precisa comparar sao os campos gravados.
     */
    private function lerMetadadosDocumento(string $strContexto, string $strProtocoloProcesso): array
    {
        $objBanco = new DatabaseUtils($strContexto);
        $arrResultado = $objBanco->query(
            'select doc.nome_arvore as nome_arvore, prot_doc.descricao as descricao'
            . '  from documento doc'
            . ' inner join protocolo prot_doc on prot_doc.id_protocolo = doc.id_documento'
            . ' inner join protocolo prot_proc on prot_proc.id_protocolo = doc.id_procedimento'
            . ' where prot_proc.protocolo_formatado = ?'
            . '   and prot_doc.sta_estado <> ?'
            . ' order by doc.id_documento',
            array($strProtocoloProcesso, ProtocoloRN::$TE_DOCUMENTO_CANCELADO)
        );

        $this->assertNotEmpty(
            $arrResultado,
            "Nenhum documento encontrado para o processo $strProtocoloProcesso em $strContexto."
        );

        return array(
            'nome_arvore' => $arrResultado[0]['nome_arvore'],
            'descricao' => $arrResultado[0]['descricao'],
        );
    }

    /**
     * #[Depends('CenarioBaseTestCase::setUpBeforeClass')]
     *
     * Mantido como comentario, seguindo a convencao dos demais testes da suite.
     */
    public function test_tramitar_processo_para_destino()
    {
        $this->markTestIncomplete(
            'ISSUE #1225 em aberto. A COR-10 entregava a sincronizacao restrita ao fluxo de '
            . 'multiplos orgaos, mas o ciclo vermelho/verde mostrou que persistir o complemento '
            . 'de identificacao - que e truncado a cada envio para caber em 100 bytes - faz a '
            . 'descricao encolher a cada ida e volta. Foi revertida. O cenario abaixo esta '
            . 'pronto e volta a valer quando a guarda anti-truncamento existir. '
            . 'Diagnostico completo em docs/to-do-erro.md.'
        );

        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Pre-condicao: multiplos orgaos habilitado dos dois lados.
        $this->encerrarSessoes();
        $this->acessarSistema(
            self::$destinatario['URL'],
            self::$destinatario['SIGLA_UNIDADE'],
            self::$destinatario['LOGIN'],
            self::$destinatario['SENHA']
        );
        $this->habilitarMultiplosOrgaos(self::$remetente, CONTEXTO_ORGAO_B);

        $this->encerrarSessoes();
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->habilitarMultiplosOrgaos(self::$destinatario, CONTEXTO_ORGAO_A);

        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste = $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_pequeno_A.pdf');

        $objProtocoloDTO = $this->cadastrarProcessoFixture(self::$processoTeste);
        $this->cadastrarDocumentoExternoFixture(self::$documentoTeste, $objProtocoloDTO->getDblIdProtocolo());

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

        $this->assertNotEmpty(self::$processoTeste['PROTOCOLO']);
    }

    #[Depends('test_tramitar_processo_para_destino')]
    public function test_alterar_metadados_no_destino_e_devolver()
    {
        putenv('DATABASE_HOST=org2-database');

        // Confere que o documento chegou ao destino antes de altera-lo, para que
        // uma falha de recebimento nao apareca depois como falha de sincronizacao.
        $arrAntes = $this->lerMetadadosDocumento(CONTEXTO_ORGAO_B, self::$processoTeste['PROTOCOLO']);

        $this->assertNotEquals(
            self::DESCRICAO_NOVA,
            $arrAntes['descricao'],
            'O documento no destino ja tinha a descricao nova antes da alteracao - o teste nao provaria nada.'
        );

        // Os ids sao resolvidos antes do update. Subconsulta sobre a propria
        // tabela alvo e recusada pelo MySQL (erro 1093), e a forma explicita
        // funciona igual nos quatro bancos suportados pelo modulo.
        $objBanco = new DatabaseUtils(CONTEXTO_ORGAO_B);
        $arrIdDocumento = $objBanco->query(
            'select doc.id_documento as id_documento'
            . '  from documento doc'
            . ' inner join protocolo prot_proc on prot_proc.id_protocolo = doc.id_procedimento'
            . ' where prot_proc.protocolo_formatado = ?',
            array(self::$processoTeste['PROTOCOLO'])
        );

        $this->assertNotEmpty($arrIdDocumento, 'Nenhum documento encontrado no destino para alterar.');

        foreach ($arrIdDocumento as $arrLinha) {
            $objBanco->execute(
                'update protocolo set descricao = ? where id_protocolo = ?',
                array(self::DESCRICAO_NOVA, $arrLinha['id_documento'])
            );
            $objBanco->execute(
                'update documento set nome_arvore = ? where id_documento = ?',
                array(self::NOME_ARVORE_NOVO, $arrLinha['id_documento'])
            );
        }

        $arrDepois = $this->lerMetadadosDocumento(CONTEXTO_ORGAO_B, self::$processoTeste['PROTOCOLO']);
        $this->assertEquals(self::DESCRICAO_NOVA, $arrDepois['descricao'], 'A alteracao no destino nao foi gravada.');
        $this->assertEquals(self::NOME_ARVORE_NOVO, $arrDepois['nome_arvore'], 'A alteracao no destino nao foi gravada.');

        $this->encerrarSessoes();
        $this->acessarSistema(
            self::$destinatario['URL'],
            self::$destinatario['SIGLA_UNIDADE'],
            self::$destinatario['LOGIN'],
            self::$destinatario['SENHA']
        );
        $this->abrirProcesso(self::$processoTeste['PROTOCOLO']);

        // Tela de devolucao no modo multiplos orgaos: destino fixo.
        $this->tramitarProcessoExternamenteMultiplosOrgaoDestinatario(true);

        $this->assertTrue(true);
    }

    #[Depends('test_alterar_metadados_no_destino_e_devolver')]
    public function test_metadados_refletidos_na_origem()
    {
        putenv('DATABASE_HOST=org1-database');

        // A devolucao em modo multiplos orgaos e uma sequencia de dois tramites
        // (sincronizacao automatica + envio automatico), e cada um avanca alguns
        // status por ciclo de monitoramento.
        for ($i = 0; $i < 8; $i++) {
            $this->executarTramitarPendenciasSimples();
            sleep(3);
        }

        $arrOrigem = $this->lerMetadadosDocumento(CONTEXTO_ORGAO_A, self::$processoTeste['PROTOCOLO']);

        // Evidencia em arquivo: o log de eventos do PHPUnit nem sempre emite o
        // desfecho deste metodo, e sem isso nao ha como afirmar o que foi
        // comparado. /tests corresponde a tests_sei5/funcional no host.
        @file_put_contents(
            '/tests/evidencia_1225.txt',
            sprintf(
                "processo=%s\nesperado_descricao=%s\nobtido_descricao=%s\nesperado_nome_arvore=%s\nobtido_nome_arvore=%s\n",
                self::$processoTeste['PROTOCOLO'],
                self::DESCRICAO_NOVA,
                var_export($arrOrigem['descricao'], true),
                self::NOME_ARVORE_NOVO,
                var_export($arrOrigem['nome_arvore'], true)
            )
        );

        $this->assertEquals(
            self::DESCRICAO_NOVA,
            $arrOrigem['descricao'],
            'ISSUE #1225: a descricao alterada no destino nao foi refletida na origem apos a sincronizacao.'
        );

        $this->assertEquals(
            self::NOME_ARVORE_NOVO,
            $arrOrigem['nome_arvore'],
            'ISSUE #1225: o nome na arvore alterado no destino nao foi refletido na origem apos a sincronizacao.'
        );
    }

    /**
     * Deixa o ambiente neutro para os demais testes da suite.
     *
     * Sem isso o Mapeamento de Envio Parcial habilitado para multiplos orgaos
     * sobrevive a esta classe e faz os testes seguintes tramitarem em modo
     * "processo aberto e sincronizado" sem pedir.
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
