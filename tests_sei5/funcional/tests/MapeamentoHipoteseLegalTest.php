<?php

/**
 * Testa a funcionalidade de mapeamento de hip�tese legal
 */
class MapeamentoHipoteseLegalTest extends FixtureCenarioBaseTestCase
{
    /**
     * @var array
     */
    public static $remetente;

    /**
     * Verificar se lista de mapeamento de hip�tese legal � exibida
     *
     * @group hipotese_legal
     *
     * @return void
     */
    public function test_verificar_lista_mapeamento_hipotese_legal_test()
    {
        // Configura��o do dados para teste do cen�rio
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
