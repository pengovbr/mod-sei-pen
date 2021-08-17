<?php

/**
 * Testes de trâmite de processos em lote
 *
 * Este mesmo documento deve ser recebido e assinalado como cancelado no destinatário e
 * a devolução do mesmo processo não deve ser impactado pela inserção de outros documentos
 */
class TramiteProcessoEmLoteTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $protocoloTeste;

    /**
     * Teste inicial de trâmite de um processo contendo um documento movido
     *
     * @group envio
     *
     * @return void
     */
    public function test_tramitar_processo_em_lote()
    {

        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);
        $dthConclusao= date('d/m/Y H:i:s');
        // Configuração do mapeamento de hipótese legal
        $bancoOrgaoA->execute("delete from md_pen_rel_hipotese_legal where id_hipotese_legal = ?", array(1));
        $bancoOrgaoA->execute("insert into md_pen_rel_hipotese_legal(id_mapeamento, id_hipotese_legal, id_hipotese_legal_pen, tipo, sin_ativo) values (?, ?, ?, ?, ?)", array(6, 1, 1, 'E', 'S'));
        // Retirada de processo que não possui documento
        $bancoOrgaoA->execute("SET SQL_SAFE_UPDATES = ?", array(0));
        $bancoOrgaoA->execute("update atividade inner join protocolo on protocolo.id_protocolo = atividade.id_protocolo and atividade.dth_conclusao is null set dth_conclusao = ? where protocolo.sta_protocolo = ? and not exists(select 1 from documento where documento.id_procedimento = protocolo.id_protocolo)", array($dthConclusao, 'P'));
        $bancoOrgaoA->execute("SET SQL_SAFE_UPDATES = ?", array(1));

        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        // Seleciona todos os processos para tramitação em lote
        $this->selecionarProcessos();

        // Trâmitar Externamento processo para órgão/unidade destinatária
        $this->tramitarProcessoExternamente(
            self::$protocoloTeste, 
            self::$destinatario['REP_ESTRUTURAS'], 
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false);
        
        sleep(180);

    }

    /**
     * Teste de verificação do correto envio do processo no sistema remetente
     *
     * @group verificacao_envio
     *
     * @depends test_tramitar_processo_em_lote
     *
     * @return void
     */
    public function test_verificar_origem_processo()
    {
        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->visualizarProcessoTramitadosEmLote($this);
        $this->selecionarSituacao();

        $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $testCase->refresh();
            $paginaTramitarProcessoEmLote = new PaginaTramitarProcessoEmLote($testCase);
            $testCase->assertStringContainsString(utf8_encode("Nenhum registro encontrado."), $paginaTramitarProcessoEmLote->informacaoLote());
            return true;
        }, PEN_WAIT_TIMEOUT_PROCESSAMENTO_EM_LOTE);
        
        sleep(300);

    }

    /**
     * Teste de trâmite externo de processo realizando a devolução para a mesma unidade de origem contendo
     * mais dois documentos, sendo um deles movido
     *
     * @group envio
     *
     * @depends test_verificar_origem_processo
     *
     * @return void
     */
    public function test_devolucao_processo_em_lote_para_origem()
    {

        $bancoOrgaoB = new DatabaseUtils(CONTEXTO_ORGAO_B);
        $dthConclusao= date('d/m/Y H:i:s');
        // Configuração do mapeamento de hipótese legal
        $bancoOrgaoB->execute("delete from md_pen_rel_hipotese_legal where id_hipotese_legal = ?", array(1));
        $bancoOrgaoB->execute("insert into md_pen_rel_hipotese_legal(id_mapeamento, id_hipotese_legal, id_hipotese_legal_pen, tipo, sin_ativo) values (?, ?, ?, ?, ?)", array(6, 1, 1, 'E', 'S'));
        $bancoOrgaoB->execute("insert into md_pen_rel_hipotese_legal(id_mapeamento, id_hipotese_legal, id_hipotese_legal_pen, tipo, sin_ativo) values (?, ?, ?, ?, ?)", array(7, 4, 4, 'E', 'S'));
        // Retirada de processo que não possui documento
        $bancoOrgaoB->execute("SET SQL_SAFE_UPDATES = ?", array(0));
        $bancoOrgaoB->execute("update atividade inner join protocolo on protocolo.id_protocolo = atividade.id_protocolo and atividade.dth_conclusao is null set dth_conclusao = ? where protocolo.sta_protocolo = ? and not exists(select 1 from documento where documento.id_procedimento = protocolo.id_protocolo)", array($dthConclusao, 'P'));
        $bancoOrgaoB->execute("SET SQL_SAFE_UPDATES = ?", array(1));

        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        // Acessar sistema do this->REMETENTE do processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        // Seleciona todos os processos para tramitação em lote
        $this->selecionarProcessos();

        // Trâmitar Externamento processo para órgão/unidade destinatária
        $this->tramitarProcessoExternamente(
            self::$protocoloTeste, 
            self::$destinatario['REP_ESTRUTURAS'], 
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false);
        
        sleep(180);
    }


    /**
     * Teste de verificação do correto envio do processo no sistema remetente
     *
     * @group verificacao_envio
     *
     * @depends test_devolucao_processo_em_lote_para_origem
     *
     * @return void
     */
    public function test_verificar_devolucao_origem_processo()
    {

        $orgaosDiferentes = self::$remetente['URL'] != self::$destinatario['URL'];

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->visualizarProcessoTramitadosEmLote($this);
        $this->selecionarSituacao();

        $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $testCase->refresh();
            $paginaTramitarProcessoEmLote = new PaginaTramitarProcessoEmLote($testCase);
            $testCase->assertStringContainsString(utf8_encode("Nenhum registro encontrado."), $paginaTramitarProcessoEmLote->informacaoLote());
            return true;
        }, PEN_WAIT_TIMEOUT_PROCESSAMENTO_EM_LOTE);
      
    }

}
