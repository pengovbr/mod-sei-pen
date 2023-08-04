<?php

/**
 * Testes de trâmite de processos em lote
 *
 * Este mesmo documento deve ser recebido e assinalado como cancelado no destinatário e
 * a devolução do mesmo processo não deve ser impactado pela inserção de outros documentos
 */
class ReordernarProcesso extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste1;
    public static $documentoTeste2;
    public static $protocoloTeste;
    public static $documentosTeste;


    /**
     *  Testa a funcionalidade de reordernar documento no Super
     *
     * @return true
     *
     */
    public function test_reordenar_ordem()
    {
        //Aumenta o tempo de timeout devido à quantidade de arquivos
        $this->setSeleniumServerRequestsTimeout(6000);

        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentosTeste[] = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        self::$documentosTeste[] = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        self::$documentosTeste[] = $this->gerarDadosDocumentoInternoTeste(self::$remetente);


        $this->assinarDocumentosInterno(self::$processoTeste, self::$documentosTeste, self::$remetente, self::$destinatario);
        $this->abrirProcesso(self::$processoTeste["PROTOCOLO"]);
        sleep(3);
        $this->paginaReordenarProcesso->irParaPaginaMudarOrdem();
        $this->paginaReordenarProcesso->clicarReordenar();
        sleep(3);
        $this->acceptAlert();
        $this->assertTrue(true);
    }

    public function assinarDocumentosInterno(&$processoTeste, $documentosTeste, $remetente, $destinatario)
    {
        $orgaosDiferentes = $remetente['URL'] != $destinatario['URL'];

        // 1 - Acessar sistema do REMETENTE do processo
        $this->acessarSistema($remetente['URL'], $remetente['SIGLA_UNIDADE'], $remetente['LOGIN'], $remetente['SENHA']);

        // 2 - Cadastrar novo processo de teste
        if (isset($processoTeste['PROTOCOLO'])) {
            $strProtocoloTeste = $processoTeste['PROTOCOLO'];
            $this->abrirProcesso($strProtocoloTeste);
        } else {
            $strProtocoloTeste = $this->cadastrarProcesso($processoTeste);
            $processoTeste['PROTOCOLO'] = $strProtocoloTeste;
        }

        // 3 - Incluir Documentos no Processo
        $documentosTeste = array_key_exists('TIPO', $documentosTeste) ? array($documentosTeste) : $documentosTeste;
        foreach ($documentosTeste as $doc) {
            $this->cadastrarDocumentoInterno($doc);
            $this->assinarDocumento($remetente['ORGAO'], $remetente['CARGO_ASSINATURA'], $remetente['SENHA']);
        }
    }
}
