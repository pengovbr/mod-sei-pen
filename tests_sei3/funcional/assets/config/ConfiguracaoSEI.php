<?

class ConfiguracaoSEI extends InfraConfiguracao  {

	private static $instance = null;

	public static function getInstance(){
		if (ConfiguracaoSEI::$instance == null) {
			ConfiguracaoSEI::$instance = new ConfiguracaoSEI();
		}
		return ConfiguracaoSEI::$instance;
	}

	public function getArrConfiguracoes(){
		return array(
			'SEI' => array(
				'URL' => getenv('HOST_URL').'/sei',
				'Producao' => false,
				'RepositorioArquivos' => '/var/sei/arquivos',

				'DigitosDocumento' => 7,
				'NumLoginUsuarioExternoSemCaptcha' => 3,
				'TamSenhaUsuarioExterno' => 8,
				'DebugWebServices' => 2,
				'MaxMemoriaPdfGb' => 4,
                'Modulos' => array(
                    "PENIntegracao" => "pen",
                )
			),

			'SessaoSEI' => array(
				'SiglaOrgaoSistema' => 'ABC',
				'SiglaSistema' => 'SEI',
				'PaginaLogin' => getenv('HOST_URL') . '/sip/login.php',
				'SipWsdl' => getenv('HOST_URL') . '/sip/controlador_ws.php?servico=wsdl',
				'https' => false
			),

			'PaginaSEI' => array(
				'NomeSistema' => 'SEI',
				'NomeSistemaComplemento' => SEI_VERSAO,
				'LogoMenu' => '',
				'OrgaoTopoJanela' => 'S',
			),

			'BancoSEI'  => array(
				'Servidor' => getenv('DATABASE_HOST'),
				'Porta' => getenv('DATABASE_PORT'),
				'Banco' => getenv('SEI_DATABASE_NAME'),
				'Usuario' => getenv('SEI_DATABASE_USER'),
				'Senha' => getenv('SEI_DATABASE_PASSWORD'),
				'UsuarioScript' => getenv('SEI_DATABASE_USER_SCRIPT'),
				'SenhaScript' => getenv('SEI_DATABASE_PASSWORD_SCRIPT'),
				'Tipo' => getenv('DATABASE_TYPE'), //MySql, SqlServer ou Oracle
				'PesquisaCaseInsensitive' => false,
			),

			'BancoAuditoriaSEI'  => array(
				'Servidor' => 'mysql',
				'Porta' => '3306',
				'Banco' => 'sei',
				'Usuario' => 'sei_user',
				'Senha' => 'sei_user',
				'Tipo' => 'MySql', //MySql, SqlServer ou Oracle
			),

			'CacheSEI' => array(
				'Servidor' => 'memcached',
				'Porta' => '11211',
				'Timeout' => 2,
				'Tempo' => 3600,
			),

			'Solr' => array(
				'Servidor' => 'http://solr:8983/solr',
				'CoreProtocolos' => 'sei-protocolos',
				'CoreBasesConhecimento' => 'sei-bases-conhecimento',
				'CorePublicacoes' => 'sei-publicacoes',
				'TempoCommitProtocolos' => 300,
				'TempoCommitBasesConhecimento' => 60,
				'TempoCommitPublicacoes' => 60,
			),

			'JODConverter' => array(
				'Servidor' => 'http://jod/converter/service'
			),

			'HostWebService' => array(
				'Sip' => array('*'),
				'Publicacao' => array('*'),
				'Ouvidoria' => array('*'),
			),

			'InfraMail' => array(
				'Tipo' => '2',
				'Servidor' => 'smtp',
				'Porta' => '1025',
				'Codificacao' => '8bit',
				'MaxDestinatarios' => 999,
				'MaxTamAnexosMb' => 999,
				'Autenticar' => false,
				'Usuario' => '',
				'Senha' => '',
				'Seguranca' => '', //TLS, SSL ou vazio
				'Protegido' => 'desenv@instituicao.gov.br',
			),
		);
	}
}