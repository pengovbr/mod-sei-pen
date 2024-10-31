<?php

/**
 *
 * Execution Groups
 * @group execute_parallel_group1
 */
class TramiteProcessoSemDadosBlocoDeTramiteTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste;

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
        $objProtocoloDTO = $this->cadastrarProcessoFixture(self::$processoTeste, false);
        $this->atualizarProcessoFixture($objProtocoloDTO, ['DESCRICAO' => '']);

        // Incluir e assinar documento no processo
        $this->cadastrarDocumentoInternoFixture(self::$documentoTeste, $objProtocoloDTO->getDblIdProtocolo());

        $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
        $objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();

        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->paginaBase->navegarParaControleProcesso();
        $this->paginaTramiteEmBloco->selecionarProcessos([$objProtocoloDTO->getStrProtocoloFormatado()]);
        $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
        $this->paginaTramiteEmBloco->selecionarBloco($objBlocoDeTramiteDTO->getNumId());
        $this->paginaTramiteEmBloco->clicarSalvar();

        sleep(2);

        $mensagem = $this->paginaTramiteEmBloco->buscarMensagemAlerta();
        
        $this->assertStringContainsString(
            utf8_encode('Descrição do processo '.$objProtocoloDTO->getStrProtocoloFormatado().' não informado.'),
            $mensagem
        );
        $this->assertStringContainsString(
            utf8_encode('Interessados do processo '.$objProtocoloDTO->getStrProtocoloFormatado().' não informados.'),
            $mensagem
        );
    }
}
