<?php

/**
 *
 * Execution Groups
 * @group execute_parallel_group1
 */
class TramiteProcessoValidacaoBotoesAposIncluirTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste;
    public static $protocoloTeste;

    /**
     * Teste de validar existencia do botão de remover processo do bloco
     *
     * @group envio
     * @large
     *
     * @return void
     */
    public function test_validar_existencia_botao_remover_do_bloco()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        // Cadastrar novo processo de teste
        $objProtocoloDTO = $this->cadastrarProcessoFixture(self::$processoTeste);
        self::$protocoloTeste = $objProtocoloDTO->getStrProtocoloFormatado();

        // Incluir e assinar documento no processo
        $this->cadastrarDocumentoInternoFixture(self::$documentoTeste, $objProtocoloDTO->getDblIdProtocolo());

        $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
        $objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();

        $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
        $objBlocoDeTramiteProtocoloFixtureDTO = $objBlocoDeTramiteProtocoloFixture->carregar([
          'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
          'IdBloco' => $objBlocoDeTramiteDTO->getNumId()
        ]);

        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        // Abrir processo
        $this->abrirProcesso(self::$protocoloTeste);

        $this->assertNotTrue($this->paginaProcesso->validarBotaoExiste("Envio Externo de Processo"));
        $this->assertNotTrue($this->paginaProcesso->validarBotaoExiste(mb_convert_encoding("Incluir Processo no Bloco de Trâmite", 'UTF-8', 'ISO-8859-1')));
        $this->assertTrue($this->paginaProcesso->validarBotaoExiste(mb_convert_encoding("Remover Processo do Bloco de Trâmite", 'UTF-8', 'ISO-8859-1')));
    }
}
