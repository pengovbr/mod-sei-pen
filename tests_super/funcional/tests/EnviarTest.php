<?php

//use Tests\Funcional\BaseTestCase;
// use Tests\Funcional\Sei\Fixtures\{ProtocoloFixture, ProcedimentoFixture, AtividadeFixture, AtributoAndamentoFixture, BlocoFixture, RelProtocoloAssuntoFixture, DocumentoFixture, RelBlocoProtocoloFixture, RelBlocoUnidadeFixture};
//use Facebook\WebDriver\WebDriverBy;

/**
 * EnviarProcessoTest
 * @group group
 */
class EnviarTest extends CenarioBaseTestCase
{
    private $objProtocoloFixture;

    // protected function setUp(): void 
    // {
    //     // parent::setUp();

    //     $parametros = [];

    //     $this->objProtocoloFixture = new ProtocoloFixture();
    //     $this->objProtocoloFixture->carregar($parametros, function($objProtocoloDTO) {
    //         $objProcedimentoFixture = new ProcedimentoFixture();

    //         $objProcedimentoFixture->carregar([
    //             'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
    //         ]);

    //         $objAtividadeFixture = new AtividadeFixture();
    //         $objAtividadeFixture->carregar([
    //             'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
    //         ]);

    //         $objProtocoloAssuntoFixture = new RelProtocoloAssuntoFixture();
    //         $objProtocoloAssuntoFixture->carregar([
    //             'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
    //         ]);
    //     });

    // }

    public function teste_enviar_processo_para_outra_unidade()
    {
        
        $parametros = [];
        $test = new BlocoDeTramiteFixture();
        $objTesteFixture = $test->carregar($parametros);


        // $this->objProtocoloFixture = new ProtocoloFixture();
        // $this->objProtocoloFixture->carregar($parametros, function($objProtocoloDTO) {
        //     $objProcedimentoFixture = new ProcedimentoFixture();

        //     $objProcedimentoDTO = $objProcedimentoFixture->carregar([
        //         'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
        //     ]);

        //     $objAtividadeFixture = new AtividadeFixture();
        //     $objAtividadeDTO = $objAtividadeFixture->carregar([
        //         'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
        //         'Conclusao' => InfraData::getStrDataHoraAtual(),
        //         'IdUsuarioConclusao' => 100000001
        //     ]);

        //     $objProtocoloAssuntoFixture = new RelProtocoloAssuntoFixture();
        //     $objProtocoloAssuntoFixture->carregar([
        //         'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo()
        //     ]);

        //     $objAtributoAndamentoFixture = new AtributoAndamentoFixture();
        //     $objAtributoAndamentoFixture->carregar([
        //         'IdAtividade' => $objAtividadeDTO->getNumIdAtividade()
        //     ]);

        //     $objDocumentoFixture = new DocumentoFixture();
        //     $objDocumentoFixture->carregar([
        //         'IdProtocolo' => $objProtocoloDTO->getDblIdProtocolo(),
        //         'IdProcedimento' => $objProcedimentoDTO->getDblIdProcedimento(),
        //     ]);

        // });

        // $objBlocoFixture = new BlocoFixture();
        // $objBlocoDTO = $objBlocoFixture->carregar([]);

        // $objRelBlocoFixture = new RelBlocoUnidadeFixture();
        // $objRelBlocoFixture->carregar([
        //     'IdBloco' => $objBlocoDTO->getNumIdBloco()
        // ]);
        
    }
}

