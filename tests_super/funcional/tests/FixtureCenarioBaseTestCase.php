<?php

use \utilphp\util;
use PHPUnit\Extensions\Selenium2TestCase;
use Tests\Funcional\Sei\Fixtures\{ProtocoloFixture,ProcedimentoFixture,AtividadeFixture,ContatoFixture};
use Tests\Funcional\Sei\Fixtures\{ParticipanteFixture,RelProtocoloAssuntoFixture,AtributoAndamentoFixture};
use Tests\Funcional\Sei\Fixtures\{DocumentoFixture,AssinaturaFixture,AnexoFixture,AnexoProcessoFixture};
use Tests\Funcional\Sei\Fixtures\{HipoteseLegalFixture};

use function PHPSTORM_META\map;
/**
 * Classe base contendo rotinas comuns utilizadas nos casos de teste do m�dulo que utiliza fixture
 */
class FixtureCenarioBaseTestCase extends CenarioBaseTestCase
{
    protected function cadastrarProcessoFixture(&$dadosProcesso)
    {

        if (!is_null($dadosProcesso['HIPOTESE_LEGAL'])){
            $param = [
                'Nome' => trim(explode('(',$dadosProcesso['HIPOTESE_LEGAL'])[0]),
                'BaseLegal' => explode(')',trim(explode('(',$dadosProcesso['HIPOTESE_LEGAL'])[1]))[0]
            ];
            $objHipLegalFixture = new HipoteseLegalFixture();
            $objHipLegalDTO = $objHipLegalFixture->buscar($param)[0];
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

        $objProcedimentoDTO = $objProcedimentoFixture->carregar([
            'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
        ]);

        $objAtividadeFixture = new AtividadeFixture();
        $objAtividadeDTO = $objAtividadeFixture->carregar([
            'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
            'Conclusao' => \InfraData::getStrDataHoraAtual(),
            'IdTarefa' => \TarefaRN::$TI_GERACAO_PROCEDIMENTO,
            'IdUsuarioConclusao' => 100000001
        ]);

        $objContatoFixture = new ContatoFixture();
        $objContatoDTO = $objContatoFixture->carregar([
            'Nome' => $parametros['Interessados']
        ]);

        $objParticipanteFixture = new ParticipanteFixture();
        $objParticipanteDTO = $objParticipanteFixture->carregar([
            'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
            'IdContato' => $objContatoDTO->getNumIdContato()
        ]);

        $objProtocoloAssuntoFixture = new RelProtocoloAssuntoFixture();
        $objProtocoloAssuntoFixture->carregar([
            'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
            'IdAssunto' => 377
        ]);

        $objAtributoAndamentoFixture = new AtributoAndamentoFixture();
        $objAtributoAndamentoFixture->carregar([
            'IdAtividade' => $objAtividadeDTO->getNumIdAtividade()
        ]);

        $dadosProcesso['PROTOCOLO'] = $objProtocoloDTO->getStrProtocoloFormatado();
        
        return $objProtocoloDTO;
    }

    protected function cadastrarDocumentoInternoFixture($dadosDocumentoInterno, $idProtocolo)
    {
        $dadosDocumentoDTO = [
            'IdProtocolo' => $idProtocolo,
            'IdProcedimento' => $idProtocolo,
            'Descricao' => $dadosDocumentoInterno['DESCRICAO'],
            'IdHipoteseLegal' => $dadosDocumentoInterno["HIPOTESE_LEGAL"] ?: null,
            'StaNivelAcessoGlobal' => $dadosDocumentoInterno["RESTRICAO"] ?: \ProtocoloRN::$NA_PUBLICO,
            'StaNivelAcessoLocal' => $dadosDocumentoInterno["RESTRICAO"] ?: \ProtocoloRN::$NA_PUBLICO,
        ];

        if ($serieDTO = $this->buscarIdSerieDoDocumento($dadosDocumentoInterno['TIPO_DOCUMENTO'])) {
            $dadosDocumentoDTO['IdSerie'] = $serieDTO->getNumIdSerie();
        }

        $objDocumentoFixture = new DocumentoFixture();
        $objDocumentoDTO = $objDocumentoFixture->carregar($dadosDocumentoDTO);

        //Adicionar assinatura ao documento
        $objAssinaturaFixture = new AssinaturaFixture();
        $objAssinaturaFixture->carregar([
            'IdProtocolo' => $idProtocolo,
            'IdDocumento' => $objDocumentoDTO->getDblIdDocumento(),
        ]);

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
    
    protected function consultarProcessoFixture($protocoloFormatado, $staProtocolo)
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

        $buscar = false;
        // 1 - Cadastrar novo processo de teste
        if (isset($processoTeste['PROTOCOLO'])) {
            $strProtocoloTeste = $processoTeste['PROTOCOLO'];

            $parametros = [
                'ProtocoloFormatado' => $strProtocoloTeste,
            ];
            $objProtocoloFixture = new ProtocoloFixture();
            $objProtocoloDTO = $objProtocoloFixture->buscar($parametros)[0];

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
                $testCase->assertStringNotContainsString(utf8_encode("Processo em tr�mite externo para "), $paginaProcesso->informacao());
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

    public function realizarTramiteExternoSemvalidacaoNoRemetenteFixture(&$processoTeste, $documentosTeste, $remetente, $destinatario)
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

}
