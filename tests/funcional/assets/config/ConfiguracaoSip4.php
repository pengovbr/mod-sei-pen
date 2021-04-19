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
                  'Producao' => true),

              'PaginaSip' => array('NomeSistema' => 'SIP'),

              'SessaoSip' => array(
                  'SiglaOrgaoSistema' => 'ABC',
                  'SiglaSistema' => 'SIP',
                  'PaginaLogin' => getenv('SEI_HOST_URL') . '/sip/login.php',
                  'SipWsdl' => getenv('HOST_URL').'/sip/controlador_ws.php?servico=sip',
                  'ChaveAcesso' => getenv('APP_SIP_CHAVE_ACESSO'),
                  'https' => false),

              'BancoSip'  => array(
                  'Servidor' => getenv('DATABASE_HOST'),
                  'Porta' => getenv('DATABASE_PORT'),
                  'Banco' => getenv('SIP_DATABASE_NAME'),
                  'Usuario' => getenv('SIP_DATABASE_USER'),
                  'Senha' => getenv('SIP_DATABASE_PASSWORD'),
   				  'UsuarioScript' => getenv('SIP_DATABASE_USER_SCRIPT'),
  				  'SenhaScript' => getenv('SIP_DATABASE_PASSWORD_SCRIPT'),
                  'Tipo' => getenv('DATABASE_TYPE')), //MySql, SqlServer, Oracle ou PostgreSql

        /*
              'BancoAuditoriaSip'  => array(
                  'Servidor' => '[Servidor BD]',
                  'Porta' => '',
                  'Banco' => '',
                  'Usuario' => '',
                  'Senha' => '',
                  'Tipo' => ''), //MySql, SqlServer, Oracle ou PostgreSql
        */

                                'CacheSip' => array('Servidor' => 'memcached',
                                                                'Porta' => '11211'),

                                'InfraMail' => array(
                                                'Tipo' => '2', //1 = sendmail (neste caso n?o ? necess?rio configurar os atributos abaixo), 2 = SMTP
                                                'Servidor' => 'smtp',
                                                'Porta' => '25',
                                                'Codificacao' => '8bit', //8bit, 7bit, binary, base64, quoted-printable
                                                'MaxDestinatarios' => 999, //numero maximo de destinatarios por mensagem
                                                'MaxTamAnexosMb' => 999, //tamanho maximo dos anexos em Mb por mensagem
                                                'Seguranca' => '', //TLS, SSL ou vazio
                                                'Autenticar' => false, //se true ent?o informar Usuario e Senha
                                                'Usuario' => '',
                                                'Senha' => '',
                                                'Protegido' => '' //campo usado em desenvolvimento, se tiver um email preenchido entao todos os emails enviados terao odestinatario ignorado e substitu?do por este valor (evita envio incorreto de email)
                                )
          );
        }
}
?>
