<?php

use PHPUnit\Framework\Attributes\{Depends, Group};

/**
 * CU-13 de docs/casos-uso-processo-aberto.md.
 *
 * Cenario: a ORIGEM esta habilitada para multiplos orgaos, mas o DESTINO nao tem
 * mapeamento algum para a unidade de origem. O que acontece quando o destino
 * tenta devolver?
 *
 * Comportamento correto, confirmado por teste: a devolucao e BLOQUEADA. O modo de
 * multiplos orgaos vem do historico do tramite, nao do mapeamento, entao o processo
 * permanece aberto e degradar para envio comum violaria o invariante
 * "quem comeca aberto permanece aberto". A saida e configuracao, nao fluxo alternativo.
 *
 * Execution Groups
 * #[Group('execute_alone_group10')]
 */
class ProcessoAbertoDestinoSemMapeamentoTest extends FixtureCenarioBaseTestCase
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

    private function habilitarOrigem(): void
    {
        $this->paginaEnvioParcialListar->navegarEnvioParcialListar();
        $this->paginaCadastroMapEnvioCompDigitais->excluirMapeamentosExistentes();

        $this->paginaEnvioParcialListar->navegarEnvioParcialListar();
        $this->paginaCadastroMapEnvioCompDigitais->novo();
        $this->paginaCadastroMapEnvioCompDigitais->setarParametros(
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE']
        );
        $this->paginaCadastroMapEnvioCompDigitais->salvar();
        sleep(1);

        $objBanco = new DatabaseUtils(CONTEXTO_ORGAO_A);
        $objBanco->execute(
            'update md_pen_envio_comp_digitais set sin_multiplos_orgaos = ? where id_unidade_pen = ?',
            array('S', self::$destinatario['ID_ESTRUTURA'])
        );
    }

    /**
     * #[Depends('CenarioBaseTestCase::setUpBeforeClass')]
     */
    public function test_enviar_com_origem_habilitada_e_destino_sem_mapeamento()
    {
        putenv('DATABASE_HOST=org1-database');

        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Destino: nenhum mapeamento. E o ponto do cenario.
        $objBancoDestino = new DatabaseUtils(CONTEXTO_ORGAO_B);
        $objBancoDestino->execute('delete from md_pen_envio_comp_digitais', array());

        $this->entrarComo(self::$remetente);
        $this->habilitarOrigem();

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

        // Origem habilitada: o processo permanece aberto nela.
        $this->entrarComo(self::$remetente);
        $this->abrirProcesso(self::$processoTeste['PROTOCOLO']);
        $this->assertFalse(
            $this->paginaProcesso->processoBloqueado(),
            'Pre-condicao: com a origem habilitada o processo deveria ficar aberto nela.'
        );

        // E o destino recebeu.
        $this->entrarComo(self::$destinatario);
        $this->abrirProcesso(self::$processoTeste['PROTOCOLO']);
        $arrDocumentos = $this->paginaProcesso->listarDocumentos();
        $this->assertCount(
            1,
            is_array($arrDocumentos) ? $arrDocumentos : array(),
            'Pre-condicao: o destino deveria ter recebido o documento.'
        );
    }

    /**
     * CU-13: destino sem mapeamento tenta devolver.
     */
    #[Depends('test_enviar_com_origem_habilitada_e_destino_sem_mapeamento')]
    public function test_cu13_destino_sem_mapeamento_tem_devolucao_bloqueada()
    {
        $objBancoDestino = new DatabaseUtils(CONTEXTO_ORGAO_B);
        $arrMapeamentos = $objBancoDestino->query('select id_unidade_pen from md_pen_envio_comp_digitais', array());
        $this->assertEmpty(
            $arrMapeamentos,
            'Pre-condicao do cenario: o destino nao pode ter mapeamento algum.'
        );

        $this->entrarComo(self::$destinatario);
        $this->abrirProcesso(self::$processoTeste['PROTOCOLO']);

        // A tela continua em modo multiplos orgaos mesmo sem mapeamento: o modo vem
        // do historico do tramite, nao da configuracao. Por isso o botao Enviar
        // nao e renderizado - a devolucao e bloqueada em vez de degradar.
        $this->paginaProcesso->navegarParaTramitarProcesso();

        $bolBotaoEnviarPresente = true;
        try {
            $this->paginaTramitar->elByXPath("//button[@value='Enviar']");
        } catch (\Exception $e) {
            $bolBotaoEnviarPresente = false;
        }

        $this->assertFalse(
            $bolBotaoEnviarPresente,
            'CU-13: sem mapeamento no destino, a devolucao deve ser BLOQUEADA. Se o botao Enviar '
            . 'aparecer, o sistema esta permitindo degradar um processo aberto para envio comum, '
            . 'violando o invariante "quem comeca aberto permanece aberto".'
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
