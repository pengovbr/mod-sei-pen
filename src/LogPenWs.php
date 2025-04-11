<?php
/**
 * Cria uma camada no SoapClient para efetuar o log de algumas requições soap
 *
 * Adicionar no arquivo ConfiguracaoSEI.php [SEI][LogPenWs] um array com a lista
 * de métodos, do api-pen.wsdl, que serão logados
 */
class LogPenWs
{

    /**
     * Instância do webservice
     */
    protected $objSoapClient;

    /**
     * Lista de métodos que serão logados no SeiLog
     */
    protected $arrListaMetodos;

    /**
     * Construtor
     */
  // phpcs:ignore PEAR.Functions.ValidDefaultValue.NotAtEnd
  public function __construct($wsdl, $options, $config = [])
    {
      $this->arrListaMetodos = is_array($config) ? $config : [];
      $this->objSoapClient = new \BeSimple\SoapClient\SoapClient($wsdl, $options);
  }


    /**
     * Método mágico
     */
  public function __call($method, $arguments)
    {
      $mixResultado = call_user_func_array([$this->objSoapClient, $method], $arguments);

    if(in_array($method, $this->arrListaMetodos)) {

        $strMensagem  = '[ SOAP Request ]'.PHP_EOL;
        $strMensagem .= 'Method: '.$method.PHP_EOL;
        $strMensagem .= 'Requiest: '.$this->objSoapClient->__getLastRequest().PHP_EOL;
        $strMensagem .= 'Response: '.$this->objSoapClient->__getLastResponse().PHP_EOL;

        LogSEI::getInstance()->gravar($strMensagem);
    }
      return $mixResultado;
  }
}
