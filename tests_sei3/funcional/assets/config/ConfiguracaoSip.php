<?

class ConfiguracaoSip extends InfraConfiguracao  {

	private static $instance = null;

	public static function getInstance(){
		if (ConfiguracaoSip::$instance == null) {
			ConfiguracaoSip::$instance = new ConfiguracaoSip();
		}
		return ConfiguracaoSip::$instance;
	}

	public function getArrConfiguracoes(){
		return array(
			'Sip' => array(
				'URL' => getenv('HOST_URL').'/sip',
				'Producao' => false,
				'NumLoginSemCaptcha' => 3,
				'TempoLimiteValidacaoLogin' => 60,
				'Modulos' => array(
					//'ABCExemploIntegracao' => 'abc/exemplo',
				),
			),

			'PaginaSip' => array(
				'NomeSistema' => 'SIP',
				'NomeSistemaComplemento' => '',
			),

			'SessaoSip' => array(
				'SiglaOrgaoSistema' => 'ABC',
				'SiglaSistema' => 'SIP',
				'PaginaLogin' => getenv('HOST_URL') . '/sip/login.php',
				'SipWsdl' => getenv('HOST_URL') . '/sip/controlador_ws.php?servico=wsdl',
				'https' => false
			),

			'BancoSip'  => array(
				'Servidor' => getenv('DATABASE_HOST'),
				'Porta' => getenv('DATABASE_PORT'),
				'Banco' => getenv('SIP_DATABASE_NAME'),
				'Usuario' => getenv('SIP_DATABASE_USER'),
				'Senha' => getenv('SIP_DATABASE_PASSWORD'),
				'UsuarioScript' => getenv('SIP_DATABASE_USER_SCRIPT'),
				'SenhaScript' => getenv('SIP_DATABASE_PASSWORD_SCRIPT'),
				'Tipo' => getenv('DATABASE_TYPE'), //MySql, SqlServer ou Oracle
				'PesquisaCaseInsensitive' => false,
			),

			'BancoAuditoriaSip'  => array(
				'Servidor' => 'mysql',
				'Porta' => '3306',
				'Banco' => 'sip',
				'Usuario' => 'sip_user',
				'Senha' => 'sip_user',
				'Tipo' => 'MySql', //MySql, SqlServer ou Oracle
			),

			'CacheSip' => array(
				'Servidor' => 'memcached',
				'Porta' => '11211',
				'Timeout' => 2,
				'Tempo' => 3600,
			),

			'HostWebService' => array(
				'Replicacao' => array('*'),
				'Pesquisa' => array('*'),
				'Autenticacao' => array('*')
			),

				'InfraMail' => array(
					'Tipo' => '2',
					'Servidor' => 'smtp',
					'Porta' => '1025',
					'Codificacao' => '8bit',
					'MaxDestinatarios' => 999,
					'MaxTamAnexosMb' => 999,
					'Seguranca' => '', //TLS, SSL ou vazio
					'Autenticar' => false,
					'Usuario' => '',
					'Senha' => '',
					'Protegido' => 'desenv@instituicao.gov.br'
					)
				);
			}
		}