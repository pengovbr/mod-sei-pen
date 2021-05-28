<?php

use \utilphp\util;

class RecebimentoDocumentoAnexadoTest extends CenarioBaseTestCase
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

        // Instanciar objeto de teste utilizando o BeSimpleSoap
        $localCertificado = self::$remetente['LOCALIZACAO_CERTIFICADO_DIGITAL'];
        $senhaCertificado = self::$remetente['SENHA_CERTIFICADO_DIGITAL'];
        self::$servicoPEN = $this->instanciarApiDeIntegracao($localCertificado, $senhaCertificado);

        // Inicia o envio dos três primeiros documentos
        $arrDocumentosPrimeiroEnvio = array(self::$documentoTeste1, self::$documentoTeste2, self::$documentoTeste3);
        $processoTeste = $this->construirMetadadosProcessoTeste(self::$processoTeste, $arrDocumentosPrimeiroEnvio);
        $novoTramite = $this->enviarMetadadosProcesso(self::$servicoPEN, self::$remetente, self::$destinatario, $processoTeste);
        $this->enviarComponentesDigitaisDoTramite(self::$servicoPEN, $novoTramite, $processoTeste);
        $reciboTramite = $this->receberReciboEnvio(self::$servicoPEN, $novoTramite);
        $this->atualizarTramitesPEN(true,false);

        //Verificar recebimento de novo processo administrativo contendo documento avulso enviado
        $this->assertNotNull($novoTramite);
        $this->assertNotNull($reciboTramite);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTeste, $arrDocumentosPrimeiroEnvio, self::$destinatario);
        $reciboTramite = $this->receberReciboTramite(self::$servicoPEN, $novoTramite);
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
        $this->realizarTramiteExternoComValidacaoNoRemetente(self::$processoTeste, $arrDocumentosSegundoEnvio, self::$remetente, self::$destinatario);
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
        $connectionTimeout = 600;
        $options = array(
            'soap_version' => SOAP_1_1
            , 'local_cert' => $localCertificado
            , 'passphrase' => $senhaCertificado
            , 'resolve_wsdl_remote_includes' => true
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


    private function enviarMetadadosProcesso($servicoPEN, $remetente, $destinatario, $processoTeste)
    {
        $parametros = new stdClass();
        $parametros->novoTramiteDeProcesso = new stdClass();
        $parametros->novoTramiteDeProcesso->cabecalho = $this->construirCabecalhoTeste($remetente, $destinatario);
        $parametros->novoTramiteDeProcesso->processo = $processoTeste;
        return $servicoPEN->enviarProcesso($parametros);
    }


    private function enviarComponentesDigitaisDoTramite($servicoPEN, $novoTramite, $processoTeste)
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

    private function receberReciboEnvio($servicoPEN, $novoTramite)
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

    public function gerarDadosProcessoTeste($contextoProducao)
    {
        $processoTeste = parent::gerarDadosProcessoTeste($contextoProducao);
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
}
