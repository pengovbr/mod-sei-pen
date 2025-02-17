<?php
/*
Escopo do caso de teste:
    Órgão 1:
        1-criar Processo Principal ($processoTestePrincipal) (Órgão 1)
        2-criar Documento Externo (documentoTeste1) no Processo Principal
        3-criar Documento Interno (documentoTeste2) no Processo Principal
        4-criar Documento Interno (documentoTeste3) no Processo Principal
        5-tramitar Processo Principal para o Órgão 2 com validação no remetente
    Órgão 2:
        6-verificar correto recebimento do processo e seus documentos no destino (Órgão 2)
        7-criar Processo Secundário ($processoTesteSecundario) (Órgão 2)
        8-mover documento externo (documentoTeste1) do Processo Principal para o Processo Secundário
        9-mover documento interno (documentoTeste2) do Processo Principal para o Processo Secundário
        10-criar documento externo (documentoTeste4) no Processo Principal
        11-criar documento interno (documentoTeste5) no Processo Principal
        12-tramitar Processo Principal para o Órgão 1 com validação no remetente
    Órgão 1:
        13-verificar correto recebimento do processo no destino (Órgão 1)
        14-criar documento interno (documentoTeste6) no Processo Principal
        15-tramitar Processo Principal para o Órgão 2 com validação no remetente
    Órgão 2:
        16-verificar correto recebimento do processo no destino (Órgão 2)   
        17-criar documento interno (documentoTeste7) no Processo Principal
        18-tramitar Processo Principal para o Órgão 1 com validação no remetente
    Órgão 1:
        19-verificar correto recebimento do processo no destino (Órgão 1)
*/

/**
 *
 * Execution Groups
 * @group exxecute_parallel
 */
class TramiteProcessoContendoDocumentoMovidoDestinoTest extends FixtureCenarioBaseTestCase
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
    public static $objProtocoloTestePrincipalDTO;
    public static $objProtocoloTestePrincipalOrg2DTO;


    
    /*  
    Escopo da função:
        Órgão 1:
            1-criar Processo Principal ($processoTestePrincipal) (Órgão 1)
            2-criar Documento Externo (documentoTeste1) no Processo Principal
            3-criar Documento Interno (documentoTeste2) no Processo Principal
            4-criar Documento Interno (documentoTeste3) no Processo Principal
            5-tramitar Processo Principal para o Órgão 2 com validação no remetente

    @group TramiteProcessoContendoDocumentoMovidoDestino
    @large
    @Depends CenarioBaseTestCase::setUpBeforeClass
    @return void
    */
    public function test_criar_processo_contendo_documentos_tramitar_remetente()
    {
        // definir Órgão 1 como remetente e Órgão 2 como destinatário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);


        // 1-criar Processo Principal ($processoTestePrincipal) (Órgão 1)
        self::$processoTestePrincipal = $this->gerarDadosProcessoTeste(self::$remetente);
        // $this->paginaBase->navegarParaControleProcesso();
        self::$objProtocoloTestePrincipalDTO = $this->cadastrarProcessoFixture(self::$processoTestePrincipal);
        self::$protocoloTestePrincipal = self::$objProtocoloTestePrincipalDTO->getStrProtocoloFormatado(); 

        // 2-criar Documento Externo (documentoTeste1) no Processo Principal
        self::$documentoTeste1 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);
        $this->cadastrarDocumentoExternoFixture(self::$documentoTeste1, self::$objProtocoloTestePrincipalDTO->getDblIdProtocolo());

        // 3-criar Documento Interno (documentoTeste2) no Processo Principal
        self::$documentoTeste2 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        $this->cadastrarDocumentoInternoFixture(self::$documentoTeste2, self::$objProtocoloTestePrincipalDTO->getDblIdProtocolo());

        // 4-criar Documento Interno (documentoTeste3) no Processo Principal
        self::$documentoTeste3 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        $this->cadastrarDocumentoInternoFixture(self::$documentoTeste3, self::$objProtocoloTestePrincipalDTO->getDblIdProtocolo());

        // acessar remetente (Órgão 1)
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        
        // Abrir processo
        $this->abrirProcesso(self::$protocoloTestePrincipal);

        // 5-tramitar Processo Principal para o Órgão 2 com validação no remetente
        $this->tramitarProcessoExternamente(self::$protocoloTestePrincipal, self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'], self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);

        // verificar se situação atual do processo está como bloqueado no remetente
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];
        $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(mb_convert_encoding("Processo em trâmite externo para ", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        // validar se recibo de trâmite foi armazenado para o processo (envio e conclusão)
        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTestePrincipal, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);

        // validar histórico de trâmite do processo
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);

        // verificar se processo está na lista de Processos Tramitados Externamente
        $deveExistir = self::$remetente['URL'] != self::$destinatario['URL'];
        $this->validarProcessosTramitados(self::$protocoloTestePrincipal, $orgaosDiferentes);
    }

    /*   
    Escopo da função:
        Órgão 2:
            6-verificar correto recebimento do processo e seus documentos no destino (Órgão 2)
            7-criar Processo Secundário ($processoTesteSecundario) (Órgão 2)
            8-mover documento externo (documentoTeste1) do Processo Principal para o Processo Secundário
            9-mover documento interno (documentoTeste2) do Processo Principal para o Processo Secundário
            10-criar documento externo (documentoTeste4) no Processo Principal
            11-criar documento interno (documentoTeste5) no Processo Principal
            12-tramitar Processo Principal para o Órgão 1 com validação no remetente
    
    @group TramiteProcessoContendoDocumentoMovidoDestino
    @large
    @depends test_criar_processo_contendo_documentos_tramitar_remetente
    @return void
    */
    public function test_criar_mover_incluir_documentos_devolver_processo_remetente()
    {   
        // 6-verificar correto recebimento do processo e seus documentos no destino (Órgão 2)
        $documentos = array(self::$documentoTeste1, self::$documentoTeste2, self::$documentoTeste3);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTestePrincipal, $documentos, self::$destinatario);

        // definir Órgão 1 como destinatário e Órgão 2 como remetente
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        // 7-criar Processo Secundário ($processoTesteSecundario) (Órgão 2)
        putenv("DATABASE_HOST=org2-database");
        self::$processoTesteSecundario = $this->gerarDadosProcessoTeste(self::$remetente);
        $objProtocoloSecundarioDTO = $this->cadastrarProcessoFixture(self::$processoTesteSecundario);
        self::$protocoloTesteSecundario = $objProtocoloSecundarioDTO->getStrProtocoloFormatado(); 

        // abrir Processo Principal
        $this->abrirProcesso(self::$protocoloTestePrincipal);
        
        // listar documentos do Processo Principal
        $listaDocumentosProcessoPrincipal = $this->paginaProcesso->listarDocumentos();
        $this->assertEquals(3, count($listaDocumentosProcessoPrincipal));
        
        // 8-mover documento externo (documentoTeste1) do Processo Principal para o Processo Secundário
        $this->paginaProcesso->selecionarDocumento($listaDocumentosProcessoPrincipal[0]);
        $this->paginaDocumento->navegarParaMoverDocumento();
        $this->paginaMoverDocumento->moverDocumentoParaProcesso(self::$protocoloTesteSecundario, "Motivo de teste");

        // 9-mover documento interno (documentoTeste2) do Processo Principal para o Processo Secundário
        $this->paginaProcesso->selecionarDocumento($listaDocumentosProcessoPrincipal[1]);
        $this->paginaDocumento->navegarParaMoverDocumento();
        $this->paginaMoverDocumento->moverDocumentoParaProcesso(self::$protocoloTesteSecundario, "Motivo de teste");

        // Consultar processo org-2
        self::$objProtocoloTestePrincipalOrg2DTO = $this->consultarProcessoFixture(self::$protocoloTestePrincipal, $staProtocolo = 'P');

        // 10-criar documento externo (documentoTeste4) no Processo Principal
        self::$documentoTeste4 = $this->gerarDadosDocumentoExternoTeste(self::$remetente);
        $this->cadastrarDocumentoExternoFixture(self::$documentoTeste4, self::$objProtocoloTestePrincipalOrg2DTO->getDblIdProtocolo());

        // 11-criar documento interno (documentoTeste5) no Processo Principal
        self::$documentoTeste5 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        $this->cadastrarDocumentoInternoFixture(self::$documentoTeste5,self::$objProtocoloTestePrincipalOrg2DTO->getDblIdProtocolo());
        
        // 12-tramitar Processo Principal para o Órgão 1 com validação no remetente
        $this->tramitarProcessoExternamente(self::$protocoloTestePrincipal, self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'], self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);

        // verificar se situação atual do processo está como bloqueado no remetente
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];
        $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(mb_convert_encoding("Processo em trâmite externo para ", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        // validar se recibo de trâmite foi armazenado para o processo (envio e conclusão)
        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTestePrincipal, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);

        // validar histórico de trâmite do processo
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);

        // verificar se processo está na lista de Processos Tramitados Externamente
        $deveExistir = self::$remetente['URL'] != self::$destinatario['URL'];
        $this->validarProcessosTramitados(self::$protocoloTestePrincipal, $orgaosDiferentes);
    }

    /*
    Escopo da função:
        Órgão 1:
            13-verificar correto recebimento do processo no destino (Órgão 1)
            14-criar documento interno (documentoTeste6) no Processo Principal
            15-tramitar Processo Principal para o Órgão 2 com validação no remetente
        
    @group TramiteProcessoContendoDocumentoMovidoDestino
    @large
    @depends test_criar_mover_incluir_documentos_devolver_processo_remetente
    @return void
    */
    public function test_incluir_documento_tramitar_destinatario()
    {
        // 13-verificar correto recebimento do processo no destino (Órgão 1)
        $documentos = array(self::$documentoTeste1, self::$documentoTeste2, self::$documentoTeste3, self::$documentoTeste4, self::$documentoTeste5);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTestePrincipal, $documentos, self::$destinatario);

        // definir Órgão 1 como remetente e Órgão 2 como destinatário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        putenv("DATABASE_HOST=org1-database");

        self::$objProtocoloTestePrincipalDTO = $this->consultarProcessoFixture(self::$protocoloTestePrincipal, $staProtocolo = 'P');

        // 14-criar documento interno (documentoTeste6) no Processo Principal
        self::$documentoTeste6 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        $this->cadastrarDocumentoInternoFixture(self::$documentoTeste6,self::$objProtocoloTestePrincipalDTO->getDblIdProtocolo());

        // abrir Processo Principal
        $this->abrirProcesso(self::$protocoloTestePrincipal);

        // 15-tramitar Processo Principal para o Órgão 2 com validação no remetente
        $this->tramitarProcessoExternamente(self::$protocoloTestePrincipal, self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'], self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);

        // verificar se situação atual do processo está como bloqueado no remetente
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];
        $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(mb_convert_encoding("Processo em trâmite externo para ", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        // validar se recibo de trâmite foi armazenado para o processo (envio e conclusão)
        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTestePrincipal, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);

        // validar histórico de trâmite do processo
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);

        // verificar se processo está na lista de Processos Tramitados Externamente
        $deveExistir = self::$remetente['URL'] != self::$destinatario['URL'];
        $this->validarProcessosTramitados(self::$protocoloTestePrincipal, $orgaosDiferentes);
    }

    /*
    Escopo da função:
        Órgão 2:
            16-verificar correto recebimento do processo no destino (Órgão 2)   
            17-criar documento interno (documentoTeste7) no Processo Principal
            18-tramitar Processo Principal para o Órgão 1 com validação no remetente
        
    @group TramiteProcessoContendoDocumentoMovidoDestino
    @large
    @depends test_incluir_documento_tramitar_destinatario
    @return void
    */
    public function test_incluir_documento_tramitar_remetente()
    {
        // 16-verificar correto recebimento do processo no destino (Órgão 2)   
        $documentos = array(self::$documentoTeste1, self::$documentoTeste2, self::$documentoTeste3, self::$documentoTeste4, self::$documentoTeste5, self::$documentoTeste6);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTestePrincipal, $documentos, self::$destinatario);

        // definir Órgão 1 como destinatário e Órgão 2 como remetente
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        putenv("DATABASE_HOST=org2-database");

        self::$objProtocoloTestePrincipalOrg2DTO = $this->consultarProcessoFixture(self::$protocoloTestePrincipal, $staProtocolo = 'P');

        // 17-criar documento interno (documentoTeste7) no Processo Principal
        self::$documentoTeste7 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        $this->cadastrarDocumentoInternoFixture(self::$documentoTeste7, self::$objProtocoloTestePrincipalOrg2DTO->getDblIdProtocolo());

        // abrir Processo Principal
        $this->abrirProcesso(self::$protocoloTestePrincipal);

        // 18-tramitar Processo Principal para o Órgão 1 com validação no remetente
        $this->tramitarProcessoExternamente(self::$protocoloTestePrincipal, self::$destinatario['REP_ESTRUTURAS'], self::$destinatario['NOME_UNIDADE'], self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);

        // verificar se situação atual do processo está como bloqueado no remetente
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];
        $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertStringNotContainsString(mb_convert_encoding("Processo em trâmite externo para ", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        // validar se recibo de trâmite foi armazenado para o processo (envio e conclusão)
        $unidade = mb_convert_encoding(self::$destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", self::$protocoloTestePrincipal, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);

        // validar histórico de trâmite do processo
        $this->validarHistoricoTramite(self::$destinatario['NOME_UNIDADE'], true, true);

        // verificar se processo está na lista de Processos Tramitados Externamente
        $deveExistir = self::$remetente['URL'] != self::$destinatario['URL'];
        $this->validarProcessosTramitados(self::$protocoloTestePrincipal, $orgaosDiferentes);
    }
    
    /*
    Escopo da função:
        Órgão 1:
            19-verificar correto recebimento do processo no destino (Órgão 1)
        
    @group TramiteProcessoContendoDocumentoMovidoDestino
    @large
    @depends test_incluir_documento_tramitar_remetente
    @return void
    */
    public function test_verificar_processo_documento_destino()
    {
        // 19-verificar correto recebimento do processo no destino (Órgão 1)
        $documentos = array(self::$documentoTeste1, self::$documentoTeste2, self::$documentoTeste3, self::$documentoTeste4, self::$documentoTeste5, self::$documentoTeste6, self::$documentoTeste7);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario(self::$processoTestePrincipal, $documentos, self::$destinatario);
    }

}