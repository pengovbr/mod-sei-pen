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
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        
        $this->navegarPara('pen_map_restricao_envio_comp_digitais_listar');
        $this->paginaCadastroMapEnvioCompDigitais->novoMap();
        sleep(5);
        $this->paginaCadastroMapEnvioCompDigitais->novo();
        sleep(5);

        $this->assertTrue(true);    
    }
}