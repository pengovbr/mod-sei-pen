<?php

/**
 * Mapeia as Unidades com limita��o de reposit�rios
 * para tramite de processos entre org�os
 */
class MapeamentoDeUnidadesComLimitacaoDeRepositoriosTest extends FixtureCenarioBaseTestCase
{
  public static $remetente;
  public $penMapUnidadesFixture;
  public $objProtocoloDTO;

  /**
   * Set up
   * Cria pameamento de unidades para o teste por Fixture
   *
   * @return void
   */
  function setUp(): void
  {
    parent::setUp();
    self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

    $penMapUnidadesFixture = new \PenMapUnidadesFixture();
    $this->penMapUnidadesFixture = $penMapUnidadesFixture->carregar([
      'IdUnidade' => 110000001,
      'Id' => self::$remetente['ID_ESTRUTURA'],
      'Sigla' => self::$remetente['SIGLA_ESTRUTURA'],
      'Nome' => self::$remetente['NOME_UNIDADE']
    ]);
  }

  /**
   * Teste mapeamento de unidades e limita��o dos reposit�rios para tramite
   *
   * @return void
   */
  public function test_mapeamento_unidades_com_limitacao_de_repositorios()
  {
    $this->removerRestricaoUnidade();

    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );
    $this->paginaMapUnidades->navegarPenMapeamentoUnidades();
    $this->paginaMapUnidades->pesquisarUnidade(self::$remetente['SIGLA_UNIDADE']);
    sleep(2);

    $this->paginaMapUnidades->selecionarEditar();
    $this->paginaMapUnidades->limparRestricoes();

    $this->paginaMapUnidades->selecionarRepoEstruturas(self::$remetente['REP_ESTRUTURAS']);
    $this->paginaMapUnidades->selecionarUnidade(self::$remetente['NOME_UNIDADE']);

    $this->paginaMapUnidades->salvar();
    sleep(2);
    $mensagem = $this->paginaCadastroOrgaoExterno->buscarMensagemAlerta();
    $this->assertStringContainsString(
      'Mapeamento de Unidade gravado com sucesso.',
      $mensagem
    );
    $this->sairSistema();
  }

  /**
   * Teste para validar se aplicou a restri��o de mapeamento de unidades no tramite
   *
   * @return void
   */
  public function test_tramitar_com_limitacao_de_repositorios()
  {
    $this->criarProcesso();

    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    $this->abrirProcesso($this->objProtocoloDTO->getStrProtocoloFormatado());

    $this->paginaProcesso->navegarParaTramitarProcesso();
    $this->paginaMapUnidades->validarRepositorio(self::$remetente['REP_ESTRUTURAS']);
    $this->paginaMapUnidades->selecionarUnidade(self::$remetente['NOME_UNIDADE']);

    $this->removerRestricaoUnidade();

    $this->sairSistema();
  }

  /**
   * Criar processo para validar tramita��o por Fixture
   *
   * @return void
   */
  private function criarProcesso()
  {
    // Defini��o de dados de teste do processo principal
    $processoTestePrincipal = $this->gerarDadosProcessoTeste(self::$remetente);
    $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
    $this->objProtocoloDTO = $this->cadastrarProcessoFixture($processoTestePrincipal);
    $this->cadastrarDocumentoInternoFixture($documentoTeste, $this->objProtocoloDTO->getDblIdProtocolo());    

  }

  /**
   * Remover restricao para limpar teste
   *
   * @return void
   */
  private function removerRestricaoUnidade()
  {
    $penUnidadeRestricaoFixture = new \PenUnidadeRestricaoFixture();
    $penUnidadeRestricaoFixture->remover([
      'NomeUnidadeRestricao' => self::$remetente['REP_ESTRUTURAS'],
      'NomeUnidadeRHRestricao' => self::$remetente['NOME_UNIDADE']
    ]);
  }

}
