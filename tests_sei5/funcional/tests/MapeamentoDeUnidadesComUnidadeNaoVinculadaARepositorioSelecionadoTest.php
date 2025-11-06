<?php

/**
 * Mapeamento de Unidades com Unidade Não Vinculada a Repositório Selecionado
 */
class MapeamentoDeUnidadesComUnidadeNaoVinculadaARepositorioSelecionadoTest extends FixtureCenarioBaseTestCase
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
      'Id' => self::$remetente['ID_ESTRUTURA'],
      'Sigla' => self::$remetente['SIGLA_ESTRUTURA'],
      'Nome' => self::$remetente['NOME_UNIDADE']
    ]);
  }

  /**
   * Teste mapeamento de unidades e limitação dos repositórios para tramite
   *
   * @return void
   */
  public function test_mapeamento_unidades_com_unidade_nao_vinculado_a_repositorio_selecionado()
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
    $this->paginaMapUnidades->selecionarUnidadeComAlert(1234567);
    sleep(2);
    $mensagem = $this->paginaBase->alertTextAndClose();
    $mensagemEsperada = mb_convert_encoding(
        "A unidade pesquisada não está vinculada à estrutura organizacional selecionada: ".self::$remetente['REP_ESTRUTURAS'].". "
      . "Por favor, verifique se a unidade pertence a outra estrutura.", 
      'UTF-8', 'ISO-8859-1');

    $this->removerRestricaoUnidade();

    $this->assertStringContainsString(
      $mensagemEsperada,
      $mensagem
    );
    $this->sairSistema();
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