<?php

class SecaoDocumentoFixture extends FixtureBase
{
    protected $objProtocoloDTO;
    protected $objProtocoloRN;

    const MODELO_ACORDAO = 43;

    public function __construct()
    {
        $this->objProtocoloRN = new \ProtocoloRN();
        $this->objProtocoloDTO = new \ProtocoloDTO();
    }
 
    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    protected function cadastrar($dados = [])
    {
        $documento = $dados['documento'] ?: false;

        $objSecaoDocumentoDTO = new \SecaoDocumentoDTO();
        $objSecaoDocumentoDTO->setNumOrdem($dados['Ordem'] ?: 0);
        $objSecaoDocumentoDTO->setStrSinSomenteLeitura($dados['SomenteLeitura'] ?: null); 
        $objSecaoDocumentoDTO->setStrSinAssinatura($dados['Assinatura'] ?: null);
        $objSecaoDocumentoDTO->setStrSinPrincipal($dados['Principal'] ?: null);
        $objSecaoDocumentoDTO->setStrSinDinamica($dados['Dinamica'] ?: null);
        $objSecaoDocumentoDTO->setStrSinCabecalho($dados['Cabecalho'] ?: null);
        $objSecaoDocumentoDTO->setStrSinRodape($dados['Rodape'] ?: null);
        $objSecaoDocumentoDTO->setStrSinHtml($dados['Html'] ?: null);
        $objSecaoDocumentoDTO->setNumIdSecaoModelo($dados['IdSecaoModelo'] ?: null);  
        $objSecaoDocumentoDTO->setDblIdDocumento($dados['IdDocumento'] ?: null);
        $objSecaoDocumentoDTO->setNumIdBaseConhecimento($dados['IdBaseConhecimento'] ?: null);
        $objSecaoDocumentoDTO->setStrConteudo($dados['Conteudo'] ?: null);

        
        $objProtocoloDB = new \SecaoDocumentoBD(\BancoSEI::getInstance());
        $objProtocoloDB->cadastrar($objSecaoDocumentoDTO);

        return $objSecaoDocumentoDTO;
    }

    public function conteudoEstatico($IdDocumento)
    {
        return [
            [
                'IdSecaoModelo' => 323,
                'IdDocumento' => $IdDocumento,
                'Ordem' => 0,
                'SomenteLeitura' => 'S',
                'Assinatura' => 'N',
                'Principal' => 'N',
                'Dinamica' => 'S',
                'Cabecalho' => 'S',
                'Rodape' => 'N',
                'Conteudo' => '<p class="Texto_Centralizado_Maiusculas_Negrito">&nbsp;&nbsp;</p>
                <p class="Texto_Centralizado_Maiusculas_Negrito">@descricao_orgao_maiusculas@</p>
                <p class="Texto_Centralizado_Maiusculas_Negrito">&nbsp;&nbsp;</p>',
                'Html' => 'S'
            ],
            [
                'IdSecaoModelo' => 252,
                'IdDocumento' => $IdDocumento,
                'Ordem' => 10,
                'SomenteLeitura' => 'S',
                'Assinatura' => 'N',
                'Principal' => 'N',
                'Dinamica' => 'S',
                'Cabecalho' => 'N',
                'Rodape' => 'N',
                'Conteudo' => '@serie@ n&ordm; @numeracao_serie@/@ano@',
                'Html' => 'N'
            ],
            [
                'IdSecaoModelo' => 253,
                'IdDocumento' => $IdDocumento,
                'Ordem' => 20,
                'SomenteLeitura' => 'S',
                'Assinatura' => 'N',
                'Principal' => 'N',
                'Dinamica' => 'S',
                'Cabecalho' => 'N',
                'Rodape' => 'N',
                'Conteudo' => 'Processo n&ordm; @processo@',
                'Html' => 'N'
            ],
            [
                'IdSecaoModelo' => 254,
                'IdDocumento' => $IdDocumento,
                'Ordem' => 30,
                'SomenteLeitura' => 'S',
                'Assinatura' => 'N',
                'Principal' => 'N',
                'Dinamica' => 'S',
                'Cabecalho' => 'N',
                'Rodape' => 'N',
                'Conteudo' => 'Recorrente/Interessado: @interessados_virgula_espaco_maiusculas@',
                'Html' => 'N'
                
            ],
            [
                'IdSecaoModelo' => 257,
                'IdDocumento' => $IdDocumento,
                'Ordem' => 40,
                'SomenteLeitura' => 'N',
                'Assinatura' => 'N',
                'Principal' => 'S',
                'Dinamica' => 'N',
                'Cabecalho' => 'N',
                'Rodape' => 'N',
                'Conteudo' => '<p class="Texto_Justificado">CNPJ/MF N&ordm; XX.XXX.XXX/XXXX-DV</p>
                <p class="Texto_Justificado">Conselheiro Relator: [Digite aqui o Nome Completo]</p>
                <p class="Texto_Justificado">F&oacute;rum Deliberativo: Reuni&atilde;o n&ordm; [indique o n&uacute;mero], de DD de mmmmmm de aaaaa</p>
                <p class="Texto_Centralizado_Maiusculas_Negrito">EMENTA</p>               
                <p class="Texto_Justificado_Recuo_Primeira_Linha">DIGITE O TEXTO EM CAIXA ALTA</p>
                <p class="Paragrafo_Numerado_Nivel1" style="margin-left: 120px;">Clique aqui para digitar o texto.</p>
                <p class="Paragrafo_Numerado_Nivel1" style="margin-left: 120px;">Clique aqui para digitar o texto.</p>
                <p class="Paragrafo_Numerado_Nivel1" style="margin-left: 120px;">Clique aqui para digitar o texto.</p>
                <p class="Texto_Centralizado_Maiusculas_Negrito">AC&Oacute;RD&Atilde;O</p>
                <p class="Texto_Justificado_Recuo_Primeira_Linha">Vistos, relatados e discutidos os presentes autos, acordam os membros do Conselho Diretor, por unanimidade, nos termos da An&aacute;lise n&ordm; XX/AAAA-GCxx, de dd de mmmmmm de aaaaa, integrante deste Ac&oacute;rd&atilde;o:</p>
                <p class="Item_Alinea_Letra">Clique aqui para digitar o texto.</p>
                <p class="Item_Alinea_Letra">Clique aqui para digitar o texto.</p>
                <p class="Item_Alinea_Letra">Clique aqui para digitar o texto.</p>
                <p class="Texto_Justificado_Recuo_Primeira_Linha">Participaram da delibera&ccedil;&atilde;o o Presidente [nome completo] e os Conselheiros [nome completo de cada Conselheiro participante].</p>
                <p class="Texto_Justificado_Recuo_Primeira_Linha">Ausente, justificadamente, o Conselheiro [nome completo], por motivo de [indicar o motivo].</p>
                ',
                'Html' => 'S'
            ],
            [
                'IdSecaoModelo' => 255,
                'IdDocumento' => $IdDocumento,
                'Ordem' => 50,
                'SomenteLeitura' => 'N',
                'Assinatura' => 'S',
                'Principal' => 'N',
                'Dinamica' => 'N',
                'Cabecalho' => 'N',
                'Rodape' => 'N',
                'Conteudo' => null,
                'Html' => 'N'
            ],
            [
                'IdSecaoModelo' => 324,
                'IdDocumento' => $IdDocumento,
                'Ordem' => 1000,
                'SomenteLeitura' => 'S',
                'Assinatura' => 'N',
                'Principal' => 'N',
                'Dinamica' => 'S',
                'Cabecalho' => 'N',
                'Rodape' => 'S',
                'Conteudo' => '<hr style="border:none; padding:0; margin:5px 2px 0 2px; border-top:medium double #333" />
                <table border="0" cellpadding="2" cellspacing="0" width="100%">
                <tr>
                <td align="left" style="font-family:Calibri;font-size:9pt;border:0;" width="50%"><strong>Refer&ecirc;ncia:</strong> Processo n&ordm; @processo@</td>
                <td align="right" style="font-family:Calibri;font-size:9pt;border:0;" width="50%">SEI n&ordm; @documento@</td>
                </tr>
                </table>',
                'Html' => 'S'
            ]
        ];
    }
}
