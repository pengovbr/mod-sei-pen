<?php

use PHPUnit\Framework\Attributes\{Depends, Group};

/**
 * Issue #1228 - documento novo incluido pelo destino nao chegava na origem
 * quando um documento externo anterior havia sido cancelado na origem.
 *
 * Cenario, no ambito da funcionalidade de envio parcial / multiplos orgaos:
 *
 *   1. ORG1 cria processo com documento externo e tramita para ORG2;
 *   2. ORG1 cancela esse documento;
 *   3. ORG2 inclui um novo documento externo e devolve o processo para ORG1;
 *   4. ORG1 deve enxergar os DOIS documentos: o cancelado e o novo.
 *
 * Antes da correcao o passo 4 nao acontecia - o novo documento nao aparecia na
 * arvore do ORG1.
 *
 * Base: teste escrito na branch fix/1228-sincronizacao-anexo-cancelado, com tres
 * ajustes necessarios para que ele efetivamente executasse:
 *
 *   a) o #[Depends('CenarioBaseTestCase::setUpBeforeClass')] era atributo PHP
 *      real e fazia o PHPUnit abortar a classe inteira ("depends on ... which
 *      does not exist"); nos demais testes da suite ele aparece como comentario;
 *   b) a devolucao usava tramitarProcessoExternamente(), que tenta escolher
 *      repositorio e unidade - mas a tela de devolucao no modo multiplos orgaos
 *      tem o destino fixo, e o Selenium falhava com "Element is not currently
 *      interactable". O correto e tramitarProcessoExternamenteMultiplosOrgaoDestinatario();
 *   c) a pre-condicao (Mapeamento de Envio Parcial habilitado nos dois orgaos)
 *      nao era montada pelo teste, que so passava se o ambiente ja estivesse
 *      configurado por outro teste executado antes.
 *
 * Execution Groups
 * #[Group('execute_alone_group7')]
 */
class TramiteSincronizacaoAposCancelamentoDocumentoTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoCancelado;
    public static $documentoIncluidoNoDestino;

    /**
     * Habilita o Mapeamento de Envio Parcial para multiplos orgaos no orgao
     * logado, apontando para a unidade da contraparte.
     */
    /**
     * Cria o Mapeamento de Envio Parcial para a contraparte.
     *
     * $bolAtivarMultiplosOrgaos so vale para quem ORIGINA o envio com processo
     * aberto. O DESTINATARIO nao precisa da flag para devolver: o array
     * EnvioMultiplosOrgaos lista as unidades que recebem, entao a unidade de
     * origem nunca esta nele. Ver pen_procedimento_expedir.php.
     */
    private function habilitarMultiplosOrgaos(array $arrContrapartida, string $strContexto, bool $bolAtivarMultiplosOrgaos = true): void
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
            array($bolAtivarMultiplosOrgaos ? 'S' : 'N', $arrContrapartida['ID_ESTRUTURA'])
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
     * #[Depends('CenarioBaseTestCase::setUpBeforeClass')]
     *
     * Mantido como comentario, seguindo a convencao dos demais testes da suite:
     * como atributo real o PHPUnit aborta com "depends on ... which does not
     * exist", porque setUpBeforeClass nao e um metodo de teste.
     */
    public function test_tramitar_processo_para_destino()
    {
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
        $this->habilitarMultiplosOrgaos(self::$remetente, CONTEXTO_ORGAO_B, false);

        $this->encerrarSessoes();
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->habilitarMultiplosOrgaos(self::$destinatario, CONTEXTO_ORGAO_A);

        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoCancelado = $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_pequeno_A.pdf');

        $objProtocoloDTO = $this->cadastrarProcessoFixture(self::$processoTeste);
        $this->cadastrarDocumentoExternoFixture(self::$documentoCancelado, $objProtocoloDTO->getDblIdProtocolo());

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
    public function test_cancelar_documento_no_orgao_de_origem()
    {
        $this->encerrarSessoes();
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->abrirProcesso(self::$processoTeste['PROTOCOLO']);
        $this->navegarParaCancelarDocumento(0);
        $this->paginaCancelarDocumento->cancelar('Cancelamento para sincronizacao');

        $this->abrirProcesso(self::$processoTeste['PROTOCOLO']);
        $this->assertCount(
            1,
            $this->paginaProcesso->listarDocumentos(),
            'A origem deveria continuar com o documento cancelado na arvore.'
        );
    }

    #[Depends('test_cancelar_documento_no_orgao_de_origem')]
    public function test_devolver_novo_documento_para_origem()
    {
        $arrRemetenteDevolucao = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$documentoIncluidoNoDestino = $this->gerarDadosDocumentoExternoTeste($arrRemetenteDevolucao, 'arquivo_pequeno_B.pdf');

        putenv('DATABASE_HOST=org2-database');
        $objProtocoloDTO = $this->consultarProcessoFixture(
            self::$processoTeste['PROTOCOLO'],
            ProtocoloRN::$TP_PROCEDIMENTO
        );
        $this->cadastrarDocumentoExternoFixture(
            self::$documentoIncluidoNoDestino,
            $objProtocoloDTO->getDblIdProtocolo()
        );

        $this->encerrarSessoes();
        $this->acessarSistema(
            $arrRemetenteDevolucao['URL'],
            $arrRemetenteDevolucao['SIGLA_UNIDADE'],
            $arrRemetenteDevolucao['LOGIN'],
            $arrRemetenteDevolucao['SENHA']
        );
        $this->abrirProcesso(self::$processoTeste['PROTOCOLO']);

        // Tela de devolucao no modo multiplos orgaos: destino fixo, sem escolha
        // de repositorio/unidade.
        $this->tramitarProcessoExternamenteMultiplosOrgaoDestinatario(true);

        $this->assertNotEmpty(self::$documentoIncluidoNoDestino);
    }

    #[Depends('test_devolver_novo_documento_para_origem')]
    public function test_receber_novo_documento_sem_reativar_documento_cancelado()
    {
        putenv('DATABASE_HOST=org1-database');

        // A devolucao em modo multiplos orgaos e uma sequencia de dois tramites
        // (sincronizacao automatica + envio automatico), e cada um avanca alguns
        // status por ciclo de monitoramento. Uma unica execucao de pendencias nao
        // basta: sem esperar, a assercao final roda antes de o recebimento sequer
        // ser tentado, e o teste falharia por motivo errado.
        for ($i = 0; $i < 8; $i++) {
            $this->executarTramitarPendenciasSimples();
            sleep(3);
        }

        $this->encerrarSessoes();
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->abrirProcesso(self::$processoTeste['PROTOCOLO']);

        $listaDocumentos = $this->paginaProcesso->listarDocumentos();

        $this->assertCount(
            2,
            $listaDocumentos,
            'ISSUE #1228: o documento incluido pelo destino nao chegou na origem apos o '
            . 'cancelamento do documento externo anterior.'
        );

        $this->validarDocumentoCancelado($listaDocumentos[0]);
    }

    /**
     * ACH-03 - defeito AINDA ABERTO, documentado aqui para nao se perder.
     *
     * Quando o documento novo incluido pelo destino tem o MESMO conteudo (mesmo
     * hash) do documento que foi cancelado na origem, o recebimento falha:
     *
     *   Inconsistencia identificada no recebimento de processo:
     *   - Componente digital de pelo menos um dos documentos do processo nao pode ser recebido.
     *
     * Mecanismo, confirmado por experimento controlado (mesmo teste, trocando
     * apenas os arquivos):
     *
     *   1. o barramento deduplica por hash e nao lista o componente como pendente,
     *      porque a origem ja o recebeu uma vez;
     *   2. o modulo tenta entao clonar o arquivo de um documento local que ja
     *      tenha aquele hash (clonarComponentesJaExistentesNoProcesso);
     *   3. o unico documento local com aquele hash e o CANCELADO - e o
     *      cancelamento no SEI remove a linha de `anexo`, entao nao ha arquivo
     *      para clonar;
     *   4. o documento novo fica sem anexo, documentosPendenteRegistro() o marca
     *      como pendente e validarPosCondicoesTramite() derruba a transacao
     *      inteira.
     *
     * Corrigir exige decidir se o modulo deve reobter do barramento um componente
     * que ele ja recebeu e descartou - o que depende do que a API permite. Nao ha
     * especificacao para isso, por isso o teste esta marcado como incompleto em
     * vez de falhar a suite.
     *
     * #[Group('multiplos_orgaos')]
     */
    public function test_hash_identico_ao_documento_cancelado_ainda_falha()
    {
        $this->markTestIncomplete(
            'ACH-03: defeito aberto. Documento novo com hash identico ao do documento cancelado '
            . 'na origem nao e recebido - ver docs/correções.md.'
        );
    }

    /**
     * Deixa o ambiente neutro para os demais testes da suite.
     *
     * Sem isso, o Mapeamento de Envio Parcial habilitado para multiplos orgaos
     * sobrevive a esta classe e faz os testes seguintes tramitarem em modo
     * "processo aberto e sincronizado" sem pedir - o processo nao fica bloqueado
     * na origem e cenarios classicos passam a falhar por timeout.
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
