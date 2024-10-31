<?php

/**
 * Classe TramiteBlocoExternoInclusaoDeProcessoEmBlocoComHipoteseLegalNaoMapeadaTest
 * 
 * Esta classe contém testes automatizados para a inclusão de processos e documentos 
 * em blocos de trâmite quando há hipóteses legais não mapeadas. A classe estende 
 * FixtureCenarioBaseTestCase e se concentra em dois cenários principais:
 * 
 * 1. Inclusão de processo restrito com hipótese legal não mapeada.
 * 2. Inclusão de documento restrito com hipótese legal não mapeada.
 */
class TramiteBlocoExternoInclusaoDeProcessoEmBlocoComHipoteseLegalNaoMapeadaTest extends FixtureCenarioBaseTestCase
{
    /**
     * @var array $remetente Dados do remetente do processo
     */
    public static $remetente;

    /**
     * @var array $destinatario Dados do destinatário do processo
     */
    public static $destinatario;

    /**
     * Teste: Inclusão de processo em bloco restrito com hipótese legal não mapeada
     * 
     * Este método testa a inclusão de um processo restrito em um bloco de trâmite 
     * quando há uma hipótese legal não mapeada associada ao processo.
     * 
     * Passos do teste:
     * - Configuração do cenário de teste com remetente e destinatário.
     * - Geração de dados do processo e documento para o teste.
     * - Cadastro de uma hipótese legal não mapeada para o processo.
     * - Inclusão do processo no bloco de trâmite e verificação da mensagem de alerta.
     */
    public function teste_inclusao_de_processo_em_bloco_restrito_com_hipotese_legal_nao_mapeada()
    {
        // Configuração dos dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Geração dos dados para o processo e documento de teste
        $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        // Cadastrar Hipótese Legal não mapeada para tramitar o processo
        $objHipoteseLegalDTO = $this->cadastrarHipoteseLegal([
          'HIPOTESE_LEGAL' => 'Hipotese Legal Recusa Processo',
          'HIPOTESE_LEGAL_BASE_LEGAL' => 'Base Hipotese Legal Recusa Processo'
        ]);
        $processoTeste["HIPOTESE_LEGAL"] = $objHipoteseLegalDTO->getStrNome(). '('. $objHipoteseLegalDTO->getStrBaseLegal().')';
        $processoTeste["RESTRICAO"] = PaginaIniciarProcesso::STA_NIVEL_ACESSO_RESTRITO;

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

        // Seleção do processo e do bloco de trâmite
        $protocoloFormatado = $objProtocoloDTO->getStrProtocoloFormatado();
        $this->paginaTramiteEmBloco->selecionarProcesso($protocoloFormatado);
        $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();

        // Inclusão do processo no bloco de trâmite
        $this->paginaTramiteEmBloco->selecionarBloco($objBlocoDeTramiteDTO->getNumId());
        $this->paginaTramiteEmBloco->clicarSalvar();

        // Verificar se a mensagem de sucesso foi exibida
        sleep(2);
        $mensagem = $this->paginaTramiteEmBloco->buscarMensagemAlerta();
        
        // Validação: a mensagem de alerta deve conter a hipótese legal não mapeada
        $this->assertStringContainsString(
            mb_convert_encoding('Hipótese legal "'. $objHipoteseLegalDTO->getStrNome() . '" do processo '.$protocoloFormatado.' não mapeada', 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );

        // Saída do sistema
        $this->sairSistema();
    }

    /**
     * Teste: Inclusão de documento restrito com hipótese legal não mapeada
     * 
     * Este método testa a inclusão de um documento restrito em um processo com
     * uma hipótese legal não mapeada.
     * 
     * Passos do teste:
     * - Configuração do cenário de teste com remetente e destinatário.
     * - Geração de dados do processo e documento para o teste.
     * - Cadastro de uma hipótese legal não mapeada para o documento.
     * - Inclusão do documento no bloco de trâmite e verificação da mensagem de alerta.
     */
    public function teste_inclusao_de_processo_em_bloco_com_documento_restrito_com_hipotese_legal_nao_mapeada()
    {
        // Configuração dos dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Geração dos dados para o processo e documento de teste
        $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        // Cadastro do processo e documento
        $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);

        // Cadastrar Hipótese Legal não mapeada para tramitar o documento
        $objHipoteseLegalDTO = $this->cadastrarHipoteseLegal([
          'HIPOTESE_LEGAL' => 'Hipotese Legal Recusa Documento',
          'HIPOTESE_LEGAL_BASE_LEGAL' => 'Base Hipotese Legal Recusa Documento'
        ]);

        $documentoTeste["HIPOTESE_LEGAL"] = $objHipoteseLegalDTO->getStrNome(). '('. $objHipoteseLegalDTO->getStrBaseLegal().')';
        $documentoTeste["RESTRICAO"] = \ProtocoloRN::$NA_RESTRITO;

        // Cadastro do documento restrito
        $objDocummentoDTO = $this->cadastrarDocumentoInternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());

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

        // Seleção do processo e do bloco de trâmite
        $protocoloFormatado = $objProtocoloDTO->getStrProtocoloFormatado();
        $this->paginaTramiteEmBloco->selecionarProcesso($protocoloFormatado);
        $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();

        // Inclusão do processo no bloco de trâmite
        $this->paginaTramiteEmBloco->selecionarBloco($objBlocoDeTramiteDTO->getNumId());
        $this->paginaTramiteEmBloco->clicarSalvar();

        // Verificar se a mensagem de sucesso foi exibida
        sleep(2);
        $mensagem = $this->paginaTramiteEmBloco->buscarMensagemAlerta();
        
        // Validação: a mensagem de alerta deve conter a hipótese legal não mapeada
        $numeroDocumento = str_pad($objDocummentoDTO->getDblIdDocumento(), 6, "0", STR_PAD_LEFT);
        $this->assertStringContainsString(
            mb_convert_encoding('Hipótese legal "'. $objHipoteseLegalDTO->getStrNome() . '" do documento Ofício '.$numeroDocumento.' não mapeada', 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );

        // Saída do sistema
        $this->sairSistema();
    }
}
