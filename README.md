Módulo de Integração SEI - PEN
======================================

Data de criação: 27/05/2016
Data de atualizaçao: 09/02/2018
Versão: 1.1.0


### REQUISITOS PARA INSTALAÇÂO:
* SEI 3.0.5 (ou superior) instalada (verificar valor da constante de versão do SEI no arquivo sei/SEI.php)

* Usuário de acesso ao banco de dados do SEI e SIP com as devidas permissões de acesso para modificar a estrutura do banco de dados. Após a instalação, o usuário de manutenção deverá ser alterado para outro contendo apenas as permissões de leitura e escrita no banco de dados.
* Caso o ambiente do ConectaGov utilizado nesta configuração esteja utilizando HTTPS com certificado digital do ICP-Brasil, será necessário configurar a cadeia de certificados do ICP-BRASIL como confiáveis nos nós de aplicação do SEI. Como todas as comunicações realizadas com o ConectaGov utilizarão conexão segura via HTTPS com certificados digitais, os servidores de aplicação precisam ser configurados para reconhecer esta cadeia como confiável, o que não é padrão. Com isto, os seguintes comandos precisam ser executados em cada nós de aplicação do SEI, incluindo aquele responsável pelo tratamento das tarefas agendadas:

  --  Copie o certificado da cadeia de CA utilizado pelo ConectaGov para o diretório /usr/local/share/ca-certificates:
  > cp <CERTIFICADO-CA-ICP-BRASIL> /usr/local/share/ca-certificates

  --  Efetue a atualização da lista de certificados confiáveis do sistema operacional
  > sudo update-ca-certificates

* Para concluir os procedimentos de configuração do módulo, será necessário registrar no portal do Processo Eletrônico Nacional - PEN as unidades administrativas que poderão realizar trâmites externo ou recebimento de processos/documentos externo no SEI. Este procedimento precisa ser realizado pelo gestor de protocolo previamente habilitado no portal do PEN. Para maiores informações, acesse o endereço eletrônico http://conectagov.processoeletronico.gov.br/ ou entre em contato pelo e-mail processo.eletronico@planejamento.gov.br


### PROCEDIMENTOS PARA INSTALAÇÂO:

1) Fazer backup dos banco de dados do SEI, SIP e repositórios de arquivos.
    
2) Instalar o gearmand e o supervisord no servidor responsável por tratar o agendamento de tarefas do sistema. 
**Importante:** É imprescindível que seja no mesmo nó em que está configurado o CRON de agendamento principal do SEI.
                       
    # Pre-requisito. Caso contrario, os demais pacotes não são encontrados no CentOS7
    yum install epel-release && yum update

    # Instalação do Gearman e supervisord               
    yum install supervisor gearmand libgearman libgearman-devel php56*-pecl-gearman


3) Configuração do supervisor. 
**ATENÇÃO 1:** No parâmetro **'user'** deve ser configurado o usuário que executa o servidor web (verifique no seu servidor qual é o usuario. Ex.: apache)
**ATENÇÂO 2:** Verifique se a localização dos scripts ProcessarPendenciasRN.php e PendenciasTramiteRN.php estão corretas no parâmetro 'command'

    vi /etc/supervisord.conf
    ...
    # Adicione no final do arquivo
    [program:sei_processar_pendencias]
    command=/usr/bin/php -c /etc/php.ini /opt/sei/web/modulos/pen/rn/ProcessarPendenciasRN.php
    numprocs=1
    directory=/opt/sei/web
    user=apache
    autostart=true
    autorestart=true
    log_stderr=true
    stdout_logfile=/var/log/supervisor/sei-supervisord-stdout.log
    stderr_logfile=/var/log/supervisor/sei-supervisord-stderr.log

    [program:sei_monitorar_pendencias]
    command=/usr/bin/php -c /etc/php.ini /opt/sei/web/modulos/pen/rn/PendenciasTramiteRN.php
    numprocs=1
    directory=/opt/sei/web
    user=apache
    autostart=true
    autorestart=true
    log_stderr=true
    stdout_logfile=/var/log/supervisor/sei-supervisord-stdout.log
    stderr_logfile=/var/log/supervisor/sei-supervisord-stderr.log

4) Configurar agendamento de tarefas de reinicialização de serviços caso se identifique os estes estão indisponíveis

Esta configuração é recomendada como contingência em conjunto com o Supervisor para garantir que os serviços de integração não serão desativados em caso de indisponibilidade momentânea da infraestrutura do ConectaGov.

No mesmo servidor em que está instalado/configurado o supervisor e gearman (passo 3), configurar o script **verificar-servicos.sh**, localizado na raiz do módulo, no serviço de agendamento CRON. Sugerimos que o tempo de monitoramento e tentativa de reativação seja configurado em 10 minutos.

4.1) Copiar script de verificação dos serviços de integração do ConectaGov para a pasta de arquivos binários do SEI 
Atenção: Altere a referência para [DIRETORIO_RAIZ_INSTALAÇÃO] descrito abaixo
  cp [DIRETORIO_RAIZ_INSTALAÇÃO]/sei/web/modulos/pen/verificar-servicos.sh /opt/sei/bin/

4.2) Configurar agendamento no cron
Atenção: Altere a referência para [DIRETORIO_RAIZ_INSTALAÇÃO] descrito abaixo

  # crontab -e 
  */10 * * * * [DIRETORIO_RAIZ_INSTALAÇÃO]/sei/bin/verificar-servicos.sh

6) Editar o arquivo "sei/ConfiguracaoSEI.php", tomando o cuidado de usar editor que não altere o charset do arquivo, para adicionar a referência ao módulo PEN na chave 'Modulos' abaixo da chave 'SEI':    
Atenção para as virgulas nos finais das linhas

        'SEI' => array(
            'URL' => 'http://[servidor sei]/sei',
            'Producao' => true,
            'RepositorioArquivos' => '/var/sei/arquivos',
            'Modulos' => array(),
            ),
                
Adicionar a referência ao módulo PEN na array da chave 'Modulos' indicada acima:
            
    'Modulos' => array('PENIntegracao' => 'pen')
    
7) Mover o diretório de arquivos do módulo "pen" para o diretório sei/web/modulos/. Importante renomear a pasta do módulo "mod-sei-pen" para somente "pen" por questões de padronização de nomenclatura.

8) Mover o arquivo do certificado digital utilizado para integração com o ConectaGov para o diretório "sei/config/".

Os certificados digitais necessários para conectar aos ambientes de desenvolvimento e homologação do PEN estão localizados no paco​te de instalação. Para o ambiente de produção, deverá ser utilizado um certificado digital válido gerado por uma Autoridade de Registro - AR confiável (Exemplo: ICP-Brasil, Certisign, Verisign, etc.).

9) MOVER o arquivo de instalação do módulo no SEI **sei_atualizar_versao_modulo_pen.php** para a pasta sei/scripts. Lembre-se de mover, e não copiar, por questões de segurança e padronização.

10) MOVER o arquivo de instalação do módulo no SIP **sip_atualizar_versao_modulo_pen.php** para a pasta sip/scripts. Lembre-se de mover, e não copiar, por questões de segurança e padronização.
        
11) Executar o script "sip_atualizar_versao_modulo_pen.php" para atualizar o banco de dados do SIP para o funcionamento do módulo:    
Atenção: Altere a referência para [DIRETORIO_RAIZ_INSTALAÇÃO] descrito abaixo

    # php -c /etc/php.ini [DIRETORIO_RAIZ_INSTALAÇÃO]/sip/scripts/sip_atualizar_versao_modulo_pen.php
            
12) Executar o script **sei_atualizar_versao_modulo_pen.php** para inserção de dados no banco do SEI referente ao módulo.
Atenção: Altere a referência para [DIRETORIO_RAIZ_INSTALAÇÃO] descrito abaixo

    # php -c /etc/php.ini [DIRETORIO_RAIZ_INSTALAÇÃO]/sei/scripts/sei_atualizar_versao_modulo_pen.php

13) Configurar as unidades do SEI que poderão realizar o envio e recebimento de trâmites externos
Os ID's de unidades são gerenciados pela própria instituiçao no portal do Processo Eletrônico Nacional: http://conectagov.processoeletronico.gov.br. 
Na fase de homologação do sistema, estes valores serão passados pela SETIC/MP.

Acesse o menu [SEI > Administração > Processo Eletrônico Nacional > Mapeamento de Unidades] e vincule as unidades administrativas com seus respectivos identificadores registrados no portal do Processo Eletrônico Nacional.

14) Configuração de unidade administrativa virtual para gerenciamento de envio e recebimento de processos pelo módulo.

Esta configuração é necessária para o SEI realizar as devidas regras de registro de históricos de trâmites externos e bloqueio de edição metadados de processos/documentos. Tal unidade será utilizada internamente pelo módulo e não deverá ter acesso liberado para nenhum usuário do sistema.

14.1) Acessar o SIP e criar uma nova unidade administrativa com as seguintes configurações:

    sigla: EXTERNO
    nome: Unidade Externa

14.2) Configurar a nova unidade na hierarquia do SEI, através da funcionalidade SIP >> Hierarquias >> Montar

Sugerimos que está unidade seja configurada no mesmo nível hierárquico da unidade de teste padrão existente no SEI. Para saber qual é a unidade de testes, basta verificar o parâmetro do SEI chamado **SEI_UNIDADE_TESTE**

15) Configuração de tipo de processo a ser aplicado aos processos recebidos de outras instituições.

Como o processo de recebimento de novos processos será feito de forma automática pelo módulo de integração, o sistema precisa atribuir um Tipo de Processo padrão para o novo procedimento recebido. Importante lembrar que a criação de um novo tipo de processo não é obrigatório, sendo possível utilizar outro pré-existente. 

Caso a opção for pela criação de um novo tipo de processo específico, segue abaixo sugestão para configuração:

15.1) Criar um novo Tipo de Processo pela funcionalidade *SEI >> Administração >> Tipo de Processo*

> Nome: Demanda Externa: Outros Órgãos Públicos        Descrição:
> Processos recebidos de outras instituições Sugestão de Assuntos: [A
> CLASSIFICAÇÃO DE ASSUNTO DEVE SER DEFINIDO JUNTAMENTE COM A ÁREA DE DOCUMENTAÇÃO] 
> Ex: 019.01 - INFORMAÇÕES SOBRE O ÓRGÃO 
> Restringir aos Órgãos: [vazio] 
> Restringir às Unidades: [vazio] 
> Níveis de Acesso Permitidos: Público 
> Nível de Acesso Sugerido: Público 
> Processo único no órgão por usuário: Não
> Interessado: Não 
> Interno do Sistema: Sim


16) Configurar os parâmetros do Módulo de Integração Pen (Menu: *SEI >> Administração >> Processo Eletrônico Nacional >> Parâmetros de Configuração*)
    
**Endereço do Web Service:**  [Endereço dos serviços de integração do PEN]
- Desenvolvimento: https://pen-api.trafficmanager.net/interoperabilidade/soap/v2/
- Homologação: https://homolog.pen.api.trafficmanager.net/interoperabilidade/soap/v2/
- Produção: https://api.conectagov.processoeletronico.gov.br/interoperabilidade/soap/v2/

**Endereço do Web Service de Pendências**: [Endereço dos serviços de notificação de trâmites de processos]
- Desenvolvimento: https://pen-pendencias.trafficmanager.net/
- Homologação: https://homolog.pen.pendencias.trafficmanager.net/
- Produção: https://pendencias.conectagov.processoeletronico.gov.br/

**ID do Repositório de Estruturas:** 
ID do repositório de origem do órgão na estrutura organizacional. Este identificador é enviado para a instituição junto com o pacote de integração.
Exemplo: 1 (Código de identificação da estrutura organizacional do Poder Executivo [SIORG])

**Localização do Certificado Digital:** 
Localização do certificado digital o órgão (arquivo do passo 8)

**Número Máximo de Tentativas de Recebimento:**
Valor padrão: 3
            
**Tamanho Máximo de Documentos Expedidos:**
Valor padrão: 50

**Senha do Certificado Digital:** 
Senha do certificado digital 
Atenção: Configuração de senha será modificada na próxima versão para utilização de criptografia

**Tipo de Processo Externo:** 
Id do tipo de documento externo. Configurar com o ID do Tipo de Processo Externo configurado no passo 15

**Unidade Geradora de Processo e Documento Recebido:** 
Id da unidade de origem que serão atribuídos os documentos recebidos de um outro órgão. Configurar com o ID da Unidade criada no passo 14


17) Iniciar Gearman e Supervisor
Atenção: Altere a referência para [DIRETORIO_RAIZ_INSTALAÇÃO] descrito abaixo:

    service gearmand start && service supervisord start

Executar o comando "ps -ef" e verificar se os dois processos seguintes estão em execução: 

    /usr/bin/php -c /etc/php.ini [DIRETORIO_RAIZ_INSTALAÇÃO]/sei/modulos/pen/rn/PendenciasTramiteRN.php
    
    /usr/bin/php -c /etc/php.ini [DIRETORIO_RAIZ_INSTALAÇÃO]/sei/modulos/pen/rn/ProcessarPendenciasRN.php

Caso não esteja houve algum problema de configuração e a expedição de processos não irá funcionar. 
Atenção: Importante colocar o serviço para ser iniciado automaticamente juntamente com o servidor. 

18) Realizar o mapeamento de tipos de documentos do SEI com as especies documentais definidas no PEN, tanto de envio quanto de recebimento. 

Esta configuração deve ser feita antes de começar a utilização do módulo.
- SEI >> Administração >> Processo Eletrônico Nacional >> Mapeamento de Tipos de Documentos >> Envio >> Cadastrar
- SEI >> Administração >> Processo Eletrônico Nacional >> Mapeamento de Tipos de Documentos >> Recebimento >> Cadastrar

Obs.: Os tipos de documentos a serem mapeados deverão estar configurados no SEI como Externo ou Interno/Externo 

19) Realizar o mapeamento das hipóteses legais do SEI com as definidas no PEN para permitir o trâmite externo de processos e documentos restritos.
Atenção: Antes de iniciar esta configuração, será necessário executar manualmente o agendamento "PENAgendamentoRN::atualizarHipotesesLegais" em [SEI >> Infra >> Agendamentos]. Isto será necessário para atualizar o SEI com a última versão da tabela de hipóteses legais do PEN.

Este mapeamento deve ser feito antes de começar a utilização do módulo e está disponível em 
- SEI >> Administração >> Processo Eletrônico Nacional >> Mapeamento de Hipóteses Legais >> Envio >> Cadastrar
- SEI >> Administração >> Processo Eletrônico Nacional >> Mapeamento de Hipóteses Legais >> Recebimento >> Cadastrar

Obs.: Os tipos de documentos a serem mapeados deverão estar configurados no SEI como Externo ou Interno/Externo 

        
19) O protocolo de comunicação implementado pelo PEN realiza a geração e assinatura digital de recibos de entrega e conclusão dos trâmites de processo. Para a correta geração dos recibos pelo módulo, é indispensável que todos os nós da aplicação estejam configurados com o serviço de sincronização de relógios oficial NTP.br.    

Este link pode ajudar a configurar conforme o SO utilizado: http://ntp.br/guia-linux-comum.php


20) Configurar as permissões de segurança para os perfis e unidades que poderão realizar o trâmite externo de processos no sistema. 

Por padrão, as funcionalidades (recursos) criados pelo módulo não são atribuídos automaticamente à um perfil específico do sistema, evitando sua disponibilização para todos os usuários do sistema sem a prévia definição dos administradores.

Sugerimos que seja criado um novo perfil de usuário que receberá as permissões aos novos recursos incluidos pelo módulo (pen_*). Este novo perfil deverá ser atribuído aos usuários que podem realizar o trâmite externo de processos para outras instituições.

Sugerimos a configuração dos seguintes recursos no novo perfil criado para trâmites de processos externos:
* pen_procedimento_expedido_listar
* pen_procedimento_expedir

Sugerimos a configuração dos seguintes recursos no perfil ADMINISTRADOR:
* pen_map_hipotese_legal_envio_alterar
* pen_map_hipotese_legal_envio_cadastrar  
* pen_map_hipotese_legal_envio_excluir    
* pen_map_hipotese_legal_envio_listar 
* pen_map_hipotese_legal_padrao   
* pen_map_hipotese_legal_padrao_cadastrar 
* pen_map_hipotese_legal_recebimento_alterar  
* pen_map_hipotese_legal_recebimento_cadastrar    
* pen_map_hipotese_legal_recebimento_excluir  
* pen_map_hipotese_legal_recebimento_listar
* pen_map_tipo_documento_envio_alterar    
* pen_map_tipo_documento_envio_cadastrar  
* pen_map_tipo_documento_envio_excluir    
* pen_map_tipo_documento_envio_listar 
* pen_map_tipo_documento_envio_visualizar 
* pen_map_tipo_documento_recebimento_alterar  
* pen_map_tipo_documento_recebimento_cadastrar    
* pen_map_tipo_documento_recebimento_excluir  
* pen_map_tipo_documento_recebimento_listar   
* pen_map_tipo_documento_recebimento_visualizar   
* pen_map_unidade_alterar 
* pen_map_unidade_cadastrar   
* pen_map_unidade_excluir 
* pen_map_unidade_listar  
* pen_parametros_configuracao 
* pen_parametros_configuracao_alterar 

