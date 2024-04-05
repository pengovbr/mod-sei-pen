<?php

class DocumentoFixture extends \FixtureBase
{
    protected $objDocumentoDTO;
    
    const MODELO_ACORDAO = 43;

    public function __construct()
    {
        $this->objDocumentoDTO = new \DocumentoDTO();
    }
 
    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }
    
    protected function cadastrar($dados = [])
    {
        // $dados['IdProtocolo'] = $this->getObjInfraIBanco()->getValorSequencia('seq_protocolo');
        // $dados['IdDocumento'] = $this->getObjInfraIBanco()->getValorSequencia('seq_documento');
        
        $protocoloFixture = new \ProtocoloFixture();
        $protocoloDTO = $protocoloFixture->cadastrar(
            [
                'StaProtocolo' => \ProtocoloRN::$TP_DOCUMENTO_GERADO,
                'documento' => true,
            ]
        );

        $this->objDocumentoDTO->setDblIdDocumento($protocoloDTO->getDblIdProtocolo());
        $this->objDocumentoDTO->setDblIdDocumentoEdoc($dados['IdDocumentoEdoc'] ?: null); 
        $this->objDocumentoDTO->setDblIdProcedimento($dados['IdProcedimento']);
        $this->objDocumentoDTO->setNumIdSerie($dados['IdSerie'] ?: 8);
        $this->objDocumentoDTO->setNumIdUnidadeResponsavel($dados['IdUnidadeResponsavel'] ?: 110000001);
        $this->objDocumentoDTO->setNumIdConjuntoEstilos($dados['IdConjuntoEstilos'] ?: 81);
        $this->objDocumentoDTO->setNumIdTipoConferencia($dados['IdTipoConferencia'] ?: null);
        $this->objDocumentoDTO->setStrSinArquivamento($dados['SinArquivamento'] ?: 'N');    
        $this->objDocumentoDTO->setStrNumero($dados['Numero'] ?: 1);
        $this->objDocumentoDTO->setStrSinBloqueado($dados['SinBloqueado'] ?: 'N');
        $this->objDocumentoDTO->setStrStaDocumento($dados['StaDocumento'] ?: DocumentoRN::$TD_EDITOR_INTERNO);
        $this->objDocumentoDTO->setStrNomeArvore($dados['SinBloqueado'] ?? 'teste');

        $objDocumentoDB = new \DocumentoBD(\BancoSEI::getInstance());
        $objDocumentoDB->cadastrar($this->objDocumentoDTO);

        $objAtividadeFixture = new \AtividadeFixture();
        $objAtividadeDTO = $objAtividadeFixture->cadastrar(
            [
                'IdProtocolo' => $protocoloDTO->getDblIdProtocolo(),
                'IdTarefa' => TarefaRN::$TI_GERACAO_DOCUMENTO,
            ]
        );
        
        $objParticipanteFixture = new \ParticipanteFixture();
        $objParticipanteFixture->carregar([
            'IdProtocolo' => $protocoloDTO->getDblIdProtocolo(),
        ]);
        
        $objAtributoAndamentoFixture = new \AtributoAndamentoFixture();
        $objAtributoAndamentoFixture->carregarVariados([
            [
                'IdAtividade' => $objAtividadeDTO->getNumIdAtividade(),
                'Nome' => 'DOCUMENTO',
                'Valor' => $protocoloDTO->getStrProtocoloFormatado(),
                'IdOrigem' => $dados['IdProtocolo'],
            ],
            [
                'IdAtividade' => $objAtividadeDTO->getNumIdAtividade()
            ]
        ]);

        $objDocumentoConteudoFixture = new \DocumentoConteudoFixture();
        $objDocumentoConteudoFixture->cadastrar(
            [
                'IdDocumento' => $this->objDocumentoDTO->getDblIdDocumento(),
            ]
        );

        $objProtocoloAssuntoFixture = new \RelProtocoloAssuntoFixture();
        $objProtocoloAssuntoFixture->carregar([
            'IdProtocolo' => $protocoloDTO->getDblIdProtocolo(),
            'IdAssunto' => 2
        ]);

        $ObjProtocoloProtocoloFixture = new \RelProtocoloProtocoloFixture();
        $ObjProtocoloProtocoloFixture->cadastrar(
            [
                'IdProtocolo' => $dados['IdProtocolo'],
                'IdDocumento' => $this->objDocumentoDTO->getDblIdDocumento(),
            ]
        );

        $secaoDocumentoFixture = new \SecaoDocumentoFixture();
        $listaSecao = $secaoDocumentoFixture->conteudoEstatico($this->objDocumentoDTO->getDblIdDocumento());

        $secaoDocumentoFixture->carregarVariados($listaSecao);

        return $this->objDocumentoDTO;
    }
}