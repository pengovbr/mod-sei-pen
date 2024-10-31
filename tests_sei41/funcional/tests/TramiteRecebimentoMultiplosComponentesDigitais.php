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
        $novoTramite = $this->enviarMetadadosProcesso(self::$servicoPEN, $remetente, $destinatario, $metadadosProcessoTeste);

        $this->enviarComponentesDigitaisDoProcesso(self::$servicoPEN, $novoTramite, $metadadosProcessoTeste);
        $reciboTramite = $this->receberReciboEnvioProcesso(self::$servicoPEN, $novoTramite);
         

        //Verificar recebimento de novo processo administrativo contendo documento avulso enviado
        $this->assertNotNull($novoTramite);
        $this->assertNotNull($reciboTramite);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, array(self::$documentoZip), $destinatario);
        $this->receberReciboTramite(self::$servicoPEN, $novoTramite);
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
        $novoTramite = $this->enviarMetadadosDocumento(self::$servicoPEN, $remetente, $destinatario, $metadadosDocumentoTeste);
        $this->enviarComponentesDigitaisDoDocumentoAvulso(self::$servicoPEN, $novoTramite, $metadadosDocumentoTeste);
        $reciboTramite = $this->receberReciboEnvioDocumentoAvulso(self::$servicoPEN, $novoTramite);
         

        //Verificar recebimento de novo processo administrativo contendo documento avulso enviado
        $this->assertNotNull($novoTramite);
        $this->assertNotNull($reciboTramite);
        $this->realizarValidacaoRecebimentoDocumentoAvulsoNoDestinatario($documentoTeste, $destinatario);
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

                    // Chaves abaixo adicionadas apenas para simplificaçÃ£o dos testes
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
                'nome' => mb_convert_encoding(util::random_string(20), 'UTF-8', 'ISO-8859-1'),
            ),

            'especie' => array(
                'codigo' => 42,
                'nomeNoProdutor' => mb_convert_encoding(util::random_string(20), 'UTF-8', 'ISO-8859-1')
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

                    // Chaves abaixo adicionadas apenas para simplificaçÃ£o dos testes
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
                'nome' => mb_convert_encoding(util::random_string(20), 'UTF-8', 'ISO-8859-1'),
                'numeroDeIdentificacao' => '999999',
            ),

            'especie' => array(
                'codigo' => 42,
                'nomeNoProdutor' => mb_convert_encoding(util::random_string(20), 'UTF-8', 'ISO-8859-1')
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
                'nome' => mb_convert_encoding(util::random_string(20), 'UTF-8', 'ISO-8859-1'),
            ),
            'interessado' => array(
                'nome' => $processoTeste['INTERESSADOS'],
            ),
            'documento' => $metadadosDocumentos,
        );
    }

}
