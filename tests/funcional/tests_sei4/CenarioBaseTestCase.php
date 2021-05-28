<?php

use \utilphp\util;
use PHPUnit\Extensions\Selenium2TestCase;

use function PHPSTORM_META\map;

/**
* Classe base contendo rotinas comuns utilizadas nos casos de teste do módulo
*/
class CenarioBaseTestCase extends Selenium2TestCase
{
    const PASTA_ARQUIVOS_TESTE = "/tmp";

    //Referência para unidades que serão consideradas no fluxo de trâmite (Remetente -> Destinatário)
    protected static $urlSistemaRemetente = null;
    protected static $siglaOrgaoRemetente = null;
    protected static $siglaUnidadeRemetente = null;
    protected static $nomeUnidadeRemetente = null;

    protected static $urlSistemaDestinatario = null;
    protected static $siglaOrgaoDestinatario = null;
    protected static $siglaUnidadeDestinatario = null;
    protected static $nomeUnidadeDestinatario = null;

    //Referências para as páginas do SEI utilizadas nos cenarios de teste
    protected $paginaBase = null;
    protected $paginaProcesso = null;
    protected $paginaTramitar = null;
    protected $paginaDocumento = null;
    protected $paginaReciboTramite = null;
    protected $paginaEditarProcesso = null;
    protected $paginaControleProcesso = null;
    protected $paginaConsultarAndamentos = null;
    protected $paginaAssinaturaDocumento = null;
    protected $paginaIncluirDocumento = null;
    protected $paginaProcessosTramitadosExternamente = null;
    protected $paginaAnexarProcesso = null;
    protected $paginaCancelarDocumento = null;

    public function setUpPage(): void
    {
        $this->paginaBase = new PaginaTeste($this);
        $this->paginaDocumento = new PaginaDocumento($this);
        $this->paginaAssinaturaDocumento = new PaginaAssinaturaDocumento($this);
        $this->paginaProcesso = new PaginaProcesso($this);
        $this->paginaTramitar = new PaginaTramitarProcesso($this);
        $this->paginaReciboTramite = new PaginaReciboTramite($this);
        $this->paginaConsultarAndamentos = new PaginaConsultarAndamentos($this);
        $this->paginaProcessosTramitadosExternamente = new PaginaProcessosTramitadosExternamente($this);
        $this->paginaControleProcesso = new PaginaControleProcesso($this);
        $this->paginaIncluirDocumento = new PaginaIncluirDocumento($this);
        $this->paginaEditarProcesso = new PaginaEditarProcesso($this);
        $this->paginaAnexarProcesso = new PaginaAnexarProcesso($this);
        $this->paginaCancelarDocumento = new PaginaCancelarDocumento($this);
        $this->paginaMoverDocumento = new PaginaMoverDocumento($this);
        $this->currentWindow()->maximize();
    }

    public static function setUpBeforeClass(): void
    {
        //TODO: Migrar todo o código abaixo para uma classe utilitária de configuração dos testes
        /***************** CONFIGURAÇÃO PRELIMINAR DO ÓRGÃO 1 *****************/
        $parametrosOrgaoA = new ParameterUtils(CONTEXTO_ORGAO_A);
        $parametrosOrgaoA->setParameter('PEN_ID_REPOSITORIO_ORIGEM', CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS);
        $parametrosOrgaoA->setParameter('PEN_TIPO_PROCESSO_EXTERNO', '100000256');
        $parametrosOrgaoA->setParameter('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO', '110000003');
        $parametrosOrgaoA->setParameter('HIPOTESE_LEGAL_PADRAO', '1'); // Controle Interno

        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);
        // Configuração do mapeamento de unidades
        $bancoOrgaoA->execute("insert into md_pen_unidade(id_unidade, id_unidade_rh) values (?, ?)", array('110000001', CONTEXTO_ORGAO_A_ID_ESTRUTURA));
        $bancoOrgaoA->execute("insert into md_pen_unidade(id_unidade, id_unidade_rh) values (?, ?)", array('110000002', CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA));
        // Configuração do prefíxo de processos
        $bancoOrgaoA->execute("update orgao set codigo_sei=? where sigla=?", array(CONTEXTO_ORGAO_A_NUMERO_SEI, CONTEXTO_ORGAO_A_SIGLA_ORGAO));
        $bancoOrgaoA->execute("update unidade set sin_protocolo=? where sigla=?", array('S', CONTEXTO_ORGAO_A_SIGLA_UNIDADE));
        $bancoOrgaoA->execute("update infra_agendamento_tarefa set parametro='debug=true' where comando='PENAgendamentoRN::processarTarefasPEN'", null);

        // Remoção de mapeamento de espécie não mapeada na origem
        $nomeSerieNaoMapeada = utf8_encode(CONTEXTO_ORGAO_A_TIPO_DOCUMENTO_NAO_MAPEADO);
        $serieNaoMapeadaOrigem = $bancoOrgaoA->query('select ID_SERIE from serie where nome = ?', array($nomeSerieNaoMapeada));
        $bancoOrgaoA->execute("delete from md_pen_rel_doc_map_enviado where id_serie = ?", array($serieNaoMapeadaOrigem[0]["ID_SERIE"]));
        $bancoOrgaoA->execute("insert into md_pen_rel_hipotese_legal(id_mapeamento, id_hipotese_legal, id_hipotese_legal_pen, tipo, sin_ativo) values (?, ?, ?, ?, ?)", array(1, 3, 3, 'E', 'S'));
        $bancoOrgaoA->execute("insert into md_pen_rel_hipotese_legal(id_mapeamento, id_hipotese_legal, id_hipotese_legal_pen, tipo, sin_ativo) values (?, ?, ?, ?, ?)", array(2, 4, 4, 'E', 'S'));
        $bancoOrgaoA->execute("insert into md_pen_rel_hipotese_legal(id_mapeamento, id_hipotese_legal, id_hipotese_legal_pen, tipo, sin_ativo) values (?, ?, ?, ?, ?)", array(3, 3, 3, 'R', 'S'));
  
        $bancoOrgaoA->execute("update infra_parametro set valor = ? where nome = ?", array(50, 'SEI_TAM_MB_DOC_EXTERNO'));

        // Habilitação da extensão docx
        $bancoOrgaoA->execute("update arquivo_extensao set sin_ativo=? where extensao=?", array('S', 'docx'));


        /***************** CONFIGURAÇÃO PRELIMINAR DO ÓRGÃO 2 *****************/
        $parametrosOrgaoB = new ParameterUtils(CONTEXTO_ORGAO_B);
        $parametrosOrgaoB->setParameter('PEN_ID_REPOSITORIO_ORIGEM', CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS);
        $parametrosOrgaoB->setParameter('PEN_TIPO_PROCESSO_EXTERNO', '100000256');
        $parametrosOrgaoB->setParameter('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO', '110000003');
        $parametrosOrgaoB->setParameter('HIPOTESE_LEGAL_PADRAO', '1'); // Controle Interno

        $bancoOrgaoB = new DatabaseUtils(CONTEXTO_ORGAO_B);
        $bancoOrgaoB->execute("insert into md_pen_unidade(id_unidade, id_unidade_rh) values ('110000001', ?)", array(CONTEXTO_ORGAO_B_ID_ESTRUTURA));
        $bancoOrgaoB->execute("update orgao set codigo_sei=? where sigla=?", array(CONTEXTO_ORGAO_B_NUMERO_SEI, CONTEXTO_ORGAO_B_SIGLA_ORGAO));
        $bancoOrgaoB->execute("update unidade set sin_protocolo=? where sigla=?", array('S', CONTEXTO_ORGAO_B_SIGLA_UNIDADE));
        $bancoOrgaoB->execute("update infra_agendamento_tarefa set parametro='debug=true' where comando='PENAgendamentoRN::processarTarefasPEN'", null);
        $bancoOrgaoB->execute("update infra_parametro set valor = ? where nome = ?", array(50, 'SEI_TAM_MB_DOC_EXTERNO'));

        // Remoção de mapeamento de espécie não mapeada na origem
        $nomeSerieNaoMapeada = utf8_encode(CONTEXTO_ORGAO_B_TIPO_DOCUMENTO_NAO_MAPEADO);
        $serieNaoMapeadaOrigem = $bancoOrgaoB->query('select ID_SERIE from serie where nome = ?', array($nomeSerieNaoMapeada));
        $bancoOrgaoB->execute("delete from md_pen_rel_doc_map_recebido where id_serie = ?", array($serieNaoMapeadaOrigem[0]["ID_SERIE"]));
        $bancoOrgaoB->execute("insert into md_pen_rel_hipotese_legal(id_mapeamento, id_hipotese_legal, id_hipotese_legal_pen, tipo, sin_ativo) values (?, ?, ?, ?, ?);", array(4, 3, 3, 'E', 'S'));
        $bancoOrgaoB->execute("insert into md_pen_rel_hipotese_legal(id_mapeamento, id_hipotese_legal, id_hipotese_legal_pen, tipo, sin_ativo) values (?, ?, ?, ?, ?);", array(5, 3, 3, 'R', 'S'));

        $bancoOrgaoB->execute("update infra_parametro set valor = ? where nome = ?", array(50, 'SEI_TAM_MB_DOC_EXTERNO'));

        //para corrigir o erro do oracle que retorna stream sem acentuação das palavras no teste de URL

        if ($bancoOrgaoA->getBdType()=="oci") {
            $result=$bancoOrgaoA->query("SELECT texto FROM tarja_assinatura where sta_tarja_assinatura=? and sin_ativo=?", array("V","S"));
            $strTarja=stream_get_contents($result[0]["TEXTO"]);
            $bancoOrgaoA->execute("update tarja_assinatura set texto=? where sta_tarja_assinatura=? and sin_ativo=?", array($strTarja,"V","S"));
        }
        

    }

    public static function tearDownAfterClass(): void
    {

    }

    public function setUp(): void
    {
        $this->setHost(PHPUNIT_HOST);
        $this->setPort(intval(PHPUNIT_PORT));
        $this->setBrowser(PHPUNIT_BROWSER);
        $this->setBrowserUrl(PHPUNIT_TESTS_URL);
        $this->setDesiredCapabilities(
            array(
                'platform' => 'LINUX',
                'chromeOptions' => array(
                    'w3c' => false,
                    'args' => [
                        '--profile-directory=' . uniqid(),
                        '--disable-features=TranslateUI',
                        '--disable-translate',
                    ],
                )
            )
        );
    }

    protected function definirRemetenteProcesso($urlSistema, $siglaOrgao, $siglaUnidade, $nomeUnidade)
    {
        self::$urlSistemaRemetente = $urlSistema;
        self::$siglaOrgaoRemetente =  $siglaOrgao;
        self::$siglaUnidadeRemetente = $siglaUnidade;
        self::$nomeUnidadeRemetente = $nomeUnidade;
    }

    protected function definirDestinatarioProcesso($urlSistema, $siglaOrgao, $siglaUnidade, $nomeUnidade)
    {
        self::$urlSistemaDestinatario = $urlSistema;
        self::$siglaOrgaoDestinatario =  $siglaOrgao;
        self::$siglaUnidadeDestinatario = $siglaUnidade;
        self::$nomeUnidadeDestinatario = $nomeUnidade;
    }

    protected function definirContextoTeste($nomeContexto)
    {
        return array(
            'URL' => constant($nomeContexto . '_URL'),
            'ORGAO' => constant($nomeContexto . '_SIGLA_ORGAO'),
            'SIGLA_UNIDADE' =>constant($nomeContexto . '_SIGLA_UNIDADE'),
            'SIGLA_UNIDADE_HIERARQUIA' => constant($nomeContexto . '_SIGLA_UNIDADE_HIERARQUIA'),
            'NOME_UNIDADE' => constant($nomeContexto . '_NOME_UNIDADE'),
            'LOGIN' => constant($nomeContexto . '_USUARIO_LOGIN'),
            'SENHA' => constant($nomeContexto . '_USUARIO_SENHA'),
            'TIPO_PROCESSO' => constant($nomeContexto . '_TIPO_PROCESSO'),
            'TIPO_DOCUMENTO' => constant($nomeContexto . '_TIPO_DOCUMENTO'),
            'TIPO_DOCUMENTO_NAO_MAPEADO' => constant($nomeContexto . '_TIPO_DOCUMENTO_NAO_MAPEADO'),
            'CARGO_ASSINATURA' => constant($nomeContexto . '_CARGO_ASSINATURA'),
            'SIGLA_UNIDADE_HIERARQUIA' => constant($nomeContexto . '_SIGLA_UNIDADE_HIERARQUIA'),
            'SIGLA_UNIDADE_SECUNDARIA' => constant($nomeContexto . '_SIGLA_UNIDADE_SECUNDARIA'),
            'SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA' => constant($nomeContexto . '_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA'),
            'NOME_UNIDADE_SECUNDARIA' => constant($nomeContexto . '_NOME_UNIDADE_SECUNDARIA'),
            'HIPOTESE_RESTRICAO' => constant($nomeContexto . '_HIPOTESE_RESTRICAO'),
            'HIPOTESE_RESTRICAO_NAO_MAPEADO' => constant($nomeContexto . '_HIPOTESE_RESTRICAO_NAO_MAPEADO'),
            'REP_ESTRUTURAS' => constant($nomeContexto . '_REP_ESTRUTURAS'),
            'HIPOTESE_RESTRICAO_PADRAO' => constant($nomeContexto . '_HIPOTESE_RESTRICAO_PADRAO'),
            'LOCALIZACAO_CERTIFICADO_DIGITAL' => realpath(__DIR__ . constant($nomeContexto . '_LOCALIZACAO_CERTIFICADO_DIGITAL')),
            'SENHA_CERTIFICADO_DIGITAL' => constant($nomeContexto . '_SENHA_CERTIFICADO_DIGITAL'),
            'ID_REP_ESTRUTURAS' => constant($nomeContexto . '_ID_REP_ESTRUTURAS'),
            'ID_ESTRUTURA' => constant($nomeContexto . '_ID_ESTRUTURA'),
        );
    }

    protected function acessarSistema($url, $siglaUnidade, $login, $senha)
    {
        $this->url($url);
        PaginaLogin::executarAutenticacao($this, $login, $senha);
        PaginaTeste::selecionarUnidadeContexto($this, $siglaUnidade);
    }

    protected function selecionarUnidadeInterna($unidadeDestino)
    {
        PaginaTeste::selecionarUnidadeContexto($this, $unidadeDestino);
    }

    protected function sairSistema()
    {
        $this->paginaBase->sairSistema();
    }

    protected function cadastrarProcesso(&$dadosProcesso)
    {
        $this->paginaBase->navegarParaControleProcesso();
        $protocoloGerado = PaginaIniciarProcesso::gerarProcessoTeste($this, $dadosProcesso);
        $dadosProcesso['PROTOCOLO'] = $protocoloGerado;
        sleep(2);
        return $protocoloGerado;
    }

    protected function abrirProcesso($protocolo)
    {
        $this->paginaBase->navegarParaControleProcesso();
        $this->paginaControleProcesso->abrirProcesso($protocolo);
    }

    protected function abrirProcessoPelaDescricao($descricao)
    {
        $this->paginaBase->navegarParaControleProcesso();
        $protocolo = $this->paginaControleProcesso->localizarProcessoPelaDescricao($descricao);
        if($protocolo){
            $this->paginaControleProcesso->abrirProcesso($protocolo);
        }
        return $protocolo;
    }

    protected function cadastrarDocumentoInterno($dadosDocumentoInterno)
    {
        $this->paginaProcesso->selecionarProcesso();
        $this->paginaIncluirDocumento->gerarDocumentoTeste($dadosDocumentoInterno);
        sleep(2);
    }

    protected function cadastrarDocumentoExterno($dadosDocumentoExterno)
    {
        $this->paginaProcesso->selecionarProcesso();
        $this->paginaIncluirDocumento->gerarDocumentoExternoTeste($dadosDocumentoExterno);
        sleep(2);
    }

    protected function assinarDocumento($siglaOrgao, $cargoAssinante, $loginSenha)
    {
        // Navegar para página de assinatura
        $this->paginaDocumento->navegarParaAssinarDocumento();
        sleep(2);

        // Assinar documento
        $this->paginaAssinaturaDocumento->selecionarOrgaoAssinante($siglaOrgao);
        $this->paginaAssinaturaDocumento->selecionarCargoAssinante($cargoAssinante);
        $this->paginaAssinaturaDocumento->assinarComLoginSenha($loginSenha);
        $this->window('');
        sleep(2);
    }

    protected function anexarProcesso($protocoloProcessoAnexado)
    {
        $this->paginaProcesso->navegarParaAnexarProcesso();
        $this->paginaAnexarProcesso->anexarProcesso($protocoloProcessoAnexado);
    }

    protected function tramitarProcessoExternamente($protocolo, $repositorio, $unidadeDestino, $unidadeDestinoHierarquia, $urgente=false, $callbackEnvio=null, $timeout=PEN_WAIT_TIMEOUT)
    {
        // Acessar funcionalidade de trâmite externo
        $this->paginaProcesso->navegarParaTramitarProcesso();

        // Preencher parâmetros do trâmite
        $this->paginaTramitar->repositorio($repositorio);
        $this->paginaTramitar->unidade($unidadeDestino, $unidadeDestinoHierarquia);
        $this->paginaTramitar->tramitar();

        if($callbackEnvio == null){
            $mensagemAlerta = null;
            try{
                $mensagemAlerta = $this->paginaTramitar->alertTextAndClose(true);
            } catch(Exception $e){}
            if($mensagemAlerta){
                throw new Exception($mensagemAlerta);
            }
        }

        try{ $mensagemAlerta = $this->paginaTramitar->alertTextAndClose(true); }
        catch(Exception $e){ }

        if(isset($mensagemAlerta)){
            throw new Exception($mensagemAlerta);
        }

        $callbackEnvio = $callbackEnvio ?: function($testCase) {
            try {
                $testCase->frame('ifrEnvioProcesso');
                $mensagemSucesso = utf8_encode('Trâmite externo do processo finalizado com sucesso!');
                $testCase->assertStringContainsString($mensagemSucesso, $testCase->byCssSelector('body')->text());
                $btnFechar = $testCase->byXPath("//input[@id='btnFechar']");
                $btnFechar->click();
            } finally {
                $testCase->frame(null);
                $testCase->frame("ifrVisualizacao");
            }

            return true;
        };

        try {
            $this->waitUntil($callbackEnvio, $timeout);
        } finally {
            $this->frame(null);
            $this->frame("ifrVisualizacao");
        }

        sleep(1);
    }

    protected function tramitarProcessoInternamente($unidadeDestino)
    {
        // Acessar funcionalidade de trâmite interno
        $this->paginaProcesso->navegarParaTramitarProcessoInterno();

        // Preencher parâmetros do trâmite
        $this->paginaTramitar->unidadeInterna($unidadeDestino);
        $this->paginaTramitar->tramitarInterno();

        sleep(1);
    }

    protected function navegarParaCancelarDocumento($ordemDocumento)
    {
        $listaDocumentos = $this->paginaProcesso->listarDocumentos();
        $this->paginaProcesso->selecionarDocumento($listaDocumentos[$ordemDocumento]);
        $this->paginaDocumento->navegarParaCancelarDocumento();
    }

    protected function tramitarProcessoInternamenteParaCancelamento($unidadeOrigem, $unidadeDestino, $protocolo)
    {
        //Tramitar internamento para liberação da funcionalidade de cancelar
        $this->tramitarProcessoInternamente($unidadeDestino);

        //Selecionar unidade interna
        $this->selecionarUnidadeInterna($unidadeDestino);
        if($protocolo){
            $this->paginaControleProcesso->abrirProcesso($protocolo['PROTOCOLO']);
        }        

        //Tramitar internamento para liberação da funcionalidade de cancelar
        $this->tramitarProcessoInternamente($unidadeOrigem);
        
        //Selecionar unidade interna
        $this->selecionarUnidadeInterna($unidadeOrigem);
        if($protocolo){
            $this->paginaControleProcesso->abrirProcesso($protocolo['PROTOCOLO']);
        }

        sleep(1);
    }

    protected function validarRecibosTramite($mensagem, $verificarReciboEnvio, $verificarReciboConclusao)
    {
        $mensagem = utf8_encode($mensagem);
        $this->waitUntil(function($testCase) use ($mensagem, $verificarReciboEnvio, $verificarReciboConclusao) {
            sleep(5);
            $testCase->refresh();
            $testCase->paginaProcesso->navegarParaConsultarRecibos();
            $this->assertTrue($testCase->paginaReciboTramite->contemTramite($mensagem, $verificarReciboEnvio, $verificarReciboConclusao));
            return true;
        }, PEN_WAIT_TIMEOUT);


    }

    protected function validarHistoricoTramite(
        $unidadeDestino, $verificarProcessoEmTramitacao=true, $verificarProcessoRecebido=true, $verificarProcessoRejeitado=false, $motivoRecusa=null
    )
    {
        $this->paginaProcesso->navegarParaConsultarAndamentos();

        if($verificarProcessoEmTramitacao){
            $this->assertTrue($this->paginaConsultarAndamentos->contemTramiteProcessoEmTramitacao($unidadeDestino));
        }

        if($verificarProcessoRecebido){
            $this->assertTrue($this->paginaConsultarAndamentos->contemTramiteProcessoRecebido($unidadeDestino));
        }

        if($verificarProcessoRejeitado){
            $this->assertTrue($this->paginaConsultarAndamentos->contemTramiteProcessoRejeitado($unidadeDestino, utf8_encode($motivoRecusa)));
        }
    }

    protected function validarDadosProcesso($descricao, $restricao, $observacoes, $listaInteressados, $hipoteseLegal = null)
    {
        sleep(2);
        $this->paginaProcesso->navegarParaEditarProcesso();
        $this->paginaEditarProcesso = new PaginaEditarProcesso($this);
        $this->assertEquals(utf8_encode($descricao), $this->paginaEditarProcesso->descricao());
        $this->assertEquals($restricao, $this->paginaEditarProcesso->restricao());

        for ($i=0; $i < count($listaInteressados); $i++) {
            $this->assertStringStartsWith(substr($listaInteressados[$i], 0, 100), $this->paginaEditarProcesso->listarInteressados()[$i]);
        }

        if($observacoes){
            $this->assertStringContainsString($observacoes, $this->byCssSelector('body')->text());
        }

        if($hipoteseLegal != null){
            $hipoteseLegalDocumento = $this->paginaEditarProcesso->recuperarHipoteseLegal();
            $this->assertEquals($hipoteseLegal, $hipoteseLegalDocumento);
        }
    }

    protected function validarDocumentoCancelado($nomeDocArvore)
    {
        sleep(2);
        $this->assertTrue($this->paginaProcesso->ehDocumentoCancelado($nomeDocArvore));
    }

    protected function validarDocumentoMovido($nomeDocArvore)
    {
        sleep(2);
        $this->assertTrue($this->paginaProcesso->ehDocumentoMovido($nomeDocArvore));
    }

    protected function validarDadosDocumento($nomeDocArvore, $dadosDocumento, $destinatario, $unidadeSecundaria=false, $hipoteseLegal=null)
    {
        sleep(2);

        // Verifica se documento possui marcação de documento anexado
        $bolPossuiDocumentoReferenciado = !is_null($dadosDocumento['ORDEM_DOCUMENTO_REFERENCIADO']);
        $this->assertTrue($this->paginaProcesso->deveSerDocumentoAnexo($bolPossuiDocumentoReferenciado, $nomeDocArvore));

        $this->paginaProcesso->selecionarDocumento($nomeDocArvore);
        $this->paginaDocumento->navegarParaConsultarDocumento();
        $mesmoOrgao = $dadosDocumento['ORIGEM'] == $destinatario['URL'];
        if($mesmoOrgao && $dadosDocumento['TIPO'] == 'G') {
            $this->assertEquals($dadosDocumento["DESCRICAO"], $this->paginaDocumento->descricao());
            if(!$mesmoOrgao){
                $observacoes = ($unidadeSecundaria) ? $this->paginaDocumento->observacoesNaTabela() : $this->paginaDocumento->observacoes();
                $this->assertEquals($dadosDocumento['OBSERVACOES'], $observacoes);
            }
        } else {
            $this->assertNotNull($this->paginaDocumento->nomeAnexo());
            $contemVariosComponentes = is_array($dadosDocumento['ARQUIVO']);
            if(!$contemVariosComponentes) {
                $nomeArquivo = $dadosDocumento['ARQUIVO'];
                $this->assertStringContainsString(basename($nomeArquivo), $this->paginaDocumento->nomeAnexo());
                if($hipoteseLegal != null){
                    $hipoteseLegalDocumento = $this->paginaDocumento->recuperarHipoteseLegal();
                    $this->assertEquals($hipoteseLegal, $hipoteseLegalDocumento);
                }
            }
        }
    }

    protected function validarProcessosTramitados($protocolo, $deveExistir)
    {
        $this->frame(null);
        $this->byLinkText("Menu")->click();
        $this->byLinkText("Processos Tramitados Externamente")->click();
        $this->assertEquals($deveExistir, $this->paginaProcessosTramitadosExternamente->contemProcesso($protocolo));
    }

    protected function validarProcessoRejeitado()
    {
        $this->paginaBase->navegarParaControleProcesso();
        $this->assertTrue($this->paginaControleProcesso->contemProcesso(self::$protocoloTeste));
        $this->assertTrue($this->paginaControleProcesso->contemAlertaProcessoRecusado(self::$protocoloTeste));
    }

    public function gerarDadosProcessoTeste($contextoProducao)
    {
        return array(
            "TIPO_PROCESSO" => $contextoProducao['TIPO_PROCESSO'],
            "DESCRICAO" => util::random_string(100),
            "OBSERVACOES" => null,
            "INTERESSADOS" => str_repeat(util::random_string(9) . ' ', 25),
            "RESTRICAO" => PaginaIniciarProcesso::STA_NIVEL_ACESSO_PUBLICO,
            "ORIGEM" => $contextoProducao['URL'],
        );
    }

    public function gerarDadosDocumentoInternoTeste($contextoProducao)
    {
        return array(
            'TIPO' => 'G', // Documento do tipo Gerado pelo sistema
            "NUMERO" => null, //Gerado automaticamente no cadastramento do documento
            "TIPO_DOCUMENTO" => $contextoProducao['TIPO_DOCUMENTO'],
            "DESCRICAO" => trim(str_repeat(util::random_string(9) . ' ', 10)),
            "OBSERVACOES" => null,
            "INTERESSADOS" => str_repeat(util::random_string(9) . ' ', 25),
            "RESTRICAO" => PaginaIniciarProcesso::STA_NIVEL_ACESSO_PUBLICO,
            "ORDEM_DOCUMENTO_REFERENCIADO" => null,
            "ARQUIVO" => ".html",
            "ORIGEM" => $contextoProducao['URL'],
        );
    }

    public function gerarDadosDocumentoExternoTeste($contextoProducao, $nomesArquivos='arquivo_pequeno.txt', $ordemDocumentoReferenciado=null)
    {
        // Tratamento para lista de arquivos em casos de documentos com mais de um componente digital
        $pasta = self::PASTA_ARQUIVOS_TESTE;
        $arquivos = is_array($nomesArquivos) ? array_map(function($item) use ($pasta) { return "$pasta/$item";}, $nomesArquivos) : "$pasta/$nomesArquivos";

        return array(
            'TIPO' => 'R', // Documento do tipo Recebido pelo sistema
            "NUMERO" => null, //Gerado automaticamente no cadastramento do documento
            "TIPO_DOCUMENTO" => $contextoProducao['TIPO_DOCUMENTO'],
            "DATA_ELABORACAO" => '01/01/2017',
            "DESCRICAO" => str_repeat(util::random_string(9) . ' ', 10),
            "OBSERVACOES" => util::random_string(500),
            "INTERESSADOS" => str_repeat(util::random_string(9) . ' ', 25),
            "ORDEM_DOCUMENTO_REFERENCIADO" => $ordemDocumentoReferenciado,
            "RESTRICAO" => PaginaIniciarProcesso::STA_NIVEL_ACESSO_PUBLICO,
            "ARQUIVO" => $arquivos,
            "ORIGEM" => $contextoProducao['URL'],
        );
    }

    public function gerarDadosDocumentoExternoGrandeTeste($contextoProducao, $nomesArquivo='arquivo_grande_gerado.txt',$tamanhoMB=100 ,  $ordemDocumentoReferenciado=null)
    {
        // Tratamento para lista de arquivos em casos de documentos com mais de um componente digital
        $pasta = self::PASTA_ARQUIVOS_TESTE;
        shell_exec('dd if=/dev/zero of=' . self::PASTA_ARQUIVOS_TESTE . '/' . $nomesArquivo . ' bs=1M count=' . $tamanhoMB);
        $arquivos = "$pasta/$nomesArquivo";

        return array(
            'TIPO' => 'R', // Documento do tipo Recebido pelo sistema
            "NUMERO" => null, //Gerado automaticamente no cadastramento do documento
            "TIPO_DOCUMENTO" => $contextoProducao['TIPO_DOCUMENTO'],
            "DATA_ELABORACAO" => '01/01/2017',
            "DESCRICAO" => str_repeat(util::random_string(9) . ' ', 10),
            "OBSERVACOES" => util::random_string(500),
            "INTERESSADOS" => str_repeat(util::random_string(9) . ' ', 25),
            "ORDEM_DOCUMENTO_REFERENCIADO" => $ordemDocumentoReferenciado,
            "RESTRICAO" => PaginaIniciarProcesso::STA_NIVEL_ACESSO_PUBLICO,
            "ARQUIVO" => $arquivos,
            "ORIGEM" => $contextoProducao['URL'],
        );
    }

    protected function realizarTramiteExterno(&$processoTeste, $documentosTeste, $remetente, $destinatario, $validarTramite)
    {
        $orgaosDiferentes = $remetente['URL'] != $destinatario['URL'];

        // 1 - Acessar sistema do REMETENTE do processo
        $this->acessarSistema($remetente['URL'], $remetente['SIGLA_UNIDADE'], $remetente['LOGIN'], $remetente['SENHA']);

        // 2 - Cadastrar novo processo de teste
        if (isset($processoTeste['PROTOCOLO'])){
            $strProtocoloTeste = $processoTeste['PROTOCOLO'];
            $this->abrirProcesso($strProtocoloTeste);
        }
        else {
            $strProtocoloTeste = $this->cadastrarProcesso($processoTeste);
            $processoTeste['PROTOCOLO'] = $strProtocoloTeste;
        }

        // 3 - Incluir Documentos no Processo
        $documentosTeste = array_key_exists('TIPO', $documentosTeste) ? array($documentosTeste) : $documentosTeste;
        foreach ($documentosTeste as $doc) {
            if($doc['TIPO'] == 'G') {
                $this->cadastrarDocumentoInterno($doc);
                $this->assinarDocumento($remetente['ORGAO'], $remetente['CARGO_ASSINATURA'], $remetente['SENHA']);
            }
            else if($doc['TIPO'] == 'R') {
                $this->cadastrarDocumentoExterno($doc);
            }
        }

        // 5 - Trâmitar Externamento processo para órgão/unidade destinatária
        $paginaTramitar = $this->paginaTramitar;
        $this->tramitarProcessoExternamente($strProtocoloTeste, $destinatario['REP_ESTRUTURAS'], $destinatario['NOME_UNIDADE'], $destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);

        if($validarTramite) {
            // 6 - Verificar se situação atual do processo está como bloqueado
            $this->waitUntil(function($testCase) use (&$orgaosDiferentes){
                sleep(5);
                $this->atualizarTramitesPEN();
                $testCase->refresh();
                $paginaProcesso = new PaginaProcesso($testCase);
                $testCase->assertStringNotContainsString(utf8_encode("Processo em trâmite externo para "), $paginaProcesso->informacao());
                $testCase->assertFalse($paginaProcesso->processoAberto());
                $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
                return true;
            }, PEN_WAIT_TIMEOUT);

            // 7 - Validar se recibo de trâmite foi armazenado para o processo (envio e conclusão)
            $unidade = mb_convert_encoding($destinatario['NOME_UNIDADE'], "ISO-8859-1");
            $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", $strProtocoloTeste, $unidade);
            $this->validarRecibosTramite($mensagemRecibo, true, true);

            // 8 - Validar histórico de trâmite do processo
            $this->validarHistoricoTramite(self::$nomeUnidadeDestinatario, true, true);

            // 9 - Verificar se processo está na lista de Processos Tramitados Externamente
            $deveExistir = $remetente['URL'] != $destinatario['URL'];
            $this->validarProcessosTramitados($strProtocoloTeste, $deveExistir);
        }
    }

    public function realizarTramiteExternoSemvalidacaoNoRemetente(&$processoTeste, $documentosTeste, $remetente, $destinatario)
    {
        $this->realizarTramiteExterno($processoTeste, $documentosTeste, $remetente, $destinatario, false);

    }

    public function realizarTramiteExternoComValidacaoNoRemetente(&$processoTeste, $documentosTeste, $remetente, $destinatario)
    {
        $this->realizarTramiteExterno($processoTeste, $documentosTeste, $remetente, $destinatario, true);
    }

    public function realizarValidacaoRecebimentoProcessoNoDestinatario($processoTeste, $documentosTeste, $destinatario, $devolucao = false, $unidadeSecundaria = false)
    {
        $strProtocoloTeste = $processoTeste['PROTOCOLO'];

        // 10 - Acessar sistema de REMETENTE do processo
        $this->acessarSistema($destinatario['URL'], $destinatario['SIGLA_UNIDADE'], $destinatario['LOGIN'], $destinatario['SENHA']);

        // 11 - Abrir protocolo na tela de controle de processos
        $this->waitUntil(function($testCase) use ($strProtocoloTeste){
            sleep(5);
            $this->abrirProcesso($strProtocoloTeste);
            return true;
        }, PEN_WAIT_TIMEOUT);

        $listaDocumentos = $this->paginaProcesso->listarDocumentos();

        // 12 - Validar dados  do processo
        $devolucao = $processoTeste['ORIGEM'] == $destinatario['URL'];
        $strTipoProcesso = utf8_encode("Tipo de processo no órgão de origem: ");
        $strTipoProcesso .= $processoTeste['TIPO_PROCESSO'];
        $processoTeste['OBSERVACOES'] = (!$devolucao) ? $strTipoProcesso : $processoTeste['OBSERVACOES'];
        $this->validarDadosProcesso($processoTeste['DESCRICAO'], $processoTeste['RESTRICAO'], $processoTeste['OBSERVACOES'], array($processoTeste['INTERESSADOS']));

        // 13 - Verificar recibos de trâmite
        $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);

        // 14 - Validar dados do documento
        $documentosTeste = array_key_exists('TIPO', $documentosTeste) ? array($documentosTeste) : $documentosTeste;
        $this->assertEquals(count($listaDocumentos), count($documentosTeste));

        for ($i=0; $i < count($listaDocumentos); $i++) {
            $this->validarDadosDocumento($listaDocumentos[$i], $documentosTeste[$i], $destinatario, $unidadeSecundaria,null);
        }
    }

    public function realizarValidacaoRecebimentoDocumentoAvulsoNoDestinatario($documentosTeste, $destinatario, $devolucao=false, $unidadeSecundaria=false)
    {
        $strProtocoloTeste = null;
        $strDescricao = $documentosTeste['DESCRICAO'];

        // Acessar sistema de REMETENTE do processo
        $this->acessarSistema($destinatario['URL'], $destinatario['SIGLA_UNIDADE'], $destinatario['LOGIN'], $destinatario['SENHA']);

        // Abrir protocolo na tela de controle de processos pelo texto da descrição
        $this->waitUntil(function($testCase) use ($strDescricao, &$strProtocoloProcesso){
            sleep(5);
            $strProtocoloTeste = $this->abrirProcessoPelaDescricao($strDescricao);
            $this->assertNotFalse($strProtocoloTeste);
            return true;
        }, PEN_WAIT_TIMEOUT);

        $listaDocumentos = $this->paginaProcesso->listarDocumentos();

        // Validar dados  do processo
        $this->validarDadosProcesso($documentosTeste['DESCRICAO'], $documentosTeste['RESTRICAO'], null, array($documentosTeste['INTERESSADOS']));

        // Verificar recibos de trâmite
        $this->validarRecibosTramite("Recebimento do Documento $strProtocoloTeste", false, true);

        // Validar dados do documento
        $documentosTeste = array_key_exists('TIPO', $documentosTeste) ? array($documentosTeste) : $documentosTeste;
        $this->assertEquals(count($listaDocumentos), count($documentosTeste));

        for ($i=0; $i < count($listaDocumentos); $i++) {
            $this->validarDadosDocumento($listaDocumentos[$i], $documentosTeste[$i], $destinatario, $unidadeSecundaria);
        }
    }

    public function realizarValidacaoNAORecebimentoProcessoNoDestinatario($destinatario, $processoTeste)
    {
        $this->acessarSistema($destinatario['URL'], $destinatario['SIGLA_UNIDADE'], $destinatario['LOGIN'], $destinatario['SENHA']);
        $this->paginaBase->navegarParaControleProcesso();
        $this->assertFalse($this->paginaControleProcesso->contemProcesso($processoTeste['PROTOCOLO'], false, false));
    }

    public function atualizarTramitesPEN($bolOrg1=true,$bolOrg2=true,$org2Primeiro=true,$quantidade=1)
    {
        /*for($i=0;$i<$quantidade;$i++){
            if($org2Primeiro){
                if($bolOrg2)exec(PEN_SCRIPT_MONITORAMENTO_ORG2);
                if($bolOrg1)exec(PEN_SCRIPT_MONITORAMENTO_ORG1);
            }else{
                if($bolOrg1)exec(PEN_SCRIPT_MONITORAMENTO_ORG1);
                if($bolOrg2)exec(PEN_SCRIPT_MONITORAMENTO_ORG2);
            }
        }*/
    }
}
