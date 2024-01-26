<?php

/**
 * Testes de trámite de processos em lote
 *
 * Este mesmo documento deve ser recebido e assinalado como cancelado no destinatário e
 * a devolução do mesmo processo não deve ser impactado pela inserção de outros documentos
 */
class TramiteReordenarDocumentosProcessoTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste1;
    public static $documentoTeste2;
    public static $protocoloTeste;
    public static $documentosTeste;
    public $objProtocoloDTO;

    function setUp(): void
    {
        parent::setUp();
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        $penMapUnidadesFixture = new \PenMapUnidadesFixture(CONTEXTO_ORGAO_A, [
            'id' => self::$remetente['ID_ESTRUTURA'],
            'sigla' => self::$remetente['SIGLA_ESTRUTURA'],
            'nome' => self::$remetente['NOME_UNIDADE']
        ]);
        $penMapUnidadesFixture->cadastrar();

        $parametros = [];
        $objProtocoloFixture = new \ProtocoloFixture();
        $this->objProtocoloDTO = $objProtocoloFixture->carregar($parametros, function($objProtocoloDTO) {
            
            $objProcedimentoFixture = new \ProcedimentoFixture();
            $objProcedimentoDTO = $objProcedimentoFixture->carregar([
                'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
            ]);

            $objAtividadeFixture = new \AtividadeFixture();
            $objAtividadeDTO = $objAtividadeFixture->carregar([
                'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
            ]);

            $objParticipanteFixture = new \ParticipanteFixture();
            $objParticipanteFixture->carregar([
                'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
            ]);

            $objProtocoloAssuntoFixture = new \RelProtocoloAssuntoFixture();
            $objProtocoloAssuntoFixture->carregar([
                'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
            ]);

            $objAtributoAndamentoFixture = new \AtributoAndamentoFixture();
            $objAtributoAndamentoFixture->carregar([
                'IdAtividade' => $objAtividadeDTO->getNumIdAtividade()
            ]);

            $tiposDocumentos = array(81, 8, 34);
            foreach ($tiposDocumentos as $tipoDocumento) {
                $objDocumentoFixture = new \DocumentoFixture();
                $objDocumentoDTO = $objDocumentoFixture->carregar([
                    'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
                    'IdProcedimento' => $objProcedimentoDTO->getDblIdProcedimento(),
                    'IdSerie' => $tipoDocumento
                ]);

                $objAtividadeFixture = new \AtividadeFixture();
                $objAtividadeDTO = $objAtividadeFixture->carregar([
                    'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
                    'IdTarefa' => TarefaRN::$TI_ASSINATURA_DOCUMENTO
                ]);

                $objAssinaturaFixture = new \AssinaturaFixture();
                $objAssinaturaFixture->carregar([
                    'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
                    'IdDocumento' => $objDocumentoDTO->getDblIdDocumento(),
                    'IdAtividade' => $objAtividadeDTO->getNumIdAtividade()
                ]);
            }
        });

    }

    /**
     * Testa a funcionalidade de reordernar documento
     *
     * @return true
     */
    public function test_reordenar_documentos_remetente_devolucao()
    {
        //Aumenta o tempo de timeout devido á quantidade de arquivos
        $this->setSeleniumServerRequestsTimeout(6000);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->abrirProcesso($this->objProtocoloDTO->getStrProtocoloFormatado());
        $this->tramitarProcessoExternamente(
            ['PROTOCOLO' => $this->objProtocoloDTO->getDblIdProtocolo()],
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false
        );

        $this->sairSistema();
        $this->acessarSistema(
            self::$destinatario['URL'],
            self::$destinatario['SIGLA_UNIDADE'],
            self::$destinatario['LOGIN'],
            self::$destinatario['SENHA']
        );
        
        $this->waitUntil(function ($testCase) {
            sleep(5);
            $this->abrirProcesso($this->objProtocoloDTO->getStrProtocoloFormatado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        $this->paginaReordenarProcesso->irParaPaginaMudarOrdem();
        $this->paginaReordenarProcesso->clicarOptionReordenar();
        $this->paginaReordenarProcesso->moverParaBaixo(2);
        $this->paginaReordenarProcesso->salvar();
        sleep(3);

        $callbackEnvio = function ($testCase) {
            $testCase->frame('ifrEnvioProcesso');
            $mensagemSucesso = utf8_encode('Inconsistência identificada no documento de ordem');
            $testCase->assertStringContainsString($mensagemSucesso, $testCase->byCssSelector('body')->text());
            sleep(2);
            $testCase->byXPath("//input[@id='btnFechar']")->click();

            return true;
        };

        $this->tramitarProcessoExternamente(
            ['PROTOCOLO' => $this->objProtocoloDTO->getDblIdProtocolo()],
            self::$remetente['REP_ESTRUTURAS'],
            self::$remetente['NOME_UNIDADE'],
            self::$remetente['SIGLA_UNIDADE_HIERARQUIA'],
            false,
            $callbackEnvio
        );
        
        $this->paginaReordenarProcesso->irParaPaginaMudarOrdem();
        $this->paginaReordenarProcesso->clicarOptionReordenar(2);
        $this->paginaReordenarProcesso->moverParaCima(2);
        $this->paginaReordenarProcesso->salvar();
        sleep(3);

        $this->tramitarProcessoExternamente(
            ['PROTOCOLO' => $this->objProtocoloDTO->getDblIdProtocolo()],
            self::$remetente['REP_ESTRUTURAS'],
            self::$remetente['NOME_UNIDADE'],
            self::$remetente['SIGLA_UNIDADE_HIERARQUIA'],
            false
        );

        $this->sairSistema();
    }
}
