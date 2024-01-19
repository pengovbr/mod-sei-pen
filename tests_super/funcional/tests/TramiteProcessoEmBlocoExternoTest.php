<?php

use Modpen\Tests\fixtures\{ProtocoloFixture, ProcedimentoFixture, AtividadeFixture, RelProtocoloAssuntoFixture, AtributoAndamentoFixture, DocumentoFixture, RelProtocoloProtocoloFixture, AssinaturaFixture};

/**
 * EnviarProcessoTest
 * @group group
 */
class TramiteProcessoEmBlocoExternoTest extends CenarioBaseTestCase
{
    private $objProtocoloFixture;

    function setUp(): void 
    {
        parent::setUp();
        $parametros = [];

        $this->objProtocoloFixture = new ProtocoloFixture();
        $this->objProtocoloFixture->carregar($parametros, function($objProtocoloDTO) {
            
            $objProcedimentoFixture = new ProcedimentoFixture();
            $objProcedimentoDTO = $objProcedimentoFixture->carregar([
                'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
            ]);

            $objAtividadeFixture = new AtividadeFixture();
            $objAtividadeDTO = $objAtividadeFixture->carregar([
                'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
            ]);

            $objProtocoloAssuntoFixture = new RelProtocoloAssuntoFixture();
            $objProtocoloAssuntoFixture->carregar([
                'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
            ]);

            $objAtributoAndamentoFixture = new AtributoAndamentoFixture();
            $objAtributoAndamentoFixture->carregar([
                'IdAtividade' => $objAtividadeDTO->getNumIdAtividade()
            ]);

            $objDocumentoFixture = new DocumentoFixture();
            $objDocumentoDTO = $objDocumentoFixture->carregar([
                'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
                'IdProcedimento' => $objProcedimentoDTO->getDblIdProcedimento(),
            ]);

            $objAssinaturaFixture = new AssinaturaFixture();
            $objAssinaturaFixture->carregar([
                'IdDocumento' => $objDocumentoDTO->getDblIdDocumento(),
            ]);

        });

    }

    public function teste_adicionar_processo_bloco()
    {
        
    }
}

