<?php
/**
 * Cadastrar e editrar bloco
 *
 * Execution Groups
 * @group execute_alone_group1
 */
class TramiteBlocoExternoInclusaoTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $objBlocoDeTramiteDTO;
    public static $objProtocoloDTO;

    /**
     * Teste em duas etapas
     * 1 - Verifica se o bloco criado na unidade secundaria é vista na listagem da SUA unidade. Esperado: retorne True
     * 2 - Verifica se o bloco criado na unidade secundaria é vista na listagem de OUTRA unidade. Esperado: retorne False
     *
     * @return void
     */
    public function test_verificar_inclusao_em_bloco_de_outra_unidade()
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

        $this->selecionarUnidadeInterna(self::$remetente['SIGLA_UNIDADE_SECUNDARIA']);
        $dados = [
            'IdUnidade' => 110000002,
            'Descricao' => 'bloco_criado_' . self::$remetente['SIGLA_UNIDADE_SECUNDARIA']
        ];

        self::$objBlocoDeTramiteDTO = $this->cadastrarBlocoDeTramite($dados);
        $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();

        // etapa 1
        $arrColunaDescricao = $this->elements($this->using('xpath')->value("//td[4]"));
        $bolEncontrado = false;  
        foreach ($arrColunaDescricao as $elemento) {
            if (trim($elemento->text()) === self::$objBlocoDeTramiteDTO->getStrDescricao()) {
                $bolEncontrado = true;
                break; 
            }
        }
        
        $this->assertTrue($bolEncontrado);

        // etapa 2
        $this->selecionarUnidadeInterna(self::$remetente['SIGLA_UNIDADE']);
        $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();

        $arrColunaDescricao = $this->elements($this->using('xpath')->value("//td[4]"));

        $bolEncontrado = false;
        foreach ($arrColunaDescricao as $elemento) {
            if (trim($elemento->text()) === self::$objBlocoDeTramiteDTO->getStrDescricao()) {
                $bolEncontrado = true;
                break; 
            }
        }

        $this->assertFalse($bolEncontrado);

        $this->sairSistema();
    }

    /**
     * Teste Incluir processo já adicionado em outro bloco
     *
     * @return void
     */
    public function test_incluir_processo_em_mais_de_um_bloco()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        self::$objProtocoloDTO = $this->cadastrarProcessos();
        $objBlocoDeTramiteDTO = $this->cadastrarBlocoDeTramite();
        
        $dados = [
            'IdProtocolo' => self::$objProtocoloDTO->getDblIdProtocolo(),
            'IdBloco' => $objBlocoDeTramiteDTO->getNumId()
        ];

        $this->cadastrarProcessoBlocoDeTramite($dados);
    
        self::$objBlocoDeTramiteDTO = $this->cadastrarBlocoDeTramite();

        $this->paginaBase->navegarParaControleProcesso();

        $this->paginaTramiteEmBloco->selecionarProcessos([self::$objProtocoloDTO->getStrProtocoloFormatado()]);
        $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
        $this->paginaTramiteEmBloco->selecionarBloco(self::$objBlocoDeTramiteDTO->getNumId());
        $this->paginaTramiteEmBloco->clicarSalvar();

        sleep(1);
        $mensagem = $this->paginaCadastrarProcessoEmBloco->buscarMensagemAlerta();
        $this->assertStringContainsString(
            mb_convert_encoding(
                'Prezado(a) usuário(a), o processo ' . self::$objProtocoloDTO->getStrProtocoloFormatado()
                . ' encontra-se inserido no bloco ' . $objBlocoDeTramiteDTO->getNumOrdem() . ' - '
                .  self::$objBlocoDeTramiteDTO->getStrDescricao()
                . ' da unidade ' . self::$remetente['SIGLA_UNIDADE']
                . '. Para continuar com essa ação é necessário que o processo seja removido do bloco em questão.'
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