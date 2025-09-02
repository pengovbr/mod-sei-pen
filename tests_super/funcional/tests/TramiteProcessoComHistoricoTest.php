<?php

/**
 * Execution Groups
 * @group execute_parallel_group3
 */
class TramiteProcessoComHistoricoTest extends FixtureCenarioBaseTestCase
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
        $this->realizarTramiteExternoComValidacaoNoRemetenteFixture(self::$processoTeste, $documentos, self::$remetente, self::$destinatario);
        self::$protocoloTeste = self::$processoTeste["PROTOCOLO"];

    }

    /**
     * Teste de realizar reprodução de último tramite
     *
     * @group envio
     * @large
     *
     * @depends test_tramitar_processo_da_origem
     *
     * @return void
     */
    public function test_realizar_pedido_reproducao_ultimo_tramite()
    {
        $strProtocoloTeste = self::$protocoloTeste;
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);
        
        // 11 - Reproduzir último trâmite
        $this->abrirProcesso($strProtocoloTeste);
        $resultadoReproducao = $this->paginaProcesso->reproduzirUltimoTramite();
        $this->assertStringContainsString(mb_convert_encoding("Reprodução de último trâmite executado com sucesso!", 'UTF-8', 'ISO-8859-1'), $resultadoReproducao);
        $this->refresh();
        $this->waitUntil(function ($testCase) {
            sleep(5);
            $testCase->refresh();
            $testCase->paginaProcesso->navegarParaConsultarAndamentos();
            $mensagemTramite = mb_convert_encoding("Reprodução de último trâmite iniciado para o protocolo ".  $strProtocoloTeste, 'UTF-8', 'ISO-8859-1');
            $testCase->assertTrue($testCase->paginaConsultarAndamentos->contemTramite($mensagemTramite));
            return true;
        }, PEN_WAIT_TIMEOUT);

    }

    /**
     * Teste para verificar a reprodução de último tramite no destinatario
     *
     * @group envio
     * @large
     *
     * @depends test_tramitar_processo_da_origem
     *
     * @return void
     */
  public function test_reproducao_ultimo_tramite()
  {
      $strProtocoloTeste = self::$protocoloTeste;

      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      $this->abrirProcesso($strProtocoloTeste);
      
      $this->waitUntil(function ($testCase) {
          sleep(5);
          $testCase->refresh();
          $testCase->paginaProcesso->navegarParaConsultarAndamentos();
          $mensagemTramite = mb_convert_encoding("Reprodução de último trâmite recebido na entidade", 'UTF-8', 'ISO-8859-1');
          $testCase->assertTrue($testCase->paginaConsultarAndamentos->contemTramite($mensagemTramite));
          return true;
      }, PEN_WAIT_TIMEOUT);

  }

    /**
     * Teste para verificar a reprodução de último tramite no remetente
     *
     * @group envio
     * @large
     *
     * @depends test_tramitar_processo_da_origem
     *
     * @return void
     */
  public function test_reproducao_ultimo_tramite_remetente_finalizado()
  {
      $strProtocoloTeste = self::$protocoloTeste;

      $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);

      // 11 - Abrir protocolo na tela de controle de processos
      $this->abrirProcesso($strProtocoloTeste);

      $this->waitUntil(function ($testCase) {
          sleep(5);
          $testCase->refresh();
          $testCase->paginaProcesso->navegarParaConsultarAndamentos();
          $mensagemTramite = mb_convert_encoding("Reprodução de último trâmite finalizado para o protocolo ".  $strProtocoloTeste, 'UTF-8', 'ISO-8859-1');
          $testCase->assertTrue($testCase->paginaConsultarAndamentos->contemTramite($mensagemTramite));
          return true;
      }, PEN_WAIT_TIMEOUT);
     
  }

    /**
     * Teste de verificação do correto recebimento do processo no destinatário
     *
     * @group verificacao_recebimento
     *
     * @depends test_reproducao_ultimo_tramite_remetente_finalizado
     *
     * @return void
     */
    public function test_verificar_destino_processo_para_devolucao()
    {
        $localCertificado = self::$destinatario['LOCALIZACAO_CERTIFICADO_DIGITAL'];
        $senhaCertificado = self::$destinatario['SENHA_CERTIFICADO_DIGITAL'];

        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);

        // Captura o IDT do processo
        $idtEnviado = $bancoOrgaoA->query("SELECT tra.id_tramite FROM protocolo p 
        inner join md_pen_processo_eletronico pen on p.id_protocolo=pen.id_procedimento
        inner join md_pen_tramite tra on pen.numero_registro=tra.numero_registro
        where protocolo_formatado=?",array(self::$protocoloTeste));

        if (array_key_exists("id_tramite", $idtEnviado[0])) {
            $idtEnviado=$idtEnviado[0]["id_tramite"];
        } else {
            $idtEnviado=$idtEnviado[0]["ID_TRAMITE"];
        }

        $curl_handler = curl_init();
        curl_setopt($curl_handler, CURLOPT_URL, PEN_ENDERECO_WEBSERVICE."tramites/" . $idtEnviado);
        curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handler, CURLOPT_FAILONERROR, true);
        curl_setopt($curl_handler, CURLOPT_SSLCERT, $localCertificado);
        curl_setopt($curl_handler, CURLOPT_SSLCERTPASSWD, $senhaCertificado);

        $saida = json_decode(curl_exec($curl_handler));
        curl_close($curl_handler);

        foreach($saida->propriedadesAdicionais as $propriedades){
        
            switch($propriedades->chave){
 
                case "CLASSIFICACAO_PrazoIntermediario_1":
                    $this->assertTrue(
                      '15' == $propriedades->valor,
                      'Classificação Prazo Intermediário 1 não é igual a 15. Valor: ' . $propriedades->valor
                    );
                     break;
                   
                case "CLASSIFICACAO_PrazoCorrente_1":
                    $this->assertTrue(
                      '5' == $propriedades->valor,
                      'Classificação Prazo Corrente 1 não é igual a 5. Valor: ' . $propriedades->valor
                    );
                     break;
 
                case "MODULO_PEN_VERSAO":
                    $this->assertTrue(
                      isset($propriedades->valor),
                      'Versão do módulo PEN não está definida. Valor: ' . $propriedades->valor
                    );
                     break;
 
                case "CLASSIFICACAO_CodigoEstruturado_1":
                    $this->assertTrue(
                      '052.212' == $propriedades->valor,
                      'Classificação Código Estruturado 1 não é igual a 052.212. Valor: ' . $propriedades->valor
                    );
                     break;
                     
            }
        }

        $this->assertEquals(
          9,
          sizeof($saida->processo->itensHistorico),
          'Quantidade de itens do histórico não é igual a 9. Valor: ' . sizeof($saida->processo->itensHistorico)
        );

    }

}
