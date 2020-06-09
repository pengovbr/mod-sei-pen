<?php

use \utilphp\util;

/**
* Classe base contendo rotinas comuns utilizadas nos casos de teste do módulo
*/
class CenarioBaseTestCase extends PHPUnit_Extensions_Selenium2TestCase
{
    //Referência para o processo e documentos sob teste
    //protected static $protocoloTeste = null;
    protected static $processoTeste = null;
    protected static $documentoTeste = null;
    
    //Referência para unidades que serão consideradas no fluxo de trâmite (Remetente -> Destinatário)
    protected static $urlSistemaRemetente = null;
    protected static $siglaOrgaoRemetente = null;
    protected static $siglaUnidadeRemetente = null;
    protected static $nomeUnidadeRemetente = null;
    
    protected static $urlSistemaDestinatario = null;
    protected static $siglaOrgaoDestinatario = null;
    protected static $siglaUnidadeDestinatario = null;
    protected static $nomeUnidadeDestinatario = null;
    
    //Referências para as páginas do SEI utilizadas nos cenários de teste
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
            'LOCALIZACAO_CERTIFICADO_DIGITAL' => constant($nomeContexto . '_LOCALIZACAO_CERTIFICADO_DIGITAL'), 
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
    
    protected function cadastrarProcesso(&$dadosProcesso)
    {
        $protocoloGerado = PaginaIniciarProcesso::gerarProcessoTeste($this, $dadosProcesso);
        $dadosProcesso['PROTOCOLO'] = $protocoloGerado;
        sleep(2);
        return $protocoloGerado;
    }
    
    protected function abrirProcesso($protocolo)
    {
        $this->byLinkText("Controle de Processos")->click();
        $this->paginaControleProcesso->abrirProcesso($protocolo);
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
    
    protected function tramitarProcessoExternamente($protocolo, $repositorio, $unidadeDestino, $unidadeDestinoHierarquia, $urgente = false, $callbackEnvio = null)
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
        
        // Aguardar finalização do trâmite
        $callbackEnvio = $callbackEnvio ?: function($testCase) { 
            $testCase->window($this->windowHandles()[1]);        
            $testCase->assertContains('Trâmite externo do processo finalizado com sucesso!', $testCase->byCssSelector('body')->text());
            $testCase->closeWindow();
            $testCase->window('');            
            return true;
        };
        
        try{ $mensagemAlerta = $this->paginaTramitar->alertTextAndClose(true); }
        catch(Exception $e){ }
        
        if(isset($mensagemAlerta))
        throw new Exception($mensagemAlerta);
        
        $this->waitUntil($callbackEnvio, PEN_WAIT_TIMEOUT);
        
        //Tempo mí­mino para recarregamento da página de fundo
        sleep(1);   
    }
    
    protected function validarRecibosTramite($mensagem, $verificarReciboEnvio, $verificarReciboConclusao)
    {    
        $this->waitUntil(function($testCase) use ($mensagem, $verificarReciboEnvio, $verificarReciboConclusao) {
            sleep(5);
            $testCase->refresh();
            $testCase->paginaProcesso->navegarParaConsultarRecibos();
            return $testCase->paginaReciboTramite->contemTramite($mensagem, $verificarReciboEnvio, $verificarReciboConclusao);
        }, 1200);
        
        $this->assertTrue($this->paginaReciboTramite->contemTramite($mensagem, $verificarReciboEnvio, $verificarReciboConclusao));
    }
    
    protected function validarHistoricoTramite($unidadeDestino, $verificarProcessoEmTramitacao = true, 
    $verificarProcessoRecebido = true, $verificarProcessoRejeitado = false, $motivoRecusa = null)
    {
        $this->paginaProcesso->navegarParaConsultarAndamentos();
        
        if($verificarProcessoEmTramitacao)
        $this->assertTrue($this->paginaConsultarAndamentos->contemTramiteProcessoEmTramitacao($unidadeDestino));
        
        if($verificarProcessoRecebido)
        $this->assertTrue($this->paginaConsultarAndamentos->contemTramiteProcessoRecebido($unidadeDestino));        
        
        if($verificarProcessoRejeitado)
        $this->assertTrue($this->paginaConsultarAndamentos->contemTramiteProcessoRejeitado($unidadeDestino, $motivoRecusa));
    }
    
    protected function validarDadosProcesso($descricao, $restricao, $observacoes, $listaInteressados, $hipoteseLegal = null)
    {
        $this->paginaProcesso->navegarParaEditarProcesso();
        $this->paginaEditarProcesso = new PaginaEditarProcesso($this);
        $this->assertEquals($descricao, $this->paginaEditarProcesso->descricao());
        $this->assertEquals($restricao, $this->paginaEditarProcesso->restricao());
        $this->assertEquals($listaInteressados, $this->paginaEditarProcesso->listarInteressados()); 

        if($observacoes){
            $this->assertContains($observacoes, $this->byCssSelector('body')->text());  
        }

        if($hipoteseLegal != null){
            $hipoteseLegalDocumento = $this->paginaEditarProcesso->recuperarHipoteseLegal();
            $this->assertEquals($hipoteseLegal, $hipoteseLegalDocumento);
        }
    }
    
    //TODO: Adicionar validação da lista de interessados do documento
    protected function validarDadosDocumento($nomeDocArvore, $dadosDocumento, $destinatario, $unidadeSecundaria = false, $hipoteseLegal = null)
    {
        sleep(2);
        $this->paginaProcesso->selecionarDocumento($nomeDocArvore);
        $this->paginaDocumento->navegarParaConsultarDocumento();
        $mesmoOrgao = $dadosDocumento['ORIGEM'] == $destinatario['ORGAO'];
        if($mesmoOrgao && $dadosDocumento['TIPO'] == 'G') {
            $this->assertEquals($dadosDocumento["DESCRICAO"], $this->paginaDocumento->descricao());
            if(!$mesmoOrgao){
                $observacoes = ($unidadeSecundaria) ? $this->paginaDocumento->observacoesNaTabela() : $this->paginaDocumento->observacoes();
                $this->assertEquals($dadosDocumento['OBSERVACOES'], $observacoes);                
            }
        } else {
            $this->assertNotNull($this->paginaDocumento->nomeAnexo());
            $this->assertContains(basename($dadosDocumento['ARQUIVO']), $this->paginaDocumento->nomeAnexo());
            if($hipoteseLegal != null){
                $hipoteseLegalDocumento = $this->paginaDocumento->recuperarHipoteseLegal();
                $this->assertEquals($hipoteseLegal, $hipoteseLegalDocumento);
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
            "DESCRICAO" => util::random_string(20), 
            "OBSERVACOES" => util::random_string(100),
            "INTERESSADOS" => util::random_string(40), 
            "RESTRICAO" => PaginaIniciarProcesso::STA_NIVEL_ACESSO_PUBLICO,
            "ORIGEM" => $contextoProducao['ORGAO'],            
        );
    }
    
    public function gerarDadosDocumentoInternoTeste($contextoProducao)
    {
        return array(
            'TIPO' => 'G', // Documento do tipo Gerado pelo sistema
            "NUMERO" => null, //Gerado automaticamente no cadastramento do documento
            "TIPO_DOCUMENTO" => $contextoProducao['TIPO_DOCUMENTO'], 
            "DESCRICAO" => util::random_string(20), 
            "OBSERVACOES" => util::random_string(100),
            "INTERESSADOS" => util::random_string(40), 
            "RESTRICAO" => PaginaIniciarProcesso::STA_NIVEL_ACESSO_PUBLICO, 
            "ARQUIVO" => ".html",
            "ORIGEM" => $contextoProducao['ORGAO'],
        );
    }  
    
    public function gerarDadosDocumentoExternoTeste($contextoProducao, $nomeArquivo = 'arquivo_001.pdf')
    {
        return array(
            'TIPO' => 'R', // Documento do tipo Recebido pelo sistema
            "NUMERO" => null, //Gerado automaticamente no cadastramento do documento
            "TIPO_DOCUMENTO" => $contextoProducao['TIPO_DOCUMENTO'], 
            "DATA_ELABORACAO" => '01/01/2017',
            "DESCRICAO" => util::random_string(20), 
            "OBSERVACOES" => util::random_string(100),
            "INTERESSADOS" => util::random_string(40), 
            "RESTRICAO" => PaginaIniciarProcesso::STA_NIVEL_ACESSO_PUBLICO,
            "ARQUIVO" => __dir__ . "/arquivos/$nomeArquivo",
            "ORIGEM" => $contextoProducao['ORGAO'],
        );  
    } 
    
    protected function realizarTramiteExterno(&$processoTeste, $documentosTeste, $remetente, $destinatario, $validarTramite)
    {
        $orgaosDiferentes = $remetente['ORGAO'] != $destinatario['ORGAO'];
        
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
        $this->tramitarProcessoExternamente($strProtocoloTeste, $destinatario['REP_ESTRUTURAS'], $destinatario['NOME_UNIDADE'], $destinatario['SIGLA_UNIDADE_HIERARQUIA'], false, function($testCase) {
            $testCase->window($this->windowHandles()[1]);        
            $testCase->assertContains('Trâmite externo do processo finalizado com sucesso!', $testCase->byCssSelector('body')->text());
            $testCase->closeWindow();
            $testCase->window('');            
            return true;
        });
        
        // 6 - Verificar se situação atual do processo está como bloqueado
        $this->waitUntil(function($testCase) use (&$orgaosDiferentes){
            sleep(5);
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertNotContains('Processo em trâmite externo para ', $paginaProcesso->informacao());        
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());            
            return true;
        }, PEN_WAIT_TIMEOUT);
        
        if($validarTramite) {
            // 7 - Validar se recibo de trâmite foi armazenado para o processo (envio e conclusão)
            $this->validarRecibosTramite(sprintf("Trâmite externo do Processo %s para %s", $strProtocoloTeste, $destinatario['NOME_UNIDADE']) , true, true);
            
            // 8 - Validar histórico de trâmite do processo
            $this->validarHistoricoTramite(self::$nomeUnidadeDestinatario, true, true);
            
            // 9 - Verificar se processo está na lista de Processos Tramitados Externamente
            $deveExistir = $remetente['ORGAO'] != $destinatario['ORGAO'];
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
        $this->abrirProcesso($strProtocoloTeste);
        $listaDocumentos = $this->paginaProcesso->listarDocumentos();
        
        // 12 - Validar dados  do processo    
        $devolucao = $processoTeste['ORIGEM'] == $destinatario['ORGAO'];
        $processoTeste['OBSERVACOES'] = (!$devolucao) ? 'Tipo de processo no órgão de origem: ' . $processoTeste['TIPO_PROCESSO'] : $processoTeste['OBSERVACOES'];
        $this->validarDadosProcesso($processoTeste['DESCRICAO'], $processoTeste['RESTRICAO'], $processoTeste['OBSERVACOES'], array($processoTeste['INTERESSADOS']));
        
        // 13 - Verificar recibos de trâmite
        $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);        
        
        // 14 - Validar dados do documento
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
    
    public function setUp()
    {
        $this->setHost(PHPUNIT_HOST);
        $this->setPort(intval(PHPUNIT_PORT));
        $this->setBrowser(PHPUNIT_BROWSER);
        $this->setBrowserUrl(PHPUNIT_TESTS_URL);
        $this->setDesiredCapabilities(
            array(
                'platform' => 'LINUX',
                'chromeOptions' => array('w3c' => false)
            )
        );
    }
    
    public function setUpPage()
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
        $this->currentWindow()->maximize();
    }
}
