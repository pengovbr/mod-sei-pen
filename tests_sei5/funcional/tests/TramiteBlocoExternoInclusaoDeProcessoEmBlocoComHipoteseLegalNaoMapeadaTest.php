<?php

/**
 * Classe TramiteBlocoExternoInclusaoDeProcessoEmBlocoComHipoteseLegalNaoMapeadaTest
 * 
 * Esta classe cont�m testes automatizados para a inclus�o de processos e documentos 
 * em blocos de tr�mite quando h� hip�teses legais n�o mapeadas. A classe estende 
 * FixtureCenarioBaseTestCase e se concentra em dois cen�rios principais:
 * 
 * 1. Inclus�o de processo restrito com hip�tese legal n�o mapeada.
 * 2. Inclus�o de documento restrito com hip�tese legal n�o mapeada.
 */
class TramiteBlocoExternoInclusaoDeProcessoEmBlocoComHipoteseLegalNaoMapeadaTest extends FixtureCenarioBaseTestCase
{
    /**
     * @var array $remetente Dados do remetente do processo
     */
    public static $remetente;

    /**
     * @var array $destinatario Dados do destinat�rio do processo
     */
    public static $destinatario;

    /**
     * Teste: Inclus�o de processo em bloco restrito com hip�tese legal n�o mapeada
     * 
     * Este m�todo testa a inclus�o de um processo restrito em um bloco de tr�mite 
     * quando h� uma hip�tese legal n�o mapeada associada ao processo.
     * 
     * Passos do teste:
     * - Configura��o do cen�rio de teste com remetente e destinat�rio.
     * - Gera��o de dados do processo e documento para o teste.
     * - Cadastro de uma hip�tese legal n�o mapeada para o processo.
     * - Inclus�o do processo no bloco de tr�mite e verifica��o da mensagem de alerta.
     */
    public function teste_inclusao_de_processo_em_bloco_restrito_com_hipotese_legal_nao_mapeada()
    {
        // Configura��o dos dados para teste do cen�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Gera��o dos dados para o processo e documento de teste
        $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        // Cadastrar Hip�tese Legal n�o mapeada para tramitar o processo
        $objHipoteseLegalDTO = $this->cadastrarHipoteseLegal([
          'HIPOTESE_LEGAL' => 'Hipotese Legal Recusa Processo',
          'HIPOTESE_LEGAL_BASE_LEGAL' => 'Base Hipotese Legal Recusa Processo'
        ]);
        $processoTeste["HIPOTESE_LEGAL"] = $objHipoteseLegalDTO->getStrNome(). '('. $objHipoteseLegalDTO->getStrBaseLegal().')';
        $processoTeste["RESTRICAO"] = PaginaIniciarProcesso::STA_NIVEL_ACESSO_RESTRITO;

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

        // Sele��o do processo e do bloco de tr�mite
        $protocoloFormatado = $objProtocoloDTO->getStrProtocoloFormatado();
        $this->paginaTramiteEmBloco->selecionarProcesso($protocoloFormatado);
        $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();

        // Inclus�o do processo no bloco de tr�mite
        $this->paginaTramiteEmBloco->selecionarBloco($objBlocoDeTramiteDTO->getNumId());
        $this->paginaTramiteEmBloco->clicarSalvar();

        // Verificar se a mensagem de sucesso foi exibida
        sleep(2);
        $mensagem = $this->paginaTramiteEmBloco->buscarMensagemAlerta();
        
        // Valida��o: a mensagem de alerta deve conter a hip�tese legal n�o mapeada
        $this->assertStringContainsString(
            mb_convert_encoding('Hip�tese legal "'. $objHipoteseLegalDTO->getStrNome() . '" do processo '.$protocoloFormatado.' n�o mapeada', 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );

        // Sa�da do sistema
        $this->sairSistema();
    }

    /**
     * Teste: Inclus�o de documento restrito com hip�tese legal n�o mapeada
     * 
     * Este m�todo testa a inclus�o de um documento restrito em um processo com
     * uma hip�tese legal n�o mapeada.
     * 
     * Passos do teste:
     * - Configura��o do cen�rio de teste com remetente e destinat�rio.
     * - Gera��o de dados do processo e documento para o teste.
     * - Cadastro de uma hip�tese legal n�o mapeada para o documento.
     * - Inclus�o do documento no bloco de tr�mite e verifica��o da mensagem de alerta.
     */
    public function teste_inclusao_de_processo_em_bloco_com_documento_restrito_com_hipotese_legal_nao_mapeada()
    {
        // Configura��o dos dados para teste do cen�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Gera��o dos dados para o processo e documento de teste
        $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        // Cadastro do processo e documento
        $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);

        // Cadastrar Hip�tese Legal n�o mapeada para tramitar o documento
        $objHipoteseLegalDTO = $this->cadastrarHipoteseLegal([
          'HIPOTESE_LEGAL' => 'Hipotese Legal Recusa Documento',
          'HIPOTESE_LEGAL_BASE_LEGAL' => 'Base Hipotese Legal Recusa Documento'
        ]);

        $documentoTeste["HIPOTESE_LEGAL"] = $objHipoteseLegalDTO->getStrNome(). '('. $objHipoteseLegalDTO->getStrBaseLegal().')';
        $documentoTeste["RESTRICAO"] = \ProtocoloRN::$NA_RESTRITO;

        // Cadastro do documento restrito
        $objDocummentoDTO = $this->cadastrarDocumentoInternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());

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

        // Sele��o do processo e do bloco de tr�mite
        $protocoloFormatado = $objProtocoloDTO->getStrProtocoloFormatado();
        $this->paginaTramiteEmBloco->selecionarProcesso($protocoloFormatado);
        $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();

        // Inclus�o do processo no bloco de tr�mite
        $this->paginaTramiteEmBloco->selecionarBloco($objBlocoDeTramiteDTO->getNumId());
        $this->paginaTramiteEmBloco->clicarSalvar();

        // Verificar se a mensagem de sucesso foi exibida
        sleep(2);
        $mensagem = $this->paginaTramiteEmBloco->buscarMensagemAlerta();
        
        // Valida��o: a mensagem de alerta deve conter a hip�tese legal n�o mapeada
        $numeroDocumento = str_pad($objDocummentoDTO->getDblIdDocumento(), 6, "0", STR_PAD_LEFT);
        $this->assertStringContainsString(
            mb_convert_encoding('Hip�tese legal "'. $objHipoteseLegalDTO->getStrNome() . '" do documento Of�cio '.$numeroDocumento.' n�o mapeada', 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );

        // Sa�da do sistema
        $this->sairSistema();
    }
}
