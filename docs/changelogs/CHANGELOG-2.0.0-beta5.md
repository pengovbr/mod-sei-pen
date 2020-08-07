# NOTAS DE VERSÃO MOD-SEI-PEN (versão 2.0.0)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o Barramento de Serviços do PEN.

Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:

* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior


## Lista de Melhorias e Correções de Problemas

O foco desta versão foi a implementação de diversas simplificações nos procedimentos de instalação e configuração do módulo, deixando alguns passos opcionais e aplicando várias configurações de forma automática, possibilitando ao administrador modificar caso necessário.

#### Suporte ao SEI 3.1.5

Adicionado o suporte ao SEI 3.1.5 nas três diferentes bases de dados suportadas pela sistema (Mysql, Oracle, SQLServer).


#### Distribuição dos arquivos do módulo posicionados nas pastas corretas do SEI e SIP (gitlab #129)

A forma de distribuição das novas versões do módulo foi modificada para simplificar o processo de instalação. Agora é disponibilizado um pacote de distribuição em que todos os arquivos do módulo estão posicionados nas pastas corretas dentro do sistema SEI e SIP, restando apenas copiar as pastas presente no arquivo .zip descompactado para proceder a instalação/atualização.


#### Simplificação do Processamento de Tarefas enviadas pelo Barramento de Serviços do PEN(gitlab #133)

A implementação das rotinas de consulta e processamento de tarefas enviadas pelo Barramento de Serviços do PEN foi modificada para utilizar o próprio agendamento de tarefas do SEI, tornando a utilização da ferramenta Supervisor opcional e, com isto, simplificando o procedimento de instalação e atualização do módulo.


#### Remoção de janela "Popup" durante trâmite externo do processo (gitlab #134)

Removida a janela "popup" que era apresentada pelo navegador durante o trâmite externo de processos. Normalmente, esta janela é bloqueada automaticamente pelo navegador, o que gerava erros no envio e necessidade de intervenção manual do usuário para proceder o seu desbloqueio.


#### Refatoração do procedimento de processamento de tarefas do Barramento de Serviços do PEN

As rotinas internas de recebimento de processos e documentos foi remodelada para permitir um alto nível de concorrência e agilizar o recebimento de processos.


#### Atribuição automática das permissões ao Perfil Básico (gitlab #121)

Durante a instalação da nova versão do módulo, todas as permissões destinadas ao usuário com perfil Básico são configuradas automaticamente no SIP, evitando este trabalho manual durante a configuração do módulo.


#### Mapeamento automático dos Tipos de Documentos às Espécies Documentais correspondentes (gitlab #126)

Adicionado o mapeamento automático de todos os Tipos de Documentos do SEI para as Espécies Documentais do PEN, evitando o trabalho manual do administrador na configuração de centenas de itens. Esta atualização também é verificada periodicamente para mapear novos tipos de documentos inseridos no SEI.


#### Mapeamento automático das Espécies Documentais aos Tipos de Documentos correspondentes (gitlab #126)

Adicionado o mapeamento automático de todas as Espécies Documentais do PEN para os Tipos de Documentos do SEI, evitando o trabalho manual do administrador na configuração de centenas de itens. Esta atualização também é verificada periodicamente para mapear novos tipos de documentos inseridos no SEI.


#### Checklist de verificação da correta instalação do módulo (gitlab #56) 

Adicionado um novo script de verificação da correta instalação e configuração do módulo, fazendo parte dos passos de instalação do módulo para certificar que o sistema está conectando corretamente com o Barramento de Serviços do PEN.


#### Remoção de scripts .bash para restauração de serviços de processamento de tarefas (gitlab #122)

Removido o script verificar_servicos.sh para monitorar e reiniciar os serviços de conexão ao Barramento por não ser mais necessário com a nova estratégia de integração desta versão.


#### Separação de configurações técnicas de integração em arquivo próprio (gitlab #124)

Todas as configurações técnicas de integração do SEI com o Barramento de Serviços do PEN foram centralizadas em um novo arquivo de configuração específico do mod-sei-pen, permitindo que a própria equipe técnica passa aplicar todas as configurações e testar o correta funcionamento do módulo antes de disponibilizá-lo para a área negocial. Na versão anterior, os administradores do SEI nas áreas de documentação eram responsáveis por estas configurações técnicas.

#### Refatoração da funcionalidade de mapeamento de hipóteses legais (gitlab #125)
Refatorado a funcionalidades de mapeamento de hipóteses legais para eliminar falhas de codificação.


#### Atualização automática das espécies documentais do Barramento de Serviços PEN (gitlab #127)
Implementado a atualização automática das Espécies Documentais disponibilizadas no Barramento de Serviços do PEN para configuração do administrador. A lista de espécies das versões anteriores eram fixas e dependiam da disponibilização de uma nova versão para adicionar novas espécies adicionadas no PEN.

#### Configuração de mapeamento de Espécie Documental Padrão para envio de processo (gitlab #128)
Adicionado funcionalidade para definição da "Espécie Documental Padrão de Envio" para aplicar a todos os documentos do processo que não foram previamente mapeados pelo Administrador do sistema. Aqueles não mapeados serão classificados com a espécies documental configurada como padrão do sistema durante o envio do processo.


#### Configuração de mapeamento de Tipo de Documento Padrão para recebimento de processo (gitlab #128)
Adicionado funcionalidade para definição do "Tipo de Documento Padrão para Recebimento" para aplicar a todos os documentos do processo que não foram previamente mapeados pelo Administrador do sistema. Aqueles não mapeados serão classificados com o tipo de documento configurado como padrão do sistema durante o recebimento do processo.

#### Erro ao recuperar cache de wsdl do serviço do Barramento PEN

Corrigido falha de permissão ao acessar aos arquivos .wsdl de cache do sistema devido a configuração errada do diretŕio de cache.

#### Tratamento de erro para não recusar em caso de falha de comunicação com o Barramento (gitlab #71)

Implementado tratativa de erro para não gerar falha de transmissão de processos caso ocorra uma falha de rede. Nesta versão, o módulo processa novas tentativas de envio antes de gerar um erro por falta de conexão.


#### Criar novo parâmetro para indicar localização do Gearman (gitlab #68)

Adicionado nova configuração no módulo para permitir que seja configurado um servidor centralizado do Gearman para gerenciar todas as tarefas assíncronas de processamento de tarefas do Barramento de Serviços do PEN. Com esta adição, é possível distribuir a carga de processamento entre todos os nós de aplicação do sistema.


#### Tratamento para evitar excesso de logs de erro no sistema

Implementado tratamento para evitar o excesso de logs de erro no SEI em caso de indisponibilidade momentânea do Barramento de Serviços do PEN.

#### Criação de novo pacote de distribuição para o projeto

A instalação das novas versões do módulo deverá ser feita através do novo pacote de distribuição (arquivo mod-sei-pen-VERSAO.zip) que contem somente os arquivos de instalação no SEI, assim como os manuais de instalação, atualização e notas de versão.

#### Utilização de usuário de script para atualização da base de dados

Corrigido script de atualização de Banco de Dados do módulo para utilizar corretamente o usuário de script (UsuarioScript, versão 3.1) configurado nos arquivos de configuração do SEI e SIP.
