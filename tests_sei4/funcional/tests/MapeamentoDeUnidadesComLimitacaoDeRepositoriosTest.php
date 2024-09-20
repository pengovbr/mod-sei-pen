<?php

use Tests\Funcional\Sei\Fixtures\ProtocoloFixture;
use Tests\Funcional\Sei\Fixtures\ProcedimentoFixture;
use Tests\Funcional\Sei\Fixtures\ParticipanteFixture;
use Tests\Funcional\Sei\Fixtures\RelProtocoloAssuntoFixture;
use Tests\Funcional\Sei\Fixtures\AtributoAndamentoFixture;
use Tests\Funcional\Sei\Fixtures\DocumentoFixture;
use Tests\Funcional\Sei\Fixtures\AssinaturaFixture;
use Tests\Funcional\Sei\Fixtures\AtividadeFixture;

/**
 * Mapeia as Unidades com limitação de repositórios
 * para tramite de processos entre orgãos
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
   * Teste para validar se aplicou a restrição de mapeamento de unidades no tramite
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
   * Criar processo para validar tramitação por Fixture
   *
   * @return void
   */
  private function criarProcesso()
  {
    $objProtocoloFixture = new ProtocoloFixture();
    $this->objProtocoloDTO = $objProtocoloFixture->carregar([], function ($objProtocoloDTO) {

      $objProcedimentoFixture = new ProcedimentoFixture();
      $objProcedimentoDTO = $objProcedimentoFixture->carregar([
        'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
      ]);

      $objAtividadeFixture = new AtividadeFixture();
      $objAtividadeDTO = $objAtividadeFixture->carregar([
        'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
      ]);

      $objParticipanteFixture = new ParticipanteFixture();
      $objParticipanteFixture->carregar([
        'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
        'IdContato' => 100000006
      ]);

      $objProtocoloAssuntoFixture = new RelProtocoloAssuntoFixture();
      $objProtocoloAssuntoFixture->carregar([
        'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
      ]);

      $objAtributoAndamentoFixture = new AtributoAndamentoFixture();
      $objAtributoAndamentoFixture->carregar([
        'IdAtividade' => $objAtividadeDTO->getNumIdAtividade()
      ]);

      $objDocumentoFixture = new DocumentoFixture();
      $objDocumentoDTO = $objDocumentoFixture->carregar([
        'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
        'IdProcedimento' => $objProcedimentoDTO->getDblIdProcedimento(),
        'IdSerie' => 34
      ]);

      $objAtividadeFixture = new AtividadeFixture();
      $objAtividadeDTO = $objAtividadeFixture->carregar([
        'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
        'IdTarefa' => TarefaRN::$TI_ASSINATURA_DOCUMENTO
      ]);

      $objAssinaturaFixture = new AssinaturaFixture();
      $objAssinaturaFixture->carregar([
        'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
        'IdDocumento' => $objDocumentoDTO->getDblIdDocumento(),
        'IdAtividade' => $objAtividadeDTO->getNumIdAtividade()
      ]);
    });
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
