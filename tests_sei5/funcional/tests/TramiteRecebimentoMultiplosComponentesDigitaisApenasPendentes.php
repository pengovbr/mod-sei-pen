<?php

use \utilphp\util;

class TramiteRecebimentoMultiplosComponentesDigitaisApenasPendentes extends CenarioBaseTestCase
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

    public static $totalDocumentos;


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

        // Instanciar objeto de teste utilizando o BeSimpleSoap
        $localCertificado = self::$contextoOrgaoA['LOCALIZACAO_CERTIFICADO_DIGITAL'];
        $senhaCertificado = self::$contextoOrgaoA['SENHA_CERTIFICADO_DIGITAL'];
        self::$servicoPEN = $this->instanciarApiDeIntegracao($localCertificado, $senhaCertificado);
    }

    /**
     * Teste de recebimento processo contendo documento com 3 componentes digitais
     *
     * @return void
     */
    public function test_recebimento_processo_com_3_componentes_digitais()
    {
        $remetente = self::$contextoOrgaoA;
        $destinatario = self::$contextoOrgaoB;

        // Simular um tr�mite chamando a API do Barramento diretamente
        self::$processoTeste = $this->gerarDadosProcessoTeste($remetente);
        self::$processoTeste['INTERESSADOS'] = trim(substr(self::$processoTeste['INTERESSADOS'], 0, 50));
        self::$processoTeste['PROTOCOLO'] = sprintf('13990.%06d/2020-00', rand(0, 999999));
        self::$documentoZip = $this->gerarDadosDocumentoExternoTeste($remetente, array(
            self::CONTEUDO_DOCUMENTO_A, self::CONTEUDO_DOCUMENTO_B, self::CONTEUDO_DOCUMENTO_C
        ));

        self::$totalDocumentos = array(self::$documentoZip);

        // Simular um tr�mite chamando a API do Barramento diretamente
        $metadadosProcessoTeste = $this->construirMetadadosProcessoTeste(self::$processoTeste, array(self::$documentoZip));
        $novoTramite = $this->enviarMetadadosProcesso(self::$servicoPEN, $remetente, $destinatario, $metadadosProcessoTeste);

        $this->enviarComponentesDigitaisDoProcesso(self::$servicoPEN, $novoTramite, $metadadosProcessoTeste);
        $reciboTramite = $this->receberReciboEnvioProcesso(self::$servicoPEN, $novoTramite);
        $this->atualizarTramitesPEN(true,false);

        //Verificar recebimento de novo processo administrativo contendo documento avulso enviado
        $this->assertNotNull($novoTramite);
        $this->assertNotNull($reciboTramite);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, array(self::$documentoZip), $destinatario);
        $this->receberReciboTramite(self::$servicoPEN, $novoTramite);
    }


    /**
     * Teste de tr�mite externo de processo com devolu��o para a mesma unidade de origem
     *
     * @depends test_recebimento_processo_com_3_componentes_digitais
     *
     * @return void
     */
    public function test_devolucao_processo_para_origem_1()
    {
        // Configura��o do dados para teste do cen�rio
        $remetente = self::$contextoOrgaoB;
        $destinatario = self::$contextoOrgaoA;

        $documentoTesteInterno = $this->gerarDadosDocumentoInternoTeste($remetente);

        $novosDocumentos =  array($documentoTesteInterno);
        $this->realizarTramiteExternoComValidacaoNoRemetente(self::$processoTeste, $novosDocumentos, $remetente, $destinatario);
        self::$totalDocumentos = array_merge(self::$totalDocumentos, array($documentoTesteInterno));
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, self::$totalDocumentos, $destinatario);
    }


    /**
     *
     * @depends test_devolucao_processo_para_origem_1
     *
     * @return void
     */
    public function test_devolucao_processo_para_destino_2()
    {
        // Configura��o do dados para teste do cen�rio
        $remetente = self::$contextoOrgaoA;
        $destinatario = array_slice(self::$contextoOrgaoB, 0);
        $destinatario['SIGLA_UNIDADE'] = $destinatario['SIGLA_UNIDADE_SECUNDARIA'];
        $destinatario['NOME_UNIDADE'] = $destinatario['NOME_UNIDADE_SECUNDARIA'];
        $destinatario['SIGLA_UNIDADE_HIERARQUIA'] = $destinatario['SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA'];

        $documentoTesteExterno = $this->gerarDadosDocumentoExternoTeste($remetente, self::CONTEUDO_DOCUMENTO_A);

        $novosDocumentos =  array($documentoTesteExterno);
        $this->realizarTramiteExternoComValidacaoNoRemetente(self::$processoTeste, $novosDocumentos, $remetente, $destinatario);
        self::$totalDocumentos = array_merge(self::$totalDocumentos, array($documentoTesteExterno));
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, self::$totalDocumentos, $destinatario);
    }


    private function receberReciboEnvioDocumentoAvulso($servicoPEN, $novoTramite)
    {
        $dadosTramite = $novoTramite->dadosTramiteDeDocumentoCriado;
        $parametros = new StdClass();
        $parametros->IDT = $dadosTramite->tramite->IDT;
        return $servicoPEN->receberReciboDeEnvio($parametros);
    }

    private function receberReciboEnvioProcesso($servicoPEN, $novoTramite)
    {
        $dadosTramite = $novoTramite->dadosTramiteDeProcessoCriado;
        $parametros = new StdClass();
        $parametros->IDT = $dadosTramite->IDT;
        return $servicoPEN->receberReciboDeEnvio($parametros);
    }

    private function receberReciboTramite($servicoPEN, $novoTramite)
    {
        $dadosTramite = $novoTramite->dadosTramiteDeProcessoCriado;
        $parametros = new StdClass();
        $parametros->IDT = $dadosTramite->IDT;
        return $servicoPEN->receberReciboDeTramite($parametros);
    }

    private function enviarMetadadosProcesso($servicoPEN, $remetente, $destinatario, $processoTeste)
    {
        $parametros = new stdClass();
        $parametros->novoTramiteDeProcesso = new stdClass();
        $parametros->novoTramiteDeProcesso->cabecalho = $this->construirCabecalhoTeste($remetente, $destinatario);
        $parametros->novoTramiteDeProcesso->processo = $processoTeste;
        return $servicoPEN->enviarProcesso($parametros);
    }

    private function enviarMetadadosDocumento($servicoPEN, $remetente, $destinatario, $documentoTeste)
    {
        $parametros = new stdClass();
        $parametros->novoTramiteDeDocumento = new stdClass();
        $parametros->novoTramiteDeDocumento->cabecalho = $this->construirCabecalhoTeste($remetente, $destinatario);
        $parametros->novoTramiteDeDocumento->documento = $documentoTeste;
        return $servicoPEN->enviarDocumento($parametros);
    }

    private function enviarComponentesDigitaisDoDocumentoAvulso($servicoPEN, $novoTramite, $documentoTeste)
    {
        $dadosTramite = $novoTramite->dadosTramiteDeDocumentoCriado;
        foreach ($documentoTeste['componenteDigital'] as $item) {
            $dadosDoComponenteDigital = new stdClass();
            $dadosDoComponenteDigital->protocolo = $documentoTeste['protocolo'];
            $dadosDoComponenteDigital->hashDoComponenteDigital = $item['valorHash'];
            $dadosDoComponenteDigital->conteudoDoComponenteDigital = new SoapVar($item['conteudo'], XSD_BASE64BINARY);
            $dadosDoComponenteDigital->ticketParaEnvioDeComponentesDigitais = $dadosTramite->ticketParaEnvioDeComponentesDigitais;

            $parametros = new stdClass();
            $parametros->dadosDoComponenteDigital = $dadosDoComponenteDigital;
            $servicoPEN->enviarComponenteDigital($parametros);
        }
    }

    private function enviarComponentesDigitaisDoProcesso($servicoPEN, $novoTramite, $processoTeste)
    {
        $dadosTramite = $novoTramite->dadosTramiteDeProcessoCriado;
        foreach ($processoTeste['documento'] as $documentoTeste) {
            foreach ($documentoTeste['componenteDigital'] as $item) {
                $dadosDoComponenteDigital = new stdClass();
                $dadosDoComponenteDigital->protocolo = $processoTeste['protocolo'];
                $dadosDoComponenteDigital->hashDoComponenteDigital = $item['valorHash'];
                $dadosDoComponenteDigital->conteudoDoComponenteDigital = new SoapVar($item['conteudo'], XSD_BASE64BINARY);
                $dadosDoComponenteDigital->ticketParaEnvioDeComponentesDigitais = $dadosTramite->ticketParaEnvioDeComponentesDigitais;

                $parametros = new stdClass();
                $parametros->dadosDoComponenteDigital = $dadosDoComponenteDigital;
                $servicoPEN->enviarComponenteDigital($parametros);
            }
        }
    }

    private function instanciarApiDeIntegracao($localCertificado, $senhaCertificado)
    {
        $connectionTimeout = 600;
        $options = array(
            'soap_version' => SOAP_1_1
            , 'local_cert' => $localCertificado
            , 'passphrase' => $senhaCertificado
            , 'resolve_wsdl_remote_includes' => true
            , 'cache_wsdl'=> BeSimple\SoapCommon\Cache::TYPE_NONE
            , 'connection_timeout' => $connectionTimeout
            , CURLOPT_TIMEOUT => $connectionTimeout
            , CURLOPT_CONNECTTIMEOUT => $connectionTimeout
            , 'encoding' => 'UTF-8'
            , 'attachment_type' => BeSimple\SoapCommon\Helper::ATTACHMENTS_TYPE_MTOM
            , 'ssl' => array(
                'allow_self_signed' => true,
            ),
        );

        return new BeSimple\SoapClient\SoapClient(PEN_ENDERECO_WEBSERVICE, $options);

    }

    private function construirCabecalhoTeste($remetente, $destinatario)
    {
        $cabecalho = new stdClass();
        $cabecalho->remetente = new stdClass();
        $cabecalho->remetente->identificacaoDoRepositorioDeEstruturas = $remetente['ID_REP_ESTRUTURAS'];
        $cabecalho->remetente->numeroDeIdentificacaoDaEstrutura = $remetente['ID_ESTRUTURA'];

        $cabecalho->destinatario = new stdClass();
        $cabecalho->destinatario->identificacaoDoRepositorioDeEstruturas = $destinatario['ID_REP_ESTRUTURAS'];
        $cabecalho->destinatario->numeroDeIdentificacaoDaEstrutura =$destinatario['ID_ESTRUTURA'];

        $cabecalho->urgente = false;
        $cabecalho->motivoDaUrgencia = null;
        $cabecalho->obrigarEnvioDeTodosOsComponentesDigitais = false;
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
                    'hash' => new SoapVar("<hash algoritmo='SHA256'>$hashDocumento</hash>", XSD_ANYXML),
                    'tipoDeConteudo' => 'txt',
                    'mimeType' => 'text/plain',
                    'tamanhoEmBytes' => $tamanhoDocumento,
                    'ordem' => $ordem + 1,

                    // Chaves abaixo adicionadas apenas para simplifica�ão dos testes
                    'valorHash' => $hashDocumento,
                    'conteudo' => $conteudo,
                );
            } finally {
               fclose($fp);
            }
        }

        return array(
            'protocolo' => '13990.000181/2020-00',
            'nivelDeSigilo' => 1,
            'descricao' => $documentoTeste['DESCRICAO'],
            'dataHoraDeProducao' => '2017-05-15T03:41:13',
            'dataHoraDeRegistro' => '2013-12-21T09:32:42-02:00',

            'produtor' => array(
                'nome' => utf8_encode(util::random_string(20)),
            ),

            'especie' => array(
                'codigo' => 42,
                'nomeNoProdutor' => utf8_encode(util::random_string(20))
            ),

            'interessado' => array(
                'nome' => $documentoTeste['INTERESSADOS'],
            ),

            'componenteDigital' => $componentes,
        );
    }

    private function construirMetadadosDocumentoTeste($documentoTeste, $ordemDocumento)
    {
        $componentes = array();
        $listaComponentes = is_array($documentoTeste['ARQUIVO']) ? $documentoTeste['ARQUIVO'] : array($documentoTeste['ARQUIVO']);

        foreach ($listaComponentes as $index => $caminhoArquivo) {
            $ordemComponente = $index + 1;
            $caminhoArquivo = realpath($caminhoArquivo);
            $fp = fopen($caminhoArquivo, "rb");
            try{
                $conteudo = fread($fp, filesize($caminhoArquivo));
                $tamanhoDocumento = strlen($conteudo);
                $hashDocumento = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $conteudo, true));
                $nomeArquivo = basename($caminhoArquivo);
                $componentes[] = array(
                    'nome' => $nomeArquivo,
                    'hash' => new SoapVar("<hash algoritmo='SHA256'>$hashDocumento</hash>", XSD_ANYXML),
                    'tipoDeConteudo' => 'txt',
                    'mimeType' => 'text/plain',
                    'tamanhoEmBytes' => $tamanhoDocumento,
                    'ordem' => $ordemComponente,

                    // Chaves abaixo adicionadas apenas para simplifica�ão dos testes
                    'valorHash' => $hashDocumento,
                    'conteudo' => $conteudo,
                );
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
                'nome' => utf8_encode(util::random_string(20)),
                'numeroDeIdentificacao' => '999999',
            ),

            'especie' => array(
                'codigo' => 42,
                'nomeNoProdutor' => utf8_encode(util::random_string(20))
            ),

            'interessado' => array(
                'nome' => $documentoTeste['INTERESSADOS'],
            ),

            'componenteDigital' => $componentes,
        );

        if(!is_null($documentoTeste['ORDEM_DOCUMENTO_REFERENCIADO'])){
            $documentoDoProcesso['ordemDoDocumentoReferenciado'] = intval($documentoTeste['ORDEM_DOCUMENTO_REFERENCIADO']);
        }

        return $documentoDoProcesso;
    }


    private function construirMetadadosProcessoTeste($processoTeste, $documentosTeste)
    {
        $metadadosDocumentos = array();
        foreach ($documentosTeste as $indice => $documentoTeste) {
            $metadadosDocumentos[] = $this->construirMetadadosDocumentoTeste($documentoTeste, $indice + 1);
        }

        return array(
            'protocolo' => $processoTeste['PROTOCOLO'],
            'nivelDeSigilo' => 1,
            'processoDeNegocio' => $processoTeste['TIPO_PROCESSO'],
            'descricao' => $processoTeste['DESCRICAO'],
            'dataHoraDeProducao' => '2017-05-15T03:41:13',
            'dataHoraDeRegistro' => '2013-12-21T09:32:42-02:00',
            'produtor' => array(
                'nome' => utf8_encode(util::random_string(20)),
            ),
            'interessado' => array(
                'nome' => $processoTeste['INTERESSADOS'],
            ),
            'documento' => $metadadosDocumentos,
        );
    }

}
