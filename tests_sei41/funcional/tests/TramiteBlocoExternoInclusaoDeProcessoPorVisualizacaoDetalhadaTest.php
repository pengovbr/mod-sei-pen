<?php

/**
 * Classe de Teste para Inclusão de Processo por Visualização Detalhada em Bloco de Trâmite.
 *
 * Esta classe realiza testes automatizados para verificar a inclusão de processos em blocos de trâmite
 * através da visualização detalhada. O teste simula um usuário acessando o sistema, selecionando um processo
 * e adicionando-o a um bloco de trâmite específico, validando se a operação foi realizada com sucesso.
 */
class TramiteBlocoExternoInclusaoDeProcessoPorVisualizacaoDetalhadaTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    
    /**
     * Método que testa a inclusão de um processo por meio da visualização detalhada.
     *
     * Este método configura o contexto do teste, gera dados necessários para o teste de inclusão,
     * realiza o acesso ao sistema, navega até a seção de controle de processos e executa a inclusão do
     * processo no bloco de trâmite. Após a inclusão, o método verifica se a mensagem de sucesso é exibida
     * corretamente.
     *
     * @return void
     */
    public function teste_inclusao_de_processo_por_visualizacao_detalhada()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Geração dos dados para o processo e documento de teste
        $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        
        // Cadastro do processo e documento
        $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);
        $this->cadastrarDocumentoInternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());
        
        // Carregar dados do bloco de trâmite
        $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
        $objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();

        // Acesso ao sistema
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        // Navegação para controle de processo e seleção de visualização detalhada
        $this->paginaBase->navegarParaControleProcesso();

        $visualizacaoDetalhadaAberta = $this->paginaTramiteEmBloco->visualizacaoDetalhadaAberta();
        if($visualizacaoDetalhadaAberta){
            $this->paginaTramiteEmBloco->fecharVisualizacaoDetalhada();
            $this->paginaTramiteEmBloco->selecionarVisualizacaoDetalhada();
        }else{
            $this->paginaTramiteEmBloco->selecionarVisualizacaoDetalhada();
        }
        
        // Seleção do processo e do bloco de trâmite
        $protocoloFormatado = $objProtocoloDTO->getStrProtocoloFormatado();
        $this->paginaTramiteEmBloco->selecionarProcesso($protocoloFormatado);
        $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
        
        // Verificação do título da página
        $titulo = mb_convert_encoding("Incluir Processo(s) no Bloco de Trâmite", 'UTF-8', 'ISO-8859-1');
        $tituloRetorno = $this->paginaTramiteEmBloco->verificarTituloDaPagina($titulo);
        $this->assertEquals($titulo, $tituloRetorno);

        // Inclusão do processo no bloco de trâmite
        $this->paginaTramiteEmBloco->selecionarBloco($objBlocoDeTramiteDTO->getNumId());
        $this->paginaTramiteEmBloco->clicarSalvar();

        // Espera para a mensagem de sucesso aparecer
        sleep(2);
        $mensagem = $this->paginaTramiteEmBloco->buscarMensagemAlerta();
        $this->assertStringContainsString(
            mb_convert_encoding('Processo(s) incluído(s) com sucesso no bloco ' . $objBlocoDeTramiteDTO->getNumOrdem(), 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );

        $this->paginaBase->navegarParaControleProcesso();       
        
        $visualizacaoDetalhadaAberta = $this->paginaTramiteEmBloco->visualizacaoDetalhadaAberta();
        if($visualizacaoDetalhadaAberta){
            $this->paginaTramiteEmBloco->fecharVisualizacaoDetalhada();
        }

        // Saída do sistema
        $this->sairSistema();
    }    
    
}
