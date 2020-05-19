<?php

use \utilphp\util;

class Cenario028Test extends CenarioBaseTestCase
{
    const ALGORITMO_HASH_DOCUMENTO = 'SHA256';
    const ALGORITMO_HASH_ASSINATURA = 'SHA256withRSA';        

    const CONTEUDO_DOCUMENTO_A = "arquivo_001.pdf";
    const CONTEUDO_DOCUMENTO_B = "arquivo_002.pdf";
    const CONTEUDO_DOCUMENTO_C = "arquivo_020.pdf";

    protected $remetente;    
    protected $destinatario;
    protected $servicoPEN;

    public function setUp(): void
    {
        parent::setup();

        // 1 - Carregar configurações representando um sistema de processo eletrônico
        // Carregar contexto de testes e dados sobre certificado digital
        $this->remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $this->destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);        
        
        // Instanciar objeto de teste utilizando o BeSimpleSoap
        $localCertificado = $this->remetente['LOCALIZACAO_CERTIFICADO_DIGITAL'];
        $senhaCertificado = $this->remetente['SENHA_CERTIFICADO_DIGITAL'];
        $this->servicoPEN = $this->instanciarApiDeIntegracao($localCertificado, $senhaCertificado);        
    }

    public function test_recebimento_documento_avulso()
    {
        // Simular um trâmite chamando a API do Barramento diretamente
        $documentoTeste = $this->construirDocumentoTeste(array(self::CONTEUDO_DOCUMENTO_A));
        $novoTramite = $this->enviarMetadadosDocumento($this->servicoPEN, $this->remetente, $this->destinatario, $documentoTeste);
        $this->enviarComponentesDigitaisDoTramite($this->servicoPEN, $novoTramite, $documentoTeste);
        $reciboTramite = $this->receberReciboEnvio($this->servicoPEN, $novoTramite);
                       
        //Verificar recebimento de novo processo administrativo contendo documento avulso enviado
        //TODO: Implementar todas as validações no sistema de destino
        $this->assertNotNull($novoTramite);
        $this->assertNotNull($reciboTramite);        
    }
    
    public function test_recebimento_documento_avulso_com_2_componentes_digitais()
    {
        // Simular um trâmite chamando a API do Barramento diretamente
        $documentoTeste = $this->construirDocumentoTeste(array(self::CONTEUDO_DOCUMENTO_A, self::CONTEUDO_DOCUMENTO_B));
        $novoTramite = $this->enviarMetadadosDocumento($this->servicoPEN, $this->remetente, $this->destinatario, $documentoTeste);
        $this->enviarComponentesDigitaisDoTramite($this->servicoPEN, $novoTramite, $documentoTeste);
        $reciboTramite = $this->receberReciboEnvio($this->servicoPEN, $novoTramite);
                       
        //Verificar recebimento de novo processo administrativo contendo documento avulso enviado
        //TODO: Implementar todas as validações no sistema de destino
        $this->assertNotNull($novoTramite);
        $this->assertNotNull($reciboTramite);        
    }

    public function test_recebimento_documento_avulso_com_2_componentes_digitais_maiores_50mb()
    {
        // Simular um trâmite chamando a API do Barramento diretamente
        $documentoTeste = $this->construirDocumentoTeste(array(self::CONTEUDO_DOCUMENTO_A, self::CONTEUDO_DOCUMENTO_B, self::CONTEUDO_DOCUMENTO_C));
        $novoTramite = $this->enviarMetadadosDocumento($this->servicoPEN, $this->remetente, $this->destinatario, $documentoTeste);
        $this->enviarComponentesDigitaisDoTramite($this->servicoPEN, $novoTramite, $documentoTeste);
        $reciboTramite = $this->receberReciboEnvio($this->servicoPEN, $novoTramite);
                       
        //Verificar recebimento de novo processo administrativo contendo documento avulso enviado
        //TODO: Implementar todas as validações no sistema de destino
        $this->assertNotNull($novoTramite);
        $this->assertNotNull($reciboTramite);        
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
            , 'local_cert' => __DIR__ . "/" . $localCertificado
            , 'passphrase' => $senhaCertificado
            , 'resolve_wsdl_remote_includes' => false
            , 'cache_wsdl'=> BeSimple\SoapCommon\Cache::TYPE_NONE
            , 'connection_timeout' => $connectionTimeout
            , CURLOPT_TIMEOUT => $connectionTimeout
            , CURLOPT_CONNECTTIMEOUT => $connectionTimeout
            , 'trace' => true
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

    private function construirDocumentoTeste($listaComponentes)
    {        
        $componentes = array();
        foreach ($listaComponentes as $ordem => $nomeArquivo) {

            $caminhoArquivo = realpath("./tests/arquivos/$nomeArquivo");
            $fp = fopen($caminhoArquivo, "rb");
            try{
                $conteudo = fread($fp, filesize($caminhoArquivo));

                $tamanhoDocumento = strlen($conteudo);
                $hashDocumento = base64_encode(hash(self::ALGORITMO_HASH_DOCUMENTO, $conteudo, true));
                $componentes[] = array(
                    'nome' => basename($nomeArquivo),
                    'hash' => new SoapVar("<hash algoritmo='SHA256'>$hashDocumento</hash>", XSD_ANYXML),                
                    'tipoDeConteudo' => 'txt',
                    'mimeType' => 'text/plain',
                    'tamanhoEmBytes' => $tamanhoDocumento,
                    'ordem' => $ordem,
                    
                    // Chaves abaixo adicionadas apenas para simplificação dos testes
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
            'descricao' => 'Doc 001',
            'dataHoraDeProducao' => '2017-05-15T03:41:13',
            'dataHoraDeRegistro' => '2013-12-21T09:32:42-02:00',

            'produtor' => array(
                'nome' => 'Teste',
            ),

            'especie' => array(
                'codigo' => 42,
                'nomeNoProdutor' => 'Despacho'
            ),

            'interessado' => array(
                'nome' => 'Usuário de Testes',
            ),

            'componenteDigital' => $componentes,
        );
    }    
}
