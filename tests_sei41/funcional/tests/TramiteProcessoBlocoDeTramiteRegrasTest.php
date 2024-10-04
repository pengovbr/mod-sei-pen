<?php

use Tests\Funcional\Sei\Fixtures\{ProtocoloFixture,ProcedimentoFixture,AtividadeFixture,ContatoFixture,ParticipanteFixture,RelProtocoloAssuntoFixture,AtributoAndamentoFixture,DocumentoFixture,AssinaturaFixture,AnexoFixture,AnexoProcessoFixture};

/**
 *
 * Execution Groups
 * @group execute_parallel_group1
 */
class TramiteProcessoBlocoDeTramiteRegrasTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $objBlocoDeTramiteDTO;

    public static function setUpBeforeClass():void
    {
        $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
        self::$objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();
    }

    /**
     * Teste pra validar mensagem de documento não assinado ao ser inserido em bloco
     *
     * @group envio
     * @large
     *
     * @return void
     */
    public function test_validar_mensagem_de_documento_nao_assinado()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        // Cadastrar novo processo de teste
        $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);

        // Incluir e assinar documento no processo
        $dadosDocumentoDTO = [
            'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
            'IdProcedimento' => $objProtocoloDTO->getDblIdProtocolo(),
            'Descricao' => $documentoTeste['DESCRICAO'],
            'IdHipoteseLegal' => $documentoTeste["HIPOTESE_LEGAL"],
            'StaNivelAcessoGlobal' => $documentoTeste["RESTRICAO"],
            'StaNivelAcessoLocal' => $documentoTeste["RESTRICAO"],
        ];

        if ($serieDTO = $this->buscarIdSerieDoDocumento($documentoTeste['TIPO_DOCUMENTO'])) {
            $dadosDocumentoDTO['IdSerie'] = $serieDTO->getNumIdSerie();
        }

        $objDocumentoFixture = new DocumentoFixture();
        $objDocumentoDTO = $objDocumentoFixture->carregar($dadosDocumentoDTO);

        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->paginaBase->navegarParaControleProcesso();
        $this->paginaTramiteEmBloco->selecionarProcessos([$objProtocoloDTO->getStrProtocoloFormatado()]);
        $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
        $this->paginaTramiteEmBloco->selecionarBloco(self::$objBlocoDeTramiteDTO->getNumId());
        $this->paginaTramiteEmBloco->clicarSalvar();
        sleep(2);

        $mensagem = $this->paginaTramiteEmBloco->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Não é possível tramitar um processos com documentos gerados e não assinados'),
            $mensagem
        );
    }

    /**
     * Teste pra validar mensagem de processo bloqueado ao ser inserido em bloco 
     *
     * @group envio
     * @large
     *
     * @return void
     */
    public function test_validar_mensagem_de_processo_bloqueado()
    {
        // Configuração do dados para teste do cenário
        $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        // Cadastrar novo processo de teste
        $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);

        // Incluir e assinar documento no processo
        $this->cadastrarDocumentoInternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());

        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);
        $bancoOrgaoA->execute("update protocolo set sta_estado=? where id_protocolo=?;", array(4, $objProtocoloDTO->getDblIdProtocolo()));

        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->paginaBase->navegarParaControleProcesso();
        $this->paginaTramiteEmBloco->selecionarProcessos([$objProtocoloDTO->getStrProtocoloFormatado()]);
        $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
        $this->paginaTramiteEmBloco->selecionarBloco(self::$objBlocoDeTramiteDTO->getNumId());
        $this->paginaTramiteEmBloco->clicarSalvar();
        sleep(2);

        $mensagem = $this->paginaTramiteEmBloco->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Prezado(a) usuário(a), o processo ' . $objProtocoloDTO->getStrProtocoloFormatado() . ' encontra-se bloqueado. Dessa forma, não foi possível realizar a sua inserção no bloco selecionado.'),
            $mensagem
        );
    }

    /**
     * Teste pra validar a mensagem de processo aberto em mais de uma unidade ao ser inserido em bloco
     *
     * @group envio
     * @large
     *
     * @return void
     */
    public function test_validar_mensagem_de_processo_aberto_em_mais_de_uma_unidade()
    {
        // Configuração do dados para teste do cenário
        $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        // Cadastrar novo processo de teste
        $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);

        // Incluir e assinar documento no processo
        $this->cadastrarDocumentoInternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());

        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->abrirProcesso($objProtocoloDTO->getStrProtocoloFormatado());

        $this->tramitarProcessoInternamente(self::$remetente['SIGLA_UNIDADE_SECUNDARIA'], true);

        $this->paginaBase->navegarParaControleProcesso();
        $this->paginaTramiteEmBloco->selecionarProcessos([$objProtocoloDTO->getStrProtocoloFormatado()]);
        $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
        $this->paginaTramiteEmBloco->selecionarBloco(self::$objBlocoDeTramiteDTO->getNumId());
        $this->paginaTramiteEmBloco->clicarSalvar();
        sleep(2);

        $mensagem = $this->paginaTramiteEmBloco->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Não é possível tramitar um processo aberto em mais de uma unidade.'),
            $mensagem
        );
        $this->assertStringContainsString(
            utf8_encode('Processo ' . $objProtocoloDTO->getStrProtocoloFormatado() . ' está aberto na(s) unidade(s): ' . self::$remetente['SIGLA_UNIDADE_SECUNDARIA']),
            $mensagem
        );
    }

    /**
     * Teste pra validar a mensagem de processo sem documentos nao pode ser incluido em bloco
     *
     * @group envio
     * @large
     *
     * @return void
     */
    public function test_validar_mensagem_de_processo_sem_documento()
    {
        // Configuração do dados para teste do cenário
        $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);

        $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);        

        $this->paginaBase->navegarParaControleProcesso();
        $this->paginaTramiteEmBloco->selecionarProcessos([$objProtocoloDTO->getStrProtocoloFormatado()]);
        $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
        $this->paginaTramiteEmBloco->selecionarBloco(self::$objBlocoDeTramiteDTO->getNumId());
        $this->paginaTramiteEmBloco->clicarSalvar();
        sleep(2);

        $mensagem = $this->paginaTramiteEmBloco->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Não é possível tramitar um processo sem documentos'),
            $mensagem
        );
    }
}
