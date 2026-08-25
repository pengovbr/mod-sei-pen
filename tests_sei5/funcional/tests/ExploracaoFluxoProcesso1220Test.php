<?php

use Facebook\WebDriver\WebDriverBy;

/**
 * Exploracao funcional do FLUXO DE PROCESSO nos pontos alterados pela #1220.
 *
 * Cobre os dois cancelamentos de tramite externo:
 *
 *  BLOQ 1 - controlador_ajax.php?acao_ajax=pen_procedimento_expedir_cancelar
 *           (botao "Cancelar" da barra de progresso do envio). Ganhou
 *           validarLink() + validarPermissao() e passou a ler o id_tramite da
 *           query ASSINADA, e nao mais do corpo POST (CWE-639/CWE-862).
 *
 *  BLOQ 2 - pen_procedimento_cancelar_expedir (icone "Cancelar Tramitacao
 *           Externa" na arvore do processo). Ganhou validarPermissao() (CWE-862).
 *
 * Para cada um se verifica os dois lados: o perfil autorizado continua
 * conseguindo cancelar (sem regressao) e a chamada forjada e recusada.
 */
class ExploracaoFluxoProcesso1220Test extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $protocoloA;
    public static $protocoloB;
    /** Link AJAX assinado, capturado da propria tela de envio. */
    public static $linkAssinado = null;
    public static $respostaBotao = null;

    function setUp(): void
    {
        parent::setUp();

        foreach (array(CONTEXTO_ORGAO_A, CONTEXTO_ORGAO_B) as $contexto) {
            $banco = new DatabaseUtils($contexto);
            $banco->execute("update infra_agendamento_tarefa set sin_ativo = ? where comando = ?",
                array('N', 'PENAgendamentoRN::processarTarefasEnvioPEN'));
            $banco->execute("update infra_agendamento_tarefa set sin_ativo = ? where comando = ?",
                array('N', 'PENAgendamentoRN::processarTarefasRecebimentoPEN'));
        }
    }

    /** Conexao PDO reaproveitada entre os testes da classe. */
    private static $objBanco = null;

    /**
     * Executa SQL no banco do org1, em qualquer um dos quatro bancos.
     *
     * A versao anterior chamava o cliente `mysql` por docker exec, com
     * 2>/dev/null. Fora do MySQL isso devolvia string VAZIA em silencio -- o
     * "command not found" ia para o /dev/null -- e as assercoes recebiam zero
     * como se fosse dado do banco. Foi assim que tres testes "falharam" no SQL
     * Server sem que o produto tivesse problema algum.
     *
     * Agora vai por PDO, pelo DatabaseUtils que o proprio harness ja usa e que
     * monta o DSN certo de cada banco. Erro de banco agora estoura, em vez de
     * virar string vazia.
     *
     * Para um SELECT devolve as linhas em TSV (uma por linha), preservando o
     * contrato que os pontos de uso esperavam.
     */
    private function sql($strSql)
    {
        if (self::$objBanco === null) {
            self::$objBanco = new DatabaseUtils(CONTEXTO_ORGAO_A);
        }

        if (!preg_match('/^\s*select/i', $strSql)) {
            self::$objBanco->execute($strSql, array());
            return '';
        }

        $arrLinhas = self::$objBanco->query($strSql, array());
        if (empty($arrLinhas)) {
            return '';
        }

        $arrSaida = array();
        foreach ($arrLinhas as $arrLinha) {
            $arrValores = array();
            foreach ($arrLinha as $mixChave => $mixValor) {
                // fetchAll devolve cada coluna duas vezes (indice e nome);
                // fica so com os indices numericos.
                if (is_int($mixChave)) {
                    $arrValores[] = $mixValor === null ? 'NULL' : $mixValor;
                }
            }
            $arrSaida[] = implode("\t", $arrValores);
        }
        return implode("\n", $arrSaida) . "\n";
    }

    private function criarProcessoComDocumento()
    {
        $arrProcesso = $this->gerarDadosProcessoTeste(self::$remetente);
        $arrDocumento = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        $objProtocoloDTO = $this->cadastrarProcessoFixture($arrProcesso);
        $this->cadastrarDocumentoInternoFixture($arrDocumento, $objProtocoloDTO->getDblIdProtocolo());
        return $objProtocoloDTO->getStrProtocoloFormatado();
    }

    /**
     * BLOQ 1 (lado positivo) -- expede um processo e, enquanto a barra de
     * progresso esta na tela, captura o link assinado de cancelamento e clica no
     * botao "Cancelar". Se o AJAX corrigido tivesse quebrado (por exemplo lendo
     * um id_tramite que a URL nao carrega), o cancelamento falharia aqui.
     */
    public function test_1_cancelar_envio_pelo_botao_da_barra_de_progresso()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        self::$protocoloA = $this->criarProcessoComDocumento();

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->abrirProcesso(self::$protocoloA);

        $bolClicado = false;
        $bolTelaEnvio = false;
        // O iframe da barra de progresso e ANINHADO (nasce dentro da tela de
        // expedicao, que ja esta em ifrConteudoVisualizacao/ifrVisualizacao).
        // Por isso a troca de frame e relativa ao contexto corrente -- um
        // frame(null) antes faria a busca falhar sempre.
        $callback = function () use (&$bolClicado, &$bolTelaEnvio) {
            try {
                $this->paginaTramitar->frame('ifrEnvioProcesso');
                $bolTelaEnvio = true;

                $strFonte = self::$driver->getPageSource();

                // O link assinado e emitido pela propria pagina de expedicao.
                if (self::$linkAssinado === null
                    && preg_match('#(controlador_ajax\.php\?acao_ajax=pen_procedimento_expedir_cancelar[^\x27"]+)#',
                        $strFonte, $arrCaptura)) {
                    self::$linkAssinado = html_entity_decode($arrCaptura[1], ENT_QUOTES, 'ISO-8859-1');
                    printf("  [BLOQ 1] link assinado de cancelamento capturado da tela de envio\n");
                }

                $arrBotoes = self::$driver->findElements(WebDriverBy::id('btnCancelarEnvio'));
                if (count($arrBotoes) > 0 && $arrBotoes[0]->isDisplayed()) {
                    // Dispara exatamente a requisicao que o botao dispara (mesma
                    // URL assinada, mesmo metodo), so que com o id_tramite do
                    // CORPO POST adulterado. Se o servidor ainda lesse o POST,
                    // ele tentaria cancelar o tramite 999999999.
                    self::$driver->manage()->timeouts()->setScriptTimeout(120);
                    self::$respostaBotao = $this->postAjaxCancelamento(
                        self::$linkAssinado, '999999999');
                    $bolClicado = true;
                    return true;
                }

                // Envio concluiu antes de conseguirmos clicar no Cancelar.
                return strpos($strFonte, 'finalizado com sucesso') !== false;
            } catch (\Exception $e) {
                // Modal ainda nao abriu (ou ja fechou): tenta de novo.
                return false;
            } finally {
                try {
                    $this->paginaTramitar->frame(null);
                    $this->paginaTramitar->frame('ifrConteudoVisualizacao');
                    $this->paginaTramitar->frame('ifrVisualizacao');
                } catch (\Exception $e) {
                }
            }
        };

        try {
            $this->tramitarProcessoExternamente(
                self::$protocoloA, self::$destinatario['REP_ESTRUTURAS'],
                self::$destinatario['NOME_UNIDADE'], self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
                false, $callback, PEN_WAIT_TIMEOUT, false);
        } catch (\Exception $e) {
            printf("  [BLOQ 1] envio encerrado com: %s\n", substr($e->getMessage(), 0, 80));
        }

        $this->assertTrue($bolTelaEnvio,
            'A barra de progresso do envio nao foi alcancada -- o fluxo de expedicao quebrou');
        $this->assertTrue($bolClicado,
            'O botao Cancelar da barra de progresso nao apareceu -- caminho do AJAX nao exercitado');

        printf("  [BLOQ 1] requisicao do botao Cancelar (link assinado, POST adulterado) respondeu: %s\n",
            $this->resumir(self::$respostaBotao));

        // O endpoint devolve json_encode(cancelarTramite(...)) -- 'null' quando a
        // chamada ao barramento conclui sem excecao. Qualquer falha viria como
        // pagina de excecao do SEI, nao como null.
        $this->assertStringNotContainsString('Exce', (string) self::$respostaBotao,
            'O cancelamento pelo link assinado terminou em excecao');
        $this->assertStringContainsString('null', (string) self::$respostaBotao,
            'O cancelamento pelo link assinado nao concluiu');

        // Se o servidor ainda lesse o corpo POST, teria mirado o tramite
        // 999999999, que nao existe -- e o barramento devolveria erro, nao null.
        $this->assertStringNotContainsString('999999999', (string) self::$respostaBotao,
            'O endpoint ainda considera o id_tramite do corpo POST');

        // Observacao de estado: o processo so deixa de exibir o icone de tramite
        // externo depois que o modulo processa as pendencias, entao logo apos o
        // cancelamento ele ainda aparece marcado. Nao e criterio de aprovacao.
        printf("  [BLOQ 1] icone de tramite externo logo apos o cancelamento: %s\n",
            $this->emTramiteExterno(self::$protocoloA)
                ? 'ainda presente (some apos o processamento de pendencias)' : 'ja removido');
    }

    /**
     * BLOQ 1 (lado negativo) -- o mesmo endpoint, chamado a mao pelo navegador ja
     * autenticado. Antes da correcao ele executava o cancelamento lendo o
     * id_tramite do corpo POST, que a assinatura do link nao cobre.
     */
    public function test_2_ajax_de_cancelamento_forjado_e_recusado()
    {
        self::$protocoloB = $this->criarProcessoComDocumento();

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->abrirProcesso(self::$protocoloB);

        $this->tramitarProcessoExternamente(
            self::$protocoloB, self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'], self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false, null, PEN_WAIT_TIMEOUT, false);

        $numIdTramite = trim((string) $this->sql(
            "select max(id_tramite) from md_pen_tramite where id_tramite is not null"));
        $this->assertNotEmpty($numIdTramite, 'Nenhum tramite registrado para o teste');
        printf("  [BLOQ 1] tramite alvo do ataque simulado: %s\n", $numIdTramite);

        self::$driver->switchTo()->defaultContent();
        self::$driver->manage()->timeouts()->setScriptTimeout(120);

        // Ataque original: nenhuma assinatura, id_tramite so no corpo POST.
        $strResposta = $this->postAjaxCancelamento(
            'controlador_ajax.php?acao_ajax=pen_procedimento_expedir_cancelar', $numIdTramite);
        $bolRecusado = strpos($strResposta, 'txtUsuario') !== false
            || preg_match('/assinatura|Acesso negado/i', $strResposta);
        printf("  [BLOQ 1] chamada sem assinatura: %s\n",
            $bolRecusado
                ? 'recusada (o SEI devolveu a tela de login e encerrou a sessao)'
                : 'ACEITA -- ' . $this->resumir($strResposta));
        $this->assertTrue((bool) $bolRecusado,
            'O endpoint aceitou uma chamada sem link assinado');

        // A sessao foi derrubada pela recusa; reautentica para conferir o estado.
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $bolSegueEmTramite = $this->emTramiteExterno(self::$protocoloB);
        printf("  [BLOQ 1] estado do processo apos a chamada forjada: %s\n",
            $bolSegueEmTramite
                ? 'segue em tramite externo (cancelamento NAO ocorreu)'
                : 'FORA de tramite externo -- a chamada forjada teve efeito');
        $this->assertTrue($bolSegueEmTramite,
            'A chamada forjada chegou a cancelar o tramite');
    }

    /**
     * BLOQ 2 -- o cancelamento legitimo, pelo icone da arvore do processo, que
     * agora passa por validarPermissao('pen_procedimento_expedir'). Precisa
     * continuar funcionando para quem tem a permissao.
     */
    public function test_3_cancelar_tramitacao_externa_pela_arvore()
    {
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->abrirProcesso(self::$protocoloB);

        $this->paginaProcesso->cancelarTramitacaoExterna();
        $strAlerta = $this->paginaTramitar->alertTextAndClose(true);
        $strEsperada = mb_convert_encoding('O trâmite externo do processo foi cancelado com sucesso!',
            'UTF-8', 'ISO-8859-1');

        printf("  [BLOQ 2] cancelamento pela arvore com perfil autorizado: %s\n",
            strpos($strAlerta, $strEsperada) !== false ? 'concluido' : trim($strAlerta));

        $this->assertStringContainsString($strEsperada, $strAlerta,
            'A validarPermissao adicionada barrou quem tem direito de cancelar');
    }

    /**
     * Um processo em tramite externo exibe o icone de cancelamento na arvore.
     * O painel de informacao nao serve: ele fala da unidade, nao do tramite.
     */
    private function emTramiteExterno($strProtocolo)
    {
        self::$driver->switchTo()->defaultContent();
        $this->paginaBase->navegarParaControleProcesso();
        $this->abrirProcesso($strProtocolo);

        self::$driver->switchTo()->defaultContent();
        self::$driver->switchTo()->frame('ifrConteudoVisualizacao');
        // O seletor viaja para o chromedriver em JSON, que exige UTF-8 -- por isso
        // o literal (ISO-8859-1, como todo arquivo deste repositorio) e convertido.
        $strAlt = mb_convert_encoding('Cancelar Tramitação Externa', 'UTF-8', 'ISO-8859-1');
        $arrIcone = self::$driver->findElements(
            WebDriverBy::xpath("//img[@alt='" . $strAlt . "']"));
        self::$driver->switchTo()->defaultContent();

        return count($arrIcone) > 0;
    }

    /** POST autenticado disparado pelo proprio navegador da sessao. */
    private function postAjaxCancelamento($strUrl, $numIdTramite)
    {
        return (string) self::$driver->executeAsyncScript(
            'var done = arguments[arguments.length - 1];'
            . 'var xhr = new XMLHttpRequest();'
            . 'xhr.open("POST", arguments[0], true);'
            . 'xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");'
            . 'xhr.onload = function () { done(xhr.status + " :: " + xhr.responseText); };'
            . 'xhr.onerror = function () { done("falha de rede"); };'
            . 'xhr.send("id_tramite=" + arguments[1]);',
            array($strUrl, $numIdTramite));
    }

    private function resumir($strTexto)
    {
        $strTexto = preg_replace('/\s+/', ' ', strip_tags($strTexto));
        return substr(trim($strTexto), 0, 140);
    }
}
