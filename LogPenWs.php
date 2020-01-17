<?php
/**
 * Cria uma camada no SoapClient para efetuar o log de algumas requiушes soap
 *
 * Adicionar no arquivo ConfiguracaoSEI.php [SEI][LogPenWs] um array com a lista
 * de mжtodos, do api-pen.wsdl, que serсo logados
 *
 * @autor Join Tecnologia
 */
class LogPenWs {

    /**
     * InstРncia do webservice
     */
    protected $objSoapClient;

    /**
     * Lista de mжtodos que serсo logados no SeiLog
     */
    protected $arrListaMetodos;

    /**
     * Construtor
     */
    public function __construct($config = array(), $wsdl, $options) {

        $this->arrListaMetodos = is_array($config) ? $config : array();
        $this->objSoapClient = new \BeSimple\SoapClient\SoapClient($wsdl, $options);
    }


    /**
     * Mжtodo mрgico
     */
    public function __call($method, $arguments) {

        $mixResultado = call_user_func_array(array($this->objSoapClient, $method), $arguments);

        if(in_array($method, $this->arrListaMetodos)) {

            $strMensagem  = '[ SOAP Request ]'.PHP_EOL;
            $strMensagem .= 'Method: '.$method.PHP_EOL;
            $strMensagem .= 'Requiest: '.$this->objSoapClient->__getLastRequest().PHP_EOL;
            $strMensagem .= 'Response: '.$this->objSoapClient->__getLastResponse().PHP_EOL;

            //file_put_contents('/tmp/pen.log', $strMensagem, FILE_APPEND);

            LogSEI::getInstance()->gravar($strMensagem);
        }
        return $mixResultado;
    }
}
