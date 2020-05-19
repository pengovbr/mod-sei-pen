<?php

class Cenario005Test extends CenarioBaseTestCase
{   
    public function test_devolucao_processo_contendo_documento_externo()
    {
        // Configuração de processo para trâmite entre órgão A --> B
        $remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        $destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);          
        $processoTeste = $this->gerarDadosProcessoTeste($remetente);
        $documentoTeste1 = $this->gerarDadosDocumentoExternoTeste($remetente);
        
        // Trâmite entre órgão A --> B
        $this->realizarTramiteExternoSemvalidacaoNoRemetente($processoTeste, $documentoTeste1, $remetente, $destinatario);
        $this->paginaBase->sairSistema();

        // Inverte fluxo, enviado o processo de volta para órgão A (B --> A)
        $remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A); 
        $documentoTeste2 = $this->gerarDadosDocumentoExternoTeste($remetente);
        
        // Trâmite entre órgão B --> A
        $this->realizarTramiteExternoComvalidacaoNoRemetente($processoTeste, $documentoTeste2, $remetente, $destinatario);
        
        $this->paginaBase->sairSistema();

        $listaDocumentos = array($documentoTeste1, $documentoTeste2);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario($processoTeste, $listaDocumentos, $destinatario);
    }
}
