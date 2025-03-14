<?php

/**
 * Classe de Teste para Inclus�o de Processo por Visualiza��o Detalhada em Bloco de Tr�mite.
 *
 * Esta classe realiza testes automatizados para verificar a inclus�o de processos em blocos de tr�mite
 * atrav�s da visualiza��o detalhada. O teste simula um usu�rio acessando o sistema, selecionando um processo
 * e adicionando-o a um bloco de tr�mite espec�fico, validando se a opera��o foi realizada com sucesso.
 */
class TramiteBlocoExternoInclusaoDeProcessoPorVisualizacaoDetalhadaTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    
    /**
     * M�todo que testa a inclus�o de um processo por meio da visualiza��o detalhada.
     *
     * Este m�todo configura o contexto do teste, gera dados necess�rios para o teste de inclus�o,
     * realiza o acesso ao sistema, navega at� a se��o de controle de processos e executa a inclus�o do
     * processo no bloco de tr�mite. Ap�s a inclus�o, o m�todo verifica se a mensagem de sucesso � exibida
     * corretamente.
     *
     * @return void
     */
    public function teste_inclusao_de_processo_por_visualizacao_detalhada()
    {
        // Configura��o do dados para teste do cen�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Gera��o dos dados para o processo e documento de teste
        $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        
        // Cadastro do processo e documento
        $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);
        $this->cadastrarDocumentoInternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());
        
        // Carregar dados do bloco de tr�mite
        $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
        $objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();

        // Acesso ao sistema
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        // Navega��o para controle de processo e sele��o de visualiza��o detalhada
        $this->paginaBase->navegarParaControleProcesso();

        $visualizacaoDetalhadaAberta = $this->paginaTramiteEmBloco->visualizacaoDetalhadaAberta();
        if($visualizacaoDetalhadaAberta){
            $this->paginaTramiteEmBloco->fecharVisualizacaoDetalhada();
            $this->paginaTramiteEmBloco->selecionarVisualizacaoDetalhada();
        }else{
            $this->paginaTramiteEmBloco->selecionarVisualizacaoDetalhada();
        }
        
        // Sele��o do processo e do bloco de tr�mite
        $protocoloFormatado = $objProtocoloDTO->getStrProtocoloFormatado();
        $this->paginaTramiteEmBloco->selecionarProcesso($protocoloFormatado);
        $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
        
        // Verifica��o do t�tulo da p�gina
        $titulo = mb_convert_encoding("Incluir Processo(s) no Bloco de Tr�mite", 'UTF-8', 'ISO-8859-1');
        $tituloRetorno = $this->paginaTramiteEmBloco->verificarTituloDaPagina($titulo);
        $this->assertEquals($titulo, $tituloRetorno);

        // Inclus�o do processo no bloco de tr�mite
        $this->paginaTramiteEmBloco->selecionarBloco($objBlocoDeTramiteDTO->getNumId());
        $this->paginaTramiteEmBloco->clicarSalvar();

        // Espera para a mensagem de sucesso aparecer
        sleep(2);
        $mensagem = $this->paginaTramiteEmBloco->buscarMensagemAlerta();
        $this->assertStringContainsString(
            mb_convert_encoding('Processo(s) inclu�do(s) com sucesso no bloco ' . $objBlocoDeTramiteDTO->getNumOrdem(), 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );

        $this->paginaBase->navegarParaControleProcesso();       
        
        $visualizacaoDetalhadaAberta = $this->paginaTramiteEmBloco->visualizacaoDetalhadaAberta();
        if($visualizacaoDetalhadaAberta){
            $this->paginaTramiteEmBloco->fecharVisualizacaoDetalhada();
        }

        // Sa�da do sistema
        $this->sairSistema();
    }    
    
}
