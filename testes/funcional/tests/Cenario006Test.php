<?php

class Cenario006Test extends CenarioBaseTestCase
{   
    public function test_devolucao_processo_contendo_html_e_doc_externo()
    {
        // Configuração de processo para trâmite entre órgão A --> B
        $remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        $destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);          
        $processoTeste = $this->gerarDadosProcessoTeste($remetente);
        $documentoInterno1 = $this->gerarDadosDocumentoInternoTeste($remetente);
        $documentoExterno1 = $this->gerarDadosDocumentoExternoTeste($remetente);
        
        // Trâmite entre órgão A --> B
        $listaDocumentos1 = array($documentoInterno1, $documentoExterno1);
        $this->realizarTramiteExternoSemvalidacaoNoRemetente($processoTeste, $listaDocumentos1, $remetente, $destinatario);
        $this->paginaBase->sairSistema();

        // Inverte fluxo, enviado o processo de volta para órgão A (B --> A)
        $strProtocoloTeste = $processoTeste['PROTOCOLO'];
        $remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_A); 
        $documentoInterno2 = $this->gerarDadosDocumentoInternoTeste($remetente);
        $documentoExterno2 = $this->gerarDadosDocumentoExternoTeste($remetente);

        // Trâmite entre órgão B --> A
        $listaDocumentos2 = array($documentoInterno2, $documentoExterno2);
        $this->realizarTramiteExternoComvalidacaoNoRemetente($processoTeste, $listaDocumentos2, $remetente, $destinatario);
        
        $this->paginaBase->sairSistema();

        $listaDocumentos = array_merge($listaDocumentos1, $listaDocumentos2);
        $this->realizarValidacaoRecebimentoProcessoNoDestinatario($processoTeste, $listaDocumentos, $destinatario);
    }
}
