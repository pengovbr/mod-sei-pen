<?php

use PHPUnit\Framework\Attributes\{Group,Large,Depends};
use PHPUnit\Framework\AssertionFailedError;

/**
 * Execution Groups
 * #[Group('execute_parallel_group3')]
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
     * Teste de tr�mite externo de processo com devolu��o para a mesma unidade de origem
     *
     * #[Group('envio')]
     *
     * @return void
     */
  public function test_tramitar_processo_da_origem()
    {
 
      // Configura��o do dados para teste do cen�rio
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
     * Teste de realizar reprodu��o de �ltimo tramite
     *
     * #[Group('envio')]
     * #[Large]
     *
     * #[Depends('test_tramitar_processo_da_origem')]
     * @return void
     */
    public function test_realizar_pedido_reproducao_ultimo_tramite()
    {
        $strProtocoloTeste = self::$protocoloTeste;

        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);

        // 11 - Reproduzir �ltimo tr�mite
        $this->abrirProcesso($strProtocoloTeste);
        $resultadoReproducao = $this->paginaProcesso->reproduzirUltimoTramite();
        $this->assertStringContainsString(mb_convert_encoding("Reprodu��o de �ltimo tr�mite executado com sucesso!", 'UTF-8', 'ISO-8859-1'), $resultadoReproducao);

        $this->waitUntil(function() {
            sleep(5);
            $this->paginaBase->refresh();
            $this->paginaProcesso->navegarParaConsultarAndamentos();
            $mensagemTramite = mb_convert_encoding("Reprodu��o de �ltimo tr�mite iniciado para o protocolo ".  $strProtocoloTeste, 'UTF-8', 'ISO-8859-1');
          try {
              $this->assertTrue($this->paginaConsultarAndamentos->contemTramite($mensagemTramite));
              return true;
          } catch (AssertionFailedError $e) {
              return false;
          }

        }, PEN_WAIT_TIMEOUT);
    }

    /**
     * Teste para verificar a reprodu��o de �ltimo tramite no destinatario
     *
     * #[Group('envio')]
     * #[Large]
     *
     * #[Depends('test_tramitar_processo_da_origem')]
     *
     * @return void
     */
    public function test_reproducao_ultimo_tramite()
    {
        $strProtocoloTeste = self::$protocoloTeste;

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->abrirProcesso($strProtocoloTeste);

        $this->waitUntil(function() {
            sleep(5);
            $this->paginaBase->refresh();
            $this->paginaProcesso->navegarParaConsultarAndamentos();
            $mensagemTramite = mb_convert_encoding("Reprodu��o de �ltimo tr�mite recebido na entidade", 'UTF-8', 'ISO-8859-1');
          try {
              $this->assertTrue($this->paginaConsultarAndamentos->contemTramite($mensagemTramite));
              return true;
          } catch (AssertionFailedError $e) {
              return false;
          }

        }, PEN_WAIT_TIMEOUT);

    }

    /**
     * Teste para verificar a reprodu��o de �ltimo tramite no remetente
     *
     * #[Group('envio')]
     * #[Large]
     *
     * #[Depends('test_tramitar_processo_da_origem')]
     *
     * @return void
     */
    public function test_reproducao_ultimo_tramite_remetente_finalizado()
    {
        $strProtocoloTeste = self::$protocoloTeste;

        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);

        // 11 - Abrir protocolo na tela de controle de processos
        $this->abrirProcesso($strProtocoloTeste);
        
        $this->waitUntil(function() {
            sleep(5);
            $this->paginaBase->refresh();
            $this->paginaProcesso->navegarParaConsultarAndamentos();
            $mensagemTramite = mb_convert_encoding("Reprodu��o de �ltimo tr�mite finalizado para o protocolo ".  $strProtocoloTeste, 'UTF-8', 'ISO-8859-1');
          try {
              $this->assertTrue($this->paginaConsultarAndamentos->contemTramite($mensagemTramite));
              return true;
          } catch (AssertionFailedError $e) {
              return false;
          }

        }, PEN_WAIT_TIMEOUT);
    }


    /**
     * Teste de verifica��o do correto recebimento do processo no destinat�rio
     *
     * #[Group('verificacao_recebimento')]
     *
     * #[Depends('test_reproducao_ultimo_tramite_remetente_finalizado')]
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
        where protocolo_formatado=?", array(self::$protocoloTeste));

    if (array_key_exists("id_tramite", $idtEnviado[0])) {
        $idtEnviado=$idtEnviado[0]["id_tramite"];
    }else{
        $idtEnviado=$idtEnviado[0]["ID_TRAMITE"];
    }

      $curl_handler = curl_init();
      curl_setopt($curl_handler, CURLOPT_URL, PEN_ENDERECO_WEBSERVICE ."tramites/" . $idtEnviado);
      curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl_handler, CURLOPT_FAILONERROR, true);
      curl_setopt($curl_handler, CURLOPT_SSLCERT, $localCertificado);
      curl_setopt($curl_handler, CURLOPT_SSLCERTPASSWD, $senhaCertificado);

      $saida = json_decode(curl_exec($curl_handler));
      curl_close($curl_handler);

    foreach($saida->propriedadesAdicionais as $propriedades){
        
      switch($propriedades->chave){
 
        case "CLASSIFICACAO_PrazoIntermediario_1":
          $this->assertEquals('5', $propriedades->valor );
            break;
                   
        case "CLASSIFICACAO_PrazoCorrente_1":
            $this->assertEquals('NA', $propriedades->valor );
            break;
 
        case "MODULO_PEN_VERSAO":
             $this->assertTrue(isset($propriedades->valor));
            break;
 
        case "CLASSIFICACAO_CodigoEstruturado_1":
            $this->assertEquals('052.211', $propriedades->valor );
            break;
 
        case "CLASSIFICACAO_Destinacao_1":
             $this->assertEquals('E', substr($propriedades->valor, 0, 1));
            break;
 
        case "CLASSIFICACAO_Observacao_1":
            $this->assertEquals('Incluem-se documentos referentes', substr($propriedades->valor, 0, 32));
            break;
 
        case "CLASSIFICACAO_Descricao_1":
             $this->assertEquals('RECEITA CORRENTE', $propriedades->valor);
            break;                
                     
      }
    }
      $this->assertEquals(9, count($saida->processo->itensHistorico) );
  }

}
