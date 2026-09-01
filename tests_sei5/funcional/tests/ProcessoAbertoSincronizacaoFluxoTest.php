<?php

use PHPUnit\Framework\Attributes\{Depends, Group};

/**
 * Fluxo completo de processo aberto e sincronizado.
 *
 * Cobre os casos de uso CU-04, CU-06, CU-09 e CU-10 de
 * docs/casos-uso-processo-aberto.md:
 *
 *   CU-04  ORG1 envia mantendo o processo aberto
 *   CU-06  ORG1 inclui documento depois do envio; ORG2 sincroniza e passa a ve-lo
 *   CU-09  ORG2 inclui documento e devolve; ORG1 passa a ve-lo
 *   CU-10  ORG1 inclui outro documento; ORG2 sincroniza de novo
 *
 * Configuracao proposital (armadilha 3 do documento): a flag sin_multiplos_orgaos
 * fica 'S' apenas na ORIGEM. O destino recebe mapeamento com 'N', que e o estado
 * real de campo - o array EnvioMultiplosOrgaos lista quem RECEBE, entao a unidade
 * de origem nunca esta nele. Habilitar os dois lados mascara defeitos de devolucao.
 *
 * Arquivos distintos em cada documento, de proposito: conteudo repetido cai na
 * ACH-03 e falharia por motivo alheio a este cenario.
 *
 * Execution Groups
 * #[Group('execute_alone_group9')]
 */
class ProcessoAbertoSincronizacaoFluxoTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;

    /**
     * Cria o Mapeamento de Envio Parcial para a contraparte.
     * $bolAtivar so vale para quem ORIGINA o envio com processo aberto.
     */
    private function configurarMapeamento(array $arrContrapartida, string $strContexto, bool $bolAtivar): void
    {
        $this->paginaEnvioParcialListar->navegarEnvioParcialListar();
        $this->paginaCadastroMapEnvioCompDigitais->excluirMapeamentosExistentes();

        $this->paginaEnvioParcialListar->navegarEnvioParcialListar();
        $this->paginaCadastroMapEnvioCompDigitais->novo();
        $this->paginaCadastroMapEnvioCompDigitais->setarParametros(
            $arrContrapartida['REP_ESTRUTURAS'],
            $arrContrapartida['NOME_UNIDADE']
        );
        $this->paginaCadastroMapEnvioCompDigitais->salvar();
        sleep(1);

        $objBanco = new DatabaseUtils($strContexto);
        $objBanco->execute(
            'update md_pen_envio_comp_digitais set sin_multiplos_orgaos = ? where id_unidade_pen = ?',
            array($bolAtivar ? 'S' : 'N', $arrContrapartida['ID_ESTRUTURA'])
        );

        // Conferencia da pre-condicao. O UPDATE acima nao reclama quando nao
        // encontra linha, e a gravacao pela tela pode falhar em silencio - sem
        // esta checagem o teste seguia como se o mapeamento existisse, e os
        // verdes desta classe nao provavam que o fluxo multiplos orgaos foi
        // realmente exercitado.
        $arrFlag = $objBanco->query(
            'select sin_multiplos_orgaos from md_pen_envio_comp_digitais where id_unidade_pen = ?',
            array($arrContrapartida['ID_ESTRUTURA'])
        );
        $this->assertNotEmpty(
            $arrFlag,
            'Pre-condicao: Mapeamento de Envio Parcial nao gravado para a estrutura '
                . $arrContrapartida['ID_ESTRUTURA'] . ' no contexto ' . $strContexto . '.'
        );
        $this->assertEquals(
            $bolAtivar ? 'S' : 'N',
            trim((string) $arrFlag[0]['SIN_MULTIPLOS_ORGAOS']),
            'Pre-condicao: a flag de multiplos orgaos nao ficou no estado esperado.'
        );
    }

    private function encerrarSessoes(): void
    {
        foreach (array(CONTEXTO_ORGAO_A_URL, CONTEXTO_ORGAO_B_URL) as $strUrl) {
            try {
                $this->url($strUrl);
                $this->sairSistema();
            } catch (\Exception $e) {
                // ja estava deslogado neste orgao
            }
        }
    }

    private function entrarComo(array $arrOrgao): void
    {
        $this->encerrarSessoes();
        $this->acessarSistema(
            $arrOrgao['URL'],
            $arrOrgao['SIGLA_UNIDADE'],
            $arrOrgao['LOGIN'],
            $arrOrgao['SENHA']
        );
    }

    /**
     * A devolucao e a sincronizacao sao sequencias de dois tramites, e cada um
     * avanca alguns status por ciclo (armadilha 4). Uma execucao so nao basta.
     */
    /**
     * Abre o processo e so devolve quando a arvore terminou de montar.
     *
     * abrirProcesso() da classe base retorna sem garantir que a tela carregou:
     * ela tenta abrir pelo Controle de Processos e, se falhar, cai numa busca
     * alternativa - mas nao confere o resultado. Sob carga de suite, o comando
     * seguinte procura o iframe da arvore antes de ele existir e o teste morre
     * com "Unable to locate element: //iframe[@id='ifrArvore']".
     *
     * A espera e por CONDICAO, nao por relogio: repete ate o iframe existir.
     * O try/catch e obrigatorio - WebDriverWait::until repete o RETORNO da
     * closure, mas nao sobrevive a uma excecao, entao NoSuchElement abortaria
     * a espera em vez de tentar de novo.
     */
    private function abrirProcessoEAguardarArvore(string $strProtocolo): void
    {
        $this->abrirProcesso($strProtocolo);

        try {
            $this->waitUntil(function () {
                try {
                    $this->paginaBase->frame(null);
                    $this->paginaBase->elByXPath("//iframe[@id='ifrArvore' or @name='ifrArvore']");
                    return true;
                } catch (\Exception $e) {
                    return null;
                }
            }, PEN_WAIT_TIMEOUT);
        } catch (\Exception $e) {
            // A espera esgotou: a arvore nunca montou. Sem diagnostico, o erro
            // sobe como TimeoutException seco e nao diz em que tela paramos.
            // Captura o estado real para distinguir sessao expirada, tela errada
            // ou processo que nao abriu.
            throw new \Exception(
                'ARVORE NAO MONTOU apos abrir ' . $strProtocolo . '. ' . $this->diagnosticarTela(),
                0,
                $e
            );
        }

        $this->paginaBase->frame(null);
    }

    /**
     * Coleta o estado da tela para diagnostico quando uma espera esgota.
     * Cada leitura vai em try/catch proprio: se o navegador estiver num estado
     * ruim, uma falha aqui nao pode mascarar o erro original.
     */
    private function diagnosticarTela(): string
    {
        $arrInfo = array();

        try { $arrInfo[] = 'url=' . self::$driver->getCurrentURL(); }
        catch (\Exception $e) { $arrInfo[] = 'url=<indisponivel>'; }

        try { $arrInfo[] = 'titulo=' . $this->paginaBase->titulo(); }
        catch (\Exception $e) { $arrInfo[] = 'titulo=<indisponivel>'; }

        try {
            $this->paginaBase->frame(null);
            $arrFrames = $this->paginaBase->elementsByXPath('//iframe');
            $arrNomes = array();
            foreach ($arrFrames as $objFrame) {
                $strId = (string) $objFrame->getAttribute('id');
                $arrNomes[] = $strId !== '' ? $strId : '<sem-id>';
            }
            $arrInfo[] = 'iframes=[' . implode(',', $arrNomes) . ']';
        } catch (\Exception $e) {
            $arrInfo[] = 'iframes=<indisponivel>';
        }

        try {
            $strTexto = preg_replace('/\s+/', ' ', $this->paginaBase->getConteudoBody());
            $arrInfo[] = 'corpo="' . substr($strTexto, 0, 300) . '"';
        } catch (\Exception $e) {
            $arrInfo[] = 'corpo=<indisponivel>';
        }

        return implode(' | ', $arrInfo);
    }

    private function processarPendenciasAte(callable $fnCondicao, int $numCiclosMax = 45): void
    {
        for ($i = 0; $i < $numCiclosMax; $i++) {
            $this->executarTramitarPendenciasSimples();

            // Espera pelo EFEITO, nao pelo relogio. A versao anterior fazia
            // 8 ciclos fixos com sleep(3): quando o barramento demorava mais que
            // isso o teste falhava, e quando respondia rapido desperdicava tempo.
            //
            // O teto precisa ser ALTO por causa da fila. O monitoramento
            // processa um lote limitado por execucao (ver NUMERO_PROCESSOS_MONITORAMENTO
            // e MAXIMO_PROCESSOS_MONITORAMENTO em PendenciasTramiteRN), e ao rodar
            // dentro da suite a fila ja chega com quase 200 pendencias deixadas
            // pelas classes anteriores. O tramite deste teste entra atras de todas
            // elas. Isolado a fila e curta e bastam poucos ciclos - foi por isso
            // que a classe passava sozinha e falhava na suite.
            //
            // Ciclos extras custam pouco: a condicao consulta o banco e o laco
            // encerra assim que o efeito chega.
            try {
                if ($fnCondicao()) {
                    return;
                }
            } catch (\Exception $e) {
                // segue para o proximo ciclo
            }

            sleep(3);
        }
    }

    private function incluirDocumentoExterno(array $arrOrgao, string $strContextoBanco, string $strArquivo): void
    {
        putenv('DATABASE_HOST=' . $strContextoBanco);
        $arrDados = $this->gerarDadosDocumentoExternoTeste($arrOrgao, $strArquivo);
        $objProtocoloDTO = $this->consultarProcessoFixture(
            self::$processoTeste['PROTOCOLO'],
            ProtocoloRN::$TP_PROCEDIMENTO
        );
        $this->cadastrarDocumentoExternoFixture($arrDados, $objProtocoloDTO->getDblIdProtocolo());
    }

    /**
     * Aciona o botao de sincronizar e fecha o alerta de confirmacao.
     * Sem fechar, o proximo comando do Selenium falha com "unexpected alert open".
     */
    private function solicitarSincronizacaoEConfirmar(): void
    {
        $this->paginaProcesso->solicitarSincronizacao('Sincronizar Processo');

        $strAlerta = $this->paginaBase->alertTextAndClose(true);

        $this->assertStringContainsString(
            'de sincroniza',
            $strAlerta,
            'A solicitacao de sincronizacao nao foi confirmada.'
        );
    }

    /**
     * Conta os documentos do processo direto no banco do orgao informado.
     *
     * Serve como CONDICAO de espera, onde contarDocumentos() nao pode ser usada:
     * aquela dirige o navegador e, quando o processo ainda nao chegou, gasta o
     * PEN_WAIT_TIMEOUT inteiro (360s) esperando a arvore. Doze ciclos assim dao
     * mais de uma hora. A consulta ao banco responde em milissegundos.
     */
    private function contarDocumentosNoBanco(string $strContexto): int
    {
        $objBanco = new DatabaseUtils($strContexto);
        $arrResultado = $objBanco->query(
            'select count(*) as total from documento
              where id_procedimento = (select id_protocolo from protocolo
                                        where protocolo_formatado = ?)',
            array(self::$processoTeste['PROTOCOLO'])
        );

        return empty($arrResultado) ? 0 : (int) $arrResultado[0]['TOTAL'];
    }

    private function contarDocumentos(array $arrOrgao): int
    {
        $this->entrarComo($arrOrgao);
        $this->abrirProcessoEAguardarArvore(self::$processoTeste['PROTOCOLO']);
        $arrDocumentos = $this->paginaProcesso->listarDocumentos();

        return is_array($arrDocumentos) ? count($arrDocumentos) : 0;
    }

    /**
     * CU-04: envio mantendo o processo aberto na origem.
     *
     * #[Depends('CenarioBaseTestCase::setUpBeforeClass')]
     */
    public function test_cu04_enviar_mantendo_processo_aberto()
    {
        putenv('DATABASE_HOST=org1-database');

        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Destino: mapeamento SEM a flag - estado real de campo.
        $this->entrarComo(self::$destinatario);
        $this->configurarMapeamento(self::$remetente, CONTEXTO_ORGAO_B, false);

        // Origem: mapeamento COM a flag - so ela origina.
        $this->entrarComo(self::$remetente);
        $this->configurarMapeamento(self::$destinatario, CONTEXTO_ORGAO_A, true);

        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        $objProtocoloDTO = $this->cadastrarProcessoFixture(self::$processoTeste);

        $arrDoc1 = $this->gerarDadosDocumentoExternoTeste(self::$remetente, 'arquivo_pequeno_A.pdf');
        $this->cadastrarDocumentoExternoFixture($arrDoc1, $objProtocoloDTO->getDblIdProtocolo());

        $this->abrirProcessoEAguardarArvore(self::$processoTeste['PROTOCOLO']);
        $this->tramitarProcessoExternamente(
            self::$processoTeste['PROTOCOLO'],
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false,
            null,
            PEN_WAIT_TIMEOUT,
            true,
            true
        );

        // O tramite dispara UM ciclo de pendencias dentro de
        // tramitarProcessoExternamente(). Isso basta com a maquina ociosa, mas
        // nao sob carga de suite: o processo ainda nao chegou ao destino, e
        // abrirProcesso() cai no fallback de busca - a tela fica em
        // "SEI - Pesquisa", sem iframe, e a espera pela arvore esgota.
        //
        // Os demais casos desta classe (cu06, cu09, cu10) ja esperavam pelas
        // pendencias; o cu04 era o unico que nao esperava.
        $this->processarPendenciasAte(function () {
            return $this->contarDocumentosNoBanco(CONTEXTO_ORGAO_B) === 1;
        });

        $this->assertEquals(
            1,
            $this->contarDocumentos(self::$destinatario),
            'CU-04: o destino deveria exibir o documento enviado.'
        );
    }

    /**
     * CU-06: documento incluido na origem apos o envio chega por sincronizacao.
     */
    #[Depends('test_cu04_enviar_mantendo_processo_aberto')]
    public function test_cu06_documento_da_origem_chega_por_sincronizacao()
    {
        $this->incluirDocumentoExterno(self::$remetente, 'org1-database', 'arquivo_pequeno_B.pdf');

        $this->assertEquals(
            2,
            $this->contarDocumentos(self::$remetente),
            'Pre-condicao: a origem deveria ter dois documentos antes da sincronizacao.'
        );

        $this->entrarComo(self::$destinatario);
        $this->abrirProcessoEAguardarArvore(self::$processoTeste['PROTOCOLO']);
        $this->assertTrue(
            $this->paginaProcesso->validarBotaoExiste('Sincronizar Processo'),
            'CU-06: o botao Sincronizar Processo deveria estar disponivel no destino.'
        );
        $this->solicitarSincronizacaoEConfirmar();

        putenv('DATABASE_HOST=org1-database');
        $this->processarPendenciasAte(function () {
            return $this->contarDocumentosNoBanco(CONTEXTO_ORGAO_B) === 2;
        });

        $this->assertEquals(
            2,
            $this->contarDocumentos(self::$destinatario),
            'CU-06: o documento incluido na origem nao chegou ao destino apos a sincronizacao.'
        );
    }

    /**
     * CU-09: destino inclui documento e devolve o processo.
     */
    #[Depends('test_cu06_documento_da_origem_chega_por_sincronizacao')]
    public function test_cu09_destino_inclui_documento_e_devolve()
    {
        $this->incluirDocumentoExterno(self::$destinatario, 'org2-database', 'arquivo_pequeno_C.pdf');

        $this->entrarComo(self::$destinatario);
        $this->abrirProcessoEAguardarArvore(self::$processoTeste['PROTOCOLO']);

        // Tela de devolucao em modo multiplos orgaos: destino fixo (armadilha 5).
        $this->tramitarProcessoExternamenteMultiplosOrgaoDestinatario(true);

        putenv('DATABASE_HOST=org1-database');
        $this->processarPendenciasAte(function () {
            return $this->contarDocumentosNoBanco(CONTEXTO_ORGAO_A) === 3;
        });

        $this->assertEquals(
            3,
            $this->contarDocumentos(self::$remetente),
            'CU-09: o documento incluido pelo destino nao chegou a origem apos a devolucao.'
        );
    }

    /**
     * CU-10: origem inclui novo documento e o destino sincroniza de novo.
     */
    #[Depends('test_cu09_destino_inclui_documento_e_devolve')]
    public function test_cu10_ciclo_completo_com_nova_sincronizacao()
    {
        $this->incluirDocumentoExterno(self::$remetente, 'org1-database', 'arquivo_pequeno.txt');

        $this->entrarComo(self::$destinatario);
        $this->abrirProcessoEAguardarArvore(self::$processoTeste['PROTOCOLO']);
        $this->solicitarSincronizacaoEConfirmar();

        putenv('DATABASE_HOST=org1-database');
        $this->processarPendenciasAte(function () {
            return $this->contarDocumentosNoBanco(CONTEXTO_ORGAO_B) === 4;
        });

        $this->assertEquals(
            4,
            $this->contarDocumentos(self::$destinatario),
            'CU-10: o destino deveria exibir os quatro documentos apos a nova sincronizacao.'
        );
    }

    /**
     * Deixa o ambiente neutro (armadilha 2): a flag sobrevive a classe e faz os
     * testes seguintes tramitarem em modo multiplos orgaos sem pedir.
     */
    public static function tearDownAfterClass(): void
    {
        foreach (array(CONTEXTO_ORGAO_A, CONTEXTO_ORGAO_B) as $strContexto) {
            try {
                $objBanco = new DatabaseUtils($strContexto);
                $objBanco->execute('delete from md_pen_envio_comp_digitais', array());
            } catch (\Exception $e) {
                // ambiente pode nao estar disponivel no encerramento
            }
        }

        parent::tearDownAfterClass();
    }
}
