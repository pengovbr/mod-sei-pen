<?php

use PHPUnit\Framework\Attributes\{Group};

/**
 * Regra de negocio sob teste (issue #1230):
 *
 *   O modo "processo aberto e sincronizado" (multiplos orgaos) e decidido na
 *   ORIGEM, no momento do envio, a partir do Mapeamento de Envio Parcial com
 *   sin_multiplos_orgaos = 'S' para a unidade de destino. Desde a issue #1212 o
 *   usuario nao tem checkbox na tela de envio: a decisao e automatica.
 *
 *   O efeito e ATOMICO. Ou o tramite sai marcado como multiplos orgaos E o
 *   processo permanece aberto/desbloqueado na origem E o destino pode
 *   sincronizar; ou nada disso acontece e o envio e um envio externo comum,
 *   com o processo bloqueado na origem.
 *
 *   Para o destino poder sincronizar/devolver, ele precisa ter Mapeamento de
 *   Envio Parcial ativo para a estrutura de ORIGEM (RN-04). O fluxo completo,
 *   portanto, exige mapeamento habilitado dos DOIS lados.
 *
 * Este par de testes fecha o cerco por dois lados:
 *
 *   test_a_...: sem autorizacao explicita, o envio TEM que ser comum.
 *               Prova que a ativacao silenciosa relatada na #1230 acabou.
 *
 *   test_b_...: com autorizacao explicita nos dois orgaos, o modo multiplos
 *               orgaos TEM que funcionar. Prova que fechar a porta nao matou a
 *               funcionalidade.
 *
 * Sem o segundo teste, "corrigir" seria indistinguivel de desligar o recurso.
 *
 * Execution Groups
 * #[Group('execute_alone_group2')]
 */
class MultiplosOrgaosAtivacaoTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;

    /**
     * Encerra sessoes abertas nos dois orgaos. A sessao do navegador sobrevive
     * entre metodos de teste; sem isso a tela de login nao aparece.
     */
    private function encerrarSessoes(): void
    {
        foreach (array(self::$destinatario['URL'], self::$remetente['URL']) as $strUrl) {
            try {
                $this->url($strUrl);
                $this->sairSistema();
            } catch (\Exception $e) {
                // ja estava deslogado neste orgao
            }
        }
    }

    private function entrarComo(array $arrContexto): void
    {
        $this->acessarSistema(
            $arrContexto['URL'],
            $arrContexto['SIGLA_UNIDADE'],
            $arrContexto['LOGIN'],
            $arrContexto['SENHA']
        );
    }

    /**
     * Recria, pela tela, o Mapeamento de Envio Parcial do orgao logado para a
     * unidade informada.
     */
    private function recriarMapeamentoPara(array $arrContrapartida): void
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
    }

    private function lerFlagMapeamento(string $strContexto, $numIdEstrutura): string
    {
        $objBanco = new DatabaseUtils($strContexto);
        $arrResultado = $objBanco->query(
            'select sin_multiplos_orgaos from md_pen_envio_comp_digitais where id_unidade_pen = ?',
            array($numIdEstrutura)
        );

        return empty($arrResultado) ? '' : (string) $arrResultado[0]['SIN_MULTIPLOS_ORGAOS'];
    }

    /**
     * Simula a autorizacao explicita do administrador (unidade presente na
     * whitelist EnvioMultiplosOrgaos e checkbox marcado na tela).
     */
    private function habilitarMultiplosOrgaos(string $strContexto, $numIdEstrutura): void
    {
        $objBanco = new DatabaseUtils($strContexto);
        $objBanco->execute(
            'update md_pen_envio_comp_digitais set sin_multiplos_orgaos = ? where id_unidade_pen = ?',
            array('S', $numIdEstrutura)
        );
    }

    private function criarEEnviarProcesso(): array
    {
        $arrProcesso = $this->gerarDadosProcessoTeste(self::$remetente);
        $arrDocumento = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        // Descricao curta de proposito: o objetivo deste teste e a ativacao do
        // modo multiplos orgaos, nao o limite do campo identificacao.complemento.
        // Esse limite e coberto pelo EnvioComplementoLimiteTest.
        $arrDocumento['DESCRICAO'] = 'Doc teste 1230';

        $objProtocoloDTO = $this->cadastrarProcessoFixture($arrProcesso);
        $this->cadastrarDocumentoInternoFixture($arrDocumento, $objProtocoloDTO->getDblIdProtocolo());

        $this->abrirProcesso($arrProcesso['PROTOCOLO']);
        $this->tramitarProcessoExternamente(
            $arrProcesso['PROTOCOLO'],
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA']
        );

        return $arrProcesso;
    }

    private function definirContextos(): void
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
    }

    /**
     * CENARIO A - reproducao da #1230.
     *
     * Mapeamento criado pela tela, sem a unidade na whitelist: a flag fica 'N'
     * e o envio tem que se comportar como envio externo comum.
     *
     * #[Group('multiplos_orgaos')]
     */
    public function test_a_sem_autorizacao_o_envio_e_comum_e_processo_fica_bloqueado()
    {
        $this->definirContextos();
        $this->encerrarSessoes();
        $this->entrarComo(self::$remetente);

        $this->recriarMapeamentoPara(self::$destinatario);

        $this->assertEquals(
            'N',
            $this->lerFlagMapeamento(CONTEXTO_ORGAO_A, self::$destinatario['ID_ESTRUTURA']),
            'Pre-condicao do cenario: sem a unidade na whitelist o mapeamento tem que ficar com a flag desligada.'
        );

        $arrProcesso = $this->criarEEnviarProcesso();

        // Na origem: envio comum mantem o processo bloqueado.
        $this->abrirProcesso($arrProcesso['PROTOCOLO']);
        $this->assertTrue(
            $this->paginaProcesso->processoBloqueado(),
            'ATIVACAO SILENCIOSA: o processo ficou desbloqueado na origem, comportamento exclusivo do '
            . 'modo multiplos orgaos, sem que a funcionalidade tivesse sido habilitada.'
        );

        // No destino: sem tramite marcado como multiplos orgaos nao ha o que sincronizar.
        $this->entrarComo(self::$destinatario);
        $this->abrirProcesso($arrProcesso['PROTOCOLO']);

        $this->assertFalse(
            $this->paginaProcesso->validarBotaoExiste('Sincronizar Processo'),
            'O botao Sincronizar Processo nao pode ser oferecido para um tramite que nao e de multiplos orgaos.'
        );
    }

    /**
     * CENARIO B - a funcionalidade continua funcionando quando autorizada.
     *
     * Exige mapeamento habilitado nos dois orgaos: na origem para decidir o modo
     * do envio, no destino para permitir sincronizar/devolver (RN-04).
     *
     * #[Group('multiplos_orgaos')]
     */
    public function test_b_com_autorizacao_o_envio_e_multiplos_orgaos()
    {
        $this->definirContextos();
        $this->encerrarSessoes();

        // Lado do destino: mapeamento habilitado para a estrutura de origem.
        $this->entrarComo(self::$destinatario);
        $this->recriarMapeamentoPara(self::$remetente);
        $this->habilitarMultiplosOrgaos(CONTEXTO_ORGAO_B, self::$remetente['ID_ESTRUTURA']);

        $this->assertEquals(
            'S',
            $this->lerFlagMapeamento(CONTEXTO_ORGAO_B, self::$remetente['ID_ESTRUTURA']),
            'Pre-condicao do cenario: o destino precisa de mapeamento habilitado para a unidade de origem.'
        );

        // Lado da origem: mapeamento habilitado para a estrutura de destino.
        $this->encerrarSessoes();
        $this->entrarComo(self::$remetente);
        $this->recriarMapeamentoPara(self::$destinatario);
        $this->habilitarMultiplosOrgaos(CONTEXTO_ORGAO_A, self::$destinatario['ID_ESTRUTURA']);

        $this->assertEquals(
            'S',
            $this->lerFlagMapeamento(CONTEXTO_ORGAO_A, self::$destinatario['ID_ESTRUTURA']),
            'Pre-condicao do cenario: a origem precisa de mapeamento habilitado para a unidade de destino.'
        );

        $arrProcesso = $this->criarEEnviarProcesso();

        // Na origem: o processo permanece aberto e desbloqueado (o sentido de "manter aberto").
        $this->abrirProcesso($arrProcesso['PROTOCOLO']);
        $this->assertFalse(
            $this->paginaProcesso->processoBloqueado(),
            'REGRESSAO: com o modo multiplos orgaos habilitado o processo deveria permanecer '
            . 'desbloqueado na origem apos o envio.'
        );

        // No destino: o botao de sincronizar tem que estar disponivel.
        $this->entrarComo(self::$destinatario);
        $this->abrirProcesso($arrProcesso['PROTOCOLO']);

        $this->assertTrue(
            $this->paginaProcesso->validarBotaoExiste('Sincronizar Processo'),
            'REGRESSAO: o destino de um tramite de multiplos orgaos precisa poder solicitar sincronizacao.'
        );
    }

    /**
     * CENARIO C - sincronizar e devolver sao acoes DISTINTAS.
     *
     * A origem habilita multiplos orgaos e envia. O destino tem mapeamento para a
     * unidade de origem, porem com a flag DESLIGADA.
     *
     * Nesse estado o destino:
     *   - PODE solicitar sincronizacao (pedido de atualizacao a origem).
     *     pen_procedimento_sincronizar.php nao exige sin_multiplos_orgaos='S';
     *   - NAO PODE devolver o processo. ExpedirProcedimentoRN exige 'S' e recusa
     *     com a mensagem da RN-04.
     *
     * Este teste existe porque a distincao foi confundida uma vez: o criterio de
     * exibicao do botao chegou a ser endurecido para exigir 'S', o que tirou do
     * destino a capacidade legitima de pedir sincronizacao e quebrou seis metodos
     * dos testes TramiteSincronizacaoMultiplosOrgao*.
     *
     * #[Group('multiplos_orgaos')]
     */
    public function test_c_destino_sem_flag_ainda_pode_solicitar_sincronizacao()
    {
        $this->definirContextos();
        $this->encerrarSessoes();

        // Destino: mapeamento existe, com a flag DESLIGADA.
        $this->entrarComo(self::$destinatario);
        $this->recriarMapeamentoPara(self::$remetente);

        $this->assertEquals(
            'N',
            $this->lerFlagMapeamento(CONTEXTO_ORGAO_B, self::$remetente['ID_ESTRUTURA']),
            'Pre-condicao do cenario: o destino precisa ter mapeamento com a flag desligada.'
        );

        // Origem: habilitada, para o envio sair em modo multiplos orgaos.
        $this->encerrarSessoes();
        $this->entrarComo(self::$remetente);
        $this->recriarMapeamentoPara(self::$destinatario);
        $this->habilitarMultiplosOrgaos(CONTEXTO_ORGAO_A, self::$destinatario['ID_ESTRUTURA']);

        $arrProcesso = $this->criarEEnviarProcesso();

        $this->entrarComo(self::$destinatario);
        $this->abrirProcesso($arrProcesso['PROTOCOLO']);

        $this->assertTrue(
            $this->paginaProcesso->validarBotaoExiste('Sincronizar Processo'),
            'Solicitar sincronizacao nao depende de sin_multiplos_orgaos=S no destino: '
            . 'e um pedido de atualizacao a origem, nao um envio. Exigir a flag aqui remove '
            . 'uma capacidade legitima do destino.'
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
