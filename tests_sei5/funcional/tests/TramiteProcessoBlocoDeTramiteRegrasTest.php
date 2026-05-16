<?php

use PHPUnit\Framework\Attributes\{Group,Large,Depends};

/**
 *
 * Execution Groups
 * #[Group('execute_parallel_group1')]
 */
class TramiteProcessoBlocoDeTramiteRegrasTest extends FixtureCenarioBaseTestCase
{
  public static $remetente;
  public static $objBlocoDeTramiteDTO;

  public static function setUpBeforeClass(): void
    {
      parent::setUpBeforeClass();
      $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
      self::$objBlocoDeTramiteDTO = $objBlocoDeTramiteFixture->carregar();
  }

    /**
     * Teste pra validar mensagem de documento não assinado ao ser inserido em bloco
     *
     * #[Group('envio')]
     * #[Large]
     *
     * @return void
     */
  public function test_validar_mensagem_de_documento_nao_assinado()
    {
      // Configuração do dados para teste do cenário
      self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
      $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
      $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

      // Cadastrar novo processo de teste
      $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);

      $this->cadastrarDocumentoInternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo(), false);

      // Acessar sistema do this->REMETENTE do processo
      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      $this->paginaBase->navegarParaControleProcesso();
      $this->paginaTramiteEmBloco->selecionarProcessos([$objProtocoloDTO->getStrProtocoloFormatado()]);
      $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
      $this->paginaTramiteEmBloco->selecionarBloco(self::$objBlocoDeTramiteDTO->getNumId());
      $this->paginaTramiteEmBloco->clicarSalvar();

      $mensagem = "Prezado(a) usuário(a), o processo " . $objProtocoloDTO->getStrProtocoloFormatado()
          . " possui documentos gerados não assinados. "
          . "Dessa forma, não foi possível realizar sua inserção no bloco selecionado.";
      $this->assertStringContainsString(
          mb_convert_encoding($mensagem, 'UTF-8', 'ISO-8859-1'),
          $this->aguardarEBuscarMensagemAlerta()
      );
  }

    /**
     * Teste pra validar mensagem de processo bloqueado ao ser inserido em bloco 
     *
     * #[Group('envio')]
     * #[Large]
     *
     * @return void
     */
  public function test_validar_mensagem_de_processo_bloqueado()
    {
      // Configuração do dados para teste do cenário
      $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
      $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

      // Cadastrar novo processo de teste
      $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);

      // Incluir e assinar documento no processo
      $this->cadastrarDocumentoInternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());

      $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);
      $bancoOrgaoA->execute("update protocolo set sta_estado=? where id_protocolo=?", array(4, $objProtocoloDTO->getDblIdProtocolo()));

      // Acessar sistema do this->REMETENTE do processo
      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      $this->paginaBase->navegarParaControleProcesso();
      $this->paginaTramiteEmBloco->selecionarProcessos([$objProtocoloDTO->getStrProtocoloFormatado()]);
      $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
      $this->paginaTramiteEmBloco->selecionarBloco(self::$objBlocoDeTramiteDTO->getNumId());
      $this->paginaTramiteEmBloco->clicarSalvar();

      $mensagem = $this->aguardarEBuscarMensagemAlerta();
      $this->assertStringContainsString(
          mb_convert_encoding('Prezado(a) usuário(a), o processo ' . $objProtocoloDTO->getStrProtocoloFormatado() . ' encontra-se bloqueado. Dessa forma, não foi possível realizar a sua inserção no bloco selecionado.', 'UTF-8', 'ISO-8859-1'),
          $mensagem
      );
  }

    /**
     * Teste pra validar a mensagem de processo aberto em mais de uma unidade ao ser inserido em bloco
     *
     * #[Group('envio')]
     * #[Large]
     *
     * @return void
     */
  public function test_validar_mensagem_de_processo_aberto_em_mais_de_uma_unidade()
    {
      // Configuração do dados para teste do cenário
      $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
      $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

      // Cadastrar novo processo de teste
      $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);

      // Incluir e assinar documento no processo
      $this->cadastrarDocumentoInternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());

      // Acessar sistema do this->REMETENTE do processo
      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

      $this->abrirProcesso($objProtocoloDTO->getStrProtocoloFormatado());

      $this->tramitarProcessoInternamente(self::$remetente['SIGLA_UNIDADE_SECUNDARIA'], true);

      $this->paginaBase->navegarParaControleProcesso();
      $this->paginaTramiteEmBloco->selecionarProcessos([$objProtocoloDTO->getStrProtocoloFormatado()]);
      $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
      $this->paginaTramiteEmBloco->selecionarBloco(self::$objBlocoDeTramiteDTO->getNumId());
      $this->paginaTramiteEmBloco->clicarSalvar();

      $mensagem = $this->aguardarEBuscarMensagemAlerta();
      $this->assertStringContainsString(
          mb_convert_encoding('Não é possível tramitar um processo aberto em mais de uma unidade.', 'UTF-8', 'ISO-8859-1'),
          $mensagem
      );
      $this->assertStringContainsString(
          mb_convert_encoding('Processo ' . $objProtocoloDTO->getStrProtocoloFormatado() . ' está aberto na(s) unidade(s): ' . self::$remetente['SIGLA_UNIDADE_SECUNDARIA'], 'UTF-8', 'ISO-8859-1'),
          $mensagem
      );
  }

    /**
     * Teste pra validar a mensagem de processo sem documentos nao pode ser incluido em bloco
     *
     * #[Group('envio')]
     * #[Large]
     *
     * @return void
     */
  public function test_validar_mensagem_de_processo_sem_documento()
    {
      // Configuração do dados para teste do cenário
      $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);

      $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);

      $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);        

      $this->paginaBase->navegarParaControleProcesso();
      $this->paginaTramiteEmBloco->selecionarProcessos([$objProtocoloDTO->getStrProtocoloFormatado()]);
      $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
      $this->paginaTramiteEmBloco->selecionarBloco(self::$objBlocoDeTramiteDTO->getNumId());
      $this->paginaTramiteEmBloco->clicarSalvar();

      $mensagem = "Prezado(a) usuário(a), o processo " . $objProtocoloDTO->getStrProtocoloFormatado()
          . " não possui documentos. Dessa forma, não foi possível realizar sua inserção no bloco selecionado.";
      $this->assertStringContainsString(
          mb_convert_encoding($mensagem, 'UTF-8', 'ISO-8859-1'),
          $this->aguardarEBuscarMensagemAlerta()
      );
  }

  private function aguardarEBuscarMensagemAlerta(): string
  {
      $this->waitUntil(function() {
          try {
              $mensagem = $this->paginaTramiteEmBloco->buscarMensagemAlerta();
              return !empty($mensagem);
          } catch (\Exception $e) {
              return false;
          }
      }, PEN_WAIT_TIMEOUT);

      return $this->paginaTramiteEmBloco->buscarMensagemAlerta();
  }
}
