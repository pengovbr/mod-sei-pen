<?php

class TramiteProcessoComArquivosGrandesTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentosTeste;
    public static $protocoloTeste;
    public static $numerosProcessos=array();


    public static function tearDownAfterClass() :void {

        $bancoOrgaoB = new DatabaseUtils(CONTEXTO_ORGAO_B);    
        $bancoOrgaoB->execute("update infra_parametro set valor = ? where nome = ?", array(50, 'SEI_TAM_MB_DOC_EXTERNO'));

    }

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

         $bancoOrgaoB = new DatabaseUtils(CONTEXTO_ORGAO_B);    
         $bancoOrgaoB->execute("update infra_parametro set valor = ? where nome = ?", array(120, 'SEI_TAM_MB_DOC_EXTERNO'));

         // Configuração do dados para teste do cenário
         self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
         self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
         self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
         self::$documentosTeste = array_merge(
            //  array_fill(0, 30, $this->gerarDadosDocumentoInternoTeste(self::$remetente)),
             array_fill(0, 10 , $this->gerarDadosDocumentoExternoGrandeTeste(self::$remetente,100))
         );
 
         shuffle(self::$documentosTeste);
 
        $this->realizarTramiteExternoSemvalidacaoNoRemetente(self::$processoTeste, self::$documentosTeste, self::$remetente, self::$destinatario,20);
        self::$numerosProcessos[]=self::$processoTeste['PROTOCOLO'];

        $novoTimeOut=PEN_WAIT_TIMEOUT*20;

        for($proc=0;$proc<9;$proc++){
        $this->duplicaProcessoCriado(self::$processoTeste);
        for($i=0;$i<2;$i++){
            $this->waitUntil(function(){
                exec(PEN_SCRIPT_MONITORAMENTO_ORG1);
                exec(PEN_SCRIPT_MONITORAMENTO_ORG2);
                return true;
                }, $novoTimeOut);
            }
            
            self::$numerosProcessos[]=$this->realizarTramiteExternoProcessoAberto(self::$processoTeste,self::$destinatario);      
        }

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

        foreach(self::$numerosProcessos as $numProcesso){

            self::$processoTeste['PROTOCOLO']=$numProcesso;            
            $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, self::$documentosTeste, self::$destinatario,false,false,false,20);
            $this->paginaBase->sairSistema();
        }
    }


}
