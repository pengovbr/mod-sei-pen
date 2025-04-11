<?php

use \utilphp\util;

/**
 * Execution Groups
 * @group execute_parallel_group3
 */
class TramiteRecebimentoDocumentoAnexadoTest extends FixtureCenarioBaseTestCase
{
    const ALGORITMO_HASH_DOCUMENTO = 'SHA256';
    const ALGORITMO_HASH_ASSINATURA = 'SHA256withRSA';

    const CONTEUDO_DOCUMENTO_A = "arquivo_pequeno_A.pdf";
    const CONTEUDO_DOCUMENTO_B = "arquivo_pequeno_B.pdf";
    const CONTEUDO_DOCUMENTO_C = "arquivo_pequeno_C.pdf";

    public static $processoTeste;
    public static $remetente;
    public static $destinatario;
    public static $servicoPEN;
    public static $documentoTeste1;
    public static $documentoTeste2;
    public static $documentoTeste3;
    public static $documentoTeste4;
    public static $documentoTeste5;

    public static $conteudoCompoonenteDigital;

    /**
     * Teste de envio de metadados do processo contendo documentos anexados
     *
     * Inicialmente são enviados 3 documentos, sendo um deles referênciado pelos outros dois documentos
     *
     * @group envio
     * @large
     *
     * @Depends CenarioBaseTestCase::setUpBeforeClass
     *
     * @return void
     */
    public function test_envio_processo_com_documento_anexado()
    {
        // Carregar contexto de testes e dados sobre certificado digital
        $ordemDocumentoReferenciado = 1;
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$processoTeste['PROTOCOLO'] = sprintf('13990.%06d/2020-00', rand(0, 999999));
        self::$documentoTeste1 = $this->gerarDadosDocumentoExternoTeste(self::$remetente, self::CONTEUDO_DOCUMENTO_A);
        self::$documentoTeste2 = $this->gerarDadosDocumentoExternoTeste(self::$remetente, self::CONTEUDO_DOCUMENTO_B, $ordemDocumentoReferenciado);
        self::$documentoTeste3 = $this->gerarDadosDocumentoExternoTeste(self::$remetente, self::CONTEUDO_DOCUMENTO_C, $ordemDocumentoReferenciado);
        self::$documentoTeste4 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);
        self::$documentoTeste5 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

        $localCertificado = self::$remetente['LOCALIZACAO_CERTIFICADO_DIGITAL'];
        $senhaCertificado = self::$remetente['SENHA_CERTIFICADO_DIGITAL'];
        self::$servicoPEN = $this->instanciarApiDeIntegracao($localCertificado, $senhaCertificado);

        // Inicia o envio dos três primeiros documentos
        $arrDocumentosPrimeiroEnvio = array(self::$documentoTeste1, self::$documentoTeste2, self::$documentoTeste3);

        $processoTeste = $this->construirMetadadosProcessoTeste(self::$processoTeste, $arrDocumentosPrimeiroEnvio);
        $novoTramite = $this->enviarMetadadosProcesso(self::$remetente, self::$destinatario, $processoTeste);
        $this->enviarComponentesDigitaisDoTramite($novoTramite, $processoTeste);
        $reciboTramiteEnvio = $this->receberReciboEnvio($novoTramite);
         

        //Verificar recebimento de novo processo administrativo contendo documento avulso enviado
        $this->assertNotNull($novoTramite);
        $this->assertNotNull($reciboTramiteEnvio);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, $arrDocumentosPrimeiroEnvio, self::$destinatario);
        $reciboTramiteRecebido = $this->receberReciboTramite($novoTramite);
        $this->assertNotNull($reciboTramiteRecebido);
    }

    /**
     * Teste de trâmite externo de processo contendo documento anexado com devolução para a mesma unidade de origem
     *
     * @group envio
     * @large
     *
     * @depends test_envio_processo_com_documento_anexado
     *
     * @return void
     */
    public function test_devolucao_processo_com_documento_anexado_para_origem()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        $arrDocumentosSegundoEnvio = array(self::$documentoTeste4, self::$documentoTeste5);
        $this->realizarTramiteExternoComValidacaoNoRemetenteFixture(self::$processoTeste, $arrDocumentosSegundoEnvio, self::$remetente, self::$destinatario);
    }

    /**
     * Teste de verificação do correto recebimento do processo com documento anexado no destinatário
     *
     * @group verificacao_recebimento
     * @large
     *
     * @depends test_devolucao_processo_com_documento_anexado_para_origem
     *
     * @return void
     */
    public function test_verificar_processo_com_documento_anexado_apos_devolucao()
    {
        $arrDocumentos = array(self::$documentoTeste1, self::$documentoTeste2, self::$documentoTeste3, self::$documentoTeste4, self::$documentoTeste5);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, $arrDocumentos, self::$destinatario);
    }


    private function instanciarApiDeIntegracao($localCertificado, $senhaCertificado) 
    {
        $arrheaders = [
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
        ];
        
        $strClientGuzzle = new GuzzleHttp\Client([
            'base_uri' => PEN_ENDERECO_WEBSERVICE,
            'timeout'  => ProcessoEletronicoRN::WS_TIMEOUT_CONEXAO,
            'headers'  => $arrheaders,
            'cert'     => [$localCertificado, $senhaCertificado],
        ]);

        return $strClientGuzzle;
    }


    private function enviarMetadadosProcesso($remetente, $destinatario, $processoTeste)
    {
        $parametros = [];
        $parametros['cabecalho'] = $this->construirCabecalhoTeste($remetente, $destinatario);
        $parametros['processo'] = $processoTeste;

        return $this->enviarProcessoAPI($parametros);
    }


    private function enviarComponentesDigitaisDoTramite($novoTramite, $processoTeste)
    {
        foreach ($processoTeste['documentos'] as $documentoTeste) {
            foreach ($documentoTeste['componentesDigitais'] as $item) {
                $dadosDoComponenteDigital = [];
                $dadosDoComponenteDigital['protocolo'] = $processoTeste['protocolo'];
                $dadosDoComponenteDigital['hashDoComponenteDigital'] = $item['hash']['conteudo'];
                $dadosDoComponenteDigital['ticketParaEnvioDeComponentesDigitais'] = $novoTramite['ticketParaEnvioDeComponentesDigitais'];

                $parametros['dadosDoComponenteDigital'] = $dadosDoComponenteDigital;
                $this->enviarComponenteDigitalAPI($parametros);
            }
        }

    }

    private function receberReciboEnvio($novoTramite)
    {
        $idt = $novoTramite['IDT'];
        return $this->receberReciboDeEnvioAPI($idt);
    }


    private function receberReciboTramite($novoTramite)
    {
        $idt = $novoTramite['IDT'];
        return $this->receberReciboDeTramiteAPI($idt);
    }

    private function construirCabecalhoTeste($remetente, $destinatario)
    {
        $cabecalho = [
            'remetente' => [
                'identificacaoDoRepositorioDeEstruturas' => $remetente['ID_REP_ESTRUTURAS'],
                'numeroDeIdentificacaoDaEstrutura' => $remetente['ID_ESTRUTURA'],
            ],
            'destinatario' => [
                'identificacaoDoRepositorioDeEstruturas' => $destinatario['ID_REP_ESTRUTURAS'],
                'numeroDeIdentificacaoDaEstrutura' => $destinatario['ID_ESTRUTURA'],
            ],
            'enviarApenasComponentesDigitaisPendentes' => false
        ];
        
        return $cabecalho;
    }

    public function gerarDadosProcessoTeste($contextoProducao)
    {
        $processoTeste = parent::gerarDadosProcessoTeste($contextoProducao);
        $processoTeste['PROTOCOLO'] = sprintf('99999.%06d/2020-00', rand(0, 999999));
        $processoTeste['INTERESSADOS'] = trim(substr($processoTeste['INTERESSADOS'], 0, 15));
        $processoTeste['DESCRICAO'] = trim(substr($processoTeste['DESCRICAO'], 0, 10));
        return $processoTeste;
    }

    public function gerarDadosDocumentoExternoTeste($contextoProducao, $nomesArquivos='arquivo_pequeno.txt', $ordemDocumentoReferenciado=null)
    {
        $dadosDocumentoTeste = parent::gerarDadosDocumentoExternoTeste($contextoProducao, $nomesArquivos, $ordemDocumentoReferenciado);
        $dadosDocumentoTeste['INTERESSADOS'] = trim(substr($dadosDocumentoTeste['INTERESSADOS'], 0, 15));
        $dadosDocumentoTeste['DESCRICAO'] = trim(substr($dadosDocumentoTeste['DESCRICAO'], 0, 10));
        return $dadosDocumentoTeste;
    }

    private function construirMetadadosProcessoTeste($processoTeste, $documentosTeste)
    {
        $metadadosDocumentos = array();
        foreach ($documentosTeste as $indice => $documentoTeste) {
            $documentos = $this->construirMetadadosDocumentoTeste($documentoTeste, $indice + 1);
            $metadadosDocumentos[] = $documentos['documentoDoProcesso'];
        }

        return array(
            'protocolo' => $processoTeste['PROTOCOLO'],
            'nivelDeSigilo' => 1,
            'processoDeNegocio' => $processoTeste['TIPO_PROCESSO'],
            'descricao' => $processoTeste['DESCRICAO'],
            'dataHoraDeProducao' => '2017-05-15T03:41:13',
            'dataHoraDeRegistro' => '2013-12-21T09:32:42-02:00',
            'produtor' => array(
                'nome' => mb_convert_encoding(util::random_string(20), 'UTF-8', 'ISO-8859-1'),
                'tipo' => "orgaopublico",
            ),
            'interessados' => array(
                [
                    "nome" => $processoTeste['INTERESSADOS'],
                ]
            ),
            'documentos' => $metadadosDocumentos,
        );
    }

    private function construirMetadadosDocumentoTeste($documentoTeste, $ordemDocumento)
    {
        $componentes = array();
        $listaComponentes = is_array($documentoTeste['ARQUIVO']) ? $documentoTeste['ARQUIVO'] : array($documentoTeste['ARQUIVO']);

        foreach ($listaComponentes as $ordemComponente => $caminhoArquivo) {
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
                    'mimeType' => 'application/pdf',
                    'tamanhoEmBytes' => $tamanhoDocumento,
                    'ordem' => $ordemComponente,
                );

                self::$conteudoCompoonenteDigital[$hashDocumento] = $conteudo;
            } finally {
               fclose($fp);
            }
        }

        $documentoDoProcesso = array(
            'protocolo' => util::random_string(5),
            'nivelDeSigilo' => 1,
            'descricao' => $documentoTeste['DESCRICAO'],
            'dataHoraDeProducao' => '2017-05-15T03:41:13',
            'dataHoraDeRegistro' => '2013-12-21T09:32:42-02:00',
            'ordem' => $ordemDocumento,
            'produtor' => array(
                'nome' => mb_convert_encoding(util::random_string(20), 'UTF-8', 'ISO-8859-1'),
                "tipo" => "orgaopublico"
            ),

            'especie' => array(
                'codigo' => 42,
                'nomeNoProdutor' => mb_convert_encoding(util::random_string(20), 'UTF-8', 'ISO-8859-1')
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

        if(!is_null($documentoTeste['ORDEM_DOCUMENTO_REFERENCIADO'])){
            $documentoDoProcesso['ordemDoDocumentoReferenciado'] = intval($documentoTeste['ORDEM_DOCUMENTO_REFERENCIADO']);
        }

        return array(
            'documentoDoProcesso' => $documentoDoProcesso,
            'componenteEnvio' => $componenteEnvio
        );
    }

    public function enviarComponenteDigitalAPI($parametros) 
    {
        try {
                
            $arrParametros = $parametros['dadosDoComponenteDigital'];
            $idTicketDeEnvio = $arrParametros['ticketParaEnvioDeComponentesDigitais'];

            $protocolo = $arrParametros['protocolo'];
            $hashDoComponenteDigital = $arrParametros['hashDoComponenteDigital'];

            $conteudoComponenteDigital = self::$conteudoCompoonenteDigital[$hashDoComponenteDigital];

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
                        'contents' => $conteudoComponenteDigital,
                        'filename' => 'conteudo.html',
                        'headers' => ['Content-Type' => 'text/html']
                    ],              
                ],
            ];
                    
            $response = self::$servicoPEN->request('PUT', $endpoint, $arrOptions);

            return $response;
    
        } catch (\Exception $e) {
            $mensagem = "Falha no envio de de componentes no documento";
            $this->fail($mensagem . " - " . $e->getMessage());
        }
    }

    public function receberReciboDeEnvioAPI($parNumIdTramite)
    {
        $endpoint = "tramites/{$parNumIdTramite}/recibo-de-envio";
        try{
            $parametros = [
                'IDT' => $parNumIdTramite
            ];

            $response = self::$servicoPEN->request('GET', $endpoint, [
                'query' => $parametros
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            $mensagem = "Falha no recebimento de recibo de trâmite de envio.";
            $this->fail($mensagem . " - " . $e->getMessage());
        }
    }

    public function enviarProcessoAPI($parametros)
    {
        try {
            $endpoint = "tramites/processo";

            $response = self::$servicoPEN->request('POST', $endpoint, [
                'json' => $parametros
            ]);

            return  json_decode($response->getBody(), true);
    
        } catch (\Exception $e) {
            $mensagem = "Falha no envio de processo";
            $this->fail($mensagem . " - " . $e->getMessage());
        }
    }


    
    public function receberReciboDeTramiteAPI($parNumIdTramite)
    {
        $endpoint = "tramites/{$parNumIdTramite}/recibo";
        try{
            $parametros = [
                'IDT' => $parNumIdTramite
            ];

            $response = self::$servicoPEN->request('GET', $endpoint, [
                'json' => $parametros
            ]);

            return  json_decode($response->getBody(), true);

        } catch (\Exception $e) {
            $mensagem = "Falha no recebimento de recibo de trâmite.";
            $this->fail($mensagem . " - " . $e->getMessage());
        }
    }
}
