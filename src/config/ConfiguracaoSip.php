<?

class ConfiguracaoSip extends InfraConfiguracao
{
 	private static $instance = null;

    public static function getInstance()
    {
        if (ConfiguracaoSip::$instance == null) {
            ConfiguracaoSip::$instance = new ConfiguracaoSip();
        }

        return ConfiguracaoSip::$instance;
    }

    public function getArrConfiguracoes()
    {
 	    return array(
 	        'Sip' => array(
 	            'URL' => getenv('SEI_HOST_URL').'/sip',
                'Producao' => false
            ),

 	        'PaginaSip' => array(
                'NomeSistema' => 'SIP'
            ),

 	        'SessaoSip' => array(
 	           'SiglaOrgaoSistema' => 'ABC',
 	           'SiglaSistema' => 'SIP',
 	           'PaginaLogin' => getenv('SEI_HOST_URL').'/sip/login.php',
 	           'SipWsdl' => 'http://localhost/sip/controlador_ws.php?servico=wsdl',
               'https' => false
            ),

 	        'BancoSip'  => array(
 	            'Servidor' => 'mysql',
 	            'Porta' => '3306',
 	            'Banco' => 'sip',
 	            'Usuario' => 'sip_user',
 	            'Senha' => 'sip_user',
 	            'UsuarioScript' => 'sip_user',
 	            'SenhaScript' => 'sip_user',
                'Tipo' => 'MySql'
            ),

	        // 'BancoSip'  => array(
	        //     'Servidor' => 'oracle',
	        //     'Porta' => '1521',
	        //     'Banco' => 'sip',
	        //     'Usuario' => 'sip',
	        //     'Senha' => 'sip_user',
	        //     'UsuarioScript' => 'sip',
	        //     'SenhaScript' => 'sip_user',
            //     'Tipo' => 'Oracle'
            // ),

	        // 'BancoSip'  => array(
	        //     'Servidor' => 'sqlserver',
	        //     'Porta' => '1433',
	        //     'Banco' => 'sip',
	        //     'Usuario' => 'sip_user',
	        //     'Senha' => 'sip_user',
	        //     'UsuarioScript' => 'sip_user',
	        //     'SenhaScript' => 'sip_user',
            //     'Tipo' => 'SqlServer'
            // ),


        'CacheSip' => array(
            'Servidor' => 'memcached',
            'Porta' => '11211'
        ),

        'HostWebService' => array(
            'Replicacao' => array('*'),
            'Pesquisa' => array('*'),
            'Autenticacao' => array('*')),

            'InfraMail' => array(
                    'Tipo' => '2',
                    'Servidor' => 'smtp',
                    'Porta' => '1025',
                    'Codificacao' => '8bit',
                    'MaxDestinatarios' => 999,
                    'MaxTamAnexosMb' => 999,
                    'Seguranca' => '',
                    'Autenticar' => false,
                    'Usuario' => '',
                    'Senha' => '',
                    'Protegido' => 'desenv@instituicao.gov.br'
            )
        );
    }
}
