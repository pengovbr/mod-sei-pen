<?php

use \utilphp\util;

/**
 * Execution Groups
 * @group execute_alone_group4
 */
class RecebimentoRecusaJustificativaGrandeTest extends FixtureCenarioBaseTestCase
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
        
        $localCertificado = $this->destinatarioWs['LOCALIZACAO_CERTIFICADO_DIGITAL'];
        $senhaCertificado = $this->destinatarioWs['SENHA_CERTIFICADO_DIGITAL'];
        $this->servicoPEN = $this->instanciarApiDeIntegracao($localCertificado, $senhaCertificado);
    }

    /**
     * Teste de trâmite externo de processo com devolução para a mesma unidade de origem
     *
     * @group envio
     *
     * @return void
     */
    public function test_tramitar_processo_da_origem()
    {

        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        $this->realizarTramiteExternoSemValidacaoNoRemetenteFixture(self::$processoTeste, self::$documentoTeste, self::$remetente, self::$destinatario);
        self::$protocoloTeste = self::$processoTeste["PROTOCOLO"];

        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);
        $id_tramite = $bancoOrgaoA->query("select max(id_tramite) as id_tramite from md_pen_componente_digital where protocolo = ?", array(self::$protocoloTeste));
        //recusa o tramite contendo justificativa grande
        if (array_key_exists("id_tramite", $id_tramite[0])) {
            $id_tramite=$id_tramite[0]["id_tramite"];
        }else{
            $id_tramite=$id_tramite[0]["ID_TRAMITE"];
        }

        sleep(5);
        $this->recusarTramite($id_tramite);        
    }

    /**
     * Teste de verificação do correto recebimento do processo no destinatário
     *
     * @group verificacao_recebimento
     *
     * @depends test_tramitar_processo_da_origem
     *
     * @return void
     */
    public function test_verificar_destino_processo_para_devolucao()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->abrirProcesso(self::$protocoloTeste);
        $this->assertTrue($this->paginaProcesso->processoAberto());

        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $this->validarRecibosTramite(sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTeste, $unidade) , true, false);
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, false, true, sprintf("An exception occurred while executing 'INSERT INTO juntadas (numeracao_sequencial, movimento, ativo, vinculada, criado_em, atualizado_em, id, uuid, documentos_juntado_id, volumes_id, atividades_id, tarefas_id, comunicacoes_id, origem_dados_id, criado_por, atualizado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)' with params [1, 'DOCUMENTO RECEBIDO VIA INTEGRA\u00c7\u00c3O COM O BARRAMENTO', 1, 0, '2021-12-02 14:21:48', '2021-12-02 14:21:48', 1317074776, '06ba31e8-75ad-4111-82d ..."));

        //Verifica se os í­cones de alerta de recusa foram adicionados e se o processo continua aberto na unidade
        $this->paginaBase->navegarParaControleProcesso();
        $this->assertTrue($this->paginaControleProcesso->contemProcesso(self::$protocoloTeste));
        $this->assertTrue($this->paginaControleProcesso->contemAlertaProcessoRecusado(self::$protocoloTeste));
    }

    
    private function recusarTramite($id_tramite)
    {
        $justificativa = "An exception occurred while executing 'INSERT INTO juntadas (numeracao_sequencial, movimento, ativo, vinculada, criado_em, atualizado_em, id, uuid, documentos_juntado_id, volumes_id, atividades_id, tarefas_id, comunicacoes_id, origem_dados_id, criado_por, atualizado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)' with params [1, 'DOCUMENTO RECEBIDO VIA INTEGRA\u00c7\u00c3O COM O BARRAMENTO', 1, 0, '2021-12-02 14:21:48', '2021-12-02 14:21:48', 1317074776, '06ba31e8-75ad-4111-82dc-6f451f51825e', 1333864526, null, null, null, null, 3534979787, null, null]: ORA-00001: restrição exclusiva (SAPIENS.UNIQ_867686DHDKJ97876) violada";

        $parametros = new stdClass();
        $parametros->recusaDeTramite = new stdClass();
        $parametros->recusaDeTramite->IDT = $id_tramite;
        $parametros->recusaDeTramite->justificativa = mb_convert_encoding($justificativa, 'UTF-8', 'ISO-8859-1');
        $parametros->recusaDeTramite->motivo = "99";
        
        return $this->recusarTramiteAPI($parametros);
    }


    private function instanciarApiDeIntegracao($localCertificado, $senhaCertificado) 
    {
        // TODO: lembrar de pegar url dinamicamente quando SOAP for removido
        $strBaseUri = PEN_ENDERECO_WEBSERVICE;
        $arrheaders = [
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
        ];
        
        $strClientGuzzle = new GuzzleHttp\Client([
            'base_uri' => $strBaseUri,
            'timeout'  => ProcessoEletronicoRN::WS_TIMEOUT_CONEXAO,
            'headers'  => $arrheaders,
            'cert'     => [$localCertificado, $senhaCertificado],
        ]);

        return $strClientGuzzle;
    }


    public function recusarTramiteAPI($parametros)
    {
        $idt = $parametros->recusaDeTramite->IDT;
        $justificativa = $parametros->recusaDeTramite->justificativa;
        $motivo = $parametros->recusaDeTramite->motivo;

        $endpoint = "tramites/{$idt}/recusa";

        $objProcessoEletronicoRN = new ProcessoEletronicoRN();
        $parametros = [
            'justificativa' => mb_convert_encoding($objProcessoEletronicoRN->reduzirCampoTexto($justificativa, 1000), 'UTF-8', 'ISO-8859-1'),
            'motivo' => $motivo
        ];

        $response = $this->servicoPEN->request('POST', $endpoint, [
            'json' => $parametros
        ]);

        return $response;
    }
}
