# Manual do Módulo ConectaGov

O módulo **conectagov** é o responsável por integrar o Sistema Eletrônico de Informações - SEI à plataforma de interoperabilidade do Processo Eletrônico Nacional - PEN. 

O projeto ConectaGov tem como objetivo interligar todos os sistema de processo eletrônico do Poder Executivo Federal a fim de simplificar a troca de documentos oficiais entre instituições de forma rápida e segura. 

A utilização deste módulo adicionará novas funcionalidades ao SEI permitindo, entre outros:
 - Enviar processos administrativos para instituições externas
 - Receber processos administrativos de outros órgãos
 - Acompanhar a relação de processos em trâmite externo
 
Para maiores informações sobre o ConectaGov e o PEN, acesse http://www.planejamento.gov.br/pensei.

Este manual está estruturado nas seguintes seções:

 1. **Instalação**
Procedimentos de instalação do módulo nos servidores de aplicação e atualização do banco de dados.

 3. **Configuração**
 Orientações voltadas para o administrador do sistema configurar os parâmetros do módulo para o correto funcionamento da integração.
 
 4. **Utilização**
 Apresentação das funcionalidades que permitem o trâmite externo de processos e o acompanhamento de seu histórico.
 
 5. **Suporte**
 Canais de comunicação para resolver problemas ou tirar dúvidas sobre o módulo e os demais componentes do ConectaGov.

## Instalação

### Pré-requisitos
 - SEI versão 3.0.5 ou superior instalada.
 - Usuário de acesso ao banco de dados do SEI e SIP com  permissões para criar novas estruturas no banco de dados.
  
Para iniciar os procedimentos de configuração do módulo, será necessário registrar no **ConectaGov** as unidades administrativas que poderão realizar o envio e recebimento de processos/documentos externo no SEI. Este procedimento precisa ser realizado pelo **Gestor de Protocolo** previamente habilitado no portal do **ConectaGov**. Para maiores informações, acesse  http://conectagov.processoeletronico.gov.br/ ou entre em contato pelo e-mail processo.eletronico@planejamento.gov.br

### Procedimentos

1. Fazer backup dos banco de dados do SEI e SIP e dos arquivos de configuração do sistema.
 
2. Instalar o **gearmand** e o **supervisord** no servidor responsável por tratar o agendamento de tarefas do sistema.
Estes dois componentes são utilizados para gerenciar a fila de recebimento de novos processos de forma assíncrona pelo SEI.
**Importante:** É imprescindível que os dois sejam instalados no mesmo nó de aplicação em que está configurado o CRON de agendamento principal do SEI.

    Exemplo de instalação do German e Supervisor no CentOS:

        # pre-requisito
        yum install epel-release && yum update

        # instalação do gearman e supervisord               
        yum install supervisor gearmand libgearman libgearman-devel php56*-pecl-gearman

3. Configuração dos serviços de recebimento de processos

    Neste passo será configurado os dois scripts PHP responsáveis por fazer monitoramento de pendências de trâmite no ConectaGov e processar o recebimento de novos processos.

    As linhas de configuração apresentadas abaixo deverão ser adicionadas no final do arquivo de configuração do *Supervisor* (/etc/supervisord.conf).
    
    **Atenção 1:** No parâmetro *[user]* deve ser configurado o usuário que executa o servidor web (verifique no seu servidor qual é o usuario. Ex.: apache)
**Atenção 2:** Verifique se a localização dos scripts ProcessarPendenciasRN.php e PendenciasTramiteRN.php estão corretas no parâmetro *[command]*.

    Exemplo de configuração do supervisor:

        # adicione no final do arquivo
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

4. Configurar a tarefa de reinicialização de serviços caso se identifique possíveis indisponibilidades.

    Esta configuração é recomendada como contingência para garantir que os serviços de integração não serão desativados em caso de indisponibilidade momentânea da infraestrutura do ConectaGov.

    Os procedimento descritos abaixo deverão ser executados no mesmo servidor em que está instalado o **supervisor** e o **gearman** (passo 3). 

    Mova o script **verificar-servicos.sh**, localizado na raiz do diretório do módulo, para a pasta de scripts do SEI. 

        cp [DIRETORIO_RAIZ_INSTALAÇÃO]/sei/web/modulos/pen/verificar-servicos.sh /opt/sei/bin/

    Configure este script no serviço de agendamento CRON com uma periodicidade sugerida de 10 minutos, tempo este utilizado para o devido monitoramento e tentativa de reativação dos serviços.

        # crontab -e 
        */10 * * * * [DIRETORIO_RAIZ_INSTALAÇÃO]/sei/bin/verificar-servicos.sh

5.  Configurar módulo ConectaGov no arquivo de configuração do SEI

    Editar o arquivo **sei/ConfiguracaoSEI.php**, tomando o cuidado de usar editor que não altere o charset ISO 5589-1 do arquivo, para adicionar a referência ao módulo PEN na chave **[Modulos]** abaixo da chave **[SEI]**:    

        'SEI' => array(
            'URL' => 'http://[servidor sei]/sei',
            'Producao' => true,
            'RepositorioArquivos' => '/var/sei/arquivos',
            'Modulos' => array('PENIntegracao' => 'pen'),
            ),

    Adicionar a referência ao módulo PEN na array da chave 'Modulos' indicada acima:
            
        'Modulos' => array('PENIntegracao' => 'pen')

6.  Mover o diretório de arquivos do módulo "pen" para o diretório sei/web/modulos/
    Importante renomear a pasta do módulo "mod-sei-pen" para somente "pen" por questões de padronização de nomenclatura.

7. Mover o arquivo do certificado digital utilizado para integração com o **ConectaGov** para o diretório "sei/config/".

    Os certificados digitais para conectar aos ambientes de desenvolvimento e homologação do PEN estão localizados no paco​te de instalação disponibilizado pela equipe técnica do Ministério do Planejamento, Desenvolvimento e Gestão - MPDG e são disponibilizados no ato do credenciamento da instituição no ConectaGov. 

    Para o ambiente de produção, deverá ser utilizado um certificado digital válido gerado por uma Autoridade de Registro - AR confiável (Exemplo: ICP-Brasil, Certisign, Verisign, etc.).

    Maiores informações e solicitações podem ser feitas através do e-mail processo.eletrônico@planejamento.gov.br.

8. Mover o arquivo de instalação do módulo no SEI **sei_atualizar_versao_modulo_pen.php** para a pasta **sei/scripts**. Lembre-se de mover, e não copiar, por questões de segurança e padronização.

9. Mover o arquivo de instalação do módulo no SIP **sip_atualizar_versao_modulo_pen.php** para a pasta **sip/scripts**. Lembre-se de mover, e não copiar, por questões de segurança e padronização.


10. Executar o script **sip_atualizar_versao_modulo_pen.php** para atualizar o banco de dados do SIP para o funcionamento do módulo:

        # php -c /etc/php.ini [DIRETORIO_RAIZ_INSTALAÇÃO]/sip/scripts/sip_atualizar_versao_modulo_pen.php

11. Executar o script **sei_atualizar_versao_modulo_pen.php** para inserção de dados no banco do SEI referente ao módulo.

        # php -c /etc/php.ini [DIRETORIO_RAIZ_INSTALAÇÃO]/sei/scripts/sei_atualizar_versao_modulo_pen.php

12. Após a instalação do módulo, o usuário de manutenção deverá ser alterado para outro contendo apenas as permissões de leitura e escrita no banco de dados.

13. Configurar as permissões de segurança para os perfis e unidades que poderão realizar o trâmite externo de processos. 

    Por padrão, as funcionalidades (recursos) criados pelo módulo não são atribuídos automaticamente à um perfil específico do sistema, evitando sua disponibilização para todos os usuários do sistema sem a prévia definição dos administradores.

    Sugerimos que seja criado um novo perfil de usuário que receberá as permissões incluídas pelo módulo (pen_*). Este novo perfil deverá ser atribuído aos usuários que poderão realizar o trâmite externo de processos para outras instituições. 

    Para criação do novo perfil e atribuição dos devidos recursos, acesse [**SIP > Perfil > Novo**]
    Exemplo: *Perfil: Trâmite Externo*
    Recursos: 
    - pen_procedimento_expedido_listar
    - pen_procedimento_expedir

    Também será necessário a configuração dos seguintes recursos ao perfil ADMINISTRADOR para permitir o mesmo realizar as configurações do módulo:
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

14. Configurar as unidades do SEI que poderão realizar o envio e recebimento de trâmites externos

    Os ID's de unidades são gerenciados pela própria instituição no portal do Processo Eletrônico Nacional ( http://conectagov.processoeletronico.gov.br). 
No credenciamento da instituição, estes valores serão passados pela unidade de TI  do MPDG.

    Acesse o menu **[SEI > Administração > Processo Eletrônico Nacional > Mapeamento de Unidades]** e vincule as unidades administrativas com seus respectivos identificadores registrados no portal do Processo Eletrônico Nacional.

15. Configuração de unidade administrativa virtual para gerenciamento de envio e recebimento de processos pelo módulo.

    Esta configuração é necessária para o SEI realizar as devidas regras de registro de históricos de trâmites externos e bloqueio de edição metadados de processos/documentos. Tal unidade será utilizada internamente pelo módulo e não deverá ter acesso liberado para nenhum usuário do sistema.

    15.1. Acessar o SIP e criar uma nova unidade administrativa com as seguintes configurações:
    
        Sigla: EXTERNO
        Nome: Unidade Externa

    15.2. Configurar a nova unidade na hierarquia do SEI, através da funcionalidade **[SIP > Hierarquias > Montar]**

    Sugerimos que está unidade seja configurada no mesmo nível hierárquico da unidade de teste padrão existente no SEI. Para saber qual é a unidade de testes, basta verificar o parâmetro do SEI chamado **SEI_UNIDADE_TESTE**

16. Configuração de tipo de processo a ser aplicado aos processos recebidos de instituições externas.

    Como o processo de recebimento de novos processos será feito de forma automática pelo módulo de integração, o sistema precisa atribuir um Tipo de Processo padrão para o novo procedimento recebido. Importante lembrar que a criação de um novo tipo de processo não é obrigatório, sendo possível utilizar outro pré-existente. 

    Caso a opção for pela criação de um novo tipo de processo específico, segue abaixo sugestão para configuração:

        Nome: Demanda Externa: Outros Órgãos Públicos 
        Descrição: Processos recebidos de outras instituições 
        // O assunto deve ser definido juntamente com a área de documentação
        Sugestão de Assuntos: 019.01 - INFORMAÇÕES SOBRE O ÓRGÃO
        Restringir aos Órgãos: [vazio] 
        Restringir às Unidades: [vazio] 
        Níveis de Acesso Permitidos: Restrito e Público 
        Nível de Acesso Sugerido: Público 
        Processo único no órgão por usuário: Não
        Interessado: Não 
        Interno do Sistema: Sim

17. Configurar os parâmetros do Módulo de Integração Pen
Acesse a funcionalidade **[SEI > Administração > Processo Eletrônico Nacional > Parâmetros de Configuração]** para configurar os parâmetros de funcionamento do módulo.
 - **Endereço do Web Service:**  
 *Endereço dos serviços de integração do PEN* 
    - Desenvolvimento: https://pen-api.trafficmanager.net/interoperabilidade/soap/v2/
    - Homologação: https://homolog.pen.api.trafficmanager.net/interoperabilidade/soap/v2/
    - Produção: https://api.conectagov.processoeletronico.gov.br/interoperabilidade/soap/v2/

- **Endereço do Web Service de Pendências**: 
*Endereço dos serviços de notificação de trâmites de processos*
    - Desenvolvimento: https://pen-pendencias.trafficmanager.net/
    - Homologação: https://homolog.pen.pendencias.trafficmanager.net/
    - Produção: https://pendencias.conectagov.processoeletronico.gov.br/

- **ID do Repositório de Estruturas:** 
*ID do repositório de origem do órgão na estrutura organizacional. Este identificador é enviado para a instituição junto com o pacote de integração.*

    Exemplo: 
    Valor 1 (Código de identificação da estrutura organizacional do Poder Executivo - SIORG)

- **Localização do Certificado Digital:** 
*Localização do certificado digital o órgão (arquivo do passo 8)*

- **Número Máximo de Tentativas de Recebimento:**
*Valor padrão: 3*
            
- **Tamanho Máximo de Documentos Expedidos:**
*Valor padrão: 50*

- **Senha do Certificado Digital:** 
*Senha do certificado digital* 
**Atenção**: Configuração de senha será modificada na próxima versão para utilização de criptografia

- **Tipo de Processo Externo:** 
*Id do tipo de documento externo. Configurar com o ID do Tipo de Processo Externo configurado no passo 15*

- **Unidade Geradora de Processo e Documento Recebido:** 
*Id da unidade de origem que serão atribuídos os documentos recebidos de um outro órgão. Configurar com o ID da Unidade criada no passo 14*


18. Iniciar serviços de monitoramento de pendências de trâmite **Gearman** e **Supervisor**

        # service gearmand start && service supervisord start

    Executar o comando **ps -ef** e verificar se os dois processos seguintes estão em execução: 

        # /usr/bin/php -c /etc/php.ini [DIRETORIO_RAIZ_INSTALAÇÃO]/sei/modulos/pen/rn/PendenciasTramiteRN.php    
        # /usr/bin/php -c /etc/php.ini [DIRETORIO_RAIZ_INSTALAÇÃO]/sei/modulos/pen/rn/ProcessarPendenciasRN.php

    Caso não esteja houve algum problema de configuração e a expedição de processos não irá funcionar. 
**Atenção**: Importante colocar o serviço para ser iniciado automaticamente juntamente com o servidor. 

19. Realizar o mapeamento de tipos de documentos do SEI com as especies documentais definidas no PEN, tanto de envio quanto de recebimento. 

    Esta configuração deve ser feita antes de começar a utilização do módulo.
    - SEI >> Administração >> Processo Eletrônico Nacional >> Mapeamento de Tipos de Documentos >> **Envio** >> Cadastrar
    - SEI >> Administração >> Processo Eletrônico Nacional >> Mapeamento de Tipos de Documentos >> **Recebimento** >> Cadastrar

    **Observação**: Os tipos de documentos a serem mapeados deverão estar configurados no SEI como Externo ou Interno/Externo 


20. Realizar o mapeamento das hipóteses legais do SEI com as definidas no PEN para permitir o trâmite externo de processos e documentos restritos.

    **Atenção**: Antes de iniciar esta configuração, será necessário executar manualmente o agendamento **PENAgendamentoRN::atualizarHipotesesLegais** em [**SEI > Infra > Agendamentos**]. Esta tarefa é necessária para atualizar o SEI com a última versão da tabela de hipóteses legais do PEN.

    Este mapeamento deve ser feito antes de começar a utilização do módulo e está disponível em 
    - SEI >> Administração >> Processo Eletrônico Nacional >> Mapeamento de Hipóteses Legais >> **Envio** >> Cadastrar
    - SEI >> Administração >> Processo Eletrônico Nacional >> Mapeamento de Hipóteses Legais >> **Recebimento** >> Cadastrar

    **Observação**: Os tipos de documentos a serem mapeados deverão estar configurados no SEI como Externo ou Interno/Externo 

21. O protocolo de comunicação implementado pelo PEN realiza a geração e assinatura digital de recibos de entrega e conclusão dos trâmites de processo. Para a correta geração dos recibos pelo módulo, é indispensável que todos os nós da aplicação estejam configurados com o serviço de sincronização de relógios oficial NTP.br.    

    Este link pode ajudar a configurar conforme o SO utilizado: http://ntp.br/guia-linux-comum.php

## Utilização

## Suporte

## Problemas Conhecidos

### Problema com validação de certificados HTTPS

Caso o ambiente do ConectaGov utilizado nesta configuração esteja utilizando HTTPS com certificado digital não reconhecido (como ICP-Brasil), será necessário configurar a cadeia não reconhecida como confiáveis nos servidores de aplicação do SEI. Com isto, os seguintes comandos precisam ser executados em cada nós de aplicação do SEI, incluindo aquele responsável pelo tratamento das tarefas agendadas:

Copie o certificado da cadeia de CA utilizado pelo ConectaGov para o diretório /usr/local/share/ca-certificates:

    cp <CADEIA-CERTIFICADO-CA> /usr/local/share/ca-certificates

Efetue a atualização da lista de certificados confiáveis do sistema operacional

    sudo update-ca-certificates
