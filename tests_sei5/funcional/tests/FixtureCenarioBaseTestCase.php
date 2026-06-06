<?php


use Tests\Funcional\Sei\Fixtures\{ProtocoloFixture,ProcedimentoFixture,AtividadeFixture,ContatoFixture};
use Tests\Funcional\Sei\Fixtures\{ParticipanteFixture,RelProtocoloAssuntoFixture,AtributoAndamentoFixture};
use Tests\Funcional\Sei\Fixtures\{DocumentoFixture,AssinaturaFixture,AnexoFixture,AnexoProcessoFixture};
use Tests\Funcional\Sei\Fixtures\{HipoteseLegalFixture,TipoProcedimentoFixture,OrgaoFixture};
use PHPUnit\Framework\AssertionFailedError;

use function PHPSTORM_META\map;
/**
 * Classe base contendo rotinas comuns utilizadas nos casos de teste do módulo que utiliza fixture
 */
class FixtureCenarioBaseTestCase extends CenarioBaseTestCase
{
  protected function cadastrarProcessoFixture(&$dadosProcesso, $cadastrarParticipante = true)
    {
    if (!is_null($dadosProcesso['HIPOTESE_LEGAL'])){
        $objHipLegalDTO = $this->buscarHipoteseLegal($dadosProcesso);
    }

      $parametros = [
          'Descricao' => $dadosProcesso['DESCRICAO'] ?: randomString(20),
          'Interessados' => $dadosProcesso['INTERESSADOS'] ?: randomString(40),
          'IdHipoteseLegal' => $dadosProcesso['HIPOTESE_LEGAL'] ? $objHipLegalDTO->getNumIdHipoteseLegal() : null,
          'StaNivelAcessoLocal' => $dadosProcesso["RESTRICAO"] ?: parent::STA_NIVEL_ACESSO_PUBLICO,
          'StaNivelAcessoGlobal' => $dadosProcesso["RESTRICAO"] ?: parent::STA_NIVEL_ACESSO_PUBLICO
      ];
      $objProtocoloFixture = new ProtocoloFixture();
      $objProtocoloDTO = $objProtocoloFixture->carregar($parametros);
      $objProcedimentoFixture = new ProcedimentoFixture();

      $parametrosProcedimento = [
        'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
      ];
      if (!is_null($dadosProcesso['ID_TIPO_PROCESSO'])) {
        $parametrosProcedimento['IdTipoProcedimento'] = $dadosProcesso['ID_TIPO_PROCESSO'];
      }
      $objProcedimentoDTO = $objProcedimentoFixture->carregar($parametrosProcedimento);

      $objAtividadeFixture = new AtividadeFixture();
      $objAtividadeDTO = $objAtividadeFixture->carregar([
          'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
          'IdTarefa' => \TarefaRN::$TI_GERACAO_PROCEDIMENTO,
          'IdUsuarioConclusao' => 100000001
      ]);

      $objContatoFixture = new ContatoFixture();
      $objContatoDTO = $objContatoFixture->carregar([
          'Nome' => $parametros['Interessados']
      ]);

    if ($cadastrarParticipante) {
        $objParticipanteFixture = new ParticipanteFixture();
        $objParticipanteDTO = $objParticipanteFixture->carregar([
            'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
            'IdContato' => $objContatoDTO->getNumIdContato()
        ]);
    }

      $objProtocoloAssuntoFixture = new RelProtocoloAssuntoFixture();
      $objProtocoloAssuntoFixture->carregar([
          'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
          'IdAssunto' => 393
      ]);

      $objAtributoAndamentoFixture = new AtributoAndamentoFixture();
      $objAtributoAndamentoFixture->carregar([
          'IdAtividade' => $objAtividadeDTO->getNumIdAtividade()
      ]);

      $dadosProcesso['PROTOCOLO'] = $objProtocoloDTO->getStrProtocoloFormatado();
        
      return $objProtocoloDTO;
  }

  protected function buscarHipoteseLegal($dados)
    {
      $param = [
          'Nome' => trim(explode('(', $dados['HIPOTESE_LEGAL'])[0]),
          'BaseLegal' => explode(')', trim(explode('(', $dados['HIPOTESE_LEGAL'])[1]))[0]
      ];
      $objHipLegalFixture = new HipoteseLegalFixture();     
      return $objHipLegalFixture->buscar($param)[0];
  }

  protected function cadastrarDocumentoInternoFixture($dadosDocumentoInterno, $idProtocolo, $assinarDocumento = true)
    {

    if (!is_null($dadosDocumentoInterno['HIPOTESE_LEGAL'])){
        $objHipLegalDTO = $this->buscarHipoteseLegal($dadosDocumentoInterno);
    }

      $dadosDocumentoDTO = [
          'IdProtocolo' => $idProtocolo,
          'IdProcedimento' => $idProtocolo,
          'Descricao' => $dadosDocumentoInterno['DESCRICAO'],
          'IdHipoteseLegal' => $dadosDocumentoInterno["HIPOTESE_LEGAL"] ? $objHipLegalDTO->getNumIdHipoteseLegal() : null,
          'StaNivelAcessoGlobal' => $dadosDocumentoInterno["RESTRICAO"] ?: \ProtocoloRN::$NA_PUBLICO,
          'StaNivelAcessoLocal' => $dadosDocumentoInterno["RESTRICAO"] ?: \ProtocoloRN::$NA_PUBLICO,
          'IdUnidadeResponsavel' => $dadosDocumentoInterno["UNIDADE_RESPONSAVEL"] ?: null
      ];

      if ($serieDTO = $this->buscarIdSerieDoDocumento($dadosDocumentoInterno['TIPO_DOCUMENTO'])) {
          $dadosDocumentoDTO['IdSerie'] = $serieDTO->getNumIdSerie();
      }

      $objDocumentoFixture = new DocumentoFixture();
      $objDocumentoDTO = $objDocumentoFixture->carregar($dadosDocumentoDTO);

      if ($assinarDocumento) {
          //Adicionar assinatura ao documento
          $objAssinaturaFixture = new AssinaturaFixture();
          $objAssinaturaFixture->carregar([
              'IdProtocolo' => $idProtocolo,
              'IdDocumento' => $objDocumentoDTO->getDblIdDocumento(),
          ]);
      }

      return $objDocumentoDTO;

  }

  protected function cadastrarDocumentoExternoFixture($dadosDocumentoExterno, $idProtocolo)
    {
      $dadosDocumentoDTO = [
          'IdProtocolo' => $idProtocolo,
          'IdProcedimento' => $idProtocolo,
          'Descricao' => $dadosDocumentoExterno['DESCRICAO'],
          'StaProtocolo' => \ProtocoloRN::$TP_DOCUMENTO_RECEBIDO,
          'StaDocumento' => \DocumentoRN::$TD_EXTERNO,
          'IdConjuntoEstilos' => null,
      ];

      if ($serieDTO = $this->buscarIdSerieDoDocumento($dadosDocumentoExterno['TIPO_DOCUMENTO'])) {
          $dadosDocumentoDTO['IdSerie'] = $serieDTO->getNumIdSerie();
      }

      $objDocumentoFixture = new DocumentoFixture();
      $objDocumentoDTO = $objDocumentoFixture->carregar($dadosDocumentoDTO);

      //Adicionar anexo ao documento
      $objAnexoFixture = new AnexoFixture();
      $objAnexoFixture->carregar([
          'IdProtocolo' => $objDocumentoDTO->getDblIdDocumento(),
          'Nome' => basename($dadosDocumentoExterno['ARQUIVO']),
      ]);

      $objAtividadeFixture = new AtividadeFixture();
      $objAtividadeDTO = $objAtividadeFixture->carregar([
          'IdProtocolo' => $idProtocolo,
          'Conclusao' => \InfraData::getStrDataHoraAtual(),
          'IdTarefa' => \TarefaRN::$TI_ARQUIVO_ANEXADO,
          'IdUsuarioConclusao' => 100000001
      ]);

      $objAtributoAndamentoFixture = new AtributoAndamentoFixture();
      $objAtributoAndamentoFixture->carregar([
          'IdAtividade' => $objAtividadeDTO->getNumIdAtividade(),
          'Nome' => 'ANEXO'
      ]);
      
      return $objDocumentoDTO;
  }

  protected function anexarProcessoFixture($protocoloPrincipalId, $protocoloProcessoAnexadoId)
    {
      // Realizar a anexação de processos
      $objAnexoProcessoFixture = new AnexoProcessoFixture();
      $objAnexoProcessoFixture->carregar([
          'IdProtocolo' => $protocoloPrincipalId,
          'IdDocumento' => $protocoloProcessoAnexadoId,
      ]);
  }
    
  protected function consultarProcessoFixture($protocoloFormatado, $staProtocolo = null)
    {
      $objProtocoloFixture = new ProtocoloFixture();
      $objProtocoloDTO = $objProtocoloFixture->buscar([
          'ProtocoloFormatado' => $protocoloFormatado,
          'StaProtocolo' => $staProtocolo ?: \ProtocoloRN::$TP_DOCUMENTO_GERADO,
      ]);
      return $objProtocoloDTO[0];
  }

  protected function realizarTramiteExternoFixture(&$processoTeste, $documentosTeste, $remetente, $destinatario, $validarTramite, $executarTramitarPendencias = true)
    {
      $orgaosDiferentes = $remetente['URL'] != $destinatario['URL'];

      // 1 - Cadastrar novo processo de teste
    if (isset($processoTeste['PROTOCOLO'])) {
        $strProtocoloTeste = $processoTeste['PROTOCOLO'];
        $objProtocoloDTO = $this->consultarProcessoFixture($strProtocoloTeste, \ProtocoloRN::$TP_PROCEDIMENTO);

    } else {
        $objProtocoloDTO  = $this->cadastrarProcessoFixture($processoTeste);
        $strProtocoloTeste = $objProtocoloDTO->getStrProtocoloFormatado(); 
        $processoTeste['PROTOCOLO'] = $strProtocoloTeste;
    }

      // 2 - Incluir Documentos no Processo
      $documentosTeste = array_key_exists('TIPO', $documentosTeste) ? array($documentosTeste) : $documentosTeste;
    foreach ($documentosTeste as $doc) {
      if ($doc['TIPO'] == 'G') {
          // cadastra e assina documento interno
          $this->cadastrarDocumentoInternoFixture($doc, $objProtocoloDTO->getDblIdProtocolo());
      } else if ($doc['TIPO'] == 'R') {
          $this->cadastrarDocumentoExternoFixture($doc, $objProtocoloDTO->getDblIdProtocolo());
      }
    }

      // 3 - Acessar sistema do REMETENTE do processo
      $this->acessarSistema($remetente['URL'], $remetente['SIGLA_UNIDADE'], $remetente['LOGIN'], $remetente['SENHA']);

      // 4 - Abrir processo
      $this->abrirProcesso($strProtocoloTeste);
        
      // 5 - Trâmitar Externamento processo para órgão/unidade destinatária
      $this->tramitarProcessoExternamente(
        $strProtocoloTeste, 
        $destinatario['REP_ESTRUTURAS'], 
        $destinatario['NOME_UNIDADE'], 
        $destinatario['SIGLA_UNIDADE_HIERARQUIA'], 
        false, 
        null, 
        PEN_WAIT_TIMEOUT, 
        $executarTramitarPendencias
      );

    if ($validarTramite) {
        // 6 - Verificar se situação atual do processo está como bloqueado
        $this->waitUntil(function() use (&$orgaosDiferentes) {
            sleep(1);
            $this->paginaProcesso->refresh();
          try {
              $this->assertStringNotContainsString(mb_convert_encoding("Processo em trâmite externo para ", 'UTF-8', 'ISO-8859-1'), $this->paginaProcesso->informacao());
              $this->assertFalse($this->paginaProcesso->processoAberto());
              $this->assertEquals($orgaosDiferentes, $this->paginaProcesso->processoBloqueado());
              return true;
          } catch (AssertionFailedError $e) {
              return false;
          }
        }, PEN_WAIT_TIMEOUT);

        // 7 - Validar se recibo de trâmite foi armazenado para o processo (envio e conclusão)
        $unidade = mb_convert_encoding($destinatario['NOME_UNIDADE'], "ISO-8859-1");
        $mensagemRecibo = sprintf("Trâmite externo do Processo %s para %s", $strProtocoloTeste, $unidade);
        $this->validarRecibosTramite($mensagemRecibo, true, true);

        // 8 - Validar histórico de trâmite do processo
        $this->validarHistoricoTramite(mb_convert_encoding($destinatario['NOME_UNIDADE'], "ISO-8859-1"), true, true);

        // 9 - Verificar se processo está na lista de Processos Tramitados Externamente
        $deveExistir = $remetente['URL'] != $destinatario['URL'];
        $this->validarProcessosTramitados($strProtocoloTeste, $deveExistir);
    }
  }
    
  public function realizarTramiteExternoComValidacaoNoRemetenteFixture(&$processoTeste, $documentosTeste, $remetente, $destinatario, $executarTramitarPendencias = true)
    {
      $this->realizarTramiteExternoFixture($processoTeste, $documentosTeste, $remetente, $destinatario, true, $executarTramitarPendencias);
  }

  public function realizarTramiteExternoSemValidacaoNoRemetenteFixture(&$processoTeste, $documentosTeste, $remetente, $destinatario, $executarTramitarPendencias = true)
    {
      $this->realizarTramiteExternoFixture($processoTeste, $documentosTeste, $remetente, $destinatario, false, $executarTramitarPendencias);
  }

  protected function buscarIdSerieDoDocumento($tipoDocumento)
    {
      $serieDTO = new \SerieDTO();
      $serieDTO->setStrNome($tipoDocumento);
      $serieDTO->retNumIdSerie();
      $serieDTO->setNumMaxRegistrosRetorno(1);

      $objBD = new \SerieBD(\BancoSEI::getInstance());
      return $objBD->consultar($serieDTO);
  }

  protected function atualizarProcessoFixture($objProtocoloDTO, $dadosProcesso = [])
    {
    if (!is_null($dadosProcesso['DESCRICAO'])) {
        $parametros['Descricao'] = $dadosProcesso['DESCRICAO'];
    }

    if (!is_null($dadosProcesso['INTERESSADOS'])) {
        $parametros['Interessados'] = $dadosProcesso['INTERESSADOS'];
    }

      $parametros['IdProtocolo'] = $objProtocoloDTO->getDblIdProtocolo();
      $objProtocoloFixture = new ProtocoloFixture();

      return $objProtocoloFixture->atualizar($parametros);
  }
  /**
   * Método cadastrarHipoteseLegal
   * 
   * Este método realiza o cadastro de uma hipótese legal para testes de trâmite de processos e documentos.
   * Ele recebe um array com os dados da hipótese legal, cria uma nova instância de `HipoteseLegalFixture`, 
   * e utiliza esses dados para carregar a hipótese legal no sistema.
   * 
   * @param array $hipotesLegal Um array contendo os dados da hipótese legal a ser cadastrada, com as seguintes chaves:
   * - `HIPOTESE_LEGAL` (string): O nome da hipótese legal.
   * - `HIPOTESE_LEGAL_BASE_LEGAL` (string): A base legal associada à hipótese.
   * - `HIPOTESE_LEGAL_DESCRICAO` (string) [opcional]: Uma descrição para a hipótese legal (padrão: 'Nova hipotese legal para testes').
   * - `HIPOTESE_LEGAL_STA_NIVEL_ACESSO` (int) [opcional]: O nível de acesso para a hipótese legal (padrão: nível restrito).
   * - `HIPOTESE_LEGAL_SIN_ATIVO` (string) [opcional]: Indicador de atividade da hipótese legal ('S' para ativo por padrão).
   * 
   * @return object $objHipoteseLegalDTO Retorna um objeto `HipoteseLegalDTO` contendo os dados da hipótese legal cadastrada.
   */
  protected function cadastrarHipoteseLegal($hipotesLegal)
  {
    // Criação de uma nova instância de HipoteseLegalFixture
    $objHipLegalFixture = new HipoteseLegalFixture();

    // Definição dos parâmetros para cadastro da hipótese legal
    $param = [
      'Nome' => $hipotesLegal['HIPOTESE_LEGAL'],
      'BaseLegal' => $hipotesLegal['HIPOTESE_LEGAL_BASE_LEGAL'],
      'Descricao' => $hipotesLegal['HIPOTESE_LEGAL_DESCRICAO'] ?? 'Nova hipotese legal para testes',
      'StaNivelAcesso' => $hipotesLegal['HIPOTESE_LEGAL_STA_NIVEL_ACESSO'] ?? \ProtocoloRN::$NA_RESTRITO,
      'SinAtivo' => $hipotesLegal['HIPOTESE_LEGAL_SIN_ATIVO'] ?? "S"
    ];

    // Carregar a hipótese legal com os parâmetros fornecidos
    $objHipoteseLegalDTO = $objHipLegalFixture->carregar($param);

    // Retorna o objeto DTO da hipótese legal cadastrada
    return $objHipoteseLegalDTO;
  }

  protected function cadastrarTipoProcedimentoFixture($dados = [])
    {
    $objTipoProcedimentoFixture = new TipoProcedimentoFixture();
    $objTipoProcedimentoDTO = $objTipoProcedimentoFixture->carregar([
      'Nome' => $dados['NOME']
    ]);

    return $objTipoProcedimentoDTO;
  }

  /**
   * Método shellExecutarTramites
   *
   * Este método executa os scripts de monitoramento de envio e recebimento de tarefas do PEN.
   */
  public function shellExecutarTramites()
  {
    $scriptEnvio       = 'php /opt/sei/scripts/mod-pen/MonitoramentoEnvioTarefasPEN.php';
    $scriptRecebimento = 'php /opt/sei/scripts/mod-pen/MonitoramentoRecebimentoTarefasPEN.php';
 
    shell_exec("docker exec funcional-org1-http-1 {$scriptEnvio}");
    shell_exec("docker exec funcional-org1-http-1 {$scriptRecebimento}");
    shell_exec("docker exec funcional-org2-http-1 {$scriptEnvio}");
    shell_exec("docker exec funcional-org2-http-1 {$scriptRecebimento}");
    shell_exec("docker exec funcional-org1-http-1 {$scriptRecebimento}");
  }
}
