<?php

class Cenario011Test extends CenarioBaseTestCase
{
    //teste no momento não é possível devido a velocidade de envio
    // public function teste_cancelamento_tramite_contendo_documento_interno()
    // {
    //     // Configuração do dados para teste do cenário
    //     $remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
    //     $destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
    //     $processoTeste = $this->gerarDadosProcessoTeste($remetente);
    //     $documentoTeste = $this->gerarDadosDocumentoInternoTeste($remetente);

    //     $this->acessarSistema($remetente['URL'], $remetente['SIGLA_UNIDADE'], $remetente['LOGIN'], $remetente['SENHA']);
    //     $strProtocoloTeste = $this->cadastrarProcesso($processoTeste);

    //     $this->cadastrarDocumentoInterno($documentoTeste);
    //     $this->assinarDocumento($remetente['ORGAO'], $remetente['CARGO_ASSINATURA'], $remetente['SENHA']);
    //     $this->tramitarProcessoExternamente($strProtocoloTeste, $destinatario['REP_ESTRUTURAS'], $destinatario['NOME_UNIDADE'], $destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);
        
    //     $this->paginaProcesso->cancelarTramitacaoExterna();        
    //     $mensagemAlerta = $this->paginaTramitar->alertTextAndClose(true);
    //     $mensagemEsperada = "O trâmite externo do processo foi cancelado com sucesso!";   
    //     $this->assertContains($mensagemEsperada, $mensagemAlerta);        
    //     $this->assertFalse($this->paginaProcesso->processoBloqueado());
    //     $this->assertTrue($this->paginaProcesso->processoAberto());
        
    //     $this->validarRecibosTramite(sprintf("Trâmite externo do Processo %s para %s", 
    //         $strProtocoloTeste, $destinatario['NOME_UNIDADE']) , true, false);
    //     $this->validarHistoricoTramite($destinatario['NOME_UNIDADE'], true, false);
    //     $this->validarProcessosTramitados($strProtocoloTeste, false);
        
    //     //Verifica se os ícones de alerta de recusa foram adicionados e se o processo continua aberto na unidade
    //     $this->paginaBase->navegarParaControleProcesso();
    //     $this->assertTrue($this->paginaControleProcesso->contemProcesso($strProtocoloTeste));
    //     $this->assertFalse($this->paginaControleProcesso->contemAlertaProcessoRecusado($strProtocoloTeste));

    //     $this->realizarValidacaoNAORecebimentoProcessoNoDestinatario($destinatario, $processoTeste);
    // }

    public function teste_cancelamento_tramite_contendo_documento_externo()
    {
        // Configuração de processo para trâmite entre órgão A --> B
        $remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        $destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $processoTeste = $this->gerarDadosProcessoTeste($remetente);
        $documentoTeste = $this->gerarDadosDocumentoExternoTeste($remetente, 'arquivo_010.pdf');
        
        $this->acessarSistema($remetente['URL'], $remetente['SIGLA_UNIDADE'], $remetente['LOGIN'], $remetente['SENHA']);
        $strProtocoloTeste = $this->cadastrarProcesso($processoTeste);

        $this->cadastrarDocumentoExterno($documentoTeste);
        $this->tramitarProcessoExternamente($strProtocoloTeste, $destinatario['REP_ESTRUTURAS'], $destinatario['NOME_UNIDADE'], $destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);

        $this->paginaProcesso->cancelarTramitacaoExterna();        
        sleep(2);
        $mensagemAlerta = $this->paginaTramitar->alertTextAndClose(true);
        $mensagemEsperada = "O trâmite externo do processo foi cancelado com sucesso!";   
        sleep(2);   
        $this->assertContains($mensagemEsperada, $mensagemAlerta);        
        $this->assertFalse($this->paginaProcesso->processoBloqueado());
        $this->assertTrue($this->paginaProcesso->processoAberto());

        $this->validarRecibosTramite(sprintf("Trâmite externo do Processo %s para %s", $strProtocoloTeste, 
            $destinatario['NOME_UNIDADE']) , true, false);
        $this->validarHistoricoTramite($destinatario['NOME_UNIDADE'], true, false);
        $this->validarProcessosTramitados($strProtocoloTeste, false);
        
        //Verifica se os ícones de alerta de recusa foram adicionados e se o processo continua aberto na unidade
        $this->paginaBase->navegarParaControleProcesso();
        $this->assertTrue($this->paginaControleProcesso->contemProcesso($strProtocoloTeste));
        $this->assertFalse($this->paginaControleProcesso->contemAlertaProcessoRecusado($strProtocoloTeste));

        $this->paginaBase->sairSistema();
        $this->realizarValidacaoNAORecebimentoProcessoNoDestinatario($destinatario, $processoTeste);
    }  

    //está dando um erro, aguardando solução
    // Neste caso, o processo deverá sofre duas ações distintas e simultâneas: Rejeição pelo destinatário e cancelamento pela origem. Nos testes
    //  * iniciais, o módulo de integração não soube tratar esta situação, gerando erro na operação de cancelamento
    // public function teste_cancelamento_tramite_contendo_documento_externo_arquivo_maior_que_permitido()
    // {
    //     // Configuração de processo para trâmite entre órgão A --> B
    //     $remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
    //     $destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
    //     $processoTeste = $this->gerarDadosProcessoTeste($remetente);
    //     $documentoTeste = $this->gerarDadosDocumentoExternoTeste($remetente, 'arquivo_020.pdf');
        
    //     $this->acessarSistema($remetente['URL'], $remetente['SIGLA_UNIDADE'], $remetente['LOGIN'], $remetente['SENHA']);
    //     $strProtocoloTeste = $this->cadastrarProcesso($processoTeste);

    //     $this->cadastrarDocumentoExterno($documentoTeste);
    //     $this->tramitarProcessoExternamente($strProtocoloTeste, $destinatario['REP_ESTRUTURAS'], $destinatario['NOME_UNIDADE'], $destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);

    //     $this->paginaProcesso->cancelarTramitacaoExterna();        
    //     sleep(2);
    //     $mensagemAlerta = $this->paginaTramitar->alertTextAndClose(true);
    //     $mensagemEsperada = "O trâmite externo do processo foi cancelado com sucesso!";   
    //     sleep(2);   
    //     $this->assertContains($mensagemEsperada, $mensagemAlerta);        
    //     $this->assertFalse($this->paginaProcesso->processoBloqueado());
    //     $this->assertTrue($this->paginaProcesso->processoAberto());

    //     $this->validarRecibosTramite(sprintf("Trâmite externo do Processo %s para %s", $strProtocoloTeste, 
    //         $destinatario['NOME_UNIDADE']) , true, false);
    //     $this->validarHistoricoTramite($destinatario['NOME_UNIDADE'], true, false);
    //     $this->validarProcessosTramitados($strProtocoloTeste, false);
        
    //     //Verifica se os ícones de alerta de recusa foram adicionados e se o processo continua aberto na unidade
    //     $this->paginaBase->navegarParaControleProcesso();
    //     $this->assertTrue($this->paginaControleProcesso->contemProcesso($strProtocoloTeste));
    //     $this->assertFalse($this->paginaControleProcesso->contemAlertaProcessoRecusado($strProtocoloTeste));

    //     $this->realizarValidacaoNAORecebimentoProcessoNoDestinatario($destinatario, $processoTeste);
    // }   
}
