<?

/**
 * Arquivo de configuração do Módulo de Integração do SEI com o Processo Eletrônico Nacional
 *
 * Seu desenvolvimento seguiu os mesmos padrões de configuração implementado pelo SEI e SIP e este
 * arquivo precisa ser adicionado à pasta de configurações do SEI para seu correto carregamento pelo módulo.
 */

class ConfiguracaoModPEN extends InfraConfiguracao  {

	private static $instance = null;

    /**
     * Obtém instância única (singleton) dos dados de configuração do módulo de integração com Barramento PEN
     *
     *
     * @return ConfiguracaoModPEN
     */
    public static function getInstance()
    {
		if (ConfiguracaoModPEN::$instance == null) {
			ConfiguracaoModPEN::$instance = new ConfiguracaoModPEN();
		}
		return ConfiguracaoModPEN::$instance;
	}

    /**
     * Definição dos parâmetros de configuração do módulo
     *
     * @return array
     */
    public function getArrConfiguracoes()
    {
        return array(
            "PEN" => array(
                // Endereço do Web Service principal de integração com o Barramento de Serviços do PEN
                // Os endereços disponíveis são os seguintes (verifique se houve atualizações durante o procedimento de instalação):
                //    - Homologação: https://homolog.api.processoeletronico.gov.br/interoperabilidade/soap/v3/
                //    - Produção: https://api.conectagov.processoeletronico.gov.br/interoperabilidade/soap/v3/
                "WebService" => "",

                // Localização completa do certificado digital utilizado para autenticação nos serviços do Barramento de Serviços do PEN.
                // Os certificados digitais são disponibilizados pela equipe do Processo Eletrônico Nacional mediante aprovação do credenciamento
                // da instituição. Verifique a seção [pré-requisitos](#pré-requisitos) para maiores informações.
                //
                // Necessário que o arquivo de certificado esteja localizado dentro da pasta de configurações do módulo:
                // Exemplo: <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI>/sei/config/mod-pen/certificado.pem
                "LocalizacaoCertificado" => "/opt/sei/config/mod-pen/certificado.pem",

                // Senha do certificado digital necessário para a aplicação descriptografar e acessar a sua chave privada
                "SenhaCertificado" => "",

                // Opcional, mas altamente desejável
                // Localização do servidor Gearman de gerenciamento de fila de processamento de tarefas do Barramento PEN
                // As mensagem recebidas são organizadas em filas de tarefas e distribuídas entre os nós da aplicação para
                // processamento paralelo. Caso este parâmetro não seja configurado ou o servidor este indisponível, o processamento será
                // feito diretamente pelo sistema na periodicidade definida pelo agendamento da tarefa PENAgendamento::receberProcessos
                "Gearman" => array(
                    "Servidor" => "",
                    "Porta" => "",     // Valor padrão: 4730
                ),


                // Opcional
                // Quantidade de tentativas de requisção dos serviços do Barramento PEN antes que um erro possa ser lançado pela aplicação
                // Necessário para aumentar a resiliência da integração em contextos de instabilidade de rede.
                // Valor padrão: 3
                "NumeroTentativasErro" => 3,


                // Opcional
                // Endereço do Web Service de monitoramente de pendências de trâmite no Barramento de Serviços do PEN
                // Configuração necessária somente quando o módulo é configurado para utilização conjunta com o Supervisor
                // para monitorar ativamente todos os eventos de envio e recebimentos de processos enviados pelo Barramento de Serviços do PEN.
                // Para maiores informações sobre como utilzar este recurso. Veja a seção [Conexão persistente com uso do Supervisor](#Conexão-persistente-com-uso-do-Supervisor) para maiores informações. \
                // Os endereços disponíveis são os seguintes (verifique se houve atualizações durante o procedimento de instalação):
                //     * Homologação: https://homolog.pendencias.processoeletronico.gov.br/
                //     * Produção: https://pendencias.conectagov.processoeletronico.gov.br/
                "WebServicePendencias" => "",
            )
        );
    }
}
