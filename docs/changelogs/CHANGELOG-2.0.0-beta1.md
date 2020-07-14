# NOTAS DE VERSÃO MOD-SEI-PEN (versão 2.0.0)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o Barramento de Serviços do PEN.

Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:

* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior


## Lista de Melhorias e Correções de Problemas

O foco desta versão foi a implementação de diversas simplificações nos procedimentos de instalação e configuração do módulo, deixando alguns passos opcionais e aplicando várias configurações de forma automática, possibilitando ao administrador modificar caso necessário.


#### Distribuição dos arquivos do módulo posicionados nas pastas corretas do SEI e SIP(gitlab #129)


#### Simplificação do Processamento de Tarefas enviadas pelo Barramento (gitlab #133)


#### Remoção de janela "Popup" durante trâmite externo do processo (gitlab #134)


#### Refatoração do procedimento de processamento de tarefas do Barramento de Serviços do PEN


#### Atribuição automática das permissões ao Perfil Básico (gitlab #121)


#### Mapeamento automático dos Tipos de Documentos às Espécies Documentais correspondentes (gitlab #126)


#### Mapeamento automático das Espécies Documentais aos Tipos de Documentos correspondentes (gitlab #126)


#### Checklist de verificação da correta instalação do módulo (gitlab #56) 


#### Remoção de scripts .bash para restauração de serviços de processamento de tarefas (gitlab #122)


#### Separação de configurações técnicas de integração em arquivo próprio (gitlab #124)


#### Refatoração da funcionalidade de mapeamento de hipóteses legais (gitlab #125)


#### Atualização automática das espécies documentais do Barramento de Serviços PEN (gitlab #127)


#### Configuração de mapeamento de Espécie Documental Padrão para envio de processo (gitlab #128)


#### Configuração de mapeamento de Tipo de Documento Padrão para recebimento de processo (gitlab #128)


#### Erro ao recuperar cache de wsdl do serviço do Barramento PEN


#### Tratamento de erro para não recusar em caso de falha de comunicação com o Barramento (gitlab #71)


#### Criar novo parâmetro para indicar localização do Gearman (gitlab #68)


#### Tratamento para evitar excesso de logs de erro no sistema


#### Criação de novo pacote de distribuição para o projeto


#### Utilização de usuário de script para atualização da base de dados
