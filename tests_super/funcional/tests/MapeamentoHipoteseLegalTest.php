<?php

/**
 * Testa a funcionalidade de mapeamento de hipótese legal
 *
 * @package tests_super\funcional\tests
 */
class MapeamentoHipoteseLegalTest extends CenarioBaseTestCase
{
    /**
     * @var array
     */
    public static $remetente;

    /**
     * @inheritdoc
     * @return void
     */
    function setUp(): void
    {
        parent::setUp();
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
    }

    /**
     * Verificar se lista de mapeamento de hipótese legal é exibida
     *
     * @group hipotese_legal
     *
     * @return void
     */
    public function test_verificar_lista_mapeamento_hipotese_legal_test()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaPenHipoteseLegalListar->navegarMapeamentoHipoteseLegalListar();
        $this->assertTrue($this->paginaPenHipoteseLegalListar->existeTabela());

        $this->sairSistema();
    }
}
