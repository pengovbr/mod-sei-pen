<?php

class TramiteProcessoGestorNaoResponsavelPelaUnidadeMapeadaTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $processoTeste;
    public static $documentoTeste;
    public static $protocoloTeste;

    /**
     * Teste de cancelamento de tr�mite com processo (em lote) contendo documento gerado (interno)
     *
     * @group envio
     * @large
     * 
     * @Depends CenarioBaseTestCase::setUpBeforeClass
     *
     * @return void
     */
    public function test_tramite_gestor_nao_responsavel_unidade()
    {
        // Configura��o dos dados para teste do cen�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        $estruturaOrgaoDestino = utf8_encode('�rg�os/ Entidades Legados');
        $unidadeDestino = utf8_encode('super.orgao5.tramita.processoeletronico.gov.br');

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        self::$protocoloTeste = $this->cadastrarProcesso(self::$processoTeste);
        $this->cadastrarDocumentoInterno(self::$documentoTeste);
        $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);
        $this->tramitarProcessoExternamenteGestorNaoResponsavelUnidade($estruturaOrgaoDestino, $unidadeDestino, '');
    }

}
