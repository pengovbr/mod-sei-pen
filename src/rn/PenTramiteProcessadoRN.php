<?php
/**
 *
 *
 */
class PenTramiteProcessadoRN extends InfraRN
{

    const STR_TIPO_PROCESSO = 'RP';
    const STR_TIPO_RECIBO = 'RR';

    const PARAM_NUMERO_TENTATIVAS = 'PEN_NUMERO_TENTATIVAS_TRAMITE_RECEBIMENTO';

    protected $objInfraBanco;
    private $strTipo;

  public function __construct($strTipo = self::STR_TIPO_PROCESSO)
    {
      parent::__construct();
      $this->strTipo = $strTipo;
  }

    /**
     *
     * @return BancoSEI
     */
  protected function inicializarObjInfraIBanco()
    {

    if(empty($this->objInfraBanco)) {

        $this->objInfraBanco = BancoSEI::getInstance();
    }

      return $this->objInfraBanco;
  }

    /**
     *
     * @return bool
     */
  protected function isProcedimentoRecebidoControlado($dblIdTramite = 0)
    {

      //Verifica se o trâmite não foi cancelado ou recusado
    if($this->isTramiteRecusadoCancelado($dblIdTramite)) {
        return true;
    }

      $objFilDTO = new PenTramiteProcessadoDTO();
      $objFilDTO->setDblIdTramite($dblIdTramite);
      $objFilDTO->setStrTipo($this->strTipo);
      $objFilDTO->setNumMaxRegistrosRetorno(1);
      $objFilDTO->retTodos();

      $objBD = new GenericoBD($this->inicializarObjInfraIBanco());
      $objDTO = $objBD->consultar($objFilDTO);

    if(empty($objDTO)) {
        $objFilDTO->setDthUltimo(InfraData::getStrDataHoraAtual());
        $objFilDTO->setNumTentativas(0);
        $objFilDTO->setStrRecebido('N');
        $objFilDTO->setStrTipo($this->strTipo);
        $objDTO = $objBD->cadastrar($objFilDTO);
        return false;
    }

    if($objDTO->getStrRecebido() == 'S') {
        return true;
    }
    else {
        $objPenParametroRN = new PenParametroRN();
        $numTentativas = $objPenParametroRN->getParametro(self::PARAM_NUMERO_TENTATIVAS);

        // Verifica o número de tentativas já realizadas é igual ao configurado
        // no parâmetro
      if($objDTO->getNumTentativas() >= $numTentativas) {
          // Somente faz a recusa se estiver recebendo o procedimento, pois
          // ao receber o recibo não pode mais recursar
        if($objDTO->getStrTipo() == self::STR_TIPO_PROCESSO) {
          // Recusa o tramite
          $objProcessoEletronicoRN = new ProcessoEletronicoRN();
          $objProcessoEletronicoRN->recusarTramite($dblIdTramite, 'Tramite recusado por falha do destinatário', ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_OUTROU);
        }
          return true;// Mente que já foi recebido para não executar novamente
      }
      else {
          // Incrementa o contador pois após sair desse método com false
          // ira executar o recebimento novamente
          $objDTO->setDthUltimo(InfraData::getStrDataHoraAtual());
          $objDTO->setNumTentativas($objDTO->getNumTentativas() + 1);
          $objDTO->setStrTipo($this->strTipo);
          $objBD->alterar($objDTO);
          return false;
      }
    }
  }

  public function setRecebido($dblIdTramite = 0)
    {

      $objDTO = new PenTramiteProcessadoDTO();
      $objDTO->setDblIdTramite($dblIdTramite);
      $objDTO->setStrTipo($this->strTipo);
      $objDTO->setNumMaxRegistrosRetorno(1);
      $objDTO->retTodos();
      $objBD = new GenericoBD($this->inicializarObjInfraIBanco());
      $objDTO = $objBD->consultar($objDTO);
    if(empty($objDTO)) {
        throw new InfraException(sprintf('ID do tramite %s não foi localizado', $dblIdTramite));
    }
      $objDTO->setDthUltimo(InfraData::getStrDataHoraAtual());
      $objDTO->setStrRecebido('S');
      $objBD->alterar($objDTO);
  }

    /**
     * Método que verifica se o trâmite em questão foi cancelado ou recusado
     *
     * @param  integer $parNumIdTramite
     * @return boolean
     */
  public function isTramiteRecusadoCancelado($parNumIdTramite)
    {

      //Instancia a classe processo eletrônico
      $processoEletronicoRN = new ProcessoEletronicoRN();

      //Busca os dados do trâmite
      $arrObjTramite = $processoEletronicoRN->consultarTramites($parNumIdTramite);
      $objTramite = $arrObjTramite[0];

      //Verifica se o trâmite em questão. foi recusado o cancelado
    if($objTramite->situacaoAtual == ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO || $objTramite->situacaoAtual == ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO) {
        return true;
    }else{
        return false;
    }

  }
}
