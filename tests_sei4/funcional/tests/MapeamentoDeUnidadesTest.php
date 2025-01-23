<?php

/**
 * Mapeia as Unidades com limitação de repositórios
 * para tramite de processos entre orgãos
 */
class MapeamentoDeUnidadesTest extends FixtureCenarioBaseTestCase
{
  public static $remetente;
  public static $destinatario;

  public static function setUpBeforeClass() : void {
    parent::setUpBeforeClass();
    putenv("DATABASE_HOST=org1-database");
    $penMapUnidadesFixture = new \PenMapUnidadesFixture();
    $penMapUnidadesFixture->remover([
        'Id' => CONTEXTO_ORGAO_A_ID_ESTRUTURA
    ]);
    $penMapUnidadesFixture->remover([
      'Id' => CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA
    ]);
    
    putenv("DATABASE_HOST=org2-database");
    $penMapUnidadesFixture->remover([
        'Id' => CONTEXTO_ORGAO_B_ID_ESTRUTURA
    ]);
    $penMapUnidadesFixture->remover([
      'Id' => CONTEXTO_ORGAO_B_ID_ESTRUTURA_SECUNDARIA
    ]);


  }

  /**
   * Volta para default
   */
  public static function tearDownAfterClass() : void {

    putenv("DATABASE_HOST=org1-database");
    $penMapUnidadesFixture = new \PenMapUnidadesFixture();
    $penMapUnidadesFixture->carregar([
        'Id' => CONTEXTO_ORGAO_A_ID_ESTRUTURA,
        'Sigla' => CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA,
        'Nome' => CONTEXTO_ORGAO_A_NOME_UNIDADE,
    ]);
    $penMapUnidadesFixture->carregar([
      'IdUnidade' => 110000002,
      'Id' => CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA,
      'Sigla' => CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA,
      'Nome' => CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA,
  ]);
    putenv("DATABASE_HOST=org2-database");
    $penMapUnidadesFixture->carregar([
        'Id' => CONTEXTO_ORGAO_B_ID_ESTRUTURA,
        'Sigla' => CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA,
        'Nome' => CONTEXTO_ORGAO_B_NOME_UNIDADE,
    ]);
  }

  /**
   * Teste cadastro de mapeamento de unidades no org1
   *
   * @return void
   */
  public function test_mapeamento_unidades_org1()
  {
    self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
    self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    $this->paginaMapUnidades->navegarPenMapeamentoUnidades();

    $this->paginaMapUnidades->cadastrarNovoMapeamento([
      'numUnidade' => '110000001',
      'nomeUnidade' => CONTEXTO_ORGAO_A_NOME_UNIDADE
    ]);
    
    $this->paginaMapUnidades->cadastrarNovoMapeamento([
      'numUnidade' => '110000002',
      'nomeUnidade' => CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA
    ]);

    $this->assertTrue($this->paginaMapUnidades->validarMapeamentoExistente(CONTEXTO_ORGAO_A_NOME_UNIDADE));
    $this->assertTrue($this->paginaMapUnidades->validarMapeamentoExistente(CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA));

    $this->sairSistema();
  }

  /**
   * Teste cadastro de mapeamento de unidades no org2
   *
   * @return void
   */
  public function test_mapeamento_unidades_org2()
  {
    
    $this->acessarSistema(
      self::$destinatario['URL'],
      self::$destinatario['SIGLA_UNIDADE'],
      self::$destinatario['LOGIN'],
      self::$destinatario['SENHA']
    );

    $this->paginaMapUnidades->navegarPenMapeamentoUnidades();

    $this->paginaMapUnidades->cadastrarNovoMapeamento([
      'numUnidade' => '110000001',
      'nomeUnidade' => CONTEXTO_ORGAO_B_NOME_UNIDADE
    ]);
    $this->assertTrue($this->paginaMapUnidades->validarMapeamentoExistente(CONTEXTO_ORGAO_B_NOME_UNIDADE));

    $this->sairSistema();
  }

  /**
   * Teste cadastro de mapeamento de unidades no org2
   *
   * @return void
   */
  public function test_altera_e_valida_mapeamento_unidades()
  {

    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    $this->paginaMapUnidades->navegarPenMapeamentoUnidades();
    $this->paginaMapUnidades->selecionarEditar();
    $this->paginaMapUnidades->selecionarUnidadePenCadastro(CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA);
    $this->paginaMapUnidades->salvar();

    sleep(2);

    $mensagem = $this->paginaMapUnidades->buscarMensagemAlerta();
    $this->assertStringContainsString(
        mb_convert_encoding('Mapeamento de Unidade gravado com sucesso.', 'UTF-8', 'ISO-8859-1'),
        $mensagem
    );


    $this->assertTrue($this->paginaMapUnidades->validarMapeamentoExistente(CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA));

    $this->sairSistema();
  }

  public function test_excluir_mapeamento_unidades()
  {


    $this->acessarSistema(
      self::$remetente['URL'],
      self::$remetente['SIGLA_UNIDADE'],
      self::$remetente['LOGIN'],
      self::$remetente['SENHA']
    );

    $this->paginaMapUnidades->navegarPenMapeamentoUnidades();

    $this->paginaMapUnidades->excluirMapeamentosExistentes();

    sleep(2);
    
    $this->assertFalse($this->paginaMapUnidades->existeTabela());
    
    $this->sairSistema();

  }
  

}
