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
                  'PaginaLogin' => getenv('HOST_URL').'/sip/login.php',
                  'SipWsdl' => getenv('HOST_URL').'/sip/controlador_ws.php?servico=sip',
                  'ChaveAcesso' => getenv('APP_SEI_CHAVE_ACESSO'), //ATEN??O: gerar uma nova chave para o SEI ap?s a instala??o (ver documento de instala??o)
                  'https' => false),

              'BancoSEI'  => array(
  				'Servidor' => getenv('DATABASE_HOST'),
  				'Porta' => getenv('DATABASE_PORT'),
  				'Banco' => getenv('SEI_DATABASE_NAME'),
  				'Usuario' => getenv('SEI_DATABASE_USER'),
  				'Senha' => getenv('SEI_DATABASE_PASSWORD'),
  				'UsuarioScript' => getenv('SEI_DATABASE_USER_SCRIPT'),
  				'SenhaScript' => getenv('SEI_DATABASE_PASSWORD_SCRIPT'),
  				'Tipo' => getenv('DATABASE_TYPE'), //MySql, SqlServer ou Oracle
                  ),

              /*
        'BancoAuditoriaSEI'  => array(
                  'Servidor' => '[servidor BD]',
                  'Porta' => '',
                  'Banco' => '',
                  'Usuario' => '',
                  'Senha' => '',
                  'Tipo' => ''), //MySql, SqlServer, Oracle ou PostgreSql
        */

                        'CacheSEI' => array('Servidor' => 'memcached',
                                                                'Porta' => '11211'),

        'Federacao' => array(
          'Habilitado' => false
         ),

              'JODConverter' => array('Servidor' => 'http://jod/converter/service'),

              'Solr' => array(
                  'Servidor' => 'http://solr:8983/solr',
                  'CoreProtocolos' => 'sei-protocolos',
                  'CoreBasesConhecimento' => 'sei-bases-conhecimento',
                  'CorePublicacoes' => 'sei-publicacoes'),

              'InfraMail' => array(
                                                'Tipo' => '2', //1 = sendmail (neste caso n?o ? necess?rio configurar os atributos abaixo), 2 = SMTP
                                                'Servidor' => 'smtp',
                                                'Porta' => '1025',
                                                'Codificacao' => '8bit', //8bit, 7bit, binary, base64, quoted-printable
                                                'MaxDestinatarios' => 999, //numero maximo de destinatarios por mensagem
                                                'MaxTamAnexosMb' => 999, //tamanho maximo dos anexos em Mb por mensagem
                                                'Seguranca' => '', //TLS, SSL ou vazio
                                                'Autenticar' => false, //se true ent?o informar Usuario e Senha
                                                'Usuario' => '',
                                                'Senha' => '',
                                                'Protegido' => '' //campo usado em desenvolvimento, se tiver um email preenchido entao todos os emails enviados terao odestinatario ignorado e substitu?do por este valor evitando envio incorreto de email
                                )
          );
        }
}
?>
