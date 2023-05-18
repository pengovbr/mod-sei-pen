<?php

class TramiteProcessoUnidadeSemHierarquiaPaiTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste1;
    public static $protocoloTeste;

    /**
     * Teste de trâmite externo de processo sem devolução para testar caso de hierarquia sem pai
     *
     * @group envio
     * @large
     * 
     * @Depends CenarioBaseTestCase::setUpBeforeClass
     *
     * @return void
     */
    public function test_tramitar_processo_da_origem()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_C);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        $documentos = array(self::$documentoTeste1);
        $this->realizarTramiteExternoSemvalidacaoNoRemetente(self::$processoTeste, $documentos, self::$remetente, self::$destinatario);
        self::$protocoloTeste = self::$processoTeste["PROTOCOLO"];

        $paginaProcesso = new PaginaProcesso($this);
        $this->assertStringNotContainsString(utf8_encode("externa para SEGES TESTE SEM PAI - - RE CGPRO"), $paginaProcesso->informacao());
    }
}
