<?php

/**
 * Testes de trâmite de processos em lote
 *
 * Este mesmo documento deve ser recebido e assinalado como cancelado no destinatário e
 * a devolução do mesmo processo não deve ser impactado pela inserção de outros documentos
 */
class MapeamentoOrgaoExterno extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste1;
    public static $documentoTeste2;
    public static $protocoloTeste;

    /**
     * Teste inicial de trâmite de um processo contendo um documento movido
     *
     * @group envio
     *
     * @return void
     */
    public function test_novo_mapeamento_orgao_externo()
    {

        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->navegarPara('pen_map_orgaos_externos_listar');
        $this->paginaCadastroOrgaoExterno->novoMapOrgao();
        $this->paginaCadastroOrgaoExterno->novoMapeamentoOrgaoExterno();

        $this->assertTrue(true);
    }

    /**
     * @return void
     *
     * @depends test_novo_mapeamento_orgao_externo
     */
    public function test_importar_csv()
    {
        // $this->paginaCadastroOrgaoExterno->createFileToOpen();
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->navegarPara('pen_map_orgaos_externos_listar');
        $this->paginaCadastroOrgaoExterno->abrirSelecaoDeArquivoParaImportacao();

        sleep(10);

        $this->assertTrue(true);

    }
}
