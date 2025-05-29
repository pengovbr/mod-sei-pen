<?

require_once DIR_SEI_WEB.'/SEI.php';

class ComponenteDigitalBD extends InfraBD {

  public function __construct(InfraIBanco $objInfraIBanco){
      parent::__construct($objInfraIBanco);
  }

    /**
     * Lista componentes digitais de determinado tr�mite
     *
     * @param TramiteDTO $parObjTramiteDTO
     * @return void
     */
  public function listarComponentesDigitaisPeloTramite($numIdTramite, $dblIdDocumento = null)
    {
    if(is_null($numIdTramite)){
        throw new InfraException('M�dulo do Tramita: Par�metro [parObjTramiteDTO] n�o informado');
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
      $objComponenteDigitalPesquisaDTO->setStrStaProtocolo('R', InfraDTO::$OPER_DIFERENTE);
      $objComponenteDigitalPesquisaDTO->setOrdNumOrdemDocumento(InfraDTO::$TIPO_ORDENACAO_ASC);
      $objComponenteDigitalPesquisaDTO->setOrdNumOrdem(InfraDTO::$TIPO_ORDENACAO_ASC);

    if(!is_null($dblIdDocumento)){
        $objComponenteDigitalPesquisaDTO->setDblIdDocumento($dblIdDocumento);
    }

      return $this->listar($objComponenteDigitalPesquisaDTO);
  }

    /**
     * Verifica a exist�ncia de algum documento contendo outro referenciado no pr�prio processo
     *
     * @param TramiteDTO $parObjTramiteDTO
     * @return void
     */
  public function possuiComponentesComDocumentoReferenciado(TramiteDTO $parObjTramiteDTO)
    {
    if(is_null($parObjTramiteDTO)){
        throw new InfraException('M�dulo do Tramita: Par�metro [parObjTramiteDTO] n�o informado');
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
