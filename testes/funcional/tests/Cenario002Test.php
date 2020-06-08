<?php

use \utilphp\util;

class Cenario002Test extends CenarioBaseTestCase
{
  
    public function test_tramitar_processo_contendo_documento_externo()
    {
        // Configuração do dados para teste do cenário
        $remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        $destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $processoTeste = $this->gerarDadosProcessoTeste($remetente);
        $documentoTeste = $this->gerarDadosDocumentoExternoTeste($remetente);

        $orgaosDiferentes = $remetente['ORGAO'] != $destinatario['ORGAO'];

        // 1 - Acessar sistema do REMETENTE do processo
        $this->acessarSistema($remetente['URL'], $remetente['SIGLA_UNIDADE'], $remetente['LOGIN'], $remetente['SENHA']);

        // 2 - Cadastrar novo processo de teste
        $strProtocoloTeste = $this->cadastrarProcesso($processoTeste);   

        // 3 - Incluir Documentos no Processo
        $this->cadastrarDocumentoExterno($documentoTeste);

        // 5 - Trâmitar Externamento processo para órgão/unidade destinatária
        $this->tramitarProcessoExternamente($strProtocoloTeste, $destinatario['REP_ESTRUTURAS'], $destinatario['NOME_UNIDADE'], $destinatario['SIGLA_UNIDADE_HIERARQUIA'], false, function($testCase) {
            $testCase->window($this->windowHandles()[1]);
            $testCase->assertContains("Trâmite externo do processo finalizado com sucesso!", $testCase->byCssSelector('body')->text());
            $testCase->closeWindow();
            $testCase->window('');            
            return true;
        });
         
        // 6 - Verificar se situação atual do processo está como bloqueado
        $this->waitUntil(function($testCase) use (&$orgaosDiferentes){
            sleep(5);
            $testCase->refresh();
            $paginaProcesso = new PaginaProcesso($testCase);
            $testCase->assertNotContains('Processo em trâmite externo para ', $paginaProcesso->informacao());
            $testCase->assertFalse($paginaProcesso->processoAberto());
            $testCase->assertEquals($orgaosDiferentes, $paginaProcesso->processoBloqueado());
            return true;
        }, PEN_WAIT_TIMEOUT);

        // 7 - Validar se recibo de trâmite foi armazenado para o processo (envio e conclusão)
        $this->validarRecibosTramite(sprintf("Trâmite externo do Processo %s para %s", $strProtocoloTeste, $destinatario['NOME_UNIDADE']) , true, true);

        // 8 - Validar histórico de trâmite do processo
        $this->validarHistoricoTramite(self::$nomeUnidadeDestinatario, true, true);

        // 9 - Verificar se processo está na lista de Processos Tramitados Externamente
        $this->validarProcessosTramitados($strProtocoloTeste, $orgaosDiferentes);

        // 10 - Acessar sistema de REMETENTE do processo
        $this->paginaBase->sairSistema();
        $this->acessarSistema($destinatario['URL'], $destinatario['SIGLA_UNIDADE'], $destinatario['LOGIN'], $destinatario['SENHA']);    

        // 11 - Abrir protocolo na tela de controle de processos
        $this->abrirProcesso($strProtocoloTeste);
        $listaDocumentos = $this->paginaProcesso->listarDocumentos();
                   
        // 12 - Validar dados  do processo
        $processoTeste['OBSERVACOES'] = $orgaosDiferentes ? 'Tipo de processo no órgão de origem: ' . $processoTeste['TIPO_PROCESSO'] : null;
        $this->validarDadosProcesso($processoTeste['DESCRICAO'], $processoTeste['RESTRICAO'], $processoTeste['OBSERVACOES'], array($processoTeste['INTERESSADOS']));
        
        // 13 - Verificar recibos de trâmite
        $this->validarRecibosTramite("Recebimento do Processo $strProtocoloTeste", false, true);        

        // 14 - Validar dados do documento
        $this->assertTrue(count($listaDocumentos) == 1);
        $this->validarDadosDocumento($listaDocumentos[0], $documentoTeste, $destinatario);
    }

    public function test_tramitar_processo_contendo_documento_externo_problema_besimplesoap()
    {   
        //Excente tempo de timeout devido ao tamanho do arquivo arquivo_corrompimento_besimplesoap.pdf
        $this->setSeleniumServerRequestsTimeout(6000);

        // Configuração do dados para teste do cenário
        $remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        $destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B); 
        $processoTeste = $this->gerarDadosProcessoTeste($remetente);
        
        // Define arquivo exemplo que gerava problema de corrompimento no BeSimpleSoap
        $documentoTeste = $this->gerarDadosDocumentoExternoTeste($remetente, 'arquivo_corrompimento_besimplesoap.pdf');
        
        $this->realizarTramiteExternoComValidacaoNoRemetente($processoTeste, $documentoTeste, $remetente, $destinatario);
        $this->paginaBase->sairSistema();
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario($processoTeste, $documentoTeste, $destinatario);
    }    

    public function test_tramitar_processo_contendo_documento_externo_mesmo_orgao()
    {        
        // Configuração do dados para teste do cenário
        $remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        $processoTeste = $this->gerarDadosProcessoTeste($remetente);
        $documentoTeste = $this->gerarDadosDocumentoExternoTeste($remetente);

        //Configuração da unidade destinatário como outra unidade do mesmo órgão
        $destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A);        
        $destinatario['SIGLA_UNIDADE'] = CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA;
        $destinatario['NOME_UNIDADE'] = CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA;
        $destinatario['SIGLA_UNIDADE_HIERARQUIA'] = CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA;
        
        $this->realizarTramiteExternoComValidacaoNoRemetente($processoTeste, $documentoTeste, $remetente, $destinatario);

        $this->paginaBase->sairSistema();

        $this->realizarValidacaoRecebimentoProcessoNoDestinatario($processoTeste, $documentoTeste, $destinatario);
    }

}
