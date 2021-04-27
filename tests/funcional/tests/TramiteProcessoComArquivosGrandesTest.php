<?php

class TramiteProcessoComArquivosGrandesTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentosTeste;
    public static $protocoloTeste;

    /**
     * Teste de trâmite externo de processo com devolução para a mesma unidade de origem
     *
     * @group envio
     * @group testePesado
     *
     * @return void
     */
    public function test_tramitar_processo_da_origem()
    {
         //Aumenta o tempo de timeout devido à quantidade de arquivos
         $this->setSeleniumServerRequestsTimeout(7200);

         // Configuração do dados para teste do cenário
         self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
         self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
         self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
         self::$documentosTeste = array_merge(
             array_fill(0, 30, $this->gerarDadosDocumentoInternoTeste(self::$remetente)),
             array_fill(0, 10, $this->gerarDadosDocumentoExternoGrandeTeste(self::$remetente,100))
         );
 
         shuffle(self::$documentosTeste);
 
        $this->realizarTramiteExternoComValidacaoNoRemetente(self::$processoTeste, self::$documentosTeste, self::$remetente, self::$destinatario,20);
        self::$protocoloTeste = self::$processoTeste["PROTOCOLO"];
        

    }


    /**
     * Teste de verificação do correto recebimento do processo no destinatário
     *
     * @group verificacao_recebimento
     * @group testePesado
     *
     * @depends test_tramitar_processo_da_origem
     *
     * @return void
     */
    public function test_verificar_destino_processo_para_devolucao()
    {
        $this->setSeleniumServerRequestsTimeout(7200);

        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, self::$documentosTeste, self::$destinatario,false,false,false);
        
    }


}
