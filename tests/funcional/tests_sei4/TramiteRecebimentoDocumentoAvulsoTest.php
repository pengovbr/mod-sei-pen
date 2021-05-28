<?php

use \utilphp\util;

class RecebimentoDocumentoAvulsoTest extends CenarioBaseTestCase
{
    const ALGORITMO_HASH_DOCUMENTO = 'SHA256';
    const ALGORITMO_HASH_ASSINATURA = 'SHA256withRSA';

    const CONTEUDO_DOCUMENTO_A = "arquivo_pequeno_A.pdf";
    const CONTEUDO_DOCUMENTO_B = "arquivo_pequeno_B.pdf";
    const CONTEUDO_DOCUMENTO_C = "arquivo_pequeno_C.pdf";

    protected $remetente;
    protected $destinatario;
    protected $servicoPEN;

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
        $this->remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $this->destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        // Instanciar objeto de teste utilizando o BeSimpleSoap
        $localCertificado = $this->remetente['LOCALIZACAO_CERTIFICADO_DIGITAL'];
        $senhaCertificado = $this->remetente['SENHA_CERTIFICADO_DIGITAL'];
        $this->servicoPEN = $this->instanciarApiDeIntegracao($localCertificado, $senhaCertificado);
    }


    /**
     * Teste de recebimento documento avulso com 2 componentes digitais
     *
     * @group verificacao_recebimento
     * @large
     *
     * @return void
     */
    public function test_recebimento_documento_avulso_com_2_componentes_digitais()
    {
        // Simular um trâmite chamando a API do Barramento diretamente
        $documentoTeste = $this->gerarDadosDocumentoExternoTeste($this->remetente, array(self::CONTEUDO_DOCUMENTO_A, self::CONTEUDO_DOCUMENTO_B));

        // Simular um trâmite chamando a API do Barramento diretamente
        $metadadosDocumentoTeste = $this->construirMetadadosDocumentoTeste($documentoTeste);
        $novoTramite = $this->enviarMetadadosDocumento($this->servicoPEN, $this->remetente, $this->destinatario, $metadadosDocumentoTeste);
        $this->enviarComponentesDigitaisDoTramite($this->servicoPEN, $novoTramite, $metadadosDocumentoTeste);
        $reciboTramite = $this->receberReciboEnvio($this->servicoPEN, $novoTramite);
        $this->atualizarTramitesPEN(true,false);

        //Verificar recebimento de novo processo administrativo contendo documento avulso enviado
        $this->assertNotNull($novoTramite);
        $this->assertNotNull($reciboTramite);
        $this->realizarValidacaoRecebimentoDocumentoAvulsoNoDestinatario($documentoTeste, $this->destinatario);
    }


    /**
     * Teste de verificação do correto recebimento do documento avulso
     *
     * @group verificacao_recebimento
     * @large
     * 
     * @depends test_recebimento_documento_avulso_com_2_componentes_digitais
     *
     * @return void
     */
    public function test_recebimento_documento_avulso()
    {
        // Simular um trâmite chamando a API do Barramento diretamente
        $documentoTeste = $this->gerarDadosDocumentoExternoTeste($this->remetente, array(self::CONTEUDO_DOCUMENTO_A));

        $metadadosDocumentoTeste = $this->construirMetadadosDocumentoTeste($documentoTeste);
        $novoTramite = $this->enviarMetadadosDocumento($this->servicoPEN, $this->remetente, $this->destinatario, $metadadosDocumentoTeste);
        $this->enviarComponentesDigitaisDoTramite($this->servicoPEN, $novoTramite, $metadadosDocumentoTeste);
        $reciboTramite = $this->receberReciboEnvio($this->servicoPEN, $novoTramite);
        $this->atualizarTramitesPEN(true,false);

        //Verificar recebimento de novo processo administrativo contendo documento avulso enviado
        $this->assertNotNull($novoTramite);
        $this->assertNotNull($reciboTramite);
        $this->realizarValidacaoRecebimentoDocumentoAvulsoNoDestinatario($documentoTeste, $this->destinatario);
    }


    private function receberReciboEnvio($servicoPEN, $novoTramite)
    {
        $dadosTramite = $novoTramite->dadosTramiteDeDocumentoCriado;
        $parametros = new StdClass();
        $parametros->IDT = $dadosTramite->tramite->IDT;
        return $servicoPEN->receberReciboDeEnvio($parametros);
    }

    private function enviarMetadadosDocumento($servicoPEN, $remetente, $destinatario, $documentoTeste)
    {
        $parametros = new stdClass();
        $parametros->novoTramiteDeDocumento = new stdClass();
        $parametros->novoTramiteDeDocumento->cabecalho = $this->construirCabecalhoTeste($remetente, $destinatario);
        $parametros->novoTramiteDeDocumento->documento = $documentoTeste;
        return $servicoPEN->enviarDocumento($parametros);
    }

    private function enviarComponentesDigitaisDoTramite($servicoPEN, $novoTramite, $documentoTeste)
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
}
