<?php

/**
 * Testes de mapeamento de envio de componentes digitais
 */
class MapeamentoRestricaoEnvioComponentesDigitaisTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste1;
    public static $documentoTeste2;
    public static $protocoloTeste;

    /**
     * Teste inicial de mapeamento de componentes digitais
     *
     * @group mapeamento
     *
     * @return void
     */
    public function test_novo_mapeamento_comp_digitais()
    {
        // Configura��o do dados para teste do cen�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        
        $this->navegarPara('pen_map_restricao_envio_comp_digitais_listar');
        sleep(10);
        $this->paginaCadastroMapEnvioCompDigitais->novoMap();
        $this->paginaCadastroMapEnvioCompDigitais->novo();

        sleep(10);

        $this->assertTrue(true);
    }
}