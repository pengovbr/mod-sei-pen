<?php
/*
Escopo do caso de teste:
    �rg�o 1:
        1-criar Processo Principal ($processoTestePrincipal) (�rg�o 1)
        2-criar Documento Externo (documentoTeste1) no Processo Principal
        3-criar Documento Interno (documentoTeste2) no Processo Principal
        4-criar Documento Interno (documentoTeste3) no Processo Principal
        5-tramitar Processo Principal para o �rg�o 2 com valida��o no remetente
    �rg�o 2:
        6-verificar correto recebimento do processo e seus documentos no destino (�rg�o 2)
        7-criar Processo Secund�rio ($processoTesteSecundario) (�rg�o 2)
        8-mover documento externo (documentoTeste1) do Processo Principal para o Processo Secund�rio
        9-mover documento interno (documentoTeste2) do Processo Principal para o Processo Secund�rio
        10-criar documento externo (documentoTeste4) no Processo Principal
        11-criar documento interno (documentoTeste5) no Processo Principal
        12-tramitar Processo Principal para o �rg�o 1 com valida��o no remetente
    �rg�o 1:
        13-verificar correto recebimento do processo no destino (�rg�o 1)
        14-criar documento interno (documentoTeste6) no Processo Principal
        15-tramitar Processo Principal para o �rg�o 2 com valida��o no remetente
    �rg�o 2:
        16-verificar correto recebimento do processo no destino (�rg�o 2)   
        17-criar documento interno (documentoTeste7) no Processo Principal
        18-tramitar Processo Principal para o �rg�o 1 com valida��o no remetente
    �rg�o 1:
        19-verificar correto recebimento do processo no destino (�rg�o 1)
*/

class TramiteProcessoContendoDocumentoMovidoDestino extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTestePrincipal;
    public static $processoTesteSecundario;
    public static $protocoloTestePrincipal;
    public static $protocoloTesteSecundario;
    public static $documentoTeste1;
    public static $documentoTeste2;
    public static $documentoTeste3;
    public static $documentoTeste4;
    public static $documentoTeste5;
    public static $documentoTeste6;
    public static $documentoTeste7;
    
    /*  
    Escopo da fun��o:
        �rg�o 1:
            1-criar Processo Principal ($processoTestePrincipal) (�rg�o 1)
            2-criar Documento Externo (documentoTeste1) no Processo Principal
            3-criar Documento Interno (documentoTeste2) no Processo Principal
            4-criar Documento Interno (documentoTeste3) no Processo Principal
            5-tramitar Processo Principal para o �rg�o 2 com valida��o no remetente

    @group TramiteProcessoContendoDocumentoMovidoDestino
    @large
    @Depends CenarioBaseTestCase::setUpBeforeClass
    @return void
    */
    public function test_criar_processo_contendo_documentos_tramitar_remetente()
    {
        // definir �rg�o 1 como remetente e �rg�o 2 como destinat�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // acessar remetente (�rg�o 1)
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        // 1-criar Processo Principal ($processoTestePrincipal) (�rg�o 1)
        self::$processoTestePrincipal = $this->gerarDadosProcessoTeste(self::$remetente);
        $this->paginaBase->navegarParaControleProcesso();
        self::$protocoloTestePrincipal = $this->cadastrarProcesso(self::$processoTestePrincipal);

        // 2-criar Documento Externo (documentoTeste1) no Processo Principal
        self::$documentoTeste1 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);
        $this->cadastrarDocumentoExterno(self::$documentoTeste1);

        // 3-criar Documento Interno (documentoTeste2) no Processo Principal
        self::$documentoTeste2 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        $this->cadastrarDocumentoInterno(self::$documentoTeste2);
        $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);

        // 4-criar Documento Interno (documentoTeste3) no Processo Principal
        self::$documentoTeste3 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        $this->cadastrarDocumentoInterno(self::$documentoTeste3);
        $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);

        // 5-tramitar Processo Principal para o �rg�o 2 com valida��o no remetente
        $this->tramitarProcessoExternamente(self::$protocoloTestePrincipal, self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'], self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);

        // verificar se situa��o atual do processo est� como bloqueado no remetente
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];
        $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $this->atualizarTramitesPEN();
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(utf8_encode("Processo em tr�mite externo para "), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        // validar se recibo de tr�mite foi armazenado para o processo (envio e conclus�o)
        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Tr�mite externo do Processo %s para %s", self::$protocoloTestePrincipal, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);

        // validar hist�rico de tr�mite do processo
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);

        // verificar se processo est� na lista de Processos Tramitados Externamente
        $deveExistir = self::$remetente['URL'] != self::$destinatario['URL'];
        $this->validarProcessosTramitados(self::$protocoloTestePrincipal, $orgaosDiferentes);
    }

    /*   
    Escopo da fun��o:
        �rg�o 2:
            6-verificar correto recebimento do processo e seus documentos no destino (�rg�o 2)
            7-criar Processo Secund�rio ($processoTesteSecundario) (�rg�o 2)
            8-mover documento externo (documentoTeste1) do Processo Principal para o Processo Secund�rio
            9-mover documento interno (documentoTeste2) do Processo Principal para o Processo Secund�rio
            10-criar documento externo (documentoTeste4) no Processo Principal
            11-criar documento interno (documentoTeste5) no Processo Principal
            12-tramitar Processo Principal para o �rg�o 1 com valida��o no remetente
    
    @group TramiteProcessoContendoDocumentoMovidoDestino
    @large
    @depends test_criar_processo_contendo_documentos_tramitar_remetente
    @return void
    */
    public function test_criar_mover_incluir_documentos_devolver_processo_remetente()
    {   
        // 6-verificar correto recebimento do processo e seus documentos no destino (�rg�o 2)
        $documentos = array(self::$documentoTeste1, self::$documentoTeste2, self::$documentoTeste3);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTestePrincipal, $documentos, self::$destinatario);

        // definir �rg�o 1 como destinat�rio e �rg�o 2 como remetente
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        // 7-criar Processo Secund�rio ($processoTesteSecundario) (�rg�o 2)
        self::$processoTesteSecundario = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$protocoloTesteSecundario = $this->cadastrarProcesso(self::$processoTesteSecundario);
    
        // abrir Processo Principal
        $this->abrirProcesso(self::$protocoloTestePrincipal);
        
        // listar documentos do Processo Principal
        $listaDocumentosProcessoPrincipal = $this->paginaProcesso->listarDocumentos();
        $this->assertEquals(3, count($listaDocumentosProcessoPrincipal));
        
        // 8-mover documento externo (documentoTeste1) do Processo Principal para o Processo Secund�rio
        $this->paginaProcesso->selecionarDocumento($listaDocumentosProcessoPrincipal[0]);
        $this->paginaDocumento->navegarParaMoverDocumento();
        $this->paginaMoverDocumento->moverDocumentoParaProcesso(self::$protocoloTesteSecundario, "Motivo de teste");

        // 9-mover documento interno (documentoTeste2) do Processo Principal para o Processo Secund�rio
        $this->paginaProcesso->selecionarDocumento($listaDocumentosProcessoPrincipal[1]);
        $this->paginaDocumento->navegarParaMoverDocumento();
        $this->paginaMoverDocumento->moverDocumentoParaProcesso(self::$protocoloTesteSecundario, "Motivo de teste");

        // 10-criar documento externo (documentoTeste4) no Processo Principal
        self::$documentoTeste4 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);
        $this->cadastrarDocumentoExterno(self::$documentoTeste4);

        // 11-criar documento interno (documentoTeste5) no Processo Principal
        self::$documentoTeste5 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        $this->cadastrarDocumentoInterno(self::$documentoTeste5);
        $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);

        // 12-tramitar Processo Principal para o �rg�o 1 com valida��o no remetente
        $this->tramitarProcessoExternamente(self::$protocoloTestePrincipal, self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'], self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);

        // verificar se situa��o atual do processo est� como bloqueado no remetente
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];
        $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $this->atualizarTramitesPEN();
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(utf8_encode("Processo em tr�mite externo para "), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        // validar se recibo de tr�mite foi armazenado para o processo (envio e conclus�o)
        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Tr�mite externo do Processo %s para %s", self::$protocoloTestePrincipal, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);

        // validar hist�rico de tr�mite do processo
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);

        // verificar se processo est� na lista de Processos Tramitados Externamente
        $deveExistir = self::$remetente['URL'] != self::$destinatario['URL'];
        $this->validarProcessosTramitados(self::$protocoloTestePrincipal, $orgaosDiferentes);
    }

    /*
    Escopo da fun��o:
        �rg�o 1:
            13-verificar correto recebimento do processo no destino (�rg�o 1)
            14-criar documento interno (documentoTeste6) no Processo Principal
            15-tramitar Processo Principal para o �rg�o 2 com valida��o no remetente
        
    @group TramiteProcessoContendoDocumentoMovidoDestino
    @large
    @depends test_criar_mover_incluir_documentos_devolver_processo_remetente
    @return void
    */
    public function test_incluir_documento_tramitar_destinatario()
    {
        // 13-verificar correto recebimento do processo no destino (�rg�o 1)
        $documentos = array(self::$documentoTeste1, self::$documentoTeste2, self::$documentoTeste3, self::$documentoTeste4, self::$documentoTeste5);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTestePrincipal, $documentos, self::$destinatario);

        // definir �rg�o 1 como remetente e �rg�o 2 como destinat�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // abrir Processo Principal
        $this->abrirProcesso(self::$protocoloTestePrincipal);

        // 14-criar documento interno (documentoTeste6) no Processo Principal
        self::$documentoTeste6 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        $this->cadastrarDocumentoInterno(self::$documentoTeste6);
        $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);

        // 15-tramitar Processo Principal para o �rg�o 2 com valida��o no remetente
        $this->tramitarProcessoExternamente(self::$protocoloTestePrincipal, self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'], self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);

        // verificar se situa��o atual do processo est� como bloqueado no remetente
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];
        $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $this->atualizarTramitesPEN();
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(utf8_encode("Processo em tr�mite externo para "), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        // validar se recibo de tr�mite foi armazenado para o processo (envio e conclus�o)
        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Tr�mite externo do Processo %s para %s", self::$protocoloTestePrincipal, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);

        // validar hist�rico de tr�mite do processo
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);

        // verificar se processo est� na lista de Processos Tramitados Externamente
        $deveExistir = self::$remetente['URL'] != self::$destinatario['URL'];
        $this->validarProcessosTramitados(self::$protocoloTestePrincipal, $orgaosDiferentes);
    }

    /*
    Escopo da fun��o:
        �rg�o 2:
            16-verificar correto recebimento do processo no destino (�rg�o 2)   
            17-criar documento interno (documentoTeste7) no Processo Principal
            18-tramitar Processo Principal para o �rg�o 1 com valida��o no remetente
        
    @group TramiteProcessoContendoDocumentoMovidoDestino
    @large
    @depends test_incluir_documento_tramitar_destinatario
    @return void
    */
    public function test_incluir_documento_tramitar_remetente()
    {
        // 16-verificar correto recebimento do processo no destino (�rg�o 2)   
        $documentos = array(self::$documentoTeste1, self::$documentoTeste2, self::$documentoTeste3, self::$documentoTeste4, self::$documentoTeste5, self::$documentoTeste6);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTestePrincipal, $documentos, self::$destinatario);

        // definir �rg�o 1 como destinat�rio e �rg�o 2 como remetente
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        // 17-criar documento interno (documentoTeste7) no Processo Principal
        self::$documentoTeste7 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        $this->cadastrarDocumentoInterno(self::$documentoTeste7);
        $this->assinarDocumento(self::$remetente['ORGAO'], self::$remetente['CARGO_ASSINATURA'], self::$remetente['SENHA']);

        // 18-tramitar Processo Principal para o �rg�o 1 com valida��o no remetente
        $this->tramitarProcessoExternamente(self::$protocoloTestePrincipal, self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'], self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);

        // verificar se situa��o atual do processo est� como bloqueado no remetente
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];
        $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $this->atualizarTramitesPEN();
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(utf8_encode("Processo em tr�mite externo para "), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        // validar se recibo de tr�mite foi armazenado para o processo (envio e conclus�o)
        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Tr�mite externo do Processo %s para %s", self::$protocoloTestePrincipal, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);

        // validar hist�rico de tr�mite do processo
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);

        // verificar se processo est� na lista de Processos Tramitados Externamente
        $deveExistir = self::$remetente['URL'] != self::$destinatario['URL'];
        $this->validarProcessosTramitados(self::$protocoloTestePrincipal, $orgaosDiferentes);
    }
    
    /*
    Escopo da fun��o:
        �rg�o 1:
            19-verificar correto recebimento do processo no destino (�rg�o 1)
        
    @group TramiteProcessoContendoDocumentoMovidoDestino
    @large
    @depends test_incluir_documento_tramitar_remetente
    @return void
    */
    public function test_verificar_processo_documento_destino()
    {
        // 19-verificar correto recebimento do processo no destino (�rg�o 1)
        $documentos = array(self::$documentoTeste1, self::$documentoTeste2, self::$documentoTeste3, self::$documentoTeste4, self::$documentoTeste5, self::$documentoTeste6, self::$documentoTeste7);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTestePrincipal, $documentos, self::$destinatario);
    }

}