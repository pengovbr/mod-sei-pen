<?php

class Cenario008Test extends CenarioBaseTestCase
{
    public function test_tramitar_documento_interno_nao_mapeado_destino()
    {
        // Configuração do dados para teste do cenário
        $remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        $destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $processoTeste = $this->gerarDadosProcessoTeste($remetente);
        $documentoTeste = $this->gerarDadosDocumentoInternoTeste($remetente);

        $this->acessarSistema($remetente['URL'], $remetente['SIGLA_UNIDADE'], $remetente['LOGIN'], $remetente['SENHA']);
        $strProtocoloTeste = $this->cadastrarProcesso($processoTeste);

        // Configuração de documento não mapeado na origem
        $documentoTeste['TIPO_DOCUMENTO'] = $destinatario['TIPO_DOCUMENTO_NAO_MAPEADO'];
        $this->cadastrarDocumentoInterno($documentoTeste);
        $this->assinarDocumento($remetente['ORGAO'], $remetente['CARGO_ASSINATURA'], $remetente['SENHA']);
        $this->tramitarProcessoExternamente($strProtocoloTeste, $destinatario['REP_ESTRUTURAS'], $destinatario['NOME_UNIDADE'], 
            $destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);

        // 6 - Verificar se situação atual do processo está como bloqueado
        $this->waitUntil(function($testCase) {
            sleep(5);
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertContains('Processo aberto somente na unidade', $paginaProcesso->informacao());        
            $testCase->assertFalse($paginaProcesso->processoBloqueado());
            $testCase->assertTrue($paginaProcesso->processoAberto());            
            return true;
        }, PEN_WAIT_TIMEOUT);

        $this->validarHistoricoTramite($destinatario['NOME_UNIDADE'], true, false, true, 
            sprintf("Documento do tipo %s não está mapeado", $destinatario['TIPO_DOCUMENTO_NAO_MAPEADO']));

        $this->validarRecibosTramite(sprintf("Trâmite externo do Processo %s para %s", $strProtocoloTeste, $destinatario['NOME_UNIDADE']) , true, false);
        $this->validarProcessosTramitados($strProtocoloTeste, false);

        //Verifica se os ícones de alerta de recusa foram adicionados e se o processo continua aberto na unidade
        $this->paginaBase->navegarParaControleProcesso();
        $this->assertTrue($this->paginaControleProcesso->contemProcesso($strProtocoloTeste));
        $this->assertTrue($this->paginaControleProcesso->contemAlertaProcessoRecusado($strProtocoloTeste));

        $this->paginaBase->sairSistema();
        $this->realizarValidacaoNAORecebimentoProcessoNoDestinatario($destinatario, $processoTeste);
    }
}
