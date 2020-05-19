<?php

use \utilphp\util;

class Cenario027Test extends CenarioBaseTestCase
{
    public function test_tramite_contendo_documento_externo_arquivo_maior_que_cinquenta_mb()
    {
        // Configuração de processo para trâmite entre órgão A --> B
        $remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        $destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $processoTeste = $this->gerarDadosProcessoTeste($remetente);
        $documentoTeste = $this->gerarDadosDocumentoExternoTeste($remetente, 'arquivo_060.pdf');

        $orgaosDiferentes = $remetente['ORGAO'] != $destinatario['ORGAO'];
        
        $this->acessarSistema($remetente['URL'], $remetente['SIGLA_UNIDADE'], $remetente['LOGIN'], $remetente['SENHA']);
        $strProtocoloTeste = $this->cadastrarProcesso($processoTeste);

        $this->cadastrarDocumentoExterno($documentoTeste);
        $this->tramitarProcessoExternamente($strProtocoloTeste, $destinatario['REP_ESTRUTURAS'], $destinatario['NOME_UNIDADE'], $destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);

        $this->waitUntil(function($testCase) use (&$orgaosDiferentes) {
            sleep(5);
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertNotContains('Processo em trâmite externo para ', $paginaProcesso->informacao());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            $testCase->assertTrue($paginaProcesso->processoAberto());
            return true;
        }, PEN_WAIT_TIMEOUT_ARQUIVOS_GRANDES);

        $this->validarRecibosTramite(sprintf("Trâmite externo do Processo %s para %s", $strProtocoloTeste, 
            $destinatario['NOME_UNIDADE']) , true, false);
        $this->validarHistoricoTramite($destinatario['NOME_UNIDADE'], true, false);
        $this->validarProcessosTramitados($strProtocoloTeste, false);
        
        //Verifica se os ícones de alerta de recusa foram adicionados e se o processo continua aberto na unidade
        $this->paginaBase->navegarParaControleProcesso();
        $this->assertTrue($this->paginaControleProcesso->contemProcesso($strProtocoloTeste));
        $this->assertTrue($this->paginaControleProcesso->contemAlertaProcessoRecusado($strProtocoloTeste));

        $this->paginaBase->sairSistema();
        $this->realizarValidacaoNAORecebimentoProcessoNoDestinatario($destinatario, $processoTeste);
    }
}
