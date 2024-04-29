<?php
use Tests\Funcional\Sei\Fixtures\{ProtocoloFixture,ProcedimentoFixture,AtividadeFixture,ParticipanteFixture,RelProtocoloAssuntoFixture,AtributoAndamentoFixture,DocumentoFixture,AssinaturaFixture};
/**
 * Teste de trâmite com envio parcial habilitado
 * 
 * Execution Groups
 * @group execute_alone_group1
 */
class TramiteEnvioParcialTest extends CenarioBaseTestCase
{
    private $objProtocoloFixture;
    public static $remetente;
    public static $destinatario;
    public static $processoTestePrincipal;
    public static $protocoloTestePrincipal;
    public static $documentoTeste1;
    public static $documentoTeste2;
    public static $arrIdMapEnvioParcialOrgaoA;
    public static $arrIdMapEnvioParcialOrgaoB;

    /**
     * @inheritdoc
     * @return void
     */
    function setUp(): void
    {
        parent::setUp();
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
    }

    /*
     * Tramitar processo para o Órgão 2 com envio parcial mapeado
     * @group mapeamento
     *
     * @return void
     */
    public function test_criar_processo_contendo_documento_tramitar_remetente_envio_parcial()
    {
        $this->criarCenarioTramiteEnvioParcialTest();

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        self::$processoTestePrincipal = $this->gerarDadosProcessoTeste(self::$remetente);
        $this->paginaBase->navegarParaControleProcesso();
        self::$protocoloTestePrincipal = $this->cadastrarProcesso(self::$processoTestePrincipal);

        self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        $this->cadastrarDocumentoInterno(self::$documentoTeste1);
        $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);

        $this->tramitarProcessoExternamente(self::$protocoloTestePrincipal, self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'], self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);
        
        $this->sairSistema();
    }

    /*
     * Verificar processo recebido no Órgão 2 com envio parcial mapeado
     * @group mapeamento
     *
     * @return void
     */
    public function test_verificar_processo_recebido_tramitar_destinatario_envio_parcial()
    {
        $documentos = array(self::$documentoTeste1);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTestePrincipal, $documentos, self::$destinatario);
    }

    /*
     * Devolver processo ao Órgão 1 com envio parcial mapeado
     * @group mapeamento
     *
     * @return void
     */
    public function test_criar_documento_processo_recebido_tramitar_destinatario_envio_parcial()
    {        
        $this->acessarSistema(
            self::$destinatario['URL'],
            self::$destinatario['SIGLA_UNIDADE'],
            self::$destinatario['LOGIN'],
            self::$destinatario['SENHA']
        );

        $this->abrirProcesso(self::$protocoloTestePrincipal);

        self::$documentoTeste2 = $this->gerarDadosDocumentoInternoTeste(self::$destinatario);
        $this->cadastrarDocumentoInterno(self::$documentoTeste2);
        $this->assinarDocumento(self::$destinatario['ORGAO'], self::$destinatario['CARGO_ASSINATURA'], self::$destinatario['SENHA']);

        $this->tramitarProcessoExternamente(self::$protocoloTestePrincipal, self::$remetente['REP_ESTRUTURAS'], self::$remetente['NOME_UNIDADE'], self::$remetente['SIGLA_UNIDADE_HIERARQUIA'], false);
        
        $this->sairSistema();
    }

    /*
     * Verificar processo recebido no Órgão 1 com envio parcial mapeado
     * @group mapeamento
     *
     * @return void
     */
    public function test_verificar_processo_recebido_tramitar_remetente_envio_parcial()
    {
        $documentos = array(self::$documentoTeste1, self::$documentoTeste2);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTestePrincipal, $documentos, self::$remetente);
    }

    /**
     * Excluir mapeamentos de Envio Parcial no Remetente e Destinatário 
     * @group mapeamento
     */
    public static function tearDownAfterClass(): void
    {
        $penMapEnvioParcialFixture = new \PenMapEnvioParcialFixture();

        putenv("DATABASE_HOST=org1-database");
        foreach(self::$arrIdMapEnvioParcialOrgaoA as $idMapEnvioParcial) {
            $penMapEnvioParcialFixture->remover([
                'Id' => $idMapEnvioParcial
            ]);
        }

        putenv("DATABASE_HOST=org2-database");
        foreach(self::$arrIdMapEnvioParcialOrgaoB as $idMapEnvioParcial) {
            $penMapEnvioParcialFixture->remover([
                'Id' => $idMapEnvioParcial
            ]);
        }

        parent::tearDownAfterClass();
    }

    /*
     * Criar processo e mapear Envio Parcial no Remetente e Destinatário
     * @group mapeamento
     *
     * @return void
     */
    private function criarCenarioTramiteEnvioParcialTest()
    {
        $parametros = [
            'Descricao' => 'teste'
        ];
        $this->objProtocoloFixture = new ProtocoloFixture();
        $this->objProtocoloFixture->carregar($parametros, function($objProtocoloDTO) {

            $objProcedimentoFixture = new ProcedimentoFixture();
            $objProcedimentoDTO = $objProcedimentoFixture->carregar([
                'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
            ]);

            $objAtividadeFixture = new AtividadeFixture();
            $objAtividadeDTO = $objAtividadeFixture->carregar([
                'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
            ]);

            $objContatoFixture = new ContatoFixture();
            $objContatoDTO = $objContatoFixture->carregar();

            $objParticipanteFixture = new ParticipanteFixture();
            $objParticipanteFixture->carregar([
                'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
                'IdContato' => $objContatoDTO->getNumIdContato()
            ]);

            $objProtocoloAssuntoFixture = new RelProtocoloAssuntoFixture();
            $objProtocoloAssuntoFixture->carregar([
                'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
            ]);

            $objAtributoAndamentoFixture = new AtributoAndamentoFixture();
            $objAtributoAndamentoFixture->carregar([
                'IdAtividade' => $objAtividadeDTO->getNumIdAtividade()
            ]);

            $objDocumentoFixture = new DocumentoFixture();
            $objDocumentoDTO = $objDocumentoFixture->carregar([
                'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
                'IdProcedimento' => $objProcedimentoDTO->getDblIdProcedimento(),
            ]);

            $objAssinaturaFixture = new AssinaturaFixture();
            $objAssinaturaFixture->carregar([
                'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
                'IdDocumento' => $objDocumentoDTO->getDblIdDocumento(),
                'IdAtividade' => $objProtocoloDTO->getDblIdProtocolo()
            ]);

        });

        // Mapear Envio Parcial no Remetente
        self::$arrIdMapEnvioParcialOrgaoA = array();
        putenv("DATABASE_HOST=org1-database");
        $objPenMapEnvioParcialFixture = new PenMapEnvioParcialFixture();
        $objMapEnvioParcial = $objPenMapEnvioParcialFixture->carregar([
            'IdEstrutura' => self::$destinatario['ID_REP_ESTRUTURAS'],
            'StrEstrutura' => self::$destinatario['REP_ESTRUTURAS'],
            'IdUnidadePen' => self::$destinatario['ID_ESTRUTURA'],
            'StrUnidadePen' => self::$destinatario['NOME_UNIDADE']
        ]);
        self::$arrIdMapEnvioParcialOrgaoA[]= $objMapEnvioParcial->getDblId();

        // Mapear Envio Parcial no Destinatário
        self::$arrIdMapEnvioParcialOrgaoB = array();
        putenv("DATABASE_HOST=org2-database");
        $objPenMapEnvioParcialFixture = new PenMapEnvioParcialFixture();
        $objMapEnvioParcial = $objPenMapEnvioParcialFixture->carregar([
            'IdEstrutura' => self::$remetente['ID_REP_ESTRUTURAS'],
            'StrEstrutura' => self::$remetente['REP_ESTRUTURAS'],
            'IdUnidadePen' => self::$remetente['ID_ESTRUTURA'],
            'StrUnidadePen' => self::$remetente['NOME_UNIDADE']
        ]);
        self::$arrIdMapEnvioParcialOrgaoB[]= $objMapEnvioParcial->getDblId();
    }
}