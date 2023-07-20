<?php

/**
 * Testes de trâmite em bloco
 *
 */

class TramiteProcessoEmBlocoTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTeste;
    public static $documentoTeste1;
    public static $documentoTeste2;
    public static $protocoloTeste;

    public static $bloco;

    /**
     * Teste inicial de trâmite de um processo contendo um documento movido
     *
     * @group envio
     *
     * @return void
     */

    public function test_tramite_em_bloco_de_processos()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        self::$documentoTeste2 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        
        // Criar bloco para tramite exeterno
        $this->navegarPara('md_pen_tramita_em_bloco');
        $this->paginaTramitarProcessoEmBloco->criarNovoBloco();
        
        self::$protocoloTeste = $this->cadastrarProcesso(self::$processoTeste);

        // criar documento e anexar no bloco
        $this->cadastrarDocumentoInterno(self::$documentoTeste1);
        $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);
        $this->abrirProcesso(self::$protocoloTeste);

        $this->selecionarBlocoDeTramite();

        $this->navegarPara('md_pen_tramita_em_bloco');
        $this->paginaTramitarProcessoEmBloco->btnTramitarBloco();
        
        // tramitar bloco
        $this->paginaTramitarProcessoEmBloco->tramitar();
        sleep(10);
      
        $this->assertTrue(true);

    }

}