<?php

use \utilphp\util;

class Cenario017Test extends CenarioBaseTestCase
{
    public function test_tramitar_documento_externo_extensao_nao_mapeada_destino()
    {
        // Configuração de processo para trâmite entre órgão A --> B
        $remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        $destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $processoTeste = $this->gerarDadosProcessoTeste($remetente);
        $documentoTeste = $this->gerarDadosDocumentoExternoTeste($remetente, 'arquivo_extensao_nao_permitida.docx');

        $orgaosDiferentes = $remetente['ORGAO'] != $destinatario['ORGAO'];
        
        $this->acessarSistema($remetente['URL'], $remetente['SIGLA_UNIDADE'], $remetente['LOGIN'], $remetente['SENHA']);
        $strProtocoloTeste = $this->cadastrarProcesso($processoTeste);

        $this->cadastrarDocumentoExterno($documentoTeste);
        $this->tramitarProcessoExternamente($strProtocoloTeste, $destinatario['REP_ESTRUTURAS'], $destinatario['NOME_UNIDADE'], $destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);

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


        $this->validarHistoricoTramite($destinatario['NOME_UNIDADE'], true, false, true, "Componentes digitais com formato inválido no destinatário.");

        $this->validarRecibosTramite(sprintf("Trâmite externo do Processo %s para %s", $strProtocoloTeste, $destinatario['NOME_UNIDADE']) , true, false);
        $this->validarProcessosTramitados($strProtocoloTeste, $orgaosDiferentes);

        //Verifica se os ícones de alerta de recusa foram adicionados e se o processo continua aberto na unidade
        $this->paginaBase->navegarParaControleProcesso();
        $this->assertTrue($this->paginaControleProcesso->contemProcesso($strProtocoloTeste));
        $this->assertTrue($this->paginaControleProcesso->contemAlertaProcessoRecusado($strProtocoloTeste));

        $this->paginaBase->sairSistema();
        $this->realizarValidacaoNAORecebimentoProcessoNoDestinatario($destinatario, $processoTeste);
    } 
}
