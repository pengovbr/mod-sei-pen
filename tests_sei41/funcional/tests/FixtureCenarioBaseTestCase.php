<?php

use \utilphp\util;
use PHPUnit\Extensions\Selenium2TestCase;
use Tests\Funcional\Sei\Fixtures\{ProtocoloFixture,ProcedimentoFixture,AtividadeFixture,ContatoFixture};
use Tests\Funcional\Sei\Fixtures\{ParticipanteFixture,RelProtocoloAssuntoFixture,AtributoAndamentoFixture};
use Tests\Funcional\Sei\Fixtures\{DocumentoFixture,AssinaturaFixture,AnexoFixture,AnexoProcessoFixture};
use Tests\Funcional\Sei\Fixtures\{HipoteseLegalFixture,TipoProcedimentoFixture};

use function PHPSTORM_META\map;
/**
 * Classe base contendo rotinas comuns utilizadas nos casos de teste do m�dulo que utiliza fixture
 */
class FixtureCenarioBaseTestCase extends CenarioBaseTestCase
{
    protected function cadastrarProcessoFixture(&$dadosProcesso, $cadastrarParticipante = true)
    {
        if (!is_null($dadosProcesso['HIPOTESE_LEGAL'])){
            $objHipLegalDTO = $this->buscarHipoteseLegal($dadosProcesso);
        }

        $parametros = [
            'Descricao' => $dadosProcesso['DESCRICAO'] ?: util::random_string(20),
            'Interessados' => $dadosProcesso['INTERESSADOS'] ?: util::random_string(40),
            'IdHipoteseLegal' => $dadosProcesso['HIPOTESE_LEGAL'] ? $objHipLegalDTO->getNumIdHipoteseLegal() : null,
            'StaNivelAcessoLocal' => $dadosProcesso["RESTRICAO"] ?: PaginaIniciarProcesso::STA_NIVEL_ACESSO_PUBLICO,
            'StaNivelAcessoGlobal' => $dadosProcesso["RESTRICAO"] ?: PaginaIniciarProcesso::STA_NIVEL_ACESSO_PUBLICO
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
            'Nome' => trim(explode('(',$dados['HIPOTESE_LEGAL'])[0]),
            'BaseLegal' => explode(')',trim(explode('(',$dados['HIPOTESE_LEGAL'])[1]))[0]
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
            'IdConjuntoEstilos' => NULL,
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
        // Realizar a anexa��o de processos
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

    protected function realizarTramiteExternoFixture(&$processoTeste, $documentosTeste, $remetente, $destinatario, $validarTramite)
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
                $this->cadastrarDocumentoInternoFixture($doc,$objProtocoloDTO->getDblIdProtocolo());
            } else if ($doc['TIPO'] == 'R') {
                $this->cadastrarDocumentoExternoFixture($doc, $objProtocoloDTO->getDblIdProtocolo());
            }
        }

        // 3 - Acessar sistema do REMETENTE do processo
        $this->acessarSistema($remetente['URL'], $remetente['SIGLA_UNIDADE'], $remetente['LOGIN'], $remetente['SENHA']);

        // 4 - Abrir processo
        $this->abrirProcesso($strProtocoloTeste);
        
        // 5 - Tr�mitar Externamento processo para �rg�o/unidade destinat�ria
        $paginaTramitar = $this->paginaTramitar;
        $this->tramitarProcessoExternamente($strProtocoloTeste, $destinatario['REP_ESTRUTURAS'], $destinatario['NOME_UNIDADE'], $destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);

        if ($validarTramite) {
            // 6 - Verificar se situa��o atual do processo est� como bloqueado
            $this->waitUntil(function ($testCase) use (&$orgaosDiferentes) {
                sleep(5);
                $testCase->refresh();
                $paginaProcesso = new PaginaProcesso($testCase);
                $testCase->assertStringNotContainsString(mb_convert_encoding("Processo em tr�mite externo para ", 'UTF-8', 'ISO-8859-1'), $paginaProcesso->informacao());
                $testCase->assertFalse($paginaProcesso->processoAberto());
                $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
                return true;
            }, PEN_WAIT_TIMEOUT);

            // 7 - Validar se recibo de tr�mite foi armazenado para o processo (envio e conclus�o)
            $unidade = mb_convert_encoding($destinatario['NOME_UNIDADE'], "ISO-8859-1");
            $mensagemRecibo = sprintf("Tr�mite externo do Processo %s para %s", $strProtocoloTeste, $unidade);
            $this->validarRecibosTramite($mensagemRecibo, true, true);

            // 8 - Validar hist�rico de tr�mite do processo
            $this->validarHistoricoTramite(self::$nomeUnidadeDestinatario, true, true);

            // 9 - Verificar se processo est� na lista de Processos Tramitados Externamente
            $deveExistir = $remetente['URL'] != $destinatario['URL'];
            $this->validarProcessosTramitados($strProtocoloTeste, $deveExistir);
        }
    }
    
    public function realizarTramiteExternoComValidacaoNoRemetenteFixture(&$processoTeste, $documentosTeste, $remetente, $destinatario)
    {
        $this->realizarTramiteExternoFixture($processoTeste, $documentosTeste, $remetente, $destinatario, true);
    }

    public function realizarTramiteExternoSemValidacaoNoRemetenteFixture(&$processoTeste, $documentosTeste, $remetente, $destinatario)
    {
        $this->realizarTramiteExternoFixture($processoTeste, $documentosTeste, $remetente, $destinatario, false);
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
   * M�todo cadastrarHipoteseLegal
   * 
   * Este m�todo realiza o cadastro de uma hip�tese legal para testes de tr�mite de processos e documentos.
   * Ele recebe um array com os dados da hip�tese legal, cria uma nova inst�ncia de `HipoteseLegalFixture`, 
   * e utiliza esses dados para carregar a hip�tese legal no sistema.
   * 
   * @param array $hipotesLegal Um array contendo os dados da hip�tese legal a ser cadastrada, com as seguintes chaves:
   * - `HIPOTESE_LEGAL` (string): O nome da hip�tese legal.
   * - `HIPOTESE_LEGAL_BASE_LEGAL` (string): A base legal associada � hip�tese.
   * - `HIPOTESE_LEGAL_DESCRICAO` (string) [opcional]: Uma descri��o para a hip�tese legal (padr�o: 'Nova hipotese legal para testes').
   * - `HIPOTESE_LEGAL_STA_NIVEL_ACESSO` (int) [opcional]: O n�vel de acesso para a hip�tese legal (padr�o: n�vel restrito).
   * - `HIPOTESE_LEGAL_SIN_ATIVO` (string) [opcional]: Indicador de atividade da hip�tese legal ('S' para ativo por padr�o).
   * 
   * @return object $objHipoteseLegalDTO Retorna um objeto `HipoteseLegalDTO` contendo os dados da hip�tese legal cadastrada.
   */
  protected function cadastrarHipoteseLegal($hipotesLegal)
  {
    // Cria��o de uma nova inst�ncia de HipoteseLegalFixture
    $objHipLegalFixture = new HipoteseLegalFixture();

    // Defini��o dos par�metros para cadastro da hip�tese legal
    $param = [
      'Nome' => $hipotesLegal['HIPOTESE_LEGAL'],
      'BaseLegal' => $hipotesLegal['HIPOTESE_LEGAL_BASE_LEGAL'],
      'Descricao' => $hipotesLegal['HIPOTESE_LEGAL_DESCRICAO'] ?? 'Nova hipotese legal para testes',
      'StaNivelAcesso' => $hipotesLegal['HIPOTESE_LEGAL_STA_NIVEL_ACESSO'] ?? \ProtocoloRN::$NA_RESTRITO,
      'SinAtivo' => $hipotesLegal['HIPOTESE_LEGAL_SIN_ATIVO'] ?? "S"
    ];

    // Carregar a hip�tese legal com os par�metros fornecidos
    $objHipoteseLegalDTO = $objHipLegalFixture->carregar($param);

    // Retorna o objeto DTO da hip�tese legal cadastrada
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

}
