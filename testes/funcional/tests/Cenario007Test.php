<?php

class Cenario007Test extends CenarioBaseTestCase
{
    public function test_tramitar_documento_interno_nao_mapeado_origem()
    {
        // Configuração do dados para teste do cenário
        $remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        $destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $processoTeste = $this->gerarDadosProcessoTeste($remetente);
        $documentoTeste = $this->gerarDadosDocumentoExternoTeste($remetente);

        $this->acessarSistema($remetente['URL'], $remetente['SIGLA_UNIDADE'], $remetente['LOGIN'], $remetente['SENHA']);
        $strProtocoloTeste = $this->cadastrarProcesso($processoTeste);

        // Configuração de documento não mapeado na origem
        $documentoTeste['TIPO_DOCUMENTO'] = $remetente['TIPO_DOCUMENTO_NAO_MAPEADO'];
        $this->cadastrarDocumentoInterno($documentoTeste);
        $this->assinarDocumento($remetente['ORGAO'], $remetente['CARGO_ASSINATURA'], $remetente['SENHA']);

        $mensagemEsperada = sprintf("Não existe mapeamento de envio para %s no documento", $documentoTeste["TIPO_DOCUMENTO"]);        
        $this->setExpectedException('Exception', $mensagemEsperada);
        $this->tramitarProcessoExternamente($strProtocoloTeste, $destinatario['REP_ESTRUTURAS'], $destinatario['NOME_UNIDADE'], $destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);
    }

    public function test_tramitar_documento_externo_nao_mapeado_origem()
    {
        // Configuração do dados para teste do cenário
        $remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        $destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $processoTeste = $this->gerarDadosProcessoTeste($remetente);
        $documentoTeste = $this->gerarDadosDocumentoExternoTeste($remetente);

        $this->acessarSistema($remetente['URL'], $remetente['SIGLA_UNIDADE'], $remetente['LOGIN'], $remetente['SENHA']);
        $strProtocoloTeste = $this->cadastrarProcesso($processoTeste);

        // Configuração de documento não mapeado na origem
        $documentoTeste['TIPO_DOCUMENTO'] = $remetente['TIPO_DOCUMENTO_NAO_MAPEADO'];
        $this->cadastrarDocumentoExterno($documentoTeste);

        $mensagemEsperada = sprintf("Não existe mapeamento de envio para %s no documento", $documentoTeste["TIPO_DOCUMENTO"]);        
        $this->setExpectedException('Exception', $mensagemEsperada);
        $this->tramitarProcessoExternamente($strProtocoloTeste, $destinatario['REP_ESTRUTURAS'], $destinatario['NOME_UNIDADE'], 
            $destinatario['SIGLA_UNIDADE_HIERARQUIA'], false);
    }
}
