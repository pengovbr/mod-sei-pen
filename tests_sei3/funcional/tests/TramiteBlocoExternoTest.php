<?php

/**
 * EnviarProcessoTest
 * @group group
 */
class TramiteBlocoExternoTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $penOrgaoExternoId;
    public static $processoTeste;
    public static $documentoTeste;
    public static $protocoloTeste;
    public static $bloco;
    public static $documentoTeste1;

    function setUp(): void 
    {
        parent::setUp();
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

    }

    public function teste_incluir_processo_em_bloco()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->cadastrarBlocoDeTramite();
        $this->paginaBase->navegarParaControleProcesso();
        $this->cadastrarProcessos();
        $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
        $this->paginaBase->navegarParaControleProcesso();
        // $this->paginaTramiteEmBloco->selecionarProcessos([self::$protocoloTeste]);
        $this->paginaTramiteEmBloco->selecionarProcessos(self::$protocoloTeste);
        $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
        $this->paginaTramiteEmBloco->clicarSalvar();

        sleep(2);
        $mensagem = $this->paginaTramiteEmBloco->buscarMensagemAlerta();

        $this->assertStringContainsString(
            utf8_encode('Processo(s) incluído(s) com sucesso no bloco'),
            $mensagem
        );

        $this->paginaBase->navegarParaControleProcesso();
        $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
        $this->paginaCadastrarProcessoEmBloco->bntTramitarBloco();
        $this->paginaCadastrarProcessoEmBloco->tramitarProcessoExternamente(
            self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false
        );

        // $this->paginaBase->sairSistema();
    }

        /**
     * Cadastra o bloco de tramite
     * @return void
     */
    private function cadastrarBlocoDeTramite()
    {

        // Configuração do dados para teste do cenário
        $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
        $this->paginaCadastrarProcessoEmBloco->novoBlocoDeTramite();
        $this->paginaCadastrarProcessoEmBloco->criarNovoBloco();
        $this->paginaCadastrarProcessoEmBloco->btnSalvar();

        sleep(1);
        $mensagemRetornoAlert = $this->paginaCadastroOrgaoExterno->buscarMensagemAlerta();
        $menssagemValidacao = utf8_encode('Bloco de Trâmite externo criado com sucesso!');

        $this->assertStringContainsString(
            $menssagemValidacao,
            $mensagemRetornoAlert
        );
    }

    /**
     * Cadastra o bloco de tramite
     * @return void
     */
    private function cadastrarProcessos()
    {
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$protocoloTeste = $this->cadastrarProcesso(self::$processoTeste);

        self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        // self::$documentoTeste2 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

        // $documentos = array(self::$documentoTeste1, self::$documentoTeste2);

        $documentosTeste = array_key_exists('TIPO', self::$documentoTeste1) ? array(self::$documentoTeste1) : self::$documentoTeste1;
        foreach ($documentosTeste as $doc) {
            if ($doc['TIPO'] == 'G') {
                $this->cadastrarDocumentoInterno($doc);
                $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);
            } else if ($doc['TIPO'] == 'R') {
                $this->cadastrarDocumentoExterno($doc);
            }
        }
    }
}