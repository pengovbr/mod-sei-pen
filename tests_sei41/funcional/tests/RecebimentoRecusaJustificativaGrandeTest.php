<?php

use \utilphp\util;

class RecebimentoRecusaJustificativaGrandeTest extends CenarioBaseTestCase
{

    protected $destinatarioWs;
    protected $servicoPEN;
    public static $remetente;
    public static $destinatario;    
    public static $processoTeste;
    public static $documentoTeste;
    public static $protocoloTeste;


    public function setUp(): void
    {
        parent::setup();

        // Carregar contexto de testes e dados sobre certificado digital
        $this->destinatarioWs = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Instanciar objeto de teste utilizando o BeSimpleSoap
        $localCertificado = $this->destinatarioWs['LOCALIZACAO_CERTIFICADO_DIGITAL'];
        $senhaCertificado = $this->destinatarioWs['SENHA_CERTIFICADO_DIGITAL'];
        $this->servicoPEN = $this->instanciarApiDeIntegracao($localCertificado, $senhaCertificado);
    }

    /**
     * Teste de tr�mite externo de processo com devolu��o para a mesma unidade de origem
     *
     * @group envio
     *
     * @return void
     */
    public function test_tramitar_processo_da_origem()
    {

        // Configura��o do dados para teste do cen�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        $this->realizarTramiteExternoSemvalidacaoNoRemetente(self::$processoTeste, self::$documentoTeste, self::$remetente, self::$destinatario);
        self::$protocoloTeste = self::$processoTeste["PROTOCOLO"];

        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);
        $id_tramite = $bancoOrgaoA->query("select max(id_tramite) as id_tramite from md_pen_componente_digital where protocolo = ?", array(self::$protocoloTeste));
        //recusa o tramite contendo justificativa grande
        if (array_key_exists("id_tramite", $id_tramite[0])) {
            $id_tramite=$id_tramite[0]["id_tramite"];
        }else{
            $id_tramite=$id_tramite[0]["ID_TRAMITE"];
        }
        $this->recusarTramite($this->servicoPEN, $id_tramite);        
    }

    /**
     * Teste de verifica��o do correto recebimento do processo no destinat�rio
     *
     * @group verificacao_recebimento
     *
     * @depends test_tramitar_processo_da_origem
     *
     * @return void
     */
    public function test_verificar_destino_processo_para_devolucao()
    {
        // Configura��o do dados para teste do cen�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->abrirProcesso(self::$protocoloTeste);
        $this->assertTrue($this->paginaProcesso->processoAberto());

        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $this->validarRecibosTramite(sprintf("Tr�mite externo do Processo %s para %s", self::$protocoloTeste, $unidade) , true, false);
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, false, true, sprintf("An exception occurred while executing 'INSERT INTO juntadas (numeracao_sequencial, movimento, ativo, vinculada, criado_em, atualizado_em, id, uuid, documentos_juntado_id, volumes_id, atividades_id, tarefas_id, comunicacoes_id, origem_dados_id, criado_por, atualizado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)' with params [1, 'DOCUMENTO RECEBIDO VIA INTEGRA\u00c7\u00c3O COM O BARRAMENTO', 1, 0, '2021-12-02 14:21:48', '2021-12-02 14:21:48', 1317074776, '06ba31e8-75ad-4111-82d ..."));

        //Verifica se os �cones de alerta de recusa foram adicionados e se o processo continua aberto na unidade
        $this->paginaBase->navegarParaControleProcesso();
        $this->assertTrue($this->paginaControleProcesso->contemProcesso(self::$protocoloTeste));
        $this->assertTrue($this->paginaControleProcesso->contemAlertaProcessoRecusado(self::$protocoloTeste));
    }

    
    private function recusarTramite($servicoPEN, $id_tramite)
    {
        $justificativa = "An exception occurred while executing 'INSERT INTO juntadas (numeracao_sequencial, movimento, ativo, vinculada, criado_em, atualizado_em, id, uuid, documentos_juntado_id, volumes_id, atividades_id, tarefas_id, comunicacoes_id, origem_dados_id, criado_por, atualizado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)' with params [1, 'DOCUMENTO RECEBIDO VIA INTEGRA\u00c7\u00c3O COM O BARRAMENTO', 1, 0, '2021-12-02 14:21:48', '2021-12-02 14:21:48', 1317074776, '06ba31e8-75ad-4111-82dc-6f451f51825e', 1333864526, null, null, null, null, 3534979787, null, null]: ORA-00001: restri��o exclusiva (SAPIENS.UNIQ_867686DHDKJ97876) violada";

        $parametros = new stdClass();
        $parametros->recusaDeTramite = new stdClass();
        $parametros->recusaDeTramite->IDT = $id_tramite;
        $parametros->recusaDeTramite->justificativa = utf8_encode($justificativa);
        $parametros->recusaDeTramite->motivo = "99";
        return $servicoPEN->recusarTramite($parametros);
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
}
