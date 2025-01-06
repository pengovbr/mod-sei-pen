<?php

use \utilphp\util;

/**
 * Execution Groups
 * @group execute_alone_group4
 */
class TramiteRecebimentoDocumentoAvulsoTest extends FixtureCenarioBaseTestCase
{
    const ALGORITMO_HASH_DOCUMENTO = 'SHA256';
    const ALGORITMO_HASH_ASSINATURA = 'SHA256withRSA';

    const CONTEUDO_DOCUMENTO_A = "arquivo_pequeno_A.pdf";
    const CONTEUDO_DOCUMENTO_B = "arquivo_pequeno_B.pdf";
    const CONTEUDO_DOCUMENTO_C = "arquivo_pequeno_C.pdf";

    protected $servicoPEN;
    protected $servicoPEN2;
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste1;
    public static $documentoTeste2;
    public static $documentoTeste3;
    public static $documentoTeste4;
    public static $documentoTeste5;

    /**
     * Teste preparatório (setUp()). Definição de contextos e instanciação da api de integração
     * 
     * @Depends CenarioBaseTestCase::setUpBeforeClass
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setup();

        // Carregar contexto de testes e dados sobre certificado digital
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        
    }

    /**
     * Teste de verificação do correto recebimento do documento avulso
     *
     * @return void
     */
    public function test_recebimento_metadados_documento_avulso()
    {

        $localCertificado = self::$remetente['LOCALIZACAO_CERTIFICADO_DIGITAL'];
        $senhaCertificado = self::$remetente['SENHA_CERTIFICADO_DIGITAL'];

       $this->servicoPEN = $this->instanciarApiDeIntegracao($localCertificado, $senhaCertificado);

        // Simular um trâmite chamando a API do Barramento diretamente
        self::$documentoTeste1 = $this->gerarDadosDocumentoExternoTeste(self::$remetente, array(self::CONTEUDO_DOCUMENTO_A));

        $metadadosDocumentoTeste = $this->construirMetadadosDocumentoTeste(self::$documentoTeste1);
        $novoTramite = $this->enviarMetadadosDocumento(self::$remetente, self::$destinatario, $metadadosDocumentoTeste);
        $this->enviarComponentesDigitaisDoTramite($novoTramite, $metadadosDocumentoTeste);
        $reciboTramite = $this->receberReciboEnvio($novoTramite);

        //Verificar recebimento de novo processo administrativo contendo documento avulso enviado
        $this->assertNotNull($novoTramite);
        $this->assertNotNull($reciboTramite);
        self::$processoTeste = $this->realizarValidacaoRecebimentoDocumentoAvulsoNoDestinatario(self::$documentoTeste1, self::$destinatario);
    }

    /**
     * Teste de trâmite externo de processo com devolução para a mesma unidade de origem
     *
     * @group envio
     * @large
     *
     *
     * @return void
     */
    public function test_devolucao_processo_para_origem()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$documentoTeste2 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        self::$documentoTeste3 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

        $documentos = array(self::$documentoTeste2, self::$documentoTeste3);
        putenv("DATABASE_HOST=org1-database");
        $this->realizarTramiteExternoComValidacaoNoRemetenteFixture(self::$processoTeste, $documentos, self::$remetente, self::$destinatario);
    }

    /**
     * Teste de verificação do correto recebimento do processo no destino
     *
     * @group verificacao_recebimento
     * @large
     *
     * @depends test_devolucao_processo_para_origem
     *
     * @return void
     */
    public function test_verificar_recebimento_processo_destino()
    {
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $documentos = array(self::$documentoTeste1, self::$documentoTeste2, self::$documentoTeste3);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, $documentos, self::$destinatario);
    }

    /**
     * Teste de trâmite externo de processo com devolução para a mesma unidade de origem
     *
     * @group envio
     * @large
     *
     * @depends test_verificar_recebimento_processo_destino
     *
     * @return void
     */
    public function test_devolucao_processo_para_origem_documento_avulso()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$documentoTeste4 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        self::$documentoTeste5 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

        putenv("DATABASE_HOST=org2-database");
        $documentos = array(self::$documentoTeste4, self::$documentoTeste5);
        $this->realizarTramiteExternoComValidacaoNoRemetenteFixture(self::$processoTeste, $documentos, self::$remetente, self::$destinatario);
    }

    /**
     * Teste de verificação do correto recebimento do processo no destino
     *
     * @group verificacao_recebimento
     * @large
     *
     * @depends test_devolucao_processo_para_origem_documento_avulso
     *
     * @return void
     */
    public function test_verificar_recebimento_processo_destino_documento_avulso()
    {
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        $documentos = array(self::$documentoTeste1, self::$documentoTeste2, self::$documentoTeste3, self::$documentoTeste4, self::$documentoTeste5);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, $documentos, self::$destinatario);
    }


    private function receberReciboEnvio($novoTramite)
    {
        $dadosTramite = $novoTramite['tramites'];
        $idt = $dadosTramite[0]['IDT'];
        return $this->receberReciboDeEnvioAPI($idt);
    }

    private function enviarMetadadosDocumento($remetente, $destinatario, $documentoTeste)
    {
        $parametros = [];
        $parametros['cabecalho'] = $this->construirCabecalhoTeste($remetente, $destinatario);
        $parametros['documento'] = $documentoTeste['documentoEnvio'];

        return $this->enviarDocumentoAPI($parametros);
    }

    private function enviarComponentesDigitaisDoTramite($novoTramite, $documentoTeste)
    {
        $parametros = [];
        $dadosDoComponenteDigital['protocolo'] = $documentoTeste['documentoEnvio']['protocolo'];
        $dadosDoComponenteDigital['hashDoComponenteDigital'] = $documentoTeste['componenteEnvio']['hashDocumento'];
        $dadosDoComponenteDigital['conteudoDoComponenteDigital'] = $documentoTeste['componenteEnvio']['conteudo'];
        $dadosDoComponenteDigital['ticketParaEnvioDeComponentesDigitais'] = $novoTramite['ticketParaEnvioDeComponentesDigitais'];
        
        $parametros['dadosDoComponenteDigital'] = $dadosDoComponenteDigital;

        $this->enviarComponenteDigitalAPI($parametros);
    }

    private function construirCabecalhoTeste($remetente, $destinatario)
    {
        $cabecalho = [
            'remetente' => [
                'identificacaoDoRepositorioDeEstruturas' => $remetente['ID_REP_ESTRUTURAS'],
                'numeroDeIdentificacaoDaEstrutura' => $remetente['ID_ESTRUTURA'],
            ],
            'destinatarios' => [
                [
                    'identificacaoDoRepositorioDeEstruturas' => $destinatario['ID_REP_ESTRUTURAS'],
                    'numeroDeIdentificacaoDaEstrutura' => $destinatario['ID_ESTRUTURA'],
                ],
            ]
        ];
        
        return $cabecalho;
    }


    public function gerarDadosDocumentoExternoTeste($contextoProducao, $nomesArquivos='arquivo_pequeno.txt', $ordemDocumentoReferenciado=null)
    {
        $dadosDocumentoTest = parent::gerarDadosDocumentoExternoTeste($contextoProducao, $nomesArquivos, $ordemDocumentoReferenciado);
        $dadosDocumentoTest['INTERESSADOS'] = trim(substr($dadosDocumentoTest['INTERESSADOS'], 0, 15));
        $dadosDocumentoTest['DESCRICAO'] = trim(substr($dadosDocumentoTest['DESCRICAO'], 0, 10));
        return $dadosDocumentoTest;

    }

    private function construirMetadadosDocumentoTeste($documentoTeste)
    {
        $componentes = array();
        $listaComponentes = is_array($documentoTeste['ARQUIVO']) ? $documentoTeste['ARQUIVO'] : array($documentoTeste['ARQUIVO']);

        foreach ($listaComponentes as $ordem => $caminhoArquivo) {
            $caminhoArquivo = realpath($caminhoArquivo);
            $fp = fopen($caminhoArquivo, "rb");
            try{
                $conteudo = fread($fp, filesize($caminhoArquivo));
                $tamanhoDocumento = strlen($conteudo);
                $hashDocumento = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $conteudo, true));
                $nomeArquivo = basename($caminhoArquivo);
                $componentes[] = array(
                    'nome' => $nomeArquivo,
                    'hash' => [
                        'algoritmo' => 'SHA256',
                        'conteudo' => $hashDocumento
                    ],
                    'tipoDeConteudo' => 'txt',
                    "mimeType" => "application/pdf",
                    'tamanhoEmBytes' => $tamanhoDocumento,
                    'ordem' => $ordem + 1,
                );
            } finally {
               fclose($fp);
            }
        }

        $documentoEnvio = array(
            'protocolo' => '13990.000185/2024-00',
            'nivelDeSigilo' => 1,
            'descricao' => $documentoTeste['DESCRICAO'],
            'dataHoraDeProducao' => '2017-05-15T03:41:13',
            'dataHoraDeRegistro' => '2013-12-21T09:32:42-02:00',
            'produtor' => array(
                "nome" => "Nome ABC",
                "tipo" => "orgaopublico"
            ),
            'especie' => array(
                'codigo' => 42,
                'nomeNoProdutor' => 'Despacho',
            ),
            'interessados' => array(
                [
                    "nome" => $documentoTeste['INTERESSADOS'],
                    "tipo" => "fisica"
                ]
            ),

            'componentesDigitais' => $componentes,
        );

        $componenteEnvio = array(
            'hashDocumento' => $hashDocumento,
            'conteudo' => $conteudo
        );

        return array(
            'documentoEnvio' => $documentoEnvio,
            'componenteEnvio' => $componenteEnvio
        );
    }

    public function enviarDocumentoAPI($parametros)
    {
        try {
            $endpoint = "tramites/documento";

            $response = $this->servicoPEN->request('POST', $endpoint, [
                'json' => $parametros
            ]);

            return  json_decode($response->getBody(), true);
    
        } catch (\Exception $e) {
            $mensagem = "Falha no envio de documento avulso";
        }
    }


    public function enviarComponenteDigitalAPI($parametros) 
    {
        try {
                
            $arrParametros = $parametros['dadosDoComponenteDigital'];
            $idTicketDeEnvio = $arrParametros['ticketParaEnvioDeComponentesDigitais'];

            $protocolo = $arrParametros['protocolo'];
            $hashDoComponenteDigital = $arrParametros['hashDoComponenteDigital'];
            $conteudo = $arrParametros['conteudoDoComponenteDigital'];

            $queryParams = [
                'hashDoComponenteDigital' => $hashDoComponenteDigital,
                'protocolo' => $protocolo
            ];
    
            $endpoint = "tickets-de-envio-de-componente/{$idTicketDeEnvio}/protocolos/componentes-a-enviar";
    
            $arrOptions = [
                'query' => $queryParams,
                'multipart' => [
                    [
                        'name'     => 'conteudo',
                        'contents' => $conteudo,
                        'filename' => 'conteudo.html',
                        'headers' => ['Content-Type' => 'text/html']
                    ],              
                ],
            ];
                    
            $response = $this->servicoPEN->request('PUT', $endpoint, $arrOptions);

            return $response;
    
        } catch (\Exception $e) {
            $mensagem = "Falha no envio de de componentes no documento";
        }
    }


    public function receberReciboDeEnvioAPI($parNumIdTramite)
    {
        $endpoint = "tramites/{$parNumIdTramite}/recibo-de-envio";
        try{
            $parametros = [
                'IDT' => $parNumIdTramite
            ];

            $response = $this->servicoPEN->request('GET', $endpoint, [
                'query' => $parametros
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            $mensagem = "Falha no recebimento de recibo de trâmite de envio.";
        }
    }

    private function instanciarApiDeIntegracao($localCertificado, $senhaCertificado) 
    {
        $arrheaders = [
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
        ];
        
        $strClientGuzzle = new GuzzleHttp\Client([
            'base_uri' => PEN_ENDERECO_WEBSERVICE,
            'handler' => GuzzleHttp\HandlerStack::create(),
            'timeout'  => 5.0,
            'headers'  => $arrheaders,
            'cert'     => [$localCertificado, $senhaCertificado],
        ]);

        return $strClientGuzzle;
    }
}
