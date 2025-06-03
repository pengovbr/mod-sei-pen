<?

require_once DIR_SEI_WEB.'/SEI.php';

class ComponenteDigitalBD extends InfraBD {

  public function __construct(InfraIBanco $objInfraIBanco){
      parent::__construct($objInfraIBanco);
  }

    /**
     * Lista componentes digitais de determinado trâmite
     *
     * @param TramiteDTO $parObjTramiteDTO
     * @return void
     */
  public function listarComponentesDigitaisPeloTramite($numIdTramite, $dblIdDocumento = null)
    {
    if(is_null($numIdTramite)){
        throw new InfraException('Módulo do Tramita: Parâmetro [parObjTramiteDTO] não informado');
    }
      $objRelProtocoloProtocoloDTO = new RelProtocoloProtocoloDTO();
      $objRelProtocoloProtocoloDTO->setStrStaAssociacao(RelProtocoloProtocoloRN::$TA_DOCUMENTO_MOVIDO, InfraDTO::$OPER_DIFERENTE);
      $objRelProtocoloProtocoloDTO->setDblIdProtocolo2($dblIdDocumento);
      $objRelProtocoloProtocoloDTO->retNumSequencia();

      $objRelProtocoloProtocoloRN = new RelProtocoloProtocoloRN();
      $arrObjRelProtocoloProtocoloDTO = $objRelProtocoloProtocoloRN->listarRN0187($objRelProtocoloProtocoloDTO);

      $arrOrdem = [];
      foreach ($arrObjRelProtocoloProtocoloDTO as $dto){
        $arrOrdem[] = $dto->getNumSequencia() + 1;
      }

      $objComponenteDigitalPesquisaDTO = new ComponenteDigitalDTO();
      $objComponenteDigitalPesquisaDTO->retStrNumeroRegistro();
      $objComponenteDigitalPesquisaDTO->retDblIdProcedimento();
      $objComponenteDigitalPesquisaDTO->retDblIdDocumento();
      $objComponenteDigitalPesquisaDTO->retNumIdTramite();
      $objComponenteDigitalPesquisaDTO->retNumCodigoEspecie();
      $objComponenteDigitalPesquisaDTO->retStrNomeEspecieProdutor();
      $objComponenteDigitalPesquisaDTO->retStrHashConteudo();
      $objComponenteDigitalPesquisaDTO->retDblIdProcedimentoAnexado();
      $objComponenteDigitalPesquisaDTO->retStrProtocoloProcedimentoAnexado();
      $objComponenteDigitalPesquisaDTO->retNumOrdemDocumento();
      $objComponenteDigitalPesquisaDTO->retNumOrdemDocumentoReferenciado();
      $objComponenteDigitalPesquisaDTO->retNumOrdemDocumentoAnexado();
      $objComponenteDigitalPesquisaDTO->retNumOrdem();
      $objComponenteDigitalPesquisaDTO->setNumIdTramite($numIdTramite);
      if (!empty($arrOrdem)){
        $objComponenteDigitalPesquisaDTO->setNumOrdemDocumento($arrOrdem, InfraDTO::$OPER_IN);
      }
      $objComponenteDigitalPesquisaDTO->setOrdNumOrdemDocumento(InfraDTO::$TIPO_ORDENACAO_ASC);
      $objComponenteDigitalPesquisaDTO->setOrdNumOrdem(InfraDTO::$TIPO_ORDENACAO_ASC);

    if(!is_null($dblIdDocumento)){
        $objComponenteDigitalPesquisaDTO->setDblIdDocumento($dblIdDocumento);
    }

      return $this->listar($objComponenteDigitalPesquisaDTO);
  }

    /**
     * Verifica a existência de algum documento contendo outro referenciado no próprio processo
     *
     * @param TramiteDTO $parObjTramiteDTO
     * @return void
     */
  public function possuiComponentesComDocumentoReferenciado(TramiteDTO $parObjTramiteDTO)
    {
    if(is_null($parObjTramiteDTO)){
        throw new InfraException('Módulo do Tramita: Parâmetro [parObjTramiteDTO] não informado');
    }

      $objComponenteDigitalPesquisaDTO = new ComponenteDigitalDTO();
      $objComponenteDigitalPesquisaDTO->setOrdNumOrdemDocumento(InfraDTO::$TIPO_ORDENACAO_ASC);
      $objComponenteDigitalPesquisaDTO->setNumIdTramite($parObjTramiteDTO->getNumIdTramite());
      $objComponenteDigitalPesquisaDTO->setNumOrdemDocumentoReferenciado(null, InfraDTO::$OPER_DIFERENTE);
      $objComponenteDigitalPesquisaDTO->retNumIdTramite();


      return $this->contar($objComponenteDigitalPesquisaDTO) > 0;
  }
}
?>
