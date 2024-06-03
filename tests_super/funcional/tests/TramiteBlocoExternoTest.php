<?php
use Tests\Funcional\Sei\Fixtures\{ProtocoloFixture,ProcedimentoFixture,AtividadeFixture,ParticipanteFixture,RelProtocoloAssuntoFixture,AtributoAndamentoFixture,DocumentoFixture,AssinaturaFixture};
/**
 * Enviar bloco simples
 *
 * Execution Groups
 * @group execute_alone_group1
 */
class TramiteBlocoExternoTest extends CenarioBaseTestCase
{
    private $objProtocoloFixture;
    public static $remetente;
    public static $destinatario;
    public static $penOrgaoExternoId;

    function setUp(): void 
    {
        parent::setUp();
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

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

            $objParticipanteFixture = new ParticipanteFixture();
            $objParticipanteFixture->carregar([
                'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
                'IdContato' => 100000006,
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

            $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
            $objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();

            $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
            $objBlocoDeTramiteProtocoloFixtureDTO = $objBlocoDeTramiteProtocoloFixture->carregar([
                'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
                'IdBloco' => $objBlocoDeTramiteDTO->getNumId()
            ]);

        });

    }

    public function teste_tramite_bloco_externo()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
        $this->paginaCadastrarProcessoEmBloco->bntTramitarBloco();
        $this->paginaCadastrarProcessoEmBloco->tramitarProcessoExternamente(
            self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false
        );
        sleep(10);

        $this->sairSistema();
    }
}