<?php

use \utilphp\util;

/**
 * Execution Groups
 * @group execute_parallel_group3
 */
class TramiteRecebimentoInteressadosDuplicadosTest extends FixtureCenarioBaseTestCase
{
    const ALGORITMO_HASH_DOCUMENTO = 'SHA256';
    const ALGORITMO_HASH_ASSINATURA = 'SHA256withRSA';

    const CONTEUDO_DOCUMENTO_A = "arquivo_pequeno_A.pdf";

    public static $processoTeste;
    public static $remetente;
    public static $destinatario;
    public static $servicoPEN;
    public static $documentoTeste1;

    public static $conteudoCompoonenteDigital;

    /**
     * Teste de envio de metadados do processo contendo interessados duplicados
     *
     * Inicialmente são enviados 2 interessados com o mesmo nome
     * 
     * @group envio
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
        self::$documentoTeste1 = $this->gerarDadosDocumentoExternoTeste(self::$remetente, self::CONTEUDO_DOCUMENTO_A);

        // Atribui dois interessados utilizando o mesmo nome
        self::$processoTeste['INTERESSADOS'] = array("Interessado com mesmo nome", "Interessado com mesmo nome");

        // Instanciar objeto de teste utilizando o Guzzle
        $localCertificado = self::$remetente['LOCALIZACAO_CERTIFICADO_DIGITAL'];
        $senhaCertificado = self::$remetente['SENHA_CERTIFICADO_DIGITAL'];
        self::$servicoPEN = $this->instanciarApiDeIntegracao($localCertificado, $senhaCertificado);

        // Inicia o envio do processo
        $arrDocumentosPrimeiroEnvio = array(self::$documentoTeste1);
        $processoTeste = $this->construirMetadadosProcessoTeste(self::$processoTeste, $arrDocumentosPrimeiroEnvio);
        $novoTramite = $this->enviarMetadadosProcesso(self::$remetente, self::$destinatario, $processoTeste);
        $this->enviarComponentesDigitaisDoTramite($novoTramite, $processoTeste);
        $reciboTramite = $this->receberReciboEnvio($novoTramite);
         

        //Verifica recebimento de novo processo administrativo contendo documento avulso enviado
        $this->assertNotNull($novoTramite);
        $this->assertNotNull($reciboTramite);

        //Verifica se somente um interessado foi registrado para o processo
        self::$processoTeste['INTERESSADOS'] = "Interessado com mesmo nome";
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, $arrDocumentosPrimeiroEnvio, self::$destinatario);
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

    private function receberReciboTramite($servicoPEN, $novoTramite)
    {
        $dadosTramite = $novoTramite->dadosTramiteDeProcessoCriado;
        $parametros = new StdClass();
        $parametros->IDT = $dadosTramite->IDT;
        return $servicoPEN->receberReciboDeTramite($parametros);
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

        $arrInteressados = array_map(function($item) {
            return array('nome' => mb_convert_encoding($item, 'UTF-8', 'ISO-8859-1'));
        }, 
        $processoTeste['INTERESSADOS']);

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
            'interessados' => $arrInteressados,
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

                self::$conteudoCompoonenteDigital = [$hashDocumento => $conteudo];
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
}
