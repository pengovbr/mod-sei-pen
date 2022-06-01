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
				'URL' => getenv('SEI_HOST_URL').'/sei',
				'Producao' => false,
				'RepositorioArquivos' => '/var/sei/arquivos',
				'Modulos' => array(
					"PENIntegracao" => "pen",
					)
			),

			'PaginaSEI' => array(
				'NomeSistema' => 'SEI',
				'NomeSistemaComplemento' => SEI_VERSAO,
				'LogoMenu' => ''),

				'SessaoSEI' => array(
					'SiglaOrgaoSistema' => 'ABC',
					'SiglaSistema' => 'SEI',
					'PaginaLogin' => getenv('SEI_HOST_URL').'/sip/login.php',
					'SipWsdl' => 'http://localhost/sip/controlador_ws.php?servico=wsdl',
					'https' => false
				),

				'BancoSEI'  => array(
						'Servidor' => 'mysql',
						'Porta' => '3306',
						'Banco' => 'sei',
						'Usuario' => 'sei_user',
						'Senha' => 'sei_user',
						'UsuarioScript' => 'sei_user',
 						'SenhaScript' => 'sei_user',
						'Tipo' => 'MySql'),

					// 'BancoSEI'  => array(
					// 	'Servidor' => 'oracle',
					// 	'Porta' => '1521',
					// 	'Banco' => 'sei',
					// 	'Usuario' => 'sei',
					// 	'Senha' => 'sei_user',
					// 	'UsuarioScript' => 'sei',
					// 	'SenhaScript' => 'sei_user',
					// 	'Tipo' => 'Oracle'), //MySql, SqlServer ou Oracle

					//   'BancoSEI'  => array(
					//           'Servidor' => 'sqlserver',
					//           'Porta' => '1433',
					//           'Banco' => 'sei',
					//           'Usuario' => 'sei_user',
					//           'Senha' => 'sei_user',
					//           'UsuarioScript' => 'sei_user',
					//           'SenhaScript' => 'sei_user',
					//           'Tipo' => 'SqlServer'), //MySql, SqlServer ou Oracle




							'CacheSEI' => array('Servidor' => 'memcached',
							'Porta' => '11211'),

							'JODConverter' => array('Servidor' => 'http://jod:8080/converter/service'),

							'Edoc' => array('Servidor' => 'http://[Servidor .NET]'),

							'Solr' => array(
								'Servidor' => 'http://solr:8983/solr',
								'CoreProtocolos' => 'sei-protocolos',
								'CoreBasesConhecimento' => 'sei-bases-conhecimento',
								'CorePublicacoes' => 'sei-publicacoes'),

								'HostWebService' => array(
									'Edoc' => array('[Servidor .NET]'),
									'Sip' => array('*'), //Referências (IP e nome na rede) de todas as máquinas que executam o SIP.
									'Publicacao' => array('*'), //Referências (IP e nome na rede) das máquinas de veículos de publicação externos cadastrados no SEI.
									'Ouvidoria' => array('*'), //Referências (IP e nome na rede) da máquina que hospeda o formulário de Ouvidoria personalizado. Se utilizar o formulário padrão do SEI, então configurar com as máquinas dos nós de aplicação do SEI.
								),

								'InfraMail' => array(
									'Tipo' => '2', //1 = sendmail (neste caso não é necessário configurar os atributos abaixo), 2 = SMTP
									'Servidor' => 'smtp',
									'Porta' => '1025',
									'Codificacao' => '8bit', //8bit, 7bit, binary, base64, quoted-printable
									'MaxDestinatarios' => 999, //numero maximo de destinatarios por mensagem
									'MaxTamAnexosMb' => 999, //tamanho maximo dos anexos em Mb por mensagem
									'Seguranca' => '', //TLS, SSL ou vazio
									'Autenticar' => false, //se true então informar Usuario e Senha
									'Usuario' => '',
									'Senha' => '',
									'Protegido' => 'desenv@instituicao.gov.br' //campo usado em desenvolvimento, se tiver um email preenchido entao todos os emails enviados terao o destinatario ignorado e substituído por este valor (evita envio incorreto de email)
									)
								);
							}
						}


