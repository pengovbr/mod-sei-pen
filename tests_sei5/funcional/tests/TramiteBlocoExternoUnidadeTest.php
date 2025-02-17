<?php
/**
 * Execution Groups
 * @group execute_alone_group1
 */
class TramiteBlocoExternoUnidadeTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $objBlocoDeTramiteDTO;
    public static $objProtocoloDTO;
    public static $documentoTeste;

    /**
     * Incluir processo que contém documento de outra unidade dentro de um bloco externo
     * 
     * @return void
     */
    public function test_envio_de_bloco_externo_para_outra_unidade()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );   

        self::$objProtocoloDTO = $this->cadastrarProcessos();

        $this->abrirProcesso(self::$objProtocoloDTO->getStrProtocoloFormatado());
        $this->assertTrue($this->paginaProcesso->processoAberto());

        // enviar processo e criar documento na unidade secundária
        $this->tramitarProcessoInternamente(self::$remetente['SIGLA_UNIDADE_SECUNDARIA']);
        $this->selecionarUnidadeInterna(self::$remetente['SIGLA_UNIDADE_SECUNDARIA']);

        $this->paginaControleProcesso->abrirProcesso(self::$objProtocoloDTO->getStrProtocoloFormatado());
        $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        
        $documentoTeste['UNIDADE_RESPONSAVEL'] = 110000002;
        $this->cadastrarDocumentoInternoFixture($documentoTeste, self::$objProtocoloDTO->getDblIdProtocolo());

        // devolver processo com novo documento
        $this->tramitarProcessoInternamente(self::$remetente['SIGLA_UNIDADE']);
        $this->selecionarUnidadeInterna(self::$remetente['SIGLA_UNIDADE']);

        self::$objBlocoDeTramiteDTO = $this->cadastrarBlocoDeTramite();
        sleep(2);

        $this->paginaTramiteEmBloco->selecionarProcessos([self::$objProtocoloDTO->getStrProtocoloFormatado()]);
        $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
        $this->paginaTramiteEmBloco->selecionarBloco(self::$objBlocoDeTramiteDTO->getNumId());
        $this->paginaTramiteEmBloco->clicarSalvar();

        sleep(1);
        $mensagem = $this->paginaCadastrarProcessoEmBloco->buscarMensagemAlerta();
        $this->assertStringContainsString(
            mb_convert_encoding(
                "Processo(s) incluído(s) com sucesso no bloco " . self::$objBlocoDeTramiteDTO->getNumOrdem()
                , 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );

        $this->sairSistema();
    }


    /**
     * Cadastra o bloco de tramite
     */
    public function cadastrarBlocoDeTramite($dados = [])
    {
        $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
        return $objBlocoDeTramiteFixture->carregar($dados);
    }

    /**
     * Cadastra processo em um bloco de tramite
     */
    public function cadastrarProcessoBlocoDeTramite($dados = [])
    {
        $objBlocoDeTramiteFixture = new \BlocoDeTramiteProtocoloFixture();
        return $objBlocoDeTramiteFixture->carregar($dados);
    }

    /**
     * Cadastra o bloco de tramite
     */
    private function cadastrarProcessos()
    {
        $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        
        $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);
        $this->cadastrarDocumentoInternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());
        
        return $objProtocoloDTO;
    }
}