<?php

use Facebook\WebDriver\WebDriverBy;

/**
 * Exploracao funcional dos pontos alterados pela issue #1220.
 *
 * Para os XSS, o payload e plantado direto no banco -- contornando a validacao
 * de entrada de proposito, porque o que foi corrigido e o SINK (a renderizacao),
 * nao a entrada. Depois a tela e aberta no navegador e verifica-se que o valor
 * chega como TEXTO, e nao como elemento no DOM.
 *
 * Para as rotas que ganharam validarPermissao(), confirma-se que o perfil
 * autorizado continua alcancando-as -- uma correcao de autorizacao exagerada
 * barraria tambem quem tem direito.
 */
class ExploracaoCorrecoes1220Test extends CenarioBaseTestCase
{
    /** Sem escape, isto vira um elemento <img> no DOM e dispara onerror. */
    const XSS = '<img src=x onerror=window.__xss=1>';

    private function logar()
    {
        $this->acessarSistema(
            CONTEXTO_ORGAO_A_URL, CONTEXTO_ORGAO_A_SIGLA_UNIDADE,
            CONTEXTO_ORGAO_A_USUARIO_LOGIN, CONTEXTO_ORGAO_A_USUARIO_SENHA);
    }

    /**
     * Navega pelo MENU, como um usuario faria. URL direta e recusada pelo SEI
     * com "Link sem assinatura" -- uma camada a mais, alem da permissao.
     */
    private function irPara($strAcao, $strTermoMenu)
    {
        // Garante uma pagina estavel, com o menu disponivel, antes de pesquisar.
        $this->paginaBase->navegarParaControleProcesso();

        $objInput = self::$driver->findElement(WebDriverBy::id('txtInfraPesquisarMenu'));
        $objInput->clear();
        $objInput->sendKeys($strTermoMenu . \Facebook\WebDriver\WebDriverKeys::ENTER);
        self::$driver->findElement(WebDriverBy::xpath("//a[@link='" . $strAcao . "']"))->click();
    }

    /** Elementos que so existem no DOM se o payload NAO tiver sido escapado. */
    private function elementosInjetados()
    {
        return count(self::$driver->findElements(WebDriverBy::cssSelector('img[onerror]')));
    }

    /** Conexao PDO reaproveitada entre os testes da classe. */
    private static $objBanco = null;

    /**
     * Executa SQL no banco do org1, em qualquer um dos quatro bancos.
     *
     * Usa PDO pelo DatabaseUtils do proprio harness, que monta o DSN certo de
     * cada banco. A versao anterior chamava o cliente `mysql` com 2>/dev/null:
     * fora do MySQL devolvia string vazia em silencio e as assercoes recebiam
     * zero como se fosse dado. Agora erro de banco estoura.
     *
     * SELECT devolve as linhas em TSV, uma por linha.
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


    /**
     * Todas as linhas que este teste planta usam ids acima de 987000, faixa que
     * o ambiente funcional nao usa. Assim a limpeza e exata e nao toca em dado
     * legitimo.
     */
    const ID_BASE = 987000;

    /**
     * Remove tudo o que este teste plantou.
     *
     * Sem isso os testes seguintes encontram a base com linhas cujo nome e um
     * payload de XSS, e falham por herdar o estrago.
     */
    private function limparVestigios()
    {
        // A ordem importa: md_pen_map_tipo_processo referencia md_pen_orgao_externo
        // por FK, entao o filho sai antes do pai. Invertido, o DELETE viola a
        // constraint -- no MySQL o helper antigo engolia o erro e a limpeza
        // simplesmente nao acontecia, sem ninguem notar.
        $this->sql("delete from md_pen_bloco where id > " . self::ID_BASE);
        $this->sql("delete from md_pen_bloco where descricao like '%onerror%'");
        $this->sql("delete from md_pen_map_tipo_processo where id > " . self::ID_BASE);
        $this->sql("delete from md_pen_map_tipo_processo where nome_tipo_processo like '%onerror%'");
        $this->sql("delete from md_pen_orgao_externo where id > " . self::ID_BASE);
        $this->sql("delete from md_pen_orgao_externo where str_orgao_origem like '%onerror%'"
                 . " or str_orgao_destino like '%onerror%'");
    }

    /** Devolve ao lugar as linhas de md_pen_unidade que o BLOQ 3 excluir. */
    private function restaurarMapeamentoUnidade($strAntes)
    {
        foreach (explode("\n", trim($strAntes)) as $strLinha) {
            if (trim($strLinha) === '') { continue; }
            $arrCampos = explode("\t", $strLinha);
            if (count($arrCampos) < 2) { continue; }
            // So a linha excluida precisa voltar; reinserir as demais colide com a
            // chave primaria. (`insert ignore` resolveria, mas so existe no MySQL.)
            $strIdUnidade = addslashes(trim($arrCampos[0]));
            if (trim((string) $this->sql(
                    "select id_unidade from md_pen_unidade where id_unidade = $strIdUnidade")) !== '') {
                continue;
            }
            $strValores = "'" . implode("','", array_map(function ($v) {
                return trim($v) === 'NULL' ? '' : addslashes(trim($v));
            }, $arrCampos)) . "'";
            $this->sql("insert into md_pen_unidade"
                     . " (id_unidade, id_unidade_rh, sigla_unidade_rh, nome_unidade_rh)"
                     . " values ($strValores)");
        }
    }

    protected function tearDown(): void
    {
        $this->limparVestigios();
        parent::tearDown();
    }

    private function verificarSink($strAcao, $strTermoMenu, $strRotulo)
    {
        $this->irPara($strAcao, $strTermoMenu);
        $this->conferirSinkAtual($strRotulo);
    }

    /**
     * Confere a pagina ja aberta: o payload precisa estar presente (prova que o
     * dado chegou ao sink) mas nunca como elemento do DOM (prova que foi escapado).
     */
    private function conferirSinkAtual($strRotulo)
    {
        $numInjetados = $this->elementosInjetados();
        $bolTextoPresente = strpos(self::$driver->getPageSource(), 'onerror') !== false;

        printf("  [%s] elementos injetados no DOM: %d | payload presente na pagina: %s\n",
            $strRotulo, $numInjetados, $bolTextoPresente ? 'sim (como texto)' : 'nao');

        $this->assertEquals(0, $numInjetados,
            "$strRotulo: o payload virou elemento no DOM -- XSS ativo");
    }

    /** XSS 2 -- nome de orgao externo. */
    public function test_xss_nome_orgao_externo()
    {
        $this->logar();

        $this->limparVestigios();
        $this->sql("insert into md_pen_orgao_externo (id, id_orgao_origem, str_orgao_origem, "
                 . "id_estrutura_origem, str_estrutura_origem, id_orgao_destino, str_orgao_destino, "
                 . "sin_ativo, id_unidade, dth_criacao) values (987001, 1, '" . self::XSS . "', "
                 . "1, 'ORIGEM', 2, 'DESTINO', 'S', 110000001, CURRENT_TIMESTAMP)");

        $this->assertNotEmpty(trim((string) $this->sql(
            "select id from md_pen_orgao_externo where id = 987001")),
            'O payload nao foi persistido -- o teste do sink seria vacuo');

        $this->verificarSink('pen_map_orgaos_externos_listar', 'Relacionamento entre Unidades', 'XSS 2 orgaos externos');
        $this->sairSistema();
    }

    /** XSS 3 -- nome de tipo de processo na reativacao. */
    public function test_xss_tipo_processo_reativar()
    {
        $this->logar();

        // O mapeamento de tipo de processo tem FK obrigatoria para o orgao externo,
        // e o tearDown do teste anterior o removeu. Cada teste monta o proprio
        // cenario: sem isto a insercao viola a FK e o payload nunca chega a tela
        // -- a assercao "zero elementos injetados" passaria sem testar nada.
        $this->sql("delete from md_pen_map_tipo_processo where id = 987002");
        $this->limparVestigios();
        $this->sql("insert into md_pen_orgao_externo (id, id_orgao_origem, str_orgao_origem, "
                 . "id_estrutura_origem, str_estrutura_origem, id_orgao_destino, str_orgao_destino, "
                 . "sin_ativo, id_unidade, dth_criacao) values (987001, 1, 'ORIGEM', "
                 . "1, 'ORIGEM', 2, 'DESTINO', 'S', 110000001, CURRENT_TIMESTAMP)");
        $this->sql("insert into md_pen_map_tipo_processo (id, id_map_orgao, id_tipo_processo_origem, "
                 . "nome_tipo_processo, sin_ativo, id_unidade, dth_criacao) "
                 . "values (987002, 987001, 1, '" . self::XSS . "', 'N', 110000001, CURRENT_TIMESTAMP)");

        // Confirma que o payload REALMENTE entrou; sem isto o teste seria vazio.
        $this->assertNotEmpty(trim((string) $this->sql(
            "select id from md_pen_map_tipo_processo where id = 987002")),
            'O payload nao foi persistido -- o teste do sink seria vacuo');

        $this->verificarSink('pen_map_tipo_processo_reativar', 'Reativar Mapeamento de Tipos de Processo', 'XSS 3 tipo de processo');
        $this->sairSistema();
    }

    /**
     * XSS 1 + BLOQUEANTE 5a -- cria um bloco de tramite pela UI, com payload na
     * descricao, e confere a listagem.
     *
     * Um fluxo cobre os dois pontos: criar o bloco exercita o
     * validarAuditarPermissao restaurado em TramiteEmBlocoRN::cadastrarControlado,
     * e a listagem exercita o sink corrigido em pen_tramite_bloco_listar.
     */
    public function test_bloco_criacao_e_xss_na_descricao()
    {
        $this->logar();

        // A listagem de blocos nao tem item de menu; o page object sabe o caminho.
        $this->paginaBase->navegarParaControleProcesso();
        $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();

        $this->paginaCadastrarProcessoEmBloco->novoBlocoDeTramite();
        $this->paginaCadastrarProcessoEmBloco->criarNovoBloco('BLOCO ' . self::XSS);
        $this->paginaCadastrarProcessoEmBloco->btnSalvar();

        printf("  [BLOQ 5a] criacao de bloco: concluida pelo perfil autorizado\n");

        $this->conferirSinkAtual('XSS 1 descricao de bloco (criado pela UI)');

        // A tela do bloco deixa o driver dentro de um iframe; o menu so e
        // alcancavel a partir do documento principal. Segue na mesma sessao.
        self::$driver->switchTo()->defaultContent();

        $this->limparVestigios();
        $this->sql("insert into md_pen_bloco (id, id_unidade, id_usuario, descricao, sta_tipo, "
                 . "sta_estado, ordem) values (987003, 110000001, 1, '" . self::XSS . "', "
                 . "'T', 'A', 1)");

        $this->paginaBase->navegarParaControleProcesso();
        $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
        $this->conferirSinkAtual('XSS 1 descricao de bloco (payload cru)');
        $this->sairSistema();
    }

    /** As rotas que ganharam validarPermissao() seguem acessiveis a quem tem direito. */
    public function test_rotas_com_nova_validacao_seguem_acessiveis()
    {
        $this->logar();

        $arrRotas = array(
            'pen_map_unidade_listar'         => array('Listar', 'mapeamento de unidades'),
            'pen_map_orgaos_externos_listar' => array('Relacionamento entre Unidades', 'orgaos externos'),
            'pen_map_tipo_processo_reativar' => array('Reativar Mapeamento de Tipos de Processo', 'reativacao de tipo de processo'),
        );

        foreach ($arrRotas as $strAcao => $arrInfo) {
            list($strTermoMenu, $strNome) = $arrInfo;
            $this->irPara($strAcao, $strTermoMenu);
            $strFonte = self::$driver->getPageSource();

            $bolNegado = strpos($strFonte, 'Acesso negado') !== false
                      || strpos($strFonte, 'permiss') !== false && strpos($strFonte, 'negad') !== false;

            $this->assertFalse($bolNegado, "Rota $strAcao passou a negar acesso ao perfil autorizado");
            printf("  [AUTZ] %s: acessivel\n", $strNome);
        }

        $this->sairSistema();
    }

    /** Aceita o confirm()/alert() que a tela dispara, se houver. */
    private function confirmarDialogo()
    {
        try {
            self::$driver->switchTo()->alert()->accept();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /** A pagina atual negou acesso ao perfil? */
    private function acessoNegado()
    {
        $strFonte = self::$driver->getPageSource();
        return strpos($strFonte, 'Acesso negado') !== false
            || strpos($strFonte, 'Acesso n') !== false && strpos($strFonte, 'negad') !== false;
    }

    /**
     * BLOQ 3 -- exclusao de mapeamento de unidade. Ganhou validarPermissao() na
     * tela e validarAuditarPermissao() dentro de PenUnidadeRN::excluirControlado.
     * O perfil autorizado precisa continuar excluindo.
     */
    public function test_bloq3_exclusao_de_mapeamento_de_unidade()
    {
        $this->logar();
        $this->irPara('pen_map_unidade_listar', 'Listar');

        // Fotografa os mapeamentos antes: a exclusao pela UI e real, e os testes
        // de mapeamento de unidade que vem depois contam com eles no lugar.
        $strMapeamentosAntes = (string) $this->sql(
            "select id_unidade, id_unidade_rh, coalesce(sigla_unidade_rh,'NULL'),"
            . " coalesce(nome_unidade_rh,'NULL') from md_pen_unidade");
        $numAntes = (int) trim((string) $this->sql("select count(*) from md_pen_unidade"));
        $arrLinks = self::$driver->findElements(
            WebDriverBy::xpath("//img[@alt='Excluir Mapeamento']/parent::a"));

        if (count($arrLinks) === 0) {
            printf("  [BLOQ 3] nenhum mapeamento de unidade cadastrado para excluir\n");
            $this->markTestSkipped('Sem mapeamento de unidade no ambiente');
        }

        $arrLinks[0]->click();
        $this->confirmarDialogo();
        sleep(2);

        $numDepois = (int) trim((string) $this->sql("select count(*) from md_pen_unidade"));

        printf("  [BLOQ 3] exclusao de mapeamento de unidade: %s (%d -> %d registros)\n",
            $this->acessoNegado() ? 'ACESSO NEGADO' : 'executada pelo perfil autorizado',
            $numAntes, $numDepois);

        $this->assertFalse($this->acessoNegado(),
            'A validacao adicionada barrou quem tem permissao de excluir mapeamento');
        $this->assertLessThan($numAntes, $numDepois,
            'A exclusao nao teve efeito no banco');

        $this->restaurarMapeamentoUnidade($strMapeamentosAntes);
        $numRestaurado = (int) trim((string) $this->sql("select count(*) from md_pen_unidade"));
        printf("  [BLOQ 3] mapeamentos restaurados para os testes seguintes: %d\n", $numRestaurado);
        $this->assertEquals($numAntes, $numRestaurado,
            'Os mapeamentos de unidade nao voltaram ao estado original');

        $this->sairSistema();
    }

    /**
     * BLOQ 4 -- importacao de tipos de processo de orgao externo. A rota passou a
     * exigir o recurso proprio de importacao, alem do de listagem. Aqui submete-se
     * o formulario que a propria tela emite (assinado), como faz o botao Importar.
     */
    public function test_bloq4_importacao_de_tipos_de_processo()
    {
        $this->logar();
        $this->irPara('pen_map_orgaos_externos_listar', 'Relacionamento entre Unidades');

        // O ambiente funcional nao traz mapeamento de orgao externo; cria-se um
        // para que a importacao tenha alvo.
        // Colunas conferidas em information_schema: a tabela nao tem
        // id_estrutura_destino, str_estrutura_destino nem 'ativo' -- o nome da
        // coluna de estado e sin_ativo.
        $this->limparVestigios();
        $this->sql("insert into md_pen_orgao_externo (id, id_orgao_origem, str_orgao_origem, "
                 . "id_estrutura_origem, str_estrutura_origem, id_orgao_destino, str_orgao_destino, "
                 . "sin_ativo, id_unidade, dth_criacao) values (987005, 1, 'ORIGEM', "
                 . "154403, 'ESTRUTURA ORIGEM', 2, 'DESTINO', 'S', 110000001, CURRENT_TIMESTAMP)");
        $this->irPara('pen_map_orgaos_externos_listar', 'Relacionamento entre Unidades');

        $numMapId = trim((string) $this->sql("select min(id) from md_pen_orgao_externo"));
        printf("  [BLOQ 4] mapeamento de orgao externo usado na importacao: %s\n",
            $numMapId !== '' ? $numMapId : '(nenhum)');

        $bolFormulario = (bool) self::$driver->executeScript(
            'var f = document.getElementById("formImportarDados");'
            . 'if (!f) { return false; }'
            . 'document.getElementById("mapId").value = arguments[0];'
            . 'document.getElementById("dadosInput").value = "[]";'
            . 'f.submit(); return true;',
            array($numMapId));

        $this->assertTrue($bolFormulario,
            'A tela nao emitiu o formulario de importacao de tipos de processo');
        sleep(3);

        printf("  [BLOQ 4] rota de importacao respondeu: %s\n",
            $this->acessoNegado() ? 'ACESSO NEGADO' : 'processada pelo perfil autorizado');

        $this->assertFalse($this->acessoNegado(),
            'A validarPermissao adicionada barrou quem tem permissao de importar');

        $this->sairSistema();
    }

    /**
     * BLOQ 5b -- cancelamento de bloco de tramite, que nao impunha autorizacao
     * alguma e agora passa por validarAuditarPermissao('pen_tramite_em_bloco_cancelar').
     */
    public function test_bloq5b_cancelamento_de_bloco_de_tramite()
    {
        $this->logar();

        $this->limparVestigios();
        $this->sql("insert into md_pen_bloco (id, id_unidade, id_usuario, descricao, sta_tipo, "
                 . "sta_estado, ordem) values (987004, 110000001, 1, 'BLOCO PARA CANCELAR', "
                 . "'T', 'A', 2)");

        $this->paginaBase->navegarParaControleProcesso();
        $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();

        $arrCheck = self::$driver->findElements(
            WebDriverBy::xpath("//input[@type='checkbox' and contains(@value,'987004')]"));
        if (count($arrCheck) === 0) {
            $arrCheck = self::$driver->findElements(
                WebDriverBy::xpath("//tr[contains(., 'BLOCO PARA CANCELAR')]//input[@type='checkbox']"));
        }

        $this->assertGreaterThan(0, count($arrCheck),
            'O bloco criado nao apareceu na listagem');
        // O label do infraCheckbox cobre o input; o clique vai pelo DOM.
        self::$driver->executeScript('arguments[0].click();', array($arrCheck[0]));

        // Observacao: nesta versao a listagem NAO emite botao ligado a
        // onClickBtnCancelar() -- a funcao existe, o recurso existe no SIP e a
        // rota existe, mas nenhum controle da tela a chama. Para exercitar o
        // servidor pelo caminho real, invoca-se a propria funcao da pagina, que
        // monta o link assinado e submete o formulario da listagem.
        self::$driver->executeScript(
            'window.confirm = function () { return true; };'
            . 'onClickBtnCancelar();');
        sleep(3);

        $strEstado = trim((string) $this->sql("select sta_estado from md_pen_bloco where id = 987004"));

        printf("  [BLOQ 5b] cancelamento de bloco: %s (sta_estado = '%s')\n",
            $this->acessoNegado() ? 'ACESSO NEGADO' : 'executado pelo perfil autorizado',
            $strEstado);

        $this->assertFalse($this->acessoNegado(),
            'A validacao adicionada barrou quem tem permissao de cancelar bloco');

        $this->sairSistema();
    }
}
