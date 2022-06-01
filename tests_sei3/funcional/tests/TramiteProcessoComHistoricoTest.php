<?php

class TramiteProcessoComHistoricoTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste1;
    public static $documentoTeste2;
    public static $protocoloTeste;

    /**
     * Teste de trâmite externo de processo com devolução para a mesma unidade de origem
     *
     * @group envio
     *
     * @return void
     */
    public function test_tramitar_processo_da_origem()
    {
 

        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        self::$documentoTeste2 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

        $documentos = array(self::$documentoTeste1, self::$documentoTeste2);
        $this->realizarTramiteExternoComValidacaoNoRemetente(self::$processoTeste, $documentos, self::$remetente, self::$destinatario);
        self::$protocoloTeste = self::$processoTeste["PROTOCOLO"];

    }


    /**
     * Teste de verificação do correto recebimento do processo no destinatário
     *
     * @group verificacao_recebimento
     *
     * @depends test_tramitar_processo_da_origem
     *
     * @return void
     */
    public function test_verificar_destino_processo_para_devolucao()
    {


        $localCertificado = self::$destinatario['LOCALIZACAO_CERTIFICADO_DIGITAL'];
        $senhaCertificado = self::$destinatario['SENHA_CERTIFICADO_DIGITAL'];

        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);

        // Captura o IDT do processo
        $idtEnviado=$bancoOrgaoA->query("SELECT tra.id_tramite FROM sei.protocolo p 
        inner join sei.md_pen_processo_eletronico pen on p.id_protocolo=pen.id_procedimento
        inner join sei.md_pen_tramite tra on pen.numero_registro=tra.numero_registro
        where protocolo_formatado=?",array(self::$protocoloTeste));

        $curl_handler = curl_init();
        curl_setopt($curl_handler, CURLOPT_URL, "https://homolog.api.processoeletronico.gov.br/interoperabilidade/rest/v2/tramites/" . $idtEnviado[0]["id_tramite"]);
        curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handler, CURLOPT_FAILONERROR, true);
        curl_setopt($curl_handler, CURLOPT_SSLCERT, $localCertificado);
        curl_setopt($curl_handler, CURLOPT_SSLCERTPASSWD, $senhaCertificado);

        $saida= json_decode(curl_exec($curl_handler));
        curl_close($curl_handler);



        foreach($saida->propriedadesAdicionais as $propriedades){
        
            switch($propriedades->chave){
 
                case "CLASSIFICACAO_PrazoIntermediario_1":
                     $this->assertEquals('15', $propriedades->valor );
                     break;
                   
                case "CLASSIFICACAO_PrazoCorrente_1":
                     $this->assertEquals('5', $propriedades->valor );
                     break;
 
                case "MODULO_PEN_VERSAO":
                     $this->assertTrue(isset($propriedades->valor));
                     break;
 
                case "CLASSIFICACAO_CodigoEstruturado_1":
                    $this->assertEquals('052.21', $propriedades->valor );
                     break;
 
                case "CLASSIFICACAO_Destinacao_1":
                     $this->assertEquals('Elimina', substr($propriedades->valor,0,7) );
                     break;
 
                case "CLASSIFICACAO_Observacao_1":
                    $this->assertEquals('Condicional', substr($propriedades->valor,0,11) );
                     break;
 
                case "CLASSIFICACAO_Descricao_1":
                     $this->assertEquals('RECEITA (inclusive', substr($propriedades->valor,0,18));
                     break;
                     
                     
            }
        }
 
     //    usort($saida->processo->itensHistorico,function($a,$b){
     //      return ($a->dataHoraOperacao < $b->dataHoraOperacao? -1: 1);
     //     });

        $this->assertEquals(5, sizeof($saida->processo->itensHistorico) );






    }



}
