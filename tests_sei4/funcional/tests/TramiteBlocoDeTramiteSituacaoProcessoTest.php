<?php

use Tests\Funcional\Sei\Fixtures\{ProtocoloFixture,ProcedimentoFixture,AtividadeFixture,ContatoFixture,ParticipanteFixture,RelProtocoloAssuntoFixture,AtributoAndamentoFixture,DocumentoFixture,AssinaturaFixture,AnexoFixture,AnexoProcessoFixture};

/**
 *
 * Execution Groups
 * @group execute_parallel_group1
 */
class TramiteBlocoDeTramiteSituacaoProcessoTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $processoTeste;

    /**
     * Teste pra validar mensagem de documento não assinado ao ser inserido em bloco
     *
     * @group envio
     * @large
     *
     * @return void
     */
    public function test_validar_situacao_do_processo_no_bloco()
    {
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      self::$processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);

      // Cadastrar novo processo de teste
      $objProtocoloDTO = $this->cadastrarProcessoFixture(self::$processoTeste);

      $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
      $objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();

      $objBlocoDeTramiteProtocoloFixture = new \BlocoDeTramiteProtocoloFixture();
      $objBlocoDeTramiteProtocoloFixtureDTO = $objBlocoDeTramiteProtocoloFixture->carregar([
        'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
        'IdBloco' => $objBlocoDeTramiteDTO->getNumId()
      ]);

      $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);
      $bancoOrgaoA->execute("update md_pen_bloco_processo set id_andamento=? where id_protocolo=?;", array(1, $objProtocoloDTO->getDblIdProtocolo()));

      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
      $this->paginaCadastrarProcessoEmBloco->bntVisualizarProcessos();
      
      $this->waitUntil(function ($testCase) {
        sleep(2);
        $testCase->refresh();
        $situacaoTr = $testCase->elements($testCase->using('xpath')->value('//table[@id="tblBlocos"]/tbody/tr[1]/td[7]//img[@title="Aguardando Processamento"]'));
        $this->assertEquals(1, count($situacaoTr));
        return true;
      }, PEN_WAIT_TIMEOUT);

      $bancoOrgaoA->execute("update md_pen_bloco_processo set id_andamento=? where id_protocolo=?;", array(2, $objProtocoloDTO->getDblIdProtocolo()));
      
      $this->waitUntil(function ($testCase) {
        sleep(2);
        $testCase->refresh();
        $situacaoTr = $testCase->elements($testCase->using('xpath')->value('//table[@id="tblBlocos"]/tbody/tr[1]/td[7]//img[@title="Aguardando Processamento"]'));
        $this->assertEquals(1, count($situacaoTr));
        return true;
      }, PEN_WAIT_TIMEOUT);

      $bancoOrgaoA->execute("update md_pen_bloco_processo set id_andamento=? where id_protocolo=?;", array(3, $objProtocoloDTO->getDblIdProtocolo()));
      
      $this->waitUntil(function ($testCase) {
        sleep(2);
        $testCase->refresh();
        $situacaoTr = $testCase->elements($testCase->using('xpath')->value('//table[@id="tblBlocos"]/tbody/tr[1]/td[7]//img[@title="Aguardando Processamento"]'));
        $this->assertEquals(1, count($situacaoTr));
        return true;
      }, PEN_WAIT_TIMEOUT);

      $bancoOrgaoA->execute("update md_pen_bloco_processo set id_andamento=? where id_protocolo=?;", array(4, $objProtocoloDTO->getDblIdProtocolo()));
      
      $this->waitUntil(function ($testCase) {
        sleep(2);
        $testCase->refresh();
        $situacaoTr = $testCase->elements($testCase->using('xpath')->value('//table[@id="tblBlocos"]/tbody/tr[1]/td[7]//img[@title="Aguardando Processamento"]'));
        $this->assertEquals(1, count($situacaoTr));
        return true;
      }, PEN_WAIT_TIMEOUT);

      $bancoOrgaoA->execute("update md_pen_bloco_processo set id_andamento=? where id_protocolo=?;", array(5, $objProtocoloDTO->getDblIdProtocolo()));
      
      $this->waitUntil(function ($testCase) {
        sleep(2);
        $testCase->refresh();
        $situacaoTr = $testCase->elements($testCase->using('xpath')->value('//table[@id="tblBlocos"]/tbody/tr[1]/td[7]//img[@title="Aguardando Processamento"]'));
        $this->assertEquals(1, count($situacaoTr));
        return true;
      }, PEN_WAIT_TIMEOUT);

      $bancoOrgaoA->execute("update md_pen_bloco_processo set id_andamento=? where id_protocolo=?;", array(8, $objProtocoloDTO->getDblIdProtocolo()));
      
      $this->waitUntil(function ($testCase) {
        sleep(2);
        $testCase->refresh();
        $situacaoTr = $testCase->elements($testCase->using('xpath')->value('//table[@id="tblBlocos"]/tbody/tr[1]/td[7]//img[@title="Aguardando Processamento"]'));
        $this->assertEquals(1, count($situacaoTr));
        return true;
      }, PEN_WAIT_TIMEOUT);
    }

}