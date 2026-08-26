<?php

use PHPUnit\Framework\Attributes\{Group};

/**
 * Regra de negocio sob teste (RN-01 / RN-02 de docs/regras.md):
 *
 *   O array "EnvioMultiplosOrgaos" do ConfiguracaoModPEN e uma whitelist das
 *   estruturas EXTERNAS (contrapartes) com as quais a instalacao local pode
 *   operar no modo "processo aberto e sincronizado".
 *
 *   Quando a unidade mapeada NAO esta na whitelist - ou quando a whitelist nem
 *   foi configurada - a opcao "Habilitar a opcao de manter o processo aberto na
 *   unidade selecionada" nao esta disponivel e, portanto, o mapeamento deve ser
 *   gravado com sin_multiplos_orgaos = 'N'.
 *
 *   Em outras palavras: a whitelist tem que falhar FECHADA. Ausencia de
 *   autorizacao explicita nunca pode resultar em funcionalidade ativada.
 *
 * Cobre a causa raiz investigada na issue #1230, em que o modo multiplos orgaos
 * aparecia ativo em um orgao sem que ninguem o tivesse ativado.
 *
 * Cada metodo de teste roda com sessao propria de navegador, por isso cada um
 * refaz a navegacao completa a partir do login.
 *
 * Execution Groups
 * #[Group('execute_alone_group1')]
 */
class EnvioMultiplosOrgaosWhitelistTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;

    private function abrirTelaNovoMapeamento()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaEnvioParcialListar->navegarEnvioParcialListar();
        $this->paginaCadastroMapEnvioCompDigitais->excluirMapeamentosExistentes();

        $this->paginaEnvioParcialListar->navegarEnvioParcialListar();
        $this->paginaCadastroMapEnvioCompDigitais->novo();
        $this->paginaCadastroMapEnvioCompDigitais->setarParametros(
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE']
        );
    }

    /**
     * Com a whitelist ausente/nao contemplando a unidade, o checkbox nao pode
     * ser exibido nem vir marcado.
     *
     * #[Group('multiplos_orgaos')]
     */
    public function test_checkbox_oculto_nao_pode_vir_marcado()
    {
        $this->abrirTelaNovoMapeamento();

        $arrEstado = $this->paginaCadastroMapEnvioCompDigitais->estadoCheckboxMultiplosOrgaos();

        $this->assertTrue(
            $arrEstado['presente'],
            'O checkbox de multiplos orgaos deveria existir no DOM da tela de cadastro.'
        );

        $this->assertFalse(
            $arrEstado['visivel'],
            'Sem a unidade na whitelist EnvioMultiplosOrgaos, o checkbox nao pode ser exibido.'
        );

        // Regra central: oculto implica desabilitado. Um checkbox invisivel e
        // marcado seria enviado no POST e ativaria a funcionalidade sem que o
        // administrador tivesse escolhido isso.
        $this->assertFalse(
            $arrEstado['marcado'],
            'FALHA ABERTA: o checkbox esta oculto porem MARCADO. '
            . 'A ausencia da unidade na whitelist esta ativando o modo multiplos orgaos.'
        );
    }

    /**
     * O mapeamento salvo nessas condicoes tem que ficar com a flag desligada
     * na base de dados.
     *
     * #[Group('multiplos_orgaos')]
     */
    public function test_mapeamento_salvo_fica_com_flag_desligada()
    {
        $this->abrirTelaNovoMapeamento();
        $this->paginaCadastroMapEnvioCompDigitais->salvar();

        sleep(1);

        $objBanco = new DatabaseUtils(CONTEXTO_ORGAO_A);
        $arrResultado = $objBanco->query(
            'select id_estrutura, id_unidade_pen, sin_multiplos_orgaos '
            . '  from md_pen_envio_comp_digitais '
            . ' where id_unidade_pen = ?',
            array(self::$destinatario['ID_ESTRUTURA'])
        );

        $this->assertNotEmpty(
            $arrResultado,
            'O mapeamento de envio parcial deveria ter sido gravado para a unidade '
            . self::$destinatario['ID_ESTRUTURA'] . '.'
        );

        $this->assertEquals(
            'N',
            $arrResultado[0]['sin_multiplos_orgaos'],
            'FALHA ABERTA: mapeamento gravado com sin_multiplos_orgaos = S sem que a unidade '
            . 'estivesse na whitelist EnvioMultiplosOrgaos e sem o administrador marcar a opcao.'
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
