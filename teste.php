<?

require_once dirname(__FILE__) . '/../../SEI.php';


InfraDebug::getInstance()->setBolLigado(false);
InfraDebug::getInstance()->setBolDebugInfra(true);
InfraDebug::getInstance()->limpar();

echo "Script de teste de conexão ao barramento \n";

$wsdl = 'https://homolog.pen.api.trafficmanager.net/interoperabilidade/soap/v2/?wsdl';
$urlPendencia = 'https://homolog.pen.pendencias.trafficmanager.net/';
$caminhoCertificado = '/opt/sei/config/BNDES-HOMOLOGall.pem';

$options = array(
  'soap_version' => SOAP_1_1
  , 'local_cert' => $caminhoCertificado
  , 'passphrase' => '1234'
  , 'resolve_wsdl_remote_includes' => true
  , 'cache_wsdl'=> WSDL_CACHE_NONE
  , 'trace' => true
  , 'encoding' => 'UTF-8'
  , 'attachment_type' => BeSimple\SoapCommon\Helper::ATTACHMENTS_TYPE_MTOM
  , 'ssl' => array(
      'allow_self_signed' => true,
    )
  );


//Testes de chamada ao serviço SOAP
$webservice = new BeSimple\SoapClient\SoapClient($wsdl, $options);
$parametros = new stdClass();
$parametros->filtroDePendencias = new stdClass();
$parametros->filtroDePendencias->todasAsPendencias = false;
$resultado = $webservice->listarPendencias($parametros);

if(isset($resultado) && isset($resultado->listaDePendencias->IDT)){
    print_r($resultado->listaDePendencias->IDT);
    foreach ($resultado->listaDePendencias->IDT as $pendencia) {
        echo sprintf(" - Pendência com IDT: %d: status %d \n", $pendencia->_, $pendencia->status);
    }

}


// $urlPendencia = 'https://homolog.pen.pendencias.trafficmanager.net/';
// $curl = curl_init($urlPendencia);
// curl_setopt($curl, CURLOPT_URL, $urlPendencia);
// curl_setopt($curl, CURLOPT_HEADER, 0);
// curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
// curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
// curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
// curl_setopt($curl, CURLOPT_SSLCERT, $caminhoCertificado);
// curl_setopt($curl, CURLOPT_SSLCERTPASSWD, '1231');
// $resultado = curl_exec($curl);
// curl_close($curl);

echo "==============================================================\n";
print_r($resultado);
echo "==============================================================\n";

exit(0);
