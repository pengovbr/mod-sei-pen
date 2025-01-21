<?php

use \utilphp\util;

/**
 * Execution Groups
 * @group execute_alone_group1
 */
class TramiteRecebimentoMultiplosComponentesDigitais extends FixtureCenarioBaseTestCase
{
    const ALGORITMO_HASH_DOCUMENTO = 'SHA256';
    const ALGORITMO_HASH_ASSINATURA = 'SHA256withRSA';

    const CONTEUDO_DOCUMENTO_A = "arquivo_pequeno_A.pdf";
    const CONTEUDO_DOCUMENTO_B = "arquivo_pequeno_B.pdf";
    const CONTEUDO_DOCUMENTO_C = "arquivo_pequeno_C.pdf";

    public static $contextoOrgaoA;
    public static $contextoOrgaoB;
    public static $processoTeste;
    public static $protocoloTeste;
    public static $servicoPEN;
    public static $documentoZip;

    public static $conteudoCompoonenteDigital;



    /**
     * Teste de recebimento dedocumento avulso com 2 componentes digitais
     *
     * @Depends CenarioBaseTestCase::setUpBeforeClass
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setup();

        // Carregar contexto de testes e dados sobre certificado digital
        self::$contextoOrgaoA = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$contextoOrgaoB = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Instanciar objeto de teste utilizando o Guzzle
        $localCertificado = self::$contextoOrgaoB['LOCALIZACAO_CERTIFICADO_DIGITAL'];
        $senhaCertificado = self::$contextoOrgaoB['SENHA_CERTIFICADO_DIGITAL'];
        self::$servicoPEN = $this->instanciarApiDeIntegracao($localCertificado, $senhaCertificado);
    }

    /**
     * Teste de recebimento processo contendo documento com 3 componentes digitais
     *
     * @return void
     */
    public function test_recebimento_processo_com_3_componentes_digitais()
    {
        $remetente = self::$contextoOrgaoB;
        $destinatario = self::$contextoOrgaoA;

        // Simular um trâmite chamando a API do Barramento diretamente
        self::$processoTeste = $this->gerarDadosProcessoTeste($remetente);
        self::$processoTeste['INTERESSADOS'] = trim(substr(self::$processoTeste['INTERESSADOS'], 0, 50));
        self::$processoTeste['PROTOCOLO'] = sprintf('13990.%06d/2020-00', rand(0, 999999));
        self::$documentoZip = $this->gerarDadosDocumentoExternoTeste($remetente, array(
            self::CONTEUDO_DOCUMENTO_A, self::CONTEUDO_DOCUMENTO_B, self::CONTEUDO_DOCUMENTO_C,
            self::CONTEUDO_DOCUMENTO_C, self::CONTEUDO_DOCUMENTO_A, self::CONTEUDO_DOCUMENTO_B,
            self::CONTEUDO_DOCUMENTO_B, self::CONTEUDO_DOCUMENTO_C, self::CONTEUDO_DOCUMENTO_A,
            self::CONTEUDO_DOCUMENTO_A, self::CONTEUDO_DOCUMENTO_B, self::CONTEUDO_DOCUMENTO_C,
        ));

        // Simular um trâmite chamando a API do Barramento diretamente
        $metadadosProcessoTeste = $this->construirMetadadosProcessoTeste(self::$processoTeste, array(self::$documentoZip));
        $novoTramite = $this->enviarMetadadosProcesso($remetente, $destinatario, $metadadosProcessoTeste);

        $this->enviarComponentesDigitaisDoProcesso($novoTramite, $metadadosProcessoTeste);
        $reciboTramite = $this->receberReciboEnvio($novoTramite);
         

        //Verificar recebimento de novo processo administrativo contendo documento avulso enviado
        $this->assertNotNull($novoTramite);
        $this->assertNotNull($reciboTramite);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, array(self::$documentoZip), $destinatario);
        $this->receberReciboTramite($novoTramite);
    }


    /**
     * Teste de trâmite externo de processo com devolução para a mesma unidade de origem
     *
     * @depends test_recebimento_processo_com_3_componentes_digitais
     *
     * @return void
     */
    public function test_devolucao_processo_para_origem()
    {
        // Configuração do dados para teste do cenário
        $remetente = self::$contextoOrgaoA;
        $destinatario = self::$contextoOrgaoB;
        $orgaosDiferentes = $remetente['URL'] != $destinatario['URL'];

        $documentoTeste1 = $this->gerarDadosDocumentoInternoTeste($remetente); 
        $documentoTeste2 = $this->gerarDadosDocumentoExternoTeste($remetente);

        $novosDocumentos =  array($documentoTeste1, $documentoTeste2);
        $this->realizarTramiteExternoComValidacaoNoRemetenteFixture(self::$processoTeste, $novosDocumentos, $remetente, $destinatario);

    }

    /**
     * Teste de recebimento documento avulso com 2 componentes digitais
     *
     * @return void
     */
    public function test_recebimento_documento_avulso_com_2_componentes_digitais()
    {
        $remetente = self::$contextoOrgaoB;
        $destinatario = self::$contextoOrgaoA;

        // Simular um trâmite chamando a API do Barramento diretamente
        $documentoTeste = $this->gerarDadosDocumentoExternoTeste($remetente, array(self::CONTEUDO_DOCUMENTO_A, self::CONTEUDO_DOCUMENTO_B));
        $documentoTeste['INTERESSADOS'] = trim(substr($documentoTeste['INTERESSADOS'], 0, 50));

        // Simular um trâmite chamando a API do Barramento diretamente
        $metadadosDocumentoTeste = $this->construirMetadadosDocumentoAvulsoTeste($documentoTeste);
        $novoTramite = $this->enviarMetadadosDocumento($remetente, $destinatario, $metadadosDocumentoTeste);
        $this->enviarComponentesDigitaisDoDocumentoAvulso($novoTramite, $metadadosDocumentoTeste);
        sleep(5);
        $reciboTramite = $this->receberReciboEnvio($novoTramite);
         

        //Verificar recebimento de novo processo administrativo contendo documento avulso enviado
        $this->assertNotNull($novoTramite);
        $this->assertNotNull($reciboTramite);
        $this->realizarValidacaoRecebimentoDocumentoAvulsoNoDestinatario($documentoTeste, $destinatario);
    }

    private function receberReciboTramite($novoTramite)
    {
        $idt = $novoTramite['IDT'];
        return $this->receberReciboDeTramiteAPI($idt);
    }

    private function receberReciboEnvio($novoTramite)
    {
        // Verifica a origem do envio para determinar se foi realizado por um trâmite de documento avulso ou dentro de um processo.
        if (isset($novoTramite['tramites'])) {
            $numIDT = $novoTramite['tramites'][0]['IDT'];
        }  else {
            $numIDT = $novoTramite['IDT'];
        }

        return $this->receberReciboDeEnvioAPI($numIDT);
    }

    private function enviarMetadadosProcesso($remetente, $destinatario, $processoTeste)
    {
        $parametros = [];
        $parametros['cabecalho'] = $this->construirCabecalhoTeste($remetente, $destinatario);
        $parametros['processo'] = $processoTeste;

        return $this->enviarProcessoAPI($parametros);
    }

    private function enviarMetadadosDocumento($remetente, $destinatario, $documentoTeste)
    {
        $parametros = [];
        $parametros['cabecalho'] = $this->construirCabecalhoDocumentoTeste($remetente, $destinatario);
        $parametros['documento'] = $documentoTeste['documentoEnvio'];

        return $this->enviarDocumentoAPI($parametros);
    }

    private function enviarComponentesDigitaisDoDocumentoAvulso($novoTramite, $documentoTeste)
    {
        $parametros = [];
        $arrComponentesDigitais = $documentoTeste['componenteEnvio'];

        $dadosDoComponenteDigital['protocolo'] = $documentoTeste['documentoEnvio']['protocolo'];
        $dadosDoComponenteDigital['ticketParaEnvioDeComponentesDigitais'] = $novoTramite['ticketParaEnvioDeComponentesDigitais'];
        $parametros['dadosDoComponenteDigital'] = $dadosDoComponenteDigital;

        foreach ($arrComponentesDigitais as $componentesDigitais) {
            $parametros['dadosDoComponenteDigital']['hashDoComponenteDigital'] = $componentesDigitais['hashDocumento'];
            self::$conteudoCompoonenteDigital[$componentesDigitais['hashDocumento']] = $componentesDigitais['conteudo'];
            $this->enviarComponenteDigitalAPI($parametros);
        }   
    }

    private function enviarComponentesDigitaisDoProcesso($novoTramite, $processoTeste)
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


    private function construirCabecalhoDocumentoTeste($remetente, $destinatario)
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
        $dadosDocumentoTest['INTERESSADOS'] = $dadosDocumentoTest['INTERESSADOS'];
        $dadosDocumentoTest['DESCRICAO'] = trim(substr($dadosDocumentoTest['DESCRICAO'], 0, 10));
        return $dadosDocumentoTest;

    }

    private function construirMetadadosDocumentoAvulsoTeste($documentoTeste)
    {
        $componentes = array();
        $listaComponentes = is_array($documentoTeste['ARQUIVO']) ? $documentoTeste['ARQUIVO'] : array($documentoTeste['ARQUIVO']);
        $componenteEnvio = [];
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

                // Utilizada exclusivamente para o envio de componentes digitais. 
                // Inclui o conteúdo do documento anexado, simplificando o processo de integração.
                $componenteEnvio[] = array(
                    'hashDocumento'  => $hashDocumento,
                    'conteudo' =>  $conteudo    
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

        return array(
            'documentoEnvio' => $documentoEnvio,
            'componenteEnvio' => $componenteEnvio
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

    public function enviarDocumentoAPI($parametros)
    {
        try {
            $endpoint = "tramites/documento";

            $response = self::$servicoPEN->request('POST', $endpoint, [
                'json' => $parametros
            ]);

            return  json_decode($response->getBody(), true);
    
        } catch (\Exception $e) {
            $mensagem = "Falha no envio de documento avulso";
            $this->fail($mensagem . " - " . $e->getMessage());
        }
    }

}
